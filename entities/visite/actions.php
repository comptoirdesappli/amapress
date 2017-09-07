<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_do_query_action_visite_participer', 'amapress_do_query_action_visite_participer' );
function amapress_do_query_action_visite_participer() {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	$visite_id    = get_the_ID();
	$redir_url    = get_post_permalink( $visite_id );
	$participants = unserialize( get_post_meta( $visite_id, 'amapress_visite_participants', true ) );
	if ( ! $participants ) {
		$participants = array();
	}
	if ( in_array( amapress_current_user_id(), $participants ) ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'already_in_list' ), $redir_url ) );
	} else {
		$participants[] = amapress_current_user_id();
		update_post_meta( $visite_id, 'amapress_visite_participants', $participants );

		amapress_mail_current_user_inscr( new AmapressVisite( $visite_id ) );

		wp_redirect_and_exit( add_query_arg( array( 'message' => 'success' ), $redir_url ) );
	}
}