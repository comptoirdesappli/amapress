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
		return $c->getModel()->getProducteurId();
	} );

	$enabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'distribution-quantites-recall-producteurs' ) );

	foreach ( $contrats_by_producteurs as $producteur_id => $contrats ) {
		if ( ! in_array( $producteur_id, $enabled_for_producteurs ) ) {
			continue;
		}

		$replacements = [];
		$producteur   = AmapressProducteur::getBy( $producteur_id );

		$replacements['producteur_contact']           = '<div><h5>Contact producteur:</h5>' .
		                                                $producteur->getUser()->getDisplay(
			                                                array(
				                                                'show_avatar' => 'false',
				                                                'show_tel'    => 'force',
				                                                'show_sms'    => 'force',
				                                                'show_email'  => 'force',
				                                                'show_roles'  => 'false',
			                                                ) ) . '</div>';
		$replacements['producteur_paniers_quantites'] = '';
		foreach ( $contrats as $contrat ) {
			$replacements['producteur_paniers_quantites'] .= amapress_get_contrat_quantite_datatable(
				$contrat->ID, null,
				$dist->getDate(), [
				'show_contact_producteur' => false,
			] );
		}
		$replacements['producteur_paniers_quantites_text'] = '';
		foreach ( $contrats as $contrat ) {
			$replacements['producteur_paniers_quantites_text'] .= amapress_get_contrat_quantite_datatable(
				$contrat->ID, null,
				$dist->getDate(), [
				'show_contact_producteur' => false,
				'mode'                    => 'text',
			] );
		}

		$replacements['lien_contrats_quantites'] = Amapress::makeLink( admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) );

		$replacements['producteur_nom']      = $producteur->getUser()->getDisplayName() . ' (' . $producteur->getTitle() . ')';
		$replacements['producteur_contrats'] = $producteur->getContratsNames();

		$referent_ids = array_map(
			function ( $r ) {
				return $r['ref_id'];
			}, AmapressContrats::getReferentsForProducteur( $producteur_id )
		);

		$target_users = amapress_prepare_message_target( "user:include=" . implode( ',', $referent_ids ), "Référents " . $producteur->getTitle(), 'referents' );
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
} );

add_action( 'amapress_recall_contrats_paiements_producteur', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		return;
	}

	$enabled_for_producteurs = Amapress::get_array( Amapress::getOption( 'contrats-liste-paiements-recall-producteurs' ) );

	foreach ( $dist->getContrats() as $contrat ) {
		if ( ! in_array( $contrat->getModel()->getProducteurId(), $enabled_for_producteurs ) ) {
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
			if ( $contrat->getModel()->getProducteurId() != $producteur->ID ) {
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

				$lieu     = AmapressLieu_distribution::getBy( $lieu_id );
				$date     = date_i18n( 'd-m-Y' );
				$filename = strtolower( sanitize_file_name( "cheques-{$contrat_instance->getModel()->getTitle()}-{$lieu->getShortName()}-au-$date.pdf" ) );
				$title    = "Chèques - {$contrat_instance->getModel()->getTitle()} - {$lieu->getShortName()}";

				$attachments[] = Amapress::createPdfFromHtmlAsMailAttachment( $html, $filename, 'L', $format );
				$attachments[] = Amapress::createXLSXFromHtmlAsMailAttachment( $html, $filename, $title );
			}
		}


		$referent_ids = array_map(
			function ( $r ) {
				return $r['ref_id'];
			}, AmapressContrats::getReferentsForContratInstance( $contrat->ID )
		);

		$target_users = amapress_prepare_message_target( "user:include=" . implode( ',', $referent_ids ), "Référents " . $producteur->getTitle(), 'referents' );
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

function amapress_contrat_quantites_recall_options() {
	return array(
		array(
			'id'                  => 'distribution-quantites-recall-1',
			'name'                => 'Rappel 1',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-quantites-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'distribution-quantites-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrat_quantites',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'distribution-quantites-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => 'Quantités de la semaine pour %%producteur_nom%% (%%producteur_contrats%%) - %%post:title%%',
		),
		array(
			'id'      => 'distribution-quantites-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-dessous (et à l'adresse suivante: %%lien_contrats_quantites%%) les quantités de la semaine pour %%lien_distribution_titre%%:\n%%producteur_paniers_quantites%%\n\n%%nom_site%%" ),
		),
		array(
			'id'   => 'distribution-quantites-recall-cc',
			'name' => amapress__( 'Cc' ),
			'type' => 'multicheck-users',
			'desc' => 'Mails en copie',
		),
		array(
			'id'        => 'distribution-quantites-recall-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs',
			'post_type' => AmapressLieu_distribution::INTERNAL_POST_TYPE,
			'desc'      => 'Activer les rappels pour les producteurs suivants :',
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
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrats-liste-paiements-recall-2',
			'name'                => 'Rappel 2',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'contrats-liste-paiements-recall-3',
			'name'                => 'Rappel 3',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_contrats_paiements_producteur',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'      => 'contrats-liste-paiements-recall-mail-subject',
			'name'    => 'Sujet du mail',
			'type'    => 'text',
			'default' => '[Chèques au producteur] Liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%',
		),
		array(
			'id'      => 'contrats-liste-paiements-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nVous trouverez ci-joint la liste des chèques à remettre à %%producteur_nom%% pour %%contrat_nom%% au %%prochaine_date_remise_cheques%%\n\n%%nom_site%%" ),
		),
		array(
			'id'   => 'contrats-liste-paiements-recall-cc',
			'name' => amapress__( 'Cc' ),
			'type' => 'multicheck-users',
			'desc' => 'Mails en copie',
		),
		array(
			'id'        => 'contrats-liste-paiements-recall-producteurs',
			'type'      => 'multicheck-posts',
			'name'      => 'Producteurs',
			'post_type' => AmapressLieu_distribution::INTERNAL_POST_TYPE,
			'desc'      => 'Activer les rappels pour les producteurs suivants :',
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		),
		array(
			'type' => 'save',
		),
	);
}

//function amapress_contrat_renew_recall_options() {
//}
