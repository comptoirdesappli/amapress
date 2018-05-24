<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesionQuantite {
	/** @var  AmapressContrat_quantite */
	private $quantite;
	/** @var  float */
	private $factor;

	/**
	 * AmapressAdhesionQuantite constructor.
	 *
	 * @param AmapressContrat_quantite $quantite
	 * @param float $factor
	 */
	public function __construct( AmapressContrat_quantite $quantite, $factor ) {
		$this->quantite = $quantite;
		$this->factor   = ! empty( $factor ) ? $factor : 1;
	}

	/**
	 * @return AmapressContrat_quantite
	 */
	public function getContratQuantite() {
		return $this->quantite;
	}

	/**
	 * @return float
	 */
	public function getFactor() {
		return $this->factor;
	}

	/**
	 * @return string
	 */
	public function getCode() {
		$quant = $this->getContratQuantite();
		if ( $quant->getContrat_instance() && $quant->getContrat_instance()->isQuantiteVariable() ) {
			if ( $this->getFactor() != 1 ) {
				return $this->getFactor() . ' x ' . $quant->getCode();
			} else {
				return $quant->getCode();
			}
		} else {
			return $quant->getCode();
		}
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		$quant = $this->getContratQuantite();
		if ( $quant->getContrat_instance() && $quant->getContrat_instance()->isQuantiteVariable() ) {
			if ( $this->getFactor() != 1 ) {
				return $this->getFactor() . ' x ' . $quant->getTitle();
			} else {
				return $quant->getTitle();
			}
		} else {
			return $quant->getTitle();
		}
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->getContratQuantite()->ID;
	}

	/**
	 * @return float
	 */
	public function getQuantite() {
		return $this->getFactor() * $this->getContratQuantite()->getQuantite();
	}

	/**
	 * @return float
	 */
	public function getPrice() {
		return $this->getFactor() * $this->getContratQuantite()->getPrix_unitaire();
	}

}

class AmapressAdhesion extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_adhesion';
	const POST_TYPE = 'adhesion';

	private static $entities_cache = array();
	const TO_CONFIRM = 'to_confirm';
	const CONFIRMED = 'confirmed';

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAdhesion
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAdhesion' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressAdhesion( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getProperty( $name ) {
		switch ( $name ) {
			case 'contrat_titre':
				return $this->getContrat_instance()->getTitle();
			case 'contrat_lien':
				return $this->getContrat_instance()->getModel()->getPermalink();
			default:
				return '';
		}
	}

	public function getDate_debut() {
		return $this->getCustomAsDate( 'amapress_adhesion_date_debut' );
	}

	public function hasDate_fin() {
		$fin = $this->getCustom( 'amapress_adhesion_date_fin' );

		return ! empty( $fin );
	}

	public function getDate_fin() {
		$fin = $this->getCustomAsDate( 'amapress_adhesion_date_fin' );
		if ( ! empty( $fin ) ) {
			return $fin;
		}

		if ( $this->getContrat_instance() ) {
			return $this->getContrat_instance()->getDate_fin();
		} else {
			return 0;
		}
	}


	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_adhesion_contrat_instance', 'AmapressContrat_instance' );
	}

	/** @return int */
	public function getContrat_instanceId() {
		return $this->getCustomAsInt( 'amapress_adhesion_contrat_instance' );
	}

	/** @return int */
	public function isNotRenewable() {
		return $this->hasDate_fin();
	}

	public function markNotRenewable() {
		if ( $this->isNotRenewable() ) {
			return;
		}
		$this->setCustom( 'amapress_adhesion_date_fin', Amapress::start_of_day( amapress_time() ) );
		$this->setCustom( 'amapress_adhesion_fin_raison', 'Non renouvellement' );
	}

	/**
	 * @return AmapressAdhesionQuantite[]
	 */
	public function getContrat_quantites( $dist_date ) {
		$factors = $this->getCustomAsFloatArray( 'amapress_adhesion_contrat_quantite_factors' );
		$quants  = $this->getCustomAsEntityArray( 'amapress_adhesion_contrat_quantite', 'AmapressContrat_quantite' );

//		amapress_dump($this->getCustom('amapress_adhesion_contrat_quantite'));
		$date_factor = 1;
		if ( $this->getContrat_instanceId() ) {
			$rattrapage = $this->getContrat_instance()->getRattrapage();
			foreach ( $rattrapage as $r ) {
				if ( Amapress::start_of_day( intval( $r['date'] ) ) == Amapress::start_of_day( $dist_date ) ) {
					$date_factor = floatval( $r['quantite'] );
					break;
				}
			}
		}
		$ret = [];
		foreach ( $quants as $quant ) {
			$factor = 1;
			if ( isset( $factors[ $quant->ID ] ) && $factors[ $quant->ID ] > 0 ) {
				$factor = $factors[ $quant->ID ];
			}
			$ret[ $quant->ID ] = new AmapressAdhesionQuantite( $quant, $factor * $date_factor );
		}

		return $ret;
	}

	/** @return array */
	public function getPaniersVariables() {
		return $this->getCustomAsArray( 'amapress_adhesion_panier_variables' );
	}

	public function getVariables_Contrat_quantites( $date ) {
		if ( ! $this->getContrat_instance()->isPanierVariable() ) {
			return array();
		}

		$quants      = array();
		$quant_by_id = AmapressContrats::get_contrat_quantites( $this->getContrat_instanceId() );
		$quant_by_id = array_combine( array_map( function ( $c ) {
			return $c->ID;
		}, $quant_by_id ), $quant_by_id );
		$paniers     = $this->getPaniersVariables();
		if ( isset( $paniers[ $date ] ) ) {
			$panier = $paniers[ $date ];
			foreach ( $panier as $quant_id => $quant ) {
				if ( $quant > 0 ) {
					/** @var AmapressContrat_quantite $contrat_quant */
					$contrat_quant = $quant_by_id[ $quant_id ];
					$quants[]      = array(
						'quantite'         => $quant,
						'contrat_quantite' => $contrat_quant
					);
				}
			}
		}

		return $quants;
	}

	public function getContrat_quantites_AsString( $date = null ) {
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$quant_labels = array();
			foreach ( $this->getVariables_Contrat_quantites( $date ) as $q ) {
				/** @var AmapressContrat_quantite $contrat_quantite */
				$contrat_quantite = $q['contrat_quantite'];
				$quantite         = $q['quantite'];
				$quant_labels[]   = $contrat_quantite->formatValue( $quantite ) . ' : ' . $contrat_quantite->getTitle();
			}

			return implode( '<br/>', $quant_labels );
		} else {
			$quant_labels = array_map(
				function ( $vv ) {
					/** @var AmapressAdhesionQuantite $vv */
					return $vv->getTitle();
				}
				, $this->getContrat_quantites( $date ) );

			return implode( ', ', $quant_labels );
		}
	}

	public function getContrat_quantites_Codes_AsString( $date = null ) {
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			return 'Var.';
		}

		$codes = array_map( function ( $vv ) {
			/** @var AmapressAdhesionQuantite $vv */
			return $vv->getCode();
		}, $this->getContrat_quantites( $date ) );
//		$quants = array_map( function ( $q ) {
//			/** @var AmapressContrat_quantite $q */
//			return $q->getQuantite();
//		}, $this->getContrat_quantites() );
//		$titles = array_map( function ( $q ) {
//			/** @var AmapressContrat_quantite $q */
//			$c = $q->getCode();
//
//			return ! empty( $c ) ? $c : $q->getTitle();
//		}, $this->getContrat_quantites() );
//		if ( count( array_unique( $quants ) ) == count( $this->getContrat_quantites() ) ) {
//			return implode( ',', $quants );
//		} else if ( count( array_unique( $codes ) ) == count( $this->getContrat_quantites() ) ) {
		return implode( ',', $codes );
//		} else {
//			return implode( ',', $titles );
//		}
	}

	public function getContrat_quantites_IDs() {
		return $this->getCustomAsIntArray( 'amapress_adhesion_contrat_quantite' );
	}

	public function getContrat_quantites_Price( $date = null ) {
		$sum = 0;
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$quant_by_id = AmapressContrats::get_contrat_quantites( $this->getContrat_instanceId() );
			$quant_by_id = array_combine( array_map( function ( $c ) {
				return $c->ID;
			}, $quant_by_id ), $quant_by_id );
			$paniers     = $this->getPaniersVariables();
			if ( isset( $paniers[ $date ] ) ) {
				$panier = $paniers[ $date ];
				foreach ( $panier as $quant_id => $quant ) {
					/** @var AmapressContrat_quantite $contrat_quant */
					$contrat_quant = $quant_by_id[ $quant_id ];
					$sum           += $contrat_quant->getPrix_unitaire() * $quant;
				}
			}
		} else {
			foreach ( $this->getContrat_quantites( $date ) as $c ) {
				$sum += $c->getPrice();
			}
		}

		return $sum;
	}

//    public function getContrat_quantites_Quants($date = null)
//    {
//        $sum = 0;
//        foreach ($this->getContrat_quantites() as $c) {
//            $sum += $c->getQuantite();
//        }
//        return $sum;
//    }

	/** @return int */
	public function getPaiements() {
		return $this->getCustom( 'amapress_adhesion_paiements', 0 );
	}

	/** @return AmapressUser */
	public function getAdherent() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent', 'AmapressUser' );
	}

	public function getAdherentId() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent' );
	}

	public function setAdherent( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent', $value );
	}

	public function getAdherent2() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent2', 'AmapressUser' );
	}

	public function getAdherent2Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent2' );
	}

	public function setAdherent2( $value ) {
		update_post_meta( $this->post->ID, 'amapress_adhesion_adherent2', $value );
	}

	public function getAdherent3() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent3', 'AmapressUser' );
	}

	public function getAdherent3Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent3' );
	}

	public function getAdherent4() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent4', 'AmapressUser' );
	}

	public function getAdherent4Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent4' );
	}

	public function setAdherent3( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent3', $value );
	}

	public function setAdherent4( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent4', $value );
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_adhesion_lieu', 'AmapressLieu_distribution' );
	}

	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_adhesion_lieu' );
	}

	public function getMessage() {
		return $this->getCustom( 'amapress_adhesion_message' );
	}

	public function getStatusDisplay() {
		switch ( $this->getCustom( 'amapress_adhesion_status' ) ) {

			case self::TO_CONFIRM:
				return 'En attente de confirmation';
			case self::CONFIRMED:
				return 'Confirmée';
			default:
				return $this->getCustom( 'amapress_adhesion_status' );
		}
	}

	public function getStatus() {
		return $this->getCustom( 'amapress_adhesion_status' );
	}

	public function setStatus( $status ) {
		$this->setCustom( 'amapress_adhesion_status', $status );
	}

	/** @return float */
	public function getTotalAmount() {
		if ( ! $this->getContrat_instanceId() ) {
			return 0;
		}
		$dates      = $this->getContrat_instance()->getListe_dates();
		$start_date = Amapress::start_of_day( $this->getDate_debut() );
		$dates      = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return Amapress::start_of_day( $d ) >= $start_date;
		} );
//		if ( $this->getContrat_instance()->isPanierVariable() ) {
		$sum = 0;
		foreach ( $dates as $date ) {
			$sum += $this->getContrat_quantites_Price( $date );
		}

		return $sum;
//		} else {
//			return count( $dates ) * $this->getContrat_quantites_Price();
//		}
	}


	private static $paiement_cache = null;

	/** @return  AmapressAdhesion[] */
	public static function getAllActiveByUserId() {
		if ( null === self::$paiement_cache ) {
			global $wpdb;
			$coadhs = array_group_by(
				$wpdb->get_results(
					"SELECT DISTINCT $wpdb->usermeta.meta_value, $wpdb->usermeta.user_id
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3')" ),
				function ( $o ) {
					return intval( $o->user_id );
				} );

			$cache = array();
			foreach ( AmapressContrats::get_active_adhesions() as $adh ) {
				$user_ids = array( $adh->getAdherentId() );
				if ( $adh->getAdherent2Id() ) {
					$user_ids[] = $adh->getAdherent2Id();
				}
				if ( $adh->getAdherent3Id() ) {
					$user_ids[] = $adh->getAdherent3Id();
				}
				if ( $adh->getAdherent4Id() ) {
					$user_ids[] = $adh->getAdherent4Id();
				}
				foreach ( $user_ids as $user_id ) {
					if ( isset( $coadhs[ $user_id ] ) ) {
						foreach ( $coadhs[ $user_id ] as $co ) {
							$user_ids[] = $co->meta_value;
						}
					}
				}
				$user_ids = array_unique( $user_ids );
				foreach ( $user_ids as $user_id ) {
					if ( ! isset( $cache[ $user_id ] ) ) {
						$cache[ $user_id ] = array();
					}
					$cache[ $user_id ][] = $adh;
				}
			}
			self::$paiement_cache = $cache;
		}

		return self::$paiement_cache;
	}

	/**
	 * @return AmapressAdhesion[]
	 */
	public static function getUserActiveAdhesions(
		$user_id = null,
		$contrat_instance_id = null,
		$date = null,
		$ignore_renouv_delta = false
	) {
		return array_map( function ( $p ) {
			return AmapressAdhesion::getBy( $p );
		}, self::getUserActiveAdhesionIds( $user_id, $contrat_instance_id, $date, $ignore_renouv_delta ) );
	}

	/**
	 * @return int[]
	 */
	public static function getUserActiveAdhesionIds(
		$user_id = null,
		$contrat_instance_id = null,
		$date = null,
		$ignore_renouv_delta = false
	) {
		if ( ! amapress_is_user_logged_in() ) {
			return [];
		}

		if ( $user_id == null ) {
			$user_id = amapress_current_user_id();
		}

		$abo_ids = AmapressContrats::get_active_contrat_instances_ids( $contrat_instance_id, $date, $ignore_renouv_delta );
		$abo_key = implode( '-', $abo_ids );

		$key = "AmapressAdhesion::getUserActiveAdhesions_{$user_id}_{$abo_key}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$user_ids = AmapressContrats::get_related_users( $user_id );
			$query    = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_adherent',
						'value'   => $user_ids,
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
					array(
						'key'     => 'amapress_adhesion_contrat_instance',
						'value'   => $abo_ids,
						'compare' => 'IN',
						'type'    => 'NUMERIC'
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
							'value'   => Amapress::end_of_day( amapress_time() ),
							'compare' => '>=',
							'type'    => 'NUMERIC',
						),
					)
				)
			);
			$res      = get_posts( $query );

			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAmapien_paiement[]
	 */
	public function getAllPaiements() {
		$key = "amapress_get_contrat_paiements_{$this->ID}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_paiement_adhesion',
						'value'   => $this->ID,
						'compare' => '=',
						'type'    => 'NUMERIC'
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
	 * @return AmapressAdhesionQuantite[]
	 */
	public static function getQuantitesForUser( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_adhesion_quantites_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			/** @var AmapressAdhesion[] $ret */
			$ret = AmapressAdhesion::getUserActiveAdhesions( $user_id, $contrat_id, $date, $ignore_renouv_delta );

			$res = array();
			foreach ( $ret as $adh ) {
//                $adh = AmapressAdhesion::getBy($v);
				foreach ( $adh->getContrat_quantites( $date ) as $q ) {
					$res[ $q->getId() ] = $q;
				}
			}
//            $res = array_unique($res);
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	public function getNextContratInstanceId() {
		$contrat_instance_id = $this->getContrat_instanceId();
		if ( empty( $contrat_instance_id ) ) {
			return null;
		}
		$contrat_instances_ids = AmapressContrats::get_active_contrat_instances_ids_by_contrat( $this->getContrat_instance()->getModel()->ID,
			null, true );
		$contrat_instances_ids = array_filter(
			$contrat_instances_ids,
			function ( $id ) use ( $contrat_instance_id ) {
				return $id != $contrat_instance_id;
			}
		);
		if ( empty( $contrat_instances_ids ) ) {
			return null;
		}

		return array_shift( $contrat_instances_ids );
	}

	public function canRenew() {
		$new_contrat_id = $this->getNextContratInstanceId();

		return $new_contrat_id
		       && $new_contrat_id != $this->getContrat_instanceId()
		       && ! $this->isNotRenewable();
	}

	public function cloneAdhesion( $as_draft = true ) {
		if ( ! $this->canRenew() ) {
			return null;
		}

		$new_contrat_instance_id = $this->getNextContratInstanceId();

//        $add_weeks = Amapress::datediffInWeeks($this->getContrat_instance()->getDate_debut(), $this->getContrat_instance()->getDate_fin());
		$meta = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}

		$new_contrat = AmapressContrat_instance::getBy( $new_contrat_instance_id );
		$date_debut  = $new_contrat->getDate_debut();

//        $date_debut = Amapress::add_a_week($this->getDate_debut(), $add_weeks);

		$meta['amapress_adhesion_date_debut'] = $date_debut;
		unset( $meta['amapress_adhesion_date_fin'] );
		unset( $meta['amapress_adhesion_fin_raison'] );
		unset( $meta['amapress_adhesion_fin_raison'] );
		unset( $meta['amapress_adhesion_paiements'] );
		unset( $meta['amapress_adhesion_panier_variables'] );
		$meta['amapress_adhesion_contrat_instance'] = $new_contrat_instance_id;

		$quants     = $this->getContrat_quantites( $date_debut );
		$new_quants = AmapressContrats::get_contrat_quantites( $new_contrat_instance_id );

		$new_quants_ids = array();
		foreach ( $quants as $quant ) {
			foreach ( $new_quants as $new_quant ) {
				if ( $new_quant->getCode() == $quant->getContratQuantite()->getCode()
				     || $new_quant->getTitle() == $quant->getContratQuantite()->getTitle()
				) {
					$new_quants_ids[] = $new_quant->ID;
				}
			}
		}
		$meta['amapress_adhesion_contrat_quantite'] = $new_quants_ids;

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressAdhesion::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => $as_draft ? 'draft' : 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		update_post_meta( $this->ID, 'amapress_adhesion_renewed', 1 );

		return AmapressAdhesion::getBy( $new_id );
	}
}