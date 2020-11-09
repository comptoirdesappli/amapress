<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function get_amapress_recettes_gallery( $atts ) {
	$atts['query_var'] = 0;

	return amapress_recettes_shortcode( $atts );
}
function amapress_recettes_shortcode( $atts ) {
	amapress_ensure_no_cache();

	$atts = shortcode_atts( array(
//		'columns'        => 4,
		'produits'    => '',
		'query_var'   => 1,
		'cat'         => '',
		'cat__not_in' => '',
		'render_func' => 'simple_recette_cell',
//		'posts_per_page' => 12,
		'if_empty'    => __( 'Pas encore de recette', 'amapress' ),
//		'order'          => 'ASC',
//		'orderby'        => 'title',
//		'id'             => 0,
		'size'        => 'thumbnail',
		'searchbox'   => true,
	), $atts );

	$produits    = $atts['produits'];
	$cat         = $atts['cat'];
	$cat__not_in = $atts['cat__not_in'];

	$query_uri = array(
		'post_type=amps_recette',
		'posts_per_page=-1',
	);
	if ( intval( $atts['query_var'] ) ) {
		$v = get_query_var( 'amapress_recette_produits' );
		if ( ! empty( $v ) ) {
			$produits = $v;
		}
		$v = get_query_var( 'amapress_recette_tag' );
		if ( ! empty( $v ) ) {
			$cat = $v;
		}
		$v = get_query_var( 'amapress_recette_tag_not_in' );
		if ( ! empty( $v ) ) {
			$cat__not_in = $v;
		}
		$v = get_query_var( 's' );
		if ( ! empty( $v ) ) {
			$query_uri[] = 's=' . urlencode( $v );
		}
	}

//    function map_produit($v) {
//        return Amapress::resolve_post_id($v,'produit');
//    }
	if ( ! empty( $produits ) ) {
		$query_uri[] = 'amapress_recette_produits=' . $produits;
	}
	if ( ! empty( $cat ) ) {
		$query_uri[] = 'amapress_recette_tag=' . $cat;
	}
	if ( ! empty( $cat__not_in ) ) {
		$query_uri[] = 'amapress_recette_tag_not_in=' . $cat__not_in;
	}

//	unset( $atts['produits'] );
//	unset( $atts['cat'] );
//	unset( $atts['cat__not_in'] );

//	$other_params    = implode( ' ', array_map( 'amapress_recettes_shortcode_map_key', array_keys( $atts ), array_values( $atts ) ) );
	$query_uri_param = implode( '&', $query_uri );

//	return do_shortcode( "[paged_gallery post_type=amps_recette query_uri=$query_uri_param $other_params]" );
	return amapress_generic_gallery( get_posts( $query_uri_param ), $atts['render_func'],
		[
			'size'      => $atts['size'],
			'if_empty'  => $atts['if_empty'],
			'searchbox' => $atts['searchbox'],
		] );
}

//function amapress_recettes_shortcode_map_key( $k, $v ) {
//	return $k . '=' . $v;
//}