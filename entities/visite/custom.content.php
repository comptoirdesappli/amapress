<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_get_custom_content_visite', 'amapress_get_custom_content_visite' );
function amapress_get_custom_content_visite( $content ) {
	$visite = new AmapressVisite( get_the_ID() );

	ob_start();

//	amapress_echo_button( __('Participer', 'amapress'), amapress_action_link( $visite->ID, 'participer' ), 'fa-fa', false, "Confirmez-vous votre participation ?" );
	$inscription   = '';
	$inscr_another = '';

	if ( amapress_is_user_logged_in() ) {

		$user_id               = amapress_current_user_id();
		$can_subscribe         = $visite->canSubscribe();
		$can_unsubscribe       = $visite->canUnsubscribe();
		$is_resp               = in_array( $user_id, $visite->getParticipantIds() );
		$slot_for_current_user = $visite->getSlotInfoForUser( $user_id );

		if ( ( AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) && $can_subscribe ) {
			$users = [ '' => '--Sélectionner un amapien--' ];
			amapress_precache_all_users();
			foreach ( get_users() as $user ) {
				$users[ $user->ID ] = sprintf( __( '%s (%s)', 'amapress' ), $user->display_name, $user->user_email );
			}
			$inscr_another = '<form class="inscription-distrib-other-user">
<select name="user" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="btn btn-default visite-inscrire-button" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir inscrire cet amapien ?', 'amapress' ) . '" data-visite="' . $visite->ID . '">' . __( 'Inscrire', 'amapress' ) . '</button>
</form>';
		}
		if ( ! $is_resp ) {
			if ( $can_subscribe ) {
				if ( empty( $visite->getSlotsConf() ) ) {
					$inscription .= '<button type="button" class="btn btn-default visite-inscrire-button" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir vous inscrire ?', 'amapress' ) . '" data-visite="' . $visite->ID . '">' . __( 'M\'inscrire', 'amapress' ) . '</button>';
				}
			} else {
				$inscription .= '<span class="visite-inscr-closed">' . __( 'Inscriptions closes', 'amapress' ) . '</span>';
			}
		} else {
			if ( $slot_for_current_user ) {
				$inscription .= '<span>' . __( 'Vous êtes inscrit pour : ', 'amapress' ) . $slot_for_current_user['display'] . '</span>';
			} else if ( $can_unsubscribe ) {
				$inscription .= '<button type="button" class="btn btn-default visite-desinscrire-button" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir vous désinscrire ?', 'amapress' ) . '" data-visite="' . $visite->ID . '">' . __( 'Me désinscrire', 'amapress' ) . '</button>';
			}
		}
	}

	amapress_echo_panel_start( __( 'Statut', 'amapress' ), null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-status' );
	echo $visite->getStatusDisplay();
	amapress_echo_panel_end();

	if ( amapress_is_user_logged_in() ) {
		amapress_echo_panel_start( __( 'Inscription complète et partielle', 'amapress' ), null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-inscription' );
		echo $inscription;
		echo amapress_get_event_slot_html( $visite, 'visite', $user_id, $can_unsubscribe, $can_subscribe );
		echo $inscr_another;
		amapress_echo_panel_end();
	}

	amapress_echo_panel_start( __( 'Au programme', 'amapress' ), null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-programme' );
	echo '<p class="visite-au-programme">' .
	     $visite->getAu_programme() .
	     '</p>';
	amapress_echo_panel_end();

	amapress_echo_panel_start( __( 'Adresse', 'amapress' ), null, 'amap-panel-visite amap-panel-visite-address' );
	if ( ! empty( $visite->getLieu_externe_nom() ) ) {
		echo '<p class="visite-nom">' .
		     wpautop( $visite->getLieu_externe_nom() ) .
		     '</p>';
	} else {
		echo '<p class="visite-nom-exploitation">' .
		     '<a href="' . $visite->getProducteur()->getPermalink() . '">' . $visite->getProducteur()->getNomExploitation() . '</a>' .
		     '</p>';
	}
	if ( ! empty( $visite->getLieu_externe_adresse() ) ) {
		echo '<p class="visite-adresse">' .
		     wpautop( $visite->getLieu_externe_adresse() ) .
		     '</p>';
	} else {
		echo '<p class="visite-adresse-exploitation">' .
		     $visite->getProducteur()->getFormattedAdresseExploitationHtml() .
		     '</p>';
	}

	amapress_echo_panel_end();

	amapress_echo_panel_start( __( 'Accès', 'amapress' ), null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-access' );
	if ( ! empty( $visite->getLieu_externe_acces() ) ) {
		echo wpautop( $visite->getLieu_externe_acces() );

		$markers = array();
		if ( $visite->isLieu_externe_AdresseLocalized() ) {
			$markers[] = array(
				'longitude' => $visite->getLieu_externe_AdresseLongitude(),
				'latitude'  => $visite->getLieu_externe_AdresseLatitude(),
				'url'       => $visite->getPermalink(),
				'title'     => $visite->getLieu_externe_nom(),
				'access'    => array(
					'longitude' => $visite->getLieu_externe_AdresseLongitude(),
					'latitude'  => $visite->getLieu_externe_AdresseLatitude(),
				)
			);
		}

		echo amapress_generate_map( $markers, 'map+streeview' );
	} else {
		echo '<p>' .
		     $visite->getProducteur()->getAcces() .
		     '</p>' .
		     do_shortcode( "[producteur-map producteur={$visite->getProducteur()->ID} mode=map+streeview]" );
	}
	amapress_echo_panel_end();

	amapress_display_messages_for_post( 'visite-messages', $visite->ID );

	amapress_echo_panel_start( __( 'Horaires', 'amapress' ), null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-hours' );
	echo sprintf( __( '<p>de %s à %s</p>', 'amapress' ), date_i18n( 'H:i', $visite->getStartDateAndHour() ), date_i18n( 'H:i', $visite->getEndDateAndHour() ) );
	amapress_echo_panel_end();

	if ( amapress_is_user_logged_in() ) {

		$responsables = array_map( function ( $u ) {
			/** @var AmapressUser $u */
			return $u->getUser();
		}, $visite->getParticipants() );

		amapress_echo_panel_start( __( 'Participants', 'amapress' ), null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-amapiens' );
		if ( count( $responsables ) > 0 ) {
			echo amapress_generic_gallery( $responsables, 'user_cell', [
				'if_empty' => __( 'Pas de participants', 'amapress' )
			] );
		} else { ?>
            <p><?php _e( 'Aucun participants', 'amapress' ) ?></p>
		<?php }

		echo $inscription;
		echo $inscr_another;

		if ( ! empty( $visite->getSlotsConf() ) ) {
			echo '<h5>' . __( 'Table des inscrits', 'amapress' ) . '</h5>';
			echo $visite->getInscritsTable( true, amapress_can_access_admin() );
			echo '<h5>' . __( 'Table des horaires', 'amapress' ) . '</h5>';
			echo $visite->getSlotsTable();
		}

		amapress_echo_panel_end();
	}

	$content = ob_get_clean();

	return $content;
}