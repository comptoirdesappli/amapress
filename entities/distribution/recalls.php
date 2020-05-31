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
		echo '<p>Distribution introuvable</p>';

		return;
	}
	$gardien_ids = $dist->getGardiensIds( true );
	if ( empty( $gardien_ids ) ) {
		echo '<p>Pas de gardiens</p>';

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
			'title' => 'Amapien',
			'data'  => 'amapien'
		),
		array(
			'title' => 'Paniers',
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
			$replacements['garde_paniers_details'] = 'Vous n\'avez pas de panier à garder.';
		} else {
			$replacements['garde_paniers_details'] = $tbl_style . amapress_get_datatable( 'gardes-paniers',
					$col_gardiens, $data_gardiens,
					$dt_options
				);
		}

		$gardien      = AmapressUser::getBy( $gardien_id );
		$target_users = amapress_prepare_message_target_to( "user:include=" . $gardien_id,
			"Gardiens de paniers de " . $dist->getTitle(), "distribution" );
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
				"Amapiens de " . $dist->getTitle(), "distribution" );
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
	echo '<p>Email aux gardiens de paniers envoyé</p>';
} );

add_action( 'amapress_recall_resp_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

		return;
	}

	$responsable_ids = $dist->getResponsablesIds();
	if ( empty( $responsable_ids ) ) {
		echo '<p>Pas de responsables</p>';

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
		$responsable_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $responsable_ids ), "Responsable de " . $dist->getTitle(), "distribution" );
	} else {
		$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), "Responsable de " . $dist->getTitle(), "distribution" );
	}
	amapress_send_message(
		Amapress::getOption( 'distribution-resp-recall-mail-subject' ),
		Amapress::getOption( 'distribution-resp-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-resp-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto() );
	echo '<p>Email aux responsables de distribution envoyé</p>';

} );
add_action( 'amapress_recall_distrib_emargement', function ( $args ) {
	//distribution-emargement-recall-mail-
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

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
		echo '<p>Pas de responsables</p>';

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
		$responsable_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $responsable_ids ), "Emargement de " . $dist->getTitle(), "distribution" );
	} else {
		$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), "Emargement de " . $dist->getTitle(), "distribution" );
	}
	amapress_send_message(
		Amapress::getOption( 'distribution-emargement-recall-mail-subject' ),
		Amapress::getOption( 'distribution-emargement-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-resp-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto() );

	echo '<p>Email d\'envoi de la liste d\'émargement envoyé</p>';

} );
add_action( 'amapress_recall_distrib_changes', function ( $args ) {
	//distribution-changes-recall-cc
	//distribution-lieu-change-recal-mail-
	//distribution-paniers-change-recall-mail-

	//paniers_modifies

	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

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
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto() );
		echo '<p>Email de changement de lieu envoyé</p>';
	} else {
		echo '<p>Pas de changement de lieu</p>';
	}

	if ( ! empty( $dist->getSpecialHeure_debut() ) || ! empty( $dist->getSpecialHeure_fin() ) ) {
		$amapien_users = amapress_prepare_message_target_bcc( $query, "Amapiens de " . $dist->getTitle(), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-hours-change-recall-mail-subject' ),
			Amapress::getOption( 'distribution-hours-change-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto() );
		echo '<p>Email de changement d\'heure envoyé</p>';
	} else {
		echo '<p>Pas de changement d\'heure</p>';
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
			amapress_get_recall_cc_from_option( 'distribution-changes-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto() );
		echo '<p>Email de changement de distribution de paniers envoyé</p>';
	} else {
		echo '<p>Pas de changement de distribution de paniers</p>';
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
		echo '<p>Email de notification aux amapiens d\'absence de distribution envoyé</p>';
	} else if ( ! $has_dist_at_date ) {
		$subject = Amapress::getOption( 'distribution-moved-recall-mail-subject' );
		$content = Amapress::getOption( 'distribution-moved-recall-mail-content' );

		$amapien_users = amapress_prepare_message_target_bcc( 'post_type=amps_adhesion&amapress_date=active&amapress_lieu=' . $lieu . '&amapress_contrat_inst=' . implode( ',', $contrat_ids ),
			'Amapiens de ' . $lieu_name, "distribution", true );

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
		echo '<p>Email de notification aux amapiens de distribution déplacée envoyé</p>';
	} else {
		echo '<p>Pas de changement de distribution</p>';
	}
} );

add_action( 'amapress_recall_verify_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

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
		echo '<p>Pas de responsables de distribution</p>';

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

	$responsable_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $responsable_ids ), "Vérification de " . $dist->getTitle(), "distribution" );
	amapress_send_message(
		Amapress::getOption( 'distribution-verify-recall-mail-subject' ),
		Amapress::getOption( 'distribution-verify-recall-mail-content' ),
		'', $responsable_users, $dist, $attachments,
		amapress_get_recall_cc_from_option( 'distribution-verify-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto() );
	echo '<p>Email de vérification des listes d\'émargement envoyé</p>';
} );

add_action( 'amapress_recall_missing_resp_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

		return;
	}


	$dist_id     = $dist->ID;
	$contrat_ids = implode( ',', $dist->getContratIds() );
	$query       = "post_type=amps_adhesion&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

	$required_resps_count = AmapressDistributions::get_required_responsables( $dist_id );
	$resps_count          = count( $dist->getResponsablesIds() );
	$missing_resps_count  = $required_resps_count - $resps_count;

	if ( $missing_resps_count <= 0 ) {
		echo '<p>Pas de responsable de distribution manquant</p>';

		return;
	}

	$subject = Amapress::getOption( 'distribution-miss-resps-recall-mail-subject' );
	$content = Amapress::getOption( 'distribution-miss-resps-recall-mail-content' );
	$url     = Amapress::get_inscription_distrib_page_href();
	if ( ! empty( $url ) ) {
		$inscription_link = Amapress::makeLink( $url, 'S\'inscrire comme responsable de distribution' );
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

	$amapien_users = amapress_prepare_message_target_bcc( $query, "Amapiens de " . $dist->getTitle(), "distribution", true );
	amapress_send_message(
		$subject,
		$content,
		'', $amapien_users, $dist, array(),
		amapress_get_recall_cc_from_option( 'distribution-miss-resps-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto()
	);
	echo '<p>Email de responsables de distribution manquants envoyé</p>';
} );

add_action( 'amapress_recall_slots_inscr_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

		return;
	}

	$slots = $dist->getSlotsConf();
	if ( empty( $slots ) ) {
		echo '<p>Pas de créneaux configurés</p>';

		return;
	}

	$subject = Amapress::getOption( 'distribution-slot-inscr-recall-mail-subject' );
	$content = Amapress::getOption( 'distribution-slot-inscr-recall-mail-content' );
	$url     = Amapress::get_inscription_distrib_page_href();
	if ( ! empty( $url ) ) {
		$inscription_link = Amapress::makeLink( $url, 'S\'inscrire à un créneau de distribution' );
	} else {
		$inscription_link = '#page non configurée#';
	}

	$content = str_replace( '%%lien_inscription%%', $inscription_link, $content );

	$dist_without_slots_amapiens_ids = $dist->getWithoutSlotsMemberIds();
	if ( empty( $dist_without_slots_amapiens_ids ) ) {
		echo '<p>Tous les membres sont incrits</p>';

		return;
	}
	$amapien_users = amapress_prepare_message_target_bcc(
		'user:include=' . implode( ',', $dist_without_slots_amapiens_ids ),
		'Amapiens non inscrits aux créneaux de ' . $dist->getTitle(), 'distribution' );
	amapress_send_message(
		$subject,
		$content,
		'', $amapien_users, $dist, array(),
		amapress_get_recall_cc_from_option( 'distribution-slot-inscr-recall-cc' ),
		null, $dist->getResponsablesResponsablesDistributionsReplyto()
	);
	echo '<p>Email d\'inscription aux créneaux de distribution envoyé</p>';
} );


add_action( 'amapress_recall_amapiens_distrib', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

		return;
	}

	if ( Amapress::getOption( 'distribution-amapiens-recall-send-indiv' ) ) {
		$columns_no_price     = [];
		$columns_no_price[]   = array(
			'title' => 'Producteur',
			'data'  => array(
				'_'    => 'prod',
				'sort' => 'prod',
			)
		);
		$columns_no_price[]   = array(
			'title' => 'Description',
			'data'  => array(
				'_'    => 'desc',
				'sort' => 'desc',
			)
		);
		$columns_no_price[]   = array(
			'title' => 'Quantité',
			'data'  => array(
				'_'    => 'fact',
				'sort' => 'fact',
			)
		);
		$columns_with_price   = array_merge( $columns_no_price );
		$columns_with_price[] = array(
			'title' => 'Total',
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

			$data = [];
			foreach ( $adhs as $adh ) {
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
					}
				}
			}

			$replacements = [];

			if ( empty( $data ) ) {
				$replacements['livraison_details_prix'] = 'Vous n\'avez pas de produit à cette livraison';
				$replacements['livraison_details']      = 'Vous n\'avez pas de produit à cette livraison';
			} else {
				$dt_options                             = array(
					'paging'       => false,
					'init_as_html' => true,
					'no_script'    => true,
					'bSort'        => false,
					'empty_desc'   => 'Pas de livraison',
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
				"Amapiens de " . $dist->getTitle(), "distribution" );
			$subject      = Amapress::getOption( 'distribution-amapiens-indiv-recall-mail-subject' );
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
		}
	} else {
		$dist_id     = $dist->ID;
		$contrat_ids = implode( ',', $dist->getContratIds() );
		$query       = "post_type=amps_adhesion&amapress_contrat_inst=$contrat_ids|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date";

		$amapien_users = amapress_prepare_message_target_bcc( $query,
			"Amapiens de " . $dist->getTitle(), "distribution", true );
		amapress_send_message(
			Amapress::getOption( 'distribution-amapiens-recall-mail-subject' ),
			Amapress::getOption( 'distribution-amapiens-recall-mail-content' ),
			'', $amapien_users, $dist, array(),
			amapress_get_recall_cc_from_option( 'distribution-amapiens-recall-cc' ),
			null, $dist->getResponsablesResponsablesDistributionsReplyto()
		);
	}
	echo '<p>Email de rappel de distribution envoyé</p>';
} );

function amapress_distribution_all_amapiens_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-amapiens-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Infos distribution à tous les amapiens',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-amapiens-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Infos distribution à tous les amapiens',
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
			'name'                => 'Rappel 3',
			'desc'                => 'Infos distribution à tous les amapiens',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_amapiens_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-amapiens-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Infos sur %%post:title%%',
		),
		array(
			'id'      => 'distribution-amapiens-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nA la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%, les responsables seront: %%post:liste-resp-phone%%\n\nA cette distribution, suivant vos inscriptions, vous aurez : %%post:liste_contrats%%\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(),
		),
		array(
			'id'           => 'distribution-amapiens-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'distribution-amapiens-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'id'      => 'distribution-amapiens-recall-send-indiv',
			'name'    => 'Envoi individuel',
			'type'    => 'checkbox',
			'desc'    => 'Envoyer le détails des paniers individuellement à chaque amapien (au du rappel collectif ci-dessus)',
			'default' => false,
		),
		array(
			'id'       => 'distribution-amapiens-indiv-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Infos sur %%post:title%%',
		),
		array(
			'id'      => 'distribution-amapiens-indiv-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\n[creneau]Vous avez choisi (ou on vous a affecté) le créneau horaire <strong>%%creneau_horaire%%</strong> pour récupérer vos paniers[/creneau]\n\nA la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%, les responsables seront: %%post:liste-resp-phone%%\n\nA cette distribution, vous aurez :\n\n%%livraison_details%%\n\n%%nom_site%%" ),
			'desc'    =>
				'La syntaxe [creneau]xxx[/creneau] permet de cibler le texte le texte affiché lorsque des créneaux horaires de récupération de paniers sont en place pour la distribution concernée.<br />Les placeholders suivants sont disponibles:' .
				AmapressDistribution::getPlaceholdersHelp(
					[
						'creneau_horaire'        => 'Créneau horaire choisi ou affecté',
						'livraison_details'      => 'Tableau détaillant les paniers livrés (sans montants) à cette distribution pour un amapien donné',
						'livraison_details_prix' => 'Tableau détaillant les paniers livrés (avec montants) à cette distribution pour un amapien donné'
					]
				),
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_missing_responsables_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-miss-resps-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Responsable(s) de distribution manquant(s) à tous les amapiens',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_missing_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-miss-resps-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Responsable(s) de distribution manquant(s) à tous les amapiens',
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
			'name'                => 'Rappel 3',
			'desc'                => 'Responsable(s) de distribution manquant(s) à tous les amapiens',
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
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Responsable(s) manquant(s) à %%post:title%%',
		),
		array(
			'id'      => 'distribution-miss-resps-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nA la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%, il manque %%nb_resp_manquants%% responsable(s) de distribution sur les %%nb_resp_requis%% requis.\n%%lien_inscription%%\nPensez à vous inscrire ! Merci\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(
					[
						'nb_resp_manquants' => 'Nombre de responsables de distribution manquants à la distribution',
						'nb_resp_inscrits'  => 'Nombre de responsables inscrits à la distribution',
						'nb_resp_requis'    => 'Nombre de responsables requis à la distribution',
						'lien_inscription'  => 'Lien "S\'inscrire comme responsable de distribution" vers la page d\'inscription aux distributions',
					]
				),
		),
		array(
			'id'           => 'distribution-miss-resps-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'distribution-miss-resps-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_distribution_slots_inscr_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-slot-inscr-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Inscription des amapiens aux créneaux horaires',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_slots_inscr_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-slot-inscr-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscription des amapiens aux créneaux horaires',
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
			'name'                => 'Rappel 3',
			'desc'                => 'Inscription des amapiens aux créneaux horaires',
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
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous n\'êtes pas encore inscrits aux créneaux de distribution à %%post:title%%',
		),
		array(
			'id'      => 'distribution-slot-inscr-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous n'êtes pas encore inscrits aux créneaux de distribution pour la %%lien_distrib_titre%% qui a lieu de %%post:heure_debut%% à %%post:heure_fin%%.\n%%lien_inscription%%\nPensez à vous inscrire ! Merci\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(
					[
						'lien_inscription' => 'Lien "S\'inscrire à un créneau de distribution" vers la page d\'inscription aux distributions',
					]
				),
		),
		array(
			'id'           => 'distribution-slot-inscr-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'distribution-slot-inscr-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
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
			'desc'                => 'Vérification infos distribution',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_verify_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-verify-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Vérification infos distribution',
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
			'name'                => 'Rappel 3',
			'desc'                => 'Vérification infos distribution',
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
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vérifier les infos de la %%post:title%%',
		),
		array(
			'id'      => 'distribution-verify-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour le collectif,\nPouvez-vous vérifier les infos suivantes de %%lien_distrib_titre_admin%% (vous pouvez modifier les infos depuis le lien précédent):\n-> que cette distribution est bien à %%post:lieu%%\n-> que les contrats suivants seront distribués : %%post:liste-paniers-lien%%\n-> que les responsables %%post:resp-inscrits%%/%%post:resp-requis%% sont : %%post:liste-resp-email-phone%%\n-> que la liste d'émargement ci-jointe est correcte\n\nMerci\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(),
		),
		array(
			'id'      => 'distribution-verify-recall-send-refs',
			'name'    => amapress__( 'Envoyer aux référents' ),
			'type'    => 'checkbox',
			'default' => true,
		),
		array(
			'id'           => 'distribution-verify-recall-to',
			'name'         => amapress__( 'Destinataire(s)' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) destinataire(s)',
		),
		array(
			'id'           => 'distribution-verify-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Destinataire(s) en copie',
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
			'desc'                => 'Responsables de distribution',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_resp_distrib',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-resp-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Responsables de distribution',
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
			'name'                => 'Rappel 3',
			'desc'                => 'Responsables de distribution',
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
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Vous êtes inscrit responsable à %%post:title%%',
		),
		array(
			'id'      => 'distribution-resp-recall-send-bcc',
			'name'    => 'Envoi Bcc',
			'type'    => 'checkbox',
			'desc'    => 'Envoyer le mail avec les responsables en Bcc (au lieu de destinataire direct) : ne permet plus la communication des responsables de la semaine entre eux.',
			'default' => 0,
		),
		array(
			'id'      => 'distribution-resp-recall-send-liste',
			'name'    => 'Liste émargement',
			'type'    => 'checkbox',
			'desc'    => 'Attacher la liste d\'émargement contenant uniquement les contrats qui seront distribués',
			'default' => 1,
		),
		array(
			'id'      => 'distribution-resp-recall-send-liste-tous',
			'name'    => 'Liste émargement complète',
			'type'    => 'checkbox',
			'desc'    => 'Attacher la liste d\'émargement contenant tous les contrats y compris ceux qui ne seront pas distribués',
			'default' => 1,
		),
		array(
			'id'      => 'distribution-resp-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous êtes inscrit responsable à %%lien_distrib_titre%%\n\nVous trouverez ci-joint la liste d'émargement de cette distribution et ci-dessous les instructions du lieu:\n\n%%lieu_instructions%%\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(),
		),
		array(
			'id'           => 'distribution-resp-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'distribution-resp-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
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
			'name'                => 'Rappel 1',
			'desc'                => 'Gardiens de paneirs',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_gardien_paniers',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-gardiens-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Gardiens de paniers',
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
			'name'                => 'Rappel 3',
			'desc'                => 'Gardiens de paniers',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_gardien_paniers',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'name' => 'Email aux gardiens de paniers',
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-gardiens-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Gardes de paniers à %%post:title%%',
		),
		array(
			'id'      => 'distribution-gardiens-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous vous êtes inscrit comme gardiens de paniers à %%lien_distrib_titre%%\n\nVous devrez récupérer les paniers des amapiens suivants:\n%%garde_paniers_details%%\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(
					[ 'garde_paniers_details' => 'Détails des paniers à garder par amapien' ]
				),
		),
		array(
			'name' => 'Email à l\'amapien faisant garder son panier',
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-amapiens-gardiened-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Garde de votre panier par %%gardien%% à %%post:title%%',
		),
		array(
			'id'      => 'distribution-amapiens-gardiened-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVotre panier sera gardé par %%gardien%% (%%gardien_contact%% / %%gardien_message%%) à %%lien_distrib_titre%%\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(
					[
						'gardien'         => 'Nom du gardien de panier choisi',
						'gardien_contact' => 'Coordonnées du gardien de panier choisi',
						'gardien_comment' => 'Message/commentaire du gardien de panier choisi',
					]
				),
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
			'desc'                => 'Envoi liste émargement',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-emargement-recall-2',
			'name'                => 'Rappel 2',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => 'Envoi liste émargement',
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
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => 'Envoi liste émargement',
			'hook_name'           => 'amapress_recall_distrib_emargement',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'distribution-emargement-recall-send-bcc',
			'name'    => 'Envoi Bcc',
			'type'    => 'checkbox',
			'desc'    => 'Envoyer le mail avec les responsables en Bcc (au lieu de destinataire direct) : ne permet plus la communication des responsables de la semaine entre eux.',
			'default' => 0,
		),
		array(
			'id'       => 'distribution-emargement-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Emargement] Liste d\'émargement de %%post:title%%',
		),
		array(
			'id'      => 'distribution-emargement-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-joint la liste d'émargement de cette distribution et ci-dessous les instructions du lieu:\n\n%%lieu_instructions%%\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(),
		),
		array(
			'id'           => 'distribution-emargement-recall-to',
			'name'         => amapress__( 'Destinataire(s)' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) destinataire(s)',
		),
		array(
			'id'           => 'distribution-emargement-recall-cc',
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
				'title' => 'Semaine du ' . date_i18n( 'd/m/Y', $w ) . ' à ' . $lieu->getTitle()
			];
		}
	}

	return $ret;
}

function amapress_distribution_changes_recall_options() {
	return array(
		array(
			'id'      => 'dist_weekday',
			'name'    => 'Jour de distribution',
			'type'    => 'select',
			'desc'    => 'Jour de distribution habituel',
			'options' => [
				'Monday'    => 'Lundi',
				'Tuesday'   => 'Mardi',
				'Wednesday' => 'Mercredi',
				'Thursday'  => 'Jeudi',
				'Friday'    => 'Vendredi',
				'Saturday'  => 'Samedi',
				'Sunday'    => 'Dimanche',
			],
			'default' => 'Thursday',
		),
		array(
			'id'                  => 'distribution-day-recall',
			'name'                => 'Rappel jour de distribution 1',
			'type'                => 'event-scheduler',
			'desc'                => 'Envoyer un rappels si pas de distribution ou changement du jour habituel',
			'hook_name'           => 'amapress_recall_distrib_thisday',
			'hook_args_generator' => 'amapress_generate_weeks_cron',
		),
		array(
			'id'                  => 'distribution-day-recall2',
			'name'                => 'Rappel jour de distribution 2',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => 'Envoyer un rappels si pas de distribution ou changement du jour habituel',
			'hook_name'           => 'amapress_recall_distrib_thisday',
			'hook_args_generator' => 'amapress_generate_weeks_cron',
		),
		array(
			'id'                  => 'distribution-changes-recall-1',
			'name'                => 'Rappel changement distribution 1',
			'desc'                => 'Changement(s) dans les paniers distribués',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_distrib_changes',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-changes-recall-2',
			'name'                => 'Rappel changement distribution 2',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'desc'                => 'Changement(s) dans les paniers distribués',
			'hook_name'           => 'amapress_recall_distrib_changes',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
//		array(
//			'id'                  => 'distribution-changes-recall-3',
//			'name'                => 'Rappel 3',
//			'type'                => 'event-scheduler',
//			'hook_name'           => 'amapress_recall_distrib_changes',
//			'hook_args_generator' => function ( $option ) {
//				return amapress_get_next_distributions_cron();
//			},
//		),
		array(
			'name' => 'En cas de changement de lieu',
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-lieu-change-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Changement de lieu pour %%post:title%%',
		),
		array(
			'id'      => 'distribution-lieu-change-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nChangement de lieu pour %%lien_distrib_titre%%\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(),
		),
		array(
			'name' => 'En cas de changement de d\'horaire',
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-hours-change-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Changement d\'horaire pour %%post:title%%',
		),
		array(
			'id'      => 'distribution-hours-change-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nChangement d'horaire pour %%lien_distrib_titre%%\n\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(),
		),
		array(
			'name' => 'En cas de modification de livraison',
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-paniers-change-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Modification de livraison à %%post:title%%',
		),
		array(
			'id'      => 'distribution-paniers-change-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nLa %%lien_distrib_titre%% comprendra les modifications suivantes :\n%%paniers_modifies%%\n%%nom_site%%" ),
			'desc'    =>
				AmapressDistribution::getPlaceholdersHelp(),
		),
		array(
			'name' => 'En cas d\'abscence de distribution',
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-none-this-day-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] Pas de distribution le %%date%%',
		),
		array(
			'id'      => 'distribution-none-this-day-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nPour rappel : Pas de distribution le %%date%%\n%%nom_site%%" ),
			'desc'    => AmapressDistribution::getPlaceholdersHelp( [
				'date' => 'Date de distribution habituelle (par ex, 22/09/2018)'
			] ),
		),
		array(
			'name' => 'En cas de changement de jour de distribution',
			'type' => 'heading',
		),
		array(
			'id'       => 'distribution-moved-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Rappel] La distribution cette semaine aura lieu le %%jour_date_dist%%',
		),
		array(
			'id'      => 'distribution-moved-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\n\nPour rappel : LLa distribution cette semaine aura lieu le %%jour_date_dist%%\n%%nom_site%%" ),
			'desc'    => AmapressDistribution::getPlaceholdersHelp( [
				'date' => 'Date de distribution habituelle (par ex, 22/09/2018)'
			] ),
		),
		array(
			'name' => 'En copie',
			'type' => 'heading',
		),
		array(
			'id'           => 'distribution-changes-recall-cc',
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


