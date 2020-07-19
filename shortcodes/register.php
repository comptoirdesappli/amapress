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
				'desc' => 'Affiche une grille des articles récents',
				'args' => [
					'limit'    => '(5 par défaut) Nombre maximum d\'articles à afficher',
					'chrlimit' => '(120 par défaut) Nombre maximum de caractères du résumé de chaque article à afficher',
				]
			] );
	}

	$inscr_distrib_conf_link = Amapress::makeLink(
		admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_inscr_distrib_options_tab' ),
		'Tableau de bord>Distributions>Configuration, onglet Inscription distribution'
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
			'desc' => 'Affiche le nombre d\'années écoulée depuis une autre année',
			'args' => [
				'year' => 'Année de départ du décompte d\'années'
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
			'desc' => 'Affiche un encadré avec titre',
			'args' => [
				'title'    => 'Titre de l\'encadré',
				'esc_html' => '(true par défaut) encoder le titre'
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
			'desc' => 'Carte des lieux de distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'recettes', 'amapress_recettes_shortcode',
		[
			'desc' => 'Gallerie des recettes',
			'args' => [
				'produits'    => 'Filtre de produits',
				'cat'         => 'Filtre de catégories',
				'cat__not_in' => 'Inverse filtre de catégories',
				'if_empty'    => '(Par défaut “Pas encore de recette”) Texte à afficher quand il n\’y a pas de recettes à afficher',
				'size'        => '(Par défaut “thumbnail”) Taille de l\’aperçu',
				'searchbox'   => '(Par défaut “true”) Afficher une barre de recherche',
			]
		] );
	amapress_register_shortcode( 'produits', 'amapress_produits_shortcode',
		[
			'desc' => 'Gallerie de produits',
			'args' => [
				'producteur'  => 'Filtre producteurs',
				'recette'     => 'Filtre recettes',
				'cat'         => 'Filtre catégories',
				'cat__not_in' => 'Inverse filtre catégories',
				'if_empty'    => '(Par défaut “Pas encore de produits”) Texte à afficher quand il n\’y a pas de recettes à afficher',
				'size'        => '(Par défaut “thumbnail”) Taille de l\’aperçu',
				'searchbox'   => '(Par défaut “true”) Afficher une barre de recherche',
			]
		] );
	amapress_register_shortcode( 'lieu-map', 'amapress_lieu_map_shortcode',
		[
			'desc' => 'Emplacement d\'un lieu (carte et StreetView)',
			'args' => [
				'lieu' => 'Afficher la carte du lieu indiqué',
				'mode' => '(Par défaut “map”) Mode d’affichage. Si Gooogle est votre afficheur de carte, alors vous pouvez choisir : map, map+streetview ou streetview',
			]
		] );
	amapress_register_shortcode( 'user-map', 'amapress_user_map_shortcode',
		[
			'desc' => 'Emplacement d\'un amapien',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'producteur-map', 'amapress_producteur_map_shortcode',
		[
			'desc' => 'Emplacement d\'un producteur',
			'args' => [
				'lieu' => 'Afficher la carte du lieu indiqué',
				'mode' => '(Par défaut “map”) Mode d’affichage. Si Gooogle est votre afficheur de carte, alors vous pouvez choisir : map, map+streetview ou streetview',
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
			'desc' => 'Historique d\'inscription des responsables aux distributions',
			'args' => [
				'show_email'          => '(Par défaut “default”) Afficher les emails',
				'show_tel'            => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'       => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile'     => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'        => '(Par défaut “false”) Afficher les adresses',
				'show_avatar'         => '(Par défaut “default”) Afficher les avatars des amapiens',
				'show_roles'          => '(Par défaut “false”) Afficher les rôles des membres du collectif',
				'show_title'          => '(Par défaut “true”) Afficher les noms des lieux',
				'past_weeks'          => '(Par défaut “5”) Nombre de semaines d’historique des distributions',
				'lieu'                => 'Filtre de lieu',
				'scroll_y'            => '(Configurable dans ' . $inscr_distrib_conf_link . ') Limite la hauteur à X pixels et permet la navigation verticale avec scroll dans la date de distributions',
				'font_size'           => '(Configurable dans ' . $inscr_distrib_conf_link . ') Taille relative du texte dans la vue en % ou em',
				'show_no_contrat'     => '(Par défaut “true”) Afficher un message "Pas de livraison" pour les dates sans livraison pour l\'amapien',
				'show_contrats_desc'  => '(Par défaut “true”) Afficher la liste des contrats pour chaque date ; si entier, limite le nombre de lignes affichées à ce nombre',
				'show_contrats_count' => '(Par défaut “false”) Afficher le nombre de contrats pour chaque date',
				'responsive'          => '(Par défaut scroll) Configuration du mode mobile/responsive : scroll (hauteur de la vue et largeur de colonnes fixes avec barres de défilement), auto (passage en mode pliant sur mobile, répartition en largeur sinon), true/false'
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
			'desc' => 'Liste statique des inscrits des responsables aux distributions',
			'args' => [
				'show_title' => '(Par défaut “true”) Afficher les noms des lieux',
				'max_dates'  => '(Configurable dans ' . $inscr_distrib_conf_link . ') Nombre maximum de distributions à venir à afficher',
				'lieu'       => 'Filtre de lieu',
			]
		] );
	amapress_register_shortcode( 'inscription-distrib', 'amapress_inscription_distrib_shortcode',
		[
			'desc' => 'Inscriptions comme responsable de distributions',
			'args' => [
				'show_past'                 => '(Par défaut “false”) Afficher les distributions passées',
				'show_next'                 => '(Par défaut “true”) Afficher les distributions à venir',
				'show_email'                => '(Par défaut “default”) Afficher les emails',
				'show_tel'                  => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'             => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile'           => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'              => '(Par défaut “false”) Afficher les adresses',
				'show_avatar'               => '(Par défaut “default”) Afficher les avatars des amapiens',
				'show_roles'                => '(Par défaut “false”) Afficher les rôles des membres du collectif',
				'show_title'                => '(Par défaut “true”) Afficher les noms des lieux',
				'past_weeks'                => '(Par défaut “5”) Nombre de semaines d’historique des distributions',
				'max_dates'                 => '(Configurable dans ' . $inscr_distrib_conf_link . ') Nombre maximum de distributions à venir à afficher',
				'lieu'                      => 'Filtre de lieu',
				'column_date_width'         => '(Configurable dans ' . $inscr_distrib_conf_link . ') Largeur de la colonne Dates/Produits',
				'fixed_column_width'        => '(Configurable dans ' . $inscr_distrib_conf_link . ') : fixe la largeur des colonnes Responsables ; en em ou px pour forcer une largeur fixe ; % pour répartir la largeur de colonnes sur la largeur du tableau',
				'scroll_y'                  => '(Configurable dans ' . $inscr_distrib_conf_link . ') Limite la hauteur à X pixels et permet la navigation verticale avec scroll dans la date de distributions',
				'font_size'                 => '(Configurable dans ' . $inscr_distrib_conf_link . ') Taille relative du texte dans la vue en % ou em ou rem',
				'show_no_contrat'           => '(Par défaut “true”) Afficher un message "Pas de livraison" pour les dates sans livraison pour l\'amapien',
				'show_contrats_desc'        => '(Par défaut “true”) Afficher la liste des contrats pour chaque date ; si entier, limite le nombre de lignes affichées à ce nombre',
				'show_contrats_count'       => '(Par défaut “false”) Afficher le nombre de contrats pour chaque date',
				'inscr_all_distrib'         => '(Par défaut “false”) Autoriser tous les amapiens à s’inscrire même sur les lieux pour lesquels ils n’ont pas de contrat',
				'allow_resp_dist_manage'    => '(Par défaut “false”) Autoriser les responsables de distributions à gérer les inscriptions le temps de la semaine où ils sont inscrits',
				'prefer_inscr_button_first' => '(Configurable dans ' . $inscr_distrib_conf_link . ') Placer les boutons d\'inscription en premier et les inscrits ensuite. False inverse.',
				'allow_gardiens'            => '(Par défaut, true si actif) Autoriser l\'inscription des gardiens de paniers',
				'allow_gardiens_comments'   => '(Par défaut, true si actif) Autoriser les gardiens de paniers à mettre un commentaire avec leur inscription',
				'allow_slots'               => '(Par défault true) Autoriser le choix de créneaux',
				'show_responsables'         => '(Par défault true) Afficher les colonnes d\'inscription Responsable de distribution',
				'responsive'                => '(Par défaut scroll) Configuration du mode mobile/responsive : scroll (hauteur de la vue et largeur de colonnes fixes avec barres de défilement), auto (passage en mode pliant sur mobile, répartition en largeur sinon), true/false'
			]
		] );
	amapress_register_shortcode( 'anon-inscription-distrib', 'amapress_inscription_distrib_shortcode',
		[
			'desc' => 'Inscriptions comme responsable de distributions',
			'args' => [
				'key'                       => '(Par exemple : ' . uniqid() . uniqid() . ') Clé de sécurisation de l\'accès à cet assistant d\'inscription aux distributions sans connexion',
				'show_past'                 => '(Par défaut “false”) Afficher les distributions passées',
				'show_next'                 => '(Par défaut “true”) Afficher les distributions à venir',
				'show_email'                => '(Par défaut “default”) Afficher les emails',
				'show_tel'                  => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'             => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile'           => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'              => '(Par défaut “false”) Afficher les adresses',
				'show_avatar'               => '(Par défaut “default”) Afficher les avatars des amapiens',
				'show_roles'                => '(Par défaut “false”) Afficher les rôles des membres du collectif',
				'show_title'                => '(Par défaut “true”) Afficher les noms des lieux',
				'past_weeks'                => '(Par défaut “5”) Nombre de semaines d’historique des distributions',
				'max_dates'                 => '(Configurable dans ' . $inscr_distrib_conf_link . ') Nombre maximum de distributions à venir à afficher',
				'lieu'                      => 'Filtre de lieu',
				'column_date_width'         => '(Configurable dans ' . $inscr_distrib_conf_link . ') Largeur de la colonne Dates/Produits',
				'fixed_column_width'        => '(Configurable dans ' . $inscr_distrib_conf_link . ') : fixe la largeur des colonnes Responsables ; en em ou px pour forcer une largeur fixe ; % pour répartir la largeur de colonnes sur la largeur du tableau',
				'scroll_y'                  => '(Configurable dans ' . $inscr_distrib_conf_link . ') Limite la hauteur à X pixels et permet la navigation verticale avec scroll dans la date de distributions',
				'font_size'                 => '(Configurable dans ' . $inscr_distrib_conf_link . ') Taille relative du texte dans la vue en % ou em ou rem',
				'show_no_contrat'           => '(Par défaut “true”) Afficher un message "Pas de livraison" pour les dates sans livraison pour l\'amapien',
				'show_contrats_desc'        => '(Par défaut “true”) Afficher la liste des contrats pour chaque date ; si entier, limite le nombre de lignes affichées à ce nombre',
				'show_contrats_count'       => '(Par défaut “false”) Afficher le nombre de contrats pour chaque date',
				'inscr_all_distrib'         => '(Par défaut “false”) Autoriser tous les amapiens à s’inscrire même sur les lieux pour lesquels ils n’ont pas de contrat',
				'allow_resp_dist_manage'    => '(Par défaut “false”) Autoriser les responsables de distributions à gérer les inscriptions le temps de la semaine où ils sont inscrits',
				'prefer_inscr_button_first' => '(Configurable dans ' . $inscr_distrib_conf_link . ') Placer les boutons d\'inscription en premier et les inscrits ensuite. False inverse.',
				'allow_gardiens'            => '(Par défaut, true si actif) Autoriser l\'inscription des gardiens de paniers',
				'allow_gardiens_comments'   => '(Par défaut, true si actif) Autoriser les gardiens de paniers à mettre un commentaire avec leur inscription',
				'allow_slots'               => '(Par défault true) Autoriser le choix de créneaux',
				'show_responsables'         => '(Par défault true) Afficher les colonnes d\'inscription Responsable de distribution',
				'responsive'                => '(Par défaut scroll) Configuration du mode mobile/responsive : scroll (hauteur de la vue et largeur de colonnes fixes avec barres de défilement), auto (passage en mode pliant sur mobile, répartition en largeur sinon), true/false'
			]
		] );
	amapress_register_shortcode( 'resp-distrib-contacts', 'amapress_responsables_distrib_shortcode',
		[
			'desc' => 'Contacts des responsables de distribution',
			'args' => [
				'distrib' => '(Par défaut "2") Afficher les responsables pour ce nombre de distributions à venir'
			]
		] );
	amapress_register_shortcode( 'inscription-visite', 'amapress_inscription_visite_shortcode',
		[
			'desc' => 'Inscripions aux visites à la ferme',
			'args' => [
				'show_email'      => '(Par défaut “default”) Afficher les emails',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile' => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses',
				'show_avatar'     => '(Par défaut “default”) Afficher les avatars des amapiens',
			]
		] );
	amapress_register_shortcode( 'inscription-amap-event', 'amapress_inscription_amap_event_shortcode',
		[
			'desc' => 'Inscriptions aux évènements AMAP',
			'args' => [
				'show_email'      => '(Par défaut “default”) Afficher les emails',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile' => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses',
				'show_avatar'     => '(Par défaut “default”) Afficher les avatars des amapiens',
			]
		] );

//    amapress_register_shortcode('paniers-intermittents-list', 'amapress_intermittents_paniers_list_shortcode');
	amapress_register_shortcode( 'echanger-paniers-list', 'amapress_echanger_panier_shortcode',
		[
			'desc' => 'Liste d\'échange de paniers',
			'args' => [
			]
		] );
//

	amapress_register_shortcode( 'anon-intermittents-inscription', 'amapress_intermittence_anon_inscription_shortcode',
		[
			'desc' => 'Inscription sans connexion à la liste des intermittents',
			'args' => [
				'key'      => '(Par exemple : ' . uniqid() . uniqid() . ') Clé de sécurisation de l\'accès à l\'inscription à la liste des intermittents',
				'shorturl' => 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne',
			]
		] );
	amapress_register_shortcode( 'intermittents-inscription', 'amapress_intermittence_inscription_shortcode',
		[
			'desc' => 'Inscription d\'un amapien à la liste des intermittents',
			'args' => [
				'show_info' => '(Par défaut “yes”) Afficher les informations d\’inscription à la liste des intermittents',
			]
		] );
	amapress_register_shortcode( 'intermittents-desinscription', 'amapress_intermittence_desinscription_shortcode',
		[
			'desc' => 'Désinscription d\'un amapien à la liste des intermittents',
			'args' => [
			]
		] );


	amapress_register_shortcode( 'adhesion-request-count', 'amapress_adhesion_request_count_shortcode',
		[
			'desc' => 'Nombre de demandes d\'adhésions en attente',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'amapress-post-its', 'amapress_postits_shortcode',
		[
			'desc' => 'Post-its des tâches courantes (listes émargement..)',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-ics-viewer', 'amapress_fullcalendar',
		[
			'desc' => 'Afficheur de calendrier ICAL/ICS',
			'args' => [
				'header_left'   => '(Par défaut “prev,next today”) Option de personnalisation de l\’entête partie gauche, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_center' => '(Par défaut “title”) Option de personnalisation de l\’entête partie centrale, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_right'  => '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\’entête partie droite, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'min_time'      => '(Par défaut “08:00:00”) Heure minimale affichée',
				'max_time'      => '(Par défaut “22:00:00”) Heure maximale affichée',
				'default_view'  => '(Par défaut “listMonth”) Type d’affichage <a href=”https://fullcalendar.io/docs#main”>Option Views de fullcalendar</a>',
				'url'           => 'Url du calendrier à afficher (ICS)',
				'icon_size'     => '(Par défaut, 1em) Taille des icônes des évènements',
			]
		] );
	amapress_register_shortcode( 'amapress-amapien-agenda-viewer', function ( $atts ) {
		$atts        = shortcode_atts(
			[ 'since_days' => 30 ],
			$atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( false, intval( $atts['since_days'] ) );

		return amapress_fullcalendar( $atts );
	},
		[
			'desc' => 'Calendrier de l\'amapien',
			'args' => [
				'since_days'    => '(Par défaut 30) Nombre de jours d\'historique de l\'agenda',
				'header_left'   => '(Par défaut “prev,next today”) Option de personnalisation de l\’entête partie gauche, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_center' => '(Par défaut “title”) Option de personnalisation de l\’entête partie centrale, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_right'  => '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\’entête partie droite, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'min_time'      => '(Par défaut “08:00:00”) Heure minimale affichée',
				'max_time'      => '(Par défaut “22:00:00”) Heure maximale affichée',
				'icon_size'     => '(Par défaut, 1em) Taille des icônes des évènements',
				'default_view'  => '(Par défaut “listMonth”) Type d’affichage <a href=”https://fullcalendar.io/docs#main”>Option Views de fullcalendar</a>',
			]
		] );
	amapress_register_shortcode( 'amapress-public-agenda-viewer', function ( $atts ) {
		$atts        = shortcode_atts(
			[ 'since_days' => 30, 'url' => '' ],
			$atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( true, intval( $atts['since_days'] ) );
		amapress_consider_logged( false );
		$ret = amapress_fullcalendar( $atts );
		amapress_consider_logged( true );

		return $ret;
	},
		[
			'desc' => 'Calendrier publique de l\'AMAP',
			'args' => [
				'since_days'    => '(Par défaut 30) Nombre de jours d\'historique de l\'agenda',
				'header_left'   => '(Par défaut “prev,next today”) Option de personnalisation de l\’entête partie gauche, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_center' => '(Par défaut “title”) Option de personnalisation de l\’entête partie centrale, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_right'  => '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\’entête partie droite, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'min_time'      => '(Par défaut “08:00:00”) Heure minimale affichée',
				'max_time'      => '(Par défaut “22:00:00”) Heure maximale affichée',
				'icon_size'     => '(Par défaut, 1em) Taille des icônes des évènements',
				'default_view'  => '(Par défaut “listMonth”) Type d’affichage <a href=”https://fullcalendar.io/docs#main”>Option Views de fullcalendar</a>',
			]
		] );

	amapress_register_shortcode( 'amapien-adhesions', 'amapress_display_user_adhesions_shortcode',
		[
			'desc' => 'Liste des inscriptions aux contrats pour un amapien',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapien-edit-infos', 'amapress_edit_user_info_shortcode',
		[
			'desc' => 'Permet à un amapien de modifier ses coordonnées',
			'args' => [
				'max_cofoyers'          => '(3 par défaut) Nombre maximum de membres du foyer',
				'edit_names'            => '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription',
				'mob_phone_required'    => '(false par défaut) Téléphones (mobiles) requis',
				'allow_remove_cofoyers' => '(true par défaut) Autoriser la suppression des membres du foyers',
				'show_cofoyers_address' => '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer',
			]
		] );
	amapress_register_shortcode( 'amapien-messages', 'amapress_user_messages_shortcode' );
	amapress_register_shortcode( 'amapien-messages-count', 'amapress_user_messages_count_shortcode' );
	amapress_register_shortcode( 'amapien-paniers-intermittents', 'amapress_user_paniers_intermittents_shortcode',
		[
			'desc' => 'Paniers proposés/échangés par un amapien',
			'args' => [
				'show_history' => '(Par défaut “false”) Afficher l\’historique des échanges de paniers de l\’amapien/intermittent',
				'history_days' => '(Par défaut “180”) Nombre de jour de l\’historique',
				'show_futur'   => '(Par défaut “true”) Afficher les échanges à venir',
			]
		] );
	amapress_register_shortcode( 'amapien-paniers-intermittents-count', 'amapress_user_paniers_intermittents_count_shortcode',
		[
			'desc' => 'Nombre de paniers proposés/échangés par un amapien',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'les-paniers-intermittents', 'amapress_all_paniers_intermittents_shortcode',
		[
			'desc' => 'Paniers disponibles sur la liste des intermittents',
			'args' => [
				'contrat'        => 'Permet de filtrer les contrats pour lesquels les paneirs à échanger sont affichés',
				'allow_amapiens' => '(Par défaut “true”) Autoriser les amapiens à réserver des paniers',
			]
		] );
	amapress_register_shortcode( 'les-paniers-intermittents-count', 'amapress_all_paniers_intermittents_count_shortcode',
		[
			'desc' => 'Nombre de paniers disponibles sur la liste des intermittents',
			'args' => [
			]
		] );

	$contrats_conf_link = Amapress::makeLink(
		admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ),
		'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats'
	);
	amapress_register_shortcode( 'mes-contrats', 'amapress_mes_contrats',
		[
			'desc' => 'Permet l\'inscription aux contrats complémentaires en cours d\'année',
			'args' => [
				'ignore_renouv_delta'                 => '(booléen, true par défaut) : ignorer la marge de renouvellement des contrats terminés',
				'allow_inscriptions'                  => '(booléen, true par défaut) : autorise l\'inscription aux contrats',
				'check_adhesion_received'             => '(Configurable dans ' . $contrats_conf_link . ') : vérifier que l\'adhésion a été validée/reçue avant de permettre l\'inscription aux contrats',
				'check_adhesion_received_or_previous' => '(Configurable dans ' . $contrats_conf_link . ') : vérifier que l\'adhésion a été validée/reçue ou qu\'une adhésion précédente a été validée avant de permettre l\'inscription aux contrats',
				'allow_adhesion'                      => '(booléen, true par défaut) : autorise l\'adhésion à l\'AMAP',
				'filter_multi_contrat'                => '(booléen, false par défaut) : en cas de variante de contrat Semaine A/B/AB, ne pas autoriser un amapien à s\'inscrire à plusieurs variantes',
				'agreement'                           => '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), 'Tableau de bord > Gestion Contrats > onglet Assistant - Pré-inscription en ligne' ) . ')',
				'check_principal'                     => '(booléen, true par défaut) : vérifier qu\'un contrat principal est actif. Peut être désactivé globalement dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ), 'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats' ),
				'send_adhesion_confirm'               => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP',
				'allow_inscription_all_dates'         => '(booléen, false par défaut) : autoriser l\'inscription à partir de toutes les dates, y compris celles après la date de clôture du contrat',
				'send_contrat_confirm'                => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour chacune de ses inscriptions aux contrats',
				'send_referents'                      => '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux référents',
				'send_tresoriers'                     => '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers',
				'adhesion_shift_weeks'                => '(0 par défaut) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion',
				'paniers_modulables_editor_height'    => '(350 par défaut) Hauteur de l\'éditeur de paniers modulables (en px)',
				'show_details_button'                 => '(false par défaut) Afficher un bouton Détails pour accéder aux détails des inscriptions au lieu de les afficher directement dans la liste',
				'show_adherents_infos'                => '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents',
				'show_adhesion_infos'                 => '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin',
				'contact_referents'                   => '(true par défaut) Affiche un lien de contact des référents dans la liste des contrats déjà souscrit (étape 4/8)',
				'before_close_hours'                  => '(24 par défaut) Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant',
				'paiements_info_required'             => '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)',
				'max_produit_label_width'             => '(par défaut, 10em) Largeur maximal de la colonne Produit pour les inscriptions Paniers Modulables',
				'show_delivery_details'               => '(false par défaut) Afficher un lien Récapitulatif des livraisons',
				'show_due_amounts'                    => '(false par défaut) Afficher un lien Récapitulatif des sommes dues',
				'show_calendar_delivs'                => '(false par défaut) Afficher un lien Calendrier des livraisons',
				'allow_remove_coadhs'                 => '(false par défaut) Autoriser la suppression des co-adhérents',
				'allow_remove_cofoyers'               => '(true par défaut) Autoriser la suppression des membres du foyers',
				'show_coadherents_address'            => '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents',
				'show_cofoyers_address'               => '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer',
				'show_current_inscriptions'           => '(true par défaut) Afficher les inscriptions en cours et à venir',
				'show_editable_inscriptions'          => '(true par défaut) Afficher les inscriptions encore éditables',
				'use_quantite_tables'                 => '(false par défaut) (Paniers modulables) Afficher les quantités en tableaux (date en ligne, quantités en colonnes)',
				'show_modify_coords'                  => '(true par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer',
				'use_contrat_term'                    => '(true par défaut) Utiliser le terme Contrat si true et Commande si false',
				'only_contrats'                       => 'Filtrage des contrats affichés (par ID). Permet, par exemple, de faire une page dédiée aux paniers modulables et commandes' .
				                                         '<br/>Valeurs possibles: ' . implode( ' ; ', array_map( function ( $c ) {
						/** @var AmapressContrat $c */
						return sprintf( '%d (%s)', $c->ID, $c->getTitle() );
					}, AmapressContrats::get_contrats() ) ),
			]
		] );
	amapress_register_shortcode( 'inscription-en-ligne', 'amapress_self_inscription',
		[
			'desc' => 'Permet les inscriptions en ligne (amapien non connecté et nouveaux, sécurisée par une clé secrète)',
			'args' => [
				'key'                                 => '(Par exemple : ' . uniqid() . uniqid() . ') Clé de sécurisation de l\'accès à l\'Assistant de Préinscription en ligne. Utilisez key=public pour permettre un accès sans clé',
				'filter_multi_contrat'                => '(booléen, false par défaut) : en cas de variante de contrat Semaine A/B/AB, ne pas autoriser un amapien à s\'inscrire à plusieurs variantes',
				'agreement'                           => '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), 'Tableau de bord > Gestion Contrats > onglet Assistant - Pré-inscription en ligne' ) . ')',
				'check_principal'                     => '(booléen, true par défaut) : vérifier qu\'un contrat principal est actif. Peut être désactivé globalement dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ), 'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats' ),
				'check_adhesion_received'             => '(Configurable dans ' . $contrats_conf_link . ') : vérifier que l\'adhésion a été validée/reçue avant de permettre l\'inscription aux contrats',
				'check_adhesion_received_or_previous' => '(Configurable dans ' . $contrats_conf_link . ') : vérifier que l\'adhésion a été validée/reçue ou qu\'une adhésion précédente a été validée avant de permettre l\'inscription aux contrats',
				'adhesion'                            => '(booléen, true par défaut) : afficher une étape Adhésion à l\'AMAP',
				'allow_inscription_all_dates'         => '(booléen, false par défaut) : autoriser l\'inscription à partir de toutes les dates, y compris celles après la date de clôture du contrat',
				'send_adhesion_confirm'               => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP',
				'send_contrat_confirm'                => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour chacune de ses inscriptions aux contrats',
				'send_referents'                   => '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux référents',
				'send_tresoriers'                  => '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers',
				'edit_names'                       => '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription',
				'send_welcome'                     => '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens',
				'only_contrats'                    => 'Filtrage des contrats affichés (par ID). Permet de faire une page dédiée à l\'inscription à un/plusieurs contrat(s) donné(s) avec une autre clé' .
				                                         '<br/>Valeurs possibles: ' . implode( ' ; ', array_map( function ( $c ) {
						/** @var AmapressContrat $c */
						return sprintf( '%d (%s)', $c->ID, $c->getTitle() );
					}, AmapressContrats::get_contrats() ) ),
				'shorturl'                         => 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne',
				'adhesion_shift_weeks'             => '(0 par défaut) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion',
				'max_coadherents'                  => '(3 par défaut) Nombre maximum de co-adhérents',
				'max_cofoyers'                     => '(3 par défaut) Nombre maximum de membres du foyer',
				'mob_phone_required'               => '(false par défaut) Téléphones (mobiles) requis',
				'track_no_renews'                  => '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1',
				'track_no_renews_email'            => '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse',
				'notify_email'                     => '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)',
				'paniers_modulables_editor_height' => '(350 par défaut) Hauteur de l\'éditeur de paniers modulables (en px)',
				'show_details_button'              => '(false par défaut) Afficher un bouton Détails pour accéder aux détails des inscriptions au lieu de les afficher directement dans la liste',
				'show_adherents_infos'             => '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents',
				'show_adhesion_infos'              => '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin',
				'allow_coadherents_inscription'    => '(true par défaut) Autoriser l\'inscription aux contrats par les co-adhérents',
				'allow_coadherents_access'         => '(true par défaut) Autoriser l\accès aux co-adhérents',
				'allow_coadherents_adhesion'       => '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents',
				'allow_remove_coadhs'              => '(false par défaut) Autoriser la suppression des co-adhérents',
				'allow_remove_cofoyers'            => '(true par défaut) Autoriser la suppression des membres du foyers',
				'show_coadherents_address'         => '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents',
				'show_cofoyers_address'            => '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer',
				'show_delivery_details'            => '(false par défaut) Afficher un lien Récapitulatif des livraisons',
				'show_calendar_delivs'             => '(false par défaut) Afficher un lien Calendrier des livraisons',
				'show_current_inscriptions'        => '(true par défaut) Afficher les inscriptions en cours et à venir',
				'show_editable_inscriptions'       => '(true par défaut) Afficher les inscriptions encore éditables',
				'use_quantite_tables'              => '(false par défaut) (Paniers modulables) Afficher les quantités en tableaux (date en ligne, quantités en colonnes)',
				'show_due_amounts'                 => '(false par défaut) Afficher un lien Récapitulatif des sommes dues',
				'show_modify_coords'               => '(false par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer',
				'contact_referents'                => '(true par défaut) Affiche un lien de contact des référents dans la liste des contrats déjà souscrit (étape 4/8)',
				'before_close_hours'               => '(24 par défaut) Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant',
				'paiements_info_required'          => '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)',
				'email'                            => '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème',
				'max_produit_label_width'          => '(par défaut, 10em) Largeur maximal de la colonne Produit pour les inscriptions Paniers Modulables',
				'use_contrat_term'                 => '(true par défaut) Utiliser le terme Contrat si true et Commande si false',
				'allow_adhesion_alone'             => '(false par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription',
				'check_honeypots'                  => '(true par défaut) Détecter et bloquer les bots de spam pour une utilisation publique (key=public) de l\'assistant',
			]
		] );
	amapress_register_shortcode( 'inscription-en-ligne-connecte', 'amapress_logged_self_inscription',
		[
			'desc' => 'Permet les inscriptions en ligne (amapien connecté)',
			'args' => [
				'filter_multi_contrat'                => '(booléen, false par défaut) : en cas de variante de contrat Semaine A/B/AB, ne pas autoriser un amapien à s\'inscrire à plusieurs variantes',
				'agreement'                           => '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), 'Tableau de bord > Gestion Contrats > onglet Assistant - Pré-inscription en ligne' ) . ')',
				'check_principal'                     => '(booléen, true par défaut) : vérifier qu\'un contrat principal est actif. Peut être désactivé globalement dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=contrat_config' ), 'Tableau de bord>Gestion Contrats>Configuration, onglet Contrats' ),
				'check_adhesion_received'             => '(Configurable dans ' . $contrats_conf_link . ') : vérifier que l\'adhésion a été validée/reçue avant de permettre l\'inscription aux contrats',
				'check_adhesion_received_or_previous' => '(Configurable dans ' . $contrats_conf_link . ') : vérifier que l\'adhésion a été validée/reçue ou qu\'une adhésion précédente a été validée avant de permettre l\'inscription aux contrats',
				'adhesion'                            => '(booléen, true par défaut) : afficher une étape Adhésion à l\'AMAP',
				'allow_inscription_all_dates'         => '(booléen, false par défaut) : autoriser l\'inscription à partir de toutes les dates, y compris celles après la date de clôture du contrat',
				'send_adhesion_confirm'               => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP',
				'send_contrat_confirm'                => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour chacune de ses inscriptions aux contrats',
				'send_referents'                      => '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux référents',
				'send_tresoriers'                     => '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers',
				'edit_names'                          => '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription',
				'only_contrats'                    => 'Filtrage des contrats affichés (par ID). Permet de faire une page dédiée à l\'inscription à un/plusieurs contrat(s) donné(s) ou commande(s)' .
				                                         '<br/>Valeurs possibles: ' . implode( ' ; ', array_map( function ( $c ) {
						/** @var AmapressContrat $c */
						return sprintf( '%d (%s)', $c->ID, $c->getTitle() );
					}, AmapressContrats::get_contrats() ) ),
				'adhesion_shift_weeks'             => '(0 par défaut) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion',
				'max_coadherents'                  => '(3 par défaut) Nombre maximum de co-adhérents',
				'max_cofoyers'                     => '(3 par défaut) Nombre maximum de membres du foyer',
				'mob_phone_required'               => '(false par défaut) Téléphones (mobiles) requis',
				'track_no_renews'                  => '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1',
				'track_no_renews_email'            => '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse',
				'notify_email'                     => '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)',
				'paniers_modulables_editor_height' => '(350 par défaut) Hauteur de l\'éditeur de paniers modulables (en px)',
				'show_details_button'              => '(false par défaut) Afficher un bouton Détails pour accéder aux détails des inscriptions au lieu de les afficher directement dans la liste',
				'show_adherents_infos'             => '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents',
				'show_adhesion_infos'              => '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin',
				'allow_coadherents_inscription'    => '(true par défaut) Autoriser l\'inscription aux contrats par les co-adhérents',
				'allow_coadherents_access'         => '(true par défaut) Autoriser l\accès aux co-adhérents',
				'allow_coadherents_adhesion'       => '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents',
				'allow_remove_coadhs'              => '(false par défaut) Autoriser la suppression des co-adhérents',
				'allow_remove_cofoyers'            => '(true par défaut) Autoriser la suppression des membres du foyers',
				'show_coadherents_address'         => '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents',
				'show_cofoyers_address'            => '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer',
				'show_delivery_details'            => '(false par défaut) Afficher un lien Récapitulatif des livraisons',
				'show_due_amounts'                 => '(false par défaut) Afficher un lien Récapitulatif des sommes dues',
				'show_modify_coords'               => '(true par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer',
				'show_calendar_delivs'             => '(false par défaut) Afficher un lien Calendrier des livraisons',
				'show_current_inscriptions'        => '(false par défaut) Afficher les inscriptions en cours et à venir',
				'show_editable_inscriptions'       => '(true par défaut) Afficher les inscriptions encore éditables',
				'use_quantite_tables'              => '(false par défaut) (Paniers modulables) Afficher les quantités en tableaux (date en ligne, quantités en colonnes)',
				'contact_referents'                => '(true par défaut) Affiche un lien de contact des référents dans la liste des contrats déjà souscrit (étape 4/8)',
				'before_close_hours'               => '(24 par défaut) Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant',
				'paiements_info_required'          => '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)',
				'email'                            => '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème',
				'max_produit_label_width'          => '(par défaut, 10em) Largeur maximal de la colonne Produit pour les inscriptions Paniers Modulables',
				'use_contrat_term'                 => '(true par défaut) Utiliser le terme Contrat si true et Commande si false',
				'skip_coords'                      => '(false par défaut) Passer l\'étape de saisie des coordonnées et des coadhérents',
				'allow_adhesion_alone'             => '(false par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription',
			]
		] );

	amapress_register_shortcode( 'adhesion-en-ligne', 'amapress_self_adhesion',
		[
			'desc' => 'Permet les adhésions en ligne (amapien non connecté et nouveaux, sécurisée par une clé secrète) uniquement (pas d\'inscription aux contrats)',
			'args' => [
				'key'                        => '(Par exemple : ' . uniqid() . uniqid() . ') Clé de sécurisation de l\'accès à l\'Assistant de Préinscription en ligne. Utilisez key=public pour permettre un accès sans clé',
				'agreement'                  => '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), 'Tableau de bord > Gestion Contrats > onglet Assistant - Pré-inscription en ligne' ) . ')',
				'send_adhesion_confirm'      => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP',
				'send_tresoriers'            => '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers',
				'edit_names'                 => '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription',
				'send_welcome'               => '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens',
				'shorturl'                   => 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne',
				'max_coadherents'            => '(3 par défaut) Nombre maximum de co-adhérents',
				'max_cofoyers'               => '(3 par défaut) Nombre maximum de membres du foyer',
				'mob_phone_required'         => '(false par défaut) Téléphones (mobiles) requis',
				'track_no_renews'            => '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1',
				'track_no_renews_email'      => '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse',
				'notify_email'               => '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)',
				'show_adherents_infos'       => '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents',
				'show_adhesion_infos'        => '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin',
				'allow_coadherents_access'   => '(true par défaut) Autoriser l\accès aux co-adhérents',
				'allow_coadherents_adhesion' => '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents',
				'allow_remove_coadhs'        => '(false par défaut) Autoriser la suppression des co-adhérents',
				'allow_remove_cofoyers'      => '(true par défaut) Autoriser la suppression des membres du foyers',
				'show_coadherents_address'   => '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents',
				'show_cofoyers_address'      => '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer',
				'show_modify_coords'         => '(false par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer',
				'paiements_info_required'    => '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)',
				'email'                      => '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème',
				'allow_adhesion_alone'       => '(true par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription',
				'check_honeypots'            => '(true par défaut) Détecter et bloquer les bots de spam pour une utilisation publique (key=public) de l\'assistant',
			]
		] );
	amapress_register_shortcode( 'adhesion-en-ligne-connecte', 'amapress_logged_self_adhesion',
		[
			'desc' => 'Permet les adhésions en ligne (amapien connecté) uniquement (pas d\'inscription aux contrats)',
			'args' => [
				'agreement'                  => '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), 'Tableau de bord > Gestion Contrats > onglet Assistant - Pré-inscription en ligne' ) . ')',
				'send_adhesion_confirm'      => '(booléen, true par défaut) : envoyer une confirmation à l\'amapien pour son adhésion à l\'AMAP',
				'send_tresoriers'            => '(booléen, true par défaut) : envoyer une notification pour les nouvelles adhésions aux trésoriers',
				'edit_names'                 => '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription',
				'send_welcome'               => '(booléen, false par défaut si New User Approve est actif, false sinon) : permet de désactiver l\'envoi automatique du mail de bienvenue aux nouveaux amapiens',
				'shorturl'                   => 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne',
				'max_coadherents'            => '(3 par défaut) Nombre maximum de co-adhérents',
				'max_cofoyers'               => '(3 par défaut) Nombre maximum de membres du foyer',
				'mob_phone_required'         => '(false par défaut) Téléphones (mobiles) requis',
				'track_no_renews'            => '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1',
				'track_no_renews_email'      => '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse',
				'notify_email'               => '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)',
				'show_adherents_infos'       => '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents',
				'show_adhesion_infos'        => '(true par défaut) Afficher la validité de l\'adhésion et le bouton d\'impression du bulletin',
				'allow_coadherents_access'   => '(true par défaut) Autoriser l\accès aux co-adhérents',
				'allow_coadherents_adhesion' => '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents',
				'allow_remove_coadhs'        => '(false par défaut) Autoriser la suppression des co-adhérents',
				'allow_remove_cofoyers'      => '(true par défaut) Autoriser la suppression des membres du foyers',
				'show_coadherents_address'   => '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents',
				'show_cofoyers_address'      => '(false par défaut) Afficher la saisie d\'adresse pour les membres du foyer',
				'show_modify_coords'         => '(false par défaut) Afficher un bouton pour modifier les coordonnées, co-adhérents et membres du foyer',
				'paiements_info_required'    => '(false par défaut) Rendre la saisie des numéros de chèques obligatoire (adhésion AMAP et inscriptions aux contrats)',
				'email'                      => '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème',
				'allow_adhesion_alone'       => '(true par défaut) Autoriser l\'adhésion même si aucun contrat n\'est ouvert à l\'inscription',
			]
		] );

	amapress_register_shortcode( 'intermittent-paniers', 'amapress_intermittent_paniers_shortcode',
		[
			'desc' => 'Paniers réservés par un intermittent',
			'args' => [
				'show_history' => '(Par défaut “false”)  Afficher l\’historique des échanges de paniers de l\’amapien/intermittent',
				'history_days' => '(Par défaut “30”) Nombre de jour de l\’historique',
				'show_futur'   => '(Par défaut “true”) Afficher les échanges à venir',
			]
		] );


	amapress_register_shortcode( 'amapiens-map', 'amapress_amapiens_map_shortcode',
		[
			'desc' => 'Carte des amapiens',
			'args' => [
				'lieu'            => 'Afficher les amapiens ayant un contrat dans le lieu de distribution indiqué',
				'show_email'      => '(Par défaut “default”) Afficher les emails des amapiens',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones des amapiens',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de fixes des amapiens',
				'show_tel_mobile' => '(Par défaut “default”) Afficher les numéros de portables des amapiens',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses des amapiens',
				'show_avatar'     => '(Par défaut “default”) Afficher les photos des amapiens',
				'show_lieu'       => '(Par défaut “default”) Afficher le nom du lieu de distribution',
			]
		] );
	amapress_register_shortcode( 'amapiens-role-list', 'amapress_amapiens_role_list_shortcode',
		[
			'desc' => 'Liste des membres du collectif de l\'AMAP',
			'args' => [
				'lieu'            => 'Afficher les membres du collectif du lieu de distribution indiqué',
				'show_prod'       => '(Par défaut “false”) Afficher les producteurs',
				'show_email'      => '(Par défaut “force”) Afficher les emails des membres du collectif',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones des membres du collectif',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de fixes des membres du collectif',
				'show_tel_mobile' => '(Par défaut “force”) Afficher les numéros de portables des membres du collectif',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses des membres du collectif',
				'show_avatar'     => '(Par défaut “default”) Afficher les photos des membres du collectif',
			]
		] );
	amapress_register_shortcode( 'user-info', 'amapress_user_info_shortcode' );
	amapress_register_shortcode( 'next_events', 'amapress_next_events_shortcode',
		[
			'desc' => 'Calendrier des prochains évènements (Slider)',
			'args' => [
			]
		] );

	if ( amapress_is_user_logged_in() ) {
		amapress_register_shortcode( 'intermittent-desinscription-href', 'amapress_intermittence_desinscription_link',
			[
				'desc' => 'Lien de désinscription des intermittents',
				'args' => [
				]
			] );
	}

	amapress_register_shortcode( 'next-distrib-href', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Url de la page de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-link', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Lien vers la page de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-date', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Date de la prochaine distribution',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-emargement-href', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Url de la page de la liste d\'émargement de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-emargement-link', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Lien vers la page de la liste d\'émargement de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-redirect-next-distrib', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Redirige vers la page de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-redirect-next-emargement', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Redirige vers la page de la liste d\'émargement de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-deliv', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Listes des prochaines distributions avec contrats livrés',
			'args' => [
				'distrib' => '(Par défaut 5) Nombre de distributions',
			]
		] );
	amapress_register_shortcode( 'next-distrib-deliv-paniers', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Listes des prochaines distributions avec contenus des paniers',
			'args' => [
				'distrib' => '(Par défaut 5) Nombre de distributions',
			]
		] );

	amapress_register_shortcode( 'liste-emargement-button', function ( $atts, $content = null ) {
		if ( is_singular( AmapressDistribution::INTERNAL_POST_TYPE ) ) {
			$dist_id = get_the_ID();
			if ( empty( $content ) ) {
				$content = 'Imprimer la liste d\'émargement';
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
			'desc' => 'Afficher les détails des livraisons de l\'amapien par date ou producteur',
			'args' => [
				'by'                  => '(Par défaut "date") Grouper les livraisons par "date" ou "producteur"',
				'ignore_renouv_delta' => '(Par défaut true) Ignorer les contrats qui sont dans leur période de renouvellement',
			]
		] );
	amapress_register_shortcode( 'amapien-details-paiements', function ( $atts, $content = null ) {
		$atts                = shortcode_atts(
			array(
				'user_id'             => null,
				'ignore_renouv_delta' => true,
			), $atts
		);
		$user_id             = ! empty( $atts['user_id'] ) ? intval( $atts['user_id'] ) : amapress_current_user_id();
		$ignore_renouv_delta = Amapress::toBool( $atts['ignore_renouv_delta'] );

		return amapress_get_details_all_paiements( $user_id, $ignore_renouv_delta );
	},
		[
			'desc' => 'Afficher le détails des sommes dues par l\'amapien',
			'args' => [
				'ignore_renouv_delta' => '(Par défaut true) Ignorer les contrats qui sont dans leur période de renouvellement',
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
			'desc' => 'Afficher le calendrier des livraisons des contrats de l\'amapien ou de tous les contrats',
			'args' => [
				'filter'              => '(Par défaut "all") Afficher tous les contrats actifs (all), les contrats ouverts aux inscriptions (subscribables) ou les contrats de l\'amapien (user)',
				'ignore_renouv_delta' => '(Par défaut true) Ignorer les contrats qui sont dans leur période de renouvellement',
				'ignore_futur'        => '(Par défaut true) Inclure les contrats non commencés',
			]
		] );

	amapress_register_shortcode( 'display-if', function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'role' => 'logged',
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
				default:
					if ( strpos( $role, 'contrat_' ) === 0 ) {
						$contrat_id = Amapress::resolve_post_id( substr( $role, 8 ), AmapressContrat::INTERNAL_POST_TYPE );
						$show       = $show || count( AmapressContrat_instance::getContratInstanceIdsForUser( null, $contrat_id ) ) > 0;
					} else {
						$show = $show || amapress_current_user_can( $role );
					}
			}
		}

		return $show ? $content : '';
	},
		[
			'desc' => 'Affiche le contenu du shortcode suivant une condition (connecté, non connecté, membre du collectif, intermittent, responsable de distribution)',
			'args' => [
				'role' => '(Par défaut "logged") Afficher le contenu de ce shortcode uniquement si l\'amapien est dans un des rôles suivants : logged, not_logged, intermittent, no_contrat, responsable_distrib (est responsable de distribution cette semaine), responsable_amap (peut accéder au Tableau de Bord), contrat_xxx (a un contrat xxx)'
			]
		] );

	amapress_register_shortcode( 'display-if-logged', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=logged]' );
	},
		[
			'desc' => 'Affiche le contenu du shortcode si l\'amapien est connecté',
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-not-logged', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=not_logged]' );
	},
		[
			'desc' => 'Affiche le contenu du shortcode si l\'amapien n\'est pas connecté',
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-no-contrat', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=no_contrat]' );
	},
		[
			'desc' => 'Affiche le contenu du shortcode si l\'amapien n\'a pas de contrat en cours',
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-intermittent', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=intermittent]' );
	},
		[
			'desc' => 'Affiche le contenu du shortcode si l\'amapien est intermittent',
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-responsable-distrib', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=responsable_distrib]' );
	},
		[
			'desc' => 'Affiche le contenu du shortcode si l\'amapien est responsable de distribution cette semaine',
			'args' => []
		] );
	amapress_register_shortcode( 'display-if-responsable-amap', function ( $atts, $content = null ) {
		return do_shortcode( '[display-if role=responsable_amap]' );
	},
		[
			'desc' => 'Affiche le contenu du shortcode si l\'amapien a accès au Tableau de bord (responsables AMAP)',
			'args' => []
		] );

	amapress_register_shortcode( 'responsable-distrib-info', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			[
				'distribution_link' => 'true',
				'emargement_link'   => 'true'
			], $atts
		);

		$ret = '';
		if ( AmapressDistributions::isCurrentUserResponsableThisWeek() ) {
			$next_distrib = AmapressDistribution::getNextDistribution( null, null, Amapress::start_of_week( amapress_time() ) );
			if ( ! $next_distrib ) {
				return '';
			}
			$ret = '<div class="resp-distrib-this-week"><p>Vous êtes responsable de distribution '
			       . ( Amapress::toBool( $atts['distribution_link'] ) ?
					Amapress::makeLink( $next_distrib->getPermalink(), 'cette semaine' ) :
					'cette semaine' ) . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			if ( Amapress::toBool( $atts['emargement_link'] ) ) {
				$ret .= '<br/>' . Amapress::makeLink( $next_distrib->getListeEmargementHref(), 'Liste d\'émargement' );
			}
			$ret .= '</p></div>';
		} elseif ( AmapressDistributions::isCurrentUserResponsableNextWeek() ) {
			$next_distrib = AmapressDistribution::getNextDistribution( null, null, Amapress::add_a_week( Amapress::start_of_week( amapress_time() ) ) );
			if ( ! $next_distrib ) {
				return '';
			}

			$ret = '<div class="resp-distrib-this-week"><p>Vous êtes responsable de distribution '
			       . ( Amapress::toBool( $atts['distribution_link'] ) ?
					Amapress::makeLink( $next_distrib->getPermalink(), 'la semaine prochaine' ) :
					'la semaine prochaine' ) . '(' . date_i18n( 'd/m/Y', $next_distrib->getDate() ) . ')';
			if ( Amapress::toBool( $atts['emargement_link'] ) ) {
				$ret .= '<br/>' . Amapress::makeLink( $next_distrib->getListeEmargementHref(), 'Liste d\'émargement' );
			}
			$ret .= '</p></div>';
		}

		return $ret;
	},
		[
			'desc' => 'Afficher un message "Vous êtes responsable de distribution cette semaine/la semaine prochaine"',
			'args' => [
				'distribution_link' => '(Par défaut true) affiche un lien vers la distribution.',
				'emargement_link'   => '(Par défaut true) affiche un lien vers la liste d\'émargement.',
			]
		] );

	amapress_register_shortcode( 'nous-contacter', function ( $atts ) {
		amapress_ensure_no_cache();

		return Amapress::getContactInfos();
	},
		[
			'desc' => 'Contenu du formulaire de contact',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'agenda-url', function ( $atts ) {
		$atts = shortcode_atts(
			[ 'since_days' => 30 ],
			$atts );
		$id   = 'agenda-url-' . md5( uniqid() );
		$url  = esc_attr( Amapress_Agenda_ICAL_Export::get_link_href( false, intval( $atts['since_days'] ) ) );

		return "<div class='input-group'><input id='$id' type='text' value='$url' class='form-control' style='max-width: 80%' /><span class='input-group-addon'><button class='btn btn-secondary copy-agenda-url' type='button' data-clipboard-target='#{$id}'><span class='fa fa-copy' /></button></span><script type='text/javascript'>jQuery(function($) { new Clipboard('.copy-agenda-url'); });</script></div>";
	},
		[
			'desc' => 'Copieur de lien de configuration de la synchronisation d\'un calendrier ICAL dans l\'agenda de l\'amapien',
			'args' => [
				'since_days' => '(Par défaut 30) Nombre de jours d\'historique de l\'agenda',
			]
		] );

	amapress_register_shortcode( 'agenda-url-button', function ( $atts ) {
		$atts = shortcode_atts(
			[
				'since_days' => 30,
				'title'      => 'Ajouter mon calendrier AMAP à mon agenda'
			],
			$atts );
		$id   = 'agenda-url_btn-' . md5( uniqid() );
		$url  = Amapress_Agenda_ICAL_Export::get_link_href( false, intval( $atts['since_days'] ) );
		$url  = preg_replace( '/^http/', 'webcal', $url );
		$url  = esc_attr( $url );

		return Amapress::makeButtonLink( $url, $atts['title'], true, true, 'btn btn-default btn-add-cal' );
	},
		[
			'desc' => 'Bouton d\'ajout de synchronisation de son calendrier (ICAL) dans l\'agenda de l\'amapien',
			'args' => [
				'since_days' => '(Par défaut 30) Nombre de jours d\'historique de l\'agenda',
				'title'      => '(Par défaut Ajouter mon calendrier AMAP à mon agenda) Titre du bouton',
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
		$agenda = '<h3 id="front-adgenda-title">' . ( amapress_is_user_logged_in() ? Amapress::getOption( 'front_agenda_title', 'Cette semaine dans mon panier...' ) : Amapress::getOption( 'front_agenda_public_title', 'Agenda' ) ) . '</h3>' .
		          $agenda_content;

//    }
		return $agenda;
	},
		[
			'desc' => 'Affiche le calendrier de la page d\'accueil',
			'args' => [
				'title' => '(Par défaut “yes”) Afficher le titre de la section',
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
			$produits = '<h3 id="front-produits-title">' . Amapress::getOption( 'front_produits_title', 'Les produits de l\'Amap...' ) . '</h3>';
		}
		if ( trim( wp_strip_all_tags( $produits_content, true ) ) != '' ) {
			$interm = '';
//        if (Amapress::isIntermittenceEnabled() && Amapress::userCanRegister()) {
//            $interm = amapress_get_button('Devenir intermittent', Amapress::getMesInfosSublink('adhesions/intermittence/inscription'));
//        }
//			if ( Amapress::isIntermittenceEnabled() ) {
//				$interm = do_shortcode( '[intermittents-inscription view=me show_info=no]' );
//			}

			$produits .= $produits_content . $interm;
		}

		return $produits;
	},
		[
			'desc' => 'Affiche la liste des producteurs/productions pour la page d\'acceuil',
			'args' => [
				'title' => '(Par défaut “yes”) Afficher le titre de la section',
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
		$map = '<h3 id="front-map-title">' . Amapress::getOption( 'front_map_title', 'Où nous trouver ?' ) . '</h3>' . $map_content;

		return $map;
	},
		[
			'desc' => 'Affiche la carte des lieux de distributions pour la page d\'accueil',
			'args' => [
				'title' => '(Par défaut “yes”) Afficher le titre de la section',
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
			'desc' => 'Affiche les infos de la page d\'accueil (calendrier, productions, carte)',
			'args' => [
				'title'            => '(Par défaut “yes”) Afficher le titre des trois sections (Agenda/Produits/Carte) de la grille par défaut',
				'agenda-classes'   => '(Par défaut “col-lg-4 col-md-6 col-sm-6 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille',
				'produits-classes' => '(Par défaut “col-lg-4 col-md-6 col-sm-6 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille',
				'map-classes'      => '(Par défaut “col-lg-4 col-md-12 col-sm-12 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille',
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
				$li .= '<br/>' . Amapress::makeLink( $mailing_list_configuration->getAdminMembersLink(), 'Voir les membres', true, true );
			}
			if ( current_user_can( 'manage_options' ) ) {
				$li .= '<br/>' . Amapress::makeLink( $mailing_list_configuration->getAdminEditLink(), 'Voir la configuration', true, true );
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
				$li .= '<br/>' . Amapress::makeLink( $ml->getAdminMembersLink(), 'Voir les membres', true, true );
			}
			if ( current_user_can( 'manage_options' ) ) {
				$li .= '<br/>' . Amapress::makeLink( $ml->getAdminEditLink(), 'Voir la configuration', true, true );
			}
			$li        .= '</li>';
			$entries[] = $li;
		}
		sort( $entries );

		if ( empty( $entries ) ) {
			echo '<p>Aucune mailing list et aucun Emails groupés configuré</p>';
		} else {
			echo '<ul class="sh-listes-diffusions" style="margin-left: 1.5em;list-style: disc">';
			echo implode( '', $entries );
			echo '</ul>';
		}

		return ob_get_clean();
	},
		[
			'desc' => 'Liste des liste de diffusions (SYMPA/SudOuest/Emails groupés) configurées sur le site',
			'args' => [
				'sms' => '(Par défaut “yes”) Afficher un lien SMS-To contenant tous les membres de chaque liste de diffusion',
			]
		] );
}

