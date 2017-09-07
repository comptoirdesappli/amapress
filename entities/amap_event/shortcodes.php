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
	$ret .= '<table class="table event-inscr-list">';
	$ret .= '<thead>';
	$ret .= '<tr>';
	$ret .= '<th>Date</th>';
	$ret .= '<th>Participants</th>';
	$ret .= '</tr>';
	$ret .= '</thead>';

	$ret        .= '<tbody>';
	$user_lieux = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
	foreach ( AmapressAmap_event::get_next_amap_events() as $event ) {
		if ( $event->getLieuId() > 0 && ! in_array( $event->getLieuId(), $user_lieux ) ) {
			continue;
		}
		$date = $event->getDate();
		$ret  .= '<tr>';
		$ret  .= '<th scope="row"><p class="inscr-list-date">' . esc_html( date_i18n( 'D j M Y', $event->getDate() ) ) . '</p><p class="inscr-list-title">' . esc_html( sprintf( '%s (%s)', $event->getTitle(), $event->getCategoriesDisplay() ) ) . '</p></th>';

		$resps           = $event->getParticipants();
		$can_unsubscribe = false; //TODO
		$can_subscribe   = Amapress::start_of_day( $date ) < Amapress::start_of_day( amapress_time() );

		$ret     .= '<td>';
		$is_resp = false;
		foreach ( $resps as $resp ) {
			$ret     .= '<div class="participant">';
			$is_resp = $is_resp || $resp->ID == amapress_current_user_id();
			$ret     .= $resp->getDisplay( $atts );
			if ( $resp->ID == amapress_current_user_id() && $can_unsubscribe ) {
				$ret .= '<button type="button" class="btn btn-default event-desinscrire-button" data-event="' . $event->ID . '">Me désinscrire</button>';
			}
			$ret .= '</div>';
		}
		if ( ! $is_resp ) {
			if ( $can_subscribe ) {
				$ret .= '<button type="button" class="btn btn-default event-inscrire-button" data-event="' . $event->ID . '">M\'inscrire</button>';
			} else {
				$ret .= '<span class="event-inscr-closed">Inscriptions closes</span>';
			}
		}
		$ret .= '</td>';
		$ret .= '</tr>';
	}
	$ret .= '</tbody>';
	$ret .= '</table>';
	$ret .= '</div>';

	return $ret;
}

//add_action('init', function () {
//    add_action('wp_ajax_desinscrire_amap_event_action', function() {
//        $dist_id = intval($_POST['event']);
//        $dist = new AmapressDistribution($dist_id);
//        switch ($dist->desinscrireResponsable(amapress_current_user_id())) {
//            case 'not_inscr':
//                echo '<p class="error">Vous n\'�tes pas inscrit</p>';
//                break;
//            case true:
//                echo '<p class="success">Votre d�sinscription a bien �t� prise en compte</p>';
//                break;
//        }
//        die();
//    });
add_action( 'wp_ajax_inscrire_amap_event_action', function () {
	$event_id = intval( $_POST['event'] );
	$event    = new AmapressAmap_event( $event_id );
	switch ( $event->inscrireParticipant( amapress_current_user_id() ) ) {
		case 'already_in_list':
			echo '<p class="error">Vous êtes déjà inscrit</p>';
			break;
		case 'list_full':
			echo '<p class="error">La distribution est déjà complête</p>';
			break;
		case 'ok':
			echo '<p class="success">Votre inscription a bien été prise en compte</p>';
			break;
	}
	die();
} );
//});