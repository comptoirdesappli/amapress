<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_get_custom_content_amap_event', 'amapress_get_custom_content_amap_event' );
function amapress_get_custom_content_amap_event( $content ) {
	$amap_event = new AmapressAmap_event( get_the_ID() );

	ob_start();

	amapress_echo_panel_start( 'Horaires', null, 'amap-panel-event amap-panel-event-hours' );
	echo '<p>' .
	     ' Le ' . date_i18n( 'l d F Y', $amap_event->getDate() ) .
	     ' de ' . date_i18n( 'H:i', $amap_event->getStartDateAndHour() ) .
	     ' à ' . date_i18n( 'H:i', $amap_event->getEndDateAndHour() ) .
	     '</p>';
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Description', null, 'amap-panel-event amap-panel-event-desc' );
	echo $content;
	amapress_echo_panel_end();

//    amapress_handle_action_messages();

	if ( $amap_event->getType() == 'lieu' ) {
		$addr_entry    = '';
		$address_acces = $amap_event->getLieu()->getAdresseAcces();
		if ( ! empty( $address_acces ) ) {
			$addr_entry = '<h3>Adresse d\'accès</h3><p>' .
			              $address_acces .
			              '</p>';
		}

		amapress_echo_panel_start( 'Adresse', null, 'amap-panel-event amap-panel-event-address' );
		echo '<p>' .
		     $amap_event->getLieu()->getFormattedAdresseHtml() .
		     '</p>';
		amapress_echo_panel_end();

		amapress_echo_panel_start( 'Accès', null, 'amap-panel-event amap-panel-event-access' );
		echo $addr_entry .
		     '<p>' .
		     $amap_event->getLieu()->getAcces() .
		     '</p>' .
		     do_shortcode( "[lieu-map lieu={$amap_event->getLieuId()} mode=map+streeview]" );
		amapress_echo_panel_end();
	} else {
		$addr_entry    = '';
		$address_acces = $amap_event->getLieu_externe_adresse();
		if ( ! empty( $address_acces ) ) {
			$addr_entry = '<h3>Adresse d\'accès</h3><p>' .
			              $address_acces .
			              '</p>';
		}

		amapress_echo_panel_start( 'Adresse', null, 'amap-panel-event amap-panel-event-address' );
		echo '<p>' .
		     $amap_event->getLieu_externe_nom() .
		     '</p>';
		echo '<p>' .
		     $amap_event->getLieu_externe_adresse() .
		     '</p>';
		amapress_echo_panel_end();

		amapress_echo_panel_start( 'Accès', null, 'amap-panel-event amap-panel-event-access' );
		echo $addr_entry .
		     '<p>' .
		     $amap_event->getLieu_externe_acces() .
		     '</p>';

		$access = null;
		if ( $amap_event->isLieu_externe_AdresseAccesLocalized() ) {
			$access = array(
				'longitude' => $amap_event->getLieu_externe_AdresseAccesLongitude(),
				'latitude'  => $amap_event->getLieu_externe_AdresseAccesLatitude(),
			);
		}

		$markers = array();
		if ( $amap_event->isLieu_externe_AdresseLocalized() ) {
			$markers[] = array(
				'longitude' => $amap_event->getLieu_externe_AdresseLongitude(),
				'latitude'  => $amap_event->getLieu_externe_AdresseLatitude(),
				'url'       => $amap_event->getPermalink(),
				'title'     => $amap_event->getLieu_externe_nom(),
				'access'    => $access,
			);
		}

		echo amapress_generate_map( $markers, 'map' );

		amapress_echo_panel_end();
	}

	if ( amapress_is_user_logged_in() ) {

		$responsables = array_map( function ( $u ) {
			return $u->getUser();
		}, $amap_event->getParticipants() );

		amapress_display_messages_for_post( 'amap-event-messages', $amap_event->ID );

		amapress_echo_panel_start( 'Participants', null, 'amap-panel-event amap-panel-event-amapiens' );
		if ( count( $responsables ) > 0 ) {
			echo amapress_generic_gallery( $responsables, 'resp', 'user_cell', 'Pas de participants' );
		} else { ?>
            <p>Aucun participants</p>
		<?php }

		amapress_echo_button( 'Participer', amapress_action_link( $amap_event->ID, 'participer' ), 'fa-fa', false, "Confirmez-vous votre participation ?" );

		amapress_echo_panel_end();
	}

	$content = ob_get_contents();

	ob_clean();

	return $content;
}