<?php

/**
 * Simulate plugin usage.
 *
 * @package WPPPB
 * @since 0.1.0
 */

$plugin_file      = $argv[1];
$simulation_file  = $argv[2];
$config_file_path = $argv[3];
$is_multisite     = $argv[4];
$network_wide     = $argv[5];

/**
 * The bootstrap for loading WordPress.
 *
 * @since 0.1.0
 */
require dirname( __FILE__ ) . '/bootstrap.php';

/**
 * Load the WP unit test factories.
 *
 * Use the $wp_test_factory global to create users, posts, etc., the same way that
 * you use the $factory property in WP unit test case classes.
 *
 * @since 0.1.0
 */
require_once getenv( 'WP_TESTS_DIR' ) . '/includes/factory.php';

$GLOBALS['wp_test_factory'] = new WP_UnitTest_Factory;

/**
 * The plugin's simulation file.
 *
 * @since 0.1.0
 */
require $simulation_file;

/**
 * Load the WordPress plugin functions.
 * 
 * These are usually only loaded in the admin, so we need to load them manually here.
 * 
 * @since 0.2.3
 */
require_once ABSPATH . '/wp-admin/includes/plugin.php';

// Deactivate the plugin.
deactivate_plugins( $plugin_file, false, $network_wide );

// EOF
