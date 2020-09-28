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

	$participants_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $participants ), "Participants " . $assemblee_generale->getTitle(), "assemblee_generale" );
	amapress_send_message(
		Amapress::getOption( 'assemblee-inscription-recall-mail-subject' ),
		Amapress::getOption( 'assemblee-inscription-recall-mail-content' ),
		'', $participants_users, $assemblee_generale, array(),
		amapress_get_recall_cc_from_option( 'assemblee-inscription-recall-cc' ),
		null, AmapressAmap_event::getResponsableAmapEventsReplyto() );
	echo '<p>Email de rappel d\'inscription à une AG envoyé</p>';
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
			'name'                => 'Rappel 1',
			'desc'                => 'Inscription à une assemblée générale de l\'AMAP',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_assemblee_generale_inscription',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_assemblee_generales_cron();
			},
		),
		array(
			'id'                  => 'assemblee-inscription-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscription à une assemblée générale de l\'AMAP',
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
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous êtes inscrit à %%post:title%%',
		),
		array(
			'id'      => 'assemblee-inscription-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nVous êtes inscrit à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' . AmapressAssemblee_generale::getPlaceholdersHelp(),
		),
		array(
			'id'           => 'assemblee-inscription-recall-cc',
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

add_action( 'amapress_recall_assemblee_generale_available', function ( $args ) {
	$assemblee_generale = new AmapressAssemblee_generale( $args['id'] );
	$participants       = $assemblee_generale->getParticipantsIds();

	$non_participants_users = amapress_prepare_message_target_bcc( 'user:amapress_contrat=active&exclude=' . implode( ',', $participants ), "Amapiens ayant un contrat", "ag" );
	amapress_send_message(
		Amapress::getOption( 'assemblee-available-recall-mail-subject' ),
		Amapress::getOption( 'assemblee-available-recall-mail-content' ),
		'', $non_participants_users, $assemblee_generale, array(),
		amapress_get_recall_cc_from_option( 'assemblee-available-recall-cc' ),
		null, AmapressAmap_event::getResponsableAmapEventsReplyto() );
	echo '<p>Email de rappel de tenue d\'un évenement envoyé</p>';

} );

function amapress_assemblee_generale_available_recall_options() {
	return array(
		array(
			'id'                  => 'assemblee-available-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Inscription à une assemblée générale de l\'AMAP',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_assemblee_generale_available',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_assemblee_generales_cron();
			},
		),
		array(
			'id'                  => 'assemblee-available-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscription à une assemblée générale de l\'AMAP',
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
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Une assemblée générale de l\'AMAP a lieu bientôt : %%post:title%%',
		),
		array(
			'id'      => 'assemblee-available-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nUne assemblée générale de l\'AMAP a lieu bientôt : %%post:titre%% (%%post:lien%%)\nPensez à vous inscrire !\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' . AmapressAssemblee_generale::getPlaceholdersHelp(),
		),
		array(
			'id'           => 'assemblee-available-recall-cc',
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