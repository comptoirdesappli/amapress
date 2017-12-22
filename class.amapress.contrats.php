<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//require_once 'class.virtualthemedpage.php';

/**
 * class short summary.
 *
 * class description.
 *
 * @version 1.0
 * @author Guillaume
 */
class AmapressContrats {
	public static $initiated = false;

	//private static $vp = null;

	public static function init() {
//		if (!self::$vp) self::$vp = new Virtual_Themed_Pages_BC();
		amapress_register_shortcode( 'adhesions', array( 'AmapressContrats', 'adhesions_shortcode' ) );
//		self::$vp->add('#/mes-adhesions#i', array('AmapressContrats','virtual_adhesions'));
//		self::$vp->add('#/adhesion#i', array('AmapressContrats','virtual_adhesion'));

		// THE AJAX ADD ACTIONS
		add_action( 'wp_ajax_update_contrat_status_action', array(
			'AmapressContrats',
			'update_contrat_status_action'
		) );
		// enqueue and localise scripts
		// THE AJAX ADD ACTIONS
		add_action( 'wp_ajax_update_paiement_status_action', array(
			'AmapressContrats',
			'update_paiement_status_action'
		) );
		//add_action( 'wp_ajax_nopriv_user_likebox_action', array('AmapressUsers','user_likebox_produit_action'));
	}

	public static function renouvellementDelta( $date ) {
		$renouv_days = Amapress::getOption( 'renouv_days' );
		if ( empty( $renouv_days ) ) {
			$renouv_days = 30;
		}

		return Amapress::add_days( $date, - $renouv_days );
	}

	public static function get_contrat_status( $contrat_id, &$result ) {
		$dists   = AmapressDistributions::generate_distributions( $contrat_id, false, true );
		$paniers = AmapressPaniers::generate_paniers( $contrat_id, false, true );
		//$commands = AmapressCommandes::generate_commandes($contrat_id, true, true);

		if ( ! isset( $dists[ $contrat_id ] ) ) {
			$res = 'no';

			return 'Infos non dispo';
		}

		$result = count( $dists[ $contrat_id ]['missing'] ) > 0
		          || count( $dists[ $contrat_id ]['associate'] ) > 0
		          || count( $dists[ $contrat_id ]['unassociate'] ) > 0
		          //|| count($commands[$contrat_id]['missing']) > 0
		          //|| count($commands[$contrat_id]['orphan']) > 0
		          || count( $paniers[ $contrat_id ] ) > 0;

		return sprintf( 'Distributions : %d manquantes ; %d à associer ; %d à déassocier\n
                        Paniers : %d manquants\n
                        Comandes : %d manquants ; %d à annuler',
			count( $dists[ $contrat_id ]['missing'] ), count( $dists[ $contrat_id ]['associate'] ), count( $dists[ $contrat_id ]['unassociate'] ),
			count( $paniers[ $contrat_id ] ),
			0, //count($commands[$contrat_id]['missing']),
			0 //count($commands[$contrat_id]['orphan'])
		);
	}

	public static function contratStatus( $contrat_id ) {
		$res = false;
		$tt  = self::get_contrat_status( $contrat_id, $res );
		if ( $res === true ) {
			return '<div class="status"><div class="contrat-status"><button class="contrat-status-button" title="' . esc_attr( $tt ) . '" data-contrat-instance="' . $contrat_id . '">Mettre à jour distributions et paniers</button><div></div>';
		} else if ( $res == 'no' ) {
			return '<div class="status"><div class="contrat-status" style="color: red;">Pas de dates</div></div>';
		} else {
			return '<div class="status"><div class="contrat-status" style="color: green;">OK</div></div>';
		}
	}

	static function update_contrat_status_action() {
		if ( ! isset( $_POST['contrat_instance'] ) ) {
			die( 'Missing contrat instance in query' );
		}

		$contrat_id = intval( $_POST['contrat_instance'] );
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		AmapressDistributions::generate_distributions( $contrat_id, false, false );
		AmapressPaniers::generate_paniers( $contrat_id, false, false );
//		AmapressCommandes::generate_commandes( $contrat_id, true, false );
		$wpdb->query( 'COMMIT' );
		echo self::contratStatus( $contrat_id );// this is passed back to the javascript function
		die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
	}

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_contrat_instances( $from_end_date = null, $ignore_renouv_delta = false ) {
		$key = "amapress_get_contrat_instances_{$from_end_date}_{$ignore_renouv_delta}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( ! $from_end_date ) {
				$from_end_date = amapress_time();
			}
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'amapress_contrat_instance_date_fin',
						'value'   => Amapress::end_of_day( $ignore_renouv_delta ? $from_end_date : AmapressContrats::renouvellementDelta( $from_end_date ) ),
						'compare' => '>=',
						'type'    => 'NUMERIC'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => "amapress_contrat_instance_ended",
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => "amapress_contrat_instance_ended",
							'value'   => 0,
							'compare' => '=',
						),
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return int[]
	 */
	public static function get_contrat_ids( $producteur_id = null, $order = true, $filter = false ) {
		return array_map( function ( $c ) {
			return $c->ID;
		}, self::get_contrats( $producteur_id, $order, $filter ) );
	}

	/**
	 * @return AmapressContrat[]
	 */
	public static function get_contrats( $producteur_id = null, $order = true, $filter = false ) {
		$key = "amapress_get_contrats_{$producteur_id}_{$order}_{$filter}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'post_type'      => AmapressContrat::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
			);
			if ( $producteur_id ) {
				$query['meta_query'] = array(
					array(
						'key'     => 'amapress_contrat_producteur',
						'value'   => $producteur_id,
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				);
			}
			$res           = array_map( function ( $p ) {
				return AmapressContrat::getBy( $p );
			}, get_posts( $query ) );
			$contrat_order = Amapress::getOption( 'contrats_order' );
			if ( ! empty( $contrat_order ) ) {
				$contrat_order = array_map( 'intval', $contrat_order );
				if ( $filter ) {
					$res = array_filter( $res, function ( $c ) use ( $contrat_order ) {
						return in_array( $c->ID, $contrat_order );
					} );
				}
				if ( $order ) {
					usort( $res, function ( $a, $b ) use ( $contrat_order ) {
						if ( $a->ID == $b->ID ) {
							return 0;
						}
						$aix = array_search( $a->ID, $contrat_order );
						$bix = array_search( $b->ID, $contrat_order );

						return ( $aix < $bix ? - 1 : 1 );
					} );
				}
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	/** @return AmapressContrat[] */
	public static function get_subscribable_contrats( $producteur_id = null, $date = null, $order = true, $filter = false ) {
		$key = "amapress_get_subscribable_contrats_{$producteur_id}_{$date}_{$order}_{$filter}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			/** @var AmapressContrat_instance[] $contrat_instances */
			$contrat_instances = array_merge(
				self::get_subscribable_contrat_instances_by_contrat( null, $date ),
				self::get_active_contrat_instances( null, $date )
			);
			$contrats_ids      = array();
			$contrats          = array();
			foreach ( $contrat_instances as $ci ) {
				$contrat = $ci->getModel();
				if ( in_array( $contrat->ID, $contrats_ids ) ) {
					continue;
				}
				if ( $producteur_id != null && $producteur_id != $contrat->getProducteurId() ) {
					continue;
				}
				$contrats[]     = $contrat;
				$contrats_ids[] = $contrat->ID;
			}
			$contrat_order = Amapress::getOption( 'contrats_order' );
			if ( ! empty( $contrat_order ) ) {
				$contrat_order = array_map( 'intval', $contrat_order );
				if ( $filter ) {
					$contrats = array_filter( $contrats, function ( $c ) use ( $contrat_order ) {
						return in_array( $c->ID, $contrat_order );
					} );
				}
				if ( $order ) {
					usort( $contrats, function ( $a, $b ) use ( $contrat_order ) {
						if ( $a->ID == $b->ID ) {
							return 0;
						}
						$aix = array_search( $a->ID, $contrat_order );
						$bix = array_search( $b->ID, $contrat_order );

						return ( $aix < $bix ? - 1 : 1 );
					} );
				}
			}
			$res = $contrats;
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function get_active_contrat_instances_ids( $contrat_instance_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key = "amapress_get_active_contrat_instances_ids_{$contrat_instance_id}_{$date}_{$ignore_renouv_delta}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( $date == null ) {
				$date = amapress_time();
			}
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
//TODO check
//                    array(
//                        'key' => 'amapress_contrat_instance_date_debut',
//                        'value' => Amapress::start_of_day($date),
//                        'compare' => '<=',
//                        'type' => 'NUMERIC'),
					array(
						'key'     => 'amapress_contrat_instance_date_fin',
						'value'   => Amapress::end_of_day( $ignore_renouv_delta ? $date : AmapressContrats::renouvellementDelta( $date ) ),
						'compare' => '>=',
						'type'    => 'NUMERIC'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => "amapress_contrat_instance_ended",
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => "amapress_contrat_instance_ended",
							'value'   => 0,
							'compare' => '=',
						),
						array(
							'key'     => 'amapress_contrat_instance_date_fin',
							'value'   => Amapress::end_of_day( $date ),
							'compare' => '>=',
							'type'    => 'NUMERIC'
						),
					),
				)
			);
			if ( $contrat_instance_id ) {
				$query['include'] = array( $contrat_instance_id );
				unset( $query['meta_query'] );
			}
			$res = get_posts( $query );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_active_contrat_instances( $contrat_instance_id = null, $date = null, $ignore_renouv_delta = false ) {
		return array_map( function ( $p ) {
			return AmapressContrat_instance::getBy( $p );
		}, self::get_active_contrat_instances_ids( $contrat_instance_id, $date, $ignore_renouv_delta ) );
	}

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_active_contrat_instances_by_contrat( $contrat_id, $date = null, $ignore_renouv_delta = false ) {
		if ( ! is_array( $contrat_id ) ) {
			$contrat_id = array( $contrat_id );
		}
		$key_ids = implode( '-', $contrat_id );
		$key     = "amapress_get_active_contrat_instances_by_contrat_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			if ( ! $date ) {
				$date = amapress_time();
			}
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
//TODO check
//                    array(
//                        'key' => 'amapress_contrat_instance_date_debut',
//                        'value' => Amapress::start_of_day($date),
//                        'compare' => '<=',
//                        'type' => 'NUMERIC'),
					array(
						'key'     => 'amapress_contrat_instance_date_fin',
						'value'   => Amapress::end_of_day( $ignore_renouv_delta ? $date : AmapressContrats::renouvellementDelta( $date ) ),
						'compare' => '>=',
						'type'    => 'NUMERIC'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => "amapress_contrat_instance_ended",
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => "amapress_contrat_instance_ended",
							'value'   => 0,
							'compare' => '=',
						),
					),
					array(
						'key'     => 'amapress_contrat_instance_model',
						'value'   => $contrat_id,
						'compare' => 'IN',

					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_all_contrat_instances_by_contrat( $contrat_id ) {
		if ( ! is_array( $contrat_id ) ) {
			$contrat_id = array( $contrat_id );
		}
		$key_ids = implode( '-', $contrat_id );
		$key     = "amapress_get_all_contrat_instances_by_contrat_{$key_ids}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'post_status'    => 'any',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_instance_model',
						'value'   => $contrat_id,
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_subscribable_contrat_instances( $contrat_id = null, $date = null ) {
		$key = "amapress_get_subscribable_contrat_instances_{$contrat_id}_{$date}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( ! $date ) {
				$date = amapress_time();
			}
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_contrat_instance_date_ouverture',
							'value'   => Amapress::start_of_day( $date ),
							'compare' => '<=',
							'type'    => 'NUMERIC'
						),
//                    array(
//                        array(
//                            'key' => 'amapress_contrat_instance_date_ouverture',
//                            'value' => null,
//                            'compare' => 'NOT EXISTS'),
//                        array(
//                            'key' => 'amapress_contrat_instance_date_debut',
//                            'value' => $date,
//                            'compare' => '<=',
//                            'type' => 'NUMERIC'),
//                    ),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_contrat_instance_date_cloture',
							'value'   => Amapress::end_of_day( $date ),
							'compare' => '>=',
							'type'    => 'NUMERIC'
						),
//                    array(
//                        array(
//                            'key' => 'amapress_contrat_instance_date_cloture',
//                            'value' => null,
//                            'compare' => 'NOT EXISTS'),
//                        array(
//                            'key' => 'amapress_contrat_instance_date_fin',
//                            'value' => $date,
//                            'compare' => '>=',
//                            'type' => 'NUMERIC'),
//                    ),
					),
				)
			);
			if ( $contrat_id ) {
				$query['include'] = array( $contrat_id );
				unset( $query['meta_query'] );
			}

//        $q = new WP_Query($query);
//        die($q->request);

			$res = array_map( function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_subscribable_contrat_instances_by_contrat( $contrat_id, $date = null ) {
		$key = "amapress_get_subscribable_contrat_instances_by_contrat_{$contrat_id}_{$date}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( ! $date ) {
				$date = amapress_time();
			}
			$meta_query = array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_contrat_instance_date_ouverture',
						'value'   => Amapress::start_of_day( $date ),
						'compare' => '<=',
						'type'    => 'NUMERIC'
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_contrat_instance_date_cloture',
						'value'   => Amapress::end_of_day( $date ),
						'compare' => '>=',
						'type'    => 'NUMERIC'
					),
				),
			);
			if ( ! empty( $contrat_id ) ) {
				$meta_query[] = array(
					'key'     => 'amapress_contrat_instance_model',
					'value'   => $contrat_id,
					'compare' => '=',
					'type'    => 'NUMERIC'
				);
			}
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => $meta_query
			);
			$res   = array_map( function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function getReferentProducteursAndLieux( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$key = "amapress_getReferentProducteursAndLieux";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			global $amapress_getting_referent_infos;

			$amapress_getting_referent_infos = true;
			$lieu_ids                        = Amapress::get_lieu_ids();
			$res                             = array();
			foreach ( Amapress::get_producteurs() as $prod ) {
				$contrats    = self::get_contrats( $prod->ID );
				$contrat_ids = array_map( function ( $c ) {
					return $c->ID;
				}, $contrats );
				if ( count( $contrat_ids ) == 0 ) {
					$contrat_ids = array( 0 );
				}
				$contrat_instance_ids = Amapress::getIDs( AmapressContrats::get_all_contrat_instances_by_contrat( $contrat_ids ) );
				if ( count( $contrat_instance_ids ) == 0 ) {
					$contrat_instance_ids = array( 0 );
				}
				foreach ( $lieu_ids as $lieu_id ) {
					foreach ( $prod->getReferentsIds( $lieu_id ) as $ref_id ) {
						if ( $ref_id ) {
//                    if (!$ignore_lieu)
							$res[] = array(
								'ref_id'               => $ref_id,
								'lieu'                 => $lieu_id,
								'producteur'           => $prod->ID,
								'contrat_ids'          => $contrat_ids,
								'contrat_instance_ids' => $contrat_instance_ids,
							);
						}
					}
				}
			}
			$amapress_getting_referent_infos = false;
			wp_cache_set( $key, $res );
		}

		$ret = $res;
		if ( $user_id ) {
			$ret = array_filter(
				$ret,
				function ( $a ) use ( $user_id ) {
					return $user_id == $a['ref_id'];
				}
			);
		}

		return $ret;
	}


	/**
	 * @return AmapressContrat_quantite[]
	 */
	public static function get_contrat_quantites( $contrat_instance_id ) {
		$key = "amapress_get_contrat_quantites_{$contrat_instance_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'post_type'      => AmapressContrat_quantite::INTERNAL_POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => - 1,
//            'orderby' => 'meta_value_num',
//            'order' => 'ASC',
//            'meta_key' => 'amapress_contrat_quantite_quantite',
				'meta_query'     => array(
					array(
						'key'     => 'amapress_contrat_quantite_contrat_instance',
						'value'   => $contrat_instance_id,
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressContrat_quantite::getBy( $p );
			}, get_posts( $query ) );
			usort( $res, function ( $a, $b ) {
				/** @var AmapressContrat_quantite $a */
				/** @var AmapressContrat_quantite $b */
				if ( abs( $a->getQuantite() - $b->getQuantite() ) < 0.01 ) {
					return $a->ID < $b->ID ? - 1 : 1;
				} else {
					return $a->getQuantite() < $b->getQuantite() ? - 1 : 1;
				}
			} );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

//    public static function get_contrat_paiements($contrat_id)
//    {
//        return get_posts(array('post_type' => 'amps_contrat_pmt',
//            'posts_per_page' => -1,
//            'meta_query' => array(
//                array(
//                    'key_num' => 'amapress_contrat_paiement_contrat_instance',
//                    'value' => $contrat_id,
//                    'compare' => '=',
//                    'type' => 'NUMERIC'),
//            )));
//    }

	public static function get_active_contrat_instances_ids_by_contrat( $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		return array_map( array(
			'Amapress',
			'to_id'
		), AmapressContrats::get_active_contrat_instances_by_contrat( $contrat_id, $date, $ignore_renouv_delta ) );
	}

	public static function get_active_adhesions_ids( $contrat_id = null, $contrat_quantite_id = null, $lieu_id = null, $date = null, $ignore_renouv_delta = false ) {
		return array_map( array( 'Amapress', 'to_id' ),
			self::get_active_adhesions( $contrat_id, $contrat_quantite_id, $lieu_id, $date, $ignore_renouv_delta ) );
	}


	/**
	 * @return AmapressAdhesion[]
	 */
	public static function get_active_adhesions( $contrat_id = null, $contrat_quantite_id = null, $lieu_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_active_adhesions_{$key_ids}_{$contrat_quantite_id}_{$lieu_id}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			if ( is_array( $contrat_id ) ) {
				$abo_ids = $contrat_id;
			} else {
				$abo_ids = AmapressContrats::get_active_contrat_instances_ids( $contrat_id, $date, $ignore_renouv_delta );
			}
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_adhesion_contrat_instance',
					'value'   => amapress_prepare_in( $abo_ids ),
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				)
			);
			if ( $lieu_id ) {
				$meta_query[] = array(
					'key'     => 'amapress_adhesion_lieu',
					'value'   => $lieu_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			}
			if ( $contrat_quantite_id ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_adhesion_contrat_quantite',
						'value'   => $contrat_quantite_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					amapress_prepare_like_in_array( 'amapress_adhesion_contrat_quantite', $contrat_quantite_id ),
				);
			}
			$meta_query[] = array(
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
			);
			$query        = array(
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => $meta_query
			);

			$res = array();
			foreach ( get_posts( $query ) as $p ) {
				$res[ $p->ID ] = AmapressAdhesion::getBy( $p );
//				$res[ $p->ID ]->getLieuId();
			}
//			var_dump($query);
//			var_dump($res);
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAdhesion[]
	 */
	public static function get_all_adhesions( $contrat_id = null, $contrat_quantite_id = null, $lieu_id = null ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_all_adhesions_{$key_ids}_{$contrat_quantite_id}_{$lieu_id}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$meta_query = array();
			if ( ! empty( $contrat_id ) ) {
				if ( is_array( $contrat_id ) ) {
					$meta_query[] = array(
						array(
							'key'     => 'amapress_adhesion_contrat_instance',
							'value'   => $contrat_id,
							'compare' => 'IN',
							'type'    => 'NUMERIC',
						)
					);
				} else {
					$meta_query[] = array(
						array(
							'key'     => 'amapress_adhesion_contrat_instance',
							'value'   => $contrat_id,
							'compare' => '=',
							'type'    => 'NUMERIC',
						)
					);
				}
			}

			if ( $lieu_id ) {
				$meta_query[] = array(
					'key'     => 'amapress_adhesion_lieu',
					'value'   => $lieu_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			}
			if ( $contrat_quantite_id ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_adhesion_contrat_quantite',
						'value'   => $contrat_quantite_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					amapress_prepare_like_in_array( 'amapress_adhesion_contrat_quantite', $contrat_quantite_id ),
				);
			}
			$query = array(
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'post_status'    => 'any',
				'meta_query'     => $meta_query
			);

			$res = array_map( function ( $p ) {
				return AmapressAdhesion::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	/**
	 * @return AmapressAmapien_paiement[]
	 */
	public static function get_all_paiements( $contrat_instance_id, $contrat_quantite = null, $lieu_id = null ) {
		$key = "amapress_get_all_paiements_{$contrat_instance_id}_{$contrat_quantite}_{$lieu_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_contrat_instance',
						'value'   => intval( $contrat_instance_id ),
						'compare' => '=',
					),
				)
			);
			if ( ! empty( $contrat_quantite ) ) {
				$query['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_adhesion_contrat_quantite',
						'value'   => $contrat_quantite,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					amapress_prepare_like_in_array( 'amapress_adhesion_contrat_quantite', $contrat_quantite ),
				);
			}
			if ( ! empty( $lieu_id ) ) {
				$query['meta_query'][] = array(
					'key'     => 'amapress_adhesion_lieu',
					'value'   => $lieu_id,
					'compare' => '=',
					'type'    => 'NUMERIC'
				);
			}
			$adhesions_ids = array_map( function ( $p ) {
				return $p->ID;
			}, get_posts( $query ) );
			$query         = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_paiement_adhesion',
						'value'   => amapress_prepare_in( $adhesions_ids ),
						'compare' => 'IN',
					),
				)
			);
			$res           = array_map( function ( $p ) {
				return new AmapressAmapien_paiement( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	public static function is_user_active_intermittent( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}
		$user = AmapressUser::getBy( $user_id );

		return $user && $user->isIntermittent();
	}


	/**
	 * @return AmapressIntermittence_panier[]
	 */
	public static function get_user_panier_intermittents( $user_id = null, $date = null ) {
		$key = "amapress_get_user_panier_intermittents_{$user_id}_{$date}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( ! $date ) {
				$date = amapress_time();
			}
			if ( $user_id == null ) {
				$user_id = amapress_current_user_id();
			}
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_intermittence_panier_date',
							'value'   => Amapress::start_of_day( $date ),
							'compare' => '>=',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'amapress_intermittence_status',
							'value'   => 'bought',
							'compare' => '=',
						),
					),
					array(
						'key'     => 'amapress_intermittence_panier_adherent',
						'value'   => intval( $user_id ),
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressIntermittence_panier::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressIntermittence_panier[]
	 */
	public static function get_active_panier_intermittents( $user_id = null, $date = null ) {
		$key = "amapress_get_active_panier_intermittents_{$user_id}_{$date}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( ! $date ) {
				$date = amapress_time();
			}
			if ( $user_id == null ) {
				$user_id = amapress_current_user_id();
			}
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_intermittence_panier_date',
						'value'   => Amapress::start_of_day( $date ),
						'compare' => '>=',
						'type'    => 'NUMERIC'
					),
					array(
						'key'     => 'amapress_intermittence_panier_adherent',
						'value'   => intval( $user_id ),
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressIntermittence_panier::getBy( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function get_related_users( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			return [];
		}

		$key = "amapress_get_related_users_{$user_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res  = array( $user_id );
			$user = AmapressUser::getBy( $user_id );
			if ( $user ) {
				if ( $user->getCoAdherent1() != null ) {
					$res[] = $user->getCoAdherent1Id();
				}
				if ( $user->getCoAdherent2() != null ) {
					$res[] = $user->getCoAdherent2Id();
				}
				$res = array_merge( $res, $user->getPrincipalUserIds() );
			}

			$in       = implode( ',', amapress_prepare_in( $res ) );
			$end_time = Amapress::end_of_day( amapress_time() );
			global $wpdb;
			$res = array_merge( $res,
				$wpdb->get_col(
					"SELECT mt3.meta_value
FROM $wpdb->postmeta
LEFT JOIN $wpdb->postmeta AS mt1
ON ($wpdb->postmeta.post_id = mt1.post_id
AND mt1.meta_key = 'amapress_adhesion_date_fin' ) 
LEFT JOIN $wpdb->postmeta AS mt2
ON ( $wpdb->postmeta.post_id = mt2.post_id )
LEFT JOIN $wpdb->postmeta AS mt3
ON ( $wpdb->postmeta.post_id = mt3.post_id 
AND mt3.meta_key = 'amapress_adhesion_adherent' ) 
WHERE 1=1 
AND ( ( ( $wpdb->postmeta.meta_key = 'amapress_adhesion_adherent'
AND CAST($wpdb->postmeta.meta_value AS SIGNED) IN ($in) ) 
OR ( $wpdb->postmeta.meta_key = 'amapress_adhesion_adherent2'
AND CAST($wpdb->postmeta.meta_value AS SIGNED) IN ($in) ) 
OR ( $wpdb->postmeta.meta_key = 'amapress_adhesion_adherent3'
AND CAST($wpdb->postmeta.meta_value AS SIGNED) IN ($in) ) ) 
AND ( mt1.post_id IS NULL 
OR ( mt2.meta_key = 'amapress_adhesion_date_fin'
AND CAST(mt2.meta_value AS UNSIGNED) = 0 ) 
OR ( mt2.meta_key = 'amapress_adhesion_date_fin'
AND CAST(mt2.meta_value AS UNSIGNED) >= $end_time ) ) )"
				) );

			$res = array_unique( array_map( 'intval', $res ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

}
