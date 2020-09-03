<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressProducteur extends TitanEntity implements iAmapress_Event_Lieu {
	const INTERNAL_POST_TYPE = 'amps_producteur';
	const POST_TYPE = 'producteur';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressProducteur
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressProducteur' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressProducteur( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//	public function getResume() {
//		return wpautop( $this->getCustom( 'amapress_producteur_resume' ) );
//	}

//	public function getPresentation() {
//		return wpautop( $this->getCustom( 'amapress_producteur_presentation' ) );
//	}

//	public function getPresentationRaw() {
//		return $this->getCustom( 'amapress_producteur_presentation' );
//	}

//	public function getHistorique() {
//		return wpautop( $this->getCustom( 'amapress_producteur_historique' ) );
//	}

	/** @return int[] */
	public static function getAllIdsByUser( $user_id ) {
		$cache_key = "amapress_prod_getAllIdsByUser_$user_id";
		$res       = wp_cache_get( $cache_key );
		if ( false == $res ) {
			$res = get_posts( array(
				'fields'         => 'ids',
				'post_type'      => AmapressProducteur::INTERNAL_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'amapress_producteur_user',
						'value'   => $user_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					)
				)
			) );
			wp_cache_set( $cache_key, $res );
		}

		return $res;
	}

	public function getAcces() {
		$this->ensure_init();

		return wpautop( stripslashes( $this->getCustom( 'amapress_producteur_acces' ) ) );
	}

	/** @return int */
	public function getUserId() {
		return $this->getCustomAsInt( 'amapress_producteur_user' );
	}

	/** @return AmapressUser */
	public function getUser() {
		return $this->getCustomAsEntity( 'amapress_producteur_user', 'AmapressUser' );
	}

	/** @return AmapressUser */
	public function getReferent( $lieu_id = null ) {
		return $this->getReferentNum( $lieu_id, 1 );
	}

	/** @return AmapressUser */
	public function getReferent2( $lieu_id = null ) {
		return $this->getReferentNum( $lieu_id, 2 );
	}

	/** @return AmapressUser */
	public function getReferent3( $lieu_id = null ) {
		return $this->getReferentNum( $lieu_id, 3 );
	}

	/** @return int */
	public function getReferentId( $lieu_id = null, $for_lieu_only = false ) {
		return $this->getReferentNumId( $lieu_id, 1, $for_lieu_only );
	}

	/** @return int */
	public function getReferent2Id( $lieu_id = null, $for_lieu_only = false ) {
		return $this->getReferentNumId( $lieu_id, 2, $for_lieu_only );
	}

	/** @return int */
	public function getReferent3Id( $lieu_id = null, $for_lieu_only = false ) {
		return $this->getReferentNumId( $lieu_id, 3, $for_lieu_only );
	}

	/** @return int[] */
	public function getAllReferentsIds() {
		$ret = [];
		foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
			$ret = array_merge( $ret, $this->getReferentsIds( $lieu_id ) );
		}
		$ret = array_merge( $ret, $this->getReferentsIds() );

		return array_unique( $ret );
	}

	/** @return int[] */
	public function getReferentsIds( $lieu_id = null, $for_lieu_only = false ) {
		return array_filter( $for_lieu_only ? [
			$this->getReferentId( $lieu_id, $for_lieu_only ),
			$this->getReferent2Id( $lieu_id, $for_lieu_only ),
			$this->getReferent3Id( $lieu_id, $for_lieu_only )
		] : [
			$this->getReferentId( $lieu_id ),
			$this->getReferent2Id( $lieu_id ),
			$this->getReferent3Id( $lieu_id ),
			$this->getReferentId( null ),
			$this->getReferent2Id( null ),
			$this->getReferent3Id( null )
		], function ( $i ) {
			return ! empty( $i );
		} );
	}

	private $referent_ids = [ 1 => [], 2 => [], 3 => [] ];

	/** @return AmapressUser */
	private function getReferentNum( $lieu_id = null, $num = 1, $for_lieu_only = false ) {
		$id = $this->getReferentNumId( $lieu_id, $num, $for_lieu_only );
		if ( empty( $id ) ) {
			return null;
		}

		return AmapressUser::getBy( $id );
	}

	public function removeReferent( $user_id ) {
		for ( $num = 1; $num <= 3; $num ++ ) {
			foreach ( array_merge( [ null ], Amapress::get_lieu_ids() ) as $lieu_id ) {
				$meta_name = 'amapress_producteur_referent' . ( $num > 1 ? $num : '' ) . ( $lieu_id ? '_' . $lieu_id : '' );
				$v         = $this->getCustom( $meta_name );
				if ( $v == $user_id ) {
					$this->deleteCustom( $meta_name );
				}
			}
		}
	}

	/** @return int */
	private function getReferentNumId( $lieu_id = null, $num = 1, $for_lieu_only = false ) {
		$lieu_name = ( $lieu_id ? $lieu_id : 'defaut' );
		if ( ! $for_lieu_only && ! empty( $this->referent_ids[ $num ][ $lieu_name ] ) ) {
			return $this->referent_ids[ $num ][ $lieu_name ];
		}
		$this->ensure_init();
		$v = $this->getCustom( 'amapress_producteur_referent' . ( $num > 1 ? $num : '' ) . ( $lieu_id ? '_' . $lieu_id : '' ) );
		if ( ! empty( $v ) ) {
			$this->referent_ids[ $num ][ $lieu_name ] = $v;
		} else {
			if ( $for_lieu_only ) {
				return null;
			}

			if ( $lieu_id ) {
				$this->referent_ids[ $num ][ $lieu_name ] = $this->getReferentNumId( null, $num );
			} else {
				$this->referent_ids[ $num ][ $lieu_name ] = null;
			}
		}

		return $this->referent_ids[ $num ][ $lieu_name ];
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

	/** @return string */
	public function getContratsNames() {
		return implode( ', ', array_map( function ( $c ) {
			/** @var AmapressContrat $c */
			return $c->getTitle();
		}, $this->getContrats() ) );
	}


	/** @return AmapressProduit[] */
	public function getProduits() {
		$prods = array_map( function ( $id ) {
			return new AmapressProduit( $id );
		}, $this->getProduitIds() );
		usort( $prods, function ( $a, $b ) {
			/** @var AmapressProduit $a */
			/** @var AmapressProduit $b */
			return strcmp( $a->getTitle(), $b->getTitle() );
		} );

		return $prods;
	}

	private $produit_ids = null;

	/** @return int[] */
	public function getProduitIds() {
		if ( null === $this->produit_ids ) {
			Amapress::setFilterForReferent( false );
			$this->produit_ids = get_posts( array(
					'post_type'      => AmapressProduit::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_produit_producteur',
								'value'   => $this->ID,
								'compare' => '=',
								'type'    => 'NUMERIC',
							),
							amapress_prepare_like_in_array( 'amapress_produit_producteur', $this->ID )
						)
					),
					'orderby'        => 'none'
				)
			);
			Amapress::setFilterForReferent( true );
		}

		return $this->produit_ids;
	}

	public function getNomExploitation() {
		$v = $this->getCustom( 'amapress_producteur_nom_exploitation' );
		if ( empty( $v ) ) {
			$v = $this->getUser() ? $this->getUser()->getDisplayName() : '';
		}

		return $v;
	}

	public function getAdresseExploitation() {
		$v = $this->getCustom( 'amapress_producteur_adresse_exploitation' );
		if ( empty( $v ) ) {
			$v = $this->getUser() ? $this->getUser()->getFormattedAdresse() : '';
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

		return $this->getUser() ? $this->getUser()->isAdresse_localized() : false;
	}

	public function getAdresseExploitationLongitude() {
		if ( $this->hasAdresseExploitation() && $this->isAdresseExploitationLocalized() ) {
			return $this->getCustom( 'amapress_producteur_adresse_exploitation_long' );
		} else {
			return $this->getUser() ? $this->getUser()->getUserLongitude() : 0;
		}
	}

	public function getAdresseExploitationLatitude() {
		if ( $this->hasAdresseExploitation() && $this->isAdresseExploitationLocalized() ) {
			return $this->getCustom( 'amapress_producteur_adresse_exploitation_lat' );
		} else {
			return $this->getUser() ? $this->getUser()->getUserLatitude() : 0;
		}
	}

	public function getFormattedAdresseExploitationHtml() {
		$v = $this->getCustom( 'amapress_producteur_adresse_exploitation' );
		if ( ! empty( $v ) ) {
			return wpautop( $v );
		}

		return $this->getUser() ? $this->getUser()->getFormattedAdresseHtml() : '';
	}

	public function resolveAddress() {
		if ( $this->hasAdresseExploitation() ) {
			return Amapress::updateLocalisation( $this->ID, false,
				'amapress_producteur_adresse_exploitation', $this->getAdresseExploitation() );
		}

		return true;
	}
}

