<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_intermittence' );
function amapress_register_entities_intermittence( $entities ) {
	$entities['intermittence_panier'] = array(
		'singular'         => __( 'Panier intermittent', 'amapress' ),
		'plural'           => __( 'Paniers intermittents', 'amapress' ),
		'internal_name'    => 'amps_inter_panier',
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => false,
		'editor'           => false,
		'slug'             => 'intermittences_paniers',
		'title_format'     => 'amapress_intermittence_panier_title_formatter',
		'slug_format'      => 'from_title',
		'menu_icon'        => 'fa-menu fa-shopping-basket',
		'default_orderby'  => 'amapress_intermittence_panier_date',
		'default_order'    => 'ASC',
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_intermittence_panier_views',
			'exp_csv' => true,
		),
		'row_actions'      => array(
			'validate_repreneur' => array(
				'label'     => __( 'Valider le repreneur', 'amapress' ),
				'condition' => function ( $post_id ) {
					$panier = AmapressIntermittence_panier::getBy( $post_id );

					return ! empty( $panier )
					       && AmapressIntermittence_panier::EXCHANGE_VALIDATE_WAIT == $panier->getStatus()
					       && ! empty( $panier->getAsk() )
					       && 1 == count( $panier->getAsk() );
				},
				'confirm'   => true,
			),
			'switch_adherent'    => [
				'label'     => __( 'Changer pour Adhérent', 'amapress' ),
				'condition' => function ( $post_id ) {
					return class_exists( 'user_switching' );
				},
				'confirm'   => true,
			]
		),
		'fields'           => array(
			'date'               => array(
				'name'       => __( 'Date', 'amapress' ),
				'type'       => 'date',
				'readonly'   => true,
				'desc'       => __( 'Date ', 'amapress' ),
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => __( 'Toutes les dates', 'amapress' ),
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'panier'             => array(
				'name'        => __( 'Panier(s)', 'amapress' ),
				'type'        => 'multicheck-posts',
				'post_type'   => AmapressPanier::INTERNAL_POST_TYPE,
				'readonly'    => true,
				'desc'        => __( 'Panier(s)', 'amapress' ),
				'show_column' => false,
			),
			'contrat_instance'   => array(
				'name'      => __( 'Contrat(s)', 'amapress' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'readonly'  => true,
				'desc'      => __( 'Contrat(s)', 'amapress' ),
			),
			'lieu'               => array(
				'name'              => __( 'Lieu', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressLieu_distribution::INTERNAL_POST_TYPE,
				'readonly'          => true,
				'desc'              => __( 'Lieu', 'amapress' ),
				'autoselect_single' => true,
//				'searchable'        => true,
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => __( 'Tous les lieux', 'amapress' ),
				),
			),
			'repreneur'          => array(
				'name'        => __( 'Repreneur', 'amapress' ),
				'type'        => 'select-users',
				'desc'        => __( 'Repreneur', 'amapress' ),
				'searchable'  => true,
				'readonly'    => true,
				'show_column' => false,
			),
			'adherent'           => array(
				'name'       => __( 'Adherent', 'amapress' ),
				'type'       => 'select-users',
				'readonly'   => true,
				'desc'       => __( 'Adherent', 'amapress' ),
				'searchable' => true,
			),
			'status'             => array(
				'name'       => __( 'Statut', 'amapress' ),
				'type'       => 'select',
				'top_filter' => array(
					'name'        => 'amapress_status',
					'placeholder' => __( 'Tous les états', 'amapress' ),
				),
				'options'    => array(
					'to_exchange'     => 'A réserver',
					'exch_valid_wait' => __( 'En attente de validation de l\'échange', 'amapress' ),
					'exchanged'       => __( 'Réservé', 'amapress' ),
					'closed'          => __( 'Cloturé', 'amapress' ),
					'cancelled'       => __( 'Annulé', 'amapress' ),
				),
				'required'   => true,
				'desc'       => __( 'Statut', 'amapress' ),
				'readonly'   => true,
			),
			'waiters'            => array(
				'name'                 => __( 'Repreneur', 'amapress' ),
				'type'                 => 'custom',
				'use_custom_as_column' => true,
				'custom'               => function ( $post_id ) {
					$panier_inter = AmapressIntermittence_panier::getBy( $post_id );
					if ( empty( $panier_inter ) ) {
						return '';
					}
					$repreneur = $panier_inter->getRepreneur();
					if ( ! empty( $repreneur ) ) {
						return Amapress::makeLink( $repreneur->getEditLink(), $repreneur->getDisplayName() . '(' . $repreneur->getUser()->user_email . ')', true, true );
					}
					$askers = [];
					foreach ( $panier_inter->getAsk() as $user_id => $user_info ) {
						$user = AmapressUser::getBy( $user_id );
						if ( empty( $user ) ) {
							continue;
						}
						$askers[] = Amapress::makeLink( $user->getEditLink(), $user->getDisplayName() . '(' . $user->getUser()->user_email . ')', true, true ) . __( ' attente depuis ', 'amapress' ) . date_i18n( 'd/m/Y', $user_info['date'] );
					}

					return implode( ', ', $askers );
				}
			),
			'adh_message'        => array(
				'name'       => __( 'Message au repreneur', 'amapress' ),
				'type'       => 'readonly',
				'desc'       => __( 'Message', 'amapress' ),
				'searchable' => true,
			),
			'adh_cancel_message' => array(
				'name'       => __( 'Message d\'annulation', 'amapress' ),
				'type'       => 'readonly',
				'desc'       => __( 'Message', 'amapress' ),
				'searchable' => true,
			),
		),
	);

	return $entities;
}

add_action( 'amapress_row_action_intermittence_panier_validate_repreneur', 'amapress_row_action_intermittence_panier_validate_repreneur' );
function amapress_row_action_intermittence_panier_validate_repreneur( $post_id ) {
	$panier = AmapressIntermittence_panier::getBy( $post_id );
	if ( ! empty( $panier )
	     && AmapressIntermittence_panier::EXCHANGE_VALIDATE_WAIT == $panier->getStatus()
	     && ! empty( $panier->getAsk() )
	     && 1 == count( $panier->getAsk() ) ) {
		foreach ( $panier->getAsk() as $user_id => $user_info ) {
			$panier->validateReprise( $user_id, true );
			break;
		}
	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_intermittence_panier_switch_adherent', 'amapress_row_action_intermittence_panier_switch_adherent' );
function amapress_row_action_intermittence_panier_switch_adherent( $post_id ) {
	$panier = AmapressIntermittence_panier::getBy( $post_id );
	if ( ! empty( $panier ) && ! empty( $panier->getAdherent() ) && class_exists( 'user_switching' ) ) {
		$link = user_switching::maybe_switch_url( $panier->getAdherent()->getUser() );
		if ( $link ) {
			wp_redirect_and_exit( htmlspecialchars_decode( $link ) );
		}
	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'amapress_clean_inter_paniers' ) ) {
		wp_schedule_event( time(), 'monthly', 'amapress_clean_inter_paniers' );
	}
} );

add_action( 'amapress_clean_inter_paniers', function () {
	$delay_months = intval( Amapress::getOption( 'delete_inter_paniers_months' ) );
	if ( $delay_months > 0 ) {
		$start_date = Amapress::start_of_month( Amapress::add_a_month( amapress_time(), - ( 120 + $delay_months ) ) );
		$end_date   = Amapress::end_of_month( Amapress::add_a_month( amapress_time(), - $delay_months ) );
		$paniers    = AmapressIntermittence_panier::get_paniers_intermittents(
			$start_date, $end_date
		);
		AmapressIntermittence_panier::getStats( $start_date, $end_date );
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		foreach ( $paniers as $panier ) {
			wp_delete_post( $panier->ID, true );
		}
		$wpdb->query( 'COMMIT' );
	}
} );
