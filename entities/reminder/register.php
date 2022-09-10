<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_reminder' );
function amapress_register_entities_reminder( $entities ) {
	$entities['reminder'] = array(
		'internal_name'    => 'amps_rmd',
		'singular'         => __( 'Rappel libre', 'amapress' ),
		'plural'           => __( 'Rappels libres', 'amapress' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => true,
		'editor'           => false,
		'slug'             => 'rmds',
		'menu_icon'        => 'dashicons-clock',
		'labels'           => array(
			'add_new'      => __( 'Planifier un nouveau rappel libre', 'amapress' ),
			'add_new_item' => __( 'Planifier un rappel libre', 'amapress' ),
		),
		'views'            => array(
			'remove' => array( 'mine' ),
		),
		'fields'           => array(
			'start_date'  => array(
				'name'     => __( 'Premier rappel', 'amapress' ),
				'type'     => 'date',
				'required' => true,
				'group'    => __( '1/ Planification', 'amapress' ),
				'desc'     => __( 'Date et heure à laquelle commence le premier rappel', 'amapress' ),
				'time'     => true,
			),
			'end_date'    => array(
				'name'  => __( 'Date de fin', 'amapress' ),
				'type'  => 'date',
				'group' => __( '1/ Planification', 'amapress' ),
				'desc'  => __( 'Date à laquelle finit le rappel', 'amapress' ),
			),
			'interval'    => array(
				'name'     => __( 'Périodicité', 'amapress' ),
				'type'     => 'select',
				'required' => true,
				'group'    => __( '1/ Planification', 'amapress' ),
				'options'  => array(
					'daily'       => __( 'Journalier', 'amapress' ),
					'weekly'      => __( 'Hebdomadaire', 'amapress' ),
					'monthly'     => __( 'Mensuel', 'amapress' ),
					'two_monthly' => __( 'Tous les deux mois', 'amapress' ),
					'quarterly'   => __( 'Trimestriel', 'amapress' ),
					'half_yearly' => __( 'Semestriel', 'amapress' ),
					'yearly'      => __( 'Annuel', 'amapress' ),
				)
			),
			'other_days'  => array(
				'name'         => __( 'Rappels supplémentaires', 'amapress' ),
				'type'         => 'select',
				'group'        => __( '1/ Planification', 'amapress' ),
				'options'      => array(
					'-1'  => __( '1 jour avant', 'amapress' ),
					'-2'  => __( '2 jours avant', 'amapress' ),
					'-3'  => __( '3 jours avant', 'amapress' ),
					'-4'  => __( '4 jours avant', 'amapress' ),
					'-5'  => __( '5 jours avant', 'amapress' ),
					'-6'  => __( '6 jours avant', 'amapress' ),
					'-7'  => __( '7 jours avant', 'amapress' ),
					'-10' => __( '10 jours avant', 'amapress' ),
					'-15' => __( '15 jours avant', 'amapress' ),
					'-30' => __( '30 jours avant', 'amapress' ),
					'-45' => __( '45 jours avant', 'amapress' ),
					'-60' => __( '60 jours avant', 'amapress' ),
					'-90' => __( '90 jours avant', 'amapress' ),
					'1'   => __( '1 jour après', 'amapress' ),
					'2'   => __( '2 jours après', 'amapress' ),
					'3'   => __( '3 jours après', 'amapress' ),
					'4'   => __( '4 jours après', 'amapress' ),
					'5'   => __( '5 jours après', 'amapress' ),
					'6'   => __( '6 jours après', 'amapress' ),
					'7'   => __( '7 jours après', 'amapress' ),
					'10'  => __( '10 jours après', 'amapress' ),
					'15'  => __( '15 jours après', 'amapress' ),
					'30'  => __( '30 jours après', 'amapress' ),
					'45'  => __( '45 jours après', 'amapress' ),
					'60'  => __( '60 jours après', 'amapress' ),
					'90'  => __( '90 jours après', 'amapress' ),
				),
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
			),
			'subject'     => array(
				'name'        => __( 'Objet', 'amapress' ),
				'type'        => 'text',
				'group'       => __( '2/ Message envoyé', 'amapress' ),
				'desc'        => __( 'Objet du mail envoyé', 'amapress' ),
				'required'    => true,
				'show_column' => false,
			),
			'content'     => array(
				'name'        => __( 'Contenu', 'amapress' ),
				'type'        => 'editor',
				'group'       => __( '2/ Message envoyé', 'amapress' ),
				'desc'        => function ( $option ) {
					return Amapress::getPlaceholdersHelpTable( 'reminder-placeholders',
						[], '' );
				},
				'required'    => true,
				'show_column' => false,
			),
			'queries'     => array(
				'group'       => __( '3/ Destinataires', 'amapress' ),
				'name'        => __( 'Groupes', 'amapress' ),
				'type'        => 'multicheck',
				'desc'        => __( 'Cocher le ou les groupes à inclure.', 'amapress' ),
				'options'     => 'amapress_get_mailinglist_queries',
				'show_column' => false,
			),
			'other_users' => array(
				'group'        => __( '3/ Destinataires', 'amapress' ),
				'name'         => __( 'Amapiens', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Sélectionner un ou plusieurs amapien(s) à inclure.', 'amapress' ),
				'show_column'  => false,
				'required'     => true,
			),
			'raw_users'   => array(
				'group'       => __( 'Membres', 'amapress' ),
				'name'        => __( 'Membres supplémentaires (emails)', 'amapress' ),
				'type'        => 'textarea',
				'desc'        => __( 'Liste d\'adresses emails supplémentaires', 'amapress' ),
				'show_column' => false,
				'searchable'  => true,
			),
		),
	);

	return $entities;
}

add_action( 'amps_reminder', function ( $id ) {
	$post = get_post( $id );
	if ( ! $post ) {
		return;
	}
	$reminder = new AmapressReminder( $post );

	$target_users                 = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $reminder->getMembersIds() ),
		$reminder->getTitle(), "reminder" );
	$target_users['other_emails'] = $reminder->getRawEmails();
	amapress_send_message(
		$reminder->getSubject(),
		$reminder->getContent(),
		'', $target_users );
} );

add_action( 'save_post', function ( $post_id, $post ) {
	/** @var WP_Post $post */
	if ( AmapressReminder::INTERNAL_POST_TYPE != $post->post_type ) {
		return;
	}

	do_action( 'amps_refresh_reminders' );
}, 999, 2 );

add_action( 'amps_refresh_reminders', function () {
	foreach ( AmapressReminder::getAll() as $reminder ) {
		$reminder->refreshCron();
	}
} );

add_action( 'admin_init', function () {
	if ( ! wp_next_scheduled( 'amps_refresh_reminders' ) ) {
		wp_schedule_event( time(), 'daily', 'amps_refresh_reminders' );
	}
} );