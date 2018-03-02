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

function amapress_get_recall_cc_from_option( $option_name ) {
	$ids = Amapress::getOption( $option_name );
	if ( empty( $ids ) ) {
		return '';
	}
	$ret = [];
	foreach (
		get_users( array(
			'include' => $ids
		) ) as $user
	) {
		$amapien = AmapressUser::getBy( $user );
		foreach ( $amapien->getAllEmails() as $email ) {
			$ret[] = $email;
		}
	}

	return $ret;
}

add_action( 'amapress_recall_dispo_panier_intermittent', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}
	$paniers = $dist->getPaniersIntermittents();
	if ( empty( $paniers ) ) {
		return;
	}

	$intermit = amapress_prepare_message_target( "user:amapress_contrat=intermittent", "Les intermittents", "intermittent" );
	amapress_send_message(
		Amapress::getOption( 'intermittence-recall-dispo-mail-subject' ),
		Amapress::getOption( 'intermittence-recall-dispo-mail-content' ),
		'', $intermit, $dist, array(),
		amapress_get_recall_cc_from_option( 'intermittence-recall-dispo-cc' ) );
} );

add_action( 'amapress_recall_validation_panier_intermittent', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}
	$paniers = $dist->getPaniersIntermittents();
	if ( empty( $paniers ) ) {
		return;
	}

	foreach ( $paniers as $panier ) {
		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-recall-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-recall-adherent-mail-content' ),
			$panier->getAdherentId(),
			$panier, null, null,
			amapress_get_recall_cc_from_option( 'intermittence-recall-validation-bcc' ) );
	}

} );

function amapress_intermittence_dispo_recall_options() {
	return array(
		array(
			'id'                  => 'intermittence-dispo-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_dispo_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'intermittence-dispo-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_dispo_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'intermittence-dispo-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_dispo_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'intermittence-recall-dispo-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => 'Il reste encore %%nb-paniers-intermittents%% à échanger',
		),
		array(
			'id'      => 'intermittence-recall-dispo-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nVous recevez ce mail en tant qu'amapien ou intermittent de l'AMAP %%nom_site%%.\n\nIl reste encore %%nb-paniers-intermittents%% proposés à la distribution de %%post:distribution-link%%\n\nSi vous souhaitez en réserver, rendez-vous sur le site %%nom_site%%, sur la page %%lien-liste-paniers%%\n\nEn cas de problème ou de questions sur le fonctionnement des intermittents, veuillez contacter : xxxx.\n\nSi vous avez des questions plus générale sur %%nom_site%%, vous pouvez écrire à xxxx.\n\n%%nom_site%%" ),
		),
		array(
			'id'   => 'intermittence-recall-dispo-cc',
			'name' => amapress__( 'Cc' ),
			'type' => 'multicheck-users',
			'desc' => 'Mails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_intermittence_validation_recall_options() {
	return array(
		array(
			'id'                  => 'intermittence-validation-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_validation_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'intermittence-validation-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_validation_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'intermittence-panier-repris-recall-adherent-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] Demande de reprise %%post:panier%% par %%post:repreneur-nom%% à valider',
		),
		array(
			'id'      => 'intermittence-panier-repris-recall-adherent-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nUne demande a été faite par %%post:repreneur%% pour votre panier (%%post:panier%%) à la distribution %%post:distribution%%\n\nVeuillez valider ou rejeter cette demande dans %%post:mes-echanges%%\n\n%%nom_site%%" ),
		),
		array(
			'id'   => 'intermittence-recall-validation-bcc',
			'name' => amapress__( 'Bcc' ),
			'type' => 'multicheck-users',
			'desc' => 'Mails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}