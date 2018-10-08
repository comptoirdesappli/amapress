<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_get_custom_content_visite', 'amapress_get_custom_content_visite' );
function amapress_get_custom_content_visite( $content ) {
	$visite = new AmapressVisite( get_the_ID() );

	ob_start();

	amapress_echo_button( 'Participer', amapress_action_link( $visite->ID, 'participer' ), 'fa-fa', false, "Confirmez-vous votre participation ?" );

	amapress_echo_panel_start( 'Au programme', null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-programme' );
	echo '<p class="visite-au-programme">' .
	     $visite->getAu_programme() .
	     '</p>';
	amapress_echo_panel_end();

	//amapress_handle_action_messages();

	amapress_echo_panel_start( 'Adresse', null, 'amap-panel-visite amap-panel-visite-address' );
	echo '<p class="visite-nom-exploitation">' .
	     '<a href="' . $visite->getProducteur()->getPermalink() . '">' . $visite->getProducteur()->getNomExploitation() . '</a>' .
	     '</p>';
	echo '<p class="visite-adresse-exploitation">' .
	     $visite->getProducteur()->getFormattedAdresseExploitationHtml() .
	     '</p>';
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Accès', null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-access' );
	echo '<p>' .
	     $visite->getProducteur()->getAcces() .
	     '</p>' .
	     do_shortcode( "[producteur-map producteur={$visite->getProducteur()->ID} mode=map+streeview]" );
	amapress_echo_panel_end();

	amapress_display_messages_for_post( 'visite-messages', $visite->ID );

	amapress_echo_panel_start( 'Horaires', null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-hours' );
	echo '<p>' .
	     ' de ' . date_i18n( 'H:i', $visite->getStartDateAndHour() ) .
	     ' à ' . date_i18n( 'H:i', $visite->getEndDateAndHour() ) .
	     '</p>';
	amapress_echo_panel_end();

	$responsables = array_map( function ( $u ) {
		/** @var AmapressUser $u */
		return $u->getUser();
	}, $visite->getParticipants() );

	amapress_echo_panel_start( 'Participants', null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-amapiens' );
	if ( count( $responsables ) > 0 ) {
		echo amapress_generic_gallery( $responsables, 'user_cell', [
			'if_empty' => 'Pas de participants'
		] );
	} else { ?>
        <p>Aucun participants</p>
	<?php }

	amapress_echo_panel_end();

	$content = ob_get_clean();

	return $content;
}