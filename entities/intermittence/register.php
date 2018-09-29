<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_intermittence' );
function amapress_register_entities_intermittence( $entities ) {
	$entities['intermittence_panier'] = array(
		'singular'         => amapress__( 'Panier intermittent' ),
		'plural'           => amapress__( 'Paniers intermittents' ),
		'internal_name'    => 'amps_inter_panier',
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => false,
		'editor'           => false,
		'slug'             => 'intermittences_paniers',
		'title_format'     => 'amapress_intermittence_panier_title_formatter',
		'slug_format'      => 'from_title',
		'menu_icon'        => 'fa-menu fa-shopping-basket',
		'default_orderby'  => 'amapress_intermittence_panier_date',
		'default_order'    => 'ASC',
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_intermittence_panier_views',
			'exp_csv' => true,
		),
//        'row_actions' => array(
//            'resend_mail' => 'amapress_get_send_panier_intermittent_available',
//        ),
		'fields'           => array(
			'date'               => array(
				'name'       => amapress__( 'Date' ),
				'type'       => 'date',
				'readonly'   => true,
				'desc'       => 'Date ',
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'panier'             => array(
				'name'      => amapress__( 'Panier(s)' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressPanier::INTERNAL_POST_TYPE,
				'readonly'  => true,
				'desc'      => 'Panier(s)',
//				'searchable' => true,
			),
			'contrat_instance'   => array(
				'name'      => amapress__( 'Contrat(s)' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'readonly'  => true,
				'desc'      => 'Contrat(s)',
//				'autoselect_single' => true,
//				'searchable'        => true,
			),
			'repreneur'          => array(
				'name'       => amapress__( 'Repreneur' ),
				'type'       => 'select-users',
				'desc'       => 'Repreneur',
				'searchable' => true,
				'readonly'   => true,
			),
			'adherent'           => array(
				'name'       => amapress__( 'Adherent' ),
				'type'       => 'select-users',
				'readonly'   => true,
				'desc'       => 'Adherent',
				'searchable' => true,
			),
			'lieu'               => array(
				'name'              => amapress__( 'Lieu' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressLieu_distribution::INTERNAL_POST_TYPE,
				'readonly'          => true,
				'desc'              => 'Lieu',
				'autoselect_single' => true,
//				'searchable'        => true,
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Toutes les lieux',
				),
			),
			'adh_message'        => array(
				'name'       => amapress__( 'Message aux repreneurs' ),
				'type'       => 'readonly',
				'desc'       => 'Message',
				'searchable' => true,
			),
			'adh_cancel_message' => array(
				'name'       => amapress__( 'Message d\'annulation' ),
				'type'       => 'readonly',
				'desc'       => 'Message',
				'searchable' => true,
			),
			'status'             => array(
				'name'       => amapress__( 'Statut' ),
				'type'       => 'select',
				'top_filter' => array(
					'name'        => 'amapress_status',
					'placeholder' => 'Tous les états',
				),
				'options'    => array(
					'to_exchange'     => 'A réserver',
					'exch_valid_wait' => 'En attente de validation de l\'échange',
					'exchanged'       => 'Réservé',
					'closed'          => 'Cloturé',
					'cancelled'       => 'Annulé',
				),
				'required'   => true,
				'desc'       => 'Statut',
				'readonly'   => true,
			),
		),
	);

	return $entities;
}