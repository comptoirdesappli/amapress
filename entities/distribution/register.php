<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_distribution' );
function amapress_register_entities_distribution( $entities ) {
	$entities['distribution'] = array(
		'singular'                 => __( 'Distribution hebdomadaire', 'amapress' ),
		'plural'                   => __( 'Distributions hebdomadaires', 'amapress' ),
		'public'                   => true,
//                'logged_or_public' => true,
		'show_in_menu'             => false,
		'show_in_nav_menu'         => false,
		'comments'                 => ! defined( 'AMAPRESS_DISABLE_DISTRIB_COMMENTS' ),
		'public_comments'          => false,
		'approve_logged_comments'  => true,
		'editor'                   => false,
		'title'                    => false,
		'title_format'             => 'amapress_distribution_title_formatter',
		'slug_format'              => 'from_title',
		'slug'                     => __( 'distributions', 'amapress' ),
		'redirect_archive'         => 'amapress_redirect_agenda',
		'menu_icon'                => 'dashicons-store',
		'other_def_hidden_columns' => array( 'amps_lo', 'comments' ),
		'show_admin_bar_new'       => false,
		'row_actions'              => array(
			'emargement'             => [
				'label'  => __( 'Liste émargement', 'amapress' ),
				'target' => '_blank',
				'href'   => function ( $dist_id ) {
					return AmapressDistribution::getBy( $dist_id )->getListeEmargementHref();
				},
			],
			'quant_prod'             => [
				'label'  => __( 'Quantités producteurs', 'amapress' ),
				'target' => '_blank',
				'href'   => function ( $dist_id ) {
					return add_query_arg( 'date',
						date_i18n( 'Y-m-d', AmapressDistribution::getBy( $dist_id )->getDate() ),
						admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) );
				},
			],
			'mailto_resp'            => [
				'label'     => __( 'Email aux responsables', 'amapress' ),
				'target'    => '_blank',
				'confirm'   => true,
				'href'      => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return $dist->getMailtoResponsables();
				},
				'condition' => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return ! empty( $dist->getMailtoResponsables() );
				},
				'show_on'   => 'editor',
			],
			'mailto_amapiens'        => [
				'label'   => __( 'Email aux amapiens', 'amapress' ),
				'target'  => '_blank',
				'href'    => admin_url( 'admin.php?page=amapress_messages_page' ),
				'show_on' => 'editor',
			],
			'resend_liste_to_resp'   => [
				'label'   => __( 'Renvoyer la liste d\'émargement aux responsables', 'amapress' ),
				'show_on' => 'editor',
				'confirm' => true,
			],
			'resend_liste_to_verify' => [
				'label'   => __( 'Envoyer les infos de distribution à vérifier', 'amapress' ),
				'show_on' => 'editor',
				'confirm' => true,
			],
		),
		'edit_header'              => function ( $post ) {
			if ( TitanFrameworkOption::isOnNewScreen() ) {
				echo amapress_get_admin_notice(
					__( 'L\'ajout d\'une distribution ne doit se faire que si une distribution exceptionnelle a lieu en dehors des distributions plannifiées !', 'amapress' ),
					'warning', false
				);
			}
		},
		'labels'                   => array(
			'add_new'      => __( 'Planifier une distribution exceptionnelle', 'amapress' ),
			'add_new_item' => __( 'Nouvelle distribution exceptionnelle', 'amapress' ),
		),
		'views'                    => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_distribution_views',
		),
		'groups'                   => array(
			__( 'Infos', 'amapress' ) => [
				'context' => 'side',
			],
		),
		'default_orderby'          => 'amapress_distribution_date',
		'default_order'            => 'ASC',
		'fields'                   => array(
			'date' => array(
				'name'       => __( 'Date de distribution', 'amapress' ),
				'type'       => 'date',
				'time'       => false,
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => __( 'Toutes les dates', 'amapress' ),
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'group'      => __( 'Infos', 'amapress' ),
				'readonly'   => function ( $post_id ) {
					return ! TitanFrameworkOption::isOnNewScreen();
				},
				'desc'       => __( 'Date de distribution', 'amapress' ),
				'required'   => true,
			),
			'lieu' => array(
				'name'       => __( 'Lieu de distribution', 'amapress' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => __( 'Infos', 'amapress' ),
				'orderby'    => 'post_title',
				'order'      => 'ASC',
				'top_filter' => array(
					'name'        => 'amapress_lieu',
					'placeholder' => __( 'Toutes les lieux', 'amapress' ),
				),
				'readonly'   => function ( $post_id ) {
					return ! TitanFrameworkOption::isOnNewScreen();
				},
				'desc'       => __( 'Lieu de distribution', 'amapress' ),
				'searchable' => true,
				'required'   => true,
			),

			'lieu_substitution' => array(
				'name'       => __( 'Lieu de substitution', 'amapress' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => __( '1/ Partage', 'amapress' ),
				'desc'       => __( 'Lieu de substitution', 'amapress' ),
				'hidden'     => function ( $option ) {
					return count( Amapress::get_lieu_ids() ) <= 1;
				},
				'searchable' => true,
			),
			'heure_debut_spec'  => array(
				'name'  => __( 'Heure début', 'amapress' ),
				'type'  => 'date',
				'date'  => false,
				'time'  => true,
				'desc'  => __( 'Heure de début exceptionnelle', 'amapress' ),
				'group' => __( '1/ Partage', 'amapress' ),
			),
			'heure_fin_spec'    => array(
				'name'  => __( 'Heure fin', 'amapress' ),
				'type'  => 'date',
				'date'  => false,
				'time'  => true,
				'desc'  => __( 'Heure de fin exceptionnelle', 'amapress' ),
				'group' => __( '1/ Partage', 'amapress' ),
			),
			'contrats'          => array(
				'name'       => __( 'Contrats', 'amapress' ),
				'type'       => 'multicheck-posts',
				'post_type'  => 'amps_contrat_inst',
				'group'      => __( '1/ Partage', 'amapress' ),
				'readonly'   => true,
				'hidden'     => true,
				'desc'       => __( 'Contrats', 'amapress' ),
				'orderby'    => 'post_title',
				'order'      => 'ASC',
				'top_filter' => array(
					'name'        => 'amapress_contrat_inst',
					'placeholder' => __( 'Tous les contrats', 'amapress' )
				),
//                'searchable' => true,
			),
			'slots_conf'        => array(
				'name'  => __( 'Créneau(x)', 'amapress' ),
				'type'  => 'text',
				'desc'  => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$dist = AmapressDistribution::getBy( $option->getPostID() );
					if ( TitanFrameworkOption::isOnNewScreen() ) {
						$dist = null;
					}

					$ret = '';
					if ( $dist ) {
						$users_in_slots = count( $dist->getUserIdsWithAnySlot() );
						if ( $users_in_slots > 0 ) {
							$ret .= sprintf( __( '<p><strong style="color: red">%d inscriptions(s) en cours. Modifier la configuration risque d’impacter ces réservations.</strong></p>', 'amapress' ),
								$users_in_slots
							);
						}
					}

					$ret .= __( 'Configurer un créneau horaire de la forme : <em>Heure Début-Heure Fin[Durée du créneau en minutes;Nombre de personnes maximum]</em><br/>', 'amapress' );
					if ( $dist ) {
						$ret .= sprintf( __( 'Horaires de distribution configurés sur le site : %s à %s', 'amapress' ),
								date_i18n( 'H:i', $dist->getStartDateAndHour() ),
								date_i18n( 'H:i', $dist->getEndDateAndHour() )
						        ) . '<br/>';
						$ret .= sprintf( __( 'Plages des créneaux configurés : %s', 'amapress' ),
								$dist->getSlotsDescription()
						        ) . '<br/>';
					}

					$ret .= __( 'Paramétrages et documentation complète : ', 'amapress' ) . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/distribution#creneaux_horaires', __( 'Créneaux horaires', 'amapress' ) );

					return $ret;
				},
				'group' => __( '1/ Partage', 'amapress' ),
			),
			'paniers'           => array(
				'name'              => __( 'Panier(s)', 'amapress' ),
				'group'             => __( '1/ Partage', 'amapress' ),
				'desc'              => __( 'Panier(s) livré(s) à cette date', 'amapress' ),
				'show_column'       => false,
//				'bare'              => true,
				'include_columns'   => array(
					'title',
					'amapress_panier_contrat_instance',
					'amapress_panier_status',
					'amapress_panier_date_subst',
				),
				'datatable_options' => array(
					'ordering'  => false,
					'paging'    => false,
					'searching' => false,
				),
				'type'              => 'related-posts',
				'query'             => function ( $postID ) {
					$dist = AmapressDistribution::getBy( $postID );

					return array(
						'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'relation' => 'AND',
								array(
									'key'     => 'amapress_panier_contrat_instance',
									'value'   => amapress_prepare_in( $dist->getContratIds() ),
									'compare' => 'IN',
									'type'    => 'NUMERIC'
								),
								array(
									'relation' => 'OR',
									array(
										'key'     => 'amapress_panier_date',
										'value'   => $dist->getDate(),
										'compare' => '=',
										'type'    => 'NUMERIC'
									),
									array(
										array(
											'key'     => 'amapress_panier_status',
											'value'   => 'delayed',
											'compare' => '=',
										),
										array(
											'key'     => 'amapress_panier_date_subst',
											'value'   => $dist->getDate(),
											'compare' => '=',
											'type'    => 'NUMERIC'
										),
									)
								)
							)
						)
					);
				}
			),

			'nb_resp_supp' => array(
				'name'        => __( 'Nombre', 'amapress' ),
				'type'        => 'number',
				'required'    => true,
				'desc'        => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$dist = AmapressDistribution::getBy( $option->getPostID() );
					if ( TitanFrameworkOption::isOnNewScreen() ) {
						$dist = null;
					}

					$ret = __( 'Indiquer le nombre de responsable(s) de distribution <strong>supplémentaire(s)</strong>', 'amapress' );
					if ( $dist ) {
						if ( $dist->getLieuSubstitutionId() ) {
							$ret .= sprintf( __( '<br/>Le nombre de responsables de distribution exceptionnellement à %s est de %d.', 'amapress' ),
								Amapress::makeLink( $dist->getLieuSubstitution()->getAdminEditLink(), $dist->getLieu()->getTitle() ),
								$dist->getLieu()->getNb_responsables()
							);
						} else {
							$ret .= sprintf( __( '<br/>Le nombre de responsables de distribution à %s est de %d.', 'amapress' ),
								Amapress::makeLink( $dist->getLieu()->getAdminEditLink(), $dist->getLieu()->getTitle() ),
								$dist->getLieu()->getNb_responsables()
							);
						}
						$contrat_nb_responsables = [];
						foreach ( $dist->getContrats() as $contrat ) {
							if ( $contrat->getNb_responsables_Supplementaires() > 0 ) {
								$contrat_nb_responsables[] = sprintf( __( '%s (%d)', 'amapress' ),
									$contrat->getModelTitle(),
									$contrat->getNb_responsables_Supplementaires() );
							}
						}
						if ( ! empty( $contrat_nb_responsables ) ) {
							$ret .= sprintf( __( '<br/>Nombre de responsables pour les contrats spécifiques: %s.', 'amapress' ),
								implode( ', ', $contrat_nb_responsables )
							);
						}
						$ret .= sprintf( __( '<br/>Nb. total: %d', 'amapress' ),
							AmapressDistributions::get_required_responsables( $dist->ID )
						);
					}

					return $ret;
				},
				'group'       => __( '2/ Responsables', 'amapress' ),
				'default'     => 0,
				'show_column' => false,
			),
			'responsables' => array(
				'name'         => __( 'Responsables', 'amapress' ),
				'group'        => __( '2/ Responsables', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Naviguer pour ajouter des gardiens de panier et des responsables de distribution', 'amapress' ),
//				'before_option' => function ( $o ) {
//					if ( Amapress::hasRespDistribRoles() ) {
//						echo '<p style="color: orange">Lorsqu\'il existe des rôles de responsables de distribution, l\'inscription ne peut se faire que depuis la page d\'inscription par dates.</p>';
//					}
//				},
				'readonly'     => true,
				'after_option' => function ( $option ) {
					/** @var TitanFrameworkOption $option */

//					$href = Amapress::get_inscription_distrib_page_href();
//					if ( ! empty( $href ) ) {
//						echo '<p>Les inscriptions aux distributions se gèrent <a href="' . esc_attr( $href ) . '" target="_blank">ici</a></p>';
//					} else {
//						echo '<p style="color:red">Aucune page du site ne contient le shortcode [inscription-distrib] (qui permet de gérer l\'inscription aux distributions)</p>';
//					}
					$dist = AmapressDistribution::getBy( $option->getPostID() );
					echo '<div style="overflow-x: scroll">';
					echo amapress_inscription_distrib_shortcode(
						[
							'date'                     => $dist->getDate(),
							'show_for_resp'            => 'true',
							'show_title'               => 'false',
							'max_dates'                => 1,
							'lieu'                     => $dist->getLieuId(),
							'manage_all_subscriptions' => 'true',
						]
					);
					echo '</div>';
				},
//                'searchable' => true,
			),

			'gardiens' => array(
				'name'         => __( 'Gardien de panier(s)', 'amapress' ),
				'group'        => __( '2/ Responsables', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Amapiens volontaires pour garder des paniers d’amapiens indisponibles', 'amapress' ),
				'readonly'     => true,
			),

			'info' => array(
				'name'  => __( 'Informations spécifiques', 'amapress' ),
				'type'  => 'editor',
				'group' => __( '3/ Informations', 'amapress' ),
				'desc'  => __( 'Informations complémentaires', 'amapress' ),
			),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_distribution', 'amapress_can_delete_distribution', 10, 2 );
function amapress_can_delete_distribution( $can, $post_id ) {
	return false;
}

function amapress_get_active_contrat_month_options( $args ) {
	$months    = array();
	$min_month = amapress_time();
	$max_month = amapress_time();
	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
		$min_month = $contrat->getDate_debut() < $min_month ? $contrat->getDate_debut() : $min_month;
		$max_month = $contrat->getDate_fin() > $max_month ? $contrat->getDate_fin() : $max_month;
	}
	$min_month = Amapress::start_of_month( $min_month );
	$max_month = Amapress::end_of_month( $max_month );
	$month     = $min_month;
	while ( $month <= $max_month ) {
		$months[ date_i18n( 'Y-m', $month ) ] = date_i18n( 'F Y', $month );
		$month                                = Amapress::add_a_month( $month );
	}

	return $months;
}

function amapress_distribution_responsable_roles_options() {
	$ret   = [];
	$lieux = Amapress::get_lieux();
	$lieux = array_filter( $lieux, function ( $l ) {
		/** @var AmapressLieu_distribution $l */
		return $l->isPrincipal();
	} );
	if ( count( $lieux ) > 1 ) {
		$ret[] = array(
			'type' => 'heading',
			'name' => __( 'Rôles des responsables de distribution - pour tous les lieux', 'amapress' ),
		);
	}
	for ( $i = 1; $i <= 10; $i ++ ) {
		$ret[] = array(
			'id'   => "resp_role_$i-name",
			'name' => sprintf( __( 'Nom du rôle %d', 'amapress' ), $i ),
			'type' => 'text',
			'desc' => __( 'Nom du rôle de responsable de distribution', 'amapress' ),
		);
		$ret[] = array(
			'id'   => "resp_role_$i-desc",
			'name' => sprintf( __( 'Description du rôle %d', 'amapress' ), $i ),
			'type' => 'editor',
			'desc' => __( 'Description du rôle de responsable de distribution', 'amapress' ),
		);
		$ret[] = array(
			'id'           => "resp_role_$i-contrats",
			'name'         => sprintf( __( "Contrat(s) (rôle %d)", 'amapress' ), $i ),
			'type'         => 'select-posts',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'post_type'    => AmapressContrat::INTERNAL_POST_TYPE,
			'desc'         => __( 'Activer ce rôle uniquement lors de la distribution de certains contrats', 'amapress' ),
		);
		$ret[] = array(
			'type' => 'save',
		);
	}

	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret[]   = array(
				'type' => 'heading',
				'name' => sprintf( __( 'Rôles des responsables de distribution - pour %s', 'amapress' ), $lieu->getTitle() ),
			);
			$lieu_id = $lieu->ID;
			for ( $i = 1; $i <= 10; $i ++ ) {
				$ret[] = array(
					'id'   => "resp_role_{$lieu_id}_$i-name",
					'name' => sprintf( __( 'Nom du rôle %d', 'amapress' ), $i ),
					'type' => 'text',
					'desc' => __( 'Nom du rôle de responsable de distribution', 'amapress' ),
				);
				$ret[] = array(
					'id'   => "resp_role_{$lieu_id}_$i-desc",
					'name' => sprintf( __( 'Description du rôle %d', 'amapress' ), $i ),
					'type' => 'editor',
					'desc' => __( 'Description du rôle de responsable de distribution', 'amapress' ),
				);
				$ret[] = array(
					'id'           => "resp_role_{$lieu_id}_$i-contrats",
					'name'         => sprintf( __( 'Contrat(s) (rôle %d)', 'amapress' ), $i ),
					'type'         => 'select-posts',
					'autocomplete' => true,
					'multiple'     => true,
					'tags'         => true,
					'post_type'    => AmapressContrat::INTERNAL_POST_TYPE,
					'desc'         => __( 'Activer ce rôle uniquement lors de la distribution de certains contrats', 'amapress' ),
				);
				$ret[] = array(
					'type' => 'save',
				);
			}
		}
	}

	return $ret;
}

add_action( 'amapress_row_action_distribution_resend_liste_to_resp', 'amapress_row_action_distribution_resend_liste_to_resp' );
function amapress_row_action_distribution_resend_liste_to_resp( $post_id ) {
	do_action( 'amapress_recall_resp_distrib', [
		'id' => $post_id
	] );
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_distribution_resend_liste_to_verify', 'amapress_row_action_distribution_resend_liste_to_verify' );
function amapress_row_action_distribution_resend_liste_to_verify( $post_id ) {
	do_action( 'amapress_recall_verify_distrib', [
		'id' => $post_id
	] );
	wp_redirect_and_exit( wp_get_referer() );
}

function amapress_distribution_hours_setter() {
	$start_date = empty( $_POST['start_date'] ) ? date_i18n( 'd/m/Y', amapress_time() ) : $_POST['start_date'];
	$end_date   = empty( $_POST['end_date'] ) ? date_i18n( 'd/m/Y', Amapress::add_a_month( amapress_time(), 12 ) ) : $_POST['end_date'];
	$lieu_id    = empty( $_POST['lieu'] ) ? null : $_POST['lieu'];
	$incr_date  = empty( $_POST['incr_date'] ) ? 2 : $_POST['incr_date'];
	$start_hour = empty( $_POST['start_hour'] ) ? null : $_POST['start_hour'];
	$end_hour   = empty( $_POST['end_hour'] ) ? null : $_POST['end_hour'];

	$lieux_options = [];
	foreach ( Amapress::get_lieux() as $lieu ) {
		$lieux_options[ strval( $lieu->ID ) ] = $lieu->getTitle();
	}
	?>
    <h4><?php _e( 'Cet outil permet de définir les horaires alternatifs des distributions.', 'amapress' ) ?></h4>

	<?php

	if ( isset( $_POST['set_hours'] ) || isset( $_POST['reset_hours'] ) ) {
		$set = isset( $_POST['set_hours'] );
		if ( ! empty( $_POST['dist'] ) ) {
			global $wpdb;
			$wpdb->query( 'START TRANSACTION' );

			foreach ( $_POST['dist'] as $k => $v ) {
				$dist_id   = intval( $v );
				$dist_post = AmapressDistribution::getBy( $dist_id, true );
				if ( $set ) {
					if ( ! empty( $start_hour ) ) {
						$start_hour_date = DateTime::createFromFormat( 'd#m#Y H:i', date_i18n( 'd/m/Y', $dist_post->getDate() ) . ' ' . trim( $start_hour ) );
						if ( $start_hour_date ) {
							$dist_post->setSpecialHeure_debut( $start_hour_date->getTimestamp() );
						}
					}
					if ( ! empty( $end_hour ) ) {
						$end_hour_date = DateTime::createFromFormat( 'd#m#Y H:i', date_i18n( 'd/m/Y', $dist_post->getDate() ) . ' ' . trim( $end_date ) );
						if ( $end_hour_date ) {
							$dist_post->setSpecialHeure_fin( $end_hour_date->getTimestamp() );
						}
					}
				} else {
					$dist_post->setSpecialHeure_debut( null );
					$dist_post->setSpecialHeure_fin( null );
				}
				amapress_compute_post_slug_and_title( $dist_post->getPost() );
			}
			$wpdb->query( 'COMMIT' );
			AmapressDistribution::clearCache();
		}
	}

	?>
    <label for="start_date" class="tf-date"><?php _e( 'Date de début :', 'amapress' ) ?><input type="text"
                                                                                               id="start_date"
                                                                                               name="start_date"
                                                                                               class="input-date date required"
                                                                                               value="<?php echo esc_attr( $start_date ); ?>"/></label>
    <br/>
    <label for="end_date" class="tf-date"><?php _e( 'Date de fin :', 'amapress' ) ?><input type="text" id="end_date"
                                                                                           name="end_date"
                                                                                           class="input-date date required"
                                                                                           value="<?php echo esc_attr( $end_date ); ?>"/></label>
    <br/>
    <label for="lieu"><?php _e( 'Lieu:', 'amapress' ) ?></label><select id="lieu"
                                                                        name="lieu"
                                                                        class="required"><?php tf_parse_select_options( $lieux_options, $lieu_id ); ?></select>
    <br/>
    <label for="incr_date"><?php _e( 'Prendre une distribution toute les ', 'amapress' ) ?></label><input type="text"
                                                                                                          id="incr_date"
                                                                                                          name="incr_date"
                                                                                                          class="number required"
                                                                                                          value="<?php echo esc_attr( $incr_date ); ?>"/><?php _e( ' dates', 'amapress' ) ?>
    <br/>
    <input type="submit" class="button button-primary" name="select_dists"
           value="<?php echo esc_attr__( 'Afficher les distributions', 'amapress' ) ?>"/>
    <br/>
    <label for="start_hour" class="tf-date"><?php _e( 'Heure de début : ', 'amapress' ) ?><input type="text"
                                                                                                 id="start_hour"
                                                                                                 name="start_hour"
                                                                                                 class="input-date time"
                                                                                                 value="<?php echo esc_attr( $start_hour ); ?>"/></label>
    <br/>
    <label for="end_hour" class="tf-date"><?php _e( 'Heure de fin : ', 'amapress' ) ?><input type="text" id="end_hour"
                                                                                             name="end_hour"
                                                                                             class="input-date time"
                                                                                             value="<?php echo esc_attr( $end_hour ); ?>"/></label>
    <br/>
    <input type="submit" class="button button-primary" name="set_hours"
           value="<?php echo esc_attr__( 'Définir', 'amapress' ) ?>"/>
    <input type="submit" class="button button-secondary" name="reset_hours"
           value="<?php echo esc_attr__( 'Effacer', 'amapress' ) ?>"/>
	<?php

	$distributions        = AmapressDistribution::get_distributions( TitanEntity::to_date( $start_date ), TitanEntity::to_date( $end_date ), 'ASC' );
	$distributions        = array_filter( $distributions, function ( $d ) use ( $lieu_id ) {
		/** @var AmapressDistribution $d */
		return $d->getLieuId() == $lieu_id;
	} );
	$filter_distributions = [];
	$i                    = 0;
	echo '<ul>';
	foreach ( $distributions as $d ) {
		$hours = ' (' . date_i18n( 'H:i', $d->getStartDateAndHour() ) . ' à ' . date_i18n( 'H:i', $d->getEndDateAndHour() ) . ')';
		if ( ( $i % $incr_date ) == 0 ) {
			$filter_distributions[] = $d;
			echo '<li><input type="checkbox" class="checkbox" id="dist_' . $d->ID . '" name="dist[' . $d->ID . ']" value="' . $d->ID . '" ' . checked( isset( $_POST['dist'][ $d->ID ] ) || isset( $_POST['select_dists'] ), true, false ) . ' /><label for="dist_' . $d->ID . '">' . esc_html( $d->getTitle() ) . $hours . ' (' . Amapress::makeLink( $d->getAdminEditLink(), __( 'Voir', 'amapress' ), true, true ) . ')' . '</label></li>';
		} else {
			echo '<li style="text-decoration: line-through">' . esc_html( $d->getTitle() ) . $hours . ' (' . Amapress::makeLink( $d->getAdminEditLink(), __( 'Voir', 'amapress' ), true, true ) . ')' . '</li>';
		}
		$i += 1;
	}
	echo '</ul>';

	TitanFrameworkOptionDate::createCalendarScript();
}

function amapress_gardiens_paniers_map( $dist_id, $show_email = true, $show_tel = true, $show_address = false ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$dist = AmapressDistribution::getBy( $dist_id );

	$me_id = amapress_current_user_id();

	$markers = array();
	foreach ( $dist->getGardiens() as $user ) {
		if ( ! $user->isAdresse_localized() ) {
			continue;
		}
		$markers[] = array(
			'longitude' => $user->getUserLongitude(),
			'latitude'  => $user->getUserLatitude(),
			'url'       => ( $show_email ? 'mailto:' . $user->getEmail() : null ),
			'title'     => $user->getDisplayName(),
			'icon'      => ( $user->ID == $me_id ? 'man' : 'green' ),
			'content'   => $user->getDisplay( [
				'show_email'      => $show_email,
				'show_tel'        => $show_tel,
				'show_tel_fixe'   => $show_tel,
				'show_tel_mobile' => $show_tel,
				'show_adresse'    => $show_address,
				'show_avatar'     => 'default',
			] ),
		);
	}

	if ( empty( $markers ) ) {
		return '';
	}

	return amapress_generate_map( $markers, 'map' );
}
