<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class AmapressExport_Users {

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

		return wp_nonce_url( $url,
			'amapress-export-users-users-page_export', '_wpnonce-amapress-export-users-users-page_export' );
	}

	public static function generate_csv() {
		@set_time_limit( 0 );
		global $wp_query;
		if ( isset( $_REQUEST['amapress_export'] ) && isset( $_REQUEST['_wpnonce-amapress-export-users-users-page_export'] ) ) {
			check_admin_referer( 'amapress-export-users-users-page_export', '_wpnonce-amapress-export-users-users-page_export' );

			$args = array(
				'fields'         => 'all_with_meta',
				'posts_per_page' => - 1,
			);
			$args = wp_parse_args( $args, wp_parse_args( $_SERVER['QUERY_STRING'] ) );

			$users = get_users( $args );

			if ( ! $users ) {
				$referer = add_query_arg( 'error', 'empty', wp_get_referer() );
				wp_redirect( remove_query_arg( 'amapress_export', $referer ) );
				exit;
			}

			$name     = isset( $_REQUEST['amapress_export_name'] ) ? isset( $_REQUEST['amapress_export_name'] ) : 'users';
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) ) {
				$sitename .= '.';
			}
			$filename = $sitename . $name . '.' . date( 'Y-m-d-H-i-s' );

			$exclude_data = apply_filters( 'amapress_users_export_exclude_data', array(
				'user_pass',
				'user_activation_key'
			) );

			global $wpdb;

			$data_keys = array(
				'ID',
				'user_login',
				'user_pass',
				'user_nicename',
				'user_email',
				'first_name',
				'last_name',
				'user_url',
				'user_registered',
				'user_activation_key',
				'user_status',
				'display_name',
				'roles'
			);
			$meta_keys = $wpdb->get_results( "SELECT distinct(meta_key) FROM $wpdb->usermeta" );
			$meta_keys = wp_list_pluck( $meta_keys, 'meta_key' );
			$fields    = array_merge( $data_keys, $meta_keys );
			$fields    = apply_filters( "amapress_users_export_fields", $fields, $name );
			$fields    = apply_filters( "amapress_{$name}_export_fields", $fields, $name );

			$csv_data = array();
			$headers  = array();
			foreach ( $fields as $key => $field ) {
				if ( in_array( $field, $exclude_data ) ) {
					unset( $fields[ $key ] );
				} else {
					$header = apply_filters( "amapress_users_get_field_display_name", $field );
					$header = wp_specialchars_decode( $header );
					$header = html_entity_decode( $header );
					if ( empty( $header ) ) {
						unset( $fields[ $key ] );
						continue;
					}
					$headers[] = $header;
				}
			}
			$csv_data[] = $headers;
//            var_dump($csv_data);
//            die();

			foreach ( $users as $user ) {
				$data = array();
				foreach ( $fields as $field ) {
					$value  = isset( $user->{$field} ) ? $user->{$field} : '';
					$value  = apply_filters( 'amapress_users_export_prepare_value', $value, $field, $user );
					$data[] = is_array( $value ) ? serialize( $value ) : $value;
				}
				$csv_data[] = $data;
//                if (count($csv_data) > 10) {
//                    var_dump($csv_data);
//                    die();
//                }
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
			Amapress::outputExcel( $objWriter );
			exit;
		}
	}
}
