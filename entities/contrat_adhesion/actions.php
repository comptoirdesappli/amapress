<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_bulk_action_amp_accept_contrat_adhesion', 'amapress_accept_contrat_adhesion', 10, 2 );
function amapress_accept_contrat_adhesion( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAdhesion::getBy( $post_id, true );
		$adh->setStatus( AmapressAdhesion::CONFIRMED );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_action( 'amapress_row_action_adhesion_accept', 'amapress_row_action_adhesion_accept' );
function amapress_row_action_adhesion_accept( $post_id ) {
	$adh = AmapressAdhesion::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAdhesion::CONFIRMED );
	}

	wp_redirect_and_exit( remove_query_arg( [
		'action',
		'amp_id',
		'_wpnonce'
	] ) );
}