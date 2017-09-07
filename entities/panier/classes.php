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

	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_panier_contrat_instance', 'AmapressContrat_instance' );
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
						'type'    => 'INT'
					),
					array(
						'key'     => 'amapress_panier_date_subst',
						'value'   => array( $start_date, $end_date ),
						'compare' => 'BETWEEN',
						'type'    => 'INT'
					),
				)
			),
			$order );
	}

	/** @return Amapress_EventEntry[] */
	public function get_related_events( $user_id ) {
		return array();
	}

	/** @return AmapressProduit[] */
	public function getSelectedProduits() {
		return $this->getCustomAsEntityArray( 'amapress_panier_produits_selected', 'AmapressProduit' );
	}
}

