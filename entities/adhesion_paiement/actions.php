<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_bulk_action_amp_adh_pmt_mark_recv', 'amapress_bulk_action_amp_adh_pmt_mark_recv', 10, 2 );
function amapress_bulk_action_amp_adh_pmt_mark_recv( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
		$adh->setStatus( AmapressAdhesion_paiement::RECEIVED );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_action( 'amapress_row_action_adhesion_paiement_mark_rcv', 'amapress_row_action_adhesion_paiement_mark_rcv' );
function amapress_row_action_adhesion_paiement_mark_rcv( $post_id ) {
	$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAdhesion_paiement::RECEIVED );

	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_paiement_unmark_rcv', 'amapress_row_action_adhesion_paiement_unmark_rcv' );
function amapress_row_action_adhesion_paiement_unmark_rcv( $post_id ) {
	$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAdhesion_paiement::NOT_RECEIVED );
	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_paiement_mark_rcv_valid', 'amapress_row_action_adhesion_paiement_mark_rcv_valid' );
function amapress_row_action_adhesion_paiement_mark_rcv_valid( $post_id ) {
	$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAdhesion_paiement::RECEIVED );
		$adh->sendValidation();
	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_paiement_send_valid', 'amapress_row_action_adhesion_paiement_send_valid' );
function amapress_row_action_adhesion_paiement_send_valid( $post_id ) {
	$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->sendValidation();
	}

	wp_redirect_and_exit( wp_get_referer() );
}