<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_paniers_intermittents_exchange_table( $adhs ) {
	static $id_incr = 0;
	$id_incr += 1;

	return amapress_get_paniers_intermittents_table( 'paniers-a-echanger' . $id_incr, $adhs,
		function ( $state, $status, $adh ) {
			if ( $status == 'to_exchange' ) {
				$ad = $adh[0];
				$id = "i{$ad->getDate()}-{$ad->getAdherent()->ID}-{$ad->getRealLieu()->ID}";
//				$state = '<div class=""><p>Informations</p><textarea id="' . $id . '"></textarea></div>';
				//data-message="val:#' . $id . '"
				$state = '<button type="button" class="btn btn-default amapress-ajax-button reprendre-panier" 
				data-action="reprendre_panier" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir reprendre ce panier ?', 'amapress' ) . '" data-panier="' . implode( ',', array_map( function ( $a ) {
						return $a->ID;
					}, $adh ) ) . '">' . __( 'Je suis interessé', 'amapress' ) . '</button>';
			}

			return $state;
		},
		array(), array( 'show_tel' => 'force', 'show_email' => 'force' ),
		array(
			'date'      => true,
			'panier'    => true,
			'quantite'  => true,
			'lieu'      => true,
			'prix'      => true,
			'adherent'  => true,
			'message'   => true,
			'repreneur' => true,
			'etat'      => true,
		) );
}

//add_filter('amapress_get_user_infos_content_paniers_a_echanger', 'amapress_get_user_infos_content_paniers_a_echanger', 10, 2);
function amapress_all_paniers_intermittents_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts                    = shortcode_atts( array(
		'contrat'                 => null,
		'allow_amapiens'          => true,
		'check_adhesion'          => Amapress::toBool( Amapress::getOption( 'intermit_adhesion_req' ) ),
		'check_adhesion_received' => false,
		'enabled_for_resp'        => false,
	), $atts );
	$check_adhesion          = Amapress::toBool( $atts['check_adhesion'] );
	$check_adhesion_received = Amapress::toBool( $atts['check_adhesion_received'] );
	if ( ! Amapress::toBool( $atts['enabled_for_resp'] ) && amapress_is_admin_or_responsable() ) {
		$check_adhesion          = false;
		$check_adhesion_received = false;
	}
	if ( $check_adhesion ) {
		$adh_period = AmapressAdhesionPeriod::getCurrent();
		if ( empty( $adh_period ) ) {
			return ( sprintf( __( 'Aucune période d\'adhésion n\'est configurée au %s', 'amapress' ), date_i18n( 'd/m/Y' ) ) );
		}

		$adh_paiement = AmapressAdhesion_paiement::getForUser( amapress_current_user_id() );
		if ( empty( $adh_paiement ) || ( $check_adhesion_received && $adh_paiement->isNotReceived() ) ) {
			return wp_unslash( Amapress::getOption( 'online_subscription_inter_req_adhesion' ) );
		}
	}

	if ( ! Amapress::toBool( $atts['allow_amapiens'] ) ) {
		$amapien = AmapressUser::getBy( amapress_current_user_id() );
		if ( $amapien && ! $amapien->isIntermittent() ) {
			return '<p><strong>' . __( 'La réservation de paniers n\'est pas ouverte aux amapiens non intermittents.', 'amapress' ) . '</strong></p>';
		}
	}
	$query = array(
		'status' => 'to_exchange',
	);
//    $ret = '';
	if ( ! empty( $atts['contrat'] ) ) {
		$dist_id = Amapress::resolve_post_id( $atts['contrat'], AmapressDistribution::INTERNAL_POST_TYPE );
		if ( $dist_id ) {
			$dist = AmapressDistribution::getBy( $dist_id );
			if ( $dist ) {
				$query['contrat_instance_id'] = array_map( function ( $a ) {
					return $a->ID;
				},
					$dist->getContrats() );
				$query['date']                = $dist->getDate();
			}
		}
	}
	$adhs = AmapressPaniers::getPanierIntermittents( $query );

	return amapress_get_paniers_intermittents_exchange_table( $adhs );
}

/**
 * @param AmapressIntermittence_panier[] $adhs
 *
 * @return string
 */
function amapress_get_paniers_intermittents_table(
	$id,
	$adhs,
	$state_function,
	$table_options = array(),
	$show_options = array(),
	$show_columns = array()
) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$show_columns = wp_parse_args( $show_columns,
		array(
			'date'      => true,
			'panier'    => true,
			'quantite'  => true,
			'lieu'      => true,
			'prix'      => true,
			'adherent'  => true,
			'message'   => true,
			'repreneur' => true,
			'etat'      => true,
		) );

	$columns = array();

	if ( $show_columns['date'] ) {
		$columns[] = array(
			'responsivePriority' => 1,
			'title'              => __( 'Date', 'amapress' ),
			'data'               => array(
				'_'    => 'date_display',
				'sort' => 'date_value',
			)
		);
	}
	if ( $show_columns['panier'] ) {
		$columns[] = array(
			'responsivePriority' => 4,
			'title'              => __( 'Panier', 'amapress' ),
			'data'               => array(
				'_'    => 'panier',
				'sort' => 'panier',
			)
		);
	}
	if ( $show_columns['lieu'] ) {
		$columns[] = array(
			'responsivePriority' => 5,
			'title'              => __( 'Lieu', 'amapress' ),
			'data'               => array(
				'_'    => 'lieu',
				'sort' => 'lieu',
			)
		);
	}
	if ( $show_columns['quantite'] ) {
		$columns[] = array(
			'responsivePriority' => 3,
			'title'              => __( 'Quantité', 'amapress' ),
			'data'               => array(
				'_'    => 'quantite',
				'sort' => 'quantite',
			)
		);
	}
	if ( $show_columns['prix'] ) {
		$columns[] = array(
			'title' => __( 'Prix', 'amapress' ),
			'data'  => array(
				'_'    => 'price',
				'sort' => 'price',
			)
		);
	}
	if ( $show_columns['adherent'] ) {
		$columns[] = array(
			'title' => __( 'Adhérent', 'amapress' ),
			'data'  => array(
				'_'    => 'adherent',
				'sort' => 'adherent',
			)
		);
	}
	if ( $show_columns['message'] ) {
		$columns[] = array(
			'title' => __( 'Message', 'amapress' ),
			'data'  => array(
				'_'    => 'message',
				'sort' => 'message',
			)
		);
	}
	if ( $show_columns['repreneur'] ) {
		$columns[] = array(
			'title' => __( 'Repreneur', 'amapress' ),
			'data'  => array(
				'_'    => 'repreneur',
				'sort' => 'repreneur',
			)
		);
	}
	if ( $show_columns['etat'] ) {
		$columns[] = array(
			'responsivePriority' => 2,
			'title'              => __( 'État', 'amapress' ),
			'data'               => array(
				'_'    => 'state',
				'sort' => 'state',
			)
		);
	}

	$allow_partial_exchange = Amapress::getOption( 'allow_partial_exchange' );
	$ahs_by_date            = array_group_by( $adhs,
		function ( $a ) use ( $allow_partial_exchange ) {
			if ( $allow_partial_exchange ) {
				return $a->ID;
			}

			/** @var AmapressIntermittence_panier $a */
			return "{$a->getDate()}-{$a->getAdherent()->ID}-{$a->getRealLieu()->ID}";
		} );
	$data                   = array();
	foreach ( $ahs_by_date as $adh ) {
		/** @var AmapressIntermittence_panier[] $adh */
		$ad          = $adh[0];
		$date        = $ad->getDate();
		$dist        = AmapressPaniers::getDistribution( $date, $ad->getLieu()->ID );
		$status      = $ad->getStatus();
		$status_text = $ad->getStatusDisplay();
		$state       = "<span class='$status'>$status_text</span>";
		if ( ! isset( $show_columns['for_print'] ) || $show_columns['for_print'] === false ) {
			$state = call_user_func( $state_function, $state, $status, $adh );
		}
		$lieu      = ( $dist->getLieuSubstitution() ? $dist->getLieuSubstitution() : $dist->getLieu() );
		$repreneur = '';
		if ( $ad->getRepreneur() != null ) {
			$repreneur = $ad->getRepreneur()->getDisplay( $show_options );
		}
		if ( empty( $repreneur ) ) {
			$askers = [];
			foreach ( $ad->getAsk() as $user_id => $user_info ) {
				$user = AmapressUser::getBy( $user_id );
				if ( empty( $user ) ) {
					continue;
				}
				$askers[] = $user->getDisplay( $show_options );
			}
			$repreneur .= '<strong>' . __( 'Non validé', 'amapress' ) . '</strong><br/>' . implode( '', $askers );
		}
		$paniers   = array();
		$quantites = array();
		$prices    = array();
		foreach ( $adh as $a ) {
			foreach ( $a->getContrat_instances() as $contrat_instance ) {
				$adhesions = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $a->getAdherent()->ID, $contrat_instance->ID );
				if ( empty( $adhesions ) ) {
					continue;
				}
				/** @var AmapressAdhesion $adhesion */
				$adhesion = array_shift( $adhesions );
				if ( empty( $adhesion ) ) {
					continue;
				}

				$paniers[]   = "<a href='{$dist->getPermalink()}'>{$contrat_instance->getModelTitle()}</a>";
				$quantites[] = $adhesion->getContrat_quantites_AsString( $date );
				$prices[]    = $adhesion->getContrat_quantites_Price( $date );
			}
		}
		$users     = AmapressContrats::get_related_users( $ad->getAdherentId() );
		$adherents = '';
		foreach ( $users as $user ) {
			$amapien = AmapressUser::getBy( $user );
			if ( ! empty( $amapien ) ) {
				$adherents .= $amapien->getDisplay( $show_options );
			}
		}
		$data[] = array(
			'panier'       => implode( ', ', $paniers ),
			'lieu'         => "<a href='{$lieu->getPermalink()}'>{$lieu->getShortName()}</a>",
			'quantite'     => implode( ', ', $quantites ),
			'price'        => implode( ' + ', $prices ),
			'adherent'     => $adherents,
			//"<a href='mailto:{$ad->getAdherent()->getUser()->user_email}'>" . $ad->getAdherent()->getDisplayName() . '</a> (' . $ad->getAdherent()->getTelephone() . ')',
			'repreneur'    => $repreneur,
			'message'      => $ad->getMessage(),
			'date_display' => date_i18n( 'd/m/Y', $date ) . '<br/><span style="font-size: 0.7em; color: #2b2b2b">' . __( 'depuis ', 'amapress' ) . date_i18n( 'd/m/Y', get_post_time( 'U', false, $ad->getPost() ) ) . '</span>',
			'date_value'   => $date,
			'state'        => $state,
		);
	}

	ob_start();

	$table_options['init_as_html'] = true;

	amapress_echo_datatable( $id, $columns, $data, $table_options );
	$content = ob_get_clean();

	return $content;
}


//add_filter('amapress_get_user_infos_content_echange_paniers', 'amapress_get_user_infos_content_echange_paniers', 10, 2);
function amapress_user_paniers_intermittents_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'show_history' => 'false',
			'history_days' => '180',
			'show_futur'   => 'true',
		), $atts
	);

	$adhs = AmapressPaniers::getPanierIntermittents(
		array(
			'adherent'     => amapress_current_user_id(),
			'history_days' => intval( $atts['history_days'] ),
			'show_history' => Amapress::toBool( $atts['show_history'] ),
			'show_futur'   => Amapress::toBool( $atts['show_futur'] ),
		)
	);

	static $id_incr = 0;
	$id_incr += 1;

	return amapress_get_paniers_intermittents_table( 'my-echanges' . $id_incr, $adhs,
		function ( $state, $status, $adh ) {
			if ( 'to_exchange' == $status || 'exch_valid_wait' == $status || 'exchanged' == $status ) {
				/** @var AmapressIntermittence_panier $ad */
				$ad    = $adh[0];
				$id    = "i{$ad->getDate()}-{$ad->getAdherent()->ID}-{$ad->getRealLieu()->ID}";
				$state = '<strong>' . $state . '</strong>';
				if ( $ad->getDate() >= Amapress::end_of_day( amapress_time() ) ) {
					$state .= '<div class="cancel-echange-panier amapress-ajax-parent" style="border-top: 1pt solid black"><label style="display: block" for="' . $id . '">' . __( 'Motif d\'annulation', 'amapress' ) . '</label><textarea id="' . $id . '"></textarea><br/>';
					$state .= '<button type="button" class="btn btn-default amapress-ajax-button annuler-echange-panier" 
				data-message="val:#' . $id . '" data-confirm="' . esc_attr__( 'Etes-vous sûr d\'annuler votre proposition?', 'amapress' ) . '" data-action="annuler_adherent" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">' . __( 'Annuler échange', 'amapress' ) . '</button></div>';
				}
				foreach ( $ad->getAsk() as $ask ) {
					$user = AmapressUser::getBy( $ask['user'] );
					if ( ! $user ) {
						continue;
					}

					$state .= '<div class="echange-panier-asker amapress-ajax-parent">';
					$state .= $user->getDisplay( array(
						'show_tel'   => 'force',
						'show_email' => 'force',
					) );
					$state .= '<div><button type="button" class="btn btn-default amapress-ajax-button validate-echange" data-user="' . $user->ID . '" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir valider la reprise de votre panier ?', 'amapress' ) . '" data-action="validate_reprise" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">' . __( 'Valider échange', 'amapress' ) . ' (' . $user->getDisplayName() . ')</button><br/>';
					$state .= '<button type="button" class="btn btn-default amapress-ajax-button reject-echange" data-user="' . $user->ID . '" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir valider la propositio ?', 'amapress' ) . '" data-action="reject_reprise" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">' . __( 'Rejet échange', 'amapress' ) . ' (' . $user->getDisplayName() . ')</button></div>';
					$state .= '</div>';
				}
				if ( $ad->getRepreneurId() ) {
					$state .= $ad->getRepreneur()->getDisplay( array(
						'show_tel'   => 'force',
						'show_email' => 'force',
					) );
				}
			}

			return $state;
		},
		array(), array( 'show_tel' => 'force', 'show_email' => 'force' ),
		array(
			'date'      => true,
			'panier'    => true,
			'quantite'  => true,
			'lieu'      => false,
			'prix'      => true,
			'adherent'  => false,
			'message'   => true,
			'repreneur' => true,
			'etat'      => true,
		) );
}

function amapress_intermittent_paniers_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'show_history' => 'false',
			'history_days' => '30',
			'show_futur'   => 'true',
		), $atts
	);

	$adhs = AmapressPaniers::getPanierIntermittents(
		array(
			'repreneur'    => amapress_current_user_id(),
			'history_days' => intval( $atts['history_days'] ),
			'show_history' => Amapress::toBool( $atts['show_history'] ),
			'show_futur'   => Amapress::toBool( $atts['show_futur'] ),
		)
	);

	static $id_incr = 0;
	$id_incr += 1;

	return amapress_get_paniers_intermittents_table( 'my-recups' . $id_incr, $adhs,
		function ( $state, $status, $adh ) {
			if ( 'exch_valid_wait' == $status || 'exchanged' == $status ) {
				/** @var AmapressIntermittence_panier $ad */
				$ad    = $adh[0];
				$state = '<div class="amapress-ajax-parent"><span style="display: block">' . ( 'exch_valid_wait' == $status ? '<strong>' . __( 'En attente de validation', 'amapress' ) . '</strong>' : 'A récupérer' ) . '</span>';
				if ( $ad->getDate() >= Amapress::end_of_day( amapress_time() ) ) {
					$state .= '<button type="button" class="btn btn-default amapress-ajax-button annuler-echange-repreneur" data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir annuler l\'échange ?', 'amapress' ) . '" data-action="annuler_repreneur" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">' . __( 'Annuler échange', 'amapress' ) . '</button></div>';
				}
			}

			return $state;
		},
		array(), array( 'show_tel' => 'force', 'show_email' => 'force' ),
		array(
			'date'      => true,
			'panier'    => true,
			'quantite'  => true,
			'lieu'      => true,
			'prix'      => true,
			'adherent'  => true,
			'message'   => true,
			'repreneur' => false,
			'etat'      => true,
		) );
}

function amapress_user_paniers_intermittents_count_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	return '<span class="badge">' . count( AmapressPaniers::getUserPaniersIntermittents() ) . '</span>';
}

function amapress_all_paniers_intermittents_count_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	return '<span class="badge">' . count( AmapressPaniers::getPaniersIntermittentsToBuy() ) . '</span>';
}