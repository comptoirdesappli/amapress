<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAmapien_paiement extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_cont_pmt';
	const POST_TYPE = 'contrat_paiement';

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

	public function getBanque() {
		return $this->getCustom( 'amapress_contrat_paiement_banque', '' );
	}


	public function getEmetteur() {
		return $this->getCustom( 'amapress_contrat_paiement_emetteur', '' );
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
					'type'    => 'INT'
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
					'type'    => 'INT'
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
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_user_paiement_icon" ) ),
					'alt'      => 'Vous allez être encaissé du chèque numéro ' . $num . ' d\'un montante de ' . $price . '€ à la date du ' . date_i18n( 'd/m/Y', $date ),
					'href'     => '/mes-adhesions'
				) );
			}
		}

		return $ret;
	}

	private static $paiement_cache = null;

	public static function getAllActiveByAdhesionId() {
		if ( ! self::$paiement_cache ) {
			$adhesions_ids        = AmapressContrats::get_active_adhesions_ids();
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
								'value'   => $adhesions_ids,
								'compare' => 'IN',
							),
						),
					)
				) ),
				function ( $p ) {
					return $p->getAdhesionId();
				}
			);
		}

		return self::$paiement_cache;
	}
}

