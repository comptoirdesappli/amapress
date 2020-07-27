<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesionPeriod extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_adh_per';
	const POST_TYPE = 'adhesion_period';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAdhesionPeriod
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAdhesionPeriod' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressAdhesionPeriod( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	public function getDate_debut() {
		return $this->getCustom( 'amapress_adhesion_period_date_debut' );
	}

	public function getDate_fin() {
		return $this->getCustom( 'amapress_adhesion_period_date_fin' );
	}

	public function getOnlineDescription() {
		return wp_unslash( $this->getCustom( 'amapress_adhesion_period_online_desc' ) );
	}

	public function getPaymentInfo() {
		return wp_unslash( $this->getCustom( 'amapress_adhesion_period_pmt_info' ) );
	}

	public function getWordModelId() {
		return $this->getCustomAsInt( 'amapress_adhesion_period_word_model' );
	}

	public function getModelDocFileName() {
		if ( defined( 'AMAPRESS_DEMO_MODE' ) ) {
			return AMAPRESS__PLUGIN_DIR . 'templates/bulletin_adhesion_generique.docx';
		}

		return get_attached_file( $this->getWordModelId(), true );
	}

	public function getModelDocStatus() {
		$model_file   = $this->getModelDocFileName();
		$placeholders = AmapressAdhesion_paiement::getPlaceholders();

		return Phptemplate_withnewline::getPlaceholderStatus( $model_file, $placeholders, 'Bulletin d\'adhésion' );
	}

	public function getMontantReseau() {
		return $this->getCustomAsFloat( 'amapress_adhesion_period_mnt_reseau' );
	}

	public function getMontantAmap() {
		return $this->getCustomAsFloat( 'amapress_adhesion_period_mnt_amap' );
	}

	public function getAllow_Cheque() {
		return $this->getCustom( 'amapress_adhesion_period_allow_chq', 1 );
	}

	public function getAllow_Cash() {
		return $this->getCustom( 'amapress_adhesion_period_allow_cash', 0 );
	}

	public function getAllow_LocalMoney() {
		return $this->getCustom( 'amapress_adhesion_period_allow_locmon', 0 );
	}

	public function getAllow_Transfer() {
		return $this->getCustom( 'amapress_adhesion_period_allow_bktrfr', 0 );
	}

	/**
	 * @return AmapressAdhesionPeriod
	 */
	public static function getCurrent( $date = null ) {
		$key = "amapress_AmapressAdhesionPeriod_getCurrent_{$date}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			if ( $date == null ) {
				$date = amapress_time();
			}
			$query = array(
				'post_type'      => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_period_date_debut',
						'value'   => Amapress::start_of_day( $date ),
						'compare' => '<=',
						'type'    => 'NUMERIC'
					),
					array(
						'key'     => 'amapress_adhesion_period_date_fin',
						'value'   => Amapress::start_of_day( $date ),
						'compare' => '>=',
						'type'    => 'NUMERIC'
					),
				)
			);
			$res   = array_map( function ( $p ) {
				return AmapressAdhesionPeriod::getBy( $p );
			}, get_posts( $query ) );
			if ( count( $res ) > 0 ) {
				$res = array_shift( $res );
			} else {
				$res = null;
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	public function clonePeriod( $as_draft = true ) {
		$add_weeks = Amapress::datediffInWeeks( $this->getDate_debut(), $this->getDate_fin() );
		$meta      = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}

		$date_debut = Amapress::add_a_week( $this->getDate_debut(), $add_weeks );
		$date_fin   = Amapress::add_a_week( $this->getDate_fin(), $add_weeks );

		$meta['amapress_adhesion_period_date_debut'] = $date_debut;
		$meta['amapress_adhesion_period_date_fin']   = $date_fin;

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => self::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => $as_draft ? 'draft' : 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		return self::getBy( $new_id );
	}
}


