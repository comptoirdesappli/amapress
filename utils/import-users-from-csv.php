<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use ForceUTF8\Encoding;

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class Amapress_Import_Users_CSV {
	private static $log_dir_path = '';
	private static $log_dir_url = '';
	private static $log_file_name = '';
	private static $initialized = false;

	/**
	 * Initialization
	 *
	 * @since 0.1
	 **/
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		$upload_dir          = wp_upload_dir();
		$user_name           = amapress_current_user_id();
		self::$log_file_name = "amapress_import_users.{$user_name}.log";
		self::$log_dir_path  = trailingslashit( $upload_dir['basedir'] );
		self::$log_dir_url   = trailingslashit( $upload_dir['baseurl'] );

		self::$initialized = true;
	}

	public static function generateModel(
		$name, $data_keys = array(
		'user_email',
		'first_name',
		'last_name'
	), $multi_fields = array()
	) {
		$post_fields = AmapressEntities::getPostTypeFields( 'user' );
		$sitename    = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty( $sitename ) ) {
			$sitename .= '.';
		}
		$filename = $sitename . $name . '.import_model';

//		$data_keys = array(
//			'ID', 'user_login', 'user_pass',
//			'user_nicename', 'user_email', 'user_url',
//			'user_registered', 'user_activation_key', 'user_status',
//			'display_name', 'roles'
//		);
		$meta_keys = array();
		foreach ( $post_fields as $k => $v ) {
			if ( ( ( ! isset( $v['csv'] ) || $v['csv'] !== false ) && ( ! isset( $v['csv_import'] ) || $v['csv_import'] !== false ) )
			     && ( ! in_array( $v['type'], TitanFrameworkOption::$csvImportExcludedTypes )
			          || ( isset( $v['csv'] ) && $v['csv'] === true )
			          || ( isset( $v['csv_import'] ) && $v['csv_import'] === true ) ) ) {
				$meta_keys[ $k ] = $k;
			}
		}
//		foreach ($multi_fields as $f) {
//			unset($meta_keys[$f]);
//			/** @var TitanFrameworkOption $option */
//			$option = isset($post_fields[$f]['tf_option']) ? $post_fields[$f]['tf_option'] : null;
//			if (!$option) continue;
//			if (is_a($option, 'TitanFrameworkOptionSelect')) {
//				foreach ($option->fetchOptionsWithCache() as $k => $value) {
//					if (empty($k)) continue;
//					$meta_keys[$k] = $value;
//				}
//			} else {
//				die("Unsupported multi type on $f");
//			}
//		}
		$taxonomies_keys   = array();
		$taxonomies_names  = array();
		$taxonomies_values = array();
		foreach ( get_object_taxonomies( 'user', 'objects' ) as $tax_name => $tax ) {
			$taxonomies_keys[]              = $tax_name;
			$taxonomies_names[ $tax_name ]  = $tax->label;
			$taxonomies_values[ $tax_name ] = get_terms( $tax_name,
				array(
					'taxonomy'   => $tax_name,
					'hide_empty' => false,
					'fields'     => 'names',
				) );;
		}
		$fields = array_merge( array_combine( $data_keys, $data_keys ), $meta_keys );

		$options      = array();
		$headers      = array();
		$headers_desc = array();
		foreach ( $fields as $key => $field ) {
			$arg = [
				'key'       => $key,
				'field'     => $field,
				'multi'     => - 1,
				'post_type' => 'user',
			];
			if ( in_array( $field, $taxonomies_names ) ) {
				$headers[ $key ]      = $taxonomies_names[ $field ];
				$headers_desc[ $key ] = 'Saisir une ou plusieurs des étiquettes suivantes séparées par des virgules';
				$options[ $key ]      = $taxonomies_values[ $field ];
			} else {
				$header = apply_filters( "amapress_users_get_field_display_name", $field );
				$desc   = apply_filters( "amapress_users_get_field_desc", '', $field );
				if ( empty( $header ) ) {
					unset( $fields[ $key ] );
					continue;
				}
				$headers[ $key ] = $header;
				$option          = AmapressEntities::getTfOption( 'user', $field );
				if ( $option ) {
					$options[ $key ] = $option->getSamplesForCSV( $arg );
					if ( empty( $desc ) ) {
						$desc = $option->getDesc();
					}
				} else if ( 'role' == $key || 'roles' == $key ) {
					global $wp_roles;
					$roles = [];
					foreach ( $wp_roles->roles as $name => $role ) {
						if ( strpos( strtolower( $role['name'] ), 'amap' ) === false ) {
							continue;
						}
						$roles[] = $role['name'];
					}
					$options[ $key ] = $roles;
					if ( empty( $desc ) ) {
						$desc = 'Choisir un rôle pour l\'utilisateur';
					}
				} else {
					$options[ $key ] = array();
				}
				$headers_desc[ $key ] = wp_strip_all_tags( $desc );
			}
		}

		$required_headers = apply_filters( 'amapress_csv_users_import_required_headers', array(
			'user_email',
			'first_name',
			'last_name'
		) );

		require_once( AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php' );

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator( "Amapress" )
		            ->setLastModifiedBy( "Amapress" )
		            ->setTitle( $filename );
		$objPHPExcel->setActiveSheetIndex( 0 );
		$sheet = $objPHPExcel->getActiveSheet();
		$col   = 0;
		foreach ( $headers as $key => $h ) {
			$sheet->setCellValueByColumnAndRow( $col, 1, $h );
			if ( ! empty( $headers_desc[ $key ] ) ) {
				$sheet->getCommentByColumnAndRow( $col, 1 )->getText()->createTextRun( $headers_desc[ $key ] );
				$sheet->getCommentByColumnAndRow( $col, 1 )->setVisible( false );
			}
			$sheet->getStyleByColumnAndRow( $col, 1 )->applyFromArray( array(
				'font' => array(
					'bold'   => true,
					'italic' => ! in_array( $key, $required_headers ),
				)
			) );

			$line = 2;
			foreach ( $options[ $key ] as $k => $opt ) {
				if ( ! is_int( $k ) && empty( $k ) ) {
					continue;
				}
				$sheet->setCellValueByColumnAndRow( $col, $line, $opt );
				$line += 1;
			}

			$col += 1;
		}
		$sheet->setTitle( $name );

		// Redirect output to a client’s web browser (Excel2007)
		@ob_clean();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="' . $filename . '.xlsx"' );
		header( 'Cache-Control: max-age=0' );
		// If you're serving to IE 9, then the following may be needed
		header( 'Cache-Control: max-age=1' );
		// If you're serving to IE over SSL, then the following may be needed
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' ); // Date in the past
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); // always modified
		header( 'Cache-Control: cache, must-revalidate' ); // HTTP/1.1
		header( 'Pragma: public' ); // HTTP/1.0
		$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		Amapress::outputExcel( $objWriter );
		exit;
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public static function process_users_csv_import() {
		self::init();
		if ( isset( $_POST['_wpnonce-amapress-import-users-users-page_import'] ) ) {
			check_admin_referer( 'amapress-import-users-users-page_import', '_wpnonce-amapress-import-users-users-page_import' );

			if ( ! empty( $_FILES['users_csv']['tmp_name'] ) ) {

				// Setup settings variables
				$filename              = $_FILES['users_csv']['tmp_name'];
				$password_nag          = isset( $_POST['password_nag'] ) ? $_POST['password_nag'] : false;
				$users_update          = isset( $_POST['users_update'] ) ? $_POST['users_update'] : false;
				$new_user_notification = isset( $_POST['new_user_notification'] ) ? $_POST['new_user_notification'] : false;

				$results = self::import_users_csv( $filename, array(
					'password_nag'          => $password_nag,
					'new_user_notification' => $new_user_notification,
					'users_update'          => $users_update
				) );

				// No users imported?
				if ( ! $results['user_ids'] ) {
					wp_redirect( add_query_arg(
						[
							'import'   => 'fail',
							'total'    => $results['total'],
							'imported' => $results['imported']
						],
						wp_get_referer() ) );
				} // Some users imported?
                elseif ( $results['errors'] ) {
	                wp_redirect( add_query_arg(
		                [
			                'import'   => 'errors',
			                'total'    => $results['total'],
			                'imported' => $results['imported']
		                ],
		                wp_get_referer() ) );
				} // All users imported? :D
				else {
					wp_redirect( add_query_arg( 'import', 'success', wp_get_referer() ) );
				}

				exit;
			}

			wp_redirect( add_query_arg( 'import', 'file', wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public static function get_import_users_page() {
		self::init();
		if ( ! current_user_can( 'create_users' ) ) {
			wp_die( __( 'Vous n\'avez pas les droits pour créer des utilisateurs !', 'amapress' ) );
		}

		ob_start();

		?>

        <div class="wrap">
        <h2><?php _e( 'Importer des utilisateurs depuis un fichier XLSX/XLS/ODS', 'amapress' ); ?></h2>
		<?php
		$error_log_file = self::$log_dir_path . self::$log_file_name;
		$error_log_url  = self::$log_dir_url . self::$log_file_name;

		if ( ! file_exists( $error_log_file ) ) {
			if ( ! @fopen( $error_log_file, 'x' ) ) {
				echo '<div class="updated"><p><strong>' . sprintf( __( 'Notice: please make the directory %s writable so that you can see the error log.', 'amapress' ), self::$log_dir_path ) . '</strong></p></div>';
			}
		}

		if ( isset( $_GET['import'] ) ) {
			$error_log_msg = '';
			if ( file_exists( $error_log_file ) ) {
				$cnt           = Encoding::file_get_contents_utf8( $error_log_file );
				$cnt           = preg_replace(
					'/\<(http[^\>]+)\>/',
					'<a href="$1" target="_blank">$1</a>',
					$cnt );
				$error_log_msg = '<pre class="import-errors">' . $cnt . '</pre>';
			}
			$imports = isset( $_REQUEST['imported'] ) ? $_REQUEST['imported'] : 0;
			$total   = isset( $_REQUEST['total'] ) ? $_REQUEST['total'] : 0;
			$remain  = $total - $imports;

			switch ( $_GET['import'] ) {
				case 'file':
					echo '<div class="error"><p><strong>' . __( 'Erreur pendant l\'upload du fichier.', 'amapress' ) . '</strong></p></div>';
					break;
				case 'data':
					echo '<div class="error"><p><strong>' . __( 'Ne peut pas extraire les données du fichier uploadé ou aucun fichier n\'a été uploadé.', 'amapress' ) . '</strong></p></div>';
					break;
				case 'fail':
					echo '<div class="error"><p><strong>' . sprintf( __( 'Aucun utilisateur n\'a pu être importé%s.', 'amapress' ), $error_log_msg ) . '</strong></p></div>';
					break;
				case 'errors':
					echo '<div class="error"><p><strong>' . sprintf( __( "$imports utilisateur(s) ont été importés et $remain autre(s) pas%s.", 'amapress' ), $error_log_msg ) . '</strong></p></div>';
					break;
				case 'success':
					echo '<div class="updated"><p><strong>' . __( 'Les utilisateurs ont été importés avec succès.', 'amapress' ) . '</strong></p></div>';
					break;
				default:
					break;
			}
		}
		?>
        <!--		<form method="post" action="" enctype="multipart/form-data">-->
		<?php wp_nonce_field( 'amapress-import-users-users-page_import', '_wpnonce-amapress-import-users-users-page_import' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="users_csv"><?php _e( 'Fichier XLSX/XLS/ODS', 'amapress' ); ?></label></th>
                <td>
                    <input type="file" accept=".xls,.xlsx,.ods" id="users_csv" name="users_csv" value=""
                           class="all-options"/><br/>
                    <span class="description"><?php echo sprintf( __( 'Vous pouvez télécharger un modèle à l\'aide des boutons Télécharger le modèle ci-dessus.', 'amapress' ) ); ?></span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e( 'Notification', 'amapress' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e( 'Notification', 'amapress' ); ?></span>
                        </legend>
                        <label for="new_user_notification">
                            <input id="new_user_notification" name="new_user_notification" type="checkbox" value="1"/>
							<?php _e( 'Envoyer aux nouveaux utilisateurs', 'amapress' ) ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e( 'Changement de mot de passe', 'amapress' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e( 'Changement de mot de passe', 'amapress' ); ?></span></legend>
                        <label for="password_nag">
                            <input id="password_nag" name="password_nag" type="checkbox" value="1"/>
							<?php _e( 'Afficher l\'interface de changement de mot de passe au premier login', 'amapress' ) ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e( 'Mise à jour des utilisateurs', 'amapress' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e( 'Mise à jour des utilisateurs', 'amapress' ); ?></span></legend>
                        <label for="users_update">
                            <input id="users_update" name="users_update" type="checkbox" value="1" checked="checked"/>
							<?php _e( 'Mettre à jour l\'utilisateur quand son nom ou son email existent', 'amapress' ); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button name="action" value="import"
                    class="button button-primary"><?php _e( 'Importer', 'amapress' ) ?></button>
        </p>
        <!--		</form>-->
		<?php

		$ret = ob_get_contents();
		ob_clean();

		return $ret;
	}

	/**
	 * Import a csv file
	 *
	 * @since 0.5
	 */
	private static function import_users_csv( $filename, $args ) {
		$errors = $user_ids = $headers_names = array();

		$defaults              = array(
			'password_nag'          => false,
			'new_user_notification' => false,
			'users_update'          => true
		);
		$args                  = wp_parse_args( $args, $defaults );
		$password_nag          = $args['password_nag'];
		$new_user_notification = $args['new_user_notification'];
		$users_update          = $args['users_update'];

		// User data fields list used to differentiate with user meta
		$userdata_fields = array(
			'ID',
			'user_login',
			'user_pass',
			'user_email',
			'user_url',
			'user_nicename',
			'display_name',
			'user_registered',
			'first_name',
			'last_name',
			'nickname',
			'description',
			'rich_editing',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'show_admin_bar_admin',
			'roles'
		);

		include( plugin_dir_path( __FILE__ ) . 'class-readcsv.php' );

		// Loop through the file lines
//		$file_handle = @fopen( $filename, 'r' );
//		if($file_handle) {
		try {
			$csv_reader = new ReadCSV( $filename ); // Skip any UTF-8 byte order mark.

			$first          = true;
			$rkey           = 0;
			$imported_users = 0;
			$total_users    = 0;
			while ( ( $line = $csv_reader->get_row() ) !== null ) {
				$rkey ++;
				// If the first line is empty, abort
				// If another line is empty, just skip it
				if ( empty( $line ) ) {
					if ( $first ) {
						break;
					} else {
						continue;
					}
				}

				$errors[ $rkey ] = array();

				// If we are on the first line, the columns are the headers
				if ( $first ) {
					$headers        = $line;
					$headers_mapped = array_map( function ( $field_name, $index ) {
						$field_name = Encoding::toUTF8( $field_name );
						if ( empty( trim( $field_name ) ) ) {
							return null;
						}

						$col_name = Amapress::num2alpha( $index );

						return apply_filters( "amapress_import_users_get_field_name", $field_name, $col_name );
					}, array_values( $headers ), array_keys( $headers ) );
//					$headers_col_names = array_map( function ( $index ) {
//						return Amapress::num2alpha( $index );
//					}, array_keys( $headers ) );
					$headers_names = array_combine(
						array_values( $headers ),
						$headers_mapped );

					$had_errors = false;
					foreach ( $headers_mapped as $k => $v ) {
						if ( is_wp_error( $v ) ) {
							$errors[ $rkey ][] = $v;
							$had_errors        = true;
						}
					}
					if ( $had_errors ) {
						break;
					}

					$required_headers = apply_filters( 'amapress_csv_users_import_required_headers', array() );
					foreach ( array_diff( $required_headers, $headers_mapped ) as $field_name ) {
						$had_errors  = true;
						$header_name = apply_filters( "amapress_users_get_field_display_name", $field_name );
						if ( is_wp_error( $header_name ) ) {
							$errors[ $rkey ][] = $header_name;
						} else {
							$errors[ $rkey ][] = new WP_Error( 'required_header_missing', "Il manque une colonne $header_name." );
						}
					}
					if ( $had_errors ) {
						break;
					}

					$first = false;
					continue;
				}

				$total_users ++;

				// Separate user data from meta
				$userdata = $usermeta = array();
				foreach ( $line as $ckey => $column ) {
					$col_name    = Amapress::num2alpha( $ckey );
					$column_name = $headers_names[ $headers[ $ckey ] ];
					$column      = trim( Encoding::toUTF8( $column ) );

					if ( empty( $column_name ) ) {
						continue;
					}

					if ( in_array( $column_name, $required_headers ) && empty( $column ) ) {
						$column = new WP_Error( 'required_value_missing', "Colonne $col_name : valeur requise pour la colonne {$headers[$ckey]}." );
					}

					if ( in_array( $column_name, $userdata_fields ) ) {
						$userdata[ $column_name ] = $column;
					} else {
						$usermeta[ $column_name ] = $column;
					}
				}

				// A plugin may need to filter the data and meta
				$userdata = apply_filters( 'amapress_import_user_data', $userdata, $usermeta );
				$usermeta = apply_filters( 'amapress_import_user_meta', $usermeta, $userdata );

				if ( is_wp_error( $userdata ) ) {
					$errors[ $rkey ][] = $userdata;
					continue;
				}

				if ( is_wp_error( $usermeta ) ) {
					$errors[ $rkey ][] = $usermeta;
					continue;
				}

				$had_errors = false;
				foreach ( $userdata as $v ) {
					if ( is_wp_error( $v ) ) {
						$errors[ $rkey ][] = $v;
						$had_errors        = true;
					}
				}
				foreach ( $usermeta as $v ) {
					if ( is_wp_error( $v ) ) {
						$errors[ $rkey ][] = $v;
						$had_errors        = true;
					}
				}


				if ( $had_errors ) {
					continue;
				}

				// If no user data, bailout!
				if ( empty( $userdata ) ) {
					continue;
				}

				// Something to be done before importing one user?
				do_action( 'amapress_pre_user_import', $userdata, $usermeta );

				$user = $user_id = false;

				if ( ! empty( $userdata['ID'] ) ) {
					$user = get_user_by( 'ID', $userdata['ID'] );
				}

				if ( ! $user && $users_update ) {
					if ( ! $user && ! empty( $userdata['user_email'] ) ) {
						$user = get_user_by( 'email', $userdata['user_email'] );
					}
					if ( ! empty( $userdata['user_login'] ) ) {
						$user = get_user_by( 'login', $userdata['user_login'] );
					}
					if ( ! $user && ! empty( $userdata['first_name'] ) && ! empty( $userdata['last_name'] ) && ! empty( $userdata['user_email'] ) ) {
						$login = strtolower( $userdata['first_name'] . '.' . $userdata['last_name'] );
						$user  = get_user_by( 'login', $login );
						if ( $user ) {
							$user_link         = Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $user->ID ), $login, true, true );
							$search_link       = Amapress::makeLink( admin_url( 'users.php?s=' . $userdata['last_name'] ), 'Rechercher ' . $userdata['last_name'], true, true );
							$errors[ $rkey ][] = new WP_Error( 'user_with_different_mail', "User with login '$user_link' already exists with email {$user->user_email}. $search_link" );
							continue;
						}
					}
				}

				$update = false;
				if ( $user ) {
					$userdata['ID'] = $user->ID;
					$update         = true;
				}

				// If creating a new user and no password was set, let auto-generate one!
				if ( ! $update && empty( $userdata['user_pass'] ) ) {
					$userdata['user_pass'] = wp_generate_password( 12, false );
				}

				if ( ! empty( $userdata['roles'] ) ) {
					$userdata['role'] = $userdata['roles'];
				}
				unset( $userdata['roles'] );

				if ( $update ) {
					unset( $userdata['role'] );
				}

				if ( empty( $userdata['user_login'] ) ) {
					if ( ! empty( $userdata['first_name'] ) and ! empty( $userdata['last_name'] ) ) {
						$userdata['user_login'] = sprintf( '%s.%s', strtolower( $userdata['first_name'] ), strtolower( $userdata['last_name'] ) );
//                    } else if (!empty($userdata['last_name'])) {
//                        $userdata['user_login'] = sprintf('%s.%s', strtolower($userdata['first_name']), strtolower($userdata['last_name']));
					} else if ( ! empty( $userdata['user_email'] ) ) {
						$userdata['user_login'] = $userdata['user_email'];
					}
				}

				if ( $update ) {
					$user_id = wp_update_user( $userdata );
				} else {
					$user_id = wp_insert_user( $userdata );
				}

				// Is there an error o_O?
				if ( is_wp_error( $user_id ) ) {
					$errors[ $rkey ][] = $user_id;
				} else {
					if ( is_multisite() ) {
						if ( ! is_user_member_of_blog( $user_id, get_current_blog_id() ) ) {
							add_user_to_blog( get_current_blog_id(), $user_id, ! empty( $userdata['role'] ) ? $userdata['role'] : 'amapien' );
						}

					}
					// If no error, let's update the user meta too!
					if ( $usermeta ) {
						foreach ( $usermeta as $metakey => $metavalue ) {
							$metavalue = maybe_unserialize( $metavalue );
							if ( empty( $metavalue ) ) {
								delete_user_meta( $user_id, $metakey );
							} else {
								update_user_meta( $user_id, $metakey, $metavalue );
							}
						}
					}

					// If we created a new user, maybe set password nag and send new user notification?
					if ( ! $update ) {
						if ( $password_nag ) {
							update_user_option( $user_id, 'default_password_nag', true, true );
						}

						if ( $new_user_notification ) {
							wp_new_user_notification( $user_id, null, 'user' );
						}
					}

					$imported_users ++;
					// Some plugins may need to do things after one user has been imported. Who know?
					do_action( 'amapress_post_user_import', $user_id );

					$user_ids[] = $user_id;
				}
			}
//			fclose( $file_handle );
//		} else {
		} catch ( Exception $e ) {
			$errors[] = new WP_Error( 'file_read', 'Unable to open CSV file.' );
		}

		// One more thing to do after all imports?
		do_action( 'amapress_post_users_import', $user_ids, $errors );

		foreach ( $errors as $k => $v ) {
			if ( count( $v ) == 0 ) {
				unset( $errors[ $k ] );
			}
		}

		// Let's log the errors
		self::log_errors( $errors );

		return array(
			'user_ids' => $user_ids,
			'errors'   => $errors,
			'total'    => $total_users,
			'imported' => $imported_users,
		);
	}

	/**
	 * Log errors to a file
	 *
	 * @param WP_Error[][] $errors
	 *
	 * @since 0.2
	 *
	 */
	private static function log_errors( $errors ) {
		if ( empty( $errors ) ) {
			return;
		}

		$log = @fopen( self::$log_dir_path . self::$log_file_name, 'w' );
		@fwrite( $log, sprintf( __( 'BEGIN %s', 'amapress' ), date( 'Y-m-d H:i:s', amapress_time() ) ) . "\n" );

		$logged = [];
		/** @var WP_Error[] $error * */
		foreach ( $errors as $key => $error ) {
			$line = $key;
			if ( ! is_array( $error ) ) {
				$error = array( $error );
			}

			foreach ( $error as $err ) {
				$message = $err->get_error_message();
				$m       = sprintf( __( '[Line %1$s] %2$s', 'amapress' ), $line, Encoding::toISO8859( $message ) );
				if ( in_array( $m, $logged ) ) {
					continue;
				}
				$logged[] = $m;
				@fwrite( $log, $m . "\n" );
			}
		}

		@fclose( $log );
	}
}