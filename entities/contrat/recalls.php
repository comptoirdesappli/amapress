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
function amapress_get_contrats_cron( $type ) {
	$ret = [];
	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
		switch ( $type ) {
			case 'open':
				if ( $contrat->canSelfSubscribe() ) {
					$ret[] = [
						'id'    => $contrat->getID(),
						'time'  => $contrat->getDate_ouverture(),
						'type'  => $type,
						'title' => 'Ouverture inscriptions (' .
						           date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) .
						           ') - ' . $contrat->getTitle()
					];
				}
				break;
			case 'close':
				if ( $contrat->canSelfSubscribe() ) {
					$ret[] = [
						'id'    => $contrat->getID(),
						'time'  => $contrat->getDate_ouverture(),
						'type'  => $type,
						'title' => 'Clôture inscriptions (' .
						           date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) .
						           ') - ' . $contrat->getTitle()
					];
				}
				break;
			case 'start':
				$ret[] = [
					'id'    => $contrat->getID(),
					'time'  => $contrat->getDate_debut(),
					'type'  => $type,
					'title' => 'Début (' .
					           date_i18n( 'd/m/Y', $contrat->getDate_debut() ) .
					           ') - ' . $contrat->getTitle()
				];
				break;
			case 'end':
				$ret[] = [
					'id'    => $contrat->getID(),
					'time'  => $contrat->getDate_fin(),
					'type'  => $type,
					'title' => 'Fin (' .
					           date_i18n( 'd/m/Y', $contrat->getDate_fin() ) .
					           ') - ' . $contrat->getTitle()
				];
				break;
		}

	}

	return $ret;
}

add_action( 'amapress_recall_contrat_quantites', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );

	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

		return;
	}

	$contrats_by_producteurs = from( $dist->getContrats() )->groupBy( function ( $c ) {
		/** @var AmapressContrat_instance $c */
		if ( empty( $c->getModel() ) ) {
			return null;
		}

		return $c->getModel()->getProducteurId();
	} );

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'distribution-quantites-recall-excl-producteurs' ) );
	$send_to_producteurs      = Amapress::get_array( Amapress::getOption( 'distribution-quantites-recall-send-producteurs' ) );
	$send_referents           = Amapress::getOption( 'distribution-quantites-recall-send-referents' );

	$sent_mails = false;
	foreach ( $contrats_by_producteurs as $producteur_id => $contrats ) {
		if ( isset( $args['prod_id'] ) && $producteur_id != $args['prod_id'] ) {
			continue;
		}

		if ( in_array( $producteur_id, $disabled_for_producteurs ) ) {
			continue;
		}

		$producteur = AmapressProducteur::getBy( $producteur_id );
		if ( empty( $producteur ) ) {
			continue;
		}
		$send_to_producteur    = in_array( $producteur_id, $send_to_producteurs );
		$show_price_modulables = Amapress::getOption( 'distribution-quantites-recall-price-mod', 0 );

		/** @var AmapressContrat_instance $contrat */
		foreach ( $contrats as $contrat ) {
			$replacements = [];

			$replacements['producteur_contact'] = '<div><h5>Contact producteur:</h5>' .
			                                      ( $producteur->getUser() ? $producteur->getUser()->getDisplay(
				                                      array(
					                                      'show_avatar' => 'false',
					                                      'show_tel'    => 'force',
					                                      'show_sms'    => 'force',
					                                      'show_email'  => 'force',
					                                      'show_roles'  => 'false',
				                                      ) ) : '' ) . '</div>';

			$tbl_style                                            = '<style>table, th, td { border-collapse: collapse; border: 1pt solid #000; } .odd {background-color: #eee; }</style>';
			$replacements['producteur_paniers_quantites_columns'] = $tbl_style . amapress_get_contrat_column_quantite_datatables(
					$contrat->ID, $dist->getDate() );
			$replacements['producteur_paniers_quantites']         = $tbl_style . amapress_get_contrat_quantite_datatable(
					$contrat->ID, null,
					$dist->getDate(),
					[
						'show_contact_producteur' => false,
						'show_price'              => $contrat->isPanierVariable() && $show_price_modulables,
						'no_script'               => true,
						'for_placeholder'         => true,
					] );
			$replacements['producteur_paniers_quantites_text']    = amapress_get_contrat_quantite_datatable(
				$contrat->ID, null,
				$dist->getDate(), [
				'show_contact_producteur' => false,
				'show_price'              => $contrat->isPanierVariable() && $show_price_modulables,
				'mode'                    => 'text',
				'no_script'               => true,
				'for_placeholder'         => true,
			] );

			$replacements['producteur_paniers_quantites_prix']       = $tbl_style . amapress_get_contrat_quantite_datatable(
					$contrat->ID, null,
					$dist->getDate(), [
					'show_contact_producteur' => false,
					'show_price'              => true,
					'no_script'               => true,
					'for_placeholder'         => true,
				] );
			$replacements['producteur_paniers_quantites_prix_group'] = $tbl_style . amapress_get_contrat_quantite_datatable(
					$contrat->ID, null,
					$dist->getDate(), [
					'show_contact_producteur' => false,
					'show_price'              => true,
					'no_script'               => true,
					'for_placeholder'         => true,
					'group_by_group'          => true,
				] );
			$replacements['producteur_paniers_quantites_text_prix']  = amapress_get_contrat_quantite_datatable(
				$contrat->ID, null,
				$dist->getDate(), [
				'show_contact_producteur' => false,
				'show_price'              => true,
				'mode'                    => 'text',
				'no_script'               => true,
				'for_placeholder'         => true,
			] );

			$replacements['producteur_paniers_quantites_amapiens']            = $tbl_style . amapress_get_contrat_quantite_datatable(
					$contrat->ID, null,
					$dist->getDate(), [
					'show_contact_producteur' => false,
					'show_price'              => false,
					'show_adherents'          => true,
					'no_script'               => true,
					'for_placeholder'         => true,
				] );
			$replacements['producteur_paniers_quantites_amapiens_prix']       = $tbl_style . amapress_get_contrat_quantite_datatable(
					$contrat->ID, null,
					$dist->getDate(), [
					'show_contact_producteur' => false,
					'show_price'              => true,
					'show_adherents'          => true,
					'no_script'               => true,
					'for_placeholder'         => true,
				] );
			$replacements['producteur_paniers_quantites_amapiens_prix_group'] = $tbl_style . amapress_get_contrat_quantite_datatable(
					$contrat->ID, null,
					$dist->getDate(), [
					'show_contact_producteur' => false,
					'show_price'              => true,
					'show_adherents'          => true,
					'no_script'               => true,
					'for_placeholder'         => true,
					'group_by_group'          => true,
				] );


			$replacements['lien_contrats_quantites'] = Amapress::makeLink(
				admin_url( 'admin.php?page=contrats_quantites_next_distrib&tab=contrat-quant-tab-' . $contrat->ID . '&date=' . date( 'Y-m-d', $dist->getDate() ) )
			);

			$replacements['producteur_nom']      = ( $producteur->getUser() ? $producteur->getUser()->getDisplayName() : '' ) . ' (' . $producteur->getTitle() . ')';
			$replacements['producteur_pseudo']   = ( $producteur->getUser() ? $producteur->getUser()->getUser()->display_name : '' ) . ' (' . $producteur->getTitle() . ')';
			$replacements['producteur_contrats'] = $producteur->getContratsNames();

			$send_title = [];
			if ( $send_to_producteur ) {
				$send_title[] = 'Producteur';
				if ( $send_referents ) {
					$send_title[] = 'Référents';
					$referent_ids = $contrat->getAllReferentsIds();
				} else {
					$referent_ids = [];
				}
				$referent_ids[] = $producteur->getUserId();
			} else {
				$send_title[] = 'Référents';
				$referent_ids = $contrat->getAllReferentsIds();
			}

			$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ),
				implode( ' et ', $send_title ) . $producteur->getTitle(), 'referents' );
			$subject      = Amapress::getOption( 'distribution-quantites-recall-mail-subject' );
			$content      = Amapress::getOption( 'distribution-quantites-recall-mail-content' );
			if ( $contrat->isPanierVariable() ) {
				$content_modulable = Amapress::getOption( 'distribution-quantites-modulable-recall-mail-content' );
				if ( ! empty( trim( strip_tags( $content_modulable ) ) ) ) {
					$content = $content_modulable;
				}
			}
			foreach ( $replacements as $k => $v ) {
				$subject = str_replace( "%%$k%%", $v, $subject );
				$content = str_replace( "%%$k%%", $v, $content );
			}

			$attachments = [];
			foreach ( Amapress::get_array( Amapress::getOption( 'distribution-quantites-recall-xlsx' ) ) as $excel_name ) {
				if ( 'adherents_columns' == $excel_name ) {
					$xl            = amapress_get_contrat_column_quantite( $contrat->ID, $dist->getDate() );
					$attachments[] = Amapress::createXLSXFromPHPExcelAsMailAttachment( $xl['xl'], $xl['filename'] );
				} else {
					$xlsx          = amapress_get_contrat_quantite_xlsx( $contrat->ID, $excel_name, $dist->getDate() );
					$attachments[] = Amapress::createXLSXFromDatatableAsMailAttachment(
						$xlsx['columns'], $xlsx['data'], $xlsx['filename'], $xlsx['title']
					);
				}
			}

			amapress_send_message(
				$subject,
				$content,
				'', $target_users, $dist, $attachments,
				amapress_get_recall_cc_from_option( 'distribution-quantites-recall-cc' ) );
			echo '<p>Email de rappel des quantités aux producteurs envoyé : ' . esc_html( $producteur->getTitle() ) . '</p>';
			$sent_mails = true;
		}
	}

	if ( ! $sent_mails ) {
		echo '<p>Pas de quantités à rappeler à cette distribution</p>';
	}

} );

add_action( 'amapress_recall_contrats_paiements_producteur', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution introuvable</p>';

		return;
	}

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'contrats-liste-paiements-recall-excl-producteurs' ) );

	$sent_mails = false;
	foreach ( $dist->getContrats() as $contrat ) {
		if ( empty( $contrat->getModel() ) ) {
			continue;
		}
		if ( in_array( $contrat->getModel()->getProducteurId(), $disabled_for_producteurs ) ) {
			continue;
		}

		$replacements = [];
		$producteur   = $contrat->getModel()->getProducteur();

		$replacements['lien_contrats_paiements'] = Amapress::makeLink( admin_url( 'admin.php?page=calendar_contrat_paiements&tab=contrat-paiement-tab-' . $contrat->ID ) );

		$replacements['contrat_nom'] = $contrat->getTitle();

		$replacements['producteur_nom']      = ( $producteur->getUser() ? $producteur->getUser()->getDisplayName() : '' ) . ' (' . $producteur->getTitle() . ')';
		$replacements['producteur_pseudo']   = ( $producteur->getUser() ? $producteur->getUser()->getUser()->display_name : '' ) . ' (' . $producteur->getTitle() . ')';
		$replacements['producteur_contrats'] = $producteur->getContratsNames();
		$date_remise                         = from( $contrat->getPaiements_Liste_dates() )
			->orderBy( function ( $d ) {
				return $d;
			} )
			->firstOrDefault( 0, function ( $d ) {
				return $d > Amapress::start_of_week( amapress_time() );
			} );
		if ( $date_remise ) {
			$replacements['prochaine_date_remise_cheques'] = date_i18n( 'd/m/Y', $date_remise );
		} else {
			$replacements['prochaine_date_remise_cheques'] = 'Aucune';
		}

		$attachments = [];
		foreach ( AmapressContrats::get_active_contrat_instances() as $contrat_instance ) {
			if ( empty( $contrat_instance->getModel() ) ) {
				continue;
			}
			if ( $contrat_instance->getModel()->getProducteurId() != $producteur->ID ) {
				continue;
			}

			foreach ( $contrat->getLieuxIds() as $lieu_id ) {
				$format = 'A3';
				$html   = amapress_get_paiement_table_by_dates(
					$contrat_instance->ID,
					$lieu_id,
					array(
						'show_next_distrib'       => false,
						'show_contact_producteur' => false,
						'for_pdf'                 => true,
					) );

				$lieu               = AmapressLieu_distribution::getBy( $lieu_id );
				$date               = date_i18n( 'd-m-Y' );
				$filename_cheques   = strtolower( sanitize_file_name( "reglements-{$contrat_instance->getModelTitle()}-{$lieu->getShortName()}-au-$date" ) );
				$filename_adherents = strtolower( sanitize_file_name( "adherents-{$contrat_instance->getModelTitle()}-{$lieu->getShortName()}-au-$date" ) );
				$title_cheques      = "Règlements - {$contrat_instance->getModelTitle()} - {$lieu->getShortName()}";
				if ( strlen( $title_cheques ) > 27 ) {
					$title_cheques = substr( $title_cheques, 0, 27 ) . '...';
				}
				$title_adherents = "Adhérents - {$contrat_instance->getModelTitle()} - {$lieu->getShortName()}";
				if ( strlen( $title_adherents ) > 27 ) {
					$title_adherents = substr( $title_adherents, 0, 27 ) . '...';
				}

				$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment( $html, $filename_cheques . '.pdf', 'L', $format );
				$attachments[] = Amapress::createXLSXFromHtmlAsMailAttachment( $html, $filename_cheques . '.xlsx', $title_cheques );
				$attachments[] = Amapress::createXLSXFromPostQueryAsMailAttachment(
					'post_type=amps_adhesion&amapress_contrat_inst=' . $contrat_instance->ID . '&amapress_lieu=' . $lieu_id,
					$filename_adherents . '.xlsx', $title_adherents );
			}
		}


		$referent_ids = array_map(
			function ( $r ) {
				return $r['ref_id'];
			}, AmapressContrats::getReferentsForContratInstance( $contrat->ID )
		);

		$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ), "Référents " . $producteur->getTitle(), 'referents' );
		$subject      = Amapress::getOption( 'contrats-liste-paiements-recall-mail-subject' );
		$content      = Amapress::getOption( 'contrats-liste-paiements-recall-mail-content' );
		foreach ( $replacements as $k => $v ) {
			$subject = str_replace( "%%$k%%", $v, $subject );
			$content = str_replace( "%%$k%%", $v, $content );
		}
		amapress_send_message(
			$subject,
			$content,
			'', $target_users, $dist, $attachments,
			amapress_get_recall_cc_from_option( 'contrats-liste-paiements-recall-cc' ) );
		echo '<p>Email de rappel de la liste des règlements envoyé</p>';
		$sent_mails = true;
	}

	if ( ! $sent_mails ) {
		echo '<p>Pas de liste de règlements à rappeler à cette distribution</p>';
	}

	//%%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%
	//contrats-liste-paiements-recall-mail-
} );

add_action( 'amapress_recall_contrat_renew', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>Distribution intouvable</p>';

		return;
	}

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'contrat-renew-recall-excl-producteurs' ) );

	$contrats           = AmapressContrats::get_active_contrat_instances( null, $dist->getDate() );
	$renewable_contrats = array_filter( $contrats, function ( $c ) use ( $disabled_for_producteurs ) {
		/** @var AmapressContrat_instance $c */
		if ( in_array( $c->getModel()->getProducteurId(), $disabled_for_producteurs ) ) {
			return false;
		}

		return $c->canRenew();
	} );
	$expire_delay       = Amapress::getOption( 'contrat-renew-recall-expire-days' );
	$dist_date          = Amapress::end_of_day( $dist->getDate() );
	$near_renew         = array_filter( $renewable_contrats, function ( $c ) use ( $expire_delay, $dist_date ) {
		/** @var AmapressContrat_instance $c */
		return $dist_date <= $c->getDate_fin() && $c->getDate_fin() <= Amapress::add_days( $dist_date, $expire_delay );
	} );
	$to_renew           = array_filter( $renewable_contrats, function ( $c ) use ( $dist ) {
		/** @var AmapressContrat_instance $c */
		return $c->getDate_fin() < Amapress::end_of_day( $dist->getDate() );
	} );
	$renewable_contrats = array_merge( $near_renew, $to_renew );

	if ( empty( $near_renew ) && empty( $to_renew ) ) {
		echo '<p>Pas de contrat à renouveler</p>';

		return;
	}

	$replacements = [];

	$replacements['nb_contrats']            = count( $to_renew ) + count( $near_renew );
	$replacements['nb_renew_contrats']      = count( $to_renew );
	$replacements['nb_near_renew_contrats'] = count( $near_renew );
	$replacements['contrats_to_renew']      = implode( '<br/>', array_map( function ( $c ) use ( $dist ) {
		/** @var AmapressContrat_instance $c */
		return sprintf( '-> Le contrat "%s" est expiré depuis le %s (depuis %d jours)',
			Amapress::makeLink( $c->getAdminEditLink(), $c->getTitle() ),
			date_i18n( 'd/m/Y', $c->getDate_fin() ),
			floor( abs( $dist->getDate() - $c->getDate_fin() ) / 3600 / 24 )
		);
	}, $to_renew ) );
	if ( empty( $replacements['contrats_to_renew'] ) ) {
		$replacements['contrats_to_renew'] = '> aucun';
	}
	$replacements['contrats_near_end'] = implode( '<br/>', array_map( function ( $c ) use ( $dist ) {
		/** @var AmapressContrat_instance $c */
		return sprintf( '-> Le contrat "%s" expire le %s (dans %d jours)',
			Amapress::makeLink( $c->getAdminEditLink(), $c->getTitle() ),
			date_i18n( 'd/m/Y', $c->getDate_fin() ),
			floor( abs( $dist->getDate() - $c->getDate_fin() ) / 3600 / 24 )
		);
	}, $near_renew ) );
	if ( empty( $replacements['contrats_near_end'] ) ) {
		$replacements['contrats_near_end'] = 'aucun';
	}

	$referent_ids = [];
	foreach ( $renewable_contrats as $c ) {
		$referent_ids = array_merge( $referent_ids, $c->getAllReferentsIds() );
	}
	$referent_ids = array_unique( $referent_ids );

	$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ), 'Référents', 'referents' );
	$subject      = Amapress::getOption( 'contrat-renew-recall-mail-subject' );
	$content      = Amapress::getOption( 'contrat-renew-recall-mail-content' );
	foreach ( $replacements as $k => $v ) {
		$subject = str_replace( "%%$k%%", $v, $subject );
		$content = str_replace( "%%$k%%", $v, $content );
	}
	amapress_send_message(
		$subject,
		$content,
		'', $target_users, $dist, array(),
		amapress_get_recall_cc_from_option( 'contrat-renew-recall-cc' ) );
	echo '<p>Email de rappel de contrat à renouveler envoyé</p>';

} );

add_action( 'amapress_recall_contrat_openclose', function ( $args ) {
	$contrat = AmapressContrat_instance::getBy( $args['id'] );
	if ( null == $contrat ) {
		echo '<p>Contrat intouvable</p>';

		return;
	}

	$today = Amapress::start_of_day( amapress_time() );
	if ( Amapress::start_of_day( $contrat->getDate_cloture() ) < $today ) {
		echo '<p>Contrat clos</p>';

		return;
	}

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'contrat-' . $args['type'] . '-recall-excl-producteurs' ) );
	if ( in_array( $contrat->getModel()->getProducteurId(), $disabled_for_producteurs ) ) {
		echo '<p>Producteur exclu</p>';

		return;
	}

	$replacements = [];

	if ( Amapress::start_of_day( $contrat->getDate_ouverture() ) < $today ) {
		$replacements['ouvre_jours'] = 'depuis ' . round( ( amapress_time() - $contrat->getDate_ouverture() ) / ( 24 * HOUR_IN_SECONDS ) ) . ' jour(s)';
		$replacements['ouvre_date']  = 'depuis le ' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() );
	} else {
		$replacements['ouvre_jours'] = 'dans ' . round( ( $contrat->getDate_ouverture() - amapress_time() ) / ( 24 * HOUR_IN_SECONDS ) ) . ' jour(s)';
		$replacements['ouvre_date']  = 'le ' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() );
	}

	if ( Amapress::start_of_day( $contrat->getDate_cloture() ) > $today ) {
		$replacements['ferme_jours'] = 'dans ' . round( ( $contrat->getDate_cloture() - amapress_time() ) / ( 24 * HOUR_IN_SECONDS ) ) . ' jour(s)';
		$replacements['ferme_date']  = 'le ' . date_i18n( 'd/m/Y', $contrat->getDate_cloture() );
	}

	$headers = 'Reply-To: ' . implode( ',', $contrat->getAllReferentsEmails() );

	$user_ids = [];
	switch ( Amapress::getOption( 'contrat-' . $args['type'] . '-recall-targets' ) ) {
		case 'with-contrat':
			$user_ids = get_users( [
				'amapress_contrat' => 'active',
				'fields'           => 'ids'
			] );
			break;
		case 'same-lieu':
			foreach ( $contrat->getLieuxIds() as $lieu_id ) {
				$user_ids = array_merge( $user_ids, get_users( [
					'amapress_lieu' => $lieu_id,
					'fields'        => 'ids'
				] ) );
			}
			break;
		case 'all':
			$user_ids = get_users( [
				'fields' => 'ids'
			] );
			break;
	}
	$user_with_this_contrat = get_users( [
		'amapress_contrat' => $contrat->ID,
		'fields'           => 'ids'
	] );
	if ( ! empty( $user_with_this_contrat ) ) {
		$user_ids = array_diff( $user_ids, $user_with_this_contrat );
	}
	$target_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $user_ids ), 'Amapiens', 'amapiens' );
	$subject      = Amapress::getOption( 'contrat-' . $args['type'] . '-recall-mail-subject' );
	$content      = Amapress::getOption( 'contrat-' . $args['type'] . '-recall-mail-content' );
	foreach ( $replacements as $k => $v ) {
		$subject = str_replace( "%%$k%%", $v, $subject );
		$content = str_replace( "%%$k%%", $v, $content );
	}
	amapress_send_message(
		$subject,
		$content,
		'', $target_users, $contrat, array(),
		amapress_get_recall_cc_from_option( 'contrat-' . $args['type'] . '-recall-cc' ),
		null, $headers
	);
	echo '<p>Email de rappel envoyé</p>';
} );

add_action( 'amapress_recall_contrat_recap_cloture', function ( $args ) {
	$contrat_instance = AmapressContrat_instance::getBy( $args['id'] );

	if ( null == $contrat_instance ) {
		echo '<p>Contrat introuvable</p>';

		return;
	}

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'contrat-recap-cloture-recall-excl-producteurs' ) );
	$send_to_producteurs      = Amapress::get_array( Amapress::getOption( 'contrat-recap-cloture-recall-send-producteurs' ) );
	$send_referents           = Amapress::getOption( 'contrat-recap-cloture-recall-send-referents' );

	$producteur_id = $contrat_instance->getModel() ? $contrat_instance->getModel()->getProducteurId() : 0;

	if ( isset( $args['prod_id'] ) && $producteur_id != $args['prod_id'] ) {
		return;
	}

	if ( in_array( $producteur_id, $disabled_for_producteurs ) ) {
		return;
	}

	$producteur = AmapressProducteur::getBy( $producteur_id );
	if ( empty( $producteur ) ) {
		return;
	}
	$send_to_producteur = in_array( $producteur_id, $send_to_producteurs );

	$replacements                                         = [];
	$replacements['producteur_contact']                   = '<div><h5>Contact producteur:</h5>' .
	                                                        ( $producteur->getUser() ? $producteur->getUser()->getDisplay(
		                                                        array(
			                                                        'show_avatar' => 'false',
			                                                        'show_tel'    => 'force',
			                                                        'show_sms'    => 'force',
			                                                        'show_email'  => 'force',
			                                                        'show_roles'  => 'false',
		                                                        ) ) : '' ) . '</div>';
	$tbl_style                                            = '<style>table, th, td { border-collapse: collapse; border: 1pt solid #000; } .odd {background-color: #eee; }</style>';
	$replacements['producteur_paniers_quantites_columns'] = $tbl_style . amapress_get_contrat_column_quantite_datatables(
			$contrat_instance->ID );
	$replacements['producteur_paniers_quantites']         = $tbl_style . amapress_get_contrat_quantite_datatable(
			$contrat_instance->ID, null,
			null, [
			'show_contact_producteur' => false,
			'show_price'              => $contrat_instance->isPanierVariable(),
			'no_script'               => true,
			'for_placeholder'         => true,
			'show_all_dates'          => true,
		] );

	$replacements['producteur_paniers_quantites_prix']       = $tbl_style . amapress_get_contrat_quantite_datatable(
			$contrat_instance->ID, null,
			null, [
			'show_contact_producteur' => false,
			'show_price'              => true,
			'no_script'               => true,
			'for_placeholder'         => true,
			'show_all_dates'          => true,
		] );
	$replacements['producteur_paniers_quantites_prix_group'] = $tbl_style . amapress_get_contrat_quantite_datatable(
			$contrat_instance->ID, null,
			null, [
			'show_contact_producteur' => false,
			'show_price'              => true,
			'no_script'               => true,
			'for_placeholder'         => true,
			'show_all_dates'          => true,
			'group_by_group'          => true,
		] );

	$replacements['producteur_paniers_quantites_amapiens']            = $tbl_style . amapress_get_contrat_quantite_datatable(
			$contrat_instance->ID, null,
			null, [
			'show_contact_producteur' => false,
			'show_price'              => false,
			'show_adherents'          => true,
			'no_script'               => true,
			'for_placeholder'         => true,
			'show_all_dates'          => true,
		] );
	$replacements['producteur_paniers_quantites_amapiens_prix']       = $tbl_style . amapress_get_contrat_quantite_datatable(
			$contrat_instance->ID, null,
			null, [
			'show_contact_producteur' => false,
			'show_price'              => true,
			'show_adherents'          => true,
			'no_script'               => true,
			'for_placeholder'         => true,
			'show_all_dates'          => true,
		] );
	$replacements['producteur_paniers_quantites_amapiens_prix_group'] = $tbl_style . amapress_get_contrat_quantite_datatable(
			$contrat_instance->ID, null,
			null, [
			'show_contact_producteur' => false,
			'show_price'              => true,
			'show_adherents'          => true,
			'no_script'               => true,
			'for_placeholder'         => true,
			'show_all_dates'          => true,
			'group_by_group'          => true,
		] );

	$replacements['lien_contrats_quantites'] = Amapress::makeLink(
		admin_url( 'admin.php?page=contrats_quantites_next_distrib&tab=contrat-quant-tab-' . $contrat_instance->ID . '&all=&date=first&with_prices=T' )
	);

	$replacements['producteur_nom']      = ( $producteur->getUser() ? $producteur->getUser()->getDisplayName() : '' ) . ' (' . $producteur->getTitle() . ')';
	$replacements['producteur_pseudo']   = ( $producteur->getUser() ? $producteur->getUser()->getUser()->display_name : '' ) . ' (' . $producteur->getTitle() . ')';
	$replacements['producteur_contrats'] = $producteur->getContratsNames();

	$send_title = [];
	if ( $send_to_producteur ) {
		$send_title[] = 'Producteur';
		if ( $send_referents ) {
			$send_title[] = 'Référents';
			$referent_ids = $contrat_instance->getAllReferentsIds();
		} else {
			$referent_ids = [];
		}
		$referent_ids[] = $producteur->getUserId();
	} else {
		$send_title[] = 'Référents';
		$referent_ids = $contrat_instance->getAllReferentsIds();
	}

	$attachments = [];
	foreach ( Amapress::get_array( Amapress::getOption( 'contrat-recap-cloture-xlsx' ) ) as $excel_name ) {
		if ( 'adherents_columns' == $excel_name ) {
			$xl            = amapress_get_contrat_column_quantite( $contrat_instance->ID );
			$attachments[] = Amapress::createXLSXFromPHPExcelAsMailAttachment( $xl['xl'], $xl['filename'] );
		} else {
			$xlsx          = amapress_get_contrat_quantite_xlsx( $contrat_instance->ID, $excel_name );
			$attachments[] = Amapress::createXLSXFromDatatableAsMailAttachment(
				$xlsx['columns'], $xlsx['data'], $xlsx['filename'], $xlsx['title']
			);
		}
	}

	$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ),
		implode( ' et ', $send_title ) . $producteur->getTitle(), 'referents' );
	$subject      = Amapress::getOption( 'contrat-recap-cloture-recall-mail-subject' );
	$content      = Amapress::getOption( 'contrat-recap-cloture-recall-mail-content' );
	foreach ( $replacements as $k => $v ) {
		$subject = str_replace( "%%$k%%", $v, $subject );
		$content = str_replace( "%%$k%%", $v, $content );
	}
	amapress_send_message(
		$subject,
		$content,
		'', $target_users, $contrat_instance, $attachments,
		amapress_get_recall_cc_from_option( 'contrat-recap-cloture-recall-cc' ) );
	echo '<p>Email de rappel récapitulatif des inscriptions envoyé : ' . esc_html( $producteur->getTitle() ) . '</p>';
} );

function amapress_contrat_quantites_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-quantites-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Quantités à livrer',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-quantites-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Quantités à livrer',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-quantites-recall-3',
			'name'                => 'Rappel 3',
			'desc'                => 'Quantités à livrer',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-quantites-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => 'Quantités de la semaine pour %%producteur_nom%% (%%producteur_contrats%%) - %%post:title%%',
		),
		array(
			'id'      => 'distribution-quantites-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-dessous (et à l'adresse suivante: %%lien_contrats_quantites%%) les quantités de la semaine pour %%lien_distribution_titre%%:\n%%producteur_paniers_quantites%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-quants-placeholders', [
				             'producteur_contrats'                              => 'Contrats du producteur',
				             'producteur_nom'                                   => 'Nom du producteur',
				             'producteur_pseudo'                                => 'Pseudo du producteur',
				             'lien_contrats_quantites'                          => 'Lien vers les quantités à la prochaine distribution',
				             'producteur_paniers_quantites_text'                => 'Quantités à la prochaine distribution (en texte)',
				             'producteur_paniers_quantites_text_prix'           => 'Quantités à la prochaine distribution (en texte avec montants)',
				             'producteur_paniers_quantites_columns'             => 'Quantités à la prochaine distribution avec Produits en colonnes',
				             'producteur_paniers_quantites'                     => 'Quantités à la prochaine distribution (en tableau avec/sans montants suivant l\'option Montants pour les paniers modulables)',
				             'producteur_paniers_quantites_prix'                => 'Quantités à la prochaine distribution (en tableau avec montants)',
				             'producteur_paniers_quantites_amapiens'            => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien)',
				             'producteur_paniers_quantites_amapiens_prix'       => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants)',
				             'producteur_paniers_quantites_prix_group'          => 'Quantités à la prochaine distribution (en tableau avec montants et produits groupés)',
				             'producteur_paniers_quantites_amapiens_prix_group' => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants et les produits groupés)',
				             'producteur_contact'                               => 'Coordonnées du producteur',
			             ], null, [], 'recall' ),
		),
		array(
			'id'      => 'distribution-quantites-modulable-recall-mail-content',
			'name'    => 'Contenu de l\'email - Paniers modulables',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-dessous (et à l'adresse suivante: %%lien_contrats_quantites%%) les quantités de la semaine pour %%lien_distribution_titre%%:\n%%producteur_paniers_quantites%%\n\nDétails par amapien:\n%%producteur_paniers_quantites_amapiens%%\n\n%%nom_site%%" ),
			'desc'    => 'Spécifique aux paniers modulables. Si vide, le contenu du mail général sera utlisé.<br/> Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-quants-placeholders', [
				             'producteur_contrats'                              => 'Contrats du producteur',
				             'producteur_nom'                                   => 'Nom du producteur',
				             'producteur_pseudo'                                => 'Pseudo du producteur',
				             'lien_contrats_quantites'                          => 'Lien vers les quantités à la prochaine distribution',
				             'producteur_paniers_quantites_text'                => 'Quantités à la prochaine distribution (en texte)',
				             'producteur_paniers_quantites_text_prix'           => 'Quantités à la prochaine distribution (en texte avec montants)',
				             'producteur_paniers_quantites_columns'             => 'Quantités à la prochaine distribution avec Produits en colonnes',
				             'producteur_paniers_quantites'                     => 'Quantités à la prochaine distribution (en tableau avec/sans montants suivant l\'option Montants pour les paniers modulables)',
				             'producteur_paniers_quantites_prix'                => 'Quantités à la prochaine distribution (en tableau avec montants)',
				             'producteur_paniers_quantites_amapiens'            => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien)',
				             'producteur_paniers_quantites_amapiens_prix'       => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants)',
				             'producteur_paniers_quantites_prix_group'          => 'Quantités à la prochaine distribution (en tableau avec montants et produits groupés)',
				             'producteur_paniers_quantites_amapiens_prix_group' => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants et les produits groupés)',
				             'producteur_contact'                               => 'Coordonnées du producteur',
			             ], null, [], 'recall' ),
		),
		array(
			'id'   => 'distribution-quantites-recall-price-mod',
			'name' => 'Montants paniers modulables',
			'desc' => 'Afficher les montants pour les paniers modulables',
			'type' => 'checkbox',
		),
		array(
			'id'      => 'distribution-quantites-recall-xlsx',
			'name'    => 'Attacher les excels suivants',
			'type'    => 'multi-check',
			'options' => [
				'date'                 => 'Récapitulatif par produit',
				'group_date'           => 'Récapitulatif par groupe produits',
				'adherents_date'       => 'Récapitulatif par adherent par produit',
				'adherents_group_date' => 'Récapitulatif par adherent par groupe produits',
				'adherents_columns'    => 'Récapitulatif par adherent par groupe produits en colonnes',
			],
			'default' => 'all',
		),
		array(
			'id'           => 'distribution-quantites-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'distribution-quantites-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'id'      => 'distribution-quantites-recall-send-referents',
			'name'    => 'Envoi aux référents',
			'type'    => 'checkbox',
			'desc'    => 'Envoyer les quantités à livrer aux référents',
			'default' => 1,
		),
		array(
			'id'        => 'distribution-quantites-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs exclus',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Désactiver les rappels pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'id'        => 'distribution-quantites-recall-send-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Envoi Producteurs',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Envoyer les rappels au producteur (par défaut uniquement aux référents) pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_contrat_paiements_recall_options() {
	return array(
		array(
			'id'                  => 'contrats-liste-paiements-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Liste des règlements',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrats-liste-paiements-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Liste des règlements',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrats-liste-paiements-recall-3',
			'name'                => 'Rappel 3',
			'desc'                => 'Liste des règlements',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'contrats-liste-paiements-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Chèques au producteur] Liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%',
		),
		array(
			'id'      => 'contrats-liste-paiements-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-joint la liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-paiements-placeholders', [
				             'prochaine_date_remise_cheques' => 'Prochaine date de remise des chèques',
				             'producteur_contrats'           => 'Contrats du producteur',
				             'producteur_nom'                => 'Nom du producteur',
				             'producteur_pseudo'             => 'Pseudo du producteur',
				             'contrat_nom'                   => 'Nom du contrat',
				             'lien_contrats_paiements'       => 'Lien vers la liste des chèques à remettre au producteur',
			             ], null, [], 'recall' ),

		),
		array(
			'id'           => 'contrats-liste-paiements-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'contrats-liste-paiements-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'id'        => 'contrats-liste-paiements-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Désactiver les rappels pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_contrat_renew_recall_options() {
	return array(
		array(
			'id'                  => 'contrat-renew-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Contrats à renouveler',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_renew',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrat-renew-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Contrats à renouveler',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_contrat_renew',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrat-renew-recall-3',
			'name'                => 'Rappel 3',
			'desc'                => 'Contrats à renouveler',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_contrat_renew',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'contrat-renew-recall-expire-days',
			'name'    => 'Délai d\'expiration',
			'type'    => 'number',
			'desc'    => 'Prévenir x jours avant la fin d\'un contrat',
			'default' => 45,
		),
		array(
			'id'       => 'contrat-renew-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => '%%nb_contrats%% contrats à renouveler',
		),
		array(
			'id'      => 'contrat-renew-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nLes contrats suivants sont à renouveler:\n%%contrats_to_renew%%\n\nLes contrats suivants seront bientôt à renouveler:\n%%contrats_near_end%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-renew-placeholders', [
				             'contrats_to_renew'      => 'Contrats à renouveler',
				             'contrats_near_end'      => 'Contrats proches de la fin',
				             'nb_contrats'            => 'Nombre de contrats à renouveler ou proches de la fin',
				             'nb_renew_contrats'      => 'Nombre de contrats à renouveler',
				             'nb_near_renew_contrats' => 'Nombre de contrats proches de la fin',
			             ], null, [], 'recall' ),
		),
		array(
			'id'           => 'contrat-renew-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'contrat-renew-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'id'        => 'contrat-renew-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Désactiver les rappels pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_contrat_open_recall_options() {
	return array(
		array(
			'id'                  => 'contrat-open-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Contrats ouverts à l\'inscription',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'open' );
			},
		),
		array(
			'id'                  => 'contrat-open-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Contrats ouverts à l\'inscription',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'open' );
			},
		),
		array(
			'id'                  => 'contrat-open-recall-3',
			'name'                => 'Rappel 3',
			'desc'                => 'Contrats ouverts à l\'inscription',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'open' );
			},
		),
		array(
			'id'      => 'contrat-open-recall-targets',
			'name'    => 'Destinataires',
			'type'    => 'radio',
			'options' => [
				'with-contrat' => 'Amapiens avec contrat',
				'same-lieu'    => 'Amapiens des lieux de distributions de ce contrat',
				'all'          => 'Tous les amapiens',
			],
			'default' => 'all',
		),
		array(
			'id'       => 'contrat-open-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => 'Inscriptions %%contrat_type_complet%% - ouverture préinscription %%ouvre_date%%',
		),
		array(
			'id'      => 'contrat-open-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nPour le contrat %%contrat_titre_complet%% les inscriptions sont ouvertes %%ouvre_date%%\n et fermeront %%ferme_date%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-open-placeholders', [
				             'ouvre_jours' => 'Ouverture en jours: "depuis/dans X jours"',
				             'ouvre_date'  => 'Ouverture date : "depuis le/le JJ/MM/AAAA"',
				             'ferme_jours' => 'Clôture en jours: "dans X jours"',
				             'ferme_date'  => 'Clôture date : "le JJ/MM/AAAA"',
			             ], null, [], 'recall' ),
		),
		array(
			'id'           => 'contrat-open-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'contrat-open-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'id'        => 'contrat-open-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Désactiver les rappels pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_contrat_close_recall_options() {
	return array(
		array(
			'id'                  => 'contrat-close-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Contrats bientôt fermés à l\'inscription',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'close' );
			},
		),
		array(
			'id'                  => 'contrat-close-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Contrats bientôt fermés à l\'inscription',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'close' );
			},
		),
		array(
			'id'                  => 'contrat-close-recall-3',
			'name'                => 'Rappel 3',
			'desc'                => 'Contrats bientôt fermés à l\'inscription',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'close' );
			},
		),
		array(
			'id'      => 'contrat-close-recall-targets',
			'name'    => 'Destinataires',
			'type'    => 'radio',
			'options' => [
				'with-contrat' => 'Amapiens avec contrat',
				'same-lieu'    => 'Amapiens des lieux de distributions de ce contrat',
				'all'          => 'Tous les amapiens',
			],
			'default' => 'all',
		),
		array(
			'id'       => 'contrat-close-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => 'Inscriptions %%contrat_type_complet%% - clôture %%ferme_date%%',
		),
		array(
			'id'      => 'contrat-close-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nPour le contrat %%contrat_titre_complet%% les inscriptions ferment %%ferme_jours%%, %%ferme_date%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-close-placeholders', [
				             'ouvre_jours' => 'Ouverture en jours: "depuis/dans X jours"',
				             'ouvre_date'  => 'Ouverture date : "depuis le/le JJ/MM/AAAA"',
				             'ferme_jours' => 'Clôture en jours: "dans X jours"',
				             'ferme_date'  => 'Clôture date : "le JJ/MM/AAAA"',
			             ], null, [], 'recall' ),
		),
		array(
			'id'           => 'contrat-close-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'contrat-close-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'id'        => 'contrat-close-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Désactiver les rappels pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}

function amapress_contrat_recap_cloture_recall_options() {
	return array(
		array(
			'id'                  => 'contrat-recap-cloture-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Récapitulatif des inscriptions à la clotûre',
			'type'                => 'event-scheduler',
			'show_before'         => false,
			'show_after'          => true,
			'hook_name'           => 'amapress_recall_contrat_recap_cloture',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'close' );
			},
		),
		array(
			'id'                  => 'contrat-recap-cloture-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Récapitulatif des inscriptions à la clotûre',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'show_before'         => false,
			'show_after'          => true,
			'hook_name'           => 'amapress_recall_contrat_recap_cloture',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'close' );
			},
		),
		array(
			'id'                  => 'contrat-recap-cloture-recall-3',
			'name'                => 'Rappel 3',
			'desc'                => 'Récapitulatif des inscriptions à la clotûre',
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'show_before'         => false,
			'show_after'          => true,
			'hook_name'           => 'amapress_recall_contrat_recap_cloture',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'close' );
			},
		),
		array(
			'id'       => 'contrat-recap-cloture-recall-mail-subject',
			'name'     => 'Sujet de l\'email',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => 'Récapitulatif des inscriptions pour %%post:titre%%',
		),
		array(
			'id'      => 'contrat-recap-cloture-recall-mail-content',
			'name'    => 'Contenu de l\'email',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-joint et ci-après le récapitulatif des inscriptions pour %%post:titre%%:\n%%producteur_paniers_quantites%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-recap-placeholders', [
				             'producteur_contrats'                              => 'Contrats du producteur',
				             'producteur_nom'                                   => 'Nom du producteur',
				             'producteur_pseudo'                                => 'Pseudo du producteur',
				             'lien_contrats_quantites'                          => 'Lien vers le récapitulatif des inscriptions',
				             'producteur_paniers_quantites'                     => 'Quantités à la prochaine distribution (en tableau avec/sans montants suivant l\'option Montants pour les paniers modulables)',
				             'producteur_paniers_quantites_columns'             => 'Quantités à la prochaine distribution avec Produits en colonnes',
				             'producteur_paniers_quantites_prix'                => 'Quantités à la prochaine distribution (en tableau avec montants)',
				             'producteur_paniers_quantites_amapiens'            => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien)',
				             'producteur_paniers_quantites_amapiens_prix'       => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants)',
				             'producteur_paniers_quantites_prix_group'          => 'Quantités à la prochaine distribution (en tableau avec montants et produits groupés)',
				             'producteur_paniers_quantites_amapiens_prix_group' => 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants et les produits groupés)',
				             'producteur_contact'                               => 'Coordonnées du producteur',
			             ], null, [], 'recall' ),
		),
		array(
			'id'      => 'contrat-recap-cloture-xlsx',
			'name'    => 'Attacher les excels suivants',
			'type'    => 'multi-check',
			'options' => [
				'date'                 => 'Récapitulatif par date',
				'month'                => 'Récapitulatif par mois',
				'quarter'              => 'Récapitulatif par trimestre',
				'group_date'           => 'Récapitulatif par date par groupe produits',
				'adherents_date'       => 'Récapitulatif par adherent par date',
				'adherents_month'      => 'Récapitulatif par adherent par mois',
				'adherents_quarter'    => 'Récapitulatif par adherent par trimestre',
				'adherents_group_date' => 'Récapitulatif par adherent par date par groupe produits',
				'adherents_columns'    => 'Récapitulatif par adherent par date par groupe produits en colonnes',
			],
			'default' => 'all',
		),
		array(
			'id'           => 'contrat-recap-cloture-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Emails en copie',
		),
		array(
			'id'           => 'contrat-recap-cloture-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'id'      => 'contrat-recap-cloture-recall-send-referents',
			'name'    => 'Envoi aux référents',
			'type'    => 'checkbox',
			'desc'    => 'Envoyer les quantités à livrer aux référents',
			'default' => 1,
		),
		array(
			'id'        => 'contrat-recap-cloture-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs exclus',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Désactiver les rappels pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'id'        => 'contrat-recap-cloture-recall-send-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Envoi Producteurs',
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => 'Envoyer les rappels au producteur (par défaut uniquement aux référents) pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}
