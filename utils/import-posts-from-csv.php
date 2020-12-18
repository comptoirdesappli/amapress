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
class Amapress_Import_Posts_CSV {
	private static $log_dir_path = '';
	private static $log_dir_url = '';
	private static $log_file_name = '';
	private static $initialized = false;

	/**
	 * Initialization
	 *
	 * @since 0.1
	 **/
	public static function init( $post_type ) {
		if ( self::$initialized ) {
			return;
		}

		$upload_dir          = wp_upload_dir();
		$user_name           = amapress_current_user_id();
		self::$log_file_name = "amapress_import_$post_type.{$user_name}.log";
		self::$log_dir_path  = trailingslashit( $upload_dir['basedir'] );
		self::$log_dir_url   = trailingslashit( $upload_dir['baseurl'] );

		self::$initialized = true;
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public static function process_posts_csv_import( $post_type ) {
		self::init( $post_type );
		if ( isset( $_POST["_wpnonce-amapress-import-posts-$post_type-page_import"] ) ) {
			check_admin_referer( "amapress-import-posts-$post_type-page_import", "_wpnonce-amapress-import-posts-$post_type-page_import" );

			$user_name           = amapress_current_user_id();
			self::$log_file_name = "amapress_import_$post_type.{$user_name}.log";

			if ( ! empty( $_FILES['posts_csv']['tmp_name'] ) ) {
				// Setup settings variables
				$filename     = $_FILES['posts_csv']['tmp_name'];
				$posts_update = isset( $_POST['posts_update'] ) ? $_POST['posts_update'] : true;

				$results = self::import_posts_csv( $filename, array(
					'posts_update' => $posts_update,
					'post_type'    => $post_type
				) );

				// No posts imported?
				if ( ! $results['post_ids'] ) {
					wp_redirect( add_query_arg(
						[
							'import'                                            => 'fail',
							'imported'                                          => $results['imported'],
							'total'                                             => $results['total'],
							'amapress_ignore_unknown_columns'                   =>
								isset( $_REQUEST['amapress_ignore_unknown_columns'] ) ?
									$_REQUEST['amapress_ignore_unknown_columns'] :
									0,
							'amapress_import_adhesion_default_date_debut'       =>
								isset( $_REQUEST['amapress_import_adhesion_default_date_debut'] ) ?
									$_REQUEST['amapress_import_adhesion_default_date_debut'] :
									amapress_time(),
							'amapress_import_adhesion_default_contrat_instance' =>
								isset( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] ) ?
									$_REQUEST['amapress_import_adhesion_default_contrat_instance'] :
									'',
							'amapress_import_adhesion_default_lieu'             =>
								isset( $_REQUEST['amapress_import_adhesion_default_lieu'] ) ?
									$_REQUEST['amapress_import_adhesion_default_lieu'] :
									'',
							'amapress_import_produit_default_producteur'        =>
								isset( $_REQUEST['amapress_import_produit_default_producteur'] ) ?
									$_REQUEST['amapress_import_produit_default_producteur'] :
									'',
						],
						wp_get_referer() ) );
				} // Some posts imported?
                elseif ( $results['errors'] ) {
					wp_redirect( add_query_arg(
						[
							'import'                                            => 'errors',
							'imported'                                          => $results['imported'],
							'total'                                             => $results['total'],
							'amapress_ignore_unknown_columns'                   =>
								isset( $_REQUEST['amapress_ignore_unknown_columns'] ) ?
									$_REQUEST['amapress_ignore_unknown_columns'] :
									0,
							'amapress_import_adhesion_default_date_debut'       =>
								isset( $_REQUEST['amapress_import_adhesion_default_date_debut'] ) ?
									$_REQUEST['amapress_import_adhesion_default_date_debut'] :
									amapress_time(),
							'amapress_import_adhesion_default_contrat_instance' =>
								isset( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] ) ?
									$_REQUEST['amapress_import_adhesion_default_contrat_instance'] :
									'',
							'amapress_import_adhesion_default_lieu'             =>
								isset( $_REQUEST['amapress_import_adhesion_default_lieu'] ) ?
									$_REQUEST['amapress_import_adhesion_default_lieu'] :
									'',
							'amapress_import_produit_default_producteur'        =>
								isset( $_REQUEST['amapress_import_produit_default_producteur'] ) ?
									$_REQUEST['amapress_import_produit_default_producteur'] :
									'',
						],
						wp_get_referer() ) );
				} // All posts imported? :D
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
	public static function get_import_posts_page( $post_type ) {
		self::init( $post_type );

		if ( ! current_user_can( 'edit_' . $post_type ) ) {
			wp_die( __( sprintf( __( "Vous n'avait pas les droits de créer des %s", 'amapress' ), $post_type ), 'amapress' ) );
		}

		ob_start();
		?>

        <div class="wrap">
        <h2><?php _e( sprintf( __( 'Import de %s depuis un fichier XLSX/XLS/ODS', 'amapress' ), $post_type ), 'amapress' ); ?></h2>
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
					echo '<div class="error"><p><strong>' . sprintf( __( 'Aucun %s n\'a pu être importé%s.', 'amapress' ), $post_type, $error_log_msg ) . '</strong></p></div>';
					break;
				case 'errors':
					echo '<div class="error"><p><strong>' . sprintf( __( '%d %s(s) ont été importés et %d autres pas%s.', 'amapress' ), $imports, $post_type, $remain, $error_log_msg ) . '</strong></p></div>';
					break;
				case 'success':
					echo '<div class="updated"><p><strong>' . sprintf( __( 'Les %s ont été importés avec succès.', 'amapress' ), $post_type ) . '</strong></p></div>';
					break;
				default:
					break;
			}
		}
		?>
        <!--	<form method="post" action="" enctype="multipart/form-data">-->
		<?php wp_nonce_field( "amapress-import-posts-$post_type-page_import", "_wpnonce-amapress-import-posts-$post_type-page_import" ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="posts_csv"><?php _e( 'Fichier XLSX/XLS/ODS', 'amapress' ); ?></label></th>
                <td>
                    <input type="file" accept=".xls,.xlsx,.ods" id="posts_csv" name="posts_csv" value=""
                           class="all-options"/><br/>
                    <span class="description"><?php echo sprintf( __( 'Vous pouvez télécharger un modèle à l\'aide des boutons Télécharger le modèle ci-dessus.', 'amapress' ) ); ?></span>
                </td>
            </tr>
            <!--            <tr valign="top">-->
            <!--                <th scope="row">-->
	        <?php //_e( "Mise à jour des $post_type", 'amapress' ); ?><!--</th>-->
            <!--                <td>-->
            <!--                    <fieldset>-->
            <!--                        <legend class="screen-reader-text">-->
            <!--                            <span>-->
	        <?php //_e( "Mise à jour des $post_type", 'amapress' ); ?><!--</span></legend>-->
            <!--                        <label for="posts_update">-->
            <!--                            <input id="posts_update" name="posts_update" type="checkbox" value="1" checked="checked"/>-->
            <!--							--><?php //_e( "Mettre à jour le $post_type si son titre ou slug existe", 'amapress' ); ?>
            <!--                        </label>-->
            <!--                    </fieldset>-->
            <!--                </td>-->
            <!--            </tr>-->
        </table>
        <p class="submit">
            <button name="action" value="import"
                    class="button button-primary"><?php _e( 'Importer', 'amapress' ) ?></button>
        </p>
        <!--	</form>-->
		<?php
		$ret = ob_get_contents();
		ob_clean();

		return $ret;
	}

	public static function generateModel(
		$post_type, $name,
		$data_keys = array( 'post_title' ),
		$multi_fields = array(),
		$post_id = 0,
		$exclude_meta_keys = []
	) {
		$pt          = amapress_simplify_post_type( $post_type );
		$post_fields = AmapressEntities::getPostTypeFields( $post_type );
		$sitename    = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty( $sitename ) ) {
			$sitename .= '.';
		}
		$post_name = '';
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$post_name = sanitize_key( AmapressUsers::unaccent( $post->post_title ) ) . '.';
			}
		}
		$filename = $post_name . $sitename . $name . '.import_model';

		$multi     = array();
		$meta_keys = array();
		foreach ( $post_fields as $k => $v ) {
			if ( ( ( ! isset( $v['csv'] ) || $v['csv'] !== false ) && ( ! isset( $v['csv_import'] ) || $v['csv_import'] !== false ) )
			     && ( ! in_array( $v['type'], TitanFrameworkOption::$csvImportExcludedTypes )
			          || ( isset( $v['csv'] ) && $v['csv'] === true )
			          || ( isset( $v['csv_import'] ) && $v['csv_import'] === true ) ) ) {
				$meta_keys[ $k ] = $k;
			}
		}
		foreach ( $exclude_meta_keys as $k ) {
			unset( $meta_keys[ $k ] );
		}
		foreach ( $multi_fields as $multi_key => $multi_value ) {
			$f = is_int( $multi_key ) ? $multi_value : $multi_key;
			unset( $meta_keys[ $f ] );
			unset( $meta_keys[ $multi_value ] );
			$option = AmapressEntities::getTfOption( $post_type, $f );
			if ( ! $option ) {
				continue;
			}
			if ( isset( $option->settings['custom_multi'] ) && is_callable( $option->settings['custom_multi'], false ) ) {
				$multi_options = call_user_func( $option->settings['custom_multi'], $option, $post_id );
			} else if ( is_a( $option, 'TitanFrameworkOptionSelect' ) ) {
				/**
				 * @var TitanFrameworkOptionSelect $option
				 */
				$multi_options = $option->fetchOptionsWithCache();
			} else {
				$multi_options = [];
			}
			foreach ( $multi_options as $k => $value ) {
				if ( empty( $k ) ) {
					continue;
				}
				$meta_keys[ '_' . $k ] = $value;
				$multi[ '_' . $k ]     = $multi_value;
			}
		}
		$taxonomies_keys   = array();
		$taxonomies_names  = array();
		$taxonomies_values = array();
		foreach ( get_object_taxonomies( amapress_unsimplify_post_type( $post_type ), 'objects' ) as $tax_name => $tax ) {
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
		$fields = apply_filters( "amapress_posts_model_fields", $fields );
		$fields = apply_filters( "amapress_posts_{$pt}_model_fields", $fields );

		$options      = array();
		$headers      = array();
		$headers_desc = array();
		foreach ( $fields as $key => $field ) {
			if ( in_array( $field, $taxonomies_names ) ) {
				$headers[ $key ]      = $taxonomies_names[ $field ];
				$headers_desc[ $key ] = __( 'Saisir une ou plusieurs des étiquettes suivantes séparées par des virgules', 'amapress' );
				$options[ $key ]      = $taxonomies_values[ $field ];
			} else {
				$arg  = [
					'key'       => $key,
					'field'     => $field,
					'multi'     => - 1,
					'post_id'   => $post_id,
					'post_type' => $pt,
				];
				$desc = '';
				if ( strpos( $key, '_' ) === 0 && isset( $multi[ $key ] ) ) {
					$header       = $field;
					$field        = $multi[ $key ];
					$arg['multi'] = intval( trim( $key, '_' ) );
					$arg['field'] = $field;
					$key          = $field . $key;
				} else {
					$header = apply_filters( "amapress_posts_get_field_display_name", $field, $pt );
					$header = apply_filters( "amapress_posts_{$pt}_get_field_display_name", $header );
					$desc   = apply_filters( "amapress_posts_get_field_desc", $desc, $field, $pt );
					$desc   = apply_filters( "amapress_posts_{$pt}_get_field_desc", $desc );
				}
				$arg['header'] = $header;
				if ( empty( $header ) ) {
					unset( $fields[ $key ] );
					continue;
				}
				$headers[ $key ] = $header;
				$option          = AmapressEntities::getTfOption( $post_type, $field );
				if ( $option ) {
					$options[ $key ] = $option->getSamplesForCSV( $arg );
					if ( empty( $desc ) ) {
						$desc = $option->getDesc();
					}
				} else {
					$options[ $key ] = array();
				}
				$headers_desc[ $key ] = wp_strip_all_tags( $desc );
			}
		}

		$required_headers = apply_filters( 'amapress_csv_posts_import_required_headers', $data_keys, $post_type, $headers );
		$required_headers = apply_filters( "amapress_csv_posts_{$post_type}_import_required_headers", $required_headers, $headers );

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
	 * Import a csv file
	 *
	 * @since 0.5
	 */
	private static function import_posts_csv( $filename, $args ) {
		$errors = $post_ids = $headers_names = array();

		$defaults     = array(
			'posts_update' => false,
			'post_type'    => null,
		);
		$args         = wp_parse_args( $args, $defaults );
		$posts_update = true; //$args['posts_update'];
		$post_type    = $args['post_type'];

		// User data fields list used to differentiate with user meta
		$postdata_fields           = array(
			'ID',
			'post_author',
			'post_name',
			'post_type',
			'post_title',
			'post_date',
			'post_date_gmt',
			'post_content',
			'post_excerpt',
			'post_status',
			'comment_status',
			'ping_status',
			'post_password',
			'post_parent',
			'post_modified',
			'post_modified_gmt',
			'comment_count',
			'menu_order'
		);
		$postdata_taxonomies       = array();
		$postdata_taxonomies_names = array();
		foreach ( get_object_taxonomies( amapress_unsimplify_post_type( $post_type ), 'objects' ) as $tax_name => $tax ) {
			$postdata_taxonomies[]                  = $tax_name;
			$postdata_taxonomies_names[ $tax_name ] = $tax->label;
		}
		$postcustom_fields = apply_filters( "amapress_import_posts_{$post_type}_custom_fields", array(), $post_type );
//		$postmulti_fields = apply_filters("amapress_import_posts_{$post_type}_is_multi_field", array(), $post_type);

		include( plugin_dir_path( __FILE__ ) . 'class-readcsv.php' );

		$has_multi      = false;
		$imported_posts = 0;
		$total_posts    = 0;
		// Loop through the file lines
//		$file_handle = @fopen( $filename, 'r' );
//		if($file_handle) {
		try {
			$csv_reader = new ReadCSV( $filename ); // Skip any UTF-8 byte order mark.

			$first = true;
			$rkey  = 0;
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
					$headers_mapped = array_map( function ( $field_name, $index ) use ( $post_type ) {
						$field_name = Encoding::toUTF8( $field_name );
						if ( empty( trim( $field_name ) ) ) {
							return null;
						}

						$col_name = Amapress::num2alpha( $index );

						$field_name = apply_filters( "amapress_import_posts_get_field_name", $field_name, $post_type, $col_name );

						return apply_filters( "amapress_import_{$post_type}_get_field_name", $field_name, $post_type, $col_name );
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

					$required_headers = apply_filters( 'amapress_csv_posts_import_required_headers', array(), $post_type, $headers_mapped );
					$required_headers = apply_filters( "amapress_csv_posts_{$post_type}_import_required_headers", $required_headers, $headers_mapped );
					foreach ( array_diff( $required_headers, $headers_mapped ) as $field_name ) {
						$had_errors  = true;
						$header_name = apply_filters( "amapress_posts_get_field_display_name", $field_name, $post_type );
						if ( is_wp_error( $header_name ) ) {
							$errors[ $rkey ][] = $header_name;
						} else {
							$errors[ $rkey ][] = new WP_Error( 'required_header_missing', sprintf( __( 'Il manque une colonne %s.', 'amapress' ), $header_name ) );
						}
					}

					if ( $had_errors ) {
						break;
					}

					$first = false;
					continue;
				}


				// Separate user data from meta
				$postdata = $postmeta = $posttaxo = $postcustom = $postmulti = array();
				foreach ( $line as $ckey => $column ) {
					$col_name    = Amapress::num2alpha( $ckey );
					$column_name = $headers_names[ $headers[ $ckey ] ];
					$column      = trim( Encoding::toUTF8( $column ) );

					if ( empty( $column_name ) ) {
						continue;
					}

					if ( in_array( $column_name, $required_headers ) && empty( $column ) ) {
						$column = new WP_Error( 'required_value_missing', sprintf( __( 'Colonne %s : valeur requise pour la colonne %s.', 'amapress' ), $col_name, $headers[ $ckey ] ) );
					}

					if ( in_array( $column_name, $postdata_fields ) ) {
						$postdata[ $column_name ] = $column;
					} else if ( in_array( $column_name, $postdata_taxonomies ) ) {
						$posttaxo[ $column_name ] = $column;
					} else if ( in_array( $column_name, $postcustom_fields ) ) {
						$postcustom[ $column_name ] = $column;
					} else if ( apply_filters( "amapress_import_posts_{$post_type}_is_multi_field", false, $column_name ) === true ) {
						$postmulti[ $column_name ] = $column;
						$has_multi                 = true;
					} else {
						$postmeta[ $column_name ] = $column;
					}
				}

				$postdata = apply_filters( "amapress_import_{$post_type}_apply_default_values_to_posts_data", $postdata, $postmeta, $posttaxo, $post_type, $postmulti );
				$postmeta = apply_filters( "amapress_import_{$post_type}_apply_default_values_to_posts_meta", $postmeta, $postdata, $posttaxo, $post_type, $postmulti );
				$posttaxo = apply_filters( "amapress_import_{$post_type}_apply_default_values_to_posts_taxonomy", $posttaxo, $postdata, $postmeta, $post_type, $postmulti );

				$postdata = apply_filters( "amapress_import_apply_default_values_to_posts_data", $postdata, $postmeta, $posttaxo, $post_type, $postmulti );
				$postmeta = apply_filters( "amapress_import_apply_default_values_to_posts_meta", $postmeta, $postdata, $posttaxo, $post_type, $postmulti );
				$posttaxo = apply_filters( "amapress_import_apply_default_values_to_posts_taxonomy", $posttaxo, $postdata, $postmeta, $post_type, $postmulti );

				// A plugin may need to filter the data and meta
				$postdata  = apply_filters( "amapress_import_{$post_type}_data", $postdata, $postmeta, $posttaxo, $postmulti );
				$postmeta  = apply_filters( "amapress_import_{$post_type}_meta", $postmeta, $postdata, $posttaxo, $postmulti );
				$posttaxo  = apply_filters( "amapress_import_{$post_type}_taxonomy", $posttaxo, $postdata, $postmeta, $postmulti );
				$postmulti = apply_filters( "amapress_import_{$post_type}_multi", $postmulti, $postdata, $postmeta, $posttaxo );

				$postdata  = apply_filters( "amapress_import_posts_data", $postdata, $postmeta, $posttaxo, $post_type, $postmulti );
				$postmeta  = apply_filters( "amapress_import_posts_meta", $postmeta, $postdata, $posttaxo, $post_type, $postmulti );
				$posttaxo  = apply_filters( "amapress_import_posts_taxonomy", $posttaxo, $postdata, $postmeta, $post_type, $postmulti );
				$postmulti = apply_filters( "amapress_import_posts_multi", $postmulti, $postdata, $postmeta, $post_type, $posttaxo );


				$had_errors = false;
				if ( is_wp_error( $postdata ) ) {
					$errors[ $rkey ][] = $postdata;
					$had_errors        = true;
				}

				if ( is_wp_error( $postmeta ) ) {
					$errors[ $rkey ][] = $postmeta;
					$had_errors        = true;
				}

				if ( is_wp_error( $posttaxo ) ) {
					$errors[ $rkey ][] = $posttaxo;
					$had_errors        = true;
				}

				foreach ( $postdata as $v ) {
					if ( is_wp_error( $v ) ) {
						$errors[ $rkey ][] = $v;
						$had_errors        = true;
					}
				}
				foreach ( $postmeta as $v ) {
					if ( is_wp_error( $v ) ) {
						$errors[ $rkey ][] = $v;
						$had_errors        = true;
					}
				}
				foreach ( $posttaxo as $v ) {
					if ( is_wp_error( $v ) ) {
						$errors[ $rkey ][] = $v;
						$had_errors        = true;
					}
				}

				foreach ( $postmulti as $v ) {
					if ( is_wp_error( $v ) ) {
						$errors[ $rkey ][] = $v;
						$had_errors        = true;
					}
				}

//                var_dump($errors);
//                die();

				if ( $had_errors && empty( $postmulti ) ) {
					continue;
				}

				// If no user data, bailout!
				if ( empty( $postdata ) && empty( $postmeta ) && empty( $postmulti ) ) {
					continue;
				}

				$postdata['post_type']   = amapress_unsimplify_post_type( $post_type );
				$postdata['post_status'] = 'publish';

				// Something to be done before importing one user?
				do_action( "amapress_pre_import", $postdata, $postmeta, $posttaxo, $postcustom );
				do_action( "amapress_pre_{$post_type}_import", $postdata, $postmeta, $posttaxo, $postcustom );

//                if (empty($postmulti))
//                    $postmulti = array('single' => 'single');

				foreach ( ( empty( $postmulti ) ? array( 'single' => 'single' ) : $postmulti ) as $multi_key => $multi_value ) {
					if ( empty( $multi_value )
					     || empty( $multi_key )
					     || is_wp_error( $multi_key )
					     || is_wp_error( $multi_value ) ) {
						continue;
					}
					$post = $post_id = false;
					$total_posts ++;

					if ( $multi_key != 'single' ) {
						unset( $postdata['ID'] );

						$postdata = apply_filters( "amapress_import_{$post_type}_apply_multi_to_posts_data", $postdata, $multi_key, $multi_value, $postmeta, $posttaxo, $post_type, $postmulti );
						$postmeta = apply_filters( "amapress_import_{$post_type}_apply_multi_to_posts_meta", $postmeta, $multi_key, $multi_value, $postdata, $posttaxo, $post_type, $postmulti );
						$posttaxo = apply_filters( "amapress_import_{$post_type}_apply_multi_to_posts_taxonomy", $posttaxo, $multi_key, $multi_value, $postdata, $postmeta, $post_type, $postmulti );

						$postdata = apply_filters( "amapress_import_apply_multi_to_posts_data", $postdata, $multi_key, $multi_value, $postmeta, $posttaxo, $post_type, $postmulti );
						$postmeta = apply_filters( "amapress_import_apply_multi_to_posts_meta", $postmeta, $multi_key, $multi_value, $postdata, $posttaxo, $post_type, $postmulti );
						$posttaxo = apply_filters( "amapress_import_apply_multi_to_posts_taxonomy", $posttaxo, $multi_key, $multi_value, $postdata, $postmeta, $post_type, $postmulti );

						if ( is_wp_error( $postdata ) ) {
							$errors[ $rkey ][] = $postdata;
						}

						if ( is_wp_error( $postmeta ) ) {
							$errors[ $rkey ][] = $postmeta;
						}

						if ( is_wp_error( $posttaxo ) ) {
							$errors[ $rkey ][] = $posttaxo;
						}

						foreach ( $postdata as $v ) {
							if ( is_wp_error( $v ) ) {
								$errors[ $rkey ][] = $v;
							}
						}
						foreach ( $postmeta as $v ) {
							if ( is_wp_error( $v ) ) {
								$errors[ $rkey ][] = $v;
							}
						}
						foreach ( $posttaxo as $v ) {
							if ( is_wp_error( $v ) ) {
								$errors[ $rkey ][] = $v;
							}
						}

						foreach ( $postmulti as $v ) {
							if ( is_wp_error( $v ) ) {
								$errors[ $rkey ][] = $v;
							}
						}

						if ( ! empty( $errors[ $rkey ] ) ) {
							continue;
						}
					} else {
						if ( isset( $postdata['ID'] ) ) {
							$post = get_post( $postdata['ID'] );
						}
						$has_meta_import_keys = false;
						foreach ( AmapressEntities::getPostTypeFields( amapress_simplify_post_type( $post_type ) ) as $field_k => $field ) {
							if ( isset( $field['import_key'] ) && $field['import_key'] ) {
								$has_meta_import_keys = true;
							}
						}
						if ( ! $post && $posts_update && ! $has_meta_import_keys ) {
							if ( isset( $postdata['post_name'] ) ) {
								$post = get_page_by_path( $postdata['post_name'], OBJECT, amapress_unsimplify_post_type( $post_type ) );
							}

							if ( ! $post && isset( $postdata['post_title'] ) ) {
								$post = get_page_by_title( $postdata['post_title'], OBJECT, amapress_unsimplify_post_type( $post_type ) );
								if ( ! $post ) {
									$post = get_page_by_title( preg_replace( '/\x{2019}/u', '\'', $postdata['post_title'] ), OBJECT, amapress_unsimplify_post_type( $post_type ) );
								}
								if ( ! $post ) {
									$post = get_page_by_title( wptexturize( $postdata['post_title'] ), OBJECT, amapress_unsimplify_post_type( $post_type ) );
								}
							}
						}
					}

					if ( ! $post && $posts_update ) {
						$post = apply_filters( "amapress_import_resolve_{$post_type}_post", null, $postdata, $postmeta, $posttaxo, $postcustom );
						if ( is_wp_error( $post ) ) {
							$errors[ $rkey ][] = $post;
							continue;
						}
						if ( ! $post ) {
							$post = apply_filters( "amapress_import_resolve_post", null, $post_type, $postdata, $postmeta, $posttaxo, $postcustom );
							if ( is_wp_error( $post ) ) {
								$errors[ $rkey ][] = $post;
								continue;
							}
						}
					}

					$update = false;
					if ( $post ) {
						$postdata['ID'] = $post->ID;
						$update         = true;
//					} else {
//						$postdata = apply_filters( "amapress_import_{$post_type}_apply_default_values_to_posts_data", $postdata, $multi_key, $multi_value, $postmeta, $posttaxo, $post_type, $postmulti );
//						$postmeta = apply_filters( "amapress_import_{$post_type}_apply_default_values_to_posts_meta", $postmeta, $multi_key, $multi_value, $postdata, $posttaxo, $post_type, $postmulti );
//						$posttaxo = apply_filters( "amapress_import_{$post_type}_apply_default_values_to_posts_taxonomy", $posttaxo, $multi_key, $multi_value, $postdata, $postmeta, $post_type, $postmulti );
//
//						$postdata = apply_filters( "amapress_import_apply_default_values_to_posts_data", $postdata, $multi_key, $multi_value, $postmeta, $posttaxo, $post_type, $postmulti );
//						$postmeta = apply_filters( "amapress_import_apply_default_values_to_posts_meta", $postmeta, $multi_key, $multi_value, $postdata, $posttaxo, $post_type, $postmulti );
//						$posttaxo = apply_filters( "amapress_import_apply_default_values_to_posts_taxonomy", $posttaxo, $multi_key, $multi_value, $postdata, $postmeta, $post_type, $postmulti );
					}

					$postdata = apply_filters( "amapress_import_{$post_type}_check_resolved_post_data_before_update", $postdata, $multi_key, $multi_value, $postmeta, $posttaxo, $post_type, $postmulti );
					if ( is_wp_error( $postdata ) ) {
						$errors[ $rkey ][] = $postdata;
						continue;
					}
					if ( empty( $postdata['ID'] ) ) {
						$update = false;
					}

					if ( $update ) {
						$post_id = wp_update_post( $postdata, true );
					} else {
						$post_id = wp_insert_post( $postdata, true );
					}

					// Is there an error o_O?
					if ( is_wp_error( $post_id ) ) {
						$errors[ $rkey ][] = $post_id;
					} else {
						// If no error, let's update the user meta too!
						if ( $postmeta ) {
							foreach ( $postmeta as $metakey => $metavalue ) {
								$metavalue = maybe_unserialize( $metavalue );
								if ( empty( $metavalue ) ) {
									delete_post_meta( $post_id, $metakey );
								} else {
									update_post_meta( $post_id, $metakey, $metavalue );
								}
							}
						}

						if ( $posttaxo ) {
							foreach ( $posttaxo as $taxokey => $taxovalue ) {
								$taxovalue = maybe_unserialize( $taxovalue );
								$tax_ids   = array();
								foreach ( explode( ',', $taxovalue ) as $tax_value ) {
									$tax_value = trim( $tax_value );
									$tax_id    = Amapress::resolve_tax_id( $tax_value, $taxokey );
									if ( ! $tax_id ) {
										$tax_id = wp_insert_term( $tax_value, $taxokey );
									}
									$tax_ids[] = $tax_id;
								}
								wp_set_post_terms( $post_id, $tax_ids, $taxokey );
							}
						}

						// Some plugins may need to do things after one user has been imported. Who know?
						do_action( "amapress_post_{$post_type}_import", $post_id, $postdata, $postmeta, $posttaxo, $postcustom );
						do_action( "amapress_post_import", $post_id, $post_type, $postdata, $postmeta, $posttaxo, $postcustom );

						$imported_posts ++;
						$post_ids[] = $post_id;
					}
				}
			}
//			fclose( $file_handle );
//		} else {
//			$errors[] = new WP_Error('file_read', __('Unable to open CSV file.', 'amapress'));
//		}
		} catch ( Exception $e ) {
			$errors[] = new WP_Error( 'file_read', $e->getMessage() );
		}


		// One more thing to do after all imports?
		do_action( "amapress_posts_import", $post_ids, $errors );
		do_action( "amapress_{$post_type}_posts_import", $post_ids, $errors );

		global $amapress_csv_import_errors;
		if ( ! empty( $amapress_csv_import_errors ) ) {
			foreach ( $amapress_csv_import_errors as $k => $v ) {
				if ( ! empty( $errors[ $k ] ) ) {
					$errors[ $k ] = array_merge( $errors[ $k ], $v );
				} else {
					$errors[ $k ] = $v;
				}
			}
		}

		foreach ( $errors as $k => $v ) {
			if ( is_array( $v ) && count( $v ) == 0 ) {
				unset( $errors[ $k ] );
			}
		}

//                var_dump($errors);
//        die();

		// Let's log the errors
		self::log_errors( $errors );

		return array(
			'post_ids' => $post_ids,
			'errors'   => $errors,
			'total'    => $total_posts,
			'imported' => $imported_posts,
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

//        var_dump(self::$log_dir_path . self::$log_file_name);
//        die();

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
