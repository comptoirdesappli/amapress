<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function amapress_lieu_map_shortcode( $atts ) {
	global $post;
	static $amapress_map_instance = 0;
	$amapress_map_instance ++;

	$atts    = shortcode_atts( array(
		'lieu' => $post,
		'mode' => 'map',
	), $atts );
	$lieu_id = Amapress::resolve_post_id( $atts['lieu'], 'amps_lieu' );
	if ( ! $lieu_id ) {
		return '';
	}

	$lieu = new AmapressLieu_distribution( $lieu_id );

	$markers = array();
	if ( ! $lieu->isAdresseLocalized() ) {
		return '';
	}
	$m = array(
		'longitude' => $lieu->getAdresseLongitude(),
		'latitude'  => $lieu->getAdresseLatitude(),
		'url'       => $lieu->getPermalink(),
		'icon'      => 'lieu',
		'title'     => $lieu->getShortName(),
		'content'   => '<p>' . esc_html( $lieu->getTitle() ) . '</p><p>' . $lieu->getFormattedAdresseHtml() . '</p>',
	);
	if ( $lieu->isAdresseAccesLocalized() ) {
		$m['access'] = array(
			'longitude' => $lieu->getAdresseAccesLongitude(),
			'latitude'  => $lieu->getAdresseAccesLatitude(),
		);
	}
	$markers[] = $m;

	return amapress_generate_map( $markers, $atts['mode'] );
}

function amapress_where_to_find_us_shortcode( $attr ) {
	$lieux = Amapress::get_lieux();
	if ( empty( $lieux ) ) {
		return '<p class="">Aucun lieux configuré</p>';
	}
	$markers = array();
	foreach ( $lieux as $lieu ) {
		if ( ! $lieu->isAdresseLocalized() ) {
			continue;
		}
		$m = array(
			'longitude' => $lieu->getAdresseLongitude(),
			'latitude'  => $lieu->getAdresseLatitude(),
			'url'       => $lieu->getPermalink(),
//            'icon' => 'lieu',
			'title'     => $lieu->getShortName(),
			'content'   => '<p>' . $lieu->getFormattedAdresseHtml() . '</p>', //'<p>'.esc_html($lieu->getTitle()).'</p>
		);
		if ( $lieu->isAdresseAccesLocalized() ) {
			$m['access'] = array(
				'longitude' => $lieu->getAdresseAccesLongitude(),
				'latitude'  => $lieu->getAdresseAccesLatitude(),
			);
		}
		$markers[] = $m;
	}

	return amapress_generate_map( $markers, 'map' );

}