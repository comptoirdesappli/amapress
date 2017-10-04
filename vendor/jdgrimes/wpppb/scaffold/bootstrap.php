<?php

/**
 * PHPUnit tests bootstrap.
 */

/**
 * The Composer-generated autoloader.
 */
require_once( dirname( __FILE__ ) . '/../../../vendor/autoload.php' );

$loader = WPPPB_Loader::instance();
$loader->add_plugin( 'my-plugin/my-plugin.php' );
$loader->load_wordpress();

// EOF
