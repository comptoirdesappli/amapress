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
		$default              = get_option( 'amapress_nb_responsables' );
		$dist                 = new AmapressDistribution( $dist_id );
		$dist_nb_responsables = $dist->getNb_responsables_Supplementaires();
		$lieu_nb_responsables = $dist->getLieu()->getNb_responsables();
		if ( $lieu_nb_responsables > 0 ) {
			return $lieu_nb_responsables + $dist_nb_responsables;
		} else {
			return $default;
		}
	}

	public static function to_date( $s ) {
		$d = DateTime::createFromFormat( 'd#m#Y', trim( $s ) );

		return Amapress::start_of_day( $d->getTimestamp() );
	}

	public static function isCurrentUserResponsableBetween( $start_date, $end_date, $user_id = null ) {
		if ( ! amapress_is_user_logged_in() ) {
			return false;
		}
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		return count( get_posts( array(
				'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'fields'         => array( 'ID' ),
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_distribution_date',
						'value'   => array( Amapress::start_of_day( $start_date ), Amapress::end_of_day( $end_date ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'amapress_distribution_responsables',
						'value'   => '"' . $user_id . '"',
						'compare' => 'LIKE',
					),
				),
			) ) ) > 0;
	}

	public static function isCurrentUserResponsableThisWeek( $user_id = null, $date = null ) {
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

	public static function isCurrentUserResponsableNextWeek( $user_id = null, $date = null ) {
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

	public static function isCurrentUserResponsable( $dist_id, $user_id = null ) {
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

	public static function generate_distributions( $contrat_id, $from_now = true, $eval = false ) {
		$res      = array();
		$contrats = AmapressContrats::get_active_contrat_instances( $contrat_id );
		foreach ( $contrats as $contrat ) {
			$now             = Amapress::start_of_day( amapress_time() );
			$all_contrat_ids = Amapress::getIDs( AmapressContrats::get_active_contrat_instances( null, Amapress::start_of_day( $from_now ? $now : $contrat->getDate_debut() ) ) );

			$res[ $contrat->ID ] = array( 'missing' => array(), 'associate' => array(), 'unassociate' => array() );
			$liste_dates         = array_unique( $contrat->getListe_dates() );
			if ( empty( $liste_dates ) ) {
				continue;
			}

			$lieux         = $contrat->getLieux();
			$lieux_ids     = $contrat->getLieuxIds();
			$contrat_model = $contrat->getModel();

			foreach ( $liste_dates as $date ) {
				if ( $from_now && $date < $now ) {
					continue;
				}
				foreach ( $lieux as $lieu ) {
					$dists = [];
					if ( ! defined( 'AMAPRESS_TEST' ) ) {
						$dists = get_posts( array(
							'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
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
									'value'   => $lieu->ID,
									'compare' => '=',
								),
							),
						) );
					}
					if ( empty( $dists ) ) {
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

			$distribs = get_posts( array(
					'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => 'amapress_distribution_date',
							'value'   => array(
								Amapress::start_of_day( $from_now ? $now : $contrat->getDate_debut() ),
								Amapress::end_of_day( $contrat->getDate_fin() )
							),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						),
					)
				)
			);
			foreach ( $distribs as $dist ) {
				$dist_date     = Amapress::start_of_day( intval( get_post_meta( $dist->ID, 'amapress_distribution_date', true ) ) );
				$dist_lieu     = intval( get_post_meta( $dist->ID, 'amapress_distribution_lieu', true ) );
				$dist_contrats = array_map( 'intval', Amapress::get_post_meta_array( $dist->ID, 'amapress_distribution_contrats' ) );
				$diff_contrats = array_diff( $dist_contrats, $all_contrat_ids );
				$clean         = false;
				if ( ! empty( $diff_contrats ) ) {
					$clean         = true;
					$dist_contrats = array_diff( $dist_contrats, $diff_contrats );
				}
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
				if ( $rem && in_array( $contrat->ID, $dist_contrats ) ) {
					$dist_contrats                        = array_diff( $dist_contrats, array( $contrat->ID ) );
					$res[ $contrat->ID ]['unassociate'][] = array( 'lieu' => $dist_lieu, 'date' => $dist_date );
				}
				if ( empty( $dist_contrats ) || $clean ) {
					$res[ $contrat->ID ]['unassociate'][] = array( 'lieu' => $dist_lieu, 'date' => $dist_date );
					if ( ! $eval && empty( $dist_contrats ) ) {
						wp_delete_post( $dist->ID );
					}
				}
				//
				if ( ! $eval && ( $add || $rem || $clean ) ) {
					update_post_meta( $dist->ID, 'amapress_distribution_contrats', $dist_contrats );
				}
			}
		}

		return $res;
	}


}
