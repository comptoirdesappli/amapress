<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressPanier extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_panier';
	const POST_TYPE = 'panier';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressPanier
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressPanier' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( $no_cache ) {
			unset( self::$entities_cache[ $post_id ] );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressPanier( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getDate() {
		return $this->getCustomAsDate( 'amapress_panier_date' );
	}

	public function getDateSubst() {
		return $this->getCustomAsDate( 'amapress_panier_date_subst' );
	}

	public function getStatus() {
		return $this->getCustom( 'amapress_panier_status' );
	}

	/**
	 * @param AmapressContrat_quantite $quantite
	 *
	 * @return string
	 */
	public function getContenu( $quantite ) {
		$ret = $this->getCustom( 'amapress_panier_contenu_' . $quantite->ID );
		if ( empty( $ret ) ) {
			$ret = $this->getCustom( 'amapress_panier_contenu' );
		}

		return stripslashes( $ret );
	}

	public function getRealDate() {
		if ( $this->isDelayed() ) {
			return $this->getDateSubst();
		}

		return $this->getDate();
	}

	public function isDelayed() {
		return 'delayed' == $this->getStatus();
	}

	public function isCancelled() {
		return 'cancelled' == $this->getStatus();
	}

//	public function getStatusDescription() {
//		switch ( $this->getStatus() ) {
//			case 'delayed':
//				return 'Reportée au ' . date_i18n( 'd/m/Y', $this->getDateSubst() );
//			case 'cancelled':
//				return 'Annulée';
//			default:
//				return '';
//		}
//	}

	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_panier_contrat_instance', 'AmapressContrat_instance' );
	}

	/** @return int */
	public function getContrat_instanceId() {
		return $this->getCustomAsInt( 'amapress_panier_contrat_instance', 0 );
	}

	/** @return AmapressPanier[] */
	public static function get_delayed_paniers(
		$contrat_instance_id = null,
		$date_delayed = null,
		$date_orig = null,
		$status = [ 'delayed', 'cancelled' ]
	) {
		if ( ! empty( $contrat_instance_id ) && ! is_array( $contrat_instance_id ) ) {
			$contrat_instance_id = array( $contrat_instance_id );
		}

		$key = "amapress_get_delayed_paniers";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$ress = get_option( 'amps_delay_pan' );
			if ( false === $ress ) {
				$post_ids = get_posts( array(
					'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'     => 'amapress_panier_status',
							'value'   => [ 'delayed', 'cancelled' ],
							'compare' => 'IN',
						),
					)
				) );
				$res      = array_map( function ( $p ) {
					return AmapressPanier::getBy( $p );
				}, get_posts(
					array(
						'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'post__in'       => array_unique( $post_ids ),
					)
				) );

				update_option( 'amps_delay_pan', $res );
			} else {
				$res = maybe_unserialize( $ress );
			}
			wp_cache_set( $key, $res );
		}

		update_meta_cache( 'post', array_map( function ( $p ) {
			/** @var AmapressPanier $p */
			return $p->getID();
		}, $res ) );

		$ret = $res;
		$ret = array_filter(
			$ret,
			function ( $p ) use ( $status ) {
				/** @var AmapressPanier $p */
				return in_array( $p->getStatus(), $status );
			}
		);
		if ( ! empty( $contrat_instance_id ) ) {
			$ret = array_filter(
				$ret,
				function ( $p ) use ( $contrat_instance_id ) {
					/** @var AmapressPanier $p */
					return in_array( $p->getContrat_instanceId(), $contrat_instance_id );
				}
			);
		}
		if ( ! empty( $date_delayed ) ) {
			$ret = array_filter(
				$ret,
				function ( $p ) use ( $date_delayed ) {
					/** @var AmapressPanier $p */
					return Amapress::start_of_day( $p->getDateSubst() ) == Amapress::start_of_day( $date_delayed );
				}
			);
		}
		if ( ! empty( $date_orig ) ) {
			$ret = array_filter(
				$ret,
				function ( $p ) use ( $date_orig ) {
					/** @var AmapressPanier $p */
					return Amapress::start_of_day( $p->getDate() ) == Amapress::start_of_day( $date_orig );
				}
			);
		}

		return $ret;
	}

	/** @return AmapressPanier[] */
	public static function get_paniers( $start_date = null, $end_date = null, $order = 'NONE' ) {
		if ( ! $start_date ) {
			$start_date = Amapress::start_of_day( amapress_time() );
		}
		if ( ! $end_date ) {
			$end_date = Amapress::end_of_week( amapress_time() );
		}

		return self::query_events(
			array(
				array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_panier_date',
						'value'   => array( $start_date, $end_date ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC'
					),
					array(
						array(
							'key'     => 'amapress_panier_status',
							'value'   => 'delayed',
							'compare' => '=',
						),
						array(
							'key'     => 'amapress_panier_date_subst',
							'value'   => array( $start_date, $end_date ),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC'
						),
					)
				)
			),
			$order );
	}

	/** @return Amapress_EventEntry[] */
	public function get_related_events( $user_id ) {
		return array();
	}

	/**
	 * @param AmapressContrat_quantite $quantite
	 *
	 * @return AmapressProduit[]
	 */
	public function getSelectedProduits( $quantite = null ) {
		$prods = $this->getCustomAsEntityArray( 'amapress_panier_produits_selected', 'AmapressProduit' );
		if ( $quantite ) {
			$prod_ids    = array_map(
				function ( $p ) {
					return $p->ID;
				}, $prods
			);
			$quant_prods = $this->getCustomAsEntityArray( 'amapress_panier_produits_' . $quantite->ID . '_selected', 'AmapressProduit' );
			foreach ( $quant_prods as $prod ) {
				if ( ! in_array( $prod->ID, $prod_ids ) ) {
					$prods[]    = $prod;
					$prod_ids[] = $prod->ID;
				}
			}
			foreach ( $quantite->getProduits() as $prod ) {
				if ( ! in_array( $prod->ID, $prod_ids ) ) {
					$prods[] = $prod;
				}
			}
		}

		return $prods;
	}
}

