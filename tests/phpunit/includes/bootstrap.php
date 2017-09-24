<?php

/**
 * PHPUnit tests bootstrap.
 */

/**
 * The Composer-generated autoloader.
 */
require_once( dirname( __FILE__ ) . '/../../../vendor/autoload.php' );

$_ENV['WP_TESTS_DIR'] = '/tmp/wp2/wordpress-tests-lib';

$loader = WPPPB_Loader::instance();
$loader->add_plugin( 'amapress.php' );
$loader->load_wordpress();

// EOF
