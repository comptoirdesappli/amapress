<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//add_action( 'amapress_do_query_action_panier_echanger', 'amapress_do_query_action_panier_echanger' );
//function amapress_do_query_action_panier_echanger() {
//	$res = amapress_echanger_panier( get_the_ID() );
//	switch ( $res ) {
//		case 'already':
//			$optionsPage = Amapress::resolve_post_id( Amapress::getOption( 'mes-infos-page' ), 'page' );
//			$base_url    = trailingslashit( get_page_link( $optionsPage ) );
//			wp_redirect_and_exit( add_query_arg( 'message', 'panier_echange_already_done', $base_url . 'echanges' ) );
//			break;
//		case true:
//			$optionsPage = Amapress::resolve_post_id( Amapress::getOption( 'mes-infos-page' ), 'page' );
//			$base_url    = trailingslashit( get_page_link( $optionsPage ) );
//			wp_redirect_and_exit( add_query_arg( 'message', 'panier_echange_saved', $base_url . 'echanges' ) );
//			break;
//		default:
//			wp_die( $res );
//	}
//}

add_action( 'wp_ajax_echanger_panier', function () {
	$dist_id = intval( $_POST['dist'] );
	$user_id = isset( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	if ( $user_id != amapress_current_user_id() ) {
		if ( ! amapress_can_access_admin() ) {
			echo '<p class="error">' . 'Accès interdit' . '</p>';
			die();
		}
	}
	$dist = AmapressDistribution::getBy( $dist_id );

	Amapress::setFilterForReferent( false );
	$contrat_ids = array_map( function ( $c ) {
		/** @var AmapressAdhesion $c */
		return $c->getContrat_instanceId();
	}, AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, $dist->getDate() ) );
//	$cnt         = 0;
	$panier_ids = [];
//	$failed      = 0;
	$paniers = AmapressPaniers::getPaniersForDist( $dist->getDate() );
	foreach ( $paniers as $panier ) {
		if ( ! in_array( $panier->getContrat_instanceId(), $contrat_ids ) ) {
			continue;
		}
		if ( $panier->isDelayed() && Amapress::start_of_day( $panier->getRealDate() ) != Amapress::start_of_day( $dist->getDate() ) ) {
			continue;
		}
		$panier_ids[] = $panier->ID;
	}
	Amapress::setFilterForReferent( true );

	if ( Amapress::getOption( 'allow_partial_exchange' ) && ! empty( $_POST['panier'] ) ) {
		$panier_id = intval( $_POST['panier'] );
		if ( in_array( $panier_id, $panier_ids ) ) {
			$panier_ids = [ $panier_id ];
		} else {
			$panier_ids = [];
		}
	}

	$cnt = count( $panier_ids );

	$target_id = isset( $_REQUEST['target'] ) ? intval( $_REQUEST['target'] ) : null;
	if ( $cnt > 0 ) {
		$res = amapress_echanger_panier(
			$dist, $panier_ids, $user_id,
			isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '',
			$target_id );
		if ( $res != 'ok' ) {
			$msg = 'Erreur lors de l\'échange du panier';
			switch ( $res ) {
				case 'too_late':
					$msg = 'Les cessions de paniers sont closes';
					break;
				case 'already':
					$msg = 'Déjà cédés';
					break;
			}
			echo '<p class="error">' . esc_html( $msg ) . '</p>';
			die();
		}
	}

	if ( $user_id == amapress_current_user_id() ) {
		if ( $cnt == 0 ) {
			echo '<p class="error">' . 'Vous n\'avez pas de panier à cette distribution' . '</p>';
		} else if ( $cnt > 1 ) {
			echo '<p class="success">' . 'Vos paniers ont été inscrits sur la liste des paniers à échanger' . '</p>';
		} else {
			echo '<p class="success">' . 'Votre panier a été inscrit sur la liste des paniers à échanger' . '</p>';
		}
	} else {
		$user = AmapressUser::getBy( $user_id );
		if ( $cnt == 0 ) {
			echo '<p class="error">';
			echo sprintf( __( '%s n\'a pas de panier à cette distribution', 'amapress' ), $user->getDisplayName() );
			echo '</p>';
		} else if ( $cnt > 1 ) {
			if ( $target_id ) {
				$target = AmapressUser::getBy( $target_id );
				echo '<p class="success">';
				echo sprintf( 'Les paniers de %s ont été attribués à %s', esc_html( $user->getDisplayName() ), esc_html( $target->getDisplayName() ) );
				echo '</p>';
			} else {
				echo '<p class="success">';
				echo sprintf( 'Les paniers de %s ont été inscrits sur la liste des paniers à échanger', esc_html( $user->getDisplayName() ) );
				echo '</p>';
			}
		} else {
			if ( $target_id ) {
				$target = AmapressUser::getBy( $target_id );
				echo '<p class="success">';
				echo sprintf( 'Le panier de %s a été attribué à %s', esc_html( $user->getDisplayName() ), esc_html( $target->getDisplayName() ) );
				echo '</p>';
			} else {
				echo '<p class="success">';
				echo sprintf( 'Le panier de %s a été inscrit sur la liste des paniers à échanger', esc_html( $user->getDisplayName() ) );
				echo '</p>';
			}
		}
	}

	die();
} );

function amapress_echanger_panier( AmapressDistribution $dist, $panier_ids, $user_id = null, $message = null, $target_user_id = null ) {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	if ( empty( $panier_ids ) || empty( $dist ) ) {
		wp_die( 'Pas de panier' );
	}

	if ( ! is_array( $panier_ids ) ) {
		$panier_ids = [ $panier_ids ];
	}


	if ( ! $user_id ) {
		$user_id = amapress_current_user_id();
	}

	$panier_date          = 0;
	$lieu_id              = 0;
	$contrat_instance_ids = [];
	foreach ( $panier_ids as $panier_id ) {
		$panier = AmapressPanier::getBy( $panier_id );

		$can_subscribe = $dist->canCease();
		if ( ! $can_subscribe ) {
			return 'too_late';
		}

		$panier_date = $panier->getRealDate();
//    $redir_url = $panier->getPermalink();
		$contrat_instance       = $panier->getContrat_instance();
		$contrat_instance_ids[] = $contrat_instance->ID;
		$adhesions              = array_values( $contrat_instance->getAdhesionsForUser( $user_id ) );
		if ( empty( $adhesions ) or ( count( $adhesions ) == 0 ) ) {
			wp_die( 'Vous ne faites pas partie de cette distribution.' );
		}

		/** @var AmapressAdhesion $adhesion */
		$adhesion = array_shift( $adhesions );
		$lieu_id  = $adhesion->getLieuId();

		if ( AmapressPaniers::isIntermittent( $panier->ID, $lieu_id ) ) {
			return 'already';
		}
	}

	$existing_paniers = AmapressPaniers::getPanierIntermittents(
		[
			'date'     => $panier_date,
			'adherent' => $user_id,
		]
	);
	foreach ( $existing_paniers as $p ) {
		if ( $p->getStatus() != AmapressIntermittence_panier::CANCELLED
		     && count( array_intersect( $p->getPanierIds(), $panier_ids ) ) > 0 ) {
			return 'already';
		}
	}

	$my_post_meta = array(
		'amapress_intermittence_panier_date'             => $panier_date,
		'amapress_intermittence_panier_panier'           => $panier_ids,
		'amapress_intermittence_panier_contrat_instance' => $contrat_instance_ids,
		'amapress_intermittence_panier_adherent'         => $user_id,
		'amapress_intermittence_panier_lieu'             => $lieu_id,
		'amapress_intermittence_panier_status'           => 'to_exchange',
		'amapress_intermittence_panier_adh_message'      => $message,
	);
	if ( $target_user_id ) {
		$my_post_meta['amapress_intermittence_panier_status']    = 'exchanged';
		$my_post_meta['amapress_intermittence_panier_repreneur'] = $target_user_id;
	}
	$my_post = array(
		'post_type'    => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
		'post_content' => '',
		'post_status'  => 'publish',
		'meta_input'   => $my_post_meta,
	);
	$new_id  = wp_insert_post( $my_post );

	if ( $target_user_id ) {
		amapress_send_panier_intermittent_affected( $new_id );
	} else {
		amapress_send_panier_intermittent_available( $new_id );
	}

	return 'ok';
}

function amapress_send_panier_intermittent_affected( $intermittence_panier_id ) {
	if ( is_a( $intermittence_panier_id, 'WP_Post' ) ) {
		$intermittence_panier_id = $intermittence_panier_id->ID;
	}
	$inter = AmapressIntermittence_panier::getBy( $intermittence_panier_id );

	$responsable      = AmapressUser::getBy( amapress_current_user_id() );
	$responsable_html = sprintf( '%s (%s)',
		Amapress::makeLink( 'mailto:' . $responsable->getEmail(), $responsable->getDisplayName() ),
		$responsable->getContacts() );

	$content = Amapress::getOption( 'intermittence-panier-admin-adh-mail-content' );
	$content = str_replace( '%%responsable%%', $responsable_html, $content );
	amapress_mail_to_current_user(
		Amapress::getOption( 'intermittence-panier-admin-adh-mail-subject' ),
		$content,
		$inter->getAdherent()->ID,
		$inter, [], null, null,
		AmapressIntermittence_panier::getResponsableIntermittentsReplyto( $inter->getLieuId() ) );

	$content = Amapress::getOption( 'intermittence-panier-admin-rep-mail-content' );
	$content = str_replace( '%%responsable%%', $responsable_html, $content );
	amapress_mail_to_current_user(
		Amapress::getOption( 'intermittence-panier-admin-rep-mail-subject' ),
		$content,
		$inter->getRepreneur()->ID,
		$inter, [], null, null,
		AmapressIntermittence_panier::getResponsableIntermittentsReplyto( $inter->getLieuId() ) );
}

function amapress_send_panier_intermittent_available( $intermittence_panier_id ) {
	if ( is_a( $intermittence_panier_id, 'WP_Post' ) ) {
		$intermittence_panier_id = $intermittence_panier_id->ID;
	}
	$inter    = AmapressIntermittence_panier::getBy( $intermittence_panier_id );
	$intermit = amapress_prepare_message_target_bcc( "user:amapress_contrat=intermittent", "Les intermittents", "intermittent" );
	amapress_send_message(
		Amapress::getOption( 'intermittence-panier-dispo-mail-subject' ),
		Amapress::getOption( 'intermittence-panier-dispo-mail-content' ),
		'', $intermit, $inter, [], null, null,
		AmapressIntermittence_panier::getResponsableIntermittentsReplyto( $inter->getLieuId() ) );

	amapress_mail_to_current_user(
		Amapress::getOption( 'intermittence-panier-on-list-mail-subject' ),
		Amapress::getOption( 'intermittence-panier-on-list-mail-content' ),
		$inter->getAdherent()->ID,
		$inter, [], null, null,
		AmapressIntermittence_panier::getResponsableIntermittentsReplyto( $inter->getLieuId() ) );
}
