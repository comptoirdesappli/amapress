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
		'default_order'      => 'ASC',
		'show_admin_bar_new' => true,
		'views'              => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_assemblee_views',
			'exp_csv' => true,
		),
		'edit_header'        => function ( $post ) {
			$event = AmapressAssemblee_generale::getBy( $post, true );
			if ( $event ) {
				if ( 'lieu_externe' == $event->getType() ) {
					if ( ! $event->isLieu_externe_AdresseLocalized() ) {
						amapress_add_admin_notice( 'Adresse du lieu externe non localisée', 'warning', false );
					}
					if ( ! empty( $event->getLieu_externe_adresse_acces() ) && ! $event->isLieu_externe_AdresseAccesLocalized() ) {
						amapress_add_admin_notice( 'Adresse d\'accès du lieu externe non localisée', 'warning', false );
					}
				}
			}
			TitanFrameworkOption::echoFullEditLinkAndWarning();
		},
		'fields'           => array(
			'ordre_du_jour' => array(
				'name'       => __( 'Ordre du jour', 'amapress' ),
				'type'       => 'editor',
				'desc'       => 'Ordre du jour',
				'searchable' => true,
				'group'      => '1/ Ordre du jour',
			),
			'date'          => array(
				'name'       => __( 'Date', 'amapress' ),
				'type'       => 'date',
				'time'       => false,
				'required'   => true,
				'desc'       => 'Date ',
				'group'      => '2/ Horaires',
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'heure_debut'   => array(
				'name'     => __( 'Heure début', 'amapress' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure début',
				'group'    => '2/ Horaires',
			),
			'heure_fin'     => array(
				'name'     => __( 'Heure fin', 'amapress' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure fin',
				'group'    => '2/ Horaires',
			),
			'type'          => array(
				'name'        => __( 'Emplacement', 'amapress' ),
				'type'        => 'select',
				'options'     => array(
					'lieu'         => 'Lieu de distribution',
					'lieu_externe' => 'Adresse externe',
				),
				'required'    => true,
				'group'       => '3/ Emplacement',
				'conditional' => array(
					'_default_'    => 'lieu',
					'lieu'         => array(
						'lieu' => array(
							'name'              => __( 'Lieu dist.', 'amapress' ),
							'type'              => 'select-posts',
							'post_type'         => 'amps_lieu',
							'desc'              => 'Lieu',
							'group'             => '3/ Emplacement',
							'autoselect_single' => true,
							'searchable'        => true,
							'required'          => true,
						),
					),
					'lieu_externe' => array(
						'lieu_externe_nom'           => array(
							'name'           => __( 'Lieu ext.', 'amapress' ),
							'type'           => 'text',
							'desc'           => 'Lieu externe',
							'group'          => '3/ Emplacement',
							'searchable'     => true,
							'required'       => true,
							'col_def_hidden' => true,
						),
						'lieu_externe_adresse'       => array(
							'name'           => __( 'Adresse ext.', 'amapress' ),
							'type'           => 'address',
							'use_as_field'   => true,
							'use_enter_gps'  => true,
							'desc'           => 'Adresse',
							'group'          => '3/ Emplacement',
							'searchable'     => true,
							'required'       => true,
							'col_def_hidden' => true,
						),
						'lieu_externe_acces'         => array(
							'name'        => __( 'Accès', 'amapress' ),
							'type'        => 'editor',
							'required'    => false,
							'desc'        => 'Accès',
							'group'       => '3/ Emplacement',
							'searchable'  => true,
							'show_column' => false,
						),
						'lieu_externe_adresse_acces' => array(
							'name'        => __( 'Adresse d\'accès', 'amapress' ),
							'type'        => 'address',
							'desc'        => 'Adresse d\'accès',
							'group'       => '3/ Emplacement',
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
				'desc'         => 'Participants',
				'group'        => '4/ Participants',
				'after_option' => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$visite = new AmapressAssemblee_generale( $option->getPostID() );
					echo '<p>Les inscriptions se gèrent <a href="' . esc_attr( $visite->getPermalink() ) . '" target="_blank">ici</a> pour cette AG</p>';
				},
			),
		),
	);

	return $entities;
}