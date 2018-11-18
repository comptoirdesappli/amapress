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
				data-action="reprendre_panier" data-confirm="Etes-vous sûr de vouloir reprendre ce panier ?" data-panier="' . implode( ',', array_map( function ( $a ) {
						return $a->ID;
					}, $adh ) ) . '">Je suis interessé</button>';
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

	$atts  = shortcode_atts( array(
		'contrat' => null,
	), $atts );
	$query = array(
		'status' => 'to_exchange',
	);
//    $ret = '';
	if ( ! empty( $atts['contrat'] ) ) {
		$dist_id = Amapress::resolve_post_id( $atts['contrat'], AmapressDistribution::INTERNAL_POST_TYPE );
		if ( $dist_id ) {
			$dist                         = AmapressDistribution::getBy( $dist_id );
			$query['contrat_instance_id'] = array_map( function ( $a ) {
				return $a->ID;
			},
				$dist->getContrats() );
			$query['date']                = $dist->getDate();
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
			'title'              => 'Date',
			'data'               => array(
				'_'    => 'date_display',
				'sort' => 'date_value',
			)
		);
	}
	if ( $show_columns['panier'] ) {
		$columns[] = array(
			'responsivePriority' => 4,
			'title'              => 'Panier',
			'data'               => array(
				'_'    => 'panier',
				'sort' => 'panier',
			)
		);
	}
	if ( $show_columns['lieu'] ) {
		$columns[] = array(
			'responsivePriority' => 5,
			'title'              => 'Lieu',
			'data'               => array(
				'_'    => 'lieu',
				'sort' => 'lieu',
			)
		);
	}
	if ( $show_columns['quantite'] ) {
		$columns[] = array(
			'responsivePriority' => 3,
			'title'              => 'Quantité',
			'data'               => array(
				'_'    => 'quantite',
				'sort' => 'quantite',
			)
		);
	}
	if ( $show_columns['prix'] ) {
		$columns[] = array(
			'title' => 'Prix',
			'data'  => array(
				'_'    => 'price',
				'sort' => 'price',
			)
		);
	}
	if ( $show_columns['adherent'] ) {
		$columns[] = array(
			'title' => 'Adhérent',
			'data'  => array(
				'_'    => 'adherent',
				'sort' => 'adherent',
			)
		);
	}
	if ( $show_columns['message'] ) {
		$columns[] = array(
			'title' => 'Message',
			'data'  => array(
				'_'    => 'message',
				'sort' => 'message',
			)
		);
	}
	if ( $show_columns['repreneur'] ) {
		$columns[] = array(
			'title' => 'Repreneur',
			'data'  => array(
				'_'    => 'repreneur',
				'sort' => 'repreneur',
			)
		);
	}
	if ( $show_columns['etat'] ) {
		$columns[] = array(
			'responsivePriority' => 2,
			'title'              => 'État',
			'data'               => array(
				'_'    => 'state',
				'sort' => 'state',
			)
		);
	}

	$ahs_by_date = array_group_by( $adhs,
		function ( $a ) {
			/** @var AmapressIntermittence_panier $a */
			return "{$a->getDate()}-{$a->getAdherent()->ID}-{$a->getRealLieu()->ID}";
		} );
	$data        = array();
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
		$paniers   = array();
		$quantites = array();
		$prices    = array();
		foreach ( $adh as $a ) {
			foreach ( $a->getContrat_instances() as $contrat_instance ) {
				$adhesions = AmapressAdhesion::getUserActiveAdhesions( $a->getAdherent()->ID, $contrat_instance->ID );
				/** @var AmapressAdhesion $adhesion */
				$adhesion = array_shift( $adhesions );

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
			'date_display' => date_i18n( 'd/m/Y', $date ),
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
					$state .= '<div class="cancel-echange-panier amapress-ajax-parent" style="border-top: 1pt solid black"><label style="display: block" for="' . $id . '">Motif d\'annulation</label><textarea id="' . $id . '"></textarea><br/>';
					$state .= '<button type="button" class="btn btn-default amapress-ajax-button annuler-echange-panier" 
				data-message="val:#' . $id . '" data-confirm="Etes-vous sûr d\'annuler votre proposition?" data-action="annuler_adherent" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">Annuler échange</button></div>';
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
					$state .= '<button type="button" class="btn btn-default amapress-ajax-button validate-echange" data-user="' . $user->ID . '" data-confirm="Etes-vous sûr de vouloir valider la reprise de votre panier ?" data-action="validate_reprise" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">Valider échange (' . $user->getDisplayName() . ')</button><br/>';
					$state .= '<button type="button" class="btn btn-default amapress-ajax-button reject-echange" data-user="' . $user->ID . '" data-confirm="Etes-vous sûr de vouloir valider la propositio, ?" data-action="reject_reprise" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">Rejet échange (' . $user->getDisplayName() . ')</button>';
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
				$state = '<div class="amapress-ajax-parent"><span style="display: block">' . ( 'exch_valid_wait' == $status ? '<strong>En attente de validation</strong>' : 'A récupérer' ) . '</span>';
				if ( $ad->getDate() >= Amapress::end_of_day( amapress_time() ) ) {
					$state .= '<button type="button" class="btn btn-default amapress-ajax-button annuler-echange-repreneur" data-confirm="Etes-vous sûr de vouloir annuler l\'échange ?" data-action="annuler_repreneur" data-panier="' . implode( ',', array_map( function ( $a ) {
							return $a->ID;
						}, $adh ) ) . '">Annuler échange</button></div>';
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