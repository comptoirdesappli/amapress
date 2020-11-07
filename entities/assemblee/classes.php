<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAssemblee_generale extends Amapress_EventBase implements iAmapress_Event_Lieu {
	const INTERNAL_POST_TYPE = 'amps_assemblee';
	const POST_TYPE = 'assemblee_generale';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAssemblee_generale
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAssemblee_generale' ) ) {
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
				self::$entities_cache[ $post_id ] = new AmapressAssemblee_generale( $post );
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

	public function getLieu_externe_nom() {
		return $this->getCustom( 'amapress_assemblee_generale_lieu_externe_nom' );
	}

	public function getLieu_externe_adresse() {
		return $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse' );
	}

	public function getLieu_externe_acces() {
		return stripslashes( $this->getCustom( 'amapress_assemblee_generale_lieu_externe_acces' ) );
	}

	public function getLieu_externe_adresse_acces() {
		return stripslashes( $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse_acces' ) );
	}

	public function isLieu_externe_AdresseLocalized() {
		$v = $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse_location_type' );

		return ! empty( $v );
	}

	public function getLieu_externe_AdresseLongitude() {
		return $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse_long' );
	}

	public function getLieu_externe_AdresseLatitude() {
		return $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse_lat' );
	}

	public function getLieu_externe_AdresseAccesLatitude() {
		return $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse_acces_lat' );
	}

	public function getLieu_externe_AdresseAccesLongitude() {
		return $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse_acces_long' );
	}

	public function isLieu_externe_AdresseAccesLocalized() {
		$v = $this->getCustom( 'amapress_assemblee_generale_lieu_externe_adresse_acces_location_type' );

		return ! empty( $v );
	}

	public function getType() {
		return $this->getCustom( 'amapress_assemblee_generale_type', 'lieu' );
	}

	public function getOrdre_du_jour() {
		return wpautop( stripslashes( $this->getCustom( 'amapress_assemblee_generale_ordre_du_jour' ) ) );
	}

	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_assemblee_generale_lieu', 'AmapressLieu_distribution' );
	}

	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_assemblee_generale_lieu' );
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
			if ( in_array( $user_id, $resps ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'       => "asm-{$this->ID}-resp",
					'date'        => $date,
					'date_end'    => $date_end,
					'class'       => "agenda-assemblee agenda-inscrit-assemblee",
					'lieu'        => $this,
					'type'        => 'assemblee_generale',
					'category'    => 'Assemblées générales',
					'priority'    => 70,
					'inscr_types' => [ 'assemblee_generale' ],
					'label'       => 'Assemblée',
					'icon'        => 'fa fa-university',
					'alt'         => sprintf( 'Vous êtes inscript pour l\'assemblée générale du %s', date_i18n( 'd/m/Y', $date ) ),
					'href'        => $this->getPermalink()
				) );
			} else {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "asm-{$this->ID}",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-assemblee agenda-inscription-assemblee",
					'type'     => 'assemblee_generale',
					'category' => 'Assemblées générales',
					'lieu'     => $this,
					'priority' => 70,
					'label'    => 'Assemblée',
					'icon'     => 'fa fa-university',
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

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			wp_die( 'Clos et passé' );
		}

		$participants = $this->getParticipantsIds();
		if ( in_array( $user_id, $participants ) ) {
			return 'already_in_list';
		} else {
			$participants[] = $user_id;
			$this->setCustom( 'amapress_assemblee_generale_participants', $participants );

			amapress_mail_current_user_inscr( $this, $user_id, 'assemblee_generale' );

			return 'ok';
		}
	}

	public function desinscrireParticipant( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			wp_die( 'Clos et passé' );
		}

		$participants = $this->getParticipantsIds();
		if ( ( $key = array_search( $user_id, $participants ) ) !== false ) {
			$events = $this->get_related_events( $user_id );
			unset( $participants[ $key ] );
			$this->setCustom( 'amapress_assemblee_generale_participants', $participants );

			amapress_mail_current_user_desinscr( $this, $user_id, 'assemblee_generale',
				null, null, null, $events );

			return 'ok';
		} else {
			return 'not_inscr';
		}
	}

	public function getLieuPermalink() {
		/** @var AmapressLieu_distribution $lieu */
		$lieu = $this->getCustomAsEntity( 'amapress_assemblee_generale_lieu', 'AmapressLieu_distribution' );
		if ( $lieu ) {
			return $lieu->getPermalink();
		} else {
			return $this->getPermalink();
		}
	}

	public function getLieuTitle() {
		/** @var AmapressLieu_distribution $lieu */
		$lieu = $this->getCustomAsEntity( 'amapress_assemblee_generale_lieu', 'AmapressLieu_distribution' );
		if ( $lieu ) {
			return $lieu->getShortName();
		} else {
			return $this->getLieu_externe_nom();
		}
	}

	public function canSubscribe() {
		return $this->canSubscribeType( 'assemblee' );
	}

	public function canUnsubscribe() {
		return $this->canUnsubscribeType( 'assemblee' );
	}
}

