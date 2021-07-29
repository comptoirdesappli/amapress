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
	$weeks          = 6;
	$date           = amapress_time();
	$next_week_date = Amapress::add_a_week( amapress_time(), $weeks - 1 );
	$next_distribs  = AmapressDistribution::get_distributions( Amapress::start_of_week( Amapress::end_of_week( $date ) ), Amapress::end_of_week( $next_week_date ), 'ASC' );

	$ret = [];
	foreach ( $next_distribs as $dist ) {
		$ret[] = [ 'id' => $dist->getID(), 'time' => $dist->getStartDateAndHour(), 'title' => $dist->getTitle() ];
	}

	return $ret;
}

add_action( 'amapress_recall_gardien_paniers', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}
	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$gardien_ids = $dist->getGardiensIds( true );
	if ( empty( $gardien_ids ) ) {
		echo '<p>' . __( 'Pas de gardiens', 'amapress' ) . '</p>';

		return;
	}

	$dt_options   = array(
		'paging'       => false,
		'init_as_html' => true,
		'no_script'    => true,
		'bSort'        => false,
	);
	$tbl_style    = '<style>table, th, td { border-collapse: collapse; border: 1pt solid #000; } .odd {background-color: #eee; }</style>';
	$col_gardiens = array(
		array(
			'title' => __( 'Amapien', 'amapress' ),
			'data'  => 'amapien'
		),
		array(
			'title' => __( 'Paniers', 'amapress' ),
			'data'  => 'paniers'
		),
	);
	foreach ( $gardien_ids as $gardien_id ) {
		if ( empty( $gardien_id ) ) {
			continue;
		}

		$amapien_ids = $dist->getGardiensPaniersAmapiensIds( $gardien_id );

		$replacements  = [];
		$data_gardiens = [];
		foreach ( $dist->getGardiensPaniersAmapiensIds( $gardien_id ) as $amapien_id ) {
			$amapien         = AmapressUser::getBy( $amapien_id );
			$data_gardiens[] = [
				'amapien' => $amapien->getSortableDisplayName() . '(' . $amapien->getContacts() . ')',
				'paniers' => $dist->getPaniersDescription( $amapien_id ),
			];
		}
		if ( empty( $amapien_ids ) ) {
			$replacements['garde_paniers_details'] = __( 'Vous n\'avez pas de panier à garder.', 'amapress' );
		} else {
			$replacements['garde_paniers_details'] = $tbl_style . amapress_get_datatable( 'gardes-paniers',
					$col_gardiens, $data_gardiens,
					$dt_options
				);
		}

		$gardien      = AmapressUser::getBy( $gardien_id );
		$target_users = amapress_prepare_message_target_to( "user:include=" . $gardien_id,
			sprintf( __( 'Gardiens de paniers de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
		$subject      = Amapress::getOption( 'distribution-gardiens-recall-mail-subject' );
		$content      = Amapress::getOption( 'distribution-gardiens-recall-mail-content' );
		foreach ( $replacements as $k => $v ) {
			$subject = str_replace( "%%$k%%", $v, $subject );
			$content = str_replace( "%%$k%%", $v, $content );
		}
		amapress_send_message(
			$subject,
			$content,
			'', $target_users, $dist, array(),
			null, null, $dist->getResponsablesResponsablesDistributionsReplyto( 'distrib-gardien' ) );

		foreach ( $amapien_ids as $amapien_id ) {
			if ( empty( $amapien_id ) ) {
				continue;
			}

			$replacements = [];

			$replacements['gardien']         = $gardien->getDisplayName();
			$replacements['gardien_contact'] = $gardien->getContacts();
			$replacements['gardien_comment'] = $dist->getGardienComment( $gardien_id );

			$target_users = amapress_prepare_message_target_to( "user:include=" . $amapien_id,
				sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
			$subject      = Amapress::getOption( 'distribution-amapiens-gardiened-recall-mail-subject' );
			$content      = Amapress::getOption( 'distribution-amapiens-gardiened-recall-mail-content' );
			foreach ( $replacements as $k => $v ) {
				$subject = str_replace( "%%$k%%", $v, $subject );
				$content = str_replace( "%%$k%%", $v, $content );
			}
			amapress_send_message(
				$subject,
				$content,
				'', $target_users, $dist, array(),
				null, null, $dist->getResponsablesResponsablesDistributionsReplyto( 'distrib-gardien' ) );
		}
	}
	echo '<p>' . __( 'Email aux gardiens de paniers envoyé', 'amapress' ) . '</p>';
} );

add_action( 'amapress_recall_resp_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$responsable_ids = $dist->getResponsablesIds();
	if ( empty( $responsable_ids ) ) {
		echo '<p>' . __( 'Pas de responsables', 'amapress' ) . '</p>';

		return;
	}

	$attachments = [];
	if ( Amapress::getOption( 'distribution-resp-recall-send-liste' ) ) {
		$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
			'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
			getListeEmargement( $dist->ID, false, true ) .
			'</div>',
			strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.pdf' ) ) );
	}
	if ( Amapress::getOption( 'distribution-resp-recall-send-liste-tous' ) ) {
		$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment(
			'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
			getListeEmargement( $dist->ID, true, true ) .
			'</div>',
			strtolower( sanitize_file_name( 'liste-emargement-tous-contrats-' . $dist->getTitle() . '.pdf' ) ) );
	}

	if ( Amapress::getOption( 'distribution-resp-recall-send-bcc' ) ) {
		$responsable_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $responsable_ids ), sprintf( __( 'Responsable de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
	} else {
		$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), sprintf( __( 'Responsable de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
	}
	amapress_send_message(
		Amapress::getOption( 'distribution-resp-recall-mail-subject' ),
		Amapress::getOption( 'distribution-resp-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-resp-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto() );
	echo '<p>' . __( 'Email aux responsables de distribution envoyé', 'amapress' ) . '</p>';

} );
add_action( 'amapress_recall_resp_distrib2', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$responsable_ids = $dist->getResponsablesIds();
	if ( empty( $responsable_ids ) ) {
		echo '<p>' . __( 'Pas de responsables', 'amapress' ) . '</p>';

		return;
	}

	if ( Amapress::getOption( 'distribution-resp-recall-2-send-bcc' ) ) {
		$responsable_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $responsable_ids ), sprintf( __( 'Responsable de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
	} else {
		$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), sprintf( __( 'Responsable de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
	}
	amapress_send_message(
		Amapress::getOption( 'distribution-resp-recall-2-mail-subject' ),
		Amapress::getOption( 'distribution-resp-recall-2-mail-content' ),
		'', $responsable_users, $dist, [],
		amapress_get_recall_cc_from_option( 'distribution-resp-recall-2-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto() );
	echo '<p>' . __( 'Email aux responsables de distribution envoyé', 'amapress' ) . '</p>';

} );
add_action( 'amapress_recall_distrib_emargement', function ( $args ) {
	//distribution-emargement-recall-mail-
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$all_responsable_ids = amapress_get_groups_user_ids_from_option( 'distribution-emargement-recall-to' );
	$responsable_ids     = [];
	foreach ( $all_responsable_ids as $responsable_id ) {
		$user_lieu_ids = AmapressUsers::get_user_lieu_ids( $responsable_id,
			$dist->getDate() );
		if ( empty( $user_lieu_ids ) || in_array( $dist->getLieuId(), $user_lieu_ids ) ) {
			$responsable_ids[] = $responsable_id;
		}
	}
	if ( empty( $responsable_ids ) ) {
		echo '<p>' . __( 'Pas de responsables', 'amapress' ) . '</p>';

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

	if ( Amapress::getOption( 'distribution-emargement-recall-send-bcc' ) ) {
		$responsable_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $responsable_ids ), sprintf( __( 'Emargement de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
	} else {
		$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), sprintf( __( 'Emargement de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
	}
	amapress_send_message(
		Amapress::getOption( 'distribution-emargement-recall-mail-subject' ),
		Amapress::getOption( 'distribution-emargement-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-resp-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto() );

	echo '<p>' . __( 'Email d\'envoi de la liste d\'émargement envoyé', 'amapress' ) . '</p>';

} );
add_action( 'amapress_recall_distrib_changes', function ( $args ) {
	//distribution-changes-recall-cc
	//distribution-lieu-change-recal-mail-
	//distribution-paniers-change-recall-mail-

	//paniers_modifies

	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	$dist_id          = $dist->ID;
	$contrat_ids      = implode( ',', $dist->getContratIds() );
	$dist_date_filter = date_i18n( 'Y-m-d', $dist->getDate() );
	$query            = "post_type=amps_adhesion&amapress_date=$dist_date_filter&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

	if ( $dist->getLieuSubstitutionId() > 0 && $dist->getLieuSubstitutionId() != $dist->getLieuId() ) {
		$amapien_users = amapress_prepare_message_target_bcc( $query, sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-lieu-change-recall-mail-subject' ),
			Amapress::getOption( 'distribution-lieu-change-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto() );
		echo '<p>' . __( 'Email de changement de lieu envoyé', 'amapress' ) . '</p>';
	} else {
		echo '<p>' . __( 'Pas de changement de lieu', 'amapress' ) . '</p>';
	}

	if ( ! empty( $dist->getSpecialHeure_debut() ) || ! empty( $dist->getSpecialHeure_fin() ) ) {
		$amapien_users = amapress_prepare_message_target_bcc( $query, sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-hours-change-recall-mail-subject' ),
			Amapress::getOption( 'distribution-hours-change-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto() );
		echo '<p>' . __( 'Email de changement d\'heure envoyé', 'amapress' ) . '</p>';
	} else {
		echo '<p>' . __( 'Pas de changement d\'heure', 'amapress' ) . '</p>';
	}

	$paniers_modifies = array_merge(
		$dist->getCancelledPaniers(),
		$dist->getDelayedToThisPaniers()
	);
	if ( ! empty( $paniers_modifies ) ) {
		$amapien_users = amapress_prepare_message_target_bcc( $query, sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-paniers-change-recall-mail-subject' ),
			Amapress::getOption( 'distribution-paniers-change-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto() );
		echo '<p>' . __( 'Email de changement de distribution de paniers envoyé', 'amapress' ) . '</p>';
	} else {
		echo '<p>' . __( 'Pas de changement de distribution de paniers', 'amapress' ) . '</p>';
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
			__( 'Amapiens de ', 'amapress' ) . $lieu_name, "distribution", true );

		$dt      = date_i18n( 'l d/m/Y', $date );
		$subject = str_replace( '%%date%%', $dt, $subject );
		$content = str_replace( '%%date%%', $dt, $content );
		amapress_send_message(
			$subject,
			$content,
			'', $amapien_users,
			null, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, AmapressDistribution::getResponsablesRespDistribReplyto( $lieu ) );
		echo '<p>' . __( 'Email de notification aux amapiens d\'absence de distribution envoyé', 'amapress' ) . '</p>';
	} else if ( ! $has_dist_at_date ) {
		$subject = Amapress::getOption( 'distribution-moved-recall-mail-subject' );
		$content = Amapress::getOption( 'distribution-moved-recall-mail-content' );

		$amapien_users = amapress_prepare_message_target_bcc( 'post_type=amps_adhesion&amapress_date=active&amapress_lieu=' . $lieu . '&amapress_contrat_inst=' . implode( ',', $contrat_ids ),
			sprintf( __( 'Amapiens de %s', 'amapress' ), $lieu_name ), "distribution", true );

		$dt      = date_i18n( 'l d/m/Y', $date );
		$subject = str_replace( '%%date%%', $dt, $subject );
		$content = str_replace( '%%date%%', $dt, $content );
		amapress_send_message(
			$subject,
			$content,
			'', $amapien_users,
			$other_dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, AmapressDistribution::getResponsablesRespDistribReplyto( $lieu ) );
		echo '<p>' . __( 'Email de notification aux amapiens de distribution déplacée envoyé', 'amapress' ) . '</p>';
	} else {
		echo '<p>' . __( 'Pas de changement de distribution', 'amapress' ) . '</p>';
	}
} );

add_action( 'amapress_recall_verify_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$responsable_ids = amapress_get_groups_user_ids_from_option( 'distribution-verify-recall-to' );
	if ( Amapress::getOption( 'distribution-verify-recall-send-refs', true ) ) {
		foreach ( $dist->getContrats() as $c ) {
			if ( empty( $c ) ) {
				continue;
			}

			$responsable_ids = array_merge( $responsable_ids, $c->getAllReferentsIds() );
		}
	}

	$responsable_ids = array_unique( $responsable_ids );

	if ( empty( $responsable_ids ) ) {
		echo '<p>' . __( 'Pas de responsables de distribution', 'amapress' ) . '</p>';

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

	$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), sprintf( __( 'Vérification de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
	amapress_send_message(
		Amapress::getOption( 'distribution-verify-recall-mail-subject' ),
		Amapress::getOption( 'distribution-verify-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-verify-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto() );
	echo '<p>' . __( 'Email de vérification des listes d\'émargement envoyé', 'amapress' ) . '</p>';
} );

add_action( 'amapress_recall_missing_resp_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}


	$dist_id     = $dist->ID;
	$contrat_ids = implode( ',', $dist->getContratIds() );
	if ( empty( $contrat_ids ) ) {
		return;
	}
	$dist_date_filter = date_i18n( 'Y-m-d', $dist->getDate() );
	$query            = "post_type=amps_adhesion&amapress_date=$dist_date_filter&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

	$required_resps_count = AmapressDistributions::get_required_responsables( $dist_id );
	$resps_count          = count( $dist->getResponsablesIds() );
	$missing_resps_count  = $required_resps_count - $resps_count;

	if ( $missing_resps_count <= 0 ) {
		echo '<p>' . __( 'Pas de responsable de distribution manquant', 'amapress' ) . '</p>';

		return;
	}

	$subject = Amapress::getOption( 'distribution-miss-resps-recall-mail-subject' );
	$content = Amapress::getOption( 'distribution-miss-resps-recall-l' . $dist->getLieuId() . '-mail-content' );
	if ( empty( $content ) ) {
		$content = Amapress::getOption( 'distribution-miss-resps-recall-mail-content' );
	}
	$url = Amapress::get_inscription_distrib_page_href( $dist->getLieu() );
	if ( ! empty( $url ) ) {
		$inscription_link = Amapress::makeLink( $url, __( 'S\'inscrire comme responsable de distribution', 'amapress' ) );
	} else {
		$inscription_link = '#page non configurée#';
	}

	$subject = str_replace( '%%nb_resp_manquants%%', $missing_resps_count, $subject );
	$subject = str_replace( '%%nb_resp_requis%%', $required_resps_count, $subject );
	$subject = str_replace( '%%nb_resp_inscrits%%', $resps_count, $subject );
	$content = str_replace( '%%nb_resp_manquants%%', $missing_resps_count, $content );
	$content = str_replace( '%%nb_resp_requis%%', $required_resps_count, $content );
	$content = str_replace( '%%nb_resp_inscrits%%', $resps_count, $content );
	$content = str_replace( '%%lien_inscription%%', $inscription_link, $content );

	$amapien_users = amapress_prepare_message_target_bcc( $query, sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution", true );
	amapress_send_message(
		$subject,
		$content,
		'', $amapien_users, $dist, array(),
		amapress_get_recall_cc_from_option( 'distribution-miss-resps-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto()
	);
	echo '<p>' . __( 'Email de responsables de distribution manquants envoyé', 'amapress' ) . '</p>';
} );

add_action( 'amapress_recall_slots_inscr_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$slots = $dist->getSlotsConf();
	if ( empty( $slots ) ) {
		echo '<p>' . __( 'Pas de créneaux configurés', 'amapress' ) . '</p>';

		return;
	}

	$subject = Amapress::getOption( 'distribution-slot-inscr-recall-mail-subject' );
	$content = Amapress::getOption( 'distribution-slot-inscr-recall-mail-content' );
	$url     = Amapress::get_inscription_distrib_page_href( $dist->getLieu() );
	if ( ! empty( $url ) ) {
		$inscription_link = Amapress::makeLink( $url, __( 'S\'inscrire à un créneau de distribution', 'amapress' ) );
	} else {
		$inscription_link = '#page non configurée#';
	}

	$content = str_replace( '%%lien_inscription%%', $inscription_link, $content );

	$dist_without_slots_amapiens_ids = $dist->getWithoutSlotsMemberIds();
	$resp_ids                        = $dist->getResponsablesIds();
	if ( ! empty( $resp_ids ) ) {
		$dist_without_slots_amapiens_ids = array_diff( $dist_without_slots_amapiens_ids, $resp_ids );
	}
	if ( empty( $dist_without_slots_amapiens_ids ) ) {
		echo '<p>' . __( 'Tous les membres sont incrits', 'amapress' ) . '</p>';

		return;
	}
	$amapien_users = amapress_prepare_message_target_bcc(
		'user:include=' . implode( ',', $dist_without_slots_amapiens_ids ),
		sprintf( __( 'Amapiens non inscrits aux créneaux de %s', 'amapress' ), $dist->getTitle() ), 'distribution' );
	amapress_send_message(
		$subject,
		$content,
		'', $amapien_users, $dist, array(),
		amapress_get_recall_cc_from_option( 'distribution-slot-inscr-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto()
	);
	echo '<p>' . __( 'Email d\'inscription aux créneaux de distribution envoyé', 'amapress' ) . '</p>';
} );


add_action( 'amapress_recall_amapiens_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$is_test = isset( $args['is_test'] ) && $args['is_test'];

	if ( Amapress::getOption( 'distribution-amapiens-recall-send-indiv' ) ) {
		$columns_no_price     = [];
		$columns_no_price[]   = array(
			'title' => __( 'Producteur', 'amapress' ),
			'data'  => array(
				'_'    => 'prod',
				'sort' => 'prod',
			)
		);
		$columns_no_price[]   = array(
			'title' => __( 'Description', 'amapress' ),
			'data'  => array(
				'_'    => 'desc',
				'sort' => 'desc',
			)
		);
		$columns_no_price[]   = array(
			'title' => __( 'Quantité', 'amapress' ),
			'data'  => array(
				'_'    => 'fact',
				'sort' => 'fact',
			)
		);
		$columns_with_price   = array_merge( $columns_no_price );
		$columns_with_price[] = array(
			'title' => __( 'Total', 'amapress' ),
			'data'  => array(
				'_'    => 'total_d',
				'sort' => 'total',
			)
		);

		$allow_partial_coadh = Amapress::hasPartialCoAdhesion();
		$date                = $dist->getDate();
		$all_adhs            = AmapressContrats::get_active_adhesions( $dist->getContratIds(),
			null, $dist->getLieuId(), $date, true, false );
		$adhesions           = array_group_by(
			$all_adhs,
			function ( $adh ) use ( $date, $allow_partial_coadh ) {
				/** @var AmapressAdhesion $adh */
				if ( ! $adh->getAdherentId() ) {
					return '';
				}
				$user = $adh->getAdherent()->getUser();
				if ( $allow_partial_coadh ) {
					$user_ids = array_unique( AmapressContrats::get_related_users( $user->ID, false, $date, $adh->getContrat_instanceId() ) );
				} else {
					$user_ids = array_unique( AmapressContrats::get_related_users( $user->ID, false, $date ) );
				}

				return implode( '_', $user_ids );
			} );
		foreach ( $adhesions as $user_ids => $adhs ) {
			$user_ids = explode( '_', $user_ids );
			if ( isset( $_GET['user_id'] ) && ! in_array( $_GET['user_id'], $user_ids ) ) {
				continue;
			}

			$liste_contrats  = [];
			$contenu_paniers = '';
			$data            = [];
			foreach ( $adhs as $adh ) {
				$has_delivery = false;
				/** @var AmapressAdhesion $adh */
				if ( $adh->getContrat_instance()->isPanierVariable() ) {
					$paniers = $adh->getPaniersVariables();
					foreach ( AmapressContrats::get_contrat_quantites( $adh->getContrat_instanceId() ) as $quant ) {
						if ( ! empty( $paniers[ $date ][ $quant->ID ] ) ) {
							$row            = [];
							$row['prod']    = $adh->getContrat_instance()->getModel()->getTitle()
							                  . '<br />'
							                  . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
							$row['desc']    = $quant->getTitle();
							$row['fact']    = $paniers[ $date ][ $quant->ID ];
							$price          = $paniers[ $date ][ $quant->ID ] * $quant->getPrix_unitaire();
							$row['total_d'] = Amapress::formatPrice( $price, true );
							$row['total']   = $price;
							$data[]         = $row;
							$has_delivery   = true;
						}
					}
				} else {
					foreach ( $adh->getContrat_quantites( $date ) as $quant ) {
						$row            = [];
						$row['prod']    = $adh->getContrat_instance()->getModel()->getTitle()
						                  . '<br />'
						                  . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
						$row['desc']    = $quant->getTitleWithoutFactor();
						$row['fact']    = $quant->getFactor();
						$row['total_d'] = Amapress::formatPrice( $quant->getPrice(), true );
						$row['total']   = $quant->getPrice();
						$data[]         = $row;
						$has_delivery   = true;
					}
				}
				if ( $has_delivery ) {
					if ( $adh->getContrat_instance()->hasPanier_CustomContent() ) {
						$panier = AmapressPaniers::getPanierForDist( $dist->getDate(), $adh->getContrat_instanceId() );
						if ( $panier ) {
							$had_content = false;
							$lret        = '<h3>' . $adh->getContrat_instance()->getModelTitle() . '</h3>';
							foreach ( $adh->getContrat_instance()->getContrat_quantites( $dist->getDate() ) as $quant ) {
								$contenu = $panier->getContenu( $quant );
								if ( empty( $contenu ) ) {
									continue;
								}
								$had_content = true;
								$lret        .= '<h4>' . $quant->getTitle() . '</h4>';
								$lret        .= '<div>' . $contenu . '</div>';
							}
							if ( $had_content ) {
								$contenu_paniers .= $lret;
							}
						}
					}
					$liste_contrats[] = $adh->getContrat_instance()->getModelTitleWithSubName();
				}
			}

			$replacements = [];

			$had_deliveries = false;
			if ( empty( $data ) ) {
				if ( Amapress::toBool( Amapress::getOption( 'distribution-amapiens-recall-disable-no-delivery' ) ) ) {
					continue;
				}
				$replacements['livraison_details_prix'] = __( 'Vous n\'avez pas de produit à cette livraison', 'amapress' );
				$replacements['livraison_details']      = __( 'Vous n\'avez pas de produit à cette livraison', 'amapress' );
				$replacements['contenu_paniers']        = '';
				$replacements['liste_contrats']         = '';
			} else {
				$dt_options                             = array(
					'paging'       => false,
					'init_as_html' => true,
					'no_script'    => true,
					'bSort'        => false,
					'empty_desc'   => __( 'Pas de livraison', 'amapress' ),
				);
				$tbl_style                              = '<style>table, th, td { border-collapse: collapse; border: 1pt solid #000; } .odd {background-color: #eee; }</style>';
				$replacements['livraison_details_prix'] = $tbl_style . amapress_get_datatable(
						'dist-recap-' . $dist->ID,
						$columns_with_price, $data,
						$dt_options );
				$replacements['livraison_details']      = $tbl_style . amapress_get_datatable(
						'dist-recap-' . $dist->ID,
						$columns_no_price, $data,
						$dt_options );
				$replacements['contenu_paniers']        = $contenu_paniers;
				$replacements['liste_contrats']         = implode( ', ', $liste_contrats );
				$had_deliveries                         = true;
			}

			$slot_info  = '';
			$slot_confs = $dist->getSlotsConf();
			if ( ! empty( $slot_confs ) ) {
				$slots = [];
				foreach ( $user_ids as $user_id ) {
					$slot = $dist->getSlotInfoForUser( $user_id );
					if ( $slot ) {
						$slots[ strval( $slot['date'] ) ] = $slot;
					}
				}
				$slot_info = implode( ', ', array_map( function ( $s ) {
					return $s['display'];
				}, $slots ) );
			}
			$replacements['creneau_horaire'] = $slot_info;

			$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $user_ids ),
				sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution" );
			$subject      = empty( $data ) ?
				Amapress::getOption( 'distribution-amapiens-indiv-recall-mail-subject-no-delivery' ) :
				Amapress::getOption( 'distribution-amapiens-indiv-recall-mail-subject' );
			$content      = Amapress::getOption( 'distribution-amapiens-indiv-recall-mail-content' );

			if ( ! empty( $slot_info ) ) {
				$content = preg_replace( '/\[\/?creneau\]/', '', $content );
			} else {
				$content = preg_replace( '/\[creneau\].+?\[\/creneau\]/', '', $content );
			}

			foreach ( $replacements as $k => $v ) {
				$subject = str_replace( "%%$k%%", $v, $subject );
				$content = str_replace( "%%$k%%", $v, $content );
			}
			amapress_send_message(
				$subject,
				$content,
				'', $target_users, $dist, array(),
				null, null, $dist->getResponsablesResponsablesDistributionsReplyto() );

			if ( $is_test && $had_deliveries ) {
				break;
			}
		}
	} else {
		$dist_id          = $dist->ID;
		$contrat_ids      = implode( ',', $dist->getContratIds() );
		$dist_date_filter = date_i18n( 'Y-m-d', $dist->getDate() );
		$query            = "post_type=amps_adhesion&amapress_date=$dist_date_filter&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

		$amapien_users = amapress_prepare_message_target_bcc( $query,
			sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-amapiens-recall-mail-subject' ),
			Amapress::getOption( 'distribution-amapiens-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-amapiens-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto()
		);
	}
	echo '<p>' . __( 'Email de rappel de distribution envoyé', 'amapress' ) . '</p>';
} );

function amapress_distribution_all_amapiens_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-amapiens-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Infos distribution à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-amapiens-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Infos distribution à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-amapiens-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Infos distribution à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'name' => __( 'Option d\'envoi', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'      => 'distribution-amapiens-recall-send-indiv',
			'name'    => __( 'Envoi individuel', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Envoyer le détails des paniers individuellement à chaque amapien (voir section <a href="#amps_recall_dist_indiv">"Email individuel à chaque amapien"</a>) au lieu du rappel collectif ci-dessous (section <a href="#amps_recall_dist_all_amapiens">"Email collectif à tous les amapiens"</a>)', 'amapress' ),
			'default' => false,
		),
		array(
			'id'      => 'distribution-amapiens-recall-disable-no-delivery',
			'name'    => __( 'Ignorer sans livraison', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Ne pas envoyer de mail de livraison si pas de livraison', 'amapress' ),
			'default' => false,
		),
		array(
			'name' => __( 'Email collectif à tous les amapiens', 'amapress' ),
			'type' => 'heading',
			'id'   => 'amps_recall_dist_all_amapiens',
		),
		array(
			'id'       => 'distribution-amapiens-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Infos sur %%post:title%%',
		),
		array(
			'id'      => 'distribution-amapiens-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nA la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%, les responsables seront: %%post:liste-resp-phone%%\n\nA cette distribution, suivant vos inscriptions, vous aurez : %%post:liste_contrats%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'id'           => 'distribution-amapiens-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'distribution-amapiens-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'name' => __( 'Email individuel à chaque amapien', 'amapress' ),
			'type' => 'heading',
			'id'   => 'amps_recall_dist_indiv',
		),
		array(
			'id'       => 'distribution-amapiens-indiv-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Infos sur %%post:title%%',
		),
		array(
			'id'       => 'distribution-amapiens-indiv-recall-mail-subject-no-delivery',
			'name'     => __( 'Objet de l\'email (sans livraison)', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
		),
		array(
			'id'      => 'distribution-amapiens-indiv-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\n\n[creneau]Vous avez choisi (ou on vous a affecté) le créneau horaire <strong>%%creneau_horaire%%</strong> pour récupérer vos paniers[/creneau]\n\nA la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%, les responsables seront: %%post:liste-resp-phone%%\n\nA cette distribution, vous aurez :\n\n%%livraison_details%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return __( 'La syntaxe [creneau]xxx[/creneau] permet de cibler le texte le texte affiché lorsque des créneaux horaires de récupération de paniers sont en place pour la distribution concernée.<br />Les placeholders suivants sont disponibles:', 'amapress' ) .
					       AmapressDistribution::getPlaceholdersHelp(
						       [
							       'creneau_horaire'        => __( 'Créneau horaire choisi ou affecté', 'amapress' ),
							       'livraison_details'      => __( 'Tableau détaillant les paniers livrés (sans montants) à cette distribution pour un amapien donné', 'amapress' ),
							       'livraison_details_prix' => __( 'Tableau détaillant les paniers livrés (avec montants) à cette distribution pour un amapien donné', 'amapress' )
						       ]
					       );
				},
		),
		array(
			'type' => 'save',
		),
	);
}

add_action( 'amapress_recall_amapiens_distrib2', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	if ( empty( $dist->getContratIds() ) ) {
		return;
	}

	$is_test = isset( $args['is_test'] ) && $args['is_test'];

	$dist_id          = $dist->ID;
	$contrat_ids      = implode( ',', $dist->getContratIds() );
	$dist_date_filter = date_i18n( 'Y-m-d', $dist->getDate() );
	$query            = "post_type=amps_adhesion&amapress_date=$dist_date_filter&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

	$amapien_users = amapress_prepare_message_target_bcc( $query,
		sprintf( __( 'Amapiens de %s', 'amapress' ), $dist->getTitle() ), "distribution", true );
	amapress_send_message(
		Amapress::getOption( 'distribution-amapiens2-recall-mail-subject' ),
		Amapress::getOption( 'distribution-amapiens2-recall-mail-content' ),
		'', $amapien_users, $dist, array(),
		amapress_get_recall_cc_from_option( 'distribution-amapiens2-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto()
	);
	echo '<p>' . __( 'Email de rappel de distribution envoyé', 'amapress' ) . '</p>';
} );

function amapress_distribution_all_amapiens2_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-amapiens2-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Infos distribution à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amapiens_distrib2',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-amapiens2-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Infos distribution à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_amapiens_distrib2',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-amapiens2-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vos paniers à %%post:title%%',
		),
		array(
			'id'      => 'distribution-amapiens2-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\n\nA la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%, suivant vos inscriptions, vous aurez : %%post:liste_contrats%%\n\nContenu des paniers :\n%%contenu_paniers%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'id'           => 'distribution-amapiens2-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'distribution-amapiens2-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_missing_responsables_recall_options() {
	$res = array(
		array(
			'id'                  => 'distribution-miss-resps-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Responsable(s) de distribution manquant(s) à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_missing_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-miss-resps-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Responsable(s) de distribution manquant(s) à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_missing_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-miss-resps-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Responsable(s) de distribution manquant(s) à tous les amapiens', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_missing_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-miss-resps-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Responsable(s) manquant(s) à %%post:title%%',
		),
	);

	$res[] = array(
		'id'      => 'distribution-miss-resps-recall-mail-content',
		'name'    => __( 'Contenu de l\'email', 'amapress' ),
		'type'    => 'editor',
		'default' => wpautop( __( "Bonjour,\nA la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%, il manque %%nb_resp_manquants%% responsable(s) de distribution sur les %%nb_resp_requis%% requis.\n%%lien_inscription%%\nPensez à vous inscrire ! Merci\n\n%%nom_site%%", 'amapress' ) ),
		'desc'    =>
			function ( $option ) {
				return AmapressDistribution::getPlaceholdersHelp(
					[
						'nb_resp_manquants' => __( 'Nombre de responsables de distribution manquants à la distribution', 'amapress' ),
						'nb_resp_inscrits'  => __( 'Nombre de responsables inscrits à la distribution', 'amapress' ),
						'nb_resp_requis'    => __( 'Nombre de responsables requis à la distribution', 'amapress' ),
						'lien_inscription'  => __( 'Lien "S\'inscrire comme responsable de distribution" vers la page d\'inscription aux distributions', 'amapress' ),
					]
				);
			},
	);

	$lieux = Amapress::get_principal_lieux();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$res[] = array(
				'id'      => 'distribution-miss-resps-recall-l' . $lieu->ID . '-mail-content',
				'name'    => __( 'Contenu de l\'email', 'amapress' ) . ' - ' . $lieu->getTitle(),
				'type'    => 'editor',
				'default' => '',
				'desc'    =>
					function ( $option ) {
						return AmapressDistribution::getPlaceholdersHelp(
							[
								'nb_resp_manquants' => __( 'Nombre de responsables de distribution manquants à la distribution', 'amapress' ),
								'nb_resp_inscrits'  => __( 'Nombre de responsables inscrits à la distribution', 'amapress' ),
								'nb_resp_requis'    => __( 'Nombre de responsables requis à la distribution', 'amapress' ),
								'lien_inscription'  => __( 'Lien "S\'inscrire comme responsable de distribution" vers la page d\'inscription aux distributions', 'amapress' ),
							]
						);
					},
			);
		}
	}

	$res = array_merge( $res, array(
		array(
			'id'           => 'distribution-miss-resps-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'distribution-miss-resps-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'type' => 'save',
		),
	) );

	return $res;
}

function amapress_distribution_slots_inscr_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-slot-inscr-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Inscription des amapiens aux créneaux horaires', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_slots_inscr_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-slot-inscr-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Inscription des amapiens aux créneaux horaires', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_slots_inscr_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-slot-inscr-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Inscription des amapiens aux créneaux horaires', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_slots_inscr_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-slot-inscr-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous n\'êtes pas encore inscrits aux créneaux de distribution à %%post:title%%',
		),
		array(
			'id'      => 'distribution-slot-inscr-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous n'êtes pas encore inscrits aux créneaux de distribution pour la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%.\n%%lien_inscription%%\nPensez à vous inscrire ! Merci\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp(
						[
							'lien_inscription' => __( 'Lien "S\'inscrire à un créneau de distribution" vers la page d\'inscription aux distributions', 'amapress' ),
						]
					);
				},
		),
		array(
			'id'           => 'distribution-slot-inscr-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'distribution-slot-inscr-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Vérification infos distribution', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_verify_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-verify-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Vérification infos distribution', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_verify_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-verify-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Vérification infos distribution', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_verify_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-verify-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vérifier les infos de la %%post:title%%',
		),
		array(
			'id'      => 'distribution-verify-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour le collectif,\nPouvez-vous vérifier les infos suivantes de %%lien_distrib_titre_admin%% (vous pouvez modifier les infos depuis le lien précédent):\n-> que cette distribution est bien à %%post:lieu%%\n-> que les contrats suivants seront distribués : %%post:liste-paniers-lien%%\n-> que les responsables %%post:resp-inscrits%%/%%post:resp-requis%% sont : %%post:liste-resp-email-phone%%\n-> que la liste d'émargement ci-jointe est correcte\n\nMerci\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'id'      => 'distribution-verify-recall-send-refs',
			'name'    => __( 'Envoyer aux référents', 'amapress' ),
			'type'    => 'checkbox',
			'default' => true,
		),
		array(
			'id'           => 'distribution-verify-recall-to',
			'name'         => __( 'Destinataire(s)', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) destinataire(s)', 'amapress' ),
		),
		array(
			'id'           => 'distribution-verify-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Destinataire(s) en copie', 'amapress' ),
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Responsables de distribution', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-resp-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Responsables de distribution', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-resp-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Responsables de distribution', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-resp-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous êtes inscrit responsable à %%post:title%%',
		),
		array(
			'id'      => 'distribution-resp-recall-send-bcc',
			'name'    => __( 'Envoi Bcc', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Envoyer le mail avec les responsables en Bcc (au lieu de destinataire direct) : ne permet plus la communication des responsables de la semaine entre eux.', 'amapress' ),
			'default' => 0,
		),
		array(
			'id'      => 'distribution-resp-recall-send-liste',
			'name'    => __( 'Liste émargement', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Attacher la liste d\'émargement contenant uniquement les contrats qui seront distribués', 'amapress' ),
			'default' => 1,
		),
		array(
			'id'      => 'distribution-resp-recall-send-liste-tous',
			'name'    => __( 'Liste émargement complète', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Attacher la liste d\'émargement contenant tous les contrats y compris ceux qui ne seront pas distribués', 'amapress' ),
			'default' => 1,
		),
		array(
			'id'      => 'distribution-resp-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous êtes inscrit responsable à %%lien_distrib_titre%%\n\nVous trouverez ci-joint la liste d'émargement de cette distribution et ci-dessous les instructions du lieu et des contrats:\n\n%%lieu_instructions%%\n%%paniers_instructions_distribution%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'id'           => 'distribution-resp-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'distribution-resp-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_responsable_recall2_options() {
	return array(
		array(
			'id'                  => 'distribution-resp-recall-2-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Responsables de distribution', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib2',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-resp-recall-2-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Responsables de distribution', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib2',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-resp-recall-2-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Responsables de distribution', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib2',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-resp-recall-2-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous êtes inscrit responsable à %%post:title%%',
		),
		array(
			'id'      => 'distribution-resp-recall-2-send-bcc',
			'name'    => __( 'Envoi Bcc', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Envoyer le mail avec les responsables en Bcc (au lieu de destinataire direct) : ne permet plus la communication des responsables de la semaine entre eux.', 'amapress' ),
			'default' => 0,
		),
		array(
			'id'      => 'distribution-resp-recall-2-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous êtes inscrit responsable à %%lien_distrib_titre%%\n\nVous trouverez ci-joint les instructions du lieu et des contrats:\n\n%%lieu_instructions%%\n%%paniers_instructions_distribution%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'id'           => 'distribution-resp-recall-2-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'distribution-resp-recall-2-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_gardiens_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-gardiens-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Gardiens de paneirs', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_gardien_paniers',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-gardiens-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Gardiens de paniers', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_gardien_paniers',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-gardiens-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Gardiens de paniers', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_gardien_paniers',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'name' => __( 'Email aux gardiens de paniers', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-gardiens-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Gardes de paniers à %%post:title%%',
		),
		array(
			'id'      => 'distribution-gardiens-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous vous êtes inscrit comme gardiens de paniers à %%lien_distrib_titre%%\n\nVous devrez récupérer les paniers des amapiens suivants:\n%%garde_paniers_details%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp(
						[ 'garde_paniers_details' => __( 'Détails des paniers à garder par amapien', 'amapress' ) ]
					);
				},
		),
		array(
			'name' => __( 'Email à l\'amapien faisant garder son panier', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-amapiens-gardiened-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Garde de votre panier par %%gardien%% à %%post:title%%',
		),
		array(
			'id'      => 'distribution-amapiens-gardiened-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVotre panier sera gardé par %%gardien%% (%%gardien_contact%% / %%gardien_message%%) à %%lien_distrib_titre%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp(
						[
							'gardien'         => __( 'Nom du gardien de panier choisi', 'amapress' ),
							'gardien_contact' => __( 'Coordonnées du gardien de panier choisi', 'amapress' ),
							'gardien_comment' => __( 'Message/commentaire du gardien de panier choisi', 'amapress' ),
						]
					);
				},
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Envoi liste émargement', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-emargement-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => __( 'Envoi liste émargement', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-emargement-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => __( 'Envoi liste émargement', 'amapress' ),
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'distribution-emargement-recall-send-bcc',
			'name'    => __( 'Envoi Bcc', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Envoyer le mail avec les responsables en Bcc (au lieu de destinataire direct) : ne permet plus la communication des responsables de la semaine entre eux.', 'amapress' ),
			'default' => 0,
		),
		array(
			'id'       => 'distribution-emargement-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Emargement] Liste d\'émargement de %%post:title%%',
		),
		array(
			'id'      => 'distribution-emargement-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous trouverez ci-joint la liste d'émargement de cette distribution et ci-dessous les instructions du lieu:\n\n%%lieu_instructions%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'id'           => 'distribution-emargement-recall-to',
			'name'         => __( 'Destinataire(s)', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) destinataire(s)', 'amapress' ),
		),
		array(
			'id'           => 'distribution-emargement-recall-cc',
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

function amapress_generate_weeks_cron( $option ) {
	$dist_weekday = Amapress::getOption( 'dist_weekday' );
	$ret          = [];
	foreach ( Amapress::get_lieux() as $lieu ) {
		$weeks = [
			Amapress::start_of_week( Amapress::add_a_week( amapress_time(), 0 ) ),
			Amapress::start_of_week( Amapress::add_a_week( amapress_time(), 1 ) ),
			Amapress::start_of_week( Amapress::add_a_week( amapress_time(), 2 ) ),
		];
		foreach ( $weeks as $w ) {
			$w     = strtotime( 'next ' . $dist_weekday, $w );
			$w     = Amapress::make_date_and_hour( $w, $lieu->getHeure_debut() );
			$ret[] = [
				'date'  => $w,
				'lieu'  => $lieu->ID,
				'time'  => $w,
				'title' => sprintf( __( 'Semaine du %s à %s', 'amapress' ), date_i18n( 'd/m/Y', $w ), $lieu->getTitle() )
			];
		}
	}

	return $ret;
}

function amapress_distribution_changes_recall_options() {
	return array(
		array(
			'id'      => 'dist_weekday',
			'name'    => __( 'Jour de distribution', 'amapress' ),
			'type'    => 'select',
			'desc'    => __( 'Jour de distribution habituel', 'amapress' ),
			'options' => [
				__( 'Monday', 'amapress' )    => __( 'Lundi', 'amapress' ),
				__( 'Tuesday', 'amapress' )   => __( 'Mardi', 'amapress' ),
				__( 'Wednesday', 'amapress' ) => __( 'Mercredi', 'amapress' ),
				__( 'Thursday', 'amapress' )  => __( 'Jeudi', 'amapress' ),
				__( 'Friday', 'amapress' )    => __( 'Vendredi', 'amapress' ),
				__( 'Saturday', 'amapress' )  => __( 'Samedi', 'amapress' ),
				__( 'Sunday', 'amapress' )    => __( 'Dimanche', 'amapress' ),
			],
			'default' => __( 'Thursday', 'amapress' ),
		),
		array(
			'id'                  => 'distribution-day-recall',
			'name'                => __( 'Rappel jour de distribution 1', 'amapress' ),
			'type'                => 'event-scheduler',
			'desc'                => __( 'Envoyer un rappels si pas de distribution ou changement du jour habituel', 'amapress' ),
			'hook_name'           => 'amapress_recall_distrib_thisday',
			'hook_args_generator' => 'amapress_generate_weeks_cron',
		),
		array(
			'id'                  => 'distribution-day-recall2',
			'name'                => __( 'Rappel jour de distribution 2', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => __( 'Envoyer un rappels si pas de distribution ou changement du jour habituel', 'amapress' ),
			'hook_name'           => 'amapress_recall_distrib_thisday',
			'hook_args_generator' => 'amapress_generate_weeks_cron',
		),
		array(
			'id'                  => 'distribution-changes-recall-1',
			'name'                => __( 'Rappel changement distribution 1', 'amapress' ),
			'desc'                => __( 'Changement(s) dans les paniers distribués', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_changes',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-changes-recall-2',
			'name'                => __( 'Rappel changement distribution 2', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => __( 'Changement(s) dans les paniers distribués', 'amapress' ),
			'hook_name'           => 'amapress_recall_distrib_changes',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
//		array(
//			'id'                  => 'distribution-changes-recall-3',
//			'name'                => __('Rappel 3', 'amapress'),
//			'type'                => 'event-scheduler',
//			'hook_name'           => 'amapress_recall_distrib_changes',
//			'hook_args_generator' => function ( $option ) {
//				return amapress_get_next_distributions_cron();
//			},
//		),
		array(
			'name' => __( 'En cas de changement de lieu', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-lieu-change-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Changement de lieu pour %%post:title%%',
		),
		array(
			'id'      => 'distribution-lieu-change-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nChangement de lieu pour %%lien_distrib_titre%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'name' => __( 'En cas de changement de d\'horaire', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-hours-change-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Changement d\'horaire pour %%post:title%%',
		),
		array(
			'id'      => 'distribution-hours-change-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nChangement d'horaire pour %%lien_distrib_titre%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'name' => __( 'En cas de modification de livraison', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-paniers-change-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Modification de livraison à %%post:title%%',
		),
		array(
			'id'      => 'distribution-paniers-change-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\n\nLa %%lien_distrib_titre%% comprendra les modifications suivantes :\n%%paniers_modifies%%\n%%nom_site%%", 'amapress' ) ),
			'desc'    =>
				function ( $option ) {
					return AmapressDistribution::getPlaceholdersHelp();
				},
		),
		array(
			'name' => __( 'En cas d\'abscence de distribution', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-none-this-day-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Pas de distribution le %%date%%',
		),
		array(
			'id'      => 'distribution-none-this-day-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\n\nPour rappel : Pas de distribution le %%date%%\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return AmapressDistribution::getPlaceholdersHelp( [
					'date' => __( 'Date de distribution habituelle (par ex, 22/09/2018)', 'amapress' )
				] );
			},
		),
		array(
			'name' => __( 'En cas de changement de jour de distribution', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-moved-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] La distribution cette semaine aura lieu le %%jour_date_dist%%',
		),
		array(
			'id'      => 'distribution-moved-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\n\nPour rappel : LLa distribution cette semaine aura lieu le %%jour_date_dist%%\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return AmapressDistribution::getPlaceholdersHelp( [
					'date' => __( 'Date de distribution habituelle (par ex, 22/09/2018)', 'amapress' )
				] );
			},
		),
		array(
			'name' => __( 'En copie', 'amapress' ),
			'type' => 'heading',
		),
		array(
			'id'           => 'distribution-changes-recall-cc',
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


