<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
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
					'type'    => 'INT',
				),
			)
		) );
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
					) );
				}
			}
		}
		do_action( "amapress_{$pt}_query_filter", $query );
	}

	global $amapress_getting_referent_infos;
	if ( ! $amapress_getting_referent_infos && is_admin() && amapress_can_access_admin() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		//var_dump($refs);
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
						'key'     => "amapress_{$pt}_producteur",
						'value'   => $r['producteur'],
						'compare' => 'IN',
					);
				}

			} else if ( $pt == 'panier' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_contrat_instance",
						'value'   => $r['contrat_instance_ids'],
						'compare' => 'IN',
					);
				}
			} else if ( $pt == 'distribution' ) {
				foreach ( $refs as $r ) {
					foreach ( $r['contrat_instance_ids'] as $contrat_id ) {
						$meta[] = array(
//                        array(
//                            'relation' => 'OR',
//                            array(
//                                'key' => "amapress_{$pt}_lieu",
//                                'value' => $r['lieu'],
//                                'compare' => '=',
//                            ),
//                            array(
//                                'key' => "amapress_{$pt}_lieu_substitution",
//                                'value' => $r['lieu'],
//                                'compare' => '=',
//                            ),
//                        ),
							array(
								'key'     => "amapress_{$pt}_contrats",
								'value'   => '"' . $contrat_id . '"',
								'compare' => 'LIKE',
							),
						);
					}
				}
//            } else if ($pt == 'lieu_distribution') {
//                $post__in = $query->get('post__in');
//                if (is_array($post__in)) $post__in = array();
//                foreach ($refs as $r) {
//                    $post__in[] = intval($r['lieu']);
//                }
//                $post__in = array_unique($post__in);
//                $query->set('post__in', $post__in);

			} else if ( $pt == 'contrat_paiement' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_contrat_instance",
						'value'   => $r['contrat_instance_ids'],
						'compare' => 'IN',
					);
				}
			} else if ( $pt == 'adhesion' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_contrat_instance",
						'value'   => $r['contrat_instance_ids'],
						'compare' => 'IN',
					);
				}
			} else if ( $pt == 'contrat' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_producteur",
						'value'   => $r['producteur'],
						'compare' => '=',
					);
				}
			} else if ( $pt == 'contrat_instance' ) {
				foreach ( $refs as $r ) {
					$meta[] = array(
						'key'     => "amapress_{$pt}_model",
						'value'   => $r['contrat_ids'],
						'compare' => 'IN',
					);
				}
			}
			if ( count( $meta ) > 0 ) {
				if ( count( $meta ) > 1 ) {
					//var_dump($meta);
					amapress_add_meta_query( $query, array(
						array_merge( array( 'relation' => 'OR' ), $meta )
					) );
				} else {
					amapress_add_meta_query( $query, array(
						$meta
					) );
				}
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
		if ( $pt == 'produit' || $pt == 'contrat' || $pt == 'visite' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_producteur",
					'value'   => $amapress_producteur,
					'compare' => '=',
				)
			) );
		} else if ( 'contrat_instance' == $pt ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_model",
					'value'   => AmapressContrats::get_contrat_ids( $amapress_producteur, false ),
					'compare' => 'IN',
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
				$arr[] = array(
					'key'     => "amapress_{$pt}_produits",
					'value'   => '"' . $prod . '"',
					'compare' => 'like',
				);
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
						'terms'    => $amapress_recette_tags,
						'operator' => 'IN',
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
		$amapress_recette_tags = explode( ',', $query->query_vars['amapress_produit_tag'] );
		if ( $pt == 'recette' ) {
			for ( $i = 0; $i < count( $amapress_recette_tags ); $i ++ ) {
				$amapress_recette_tags[ $i ] = Amapress::resolve_tax_id( $amapress_recette_tags[ $i ], 'amps_produit_category' );
			}
			amapress_add_tax_query( $query, array(
				array(
					array(
						'taxonomy' => 'amps_produit_category',
						'field'    => 'term_id',
						'terms'    => $amapress_recette_tags,
						'operator' => 'IN',
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
		$amapress_contrat_instnace = Amapress::resolve_post_id( $query->query_vars['amapress_contrat_inst'], 'amps_contrat_inst' );
		if ( $pt == 'adhesion' || $pt == 'commande' || $pt == 'intermittence_panier' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_contrat_instance",
					'value'   => $amapress_contrat_instnace,
					'compare' => '=',
				)
			) );
		} else if ( $pt == 'contrat_paiement' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_contrat_paiement_contrat_instance",
					'value'   => $amapress_contrat_instnace,
					'compare' => '=',
					'type'    => 'INT',
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
						'type'    => 'INT',
					),
					array(
						'key'     => "amapress_{$pt}_contrat_quantite",
						'value'   => '"' . $amapress_contrat_qt . '"',
						'compare' => 'like',
					)
				)
			) );
		}
	}
	if ( ! empty( $query->query_vars['amapress_contrat'] ) ) {
		$amapress_contrat = Amapress::resolve_post_id( $query->query_vars['amapress_contrat'], 'amps_contrat' );
		if ( $pt == 'adhesion' || $pt == 'commande' || $pt == 'intermittence_panier' ) {
			$amapress_date        = get_query_var( 'amapress_date' );
			$date                 = ( empty( $amapress_date ) || $amapress_date == 'active' ) ? null : ( is_int( $amapress_date ) ? intval( $amapress_date ) : DateTime::createFromFormat( 'Y-m-d', $amapress_date )->getTimestamp() );
			$active_contrat_insts = AmapressContrats::get_active_contrat_instances_ids_by_contrat( $amapress_contrat, $date );
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_contrat_instance",
					'value'   => $active_contrat_insts,
					'compare' => 'IN',
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
						'value'   => $user_ids,
						'compare' => 'IN',
					),
					array(
						'key'     => "amapress_{$pt}_adherent2",
						'value'   => $user_ids,
						'compare' => 'IN',
					),
					array(
						'key'     => "amapress_{$pt}_adherent3",
						'value'   => $user_ids,
						'compare' => 'IN',
					),
				),
			) );
		} else if ( $pt == 'amap_event' || $pt == 'visite' || $pt == 'assemblee' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_participants",
					'value'   => '"' . $amapress_user . '"',
					'compare' => 'LIKE',
				),
			) );
		} else if ( $pt == 'distribution' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_responsables",
					'value'   => '"' . $amapress_user . '"',
					'compare' => 'LIKE',
				),
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
					'value'   => $user_ids,
					'compare' => 'IN',
				),
			) );
		} else if ( $pt == 'intermittence_panier' ) {
			$user_ids = AmapressContrats::get_related_users( $amapress_user );
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_{$pt}_adherent",
					'value'   => $user_ids,
					'compare' => 'IN',
				),
			) );
		} else if ( $pt == 'contrat_paiement' ) {
			$contrat_instance_ids = AmapressContrats::get_user_active_contrat_instances( $amapress_user );
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_contrat_paiement_contrat_instance",
					'value'   => $contrat_instance_ids,
					'compare' => 'IN',
					'type'    => 'INT',
				)
			) );
		}
	}

	if ( ! empty( $query->query_vars['amapress_lieu'] ) ) {
		$amapress_lieu = Amapress::resolve_post_id( $query->query_vars['amapress_lieu'], 'amps_lieu_distribution' );
		if ( $pt == 'adhesion' || $pt == 'amap_event' || $pt == 'assemblee_generale' || $pt == 'commande' || $pt == 'intermittence_panier' ) {
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
		} else if ( $pt == 'contrat_paiement' ) {
			amapress_add_meta_query( $query, array(
				array(
					'key'     => "amapress_contrat_paiement_adhesion",
					'value'   => AmapressContrats::get_active_adhesions_ids( null, null, $amapress_lieu ),
					'compare' => 'IN',
					'type'    => 'INT',
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
					'relation' => 'OR',
					array(
						'key'     => "amapress_{$pt}_adherent1",
						'compare' => 'EXISTS',
					),
					array(
						'key'     => "amapress_{$pt}_adherent2",
						'compare' => 'EXISTS',
					)
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
			} else if ( $pt == 'adhesion' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_adhesion_contrat_instance",
						'value'   => AmapressContrats::get_active_contrat_instances_ids(),
						'compare' => 'IN',
						'type'    => 'INT',
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
							'value'   => Amapress::end_of_day( amapress_time() ),
							'compare' => '>=',
							'type'    => 'INT',
						),
					)
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
		} else if ( $amapress_date == 'thisweek' ) {
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
				amapress_add_meta_query( $query, array(
					array(
						'key'     => "amapress_{$pt}_date",
						'value'   => array(
							Amapress::start_of_day( amapress_time() ),
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
							Amapress::start_of_week( amapress_time() ),
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
			if ( $pt == 'distribution' || $pt == 'panier' || $pt == 'assemblee_generale' || $pt == 'visite' || $pt == 'contrat_paiement' || $pt == 'amap_event' || $pt == 'intermittence_panier' ) {
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
					amapress_add_meta_query( $query, array(
						array(
							'key'     => "amapress_{$pt}_date",
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

add_action( 'pre_get_users', function ( WP_User_Query $uqi ) {
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
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_user_telephone3',
								'compare' => "NOT EXISTS"
							),
							array(
								'key'     => 'amapress_user_telephone3',
								'compare' => "=",
								'value'   => '',
							)
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_user_telephone4',
								'compare' => "NOT EXISTS"
							),
							array(
								'key'     => 'amapress_user_telephone4',
								'compare' => "=",
								'value'   => '',
							)
						),
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
						'type'    => 'INT',
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
	$where = '';
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
			$user_ids = implode( ',', array_unique( $user_ids ) );
			if ( empty( $user_ids ) ) {
				$user_ids = '0';
			}
			$where .= " AND $wpdb->users.ID IN ($user_ids)";
			//
		} else if ( $amapress_role == 'referent_producteur' ) {
			$user_ids = array();
			foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
				$prod = $contrat->getModel()->getProducteur();
				foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
					if ( $prod->getReferent( $lieu_id ) == null ) {
						continue;
					}
					$user_ids[] = $prod->getReferent( $lieu_id )->ID;
				}
				if ( $prod->getReferent() == null ) {
					continue;
				}
				$user_ids[] = $prod->getReferent()->ID;
			}
			$user_ids = implode( ',', array_unique( $user_ids ) );
			if ( empty( $user_ids ) ) {
				$user_ids = '0';
			}
			$where .= " AND $wpdb->users.ID IN ($user_ids)";

		} else if ( $amapress_role == 'resp_distrib' ) {
			$distribs = get_posts( array(
				'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_distribution_date',
						'value'   => array(
							Amapress::start_of_week( amapress_time() ),
							Amapress::end_of_week( amapress_time() )
						),
						'compare' => 'BETWEEN',
						'type'    => 'INT',
					),
				),
			) );
			$user_ids = array();
			foreach ( $distribs as $distrib ) {
				$resp = Amapress::get_post_meta_array( $distrib->ID, 'amapress_distribution_responsables' );
				if ( $resp ) {
					$user_ids = array_merge( $user_ids, $resp );
				}
			}
			$user_ids = implode( ',', array_unique( $user_ids ) );
			if ( empty( $user_ids ) ) {
				$user_ids = '0';
			}
			$where .= " AND $wpdb->users.ID IN ($user_ids)";
		} else if ( $amapress_role == 'resp_distrib_next' ) {
			$time     = Amapress::add_a_week( amapress_time() );
			$distribs = get_posts( array(
				'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_distribution_date',
						'value'   => array( Amapress::start_of_week( $time ), Amapress::end_of_week( $time ) ),
						'compare' => 'BETWEEN',
						'type'    => 'INT',
					),
				),
			) );
			$user_ids = array();
			foreach ( $distribs as $distrib ) {
				$resp = Amapress::get_post_meta_array( $distrib->ID, 'amapress_distribution_responsables' );
				if ( $resp ) {
					$user_ids = array_merge( $user_ids, $resp );
				}
			}
			$user_ids = implode( ',', array_unique( $user_ids ) );
			if ( empty( $user_ids ) ) {
				$user_ids = '0';
			}
			$where .= " AND $wpdb->users.ID IN ($user_ids)";
		} else if ( $amapress_role == 'resp_distrib_month' ) {
			$distribs = get_posts( array(
				'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_distribution_date',
						'value'   => array(
							Amapress::start_of_week( amapress_time() ),
							Amapress::end_of_month( amapress_time() )
						),
						'compare' => 'BETWEEN',
						'type'    => 'INT',
					),
				),
			) );
			$user_ids = array();
			foreach ( $distribs as $distrib ) {
				$resp = Amapress::get_post_meta_array( $distrib->ID, 'amapress_distribution_responsables' );
				if ( $resp ) {
					$user_ids = array_merge( $user_ids, $resp );
				}
			}
			$user_ids = implode( ',', array_unique( $user_ids ) );
			if ( empty( $user_ids ) ) {
				$user_ids = '0';
			}
			$where .= " AND $wpdb->users.ID IN ($user_ids)";
		} else if ( strpos( $amapress_role, 'amap_role_' ) === 0 ) {
			$amap_role = substr( $amapress_role, 10 );
			if ( 'any' == $amap_role ) {
				$uqi->query_vars[ AmapressUser::AMAP_ROLE ] = '*';
			} else {
				$uqi->query_vars[ AmapressUser::AMAP_ROLE ] = $amap_role;
			}
//            if ($amap_role == 'any') {
//                $user_ids = get_users(array(
//                    'fields' => 'ID',
//                    'meta_query' => array(
//                        array(
//                            'relation' => 'AND',
//                            array(
//                                'key' => "amapress_user_amap_roles",
//                                'value' => '"\d+"',
//                                'compare' => 'REGEXP',
//                            ),
//                            array(
//                                'key' => "amapress_user_amap_roles",
//                                'compare' => 'EXISTS',
//                            ),
//                        )
//                    )));
//                $user_ids = array_merge($user_ids, get_users(array(
//                    'fields' => 'ID',
//                    'role__in' => amapress_can_access_admin_roles(),
//                )));
//            } else {
//                $amap_role = Amapress::resolve_tax_id($amap_role, AmapressUser::AMAP_ROLE);
//                $user_ids = get_users(array(
//                    'fields' => 'ID',
//                    'meta_query' => array(
//                        array(
//                            'key' => "amapress_user_amap_roles",
//                            'value' => '"' . $amap_role . '"',
//                            'compare' => 'LIKE',
//                        )
//                    )));
//                $user_ids = array_merge($user_ids, get_users(array(
//                    'fields' => 'ID',
//                    'role__in' => array($amap_role),
//                )));
//            }
//            if (!empty($user_ids)) {
//                $user_ids = implode(',', array_unique($user_ids));
//                if (empty($user_ids)) $user_ids = '0';
//                $where .= " AND $wpdb->users.ID IN ($user_ids)";
//            }
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
			foreach ( $adhs as $adh ) {
				if ( $adh->getAdherent2() != null ) {
					$user_ids[] = $adh->getAdherent2()->ID;
				}
				if ( $adh->getAdherent3() != null ) {
					$user_ids[] = $adh->getAdherent3()->ID;
				}
			}

			if ( count( $user_ids ) > 0 ) {
				$user_ids = implode( ',', array_unique( $user_ids ) );
				if ( empty( $user_ids ) ) {
					$user_ids = '0';
				}
				$where .= " AND $wpdb->users.ID IN ($user_ids)";
			} else {
				$where .= " AND 0 = 1";
			}
		} else {
			$op = 'IN';
			if ( $amapress_contrat == 'no' || $amapress_contrat == 'none' ) {
				$contrat_ids = AmapressContrats::get_active_contrat_instances_ids();
				$op          = 'NOT IN';
			} else if ( $amapress_contrat == 'active' ) {
				$contrat_ids = AmapressContrats::get_active_contrat_instances_ids();
			} else if ( $amapress_contrat == 'lastyear' ) {
				$contrat_ids = AmapressContrats::get_contrat_instances();
			} else {
				$contrat_ids = array( Amapress::resolve_post_id( $amapress_contrat, AmapressContrat_instance::INTERNAL_POST_TYPE ) );
			}
			$contrat_ids = implode( ',', $contrat_ids );
			$where       .= " AND $wpdb->users.ID $op (SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta as amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   WHERE (amps_pmach.meta_key='amapress_adhesion_adherent' OR amps_pmach.meta_key='amapress_adhesion_adherent2' OR amps_pmach.meta_key='amapress_adhesion_adherent3')
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_contrat_instance'
                                                   AND amps_pm_contrat.meta_value IN ($contrat_ids))";
		}
	}
	if ( isset( $uqi->query_vars['amapress_lieu'] ) ) {
		$amapress_lieu = $uqi->query_vars['amapress_lieu'];
		if ( ! is_array( $amapress_lieu ) ) {
			$amapress_lieu = array( $amapress_lieu );
		}
		$lieu_ids    = array_map( function ( $l ) {
			return Amapress::resolve_post_id( $l, AmapressLieu_distribution::INTERNAL_POST_TYPE );
		}, $amapress_lieu );
		$contrat_ids = AmapressContrats::get_active_contrat_instances_ids();
		$contrat_ids = implode( ',', $contrat_ids );
		$lieu_ids    = implode( ',', $lieu_ids );
		$where       .= " AND $wpdb->users.ID IN (SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   INNER JOIN $wpdb->postmeta as amps_pm_adhesion ON amps_pm_adhesion.post_id = amps_pmach.post_id
                                                   WHERE (amps_pmach.meta_key='amapress_adhesion_adherent' OR amps_pmach.meta_key='amapress_adhesion_adherent2' OR amps_pmach.meta_key='amapress_adhesion_adherent3')
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_contrat_instance'
                                                   AND amps_pm_adhesion.meta_key = 'amapress_adhesion_lieu'
                                                   AND amps_pm_contrat.meta_value IN ($contrat_ids)
                                                   AND amps_pm_adhesion.meta_value IN ($lieu_ids))";
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
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_paiement_date'
                                                   AND amps_pm_contrat.meta_value BETWEEN %d AND %d)", intval( $min_date ), intval( $max_date ) );
			} else {
				$where .= $wpdb->prepare( " AND $wpdb->users.ID NOT IN (SELECT amps_pmach.meta_value
                                                   FROM $wpdb->postmeta amps_pmach
                                                   INNER JOIN $wpdb->postmeta as amps_pm_contrat ON amps_pm_contrat.post_id = amps_pmach.post_id
                                                   WHERE amps_pmach.meta_key='amapress_adhesion_paiement_user'
                                                   AND amps_pm_contrat.meta_key = 'amapress_adhesion_paiement_period'
                                                   AND amps_pm_contrat.meta_value = %d)", $period->ID );
			}
		}
	}
	$uqi->query_where .= $where;
	//var_dump($uqi->query_from);
//    var_dump($uqi->query_where);
//    die();
} );

add_filter( 'users_list_table_query_args', function ( $args ) {
	if ( isset( $_GET['amapress_contrat'] ) ) {
		$args['amapress_contrat'] = $_GET['amapress_contrat'];
	}
	if ( isset( $_GET['amapress_lieu'] ) ) {
		$args['amapress_lieu'] = $_GET['amapress_lieu'];
	}
	if ( isset( $_GET['amapress_adhesion'] ) ) {
		$args['amapress_adhesion'] = $_GET['amapress_adhesion'];
	}
	if ( isset( $_GET['amapress_info'] ) ) {
		$args['amapress_info'] = $_GET['amapress_info'];
	}
	if ( isset( $_GET['amapress_role'] ) ) {
		$args['amapress_role'] = $_GET['amapress_role'];
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
				unset( $query['post_type'][ $pt ] );
			}
		}
		$query['post_type'] = array( 'post', 'pages' ); // show only posts and pages
	}
	$query['amapress_date'] = 'active';

	return $query;

}

add_filter( 'wp_link_query_args', 'amapress_wp_link_query_args' );