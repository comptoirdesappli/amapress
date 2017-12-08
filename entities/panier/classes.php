<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressPanier extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_panier';
	const POST_TYPE = 'panier';

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

		return $ret;
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

	public static function get_delayed_paniers(
		$contrat_instance_id = null,
		$date_delayed = null,
		$date_orig = null,
		$status = [ 'delayed', 'cancelled' ]
	) {
		return [];

		if ( ! empty( $contrat_instance_id ) && ! is_array( $contrat_instance_id ) ) {
			$contrat_instance_id = array( $contrat_instance_id );
		}

		$key_ids       = is_array( $contrat_instance_id ) ? implode( '-', $contrat_instance_id ) : $contrat_instance_id;
		$status_string = implode( '-', $status );
		$key           = "amapress_get_delayed_paniers_{$key_ids}_{$date_orig}_{$date_delayed}_{$status_string}";
		$res           = wp_cache_get( $key );
		if ( false === $res ) {
			$meta = array(
				array(
					'key'     => 'amapress_panier_status',
					'value'   => $status,
					'compare' => 'IN',
				),
			);
			if ( ! empty( $contrat_instance_id ) ) {
				$meta[] = array(
					'key'     => 'amapress_panier_contrat_instance',
					'value'   => $contrat_instance_id,
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				);
			}
			if ( ! empty( $date_delayed ) ) {
				$meta[] = array(
					'key'     => 'amapress_panier_date_subst',
					'value'   => Amapress::start_of_day( $date_delayed ),
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			}
			if ( ! empty( $date_orig ) ) {
				$meta[] = array(
					'key'     => 'amapress_panier_date',
					'value'   => Amapress::start_of_day( $date_orig ),
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			}
			$query =
				array(
					'post_type'      => 'amps_panier',
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
					'meta_query'     => array(
						$meta,
					)
				);
			$res   = array_map( function ( $p ) {
				return new AmapressPanier( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
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

