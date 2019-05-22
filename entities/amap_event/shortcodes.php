<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_inscription_amap_event_shortcode( $atts ) {
	$atts = shortcode_atts( array(
//        'show_past' => false,
		'show_email'      => 'default',
		'show_tel'        => 'default',
		'show_tel_fixe'   => 'default',
		'show_tel_mobile' => 'default',
		'show_adresse'    => 'default',
		'show_avatar'     => 'default',
	), $atts );

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$ret = '';
	$ret .= '<div class="table-responsive">';
	$ret .= '<table class="table display responsive nowrap event-inscr-list">';
	$ret .= '<thead>';
	$ret .= '<tr>';
	$ret .= '<th>Date</th>';
	$ret .= '<th>Participants</th>';
	$ret .= '</tr>';
	$ret .= '</thead>';

	$ret .= '<tbody>';
//	$user_lieux = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
	foreach ( AmapressAmap_event::get_next_amap_events() as $event ) {
//		if ( $event->getLieuId() > 0 && ! in_array( $event->getLieuId(), $user_lieux ) ) {
//			continue;
//		}
		$date = $event->getDate();
		$ret  .= '<tr>';
		$ret  .= '<th scope="row"><p class="inscr-list-date">' . esc_html( date_i18n( 'D j M Y', $event->getDate() ) ) . '</p><p class="inscr-list-title">' . esc_html( sprintf( '%s (%s)', $event->getTitle(), $event->getCategoriesDisplay() ) ) . '</p></th>';

		$resps           = $event->getParticipants();
		$can_unsubscribe = Amapress::start_of_day( $date ) > Amapress::start_of_day( amapress_time() ); //TODO
		$can_subscribe   = Amapress::start_of_day( $date ) > Amapress::start_of_day( amapress_time() );

		$users = [ '' => '--Sélectionner un amapien--' ];
		foreach ( get_users() as $user ) {
			$users[ $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
		}
		$inscr_another = '';
		if ( ( AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) && $can_subscribe ) {
			$inscr_another = '<form class="inscription-distrib-other-user">
<select name="user" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="btn btn-default event-inscrire-button" data-confirm="Etes-vous sûr de vouloir désinscrire cet amapien ?" data-event="' . $event->ID . '">Inscrire</button>
</form>';
		}

		$ret     .= '<td>';
		$is_resp = false;
		foreach ( $resps as $resp ) {
			$ret     .= '<div class="participant">';
			$is_resp = $is_resp || $resp->ID == amapress_current_user_id();
			$ret     .= $resp->getDisplay( $atts );
			if ( $resp->ID == amapress_current_user_id() && $can_unsubscribe ) {
				$ret .= '<button type="button" class="btn btn-default event-desinscrire-button" data-confirm="Etes-vous sûr de vouloir vous désinscrire ?" data-event="' . $event->ID . '">Me désinscrire</button>';
			}
			$ret .= '</div>';
		}
		if ( ! $is_resp ) {
			if ( $can_subscribe ) {
				$ret .= '<button type="button" class="btn btn-default event-inscrire-button" data-confirm="Etes-vous sûr de vouloir vous inscrire ?" data-event="' . $event->ID . '">M\'inscrire</button>';
			} else {
				$ret .= '<span class="event-inscr-closed">Inscriptions closes</span>';
			}
		}
		$ret .= $inscr_another;
		$ret .= '</td>';
		$ret .= '</tr>';
	}
	$ret .= '</tbody>';
	$ret .= '</table>';
	$ret .= '</div>';

	return $ret;
}

//add_action('init', function () {
add_action( 'wp_ajax_desinscrire_amap_event_action', function () {
	$event_id   = intval( $_POST['event'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$event = new AmapressAmap_event( $event_id );
	switch ( $event->desinscrireParticipant( $user_id ) ) {
		case 'not_inscr':
			echo '<p class="error">Non inscrit</p>';
			break;
		case true:
			echo '<p class="success">Désinscription a bien été prise en compte</p>';
			break;
	}
	die();
} );
add_action( 'wp_ajax_inscrire_amap_event_action', function () {
	$event_id   = intval( $_POST['event'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}
	$event = new AmapressAmap_event( $event_id );
	switch ( $event->inscrireParticipant( $user_id ) ) {
		case 'already_in_list':
			echo '<p class="error">Déjà inscrit</p>';
			break;
		case 'ok':
			echo '<p class="success">L\'inscription a bien été prise en compte</p>';
			break;
	}
	die();
} );
//});