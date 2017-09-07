<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function amapress_produits_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'columns'        => 4,
		'producteur'     => '',
		'recette'        => '',
		'render_func'    => 'simple_produit_cell',
		'cat'            => '',
		'cat__not_in'    => '',
		'if_empty'       => urlencode( 'Pas encore de produits' ),
		'posts_per_page' => 12,
		'order'          => 'ASC',
		'orderby'        => 'title',
		'id'             => 0,
		'size'           => 'thumbnail',
	), $atts );

	$producteur  = $atts['producteur'];
	$recette     = $atts['recette'];
	$cat         = $atts['cat'];
	$cat__not_in = $atts['cat__not_in'];

//    function map_produit($v) {
//        return Amapress::resolve_post_id($v,'produit');
//    }
	$query_uri = array();
	if ( ! empty( $producteur ) ) {
		$query_uri[] = 'amapress_producteur=' . $producteur;
	}
	if ( ! empty( $recette ) ) {
		$query_uri[] = 'amapress_produit_recette=' . $recette;
	}
	if ( ! empty( $cat ) ) {
		$query_uri[] = 'amapress_produit_tag=' . $cat;
	}
	if ( ! empty( $cat__not_in ) ) {
		$query_uri[] = 'amapress_produit_tag_not_in=' . $cat__not_in;
	}

	unset( $atts['producteur'] );
	unset( $atts['recette'] );
	unset( $atts['cat'] );
	unset( $atts['cat__not_in'] );


	$other_params    = implode( ' ', array_map( function ( $k, $v ) {
		return $k . '=' . $v;
	}, array_keys( $atts ), array_values( $atts ) ) );
	$query_uri_param = urlencode( implode( '&', $query_uri ) );

	return do_shortcode( "[paged_gallery post_type=amps_produit query_uri=$query_uri_param $other_params]" );
}