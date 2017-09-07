<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_commande' );
function amapress_register_entities_commande( $entities ) {
	$entities['commande'] = array(
		'singular'         => amapress__( 'Distribution ponctuelle' ),
		'plural'           => amapress__( 'Distributions ponctuelles' ),
		'public'           => true,
		'logged_or_public' => true,
		'title_format'     => 'amapress_commande_title_formatter',
		'slug_format'      => 'from_title',
		//                'show_in_menu' => false,
		'slug'             => amapress__( 'commandes' ),
		'editor'           => false,
		'title'            => false,
		'redirect_archive' => 'amapress_redirect_agenda',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'menu_icon'        => 'flaticon-interface-1',
		'views'            => array(
			'remove' => array( 'mine' ),
//                    '_dyn_' => 'amapress_distribution_views',
		),
		'fields'           => array(
			'date_distrib'     => array(
				'name'     => amapress__( 'Date de distribution' ),
				'type'     => 'date',
				'time'     => true,
				'group'    => 'Livraison',
				'required' => true,
				'desc'     => 'Date de distribution',
			),
			'date_debut'       => array(
				'name'     => amapress__( 'Date d\'ouverture de la commande' ),
				'type'     => 'date',
				'time'     => true,
				'group'    => 'Validité',
				'required' => true,
				'desc'     => 'Date d\'ouverture de la commande',
			),
			'date_fin'         => array(
				'name'     => amapress__( 'Date de cloture de la commandes' ),
				'type'     => 'date',
				'group'    => 'Validité',
				'time'     => true,
				'required' => true,
				'desc'     => 'Date de cloture de la commandes',
			),
			'contrat_instance' => array(
				'name'      => amapress__( 'Contrat associé' ),
				'type'      => 'select-posts',
				'group'     => 'Gestion',
				'post_type' => 'amps_contrat_inst',
				'required'  => true,
				'desc'      => 'Contrat associé',
			),
			'lieu'             => array(
				'name'      => amapress__( 'Lieu de distribution' ),
				'type'      => 'select-posts',
				'post_type' => 'amps_lieu',
				'group'     => 'Livraison',
				'required'  => true,
				'desc'      => 'Lieu de distribution',
			),
			'responsables'     => array(
				'name'  => amapress__( 'Responsables' ),
				'type'  => 'multicheck-users',
				'group' => 'Livraison',
				'desc'  => 'Responsables',
			),
		),
	);

	return $entities;
}