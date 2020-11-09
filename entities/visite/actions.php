<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_action( 'wp_ajax_visite_desinscrire_slot', function () {
	$visite_id  = intval( $_POST['visite'] );
	$slot       = strval( $_POST['slot'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! amapress_can_access_admin() ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}


	$visite = new AmapressVisite( $visite_id );
	switch ( $visite->manageSlot( $user_id, $slot, false ) ) {
		case 'not_inscr':
			echo '<p class="error">' . __( 'Vous n\'aviez pas choisi de créneau', 'amapress' ) . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . __( 'Désaffectation du créneau prise en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );
add_action( 'wp_ajax_visite_inscrire_slot', function () {
	$visite_id  = intval( $_POST['visite'] );
	$slot       = strval( $_POST['slot'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = amapress_current_user_id() == $user_id;
	if ( ! $is_current && ! amapress_can_access_admin() ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}

	$visite = new AmapressVisite( $visite_id );
	switch ( $visite->manageSlot( $user_id, $slot, true ) ) {
		case 'already_in_list':
			echo '<p class="error">' . __( 'Vous avez déjà choisi un créneau', 'amapress' ) . '</p>';
			break;
		case 'full':
			echo '<p class="error">' . __( 'Ce créneau est complet', 'amapress' ) . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . __( 'Choix du créneau pris en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );