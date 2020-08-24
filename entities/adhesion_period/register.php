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
		'edit_header'      => function ( $post ) {
			$period = AmapressAdhesionPeriod::getBy( $post->ID );
			$result = $period->getModelDocStatus();
			if ( true !== $result ) {
				echo amapress_get_admin_notice( $result['message'], $result['status'], false );
			}
		},
		'row_actions'      => array(
			'renew' => [
				'label'   => 'Renouveler',
				'confirm' => true,
			],
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
			'word_model'   => array(
				'name'            => amapress__( 'Bulletin personnalisé' ),
				'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'type'            => 'upload',
				'show_column'     => false,
				'show_download'   => true,
				'show_title'      => true,
				'selector-title'  => 'Sélectionnez/téléversez un modèle de bulletin DOCX',
				'selector-button' => 'Utiliser ce modèle',
				'group'           => 'Pré-inscription en ligne',
				'desc'            => '<p>Configurer un modèle de bulletin à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_placeholders' ) . '">Plus d\'info</a>)</p>
<p>Vous pouvez télécharger <a target="_blank" href="' . esc_attr( Amapress::getBulletinGenericUrl() ) . '">ici</a> un modèle DOCX générique utilisable comme bulletin d\'adhésion. Vous aurez à personnaliser le logo de votre AMAP et les élements de l\'adhésion (don, panier solidaire, règlement, explications...).</p>',
			),
			'mnt_reseau'   => array(
				'name'     => amapress__( 'Montant adhésion au réseau' ),
				'type'     => 'number',
				'required' => true,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion au réseau',
			),
			'mnt_amap'     => array(
				'name'     => amapress__( 'Montant adhésion AMAP' ),
				'type'     => 'number',
				'required' => true,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion AMAP',
			),
			'allow_chq'    => array(
				'name'        => amapress__( 'Chèque' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en chèque',
			),
			'allow_cash'   => array(
				'name'        => amapress__( 'Espèces' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en espèce',
			),
			'allow_bktrfr'   => array(
				'name'        => amapress__( 'Virement' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement par virement',
			),
			'allow_locmon'   => array(
				'name'        => amapress__( 'Monnaie locale' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en monnaie locale',
			),
			'pmt_info'       => array(
				'name'  => amapress__( 'Info règlements' ),
				'type'  => 'editor',
				'group' => 'Pré-inscription en ligne',
				'desc'  => 'Informations relatives aux règlements (ordres des chèques, virement) à afficher lors de l\'adhésion en ligne',
			),
			'pmt_user_input' => array(
				'name'        => amapress__( 'Libellé règlements' ),
				'type'        => 'checkbox',
				'group'       => '5/6 - Pré-inscription en ligne',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Permettre aux amapiens de renseigner les numéros des chèques dans l’assistant d\'adhésion en ligne',
			),
		),
	);

	return $entities;
}

add_action( 'amapress_row_action_adhesion_period_renew', 'amapress_row_action_adhesion_period_renew' );
function amapress_row_action_adhesion_period_renew( $post_id ) {
	$period     = AmapressAdhesionPeriod::getBy( $post_id );
	$new_period = $period->clonePeriod();
	if ( ! $new_period ) {
		wp_die( 'Une erreur s\'est produit lors du renouvellement de la période d\'adhésion. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_period->ID}&action=edit" ) );
}
