<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressContrat extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat';
	const POST_TYPE = 'contrat';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_contrat_photo');
//    }

	public function getPresentation() {
		return wpautop( $this->getCustom( 'amapress_contrat_presentation' ) );
	}

	public function getPresentationRaw() {
		return $this->getCustom( 'amapress_contrat_presentation' );
	}

	public function getNb_visites() {
		return floatval( $this->getCustom( 'amapress_contrat_nb_visites', 0 ) );
	}

	public function getMax_adherents() {
		return floatval( $this->getCustom( 'amapress_contrat_max_adherents', 0 ) );
	}

	/** @return AmapressProducteur */
	public function getProducteur() {
		return $this->getCustomAsEntity( 'amapress_contrat_producteur', 'AmapressProducteur' );
	}

	/** @return int */
	public function getProducteurId() {
		return $this->getCustomAsInt( 'amapress_contrat_producteur' );
	}
}

class AmapressContrat_instance extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat_inst';
	const POST_TYPE = 'contrat_instance';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	/** @return AmapressContrat */
	public function getModel() {
		return $this->getCustomAsEntity( 'amapress_contrat_instance_model', 'AmapressContrat' );
	}

	public function getMax_adherents() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_max_adherents', 0 );
	}

	public function getDate_ouverture() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_ouverture' );
	}

	public function getDate_cloture() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_cloture' );
	}

	public function getDate_debut() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_debut' );
	}


	public function isPanierVariable() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_panier_variable' );
	}

	public function getDate_fin() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_fin' );
	}

	/** @return AmapressLieu_distribution[] */
	public function getLieux() {
		return $this->getCustomAsEntityArray( 'amapress_contrat_instance_lieux', 'AmapressLieu_distribution' );
	}

	/** @return int[] */
	public function getLieuxIds() {
		return $this->getCustomAsIntArray( 'amapress_contrat_instance_lieux' );
	}

	public function getType() {
		return $this->getCustom( 'amapress_contrat_instance_type' );
	}

	public function getNb_visites() {
		return floatval( $this->getCustom( 'amapress_contrat_instance_nb_visites', 0 ) );
	}

	/** @return string */
	public function getContrat() {
		return wpautop( $this->getCustom( 'amapress_contrat_instance_contrat' ) );
	}

	/** @return string */
	public function getContratRaw() {
		return $this->getCustom( 'amapress_contrat_instance_contrat' );
	}

	public function isPrincipal() {
		return isset( $this->custom['amapress_contrat_instance_is_principal'] ) ? intval( $this->custom['amapress_contrat_instance_is_principal'] ) : 0;
	}

	public function getListe_dates() {
		$liste_dates = $this->getCustomAsDateArray( 'amapress_contrat_instance_liste_dates' );
		if ( empty( $liste_dates ) || count( $liste_dates ) == 0 ) {
			$liste_dates = $this->getCustomAsDateArray( 'amapress_contrat_instance_commande_liste_dates' );
		}

		return $liste_dates;
	}

	public function getPaiements_Liste_dates() {
		return $this->getCustomAsDateArray( 'amapress_contrat_instance_liste_dates_paiements' );
	}

	public function getPossiblePaiements() {
		return $this->getCustomAsIntArray( 'amapress_contrat_instance_paiements' );
	}

	public function cloneContrat( $as_draft = true ) {
		$add_weeks = Amapress::datediffInWeeks( $this->getDate_debut(), $this->getDate_fin() );
		$meta      = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}

		$date_debut = Amapress::add_a_week( $this->getDate_debut(), $add_weeks );
		$date_fin   = Amapress::add_a_week( $this->getDate_fin(), $add_weeks );

		$new_liste_dates                               = array_map(
			function ( $date ) use ( $add_weeks ) {
				return Amapress::add_a_week( $date, $add_weeks );
			}, $this->getListe_dates() );
		$new_liste_dates                               = array_filter(
			$new_liste_dates,
			function ( $date ) use ( $date_debut, $date_fin ) {
				return $date_debut <= $date && $date <= $date_fin;
			} );
		$meta['amapress_contrat_instance_liste_dates'] = implode( ',',
			array_map(
				function ( $date ) {
					return date( 'd/m/Y', $date );
				}, $new_liste_dates )
		);
		unset( $meta['amapress_contrat_instance_liste_dates_paiements'] );
		unset( $meta['amapress_contrat_instance_commande_liste_dates'] );
		$meta['amapress_contrat_instance_date_debut'] = $date_debut;
		$meta['amapress_contrat_instance_date_fin']   = $date_fin;
		unset( $meta['amapress_contrat_instance_ended'] );
		$meta['amapress_contrat_instance_date_ouverture'] = Amapress::add_a_week( $this->getDate_ouverture(), $add_weeks );
		$meta['amapress_contrat_instance_date_cloture']   = Amapress::add_a_week( $this->getDate_cloture(), $add_weeks );

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressContrat_instance::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => $as_draft ? 'draft' : 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		foreach ( AmapressContrats::get_contrat_quantites( $this->ID ) as $quantite ) {
			$quantite->cloneForContrat( $new_id );
		}

		return new AmapressContrat_instance( $new_id );
	}
}

class AmapressContrat_quantite extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat_quant';
	const POST_TYPE = 'contrat_quantite';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_contrat_quantite_photo', '');
//    }

	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_contrat_quantite_contrat_instance', 'AmapressContrat_instance' );
	}

//    public function getMax_Commandable()
//    {
//        return $this->getCustomAsFloat('amapress_contrat_quantite_max_quantite');
//    }

	public function getPrix_unitaire() {
		return $this->getCustomAsFloat( 'amapress_contrat_quantite_prix_unitaire' );
	}

	public function getQuantite() {
		return $this->getCustomAsFloat( 'amapress_contrat_quantite_quantite' );
	}

	public function getCode() {
		return $this->getCustom( 'amapress_contrat_quantite_code' );
	}

	public function getDescription() {
		return $this->getCustom( 'amapress_contrat_quantite_description' );
	}

	/** @return AmapressProduit[] */
	public function getProduits() {
		return $this->getCustomAsEntityArray( 'amapress_contrat_quantite_produits', 'AmapressProduit' );
	}

	/** @return int[] */
	public function getProduitsIds() {
		return $this->getCustomAsIntArray( 'amapress_contrat_quantite_produits' );
	}

	public function getPriceUnit() {
		return $this->getCustom( 'amapress_contrat_quantite_unit' );
	}

	public function getPriceUnitDisplay() {
		switch ( $this->getPriceUnit() ) {
			case 'kg':
				return 'le kg';
			case 'l':
				return 'le litre';
			case 'unit':
				return 'l\'unité';
			default:
				return $this->getPriceUnit();
		}
	}

	public function getQuantiteConfig() {
		return $this->getCustom( 'amapress_contrat_quantite_quantite_config' );
	}

	public function getAvailFrom() {
		return $this->getCustom( 'amapress_contrat_quantite_avail_from' );
	}

	public function getAvailTo() {
		return $this->getCustom( 'amapress_contrat_quantite_avail_to' );
	}

	public function getQuantiteOptions() {
		$confs = $this->getQuantiteConfig();
		if ( empty( $confs ) ) {
			$confs = '0>10:1';
		}

		$options = array();
		foreach ( explode( ';', $confs ) as $conf ) {
			$m     = array();
			$float = '(?:\d+(?:[,\.]\d+)?)';
			$unit  = '(?:g|kg|l|ml)';
			if ( preg_match( "/(?<start>$float)(?<start_unit>$unit)?(?:\\s*\\>\\s*(?<stop>$float)(?<stop_unit>$unit)?(?:\\s*\\:\\s*(?<incr>$float)(?<incr_unit>$unit)?)?)?/", $conf, $m ) !== false ) {
				$start_unit_factor = isset( $m['start_unit'] ) && ( $m['start_unit'] == 'g' || $m['start_unit'] == 'ml' ) ? 1000 : 1;
				$start             = isset( $m['start'] ) ? floatval( str_replace( ',', '.', $m['start'] ) ) : 1;
				$start             = $start / $start_unit_factor;
				$stop_unit_factor  = isset( $m['stop_unit'] ) && ( $m['stop_unit'] == 'g' || $m['stop_unit'] == 'ml' ) ? 1000 : 1;
				$stop              = isset( $m['stop'] ) ? floatval( str_replace( ',', '.', $m['stop'] ) ) : 1;
				$stop              = $stop / $stop_unit_factor;
				$incr_unit_factor  = isset( $m['incr_unit'] ) && ( $m['incr_unit'] == 'g' || $m['incr_unit'] == 'ml' ) ? 1000 : 1;
				$incr              = isset( $m['incr'] ) ? floatval( str_replace( ',', '.', $m['incr'] ) ) : 1;
				$incr              = $incr / $incr_unit_factor;

				for ( $i = $start; $i <= $stop; $i += $incr ) {
					$k             = strval( $i );
					$options[ $k ] = $this->formatValue( $i );
				}
			}
		}

		return $options;
	}

	public function formatValue( $value ) {
		if ( $this->getPriceUnit() == 'kg' ) {
			if ( $value < 1 ) {
				return sprintf( '%d', (int) ( $value * 1000.0 ) ) . 'g';
			} else {
				return round( $value, 2 ) . 'kg';
			}
		} else if ( $this->getPriceUnit() == 'l' ) {
			if ( $value < 1 ) {
				return sprintf( '%d', (int) ( $value * 1000.0 ) ) . 'ml';
			} else {
				return round( $value, 2 ) . 'l';
			}
		} else {
			if ( abs( $value - 0.25 ) < 0.001 ) {
				return '1/4';
			} else if ( abs( $value - 0.333 ) < 0.001 ) {
				return '2/3';
			} else if ( abs( $value - 0.5 ) < 0.001 ) {
				return '1/2';
			} else if ( abs( $value - 0.666 ) < 0.001 ) {
				return '2/3';
			} else if ( abs( $value - 0.75 ) < 0.001 ) {
				return '3/4';
			} else {
				return round( $value, 2 );
			}
		}
	}

	public function cloneForContrat( $contrat_instance_id, $as_draft = true ) {
		$meta = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}
		$meta['amapress_contrat_quantite_contrat_instance'] = $contrat_instance_id;

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressContrat_quantite::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => $as_draft ? 'draft' : 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		return new AmapressContrat_quantite( $new_id );
	}
}
