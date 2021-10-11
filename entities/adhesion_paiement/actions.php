<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_bulk_action_amp_adh_pmt_mark_recv', 'amapress_bulk_action_amp_adh_pmt_mark_recv', 10, 2 );
function amapress_bulk_action_amp_adh_pmt_mark_recv( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
		$adh->setStatus( AmapressAdhesion_paiement::RECEIVED );
		delete_user_meta( $adh->getUserId(), 'pw_user_status' );
		delete_transient( 'new_user_approve_user_statuses' );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

function amapress_bulk_action_amp_adh_pmt_check_uncheck( $sendback, $post_ids, $num, $value ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
		$adh->setCustomCheck( $num, $value );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_filter( 'amapress_bulk_action_amp_adh_pmt_check_1', function ( $sendback, $post_ids ) {
	return amapress_bulk_action_amp_adh_pmt_check_uncheck( $sendback, $post_ids, 1, 1 );
}, 10, 2 );
add_filter( 'amapress_bulk_action_amp_adh_pmt_uncheck_1', function ( $sendback, $post_ids ) {
	return amapress_bulk_action_amp_adh_pmt_check_uncheck( $sendback, $post_ids, 1, 0 );
}, 10, 2 );
add_filter( 'amapress_bulk_action_amp_adh_pmt_check_2', function ( $sendback, $post_ids ) {
	return amapress_bulk_action_amp_adh_pmt_check_uncheck( $sendback, $post_ids, 2, 1 );
}, 10, 2 );
add_filter( 'amapress_bulk_action_amp_adh_pmt_uncheck_2', function ( $sendback, $post_ids ) {
	return amapress_bulk_action_amp_adh_pmt_check_uncheck( $sendback, $post_ids, 2, 0 );
}, 10, 2 );
add_filter( 'amapress_bulk_action_amp_adh_pmt_check_3', function ( $sendback, $post_ids ) {
	return amapress_bulk_action_amp_adh_pmt_check_uncheck( $sendback, $post_ids, 3, 1 );
}, 10, 2 );
add_filter( 'amapress_bulk_action_amp_adh_pmt_uncheck_3', function ( $sendback, $post_ids ) {
	return amapress_bulk_action_amp_adh_pmt_check_uncheck( $sendback, $post_ids, 3, 0 );
}, 10, 2 );

add_action( 'amapress_row_action_adhesion_paiement_mark_rcv', 'amapress_row_action_adhesion_paiement_mark_rcv' );
function amapress_row_action_adhesion_paiement_mark_rcv( $post_id ) {
	$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAdhesion_paiement::RECEIVED );
		delete_user_meta( $adh->getUserId(), 'pw_user_status' );
		delete_transient( 'new_user_approve_user_statuses' );
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
		delete_user_meta( $adh->getUserId(), 'pw_user_status' );
		delete_transient( 'new_user_approve_user_statuses' );
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

add_filter( 'amapress_bulk_action_amp_adh_pmt_send_valid', 'amapress_bulk_action_amp_adh_pmt_send_valid', 10, 2 );
function amapress_bulk_action_amp_adh_pmt_send_valid( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
		if ( $adh ) {
			$adh->sendValidation();
		}
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_filter( 'amapress_bulk_action_amp_adh_pmt_mark_recv_valid', 'amapress_bulk_action_amp_adh_pmt_mark_recv_valid', 10, 2 );
function amapress_bulk_action_amp_adh_pmt_mark_recv_valid( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAdhesion_paiement::getBy( $post_id, true );
		$adh->setStatus( AmapressAdhesion_paiement::RECEIVED );
		delete_user_meta( $adh->getUserId(), 'pw_user_status' );
		delete_transient( 'new_user_approve_user_statuses' );
		$adh->sendValidation();
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}