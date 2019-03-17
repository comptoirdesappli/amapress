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
			]
		] );
	amapress_register_shortcode( 'produits', 'amapress_produits_shortcode',
		[
			'desc' => 'Gallerie de produits',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'lieu-map', 'amapress_lieu_map_shortcode',
		[
			'desc' => 'Emplacement d\'un lieu (carte et StreetView)',
			'args' => [
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
			]
		] );
	amapress_register_shortcode( 'liste-inscription-distrib', function ( $args ) {
		$args         = shortcode_atts(
			[
				'lieu'       => 0,
				'show_title' => 'false',
			],
			$args
		);
		$dist_lieu_id = 0;
		if ( ! empty( $args['lieu'] ) ) {
			$dist_lieu_id = Amapress::resolve_post_id( $dist_lieu_id, AmapressLieu_distribution::INTERNAL_POST_TYPE );

			return do_shortcode( '[inscription-distrib for_pdf=true show_title=' . $args['show_title'] . ' for_emargement=true show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=true max_dates=52 lieu=' . $dist_lieu_id . ']' );
		} else {
			$ret = '';
			foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
				$ret .= do_shortcode( '[inscription-distrib for_pdf=true show_title=' . $args['show_title'] . ' for_emargement=true show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=true max_dates=52 lieu=' . $lieu_id . ']' );
			}

			return $ret;
		}
	},
		[
			'desc' => 'Liste statique des inscrits des responsables aux distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'inscription-distrib', 'amapress_inscription_distrib_shortcode',
		[
			'desc' => 'Inscriptions comme responsable de distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'inscription-visite', 'amapress_inscription_visite_shortcode',
		[
			'desc' => 'Inscripions aux visites à la ferme',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'inscription-amap-event', 'amapress_inscription_amap_event_shortcode',
		[
			'desc' => 'Inscriptions aux évènements AMAP',
			'args' => [
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
	amapress_register_shortcode( 'intermittents-inscription', 'amapress_intermittence_inscription_shortcode',
		[
			'desc' => 'Inscription d\'un amapien à la liste des intermittents',
			'args' => [
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
			'desc' => '',
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
			'desc' => 'Calendrier de l\'AMAP',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-amapien-agenda-viewer', function ( $atts ) {
		$atts        = wp_parse_args( $atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( false );

		return amapress_fullcalendar( $atts );
	},
		[
			'desc' => 'Calendrier de l\'amapien',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-public-agenda-viewer', function ( $atts ) {
		$atts        = wp_parse_args( $atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( true );
		amapress_consider_logged( false );
		$ret = amapress_fullcalendar( $atts );
		amapress_consider_logged( true );

		return $ret;
	},
		[
			'desc' => 'Calendrier publique de l\'AMAP',
			'args' => [
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
			]
		] );
	amapress_register_shortcode( 'amapien-messages', 'amapress_user_messages_shortcode' );
	amapress_register_shortcode( 'amapien-messages-count', 'amapress_user_messages_count_shortcode' );
	amapress_register_shortcode( 'amapien-paniers-intermittents', 'amapress_user_paniers_intermittents_shortcode',
		[
			'desc' => 'Paniers proposés/échangés par un amapien',
			'args' => [
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
			]
		] );
	amapress_register_shortcode( 'les-paniers-intermittents-count', 'amapress_all_paniers_intermittents_count_shortcode',
		[
			'desc' => 'Nombre de paniers disponibles sur la liste des intermittents',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'mes-contrats', 'amapress_mes_contrats',
		[
			'desc' => 'Permet l\'inscription aux contrats complémentaires en cours d\'année',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'inscription-en-ligne', 'amapress_self_inscription',
		[
			'desc' => 'Permet les inscriptions en ligne',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'intermittent-paniers', 'amapress_intermittent_paniers_shortcode',
		[
			'desc' => 'Paniers réservés par un intermittent',
			'args' => [
			]
		] );


	amapress_register_shortcode( 'amapiens-map', 'amapress_amapiens_map_shortcode',
		[
			'desc' => 'Carte des amapiens',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapiens-role-list', 'amapress_amapiens_role_list_shortcode',
		[
			'desc' => 'Liste des membres du collectif de l\'AMAP',
			'args' => [
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
		$id  = 'agenda-url-' . md5( uniqid() );
		$url = esc_attr( Amapress_Agenda_ICAL_Export::get_link_href() );

		return "<div class='input-group'><input id='$id' type='text' value='$url' class='form-control' style='max-width: 80%' /><span class='input-group-addon'><button class='btn btn-secondary copy-agenda-url' type='button' data-clipboard-target='#{$id}'><span class='fa fa-copy' /></button></span><script type='text/javascript'>jQuery(function() { new Clipboard('.copy-agenda-url'); });</script></div>";
	},
		[
			'desc' => 'Copieur de lien de configuration de la synchronisation d\'un calendrier ICAL dans l\'agenda de l\'amapien',
			'args' => [
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
			]
		] );

	amapress_register_shortcode( 'listes-diffusions', function ( $atts ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'sms' => 'yes',
			),
			$atts );

		ob_start();

		$do_sms_link = Amapress::toBool( $atts['sms'] ) && amapress_can_access_admin();
		echo '<ul>';
		foreach ( Amapress_MailingListConfiguration::getAll() as $mailing_list_configuration ) {
			echo '<li>';
			$name = $mailing_list_configuration->getAddress();
			$desc = $mailing_list_configuration->getDescription();
			echo Amapress::makeLink( "mailto:$name", $name );
			if ( $do_sms_link ) {
				echo ' ; ' . Amapress::makeLink( $mailing_list_configuration->getMembersSMSTo(), 'Envoyer un SMS aux membres' );
			}
			if ( ! empty( $desc ) ) {
				echo "<br/><em>$desc</em>";
			}
			echo '</li>';
		}
		echo '</ul>';

		return ob_get_clean();
	},
		[
			'desc' => 'Liste des liste de diffusions (SYMPA/SudOuest) configurées sur le site',
			'args' => [
			]
		] );
}

