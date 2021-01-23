<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_init', 'amapress_csv_export' );
function amapress_csv_export() {
	AmapressExport_Users::generate_csv();
	AmapressExport_Posts::generate_csv();
}

add_filter( 'amapress_posts_adhesion_export_exclude_data', 'amapress_posts_adhesion_export_exclude_data' );
function amapress_posts_adhesion_export_exclude_data( $exclude_data ) {
	return array_merge( array( 'post_title', 'post_content', 'post_excerpt' ), $exclude_data );
}

add_filter( 'amapress_posts_export_exclude_data', 'amapress_posts_export_exclude_data', 10, 2 );
function amapress_posts_export_exclude_data( $exclude_data, $post_type ) {
	$exclude_data = array_merge( array(
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
		'menu_order',
		'thumb-preview'
	), $exclude_data );

	foreach ( AmapressEntities::getFilteredFields( $post_type ) as $field => $options ) {
		if ( ( isset( $options['csv'] ) && $options['csv'] == false ) || ( isset( $options['csv_export'] ) && $options['csv_export'] == false ) ) {
			$exclude_data[] = "amapress_{$post_type}_{$field}";
		}
	}

	return $exclude_data;
}

add_filter( 'amapress_posts_get_field_display_name', 'amapress_posts_get_field_display_name', 10, 2 );
function amapress_posts_get_field_display_name( $field_name, $post_type ) {
	$kvs = amapress_get_wp_posts_labels( $post_type );
	if ( isset( $kvs[ $field_name ] ) ) {
		return $kvs[ $field_name ];
	}

	$labels = AmapressEntities::getPostFieldsLabels( $post_type );
	if ( isset( $labels[ $field_name ] ) ) {
		return $labels[ $field_name ];
	}

	if ( $post_type == AmapressAdhesion_paiement::POST_TYPE ) {
		$terms = get_terms( AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
			array(
				'taxonomy'   => AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
				'hide_empty' => false,
			) );
		foreach ( $terms as $term ) {
			/** @var WP_Term $term */
			if ( "amapress_adhesion_paiement_pmt_{$term->term_id}" == $field_name ) {
				return $term->name;
			}
		}
	}

	$name = amapress_unsimplify_post_type( $post_type );
	$cols = apply_filters( "manage_edit-{$name}_columns", array() );
	if ( isset( $cols[ $field_name ] ) ) {
		return $cols[ $field_name ];
	}

	//TODO place in right entity
	if ( $post_type == AmapressAdhesion::POST_TYPE ) {
		if ( $field_name == 'amapress_adhesion_contrat_instance' ) {
			return __( 'Contrat', 'amapress' );
		}
		if ( $field_name == 'amapress_adhesion_contrat_quantite' ) {
			return __( 'Quantité', 'amapress' );
		}
	}

	return null;
}

add_filter( 'amapress_posts_export_fields', 'amapress_posts_export_fields', 10, 2 );
function amapress_posts_export_fields( $fields, $name ) {
	$name     = amapress_unsimplify_post_type( $name );
	$def_cols = array(
		'ID'           => 'ID',
		'post_title'   => __( 'Name' ),
		'post_excerpt' => __( 'Excerpt' ),
		'post_content' => __( 'Content' ),
	);
	if ( ! post_type_supports( $name, 'title' ) ) {
		unset( $def_cols['post_title'] );
	}
	if ( ! post_type_supports( $name, 'excerpt' ) ) {
		unset( $def_cols['post_excerpt'] );
	}
	if ( ! post_type_supports( $name, 'editor' ) ) {
		unset( $def_cols['post_content'] );
	}
	$cols = apply_filters( "manage_edit-{$name}_columns", $def_cols );

	return array_keys( $cols );
}

add_filter( 'amapress_posts_export_prepare_value', 'amapress_posts_export_prepare_value', 10, 3 );
function amapress_posts_export_prepare_value( $value, $field, $post ) {
	$value = apply_filters( "manage_{$post->post_type}_posts_custom_column_export", $value, $field, $post->ID );

	$formatters = AmapressEntities::getPostFieldsFormatters();
	if ( ! empty( $formatters[ $field ] ) ) {
		$res = call_user_func( $formatters[ $field ], $value );

		return $res;
	}

	return $value;
}

add_filter( 'amapress_users_export_exclude_data', 'amapress_users_export_exclude_data' );
function amapress_users_export_exclude_data( $exclude_date ) {
	$exclude_date = array_merge( array(
		'user_pass',
		'user_registered',
		'rich_editing',
		'comment_shortcuts',
		'admin_color',
		'use_ssl',
		'show_admin_bar_front',
		'show_admin_bar_admin'
	), $exclude_date );

	foreach ( AmapressEntities::getFilteredFields( 'user' ) as $field => $options ) {
		if ( ( isset( $options['csv'] ) && $options['csv'] == false ) || ( isset( $options['csv_export'] ) && $options['csv_export'] == false ) ) {
			$exclude_data[] = "amapress_user_{$field}";
		}
	}

	return $exclude_date;
}

add_filter( 'amapress_users_get_field_display_name', 'amapress_users_get_field_display_name' );
function amapress_users_get_field_display_name( $field_name ) {
	$kvs = amapress_get_wp_users_labels();
	if ( isset( $kvs[ $field_name ] ) ) {
		return $kvs[ $field_name ];
	}

	$labels = AmapressEntities::getPostFieldsLabels( 'user' );
	if ( isset( $labels[ $field_name ] ) ) {
		return $labels[ $field_name ];
	}

	$cols = apply_filters( 'manage_users_columns', array() );
	if ( isset( $cols[ $field_name ] ) ) {
		return $cols[ $field_name ];
	}

	return null;
}

add_filter( 'amapress_users_export_fields', 'amapress_users_export_fields', 10, 2 );
function amapress_users_export_fields( $fields, $name ) {
	$cols = apply_filters( 'manage_users_columns', array(
		'ID'         => 'ID',
		'user_login' => __( 'Username' ),
		'first_name' => __( 'First Name' ),
		'last_name'  => __( 'Last Name' ),
		'user_email' => __( 'Email' ),
		'email2'     => __( 'Email 2' ),
		'email3'     => __( 'Email 3' ),
		'email4'     => __( 'Email 4' ),
		'role'       => __( 'Rôle sur le site' ),
	) );

	return array_keys( $cols );
}

add_filter( 'amapress_users_export_prepare_value', 'amapress_users_export_prepare_value', 10, 3 );
function amapress_users_export_prepare_value( $value, $field, $user ) {
	$value = apply_filters( 'manage_users_custom_column_export', $value, $field, $user->ID );

	$formatters = AmapressEntities::getPostFieldsFormatters();
	if ( ! empty( $formatters[ $field ] ) ) {
		$res = call_user_func( $formatters[ $field ], $value );

		return $res;
	}
	if ( $field == 'roles' ) {
		return implode( ', ', $value );
	}

	return $value;
}


function amapress_get_formatter( $post_type, $key, $type, $label ) {
	if ( $type == 'date' ) {
		return function ( $value ) use ( $label, $post_type ) {
			if ( intval( $value ) <= 0 ) {
				return '';
			}
			$ents    = AmapressEntities::getPostTypes();
			$pt_ents = wp_parse_args( $ents[ $post_type ], array( 'date' => true, 'time' => false ) );
			if ( $pt_ents['date'] && ! $pt_ents['time'] ) {
				return date_i18n( TitanFrameworkOptionDate::$default_date_format, intval( $value ) );
			} else if ( ! $pt_ents['date'] && $pt_ents['time'] ) {
				return date_i18n( TitanFrameworkOptionDate::$default_time_format, intval( $value ) );
			} else {
				return date_i18n( TitanFrameworkOptionDate::$default_date_format . ' ' . TitanFrameworkOptionDate::$default_time_format, intval( $value ) );
			}
		};
	} else if ( $type == 'checkbox' ) {
		return function ( $value ) use ( $label ) {
			return ( $value ? 'oui' : 'non' );
		};
	} else if ( $type == 'float' || $type == 'price' ) {
		return function ( $value ) use ( $label ) {
			return $value;
		};
	} else if ( $type == 'select' ) {
		return function ( $value ) use ( $label, $post_type, $key ) {
			$v      = strtolower( $value );
			$fields = AmapressEntities::getFilteredFields( $post_type );
			if ( is_array( $fields[ $key ]['options'] ) && array_key_exists( $v, $fields[ $key ]['options'] ) ) {
				return $fields[ $key ]['options'][ $v ];
			}

			return $v;
		};
	} else if ( $type == 'select-posts' ) {
		return function ( $value ) use ( $label, $post_type ) {
			$id = Amapress::resolve_post_id( $value, $post_type );
			if ( $id <= 0 ) {
				return $value;
			}

			return get_post( $id )->post_title;
		};
	} else if ( $type == 'select-users' ) {
		return function ( $value ) use ( $label ) {
			$id = Amapress::resolve_user_id( $value );
			if ( $id <= 0 ) {
				return $value;
			}

			return amapress_get_user_by_id_or_archived( $id )->user_email;
		};
	} else if ( strpos( $type, 'multicheck' ) || strpos( $type, 'multidate' ) ) {
		return function ( $value ) use ( $label, $type ) {
			return new WP_Error( 'unsupported', "Type $type is not supported ($label)" );
		};
	}
}