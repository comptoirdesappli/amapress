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

//	private static $vp = null;

	public static function init() {
//		if (!self::$vp) self::$vp = new Virtual_Themed_Pages_BC();
//		self::$vp->add('#/distribution#i', array('AmapressDistributions','virtual_distributions'));
//        add_shortcode('distributions',array('AmapressDistributions','distributions_shortcode'));
//        add_shortcode('distribution',array('AmapressDistributions','distribution_shortcode'));
	}
//	static function virtual_distributions($v, $url){
//		if (is_admin()) return;
//		if (!is_user_logged_in())
//		{
//			$v->redirect='/wp-login.php';
//			return;
//		}
//		// extract an id from the URL
//		if (preg_match('#distribution-(\d{2}-\d{2}-\d{4})-a-(.+)#', $url, $m)) {
//			$v->body = do_shortcode('[distribution lieu="'.$m[2].'" date="'.$m[1].'"]');
//			$v->title = 'Distributions du '.$m[1].' à '.Amapress::get_lieu_display($m[2]);
//		} else if (preg_match('#distribution-(\d{2}-\d{2}-\d{4})#', $url, $m)) {
//			$v->body = do_shortcode('[distribution date="'.$m[1].'"]');
//			$v->title = 'Distributions du '.$m[1];
//		} else if (preg_match('#distributions-a-(.+)#', $url, $m)) {
//			$v->body = do_shortcode('[distribution lieu="'.$m[1].'"]');
//			$v->title = 'Prochaines distributions à "'.Amapress::get_lieu_display($m[1]).'"';
//		} else {
//			$v->body = do_shortcode('[distributions]');
//			$v->title = 'Les prochaines distributions';
//		}
//		// could wp_die() if id not extracted successfully...
//
//		$v->template = 'page'; // optional
//		$v->subtemplate = 'page'; // optional
//	}
//	public static function distributions_shortcode($atts) {
//		$atts = shortcode_atts( array(
//			'count' => 10,
//			'lieu' => null,
//		), $atts, 'distributions' );
//
//		if (!is_user_logged_in()) return '';
//
//		$lieu=$atts['lieu'];
//		$lieu_ids = AmapressUsers::get_current_user_lieu_ids();
//		if ($lieu) {
//			$lieu_id = Amapress::get_lieu_id($lieu);
//			if (!in_array($lieu_id,$lieu_ids)) return '';
//			$lieu_ids=array($lieu_id);
//		}
//
//		ob_start();
//		$posts = get_posts(array(
//							'post_type' => 'amps_distribution',
//							'orderby' => 'meta_value_num',
//							'order' => 'ASC',
//							'key' => 'amapress_distribution_date',
//							'posts_per_page' => intval($atts['count']),
//							'meta_query' => array(
//									'relation' => 'AND',
//									array(
//										'key_num' => 'amapress_distribution_date',
//										'value' => Amapress::start_of_day(time()),
//										'compare' => '>='),
//                                        array(
//                                            'relation' => 'OR',
//										    array(
//											    'key' => 'amapress_distribution_lieu',
//											    'value' => $lieu_ids,
//											    'compare' => 'IN',
//											    'type' => 'INT',
//										    ),
//										    array(
//											    'key' => 'amapress_distribution_lieu_substitution',
//											    'value' => $lieu_ids,
//											    'compare' => 'IN',
//											    'type' => 'INT',
//										    ),
//                                        ),
//                            )));
//		foreach ($posts as $post) {
//			AmapressDistributions::echoDistribution($post->ID);
//		}
//
//		$ret = ob_get_contents();
//		ob_end_clean();
//
//		return $ret;
//	}
//	public static function distribution_shortcode($atts) {
//		$atts = shortcode_atts( array(
//			'lieu' => null,
//			'date' => null,
//		), $atts, 'distribution' );
//
//		if (!is_user_logged_in()) return '';
//
//		$lieu=$atts['lieu'];
//		$lieu_ids = AmapressUsers::get_current_user_lieu_ids();
//		if ($lieu) {
//			$lieu_id = Amapress::get_lieu_id($lieu);
//			if (!in_array($lieu_id,$lieu_ids)) return '';
//			$lieu_ids=array($lieu_id);
//		}
//		$date = array(
//					'key' => 'amapress_distribution_date',
//					'value' => array(Amapress::start_of_week(time()),Amapress::end_of_week(time())),
//					'compare' => 'BETWEEN',
//					'type' => 'INT');
//		if ($atts['date']) {
//            $dt=DateTime::createFromFormat('d#m#Y', $atts['date'])->getTimestamp();
//			$date = array(
//						'key' => 'amapress_distribution_date',
//						'value' => array(Amapress::start_of_week($dt),Amapress::end_of_week($dt)),
//						'compare' => 'BETWEEN',
//						'type' => 'INT');
//		}
//
//		$posts = get_posts(array(
//							'post_type' => 'amps_distribution',
//							'orderby' => 'meta_value_num',
//							'order' => 'ASC',
//							'meta_key' => 'amapress_distribution_date',
//							'post_per_page' => intval($atts['count']),
//							'meta_query' => array(
//									'relation' => 'AND',
//									$date,
//                                        array(
//                                            'relation' => 'OR',
//										    array(
//											    'key' => 'amapress_distribution_lieu',
//											    'value' => $lieu_ids,
//											    'compare' => 'IN',
//											    'type' => 'INT',
//										    ),
//										    array(
//											    'key' => 'amapress_distribution_lieu_substitution',
//											    'value' => $lieu_ids,
//											    'compare' => 'IN',
//											    'type' => 'INT',
//										    ),
//                                        ),
//							)));
//		if (count($posts) == 0) {
//			if (!$atts['date'])
//				return 'Pas de distribution cette semaine';
//			else
//				return 'Pas de distribution pour la date demandée : '.$atts['date'];
//		}
//
//		ob_start();
//		foreach ($posts as $post) {
//			AmapressDistributions::echoDistribution($post->ID);
//		}
//		$ret = ob_get_contents();
//		ob_end_clean();
//
//		return $ret;
//	}

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
			$res[ $contrat->ID ] = array( 'missing' => array(), 'associate' => array(), 'unassociate' => array() );
			$liste_dates         = array_unique( $contrat->getListe_dates() );
			if ( empty( $liste_dates ) ) {
				continue;
			}

			$lieux         = $contrat->getLieux();
			$lieux_ids     = $contrat->getLieuxIds();
			$contrat_model = $contrat->getModel();

			$now = Amapress::start_of_day( amapress_time() );
			foreach ( $liste_dates as $date ) {
				if ( $from_now && $date < $now ) {
					continue;
				}
				foreach ( $lieux as $lieu ) {
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
				$dist_contrats = Amapress::get_post_meta_array( $dist->ID, 'amapress_distribution_contrats' );
				$rem           = false;
				$add           = false;
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
				if ( empty( $dist_contrats ) ) {
					$res[ $contrat->ID ]['unassociate'][] = array( 'lieu' => $dist_lieu, 'date' => $dist_date );
					if ( ! $eval ) {
						wp_delete_post( $dist->ID );
					}
				}
				//
				if ( ! $eval && ( $add || $rem ) ) {
					update_post_meta( $dist->ID, 'amapress_distribution_contrats', $dist_contrats );
				}
			}
		}
		return $res;
	}


}
