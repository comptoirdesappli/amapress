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

	$resp_ids = Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_responsables' );
	if ( $resp_ids && count( $resp_ids ) > 0 ) {
		$responsables = get_users( array( 'include' => array_map( 'intval', $resp_ids ) ) );
	} else {
		$responsables = array();
	}
//    $responsables_names=array_map(array('AmapressUsers','to_displayname'),$responsables);
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

	if ( amapress_is_user_logged_in() ) {
		$can_subscribe = $dist->canSubscribe();
		amapress_echo_panel_start( 'Responsables de distributions', 'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' amap-panel-resp-dist' );
		if ( count( $responsables ) > 0 ) {
			$render_func = 'user_cell';
			if ( amapress_can_access_admin() ||
			     ( Amapress::start_of_week( Amapress::add_a_week( amapress_time(), - 1 ) ) <= $dist_date
			       && $dist_date <= Amapress::end_of_week( amapress_time() ) ) ) {
				$render_func = 'user_cell_contact';

			}
			echo '<div>' . amapress_generic_gallery( $responsables, $render_func, [
					'if_empty' => 'Pas de responsables'
				] ) . '</div><br style="clear:both" />';
		} else { ?>
            <p class="dist-no-resp">Aucun responsable</p>
		<?php } ?>
		<?php
		if ( count( $responsables ) < $needed_responsables ) {
			if ( Amapress::start_of_day( $dist->getDate() ) >= Amapress::start_of_day( amapress_time() ) ) {
				$missing_format = 'Il manque encore %d responsable(s) de distributions.';
			} else {
				$missing_format = 'Il manquait %d responsable(s) de distributions.';
			}
			echo '<p class="dist-miss-resp">' . esc_html( sprintf( $missing_format, $needed_responsables - count( $responsables ) ) ) . '</p>';
			$need_responsables = true;
			$href              = Amapress::get_inscription_distrib_page_href();
			if ( ! empty( $href ) ) {
				if ( $is_resp_amap ) {
					echo '<p>Les inscriptions aux distributions des amapiens se gèrent <a href="' . esc_attr( $href ) . '" target="_blank">ici</a></p>';
				}
				if ( $is_resp ) {
					echo '<p>' . Amapress::makeLink( $href, 'Vous êtes responsable de distribution', true, true ) . '</p>';
				} else {
					echo '<p>' . Amapress::makeButtonLink( $href, ! empty( $dist->getSlotsConf() ) ? 'Inscriptions et créneaux horaires' : 'Inscriptions', true, true ) . '</p>';
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
			$btns[] = amapress_get_button( 'Liste d\'émargement',
				amapress_action_link( $dist_id, 'liste-emargement' ), 'fa-fa',
				true, null, 'btn-print-liste' );
		}
	}
	if ( $is_resp_amap || current_user_can( 'edit_distrib' ) ) {
		$btns[] = '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '#amapress_distribution_nb_resp_supp" class="btn btn-default">Ajouter des responsables supplémentaires</a>';
		$btns[] = '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '" class="btn btn-default">Editer la distribution (infos, horaires, créneaux...)</a>';
	}
	if ( $is_resp_amap || current_user_can( 'edit_contrat_instance' ) ) {
		$btns[] = '<a href="' . esc_attr( admin_url( 'admin.php?page=contrats_quantites_next_distrib&date=' . date_i18n( 'Y-m-d', $dist->getDate() ) ) )
		          . '" class="btn btn-default">Quantités au producteur</a>';
	}
	if ( $is_resp_amap || current_user_can( 'edit_distrib' ) ) {
		$mailto = $dist->getMailtoResponsables();
		if ( ! empty( $mailto ) ) {
			$btns[] = '<a href="' . $mailto . '" class="btn btn-default">Email aux responsables</a>';
		}

		$btns[] = '<a target="_blank" href="' . admin_url( 'admin.php?page=amapress_messages_page' ) . '" class="btn btn-default">Email aux amapiens</a>';
	}
	?>

    <div class="distribution">
        <div class="btns">
			<?php echo implode( '', $btns ) ?>
        </div>
		<?php

		$current_user_slot = $dist->getSlotInfoForUser( amapress_current_user_id() );
		if ( $current_user_slot ) {
			echo '<p class="amapien-has-creneau" style="text-align: center;color: orange">Vous avez le créneau ' . esc_html( $current_user_slot['display'] ) . '</p>';
		}

		if ( $need_responsables ) {
			echo $panel_resp;
		}

		if ( amapress_is_user_logged_in() && Amapress::getOption( 'enable-gardiens-paniers' ) ) {
			amapress_echo_panel_start_no_esc( '<a id="panel_gardiens_paniers"/>Gardiens de paniers' );
			if ( empty( $user_contrats ) ) {
				echo '<p><strong>Vous n\'avez pas de contrats à cette distribution</strong></p>';
			}
			echo amapress_gardiens_paniers_map( $dist_id );
			$amapiens_map_link = Amapress::get_page_with_shortcode_href( 'amapiens-map', 'amps_amapiens_map_href' );
			if ( ! empty( $amapiens_map_link ) ) {
				echo '<p>' . Amapress::makeLink( $amapiens_map_link, 'Voir la carte complète des amapiens', true, true ) . '</p>';
			}
			if ( in_array( amapress_current_user_id(), $dist->getGardiensIds( false ) ) ) {
				echo '<p style="font-weight: bold; margin-top: 1em">Vous êtes inscrit Gardien de paniers</p>';
			}
			$current_amapien = AmapressUser::getBy( amapress_current_user_id() );
			$gardien_id      = $dist->getPanierGardienId( amapress_current_user_id() );
			if ( ! empty( $gardien_id ) ) {
				$gardien_comment = $dist->getGardienComment( $gardien_id );
				if ( ! empty( $gardien_comment ) ) {
					$gardien_comment = '<br /><em>' . esc_html( $gardien_comment ) . '</em>';
				}
				$gardien = AmapressUser::getBy( $gardien_id );
				echo '<p style="font-weight: bold; margin-top: 1em">Votre/vos panier(s) seront gardés par ' . $gardien->getDisplayName() . '(' . $gardien->getContacts( wp_is_mobile() ) . ')<em>' . $gardien_comment . '</em></p>';

			}
			$gardien_amapien_ids = $dist->getGardiensPaniersAmapiensIds( amapress_current_user_id() );
			if ( ! empty( $gardien_amapien_ids ) ) {
				echo '<p>Vous gardez les paniers de : ' .
				     implode( ', ', array_map( function ( $uid ) {
					     $u = AmapressUser::getBy( $uid );

					     return sprintf( '%s (%s)', $u->getDisplayName(), $u->getContacts( wp_is_mobile() ) );
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
					'title' => 'Nom',
					'data'  => 'name'
				),
				array(
					'title' => 'Contacts',
					'data'  => 'contacts'
				),
				array(
					'title' => 'Infos',
					'data'  => 'infos'
				),
			);

			$data = array_map( function ( $u ) use ( $current_amapien, $gardien_id, $dist, $can_subscribe, $user_contrats ) {
				$link = '';
				if ( $can_subscribe && ! empty( $user_contrats ) ) {
					if ( empty( $gardien_id ) && $u->ID != amapress_current_user_id() ) {
						$link = '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="inscrire_garde" data-confirm="Avez-vous pris contact avec ce gardien de paniers et souhaitez-vous vraiment lui confier la garde de votre panier ?"
					data-dist="' . $dist->ID . '" data-gardien="' . $u->ID . '" data-user="' . amapress_current_user_id() . '">Confier la garde</button>';
					} elseif ( $u->ID == $gardien_id ) {
						$link = '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="desinscrire_garde" data-confirm="Avez-vous pris contact avec ce gardien de paniers et souhaitez-vous vraiment lui retirer la garde de votre panier ?"
					data-dist="' . $dist->ID . '" data-gardien="' . $u->ID . '" data-user="' . amapress_current_user_id() . '">Retirer la garde</button>';
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
									sprintf( 'à %s (à vol d\'oiseau)',
										AmapressUsers::distanceFormatMeter(
											$current_amapien->getUserLatitude(),
											$current_amapien->getUserLongitude(),
											$u->getUserLatitude(),
											$u->getUserLongitude() ) )
									: 'Vous n\'êtes pas localisé'
								) ) : '' )
				);
			}, $dist->getGardiens() );

			amapress_echo_datatable( 'all-gardiens', $columns, $data,
				array(
					'searching'    => true,
					'nowrap'       => false,
					'responsive'   => false,
					'init_as_html' => 'true'
				) );

			if ( in_array( amapress_current_user_id(), $dist->getGardiensIds() ) ) {
				$inscription_href = Amapress::get_inscription_distrib_page_href();
				if ( ! empty( $inscription_href ) ) {
					echo '<p>' . Amapress::makeButtonLink(
							$inscription_href, 'Se proposer comme gardien de panier',
							true, true
						) . '</p>';
				}
			}

			if ( amapress_can_access_admin() && Amapress::end_of_day( $dist_date ) > amapress_time() ) {
				amapress_echo_panel_start( 'Pour les responsables AMAP' );
				$users = [ '' => '--Sélectionner un amapien--' ];
				//precache
				amapress_precache_all_users();

				foreach ( $dist->getMainAdherentsIds( true ) as $user_id ) {
					$user               = AmapressUser::getBy( $user_id );
					$users[ $user->ID ] = sprintf( '%s (%s)', $user->getDisplayName(), $user->getEmail() );
				}
				echo '<form><label for="other-amapien">Amapien demandeur</label>';
				echo '<select name="other-amapien" id="other-amapien" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>';
				echo '<br/><label for="other-gardien">Gardien</label>';
				echo '<select name="other-gardien" id="other-gardien" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>';
				echo '<br/><button type="button" class="btn btn-default amapress-ajax-button" 
					data-action="inscrire_garde" data-confirm="Etes-vous sûr ?"
					data-dist="' . $dist_id . '" data-gardien="val:#other-gardien" data-user="val:#other-amapien">Confier la garde</button></form>';
				echo '<hr/>';

				$columns = array(
					array(
						'title' => '',
						'data'  => 'link',
					),
					array(
						'title' => 'Gardien',
						'data'  => 'gardien'
					),
					array(
						'title' => 'Demandeur',
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
					data-action="desinscrire_garde" data-confirm="Etes-vous sûr ?"
					data-dist="' . $dist_id . '" data-gardien="' . $gardien->ID . '" data-user="' . $amapien->ID . '">Retirer la garde</button>',
							'gardien' => sprintf( '%s (%s)', $gardien->getDisplayName(), $gardien->getContacts( false ) ) . $gardien_comment,
							'amapien' => sprintf( '%s (%s)', $amapien->getDisplayName(), $amapien->getContacts( false ) ),
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
			}

			amapress_echo_panel_end();
		}

		$instructions = '';
		if ( $is_resp || $is_resp_amap ) {
			$add_text = '';
			if ( ! $is_resp_amap ) {
				$add_text = '<span class="resp-distribution">Vous êtes responsable de distribution</span> - ';
			}

			$instructions .= amapress_get_panel_start_no_esc( $add_text . 'Instruction du lieu ',
				'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' ',
				'instructions-lieu' );
			if ( ! $is_resp_amap ) {
				if ( $can_unsubscribe ) {
					amapress_get_button( 'Se désinscrire', amapress_action_link( $dist_id, 'desinscrire' ), 'fa-fa', false, "Confirmez-vous votre désinscription ?" );
				}
			}
			$instructions .= $dist->getLieu()->getInstructions_privee();
			if ( strpos( $dist->getLieu()->getInstructions_privee(), '[liste-emargement-button]' ) === false ) {
				$instructions .= '<br/>';
				$instructions .= amapress_get_button( 'Imprimer la liste d\'émargement',
					amapress_action_link( $dist_id, 'liste-emargement' ), 'fa-fa',
					true, null, 'btn-print-liste' );
			}
			$instructions .= amapress_get_panel_end();
			if ( $is_resp ) {
				echo $instructions;
			}
		}

		$lieu_id = $lieu->ID;
		amapress_echo_panel_start( 'Lieu', 'fa-map-marker', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' amap-panel-dist-lieu amap-panel-dist-lieu-' . $lieu_id );

		echo '<div class="dist-lieu-photo"><img src="' . amapress_get_avatar_url( $lieu_id, null, 'produit-thumb', 'default_lieu.jpg' ) . '" alt="" /></div>';
		echo '<h3><a href="' . get_post_permalink( $lieu_id ) . '">' . ( $lieu_subst_id ? '<strong> EXCEPTIONNELLEMENT à </strong>' : '' ) . $lieu->post_title . '</a></h3>' .
		     '<p class="dist-lieu-adresse">Adresse : ' . get_post_meta( $lieu_id, 'amapress_lieu_distribution_adresse', true ) . '<br />' .
		     get_post_meta( $lieu_id, 'amapress_lieu_distribution_code_postal', true ) . ' ' . get_post_meta( $lieu_id, 'amapress_lieu_distribution_ville', true ) .
		     '</p>' .
		     '<p class="dist-lieu-horaires">' .
		     ' de ' . date_i18n( 'H:i', get_post_meta( $lieu_id, 'amapress_lieu_distribution_heure_debut', true ) ) .
		     ' à ' . date_i18n( 'H:i', get_post_meta( $lieu_id, 'amapress_lieu_distribution_heure_fin', true ) ) .
		     '</p>';
		amapress_echo_panel_end();

		if ( amapress_is_user_logged_in() ) {
			$info_html = $dist->getInformations();
			$info_text = trim( strip_tags( $info_html ) );
			if ( ! empty( $info_text ) ) {
				amapress_echo_panel_start( 'Informations spécifiques', 'fa-fa', 'amap-panel-dist amap-panel-dist-info' );
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
			amapress_echo_panel_start( 'Panier(s) intermittent(s)', 'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' ' );
			echo amapress_get_paniers_intermittents_exchange_table( $adhs );
			amapress_echo_panel_end();
		}

		//        amapress_echo_panel_start('Panier(s)', 'fa-shopping-basket', 'amap-panel-dist amap-panel-dist-'.$lieu_id.' ');

		if ( amapress_is_user_logged_in() && Amapress::isIntermittenceEnabled() && ! empty( $user_contrats ) ) {
			amapress_echo_panel_start( 'En cas d\'absence - Espace intermittents' );
			$paniers       = AmapressPaniers::getPaniersForDist( $dist->getDate() );
			$ceder_title   = count( $user_contrats ) > 1 ? 'Céder mes ' . count( $user_contrats ) . ' paniers' : 'Céder mon panier';
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
						echo '<div class="echange-panier-info amapress-ajax-parent"><h4 class="echange-panier-info-title">Informations</h4><textarea id="' . $id . '"></textarea><br/>';
						echo '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="echanger_panier" data-message="val:#' . $id . '" data-confirm="Etes-vous sûr de vouloir céder votre/vos paniers ?"
					data-dist="' . $dist->ID . '" data-user="' . amapress_current_user_id() . '">' . $ceder_title . '</button></div>';
					} else {
						echo '<span class="echange-closed">Cessions de paniers closes</span>';
					}
					break;
				case 'to_exchange':
					echo '<span class="panier-to-exchange">Panier(s) en attente de repreneur</span>';
					break;
				case 'exchanged':
					echo '<span class="panier-exchanged">Panier(s) cédé(s)</span>';
					break;
				case 'exch_valid_wait':
					echo '<span class="panier-exchanged">Panier(s) en attente de validation de reprise</span>';
					break;
				case 'closed':
					echo '<span class="echange-done">Cession effectuée</span>';
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
					$ceder_panier_title = 'Céder mon panier "' . $panier_title . '"';
					switch ( $is_intermittent ) {
						case 'exchangeable':
							if ( $can_subscribe ) {
								$id = "info_{$panier->ID}_{$dist->ID}";
								echo '<div class="echange-panier-info amapress-ajax-parent"><h4 class="echange-panier-info-title">Informations</h4><textarea id="' . $id . '"></textarea><br/>';
								echo '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="echanger_panier" data-message="val:#' . $id . '" data-confirm="Etes-vous sûr de vouloir céder votre/vos paniers ?"
					data-dist="' . $dist->ID . '" data-panier="' . $panier->ID . '" data-user="' . amapress_current_user_id() . '">' . $ceder_panier_title . '</button></div>';
							} else {
								echo '<span class="echange-closed">Cessions de paniers closes</span>';
							}
							break;
						case 'to_exchange':
							echo '<span class="panier-to-exchange">Panier "' . $panier_title . '" en attente de repreneur</span>';
							break;
						case 'exchanged':
							echo '<span class="panier-exchanged">Panier "' . $panier_title . '" cédé</span>';
							break;
						case 'exch_valid_wait':
							echo '<span class="panier-exchanged">Panier "' . $panier_title . '" en attente de validation de reprise</span>';
							break;
						case 'closed':
							echo '<span class="echange-done">Cession panier "' . $panier_title . '" effectuée</span>';
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
				$contrat_model = get_post( intval( get_post_meta( $contrat_id, 'amapress_contrat_instance_model', true ) ) );
				$panier        = AmapressPaniers::getPanierForDist( $dist_date, $contrat_id );
				if ( $panier == null ) {
					continue;
				}

				$icon = Amapress::coalesce_icons( amapress_get_avatar_url( $contrat_id, null, 'produit-thumb', null ), Amapress::getOption( "contrat_{$contrat_model->ID}_icon" ), amapress_get_avatar_url( $contrat_model->ID, null, 'produit-thumb', 'default_contrat.jpg' ) );
				if ( ! empty( $icon ) && false !== strpos( $icon, '://' ) ) {
					$icon = '<img src="' . esc_attr( $icon ) . '" class="dist-panier-contrat-img" alt="' . esc_attr( $contrat_model->post_title ) . '" />';
				}

				$panier_btns = '';
				if ( $is_resp_amap || current_user_can( 'edit_panier' ) ) {
					$panier_btns = '<a href="' . esc_attr( $panier->getAdminEditLink() ) . '" class="btn btn-default">Editer le contenu/Déplacer</a>';
				}
				amapress_echo_panel_start_no_esc( Amapress::makeLink( get_permalink( $contrat_model ), $contrat_model->post_title, true, true ) . $panier_btns, $icon,
					'amap-panel-dist amap-panel-dist-' . $lieu_id . ' amap-panel-dist-panier amap-panel-dist-panier-' . $contrat_model->ID );
				echo AmapressPaniers::getPanierContentHtml( $panier->ID, $lieu_id );
				amapress_echo_panel_end();

				$has_contrats = true;
			}
		}
		if ( ! $has_contrats ) {
			amapress_echo_panel_start( 'Panier(s)', 'fa-shopping-basket', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' ' );
			echo '<p class="no-paniers">Vous n\'avez pas de panier à cette distribution</p>';
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
