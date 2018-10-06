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

	$is_resp         = AmapressDistributions::isCurrentUserResponsable( $dist_id );
	$is_resp_amap    = amapress_current_user_can( 'administrator' ) || amapress_current_user_can( 'responsable_amap' );
	$can_unsubscribe = Amapress::start_of_week( $dist_date ) < Amapress::start_of_week( amapress_time() );

	$need_responsables = false;

	ob_start();

	if ( amapress_is_user_logged_in() ) {
		$can_subscribe = Amapress::end_of_day( $dist_date ) > amapress_time();
		amapress_echo_panel_start( 'Responsables de distributions', 'fa-fa', 'amap-panel-dist amap-panel-dist-' . $lieu_id . ' amap-panel-resp-dist' );
		if ( count( $responsables ) > 0 ) {
			echo '<div>' . amapress_generic_gallery( $responsables, 'resp', 'user_cell_contact', 'Pas de responsables' ) . '</div><br style="clear:both" />';
		} else { ?>
            <p class="dist-no-resp">Aucun responsable</p>
		<?php } ?>
		<?php if ( count( $responsables ) < $needed_responsables ) { ?>
            <p class="dist-miss-resp">
                Il <?php echo( $can_subscribe ? 'manque encore' : 'manquait' ) ?> <?php echo $needed_responsables - count( $responsables ) ?>
                responsable(s) de
                distributions.
            </p>
			<?php
			$need_responsables = true;
			if ( $can_subscribe && Amapress::hasRespDistribRoles() && ! $is_resp ) {
				echo '<p>';
				amapress_echo_button( 'S\'inscrire', amapress_action_link( $dist_id, 'sinscrire' ), 'fa-fa', false, "Confirmez-vous votre inscription ?" );
				echo '</p>';
//                    } else if ($can_unsubscribe && $is_resp) {
//                        echo '<p>';
//                        amapress_echo_button("Se désinscrire", amapress_action_link($dist_id, 'desinscrire'), false, "Confirmez-vous votre désinscription ?");
//                        echo '</p>';
			} ?>
			<?php
		}
		amapress_echo_panel_end();
	}

	$panel_resp = ob_get_clean();

//    var_dump($panel_resp);

	ob_start();

//    amapress_handle_action_messages();

	$btns   = [];
	$btns[] = amapress_get_button( 'Liste d\'émargement',
		amapress_action_link( $dist_id, 'liste-emargement' ), 'fa-fa',
		true, null, 'btn-print-liste' );
	if ( $is_resp_amap || current_user_can( 'edit_distrib' ) ) {
		$btns[] = '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '" class="btn btn-default">Editer la distribution</a>';
	}
	if ( $is_resp_amap || current_user_can( 'edit_contrat_instance' ) ) {
		$btns[] = '<a href="' . esc_attr( admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) ) . '" class="btn btn-default">Quantités au producteur</a>';
	}
	if ( $is_resp_amap || current_user_can( 'edit_distrib' ) ) {
		$mailto = $dist->getMailtoResponsables();
		if ( ! empty( $mailto ) ) {
			$btns[] = '<a href="' . $mailto . '" class="btn btn-default">Mail aux responsables</a>';
		}
		$smsto = $dist->getSMStoResponsables();
		if ( ! empty( $smsto ) ) {
			$btns[] = '<a href="' . $mailto . '" class="btn btn-default">SMS aux responsables</a>';
		}

		$mailto = $dist->getMailtoAmapiens();
		if ( ! empty( $mailto ) ) {
			$btns[] = '<a href="' . $mailto . '" class="btn btn-default">Mail aux amapiens</a>';
		}
		$smsto = $dist->getSMStoAmapiens();
		if ( ! empty( $smsto ) ) {
			$btns[] = '<a href="' . $mailto . '" class="btn btn-default">SMS aux amapiens</a>';
		}
	}
	?>

    <div class="distribution">
        <div class="btns">
	        <?php echo implode( '', $btns ) ?>
        </div>
		<?php

		if ( $need_responsables ) {
			echo $panel_resp;
		}

		$instructions = '';
		if ( $is_resp || $is_resp_amap ) {
			$add_text = '';
			if ( ! $is_resp_amap ) {
				$add_text = '<span class="resp-distribution">Vous êtes responsable de distribution</span> - ';
			}

			$instructions .= amapress_get_panel_start( $add_text . 'Instruction du lieu ',
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
		amapress_echo_panel_end();


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
				amapress_echo_panel_start_no_esc( esc_html( $contrat_model->post_title ) . $panier_btns, $icon,
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
	$content = ob_get_contents();
	ob_clean();

	return $content;
}