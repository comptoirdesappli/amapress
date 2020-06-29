<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
function amapress_prepare_in_sql( $in ) {
	return implode( ',', amapress_prepare_in( $in ) );
}

function amapress_prepare_in( $in ) {
	if ( ! is_array( $in ) ) {
		$in = [ $in ];
	}
	$in = array_unique( array_filter( $in, function ( $v ) {
		return ! empty( $v );
	} ) );
	if ( empty( $in ) ) {
		return array( 0 );
	}

	return $in;
}

function amapress_prepare_like_in_array( $key, $value ) {
	return array(
		'relation' => 'OR',
		array(
			'key'     => $key,
			'value'   => '"' . $value . '"',
			'compare' => 'like'
		),
		array(
			'key'     => $key,
			'value'   => 'i:' . $value . ';',
			'compare' => 'like'
		),
	);
}

function amapress_add_meta_query( WP_Query $query, $meta_query ) {
	$meta = $query->get( 'meta_query' );
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}
	$meta = array_merge( $meta, $meta_query );
	$query->set( 'meta_query', $meta );
}

function amapress_add_meta_user_query( WP_User_Query $query, $meta_query ) {
	$meta = $query->get( 'meta_query' );
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}
	$meta = array_merge( $meta, $meta_query );
	$query->set( 'meta_query', $meta );
}

function amapress_add_tax_query( WP_Query $query, $tax_query ) {
	$tax = $query->tax_query;
	if ( ! is_array( $tax ) ) {
		$tax = array();
	}
	$tax = array_merge( $tax, $tax_query );
	$query->set( 'tax_query', $tax );
}

add_action( 'pre_get_posts', 'amapress_filter_posts' );
function amapress_filter_posts( WP_Query $query ) {
	if ( ! amapress_is_user_logged_in() && ! $query->is_main_query() ) {
		global $amapress_no_filter_amps_lo;
		if ( ! $amapress_no_filter_amps_lo ) {
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					array(
						'key'     => "amps_lo",
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => "amps_lo",
						'value'   => 0,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				)
			) );
		}
	}

	$pts = AmapressEntities::getPostTypes();
	$pt  = amapress_simplify_post_type( isset( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : '' );
	if ( is_array( $pt ) ) {
		return;
	}
	if ( isset( $query->query_vars['post_type'] ) ) {

		if ( $query->is_main_query() && array_key_exists( $pt, $pts ) ) {
			if ( isset( $pts[ $pt ]['logged_or_public'] ) ) {
				$logged_or_public = $pts[ $pt ]['logged_or_public'];
				if ( apply_filters( "amapress_is_{$pt}_logged_or_public", $logged_or_public, $query ) === true
				     && ! amapress_is_user_logged_in()
				) {
					amapress_add_meta_query( $query, array(
						array(
							'relation' => 'AND',
							array(
								'key'     => "amapress_{$pt}_public",
								'compare' => 'EXISTS',
							),
							array(
								'key'     => "amapress_{$pt}_public",
								'value'   => 1,
								'compare' => '=',
							),
						),
					) );
				}
			}
		}
		do_action( "amapress_{$pt}_query_filter", $query );
	}

	global $amapress_no_filter_referent;
	if ( ! $amapress_no_filter_referent
	     && ( ! isset( $query->query_vars['amapress_no_filter_referent'] ) || ! $query->query_vars['amapress_no_filter_referent'] )
	     && is_admin() && amapress_can_access_admin() && ! amapress_is_admin_or_responsable() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		if ( count( $refs ) > 0 ) {
			$meta = array();
			if ( $pt == 'producteur' ) {
				$post__in = $query->get( 'post__in' );
				if ( is_array( $post__in ) ) {
					$post__in = array();
				}
				foreach ( $refs as $r ) {
					$post__in[] = intval( $r['producteur'] );
				}
				$post__in = array_unique( $post__in );
				$query->set( 'post__in', $post__in );
			} else if ( $pt == 'produit' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_produit_producteur",
						'value'   => amapress_prepare_in( $r['producteur'] ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					);
					$meta[] = amapress_prepare_like_in_array( 'amapress_produit_producteur', $r['producteur'] );
				}

			} else if ( $pt == 'panier' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_contrat_instance",
						'value'   => amapress_prepare_in( $r['contrat_instance_ids'] ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					);
				}
			} else if ( $pt == 'contrat_paiement' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_contrat_instance",
						'value'   => amapress_prepare_in( $r['contrat_instance_ids'] ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					);
				}
			} else if ( $pt == 'adhesion' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_contrat_instance",
						'value'   => amapress_prepare_in( $r['contrat_instance_ids'] ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					);
				}
			} else if ( $pt == 'contrat' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_producteur",
						'value'   => $r['producteur'],
						'compare' => '=',
						'type'    => 'NUMERIC'
					);
				}
			} else if ( $pt == 'contrat_instance' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_model",
						'value'   => amapress_prepare_in( $r['contrat_ids'] ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					);
				}
			}
			if ( count( $meta ) > 0 ) {
				if ( count( $meta ) > 1 ) {
					amapress_add_meta_query( $query, array(
						array_merge(
							array( 'relation' => 'OR' ),
							$meta
						)
					) );
				} else {
					amapress_add_meta_query( $query, array(
						$meta
					) );
				}
			}
		}
	}
	if ( is_admin() && amapress_current_user_can( 'producteur' ) ) {
		$meta = [];
		if ( $pt == 'producteur' ) {
			$meta[] = array(
				'key'     => "amapress_producteur_user",
				'value'   => amapress_current_user_id(),
				'compare' => '=',
				'type'    => 'NUMERIC'
			);
		} else if ( $pt == 'produit' ) {
			$prod_ids = AmapressProducteur::getAllIdsByUser( amapress_current_user_id() );
			foreach ( $prod_ids as $id ) {
				$meta[] = array(
					'key'     => "amapress_produit_producteur",
					'value'   => amapress_prepare_in( $id ),
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				);
				$meta[] = amapress_prepare_like_in_array( 'amapress_produit_producteur', $id );
			}
		} else if ( $pt == 'contrat' ) {
			$prod_ids = AmapressProducteur::getAllIdsByUser( amapress_current_user_id() );
			$meta[]   = array(
				'key'     => "amapress_contrat_producteur",
				'value'   => amapress_prepare_in( $prod_ids ),
				'compare' => 'IN',
				'type'    => 'NUMERIC'
			);
		} else if ( $pt == 'contrat_instance' ) {
			$prod_ids    = AmapressProducteur::getAllIdsByUser( amapress_current_user_id() );
			$contrat_ids = [];
			foreach ( $prod_ids as $prod_id ) {
				foreach ( AmapressContrats::get_contrats( $prod_id, false, false ) as $contrat ) {
					$contrat_ids[] = $contrat->ID;
				}
			}
			$meta[] = array(
				'key'     => "amapress_contrat_instance_model",
				'value'   => amapress_prepare_in( $contrat_ids ),
				'compare' => 'IN',
				'type'    => 'NUMERIC'
			);
		} else if ( $pt == 'panier' ) {
			$prod_ids             = AmapressProducteur::getAllIdsByUser( amapress_current_user_id() );
			$contrat_instance_ids = [];
			foreach ( $prod_ids as $prod_id ) {
				foreach ( AmapressContrats::get_contrats( $prod_id, false, false ) as $contrat ) {
					foreach ( AmapressContrats::get_all_contrat_instances_by_contrat_ids( $contrat->ID ) as $contrat_instance_id ) {
						$contrat_instance_ids[] = $contrat_instance_id;
					}
				}
			}
			$meta[] = array(
				'key'     => "amapress_panier_contrat_instance",
				'value'   => amapress_prepare_in( $contrat_instance_ids ),
				'compare' => 'IN',
				'type'    => 'NUMERIC'
			);
		}
		if ( count( $meta ) > 0 ) {
			if ( count( $meta ) > 1 ) {
				amapress_add_meta_query( $query, array(
					array_merge(
						array( 'relation' => 'OR' ),
						$meta
					)
				) );
			} else {
				amapress_add_meta_query( $query, array(
					$meta
				) );
			}
		}
	}

//    if (!empty($_GET['orderby']) && !empty($_GET['orderby'])) {
//
//    }
//    if ($query->query_vars['orderby']=='amapress_adhesion_status') {
//        var_dump($query->query_vars);
//        die();
//    }

	if ( ! empty( $query->query_vars['amapress_producteur'] ) ) {
		$amapress_producteur = Amapress::resolve_post_id( $query->query_vars['amapress_producteur'], AmapressProducteur::INTERNAL_POST_TYPE );
		if ( $pt == 'contrat' || $pt == 'visite' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_producteur",
					'value'   => $amapress_producteur,
					'compare' => '=',
				)
			) );
		} else if ( $pt == 'produit' ) {
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					array(
						'key'     => "amapress_produit_producteur",
						'value'   => $amapress_producteur,
						'compare' => '=',
					),
					amapress_prepare_like_in_array( 'amapress_produit_producteur', $amapress_producteur )
				)
			) );
		} else if ( 'contrat_instance' == $pt ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_model",
					'value'   => amapress_prepare_in( AmapressContrats::get_contrat_ids( $amapress_producteur, false ) ),
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_status'] ) ) {
		$amapress_status = $query->query_vars['amapress_status'];
		if ( AmapressDistribution::POST_TYPE == $pt ) {
			if ( 'change_lieu' == $amapress_status ) {
				amapress_add_meta_query( $query, array(
					array(
						array(
							'key'     => "amapress_{$pt}_lieu_substitution",
							'compare' => 'EXISTS',
						),
						array(
							'key'     => "amapress_{$pt}_lieu_substitution",
							'value'   => 0,
							'compare' => '>',
							'type'    => 'NUMERIC',
						),
					)
				) );
			} else if ( 'change_hours' == $amapress_status ) {
				amapress_add_meta_query( $query, array(
					array(
						'relation' => 'OR',
						array(
							array(
								'key'     => "amapress_{$pt}_heure_debut_spec",
								'compare' => 'EXISTS',
							),
							array(
								'key'     => "amapress_{$pt}_heure_debut_spec",
								'value'   => 0,
								'compare' => '>',
								'type'    => 'NUMERIC',
							),
						),
						array(
							array(
								'key'     => "amapress_{$pt}_heure_fin_spec",
								'compare' => 'EXISTS',
							),
							array(
								'key'     => "amapress_{$pt}_heure_fin_spec",
								'value'   => 0,
								'compare' => '>',
								'type'    => 'NUMERIC',
							),
						),
					)
				) );
			} else if ( 'change_paniers' == $amapress_status ) {
				$paniers = AmapressPanier::get_delayed_paniers();
				$dates   = [];
				foreach ( $paniers as $panier ) {
					$dates[] = $panier->getDate();
					if ( $panier->isDelayed() ) {
						$dates[] = $panier->getDateSubst();
					}
				}
				$dates = array_unique( $dates );
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => $dates,
						'compare' => 'IN',
						'type'    => 'NUMERIC',
					),
				) );
			}
		} else {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_status",
					'value'   => $amapress_status,
					'compare' => '=',
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_pmt_type'] ) ) {
		$amapress_pmt_type = $query->query_vars['amapress_pmt_type'];
		$suffix            = 'type';
		if ( AmapressAdhesion::POST_TYPE == $pt ) {
			$suffix = 'pmt_type';
		}
		if ( 'chq' == $amapress_pmt_type ) {
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					array(
						'key'     => "amapress_{$pt}_$suffix",
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => "amapress_{$pt}_$suffix",
						'value'   => $amapress_pmt_type,
						'compare' => '=',
					),
				)
			) );
		} else {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_$suffix",
					'value'   => $amapress_pmt_type,
					'compare' => '=',
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_adhesion_period'] ) ) {
		if ( $pt == 'adhesion_paiement' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_period",
					'value'   => $query->query_vars['amapress_adhesion_period'],
					'compare' => '=',
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_recette_produits'] ) ) {
		$amapress_recette_produits = array_map( function ( $a ) {
			return Amapress::resolve_post_id( $a, AmapressProduit::INTERNAL_POST_TYPE );
		}, explode( ',', $query->query_vars['amapress_recette_produits'] ) );
//        var_dump($amapress_recette_produits);
		if ( $pt == 'recette' ) {
			$arr = array( 'relation' => 'OR' );
			foreach ( $amapress_recette_produits as $prod ) {
				$arr[] = amapress_prepare_like_in_array( "amapress_{$pt}_produits", $prod );
			}
			amapress_add_meta_query( $query, array(
				array(
					$arr
				)
			) );
		}
	}
//    if (!empty($query->query_vars['amapress_recette_produits'])) {
//        $amapress_recette_produits = explode(',', $query->query_vars['amapress_recette_produits']);
//        if ($pt == 'recette') {
//            $arr = array('relation' => 'OR');
//            foreach ($amapress_recette_produits as $prod) {
//                $arr[] = array(
//                    'key' => "amapress_{$pt}_produits",
//                    'value' => '"'.Amapress::resolve_post_id($prod,'amps_produit').'"',
//                    'compare' => 'like',
//                );
//            }
//            amapress_add_meta_query($query, array(
//                array(
//                    $arr
//                )
//            ));
//        }
//    }
	if ( ! empty( $query->query_vars['amapress_event_tag'] ) ) {
		$amapress_event_tag = explode( ',', $query->query_vars['amapress_event_tag'] );
		if ( $pt == AmapressAmap_event::POST_TYPE ) {
			for ( $i = 0; $i < count( $amapress_event_tag ); $i ++ ) {
				$amapress_event_tag[ $i ] = Amapress::resolve_tax_id( $amapress_event_tag[ $i ], AmapressAmap_event::CATEGORY );
			}

			amapress_add_tax_query( $query, array(
				array(
					array(
						'taxonomy' => AmapressAmap_event::CATEGORY,
						'field'    => 'term_id',
						'terms'    => amapress_prepare_in( $amapress_event_tag ),
						'operator' => 'IN',
						'type'     => 'NUMERIC'
					)
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_recette_tag'] ) ) {
		$amapress_recette_tags = explode( ',', $query->query_vars['amapress_recette_tag'] );
		if ( $pt == 'recette' ) {
			for ( $i = 0; $i < count( $amapress_recette_tags ); $i ++ ) {
				$amapress_recette_tags[ $i ] = Amapress::resolve_tax_id( $amapress_recette_tags[ $i ], 'amps_recette_category' );
			}

			amapress_add_tax_query( $query, array(
				array(
					array(
						'taxonomy' => 'amps_recette_category',
						'field'    => 'term_id',
						'terms'    => amapress_prepare_in( $amapress_recette_tags ),
						'operator' => 'IN',
						'type'     => 'NUMERIC'
					)
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_recette_tag_not_in'] ) ) {
		$amapress_recette_tags = explode( ',', $query->query_vars['amapress_recette_tag_not_in'] );
		if ( $pt == 'recette' ) {
			for ( $i = 0; $i < count( $amapress_recette_tags ); $i ++ ) {
				$amapress_recette_tags[ $i ] = Amapress::resolve_tax_id( $amapress_recette_tags[ $i ], 'amps_recette_category' );
			}
			amapress_add_tax_query( $query, array(
				array(
					array(
						'taxonomy' => 'amps_recette_category',
						'field'    => 'term_id',
						'terms'    => $amapress_recette_tags,
						'operator' => 'NOT IN',
					)
				)
			) );
		}
	}

	if ( ! empty( $query->query_vars['amapress_produit_tag'] ) ) {
		$amapress_produit_tag = explode( ',', $query->query_vars['amapress_produit_tag'] );
		if ( $pt == AmapressProduit::POST_TYPE ) {
			for ( $i = 0; $i < count( $amapress_produit_tag ); $i ++ ) {
				$amapress_produit_tag[ $i ] = Amapress::resolve_tax_id( $amapress_produit_tag[ $i ], AmapressProduit::CATEGORY );
			}
			amapress_add_tax_query( $query, array(
				array(
					array(
						'taxonomy' => AmapressProduit::CATEGORY,
						'field'    => 'term_id',
						'terms'    => amapress_prepare_in( $amapress_produit_tag ),
						'operator' => 'IN',
						'type'     => 'NUMERIC'
					)
				)
			) );
		}
	}

	if ( ! empty( $query->query_vars['amapress_produit_tag_not_in'] ) ) {
		$amapress_recette_tags = explode( ',', $query->query_vars['amapress_produit_tag_not_in'] );
		if ( $pt == 'produit' ) {
			for ( $i = 0; $i < count( $amapress_recette_tags ); $i ++ ) {
				$amapress_recette_tags[ $i ] = Amapress::resolve_tax_id( $amapress_recette_tags[ $i ], 'amps_produit_category' );
			}
			amapress_add_tax_query( $query, array(
				array(
					array(
						'taxonomy' => 'amps_produit_category',
						'field'    => 'term_id',
						'terms'    => $amapress_recette_tags,
						'operator' => 'NOT IN',
					)
				)
			) );
		}
	}

	if ( ! empty( $query->query_vars['amapress_produit_recette'] ) ) {
		$amapress_recette = Amapress::resolve_post_id( $query->query_vars['amapress_produit_recette'], 'amps_recette' );
		if ( $pt == 'produit' ) {
			$prods = array_map( 'intval', Amapress::get_post_meta_array( $amapress_recette, 'amapress_recette_produits' ) );
			$query->set( 'post__in', $prods );
		}
	}
	if ( ! empty( $query->query_vars['amapress_contrat_inst'] ) ) {
		$amapress_contrat_instnace = array_map(
			function ( $id ) {
				return Amapress::resolve_post_id(
					$id,
					'amps_contrat_inst' );
			},
			explode( ',', $query->query_vars['amapress_contrat_inst'] )
		);
		if ( $pt == 'adhesion' || $pt == 'intermittence_panier' || 'panier' == $pt ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_contrat_instance",
					'value'   => $amapress_contrat_instnace,
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				)
			) );
		} else if ( $pt == 'contrat_paiement' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_contrat_paiement_contrat_instance",
					'value'   => $amapress_contrat_instnace,
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				)
			) );
		} else if ( $pt == 'distribution' ) {
			$query_or = array();
			foreach ( $amapress_contrat_instnace as $contrat_inst ) {
				$cancelled_paniers_dates = array_map( function ( $p ) {
					/** @var AmapressPanier $p */
					return Amapress::start_of_day( $p->getDate() );
				}, AmapressPanier::get_delayed_paniers( $contrat_inst ) );
				$delayed_paniers_dates   = array_map( function ( $p ) {
					/** @var AmapressPanier $p */
					return Amapress::start_of_day( $p->getDateSubst() );
				}, AmapressPanier::get_delayed_paniers( $contrat_inst, null, null, [ 'delayed' ] ) );
				$query_or[]              =
					array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_distribution_date',
								'value'   => amapress_prepare_in( $delayed_paniers_dates ),
								'compare' => 'IN',
								'type'    => 'NUMERIC',
							),
							amapress_prepare_like_in_array( "amapress_distribution_contrats", $contrat_inst )
						),
						array(
							array(
								'key'     => 'amapress_distribution_date',
								'value'   => amapress_prepare_in( $cancelled_paniers_dates ),
								'compare' => 'NOT IN',
								'type'    => 'NUMERIC',
							),
						)
					);
			}
//			amapress_dump($query_or);
//			die();
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					$query_or
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_contrat_qt'] ) ) {
		$amapress_contrat_qt = Amapress::resolve_post_id( $query->query_vars['amapress_contrat_qt'], 'amps_contrat_quant' );
		if ( $pt == 'adhesion' ) {
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					array(
						'key'     => "amapress_{$pt}_contrat_quantite",
						'value'   => $amapress_contrat_qt,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					amapress_prepare_like_in_array( "amapress_{$pt}_contrat_quantite", $amapress_contrat_qt ),
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_contrat'] ) ) {
		$amapress_contrat = Amapress::resolve_post_id( $query->query_vars['amapress_contrat'], 'amps_contrat' );
		if ( $pt == 'adhesion' || $pt == 'intermittence_panier' || $pt == 'panier' ) {
			$active_contrat_insts = AmapressContrats::get_all_contrat_instances_by_contrat_ids( $amapress_contrat );
			if ( empty( $active_contrat_insts ) ) {
				$active_contrat_insts = array_map(
					function ( $id ) {
						return Amapress::resolve_post_id(
							$id,
							'amps_contrat_inst' );
					},
					explode( ',', $query->query_vars['amapress_contrat'] )
				);
			}
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_contrat_instance",
					'value'   => amapress_prepare_in( $active_contrat_insts ),
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				)
			) );
		} else if ( $pt == 'contrat_instance' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_model",
					'value'   => intval( $amapress_contrat ),
					'compare' => '=',
				)
			) );
		} else if ( $pt == 'distribution' ) {
//			$amapress_date        = get_query_var( 'amapress_date' );
//			$date                 = ( empty( $amapress_date ) || $amapress_date == 'active' ) ? null : ( is_int( $amapress_date ) ? intval( $amapress_date ) : DateTime::createFromFormat( 'Y-m-d', $amapress_date )->getTimestamp() );
			$active_contrat_insts = AmapressContrats::get_all_contrat_instances_by_contrat_ids( $amapress_contrat );
			if ( empty( $active_contrat_insts ) ) {
				$active_contrat_insts = array_map(
					function ( $id ) {
						return Amapress::resolve_post_id(
							$id,
							'amps_contrat_inst' );
					},
					explode( ',', $query->query_vars['amapress_contrat'] )
				);
			}
			$query_or = array();
			foreach ( $active_contrat_insts as $contrat_inst ) {
				$cancelled_paniers_dates = array_map( function ( $p ) {
					/** @var AmapressPanier $p */
					return Amapress::start_of_day( $p->getDate() );
				}, AmapressPanier::get_delayed_paniers( $contrat_inst ) );
				$delayed_paniers_dates   = array_map( function ( $p ) {
					/** @var AmapressPanier $p */
					return Amapress::start_of_day( $p->getDateSubst() );
				}, AmapressPanier::get_delayed_paniers( $contrat_inst, null, null, [ 'delayed' ] ) );
				$query_or[]              =
					array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_distribution_date',
								'value'   => amapress_prepare_in( $delayed_paniers_dates ),
								'compare' => 'IN',
								'type'    => 'NUMERIC',
							),
							amapress_prepare_like_in_array( "amapress_distribution_contrats", $contrat_inst )
						),
						array(
							array(
								'key'     => 'amapress_distribution_date',
								'value'   => amapress_prepare_in( $cancelled_paniers_dates ),
								'compare' => 'NOT IN',
								'type'    => 'NUMERIC',
							),
						)
					);
			}
//			amapress_dump($query_or);
//			die();
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					$query_or
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_post'] ) ) {
		$amapress_post = array_map( 'intval', explode( ',', $query->query_vars['amapress_post'] ) );
		$query->set( 'post__in', $amapress_post );
	}

	if ( ! empty( $query->query_vars['amapress_user'] ) ) {
		$amapress_user = Amapress::resolve_user_id( $query->query_vars['amapress_user'] );
		if ( $pt == 'adhesion' ) {
			$user_ids = AmapressContrats::get_related_users( $amapress_user );
//            var_dump($user_ids);
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					array(
						'key'     => "amapress_{$pt}_adherent",
						'value'   => amapress_prepare_in( $user_ids ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
					array(
						'key'     => "amapress_{$pt}_adherent2",
						'value'   => amapress_prepare_in( $user_ids ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
					array(
						'key'     => "amapress_{$pt}_adherent3",
						'value'   => amapress_prepare_in( $user_ids ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
				),
			) );
		} else if ( $pt == 'amap_event' || $pt == 'visite' || $pt == 'assemblee' ) {
			amapress_add_meta_query( $query, array(
				amapress_prepare_like_in_array( "amapress_{$pt}_participants", $amapress_user ),
			) );
		} else if ( $pt == 'distribution' ) {
			amapress_add_meta_query( $query, array(
				amapress_prepare_like_in_array( "amapress_{$pt}_responsables", $amapress_user ),
			) );
//        } else if ($pt == 'adhesion_intermittence') {
//            amapress_add_meta_query($query, array(
//                array(
//                    'key' => "amapress_{$pt}_user",
//                    'value' => $amapress_user,
//                    'compare' => '=',
//                ),
//            ));
		} else if ( $pt == 'adhesion_paiement' ) {
			$user_ids = AmapressContrats::get_related_users( $amapress_user );
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_user",
					'value'   => amapress_prepare_in( $user_ids ),
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				),
			) );
		} else if ( $pt == 'intermittence_panier' ) {
			$user_ids = AmapressContrats::get_related_users( $amapress_user );
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_adherent",
					'value'   => amapress_prepare_in( $user_ids ),
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				),
			) );
		} else if ( $pt == 'contrat_paiement' ) {
			$contrat_instance_ids = AmapressContrat_instance::getContratInstanceIdsForUser( $amapress_user );
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_contrat_paiement_contrat_instance",
					'value'   => amapress_prepare_in( $contrat_instance_ids ),
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				)
			) );
		}
	}

	if ( ! empty( $query->query_vars['amapress_lieu'] ) ) {
		$amapress_lieu = Amapress::resolve_post_id( $query->query_vars['amapress_lieu'], 'amps_lieu_distribution' );
		if ( $pt == 'adhesion' || $pt == 'amap_event' || $pt == 'assemblee_generale' || $pt == 'intermittence_panier' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_lieu",
					'value'   => $amapress_lieu,
					'compare' => '=',
				)
			) );
		} else if ( $pt == 'distribution' ) {
			amapress_add_meta_query( $query, array(
				array(
					'relation' => 'OR',
					array(
						'key'     => "amapress_{$pt}_lieu",
						'value'   => $amapress_lieu,
						'compare' => '=',
					),
					array(
						'key'     => "amapress_{$pt}_lieu_substitution",
						'value'   => $amapress_lieu,
						'compare' => '=',
					)
				)
			) );
		} else if ( $pt == 'contrat_instance' ) {
			amapress_add_meta_query( $query,
				array(
					amapress_prepare_like_in_array( "amapress_{$pt}_lieux", $amapress_lieu ),
				)
			);
		} else if ( $pt == 'contrat_paiement' ) {
			//TODO : what if past ?
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_contrat_paiement_adhesion",
					'value'   => amapress_prepare_in( AmapressContrats::get_active_adhesions_ids( null, null, $amapress_lieu ) ),
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_with_coadherents'] ) ) {
//        $amapress_role = $query->query_vars['amapress_role'];
		if ( $pt == 'adhesion' ) {
//            if ($amapress)
			amapress_add_meta_query( $query, array(
				array(
					array(
						'key'     => "amapress_{$pt}_adherent",
						'compare' => 'IN',
						'value'   => amapress_prepare_in( get_users(
							array(
								'fields'           => 'ids',
								'amapress_contrat' => 'with_coadherent',
							)
						) ),
					),
				),
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_date'] ) ) {
		$amapress_date = $query->query_vars['amapress_date'];
		if ( $amapress_date == 'lastyear' ) {
			if ( $pt == 'contrat_instance' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date_fin",
						'value'   => Amapress::start_of_day( Amapress::remove_a_year( amapress_time() ) ),
						'compare' => '>=',
					),
					array(
						'key'     => "amapress_{$pt}_date_fin",
						'value'   => Amapress::end_of_day( amapress_time() ),
						'compare' => '<=',
					),
				) );
			} else if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite'
			            || $pt == 'amap_event' || $pt == 'contrat_paiement' || $pt == 'intermittence_panier'
			) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => Amapress::start_of_day( Amapress::remove_a_year( amapress_time() ) ),
						'compare' => '>=',
					),
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => Amapress::end_of_day( amapress_time() ),
						'compare' => '<=',
					),
				) );
			} else if ( $pt == 'adhesion' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date_debut",
						'value'   => Amapress::start_of_day( Amapress::remove_a_year( amapress_time() ) ),
						'compare' => '>=',
					),
					array(
						'key'     => "amapress_{$pt}_date_debut",
						'value'   => Amapress::end_of_day( amapress_time() ),
						'compare' => '<=',
					),
				) );
			}
		} else if ( $amapress_date == 'past' ) {
			if ( $pt == 'contrat_instance' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date_fin",
						'value'   => Amapress::end_of_day( amapress_time() ),
						'compare' => '<=',
					),
				) );
			} else if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite'
			            || $pt == 'amap_event' || $pt == 'contrat_paiement' || $pt == 'intermittence_panier'
			) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => Amapress::end_of_day( amapress_time() ),
						'compare' => '<=',
					)
				) );
			} else if ( $pt == 'adhesion' ) {
				amapress_add_meta_query( $query, array(
					array(
						'relation' => 'OR',
						array(
							'key'     => "amapress_adhesion_contrat_instance",
							'value'   => amapress_prepare_in( AmapressContrats::get_active_contrat_instances_ids() ),
							'compare' => 'NOT IN',
							'type'    => 'NUMERIC',
						),
						array(
							'relation' => 'AND',
							array(
								'key'     => "amapress_adhesion_contrat_instance",
								'value'   => amapress_prepare_in( AmapressContrats::get_active_contrat_instances_ids() ),
								'compare' => 'IN',
								'type'    => 'NUMERIC',
							),
							array(
								'key'     => 'amapress_adhesion_date_fin',
								'value'   => 0,
								'compare' => '>',
								'type'    => 'NUMERIC',
							),
							array(
								'key'     => 'amapress_adhesion_date_fin',
								'compare' => 'EXISTS',
							),
							array(
								'key'     => 'amapress_adhesion_date_fin',
								'value'   => Amapress::end_of_day( amapress_time() ),
								'compare' => '<=',
								'type'    => 'NUMERIC',
							)
						)
					)
				) );
			}
		} else if ( $amapress_date == 'next' || $amapress_date == 'active' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite'
			     || $pt == 'amap_event' || $pt == 'contrat_paiement' || $pt == 'intermittence_panier'
			) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => Amapress::start_of_day( amapress_time() ),
						'compare' => '>=',
					)
				) );
			} else if ( $pt == 'contrat_instance' ) {
				amapress_add_meta_query( $query, array(
//TODO check
//                    array(
//                        'key' => "amapress_{$pt}_date_debut",
//                        'value' => Amapress::start_of_day(amapress_time()),
//                        'compare' => '<=',
//                    ),
					array(
						'key'     => "amapress_{$pt}_date_fin",
						'value'   => Amapress::end_of_day( AmapressContrats::renouvellementDelta( amapress_time() ) ),
						'compare' => '>=',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => "amapress_{$pt}_ended",
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => "amapress_{$pt}_ended",
							'value'   => 0,
							'compare' => '=',
						),
					),
				) );
			} else if ( $pt == 'message' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_message_associated_date",
						'value'   => Amapress::start_of_day( amapress_time() ),
						'compare' => '>=',
					)
				) );
			} else if ( $pt == 'adhesion_request' ) {
				amapress_add_meta_query( $query, array(
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_adhesion_request_status',
							'value'   => 'to_confirm',
							'compare' => '=',
						),
						array(
							'key'     => 'amapress_adhesion_request_status',
							'compare' => 'NOT EXISTS',
						),
					)
				) );
			} else if ( $pt == AmapressAdhesionPeriod::POST_TYPE ) {
				amapress_add_meta_query( $query, array(
					array(
						array(
							'key'     => 'amapress_adhesion_period_date_debut',
							'value'   => Amapress::start_of_day( amapress_time() ),
							'compare' => '<=',
						),
						array(
							'key'     => 'amapress_adhesion_period_date_fin',
							'value'   => Amapress::end_of_day( amapress_time() ),
							'compare' => '>=',
						),
					)
				) );
			} else if ( $pt == AmapressAdhesion_paiement::POST_TYPE ) {
				$adh_per    = AmapressAdhesionPeriod::getCurrent();
				$adh_per_id = $adh_per ? $adh_per->ID : 0;
				amapress_add_meta_query( $query, array(
					array(
						array(
							'key'     => 'amapress_adhesion_paiement_period',
							'value'   => $adh_per_id,
							'compare' => '=',
						),
					)
				) );
				//
			} else if ( $pt == 'adhesion' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_adhesion_contrat_instance",
						'value'   => amapress_prepare_in( AmapressContrats::get_active_contrat_instances_ids() ),
						'compare' => 'IN',
						'type'    => 'NUMERIC',
					),
				) );
				amapress_add_meta_query( $query, array(
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_adhesion_date_fin',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'amapress_adhesion_date_fin',
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						),
						array(
							'key'     => 'amapress_adhesion_date_fin',
							'value'   => Amapress::end_of_day( amapress_time() ),
							'compare' => '>=',
							'type'    => 'NUMERIC',
						),
					)
				) );
			}
		} else if ( $amapress_date == 'renew' ) {
			$contrat_inst_ids_to_renew = [];
			$contrat_instances         = AmapressContrats::get_active_contrat_instances();
			foreach ( $contrat_instances as $contrat_instance ) {
				if ( Amapress::start_of_day( $contrat_instance->getDate_fin() ) <= amapress_time() && $contrat_instance->canRenew() ) {
					$contrat_inst_ids_to_renew[] = $contrat_instance->ID;
				}
			}
			if ( $pt == 'contrat_instance' ) {
				$query->set( 'post__in', amapress_prepare_in( $contrat_inst_ids_to_renew ) );
			} else if ( $pt == 'adhesion' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_adhesion_contrat_instance",
						'value'   => amapress_prepare_in( $contrat_inst_ids_to_renew ),
						'compare' => 'IN',
						'type'    => 'NUMERIC',
					),
				) );
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_adhesion_renewed",
						'compare' => 'NOT EXISTS',
					),
				) );
				amapress_add_meta_query( $query, array(
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_adhesion_date_fin',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'amapress_adhesion_date_fin',
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						),
					)
				) );
			}
		} else if ( $amapress_date == 'ended' ) {
			if ( $pt == 'adhesion' ) {
				$active_contrat_instance_ids = AmapressContrats::get_active_contrat_instances_ids();
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_adhesion_contrat_instance",
						'value'   => amapress_prepare_in( $active_contrat_instance_ids ),
						'compare' => 'IN',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'amapress_adhesion_date_fin',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'amapress_adhesion_date_fin',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'amapress_adhesion_date_fin',
						'value'   => Amapress::end_of_day( amapress_time() ),
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				) );
			}
		} else if ( $amapress_date == 'today' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::start_of_day( amapress_time() ),
							Amapress::end_of_day( amapress_time() ),
						),
						'compare' => 'BETWEEN',
					)
				) );
			}
		} else if ( $amapress_date == 'lastweek' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::add_a_week( Amapress::start_of_week( amapress_time() ), - 1 ),
							Amapress::add_a_week( Amapress::end_of_week( amapress_time() ), - 1 ),
						),
						'compare' => 'BETWEEN',
					)
				) );
			}
		} else if ( $amapress_date == 'thisweek' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::start_of_week( amapress_time() ),
							Amapress::end_of_week( amapress_time() ),
						),
						'compare' => 'BETWEEN',
					)
				) );
			}
		} else if ( $amapress_date == 'foraweek' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::start_of_day( amapress_time() ),
							Amapress::add_a_week( Amapress::start_of_day( amapress_time() ) ),
						),
						'compare' => 'BETWEEN',
					)
				) );
			}
		} else if ( $amapress_date == 'nextweek' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::add_a_week( Amapress::start_of_week( amapress_time() ) ),
							Amapress::add_a_week( Amapress::end_of_week( amapress_time() ) ),
						),
						'compare' => 'BETWEEN',
					)
				) );
			}
		} else if ( $amapress_date == 'thismonth' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::start_of_month( amapress_time() ),
							Amapress::end_of_month( amapress_time() ),
						),
						'compare' => 'BETWEEN',
					)
				) );
				//echo (date('d m Y', end_of_month(time())));
			}
		} else if ( $amapress_date == 'nextmonth' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::add_a_month( Amapress::start_of_month( amapress_time() ) ),
							Amapress::add_a_month( Amapress::end_of_month( amapress_time() ) ),
						),
						'compare' => 'BETWEEN',
					)
				) );
				//echo (date('d m Y', end_of_month(time())));
			}
		} else if ( $amapress_date == 'prevmonth' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::add_a_month( Amapress::start_of_month( amapress_time() ), - 1 ),
							Amapress::add_a_month( Amapress::end_of_month( amapress_time() ), - 1 ),
						),
						'compare' => 'BETWEEN',
					)
				) );
				//echo (date('d m Y', end_of_month(time())));
			}
		} else if ( $amapress_date == 'foramonth' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::start_of_week( amapress_time() ),
							Amapress::add_a_month( Amapress::start_of_week( amapress_time() ) ),
						),
						'compare' => 'BETWEEN',
					)
				) );
			}
		} else {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' || $pt == 'contrat_instance' || $pt == 'adhesion' ) {
				$start_date = $end_date = null;
				$date       = DateTime::createFromFormat( 'Y-m-d', $amapress_date );
				if ( $date ) {
					$date       = $date->getTimestamp();
					$start_date = Amapress::start_of_day( $date );
					$end_date   = Amapress::end_of_day( $date );
				} else {
					$date = DateTime::createFromFormat( 'Y-m', $amapress_date );
					if ( $date ) {
						$date       = $date->getTimestamp();
						$start_date = Amapress::start_of_month( $date );
						$end_date   = Amapress::end_of_month( $date );
					} else {
						$date = DateTime::createFromFormat( 'Y', $amapress_date );
						if ( $date ) {
							$date       = $date->getTimestamp();
							$start_date = Amapress::start_of_year( $date );
							$end_date   = Amapress::end_of_year( $date );
						}
					}
				}

				if ( $start_date && $end_date ) {
					if ( $pt == 'contrat_instance' ) {
						amapress_add_meta_query( $query, array(
							array(
								'relation' => 'OR',
								array(
									'relation' => 'AND',
									array(
										'key'     => "amapress_{$pt}_date_debut",
										'value'   => array(
											$start_date,
											$end_date,
										),
										'compare' => 'BETWEEN',
									),
									array(
										'key'     => "amapress_{$pt}_date_fin",
										'value'   => array(
											$start_date,
											$end_date,
										),
										'compare' => 'BETWEEN',
									)
								),
								array(
									'relation' => 'AND',
									array(
										'key'     => "amapress_{$pt}_date_debut",
										'value'   => $start_date,
										'compare' => '<=',
									),
									array(
										'key'     => "amapress_{$pt}_date_fin",
										'value'   => $end_date,
										'compare' => '>=',
									)
								),
							)
						) );
					} else if ( 'adhesion' == $pt ) {
						$active_contrat_instance_ids = AmapressContrats::get_active_contrat_instances_ids( null, $start_date, true, false );
						amapress_add_meta_query( $query, array(
							array(
								'key'     => "amapress_adhesion_contrat_instance",
								'value'   => amapress_prepare_in( $active_contrat_instance_ids ),
								'compare' => 'IN',
								'type'    => 'NUMERIC',
							),
							array(
								'relation' => 'OR',
								array(
									'key'     => 'amapress_adhesion_date_fin',
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => 'amapress_adhesion_date_fin',
									'value'   => 0,
									'compare' => '=',
									'type'    => 'NUMERIC',
								),
								array(
									'key'     => 'amapress_adhesion_date_fin',
									'value'   => Amapress::end_of_day( $end_date ),
									'compare' => '>=',
									'type'    => 'NUMERIC',
								),
							)
						) );
					} else {
						amapress_add_meta_query( $query, array(
							array(
								'key'     => "amapress_{$pt}_date" . ( $pt == 'adhesion' ? '_debut' : '' ),
								'value'   => array(
									$start_date,
									$end_date,
								),
								'compare' => 'BETWEEN',
							)
						) );
					}
				}
			}
		}
	}
//	amapress_dump($query->get( 'meta_query' ));
}

add_action( 'pre_get_users', function ( WP_User_Query $uqi ) {
	global $pagenow;
	if ( is_admin() && 'users.php' == $pagenow ) {
		if ( empty( $_REQUEST['orderby'] ) ) {
			$uqi->query_vars['orderby'] = 'last_name';
		}
		if ( empty( $_REQUEST['order'] ) ) {
			$uqi->query_vars['order'] = 'ASC';
		}
	}
	if ( ! empty( $uqi->query_vars['amapress_info'] ) ) {
		$amapress_info = $uqi->query_vars['amapress_info'];
		if ( 'address_unk' == $amapress_info ) {
			amapress_add_meta_user_query( $uqi, array(
				array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_user_location_type',
						'compare' => "NOT EXISTS"
					),
					array(
						'key'     => 'amapress_user_location_type',
						'compare' => "=",
						'value'   => '',
					)
				)
			) );
		} else if ( 'phone_unk' == $amapress_info ) {
			amapress_add_meta_user_query( $uqi, array(
				array(
					array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_user_telephone',
								'compare' => "NOT EXISTS"
							),
							array(
								'key'     => 'amapress_user_telephone',
								'compare' => "=",
								'value'   => '',
							)
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_user_telephone2',
								'compare' => "NOT EXISTS"
							),
							array(
								'key'     => 'amapress_user_telephone2',
								'compare' => "=",
								'value'   => '',
							)
						),
//						array(
//							'relation' => 'OR',
//							array(
//								'key'     => 'amapress_user_telephone3',
//								'compare' => "NOT EXISTS"
//							),
//							array(
//								'key'     => 'amapress_user_telephone3',
//								'compare' => "=",
//								'value'   => '',
//							)
//						),
//						array(
//							'relation' => 'OR',
//							array(
//								'key'     => 'amapress_user_telephone4',
//								'compare' => "NOT EXISTS"
//							),
//							array(
//								'key'     => 'amapress_user_telephone4',
//								'compare' => "=",
//								'value'   => '',
//							)
//						),
					),
				)
			) );
		}
	}
	if ( isset( $uqi->query_vars['amapress_contrat'] ) ) {
		$amapress_contrat = $uqi->query_vars['amapress_contrat'];
		if ( $amapress_contrat == 'intermittent' ) {
			amapress_add_meta_user_query( $uqi, array(
				array(
					array(
						'key'     => 'amapress_user_intermittent',
						'compare' => "=",
						'value'   => 1,
						'type'    => 'NUMERIC',
					)
				)
			) );
		}
	}
	if ( isset( $uqi->query_vars['amapress_role'] ) ) {
		$amapress_role = $uqi->query_vars['amapress_role'];
		if ( $amapress_role == 'access_admin' ) {
			$uqi->query_vars['role__in'] = amapress_can_access_admin_roles();
		} else if ( $amapress_role == 'never_logged' ) {
			amapress_add_meta_user_query( $uqi, array(
				array(
					array(
						'key'     => 'last_login',
						'compare' => "NOT EXISTS"
					)
				)
			) );
//        } else if (strpos($amapress_role, 'amap_role_') === 0) {
//            $amap_role = substr($amapress_role, 10);
//            if ($amap_role == 'any') {
//                amapress_add_meta_user_query($uqi, array(
//                    array(
//                        'relation' => 'AND',
//                        array(
//                            'key' => "amapress_user_amap_roles",
//                            'value' => '"\d+"',
//                            'compare' => 'REGEXP',
//                        ),
//                        array(
//                            'key' => "amapress_user_amap_roles",
//                            'compare' => 'EXISTS',
//                        ),
//                    )
//                ));
//            } else {
//                amapress_add_meta_user_query($uqi, array(
//                    array(
//                        'key' => "amapress_user_amap_roles",
//                        'value' => '"' . $amap_role . '"',
//                        'compare' => 'LIKE',
//                    )
//                ));
//            }
		}
	}
} );

add_action( 'pre_user_query', function ( WP_User_Query $uqi ) {
	global $wpdb;
	$where                 = '';
	$lieu_already_filtered = false;
	if ( isset( $uqi->query_vars['amapress_role'] ) ) {
		$amapress_role = $uqi->query_vars['amapress_role'];
		if ( $amapress_role == 'referent_lieu' ) {
			$user_ids = array();
			foreach ( Amapress::get_lieux() as $lieu ) {
				if ( $lieu->getReferent() == null ) {
					continue;
				}
				$user_ids[] = $lieu->getReferent()->ID;
			}
			$user_id_sql = amapress_prepare_in_sql( $user_ids );
			$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
			//
		} else if ( $amapress_role == 'referent_producteur' ) {
			$user_ids = array();
			foreach ( AmapressContrats::get_contrats() as $contrat ) {
				$user_ids = array_merge( $user_ids, $contrat->getAllReferentsIds() );
			}
			$user_id_sql = amapress_prepare_in_sql( $user_ids );
			$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
		} else if ( $amapress_role == 'resp_distrib'
		            || $amapress_role == 'resp_distrib_next'
		            || $amapress_role == 'resp_distrib_month' ) {
			if ( isset( $uqi->query_vars['amapress_lieu'] ) ) {
				$amapress_lieu = $uqi->query_vars['amapress_lieu'];
				if ( ! is_array( $amapress_lieu ) ) {
					$amapress_lieu = array( $amapress_lieu );
				}
				$lieu_ids = array_map( function ( $l ) {
					return Amapress::resolve_post_id( $l, AmapressLieu_distribution::INTERNAL_POST_TYPE );
				}, $amapress_lieu );
			} else {
				$lieu_ids = [];
			}

			//resp_distrib
			$time       = amapress_time();
			$start_date = Amapress::start_of_week( $time );
			$end_date   = Amapress::end_of_week( $time );
			if ( $amapress_role == 'resp_distrib_next' ) {
				$time       = Amapress::add_a_week( $time );
				$start_date = Amapress::start_of_week( $time );
				$end_date   = Amapress::end_of_week( $time );
			} elseif ( $amapress_role == 'resp_distrib_month' ) {
				$start_date = Amapress::start_of_week( $time );
				$end_date   = Amapress::end_of_month( $time );
			}

			if ( empty( $lieu_ids ) ) {
				$user_ids = AmapressDistributions::getResponsablesBetween(
					$start_date, $end_date );
			} else {
				$user_ids = AmapressDistributions::getResponsablesLieuBetween(
					$start_date, $end_date, $lieu_ids );
			}

			$lieu_already_filtered = true;
			$user_id_sql           = amapress_prepare_in_sql( $user_ids );
			$where                 .= " AND $wpdb->users.ID IN ($user_id_sql)";
		} else if ( 'collectif' == $amapress_role ) {
			$user_ids    = array_merge(
				get_users_cached( wp_parse_args( 'amapress_role=access_admin&fields=id' ) ),
				get_users_cached( wp_parse_args( 'amapress_role=amap_role_any&fields=id' ) ),
				get_users_cached( wp_parse_args( 'amapress_role=referent_producteur&fields=id' ) )
			);
			$user_id_sql = amapress_prepare_in_sql( $user_ids );
			$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
		} else if ( 'collectif_no_prod' == $amapress_role ) {
			$user_ids    = array_merge(
				get_users_cached( wp_parse_args( 'amapress_role=access_admin&fields=id' ) ),
				get_users_cached( wp_parse_args( 'amapress_role=amap_role_any&fields=id' ) ),
				get_users_cached( wp_parse_args( 'amapress_role=referent_producteur&fields=id' ) )
			);
			$user_ids    = array_diff( $user_ids,
				get_users_cached( wp_parse_args( 'role=producteur&fields=id' ) )
			);
			$user_id_sql = amapress_prepare_in_sql( $user_ids );
			$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
		} else if ( 'collectif_no_amap_role' == $amapress_role ) {
			$user_ids    = array_merge(
				get_users_cached( wp_parse_args( 'amapress_role=access_admin&fields=id' ) )
			);
			$user_ids    = array_diff( $user_ids,
				get_users_cached( wp_parse_args( 'amapress_role=amap_role_any&fields=id' ) ),
				get_users_cached( wp_parse_args( 'amapress_role=referent_producteur&fields=id' ) ),
				get_users_cached( wp_parse_args( 'role=producteur&fields=id' ) )
			);
			$user_id_sql = amapress_prepare_in_sql( $user_ids );
			$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
		} else if ( strpos( $amapress_role, 'amap_role_' ) === 0 ) {
			$amap_role = substr( $amapress_role, 10 );
			if ( 'any' == $amap_role ) {
				$uqi->query_vars[ AmapressUser::AMAP_ROLE ] = '*';
			} else {
				$uqi->query_vars[ AmapressUser::AMAP_ROLE ] = $amap_role;
			}
		}
	}

	if ( isset( $uqi->query_vars['amapress_mllst_id'] ) ) {
		$ml = Amapress_MailingListConfiguration::getBy( intval( $uqi->query_vars['amapress_mllst_id'] ) );
		if ( $ml ) {
			$user_ids = $ml->getMembersIds();
			if ( count( $user_ids ) > 0 ) {
				$user_id_sql = amapress_prepare_in_sql( $user_ids );
				$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
			} else {
				$where .= " AND 0 = 1";
			}
		}
	}

	//amapress_mlgrp_id
	if ( isset( $uqi->query_vars['amapress_mlgrp_id'] ) ) {
		$ml = AmapressMailingGroup::getBy( intval( $uqi->query_vars['amapress_mlgrp_id'] ) );
		if ( $ml ) {
			$user_ids = $ml->getMembersIds();
			if ( count( $user_ids ) > 0 ) {
				$user_id_sql = amapress_prepare_in_sql( $user_ids );
				$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
			} else {
				$where .= " AND 0 = 1";
			}
		}
	}

	if ( isset( $uqi->query_vars['amapress_coadherents'] ) ) {
		$user_id  = Amapress::resolve_user_id( $uqi->query_vars['amapress_coadherents'] );
		$user_ids = AmapressContrats::get_related_users( $user_id );
		if ( ( $key = array_search( $user_id, $user_ids ) ) !== false ) {
			unset( $user_ids[ $key ] );
		}

		if ( count( $user_ids ) > 0 ) {
			$user_id_sql = amapress_prepare_in_sql( $user_ids );
			$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
		} else {
			$where .= " AND 0 = 1";
		}
	}

	if ( isset( $uqi->query_vars['amapress_contrat'] ) ) {
		$amapress_contrat = $uqi->query_vars['amapress_contrat'];
		if ( $amapress_contrat == 'intermittent' ) {
//            $where .= " AND $wpdb->users.ID IN (SELECT amps_pmach.meta_value
//                                                   FROM $wpdb->postmeta as amps_pmach
//                                                   WHERE amps_pmach.meta_key='amapress_adhesion_intermittence_user')";
			//
		} else if ( $amapress_contrat == 'coadherent' ) {
			$adhs     = AmapressContrats::get_active_adhesions();
			$user_ids = array();
			foreach (
				amapress_get_col_cached(
					"SELECT DISTINCT $wpdb->usermeta.meta_value
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3', 'amapress_user_co-foyer-1', 'amapress_user_co-foyer-2', 'amapress_user_co-foyer-3')" ) as $user_id
			) {
				$user_ids[] = intval( $user_id );
			}
			foreach ( $adhs as $adh ) {
				if ( $adh->getAdherent2Id() ) {
					$user_ids[] = $adh->getAdherent2Id();
				}
				if ( $adh->getAdherent3Id() ) {
					$user_ids[] = $adh->getAdherent3Id();
				}
				if ( $adh->getAdherent4Id() ) {
					$user_ids[] = $adh->getAdherent4Id();
				}
			}

			if ( count( $user_ids ) > 0 ) {
				$user_id_sql = amapress_prepare_in_sql( $user_ids );
				$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
			} else {
				$where .= " AND 0 = 1";
			}
		} else if ( $amapress_contrat == 'with_coadherent' ) {
			$adhs     = AmapressContrats::get_active_adhesions();
			$user_ids = array();
			foreach (
				amapress_get_col_cached(
					"SELECT DISTINCT $wpdb->usermeta.user_id
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3', 'amapress_user_co-foyer-1', 'amapress_user_co-foyer-2', 'amapress_user_co-foyer-3')" ) as $user_id
			) {
				$user_ids[] = intval( $user_id );
			}
			foreach ( $adhs as $adh ) {
				if ( $adh->getAdherent2Id() || $adh->getAdherent3Id() || $adh->getAdherent4Id() ) {
					$user_ids[] = $adh->getAdherentId();
				}
			}

			if ( count( $user_ids ) > 0 ) {
				$user_id_sql = amapress_prepare_in_sql( $user_ids );
				$where       .= " AND $wpdb->users.ID IN ($user_id_sql)";
			} else {
				$where .= " AND 0 = 1";
			}
		} else {
			$op          = 'IN';
			$date        = Amapress::end_of_day( amapress_time() );
			$contrat_ids = array();
			if ( $amapress_contrat == 'no' || $amapress_contrat == 'none' ) {
				$contrat_ids = AmapressContrats::get_active_contrat_instances_ids();
				$op          = 'NOT IN';
			} else if ( $amapress_contrat == 'active' ) {
				$contrat_ids = AmapressContrats::get_active_contrat_instances_ids();
			} else if ( $amapress_contrat == 'lastyear' ) {
				$contrat_ids = AmapressContrats::get_active_contrat_instances_ids( null, Amapress::remove_a_year( amapress_time() ), false, false );
				$date        = Amapress::remove_a_year( $date );
			} else {
				$id = Amapress::resolve_post_id( $amapress_contrat, AmapressContrat::INTERNAL_POST_TYPE );
				if ( ! $id ) {
					$id = Amapress::resolve_post_id( $amapress_contrat, AmapressContrat_instance::INTERNAL_POST_TYPE );
				}
				if ( $id ) {
					$post = get_post( $id );
					if ( $post ) {
						$pt = amapress_simplify_post_type( $post->post_type );
						if ( 'contrat_instance' == $pt ) {
							$contrat_ids = array( $id );
						} else if ( 'contrat' == $pt ) {
							$contrat_ids = AmapressContrats::get_active_contrat_instances_ids_by_contrat( $id );
						}
					}
				}
			}
			if ( isset( $uqi->query_vars['amapress_subcontrat'] ) ) {
				$amapress_subcontrat = $uqi->query_vars['amapress_subcontrat'];
				$contrat_ids         = array_filter( $contrat_ids, function ( $contrat_id ) use ( $amapress_subcontrat ) {
					$contrat = AmapressContrat_instance::getBy( $contrat_id );
					if ( ! $contrat ) {
						return false;
					}

					return 0 === strcasecmp( trim( $amapress_subcontrat ), trim( $contrat->getSubName() ) );
				} );
			}
			$contrat_ids = amapress_prepare_in_sql( $contrat_ids );
			$user_ids    = array();
			foreach (
				amapress_get_col_cached( "SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta as amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   LEFT JOIN $wpdb->postmeta as amps_pm_date_fin ON amps_pm_date_fin.post_id = amps_pmach.post_id AND amps_pm_date_fin.meta_key='amapress_adhesion_date_fin'
                                                   INNER JOIN $wpdb->posts as amps_posts ON amps_posts.ID = amps_pmach.post_id
                                                   WHERE (amps_pmach.meta_key='amapress_adhesion_adherent' OR amps_pmach.meta_key='amapress_adhesion_adherent2' OR amps_pmach.meta_key='amapress_adhesion_adherent3' OR amps_pmach.meta_key='amapress_adhesion_adherent4')
                                                   AND amps_posts.post_status = 'publish'
                                                   AND (amps_pm_date_fin.meta_value IS NULL OR CAST(amps_pm_date_fin.meta_value as SIGNED) = 0 OR CAST(amps_pm_date_fin.meta_value as SIGNED) > $date)
                                                   AND amps_pmach.meta_value IS NOT NULL
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_contrat_instance'
                                                   AND CAST(amps_pm_contrat.meta_value as SIGNED) IN ($contrat_ids)" ) as $user_id
			) {
				$user_ids[] = intval( $user_id );
			}
			$all_user_ids = amapress_prepare_in_sql( $user_ids );
			foreach (
				amapress_get_col_cached(
					"SELECT DISTINCT $wpdb->usermeta.meta_value
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3', 'amapress_user_co-foyer-1', 'amapress_user_co-foyer-2', 'amapress_user_co-foyer-3')
AND $wpdb->usermeta.user_id IN ($all_user_ids)" ) as $user_id
			) {
				$user_ids[] = intval( $user_id );
			}
			$all_user_ids = amapress_prepare_in_sql( $user_ids );
			$where        .= " AND $wpdb->users.ID $op ($all_user_ids)";
		}
	}
	if ( ! $lieu_already_filtered && isset( $uqi->query_vars['amapress_lieu'] ) ) {
		$date          = Amapress::end_of_day( amapress_time() );
		$amapress_lieu = $uqi->query_vars['amapress_lieu'];
		if ( ! is_array( $amapress_lieu ) ) {
			$amapress_lieu = array( $amapress_lieu );
		}
		$lieu_ids     = array_map( function ( $l ) {
			return Amapress::resolve_post_id( $l, AmapressLieu_distribution::INTERNAL_POST_TYPE );
		}, $amapress_lieu );
		$cache_key    = 'amps_lieu_' . implode( '_', $lieu_ids ) . '_user_ids';
		$all_user_ids = wp_cache_get( $cache_key );
		if ( false === $all_user_ids ) {
			$contrat_ids = AmapressContrats::get_active_contrat_instances_ids();
			$contrat_ids = amapress_prepare_in_sql( $contrat_ids );
			$user_ids    = array();
			foreach ( $lieu_ids as $lieu_id ) {
				$lieu = AmapressLieu_distribution::getBy( $lieu_id );
				if ( $lieu && $lieu->getReferentId() ) {
					$user_ids[] = $lieu->getReferentId();
				}
				foreach ( AmapressContrats::getReferentsForLieu( $lieu_id ) as $ref ) {
					$user_ids[] = $ref['ref_id'];
				}
			}
			$lieu_ids = amapress_prepare_in_sql( $lieu_ids );
			foreach (
				amapress_get_col_cached( "SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   LEFT JOIN $wpdb->postmeta as amps_pm_date_fin ON amps_pm_date_fin.post_id = amps_pmach.post_id AND amps_pm_date_fin.meta_key='amapress_adhesion_date_fin'
                                                   INNER JOIN $wpdb->postmeta as amps_pm_adhesion ON amps_pm_adhesion.post_id = amps_pmach.post_id
                                                   INNER JOIN $wpdb->posts as amps_posts ON amps_posts.ID = amps_pmach.post_id
                                                   WHERE (amps_pmach.meta_key='amapress_adhesion_adherent' OR amps_pmach.meta_key='amapress_adhesion_adherent2' OR amps_pmach.meta_key='amapress_adhesion_adherent3' OR amps_pmach.meta_key='amapress_adhesion_adherent4')
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_contrat_instance'
                                                   AND amps_pm_adhesion.meta_key = 'amapress_adhesion_lieu'
                                                   AND (amps_pm_date_fin.meta_value IS NULL OR CAST(amps_pm_date_fin.meta_value as SIGNED) = 0 OR CAST(amps_pm_date_fin.meta_value as SIGNED) > $date)
                                                   AND amps_pmach.meta_value IS NOT NULL
                                                   AND amps_posts.post_status = 'publish'
                                                   AND amps_pm_contrat.meta_value IN ($contrat_ids)
                                                   AND amps_pm_adhesion.meta_value IN ($lieu_ids)" ) as $user_id
			) {
				$user_ids[] = intval( $user_id );
			}
			$all_user_ids = amapress_prepare_in_sql( $user_ids );
			foreach (
				amapress_get_col_cached(
					"SELECT DISTINCT $wpdb->usermeta.meta_value
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3', 'amapress_user_co-foyer-1', 'amapress_user_co-foyer-2', 'amapress_user_co-foyer-3')
AND $wpdb->usermeta.user_id IN ($all_user_ids)" ) as $user_id
			) {
				$user_ids[] = intval( $user_id );
			}
			$all_user_ids = amapress_prepare_in_sql( $user_ids );
			wp_cache_set( $cache_key, $all_user_ids );
		}
		$where .= " AND $wpdb->users.ID IN ($all_user_ids)";
	}
	if ( isset( $uqi->query_vars['amapress_adhesion'] ) ) {
		$amapress_adhesion = $uqi->query_vars['amapress_adhesion'];
		if ( $amapress_adhesion == 'nok' ) {
			$min_date = amapress_time();
			$max_date = amapress_time();
			$period   = AmapressAdhesionPeriod::getCurrent();
			if ( ! $period ) {
				$contrats = AmapressContrats::get_active_contrat_instances();
				foreach ( $contrats as $c ) {
					if ( $min_date > $c->getDate_debut() ) {
						$min_date = $c->getDate_debut();
					}
					if ( $max_date < $c->getDate_fin() ) {
						$max_date = $c->getDate_fin();
					}
				}
				$where .= $wpdb->prepare( " AND $wpdb->users.ID NOT IN (SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   WHERE amps_pmach.meta_key='amapress_adhesion_paiement_user'
                                                   AND amps_pmach.meta_value IS NOT NULL
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_paiement_date'
                                                   AND amps_pm_contrat.meta_value BETWEEN %d AND %d)", intval( $min_date ), intval( $max_date ) );
			} else {
				$where .= $wpdb->prepare( " AND $wpdb->users.ID NOT IN (SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   AND amps_pmach.meta_value IS NOT NULL
                                                   WHERE amps_pmach.meta_key='amapress_adhesion_paiement_user'
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_paiement_period'
                                                   AND amps_pm_contrat.meta_value = %d)", $period->ID );
			}
		} elseif ( $amapress_adhesion == 'ok' ) {
			$min_date = amapress_time();
			$max_date = amapress_time();
			$period   = AmapressAdhesionPeriod::getCurrent();
			if ( ! $period ) {
				$contrats = AmapressContrats::get_active_contrat_instances();
				foreach ( $contrats as $c ) {
					if ( $min_date > $c->getDate_debut() ) {
						$min_date = $c->getDate_debut();
					}
					if ( $max_date < $c->getDate_fin() ) {
						$max_date = $c->getDate_fin();
					}
				}
				$where .= $wpdb->prepare( " AND $wpdb->users.ID IN (SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   WHERE amps_pmach.meta_key='amapress_adhesion_paiement_user'
                                                   AND amps_pmach.meta_value IS NOT NULL
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_paiement_date'
                                                   AND amps_pm_contrat.meta_value BETWEEN %d AND %d)", intval( $min_date ), intval( $max_date ) );
			} else {
				$where .= $wpdb->prepare( " AND $wpdb->users.ID IN (SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   AND amps_pmach.meta_value IS NOT NULL
                                                   WHERE amps_pmach.meta_key='amapress_adhesion_paiement_user'
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_paiement_period'
                                                   AND amps_pm_contrat.meta_value = %d)", $period->ID );
			}
		}
	}
	$uqi->query_where .= $where;
	if ( strpos( $uqi->query_fields, 'DISTINCT' ) === false ) {
		$uqi->query_fields = 'DISTINCT ' . $uqi->query_fields;
	}
	//var_dump($uqi->query_from);
//	amapress_dump($uqi->request);
//	amapress_dump($uqi->query_from);
//	amapress_dump($uqi->query_limit);
//	amapress_dump($uqi->query_where);
//    die();
} );

add_filter( 'users_list_table_query_args', function ( $args ) {
	if ( isset( $_GET['amapress_contrat'] ) ) {
		$args['amapress_contrat'] = $_GET['amapress_contrat'];
	}
	if ( isset( $_GET['amapress_subcontrat'] ) ) {
		$args['amapress_subcontrat'] = $_GET['amapress_subcontrat'];
	}
	if ( isset( $_GET['amapress_lieu'] ) ) {
		$args['amapress_lieu'] = $_GET['amapress_lieu'];
	}
	if ( isset( $_GET['amapress_adhesion'] ) ) {
		$args['amapress_adhesion'] = $_GET['amapress_adhesion'];
	}
	if ( isset( $_GET['amapress_mllst_id'] ) ) {
		$args['amapress_mllst_id'] = $_GET['amapress_mllst_id'];
	}
	if ( isset( $_GET['amapress_info'] ) ) {
		$args['amapress_info'] = $_GET['amapress_info'];
	}
	if ( isset( $_GET['amapress_role'] ) ) {
		$args['amapress_role'] = $_GET['amapress_role'];
	}
	if ( isset( $_GET['amapress_mlgrp_id'] ) ) {
		$args['amapress_mlgrp_id'] = $_GET['amapress_mlgrp_id'];
	}
	if ( isset( $_GET['amapress_coadherents'] ) ) {
		$args['amapress_coadherents'] = $_GET['amapress_coadherents'];
	}
	if ( isset( $_GET['orderby'] ) ) {
		$args['custom_orderby'] = $_GET['orderby'];
	}

	return $args;
} );


function amapress_wp_link_query_args( $query ) {
	if ( is_array( $query['post_type'] ) ) {
		foreach ( $query['post_type'] as $pt ) {
			if ( $pt == 'page' || $pt == 'post' ) {
				continue;
			}

			$ptt  = amapress_simplify_post_type( $pt );
			$ents = AmapressEntities::getPostTypes();
			if ( isset( $ents[ $ptt ]['show_in_nav_menu'] ) && $ents[ $ptt ]['show_in_nav_menu'] === false ) {
				$query['post_type'] = array_filter( $query['post_type'], function ( $v ) use ( $pt ) {
					return $v != $pt;
				} );
			}
		}
	} else {
		$query['post_type'] = [ $query['post_type'] ];
	}
	foreach ( AmapressEntities::getPostTypes() as $pt => $conf ) {
		if ( ! isset( $conf['show_in_nav_menu'] ) || $conf['show_in_nav_menu'] !== false ) {
			$query['post_type'][] = amapress_unsimplify_post_type( $pt );
		}
	}

	return $query;

}

add_filter( 'wp_link_query_args', 'amapress_wp_link_query_args' );