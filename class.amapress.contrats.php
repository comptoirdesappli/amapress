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

	public static function init() {
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

	/** @return AmapressContrat_instance[] */
	public static function get_contrat_to_generate() {
		$contrats         = AmapressContrats::get_active_contrat_instances();
		$contrats_tocheck = array();
		foreach ( $contrats as $contrat ) {
			$res = false;
			AmapressContrats::get_contrat_status( $contrat->ID, $res );
			if ( $res ) {
				$contrats_tocheck[] = $contrat;
			}
		}

		return $contrats_tocheck;
	}

	public static function get_contrat_status( $contrat_id, &$need_generate ) {
		$post_status = get_post_status( $contrat_id );
		if ( 'draft' == $post_status || 'auto-draft' == $post_status ) {
			$need_generate = 'draft';

			return __( 'Brouillon, pas encore disponible', 'amapress' );
		} else if ( 'trash' == $post_status ) {
			$need_generate = 'trash';

			return __( 'Dans la corbeille, plus disponible', 'amapress' );
		}
		$dists   = AmapressDistributions::generate_distributions( $contrat_id, true );
		$paniers = AmapressPaniers::generate_paniers( $contrat_id, true );
		//$commands = AmapressCommandes::generate_commandes($contrat_id, true, true);

		if ( ! isset( $dists[ $contrat_id ] ) ) {
			$need_generate = 'no';

			return __( 'Pas de distributions', 'amapress' );
		}

		$need_generate = count( $dists[ $contrat_id ]['missing'] ) > 0
		                 || count( $dists[ $contrat_id ]['associate'] ) > 0
		                 || count( $dists[ $contrat_id ]['unassociate'] ) > 0
		                 //|| count($commands[$contrat_id]['missing']) > 0
		                 //|| count($commands[$contrat_id]['orphan']) > 0
		                 || count( $paniers[ $contrat_id ] ) > 0;

		return sprintf( __( "Distributions : %d manquantes ; %d à associer ; %d à déassocier\n
                        Paniers : %d manquants", 'amapress' ),
			count( $dists[ $contrat_id ]['missing'] ), count( $dists[ $contrat_id ]['associate'] ), count( $dists[ $contrat_id ]['unassociate'] ),
			count( $paniers[ $contrat_id ] )
		);
	}

	public static function contratStatus( $contrat_id, $tag = 'div' ) {
		$res     = false;
		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( ! $contrat ) {
			return '<' . $tag . ' class="status"><' . $tag . ' class="contrat-status" style="color: red;"><span>' . __( 'Supprimé', 'amapress' ) . '</span></' . $tag . '></' . $tag . '>';
		}
		if ( $contrat->isArchived() ) {
			return '<' . $tag . ' class="status"><' . $tag . ' class="contrat-status" style="color: green;"><span>' . __( 'Archivé', 'amapress' ) . '</span></' . $tag . '></' . $tag . '>';
		}

		$tt = self::get_contrat_status( $contrat_id, $res );
		if ( $res === true ) {
			return '<' . $tag . ' class="status"><' . $tag . ' class="contrat-status"><button class="contrat-status-button" title="' . esc_attr( $tt ) . '" data-contrat-instance="' . $contrat_id . '">' . __( 'Mettre à jour distributions et paniers', 'amapress' ) . '</button></' . $tag . '></' . $tag . '>';
		} else if ( $res === 'no' ) {
			return '<' . $tag . ' class="status"><' . $tag . ' class="contrat-status" style="color: red;"><span>' . __( 'Pas de dates', 'amapress' ) . '</span></' . $tag . '></' . $tag . '>';
		} else if ( $res === 'draft' ) {
			return '<' . $tag . ' class="status"><' . $tag . ' class="contrat-status" style="color: orange;"><span>' . __( 'Brouillon', 'amapress' ) . '</span></' . $tag . '></' . $tag . '>';
		} else if ( $res === 'trash' ) {
			return '<' . $tag . ' class="status"><' . $tag . ' class="contrat-status" style="color: red;"><span>' . __( 'Corbeille', 'amapress' ) . '</span></' . $tag . '></' . $tag . '>';
		} else {
			return '<' . $tag . ' class="status"><' . $tag . ' class="contrat-status" style="color: green;"><span>OK</span></' . $tag . '></' . $tag . '>';
		}
	}

	static function update_contrat_status_action() {
		if ( ! isset( $_POST['contrat_instance'] ) ) {
			die( 'Missing contrat instance in query' );
		}

		$contrat_id = intval( $_POST['contrat_instance'] );
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		AmapressDistributions::generate_distributions( $contrat_id, false );
		AmapressPaniers::generate_paniers( $contrat_id, false );
//		AmapressCommandes::generate_commandes( $contrat_id, true, false );
		$wpdb->query( 'COMMIT' );
		echo self::contratStatus( $contrat_id, 'div' );// this is passed back to the javascript function
		die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
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
	public static function get_contrats( $producteur_id = null, $order = true, $filter = false, $no_cache = false ) {
		$key = "amapress_get_contrats_{$producteur_id}";
		$res = wp_cache_get( $key );
		if ( $no_cache || false === $res ) {
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
			$res         = array_map( function ( $p ) {
				return AmapressContrat::getBy( $p );
			}, get_posts( $query ) );
			$contrat_ids = [];
			foreach ( $res as $c ) {
				$contrat_ids[] = $c->ID;
			}
			update_meta_cache( 'post', $contrat_ids );
			wp_cache_set( $key, $res );
		}

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
				if ( empty( $contrat ) ) {
					continue;
				}
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

	public static function get_active_contrat_instances_ids(
		$contrat_instance_id = null, $date = null,
		$ignore_renouv_delta = false,
		$include_futur = true
	) {
		if ( empty( $date ) ) {
			$date = amapress_time();
		}
		$date = Amapress::end_of_day( $date );

		$filter = Amapress::getFilterForReferent();
		if ( empty( $filter ) ) {
			$filter = 0;
		} else {
			$filter = 1;
		}
		if ( empty( $ignore_renouv_delta ) ) {
			$ignore_renouv_delta = 0;
		} else {
			$ignore_renouv_delta = 1;
		}

		$key = "amapress_get_active_contrat_instances_ids_{$contrat_instance_id}_{$date}_{$ignore_renouv_delta}_{$filter}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_contrat_instance_date_fin',
					'value'   => Amapress::start_of_day( $ignore_renouv_delta ? $date : AmapressContrats::renouvellementDelta( $date ) ),
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
			);
			if ( ! $include_futur ) {
				$meta_query[] = array(
					'key'     => 'amapress_contrat_instance_date_debut',
					'value'   => Amapress::start_of_day( $date ),
					'compare' => '<=',
					'type'    => 'NUMERIC'
				);
			}
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => $meta_query
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
	public static function get_active_contrat_instances( $contrat_instance_id = null, $date = null, $ignore_renouv_delta = false, $include_futur = true ) {
		if ( empty( $date ) ) {
			$date = amapress_time();
		}
		$date = Amapress::end_of_day( $date );


		$key = "amapress_get_active_contrat_instances_{$contrat_instance_id}_{$date}_{$ignore_renouv_delta}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$post_ids = self::get_active_contrat_instances_ids( $contrat_instance_id, $date, $ignore_renouv_delta, $include_futur );
			update_meta_cache( 'post', $post_ids );

			self::get_contrats( null, false, false );

			$kkey  = 'amapress_get_all_active_contrat_instances_' . implode( '_', $post_ids );
			$posts = wp_cache_get( $kkey );
			if ( false === $posts ) {
				$posts = get_posts( [
						'posts_per_page' => - 1,
						'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
						'post__in'       => $post_ids,
					]
				);
				wp_cache_set( $kkey, $posts );
			}
			$res = array_map( function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			}, $posts );
			$res = array_filter( $res, function ( $c ) {
				/** @var AmapressContrat_instance $c */
				return ! empty( $c->getModel() );
			} );
			wp_cache_set( $key, $res );
		}

		return $res;
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
						'type'    => 'NUMERIC'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			}, get_posts( $query ) );
			$res   = array_filter( $res, function ( $c ) {
				/** @var AmapressContrat_instance $c */
				return ! empty( $c->getModel() );
			} );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return int[]
	 */
	public static function get_all_contrat_instances_by_contrat_ids( $contrat_id, $no_cache = false ) {
		if ( ! is_array( $contrat_id ) ) {
			$contrat_id = array( $contrat_id );
		}
		$key_ids = implode( '_', $contrat_id );
		$key     = "amapress_get_all_contrat_instances_by_contrat_$key_ids";
		$res     = wp_cache_get( $key );
		if ( $no_cache || false === $res ) {
			$query = array(
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'post_status'    => [ 'draft', 'publish', 'trash' ],
				'fields'         => 'ids',
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
			$res   = get_posts( $query );
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
			$res = array_filter( $res, function ( $c ) {
				/** @var AmapressContrat_instance $c */
				return ! empty( $c->getModel() );
			} );
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
						'value'   => Amapress::start_of_day( $date ),
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
			$res   = array_filter( $res, function ( $c ) {
				/** @var AmapressContrat_instance $c */
				return ! empty( $c->getModel() );
			} );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function getReferentsForLieu( $lieu_id ) {
		return from( self::getAllReferentProducteursAndLieux() )->where( function ( $r ) use ( $lieu_id ) {
			return $r['lieu'] == $lieu_id;
		} )->toArray();
	}

	public static function getReferentsForProducteur( $producteur_id ) {
		return from( self::getAllReferentProducteursAndLieux() )->where( function ( $r ) use ( $producteur_id ) {
			return $r['producteur'] == $producteur_id;
		} )->toArray();
	}

	public static function getReferentsForContratInstance( $contrat_instance_id ) {
		return from( self::getAllReferentProducteursAndLieux() )->where( function ( $r ) use ( $contrat_instance_id ) {
			return in_array( $contrat_instance_id, $r['contrat_instance_ids'] );
		} )->toArray();
	}

	public static function getReferentsForContrat( $contrat_id ) {
		return from( self::getAllReferentProducteursAndLieux() )->where( function ( $r ) use ( $contrat_id ) {
			return in_array( $contrat_id, $r['contrat_instance_ids'] );
		} )->toArray();
	}

	public static function getAllReferentProducteursAndLieux() {
		return self::getReferentProducteursAndLieux( 'all' );
	}


	public static function getReferentProducteursAndLieux( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$key = 'amps_refs_prods';
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			Amapress::setFilterForReferent( false );

			$lieu_ids = Amapress::get_lieu_ids();
			$res      = array();
			foreach ( Amapress::get_producteurs( true ) as $prod ) {
				$contrats    = self::get_contrats( $prod->ID, false, false, true );
				$contrat_ids = array_map( function ( $c ) {
					return $c->ID;
				}, $contrats );
				if ( count( $contrat_ids ) == 0 ) {
					$contrat_ids = array( 0 );
				}
				foreach ( $contrat_ids as $contrat_id ) {
					$contrat_instance_ids = AmapressContrats::get_all_contrat_instances_by_contrat_ids( [ $contrat_id ], true );
					if ( count( $contrat_instance_ids ) == 0 ) {
						$contrat_instance_ids = array( 0 );
					}
					foreach ( $lieu_ids as $lieu_id ) {
						$contrat = AmapressContrat::getBy( $contrat_id );
						if ( $contrat ) {
							$contrat_lieu_ref_ids = $contrat->getReferentsIds( $lieu_id, true, true );
							$contrat_ref_ids      = $contrat->getReferentsIds( $lieu_id, false, true );
							$prod_lieu_ref_ids    = $prod->getReferentsIds( $lieu_id, true );
							foreach ( $contrat->getReferentsIds( $lieu_id ) as $ref_id ) {
								if ( $ref_id ) {
									$res[] = array(
										'ref_id'               => $ref_id,
										'lieu'                 => $lieu_id,
										'level'                =>
											( in_array( $ref_id, $contrat_lieu_ref_ids ) ? 'contrat_lieu' :
												( in_array( $ref_id, $contrat_ref_ids ) ? 'contrat' :
													( in_array( $ref_id, $prod_lieu_ref_ids ) ? 'prod_lieu' :
														'prod' ) ) ),
										'producteur'           => $prod->ID,
										'contrat_ids'          => [ $contrat_id ],
										'contrat_instance_ids' => $contrat_instance_ids,
									);
								}
							}
						} else {
							foreach ( $prod->getReferentsIds( $lieu_id ) as $ref_id ) {
								$prod_lieu_ref_ids = $prod->getReferentsIds( $lieu_id, true );
								if ( $ref_id ) {
									$res[] = array(
										'ref_id'               => $ref_id,
										'lieu'                 => $lieu_id,
										'level'                => ( in_array( $ref_id, $prod_lieu_ref_ids ) ? 'prod_lieu' : 'prod' ),
										'producteur'           => $prod->ID,
										'contrat_ids'          => [ $contrat_id ],
										'contrat_instance_ids' => $contrat_instance_ids,
									);
								}
							}
						}
					}
				}
			}
			Amapress::setFilterForReferent( true );
			wp_cache_set( $key, $res );
		}

		$ret = $res;
		if ( 'all' !== $user_id ) {
			$ret = array_filter(
				$ret,
				function ( $a ) use ( $user_id ) {
					return $user_id == $a['ref_id'];
				}
			);
			if ( current_user_can( 'referent' ) && empty( $ret ) ) {
				$ret = [
					array(
						'ref_id'               => $user_id,
						'lieu'                 => 0,
						'level'                => 'prod',
						'producteur'           => 0,
						'contrat_ids'          => [],
						'contrat_instance_ids' => [],
					)
				];
			}
		}

		return $ret;
	}


	/**
	 * @return AmapressContrat_quantite[]
	 */
	public static function get_contrat_quantites( $contrat_instance_id, $date = null, $order_by_groups = true ) {
		$key = "amapress_get_contrat_quantites_{$contrat_instance_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$active_contrat_instance_ids = [];
			foreach ( AmapressContrats::get_active_contrat_instances_ids( null, $date ) as $cid ) {
				if ( false === wp_cache_get( "amapress_get_contrat_quantites_$cid" ) ) {
					$active_contrat_instance_ids[] = $cid;
				}
			}
			if ( ! in_array( $contrat_instance_id, $active_contrat_instance_ids ) ) {
				$active_contrat_instance_ids[] = $contrat_instance_id;
			}
			$query   = array(
				'post_type'      => AmapressContrat_quantite::INTERNAL_POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => - 1,
				'orderby'        => 'none',
				'meta_query'     => array(
					array(
						'key'     => 'amapress_contrat_quantite_contrat_instance',
						'value'   => amapress_prepare_in( $active_contrat_instance_ids ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
				)
			);
			$all_res = array_map( function ( $p ) {
				return AmapressContrat_quantite::getBy( $p );
			}, get_posts( $query ) );
			foreach (
				array_group_by( $all_res, function ( $q ) {
					/** @var AmapressContrat_quantite $q */
					return $q->getContrat_instanceId();
				} ) as $cid => $quants
			) {
				usort( $quants, function ( $a, $b ) {
					/** @var AmapressContrat_quantite $a */
					/** @var AmapressContrat_quantite $b */
					if ( abs( $a->getQuantite() - $b->getQuantite() ) < 0.01 ) {
						return $a->ID < $b->ID ? - 1 : 1;
					} else {
						return $a->getQuantite() < $b->getQuantite() ? - 1 : 1;
					}
				} );
				wp_cache_set( "amapress_get_contrat_quantites_$cid", $quants );
			}
			$res = wp_cache_get( $key );
		}
		if ( empty( $res ) ) {
			$res = [];
		}

		if ( ! $order_by_groups ) {
			return $res;
		}

		$ordered_res = [];
		foreach ( $res as $quant ) {
			$grp_class_name = '';
			$has_group      = preg_match( '/^\s*\[([^\]]+)\]/', $quant->getTitle(), $matches );
			if ( $has_group ) {
				if ( isset( $matches[1] ) ) {
					$grp_class_name = sanitize_html_class( $matches[1] );
				}
			}
			if ( ! isset( $ordered_res[ $grp_class_name ] ) ) {
				$ordered_res[ $grp_class_name ] = [];
			}
			$ordered_res[ $grp_class_name ][] = $quant;
		}

		$res = [];
		foreach ( $ordered_res as $k => $grp ) {
			$res = array_merge( $res, array_values( $grp ) );
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
	public static function get_active_adhesions(
		$contrat_id = null,
		$contrat_quantite_id = null,
		$lieu_id = null,
		$date = null,
		$ignore_renouv_delta = false,
		$include_futur = true
	) {
		$filter  = Amapress::getFilterForReferent();
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_active_adhesions_{$filter}_{$key_ids}_{$contrat_quantite_id}_{$lieu_id}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			if ( null == $date ) {
				$date = amapress_time();
			}
			if ( is_array( $contrat_id ) ) {
				$abo_ids = $contrat_id;
			} else {
				$abo_ids = AmapressContrats::get_active_contrat_instances_ids( $contrat_id, $date, $ignore_renouv_delta, $include_futur );
			}
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_adhesion_contrat_instance',
					'value'   => amapress_prepare_in( $abo_ids ),
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				),
			);
			if ( ! $include_futur ) {
				$meta_query[] = array(
					'key'     => 'amapress_adhesion_date_debut',
					'value'   => Amapress::end_of_day( $date ),
					'compare' => '<=',
					'type'    => 'NUMERIC'
				);
			}
			if ( $lieu_id ) {
				$meta_query[] = array(
					'key'     => 'amapress_adhesion_lieu',
					'value'   => $lieu_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
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
						'value'   => Amapress::start_of_day( $date ),
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				)
			);
			$query        = array(
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'orderby'        => 'none',
				'meta_query'     => $meta_query
			);

			$res = array();
			foreach ( get_posts( $query ) as $p ) {
				$adh = AmapressAdhesion::getBy( $p );
				if ( $contrat_quantite_id ) {
					if ( ! in_array( $contrat_quantite_id, $adh->getContrat_quantites_IDs() ) ) {
						continue;
					}
				}
				$res[ $p->ID ] = $adh;
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAdhesion[]
	 */
	public static function get_all_adhesions( $contrat_id = null, $contrat_quantite_id = null, $lieu_id = null ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_all_adhesions_{$key_ids}_{$lieu_id}";
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
			$query = array(
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'post_status'    => [ 'publish', 'draft' ],
				'meta_query'     => $meta_query
			);

			$res = array_map( function ( $p ) {
				return AmapressAdhesion::getBy( $p );
			}, get_posts( $query ) );
			$res = array_filter( $res, function ( $a ) {
				/** @var AmapressAdhesion $a */
				return $a->getContrat_instance();
			} );
			wp_cache_set( $key, $res );
		}

		if ( $contrat_quantite_id ) {
			$res = array_filter( $res, function ( $a ) use ( $contrat_quantite_id ) {
				/** @var AmapressAdhesion $a */
				return in_array( $contrat_quantite_id, $a->getContrat_quantites_IDs() );
			} );
		}

		return $res;
	}


	/**
	 * @return AmapressAmapien_paiement[]
	 */
	public static function get_all_paiements( $contrat_instance_id, $lieu_id = null ) {
		$key = "amapress_get_all_paiements_{$contrat_instance_id}_{$lieu_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'orderby'        => 'none',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_contrat_instance',
						'value'   => intval( $contrat_instance_id ),
						'compare' => '=',
					),
				)
			);
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
						'type'    => 'NUMERIC'
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

	private static $related_user_cache = [];

	/* @return int[] */
	public static function get_related_users(
		$user_id, $allow_not_logged = false,
		$date = null, $contrat_id = null,
		$include_cofoyers = true,
		$include_coadhs = 'auto',
		$include_principal = true
	) {
		if ( ! $allow_not_logged && ! amapress_is_user_logged_in() ) {
			return [];
		}

		if ( null == $date ) {
			$date = amapress_time();
		}

		$date = Amapress::end_of_day( $date );

		$key = "amapress_get_related_users_{$user_id}_{$date}_{$contrat_id}_{$include_cofoyers}_{$include_coadhs}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res  = array( $user_id );
			$user = AmapressUser::getBy( $user_id );
			if ( $user ) {
				if ( true === $include_coadhs || ( 'auto' === $include_coadhs && ! Amapress::hasPartialCoAdhesion() ) ) {
					if ( $user->getCoAdherent1Id() ) {
						$res[] = $user->getCoAdherent1Id();
					}
					if ( $user->getCoAdherent2Id() ) {
						$res[] = $user->getCoAdherent2Id();
					}
					if ( $user->getCoAdherent3Id() ) {
						$res[] = $user->getCoAdherent3Id();
					}
				}
				if ( $include_cofoyers ) {
					if ( $user->getCoFoyer1Id() ) {
						$res[] = $user->getCoFoyer1Id();
					}
					if ( $user->getCoFoyer2Id() ) {
						$res[] = $user->getCoFoyer2Id();
					}
					if ( $user->getCoFoyer3Id() ) {
						$res[] = $user->getCoFoyer3Id();
					}
				}
				if ( $include_principal ) {
					$res = array_merge( $res, $user->getPrincipalUserIds() );
				}
			}

			if ( false !== $include_coadhs ) {
				$active_contrat_instances_ids = amapress_prepare_in_sql(
					AmapressContrats::get_active_contrat_instances_ids( $contrat_id, $date, true ) );
				if ( amapress_current_user_id() == $user_id ) {
					$in = amapress_prepare_in_sql( $res );

					global $wpdb;
					$res = array_merge( $res,
						amapress_get_col_cached(
							"SELECT mt3.meta_value
FROM $wpdb->postmeta
LEFT JOIN $wpdb->postmeta AS mt1
ON ($wpdb->postmeta.post_id = mt1.post_id
AND mt1.meta_key = 'amapress_adhesion_date_fin' ) 
LEFT JOIN $wpdb->postmeta AS mt2
ON ( $wpdb->postmeta.post_id = mt2.post_id )
LEFT JOIN $wpdb->postmeta AS mt4
ON ( $wpdb->postmeta.post_id = mt4.post_id AND mt4.meta_key = 'amapress_adhesion_contrat_instance')
LEFT JOIN $wpdb->postmeta AS mt3
ON ( $wpdb->postmeta.post_id = mt3.post_id 
AND mt3.meta_key IN ('amapress_adhesion_adherent','amapress_adhesion_adherent2','amapress_adhesion_adherent3','amapress_adhesion_adherent4') ) 
WHERE 1=1 
AND mt4.meta_value IN ($active_contrat_instances_ids)
AND $wpdb->postmeta.meta_key IN ('amapress_adhesion_adherent','amapress_adhesion_adherent2','amapress_adhesion_adherent3','amapress_adhesion_adherent4')
AND CAST($wpdb->postmeta.meta_value AS SIGNED) IN ($in)  
AND ( mt1.post_id IS NULL 
OR ( mt2.meta_key = 'amapress_adhesion_date_fin'
AND CAST(mt2.meta_value AS UNSIGNED) = 0 ) 
OR ( mt2.meta_key = 'amapress_adhesion_date_fin'
AND CAST(mt2.meta_value AS UNSIGNED) >= $date ) )"
						) );
				} else {
					$rel_key = "{$date}_{$contrat_id}";
					if ( empty( self::$related_user_cache[ $rel_key ] ) ) {
						global $wpdb;
						self::$related_user_cache[ $rel_key ] = array_group_by(
							amapress_get_results_cached(
								"SELECT DISTINCT mt3.meta_value as user_id, $wpdb->postmeta.meta_value
FROM $wpdb->postmeta
LEFT JOIN $wpdb->postmeta AS mt1
ON ($wpdb->postmeta.post_id = mt1.post_id
AND mt1.meta_key = 'amapress_adhesion_date_fin' ) 
LEFT JOIN $wpdb->postmeta AS mt2
ON ( $wpdb->postmeta.post_id = mt2.post_id )
LEFT JOIN $wpdb->postmeta AS mt4
ON ( $wpdb->postmeta.post_id = mt4.post_id AND mt4.meta_key = 'amapress_adhesion_contrat_instance')
LEFT JOIN $wpdb->postmeta AS mt3
ON ( $wpdb->postmeta.post_id = mt3.post_id 
AND mt3.meta_key IN ('amapress_adhesion_adherent','amapress_adhesion_adherent2','amapress_adhesion_adherent3','amapress_adhesion_adherent4') ) 
WHERE 1=1 
AND mt4.meta_value IN ($active_contrat_instances_ids)
AND $wpdb->postmeta.meta_key IN ('amapress_adhesion_adherent', 'amapress_adhesion_adherent2', 'amapress_adhesion_adherent3','amapress_adhesion_adherent4') 
AND ( mt1.post_id IS NULL 
OR ( mt2.meta_key = 'amapress_adhesion_date_fin'
AND CAST(mt2.meta_value AS UNSIGNED) = 0 ) 
OR ( mt2.meta_key = 'amapress_adhesion_date_fin'
AND CAST(mt2.meta_value AS UNSIGNED) >= $date ) )"
							),
							function ( $o ) {
								return intval( $o->meta_value );
							} );
					}

					if ( isset( self::$related_user_cache[ $rel_key ][ $user_id ] ) ) {
						foreach ( self::$related_user_cache[ $rel_key ][ $user_id ] as $o ) {
							if ( ! $o->user_id || in_array( $o->user_id, $res ) ) {
								continue;
							}

							$res[] = $o->user_id;
						}
					}
				}
			}

			$res = array_unique( array_map( 'intval', $res ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

}
