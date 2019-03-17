<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $all_amapress_shortcodes;
global $all_amapress_shortcodes_descs;
/**
 * @param string $tag
 * @param callable $func
 * @param array $desc
 */
function amapress_register_shortcode( $tag, $func, $desc = [] ) {
	global $all_amapress_shortcodes;
	if ( empty( $all_amapress_shortcodes ) ) {
		$all_amapress_shortcodes = [];
	}
	$all_amapress_shortcodes[ $tag ] = $func;

	global $all_amapress_shortcodes_descs;
	if ( empty( $all_amapress_shortcodes_descs ) ) {
		$all_amapress_shortcodes_descs = [];
	}
	$all_amapress_shortcodes_descs[ $tag ] = $desc;

	add_shortcode( $tag, $func );
}