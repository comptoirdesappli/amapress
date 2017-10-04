<?php

/**
 * Install a plugin remotely.
 *
 * @package WPPPB
 * @since 0.1.0
 */

$plugins_info     = json_decode( $argv[1], true );
$config_file_path = $argv[2];
$is_multisite     = (bool) $argv[3];
$custom_files     = json_decode( $argv[4], true );

/**
 * The bootstrap file for loading WordPress.
 *
 * @since 0.1.0
 */
require dirname( __FILE__ ) . '/bootstrap.php';

// Clean out the database.
global $wpdb;

// But we'll leave the core tables untouched.
$core_tables = array_fill_keys( $wpdb->tables(), 1 );

if ( is_multisite() ) {
	$site_ids = $wpdb->get_col(
		"SELECT `blog_id` FROM `{$wpdb->blogs}` WHERE `site_id` = {$wpdb->siteid}"
	);

	foreach ( $site_ids as $site_id ) {
		$core_tables += array_fill_keys( $wpdb->tables( 'all', true, $site_id ), 1 );
	}
}

// Just remove any lingering plugin tables.
foreach ( $wpdb->get_col( "SHOW TABLES LIKE '" . $wpdb->base_prefix . "%'" ) as $table ) {
	if ( ! isset( $core_tables[ $table ] ) ) {
		$wpdb->query( "DROP TABLE {$table}" );
	}
}

/**
 * The plugin API from WordPress.
 *
 * @since 0.1.0
 */
require_once ABSPATH . '/wp-admin/includes/plugin.php';

// Load files to include before the plugins are installed.
foreach ( $custom_files['before'] as $file => $data ) {
	require $file;
}

// Activate the plugins.
foreach ( $plugins_info as $plugin => $info ) {
	activate_plugin( $plugin, '', $info['network_wide'] );
}

// Load files to include after the plugins are installed.
foreach ( $custom_files['after'] as $file => $data ) {
	require $file;
}

// EOF
