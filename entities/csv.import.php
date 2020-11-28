<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_wp_users_labels() {
	return array(
		'ID'              => 'ID',
		'user_login'      => __( 'Identifiant', 'amapress' ),
		'user_pass'       => __( 'Mot de passe', 'amapress' ),
		'user_email'      => __( 'Email', 'amapress' ),
		'user_url'        => 'URL',
		'user_nicename'   => __( 'Slug', 'amapress' ),
		'display_name'    => __( 'Nom d\'affichage', 'amapress' ),
		'user_registered' => __( 'Date de création', 'amapress' ),
		'first_name'      => __( 'Prénom', 'amapress' ),
		'last_name'       => __( 'Nom', 'amapress' ),
		'nickname'        => __( 'Surnom', 'amapress' ),
		'description'     => __( 'Description', 'amapress' ),
//        'rich_editing' => '',
//        'comment_shortcuts' => '',
//        'admin_color' => '',
//        'use_ssl' => 'SSL?',
//        'show_admin_bar_front' => '',
//        'show_admin_bar_admin' => __('Bar admin?', 'amapress'),
		'roles'           => __( 'Rôle sur le site', 'amapress' ),
		'email2'          => __( 'Email 2', 'amapress' ),
		'email3'          => __( 'Email 3', 'amapress' ),
		'email4'          => __( 'Email 4', 'amapress' ),
	);
}

function amapress_get_wp_posts_labels( $post_type ) {
	$post_type = amapress_unsimplify_post_type( $post_type );
	$ret       = array(
		'ID'            => 'ID',
		'post_author'   => __( 'Auteur de l\'article', 'amapress' ),
		'post_name'     => __( 'Slug', 'amapress' ),
		'post_type'     => __( 'Type d\'article', 'amapress' ),
		'post_title'    => __( 'Titre', 'amapress' ),
		'post_date'     => __( 'Date de publication', 'amapress' ),
		'post_content'  => __( 'Contenu', 'amapress' ),
		'post_excerpt'  => __( 'Résumé de l\'article', 'amapress' ),
		'post_status'   => __( 'Statut de l\'article', 'amapress' ),
		'post_modified' => __( 'Date de dernière modification', 'amapress' ),
	);
	if ( ! post_type_supports( $post_type, 'title' ) ) {
		unset( $ret['post_title'] );
	}
	if ( ! post_type_supports( $post_type, 'excerpt' ) ) {
		unset( $ret['post_excerpt'] );
	}
	if ( ! post_type_supports( $post_type, 'editor' ) ) {
		unset( $ret['post_content'] );
	}
	if ( ! post_type_supports( $post_type, 'author' ) ) {
		unset( $ret['post_author'] );
	}

	return $ret;
}

//add_filter('amapress_import_adhesion_intermittence_meta', 'amapress_import_adhesion_intermittence_meta');
//function amapress_import_adhesion_intermittence_meta($postmeta) {
//    if (isset($postmeta['amapress_adhesion_intermittence_user'])) {
//        amapress_create_user_if_not_exists($postmeta['amapress_adhesion_intermittence_user']);
//    }
//    return $postmeta;
//}

add_filter( 'amapress_import_users_get_field_name', 'amapress_import_users_get_field_name', 10, 2 );
function amapress_import_users_get_field_name( $field_name, $colname ) {
	$kvs = amapress_get_wp_users_labels();
	$kvs = array_combine( array_values( $kvs ), array_keys( $kvs ) );
	if ( isset( $kvs[ $field_name ] ) ) {
		return $kvs[ $field_name ];
	}

	$labels = AmapressEntities::getPostFieldsLabels( 'user' );
	$labels = array_combine( array_values( $labels ), array_keys( $labels ) );
	if ( isset( $labels[ $field_name ] ) ) {
		return $labels[ $field_name ];
	}

	if ( ! empty( $_REQUEST['amapress_ignore_unknown_columns'] ) ) {
		global $amapress_csv_import_errors;
		if ( empty( $amapress_csv_import_errors ) ) {
			$amapress_csv_import_errors = [];
		}
		$amapress_csv_import_errors[1] = new WP_Error( 'unknown_header', sprintf( __( 'Colonne %s : un utilisateur ne contient pas de champs %s', 'amapress' ), $colname, $field_name ) );

		return null;
	}

	return new WP_Error( 'unknown_header', sprintf( __( 'Colonne %s : un utilisateur ne contient pas de champs %s', 'amapress' ), $colname, $field_name ) );
}

add_filter( 'amapress_import_user_data', 'amapress_import_user_data', 10, 2 );
function amapress_import_user_data( $userdata, $usermeta ) {
	$excluded_fields = array(
		'user_pass',
		'user_registered',
		'rich_editing',
		'comment_shortcuts',
		'admin_color',
		'use_ssl',
		'show_admin_bar_front',
		'show_admin_bar_admin'
	);
	foreach ( $excluded_fields as $excluded_field ) {
		unset( $userdata[ $excluded_field ] );
	}

	$validators = AmapressEntities::getPostFieldsValidators();
	foreach ( $userdata as $k => $v ) {
		if ( ! empty( $validators[ $k ] ) ) {
			$res = call_user_func( $validators[ $k ], $v );
//            if (is_wp_error($res)) return $res;
			$userdata[ $k ] = $res;
		} else if ( 'role' == $k || 'roles' == $k ) {
			global $wp_roles;
			$found  = false;
			$v_norm = wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $v ) ) );
			foreach ( $wp_roles->roles as $name => $role ) {
				if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $name ) ) ), $v_norm ) === 0 ) {
					$v     = $name;
					$found = true;
				} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $role['name'] ) ) ), $v_norm ) === 0 ) {
					$v     = $name;
					$found = true;
				}
			}
			if ( $found ) {
				$userdata[ $k ] = $v;
			} else {
				$userdata[ $k ] = new WP_Error( 'unknown_user_role', sprintf( __( 'Le rôle utilisateur \'%d\' n\'existe pas', 'amapress' ), $v ) );
			}
		}
	}

	return $userdata;
}

add_filter( 'amapress_import_user_meta', 'amapress_import_user_meta', 10, 2 );
function amapress_import_user_meta( $usermeta, $userdata ) {
	$excluded_fields = array();
	foreach ( $excluded_fields as $excluded_field ) {
		unset( $usermeta[ $excluded_field ] );
	}

	$validators = AmapressEntities::getPostFieldsValidators();
	foreach ( $usermeta as $k => $v ) {
		if ( ! empty( $validators[ $k ] ) ) {

			$res = call_user_func( $validators[ $k ], $v );
//            if (is_wp_error($res)) return $res;
			$usermeta[ $k ] = $res;
		}
	}

	return $usermeta;
}

add_action( 'amapress_post_user_import', 'amapress_post_user_import' );
function amapress_post_user_import( $user_id ) {
	$user = AmapressUser::getBy( $user_id );
	$user->resolveAddress();
}

add_filter( 'amapress_import_posts_adhesion_is_multi_field', 'amapress_import_posts_adhesion_is_multi_field', 10, 2 );
function amapress_import_posts_adhesion_is_multi_field( $is_multi, $column_name ) {
	return is_int( $column_name );
}

//add_filter('amapress_import_adhesion_apply_multi_to_posts_data','amapress_import_adhesion_apply_multi_to_posts_data', 10, 3);
//function amapress_import_adhesion_apply_multi_to_posts_data($postdata, $multi_key, $multi_value) {
//    return $postdata;
//}
add_filter( 'amapress_import_adhesion_apply_multi_to_posts_meta', 'amapress_import_adhesion_apply_multi_to_posts_meta', 10, 4 );
function amapress_import_adhesion_apply_multi_to_posts_meta( $postmeta, $multi_key, $multi_value, $postdata ) {
	$postmeta['amapress_adhesion_contrat_instance'] = $multi_key;

	$postmeta['amapress_adhesion_contrat_quantite']         = array_map(
		function ( $id ) {
			return $id['id'];
		}, $multi_value );
	$postmeta['amapress_adhesion_contrat_quantite_factors'] = array_combine(
		array_map(
			function ( $id ) {
				return $id['id'];
			}, $multi_value ),
		array_map(
			function ( $id ) {
				return $id['quant'];
			}, $multi_value )
	);
	$contrat_instance                                       = AmapressContrat_instance::getBy( $multi_key );

	$date_debut_string = isset( $postmeta['amapress_adhesion_date_debut'] ) ? $postmeta['amapress_adhesion_date_debut'] : 0;
	if ( ! is_wp_error( $date_debut_string ) && ! empty( $date_debut_string ) ) {
		$date_debut = Amapress::start_of_day( $date_debut_string );
		if ( $date_debut < Amapress::start_of_day( $contrat_instance->getDate_debut() )
		     || $date_debut > Amapress::start_of_day( $contrat_instance->getDate_fin() ) ) {
			$dt            = date_i18n( 'd/m/Y', $date_debut );
			$contrat_debut = date_i18n( 'd/m/Y', $contrat_instance->getDate_debut() );
			$contrat_fin   = date_i18n( 'd/m/Y', $contrat_instance->getDate_fin() );

			return new WP_Error( 'invalid_date', sprintf( __( 'La date de début %s est en dehors des dates (%s - %s) du contrat \'%s\'', 'amapress' ), $dt, $contrat_debut, $contrat_fin, $contrat_instance->getTitle() ) );
		}
	}
//	if ( $postmeta['amapress_adhesion_date_debut'] < $contrat_instance->getDate_debut()
//	     || $postmeta['amapress_adhesion_date_debut'] > $contrat_instance->getDate_fin() ) {
//		$dt = date_i18n( 'd/m/Y', $postmeta['amapress_adhesion_date_debut'] );
//
//		return new WP_Error( 'invalid_date', "La date de début $dt est en dehors des dates du contrat '{$contrat_instance->getTitle()}'" );
//	}
	$postmeta['amapress_adhesion_status'] = 'confirmed';

//	$postmeta['amapress_adhesion_contrat_quantite_factors'] = $multi_value;

	return $postmeta;
}

add_filter( 'amapress_import_posts_get_field_name', 'amapress_import_posts_get_field_name', 10, 3 );
function amapress_import_posts_get_field_name( $field_name, $post_type, $colname ) {
	$kvs = amapress_get_wp_posts_labels( $post_type );
	$kvs = array_combine( array_values( $kvs ), array_keys( $kvs ) );
	if ( isset( $kvs[ $field_name ] ) ) {
		return $kvs[ $field_name ];
	}

	$labels = AmapressEntities::getPostFieldsLabels( $post_type );
	$labels = array_combine( array_values( $labels ), array_keys( $labels ) );
	if ( isset( $labels[ $field_name ] ) ) {
		return $labels[ $field_name ];
	}

	//TODO place in right entity
	if ( $post_type == AmapressAdhesion::POST_TYPE ) {
		if ( $field_name == __( 'Contrat', 'amapress' ) ) {
			return 'amapress_adhesion_contrat_instance';
		}
		if ( $field_name == __( 'Quantité', 'amapress' ) ) {
			return 'amapress_adhesion_contrat_quantite';
		}
		$id = Amapress::resolve_post_id( $field_name, AmapressContrat_instance::POST_TYPE );
		if ( $id > 0 ) {
			return $id;
		}

		if ( ! empty( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] ) ) {
			$contrat_instance = AmapressContrat_instance::getBy( intval( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] ) );
			$quant            = amapress_resolve_contrat_quantite_id( $contrat_instance->ID, $field_name );
			if ( ! empty( $quant ) ) {
				return 'contrat_quant_' . $quant['id'];
			}
		}
	}

	if ( ! empty( $_REQUEST['amapress_ignore_unknown_columns'] ) ) {
		global $amapress_csv_import_errors;
		if ( empty( $amapress_csv_import_errors ) ) {
			$amapress_csv_import_errors = [];
		}
		$amapress_csv_import_errors[1] = new WP_Error( 'unknown_header', sprintf( __( 'Colonne %s : un %s ne contient pas de champs %s', 'amapress' ), $colname, $post_type, $field_name ) );

		return null;
	}

	return new WP_Error( 'unknown_header', sprintf( __( 'Colonne %s : un %s ne contient pas de champs %s', 'amapress' ), $colname, $post_type, $field_name ) );
}

add_filter( 'amapress_import_posts_data', 'amapress_import_posts_data', 10, 2 );
function amapress_import_posts_data( $postdata, $postmeta ) {
	$excluded_fields = array(
		'post_author',
		'post_type',
		'post_date',
		'post_date_gmt',
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
	foreach ( $excluded_fields as $excluded_field ) {
		unset( $postdata[ $excluded_field ] );
	}

	$validators = AmapressEntities::getPostFieldsValidators();
	foreach ( $postdata as $k => $v ) {
		if ( ! empty( $validators[ $k ] ) ) {
			$res = call_user_func( $validators[ $k ], $v );
//            if (is_wp_error($res)) return $res;
			$postdata[ $k ] = $res;
		}
	}

	return $postdata;
}

add_filter( 'amapress_import_posts_meta', 'amapress_import_posts_meta', 10, 4 );
function amapress_import_posts_meta( $postmeta, $postdata, $posttaxo, $post_type ) {
	$excluded_fields = array();
	foreach ( $excluded_fields as $excluded_field ) {
		unset( $postmeta[ $excluded_field ] );
	}

	$validators = AmapressEntities::getPostFieldsValidators();
	foreach ( $postmeta as $k => $v ) {
		if ( ! empty( $validators[ $k ] ) ) {
			$res = call_user_func( $validators[ $k ], $v );
//            if (is_wp_error($res)) return $res;
			$postmeta[ $k ] = $res;
		}
	}

	return $postmeta;
}

function amapress_get_validator( $post_type, $field_name, $settings ) {
	if ( isset( $settings['csv_validator'] ) && is_callable( $settings['csv_validator'], false ) ) {
		return $settings['csv_validator'];
	}
	$label    = ! empty( $settings['name'] ) ? $settings['name'] : $field_name;
	$required = isset( $settings['required'] ) ? $settings['required'] : false;
	$type     = $settings['type'];
	if ( $type == 'date' ) {
		$has_time = isset( $settings['time'] ) ? $settings['time'] : false;

		return function ( $value ) use ( $label, $has_time, $required ) {
			try {
				if ( is_wp_error( $value ) ) {
					return $value;
				}
				if ( is_string( $value ) ) {
					$value = trim( $value );
				}
				if ( ! $required && empty( $value ) ) {
					return null;
				}
				$ret_date = 0;
				if ( is_float( $value ) || is_int( $value ) || preg_match( '/^\d+$/', strval( $value ) ) ) {
					$ret_date = PHPExcel_Shared_Date::ExcelToPHP( intval( $value ) );
				} else if ( preg_match( '/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value ) ) {
					$ret_date = DateTime::createFromFormat( TitanFrameworkOptionDate::$default_date_format, $value )->getTimestamp();
				} else if ( preg_match( '/^\d{1,2}\/\d{1,2}\/\d{4} \d{2}:\d{2}$/', $value ) ) {
					$ret_date = DateTime::createFromFormat( TitanFrameworkOptionDate::$default_date_format . ' ' . TitanFrameworkOptionDate::$default_time_format, $value )->getTimestamp();
				} else if ( preg_match( '/^\d{2}:\d{2}$/', $value ) ) {
					$ret_date = DateTime::createFromFormat( date( TitanFrameworkOptionDate::$default_date_format, 0 ) . ' ' . TitanFrameworkOptionDate::$default_time_format, $value )->getTimestamp();
				} else if ( preg_match( '/^\d{1,2}-\d{1,2}-\d{2}$/', $value ) ) {
					$ret_date = DateTime::createFromFormat( 'm-d-y', $value )->getTimestamp();
				} else {
					return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' non valide pour \'%s\'', 'amapress' ), $value, $label ) );
				}
				if ( $has_time ) {
					return $ret_date;
				} else {
					return Amapress::start_of_day( $ret_date );
				}
			} catch ( Exception $e ) {
				return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%d\' non valide pour \'%s\': %s', 'amapress' ), $value, $label, $e->getMessage() ) );
			}
		};
	} else if ( $type == 'checkbox' ) {
		return function ( $value ) use ( $label ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$v = strtolower( trim( $value ) );

			return $v == "true" || $v == "vrai" || $v == "oui" || $v == 1;
		};
	} else if ( $type == 'float' || $type == 'number' || $type == 'price' ) {
		return function ( $value ) use ( $label ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			try {
				return floatval( trim( trim( $value, '€' ) ) );
			} catch ( Exception $e ) {
				return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' non valide pour \'%s\': %s', 'amapress' ), $value, $label, $e->getMessage() ) );
			}
		};
	} else if ( $type == 'select' ) {
		return function ( $value ) use ( $label, $settings ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$v = strtolower( trim( $value ) );
			if ( is_array( $settings['options'] ) && ! array_key_exists( $v, $settings['options'] ) ) {
				$labels = array_combine(
					array_map( function ( $a ) {
						return strtolower( $a );
					}, array_values( $settings['options'] ) ),
					array_keys( $settings['options'] ) );
				if ( ! array_key_exists( $v, $labels ) ) {
					return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' non trouvée pour \'%s\'', 'amapress' ), $value, $label ) );
				} else {
					return $labels[ $v ];
				}
			} else if ( ! is_array( $settings['options'] ) ) {
				return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' non trouvée pour \'%s\'', 'amapress' ), $value, $label ) );
			}

			return $v;
		};
	} else if ( 'select-posts' == $type || 'multicheck-posts' == $type ) {
		return function ( $value ) use ( $label, $settings ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			if ( is_string( $value ) ) {
				$value = trim( $value );
			}
			if ( is_string( $value ) ) {
				$id = Amapress::resolve_post_id( $value, $settings['post_type'] );
				if ( $id > 0 ) {
					return $id;
				}
			}

			$values = Amapress::get_array( $value );
			if ( ! is_array( $values ) ) {
				$values = array( $values );
			}

			$errors = array();
			$res    = array();
			foreach ( $values as $v ) {
				if ( is_wp_error( $v ) ) {
					return $v;
				}

				if ( is_string( $v ) ) {
					$v = trim( $v );
				}

				$id = Amapress::resolve_post_id( $v, $settings['post_type'] );
				if ( $id <= 0 ) {
					$post_type = amapress_unsimplify_post_type( $settings['post_type'] );
					$pt        = amapress_simplify_post_type( $settings['post_type'] );
					$url       = apply_filters( "amapress_get_edit_url_for_$pt", add_query_arg( 's', $v, admin_url( "edit.php?post_type=$post_type" ) ) );
					$errors[]  = sprintf( __( 'Valeur \'%s\' non trouvée/non unique pour \'%s\' (Voir <%s>)', 'amapress' ), $v, $label, $url );
				} else {
					$res[] = $id;
				}
			}
			if ( ! empty( $errors ) ) {
				return new WP_Error( 'cannot_parse', implode( ' ; ', $errors ) );
			}

			if ( count( $res ) == 1 ) {
				return array_shift( $res );
			} else {
				return $res;
			}
		};
	} else if ( 'select-users' == $type || 'multicheck-users' == $type ) {
		return function ( $value ) use ( $label ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			if ( is_string( $value ) ) {
				$value = trim( $value );
			}
			if ( is_string( $value ) ) {
				$id = Amapress::resolve_user_id( $value );
				if ( $id > 0 ) {
					return $id;
				}

				if ( preg_match( '/\S+/', $value, $value_first ) ) {
					$id = Amapress::resolve_user_id( $value_first[0] );
					if ( $id > 0 ) {
						return $id;
					}
				}
			}

			$values = Amapress::get_array( $value );
			if ( ! is_array( $values ) ) {
				$values = array( $values );
			}

			$errors = array();
			$res    = array();
			foreach ( $values as $v ) {
				if ( is_wp_error( $v ) ) {
					return $v;
				}

				if ( is_string( $v ) ) {
					$v = trim( $v );
				}

				$id = Amapress::resolve_user_id( $v );
				if ( $id <= 0 ) {
					$url      = add_query_arg( 's', $v, admin_url( 'users.php' ) );
					$errors[] = sprintf( __( 'Valeur \'%s\' non trouvée/non unique pour \'%s\' (Voir <%s>)', 'amapress' ), $v, $label, $url );
				} else {
					$res[] = $id;
				}
			}
			if ( ! empty( $errors ) ) {
				return new WP_Error( 'cannot_parse', implode( ' ; ', $errors ) );
			}

			if ( count( $res ) == 1 ) {
				return array_shift( $res );
			} else {
				return $res;
			}
		};
	} else if ( 'multicheck' == $type ) {
		return function ( $value ) use ( $label, $settings ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$vs   = trim( strtolower( trim( $value ) ), ',' );
			$vs   = array_map( function ( $v ) use ( $value, $label, $settings ) {
				if ( is_string( $v ) ) {
					$v = trim( $v );
				}

				if ( is_array( $settings['options'] ) && ! array_key_exists( $v, $settings['options'] ) ) {
					$labels = array_combine(
						array_map( function ( $a ) {
							return trim( strtolower( $a ) );
						}, array_values( $settings['options'] ) ),
						array_keys( $settings['options'] ) );
					if ( ! array_key_exists( $v, $labels ) ) {
						return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' dans \'%s\' non trouvée pour \'%s\'', 'amapress' ), $v, $value, $label ) );
					} else {
						return $labels[ $v ];
					}
				} else if ( ! is_array( $settings['options'] ) ) {
					return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' dans \'%s\' non trouvée pour \'%s\'', 'amapress' ), $v, $value, $label ) );
				}

				return $v;
			}, explode( ',', $vs ) );
			$errs = array_map( function ( WP_Error $err ) {
				return $err->get_error_message();
			},
				array_filter( $vs, 'is_wp_error' )
			);
			if ( ! empty( $errs ) ) {
				return new WP_Error( 'cannot_parse', implode( ' ; ', $errs ) );
			}

			return implode( ',', $vs );
		};
	} else if ( 'multidate' == $type ) {
		return function ( $value ) use ( $label, $settings, $required ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$vs = trim( strtolower( trim( $value ) ), ',' );
			if ( ! $required && empty( $vs ) ) {
				return null;
			}
			$vs   = array_map( function ( $v ) use ( $value, $label, $settings ) {
				if ( is_string( $v ) ) {
					$v = trim( $v );
				}

				try {
					if ( is_float( $v ) || is_int( $v ) || preg_match( '/^\d+$/', strval( $v ) ) ) {
						$dt = PHPExcel_Shared_Date::ExcelToPHP( intval( $v ) );
					} else if ( preg_match( '/^\d{1,2}\/\d{1,2}\/\d{4}$/', $v ) ) {
						$dt = DateTime::createFromFormat( TitanFrameworkOptionDate::$default_date_format, $v )->getTimestamp();
					} else if ( preg_match( '/^\d{1,2}\/\d{1,2}\/\d{4} \d{2}:\d{2}$/', $v ) ) {
						$dt = DateTime::createFromFormat( TitanFrameworkOptionDate::$default_date_format . ' ' . TitanFrameworkOptionDate::$default_time_format, $v )->getTimestamp();
					} else if ( preg_match( '/^\d{2}:\d{2}$/', $v ) ) {
						$dt = DateTime::createFromFormat( date( TitanFrameworkOptionDate::$default_date_format, 0 ) . ' ' . TitanFrameworkOptionDate::$default_time_format, $v )->getTimestamp();
					} else if ( preg_match( '/^\d{1,2}-\d{1,2}-\d{2}$/', $v ) ) {
						$dt = DateTime::createFromFormat( 'm-d-y', $v )->getTimestamp();
					} else {
						return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' dans \'%s\' non valide pour \'%s\'', 'amapress' ), $v, $value, $label ) );
					}

					return date_i18n( 'd/m/Y', $dt );
				} catch ( Exception $e ) {
					return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%d\' dans \'%s\' non valide pour \'%s\': %s', 'amapress' ), $v, $value, $label, $e->getMessage() ) );
				}
			}, explode( ',', $vs ) );
			$errs = array_map( function ( WP_Error $err ) {
				return $err->get_error_message();
			},
				array_filter( $vs, 'is_wp_error' )
			);
			if ( ! empty( $errs ) ) {
				return new WP_Error( 'cannot_parse', implode( ' ; ', $errs ) );
			}

			return implode( ',', $vs );
		};
	}

	return null;
}

function amapress_get_adhesions_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressAdhesion::POST_TYPE );
}

//function amapress_get_adhesions_intermittence_import_page()
//{
//    return Amapress_Import_Posts_CSV::get_import_posts_page(AmapressAdhesion_intermittence::POST_TYPE);
//}

function amapress_get_produits_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressProduit::POST_TYPE );
}

function amapress_get_paiements_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressAmapien_paiement::POST_TYPE );
}

function amapress_get_contrat_quantites_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressContrat_quantite::POST_TYPE );
}

function amapress_get_producteurs_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressProducteur::POST_TYPE );
}

function amapress_get_productions_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressContrat::POST_TYPE );
}

function amapress_get_adh_pmt_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressAdhesion_paiement::POST_TYPE );
}

function amapress_get_contrats_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressContrat_instance::POST_TYPE );
}

function amapress_get_visites_import_page() {
	return Amapress_Import_Posts_CSV::get_import_posts_page( AmapressVisite::POST_TYPE );
}

add_action( 'tf_custom_admin_amapress_action_import', 'amapress_process_csv_import' );
function amapress_process_csv_import() {
	global $amapress_imported_csv_contrats;
	$amapress_imported_csv_contrats = [];
	Amapress_Import_Posts_CSV::process_posts_csv_import( AmapressAdhesion::POST_TYPE );
	Amapress_Import_Posts_CSV::process_posts_csv_import( AmapressAdhesion_paiement::POST_TYPE );
//    Amapress_Import_Posts_CSV::process_posts_csv_import(AmapressAdhesion_intermittence::POST_TYPE);
//    Amapress_Import_Posts_CSV::process_posts_csv_import(AmapressAmapien_paiement::POST_TYPE);
	Amapress_Import_Posts_CSV::process_posts_csv_import( AmapressProduit::POST_TYPE );
//    Amapress_Import_Posts_CSV::process_posts_csv_import(AmapressVisite::POST_TYPE);
	Amapress_Import_Posts_CSV::process_posts_csv_import( AmapressProducteur::POST_TYPE );
	Amapress_Import_Posts_CSV::process_posts_csv_import( AmapressContrat::POST_TYPE );
	Amapress_Import_Posts_CSV::process_posts_csv_import( AmapressContrat_instance::POST_TYPE );
	Amapress_Import_Posts_CSV::process_posts_csv_import( AmapressContrat_quantite::POST_TYPE );
}

add_action( 'admin_init', function () {
	foreach ( AmapressContrats::get_active_contrat_instances_ids() as $id ) {
		add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressAdhesion::POST_TYPE . '_contrat_' . $id, 'amapress_process_generate_model' );

	}
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressAdhesion::POST_TYPE, 'amapress_process_generate_model' );
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressAdhesion::POST_TYPE . '_multi', 'amapress_process_generate_model' );
//add_action('tf_custom_admin_amapress_action_generate_model_'.AmapressAdhesion_intermittence::POST_TYPE, 'amapress_process_generate_model');
//add_action('tf_custom_admin_amapress_action_generate_model_'.AmapressAmapien_paiement::POST_TYPE, 'amapress_process_generate_model');
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressAdhesion_paiement::POST_TYPE, 'amapress_process_generate_model' );
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressContrat_quantite::POST_TYPE, 'amapress_process_generate_model' );
	add_action( 'tf_custom_admin_amapress_action_generate_model_user', 'amapress_process_generate_model' );
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressProducteur::POST_TYPE, 'amapress_process_generate_model' );
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressContrat::POST_TYPE, 'amapress_process_generate_model' );
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressProduit::POST_TYPE, 'amapress_process_generate_model' );
	add_action( 'tf_custom_admin_amapress_action_generate_model_' . AmapressContrat_instance::POST_TYPE, 'amapress_process_generate_model' );
//add_action('tf_custom_admin_amapress_action_generate_model_'., 'amapress_process_generate_model');
} );

function amapress_process_generate_model() {
	$action = isset( $_POST['action'] ) ? $_POST['action'] : '';
	switch ( $action ) {
		case 'generate_model_' . AmapressAdhesion::POST_TYPE:
			Amapress_Import_Posts_CSV::generateModel( AmapressAdhesion::POST_TYPE, 'inscriptions_contrats', array() );
			break;
		case 'generate_model_' . AmapressContrat_instance::POST_TYPE:
			Amapress_Import_Posts_CSV::generateModel( AmapressContrat_instance::POST_TYPE, 'contrats', array() );
			break;
		case 'generate_model_' . AmapressAdhesion::POST_TYPE . '_multi':
			Amapress_Import_Posts_CSV::generateModel( AmapressAdhesion::POST_TYPE, 'inscriptions_contrats_multi', array(),
				array( 'amapress_adhesion_contrat_instance' => 'amapress_adhesion_contrat_quantite' ) );
			break;
//        case 'generate_model_'.AmapressAdhesion_intermittence::POST_TYPE:
//            Amapress_Import_Posts_CSV::generateModel(AmapressAdhesion_intermittence::POST_TYPE, 'inscriptions_intermittents', array());
//            break;
		case 'generate_model_' . AmapressAdhesion_paiement::POST_TYPE:
			Amapress_Import_Posts_CSV::generateModel( AmapressAdhesion_paiement::POST_TYPE, 'adhesions_amap', array() );
			break;
		case 'generate_model_' . AmapressContrat_quantite::POST_TYPE:
			Amapress_Import_Posts_CSV::generateModel( AmapressContrat_quantite::POST_TYPE, 'contrats_config_paniers', [
				'post_title',
				'post_content'
			] );
			break;
		case 'generate_model_' . AmapressProducteur::POST_TYPE:
			Amapress_Import_Posts_CSV::generateModel( AmapressProducteur::POST_TYPE, 'producteurs', [
				'post_title',
				'post_content'
			] );
			break;
		case 'generate_model_' . AmapressContrat::POST_TYPE:
			Amapress_Import_Posts_CSV::generateModel( AmapressContrat::POST_TYPE, 'productions', [
				'post_title',
				'post_content'
			] );
			break;
		case 'generate_model_' . AmapressProduit::POST_TYPE:
			Amapress_Import_Posts_CSV::generateModel( AmapressProduit::POST_TYPE, 'produits', [
				'post_title',
				'post_content'
			] );
			break;
		case 'generate_model_user':
			Amapress_Import_Users_CSV::generateModel( 'amapiens', array(
				'user_email',
				'first_name',
				'last_name',
				'email2',
				'email3',
				'email4',
				'roles'
			) );
			break;
		default:
			if ( 0 === strpos( $action, 'generate_model_' . AmapressAdhesion::POST_TYPE . '_contrat_' ) ) {
				$contrat_instance_id = intval( substr( $action, strlen( 'generate_model_' . AmapressAdhesion::POST_TYPE . '_contrat_' ) ) );
				Amapress_Import_Posts_CSV::generateModel(
					AmapressAdhesion::POST_TYPE, 'inscriptions_contrat', array(),
					array( 'amapress_adhesion_contrat_quantite' => 'amapress_adhesion_contrat_quantite' ),
					$contrat_instance_id,
					[
						'amapress_adhesion_contrat_instance'
					] );
			}
			break;
	}
}


add_action( 'tf_custom_admin_amapress_action_import', 'amapress_process_users_csv_import' );
function amapress_process_users_csv_import() {
	Amapress_Import_Users_CSV::process_users_csv_import();
}

add_filter( 'amapress_csv_posts_produit_import_required_headers', 'amapress_csv_posts_produit_import_required_headers', 10, 2 );
function amapress_csv_posts_produit_import_required_headers( $required_headers, $headers ) {
	$required_headers = array_combine( array_values( $required_headers ), array_values( $required_headers ) );

	if ( ! empty( $_REQUEST['amapress_import_produit_default_producteur'] ) ) {
		unset( $required_headers['amapress_produit_producteur'] );
	}

	return array_values( $required_headers );
}

add_filter( 'amapress_csv_posts_contrat_quantite_import_required_headers', 'amapress_csv_posts_contrat_quantite_import_required_headers', 10, 2 );
function amapress_csv_posts_contrat_quantite_import_required_headers( $required_headers, $headers ) {
	$required_headers = array_combine( array_values( $required_headers ), array_values( $required_headers ) );

	if ( ! empty( $_REQUEST['amapress_import_contrat_quantite_default_contrat_instance'] ) ) {
		unset( $required_headers['amapress_contrat_quantite_contrat_instance'] );
	}

	return array_values( $required_headers );
}

add_filter( 'amapress_csv_posts_adhesion_paiement_import_required_headers', 'amapress_csv_posts_adhesion_paiement_import_required_headers', 10, 2 );
function amapress_csv_posts_adhesion_paiement_import_required_headers( $required_headers, $headers ) {
	$required_headers = array_combine( array_values( $required_headers ), array_values( $required_headers ) );

	if ( ! empty( $_REQUEST['amapress_import_adhesion_paiement_default_period'] ) ) {
		unset( $required_headers['amapress_adhesion_paiement_period'] );
	}

	return array_values( $required_headers );
}

add_filter( 'amapress_csv_posts_adhesion_import_required_headers', 'amapress_csv_posts_adhesion_import_required_headers', 10, 2 );
function amapress_csv_posts_adhesion_import_required_headers( $required_headers, $headers ) {
	$required_headers = array_combine( array_values( $required_headers ), array_values( $required_headers ) );

	$has_multi_quant_columns = false;
	$has_multi               = false;
	foreach ( $headers as $h ) {
		$has_multi               = $has_multi || is_int( $h );
		$has_multi_quant_columns = $has_multi_quant_columns
		                           || strpos( $h, 'contrat_quant_' ) !== false;
	}

	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] ) ) {
		unset( $required_headers['amapress_adhesion_contrat_instance'] );
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_lieu'] ) ) {
		unset( $required_headers['amapress_adhesion_lieu'] );
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_date_debut'] ) ) {
		unset( $required_headers['amapress_adhesion_date_debut'] );
	}
	if ( ! empty( $_REQUEST['amapress_import_produit_default_producteur'] ) ) {
		unset( $required_headers['amapress_produit_producteur'] );
	}

	if ( $has_multi_quant_columns ) {
		unset( $required_headers['amapress_adhesion_contrat_quantite'] );
		unset( $required_headers['amapress_adhesion_contrat_quantite_factors'] );
	}

	if ( $has_multi ) {
		unset( $required_headers['amapress_adhesion_contrat_instance'] );
		unset( $required_headers['amapress_adhesion_contrat_quantite'] );
		unset( $required_headers['amapress_adhesion_contrat_quantite_factors'] );
	}

//    var_dump($_REQUEST);
//    var_dump($required_headers);
//    die();
	return array_values( $required_headers );
}

add_filter( 'amapress_import_resolve_post', 'amapress_import_resolve_post', 10, 4 );
function amapress_import_resolve_post( $post, $post_type, $postdata, $postmeta ) {
	$post_type = amapress_simplify_post_type( $post_type );
	$ents      = AmapressEntities::getPostTypes();
	if ( isset( $ents[ $post_type ]['import_by_meta'] ) && $ents[ $post_type ]['import_by_meta'] == false ) {
		return null;
	}

	$fields   = AmapressEntities::getPostTypeFields( $post_type );
	$tmp_meta = array_combine( array_keys( $postmeta ), array_values( $postmeta ) );

	$tmp_meta = apply_filters( "amapress_import_{$post_type}_apply_default_values_to_posts_meta", $tmp_meta, $postdata );
	$tmp_meta = apply_filters( "amapress_import_apply_default_values_to_posts_meta", $tmp_meta, $postdata );

	foreach ( $tmp_meta as $k => $v ) {
		if ( is_wp_error( $k ) ) {
			return $k;
		}
		if ( is_wp_error( $v ) ) {
			return $v;
		}
	}

	$tmp_meta = array_filter( $tmp_meta, function ( $v, $k ) use ( $fields ) {
		if ( empty( $k ) || empty( $v ) ) {
			return false;
		}
		if ( isset( $fields[ $k ]['import_key'] ) ) {
			return true;
		}

		return false;
	}, ARRAY_FILTER_USE_BOTH );
	if ( empty( $tmp_meta ) ) {
		return null;
	}
	$args  = array(
		'post_type'      => amapress_unsimplify_post_type( $post_type ),
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array_map( function ( $k, $v ) use ( $fields ) {
				if ( $fields[ $k ]['type'] == 'date' ) {
					return array(
						'key'     => $k,
						'value'   => array(
							Amapress::start_of_day( intval( $v ) ),
							Amapress::end_of_day( intval( $v ) )
						),
						'compare' => 'BETWEEN',
					);
				}

				return array(
					'key'   => $k,
					'value' => $v,
				);
			}, array_keys( $tmp_meta ), array_values( $tmp_meta ) )
		),
	);
	$posts = get_posts( $args );

//    var_dump($posts);

	if ( count( $posts ) == 1 ) {
		return $posts[0];
	} else if ( count( $posts ) > 1 ) {
		$pt       = amapress_unsimplify_post_type( $post_type );
		$post_ids = array_map( function ( $p ) {
			return $p->ID;
		}, $posts );
		$url      = admin_url( 'edit.php?post_type=' . $pt . '&amapress_post=' . implode( ',', $post_ids ) );

		return new WP_Error( "amapress_duplicate_$post_type", sprintf( __( 'Il existe déjà plusieurs %s avec les mêmes données. Voir <%s>', 'amapress' ), $post_type, $url ) );
	}

//    var_dump($args);

	return null;
}

add_filter( 'amapress_csv_posts_import_required_headers', 'amapress_csv_posts_import_required_headers', 10, 2 );
function amapress_csv_posts_import_required_headers( $required_headers, $post_type ) {
	$ents = AmapressEntities::getPostTypes();

	if ( isset( $ents[ $post_type ]['csv_required_fields'] ) ) {
		if ( ! is_array( $ents[ $post_type ]['csv_required_fields'] ) ) {
			$ents[ $post_type ]['csv_required_fields'] = [ $ents[ $post_type ]['csv_required_fields'] ];
		}
		$required_headers = array_merge( $required_headers, $ents[ $post_type ]['csv_required_fields'] );
	}

	$fields = AmapressEntities::getPostTypeFields( $post_type );
	foreach ( $fields as $field_name => $field ) {
		if ( ( isset( $field['csv_required'] ) && $field['csv_required'] == true )
		     || ( isset( $field['import_key'] ) && $field['import_key'] == true ) ) {
			$required_headers[] = $field_name;
		}
	}

	return $required_headers;
}

add_filter( 'amapress_csv_users_import_required_headers', 'amapress_csv_users_import_required_headers' );
function amapress_csv_users_import_required_headers( $required_headers ) {
	$ents = AmapressEntities::getPostTypes();

	if ( isset( $ents['user']['csv_required_fields'] ) ) {
		$required_headers = array_merge( $required_headers, $ents['user']['csv_required_fields'] );
	}

	$fields = AmapressEntities::getPostTypeFields( 'user' );
	foreach ( $fields as $field_name => $field ) {
		if ( ( isset( $field['csv_required'] ) and $field['csv_required'] == true )
		     or ( isset( $field['import_key'] ) and $field['import_key'] == true ) ) {
			$required_headers[] = $field_name;
		}
	}

	return $required_headers;
}

add_filter( 'amapress_import_contrat_instance_check_resolved_post_data_before_update',
	function ( $postdata, $multi_key, $multi_value, $postmeta, $posttaxo, $post_type, $postmulti ) {
		if ( isset( $postdata['ID'] ) ) {
			$post_id = $postdata['ID'];
			$contrat = AmapressContrat_instance::getBy( $post_id, true );
			if ( $contrat->isArchived() ) {
				return new WP_Error( 'contrat_archived', sprintf( __( 'Le contrat %s est archivé !', 'amapress' ), $contrat->getTitle() ) );
			}
			if ( ! isset( $_REQUEST['amapress_override_contrat_with_inscriptions'] ) ) {
				$adhs = AmapressContrats::get_active_adhesions( $post_id );
				if ( ! empty( $adhs ) ) {
					return new WP_Error( 'contrat_has_inscr', sprintf( __( 'Le contrat %s a des inscriptions : modifier ses paramètres peut modifier ou annuler ses inscriptions en cours.  <br/>Si vous êtes sûr, cocher la case "Autoriser la mise à jour de contrats avec inscriptions actives"', 'amapress' ), $contrat->getTitle() ) );
				}
			}

		}

		return $postdata;
	}, 10, 7 );

add_filter( 'amapress_import_contrat_quantite_check_resolved_post_data_before_update',
	function ( $postdata, $multi_key, $multi_value, $postmeta, $posttaxo, $post_type, $postmulti ) {
		if ( isset( $postmeta['amapress_contrat_quantite_contrat_instance'] ) ) {
			$post_id = $postmeta['amapress_contrat_quantite_contrat_instance'];
			$contrat = AmapressContrat_instance::getBy( $post_id, true );
			if ( ! $contrat ) {
				return new WP_Error( 'contrat_not_found', sprintf( __( 'Le contrat %s n\'existe pas', 'amapress' ), $contrat->getTitle() ) );
			}
			if ( $contrat->isArchived() ) {
				return new WP_Error( 'contrat_archived', sprintf( __( 'Le contrat %s est archivé !', 'amapress' ), $contrat->getTitle() ) );
			}
			$amapress_override_all_contrat_quantites = isset( $_REQUEST['amapress_override_all_contrat_quantites'] );
			if ( ! isset( $_REQUEST['amapress_override_contrat_with_inscriptions'] )
			     || $amapress_override_all_contrat_quantites ) {
				$adhs = AmapressContrats::get_active_adhesions( $post_id );
				if ( ! empty( $adhs ) ) {
					if ( ! $amapress_override_all_contrat_quantites ) {
						return new WP_Error( 'contrat_has_inscr', sprintf( __( 'Le contrat %s a des inscriptions : modifier ses configurations de paniers peut modifier ou annuler ses inscriptions en cours. <br/>Si vous êtes sûr, cocher la case "Autoriser la mise à jour de contrats avec inscriptions actives"', 'amapress' ), $contrat->getTitle() ) );
					} else {
						return new WP_Error( 'contrat_has_inscr', sprintf( __( 'Le contrat %s a des inscriptions : supprimer ses configurations de paniers avant de réimporter des nouvelles aurait pour conséquence d\'annuler ses inscriptions en cours. Vous devez mettre à jour les configurations de paniers dans %s', 'amapress' ), $contrat->getTitle(), Amapress::makeLink( $contrat->getAdminEditLink(), 'la configuration du contrat' ) ) );
					}
				}
			}

			global $amapress_imported_csv_contrats;
			if ( empty( $amapress_imported_csv_contrats ) ) {
				$amapress_imported_csv_contrats = [];
			}

			if ( $amapress_override_all_contrat_quantites ) {
				if ( ! in_array( $post_id, $amapress_imported_csv_contrats ) ) {
					$amapress_imported_csv_contrats[] = $post_id;
					$quantites                        = AmapressContrats::get_contrat_quantites( $post_id );
					foreach ( $quantites as $quantite ) {
						wp_delete_post( $quantite->ID, true );
					}
					unset( $postdata['ID'] );
				}
			}
		}

		return $postdata;
	}, 10, 7 );

add_action( 'amapress_contrat_instance_posts_import', function ( $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$contrat_instance = AmapressContrat_instance::getBy( $post_id );
		if ( $contrat_instance ) {
			AmapressDistributions::generate_distributions( $contrat_instance->ID, false );
			AmapressPaniers::generate_paniers( $contrat_instance->ID, false );
		}
	}
} );