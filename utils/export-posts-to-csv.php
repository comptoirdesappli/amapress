<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class AmapressExport_Posts {

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public static function get_export_url( $name = null ) {
		$url = add_query_arg( 'amapress_export', 'csv' );
		if ( ! empty( $name ) ) {
			$url = add_query_arg( 'amapress_export_name', $name, $url );
		}

		return wp_nonce_url( $url, 'amapress-export-posts-posts-page_export', '_wpnonce-amapress-export-posts-posts-page_export' );
	}

	public static function generate_csv() {
		global $wp_query;

		if ( isset( $_REQUEST['amapress_export'] ) && isset( $_REQUEST['_wpnonce-amapress-export-posts-posts-page_export'] ) ) {
			check_admin_referer( 'amapress-export-posts-posts-page_export', '_wpnonce-amapress-export-posts-posts-page_export' );

			$args = array(
				'fields'         => 'all_with_meta',
				'posts_per_page' => - 1,
			);
			$args = wp_parse_args( $args, wp_parse_args( $_SERVER['QUERY_STRING'] ) );

			$posts = get_posts( $args );

			if ( ! $posts ) {
				$referer = add_query_arg( 'error', 'empty', wp_get_referer() );
				wp_redirect( remove_query_arg( 'amapress_export', $referer ) );
				exit;
			}

			$pt       = amapress_simplify_post_type( $args['post_type'] );
			$name     = isset( $_REQUEST['amapress_export_name'] ) ? isset( $_REQUEST['amapress_export_name'] ) : $pt;
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) ) {
				$sitename .= '.';
			}
			$filename = $sitename . $name . '.' . date( 'Y-m-d-H-i-s' );

			$exclude_data = array();
			$exclude_data = apply_filters( "amapress_posts_export_exclude_data", $exclude_data, $name );
			$exclude_data = apply_filters( "amapress_posts_{$name}_export_exclude_data", $exclude_data );

			global $wpdb;

			$data_keys        = array(
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
			$meta_keys        = $wpdb->get_results( "SELECT distinct(pm.meta_key) FROM $wpdb->postmeta pm INNER JOIN $wpdb->posts p on p.ID = pm.post_id where p.post_type='{$args['post_type']}'" );
			$meta_keys        = wp_list_pluck( $meta_keys, 'meta_key' );
			$taxonomies_keys  = array();
			$taxonomies_names = array();
			foreach ( get_object_taxonomies( amapress_unsimplify_post_type( $args['post_type'] ), 'objects' ) as $tax_name => $tax ) {
				$taxonomies_keys[]             = $tax_name;
				$taxonomies_names[ $tax_name ] = $tax->label;
			}
			$fields = array_merge( $data_keys, $meta_keys );
			$fields = apply_filters( "amapress_posts_export_fields", $fields, $name );
			$fields = apply_filters( "amapress_{$name}_export_fields", $fields, $name );

			$csv_data = array();
			$headers  = array();
			foreach ( $fields as $key => $field ) {
				if ( in_array( $field, $exclude_data ) ) {
					unset( $fields[ $key ] );
				} else if ( in_array( $field, $taxonomies_names ) ) {
					$headers[] = $taxonomies_names[ $field ];
				} else {
					$header = apply_filters( "amapress_posts_get_field_display_name", $field, $pt );
					$header = apply_filters( "amapress_posts_{$pt}_get_field_display_name", $header );
					if ( empty( $header ) ) {
						unset( $fields[ $key ] );
						continue;
					}
					$headers[] = $header;
				}
			}
			$csv_data[] = $headers;

			foreach ( $posts as $post ) {
//                var_dump($fields);
//                die();
//                $customs = get_post_custom($post->ID);
				$data = array();
				foreach ( $fields as $field ) {
					if ( in_array( $field, $taxonomies_names ) ) {
						$value = implode( ', ', wp_get_post_terms( $post->ID, $field, array( "fields" => "names" ) ) );
					} else {
						$value = ! empty( $post->{$field} ) ? TitanEntity::prepare_custom_field_value( $post->{$field} ) : '';
//                        var_dump($value);
//                        if ('amapress_producteur_user' == $field) die();
//                    } else {
//                        var_dump($customs);
//                        die();
//                        $value = !empty($customs[$field]) ? TitanEntity::prepare_custom_field_value($customs[$field]) : '';
//                        var_dump($field);
//                        var_dump($value);
//                        if ('amapress_producteur_user' == $field) die();
					}
					$value  = apply_filters( "amapress_posts_export_prepare_value", $value, $field, $post );
					$value  = apply_filters( "amapress_posts_{$pt}_export_prepare_value", $value, $field, $post );
					$data[] = is_array( $value ) ? serialize( $value ) : $value;
				}
				$csv_data[] = $data;
			}

			require_once( AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php' );

			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setCreator( "Amapress" )
			            ->setLastModifiedBy( "Amapress" )
			            ->setTitle( $filename );
			$objPHPExcel->setActiveSheetIndex( 0 )->fromArray( $csv_data );
			$objPHPExcel->getActiveSheet()->setTitle( $name );
			$objPHPExcel->setActiveSheetIndex( 0 );

			// Redirect output to a client’s web browser (Excel2007)
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
			$objWriter->save( 'php://output' );
			exit;
		}
	}
}
