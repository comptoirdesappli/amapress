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
			$post    = $post_or_id;
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

	public function getInstructionsDistribution() {
		return wpautop( stripslashes( $this->getCustom( 'amapress_contrat_instr_distrib' ) ) );
	}

	/** @return AmapressProducteur */
	public function getProducteur() {
		return $this->getCustomAsEntity( 'amapress_contrat_producteur', 'AmapressProducteur' );
	}

	/** @return int */
	public function getProducteurId() {
		return $this->getCustomAsInt( 'amapress_contrat_producteur' );
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
	public function getReferentsIds( $lieu_id = null, $for_lieu_only = false, $for_contrat_only = false ) {
		if ( empty( $this->getProducteur() ) ) {
			return [];
		}

		$key = "AmapressContrat_getReferentsIds_{$lieu_id}_{$for_lieu_only}_{$for_contrat_only}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( ! $for_contrat_only ) {
				$prod_refs = $this->getProducteur()->getReferentsIds( $lieu_id, $for_lieu_only );
				if ( ! $for_lieu_only ) {
					$prod_refs = array_merge( $prod_refs, $this->getProducteur()->getReferentsIds( null, $for_lieu_only ) );
				}
			} else {
				$prod_refs = [];
			}

			$contrat_refs = $for_lieu_only ? [
				$this->getReferentId( $lieu_id, $for_lieu_only ),
				$this->getReferent2Id( $lieu_id, $for_lieu_only ),
				$this->getReferent3Id( $lieu_id, $for_lieu_only )
			] : [
				$this->getReferentId( $lieu_id ),
				$this->getReferent2Id( $lieu_id ),
				$this->getReferent3Id( $lieu_id ),
				$this->getReferentId( null ),
				$this->getReferent2Id( null ),
				$this->getReferent3Id( null ),
			];

			$res = array_unique(
				array_merge( $prod_refs,
					array_filter( $contrat_refs, function ( $i ) {
						return ! empty( $i );
					} )
				)
			);
		}

		return $res;
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
				$meta_name = 'amapress_contrat_referent' . ( $num > 1 ? $num : '' ) . ( $lieu_id ? '_' . $lieu_id : '' );
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
		$v = $this->getCustom( 'amapress_contrat_referent' . ( $num > 1 ? $num : '' ) . ( $lieu_id ? '_' . $lieu_id : '' ) );
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
}

class AmapressContrat_instance extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat_inst';
	const POST_TYPE = 'contrat_instance';


	/** @return AmapressContrat_instance[] */
	public static function getAll() {
		return array_map(
			function ( $p ) {
				return AmapressContrat_instance::getBy( $p );
			},
			get_posts(
				array(
					'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
				)
			)
		);
	}

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
		if ( $no_cache ) {
			unset( self::$entities_cache[ $post_id ] );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) ) {
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

	public function hasPanier_CustomContent() {
		return $this->getCustom( 'amapress_contrat_instance_has_pancust', 0 );
	}

	public function getSpecialMention() {
		return $this->getCustom( 'amapress_contrat_instance_special_mention', '' );
	}

	public function getPaiementsMention() {
		return $this->getCustom( 'amapress_contrat_instance_paiements_mention', '' );
	}

	public function getPaiementsOrdre() {
		$ret = $this->getCustom( 'amapress_contrat_instance_paiements_ordre', '' );
		if ( empty( $ret ) && $this->getModel() && $this->getModel()->getProducteur() && $this->getModel()->getProducteur()->getUser() ) {
			$ret = $this->getModel()->getProducteur()->getUser()->getDisplayName();
		}

		return $ret;
	}

	public function getManage_Cheques() {
		return $this->getCustom( 'amapress_contrat_instance_manage_paiements', 1 );
	}

	public function getAllowAmapienInputPaiementsDetails() {
		return $this->getCustom( 'amapress_contrat_instance_pmt_user_input', 1 );
	}

	public function getAllowAmapienInputPaiementsDates() {
		return $this->getCustom( 'amapress_contrat_instance_pmt_user_dates', 0 );
	}

	public function getAllow_Delivery_Pay() {
		return $this->getCustom( 'amapress_contrat_instance_allow_deliv_pay', 0 );
	}

	public function getAllow_Cash() {
		return $this->getCustom( 'amapress_contrat_instance_allow_cash', 0 );
	}

	public function getStripePublicKey() {
		return $this->getCustom( 'amapress_contrat_instance_stripe_public_key', '' );
	}

	public function getStripeSecretKey() {
		return $this->getCustom( 'amapress_contrat_instance_stripe_secret_key', '' );
	}

	public function getStripeMinAmount() {
		return $this->getCustomAsFloat( 'amapress_contrat_instance_stripe_min_amount', 0 );
	}

	public function getAllow_Stripe() {
		return ! empty( $this->getStripePublicKey() ) && ! empty( $this->getStripeSecretKey() );
	}

	public function getDon_Distribution() {
		return $this->getCustom( 'amapress_contrat_instance_don_dist', 0 );
	}

	public function getDon_DistributionLabel() {
		return stripslashes( $this->getCustom( 'amapress_contrat_instance_don_dist_lbl', __( 'Don par distribution', 'amapress' ) ) );
	}

	public function getDon_DistributionDescription() {
		return stripslashes( $this->getCustom( 'amapress_contrat_instance_don_dist_desc', '' ) );
	}

	public function getPayByMonth() {
		return $this->getCustom( 'amapress_contrat_instance_pay_month', 0 )
		       || $this->getPayByMonthOnly();
	}

	public function getPayByMonthOnly() {
		return $this->getCustom( 'amapress_contrat_instance_pay_month_only', 0 );
	}

	public function getAllow_Transfer() {
		return $this->getCustom( 'amapress_contrat_instance_allow_bktrfr', 0 );
	}

	public function getAllow_LocalMoney() {
		return $this->getCustom( 'amapress_contrat_instance_allow_locmon', 0 );
	}

	public function getAllow_Prelevement() {
		return $this->getCustom( 'amapress_contrat_instance_allow_prlv', 0 );
	}

	public function getNb_responsables_Supplementaires() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_nb_resp_supp', 0 );
	}

	public function getModelTitle() {
		$model = $this->getModel();
		if ( empty( $model ) ) {
			return __( 'Présentation Archivée', 'amapress' );
		}

		return $model->getTitle();
	}

	public function getModelTitleWithSubName() {
		if ( ! empty( $this->getSubName() ) ) {
			return $this->getModelTitle() . ' - ' . $this->getSubName();
		} else {
			return $this->getModelTitle();
		}
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

	public function getMaxUseEquivalentQuant() {
		return $this->getCustom( 'amapress_contrat_instance_use_equiv', 0 );
	}

	public function hasEquivalentQuant() {
		return from( AmapressContrats::get_contrat_quantites( $this->ID, null, false ) )->distinct( function ( $c ) {
				/** @var AmapressContrat_quantite $c */
				return $c->getQuantite();
			} )->count() > 1;
	}

	public function getMax_adherents() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_max_adherents', 0 );
	}

	public function getDate_ouverture() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_ouverture' );
	}

	public function getContratInfo() {
		$info = $this->getCustom( 'amapress_contrat_instance_contrat_info' );

		$placeholders = [];
		foreach ( self::getProperties( amapress_time() ) as $prop_name => $prop ) {
			$placeholders[ $prop_name ] = $prop;
		}
		foreach ( self::getProperties( $this->getDate_debut() ) as $prop_name => $prop ) {
			$placeholders[ 'total_' . $prop_name ] = $prop;
		}

		$info = $res = preg_replace_callback( '/\%\%(?<opt>[\w\d_-]+)(?:\:(?<subopt>[\w\d_-]+))?(?:,(?<fmt>[^%]+))?\%\%/i',
			function ( $m ) use ( $placeholders ) {
				$opt = isset( $m['opt'] ) ? $m['opt'] : '';
//				$subopt = isset( $m['subopt'] ) ? $m['subopt'] : '';
//				$fmt    = isset( $m['fmt'] ) ? $m['fmt'] : '';

				if ( isset( $placeholders[ $opt ] ) ) {
					return call_user_func( $placeholders[ $opt ]['func'], $this );
				} else {
					return "%%UNK:$opt%%";
				}
			}, $info );

		return $info;
	}

	public function getDate_cloture() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_cloture' );
	}

	public function addClotureDays( $days = 1 ) {
		$date = $this->getDate_cloture();
		$this->setCustom( 'amapress_contrat_instance_date_cloture', Amapress::add_days( $date, $days ) );
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

	public function getMaxContratMonths() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_max_months', - 1 );
	}

	public function getSampleQuantiteCSV() {
		$quants             = AmapressContrats::get_contrat_quantites( $this->ID );
		$has_distinct_value = ( count( $quants ) == count( array_unique( array_map( function ( $q ) {
				/** @var AmapressContrat_quantite $q */
				return $q->getQuantite();
			}, $quants ) ) ) );
		$contrat_ret        = [
			''
		];
		if ( ! $this->isQuantiteVariable() && 1 == count( $quants ) ) {
			$contrat_ret[] = 'X';
		}

		foreach ( $quants as $q ) {
			$contrat_ret[] = $q->getTitle();
			$contrat_ret[] = $q->getCode();
			if ( $has_distinct_value ) {
				$contrat_ret[] = strval( $q->getQuantite() );
				if ( $this->isQuantiteVariable() ) {
					$contrat_ret[] = sprintf( __( 'Multiple de %s', 'amapress' ), $q->getQuantite() );
				}
			}
			if ( $this->isQuantiteVariable() ) {
				foreach ( $q->getQuantiteOptions() as $v ) {
					$contrat_ret[] = $v . ' ' . $q->getCode();
					$contrat_ret[] = $v . ' x ' . $q->getCode();
				}
			}
		}
		if ( $this->isQuantiteMultiple() && count( $quants ) > 1 ) {
			$contrat_ret[] = __( 'Par ex: ', 'amapress' ) . __( 'X ', 'amapress' ) . $quants[0]->getCode() . ', Y ' . $quants[1]->getCode();
			$contrat_ret[] = __( 'Par ex: ', 'amapress' ) . __( 'X x ', 'amapress' ) . $quants[0]->getCode() . ', Y x ' . $quants[1]->getCode();
		}

		return $contrat_ret;
	}

	/** @return AmapressLieu_distribution[] */
	public function getLieux() {
		return $this->getCustomAsEntityArray( 'amapress_contrat_instance_lieux', 'AmapressLieu_distribution' );
	}

	/** @return int[] */
	public function getLieuxIds() {
		return $this->getCustomAsIntArray( 'amapress_contrat_instance_lieux' );
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
	public function getReferentsIds( $lieu_id = null ) {
		if ( empty( $this->getModel() ) ) {
			return [];
		}

		return $this->getModel()->getReferentsIds( $lieu_id );
	}

	/** @return string[] */
	public function getAllReferentsEmails( $lieu_id = null ) {
		if ( empty( $this->getModel() ) ) {
			return [];
		}

		return array_unique( array_map(
			function ( $ref_id ) {
				$ref = AmapressUser::getBy( $ref_id );
				if ( empty( $ref ) ) {
					return '';
				}

				return $ref->getEmail();
			},
			$this->getModel()->getReferentsIds( $lieu_id )
		) );
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

	public function setSelfSubscribe( $open ) {
		$this->setCustom( 'amapress_contrat_instance_self_subscribe', $open ? 1 : 0 );
	}

	public function canSelfSubscribe( $user_id = null ) {
		$res = $this->getCustomAsInt( 'amapress_contrat_instance_self_subscribe', 0 );
		if ( empty( $res ) ) {
			return $res;
		}
		if ( $user_id ) {
			$contrats_conditions = $this->canSelfContratsCondition();
			if ( ! empty( $contrats_conditions ) ) {
				$start_date = amapress_time();
				foreach ( $contrats_conditions as $contrat ) {
					if ( $contrat->getDate_debut() < $start_date ) {
						$start_date = $contrat->getDate_debut();
					}
				}
				$all_contrat_ids = array_map( function ( $adh ) {
					/** @var AmapressAdhesion $adh */
					return $adh->getContrat_instanceId();
				}, AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck(
					$user_id, null, $start_date, false, true, true
				) );
				foreach ( $contrats_conditions as $contrat ) {
					if ( ! in_array( $contrat->ID, $all_contrat_ids ) ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	/** @return AmapressContrat_instance[] */
	public function canSelfContratsCondition() {
		return $this->getCustomAsEntityArray( 'amapress_contrat_instance_self_contrats', 'AmapressContrat_instance' );
	}

	public function canSelfEdit() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_self_edit', 0 );
	}

	public function hasGroups() {
		$contrat_instance_quantites = AmapressContrats::get_contrat_quantites( $this->ID );
		$has_groups                 = false;
		foreach ( $contrat_instance_quantites as $q ) {
			if ( ! empty( $q->getGroupName() ) ) {
				$has_groups = true;
				break;
			}
		}

		return $has_groups;
	}

	public function isFull( $contrat_quantite_id = null, $lieu_id = null, $date = null ) {
		$use_equiv      = $this->getMaxUseEquivalentQuant();
		$max_adhs       = $this->getMax_adherents();
		$max_quant_adhs = 0;
		if ( $contrat_quantite_id ) {
			$contrat_quantite = AmapressContrat_quantite::getBy( $contrat_quantite_id );
			$max_quant_adhs   = $contrat_quantite->getMaxAdherents();
		}
		if ( $max_adhs > 0 || $max_quant_adhs > 0 ) {
			Amapress::setFilterForReferent( false );
			$adhs = AmapressContrats::get_active_adhesions( $this->ID, $contrat_quantite_id, $lieu_id, $date );
			Amapress::setFilterForReferent( true );

			$adhs_count = 0;
			foreach ( $adhs as $adh ) {
				if ( $use_equiv ) {
					foreach ( $adh->getContrat_quantites( null ) as $q ) {
						$adhs_count += $q->getQuantite();
					}
				} else {
					$adhs_count += 1;
				}
			}

			if ( $max_adhs > 0 && $adhs_count >= $max_adhs ) {
				return true;
			}

			if ( $max_quant_adhs > 0 && $adhs_count >= $max_quant_adhs ) {
				return true;
			}
		}

		return false;
	}

	public function getAdherentsCount( $contrat_quantite_id = null, $lieu_id = null, $date = null ) {
		if ( empty( $date ) && empty( $lieu_id ) && empty( $contrat_quantite_id ) ) {
			return get_posts_count( "post_type=amps_adhesion&amapress_contrat_inst={$this->ID}" );
		}
		Amapress::setFilterForReferent( false );
		$adhs = AmapressContrats::get_active_adhesions( $this->ID, $contrat_quantite_id, $lieu_id, $date );
		Amapress::setFilterForReferent( true );

		return count( $adhs );
	}

	public function getAdherentsEquivalentQuantites( $contrat_quantite_id = null, $lieu_id = null, $date = null ) {
		if ( ! $this->hasEquivalentQuant() ) {
			return 0;
		}
		Amapress::setFilterForReferent( false );
		$adhs = AmapressContrats::get_active_adhesions( $this->ID, $contrat_quantite_id, $lieu_id, $date );
		Amapress::setFilterForReferent( true );

		$adhs_count = 0;
		foreach ( $adhs as $adh ) {
			foreach ( $adh->getContrat_quantites( null ) as $q ) {
				$adhs_count += $q->getQuantite();
			}
		}

		return $adhs_count;
	}

	public function isEnded() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_ended', 0 );
	}

	private $liste_dates = null;

	public function getListe_dates() {
		if ( null === $this->liste_dates ) {
			$this->liste_dates = array_filter( $this->getCustomAsDateArray( 'amapress_contrat_instance_liste_dates' ),
				function ( $d ) {
					return Amapress::start_of_day( $this->getDate_debut() ) <= $d && $d <= Amapress::end_of_day( $this->getDate_fin() );
				} );
		}

		return $this->liste_dates;
	}

	public function getRattrapage() {
		return $this->getCustomAsArray( 'amapress_contrat_instance_rattrapage' );
	}

	/** @return array */
	public function getRemainingDatesByMonth( $date = null ) {
		$dates           = $this->getRemainingDates( $date );
		$by_month_totals = [];
		foreach ( $dates as $date ) {
			$month = date_i18n( 'M', $date );
			if ( empty( $by_month_totals[ $month ] ) ) {
				$by_month_totals[ $month ] = 0;
			}
			$by_month_totals[ $month ] += 1;
		}

		return $by_month_totals;
	}

	public function getDateFactor( $dist_date, $quantite_id = null ) {
		$key         = "AmapressCI_getDateFactor_{$dist_date}_{$quantite_id}";
		$date_factor = wp_cache_get( $key );
		if ( false === $date_factor ) {
			$date_factor = 1;
			if ( $dist_date && $quantite_id ) {
				$quantite = AmapressContrat_quantite::getBy( $quantite_id );
				if ( $quantite ) {
					if ( ! $quantite->isInDistributionDates( $dist_date ) ) {
						return 0;
					}
				}
			}
			$rattrapage = $this->getRattrapage();
			foreach ( $rattrapage as $r ) {
				if ( Amapress::start_of_day( intval( $r['date'] ) ) == Amapress::start_of_day( $dist_date ) ) {
					$date_factor = floatval( $r['quantite'] );
					break;
				}
			}

			wp_cache_set( $key, $date_factor );
		}

		return $date_factor;
	}

	public function getDateFactorDisplay( $date ) {
		$factor = $this->getDateFactor( $date );
		if ( abs( $factor - 2 ) < 0.001 ) {
			return __( 'Double distribution', 'amapress' );
		} elseif ( abs( $factor - 1 ) < 0.001 ) {
			return '';
		} else {
			return "$factor distribution";
		}
	}

	public function getRealDateForDistribution( $date ) {
		$paniers = AmapressPanier::get_delayed_paniers( null, null, $date, [ 'delayed' ] );
		foreach ( $paniers as $p ) {
			if ( $p->getContrat_instanceId() == $this->ID ) {
				return Amapress::start_of_day( $p->getDateSubst() );
			}
		}

		return Amapress::start_of_day( $date );
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
		$contrat_attachment_id = $this->getCustomAsInt( 'amapress_contrat_instance_word_model' );
		if ( empty( $contrat_attachment_id ) ) {
			if ( $this->isPanierVariable() ) {
				if ( $this->hasGroups() ) {
					$contrat_attachment_id = Amapress::getOption( 'default_word_modulable_group_model' );
				} else {
					$contrat_attachment_id = Amapress::getOption( 'default_word_modulable_model' );
				}
			} else {
				$contrat_attachment_id = Amapress::getOption( 'default_word_model' );
			}
		}

		return $contrat_attachment_id;
	}

	public function getContratModelDocFileName() {
		if ( defined( 'AMAPRESS_DEMO_MODE' ) ) {
			if ( $this->isPanierVariable() ) {
				if ( $this->hasGroups() ) {
					return AMAPRESS__PLUGIN_DIR . 'templates/contrat_generique_modulables_complet.docx';
				} else {
					return AMAPRESS__PLUGIN_DIR . 'templates/contrat_generique_modulables.docx';
				}
			} else {
				return AMAPRESS__PLUGIN_DIR . 'templates/contrat_generique.docx';
			}
		}

		return get_attached_file( $this->getContratWordModelId(), true );
	}

	public function getContratPapierWordModelId() {
		$contrat_attachment_id = $this->getCustomAsInt( 'amapress_contrat_instance_word_paper_model' );
		if ( empty( $contrat_attachment_id ) ) {
			if ( $this->isPanierVariable() ) {
				if ( $this->hasGroups() ) {
					$contrat_attachment_id = Amapress::getOption( 'default_word_modulable_group_paper_model' );
				} else {
					$contrat_attachment_id = Amapress::getOption( 'default_word_modulable_paper_model' );
				}
			} else {
				$contrat_attachment_id = Amapress::getOption( 'default_word_paper_model' );
			}
		}

		return $contrat_attachment_id;
	}

	public function getContratPapierModelDocFileName() {
		if ( defined( 'AMAPRESS_DEMO_MODE' ) ) {
			if ( $this->isPanierVariable() ) {
				return AMAPRESS__PLUGIN_DIR . 'templates/contrat_generique_modulables.docx';
			} else {
				return AMAPRESS__PLUGIN_DIR . 'templates/contrat_generique.docx';
			}
		}

		return get_attached_file( $this->getContratPapierWordModelId(), true );
	}

	public function getContratModelDocStatus() {
		$model_file   = $this->getContratModelDocFileName();
		$placeholders = $this->generateContratDoc( $this->getDate_debut(), false, true );

		return Phptemplate_withnewline::getPlaceholderStatus( $model_file, $placeholders, __( 'Contrat personnalisé', 'amapress' ) );
	}

	public function getContratPapierModelDocStatus() {
		$model_file   = $this->getContratPapierModelDocFileName();
		$placeholders = $this->generateContratDoc( $this->getDate_debut(), false, true );

		return Phptemplate_withnewline::getPlaceholderStatus( $model_file, $placeholders, __( 'Contrat vierge', 'amapress' ) );
	}

//	public static function getPlaceholdersHelp() {
//		$ret = [];
//
//		foreach ( self::getProperties() as $prop_name => $prop ) {
//			if ( ! isset( $prop['desc'] ) ) {
//				continue;
//			}
//
//			$ret[ $prop_name ] = $prop['desc'];
//		}
//		$ret["quantite"]               = '(Tableau quantité) Libellé quantité';
//		$ret["quantite_code"]          = '(Tableau quantité) Code quantité';
//		$ret["quantite_nb_distrib"]    = '(Tableau quantité) Nombre de distribution restantes';
//		$ret["quantite_sous_total"]    = '(Tableau quantité) Prix pour la quantité choisie';
//		$ret["quantite_total"]         = '(Tableau quantité) Prix pour la quuantité choisie x nombre distrib';
//		$ret["quantite_nombre"]        = '(Tableau quantité) Facteur quantité choisi';
//		$ret["quantite_prix_unitaire"] = '(Tableau quantité) Prix à l\'unité';
//		$ret["quantite_description"]   = '(Tableau quantité) Description de la quantité';
//		$ret["quantite_unite"]         = '(Tableau quantité) Unité de la quantité';
//
//		return '<table id="contrat-placeholders"><thead><tr><th>Placeholder</th><th>Description</th></tr></thead>' .
//		       implode( '', array_map( function ( $pn, $p ) {
//			       return '<tr><td>${' . esc_html( $pn ) . '}</td><td>' . esc_html( $p ) . '</td></tr>';
//		       }, array_keys( $ret ), array_values( $ret ) ) )
//		       . '</table>';
//	}

	public function getMailtoAmapiens() {
		$mails = [];
		foreach (
			get_users( [
				'amapress_contrat' => $this->ID,
			] ) as $user
		) {
			/** @var WP_User $user */
			$amapien = AmapressUser::getBy( $user );
			$mails   = array_merge( $mails, $amapien->getAllEmails() );
		}
		if ( empty( $mails ) ) {
			return '';
		}

		$site_email = Amapress::getOption( 'email_from_mail' );

		return 'mailto:' . rawurlencode( $site_email ) . '?bcc=' . rawurlencode( implode( ',', $mails ) ) . '&subject=' . __( 'Contrat ', 'amapress' ) . $this->getTitle();
	}

	public function getContratDocFileName( $date_first_distrib ) {
		$model_filename = $this->getContratModelDocFileName();
		$ext            = strpos( $model_filename, '.docx' ) !== false ? '.docx' : '.odt';

		return trailingslashit( Amapress::getContratDir() ) . sanitize_file_name(
				__( 'contrat-papier-', 'amapress' ) . $this->getTitle() . '-' . $this->ID . '-' . date_i18n( 'Y-m-d', $date_first_distrib ) . $ext );
	}

	public function getFormattedRattrapages( $dates = null ) {
		$rattrapage        = [];
		$double_rattrapage = [];
		$un5_rattrapage    = [];
		if ( null == $dates ) {
			$dates = $this->getRemainingDates();
		}
		foreach ( $dates as $d ) {
			$the_factor = $this->getDateFactor( $d );
			if ( abs( $the_factor - 2 ) < 0.001 ) {
				$double_rattrapage[] = date_i18n( 'd/m/Y', $d );
			} else if ( abs( $the_factor - 1.5 ) < 0.001 ) {
				$un5_rattrapage[] = date_i18n( 'd/m/Y', $d );
			} else if ( abs( $the_factor - 1 ) > 0.001 ) {
				$rattrapage[] = sprintf( __( '%d distribution le %s', 'amapress' ), $the_factor, date_i18n( 'd/m/Y', $d ) );
			}
		}

		if ( ! empty( $double_rattrapage ) ) {
			$rattrapage[] = _n( 'double distribution le', 'double distribution les', count( $double_rattrapage ) ) . ' ' . implode( ', ', $double_rattrapage );
		}
		if ( ! empty( $un5_rattrapage ) ) {
			$rattrapage[] = _n( '1.5 distribution le', '1.5 distribution les', count( $un5_rattrapage ) ) . ' ' . implode( ', ', $un5_rattrapage );
		}

		return $rattrapage;
	}

	public function getFormattedDatesDistribMois( $first_date_distrib, $quantite_id = null ) {
		$dates         = $this->getRemainingDates( $first_date_distrib, $quantite_id );
		$grouped_dates = from( $dates )->groupBy( function ( $d ) {
			return date_i18n( 'F Y', $d );
		} );

		$grouped_dates_array = [];
		foreach ( $grouped_dates as $k => $v ) {
			$grouped_dates_array[] = $k . ' : ' . _n( 'le ', 'les ', count( $v ) ) . implode( ', ', array_map(
					function ( $d ) {
						return date_i18n( 'd', $d );
					}, $v
				) );
		}

		return $grouped_dates_array;
	}

	public static function getProperties( $first_date_distrib = null ) {
		$ret                                     = [];
		$ret['contrat_type']                     = [
			'desc' => __( 'Type du contrat (par ex, Légumes)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				if ( empty( $adh->getModel() ) ) {
					return '';
				}

				return $adh->getModelTitle();
			}
		];
		$ret['contrat_type_complet']             = [
			'desc' => __( 'Nom du contrat (par ex, Légumes - Semaine A)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				if ( empty( $adh->getModel() ) ) {
					return '';
				}

				return $adh->getModelTitleWithSubName();
			}
		];
		$ret['contrat_titre_complet']            = [
			'desc' => __( 'Nom du contrat (par ex, Légumes 09/2018-08/2019 - Semaine A)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				if ( ! empty( $adh->getSubName() ) ) {
					return $adh->getTitle() . ' - ' . $adh->getSubName();
				} else {
					return $adh->getTitle();
				}
			}
		];
		$ret['contrat_titre']                    = [
			'desc' => __( 'Nom du contrat (par ex, Légumes 09/2018-08/2019)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getTitle();
			}
		];
		$ret['contrat_sous_titre']               = [
			'desc' => __( 'Nom complémentaire du contrat (par ex, Semaine A)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getSubName();
			}
		];
		$ret['contrat_lien']                     = [
			'desc' => __( 'Lien vers la présentation du contrat', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				if ( empty( $adh->getModel() ) ) {
					return '';
				}

				return $adh->getModel()->getPermalink();
			}
		];
		$ret['tous_referents']                   = [
			'desc' => __( 'Nom des référents du contrat', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', array_unique( array_map(
					function ( $ref_id ) {
						$ref = AmapressUser::getBy( $ref_id );
						if ( empty( $ref ) ) {
							return '';
						}

						return $ref->getDisplayName();
					},
					$adh->getModel()->getReferentsIds()
				) ) );
			}
		];
		$ret['tous_referents_email']             = [
			'desc' => __( 'Nom des référents du contrat avec emails', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', array_unique( array_map(
					function ( $ref_id ) {
						$ref = AmapressUser::getBy( $ref_id );
						if ( empty( $ref ) ) {
							return '';
						}

						return $ref->getDisplayName() . '(' . $ref->getEmail() . ')';
					},
					$adh->getModel()->getReferentsIds()
				) ) );
			}
		];
		$ret['tous_referents_contacts']          = [
			'desc' => __( 'Nom des référents du contrat avec contacts', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', array_unique( array_map(
					function ( $ref_id ) {
						$ref = AmapressUser::getBy( $ref_id );
						if ( empty( $ref ) ) {
							return '';
						}

						return $ref->getDisplayName() . ' (' . $ref->getEmail() . '/' . $ref->getTelTo( 'both', false, false, '/' ) . ')';
					},
					$adh->getModel()->getReferentsIds()
				) ) );
			}
		];
		$ret['referents']                        = [
			'desc' => __( 'Nom des référents du contrat', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', array_unique( array_map(
					function ( $ref_id ) {
						$ref = AmapressUser::getBy( $ref_id );
						if ( empty( $ref ) ) {
							return '';
						}

						return $ref->getDisplayName();
					},
					$adh->getModel()->getReferentsIds()
				) ) );
			}
		];
		$ret['referents_email']                  = [
			'desc' => __( 'Nom des référents du contrat avec emails', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', array_unique( array_map(
					function ( $ref_id ) {
						$ref = AmapressUser::getBy( $ref_id );
						if ( empty( $ref ) ) {
							return '';
						}

						return $ref->getDisplayName() . '(' . $ref->getEmail() . ')';
					},
					$adh->getModel()->getReferentsIds()
				) ) );
			}
		];
		$ret['referents_contacts']               = [
			'desc' => __( 'Nom des référents du contrat avec contacts', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', array_unique( array_map(
					function ( $ref_id ) {
						$ref = AmapressUser::getBy( $ref_id );
						if ( empty( $ref ) ) {
							return '';
						}

						return $ref->getDisplayName() . ' (' . $ref->getEmail() . '/' . $ref->getTelTo( 'both', false, false, '/' ) . ')';
					},
					$adh->getModel()->getReferentsIds()
				) ) );
			}
		];
		$ret['date_debut'] = [
			'desc' => __( 'Date début du contrat (par ex, 22/09/2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'd/m/Y', $adh->getDate_debut() );
			}
		];
		$ret['date_fin'] = [
			'desc' => __( 'Date fin du contrat (par ex, 22/09/2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'd/m/Y', $adh->getDate_fin() );
			}
		];
		$ret['date_debut_lettre'] = [
			'desc' => __( 'Date début du contrat (par ex, 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'j F Y', $adh->getDate_debut() );
			}
		];
		$ret['date_fin_lettre'] = [
			'desc' => __( 'Date fin du contrat (par ex, 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'j F Y', $adh->getDate_fin() );
			}
		];
		$ret['date_debut_complete'] = [
			'desc' => __( 'Date début du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'l j F Y', $adh->getDate_debut() );
			}
		];
		$ret['date_fin_complete'] = [
			'desc' => __( 'Date fin du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'l j F Y', $adh->getDate_fin() );
			}
		];
		$ret['date_ouverture'] = [
			'desc' => __( 'Date d\'ouverture du contrat (par ex, 22/09/2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'd/m/Y', $adh->getDate_ouverture() );
			}
		];
		$ret['date_cloture'] = [
			'desc' => __( 'Date de clôture du contrat (par ex, 22/09/2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'd/m/Y', $adh->getDate_cloture() );
			}
		];
		$ret['date_ouverture_lettre'] = [
			'desc' => __( 'Date d\'ouverture du contrat (par ex, 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'j F Y', $adh->getDate_ouverture() );
			}
		];
		$ret['date_cloture_lettre'] = [
			'desc' => __( 'Date de clôture du contrat (par ex, 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'j F Y', $adh->getDate_cloture() );
			}
		];
		$ret['date_ouverture_complete'] = [
			'desc' => __( 'Date d\'ouverture du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'l j F Y', $adh->getDate_ouverture() );
			}
		];
		$ret['date_cloture_complete'] = [
			'desc' => __( 'Date de clôture du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'l j F Y', $adh->getDate_cloture() );
			}
		];
		$ret['lieux'] = [
			'desc' => __( 'Lieux de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getTitle();
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieu'] = [
			'desc' => __( 'Lieu de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getTitle();
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieux_court'] = [
			'desc' => __( 'Lieux de distribution (nom court)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getShortName();
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieu_court'] = [
			'desc' => __( 'Lieu de distribution (nom court)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getShortName();
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieu_heure_debut'] = [
			'desc' => __( 'Heure de début de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return date_i18n( 'H:i', $l->getHeure_debut() );
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieu_heure_fin'] = [
			'desc' => __( 'Heure de fin de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return date_i18n( 'H:i', $l->getHeure_fin() );
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieu_adresse'] = [
			'desc' => __( 'Adresse du lieu de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getFormattedAdresse();
				}, $adh->getLieux() ) );
			}
		];
		$ret['contrat_debut'] = [
			'desc' => __( 'Début du contrat (mois/année)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'm/Y', $adh->getDate_debut() );
			}
		];
		$ret['contrat_fin'] = [
			'desc' => __( 'Fin du contrat (mois/année)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'm/Y', $adh->getDate_fin() );
			}
		];
		$ret['contrat_debut_annee'] = [
			'desc' => __( 'Année de début du contrat', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'Y', $adh->getDate_debut() );
			}
		];
		$ret['contrat_fin_annee'] = [
			'desc' => __( 'Année de fin du contrat', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'Y', $adh->getDate_fin() );
			}
		];
		$ret['producteur.pseudo'] = [
			'desc' => __( 'Pseudo producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getUser()->nickname;
			}
		];
		$ret['producteur.nom_public'] = [
			'desc' => __( 'Nom public producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getUser()->display_name;
			}
		];
		$ret['producteur.nom'] = [
			'desc' => __( 'Nom producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getUser()->last_name;
			}
		];
		$ret['producteur.prenom'] = [
			'desc' => __( 'Prénom producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getUser()->first_name;
			}
		];
		$ret['producteur.ferme'] = [
			'desc' => __( 'Nom de la ferme producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getNomExploitation();
			}
		];
		$ret['producteur.ferme.adresse'] = [
			'desc' => __( 'Adresse de la ferme producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getAdresseExploitation();
			}
		];
		$ret['producteur.adresse'] = [
			'desc' => __( 'Adresse complète producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getFormattedAdresse();
			}
		];
		$ret['producteur.rue'] = [
			'desc' => __( 'Adresse producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getAdresse();
			}
		];
		$ret['producteur.code_postal'] = [
			'desc' => __( 'Code postal producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getCode_postal();
			}
		];
		$ret['producteur.ville'] = [
			'desc' => __( 'Ville producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getVille();
			}
		];
		$ret['producteur.tel'] = [
			'desc' => __( 'Téléphone producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getTelephone();
			}
		];
		$ret['producteur.email'] = [
			'desc' => __( 'Email producteur', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getProducteur()->getUser()->getEmail();
			}
		];
		$ret['nb_paiements'] = [
			'desc' => __( 'Nombre de chèques/règlements possibles', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', $adh->getPossiblePaiements() );
			}
		];
		$ret['dates_rattrapages'] = [
			'desc' => __( 'Description des dates de distribution de rattrapage', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', $adh->getFormattedRattrapages() );
			}
		];
		$ret['dates_rattrapages_list'] = [
			'desc' => __( 'Listes html des dates de distribution de rattrapage', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( '<br />', array_map( function ( $s ) {
					return '* ' . $s;
				}, $adh->getFormattedRattrapages() ) );
			}
		];
		$ret['prochaine_date_distrib_complete'] = [
			'desc' => __( 'Prochaine date de distribution complète', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				$date = from( $adh->getRemainingDates( $first_date_distrib ) )->firstOrDefault();
				if ( $date ) {
					return date_i18n( 'l j F Y', $date );
				} else {
					return __( 'Aucune', 'amapress' );
				}
			}
		];
		$ret['prochaine_date_distrib']           = [
			'desc' => __( 'Prochaine date de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				$date = from( $adh->getRemainingDates( $first_date_distrib ) )->firstOrDefault();
				if ( $date ) {
					return date_i18n( 'd/m/Y', $date );
				} else {
					return __( 'Aucune', 'amapress' );
				}
			}
		];
		$ret['mention_speciale']                 = [
			'desc' => __( 'Champ Mention spéciale du contrat', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getSpecialMention();
			}
		];
		$ret['paiements_ordre']                  = [
			'desc' => __( 'Ordre à indiquer sur les chèques', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return wp_unslash( $adh->getPaiementsOrdre() );
			}
		];
		$ret['paiements_mention']                = [
			'desc' => __( 'Mention pour les paiements', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return wp_strip_all_tags( html_entity_decode( wp_unslash( $adh->getPaiementsMention() ) ) );
			}
		];
		$ret['nb_dates']                         = [
			'desc' => __( 'Nombre de dates de distributions restantes', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return count( $adh->getRemainingDates( $first_date_distrib ) );
			}
		];
		$ret['nb_distributions']                 = [
			'desc' => __( 'Nombre de distributions restantes', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return $adh->getRemainingDatesWithFactors( $first_date_distrib );
			}
		];
		$ret['dates_distribution_par_mois']      = [
			'desc' => __( 'Dates de distributions regroupées par mois', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return implode( ' ; ', $adh->getFormattedDatesDistribMois( $first_date_distrib ) );
			}
		];
		$ret['option_paiements']                 = [
			'desc' => __( 'Option de paiement choisie', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
//				if ( ! $adh->getManage_Cheques() ) {
//					return wp_strip_all_tags( html_entity_decode( wp_unslash( $adh->getPaiementsMention() ) ) );
//				}
				$paiements = [];
				if ( $adh->getAllow_Cash() ) {
					$paiements[] = 'en espèces';
				}
				if ( $adh->getAllow_Transfer() ) {
					$paiements[] = 'par virement';
				}
				if ( $adh->getAllow_LocalMoney() ) {
					$paiements[] = 'en monnaie locale';
				}
				foreach ( $adh->getPossiblePaiements() as $nb_cheques ) {
					$paiements[] = "$nb_cheques chq." . ( $adh->getPayByMonth() ? ' (au mois)' : '' );
				}
				if ( $adh->getAllow_Prelevement() ) {
					foreach ( $adh->getPossiblePaiements() as $nb_cheques ) {
						$paiements[] = "$nb_cheques prélèvement(s)";
					}
				}

				return implode( ' ou ', $paiements );
			}
		];
		$ret['dates_distribution_par_mois_list'] = [
			'desc' => __( 'Liste html des dates de distributions regroupées par mois', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return implode( '<br />', array_map( function ( $s ) {
					return '* ' . $s;
				}, $adh->getFormattedDatesDistribMois( $first_date_distrib ) ) );
			}
		];
		$ret['premiere_date']                    = [
			'desc' => __( 'Première date de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return date_i18n( 'd/m/Y', from( $adh->getRemainingDates( $first_date_distrib ) )->firstOrDefault() );
			}
		];
		$ret['derniere_date']                    = [
			'desc' => __( 'Dernière date de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return date_i18n( 'd/m/Y', from( $adh->getRemainingDates( $first_date_distrib ) )->lastOrDefault() );
			}
		];
		$ret['dates_distribution']               = [
			'desc' => __( 'Liste des dates de distribution', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return implode( ', ', array_map( function ( $d ) {
					return date_i18n( 'd/m/Y', $d );
				}, $adh->getRemainingDates( $first_date_distrib ) ) );
			}
		];
		$ret['quantites_table']                  = [
			'desc' => __( 'Table des quantités', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				$columns   = [];
				$columns[] = array(
					'title' => __( 'Quantité', 'amapress' ),
					'data'  => 'quantite',
				);
				$columns[] = array(
					'title' => __( 'Description', 'amapress' ),
					'data'  => 'quantite_description',
				);
				$columns[] = array(
					'title' => __( 'Nombre de distributions', 'amapress' ),
					'data'  => 'quantite_nb_distrib',
				);
				$columns[] = array(
					'title' => __( 'Prix unitaire', 'amapress' ),
					'data'  => 'quantite_prix_unitaire',
				);
				if ( $adh->isPanierVariable() || $adh->isQuantiteVariable() ) {
					$columns[] = array(
						'title' => __( 'Unité', 'amapress' ),
						'data'  => 'quantite_unite',
					);
				}
				$lines = $adh->getQuantiteTables( $first_date_distrib );
				static $id = 1;

				return amapress_get_datatable( 'quant-table' . ( $id ++ ), $columns, $lines,
					array(
						'paging'       => false,
						'searching'    => false,
						'nowrap'       => false,
						'responsive'   => false,
						'init_as_html' => true,
					) );
			}
		];
		$ret['quantites_table_total']            = [
			'desc' => __( 'Table des quantités avec total', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				$columns   = [];
				$columns[] = array(
					'title' => __( 'Quantité', 'amapress' ),
					'data'  => 'quantite',
				);
				$columns[] = array(
					'title' => __( 'Description', 'amapress' ),
					'data'  => 'quantite_description',
				);
				$columns[] = array(
					'title' => __( 'Nombre de distributions', 'amapress' ),
					'data'  => 'quantite_nb_distrib',
				);
				$columns[] = array(
					'title' => __( 'Prix unitaire', 'amapress' ),
					'data'  => 'quantite_prix_unitaire',
				);
				if ( $adh->isPanierVariable() || $adh->isQuantiteVariable() ) {
					$columns[] = array(
						'title' => __( 'Unité', 'amapress' ),
						'data'  => 'quantite_unite',
					);
				}
				$columns[] = array(
					'title' => __( 'Total', 'amapress' ),
					'data'  => 'quantite_total',
				);
				$lines     = $adh->getQuantiteTables( $first_date_distrib );
				static $id = 1;

				return amapress_get_datatable( 'quant-table' . ( $id ++ ), $columns, $lines,
					array(
						'paging'       => false,
						'searching'    => false,
						'nowrap'       => false,
						'responsive'   => false,
						'init_as_html' => true,
					) );
			}
		];
		$ret['quantites_table_dates']            = [
			'desc' => __( 'Table des quantités avec dates spécifiques', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				$columns   = [];
				$columns[] = array(
					'title' => __( 'Quantité', 'amapress' ),
					'data'  => 'quantite',
				);
				$columns[] = array(
					'title' => __( 'Description', 'amapress' ),
					'data'  => 'quantite_description',
				);
				$columns[] = array(
					'title' => __( 'Nombre de distributions', 'amapress' ),
					'data'  => 'quantite_nb_distrib',
				);
				$columns[] = array(
					'title' => __( 'Dates de distributions', 'amapress' ),
					'data'  => 'quantite_dates_distrib',
				);
				$columns[] = array(
					'title' => __( 'Prix unitaire', 'amapress' ),
					'data'  => 'quantite_prix_unitaire',
				);
				if ( $adh->isPanierVariable() || $adh->isQuantiteVariable() ) {
					$columns[] = array(
						'title' => __( 'Unité', 'amapress' ),
						'data'  => 'quantite_unite',
					);
				}
				$lines = $adh->getQuantiteTables( $first_date_distrib );
				static $id = 1;

				return amapress_get_datatable( 'quant-table' . ( $id ++ ), $columns, $lines,
					array(
						'paging'       => false,
						'searching'    => false,
						'nowrap'       => false,
						'responsive'   => false,
						'init_as_html' => true,
					) );
			}
		];
		$ret['quantites_table_dates_total']      = [
			'desc' => __( 'Table des quantités avec total et dates spécifiques', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				$columns   = [];
				$columns[] = array(
					'title' => __( 'Quantité', 'amapress' ),
					'data'  => 'quantite',
				);
				$columns[] = array(
					'title' => __( 'Description', 'amapress' ),
					'data'  => 'quantite_description',
				);
				$columns[] = array(
					'title' => __( 'Nombre de distributions', 'amapress' ),
					'data'  => 'quantite_nb_distrib',
				);
				$columns[] = array(
					'title' => __( 'Dates de distributions', 'amapress' ),
					'data'  => 'quantite_dates_distrib',
				);
				$columns[] = array(
					'title' => __( 'Prix unitaire', 'amapress' ),
					'data'  => 'quantite_prix_unitaire',
				);
				if ( $adh->isPanierVariable() || $adh->isQuantiteVariable() ) {
					$columns[] = array(
						'title' => __( 'Unité', 'amapress' ),
						'data'  => 'quantite_unite',
					);
				}
				$columns[] = array(
					'title' => __( 'Total', 'amapress' ),
					'data'  => 'quantite_total',
				);
				$lines     = $adh->getQuantiteTables( $first_date_distrib );
				static $id = 1;

				return amapress_get_datatable( 'quant-table' . ( $id ++ ), $columns, $lines,
					array(
						'paging'       => false,
						'searching'    => false,
						'nowrap'       => false,
						'responsive'   => false,
						'init_as_html' => true,
					) );
			}
		];
		$ret['adherent'] = [
			'desc' => __( 'Prénom Nom adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.type'] = [
			'desc' => __( 'Type d\'adhérent (Principal, Co-adhérent...) (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.pseudo'] = [
			'desc' => __( 'Pseudo adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.nom_public'] = [
			'desc' => __( 'Nom public adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.nom'] = [
			'desc' => __( 'Nom adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.prenom'] = [
			'desc' => __( 'Prénom adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.adresse'] = [
			'desc' => __( 'Adresse adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.code_postal'] = [
			'desc' => __( 'Code postal adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.ville'] = [
			'desc' => __( 'Ville adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.rue'] = [
			'desc' => __( 'Rue (adresse) adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.tel'] = [
			'desc' => __( 'Téléphone adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['adherent.email'] = [
			'desc' => __( 'Email adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherents.noms'] = [
			'desc' => __( 'Liste des co-adhérents (Prénom, Nom) (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherents.contacts'] = [
			'desc' => __( 'Liste des co-adhérents (Prénom, Nom, Emails, Tel) (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent'] = [
			'desc' => __( 'Prénom Nom co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent.pseudo'] = [
			'desc' => __( 'Pseudo co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent.nom_public'] = [
			'desc' => __( 'Nom public co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent.nom'] = [
			'desc' => __( 'Nom co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent.prenom'] = [
			'desc' => __( 'Prénom co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent.adresse'] = [
			'desc' => __( 'Adresse co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent.tel'] = [
			'desc' => __( 'Téléphone co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];
		$ret['coadherent.email'] = [
			'desc' => __( 'Email co-adhérent (à remplir)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return '';
			}
		];

		$ret['nb_inscriptions'] = [
			'desc' => __( 'Nombre d\'adhérents', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getAdherentsCount();
			}
		];
		$ret['nb_parts']        = [
			'desc' => __( 'Nombre de parts (contrats avec Facteurs Quantités, par ex, Panier et Demi Panier)', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getAdherentsEquivalentQuantites();
			}
		];
		$ret['max_parts']       = [
			'desc' => __( 'Nombre de parts maximum', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getMax_adherents();
			}
		];
		$ret['dispo_parts']     = [
			'desc' => __( 'Nombre de parts disponibles', 'amapress' ),
			'func' => function ( AmapressContrat_instance $adh ) {
				$max = $adh->getMax_adherents();
				if ( $max <= 0 ) {
					return __( 'Pas de quota', 'amapress' );
				}
				if ( $adh->getMaxUseEquivalentQuant() ) {
					return $max - $adh->getAdherentsEquivalentQuantites();
				} else {
					return $max - $adh->getAdherentsCount();
				}
			}
		];

		return $ret;
	}

	/** @return array */
	public function getDatesByMonth() {
		$dates           = $this->getRemainingDates();
		$by_month_totals = [];
		foreach ( $dates as $date ) {
			$month = date_i18n( 'M', $date );
			if ( empty( $by_month_totals[ $month ] ) ) {
				$by_month_totals[ $month ] = 0;
			}
			$by_month_totals[ $month ] += 1;
		}

		return $by_month_totals;
	}

	public function getQuantiteTables( $first_date_distrib ) {
		$lines  = [];
		$quants = AmapressContrats::get_contrat_quantites( $this->ID );
		foreach ( $quants as $quant ) {
			$row                           = [];
			$remaining_dates               = $this->getRemainingDates( $first_date_distrib, $quant->ID );
			$remaining_dates_count         = count( $remaining_dates );
			$remaining_distrib             = $this->getRemainingDatesWithFactors( $first_date_distrib, $quant->ID, true );
			$remaining_distrib_sum         = $this->getRemainingDatesWithFactors( $first_date_distrib, $quant->ID );
			$row["quantite"]               = $quant->getTitle();
			$row["quantite_code"]          = $quant->getCode();
			$row["quantite_nb_dates"]      = $remaining_dates_count;
			$row["quantite_nb_distrib"]    = $remaining_distrib_sum;
			$row["quantite_dates_distrib"] = implode( ', ', array_map( function ( $d, $f ) {
				if ( abs( $f - 1.0 ) < 0.001 ) {
					return date_i18n( 'd/m/Y', $d );
				} else if ( abs( $f - 2.0 ) < 0.001 ) {
					return date_i18n( 'd/m/Y', $d ) . '(double)';
				} else {
					return date_i18n( 'd/m/Y', $d ) . '(' . $f . ')';
				}
			}, array_keys( $remaining_distrib ), array_values( $remaining_dates ) ) );
			$row["quantite_dates"]         = implode( ', ', array_map( function ( $d ) {
				return date_i18n( 'd/m/Y', $d );
			}, $remaining_dates ) );
			if ( $quant->getPrix_unitaire() > 0 ) {
				$row["quantite_total"] = Amapress::formatPrice( $quant->getPrix_unitaire() * $remaining_distrib_sum );
			} else {
				$row["quantite_total"] = $quant->getPrix_unitaireDisplay();
			}
			$row["quantite_prix_unitaire"] = $quant->getPrix_unitaireDisplay();
			$row["quantite_description"]   = $quant->getDescription();
			$row["quantite_unite"]         = $quant->getPriceUnitDisplay();
			$paiements                     = [];
			$amount                        = $quant->getPrix_unitaire() * $remaining_distrib_sum;
			if ( $this->getPayByMonth() ) {
				$by_months = $this->getDatesByMonth();
				if ( ! $this->getPayByMonthOnly() && in_array( 1, $this->getPossiblePaiements() ) ) {
					$paiements[] = sprintf( __( "1 chèque de %0.2f €", 'amapress' ), $amount );
				}
				if ( in_array( count( $by_months ), $this->getPossiblePaiements() ) ) {
					$paiements[] = implode( ' ; ', array_map( function ( $month, $month_count ) {
						return sprintf( __( "%s: 1 chèque pour %d distributions", 'amapress' ),
							$month,
							$month_count );
					}, array_keys( $by_months ), array_values( $by_months ) ) );
				}
			} else {
				foreach ( $this->getPossiblePaiements() as $nb_cheque ) {
					$ch = $this->getChequeOptionsForTotal( $nb_cheque, $amount );
					if ( ! isset( $ch['desc'] ) ) {
						continue;
					}
					$paiements[] = $ch['desc'];
				}
			}
			if ( $this->getAllow_Prelevement() ) {
				if ( $this->getPayByMonth() ) {
					$by_months = $this->getDatesByMonth();
					if ( ! $this->getPayByMonthOnly() && in_array( 1, $this->getPossiblePaiements() ) ) {
						$paiements[] = sprintf( __( "1 prélèvement de %0.2f €", 'amapress' ), $amount );
					}
					if ( in_array( count( $by_months ), $this->getPossiblePaiements() ) ) {
						$paiements[] = implode( ' ; ', array_map( function ( $month, $month_count ) {
							return sprintf( __( "%s: 1 prélèvement pour %d distributions", 'amapress' ),
								$month,
								$month_count );
						}, array_keys( $by_months ), array_values( $by_months ) ) );
					}
				} else {
					foreach ( $this->getPossiblePaiements() as $nb_cheque ) {
						$ch = $this->getChequeOptionsForTotal( $nb_cheque, $amount, 'prélèvement' );
						if ( ! isset( $ch['desc'] ) ) {
							continue;
						}
						$paiements[] = $ch['desc'];
					}
				}
			}
			if ( $this->getAllow_Cash() ) {
				$paiements[] = __( 'En espèces', 'amapress' );
			}
			if ( $this->getAllow_Transfer() ) {
				$paiements[] = __( 'Par virement', 'amapress' );
			}
			$row["quantite_paiements"] = '[] ' . implode( "<br />[] ", $paiements );
			$lines[]                   = $row;
		}

		return $lines;
	}

	public function getRemainingDates( $date = null, $quantite_id = null ) {
		if ( empty( $date ) ) {
			$date = amapress_time();
		}

		$dates      = $this->getListe_dates();
		$start_date = Amapress::start_of_day( $date );

		$dates = array_filter( $dates, function ( $d ) use ( $start_date, $quantite_id ) {
			$factor = $this->getDateFactor( $d, $quantite_id );
			if ( abs( $factor ) < 0.001 ) {
				return false;
			}

			return Amapress::start_of_day( $d ) >= $start_date;
		} );

		$max_contrat_months = $this->getMaxContratMonths();
		$end_date           = $max_contrat_months > 0 ? Amapress::add_a_month( $start_date, $max_contrat_months ) : null;
		if ( $end_date ) {
			$end_date = Amapress::end_of_day( $end_date );
			$dates    = array_filter( $dates, function ( $d ) use ( $end_date ) {
				return Amapress::start_of_day( $d ) <= $end_date;
			} );
		}

		return $dates;
	}

	public static function getPlaceholders( $context ) {
		$ret = [];

		foreach ( amapress_replace_mail_placeholders_help( '', false, false ) as $k => $v ) {
			$ret[ $k ] = $v;
		}
		foreach ( Amapress::getPlaceholdersHelpForProperties( self::getProperties() ) as $prop_name => $prop_desc ) {
			$ret[ $prop_name ] = $prop_desc;
		}
		if ( 'pres' == $context ) {
			foreach ( Amapress::getPlaceholdersHelpForProperties( self::getProperties() ) as $prop_name => $prop_desc ) {
				$ret[ 'total_' . $prop_name ] = '(Durée total) ' . $prop_desc;
			}
		}
		if ( 'paper' == $context ) {
			$ret["quantite"]                         = '(Tableau quantité, contrat simple) Libellé quantité avec facteur';
			$ret["quantite_simple"]                  = '(Tableau quantité, contrat simple) Libellé quantité';
			$ret["quantite_groupe"]                  = '(Tableau quantité, contrat simple) Groupe du libellé quantité';
			$ret["quantite_sans_groupe"]             = '(Tableau quantité, contrat simple) Libellé quantité sans groupe';
			$ret["quantite_code"]                    = '(Tableau quantité, contrat simple) Code quantité';
			$ret["quantite_nb_dates"]                = '(Tableau quantité, contrat simple) Nombre de dates de distribution restantes';
			$ret["quantite_nb_distrib"]              = '(Tableau quantité, contrat simple) Nombre de distribution restantes (rattrapages inclus)';
			$ret["quantite_dates_distrib"]           = '(Tableau quantité, contrat simple) Distribution restantes avec rattrapages';
			$ret["quantite_dates"]                   = '(Tableau quantité, contrat simple) Dates de distribution restantes';
			$ret["quantite_sous_total"]              = '(Tableau quantité, contrat simple) Prix pour la quantité choisie';
			$ret["quantite_total"]                   = '(Tableau quantité) Prix pour la quuantité choisie x nombre distrib';
			$ret["quantite_nombre"]                  = '(Tableau quantité, contrat simple) Facteur quantité choisi';
			$ret["quantite_prix_unitaire"]           = '(Tableau quantité, contrat simple) Prix à l\'unité';
			$ret["quantite_description"]             = '(Tableau quantité) Description de la quantité ; pour les paniers modulables : quantités livrées à la date donnée';
			$ret["quantite_description_no_price"]    = '(Tableau quantité, paniers modulables) : quantités livrées sans les prix à la date donnée';
			$ret["quantite_description_br"]          = '(Tableau quantité, paniers modulables) : quantités livrées à la date donnée avec retour à la ligne entre chaque';
			$ret["quantite_description_br_no_price"] = '(Tableau quantité, paniers modulables) : quantités livrées sans les prix à la date donnée avec retour à la ligne entre chaque';
			$ret["quantite_unite"]                   = '(Tableau quantité, contrat simple) Unité de la quantité';
			$ret["quantite_date"]                    = '(Tableau quantité, paniers modulables) : date de livraison';
			$ret["quantite_paiements"]               = '(Tableau quantité) Possibilités de paiements';

			$ret["quantite_details_date"]          = '(Tableau quantité détails) pour les paniers modulables : Date de distribution';
			$ret["quantite_details_total"]         = '(Tableau quantité détails) pour les paniers modulables : Prix pour la quuantité choisie';
			$ret["quantite_details_nombre"]        = '(Tableau quantité détails) pour les paniers modulables : Facteur quantité choisi';
			$ret["quantite_details_prix_unitaire"] = '(Tableau quantité détails) pour les paniers modulables : Prix à l\'unité';
			$ret["quantite_details_unite"]         = '(Tableau quantité détails) pour les paniers modulables : Unité de la quantité';
			$ret["quantite_details_groupe"]        = '(Tableau quantité détails) pour les paniers modulables : Groupe de la quantité';
			$ret["quantite_details_description"]   = '(Tableau quantité détails) pour les paniers modulables : Description de la quantité';
		}

		return $ret;
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $context = '', $show_toggler = true ) {
		$ret = self::getPlaceholders( $context );

		return Amapress::getPlaceholdersHelpTable( 'contrat_inst-placeholders', $ret,
			'du contrat', $additional_helps, false,
			'paper' == $context ? '${' : '%%', 'paper' == $context ? '}' : '%%',
			$show_toggler );
	}

	/** @return AmapressContrat_quantite[] */
	public function getContrat_quantites( $date = null ) {
		$res = [];
		foreach ( AmapressContrats::get_contrat_quantites( $this->ID ) as $contrat_quantite ) {
			if ( ! $contrat_quantite->isInDistributionDates( $date ) ) {
				continue;
			}
			$res[] = $contrat_quantite;
		}

		return $res;
	}

	public function getContrat_quantites_AsString(
		$date = null,
		$show_price_unit = false,
		$separator = ', ',
		$filter_grp_name = null
	) {
		if ( null !== $filter_grp_name ) {
			$filter_grp_name = trim( $filter_grp_name, '[]' );
		}
		if ( $this->isPanierVariable() || $this->isQuantiteVariable() ) {
			$quant_labels = array();
			foreach ( AmapressContrats::get_contrat_quantites( $this->ID ) as $contrat_quantite ) {
				if ( null !== $filter_grp_name && $contrat_quantite->getGroupName() != $filter_grp_name ) {
					continue;
				}
				if ( ! $contrat_quantite->isInDistributionDates( $date ) ) {
					continue;
				}
				$quant_labels[] = esc_html( '___ x ' . $contrat_quantite->getTitle() . ( $show_price_unit ? ' à ' . $contrat_quantite->getPrix_unitaireDisplay() : '' ) );
			}

			return implode( $separator, $quant_labels );
		} else {
			$quant_labels = array();
			foreach ( AmapressContrats::get_contrat_quantites( $this->ID ) as $contrat_quantite ) {
				if ( null !== $filter_grp_name && $contrat_quantite->getGroupName() != $filter_grp_name ) {
					continue;
				}
				if ( ! $contrat_quantite->isInDistributionDates( $date ) ) {
					continue;
				}
				$quant_labels[] = esc_html( $contrat_quantite->getTitle() . ( $show_price_unit ? ' à ' . $contrat_quantite->getPrix_unitaireDisplay() : '' ) );
			}

			return implode( $separator, $quant_labels );
		}
	}

	public function generateContratDoc( $date_first_distrib, $editable, $check_only = false ) {
		$out_filename   = $this->getContratDocFileName( $date_first_distrib );
		$model_filename = $this->getContratPapierModelDocFileName();
		if ( ! $check_only && empty( $model_filename ) ) {
			return '';
		}

		$placeholders = [];
		foreach ( amapress_replace_mail_placeholders_help( '', false, false ) as $k => $v ) {
			$prop_name                  = $k;
			$placeholders[ $prop_name ] = amapress_replace_mail_placeholders( "%%$prop_name%%", null );
		}
		foreach ( self::getProperties( $date_first_distrib ) as $prop_name => $prop_config ) {
			$placeholders[ $prop_name ] = call_user_func( $prop_config['func'], $this );
		}
		$placeholders['quantites']                    = '';
		$placeholders['inscription_admin_link']       = '';
		$placeholders['message']                      = '';
		$placeholders['quantites_prix']               = '';
		$placeholders['quantites_prix_unitaire']      = '';
		$placeholders['produits_paiements_livraison'] = '';
		$placeholders['don_distribution']             = '';
		$placeholders['don_distribution_nom']         = $this->getDon_DistributionLabel();
		$placeholders['don_distribution_desc']        = $this->getDon_DistributionDescription();
		$placeholders['don_total']                    = '';
		$placeholders['id']                           = '';
		$placeholders['total']                        = '';
		$placeholders['adherent']                     = '';
		$placeholders['adherent.type']                = '';
		$placeholders['adherent.pseudo']              = '';
		$placeholders['adherent.nom_public']          = '';
		$placeholders['adherent.nom']                 = '';
		$placeholders['adherent.prenom']              = '';
		$placeholders['adherent.adresse']             = '';
		$placeholders['adherent.code_postal']         = '';
		$placeholders['adherent.ville']               = '';
		$placeholders['adherent.rue']                 = '';
		$placeholders['adherent.tel']                 = '';
		$placeholders['adherent.email']               = '';
		$placeholders['cofoyers.noms']                = '';
		$placeholders['cofoyers.contacts']            = '';
		$placeholders['coadherents.noms']             = '';
		$placeholders['coadherents.contacts']         = '';
		$placeholders['touscoadherents.noms']         = '';
		$placeholders['touscoadherents.contacts']     = '';
		$placeholders['coadherent']                   = '';
		$placeholders['coadherent.pseudo']            = '';
		$placeholders['coadherent.nom_public']        = '';
		$placeholders['coadherent.nom']               = '';
		$placeholders['coadherent.prenom']            = '';
		$placeholders['coadherent.adresse']           = '';
		$placeholders['coadherent.tel']               = '';
		$placeholders['coadherent.email']             = '';

		$quants              = AmapressContrats::get_contrat_quantites( $this->ID );
		$i                   = 1;
		$ii                  = 1;
		$lines_count         = 0;
		$details_lines_count = 0;
		if ( $this->isPanierVariable() ) {
			$dates = $this->getRemainingDates( $date_first_distrib );
			if ( $check_only && empty( $dates ) ) {
				$dates = [ amapress_time() ];
			}
			foreach ( $dates as $date ) {
				$placeholders["quantite_date#$i"]                    = date_i18n( 'd/m/Y', $date );
				$placeholders["quantite_total#$i"]                   = str_repeat( chr( 160 ), 4 );
				$placeholders["quantite_description#$i"]             = $this->getContrat_quantites_AsString( $date, true );
				$placeholders["quantite_description_no_price#$i"]    = $this->getContrat_quantites_AsString( $date );
				$placeholders["quantite_description_br#$i"]          = $this->getContrat_quantites_AsString( $date, true, '<br />' );
				$placeholders["quantite_description_br_no_price#$i"] = $this->getContrat_quantites_AsString( $date, false, '<br />' );

				foreach ( $this->getContrat_quantites( $date ) as $contrat_quantite ) {
					$placeholders["quantite_details_date#$ii"]          = date_i18n( 'd/m/Y', $date );
					$placeholders["quantite_details_total#$ii"]         = str_repeat( chr( 160 ), 4 );
					$placeholders["quantite_details_nombre#$ii"]        = '';
					$placeholders["quantite_details_groupe#$ii"]        = $contrat_quantite->getGroupName();
					$placeholders["quantite_details_prix_unitaire#$ii"] = $contrat_quantite->getPrix_unitaireDisplay();
					$placeholders["quantite_details_unite#$ii"]         = $contrat_quantite->getPriceUnitDisplay();
					$placeholders["quantite_details_description#$ii"]   = $contrat_quantite->getTitle();

					$ii                  += 1;
					$details_lines_count += 1;
				}

				$i           += 1;
				$lines_count += 1;
			}
		} else {
			foreach ( $quants as $quant ) {
				$remaining_dates                         = $this->getRemainingDates( $date_first_distrib, $quant->ID );
				$nb_remaining_dates                      = count( $remaining_dates );
				$remaining_distrib                       = $this->getRemainingDatesWithFactors( $date_first_distrib, $quant->ID, true );
				$nb_remaining_distrib                    = $this->getRemainingDatesWithFactors( $date_first_distrib, $quant->ID );
				$placeholders["quantite#$i"]             = $quant->getTitle();
				$placeholders["quantite_simple#$i"]      = $quant->getTitle();
				$placeholders["quantite_groupe#$i"]      = $quant->getGroupName();
				$placeholders["quantite_sans_groupe#$i"] = $quant->getTitleWithoutGroup();
				$placeholders["quantite_code#$i"]        = $quant->getCode();
				$placeholders["quantite_nb_dates#$i"]    = $nb_remaining_dates;
				$placeholders["quantite_nb_distrib#$i"]  = $nb_remaining_distrib;
				if ( $this->isQuantiteVariable() ) {
					$placeholders["quantite_total#$i"] = '';
				} elseif ( $quant->getPrix_unitaire() > 0 ) {
					$placeholders["quantite_total#$i"] = Amapress::formatPrice( $quant->getPrix_unitaire() * $nb_remaining_distrib );
				} else {
					$placeholders["quantite_total#$i"] = $quant->getPrix_unitaireDisplay();
				}
				$placeholders["quantite_prix_unitaire#$i"] = $quant->getPrix_unitaireDisplay();
				$placeholders["quantite_description#$i"]   = $quant->getDescription();
				$placeholders["quantite_unite#$i"]         = $quant->getPriceUnitDisplay();
				$placeholders["quantite_dates_distrib#$i"] = implode( ', ', array_map( function ( $d, $f ) {
					if ( abs( $f - 1.0 ) < 0.001 ) {
						return date_i18n( 'd/m/Y', $d );
					} else if ( abs( $f - 2.0 ) < 0.001 ) {
						return date_i18n( 'd/m/Y', $d ) . '(double)';
					} else {
						return date_i18n( 'd/m/Y', $d ) . '(' . $f . ')';
					}
				}, array_keys( $remaining_distrib ), array_values( $remaining_dates ) ) );
				$placeholders["quantite_dates#$i"]         = implode( ', ', array_map( function ( $d ) {
					return date_i18n( 'd/m/Y', $d );
				}, $remaining_dates ) );
				$placeholders["quantite_sous_total#$i"]    = '';
				$placeholders["quantite_nombre#$i"]        = '';

				$i           += 1;
				$lines_count += 1;
			}
		}
		for ( $i = 1; $i <= 12; $i ++ ) {
			$placeholders["paiement_type#$i"]     = '';
			$placeholders["paiement_numero#$i"]   = '';
			$placeholders["paiement_emetteur#$i"] = '';
			$placeholders["paiement_banque#$i"]   = '';
			$placeholders["paiement_montant#$i"]  = '';
			$placeholders["paiement_date#$i"]     = '';
			$placeholders["paiement_status#$i"]   = '';

			$placeholders["paiement_{$i}_type"]     = $placeholders["paiement_type#$i"];
			$placeholders["paiement_{$i}_numero"]   = $placeholders["paiement_numero#$i"];
			$placeholders["paiement_{$i}_emetteur"] = $placeholders["paiement_emetteur#$i"];
			$placeholders["paiement_{$i}_banque"]   = $placeholders["paiement_banque#$i"];
			$placeholders["paiement_{$i}_montant"]  = $placeholders["paiement_montant#$i"];
			$placeholders["paiement_{$i}_date"]     = $placeholders["paiement_date#$i"];
			$placeholders["paiement_{$i}_status"]   = $placeholders["paiement_status#$i"];
		}

		$ii        = 1;
		$grp_names = [];
		foreach ( $quants as $quant ) {
			$grp_name = $quant->getGroupName();
			if ( ! in_array( $grp_name, $grp_names ) ) {
				$grp_names[] = $grp_name;
			}
		}
		foreach ( $grp_names as $grp_name ) {
			$placeholders["groupe_nom#$ii"]                              = $grp_name;
			$placeholders["groupe_quantite_description#$ii"]             = $this->getContrat_quantites_AsString( null, true, ', ', $grp_name );
			$placeholders["groupe_quantite_description_no_price#$ii"]    = $this->getContrat_quantites_AsString( null, false, ', ', $grp_name );
			$placeholders["groupe_quantite_description_br#$ii"]          = '* ' . $this->getContrat_quantites_AsString( null, true, '<br/>* ', $grp_name );
			$placeholders["groupe_quantite_description_br_no_price#$ii"] = '* ' . $this->getContrat_quantites_AsString( null, false, '<br/>* ', $grp_name );
			if ( $this->isPanierVariable() ) {
				$placeholders["groupe_date#$ii"] = '';
			}
			$placeholders["groupe_total#$ii"]  = '';
			$placeholders["groupe_nombre#$ii"] = '';

			$ii += 1;
		}

		if ( $check_only ) {
			return $placeholders;
		}

		\PhpOffice\PhpWord\Settings::setTempDir( Amapress::getTempDir() );
		$templateProcessor = new Phptemplate_withnewline( $model_filename );

		try {
			$templateProcessor->cloneRow( 'quantite_date', $lines_count );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'quantite', $lines_count );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				try {
					$templateProcessor->cloneRow( 'quantite_simple', $lines_count );
				} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					try {
						$templateProcessor->cloneRow( 'quantite_description', $lines_count );
					} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					}
				}
			}
		}

		$lines_count = 0;
		foreach ( $this->getPossiblePaiements() as $nb ) {
			$lines_count = $nb > $lines_count ? $nb : $lines_count;
		}
		try {
			$templateProcessor->cloneRow( 'paiement_montant', $lines_count );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'paiement_numero', $lines_count );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				try {
					$templateProcessor->cloneRow( 'paiement_emetteur', $lines_count );
				} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					try {
						$templateProcessor->cloneRow( 'paiement_banque', $lines_count );
					} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					}
				}
			}
		}

		try {
			$templateProcessor->cloneRow( 'groupe_date', 0 );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'groupe_nom', 0 );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			}
		}

		try {
			$templateProcessor->cloneRow( 'quantite_details_date', 0 );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'quantite_details_nombre', 0 );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				try {
					$templateProcessor->cloneRow( 'quantite_details_description', 0 );
				} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				}
			}
		}

		foreach ( $placeholders as $k => $v ) {
			$templateProcessor->setValue( $k, $v );
		}

		$templateProcessor->saveAs( $out_filename );

		if ( ! $editable ) {
			$out_filename = Amapress::convertToPDF( $out_filename );
		}

		return $out_filename;
	}


	public function getMinEngagement() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_min_engagement' );
	}

	public function getCustomRepartitions() {
		return $this->getCustomAsArray( 'amapress_contrat_instance_pmt_reps' );
	}

	public function hasCustomMultiplePaiements() {
		return ! empty( $this->getCustomRepartitions() );
	}

	public function getCustomMultipleRepartitionPercent( $nb_paiements, $paiement_index ) {
		if ( $nb_paiements <= 0 ) {
			return 0;
		}

		$reps = $this->getCustomRepartitions();
		if ( isset( $reps[ $nb_paiements ] ) ) {
			$percents = array_map( function ( $p ) {
				return intval( trim( $p, " \t\r\n%" ) );
			}, explode( ',', $reps[ $nb_paiements ] ) );

			return isset( $percents[ $paiement_index ] ) ? $percents[ $paiement_index ] : 100.0 / $nb_paiements;
		} else {
			return 100.0 / $nb_paiements;
		}
	}

	/** @return array */
	public function getTotalAmountByCustom( $nb_paiements, $total ) {
		$amounts   = [];
		$remaining = $total;
		for ( $i = 0; $i < $nb_paiements - 1; $i ++ ) {
			$percent       = $this->getCustomMultipleRepartitionPercent( $nb_paiements, $i );
			$cheque_amount = floor( $total * $percent / 100.0 );
			$amounts[]     = $cheque_amount;
			$remaining     -= $cheque_amount;
		}
		$amounts[] = $remaining;

		return $amounts;
	}

	public function getChequeOptionsForTotal( $nb_cheque, $total, $type_paiement = 'chèque' ) {
		if ( $this->hasCustomMultiplePaiements() ) {
			$desc = implode( ' ; ', array_map( function ( $amount ) use ( $type_paiement ) {
				return sprintf( __( '1 %s de %0.2f €', 'amapress' ), $type_paiement, $amount );
			}, $this->getTotalAmountByCustom( $nb_cheque, $total ) ) );

			return [
				'desc'          => $desc,
				'main_amount'   => 0,
				'remain_amount' => 0,
			];
		}
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
			$option      = sprintf( __( '%s %s(s) de %0.2f €', 'amapress' ), $nb, $type_paiement, $cheque_main_amount );
		} else if ( $last_cheque == 0 ) {
			$nb     = 1;
			$option = sprintf( __( '1 %s de %0.2f €', 'amapress' ), $type_paiement, $cheque_main_amount );
		} else {
			$nb     = $nb_cheque - 1;
			$option = sprintf( __( '%d %s(s) de %0.2f € et 1 %s de %0.2f €', 'amapress' ), $nb, $type_paiement, $type_paiement, $cheque_main_amount, $last_cheque );
		}

		return [
			'desc'          => $option,
			'main_amount'   => $cheque_main_amount,
			'remain_amount' => $last_cheque,
		];
	}

	/** @return AmapressAdhesion[] */
	public function getAdhesionsForUser( $user_id = null, $date = null, $ignore_renouv_delta = false ) {
		return AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, $this->ID, $date, $ignore_renouv_delta );
	}

	/**
	 * @return int[]
	 */
	public static function getContratInstanceIdsForUser( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_contrat_instances_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$ads = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, $contrat_id, $date, $ignore_renouv_delta );
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
		unset( $meta['amapress_contrat_instance_archives_infos'] );
		unset( $meta['amapress_contrat_instance_archived'] );
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

	public function getRemainingDatesWithFactors( $start_date, $quantite_id = null, $return_array = false ) {
		$dates              = $this->getListe_dates();
		$dates              = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return $d >= $start_date;
		} );
		$max_contrat_months = $this->getMaxContratMonths();
		$end_date           = $max_contrat_months > 0 ? Amapress::add_a_month( $start_date, $max_contrat_months ) : null;
		if ( $end_date ) {
			$end_date = Amapress::end_of_day( $end_date );
			$dates    = array_filter( $dates, function ( $d ) use ( $end_date ) {
				return Amapress::start_of_day( $d ) <= $end_date;
			} );
		}
		if ( $return_array ) {
			$ret = [];
			foreach ( $dates as $d ) {
				$ret[ $d ] = $this->getDateFactor( $d, $quantite_id );
			}

			return $ret;
		} else {
			$dates_factors = 0;
			foreach ( $dates as $d ) {
				$dates_factors += $this->getDateFactor( $d, $quantite_id );
			}

			return $dates_factors;
		}
	}

	public function getInscriptionsStats() {
		$stored = $this->getCustom( 'stats' );
		if ( ! empty( $stored ) ) {
			return $stored;
		}

		$inscriptions = AmapressContrats::get_all_adhesions( $this->getID() );

		$lieux = [];
		foreach ( $inscriptions as $inscription ) {
			$k = strval( $inscription->getLieuId() );
			if ( ! isset( $lieux[ $k ] ) ) {
				$lieux[ $k ] = $inscription->getLieu()->getShortName();
			}
		}
		$quantites = AmapressContrats::get_contrat_quantites( $this->getID() );

		$columns   = [];
		$columns[] = array(
			'title' => __( 'Date', 'amapress' ),
			'data'  => 'date',
		);
		if ( count( $lieux ) > 1 ) {
			foreach ( $lieux as $lieu_key => $lieu_name ) {
				$lieu_id   = intval( $lieu_key );
				$columns[] = array(
					'title' => $lieu_name . ' - Inscriptions',
					'data'  => 'lieu_' . $lieu_id . '_inscriptions',
				);
				foreach ( $quantites as $quantite ) {
					$columns[] = array(
						'title' => $lieu_name . ' - ' . $quantite->getTitle(),
						'data'  => 'lieu_' . $lieu_id . '_q' . $quantite->getID(),
					);
				}
			}
		}
		$columns[] = array(
			'title' => __( 'Inscriptions', 'amapress' ),
			'data'  => 'lieu_all_inscriptions',
		);
		foreach ( $quantites as $quantite ) {
			$columns[] = array(
				'title' => $quantite->getTitle(),
				'data'  => 'lieu_all_q' . $quantite->getID(),
			);
		}

		$lines = [];
		foreach ( $this->getListe_dates() as $date ) {
			$date_inscriptions = array_filter( $inscriptions,
				function ( $inscription ) use ( $date ) {
					/** @var AmapressAdhesion $inscription */
					return Amapress::start_of_day( $inscription->getDate_debut() ) <= Amapress::start_of_day( $date )
					       && Amapress::end_of_day( $date ) <= Amapress::end_of_day( $inscription->getDate_fin() );
				} );
			$line              = [
				'date'                  => date_i18n( 'd/m/Y', $date ),
				'date_int'              => $date,
				'lieu_all_inscriptions' => count( $date_inscriptions )
			];


			if ( count( $lieux ) > 1 ) {
				foreach ( $lieux as $lieu_key => $lieu_name ) {
					$lieu_id                                      = intval( $lieu_key );
					$lieu_inscriptions                            = array_filter( $date_inscriptions,
						function ( $inscription ) use ( $lieu_id ) {
							/** @var AmapressAdhesion $inscription */
							return $inscription->getLieuId() == $lieu_id;
						} );
					$line[ 'lieu_' . $lieu_id . '_inscriptions' ] = count( $lieu_inscriptions );

					$lieu_price = 0;
					foreach ( $quantites as $quantite ) {
						$quantite_sum                                           = from( $lieu_inscriptions )->sum(
							function ( $inscription ) use ( $quantite, $date ) {
								/** @var AmapressAdhesion $inscription */
								foreach ( $inscription->getContrat_quantites( $date ) as $q ) {
									if ( $q->getId() == $quantite->ID ) {
										return $q->getFactor();
									}
								}

								return 0;
							} );
						$quantite_price                                         = from( $lieu_inscriptions )->sum(
							function ( $inscription ) use ( $quantite, $date ) {
								/** @var AmapressAdhesion $inscription */
								foreach ( $inscription->getContrat_quantites( $date ) as $q ) {
									if ( $q->getId() == $quantite->ID ) {
										return $q->getPrice();
									}
								}

								return 0;
							} );
						$lieu_price                                             += $quantite_price;
						$line[ 'lieu_' . $lieu_id . '_q' . $quantite->getID() ] = $quantite_sum;
						$line[ 'lieu_' . $lieu_id . '_p' . $quantite->getID() ] = $quantite_price;
					}
					$line[ 'lieu_' . $lieu_id . '_p' ] = $lieu_price;
				}
			}

			$all_price = 0;
			foreach ( $quantites as $quantite ) {
				$quantite_sum                              = from( $date_inscriptions )->sum(
					function ( $inscription ) use ( $quantite, $date ) {
						/** @var AmapressAdhesion $inscription */
						foreach ( $inscription->getContrat_quantites( $date ) as $q ) {
							if ( $q->getId() == $quantite->ID ) {
								return $q->getFactor();
							}
						}

						return 0;
					} );
				$quantite_price                            = from( $date_inscriptions )->sum(
					function ( $inscription ) use ( $quantite, $date ) {
						/** @var AmapressAdhesion $inscription */
						foreach ( $inscription->getContrat_quantites( $date ) as $q ) {
							if ( $q->getId() == $quantite->ID ) {
								return $q->getPrice();
							}
						}

						return 0;
					} );
				$all_price                                 += $quantite_price;
				$line[ 'lieu_all_q' . $quantite->getID() ] = $quantite_sum;
				$line[ 'lieu_all_p' . $quantite->getID() ] = $quantite_price;
			}
			$line['lieu_all_p'] = $all_price;

			$lines[] = $line;
		}

		$ret = [
			'columns' => $columns,
			'lines'   => $lines,
		];
		if ( amapress_time() > Amapress::end_of_day( $this->getDate_fin() ) ) {
			$this->setCustom( 'stats', $ret );
		}

		return $ret;
	}

	public function canBeArchived() {
		return ! $this->isArchived() && amapress_time() > Amapress::add_a_month(
				Amapress::end_of_day( $this->getDate_fin() ), Amapress::getOption( 'archive_months', 3 ) );
	}

	public function isArchived() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_archived', 0 );
	}

	public function archive() {
		if ( ! $this->canBeArchived() ) {
			return false;
		}

		//compute inscriptions stats
		echo '<p>' . __( 'Stockage des statistiques', 'amapress' ) . '</p>';
		$this->getInscriptionsStats();

		$archives_infos = [];
		//extract inscriptions xlsx
		echo '<p>' . __( 'Stockage de l\'excel des inscriptions', 'amapress' ) . '</p>';
		$objPHPExcel = AmapressExport_Posts::generate_phpexcel_sheet( 'post_type=amps_adhesion&amapress_contrat_inst=' . $this->ID,
			null, sprintf( __( 'Contrat %s - Inscriptions', 'amapress' ), $this->getTitle() ) );
		$filename    = sprintf( __( 'contrat-%s-inscriptions.xlsx', 'amapress' ), $this->ID );
		$objWriter   = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( Amapress::getArchivesDir() . '/' . $filename );
		$archives_infos['file_inscriptions'] = $filename;
		foreach ( [ 'adherents_date', 'group_date' ] as $xl_name ) {
			$xlsx = amapress_get_contrat_quantite_xlsx( $this->ID, $xl_name );
			Amapress::createXLSXFromDatatableAsFile(
				$xlsx['columns'], $xlsx['data'],
				Amapress::getArchivesDir() . '/' . $xlsx['filename'], $xlsx['title']
			);
			$archives_infos["file_$xl_name"] = $xlsx['filename'];
		}
		if ( $this->isPanierVariable() ) {
			$xl = amapress_get_contrat_column_quantite( $this->ID );
			Amapress::createXLSXFromPHPExcelAsFile( $xl['xl'],
				Amapress::getArchivesDir() . '/' . $xl['filename'] );
			$archives_infos['file_adherents_columns'] = $xl['filename'];
		}
		//extract paiements xlsx
		echo '<p>' . __( 'Stockage des excel des règlements', 'amapress' ) . '</p>';
		foreach ( ( count( $this->getLieuxIds() ) > 1 ? array_merge( [ 0 ], $this->getLieuxIds() ) : $this->getLieuxIds() ) as $lieu_id ) {
			$lieu        = ( 0 == $lieu_id ? null : AmapressLieu_distribution::getBy( $lieu_id ) );
			$html        = amapress_get_paiement_table_by_dates(
				$this->ID,
				$lieu_id,
				array(
					'show_next_distrib'       => false,
					'show_contact_producteur' => false,
					'for_pdf'                 => true,
				) );
			$objPHPExcel = AMapress::createXLSXFromHtml( $html,
				sprintf( __( 'Contrat %s - Règlements - %s', 'amapress' ), $this->getTitle(), 0 == $lieu_id ? __( 'Tous les lieux', 'amapress' ) : $lieu->getTitle() ) );
			$filename    = sprintf( __( 'contrat-%s-reglements-%s.xlsx', 'amapress' ), $this->ID, 0 == $lieu_id ? 'tous' : strtolower( sanitize_file_name( $lieu->getLieuTitle() ) ) );
			$objWriter   = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
			$objWriter->save( Amapress::getArchivesDir() . '/' . $filename );
			$archives_infos["file_cheques_$lieu_id"] = $filename;
		}

		$inscriptions                         = AmapressContrats::get_all_adhesions( $this->getID() );
		$archives_infos['count_inscriptions'] = count( $inscriptions );

		echo '<p>' . __( 'Stockage des infos du contrat pour archive', 'amapress' ) . '</p>';
		$this->setCustom( 'amapress_contrat_instance_archives_infos', $archives_infos );

		echo '<p>' . __( 'Archivage des inscriptions et règlements', 'amapress' ) . '</p>';
		global $wpdb;
		//start transaction
		$wpdb->query( 'START TRANSACTION' );
		//delete related inscription and paiements
		foreach ( $inscriptions as $inscription ) {
			wp_delete_post( $inscription->ID, true );
		}
		//mark archived
		$this->setCustom( 'amapress_contrat_instance_archived', 1 );
		//end transaction
		$wpdb->query( 'COMMIT' );
	}

	public function getArchiveInfo() {
		$res = $this->getCustomAsArray( 'amapress_contrat_instance_archives_infos' );
		if ( empty( $res ) ) {
			$res = [ 'count_inscriptions' => 0 ];
		}

		return $res;
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

	/** @return int */
	public function getContrat_instanceId() {
		return $this->getCustomAsInt( 'amapress_contrat_quantite_contrat_instance' );
	}

//    public function getMax_Commandable()
//    {
//        return $this->getCustomAsFloat('amapress_contrat_quantite_max_quantite');
//    }

	public function getPrix_unitaireDisplay() {
		$price_unit = $this->getPrix_unitaire();
		if ( abs( $price_unit ) < 0.001 ) {
			return __( 'Prix au poids', 'amapress' );
		} else {
			return Amapress::formatPrice( $price_unit );
		}
	}

	private $group_name = null;

	public function getGroupName() {
		if ( null === $this->group_name ) {
			$this->group_name = '';
			$has_group        = preg_match( '/^\s*\[([^\]]+)\]/', $this->getTitle(), $matches );
			if ( $has_group && isset( $matches[1] ) ) {
				$this->group_name = $matches[1];
			}
		}

		return $this->group_name;
	}

	private $title_wo_group_name = null;

	public function getTitleWithoutGroup() {
		if ( null === $this->title_wo_group_name ) {
			$this->title_wo_group_name = $this->getTitle();
			$has_group                 = preg_match( '/^\s*\[[^\]]+\](.+)/', $this->getTitle(), $matches );
			if ( $has_group && isset( $matches[1] ) ) {
				$this->title_wo_group_name = $matches[1];
			}
		}

		return $this->title_wo_group_name;
	}

	public function getGroupMultiple() {
		$res = $this->getCustomAsInt( 'amapress_contrat_quantite_grp_mult', 1 );
		if ( $res < 1 ) {
			$res = 1;
		}

		return $res;
	}

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
		$cnt = $this->getContent();
		if ( empty( $cnt ) ) {
			$cnt = $this->getCustom( 'amapress_contrat_quantite_description' );
		}

		return stripslashes( $cnt );
	}

	public function getMaxAdherents() {
		return $this->getCustomAsInt( 'amapress_contrat_quantite_max_adhs' );
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
				return __( 'le kg', 'amapress' );
			case 'l':
				return __( 'le litre', 'amapress' );
			case 'unit':
				return __( 'l\'unité', 'amapress' );
			case 'cm':
				return __( 'le centimètre', 'amapress' );
			case 'm':
				return __( 'le mètre', 'amapress' );
			default:
				return $this->getPriceUnit();
		}
	}

	public function getQuantiteConfig() {
		return $this->getCustom( 'amapress_contrat_quantite_quantite_config' );
	}

	public function getQuantiteOptions() {
		$confs = $this->getQuantiteConfig();
		if ( empty( $confs ) ) {
			if ( $this->getContrat_instance()->isPanierVariable() ) {
				$confs = '0>25:1';
			} else {
				$confs = '0>10:1';
			}
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
					if ( ( $i > 0 ) || ( $this->getContrat_instance()->isPanierVariable() && $i >= 0 ) ) {
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
		$value, $one_unit_display = '1', $no_unit_suffix = '', $no_unit_fraction_suffix = ''
	) {
		if ( $this->getPriceUnit() == 'kg' ) {
			if ( $value < 1 ) {
				return sprintf( __( '%d', 'amapress' ), (int) ( $value * 1000.0 ) ) . 'g';
			} else {
				return round( $value, 2 ) . 'kg';
			}
		} else if ( $this->getPriceUnit() == 'l' ) {
			if ( $value < 1 ) {
				return sprintf( __( '%d', 'amapress' ), (int) ( $value * 1000.0 ) ) . 'ml';
			} else {
				return round( $value, 2 ) . __( 'L', 'amapress' );
			}
		} else if ( $this->getPriceUnit() == 'm' ) {
			if ( $value < 1 ) {
				return sprintf( __( '%d', 'amapress' ), (int) ( $value * 100.0 ) ) . 'cm';
			} else {
				return round( $value, 2 ) . 'm';
			}
		} else if ( $this->getPriceUnit() == 'cm' ) {
			if ( $value < 1 ) {
				return sprintf( __( '%d', 'amapress' ), (int) ( $value * 10.0 ) ) . 'mm';
			} else {
				return round( $value, 2 ) . 'cm';
			}
		} else {
			if ( abs( $value - 0.25 ) < 0.001 ) {
				return '1/4' . $no_unit_fraction_suffix;
			} else if ( abs( $value - 0.333 ) < 0.001 ) {
				return '2/3' . $no_unit_fraction_suffix;
			} else if ( abs( $value - 0.5 ) < 0.001 ) {
				return '1/2' . $no_unit_fraction_suffix;
			} else if ( abs( $value - 0.666 ) < 0.001 ) {
				return '2/3' . $no_unit_fraction_suffix;
			} else if ( abs( $value - 0.75 ) < 0.001 ) {
				return '3/4' . $no_unit_fraction_suffix;
			} else if ( abs( $value - 1 ) < 0.001 ) {
				return $one_unit_display;
			} else {
				return round( $value, 2 ) . $no_unit_suffix;
			}
		}
	}

	public
	function getFormattedTitle(
		$factor,
		$as_html = false
	) {
		if ( $factor != 1 ) {
			$title     = $this->getTitle();
			$grp_name  = '';
			$has_group = preg_match( '/^\s*(\[[^\]]+\])(.+)/', $title, $matches );
			if ( $has_group ) {
				if ( isset( $matches[1] ) ) {
					$grp_name = $matches[1];
					$title    = $matches[2];
				}
			}
			if ( ! empty( $grp_name ) ) {
				if ( $as_html ) {
					return sprintf( __( '%s <strong>%s</strong> x %s', 'amapress' ), esc_html( $grp_name ), esc_html( $factor ), esc_html( $title ) );
				} else {
					return sprintf( __( '%s %s x %s', 'amapress' ), $grp_name, $factor, $title );
				}
			} else {
				if ( $as_html ) {
					return sprintf( __( '<strong>%s</strong> x %s', 'amapress' ), esc_html( $factor ), esc_html( $title ) );
				} else {
					return sprintf( __( '%s x %s', 'amapress' ), $factor, $title );
				}
			}
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

	public function getSpecificDistributionDates() {
		return $this->getCustomAsDateArray( 'amapress_contrat_quantite_liste_dates' );
	}

	private $quantite_liste_dates = null;

	public function isInDistributionDates( $dist_date ) {
		if ( null === $this->quantite_liste_dates ) {
			$this->quantite_liste_dates = array_map( function ( $d ) {
				return Amapress::start_of_day( $d );
			}, $this->getSpecificDistributionDates() );
		}

		if ( empty( $this->quantite_liste_dates ) ) {
			return true;
		}

		return in_array( Amapress::start_of_day( $dist_date ), $this->quantite_liste_dates );
	}

	public static function cleanOrphans() {
		global $wpdb;
		$orphans = $wpdb->get_col( "SELECT $wpdb->posts.ID
FROM $wpdb->posts 
INNER JOIN $wpdb->postmeta
ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
WHERE 1=1 
AND ( ( $wpdb->postmeta.meta_key = 'amapress_contrat_quantite_contrat_instance'
AND CAST($wpdb->postmeta.meta_value as SIGNED) NOT IN (
SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = '" . AmapressContrat_instance::INTERNAL_POST_TYPE . "'
) ) )
AND $wpdb->posts.post_type = '" . AmapressContrat_quantite::INTERNAL_POST_TYPE . "'
GROUP BY $wpdb->posts.ID" );

		$wpdb->query( 'START TRANSACTION' );
		foreach ( $orphans as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$wpdb->query( 'COMMIT' );

		$count = count( $orphans );
		if ( $count > 0 ) {
			return sprintf( __( '%s quantités orphelines nettoyées', 'amapress' ), $count );
		} else {
			return __( 'Aucun quantité orpheline', 'amapress' );
		}
	}
}
