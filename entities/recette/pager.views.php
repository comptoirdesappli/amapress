<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_gallery_render_simple_recette_cell', 'amapress_simple_recette_cell' );
function amapress_simple_recette_cell( $recette, $add_class = '' ) {
	$recette_id = $recette->ID;
	$url        = amapress_get_avatar_url( $recette_id, null, 'produit-thumb', 'default_recette.jpg' );
	$prod_photo = '<img src="' . $url . '" alt="Photo de ' . esc_attr( $recette->post_title ) . '" />';

	$tags = get_the_term_list(
		$recette_id, AmapressRecette::CATEGORY,
		'<span class="label label-default">',
		', ',
		'</span>' );

	return '<div class="recette-cell ' . $add_class . '">
                    <div class="thumbnail">
                        ' . $prod_photo . '
                        <div class="caption">
                            <h3><a href="' . get_post_permalink( $recette->ID ) . '">' . $recette->post_title . '</a></h3>
                            ' . ( ! empty( $tags ) ? '<p class="recette-tags">' . $tags . '</p>' : '' ) . '
                        </div>
                    </div>
                </div>';
}