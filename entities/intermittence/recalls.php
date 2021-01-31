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

function amapress_get_groups_user_ids_from_option( $option_name ) {
	$responsable_ids = [];
	$to_groups       = Amapress::get_array( Amapress::getOption( $option_name ) );
	foreach ( $to_groups as $g ) {
		$args            = wp_parse_args( $g );
		$args['fields']  = 'ids';
		$responsable_ids = array_merge( $responsable_ids,
			get_users( $args ) );
	}

	return $responsable_ids;
}

function amapress_get_recall_cc_from_option( $option_name ) {
	$ids = Amapress::get_array( Amapress::getOption( $option_name ) );
	if ( empty( $ids ) ) {
		$ids = [];
	}
	$ids = array_map( 'intval', $ids );
	$ids = array_merge( $ids, amapress_get_groups_user_ids_from_option( $option_name . '-groups' ) );
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
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}
	$paniers = $dist->getPaniersIntermittentsDispo();
	if ( empty( $paniers ) ) {
		echo '<p>' . __( 'Pas de paniers intermittents disponible', 'amapress' ) . '</p>';

		return;
	}

	$intermit = amapress_prepare_message_target_bcc( 'user:amapress_contrat=intermittent', __( 'Les intermittents', 'amapress' ), "intermittent" );
	amapress_send_message(
		Amapress::getOption( 'intermittence-recall-dispo-mail-subject' ),
		Amapress::getOption( 'intermittence-recall-dispo-mail-content' ),
		'', $intermit, $dist, array(),
		amapress_get_recall_cc_from_option( 'intermittence-recall-dispo-cc' ),
		null,
		AmapressIntermittence_panier::getResponsableIntermittentsReplyto( $dist->getLieuId() ) );
	echo '<p>' . __( 'Email de paniers intermittents disponibles envoyé', 'amapress' ) . '</p>';

} );

add_action( 'amapress_recall_validation_panier_intermittent', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}
	$paniers = $dist->getPaniersIntermittents();
	if ( empty( $paniers ) ) {
		echo '<p>' . __( 'Pas de paniers intermittents en attente de validation', 'amapress' ) . '</p>';

		return;
	}

	$sent_emails = false;
	foreach ( $paniers as $panier ) {
		if ( 'exch_valid_wait' != $panier->getStatus() ) {
			continue;
		}

		foreach ( $panier->getAsk() as $user_id => $user_info ) {
			$panier->setLastAskId( $user_id );

			$subject = Amapress::getOption( 'intermittence-panier-repris-recall-adherent-mail-subject' );
			$content = Amapress::getOption( 'intermittence-panier-repris-recall-adherent-mail-content' );

			$subject = str_replace( '%%attente_depuis%%', date_i18n( 'd/m/Y', $user_info['date'] ), $subject );
			$content = str_replace( '%%attente_depuis%%', date_i18n( 'd/m/Y', $user_info['date'] ), $content );

			amapress_mail_to_current_user(
				$subject,
				$content,
				$panier->getAdherentId(),
				$panier, null, null,
				amapress_get_recall_cc_from_option( 'intermittence-recall-validation-bcc' ),
				AmapressIntermittence_panier::getResponsableIntermittentsReplyto( $panier->getLieuId() ) );
			echo '<p>' . __( 'Email de paniers intermittents en attente à valider envoyé', 'amapress' ) . '</p>';
		}
	}
	if ( ! $sent_emails ) {
		echo '<p>' . __( 'Pas de paniers intermittents en attente de validation', 'amapress' ) . '</p>';
	}

} );

function amapress_intermittence_dispo_recall_options() {
	return array(
		array(
			'id'                  => 'intermittence-dispo-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Panier(s) intermittent(s) encore disponibles', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_dispo_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'intermittence-dispo-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Panier(s) intermittent(s) encore disponibles', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_dispo_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'intermittence-dispo-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Panier(s) intermittent(s) encore disponibles', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_dispo_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'intermittence-dispo-recall-4',
			'name'                => __( 'Rappel 4', 'amapress' ),
			'desc'                => __( 'Panier(s) intermittent(s) encore disponibles', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_dispo_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'intermittence-recall-dispo-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => __( 'Il reste encore %%nb-paniers-intermittents%% panier(s) à échanger à %%post:title%%', 'amapress' ),
		),
		array(
			'id'      => 'intermittence-recall-dispo-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\n\nVous recevez ce mail en tant qu'amapien ou intermittent de l'AMAP %%nom_site%%.\n\nIl reste encore %%nb-paniers-intermittents%% panier(s) proposés à la distribution de %%post:title-link%%\n\nSi vous souhaitez en réserver, rendez-vous sur le site %%nom_site%%, sur la page %%lien-liste-paniers%%\n\nPour vous désinscrire de la liste des intermittents : %%lien_desinscription_intermittent%%\n\nEn cas de problème ou de questions sur le fonctionnement des intermittents, veuillez contacter : [[à remplir]].\n\nSi vous avez des questions plus générale sur %%nom_site%%, vous pouvez écrire à [[à remplir]].\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return AmapressIntermittence_panier::getPlaceholdersHelp( [

				] );
			},
		),
		array(
			'id'           => 'intermittence-recall-dispo-cc',
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

function amapress_intermittence_validation_recall_options() {
	return array(
		array(
			'id'                  => 'intermittence-validation-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Validation panier intermittent en attente', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_validation_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'intermittence-validation-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Validation panier intermittent en attente', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_validation_panier_intermittent',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'intermittence-panier-repris-recall-adherent-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Demande de reprise %%post:panier%% par %%post:repreneur-nom%% à valider depuis le %%attente_depuis%%',
		),
		array(
			'id'      => 'intermittence-panier-repris-recall-adherent-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nUne demande a été faite par %%post:repreneur%% le %%attente_depuis%% pour votre panier (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\nVeuillez valider ou rejeter cette demande dans %%post:mes-echanges%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return AmapressIntermittence_panier::getPlaceholdersHelp( [
					'attente_depuis' => __( 'Date de demande de reprise du panier (par ex, 22/09/2018)', 'amapress' )
				] );
			},
		),
		array(
			'id'           => 'intermittence-recall-validation-bcc',
			'name'         => __( 'Bcc', 'amapress' ),
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