<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_lieu_distribution' );
function amapress_register_entities_lieu_distribution( $entities ) {
	$entities['lieu_distribution'] = array(
		'internal_name'           => 'amps_lieu',
		'singular'                => amapress__( 'Lieu de distribution' ),
		'plural'                  => amapress__( 'Lieux de distribution' ),
		'public'                  => true,
		'show_in_menu'            => false,
		'thumb'                   => true,
		'special_options'         => array(),
		'slug'                    => 'lieux-distribution',
		'custom_archive_template' => true,
		'menu_icon'               => 'fa-menu fa-map-signs',
		'default_orderby'         => 'post_title',
		'default_order'           => 'ASC',
		'show_admin_bar_new'      => false,
		'row_actions'             => array(
			'relocate' => array(
				'label'     => 'Géolocaliser',
				'condition' => function ( $user_id ) {
					$lieu = AmapressLieu_distribution::getBy( $user_id );

					return $lieu && ( ! $lieu->isAdresseAccesLocalized() || ! $lieu->isAdresseLocalized() );
				},
				'confirm'   => true,
			),
		),
		'views'                   => array(
			'remove' => array( 'mine' ),
		),
		'edit_header'             => function ( $post ) {
			$lieu = AmapressLieu_distribution::getBy( $post, true );
			if ( $lieu ) {
				if ( ! $lieu->isAdresseLocalized() ) {
					amapress_add_admin_notice( 'Adresse du lieu non localisée', 'warning', false );
				}
				if ( ! empty( $lieu->getAdresseAcces() ) && ! $lieu->isAdresseAccesLocalized() ) {
					amapress_add_admin_notice( 'Adresse d\'accès du lieu non localisée', 'warning', false );
				}
			}
		},
		'fields'                  => array(
			'shortname'           => array(
				'name'       => amapress__( 'Nom court' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => 'Nom court',
				'group'      => 'Information',
				'searchable' => true,
			),
			'principal'           => array(
				'name'    => amapress__( 'Lieu principal' ),
				'group'   => 'Information',
				'type'    => 'checkbox',
				'default' => true,
			),
//            'photo' => array(
//                'name' => amapress__('Photo'),
//                'type' => 'upload',
//                'group' => 'Information',
//                'desc' => 'Photo',
//            ),
			'contact_externe'     => array(
				'name'       => amapress__( 'Contact externe' ),
				'type'       => 'editor',
				'desc'       => 'Contact externe',
				'group'      => 'Gestion',
				'searchable' => true,
			),
			'referent'            => array(
				'name'       => amapress__( 'Référent' ),
				'type'       => 'select-users',
				'desc'       => 'Référent',
				'group'      => 'Gestion',
				'searchable' => true,
			),
			'nb_responsables'     => array(
				'name'     => amapress__( 'Nombre de responsables de distributions' ),
				'type'     => 'number',
				'required' => true,
				'desc'     => 'Nombre de responsables de distributions',
				'group'    => 'Gestion',
			),
			'heure_debut'         => array(
				'name'     => amapress__( 'Heure début' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure début',
				'group'    => 'Horaires',
			),
			'heure_fin'           => array(
				'name'     => amapress__( 'Heure fin' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure fin',
				'group'    => 'Horaires',
			),
			'adresse'             => array(
				'name'       => amapress__( 'Adresse' ),
				'type'       => 'textarea',
				'desc'       => 'Adresse',
				'required'   => true,
//                'save' => array('AmapressUsers', 'resolveLieuAddress'),
				'group'      => 'Adresse',
				'searchable' => true,
			),
			'code_postal'         => array(
				'name'       => amapress__( 'Code postal' ),
				'type'       => 'text',
				'desc'       => 'Code postal',
				'group'      => 'Adresse',
				'searchable' => true,
			),
			'ville'               => array(
				'name'       => amapress__( 'Ville' ),
				'type'       => 'text',
				'desc'       => 'Ville',
				'group'      => 'Adresse',
				'searchable' => true,
			),
			'adresse_localized'   => array(
				'name'                   => amapress__( 'Localisé' ),
				'type'                   => 'address',
				'field_name_prefix'      => 'amapress_lieu_distribution',
				'use_as_field'           => false,
				'use_enter_gps'          => true,
				'address_field_name'     => 'amapress_lieu_distribution_adresse',
				'postal_code_field_name' => 'amapress_lieu_distribution_code_postal',
				'town_field_name'        => 'amapress_lieu_distribution_ville',
				'group'                  => 'Adresse',
				'searchable'             => true,
			),
			'acces'               => array(
				'name'       => amapress__( 'Accès' ),
				'type'       => 'editor',
				'required'   => false,
				'desc'       => 'Accès',
				'group'      => 'Adresse',
				'searchable' => true,
			),
			'adresse_acces'       => array(
				'name'         => amapress__( 'Adresse d\'accès' ),
				'type'         => 'address',
				'use_as_field' => true,
				'group'        => 'Adresse',
				'searchable'   => true,
			),
			'instructions_privee' => array(
				'name'       => amapress__( 'Instructions privées' ),
				'type'       => 'editor',
				'required'   => false,
				'desc'       => 'Instructions privées',
				'group'      => 'Gestion',
				'searchable' => true,
			),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_lieu_distribution', 'amapress_can_delete_lieu_distribution', 10, 2 );
function amapress_can_delete_lieu_distribution( $can, $post_id ) {
	$lieux = get_posts(
		'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE . '&amapress_lieu=' . $post_id
	);

	return empty( $lieux );
}

add_action( 'amapress_row_action_lieu_distribution_relocate', 'amapress_row_action_lieu_distribution_relocate' );
function amapress_row_action_lieu_distribution_relocate( $post_id ) {
	$lieu = AmapressLieu_distribution::getBy( $post_id );
	if ( $lieu ) {
		$lieu->resolveAddress();
	}
	wp_redirect_and_exit( wp_get_referer() );
}