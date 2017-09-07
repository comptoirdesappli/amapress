<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_contrat_header_shortcode( $atts, $content = null ) {
	return '<div class="contrat-header">' . do_shortcode( $content ) . '</div>';
}