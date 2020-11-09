<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_request' );
function amapress_register_entities_adhesion_request( $entities ) {
	$entities['adhesion_request'] = array(
		'internal_name'      => 'amps_adh_req',
		'singular'           => __( 'Demande d\'adhésion', 'amapress' ),
		'plural'             => __( 'Demandes d\'adhésion', 'amapress' ),
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
				'label'     => __( 'Créer compte utilisateur', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesionRequest::getBy( $adh_id );

					return ! empty( $adh ) && empty( $adh->getAmapienIfExists() );
				},
			],
			'send_reply'        => __( 'Envoyer le mail de réponse type', 'amapress' ),
			'send_reply_manual' => __( 'Réponse personnalisée envoyée', 'amapress' ),
		),
		'show_admin_bar_new' => true,
		'default_orderby'    => 'post_date',
		'default_order'      => 'ASC',
		'show_date_column'   => true,
		'fields'             => array(
			'email'             => array(
				'name'       => __( 'Email', 'amapress' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => __( 'Email', 'amapress' ),
				'is_email'   => true,
				'searchable' => true,
			),
			'first_name'        => array(
				'name'       => __( 'Prénom', 'amapress' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => __( 'Prénom', 'amapress' ),
				'searchable' => true,
			),
			'last_name'         => array(
				'name'       => __( 'Nom', 'amapress' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => __( 'Nom', 'amapress' ),
				'searchable' => true,
			),
			'amapien'           => array(
				'name'                 => __( 'Amapien', 'amapress' ),
				'type'                 => 'custom',
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'custom'               => function ( $adh_id ) {
					$adh     = AmapressAdhesionRequest::getBy( $adh_id );
					$amapien = $adh->getAmapienIfExists();
					if ( $amapien ) {
						return Amapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName(), true, true );
					} else {
						return __( 'Non existant', 'amapress' );
					}
				},
				'use_custom_as_column' => true,
			),
			'reply_type'        => array(
				'name'        => __( 'Réponse type', 'amapress' ),
				'type'        => 'custom',
				'show_column' => false,
				'show_on'     => 'edit-only',
				'custom'      => function ( $adh_id ) {
					$adh = AmapressAdhesionRequest::getBy( $adh_id );

					return $adh->getFormattedReplyMail();
				},
			),
			'reply_cnt'         => array(
				'name'                 => __( 'Réponses', 'amapress' ),
				'type'                 => 'custom',
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'custom'               => function ( $adh_id ) {
					$adh = AmapressAdhesionRequest::getBy( $adh_id );
					$cnt = $adh->getSendReplyCount();
					if ( $cnt > 0 ) {
						return sprintf( __( 'Réponse envoyée (%d fois)', 'amapress' ), $cnt );
					} else {
						return __( 'Réponse non envoyée', 'amapress' );
					}
				},
				'use_custom_as_column' => true,
			),
			'date'              => array(
				'name'     => __( 'Date demande', 'amapress' ),
				'type'     => 'date',
				'time'     => true,
				'required' => true,
				'desc'     => __( 'Date de la demande', 'amapress' ),
			),
			'adresse'           => array(
				'name'       => __( 'Adresse', 'amapress' ),
				'type'       => 'textarea',
				'desc'       => __( 'Nom', 'amapress' ),
				'searchable' => true,
			),
			'adresse_localized' => array(
				'name'               => __( 'Localisé', 'amapress' ),
				'type'               => 'address',
				'field_name_prefix'  => 'amapress_adhesion_request',
				'use_as_field'       => false,
				'address_field_name' => 'amapress_adhesion_request_adresse',
			),
			'telephone'         => array(
				'name'       => __( 'Téléphone', 'amapress' ),
				'type'       => 'text',
				'desc'       => __( 'Téléphone', 'amapress' ),
				'searchable' => true,
			),
			'other_info'        => array(
				'name' => __( 'Autres informations', 'amapress' ),
				'type' => 'editor',
			),
			'lieux'             => array(
				'name'              => __( 'Lieux de distribution', 'amapress' ),
				'type'              => 'multicheck-posts',
				'post_type'         => AmapressLieu_distribution::INTERNAL_POST_TYPE,
				'desc'              => __( 'Lieux de distribution', 'amapress' ),
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => __( 'Tous les lieux', 'amapress' ),
				),
			),
			'contrats'          => array(
				'name'      => __( 'Contrats', 'amapress' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat::INTERNAL_POST_TYPE,
				'desc'      => __( 'Contrats', 'amapress' ),
			),
			'contrat_intances'  => array(
				'name'      => __( 'Contrats spécifiques', 'amapress' ),
				'type'      => 'multicheck-posts',
				'post_type' => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'desc'      => __( 'Contrats', 'amapress' ),
			),
			'intermittent'      => array(
				'name' => __( 'Intermittent', 'amapress' ),
				'type' => 'checkbox',
				'desc' => __( 'Le contact souhaite devenir intermittent', 'amapress' ),
			),
			'status'            => array(
				'name'       => __( 'Statut', 'amapress' ),
				'type'       => 'select',
				'group'      => __( '1/ Informations', 'amapress' ),
				'options'    => array(
					'to_confirm' => __( 'A confirmer', 'amapress' ),
					'confirmed'  => __( 'Confirmée', 'amapress' ),
					'cancelled'  => __( 'Annulée', 'amapress' ),
				),
				'required'   => true,
				'desc'       => __( 'Statut', 'amapress' ),
				'top_filter' => array(
					'name'        => 'amapress_status',
					'placeholder' => __( 'Toutes les statuts', 'amapress' ),
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
		amapress_add_admin_notice( sprintf( __( 'Accéder à la configuration du <a href="%s" target="_blank">mail de réponse type</a>', 'amapress' ), esc_attr( admin_url( 'options-general.php?page=amapress_site_options_page&tab=adh_req_reply_mail' ) ) ), 'notice', false, false );
	}
} );
