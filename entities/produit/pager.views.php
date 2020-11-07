<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_gallery_sort_produit_panier_cell', 'amapress_gallery_sort_produit_panier_cell', 10, 2 );
function amapress_gallery_sort_produit_panier_cell( $sort, $produit ) {
	$produit_id = $produit['produit']->ID;
	$prod       = get_post( $produit_id );

	return strtolower( $prod->post_title );
}

add_filter( 'amapress_gallery_category_produit_panier_cell', 'amapress_gallery_category_produit_panier_cell', 10, 2 );
function amapress_gallery_category_produit_panier_cell( $category, $produit ) {
	$tags = get_the_terms(
		$produit['produit']->ID,
		AmapressProduit::CATEGORY );

	return 'produit-cell ' . ( $tags ? implode( ' ', array_map( function ( $t ) {
			/** @var WP_Term $t */
			return sanitize_html_class( $t->name );
		}, $tags ) ) : '' );
}

add_filter( 'amapress_gallery_render_produit_panier_cell', 'amapress_gallery_render_produit_panier_cell' );
function amapress_gallery_render_produit_panier_cell( $produit, $add_class = null ) {
	$produit_id = $produit['produit']->ID;
	$url        = amapress_get_avatar_url( $produit_id, null, 'produit-thumb', 'default_produit.jpg' );
	$prod       = get_post( $produit_id );
	$prod_photo = '';
	$prod_photo = '<img src="' . $url . '" alt="' . esc_attr( $prod->post_title ) . '" />';

	return '<div class="thumbnail">
                        ' . $prod_photo . '
                        <div class="caption">
                            <h3><a href="' . get_post_permalink( $prod->ID ) . '">' . $prod->post_title . '</a></h3>
                            <p>' . $produit['quantite'] . ' ' . $produit['unit'] . '</p>
                        </div>
                    </div>';
}

add_filter( 'amapress_gallery_sort_produit_simple_cell', 'amapress_gallery_sort_produit_simple_cell', 10, 2 );
function amapress_gallery_sort_produit_simple_cell( $sort, $produit ) {
	$produit_id = $produit['produit']->ID;
	$prod       = get_post( $produit_id );

	return strtolower( $prod->post_title );
}

add_filter( 'amapress_gallery_category_produit_simple_cell', 'amapress_gallery_category_produit_simple_cell', 10, 2 );
function amapress_gallery_category_produit_simple_cell( $category, $produit ) {
	$tags = get_the_terms(
		$produit['produit']->ID,
		AmapressProduit::CATEGORY );

	return 'produit-cell ' . ( $tags ? implode( ' ', array_map( function ( $t ) {
			/** @var WP_Term $t */
			return sanitize_html_class( $t->name );
		}, $tags ) ) : '' );
}

add_filter( 'amapress_gallery_render_simple_produit_cell', 'amapress_simple_produit_cell', 10, 2 );
function amapress_simple_produit_cell( $produit, $add_class = '' ) {
	$produit_id = $produit->ID;
	$url        = amapress_get_avatar_url( $produit_id, null, 'produit-thumb', 'default_produit.jpg' );
	$prod       = get_post( $produit_id );
	$prod_photo = '<img src="' . $url . '" alt="' . esc_attr( $prod->post_title ) . '" />';

	return '<div class="thumbnail">
                        ' . $prod_photo . '
                        <div class="caption">
                            <h3><a href="' . get_post_permalink( $prod->ID ) . '">' . $prod->post_title . '</a></h3>
                        </div>
                    </div>';
}