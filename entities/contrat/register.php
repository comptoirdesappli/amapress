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
		'singular'                 => __( 'Production', 'amapress' ),
		'plural'                   => __( 'Productions', 'amapress' ),
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
			__( 'Producteur', 'amapress' ) => [
				'context' => 'side',
			],
		],
		'edit_header'              => function ( $post ) {
			$contrat = AmapressContrat::getBy( $post );
			if ( TitanFrameworkOption::isOnEditScreen() ) {
				if ( empty( $contrat->getProducteur() ) ) {
					echo '<div class="notice notice-error"><p>' . __( 'Production invalide : pas de producteur associée', 'amapress' ) . '</p></div>';
				}
			}
			if ( empty( $contrat->getAllReferentsIds() ) ) {
				echo '<div class="notice notice-error"><p>' . __( 'Production sans référent', 'amapress' ) . '</p></div>';
			}

			TitanFrameworkOption::echoFullEditLinkAndWarning();

			echo '<h2>' . __( 'Présentation de la production <em>(Légumes, Champignons, Pains...)</em>', 'amapress' ) . '</h2>';
		},
		'fields'                   => array(
//			'amapress_icon_id' => array(
//				'name'    => __( 'Icône', 'amapress' ),
//				'type'    => 'upload',
//				'group'   => __('Information', 'amapress'),
//				'desc'    => __('Icône', 'amapress'),
//				'bare_id' => true,
//			),
//            'presentation' => array(
//                'name' => amapress__('Présentation'),
//                'type' => 'editor',
//                'required' => true,
//                'desc' => __('Présentation', 'amapress'),
//            ),
//            'nb_visites' => array(
//                'name' => amapress__('Nombre de visites obligatoires'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => __('Nombre de visites obligatoires', 'amapress'),
//            ),
//            'max_adherents' => array(
//                'name' => amapress__('Nombre de maximum d\'adhérents'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => __('Nombre de maximum d\'adhérents', 'amapress'),
//            ),
			'producteur'    => array(
				'name'              => __( 'Producteur', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'required'          => true,
				'desc'              => __( 'Producteur', 'amapress' ),
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'group'             => __( 'Producteur', 'amapress' ),
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => __( 'Toutes les producteurs', 'amapress' ),
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
			'referent'      => array(
				'name'         => __( 'Référent', 'amapress' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => __( '2/ Référents spécifiques', 'amapress' ),
				'desc'         => __( 'Référent producteur pour tous les lieux et spécifique pour cette production et son/ses contrat(s)', 'amapress' ),
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent2'     => array(
				'name'         => __( 'Référent 2', 'amapress' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => __( '2/ Référents spécifiques', 'amapress' ),
				'desc'         => __( 'Deuxième référent producteur pour tous les lieux et spécifique pour cette production et son/ses contrat(s)', 'amapress' ),
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent3'     => array(
				'name'         => __( 'Référent 3', 'amapress' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => __( '2/ Référents spécifiques', 'amapress' ),
				'desc'         => __( 'Troisième référent producteur pour tous les lieux et spécifique pour cette production et son/ses contrat(s)', 'amapress' ),
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'code'          => array(
				'name'           => __( 'Code', 'amapress' ),
				'group'          => __( 'Liste émargement', 'amapress' ),
				'type'           => 'text',
				'desc'           => __( 'Code de la production pour la liste d\'émargement (par défaut, titre de cette production)', 'amapress' ),
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'contrats'      => array(
				'name'            => __( 'Contrats', 'amapress' ),
				'show_column'     => true,
				'group'           => __( 'Contrats', 'amapress' ),
				'include_columns' => array(
					'title',
					'amapress_contrat_instance_name',
					'amapress_contrat_instance_type',
				),
				'type'            => 'related-posts',
				'query'           => 'post_type=amps_contrat_inst&amapress_date=active&amapress_contrat=%%id%%',
			),
			'instr_distrib' => array(
				'name'       => __( 'Instructions de distribution', 'amapress' ),
				'type'       => 'editor',
				'required'   => false,
				'group'      => __( '3/ Distributions', 'amapress' ),
				'desc'       => __( 'Instructions de distribution de ce contrat', 'amapress' ),
				'searchable' => true,
			),
		),
	);
	$entities['contrat_instance'] = array(
		'internal_name'            => 'amps_contrat_inst',
		'singular'                 => __( 'Modèle de contrat', 'amapress' ),
		'plural'                   => __( 'Modèles de contrat', 'amapress' ),
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
			__( 'Statut', 'amapress' ) => [
				'context' => 'side',
			],
		),
		'edit_header'              => function ( $post ) {
			$contrat = AmapressContrat_instance::getBy( $post );
			if ( TitanFrameworkOption::isOnEditScreen() ) {
				if ( empty( $contrat->getModel() ) ) {
					echo '<div class="notice notice-error"><p>' . __( 'Modèle de contrat invalide : pas de production associée', 'amapress' ) . '</p></div>';
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
				if ( $contrat->getPayByMonth() ) {
					$deliv_dates    = $contrat->getDatesByMonth( $contrat->getDate_debut() );
					$pmt_dates      = $contrat->getPaiementDatesByMonth();
					$missing_months = array_diff( array_keys( $deliv_dates ), array_keys( $pmt_dates ) );
					if ( ! empty( $missing_months ) ) {
						echo '<div class="notice notice-error"><p>' .
						     sprintf( __( 'Il n\'y a pas de date de paiement pour les mois suivants : %s. <br/><strong>La répartition des dates de paiement ne fonctionnera pas correctement.</strong>', 'amapress' ),
							     implode( ', ', $missing_months ) ) . '</p></div>';
					}
				} else {
					if ( $max_nb_paiements > $nb_dates_paiements ) {
						echo '<div class="notice notice-warning"><p>' .
						     sprintf( __( 'Il y a moins de dates de paiement renseignées (%d) que le nombre de règlements maximum autorisé (%d). <br/><strong>La répartition des dates de paiement ne fonctionnera pas correctement.</strong>', 'amapress' ),
							     $nb_dates_paiements, $max_nb_paiements ) . '</p></div>';
					}
				}

				if ( empty( AmapressContrats::get_contrat_quantites( $post->ID ) ) ) {
					$class   = 'notice notice-error';
					$message = __( '<a href="#amp_config_paniers">Vous devez configurer les quantités et tarifs des paniers</a>', 'amapress' );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
				}

				echo '<h4>' . __( 'EDITER', 'amapress' ) . '</h4>';

				if ( $contrat->isArchived() ) {
					echo '<p style="color: red">' . __( 'Contrat archivé. Pas de modification possible.', 'amapress' ) . '</p>';
				}

				$adhs = AmapressContrats::get_active_adhesions( $post->ID );
				if ( ! empty( $adhs ) ) {
					if ( isset( $_REQUEST['adv'] ) || isset( $_REQUEST['full_edit'] ) ) {
						echo '<p style="color: red"><span class="dashicons dashicons-warning"></span> Édition d’un contrat actif</p>';
						if ( $contrat->isArchived() ) {
							wp_die( __( 'Edition d\'un contrat archivée impossible', 'amapress' ) );
						}
					}

					if ( ! $contrat->isArchived() ) {
						TitanFrameworkOption::echoFullEditLinkAndWarning(
							__( 'Edition avancée', 'amapress' ), ''
						);

						echo '<p>' . sprintf( __( 'Modifier ce contrat peut impacter les %s inscriptions associées. 
<span class="description">(Par ex : si vous changez le nombre de dates de distribution, le montant de l\'inscription sera adapté et les quantités saisies dans le cas d\'un contrat modulable seront perdues.)</span>', 'amapress' ), count( $adhs ) ) . '</p>';
//				echo '<p>Ce contrat a déjà des inscriptions. Modifier ce contrat peut impacter les ' . count( $adhs ) . ' inscriptions associées. Par exemple si vous changez le nombre de dates de distribution le montant de l\'inscription sera adapté et les quantités saisies dans le cas d\'un contrat avec quantités variables peuvent être perdues.</p>';
//				if ( ! isset( $_REQUEST['adv'] ) ) {
////					echo '<p>Si vous voulez malgrès tout éditer le contrat, utiliser le lien suivant : <a href="' . esc_attr( add_query_arg( 'adv', 'true' ) ) . '">Confirmer l\'édition.</a></p>';
//					echo '<p><a href="' . esc_attr( add_query_arg( 'adv', 'true' ) ) . '">Confirmer l\'édition</a></p>';
//				}

						echo '<p>' . sprintf( __( '<a href="%s">Dupliquer</a> : Créer un nouveau contrat - Durée et période identiques <span class="description">(Par ex : Semaine A - Semaine B)</span>', 'amapress' ), amapress_get_row_action_href( 'clone', $post->ID ) ) . '</p>';
					}
				}

				echo '<h4>' . __( 'CONSULTER', 'amapress' ) . '</h4>';
				if ( count( $adhs ) > 0 ) {
					echo '<p>' . sprintf( __( '<a target="_blank" href="%s">La liste des %s adhérent(s) inscrits à ce contrat</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_adhesion&amapress_contrat_inst=' . $post->ID ), count( $adhs ) ) . '</p>';
				}
				echo '<p>' . sprintf( __( '<a target="_blank" href="%s">Les statistiques annuelles</a>', 'amapress' ), admin_url( 'admin.php?amp_stats_contrat=' . $post->ID . '&page=contrats_quantites_stats' ) ) . '</p>';

				if ( ! $contrat->isArchived() ) {
					echo '<h4>' . __( 'EXPORTER FICHIERS', 'amapress' ) . '</h4>';
					echo '<p>';
					echo '<a href="' . AmapressExport_Posts::get_export_url( null, admin_url( 'edit.php?post_type=amps_adhesion&amapress_contrat_inst=' . $post->ID . '&amapress_export=csv' ) ) . '">' . __( 'Adhérents (XLSX)', 'amapress' ) . '</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=paiement_table_xlsx&contrat=' . $post->ID ) . '">' . __( 'Chèques/règlements (XLSX)', 'amapress' ) . '</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=paiement_table_pdf&contrat=' . $post->ID ) . '">' . __( 'Chèques/règlements (PDF)', 'amapress' ) . '</a>';
					echo '<a href="' . admin_url( 'admin-post.php?action=delivery_table_xlsx&type=group_date&contrat=' . $post->ID ) . '">' . __( 'Livraisons par dates (XLSX)', 'amapress' ) . '</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=delivery_table_xlsx&type=adherents_date&contrat=' . $post->ID ) . '">' . __( 'Livraisons par adhérents (XLSX)', 'amapress' ) . '</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=delivery_table_xlsx&type=adherents_columns&contrat=' . $post->ID ) . '">' . __( 'Livraisons par adhérents et quantités en colonnes (XLSX)', 'amapress' ) . '</a>,';
					echo '</p>';
				} else {
					echo '<h4>' . __( 'TELECHARGER ARCHIVES', 'amapress' ) . '</h4>';
					echo '<p>';
					echo '<a href="' . admin_url( 'admin-post.php?action=archives_inscriptions&contrat=' . $post->ID ) . '">' . __( 'Adhérents (XLSX)', 'amapress' ) . '</a>,';
					foreach ( ( count( $contrat->getLieuxIds() ) > 1 ? array_merge( [ 0 ], $contrat->getLieuxIds() ) : $contrat->getLieuxIds() ) as $lieu_id ) {
						$lieu = ( 0 == $lieu_id ? null : AmapressLieu_distribution::getBy( $lieu_id ) );
						echo '<a href="' . admin_url( 'admin-post.php?action=archives_cheques&lieu=' . $lieu_id . '&contrat=' . $post->ID ) . '">' . __( 'Chèques/règlements - ', 'amapress' ) . ( 0 == $lieu_id ? __( 'Tous les lieux', 'amapress' ) : $lieu->getTitle() ) . ' (XLSX)</a>,';
					}
					echo '<a href="' . admin_url( 'admin-post.php?action=archives_inscriptions&type=group_date&contrat=' . $post->ID ) . '">' . __( 'Livraisons par dates (XLSX)', 'amapress' ) . '</a>,';
					echo '<a href="' . admin_url( 'admin-post.php?action=archives_inscriptions&type=adherents_date&contrat=' . $post->ID ) . '">' . __( 'Livraisons par adhérents (XLSX)', 'amapress' ) . '</a>,';
					echo '</p>';
					echo '<h4>' . __( 'SUPPRIMER ARCHIVES', 'amapress' ) . '</h4>';
					echo '<p>';
					echo '<a href="' . admin_url( 'admin-post.php?action=archives_inscriptions&type=delete_all&contrat=' . $post->ID ) . '">' . __( 'Supprimer totalement les archives', 'amapress' ) . '</a>,';
					echo '</p>';
				}
			}

			echo '<h4>' . __( 'ACCÈS RAPIDE', 'amapress' ) . '</h4>';

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
			'renew'                => array(
				'label'     => __( 'Renouveler (prolongement)', 'amapress' ),
				'condition' => 'amapress_can_renew_contrat_instance',
				'show_on'   => 'list',
				'confirm'   => true,
			),
			'renew_same_period'    => array(
				'label'     => __( 'Renouveler (même période)', 'amapress' ),
				'condition' => 'amapress_can_renew_same_period_contrat_instance',
				'show_on'   => 'list',
				'confirm'   => true,
			),
			'clone'                => [
				'label'   => __( 'Dupliquer', 'amapress' ),
				'show_on' => 'list',
				'confirm' => true,
			],
			'clone_next_week'      => [
				'label'     => __( 'Cloner - une semaine', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return $contrat->isPanierVariable()
					       && abs( Amapress::datediffInWeeks( $contrat->getDate_fin(), $contrat->getDate_debut() ) ) <= 2;
				},
				'confirm'   => true,
			],
			'clone_next_next_week' => [
				'label'     => __( 'Cloner - deux semaines', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return $contrat->isPanierVariable()
					       && abs( Amapress::datediffInWeeks( $contrat->getDate_fin(), $contrat->getDate_debut() ) ) <= 3;
				},
				'confirm'   => true,
			],
			'clone_next_month'     => [
				'label'     => __( 'Cloner - mois prochain', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return $contrat->isPanierVariable()
					       && abs( Amapress::datediffInWeeks( $contrat->getDate_fin(), $contrat->getDate_debut() ) ) <= 5;
				},
				'confirm'   => true,
			],
			'generate_contrat'     => [
				'label'     => __( 'Générer le contrat papier (DOCX)', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return ! empty( $contrat->getContratPapierModelDocFileName() )
					       && Amapress::start_of_week( $contrat->getDate_fin() ) > Amapress::start_of_day( amapress_time() );
				},
			],
			'mailto_amapiens'      => [
				'label'     => __( 'Email aux amapiens', 'amapress' ),
				'target'    => '_blank',
//				'confirm'   => true,
				'href'      => admin_url( 'admin.php?page=amapress_messages_page' ),
				'condition' => function ( $adh_id ) {
					return TitanFrameworkOption::isOnEditScreen();
				},
				'show_on'   => 'editor',
			],
//			'smsto_amapiens'     => [
//				'label'     => __('Sms aux amapiens', 'amapress'),
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
			'show_distribs'        => [
				'label'   => __( 'Historique des distributions', 'amapress' ),
				'target'  => '_blank',
				'href'    => function ( $adh_id ) {
					return admin_url( "edit.php?post_type=amps_distribution&amapress_contrat_inst=$adh_id" );
				},
				'show_on' => 'editor',
			],
			'show_next_distribs'   => [
				'label'     => __( 'Prochaines distributions', 'amapress' ),
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
//				'label'     => __('Exporter les inscriptions', 'amapress'),
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
//				'label'     => __('Exporter les chèques (XLSX)', 'amapress'),
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
//				'label'     => __('Exporter les chèques (PDF)', 'amapress'),
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
//				'label'     => __('Voir les stats', 'amapress'),
//				'target'    => '_blank',
//				'href'      => function ( $adh_id ) {
//					return admin_url( 'admin.php?amp_stats_contrat=' . $adh_id . '&page=contrats_quantites_stats' );
//				},
//				'condition' => function ( $adh_id ) {
//					return TitanFrameworkOption::isOnEditScreen();
//				},
//				'show_on'   => 'none',
//			],
			'open_inscr'           => [
				'label'     => __( 'Ouvrir inscriptions', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return ! $contrat->canSelfSubscribe()
					       && Amapress::start_of_day( $contrat->getDate_cloture() ) >= Amapress::start_of_day( amapress_time() );
				},
			],
			'close_inscr'          => [
				'label'     => __( 'Fermer inscriptions', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$contrat = AmapressContrat_instance::getBy( $adh_id );

					return $contrat->canSelfSubscribe()
					       && Amapress::start_of_day( $contrat->getDate_cloture() ) >= Amapress::start_of_day( amapress_time() );
				},
			],
		),
		'bulk_actions'             => array(
			'amp_incr_cloture' => array(
				'label'    => __( 'Reporter date clôture (1j)', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Date de clôture repoussée d\'un jour', 'amapress' ),
					'>1' => __( 'Dates de clôture repoussées d\'un jour', 'amapress' ),
				),
			),
			'amp_decr_cloture' => array(
				'label'    => __( 'Diminuer date clôture (1j)', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Date de clôture diminuée d\'un jour', 'amapress' ),
					'>1' => __( 'Dates de clôture diminuées d\'un jour', 'amapress' ),
				),
			),
		),
		'labels'                   => array(
			'add_new'      => __( 'Ajouter', 'amapress' ),
			'add_new_item' => __( 'Ajout modèle de contrat', 'amapress' ),
			'edit_item'    => __( 'Éditer - Modèle de contrat', 'amapress' ),
		),
		'views'                    => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_contrat_instance_views',
			'exp_csv' => true,
		),
		'other_def_hidden_columns' => array( 'thumb-preview' ),
		'fields'                   => array(
			//renouvellement
			'renouv'                => array(
				'name'        => __( 'Options', 'amapress' ),
				'show_column' => false,
				'csv'         => false,
				'show_on'     => 'edit-only',
				'group'       => '> Renouvellement',
//				'bare'        => true,
				'type'        => 'custom',
				'custom'      => function ( $post_id ) {
					$ret = '';
					if ( amapress_can_renew_contrat_instance( $post_id ) ) {
						$ret .= '<p>' . sprintf( __( '<a href="%s">Prolongation</a> : Dates continues - Durée identique <span class="description">(Par ex : contrats trimestriels)</span>', 'amapress' ), amapress_get_row_action_href( 'renew', $post_id ) ) . '</p>';
					}
					if ( amapress_can_renew_same_period_contrat_instance( $post_id ) ) {
						$ret .= '<p>' . sprintf( __( '<a href="%s">Cyclique</a> : Même période - Année suivante <span class="description">(Par ex : contrats annuels)</span>', 'amapress' ), amapress_get_row_action_href( 'renew_same_period', $post_id ) ) . '</p>';
					}
					if ( empty( $ret ) ) {
						$ret .= '<p>' . __( 'Contrat déjà renouvellé', 'amapress' ) . '</p>';
					}

					return $ret;

//					return '<tr><td colspan="2" style="margin: 0; padding: 0">' .
//					       $ret
//					       . '</td></tr>';
				}
			),

			// 1/6 - Ferme
			'producteur'            => array(
				'name'                 => __( 'Producteur', 'amapress' ),
				'type'                 => 'custom',
				'csv_import'           => false,
				'group'                => __( '1/6 - Ferme', 'amapress' ),
				'show_on'              => 'edit-only',
				'show_column'          => true,
				'use_custom_as_column' => true,
				'custom'               => function ( $post_id ) {
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
				'name'              => __( 'Production', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressContrat::INTERNAL_POST_TYPE,
				'group'             => __( '1/6 - Ferme', 'amapress' ),
				'required'          => true,
				'desc'              => __( 'Sélectionner la production.', 'amapress' ),
				'import_key'        => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'           => 'amapress_contrat',
					'placeholder'    => __( 'Toutes les productions', 'amapress' ),
					'custom_options' => function ( $args ) {
						$ret      = [];
						$contrats = AmapressContrats::get_contrats();
						usort( $contrats, function ( $a, $b ) {
							return strcmp( $a->getTitle(), $b->getTitle() );
						} );
						foreach ( $contrats as $contrat ) {
							$ret[ strval( $contrat->ID ) ] = sprintf( '%s / %s',
								$contrat->getTitle(), $contrat->getProducteur()->getNomExploitation() );
						}

						return $ret;
					}
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
				'name'                 => __( 'Référents', 'amapress' ),
				'type'                 => 'custom',
				'group'                => __( '1/6 - Ferme', 'amapress' ),
				'show_on'              => 'edit-only',
				'csv_import'           => false,
				'desc'                 => __( 'Pour modifier les référents, cliquez sur le lien Producteur ci-dessus', 'amapress' ),
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
				'name'           => __( 'Visite', 'amapress' ),
				'group'          => __( '1/6 - Ferme', 'amapress' ),
				'type'           => 'number',
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'desc'           => __( 'Nombre de visite(s) obligatoire(s) chez le producteur', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'max'            => 12,
			),

			// 2/6 - Paramètres généraux
			'date_debut'            => array(
				'name'          => __( 'Début', 'amapress' ),
				'type'          => 'date',
				'group'         => __( '2/6 - Paramètres généraux', 'amapress' ),
				'required'      => true,
				'desc'          => __( 'Date de début du contrat', 'amapress' ),
				'import_key'    => true,
				'top_filter'    => array(
					'name'           => 'amapress_date',
					'placeholder'    => __( 'Toutes les dates', 'amapress' ),
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
				'name'          => __( 'Fin', 'amapress' ),
				'type'          => 'date',
				'group'         => __( '2/6 - Paramètres généraux', 'amapress' ),
				'required'      => true,
				'desc'          => __( 'Date de fin du contrat', 'amapress' ),
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
			'max_months'            => array(
				'name'           => __( 'Contrat glissant', 'amapress' ),
				'type'           => 'number',
				'group'          => __( '2/6 - Paramètres généraux', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'required'       => true,
				'desc'           => function ( $o ) {
					return sprintf( __( 'Indiquer une durée en mois pour activer l\'option %s', 'amapress' ), Amapress::makeWikiLink( 'https://wiki.amapress.fr/contrats/slide', __( 'Contrat glissant', 'amapress' ) ) );
				},
				'min'            => 0,
				'max'            => 12,
				'default'        => 0,
				'slider'         => false,
				'unit'           => 'mois',
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'model_name'            => array(
				'name'        => __( 'Nom générique', 'amapress' ),
				'show_column' => false,
				'show_on'     => 'edit-only',
				'group'       => __( '2/6 - Paramètres généraux', 'amapress' ),
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
				'name'           => __( 'Nom complémentaire', 'amapress' ),
				'group'          => __( '2/6 - Paramètres généraux', 'amapress' ),
				'type'           => 'text',
				'desc'           => __( 'Lorsque 2 contrats de même type coexistent (Par ex : ”Semaine A”, “Semaine B”)', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'max_adherents'         => array(
				'name'           => __( 'Nombre d’amapiens maximum', 'amapress' ),
				'type'           => 'number',
				'group'          => __( '2/6 - Paramètres généraux', 'amapress' ),
				'required'       => true,
				'desc'           => __( 'Nombre maximum d’inscriptions (ou parts) autorisées par le producteur', 'amapress' ),
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'use_equiv'             => array(
				'name'        => __( 'Maximum en part', 'amapress' ),
				'type'        => 'checkbox',
				'default'     => false,
				'show_column' => false,
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'group'       => __( '2/6 - Paramètres généraux', 'amapress' ),
				'desc'        => __( 'Compter les maximums en part (Coefficient de part) en non en inscriptions', 'amapress' ),
			),
			'min_engagement'        => array(
				'name'           => __( 'Engagement minimum', 'amapress' ),
				'type'           => 'number',
				'group'          => __( '2/6 - Paramètres généraux', 'amapress' ),
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'desc'           => __( 'Montant minimum demandé par le producteur pour un contrat', 'amapress' ),
			),
//			'word_paper_model'      => array(
//				'name'            => __( 'Contrat vierge', 'amapress' ),
//				'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//				'type'            => 'upload',
//				'show_column'     => false,
//				'show_download'   => true,
//				'show_title'      => true,
//				'selector-button' => __('Utiliser ce modèle', 'amapress'),
//				'selector-title'  => __('Sélectionnez/téléversez un modèle de contrat personnalisé DOCX', 'amapress'),
//				'group'           => __('2/6 - Paramètres généraux', 'amapress'),
//				'desc'            => '
//                <p><strong>Vous pouvez configurer ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ), 'un modèle global pour tous les contrats' ) . ' et laisser ce champs vide. Le contrat général sera utilisé automatiquement.</strong></p>
//				<p>Sinon configurez un contrat vierge à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)</p>
//				<p>Vous pouvez télécharger <a target="_blank" href="' . esc_attr( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) ) . '">ici</a> l\'un des modèles DOCX génériques utilisables comme contrat vierge. Vous aurez à personnaliser le logo de votre AMAP et les engagements.</p>',
//			),
			'contrat_info'          => array(
				'name'        => __( 'Termes du contrat', 'amapress' ),
				'type'        => 'editor',
				'show_column' => false,
				'group'       => __( '2/6 - Paramètres généraux', 'amapress' ),
				'desc'        => function ( $o ) {
					return sprintf( __( 'Termes du contrats (Pour les utilisateurs avancés : à compléter avec des marquages substitutifs de type "%%%%xxx%%%%" <a target="_blank" href="%s">Plus d\'info</a>)', 'amapress' ), admin_url( 'admin.php?page=amapress_help_page&tab=pres_prod_contrat_placeholders' ) );
				},
			),
			'special_mention'       => array(
				'name'        => __( 'Mention', 'amapress' ),
				'type'        => 'textarea',
				'group'       => __( '2/6 - Paramètres généraux', 'amapress' ),
				'show_column' => false,
				'default'     => '',
				'desc'        => __( 'Mention spécial ou partie variable pour le contrat (remplit le placeholder %%mention_speciale%%)', 'amapress' ),
			),


//			'type'           => array(
//				'name'          => __( 'Type de contrat', 'amapress' ),
//				'type'          => 'select',
//				'options'       => array(
//					'panier' => __('Distributions régulières', 'amapress'),
////					'commande' => __('Commandes', 'amapress'),
//				),
//				'required'      => true,
//				'group'         => __('Gestion', 'amapress'),
//				'desc'          => __('Type de contrat', 'amapress'),
//				'import_key'    => true,
//				'default'       => 'panier',
//				'readonly'      => 'amapress_is_contrat_instance_readonly',
//				'custom_column' => function ( $option, $post_id ) {
//					$status           = [];
//					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
//					if ( $contrat_instance->isPanierVariable() ) {
//						$status[] = __('Paniers variables', 'amapress');
//					} else if ( $contrat_instance->isQuantiteVariable() ) {
//						if ( $contrat_instance->isQuantiteMultiple() ) {
//							$status[] = __('Quantités variables multiples', 'amapress');
//						} else {
//							$status[] = __('Quantités variables', 'amapress');
//						}
//					} else {
//						if ( $contrat_instance->isQuantiteMultiple() ) {
//							$status[] = __('Quantités fixes multiples', 'amapress');
//						} else {
//							$status[] = __('Quantités fixes', 'amapress');
//						}
//					}
//					if ( $contrat_instance->isPrincipal() ) {
//						$status[] = __('Principal', 'amapress');
//					}
//					if ( $contrat_instance->isEnded() ) {
//						$status[] = __('Clôturé', 'amapress');
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
////							'name'        => __( 'Calendrier des commandes', 'amapress' ),
////							'type'        => 'multidate',
////							'group'       => __('Commandes', 'amapress'),
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'required'    => true,
////							'show_column' => false,
////							'desc'        => '',
////						),
////						'commande_cannot_modify' => array(
////							'name'        => __( 'Commandes fermes', 'amapress' ),
////							'type'        => 'checkbox',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'group'       => __('Commandes', 'amapress'),
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => '',
////						),
////						'commande_open_before'   => array(
////							'name'        => __( 'Ouverture des commandes', 'amapress' ),
////							'type'        => 'number',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'group'       => __('Commandes', 'amapress'),
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => __('Ouverture des commandes x jours avant (0=tout de suite)', 'amapress'),
////						),
////						'commande_close_before'  => array(
////							'name'        => __( 'Fermeture des commandes', 'amapress' ),
////							'group'       => __('Commandes', 'amapress'),
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'type'        => 'number',
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => __('Fermeture des commandes x jours avant', 'amapress'),
////						),
////					),
//				)
//			),

			// 3/6 Distributions
			'lieux'                 => array(
				'name'           => __( 'Lieu(x)', 'amapress' ),
				'type'           => 'multicheck-posts',
				'post_type'      => 'amps_lieu',
				'group'          => __( '3/6 - Distributions', 'amapress' ),
				'required'       => true,
				'csv_required'   => true,
				'desc'           => __( 'Lieu(x) de distribution', 'amapress' ),
				'select_all'     => true,
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'top_filter'     => array(
					'name'        => 'amapress_lieu',
					'placeholder' => __( 'Tous les lieux', 'amapress' )
				),
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'liste_dates'           => array(
				'name'             => __( 'Calendrier initial', 'amapress' ),
				'type'             => 'multidate',
				'required'         => true,
				'csv_required'     => true,
				'group'            => __( '3/6 - Distributions', 'amapress' ),
				'readonly'         => 'amapress_is_contrat_instance_readonly',
				'show_column'      => true,
				'col_def_hidden'   => true,
				'column_value'     => 'dates_count',
				'desc'             => function ( $option ) {
					$ret = __( 'Sélectionner les dates de distribution fournies par le producteur', 'amapress' );
					if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
						$ret .= '<br/><a id="amapress_deliv_step_1w_dates" class="button button-secondary">' . __( 'Toutes les semaines', 'amapress' ) . '</a>';
						$ret .= '<a id="amapress_deliv_step_2w_dates" class="button button-secondary">' . __( 'Toutes les deux semaines', 'amapress' ) . '</a>';
						$ret .= '<a id="amapress_deliv_step_1m_dates" class="button button-secondary">' . __( 'Tous les mois', 'amapress' ) . '</a>';
						$ret .= '<a id="amapress_reset_deliv_dates" class="button button-secondary">' . __( 'Supprimer toutes les dates', 'amapress' ) . '</a>';
						$ret .= '<script type="application/javascript">jQuery(function($) {
    var $date_debut = $("#amapress_contrat_instance_date_debut");
    var $date_fin = $("#amapress_contrat_instance_date_fin");
    var $dest = $("#amapress_contrat_instance_liste_dates-cal");
    var getDateDebut = function() {
         return $.datepicker.parseDate("' . TitanFrameworkOptionMultiDate::$default_jquery_date_format . '", $date_debut.val());
    };
    var getDateFin = function() {
         return $.datepicker.parseDate("' . TitanFrameworkOptionMultiDate::$default_jquery_date_format . '", $date_fin.val());
    };
    $("#amapress_deliv_step_1w_dates").click(function() {
        $dest.multiDatesPicker("resetDates");
        var dates = [];
        var start_date = getDateDebut();
        var end_date = getDateFin();
        while (start_date <= end_date) {
            dates.push(new Date(start_date));
            start_date.setDate(start_date.getDate() + 7);
        }
        $dest.multiDatesPicker(
            "addDates",
            dates
        );
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_deliv_step_2w_dates").click(function() {
        $dest.multiDatesPicker("resetDates");
        var dates = [];
        var start_date = getDateDebut();
        var end_date = getDateFin();
        while (start_date <= end_date) {
            dates.push(new Date(start_date));
            start_date.setDate(start_date.getDate() + 7 * 2);
        }
        $dest.multiDatesPicker(
            "addDates",
            dates
        );
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_deliv_step_1m_dates").click(function() {
        $dest.multiDatesPicker("resetDates");
        var dates = [];
        var start_date = getDateDebut();
        var end_date = getDateFin();
        while (start_date <= end_date) {
            dates.push(new Date(start_date));
            var month = start_date.getMonth();
            start_date.setDate(start_date.getDate() + 7 * 4);
            while (start_date.getMonth() == month)
                start_date.setDate(start_date.getDate() + 7);
        }
        $dest.multiDatesPicker(
            "addDates",
            dates
        );
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_reset_deliv_dates").click(function() {
        $dest.multiDatesPicker("resetDates");
        $dest.multiDatesPicker("refresh");
    });
});</script>';

					}

					return $ret;
				},
				'show_dates_count' => true,
				'show_dates_list'  => true,
				'before_option'    =>
					function ( $option ) {
						$is_readonly = amapress_is_contrat_instance_readonly( $option );
						if ( ! TitanFrameworkOption::isOnNewScreen() ) {
							if ( $is_readonly ) {
							} else {
								$val_id = $option->getID() . '-validate';
								echo '<p><input type="checkbox" id="' . $val_id . '" ' . checked( ! $is_readonly, true, false ) . ' /><label for="' . $val_id . '">' . __( 'Cocher cette case pour modifier les dates lors du renouvellement du contrat.', 'amapress' ) . '</label></p>';
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
				'name'              => __( 'Report livraison', 'amapress' ),
				'group'             => __( '3/6 - Distributions', 'amapress' ),
				'table_header_text' => '<p>' . __( 'Pour annuler ou reporter une distribution déjà planifiée, sélectionner le panier correspondant dans la liste ci-dessous', 'amapress' ) . '</p>',
				'desc'              => __( 'Dates de livraison des paniers de ce contrat', 'amapress' ),
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
					'lengthMenu' => [ [ 2, 5, 10, 25, 50, - 1 ], [ 2, 5, 10, 25, 50, __( 'Tous', 'amapress' ) ] ],
				),
				'type'              => 'related-posts',
				'query'             => 'post_type=amps_panier&amapress_contrat_inst=%%id%%',
			),
			'nb_resp_supp'          => array(
				'name'           => __( 'Responsables', 'amapress' ),
				'type'           => 'number',
				'required'       => true,
				'desc'           => __( 'Le nombre de responsable de distribution est configuré pour chaque ', 'amapress' ) .
				                    Amapress::makeLink( admin_url( 'edit.php?post_type=amps_lieu' ), 'lieu de distribution', true, true ) .
				                    '. Facultatif : Ajouter des responsables supplémentaires.',
				'group'          => __( '3/6 - Distributions', 'amapress' ),
				'default'        => 0,
				'max'            => 10,
				'show_column'    => true,
				'col_def_hidden' => true,
			),

			// 4/6 Paniers
			'quant_type'            => array(
				'name'              => __( 'Choix du contenu des paniers', 'amapress' ),
				'type'              => 'custom',
				'group'             => __( '4/6 - Paniers', 'amapress' ),
				'readonly'          => 'amapress_is_contrat_instance_readonly',
				'csv'               => true,
				'show_column'       => true,
				'col_def_hidden'    => true,
				'custom_csv_sample' => function ( $option, $arg ) {
					return [
						__( 'Choix unique - quantité déterminée', 'amapress' ),
						__( 'Choix multiple - quantités déterminées', 'amapress' ),
						__( 'Choix unique - quantité libre', 'amapress' ),
						__( 'Choix multiple - quantités libres', 'amapress' ),
						__( 'Paniers modulables', 'amapress' ),
						__( 'Commandes variables', 'amapress' ),
					];
				},
				'csv_validator'     => function ( $value ) {
					switch ( $value ) {
						case __( 'Choix unique - quantité déterminée', 'amapress' ):
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_commande_variable' => 0,
								'amapress_contrat_instance_quantite_multi'    => 0,
								'amapress_contrat_instance_quantite_variable' => 0,
							];
						case __( 'Choix multiple - quantités déterminées', 'amapress' ):
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_commande_variable' => 0,
								'amapress_contrat_instance_quantite_multi'    => 1,
								'amapress_contrat_instance_quantite_variable' => 0,
							];
						case __( 'Choix unique - quantité libre', 'amapress' ):
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_commande_variable' => 0,
								'amapress_contrat_instance_quantite_multi'    => 0,
								'amapress_contrat_instance_quantite_variable' => 1,
							];
						case __( 'Choix multiple - quantités libres', 'amapress' ):
							return [
								'amapress_contrat_instance_panier_variable'   => 0,
								'amapress_contrat_instance_commande_variable' => 0,
								'amapress_contrat_instance_quantite_multi'    => 1,
								'amapress_contrat_instance_quantite_variable' => 1,
							];
						case __( 'Paniers modulables', 'amapress' ):
							return [
								'amapress_contrat_instance_panier_variable'   => 1,
								'amapress_contrat_instance_commande_variable' => 0,
								'amapress_contrat_instance_quantite_multi'    => 0,
								'amapress_contrat_instance_quantite_variable' => 0,
							];
						case __( 'Commandes variables', 'amapress' ):
							return [
								'amapress_contrat_instance_panier_variable'   => 1,
								'amapress_contrat_instance_commande_variable' => 1,
								'amapress_contrat_instance_quantite_multi'    => 0,
								'amapress_contrat_instance_quantite_variable' => 0,
							];
						default:
							return new WP_Error( 'cannot_parse', sprintf( __( 'Valeur \'%s\' non trouvée pour \'Choix du contenu des paniers\'', 'amapress' ), $value ) );
					}
				},
				'column'            => function ( $post_id ) {
					$status           = [];
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( $contrat_instance->isPanierVariable() ) {
						if ( $contrat_instance->isCommandeVariable() ) {
							$status[] = __( 'Commandes variables', 'amapress' );
						} else {
							$status[] = __( 'Paniers modulables', 'amapress' );
						}
					} else if ( $contrat_instance->isQuantiteVariable() ) {
						if ( $contrat_instance->isQuantiteMultiple() ) {
							$status[] = __( 'Choix multiple -  quantités libres', 'amapress' );
						} else {
							$status[] = __( 'Choix unique - quantité libre', 'amapress' );
						}
					} else {
						if ( $contrat_instance->isQuantiteMultiple() ) {
							$status[] = __( 'Choix multiple - quantités déterminées', 'amapress' );
						} else {
							$status[] = __( 'Choix unique - quantité déterminée', 'amapress' );
						}
					}
					if ( $contrat_instance->isPrincipal() ) {
						$status[] = __( 'Principal', 'amapress' );
					}
					if ( $contrat_instance->isEnded() ) {
						$status[] = __( 'Clôturé', 'amapress' );
					}

					echo implode( ', ', $status );
				},
				'custom'            => function ( $post_id ) {
					$type             = 'quant_fix';
					$contrat_instance = AmapressContrat_instance::getBy( $post_id, true );
					if ( $contrat_instance ) {
						if ( $contrat_instance->isPanierVariable() ) {
							if ( $contrat_instance->isCommandeVariable() ) {
								$type = 'commande_var';
							} else {
								$type = 'panier_var';
							}
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
//			'quant_fix'       => __('Choix unique - quantité déterminée', 'amapress'),
//			'quant_fix_multi' => __('Choix multiple - quantités déterminées', 'amapress'),
//			'quant_var'       => __('Choix unique - quantité libre', 'amapress'),
//			'quant_var_multi' => __('Choix multiple -  quantités libres', 'amapress'),
//			'panier_var'      => __('Paniers modulables', 'amapress'),
//			'commande_var'      => __('Commandes variables', 'amapress'),
//		];
					ob_start();
					?>
                    <p><?php _e( 'Choisissez le type d’option(s) proposée(s) dans le contrat d’origine concernant la composition des paniers.', 'amapress' ) ?></p>
                    <p><input type="radio" class="required" <?php checked( 'quant_fix', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_fix" value="quant_fix"/><label
                                for="amp_quant_fix"><strong><?php _e( 'Choix unique - quantité déterminée', 'amapress' ) ?></strong><?php _e( ' : ', 'amapress' ) ?>
							<?php _e( 'L’adhérent choisit une seule option pour toute la durée du contrat.', 'amapress' ) ?>
                        </label>
                        <br/><span
                                class="description"><?php _e( '(Par ex : “Légumes - Panier/Demi-panier” , “Champignons - Petit/Moyen/Grand”, “Fruits - Petit/Moyen/Grand”, “Jus - 1L/3L/6L”, “Oeuf - 6/12”...)', 'amapress' ) ?></span>
                    </p>
                    <p><input type="radio" class="required" <?php checked( 'quant_fix_multi', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_fix_multi" value="quant_fix_multi"/><label
                                for="amp_quant_fix_multi"><strong><?php _e( 'Choix multiple - quantités déterminées', 'amapress' ) ?></strong><?php _e( ' : ', 'amapress' ) ?>
							<?php _e( 'L’adhérent peut choisir différents produits associés à différentes tailles de panier pour toute la durée du contrat', 'amapress' ) ?>
                        </label><br/><span
                                class="description"><?php _e( '(Par ex :  “Champignons - Gros fan de champis 1 kg <strong>et</strong>  petit fan de pleurotes 250g <strong>et</strong>  Petit fan de shiitake 250g…”)', 'amapress' ) ?></span>
                    </p>
                    <p><input type="radio" class="required" <?php checked( 'quant_var', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_var" value="quant_var"/><label
                                for="amp_quant_var"><strong><?php _e( 'Choix unique - quantité libre', 'amapress' ) ?></strong>
                            : L’adhérent choisit la “Quantité” d’un produit pour toute durée du contrat
                        </label><br/><span
                                class="description"><?php _e( '(Par ex : “Quantité  x Poulets”, “Quantité x 6 oeufs”...)', 'amapress' ) ?></span>
                    </p>
                    <p><input type="radio" class="required" <?php checked( 'quant_var_multi', $type ) ?>
                              name="amapress_quantite_type" id="amp_quant_var_multi" value="quant_var_multi"/><label
                                for="amp_quant_var_multi"><strong><?php _e( 'Choix multiple - quantités libres', 'amapress' ) ?></strong><?php _e( ' : ', 'amapress' ) ?>
							<?php _e( 'L’adhérent peut choisir différents produits et différentes Quantités pour toute la durée du contrat', 'amapress' ) ?>
                        </label><br/><span
                                class="description"><?php _e( '(Par ex : “Oeufs - Quantité 6 et 12 oeufs”, “Fromage - Quantité Petit Panier Option 1 et Quantité Grand panier et Quantité Panier Faisselle...”, “Volailles - Quantité Petit poulet/ Quantité Moyen Poulet, Quantité Gros Poulet”...)', 'amapress' ) ?></span>
                    <p><input type="radio" class="required" <?php checked( 'panier_var', $type ) ?>
                              name="amapress_quantite_type" id="amp_panier_var" value="panier_var"/><label
                                for="amp_panier_var"><strong><?php _e( 'Paniers modulables', 'amapress' ) ?></strong><?php _e( ' : ', 'amapress' ) ?>
							<?php _e( 'L’adhérent compose à l’avance un panier spécifique pour chaque distribution', 'amapress' ) ?>
                        </label><br/><span
                                class="description"><?php _e( '(Par ex : “Brie”, “Epicerie”...)', 'amapress' ) ?></span>
                    </p>
                    <p><input type="radio" class="required" <?php checked( 'commande_var', $type ) ?>
                              name="amapress_quantite_type" id="amp_commande_var" value="commande_var"/><label
                                for="amp_panier_var"><strong><?php _e( 'Commandes variables', 'amapress' ) ?></strong><?php _e( ' : ', 'amapress' ) ?>
							<?php _e( 'L’adhérent compose avant chaque distribution un panier spécifique', 'amapress' ) ?>
                        </label><br/><span
                                class="description"><?php _e( '(Par ex : commande pour la semaine suivante)', 'amapress' ) ?></span>
                    </p>
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
						delete_post_meta(
							$post_id,
							'amapress_contrat_instance_commande_variable'
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
							case 'commande_var':
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
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_commande_variable',
									1
								);
								break;
						}

						return true;
					}
				},
			),
			'quant_editor'          => array(
				'name'        => __( 'Configuration des paniers (Taille/Quantités)', 'amapress' ),
				'type'        => 'custom',
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'column'      => null,
				'custom'      => 'amapress_get_contrat_quantite_editor',
				'save'        => 'amapress_save_contrat_quantite_editor',
				'show_on'     => 'edit-only',
				'show_column' => false,
				'csv'         => false,
				'bare'        => true,
//                'desc' => __('Quantités', 'amapress'),
			),
			'close_hours'           => array(
				'name'        => __( 'Clôture des inscriptions', 'amapress' ),
				'desc'        => __( 'Clôturer les inscriptions x heures avant la distribution (-1, valeur par défaut de l\'AMAP)', 'amapress' ),
				'type'        => 'number',
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => 'false',
				'min'         => - 1,
				'step'        => 1,
				'default'     => - 1,
				'slider'      => false,
				'unit'        => 'heure(s)',
			),
			'has_pancust'           => array(
				'name'        => __( 'Contenu du panier', 'amapress' ),
				'type'        => 'checkbox',
				'show_column' => false,
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'desc'        => __( 'Rendre accessible la description des paniers', 'amapress' ),
			),
			'prod_msg'              => array(
				'name'        => __( 'Message au producteur', 'amapress' ),
				'type'        => 'checkbox',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => false,
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'desc'        => __( 'Activer un champ Message de commande au producteur pour les options de commandes', 'amapress' ),
			),
			'prod_msg_desc'         => array(
				'name'        => __( 'Message au producteur - Instruction', 'amapress' ),
				'type'        => 'editor',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => false,
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'desc'        => __( 'Instruction à l\'amapien sur comment remplir le Message de commande au producteur', 'amapress' ),
			),
			'rattrapage'            => array(
				'name'        => __( 'Rattrapage', 'amapress' ),
				'desc'        => '',
				'type'        => 'custom',
				'group'       => __( '4/6 - Paniers', 'amapress' ),
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
						$rattrapage[] = sprintf( __( '%s (%.1f)', 'amapress' ), date_i18n( 'd/m/Y', intval( $r['date'] ) ), $r['quantite'] );
					}

					if ( empty( $rattrapage ) ) {
						return __( 'Aucun', 'amapress' );
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
					echo '<p style="padding: 20px 10px 20px 0;"><strong>' . __( 'Quantités de rattrapage', 'amapress' ) . '</strong></p>';
					echo '<p class="description">' . 'ptions pour les Amap dont les quantités de rattrapage sont annoncées en début de contrat' . '</p>';
					echo '<table id="quant_rattrapage" style="width: 100%; border: 1pt solid black">
<thead><tr><th style="padding-left: 20px">' . __( 'Sélectionner une date', 'amapress' ) . '</th><th style="padding-left: 20px">' . __( 'Indiquer la quantité', 'amapress' ) . '</th></tr></thead>
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
                                <input id="<?php echo "amapress_quantite_rattrapage-quant-$i"; ?>"
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
			'don_dist'              => array(
				'name'           => __( 'Don par distribution', 'amapress' ),
				'desc'           => __( 'Activer la possibilité de faire un don par distribution en plus du prix unitaire du panier (panier solidaire du producteur)', 'amapress' ),
				'type'           => 'checkbox',
				'group'          => __( '4/6 - Paniers', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'show_column'    => true,
				'col_def_hidden' => true,
				'show_on'        => 'edit-only',
			),
			'don_dist_apart'        => array(
				'name'        => __( 'Don par distribution - A part', 'amapress' ),
				'desc'        => __( 'Ne pas inclure le Don par distribution dans le total. Le montant du don est versé à part.', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'default'     => false,
				'show_column' => false,
				'show_on'     => 'edit-only',
			),
			'don_dist_lbl'          => array(
				'name'        => __( 'Don par distribution - Libellé', 'amapress' ),
				'desc'        => __( 'Libellé du don par distribution', 'amapress' ),
				'type'        => 'text',
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'default'     => __( 'Don par distribution', 'amapress' ),
				'show_column' => false,
				'show_on'     => 'edit-only',
			),
			'don_dist_desc'         => array(
				'name'        => __( 'Don par distribution - Description', 'amapress' ),
				'desc'        => __( 'Description du don par distribution', 'amapress' ),
				'type'        => 'editor',
				'group'       => __( '4/6 - Paniers', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => false,
				'show_on'     => 'edit-only',
			),
			// 5/6 - Pré-inscription en ligne
			'self_subscribe'        => array(
				'name'           => __( 'Activer', 'amapress' ),
				'type'           => 'checkbox',
				'group'          => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'desc'           => __( 'Rendre accessible les pré-inscriptions en ligne pour ce contrat', 'amapress' ),
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'self_contrats'         => array(
				'name'           => __( 'Autres contrats', 'amapress' ),
				'type'           => 'select-posts',
				'post_type'      => 'amps_contrat_inst',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'group'          => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'desc'           => __( 'Rendre accessible les pré-inscriptions en ligne pour ce contrat si l\'amapien a une inscription à l\'un de ces contrats', 'amapress' ),
				'show_column'    => true,
				'col_def_hidden' => true,
				'multiple'       => true,
				'tags'           => true,
				'autocomplete'   => true,
			),
			'self_edit'             => array(
				'name'           => __( 'Editer', 'amapress' ),
				'type'           => 'checkbox',
				'group'          => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'desc'           => __( 'Autoriser l\'édition de l\'inscription jusqu\'à sa validation', 'amapress' ),
				'default'        => false,
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'date_ouverture'        => array(
				'name'           => __( 'Ouverture', 'amapress' ),
				'type'           => 'date',
				'group'          => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'desc'           => __( 'Date d\'ouverture des inscriptions en ligne', 'amapress' ),
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
				'custom_export'  => function ( $option, $post_id ) {
					$contrat = AmapressContrat_instance::getBy( $post_id );
					echo $contrat->getDate_ouverture();
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
				'name'           => __( 'Clôture', 'amapress' ),
				'type'           => 'date',
				'group'          => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
				'desc'           => __( 'Date de clôture des inscriptions en ligne', 'amapress' ),
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
				'custom_export'  => function ( $option, $post_id ) {
					$contrat = AmapressContrat_instance::getBy( $post_id );
					echo $contrat->getDate_cloture();
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
				'name'        => __( 'Libellé règlements', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => __( 'Permettre aux amapiens de renseigner les numéros des chèques dans l’assistant de pré-inscription en ligne', 'amapress' ),
			),
			'pmt_user_dates'        => array(
				'name'        => __( 'Dates règlements', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Permettre aux amapiens de choisir les dates de paiement des chèques dans l’assistant de pré-inscription en ligne', 'amapress' ),
			),
			'word_model'            => array(
				'name'            => __( 'Contrat personnalisé', 'amapress' ),
				'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'type'            => 'upload',
				'show_column'     => false,
				'show_download'   => true,
				'show_title'      => true,
				'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
				'selector-title'  => __( 'Sélectionnez/téléversez un modèle de contrat papier DOCX', 'amapress' ),
				'group'           => __( '5/6 - Pré-inscription en ligne', 'amapress' ),
				'desc'            => function ( $o ) {
					return sprintf( __( '<p><strong>Vous pouvez configurer %s et laisser ce champs vide. Le contrat général sera utilisé automatiquement.</strong></p>
<p>Sinon, configurez un modèle de contrat à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="%s">Plus d\'info</a>)</p>
<p>Vous pouvez télécharger <a target="_blank" href="%s">ici</a> l\'un des modèles DOCX génériques utilisables comme contrat vierge. Vous aurez à personnaliser le logo de votre AMAP et les engagements.</p>', 'amapress' ), Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ), 'un modèle global pour tous les contrats' ), admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ), esc_attr( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) ) );
				},
			),


			//Statut
			'is_principal'          => array(
				'name'        => __( 'Contrat principal', 'amapress' ),
				'type'        => 'checkbox',
				'show_column' => false,
				'required'    => true,
				'group'       => __( 'Statut', 'amapress' ),
				'desc'        => __( 'Rendre obligatoire ce contrat (Par ex : Contrat légumes)', 'amapress' ),
			),
			'status'                => array(
				'name'    => __( 'Statut', 'amapress' ),
				'type'    => 'custom',
				'column'  => function ( $post_id ) {
					return AmapressContrats::contratStatus( $post_id );
				},
				'custom'  => function ( $post_id ) {
					return AmapressContrats::contratStatus( $post_id );
				},
				'group'   => __( 'Statut', 'amapress' ),
				'save'    => null,
				'csv'     => false,
				'desc'    => __( 'Statut', 'amapress' ),
				'show_on' => 'edit-only',
			),
			'ended'                 => array(
				'name'        => __( 'Clôturer', 'amapress' ),
				'type'        => 'checkbox',
				'csv_import'  => false,
				'group'       => __( 'Statut', 'amapress' ),
				'desc'        => __( 'Ferme le contrat (<strong>Renouveler le contrat avant de cocher cette case</strong>)', 'amapress' ),
				'show_on'     => 'edit-only',
				'show_column' => false,
			),
			'status_type'           => array(
				'name'                 => __( 'Résumé', 'amapress' ),
				'type'                 => 'custom',
				'use_custom_as_column' => true,
				'custom'               => function ( $post_id ) {
					$ret     = [];
					$contrat = AmapressContrat_instance::getBy( $post_id );
					if ( $contrat->isPanierVariable() ) {
						$ret[] = __( 'Paniers modulables', 'amapress' );
					} else {
						$ret[] = __( 'Contrat récurrent', 'amapress' );
					}
					if ( $contrat->isPrincipal() ) {
						$ret[] = __( 'principal', 'amapress' );
					}
					if ( $contrat->canSelfEdit() ) {
						$ret[] = __( 'éditable', 'amapress' );
					} else {
						$ret[] = __( 'non éditable', 'amapress' );
					}
					if ( ! empty( $contrat->getPossiblePaiements() ) ) {
						$ret[] = sprintf( __( '%s chèque(s)', 'amapress' ), implode( ';', $contrat->getPossiblePaiements() ) );
					}
					if ( $contrat->getPayByMonthOnly() ) {
						$ret[] = __( 'répartition au mois uniquement', 'amapress' );
					} elseif ( $contrat->getPayByMonth() ) {
						$ret[] = __( 'répartition au mois', 'amapress' );
					}
					if ( $contrat->getAllow_Transfer() ) {
						$ret[] = __( 'virement', 'amapress' );
					}
					if ( $contrat->getAllow_Cash() ) {
						$ret[] = __( 'espèces', 'amapress' );
					}
					if ( $contrat->getAllow_LocalMoney() ) {
						$ret[] = __( 'monnaie locale', 'amapress' );
					}
					if ( $contrat->getAllow_Prelevement() ) {
						if ( ! empty( $contrat->getPossiblePaiements() ) ) {
							$ret[] = sprintf( __( '%s prélèvement(s)', 'amapress' ), implode( ';', $contrat->getPossiblePaiements() ) );
						}
					}
					if ( $contrat->getAllow_Delivery_Pay() ) {
						$ret[] = __( 'à la livraison', 'amapress' );
					}
					if ( $contrat->getMax_adherents() > 0 ) {
						if ( $contrat->getMaxUseEquivalentQuant() ) {
							$ret[] = '<strong>' . sprintf( __( '%d parts max.', 'amapress' ), $contrat->getMax_adherents() ) . '</strong>';
						} else {
							$ret[] = '<strong>' . sprintf( __( '%d adhérents max.', 'amapress' ), $contrat->getMax_adherents() ) . '</strong>';
						}
					}
					if ( $contrat->isFull() ) {
						$ret[] = '<span style="color:red"><strong>' . __( 'COMPLET', 'amapress' ) . '</strong></span>';
					} elseif ( $contrat->getDate_ouverture() > Amapress::start_of_day( amapress_time() ) ) {
						$ret[] = sprintf( __( '<span style="color:orange">ouvrira le %s</span>', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) );
					} elseif ( $contrat->getDate_cloture() < Amapress::end_of_day( amapress_time() ) ) {
						$ret[] = sprintf( __( '<span style="color:orange">clos depuis %s</span>', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) );
					} elseif ( ! $contrat->canSelfSubscribe() ) {
						$ret[] = '<span style="color:orange">inscription fermée</span>';
					} elseif ( ! empty( $contrat->canSelfContratsCondition() ) ) {
						$ret[] = sprintf( __( '<span style="color: green">inscription conditionnelle (%s&gt;%s)</span>', 'amapress' ),
							date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ),
							date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) );
					} else {
						$ret[] = sprintf( __( '<span style="color: green">inscription ouverte (%s&gt;%s)</span>', 'amapress' ),
							date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ),
							date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) );
					}

					return implode( ', ', $ret );
				},
				'csv'                  => false,
				'group'                => __( 'Statut', 'amapress' ),
			),
//						'quantite_multi'        => array(
//							'name'        => __( 'Quantités multiples', 'amapress' ),
//							'type'        => 'checkbox',
//							'group'       => __('Gestion', 'amapress'),
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'default'     => 1,
//							'desc'        => __('Cocher cette case si les quantités ', 'amapress'),
//						),
//						'panier_variable'       => array(
//							'name'        => __( 'Paniers personnalisés', 'amapress' ),
//							'type'        => 'checkbox',
//							'group'       => __('Gestion', 'amapress'),
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'desc'        => __('Cocher cette case si les paniers sont spécifiques pour chacun des adhérents', 'amapress'),
//						),
//						'quantite_variable'     => array(
//							'name'        => __( 'Quantités personnalisées', 'amapress' ),
//							'type'        => 'checkbox',
//							'group'       => __('Gestion', 'amapress'),
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'desc'        => __('Cocher cette case si les quantités peuvent être modulées (par ex, 1L, 1.5L, 3L...)', 'amapress'),
//						),

			// 6/6 - reglements
			'liste_dates_paiements' => array(
				'name'             => __( 'Calendrier des paiements', 'amapress' ),
				'type'             => 'multidate',
				'readonly'         => 'amapress_is_contrat_instance_readonly',
				'required'         => true,
				'group'            => __( '6/6 - Règlements', 'amapress' ),
				'show_column'      => false,
				'show_dates_count' => true,
				'show_dates_list'  => true,
				'desc'             => function ( $option ) {
					$ret = __( 'Indiquez les dates indicatives d’encaissement des chèques communiquées par le producteur pour les règlements en plusieurs fois', 'amapress' );
					if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
						$ret .= '<br/><a id="amapress_recopy_dates_deliv_to_paiements" class="button button-secondary">' . __( 'Toutes les dates de distribution', 'amapress' ) . '</a>';
						$ret .= '<a id="amapress_first_deliv_dates_paiements" class="button button-secondary">' . __( 'Première distribution de chaque mois', 'amapress' ) . '</a>';
						$ret .= '<a id="amapress_last_deliv_dates_paiements" class="button button-secondary">' . __( 'Dernière distribution de chaque mois', 'amapress' ) . '</a>';
						$ret .= '<br/><a id="amapress_first_dates_paiements" class="button button-secondary">' . __( 'Premier jour de chaque mois', 'amapress' ) . '</a>';
						$ret .= '<a id="amapress_last_dates_paiements" class="button button-secondary">' . __( 'Dernier jour de chaque mois', 'amapress' ) . '</a>';
						$ret .= '<br/><a id="amapress_reset_dates_paiements" class="button button-secondary">' . __( 'Supprimer toutes les dates', 'amapress' ) . '</a>';
						$ret .= '<script type="application/javascript">jQuery(function($) {
    var $source = $("#amapress_contrat_instance_liste_dates-cal");
    var $dest = $("#amapress_contrat_instance_liste_dates_paiements-cal");
    var getDatesByMonth = function() {
              var groupBy = function(xs, keyer) {
          return xs.reduce(function(rv, x) {
            var key = keyer(x);
            (rv[key] = rv[key] || []).push(x);
            return rv;
          }, {});
        };
        return groupBy(
            $("#amapress_contrat_instance_liste_dates-cal").multiDatesPicker("getDates"),
            function(d) {
                var dt = $.datepicker.parseDate("' . TitanFrameworkOptionMultiDate::$default_jquery_date_format . '",d);
                return dt.getMonth() + "-" + dt.getFullYear();
            }
            );  
    };
    $("#amapress_recopy_dates_deliv_to_paiements").click(function() {
        $dest.multiDatesPicker("resetDates");
        $dest.multiDatesPicker(
            "value",
            $source.multiDatesPicker("value")
        );
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_first_deliv_dates_paiements").click(function() {
        var by_months = getDatesByMonth();
        var by_month_dates = [];
        for (var k in by_months) {
            by_month_dates.push(by_months[k][0]);
        }
        $dest.multiDatesPicker("resetDates");
        $dest.multiDatesPicker("addDates", by_month_dates);
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_last_deliv_dates_paiements").click(function() {
        var by_months = getDatesByMonth();
        var by_month_dates = [];
        for (var k in by_months) {
            by_month_dates.push(by_months[k][by_months[k].length - 1]);
        }
        $dest.multiDatesPicker("resetDates");
        $dest.multiDatesPicker("addDates", by_month_dates);
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_first_dates_paiements").click(function() {
        var by_months = getDatesByMonth();
        var by_month_dates = [];
        for (var k in by_months) {
            var d = $.datepicker.parseDate("dd/mm/yy", by_months[k][0]);
            d.setDate(1);
            by_month_dates.push(d);
        }
        $dest.multiDatesPicker("resetDates");
        $dest.multiDatesPicker("addDates", by_month_dates);
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_last_dates_paiements").click(function() {
        var by_months = getDatesByMonth();
        var by_month_dates = [];
        for (var k in by_months) {
            var d = $.datepicker.parseDate("dd/mm/yy", by_months[k][0]);
            d.setDate(0);
            by_month_dates.push(d);
        }
        $dest.multiDatesPicker("resetDates");
        $dest.multiDatesPicker("addDates", by_month_dates);
        $dest.multiDatesPicker("refresh");
    });
    $("#amapress_reset_dates_paiements").click(function() {
        $dest.multiDatesPicker("resetDates");
        $dest.multiDatesPicker("refresh");
    });
});</script>';
					}

					return $ret;
				},
			),
			'paiements'             => array(
				'name'              => __( 'Nombre de chèques', 'amapress' ),
				'type'              => 'multicheck',
				'desc'              => __( 'Indiquez le nombre de règlements par chèque autorisés par le producteur', 'amapress' ) .
				                       '<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/contrats/creation#pre-inscription_en_ligne' ),
				'group'             => __( '6/6 - Règlements', 'amapress' ),
				'readonly'          => 'amapress_is_contrat_instance_readonly',
				'required'          => true,
				'csv_required'      => true,
				'show_column'       => true,
				'col_def_hidden'    => true,
				'custom_csv_sample' => function ( $option, $arg ) {
					$ret = [];
					for ( $i = 1; $i <= 12; $i ++ ) {
						$ret[] = $i;
					}

					return $ret;
				},
				'csv_validator'     => function ( $value ) {
					$values = array_map( function ( $v ) {
						return intval( trim( $v ) );
					}, explode( ',', $value ) );
					foreach ( $values as $v ) {
						if ( intval( $v ) < 1 || intval( $v ) > 12 ) {
							return new WP_Error( 'cannot_parse', sprintf( __( "Valeur '%s' non valide : doit être une liste de paiements chacun entre 1 et 12", 'amapress' ), $value ) );
						}
					}

					return implode( ',', $values );
				},
				'options'           => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$contrat    = AmapressContrat_instance::getBy( $option->getPostID(), true );
					$reps       = $contrat ? $contrat->getCustomRepartitions() : [];
					$reps_dates = $contrat ? $contrat->getCustomRepartitionsDates() : [];

					$options     = [
						'1' => __( '1 chèque', 'amapress' ),
					];
					$is_readonly = amapress_is_contrat_instance_readonly( $option );
					for ( $i = 2; $i <= 12; $i ++ ) {
						$v       = isset( $reps[ $i ] ) ? $reps[ $i ] : '';
						$v_dates = isset( $reps_dates[ $i ] ) ? $reps_dates[ $i ] : [];
						$rep     = '';
						if ( $is_readonly ) {
							$v = esc_html( $v );
							if ( ! empty( $v ) ) {
								$rep .= '<em> ; ' . __( 'Répartition : ', 'amapress' ) . $v . '</em>';
							}
							if ( ! empty( $v_dates ) ) {
								$rep .= '<em> ; ' . __( 'Dates : ', 'amapress' ) . implode( ', ', $v_dates ) . '</em>';
							}
						} else {
							$v   = esc_attr( $v );
							$rep = "<br/>" . __( 'Répartition :', 'amapress' ) . "<input id='amapress_pmt_repartitions-$i'
                                       name='amapress_pmt_repartitions[$i]'
                                       class='text repartitionCheck'
                                       data-num='$i'
                                       style='width: 15em'
                                       value='$v' />";

							$all_liste_dates_options = [];
							foreach ( $contrat->getPaiements_Liste_dates() as $d ) {
								$v                             = date_i18n( TitanFrameworkOptionDate::$default_date_format, intval( $d ) );
								$all_liste_dates_options[ $v ] = $v;
							}
							$liste_dates_options = [];
							foreach ( $v_dates as $d ) {
								$liste_dates_options[ $d ] = $d;
							}
							$rep .= ' ; ' . __( 'Dates :', 'amapress' ) . " <select data-max='$i' style='width: 15em; display: inline-block' id='amapress_pmt_repartitions_dates-$i' name='amapress_pmt_repartitions_dates[$i][]' class='repartitions-dates repartitionDatesCheck' multiple='multiple' data-placeholder='" . esc_attr__( 'Dates de paiement', 'amapress' ) . "'>" .
							        tf_parse_select_options( $all_liste_dates_options, $liste_dates_options, false ) . '</select>';
						}
						$options[ strval( $i ) ] = ( $is_readonly ? '<strong>' : '' ) . sprintf( __( '%d chèques', 'amapress' ), $i ) . ( $is_readonly ? '</strong>' : '' ) .
						                           ( $is_readonly ? '' : '</label>' ) . "<span id='chq-rep-$i'>" . $rep . "</span>" . ( $is_readonly ? '' : '<label>' ); //hack for excluding $rep of wrapping label added to multicheck
					}

					return $options;
				},
				'custom_save'       => function ( $post_id ) {
					if ( isset( $_POST['amapress_pmt_repartitions'] ) ) {
						$amapress_pmt_repartitions = $_POST['amapress_pmt_repartitions'];
						foreach ( $amapress_pmt_repartitions as $i => $r ) {
							if ( empty( $r ) ) {
								unset( $amapress_pmt_repartitions[ $i ] );
							}
						}
						update_post_meta(
							$post_id,
							'amapress_contrat_instance_pmt_reps',
							$amapress_pmt_repartitions );
					}
					if ( isset( $_POST['amapress_pmt_repartitions_dates'] ) ) {
						$amapress_pmt_repartitions_dates = $_POST['amapress_pmt_repartitions_dates'];
						foreach ( $amapress_pmt_repartitions_dates as $i => $r ) {
							if ( empty( $r ) ) {
								unset( $amapress_pmt_repartitions_dates[ $i ] );
							}
						}
						update_post_meta(
							$post_id,
							'amapress_contrat_instance_pmt_reps_dates',
							$amapress_pmt_repartitions_dates );
					}

					return false;
				},
				'export'            => function ( $post_id ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( ! $contrat_instance ) {
						return '';
					}

					return implode( ',', $contrat_instance->getPossiblePaiements() );
				},
				'column'            => function ( $post_id ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( ! $contrat_instance ) {
						return '';
					}

					return implode( ',', $contrat_instance->getPossiblePaiements() );
				},
				'after_option'      => function ( $options ) {
					echo '<script type="application/javascript">
jQuery(function($) {
    $("input[name^=amapress_contrat_instance_paiements]").each(function() {
        var $this = $(this);
        if ($this.attr("name") != "amapress_contrat_instance_paiements[]")
            return;
        var $chqrep = $("#chq-rep-" + $this.attr("value"));
        var handleRepState = function() {
            if ($this.is(":checked")) {
                $chqrep.show();
            } else {
                $chqrep.hide();
            }
        };
        $this.change(handleRepState);
        handleRepState();
    });
    $(".repartitions-dates").select2MultiCheckboxes({
        templateSelection: function(selected, total, element) {
          return selected.length + "' . __( ' sur ', 'amapress' ) . '" + $(element).data("max");
        },
    });
});
</script>';
				}
			),
			'pay_month'             => array(
				'name'        => __( 'Paiement mensuel', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Activer la répartition mensuelle de la remise des règlements au producteur', 'amapress' ),
			),
			'pay_month_only'        => array(
				'name'        => __( 'Paiement mensuel uniquement', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'N\'autoriser que le paiement mensuel au producteur (pas de paiement total)', 'amapress' ),
			),
			'allow_deliv_pay'       => array(
				'name'        => __( 'A la livraison', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour régler les commandes dont le prix (lié au poids) est connu à la livraison', 'amapress' ),
			),
			'allow_cash'            => array(
				'name'        => __( 'Espèces', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en espèce', 'amapress' ),
			),
			'allow_bktrfr'          => array(
				'name'        => __( 'Virement', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement par virement', 'amapress' ),
			),
			'allow_locmon'          => array(
				'name'        => __( 'Monnaie locale', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en monnaie locale', 'amapress' ),
			),
			'allow_prlv'            => array(
				'name'        => __( 'Prélèvement', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement par prélèvement', 'amapress' ),
			),
			'stripe_public_key'     => array(
				'name'        => __( 'Clé Stripe (Publique)', 'amapress' ),
				'type'        => 'text',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'default'     => '',
				'show_column' => false,
				'desc'        => function ( $option ) {
					return __( 'Clé publique des paiement en ligne via Stripe pour ce contrat/producteur. Remplir ce champs active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien le paiement en ligne', 'amapress' );
				}
			),
			'stripe_secret_key'     => array(
				'name'        => __( 'Clé Stripe (secrète)', 'amapress' ),
				'type'        => 'text',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'default'     => '',
				'show_column' => false,
				'desc'        => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$prod_id          = - 1;
					$contrat_instance = AmapressContrat_instance::getBy( $option->getPostID() );
					if ( $contrat_instance && $contrat_instance->getModel() ) {
						$prod_id = $contrat_instance->getModel()->getProducteurId();
					}

					return __( 'Clé secrète des paiement en ligne via Stripe pour ce contrat/producteur.', 'amapress' ) .
					       ( $prod_id > 0 ? '<br/>' . Amapress::getWpConfigSecretHelp( "AMAPRESS_PRODUCTEUR_{$prod_id}_STRIPE_SECRET_KEY" ) : '' );
				}
			),
			'stripe_min_amount'     => array(
				'name'           => __( 'Montant min. Stripe', 'amapress' ),
				'type'           => 'number',
				'group'          => __( '6/6 - Règlements', 'amapress' ),
				'required'       => true,
				'show_column'    => true,
				'default'        => 0,
				'col_def_hidden' => true,
				'readonly'       => 'amapress_is_contrat_instance_readonly',
				'desc'           => __( 'Montant minimum requis pour activer le paiement en ligne Stripe', 'amapress' ),
			),
			'manage_paiements'      => array(
				'name'        => __( 'Répartition des règlements', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => __( 'Gérer la répartition et le suivi des règlements dans Amapress', 'amapress' ),
			),
			'paiements_mention'     => array(
				'name'        => __( 'Références', 'amapress' ),
				'type'        => 'editor',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => false,
				'desc'        => __( 'Donner des instructions dans l’assistant de pré-inscription en ligne concernant les modalités de règlement<br/>Le placeholder %%id%% peut être utilisé pour mentionner une référence d\'inscription', 'amapress' ),
			),
			'paiements_ordre'       => array(
				'name'        => __( 'Ordre des chèques', 'amapress' ),
				'type'        => 'text',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'show_column' => false,
				'desc'        => __( 'Indiquer l’ordre des chèques si différent du nom du producteur', 'amapress' ),
			),
			'min_cheque_amount'     => array(
				'name'        => __( 'Montant minimum', 'amapress' ),
				'type'        => 'number',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
				'required'    => true,
				'show_column' => false,
				'desc'        => __( 'Montant minimum du plus petit règlement pour les paiements en plusieurs fois', 'amapress' ),
			),
			'options_paiements'     => array(
				'name'        => __( 'Répartition', 'amapress' ),
				'type'        => 'custom',
				'group'       => __( '6/6 - Règlements', 'amapress' ),
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
							'title' => __( 'Date de début contrat', 'amapress' ),
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
								if ( $contrat_instance->getAllow_Prelevement() ) {
									foreach ( $contrat_instance->getPossiblePaiements() as $nb_cheque ) {
										$o = $contrat_instance->getChequeOptionsForTotal( $nb_cheque, $total,
											__( 'prélèvement', 'amapress' ), __( 'prélèvements', 'amapress' ) );
										if ( empty( $o ) ) {
											continue;
										}
										$options .= '<li>' . esc_html( $o['desc'] ) . '</li>';
									}
								}
							}
							if ( $contrat_instance->getAllow_Cash() ) {
								$options .= '<li>' . __( 'En espèces', 'amapress' ) . '</li>';
							}
							if ( $contrat_instance->getAllow_Transfer() ) {
								$options .= '<li>' . __( 'Par virement', 'amapress' ) . '</li>';
							}

							$row[ 'quant_' . $quant->ID ] = "<p>{$remaining_dates_factors} x {$price}€ = {$total}€</p><ul>{$options}</ul>";
						}
//					    foreach ($contrat_instance->gett)
						$data[] = $row;
					}

					$ret = '<p>' . __( 'Consulter notre <a href="#" id="show_options_cheques">proposition de répartition</a> du montant des versements pour vos contrats', 'amapress' ) . '</p>';
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
//                            'desc' => __('Quantités', 'amapress'),
//                            'group' => __('Distributions', 'amapress'),
//                            'post_type' => 'amps_contrat_quant',
//                            'parent' => 'amapress_contrat_quantite_contrat_instance',
//                        ),
			'inscriptions'          => array(
				'name'                     => __( 'Inscriptions', 'amapress' ),
				'show_column'              => true,
				'show_table'               => false,
				'hidden'                   => true,
				'group'                    => __( 'Inscriptions', 'amapress' ),
				'empty_text'               => __( 'Pas encore d\'inscriptions', 'amapress' ),
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
			'equiv_quants'          => array(
				'name'                 => __( 'Parts', 'amapress' ),
				'type'                 => 'custom',
				'hidden'               => true,
				'group'                => __( 'Inscriptions', 'amapress' ),
				'show_on'              => 'edit-only',
				'use_custom_as_column' => true,
				'custom'               => function ( $post_id ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );

					if ( ! $contrat_instance->hasEquivalentQuant() ) {
						return 'NA';
					}

					return $contrat_instance->getAdherentsEquivalentQuantites();
				},
			),
//			'contrat'           => array(
//				'name'       => __( 'Info contrat en ligne', 'amapress' ),
//				'type'       => 'editor',
//				'group'      => __('Pré-inscription en ligne', 'amapress'),
//				'desc'       => __('Configurer les informations supplémentaires à afficher lors de la souscription en ligne', 'amapress'),
//				'wpautop'    => false,
//				'searchable' => true,
//				'readonly'   => 'amapress_is_contrat_instance_readonly',
//			),
		),
	);
	$entities['contrat_quantite'] = array(
		'internal_name'    => 'amps_contrat_quant',
		'singular'         => __( 'Contrat quantité', 'amapress' ),
		'plural'           => __( 'Contrats quantités', 'amapress' ),
		'public'           => 'adminonly',
		'thumb'            => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'contrat_quantites',
		'quick_edit'       => false,
		'fields'           => array(
			'contrat_instance' => array(
				'name'              => __( 'Contrat', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'required'          => true,
				'csv_required'      => true,
				'desc'              => __( 'Contrat', 'amapress' ),
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
				'name'         => __( 'Code', 'amapress' ),
				'type'         => 'text',
				'csv_required' => true,
				'desc'         => __( 'Code', 'amapress' ),
				'import_key'   => true,
				'searchable'   => true,
			),
			'prix_unitaire'    => array(
				'name'         => __( 'Prix unitaire', 'amapress' ),
				'type'         => 'price',
				'required'     => true,
				'csv_required' => true,
				'unit'         => '€',
				'desc'         => __( 'Prix unitaire', 'amapress' ),
			),
			//que distrib
			'quantite'         => array(
				'name' => __( 'Coefficient de part', 'amapress' ),
				'type' => 'float',
			),
			//commandes
			'produits'         => array(
				'name'         => __( 'Produits', 'amapress' ),
				'type'         => 'select-posts',
				'post_type'    => AmapressProduit::INTERNAL_POST_TYPE,
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'csv'          => false,
			),
			'unit'             => array(
				'name'    => __( 'Unité', 'amapress' ),
				'type'    => 'select',
				'options' => array(
					'unit' => 'A l\'unité',
					'kg'   => __( 'Au kg', 'amapress' ),
					'l'    => __( 'Au litre', 'amapress' ),
					'cm'   => __( 'Au centimètre', 'amapress' ),
					'm'    => __( 'Au mètre', 'amapress' ),
				),
			),
			'quantite_config'  => array(
				'name'              => __( 'Choix quantité', 'amapress' ),
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
				'name' => __( 'Calendrier spécifique', 'amapress' ),
				'type' => 'multidate',
			),
			'grp_mult'         => array(
				'name' => __( 'Confection', 'amapress' ),
				'type' => 'number',
			),
			'max_adhs'         => array(
				'name' => __( 'Limite', 'amapress' ),
				'type' => 'number',
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
//                'desc' => __('Contrat', 'amapress'),
//            ),
//            'liste_dates' => array(
//                'name' => amapress__('Dates'),
//                'type' => 'custom',
//                'custom' => array(__('AmapressContrats', 'amapress'), "displayPaiementListeDates"),
//                'save' => array(__('AmapressContrats', 'amapress'), "savePaiementListeDates"),
//                'required' => true,
//                'desc' => __('Dates', 'amapress'),
//            ),
//        ),
//    );
	return $entities;
}

add_filter( 'amapress_contrat_fields', 'amapress_contrat_fields' );
function amapress_contrat_fields( $fields ) {
	$lieux = Amapress::get_lieux();
	if ( count( $lieux ) > 1 || isset( $_GET['all_lieux'] ) ) {
		foreach ( $lieux as $lieu ) {
			$fields[ 'referent_' . $lieu->ID ] = array(
				'name'         => sprintf( __( 'Référent %s', 'amapress' ), $lieu->getShortName() ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => __( '2/ Référents spécifiques', 'amapress' ),
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'searchable'   => true,
				'autocomplete' => true,
				'desc'         => sprintf( __( 'Référent producteur pour %s et spécifique pour cette production et son/ses contrat(s)', 'amapress' ), $lieu->getTitle() ),
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
		return new WP_Error( 'ignore_contrat', __( "Colonne contrat vide. La ligne sera ignorée.", 'amapress' ) );
	}

	$quants = [];
	foreach ( $postmeta as $k => $v ) {
		if ( strpos( $k, 'contrat_quant_' ) === 0 ) {
			$quant_id = intval( substr( $k, 14 ) );
			$quant    = AmapressContrat_quantite::getBy( $quant_id );
			if ( null == $quant ) {
				return new WP_Error( 'cannot_find_quant', sprintf( __( "Impossible de résoudre la quantité %s", 'amapress' ), $quant_id ) );
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
		return new WP_Error( 'ignore_contrat_quantite', __( 'Colonne quantité vide. La ligne sera ignorée.', 'amapress' ) );
	}

	$contrat_instance_id = Amapress::resolve_post_id( $postmeta['amapress_adhesion_contrat_instance'], AmapressContrat_instance::INTERNAL_POST_TYPE );
	if ( empty( $contrat_instance_id ) || $contrat_instance_id <= 0 ) {
		return new WP_Error( 'cannot_find_contrat', sprintf( __( "Impossible de trouver le contrat '%s'", 'amapress' ), $postmeta['amapress_adhesion_contrat_instance'] ) );
	}
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( empty( $contrat_instance ) ) {
		return new WP_Error( 'cannot_find_contrat', sprintf( __( "Impossible de trouver le contrat '%s'", 'amapress' ), $postmeta['amapress_adhesion_contrat_instance'] ) );
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

			return new WP_Error( 'invalid_date', sprintf( __( "La date de début %s est en dehors des dates (%s - %s) du contrat '%s'", 'amapress' ), $dt, $contrat_debut, $contrat_fin, $contrat_instance->getTitle() ) );
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
			$errors[]         = sprintf( __( "Valeur '%s' non valide pour '%s' (Voir <%s>)", 'amapress' ), $v, $contrat_instance->getTitle(), $url );
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
	echo '<td style="width: 20%">';
	echo '<span title="' . esc_attr__( 'Indiquez la nomenclature producteur', 'amapress' ) . '">' . esc_html__( 'Intitulé', 'amapress' ) . '</span>';
	echo "<br/><input style='width: 100%' type='text' class='required' name='amapress_quant_data[$id][title]' placeholder='" . __( "Nom produit", 'amapress' ) . "' value='$title' />";
	echo '<br/><span title="' . esc_attr__( 'Nom court pour liste d\'émargement', 'amapress' ) . '">' . esc_html__( 'Code', 'amapress' ) . '</span>';
	echo "<br/><input style='width: 100%' type='text' class='' name='amapress_quant_data[$id][code]' placeholder='" . __( "NP", 'amapress' ) . "' value='$code' />";
	echo '</td>';
	echo '<td>';
	echo '<span title="' . esc_attr__( 'Variantes de poids, de composition, de confection.', 'amapress' ) . '">' . esc_html__( 'Description', 'amapress' ) . '</span>';
	echo "<textarea style='width: 100%; height: 100%' rows='3' class='' name='amapress_quant_data[$id][desc]' placeholder='" . __( "Variante poids, composition, confection", 'amapress' ) . "'>{$description}</textarea>";
	echo '</td>';
	echo '<td style="width: 15%">';
	echo '<span title="' . esc_attr__( 'Quantités proposées par le producteur à l’amapien', 'amapress' ) . '">' . esc_html__( 'Choix quantité', 'amapress' ) . '</span>';
	echo "<br/><input style='width: 100%' type='text' class='text' name='amapress_quant_data[$id][quant_conf]' placeholder='" . __( "Nombre autorisé", 'amapress' ) . "' value='$quantite_conf' />";
	if ( $contrat_instance->isPanierVariable() || $contrat_instance->isQuantiteVariable() ) {
		echo '<br/><span title="' . esc_attr__( 'Regroupez des produits en carton', 'amapress' ) . '">' . esc_html__( 'Confection', 'amapress' ) . '</span>';
		echo "<br/><input type='number' class='required number' name='amapress_quant_data[$id][grp_mult]' min='0' step='1' placeholder='" . __( "Nom produit [Variante]", 'amapress' ) . "' value='$grp_mult' />";
	}
	echo '</td>';
	echo '<td style="width: 11em">';
	echo '<span title="' . esc_attr__( '', 'amapress' ) . '">' . esc_html__( 'Prix - Unité', 'amapress' ) . '</span>';
	echo "<br/><input style='width: 5em' type='number' class='required number' name='amapress_quant_data[$id][price]' min='0' step='0.01' placeholder='" . __( "Prix unitaire", 'amapress' ) . "' value='$price' />";
	if ( empty( $unit ) ) {
		$unit = 'unit';
	}
	echo "<select style='display: inline-block; width:5em;min-width: auto;' class='required' name='amapress_quant_data[$id][unit]'>";
	echo '<option value="">' . __( '--Unité de prix--', 'amapress' ) . '</option>';
	echo '<option ' . selected( 'unit', $unit, false ) . ' value="unit">' . __( 'pièce', 'amapress' ) . '</option>';
	echo '<option ' . selected( 'kg', $unit, false ) . ' value="kg">kg</option>';
	echo '<option ' . selected( 'l', $unit, false ) . ' value="l">L</option>';
	echo '<option ' . selected( 'cm', $unit, false ) . ' value="cm">cm</option>';
	echo '<option ' . selected( 'm', $unit, false ) . ' value="m">m</option>';
	echo '</select>';
	echo '<br/><span title="' . esc_attr__( 'Ajoutez un coefficient pour convertir les parts en quantités totales à communiquer au producteur', 'amapress' ) . '">' . esc_html__( 'Coefficient de part', 'amapress' ) . '</span>';
	echo "<br/><input style='width: 100%' type='number' class='required number' name='amapress_quant_data[$id][quant]' min='0' step='0.01' placeholder='" . __( "Coefficient", 'amapress' ) . "' value='$quantite' />";
	echo '</td>';
	echo '<td style="width: 10em">';
	echo '<span title="' . esc_attr__( 'Maximum de part ou d\'adhérent', 'amapress' ) . '">' . esc_html__( 'Limite', 'amapress' ) . '</span>';
	echo "<br/><input style='width: 100%' type='number' class='required number' name='amapress_quant_data[$id][max_adhs]' min='0' step='1' placeholder='" . esc_attr__( 'Maximum d’adhérents', 'amapress' ) . "' value='$max_adhs' />";
	echo '<br/><span title="' . esc_attr__( 'A compléter lorsque des produits issus d\'un même contrat ne sont pas livrés à toutes les dates', 'amapress' ) . '">' . esc_html__( 'Dates spécifiques', 'amapress' ) . '</span>';
	echo '<br/>';
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
    <select style='width: 5em' id="<?php echo "amapress_quant_data[$id][liste_dates]" ?>"
            name="<?php echo "amapress_quant_data[$id][liste_dates][]"; ?>"
            class="quant-dates" multiple="multiple"
            data-placeholder="<?php echo esc_attr__( 'Dates spé.', 'amapress' ) ?>">
		<?php
		tf_parse_select_options( $all_liste_dates_options, $liste_dates_options );
		?>
    </select>
	<?php
	echo '</td>';

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
    <p style="padding: 20px 10px 20px 0;" id="amp_config_paniers">
        <strong><?php _e( 'Configuration des paniers (Taille/Quantités)', 'amapress' ) ?></strong></p>
    <p><?php _e( 'Exporter les paniers vers Excel', 'amapress' ) ?><?php echo Amapress::makeButtonLink(
			AmapressExport_Posts::get_export_url( null,
				admin_url( 'edit.php?post_type=amps_contrat_quant&amapress_contrat_inst=' . $contrat_instance->ID . '&amapress_export=csv' ) ),
			'Export CSV' ); ?></p>
    <p><?php _e( 'Créer un panier à partir d’un modèle Excel', 'amapress' ) ?> <a
                href="<?php echo admin_url( 'admin.php?page=amapress_import_page&tab=import_quant_paniers&amapress_import_contrat_quantite_default_contrat_instance=' . $contrat_instance->ID ); ?>"
                target="_blank" class="button button-secondary"><?php _e( 'Import CSV', 'amapress' ) ?></a></p>
    <p style="padding-bottom: 20px"><span class="btn add-model dashicons dashicons-plus-alt"
                                          onclick="amapress_add_quant(this)"></span> <?php _e( 'Ajouter un produit', 'amapress' ) ?>
    </p>
    <input type="hidden" name="amapress_quant_data_contrat_instance_id"
           value="<?php echo $contrat_instance_id; ?>"/>
    <table id="quant_editor_table" class="table" style="width: 100%; border: 1pt solid black">
        <tbody>
		<?php
		foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance_id ) as $quant ) {
			$id   = $quant->ID;
			$tit  = esc_attr( $quant->getTitle() );
			$q    = esc_attr( $quant->getQuantite() );
			$c    = esc_attr( $quant->getCode() );
			$pr   = esc_attr( $quant->getPrix_unitaire() );
			$qc   = esc_attr( $quant->getQuantiteConfig() );
			$desc = esc_textarea( $quant->getDescription() );

			amapress_quantite_editor_line( $contrat_instance, $id, $tit, $c, $desc, $pr, $quant->getPriceUnit(),
				$qc, $q, implode( ',', $quant->getProduitsIds() ), get_post_thumbnail_id( $quant->ID ),
				$quant->getSpecificDistributionDates(), $quant->getMaxAdherents(), $quant->getGroupMultiple() );
		}
		?>
        </tbody>
    </table>
    <p class="description"><?php echo Amapress::makeWikiLink( 'https://wiki.amapress.fr/contrats/creation#configurer_les_paniers', __( 'Configurer les paniers', 'amapress' ) ) ?></p>
	<?php
	$contents = ob_get_clean();

	ob_start();
	amapress_quantite_editor_line( $contrat_instance, '%%id%%', '', '', '', 0, 0,
		'', 0, '', '', [], 0, 1 );

	$new_row = ob_get_clean();

	$new_row  = wp_json_encode( array( 'html' => $new_row ) );
	$contents .= "<script type='text/javascript'>//<![CDATA[
    jQuery(function($) {
        amapress_quant_load_tags();
    });
    function amapress_quant_load_tags() {
        jQuery('.quant-dates').select2MultiCheckboxes({
            templateSelection: function(selected, total) {
              return selected.length + \"" . __( ' sur ', 'amapress' ) . "\" + total;
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
        if (!confirm('" . esc_js( __( 'Voulez-vous vraiment supprimer cette quantité ?', 'amapress' ) ) . "')) return;
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
			wp_delete_post( $qid, true );
		}
		foreach ( $_POST['amapress_quant_data'] as $quant_id => $quant_data ) {
			$my_post = array(
				'post_title'   => $quant_data['title'],
				'post_type'    => AmapressContrat_quantite::INTERNAL_POST_TYPE,
				'post_content' => $quant_data['desc'],
				'post_status'  => 'publish',
				'meta_input'   => array(
					'amapress_contrat_quantite_contrat_instance' => $contrat_instance_id,
					'amapress_contrat_quantite_prix_unitaire'    => $quant_data['price'],
					'amapress_contrat_quantite_code'             => ! empty( $quant_data['code'] ) ? $quant_data['code'] : $quant_data['title'],
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
				wp_update_post( $my_post );
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
		wp_die( __( 'Une erreur s\'est produit lors du renouvellement du contrat. Veuillez réessayer', 'amapress' ) );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_renew_same_period', 'amapress_row_action_contrat_instance_renew_same_period' );
function amapress_row_action_contrat_instance_renew_same_period( $post_id ) {
	$contrat_inst         = AmapressContrat_instance::getBy( $post_id );
	$new_contrat_instance = $contrat_inst->cloneContrat( true, true, true );
	if ( ! $new_contrat_instance ) {
		wp_die( __( 'Une erreur s\'est produit lors du renouvellement du contrat. Veuillez réessayer', 'amapress' ) );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_clone', 'amapress_row_action_contrat_instance_clone' );
function amapress_row_action_contrat_instance_clone( $post_id ) {
	$contrat_inst = AmapressContrat_instance::getBy( $post_id );
	if ( $contrat_inst->isArchived() ) {
		wp_die( __( 'Impossible de dupliquer un contrat archivé', 'amapress' ) );
	}

	$new_contrat_instance = $contrat_inst->cloneContrat( true, false );
	if ( ! $new_contrat_instance ) {
		wp_die( __( 'Une erreur s\'est produit lors de la duplication du contrat. Veuillez réessayer', 'amapress' ) );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_clone_next_week', 'amapress_row_action_contrat_instance_clone_next_week' );
function amapress_row_action_contrat_instance_clone_next_week( $post_id ) {
	$contrat_inst = AmapressContrat_instance::getBy( $post_id );
	if ( $contrat_inst->isArchived() ) {
		wp_die( __( 'Impossible de dupliquer un contrat archivé', 'amapress' ) );
	}

	$new_contrat_instance = $contrat_inst->cloneContratForNextWeeks( 1, false );
	if ( ! $new_contrat_instance ) {
		wp_die( __( 'Une erreur s\'est produit lors de la duplication du contrat. Veuillez réessayer', 'amapress' ) );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_clone_next_next_week', 'amapress_row_action_contrat_instance_clone_next_next_week' );
function amapress_row_action_contrat_instance_clone_next_next_week( $post_id ) {
	$contrat_inst = AmapressContrat_instance::getBy( $post_id );
	if ( $contrat_inst->isArchived() ) {
		wp_die( __( 'Impossible de dupliquer un contrat archivé', 'amapress' ) );
	}

	$new_contrat_instance = $contrat_inst->cloneContratForNextWeeks( 2, false );
	if ( ! $new_contrat_instance ) {
		wp_die( __( 'Une erreur s\'est produit lors de la duplication du contrat. Veuillez réessayer', 'amapress' ) );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_clone_next_month', 'amapress_row_action_contrat_instance_clone_next_month' );
function amapress_row_action_contrat_instance_clone_next_month( $post_id ) {
	$contrat_inst = AmapressContrat_instance::getBy( $post_id );
	if ( $contrat_inst->isArchived() ) {
		wp_die( __( 'Impossible de dupliquer un contrat archivé', 'amapress' ) );
	}

	$new_contrat_instance = $contrat_inst->cloneContratForNextMonths( 1, false );
	if ( ! $new_contrat_instance ) {
		wp_die( __( 'Une erreur s\'est produit lors de la duplication du contrat. Veuillez réessayer', 'amapress' ) );
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
		_n_noop( __( 'Document <span class="count">(%s)</span>', 'amapress' ), __( 'Documents <span class="count">(%s)</span>', 'amapress' ) )
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
	if ( is_admin()
	     && amapress_can_access_admin()
	     && ! amapress_is_admin_or_responsable()
	     && ! isset( $_COOKIE[ AMAPRESS_ROLE_SETTER_COOKIE ] )
	) {
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
	if ( is_admin()
	     && amapress_can_access_admin()
	     && ! amapress_is_admin_or_responsable()
	     && ! isset( $_COOKIE[ AMAPRESS_ROLE_SETTER_COOKIE ] )
	) {
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
			wp_delete_post( $id, true );
		}
	}
}, 1000 );

add_filter( 'amapress_row_actions_label_contrat_instance', function ( $abel ) {
	return '';
} );

function amapress_contrat_instance_archivables_view() {
	$columns = array(
		array(
			'title' => __( 'Contrat', 'amapress' ),
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
			'archive' => Amapress::makeLink( $archive_link, __( 'Archiver', 'amapress' ) ),
		);
	}

	return '<p class="description">' . sprintf( __( 'Les contrats ci-dessous sont terminés depuis au moins %d mois.', 'amapress' ), Amapress::getOption( 'archive_months', 3 ) ) . '</p>'
	       . ( ! empty( $data ) ? '<p>' . Amapress::makeLink( add_query_arg(
				array(
					'action' => 'archive_all_contrat',
				),
				admin_url( 'admin-post.php' )
			), 'Archiver tous les contrats archivables' ) . '</p>' : '' )
	       . amapress_get_datatable( 'contrat-archivables-table', $columns, $data );
}

add_action( 'admin_post_archive_all_contrat', function () {
	if ( ! isset( $_REQUEST['confirm'] ) ) {
		echo '<p>';
		echo sprintf( __( 'Etes-vous sûr de vouloir archiver tous les contrats archivables ? 
<br />
<a href="%s"> Confirmer l\'archivage</a>', 'amapress' ), add_query_arg( 'confirm', 'yes' ) );
		echo '</p>';
		die();
	}

	if ( 'yes' != $_REQUEST['confirm'] ) {
		wp_die( __( 'Archivage abandonné.', 'amapress' ) );
	}
	foreach ( AmapressContrat_instance::getAll() as $contrat_instance ) {
		if ( ! $contrat_instance->canBeArchived() || $contrat_instance->isArchived() ) {
			continue;
		}

		if ( ! current_user_can( 'edit_contrat_instance', $contrat_instance->ID ) ) {
			continue;
		}

		echo '<h4 style="font-weight: bold; font-size: 1.5em">' . esc_html( $contrat_instance->getTitle() ) . '</h4>';

		$contrat_instance->archive();

		echo '<p style="color: green">' . __( 'Archivage effectué', 'amapress' ) . '</p>';

		echo '<p><a href="' . esc_attr( admin_url( 'admin.php?page=contrats_archives' ) ) . '">' . __( 'Retour à la liste des contrats archivables', 'amapress' ) . '</a></p>';
	}
} );

add_action( 'admin_post_archive_contrat', function () {
	$contrat_id = isset( $_REQUEST['contrat_id'] ) ? intval( $_REQUEST['contrat_id'] ) : 0;
	$contrat    = AmapressContrat_instance::getBy( $contrat_id );
	if ( empty( $contrat ) ) {
		wp_die( __( 'Contrat inconnu', 'amapress' ) );
	}

	if ( ! current_user_can( 'edit_contrat_instance', $contrat_id ) ) {
		wp_die( __( 'Vous n\'avez pas le droit d\'archiver ce contrat', 'amapress' ) );
	}

	if ( $contrat->isArchived() ) {
		wp_die( __( 'Contrat déjà archivé', 'amapress' ) );
	}

	if ( ! $contrat->canBeArchived() ) {
		wp_die( __( 'Contrat non archivable', 'amapress' ) );
	}

	if ( ! isset( $_REQUEST['confirm'] ) ) {
		echo '<p>';
		echo sprintf( __( 'Etes-vous sûr de vouloir archiver le contrat %s ? 
<br />
<a href = "%s"> Confirmer l\'archivage</a>', 'amapress' ), esc_html( $contrat->getTitle() ), add_query_arg( 'confirm', 'yes' ) );
		echo '</p>';
		die();
	}

	if ( 'yes' != $_REQUEST['confirm'] ) {
		wp_die( sprintf( __( 'Archivage du contrat %s abandonné.', 'amapress' ), esc_html( $contrat->getTitle() ) ) );
	}

	$contrat->archive();

	echo '<p style="color: green">' . __( 'Archivage effectué', 'amapress' ) . '</p>';

	echo '<p><a href="' . esc_attr( admin_url( 'admin.php?page=contrats_archives' ) ) . '">' . __( 'Retour à la liste des contrats archivables', 'amapress' ) . '</a></p>';
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