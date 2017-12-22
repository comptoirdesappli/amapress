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
			'date'              => array(
				'name'     => amapress__( 'Date du panier' ),
				'type'     => 'date',
				'readonly' => true,
				'desc'     => 'Date de distribution',
				'group'    => '1/ Informations',
			),
			'contrat_instance'  => array(
				'name'       => amapress__( 'Contrat' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_contrat_inst',
				'readonly'   => true,
				'desc'       => 'Contrat',
				'searchable' => true,
				'group'      => '1/ Informations',
			),
			'produits_selected' => array(
				'name'         => amapress__( 'Produits associés' ),
				'type'         => 'select-posts',
				'post_type'    => AmapressProduit::INTERNAL_POST_TYPE,
				'desc'         => 'Produits associés aux paniers',
				'multiple'     => true,
				'tags'         => true,
				'autocomplete' => true,
				'group'        => '2/ Contenu',
			),
//			'produits'         => array(
//				'name'   => amapress__( 'Panier' ),
//				'type'   => 'custom',
//				'custom' => array( 'AmapressPaniers', "panierTable" ),
//				'save'   => array( 'AmapressPaniers', 'savePanierTable' ),
//				'desc'   => 'Produits',
//			),
			'status'            => array(
				'name'    => amapress__( '' ),
				'type'    => 'select',
				'options' => array(
					''          => 'En temps',
					'cancelled' => 'Annulé',
					'delayed'   => 'Reporté',
				),
				'group'   => '3/ Modification',
			),
			'date_subst'        => array(
				'name'  => amapress__( 'Date de remplacement' ),
				'type'  => 'date',
				'desc'  => 'Date de distribution de remplacement',
				'group' => '3/ Modification',
			),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_panier', 'amapress_can_delete_panier', 10, 2 );
function amapress_can_delete_panier( $can, $post_id ) {
	return false;
}

add_filter( 'amapress_panier_fields', 'amapress_panier_fields' );
function amapress_panier_fields( $fields ) {
	if ( isset( $_REQUEST['post'] ) || isset( $_REQUEST['post_ID'] ) ) {
		$post_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : $_REQUEST['post_ID'];
		if ( get_post_type( $post_id ) == AmapressPanier::INTERNAL_POST_TYPE ) {
			$panier = AmapressPanier::getBy( $post_id );
			if ( $panier->getContrat_instanceId() && ! $panier->getContrat_instance()->isPanierVariable() ) {
				foreach ( AmapressContrats::get_contrat_quantites( $panier->getContrat_instanceId() ) as $quantite ) {
					$fields[ 'contenu_' . $quantite->ID ] = array(
						'name'  => amapress__( 'Contenu pour ' . $quantite->getTitle() ),
						'type'  => 'editor',
						'group' => '2/ Contenu',
					);
				}
			}
		}
	}


	return $fields;
}