<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAmapien_paiement extends Amapress_EventBase {
	private static $entities_cache = array();
	const INTERNAL_POST_TYPE = 'amps_cont_pmt';
	const POST_TYPE = 'contrat_paiement';

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAmapien_paiement
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAmapien_paiement' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressAmapien_paiement( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getDate() {
		$this->ensure_init();

		return $this->getCustomAsInt( 'amapress_contrat_paiement_date' );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	/** @return AmapressAdhesion */
	public function getAdhesion() {
		return $this->getCustomAsEntity( 'amapress_contrat_paiement_adhesion', 'AmapressAdhesion' );
	}

	/** @return int */
	public function getAdhesionId() {
		return $this->getCustomAsInt( 'amapress_contrat_paiement_adhesion' );
	}


	public function getStatusDisplay() {
		$this->ensure_init();
		switch ( $this->getStatus() ) {

			case 'not_received':
				return 'Non reçu';
			case 'received':
				return 'Reçu';
			case 'bank':
				return 'Encaissé';
			default:
				return $this->getStatus();
		}
	}

	public function getStatus() {
		return $this->getCustom( 'amapress_contrat_paiement_status', '' );
	}

	public function getAmount() {
		return $this->getCustomAsFloat( 'amapress_contrat_paiement_amount', 0 );
	}

	public function getNumero() {
		return $this->getCustom( 'amapress_contrat_paiement_numero', '' );
	}

	public function getType() {
		$ret = $this->getCustom( 'amapress_contrat_paiement_type', '' );
		if ( empty( $ret ) ) {
			$ret = 'chq';
		}

		return $ret;
	}

	public function getTypeFormatted() {
		switch ( $this->getType() ) {
			case 'chq':
				return 'Chèque';
			case 'esp':
				return 'Espèces';
			case 'vir':
				return 'Virement';
			case 'dlv':
				return 'A la livraison';
		}
	}

	public function getBanque() {
		return $this->getCustom( 'amapress_contrat_paiement_banque', '' );
	}


	public function getEmetteur() {
		return $this->getCustom( 'amapress_contrat_paiement_emetteur', '' );
	}

	public static function cleanOrphans() {
		global $wpdb;
		$orphans = $wpdb->get_col( "SELECT $wpdb->posts.ID
FROM $wpdb->posts 
INNER JOIN $wpdb->postmeta
ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
WHERE 1=1 
AND ( ( $wpdb->postmeta.meta_key = 'amapress_contrat_paiement_adhesion'
AND CAST($wpdb->postmeta.meta_value as SIGNED) NOT IN (
SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = '" . AmapressAdhesion::INTERNAL_POST_TYPE . "'
) ) )
AND $wpdb->posts.post_type = '" . AmapressAmapien_paiement::INTERNAL_POST_TYPE . "'
GROUP BY $wpdb->posts.ID" );

		$wpdb->query( 'START TRANSACTION' );
		foreach ( $orphans as $post_id ) {
			wp_delete_post( $post_id );
		}
		$wpdb->query( 'COMMIT' );

		$count = count( $orphans );
		if ( $count ) {
			return "$count règlements orphelins nettoyés";
		} else {
			return "Aucun règlement orphelin";
		}
//		$orphans = get_posts(
//			[
//				'post_type' => self::INTERNAL_POST_TYPE,
//				'posts_per_page' => -1,
//				'fields' => 'ids',
//				'meta_query' => [
//					[
//						'key' => 'amapress_contrat_paiement_adhesion',
//						'value' => [
//							12, 24
//						],
//						'compare' => 'NOT IN'
//					]
//				]
//			]
//		);
	}

	/** @return AmapressAmapien_paiement[] */
	public static function get_next_paiements( $user_id = null, $date = null, $order = 'NONE' ) {
		if ( ! amapress_is_user_logged_in() ) {
			return [];
		}

		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}
		$adhs_ids = AmapressAdhesion::getUserActiveAdhesionIds( $user_id );
		if ( empty( $adhs_ids ) ) {
			return [];
		}

		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_contrat_paiement_date',
					'value'   => Amapress::add_days( $date, - 15 ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				),
				array(
					'key'     => 'amapress_contrat_paiement_adhesion',
					'value'   => amapress_prepare_in( $adhs_ids ),
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return AmapressAmapien_paiement[] */
	public static function get_paiements( $start_date = null, $end_date = null, $order = 'NONE' ) {
		if ( ! $start_date ) {
			$start_date = Amapress::start_of_day( amapress_time() );
		}
		if ( ! $end_date ) {
			$end_date = Amapress::end_of_week( amapress_time() );
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_contrat_paiement_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		$ret = array();
		if ( empty( $user_id ) || $user_id <= 0 ) {

		} else {
			$price     = $this->getAmount();
			$num       = $this->getNumero();
			$date      = $this->getDate();
			$adhesions = AmapressAdhesion::getUserActiveAdhesions();
			if ( $adhesions ) {
				$adh = array_shift( $adhesions );
				//TODO page link
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "upmt-{$this->ID}",
					'date'     => $date,
					'date_end' => $date,
					'type'     => 'user-paiement',
					'category' => 'Encaissements',
					'label'    => "Encaissement {$price}€",
					'class'    => "agenda-user-paiement",
					'priority' => 0,
					'lieu'     => $adh->getLieu(),
					'icon'     => 'flaticon-business',
					'alt'      => 'Vous allez être encaissé ' . ( 'chq' == $this->getType() ? ' du chèque numéro ' . $num : ( 'esp' == $this->getType() ? ' des espèces remises ' : ( 'vir' == $this->getType() ? ' du virement ' : ( 'dlv' == $this->getType() ? ' à la livraison' : '' ) ) ) ) . ' d\'un montante de ' . $price . '€ à la date du ' . date_i18n( 'd/m/Y', $date ),
					'href'     => '/mes-adhesions'
				) );
			}
		}

		return $ret;
	}

	private static $paiement_cache = null;

	public static function getAllActiveByAdhesionId() {
		if ( ! self::$paiement_cache ) {
			$adhesions     = AmapressContrats::get_all_adhesions( AmapressContrats::get_active_contrat_instances_ids() );
			$adhesions_ids = [];
			foreach ( $adhesions as $adhesion ) {
				$adhesions_ids[] = $adhesion->ID;
			}

			do {
				$changed = count( $adhesions_ids );
				foreach (
					get_posts(
						array(
							'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
							'posts_per_page' => - 1,
							'meta_query'     => array(
								array(
									'key'     => 'amapress_adhesion_related',
									'value'   => amapress_prepare_in( $adhesions_ids ),
									'compare' => 'IN',
									'type'    => 'NUMERIC',
								),
							),
						)
					) as $adhesion
				) {
					if ( ! in_array( $adhesion->ID, $adhesions_ids ) ) {
						$adhesions_ids[] = $adhesion->ID;
					}
				}
			} while ( $changed != count( $adhesions_ids ) );
			self::$paiement_cache = array_group_by( array_map(
				function ( $p ) {
					return new AmapressAmapien_paiement( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'key'     => 'amapress_contrat_paiement_adhesion',
								'value'   => amapress_prepare_in( $adhesions_ids ),
								'compare' => 'IN',
								'type'    => 'NUMERIC',
							),
						),
					)
				) ),
				function ( $p ) {
					/** @var AmapressAmapien_paiement $p */
					return $p->getAdhesionId();
				}
			);
		}

		return self::$paiement_cache;
	}
}

