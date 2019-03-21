<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_init', 'amapress_customizer_init' );

function amapress_customizer_init() {
//	global $pagenow;
//	if ('customize.php' != $pagenow && !is_customize_preview()) return;
//	if (! Amapress::isBackOfficePage()) return;
//	if ( ! Amapress::isBackOfficePage() ) {
//		return;
//	}
	if ( ! is_admin() && ! is_customize_preview() ) {
		return;
	}
	
	$titan    = TitanFramework::getInstance( 'amapress' );
	$contrats = AmapressContrats::get_contrats();

	$section = $titan->createCustomizer( array(
		'id' => 'static_front_page',
	) );
	$section->createOption( array(
		'name' => __( 'Amapress - Colonne Agenda', 'amapress' ),
		'type' => 'heading',
	) );
	$section->createOption( array(
		'name'    => __( 'Titre "utilisateur connecté" de la colonne Agenda', 'amapress' ),
		'id'      => 'front_agenda_title',
		'type'    => 'text',
		'default' => 'Cette semaine dans mon panier...',
	) );
	$section->createOption( array(
		'name'    => __( 'Titre "public" de la colonne Agenda', 'amapress' ),
		'id'      => 'front_agenda_public_title',
		'type'    => 'text',
		'default' => 'Agenda',
	) );
	$section->createOption( array(
		'name'    => __( 'Nombre max de date - Adhérents', 'amapress' ),
		'id'      => 'agenda_max_dates',
		'type'    => 'number',
		'default' => '5',
	) );
	$section->createOption( array(
		'name'    => __( 'Nombre max de date - Site publique', 'amapress' ),
		'id'      => 'agenda_max_public_dates',
		'type'    => 'number',
		'default' => '5',
	) );

	$section->createOption( array(
		'name' => __( 'Amapress - Colonne Produits', 'amapress' ),
		'type' => 'heading',
	) );
	//liste des contrats page accueil
	$section->createOption( array(
		'name'    => __( 'Titre de la colonne Produits', 'amapress' ),
		'id'      => 'front_produits_title',
		'type'    => 'text',
		'default' => 'Les produits de l\'Amap...',
	) );

	$contrat_options = array();
	foreach ( $contrats as $contrat ) {
		$contrat_options[ $contrat->ID ] = $contrat->getTitle();
	}

	$section->createOption( array(
		'name'           => __( 'Ordre des produits', 'amapress' ),
		'id'             => 'contrats_order',
		'type'           => 'sortable',
		'visible_button' => false,
		'options'        => $contrat_options,
	) );

	$section->createOption( array(
		'name'    => __( 'Texte du bouton si non Adhérent', 'amapress' ),
		'id'      => 'front_produits_button_text_if_not_adherent',
		'type'    => 'text',
		'default' => 'Découvrir',
	) );
	$section->createOption( array(
		'name'    => __( 'Texte du bouton si Adhérent', 'amapress' ),
		'id'      => 'front_produits_button_text_if_adherent',
		'type'    => 'text',
		'default' => 'Adhérent',
	) );

	$section->createOption( array(
		'name' => __( 'Menu "Se connecter"', 'amapress' ),
		'type' => 'heading',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur du texte', 'amapress' ),
		'id'      => "menu_connecter_col_fg",
		'type'    => 'color',
		'default' => '',
		'css'     => "#menu-item-amapress-connecter { color: value }",
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond', 'amapress' ),
		'id'      => "menu_connecter_col_bg",
		'type'    => 'color',
		'default' => '',
		'css'     => "#menu-item-amapress-connecter { background-color: value }",
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure', 'amapress' ),
		'id'      => "menu_connecter_col_brd",
		'type'    => 'color',
		'default' => '',
		'css'     => "#menu-item-amapress-connecter { border-color: value }",
	) );


	$section->createOption( array(
		'name' => __( 'Amapress - Colonne Carte', 'amapress' ),
		'type' => 'heading',
	) );
	//liste des contrats page accueil
	$section->createOption( array(
		'name'    => __( 'Titre de la colonne Carte', 'amapress' ),
		'id'      => 'front_map_title',
		'type'    => 'text',
		'default' => 'Où nous trouver ?',
	) );

	$section = $titan->createCustomizer( array(
		'id' => 'colors',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur des bordures des encadrés', 'amapress' ),
		'id'      => 'agenda_panel_col_brd',
		'type'    => 'color',
		'default' => '#9d9d9d',
		'css'     => '.amap-panel, .amap-panel-heading, .tab-content { border-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur des légendes', 'amapress' ),
		'id'      => "caption_col_fg",
		'type'    => 'color',
		'default' => '',
		'css'     => ".thumbnail .caption * { color: value }",
	) );
//    $section->createOption(array(
//        'name' => __('Couleur du texte des produits', 'amapress'),
//        'id' => 'front_produits_col_fg',
//        'type' => 'color',
//        'default' => '#ffffff',
//        'css' => '.contrat-list li, .contrat-list li * { color: value }',
//    ));
//    $section->createOption(array(
//        'name' => __('Couleur de fond des produits', 'amapress'),
//        'id' => 'front_produits_col_bg',
//        'type' => 'color',
//        'default' => '#dd9933',
//        'css' => '.contrat-list li { background-color: value }',
//    ));
//    $section->createOption(array(
//        'name' => __('Couleur de bordure des produits', 'amapress'),
//        'id' => 'front_produits_col_brd',
//        'type' => 'color',
//        'default' => '#ffffff',
//        'css' => '.contrat-list li { border-color: value }',
//    ));

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Dates', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	$section->createOption( array(
		'name' => __( 'Pastille des dates', 'amapress' ),
		'type' => 'heading',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur du texte des dates', 'amapress' ),
		'id'      => 'agenda_dates_col_fg',
		'type'    => 'color',
		'default' => '#FFFFFF',
		'css'     => '.evt-date, .evt-date * { color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond des dates', 'amapress' ),
		'id'      => 'agenda_dates_col_bg',
		'type'    => 'color',
		'default' => '#000000',
		'css'     => '.evt-date { background-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure des dates', 'amapress' ),
		'id'      => 'agenda_dates_col_brd',
		'type'    => 'color',
		'default' => '',
		'css'     => '.evt-date { border: 1pt solid value }',
	) );

	$section->createOption( array(
		'name' => __( 'Contour des dates', 'amapress' ),
		'type' => 'heading',
	) );
	$section->createOption( array(
		'name'    => __( "Couleur de fond des dates", 'amapress' ),
		'id'      => "agenda_event_bg",
		'type'    => 'color',
		'default' => '',
		'css'     => '.event { background-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( "Couleur de bordure des évènements", 'amapress' ),
		'id'      => "agenda_event_brd",
		'type'    => 'color',
		'default' => '',
		'css'     => '.event { border: 1pt solid value }',
	) );

	foreach (
		array(
			'visite'             => 'Visite',
			'inter-recup'        => 'Récupération panier intermittent',
			'intermittence'      => 'Panier intermittent',
			'distribution'       => 'Distribution',
			'resp-distribution'  => 'Responsable distribution',
			'user-paiement'      => 'Encaissement de chèque',
			'assemblee_generale' => 'Assemblée générale',
			'amap_event'         => 'Evênement',
		) as $type => $name
	) {
		$section->createOption( array(
			'name' => __( $name, 'amapress' ),
			'type' => 'heading',
		) );
		$section->createOption( array(
			'name'    => __( 'Couleur du texte', 'amapress' ),
			'id'      => "agenda_{$type}_dates_col_fg",
			'type'    => 'color',
			'default' => '',
			'css'     => ".evt-date-type-{$type}, .evt-date-type-{$type} * { color: value }",
		) );
		$section->createOption( array(
			'name'    => __( 'Couleur de fond', 'amapress' ),
			'id'      => "agenda_{$type}_dates_col_bg",
			'type'    => 'color',
			'default' => '',
			'css'     => ".evt-date-type-{$type} { background-color: value }",
		) );
		$section->createOption( array(
			'name'    => __( 'Couleur de bordure', 'amapress' ),
			'id'      => "agenda_{$type}_dates_col_brd",
			'type'    => 'color',
			'default' => '',
			'css'     => ".evt-date-type-{$type} { border-color: value }",
		) );
	}


	$section = $titan->createCustomizer( array(
		'name'  => __( 'Lieux', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	//assemblées
	$section->createOption( array(
		'name'    => __( 'Couleur du texte des lieux', 'amapress' ),
		'id'      => 'agenda_lieux_col_fg',
		'type'    => 'color',
		'default' => '#FFFFFF',
		'css'     => '.evt-lieu .evt-lieu-cnt, .evt-lieu .evt-lieu-cnt * { color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur du picto des lieux', 'amapress' ),
		'id'      => 'agenda_lieux_picto_fg',
		'type'    => 'color',
		'default' => '#333333',
		'css'     => '.evt-lieu .evt-lieu-cnt i.fa, { color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond des lieux', 'amapress' ),
		'id'      => 'agenda_lieux_col_bg',
		'type'    => 'color',
		'default' => '#A6C79D',
		'css'     => '.evt-lieu .evt-lieu-cnt { background-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure des lieux', 'amapress' ),
		'id'      => 'agenda_lieux_col_brd',
		'type'    => 'color',
		'default' => '',
		'css'     => '.evt-lieu .evt-lieu-cnt { border-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de séparateur des lieux', 'amapress' ),
		'id'      => 'agenda_lieux_col_sep',
		'type'    => 'color',
		'default' => '#A6C79D',
		'css'     => '.evt-cnt-inner:nth-child(1n+2) { border-top-color: value }',
	) );

	$lieux = Amapress::get_lieux();

	foreach ( $lieux as $lieu ) {
		$section = $titan->createCustomizer( array(
			'name'  => __( $lieu->getTitle(), 'amapress' ),
			'panel' => 'Amapress Agenda',
		) );
		//assemblées
		$section->createOption( array(
			'name'    => __( 'Couleur du texte de ' . $lieu->getTitle(), 'amapress' ),
			'id'      => 'agenda_lieux_' . $lieu->ID . '_col_fg',
			'type'    => 'color',
			'default' => '',
			'css'     => '.evt-lieu.evt-lieu-' . $lieu->ID . ' .evt-lieu-cnt, .evt-lieu.evt-lieu-' . $lieu->ID . ' .evt-lieu-cnt * { color: value }',
		) );
		$section->createOption( array(
			'name'    => __( 'Couleur de fond de ' . $lieu->getTitle(), 'amapress' ),
			'id'      => 'agenda_lieux_' . $lieu->ID . '_col_bg',
			'type'    => 'color',
			'default' => '',
			'css'     => '.evt-lieu.evt-lieu-' . $lieu->ID . ' .evt-lieu-cnt { background-color: value }',
		) );
		$section->createOption( array(
			'name'    => __( 'Couleur de bordure de ' . $lieu->getTitle(), 'amapress' ),
			'id'      => 'agenda_lieux_' . $lieu->ID . '_col_brd',
			'type'    => 'color',
			'default' => '',
			'css'     => '.evt-lieu.evt-lieu-' . $lieu->ID . ' .evt-lieu-cnt { border-color: value }',
		) );
	}

//    $section = $titan->createCustomizer(array(
//        'name' => __('Général', 'amapress'),
//        'panel' => 'Amapress Agenda',
//    ));

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Responsables distribution', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	//responsables distribution
	$section->createOption( array(
		'name'    => __( 'Icône des responsables distribution', 'amapress' ),
		'id'      => 'agenda_resp_distrib_icon',
		'type'    => 'text',
		'default' => '',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur du texte des responsables distribution', 'amapress' ),
		'id'      => 'agenda_resp_distrib_col_fg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-resp-distrib, .agenda-resp-distrib * { color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond des responsables distribution', 'amapress' ),
		'id'      => 'agenda_resp_distrib_col_bg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-resp-distrib { background-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure des responsables distribution', 'amapress' ),
		'id'      => 'agenda_resp_distrib_col_brd',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-resp-distrib { border-color: value }',
	) );

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Visites à la ferme', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	//visites
	$section->createOption( array(
		'name'    => __( 'Icône des visites', 'amapress' ),
		'id'      => 'agenda_visite_icon',
		'type'    => 'text',
		'default' => 'dashicons dashicons-admin-post',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur du texte des visites', 'amapress' ),
		'id'      => 'agenda_visite_col_fg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-visite, .agenda-visite * { color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond des visites', 'amapress' ),
		'id'      => 'agenda_visite_col_bg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-visite { background-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure des visites', 'amapress' ),
		'id'      => 'agenda_visite_col_brd',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-visite { border-color: value }',
	) );

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Assemblées générales', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	//assemblées
	$section->createOption( array(
		'name'    => __( 'Icône des assemblées', 'amapress' ),
		'id'      => 'agenda_assemblee_icon',
		'type'    => 'text',
		'default' => 'dashicons dashicons-admin-post',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur du texte des assemblées', 'amapress' ),
		'id'      => 'agenda_assemblee_col_fg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-assemblee, .agenda-assemblee * { color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond des assemblées', 'amapress' ),
		'id'      => 'agenda_assemblee_col_bg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-assemblee { background-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure des assemblées', 'amapress' ),
		'id'      => 'agenda_assemblee_col_brd',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-assemblee { border-color: value }',
	) );

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Paiements', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	//paiements
	$section->createOption( array(
		'name'    => __( 'Icône des paiements', 'amapress' ),
		'id'      => 'agenda_contrat_paiement_icon',
		'type'    => 'text',
		'default' => 'dashicons dashicons-admin-post',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur du texte des paiements', 'amapress' ),
		'id'      => 'agenda_contrat_paiement_col_fg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-contrat-paiement, .agenda-contrat-paiement * { color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond des paiements', 'amapress' ),
		'id'      => 'agenda_contrat_paiement_col_bg',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-contrat-paiement { background-color: value }',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure des paiements', 'amapress' ),
		'id'      => 'agenda_contrat_paiement_col_brd',
		'type'    => 'color',
		'default' => '',
		'css'     => '.agenda-contrat-paiement { border-color: value }',
	) );

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Commandes', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	//paiements
	$section->createOption( array(
		'name'    => __( 'Icône des commandes', 'amapress' ),
		'id'      => 'agenda_commande_icon',
		'type'    => 'text',
		'default' => 'dashicons dashicons-admin-post',
	) );

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Contrats', 'amapress' ),
		'panel' => 'Amapress Agenda',
	) );
	//paiements
	$section->createOption( array(
		'name'    => __( 'Icône des contrats', 'amapress' ),
		'id'      => 'agenda_contrat_icon',
		'type'    => 'text',
		'default' => 'dashicons dashicons-admin-post',
	) );

	foreach ( $contrats as $contrat ) {
		$tit     = $contrat->getTitle();
		$id      = $contrat->ID; //get_post_meta($contrat->ID, 'amapress_contrat_instance_model', true);
		$section = $titan->createCustomizer( array(
			'name'  => __( 'Contrat ' . $tit, 'amapress' ),
			'panel' => 'Amapress Agenda',
		) );
		$section->createOption( array(
			'name'    => __( "Icône du contrat $tit", 'amapress' ),
			'id'      => "agenda_contrat_{$id}_icon",
			'type'    => 'text',
			'default' => '',
		) );
		$section->createOption( array(
			'name'    => __( "Couleur du texte du contrat $tit", 'amapress' ),
			'id'      => "agenda_contrat_{$id}_col_fg",
			'type'    => 'color',
			'default' => '',
			'css'     => '.agenda-contrat-' . $id . ',.agenda-contrat-' . $id . ' * { color: value }',
		) );
		$section->createOption( array(
			'name'    => __( "Couleur de fond du contrat $tit", 'amapress' ),
			'id'      => "agenda_contrat_{$id}_col_bg",
			'type'    => 'color',
			'default' => '',
			'css'     => '.agenda-contrat-' . $id . ' { background-color: value }',
		) );
		$section->createOption( array(
			'name'    => __( "Couleur de bordure du contrat $tit", 'amapress' ),
			'id'      => "agenda_contrat_{$id}_col_brd",
			'type'    => 'color',
			'default' => '',
			'css'     => '.agenda-contrat-' . $id . ' { border-color: value }',
		) );
	}

	$section = $titan->createCustomizer( array(
		'name'  => __( 'Post-It', 'amapress' ),
		'panel' => 'Amapress Presentation',
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur d\'entête', 'amapress' ),
		'id'      => "postit_header_col_fg",
		'type'    => 'color',
		'default' => '',
		'css'     => ".post-it h4 { color: value }",
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond d\'entête', 'amapress' ),
		'id'      => "postit_header_col_bg",
		'type'    => 'color',
		'default' => '',
		'css'     => ".post-it h4 { background-color: value }",
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de bordure', 'amapress' ),
		'id'      => "postit_col_brd",
		'type'    => 'color',
		'default' => '',
		'css'     => ".post-it { border-color: value }",
	) );
	$section->createOption( array(
		'name'    => __( 'Couleur de fond', 'amapress' ),
		'id'      => "postit_col_bg",
		'type'    => 'color',
		'default' => '',
		'css'     => ".post-it { background-color: value }",
	) );


	$section = $titan->createCustomizer( array(
		'name'  => __( 'Encadrés', 'amapress' ),
		'panel' => 'Amapress Presentation',
	) );
	$section->createOption( array(
		'name'    => __( 'Présentation de la production', 'amapress' ),
		'id'      => 'pres_producteur_title',
		'type'    => 'text',
		'default' => 'Présentation de la production',
	) );
	$section->createOption( array(
		'name'    => __( 'Présentation du contrat', 'amapress' ),
		'id'      => 'pres_contrat_title',
		'type'    => 'text',
		'default' => 'Présentation du contrat',
	) );
	$section->createOption( array(
		'name'    => __( 'Présentation des produits', 'amapress' ),
		'id'      => 'pres_produits_title',
		'type'    => 'text',
		'default' => 'Ses produits',
	) );
	$section->createOption( array(
		'name'    => __( 'Producteur', 'amapress' ),
		'id'      => 'producteur_title',
		'type'    => 'text',
		'default' => 'Producteur',
	) );

}