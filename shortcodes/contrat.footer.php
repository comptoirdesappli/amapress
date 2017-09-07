<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_contrat_footer_shortcode( $atts, $content = null ) {
	return '<div class="contrat-footer">' . do_shortcode( $content ) . '</div>';
}