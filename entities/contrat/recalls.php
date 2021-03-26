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
		if ( 0 === strcasecmp( $contrat->getSubName(), 'test' ) ) {
			continue;
		}
		switch ( $type ) {
			case 'open':
				if ( $contrat->canSelfSubscribe() ) {
					$ret[] = [
						'id'    => $contrat->getID(),
						'time'  => $contrat->getDate_ouverture(),
						'type'  => $type,
						'title' => sprintf( __( 'Ouverture inscriptions (%s) - %s', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ), $contrat->getTitle() )
					];
				}
				break;
			case 'close':
				if ( $contrat->canSelfSubscribe() ) {
					$ret[] = [
						'id'    => $contrat->getID(),
						'time'  => $contrat->getDate_cloture(),
						'type'  => $type,
						'title' => sprintf( __( 'Clôture inscriptions (%s) - %s', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_cloture() ), $contrat->getTitle() )
					];
				}
				break;
			case 'start':
				$ret[] = [
					'id'    => $contrat->getID(),
					'time'  => $contrat->getDate_debut(),
					'type'  => $type,
					'title' => sprintf( __( 'Début (%s) - %s', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_debut() ), $contrat->getTitle() )
				];
				break;
			case 'end':
				$ret[] = [
					'id'    => $contrat->getID(),
					'time'  => $contrat->getDate_fin(),
					'type'  => $type,
					'title' => sprintf( __( 'Fin (%s) - %s', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_fin() ), $contrat->getTitle() )
				];
				break;
		}

	}

	return $ret;
}

function amapress_recall_contrat_quantites( $args, $num = '' ) {

	$dist = AmapressDistribution::getBy( $args['id'] );

	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	$contrats_by_producteurs = from( $dist->getContrats() )->groupBy( function ( $c ) {
		/** @var AmapressContrat_instance $c */
		if ( empty( $c->getModel() ) ) {
			return null;
		}

		return $c->getModel()->getProducteurId();
	} );

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'distribution-quantites' . $num . '-recall-excl-producteurs' ) );
	$send_to_producteurs      = Amapress::get_array( Amapress::getOption( 'distribution-quantites' . $num . '-recall-send-producteurs' ) );
	$send_referents           = Amapress::getOption( 'distribution-quantites' . $num . '-recall-send-referents' );

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
		$show_price_modulables = Amapress::getOption( 'distribution-quantites' . $num . '-recall-price-mod', 0 );

		/** @var AmapressContrat_instance $contrat */
		foreach ( $contrats as $contrat ) {
			$replacements = [];

			$replacements['producteur_contact'] = '<div><h5>' . __( 'Contact producteur:', 'amapress' ) . '</h5>' .
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
				$send_title[] = __( 'Producteur', 'amapress' );
				if ( $send_referents ) {
					$send_title[] = __( 'Référents', 'amapress' );
					$referent_ids = $contrat->getAllReferentsIds();
				} else {
					$referent_ids = [];
				}
				$referent_ids[] = $producteur->getUserId();
			} else {
				$send_title[] = __( 'Référents', 'amapress' );
				$referent_ids = $contrat->getAllReferentsIds();
			}

			$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ),
				implode( ' et ', $send_title ) . $producteur->getTitle(), 'referents' );
			$subject      = Amapress::getOption( 'distribution-quantites' . $num . '-recall-mail-subject' );
			$content      = Amapress::getOption( 'distribution-quantites' . $num . '-recall-mail-content' );
			if ( $contrat->isPanierVariable() ) {
				$content_modulable = Amapress::getOption( 'distribution-quantites' . $num . '-modulable-recall-mail-content' );
				if ( ! empty( trim( strip_tags( $content_modulable ) ) ) ) {
					$content = $content_modulable;
				}
			}
			foreach ( $replacements as $k => $v ) {
				$subject = str_replace( "%%$k%%", $v, $subject );
				$content = str_replace( "%%$k%%", $v, $content );
			}

			$attachments = [];
			foreach ( Amapress::get_array( Amapress::getOption( 'distribution-quantites' . $num . '-recall-xlsx' ) ) as $excel_name ) {
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
				amapress_get_recall_cc_from_option( 'distribution-quantites' . $num . '-recall-cc' ) );
			echo '<p>' . sprintf( __( 'Email de rappel des quantités aux producteurs envoyé : %s', 'amapress' ), esc_html( $producteur->getTitle() ) ) . '</p>';
			$sent_mails = true;
		}
	}

	if ( ! $sent_mails ) {
		echo '<p>' . __( 'Pas de quantités à rappeler à cette distribution', 'amapress' ) . '</p>';
	}
}

add_action( 'amapress_recall_contrat_quantites', function ( $args ) {
	amapress_recall_contrat_quantites( $args );
} );
add_action( 'amapress_recall_contrat_quantites2', function ( $args ) {
	amapress_recall_contrat_quantites( $args, '2' );
} );
add_action( 'amapress_recall_contrat_quantites3', function ( $args ) {
	amapress_recall_contrat_quantites( $args, '3' );
} );

add_action( 'amapress_recall_contrats_paiements_producteur', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

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
			$replacements['prochaine_date_remise_cheques'] = __( 'Aucune', 'amapress' );
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
				$title_cheques      = sprintf( __( 'Règlements - %s - %s', 'amapress' ), $contrat_instance->getModelTitle(), $lieu->getShortName() );
				if ( strlen( $title_cheques ) > 27 ) {
					$title_cheques = substr( $title_cheques, 0, 27 ) . '...';
				}
				$title_adherents = sprintf( __( 'Adhérents - %s - %s', 'amapress' ), $contrat_instance->getModelTitle(), $lieu->getShortName() );
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

		$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ), sprintf( __( 'Référents %s', 'amapress' ), $producteur->getTitle() ), 'referents' );
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
		echo '<p>' . __( 'Email de rappel de la liste des règlements envoyé', 'amapress' ) . '</p>';
		$sent_mails = true;
	}

	if ( ! $sent_mails ) {
		echo '<p>' . __( 'Pas de liste de règlements à rappeler à cette distribution', 'amapress' ) . '</p>';
	}

	//%%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%
	//contrats-liste-paiements-recall-mail-
} );

add_action( 'amapress_recall_contrat_renew', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution intouvable', 'amapress' ) . '</p>';

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
		echo '<p>' . __( 'Pas de contrat à renouveler', 'amapress' ) . '</p>';

		return;
	}

	$replacements = [];

	$replacements['nb_contrats']            = count( $to_renew ) + count( $near_renew );
	$replacements['nb_renew_contrats']      = count( $to_renew );
	$replacements['nb_near_renew_contrats'] = count( $near_renew );
	$replacements['contrats_to_renew']      = implode( '<br/>', array_map( function ( $c ) use ( $dist ) {
		/** @var AmapressContrat_instance $c */
		return sprintf( __( '-> Le contrat "%s" est expiré depuis le %s (depuis %d jours)', 'amapress' ),
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
		return sprintf( __( '-> Le contrat "%s" expire le %s (dans %d jours)', 'amapress' ),
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

	$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ), __( 'Référents', 'amapress' ), 'referents' );
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
	echo '<p>' . __( 'Email de rappel de contrat à renouveler envoyé', 'amapress' ) . '</p>';

} );

add_action( 'amapress_recall_contrat_openclose', function ( $args ) {
	$contrat = AmapressContrat_instance::getBy( $args['id'] );
	if ( null == $contrat ) {
		echo '<p>' . __( 'Contrat intouvable', 'amapress' ) . '</p>';

		return;
	}

	if ( 0 === strcasecmp( $contrat->getSubName(), 'test' ) ) {
		return;
	}

	$today = Amapress::start_of_day( amapress_time() );
	if ( Amapress::start_of_day( $contrat->getDate_cloture() ) < $today ) {
		echo '<p>' . __( 'Contrat clos', 'amapress' ) . '</p>';

		return;
	}

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'contrat-' . $args['type'] . '-recall-excl-producteurs' ) );
	if ( in_array( $contrat->getModel()->getProducteurId(), $disabled_for_producteurs ) ) {
		echo '<p>' . __( 'Producteur exclu', 'amapress' ) . '</p>';

		return;
	}

	$replacements = [];

	if ( Amapress::start_of_day( $contrat->getDate_ouverture() ) < $today ) {
		$replacements['ouvre_jours'] = sprintf( __( 'depuis %s jour(s)', 'amapress' ), round( ( amapress_time() - $contrat->getDate_ouverture() ) / ( 24 * HOUR_IN_SECONDS ) ) );
		$replacements['ouvre_date']  = sprintf( __( 'depuis le %s', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) );
	} else {
		$replacements['ouvre_jours'] = sprintf( __( 'dans %s jour(s)', 'amapress' ), round( ( $contrat->getDate_ouverture() - amapress_time() ) / ( 24 * HOUR_IN_SECONDS ) ) );
		$replacements['ouvre_date']  = sprintf( __( 'le %s', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) );
	}

	if ( Amapress::start_of_day( $contrat->getDate_cloture() ) > $today ) {
		$replacements['ferme_jours'] = sprintf( __( 'dans %s jour(s)', 'amapress' ), round( ( $contrat->getDate_cloture() - amapress_time() ) / ( 24 * HOUR_IN_SECONDS ) ) );
		$replacements['ferme_date']  = sprintf( __( 'le %s', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) );
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
				'amapress_role' => 'active',
				'fields'        => 'ids'
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
	$target_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $user_ids ), __( 'Amapiens', 'amapress' ), 'amapiens' );
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
	echo '<p>' . __( 'Email de rappel envoyé', 'amapress' ) . '</p>';
} );

add_action( 'amapress_recall_contrat_recap_cloture', function ( $args ) {
	$contrat_instance = AmapressContrat_instance::getBy( $args['id'] );

	if ( null == $contrat_instance ) {
		echo '<p>' . __( 'Contrat introuvable', 'amapress' ) . '</p>';

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
	$replacements['producteur_contact']                   = '<div><h5>' . __( 'Contact producteur:', 'amapress' ) . '</h5>' .
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
		$send_title[] = __( 'Producteur', 'amapress' );
		if ( $send_referents ) {
			$send_title[] = __( 'Référents', 'amapress' );
			$referent_ids = $contrat_instance->getAllReferentsIds();
		} else {
			$referent_ids = [];
		}
		$referent_ids[] = $producteur->getUserId();
	} else {
		$send_title[] = __( 'Référents', 'amapress' );
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
	echo '<p>' . sprintf( __( 'Email de rappel récapitulatif des inscriptions envoyé : %s', 'amapress' ), esc_html( $producteur->getTitle() ) ) . '</p>';
} );

function amapress_contrat_quantites_recall_options( $num = '' ) {
	return array(
		array(
			'id'                  => 'distribution-quantites' . $num . '-recall-1',
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Quantités à livrer', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites' . $num,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-quantites' . $num . '-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Quantités à livrer', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites' . $num,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-quantites' . $num . '-recall-3',
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Quantités à livrer', 'amapress' ),
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites' . $num,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-quantites' . $num . '-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'type'     => 'text',
			'sanitize' => false,
			'default'  => __( 'Quantités de la semaine pour %%producteur_nom%% (%%producteur_contrats%%) - %%post:title%%', 'amapress' ),
		),
		array(
			'id'      => 'distribution-quantites' . $num . '-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous trouverez ci-dessous (et à l'adresse suivante: %%lien_contrats_quantites%%) les quantités de la semaine pour %%lien_distribution_titre%%:\n%%producteur_paniers_quantites%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
				       Amapress::getPlaceholdersHelpTable( 'liste-quants-placeholders', [
					       'producteur_contrats'                              => __( 'Contrats du producteur', 'amapress' ),
					       'producteur_nom'                                   => __( 'Nom du producteur', 'amapress' ),
					       'producteur_pseudo'                                => __( 'Pseudo du producteur', 'amapress' ),
					       'lien_contrats_quantites'                          => __( 'Lien vers les quantités à la prochaine distribution', 'amapress' ),
					       'producteur_paniers_quantites_text'                => __( 'Quantités à la prochaine distribution (en texte)', 'amapress' ),
					       'producteur_paniers_quantites_text_prix'           => __( 'Quantités à la prochaine distribution (en texte avec montants)', 'amapress' ),
					       'producteur_paniers_quantites_columns'             => __( 'Quantités à la prochaine distribution avec Produits en colonnes', 'amapress' ),
					       'producteur_paniers_quantites'                     => __( 'Quantités à la prochaine distribution (en tableau avec/sans montants suivant l\'option Montants pour les paniers modulables)', 'amapress' ),
					       'producteur_paniers_quantites_prix'                => __( 'Quantités à la prochaine distribution (en tableau avec montants)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens'            => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens_prix'       => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants)', 'amapress' ),
					       'producteur_paniers_quantites_prix_group'          => __( 'Quantités à la prochaine distribution (en tableau avec montants et produits groupés)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens_prix_group' => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants et les produits groupés)', 'amapress' ),
					       'producteur_contact'                               => __( 'Coordonnées du producteur', 'amapress' ),
				       ], null, [], 'recall' );
			},
		),
		array(
			'id'      => 'distribution-quantites' . $num . '-modulable-recall-mail-content',
			'name'    => __( 'Contenu de l\'email - Paniers modulables', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous trouverez ci-dessous (et à l'adresse suivante: %%lien_contrats_quantites%%) les quantités de la semaine pour %%lien_distribution_titre%%:\n%%producteur_paniers_quantites%%\n\nDétails par amapien:\n%%producteur_paniers_quantites_amapiens%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Spécifique aux paniers modulables. Si vide, le contenu du mail général sera utlisé.<br/> Les placeholders suivants sont disponibles:', 'amapress' ) .
				       Amapress::getPlaceholdersHelpTable( 'liste-quants-placeholders', [
					       'producteur_contrats'                              => __( 'Contrats du producteur', 'amapress' ),
					       'producteur_nom'                                   => __( 'Nom du producteur', 'amapress' ),
					       'producteur_pseudo'                                => __( 'Pseudo du producteur', 'amapress' ),
					       'lien_contrats_quantites'                          => __( 'Lien vers les quantités à la prochaine distribution', 'amapress' ),
					       'producteur_paniers_quantites_text'                => __( 'Quantités à la prochaine distribution (en texte)', 'amapress' ),
					       'producteur_paniers_quantites_text_prix'           => __( 'Quantités à la prochaine distribution (en texte avec montants)', 'amapress' ),
					       'producteur_paniers_quantites_columns'             => __( 'Quantités à la prochaine distribution avec Produits en colonnes', 'amapress' ),
					       'producteur_paniers_quantites'                     => __( 'Quantités à la prochaine distribution (en tableau avec/sans montants suivant l\'option Montants pour les paniers modulables)', 'amapress' ),
					       'producteur_paniers_quantites_prix'                => __( 'Quantités à la prochaine distribution (en tableau avec montants)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens'            => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens_prix'       => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants)', 'amapress' ),
					       'producteur_paniers_quantites_prix_group'          => __( 'Quantités à la prochaine distribution (en tableau avec montants et produits groupés)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens_prix_group' => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants et les produits groupés)', 'amapress' ),
					       'producteur_contact'                               => __( 'Coordonnées du producteur', 'amapress' ),
				       ], null, [], 'recall' );
			},
		),
		array(
			'id'   => 'distribution-quantites' . $num . '-recall-price-mod',
			'name' => __( 'Montants paniers modulables', 'amapress' ),
			'desc' => __( 'Afficher les montants pour les paniers modulables', 'amapress' ),
			'type' => 'checkbox',
		),
		array(
			'id'      => 'distribution-quantites' . $num . '-recall-xlsx',
			'name'    => __( 'Attacher les excels suivants', 'amapress' ),
			'type'    => 'multi-check',
			'options' => [
				'date'                 => __( 'Récapitulatif par produit', 'amapress' ),
				'group_date'           => __( 'Récapitulatif par groupe produits', 'amapress' ),
				'adherents_date'       => __( 'Récapitulatif par adherent par produit', 'amapress' ),
				'adherents_group_date' => __( 'Récapitulatif par adherent par groupe produits', 'amapress' ),
				'adherents_columns'    => __( 'Récapitulatif par adherent par groupe produits en colonnes', 'amapress' ),
			],
			'default' => 'all',
		),
		array(
			'id'           => 'distribution-quantites' . $num . '-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'distribution-quantites' . $num . '-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'id'      => 'distribution-quantites' . $num . '-recall-send-referents',
			'name'    => __( 'Envoi aux référents', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Envoyer les quantités à livrer aux référents', 'amapress' ),
			'default' => 1,
		),
		array(
			'id'        => 'distribution-quantites' . $num . '-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Producteurs exclus', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Désactiver les rappels pour les producteurs suivants :', 'amapress' ),
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'id'        => 'distribution-quantites' . $num . '-recall-send-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Envoi Producteurs', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Envoyer les rappels au producteur (par défaut uniquement aux référents) pour les producteurs suivants :', 'amapress' ),
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Liste des règlements', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrats-liste-paiements-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Liste des règlements', 'amapress' ),
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
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Liste des règlements', 'amapress' ),
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
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Chèques au producteur] Liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%',
		),
		array(
			'id'      => 'contrats-liste-paiements-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous trouverez ci-joint la liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
				       Amapress::getPlaceholdersHelpTable( 'liste-paiements-placeholders', [
					       'prochaine_date_remise_cheques' => __( 'Prochaine date de remise des chèques', 'amapress' ),
					       'producteur_contrats'           => __( 'Contrats du producteur', 'amapress' ),
					       'producteur_nom'                => __( 'Nom du producteur', 'amapress' ),
					       'producteur_pseudo'             => __( 'Pseudo du producteur', 'amapress' ),
					       'contrat_nom'                   => __( 'Nom du contrat', 'amapress' ),
					       'lien_contrats_paiements'       => __( 'Lien vers la liste des chèques à remettre au producteur', 'amapress' ),
				       ], null, [], 'recall' );
			},

		),
		array(
			'id'           => 'contrats-liste-paiements-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'contrats-liste-paiements-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'id'        => 'contrats-liste-paiements-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Producteurs', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Désactiver les rappels pour les producteurs suivants :', 'amapress' ),
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Contrats à renouveler', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_renew',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrat-renew-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Contrats à renouveler', 'amapress' ),
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
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Contrats à renouveler', 'amapress' ),
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
			'name'    => __( 'Délai d\'expiration', 'amapress' ),
			'type'    => 'number',
			'desc'    => __( 'Prévenir x jours avant la fin d\'un contrat', 'amapress' ),
			'default' => 45,
		),
		array(
			'id'       => 'contrat-renew-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'type'     => 'text',
			'sanitize' => false,
			'default'  => '%%nb_contrats%% contrats à renouveler',
		),
		array(
			'id'      => 'contrat-renew-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nLes contrats suivants sont à renouveler:\n%%contrats_to_renew%%\n\nLes contrats suivants seront bientôt à renouveler:\n%%contrats_near_end%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
				       Amapress::getPlaceholdersHelpTable( 'liste-renew-placeholders', [
					       'contrats_to_renew'      => __( 'Contrats à renouveler', 'amapress' ),
					       'contrats_near_end'      => __( 'Contrats proches de la fin', 'amapress' ),
					       'nb_contrats'            => __( 'Nombre de contrats à renouveler ou proches de la fin', 'amapress' ),
					       'nb_renew_contrats'      => __( 'Nombre de contrats à renouveler', 'amapress' ),
					       'nb_near_renew_contrats' => __( 'Nombre de contrats proches de la fin', 'amapress' ),
				       ], null, [], 'recall' );
			},
		),
		array(
			'id'           => 'contrat-renew-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'contrat-renew-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'id'        => 'contrat-renew-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Producteurs', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Désactiver les rappels pour les producteurs suivants :', 'amapress' ),
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Contrats ouverts à l\'inscription', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'open' );
			},
		),
		array(
			'id'                  => 'contrat-open-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Contrats ouverts à l\'inscription', 'amapress' ),
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
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Contrats ouverts à l\'inscription', 'amapress' ),
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
			'type' => 'note',
			'desc' => __( 'Les contrats dont le nom complémentaire est égal à "test" ne sont pas concernés par ces rappels', 'amapress' ),
		),
		array(
			'id'      => 'contrat-open-recall-targets',
			'name'    => __( 'Destinataires', 'amapress' ),
			'type'    => 'radio',
			'options' => [
				'with-contrat' => __( 'Amapiens avec contrat', 'amapress' ),
				'same-lieu'    => __( 'Amapiens des lieux de distributions de ce contrat', 'amapress' ),
				'all'          => __( 'Tous les amapiens (avec adhésions/avec contrats/collectifs sauf producteur)', 'amapress' ),
			],
			'default' => 'all',
		),
		array(
			'id'       => 'contrat-open-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'type'     => 'text',
			'sanitize' => false,
			'default'  => __( 'Inscriptions %%contrat_type_complet%% - ouverture préinscription %%ouvre_date%%', 'amapress' ),
		),
		array(
			'id'      => 'contrat-open-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nPour le contrat %%contrat_titre_complet%% les inscriptions sont ouvertes %%ouvre_date%%\n et fermeront %%ferme_date%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
				       AmapressContrat_instance::getPlaceholdersHelp( [
					       'ouvre_jours' => __( 'Ouverture en jours: "depuis/dans X jours"', 'amapress' ),
					       'ouvre_date'  => __( 'Ouverture date : "depuis le/le JJ/MM/AAAA"', 'amapress' ),
					       'ferme_jours' => __( 'Clôture en jours: "dans X jours"', 'amapress' ),
					       'ferme_date'  => __( 'Clôture date : "le JJ/MM/AAAA"', 'amapress' ),
				       ], null );
			},
		),
		array(
			'id'           => 'contrat-open-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'contrat-open-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'id'        => 'contrat-open-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Producteurs', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Désactiver les rappels pour les producteurs suivants :', 'amapress' ),
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Contrats bientôt fermés à l\'inscription', 'amapress' ),
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_openclose',
			'show_after'          => true,
			'hook_args_generator' => function ( $option ) {
				return amapress_get_contrats_cron( 'close' );
			},
		),
		array(
			'id'                  => 'contrat-close-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Contrats bientôt fermés à l\'inscription', 'amapress' ),
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
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Contrats bientôt fermés à l\'inscription', 'amapress' ),
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
			'type' => 'note',
			'desc' => __( 'Les contrats dont le nom complémentaire est égal à "test" ne sont pas concernés par ces rappels', 'amapress' ),
		),
		array(
			'id'      => 'contrat-close-recall-targets',
			'name'    => __( 'Destinataires', 'amapress' ),
			'type'    => 'radio',
			'options' => [
				'with-contrat' => __( 'Amapiens avec contrat', 'amapress' ),
				'same-lieu'    => __( 'Amapiens des lieux de distributions de ce contrat', 'amapress' ),
				'all'          => __( 'Tous les amapiens (avec adhésions/avec contrats/collectifs sauf producteur)', 'amapress' ),
			],
			'default' => 'all',
		),
		array(
			'id'       => 'contrat-close-recall-mail-subject',
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'type'     => 'text',
			'sanitize' => false,
			'default'  => __( 'Inscriptions %%contrat_type_complet%% - clôture %%ferme_date%%', 'amapress' ),
		),
		array(
			'id'      => 'contrat-close-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nPour le contrat %%contrat_titre_complet%% les inscriptions ferment %%ferme_jours%%, %%ferme_date%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
				       Amapress::getPlaceholdersHelpTable( 'liste-close-placeholders', [
					       'ouvre_jours' => __( 'Ouverture en jours: "depuis/dans X jours"', 'amapress' ),
					       'ouvre_date'  => __( 'Ouverture date : "depuis le/le JJ/MM/AAAA"', 'amapress' ),
					       'ferme_jours' => __( 'Clôture en jours: "dans X jours"', 'amapress' ),
					       'ferme_date'  => __( 'Clôture date : "le JJ/MM/AAAA"', 'amapress' ),
				       ], null, [], 'recall' );
			},
		),
		array(
			'id'           => 'contrat-close-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'contrat-close-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'id'        => 'contrat-close-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Producteurs', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Désactiver les rappels pour les producteurs suivants :', 'amapress' ),
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
			'name'                => __( 'Rappel 1', 'amapress' ),
			'desc'                => __( 'Récapitulatif des inscriptions à la clotûre', 'amapress' ),
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
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Récapitulatif des inscriptions à la clotûre', 'amapress' ),
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
			'name'                => __( 'Rappel 3', 'amapress' ),
			'desc'                => __( 'Récapitulatif des inscriptions à la clotûre', 'amapress' ),
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
			'name'     => __( 'Objet de l\'email', 'amapress' ),
			'type'     => 'text',
			'sanitize' => false,
			'default'  => __( 'Récapitulatif des inscriptions pour %%post:titre%%', 'amapress' ),
		),
		array(
			'id'      => 'contrat-recap-cloture-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nVous trouverez ci-joint et ci-après le récapitulatif des inscriptions pour %%post:titre%%:\n%%producteur_paniers_quantites%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
				       Amapress::getPlaceholdersHelpTable( 'liste-recap-placeholders', [
					       'producteur_contrats'                              => __( 'Contrats du producteur', 'amapress' ),
					       'producteur_nom'                                   => __( 'Nom du producteur', 'amapress' ),
					       'producteur_pseudo'                                => __( 'Pseudo du producteur', 'amapress' ),
					       'lien_contrats_quantites'                          => __( 'Lien vers le récapitulatif des inscriptions', 'amapress' ),
					       'producteur_paniers_quantites'                     => __( 'Quantités à la prochaine distribution (en tableau avec/sans montants suivant l\'option Montants pour les paniers modulables)', 'amapress' ),
					       'producteur_paniers_quantites_columns'             => __( 'Quantités à la prochaine distribution avec Produits en colonnes', 'amapress' ),
					       'producteur_paniers_quantites_prix'                => __( 'Quantités à la prochaine distribution (en tableau avec montants)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens'            => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens_prix'       => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants)', 'amapress' ),
					       'producteur_paniers_quantites_prix_group'          => __( 'Quantités à la prochaine distribution (en tableau avec montants et produits groupés)', 'amapress' ),
					       'producteur_paniers_quantites_amapiens_prix_group' => __( 'Quantités à la prochaine distribution (en tableau avec le détails par amapien et les montants et les produits groupés)', 'amapress' ),
					       'producteur_contact'                               => __( 'Coordonnées du producteur', 'amapress' ),
				       ], null, [], 'recall' );
			},
		),
		array(
			'id'      => 'contrat-recap-cloture-xlsx',
			'name'    => __( 'Attacher les excels suivants', 'amapress' ),
			'type'    => 'multi-check',
			'options' => [
				'date'                 => __( 'Récapitulatif par date', 'amapress' ),
				'month'                => __( 'Récapitulatif par mois', 'amapress' ),
				'quarter'              => __( 'Récapitulatif par trimestre', 'amapress' ),
				'group_date'           => __( 'Récapitulatif par date par groupe produits', 'amapress' ),
				'adherents_date'       => __( 'Récapitulatif par adherent par date', 'amapress' ),
				'adherents_month'      => __( 'Récapitulatif par adherent par mois', 'amapress' ),
				'adherents_quarter'    => __( 'Récapitulatif par adherent par trimestre', 'amapress' ),
				'adherents_group_date' => __( 'Récapitulatif par adherent par date par groupe produits', 'amapress' ),
				'adherents_columns'    => __( 'Récapitulatif par adherent par date par groupe produits en colonnes', 'amapress' ),
			],
			'default' => 'all',
		),
		array(
			'id'           => 'contrat-recap-cloture-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'contrat-recap-cloture-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'id'      => 'contrat-recap-cloture-recall-send-referents',
			'name'    => __( 'Envoi aux référents', 'amapress' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Envoyer les quantités à livrer aux référents', 'amapress' ),
			'default' => 1,
		),
		array(
			'id'        => 'contrat-recap-cloture-recall-excl-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Producteurs exclus', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Désactiver les rappels pour les producteurs suivants :', 'amapress' ),
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'id'        => 'contrat-recap-cloture-recall-send-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => __( 'Envoi Producteurs', 'amapress' ),
			'post_type' => AmapressProducteur::INTERNAL_POST_TYPE,
			'desc'      => __( 'Envoyer les rappels au producteur (par défaut uniquement aux référents) pour les producteurs suivants :', 'amapress' ),
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}
