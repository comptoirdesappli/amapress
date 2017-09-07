<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_do_query_action_assemblee_generale_participer', 'amapress_do_query_action_assemblee_generale_participer' );
function amapress_do_query_action_assemblee_generale_participer() {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	$assemblee_id = get_the_ID();
	$redir_url    = get_post_permalink( $assemblee_id );
	$participants = unserialize( get_post_meta( $assemblee_id, 'amapress_assemblee_generale_participants', true ) );
	if ( ! $participants ) {
		$participants = array();
	}
	if ( in_array( amapress_current_user_id(), $participants ) ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'already_in_list' ), $redir_url ) );
	} else {
		$participants[] = amapress_current_user_id();
		update_post_meta( $assemblee_id, 'amapress_assemblee_generale_participants', $participants );

		amapress_mail_current_user_inscr( new AmapressAssemblee_generale( $assemblee_id ) );

		wp_redirect_and_exit( add_query_arg( array( 'message' => 'success' ), $redir_url ) );
	}
}