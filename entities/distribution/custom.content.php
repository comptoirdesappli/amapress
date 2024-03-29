<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_get_custom_content_distribution', 'amapress_get_custom_content_distribution' );
function amapress_get_custom_content_distribution( $content ) {
	$dist_id = get_the_ID();
	$dist    = AmapressDistribution::getBy( $dist_id );

	$dist_date     = intval( get_post_meta( $dist_id, 'amapress_distribution_date', true ) );
	$dist_contrats = $dist->getContratIds();
	$user_contrats = AmapressContrat_instance::getContratInstanceIdsForUser();
	$user_contrats = array_filter( $user_contrats, function ( $cid ) use ( $dist_contrats ) {
		return in_array( $cid, $dist_contrats );
	} );

	$resp_ids = $dist->getResponsablesIds();
	if ( $resp_ids && count( $resp_ids ) > 0 ) {
		$responsables = get_users( array( 'include' => array_map( 'intval', $resp_ids ) ) );
	} else {
		$responsables = array();
	}
//    $responsables_names=array_map(array(__('AmapressUsers', 'amapress'),'to_displayname'),$responsables);
	$needed_responsables = AmapressDistributions::get_required_responsables( $dist_id );
	$lieu_id             = get_post_meta( $dist_id, 'amapress_distribution_lieu', true );
	$lieu_subst_id       = get_post_meta( $dist_id, 'amapress_distribution_lieu_substitution', true );
	$lieu                = $lieu_subst_id ? get_post( $lieu_subst_id ) : get_post( $lieu_id );
	if ( ! $lieu && $lieu_subst_id ) {
		$lieu_subst_id = null;
		$lieu          = get_post( $lieu_id );
	}

	$is_resp         = AmapressDistributions::isCurrentUserResponsable( $dist_id );
	$is_resp_amap    = amapress_current_user_can( 'administrator' ) || amapress_current_user_can( 'responsable_amap' );
	$can_unsubscribe = Amapress::start_of_week( $dist_date ) < Amapress::start_of_week( amapress_time() );

	$need_responsables = false;

	ob_start();

	if ( amapress_is_user_logged_in() && ! empty( $dist->getContratIds() ) ) {
		$can_subscribe = $dist->canSubscribe();
		amapress_echo_panel_start( __( 'Responsables de distributions', 'amapress' ), 'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' amap-panel-resp-dist' );
		if ( count( $resp_ids ) > 0 ) {
			$render_func = 'user_cell';
			if ( amapress_can_access_admin() ||
			     ( Amapress::start_of_week( Amapress::add_a_week( amapress_time(), - 1 ) ) <= $dist_date
			       && $dist_date <= Amapress::end_of_week( amapress_time() ) ) ) {
				$render_func = 'user_cell_contact';

			}
			echo '<div>' . amapress_generic_gallery( $responsables, $render_func, [
					'if_empty' => __( 'Pas de responsables', 'amapress' )
				], $dist ) . '</div><br style="clear:both" />';
		} else { ?>
            <p class="dist-no-resp"><?php _e( 'Aucun responsable', 'amapress' ) ?></p>
		<?php } ?>
		<?php
		if ( count( $resp_ids ) < $needed_responsables ) {
			if ( Amapress::start_of_day( $dist->getDate() ) >= Amapress::start_of_day( amapress_time() ) ) {
				$missing_format = __( 'Il manque encore %d responsable(s) de distributions.', 'amapress' );
			} else {
				$missing_format = __( 'Il manquait %d responsable(s) de distributions.', 'amapress' );
			}
			echo '<p class="dist-miss-resp">' . esc_html( sprintf( $missing_format, $needed_responsables - count( $resp_ids ) ) ) . '</p>';
			$need_responsables = true;
			$href              = Amapress::get_inscription_distrib_page_href( $dist->getLieu() );
			if ( ! empty( $href ) ) {
				if ( $is_resp_amap ) {
					echo '<p>' . sprintf( __( 'Les inscriptions aux distributions des amapiens se gèrent <a href="%s" target="_blank">ici</a>', 'amapress' ), esc_attr( $href ) ) . '</p>';
				}
				if ( $is_resp ) {
					echo '<p>' . Amapress::makeLink( $href, __( 'Vous êtes responsable de distribution', 'amapress' ), true, true ) . '</p>';
				} else {
					echo '<p>' . Amapress::makeButtonLink( $href, ! empty( $dist->getSlotsConf() ) ? __( 'Inscriptions et créneaux horaires', 'amapress' ) : __( 'Inscriptions', 'amapress' ), true, true ) . '</p>';
				}

			}
			?>
			<?php
		}
		amapress_echo_panel_end();
	}

	$panel_resp = ob_get_clean();

//    var_dump($panel_resp);

	ob_start();

//    amapress_handle_action_messages();

	$btns = [];
	if ( amapress_is_user_logged_in() ) {
		if ( amapress_can_access_admin() || AmapressDistributions::isCurrentUserResponsableThisWeek() ) {
			$btns[] = amapress_get_button( __( 'Liste d\'émargement', 'amapress' ),
				amapress_action_link( $dist_id, 'liste-emargement' ), 'fa-fa',
				true, null, 'btn-print-liste' );
		}
	}
	if ( $is_resp_amap || current_user_can( 'edit_distrib' ) ) {
		$btns[] = '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '#amapress_distribution_nb_resp_supp" class="btn btn-default">' . __( 'Ajouter des responsables supplémentaires', 'amapress' ) . '</a>';
		$btns[] = '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '" class="btn btn-default">' . __( 'Editer la distribution (infos, horaires, créneaux...)', 'amapress' ) . '</a>';
	}
	if ( $is_resp_amap || current_user_can( 'edit_contrat_instance' ) ) {
		$btns[] = '<a href="' . esc_attr( admin_url( 'admin.php?page=contrats_quantites_next_distrib&date=' . date_i18n( 'Y-m-d', $dist->getDate() ) ) )
		          . '" class="btn btn-default">' . __( 'Quantités au producteur', 'amapress' ) . '</a>';
	}
	if ( $is_resp_amap || current_user_can( 'edit_distrib' ) ) {
		$mailto = $dist->getMailtoResponsables();
		if ( ! empty( $mailto ) ) {
			$btns[] = '<a href="' . $mailto . '" class="btn btn-default">' . __( 'Email aux responsables', 'amapress' ) . '</a>';
		}

		$btns[] = '<a target="_blank" href="' . admin_url( 'admin.php?page=amapress_messages_page' ) . '" class="btn btn-default">' . __( 'Email aux amapiens', 'amapress' ) . '</a>';
	}
	?>

    <div class="distribution">
        <div class="btns">
			<?php echo implode( '', $btns ) ?>
        </div>
		<?php

		if ( empty( $dist->getContratIds() ) ) {
			echo '<p class="dist-cancelled" style="font-weight: bold;color: #FF0000; text-align: center">' . __( 'Distribution annulée ou reportée', 'amapress' ) . '</p>';
		}

		$current_user_slot = $dist->getSlotInfoForUser( amapress_current_user_id() );
		if ( $current_user_slot ) {
			echo '<p class="amapien-has-creneau" style="text-align: center;color: orange">' . __( 'Vous avez le créneau ', 'amapress' ) . esc_html( $current_user_slot['display'] ) . '</p>';
		}

		if ( $need_responsables ) {
			echo $panel_resp;
		}

		if ( amapress_is_user_logged_in() && Amapress::getOption( 'enable-gardiens-paniers' ) ) {
			amapress_echo_panel_start_no_esc( '<a id="panel_gardiens_paniers"></a>' . __( 'Gardiens de paniers', 'amapress' ) );
			if ( empty( $user_contrats ) ) {
				echo '<p><strong>' . __( 'Vous n\'avez pas de contrats à cette distribution', 'amapress' ) . '</strong></p>';
			}
			echo amapress_gardiens_paniers_map( $dist_id );
			$amapiens_map_link = Amapress::get_page_with_shortcode_href( 'amapiens-map', 'amps_amapiens_map_href' );
			if ( ! empty( $amapiens_map_link ) ) {
				echo '<p>' . Amapress::makeLink( $amapiens_map_link, __( 'Voir la carte complète des amapiens', 'amapress' ), true, true ) . '</p>';
			}
			if ( in_array( amapress_current_user_id(), $dist->getGardiensIds( false ) ) ) {
				echo '<p style="font-weight: bold; margin-top: 1em">' . __( 'Vous êtes inscrit Gardien de paniers', 'amapress' ) . '</p>';
			}
			$current_amapien = AmapressUser::getBy( amapress_current_user_id() );
			$gardien_id      = $dist->getPanierGardienId( amapress_current_user_id() );
			if ( ! empty( $gardien_id ) ) {
				$gardien_comment = $dist->getGardienComment( $gardien_id );
				if ( ! empty( $gardien_comment ) ) {
					$gardien_comment = '<br /><em>' . esc_html( $gardien_comment ) . '</em>';
				}
				$gardien = AmapressUser::getBy( $gardien_id );
				echo '<p style="font-weight: bold; margin-top: 1em">';
				echo sprintf( __( 'Votre/vos panier(s) seront gardés par %s(%s)', 'amapress' ), $gardien->getDisplayName(), $gardien->getContacts( wp_is_mobile() ) );
				echo '<em>' . $gardien_comment . '</em></p>';

			}
			$gardien_amapien_ids = $dist->getGardiensPaniersAmapiensIds( amapress_current_user_id() );
			if ( ! empty( $gardien_amapien_ids ) ) {
				echo '<p>' . __( 'Vous gardez les paniers de : ', 'amapress' ) .
				     implode( ', ', array_map( function ( $uid ) {
					     $u = AmapressUser::getBy( $uid );

					     return sprintf( __( '%s (%s)', 'amapress' ), $u->getDisplayName(), $u->getContacts( wp_is_mobile() ) );
				     }, $gardien_amapien_ids ) )
				     . '</p>';
			}
			echo wp_unslash( Amapress::getOption( 'gardiens-paniers-message' ) );
			$columns = array(
				array(
					'title' => '',
					'data'  => 'link',
				),
				array(
					'title' => __( 'Nom', 'amapress' ),
					'data'  => 'name'
				),
				array(
					'title' => __( 'Contacts', 'amapress' ),
					'data'  => 'contacts'
				),
				array(
					'title' => __( 'Infos', 'amapress' ),
					'data'  => 'infos'
				),
			);

			$dist_gardiens = $dist->getGardiens();
			if ( ! empty( $gardien_id ) && ! in_array( $gardien_id, $dist->getGardiensIds() ) ) {
				$dist_gardiens[] = AmapressUser::getBy( $gardien_id );
			}
			$data = array_map( function ( $u ) use ( $current_amapien, $gardien_id, $dist, $can_subscribe, $user_contrats ) {
				$link = '';
				if ( $can_subscribe && ! empty( $user_contrats ) ) {
					if ( empty( $gardien_id ) && $u->ID != amapress_current_user_id() ) {
						$link = '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="inscrire_garde" data-confirm="' . esc_attr__( 'Avez-vous pris contact avec ce gardien de paniers et souhaitez-vous vraiment lui confier la garde de votre panier ?', 'amapress' ) . '"
					data-dist="' . $dist->ID . '" data-gardien="' . $u->ID . '" data-user="' . amapress_current_user_id() . '">' . __( 'Confier la garde', 'amapress' ) . '</button>';
					} elseif ( $u->ID == $gardien_id ) {
						$link = '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="desinscrire_garde" data-confirm="' . esc_attr__( 'Avez-vous pris contact avec ce gardien de paniers et souhaitez-vous vraiment lui retirer la garde de votre panier ?', 'amapress' ) . '"
					data-dist="' . $dist->ID . '" data-gardien="' . $u->ID . '" data-user="' . amapress_current_user_id() . '">' . __( 'Retirer la garde', 'amapress' ) . '</button>';
					}
				}

				/** @var AmapressUser $u */
				$gardien_comment = $dist->getGardienComment( $u->ID );
				if ( ! empty( $gardien_comment ) ) {
					$gardien_comment = esc_html( $gardien_comment ) . '<br />';
				}

				return array(
					'link'     => $link,
					'name'     => esc_html( $u->getDisplayName() ),
					'contacts' => $u->getContacts( false ),
					'infos'    => $gardien_comment . ( $u->ID != amapress_current_user_id() ? esc_html(
							! $u->isAdresse_localized() ?
								'amapien non localisé' :
								( $current_amapien->isAdresse_localized() ?
									sprintf( __( 'à %s (à vol d\'oiseau)', 'amapress' ),
										AmapressUsers::distanceFormatMeter(
											$current_amapien->getUserLatitude(),
											$current_amapien->getUserLongitude(),
											$u->getUserLatitude(),
											$u->getUserLongitude() ) )
									: __( 'Vous n\'êtes pas localisé', 'amapress' )
								) ) : '' )
				);
			}, $dist_gardiens );

			amapress_echo_datatable( 'all-gardiens', $columns, $data,
				array(
					'searching'    => true,
					'nowrap'       => false,
					'responsive'   => false,
					'init_as_html' => 'true'
				) );

			if ( ! in_array( amapress_current_user_id(), $dist->getGardiensIds() ) ) {
				$inscription_href = Amapress::get_inscription_distrib_page_href( $dist->getLieu() );
				if ( ! empty( $inscription_href ) ) {
					echo '<p>' . Amapress::makeButtonLink(
							$inscription_href, __( 'Se proposer comme gardien de panier', 'amapress' ),
							true, true
						) . '</p>';
				}
			}

			if ( Amapress::end_of_day( $dist_date ) > amapress_time() ) {
				//precache
				amapress_precache_all_users();

				if ( amapress_can_access_admin() ) {
					amapress_echo_panel_start( __( 'Pour les responsables AMAP', 'amapress' ) );
					$users = [ '' => '--Sélectionner un amapien--' ];

					foreach ( $dist->getMainAdherentsIds( true ) as $user_id ) {
						$user               = AmapressUser::getBy( $user_id );
						$users[ $user->ID ] = sprintf( __( '%s (%s)', 'amapress' ), $user->getDisplayName(), $user->getEmail() );
					}
					echo '<form><label for="other-amapien">' . __( 'Amapien demandeur', 'amapress' ) . '</label>';
					echo '<select name="other-amapien" id="other-amapien" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>';
					echo '<br/><label for="other-gardien">' . __( 'Gardien', 'amapress' ) . '</label>';
					echo '<select name="other-gardien" id="other-gardien" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>';
					echo '<br/><button type="button" class="btn btn-default amapress-ajax-button" 
					data-action="inscrire_garde" data-confirm="' . esc_attr__( 'Etes-vous sûr ?', 'amapress' ) . '"
					data-dist="' . $dist_id . '" data-gardien="val:#other-gardien" data-user="val:#other-amapien">' . __( 'Confier la garde', 'amapress' ) . '</button></form>';
					echo '<hr/>';

					$columns = array(
						array(
							'title' => '',
							'data'  => 'link',
						),
						array(
							'title' => __( 'Gardien', 'amapress' ),
							'data'  => 'gardien'
						),
						array(
							'title' => __( 'Demandeur', 'amapress' ),
							'data'  => 'amapien'
						),
					);

					$data = [];
					foreach ( $dist->getGardiens( true ) as $gardien ) {
						if ( ! $gardien ) {
							continue;
						}
						foreach ( $dist->getGardiensPaniersAmapiensIds( $gardien->ID ) as $amapien_id ) {
							$amapien = AmapressUser::getBy( $amapien_id );
							if ( ! $amapien ) {
								continue;
							}

							$gardien_comment = $dist->getGardienComment( $gardien->ID );
							if ( ! empty( $gardien_comment ) ) {
								$gardien_comment = '<br />' . esc_html( $gardien_comment );
							}

							$data[] = array(
								'link'    => '<button type="button" class="btn btn-default amapress-ajax-button" 
					data-action="desinscrire_garde" data-confirm="' . esc_attr__( 'Etes-vous sûr ?', 'amapress' ) . '"
					data-dist="' . $dist_id . '" data-gardien="' . $gardien->ID . '" data-user="' . $amapien->ID . '">' . __( 'Retirer la garde', 'amapress' ) . '</button>',
								'gardien' => sprintf( __( '%s (%s)', 'amapress' ), $gardien->getDisplayName(), $gardien->getContacts( false ) ) . $gardien_comment,
								'amapien' => sprintf( __( '%s (%s)', 'amapress' ), $amapien->getDisplayName(), $amapien->getContacts( false ) ),
							);
						}
					}

					amapress_echo_datatable( 'all-gardes', $columns, $data,
						array(
							'searching'    => true,
							'nowrap'       => false,
							'responsive'   => false,
							'init_as_html' => 'true'
						) );

					amapress_echo_panel_end();
				} elseif ( empty( $gardien_id ) && Amapress::toBool( Amapress::getOption( 'allow-affect-gardiens' ) ) ) {
					amapress_echo_panel_start( __( 'Affecter la garde de mon panier', 'amapress' ) );
					$users = [ '' => '--Sélectionner un amapien--' ];

					foreach ( $dist->getMainAdherentsIds( true ) as $user_id ) {
						$user               = AmapressUser::getBy( $user_id );
						$users[ $user->ID ] = sprintf( __( '%s (%s)', 'amapress' ), $user->getDisplayName(), $user->getEmail() );
					}
					echo '<form><label for="other-gardien">' . __( 'Gardien', 'amapress' ) . '</label>';
					echo '<select name="other-gardien" id="other-gardien" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>';
					echo '<br/><button type="button" class="btn btn-default amapress-ajax-button" 
					data-action="inscrire_garde" data-confirm="' . esc_attr__( 'Etes-vous sûr ?', 'amapress' ) . '"
					data-dist="' . $dist_id . '" data-gardien="val:#other-gardien" data-user="val:#other-amapien">' . __( 'Confier la garde', 'amapress' ) . '</button></form>';
					amapress_echo_panel_end();
				}
			}

			amapress_echo_panel_end();
		}

		$instructions = '';
		if ( $is_resp || $is_resp_amap ) {
			$add_text = '';
			if ( ! $is_resp_amap ) {
				$add_text = '<span class="resp-distribution">' . __( 'Vous êtes responsable de distribution', 'amapress' ) . '</span> - ';
			}

			$instructions .= amapress_get_panel_start_no_esc( $add_text . __( 'Instruction du lieu ', 'amapress' ),
				'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' ',
				'instructions-lieu' );
			if ( ! $is_resp_amap ) {
				if ( $can_unsubscribe ) {
					amapress_get_button( __( 'Se désinscrire', 'amapress' ), amapress_action_link( $dist_id, 'desinscrire' ), 'fa-fa', false, __( 'Confirmez-vous votre désinscription ?', 'amapress' ) );
				}
			}
			$instructions .= $dist->getLieu()->getInstructions_privee();
			if ( strpos( $dist->getLieu()->getInstructions_privee(), '[liste-emargement-button]' ) === false ) {
				$instructions .= '<br/>';
				$instructions .= amapress_get_button( __( 'Imprimer la liste d\'émargement', 'amapress' ),
					amapress_action_link( $dist_id, 'liste-emargement' ), 'fa-fa',
					true, null, 'btn-print-liste' );
			}
			$instructions .= amapress_get_panel_end();

			$paniers_instructions_distribution = $dist->getProperty( 'paniers_instructions_distribution' );
			if ( ! empty( $paniers_instructions_distribution ) ) {
				$instructions .= amapress_get_panel_start_no_esc( $add_text . __( 'Instructions de distribution', 'amapress' ),
					'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' ',
					'instructions-dist' );
				$instructions .= $paniers_instructions_distribution;
				$instructions .= amapress_get_panel_end();
			}

			if ( $is_resp ) {
				echo $instructions;
			}
		}

		$lieu_id = $lieu->ID;
		amapress_echo_panel_start( __( 'Lieu', 'amapress' ), 'fa-map-marker', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' amap-panel-dist-lieu amap-panel-dist-lieu-' . $lieu_id );

		echo '<div class="dist-lieu-photo"><img src="' . amapress_get_avatar_url( $lieu_id, null, 'produit-thumb', 'default_lieu.jpg' ) . '" alt="" /></div>';
		echo '<h3><a href="' . get_post_permalink( $lieu_id ) . '">' . ( $lieu_subst_id ? ' <strong>' . __( 'EXCEPTIONNELLEMENT à', 'amapress' ) . '</strong> ' : '' ) . $lieu->post_title . '</a></h3>' .
		     '<p class="dist-lieu-adresse">' . __( 'Adresse : ', 'amapress' ) . get_post_meta( $lieu_id, 'amapress_lieu_distribution_adresse', true ) . '<br />' .
		     get_post_meta( $lieu_id, 'amapress_lieu_distribution_code_postal', true ) . ' ' . get_post_meta( $lieu_id, 'amapress_lieu_distribution_ville', true ) .
		     '</p>' .
		     '<p class="dist-lieu-horaires">';
		echo sprintf( __( 'de %s à %s', 'amapress' ), date_i18n( 'H:i', get_post_meta( $lieu_id, 'amapress_lieu_distribution_heure_debut', true ) ), date_i18n( 'H:i', get_post_meta( $lieu_id, 'amapress_lieu_distribution_heure_fin', true ) ) );
		echo '</p>';
		amapress_echo_panel_end();

		if ( amapress_is_user_logged_in() ) {
			$info_html = $dist->getInformations();
			$info_text = trim( strip_tags( $info_html ) );
			if ( ! empty( $info_text ) ) {
				amapress_echo_panel_start( __( 'Informations spécifiques', 'amapress' ), 'fa-fa', 'amap-panel-dist amap-panel-dist-info' );
				echo $info_html;
				amapress_echo_panel_end();
			}
			amapress_display_messages_for_post( 'distribution-messages', $dist_id );
		}

		$query                        = array(
			'status' => 'to_exchange',
		);
		$query['contrat_instance_id'] = array_map( function ( $a ) {
			return $a->ID;
		}, $dist->getContrats() );
		$query['date']                = $dist->getDate();
		$adhs                         = AmapressPaniers::getPanierIntermittents( $query );
		if ( count( $adhs ) > 0 ) {
			amapress_echo_panel_start( __( 'Panier(s) intermittent(s)', 'amapress' ), 'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' ' );
			echo amapress_get_paniers_intermittents_exchange_table( $adhs );
			amapress_echo_panel_end();
		}

		//        amapress_echo_panel_start(__('Panier(s)', 'amapress'), 'fa-shopping-basket', 'amap-panel-dist amap-panel-dist-'.$lieu_id.' ');

		if ( amapress_is_user_logged_in() && Amapress::isIntermittenceEnabled() && ! empty( $user_contrats ) ) {
			amapress_echo_panel_start( __( 'En cas d\'absence - Espace intermittents', 'amapress' ) );
			$paniers       = AmapressPaniers::getPaniersForDist( $dist->getDate() );
			$ceder_title   = count( $user_contrats ) > 1 ? sprintf( __( 'Céder mes %s paniers', 'amapress' ), count( $user_contrats ) ) : __( 'Céder mon panier', 'amapress' );
			$can_subscribe = Amapress::start_of_day( $dist->getDate() ) >= Amapress::start_of_day( amapress_time() );

			$is_intermittent = 'exchangeable';
			foreach ( $paniers as $panier ) {
				if ( ! in_array( $panier->getContrat_instanceId(), $user_contrats ) ) {
					continue;
				}
				$status = AmapressPaniers::isIntermittent( $panier->ID, $dist->getLieuId() );
				if ( ! empty( $status ) ) {
					$is_intermittent = $status;
				}
			}

			switch ( $is_intermittent ) {
				case 'exchangeable':
					if ( $can_subscribe ) {
						$id = "info_{$dist->ID}";
						echo '<div class="echange-panier-info amapress-ajax-parent"><h4 class="echange-panier-info-title">' . __( 'Informations', 'amapress' ) . '</h4><textarea id="' . $id . '"></textarea><br/>';
						echo '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="echanger_panier" data-message="val:#' . $id . '" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir céder votre/vos paniers ?', 'amapress' ) . '"
					data-dist="' . $dist->ID . '" data-user="' . amapress_current_user_id() . '">' . $ceder_title . '</button></div>';
					} else {
						echo '<span class="echange-closed">' . __( 'Cessions de paniers closes', 'amapress' ) . '</span>';
					}
					break;
				case 'to_exchange':
					echo '<span class="panier-to-exchange">' . __( 'Panier(s) en attente de repreneur', 'amapress' ) . '</span>';
					break;
				case 'exchanged':
					echo '<span class="panier-exchanged">' . __( 'Panier(s) cédé(s)', 'amapress' ) . '</span>';
					break;
				case 'exch_valid_wait':
					echo '<span class="panier-exchanged">' . __( 'Panier(s) en attente de validation de reprise', 'amapress' ) . '</span>';
					break;
				case 'closed':
					echo '<span class="echange-done">' . __( 'Cession effectuée', 'amapress' ) . '</span>';
					break;
			}
			if ( Amapress::getOption( 'allow_partial_exchange' ) && $can_subscribe && count( $user_contrats ) > 1 ) {
				echo '<div id="inter_partial_exchanges">';
				foreach ( $paniers as $panier ) {
					echo '<div>';
					$is_intermittent = 'exchangeable';
					if ( ! in_array( $panier->getContrat_instanceId(), $user_contrats ) ) {
						continue;
					}
					$status = AmapressPaniers::isIntermittent( $panier->ID, $dist->getLieuId() );
					if ( ! empty( $status ) ) {
						$is_intermittent = $status;
					}

					$panier_title       = $panier->getContrat_instance()->getModel()->getTitle();
					$ceder_panier_title = sprintf( __( 'Céder mon panier "%s"', 'amapress' ), $panier_title );
					switch ( $is_intermittent ) {
						case 'exchangeable':
							if ( $can_subscribe ) {
								$id = "info_{$panier->ID}_{$dist->ID}";
								echo '<div class="echange-panier-info amapress-ajax-parent"><h4 class="echange-panier-info-title">' . __( 'Informations', 'amapress' ) . '</h4><textarea id="' . $id . '"></textarea><br/>';
								echo '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="echanger_panier" data-message="val:#' . $id . '" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir céder votre/vos paniers ?', 'amapress' ) . '"
					data-dist="' . $dist->ID . '" data-panier="' . $panier->ID . '" data-user="' . amapress_current_user_id() . '">' . $ceder_panier_title . '</button></div>';
							} else {
								echo '<span class="echange-closed">' . __( 'Cessions de paniers closes', 'amapress' ) . '</span>';
							}
							break;
						case 'to_exchange':
							echo '<span class="panier-to-exchange">';
							echo sprintf( __( 'Panier "%s" en attente de repreneur', 'amapress' ), $panier_title );
							echo '</span>';
							break;
						case 'exchanged':
							echo '<span class="panier-exchanged">';
							echo sprintf( __( 'Panier "%s" cédé', 'amapress' ), $panier_title );
							echo '</span>';
							break;
						case 'exch_valid_wait':
							echo '<span class="panier-exchanged">';
							echo sprintf( __( 'Panier "%s" en attente de validation de reprise', 'amapress' ), $panier_title );
							echo '</span>';
							break;
						case 'closed':
							echo '<span class="echange-done">';
							echo sprintf( __( 'Cession panier "%s" effectuée', 'amapress' ), $panier_title );
							echo '</span>';
							break;
					}
					echo '</div>';
				}
				echo '</div>';
			}
			amapress_echo_panel_end();
		}


		$has_contrats = false;
		foreach ( $dist_contrats as $contrat_id ) {
			if ( ! amapress_is_user_logged_in() || in_array( intval( $contrat_id ), $user_contrats ) ) {
				$contrat_instance = AmapressContrat_instance::getBy( $contrat_id );
				if ( null == $contrat_instance ) {
					continue;
				}
				$contrat_model = $contrat_instance->getModel();
				if ( null == $contrat_model ) {
					continue;
				}
				$panier = AmapressPaniers::getPanierForDist( $dist_date, $contrat_id );
				if ( null == $panier ) {
					continue;
				}

				$icon = Amapress::coalesce_icons( amapress_get_avatar_url( $contrat_id, null, 'produit-thumb', null ), Amapress::getOption( "contrat_{$contrat_model->ID}_icon" ), amapress_get_avatar_url( $contrat_model->ID, null, 'produit-thumb', 'default_contrat.jpg' ) );
				if ( ! empty( $icon ) && false !== strpos( $icon, '://' ) ) {
					$icon = '<img src="' . esc_attr( $icon ) . '" class="dist-panier-contrat-img" alt="' . esc_attr( $contrat_model->getTitle() ) . '" />';
				}

				$panier_btns = '';
				if ( $is_resp_amap || current_user_can( 'edit_panier' ) ) {
					$panier_btns = '<a href="' . esc_attr( $panier->getAdminEditLink() ) . '" class="btn btn-default">' . __( 'Editer le contenu/Déplacer', 'amapress' ) . '</a>';
				}
				amapress_echo_panel_start_no_esc( Amapress::makeLink( $contrat_model->getPermalink(), $contrat_instance->getProperty( 'contrat_type_complet' ), true, true ) . $panier_btns, $icon,
					'amap-panel-dist amap-panel-dist-' . $lieu_id . ' amap-panel-dist-panier amap-panel-dist-panier-' . $contrat_model->ID );

				$adhesions = AmapressAdhesion::getUserActiveAdhesions(
					amapress_current_user_id(), $contrat_id, $dist_date
				);
				if ( ! empty( $adhesions ) ) {
					$adhesion   = $adhesions[0];
					$coadh_info = '';
					$coadh_id   = $adhesion->getCoadhIdFromShareCalendarDate( $dist_date );
					if ( $coadh_id ) {
						$coadh = AmapressUser::getBy( $coadh_id );
						if ( ! empty( $coadh ) ) {
							if ( $coadh_id == amapress_current_user_id() ) {
								$coadh_info = __( 'A votre tour', 'amapress' );
							} else {
								$coadh_info = sprintf( __( 'Au tour de %s', 'amapress' ), $coadh->getDisplayName() );
							}
						}
					}

					if ( ! empty( $coadh_info ) ) {
						echo '<p><strong>' . __( 'Partage de panier : ', 'amapress' ) . $coadh_info . '</strong></p>';
					}
				}

				echo AmapressPaniers::getPanierContentHtml( $panier->ID, $lieu_id );
				amapress_echo_panel_end();

				$has_contrats = true;
			}
		}
		if ( ! $has_contrats ) {
			amapress_echo_panel_start( __( 'Panier(s)', 'amapress' ), 'fa-shopping-basket', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' ' );
			echo '<p class="no-paniers">' . __( 'Vous n\'avez pas de panier à cette distribution', 'amapress' ) . '</p>';
			amapress_echo_panel_end();
		}

		if ( ! $need_responsables ) {
			echo $panel_resp;
		}

		if ( $is_resp_amap && ! $is_resp ) {
			echo $instructions;
		}

		?>
    </div>
	<?php
	$content = ob_get_clean();

	return $content;
}
