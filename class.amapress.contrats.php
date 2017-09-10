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

//    public static function generate_paiements($abo_id, $from_now = true, $eval = false)
//    {
//        $res = array();
//        $abos = AmapressContrats::get_active_adhesions($abo_id);
//        foreach ($abos as $abo) {
//            $res[$abo->ID] = array();
//
//            $date_debut = intval(get_post_meta($abo->ID, 'amapress_adhesion_date_debut', true));
//            $user_id = intval(get_post_meta($abo->ID, 'amapress_adhesion_adherent', true));
//            $contrat_id = intval(get_post_meta($abo->ID, 'amapress_adhesion_contrat_instance', true));
//            $quant_id = intval(get_post_meta($abo->ID, 'amapress_adhesion_contrat_quantite', true));
//            $paiement_id = intval(get_post_meta($abo->ID, 'amapress_adhesion_contrat_paiement', true));
//            $paiements = self::get_paiments($contrat_id, $quant_id, $paiement_id);
//            if (!$paiements)
//                continue;
//
//            $now = Amapress::start_of_day(amapress_time());
//            foreach ($paiements as $p) {
//                $date = $p['date'];
//                if ($from_now && $date < $now) continue;
//                $ps = get_posts(array(
//                    'posts_per_page' => -1,
//                    'post_type' => 'amps_cont_pmt',
//                    'meta_query' => array(
//                        'relation' => 'AND',
//                        array(
//                            'key' => 'amapress_contrat_paiement_date',
//                            'value' => array(
//                                Amapress::start_of_day($date),
//                                Amapress::end_of_day($date)),
//                            'compare' => 'BETWEEN',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_contrat_paiement_user',
//                            'value' => $user_id,
//                            'compare' => '=',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_contrat_paiement_adhesion',
//                            'value' => $abo->ID,
//                            'compare' => '=',
//                            'type' => 'INT'),
//                    )));
//                if (count($ps) == 0) {
//                    // Gather post data.
//                    $my_post = array(
//                        'post_title' => 'Paiement',
//                        'post_type' => 'amps_cont_pmt',
//                        'post_content' => '',
//                        'post_status' => 'publish',
//                        'post_author' => '1',
//                        'meta_input' => array(
//                            'amapress_contrat_paiement_user' => $user_id,
//                            'amapress_contrat_paiement_adhesion' => $abo->ID,
//                            'amapress_contrat_paiement_status' => 'not_received',
//                            'amapress_contrat_paiement_amount' => $p['price'],
//                            'amapress_contrat_paiement_date' => Amapress::start_of_day($date),
//                        ),
//                    );
//
//                    $res[$abo->ID][] = array('price' => $p->price, 'date' => Amapress::start_of_day($date));
//                    // Insert the post into the database.
//                    if (!$eval) {
//                        wp_insert_post($my_post);
//                    }
//                }
//            }
//        }
//        return $res;
//    }
//
	public static function renouvellementDelta( $date ) {
		$renouv_days = Amapress::getOption( 'renouv_days' );
		if ( empty( $renouv_days ) ) {
			$renouv_days = 30;
		}

		return Amapress::add_days( $date, - $renouv_days );
	}

	public static function get_contrat_status( $contrat_id, &$result ) {
		$dists   = AmapressDistributions::generate_distributions( $contrat_id, true, true );
		$paniers = AmapressPaniers::generate_paniers( $contrat_id, true, true );
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
			die( 'Error' );
		}

		/* this area is very simple but being serverside it affords the possibility of retreiving data from the server and passing it back to the javascript function */
		$contrat_id = intval( $_POST['contrat_instance'] );
		AmapressDistributions::generate_distributions( $contrat_id, true, false );
		AmapressPaniers::generate_paniers( $contrat_id, true, false );
		AmapressCommandes::generate_commandes( $contrat_id, true, false );
		echo self::contratStatus( $contrat_id );// this is passed back to the javascript function
		die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
	}

	public static function paiementStatus( $abo_id ) {
//        return '<div class="status"><div class="paiement-status">' . self::get_paiement_status($abo_id) . '</div><p><button class="paiement-status-button" data-paiement="' . $abo_id . '">Générer les paiements</button></p></div>';
		return '';
	}

	public static function get_paiement_status( $abo_id ) {
//        $ps = self::generate_paiements($abo_id, false, true);
//        return sprintf('<p>Paiements : %d manquants</p>',
//            count($ps[$abo_id]));
		return '';
	}

	function update_paiement_status_action() {
		/* this area is very simple but being serverside it affords the possibility of retreiving data from the server and passing it back to the javascript function */
		$paiement_id = intval( $_POST['adhesion'] );
//        self::generate_paiements($paiement_id, false, false);
		echo AmapressContrats::get_paiement_status( $paiement_id );// this is passed back to the javascript function
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
						'type'    => 'INT'
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
				return new AmapressContrat_instance( $p );
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
						'type'    => 'INT'
					),
				);
			}
			$res           = array_map( function ( $p ) {
				return new AmapressContrat( $p );
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
				self::get_subscribable_contrat_instances( null, $date ),
				self::get_active_contrat_instances( null, $date )
			);
			$contrats_ids      = array();
			$contrats          = array();
			foreach ( $contrat_instances as $ci ) {
				$contrat = $ci->getModel();
				if ( in_array( $contrat->ID, $contrats_ids ) ) {
					continue;
				}
				if ( $producteur_id != null && $producteur_id != $contrat->getProducteur()->ID ) {
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

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_active_contrat_instances( $contrat_instance_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key = "amapress_get_active_contrat_instances_{$contrat_instance_id}_{$date}_{$ignore_renouv_delta}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( $date == null ) {
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
//                        'type' => 'INT'),
					array(
						'key'     => 'amapress_contrat_instance_date_fin',
						'value'   => Amapress::end_of_day( $ignore_renouv_delta ? $date : AmapressContrats::renouvellementDelta( $date ) ),
						'compare' => '>=',
						'type'    => 'INT'
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
			if ( $contrat_instance_id ) {
				$query['include'] = array( $contrat_instance_id );
				unset( $query['meta_query'] );
			}
			$res = array_map( function ( $p ) {
				return new AmapressContrat_instance( $p );
			}, get_posts( $query ) );
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
//                        'type' => 'INT'),
					array(
						'key'     => 'amapress_contrat_instance_date_fin',
						'value'   => Amapress::end_of_day( $ignore_renouv_delta ? $date : AmapressContrats::renouvellementDelta( $date ) ),
						'compare' => '>=',
						'type'    => 'INT'
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
						'type'    => 'INT'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return new AmapressContrat_instance( $p );
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
						'type'    => 'INT'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return new AmapressContrat_instance( $p );
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
							'type'    => 'INT'
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
//                            'type' => 'INT'),
//                    ),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_contrat_instance_date_cloture',
							'value'   => Amapress::end_of_day( $date ),
							'compare' => '>=',
							'type'    => 'INT'
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
//                            'type' => 'INT'),
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
				return new AmapressContrat_instance( $p );
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
							'type'    => 'INT'
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
//                            'type' => 'INT'),
//                    ),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_contrat_instance_date_cloture',
							'value'   => Amapress::end_of_day( $date ),
							'compare' => '>=',
							'type'    => 'INT'
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
//                            'type' => 'INT'),
//                    ),
					),
					array(
						'key'     => 'amapress_contrat_instance_model',
						'value'   => $contrat_id,
						'compare' => '=',
						'type'    => 'INT'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return new AmapressContrat_instance( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function getReferentProducteursAndLieux( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$key = "amapress_getReferentProducteursAndLieux_{$user_id}";
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
				$contrat_instance_ids = self::get_active_contrat_instances_ids_by_contrat( $contrat_ids );
				if ( count( $contrat_instance_ids ) == 0 ) {
					$contrat_instance_ids = array( 0 );
				}
				foreach ( $lieu_ids as $lieu_id ) {
					$ref = $prod->getReferent( $lieu_id );
					if ( $ref && $ref->ID == $user_id ) {
//                    if (!$ignore_lieu)
						$res[] = array(
							'lieu'                 => $lieu_id,
							'producteur'           => $prod->ID,
							'contrat_ids'          => $contrat_ids,
							'contrat_instance_ids' => $contrat_instance_ids,
						);
					}
				}
			}
			$amapress_getting_referent_infos = false;
			wp_cache_set( $key, $res );
		}

		return $res;
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
						'key_num' => 'amapress_contrat_quantite_contrat_instance',
						'value'   => $contrat_instance_id,
						'compare' => '=',
						'type'    => 'INT'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return new AmapressContrat_quantite( $p );
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
//                    'type' => 'INT'),
//            )));
//    }

	public static function get_active_contrat_instances_ids( $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		return array_map( array(
			'Amapress',
			'to_id'
		), AmapressContrats::get_active_contrat_instances( $contrat_id, $date, $ignore_renouv_delta ) );
	}

	public static function get_active_contrat_instances_ids_by_contrat( $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		return array_map( array(
			'Amapress',
			'to_id'
		), AmapressContrats::get_active_contrat_instances_by_contrat( $contrat_id, $date, $ignore_renouv_delta ) );
	}

	public static function get_ids( $coll ) {
		return array_map( array( 'Amapress', 'to_id' ), $coll );
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
				array(
					'key'     => 'amapress_adhesion_contrat_instance',
					'value'   => $abo_ids,
					'compare' => 'IN',
					'type'    => 'INT',
				)
			);
			if ( $lieu_id ) {
				$meta_query[] = array(
					'key_num' => 'amapress_adhesion_lieu',
					'value'   => $lieu_id,
					'compare' => '=',
					'type'    => 'INT',
				);
			}
			if ( $contrat_quantite_id ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key_num' => 'amapress_adhesion_contrat_quantite',
						'value'   => $contrat_quantite_id,
						'compare' => '=',
						'type'    => 'INT',
					),
					array(
						'key_num' => 'amapress_adhesion_contrat_quantite',
						'value'   => '"' . $contrat_quantite_id . '"',
						'compare' => 'like',
					)
				);
			}
			$meta_query[] = array(
				array(
					'relation' => 'OR',
					array(
						'key_num' => 'amapress_adhesion_date_fin',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key_num' => 'amapress_adhesion_date_fin',
						'value'   => Amapress::end_of_day( amapress_time() ),
						'compare' => '>=',
						'type'    => 'INT',
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
				$res[ $p->ID ] = new AmapressAdhesion( $p );
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
							'type'    => 'INT',
						)
					);
				} else {
					$meta_query[] = array(
						array(
							'key'     => 'amapress_adhesion_contrat_instance',
							'value'   => $contrat_id,
							'compare' => '=',
							'type'    => 'INT',
						)
					);
				}
			}

			if ( $lieu_id ) {
				$meta_query[] = array(
					'key_num' => 'amapress_adhesion_lieu',
					'value'   => $lieu_id,
					'compare' => '=',
					'type'    => 'INT',
				);
			}
			if ( $contrat_quantite_id ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key_num' => 'amapress_adhesion_contrat_quantite',
						'value'   => $contrat_quantite_id,
						'compare' => '=',
						'type'    => 'INT',
					),
					array(
						'key_num' => 'amapress_adhesion_contrat_quantite',
						'value'   => '"' . $contrat_quantite_id . '"',
						'compare' => 'like',
					)
				);
			}
			$query = array(
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'post_status'    => 'any',
				'meta_query'     => $meta_query
			);

			$res = array_map( function ( $p ) {
				return new AmapressAdhesion( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return int[]
	 */
	public static function get_user_active_adhesion_ids( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_adhesion_ids_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			if ( $user_id == null ) {
				$user_id = amapress_current_user_id();
			}
			$abo_ids  = AmapressContrats::get_active_contrat_instances_ids( $contrat_id, $date, $ignore_renouv_delta );
			$user_ids = self::get_related_users( $user_id, false );
			$query    = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_contrat_instance',
						'value'   => $abo_ids,
						'compare' => 'IN',
						'type'    => 'INT'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_adhesion_adherent',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'INT'
						),
						array(
							'key'     => 'amapress_adhesion_adherent2',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'INT'
						),
						array(
							'key'     => 'amapress_adhesion_adherent3',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'INT'
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key_num' => 'amapress_adhesion_date_fin',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key_num' => 'amapress_adhesion_date_fin',
							'value'   => Amapress::end_of_day( amapress_time() ),
							'compare' => '>=',
							'type'    => 'INT',
						),
					)
				)
			);
			$res      = array_map( function ( $p ) {
				return $p->ID;
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAdhesion[]
	 */
	public static function get_user_active_adhesion( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_adhesion_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$res = array_map( function ( $p ) {
				return new AmapressAdhesion( $p );
			}, self::get_user_active_adhesion_ids( $user_id, $contrat_id, $date, $ignore_renouv_delta ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAmapien_paiement[]
	 */
	public static function get_contrat_paiements( $adhesion_id ) {
		$key = "amapress_get_contrat_paiements_{$adhesion_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_paiement_adhesion',
						'value'   => $adhesion_id,
						'compare' => '=',
						'type'    => 'INT'
					),
				),
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_key'       => 'amapress_contrat_paiement_date'
			);
			$res   = array_map( function ( $p ) {
				return new AmapressAmapien_paiement( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAmapien_paiement[]
	 */
	public static function get_all_paiements( $contrat_instance_id, $contrat_quantite = null ) {
		$key = "amapress_get_all_paiements_{$contrat_instance_id}_{$contrat_quantite}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_contrat_instance',
						'value'   => $contrat_instance_id,
						'compare' => '=',
						'type'    => 'INT'
					),
				)
			);
			if ( ! empty( $contrat_quantite ) ) {
				$query['meta_query'][] = array(
					'key'     => 'amapress_adhesion_contrat_quantite',
					'value'   => $contrat_quantite,
					'compare' => '=',
					'type'    => 'INT'
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
						'value'   => $adhesions_ids,
						'compare' => 'IN',
						'type'    => 'INT'
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



//    /**
//     * @return AmapressAdhesion_intermittence[]
//     */
//    public static function get_user_active_intermittence($user_id = null, $date = null)
//    {
//        $key = "amapress_get_user_active_intermittence_{$user_id}_{$date}";
//        $res = wp_cache_get($key);
//        if (false === $res) {
//            if (!$date) $date = amapress_time();
//            if ($user_id == null) $user_id = amapress_current_user_id();
//            $query = array(
//                'posts_per_page' => -1,
//                'post_type' => AmapressAdhesion_intermittence::INTERNAL_POST_TYPE,
//                'meta_query' => array(
//                    'relation' => 'AND',
//                    array(
//                        'key' => 'amapress_adhesion_intermittence_date_debut',
//                        'value' => Amapress::end_of_day($date),
//                        'compare' => '<=',
//                        'type' => 'INT'
//                    ),
//                    array(
//                        'relation' => 'OR',
//                        array(
//                            'key' => 'amapress_adhesion_intermittence_date_fin',
//                            'value' => Amapress::end_of_day($date),
//                            'compare' => '>',
//                            'type' => 'INT'
//                        ),
//                        array(
//                            'key' => 'amapress_adhesion_intermittence_date_fin',
//                            'compare' => 'NOT EXISTS',
//                        ),
//                        array(
//                            'key' => 'amapress_adhesion_intermittence_date_fin',
//                            'value' => 0,
//                            'compare' => '=',
//                        ),
//                    ),
//                    array(
//                        'key' => 'amapress_adhesion_intermittence_user',
//                        'value' => intval($user_id),
//                        'compare' => '=',
//                        'type' => 'INT'
//                    ),
//                ));
//            $res = array_map(function ($p) {
//                return new AmapressAdhesion_intermittence($p);
//            }, get_posts($query));
//            wp_cache_set($key, $res);
//        }
//        return $res;
//    }

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
							'type'    => 'INT'
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
						'type'    => 'INT'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return new AmapressIntermittence_panier( $p );
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
						'type'    => 'INT'
					),
					array(
						'key'     => 'amapress_intermittence_panier_adherent',
						'value'   => intval( $user_id ),
						'compare' => '=',
						'type'    => 'INT'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return new AmapressIntermittence_panier( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	/**
	 * @return AmapressContrat_quantite[]
	 */
	public static function get_user_active_adhesion_quantites( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_adhesion_quantites_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
//            if (!$user_id) $user_id = amapress_current_user_id();
//            $abo_ids = AmapressContrats::get_active_contrat_instances_ids($contrat_id, $date);
//            $user_ids = self::get_related_users($user_id, false);
//            $ret = get_posts(array(
//                'posts_per_page' => -1,
//                'post_type' => AmapressAdhesion::INTERNAL_POST_TYPE,
//                'meta_query' => array(
//                    'relation' => 'AND',
//                    array(
//                        'key' => 'amapress_adhesion_contrat_instance',
//                        'value' => $abo_ids,
//                        'compare' => 'IN',
//                        'type' => 'INT'),
//                    array('relation' => 'OR',
//                        array(
//                            'key' => 'amapress_adhesion_adherent',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_adhesion_adherent2',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_adhesion_adherent3',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                    ),
//                )));

			/** @var AmapressAdhesion[] $ret */
			$ret = array_map( function ( $p ) {
				return new AmapressAdhesion( $p );
			}, self::get_user_active_adhesion_ids( $user_id, $contrat_id, $date, $ignore_renouv_delta ) );

			$res = array();
			foreach ( $ret as $adh ) {
//                $adh = new AmapressAdhesion($v);
				foreach ( $adh->getContrat_quantites() as $q ) {
					$res[] = $q;
				}
			}
//            $res = array_unique($res);
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function get_related_users( $user_id, $search_in_adhesion = true ) {
		$key = "amapress_get_related_users_{$user_id}_{$search_in_adhesion}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res  = array( $user_id );
			$user = AmapressUser::getBy( $user_id );
			if ( $user ) {
				if ( $user->getCoAdherent1() != null ) {
					$res[] = $user->getCoAdherent1()->ID;
				}
				if ( $user->getCoAdherent2() != null ) {
					$res[] = $user->getCoAdherent2()->ID;
				}
				$res = array_merge( $res, $user->getPrincipalUserIds() );
			}

			if ( $search_in_adhesion ) {
				foreach ( self::get_user_active_adhesion( $user_id ) as $adh ) {
					if ( $adh->getAdherent() != null && ! in_array( $adh->getAdherent()->ID, $res ) ) {
						$res[] = $adh->getAdherent()->ID;
					}
					if ( $adh->getAdherent2() != null && ! in_array( $adh->getAdherent2()->ID, $res ) ) {
						$res[] = $adh->getAdherent2()->ID;
					}
					if ( $adh->getAdherent3() != null && ! in_array( $adh->getAdherent3()->ID, $res ) ) {
						$res[] = $adh->getAdherent3()->ID;
					}
				}
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressContrat_instance[]
	 */
	public static function get_user_active_contrats( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_contrats_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$ads = self::get_user_active_adhesion( $user_id, $contrat_id, $date, $ignore_renouv_delta );
			$res = array();
			foreach ( $ads as $ad ) {
				$res[] = $ad->getContrat_instance()->getModel()->ID;
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return int[]
	 */
	public static function get_user_active_contrat_instances( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_contrat_instances_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$ads = self::get_user_active_adhesion( $user_id, $contrat_id, $date, $ignore_renouv_delta );
			$res = array();
			foreach ( $ads as $ad ) {
				$res[] = $ad->getContrat_instanceId();
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	public static function to_date( $s ) {
		return Amapress::start_of_day( DateTime::createFromFormat( 'd#m#Y', $s )->getTimestamp() );
	}

//    public static function get_adhesion_real_paiments($abo_id)
//    {
//        $user_id = intval(get_post_meta($abo_id, 'amapress_adhesion_user', true));
//        $paiments = get_posts(array(
//            'posts_per_page' => -1,
//            'post_type' => 'amps_cont_pmt',
//            'meta_query' => array(
//                'relation' => 'AND',
//                array(
//                    'key' => 'amapress_contrat_paiement_user',
//                    'value' => $user_id,
//                    'compare' => '=',
//                    'type' => 'INT'),
//                array(
//                    'key' => 'amapress_contrat_paiement_adhesion',
//                    'value' => $abo_id,
//                    'compare' => '=',
//                    'type' => 'INT'),
//            )));
//        $res = array();
//        foreach ($paiments as $p) {
//            $dt = get_post_meta($p->ID, 'amapress_contrat_paiement_date', true);
//            $price = floatval(get_post_meta($p->ID, 'amapress_contrat_paiement_amount', true));
//            $status = get_post_meta($p->ID, 'amapress_contrat_paiement_status', true);
//            $res[] = array('date' => $dt, 'price' => $price, 'status' => $status);
//        }
//        return $res;
//    }
//
//    public static function get_adhesion_theorical_paiments($abo_id)
//    {
//        $date_debut = get_post_meta($abo_id, 'amapress_adhesion_date_debut', true);
//        $contrat_id = get_post_meta($abo_id, 'amapress_adhesion_contrat_instance', true);
//        $contrat_quantite_id = intval(get_post_meta($abo_id, 'amapress_adhesion_contrat_quantite', true));
//        $paiements = intval(get_post_meta($abo_id, 'amapress_adhesion_paiements', true));
//        $dates = Amapress::get_post_meta_array($contrat_id, 'amapress_contrat_instance_liste_dates', true);
//        $dists = 0;
//        foreach ($dates as $dt)
//            if (Amapress::start_of_day(self::to_date($dt)) >= Amapress::start_of_day($date_debut))
//                $dists++;
//        $unit_price = floatval(get_post_meta($contrat_quantite_id, 'amapress_contrat_quantite_prix_unitaire', true));
//        $price = $dists * $unit_price;
//        $res = array();
//        for ($i = 1; $i <= $paiements; $i++) {
//            $res[] = array('date' => self::to_date($e[0]), 'price' => floatval($e[1]) / 100.0 * $price);
//        }
//        return $res;
//    }

}
