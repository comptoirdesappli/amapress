<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @param string $tag
 * @param callable $func
 */
function amapress_register_shortcode( $tag, $func ) {
	add_shortcode( $tag, $func );
}