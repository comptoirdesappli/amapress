<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_panier' );
function amapress_register_entities_panier( $entities ) {
	$entities['panier'] = array(
		'singular'         => amapress__( 'Panier' ),
		'plural'           => amapress__( 'Paniers' ),
		'public'           => true,
		'logged_or_public' => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'title_format'     => 'amapress_panier_title_formatter',
		'slug_format'      => 'from_title',
		'slug'             => amapress__( 'paniers' ),
		'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'fa-menu fa-shopping-basket',
		'views'            => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_panier_views',
		),
		'fields'           => array(
			'date'             => array(
				'name'     => amapress__( 'Date du panier' ),
				'type'     => 'date',
				'readonly' => true,
				'desc'     => 'Date de distribution',
			),
			'contrat_instance' => array(
				'name'       => amapress__( 'Contrat' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_contrat_inst',
				'readonly'   => true,
				'desc'       => 'Contrat',
				'searchable' => true,
			),
			'produits'         => array(
				'name'   => amapress__( 'Panier' ),
				'type'   => 'custom',
				'custom' => array( 'AmapressPaniers', "panierTable" ),
				'save'   => array( 'AmapressPaniers', 'savePanierTable' ),
				'desc'   => 'Produits',
			),
			'status'           => array(
				'name'    => amapress__( '' ),
				'type'    => 'select',
				'options' => array(
					''          => 'En temps',
					'cancelled' => 'Annulé',
					'delayed'   => 'Reporté',
				),
			),
			'date_subst'       => array(
				'name' => amapress__( 'Date de remplacement' ),
				'type' => 'date',
				'desc' => 'Date de distribution de remplacement',
			),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_panier', 'amapress_can_delete_panier', 10, 2 );
function amapress_can_delete_panier( $can, $post_id ) {
	return false;
}