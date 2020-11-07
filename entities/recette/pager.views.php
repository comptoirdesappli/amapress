<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_gallery_render_simple_recette_cell', 'amapress_simple_recette_cell' );
function amapress_simple_recette_cell( $recette, $add_class = '' ) {
	$recette_id = $recette->ID;
	$url        = amapress_get_avatar_url( $recette_id, null, 'produit-thumb', 'default_recette.jpg' );
	$prod_photo = '<img src="' . $url . '" alt="' . esc_attr( $recette->post_title ) . '" />';

	$tags = get_the_term_list(
		$recette_id, AmapressRecette::CATEGORY,
		'<span class="label label-default">',
		', ',
		'</span>' );

	return '<div class="thumbnail">
                        ' . $prod_photo . '
                        <div class="caption">
                            <h3><a href="' . get_post_permalink( $recette->ID ) . '">' . $recette->post_title . '</a></h3>
                            ' . ( ! empty( $tags ) ? '<p class="recette-tags">' . $tags . '</p>' : '' ) . '
                        </div>
                    </div>';
}


add_filter( 'amapress_gallery_sort_simple_recette_cell', 'amapress_gallery_sort_simple_recette_cell', 10, 2 );
function amapress_gallery_sort_simple_recette_cell( $sort, $recette ) {
	return strtolower( $recette->post_title );
}


add_filter( 'amapress_gallery_category_simple_recette_cell', 'amapress_gallery_category_simple_recette_cell', 10, 2 );
function amapress_gallery_category_simple_recette_cell( $categories, $recette ) {
	$tags = get_the_terms(
		$recette->ID,
		AmapressRecette::CATEGORY );

	return 'recette-cell ' . ( $tags ? implode( ' ', array_map( function ( $t ) {
			/** @var WP_Term $t */
			return sanitize_html_class( $t->name );
		}, $tags ) ) : '' );
}