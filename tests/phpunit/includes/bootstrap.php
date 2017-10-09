<?php

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $wp_tests_dir ) {
	echo( '$_ENV["WP_TESTS_DIR"] is not set.' . PHP_EOL );
	exit( 1 );
}
require_once $wp_tests_dir . '/includes/functions.php';

function _manually_load_environment() {
// Add your theme
	switch_theme( 'twentysixteen' );

// Update array with plugins to include ...
	$plugins_to_active = array(
		'amapress/amapress.php'
	);


	foreach ( $plugins_to_active as $plugin ) {
		require dirname( __FILE__ ) . '/../../../../' . $plugin;
	}

	update_option( 'active_plugins', $plugins_to_active );
}

tests_add_filter( 'muplugins_loaded', '_manually_load_environment' );

require $wp_tests_dir . '/includes/bootstrap.php';

require_once dirname( __FILE__ ) . '/class.amapress.unit.testcase.php';
