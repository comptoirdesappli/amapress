<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_assemblee' );
function amapress_register_entities_assemblee( $entities ) {
	$entities['assemblee_generale'] = array(
		'internal_name'    => 'amps_assemblee',
		'singular'         => amapress__( 'Assemblée générale' ),
		'plural'           => amapress__( 'Assemblées générales' ),
		'public'           => true,
		'logged_or_public'   => true,
		'show_in_menu'       => false,
		'show_in_nav_menu'   => false,
		'editor'             => false,
		'title'              => false,
		'thumb'              => true,
		'title_format'       => 'amapress_assemblee_title_formatter',
		'slug_format'        => 'from_title',
		'slug'               => amapress__( 'assemblees' ),
		'redirect_archive'   => 'amapress_redirect_agenda',
		'menu_icon'          => 'fa-menu fa-university',
		'default_orderby'    => 'amapress_assemblee_generale_date',
		'default_order'      => 'ASC',
		'show_admin_bar_new' => true,
		'views'              => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_assemblee_views',
			'exp_csv' => true,
		),
		'fields'             => array(
			'ordre_du_jour' => array(
				'name'       => amapress__( 'Ordre du jour' ),
				'type'       => 'editor',
				'desc'       => 'Ordre du jour',
				'searchable' => true,
				'group'      => '1/ Ordre du jour',
			),
			'date'          => array(
				'name'       => amapress__( 'Date' ),
				'type'       => 'date',
				'time'       => true,
				'required'   => true,
				'desc'       => 'Date ',
				'group'      => '2/ Horaires',
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'heure_debut'   => array(
				'name'     => amapress__( 'Heure début' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure début',
				'group'    => '2/ Horaires',
			),
			'heure_fin'     => array(
				'name'     => amapress__( 'Heure fin' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure fin',
				'group'    => '2/ Horaires',
			),
			'lieu'          => array(
				'name'              => amapress__( 'Lieu de distribution' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_lieu',
				'required'          => true,
				'desc'              => 'Lieu de distribution',
				'autoselect_single' => true,
				'searchable'        => true,
				'group'             => '3/ Emplacement',
			),
			'participants'  => array(
				'name'         => amapress__( 'Participants' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Participants',
				'group'        => '4/ Participants',
			),
		),
	);

	return $entities;
}