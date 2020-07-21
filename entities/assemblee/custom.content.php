<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_get_custom_content_assemblee_generale', 'amapress_get_custom_content_assemblee_generale' );
function amapress_get_custom_content_assemblee_generale( $content ) {
	$assemblee_generale = AmapressAssemblee_generale::getBy( get_the_ID() );

	ob_start();

	$can_unsubscribe = $assemblee_generale->canUnsubscribe();
	$can_subscribe   = $assemblee_generale->canSubscribe();
	$is_resp         = in_array( amapress_current_user_id(), $assemblee_generale->getParticipantsIds() );

	$users = [ '' => '--Sélectionner un amapien--' ];
	amapress_precache_all_users();
	foreach ( get_users() as $user ) {
		$users[ $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
	}
	$inscr_another = '';
	if ( amapress_can_access_admin() && $can_subscribe ) {
		$inscr_another = '<form class="inscription-distrib-other-user">
<select name="user" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="btn btn-default assemblee-inscrire-button" data-confirm="Etes-vous sûr de vouloir inscrire cet amapien ?" data-event="' . $assemblee_generale->ID . '">Inscrire</button>
</form>';
	}
	$inscription = '';
	if ( ! $is_resp ) {
		if ( $can_subscribe ) {
			$inscription .= '<button type="button" class="btn btn-default assemblee-inscrire-button" data-confirm="Etes-vous sûr de vouloir vous inscrire ?" data-event="' . $assemblee_generale->ID . '">M\'inscrire</button>';
		} else {
			$inscription .= '<span class="assemblee-inscr-closed">Inscriptions closes</span>';
		}
	} else if ( $can_unsubscribe ) {
		$inscription .= '<button type="button" class="btn btn-default assemblee-desinscrire-button" data-confirm="Etes-vous sûr de vouloir vous désinscrire ?" data-event="' . $assemblee_generale->ID . '">Me désinscrire</button>';
	}
	echo $inscription;

	amapress_echo_panel_start( 'Horaires' );
	echo '<p>' .
	     ' de ' . date_i18n( 'H:i', $assemblee_generale->getStartDateAndHour() ) .
	     ' à ' . date_i18n( 'H:i', $assemblee_generale->getEndDateAndHour() ) .
	     '</p>';
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Ordre du jour' );
	echo $assemblee_generale->getOrdre_du_jour();
	amapress_echo_panel_end();

//    amapress_handle_action_messages();

	if ( $assemblee_generale->getType() == 'lieu' ) {
		if ( $assemblee_generale->getLieu() ) {
			$addr_entry    = '';
			$address_acces = $assemblee_generale->getLieu()->getAdresseAcces();
			if ( ! empty( $address_acces ) ) {
				$addr_entry = '<h3>Adresse d\'accès</h3><p>' .
				              $address_acces .
				              '</p>';
			}

			amapress_echo_panel_start( 'Adresse', null, 'amap-panel-assemblee amap-panel-assemblee-address' );
			echo '<p>' .
			     $assemblee_generale->getLieu()->getFormattedAdresseHtml() .
			     '</p>';
			amapress_echo_panel_end();

			amapress_echo_panel_start( 'Accès', null, 'amap-panel-assemblee amap-panel-assemblee-access' );
			echo $addr_entry .
			     '<p>' .
			     $assemblee_generale->getLieu()->getAcces() .
			     '</p>' .
			     do_shortcode( "[lieu-map lieu={$assemblee_generale->getLieuId()} mode=map+streeview]" );
			amapress_echo_panel_end();
		} else {
			amapress_echo_panel_start( 'Adresse', null, 'amap-panel-assemblee amap-panel-assemblee-address' );
			echo '<p>Lieu non défini</p>';
			amapress_echo_panel_end();
		}
	} else {
		$addr_entry    = '';
		$address_acces = $assemblee_generale->getLieu_externe_adresse();
		if ( ! empty( $address_acces ) ) {
			$addr_entry = '<h3>Adresse d\'accès</h3><p>' .
			              $address_acces .
			              '</p>';
		}

		amapress_echo_panel_start( 'Adresse', null, 'amap-panel-assemblee amap-panel-assemblee-address' );
		echo '<p>' .
		     $assemblee_generale->getLieu_externe_nom() .
		     '</p>';
		echo '<p>' .
		     $assemblee_generale->getLieu_externe_adresse() .
		     '</p>';
		amapress_echo_panel_end();

		amapress_echo_panel_start( 'Accès', null, 'amap-panel-assemblee amap-panel-assemblee-access' );
		echo $addr_entry .
		     '<p>' .
		     $assemblee_generale->getLieu_externe_acces() .
		     '</p>';

		$access = null;
		if ( $assemblee_generale->isLieu_externe_AdresseAccesLocalized() ) {
			$access = array(
				'longitude' => $assemblee_generale->getLieu_externe_AdresseAccesLongitude(),
				'latitude'  => $assemblee_generale->getLieu_externe_AdresseAccesLatitude(),
			);
		}

		$markers = array();
		if ( $assemblee_generale->isLieu_externe_AdresseLocalized() ) {
			$markers[] = array(
				'longitude' => $assemblee_generale->getLieu_externe_AdresseLongitude(),
				'latitude'  => $assemblee_generale->getLieu_externe_AdresseLatitude(),
				'url'       => $assemblee_generale->getPermalink(),
				'title'     => $assemblee_generale->getLieu_externe_nom(),
				'access'    => $access,
			);
		}

		echo amapress_generate_map( $markers, 'map' );

		amapress_echo_panel_end();
	}

	if ( amapress_is_user_logged_in() ) {

		$responsables = array_map( function ( $u ) {
			return $u->getUser();
		}, $assemblee_generale->getParticipants() );

		amapress_echo_panel_start( 'Participants', null, 'amap-panel-assemblee amap-panel-assemblee-amapiens' );
		if ( count( $responsables ) > 0 ) {
			echo amapress_generic_gallery( $responsables, 'user_cell', [
				'if_empty' => 'Pas de participant'
			] );
		} else { ?>
            <p>Aucun participants</p>
		<?php }

		echo $inscr_another;

		amapress_echo_panel_end();
	}

	$content = ob_get_contents();

	ob_clean();

	return $content;
}