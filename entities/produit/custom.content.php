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
	$prod = new AmapressProduit( get_the_ID() );
	ob_start();
	$producteurs = $prod->getProducteurs();
	echo '<h3>' . _n( 'Producteur', 'Producteurs', count( $producteurs ), 'amapress' ) . '</h3>';
	foreach ( $producteurs as $producteur ) {
		if ( $producteur ) {
			echo '<p><a href="' . $producteur->getPermalink() . '">' . $producteur->getTitle() . '</a></p>';
		}
	}

	echo '<h3>Recettes</h3>';
	echo do_shortcode( "[recettes produits={$prod->ID}]" );

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