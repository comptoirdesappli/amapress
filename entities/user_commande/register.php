<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_user_commande' );
function amapress_register_entities_user_commande( $entities ) {
	$entities['user_commande'] = array(
		'singular'         => amapress__( 'Bon de commande' ),
		'plural'           => amapress__( 'Bons de commandes' ),
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'public'           => 'adminonly',
//                'show_in_menu' => false,
		'editor'           => false,
		'title'            => false,
		'title_format'     => 'amapress_user_commande_title_formatter',
		'slug_format'      => 'from_id',
		'slug'             => amapress__( 'mes-commandes' ),
		'menu_icon'        => 'flaticon-restaurant',
		'views'            => array(
			'remove'  => array( 'mine' ),
			'exp_csv' => true,
//                    '_dyn_' => 'amapress_panier_views',
		),
		'fields'           => array(
			'commande' => array(
				'name'      => amapress__( 'Commande' ),
				'type'      => 'select-posts',
				'post_type' => 'amps_commande',
				'required'  => true,
				'desc'      => 'Commande',
			),
			'amapien'  => array(
				'name'     => amapress__( 'Amapien' ),
				'type'     => 'select-users',
				'required' => true,
				'desc'     => 'Amapien',
			),
//                    'produits' => array(
//                        'name' => amapress__('Panier'),
//                        'type' => 'custom',
//                        'custom' => array('AmapressPaniers', "panierTable"),
//                        'save' => array('AmapressPaniers', 'savePanierTable'),
//                        'desc' => 'Produits',
//                    ),
		),
	);

	return $entities;
}