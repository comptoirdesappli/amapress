<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesion_paiement extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_adh_pmt';
	const POST_TYPE = 'adhesion_paiement';
	const PAIEMENT_TAXONOMY = 'amps_paiement_category';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getUser() {
		return $this->getCustomAsEntity( 'amapress_adhesion_paiement_user', 'AmapressUser' );
	}

	public function getUserId() {
		return $this->getCustomAsInt( 'amapress_adhesion_paiement_user' );
	}

	public function getPeriod() {
		return $this->getCustomAsEntity( 'amapress_adhesion_paiement_period', 'AmapressAdhesionPeriod' );
	}

	public function getPeriodId() {
		return $this->getCustomAsInt( 'amapress_adhesion_paiement_period' );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getDate() {
		return $this->getCustom( 'amapress_adhesion_paiement_date' );
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
		return $this->getCustom( 'amapress_adhesion_paiement_status' );
	}

	public function getNumero() {
		return $this->getCustom( 'amapress_adhesion_paiement_numero' );
	}

	public function getAmount( $type = null ) {
		$this->ensure_init();

		if ( $type ) {
			$specific_amount = $this->getCustomAsArray( 'amapress_adhesion_paiement_repartition' );
			if ( ! empty( $specific_amount ) ) {
				$tax_id = Amapress::resolve_tax_id( $type, self::PAIEMENT_TAXONOMY );
				if ( isset( $specific_amount[ $tax_id ] ) ) {
					return $specific_amount[ $tax_id ];
				}
			}

			return 0;
		}

		return $this->getCustomAsFloat( 'amapress_adhesion_paiement_amount' );
	}

	/** @return AmapressAmapien_paiement[] */
	public static function get_next_paiements( $user_id = null, $date = null, $order = 'NONE' ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}
		$adhs_ids = array_map( function ( $a ) {
			return $a->ID;
		}, AmapressContrats::get_user_active_adhesion( $user_id ) );
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
					'value'   => $adhs_ids,
					'compare' => 'IN'
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
			$adhesions = AmapressContrats::get_user_active_adhesion();
			$adh       = array_shift( $adhesions );
			//TODO page link
			$ret[] = new Amapress_EventEntry( array(
				'ev_id'    => "upmt-{$this->ID}",
				'date'     => $date,
				'date_end' => $date,
				'type'     => 'user-paiement',
				'category' => 'Encaissements',
				'label'    => "Encaissement {$price}€",
				'class'    => "agenda-user-paiement",
				'lieu'     => $adh->getLieu(),
				'priority' => 0,
				'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_user_paiement_icon" ) ),
				'alt'      => 'Vous allez être encaissé du chèque numéro ' . $num . ' d\'un montante de ' . $price . '€ à la date du ' . date_i18n( 'd/m/Y', $date ),
				'href'     => '/mes-adhesions'
			) );
		}

		return $ret;
	}

	private static $paiement_cache = null;

	public static function getAllActiveByUserId() {
		if ( ! self::$paiement_cache ) {
//            $adhesions_ids = AmapressContrats::get_active_adhesions_ids();
			$period               = AmapressAdhesionPeriod::getCurrent();
			$period_id            = $period ? $period->ID : 0;
			self::$paiement_cache = array_group_by( array_map(
				function ( $p ) {
					return new AmapressAdhesion_paiement( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_adhesion_paiement_period',
								'value'   => $period_id,
								'compare' => '=',
							),
							array(
								'key'     => 'amapress_adhesion_paiement_period',
								'compare' => 'NOT EXISTS',
							),
						),
					)
				) ),
				function ( $p ) use ( $period ) {
					/** @var AmapressAdhesion_paiement $p */
					if ( $period && ! $p->getPeriodId() ) {
						update_post_meta( $p->ID, 'amapress_adhesion_paiement_period', $period->ID );
					}

					return $p->getUserId();
				}
			);
		}

		return self::$paiement_cache;
	}
}

