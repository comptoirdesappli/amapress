<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressProducteur extends TitanEntity implements iAmapress_Event_Lieu {
	const INTERNAL_POST_TYPE = 'amps_producteur';
	const POST_TYPE = 'producteur';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getResume() {
		return wpautop( $this->getCustom( 'amapress_producteur_resume' ) );
	}

	public function getPresentation() {
		return wpautop( $this->getCustom( 'amapress_producteur_presentation' ) );
	}

	public function getPresentationRaw() {
		return $this->getCustom( 'amapress_producteur_presentation' );
	}

	public function getHistorique() {
		return wpautop( $this->getCustom( 'amapress_producteur_historique' ) );
	}

	public function getAcces() {
		$this->ensure_init();

		return wpautop( $this->getCustom( 'amapress_producteur_acces' ) );
	}

	/** @return AmapressUser */
	public function getUser() {
		return $this->getCustomAsEntity( 'amapress_producteur_user', 'AmapressUser' );
	}

	private $referents = array();

	/** @return AmapressUser */
	public function getReferent( $lieu_id = null ) {
		$this->ensure_init();
		$v         = $this->getCustom( 'amapress_producteur_referent' . ( $lieu_id ? '_' . $lieu_id : '' ) );
		$lieu_name = ( $lieu_id ? $lieu_id : 'defaut' );
		if ( ! empty( $v ) ) {
			$this->referents[ $lieu_name ] = AmapressUser::getBy( $v );
		} else {
			if ( $lieu_id ) {
				return $this->getReferent( null );
			} else {
				$this->referents[ $lieu_name ] = null;
			}
		}

		return $this->referents[ $lieu_name ];
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

	/** @return AmapressContrat[] */
	public function getContrats() {
		return AmapressContrats::get_contrats( $this->ID );
	}


	/** @return AmapressProduit[] */
	public function getProduits() {
		return array_map( function ( $id ) {
			return new AmapressProduit( $id );
		}, get_posts( array(
				'post_type'      => AmapressProduit::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'amapress_produit_producteur',
						'value'   => $this->ID,
						'compare' => '=',
						'type'    => 'INT',
					),
				),
				'order'          => 'ASC',
				'orderby'        => 'title'
			)
		) );
	}

	public function getNomExploitation() {
		$v = $this->getCustom( 'amapress_producteur_nom_exploitation' );
		if ( empty( $v ) ) {
			$v = $this->getUser()->getDisplayName();
		}

		return $v;
	}

	public function getAdresseExploitation() {
		$v = $this->getCustom( 'amapress_producteur_adresse_exploitation' );
		if ( empty( $v ) ) {
			$v = $this->getUser()->getFormattedAdresse();
		}

		return $v;
	}

	public function hasAdresseExploitation() {
		$v = $this->getCustom( 'amapress_producteur_adresse_exploitation_location_type' );

		return ! empty( $v );
	}

	public function isAdresseExploitationLocalized() {
		if ( $this->hasAdresseExploitation() ) {
			return true;
		}

		return $this->getUser()->isAdresse_localized();
	}

	public function getAdresseExploitationLongitude() {
		if ( $this->hasAdresseExploitation() && $this->isAdresseExploitationLocalized() ) {
			return $this->getCustom( 'amapress_producteur_adresse_exploitation_long' );
		} else {
			return $this->getUser()->getUserLongitude();
		}
	}

	public function getAdresseExploitationLatitude() {
		if ( $this->hasAdresseExploitation() && $this->isAdresseExploitationLocalized() ) {
			return $this->getCustom( 'amapress_producteur_adresse_exploitation_lat' );
		} else {
			return $this->getUser()->getUserLatitude();
		}
	}

	public function getFormattedAdresseExploitationHtml() {
		$v = $this->getCustom( 'amapress_producteur_adresse_exploitation' );
		if ( ! empty( $v ) ) {
			return wpautop( $v );
		}

		return $this->getUser()->getFormattedAdresseHtml();
	}
}

