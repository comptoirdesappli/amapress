<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/generic.map.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/generic.pager.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/ics.fullcalendar.php' );
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/where.to.find.us.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/lieu.map.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.info.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.title.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.header.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.footer.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/user.info.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/user.map.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/user.avatar.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/recettes.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/produits.php');
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/next.events.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/drives.view.php' );

add_action( 'wp_ajax_get_years_from', function () {
	if ( ! isset( $_POST['year'] ) ) {
		return '';
	}
	$diff = intval( date( 'Y' ) ) - intval( $_POST['year'] );
	if ( $diff <= 0 ) {
		$diff = 1;
	}
	printf( _n( '%s an', '%s ans', $diff, 'amapress' ), number_format_i18n( $diff ) );
	die();
} );

add_filter( 'amapress_init', 'amapress_register_shortcodes' );
function amapress_register_shortcodes() {
	if ( 'active' == amapress_is_plugin_active( 'latest-post-shortcode' ) ) {
		amapress_register_shortcode( 'amapress-latest-posts', function ( $atts ) {
			$atts = shortcode_atts(
				[
					'limit'    => '5',
					'chrlimit' => '120',
				],
				$atts
			);

			return do_shortcode( '[latest-selected-content 
		limit=' . $atts['limit'] . ' display="title,date,excerpt-small" 
		chrlimit=' . $atts['chrlimit'] . ' url="yes_blank" image="thumbnail" 
		elements="3" type="post" status="publish" 
		orderby="dateD" show_extra="date_diff"]' );
		},
			[
				'desc' => __( 'Affiche une grille des articles récents', 'amapress' ),
				'args' => [
					'limit'    => __( '(5 par défaut) Nombre maximum d\'articles à afficher', 'amapress' ),
					'chrlimit' => __( '(120 par défaut) Nombre maximum de caractères du résumé de chaque article à afficher', 'amapress' ),
				]
			] );
	}

	$contrats_conf_link      = Amapress::makeLink(
		admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ),
		__( 'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats', 'amapress' )
	);
	$inscr_distrib_conf_link = Amapress::makeLink(
		admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_inscr_distrib_options_tab' ),
		__( 'Tableau de bord>Distributions>Configuration, onglet Inscription distribution', 'amapress' )
	);


	amapress_register_shortcode( 'years-since', function ( $atts ) {
		$atts = shortcode_atts(
			[ 'year' => '' ],
			$atts
		);
		$year = esc_attr( intval( $atts['year'] ) );

		return "<span class='amp-years-since' data-year='$year'></span>";
	},
		[
			'desc' => __( 'Affiche le nombre d\'années écoulée depuis une autre année', 'amapress' ),
			'args' => [
				'year' => __( 'Année de départ du décompte d\'années', 'amapress' )
			]
		] );
	amapress_register_shortcode( 'amapien-connecte-infos', function ( $atts, $content ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		return amapress_replace_mail_placeholders( $content,
			AmapressUser::getBy( amapress_current_user_id() ) );
	},
		[
			'desc' => __( 'Rempli les informations de l\'amapien connecté (dans le texte avec placeholders placé dans le shortcode)', 'amapress' ),
			'args' => [
				'contenu' => Amapress::makeLink( admin_url( 'admin.php?page=amapress_help_page&tab=amapien_placeholders' ),
					__( 'Placeholders disponibles', 'amapress' ), true, true ),
			]
		] );
	amapress_register_shortcode( 'amapress-panel', function ( $atts, $content ) {
		$atts = shortcode_atts(
			[
				'title'    => '',
				'esc_html' => true,
			],
			$atts
		);
		if ( Amapress::toBool( $atts['esc_html'] ) ) {
			return amapress_get_panel_start( $atts['title'] ) . $content . amapress_get_panel_end();
		} else {
			return amapress_get_panel_start_no_esc( $atts['title'] ) . $content . amapress_get_panel_end();
		}
	},
		[
			'desc' => __( 'Affiche un encadré avec titre', 'amapress' ),
			'args' => [
				'title'    => __( 'Titre de l\'encadré', 'amapress' ),
				'esc_html' => __( '(true par défaut) encoder le titre', 'amapress' )
			]
		] );
	amapress_register_shortcode( 'paged_gallery', 'amapress_generic_paged_gallery_shortcode',
		[
			'desc' => '',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'nous-trouver', 'amapress_where_to_find_us_shortcode',
		[
			'desc' => __( 'Carte des lieux de distributions', 'amapress' ),
			'args' => [
				'padding'  => __( '(0 par défaut) marge autours du centrage de la carte', 'amapress' ),
				'max_zoom' => __( '(18 par défaut, maximum) zoom maximal de la carte', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'recettes', 'amapress_recettes_shortcode',
		[
			'desc' => __( 'Gallerie des recettes', 'amapress' ),
			'args' => [
				'produits'    => __( 'Filtre de produits', 'amapress' ),
				'cat'         => __( 'Filtre de catégories', 'amapress' ),
				'cat__not_in' => __( 'Inverse filtre de catégories', 'amapress' ),
				'if_empty'    => __( '(Par défaut “Pas encore de recette”) Texte à afficher quand il n\'y a pas de recettes à afficher', 'amapress' ),
				'size'        => __( '(Par défaut “thumbnail”) Taille de l\'aperçu', 'amapress' ),
				'searchbox'   => __( '(Par défaut “true”) Afficher une barre de recherche', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'produits', 'amapress_produits_shortcode',
		[
			'desc' => __( 'Gallerie de produits', 'amapress' ),
			'args' => [
				'producteur'  => __( 'Filtre producteurs', 'amapress' ),
				'recette'     => __( 'Filtre recettes', 'amapress' ),
				'cat'         => __( 'Filtre catégories', 'amapress' ),
				'cat__not_in' => __( 'Inverse filtre catégories', 'amapress' ),
				'if_empty'    => __( '(Par défaut “Pas encore de produits”) Texte à afficher quand il n\'y a pas de recettes à afficher', 'amapress' ),
				'size'        => __( '(Par défaut “thumbnail”) Taille de l\'aperçu', 'amapress' ),
				'searchbox'   => __( '(Par défaut “true”) Afficher une barre de recherche', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'lieu-map', 'amapress_lieu_map_shortcode',
		[
			'desc' => __( 'Emplacement d\'un lieu (carte et StreetView)', 'amapress' ),
			'args' => [
				'lieu'     => __( 'Afficher la carte du lieu indiqué. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
				'mode'     => __( '(Par défaut “map”) Mode d’affichage. Si Gooogle est votre afficheur de carte, alors vous pouvez choisir : map, map+streetview ou streetview', 'amapress' ),
				'padding'  => __( '(0 par défaut) marge autours du centrage de la carte', 'amapress' ),
				'max_zoom' => __( '(18 par défaut, maximum) zoom maximal de la carte', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'user-map', 'amapress_user_map_shortcode',
		[
			'desc' => __( 'Emplacement d\'un amapien', 'amapress' ),
			'args' => [
				'padding'  => __( '(0 par défaut) marge autours du centrage de la carte', 'amapress' ),
				'max_zoom' => __( '(18 par défaut, maximum) zoom maximal de la carte', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'producteur-map', 'amapress_producteur_map_shortcode',
		[
			'desc' => __( 'Emplacement d\'un producteur', 'amapress' ),
			'args' => [
				'lieu'     => __( 'Afficher la carte du lieu indiqué. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
				'mode'     => __( '(Par défaut “map”) Mode d’affichage. Si Gooogle est votre afficheur de carte, alors vous pouvez choisir : map, map+streetview ou streetview', 'amapress' ),
				'padding'  => __( '(0 par défaut) marge autours du centrage de la carte', 'amapress' ),
				'max_zoom' => __( '(18 par défaut, maximum) zoom maximal de la carte', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'amapien-avatar', 'amapress_amapien_avatar_shortcode',
		[
			'desc' => '',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'histo-inscription-distrib', 'amapress_histo_inscription_distrib_shortcode',
		[
			'desc' => __( 'Historique d\'inscription des responsables aux distributions', 'amapress' ),
			'args' => [
				'show_email'          => __( '(Par défaut “default”) Afficher les emails', 'amapress' ),
				'show_tel'            => __( '(Par défaut “default”) Afficher les numéros de téléphones', 'amapress' ),
				'show_tel_fixe'       => __( '(Par défaut “default”) Afficher les numéros de téléphones fixes', 'amapress' ),
				'show_tel_mobile'     => __( '(Par défaut “default”) Afficher les numéros de téléphones mobiles', 'amapress' ),
				'show_adresse'        => __( '(Par défaut “false”) Afficher les adresses', 'amapress' ),
				'show_avatar'         => __( '(Par défaut “default”) Afficher les avatars des amapiens', 'amapress' ),
				'show_roles'          => __( '(Par défaut “false”) Afficher les rôles des membres du collectif', 'amapress' ),
				'show_title'          => __( '(Par défaut “true”) Afficher les noms des lieux', 'amapress' ),
				'past_weeks'          => __( '(Par défaut “5”) Nombre de semaines d’historique des distributions', 'amapress' ),
				'lieu'                => __( 'Filtre de lieu. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
				'scroll_y'            => sprintf( __( '(Configurable dans %s) Limite la hauteur à X pixels et permet la navigation verticale avec scroll dans la date de distributions', 'amapress' ), $inscr_distrib_conf_link ),
				'font_size'           => sprintf( __( '(Configurable dans %s) Taille relative du texte dans la vue en %% ou em', 'amapress' ), $inscr_distrib_conf_link ),
				'show_no_contrat'     => __( '(Par défaut “true”) Afficher un message "Pas de livraison" pour les dates sans livraison pour l\'amapien', 'amapress' ),
				'show_contrats_desc'  => __( '(Par défaut “true”) Afficher la liste des contrats pour chaque date ; si entier, limite le nombre de lignes affichées à ce nombre', 'amapress' ),
				'show_contrats_count' => __( '(Par défaut “false”) Afficher le nombre de contrats pour chaque date', 'amapress' ),
				'responsive'          => __( '(Par défaut scroll) Configuration du mode mobile/responsive : scroll (hauteur de la vue et largeur de colonnes fixes avec barres de défilement), auto (passage en mode pliant sur mobile, répartition en largeur sinon), true/false', 'amapress' )
			]
		] );
	amapress_register_shortcode( 'liste-inscription-distrib', function ( $args ) {
		$args         = shortcode_atts(
			[
				'lieu'       => 0,
				'max_dates'  => 52,
				'show_title' => 'false',
			],
			$args
		);
		$dist_lieu_id = 0;
		if ( ! empty( $args['lieu'] ) ) {
			$dist_lieu_id = Amapress::resolve_post_id( $dist_lieu_id, AmapressLieu_distribution::INTERNAL_POST_TYPE );

			return do_shortcode( '[inscription-distrib for_pdf=true show_title=' . $args['show_title'] . ' for_emargement=true show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=true max_dates=' . $args['max_dates'] . ' lieu=' . $dist_lieu_id . ']' );
		} else {
			$ret = '';
			foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
				$ret .= do_shortcode( '[inscription-distrib for_pdf=true show_title=' . $args['show_title'] . ' for_emargement=true show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=true max_dates=' . $args['max_dates'] . ' lieu=' . $lieu_id . ']' );
			}

			return $ret;
		}
	},
		[
			'desc' => __( 'Liste statique des inscrits des responsables aux distributions', 'amapress' ),
			'args' => [
				'show_title' => __( '(Par défaut “true”) Afficher les noms des lieux', 'amapress' ),
				'max_dates'  => sprintf( __( '(Configurable dans %s) Nombre maximum de distributions à venir à afficher', 'amapress' ), $inscr_distrib_conf_link ),
				'lieu'       => __( 'Filtre de lieu. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
			]
		] );
	amapress_register_shortcode( 'inscription-distrib', 'amapress_inscription_distrib_shortcode',
		[
			'desc' => __( 'Inscriptions comme responsable de distributions', 'amapress' ),
			'args' => [
				'show_past'                 => __( '(Par défaut “false”) Afficher les distributions passées', 'amapress' ),
				'show_next'                 => __( '(Par défaut “true”) Afficher les distributions à venir', 'amapress' ),
				'show_email'                => __( '(Par défaut “default”) Afficher les emails', 'amapress' ),
				'show_tel'                  => __( '(Par défaut “default”) Afficher les numéros de téléphones', 'amapress' ),
				'show_tel_fixe'             => __( '(Par défaut “default”) Afficher les numéros de téléphones fixes', 'amapress' ),
				'show_tel_mobile'           => __( '(Par défaut “default”) Afficher les numéros de téléphones mobiles', 'amapress' ),
				'show_adresse'              => __( '(Par défaut “false”) Afficher les adresses', 'amapress' ),
				'show_avatar'               => __( '(Par défaut “default”) Afficher les avatars des amapiens', 'amapress' ),
				'show_roles'                => __( '(Par défaut “false”) Afficher les rôles des membres du collectif', 'amapress' ),
				'show_title'                => __( '(Par défaut “true”) Afficher les noms des lieux', 'amapress' ),
				'past_weeks'                => __( '(Par défaut “5”) Nombre de semaines d’historique des distributions', 'amapress' ),
				'max_dates'                 => sprintf( __( '(Configurable dans %s) Nombre maximum de distributions à venir à afficher', 'amapress' ), $inscr_distrib_conf_link ),
				'lieu'                      => __( 'Filtre de lieu. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
				'column_date_width'         => sprintf( __( '(Configurable dans %s) Largeur de la colonne Dates/Produits', 'amapress' ), $inscr_distrib_conf_link ),
				'fixed_column_width'        => sprintf( __( '(Configurable dans %s) : fixe la largeur des colonnes Responsables ; en em ou px pour forcer une largeur fixe ; %% pour répartir la largeur de colonnes sur la largeur du tableau', 'amapress' ), $inscr_distrib_conf_link ),
				'scroll_y'                  => sprintf( __( '(Configurable dans %s) Limite la hauteur à X pixels et permet la navigation verticale avec scroll dans la date de distributions', 'amapress' ), $inscr_distrib_conf_link ),
				'font_size'                 => sprintf( __( '(Configurable dans %s) Taille relative du texte dans la vue en %% ou em ou rem', 'amapress' ), $inscr_distrib_conf_link ),
				'show_no_contrat'           => __( '(Par défaut “true”) Afficher un message "Pas de livraison" pour les dates sans livraison pour l\'amapien', 'amapress' ),
				'show_contrats_desc'        => __( '(Par défaut “true”) Afficher la liste des contrats pour chaque date ; si entier, limite le nombre de lignes affichées à ce nombre', 'amapress' ),
				'show_contrats_count'       => __( '(Par défaut “false”) Afficher le nombre de contrats pour chaque date', 'amapress' ),
				'inscr_all_distrib'         => __( '(Par défaut “false”) Autoriser tous les amapiens à s’inscrire même sur les lieux pour lesquels ils n’ont pas de contrat', 'amapress' ),
				'allow_resp_dist_manage'    => __( '(Par défaut “false”) Autoriser les responsables de distributions à gérer les inscriptions le temps de la semaine où ils sont inscrits', 'amapress' ),
				'prefer_inscr_button_first' => sprintf( __( '(Configurable dans %s) Placer les boutons d\'inscription en premier et les inscrits ensuite. False inverse.', 'amapress' ), $inscr_distrib_conf_link ),
				'allow_gardiens'            => __( '(Par défaut, true si actif) Autoriser l\'inscription des gardiens de paniers', 'amapress' ),
				'allow_gardiens_comments'   => __( '(Par défaut, true si actif) Autoriser les gardiens de paniers à mettre un commentaire avec leur inscription', 'amapress' ),
				'allow_slots'               => __( '(Par défault true) Autoriser le choix de créneaux', 'amapress' ),
				'show_responsables'         => __( '(Par défault true) Afficher les colonnes d\'inscription Responsable de distribution', 'amapress' ),
				'responsive'                => __( '(Par défaut scroll) Configuration du mode mobile/responsive : scroll (hauteur de la vue et largeur de colonnes fixes avec barres de défilement), auto (passage en mode pliant sur mobile, répartition en largeur sinon), true/false', 'amapress' )
			]
		] );
	amapress_register_shortcode( 'anon-inscription-distrib', 'amapress_inscription_distrib_shortcode',
		[
			'desc' => __( 'Inscriptions comme responsable de distributions', 'amapress' ),
			'args' => [
				'key'                       => __( '(Par exemple : ', 'amapress' ) . uniqid() . uniqid() . ') Clé de sécurisation de l\'accès à cet assistant d\'inscription aux distributions sans connexion',
				'show_past'                 => __( '(Par défaut “false”) Afficher les distributions passées', 'amapress' ),
				'show_next'                 => __( '(Par défaut “true”) Afficher les distributions à venir', 'amapress' ),
				'show_email'                => __( '(Par défaut “default”) Afficher les emails', 'amapress' ),
				'show_tel'                  => __( '(Par défaut “default”) Afficher les numéros de téléphones', 'amapress' ),
				'show_tel_fixe'             => __( '(Par défaut “default”) Afficher les numéros de téléphones fixes', 'amapress' ),
				'show_tel_mobile'           => __( '(Par défaut “default”) Afficher les numéros de téléphones mobiles', 'amapress' ),
				'show_adresse'              => __( '(Par défaut “false”) Afficher les adresses', 'amapress' ),
				'show_avatar'               => __( '(Par défaut “default”) Afficher les avatars des amapiens', 'amapress' ),
				'show_roles'                => __( '(Par défaut “false”) Afficher les rôles des membres du collectif', 'amapress' ),
				'show_title'                => __( '(Par défaut “true”) Afficher les noms des lieux', 'amapress' ),
				'past_weeks'                => __( '(Par défaut “5”) Nombre de semaines d’historique des distributions', 'amapress' ),
				'max_dates'                 => sprintf( __( '(Configurable dans %s) Nombre maximum de distributions à venir à afficher', 'amapress' ), $inscr_distrib_conf_link ),
				'lieu'                      => __( 'Filtre de lieu. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
				'column_date_width'         => sprintf( __( '(Configurable dans %s) Largeur de la colonne Dates/Produits', 'amapress' ), $inscr_distrib_conf_link ),
				'fixed_column_width'        => sprintf( __( '(Configurable dans %s) : fixe la largeur des colonnes Responsables ; en em ou px pour forcer une largeur fixe ; %% pour répartir la largeur de colonnes sur la largeur du tableau', 'amapress' ), $inscr_distrib_conf_link ),
				'scroll_y'                  => sprintf( __( '(Configurable dans %s) Limite la hauteur à X pixels et permet la navigation verticale avec scroll dans la date de distributions', 'amapress' ), $inscr_distrib_conf_link ),
				'font_size'                 => sprintf( __( '(Configurable dans %s) Taille relative du texte dans la vue en %% ou em ou rem', 'amapress' ), $inscr_distrib_conf_link ),
				'show_no_contrat'           => __( '(Par défaut “true”) Afficher un message "Pas de livraison" pour les dates sans livraison pour l\'amapien', 'amapress' ),
				'show_contrats_desc'        => __( '(Par défaut “true”) Afficher la liste des contrats pour chaque date ; si entier, limite le nombre de lignes affichées à ce nombre', 'amapress' ),
				'show_contrats_count'       => __( '(Par défaut “false”) Afficher le nombre de contrats pour chaque date', 'amapress' ),
				'inscr_all_distrib'         => __( '(Par défaut “false”) Autoriser tous les amapiens à s’inscrire même sur les lieux pour lesquels ils n’ont pas de contrat', 'amapress' ),
				'allow_resp_dist_manage'    => __( '(Par défaut “false”) Autoriser les responsables de distributions à gérer les inscriptions le temps de la semaine où ils sont inscrits', 'amapress' ),
				'prefer_inscr_button_first' => sprintf( __( '(Configurable dans %s) Placer les boutons d\'inscription en premier et les inscrits ensuite. False inverse.', 'amapress' ), $inscr_distrib_conf_link ),
				'allow_gardiens'            => __( '(Par défaut, true si actif) Autoriser l\'inscription des gardiens de paniers', 'amapress' ),
				'allow_gardiens_comments'   => __( '(Par défaut, true si actif) Autoriser les gardiens de paniers à mettre un commentaire avec leur inscription', 'amapress' ),
				'allow_slots'               => __( '(Par défault true) Autoriser le choix de créneaux', 'amapress' ),
				'show_responsables'         => __( '(Par défault true) Afficher les colonnes d\'inscription Responsable de distribution', 'amapress' ),
				'responsive'                => __( '(Par défaut scroll) Configuration du mode mobile/responsive : scroll (hauteur de la vue et largeur de colonnes fixes avec barres de défilement), auto (passage en mode pliant sur mobile, répartition en largeur sinon), true/false', 'amapress' )
			]
		] );
	amapress_register_shortcode( 'resp-distrib-contacts', 'amapress_responsables_distrib_shortcode',
		[
			'desc' => __( 'Contacts des responsables de distribution', 'amapress' ),
			'args' => [
				'distrib' => __( '(Par défaut "2") Afficher les responsables pour ce nombre de distributions à venir', 'amapress' )
			]
		] );
	amapress_register_shortcode( 'inscription-visite', 'amapress_inscription_visite_shortcode',
		[
			'desc' => __( 'Inscripions aux visites à la ferme', 'amapress' ),
			'args' => [
				'show_email'      => __( '(Par défaut “default”) Afficher les emails', 'amapress' ),
				'show_tel'        => __( '(Par défaut “default”) Afficher les numéros de téléphones', 'amapress' ),
				'show_tel_fixe'   => __( '(Par défaut “default”) Afficher les numéros de téléphones fixes', 'amapress' ),
				'show_tel_mobile' => __( '(Par défaut “default”) Afficher les numéros de téléphones mobiles', 'amapress' ),
				'show_adresse'    => __( '(Par défaut “default”) Afficher les adresses', 'amapress' ),
				'show_avatar'     => __( '(Par défaut “default”) Afficher les avatars des amapiens', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'inscription-amap-event', 'amapress_inscription_amap_event_shortcode',
		[
			'desc' => __( 'Inscriptions aux évènements AMAP', 'amapress' ),
			'args' => [
				'show_email'      => __( '(Par défaut “default”) Afficher les emails', 'amapress' ),
				'show_tel'        => __( '(Par défaut “default”) Afficher les numéros de téléphones', 'amapress' ),
				'show_tel_fixe'   => __( '(Par défaut “default”) Afficher les numéros de téléphones fixes', 'amapress' ),
				'show_tel_mobile' => __( '(Par défaut “default”) Afficher les numéros de téléphones mobiles', 'amapress' ),
				'show_adresse'    => __( '(Par défaut “default”) Afficher les adresses', 'amapress' ),
				'show_avatar'     => __( '(Par défaut “default”) Afficher les avatars des amapiens', 'amapress' ),
			]
		] );

//    amapress_register_shortcode('paniers-intermittents-list', 'amapress_intermittents_paniers_list_shortcode');
	amapress_register_shortcode( 'echanger-paniers-list', 'amapress_echanger_panier_shortcode',
		[
			'desc' => __( 'Liste d\'échange de paniers', 'amapress' ),
			'args' => [
			]
		] );
//

	amapress_register_shortcode( 'anon-extern-amapien-inscription', 'amapress_extern_user_inscription_shortcode',
		[
			'desc' => __( 'Inscription sans connexion comme amapien externe', 'amapress' ),
			'args' => [
				'key'      => __( '(Par exemple : ', 'amapress' ) . uniqid() . uniqid() . ') Clé de sécurisation de l\'accès à l\'inscription',
				'shorturl' => __( 'Url raccourcie de la page sur laquelle se trouve cet Assistant d\'inscription', 'amapress' ),
				'group'    => __( 'Groupe Amapien affecté lors de l\'inscription', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'anon-intermittents-inscription', 'amapress_intermittence_anon_inscription_shortcode',
		[
			'desc' => __( 'Inscription sans connexion à la liste des intermittents', 'amapress' ),
			'args' => [
				'key'      => sprintf( __( '(Par exemple : %s%s) Clé de sécurisation de l\'accès à l\'inscription à la liste des intermittents', 'amapress' ), uniqid(), uniqid() ),
				'shorturl' => __( 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'intermittents-inscription', 'amapress_intermittence_inscription_shortcode',
		[
			'desc' => __( 'Inscription d\'un amapien à la liste des intermittents', 'amapress' ),
			'args' => [
				'show_info' => '(Par défaut “yes”) Afficher les informations d\'inscription à la liste des intermittents',
			]
		] );
	amapress_register_shortcode( 'intermittents-desinscription', 'amapress_intermittence_desinscription_shortcode',
		[
			'desc' => __( 'Désinscription d\'un amapien à la liste des intermittents', 'amapress' ),
			'args' => [
			]
		] );


	amapress_register_shortcode( 'adhesion-request-count', 'amapress_adhesion_request_count_shortcode',
		[
			'desc' => __( 'Nombre de demandes d\'adhésions en attente', 'amapress' ),
			'args' => [
			]
		] );

	amapress_register_shortcode( 'amapress-post-its', 'amapress_postits_shortcode',
		[
			'desc' => __( 'Post-its des tâches courantes (listes émargement..)', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-ics-viewer', 'amapress_fullcalendar',
		[
			'desc' => __( 'Afficheur de calendrier ICAL/ICS', 'amapress' ),
			'args' => [
				'header_left'   => __( '(Par défaut “prev,next today”) Option de personnalisation de l\'entête partie gauche, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'header_center' => __( '(Par défaut “title”) Option de personnalisation de l\'entête partie centrale, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'header_right'  => __( '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\'entête partie droite, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'min_time'      => __( '(Par défaut “08:00:00”) Heure minimale affichée', 'amapress' ),
				'max_time'      => __( '(Par défaut “22:00:00”) Heure maximale affichée', 'amapress' ),
				'default_view'  => __( '(Par défaut “listMonth”) Type d’affichage fullcalendar : <a href="https://fullcalendar.io/docs/v3/month-view" target="_blank">month</a>, <a href="https://fullcalendar.io/docs/v3/list-view" target="_blank">listDay, listWeek, listMonth ou listYear</a>, <a href="https://fullcalendar.io/docs/v3/agenda-view" target="_blank">agendaDay ou agendaWeek</a>, <a href="https://fullcalendar.io/docs/v3/basic-view" target="_blank">basicDay ou basicWeek</a>', 'amapress' ),
				'url'           => __( 'Url du calendrier à afficher (ICS)', 'amapress' ),
				'icon_size'     => __( '(Par défaut, 1em) Taille des icônes des évènements', 'amapress' ),
				'hidden_days'   => __( '(Par défaut “”) Liste des jours de la semaine cachés (0 = dimanche)', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'amapress-amapien-agenda-viewer', function ( $atts ) {
		$atts        = shortcode_atts(
			[
				'since_days'    => 30,
				'header_left'   => 'prev,next today',
				'header_center' => 'title',
				'header_right'  => 'month,listMonth,listWeek',
				'min_time'      => '08:00:00',
				'max_time'      => '22:00:00',
				'icon_size'     => '1em',
				'default_view'  => 'listMonth',
				'hidden_days'   => '',
			],
			$atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( false, intval( $atts['since_days'] ) );

		return amapress_fullcalendar( $atts );
	},
		[
			'desc' => __( 'Calendrier de l\'amapien', 'amapress' ),
			'args' => [
				'since_days'    => __( '(Par défaut 30) Nombre de jours d\'historique de l\'agenda', 'amapress' ),
				'header_left'   => __( '(Par défaut “prev,next today”) Option de personnalisation de l\'entête partie gauche, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'header_center' => __( '(Par défaut “title”) Option de personnalisation de l\'entête partie centrale, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'header_right'  => __( '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\'entête partie droite, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'default_view'  => __( '(Par défaut “listMonth”) Type d’affichage fullcalendar : <a href="https://fullcalendar.io/docs/v3/month-view" target="_blank">month</a>, <a href="https://fullcalendar.io/docs/v3/list-view" target="_blank">listDay, listWeek, listMonth ou listYear</a>, <a href="https://fullcalendar.io/docs/v3/agenda-view" target="_blank">agendaDay ou agendaWeek</a>, <a href="https://fullcalendar.io/docs/v3/basic-view" target="_blank">basicDay ou basicWeek</a>', 'amapress' ),
				'min_time'      => __( '(Par défaut “08:00:00”) Heure minimale affichée', 'amapress' ),
				'max_time'      => __( '(Par défaut “22:00:00”) Heure maximale affichée', 'amapress' ),
				'icon_size'     => __( '(Par défaut, 1em) Taille des icônes des évènements', 'amapress' ),
				'hidden_days'   => __( '(Par défaut “”) Liste des jours de la semaine cachés (0 = dimanche)', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'amapress-public-agenda-viewer', function ( $atts ) {
		$atts        = shortcode_atts(
			[
				'since_days'    => 30,
				'header_left'   => 'prev,next today',
				'header_center' => 'title',
				'header_right'  => 'month,listMonth,listWeek',
				'min_time'      => '08:00:00',
				'max_time'      => '22:00:00',
				'icon_size'     => '1em',
				'default_view'  => 'listMonth',
				'hidden_days'   => '',
			],
			$atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( true, intval( $atts['since_days'] ) );
		amapress_consider_logged( false );
		$ret = amapress_fullcalendar( $atts );
		amapress_consider_logged( true );

		return $ret;
	},
		[
			'desc' => __( 'Calendrier publique de l\'AMAP', 'amapress' ),
			'args' => [
				'since_days'    => __( '(Par défaut 30) Nombre de jours d\'historique de l\'agenda', 'amapress' ),
				'header_left'   => __( '(Par défaut “prev,next today”) Option de personnalisation de l\'entête partie gauche, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'header_center' => __( '(Par défaut “title”) Option de personnalisation de l\'entête partie centrale, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'header_right'  => __( '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\'entête partie droite, voir <a href="https://fullcalendar.io/docs/v3/header" target="_blank">Options de fullcalendar</a>', 'amapress' ),
				'default_view'  => __( '(Par défaut “listMonth”) Type d’affichage fullcalendar : <a href="https://fullcalendar.io/docs/v3/month-view" target="_blank">month</a>, <a href="https://fullcalendar.io/docs/v3/list-view" target="_blank">listDay, listWeek, listMonth ou listYear</a>, <a href="https://fullcalendar.io/docs/v3/agenda-view" target="_blank">agendaDay ou agendaWeek</a>, <a href="https://fullcalendar.io/docs/v3/basic-view" target="_blank">basicDay ou basicWeek</a>', 'amapress' ),
				'min_time'      => __( '(Par défaut “08:00:00”) Heure minimale affichée', 'amapress' ),
				'max_time'      => __( '(Par défaut “22:00:00”) Heure maximale affichée', 'amapress' ),
				'icon_size'     => __( '(Par défaut, 1em) Taille des icônes des évènements', 'amapress' ),
				'hidden_days'   => __( '(Par défaut “”) Liste des jours de la semaine cachés (0 = dimanche)', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'amapien-adhesions', 'amapress_display_user_adhesions_shortcode',
		[
			'desc' => __( 'Liste des inscriptions aux contrats pour un amapien', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapien-edit-infos', 'amapress_edit_user_info_shortcode',
		[
			'desc' => __( 'Permet à un amapien de modifier ses coordonnées', 'amapress' ),
			'args' => [
				'max_cofoyers'          => sprintf( __( '(Configurable dans %s) Nombre maximum de membres du foyer', 'amapress' ), $contrats_conf_link ),
				'edit_names'            => __( '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription', 'amapress' ),
				'mob_phone_required'    => sprintf( __( '(Configurable dans %s) Téléphones (mobiles) requis', 'amapress' ), $contrats_conf_link ),
				'address_required'      => __( '(false par défaut) Adresse requise', 'amapress' ),
				'show_adherents_infos'  => __( '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents', 'amapress' ),
				'allow_remove_cofoyers' => __( '(true par défaut) Autoriser la suppression des membres du foyers', 'amapress' ),
				'show_cofoyers_address' => __( '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer', 'amapress' ),
				'allow_trombi_decline'  => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'           => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'amapien-messages', 'amapress_user_messages_shortcode' );
	amapress_register_shortcode( 'amapien-messages-count', 'amapress_user_messages_count_shortcode' );
	amapress_register_shortcode( 'amapien-paniers-intermittents', 'amapress_user_paniers_intermittents_shortcode',
		[
			'desc' => __( 'Paniers proposés/échangés par un amapien', 'amapress' ),
			'args' => [
				'show_history' => __( '(Par défaut “false”) Afficher l\'historique des échanges de paniers de l\'amapien/intermittent', 'amapress' ),
				'history_days' => __( '(Par défaut “180”) Nombre de jour de l\'historique', 'amapress' ),
				'show_futur'   => __( '(Par défaut “true”) Afficher les échanges à venir', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'amapien-paniers-intermittents-count', 'amapress_user_paniers_intermittents_count_shortcode',
		[
			'desc' => __( 'Nombre de paniers proposés/échangés par un amapien', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'les-paniers-intermittents', 'amapress_all_paniers_intermittents_shortcode',
		[
			'desc' => __( 'Paniers disponibles sur la liste des intermittents', 'amapress' ),
			'args' => [
				'contrat'                 => __( 'Permet de filtrer les contrats pour lesquels les paneirs à échanger sont affichés', 'amapress' ),
				'allow_amapiens'          => __( '(Par défaut “true”) Autoriser les amapiens à réserver des paniers', 'amapress' ),
				'check_adhesion'          => sprintf( __( '(Par défaut %s, configurable dans %s) Autoriser la réservation de paniers uniquement si l\'intermittent a une adhésion à l\'AMAP', 'amapress' ),
					Amapress::toBool( Amapress::getOption( 'intermit_adhesion_req' ) ) ? 'true' : 'false',
					Amapress::makeLink( admin_url( 'admin.php?page=amapress_intermit_conf_opt_page&tab=amapress_intermit_conf_tab' ),
						__( 'Tableau de bord>Espace intermittents>Configuration, onglet Configuration de l\'espace intermittents', 'amapress' ), true, true ) ),
				'check_adhesion_received' => __( '(Par défaut false) Autoriser la réservation de paniers uniquement si l\'adhésion est validée', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'les-paniers-intermittents-count', 'amapress_all_paniers_intermittents_count_shortcode',
		[
			'desc' => __( 'Nombre de paniers disponibles sur la liste des intermittents', 'amapress' ),
			'args' => [
			]
		] );

	amapress_register_shortcode( 'mes-contrats', 'amapress_mes_contrats',
		[
			'desc' => __( 'Permet l\'inscription aux contrats complémentaires en cours d\'année', 'amapress' ),
			'args' => [
				'ignore_renouv_delta'                 => __( '(booléen, true par défaut) : ignorer la marge de renouvellement des contrats terminés', 'amapress' ),
				'allow_inscriptions'                  => __( '(booléen, true par défaut) : autorise l\'inscription aux contrats', 'amapress' ),
				'allow_inscriptions_without_adhesion' => __( '(booléen, false par défaut) : autorise l\'inscriptions aux contrats même si l\'amapien n\'a pas d\'adhésion en cours', 'amapress' ),
				'adhesion_category'                   => __( '("" par défaut) filtrage de la période d\'adhésion par catégorie', 'amapress' ),
				'check_adhesion_received'             => sprintf( __( '(Configurable dans %s) : vérifier que l\'adhésion a été validée/reçue avant de permettre l\'inscription aux contrats', 'amapress' ), $contrats_conf_link ),
				'check_adhesion_received_or_previous' => sprintf( __( '(Configurable dans %s) : vérifier que l\'adhésion a été validée/reçue ou qu\'une adhésion précédente a été validée avant de permettre l\'inscription aux contrats', 'amapress' ), $contrats_conf_link ),
				'allow_adhesion'                      => __( '(booléen, true par défaut) : autorise l\'adhésion à l\'AMAP', 'amapress' ),
				'filter_multi_contrat'                => __( '(booléen, false par défaut) : en cas de variante de contrat Semaine A/B/AB, ne pas autoriser un amapien à s\'inscrire à plusieurs variantes', 'amapress' ),
				'allow_classic_adhesion'              => __( '(booléen, true par défaut) : autoriser l\'adhésion à l\'AMAP classique (chèque...) quand l\'adhésion HelloAsso est aussi possible', 'amapress' ),
				'agreement'                           => __( '(booléen, true par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ', 'amapress' ) . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Tableau de bord > Gestion Adhésions > onglet Assistant - Adhésion en ligne', 'amapress' ) ) . ')',
				'agreement_new_only'                  => __( '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP uniquement pour les nouveaux adhérents', 'amapress' ),
				'check_principal'                     => __( '(booléen, true par défaut) : vérifier qu\'un contrat principal est actif. Peut être désactivé globalement dans ', 'amapress' ) . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ), __( 'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats', 'amapress' ) ),
				'send_adhesion_confirm'               => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP', 'amapress' ),
				'send_adhesion_bulletin'              => __( '(booléen, true par défaut) : envoyer le bulletin d\'adhésion avec la confirmation d\'adhésion', 'amapress' ),
				'allow_inscription_all_dates'         => __( '(booléen, false par défaut) : autoriser l\'inscription à partir de toutes les dates, y compris celles après la date de clôture du contrat', 'amapress' ),
				'send_contrat_confirm'                => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour chacune de ses inscriptions aux contrats', 'amapress' ),
				'send_referents'                      => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux référents', 'amapress' ),
				'send_tresoriers'                     => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers', 'amapress' ),
				'adhesion_shift_weeks'                => sprintf( __( '(Configurable dans %s) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion', 'amapress' ), $contrats_conf_link ),
				'paniers_modulables_editor_height'    => __( '(350 par défaut) Hauteur de l\'éditeur de paniers modulables (en px ou sinon préciser l\'unité)', 'amapress' ),
				'custom_checks_label'                 => __( '("Options : " par défaut) Label des cases à cocher personnalisées', 'amapress' ),
				'allow_adhesion_lieu'                 => __( '(booléen, false par défaut) : permettre de choisir son lieu de distribution souhaité dès l\'adhésion', 'amapress' ),
				'allow_adhesion_message'              => __( '(booléen, false par défaut) : permettre d\'ajouter un message pour le trésorier lors de l\'adhésion', 'amapress' ),
				'show_details_button'                 => __( '(false par défaut) Afficher un bouton Détails pour accéder aux détails des inscriptions au lieu de les afficher directement dans la liste', 'amapress' ),
				'show_adherents_infos'                => __( '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents', 'amapress' ),
				'show_adhesion_infos'                 => __( '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin', 'amapress' ),
				'contact_referents'                   => __( '(true par défaut) Affiche un lien de contact des référents dans la liste des contrats déjà souscrit (étape 4/8)', 'amapress' ),
				'before_close_hours'                  => sprintf( __( '(Configurable dans %s) Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant le jour de distribution', 'amapress' ), $contrats_conf_link ),
				'paiements_info_required'             => __( '(false par défaut) Rendre la saisie de la banque et de l\'émetteur des règlements obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'paiements_numero_required'           => __( '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'allow_trombi_decline'                => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'                         => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
				'max_produit_label_width'             => __( '(par défaut, 10em) Largeur maximal de la colonne Produit pour les inscriptions Paniers Modulables', 'amapress' ),
				'show_delivery_details'               => __( '(false par défaut) Afficher un lien Récapitulatif des livraisons', 'amapress' ),
				'show_due_amounts'                    => __( '(false par défaut) Afficher un lien Récapitulatif des sommes dues', 'amapress' ),
				'show_calendar_delivs'                => __( '(false par défaut) Afficher un lien Calendrier des livraisons', 'amapress' ),
				'allow_remove_coadhs'                 => __( '(false par défaut) Autoriser la suppression des co-adhérents', 'amapress' ),
				'allow_remove_cofoyers'               => __( '(true par défaut) Autoriser la suppression des membres du foyers', 'amapress' ),
				'show_coadherents_address'            => __( '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents', 'amapress' ),
				'show_cofoyers_address'               => __( '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer', 'amapress' ),
				'show_only_subscribable_inscriptions' => __( '(false par défaut) Afficher les inscriptions à venir uniquement', 'amapress' ),
				'include_contrat_subnames'            => __( '("" par défaut, séparateur virgule) Inclure uniquement les contrats ayant l\'un des Nom complémentaires précisés', 'amapress' ),
				'exclude_contrat_subnames'            => __( '("" par défaut, séparateur virgule) Exclure les contrats ayant l\'un des Nom complémentaires précisés', 'amapress' ),
				'show_current_inscriptions'           => __( '(true par défaut) Afficher les inscriptions en cours et à venir', 'amapress' ),
				'show_editable_inscriptions'          => __( '(true par défaut) Afficher les inscriptions encore éditables', 'amapress' ),
				'show_close_date'                     => __( '(false par défaut) Afficher la date de clôture des inscriptions en ligne pour chaque contrat', 'amapress' ),
				'show_max_deliv_dates'                => __( '(3 par défaut) Afficher les dates de livraison dans la liste des contrats pour les contrats jusqu\'à X dates', 'amapress' ),
				'use_quantite_tables'                 => __( '(false par défaut) (Paniers modulables) Afficher les quantités en tableaux (date en ligne, quantités en colonnes)', 'amapress' ),
				'show_modify_coords'                  => __( '(true par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer dans l\'étape 4/8 contrats', 'amapress' ),
				'use_contrat_term'                    => __( '(true par défaut) Utiliser le terme Contrat si true et Commande si false', 'amapress' ),
				'sort_contrats'                       => __( '(title par défaut) Ordre d\'affichage des contrats/commandes ouvertes aux inscriptions : title, inscr_start, inscr_end, contrat_start', 'amapress' ),
				'only_contrats'                       => __( 'Filtrage des contrats affichés (par ID). Permet, par exemple, de faire une page dédiée aux paniers modulables et commandes', 'amapress' ) .
				                                         '<br/>' . __( 'Valeurs possibles: ', 'amapress' ) . implode( ' ; ', array_map( function ( $c ) {
						/** @var AmapressContrat $c */
						return sprintf( __( '%d (%s)', 'amapress' ), $c->ID, $c->getTitle() );
					}, AmapressContrats::get_contrats() ) ),
			]
		] );
	amapress_register_shortcode( 'inscription-en-ligne', 'amapress_self_inscription',
		[
			'desc' => __( 'Permet les inscriptions en ligne (amapien non connecté et nouveaux, sécurisée par une clé secrète)', 'amapress' ),
			'args' => [
				'key'                                 => sprintf( __( '(Par exemple : %s%s) Clé de sécurisation de l\'accès à l\'Assistant de Préinscription en ligne. Utilisez key=public pour permettre un accès sans clé', 'amapress' ), uniqid(), uniqid() ),
				'use_steps_nums'                      => __( '(booléen, true par défaut) : afficher les numéros d\'étapes', 'amapress' ),
				'allow_new_mail'                      => __( '(booléen, true par défaut): autoriser l\'inscription avec des emails inconnus/non associés à des comptes amapiens existants', 'amapress' ),
				'allow_existing_mail_for_public'      => __( '(booléen, false par défaut): autoriser l\'inscription avec des emails de comptes amapiens existants (pour la version sans clé/public)', 'amapress' ),
				'filter_multi_contrat'                => __( '(booléen, false par défaut) : en cas de variante de contrat Semaine A/B/AB, ne pas autoriser un amapien à s\'inscrire à plusieurs variantes', 'amapress' ),
				'agreement'                           => __( '(booléen, true par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ', 'amapress' ) . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Tableau de bord > Gestion Adhésions > onglet Assistant - Adhésion en ligne', 'amapress' ) ) . ')',
				'agreement_new_only'                  => __( '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP uniquement pour les nouveaux adhérents', 'amapress' ),
				'adhesion_category'                   => __( '("" par défaut) filtrage de la période d\'adhésion par catégorie', 'amapress' ),
				'check_principal'                     => sprintf( __( '(booléen, true par défaut) : vérifier qu\'un contrat principal est actif. Peut être désactivé globalement dans %s', 'amapress' ), Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ), __( 'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats', 'amapress' ) ) ),
				'check_adhesion_received'             => sprintf( __( '(Configurable dans %s) : vérifier que l\'adhésion a été validée/reçue avant de permettre l\'inscription aux contrats', 'amapress' ), $contrats_conf_link ),
				'check_adhesion_received_or_previous' => sprintf( __( '(Configurable dans %s) : vérifier que l\'adhésion a été validée/reçue ou qu\'une adhésion précédente a été validée avant de permettre l\'inscription aux contrats', 'amapress' ), $contrats_conf_link ),
				'adhesion'                            => __( '(booléen, true par défaut) : afficher une étape Adhésion à l\'AMAP', 'amapress' ),
				'allow_inscription_all_dates'         => __( '(booléen, false par défaut) : autoriser l\'inscription à partir de toutes les dates, y compris celles après la date de clôture du contrat', 'amapress' ),
				'send_adhesion_confirm'               => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP', 'amapress' ),
				'send_adhesion_bulletin'              => __( '(booléen, true par défaut) : envoyer le bulletin d\'adhésion avec la confirmation d\'adhésion', 'amapress' ),
				'send_contrat_confirm'                => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour chacune de ses inscriptions aux contrats', 'amapress' ),
				'send_referents'                      => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux référents', 'amapress' ),
				'send_tresoriers'                     => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers', 'amapress' ),
				'edit_names'                          => __( '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription', 'amapress' ),
				'send_welcome'                        => __( '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens', 'amapress' ),
				'sort_contrats'                       => __( '(title par défaut) Ordre d\'affichage des contrats/commandes ouvertes aux inscriptions : title, inscr_start, inscr_end, contrat_start', 'amapress' ),
				'only_contrats'                       => __( 'Filtrage des contrats affichés (par ID). Permet de faire une page dédiée à l\'inscription à un/plusieurs contrat(s) donné(s) avec une autre clé', 'amapress' ) .
				                                         '<br/>' . __( 'Valeurs possibles: ', 'amapress' ) . implode( ' ; ', array_map( function ( $c ) {
						/** @var AmapressContrat $c */
						return sprintf( __( '%d (%s)', 'amapress' ), $c->ID, $c->getTitle() );
					}, AmapressContrats::get_contrats() ) ),
				'shorturl'                            => __( 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne', 'amapress' ),
				'adhesion_shift_weeks'                => sprintf( __( '(Configurable dans %s) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion', 'amapress' ), $contrats_conf_link ),
				'max_coadherents'                     => sprintf( __( '(Configurable dans %s) Nombre maximum de co-adhérents', 'amapress' ), $contrats_conf_link ),
				'max_cofoyers'                        => sprintf( __( '(Configurable dans %s) Nombre maximum de membres du foyer', 'amapress' ), $contrats_conf_link ),
				'mob_phone_required'                  => sprintf( __( '(Configurable dans %s) Téléphones (mobiles) requis', 'amapress' ), $contrats_conf_link ),
				'address_required'                    => __( '(false par défaut) Adresse requise', 'amapress' ),
				'track_no_renews'                     => __( '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1', 'amapress' ),
				'track_no_renews_email'               => __( '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse', 'amapress' ),
				'send_no_renews_message'              => __( '(false par défaut) Envoyer un message de non renouvellement à l\'amapien', 'amapress' ),
				'notify_email'                        => __( '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)', 'amapress' ),
				'paniers_modulables_editor_height'    => __( '(350 par défaut) Hauteur de l\'éditeur de paniers modulables (en px ou sinon préciser l\'unité)', 'amapress' ),
				'custom_checks_label'                 => __( '("Options : " par défaut) Label des cases à cocher personnalisées', 'amapress' ),
				'allow_adhesion_lieu'                 => __( '(booléen, false par défaut) : permettre de choisir son lieu de distribution souhaité dès l\'adhésion', 'amapress' ),
				'allow_adhesion_message'              => __( '(booléen, false par défaut) : permettre d\'ajouter un message pour le trésorier lors de l\'adhésion', 'amapress' ),
				'show_details_button'                 => __( '(false par défaut) Afficher un bouton Détails pour accéder aux détails des inscriptions au lieu de les afficher directement dans la liste', 'amapress' ),
				'show_adherents_infos'                => __( '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents', 'amapress' ),
				'show_adhesion_infos'                 => __( '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin', 'amapress' ),
				'allow_coadherents_inscription'       => __( '(true par défaut) Autoriser l\'inscription aux contrats par les co-adhérents', 'amapress' ),
				'allow_coadherents_access'            => __( '(true par défaut) Autoriser l\accès aux co-adhérents', 'amapress' ),
				'allow_coadherents_adhesion'          => __( '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents', 'amapress' ),
				'allow_remove_coadhs'                 => __( '(false par défaut) Autoriser la suppression des co-adhérents', 'amapress' ),
				'allow_remove_cofoyers'               => __( '(true par défaut) Autoriser la suppression des membres du foyers', 'amapress' ),
				'show_coadherents_address'            => __( '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents', 'amapress' ),
				'show_cofoyers_address'               => __( '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer', 'amapress' ),
				'show_delivery_details'               => __( '(false par défaut) Afficher un lien Récapitulatif des livraisons', 'amapress' ),
				'show_calendar_delivs'                => __( '(false par défaut) Afficher un lien Calendrier des livraisons', 'amapress' ),
				'include_contrat_subnames'            => __( '("" par défaut, séparateur virgule) Inclure uniquement les contrats ayant l\'un des Nom complémentaires précisés', 'amapress' ),
				'exclude_contrat_subnames'            => __( '("" par défaut, séparateur virgule) Exclure les contrats ayant l\'un des Nom complémentaires précisés', 'amapress' ),
				'show_current_inscriptions'           => __( '(true par défaut) Afficher les inscriptions en cours et à venir', 'amapress' ),
				'show_only_subscribable_inscriptions' => __( '(true par défaut) Afficher les inscriptions à venir uniquement', 'amapress' ),
				'show_editable_inscriptions'          => __( '(true par défaut) Afficher les inscriptions encore éditables', 'amapress' ),
				'show_close_date'                     => __( '(false par défaut) Afficher la date de clôture des inscriptions en ligne pour chaque contrat', 'amapress' ),
				'show_max_deliv_dates'                => __( '(3 par défaut) Afficher les dates de livraison dans la liste des contrats pour les contrats jusqu\'à X dates', 'amapress' ),
				'use_quantite_tables'                 => __( '(false par défaut) (Paniers modulables) Afficher les quantités en tableaux (date en ligne, quantités en colonnes)', 'amapress' ),
				'show_due_amounts'                    => __( '(false par défaut) Afficher un lien Récapitulatif des sommes dues', 'amapress' ),
				'show_modify_coords'                  => __( '(false par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer dans l\'étape 4/8 contrats', 'amapress' ),
				'contact_referents'                   => __( '(true par défaut) Affiche un lien de contact des référents dans la liste des contrats déjà souscrit (étape 4/8)', 'amapress' ),
				'before_close_hours'                  => sprintf( __( '(Configurable dans %s) Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant le jour de distribution', 'amapress' ), $contrats_conf_link ),
				'paiements_info_required'             => __( '(false par défaut) Rendre la saisie de la banque et de l\'émetteur des règlements obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'paiements_numero_required'           => __( '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'allow_trombi_decline'                => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'                         => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
				'email'                               => __( '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème', 'amapress' ),
				'max_produit_label_width'             => __( '(par défaut, 10em) Largeur maximal de la colonne Produit pour les inscriptions Paniers Modulables', 'amapress' ),
				'use_contrat_term'                    => __( '(true par défaut) Utiliser le terme Contrat si true et Commande si false', 'amapress' ),
				'allow_adhesion_alone'                => __( '(false par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription', 'amapress' ),
				'check_honeypots'                     => __( '(true par défaut) Détecter et bloquer les bots de spam pour une utilisation publique (key=public) de l\'assistant', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'inscription-en-ligne-connecte', 'amapress_logged_self_inscription',
		[
			'desc' => __( 'Permet les inscriptions en ligne (amapien connecté)', 'amapress' ),
			'args' => [
				'filter_multi_contrat'                => __( '(booléen, false par défaut) : en cas de variante de contrat Semaine A/B/AB, ne pas autoriser un amapien à s\'inscrire à plusieurs variantes', 'amapress' ),
				'use_steps_nums'                      => __( '(booléen, true par défaut) : afficher les numéros d\'étapes', 'amapress' ),
				'allow_new_mail'                      => __( '(booléen, true par défaut): autoriser l\'inscription avec des emails inconnus/non associés à des comptes amapiens existants', 'amapress' ),
				'allow_existing_mail_for_public'      => __( '(booléen, false par défaut): autoriser l\'inscription avec des emails de comptes amapiens existants (pour la version sans clé/public)', 'amapress' ),
				'allow_classic_adhesion'              => __( '(booléen, true par défaut) : autoriser l\'adhésion à l\'AMAP classique (chèque...) quand l\'adhésion HelloAsso est aussi possible', 'amapress' ),
				'agreement'                           => sprintf( __( '(booléen, true par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans %s)', 'amapress' ), Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Tableau de bord > Gestion Adhésions > onglet Assistant - Adhésion en ligne', 'amapress' ) ) ),
				'agreement_new_only'                  => __( '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP uniquement pour les nouveaux adhérents', 'amapress' ),
				'adhesion_category'                   => __( '("" par défaut) filtrage de la période d\'adhésion par catégorie', 'amapress' ),
				'check_principal'                     => __( '(booléen, true par défaut) : vérifier qu\'un contrat principal est actif. Peut être désactivé globalement dans ', 'amapress' ) . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ), __( 'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats', 'amapress' ) ),
				'check_adhesion_received'             => sprintf( __( '(Configurable dans %s) : vérifier que l\'adhésion a été validée/reçue avant de permettre l\'inscription aux contrats', 'amapress' ), $contrats_conf_link ),
				'check_adhesion_received_or_previous' => sprintf( __( '(Configurable dans %s) : vérifier que l\'adhésion a été validée/reçue ou qu\'une adhésion précédente a été validée avant de permettre l\'inscription aux contrats', 'amapress' ), $contrats_conf_link ),
				'allow_inscriptions_without_adhesion' => __( '(booléen, false par défaut) : autorise l\'inscriptions aux contrats même si l\'amapien n\'a pas d\'adhésion en cours', 'amapress' ),
				'adhesion'                            => __( '(booléen, false par défaut) : afficher une étape Adhésion à l\'AMAP', 'amapress' ),
				'allow_inscription_all_dates'         => __( '(booléen, false par défaut) : autoriser l\'inscription à partir de toutes les dates, y compris celles après la date de clôture du contrat', 'amapress' ),
				'send_adhesion_confirm'               => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP', 'amapress' ),
				'send_adhesion_bulletin'              => __( '(booléen, true par défaut) : envoyer le bulletin d\'adhésion avec la confirmation d\'adhésion', 'amapress' ),
				'send_contrat_confirm'                => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour chacune de ses inscriptions aux contrats', 'amapress' ),
				'send_referents'                      => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux référents', 'amapress' ),
				'send_tresoriers'                     => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers', 'amapress' ),
				'edit_names'                          => __( '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription', 'amapress' ),
				'sort_contrats'                       => __( '(title par défaut) Ordre d\'affichage des contrats/commandes ouvertes aux inscriptions : title, inscr_start, inscr_end, contrat_start', 'amapress' ),
				'only_contrats'                       => __( 'Filtrage des contrats affichés (par ID). Permet de faire une page dédiée à l\'inscription à un/plusieurs contrat(s) donné(s) ou commande(s)', 'amapress' ) .
				                                         '<br/>' . __( 'Valeurs possibles: ', 'amapress' ) . implode( ' ; ', array_map( function ( $c ) {
						/** @var AmapressContrat $c */
						return sprintf( __( '%d (%s)', 'amapress' ), $c->ID, $c->getTitle() );
					}, AmapressContrats::get_contrats() ) ),
				'adhesion_shift_weeks'                => sprintf( __( '(Configurable dans %s) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion', 'amapress' ), $contrats_conf_link ),
				'max_coadherents'                     => sprintf( __( '(Configurable dans %s) Nombre maximum de co-adhérents', 'amapress' ), $contrats_conf_link ),
				'max_cofoyers'                        => sprintf( __( '(Configurable dans %s) Nombre maximum de membres du foyer', 'amapress' ), $contrats_conf_link ),
				'mob_phone_required'                  => sprintf( __( '(Configurable dans %s) Téléphones (mobiles) requis', 'amapress' ), $contrats_conf_link ),
				'address_required'                    => __( '(false par défaut) Adresse requise', 'amapress' ),
				'track_no_renews'                     => __( '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1', 'amapress' ),
				'track_no_renews_email'               => __( '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse', 'amapress' ),
				'send_no_renews_message'              => __( '(false par défaut) Envoyer un message de non renouvellement à l\'amapien', 'amapress' ),
				'notify_email'                        => __( '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)', 'amapress' ),
				'paniers_modulables_editor_height'    => __( '(350 par défaut) Hauteur de l\'éditeur de paniers modulables (en px ou sinon préciser l\'unité)', 'amapress' ),
				'custom_checks_label'                 => __( '("Options : " par défaut) Label des cases à cocher personnalisées', 'amapress' ),
				'allow_adhesion_lieu'                 => __( '(booléen, false par défaut) : permettre de choisir son lieu de distribution souhaité dès l\'adhésion', 'amapress' ),
				'allow_adhesion_message'              => __( '(booléen, false par défaut) : permettre d\'ajouter un message pour le trésorier lors de l\'adhésion', 'amapress' ),
				'show_details_button'                 => __( '(false par défaut) Afficher un bouton Détails pour accéder aux détails des inscriptions au lieu de les afficher directement dans la liste', 'amapress' ),
				'show_adherents_infos'                => __( '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents', 'amapress' ),
				'show_adhesion_infos'                 => __( '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin', 'amapress' ),
				'allow_coadherents_inscription'       => __( '(true par défaut) Autoriser l\'inscription aux contrats par les co-adhérents', 'amapress' ),
				'allow_coadherents_access'            => __( '(true par défaut) Autoriser l\accès aux co-adhérents', 'amapress' ),
				'allow_coadherents_adhesion'          => __( '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents', 'amapress' ),
				'allow_remove_coadhs'                 => __( '(false par défaut) Autoriser la suppression des co-adhérents', 'amapress' ),
				'allow_remove_cofoyers'               => __( '(true par défaut) Autoriser la suppression des membres du foyers', 'amapress' ),
				'show_coadherents_address'            => __( '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents', 'amapress' ),
				'show_cofoyers_address'               => __( '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer', 'amapress' ),
				'show_delivery_details'               => __( '(false par défaut) Afficher un lien Récapitulatif des livraisons', 'amapress' ),
				'show_due_amounts'                    => __( '(false par défaut) Afficher un lien Récapitulatif des sommes dues', 'amapress' ),
				'show_modify_coords'                  => __( '(true par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer dans l\'étape 4/8 contrats', 'amapress' ),
				'show_calendar_delivs'                => __( '(false par défaut) Afficher un lien Calendrier des livraisons', 'amapress' ),
				'show_only_subscribable_inscriptions' => __( '(false par défaut) Afficher les inscriptions à venir uniquement', 'amapress' ),
				'include_contrat_subnames'            => __( '("" par défaut, séparateur virgule) Inclure uniquement les contrats ayant l\'un des Nom complémentaires précisés', 'amapress' ),
				'exclude_contrat_subnames'            => __( '("" par défaut, séparateur virgule) Exclure les contrats ayant l\'un des Nom complémentaires précisés', 'amapress' ),
				'show_current_inscriptions'           => __( '(true par défaut) Afficher les inscriptions en cours et à venir', 'amapress' ),
				'show_editable_inscriptions'          => __( '(true par défaut) Afficher les inscriptions encore éditables', 'amapress' ),
				'show_close_date'                     => __( '(false par défaut) Afficher la date de clôture des inscriptions en ligne pour chaque contrat', 'amapress' ),
				'show_max_deliv_dates'                => __( '(3 par défaut) Afficher les dates de livraison dans la liste des contrats pour les contrats jusqu\'à X dates', 'amapress' ),
				'use_quantite_tables'                 => __( '(false par défaut) (Paniers modulables) Afficher les quantités en tableaux (date en ligne, quantités en colonnes)', 'amapress' ),
				'contact_referents'                   => __( '(true par défaut) Affiche un lien de contact des référents dans la liste des contrats déjà souscrit (étape 4/8)', 'amapress' ),
				'before_close_hours'                  => sprintf( __( '(Configurable dans %s) Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant le jour de distribution', 'amapress' ), $contrats_conf_link ),
				'paiements_info_required'             => __( '(false par défaut) Rendre la saisie de la banque et de l\'émetteur des règlements obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'paiements_numero_required'           => __( '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'allow_trombi_decline'                => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'                         => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
				'email'                               => __( '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème', 'amapress' ),
				'max_produit_label_width'             => __( '(par défaut, 10em) Largeur maximal de la colonne Produit pour les inscriptions Paniers Modulables', 'amapress' ),
				'use_contrat_term'                    => __( '(true par défaut) Utiliser le terme Contrat si true et Commande si false', 'amapress' ),
				'skip_coords'                         => __( '(false par défaut) Passer l\'étape de saisie des coordonnées et des coadhérents', 'amapress' ),
				'allow_adhesion_alone'                => __( '(false par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'adhesion-en-ligne', 'amapress_self_adhesion',
		[
			'desc' => __( 'Permet les adhésions en ligne (amapien non connecté et nouveaux, sécurisée par une clé secrète) uniquement (pas d\'inscription aux contrats)', 'amapress' ),
			'args' => [
				'key'                            => sprintf( __( '(Par exemple : %s%s) Clé de sécurisation de l\'accès à l\'Assistant de Préinscription en ligne. Utilisez key=public pour permettre un accès sans clé', 'amapress' ), uniqid(), uniqid() ),
				'use_steps_nums'                 => __( '(booléen, true par défaut) : afficher les numéros d\'étapes', 'amapress' ),
				'allow_new_mail'                 => __( '(booléen, true par défaut): autoriser l\'inscription avec des emails inconnus/non associés à des comptes amapiens existants', 'amapress' ),
				'allow_existing_mail_for_public' => __( '(booléen, false par défaut): autoriser l\'inscription avec des emails de comptes amapiens existants (pour la version sans clé/public)', 'amapress' ),
				'agreement'                      => __( '(booléen, true par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ', 'amapress' ) . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Tableau de bord > Gestion Adhésions > onglet Assistant - Adhésion en ligne', 'amapress' ) ) . ')',
				'agreement_new_only'             => __( '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP uniquement pour les nouveaux adhérents', 'amapress' ),
				'send_adhesion_confirm'          => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP', 'amapress' ),
				'send_adhesion_bulletin'         => __( '(booléen, true par défaut) : envoyer le bulletin d\'adhésion avec la confirmation d\'adhésion', 'amapress' ),
				'send_tresoriers'                => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers', 'amapress' ),
				'edit_names'                     => __( '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription', 'amapress' ),
				'send_welcome'                   => __( '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens', 'amapress' ),
				'shorturl'                       => __( 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne', 'amapress' ),
				'max_coadherents'                => sprintf( __( '(Configurable dans %s)  Nombre maximum de co-adhérents', 'amapress' ), $contrats_conf_link ),
				'max_cofoyers'                   => sprintf( __( '(Configurable dans %s)  Nombre maximum de membres du foyer', 'amapress' ), $contrats_conf_link ),
				'mob_phone_required'             => sprintf( __( '(Configurable dans %s) Téléphones (mobiles) requis', 'amapress' ), $contrats_conf_link ),
				'address_required'               => __( '(false par défaut) Adresse requise', 'amapress' ),
				'track_no_renews'                => __( '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1', 'amapress' ),
				'track_no_renews_email'          => __( '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse', 'amapress' ),
				'send_no_renews_message'         => __( '(false par défaut) Envoyer un message de non renouvellement à l\'amapien', 'amapress' ),
				'notify_email'                   => __( '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)', 'amapress' ),
				'show_adherents_infos'           => __( '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents', 'amapress' ),
				'show_adhesion_infos'            => __( '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin', 'amapress' ),
				'allow_adhesion_lieu'            => __( '(booléen, false par défaut) : permettre de choisir son lieu de distribution souhaité dès l\'adhésion', 'amapress' ),
				'custom_checks_label'            => __( '("Options : " par défaut) Label des cases à cocher personnalisées', 'amapress' ),
				'allow_adhesion_message'         => __( '(booléen, false par défaut) : permettre d\'ajouter un message pour le trésorier lors de l\'adhésion', 'amapress' ),
				'allow_coadherents_access'       => __( '(true par défaut) Autoriser l\accès aux co-adhérents', 'amapress' ),
				'allow_coadherents_adhesion'     => __( '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents', 'amapress' ),
				'allow_remove_coadhs'            => __( '(false par défaut) Autoriser la suppression des co-adhérents', 'amapress' ),
				'allow_remove_cofoyers'          => __( '(true par défaut) Autoriser la suppression des membres du foyers', 'amapress' ),
				'show_coadherents_address'       => __( '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents', 'amapress' ),
				'show_cofoyers_address'          => __( '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer', 'amapress' ),
				'show_modify_coords'             => __( '(false par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer dans l\'étape 4/8 contrats', 'amapress' ),
				'paiements_info_required'        => __( '(false par défaut) Rendre la saisie de la banque et de l\'émetteur des règlements obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'paiements_numero_required'      => __( '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'allow_trombi_decline'           => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'                    => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
				'email'                          => __( '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème', 'amapress' ),
				'allow_adhesion_alone'           => __( '(true par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription', 'amapress' ),
				'check_honeypots'                => __( '(true par défaut) Détecter et bloquer les bots de spam pour une utilisation publique (key=public) de l\'assistant', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'adhesion-en-ligne-connecte', 'amapress_logged_self_adhesion',
		[
			'desc' => __( 'Permet les adhésions en ligne (amapien connecté) uniquement (pas d\'inscription aux contrats)', 'amapress' ),
			'args' => [
				'agreement'                      => sprintf( __( '(booléen, true par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans %s)', 'amapress' ), Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Tableau de bord > Gestion Adhésions > onglet Assistant - Adhésion en ligne', 'amapress' ) ) ),
				'agreement_new_only'             => __( '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP uniquement pour les nouveaux adhérents', 'amapress' ),
				'use_steps_nums'                 => __( '(booléen, true par défaut) : afficher les numéros d\'étapes', 'amapress' ),
				'allow_new_mail'                 => __( '(booléen, true par défaut): autoriser l\'inscription avec des emails inconnus/non associés à des comptes amapiens existants', 'amapress' ),
				'allow_existing_mail_for_public' => __( '(booléen, false par défaut): autoriser l\'inscription avec des emails de comptes amapiens existants (pour la version sans clé/public)', 'amapress' ),
				'send_adhesion_confirm'          => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP', 'amapress' ),
				'send_adhesion_bulletin'         => __( '(booléen, true par défaut) : envoyer le bulletin d\'adhésion avec la confirmation d\'adhésion', 'amapress' ),
				'send_tresoriers'                => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers', 'amapress' ),
				'edit_names'                     => __( '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription', 'amapress' ),
				'send_welcome'                   => __( '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens', 'amapress' ),
				'shorturl'                       => __( 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne', 'amapress' ),
				'max_coadherents'                => sprintf( __( '(Configurable dans %s) Nombre maximum de co-adhérents', 'amapress' ), $contrats_conf_link ),
				'max_cofoyers'                   => sprintf( __( '(Configurable dans %s) Nombre maximum de membres du foyer', 'amapress' ), $contrats_conf_link ),
				'mob_phone_required'             => sprintf( __( '(Configurable dans %s) Téléphones (mobiles) requis', 'amapress' ), $contrats_conf_link ),
				'address_required'               => __( '(false par défaut) Adresse requise', 'amapress' ),
				'track_no_renews'                => __( '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1', 'amapress' ),
				'track_no_renews_email'          => __( '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse', 'amapress' ),
				'send_no_renews_message'         => __( '(false par défaut) Envoyer un message de non renouvellement à l\'amapien', 'amapress' ),
				'notify_email'                   => __( '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)', 'amapress' ),
				'custom_checks_label'            => __( '("Options : " par défaut) Label des cases à cocher personnalisées', 'amapress' ),
				'allow_adhesion_lieu'            => __( '(booléen, false par défaut) : permettre de choisir son lieu de distribution souhaité dès l\'adhésion', 'amapress' ),
				'allow_adhesion_message'         => __( '(booléen, false par défaut) : permettre d\'ajouter un message pour le trésorier lors de l\'adhésion', 'amapress' ),
				'skip_coords'                    => __( '(false par défaut) Passer l\'étape de saisie des coordonnées et des coadhérents', 'amapress' ),
				'show_adherents_infos'           => __( '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents', 'amapress' ),
				'show_adhesion_infos'            => __( '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin', 'amapress' ),
				'allow_coadherents_access'       => __( '(true par défaut) Autoriser l\accès aux co-adhérents', 'amapress' ),
				'allow_coadherents_adhesion'     => __( '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents', 'amapress' ),
				'allow_remove_coadhs'            => __( '(false par défaut) Autoriser la suppression des co-adhérents', 'amapress' ),
				'allow_remove_cofoyers'          => __( '(true par défaut) Autoriser la suppression des membres du foyers', 'amapress' ),
				'show_coadherents_address'       => __( '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents', 'amapress' ),
				'show_cofoyers_address'          => __( '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer', 'amapress' ),
				'show_modify_coords'             => __( '(false par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer dans l\'étape 4/8 contrats', 'amapress' ),
				'paiements_info_required'        => __( '(false par défaut) Rendre la saisie de la banque et de l\'émetteur des règlements obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'paiements_numero_required'      => __( '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'allow_trombi_decline'           => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'                    => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
				'email'                          => __( '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème', 'amapress' ),
				'allow_adhesion_alone'           => __( '(true par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'intermittent-adhesion-en-ligne', 'amapress_inter_self_adhesion',
		[
			'desc' => __( 'Permet les adhésions en ligne pour les intermittents (non connecté et nouveaux, sécurisée par une clé secrète) uniquement', 'amapress' ),
			'args' => [
				'key'                            => sprintf( __( '(Par exemple : %s%s) Clé de sécurisation de l\'accès à l\'Assistant de Préinscription en ligne. Utilisez key=public pour permettre un accès sans clé', 'amapress' ), uniqid(), uniqid() ),
				'use_steps_nums'                 => __( '(booléen, true par défaut) : afficher les numéros d\'étapes', 'amapress' ),
				'allow_new_mail'                 => __( '(booléen, true par défaut): autoriser l\'inscription avec des emails inconnus/non associés à des comptes amapiens existants', 'amapress' ),
				'allow_existing_mail_for_public' => __( '(booléen, false par défaut): autoriser l\'inscription avec des emails de comptes amapiens existants (pour la version sans clé/public)', 'amapress' ),
				'agreement'                      => sprintf( __( '(booléen, true par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans %s)', 'amapress' ), Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Tableau de bord > Gestion Adhésions > onglet Assistant - Adhésion en ligne', 'amapress' ) ) ),
				'agreement_new_only'             => __( '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP uniquement pour les nouveaux adhérents', 'amapress' ),
				'send_adhesion_confirm'          => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP', 'amapress' ),
				'send_adhesion_bulletin'         => __( '(booléen, true par défaut) : envoyer le bulletin d\'adhésion avec la confirmation d\'adhésion', 'amapress' ),
				'send_tresoriers'                => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers', 'amapress' ),
				'edit_names'                     => __( '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription', 'amapress' ),
				'send_welcome'                   => __( '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens', 'amapress' ),
				'shorturl'                       => __( 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne', 'amapress' ),
				'mob_phone_required'             => sprintf( __( '(Configurable dans %s) Téléphones (mobiles) requis', 'amapress' ), $contrats_conf_link ),
				'address_required'               => __( '(false par défaut) Adresse requise', 'amapress' ),
				'notify_email'                   => __( '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)', 'amapress' ),
				'custom_checks_label'            => __( '("Options : " par défaut) Label des cases à cocher personnalisées', 'amapress' ),
				'show_adhesion_infos'            => __( '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin', 'amapress' ),
				'allow_adhesion_message'         => __( '(booléen, false par défaut) : permettre d\'ajouter un message pour le trésorier lors de l\'adhésion', 'amapress' ),
				'paiements_info_required'        => __( '(false par défaut) Rendre la saisie de la banque et de l\'émetteur des règlements obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'paiements_numero_required'      => __( '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'allow_trombi_decline'           => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'                    => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
				'track_no_renews'                => __( '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1', 'amapress' ),
				'track_no_renews_email'          => __( '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse', 'amapress' ),
				'send_no_renews_message'         => __( '(false par défaut) Envoyer un message de non renouvellement à l\'amapien', 'amapress' ),
				'email'                          => __( '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème', 'amapress' ),
				'check_honeypots'                => __( '(true par défaut) Détecter et bloquer les bots de spam pour une utilisation publique (key=public) de l\'assistant', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'intermittent-adhesion-en-ligne-connecte', 'amapress_inter_logged_self_adhesion',
		[
			'desc' => __( 'Permet les adhésions en ligne des intermittents (connecté) uniquement', 'amapress' ),
			'args' => [
				'agreement'                      => __( '(booléen, true par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ', 'amapress' ) . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Tableau de bord > Gestion Adhésions > onglet Assistant - Adhésion en ligne', 'amapress' ) ) . ')',
				'agreement_new_only'             => __( '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP uniquement pour les nouveaux adhérents', 'amapress' ),
				'use_steps_nums'                 => __( '(booléen, true par défaut) : afficher les numéros d\'étapes', 'amapress' ),
				'allow_new_mail'                 => __( '(booléen, true par défaut): autoriser l\'inscription avec des emails inconnus/non associés à des comptes amapiens existants', 'amapress' ),
				'allow_existing_mail_for_public' => __( '(booléen, false par défaut): autoriser l\'inscription avec des emails de comptes amapiens existants (pour la version sans clé/public)', 'amapress' ),
				'send_adhesion_confirm'          => __( '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP', 'amapress' ),
				'send_adhesion_bulletin'         => __( '(booléen, true par défaut) : envoyer le bulletin d\'adhésion avec la confirmation d\'adhésion', 'amapress' ),
				'send_tresoriers'                => __( '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers', 'amapress' ),
				'edit_names'                     => __( '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription', 'amapress' ),
				'send_welcome'                   => __( '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens', 'amapress' ),
				'shorturl'                       => __( 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne', 'amapress' ),
				'mob_phone_required'             => sprintf( __( '(Configurable dans %s) Téléphones (mobiles) requis', 'amapress' ), $contrats_conf_link ),
				'address_required'               => __( '(false par défaut) Adresse requise', 'amapress' ),
				'notify_email'                   => __( '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)', 'amapress' ),
				'custom_checks_label'            => __( '("Options : " par défaut) Label des cases à cocher personnalisées', 'amapress' ),
				'allow_adhesion_message'         => __( '(booléen, false par défaut) : permettre d\'ajouter un message pour le trésorier lors de l\'adhésion', 'amapress' ),
				'skip_coords'                    => __( '(false par défaut) Passer l\'étape de saisie des coordonnées et des coadhérents', 'amapress' ),
				'show_adhesion_infos'            => __( '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin', 'amapress' ),
				'paiements_info_required'        => __( '(false par défaut) Rendre la saisie de la banque et de l\'émetteur des règlements obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'paiements_numero_required'      => __( '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)', 'amapress' ),
				'track_no_renews'                => __( '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1', 'amapress' ),
				'track_no_renews_email'          => __( '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse', 'amapress' ),
				'send_no_renews_message'         => __( '(false par défaut) Envoyer un message de non renouvellement à l\'amapien', 'amapress' ),
				'allow_trombi_decline'           => __( '(true par défaut) Afficher une case à cocher pour ne pas apparaître sur le trombinoscope', 'amapress' ),
				'force_upper'                    => __( '(false par défaut) Forcer la mise en majuscules des informations', 'amapress' ),
				'email'                          => __( '(adresse email de l\'administrateur par défaut) Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'intermittent-paniers', 'amapress_intermittent_paniers_shortcode',
		[
			'desc' => __( 'Paniers réservés par un intermittent', 'amapress' ),
			'args' => [
				'show_history' => __( '(Par défaut “false”)  Afficher l\'historique des échanges de paniers de l\'amapien/intermittent', 'amapress' ),
				'history_days' => __( '(Par défaut “30”) Nombre de jour de l\'historique', 'amapress' ),
				'show_futur'   => __( '(Par défaut “true”) Afficher les échanges à venir', 'amapress' ),
			]
		] );


	amapress_register_shortcode( 'amapiens-map', 'amapress_amapiens_map_shortcode',
		[
			'desc' => __( 'Carte des amapiens', 'amapress' ),
			'args' => [
				'lieu'            => __( 'Afficher les amapiens ayant un contrat dans le lieu de distribution indiqué. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
				'show_email'      => __( '(Par défaut “default”) Afficher les emails des amapiens', 'amapress' ),
				'show_tel'        => __( '(Par défaut “default”) Afficher les numéros de téléphones des amapiens', 'amapress' ),
				'show_tel_fixe'   => __( '(Par défaut “default”) Afficher les numéros de fixes des amapiens', 'amapress' ),
				'show_tel_mobile' => __( '(Par défaut “default”) Afficher les numéros de portables des amapiens', 'amapress' ),
				'show_adresse'    => __( '(Par défaut “default”) Afficher les adresses des amapiens', 'amapress' ),
				'show_avatar'     => __( '(Par défaut “default”) Afficher les photos des amapiens', 'amapress' ),
				'show_lieu'       => __( '(Par défaut “default”) Afficher le nom du lieu de distribution', 'amapress' ),
				'padding'         => __( '(0 par défaut) marge autours du centrage de la carte', 'amapress' ),
				'max_zoom'        => __( '(18 par défaut, maximum) zoom maximal de la carte', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'amapiens-role-list', 'amapress_amapiens_role_list_shortcode',
		[
			'desc' => __( 'Liste des membres du collectif de l\'AMAP', 'amapress' ),
			'args' => [
				'lieu'            => __( 'Afficher les membres du collectif du lieu de distribution indiqué. ', 'amapress' ) . AmapressLieu_distribution::getLieuFilterHelp(),
				'show_prod'       => __( '(Par défaut “false”) Afficher les producteurs', 'amapress' ),
				'show_email'      => __( '(Par défaut “force”) Afficher les emails des membres du collectif', 'amapress' ),
				'show_tel'        => __( '(Par défaut “default”) Afficher les numéros de téléphones des membres du collectif', 'amapress' ),
				'show_tel_fixe'   => __( '(Par défaut “default”) Afficher les numéros de fixes des membres du collectif', 'amapress' ),
				'show_tel_mobile' => __( '(Par défaut “force”) Afficher les numéros de portables des membres du collectif', 'amapress' ),
				'show_adresse'    => __( '(Par défaut “default”) Afficher les adresses des membres du collectif', 'amapress' ),
				'show_avatar'     => __( '(Par défaut “default”) Afficher les photos des membres du collectif', 'amapress' ),
				'searchbox'       => __( '(Par défaut “true”) Afficher une barre de recherche', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'user-info', 'amapress_user_info_shortcode' );
	amapress_register_shortcode( 'next_events', 'amapress_next_events_shortcode',
		[
			'desc' => __( 'Calendrier des prochains évènements (Slider)', 'amapress' ),
			'args' => [
			]
		] );

	if ( amapress_is_user_logged_in() ) {
		amapress_register_shortcode( 'intermittent-desinscription-href', 'amapress_intermittence_desinscription_link',
			[
				'desc' => __( 'Lien de désinscription des intermittents', 'amapress' ),
				'args' => [
				]
			] );
	}

	amapress_register_shortcode( 'next-distrib-href', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Url de la page de la prochaine distributions', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-link', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Lien vers la page de la prochaine distributions', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-date', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Date de la prochaine distribution', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-emargement-href', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Url de la page de la liste d\'émargement de la prochaine distributions', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-emargement-link', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Lien vers la page de la liste d\'émargement de la prochaine distributions', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-redirect-next-distrib', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Redirige vers la page de la prochaine distributions', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-redirect-next-emargement', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Redirige vers la page de la liste d\'émargement de la prochaine distributions', 'amapress' ),
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-deliv', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Listes des prochaines distributions avec contrats livrés', 'amapress' ),
			'args' => [
				'distrib' => __( '(Par défaut 5) Nombre de distributions', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'next-distrib-deliv-paniers', 'amapress_next_distrib_shortcode',
		[
			'desc' => __( 'Listes des prochaines distributions avec contenus des paniers', 'amapress' ),
			'args' => [
				'distrib' => __( '(Par défaut 5) Nombre de distributions', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'liste-emargement-button', function ( $atts, $content = null ) {
		if ( is_singular( AmapressDistribution::INTERNAL_POST_TYPE ) ) {
			$dist_id = get_the_ID();
			if ( empty( $content ) ) {
				$content = __( 'Imprimer la liste d\'émargement', 'amapress' );
			}

			return amapress_get_button_no_esc( $content,
				amapress_action_link( $dist_id, 'liste-emargement' ), 'fa-fa',
				true, null, 'btn-print-liste' );
		}

		//TODO : for other place, next distrib
		return '';
	} );

	amapress_register_shortcode( 'amapien-details-livraisons', function ( $atts, $content = null ) {
		$atts                = shortcode_atts(
			array(
				'user_id'             => null,
				'ignore_renouv_delta' => true,
				'by'                  => 'date'
			), $atts
		);
		$user_id             = ! empty( $atts['user_id'] ) ? intval( $atts['user_id'] ) : amapress_current_user_id();
		$ignore_renouv_delta = Amapress::toBool( $atts['ignore_renouv_delta'] );

		return amapress_get_details_all_deliveries( $user_id, $ignore_renouv_delta,
			'producteur' === $atts['by'], null, isset( $_GET['grp_by_grp'] ) );
	},
		[
			'desc' => __( 'Afficher les détails des livraisons de l\'amapien par date ou producteur', 'amapress' ),
			'args' => [
				'by'                  => __( '(Par défaut "date") Grouper les livraisons par "date" ou "producteur"', 'amapress' ),
				'ignore_renouv_delta' => __( '(Par défaut true) Ignorer les contrats qui sont dans leur période de renouvellement', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'amapien-details-paiements', function ( $atts, $content = null ) {
		$atts    = shortcode_atts(
			array(
				'user_id'                 => null,
				'ignore_renouv_delta'     => true,
				'show_dates_encaissement' => false,
				'show_dates_livraisons'   => false,
			), $atts
		);
		$user_id = ! empty( $atts['user_id'] ) ? intval( $atts['user_id'] ) : amapress_current_user_id();

		return amapress_get_details_all_paiements( $user_id,
			Amapress::toBool( $atts['ignore_renouv_delta'] ),
			Amapress::toBool( $atts['show_dates_encaissement'] ),
			Amapress::toBool( $atts['show_dates_livraisons'] )
		);
	},
		[
			'desc' => __( 'Afficher le détails des sommes dues par l\'amapien', 'amapress' ),
			'args' => [
				'ignore_renouv_delta'     => __( '(Par défaut true) Ignorer les contrats qui sont dans leur période de renouvellement', 'amapress' ),
				'show_dates_encaissement' => __( '(Par défaut false) Afficher les dates d\'encaissement', 'amapress' ),
				'show_dates_livraisons'   => __( '(Par défaut false) Afficher les dates de livraison', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'calendrier-contrats', function ( $atts, $content = null ) {
		$atts                = shortcode_atts(
			array(
				'user_id'             => null,
				'filter'              => 'all',
				'ignore_renouv_delta' => true,
				'include_futur'       => true,
			), $atts
		);
		$user_id             = ! empty( $atts['user_id'] ) ? intval( $atts['user_id'] ) : amapress_current_user_id();
		$ignore_renouv_delta = Amapress::toBool( $atts['ignore_renouv_delta'] );
		$include_futur       = Amapress::toBool( $atts['include_futur'] );

		switch ( $atts['filter'] ) {
			case 'user':
				$adhs     = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, true, null,
					$ignore_renouv_delta, false, $include_futur );
				$contrats = [];
				foreach ( $adhs as $adh ) {
					$contrats[ $adh->getContrat_instanceId() ] = $adh->getContrat_instance();
				}
				break;
			case 'subscribables':
				$contrats = AmapressContrats::get_subscribable_contrat_instances();
				break;
			default:
				$contrats = AmapressContrats::get_active_contrat_instances( null, null,
					$ignore_renouv_delta, $include_futur );
		}

		return amapress_get_contrats_calendar( $contrats );
	},
		[
			'desc' => __( 'Afficher le calendrier des livraisons des contrats de l\'amapien ou de tous les contrats', 'amapress' ),
			'args' => [
				'filter'              => __( '(Par défaut "all") Afficher tous les contrats actifs (all), les contrats ouverts aux inscriptions (subscribables) ou les contrats de l\'amapien (user)', 'amapress' ),
				'ignore_renouv_delta' => __( '(Par défaut true) Ignorer les contrats qui sont dans leur période de renouvellement', 'amapress' ),
				'ignore_futur'        => __( '(Par défaut true) Inclure les contrats non commencés', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'display-if', function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'role' => 'logged',
				'key'  => '',
			), $atts
		);
		$show = false;
		foreach ( explode( ',', $atts['role'] ) as $role ) {
			switch ( $role ) {
				case 'logged':
					$show = $show || amapress_is_user_logged_in();
					break;
				case 'not_logged':
					$show = $show || ! amapress_is_user_logged_in();
					break;
				case 'intermittent':
					$show = $show || AmapressContrats::is_user_active_intermittent();
					break;
				case 'no_contrat':
					$show = $show || count( AmapressContrat_instance::getContratInstanceIdsForUser() ) == 0;
					break;
				case 'responsable_distrib':
					$show = $show || AmapressDistributions::isCurrentUserResponsableThisWeek();
					break;
				case 'responsable_amap':
					$show = $show || amapress_can_access_admin();
					break;
				case 'no_adhesion':
					$adh  = AmapressAdhesion_paiement::getForUser( amapress_current_user_id() );
					$show = $show || ( null == $adh );
					break;
				case 'adhesion':
					$adh  = AmapressAdhesion_paiement::getForUser( amapress_current_user_id() );
					$show = $show || ( null != $adh );
					break;
				case 'adhesion_nok':
					$adh  = AmapressAdhesion_paiement::getForUser( amapress_current_user_id() );
					$show = $show || ( $adh && $adh->isNotReceived() );
					break;
				case 'key':
					$show = $show || ! empty( $atts['key'] );
					break;
				default:
					if ( strpos( $role, 'contrat_' ) === 0 ) {
						$contrat_id = Amapress::resolve_post_id( substr( $role, 8 ), AmapressContrat::INTERNAL_POST_TYPE );
						$show       = $show || count( AmapressContrat_instance::getContratInstanceIdsForUser( null, $contrat_id ) ) > 0;
					} else {
						$show = $show || amapress_current_user_can( $role );
					}
			}
		}

		if ( ! empty( $atts['key'] ) ) {
			if ( empty( $_GET['key'] ) || $_GET['key'] != $atts['key'] ) {
				$show = false;
			}
		}

		return $show ? do_shortcode( $content ) : '';
	},
		[
			'desc' => __( 'Affiche le contenu du shortcode suivant une condition (connecté, non connecté, membre du collectif, intermittent, responsable de distribution, adhésion non réglée, adhésion, sans adhésion)', 'amapress' ),
			'args' => [
				'role' => __( '(Par défaut "logged") Afficher le contenu de ce shortcode uniquement si l\'amapien est dans un des rôles suivants : logged, not_logged, intermittent, no_contrat, no_adhesion, adhesion, adhesion_nok, responsable_distrib (est responsable de distribution cette semaine), responsable_amap (peut accéder au Tableau de Bord), contrat_xxx (a un contrat xxx), key (utilise uniquement la clé secrète)', 'amapress' ),
				'key'  => sprintf( __( '(Par défaut "", sans clé) Protège également le contenu avec une clé secrète (par ex, %s) ; pour protéger uniquement par clé, utiliser role=key', 'amapress' ), uniqid() . uniqid() ),
			]
		] );

	amapress_register_shortcode( 'display-if-logged', function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'key' => '',
			), $atts
		);

		return do_shortcode( '[display-if role=logged key="' . $atts['key'] . '"]' . $content . '[/display-if]' );
	},
		[
			'desc' => __( 'Affiche le contenu du shortcode si l\'amapien est connecté', 'amapress' ),
			'args' => [
				'key' => sprintf( __( '(Par défaut "", sans clé) Protège également le contenu avec une clé secrète (par ex, %s) ; pour protéger uniquement par clé, utiliser role=key', 'amapress' ), uniqid() . uniqid() ),
			]
		] );
	amapress_register_shortcode( 'display-if-not-logged', function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'key' => '',
			), $atts
		);

		return do_shortcode( '[display-if role=not_logged key="' . $atts['key'] . '"]' . $content . '[/display-if]' );
	},
		[
			'desc' => __( 'Affiche le contenu du shortcode si l\'amapien n\'est pas connecté', 'amapress' ),
			'args' => [
				'key' => sprintf( __( '(Par défaut "", sans clé) Protège également le contenu avec une clé secrète (par ex, %s) ; pour protéger uniquement par clé, utiliser role=key', 'amapress' ), uniqid() . uniqid() ),
			]
		] );
	amapress_register_shortcode( 'display-if-no-contrat', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=no_contrat]' . $content . '[/display-if]' );
	},
		[
			'desc' => __( 'Affiche le contenu du shortcode si l\'amapien n\'a pas de contrat en cours', 'amapress' ),
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-intermittent', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=intermittent]' . $content . '[/display-if]' );
	},
		[
			'desc' => __( 'Affiche le contenu du shortcode si l\'amapien est intermittent', 'amapress' ),
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-responsable-distrib', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=responsable_distrib]' . $content . '[/display-if]' );
	},
		[
			'desc' => __( 'Affiche le contenu du shortcode si l\'amapien est responsable de distribution cette semaine', 'amapress' ),
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-responsable-amap', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=responsable_amap]' . $content . '[/display-if]' );
	},
		[
			'desc' => __( 'Affiche le contenu du shortcode si l\'amapien a accès au Tableau de bord (responsables AMAP)', 'amapress' ),
			'args' => []
		] );

	amapress_register_shortcode( 'responsable-distrib-info', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			[
				'distribution_link'   => 'true',
				'emargement_link'     => 'true',
				'emargement_pdf_link' => 'true',
			], $atts
		);

		$ret = '';
		if ( AmapressDistributions::isCurrentUserResponsableThisWeek() ) {
			$next_distrib = AmapressDistribution::getNextDistribution( null, null, Amapress::start_of_week( amapress_time() ) );
			if ( ! $next_distrib ) {
				return '';
			}
			$ret = '<div class="resp-distrib-this-week"><p>' . __( 'Vous êtes responsable de distribution ', 'amapress' )
			       . ( Amapress::toBool( $atts['distribution_link'] ) ?
					Amapress::makeLink( $next_distrib->getPermalink(), __( 'cette semaine', 'amapress' ) ) :
					__( 'cette semaine', 'amapress' ) ) . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			if ( Amapress::toBool( $atts['emargement_link'] ) ) {
				$ret .= '<br/>' . Amapress::makeLink( $next_distrib->getListeEmargementHref(), __( 'Liste d\'émargement', 'amapress' ) )
				        . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			}
			if ( Amapress::toBool( $atts['emargement_pdf_link'] ) ) {
				$ret .= '<br/>' . Amapress::makeLink( $next_distrib->getListeEmargementHref() . '/pdf', __( 'Liste d\'émargement en PDF', 'amapress' ) )
				        . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			}
			$ret .= '</p></div>';
		} elseif ( AmapressDistributions::isCurrentUserResponsableNextWeek() ) {
			$next_distrib = AmapressDistribution::getNextDistribution( null, null, Amapress::add_a_week( Amapress::start_of_week( amapress_time() ) ) );
			if ( ! $next_distrib ) {
				return '';
			}

			$ret = '<div class="resp-distrib-this-week"><p>' . __( 'Vous êtes responsable de distribution ', 'amapress' )
			       . ( Amapress::toBool( $atts['distribution_link'] ) ?
					Amapress::makeLink( $next_distrib->getPermalink(), __( 'la semaine prochaine', 'amapress' ) ) :
					__( 'la semaine prochaine', 'amapress' ) ) . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			if ( Amapress::toBool( $atts['emargement_link'] ) ) {
				$ret .= '<br/>' . Amapress::makeLink( $next_distrib->getListeEmargementHref(), __( 'Liste d\'émargement', 'amapress' ) )
				        . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			}
			if ( Amapress::toBool( $atts['emargement_pdf_link'] ) ) {
				$ret .= '<br/>' . Amapress::makeLink( $next_distrib->getListeEmargementHref() . '/pdf', __( 'Liste d\'émargement en PDF', 'amapress' ) )
				        . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			}
			$ret .= '</p></div>';
		}

		return $ret;
	},
		[
			'desc' => __( 'Afficher un message "Vous êtes responsable de distribution cette semaine/la semaine prochaine"', 'amapress' ),
			'args' => [
				'distribution_link'   => __( '(Par défaut true) affiche un lien vers la distribution.', 'amapress' ),
				'emargement_link'     => __( '(Par défaut true) affiche un lien vers la liste d\'émargement.', 'amapress' ),
				'emargement_pdf_link' => __( '(Par défaut false) affiche un lien vers la liste d\'émargement en PDF.', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'nous-contacter', function ( $atts ) {
		amapress_ensure_no_cache();

		return Amapress::getContactInfos();
	},
		[
			'desc' => __( 'Contenu du formulaire de contact', 'amapress' ),
			'args' => [
			]
		] );

	amapress_register_shortcode( 'agenda-url', function ( $atts ) {
		$atts = shortcode_atts(
			[ 'since_days' => 30 ],
			$atts );
		$id   = 'agenda-url-' . md5( uniqid() );
		$url  = esc_attr( Amapress_Agenda_ICAL_Export::get_link_href( false, intval( $atts['since_days'] ) ) );

		return "<div class='input-group'><input id='$id' type='text' value='$url' class='form-control' style='max-width: 80%' /><span class='input-group-addon'><button class='btn btn-secondary copy-agenda-url' type='button' data-clipboard-target='#{$id}'><span class='fa fa-copy' /></button></span><script type='text/javascript'>jQuery(function($) { new ClipboardJS('.copy-agenda-url'); });</script></div>";
	},
		[
			'desc' => __( 'Copieur de lien de configuration de la synchronisation d\'un calendrier ICAL dans l\'agenda de l\'amapien', 'amapress' ),
			'args' => [
				'since_days' => __( '(Par défaut 30) Nombre de jours d\'historique de l\'agenda', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'agenda-url-button', function ( $atts ) {
		$atts = shortcode_atts(
			[
				'since_days' => 30,
				'title'      => __( 'Ajouter mon calendrier AMAP à mon agenda', 'amapress' )
			],
			$atts );
		$id   = 'agenda-url_btn-' . md5( uniqid() );
		$url  = Amapress_Agenda_ICAL_Export::get_link_href( false, intval( $atts['since_days'] ) );
		$url  = preg_replace( '/^http/', 'webcal', $url );
		$url  = esc_attr( $url );

		return Amapress::makeButtonLink( $url, $atts['title'], true, true, 'btn btn-default btn-add-cal' );
	},
		[
			'desc' => __( 'Bouton d\'ajout de synchronisation de son calendrier (ICAL) dans l\'agenda de l\'amapien', 'amapress' ),
			'args' => [
				'since_days' => __( '(Par défaut 30) Nombre de jours d\'historique de l\'agenda', 'amapress' ),
				'title'      => __( '(Par défaut Ajouter mon calendrier AMAP à mon agenda) Titre du bouton', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'front_next_events', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$max            = amapress_is_user_logged_in() ? Amapress::getOption( 'agenda_max_dates', 5 ) : Amapress::getOption( 'agenda_max_public_dates', 5 );
		$agenda_content = do_shortcode( '[next_events max=' . $max . ']' );
		if ( ! Amapress::toBool( $atts['title'] ) ) {
			return $agenda_content;
		}
//    $agenda = '';
//    if (trim(wp_strip_all_tags($agenda_content, true)) != '') {
//    (amapress_is_user_logged_in() ? ' (<a href="' . Amapress_Agenda_ICAL_Export::get_link_href() . '"><i class="fa fa-calendar" aria-hidden="true"></i> iCal</a>)' : '') . '</h2>' .
		$agenda = '<h3 id="front-adgenda-title">' . ( amapress_is_user_logged_in() ? Amapress::getOption( 'front_agenda_title', __( 'Cette semaine dans mon panier...', 'amapress' ) ) : Amapress::getOption( 'front_agenda_public_title', __( 'Agenda', 'amapress' ) ) ) . '</h3>' .
		          $agenda_content;

//    }
		return $agenda;
	},
		[
			'desc' => __( 'Affiche le calendrier de la page d\'accueil', 'amapress' ),
			'args' => [
				'title' => __( '(Par défaut “yes”) Afficher le titre de la section', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'front_produits', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$produits_content = Amapress::get_contrats_list();
		$produits         = '';
		if ( Amapress::toBool( $atts['title'] ) ) {
			$produits = '<h3 id="front-produits-title">' . Amapress::getOption( 'front_produits_title', __( 'Les produits de l\'Amap...', 'amapress' ) ) . '</h3>';
		}
		if ( trim( wp_strip_all_tags( $produits_content, true ) ) != '' ) {
			$interm = '';
//        if (Amapress::isIntermittenceEnabled() && Amapress::userCanRegister()) {
//            $interm = amapress_get_button(__('Devenir intermittent', 'amapress'), Amapress::getMesInfosSublink('adhesions/intermittence/inscription'));
//        }
//			if ( Amapress::isIntermittenceEnabled() ) {
//				$interm = do_shortcode( '[intermittents-inscription view=me show_info=no]' );
//			}

			$produits .= $produits_content . $interm;
		}

		return $produits;
	},
		[
			'desc' => __( 'Affiche la liste des producteurs/productions pour la page d\'acceuil', 'amapress' ),
			'args' => [
				'title' => __( '(Par défaut “yes”) Afficher le titre de la section', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'front_nous_trouver', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$map_content = do_shortcode( '[nous-trouver]' );
		if ( ! Amapress::toBool( $atts['title'] ) ) {
			return $map_content;
		}
		$map = '<h3 id="front-map-title">' . Amapress::getOption( 'front_map_title', __( 'Où nous trouver ?', 'amapress' ) ) . '</h3>' . $map_content;

		return $map;
	},
		[
			'desc' => __( 'Affiche la carte des lieux de distributions pour la page d\'accueil', 'amapress' ),
			'args' => [
				'title' => __( '(Par défaut “yes”) Afficher le titre de la section', 'amapress' ),
			]
		] );
	amapress_register_shortcode( 'front_default_grid', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title'            => 'yes',
				'agenda-classes'   => 'col-lg-4 col-md-6 col-sm-6 col-xs-12',
				'produits-classes' => 'col-lg-4 col-md-6 col-sm-6 col-xs-12',
				'map-classes'      => 'col-lg-4 col-md-12 col-sm-12 col-xs-12',
			),
			$atts );

		$agenda   = do_shortcode( '[front_next_events title=yes]' );
		$produits = do_shortcode( '[front_produits title=yes]' );
		$map      = do_shortcode( '[front_nous_trouver title=yes]' );

		return '<div class="front-page">
                    <div class="row">
                        <div class="' . $atts['agenda-classes'] . '">' . $agenda . '</div>
                        <div class="' . $atts['produits-classes'] . '">' . $produits . '</div>
                        <div class="' . $atts['map-classes'] . '">' . $map . '</div>
                    </div>
                </div>';
	},
		[
			'desc' => __( 'Affiche les infos de la page d\'accueil (calendrier, productions, carte)', 'amapress' ),
			'args' => [
				'title'            => __( '(Par défaut “yes”) Afficher le titre des trois sections (Agenda/Produits/Carte) de la grille par défaut', 'amapress' ),
				'agenda-classes'   => __( '(Par défaut “col-lg-4 col-md-6 col-sm-6 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille', 'amapress' ),
				'produits-classes' => __( '(Par défaut “col-lg-4 col-md-6 col-sm-6 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille', 'amapress' ),
				'map-classes'      => __( '(Par défaut “col-lg-4 col-md-12 col-sm-12 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille', 'amapress' ),
			]
		] );

	amapress_register_shortcode( 'listes-diffusions', function ( $atts ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		ob_start();

		$entries = [];
		foreach ( Amapress_MailingListConfiguration::getAll() as $mailing_list_configuration ) {
			$li   = '<li>';
			$name = $mailing_list_configuration->getAddress();
			$desc = $mailing_list_configuration->getDescription();
			$li   .= Amapress::makeLink( "mailto:$name", $name );
			if ( ! empty( $desc ) ) {
				$li .= "<br/><em>$desc</em>";
			}
			if ( current_user_can( 'list_users' ) ) {
				$li .= '<br/>' . Amapress::makeLink( $mailing_list_configuration->getAdminMembersLink(), __( 'Voir les membres', 'amapress' ), true, true );
			}
			if ( current_user_can( 'manage_options' ) ) {
				$li .= '<br/>' . Amapress::makeLink( $mailing_list_configuration->getAdminEditLink(), __( 'Voir la configuration', 'amapress' ), true, true );
			}
			$li        .= '</li>';
			$entries[] = $li;
		}
		foreach ( AmapressMailingGroup::getAll() as $ml ) {
			$li   = '<li>';
			$name = $ml->getName();
			$desc = $ml->getDescription();
			$li   .= Amapress::makeLink( "mailto:$name", $name );
			if ( ! empty( $desc ) ) {
				$li .= "<br/><em>$desc</em>";
			}
			if ( current_user_can( 'list_users' ) ) {
				$li .= '<br/>' . Amapress::makeLink( $ml->getAdminMembersLink(), __( 'Voir les membres', 'amapress' ), true, true );
			}
			if ( current_user_can( 'manage_options' ) ) {
				$li .= '<br/>' . Amapress::makeLink( $ml->getAdminEditLink(), __( 'Voir la configuration', 'amapress' ), true, true );
			}
			$li        .= '</li>';
			$entries[] = $li;
		}
		sort( $entries );

		if ( empty( $entries ) ) {
			echo '<p>' . __( 'Aucune mailing list et aucun Emails groupés configuré', 'amapress' ) . '</p>';
		} else {
			echo '<ul class="sh-listes-diffusions" style="margin-left: 1.5em;list-style: disc">';
			echo implode( '', $entries );
			echo '</ul>';
		}

		return ob_get_clean();
	},
		[
			'desc' => __( 'Liste des liste de diffusions (SYMPA/SudOuest/Emails groupés) configurées sur le site', 'amapress' ),
			'args' => [
				'sms' => __( '(Par défaut “yes”) Afficher un lien SMS-To contenant tous les membres de chaque liste de diffusion', 'amapress' ),
			]
		] );


	amapress_register_shortcode( 'helloasso-adhesion', function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'adhesion_shift_weeks' => intval( Amapress::getOption( 'adhesion_shift_weeks' ) ),
				'category'             => '',
				'form_type'            => 'form',
				'show_period'          => 'true',
				'show_adherent_info'   => 'true',
			), $atts
		);

		$date = Amapress::add_a_week( amapress_time(), intval( $atts['adhesion_shift_weeks'] ) );

		$period_adhesion = AmapressAdhesionPeriod::getCurrent( $date, $atts['category'] );
		if ( ! $period_adhesion ) {
			return __( 'Adhésions closes', 'amapress' );
		}

		if ( empty( $period_adhesion->getHelloAssoFormUrl() ) ) {
			return __( 'Formulaire HelloAsso non associé', 'amapress' );
		}

		$form      = '';
		$form_type = $atts['form_type'];
		switch ( $form_type ) {
			case 'button':
				$form = '<iframe id="haWidget" allowtransparency="true" src="' .
				        trailingslashit( $period_adhesion->getHelloAssoFormUrl() ) . 'widget-bouton" style="width:100%;height:70px;border:none;"></iframe>' .
				        '<div style="width:100%;text-align:center;">Propulsé par <a href="https://www.helloasso.com" rel="nofollow">HelloAsso</a></div>';
				break;
			case 'thumb':
				$form = '<iframe id="haWidget" allowtransparency="true" src="' .
				        trailingslashit( $period_adhesion->getHelloAssoFormUrl() ) . 'widget-vignette" style="width:350px;height:450px;border:none;"></iframe>' .
				        '<div style="width:100%;text-align:center;">Propulsé par <a href="https://www.helloasso.com" rel="nofollow">HelloAsso</a></div>';
				break;
			case 'thumb_vert':
				$form = '<iframe id="haWidget" allowtransparency="true" src="' .
				        trailingslashit( $period_adhesion->getHelloAssoFormUrl() ) . 'widget-vignette-horizontale" style="width:800px;height:400px;border:none;"></iframe>' .
				        '<div style="width:100%;text-align:center;">Propulsé par <a href="https://www.helloasso.com" rel="nofollow">HelloAsso</a></div>';
				break;
			default:
				$form = '<iframe id="haWidget" allowtransparency="true" scrolling="auto" src="' .
				        trailingslashit( $period_adhesion->getHelloAssoFormUrl() ) . 'widget" style="width:100%;height:750px;border:none;" onload="window.scroll(0, this.offsetTop)"></iframe>' .
				        '<div style="width:100%;text-align:center;">Propulsé par <a href="https://www.helloasso.com" rel="nofollow">HelloAsso</a></div>';
		}
		$title = '';
		if ( Amapress::toBool( $atts['show_period'] ) ) {
			$title = '<h4>' . esc_html( $period_adhesion->getTitle() ) . '</h4>';
		}
		$info = '';
		if ( Amapress::toBool( $atts['show_adherent_info'] ) ) {
			if ( amapress_is_user_logged_in() ) {
				$amapien = AmapressUser::getBy( amapress_current_user_id() );
				$info    = '<p>' . sprintf( __( 'Vous êtes connecté en tant que <strong>%s</strong>. Votre adresse email est <strong>%s</strong>', 'amapress' ),
						$amapien->getDisplayName(), $amapien->getEmail() ) . '</p>';
			} else {
				$info = '<p>' . __( 'Vous n\'êtes pas connecté', 'amapress' ) . '</p>';
			}
		}

		return $title . $info . $content . $form;
	},
		[
			'desc' => __( 'Affichage le formulaire HelloAsso (Formulaire/Vignette/Vignette horizontale/Bouton)', 'amapress' ),
			'args' => [
				'contenu'              => __( 'Dans le contenu du shortcode, placez vos instructions de ré/adhésion.', 'amapress' ),
				'show_period'          => __( '(true par défaut) Afficher le nom de la période d\'adhésion du formulaire', 'amapress' ),
				'show_adherent_info'   => __( '(true par défaut) Afficher les infos de l\'utilisateur connecté', 'amapress' ),
				'form_type'            => __( '(form par défaut) Type de formulaire à afficher (form: Formulaire, thumb: Vignette, thumbhori: Vignette horizontale, button: Bouton)', 'amapress' ),
				'category'             => __( '("" par défaut) Filtre par catégorie d\'adhésion', 'amapress' ),
				'adhesion_shift_weeks' => sprintf( __( '(Configurable dans %s) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion', 'amapress' ), $contrats_conf_link ),
			]
		] );

	amapress_register_shortcode( 'amapress-backoffice-view', function ( $atts, $content ) {
		$atts = shortcode_atts( [
			'logged'  => 'true',
			'users'   => 'false',
			'query'   => '',
			'columns' => 'all',
			'view'    => 'responsive',
			'order'   => '0',
		], $atts );
		if ( ! amapress_is_user_logged_in() && Amapress::toBool( $atts['logged'] ) ) {
			return '';
		}

		$atts['query'] = urlencode( html_entity_decode( $atts['query'] ) );
		$url           = admin_url( 'admin-post.php' );
		$url           = add_query_arg( $atts, $url );
		$url           = add_query_arg( 'hash', amapress_sha_secret( 'amps_export_data' . $atts['query'] . $atts['columns'] ), $url );
		$url           = add_query_arg( 'action', 'export_datatable', $url );
		$id            = 'amp-bo-view-' . uniqid() . '-wrapper';

		return '<div id="' . $id . '"></div>
<script type="application/javascript">
	jQuery(function($) {
	    var url = ' . wp_json_encode( $url ) . ';
	    var id = ' . wp_json_encode( $id ) . ';
	    $.get(url, function(res) { $("#" + id).html(res);})
	})
</script>';

	},
		[
			'desc' => __( 'Affiche les données d\'une requête sur les inscriptions/contrats/adhésions/producteurs... ou comptes utilisateurs', 'amapress' ),
			'args' => [
				'logged'  => '(Par défaut "true") Uniquement pour utilisateur connecté',
				'users'   => '(Par défaut "false") Requête sur les comptes utilisateurs',
				'query'   => '(Par défaut "") Requête de sélection des données à afficher',
				'columns' => '(Par défaut "all") Colonnes à afficher',
				'order'   => '(Par défaut 0) Colonnes triée par défaut',
				'view'    => '(Par défaut "responsive") Type de vue "none" (pleine largeur), "responsive" (collapsing de colonnes), "scroll"',
			]
		] );
}


add_action( 'admin_post_export_datatable', 'amapress_admin_post_export_datatable' );
add_action( 'admin_post_nopriv_export_datatable', 'amapress_admin_post_export_datatable' );
function amapress_admin_post_export_datatable() {
	$atts = shortcode_atts( [
		'logged'  => 'true',
		'users'   => 'false',
		'view'    => 'responsive',
		'order'   => '0',
		'query'   => '',
		'columns' => 'all',
	], $_REQUEST );
	if ( ! isset( $_REQUEST['hash'] )
	     || $_REQUEST['hash'] != amapress_sha_secret( 'amps_export_data' . urlencode( $atts['query'] ) . $atts['columns'] ) ) {
		wp_die( __( 'Accès invalide', 'amapress' ) );
	}
	if ( ! amapress_is_user_logged_in() && Amapress::toBool( $atts['logged'] ) ) {
		return '';
	}

	if ( Amapress::toBool( $atts['users'] ) ) {
		$data = AmapressExport_Users::generate_datatable_data(
			$atts['query'],
			null, null,
			$atts['columns']
		);
	} else {
		$data = AmapressExport_Posts::generate_datatable_data(
			$atts['query'],
			null, null,
			$atts['columns']
		);
	}

	echo amapress_get_datatable( 'amp-bo-view-' . uniqid(),
		$data['columns'], $data['data'],
		array(
			'paging'     => true,
			'responsive' => 'responsive' == $atts['view'],
			'scrollX'    => 'scroll' == $atts['view'],
			'order'      => [
				[ intval( $atts['order'] ), 'asc' ]
			]
		),
		array(
			Amapress::DATATABLES_EXPORT_EXCEL,
			Amapress::DATATABLES_EXPORT_PRINT
		) );
}