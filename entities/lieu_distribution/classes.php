<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressLieu_distribution extends TitanEntity implements iAmapress_Event_Lieu {
	const INTERNAL_POST_TYPE = 'amps_lieu';
	const POST_TYPE = 'lieu_distribution';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressLieu_distribution
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressLieu_distribution' ) ) {
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
				self::$entities_cache[ $post_id ] = new AmapressLieu_distribution( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_lieu_distribution_photo');
//    }

	public function isPrincipal() {
		return $this->getCustomAsInt( 'amapress_lieu_distribution_principal', 1 );
	}

	public function getContact_externe() {
		$this->ensure_init();

		return wpautop( stripslashes( $this->getCustom( 'amapress_lieu_distribution_contact_externe' ) ) );
	}

	/** @return AmapressUser */
	public function getReferent() {
		return $this->getCustomAsEntity( 'amapress_lieu_distribution_referent', 'AmapressUser' );
	}

	/** @return int */
	public function getReferentId() {
		return $this->getCustomAsInt( 'amapress_lieu_distribution_referent' );
	}

	public function getShortName() {
		$ret = $this->getCustom( 'amapress_lieu_distribution_shortname', null );
		if ( empty( $ret ) ) {
			$ret = $this->getTitle();
		}

		return $ret;
	}

	public function getNb_responsables() {
		return $this->getCustomAsInt( 'amapress_lieu_distribution_nb_responsables', 0 );
	}

	public function getInstructions_privee() {
		return wpautop( stripslashes( $this->getCustom( 'amapress_lieu_distribution_instructions_privee' ) ) );
	}

	public function getHeure_debut() {
		return $this->getCustom( 'amapress_lieu_distribution_heure_debut' );
	}

	public function getHeure_fin() {
		return $this->getCustom( 'amapress_lieu_distribution_heure_fin' );
	}

	public function getAdresse() {
		return $this->getCustom( 'amapress_lieu_distribution_adresse' );
	}

	public function getCode_postal() {
		return $this->getCustom( 'amapress_lieu_distribution_code_postal' );
	}

	public function getVille() {
		return $this->getCustom( 'amapress_lieu_distribution_ville' );
	}

	public function isAdresseLocalized() {
		$v = $this->getCustom( 'amapress_lieu_distribution_location_type' );

		return ! empty( $v );
	}

	public function getAdresseLongitude() {
		return $this->getCustom( 'amapress_lieu_distribution_long' );
	}

	public function getAdresseLatitude() {
		return $this->getCustom( 'amapress_lieu_distribution_lat' );
	}

	public function getAcces() {
		return wpautop( stripslashes( $this->getCustom( 'amapress_lieu_distribution_acces' ) ) );
	}

	public function getAccesRaw() {
		return stripslashes( $this->getCustom( 'amapress_lieu_distribution_acces' ) );
	}

	public function getAdresseAcces() {
		return stripslashes( $this->getCustom( 'amapress_lieu_distribution_adresse_acces' ) );
	}

	public function getAdresseAccesLatitude() {
		return $this->getCustom( 'amapress_lieu_distribution_adresse_acces_lat' );
	}

	public function getAdresseAccesLongitude() {
		return $this->getCustom( 'amapress_lieu_distribution_adresse_acces_long' );
	}

	public function isAdresseAccesLocalized() {
		$v = $this->getCustom( 'amapress_lieu_distribution_adresse_acces_location_type' );

		return ! empty( $v );
	}

	public function getLieuId() {
		return $this->ID;
	}

	public function getLieuPermalink() {
		return $this->getPermalink();
	}

	public function getLieuTitle() {
		return $this->getTitle();
	}

	public function getFormattedAdresse() {
		$cp = $this->getCode_postal();
		$v  = $this->getVille();
		if ( ! empty( $v ) ) {
			return sprintf( '%s, %s %s', $this->getAdresse(), $cp, $v );
		} else {
			return $this->getAdresse();
		}
	}

	public function getFormattedAdresseHtml() {
		$cp = $this->getCode_postal();
		$v  = $this->getVille();
		if ( ! empty( $v ) ) {
			return sprintf( '%s<br/>%s %s', $this->getAdresse(), $cp, $v );
		} else {
			return $this->getAdresse();
		}
	}

	public function getTitle() {
		return parent::getTitle();
	}

	public function resolveAddress() {
		if ( ! empty( $this->getAdresseAcces() ) ) {
			Amapress::updateLocalisation( $this->ID, false,
				'amapress_lieu_distribution_adresse_acces', $this->getAdresseAcces() );
		}
		if ( ! empty( $this->getFormattedAdresse() ) ) {
			Amapress::updateLocalisation( $this->ID, false,
				'amapress_lieu_distribution', $this->getFormattedAdresse() );
		}
	}
}

