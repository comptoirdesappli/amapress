<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_contrat_title_shortcode( $atts, $content = null ) {
	return '<h2 class="contrat-title">' . do_shortcode( $content ) . '</h2>';
}