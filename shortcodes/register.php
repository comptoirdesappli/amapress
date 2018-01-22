<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/generic.map.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/generic.pager.php' );
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

add_filter( 'amapress_init', 'amapress_register_shortcodes' );
function amapress_register_shortcodes() {
	amapress_register_shortcode( 'paged_gallery', 'amapress_generic_paged_gallery_shortcode' );
	amapress_register_shortcode( 'nous-trouver', 'amapress_where_to_find_us_shortcode' );
	amapress_register_shortcode( 'recettes', 'amapress_recettes_shortcode' );
	amapress_register_shortcode( 'produits', 'amapress_produits_shortcode' );
	amapress_register_shortcode( 'lieu-map', 'amapress_lieu_map_shortcode' );
	amapress_register_shortcode( 'user-map', 'amapress_user_map_shortcode' );
	amapress_register_shortcode( 'producteur-map', 'amapress_producteur_map_shortcode' );
	amapress_register_shortcode( 'amapien-avatar', 'amapress_amapien_avatar_shortcode' );
	amapress_register_shortcode( 'inscription-distrib', 'amapress_inscription_distrib_shortcode' );
	amapress_register_shortcode( 'inscription-visite', 'amapress_inscription_visite_shortcode' );

//    amapress_register_shortcode('paniers-intermittents-list', 'amapress_intermittents_paniers_list_shortcode');
	amapress_register_shortcode( 'echanger-paniers-list', 'amapress_echanger_panier_shortcode' );
//
	amapress_register_shortcode( 'intermittents-inscription', 'amapress_intermittence_inscription_shortcode' );
	amapress_register_shortcode( 'intermittents-desinscription', 'amapress_intermittence_desinscription_shortcode' );


	amapress_register_shortcode( 'adhesion-request-count', 'amapress_adhesion_request_count_shortcode' );

	amapress_register_shortcode( 'amapress-post-its', 'amapress_postits_shortcode' );

	amapress_register_shortcode( 'amapien-adhesions', 'amapress_display_user_adhesions_shortcode' );
	amapress_register_shortcode( 'amapien-edit-infos', 'amapress_edit_user_info_shortcode' );
	amapress_register_shortcode( 'amapien-messages', 'amapress_user_messages_shortcode' );
	amapress_register_shortcode( 'amapien-messages-count', 'amapress_user_messages_count_shortcode' );
	amapress_register_shortcode( 'amapien-paniers-intermittents', 'amapress_user_paniers_intermittents_shortcode' );
	amapress_register_shortcode( 'amapien-paniers-intermittents-count', 'amapress_user_paniers_intermittents_count_shortcode' );
	amapress_register_shortcode( 'les-paniers-intermittents', 'amapress_all_paniers_intermittents_shortcode' );
	amapress_register_shortcode( 'les-paniers-intermittents-count', 'amapress_all_paniers_intermittents_count_shortcode' );

//    amapress_register_shortcode('intermittent-adhesions', 'amapress_display_intermittent_adhesions_shortcode');
//    amapress_register_shortcode('intermittent-inscription', 'amapress_display_intermittent_inscription_shortcode');
	amapress_register_shortcode( 'intermittent-paniers', 'amapress_intermittent_paniers_shortcode' );


	amapress_register_shortcode( 'amapiens-map', 'amapress_amapiens_map_shortcode' );
	amapress_register_shortcode( 'amapiens-role-list', 'amapress_amapiens_role_list_shortcode' );
	amapress_register_shortcode( 'contrat-info', 'amapress_contrat_info_shortcode' );
	amapress_register_shortcode( 'user-info', 'amapress_user_info_shortcode' );
	amapress_register_shortcode( 'contrat-title', 'amapress_contrat_title_shortcode' );
	amapress_register_shortcode( 'contrat-header', 'amapress_contrat_header_shortcode' );
	amapress_register_shortcode( 'contrat-footer', 'amapress_contrat_footer_shortcode' );
	amapress_register_shortcode( 'next_events', 'amapress_next_events_shortcode' );
	amapress_register_shortcode( 'events_calendar', function () {
		return 'COMING SOON';
	} );

	if ( amapress_is_user_logged_in() ) {
		amapress_register_shortcode( 'intermittent-desinscription-href', 'amapress_intermittence_desinscription_link' );
	}

	amapress_register_shortcode( 'next-distrib-href', 'amapress_next_distrib_shortcode' );
	amapress_register_shortcode( 'next-distrib-link', 'amapress_next_distrib_shortcode' );
	amapress_register_shortcode( 'next-distrib-date', 'amapress_next_distrib_shortcode' );
	amapress_register_shortcode( 'next-emargement-href', 'amapress_next_distrib_shortcode' );
	amapress_register_shortcode( 'next-emargement-link', 'amapress_next_distrib_shortcode' );
	amapress_register_shortcode( 'amapress-redirect-next-distrib', 'amapress_next_distrib_shortcode' );
	amapress_register_shortcode( 'amapress-redirect-next-emargement', 'amapress_next_distrib_shortcode' );

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
	} );

	amapress_register_shortcode( 'nous-contacter', function ( $atts ) {
		return Amapress::getOption( 'contrat_info_anonymous' );
	} );

	amapress_register_shortcode( 'agenda-url', function ( $atts ) {
		$id  = 'agenda-url-' . md5( uniqid() );
		$url = esc_attr( Amapress_Agenda_ICAL_Export::get_link_href() );

		return "<div class='input-group'><input id='$id' type='text' value='$url' class='form-control' /><span class='input-group-btn'><button class='btn btn-secondary copy-agenda-url' type='button' data-clipboard-target='#{$id}'><span class='glyphicon glyphicon-copy' /></button></span><script type='text/javascript'>jQuery(function() { new Clipboard('.copy-agenda-url'); });</script></div>";
	} );

	amapress_register_shortcode( 'front_next_events', function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$max            = amapress_is_user_logged_in() ? Amapress::getOption( 'agenda_max_dates' ) : Amapress::getOption( 'agenda_max_public_dates' );
		$agenda_content = do_shortcode( '[next_events max=' . $max . ']' );
		if ( ! Amapress::toBool( $atts['title'] ) ) {
			return $agenda_content;
		}
//    $agenda = '';
//    if (trim(wp_strip_all_tags($agenda_content, true)) != '') {
//    (amapress_is_user_logged_in() ? ' (<a href="' . Amapress_Agenda_ICAL_Export::get_link_href() . '"><i class="fa fa-calendar" aria-hidden="true"></i> iCal</a>)' : '') . '</h2>' .
		$agenda = '<h3 id="front-adgenda-title">' . ( amapress_is_user_logged_in() ? Amapress::getOption( 'front_agenda_title' ) : Amapress::getOption( 'front_agenda_public_title' ) ) . '</h3>' .
		          $agenda_content;

//    }
		return $agenda;
	} );
	amapress_register_shortcode( 'front_produits', function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$produits_content = Amapress::get_contrats_list();
		$produits         = '';
		if ( Amapress::toBool( $atts['title'] ) ) {
			$produits = '<h3 id="front-produits-title">' . Amapress::getOption( 'front_produits_title' ) . '</h3>';
		}
		if ( trim( wp_strip_all_tags( $produits_content, true ) ) != '' ) {
			$interm = '';
//        if (Amapress::isIntermittenceEnabled() && Amapress::userCanRegister()) {
//            $interm = amapress_get_button('Devenir intermittent', Amapress::getMesInfosSublink('adhesions/intermittence/inscription'));
//        }
			if ( Amapress::isIntermittenceEnabled() ) {
				$interm = do_shortcode( '[intermittents-inscription view=me show_info=no]' );
			}

			$produits .= $produits_content . $interm;
		}

		return $produits;
	} );
	amapress_register_shortcode( 'front_nous_trouver', function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$map_content = do_shortcode( '[nous-trouver]' );
		if ( ! Amapress::toBool( $atts['title'] ) ) {
			return $map_content;
		}
		$map = '<h3 id="front-map-title">' . Amapress::getOption( 'front_map_title' ) . '</h3>' . $map_content;

		return $map;
	} );
	amapress_register_shortcode( 'front_default_grid', function ( $atts ) {
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
	} );
}

