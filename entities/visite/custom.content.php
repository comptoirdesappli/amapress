<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_get_custom_content_visite', 'amapress_get_custom_content_visite' );
function amapress_get_custom_content_visite( $content ) {
	$visite = new AmapressVisite( get_the_ID() );

	ob_start();

//	amapress_echo_button( 'Participer', amapress_action_link( $visite->ID, 'participer' ), 'fa-fa', false, "Confirmez-vous votre participation ?" );

	$user_id               = amapress_current_user_id();
	$can_subscribe         = Amapress::start_of_day( $visite->getDate() ) > Amapress::start_of_day( amapress_time() );
	$can_unsubscribe       = Amapress::start_of_day( $visite->getDate() ) > Amapress::start_of_day( amapress_time() ); //TODO
	$is_resp               = in_array( $user_id, $visite->getParticipantIds() );
	$slot_for_current_user = $visite->getSlotInfoForUser( $user_id );

	$users = [ '' => '--Sélectionner un amapien--' ];
	amapress_precache_all_users();
	foreach ( get_users() as $user ) {
		$users[ $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
	}
	$inscr_another = '';
	if ( ( AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) && $can_subscribe ) {
		$inscr_another = '<form class="inscription-distrib-other-user">
<select name="user" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="btn btn-default visite-inscrire-button" data-confirm="Etes-vous sûr de vouloir inscrire cet amapien ?" data-visite="' . $visite->ID . '">Inscrire</button>
</form>';
	}
	$inscription = '';
	if ( ! $is_resp ) {
		if ( $can_subscribe ) {
			$inscription .= '<button type="button" class="btn btn-default visite-inscrire-button" data-confirm="Etes-vous sûr de vouloir vous inscrire ?" data-visite="' . $visite->ID . '">M\'inscrire</button>';
		} else {
			$inscription .= '<span class="visite-inscr-closed">Inscriptions closes</span>';
		}
	} else {
		if ( $slot_for_current_user ) {
			$inscription .= '<span>Vous êtes inscrit pour : ' . $slot_for_current_user['display'] . '</span>';
		} else if ( $can_unsubscribe ) {
			$inscription .= '<button type="button" class="btn btn-default visite-desinscrire-button" data-confirm="Etes-vous sûr de vouloir vous désinscrire ?" data-visite="' . $visite->ID . '">Me désinscrire</button>';
		}
	}

	if ( ! empty( $inscription ) ) {
		amapress_echo_panel_start( 'Inscription complète et partielle', null, 'amap-panel-visite amap-panel-visite-' . $visite->getProducteur()->ID . ' amap-panel-visite-inscription' );
		echo $inscription;
		echo '<hr/>';
		echo amapress_get_event_slot_html( $visite, 'visite', $user_id, $can_unsubscribe, $can_subscribe );
		amapress_echo_panel_end();
	}

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

	echo $inscr_another;

	amapress_echo_panel_end();

	$content = ob_get_clean();

	return $content;
}