<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $all_amapress_shortcodes;
/**
 * @param string $tag
 * @param callable $func
 */
function amapress_register_shortcode( $tag, $func ) {
	global $all_amapress_shortcodes;
	if ( empty( $all_amapress_shortcodes ) ) {
		$all_amapress_shortcodes = [];
	}
	$all_amapress_shortcodes[ $tag ] = $func;
	add_shortcode( $tag, $func );
}