<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 13/05/2016
 * Time: 11:14
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_can_renew_contrat_instance( $post_or_user ) {
	$contrat_instance = AmapressContrat_instance::getBy( $post_or_user );
	if ( ! $contrat_instance ) {
		return false;
	}

	return $contrat_instance->canRenew();
}

function amapress_can_renew_same_period_contrat_instance( $post_or_user ) {
	$contrat_instance = AmapressContrat_instance::getBy( $post_or_user );
	if ( ! $contrat_instance ) {
		return false;
	}

	$diff = Amapress::datediffInWeeks(
		Amapress::start_of_week( $contrat_instance->getDate_debut() ),
		Amapress::end_of_week( $contrat_instance->getDate_fin() )
	);

	return $diff <= 53;
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_contrat' );
function amapress_register_entities_contrat( $entities ) {
	$entities['contrat']          = array(
		'singular'                 => amapress__( 'Production' ),
		'plural'                   => amapress__( 'Productions' ),
		'public'                   => true,
		'thumb'                    => true,
		'editor'                   => true,
		'special_options'          => array(),
		'show_in_menu'             => false,
		'slug'                     => 'contrats',
		'custom_archive_template'  => true,
		'menu_icon'                => 'flaticon-note',
		'default_orderby'          => 'post_title',
		'default_order'            => 'ASC',
		'other_def_hidden_columns' => array( 'amps_lo' ),
		'show_admin_bar_new'       => false,
		'views'                    => array(
			'remove'  => array( 'mine' ),
			'exp_csv' => true,
		),
		'groups'                   => [
			'Producteur' => [
				'context' => 'side',
			],
		],
		'edit_header'              => function ( $post ) {
			$contrat = AmapressContrat::getBy( $post );
			if ( TitanFrameworkOption::isOnEditScreen() ) {
				if ( empty( $contrat->getProducteur() ) ) {
					echo '<div class="notice notice-error"><p>Production invalide : pas de producteur associée</p></div>';
				}
			}
			if ( empty( $contrat->getAllReferentsIds() ) ) {
				echo '<div class="notice notice-error"><p>Production sans référent</p></div>';
			}

			TitanFrameworkOption::echoFullEditLinkAndWarning();

			echo '<h2>Présentation de la production <em>(Légumes, Champignons, Pains...)</em></h2>';
		},
		'fields'                   => array(
//			'amapress_icon_id' => array(
//				'name'    => amapress__( 'Icône' ),
//				'type'    => 'upload',
//				'group'   => 'Information',
//				'desc'    => 'Icône',
//				'bare_id' => true,
//			),
//            'presentation' => array(
//                'name' => amapress__('Présentation'),
//                'type' => 'editor',
//                'required' => true,
//                'desc' => 'Présentation',
//            ),
//            'nb_visites' => array(
//                'name' => amapress__('Nombre de visites obligatoires'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => 'Nombre de visites obligatoires',
//            ),
//            'max_adherents' => array(
//                'name' => amapress__('Nombre de maximum d\'adhérents'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => 'Nombre de maximum d\'adhérents',
//            ),
			'producteur' => array(
				'name'              => amapress__( 'Producteur' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'required'          => true,
				'desc'              => 'Producteur',
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'group'             => 'Producteur',
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => 'Toutes les producteurs',
				),
				'searchable'        => true,
				'readonly'          => function ( $post_id ) {
					if ( TitanFrameworkOption::isOnEditScreen() ) {
						return true;
					}

					if ( amapress_is_current_user_producteur() ) {
						return true;
					}

					return false;
				},
			),
			'referent'   => array(
				'name'         => amapress__( 'Référent' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents spécifiques',
				'desc'         => 'Référent producteur pour tous les lieux et spécifique pour cette production et son/ses contrat(s)',
				'readonly'     => 'amapress_is_current_user_producteur',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent2'  => array(
				'name'         => amapress__( 'Référent 2' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents spécifiques',
				'desc'         => 'Deuxième référent producteur pour tous les lieux et spécifique pour cette production et son/ses contrat(s)',
				'readonly'     => 'amapress_is_current_user_producteur',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent3'  => array(
				'name'         => amapress__( 'Référent 3' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents spécifiques',
				'desc'         => 'Troisième référent producteur pour tous les lieux et spécifique pour cette production et son/ses contrat(s)',
				'readonly'     => 'amapress_is_current_user_producteur',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'contrats'   => array(
				'name'            => amapress__( 'Contrats' ),
				'show_column'     => true,
				'group'           => 'Contrats',
				'include_columns' => array(
					'title',
					'amapress_contrat_instance_name',
					'amapress_contrat_instance_type',
				),
				'type'            => 'related-posts',
				'query'           => 'post_type=amps_contrat_inst&amapress_date=active&amapress_contrat=%%id%%',
			),
		),
	);
	$entities['contrat_instance'] = array(
		'internal_name'            => 'amps_contrat_inst',
		'singular'                 => amapress__( 'Modèle de contrat' ),
		'plural'                   => amapress__( 'Modèles de contrat' ),
		'public'                   => 'adminonly',
		'show_in_menu'             => false,
		'special_options'          => array(),
		'slug'                     => 'contrat_instances',
		'title_format'             => 'amapress_contrat_instance_title_formatter',
		'title'                    => false,
		'slug_format'              => 'from_title',
		'thumb'                    => true,
		'editor'                   => false,
		'menu_icon'                => 'flaticon-interface',
		'default_orderby'          => 'post_title',
		'default_order'            => 'ASC',
		'show_in_nav_menu'         => false,
		'groups'                   => array(
			'Statut' => [
				'context' => 'side',
			],
		),
		'edit_header'              => function ( $post ) {
			$contrat = AmapressContrat_instance::getBy( $post );
			if ( TitanFrameworkOption::isOnEditScreen() ) {
				if ( empty( $contrat->getModel() ) ) {
					echo '<div class="notice notice-error"><p>Modèle de contrat invalide : pas de production associée</p></div>';
				} else {
					$result = $contrat->getContratModelDocStatus();
					if ( true !== $result ) {
						echo amapress_get_admin_notice( $result['message'], $result['status'], false );
					}

					$result = $contrat->getContratPapierModelDocStatus();
					if ( true !== $result ) {
						echo amapress_get_admin_notice( $result['message'], $result['status'], false );
					}
				}

				$max_nb_paiements = 0;
				foreach ( $contrat->getPossiblePaiements() as $nb_pmt ) {
					$max_nb_paiements = max( $nb_pmt, $max_nb_paiements );
				}
				$nb_dates_paiements = count( $contrat->getPaiements_Liste_dates() );
				if ( $max_nb_paiements > $nb_dates_paiements ) {
					echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf( 'Il y a moins de dates d\'encaissement (%d) que le nombre de chèque maximum autorisé (%d)', $nb_dates_paiements, $max_nb_paiements ) . '</p></div>';
				}


				if ( empty( AmapressContrats::get_contrat_quantites( $post->ID ) ) ) {
					$class   = 'notice notice-error';
					$message = 'Vous devez configurer les quantités et tarifs des paniers';
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				}

				echo '<h4>EDITER</h4>';

				if ( $contrat->isArchived() ) {
					echo '<p style="color: red">Contrat archivé. Pas de modification possible.</p>';
				}

				$adhs = AmapressContrats::get_active_adhesions( $post->ID );
				if ( ! empty( $adhs ) ) {
					if ( isset( $_REQUEST['adv'] ) || isset( $_REQUEST['full_edit'] ) ) {
						echo '<p style="color: red"><span class="dashicons dashicons-warning"></span> Édition d’un contrat actif</p>';
						if ( $contrat->isArchived() ) {
							wp_die( 'Edition d\'un contrat archivée impossible' );
						}
					}

					if ( ! $contrat->isArchived() ) {
						TitanFrameworkOption::echoFullEditLinkAndWarning(
							'Edition avancée', ''
						);

						echo '<p>Modifier ce contrat peut impacter les ' . count( $adhs ) . ' inscriptions associées. 
<span class="description">(Par ex : si vous changez le nombre de dates de distribution, le montant de l\'inscription sera adapté et les quantités saisies dans le cas d\'un contrat modulable seront perdues.)</span></p>';
//				echo '<p>Ce contrat a déjà des inscriptions. Modifier ce contrat peut impacter les ' . count( $adhs ) . ' inscriptions associées. Par exemple si vous changez le nombre de dates de distribution le montant de l\'inscription sera adapté et les quantités saisies dans le cas d\'un contrat avec quantités variables peuvent être perdues.</p>';
//				if ( ! isset( $_REQUEST['adv'] ) ) {
////					echo '<p>Si vous voulez malgrès tout éditer le contrat, utiliser le lien suivant : <a href="' . esc_attr( add_query_arg( 'adv', 'true' ) ) . '">Confirmer l\'édition.</a></p>';
//					echo '<p><a href="' . esc_attr( add_query_arg( 'adv', 'true' ) ) . '">Confirmer l\'édition</a></p>';
//				}

						echo '<p><a href="' . amapress_get_row_action_href( 'clone', $post->ID ) . '">Dupliquer</a> : Créer un nouveau contrat - Durée et période identiques <span class="description">(Par ex : Semaine A - Semaine B)</span></p>';
					}
				}

				echo '<h4>CONSULTER</h4>';
				if ( count( $adhs ) > 0 ) {
					echo '<p><a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_adhesion&amapress_contrat_inst=' . $post->ID ) . '">La liste des ' . count( $adhs ) . ' adhérent(s) inscrits à ce contrat</a></p>';
				}
				echo '<p><a target="_blank" href="' . admin_url( 'admin.php?amp_stats_contrat=' . $post->ID . '&page=contrats_quantites_stats' ) . '">Les statistiques annuelles</a></p>';

				if ( ! $contrat->isArchived() ) {
					echo '<h4>EXPORTER FICHIERS</h4>';
					echo '<p>';
					echo '<a href="' . AmapressExport_Posts::get_export_url( null, admin_url( 'edit.php?post_type=amps_adhesion&amapress_contrat_inst=' . $post->ID . '&amapress_export=csv' ) ) . '">Adhérents (XLSX)</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=paiement_table_xlsx&contrat=' . $post->ID ) . '">Chèques/règlements (XLSX)</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=paiement_table_pdf&contrat=' . $post->ID ) . '">Chèques/règlements (PDF)</a>';
					echo '<a href="' . admin_url( 'admin-post.php?action=delivery_table_xlsx&type=group_date&contrat=' . $post->ID ) . '">Livraisons par dates (XLSX)</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=delivery_table_xlsx&type=adherents_date&contrat=' . $post->ID ) . '">Livraisons par adhérents (XLSX)</a>,';
					echo '</p>';
				} else {
					echo '<h4>TELECHARGER ARCHIVES</h4>';
					echo '<p>';
					echo '<a href="' . admin_url( 'admin-post.php?action=archives_inscriptions&contrat=' . $post->ID ) . '">Adhérents (XLSX)</a>,';
					foreach ( ( count( $contrat->getLieuxIds() ) > 1 ? array_merge( [ 0 ], $contrat->getLieuxIds() ) : $contrat->getLieuxIds() ) as $lieu_id ) {
						$lieu = ( 0 == $lieu_id ? null : AmapressLieu_distribution::getBy( $lieu_id ) );
						echo '<a href="' . admin_url( 'admin-post.php?action=archives_cheques&lieu=' . $lieu_id . '&contrat=' . $post->ID ) . '">Chèques/règlements - ' . ( 0 == $lieu_id ? 'Tous les lieux' : $lieu->getTitle() ) . ' (XLSX)</a>,';
					}
					echo '<a href="' . admin_url( 'admin-post.php?action=archives_inscriptions&type=group_date&contrat=' . $post->ID ) . '">Livraisons par dates (XLSX)</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=archives_inscriptions&type=adherents_date&contrat=' . $post->ID ) . '">Livraisons par adhérents (XLSX)</a>,';
					echo '</p>';
				}
			}

			echo '<h4>ACCÈS RAPIDE</h4>';

			if ( $contrat->isArchived() ) {
				echo '<style type="text/css">
.amapress_publish.button.button-primary, #amapress_publish, .amapress_publish.button-primary {
    display: none !important;
}
</style>';
			}

			echo '<script type="text/javascript">jQuery(function($) { $("body > div#ui-datepicker-div").hide(); });</script>';
		},
		'row_actions'              => array(
			'renew'              => array(
				'label'     => 'Renouveler (prolongement)',
				'condition' => 'amapress_can_renew_contrat_instance',
				'show_on'   => 'list',
				'confirm'   => true,
			),
			'renew_same_period'  => array(
				'label'     => 'Renouveler (même période)',
				'condition' => 'amapress_can_renew_same_period_contrat_instance',
				'show_on'   => 'list',
				'confirm'   => true,
			),
			'clone'              => [
				'label'   => 'Dupliquer',
				'show_on' => 'list',
				'confirm' => true,
			],
			'generate_contrat'   => [
				'label'     => 'Générer le contrat papier (DOCX)',
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return ! empty( $contrat->getContratPapierModelDocFileName() )
					       && Amapress::start_of_week( $contrat->getDate_fin() ) > Amapress::start_of_day( amapress_time() );
				},
			],
			'mailto_amapiens'    => [
				'label'     => 'Email aux amapiens',
				'target'    => '_blank',
//				'confirm'   => true,
				'href'      => admin_url( 'admin.php?page=amapress_messages_page' ),
				'condition' => function ( $adh_id ) {
					return TitanFrameworkOption::isOnEditScreen();
				},
				'show_on'   => 'editor',
			],
//			'smsto_amapiens'     => [
//				'label'     => 'Sms aux amapiens',
//				'target'    => '_blank',
//				'confirm'   => true,
//				'href'      => function ( $adh_id ) {
//					$contrat = AmapressContrat_instance::getBy( $adh_id );
//
//					return $contrat->getSMStoAmapiens();
//				},
//				'condition' => function ( $adh_id ) {
//					return TitanFrameworkOption::isOnEditScreen();
//				},
//				'show_on'   => 'editor',
//			],
			'show_distribs'      => [
				'label'   => 'Historique des distributions',
				'target'  => '_blank',
				'href'    => function ( $adh_id ) {
					return admin_url( "edit.php?post_type=amps_distribution&amapress_contrat_inst=$adh_id" );
				},
				'show_on' => 'editor',
			],
			'show_next_distribs' => [
				'label'     => 'Prochaines distributions',
				'target'    => '_blank',
				'href'      => function ( $adh_id ) {
					return admin_url( "edit.php?post_type=amps_distribution&amapress_date=active&amapress_contrat_inst=$adh_id" );
				},
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return $contrat->getDate_fin() > Amapress::end_of_week( amapress_time() );
				},
			],
//			'export_inscr'      => [
//				'label'     => 'Exporter les inscriptions',
//				'target'    => '_blank',
//				'confirm'   => true,
//				'href'      => function ( $adh_id ) {
//					return AmapressExport_Posts::get_export_url( null, admin_url( 'edit.php?post_type=amps_adhesion&amapress_contrat_inst=' . $adh_id . '&amapress_export=csv' ) );
//				},
//				'condition' => function ( $adh_id ) {
//					return TitanFrameworkOption::isOnEditScreen();
//				},
//				'show_on'   => 'none',
//			],
//			'export_pmt_xlsx'   => [
//				'label'     => 'Exporter les chèques (XLSX)',
//				'target'    => '_blank',
//				'confirm'   => true,
//				'href'      => function ( $adh_id ) {
//					return admin_url( 'admin-post.php?action=paiement_table_xlsx&contrat=' . $adh_id );
//				},
//				'condition' => function ( $adh_id ) {
//					return TitanFrameworkOption::isOnEditScreen();
//				},
//				'show_on'   => 'none',
//			],
//			'export_pmt_pdf'    => [
//				'label'     => 'Exporter les chèques (PDF)',
//				'target'    => '_blank',
//				'confirm'   => true,
//				'href'      => function ( $adh_id ) {
//					return admin_url( 'admin-post.php?action=paiement_table_pdf&contrat=' . $adh_id );
//				},
//				'condition' => function ( $adh_id ) {
//					return TitanFrameworkOption::isOnEditScreen();
//				},
//				'show_on'   => 'none',
//			],
//			'view_stats'        => [
//				'label'     => 'Voir les stats',
//				'target'    => '_blank',
//				'href'      => function ( $adh_id ) {
//					return admin_url( 'admin.php?amp_stats_contrat=' . $adh_id . '&page=contrats_quantites_stats' );
//				},
//				'condition' => function ( $adh_id ) {
//					return TitanFrameworkOption::isOnEditScreen();
//				},
//				'show_on'   => 'none',
//			],
			'open_inscr'  => [
				'label'     => 'Ouvrir inscriptions',
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return ! $contrat->canSelfSubscribe()
					       && Amapress::start_of_week( $contrat->getDate_fin() ) > Amapress::start_of_day( amapress_time() );
				},
			],
			'close_inscr' => [
				'label'     => 'Fermer inscriptions',
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return $contrat->canSelfSubscribe()
					       && Amapress::start_of_week( $contrat->getDate_fin() ) > Amapress::start_of_day( amapress_time() );
				},
			],
		),
		'bulk_actions'             => array(
			'amp_incr_cloture' => array(
				'label'    => 'Reporter date clôture (1j)',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant l\'opération',
					'0'  => 'Une erreur s\'est produit pendant l\'opération',
					'1'  => 'Date de clôture repoussée d\'un jour',
					'>1' => 'Dates de clôture repoussées d\'un jour',
				),
			),
			'amp_decr_cloture' => array(
				'label'    => 'Diminuer date clôture (1j)',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant l\'opération',
					'0'  => 'Une erreur s\'est produit pendant l\'opération',
					'1'  => 'Date de clôture diminuée d\'un jour',
					'>1' => 'Dates de clôture diminuées d\'un jour',
				),
			),
		),
		'labels'                   => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Ajout modèle de contrat',
			'edit_item'    => 'Éditer - Modèle de contrat',
		),
		'views'                    => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_contrat_instance_views',
		),
		'other_def_hidden_columns' => array( 'thumb-preview' ),
		'fields'                   => array(
			//renouvellement
			'renouv'                => array(
				'name'        => amapress__( 'Options' ),
				'show_column' => false,
				'csv'         => false,
				'show_on'     => 'edit-only',
				'group'       => '> Renouvellement',
//				'bare'        => true,
				'type'        => 'custom',
				'custom'      => function ( $post_id ) {
					$ret = '';
					if ( amapress_can_renew_contrat_instance( $post_id ) ) {
						$ret .= '<p><a href="' . amapress_get_row_action_href( 'renew', $post_id ) . '">Prolongation</a> : Dates continues - Durée identique <span class="description">(Par ex : contrats trimestriels)</span></p>';
					}
					if ( amapress_can_renew_same_period_contrat_instance( $post_id ) ) {
						$ret .= '<p><a href="' . amapress_get_row_action_href( 'renew_same_period', $post_id ) . '">Cyclique</a> : Même période - Année suivante <span class="description">(Par ex : contrats annuels)</span></p>';
					}
					if ( empty( $ret ) ) {
						$ret .= '<p>Contrat déjà renouvellé</p>';
					}

					return $ret;

//					return '<tr><td colspan="2" style="margin: 0; padding: 0">' .
//					       $ret
//					       . '</td></tr>';
				}
			),

			// 1/6 - Ferme
			'producteur'            => array(
				'name'        => amapress__( 'Producteur' ),
				'type'        => 'custom',
				'csv_import'  => false,
				'group'       => '1/6 - Ferme',
				'show_on'     => 'edit-only',
				'show_column' => false,
				'custom'      => function ( $post_id ) {
					$contrat = AmapressContrat_instance::getBy( $post_id );
					if ( empty( $contrat )
					     || empty( $contrat->getModel() )
					     || empty( $contrat->getModel()->getProducteur() )
					     || empty( $contrat->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return Amapress::makeLink(
							$contrat->getModel()->getProducteur()->getAdminEditLink(),
							$contrat->getModel()->getProducteur()->getTitle() )
					       . ' (' . Amapress::makeLink(
							$contrat->getModel()->getProducteur()->getUser()->getEditLink(),
							$contrat->getModel()->getProducteur()->getUser()->getDisplayName() ) . ')';
				}
			),
			'model'                 => array(
				'name'              => amapress__( 'Production' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressContrat::INTERNAL_POST_TYPE,
				'group'             => '1/6 - Ferme',
				'required'          => true,
				'desc'              => 'Sélectionner la production.',
				'import_key'        => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_contrat',
					'placeholder' => 'Toutes les productions',
				),
				'readonly'          => function ( $post_id ) {
					if ( TitanFrameworkOption::isOnNewScreen() ) {
						return false;
					}
					if ( TitanFrameworkOption::isOnEditScreen() ) {
						return true;
					}

					return amapress_is_contrat_instance_readonly( $post_id );
				},
				'searchable'        => true,
			),
			'refs'                  => array(
				'name'                 => amapress__( 'Référents' ),
				'type'                 => 'custom',
				'group'                => '1/6 - Ferme',
				'show_on'              => 'edit-only',
				'csv_import'           => false,
				'desc'                 => 'Pour modifier les référents, cliquez sur le lien Producteur ci-dessus',
				'use_custom_as_column' => true,
				'custom'               => function ( $post_id ) {
					$contrat = AmapressContrat_instance::getBy( $post_id );
					if ( empty( $contrat )
					     || empty( $contrat->getModel() )
					     || empty( $contrat->getModel()->getAllReferentsIds() ) ) {
						return '';
					}

					$refs = [];
					foreach ( $contrat->getModel()->getAllReferentsIds() as $user_id ) {
						$ref    = AmapressUser::getBy( $user_id );
						$refs[] = Amapress::makeLink(
							$ref->getEditLink(),
							$ref->getDisplayName() );
					}

					return implode( ', ', $refs );
				},
			),
			'nb_visites'            => array(
				'name'           => amapress__( 'Visite' ),
				'group'          => '1/6 - Ferme',
				'type'           => 'number',
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'desc'           => 'Nombre de visite(s) obligatoire(s) chez le producteur',
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'max'            => 12,
			),

			// 2/6 - Paramètres généraux
			'date_debut'            => array(
				'name'          => amapress__( 'Début' ),
				'type'          => 'date',
				'group'         => '2/6 - Paramètres généraux',
				'required'      => true,
				'desc'          => 'Date de début du contrat',
				'import_key'    => true,
				'top_filter'    => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'readonly'      => 'amapress_is_contrat_instance_readonly',
				'before_option' =>
					function ( $option ) {
						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
							echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $date_debut = $("#amapress_contrat_instance_date_debut");
    var $date_fin = $("#amapress_contrat_instance_date_fin");
    var $date_ouverture = $("#amapress_contrat_instance_date_ouverture");
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $date_debut.change(function() {
        $date_fin.datepicker("option","minDate", $date_debut.val());
        $date_ouverture.datepicker("option","maxDate", $date_debut.val());
        $liste_dates.multiDatesPicker("option", {minDate: $(this).val()});
    });
    $date_fin.datepicker("option","minDate", $date_debut.val());
    $date_ouverture.datepicker("option","maxDate", $date_debut.val());
    $liste_dates.multiDatesPicker("option", {minDate: $date_debut.val()});
});
//]]>
</script>';
						}
					},
			),
			'date_fin'              => array(
				'name'          => amapress__( 'Fin' ),
				'type'          => 'date',
				'group'         => '2/6 - Paramètres généraux',
				'required'      => true,
				'desc'          => 'Date de fin du contrat',
				'import_key'    => true,
				'readonly'      => 'amapress_is_contrat_instance_readonly',
				'before_option' =>
					function ( $option ) {
						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
							echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $date_debut = $("#amapress_contrat_instance_date_debut");
    var $date_fin = $("#amapress_contrat_instance_date_fin");
    var $date_cloture = $("#amapress_contrat_instance_date_cloture");
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $date_fin.on("change", function() {
        $date_debut.datepicker("option","maxDate", $date_fin.val());
        $date_cloture.datepicker("option","maxDate", $date_fin.val());
        $liste_dates.multiDatesPicker("option", {maxDate: $(this).val()});
    });
    $date_debut.datepicker("option","maxDate", $date_fin.val());
    $date_cloture.datepicker("option","maxDate", $date_fin.val());
    $liste_dates.multiDatesPicker("option", {maxDate: $date_fin.val()});
});
//]]>
</script>';
						}
					},
			),
			'model_name'            => array(
				'name'        => amapress__( 'Nom générique' ),
				'show_column' => false,
				'show_on'     => 'edit-only',
				'group'       => '2/6 - Paramètres généraux',
				'type'        => 'custom',
				'csv_import'  => false,
				'custom'      => function ( $post_id ) {
					$contrat = AmapressContrat_instance::getBy( $post_id );
					if ( ! $contrat ) {
						return '';
					}

					return $contrat->getTitle();
				}
			),
			'name'                  => array(
				'name'           => amapress__( 'Nom complémentaire' ),
				'group'          => '2/6 - Paramètres généraux',
				'type'           => 'text',
				'desc'           => 'Lorsque 2 contrats de même type coexistent (Par ex : ”Semaine A”, “Semaine B”)',
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'max_adherents'         => array(
				'name'           => amapress__( 'Nombre d’amapiens maximum' ),
				'type'           => 'number',
				'group'          => '2/6 - Paramètres généraux',
				'required'       => true,
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'desc'           => 'Nombre maximum d’inscriptions autorisées par le producteur',
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'min_engagement'        => array(
				'name'           => amapress__( 'Engagement minimum' ),
				'type'           => 'number',
				'group'          => '2/6 - Paramètres généraux',
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'desc'           => 'Montant minimum demandé par le producteur pour un contrat',
			),
//			'word_paper_model'      => array(
//				'name'            => amapress__( 'Contrat vierge' ),
//				'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//				'type'            => 'upload',
//				'show_column'     => false,
//				'show_download'   => true,
//				'show_title'      => true,
//				'selector-button' => 'Utiliser ce modèle',
//				'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat personnalisé DOCX',
//				'group'           => '2/6 - Paramètres généraux',
//				'desc'            => '
//                <p><strong>Vous pouvez configurer ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ), 'un modèle global pour tous les contrats' ) . ' et laisser ce champs vide. Le contrat général sera utilisé automatiquement.</strong></p>
//				<p>Sinon configurez un contrat vierge à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)</p>
//				<p>Vous pouvez télécharger <a target="_blank" href="' . esc_attr( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) ) . '">ici</a> l\'un des modèles DOCX génériques utilisables comme contrat vierge. Vous aurez à personnaliser le logo de votre AMAP et les engagements.</p>',
//			),
			'contrat_info'          => array(
				'name'        => amapress__( 'Termes du contrat' ),
				'type'        => 'editor',
				'show_column' => false,
				'group'       => '2/6 - Paramètres généraux',
				'desc'        => 'Termes du contrats (Pour les utilisateurs avancés : à compléter avec des marquages substitutifs de type "%%xxx%%" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=pres_prod_contrat_placeholders' ) . '">Plus d\'info</a>)',
			),
			'special_mention'       => array(
				'name'        => amapress__( 'Mention' ),
				'type'        => 'textarea',
				'group'       => '2/6 - Paramètres généraux',
				'show_column' => false,
				'default'     => '',
				'desc'        => 'Mention spécial ou partie variable pour le contrat (remplit le placeholder %%mention_speciale%%)',
			),


//			'type'           => array(
//				'name'          => amapress__( 'Type de contrat' ),
//				'type'          => 'select',
//				'options'       => array(
//					'panier' => 'Distributions régulières',
////					'commande' => 'Commandes',
//				),
//				'required'      => true,
//				'group'         => 'Gestion',
//				'desc'          => 'Type de contrat',
//				'import_key'    => true,
//				'default'       => 'panier',
//				'readonly'      => 'amapress_is_contrat_instance_readonly',
//				'custom_column' => function ( $option, $post_id ) {
//					$status           = [];
//					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
//					if ( $contrat_instance->isPanierVariable() ) {
//						$status[] = 'Paniers variables';
//					} else if ( $contrat_instance->isQuantiteVariable() ) {
//						if ( $contrat_instance->isQuantiteMultiple() ) {
//							$status[] = 'Quantités variables multiples';
//						} else {
//							$status[] = 'Quantités variables';
//						}
//					} else {
//						if ( $contrat_instance->isQuantiteMultiple() ) {
//							$status[] = 'Quantités fixes multiples';
//						} else {
//							$status[] = 'Quantités fixes';
//						}
//					}
//					if ( $contrat_instance->isPrincipal() ) {
//						$status[] = 'Principal';
//					}
//					if ( $contrat_instance->isEnded() ) {
//						$status[] = 'Clôturé';
//					}
//
//					echo implode( ', ', $status );
//				},
//				'conditional'   => array(
//					'_default_' => 'panier',
//					'panier'    => array(
//
//					),
////					'commande'  => array(
////						'commande_liste_dates'   => array(
////							'name'        => amapress__( 'Calendrier des commandes' ),
////							'type'        => 'multidate',
////							'group'       => 'Commandes',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'required'    => true,
////							'show_column' => false,
////							'desc'        => '',
////						),
////						'commande_cannot_modify' => array(
////							'name'        => amapress__( 'Commandes fermes' ),
////							'type'        => 'checkbox',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'group'       => 'Commandes',
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => '',
////						),
////						'commande_open_before'   => array(
////							'name'        => amapress__( 'Ouverture des commandes' ),
////							'type'        => 'number',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'group'       => 'Commandes',
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => 'Ouverture des commandes x jours avant (0=tout de suite)',
////						),
////						'commande_close_before'  => array(
////							'name'        => amapress__( 'Fermeture des commandes' ),
////							'group'       => 'Commandes',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'type'        => 'number',
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => 'Fermeture des commandes x jours avant',
////						),
////					),
//				)
//			),

			// 3/6 Distributions
			'lieux'                 => array(
				'name'           => amapress__( 'Lieu(x)' ),
				'type'           => 'multicheck-posts',
				'post_type'      => 'amps_lieu',
				'group'          => '3/6 - Distributions',
				'required'       => true,
				'csv_required'   => true,
				'desc'           => 'Lieu(x) de distribution',
				'select_all'     => true,
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'top_filter'     => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Tous les lieux'
				),
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'liste_dates'           => array(
				'name'             => amapress__( 'Calendrier initial' ),
				'type'             => 'multidate',
				'required'         => true,
				'csv_required'     => true,
				'group'            => '3/6 - Distributions',
				'readonly'         => 'amapress_is_contrat_instance_readonly',
				'show_column'      => true,
				'col_def_hidden'   => true,
				'column_value'     => 'dates_count',
				'desc'             => 'Sélectionner les dates de distribution fournies par le producteur',
				'show_dates_count' => true,
				'show_dates_list'  => true,
				'before_option'    =>
					function ( $option ) {
						$is_readonly = amapress_is_contrat_instance_readonly( $option );
						if ( ! TitanFrameworkOption::isOnNewScreen() ) {
							if ( $is_readonly ) {
							} else {
								$val_id = $option->getID() . '-validate';
								echo '<p><input type="checkbox" id="' . $val_id . '" ' . checked( ! $is_readonly, true, false ) . ' /><label for="' . $val_id . '">Cocher cette case pour modifier les dates lors du renouvellement du contrat.</label></p>';
								echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $("#' . $val_id . '").change(function() {
        $liste_dates.multiDatesPicker("option", {disabled: !$(this).is(\':checked\')});
    });
    $liste_dates.multiDatesPicker("option", {disabled: ' . ( $is_readonly ? 'true' : 'false' ) . '});
});
//]]>
</script>';
							}
						}
					},
			),
			'les-paniers'           => array(
				'name'              => amapress__( 'Report livraison' ),
				'group'             => '3/6 - Distributions',
				'table_header_text' => '<p>Pour annuler ou reporter une distribution déjà planifiée, sélectionner le panier correspondant dans la liste ci-dessous</p>',
				'desc'              => 'Dates de livraison des paniers de ce contrat',
				'csv_import'        => false,
				'show_column'       => false,
				'show_link'         => false,
				'show_on'           => 'edit-only',
				'include_columns'   => array(
					'title',
					'amapress_panier_status',
					'amapress_panier_date_subst',
				),
				'datatable_options' => array(
					'paging'     => true,
					'bSort'      => false,
					'info'       => false,
					'searching'  => true,
					'lengthMenu' => [ [ 2, 5, 10, 25, 50, - 1 ], [ 2, 5, 10, 25, 50, 'Tous' ] ],
				),
				'type'              => 'related-posts',
				'query'             => 'post_type=amps_panier&amapress_contrat_inst=%%id%%',
			),
			'nb_resp_supp'          => array(
				'name'           => amapress__( 'Responsables' ),
				'type'           => 'number',
				'required'       => true,
				'desc'           => 'Le nombre de responsable de distribution est configuré pour chaque ' .
				                    Amapress::makeLink( admin_url( 'edit.php?post_type=amps_lieu' ), 'lieu de distribution', true, true ) .
				                    '. Facultatif : Ajouter des responsables supplémentaires.',
				'group'          => '3/6 - Distributions',
				'default'        => 0,
				'max'            => 10,
				'show_column'    => true,
				'col_def_hidden' => true,
			),

			// 4/6 Paniers
			'quant_type'            => array(
				'name'              => amapress__( 'Choix du contenu des paniers' ),
				'type'              => 'custom',
				'group'             => '4/6 - Paniers',
				'readonly'          => 'amapress_is_contrat_instance_readonly',
				'csv'               => true,
				'show_column'       => true,
				'col_def_hidden'    => true,
				'custom_csv_sample' => function ( $option, $arg ) {
					return [
						'Choix unique - quantité déterminée',
						'Choix multiple - quantités déterminées',
						'Choix unique - quantité libre',
						'Choix multiple - quantités libres',
						'Paniers modulables'
					];
				},
				'csv_validator'     => function ( $value ) {
					switch ( $value ) {
						case 'Choix unique - quantité déterminée':
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_quantite_multi'    => 0,
								'amapress_contrat_instance_quantite_variable' => 0,
							];
						case 'Choix multiple - quantités déterminées':
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_quantite_multi'    => 1,
								'amapress_contrat_instance_quantite_variable' => 0,
							];
						case 'Choix unique - quantité libre':
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_quantite_multi'    => 0,
								'amapress_contrat_instance_quantite_variable' => 1,
							];
						case 'Choix multiple - quantités libres':
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_quantite_multi'    => 1,
								'amapress_contrat_instance_quantite_variable' => 1,
							];
						case 'Paniers modulables':
							return [
								'amapress_contrat_instance_panier_variable'   => 1,
								'amapress_contrat_instance_quantite_multi'    => 0,
								'amapress_contrat_instance_quantite_variable' => 0,
							];
						default:
							return new WP_Error( 'cannot_parse', "Valeur '$value' non trouvée pour 'Choix du contenu des paniers'" );
					}
				},
				'column'            => function ( $post_id ) {
					$status           = [];
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( $contrat_instance->isPanierVariable() ) {
						$status[] = 'Paniers modulables';
					} else if ( $contrat_instance->isQuantiteVariable() ) {
						if ( $contrat_instance->isQuantiteMultiple() ) {
							$status[] = 'Choix multiple -  quantités libres';
						} else {
							$status[] = 'Choix unique - quantité libre';
						}
					} else {
						if ( $contrat_instance->isQuantiteMultiple() ) {
							$status[] = 'Choix multiple - quantités déterminées';
						} else {
							$status[] = 'Choix unique - quantité déterminée';
						}
					}
					if ( $contrat_instance->isPrincipal() ) {
						$status[] = 'Principal';
					}
					if ( $contrat_instance->isEnded() ) {
						$status[] = 'Clôturé';
					}

					echo implode( ', ', $status );
				},
				'custom'            => function ( $post_id ) {
					$type             = 'quant_fix';
					$contrat_instance = AmapressContrat_instance::getBy( $post_id, true );
					if ( $contrat_instance ) {
						if ( $contrat_instance->isPanierVariable() ) {
							$type = 'panier_var';
						} else if ( $contrat_instance->isQuantiteVariable() ) {
							if ( $contrat_instance->isQuantiteMultiple() ) {
								$type = 'quant_var_multi';
							} else {
								$type = 'quant_var';
							}
						} else {
							if ( $contrat_instance->isQuantiteMultiple() ) {
								$type = 'quant_fix_multi';
							} else {
								$type = 'quant_fix';
							}
						}
					}

//		$types = [
//			'quant_fix'       => 'Choix unique - quantité déterminée',
//			'quant_fix_multi' => 'Choix multiple - quantités déterminées',
//			'quant_var'       => 'Choix unique - quantité libre',
//			'quant_var_multi' => 'Choix multiple -  quantités libres',
//			'panier_var'      => 'Paniers modulables',
//		];
					ob_start();
					?>
                    <p>Choisissez le type d’option(s) proposée(s) dans le contrat d’origine concernant la composition
                        des paniers.</p>
                    <p><input type="radio" class="required" <?php checked( 'quant_fix', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_fix" value="quant_fix"/><label
                                for="amp_quant_fix"><strong>Choix unique - quantité déterminée</strong> : L’adhérent
                            choisit une seule
                            option pour toute la durée du contrat. </label><br/><span class="description">(Par ex : “Légumes - Panier/Demi-panier” , “Champignons - Petit/Moyen/Grand”, “Fruits - Petit/Moyen/Grand”, “Jus - 1L/3L/6L”, “Oeuf - 6/12”...)</span>
                    </p>
                    <p><input type="radio" class="required" <?php checked( 'quant_fix_multi', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_fix_multi" value="quant_fix_multi"/><label
                                for="amp_quant_fix_multi"><strong>Choix multiple - quantités déterminées</strong> :
                            L’adhérent peut choisir différents
                            produits associés à différentes tailles de panier pour toute la durée du contrat
                        </label><br/><span
                                class="description">(Par ex :  “Champignons - Gros fan de champis 1 kg <strong>et</strong>  petit fan de pleurotes 250g <strong>et</strong>  Petit fan de shiitake 250g…”)</span>
                    </p>
                    <p><input type="radio" class="required" <?php checked( 'quant_var', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_var" value="quant_var"/><label
                                for="amp_quant_var"><strong>Choix unique - quantité libre</strong> : L’adhérent choisit
                            la “Quantité” d’un
                            produit pour toute durée du contrat
                        </label><br/><span
                                class="description">(Par ex : “Quantité  x Poulets”, “Quantité x 6 oeufs”...)</span></p>
                    <p><input type="radio" class="required" <?php checked( 'quant_var_multi', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_var_multi" value="quant_var_multi"/><label
                                for="amp_quant_var_multi"><strong>Choix multiple - quantités libres</strong> :
                            L’adhérent peut choisir différents
                            produits et différentes Quantités pour toute la durée du contrat </label><br/><span
                                class="description">(Par ex : “Oeufs - Quantité 6 et 12 oeufs”, “Fromage - Quantité Petit Panier Option 1 et Quantité Grand panier et Quantité Panier Faisselle...”, “Volailles - Quantité Petit poulet/ Quantité Moyen Poulet, Quantité Gros Poulet”...)</span>
                    <p><input type="radio" class="required" <?php checked( 'panier_var', $type ) ?>
                              name="amapress_quantite_type" id="amp_panier_var" value="panier_var"/><label
                                for="amp_panier_var"><strong>Paniers modulables</strong> : L’adhérent compose à l’avance
                            un panier spécifique
                            pour chaque distribution </label><br/><span
                                class="description">(Par ex : “Brie”, “Epicerie”...)</span></p>
					<?php
					return ob_get_clean();
				},
				'save'              => function ( $post_id ) {
					if ( isset( $_POST['amapress_quantite_type'] ) ) {
						$amapress_quantite_type = $_POST['amapress_quantite_type'];
						delete_post_meta(
							$post_id,
							'amapress_contrat_instance_panier_variable'
						);
						switch ( $amapress_quantite_type ) {
							case 'quant_fix':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									0 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									0 );
								break;
							case 'quant_fix_multi':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									1 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									0 );
								break;
							case 'quant_var':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									0 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									1 );
								break;
							case 'quant_var_multi':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									1 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									1 );
								break;
							case 'panier_var':
								delete_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi' );
								delete_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable' );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_panier_variable',
									1
								);
								break;
						}

						return true;
					}
				},
			),
			'quant_editor'          => array(
				'name'        => amapress__( 'Configuration des paniers (Taille/Quantités)' ),
				'type'        => 'custom',
				'group'       => '4/6 - Paniers',
				'column'      => null,
				'custom'      => 'amapress_get_contrat_quantite_editor',
				'save'        => 'amapress_save_contrat_quantite_editor',
				'show_on'     => 'edit-only',
				'show_column' => false,
				'csv'         => false,
				'bare'        => true,
//                'desc' => 'Quantités',
			),
			'has_pancust'           => array(
				'name'        => amapress__( 'Contenu du panier' ),
				'type'        => 'checkbox',
				'show_column' => false,
				'group'       => '4/6 - Paniers',
				'desc'        => 'Rendre accessible la description des paniers',
			),
			'rattrapage'            => array(
				'name'        => amapress__( 'Rattrapage' ),
				'desc'        => '',
				'type'        => 'custom',
				'group'       => '4/6 - Paniers',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => false,
				'csv_import'  => false,
				'bare'        => true,
				'show_on'     => 'edit-only',
				'hidden'      => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$contrat = AmapressContrat_instance::getBy( $option->getPostID() );

					return $contrat->isPanierVariable();
				},
				'column'      => function ( $post_id ) {
					$contrat    = AmapressContrat_instance::getBy( $post_id, true );
					$rattrapage = [];
					foreach ( $contrat->getRattrapage() as $r ) {
						$rattrapage[] = sprintf( '%s (%.1f)', date_i18n( 'd/m/Y', intval( $r['date'] ) ), $r['quantite'] );
					}

					if ( empty( $rattrapage ) ) {
						return 'Aucun';
					}

					return implode( ', ', $rattrapage );
				},
				'custom'      => function ( $post_id ) {
					$contrat    = AmapressContrat_instance::getBy( $post_id, true );
					$rattrapage = [];
					foreach ( $contrat->getRattrapage() as $r ) {
						$rattrapage[] = $r;
					}
					$i = 0;
					while ( $i < 6 ) {
						$rattrapage[] = [
							'date'     => 0,
							'quantite' => 1,
						];
						$i ++;
					}

					$dates      = [];
					$dates["0"] = '--Date--';
					foreach ( $contrat->getListe_dates() as $date ) {
						$dates[ strval( $date ) ] = date_i18n( 'd/m/Y', $date );
					}

					ob_start();
					echo '<p style="padding: 20px 10px 20px 0;"><strong>Quantités de rattrapage</strong></p>';
					echo '<p class="description">Options pour les Amap dont les quantités de rattrapage sont annoncées en début de contrat</p>';
					echo '<table id="quant_rattrapage" style="width: 100%; border: 1pt solid black">
<thead><tr><th style="padding-left: 20px">Sélectionner une date</th><th style="padding-left: 20px">Indiquer la quantité</th></tr></thead>
<tbody>';
					$i = 0;
					foreach ( $rattrapage as $r ) {
						?>
                        <tr>
                            <td>
                                <select id="<?php echo "amapress_quantite_rattrapage-date-$i"; ?>"
                                        name="<?php echo "amapress_quantite_rattrapage[$i][date]"; ?>"
                                        style="width: 100%"
                                ><?php
									tf_parse_select_options( $dates, $r['date'] );
									?>
                                </select>
                            </td>
                            <td>
                                <input id="<?php echo "amapress_quantite_rattrapage-date-$i"; ?>"
                                       name="<?php echo "amapress_quantite_rattrapage[$i][quantite]"; ?>"
                                       class="number positiveNumber"
                                       value="<?php echo $r['quantite']; ?>"
                                />
                            </td>
                        </tr>
						<?php
						$i ++;
					}
					echo '</tbody></table>';

					return ob_get_clean();
				},
				'save'        => function ( $post_id ) {
					if ( isset( $_POST['amapress_quantite_rattrapage'] ) ) {
						$amapress_quantite_rattrapage = $_POST['amapress_quantite_rattrapage'];
						foreach ( $amapress_quantite_rattrapage as $i => $r ) {
							if ( "0" == $r['date'] ) {
								unset( $amapress_quantite_rattrapage[ $i ] );
							}
						}
						update_post_meta(
							$post_id,
							'amapress_contrat_instance_rattrapage',
							$amapress_quantite_rattrapage );

						return true;
					}
				}
			),

			// 5/6 - Pré-inscription en ligne
			'self_subscribe'        => array(
				'name'           => amapress__( 'Activer' ),
				'type'           => 'checkbox',
				'group'          => '5/6 - Pré-inscription en ligne',
				'desc'           => 'Rendre accessible les pré-inscriptions en ligne pour ce contrat',
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'self_contrats'         => array(
				'name'           => amapress__( 'Autres contrats' ),
				'type'           => 'select-posts',
				'post_type'      => 'amps_contrat_inst',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'group'          => '5/6 - Pré-inscription en ligne',
				'desc'           => 'Rendre accessible les pré-inscriptions en ligne pour ce contrat si l\'amapien a une inscription à l\'un de ces contrats',
				'show_column'    => true,
				'col_def_hidden' => true,
				'multiple'       => true,
				'tags'           => true,
				'autocomplete'   => true,
			),
			'self_edit'             => array(
				'name'           => amapress__( 'Editer' ),
				'type'           => 'checkbox',
				'group'          => '5/6 - Pré-inscription en ligne',
				'desc'           => 'Autoriser l\'édition de l\'inscription jusqu\'à sa validation',
				'default'        => false,
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'date_ouverture'        => array(
				'name'           => amapress__( 'Ouverture' ),
				'type'           => 'date',
				'group'          => '5/6 - Pré-inscription en ligne',
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'desc'           => 'Date d\'ouverture des inscriptions en ligne',
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'custom_column'  => function ( $option, $post_id ) {
					$contrat = AmapressContrat_instance::getBy( $post_id );
					$color   = 'green';
					if ( $contrat
					     && ( $contrat->getDate_ouverture() > Amapress::start_of_day( amapress_time() )
					          || $contrat->getDate_cloture() < Amapress::end_of_day( amapress_time() )
					          || ! $contrat->canSelfSubscribe()
					     ) ) {
						$color = 'orange';
					}
					echo "<span style='color:$color'>";
					echo date_i18n( 'd/m/Y', $contrat->getDate_ouverture() );
					echo '</span>';
				},
				'before_option'  =>
					function ( $option ) {
						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
							echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $date_ouverture = $("#amapress_contrat_instance_date_ouverture");
    var $date_cloture = $("#amapress_contrat_instance_date_cloture");
    $date_ouverture.change(function() {
        $date_cloture.datepicker("option","minDate", $(this).val());
    });
    $date_cloture.datepicker("option","minDate", $date_ouverture.val());
});
//]]>
</script>';
						}
					},
			),
			'date_cloture'          => array(
				'name'           => amapress__( 'Clôture' ),
				'type'           => 'date',
				'group'          => '5/6 - Pré-inscription en ligne',
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'desc'           => 'Date de clôture des inscriptions en ligne',
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'custom_column'  => function ( $option, $post_id ) {
					$contrat = AmapressContrat_instance::getBy( $post_id );
					$color   = 'green';
					if ( $contrat
					     && ( $contrat->getDate_ouverture() > Amapress::start_of_day( amapress_time() )
					          || $contrat->getDate_cloture() < Amapress::end_of_day( amapress_time() )
					          || ! $contrat->canSelfSubscribe()
					     ) ) {
						$color = 'orange';
					}
					echo "<span style='color:$color'>";
					echo date_i18n( 'd/m/Y', $contrat->getDate_cloture() );
					echo '</span>';
				},
//				'before_option' =>
//					function ( $option ) {
//						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
//							echo '<script type="text/javascript">
////<![CDATA[
//jQuery(function($) {
//    var $date_ouverture = $("#amapress_contrat_instance_date_ouverture");
//    var $date_cloture = $("#amapress_contrat_instance_date_cloture");
//    $date_cloture.on("change", function() {
//        $date_ouverture.datepicker("option","maxDate", $date_cloture.val());
//    });
//    $date_ouverture.datepicker("option","maxDate", $date_cloture.val());
//});
////]]>
//</script>';
//						}
//					},
			),
			'pmt_user_input'        => array(
				'name'        => amapress__( 'Libellé règlements' ),
				'type'        => 'checkbox',
				'group'       => '5/6 - Pré-inscription en ligne',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Permettre aux amapiens de renseigner les numéros des chèques dans l’assistant de pré-inscription en ligne',
			),
			'word_model'            => array(
				'name'            => amapress__( 'Contrat personnalisé' ),
				'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'type'            => 'upload',
				'show_column'     => false,
				'show_download'   => true,
				'show_title'      => true,
				'selector-button' => 'Utiliser ce modèle',
				'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat papier DOCX',
				'group'           => '5/6 - Pré-inscription en ligne',
				'desc'            => '
<p><strong>Vous pouvez configurer ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ), 'un modèle global pour tous les contrats' ) . ' et laisser ce champs vide. Le contrat général sera utilisé automatiquement.</strong></p>
<p>Sinon, configurez un modèle de contrat à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ) . '">Plus d\'info</a>)</p>
<p>Vous pouvez télécharger <a target="_blank" href="' . esc_attr( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) ) . '">ici</a> l\'un des modèles DOCX génériques utilisables comme contrat vierge. Vous aurez à personnaliser le logo de votre AMAP et les engagements.</p>',
			),


			//Statut
			'is_principal'          => array(
				'name'        => amapress__( 'Contrat principal' ),
				'type'        => 'checkbox',
				'show_column' => false,
				'required'    => true,
				'group'       => 'Statut',
				'desc'        => 'Rendre obligatoire ce contrat (Par ex : Contrat légumes)',
			),
			'status'                => array(
				'name'    => amapress__( 'Statut' ),
				'type'    => 'custom',
				'column'  => function ( $post_id ) {
					return AmapressContrats::contratStatus( $post_id );
				},
				'custom'  => function ( $post_id ) {
					return AmapressContrats::contratStatus( $post_id );
				},
				'group'   => 'Statut',
				'save'    => null,
				'csv'     => false,
				'desc'    => 'Statut',
				'show_on' => 'edit-only',
			),
			'ended'                 => array(
				'name'        => amapress__( 'Clôturer' ),
				'type'        => 'checkbox',
				'csv_import'  => false,
				'group'       => 'Statut',
				'desc'        => 'Ferme le contrat (<strong>Renouveler le contrat avant de cocher cette case</strong>)',
				'show_on'     => 'edit-only',
				'show_column' => false,
			),
			'status_type'           => array(
				'name'                 => amapress__( 'Résumé' ),
				'type'                 => 'custom',
				'use_custom_as_column' => true,
				'custom'               => function ( $post_id ) {
					$ret     = [];
					$contrat = AmapressContrat_instance::getBy( $post_id );
					if ( $contrat->isPanierVariable() ) {
						$ret[] = 'Paniers modulables';
					} else {
						$ret[] = 'Contrat récurrent';
					}
					if ( $contrat->isPrincipal() ) {
						$ret[] = 'principal';
					}
					if ( $contrat->canSelfEdit() ) {
						$ret[] = 'éditable';
					} else {
						$ret[] = 'non éditable';
					}
					if ( ! empty( $contrat->getPossiblePaiements() ) ) {
						$ret[] = implode( ';', $contrat->getPossiblePaiements() ) . ' chèque(s)';
					}
					if ( $contrat->getAllow_Transfer() ) {
						$ret[] = 'répartition au mois';
					}
					if ( $contrat->getAllow_Transfer() ) {
						$ret[] = 'virement';
					}
					if ( $contrat->getAllow_Cash() ) {
						$ret[] = 'espèces';
					}
					if ( $contrat->getAllow_LocalMoney() ) {
						$ret[] = 'monnaie locale';
					}
					if ( $contrat->getAllow_Delivery_Pay() ) {
						$ret[] = 'à la livraison';
					}
					if ( $contrat
					     && $contrat->getDate_ouverture() > Amapress::start_of_day( amapress_time() )
					) {
						$ret[] = '<span style="color:orange">ouvrira le ' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) . '</span>';
					} elseif ( $contrat
					           && $contrat->getDate_cloture() < Amapress::end_of_day( amapress_time() )
					) {
						$ret[] = '<span style="color:orange">clos depuis ' . date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) . '</span>';
					} elseif ( $contrat
					           && ! $contrat->canSelfSubscribe()
					) {
						$ret[] = '<span style="color:orange">inscription fermée</span>';
					} elseif ( ! empty( $contrat->canSelfContratsCondition() ) ) {
						$ret[] = sprintf( '<span style="color: green">inscription conditionnelle (%s&gt;%s)</span>',
							date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ),
							date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) );
					} else {
						$ret[] = sprintf( '<span style="color: green">inscription ouverte (%s&gt;%s)</span>',
							date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ),
							date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) );
					}

					return implode( ', ', $ret );
				},
				'csv'                  => false,
				'group'                => 'Statut',
			),
//						'quantite_multi'        => array(
//							'name'        => amapress__( 'Quantités multiples' ),
//							'type'        => 'checkbox',
//							'group'       => 'Gestion',
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'default'     => 1,
//							'desc'        => 'Cocher cette case si les quantités ',
//						),
//						'panier_variable'       => array(
//							'name'        => amapress__( 'Paniers personnalisés' ),
//							'type'        => 'checkbox',
//							'group'       => 'Gestion',
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'desc'        => 'Cocher cette case si les paniers sont spécifiques pour chacun des adhérents',
//						),
//						'quantite_variable'     => array(
//							'name'        => amapress__( 'Quantités personnalisées' ),
//							'type'        => 'checkbox',
//							'group'       => 'Gestion',
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'desc'        => 'Cocher cette case si les quantités peuvent être modulées (par ex, 1L, 1.5L, 3L...)',
//						),

			// 6/6 - reglements
			'paiements'             => array(
				'name'           => amapress__( 'Nombre de chèques' ),
				'type'           => 'multicheck',
				'desc'           => 'Sélectionner le nombre de règlements autorisés par le producteur',
				'group'          => '6/6 - Règlements',
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'required'       => true,
				'csv_required'   => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'options'        => array(
					'1'  => '1 chèque',
					'2'  => '2 chèques',
					'3'  => '3 chèques',
					'4'  => '4 chèques',
					'5'  => '5 chèques',
					'6'  => '6 chèques',
					'7'  => '7 chèques',
					'8'  => '8 chèques',
					'9'  => '9 chèques',
					'10' => '10 chèques',
					'11' => '11 chèques',
					'12' => '12 chèques',
				)
			),
			'pay_month'             => array(
				'name'        => amapress__( 'Paiement mensuel' ),
				'type'        => 'checkbox',
				'group'       => '6/6 - Règlements',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Activer la répartition mensuelle de la remise des chèques au producteur',
			),
			'allow_deliv_pay'       => array(
				'name'        => amapress__( 'A la livraison' ),
				'type'        => 'checkbox',
				'group'       => '6/6 - Règlements',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour régler les commandes dont le prix (lié au poids) est connu à la livraison',
			),
			'allow_cash'            => array(
				'name'        => amapress__( 'Espèces' ),
				'type'        => 'checkbox',
				'group'       => '6/6 - Règlements',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en espèce',
			),
			'allow_bktrfr'          => array(
				'name'        => amapress__( 'Virement' ),
				'type'        => 'checkbox',
				'group'       => '6/6 - Règlements',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement par virement',
			),
			'allow_locmon'          => array(
				'name'        => amapress__( 'Monnaie locale' ),
				'type'        => 'checkbox',
				'group'       => '6/6 - Règlements',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en monnaie locale',
			),
			'manage_paiements'      => array(
				'name'        => amapress__( 'Répartition des règlements' ),
				'type'        => 'checkbox',
				'group'       => '6/6 - Règlements',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => 'Gérer la répartition et le suivi des règlements dans Amapress',
			),
			'paiements_mention'     => array(
				'name'        => amapress__( 'Références' ),
				'type'        => 'editor',
				'group'       => '6/6 - Règlements',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => false,
				'desc'        => 'Donner des instructions dans l’assistant de pré-inscription en ligne concernant les modalités de règlement<br/>Le placeholder %%id%% peut être utilisé pour mentionner une référence d\'inscription',
			),
			'paiements_ordre'       => array(
				'name'        => amapress__( 'Ordre des chèques' ),
				'type'        => 'text',
				'group'       => '6/6 - Règlements',
				'show_column' => false,
				'desc'        => 'Indiquer l’ordre des chèques si différent du nom du producteur',
			),
			'liste_dates_paiements' => array(
				'name'             => amapress__( 'Calendrier des remises de chèques' ),
				'type'             => 'multidate',
				'readonly'         => 'amapress_is_contrat_instance_readonly',
				'required'         => true,
				'group'            => '6/6 - Règlements',
				'show_column'      => false,
				'show_dates_count' => true,
				'show_dates_list'  => true,
				'desc'             => 'Sélectionner les dates auxquelles le producteur souhaite recevoir les chèques',
			),
			'min_cheque_amount'     => array(
				'name'        => amapress__( 'Montant minimum' ),
				'type'        => 'number',
				'group'       => '6/6 - Règlements',
				'required'    => true,
				'show_column' => false,
				'desc'        => 'Montant minimum du plus petit règlement pour les paiements en plusieurs fois',
			),
			'options_paiements'     => array(
				'name'        => amapress__( 'Répartition' ),
				'type'        => 'custom',
				'group'       => '6/6 - Règlements',
				'csv'         => false,
				'show_on'     => 'edit-only',
				'show_column' => false,
				'custom'      => function ( $post_id ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( ! $contrat_instance ) {
						return '';
					}

//					if ( $contrat_instance->isQuantiteVariable() ) {
//						return '<p>Pas de proposition de répartition pour ce type de paniers</p>';
//					}

					$columns = array(
						array(
							'title' => 'Date de début contrat',
							'data'  => 'date',
						),
					);
					$quants  = AmapressContrats::get_contrat_quantites( $contrat_instance->ID );
					foreach ( $quants as $quant ) {
						$columns[] = array(
							'title' => $quant->getTitle(),
							'data'  => 'quant_' . $quant->ID,
						);
					}
					$data = [];
					foreach ( $contrat_instance->getListe_dates() as $date ) {
						if ( Amapress::end_of_day( $date ) > $contrat_instance->getDate_cloture() ) {
							continue;
						}

						if ( ! empty( $contrat_instance->getContratPapierModelDocFileName() ) ) {
							$row = array(
								'date' => Amapress::makeLink(
									amapress_get_row_action_href( 'generate_contrat', $post_id, [
										'start_date' => $date
									] ),
									date_i18n( 'd/m/y', $date ) . ' (contrat vierge)', true, true ),
							);
						} else {
							$row = array(
								'date' => date_i18n( 'd/m/y', $date ),
							);
						}
						foreach ( $quants as $quant ) {
							$remaining_dates_factors = $contrat_instance->getRemainingDatesWithFactors( $date, $quant->ID );
							$price                   = $quant->getPrix_unitaire();
							$total                   = $remaining_dates_factors * $price;
							$options                 = '';
							if ( ! $contrat_instance->isQuantiteVariable() && ! $contrat_instance->isPanierVariable() ) {
								foreach ( $contrat_instance->getPossiblePaiements() as $nb_cheque ) {
									$o = $contrat_instance->getChequeOptionsForTotal( $nb_cheque, $total );
									if ( empty( $o ) ) {
										continue;
									}
									$options .= '<li>' . esc_html( $o['desc'] ) . '</li>';
								}
							}
							if ( $contrat_instance->getAllow_Cash() ) {
								$options .= '<li>En espèces</li>';
							}
							if ( $contrat_instance->getAllow_Transfer() ) {
								$options .= '<li>Par virement</li>';
							}

							$row[ 'quant_' . $quant->ID ] = "<p>{$remaining_dates_factors} x {$price}€ = {$total}€</p><ul>{$options}</ul>";
						}
//					    foreach ($contrat_instance->gett)
						$data[] = $row;
					}

					$ret = '<p>Consulter notre <a href="#" id="show_options_cheques">proposition de répartition</a> du montant des versements pour vos contrats</p>';
					$ret .= amapress_get_datatable( 'options_cheques', $columns, $data,
						array(
							'paging'    => false,
							'bSort'     => false,
							'info'      => false,
							'searching' => false,
						)
					);
					$ret .= '<style>#options_cheques { display: none; }#options_cheques.opened { display: block; }</style>';
					$ret .= '<script type="text/javascript">jQuery(function($) {$("#options_cheques").addClass("closed");$("#show_options_cheques").click(function() { $("#options_cheques").toggleClass("opened"); return false; }); });</script>';

					return $ret;
				}
			),

//                        'list_quantites' => array(
//                            'name' => amapress__('Quantités'),
//                            'type' => 'show-posts',
//                            'desc' => 'Quantités',
//                            'group' => 'Distributions',
//                            'post_type' => 'amps_contrat_quant',
//                            'parent' => 'amapress_contrat_quantite_contrat_instance',
//                        ),
			'inscriptions'          => array(
				'name'                     => amapress__( 'Inscriptions' ),
				'show_column'              => true,
				'show_table'               => false,
				'hidden'                   => true,
				'group'                    => 'Inscriptions',
				'empty_text'               => 'Pas encore d\'inscriptions',
				'related_posts_count_link' => function ( $post_id ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( ! $contrat_instance ) {
						return false;
					}

					return ! $contrat_instance->isArchived();
				},
				'related_posts_count_func' => function ( $post_id ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( ! $contrat_instance ) {
						return false;
					}

					if ( $contrat_instance->isArchived() ) {
						return $contrat_instance->getArchiveInfo()['count_inscriptions'];
					} else {
						return get_posts_count( "post_type=amps_adhesion&amapress_contrat_inst=$post_id" );
					}
				},
//				'include_columns' => array(
//					'title',
//					'amapress_adhesion_quantite',
//					'amapress_adhesion_lieu',
//					'amapress_adhesion_date_debut',
//					'amapress_total_amount',
//				),
				'type'                     => 'related-posts',
				'query'                    => 'post_type=amps_adhesion&amapress_contrat_inst=%%id%%',
			),
//			'contrat'           => array(
//				'name'       => amapress__( 'Info contrat en ligne' ),
//				'type'       => 'editor',
//				'group'      => 'Pré-inscription en ligne',
//				'desc'       => 'Configurer les informations supplémentaires à afficher lors de la souscription en ligne',
//				'wpautop'    => false,
//				'searchable' => true,
//				'readonly'   => 'amapress_is_contrat_instance_readonly',
//			),
		),
	);
	$entities['contrat_quantite'] = array(
		'internal_name'    => 'amps_contrat_quant',
		'singular'         => amapress__( 'Contrat quantité' ),
		'plural'           => amapress__( 'Contrats quantités' ),
		'public'           => 'adminonly',
		'thumb'            => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'contrat_quantites',
		'quick_edit'       => false,
		'fields'           => array(
			'contrat_instance' => array(
				'name'              => amapress__( 'Contrat' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'required'          => true,
				'csv_required'      => true,
				'desc'              => 'Contrat',
				'import_key'        => true,
				'autoselect_single' => true,
				'searchable'        => true,
				'custom_csv_sample' => function ( $option, $arg ) {
					$ret = [];
					foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
						$ret[ $c->ID ] = $c->getTitle();
					}

					return $ret;
				}
			),
			'code'             => array(
				'name'         => amapress__( 'Code' ),
				'type'         => 'text',
				'csv_required' => true,
				'desc'         => 'Code',
				'import_key'   => true,
				'searchable'   => true,
			),
			'prix_unitaire'    => array(
				'name'         => amapress__( 'Prix unitaire' ),
				'type'         => 'price',
				'required'     => true,
				'csv_required' => true,
				'unit'         => '€',
				'desc'         => 'Prix unitaire',
			),
			//que distrib
			'quantite'         => array(
				'name' => amapress__( 'Facteur quantité' ),
				'type' => 'float',
//                'required' => true,
				'desc' => 'Quantité',
//                'import_key' => true,
			),
			//commandes
			'produits'         => array(
				'name'         => amapress__( 'Produits' ),
				'type'         => 'select-posts',
				'post_type'    => AmapressProduit::INTERNAL_POST_TYPE,
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'csv'          => false,
			),
			'unit'             => array(
				'name'    => amapress__( 'Unité' ),
				'type'    => 'select',
				'options' => array(
					'unit' => 'A l\'unité',
					'kg'   => 'Au kg',
					'l'    => 'Au litre',
				),
			),
			'quantite_config'  => array(
				'name'              => amapress__( 'Config quantités disponibles' ),
				'type'              => 'text',
				'csv'               => false,
				'custom_csv_sample' => function ( $option, $arg ) {
					$ret = [];

					$ret[] = '1;3;5';
					$ret[] = '1-3:0.5';
					$ret[] = '1-3;5;10';

					return $ret;
				}
			),
			'liste_dates'      => array(
				'name' => amapress__( 'Calendrier spécifique' ),
				'type' => 'multidate',
			),
		),
	);
//    $entities['contrat_paiement'] = array(
//        'internal_name' => 'amps_contrat_pmt',
//        'singular' => amapress__('Contrat paiment'),
//        'plural' => amapress__('Contrats paiements'),
//        'public' => 'adminonly',
//        'show_in_menu' => false,
//        'special_options' => array(),
//        'slug' => 'contrat_paiements',
//        'fields' => array(
//            'contrat_instance' => array(
//                'name' => amapress__('Contrat'),
//                'type' => 'select-posts',
//                'post_type' => 'amps_contrat_inst',
//                'required' => true,
//                'desc' => 'Contrat',
//            ),
//            'liste_dates' => array(
//                'name' => amapress__('Dates'),
//                'type' => 'custom',
//                'custom' => array('AmapressContrats', "displayPaiementListeDates"),
//                'save' => array('AmapressContrats', "savePaiementListeDates"),
//                'required' => true,
//                'desc' => 'Dates',
//            ),
//        ),
//    );
	return $entities;
}

add_filter( 'amapress_contrat_fields', 'amapress_contrat_fields' );
function amapress_contrat_fields( $fields ) {
	$lieux = Amapress::get_lieux();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$fields[ 'referent_' . $lieu->ID ] = array(
				'name'         => amapress__( 'Référent ' . $lieu->getShortName() ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents spécifiques',
				'readonly'     => 'amapress_is_current_user_producteur',
				'searchable'   => true,
				'autocomplete' => true,
				'desc'         => 'Référent producteur pour ' . $lieu->getTitle() . ' et spécifique pour cette production et son/ses contrat(s)',
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			);
		}
	}

	return $fields;
}

add_filter( 'amapress_import_adhesion_multi', 'amapress_import_adhesion_multi', 5, 4 );
function amapress_import_adhesion_multi( $postmulti, $postdata, $postmeta, $posttaxo ) {
	foreach ( $postmulti as $k => $v ) {
		$postmulti[ $k ] = amapress_resolve_contrat_quantite_ids( $k, $v );
	}

	return $postmulti;
}

add_filter( 'amapress_get_edit_url_for_contrat_quantite', 'amapress_get_edit_url_for_contrat_quantite' );
function amapress_get_edit_url_for_contrat_quantite( $url ) {
	return admin_url( 'edit.php?post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE );
}

add_filter( 'amapress_import_adhesion_apply_default_values_to_posts_meta', 'amapress_import_adhesion_apply_default_values_to_posts_meta' );
function amapress_import_adhesion_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] )
	     && empty( $postmeta['amapress_adhesion_contrat_instance'] ) ) {
		$postmeta['amapress_adhesion_contrat_instance'] = $_REQUEST['amapress_import_adhesion_default_contrat_instance'];
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_lieu'] )
	     && empty( $postmeta['amapress_adhesion_lieu'] ) ) {
		$postmeta['amapress_adhesion_lieu'] = $_REQUEST['amapress_import_adhesion_default_lieu'];
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_date_debut'] )
	     && empty( $postmeta['amapress_adhesion_date_debut'] ) ) {
		$postmeta['amapress_adhesion_date_debut'] = $_REQUEST['amapress_import_adhesion_default_date_debut'];
	}

//	$contrat_instance = AmapressContrat_instance::getBy( $postmeta['amapress_adhesion_contrat_instance'] );
//	if ( $postmeta['amapress_adhesion_date_debut'] < $contrat_instance->getDate_debut()
//	     || $postmeta['amapress_adhesion_date_debut'] > $contrat_instance->getDate_fin() ) {
//		$postmeta['amapress_adhesion_date_debut'] = $contrat_instance->getDate_debut();
//	}
//	$postmeta['amapress_adhesion_status'] = 'confirmed';

	return $postmeta;
}

add_filter( 'amapress_import_posts_meta', 'amapress_import_contrat_instance_meta', 50, 1 );
function amapress_import_contrat_instance_meta( $postmeta ) {
	if ( ! empty( $postmeta['amapress_contrat_instance_quant_type'] ) ) {
		foreach ( $postmeta['amapress_contrat_instance_quant_type'] as $k => $v ) {
			$postmeta[ $k ] = $v;
		}
		unset( $postmeta['amapress_contrat_instance_quant_type'] );
	}

	return $postmeta;
}


add_filter( 'amapress_import_produit_apply_default_values_to_posts_meta', 'amapress_import_produit_apply_default_values_to_posts_meta' );
function amapress_import_produit_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_produit_default_producteur'] )
	     && empty( $postmeta['amapress_produit_producteur'] ) ) {
		$postmeta['amapress_produit_producteur'] = $_REQUEST['amapress_import_produit_default_producteur'];
	}

	return $postmeta;
}

add_filter( 'amapress_import_contrat_quantite_apply_default_values_to_posts_meta', 'amapress_import_contrat_quantite_apply_default_values_to_posts_meta' );
function amapress_import_contrat_quantite_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_contrat_quantite_default_contrat_instance'] ) && empty( $postmeta['amapress_contrat_quantite_contrat_instance'] ) ) {
		$postmeta['amapress_contrat_quantite_contrat_instance'] = $_REQUEST['amapress_import_contrat_quantite_default_contrat_instance'];
	}

	if ( empty( $postmeta['amapress_contrat_quantite_quantite'] ) ) {
		$postmeta['amapress_contrat_quantite_quantite'] = 1;
	}
//    if (empty($postmeta['amapress_contrat_quantite_unit']))
//        $postmeta['amapress_contrat_quantite_quantite'] = 'unit';

	return $postmeta;
}

add_filter( 'amapress_import_adhesion_meta', 'amapress_import_adhesion_meta', 5, 4 );
function amapress_import_adhesion_meta( $postmeta, $postdata, $posttaxo, $postmulti ) {
	if ( ! empty( $postmulti ) ) {
		return $postmeta;
	}

	if ( ( isset( $postmeta['amapress_adhesion_contrat_instance'] ) && is_wp_error( $postmeta['amapress_adhesion_contrat_instance'] ) )
	     || ( isset( $postmeta['amapress_adhesion_contrat_quantite'] ) && is_wp_error( $postmeta['amapress_adhesion_contrat_quantite'] ) ) ) {
		return $postmeta;
	}

	$postmeta = apply_filters( "amapress_import_adhesion_apply_default_values_to_posts_meta", $postmeta, $postdata );
	$postmeta = apply_filters( "amapress_import_apply_default_values_to_posts_meta", $postmeta, $postdata );

	if ( empty( $postmeta['amapress_adhesion_contrat_instance'] ) ) {
		return new WP_Error( 'ignore_contrat', "Colonne contrat vide. La ligne sera ignorée." );
	}

	$quants = [];
	foreach ( $postmeta as $k => $v ) {
		if ( strpos( $k, 'contrat_quant_' ) === 0 ) {
			$quant_id = intval( substr( $k, 14 ) );
			$quant    = AmapressContrat_quantite::getBy( $quant_id );
			if ( null == $quant ) {
				return new WP_Error( 'cannot_find_quant', "Impossible de résoudre la quantité {$quant_id}" );
			}

			$v = trim( $v );
			if ( empty( $v ) ) {
				continue;
			}
			if ( Amapress::toBool( $v ) ) {
				$quants[] = $quant->getCode();
			} else {
				$quants[] = $v . ' ' . $quant->getCode();
			}
			unset( $postmeta[ $k ] );
		}
	}
	if ( ! empty( $quants ) ) {
		$postmeta['amapress_adhesion_contrat_quantite'] = implode( ', ', $quants );
	}

	if ( empty( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
		return new WP_Error( 'ignore_contrat_quantite', "Colonne quantité vide. La ligne sera ignorée." );
	}

	$contrat_instance_id = Amapress::resolve_post_id( $postmeta['amapress_adhesion_contrat_instance'], AmapressContrat_instance::INTERNAL_POST_TYPE );
	if ( empty( $contrat_instance_id ) || $contrat_instance_id <= 0 ) {
		return new WP_Error( 'cannot_find_contrat', "Impossible de trouver le contrat '{$postmeta['amapress_adhesion_contrat_instance']}'" );
	}

	$postmeta['amapress_adhesion_contrat_instance'] = $contrat_instance_id;

	$ids = amapress_resolve_contrat_quantite_ids( $contrat_instance_id, $postmeta['amapress_adhesion_contrat_quantite'] );
	if ( is_wp_error( $ids ) ) {
		return $ids;
	}

	$postmeta['amapress_adhesion_contrat_quantite']         = array_map(
		function ( $id ) {
			return $id['id'];
		}, $ids );
	$postmeta['amapress_adhesion_contrat_quantite_factors'] = array_combine(
		array_map(
			function ( $id ) {
				return $id['id'];
			}, $ids ),
		array_map(
			function ( $id ) {
				return $id['quant'];
			}, $ids )
	);

	$postmeta['amapress_adhesion_status'] = 'confirmed';

	return $postmeta;
}

add_filter( 'amapress_import_posts_meta', 'amapress_import_adhesion_meta2', 15, 4 );
function amapress_import_adhesion_meta2( $postmeta, $postdata, $posttaxo, $post_type ) {
	if ( $post_type != AmapressAdhesion::POST_TYPE ) {
		return $postmeta;
	}
	if ( ! empty( $postmulti ) ) {
		return $postmeta;
	}

	if ( is_wp_error( $postmeta ) ) {
		return $postmeta;
	}

	if ( ! isset( $postmeta['amapress_adhesion_contrat_instance'] ) ) {
		return $postmeta;
	}

	if ( empty( is_wp_error( $postmeta['amapress_adhesion_contrat_instance'] ) )
	     || empty( is_wp_error( $postmeta['amapress_adhesion_contrat_quantite'] ) )
	     || is_wp_error( $postmeta['amapress_adhesion_contrat_instance'] )
	     || is_wp_error( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
		return $postmeta;
	}

	$contrat_instance  = AmapressContrat_instance::getBy( $postmeta['amapress_adhesion_contrat_instance'] );
	$date_debut_string = isset( $postmeta['amapress_adhesion_date_debut'] ) ? $postmeta['amapress_adhesion_date_debut'] : '';
	if ( ! is_wp_error( $date_debut_string ) && ! empty( $date_debut_string ) ) {
		$date_debut = Amapress::start_of_day( $date_debut_string );
		if ( $date_debut < Amapress::start_of_day( $contrat_instance->getDate_debut() )
		     || $date_debut > Amapress::start_of_day( $contrat_instance->getDate_fin() ) ) {
			$dt            = date_i18n( 'd/m/Y', $date_debut );
			$contrat_debut = date_i18n( 'd/m/Y', $contrat_instance->getDate_debut() );
			$contrat_fin   = date_i18n( 'd/m/Y', $contrat_instance->getDate_fin() );

			return new WP_Error( 'invalid_date', "La date de début $dt est en dehors des dates ($contrat_debut - $contrat_fin) du contrat '{$contrat_instance->getTitle()}'" );
		}
	}
	$postmeta['amapress_adhesion_status'] = 'confirmed';

	return $postmeta;
}

function amapress_resolve_contrat_quantite_ids( $contrat_instance_id, $contrat_quantite_name ) {
	if ( is_string( $contrat_quantite_name ) ) {
		$contrat_quantite_name = trim( $contrat_quantite_name );
		if ( empty( $contrat_quantite_name ) ) {
			return null;
		}

		$id = amapress_resolve_contrat_quantite_id( $contrat_instance_id, $contrat_quantite_name );
		if ( $id ) {
			return [ $id ];
		}
	}

	$values = Amapress::get_array( $contrat_quantite_name );
	if ( ! is_array( $values ) ) {
		$values = array( $values );
	}

	$errors = array();
	$res    = array();
	foreach ( $values as $v ) {
//        $v = trim($v);
		$id = amapress_resolve_contrat_quantite_id( $contrat_instance_id, $v );
		if ( empty( $id ) ) {
			$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
			$url              = admin_url( "post.php?post=$contrat_instance_id&action=edit" );
			$errors[]         = "Valeur '$v' non valide pour '{$contrat_instance->getTitle()}' (Voir <$url>)";
		} else {
			$res[] = $id;
		}
	}
	if ( ! empty( $errors ) ) {
		return new WP_Error( 'cannot_parse', implode( ' ; ', $errors ) );
	}

//	if ( count( $res ) == 1 ) {
	return $res;
//	} else {
//		return $res;
//	}
}

//add_filter('amapress_resolve_contrat_quantite_id','amapress_resolve_contrat_quantite_id', 10, 2);
function amapress_resolve_contrat_quantite_id( $contrat_instance_id, $contrat_quantite_name ) {
	if ( is_wp_error( $contrat_quantite_name ) ) {
		return null;
	}

	$quants = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
	if ( ! empty( $quants ) && count( $quants ) == 1 && Amapress::toBool( $contrat_quantite_name ) ) {
		$fquant                = from( $quants )->first();
		$contrat_quantite_name = $fquant->getCode();
	}
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
//    $cn = $contrat_quantite_name;
	$contrat_quantite_name = wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $contrat_quantite_name ) ) );
	if ( empty( $contrat_quantite_name ) ) {
		return null;
	}

	foreach ( $quants as $quant ) {
		if ( ! empty( $quant->getCode() ) && strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getCode() ) ) ), $contrat_quantite_name ) === 0 ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getSlug() ) ) ), $contrat_quantite_name ) === 0 ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getTitle() ) ) ), $contrat_quantite_name ) === 0 ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		} else if ( abs( $quant->getQuantite() ) > 0.001 && str_replace( ',', '.', strval( $quant->getQuantite() ) ) == str_replace( ',', '.', $contrat_quantite_name ) ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		}

		if ( $contrat_instance->isQuantiteVariable() ) {
			foreach ( $quant->getQuantiteOptions() as $raw => $fmt ) {
				if ( empty( $raw ) ) {
					continue;
				}
				foreach ( [ $raw, $fmt, $raw . ' ', $fmt . ' ', $raw . ' x ', $fmt . ' x ' ] as $prefix ) {
					if ( ! empty( $quant->getCode() ) && strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $prefix . $quant->getCode() ) ) ), $contrat_quantite_name ) === 0 ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $prefix . $quant->getSlug() ) ) ), $contrat_quantite_name ) === 0 ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $prefix . $quant->getTitle() ) ) ), $contrat_quantite_name ) === 0 ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					} else if ( abs( $quant->getQuantite() ) > 0.001 && str_replace( ',', '.', strval( $quant->getQuantite() ) ) == str_replace( ',', '.', $contrat_quantite_name ) ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					}
				}
			}
		}
	}
//    var_dump($contrat_quantite_name);
//    var_dump($cn);
//    die();
	return null;
}

function amapress_quantite_editor_line( AmapressContrat_instance $contrat_instance, $id, $title, $code, $description, $price, $unit, $quantite_conf, $quantite, $produits, $photo, $liste_dates, $max_adhs, $grp_mult ) {
	if ( $contrat_instance->getModel() == null ) {
		return '';
	}

	$contrat_produits = array();
	if ( ! empty( $contrat_instance->getModel()->getProducteur() ) ) {
		foreach ( $contrat_instance->getModel()->getProducteur()->getProduits() as $prod ) {
			$contrat_produits[ $prod->ID ] = $prod->getTitle();
		}
	}
	echo '<tr style="vertical-align: top">';
	echo "<td><input style='width: 100%' type='text' class='required' name='amapress_quant_data[$id][title]' placeholder='Intitulé' value='$title' />";
	echo "<br/><em title='Abbréviation ou code pour affichage sur la liste d&apos;émargement'>Code liste émargement</em>:<input style='width: 100%' type='text' class='' name='amapress_quant_data[$id][code]' placeholder='Code' value='$code' /></td>";
	echo "<td><textarea style='width: 100%; height: 100%' rows='3' class='' name='amapress_quant_data[$id][desc]' placeholder='Description'>{$description}</textarea></td>";
	echo "<td><input style='width: 5em' type='number' class='required number' name='amapress_quant_data[$id][price]' min='0' step='0.01' placeholder='Prix unitaire' value='$price' /></td>";
	echo "<td><input style='width: 100%' type='number' class='required number' name='amapress_quant_data[$id][quant]' min='0' step='0.01' placeholder='Facteur quantité' value='$quantite' /></td>";
	if ( $contrat_instance->isPanierVariable() || $contrat_instance->isQuantiteVariable() ) {
		if ( empty( $unit ) ) {
			$unit = 'unit';
		}
		echo "<td><select style='width: 100%' class='required' name='amapress_quant_data[$id][unit]'>";
		echo '<option value="">--Unité de prix--</option>';
		echo '<option ' . selected( 'unit', $unit, false ) . ' value="unit">pièce</option>';
		echo '<option ' . selected( 'kg', $unit, false ) . ' value="kg">kg</option>';
		echo '<option ' . selected( 'l', $unit, false ) . ' value="l">L</option>';
		echo '</select></td>';
		echo "<td><input style='width: 100%' type='text' class='text' name='amapress_quant_data[$id][quant_conf]' placeholder='Config' value='$quantite_conf' />
<br/>Grp. Multiple:<input type='number' class='required number' name='amapress_quant_data[$id][grp_mult]' min='0' step='1' placeholder='Multiple' value='$grp_mult' /></td>";
	}
	$all_liste_dates_options = [];
	foreach ( $contrat_instance->getListe_dates() as $d ) {
		$v                             = date_i18n( TitanFrameworkOptionDate::$default_date_format, intval( $d ) );
		$all_liste_dates_options[ $v ] = $v;
	}
	$liste_dates_options = [];
	foreach ( $liste_dates as $d ) {
		$v                         = date_i18n( TitanFrameworkOptionDate::$default_date_format, intval( $d ) );
		$liste_dates_options[ $v ] = $v;
	}
	?>
    <td><select style='width: 5em' id="<?php echo "amapress_quant_data[$id][liste_dates]" ?>"
                name="<?php echo "amapress_quant_data[$id][liste_dates][]"; ?>"
                class="quant-dates" multiple="multiple"
                data-placeholder="Dates spé.">
			<?php
			tf_parse_select_options( $all_liste_dates_options, $liste_dates_options );
			?>
        </select>
    </td>
	<?php
	echo "<td><input style='width: 100%' type='number' class='required number' name='amapress_quant_data[$id][max_adhs]' min='0' step='1' placeholder='Adhérents' value='$max_adhs' /></td>";
	?>
    <td><select style='width: 5em' id="<?php echo "amapress_quant_data[$id][produits]" ?>"
                name="<?php echo "amapress_quant_data[$id][produits][]"; ?>"
                class="quant-produit" multiple="multiple"
                data-placeholder="Produits associés">
			<?php
			tf_parse_select_options( $contrat_produits, $produits );
			?>
        </select>
    </td>
	<?php
//	echo "<td class='quant-upload'  style='border-top: 1pt solid #8c8c8c; border-collapse: collapse'>";
//	TitanFrameworkOptionUpload::echo_uploader( "amapress_quant_data[$id][photo]", $photo, '' );
//	echo "</td>";
	if ( amapress_can_delete_contrat_quantite( '', $id ) === true ) {
		echo "<td><span class='btn del-model-tab dashicons dashicons-dismiss' onclick='amapress_del_quant(this)'></span></td>";
	} else {
		echo "<td></td>";
	}
	echo '</tr>';
}

function amapress_get_contrat_quantite_editor( $contrat_instance_id ) {
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( $contrat_instance->getModel() == null ) {
		return '';
	}

//	<p>Pour importer des Quantités/Taille de panier depuis un excel, veuillez utiliser <a
	/*                        href="<?php echo admin_url( 'admin.php?page=amapress_import_page&tab=import_quant_paniers&amapress_import_contrat_quantite_default_contrat_instance=' . $contrat_instance->ID ); ?>"*/
//                        target="_blank" class="button button-secondary">Import CSV</a> (en indiquant
	/*                "<?php echo esc_html( $contrat_instance->getTitle() ); ?>" dans la colonne Contrat ou le choisissant*/
//                comme contrat par défaut)</p>

	ob_start();
	?>
    <p style="padding: 20px 10px 20px 0;"><strong>Configuration des paniers (Taille/Quantités)</strong></p>
    <p>Créer un panier à partir d’un modèle Excel <a
                href="<?php echo admin_url( 'admin.php?page=amapress_import_page&tab=import_quant_paniers&amapress_import_contrat_quantite_default_contrat_instance=' . $contrat_instance->ID ); ?>"
                target="_blank" class="button button-secondary">Import CSV</a></p>
    <p style="padding-bottom: 20px"><span class="btn add-model dashicons dashicons-plus-alt"
                                          onclick="amapress_add_quant(this)"></span> Ajouter une quantité</p>
    <input type="hidden" name="amapress_quant_data_contrat_instance_id"
           value="<?php echo $contrat_instance_id; ?>"/>
    <table id="quant_editor_table" class="table" style="width: 100%; border: 1pt solid black">
        <thead>
        <tr>
            <th style="padding-left: 10px">Intitulé*</th>
            <th title="Description">Desc.</th>
            <th style="width: 50px">Prix*</th>
            <th style="width: 50px"
                title="Facteur quantité: par ex, 0.5 pour demi panier et 1 pour panier. 0 si non utile pour le contrat">
                Fact. quant.<span class="dashicons dashicons-editor-help"></span>
            </th>
			<?php if ( $contrat_instance->isPanierVariable() || $contrat_instance->isQuantiteVariable() ) { ?>
                <th style="width: 60px">Unité*</th>
                <th style="width: 70px"
                    title="Options de quantités possibles, par ex : 1-3;5;10 pour autoriser 1,2,3,5,10">Quantités config<span
                            class="dashicons dashicons-editor-help"></span>
                </th>
			<?php } ?>
            <th title="Dates spécifiques de distribution du type de panier"
            >Dates spec.<span class="dashicons dashicons-editor-help"></span>
            </th>
            <th style="width: 50px">Max Adhs.</th>
            <th>Produits</th>
            <!--            <th style="width: 30px">Photo</th>-->
            <th style="width: 30px"></th>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance_id ) as $quant ) {
			$id   = $quant->ID;
			$tit  = esc_attr( $quant->getTitle() );
			$q    = esc_attr( $quant->getQuantite() );
			$c    = esc_attr( $quant->getCode() );
			$pr   = esc_attr( $quant->getPrix_unitaire() );
			$qc   = esc_attr( $quant->getQuantiteConfig() );
			$desc = esc_textarea( stripslashes( $quant->getDescription() ) );

			amapress_quantite_editor_line( $contrat_instance, $id, $tit, $c, $desc, $pr, $quant->getPriceUnit(),
				$qc, $q, implode( ',', $quant->getProduitsIds() ), get_post_thumbnail_id( $quant->ID ),
				$quant->getSpecificDistributionDates(), $quant->getMaxAdherents(), $quant->getGroupMultiple() );
		}
		?>
        </tbody>
    </table>
    <p class="description"><a
                href="https://wiki.amapress.fr/contrats/exemple_paniers" target="_blank">* Consulter les instructions
            spécifiques et exemples</a></p>
    <p class="description">Pour les produits <strong>payables au poids à la livraison</strong>, indiquez un prix
        unitaire de <strong>0</strong>.</p>
    <p class="description">La colonne <strong>Fact. quant.</strong> permet d'indiquer la quantité totale à fournir au
        producteur :
        <br/>- par exemple, pour un contrat légumes, 0.5 pour un demi panier, 1 pour un panier, 2 pour un double ; 3
        demi paniers + 2 paniers = 3.5 équivalent panier (3 * 0.5 + 2 * 1)
        <br/>- par exemple pour un contrat oeufs, 6 pour une boîte de 6, etc..
        <br/><strong>Pour tous les autres producteurs, vous pouvez laisser cette colonne à 0</strong>
    </p>
	<?php
	$contents = ob_get_clean();

	ob_start();
	amapress_quantite_editor_line( $contrat_instance, '%%id%%', '', '', '', 0, 0,
		'', 0, '', '', [], 0, 1 );

	$new_row = ob_get_clean();

	$new_row  = json_encode( array( 'html' => $new_row ) );
	$contents .= "<script type='text/javascript'>//<![CDATA[
    jQuery(function($) {
        amapress_quant_load_tags();
    });
    function amapress_quant_load_tags() {
        jQuery('.quant-dates').select2MultiCheckboxes({
            templateSelection: function(selected, total) {
              return selected.length + \" sur \" + total;
            }
          });
        jQuery('.quant-produit').select2({
            allowClear: true,
              escapeMarkup: function(markup) {
        return markup;
    },
              templateResult: function(data) {
        return jQuery('<span>'+data.text+'</span>');
    },
              templateSelection: function(data) {
        return jQuery('<span>'+data.text+'</span>');
    }
        });
    }
    function amapress_add_quant(e) {
        var max = jQuery(e).data('max') || 0;
        max -= 1;
        jQuery(e).data('max', max);
        var html = {$new_row}['html'];
        html = html.replace(/%%id%%/g, max);
        jQuery('#quant_editor_table tbody').append(jQuery(html));
        amapress_quant_load_tags();
    };
    function amapress_del_quant(e) {
        if (!confirm('Voulez-vous vraiment supprimer cette quantité ?')) return;
        jQuery(e).closest('tr').remove();
    };
    //]]>
</script>";

	return $contents;
}

function amapress_save_contrat_quantite_editor( $contrat_instance_id ) {
	if ( isset( $_POST['amapress_quant_data'] ) && isset( $_POST['amapress_quant_data_contrat_instance_id'] ) ) {
		$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
		if ( $contrat_instance && $contrat_instance->isArchived() ) {
			unset( $_POST['amapress_quant_data'] );

			return;
		}

		$quants     = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
		$quants_ids = array_map( function ( $q ) {
			return $q->ID;
		}, $quants );

		foreach ( array_diff( $quants_ids, array_keys( $_POST['amapress_quant_data'] ) ) as $qid ) {
			wp_delete_post( $qid );
		}
		foreach ( $_POST['amapress_quant_data'] as $quant_id => $quant_data ) {
			$my_post = array(
				'post_title'   => $quant_data['title'],
				'post_type'    => AmapressContrat_quantite::INTERNAL_POST_TYPE,
				'post_content' => '',
				'post_status'  => 'publish',
				'meta_input'   => array(
					'amapress_contrat_quantite_contrat_instance' => $contrat_instance_id,
					'amapress_contrat_quantite_prix_unitaire'    => $quant_data['price'],
					'amapress_contrat_quantite_code'             => ! empty( $quant_data['code'] ) ? $quant_data['code'] : $quant_data['title'],
					'amapress_contrat_quantite_description'      => $quant_data['desc'],
					'amapress_contrat_quantite_quantite_config'  => isset( $quant_data['quant_conf'] ) ? $quant_data['quant_conf'] : null,
					'amapress_contrat_quantite_grp_mult'         => isset( $quant_data['grp_mult'] ) ? $quant_data['grp_mult'] : null,
					'amapress_contrat_quantite_unit'             => isset( $quant_data['unit'] ) ? $quant_data['unit'] : null,
					'amapress_contrat_quantite_produits'         => isset( $quant_data['produits'] ) ? $quant_data['produits'] : null,
					'amapress_contrat_quantite_liste_dates'      => ! empty( $quant_data['liste_dates'] ) ? $quant_data['liste_dates'] : null,
					'amapress_contrat_quantite_quantite'         => isset( $quant_data['quant'] ) ? $quant_data['quant'] : null,
					'amapress_contrat_quantite_max_adhs'         => $quant_data['max_adhs'],
					'_thumbnail_id'                              => isset( $quant_data['photo'] ) ? $quant_data['photo'] : null,
				),
			);
			if ( $quant_id < 0 ) {
				wp_insert_post( $my_post );
			} else {
				$my_post['ID'] = $quant_id;
//                $my_post['post_status'] = 'publish';
				$r = wp_update_post( $my_post );
			}
		}
		unset( $_POST['amapress_quant_data'] );
	}
}

add_filter( 'amapress_can_delete_contrat', 'amapress_can_delete_contrat', 10, 2 );
function amapress_can_delete_contrat( $can, $post_id ) {
	return count( AmapressContrats::get_all_contrat_instances_by_contrat_ids( $post_id ) ) == 0;
}

add_filter( 'amapress_can_delete_contrat_instance', 'amapress_can_delete_contrat_instance', 10, 2 );
function amapress_can_delete_contrat_instance( $can, $post_id ) {
	return count( AmapressContrats::get_all_adhesions( $post_id ) ) == 0;
}

add_filter( 'amapress_can_delete_contrat_quantite', 'amapress_can_delete_contrat_quantite', 10, 2 );
function amapress_can_delete_contrat_quantite( $can, $post_id ) {
	return count( AmapressContrats::get_all_adhesions( null, $post_id ) ) == 0;
}

add_action( 'amapress_row_action_contrat_instance_renew', 'amapress_row_action_contrat_instance_renew' );
function amapress_row_action_contrat_instance_renew( $post_id ) {
	$contrat_inst         = AmapressContrat_instance::getBy( $post_id );
	$new_contrat_instance = $contrat_inst->cloneContrat();
	if ( ! $new_contrat_instance ) {
		wp_die( 'Une erreur s\'est produit lors du renouvellement du contrat. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_renew_same_period', 'amapress_row_action_contrat_instance_renew_same_period' );
function amapress_row_action_contrat_instance_renew_same_period( $post_id ) {
	$contrat_inst         = AmapressContrat_instance::getBy( $post_id );
	$new_contrat_instance = $contrat_inst->cloneContrat( true, true, true );
	if ( ! $new_contrat_instance ) {
		wp_die( 'Une erreur s\'est produit lors du renouvellement du contrat. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_clone', 'amapress_row_action_contrat_instance_clone' );
function amapress_row_action_contrat_instance_clone( $post_id ) {
	$contrat_inst = AmapressContrat_instance::getBy( $post_id );
	if ( $contrat_inst->isArchived() ) {
		wp_die( 'Impossible de dupliquer un contrat archivé' );
	}

	$new_contrat_instance = $contrat_inst->cloneContrat( true, false );
	if ( ! $new_contrat_instance ) {
		wp_die( 'Une erreur s\'est produit lors de la duplication du contrat. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

/** @param TitanFrameworkOption $option */
function amapress_is_contrat_instance_readonly( $option ) {
	if ( TitanFrameworkOption::isOnNewScreen() ) {
		return false;
	}

	$contrat_instance_id = $option->getPostID();
	if ( ! $contrat_instance_id ) {
		return false;
	}
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat_instance ) {
		return false;
	}
	if ( $contrat_instance->isArchived() ) {
		return true;
	}


	if ( isset( $_REQUEST['adv'] ) || isset( $_REQUEST['full_edit'] ) ) {
		return false;
	}
	$referer = parse_url( wp_get_referer() );
	if ( isset( $referer['query'] ) ) {
		parse_str( $referer['query'], $path );
		if ( ( isset( $path['adv'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) ) {
			return false;
		}
	}

	$adhs = AmapressContrats::get_active_adhesions( $contrat_instance_id );

	return ! empty( $adhs );
}

function amapress_modify_post_mime_types( $post_mime_types ) {

	$post_mime_types['application/pdf']                                                         = array(
		__( 'PDFs' ),
		__( 'Gérer les PDFs' ),
		_n_noop( 'PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>' )
	);
	$post_mime_types['application/vnd.openxmlformats-officedocument.wordprocessingml.document'] = array(
		__( 'Documents' ),
		__( 'Gérer les Documents' ),
		_n_noop( 'Document <span class="count">(%s)</span>', 'Documents <span class="count">(%s)</span>' )
	);

	return $post_mime_types;

}

add_filter( 'post_mime_types', 'amapress_modify_post_mime_types' );

add_action( 'amapress_row_action_contrat_instance_generate_contrat', 'amapress_row_action_contrat_instance_generate_contrat' );
function amapress_row_action_contrat_instance_generate_contrat( $post_id ) {
	$adhesion       = AmapressContrat_instance::getBy( $post_id );
	$date           = isset( $_GET['start_date'] ) ? intval( $_GET['start_date'] ) : amapress_time();
	$full_file_name = $adhesion->generateContratDoc( $date, true );
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'amapress_row_action_contrat_instance_open_inscr', 'amapress_row_action_contrat_instance_open_inscr' );
function amapress_row_action_contrat_instance_open_inscr( $post_id ) {
	$contrat = AmapressContrat_instance::getBy( $post_id );
	$contrat->setSelfSubscribe( true );
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_contrat_instance_close_inscr', 'amapress_row_action_contrat_instance_close_inscr' );
function amapress_row_action_contrat_instance_close_inscr( $post_id ) {
	$contrat = AmapressContrat_instance::getBy( $post_id );
	$contrat->setSelfSubscribe( false );
	wp_redirect_and_exit( wp_get_referer() );
}

add_filter( 'amapress_can_delete_attachment', 'amapress_can_delete_attachment', 10, 2 );
function amapress_can_delete_attachment( $can, $post_id ) {
	$key         = 'amapress_can_delete_attachment';
	$attachments = wp_cache_get( $key );
	if ( false === $attachments ) {
		$attachments            = [];
		$attachments[]          = Amapress::getOption( 'default_word_model' );
		$attachments[]          = Amapress::getOption( 'default_word_paper_model' );
		$attachments[]          = Amapress::getOption( 'default_word_modulable_model' );
		$attachments[]          = Amapress::getOption( 'default_word_modulable_paper_model' );
		$attachments[]          = Amapress::getOption( 'default_word_modulable_group_model' );
		$attachments[]          = Amapress::getOption( 'default_word_modulable_group_paper_model' );
		$single_attachment_keys = array(
			'amapress_contrat_instance_word_model',
			'amapress_contrat_instance_word_paper_model',
			'amapress_adhesion_period_word_model',
		);
		global $wpdb;
		$meta_query = [];
		foreach ( $single_attachment_keys as $single_attachment_key ) {
			$meta_query[] = $wpdb->prepare( "($wpdb->postmeta.meta_key = %s)", $single_attachment_key );
		}

		$where  = implode( ' OR ', $meta_query );
		$values = amapress_get_col_cached( "SELECT DISTINCT $wpdb->postmeta.meta_value FROM $wpdb->postmeta WHERE $where" );
		foreach ( $values as $v ) {
			$v = maybe_unserialize( $v );
			if ( is_array( $v ) ) {
				$attachments = array_merge( $attachments, $v );
			} else {
				$attachments[] = $v;
			}
		}
		$attachments = array_unique( array_map( 'intval', $attachments ) );
		wp_cache_set( $key, $attachments );
	}

	return ! in_array( $post_id, $attachments );
}


add_filter( 'amapress_can_edit_contrat_instance', function ( $can, $post_id ) {
	if ( is_admin() && amapress_can_access_admin() && ! amapress_is_admin_or_responsable() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		if ( count( $refs ) > 0 ) {
			$contrat_instance = AmapressContrat_instance::getBy( $post_id );
			$model_id         = TitanFrameworkOption::isOnNewScreen()
			                    && isset( $_POST['amapress_contrat_instance_model'] ) ?
				intval( $_POST['amapress_contrat_instance_model'] ) :
				0;
			if ( ! $model_id && $contrat_instance ) {
				$model_id = $contrat_instance->getModelId();
			}
			foreach ( $refs as $r ) {
				if ( in_array( $model_id, $r['contrat_ids'] ) ) {
					return $can;
				}
			}

			return false;
		}
	}

	return $can;
}, 10, 2 );

add_filter( 'amapress_can_edit_contrat', function ( $can, $post_id ) {
	if ( is_admin() && amapress_can_access_admin() && ! amapress_is_admin_or_responsable() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		if ( count( $refs ) > 0 ) {
			foreach ( $refs as $r ) {
				if ( in_array( $post_id, $r['contrat_ids'] ) ) {
					return $can;
				}
			}

			return false;
		}
	}

	return $can;
}, 10, 2 );

add_action( 'delete_post', function ( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( AmapressContrat_instance::INTERNAL_POST_TYPE == $post_type ) {
		$quants_ids = get_posts(
			[
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_type'      => AmapressContrat_quantite::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_quantite_contrat_instance',
						'value'   => $post_id,
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				)
			]
		);
		foreach ( $quants_ids as $id ) {
			wp_delete_post( $id );
		}
	}
}, 1000 );

add_filter( 'amapress_row_actions_label_contrat_instance', function ( $abel ) {
	return '';
} );

function amapress_contrat_instance_archivables_view() {
	$columns = array(
		array(
			'title' => 'Contrat',
			'data'  => array(
				'_'    => 'contrat',
				'sort' => 'contrat',
			)
		),
		array(
			'title' => '',
			'data'  => 'archive'
		),
	);

	$data = array();
	foreach ( AmapressContrat_instance::getAll() as $contrat_instance ) {
		if ( ! $contrat_instance->canBeArchived() ) {
			continue;
		}

		$archive_link = add_query_arg(
			array(
				'action'     => 'archive_contrat',
				'contrat_id' => $contrat_instance->ID,
			),
			admin_url( 'admin-post.php' )
		);
		$data[]       = array(
			'contrat' => Amapress::makeLink( $contrat_instance->getAdminEditLink(), $contrat_instance->getTitle(), true, true ),
			'archive' => Amapress::makeLink( $archive_link, 'Archiver' ),
		);
	}

	return amapress_get_datatable( 'contrat-archivables-table', $columns, $data );
}

add_action( 'admin_post_archive_contrat', function () {
	$contrat_id = isset( $_REQUEST['contrat_id'] ) ? intval( $_REQUEST['contrat_id'] ) : 0;
	$contrat    = AmapressContrat_instance::getBy( $contrat_id );
	if ( empty( $contrat ) ) {
		wp_die( 'Contrat inconnu' );
	}

	if ( ! current_user_can( 'edit_contrat_instance', $contrat_id ) ) {
		wp_die( 'Vous n\'avez pas le droit d\'archiver ce contrat' );
	}

	if ( $contrat->isArchived() ) {
		wp_die( 'Contrat déjà archivé' );
	}

	if ( ! $contrat->canBeArchived() ) {
		wp_die( 'Contrat non archivable' );
	}

	if ( ! isset( $_REQUEST['confirm'] ) ) {
		echo '<p>Etes-vous sûr de vouloir archiver le contrat ' . esc_html( $contrat->getTitle() ) . ' ? 
<br />
<a href = "' . add_query_arg( 'confirm', 'yes' ) . '"> Confirmer l\'archivage</a>';
		die();
	}

	if ( 'yes' != $_REQUEST['confirm'] ) {
		wp_die( 'Archivage du contrat ' . esc_html( $contrat->getTitle() ) . ' abandonné.' );
	}

	$contrat->archive();

	echo '<p style="color: green">Archivage effectué</p>';

	echo '<p><a href="' . esc_attr( admin_url( 'admin.php?page=contrats_archives' ) ) . '">Retour à la liste des contrats archivables</a></p>';
	die();
} );

add_filter( 'amapress_bulk_action_amp_incr_cloture', 'amapress_bulk_action_amp_incr_cloture', 10, 2 );
function amapress_bulk_action_amp_incr_cloture( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$contrat_instance = AmapressContrat_instance::getBy( $post_id );
		$contrat_instance->addClotureDays( 1 );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_filter( 'amapress_bulk_action_amp_decr_cloture', 'amapress_bulk_action_amp_decr_cloture', 10, 2 );
function amapress_bulk_action_amp_decr_cloture( $sendback, $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		$contrat_instance = AmapressContrat_instance::getBy( $post_id );
		$contrat_instance->addClotureDays( - 1 );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}