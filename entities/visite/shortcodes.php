<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_inscription_visite_shortcode( $atts ) {
	$atts = shortcode_atts( array(
//        'show_past' => false,
		'show_email'      => 'default',
		'show_tel'        => 'default',
		'show_tel_fixe'   => 'default',
		'show_tel_mobile' => 'default',
		'show_adresse'    => 'false',
		'show_roles'      => 'false',
		'show_avatar'     => 'default',
		'allow_slots'     => 'true',
	), $atts );

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$visites = AmapressVisite::get_next_visites();

	$has_slots = false;
	foreach ( $visites as $visite ) {
		if ( ! empty( $visite->getSlotsConf() ) ) {
			$has_slots = true;
			break;
		}
	}
	if ( ! Amapress::toBool( $atts['allow_slots'] ) ) {
		$has_slots = false;
	}

	$user_id = amapress_current_user_id();

	$ret = '';
	$ret .= '<div class="table-responsive">';
	$ret .= '<table class="table display responsive nowrap visite-inscr-list">';
	$ret .= '<thead>';
	$ret .= '<tr>';
	$ret .= '<th>Date</th>';
	if ( $has_slots ) {
		$ret .= '<th>Créneaux</th>';
	}
	$ret .= '<th>Participants</th>';
	$ret .= '</tr>';
	$ret .= '</thead>';

	$ret .= '<tbody>';
	foreach ( $visites as $event ) {
		$date = $event->getDate();
		$ret  .= '<tr>';
		$ret  .= '<th scope="row" class="inscr-list-info"><p class="inscr-list-date">' . esc_html( date_i18n( 'D j M Y', $event->getDate() ) ) . '</p><p class="inscr-list-title">' . esc_html( sprintf( '%s (%s)', $event->getTitle(), $event->getProducteur()->getTitle() ) ) . '</p></th>';


		$resps           = $event->getParticipants();
		$can_unsubscribe = Amapress::start_of_day( $date ) > Amapress::start_of_day( amapress_time() ); //TODO
		$can_subscribe   = Amapress::start_of_day( $date ) > Amapress::start_of_day( amapress_time() );

		if ( $has_slots ) {
			$ret .= "<td>";
			$ret .= amapress_get_event_slot_html( $event, 'visite', $user_id, $can_unsubscribe, $can_subscribe );
			$ret .= "</td>";
		}

		$inscr_another = '';
		if ( ( AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) && $can_subscribe ) {
			$users = [ '' => '--Sélectionner un amapien--' ];
			amapress_precache_all_users();
			foreach ( get_users() as $user ) {
				$users[ $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
			}
			$inscr_another = '<div><form class="inscription-distrib-other-user">
<select name="user" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="btn btn-default visite-inscrire-button" data-confirm="Etes-vous sûr de vouloir inscrire cet amapien ?" data-visite="' . $event->ID . '">Inscrire</button>
</form></div>';
		}

		$ret     .= '<td>';
		$ret     .= '<p>' . Amapress::makeLink( $event->getPermalink(), sprintf( '%d participant(s)', count( $resps ) ), true, true ) . '</p>';
		$is_resp = in_array( amapress_current_user_id(), $event->getParticipantIds() );
//		foreach ( $resps as $resp ) {
//			$is_resp       = $is_resp || $resp->ID == ;
//			$slot_for_user = $event->getSlotInfoForUser( $resp->ID );
//			if ( $resp->ID == amapress_current_user_id() && $can_unsubscribe ) {
//				if ( $slot_for_user ) {
//					$ret .= '<br/><strong>' . $slot_for_user['display'] . '</strong>';
//				} else {
//				}
//			} else if ( $slot_for_user ) {
//				$ret .= $slot_for_user['display'];
//			}
//		}
		if ( ! $has_slots ) {
			if ( ! $is_resp ) {
				if ( $can_subscribe ) {
					$ret .= '<button type="button" class="btn btn-default visite-inscrire-button" data-confirm="Etes-vous sûr de vouloir vous inscrire ?" data-visite="' . $event->ID . '">M\'inscrire</button>';
				} else {
					$ret .= '<span class="visite-inscr-closed">Inscriptions closes</span>';
				}
			} else {
				$ret .= '<br/><button type="button" class="btn btn-default visite-desinscrire-button" data-confirm="Etes-vous sûr de vouloir vous désinscrire ?" data-visite="' . $event->ID . '">Me désinscrire</button>';
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

/**
 * @param AmapressVisite $event
 * @param $user_id
 * @param $can_unsubscribe
 * @param $can_subscribe
 *
 * @return string
 */
function amapress_get_event_slot_html( $event, $type, $user_id, $can_unsubscribe, $can_subscribe ) {
	$ret                   = '';
	$slot_for_current_user = $event->getSlotInfoForUser( $user_id );
	if ( $slot_for_current_user ) {
		$ret .= esc_html( $slot_for_current_user['display'] ) . '<br/>';
		if ( $can_unsubscribe ) {
			$ret .= '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="' . $type . '_desinscrire_slot" data-confirm="Etes-vous sûr vous désinscrire de ce créneau ?"
					data-' . $type . '="' . $event->ID . '" data-slot="' . strval( $slot_for_current_user['date'] ) . '">Me désinscrire</button>';
		}
	} else {
		if ( $can_subscribe ) {
			$dist_slots_options = [];
			foreach ( $event->getAvailableSlots() as $k => $conf ) {
				if ( $conf['max'] <= 0 ) {
					$dist_slots_options[ $k ] = $conf['display'];
				} else {
					$dist_slots_options[ $k ] = sprintf( '%s (%d/%d)', $conf['display'], intval( $conf['current'] ), intval( $conf['max'] ) );
				}
			}

			if ( ! empty( $dist_slots_options ) ) {
				$affect_slot_id = 'affect-slot-' . $event->ID;
				$affect_slot    = "<select id='$affect_slot_id'>";
				$affect_slot    .= tf_parse_select_options( $dist_slots_options, null, false );
				$affect_slot    .= '</select>';
				$affect_slot    .= '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="' . $type . '_inscrire_slot" data-confirm="Etes-vous sûr de vous inscrire à ce créneau ?"
					data-' . $type . '="' . $event->ID . '" data-slot="val:#' . $affect_slot_id . '">M\'inscrire</button>';
				$ret            .= $affect_slot;
			}
		}
	}

	return $ret;
}

//add_action('init', function () {
add_action( 'wp_ajax_desinscrire_visite_action', function () {
	$event_id   = intval( $_POST['visite'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$event = new AmapressVisite( $event_id );
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
add_action( 'wp_ajax_inscrire_visite_action', function () {
	$event_id   = intval( $_POST['visite'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsableThisWeek() || amapress_can_access_admin() ) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$event = new AmapressVisite( $event_id );
	switch ( $event->inscrireParticipant( $user_id ) ) {
		case 'already_in_list':
			echo '<p class="error">Déjà inscrit</p>';
			break;
		case 'ok':
			echo '<p class="success">Inscription a bien été prise en compte</p>';
			break;
	}
	die();
} );
//});