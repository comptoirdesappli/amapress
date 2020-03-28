<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_intermittence' );
function amapress_register_entities_intermittence( $entities ) {
	$entities['intermittence_panier'] = array(
		'singular'         => amapress__( 'Panier intermittent' ),
		'plural'           => amapress__( 'Paniers intermittents' ),
		'internal_name'    => 'amps_inter_panier',
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'           => false,
		'editor'          => false,
		'slug'            => 'intermittences_paniers',
		'title_format'    => 'amapress_intermittence_panier_title_formatter',
		'slug_format'     => 'from_title',
		'menu_icon'       => 'fa-menu fa-shopping-basket',
		'default_orderby' => 'amapress_intermittence_panier_date',
		'default_order'   => 'ASC',
		'views'           => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_intermittence_panier_views',
			'exp_csv' => true,
		),
		'row_actions'     => array(
			'validate_repreneur' => array(
				'label'     => 'Valider le repreneur',
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
				'label'     => 'Changer pour Adhérent',
				'condition' => function ( $post_id ) {
					return class_exists( 'user_switching' );
				},
				'confirm'   => true,
			]
		),
		'fields'          => array(
			'date'               => array(
				'name'       => amapress__( 'Date' ),
				'type'       => 'date',
				'readonly'   => true,
				'desc'       => 'Date ',
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'panier'             => array(
				'name'        => amapress__( 'Panier(s)' ),
				'type'        => 'multicheck-posts',
				'post_type'   => AmapressPanier::INTERNAL_POST_TYPE,
				'readonly'    => true,
				'desc'        => 'Panier(s)',
				'show_column' => false,
			),
			'contrat_instance'   => array(
				'name'      => amapress__( 'Contrat(s)' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'readonly'  => true,
				'desc'      => 'Contrat(s)',
			),
			'lieu'               => array(
				'name'              => amapress__( 'Lieu' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressLieu_distribution::INTERNAL_POST_TYPE,
				'readonly'          => true,
				'desc'              => 'Lieu',
				'autoselect_single' => true,
//				'searchable'        => true,
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Toutes les lieux',
				),
			),
			'repreneur'          => array(
				'name'        => amapress__( 'Repreneur' ),
				'type'        => 'select-users',
				'desc'        => 'Repreneur',
				'searchable'  => true,
				'readonly'    => true,
				'show_column' => false,
			),
			'adherent'           => array(
				'name'       => amapress__( 'Adherent' ),
				'type'       => 'select-users',
				'readonly'   => true,
				'desc'       => 'Adherent',
				'searchable' => true,
			),
			'status'             => array(
				'name'       => amapress__( 'Statut' ),
				'type'       => 'select',
				'top_filter' => array(
					'name'        => 'amapress_status',
					'placeholder' => 'Tous les états',
				),
				'options'    => array(
					'to_exchange'     => 'A réserver',
					'exch_valid_wait' => 'En attente de validation de l\'échange',
					'exchanged'       => 'Réservé',
					'closed'          => 'Cloturé',
					'cancelled'       => 'Annulé',
				),
				'required'   => true,
				'desc'       => 'Statut',
				'readonly'   => true,
			),
			'waiters'            => array(
				'name'                 => amapress__( 'Repreneur' ),
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
						$askers[] = Amapress::makeLink( $user->getEditLink(), $user->getDisplayName() . '(' . $user->getUser()->user_email . ')', true, true ) . ' attente depuis ' . date_i18n( 'd/m/Y', $user_info['date'] );
					}

					return implode( ', ', $askers );
				}
			),
			'adh_message'        => array(
				'name'       => amapress__( 'Message au repreneur' ),
				'type'       => 'readonly',
				'desc'       => 'Message',
				'searchable' => true,
			),
			'adh_cancel_message' => array(
				'name'       => amapress__( 'Message d\'annulation' ),
				'type'       => 'readonly',
				'desc'       => 'Message',
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