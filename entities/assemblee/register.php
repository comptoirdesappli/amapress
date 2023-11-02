<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_assemblee' );
function amapress_register_entities_assemblee( $entities ) {
	$entities['assemblee_generale'] = array(
		'internal_name'           => 'amps_assemblee',
		'singular'                => __( 'Assemblée générale', 'amapress' ),
		'plural'                  => __( 'Assemblées générales', 'amapress' ),
		'public'                  => true,
		'logged_or_public'        => true,
		'show_in_menu'            => false,
		'show_in_nav_menu'        => false,
		'comments'                => ! defined( 'AMAPRESS_DISABLE_ASSEMBLEE_COMMENTS' ),
		'approve_logged_comments' => true,
		'public_comments'         => false,
		'editor'                  => false,
		'title'                   => false,
		'thumb'                   => true,
		'title_format'            => 'amapress_assemblee_title_formatter',
		'slug_format'             => 'from_title',
		'slug'                    => __( 'assemblees', 'amapress' ),
		'redirect_archive'        => 'amapress_redirect_agenda',
		'menu_icon'               => 'fa-menu fa-university',
		'default_orderby'         => 'amapress_assemblee_generale_date',
		'default_order'           => 'ASC',
		'show_admin_bar_new'      => true,
		'groups'                  => array(
			__( 'Visibilité', 'amapress' ) => [
				'context' => 'side',
			],
		),
		'views'                   => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_assemblee_views',
			'exp_csv' => true,
		),
		'edit_header'             => function ( $post ) {
			$event = AmapressAssemblee_generale::getBy( $post, true );
			if ( $event ) {
				if ( 'lieu_externe' == $event->getType() ) {
					if ( ! $event->isLieu_externe_AdresseLocalized() ) {
						amapress_add_admin_notice( __( 'Adresse du lieu externe non localisée', 'amapress' ), 'warning', false );
					}
					if ( ! empty( $event->getLieu_externe_adresse_acces() ) && ! $event->isLieu_externe_AdresseAccesLocalized() ) {
						amapress_add_admin_notice( __( 'Adresse d\'accès du lieu externe non localisée', 'amapress' ), 'warning', false );
					}
				}
			}
			TitanFrameworkOption::echoFullEditLinkAndWarning();
		},
		'fields'                  => array(
			'public'        => array(
				'desc'  => __( 'Publique ?', 'amapress' ),
				'group' => __( 'Visibilité', 'amapress' ),
				'type'  => 'checkbox',
			),
			'ordre_du_jour' => array(
				'name'       => __( 'Ordre du jour', 'amapress' ),
				'type'       => 'editor',
				'desc'       => __( 'Ordre du jour', 'amapress' ),
				'searchable' => true,
				'group'      => __( '1/ Ordre du jour', 'amapress' ),
			),
			'date'          => array(
				'name'       => __( 'Date', 'amapress' ),
				'type'       => 'date',
				'time'       => false,
				'required'   => true,
				'desc'       => __( 'Date ', 'amapress' ),
				'group'      => __( '2/ Horaires', 'amapress' ),
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => __( 'Toutes les dates', 'amapress' ),
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'heure_debut'   => array(
				'name'     => __( 'Heure début', 'amapress' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => __( 'Heure début', 'amapress' ),
				'group'    => __( '2/ Horaires', 'amapress' ),
			),
			'heure_fin'     => array(
				'name'     => __( 'Heure fin', 'amapress' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => __( 'Heure fin', 'amapress' ),
				'group'    => __( '2/ Horaires', 'amapress' ),
			),
			'type'          => array(
				'name'        => __( 'Emplacement', 'amapress' ),
				'type'        => 'select',
				'options'     => array(
					'lieu'         => __( 'Lieu de distribution', 'amapress' ),
					'lieu_externe' => __( 'Adresse externe', 'amapress' ),
				),
				'required'    => true,
				'group'       => __( '3/ Emplacement', 'amapress' ),
				'conditional' => array(
					'_default_'    => 'lieu',
					'lieu'         => array(
						'lieu' => array(
							'name'              => __( 'Lieu dist.', 'amapress' ),
							'type'              => 'select-posts',
							'post_type'         => 'amps_lieu',
							'desc'              => __( 'Lieu', 'amapress' ),
							'group'             => __( '3/ Emplacement', 'amapress' ),
							'autoselect_single' => true,
							'searchable'        => true,
							'required'          => true,
						),
					),
					'lieu_externe' => array(
						'lieu_externe_nom'           => array(
							'name'           => __( 'Lieu ext.', 'amapress' ),
							'type'           => 'text',
							'desc'           => __( 'Lieu externe', 'amapress' ),
							'group'          => __( '3/ Emplacement', 'amapress' ),
							'searchable'     => true,
							'required'       => true,
							'col_def_hidden' => true,
						),
						'lieu_externe_adresse'       => array(
							'name'           => __( 'Adresse ext.', 'amapress' ),
							'type'           => 'address',
							'use_as_field'   => true,
							'use_enter_gps'  => true,
							'desc'           => __( 'Adresse', 'amapress' ),
							'group'          => __( '3/ Emplacement', 'amapress' ),
							'searchable'     => true,
							'required'       => true,
							'col_def_hidden' => true,
						),
						'lieu_externe_acces'         => array(
							'name'        => __( 'Accès', 'amapress' ),
							'type'        => 'editor',
							'required'    => false,
							'desc'        => __( 'Accès', 'amapress' ),
							'group'       => __( '3/ Emplacement', 'amapress' ),
							'searchable'  => true,
							'show_column' => false,
						),
						'lieu_externe_adresse_acces' => array(
							'name'        => __( 'Adresse d\'accès', 'amapress' ),
							'type'        => 'address',
							'desc'        => __( 'Adresse d\'accès', 'amapress' ),
							'group'       => __( '3/ Emplacement', 'amapress' ),
							'searchable'  => true,
							'show_column' => false,
						),
					),
				)
			),
			'participants'  => array(
				'name'         => __( 'Participants', 'amapress' ),
				'type'         => 'select-users',
				'readonly'     => true,
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Participants', 'amapress' ),
				'group'        => __( '4/ Participants', 'amapress' ),
				'after_option' => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$visite = new AmapressAssemblee_generale( $option->getPostID() );
					echo '<p>' . sprintf( __( 'Les inscriptions se gèrent <a href="%s" target="_blank">ici</a> pour cette AG', 'amapress' ), esc_attr( $visite->getPermalink() ) ) . '</p>';
				},
			),
		),
	);

	return $entities;
}