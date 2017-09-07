<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesion extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_adhesion';
	const POST_TYPE = 'adhesion';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getProperty( $name ) {
		switch ( $name ) {
			case 'contrat_titre':
				return $this->getContrat_instance()->getTitle();
			case 'contrat_lien':
				return $this->getContrat_instance()->getModel()->getPermalink();
			default:
				return '';
		}
	}

	public function getDate_debut() {
		return $this->getCustomAsDate( 'amapress_adhesion_date_debut' );
	}

	public function hasDate_fin() {
		$fin = $this->getCustom( 'amapress_adhesion_date_fin' );

		return ! empty( $fin );
	}

	public function getDate_fin() {
		$fin = $this->getCustomAsDate( 'amapress_adhesion_date_fin' );
		if ( ! empty( $fin ) ) {
			return $fin;
		}

		return $this->getContrat_instance()->getDate_fin();
	}


	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_adhesion_contrat_instance', 'AmapressContrat_instance' );
	}

	/** @return int */
	public function getContrat_instanceId() {
		return $this->getCustomAsInt( 'amapress_adhesion_contrat_instance' );
	}


	/**
	 * @return AmapressContrat_quantite[]
	 */
	public function getContrat_quantites() {
		return $this->getCustomAsEntityArray( 'amapress_adhesion_contrat_quantite', 'AmapressContrat_quantite' );
	}

	/** @return array */
	public function getPaniersVariables() {
		return $this->getCustomAsArray( 'amapress_adhesion_panier_variables' );
	}

	public function getVariables_Contrat_quantites( $date ) {
		if ( ! $this->getContrat_instance()->isPanierVariable() ) {
			return array();
		}

		$quants      = array();
		$quant_by_id = AmapressContrats::get_contrat_quantites( $this->getContrat_instance()->ID );
		$quant_by_id = array_combine( array_map( function ( $c ) {
			return $c->ID;
		}, $quant_by_id ), $quant_by_id );
		$paniers     = $this->getPaniersVariables();
		if ( isset( $paniers[ $date ] ) ) {
			$panier = $paniers[ $date ];
			foreach ( $panier as $quant_id => $quant ) {
				if ( $quant > 0 ) {
					/** @var AmapressContrat_quantite $contrat_quant */
					$contrat_quant = $quant_by_id[ $quant_id ];
					$quants[]      = array(
						'quantite'         => $quant,
						'contrat_quantite' => $contrat_quant
					);
				}
			}
		}

		return $quants;
	}

	public function getContrat_quantites_AsString( $date = null ) {
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$quant_labels = array();
			foreach ( $this->getVariables_Contrat_quantites( $date ) as $q ) {
				/** @var AmapressContrat_quantite $contrat_quantite */
				$contrat_quantite = $q['contrat_quantite'];
				$quantite         = $q['quantite'];
				$quant_labels[]   = $contrat_quantite->formatValue( $quantite ) . ' : ' . $contrat_quantite->getTitle();
			}

			return implode( '<br/>', $quant_labels );
		} else {
			$quant_labels = array_map(
				function ( $vv ) {
					/** @var AmapressContrat_quantite $vv */
					return $vv->getTitle();
				}
				, $this->getContrat_quantites() );

			return implode( ', ', $quant_labels );
		}
	}

	public function getContrat_quantites_Codes_AsString( $date = null ) {
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			return 'Var.';
		}

		$codes  = array_map( function ( $q ) {
			/** @var AmapressContrat_quantite $q */
			return $q->getCode();
		}, $this->getContrat_quantites() );
		$quants = array_map( function ( $q ) {
			/** @var AmapressContrat_quantite $q */
			return $q->getQuantite();
		}, $this->getContrat_quantites() );
		$titles = array_map( function ( $q ) {
			/** @var AmapressContrat_quantite $q */
			$c = $q->getCode();

			return ! empty( $c ) ? $c : $q->getTitle();
		}, $this->getContrat_quantites() );
		if ( count( array_unique( $quants ) ) == count( $this->getContrat_quantites() ) ) {
			return implode( ',', $quants );
		} else if ( count( array_unique( $codes ) ) == count( $this->getContrat_quantites() ) ) {
			return implode( ',', $codes );
		} else {
			return implode( ',', $titles );
		}
	}

	public function getContrat_quantites_IDs() {
		return $this->getCustomAsIntArray( 'amapress_adhesion_contrat_quantite' );
	}

	public function getContrat_quantites_Price( $date = null ) {
		$sum = 0;
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$quant_by_id = AmapressContrats::get_contrat_quantites( $this->getContrat_instance()->ID );
			$quant_by_id = array_combine( array_map( function ( $c ) {
				return $c->ID;
			}, $quant_by_id ), $quant_by_id );
			$paniers     = $this->getPaniersVariables();
			if ( isset( $paniers[ $date ] ) ) {
				$panier = $paniers[ $date ];
				foreach ( $panier as $quant_id => $quant ) {
					/** @var AmapressContrat_quantite $contrat_quant */
					$contrat_quant = $quant_by_id[ $quant_id ];
					$sum           += $contrat_quant->getPrix_unitaire() * $quant;
				}
			}
		} else {
			foreach ( $this->getContrat_quantites() as $c ) {
				$sum += $c->getPrix_unitaire();
			}
		}

		return $sum;
	}

//    public function getContrat_quantites_Quants($date = null)
//    {
//        $sum = 0;
//        foreach ($this->getContrat_quantites() as $c) {
//            $sum += $c->getQuantite();
//        }
//        return $sum;
//    }

	/** @return int */
	public function getPaiements() {
		return $this->getCustom( 'amapress_adhesion_paiements', 0 );
	}

	/** @return AmapressUser */
	public function getAdherent() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent', 'AmapressUser' );
	}

	public function getAdherentId() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent' );
	}

	public function setAdherent( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent', $value );
	}

	public function getAdherent2() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent2', 'AmapressUser' );
	}

	public function getAdherent2Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent2' );
	}

	public function setAdherent2( $value ) {
		update_post_meta( $this->post->ID, 'amapress_adhesion_adherent2', $value );
	}

	public function getAdherent3() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent3', 'AmapressUser' );
	}

	public function getAdherent3Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent3' );
	}

	public function setAdherent3( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent3', $value );
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_adhesion_lieu', 'AmapressLieu_distribution' );
	}

	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_adhesion_lieu' );
	}

	public function getMessage() {
		return $this->getCustom( 'amapress_adhesion_message' );
	}

	public function getStatusDisplay() {
		switch ( $this->getCustom( 'amapress_adhesion_status' ) ) {

			case 'to_confirm':
				return 'En attente de confirmation';
			case 'confirmed':
				return 'Confirmée';
			default:
				return $this->getCustom( 'amapress_adhesion_status' );
		}
	}

	public function getStatus() {
		return $this->getCustom( 'amapress_adhesion_status' );
	}

	/** @return float */
	public function getTotalAmount() {
		if ( $this->getContrat_instance() == null ) {
			return 0;
		}
		$dates      = $this->getContrat_instance()->getListe_dates();
		$start_date = Amapress::start_of_day( $this->getDate_debut() );
		$dates      = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return Amapress::start_of_day( $d ) >= $start_date;
		} );
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$sum = 0;
			foreach ( $dates as $date ) {
				$sum += $this->getContrat_quantites_Price( $date );
			}

			return $sum;
		} else {
			return count( $dates ) * $this->getContrat_quantites_Price();
		}
	}


	private static $paiement_cache = null;

	public static function getAllActiveByUserId() {
		if ( ! self::$paiement_cache ) {
			$cache = array();
			foreach ( AmapressContrats::get_active_adhesions() as $adh ) {
				if ( ! isset( $cache[ $adh->getAdherentId() ] ) ) {
					$cache[ $adh->getAdherentId() ] = array();
				}
				$cache[ $adh->getAdherentId() ][] = $adh;
				if ( $adh->getAdherent2Id() > 0 ) {
					if ( ! isset( $cache[ $adh->getAdherent2Id() ] ) ) {
						$cache[ $adh->getAdherent2Id() ] = array();
					}
					$cache[ $adh->getAdherent2Id() ][] = $adh;
				}
				if ( $adh->getAdherent3Id() > 0 ) {
					if ( ! isset( $cache[ $adh->getAdherent3Id() ] ) ) {
						$cache[ $adh->getAdherent3Id() ] = array();
					}
					$cache[ $adh->getAdherent3Id() ][] = $adh;
				}
			}
			self::$paiement_cache = $cache;
		}

		return self::$paiement_cache;
	}

	public function getNextContratInstanceId() {
		$contrat_instance_id   = $this->getContrat_instanceId();
		$contrat_instances_ids = AmapressContrats::get_active_contrat_instances_ids_by_contrat( $this->getContrat_instance()->getModel()->ID,
			null, true );
		$contrat_instances_ids = array_filter(
			$contrat_instances_ids,
			function ( $id ) use ( $contrat_instance_id ) {
				return $id != $contrat_instance_id;
			}
		);
		if ( empty( $contrat_instances_ids ) ) {
			return null;
		}

		return array_shift( $contrat_instances_ids );
	}

	public function cloneAdhesion( $as_draft = true ) {
		$new_contrat_instance_id = $this->getNextContratInstanceId();
		if ( ! $new_contrat_instance_id ) {
			return null;
		}

//        $add_weeks = Amapress::datediffInWeeks($this->getContrat_instance()->getDate_debut(), $this->getContrat_instance()->getDate_fin());
		$meta = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}

		$new_contrat = new AmapressContrat_instance( $new_contrat_instance_id );
		$date_debut  = $new_contrat->getDate_debut();

//        $date_debut = Amapress::add_a_week($this->getDate_debut(), $add_weeks);

		$meta['amapress_adhesion_date_debut'] = $date_debut;
		unset( $meta['amapress_adhesion_date_fin'] );
		unset( $meta['amapress_adhesion_fin_raison'] );
		unset( $meta['amapress_adhesion_fin_raison'] );
		unset( $meta['amapress_adhesion_paiements'] );
		unset( $meta['amapress_adhesion_panier_variables'] );
		$meta['amapress_adhesion_contrat_instance'] = $new_contrat_instance_id;

		$quants     = $this->getContrat_quantites();
		$new_quants = AmapressContrats::get_contrat_quantites( $new_contrat_instance_id );

		$new_quants_ids = array();
		foreach ( $quants as $quant ) {
			foreach ( $new_quants as $new_quant ) {
				if ( $new_quant->getCode() == $quant->getCode()
				     || $new_quant->getTitle() == $quant->getTitle()
				) {
					$new_quants_ids[] = $new_quant->ID;
				}
			}
		}
		$meta['amapress_adhesion_contrat_quantite'] = $new_quants_ids;

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressAdhesion::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => $as_draft ? 'draft' : 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		return new AmapressAdhesion( $new_id );
	}
}