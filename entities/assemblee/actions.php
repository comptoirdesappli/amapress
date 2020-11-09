<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'wp_ajax_desinscrire_assemblee_action', function () {
	$event_id   = intval( $_POST['event'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! amapress_can_access_admin() ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}

	$event = AmapressAssemblee_generale::getBy( $event_id );
	switch ( $event->desinscrireParticipant( $user_id ) ) {
		case 'not_inscr':
			echo '<p class="error">' . __( 'Non inscrit', 'amapress' ) . '</p>';
			break;
		case true:
			echo '<p class="success">' . __( 'Désinscription a bien été prise en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );
add_action( 'wp_ajax_inscrire_assemblee_action', function () {
	$event_id   = intval( $_POST['event'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! amapress_can_access_admin() ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}

	$event = AmapressAssemblee_generale::getBy( $event_id );
	switch ( $event->inscrireParticipant( $user_id ) ) {
		case 'already_in_list':
			echo '<p class="error">' . __( 'Déjà inscrit', 'amapress' ) . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . __( 'Inscription a bien été prise en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );
