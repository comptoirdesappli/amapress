<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
//
add_filter( 'amapress_get_custom_content_produit', 'amapress_get_custom_content_produit' );
function amapress_get_custom_content_produit( $content ) {
	if ( is_search() ) {
		return $content;
//		return amapress_get_custom_archive_content_produit( $content );
	}
//
	$prod_id = get_the_ID();
////    $url = amapress_get_avatar_url($prod_id, null, 'post-thumb', 'default_produit.jpg');
////    $cnt = '<div class="produit-photo"><img src="' . $url . '" alt="Photo de ' . esc_attr(get_the_title()) . '" /></div>' . $content;
//
	$producteur_id = get_post_meta( $prod_id, 'amapress_produit_producteur', true );
	if ( empty( $producteur_id ) ) {
		return $content;
	}

	ob_start();
//
//	echo $content;
////    echo '<div class="row">';
////    echo '<div class="col-md-4 col-sm-12">';
////    echo $cnt;
////    echo '</div>';
////    echo '<div class="col-md-8 col-sm-12">';
//	amapress_tabs_model_echo( 'produit_models', $prod_id, 'amapress_produit_content' );
////    echo '</div>';
////    echo '</div>';
//
	echo '<h3>' . Amapress::getOption( 'producteur_title', 'Producteur' ) . '</h3>';
	echo '<p><a href="' . get_post_permalink( $producteur_id ) . '">' . get_post( $producteur_id )->post_title . '</a></p>';

	echo '<h3>Recettes</h3>';
	echo do_shortcode( "[recettes produits={$prod_id}]" );

	$content .= ob_get_clean();

	return $content;
}
//
//add_filter( 'amapress_get_custom_archive_excerpt_produit', 'amapress_get_custom_archive_content_produit' );
//add_filter( 'amapress_get_custom_archive_content_produit', 'amapress_get_custom_archive_content_produit' );
//function amapress_get_custom_archive_content_produit( $content ) {
//	$prod_id = get_the_ID();
//
//	ob_start();
//
//	echo $content;
//	amapress_tabs_model_echo( 'produit_models', $prod_id, 'amapress_produit_content', true );
//
//	$content = ob_get_clean();
//
//	return strip_tags( $content, '<a><b><strong><i><em><br>' );
//}