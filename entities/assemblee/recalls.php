<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


add_action( 'amapress_recall_assemblee_generale_inscription', function ( $args ) {
	$assemblee_generale = new AmapressAssemblee_generale( $args['id'] );
	$participants       = $assemblee_generale->getParticipantsIds();
	if ( empty( $participants ) ) {
		return;
	}

	$participants_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $participants ), sprintf( __( 'Participants %s', 'amapress' ), $assemblee_generale->getTitle() ), "assemblee_generale" );
	amapress_send_message(
		Amapress::getOption( 'assemblee-inscription-recall-mail-subject' ),
		Amapress::getOption( 'assemblee-inscription-recall-mail-content' ),
		'', $participants_users, $assemblee_generale, array(),
		amapress_get_recall_cc_from_option( 'assemblee-inscription-recall-cc' ),
		null, AmapressAmap_event::getResponsableAmapEventsReplyto() );
	echo '<p>' . __( 'Email de rappel d\'inscription à une AG envoyé', 'amapress' ) . '</p>';
} );

/** @return array */
function amapress_get_next_assemblee_generales_cron() {
	$weeks          = 2;
	$date           = amapress_time();
	$next_week_date = Amapress::add_a_week( amapress_time(), $weeks - 1 );
	$next_events    = AmapressAssemblee_generale::get_next_assemblees(
		Amapress::start_of_week( Amapress::end_of_week( $date ) ),
		Amapress::end_of_week( $next_week_date )
	);

	$ret = [];
	foreach ( $next_events as $assemblee_generale ) {
		$ret[] = [
			'id'    => $assemblee_generale->getID(),
			'time'  => $assemblee_generale->getStartDateAndHour(),
			'title' => $assemblee_generale->getTitle()
		];
	}

	return $ret;
}

function amapress_assemblee_generale_inscription_recall_options() {
	return array(
		array(
			'id'                  => 'assemblee-inscription-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Inscription à une assemblée générale de l\'AMAP', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_assemblee_generale_inscription',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_assemblee_generales_cron();
			},
		),
		array(
			'id'                  => 'assemblee-inscription-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Inscription à une assemblée générale de l\'AMAP', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_assemblee_generale_inscription',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_assemblee_generales_cron();
			},
		),
		array(
			'id'       => 'assemblee-inscription-recall-mail-subject',
			'name'     => __( 'Sujet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous êtes inscrit à %%post:title%%',
		),
		array(
			'id'      => 'assemblee-inscription-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\n\nVous êtes inscrit à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) . AmapressAssemblee_generale::getPlaceholdersHelp();
			},
		),
		array(
			'id'           => 'assemblee-inscription-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'type' => 'save',
		),
	);
}

add_action( 'amapress_recall_assemblee_generale_available', function ( $args ) {
	$assemblee_generale = new AmapressAssemblee_generale( $args['id'] );
	$participants       = $assemblee_generale->getParticipantsIds();

	$non_participants_users = amapress_prepare_message_target_bcc( 'user:amapress_role=active&exclude=' . implode( ',', $participants ), __( 'Amapiens ayant un contrat', 'amapress' ), "ag" );
	amapress_send_message(
		Amapress::getOption( 'assemblee-available-recall-mail-subject' ),
		Amapress::getOption( 'assemblee-available-recall-mail-content' ),
		'', $non_participants_users, $assemblee_generale, array(),
		amapress_get_recall_cc_from_option( 'assemblee-available-recall-cc' ),
		null, AmapressAmap_event::getResponsableAmapEventsReplyto() );
	echo '<p>' . __( 'Email de rappel de tenue d\'un évenement envoyé', 'amapress' ) . '</p>';

} );

function amapress_assemblee_generale_available_recall_options() {
	return array(
		array(
			'id'                  => 'assemblee-available-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Inscription à une assemblée générale de l\'AMAP', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_assemblee_generale_available',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_assemblee_generales_cron();
			},
		),
		array(
			'id'                  => 'assemblee-available-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Inscription à une assemblée générale de l\'AMAP', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_assemblee_generale_available',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_assemblee_generales_cron();
			},
		),
		array(
			'id'       => 'assemblee-available-recall-mail-subject',
			'name'     => __( 'Sujet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Une assemblée générale de l\'AMAP a lieu bientôt : %%post:title%%',
		),
		array(
			'id'      => 'assemblee-available-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nUne assemblée générale de l\'AMAP a lieu bientôt : %%post:titre%% (%%post:lien%%)\nPensez à vous inscrire !\n\n%%nom_site%%" ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) . AmapressAssemblee_generale::getPlaceholdersHelp();
			},
		),
		array(
			'id'           => 'assemblee-available-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'type' => 'save',
		),
	);
}