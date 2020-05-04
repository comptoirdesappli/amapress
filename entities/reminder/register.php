<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_reminder' );
function amapress_register_entities_reminder( $entities ) {
	$entities['reminder'] = array(
		'internal_name'    => 'amps_rmd',
		'singular'         => amapress__( 'Rappel libre' ),
		'plural'           => amapress__( 'Rappels libres' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => true,
		'editor'           => false,
		'slug'             => 'rmds',
		'menu_icon'        => 'dashicons-clock',
		'labels'           => array(
			'add_new'      => 'Planifier un nouveau rappel libre',
			'add_new_item' => 'Planifier un rappel libre',
		),
		'views'            => array(
			'remove' => array( 'mine' ),
		),
		'fields'           => array(
			'start_date'  => array(
				'name'     => amapress__( 'Premier rappel' ),
				'type'     => 'date',
				'required' => true,
				'group'    => '1/ Planification',
				'desc'     => 'Date et heure à laquelle commence le premier rappel',
				'time'     => true,
			),
			'end_date'    => array(
				'name'  => amapress__( 'Date de fin' ),
				'type'  => 'date',
				'group' => '1/ Planification',
				'desc'  => 'Date à laquelle finit le rappel',
			),
			'interval'    => array(
				'name'     => amapress__( 'Périodicité' ),
				'type'     => 'select',
				'required' => true,
				'group'    => '1/ Planification',
				'options'  => array(
					'daily'       => 'Journalier',
					'weekly'      => 'Hebdomadaire',
					'monthly'     => 'Mensuel',
					'two_monthly' => 'Tous les deux mois',
					'quarterly'   => 'Trimestriel',
					'half_yearly' => 'Semestriel',
					'yearly'      => 'Annuel',
				)
			),
			'other_days'  => array(
				'name'         => amapress__( 'Rappels supplémentaires' ),
				'type'         => 'select',
				'group'        => '1/ Planification',
				'options'      => array(
					'-1'  => '1 jour avant',
					'-2'  => '2 jours avant',
					'-3'  => '3 jours avant',
					'-4'  => '4 jours avant',
					'-5'  => '5 jours avant',
					'-6'  => '6 jours avant',
					'-7'  => '7 jours avant',
					'-10' => '10 jours avant',
					'-15' => '15 jours avant',
					'-30' => '30 jours avant',
					'-45' => '45 jours avant',
					'-60' => '60 jours avant',
					'-90' => '90 jours avant',
					'1'   => '1 jour après',
					'2'   => '2 jours après',
					'3'   => '3 jours après',
					'4'   => '4 jours après',
					'5'   => '5 jours après',
					'6'   => '6 jours après',
					'7'   => '7 jours après',
					'10'  => '10 jours après',
					'15'  => '15 jours après',
					'30'  => '30 jours après',
					'45'  => '45 jours après',
					'60'  => '60 jours après',
					'90'  => '90 jours après',
				),
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
			),
			'subject'     => array(
				'name'        => amapress__( 'Objet' ),
				'type'        => 'text',
				'group'       => '2/ Message envoyé',
				'desc'        => 'Objet du mail envoyé',
				'required'    => true,
				'show_column' => false,
			),
			'content'     => array(
				'name'        => amapress__( 'Contenu' ),
				'type'        => 'editor',
				'group'       => '2/ Message envoyé',
				'desc'        => Amapress::getPlaceholdersHelpTable( 'reminder-placeholders',
					[], '' ),
				'required'    => true,
				'show_column' => false,
			),
			'queries'     => array(
				'group'       => '3/ Destinataires',
				'name'        => amapress__( 'Groupes' ),
				'type'        => 'multicheck',
				'desc'        => 'Cocher le ou les groupes à inclure.',
				'options'     => 'amapress_get_mailinglist_queries',
				'show_column' => false,
			),
			'other_users' => array(
				'group'        => '3/ Destinataires',
				'name'         => amapress__( 'Amapiens' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Sélectionner un ou plusieurs amapien(s) à inclure.',
				'show_column'  => false,
				'required'     => true,
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

	$target_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $reminder->getMembersIds() ),
		$reminder->getTitle(), "reminder" );
	amapress_send_message(
		$reminder->getSubject(),
		$reminder->getContent(),
		'', $target_users );
} );

add_action( 'save_post', function () {
	do_action( 'amps_refresh_reminders' );
}, 999 );

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