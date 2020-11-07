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
		echo '<p class="error">' . 'Non autorisé' . '</p>';
		die();
	}


	$visite = new AmapressVisite( $visite_id );
	switch ( $visite->manageSlot( $user_id, $slot, false ) ) {
		case 'not_inscr':
			echo '<p class="error">' . 'Vous n\'aviez pas choisi de créneau' . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . 'Désaffectation du créneau prise en compte' . '</p>';
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
		echo '<p class="error">' . 'Non autorisé' . '</p>';
		die();
	}

	$visite = new AmapressVisite( $visite_id );
	switch ( $visite->manageSlot( $user_id, $slot, true ) ) {
		case 'already_in_list':
			echo '<p class="error">' . 'Vous avez déjà choisi un créneau' . '</p>';
			break;
		case 'full':
			echo '<p class="error">' . 'Ce créneau est complet' . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . 'Choix du créneau pris en compte' . '</p>';
			break;
	}
	die();
} );