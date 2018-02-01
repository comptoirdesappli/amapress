<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_postits_shortcode( $args ) {
	$post_its = apply_filters( 'amapress_register_post-its', array() );
	usort( $post_its, function ( $a, $b ) {
		$da = isset( $a['date'] ) ? $a['date'] : 0;
		$db = isset( $b['date'] ) ? $b['date'] : 0;
		if ( $da == $db ) {
			return 0;
		}

		return $da < $db ? - 1 : 1;
	} );

	$ret = '<div class="post-its">';
	foreach ( $post_its as $post_it ) {
		$ret .= '<div class="post-it ' . esc_attr( isset( $post_it['type'] ) ? 'post-it-' . $post_it['type'] : '' ) . '">';
		$ret .= '<h4>' . ( isset( $post_it['title_html'] ) ? $post_it['title_html'] : esc_html( $post_it['title'] ) ) . '</h4>';
		$ret .= '<div class="post-it-content">';
		$ret .= $post_it['content'];
		$ret .= '</div>';
		$ret .= '</div>';
	}
	$ret .= '</div>';

	return $ret;
}