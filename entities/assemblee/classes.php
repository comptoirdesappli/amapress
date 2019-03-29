<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAssemblee_generale extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_assemblee';
	const POST_TYPE = 'assemblee_generale';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getStartDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getHeure_debut() );
	}

	public function getEndDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getHeure_fin() );
	}

	public function getDate() {
		return $this->getCustom( 'amapress_assemblee_generale_date' );
	}

	public function getHeure_debut() {
		return $this->getCustom( 'amapress_assemblee_generale_heure_debut' );
	}

	public function getHeure_fin() {
		return $this->getCustom( 'amapress_assemblee_generale_heure_fin' );
	}

	public function getOrdre_du_jour() {
		return wpautop( stripslashes( $this->getCustom( 'amapress_assemblee_generale_ordre_du_jour' ) ) );
	}

	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_assemblee_generale_lieu', 'AmapressLieu_distribution' );
	}

	public function getParticipants() {
		return $this->getCustomAsEntityArray( 'amapress_assemblee_generale_participants', 'AmapressUser' );
	}

	public function getParticipantsIds() {
		return $this->getCustomAsIntArray( 'amapress_assemblee_generale_participants' );
	}

	/** @return AmapressAssemblee_generale[] */
	public static function get_next_assemblees( $date = null, $order = 'NONE' ) {
		if ( ! amapress_is_user_logged_in() ) {
			return [];
		}

		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_assemblee_generale_date',
					'value'   => Amapress::start_of_day( $date ),
					'compare' => '>=',
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
			$resps    = $this->getParticipantsIds();
			$date     = $this->getStartDateAndHour();
			$date_end = $this->getEndDateAndHour();
			$asm_lieu = $this->getLieu();
			if ( in_array( $user_id, $resps ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "asm-{$this->ID}-resp",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-assemblee",
					'lieu'     => $asm_lieu,
					'type'     => 'assemblee_generale',
					'category' => 'Assemblées générales',
					'priority' => 70,
					'label'    => 'Assemblée',
					'icon'     => Amapress::get_icon( 'fa fa-university' ),
					'alt'      => 'Vous êtes inscript pour l\'assemblée générale du ' . date_i18n( 'd/m/Y', $date ),
					'href'     => $this->getPermalink()
				) );
			} else {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "asm-{$this->ID}",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-inscription-assemblee",
					'type'     => 'assemblee_generale',
					'category' => 'Assemblées générales',
					'lieu'     => $asm_lieu,
					'priority' => 70,
					'label'    => 'Assemblée',
					'icon'     => Amapress::get_icon( 'fa fa-university' ),
					'content'  => 'Vous êtes inscript pour l\'assemblée générale du ' . date_i18n( 'd/m/Y', $date ),
					'href'     => $this->getPermalink()
				) );
			}
		}

		return $ret;
	}

	public function inscrireParticipant( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		$participants = unserialize( get_post_meta( $this->ID, 'amapress_assemblee_generale_participants', true ) );
		if ( ! $participants ) {
			$participants = array();
		}
		if ( in_array( $user_id, $participants ) ) {
			return 'already_in_list';
		} else {
			$participants[] = $user_id;
			update_post_meta( $this->ID, 'amapress_assemblee_generale_participants', $participants );

			amapress_mail_current_user_inscr( $this, $user_id, 'assemble' );

			return 'ok';
		}
	}
}

