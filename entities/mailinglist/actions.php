<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_init', 'amapress_sync_all_list' );
function amapress_sync_all_list() {
	if ( isset( $_GET['sync_all'] ) ) {
		amapress_mailinglists_autosync();
		wp_redirect_and_exit( remove_query_arg( 'sync_all' ) );
	}
}
