<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressVisite extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_visite';
	const POST_TYPE = 'visite';

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

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_visite_photo');
//    }

	public function getDate() {
		return $this->getCustom( 'amapress_visite_date' );
	}

	public function getHeure_debut() {
		return $this->getCustom( 'amapress_visite_heure_debut' );
	}

	public function getHeure_fin() {
		return $this->getCustom( 'amapress_visite_heure_fin' );
	}

	/** @return AmapressProducteur */
	public function getProducteur() {
		return $this->getCustomAsEntity( 'amapress_visite_producteur', 'AmapressProducteur' );
	}

	public function getAu_programme() {
		return wpautop( stripslashes( $this->custom['amapress_visite_au_programme'] ) );
	}

	/** @return AmapressUser[] */
	public function getParticipants() {
		return $this->getCustomAsEntityArray( 'amapress_visite_participants', 'AmapressUser' );
	}

	/** @return int[] */
	public function getParticipantIds() {
		return $this->getCustomAsIntArray( 'amapress_visite_participants' );
	}

	public function inscrireParticipant( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		$participants = unserialize( get_post_meta( $this->ID, 'amapress_visite_participants', true ) );
		if ( ! $participants ) {
			$participants = array();
		}
		if ( in_array( $user_id, $participants ) ) {
			return 'already_in_list';
		} else {
			$participants[] = $user_id;
			update_post_meta( $this->ID, 'amapress_visite_participants', $participants );

			amapress_mail_current_user_inscr( $this, $user_id, 'visite' );

			return 'ok';
		}
	}

	public function desinscrireParticipant( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		$participants = Amapress::get_post_meta_array( $this->ID, 'amapress_visite_participants' );
		if ( ! $participants ) {
			$participants = array();
		}

		if ( ( $key = array_search( $user_id, $participants ) ) !== false ) {
			unset( $participants[ $key ] );

			update_post_meta( $this->ID, 'amapress_visite_participants', $participants );

			amapress_mail_current_user_desinscr( $this, $user_id, 'visite' );

			return 'ok';
		} else {
			return 'not_inscr';
		}
	}

	/** @return AmapressVisite[] */
	public static function get_next_visites( $date = null, $order = 'ASC' ) {
		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_visite_date',
					'value'   => Amapress::start_of_day( $date ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return AmapressVisite[] */
	public static function get_visites( $start_date = null, $end_date = null, $order = 'NONE' ) {
		if ( ! $start_date ) {
			$start_date = Amapress::start_of_day( amapress_time() );
		}
		if ( ! $end_date ) {
			$end_date = Amapress::end_of_week( amapress_time() );
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_visite_date',
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
			$resps      = $this->getParticipantIds();
			$date       = $this->getStartDateAndHour();
			$date_end   = $this->getEndDateAndHour();
			$producteur = $this->getProducteur();
			if ( in_array( $user_id, $resps ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "visite-{$this->ID}-resp",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-visite",
					'type'     => 'visite',
					'category' => 'Visites',
					'priority' => 90,
					'lieu'     => $producteur,
					'label'    => 'Visite ' . $producteur->getTitle(),
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_visite_icon" ) ),
					'alt'      => 'Vous êtes inscript pour la visite à la ferme du ' . date_i18n( 'd/m/Y', $date ),
					'href'     => $this->getPermalink()
				) );
			} else {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "visite-{$this->ID}",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-inscription-visite",
					'type'     => 'visite',
					'category' => 'Visites',
					'priority' => 95,
					'lieu'     => $producteur,
					'label'    => 'Visite ' . $producteur->getTitle(),
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_visite_inscription_icon" ) ),
					'alt'      => 'Une vsite est prévue à la ferme le ' . date_i18n( 'd/m/Y', $date ),
					'href'     => $this->getPermalink()
				) );
			}
		}

		return $ret;
	}

	public static function getPlaceholdersHelp( $additional_helps = [] ) {
		return Amapress::getPlaceholdersHelpTable( 'visite-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'de la distribution', $additional_helps );
	}
}
