<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_get_custom_content_recette', 'amapress_get_custom_content_recette' );
function amapress_get_custom_content_recette( $content ) {
	if ( is_search() ) {
		return $content;
	}

	ob_start();

	$recette_id = get_the_ID();
	if ( amapress_is_user_logged_in() ) {
		echo '<div class="recette-auteur"><a href="mailto:' . esc_attr( get_the_author_meta( 'email' ) ) . '">' . esc_html( get_the_author() ) . '</a></div>';
	} else {
		echo '<div class="recette-auteur">' . esc_html( get_the_author() ) . '</div>';
	}


	echo '<h3>' . __( 'Produits', 'amapress' ) . '</h3>';
	echo amapress_produits_shortcode(
		[ 'recette' => $recette_id ]
	);

	$content .= ob_get_clean();

	return $content;
}