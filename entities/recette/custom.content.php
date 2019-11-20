<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_get_custom_content_recette', 'amapress_get_custom_content_recette' );
function amapress_get_custom_content_recette( $content ) {
	if ( is_search() ) {
		return $content;
//		return amapress_get_custom_archive_content_recette( $content );
	}

	ob_start();

//	echo $content;

	$recette_id = get_the_ID();
//    $url = amapress_get_avatar_url($recette_id, null, 'produit-thumb', 'default_recette.jpg');
//    $cnt = '<div class="recette-photo"><img src="' . $url . '" alt="Photo de ' . esc_attr(get_the_title()) . '" /></div>' . $content;

//    echo '<div class="row">';
//    echo '<div class="col-md-4 col-sm-12">';
//    echo $cnt;
//    echo '</div>';
//    echo '<div class="col-md-8 col-sm-12">';
//	amapress_tabs_model_echo( 'recette_models', $recette_id, 'amapress_recette_content' );
	if ( amapress_is_user_logged_in() ) {
		echo '<div class="recette-auteur"><a href="mailto:' . esc_attr( get_the_author_meta( 'email' ) ) . '">' . esc_html( get_the_author() ) . '</a></div>';
	} else {
		echo '<div class="recette-auteur">' . esc_html( get_the_author() ) . '</div>';
	}
//    echo '</div>';
//    echo '</div>';


	echo '<h3>Produits</h3>';
	echo amapress_produits_shortcode(
		[ 'recette' => $recette_id ]
	);

	$content .= ob_get_clean();

	return $content;
}

//add_filter( 'amapress_get_custom_archive_excerpt_recette', 'amapress_get_custom_archive_content_recette' );
//add_filter( 'amapress_get_custom_archive_content_recette', 'amapress_get_custom_archive_content_recette' );
//function amapress_get_custom_archive_content_recette( $content ) {
//	ob_start();
//
//	echo $content;
//
//	$recette_id = get_the_ID();
//	amapress_tabs_model_echo( 'recette_models', $recette_id, 'amapress_recette_content', true );
//
//	$content = ob_get_clean();
//
//	return strip_tags( $content, '<a><b><strong><i><em><br>' );
//}