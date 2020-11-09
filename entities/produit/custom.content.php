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
	echo '<h3>' . _n( __( 'Producteur', 'amapress' ), __( 'Producteurs', 'amapress' ), count( $producteurs ), 'amapress' ) . '</h3>';
	foreach ( $producteurs as $producteur ) {
		if ( $producteur && $producteur->getPost() ) {
			echo '<p><a href="' . $producteur->getPermalink() . '">' . $producteur->getTitle() . '</a></p>';
		}
	}

	echo '<h3>' . __( 'Recettes', 'amapress' ) . '</h3>';
	echo get_amapress_recettes_gallery(
		[
			'produits' => $prod->ID,
		]
	);

	$content .= ob_get_clean();

	return $content;
}