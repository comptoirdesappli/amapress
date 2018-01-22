<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_visite' );
function amapress_register_entities_visite( $entities ) {
	$entities['visite'] = array(
		'singular'         => amapress__( 'Visite à la ferme' ),
		'plural'             => amapress__( 'Visites à la ferme' ),
		'public'             => true,
		'logged_or_public'   => true,
		'show_in_menu'       => false,
		'show_in_nav_menu'   => false,
		'editor'             => false,
		'title'              => false,
		'thumb'              => true,
		'title_format'       => 'amapress_visite_title_formatter',
		'slug_format'        => 'from_title',
		'slug'               => amapress__( 'visites' ),
		'redirect_archive'   => 'amapress_redirect_agenda',
		'menu_icon'          => 'flaticon-sprout',
		'show_admin_bar_new' => true,
		'views'              => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_visite_views',
			'exp_csv' => true,
		),
		'fields'             => array(
//			'photo'        => array(
//				'name'  => amapress__( 'Photo' ),
//				'type'  => 'upload',
//				'group' => 'Information',
//				'desc'  => 'Photo',
//			),
			'date'         => array(
				'name'         => amapress__( 'Date de visite' ),
				'type'         => 'date',
				'time'         => true,
				'required'     => true,
				'desc'         => 'Date de visite',
				'import_key'   => true,
				'csv_required' => true,
			),
			'heure_debut'  => array(
				'name'         => amapress__( 'Heure début' ),
				'type'         => 'date',
				'date'         => false,
				'time'         => true,
				'required'     => true,
				'desc'         => 'Heure début',
				'csv_required' => true,
			),
			'heure_fin'    => array(
				'name'         => amapress__( 'Heure fin' ),
				'type'         => 'date',
				'date'         => false,
				'time'         => true,
				'required'     => true,
				'desc'         => 'Heure fin',
				'csv_required' => true,
			),
			'producteur'   => array(
				'name'              => amapress__( 'Producteur' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'required'          => true,
				'desc'              => 'Producteur',
				'import_key'        => true,
				'csv_required'      => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => 'Toutes les producteurs',
				),
				'searchable'        => true,
			),
			'au_programme' => array(
				'name'       => amapress__( 'Au programme' ),
				'type'       => 'editor',
				'desc'       => 'Au programme',
				'searchable' => true,
			),
			'participants' => array(
				'name'         => amapress__( 'Participants' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Participants',
			),
		),
	);

	return $entities;
}