<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_lieu_distribution' );
function amapress_register_entities_lieu_distribution( $entities ) {
	$entities['lieu_distribution'] = array(
		'internal_name'           => 'amps_lieu',
		'singular'                => __( 'Lieu de distribution', 'amapress' ),
		'plural'                  => __( 'Lieux de distribution', 'amapress' ),
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
				'label'     => __( 'Géolocaliser', 'amapress' ),
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
					amapress_add_admin_notice( __( 'Adresse du lieu non localisée', 'amapress' ), 'warning', false );
				}
				if ( ! empty( $lieu->getAdresseAcces() ) && ! $lieu->isAdresseAccesLocalized() ) {
					amapress_add_admin_notice( __( 'Adresse d\'accès du lieu non localisée', 'amapress' ), 'warning', false );
				}
			}
		},
		'fields'                  => array(
			'shortname'           => array(
				'name'       => __( 'Nom court', 'amapress' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => __( 'Nom court', 'amapress' ),
				'group'      => __( 'Information', 'amapress' ),
				'searchable' => true,
			),
			'principal'           => array(
				'name'    => __( 'Lieu principal', 'amapress' ),
				'group'   => __( 'Information', 'amapress' ),
				'type'    => 'checkbox',
				'default' => true,
			),
//            'photo' => array(
//                'name' => amapress__('Photo'),
//                'type' => 'upload',
//                'group' => __('Information', 'amapress'),
//                'desc' => __('Photo', 'amapress'),
//            ),
			'contact_externe'     => array(
				'name'       => __( 'Contact externe', 'amapress' ),
				'type'       => 'editor',
				'desc'       => __( 'Contact externe', 'amapress' ),
				'group'      => __( 'Gestion', 'amapress' ),
				'searchable' => true,
			),
			'referent'            => array(
				'name'       => __( 'Référent', 'amapress' ),
				'type'       => 'select-users',
				'desc'       => __( 'Référent', 'amapress' ),
				'group'      => __( 'Gestion', 'amapress' ),
				'searchable' => true,
			),
			'nb_responsables'     => array(
				'name'     => __( 'Nombre de responsables de distributions', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'desc'     => __( 'Nombre de responsables de distributions', 'amapress' ),
				'group'    => __( 'Gestion', 'amapress' ),
			),
			'heure_debut'         => array(
				'name'     => __( 'Heure début', 'amapress' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => __( 'Heure début', 'amapress' ),
				'group'    => __( 'Horaires', 'amapress' ),
			),
			'heure_fin'           => array(
				'name'     => __( 'Heure fin', 'amapress' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => __( 'Heure fin', 'amapress' ),
				'group'    => __( 'Horaires', 'amapress' ),
			),
			'adresse'             => array(
				'name'       => __( 'Adresse', 'amapress' ),
				'type'       => 'textarea',
				'desc'       => __( 'Adresse', 'amapress' ),
				'required'   => true,
//                'save' => array(__('AmapressUsers', 'amapress'), 'resolveLieuAddress'),
				'group'      => __( 'Adresse', 'amapress' ),
				'searchable' => true,
			),
			'code_postal'         => array(
				'name'       => __( 'Code postal', 'amapress' ),
				'type'       => 'text',
				'desc'       => __( 'Code postal', 'amapress' ),
				'group'      => __( 'Adresse', 'amapress' ),
				'searchable' => true,
			),
			'ville'               => array(
				'name'       => __( 'Ville', 'amapress' ),
				'type'       => 'text',
				'desc'       => __( 'Ville', 'amapress' ),
				'group'      => __( 'Adresse', 'amapress' ),
				'searchable' => true,
			),
			'adresse_localized'   => array(
				'name'                   => __( 'Localisé', 'amapress' ),
				'type'                   => 'address',
				'field_name_prefix'      => 'amapress_lieu_distribution',
				'use_as_field'           => false,
				'use_enter_gps'          => true,
				'address_field_name'     => 'amapress_lieu_distribution_adresse',
				'postal_code_field_name' => 'amapress_lieu_distribution_code_postal',
				'town_field_name'        => 'amapress_lieu_distribution_ville',
				'group'                  => __( 'Adresse', 'amapress' ),
				'searchable'             => true,
			),
			'acces'               => array(
				'name'       => __( 'Accès', 'amapress' ),
				'type'       => 'editor',
				'required'   => false,
				'desc'       => __( 'Accès', 'amapress' ),
				'group'      => __( 'Adresse', 'amapress' ),
				'searchable' => true,
			),
			'adresse_acces'       => array(
				'name'         => __( 'Adresse d\'accès', 'amapress' ),
				'type'         => 'address',
				'use_as_field' => true,
				'group'        => __( 'Adresse', 'amapress' ),
				'searchable'   => true,
			),
			'instructions_privee' => array(
				'name'       => __( 'Instructions privées', 'amapress' ),
				'type'       => 'editor',
				'required'   => false,
				'desc'       => __( 'Instructions privées', 'amapress' ),
				'group'      => __( 'Gestion', 'amapress' ),
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