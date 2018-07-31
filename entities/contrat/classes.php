<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressContrat extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat';
	const POST_TYPE = 'contrat';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressContrat
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressContrat' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressContrat( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_contrat_photo');
//    }

//	public function getPresentation() {
//		return wpautop( $this->getCustom( 'amapress_contrat_presentation' ) );
//	}
//
//	public function getPresentationRaw() {
//		return $this->getCustom( 'amapress_contrat_presentation' );
//	}

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


	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressContrat_instance
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressContrat_instance' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressContrat_instance( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	/** @return AmapressContrat */
	public function getModel() {
		return $this->getCustomAsEntity( 'amapress_contrat_instance_model', 'AmapressContrat' );
	}

	/** @return string */
	public function getSubName() {
		return $this->getCustom( 'amapress_contrat_instance_name' );
	}

	/** @return int */
	public function getModelId() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_model' );
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

	public function isQuantiteMultiple() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_quantite_multi', 1 );
	}

	public function isQuantiteVariable() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_quantite_variable' );
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

//	public function getType() {
//		return $this->getCustom( 'amapress_contrat_instance_type' );
//	}

	public function getNb_visites() {
		return floatval( $this->getCustom( 'amapress_contrat_instance_nb_visites', 0 ) );
	}

	/** @return string */
//	public function getOnlineContrat() {
//		return wpautop( $this->getCustom( 'amapress_contrat_instance_contrat' ) );
//	}

	/** @return string */
	public function getOnlineContratRaw() {
		return $this->getCustom( 'amapress_contrat_instance_contrat' );
	}

	public function hasOnlineContrat() {
		$contrat_cnt = $this->getOnlineContratRaw();
		if ( empty( $contrat_cnt ) || strlen( wp_strip_all_tags( $contrat_cnt ) ) < 15 ) {
			return false;
		}

		return true;
	}

	public function isPrincipal() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_is_principal', 0 );
	}

	public function canSelfSubscribe() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_self_subscribe', 0 );
	}

	public function isEnded() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_ended', 0 );
	}

	public function getListe_dates() {
		$liste_dates = $this->getCustomAsDateArray( 'amapress_contrat_instance_liste_dates' );
		if ( empty( $liste_dates ) || count( $liste_dates ) == 0 ) {
			$liste_dates = $this->getCustomAsDateArray( 'amapress_contrat_instance_commande_liste_dates' );
		}

		$liste_dates = array_filter( $liste_dates, function ( $d ) {
			return Amapress::start_of_day( $this->getDate_debut() ) <= $d && $d <= Amapress::end_of_day( $this->getDate_fin() );
		} );

		return $liste_dates;
	}

	public function getRattrapage() {
		return $this->getCustomAsArray( 'amapress_contrat_instance_rattrapage' );
	}

	public function getDateFactor( $dist_date ) {
		$date_factor = 1;
		$rattrapage  = $this->getRattrapage();
		foreach ( $rattrapage as $r ) {
			if ( Amapress::start_of_day( intval( $r['date'] ) ) == Amapress::start_of_day( $dist_date ) ) {
				$date_factor = floatval( $r['quantite'] );
				break;
			}
		}

		return $date_factor;
	}

	public function getPaiements_Liste_dates() {
		return $this->getCustomAsDateArray( 'amapress_contrat_instance_liste_dates_paiements' );
	}

	public function getPossiblePaiements() {
		return $this->getCustomAsIntArray( 'amapress_contrat_instance_paiements' );
	}

	public function getMinChequeAmount() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_min_cheque_amount' );
	}

	public function getContratWordModelId() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_word_model' );
	}

	public function getContratModelDocFileName() {
		return get_attached_file( $this->getContratWordModelId(), true );
	}

//	public function getContratDocFileName($date_first_distrib) {
//		$model_filename = $this->getContratModelDocFileName();
//		$ext = strpos($model_filename, '.docx') !== false ? '.docx' : '.odt';
//		return trailingslashit( Amapress::getContratDir() ) . sanitize_file_name(
//				'inscription-' . $this->ID . '-' . date_i18n('Y-m-d', $date_first_distrib) . '-' . $this->getTitle() . $ext );
//	}

//	public function generateContratDoc($date_first_distrib) {
//		$out_filename = $this->getContratDocFileName($date_first_distrib);
//		$model_filename = $this->getContratModelDocFileName();
//
//		$placeholders = [];
//		foreach ( self::getProperties() as $prop_name => $prop_config ) {
//			$placeholders[ $prop_name ] = call_user_func( $prop_config['func'], $this );
//		}
//
//		$remaining_dates = $this->getRemainingDatesWithFactors($date_first_distrib);
//		$quants = $this->get( null );
////		if ( ! $this->getContrat_instance()->isPanierVariable() ) {
//		$i = 1;
//		foreach ( $quants as $quant ) {
//			$placeholders["quantite#$i"]               = $quant->getTitle();
//			$placeholders["quantite_code#$i"]          = $quant->getCode();
//			$placeholders["quantite_total#$i"]         = $quant->getPrice();
//			$placeholders["quantite_nombre#$i"]        = $quant->getFactor();
//			$placeholders["quantite_prix_unitaire#$i"] = $quant->getContratQuantite()->getPrix_unitaire();
//			$placeholders["quantite_description#$i"]   = $quant->getContratQuantite()->getDescription();
//			$placeholders["quantite_unite#$i"]         = $quant->getContratQuantite()->getPriceUnitDisplay();
//			$i                                         += 1;
//		}
////		}
//
//
//		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor( $model_filename );
//		$templateProcessor->cloneRow( 'quantite', count( $quants ) );
//		foreach ( $placeholders as $k => $v ) {
//			$templateProcessor->setValue( $k, $v );
//		}
//
//		$templateProcessor->saveAs( $out_filename );
//
//		return $out_filename;
//	}
//

	public function getMinEngagement() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_min_engagement' );
	}

	public function getChequeOptionsForTotal( $nb_cheque, $total ) {
//		$last_cheque = $this->getMinChequeAmount();

		if ( $nb_cheque > 1 ) {
			if ( ( $total / $nb_cheque ) * 2 == intval( $total / $nb_cheque * 2 ) ) {
				$last_cheque        = $total / $nb_cheque;
				$cheque_main_amount = $total / $nb_cheque;
			} else {
				$cheque_main_amount = floor( $total / $nb_cheque );
				$last_cheque        = $total - $cheque_main_amount * ( $nb_cheque - 1 );
			}
		} else {
			$last_cheque        = 0;
			$cheque_main_amount = $total;
		}

		$nb = $nb_cheque;
		if ( $cheque_main_amount == $last_cheque ) {
			$last_cheque = 0;
			$option      = sprintf( "$nb chèque(s) de %0.2f €", $cheque_main_amount );
		} else if ( $last_cheque == 0 ) {
			$nb     = 1;
			$option = sprintf( "1 chèque de %0.2f €", $cheque_main_amount );
		} else {
			$nb     = $nb_cheque - 1;
			$option = sprintf( "$nb chèque(s) de %0.2f € et 1 chèque de %0.2f €", $cheque_main_amount, $last_cheque );
		}

		return [
			'desc'          => $option,
			'main_amount'   => $cheque_main_amount,
			'remain_amount' => $last_cheque,
		];
	}

	/** @return AmapressAdhesion[] */
	public function getAdhesionsForUser( $user_id = null, $date = null, $ignore_renouv_delta = false ) {
		return AmapressAdhesion::getUserActiveAdhesions( $user_id, $this->ID, $date, $ignore_renouv_delta );
	}

	/**
	 * @return int[]
	 */
	public static function getContratInstanceIdsForUser( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_contrat_instances_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$ads = AmapressAdhesion::getUserActiveAdhesions( $user_id, $contrat_id, $date, $ignore_renouv_delta );
			$res = array();
			foreach ( $ads as $ad ) {
				$res[] = $ad->getContrat_instanceId();
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public function cloneContrat( $as_draft = true, $for_renew = true, $same_period = false ) {
		$this->ensure_init();

		if ( ! $for_renew ) {
			$add_weeks = 0;
		} else if ( $same_period ) {
			$add_weeks = 52;
		} else {
			$add_weeks = Amapress::datediffInWeeks(
				Amapress::start_of_week( $this->getDate_debut() ),
				Amapress::end_of_week( $this->getDate_fin() )
			);
		}
		$meta = array();
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

		$new_liste_dates_paiements                               = array_map(
			function ( $date ) use ( $add_weeks ) {
				return Amapress::add_a_week( $date, $add_weeks );
			}, $this->getPaiements_Liste_dates() );
		$meta['amapress_contrat_instance_liste_dates_paiements'] = implode( ',',
			array_map(
				function ( $date ) {
					return date( 'd/m/Y', $date );
				}, $new_liste_dates_paiements )
		);
		if ( $for_renew ) {
//			unset( $meta['amapress_contrat_instance_liste_dates_paiements'] );
			unset( $meta['amapress_contrat_instance_commande_liste_dates'] );
		}
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

		return AmapressContrat_instance::getBy( $new_id );
	}

	public function canRenew() {
		$contrat_instances = AmapressContrats::get_active_contrat_instances();

		return ! from( $contrat_instances )->any( function ( $a ) {
			/** @var AmapressContrat_instance $a */
			return ( $a->getModelId() == $this->getModelId()
			         && ( $a->getSubName() == $this->getSubName() || ( empty( $a->getSubName() ) && empty( $this->getSubName() ) ) )
			         && $a->getDate_debut() > $this->getDate_debut() );
		} );
	}

	public function getRemainingDatesWithFactors( $start_date ) {
		$dates         = $this->getListe_dates();
		$dates         = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return $d >= $start_date;
		} );
		$dates_factors = 0;
		foreach ( $dates as $d ) {
			$dates_factors += $this->getDateFactor( $d );
		}

		return $dates_factors;
	}
}

class AmapressContrat_quantite extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat_quant';
	const POST_TYPE = 'contrat_quantite';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressContrat_quantite
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressContrat_quantite' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressContrat_quantite( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

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
			$unit  = '(?:g|kg|l|ml|L)';
			if ( preg_match( "/(?<start>$float)(?<start_unit>$unit)?(?:\\s*(?:\\>|-)\\s*(?<stop>$float)(?<stop_unit>$unit)?(?:\\s*\\:\\s*(?<incr>$float)(?<incr_unit>$unit)?)?)?/", $conf, $m ) !== false ) {
				$start_unit_factor = isset( $m['start_unit'] ) && ( $m['start_unit'] == 'g' || $m['start_unit'] == 'ml' ) ? 1000 : 1;
				$start             = isset( $m['start'] ) ? floatval( str_replace( ',', '.', $m['start'] ) ) : 1;
				$start             = $start / $start_unit_factor;
				$stop_unit_factor  = isset( $m['stop_unit'] ) && ( $m['stop_unit'] == 'g' || $m['stop_unit'] == 'ml' ) ? 1000 : 1;
				$stop              = isset( $m['stop'] ) ? floatval( str_replace( ',', '.', $m['stop'] ) ) : $start;
				$stop              = $stop / $stop_unit_factor;
				$incr_unit_factor  = isset( $m['incr_unit'] ) && ( $m['incr_unit'] == 'g' || $m['incr_unit'] == 'ml' ) ? 1000 : 1;
				$incr              = isset( $m['incr'] ) ? floatval( str_replace( ',', '.', $m['incr'] ) ) : 1;
				$incr              = $incr / $incr_unit_factor;

				for ( $i = $start; $i <= $stop; $i += $incr ) {
					if ( $i > 0 ) {
						$k             = strval( $i );
						$options[ $k ] = $this->formatValue( $i );
					}
				}
			}
		}

		return $options;
	}

	public
	function formatValue(
		$value
	) {
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
				return round( $value, 2 ) . 'L';
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

	public
	function getFormattedTitle(
		$factor
	) {
		if ( $factor != 1 ) {
			return "$factor x {$this->getTitle()}";
		} else {
			return $this->getTitle();
		}
	}

	public
	function cloneForContrat(
		$contrat_instance_id
	) {
		$this->ensure_init();

		$meta = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}
		$meta['amapress_contrat_quantite_contrat_instance'] = $contrat_instance_id;

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressContrat_quantite::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		return AmapressContrat_quantite::getBy( $new_id );
	}
}
