<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 16/02/2018
 * Time: 05:52
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


add_action( 'amapress_recall_amap_event_inscription', function ( $args ) {
	$amap_event   = new AmapressAmap_event( $args['id'] );
	$participants = $amap_event->getParticipantsIds();
	if ( empty( $participants ) ) {
		return;
	}

	$participants_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $participants ), "Participants " . $amap_event->getTitle(), "amap_event" );
	amapress_send_message(
		Amapress::getOption( 'amap-event-inscription-recall-mail-subject' ),
		Amapress::getOption( 'amap-event-inscription-recall-mail-content' ),
		'', $participants_users, $amap_event, array(),
		amapress_get_recall_cc_from_option( 'amap-event-inscription-recall-cc' ),
		null, AmapressAmap_event::getResponsableAmapEventsReplyto() );
	echo '<p>Email de rappel d\'inscription à un évenement envoyé</p>';
} );

/** @return array */
function amapress_get_next_amap_events_cron() {
	$weeks          = 2;
	$date           = amapress_time();
	$next_week_date = Amapress::add_a_week( amapress_time(), $weeks - 1 );
	$next_events    = AmapressAmap_event::get_next_amap_events(
		Amapress::start_of_week( Amapress::end_of_week( $date ) ),
		Amapress::end_of_week( $next_week_date )
	);

	$ret = [];
	foreach ( $next_events as $amap_event ) {
		$ret[] = [ 'id'    => $amap_event->getID(),
		           'time'  => $amap_event->getStartDateAndHour(),
		           'title' => $amap_event->getTitle()
		];
	}

	return $ret;
}

function amapress_amap_event_inscription_recall_options() {
	return array(
		array(
			'id'                  => 'amap-event-inscription-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Inscription à un évènement AMAP',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amap_event_inscription',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_amap_events_cron();
			},
		),
		array(
			'id'                  => 'amap-event-inscription-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscription à un évènement AMAP',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_amap_event_inscription',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_amap_events_cron();
			},
		),
		array(
			'id'       => 'amap-event-inscription-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous êtes inscrit à %%post:title%%',
		),
		array(
			'id'      => 'amap-event-inscription-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nVous êtes inscrit à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
			'desc'    => function ( $option ) {
				return 'Les placeholders suivants sont disponibles:' . AmapressAmap_event::getPlaceholdersHelp();
			},
		),
		array(
			'id'           => 'amap-event-inscription-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}

add_action( 'amapress_recall_amap_event_available', function ( $args ) {
	$amap_event   = new AmapressAmap_event( $args['id'] );
	$participants = $amap_event->getParticipantsIds();

	$non_participants_users = amapress_prepare_message_target_bcc( 'user:amapress_contrat=active&exclude=' . implode( ',', $participants ), "Amapiens ayant un contrat", "visite" );
	amapress_send_message(
		Amapress::getOption( 'amap-event-available-recall-mail-subject' ),
		Amapress::getOption( 'amap-event-available-recall-mail-content' ),
		'', $non_participants_users, $amap_event, array(),
		amapress_get_recall_cc_from_option( 'amap-event-available-recall-cc' ),
		null, AmapressAmap_event::getResponsableAmapEventsReplyto() );
	echo '<p>Email de rappel de tenue d\'un évenement envoyé</p>';

} );

function amapress_amap_event_available_recall_options() {
	return array(
		array(
			'id'                  => 'amap-event-available-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Inscription à un évènement AMAP',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amap_event_available',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_amap_events_cron();
			},
		),
		array(
			'id'                  => 'amap-event-available-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscription à un évènement AMAP',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_amap_event_available',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_amap_events_cron();
			},
		),
		array(
			'id'       => 'amap-event-available-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Un évènement AMAP a lieu bientôt : %%post:title%%',
		),
		array(
			'id'      => 'amap-event-available-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nUn évènement AMAP a lieu bientôt : %%post:titre%% (%%post:lien%%)\nPensez à vous inscrire !\n\n%%nom_site%%" ),
			'desc'    => function ( $option ) {
				return 'Les placeholders suivants sont disponibles:' . AmapressAmap_event::getPlaceholdersHelp();
			},
		),
		array(
			'id'           => 'amap-event-available-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}