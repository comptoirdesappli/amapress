<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_visite' );
function amapress_register_entities_visite( $entities ) {
	$entities['visite'] = array(
		'singular'                => __( 'Visite à la ferme', 'amapress' ),
		'plural'                  => __( 'Visites à la ferme', 'amapress' ),
		'public'                  => true,
		'logged_or_public'        => true,
		'show_in_menu'            => false,
		'show_in_nav_menu'        => false,
		'editor'                  => false,
		'comments'                => ! defined( 'AMAPRESS_DISABLE_VISITE_COMMENTS' ),
		'public_comments'         => false,
		'approve_logged_comments' => true,
		'title'                   => false,
		'thumb'                   => true,
		'title_format'            => 'amapress_visite_title_formatter',
		'slug_format'             => 'from_title',
		'slug'                    => __( 'visites', 'amapress' ),
		'redirect_archive'        => 'amapress_redirect_agenda',
		'menu_icon'               => 'flaticon-sprout',
		'show_admin_bar_new'      => true,
		'groups'                  => array(
			__( 'Visibilité', 'amapress' ) => [
				'context' => 'side',
			],
		),
		'views'                   => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_visite_views',
			'exp_csv' => true,
		),
		'edit_header'             => function ( $post ) {
			TitanFrameworkOption::echoFullEditLinkAndWarning();
		},
		'fields'                  => array(
//			'photo'        => array(
//				'name'  => __( 'Photo', 'amapress' ),
//				'type'  => 'upload',
//				'group' => __('Information', 'amapress'),
//				'desc'  => __('Photo', 'amapress'),
//			),
			'public'       => array(
				'desc'  => __( 'Publique ?', 'amapress' ),
				'group' => __( 'Visibilité', 'amapress' ),
				'type'  => 'checkbox',
			),
			'status'       => array(
				'name'     => __( 'Statut', 'amapress' ),
				'type'     => 'select',
				'group'    => __( '1/ Informations', 'amapress' ),
				'options'  => array(
					'to_confirm' => __( 'A confirmer', 'amapress' ),
					'confirmed'  => __( 'Confirmée', 'amapress' ),
					'cancelled'  => __( 'Annulée', 'amapress' ),
				),
				'default'  => 'confirmed',
				'required' => true,
			),
			'au_programme' => array(
				'name'       => __( 'Au programme', 'amapress' ),
				'type'       => 'editor',
				'desc'       => __( 'Au programme', 'amapress' ),
				'searchable' => true,
				'group'      => __( '1/ Informations', 'amapress' ),
			),
			'date'         => array(
				'name'         => __( 'Date de visite', 'amapress' ),
				'type'         => 'date',
				'time'         => false,
				'required'     => true,
				'desc'         => __( 'Date de visite', 'amapress' ),
				'import_key'   => true,
				'csv_required' => true,
				'group'        => __( '2/ Horaires', 'amapress' ),
			),
			'heure_debut'  => array(
				'name'         => __( 'Heure début', 'amapress' ),
				'type'         => 'date',
				'date'         => false,
				'time'         => true,
				'required'     => true,
				'desc'         => __( 'Heure début', 'amapress' ),
				'csv_required' => true,
				'group'        => __( '2/ Horaires', 'amapress' ),
			),
			'heure_fin'    => array(
				'name'         => __( 'Heure fin', 'amapress' ),
				'type'         => 'date',
				'date'         => false,
				'time'         => true,
				'required'     => true,
				'desc'         => __( 'Heure fin', 'amapress' ),
				'csv_required' => true,
				'group'        => __( '2/ Horaires', 'amapress' ),
			),
			'slots_conf'   => array(
				'name'  => __( 'Créneau(x)', 'amapress' ),
				'type'  => 'text',
				'desc'  => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$visite = new AmapressVisite( $option->getPostID() );

					$ret            = '';
					$users_in_slots = count( $visite->getUserIdsWithAnySlot() );
					if ( $users_in_slots > 0 ) {
						$ret .= '<p><strong style="color: red">' . sprintf(
								__( 'Attention : %d amapien(s) sont déjà inscrits. Modifier la configuration peut impacter l\'affectation de leurs créneaux', 'amapress' ),
								$users_in_slots
							) . '</strong></p>';
					}

					$ret .= sprintf( __( 'Configurer un ou plusieurs créneau(x) séparés par des | et de la forme : <strong>Heure Début-Heure Fin(nom du créneau)</strong>
<br/>Exemple : 9h-12h(matin)|14h-17h(aprem)|9h-17h(journée)
<br/>Créneau(x) horaire(s) actuels (<strong>visite de %s à %s</strong>) : %s', 'amapress' ),
						date_i18n( 'H:i', $visite->getStartDateAndHour() ),
						date_i18n( 'H:i', $visite->getEndDateAndHour() ),
						$visite->getSlotsDescription()
					);// .
//					       '<br/>' .
//					       Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/distribution' );
					return $ret;
				},
				'group' => __( '2/ Horaires', 'amapress' ),
			),
			'producteur'           => array(
				'name'              => __( 'Producteur', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'required'          => true,
				'desc'              => __( 'Producteur', 'amapress' ),
				'import_key'        => true,
				'csv_required'      => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => __( 'Toutes les producteurs', 'amapress' ),
				),
				'searchable'        => true,
				'group'             => __( '3/ Emplacement', 'amapress' ),
			),
			'lieu_externe_nom'     => array(
				'name'           => __( 'Lieu ext.', 'amapress' ),
				'type'           => 'text',
				'desc'           => __( 'Lieu externe', 'amapress' ),
				'group'          => __( '3/ Emplacement', 'amapress' ),
				'searchable'     => true,
				'col_def_hidden' => true,
			),
			'lieu_externe_adresse' => array(
				'name'           => __( 'Adresse ext.', 'amapress' ),
				'type'           => 'address',
				'use_as_field'   => true,
				'use_enter_gps'  => true,
				'desc'           => __( 'Adresse', 'amapress' ),
				'group'          => __( '3/ Emplacement', 'amapress' ),
				'searchable'     => true,
				'col_def_hidden' => true,
			),
			'lieu_externe_acces'   => array(
				'name'        => __( 'Accès', 'amapress' ),
				'type'        => 'editor',
				'required'    => false,
				'desc'        => __( 'Accès', 'amapress' ),
				'group'       => __( '3/ Emplacement', 'amapress' ),
				'searchable'  => true,
				'show_column' => false,
			),
			'participants'         => array(
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
					$visite = new AmapressVisite( $option->getPostID() );
					echo '<p>' . sprintf( __( 'Les inscriptions se gèrent <a href="%s" target="_blank">ici</a> pour cette visite', 'amapress' ), esc_attr( $visite->getPermalink() ) ) . '</p>';
				},
			),
		),
	);

	return $entities;
}