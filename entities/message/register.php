<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_message' );
function amapress_register_entities_message( $entities ) {
	$entities['message'] = array(
		'singular'         => amapress__( 'Message' ),
		'plural'           => amapress__( 'Messages' ),
		'public'           => 'adminonly',
		'special_options'  => array(),
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'slug'             => 'messages',
		'logged_or_public' => true,
		'fields'           => array(
			'target_name'     => array(
				'name' => amapress__( 'Destinataire' ),
				'type' => 'readonly',
				'desc' => 'Destinataire',
			),
			'query_string'    => array(
				'name'        => amapress__( 'Accès liste destinataires' ),
				'type'        => 'readonly',
				'show_column' => false,
			),
			'user_ids'        => array(
				'name'         => amapress__( 'Destinataires' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'readonly'     => true,
			),
			'target_type'     => array(
				'name'        => amapress__( 'Type de destinataire' ),
				'type'        => 'readonly',
				'show_column' => false,
			),
			'associated_date' => array(
				'name' => amapress__( 'Date associée' ),
				'type' => 'readonly',
			),
			'content_for_sms' => array(
				'name'        => 'Contenu du sms associé',
				'type'        => 'readonly',
				'show_column' => false,
				'searchable'  => true,
			),
			'sms_sent'        => array(
				'name'     => 'Sms relayé',
				'type'     => 'checkbox',
				'readonly' => true,
			),
		),
	);

	return $entities;
}

