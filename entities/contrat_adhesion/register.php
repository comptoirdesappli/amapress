<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion' );
function amapress_register_entities_adhesion( $entities ) {
	$entities['adhesion'] = array(
		'singular'         => __( 'Inscription Contrat', 'amapress' ),
		'plural'           => __( 'Inscriptions Contrat', 'amapress' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => false,
		'editor'           => false,
		'slug'             => 'adhesions',
		'title_format'     => 'amapress_adhesion_title_formatter',
		'slug_format'      => 'from_title',
		'menu_icon'        => 'flaticon-signature',
//		'show_admin_bar_new' => true,
		'labels'           => array(
			'add_new'      => __( 'Ajouter', 'amapress' ),
			'add_new_item' => __( 'Ajout Inscription', 'amapress' ),
		),
		'groups'           => array(
			__( 'Infos', 'amapress' ) => [
				'context' => 'side',
			],
		),
		'row_actions'      => array(
			//visibilité checkée dans amapress_row_actions_adhesion
			'renew'                => __( 'Renouveler', 'amapress' ),
			'close'                => [
				'label'     => __( 'Clôturer à la fin', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion::CONFIRMED == AmapressAdhesion::getBy( $adh_id )->getStatus();
				},
				'confirm'   => true,
			],
			'generate_contrat'     => [
				'label'     => __( 'Générer le contrat (DOCX)', 'amapress' ),
				'condition' => function ( $adh_id ) {
					if ( TitanFrameworkOption::isOnNewScreen() ) {
						return false;
					}

					$adh = AmapressAdhesion::getBy( $adh_id );
					if ( empty( $adh ) ) {
						return false;
					}
					if ( empty( $adh->getContrat_instance() ) ) {
						return false;
					}

					return ! empty( $adh->getContrat_instance()->getContratModelDocFileName() );
				},
			],
			'generate_contrat_pdf' => [
				'label'     => __( 'Générer le contrat (PDF)', 'amapress' ),
				'condition' => function ( $adh_id ) {
					if ( TitanFrameworkOption::isOnNewScreen() ) {
						return false;
					}

					$adh = AmapressAdhesion::getBy( $adh_id );
					if ( empty( $adh ) ) {
						return false;
					}
					if ( empty( $adh->getContrat_instance() ) ) {
						return false;
					}

					return ! empty( $adh->getContrat_instance()->getContratModelDocFileName() );
				},
			],
			'send_confirmation'    => [
				'label'     => __( 'Envoyer email confirmation', 'amapress' ),
				'confirm'   => true,
				'condition' => function ( $adh_id ) {
					return TitanFrameworkOption::isOnEditScreen();
				},
			],
			'accept'               => [
				'label'     => __( 'Confirmer inscription', 'amapress' ),
				'confirm'   => true,
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesion::getBy( $adh_id );

					return AmapressAdhesion::TO_CONFIRM == $adh->getStatus()
					       && 'stp' != $adh->getMainPaiementType();
				},
			],
			'add_compl'            => [
				'label'   => __( 'Ajouter inscription complémentaire', 'amapress' ),
				'confirm' => true,
				'show_on' => 'editor',
				'target'  => '_blank',
				'href'    => function ( $adh_id ) {
					$adh  = AmapressAdhesion::getBy( $adh_id );
					$args = [
						'amapress_adhesion_adherent'         => $adh->getAdherentId(),
						'amapress_adhesion_adherent2'        => $adh->getAdherent2Id(),
						'amapress_adhesion_adherent3'        => $adh->getAdherent3Id(),
						'amapress_adhesion_adherent4'        => $adh->getAdherent4Id(),
						'amapress_adhesion_contrat_instance' => $adh->getContrat_instanceId(),
						'amapress_adhesion_lieu'             => $adh->getAdherent4Id(),
						'amapress_adhesion_related'          => $adh->ID,
						'amapress_adhesion_date_debut'       => date_i18n( 'd/m/Y', Amapress::add_days( $adh->getDate_fin(), 1 ) ),
					];
					foreach ( $args as $k => $v ) {
						if ( empty( $v ) ) {
							unset( $args[ $k ] );
						}
					}

					return add_query_arg( $args, 'post-new.php?post_type=amps_adhesion' );
				},
				'condition' => function ( $adh_id ) {
					if ( TitanFrameworkOption::isOnNewScreen() ) {
						return false;
					}
					$adh = AmapressAdhesion::getBy( $adh_id );

					return $adh->hasBeforeEndDate_fin();
				},
			],
		),
		'bulk_actions'     => array(
			'amp_accept_contrat_adhesion'         => array(
				'label'    => __( 'Confirmer inscription', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Une inscription a été confirmée avec succès', 'amapress' ),
					'>1' => '%s inscriptions ont été confirmée avec succès',
				),
			),
			'amp_resend_confirm_contrat_adhesion' => array(
				'label'    => __( 'Envoyer l\'email de confirmation inscription', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Une inscription a été confirmée avec succès', 'amapress' ),
					'>1' => '%s inscriptions ont été confirmée avec succès',
				),
			),
		),
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'edit_header'      => function ( $post ) {
			TitanFrameworkOption::echoFullEditLinkAndWarning();

			echo '<script type="text/javascript">jQuery(function($) { $("body > div#ui-datepicker-div").hide(); });</script>';

			$adh = AmapressAdhesion::getBy( $post );
			if ( ! $adh->getContrat_instance() || ! $adh->getAdherent() ) {
				return;
			}
			if ( $adh->getContrat_instance()->isPrincipal() ) {
				return;
			}

			$principal_contrat            = null;
			$principal_contrat_date_debut = 0;
			Amapress::setFilterForReferent( false );
			$contrats = AmapressContrats::get_active_contrat_instances( null, $adh->getDate_debut(), true );
			Amapress::setFilterForReferent( true );
			foreach ( $contrats as $contrat ) {
				if ( $contrat->isPrincipal() && $contrat->getDate_debut() < $adh->getDate_fin() && $contrat->getDate_debut() > $principal_contrat_date_debut ) {
					$principal_contrat            = $contrat;
					$principal_contrat_date_debut = $contrat->getDate_debut();
				}
			}

			$message = '';
			if ( $principal_contrat ) {
				Amapress::setFilterForReferent( false );
				$other_adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $adh->getAdherentId(), $principal_contrat->ID, $adh->getDate_debut(), true );
				Amapress::setFilterForReferent( true );
				if ( ! empty( $other_adhs ) ) {
					return;
				}
				$message = sprintf( __( 'L\'amapien %s n\'a pas de contrat principal : %s', 'amapress' ), $adh->getAdherent()->getDisplayName(), $principal_contrat->getTitle() );
			} else if ( ! Amapress::getOption( 'disable_principal', false ) ) {
				$message = __( 'Pas de contrat principal actif', 'amapress' );
			}

			if ( ! empty( $message ) ) {
				$class = 'notice notice-warning';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}
		},
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_adhesion_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'adherent_display'  => array(
				'csv_import'    => false,
				'csv_export'    => true,
				'hidden'        => true,
				'group'         => __( '1/ Informations', 'amapress' ),
				'name'          => __( 'Adhérent', 'amapress' ),
				'join_meta_key' => 'amapress_adhesion_adherent',
				'join_on'       => 'user',
				'sort_column'   => 'display_name',
				'type'          => 'custom',
				'column'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return Amapress::makeLink( $adh->getAdherent()->getEditLink(), $adh->getAdherent()->getDisplayName(), true, true );
				},
				'export'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getDisplayName();
				}
			),
			'adherent'          => array(
				'name'         => __( 'Adhérent', 'amapress' ),
				'type'         => 'select-users',
				'required'     => true,
				'group'        => __( '1/ Informations', 'amapress' ),
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'searchable'   => true,
				'csv_export'   => false,
				'show_column'  => false,
				'readonly'     => function ( $post_id ) {
					return TitanFrameworkOption::isOnEditScreen();
				}
			),
			'adherent_lastname' => array(
				'csv_import'    => false,
				'csv_export'    => true,
				'hidden'        => true,
				'group'         => __( '1/ Informations', 'amapress' ),
				'name'          => __( 'Nom', 'amapress' ),
				'type'          => 'custom',
				'join_meta_key' => 'amapress_adhesion_adherent',
				'sort_column'   => 'last_name',
				'join_on'       => 'user_meta',
				'column'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getUser()->last_name;
				}
			),
			'adherent_email'    => array(
				'csv_import'    => false,
				'csv_export'    => true,
				'hidden'        => true,
				'group'         => __( '1/ Informations', 'amapress' ),
				'name'          => __( 'Email', 'amapress' ),
				'type'          => 'custom',
				'join_meta_key' => 'amapress_adhesion_adherent',
				'join_on'       => 'user',
				'sort_column'   => 'user_email',
				'column'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getUser()->user_email;
				}
			),
			'adherent_address'  => array(
				'csv_import' => false,
				'csv_export' => true,
				'hidden'     => true,
				'group'      => __( '1/ Informations', 'amapress' ),
				'name'       => __( 'Adresse', 'amapress' ),
				'type'       => 'custom',
				'column'     => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getFormattedAdresse();
				}
			),
			'status'            => array(
				'name'     => __( 'Statut', 'amapress' ),
				'type'     => 'select',
				'group'    => __( 'Infos', 'amapress' ),
				'options'  => array(
					'to_confirm' => __( 'A confirmer', 'amapress' ),
					'confirmed'  => __( 'Confirmée', 'amapress' ),
				),
				'default'  => function ( $option ) {
					$my_roles = AmapressContrats::getReferentProducteursAndLieux( amapress_current_user_id() );
					if ( ! empty( $my_roles ) ) {
						return 'confirmed';
					} else {
						return 'to_confirm';
					}
				},
				'required' => true,
				'readonly' => 'amapress_is_contrat_adhesion_readonly',
				//				'desc'     => __('Statut', 'amapress'),
			),
			'quantites_editor'  => array(
				'name'         => __( 'Contrat et Quantité(s)', 'amapress' ),
				'type'         => 'custom',
				'show_column'  => false,
				'custom'       => 'amapress_adhesion_contrat_quantite_editor',
				'save'         => 'amapress_save_adhesion_contrat_quantite_editor',
				'desc'         => __( 'Sélectionner <strong>le contrat*</strong> et les quantités/produits associé(s) de cette inscription :
<br/><strong>* Vous ne pouvez créer une inscription qu\'à un seul contrat à la fois</strong></br/></br/>', 'amapress' ),
				'show_desc'    => 'before',
				'group'        => __( '2/ Contrat', 'amapress' ),
				'after_option' => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					echo '<p>' . Amapress::makeLink(
							add_query_arg( [
								'page'     => 'amps_inscr_details',
								'inscr_id' => $option->getPostID(),
							],
								admin_url( 'admin.php' )
							),
							__( 'Récapitulatif des livraisons', 'amapress' ), true, true
						) . '</p>';
				},
				'csv'          => false,
//                'show_on' => 'edit',
			),
			'contrat_instance'  => array(
				'name'              => __( 'Contrat', 'amapress' ),
				'type'              => 'select-posts',
//                'readonly' => 'edit',
				'hidden'            => true,
				'group'             => __( '2/ Contrat', 'amapress' ),
				'post_type'         => 'amps_contrat_inst',
				'desc'              => __( 'Contrat', 'amapress' ),
				'import_key'        => true,
//                'required' => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_contrat_inst',
					'placeholder' => __( 'Tous les contrats', 'amapress' )
				),
				'csv_required'      => true,
				'searchable'        => true,
				'custom_multi'      => function ( $option, $post_id ) {
					$ret = [];
					foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
						$ret[ $c->ID ] = $c->getTitle();
					}

					return $ret;
				},
				'custom_csv_sample' => function ( $option, $arg ) {
					$ret = [];
					foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
						$ret[ $c->ID ] = $c->getTitle();
					}

					return $ret;
				}

			),
			'contrat_quantite' => array(
				'name'              => __( 'Quantité', 'amapress' ),
				'type'              => 'custom',
				'readonly'          => true,
				'hidden'            => true,
				'group'             => __( '2/ Contrat', 'amapress' ),
				'required'          => true,
				'post_type'         => 'amps_contrat_quant',
				'desc'              => __( 'Quantité', 'amapress' ),
				'column'            => function ( $post_id, $option = null ) {
					if ( ! $post_id ) {
						return '';
					}
					$adh = AmapressAdhesion::getBy( $post_id );

					return $adh->getContrat_quantites_AsString();
				},
				'custom_multi'      => function ( $option, $post_id ) {
					$ret = [];
					foreach ( AmapressContrats::get_contrat_quantites( $post_id ) as $c ) {
						$ret[ $c->ID ] = $c->getTitle();
					}

					return $ret;
				},
				'custom_csv_sample' => function ( $option, $arg ) {
					if ( $arg['multi'] != - 1 ) {
						if ( isset( $arg['post_id'] ) && $arg['post_id'] ) {
							$ret = [
								''
							];
							$q   = AmapressContrat_quantite::getBy( $arg['multi'] );
							if ( $q->getContrat_instance()->isQuantiteVariable() ) {
								foreach ( $q->getQuantiteOptions() as $v ) {
									$ret[] = $v;
								}
							} else {
								$ret[] = '1';
								$ret[] = 'X';
							}


							return $ret;
						} else {
							$c            = AmapressContrat_instance::getBy( $arg['multi'] );
							$ret          = $c->getSampleQuantiteCSV();
							$filtered_ret = [];
							foreach ( $ret as $r ) {
								if ( ! in_array( $r, $filtered_ret ) ) {
									$filtered_ret[] = $r;
								}
							}

							return $filtered_ret;
						}
					} else {
						$ret = [];
						foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
							$ret[]        = sprintf( __( '**Pour le contrat <%s>**', 'amapress' ), $c->getTitle() );
							$contrat_ret  = $c->getSampleQuantiteCSV();
							$filtered_ret = [];
							foreach ( $contrat_ret as $r ) {
								if ( ! in_array( $r, $filtered_ret ) ) {
									$filtered_ret[] = $r;
									$ret[]          = $r;
								}
							}
						}

						return $ret;
					}
				},
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_contrat_qt',
					'placeholder' => __( 'Toutes les quantités', 'amapress' )
				),
				'csv_required'      => true,
				'wrap_edit'         => false,
//                'import_key' => true,
//                'csv_required' => true,
			),
			'prod_msg'         => array(
				'name'           => __( 'Message au producteur', 'amapress' ),
				'type'           => 'textarea',
				'group'          => __( '2/ Contrat', 'amapress' ),
				'desc'           => __( 'Message de commande au producteur', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_adhesion_readonly',
				'col_def_hidden' => true,
			),
			'date_debut'       => array(
				'name'          => __( 'Date de début', 'amapress' ),
				'type'          => 'date',
				'required'      => true,
				'group'         => __( '2/ Contrat', 'amapress' ),
				'desc'          => __( 'Date à laquelle démarre le contrat', 'amapress' ),
				'csv_required'  => true,
				'default'       => function ( $option = null ) {
					return amapress_time();
				},
				'readonly'      => 'amapress_is_contrat_adhesion_readonly',
				'top_filter'    => array(
					'name'           => 'amapress_date',
					'placeholder'    => __( 'Toutes les dates', 'amapress' ),
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'before_option' =>
					function ( $option ) {
						/** @var TitanFrameworkOption $option */
						if ( TitanFrameworkOption::isOnEditScreen() ) {
							$adh = AmapressAdhesion::getBy( $option->getPostID() );
							if ( null == $adh || null == $adh->getContrat_instance() ) {
								return;
							}
							echo '<script type="text/javascript">
jQuery(function($) {
    var $date_debut = $("#amapress_adhesion_date_debut");
    $date_debut.datepicker("option", 
    	{
    	    minDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_debut() ) . '",
    	    maxDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_fin() ) . '"
    	}
    );
});
</script>';
						}
					},
			),
			'pmt_type'          => array(
				'name'           => __( 'Moyen de règlement principal', 'amapress' ),
				'type'           => 'select',
				'group'          => __( '3/ Paiements', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_adhesion_readonly',
				'options'        => array(
					'chq' => __( 'Chèque', 'amapress' ),
					'esp' => __( 'Espèces', 'amapress' ),
					'stp' => __( 'Paiement en ligne (Stripe)', 'amapress' ),
					'vir' => __( 'Virement', 'amapress' ),
					'mon' => __( 'Monnaie locale', 'amapress' ),
					'dlv' => __( 'A la livraison', 'amapress' ),
					'prl' => __( 'Prélèvement', 'amapress' ),
				),
				'default'        => 'chq',
				'required'       => true,
				'desc'           => __( 'Moyen de règlement principal : chèques ou espèces ou virement ou prélèvement', 'amapress' ),
				'show_column'    => true,
				'col_def_hidden' => true,
				'top_filter'     => array(
					'name'        => 'amapress_pmt_type',
					'placeholder' => __( 'Tous les type de paiement', 'amapress' ),
				),
			),
			'paiements'         => array(
				'name'           => __( 'Nombre de paiements', 'amapress' ),
				'type'           => 'custom',
				'group'          => __( '3/ Paiements', 'amapress' ),
				'required'       => true,
				'desc'           => __( 'Nombre de paiements. <b>Lorsque vous changer la valeur de ce champs, il est nécessaire d\'enregistrer l\'adhésion</b>', 'amapress' ),
				'custom'         => 'amapress_paiements_count_editor',
				'show_on'        => 'edit-only',
				'show_column'    => true,
				'col_def_hidden' => true,
				'csv_import'     => false,
				'custom_column'  => function ( $option, $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );

					echo $adh->getPaiements();
				},
				'custom_export'  => function ( $option, $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );

					echo $adh->getPaiements();
				},
//                'csv_required' => true,
			),
			'paiements_editor'  => array(
				'name'        => __( 'Details des paiements', 'amapress' ),
				'type'        => 'custom',
				'show_column' => false,
				'custom'      => 'amapress_paiements_editor',
				'save'        => 'amapress_save_paiements_editor',
//                'desc' => __('Details des', 'amapress'),
				'group'       => __( '3/ Paiements', 'amapress' ),
				'csv'         => false,
				'show_on'     => 'edit-only',
			),
			'lieu'              => array(
				'name'              => __( 'Lieu', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_lieu',
				'required'          => true,
				'desc'              => __( 'Sélectionner le lieu de distribution', 'amapress' ),
				'group'             => __( '2/ Contrat', 'amapress' ),
				'import_key'        => true,
				'csv_required'      => true,
				'autoselect_single' => true,
				'searchable'        => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'readonly'          => 'amapress_is_contrat_adhesion_readonly',
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => __( 'Tous les lieux', 'amapress' )
				),
				'default'           => function ( $option = null ) {
					if ( ! empty( $_GET['amapress_adhesion_adherent'] ) ) {
						$user_lieux = AmapressUsers::get_user_lieu_ids( intval( $_GET['amapress_adhesion_adherent'] ) );

						return array_shift( $user_lieux );
					}

					return 0;
				}
			),
			'related'           => array(
				'name'           => __( 'Inscription liée', 'amapress' ),
				'type'           => 'select',
				'options'        => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$adhesion_id = $option->getPostID();
					$adhesion    = AmapressAdhesion::getBy( $adhesion_id );
					$ret         = [];
					if ( $adhesion && empty( $_GET['amapress_adhesion_related'] ) ) {
						foreach (
							get_posts(
								array(
									'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
									'posts_per_page' => - 1,
									'meta_query'     => array(
										array(
											'key'     => 'amapress_adhesion_contrat_instance',
											'value'   => $adhesion->getContrat_instanceId(),
											'compare' => '=',
											'type'    => 'NUMERIC',
										),
										array(
											'key'     => 'amapress_adhesion_adherent',
											'value'   => $adhesion->getAdherentId(),
											'compare' => '=',
											'type'    => 'NUMERIC',
										),
									),
								)
							) as $prev_adhesion
						) {
							$ret[ $prev_adhesion->ID ] = $prev_adhesion->post_title;
						}
						if ( $adhesion->getRelatedAdhesion() ) {
							$prev_adhesion             = $adhesion->getRelatedAdhesion();
							$ret[ $prev_adhesion->ID ] = $prev_adhesion->getTitle();
						}
					}
					if ( ! empty( $_REQUEST['amapress_adhesion_related'] ) ) {
						$prev_adhesion             = AmapressAdhesion::getBy( intval( $_REQUEST['amapress_adhesion_related'] ) );
						$ret[ $prev_adhesion->ID ] = $prev_adhesion->getTitle();
					}
					unset( $ret[ $adhesion_id ] );
					$ret[0] = __( 'Aucune', 'amapress' );

					return $ret;
				},
				'hidden'         => function ( $option ) {
					return TitanFrameworkOption::isOnNewScreen() && empty( $_REQUEST['amapress_adhesion_related'] );
				},
//				'show_on'     => 'edit-only',
				'desc'           => __( 'Sélectionner l\'inscription précédente en cas de changement de quantités en cours d\'année', 'amapress' ),
				'group'          => __( '2/ Contrat', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_adhesion_readonly',
				'show_column'    => true,
				'col_def_hidden' => true,
				'csv_import'     => false,
			),
			'message'          => array(
				'name'           => __( 'Message aux référents', 'amapress' ),
				'type'           => 'textarea',
				'readonly'       => true,
				'group'          => __( '2/ Contrat', 'amapress' ),
				'desc'           => __( 'Message aux référents lors de l\'inscription en ligne', 'amapress' ),
				'col_def_hidden' => true,
			),
			'don_dist'          => array(
				'name'           => __( 'Don par distribution', 'amapress' ),
				'type'           => 'number',
				'group'          => __( '2/ Contrat', 'amapress' ),
				'desc'           => __( 'Don par distribution en plus du prix unitaire du panier', 'amapress' ),
				'col_def_hidden' => true,
				'min'            => 0,
				'default'        => 0,
				'unit'           => __( '€', 'amapress' ),
			),
			'all-coadherents'   => array(
				'name'            => __( 'Co-adhérents', 'amapress' ),
				'group'           => __( '4/ Coadhérents', 'amapress' ),
				'show_column'     => false,
				'include_columns' => array(
					'name',
					'email',
					'role',
					'amapress_user_telephone',
					'amapress_user_adresse',
				),
				'type'            => 'related-users',
				'query'           => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );

					return 'amapress_coadherents=' . $adh->getAdherent()->ID;
				},
			),
			'adherent2'         => array(
				'name'          => __( 'Co-Adhérent 1', 'amapress' ),
				'type'          => 'select-users',
				'required'      => false,
				'desc'          => function ( $o ) {
					return sprintf( __( 'Sélectionner un Co-Adhérent 1 si spécifique à ce contrat. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="%s" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs', 'amapress' ), admin_url( 'user-new.php' ) );
				},
				'group'         => __( '4/ Coadhérents', 'amapress' ),
				'readonly'      => 'amapress_is_contrat_adhesion_readonly',
				'autocomplete'  => true,
				'searchable'    => true,
				'custom_column' => function ( $option, $post_id ) {
					$user = AmapressAdhesion::getBy( $post_id );
					if ( $user->getAdherent() ) {
						if ( $user->getAdherent2() ) {
							echo $user->getAdherent2()->getDisplayNameWithAdminEditLink();
						} else if ( ! Amapress::hasPartialCoAdhesion() && $user->getAdherent()->getCoAdherent1() ) {
							echo $user->getAdherent()->getCoAdherent1()->getDisplayNameWithAdminEditLink();
						}
					}
				},
				'custom_export' => function ( $option, $post_id ) {
					$user = AmapressAdhesion::getBy( $post_id );
					if ( $user->getAdherent() ) {
						if ( $user->getAdherent2() ) {
							echo $user->getAdherent2()->getDisplayName();
						} else if ( ! Amapress::hasPartialCoAdhesion() && $user->getAdherent()->getCoAdherent1() ) {
							echo $user->getAdherent()->getCoAdherent1()->getDisplayName();
						}
					}
				},
			),
			'adherent3'         => array(
				'name'           => __( 'Co-Adhérent 2', 'amapress' ),
				'type'           => 'select-users',
				'required'       => false,
				'desc'           => function ( $o ) {
					return sprintf( __( 'Sélectionner un Co-Adhérent 2 si spécifique à ce contrat. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="%s" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs', 'amapress' ), admin_url( 'user-new.php' ) );
				},
				'group'          => __( '4/ Coadhérents', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_adhesion_readonly',
				'autocomplete'   => true,
				'searchable'     => true,
				'col_def_hidden' => true,
				'custom_column'  => function ( $option, $post_id ) {
					$user = AmapressAdhesion::getBy( $post_id );
					if ( $user->getAdherent() ) {
						if ( $user->getAdherent3() ) {
							echo $user->getAdherent3()->getDisplayNameWithAdminEditLink();
						} else if ( ! Amapress::hasPartialCoAdhesion() && $user->getAdherent()->getCoAdherent2() ) {
							echo $user->getAdherent()->getCoAdherent2()->getDisplayNameWithAdminEditLink();
						}
					}
				},
				'custom_export'  => function ( $option, $post_id ) {
					$user = AmapressAdhesion::getBy( $post_id );
					if ( $user->getAdherent() ) {
						if ( $user->getAdherent3() ) {
							echo $user->getAdherent3()->getDisplayName();
						} else if ( ! Amapress::hasPartialCoAdhesion() && $user->getAdherent()->getCoAdherent2() ) {
							echo $user->getAdherent()->getCoAdherent2()->getDisplayName();
						}
					}
				},
			),
			'adherent4'         => array(
				'name'           => __( 'Co-Adhérent 3', 'amapress' ),
				'type'           => 'select-users',
				'required'       => false,
				'desc'           => function ( $o ) {
					return sprintf( __( 'Sélectionner un Co-Adhérent 3 si spécifique à ce contrat. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="%s" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs', 'amapress' ), admin_url( 'user-new.php' ) );
				},
				'group'          => __( '4/ Coadhérents', 'amapress' ),
				'readonly'       => 'amapress_is_contrat_adhesion_readonly',
				'autocomplete'   => true,
				'searchable'     => true,
				'col_def_hidden' => true,
				'custom_column'  => function ( $option, $post_id ) {
					$user = AmapressAdhesion::getBy( $post_id );
					if ( $user->getAdherent() ) {
						if ( $user->getAdherent4() ) {
							echo $user->getAdherent4()->getDisplayNameWithAdminEditLink();
						} else if ( ! Amapress::hasPartialCoAdhesion() && $user->getAdherent()->getCoAdherent3() ) {
							echo $user->getAdherent()->getCoAdherent3()->getDisplayNameWithAdminEditLink();
						}
					}
				},
				'custom_export'  => function ( $option, $post_id ) {
					$user = AmapressAdhesion::getBy( $post_id );
					if ( $user->getAdherent() ) {
						if ( $user->getAdherent4() ) {
							echo $user->getAdherent4()->getDisplayName();
						} else if ( ! Amapress::hasPartialCoAdhesion() && $user->getAdherent()->getCoAdherent3() ) {
							echo $user->getAdherent()->getCoAdherent3()->getDisplayName();
						}
					}
				},
			),
			'date_fin'          => array(
				'name'           => __( 'Date de fin', 'amapress' ),
				'type'           => 'date',
				'group'          => __( '5/ Fin de contrat avant terme', 'amapress' ),
				'desc'           => __( 'Date à laquelle se termine le contrat', 'amapress' ),
				'col_def_hidden' => true,
				'show_column'    => true,
				'show_on'        => 'edit-only',
				'before_option'  =>
					function ( $option ) {
						/** @var TitanFrameworkOption $option */
						if ( TitanFrameworkOption::isOnEditScreen() ) {
							$adh = AmapressAdhesion::getBy( $option->getPostID() );
							echo '<script type="text/javascript">
jQuery(function($) {
    var $date_debut = $("#amapress_adhesion_date_fin");
    $date_debut.datepicker("option", 
    	{
    	    minDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_debut() ) . '",
    	    maxDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_fin() ) . '"
    	}
    );
});
</script>';
						}
					},
			),
			'pmt_fin'           => array(
				'name'        => __( 'Date fin des paiements', 'amapress' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'group'       => __( '5/ Fin de contrat avant terme', 'amapress' ),
				'desc'        => __( 'Prendre en compte la date de fin pour recalculer le montant de l\'inscription', 'amapress' ),
				'show_column' => false,
				'show_on'     => 'edit-only',
			),
			'fin_raison'        => array(
				'name'           => __( 'Motif', 'amapress' ),
				'type'           => 'textarea',
				'group'          => __( '5/ Fin de contrat avant terme', 'amapress' ),
				'desc'           => __( 'Motif de départ (Déménagement, insatisfaction, ...)', 'amapress' ),
				'col_def_hidden' => true,
				'show_column'    => true,
				'show_on'        => 'edit-only',
			),
		),
	);

	return $entities;
}

function amapress_is_contrat_adhesion_readonly( $option ) {
	if ( TitanFrameworkOption::isOnNewScreen() ) {
		return false;
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

	return true;
}

function amapress_adhesion_contrat_quantite_editor( $post_id ) {
	$ret                   = '';
	$adh                   = AmapressAdhesion::getBy( $post_id );
	$date_debut            = $adh->getDate_debut() ? $adh->getDate_debut() : amapress_time();
	$adhesion_quantite_ids = $adh->getContrat_instance() ? $adh->getContrat_quantites_IDs() : array();
	$adhesion_quantites    = $adh->getContrat_quantites( null );
	$paniers_variables     = $adh->getPaniersVariables();
	$disabled              = amapress_is_contrat_adhesion_readonly( null ) ? 'disabled="disabled"' : '';
	$ret                   .= "<fieldset style='min-width: inherit' $disabled>";
	$contrats              = AmapressContrats::get_active_contrat_instances(
		$adh->getContrat_instance() ? $adh->getContrat_instance()->ID : null,
		$date_debut );
	$excluded_contrat_ids  = [];
	if ( TitanFrameworkOption::isOnNewScreen() && ! empty( $_GET['amapress_adhesion_adherent'] ) ) {
		foreach ( AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( intval( $_GET['amapress_adhesion_adherent'] ) ) as $user_adh ) {
			$excluded_contrat_ids[] = $user_adh->getContrat_instanceId();
		}
	}
	if ( ! empty( $_GET['amapress_adhesion_contrat_instance'] ) ) {
		$needed_contrat = AmapressContrat_instance::getBy( intval( $_GET['amapress_adhesion_contrat_instance'] ) );
		if ( $needed_contrat ) {
			$excluded_contrat_ids = [];
			$contrats             = [
				$needed_contrat
			];
		}
	}

	$had_contrat = false;
	foreach ( $contrats as $contrat_instance ) {
		if ( in_array( $contrat_instance->ID, $excluded_contrat_ids ) ) {
			continue;
		}
		$id = 'contrat-' . $contrat_instance->ID;
		if ( $contrat_instance->isPanierVariable() ) {
			if ( TitanFrameworkOption::isOnNewScreen() ) {
				$had_contrat = true;
				$ret         .= '';
				$ret         .= sprintf( __( '<b>%s</b><div><label for="%s"><input class="%s" id="%s" type="checkbox" name="%s[]" value="%s" data-excl="%s" data-contrat-date-debut="%s" data-contrat-date-fin="%s"/> Panier personnalisé</label></div>', 'amapress' ),
					esc_html( $contrat_instance->getTitle() ),
					$id,
					'multicheckReq exclusiveContrat contrat-quantite onlyOneInscription', //multicheckReq
					$id,
					'amapress_adhesion_contrat_vars',
					esc_attr( $contrat_instance->ID ),
					esc_attr( $contrat_instance->ID ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_debut() ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_fin() )
				);
				$ret         .= "<script type='text/javascript'>jQuery('#$id').change(function() {
    var this_option = jQuery(this);
  jQuery('#amapress_adhesion_date_debut').datepicker('option', {minDate:this_option.data('contrat-date-debut'), maxDate: this_option.data('contrat-date-fin')});
})</script>";
			} else {
				if ( ! $paniers_variables ) {
					$paniers_variables = array();
				}

				$columns = array(
					array(
						'title' => __( 'Produit', 'amapress' ),
						'data'  => 'produit',
					),
				);
				foreach ( $contrat_instance->getListe_dates() as $date ) {
					$columns[] = array(
						'title' => date_i18n( 'd/m/y', $date ),
						'data'  => 'd-' . $date,
					);
				}

				if ( ! TitanFrameworkOption::isOnNewScreen() && ! isset( $_REQUEST['full_edit'] ) ) {
					$ret .= '<input id="amapress_adhesion_adherent" name="amapress_adhesion_adherent" type="hidden" value="' . $adh->getAdherentId() . '" />';
				}

				$data = array();
				foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance->ID ) as $quant ) {
					$row     = array(
						'produit' => esc_html( $quant->getTitle() ),
					);
					$options = $quant->getQuantiteOptions();
					foreach ( $contrat_instance->getListe_dates() as $date ) {
						$val         = isset( $paniers_variables[ $date ][ $quant->ID ] ) ? $paniers_variables[ $date ][ $quant->ID ] : '';
						$is_empty    = empty( $val );
						$empty_class = $is_empty ? 'contrat_panier_vars-empty' : 'contrat_panier_vars-with-value';
						$ed          = '';
						$ed          .= "<select name='amapress_adhesion_contrat_panier_vars[$date][{$quant->ID}]' class='contrat_panier_vars-select $empty_class'>";
						$ed          .= tf_parse_select_options( $options, $val, false );
						$ed          .= '</select>';
						if ( ! $quant->isInDistributionDates( $date ) ) {
							$ed = '<span class="contrat_panier_vars-na">NA</span>';
						}
						$row[ 'd-' . $date ] = $ed;
					}
					$data[] = $row;
				}

//                $ret .= '<table class="display nowrap dataTable no-footer" width="100%" cellspacing="0" role="grid" style="margin-left: 0px; width: 9875px;"><thead><tr role="row"><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 60px;">Produit</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">29/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">31/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">31/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">30/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/09/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/09/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">30/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">29/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">17/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">24/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">31/08/17</th></tr></thead></table>';


				$had_contrat = true;
				$ret         .= amapress_get_datatable( 'quant-commandes', $columns, $data, array(
					'initComplete' => 'function() {
                    jQuery(".contrat_panier_vars-select").parent().click(
                        function() {
                            jQuery(this).find(".contrat_panier_vars-select").css(\'visibility\', \'visible\');
                        }
                    );
                    jQuery(".contrat_panier_vars-select.contrat_panier_vars-empty").css(\'visibility\', \'hidden\');
                    }',
					'bSort'        => false,
					'paging'       => false,
					'searching'    => true,
					'bAutoWidth'   => true,
					'responsive'   => false,
					'init_as_html' => true,
					'scrollX'      => true,
					'scrollY'      => 350,
					'fixedColumns' => array( 'leftColumns' => 2 ),
				) );
			}
		} else {
			$contrat_quants     = AmapressContrats::get_contrat_quantites( $contrat_instance->ID );
			$contrat_quants_ids = array_map( function ( $c ) {
				return $c->ID;
			}, $contrat_quants );
			if ( empty( $contrat_quants ) || count( $contrat_quants ) == 0 ) {
				continue;
			}
			if ( count( $adhesion_quantite_ids ) > 0 && count( array_intersect( $adhesion_quantite_ids, $contrat_quants_ids ) ) == 0 ) {
				continue;
			}

			$had_contrat = true;
			$ret         .= '<b>' . Amapress::makeLink( $contrat_instance->getAdminEditLink(), $contrat_instance->getTitle(), true, true ) . '</b>';
			$ret         .= '<div>';
			if ( ! TitanFrameworkOption::isOnNewScreen() && ! isset( $_REQUEST['full_edit'] ) ) {
				$ret .= '<input id="amapress_adhesion_adherent" name="amapress_adhesion_adherent" type="hidden" value="' . $adh->getAdherentId() . '" />';
			}

			foreach ( $contrat_quants as $quantite ) {
				if ( empty( $quantite ) ) {
					continue;
				}
				$id        = 'contrat-' . $contrat_instance->ID . '-quant-' . $quantite->ID;
				$id_factor = 'contrat-' . $contrat_instance->ID . '-quant-' . $quantite->ID . '-factor';

				$quant_var_editor = '';
				if ( $contrat_instance->isQuantiteVariable() ) {
//					$disabled         = disabled( in_array( $quantite->ID, $adhesion_quantite_ids ), false, false );
					$hidden           = ! in_array( $quantite->ID, $adhesion_quantite_ids ) ? ';display:none' : '';
					$quant_var_editor .= "<select id='$id_factor' name='amapress_adhesion_contrat_quants_factors[{$quantite->ID}]' style='display: inline-block;min-width: auto$hidden'>";

					$quant_var_editor .= tf_parse_select_options(
						$quantite->getQuantiteOptions(),
						! empty( $adhesion_quantites[ $quantite->ID ] ) ? $adhesion_quantites[ $quantite->ID ]->getFactor() : null,
						false );
					$quant_var_editor .= '</select>';
				}


				$type = $contrat_instance->isQuantiteMultiple() ? 'checkbox' : 'radio';
				if ( empty( $quantite->getContrat_instance() ) || empty( $contrat_instance ) ) {
					continue;
				}
				$ret .= sprintf( __( '<label for="%s" style="white-space: nowrap;"><input class="%s" id="%s" type="%s" name="%s[]" value="%s" %s data-excl="%s" data-contrat-date-debut="%s" data-contrat-date-fin="%s"/> %s %s </label> <br />', 'amapress' ),
					$id,
					'multicheckReq exclusiveContrat contrat-quantite' . ( TitanFrameworkOption::isOnNewScreen() ? ' onlyOneInscription' : '' ), //multicheckReq
					$id,
					$type,
					'amapress_adhesion_contrat_quants',
					esc_attr( $quantite->ID ),
					checked( in_array( $quantite->ID, $adhesion_quantite_ids ), true, false ),
					esc_attr( $quantite->getContrat_instance()->ID ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_debut() ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_fin() ),
					$quant_var_editor,
					esc_html( $quantite->getTitle() . ' ( ' . $quantite->getPrix_unitaireDisplay() . ' ' . $quantite->getPriceUnitDisplay() . ')' )
				);

				$ret .= "<script type='text/javascript'>jQuery('#$id').change(function() {
    var this_option = jQuery(this);
  jQuery('#amapress_adhesion_date_debut').datepicker('option', {minDate:this_option.data('contrat-date-debut'), maxDate: this_option.data('contrat-date-fin')});
  jQuery('#$id_factor').toggle(this_option.is(':checked'));
})</script>";
			}
			$ret .= '</div>';
		}
	}
	if ( ! $had_contrat ) {
		$ret .= '<p class="adhesion-date-error">' . sprintf( __( 'La date de début (%s) est en dehors des dates du contrat associé', 'amapress' ), esc_html( date_i18n( 'd/m/Y', $date_debut ) ) ) . '</p>';
	}

//	$ret .= '</fieldset>';

	return $ret;
}

add_action( 'wp_ajax_check_inscription_unique', function () {
	$contrats = $_POST['contrats'];
	$user_id  = $_POST['user'];
	$post_ID  = $_POST['post_ID'];
	$related  = isset( $_POST['related'] ) ? $_POST['related'] : 0;

	$contrats = array_unique( array_map( 'intval', explode( ',', $contrats ) ) );

	$user_id = intval( $user_id );
	$adhs    = array();
	foreach ( $contrats as $contrat ) {
		foreach ( AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, $contrat ) as $adh ) {
			if ( $adh->getID() == $post_ID || $adh->getID() == $related ) {
				continue;
			}

			$adhs[] = $adh;
		}
	}
	if ( empty( $adhs ) ) {
		echo wp_json_encode( true );
	} else {
		echo wp_json_encode( __( 'L\'amapien possède déjà un contrat de ce type', 'amapress' ) );
	}

	wp_die();
} );

function amapress_save_adhesion_contrat_quantite_editor( $adhesion_id ) {
	if ( ! empty( $_REQUEST['amapress_adhesion_contrat_vars'] ) ) {
		update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_instance', intval( $_REQUEST['amapress_adhesion_contrat_vars'][0] ) );
	} else if ( isset( $_REQUEST['amapress_adhesion_contrat_panier_vars'] ) ) {
		update_post_meta( $adhesion_id, 'amapress_adhesion_panier_variables', $_REQUEST['amapress_adhesion_contrat_panier_vars'] );
	} else if ( isset( $_REQUEST['amapress_adhesion_contrat_quants'] ) ) {
		$quants = array_map( 'intval', $_REQUEST['amapress_adhesion_contrat_quants'] );
		if ( ! empty( $quants ) ) {
			$first_quant = AmapressContrat_quantite::getBy( $quants[0] );
			update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_instance', $first_quant->getContrat_instance()->ID );
			update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_quantite', $quants );

			if ( isset( $_REQUEST['amapress_adhesion_contrat_quants_factors'] ) ) {
				$factors = array_map( 'floatval', $_REQUEST['amapress_adhesion_contrat_quants_factors'] );
				update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_quantite_factors', $factors );
			}
		}
	}
}

//add_filter('amapress_can_delete_contrat_adhesion', 'amapress_can_delete_contrat_adhesion', 10, 2);
//function amapress_can_delete_contrat_adhesion($can, $post_id) {
//    return false;
//}

add_action( 'amapress_row_action_adhesion_renew', 'amapress_row_action_adhesion_renew' );
function amapress_row_action_adhesion_renew( $post_id ) {
	$adhesion     = AmapressAdhesion::getBy( $post_id );
	$new_adhesion = $adhesion->cloneAdhesion();
	if ( ! $new_adhesion ) {
		wp_die( __( 'Une erreur s\'est produit lors du renouvellement de l\'adhésion. Veuillez réessayer', 'amapress' ) );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_adhesion->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_adhesion_generate_contrat', 'amapress_row_action_adhesion_generate_contrat' );
function amapress_row_action_adhesion_generate_contrat( $post_id ) {
	$adhesion       = AmapressAdhesion::getBy( $post_id );
	$full_file_name = $adhesion->generateContratDoc( true );
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'amapress_row_action_adhesion_generate_contrat_pdf', 'amapress_row_action_adhesion_generate_contrat_pdf' );
function amapress_row_action_adhesion_generate_contrat_pdf( $post_id ) {
	$adhesion       = AmapressAdhesion::getBy( $post_id );
	$full_file_name = $adhesion->generateContratDoc( false );
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'amapress_row_action_adhesion_send_confirmation', 'amapress_row_action_adhesion_send_confirmation' );
function amapress_row_action_adhesion_send_confirmation( $post_id ) {
	$adhesion = AmapressAdhesion::getBy( $post_id );
	$adhesion->sendConfirmationMail();
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_close', 'amapress_row_action_adhesion_close' );
function amapress_row_action_adhesion_close( $post_id ) {
	$adhesion = AmapressAdhesion::getBy( $post_id );
	$adhesion->markClosed();
	wp_redirect_and_exit( wp_get_referer() );
}

add_filter( 'amapress_row_actions_adhesion', 'amapress_row_actions_adhesion', 10, 2 );
function amapress_row_actions_adhesion( $actions, $adhesion_id ) {
	$adh = AmapressAdhesion::getBy( $adhesion_id );

//    $contrat_instance_id = $adh->getContrat_instanceId();
//    $contrat_instances_ids = AmapressContrats::get_active_contrat_instances_ids_by_contrat($adh->getContrat_instance()->getModel()->ID,
//        null, true);
//    $contrat_instances_ids = array_filter(
//        $contrat_instances_ids,
//        function($id) use ($contrat_instance_id) {
//            return $id != $contrat_instance_id;
//        }
//    );

	if ( ! $adh->canRenew() ) {
		unset( $actions['renew'] );
		unset( $actions['no_renew'] );
	}

	return $actions;
}

//function amapress_echo_all_contrat_quantite() {
//	$ret    = '';
//	$ret    .= '<div><ul class="nav nav-tabs" role="tablist">';
//	$active = 'active';
//	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat_instance ) {
//		$ret    .= '<li role="presentation" class="'.$active.'"><a href="#contrat-instance-'.$contrat_instance->ID.'" aria-controls="'.$contrat_instance->ID . '" role="tab" data-toggle="tab">'.esc_html($contrat_instance->getTitle()).'</a></li>';
//		$active = '';
//	}
//	$ret .= '</ul>';
//
//	$ret    .= '<div class="tab-content">';
//	$active = 'active';
//	foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
//		$ret    .= '<div role = "tabpanel" class="tab-pane '.$active.'" id="contrat-instance-' . $contrat_instance->ID . '" >'.
//		           amapress_get_contrat_quantite_datatable( $contrat_instances_id ) .'</div >';
//		$active = '';
//	}
//	$ret .= '</div >';
//
//	return $ret;
//}
function amapress_get_contrat_column_quantite_datatables(
	$contrat_instance_id,
	$date = 'first'
) {
	$data_by_date = amapress_get_contrat_column_quantite( $contrat_instance_id, $date, false );
	$ret          = '';
	foreach ( $data_by_date as $date => $data ) {
		$ret .= '<h4>' . esc_html( $date ) . '</h4>';
		$ret .= amapress_get_datatable( uniqid( 'amps-qc-' ), $data['columns'], $data['data'] );
	}

	return $ret;
}

function amapress_get_contrat_column_quantite(
	$contrat_instance_id,
	$date = 'first',
	$return_xl = true
) {
	$contrat                    = AmapressContrat_instance::getBy( $contrat_instance_id );
	$contrat_instance_quantites = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
	$data                       = amapress_get_contrat_quantite_datatable(
		$contrat->ID, null,
		$date, [
		'show_price'     => true,
		'show_adherents' => true,
		'show_all_dates' => 'first' == $date,
		'group_by'       => 'date',
		'group_by_group' => false,
		'mode'           => 'xlsx',
	] );


	$data_by_date = array_group_by( $data['data'], function ( $row ) {
		return $row['date_sort'];
	} );

	foreach ( $data_by_date as $date_iso => $date_data ) {
		$date_i       = strtotime( $date_iso );
		$date         = date_i18n( 'd/m/Y', $date_i );
		$date_display = date_i18n( 'l d F Y', $date_i );
		$out_data     = [];
		$out_columns  = [];
		$quants       = [];
		$quants_ids   = [];

		foreach ( $date_data as $row ) {
			$quants_ids[] = $row['quant_id'];
		}
		$quants_ids = array_unique( $quants_ids );
		foreach ( $contrat_instance_quantites as $contrat_instance_quantite ) {
			if ( in_array( $contrat_instance_quantite->ID, $quants_ids ) ) {
				$quants[ strval( $contrat_instance_quantite->ID ) ] = $contrat_instance_quantite->getTitle();
			}
		}

		$out_columns[] = array(
			'title' => __( 'Adhérent', 'amapress' ),
			'data'  => 'adherent',
		);
		$out_columns[] = array(
			'title' => __( 'Téléphone', 'amapress' ),
			'data'  => 'adherent_tel'
		);
		$out_columns[] = array(
			'title' => __( 'Emargement', 'amapress' ),
			'data'  => 'emargement'
		);
		foreach ( $quants as $k => $v ) {
			$out_columns[] = array(
				'title' => $v,
				'data'  => "q_$k",
			);
		}
		$out_columns[] = array(
			'title' => __( 'Montant total', 'amapress' ),
			'data'  => 'total',
		);

		foreach (
			array_group_by( array_values( $date_data ), function ( $row ) {
				return $row['adherent'];
			} ) as $adherent => $adherent_datas
		) {
			if ( empty( $out_data[ $adherent ] ) ) {
				$out_data[ $adherent ] = [
					'total'      => 0,
					'adherent'   => $adherent,
					'emargement' => '',
				];
			}

			foreach ( $quants_ids as $q_id ) {
				$out_data[ $adherent ]["q_$q_id"] = 0;
			}
			foreach ( $adherent_datas as $adherent_data ) {
				$out_data[ $adherent ]['adherent_tel'] = $adherent_data['adherent_tel'];
				$quant_id                              = $adherent_data['quant_id'];
				$all_num                               = $adherent_data['all_num'];
				$price                                 = $adherent_data['price'];
				if ( ! empty( $out_data[ $adherent ]["q_$quant_id"] ) ) {
					$out_data[ $adherent ]["q_$quant_id"] += $all_num;
				} else {
					$out_data[ $adherent ]["q_$quant_id"] = $all_num;
				}
				$out_data[ $adherent ]['total'] += $price;
			}
		}

		unset( $data_by_date[ $date_iso ] );
		$data_by_date[ $date ] = [
			'display' => $date_display,
			'data'    => array_values( $out_data ),
			'columns' => $out_columns
		];
	}

	if ( ! $return_xl ) {
		return $data_by_date;
	}

	$filename = strtolower( sanitize_file_name( "quantites-{$contrat->getModelTitle()}-colonnes" ) );
	$title    = "{$contrat->getModelTitle()} - Produits en colonnes";
	if ( strlen( $title ) > 27 ) {
		$title = substr( $title, 0, 27 ) . '...';
	}

	$objPHPExcel = new PHPExcel();
	$objPHPExcel->getProperties()->setCreator( "Amapress" )
	            ->setLastModifiedBy( "Amapress" )
	            ->setTitle( ! empty( $title ) ? $title : $filename );
	$ix = 0;
	foreach ( $data_by_date as $date => $data ) {
		$csv_data = [];
		$row_data = [];
		foreach ( $data['columns'] as $header ) {
			$row_data[] = strip_tags( $header['title'] );
		}
		$csv_data[] = $row_data;

		foreach ( $data['data'] as $row ) {
			$row_data = [];
			foreach ( $data['columns'] as $header ) {
				$row_data[] = strip_tags( ! empty( $row[ $header['data'] ] ) ? $row[ $header['data'] ] : '' );
			}
			$csv_data[] = $row_data;
		}

		if ( $ix > 0 ) {
			$objPHPExcel->createSheet();
		}
		$objPHPExcel->setActiveSheetIndex( $ix )->fromArray( $csv_data );
		$sheet = $objPHPExcel->getActiveSheet();
		$style = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			)
		);
		$sheet->getColumnDimensionByColumn( 0 )->setAutoSize( true );
		$sheet->getColumnDimensionByColumn( 1 )->setAutoSize( true );
		$sheet->getColumnDimensionByColumn( 2 )->setAutoSize( true );
		$sheet->getColumnDimensionByColumn( count( $out_columns ) - 1 )->setAutoSize( true );
		$sheet->getDefaultStyle()->applyFromArray( $style )->getAlignment()
		      ->setWrapText( true );
		$sheet->getStyleByColumnAndRow(
			0, 1,
			count( $out_columns ) - 1,
			$sheet->getHighestRow()
		)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
		for ( $i = 2; $i <= $sheet->getHighestRow(); $i ++ ) {
			if ( $i % 2 == 0 ) {
				$sheet->getStyleByColumnAndRow( 0, $i, count( $out_columns ) - 1, $i )->applyFromArray(
					array(
						'fill' => array(
							'type'  => \PHPExcel_Style_Fill::FILL_SOLID,
							'color' => array( 'argb' => 'FFF3F3F3' )
						),
					)
				);
			}
		}
		$sheet->insertNewRowBefore()->mergeCellsByColumnAndRow( 0, 1, count( $out_columns ) - 1, 1 );
		$sheet->setCellValueByColumnAndRow( 0, 1, $data['display'] );
		$sheet->setTitle( str_replace( '/', '-', $date ) );
		$ix += 1;
	}
	$objPHPExcel->setActiveSheetIndex( 0 );

	return [ 'xl' => $objPHPExcel, 'filename' => $filename . '.xlsx' ];
}


function amapress_get_contrat_quantite_xlsx(
	$contrat_instance_id,
	$type,
	$date = 'first'
) {
	$contrat        = AmapressContrat_instance::getBy( $contrat_instance_id );
	$group_by       = 'date';
	$show_adherents = false;
	$group_by_group = false;
	$by_date        = __( 'par-date', 'amapress' );
	$by_date_title  = __( 'par date', 'amapress' );
	if ( 'first' != $date ) {
		$by_date       = date_i18n( 'Y-m-d', $date );
		$by_date_title = date_i18n( 'd-m-Y', $date );
	}
	switch ( $type ) {
		case 'quant_column_adherents_by_date':
			$show_adherents = true;
			break;
		case 'adherents_group_date':
			$show_adherents  = true;
			$type_title_file = sprintf( __( 'avec-adherents-%s-groupe', 'amapress' ), $by_date );
			$type_title      = sprintf( __( 'Avec adherents %s-groupe', 'amapress' ), $by_date_title );
			$group_by_group  = true;
			break;
		case 'adherents_date':
			$show_adherents  = true;
			$type_title_file = sprintf( __( 'avec-adherents-%s', 'amapress' ), $by_date );
			$type_title      = sprintf( __( 'Avec adherents %s', 'amapress' ), $by_date_title );
			break;
		case 'adherents_month':
			$group_by        = 'month';
			$show_adherents  = true;
			$type_title_file = __( 'avec-adherents-par-mois', 'amapress' );
			$type_title      = __( 'Avec adhérents par mois', 'amapress' );
			break;
		case 'adherents_quarter':
			$group_by        = 'quarter';
			$show_adherents  = true;
			$type_title_file = __( 'avec-adherents-par-trimestre', 'amapress' );
			$type_title      = __( 'Avec adhérents par trimestre', 'amapress' );
			break;
		case 'month':
			$group_by        = 'month';
			$type_title_file = __( 'par-mois', 'amapress' );
			$type_title      = __( 'Par mois', 'amapress' );
			break;
		case 'quarter':
			$group_by        = 'quarter';
			$type_title_file = __( 'par-trimestre', 'amapress' );
			$type_title      = __( 'Par trimestre', 'amapress' );
			break;
		case 'group_date':
			$type_title_file = $by_date;
			$type_title      = $by_date_title;
			$group_by_group  = true;
			break;
		default:
			$type_title_file = $by_date;
			$type_title      = $by_date_title;
			break;
	}
	$data = amapress_get_contrat_quantite_datatable(
		$contrat->ID, null,
		$date, [
		'show_price'     => $contrat->isPanierVariable(),
		'show_adherents' => $show_adherents,
		'show_all_dates' => 'first' == $date,
		'group_by'       => $group_by,
		'group_by_group' => $group_by_group,
		'mode'           => 'xlsx',
	] );

	$filename = strtolower( sanitize_file_name( "quantites-{$contrat->ID}-{$contrat->getModelTitle()}-{$type_title_file}" ) );
	$title    = "{$contrat->getModelTitle()} - $type_title";
	if ( strlen( $title ) > 27 ) {
		$title = substr( $title, 0, 27 ) . '...';
	}

	return [
		'data'     => $data['data'],
		'columns'  => $data['columns'],
		'filename' => $filename . '.xlsx',
		'title'    => $title
	];
}

function amapress_get_contrat_quantite_datatable(
	$contrat_instance_id,
	$lieu_id = null,
	$date = null,
	$options = array()
) {
	$contrat_instance           = AmapressContrat_instance::getBy( $contrat_instance_id );
	$contrat_instance_quantites = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
	$has_groups                 = $contrat_instance->hasGroups();

	$root_url = admin_url( 'admin.php?page=contrats_quantites_next_distrib&tab=contrat-quant-tab-' . $contrat_instance_id );

	$date_is_first = false;
	if ( 'first' == $date ) {
		$date          = Amapress::start_of_week( $contrat_instance->getDate_debut() );
		$date_is_first = true;
	}
	$date_is_current = false;
	if ( ! $date ) {
		$next_distrib = AmapressDistribution::getNextDistribution( $lieu_id, $contrat_instance_id );
		if ( $next_distrib ) {
			$date = $next_distrib->getDate();
		} else {
			$date = amapress_time();
		}
		$date_is_current = true;
	}

	/** @var AmapressDistribution $dist */
	$next_distribs      = AmapressDistribution::get_next_distributions( $date, 'ASC' );
	$dist               = null;
	$next_next_dist     = null;
	$next_next_distribs = [];
	foreach ( $next_distribs as $distrib ) {
		if ( in_array( $contrat_instance_id, $distrib->getContratIds() ) && ( empty( $lieu_id ) || $distrib->getLieuId() == $lieu_id ) ) {
			if ( ! $dist ) {
				$dist = $distrib;
			} elseif ( ! $next_next_dist && $dist ) {
				$next_next_dist = $distrib;
			} elseif ( $next_next_dist ) {
				$next_next_distribs[] = $distrib;
			}
		}
	}
	if ( $dist ) {
		$date = $dist->getDate();
	}

	$options = wp_parse_args(
		$options,
		array(
			'show_next_distrib'       => true,
			'show_contact_producteur' => true,
			'show_adherents'          => $contrat_instance->isPanierVariable(),
			'show_adherents_count'    => true,
			'show_all_dates'          => false,
			'group_by'                => 'none',
			'show_price'              => false,
			'group_by_group'          => false,
			'show_empty_lines'        => ! $contrat_instance->isPanierVariable(),
			'show_sum_fact_details'   => true,
			'show_fact_details'       => $contrat_instance->isQuantiteVariable(),
			'show_equiv_quantite'     => from( $contrat_instance_quantites )->distinct( function ( $c ) {
					/** @var AmapressContrat_quantite $c */
					return $c->getQuantite();
				} )->count() > 1,
			'no_script'               => false,
			'mode'                    => 'both',
			'for_placeholder'         => false,
		)
	);

	$for_placeholder       = Amapress::toBool( $options['for_placeholder'] );
	$group_by              = $options['group_by'];
	$show_empty_lines      = $options['show_empty_lines'];
	$show_adherents        = $options['show_adherents'];
	$show_adherents_count  = $options['show_adherents_count'];
	$show_equiv_quantite   = $options['show_equiv_quantite'];
	$show_fact_details     = $options['show_fact_details'];
	$show_sum_fact_details = $options['show_sum_fact_details'];
	$show_all_dates        = $options['show_all_dates'];
	$group_by_group        = Amapress::toBool( $options['group_by_group'] ) && $has_groups;
	$show_price            = $options['show_price'];

	if ( $show_adherents ) {
		$show_adherents_count = false;
		$show_empty_lines     = false;
	}

	$columns = [];
	if ( $show_all_dates ) {
		$columns[] = array(
			'title' => __( 'Date', 'amapress' ),
			'data'  => array(
				'_'    => 'date',
				'sort' => 'date_sort',
			)
		);
	}

	if ( $show_adherents ) {
		$columns[] = array(
			'title' => __( 'Adhérent', 'amapress' ),
			'data'  => array(
				'_'    => 'adherent',
				'sort' => 'adherent',
			)
		);
	}

	if ( $has_groups ) {
		$columns[] = array(
			'title' => __( 'Groupe', 'amapress' ),
			'data'  => array(
				'_'    => 'group',
				'sort' => 'group',
			)
		);
	}

	$columns[] = array(
		'title' => __( 'Description', 'amapress' ),
		'data'  => array(
			'_'    => 'quant',
			'sort' => 'quant',
		)
	);
	$lieux     = Amapress::get_lieux();
	if ( ! $contrat_instance->isPanierVariable() ) {
		if ( count( $lieux ) > 1 ) {
			foreach ( $lieux as $lieu ) {
				$columns[] = array(
					'title' => $lieu->getShortName(),
					'data'  => array(
						'_'    => "lieu_{$lieu->ID}",
						'sort' => "lieu_{$lieu->ID}",
					)
				);
			}
			$columns[] = array(
				'title' => __( 'Tous', 'amapress' ),
				'data'  => array(
					'_'    => 'all',
					'sort' => 'all',
				)
			);
		} else {
			foreach ( $lieux as $lieu ) {
				$columns[] = array(
					'title' => $lieu->getTitle(),
					'data'  => array(
						'_'    => "all",
						'sort' => "all",
					)
				);
			}
		}
	}
	if ( $show_adherents_count ) {
		$columns[] = array(
			'title' => __( 'Adhérents', 'amapress' ),
			'data'  => array(
				'_'    => "all_adhs",
				'sort' => "all_adhs",
			)
		);
	}
	$columns[] = array(
		'title' => __( 'Quantité', 'amapress' ),
		'data'  => array(
			'_'    => "all_num",
			'sort' => "all_num",
		)
	);

	if ( $show_price ) {
		$columns[] = array(
			'title' => __( 'Total', 'amapress' ),
			'data'  => array(
				'_'    => 'price_d',
				'sort' => 'price',
			)
		);
	}

	$overall_total_price = 0;
	if ( 'xlsx' !== $options['mode'] ) {
		$contrat_instance_quantites[] = null;
	}
	$data         = array();
	$sum_data     = array();
	$all_distribs = [ $dist ];
	if ( $show_all_dates ) {
		$all_distribs = [ $dist ];
		if ( $next_next_dist ) {
			$all_distribs[] = $next_next_dist;
		}
		$all_distribs = array_merge( $all_distribs, $next_next_distribs );
	}
	foreach ( $all_distribs as $d ) {
		$all_adhesions = AmapressContrats::get_active_adhesions( $contrat_instance_id, null, $lieu_id, $d ? $d->getDate() : $date, true, false );
		if ( $show_adherents ) {
			usort( $all_adhesions, function ( $a, $b ) {
				/** @var AmapressAdhesion $a */
				/** @var AmapressAdhesion $b */
				return strcmp( $a->getAdherent()->getSortableDisplayName(), $b->getAdherent()->getSortableDisplayName() );
			} );
		}
		$real_date = $d ? $d->getRealDateForContrat( $contrat_instance_id ) : $date;
		/** @var AmapressAdhesion[] $adhesions */
		foreach (
			array_merge( $show_adherents && count( $all_adhesions ) > 1 ? array_map( function ( $adh ) {
				return [ $adh ];
			}, $all_adhesions ) : [], [ $all_adhesions ] ) as $adhesions
		) {
			$qidx = 0;
			foreach ( $contrat_instance_quantites as $quant ) {
				$qidx += 1;
				if ( $contrat_instance->isPanierVariable() && empty( $quant ) && ( ! $show_adherents_count || $show_adherents ) ) {
					continue;
				}
				/** @var AmapressContrat_quantite $quant */
				$row = array();
				if ( $show_adherents ) {
					if ( isset( $adhesions[0] ) ) {
						/** @var AmapressAdhesion $adhesion */
						$adhesion            = $adhesions[0];
						$row['adherent']     = $adhesion->getAdherent()->getSortableDisplayName();
						$row['adherent_tel'] = implode( '/', $adhesion->getAdherent()->getPhoneNumbers( true ) );
					} else {
						$row['adherent']     = '';
						$row['adherent_tel'] = '';
					}
				}
				$row['date']      = date_i18n( 'd/m/Y', $real_date );
				$row['date_sort'] = date( 'Y-m-d', $real_date );
				$quant_title      = $quant ? $quant->getTitle() : '-toutes-';
				$row['quant']     = $quant ? ( $has_groups ? $quant->getTitleWithoutGroup() : $quant->getTitle() ) : '¤-Toutes-¤';
				$row['group']     = $quant ? $quant->getGroupName() : '--';
				$row['quant_id']  = $quant ? $quant->ID : 0;
				$row['qid']       = $quant ? str_pad( $qidx, 8, '0', STR_PAD_LEFT ) : '99999999';
				$quand_id         = $quant ? $quant->getID() : 0;
				if ( count( $lieux ) > 1 ) {
					foreach ( $lieux as $lieu ) {
						$lieu_quant_adh_count      = 0;
						$lieu_quant_count          = 0;
						$lieu_quant_sum            = 0;
						$lieu_quant_fact_adh_count = [];
						foreach ( $adhesions as $adh ) {
							if ( $adh->getLieuId() != $lieu->ID ) {
								continue;
							}
							if ( empty( $quand_id ) ) {
								$lieu_quant_adh_count += 1;
							}
							foreach ( $adh->getContrat_quantites( $real_date ) as $adh_quant ) {
								if ( ! empty( $quand_id ) && $adh_quant->getId() != $quand_id ) {
									continue;
								}

								if ( ! empty( $quand_id ) ) {
									$lieu_quant_adh_count += 1;
								}
								$lieu_quant_count += $adh_quant->getFactor();
								$lieu_quant_sum   += $adh_quant->getQuantite();

								if ( ! empty( $quand_id ) ) {
									$quant_key = trim( $quant->formatValue( $adh_quant->getFactor(), '' ) . ' "' . $quant->getCode() ) . '"';
									if ( empty( $lieu_quant_fact_adh_count[ $quant_key ] ) ) {
										$lieu_quant_fact_adh_count[ $quant_key ] = 0;
									}

									$lieu_quant_fact_adh_count[ $quant_key ] += 1;
								}
							}
						}
						ksort( $lieu_quant_fact_adh_count );
						$fact_details = implode( "<br/>", array_map(
							function ( $k, $v ) {
								return "$v x $k";
							},
							array_keys( $lieu_quant_fact_adh_count ),
							array_values( $lieu_quant_fact_adh_count )
						) );

						$row["lieu_{$lieu->ID}_adhs"] = $lieu_quant_adh_count;
						$row["lieu_{$lieu->ID}_num"]  = $lieu_quant_count;
						if ( empty( $lieu_quant_adh_count ) ) {
							$row["lieu_{$lieu->ID}"]     = '';
							$row["lieu_{$lieu->ID}_txt"] = '';
						} else if ( abs( $lieu_quant_sum ) < 0.001 || ! $show_equiv_quantite ) {
							$row["lieu_{$lieu->ID}"]     = ( $show_adherents_count ?
									"$lieu_quant_adh_count adhérents ; " : '' ) .
							                               ( $show_fact_details ?
								                               "<br/>$fact_details" . ( $show_sum_fact_details && count( $lieu_quant_fact_adh_count ) > 1 ? "<br/>= $lieu_quant_count x $quant_title" : '' )
								                               : "$lieu_quant_count x $quant_title" );
							$row["lieu_{$lieu->ID}_txt"] = ( $show_fact_details ?
								"<br/>$fact_details" . ( $show_sum_fact_details && count( $lieu_quant_fact_adh_count ) > 1 ? "<br/>= $lieu_quant_count x $quant_title" : '' )
								: "$lieu_quant_count x $quant_title" );
						} else {
							$row["lieu_{$lieu->ID}"]     = ( $show_adherents_count ?
									"$lieu_quant_adh_count adhérents ; " : '' ) .
							                               ( $show_fact_details ?
								                               "<br/>$fact_details" . ( $show_sum_fact_details && count( $lieu_quant_fact_adh_count ) > 1 ? "<br/>= $lieu_quant_count x $quant_title" : '' )
								                               : "$lieu_quant_count x $quant_title" ) .
							                               "<br/><em>équivalent quantité : $lieu_quant_sum</em>";
							$row["lieu_{$lieu->ID}_txt"] = ( $show_fact_details ?
								"<br/>$fact_details" . ( $show_sum_fact_details && count( $lieu_quant_fact_adh_count ) > 1 ? "<br/>= $lieu_quant_count x $quant_title" : '' )
								: "$lieu_quant_count x $quant_title" );
						}
					}
				}
				$all_quant_adh_count      = 0;
				$all_quant_count          = 0;
				$all_quant_sum            = 0;
				$all_quant_all_price      = 0;
				$all_quant_fact_adh_count = [];
				foreach ( $adhesions as $adh ) {
					$quants_for_date = $adh->getContrat_quantites( $real_date );
					if ( empty( $quand_id ) && ! empty( $quants_for_date ) ) {
						$all_quant_adh_count += 1;
					}
					foreach ( $quants_for_date as $adh_quant ) {
						if ( ! empty( $quand_id ) && $adh_quant->getId() != $quand_id ) {
							continue;
						}

						if ( ! empty( $quand_id ) ) {
							$all_quant_adh_count += 1;
						}
						$all_quant_count += $adh_quant->getFactor();
						$all_quant_sum   += $adh_quant->getQuantite();

						$all_quant_all_price += $adh_quant->getPrice();

						if ( ! empty( $quand_id ) ) {
							$quant_key = trim( $quant->formatValue( $adh_quant->getFactor(), '' ) ) . ' "' . $quant->getCode() . '"';
							if ( empty( $all_quant_fact_adh_count[ $quant_key ] ) ) {
								$all_quant_fact_adh_count[ $quant_key ] = 0;
							}
							$all_quant_fact_adh_count[ $quant_key ] += 1;
						}
					}
				}
				ksort( $all_quant_fact_adh_count );
				$fact_details = implode( "<br/>", array_map(
					function ( $k, $v ) {
						return "$v x $k";
					},
					array_keys( $all_quant_fact_adh_count ),
					array_values( $all_quant_fact_adh_count )
				) );
				$total_price  = $all_quant_all_price;

				$row['price_d'] = Amapress::formatPrice( $total_price, true );
				$row['price']   = $total_price;
				if ( $quant && ( ! $show_adherents || 1 == count( $adhesions ) ) ) {
					$overall_total_price += $total_price;
				}

				$row['all_adhs'] = $all_quant_adh_count;
				$row['all_num']  = $all_quant_count;
				if ( empty( $all_quant_adh_count ) ) {
					$row['all']     = '';
					$row['all_txt'] = '';
				} else if ( abs( $all_quant_sum ) < 0.001 || ! $show_equiv_quantite ) {
					$row['all']     = ( $show_adherents_count ? "$all_quant_adh_count adhérents ; " : '' ) .
					                  ( $show_fact_details ?
						                  "<br/>$fact_details" . ( $show_sum_fact_details && count( $all_quant_fact_adh_count ) > 1 ? "<br/>= <strong>$all_quant_count</strong> x $quant_title" : '' )
						                  : "<strong>$all_quant_count</strong> x $quant_title" );
					$row['all_txt'] = ( $show_fact_details ?
						"<br/>$fact_details" . ( $show_sum_fact_details && count( $all_quant_fact_adh_count ) > 1 ? "<br/>= <strong>$all_quant_count</strong> x $quant_title" : '' )
						: "<strong>$all_quant_count</strong> x $quant_title" );
				} else {
					$row['all']     = ( $show_adherents_count ? "$all_quant_adh_count adhérents ; " : '' ) .
					                  ( $show_fact_details ?
						                  "<br/>$fact_details" . ( $show_sum_fact_details && count( $all_quant_fact_adh_count ) > 1 ? "<br/>= <strong>$all_quant_count</strong> x $quant_title" : '' )
						                  : "<strong>$all_quant_count</strong> x $quant_title" ) . "<br/><em>" . __( 'équivalent quantité : ', 'amapress' ) . "$all_quant_sum</em>";
					$row['all_txt'] = ( $show_fact_details ?
						"<br/>$fact_details" . ( $show_sum_fact_details && count( $all_quant_fact_adh_count ) > 1 ? "<br/>= <strong>$all_quant_count</strong> x $quant_title" : '' )
						: "<strong>$all_quant_count</strong> x $quant_title" );
				}
				if ( ! $show_empty_lines && 0 == $all_quant_adh_count ) {
					continue;
				}
				if ( $group_by_group || 'month' == $group_by || 'quarter' == $group_by ) {
					if ( $show_adherents ) {
						if ( count( $adhesions ) > 1 ) {
							$sum_data[] = $row;
							continue;
						} elseif ( 1 == count( $all_adhesions ) ) {
							$sum_data[] = $row;
						}
					}

					if ( 'quarter' == $group_by ) {
						$quarter          = ceil( intval( date( 'n', $real_date ) ) / 3 );
						$row['date']      = "T$quarter";
						$row['date_sort'] = "T$quarter";
					} elseif ( 'month' == $group_by ) {
						$row['date']      = date_i18n( 'm/Y', $real_date );
						$row['date_sort'] = date( 'Y-m', $real_date );
					}
					$key = $row['date_sort'] . ( $group_by_group ? $row['group'] : $row['quant'] ) . ( $show_adherents ? $row['adherent'] : '' );
					if ( $quant ) {
						$row['all_mult'] = $quant->getGroupMultiple();
					}
					if ( isset( $data[ $key ] ) ) {
						foreach ( $row as $k => $v ) {
							if ( $group_by_group ) {
								if ( 'quant' == $k ) {
									$data[ $key ][ $k ] .= '<br/> + <strong>' . $row['all_num'] . '</strong> x ' . $v;
									continue;
								}
							}
							if ( 'quant' == $k || 'quant_id' == $k || 'date' == $k || 'date_sort' == $k || 'adherent_tel' == $k || 'adherent' == $k || 'group' == $k || 'all_mult' == $k ) {
								continue;
							}
							if ( ! is_numeric( $v ) ) {
								if ( ! empty( $data[ $key ][ $k ] ) && ! empty( $v ) ) {
									$data[ $key ][ $k ] .= ' + ' . $v;
								} else {
									$data[ $key ][ $k ] .= $v;
								}
							} else {
								$data[ $key ][ $k ] += $v;
							}
						}
						$data[ $key ]['price_d'] = Amapress::formatPrice( $data[ $key ]['price'], true );
					} else {
						if ( $group_by_group ) {
							$row['quant'] = '<strong>' . $row['all_num'] . '</strong> x ' . $row['quant'];
						}
						$data[ $key ] = $row;
					}
				} elseif ( $show_adherents ) {
					if ( count( $adhesions ) > 1 ) {
						$sum_data[] = $row;
					} elseif ( 1 == count( $all_adhesions ) ) {
						$sum_data[] = $row;
						$data[]     = $row;
					} else {
						$data[] = $row;
					}
				} else {
					$data[] = $row;
				}
			}
		}
	}
	if ( $group_by_group ) {
		foreach ( $data as $key => $row ) {
			if ( ! empty( $data[ $key ]['all_mult'] ) ) {
				$data[ $key ]['all_num'] = $data[ $key ]['all_num'] / $data[ $key ]['all_mult'];
			}
		}
	}
	$data = array_values( $data );
	usort( $data, function ( $a, $b ) use ( $show_adherents ) {
		$key_a = $a['date_sort'] . '-' . ( $show_adherents ? $a['adherent'] : '' ) . '-' . $a['qid'];
		$key_b = $b['date_sort'] . '-' . ( $show_adherents ? $b['adherent'] : '' ) . '-' . $b['qid'];

		return strcasecmp( $key_a, $key_b );
	} );

	$next_distrib_text = '';
	if ( ! $for_placeholder ) {
		$next_distrib_text .= '<p>' . Amapress::wrapIf( Amapress::makeLink(
				$root_url . '&all&date=first',
				__( 'Récapitulatif contrat ', 'amapress' ) . $contrat_instance->getTitle() ), $show_all_dates && $date_is_first ) . ' | ' .
		                      Amapress::wrapIf( Amapress::makeLink(
			                      $root_url . '&all',
			                      __( 'Quantités par date à partir du ', 'amapress' ) . date_i18n( 'd/m/Y' ) ), $show_all_dates && ! $date_is_first ) . ' | ' .
		                      Amapress::wrapIf( Amapress::makeLink(
			                      $root_url,
			                      __( 'Quantités à la prochaine distribution à partir du ', 'amapress' ) . date_i18n( 'd/m/Y' ) ), ! $show_all_dates && $date_is_current ) .
		                      '</p><hr/>';
		$next_distrib_text .= '<p>' .
		                      ( $show_price ? Amapress::makeLink( remove_query_arg( 'with_prices' ), __( 'Masquer les montants', 'amapress' ) )
			                      : Amapress::makeLink( add_query_arg( 'with_prices', 'T' ), __( 'Afficher les montants', 'amapress' ) ) ) .
		                      ' | ' .
		                      ( $show_adherents ? Amapress::makeLink( remove_query_arg( 'with_adherent' ), __( 'Masquer les amapiens', 'amapress' ) )
			                      : Amapress::makeLink( add_query_arg( 'with_adherent', 'T' ), __( 'Afficher les amapiens', 'amapress' ) ) ) .
		                      ( $has_groups ?
			                      ( ' | ' .
			                        ( $group_by_group ? Amapress::makeLink( add_query_arg( 'grp_by_grp', 'F' ), __( 'Ne pas grouper les produits', 'amapress' ) )
				                        : Amapress::makeLink( remove_query_arg( 'grp_by_grp' ), __( 'Grouper les produits', 'amapress' ) ) ) )
			                      : '' ) .
		                      ( ! $show_all_dates && ! $show_adherents ? ' | ' .
		                                                                 ( $show_empty_lines ? Amapress::makeLink( add_query_arg( 'without_empty', 'T' ), __( 'Masquer les lignes vides', 'amapress' ) )
			                                                                 : Amapress::makeLink( remove_query_arg( 'without_empty' ), __( 'Afficher les lignes vides', 'amapress' ) ) ) : '' ) .
		                      ( $show_all_dates ? ' | ' .
		                                          Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'by', 'month' ), __( 'Afficher par mois', 'amapress' ) ), 'month' == $group_by ) .
		                                          ' | ' .
		                                          Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'by', 'quarter' ), __( 'Afficher par trimestre', 'amapress' ) ), 'quarter' == $group_by ) .
		                                          ' | ' .
		                                          Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'by', 'date' ), __( 'Afficher par date', 'amapress' ) ), 'date' == $group_by || 'none' == $group_by ) : '' ) .
		                      '</p><hr/>';
	}

	if ( $contrat_instance->isPanierVariable() ) {
		$next_distrib_text .= '<p>' . Amapress::makeLink(
				admin_url( 'admin-post.php?action=delivery_table_xlsx&type=adherents_columns&contrat=' . $contrat_instance_id ),
				__( 'Télécharger le récapitulatif des quantités avec produits en colonnes', 'amapress' ), true, true ) . '</p><hr/>';
	}

	$print_title = '';
	if ( $show_all_dates ) {
		if ( $date_is_first ) {
			$next_distrib_text .= '<h4>' . __( 'Informations pour le contrat ', 'amapress' ) . $contrat_instance->getTitle() . '</h4>';
			$print_title       = __( 'Récapitulatif pour le contrat ', 'amapress' ) . $contrat_instance->getTitle();
		} else {
			$next_distrib_text .= '<h4>' . __( 'Informations pour les prochaines distributions à partir du ', 'amapress' ) . date_i18n( 'd/m/Y', $date ) . '</h4>';
			$print_title       = __( 'Récapitulatif pour les prochaines distributions à partir du ', 'amapress' ) . date_i18n( 'd/m/Y', $date ) . ' pour le contrat ' . $contrat_instance->getTitle();
		}
		if ( ! $date_is_current && ! $for_placeholder ) {
			$next_distrib_text .= '<p>' . Amapress::makeLink(
					$root_url . '&all',
					__( 'Afficher les quantités à partir du ', 'amapress' ) . date_i18n( 'd/m/Y' ) ) . '</p><hr/>';
		}
	} else {
		if ( $dist ) {
			$next_distrib_text .= '<h4>' . __( 'Informations pour la prochaine distribution du ', 'amapress' ) . date_i18n( 'd/m/Y', $dist->getDate() ) .
			                      ' (' . Amapress::makeLink( $dist->getPermalink(), __( 'Voir la distribution', 'amapress' ), true, true )
			                      . ')</h4>';
			$print_title       = sprintf( __( 'Récapitulatif pour la prochaine distribution (%s) pour le contrat %s', 'amapress' ), date_i18n( 'd/m/Y', $dist->getDate() ), $contrat_instance->getTitle() );
		} else {
			$next_distrib_text .= '<h4>' . __( 'Informations pour la prochaine distribution à partir du ', 'amapress' ) . date_i18n( 'd/m/Y', $date ) . '</h4>';
			$print_title       = sprintf( __( 'Récapitulatif pour la prochaine distribution pour le contrat %s', 'amapress' ), $contrat_instance->getTitle() );
		}
		if ( ! $for_placeholder ) {
			if ( ! $date_is_current ) {
				$next_distrib_text .= '<p>' . Amapress::makeLink( $root_url,
						__( 'Revenir à la prochaine distribution à partir du ', 'amapress' ) . date_i18n( 'd/m/Y' ) ) . '</p><hr/>';
			}
			if ( ! $date_is_first ) {
				$next_distrib_text .= '<p>' . Amapress::makeLink( $root_url . '&date=first',
						__( 'Revenir à la première distribution à partir du ', 'amapress' ) . date_i18n( 'd/m/Y', $contrat_instance->getDate_debut() ) ) . '</p><hr/>';
			}
		}
	}
	if ( $options['show_next_distrib'] && ! $show_all_dates && ! $for_placeholder ) {
		$next_distrib_text .= '<p>' . ( $dist && Amapress::end_of_day( $dist->getDate() ) > amapress_time() ? __( 'Prochaine distribution: ', 'amapress' ) : __( 'Distribution du ', 'amapress' ) ) .
		                      ( $dist ? Amapress::makeLink(
			                      $root_url . ( $show_all_dates ? '&all' : '' ) . '&date=' . date( 'Y-m-d', $dist->getDate() ),
			                      (
			                      Amapress::start_of_week( amapress_time() ) < $dist->getDate() && $dist->getDate() < Amapress::end_of_week( amapress_time() ) ?
				                      '<strong>' . __( 'Cette semaine', 'amapress' ) . '</strong> - ' :
				                      (
				                      Amapress::start_of_week( Amapress::add_a_week( amapress_time() ) ) < $dist->getDate() && $dist->getDate() < Amapress::end_of_week( Amapress::add_a_week( amapress_time() ) ) ?
					                      '<strong>' . __( 'Semaine prochaine', 'amapress' ) . '</strong> - ' :
					                      ''
				                      ) ) . date_i18n( 'd/m/Y H:i', $dist->getStartDateAndHour() ), false )
			                      : '<strong>' . __( 'non planifiée', 'amapress' ) . '</strong>' );

		$factor = $dist ? $contrat_instance->getDateFactorDisplay( $dist->getDate() ) : null;
		if ( ! empty( $factor ) ) {
			$next_distrib_text .= ' - <strong style="color:orange">' . esc_html( $factor ) . '</strong>';
		}
		$next_distrib_text .= '</p>';
		if ( $next_next_dist ) {
			$next_distrib_text .= '<p>' . __( 'Distribution suivante : ', 'amapress' ) .
			                      Amapress::makeLink( $root_url . ( $show_all_dates ? '&all' : '' ) . '&date=' . date( 'Y-m-d', $next_next_dist->getDate() ),
				                      $next_next_dist->getTitle() ) . '</p>';
		}

		if ( ! $contrat_instance->isPanierVariable() ) {
			$next_next_distribs = array_slice( $next_next_distribs, 0, 15 );
		}

		if ( ! empty( $next_next_distribs ) ) {
			$next_distrib_text .= '<p>' . __( 'Distributions suivantes : ', 'amapress' ) . implode( ', ', array_map(
					function ( $d ) use ( $contrat_instance_id, $root_url, $show_all_dates ) {
						/** @var AmapressDistribution $d */
						return Amapress::makeLink( $root_url . ( $show_all_dates ? '&all' : '' ) . '&date=' . date( 'Y-m-d', $d->getDate() ),
							date_i18n( 'd/m/Y', $d->getDate() ) );
					}, $next_next_distribs
				) ) . '</p>';
		}

		$next_distrib_text .= '<p>' . Amapress::makeLink(
				admin_url( "edit.php?post_type=amps_distribution&amapress_date=active&amapress_contrat_inst=$contrat_instance_id" ),
				__( 'Autres dates de distribution', 'amapress' ) ) . '</p>';
	}
	$contact_producteur = '';
	if ( $options['show_contact_producteur'] ) {
		if ( ! empty( $contrat_instance->getModel() ) && ! empty( $contrat_instance->getModel()->getProducteur() ) && ! empty( $contrat_instance->getModel()->getProducteur()->getUser() ) ) {
			$contact_producteur = '<div><h5>' . __( 'Contact producteur:', 'amapress' ) . '</h5>' .
			                      $contrat_instance->getModel()->getProducteur()->getUser()->getDisplay(
				                      array(
					                      'show_avatar' => 'false',
					                      'show_tel'    => 'force',
					                      'show_sms'    => 'force',
					                      'show_email'  => 'force',
					                      'show_roles'  => 'false',
				                      ) ) . '</div>';
		}
	}

	if ( 'xlsx' == $options['mode'] ) {
		foreach ( $data as $k => $v ) {
			$data[ $k ]['price_d'] = $v['price'];
		}

		return [
			'columns' => $columns,
			'data'    => $data
		];
	}

	$output = '';
	if ( $show_all_dates || $dist ) {
		if ( 'table' == $options['mode'] || 'both' == $options['mode'] ) {
			$dt_options = array(
				'paging'       => false,
				'init_as_html' => $options['no_script'] || ( ! $show_all_dates && ! $show_adherents ),
				'no_script'    => $options['no_script'],
				'bSort'        => ! $contrat_instance->isPanierVariable(),
				'empty_desc'   => __( 'Pas de livraison', 'amapress' ),
			);
			if ( $show_all_dates ) {
				$dt_options['rowGroup'] = [
					'dataSrc' => 'date'
				];
			} elseif ( $show_adherents ) {
				$dt_options['rowGroup'] = [
					'dataSrc' => 'adherent'
				];
			}
			$output .= amapress_get_datatable( 'contrat-instance-recap-' . $contrat_instance_id,
				$columns, $data,
				$dt_options,
				array(
					[
						'extend' => Amapress::DATATABLES_EXPORT_EXCEL,
						'title'  => $print_title
					],
					[
						'extend'        => Amapress::DATATABLES_EXPORT_PRINT,
						'title'         => $print_title,
//					'autoPrint' => false,
						'exportOptions' => [
							'rowGroup' => true
						]
					],
				) );
		}
		if ( ! $show_all_dates && ( 'text' == $options['mode'] || 'both' == $options['mode'] ) ) {
			if ( count( $lieux ) > 1 ) {
				foreach ( $lieux as $lieu ) {
					$output        .= '<p>' . __( 'À ', 'amapress' ) . esc_html( $lieu->getShortName() ) . ' : ';
					$output_quants = [];
					foreach ( $data as $row ) {
						if ( ! empty( $row["lieu_{$lieu->ID}_txt"] ) ) {
							$output_quants[] = esc_html( $row["lieu_{$lieu->ID}_txt"] );
						}
					}
					$output .= sprintf( __( '(%d adhérent(s)) ; ', 'amapress' ), $row["lieu_{$lieu->ID}_adhs"] );
					$output .= implode( ', ', $output_quants );
					$output .= '</p>';
				}
			}
			$output        .= '<p' . __( 'En tout : ', 'amapress' );
			$output_quants = [];
			$last_row      = null;
			foreach ( empty( $sum_data ) ? $data : $sum_data as $row ) {
				if ( ! empty( $row["all_txt"] ) ) {
					$output_quants[] = strpos( $row["all_txt"], '=' ) !== false ? '[' . $row["all_txt"] . ']' : $row["all_txt"];
				}
				$last_row = $row;
			}
			$output .= sprintf( __( '(%s adhérent(s)) ; ', 'amapress' ), $show_adherents ?
				count( array_unique( array_map( function ( $d ) {
					return $d['adherent'];
				}, $data ) ) )
				: $last_row["all_adhs"] );
			$output .= implode( ', ', $output_quants );
			$output .= '</p>';

		}
		if ( $show_price ) {
			$output .= '<p><strong>' . __( 'Total: ', 'amapress' ) . Amapress::formatPrice( $overall_total_price, true ) . '</strong></p>';
		}
	} else {
		$output .= '<p style="text-align: center"><strong>' . __( 'Pas de distribution plannifiée à partir de cette date !', 'amapress' ) . '</strong></p>';
	}

	return '<div class="contrat-instance-recap contrat-instance-' . $contrat_instance_id . '">' .
	       $next_distrib_text .
	       $contact_producteur .
	       '<p><em>' . __( 'Information à jour en date du ', 'amapress' ) . date_i18n( 'd/m/Y', $date ) . ( ! $show_all_dates && $date != $real_date ? __( ' (panier déplacé du ', 'amapress' ) . date_i18n( 'd/m/Y', $real_date ) . ')' : '' ) . '</em></p>' .
	       $output . '</div>';
}

function amapress_producteurs_finances_custom() {
	return amapress_get_producteurs_finances_datatable( null,
		isset( $_GET['contrat_id'] ) ? [ intval( $_GET['contrat_id'] ) ] : null,
		[
			'group_date_by'        => isset( $_GET['date_by'] ) ? $_GET['date_by'] : 'none',
			'group_by'             => isset( $_GET['by'] ) ? $_GET['by'] : 'date',
			'show_adherents'       => isset( $_GET['show_adherents'] ),
			'show_adherents_count' => ! isset( $_GET['show_adherents'] )
		] );
}

function amapress_get_producteurs_finances_datatable(
	$date = null,
	$contrat_instance_ids = null,
	$options = array()
) {
	if ( ! empty( $contrat_instance_ids ) ) {
		$contrat_instances = array_map( function ( $id ) {
			return AmapressContrat_instance::getBy( $id );
		}, $contrat_instance_ids );
	} else {
		$contrat_instances = AmapressContrats::get_active_contrat_instances( null, $date );
	}

	$options = wp_parse_args(
		$options,
		array(
			'show_adherents'       => false,
			'show_adherents_count' => true,
			'show_empty_lines'     => false,
			'group_by'             => 'date',
			'group_date_by'        => 'none',
			'no_script'            => false,
		)
	);

	$group_date_by = $options['group_date_by'];
	if ( empty( $group_date_by ) ) {
		$group_date_by = 'none';
	}
	$show_adherents_count = $options['show_adherents_count'];
	$show_adherents       = $options['show_adherents'];
	$show_empty_lines     = $options['show_empty_lines'];

	$columns = [
		array(
			'title' => __( 'Date', 'amapress' ),
			'data'  => array(
				'_'    => 'date',
				'sort' => 'date_sort',
			)
		),
	];

	$columns[] = array(
		'title' => __( 'Producteur', 'amapress' ),
		'data'  => array(
			'_'    => 'prod',
			'sort' => 'prod',
		)
	);

	if ( $show_adherents_count ) {
		$columns[] = array(
			'title' => __( 'Adhérents', 'amapress' ),
			'data'  => array(
				'_'    => "all_adhs",
				'sort' => "all_adhs",
			)
		);
	} elseif ( $show_adherents ) {
		$columns[] = array(
			'title' => __( 'Adhérent', 'amapress' ),
			'data'  => array(
				'_'    => "adherent",
				'sort' => "adherent",
			)
		);
		$columns[] = array(
			'title' => __( 'Lieu', 'amapress' ),
			'data'  => array(
				'_'    => "lieu",
				'sort' => "lieu",
			)
		);
	}
	$columns[] = array(
		'title' => __( 'Total', 'amapress' ),
		'data'  => array(
			'_'    => 'price_d',
			'sort' => 'price',
		)
	);

	$data = [];
	if ( $show_adherents ) {
		foreach ( $contrat_instances as $contrat_instance ) {
			$inscriptions = AmapressContrats::get_all_adhesions( $contrat_instance->getID() );

			foreach ( $contrat_instance->getListe_dates() as $date ) {
				$date_inscriptions = array_filter( $inscriptions,
					function ( $inscription ) use ( $date ) {
						/** @var AmapressAdhesion $inscription */
						return Amapress::start_of_day( $inscription->getDate_debut() ) <= Amapress::start_of_day( $date )
						       && Amapress::end_of_day( $date ) <= Amapress::end_of_day( $inscription->getDate_fin() );
					} );
				/** @var AmapressAdhesion $inscription */
				foreach ( $date_inscriptions as $inscription ) {
					$row = array();

					$row['prod'] = $contrat_instance->getModel()->getTitle()
					               . '<br />'
					               . '<em>' . $contrat_instance->getModel()->getProducteur()->getTitle() . '</em>';

					$row['lieu']     = $inscription->getLieu()->getShortName();
					$row['adherent'] = sprintf( __( '%s<br/>(%s)', 'amapress' ),
						$inscription->getAdherent()->getSortableDisplayName(), $inscription->getAdherent()->getEmail() );

					$total_amount = 0;
					foreach ( $inscription->getContrat_quantites( $date ) as $q ) {
						$total_amount += $q->getPrice();
					}

					if ( $total_amount < 0.001 && ! $show_empty_lines ) {
						continue;
					}

					$row['price_d'] = Amapress::formatPrice( $total_amount, true );
					$row['price']   = $total_amount;

					if ( 'quarter' == $group_date_by ) {
						$quarter          = ceil( intval( date( 'n', $date ) ) / 3 );
						$row['date']      = "T$quarter";
						$row['date_sort'] = "T$quarter";
					} elseif ( 'month' == $group_date_by ) {
						$row['date']      = date_i18n( 'm/Y', $date );
						$row['date_sort'] = date( 'Y-m', $date );
					} else {
						$row['date']      = date_i18n( 'd/m/Y', $date );
						$row['date_sort'] = date( 'Y-m-d', $date );
					}
					$key = $row['date_sort'] . $row['prod'] . $row['adherent'] . $row['lieu'];
					if ( isset( $data[ $key ] ) ) {
						foreach ( $row as $k => $v ) {
							if ( 'prod' == $k || 'adherent' == $k || 'lieu' == $k || 'date' == $k || 'date_sort' == $k ) {
								continue;
							}
							if ( is_string( $v ) ) {
								$data[ $key ][ $k ] .= $v;
							} else {
								$data[ $key ][ $k ] += $v;
							}
						}
						$data[ $key ]['price_d'] = Amapress::formatPrice( $data[ $key ]['price'], true );
					} else {
						$data[ $key ] = $row;
					}
				}
			}
		}
	} else {
		foreach ( $contrat_instances as $contrat_instance ) {
			$stats = $contrat_instance->getInscriptionsStats()['lines'];
			foreach ( $stats as $date_stat ) {
				$real_date = intval( $date_stat['date_int'] );
				$row       = array();

				$row['prod'] = $contrat_instance->getModel()->getTitle()
				               . '<br />'
				               . '<em>' . $contrat_instance->getModel()->getProducteur()->getTitle() . '</em>';

				$row['lieu']     = '';
				$row['adherent'] = '';

				$row['price_d'] = Amapress::formatPrice( $date_stat['lieu_all_p'], true );
				$row['price']   = $date_stat['lieu_all_p'];

				$row['all_adhs'] = $date_stat['lieu_all_inscriptions'];

				if ( 'quarter' == $group_date_by ) {
					$quarter          = ceil( intval( date( 'n', $real_date ) ) / 3 );
					$row['date']      = "T$quarter";
					$row['date_sort'] = "T$quarter";
				} elseif ( 'month' == $group_date_by ) {
					$row['date']      = date_i18n( 'm/Y', $real_date );
					$row['date_sort'] = date( 'Y-m', $real_date );
				} else {
					$row['date']      = date_i18n( 'd/m/Y', $real_date );
					$row['date_sort'] = date( 'Y-m-d', $real_date );
				}
				$key = $row['date_sort'] . $row['prod'];
				if ( isset( $data[ $key ] ) ) {
					foreach ( $row as $k => $v ) {
						if ( 'prod' == $k || 'date' == $k || 'date_sort' == $k ) {
							continue;
						}
						if ( 'all_adhs' == $k ) {
							$data[ $key ][ $k ] = $v > $data[ $key ][ $k ] ? $v : $data[ $key ][ $k ];
							continue;
						}
						if ( is_string( $v ) ) {
							$data[ $key ][ $k ] .= $v;
						} else {
							$data[ $key ][ $k ] += $v;
						}
					}
					$data[ $key ]['price_d'] = Amapress::formatPrice( $data[ $key ]['price'], true );
				} else {
					$data[ $key ] = $row;
				}
			}
		}
	}
	$data     = array_values( $data );
	$group_by = ! empty( $options['group_by'] ) ? $options['group_by'] : 'date';
	if ( 'date' == $group_by ) {
		usort( $data, function ( $a, $b ) {
			$ret = strcmp( $a['date_sort'], $b['date_sort'] );
			if ( 0 == $ret ) {
				$ret = strcmp( $a['prod'], $b['prod'] );
			}
			if ( 0 == $ret ) {
				$ret = strcmp( $a['lieu'], $b['lieu'] );
			}
			if ( 0 == $ret ) {
				$ret = strcmp( $a['adherent'], $b['adherent'] );
			}

			return $ret;
		} );
	} elseif ( 'prod' == $group_by ) {
		usort( $data, function ( $a, $b ) {
			$ret = strcmp( $a['prod'], $b['prod'] );
			if ( 0 == $ret ) {
				$ret = strcmp( $a['date_sort'], $b['date_sort'] );
			}
			if ( 0 == $ret ) {
				$ret = strcmp( $a['lieu'], $b['lieu'] );
			}
			if ( 0 == $ret ) {
				$ret = strcmp( $a['adherent'], $b['adherent'] );
			}

			return $ret;
		} );
	} elseif ( 'adh' == $group_by ) {
		usort( $data, function ( $a, $b ) {
			$ret = strcmp( $a['adherent'], $b['adherent'] );
			if ( 0 == $ret ) {
				$ret = strcmp( $a['date_sort'], $b['date_sort'] );
			}
			if ( 0 == $ret ) {
				$ret = strcmp( $a['lieu'], $b['lieu'] );
			}
			if ( 0 == $ret ) {
				$ret = strcmp( $a['prod'], $b['prod'] );
			}

			return $ret;
		} );
	}


	$print_title = __( 'Récapitulatif financier des producteurs', 'amapress' );

	$dt_options             = array(
		'paging'       => false,
		'init_as_html' => $options['no_script'],
		'no_script'    => $options['no_script'],
		'bSort'        => false,
	);
	$dt_options['rowGroup'] = [
		'dataSrc' => ! empty( $options['group_by'] ) ? $options['group_by'] : 'date'
	];
	$output                 = amapress_get_datatable( 'contrat-instance_finances-recap',
		$columns, $data,
		$dt_options,
		array(
			[
				'extend' => Amapress::DATATABLES_EXPORT_EXCEL,
				'title'  => $print_title
			],
			[
				'extend'        => Amapress::DATATABLES_EXPORT_PRINT,
				'title'         => $print_title,
				'exportOptions' => [
					'rowGroup' => true
				]
			],
		) );

	$contrat_instances_ids = array_map( function ( $c ) {
		return $c->ID;
	}, $contrat_instances );
	$all_contrat_instances = AmapressContrats::get_active_contrat_instances( null, $date );
	usort( $all_contrat_instances, function ( $a, $b ) {
		/** @var AmapressContrat_instance $a */
		/** @var AmapressContrat_instance $b */
		if ( $a->getDate_debut() == $b->getDate_debut() ) {
			return strcmp( $a->getTitle(), $b->getTitle() );
		} else {
			return $a->getDate_debut() < $b->getDate_debut() ? - 1 : 1;
		}
	} );
	$filters = '<p>' .
	           implode( ' | ',
		           array_map( function ( $contrat ) use ( $contrat_instances_ids ) {
			           /** @var AmapressContrat_instance $contrat */
			           return Amapress::wrapIf(
				           Amapress::makeLink( add_query_arg( 'contrat_id', $contrat->ID ), $contrat->getTitle() ),
				           in_array( $contrat->ID, $contrat_instances_ids ) );
		           }, $all_contrat_instances )
	           )
	           . '</p><hr/>';

	$filters .= '<p>' .
	            ( $show_adherents ?
		            Amapress::makeLink( remove_query_arg( 'show_adherents' ),
			            __( 'Masquer les adhérents', 'amapress' ) ) :
		            Amapress::makeLink( add_query_arg( 'show_adherents', 'T' ),
			            __( 'Afficher les adhérents', 'amapress' ) ) ) .
	            '</p><hr/>';
	$filters .= '<p>' .
	            Amapress::wrapIf(
		            Amapress::makeLink( add_query_arg( 'date_by', 'date' ),
			            __( 'Afficher par date', 'amapress' ) ),
		            'date' == $group_date_by ) .
	            ' | ' .
	            Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'date_by', 'month' ),
		            __( 'Afficher par mois', 'amapress' ) ),
		            'month' == $group_date_by ) .
	            ' | ' .
	            Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'date_by', 'quarter' ),
		            __( 'Afficher par trimestre', 'amapress' ) ),
		            'quarter' == $group_date_by ) .
	            '</p><hr/>';
	$filters .= '<p>' .
	            Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'by', 'date' ),
		            __( 'Grouper par date', 'amapress' ) ),
		            'date' == $group_by ) .
	            ' | ' .
	            Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'by', 'prod' ),
		            __( 'Grouper par producteur', 'amapress' ) ),
		            'prod' == $group_by ) .
	            ( $show_adherents ?
		            ' | ' .
		            Amapress::wrapIf( Amapress::makeLink( add_query_arg( 'by', 'adh' ),
			            __( 'Grouper par adhérent', 'amapress' ) ),
			            'adh' == $group_by )
		            : '' ) .
	            '</p><hr/>';

	return '<div class="contrat-instances-finances-recap">' .
	       '<p><em>' . __( 'Information à jour en date du ', 'amapress' ) . date_i18n( 'd/m/Y', $date ) . '</em></p>' .
	       $filters .
	       $output . '</div>';
}

//function amapress_echo_all_contrat_paiements_by_date() {
//	$ret = '';
//	foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
//		$ret .= amapress_get_paiement_table_by_dates( $contrat_instances_id );
//	}
//
//	return $ret;
//}

function amapress_get_paiement_table_by_dates(
	$contrat_instance_id,
	$lieu_id = null,
	$options = array()
) {

	$options = wp_parse_args(
		$options,
		array(
			'show_next_distrib'       => true,
			'show_contact_producteur' => true,
			'show_all_dates'          => true,
			'for_pdf'                 => false,
		)
	);

	$for_pdf         = $options['for_pdf'];
	$lien_export_pdf = '';
	if ( ! $for_pdf ) {
		$lien_export_pdf = '<p>';
		$lien_export_pdf .= '<a class="button button-primary" href="' . esc_attr( admin_url( 'admin-post.php?action=paiement_table_pdf&lieu=' . $lieu_id . '&contrat=' . $contrat_instance_id ) ) . '">' . __( 'Exporter en PDF', 'amapress' ) . '</a>';
		$lien_export_pdf .= '<a class="button button-primary" href="' . esc_attr( admin_url( 'admin-post.php?action=paiement_table_xlsx&lieu=' . $lieu_id . '&contrat=' . $contrat_instance_id ) ) . '">' . __( 'Exporter en Excel', 'amapress' ) . '</a>';
		$lien_export_pdf .= '</p>';
	}

	$title      = __( 'Calendrier des paiements', 'amapress' );
	$lieu_title = '';
	if ( ! empty( $lieu_id ) ) {
		$lieu       = AmapressLieu_distribution::getBy( $lieu_id );
		$title      .= ' - ' . $lieu->getTitle();
		$lieu_title = '<h1>' . esc_html( $lieu->getTitle() ) . '</h1>';
	}
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
	$paiements        = AmapressContrats::get_all_paiements( $contrat_instance_id, $lieu_id );
	$user_ids         = array_map( function ( $p ) {
		/** @var AmapressAmapien_paiement $p */
		return $p->getAdhesion()->getAdherentId();
	}, $paiements );
	update_meta_cache( 'user', $user_ids );
	cache_users( $user_ids );
	$dates = array_map(
		function ( $p ) {
			/** @var AmapressAmapien_paiement $p */
			return $p->getDate();
		}, $paiements );
	$dates = array_merge( $dates, $contrat_instance->getPaiements_Liste_dates() );
	$dates = array_unique( $dates );
	if ( ! $options['show_all_dates'] ) {
		$dates = array_filter( $dates, function ( $d ) {
			return $d > Amapress::start_of_day( amapress_time() );
		} );
	}
	$dates = array_filter( $dates, function ( $d ) {
		return ! empty( $d );
	} );
	sort( $dates );
	$emetteurs = array_map(
		function ( $p ) use ( $paiements ) {
			/** @var AmapressAmapien_paiement $p */
			$all_emetteurs =
				array_map(
					function ( $op ) {
						/** @var AmapressAmapien_paiement $op */
						return $op->getEmetteur();
					},
					array_filter(
						$paiements,
						function ( $op ) use ( $p ) {
							/** @var AmapressAmapien_paiement $op */
							return $op->getAdhesionId() == $p->getAdhesionId();
						}
					)
				);
			if ( $p->getAdhesion()->getAdherent() ) {
				$all_emetteurs[] = $p->getAdhesion()->getAdherent()->getDisplayName();
			}
			if ( $p->getAdhesion()->getAdherent2() ) {
				$all_emetteurs[] = $p->getAdhesion()->getAdherent2()->getDisplayName();
			}
			if ( $p->getAdhesion()->getAdherent3() ) {
				$all_emetteurs[] = $p->getAdhesion()->getAdherent3()->getDisplayName();
			}
			$all_emetteurs = array_unique( $all_emetteurs );
			$all_emetteurs = array_filter( $all_emetteurs,
				function ( $em ) {
					return ! empty( $em );
				} );
			sort( $all_emetteurs );
			$all_emetteurs = array_map(
				function ( $em ) use ( $p ) {
					if ( $em == $p->getEmetteur() ) {
						return '<strong>[' . esc_html( $em ) . ']</strong>';
					} else {
						return esc_html( $em );
					}
				},
				$all_emetteurs
			);

			$date_debut = date_i18n( 'd/m/Y', $p->getAdhesion()->getDate_debut() );
			if ( $p->getAdhesion()->hasDate_fin() ) {
				$date_debut .= '>' . date_i18n( 'd/m/Y', $p->getAdhesion()->getDate_fin() );
			}

			return array(
				'emetteur'       => $p->getEmetteur(),
				'banque'         => $p->getBanque(),
				'last_name'      => $p->getAdhesion()->getAdherent()->getUser()->last_name,
				'lieu'           => $p->getAdhesion()->getLieu()->getShortName(),
				'quantite'       => $p->getAdhesion()->getContrat_quantites_Codes_AsString(),
				'label'          => implode( ', ', $all_emetteurs ),
				'href'           => $p->getAdhesion()->getAdminEditLink(),
				'date_debut'     => $date_debut,
				'date_debut_raw' => $p->getAdhesion()->getDate_debut()
			);
		}, $paiements );
	$emitters  = array();
	foreach ( $emetteurs as $emetteur ) {
		$emitters[ $emetteur['emetteur'] ] = $emetteur;
	}
	usort( $emitters,
		function ( $a, $b ) {
			$a_emetteur = $a['last_name'];
			$b_emetteur = $b['last_name'];
			if ( $a_emetteur == $b_emetteur ) {
				return 0;
			}

			return $a_emetteur < $b_emetteur ? - 1 : 1;
		} );

	$columns = [];
	if ( empty( $lieu_id ) ) {
		$columns[] =
			array(
				'title' => __( 'Lieu', 'amapress' ),
				'data'  => 'lieu',
			);
	}
	$columns[] =
		array(
			'title' => __( 'Nom', 'amapress' ),
			'data'  => 'last_name',
		);
	$columns[] =
		array(
			'title' => __( 'Début', 'amapress' ),
			'data'  => 'date_debut',
		);
	$columns[] =
		array(
			'title' => __( 'Emetteur', 'amapress' ),
			'data'  => 'emetteur',
		);
	$columns[] =
		array(
			'title' => __( 'Quantité', 'amapress' ),
			'data'  => 'quantite',
		);
	$columns[] =
		array(
			'title' => __( 'Banque', 'amapress' ),
			'data'  => 'banque',
		);

	foreach ( $dates as $date ) {
		$columns[] = array(
			'title' => Amapress::makeLink( admin_url( 'edit.php?post_type=amps_cont_pmt&amapress_contrat_inst=' . $contrat_instance_id . '&amapress_date=' . date( 'Y-m-d', $date ) . '&amapress_lieu=' . $lieu_id ), date_i18n( 'd/m/Y', $date ), true, true ),
			'data'  => "date_{$date}",
		);
	}

	$data = array();
	foreach ( $emitters as $emetteur_obj ) {
		$emetteur           = $emetteur_obj['emetteur'];
		$emetteur_label     = $emetteur_obj['label'];
		$emetteur_href      = $emetteur_obj['href'];
		$row                = array(
			'emetteur'   => Amapress::makeLink( $emetteur_href, $emetteur_label, false ),
			'last_name'  => esc_html( $emetteur_obj['last_name'] ),
			'lieu'       => esc_html( $emetteur_obj['lieu'] ),
			'banque'     => esc_html( $emetteur_obj['banque'] ),
			'quantite'   => esc_html( $emetteur_obj['quantite'] ),
			'date_debut' => Amapress::makeLink( admin_url( 'edit.php?post_type=amps_adhesion&amapress_contrat_inst=' . $contrat_instance_id . '&amapress_date=' . date( 'Y-m', $emetteur_obj['date_debut_raw'] ) . '&amapress_lieu=' . $lieu_id ), $emetteur_obj['date_debut'], true, true ),
		);
		$emetteur_paiements = array_filter(
			$paiements,
			function ( $p ) use ( $emetteur ) {
				/** @var AmapressAmapien_paiement $p */
				return $p->getEmetteur() == $emetteur;
			}
		);

		foreach ( $dates as $date ) {
			$emetteur_date_paiements = array_filter(
				$emetteur_paiements,
				function ( $p ) use ( $date ) {
					/** @var AmapressAmapien_paiement $p */
					return $p->getDate() == $date;
				}
			);

			$contrat_adhesion = null;
			if ( count( $emetteur_paiements ) > 0 ) {
				$emetteur_paiements2 = array_values( $emetteur_paiements );
				$contrat_adhesion    = array_shift( $emetteur_paiements2 )->getAdhesion();
			}
			if ( $contrat_adhesion && $date < $contrat_adhesion->getDate_debut() ) {
				$val = [
					'value' => '&gt;&gt;&gt;',
					'style' => 'background-color: #ccc;',
				];
			} else if ( $contrat_adhesion && $date > $contrat_adhesion->getDate_fin() ) {
				$val = [
					'value' => '&lt;&lt;&lt;',
					'style' => 'background-color: #ccc;',
				];
			} else {
				$val = implode( ',', array_filter(
					array_map(
						function ( $p ) use ( $emetteur_obj ) {
							/** @var AmapressAmapien_paiement $p */
							$banque = $p->getBanque();
							if ( ! empty( $banque ) && $emetteur_obj['banque'] != $banque ) {
								return esc_html( "{$p->getNumero()} ({$banque})" );
							} else {
								return esc_html( "{$p->getNumero()}" );
							}
						}, $emetteur_date_paiements )
					, function ( $e ) {
					return ! empty( $e );
				} ) );
			}
			$row["date_{$date}"] = $val;
		}
		$data[] = $row;
	}

	$next_distribs = AmapressDistribution::get_next_distributions( amapress_time(), 'ASC' );
	$dist          = null;
	foreach ( $next_distribs as $distrib ) {
		if ( in_array( $contrat_instance_id, $distrib->getContratIds() ) && ( empty( $lieu_id ) || $distrib->getLieuId() == $lieu_id ) ) {
			$dist = $distrib;
			break;
		}
	}

	$id = "contrat-$contrat_instance_id-paiements-month-$lieu_id";
	$fn = "contrat_{$contrat_instance_id}_paiements_month_$lieu_id";

	$next_distrib_text = '';
	if ( $options['show_next_distrib'] ) {
		$next_distrib_text = '<p>' . __( 'Prochaine distribution: ', 'amapress' ) . ( $dist ? ( $dist && Amapress::end_of_week( amapress_time() ) > $dist->getDate() ? '<strong>' . __( 'Cette semaine', 'amapress' ) . '</strong> - ' : '' ) . date_i18n( 'd/m/Y H:i', $dist->getStartDateAndHour() ) : '<strong>' . __( 'non planifiée', 'amapress' ) . '</strong>' ) . '</p>';
	}
	$contact_producteur = '';
	if ( $options['show_contact_producteur'] ) {
		if ( ! empty( $contrat_instance->getModel() ) && ! empty( $contrat_instance->getModel()->getProducteur() ) && ! empty( $contrat_instance->getModel()->getProducteur()->getUser() ) ) {
			$contact_producteur = '<div><h5>' . __( 'Contact producteur:', 'amapress' ) . '</h5>' .
			                      $contrat_instance->getModel()->getProducteur()->getUser()->getDisplay(
				                      array(
					                      'show_avatar' => 'false',
					                      'show_tel'    => 'force',
					                      'show_sms'    => 'force',
					                      'show_email'  => 'force',
					                      'show_roles'  => 'false',
				                      ) ) . '</div>';
		}
	}

	$ret = '<div class="contrat-instance-recap contrat-instance-' . $contrat_instance_id . '">
' . $lieu_title . $next_distrib_text . $contact_producteur . $lien_export_pdf;
	$ret .= amapress_get_datatable(
		$id,
		$columns, $data,
		array(
			'bSort'          => true,
			'paging'         => false,
			'searching'      => true,
			'bAutoWidth'     => true,
			'responsive'     => false,
			'scrollX'        => true,
			'scrollY'        => '250px',
			'scrollCollapse' => true,
			'cell-border'    => true,
			'fixedColumns'   => array( 'leftColumns' => 1 ),
			'fixedHeader'    => true,
			//			       'initComplete' => $fn,
			'init_as_html'   => true,
			'no_script'      => $for_pdf,
//			       'dom'          => __('Bfrtip', 'amapress'),
//			       'buttons'      => [],
		)
	);
	if ( $for_pdf ) {
		$ret .= '<style type="text/css">
a {
	color: black;
	text-decoration: none;
}
table, td { 
	border: 1px solid black; 
	border-collapse: collapse; 
	padding: 2px; 
	font-size: 8pt;
}
.odd { 
	background-color: #EEEEEE; 
}
th {
	border: 1px solid black; 
	border-collapse: collapse; 
	padding: 2px; 
	font-size: 8pt;
	font-weight: bold;
}
</style>';
	} else {
//		$ret .= '
//<script type="text/javascript">
//function ' . $fn . '() {
//var table = jQuery(\'#' . $id . '\').DataTable();
//new jQuery.fn.dataTable.Buttons( table, {
//    buttons: [
//        \'excel\',
//        {
//            extend: \'print\',
//            text: \'Imprimer\',
//            title: \'' . $title . '\',
//            autoPrint: false,
//            exportOptions: {
//                columns: \':visible\',
//                stripHtml: false,
//            },
//            customize: function (win) {
//                jQuery(win.document.body).css(\'background-color\', \'#fff\');
//                jQuery(win.document.body).find(\'table\').addClass(\'display\');/*.css(\'font-size\', \'9px\');*/
//                jQuery(win.document.body).find(\'table td\').css(\'border\', \'1px solid black\');
//                jQuery(win.document.body).find(\'tr:nth-child(odd) td\').each(function(index){
//                    jQuery(this).css(\'background-color\',\'#eee\');
//                });
//                jQuery(win.document.body).find(\'a\').css(\'color\',\'black\').css(\'text-decoration\',\'none\');
//            }
//        }
//    ]
//} );
//    table.buttons( 0, null ).container().prependTo(
//        table.table().container()
//    );
//}
//</script>';
	}
	$ret .= '</div>';

	return $ret;
}

add_action( 'tf_custom_admin_amapress_action_existing_user', function () {
	wp_redirect_and_exit( add_query_arg( 'user_id', $_POST['user_id'] ) );
} );
add_action( 'tf_custom_admin_amapress_action_new_user', function () {
	$email      = $_POST['email'];
	$last_name  = $_POST['last_name'];
	$first_name = $_POST['first_name'];
	$address    = $_POST['address'];
	$tel        = $_POST['tel'];

	$user_id = amapress_create_user_if_not_exists( $email, $first_name, $last_name, $address, $tel, 'user' );

	wp_redirect_and_exit( add_query_arg( 'user_id', $user_id ) );
} );

function amapress_create_user_and_adhesion_assistant( $post_id, TitanFrameworkOption $option ) {
	if ( isset( $_REQUEST['user_id'] ) ) {
		if ( isset( $_REQUEST['assistant'] ) ) {
			echo do_shortcode( '[inscription-en-ligne admin_mode=true]' );
		} else {

			echo '<h4>2/ Inscription</h4>';

			$user = AmapressUser::getBy( $_REQUEST['user_id'] );

			echo '<hr />';
			echo $user->getDisplay();
			echo '<p>' . Amapress::makeButtonLink( $user->getEditLink(), __( 'Modifier', 'amapress' ), true, true ) . '</p>';
			echo '<hr />';


			Amapress::setFilterForReferent( false );
			$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user->ID );
			Amapress::setFilterForReferent( true );
			usort( $adhs, function ( $a, $b ) {
				return strcmp( $a->getTitle(), $b->getTitle() );
			} );
			echo '<p><strong>' . __( 'Ses contrats/commandes :', 'amapress' ) . '</strong></p>';
			echo '<ul style="list-style-type: circle">';
			foreach ( $adhs as $adh ) {
				$renew_url = '';
				if ( Amapress::start_of_day( $adh->getDate_fin() ) < Amapress::start_of_day( amapress_time() ) ) {
					$renew_url = 'edit.php?post_type=amps_adhesion&action=renew&amp_id=' . $adh->ID;
					$renew_url = esc_url( wp_nonce_url( $renew_url,
							"renew_{$adh->ID}" )
					);
				}
				echo '<li style="margin-left: 35px">';
				$lnk = current_user_can( 'edit_post', $adh->ID ) ?
					'<a target="_blank" href="' . esc_attr( $adh->getAdminEditLink() ) . '" >' . __( 'Voir', 'amapress' ) . '</a>&nbsp;:&nbsp;' : '';
				echo $lnk . esc_html( $adh->getTitle() );
				if ( ! empty( $renew_url ) ) {
					echo '<br/><a target="_blank" href="' . $renew_url . '" class="button button-secondary">' . __( 'renouveler', 'amapress' ) . '</a>';
				}
				echo '</li>';
			}
			if ( empty( $adhs ) ) {
				echo '<li>' . __( 'Aucun contrat', 'amapress' ) . '</li>';
			}
			echo '</ul>';

			$add_url = add_query_arg( 'assistant', true );
			echo '<p><a target="_blank" href="' . $add_url . '" class="button button-secondary">' . __( 'Inscription avec l\'assistant', 'amapress' ) . '</a></p>';
			echo '<br />';

			$add_url = admin_url( 'post-new.php?post_type=amps_adhesion&amapress_adhesion_adherent=' . $user->ID );
			echo '<h4>' . __( 'Configuration avancée', 'amapress' ) . '</h4>';
			echo '<p><a target="_blank" href="' . $add_url . '" class="button button-secondary">' . __( 'Inscription classique', 'amapress' ) . '</a></p>';
		}
	} else {
		echo '<h4>1/ Choisir un utilisateur ou le créer</h4>';
		$options = [];
		Amapress::setFilterForReferent( false );
		$all_user_adhs = AmapressContrats::get_active_adhesions();
		Amapress::setFilterForReferent( true );
		amapress_precache_all_users();
		/** @var WP_User $user */
		foreach ( get_users() as $user ) {
			$user_adhs            = from( $all_user_adhs )
				->count( function ( $a ) use ( $user ) {
					/** @var AmapressAdhesion $a */
					return $a->getAdherentId() == $user->ID;
				} );
			$options[ $user->ID ] = sprintf( __( '%s[%s] (%s contrat(s))', 'amapress' ), $user->display_name, $user->user_email, $user_adhs );
		}

		echo '<form method="post" id="existing_user">';
		echo '<input type="hidden" name="action" value="existing_user" />';
		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<select style="max-width: none; min-width: 50%;" id="user_id" name="user_id" class="autocomplete" data-placeholder="' . esc_attr__( 'Sélectionner un utilisateur', 'amapress' ) . '">';
		tf_parse_select_options( $options, isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null );
		echo '</select><br />';
		echo '<input type="submit" class="button button-primary" value="' . esc_attr__( 'Choisir', 'amapress' ) . '" />';
		echo '</form>';

		echo '<p><strong>' . __( 'OU', 'amapress' ) . '</strong></p>';

		echo '<form method="post" id="new_user">';
		echo '<input type="hidden" name="action" value="new_user" />';
		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<table style="min-width: 50%">';
		echo '<tr>';
		echo '<th style="text-align: left; width: auto"><label style="width: 10%" for="email">' . __( 'Email: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="email" name="email" class="required email emailDoesNotExists" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="last_name">' . __( 'Nom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="first_name">' . __( 'Prénom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="tel">' . __( 'Téléphone: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="tel" name="tel" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="address">' . __( 'Adresse: ', 'amapress' ) . '</label></th>
<td><textarea style="width: 100%" rows="8" id="address" name="address" class=""></textarea>';
		echo '</tr>';
		echo '</table>';
		echo '<input style="min-width: 50%" type="submit" class="button button-primary" value="' . esc_attr__( 'Créer l\'amapien', 'amapress' ) . '" />';
		echo '</form>';
	}
	echo '<hr />';
	echo '<p><a href="' . remove_query_arg( [
			'user_id',
			'step',
			'assistant'
		] ) . '" class="button button-primary">' . __( 'Choisir un autre amapien', 'amapress' ) . '</a></p>';
	echo '<script type="text/javascript">jQuery(function($) {
    $("#user_id").select2({
        allowClear: true,
		  escapeMarkup: function(markup) {
		return markup;
	},
		  templateResult: function(data) {
		return $("<span>"+data.text+"</span>");
	},
		  templateSelection: function(data) {
		return $("<span>"+data.text+"</span>");
	},
    });
    $("form#new_user").validate({
                onkeyup: false,
        }
    );
});
</script>
<style type="text/css">
	.error {
		font-weight: bold;
		color: red;
	}
</style>';
}

add_action( 'tf_custom_admin_amapress_action_new_user_distrib', function () {
	$email = $_POST['email'];
	if ( empty( $email ) ) {
		$email = uniqid( 'm' ) . '@nomail.org';
	}
	$last_name  = $_POST['last_name'];
	$first_name = $_POST['first_name'];
	$address    = $_POST['address'];
	$tel        = $_POST['tel'];

	$user_id = amapress_create_user_if_not_exists( $email, $first_name, $last_name, $address, $tel, 'user' );

	wp_redirect_and_exit( add_query_arg( 'user_id', $user_id ) );
} );
function amapress_create_user_for_distribution( $post_id, TitanFrameworkOption $option ) {
	if ( isset( $_REQUEST['user_id'] ) ) {
		echo '<h4>' . __( 'Utilisateur créé:', 'amapress' ) . '</h4>';

		$user = AmapressUser::getBy( $_REQUEST['user_id'] );

		echo '<hr />';
		echo $user->getDisplay();
		echo '<hr />';
	} else {
		echo '<h4>' . __( 'Entrer les informations sur la personne hors AMAP', 'amapress' ) . '</h4>';
		echo '<form method="post" id="new_user_distrib">';
		echo '<input type="hidden" name="action" value="new_user_distrib" />';
		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<table style="min-width: 50%">';
		echo '<tr>';
		echo '<th style="text-align: left; width: auto"><label style="width: 10%" for="email">' . __( 'Email: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="email" name="email" class="email emailDoesNotExists" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="last_name">' . __( 'Nom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="first_name">' . __( 'Prénom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="tel">' . __( 'Téléphone: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="tel" name="tel" class="" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="address">' . __( 'Adresse: ', 'amapress' ) . '</label></th>
<td><textarea style="width: 100%" rows="8" id="address" name="address" class=""></textarea>';
		echo '</tr>';
		echo '</table>';
		echo '<input style="min-width: 50%" type="submit" class="button button-primary" value="' . esc_attr__( 'Créer la personne', 'amapress' ) . '" />';
		echo '</form>';
		echo '<script type="text/javascript">jQuery(function($) {
    $("form#new_user_distrib").validate({
                onkeyup: false,
        }
    );
});
</script>
<style type="text/css">
	.error {
		font-weight: bold;
		color: red;
	}
</style>';
	}
	echo '<hr />';
	echo '<p><a href="' . remove_query_arg( 'user_id' ) . '" class="button button-primary">' . __( 'Ajouter une autre personne', 'amapress' ) . '</a></p>';
}

add_action( 'tf_custom_admin_amapress_action_new_coadherent', function () {
	$email      = $_POST['email'];
	$last_name  = $_POST['last_name'];
	$first_name = $_POST['first_name'];
	$address    = $_POST['address'];
	$tel        = $_POST['tel'];
	$type       = $_POST['type'];

	$co_id = amapress_create_user_if_not_exists( $email, $first_name, $last_name, $address, $tel, 'user' );

	$user_id = intval( $_REQUEST['user_id'] );
	$user    = AmapressUser::getBy( $user_id );
	$user->addCoadherent( $co_id, null, 'cof' == $type );

	wp_redirect_and_exit( add_query_arg( 'user_id', $user_id ) );
} );
function amapress_create_coadhesion_assistant( $post_id, TitanFrameworkOption $option ) {
	if ( isset( $_REQUEST['user_id'] ) ) {
		$user_id = intval( $_REQUEST['user_id'] );
		$user    = AmapressUser::getBy( $user_id );

		echo '<h4>3/ Adhérent principal</h4>';
		echo '<hr />';
		echo $user->getDisplay();
		echo '<hr />';
		echo '<h4>4/ Coadhérents / Membres du foyer :</h4>';

		foreach ( AmapressContrats::get_related_users( $user_id, false, null, null, true, true ) as $co_id ) {
			if ( $co_id == $user_id ) {
				continue;
			}
			$co = AmapressUser::getBy( $co_id );
			echo $co->getDisplay();
		}
	} else {
		echo '<form method="post" id="new_coadherent">';
		echo '<input type="hidden" name="action" value="new_coadherent" />';

		echo '<h4>1/ Choisir l\'adhérent principal</h4>';
		$options       = [];
		$all_user_adhs = AmapressContrats::get_active_adhesions();
		amapress_precache_all_users();
		/** @var WP_User $user */
		foreach ( get_users() as $user ) {
			$user_adhs            = from( $all_user_adhs )
				->count( function ( $a ) use ( $user ) {
					/** @var AmapressAdhesion $a */
					return $a->getAdherentId() == $user->ID;
				} );
			$options[ $user->ID ] = $user->display_name . '[' . $user->user_email . '] (' . $user_adhs . ' contrat(s))';
		}

		echo '<select style="max-width: none; min-width: 50%;" id="user_id" name="user_id" class="autocomplete required" data-placeholder="' . esc_attr__( 'Sélectionner un utilisateur', 'amapress' ) . '">';
		tf_parse_select_options( $options, isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null );
		echo '</select><br />';

		echo '<p><strong>2/ Saisir les coordonnées du coadhérent :</strong></p>';

		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<table style="min-width: 50%">';
		echo '<tr>';
		echo '<th style="text-align: left; width: auto"><label style="width: 10%" for="email">' . __( 'Email: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="email" name="email" class="required email" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="last_name">' . __( 'Nom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="first_name">' . __( 'Prénom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="tel">' . __( 'Téléphone: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="tel" name="tel" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="address">' . __( 'Adresse: ', 'amapress' ) . '</label></th>
<td><textarea style="width: 100%" rows="8" id="address" name="address" class=""></textarea>';
		echo '</tr>';
		echo '<tr>
	<th style="text-align: left; width: auto">' . __( 'Type: ', 'amapress' ) . '</th>
	<td>
		<label for="type-co"><input id="type-co" name="type" value="co" type="radio" checked="checked" /> ' . __( 'Co-adhérent', 'amapress' ) . '</label>
		<label for="type-cof"><input id="type-cof" name="type" value="cof" type="radio" /> ' . __( 'Membre du foyer', 'amapress' ) . '</label>
	</td>
</tr>';
		echo '</table>';
		echo '<input style="min-width: 50%; margin-top: 0.5em" type="submit" class="button button-primary" value="' . esc_attr__( 'Ajouter le coadhérent', 'amapress' ) . '" />';
		echo '</form>';
	}
	echo '<hr />';
	echo '<p><a href="' . remove_query_arg( 'user_id' ) . '" class="button button-primary">' . __( 'Associer un autre amapien', 'amapress' ) . '</a></p>';
	echo '<script type="text/javascript">jQuery(function($) {
    $("#user_id").select2({
        allowClear: true,
		  escapeMarkup: function(markup) {
		return markup;
	},
		  templateResult: function(data) {
		return $("<span>"+data.text+"</span>");
	},
		  templateSelection: function(data) {
		return $("<span>"+data.text+"</span>");
	},
    });
    $("form#new_coadherent").validate({
                onkeyup: false,
        }
    );
});
</script>
<style type="text/css">
	.error {
		font-weight: bold;
		color: red;
	}
</style>';
}

add_action( 'wp_ajax_check_email_exists', function () {
	$email = $_POST['email'];
	$user  = get_user_by( 'email', $email );
	if ( ! $user ) {
		echo wp_json_encode( true );
	} else {
		echo wp_json_encode( __( 'Cette adresse est déjà utilisée', 'amapress' ) );
	}

	wp_die();
} );

add_action( 'delete_post', function ( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( AmapressAdhesion::INTERNAL_POST_TYPE == $post_type ) {
		$paiements_ids = get_posts(
			[
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_paiement_adhesion',
						'value'   => $post_id,
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				)
			]
		);
		foreach ( $paiements_ids as $id ) {
			wp_delete_post( $id, true );
		}
	}
}, 1000 );

add_filter( 'hidden_meta_boxes', function ( $hidden ) {
	return array_filter( $hidden,
		function ( $v ) {
			return ! preg_match( '/^\d+\/-/', $v );
		}
	);

	return $hidden;
} );

add_filter( 'amapress_can_edit_adhesion', function ( $can, $post_id ) {
	if ( is_admin() && amapress_can_access_admin() && ! amapress_is_admin_or_responsable() && ! TitanFrameworkOption::isOnNewScreen() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		if ( count( $refs ) > 0 ) {
			$adhesion = AmapressAdhesion::getBy( $post_id );
			if ( $adhesion ) {
				foreach ( $refs as $r ) {
					if ( in_array( $adhesion->getContrat_instanceId(), $r['contrat_instance_ids'] ) ) {
						return $can;
					}
				}
			}

			return false;
		}
	}

	return $can;
}, 10, 2 );

add_filter( 'amapress_gestion-contrats_page_contrat_paiements_default_hidden_columns', function ( $hidden ) {
	return array_merge( $hidden, [
		'amapress_user_no_renew',
		'amapress_user_no_renew_reason',
		'amapress_user_last_login',
		'amapress_user_adresse',
		'amapress_user_code_postal',
		'amapress_user_ville',
		'amapress_user_hidaddr',
		'amapress_user_telephone',
		'amapress_user_telephone2',
		'amapress_user_telephone3',
		'amapress_user_telephone4',
		'amapress_user_co-adherent-1',
		'amapress_user_co-adherent-2',
		'amapress_user_co-adherent-3',
		'amapress_user_co-foyer-1',
		'amapress_user_co-foyer-2',
		'amapress_user_co-foyer-3',
		'amapress_user_co-adherents',
		'amapress_user_co-adherents-infos',
	] );
} );

add_action( 'admin_menu', function () {
	add_submenu_page( null, __( 'Récapitulatif de commande', 'amapress' ),
		'', 'edit_adhesion', 'amps_inscr_details',
		function () {
			$contrat_id = null;
			if ( isset( $_GET['inscr_id'] ) ) {
				if ( ! current_user_can( 'edit_adhesion', $_GET['inscr_id'] ) ) {
					wp_die( __( 'Access denied', 'amapress' ) );
				}
				$adh = AmapressAdhesion::getBy( intval( $_GET['inscr_id'] ) );
				if ( empty( $adh ) ) {
					wp_die( __( 'Access denied', 'amapress' ) );
				}

				$grp_by_grp = isset( $_GET['grp_by_grp'] );
				echo amapress_get_details_all_deliveries( $adh->getAdherentId(),
					true, false,
					$adh->getContrat_instanceId(), $grp_by_grp );
			} else {
				wp_die( __( 'Invalid access', 'amapress' ) );
			}
		}
	);
} );
