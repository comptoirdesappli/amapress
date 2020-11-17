<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_bulk_action_amp_cnt_pmt_mark_recv', 'amapress_bulk_action_amp_cnt_pmt_mark_recv', 10, 2 );
function amapress_bulk_action_amp_cnt_pmt_mark_recv( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAmapien_paiement::getBy( $post_id, true );
		$adh->setStatus( AmapressAmapien_paiement::RECEIVED );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_action( 'amapress_row_action_contrat_paiement_mark_rcv', 'amapress_row_action_contrat_paiement_mark_rcv' );
function amapress_row_action_contrat_paiement_mark_rcv( $post_id ) {
	$adh = AmapressAmapien_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAmapien_paiement::RECEIVED );

	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_filter( 'amapress_bulk_action_amp_cnt_pmt_mark_bank', 'amapress_bulk_action_amp_cnt_pmt_mark_bank', 10, 2 );
function amapress_bulk_action_amp_cnt_pmt_mark_bank( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAmapien_paiement::getBy( $post_id, true );
		$adh->setStatus( AmapressAmapien_paiement::BANK );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_action( 'amapress_row_action_contrat_paiement_mark_bank', 'amapress_row_action_contrat_paiement_mark_bank' );
function amapress_row_action_contrat_paiement_mark_bank( $post_id ) {
	$adh = AmapressAmapien_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAmapien_paiement::BANK );

	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_contrat_paiement_unmark_rcv', 'amapress_row_action_contrat_paiement_unmark_rcv' );
function amapress_row_action_contrat_paiement_unmark_rcv( $post_id ) {
	$adh = AmapressAmapien_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->setStatus( AmapressAmapien_paiement::NOT_RECEIVED );
	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_filter( 'amapress_bulk_action_amp_cnt_pmt_send_notrcv_recall', 'amapress_bulk_action_amp_cnt_pmt_send_notrcv_recall', 10, 2 );
function amapress_bulk_action_amp_cnt_pmt_send_notrcv_recall( $sendback, $post_ids ) {
	$cnt = 0;
	foreach ( $post_ids as $post_id ) {
		$adh = AmapressAmapien_paiement::getBy( $post_id, true );
		if ( $adh->sendAwaitingRecall() ) {
			$cnt ++;
		}
	}

	return amapress_add_bulk_count( $sendback, $cnt );
}

add_action( 'amapress_row_action_contrat_paiement_send_notrcv_recall', 'amapress_row_action_contrat_paiement_send_notrcv_recall' );
function amapress_row_action_contrat_paiement_send_notrcv_recall( $post_id ) {
	$adh = AmapressAmapien_paiement::getBy( $post_id, true );
	if ( $adh ) {
		$adh->sendAwaitingRecall();
	}

	wp_redirect_and_exit( wp_get_referer() );
}