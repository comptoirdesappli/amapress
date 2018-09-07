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
			'date_debut'  => array(
				'name'         => amapress__( 'Date de début' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => '1/ Informations',
				'desc'         => 'Date à laquelle démarre la période',
				'csv_required' => true,
			),
			'date_fin'    => array(
				'name'         => amapress__( 'Date de fin' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => '1/ Informations',
				'desc'         => 'Date à laquelle finit la période',
				'csv_required' => true,
			),
			'online_desc' => array(
				'name'  => amapress__( 'Contenu bulletin' ),
				'type'  => 'editor',
				'group' => 'Pré-inscription en ligne',
				'desc'  => 'Contenu à afficher lors de l\'adhésion en ligne',
			),
			'word_model'  => array(
				'name'            => amapress__( 'Bulletin personnalisé' ),
				'media-type'      => 'application/vnd.oasis.opendocument.text,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'type'            => 'upload',
				'show_column'     => false,
				'selector-button' => 'Utiliser ce modèle',
				'group'           => 'Pré-inscription en ligne',
				'desc'            => 'Configurer un modèle de bulletin à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_placeholders' ) . '">Plus d\'info</a>)',
			),
			'mnt_reseau'  => array(
				'name'     => amapress__( 'Montant adhésion au réseau' ),
				'type'     => 'number',
				'required' => true,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion au réseau',
			),
			'mnt_amap'    => array(
				'name'     => amapress__( 'Montant adhésion AMAP' ),
				'type'     => 'number',
				'required' => true,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion AMAP',
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
