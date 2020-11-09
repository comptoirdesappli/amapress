<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_message' );
function amapress_register_entities_message( $entities ) {
	$entities['message'] = array(
		'singular'         => __( 'Message', 'amapress' ),
		'plural'           => __( 'Messages', 'amapress' ),
		'public'           => 'adminonly',
		'special_options'  => array(),
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'slug'             => 'messages',
		'logged_or_public' => true,
		'fields'           => array(
			'target_name'     => array(
				'name' => __( 'Destinataire', 'amapress' ),
				'type' => 'readonly',
				'desc' => __( 'Destinataire', 'amapress' ),
			),
			'query_string'    => array(
				'name'        => __( 'Accès liste destinataires', 'amapress' ),
				'type'        => 'readonly',
				'show_column' => false,
			),
			'user_ids'        => array(
				'name'         => __( 'Destinataires', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'readonly'     => true,
			),
			'target_type'     => array(
				'name'        => __( 'Type de destinataire', 'amapress' ),
				'type'        => 'readonly',
				'show_column' => false,
			),
			'associated_date' => array(
				'name' => __( 'Date associée', 'amapress' ),
				'type' => 'readonly',
			),
			'content_for_sms' => array(
				'name'        => __( 'Contenu du sms associé', 'amapress' ),
				'type'        => 'readonly',
				'show_column' => false,
				'searchable'  => true,
			),
			'sms_sent'        => array(
				'name'     => __( 'Sms relayé', 'amapress' ),
				'type'     => 'checkbox',
				'readonly' => true,
			),
		),
	);

	return $entities;
}

