<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_period' );
function amapress_register_entities_adhesion_period( $entities ) {
	$entities['adhesion_period'] = array(
		'internal_name'    => 'amps_adh_per',
		'singular'         => amapress__( 'Période Adhésion' ),
		'plural'           => amapress__( 'Périodes Adhésion' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => false,
		'editor'           => false,
		'slug'             => 'adhesion_periods',
		'title_format'     => 'amapress_adhesion_period_title_formatter',
		'slug_format'      => 'from_title',
		'menu_icon'        => 'flaticon-signature',
		'labels'           => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Ajouter une période d\'adhésion',
		),
		'row_actions'      => array(
			'renew' => 'Renouveler',
		),
		'views'            => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_adhesion_period_views',
//            'exp_csv' => true,
		),
		'fields'           => array(
			'date_debut' => array(
				'name'         => amapress__( 'Date de début' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => '1/ Informations',
				'desc'         => 'Date à laquelle démarre la période',
				'csv_required' => true,
			),
			'date_fin'   => array(
				'name'         => amapress__( 'Date de fin' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => '1/ Informations',
				'desc'         => 'Date à laquelle finit la période',
				'csv_required' => true,
			),
		),
	);

	return $entities;
}

add_action( 'amapress_row_action_adhesion_period_renew', 'amapress_row_action_adhesion_period_renew' );
function amapress_row_action_adhesion_period_renew( $post_id ) {
	$period     = new AmapressAdhesionPeriod( $post_id );
	$new_period = $period->clonePeriod();
	if ( ! $new_period ) {
		wp_die( 'Une erreur s\'est produit lors du renouvèlement de la période d\'adhésion. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_period->ID}&action=edit" ) );
}
