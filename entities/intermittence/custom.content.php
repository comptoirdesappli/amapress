<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_paniers_intermittents_exchange_table( $adhs ) {
	return amapress_get_paniers_intermittents_table( 'paniers-a-echanger', $adhs,
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
			$dist                         = new AmapressDistribution( $dist_id );
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
				'_'    => 'date.display',
				'sort' => 'date.value',
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
			$adhesions = AmapressAdhesion::getUserActiveAdhesions( $a->getAdherent()->ID, $a->getContrat_instance()->ID );
			/** @var AmapressAdhesion $adhesion */
			$adhesion = array_shift( $adhesions );

			$paniers[]   = "<a href='{$dist->getPermalink()}'>{$a->getContrat_instance()->getModel()->getTitle()}</a>";
			$quantites[] = $adhesion->getContrat_quantites_AsString( $date );
			$prices[]    = $adhesion->getContrat_quantites_Price( $date );
		}
		$data[] = array(
			'panier'    => implode( ', ', $paniers ),
			'lieu'      => "<a href='{$lieu->getPermalink()}'>{$lieu->getShortName()}</a>",
			'quantite'  => implode( ', ', $quantites ),
			'price'     => implode( ' + ', $prices ),
			'adherent'  => $ad->getAdherent()->getDisplay( $show_options ),
			//"<a href='mailto:{$ad->getAdherent()->getUser()->user_email}'>" . $ad->getAdherent()->getDisplayName() . '</a> (' . $ad->getAdherent()->getTelephone() . ')',
			'repreneur' => $repreneur,
			'message'   => $ad->getMessage(),
			'date'      => array(
				'display' => date_i18n( 'd/m/Y', $date ),
				'value'   => $date
			),
			'state'     => $state,
		);
	}

	ob_start();

	$table_options['initComplete'] = 'function() { amapress_init_front_end_ajax_buttons(); }';

	amapress_echo_datatable( $id, $columns, $data, $table_options );
	$content = ob_get_clean();

	return $content;
}


//add_filter('amapress_get_user_infos_content_echange_paniers', 'amapress_get_user_infos_content_echange_paniers', 10, 2);
function amapress_user_paniers_intermittents_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

//    amapress_handle_action_messages();

	$adhs = AmapressPaniers::getPanierIntermittents(
		array(
			'adherent' => amapress_current_user_id()
		)
	);

	return amapress_get_paniers_intermittents_table( 'my-echanges', $adhs,
		function ( $state, $status, $adh ) {
			if ( 'to_exchange' == $status || 'exch_valid_wait' == $status || 'exchanged' == $status ) {
				$ad    = $adh[0];
				$id    = "i{$ad->getDate()}-{$ad->getAdherent()->ID}-{$ad->getRealLieu()->ID}";
				$state .= '<div class="cancel-echange-panier amapress-ajax-parent"><h4>Motif d\'annulation</h4><textarea id="' . $id . '"></textarea><br/>';
				$state .= '<button type="button" class="btn btn-default amapress-ajax-button annuler-echange-panier" 
				data-message="val:#' . $id . '" data-confirm="Etes-vous sûr d\'annuler votre proposition?" data-action="annuler_adherent" data-panier="' . implode( ',', array_map( function ( $a ) {
						return $a->ID;
					}, $adh ) ) . '">Annuler échange</button></div>';
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
					$state .= '<div/>';
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

//	$columns = array(
//		array(
//			'responsivePriority' => 1,
//			'title'              => 'Date',
//			'data'               => array(
//				'_'    => 'date.display',
//				'sort' => 'date.value',
//			)
//		),
//		array(
//			'responsivePriority' => 4,
//			'title'              => 'Panier',
//			'data'               => array(
//				'_'    => 'panier',
//				'sort' => 'panier',
//			)
//		),
//		array(
//			'title' => 'Quantité',
//			'data'  => array(
//				'_'    => 'quantite',
//				'sort' => 'quantite',
//			)
//		),
//		array(
//			'title' => 'Prix',
//			'data'  => array(
//				'_'    => 'price',
//				'sort' => 'price',
//			)
//		),
//		array(
//			'responsivePriority' => 2,
//			'title'              => 'Repreneur',
//			'data'               => array(
//				'_'    => 'repreneur',
//				'sort' => 'repreneur',
//			)
//		),
//		array(
//			'responsivePriority' => 2,
//			'title'              => 'Message',
//			'data'               => array(
//				'_'    => 'message',
//				'sort' => 'message',
//			)
//		),
//		array(
//			'responsivePriority' => 3,
//			'title'              => 'Etat',
//			'data'               => array(
//				'_'    => 'state',
//				'sort' => 'state',
//			)
//		),
//	);
//
//	$ahs_by_date = array_group_by( $adhs,
//		function ( $a ) {
//			/** @var AmapressIntermittence_panier $a */
//			return "{$a->getDate()}-{$a->getAdherent()->ID}-{$a->getRealLieu()->ID}";
//		} );
//	$data        = array();
//	foreach ( $ahs_by_date as $adh ) {
//		/** @var AmapressIntermittence_panier[] $adh */
//		$ad          = $adh[0];
//		$date        = $ad->getDate();
//		$dist        = AmapressPaniers::getDistribution( $date, $ad->getLieu()->ID );
//		$status      = $ad->getStatus();
//		$status_text = $ad->getStatusDisplay();
//		$state       = "<span class='$status'>$status_text</span>";
//		if ( ! isset( $show_columns['for_print'] ) || $show_columns['for_print'] === false ) {
//
//		}
//		$lieu      = ( $dist->getLieuSubstitution() ? $dist->getLieuSubstitution() : $dist->getLieu() );
//		$repreneur = '';
//		if ( $ad->getRepreneur() != null ) {
//			$repreneur = $ad->getRepreneur()->getDisplay(
//				array( 'show_tel' => 'force', 'show_email' => 'force' )
//			); //"<a href='mailto:{$ad->getRepreneur()->getUser()->user_email}'>" . $ad->getRepreneur()->getDisplayName() . '</a> (' . $ad->getRepreneur()->getTelephone() . ')';
//		}
//		$paniers   = array();
//		$quantites = array();
//		$prices    = array();
//		foreach ( $adh as $a ) {
//			$adhesions = AmapressContrats::get_user_active_adhesion( $a->getAdherent()->ID, $a->getContrat_instance()->ID );
//			/** @var AmapressAdhesion $adhesion */
//			$adhesion = array_shift( $adhesions );
//
//			$paniers[]   = "<a href='{$dist->getPermalink()}'>{$a->getContrat_instance()->getModel()->getTitle()}</a>";
//			$quantites[] = $adhesion->getContrat_quantites_AsString( $date );
//			$prices[]    = $adhesion->getContrat_quantites_Price( $date );
//		}
//		$data[] = array(
//			'panier'    => implode( ', ', $paniers ),
//			'lieu'      => "<a href='{$lieu->getPermalink()}'>{$lieu->getShortName()}</a>",
//			'quantite'  => implode( ', ', $quantites ),
//			'price'     => implode( ' + ', $prices ),
//			'message'   => $adhesion->getMessage(),
//			'adherent'  => $ad->getAdherent()->getDisplay(
//				array( 'show_tel' => 'force', 'show_email' => 'force' )
//			),
//			//"<a href='mailto:{$ad->getAdherent()->getUser()->user_email}'>" . $ad->getAdherent()->getDisplayName() . '</a> (' . $ad->getAdherent()->getTelephone() . ')',
//			'repreneur' => $repreneur,
//			'date'      => array(
//				'display' => date_i18n( 'd/m/Y', $date ),
//				'value'   => $date
//			),
//			'state'     => $state,
//		);
//	}
//
//	ob_start();
//
//	$table_options['initComplete'] = 'function() { amapress_init_front_end_ajax_buttons(); }';
//
//	amapress_echo_datatable( 'my-echanges', $columns, $data, $table_options );
//	$content = ob_get_contents();
//	ob_clean();
//
//	return $content;
}

function amapress_intermittent_paniers_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

//    amapress_handle_action_messages();

	$adhs = AmapressPaniers::getPanierIntermittents(
		array(
			'repreneur' => amapress_current_user_id()
		)
	);

	return amapress_get_paniers_intermittents_table( 'my-recups', $adhs,
		function ( $state, $status, $adh ) {
			if ( 'exch_valid_wait' == $status || 'exchanged' == $status ) {
				$state = '<span style="display: block">' . ( 'exch_valid_wait' == $status ? '<strong>En attente de validation</strong>' : 'A récupérer' ) . '</span>';
				$state .= '<button type="button" class="btn btn-default amapress-ajax-button annuler-echange-repreneur" data-confirm="Etes-vous sûr de vouloir annuler l\'échange ?" data-action="annuler_repreneur" data-panier="' . implode( ',', array_map( function ( $a ) {
						return $a->ID;
					}, $adh ) ) . '">Annuler échange</button>';
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

//	$columns = array(
//		array(
//			'responsivePriority' => 1,
//			'title'              => 'Date',
//			'data'               => array(
//				'_'    => 'date.display',
//				'sort' => 'date.value',
//			)
//		),
//		array(
//			'responsivePriority' => 2,
//			'title'              => 'Adhérent',
//			'data'               => array(
//				'_'    => 'adherent',
//				'sort' => 'adherent',
//			)
//		),
//		array(
//			'responsivePriority' => 3,
//			'title'              => 'Message',
//			'data'               => array(
//				'_'    => 'message',
//				'sort' => 'message',
//			)
//		),
//		array(
//			'responsivePriority' => 4,
//			'title'              => 'Panier',
//			'data'               => array(
//				'_'    => 'panier',
//				'sort' => 'panier',
//			)
//		),
//		array(
//			'title' => 'Quantité',
//			'data'  => array(
//				'_'    => 'quantite',
//				'sort' => 'quantite',
//			)
//		),
//		array(
//			'title' => 'Prix',
//			'data'  => array(
//				'_'    => 'price',
//				'sort' => 'price',
//			)
//		),
//		array(
//			'title' => 'Etat',
//			'data'  => array(
//				'_'    => 'state',
//				'sort' => 'state',
//			)
//		),
//	);
//	$data    = array();
//	foreach ( $adhs as $ad ) {
//		$date      = $ad->getDate();
//		$dist      = AmapressPaniers::getDistribution( $date, $ad->getLieu()->ID );
//		$adhesions = AmapressContrats::get_user_active_adhesion( $ad->getAdherent()->ID, $ad->getContrat_instance()->ID );
//		/** @var AmapressAdhesion $adhesion */
//		$adhesion    = array_shift( $adhesions );
//		$status      = $ad->getStatus();
//		$status_text = $ad->getStatusDisplay();
//		$state       = "<span class='$status'>$status_text</span>";
//		if ( ! isset( $show_columns['for_print'] ) || $show_columns['for_print'] === false ) {
//
//		}
//
//		$data[] = array(
//			'panier'    => '<a href="' . esc_attr( $dist->getPermalink() ) . '">' . esc_html( $ad->getContrat_instance()->getModel()->getTitle() ) . '</a>',
//			'quantite'  => $adhesion->getContrat_quantites_AsString( $date ),
//			'price'     => $adhesion->getContrat_quantites_Price( $date ),
//			'message'   => $adhesion->getMessage(),
//			'date'      => array(
//				'display' => date_i18n( 'd/m/Y', $date ),
//				'value'   => $date
//			),
//			'adherent'  => $ad->getAdherent()->getDisplay(
//				array( 'show_tel' => 'force', 'show_email' => 'force' )
//			),
//			'repreneur' => $ad->getRepreneur() != null ? $ad->getRepreneur()->getDisplay(
//				array( 'show_tel' => 'force', 'show_email' => 'force' )
//			) : '',
//			'state'     => $state,
//		);
//	}
//
//	ob_start();
//	amapress_echo_datatable( 'my-recups', $columns, $data );
//	$content = ob_get_contents();
//	ob_clean();
//
//	return $content;
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