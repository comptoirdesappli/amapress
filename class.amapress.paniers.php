<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * class short summary.
 *
 * class description.
 *
 * @version 1.0
 * @author Guillaume
 */
class AmapressPaniers {
	public static function generate_paniers( $contrat_id, $eval = false ) {
		$key = 'amps_gen_pan_' . $contrat_id;
		$res = ! $eval ? [] : maybe_unserialize( get_option( $key ) );
		if ( ! empty( $res ) ) {
			return $res;
		}

		$contrats = [ AmapressContrat_instance::getBy( $contrat_id ) ];
		/** @var AmapressContrat_instance $contrat */
		foreach ( $contrats as $contrat ) {
			if ( empty( $contrat ) ) {
				continue;
			}

			$relative_date = Amapress::add_a_month( Amapress::start_of_day( $contrat->getDate_debut() ), - 12 );

			Amapress::setFilterForReferent( false );
			$all_contrat_ids = AmapressContrats::get_active_contrat_instances_ids( null,
				$relative_date );
			Amapress::setFilterForReferent( true );

			$res[ $contrat->ID ] = array();
			$contrat_model       = $contrat->getModel();
			$lieux_ids           = $contrat->getLieuxIds();
			$liste_dates         = array_unique( $contrat->getListe_dates() );
			if ( empty( $liste_dates ) || empty( $contrat_model ) ) {
				continue;
			}

			foreach ( $liste_dates as $date ) {
				$paniers = [];
				if ( ! defined( 'AMAPRESS_TEST' ) ) {
					$paniers = get_posts( array(
						'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							'relation' => 'AND',
							array(
								'key'     => 'amapress_panier_date',
								'value'   => array(
									Amapress::start_of_day( $date ),
									Amapress::end_of_day( $date )
								),
								'compare' => 'BETWEEN',
								'type'    => 'NUMERIC'
							),
							array(
								'key'     => 'amapress_panier_contrat_instance',
								'value'   => $contrat->ID,
								'compare' => '=',
								'type'    => 'NUMERIC'
							),
						)
					) );
				}
				if ( count( $paniers ) == 0 ) {
					$my_post = array(
						'post_title'   => sprintf( __( 'Panier de %s%s du %02d-%02d-%04d', 'amapress' ),
							$contrat_model->getTitle(),
							! empty( $contrat->getSubName() ) ? ' - ' . $contrat->getSubName() : '',
							date( 'd', $date ), date( 'm', $date ), date( 'Y', $date ) ),
						'post_type'    => AmapressPanier::INTERNAL_POST_TYPE,
						'post_content' => '',
						'post_status'  => 'publish',
						'meta_input'   => array(
							'amapress_panier_contrat_instance' => $contrat->ID,
							'amapress_panier_date'             => Amapress::start_of_day( $date ),
						),
					);

					$res[ $contrat->ID ][] = array( 'lieux' => $lieux_ids, 'date' => Amapress::start_of_day( $date ) );
					// Insert the post into the database.
					if ( ! $eval ) {
						wp_insert_post( $my_post );
					}
				} else if ( count( $paniers ) > 1 ) {
					array_shift( $paniers );
					foreach ( $paniers as $panier_post ) {
						$panier                = AmapressPanier::getBy( $panier_post );
						$res[ $contrat->ID ][] = array(
							'lieux' => $lieux_ids,
							'date'  => Amapress::start_of_day( $panier->getDate() )
						);
						if ( ! $eval ) {
							wp_delete_post( $panier->ID, true );
						}
					}
				}
			}

			if ( ! defined( 'AMAPRESS_TEST' ) ) {
				$paniers = get_posts( array(
						'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							'relation' => 'AND',
							array(
								'key'     => 'amapress_panier_date',
								'value'   => $relative_date,
								'compare' => '>=',
								'type'    => 'NUMERIC',
							),
						)
					)
				);
				foreach ( $paniers as $panier_post ) {
					$panier = AmapressPanier::getBy( $panier_post );
					if ( ! in_array( $panier->getContrat_instanceId(), $all_contrat_ids ) ) {
						$res[ $contrat->ID ][] = array(
							'lieux' => $lieux_ids,
							'date'  => Amapress::start_of_day( $panier->getDate() )
						);
						if ( ! $eval ) {
							wp_delete_post( $panier_post->ID, true );
						}
						continue;
					}
					if ( $panier->getContrat_instanceId() == $contrat->ID ) {
						if ( ! in_array( $panier->getDate(), $liste_dates ) ) {
							$res[ $contrat->ID ][] = array(
								'lieux' => $lieux_ids,
								'date'  => Amapress::start_of_day( $panier->getDate() )
							);
							if ( ! $eval ) {
								wp_delete_post( $panier_post->ID, true );
							}
							continue;
						}
					}
				}
			}
		}

		if ( ! $eval ) {
			delete_option( $key );
		} else {
			update_option( $key, $res );
		}

		return $res;
	}

	public
	static function getPanierVariableCommandes(
		$contrat_instance_id, $date, $lieu_id = null
	) {
		$columns = array(
			array(
				'title' => __( 'Nom', 'amapress' ),
				'data'  => array(
					'_'    => 'last_name',
					'sort' => 'last_name',
				)
			),
			array(
				'title' => __( 'Prénom', 'amapress' ),
				'data'  => array(
					'_'    => 'first_name',
					'sort' => 'first_name',
				)
			),
			array(
				'title' => __( 'Lieu', 'amapress' ),
				'data'  => array(
					'_'    => 'lieu',
					'sort' => 'lieu',
				)
			),
			array(
				'title' => __( 'Contenu', 'amapress' ),
				'data'  => array(
					'_'    => 'content',
					'sort' => 'content',
				)
			),
		);

		$adhesions = array_group_by(
			AmapressContrats::get_active_adhesions( array( $contrat_instance_id ) ),
			function ( $adh ) use ( $contrat_instance_id ) {
				/** @var AmapressAdhesion $adh */
				if ( Amapress::hasPartialCoAdhesion() ) {
					$user_ids = AmapressContrats::get_related_users( $adh->getAdherent()->getUser()->ID,
						false, null, $contrat_instance_id );
				} else {
					$user_ids = AmapressContrats::get_related_users( $adh->getAdherent()->getUser()->ID );
				}

				return implode( '_', $user_ids );
			} );
		$sums      = [
			'adhs' => count( $adhesions ),
		];
		foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance_id ) as $quant ) {
			$sums["quants"]["quant_{$quant->ID}"] = [
				'sum'   => 0,
				'price' => 0,
				'label' => $quant->getTitle(),
			];
		}
		$liste = array();
		/** @var AmapressAdhesion[] $adhs */
		foreach ( $adhesions as $user_ids => $adhs ) {
			$line = array();

			$user_ids = explode( '_', $user_ids );
			$users    = array_map( function ( $user_id ) {
				return amapress_get_user_by_id_or_archived( intval( $user_id ) );
			}, $user_ids );

			$line['first_name'] = implode( ' / ', array_map( function ( $user ) {
				return $user->first_name;
			}, $users ) );
			$line['last_name']  = implode( ' / ', array_map( function ( $user ) {
				return ! empty( $user->last_name ) ? $user->last_name : $user->display_name;
			}, $users ) );
			$line['tel']        = implode( '<br/>', array_map( function ( $user ) {
				$adh = AmapressUser::getBy( $user );

				return $adh->getTelTo();
			}, $users ) );

			foreach ( $adhs as $adh ) {
				if ( $adh->getContrat_instanceId() == $contrat_instance_id &&
				     ( $lieu_id == null || $adh->getLieuId() == $lieu_id )
				) {
					foreach ( $adh->getContrat_quantites( $date ) as $quant ) {
						$qid                                   = $quant->getID();
						$sums["quants"]["quant_$qid"]['sum']   += $quant->getFactor();
						$sums["quants"]["quant_$qid"]['price'] += $quant->getPrice();
					}
					$line['lieu']    = $adh->getLieu()->getShortName();
					$line['content'] = $adh->getContrat_quantites_AsString( $date );
					if ( ! empty( $line['content'] ) ) {
						$liste[] = $line;
					}
				}
			}
		}

		usort( $liste, function ( $a, $b ) {
			return strcasecmp( wp_strip_all_tags( $a['last_name'] ), wp_strip_all_tags( $b['last_name'] ) );
		} );

		return array(
			'columns' => $columns,
			'data'    => $liste,
			'adhs'    => $sums['adhs'],
			'quants'  => $sums['quants'],
			'resume'  => implode( ', ', array_map( function ( $q ) {
				return "{$q['sum']} x {$q['label']}";
			}, array_filter( array_values( $sums['quants'] ), function ( $q ) {
				return $q['sum'] > 0;
			} ) ) )
		);
	}

	public static function get_selected_produits(
		$panier_id
	) {
		return Amapress::get_post_meta_array( $panier_id, 'amapress_panier_produits_selected' );
	}

//    public static function panier_shortcode($atts)
//    {
//        return getPanier();
//    }

	/** @return AmapressDistribution */
	public
	static function getDistribution(
		$date, $lieu
	) {
		$dists = get_posts( array(
			'post_type'      => 'amps_distribution',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_distribution_date',
					'value'   => array(
						Amapress::start_of_day( $date ),
						Amapress::end_of_day( $date )
					),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				),
				array(
					'key'     => 'amapress_distribution_lieu',
					'value'   => intval( $lieu ),
					'compare' => '=',
					'type'    => 'NUMERIC'
				),
			),
		) );
		if ( count( $dists ) ) {
			return AmapressDistribution::getBy( $dists[0] );
		}

		return null;
	}

	/**
	 * @param int $panier_id
	 * @param int $lieu_id
	 * @param int $user_id
	 *
	 * @return string
	 */
	public
	static function isIntermittent(
		$panier_id, $lieu_id, $user_id = null
	) {
		$panier = AmapressPanier::getBy( $panier_id );
		if ( empty( $user_id ) ) {
			$user_id = amapress_current_user_id();
		}
		$paniers = self::getPanierIntermittents(
			[
				'panier_id' => $panier_id,
				'date'      => $panier->getRealDate(),
				'adherent'  => $user_id,
				'lieu_id'   => $lieu_id,
			]
		);
		$paniers = array_filter(
			$paniers,
			function ( $p ) {
				/** @var AmapressIntermittence_panier $p */
				return $p->getStatus() != 'cancelled';
			}
		);
		if ( count( $paniers ) > 0 ) {
			$echange = array_shift( $paniers );

			return $echange->getStatus();
		}

		return false;
	}

	public
	static function getUserPaniersIntermittents() {
		return self::getPanierIntermittents( array(
			'adherent' => amapress_current_user_id(),
		) );
	}

	public
	static function getPaniersIntermittentsToBuy() {
		return self::getPanierIntermittents( array(
			'status' => 'to_exchange',
		) );
	}

	/**
	 * @return AmapressIntermittence_panier[]
	 */
	public
	static function getPanierIntermittents(
		$args
	) {
		$args = wp_parse_args(
			$args,
			array(
				'panier_id'           => null,
				'contrat_instance_id' => null,
				'adherent'            => null,
				'repreneur'           => null,
				'lieu_id'             => null,
				'status'              => null,
				'date'                => null,
				'show_history'        => false,
				'show_futur'          => true,
				'history_days'        => 365,
			) );

//		$dt  = Amapress_Calendar::$get_next_events_start_date ?
//			Amapress_Calendar::$get_next_events_start_date :
//			$args['date'];
//		if ($args['date'] && $args['date'] < $dt){
//			$dt = $args['date'];
//		}
		$key = "amapress_getPanierIntermittents_{$args['history_days']}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = array_map( function ( $p ) {
				return AmapressIntermittence_panier::getBy( $p );
			}, get_posts( array(
				'post_type'      => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'amapress_intermittence_panier_date',
						'value'   => Amapress::start_of_day( Amapress::add_days( amapress_time(), - intval( $args['history_days'] ) ) ),
						'compare' => '>=',
						'type'    => 'NUMERIC'
					)
				)
			) ) );
			wp_cache_set( $key, $res );
		}

		$ret = $res;
		if ( ! empty( $args['panier_id'] ) ) {
			if ( ! is_array( $args['panier_id'] ) ) {
				$args['panier_id'] = [ $args['panier_id'] ];
			}
			$ret = array_filter(
				$ret,
				function ( $ip ) use ( $args ) {
					/** @var AmapressIntermittence_panier $ip */
					foreach ( $ip->getPanierIds() as $id ) {
						if ( in_array( $id, $args['panier_id'] ) ) {
							return true;
						}
					}

					return false;
				}
			);
		}
		if ( ! empty( $args['contrat_instance_id'] ) ) {
			if ( ! is_array( $args['contrat_instance_id'] ) ) {
				$args['contrat_instance_id'] = [ $args['contrat_instance_id'] ];
			}
			$ret = array_filter(
				$ret,
				function ( $ip ) use ( $args ) {
					/** @var AmapressIntermittence_panier $ip */

					foreach ( $ip->getContrat_instanceIds() as $id ) {
						if ( in_array( $id, $args['contrat_instance_id'] ) ) {
							return true;
						}
					}

					return false;
				}
			);
		}
		if ( ! empty( $args['adherent'] ) ) {
			$adherent = $args['adherent'];
			if ( ! is_array( $adherent ) ) {
				$adherent = AmapressContrats::get_related_users( $adherent );
			}
			$ret = array_filter(
				$ret,
				function ( $ip ) use ( $adherent ) {
					/** @var AmapressIntermittence_panier $ip */

					return in_array( $ip->getAdherentId(), $adherent );
				}
			);
		}
		if ( ! empty( $args['repreneur'] ) ) {
			$ret = array_filter(
				$ret,
				function ( $ip ) use ( $args ) {
					/** @var AmapressIntermittence_panier $ip */
					$repreneur = $args['repreneur'];
					$ask       = $ip->getAsk();
					$status    = $ip->getStatus();

					return ( 'exch_valid_wait' == $status || 'exchanged' == $status )
					       && ( $ip->getRepreneurId() == $repreneur || isset( $ask[ $repreneur ] ) );
				}
			);
		}
		if ( ! empty( $args['lieu_id'] ) ) {
			$ret = array_filter(
				$ret,
				function ( $ip ) use ( $args ) {
					/** @var AmapressIntermittence_panier $ip */
					return $ip->getLieuId() == $args['lieu_id'];
				}
			);
		}
		if ( ! empty( $args['status'] ) ) {
			$ret = array_filter(
				$ret,
				function ( $ip ) use ( $args ) {
					/** @var AmapressIntermittence_panier $ip */
					return $ip->getStatus() == $args['status'];
				}
			);
			if ( $args['status'] == 'to_exchange' ) {
				$me  = AmapressContrats::get_related_users( amapress_current_user_id() );
				$ret = array_filter(
					$ret,
					function ( $ip ) use ( $me ) {
						/** @var AmapressIntermittence_panier $ip */
						return ! in_array( $ip->getAdherentId(), $me );
					}
				);
//				$meta_query[] = array(
//					'key'     => 'amapress_intermittence_panier_adherent',
//					'value'   => AmapressContrats::get_related_users( amapress_current_user_id() ),
//					'compare' => 'NOT IN',
//					'type'    => 'NUMERIC',
//				);
			}
		}
		if ( ! empty( $args['date'] ) ) {
//			$meta_query[] = array(
//				'key'   => 'amapress_intermittence_panier_date',
//				'value' => Amapress::start_of_day( $args['date'] ),
//				'type'  => 'NUMERIC'
//			);
			$ret = array_filter(
				$ret,
				function ( $ip ) use ( $args ) {
					/** @var AmapressIntermittence_panier $ip */
					return Amapress::start_of_day( $ip->getDate() ) == Amapress::start_of_day( $args['date'] );
				}
			);
		} else {
			if ( ! $args['show_history'] ) {
//			$meta_query[] = array(
//				'key'     => 'amapress_intermittence_panier_date',
//				'value'   => Amapress::start_of_day( amapress_time() ),
//				'compare' => '>=',
//				'type'    => 'NUMERIC'
//			);
				$ret = array_filter(
					$ret,
					function ( $ip ) use ( $args ) {
						/** @var AmapressIntermittence_panier $ip */
						return $ip->getDate() >= Amapress::start_of_day( amapress_time() );
					}
				);
			}
			if ( ! $args['show_futur'] ) {
				$ret = array_filter(
					$ret,
					function ( $ip ) use ( $args ) {
						/** @var AmapressIntermittence_panier $ip */
						return $ip->getDate() <= Amapress::end_of_day( amapress_time() );
					}
				);
			}
		}

//		$args  = array(
//			'post_type'      => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
//			'posts_per_page' => - 1,
//			'meta_query'     => $meta_query
//		);
//		$posts = get_posts(
//			$args
//		);
//
//		return array_map( function ( $p ) {
//			return AmapressIntermittence_panier::getBy( $p );
//		}, $posts );
		return $ret;
	}

	/** @return AmapressPanier */
	public
	static function getPanierForDist(
		$dist_date, $contrat_instance_id
	) {
		$res = get_posts(
			array(
				'post_type'      => 'amps_panier',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_panier_date',
							'value'   => array(
								Amapress::start_of_day( $dist_date ),
								Amapress::end_of_day( $dist_date )
							),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						),
						array(
							array(
								'key'     => 'amapress_panier_status',
								'value'   => 'delayed',
								'compare' => '=',
							),
							array(
								'key'     => 'amapress_panier_date_subst',
								'value'   => array(
									Amapress::start_of_day( $dist_date ),
									Amapress::end_of_day( $dist_date )
								),
								'compare' => 'BETWEEN',
								'type'    => 'NUMERIC',
							),
						),
					),
					array(
						'key'     => 'amapress_panier_contrat_instance',
						'value'   => $contrat_instance_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				)
			) );
		if ( count( $res ) == 0 ) {
			return null;
		}

		return AmapressPanier::getBy( array_shift( $res ) );
	}

	/** @return AmapressPanier[] */
	public
	static function getPaniersForDist(
		$dist_date
	) {
		$res = get_posts( array(
			'post_type'      => 'amps_panier',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_panier_date',
						'value'   => array( Amapress::start_of_day( $dist_date ), Amapress::end_of_day( $dist_date ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
					array(
						array(
							'key'     => 'amapress_panier_status',
							'value'   => 'delayed',
							'compare' => '=',
						),
						array(
							'key'     => 'amapress_panier_date_subst',
							'value'   => array(
								Amapress::start_of_day( $dist_date ),
								Amapress::end_of_day( $dist_date )
							),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						),
					)
				)
			)
		) );

		return array_map( function ( $r ) {
			return AmapressPanier::getBy( $r );
		}, $res );
	}

	public
	static function getPanierQuantiteTable(
		$table_id, $quantites, $options = array()
	) {
		$options = wp_parse_args( $options,
			array(
				'show_price'    => false,
				'show_quantite' => true,
				'empty_desc'    => __( 'Pas de produit à cette distribution', 'amapress' ),
			) );
		$columns = array();
		if ( $options['show_quantite'] ) {
			$columns[] = array(
				'title' => __( 'Quantité', 'amapress' ),
				'data'  => array(
					'_'    => 'quantite',
					'sort' => 'quantite',
				)
			);
		}
//        $columns[] = array(
//            'title' => '',
//            'data' => array(
//                '_' => 'produit_photo',
//                'sort' => 'produit_photo',
//            )
//        );
		$columns[] = array(
			'title' => __( 'Produit', 'amapress' ),
			'data'  => array(
				'_'    => 'produit',
				'sort' => 'produit',
			)
		);
		if ( $options['show_price'] ) {
			$columns[] = array(
				'title' => __( 'Prix', 'amapress' ),
				'data'  => array(
					'_'    => 'price',
					'sort' => 'price',
				)
			);
		}
		if ( empty( $quantites ) ) {
			return '<div class="panier-vide">' . esc_html( $options['empty_desc'] ) . '</div>';
		} else {
			return amapress_get_datatable( 'liste-emargement-contrat-variable-' . $table_id, $columns, $quantites,
				array(
					'paging'    => true,
					'searching' => false,
					"language"  => array( 'emptyTable' => $options['empty_desc'] )
				),
				array(
					Amapress::DATATABLES_EXPORT_EXCEL,
					Amapress::DATATABLES_EXPORT_PRINT
				) );
		}
	}

	public
	static function getPanierContentHtml(
		$panier_id, $lieu_id = null
	) {
		global $post;
		if ( ! $panier_id && $post && $post->post_type == 'amps_panier' ) {
			$panier_id = $post->ID;
		}
		$panier = get_post( intval( $panier_id ) );
		if ( ! $panier ) {
			return '';
		}

		$pani = AmapressPanier::getBy( $panier );
		if ( $pani->getContrat_instance() == null ) {
			return __( 'Sélectionner le contrat associé', 'amapress' );
		}
		$quantites = AmapressContrats::get_contrat_quantites( $pani->getContrat_instanceId() );

		ob_start();

		$produits_in_panier = array();
		if ( $pani->getContrat_instance()->isPanierVariable() ) {
			$adhs = $pani->getContrat_instance()->getAdhesionsForUser( null, $pani->getDate() );
			if ( amapress_is_user_logged_in() && ! empty( $adhs ) ) {
				$produits_objects = array();
				/** @var AmapressAdhesion $adh */
				foreach ( $adhs as $adh ) {
					foreach ( $adh->getVariables_Contrat_quantites( $pani->getDate() ) as $quant ) {
						$quantite = $quant['quantite'];
						/** @var AmapressContrat_quantite $contrat_quantite */
						$contrat_quantite = $quant['contrat_quantite'];

						$produits_in_panier = array_merge( $produits_in_panier, $contrat_quantite->getProduitsIds() );

						$produits_objects[] = array(
							'produit'  => esc_html( $contrat_quantite->getTitle() ),
							'price'    => $contrat_quantite->getPrix_unitaireDisplay(),
							'quantite' => $contrat_quantite->formatValue( $quantite ),
						);
					}
				}
				echo '<h4>' . __( 'Les produits de cette livraison', 'amapress' ) . '</h4>';
				echo self::getPanierQuantiteTable( 'all-' . $pani->getContrat_instanceId() . '-' . $pani->ID, $produits_objects );
			} else {
				$produits_objects = array();
				foreach ( AmapressContrats::get_contrat_quantites( $pani->getContrat_instanceId() ) as $contrat_quantite ) {
					$produits_objects[] = array(
						'produit'  => esc_html( $contrat_quantite->getTitle() ),
						'price'    => $contrat_quantite->getPrix_unitaireDisplay(),
						'quantite' => $contrat_quantite->getPriceUnitDisplay(),
					);
				}
				echo '<h4>' . __( 'Les produits disponibles pour ce contrat', 'amapress' ) . '</h4>';
				echo self::getPanierQuantiteTable( 'public-' . $pani->getContrat_instanceId() . '-' . $pani->ID, $produits_objects );
			}
		} else {
//			$dist_is_today = Amapress::start_of_day( $pani->getDate() ) == Amapress::start_of_day( amapress_time() );
//			$dist_is_after = Amapress::start_of_day( $pani->getDate() ) >= Amapress::start_of_day( amapress_time() );


//			$produits_objects = array();
//			foreach ( $produits as $produit ) {
//				$produits_abo = Amapress::get_post_meta_array( intval( $panier_id ), 'amapress_panier_produits_' . $produit->ID . '_quants' );
//				$u            = '';
//				foreach ( array( 'unit' => __('Unité', 'amapress'), 'kg' => 'kg' ) as $unit => $unit_label ) {
//					if ( $unit == $produits_abo['unit'] ) {
//						$u = $unit_label;
//					}
//				}
//				foreach ( $quantites as $quantite ) {
//					if ( ! isset( $produits_objects[ $quantite->ID ] ) ) {
//						$produits_objects[ $quantite->ID ] = array();
//					}
//					$produits_objects[ $quantite->ID ][] = array(
//						'produit'  => '<img class="panier-produit-photo" alt="' . esc_attr( $produit->getTitle() ) . '" src="' . amapress_get_avatar_url( $quantite->ID, null, 'produit-thumb', 'default_produit.jpg' ) . '" />' . '<a href="' . esc_attr( $quantite->getPermalink() ) . '">' . esc_html( $quantite->getTitle() ) . '</a>',
//						'price'    => $quantite->getPrix_unitaire(),
//						'quantite' => $produits_abo[ $quantite->ID ] . $u,
//					);
//				}
//			}

			$user_quantites     = null;
			$user_quantites_ids = null;
			if ( amapress_is_user_logged_in() ) {
				$user_quantites     = AmapressAdhesion::getQuantitesForUser( null, null, $pani->getDate() );
				$user_quantites_ids = array_map( function ( $v ) {
					/** @var AmapressAdhesionQuantite $v */
					return $v->getId();
				}, $user_quantites );
			}

			foreach ( $quantites as $quantite ) {
				if ( ! $quantite->isInDistributionDates( $pani->getDate() ) ) {
					continue;
				}
				if ( empty( $quantite->getContrat_instance() ) ) {
					continue;
				}

				if ( ! empty( $user_quantites_ids ) && ! in_array( $quantite->ID, $user_quantites_ids ) ) {
					continue;
				}

				$produits = $pani->getSelectedProduits( $quantite );
				foreach ( $produits as $prod ) {
					$produits_in_panier[] = $prod->getID();
				}

				$url    = amapress_get_avatar_url( $quantite->ID, null, 'produit-thumb', 'default_contrat.jpg' );
				$title  = ! empty( $user_quantites_ids ) ? $user_quantites[ $quantite->getID() ]->getTitleWithFactor() : $quantite->getTitle();
				$factor = $quantite->getContrat_instance()->getDateFactorDisplay( $pani->getDate() );
				if ( ! empty( $factor ) ) {
					$title = "$factor - $title";
				}
				echo '<h3><img class="dist-panier-quantite-img" src="' . $url . '" alt="" /> ' . esc_html( $title );
//                if (amapress_is_user_logged_in()) {
//                    if ($dist_is_after && !$dist_is_today) {
//                        $inter_status = self::isIntermittent($panier_id, $pani->getContrat_instance()->ID, $lieu_id);
//                        if ($inter_status) {
//                            if ($inter_status == 'to_exchange') {
//                                amapress_echo_button(__('Panier sur la liste d\'intermittence', 'amapress'), Amapress::getPageLink('mes-paniers-intermittents-page'),
//                                    'fa-fa', false, null, 'amap-button-echanger');
//                            } else {
//                                amapress_echo_button(__('Panier échangé', 'amapress'), Amapress::getPageLink('mes-paniers-intermittents-page'),
//                                    'fa-fa', false, null, 'amap-button-echanger');
//                            }
//                        } else {
//                            amapress_echo_button(__('Céder mon panier', 'amapress'), amapress_action_link($panier_id, 'echanger', array($quantite->getPost()->post_name)),
//                                'fa-fa', false, __('Etes-vous sûr de vouloir céder votre panier ?', 'amapress'), 'amap-button-echanger');
//                        }
//                    }
//                }
				echo '</h3>';
				echo '<div class="dist-panier-quantite-content">';

				echo '<p class="panier-quantite-description">' . $quantite->getDescription() . '</p>';

				echo '<div class="panier-contenu">' . $pani->getContenu( $quantite ) . '</div>';

//				if ( isset( $produits_objects[ $quantite->ID ] ) ) {
//					echo self::getPanierQuantiteTable( $quantite->ID, $produits_objects[ $quantite->ID ] );
//				} else {
//					echo self::getPanierQuantiteTable( $quantite->ID, array(), array( 'empty_desc' => __('Contenu du panier de cette distribution non rempli.', 'amapress') ) );
//				}

				if ( amapress_current_user_can( 'edit_panier' ) ) {
					echo '<br/>' . amapress_get_button( __( 'Renseigner le contenu du panier', 'amapress' ), admin_url( "post.php?post=$panier_id&action=edit" ), 'fa-fa' );
				}

//                    $arr = isset($produits_objects[$quantite->ID]) ? $produits_objects[$quantite->ID] : null;
//                    if (!$arr) $arr = array();
				if ( ! empty( $produits ) ) {
					echo '<h4>' . __( 'Produits associés', 'amapress' ) . '</h4>';
					echo amapress_generic_gallery(
						array_map(
							function ( $p ) {
								return [
									'produit'  => $p,
									'price'    => null,
									'quantite' => null,
									'unit'     => null,
								];
							}
							, $produits ),
						'produit_panier_cell', [
						'if_empty' => __( 'Contenu du panier de cette distribution non rempli.', 'amapress' )
					] );
				}

				echo '</div>';
			}
			//$quantites = array_filter($quantites, '');
//            } else {
//                foreach ($quantites as $quantite) {
//                    $url = amapress_get_avatar_url($quantite->ID, null, 'produit-thumb', 'default_contrat.jpg');
//                    echo '<h3><img class="dist-panier-quantite-img" src="' . $url . '" alt="" /> ' . esc_html($quantite->post_title) . '</h3>';
//                    if (isset($produits_objects[$quantite->ID])) {
//                        echo amapress_generic_gallery($produits_objects[$quantite->ID], 'panier', 'produit_panier_cell', $panier_empty);
//
//                        foreach ($produits_objects[$quantite->ID] as $prod)
//                            $produits_in_panier[] = $prod['produit']->ID;
//                    }
//                }
//            }
		}

		$produits_in_panier = implode( ',', array_unique( $produits_in_panier ) );
		if ( ! empty( $produits_in_panier ) ) {
			echo '<h3>' . amapress_get_font_icon( 'fa-fa' ) . ' ' . __( 'Recettes associées', 'amapress' ) . '</h3>';
			$recette_edit_link = ( amapress_current_user_can( 'publish_recette' ) ?
				amapress_get_button( __( 'Publier une nouvelle recette', 'amapress' ), admin_url( "post-new.php?post_type=amps_recette" ), 'fa-fa' ) :
				amapress_is_user_logged_in() && ! empty( Amapress::getOption( 'publier-recette-page' ) ) ? amapress_get_button( __( 'Proposer une nouvelle recette', 'amapress' ), get_post_permalink( Amapress::getOption( 'publier-recette-page' ) ), 'fa-fa' ) : '' );
			$recette_empty     = '<span class="recette-empty">' . __( 'Pas de recettes pour les produits présents dans le panier', 'amapress' ) . '</span> ' . $recette_edit_link;
			$no_recettes       = urlencode( $recette_empty );
			echo get_amapress_recettes_gallery(
				[
					'produits' => $produits_in_panier,
					'if_empty' => $no_recettes
				]
			);
		}

		$ret = ob_get_clean();

		return $ret;
	}
}
