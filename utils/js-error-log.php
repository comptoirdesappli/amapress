<?php

/**
 * JavaScript Error Log
 * Derived from JavaScript Error Log
 * <https://github.com/octalmage/js-error-log>
 *
 * @author    Jason Stallings (https://jason.stallin.gs)
 * @license   GPL-3.0+
 * @link      https://github.com/octalmage/js-error-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Amapress_JS_Error_Log {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'wp_ajax_js_log_error', array( $this, 'js_log_error' ) );
		add_action( 'wp_ajax_nopriv_js_log_error', array( $this, 'js_log_error' ) );
	}

	public function enqueue_script() {
		wp_enqueue_script(
			'js-error-log',
			plugins_url( 'js/js-error-log.js', AMAPRESS__PLUGIN_FILE )
		);
		wp_localize_script( 'js-error-log', 'js_error_log',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function js_log_error() {
		if ( isset( $_REQUEST['msg'] ) && isset( $_REQUEST['line'] ) && isset( $_REQUEST['url'] ) ) {
			if ( empty( $_REQUEST['referer'] ) ) {
				$_REQUEST['referer'] = '';
			}
			$error = filter_input_array( INPUT_POST, array(
				'msg'     => FILTER_SANITIZE_STRING,
				'url'     => FILTER_SANITIZE_STRING,
				'line'    => FILTER_SANITIZE_STRING,
				'referer' => FILTER_SANITIZE_STRING,
			) );

			error_log(
				'JavaScript Error: ' .
				html_entity_decode( $error['msg'], ENT_QUOTES ) .
				', file: ' . $error['url'] . ':' . $error['line'] .
				', agent: ' . $_SERVER['HTTP_USER_AGENT'] .
				', user: ' . get_current_user_id() .
				', referer: ' . $_REQUEST['referer'] );
			wp_send_json( $error );
		}
		wp_die();
	}
}

if ( 0 == strcasecmp( 'On', ini_get( 'log_errors' ) ) ) {
	if ( ! defined( 'AMAPRESS_DISABLE_JS_ERROR_LOG' ) ) {
		new Amapress_JS_Error_Log();
	}
}



