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
		'logged_or_public' => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'thumb'            => true,
		'title_format'     => 'amapress_assemblee_title_formatter',
		'slug_format'      => 'from_title',
		'slug'             => amapress__( 'assemblees' ),
		'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'fa-menu fa-university',
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_assemblee_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'date'          => array(
				'name'     => amapress__( 'Date' ),
				'type'     => 'date',
				'time'     => true,
				'required' => true,
				'desc'     => 'Date',
			),
			'heure_debut'   => array(
				'name'     => amapress__( 'Heure début' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure début',
			),
			'heure_fin'     => array(
				'name'     => amapress__( 'Heure fin' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure fin',
			),
			'ordre_du_jour' => array(
				'name'       => amapress__( 'Ordre du jour' ),
				'type'       => 'editor',
				'desc'       => 'Ordre du jour',
				'searchable' => true,
			),
			'lieu'          => array(
				'name'              => amapress__( 'Lieu de distribution' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_lieu',
				'required'          => true,
				'desc'              => 'Lieu de distribution',
				'autoselect_single' => true,
				'searchable'        => true,
			),
			'participants'  => array(
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