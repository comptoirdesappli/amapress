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

add_action( 'amapress_recall_visite_inscription', function ( $args ) {
	$visite       = new AmapressVisite( $args['id'] );
	$participants = $visite->getParticipantIds();
	if ( empty( $participants ) ) {
		echo '<p>Aucun participants</p>';
		return;
	}

	$participants_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $participants ), "Participants " . $visite->getTitle(), "visite" );
	amapress_send_message(
		Amapress::getOption( 'visite-inscription-recall-mail-subject' ),
		Amapress::getOption( 'visite-inscription-recall-mail-content' ),
		'', $participants_users, $visite, array(),
		amapress_get_recall_cc_from_option( 'visite-inscription-recall-cc' ),
		null, AmapressVisite::getResponsableVisitesReplyto() );
	echo '<p>Email de rappel d\'inscription à une visite envoyé</p>';
} );

/** @return array */
function amapress_get_next_visites_cron() {
	$weeks          = 2;
	$date           = amapress_time();
	$next_week_date = Amapress::add_a_week( amapress_time(), $weeks - 1 );
	$next_visites   = AmapressVisite::get_visites(
		Amapress::start_of_week( Amapress::end_of_week( $date ) ),
		Amapress::end_of_week( $next_week_date )
	);

	$ret = [];
	foreach ( $next_visites as $visite ) {
		$ret[] = [ 'id' => $visite->getID(), 'time' => $visite->getStartDateAndHour(), 'title' => $visite->getTitle() ];
	}

	return $ret;
}

function amapress_visite_inscription_recall_options() {
	return array(
		array(
			'id'                  => 'visite-inscription-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Inscription à une visite',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_visite_inscription',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_visites_cron();
			},
		),
		array(
			'id'                  => 'visite-inscription-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscription à une visite',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_visite_inscription',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_visites_cron();
			},
		),
		array(
			'id'       => 'visite-inscription-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous êtes inscrit à %%post:title%%',
		),
		array(
			'id'      => 'visite-inscription-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nVous êtes inscrit à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
			'desc'    => function ( $option ) {
				return 'Les placeholders suivants sont disponibles:' . AmapressVisite::getPlaceholdersHelp();
			},
		),
		array(
			'id'           => 'visite-inscription-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
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

add_action( 'amapress_recall_visite_available', function ( $args ) {
	$visite       = new AmapressVisite( $args['id'] );
	$participants = $visite->getParticipantIds();
	$producteur   = $visite->getProducteur();
	if ( empty( $producteur ) ) {
		echo '<p>Producteur introuvable</p>';
		return;
	}
	$contrats = $producteur->getContrats();
	$contrat  = array_shift( $contrats );
	if ( empty( $contrat ) ) {
		echo '<p>Producteur sans contrat</p>';
		return;
	}
	$contrat_id = $contrat->ID;

	$non_participants_users = amapress_prepare_message_target_bcc( 'user:amapress_contrat=' . $contrat_id . '&exclude=' . implode( ',', $participants ), "Amapiens ayant un contrat " . $contrat->getTitle(), "visite" );
	amapress_send_message(
		Amapress::getOption( 'visite-available-recall-mail-subject' ),
		Amapress::getOption( 'visite-available-recall-mail-content' ),
		'', $non_participants_users, $visite, array(),
		amapress_get_recall_cc_from_option( 'visite-available-recall-cc' ),
		null, AmapressVisite::getResponsableVisitesReplyto() );
	echo '<p>Email de rappel qu\'une visite a lieu envoyé</p>';
} );
function amapress_visite_available_recall_options() {
	return array(
		array(
			'id'                  => 'visite-available-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Inscription à une visite',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_visite_available',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_visites_cron();
			},
		),
		array(
			'id'                  => 'visite-available-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscription à une visite',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_visite_available',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_visites_cron();
			},
		),
		array(
			'id'       => 'visite-available-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Une viste a lieu bientôt : %%post:title%%',
		),
		array(
			'id'      => 'visite-available-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nUne visite a lieu bientôt : %%post:titre%% (%%post:lien%%)\nPensez à vous inscrire !\n\n%%nom_site%%" ),
			'desc'    => function ( $option ) {
				return 'Les placeholders suivants sont disponibles:' . AmapressVisite::getPlaceholdersHelp();
			},
		),
		array(
			'id'           => 'visite-available-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
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