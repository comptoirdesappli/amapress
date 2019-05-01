<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_get_custom_content_assemblee_generale', 'amapress_get_custom_content_assemblee_generale' );
function amapress_get_custom_content_assemblee_generale( $content ) {
	$assemblee_generale = new AmapressAssemblee_generale( get_the_ID() );

	ob_start();

	if ( in_array( amapress_current_user_id(), $assemblee_generale->getParticipantsIds() ) ) {
		amapress_echo_button( 'Participer', amapress_action_link( $assemblee_generale->ID, 'participer' ), 'fa-fa', false, "Confirmez-vous votre participation ?" );
	}

	amapress_echo_panel_start( 'Ordre du jour' );
	echo $assemblee_generale->getOrdre_du_jour();
	amapress_echo_panel_end();

//    amapress_handle_action_messages();

	$addr_entry    = '';
	$address_acces = $assemblee_generale->getLieu()->getAdresseAcces();
	if ( ! empty( $address_acces ) ) {
		$addr_entry = '<h3>Adresse d\'accès</h3><p>' .
		              $address_acces .
		              '</p>';
	}

	amapress_echo_panel_start( 'Adresse', null, 'amap-panel-event amap-panel-event-address' );
	echo '<p>' .
	     $assemblee_generale->getLieu()->getFormattedAdresseHtml() .
	     '</p>';
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Accès', null, 'amap-panel-event amap-panel-event-access' );
	echo $addr_entry .
	     '<p>' .
	     $assemblee_generale->getLieu()->getAcces() .
	     '</p>' .
	     do_shortcode( "[lieu-map lieu={$assemblee_generale->getLieu()->ID} mode=map+streeview]" );
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Horaires' );
	echo '<p>' .
	     ' de ' . date_i18n( 'H:i', $assemblee_generale->getStartDateAndHour() ) .
	     ' à ' . date_i18n( 'H:i', $assemblee_generale->getEndDateAndHour() ) .
	     '</p>';
	amapress_echo_panel_end();

	$responsables = array_map( function ( $u ) {
		return $u->getUser();
	}, $assemblee_generale->getParticipants() );

	amapress_display_messages_for_post( 'assemblee-messages', $assemblee_generale->ID );

	amapress_echo_panel_start( 'Participants' );
	if ( count( $responsables ) > 0 ) {
		echo amapress_generic_gallery( $responsables, 'user_cell', [
			'if_empty' => 'Pas de participant'
		] );
	} else { ?>
        <p>Aucun participants</p>
	<?php }

	amapress_echo_panel_end();

	$content = ob_get_contents();

	ob_clean();

	return $content;
}