<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_handle_and_get_action_messages() {
	static $messages = array(
		'inscr_success'        => array(
			'content' => 'Votre participation a été enregistrée.',
			'type'    => 'success',
		),
		'desinscr_success'     => array(
			'content' => 'Votre participation a été desenregistrée.',
			'type'    => 'success',
		),
		'already_in_list'      => array(
			'content' => 'Vous êtes déjà dans la liste des participants.',
			'type'    => 'warn',
		),
		'already_taken'        => array(
			'content' => 'Rôle déjà pris',
			'type'    => 'error',
		),
		'list_full'            => array(
			'content' => 'La liste des participants est déjà complète.',
			'type'    => 'error',
		),
		'not_inscr'            => array(
			'content' => 'Vous n\'êtes pas inscrit.',
			'type'    => 'error',
		),
		'already_inscr'        => array(
			'content' => 'Vous êtes déjà inscrit.',
			'type'    => 'error',
		),
		'panier_echange_saved' => array(
			'content' => 'Votre panier a bien été ajouté dans la liste des paniers intermittents.',
			'type'    => 'success',
		),
		'panier_echange_already_done' => array(
			'content' => 'Votre panier est déjà dans la liste des paniers intermittents.',
			'type'    => 'success',
		),
		'adhesion_success'            => array(
			'content' => 'Votre adhésion a été prise en compte.',
			'type'    => 'success',
		),
		'intermittence_success'       => array(
			'content' => 'Votre demande d\'inscription à l\'espace intermittents a été prise en compte.',
			'type'    => 'success',
		),
		'fill_fields'                 => array(
			'content' => 'Remplir tous les champs requis.',
			'type'    => 'error',
		),
	);
	if ( isset( $_GET['message'] ) ) {
		$result = $_GET['message'];
		if ( array_key_exists( $result, $messages ) ) {
			$message = $messages[ $result ];

			return "<div class='alert alert-{$message['type']}' role='alert'>{$message['content']}</div>";
		}
	}

	return '';
}