<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_handle_and_get_action_messages() {
	static $messages = null;

	if ( empty( $messages ) ) {
		$messages = array(
			'inscr_success'               => array(
				'content' => __( 'Votre participation a été enregistrée.', 'amapress' ),
				'type'    => 'success',
			),
			'desinscr_success'            => array(
				'content' => __( 'Votre participation a été desenregistrée.', 'amapress' ),
				'type'    => 'success',
			),
			'already_in_list'             => array(
				'content' => __( 'Vous êtes déjà dans la liste des participants.', 'amapress' ),
				'type'    => 'warn',
			),
			'already_taken'               => array(
				'content' => __( 'Rôle déjà pris', 'amapress' ),
				'type'    => 'error',
			),
			'list_full'                   => array(
				'content' => __( 'La liste des participants est déjà complète.', 'amapress' ),
				'type'    => 'error',
			),
			'not_inscr'                   => array(
				'content' => __( 'Vous n\'êtes pas inscrit.', 'amapress' ),
				'type'    => 'error',
			),
			'already_inscr'               => array(
				'content' => __( 'Vous êtes déjà inscrit.', 'amapress' ),
				'type'    => 'error',
			),
			'panier_echange_saved'        => array(
				'content' => __( 'Votre panier a bien été ajouté dans la liste des paniers intermittents.', 'amapress' ),
				'type'    => 'success',
			),
			'panier_echange_already_done' => array(
				'content' => __( 'Votre panier est déjà dans la liste des paniers intermittents.', 'amapress' ),
				'type'    => 'success',
			),
			'adhesion_success'            => array(
				'content' => __( 'Votre adhésion a été prise en compte.', 'amapress' ),
				'type'    => 'success',
			),
			'intermittence_success'       => array(
				'content' => __( 'Votre demande d\'inscription à l\'espace intermittents a été prise en compte.', 'amapress' ),
				'type'    => 'success',
			),
			'fill_fields'                 => array(
				'content' => __( 'Remplir tous les champs requis.', 'amapress' ),
				'type'    => 'error',
			),
		);
	}
	if ( isset( $_GET['message'] ) ) {
		$result = $_GET['message'];
		if ( array_key_exists( $result, $messages ) ) {
			$message = $messages[ $result ];

			return "<div class='alert alert-{$message['type']}' role='alert'>{$message['content']}</div>";
		}
	}

	return '';
}