<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_page' );
function amapress_register_entities_page( $entities ) {
	$entities['page'] = array(
		'internal_name'   => 'page',
		'default_orderby' => 'post_title',
		'default_order'   => 'ASC',
		'public_comments' => true,
		'fields'          => array(
//            'logged_only' => array(
//                'name' => amapress__('Amapiens seulement'),
//                'type' => 'checkbox',
//                'group' => __('Amapress Page Protection', 'amapress'),
//                'desc' => __('Amapiens seulement', 'amapress'),
//            ),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_page', 'amapress_can_delete_page', 10, 2 );
function amapress_can_delete_page( $can, $post_id ) {
	$reserved_pages = array(
		intval( Amapress::getOption( 'mes-infos-page' ) ),
		intval( Amapress::getOption( 'paniers-intermittents-page' ) ),
		intval( Amapress::getOption( 'mes-paniers-intermittents-page' ) ),
	);

	return ! in_array( $post_id, $reserved_pages );
}