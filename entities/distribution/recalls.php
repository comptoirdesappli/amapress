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

/** @return array */
function amapress_get_next_distributions_cron() {
	$weeks          = 2;
	$date           = amapress_time();
	$next_week_date = Amapress::add_a_week( amapress_time(), $weeks - 1 );
	$next_distribs  = AmapressDistribution::get_distributions( Amapress::start_of_week( Amapress::end_of_week( $date ) ), Amapress::end_of_week( $next_week_date ) );

	$ret = [];
	foreach ( $next_distribs as $dist ) {
		$ret[] = [ 'id' => $dist->getID(), 'time' => $dist->getStartDateAndHour() ];
	}

	return $ret;
}

add_action( 'amapress_recall_resp_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$responsable_ids = $dist->getResponsablesIds();
	if ( empty( $responsable_ids ) ) {
		return;
	}

	$attachments   = [];
	$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, false, true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.pdf' ) ) );
	$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, true, true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-tous-contrats-' . $dist->getTitle() . '.pdf' ) ) );

	$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), "Responsable de " . $dist->getTitle(), "distribution" );
	amapress_send_message(
		Amapress::getOption( 'distribution-resp-recall-mail-subject' ),
		Amapress::getOption( 'distribution-resp-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-resp-recall-cc' ) );
} );
add_action( 'amapress_recall_distrib_emargement', function ( $args ) {
	//distribution-emargement-recall-mail-
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$responsable_ids = Amapress::get_array( Amapress::getOption( 'distribution-emargement-recall-to' ) );
	if ( empty( $responsable_ids ) ) {
		return;
	}

	$attachments   = [];
	$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, false, true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.pdf' ) ) );
	$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, true, true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-tous-contrats-' . $dist->getTitle() . '.pdf' ) ) );

	$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), "Emargement de " . $dist->getTitle(), "distribution" );
	amapress_send_message(
		Amapress::getOption( 'distribution-emargement-recall-mail-subject' ),
		Amapress::getOption( 'distribution-emargement-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-resp-recall-cc' ) );
} );
add_action( 'amapress_recall_distrib_changes', function ( $args ) {
	//distribution-changes-recall-cc
	//distribution-lieu-change-recal-mail-
	//distribution-paniers-change-recall-mail-

	//paniers_modifies

	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$dist_id     = $dist->ID;
	$contrat_ids = implode( ',', $dist->getContratIds() );
	$query       = "post_type=amps_adhesion&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

	if ( $dist->getLieuSubstitutionId() > 0 && $dist->getLieuSubstitutionId() != $dist->getLieuId() ) {
		$amapien_users = amapress_prepare_message_target_bcc( $query, "Amapiens de " . $dist->getTitle(), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-lieu-change-recall-mail-subject' ),
			Amapress::getOption( 'distribution-lieu-change-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ) );
	}

	$paniers_modifies = array_merge(
		$dist->getCancelledPaniers(),
		$dist->getDelayedToThisPaniers()
	);
	if ( ! empty( $paniers_modifies ) ) {
		$amapien_users = amapress_prepare_message_target_bcc( $query, "Amapiens de " . $dist->getTitle(), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-paniers-change-recall-mail-subject' ),
			Amapress::getOption( 'distribution-paniers-change-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ) );
	}
} );

add_action( 'amapress_recall_distrib_thisday', function ( $args ) {
	$date      = $args['date'];
	$lieu      = $args['lieu'];
	$lieu_name = AmapressLieu_distribution::getBy( $lieu )->getTitle();

	$dists            = AmapressDistribution::get_distributions( Amapress::start_of_week( $date ), Amapress::end_of_week( $date ) );
	$other_dist       = null;
	$has_dist         = false;
	$has_dist_at_date = false;
	$contrat_ids      = [];
	foreach ( $dists as $dist ) {
		if ( $lieu != $dist->getLieuId() ) {
			continue;
		}

		$contrat_ids = array_merge( $dist->getContratIds(), $contrat_ids );
		if ( ! empty( $dist->getContratIds() ) ) {
			$has_dist = true;
			if ( Amapress::start_of_day( $dist->getDate() ) == Amapress::start_of_day( $date ) ) {
				$has_dist_at_date = true;
				$other_dist       = $dist;
			}
		}
	}

	$contrat_ids = array_unique( $contrat_ids );

	if ( ! $has_dist ) {
		$subject = Amapress::getOption( 'distribution-none-this-day-recall-mail-subject' );
		$content = Amapress::getOption( 'distribution-none-this-day-recall-mail-content' );

		$amapien_users = amapress_prepare_message_target_bcc( 'post_type=amps_adhesion&amapress_date=active&amapress_lieu=' . $lieu,
			'Amapiens de ' . $lieu_name, "distribution", true );

		$dt      = date_i18n( 'l d/m/Y' );
		$subject = str_replace( '%%date%%', $dt, $subject );
		$content = str_replace( '%%date%%', $dt, $content );
		amapress_send_message(
			$subject,
			$content,
			'', $amapien_users,
			null, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ) );
	} else if ( ! $has_dist_at_date ) {
		$subject = Amapress::getOption( 'distribution-moved-recall-mail-subject' );
		$content = Amapress::getOption( 'distribution-moved-recall-mail-content' );

		$amapien_users = amapress_prepare_message_target_bcc( 'post_type=amps_adhesion&amapress_date=active&amapress_lieu=' . $lieu . '&amapress_contrat_inst=' . implode( ',', $contrat_ids ),
			'Amapiens de ' . $lieu_name, "distribution", true );

		$dt      = date_i18n( 'l d/m/Y' );
		$subject = str_replace( '%%date%%', $dt, $subject );
		$content = str_replace( '%%date%%', $dt, $content );
		amapress_send_message(
			$subject,
			$content,
			'', $amapien_users,
			$other_dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ) );
	}
} );

add_action( 'amapress_recall_verify_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$responsable_ids = Amapress::get_array( Amapress::getOption( 'distribution-verify-recall-to' ) );
	if ( empty( $responsable_ids ) ) {
//		return;
	}

	$attachments   = [];
	$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, false, true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.pdf' ) ) );
	$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, true, true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-tous-contrats-' . $dist->getTitle() . '.pdf' ) ) );

	$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), "Vérification de " . $dist->getTitle(), "distribution" );
	amapress_send_message(
		Amapress::getOption( 'distribution-verify-recall-mail-subject' ),
		Amapress::getOption( 'distribution-verify-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments );
} );

add_action( 'amapress_recall_amapiens_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$dist_id     = $dist->ID;
	$contrat_ids = implode( ',', $dist->getContratIds() );
	$query       = "post_type=amps_adhesion&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

	$amapien_users = amapress_prepare_message_target_bcc( $query, "Amapiens de " . $dist->getTitle(), "distribution", true );
	amapress_send_message(
		Amapress::getOption( 'distribution-amapiens-recall-mail-subject' ),
		Amapress::getOption( 'distribution-amapiens-recall-mail-content' ),
		'', $amapien_users, $dist, array(),
		amapress_get_recall_cc_from_option( 'distribution-amapiens-recall-cc' )
	);
} );

function amapress_distribution_all_amapiens_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-amapiens-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-amapiens-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-amapiens-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'distribution-amapiens-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] Infos sur %%post:title%%',
		),
		array(
			'id'      => 'distribution-amapiens-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nA la %%lien_distrib_titre%%, les responsables seront: %%post:liste-resp-phone%%\n\nA cette distribution, suivant vos inscriptions, vous aurez : %%post:liste_contrats%%\n\n%%nom_site%%" ),
		),
		array(
			'id'           => 'distribution-amapiens-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_verify_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-verify-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_verify_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-verify-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_verify_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-verify-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_verify_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'distribution-verify-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] Vérifier les infos de la %%post:title%%',
		),
		array(
			'id'      => 'distribution-verify-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour le collectif,\nPouvez-vous vérifier les infos suivantes de %%lien_distrib_titre_admin%% (vous pouvez modifier les infos depuis le lien précédent):\n-> que cette distribution est bien à %%post:lieu%%\n-> que les contrats suivants seront distribués : %%post:liste-paniers-lien%%\n-> que les responsables %%post:resp-inscrits%%/%%post:resp-requis%% sont : %%post:liste-resp-email-phone%%\n-> que la liste d'émargement ci-jointe est correcte\n\nMerci\n\n%%nom_site%%" ),
		),
		array(
			'id'           => 'distribution-verify-recall-to',
			'name'         => amapress__( 'Destinataire(s)' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Destinataire(s)',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_responsable_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-resp-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-resp-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-resp-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'distribution-resp-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] Vous êtes inscrit responsable à %%post:title%%',
		),
		array(
			'id'      => 'distribution-resp-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous êtes inscrit responsable à %%lien_distrib_titre%%\n\nVous trouverez ci-joint la liste d'émargement de cette distribution et ci-dessous les instructions du lieu:\n\n%%lieu_instructions%%\n\n%%nom_site%%" ),
		),
		array(
			'id'           => 'distribution-resp-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_emargement_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-emargement-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-emargement-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-emargement-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'distribution-emargement-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Emargement] Liste d\'émargement de %%post:title%%',
		),
		array(
			'id'      => 'distribution-emargement-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-joint la liste d'émargement de cette distribution et ci-dessous les instructions du lieu:\n\n%%lieu_instructions%%\n\n%%nom_site%%" ),
		),
		array(
			'id'           => 'distribution-emargement-recall-to',
			'name'         => amapress__( 'Envoyer à' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails de tous destinataires. Chaque destinataire recevra la liste d\'émargement de son lieu de distribution.',
		),
		array(
			'id'           => 'distribution-emargement-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_changes_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-day-recall',
			'name'                => 'Jour de distribution habituel',
			'type'                => 'event-scheduler',
			'scheduler_type'      => 'some_day',
			'hook_name'           => 'amapress_recall_distrib_thisday',
			'hook_args_generator' => function ( $option ) {
				$weeks = [
					Amapress::start_of_week( Amapress::add_a_week( amapress_time(), 0 ) ),
					Amapress::start_of_week( Amapress::add_a_week( amapress_time(), 1 ) ),
					Amapress::start_of_week( Amapress::add_a_week( amapress_time(), 2 ) ),
				];
				$ret   = [];
				foreach ( $weeks as $w ) {
					$ret[] = [ 'date' => $w, 'time' => $w ];
				}

				return $ret;
			},
		),
		array(
			'id'                  => 'distribution-changes-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_changes',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-changes-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_changes',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-changes-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_changes',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'name' => 'En cas de changement de lieu',
			'type' => 'heading',
		),
		array(
			'id'      => 'distribution-lieu-change-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] Changement de lieu pour %%post:title%%',
		),
		array(
			'id'      => 'distribution-lieu-change-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nChangement de lieu pour %%lien_distrib_titre%%\n\n%%nom_site%%" ),
		),
		array(
			'name' => 'En cas de modification de livraison',
			'type' => 'heading',
		),
		array(
			'id'      => 'distribution-paniers-change-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] Modification de livraison à %%post:title%%',
		),
		array(
			'id'      => 'distribution-paniers-change-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nLa %%lien_distrib_titre%% comprendra les modifications suivantes :\n%%paniers_modifies%%\n%%nom_site%%" ),
		),
		array(
			'name' => 'En cas d\'abscence de distribution',
			'type' => 'heading',
		),
		array(
			'id'      => 'distribution-none-this-day-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] Pas de distribution aujourd\'hui (%%date%%)',
		),
		array(
			'id'      => 'distribution-none-this-day-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nPour rappel : Pas de distribution aujourd'hui %%date%%\n%%nom_site%%" ),
		),
		array(
			'name' => 'En cas de changement de jour de distribution',
			'type' => 'heading',
		),
		array(
			'id'      => 'distribution-moved-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Rappel] La distribution cette semaine aura lieu le %%jour_date_dist%%',
		),
		array(
			'id'      => 'distribution-moved-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nPour rappel : LLa distribution cette semaine aura lieu le %%jour_date_dist%%\n%%nom_site%%" ),
		),
		array(
			'name' => 'En copie',
			'type' => 'heading',
		),
		'distribution-changes-recall-cc' => array(
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
		),
		array(
			'type' => 'save',
		),
	);
}


