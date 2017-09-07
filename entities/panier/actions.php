<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_do_query_action_panier_echanger', 'amapress_do_query_action_panier_echanger' );
function amapress_do_query_action_panier_echanger() {
	$res = amapress_echanger_panier( get_the_ID() );
	switch ( $res ) {
		case 'already':
			$optionsPage = Amapress::resolve_post_id( Amapress::getOption( 'mes-infos-page' ), 'page' );
			$base_url    = trailingslashit( get_page_link( $optionsPage ) );
			wp_redirect_and_exit( add_query_arg( 'message', 'panier_echange_already_done', $base_url . 'echanges' ) );
			break;
		case true:
			$optionsPage = Amapress::resolve_post_id( Amapress::getOption( 'mes-infos-page' ), 'page' );
			$base_url    = trailingslashit( get_page_link( $optionsPage ) );
			wp_redirect_and_exit( add_query_arg( 'message', 'panier_echange_saved', $base_url . 'echanges' ) );
			break;
		default:
			wp_die( $res );
	}
}

add_action( 'wp_ajax_echanger_panier', function () {
	$dist_id = intval( $_POST['dist'] );
	$user_id = isset( $_POST['user'] ) ? intval( $_POST['user'] ) : null;
	$dist    = new AmapressDistribution( $dist_id );

	$contrat_ids = array_map( function ( $c ) {
		/** @var AmapressAdhesion $c */
		return $c->getContrat_instance()->ID;
	}, AmapressContrats::get_user_active_adhesion( $user_id ) );
	$cnt         = 0;
	$failed      = 0;
	foreach ( $dist->getContrats() as $contrat ) {
		if ( ! in_array( $contrat->ID, $contrat_ids ) ) {
			continue;
		}
		$panier = AmapressPaniers::getPanierForDist( $dist->getDate(), $contrat->ID );
		if ( $panier == null ) {
			continue;
		}
		if ( amapress_echanger_panier( $panier->ID, $user_id, isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' ) == 'ok' ) {
			$cnt ++;
		} else {
			$failed ++;
		}
	}
	if ( $failed > 0 ) {
		echo '<p class="error">Erreur lors de l\'échange du panier</p>';
		die();
	}

	if ( $user_id == amapress_current_user_id() ) {
		if ( $cnt == 0 ) {
			echo '<p class="success">Vous n\'avez pas de panier à cette distribution</p>';
		} else if ( $cnt > 1 ) {
			echo '<p class="success">Vos paniers ont été inscrits sur la liste des paniers à échanger</p>';
		} else {
			echo '<p class="success">Votre panier a été inscrit sur la liste des paniers à échanger</p>';
		}
	} else {
		$user = AmapressUser::getBy( $user_id );
		if ( $cnt == 0 ) {
			echo '<p class="success">' . $user->getDisplayName() . ' n\'a pas de panier à cette distribution</p>';
		} else if ( $cnt > 1 ) {
			echo '<p class="success">Les paniers de ' . $user->getDisplayName() . ' ont été inscrits sur la liste des paniers à échanger</p>';
		} else {
			echo '<p class="success">Le panier de ' . $user->getDisplayName() . ' a été inscrit sur la liste des paniers à échanger</p>';
		}
	}

	die();
} );

function amapress_echanger_panier( $panier_id, $user_id = null, $message = null ) {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	if ( ! $user_id ) {
		$user_id = amapress_current_user_id();
	}

	$panier      = new AmapressPanier( $panier_id );
	$panier_date = $panier->getDate();
//    $redir_url = $panier->getPermalink();
	$contrat_instance = $panier->getContrat_instance();
	$adhesions        = array_values( AmapressContrats::get_user_active_adhesion( $user_id, $contrat_instance->ID ) );
	if ( empty( $adhesions ) or ( count( $adhesions ) == 0 ) ) {
		wp_die( 'Vous ne faites pas partie de cette distribution.' );
	}

	$adhesion = array_shift( $adhesions );
	$lieu     = $adhesion->getLieu();//intval(get_post_meta($adhesion->ID, 'amapress_adhesion_lieu', true));
	$dist     = AmapressPaniers::getDistribution( $panier_date, $lieu->ID );

//    $optionsPage = Amapress::resolve_post_id(Amapress::getOption('mes-infos-page'), 'page');
//    $base_url = trailingslashit(get_page_link($optionsPage));

	if ( AmapressPaniers::isIntermittent( $panier->ID, $contrat_instance->ID, $lieu->ID ) ) {
		return 'already';
	}

	$my_post = array(
		'post_type'    => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
		'post_content' => '',
		'post_status'  => 'publish',
		'meta_input'   => array(
			'amapress_intermittence_panier_date'             => $panier_date,
			'amapress_intermittence_panier_panier'           => $panier->ID,
			'amapress_intermittence_panier_contrat_instance' => $contrat_instance->ID,
			'amapress_intermittence_panier_adherent'         => amapress_current_user_id(),
			'amapress_intermittence_panier_lieu'             => $lieu->ID,
			'amapress_intermittence_panier_status'           => 'to_exchange',
			'amapress_intermittence_panier_adh_message'      => $message,
		),
	);
	$new_id  = wp_insert_post( $my_post );

	amapress_send_panier_intermittent_available( $new_id );

	return 'ok';
}

function amapress_send_panier_intermittent_available( $intermittence_panier_id ) {
	if ( is_a( $intermittence_panier_id, 'WP_Post' ) ) {
		$intermittence_panier_id = $intermittence_panier_id->ID;
	}
	$inter = new AmapressIntermittence_panier( $intermittence_panier_id );
//    $panier = $inter->getPanier();
//    $dist = AmapressPaniers::getDistribution($panier->getDate(), $inter->getLieu()->ID);
	//amapress_contrat=intermittent
	//$intermit = amapress_prepare_message_target("post_type=amps_inter_adhe&amapress_date=active|amapress_adhesion_intermittence_user", "Les intermittents", "intermittent");
	$intermit = amapress_prepare_message_target( "user:amapress_contrat=intermittent", "Les intermittents", "intermittent" );
	amapress_send_message(
		Amapress::getOption( 'intermittence-panier-dispo-mail-subject' ),
		Amapress::getOption( 'intermittence-panier-dispo-mail-content' ),
		'', $intermit, $inter );

	amapress_mail_to_current_user(
		Amapress::getOption( 'intermittence-panier-on-list-mail-subject' ),
		Amapress::getOption( 'intermittence-panier-on-list-mail-content' ),
		$inter->getAdherent()->ID,
		$inter );
}
