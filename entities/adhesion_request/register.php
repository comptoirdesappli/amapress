<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_request' );
function amapress_register_entities_adhesion_request( $entities ) {
	$entities['adhesion_request'] = array(
		'internal_name'    => 'amps_adh_req',
		'singular'         => amapress__( 'Demande de préinscription' ),
		'plural'           => amapress__( 'Demandes de préinscription' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'adhesions_requests',
		'title_format'     => 'amapress_adhesion_request_title_formatter',
		'slug_format'      => 'from_title',
		'title'            => false,
		'editor'           => false,
//        'menu_icon' => 'flaticon-business',
		'redirect_archive' => 'amapress_redirect_agenda',
//        'menu_icon' => 'fa-menu fa-university',
		'views'            => array(
			'remove'  => array( 'mine' ),
//            '_dyn_' => 'amapress_assemblee_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'first_name'        => array(
				'name'       => amapress__( 'Prénom' ),
				'type'       => 'text',
				'time'       => true,
				'required'   => true,
				'desc'       => 'Prénom',
				'searchable' => true,
			),
			'last_name'         => array(
				'name'       => amapress__( 'Nom' ),
				'type'       => 'text',
				'time'       => true,
				'required'   => true,
				'desc'       => 'Nom',
				'searchable' => true,
			),
			'adresse'           => array(
				'name'       => amapress__( 'Adresse' ),
				'type'       => 'textarea',
				'time'       => true,
				'required'   => true,
				'desc'       => 'Nom',
				'searchable' => true,
//                'group' => 'Adresse',
			),
			'adresse_localized' => array(
				'name'               => amapress__( 'Localisé' ),
				'type'               => 'address',
				'field_name_prefix'  => 'amapress_adhesion_request',
				'use_as_field'       => false,
				'address_field_name' => 'amapress_adhesion_request_adresse',
//                'postal_code_field_name' => 'amapress_lieu_distribution_code_postal',
//                'town_field_name' => 'amapress_lieu_distribution_ville',
//                'group' => 'Adresse',
			),
			'telephone'         => array(
				'name'       => amapress__( 'Téléphone' ),
				'type'       => 'text',
				'time'       => true,
				'required'   => true,
				'desc'       => 'Téléphone',
				'searchable' => true,
			),
			'lieux'             => array(
				'name'              => amapress__( 'Lieux de distribution' ),
				'type'              => 'multicheck-posts',
				'post_type'         => AmapressLieu_distribution::INTERNAL_POST_TYPE,
				'desc'              => 'Lieux de distribution',
				'autoselect_single' => true,
			),
			'contrat_intances'  => array(
				'name'      => amapress__( 'Contrats' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'desc'      => 'Contrats',
			),
			'status'            => array(
				'name'     => amapress__( 'Statut' ),
				'type'     => 'select',
				'group'    => '1/ Informations',
				'options'  => array(
					'to_confirm' => 'En attente de confirmation',
					'confirmed'  => 'Confirmée',
				),
				'required' => true,
				'desc'     => 'Statut',
			),
		),
	);

	return $entities;
}

function amapress_adhesion_request_count_shortcode( $atts ) {
	$posts = get_posts(
		array(
			'posts_per_page' => - 1,
			'post_type'      => 'amps_adh_req',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'amapress_adhesion_request_status',
					'value'   => 'to_confirm',
					'compare' => '=',
				),
				array(
					'key'     => 'amapress_adhesion_request_status',
					'compare' => 'NOT EXISTS',
				),
			)
		)
	);
	$cnt   = count( $posts );

	return "<span class='update-plugins count-$cnt' style='background-color:white;color:black;margin-left:5px;'><span class='plugin-count'>$cnt</span></span>";
}