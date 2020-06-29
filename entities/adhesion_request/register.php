<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_request' );
function amapress_register_entities_adhesion_request( $entities ) {
	$entities['adhesion_request'] = array(
		'internal_name'      => 'amps_adh_req',
		'singular'           => amapress__( 'Demande d\'adhésion' ),
		'plural'             => amapress__( 'Demandes d\'adhésion' ),
		'public'             => 'adminonly',
		'show_in_menu'       => false,
		'show_in_nav_menu'   => false,
		'special_options'    => array(),
		'slug'               => 'adhesions_requests',
		'title_format'       => 'amapress_adhesion_request_title_formatter',
		'slug_format'        => 'from_title',
		'title'              => false,
		'editor'             => false,
//        'menu_icon' => 'flaticon-business',
		'redirect_archive'   => 'amapress_redirect_agenda',
//        'menu_icon' => 'fa-menu fa-university',
		'views'              => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_adhesion_request_views',
			'exp_csv' => true,
		),
		'row_actions'        => array(
			'create_user'       => [
				'label'     => 'Créer compte utilisateur',
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesionRequest::getBy( $adh_id );

					return ! empty( $adh ) && empty( $adh->getAmapienIfExists() );
				},
			],
			'send_reply'        => 'Envoyer le mail de réponse type',
			'send_reply_manual' => 'Réponse personnalisée envoyée',
		),
		'show_admin_bar_new' => true,
		'default_orderby'    => 'post_date',
		'default_order'      => 'ASC',
		'show_date_column'   => true,
		'fields'             => array(
			'email'             => array(
				'name'       => amapress__( 'Email' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => 'Email',
				'is_email'   => true,
				'searchable' => true,
			),
			'first_name'        => array(
				'name'       => amapress__( 'Prénom' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => 'Prénom',
				'searchable' => true,
			),
			'last_name'         => array(
				'name'       => amapress__( 'Nom' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => 'Nom',
				'searchable' => true,
			),
			'amapien'           => array(
				'name'                 => amapress__( 'Amapien' ),
				'type'                 => 'custom',
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'custom'               => function ( $adh_id ) {
					$adh     = AmapressAdhesionRequest::getBy( $adh_id );
					$amapien = $adh->getAmapienIfExists();
					if ( $amapien ) {
						return Amapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName(), true, true );
					} else {
						return 'Non existant';
					}
				},
				'use_custom_as_column' => true,
			),
			'reply_cnt'         => array(
				'name'                 => amapress__( 'Réponses' ),
				'type'                 => 'custom',
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'custom'               => function ( $adh_id ) {
					$adh = AmapressAdhesionRequest::getBy( $adh_id );
					$cnt = $adh->getSendReplyCount();
					if ( $cnt > 0 ) {
						return sprintf( 'Réponse envoyée (%d fois)', $cnt );
					} else {
						return 'Réponse non envoyée';
					}
				},
				'use_custom_as_column' => true,
			),
			'adresse'           => array(
				'name'       => amapress__( 'Adresse' ),
				'type'       => 'textarea',
				'desc'       => 'Nom',
				'searchable' => true,
			),
			'adresse_localized' => array(
				'name'               => amapress__( 'Localisé' ),
				'type'               => 'address',
				'field_name_prefix'  => 'amapress_adhesion_request',
				'use_as_field'       => false,
				'address_field_name' => 'amapress_adhesion_request_adresse',
			),
			'telephone'         => array(
				'name'       => amapress__( 'Téléphone' ),
				'type'       => 'text',
				'desc'       => 'Téléphone',
				'searchable' => true,
			),
			'other_info'        => array(
				'name' => amapress__( 'Autres informations' ),
				'type' => 'editor',
			),
			'lieux'             => array(
				'name'              => amapress__( 'Lieux de distribution' ),
				'type'              => 'multicheck-posts',
				'post_type'         => AmapressLieu_distribution::INTERNAL_POST_TYPE,
				'desc'              => 'Lieux de distribution',
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Tous les lieux',
				),
			),
			'contrats'          => array(
				'name'      => amapress__( 'Contrats' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat::INTERNAL_POST_TYPE,
				'desc'      => 'Contrats',
			),
			'contrat_intances'  => array(
				'name'      => amapress__( 'Contrats spécifiques' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'desc'      => 'Contrats',
			),
			'intermittent'      => array(
				'name' => amapress__( 'Intermittent' ),
				'type' => 'checkbox',
				'desc' => 'Le contact souhaite devenir intermittent',
			),
			'status'            => array(
				'name'       => amapress__( 'Statut' ),
				'type'       => 'select',
				'group'      => '1/ Informations',
				'options'    => array(
					'to_confirm' => 'A confirmer',
					'confirmed'  => 'Confirmée',
					'cancelled'  => 'Annulée',
				),
				'required'   => true,
				'desc'       => 'Statut',
				'top_filter' => array(
					'name'        => 'amapress_status',
					'placeholder' => 'Toutes les statuts',
				),
			),
		),
	);

	return $entities;
}

function amapress_adhesion_request_count_shortcode( $atts ) {
	amapress_ensure_no_cache();

	$cnt = get_option( 'amps_adh_req_count' );
	if ( false === $cnt ) {
		$cnt = get_posts_count(
			array(
				'posts_per_page' => - 1,
				'post_type'      => 'amps_adh_req',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_adhesion_request_status',
						'value'   => 'to_confirm',
						'compare' => '=',
					),
					array(
						'key'     => 'amapress_adhesion_request_status',
						'compare' => 'NOT EXISTS',
					),
				)
			)
		);
		update_option( 'amps_adh_req_count', $cnt );
	}

	return "<span class='update-plugins count-$cnt' style='background-color:white;color:black;margin-left:5px;'><span class='plugin-count'>$cnt</span></span>";
}

add_action( 'amapress_row_action_adhesion_request_create_user', 'amapress_row_action_adhesion_request_create_user' );
function amapress_row_action_adhesion_request_create_user( $post_id ) {
	$adh_req = AmapressAdhesionRequest::getBy( $post_id );
	if ( empty( $adh_req->getAmapienIfExists() ) ) {
		amapress_create_user_if_not_exists( $adh_req->getEmail(),
			$adh_req->getFirstName(), $adh_req->getLastName(),
			$adh_req->getAdresse(), $adh_req->getTelephone() );
	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_request_send_reply', 'amapress_row_action_adhesion_request_send_reply' );
function amapress_row_action_adhesion_request_send_reply( $post_id ) {
	$adh_req = AmapressAdhesionRequest::getBy( $post_id );
	if ( $adh_req ) {
		$adh_req->sendReplyMail();
	}
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_request_send_reply_manual', 'amapress_row_action_adhesion_request_send_reply_manual' );
function amapress_row_action_adhesion_request_send_reply_manual( $post_id ) {
	$adh_req = AmapressAdhesionRequest::getBy( $post_id );
	if ( $adh_req ) {
		$adh_req->incrSendReplyCount();
	}
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'admin_head', function () {
	$screen = get_current_screen();
	if ( $screen && ( 'edit-amps_adh_req' == $screen->id || 'amps_adh_req' == $screen->id ) ) {
		amapress_add_admin_notice( 'Accéder à la configuration du <a href="' . esc_attr( admin_url( 'options-general.php?page=amapress_site_options_page&tab=adh_req_reply_mail' ) ) . '" target="_blank">mail de réponse type</a>', 'notice', false, false );
	}
} );
