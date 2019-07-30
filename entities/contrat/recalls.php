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


add_action( 'amapress_recall_contrat_quantites', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );

	if ( null == $dist ) {
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

	foreach ( $contrats_by_producteurs as $producteur_id => $contrats ) {
		if ( in_array( $producteur_id, $disabled_for_producteurs ) ) {
			continue;
		}

		$producteur = AmapressProducteur::getBy( $producteur_id );
		if ( empty( $producteur ) ) {
			continue;
		}

		/** @var AmapressContrat $contrat */
		foreach ( $contrats as $contrat ) {
			$replacements                                      = [];
			$replacements['producteur_contact']                = '<div><h5>Contact producteur:</h5>' .
			                                                     $producteur->getUser()->getDisplay(
				                                                     array(
					                                                     'show_avatar' => 'false',
					                                                     'show_tel'    => 'force',
					                                                     'show_sms'    => 'force',
					                                                     'show_email'  => 'force',
					                                                     'show_roles'  => 'false',
				                                                     ) ) . '</div>';
			$replacements['producteur_paniers_quantites']      = '<style>table, th, td { border-collapse: collapse; border: 1pt solid #000; } .odd {background-color: #eee; }</style>';
			$replacements['producteur_paniers_quantites']      = amapress_get_contrat_quantite_datatable(
				$contrat->ID, null,
				$dist->getDate(), [
				'show_contact_producteur' => false,
				'no_script'               => true,
			] );
			$replacements['producteur_paniers_quantites_text'] = '';
			$replacements['producteur_paniers_quantites_text'] = amapress_get_contrat_quantite_datatable(
				$contrat->ID, null,
				$dist->getDate(), [
				'show_contact_producteur' => false,
				'mode'                    => 'text',
				'no_script'               => true,
			] );

			$replacements['lien_contrats_quantites'] = Amapress::makeLink( admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) );

			$replacements['producteur_nom']      = $producteur->getUser()->getDisplayName() . ' (' . $producteur->getTitle() . ')';
			$replacements['producteur_contrats'] = $producteur->getContratsNames();

			$referent_ids = $contrat->getAllReferentsIds();

			$target_users = amapress_prepare_message_target_to( "user:include=" . implode( ',', $referent_ids ), "Référents " . $producteur->getTitle(), 'referents' );
			$subject      = Amapress::getOption( 'distribution-quantites-recall-mail-subject' );
			$content      = Amapress::getOption( 'distribution-quantites-recall-mail-content' );
			foreach ( $replacements as $k => $v ) {
				$subject = str_replace( "%%$k%%", $v, $subject );
				$content = str_replace( "%%$k%%", $v, $content );
			}
			amapress_send_message(
				$subject,
				$content,
				'', $target_users, $dist, array(),
				amapress_get_recall_cc_from_option( 'distribution-quantites-recall-cc' ) );
		}
	}
} );

add_action( 'amapress_recall_contrats_paiements_producteur', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$disabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'contrats-liste-paiements-recall-excl-producteurs' ) );

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

		$replacements['producteur_nom']      = $producteur->getUser()->getDisplayName() . ' (' . $producteur->getTitle() . ')';
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
				$filename_cheques   = strtolower( sanitize_file_name( "cheques-{$contrat_instance->getModelTitle()}-{$lieu->getShortName()}-au-$date" ) );
				$filename_adherents = strtolower( sanitize_file_name( "adherents-{$contrat_instance->getModelTitle()}-{$lieu->getShortName()}-au-$date" ) );
				$title_cheques      = "Chèques - {$contrat_instance->getModelTitle()} - {$lieu->getShortName()}";
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
	}

	//%%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%
	//contrats-liste-paiements-recall-mail-
} );

add_action( 'amapress_recall_contrat_renew', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$contrats           = AmapressContrats::get_active_contrat_instances( null, $dist->getDate() );
	$renewable_contrats = array_filter( $contrats, function ( $c ) {
		/** @var AmapressContrat_instance $c */
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

	if ( empty( $near_renew ) && empty( $to_renew ) ) {
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
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'distribution-quantites-recall-mail-subject',
			'name'     => 'Sujet du mail',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => 'Quantités de la semaine pour %%producteur_nom%% (%%producteur_contrats%%) - %%post:title%%',
		),
		array(
			'id'      => 'distribution-quantites-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-dessous (et à l'adresse suivante: %%lien_contrats_quantites%%) les quantités de la semaine pour %%lien_distribution_titre%%:\n%%producteur_paniers_quantites%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-quants-placeholders', [
				             'producteur_contrats'               => 'Contrats du producteur',
				             'producteur_nom'                    => 'Nom du producteur',
				             'lien_contrats_quantites'           => 'Lien vers les quantités à la prochaine distribution',
				             'producteur_paniers_quantites_text' => 'Quantités à la prochaine distribution (en texte)',
				             'producteur_paniers_quantites'      => 'Quantités à la prochaine distribution (en tableau)',
				             'producteur_contact'                => 'Coordonnées du producteur',
			             ], null ),
		),
		array(
			'id'           => 'distribution-quantites-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
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
			'id'        => 'distribution-quantites-recall-excl-producteurs',
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

function amapress_contrat_paiements_recall_options() {
	return array(
		array(
			'id'                  => 'contrats-liste-paiements-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Liste des chèques',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrats-liste-paiements-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Liste des chèques',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrats-liste-paiements-recall-3',
			'name'                => 'Rappel 3',
			'desc'                => 'Liste des chèques',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'contrats-liste-paiements-recall-mail-subject',
			'name'     => 'Sujet du mail',
			'sanitize' => false,
			'type'     => 'text',
			'default'  => '[Chèques au producteur] Liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%',
		),
		array(
			'id'      => 'contrats-liste-paiements-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-joint la liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-paiements-placeholders', [
				             'prochaine_date_remise_cheques' => 'Prochaine date de remise des chèques',
				             'producteur_contrats'           => 'Contrats du producteur',
				             'producteur_nom'                => 'Nom du producteur',
				             'contrat_nom'                   => 'Nom du contrat',
				             'lien_contrats_paiements'       => 'Lien vers la liste des chèques à remettre au producteur',
			             ], null ),

		),
		array(
			'id'           => 'contrats-liste-paiements-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
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
			'name'     => 'Sujet du mail',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => '%%nb_contrats%% contrats à renouveler',
		),
		array(
			'id'      => 'contrat-renew-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nLes contrats suivants sont à renouvèler:\n%%contrats_to_renew%%\n\nLes contrats suivants seront bientôt à renouvèler:\n%%contrats_near_end%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'liste-renew-placeholders', [
				             'contrats_to_renew'      => 'Contrats à renouvèler',
				             'contrats_near_end'      => 'Contrats proches de la fin',
				             'nb_contrats'            => 'Nombre de contrats à renouveler ou proches de la fin',
				             'nb_renew_contrats'      => 'Nombre de contrats à renouveler',
				             'nb_near_renew_contrats' => 'Nombre de contrats proches de la fin',
			             ], null ),
		),
		array(
			'id'           => 'contrat-renew-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
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
			'type' => 'save',
		),
	);
}

//function amapress_contrat_renew_recall_options() {
//}
