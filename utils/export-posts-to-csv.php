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
	public static function get_export_url( $name = null, $url = null, $columns = null ) {
		if ( empty( $url ) ) {
			$url = add_query_arg( 'amapress_export', 'csv' );
		} else {
			$url = add_query_arg( 'amapress_export', 'csv', $url );
		}

		if ( ! empty( $name ) ) {
			$url = add_query_arg( 'amapress_export_name', $name, $url );
		}
		if ( ! empty( $columns ) ) {
			$url = add_query_arg( 'amapress_export_columns', $columns, $url );
		}

		return wp_nonce_url( $url, 'amapress-export-posts-posts-page_export', '_wpnonce-amapress-export-posts-posts-page_export' );
	}

	public static function generate_csv() {
		if ( isset( $_REQUEST['amapress_export'] ) && isset( $_REQUEST['_wpnonce-amapress-export-posts-posts-page_export'] ) ) {
			check_admin_referer( 'amapress-export-posts-posts-page_export', '_wpnonce-amapress-export-posts-posts-page_export' );

			$args                    = wp_parse_args( $_SERVER['QUERY_STRING'] );
			$pt                      = amapress_simplify_post_type( $args['post_type'] );
			$amapress_export_columns = isset( $_REQUEST['amapress_export_columns'] ) ?
				$_REQUEST['amapress_export_columns'] : 'all';
			$amapress_export_name    = isset( $_REQUEST['amapress_export_name'] ) ?
				$_REQUEST['amapress_export_name'] : $pt;
			$objPHPExcel             = self::generate_phpexcel_sheet(
				$args, $amapress_export_name,
				null,
				$amapress_export_columns
			);

			if ( null == $objPHPExcel ) {
				$referer = add_query_arg( 'error', 'empty', wp_get_referer() );
				wp_redirect( remove_query_arg( 'amapress_export', $referer ) );
				exit;
			}

			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) ) {
				$sitename .= '.';
			}
			$filename = $sitename . $amapress_export_name . '.' . date( 'Y-m-d-H-i-s' );


			@ob_clean();
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
			Amapress::outputExcel( $objWriter );
			exit;
		}
	}

	public static function generate_phpexcel_sheet(
		$query_string,
		$base_export_name = null,
		$title = null,
		$columns = 'all'
	) {
		$data = self::generate_export_data( $query_string, $base_export_name, $title, $columns );

		require_once( AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php' );

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator( "Amapress" )
		            ->setLastModifiedBy( "Amapress" )
		            ->setTitle( ! empty( $title ) ? $title : $data['export_name'] );
		$objPHPExcel->setActiveSheetIndex( 0 )->fromArray( array_merge( $data['csv_headers'], $data['csv_data'] ) );
		$objPHPExcel->getActiveSheet()->setTitle( $data['export_name'] );
		$objPHPExcel->setActiveSheetIndex( 0 );

		return $objPHPExcel;
	}

	public static function generate_export_data(
		$query_string,
		$base_export_name = null,
		$title = null,
		$columns = 'all'
	) {
		$args = array(
			'fields'         => 'all_with_meta',
			'posts_per_page' => - 1,
		);

		$args = wp_parse_args( $args, wp_parse_args( $query_string ) );

		unset( $args['_wpnonce-amapress-export-posts-posts-page_export'] );
		unset( $args['amapress_export'] );
		unset( $args['amapress_export_columns'] );

		$pt          = amapress_simplify_post_type( $args['post_type'] );
		$export_name = ! empty( $base_export_name ) ? $base_export_name : $pt;

		$exclude_data = array();
		$exclude_data = apply_filters( "amapress_posts_export_exclude_data", $exclude_data, $export_name );
		$exclude_data = apply_filters( "amapress_posts_{$export_name}_export_exclude_data", $exclude_data );

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
		$fields = apply_filters( "amapress_posts_export_fields", $fields, $export_name );
		$fields = apply_filters( "amapress_{$export_name}_export_fields", $fields, $export_name );

		$include_data = [];
		if ( 'visible' == $columns ) {
			if ( ! function_exists( 'get_hidden_columns' ) ) {
				require_once ABSPATH . 'wp-admin/includes/template.php';
				require_once ABSPATH . 'wp-admin/includes/screen.php';
			}
			global $current_screen;
			$hidden       = get_hidden_columns(
				$current_screen ?
					$current_screen :
					'edit-' . amapress_unsimplify_post_type( $export_name )
			);
			$exclude_data = array_merge( $exclude_data,
				$hidden
			);
		} elseif ( 'all' != $columns ) {
			if ( ! is_array( $columns ) ) {
				$columns = explode( ',', $columns );
			}
			$include_data = $columns;
			$exclude_data = [];
		}

		$headers    = array();
		$header_ids = array();
		foreach ( $fields as $key => $field ) {
			if ( ! empty( $include_data ) && ! in_array( $field, $include_data ) ) {
				unset( $fields[ $key ] );
			} elseif ( in_array( $field, $exclude_data ) ) {
				unset( $fields[ $key ] );
			} else if ( in_array( $field, $taxonomies_names ) ) {
				$headers[]    = $taxonomies_names[ $field ];
				$header_ids[] = $field;
			} else {
				$header = apply_filters( "amapress_posts_get_field_display_name", $field, $pt );
				$header = apply_filters( "amapress_posts_{$pt}_get_field_display_name", $header );
				$header = wp_specialchars_decode( $header );
				$header = html_entity_decode( $header );
				if ( empty( $header ) ) {
					unset( $fields[ $key ] );
					continue;
				}
				$headers[]    = $header;
				$header_ids[] = $field;
			}
		}

		$csv_data = array();
		$posts    = get_posts( $args );
		foreach ( $posts as $post ) {
			$data = array();
			foreach ( $fields as $field ) {
				if ( in_array( $field, $taxonomies_names ) ) {
					$value = implode( ', ', wp_get_post_terms( $post->ID, $field, array( "fields" => "names" ) ) );
				} else {
					$value = ! empty( $post->{$field} ) ? TitanEntity::prepare_custom_field_value( $post->{$field} ) : '';
				}
				$value  = apply_filters( "amapress_posts_export_prepare_value", $value, $field, $post );
				$value  = apply_filters( "amapress_posts_{$pt}_export_prepare_value", $value, $field, $post );
				$data[] = is_array( $value ) ? serialize( $value ) : $value;
			}
			$csv_data[] = $data;
		}

		return [
			'header_ids'  => $header_ids,
			'csv_headers' => $headers,
			'csv_data'    => $csv_data,
			'export_name' => $export_name,
			'args'        => $args,
			'query'       => implode( '&', array_map( function ( $k, $v ) {
				return "$k=$v";
			}, array_keys( $args ), array_values( $args ) ) ),
		];
	}

	public static function generate_datatable_data(
		$query_string,
		$base_export_name = null,
		$title = null,
		$columns = 'all'
	) {
		$data = self::generate_export_data( $query_string, $base_export_name, $title, $columns );

		$header_ids = $data['header_ids'];

		$data_columns = [];
		$data_rows    = [];
		foreach ( array_combine( $header_ids, $data['csv_headers'] ) as $k => $v ) {
			$data_columns[] = array(
				'title' => $v,
				'data'  => $k,
			);
		}
		foreach ( $data['csv_data'] as $csv_row ) {
			$data_row = [];
			$i        = 0;
			foreach ( $csv_row as $col_data ) {
				$data_row[ $header_ids[ $i ] ] = $col_data;
				$i                             += 1;
			}
			$data_rows[] = $data_row;
		}

		return [
			'columns' => $data_columns,
			'data'    => $data_rows,
		];
	}
}
