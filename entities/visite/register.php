<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_visite' );
function amapress_register_entities_visite( $entities ) {
	$entities['visite'] = array(
		'singular'                => amapress__( 'Visite à la ferme' ),
		'plural'                  => amapress__( 'Visites à la ferme' ),
		'public'                  => true,
		'logged_or_public'        => true,
		'show_in_menu'            => false,
		'show_in_nav_menu'        => false,
		'editor'                  => false,
		'comments'                => true,
		'public_comments'         => false,
		'approve_logged_comments' => true,
		'title'                   => false,
		'thumb'                   => true,
		'title_format'            => 'amapress_visite_title_formatter',
		'slug_format'             => 'from_title',
		'slug'                    => amapress__( 'visites' ),
		'redirect_archive'        => 'amapress_redirect_agenda',
		'menu_icon'               => 'flaticon-sprout',
		'show_admin_bar_new'      => true,
		'views'                   => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_visite_views',
			'exp_csv' => true,
		),
		'edit_header'        => function ( $post ) {
			TitanFrameworkOption::echoFullEditLinkAndWarning();
		},
		'fields'           => array(
//			'photo'        => array(
//				'name'  => amapress__( 'Photo' ),
//				'type'  => 'upload',
//				'group' => 'Information',
//				'desc'  => 'Photo',
//			),
			'status'       => array(
				'name'     => amapress__( 'Statut' ),
				'type'     => 'select',
				'group'    => '1/ Informations',
				'options'  => array(
					'to_confirm' => 'A confirmer',
					'confirmed'  => 'Confirmée',
					'cancelled'  => 'Annulée',
				),
				'default'  => 'confirmed',
				'required' => true,
			),
			'au_programme' => array(
				'name'       => amapress__( 'Au programme' ),
				'type'       => 'editor',
				'desc'       => 'Au programme',
				'searchable' => true,
				'group'      => '1/ Informations',
			),
			'date'         => array(
				'name'         => amapress__( 'Date de visite' ),
				'type'         => 'date',
				'time'         => false,
				'required'     => true,
				'desc'         => 'Date de visite',
				'import_key'   => true,
				'csv_required' => true,
				'group'        => '2/ Horaires',
			),
			'heure_debut'  => array(
				'name'         => amapress__( 'Heure début' ),
				'type'         => 'date',
				'date'         => false,
				'time'         => true,
				'required'     => true,
				'desc'         => 'Heure début',
				'csv_required' => true,
				'group'        => '2/ Horaires',
			),
			'heure_fin'    => array(
				'name'         => amapress__( 'Heure fin' ),
				'type'         => 'date',
				'date'         => false,
				'time'         => true,
				'required'     => true,
				'desc'         => 'Heure fin',
				'csv_required' => true,
				'group'        => '2/ Horaires',
			),
			'slots_conf'           => array(
				'name'  => amapress__( 'Créneau(x)' ),
				'type'  => 'text',
				'desc'  => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$visite = new AmapressVisite( $option->getPostID() );

					$ret            = '';
					$users_in_slots = count( $visite->getUserIdsWithAnySlot() );
					if ( $users_in_slots > 0 ) {
						$ret .= sprintf(
							'<p><strong style="color: red">Attention : %d amapien(s) sont déjà inscrits. Modifier la configuration peut impacter l\'affectation de leurs créneaux</strong></p>',
							$users_in_slots
						);
					}

					$ret .= 'Configurer un ou plusieurs créneau(x) séparés par des | et de la forme : <strong>Heure Début-Heure Fin(nom du créneau)</strong>
<br/>Exemple : 9h-12h(matin)|14h-17h(aprem)|9h-17h(journée)<br/>' .
					        sprintf( 'Créneau(x) horaire(s) actuels (<strong>visite de %s à %s</strong>) : %s',
						        date_i18n( 'H:i', $visite->getStartDateAndHour() ),
						        date_i18n( 'H:i', $visite->getEndDateAndHour() ),
						        $visite->getSlotsDescription()
					        );// .
//					       '<br/>' .
//					       Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/distribution' );
					return $ret;
				},
				'group' => '2/ Horaires',
			),
			'producteur'           => array(
				'name'              => amapress__( 'Producteur' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'required'          => true,
				'desc'              => 'Producteur',
				'import_key'        => true,
				'csv_required'      => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => 'Toutes les producteurs',
				),
				'searchable'        => true,
				'group'             => '3/ Emplacement',
			),
			'lieu_externe_nom'     => array(
				'name'           => amapress__( 'Lieu ext.' ),
				'type'           => 'text',
				'desc'           => 'Lieu externe',
				'group'          => '3/ Emplacement',
				'searchable'     => true,
				'col_def_hidden' => true,
			),
			'lieu_externe_adresse' => array(
				'name'           => amapress__( 'Adresse ext.' ),
				'type'           => 'address',
				'use_as_field'   => true,
				'use_enter_gps'  => true,
				'desc'           => 'Adresse',
				'group'          => '3/ Emplacement',
				'searchable'     => true,
				'col_def_hidden' => true,
			),
			'lieu_externe_acces'   => array(
				'name'        => amapress__( 'Accès' ),
				'type'        => 'editor',
				'required'    => false,
				'desc'        => 'Accès',
				'group'       => '3/ Emplacement',
				'searchable'  => true,
				'show_column' => false,
			),
			'participants'         => array(
				'name'         => amapress__( 'Participants' ),
				'type'         => 'select-users',
				'readonly'     => true,
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Participants',
				'group'        => '4/ Participants',
				'after_option' => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$visite = new AmapressVisite( $option->getPostID() );
					echo '<p>Les inscription se gère <a href="' . esc_attr( $visite->getPermalink() ) . '" target="_blank">ici</a> pour cette visite</p>';
				},
			),
		),
	);

	return $entities;
}