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
class AmapressDistributions {
	public static $initiated = false;

	public static function get_responsables( $dist_id ) {
		return Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_responsables' );
	}

	public static function get_visite_participants( $dist_id ) {
		return Amapress::get_post_meta_array( $dist_id, 'amapress_visite_participants' );
	}

	public static function get_required_responsables( $dist_id ) {
		$default                 = get_option( 'amapress_nb_responsables' );
		$dist                    = AmapressDistribution::getBy( $dist_id );
		$dist_nb_responsables    = $dist->getNb_responsables_Supplementaires();
		$lieu_nb_responsables    = $dist->getLieu() ? $dist->getLieu()->getNb_responsables() : 0;
		$contrat_nb_responsables = 0;
		foreach ( $dist->getContrats() as $contrat ) {
			$contrat_nb_responsables += $contrat->getNb_responsables_Supplementaires();
		}
		if ( $lieu_nb_responsables > 0 ) {
			return $lieu_nb_responsables + $dist_nb_responsables + $contrat_nb_responsables;
		} else {
			return $default + $contrat_nb_responsables;
		}
	}

	public static function to_date( $s ) {
		$d = DateTime::createFromFormat( 'd#m#Y', trim( $s ) );

		return Amapress::start_of_day( $d->getTimestamp() );
	}

	/** @return int[] */
	public static function getResponsablesBetween( $start_date, $end_date ) {
		$start_date = Amapress::start_of_day( $start_date );
		$end_date   = Amapress::end_of_day( $end_date );
		$key        = "getResponsablesBetween-$start_date-$end_date";
		$res        = wp_cache_get( $key );
		if ( false === $res ) {
			global $wpdb;
			$query    = $wpdb->prepare(
				"SELECT mt2.meta_value FROM $wpdb->postmeta mt1 
					INNER JOIN $wpdb->postmeta mt2 ON mt1.post_id = mt2.post_id 
					WHERE mt1.meta_key = %s AND mt2.meta_key = %s
					AND CAST(mt1.meta_value as SIGNED) BETWEEN %d AND %d",
				'amapress_distribution_date',
				'amapress_distribution_responsables',
				Amapress::start_of_day( $start_date ),
				Amapress::end_of_day( $end_date )
			);
			$resp_ids = [];
			foreach ( amapress_get_col_cached( $query ) as $responsables ) {
				$v = maybe_unserialize( $responsables );
				if ( is_array( $v ) ) {
					$resp_ids += $v;
				} else {
					$resp_ids[] = $v;
				}
			}
			$resp_ids = array_unique( array_map( 'intval', $resp_ids ) );
			$res      = $resp_ids;
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/** @return int[] */
	public static function getResponsablesLieuBetween( $start_date, $end_date, $lieu_ids ) {
		if ( empty( $lieu_ids ) ) {
			return self::getResponsablesBetween( $start_date, $end_date );
		}

		$start_date = Amapress::start_of_day( $start_date );
		$end_date   = Amapress::end_of_day( $end_date );
		$key        = "getResponsablesBetween-$start_date-$end_date-" . implode( '-', $lieu_ids );
		$res        = wp_cache_get( $key );
		if ( false === $res ) {
			global $wpdb;
			$query    = $wpdb->prepare(
				"SELECT mt2.meta_value FROM $wpdb->postmeta mt1 
					INNER JOIN $wpdb->postmeta mt2 ON mt1.post_id = mt2.post_id 
					INNER JOIN $wpdb->postmeta mt3 ON mt1.post_id = mt3.post_id 
					WHERE mt1.meta_key = %s AND mt2.meta_key = %s AND mt3.meta_key = %s
					AND CAST(mt1.meta_value as SIGNED) BETWEEN %d AND %d
					AND CAST(mt3.meta_value as SIGNED) IN (" . amapress_prepare_in_sql( $lieu_ids ) . ")",
				'amapress_distribution_date',
				'amapress_distribution_responsables',
				'amapress_distribution_lieu',
				Amapress::start_of_day( $start_date ),
				Amapress::end_of_day( $end_date )
			);
			$resp_ids = [];
			foreach ( amapress_get_col_cached( $query ) as $responsables ) {
				$v = maybe_unserialize( $responsables );
				if ( is_array( $v ) ) {
					$resp_ids += $v;
				} else {
					$resp_ids[] = $v;
				}
			}
			$resp_ids = array_unique( array_map( 'intval', $resp_ids ) );
			$res      = $resp_ids;
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function isCurrentUserResponsableBetween( $start_date, $end_date, $user_id = null ) {
		if ( ! amapress_is_user_logged_in() ) {
			return false;
		}
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$key        = "isCurrentUserResponsableBetween-$start_date-$end_date-$user_id";
		$post_count = wp_cache_get( $key );
		if ( false === $post_count ) {
			$post_count = get_posts_count( array(
				'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'orderby'        => 'none',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_distribution_date',
						'value'   => array( Amapress::start_of_day( $start_date ), Amapress::end_of_day( $end_date ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
					amapress_prepare_like_in_array( 'amapress_distribution_responsables', $user_id ),
				),
			) );
			wp_cache_set( $key, $post_count );
		}

		return $post_count > 0;
	}

	public static function isCurrentUserResponsableThisWeek(
		$user_id = null, $date = null
	) {
		if ( ! amapress_is_user_logged_in() ) {
			return false;
		}
		if ( ! $date ) {
			$date = amapress_time();
		}
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		return self::isCurrentUserResponsableBetween( Amapress::start_of_week( $date ), Amapress::end_of_week( $date ), $user_id );
	}

	public static function isCurrentUserResponsableNextWeek(
		$user_id = null, $date = null
	) {
		if ( ! amapress_is_user_logged_in() ) {
			return false;
		}
		if ( ! $date ) {
			$date = amapress_time();
		}
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		return self::isCurrentUserResponsableBetween( Amapress::start_of_week( Amapress::add_a_week( $date ) ), Amapress::end_of_week( Amapress::add_a_week( $date ) ), $user_id );
	}

	public static function isCurrentUserResponsable(
		$dist_id, $user_id = null
	) {
		if ( ! amapress_is_user_logged_in() ) {
			return false;
		}
		//if (current_user_can('responsable')) return true;
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}
		$resp_ids = Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_responsables' );

		return in_array( $user_id, $resp_ids );
	}

	public static function generate_distributions(
		$contrat_id, $from_now = true, $eval = false
	) {
		$key = 'amps_gen_dist_' . $contrat_id;
		$res = ! $eval ? [] : maybe_unserialize( get_option( $key ) );
		if ( ! empty( $res ) ) {
//foreach ($res[$contrat_id]['unassociate'] as $a) {
//	amapress_dump(date_i18n('d/m/Y', $a['date']));
//}
			return $res;
		}

//		$is_ref   = count( AmapressContrats::getReferentProducteursAndLieux() ) > 0;
		$res      = array();
		$contrats = [ AmapressContrat_instance::getBy( $contrat_id ) ];
		/** @var AmapressContrat_instance $contrat */
		foreach ( $contrats as $contrat ) {
			if ( empty( $contrat ) ) {
				continue;
			}

			$now = Amapress::start_of_day( $contrat->getDate_debut() );
			Amapress::setFilterForReferent( false );
			$all_contrat_ids = AmapressContrats::get_active_contrat_instances_ids( null, Amapress::start_of_day( $from_now ? $now : $contrat->getDate_debut() ) );
			Amapress::setFilterForReferent( true );

			$res[ $contrat->ID ] = array( 'missing' => array(), 'associate' => array(), 'unassociate' => array() );
			$liste_dates         = array_unique( $contrat->getListe_dates() );
			if ( empty( $liste_dates ) ) {
				continue;
			}

			$lieux         = $contrat->getLieux();
			$lieux_ids     = $contrat->getLieuxIds();
			$contrat_model = $contrat->getModel();
			if ( empty( $lieux ) || empty( $contrat_model ) ) {
				continue;
			}

			foreach ( $liste_dates as $date ) {
				if ( $from_now && $date < $now ) {
					continue;
				}
				foreach ( $lieux as $lieu ) {
					$start     = Amapress::start_of_day( $date );
					$stop      = Amapress::end_of_day( $date );
					$cache_key = "amapress_generate_distribs1_$start-$stop-{$lieu->ID}";
					$cnt       = wp_cache_get( $cache_key );
					if ( false == $cnt ) {
						$cnt = get_posts_count( array(
							'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
							'posts_per_page' => - 1,
							'meta_query'     => array(
								'relation' => 'AND',
								array(
									'key'     => 'amapress_distribution_date',
									'value'   => array(
										$start,
										$stop
									),
									'compare' => 'BETWEEN',
									'type'    => 'NUMERIC'
								),
								array(
									'key'     => 'amapress_distribution_lieu',
									'value'   => $lieu->ID,
									'compare' => '=',
								),
							),
						) );
						if ( $cnt > 0 ) {
							wp_cache_set( $cache_key, $cnt );
						}
					}
					if ( $cnt == 0 ) {
						$my_post = array(
							'post_title'   => sprintf( 'Distribution de %s du %02d-%02d-%04d à %s',
								$contrat_model->getTitle(),
								date( 'd', $date ), date( 'm', $date ), date( 'Y', $date ),
								$lieu->getTitle() ),
							'post_type'    => AmapressDistribution::INTERNAL_POST_TYPE,
							'post_content' => '',
							'post_status'  => 'publish',
							'meta_input'   => array(
								'amapress_distribution_lieu'     => $lieu->ID,
								'amapress_distribution_date'     => Amapress::start_of_day( $date ),
								'amapress_distribution_contrats' => array( $contrat->ID ),
							),
						);

						$res[ $contrat->ID ]['missing'][] = array(
							'lieu' => $lieu->ID,
							'date' => Amapress::start_of_day( $date )
						);

						if ( ! $eval ) {
							wp_insert_post( $my_post );
						}
					}
				}
			}

			$start = Amapress::start_of_day( $from_now ? $now : $contrat->getDate_debut() );
			//$stop      = Amapress::end_of_day( $contrat->getDate_fin() );
			$cache_key = "amapress_generate_distribs2_$start";
			$distribs  = wp_cache_get( $cache_key );
			if ( false == $distribs ) {
				$distribs = get_posts( array(
						'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'fields'         => 'ids',
						'meta_query'     => array(
							'relation' => 'AND',
							array(
								'key'     => 'amapress_distribution_date',
								'value'   => $start,
								'compare' => '>=',
								'type'    => 'NUMERIC',
							),
						)
					)
				);
				update_meta_cache( 'post', $distribs );
				wp_cache_set( $cache_key, $distribs );
			}
			$keys = [];
			foreach ( $distribs as $dist_id ) {
				$dist_date      = Amapress::start_of_day( intval( get_post_meta( $dist_id, 'amapress_distribution_date', true ) ) );
				$dist_lieu      = intval( get_post_meta( $dist_id, 'amapress_distribution_lieu', true ) );
				$k              = "$dist_date|$dist_lieu";
				$already_exists = isset( $keys[ $k ] );
				$clean          = $already_exists;
				$keys[ $k ]     = true;

				$dist_contrats = array_map( 'intval', Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_contrats' ) );
//				if ( ! $is_ref ) {
				$diff_contrats = array_diff( $dist_contrats, $all_contrat_ids );
				if ( ! empty( $diff_contrats ) ) {
					$clean         = true;
					$dist_contrats = array_diff( $dist_contrats, $diff_contrats );
				}
//				}
				$rem = false;
				$add = false;
				if ( in_array( $dist_date, $liste_dates ) ) {
					if ( in_array( $dist_lieu, $lieux_ids ) ) {
						$add = true;
					} else {
						$rem = true;
					}
				} else {
					$rem = true;
				}
				if ( $add && ! in_array( $contrat->ID, $dist_contrats ) ) {
					$dist_contrats[]                    = $contrat->ID;
					$res[ $contrat->ID ]['associate'][] = array( 'lieu' => $dist_lieu, 'date' => $dist_date );
				}
				$add_to_unassociate = false;
				if ( $rem && in_array( $contrat->ID, $dist_contrats ) ) {
					$add_to_unassociate = true;
					$dist_contrats      = array_diff( $dist_contrats, array( $contrat->ID ) );
				}
				if ( empty( $dist_contrats ) || $clean ) {
					$add_to_unassociate = true;
					if ( empty( $dist_contrats ) ) {
						$reports = AmapressPaniers::getPaniersForDist( $dist_date );
						if ( ! empty( $reports ) ) {
							$add_to_unassociate = false;
						} else if ( ! $eval ) {
							wp_delete_post( $dist_id );
						}
					}
					if ( ! $eval && $already_exists ) {
						wp_delete_post( $dist_id );
					}
				}
				if ( $add_to_unassociate ) {
					$res[ $contrat->ID ]['unassociate'][] = array( 'lieu' => $dist_lieu, 'date' => $dist_date );
				}
				//
				if ( ! $eval && ( $add || $rem || $clean ) ) {
					update_post_meta( $dist_id, 'amapress_distribution_contrats', $dist_contrats );
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
}
