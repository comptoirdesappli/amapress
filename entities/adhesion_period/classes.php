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

	public function getName() {
		return wp_unslash( $this->getCustom( 'amapress_adhesion_period_name' ) );
	}

	public function getOnlineDescription() {
		return wp_unslash( $this->getCustom( 'amapress_adhesion_period_online_desc' ) );
	}

	public function getCustomCheck( $index ) {
		return wp_unslash( $this->getCustom( 'amapress_adhesion_period_custom_check' . $index ) );
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

		return Phptemplate_withnewline::getPlaceholderStatus( $model_file, $placeholders, __( 'Bulletin d\'adhésion', 'amapress' ) );
	}

	public function getMontantReseau( $intermittent = false ) {
		return $this->getCustomAsFloat(
			$intermittent ? 'amapress_adhesion_period_mnt_reseau_inter' : 'amapress_adhesion_period_mnt_reseau' );
	}

	public function getMontantAmap( $intermittent = false ) {
		return $this->getCustomAsFloat(
			$intermittent ? 'amapress_adhesion_period_mnt_amap_inter' : 'amapress_adhesion_period_mnt_amap' );
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

	public function getAllowAmapienInputPaiementsDetails() {
		return $this->getCustom( 'amapress_adhesion_period_pmt_user_input', 1 );
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
					array(
						'relation' => 'OR',
						array(
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
						),
						array(
							'relation' => 'AND',
							array(
								'key'     => 'amapress_adhesion_period_date_debut',
								'value'   => Amapress::start_of_day( $date ),
								'compare' => '>=',
								'type'    => 'NUMERIC'
							),
						),
					),
				),
			);
			$res   = array_map( function ( $p ) {
				return AmapressAdhesionPeriod::getBy( $p );
			}, get_posts( $query ) );
			usort( $res, function ( $pa, $pb ) {
				/** @var AmapressAdhesionPeriod $pa */
				/** @var AmapressAdhesionPeriod $pb */
				return $pa->getDate_debut() < $pb->getDate_debut() ? - 1 : 1;
			} );
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

	public function canBeArchived() {
		return ! $this->isArchived() && amapress_time() > Amapress::add_a_month(
				Amapress::end_of_day( $this->getDate_fin() ), Amapress::getOption( 'archive_months', 3 ) );
	}

	public function isArchived() {
		return $this->getCustomAsInt( 'amapress_adhesion_period_archived', 0 );
	}

	public function archive() {
		if ( ! $this->canBeArchived() ) {
			return false;
		}

		$archives_infos = [];
		//extract inscriptions xlsx
		echo '<p>' . __( 'Stockage de l\'excel des adhésions', 'amapress' ) . '</p>';
		$objPHPExcel = AmapressExport_Posts::generate_phpexcel_sheet( 'post_type=amps_adh_pmt&amapress_adhesion_period=' . $this->ID,
			null, sprintf( __( '%s - Adhésions', 'amapress' ), $this->getTitle() ) );
		$filename    = 'periode-' . $this->ID . '-adhesions.xlsx';
		$objWriter   = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( Amapress::getArchivesDir() . '/' . $filename );
		$archives_infos['file_adhesions'] = $filename;

		//extract paiements xlsx
		echo '<p>' . __( 'Stockage des excel des règlements', 'amapress' ) . '</p>';
		$_GET['page']     = 'adhesion_paiements';
		$_GET['adh_date'] = Amapress::add_days( $this->getDate_debut(), 1 );
		$objPHPExcel      = AmapressExport_Users::generate_phpexcel_sheet( 'amapress_adhesion=all',
			null, sprintf( __( '%s - Réglements', 'amapress' ), $this->getTitle() ) );
		$filename         = 'periode-' . $this->ID . '-paiements.xlsx';
		$objWriter        = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( Amapress::getArchivesDir() . '/' . $filename );
		$archives_infos['file_paiements'] = $filename;

		$adhesions                         = get_posts( 'post_type=amps_adh_pmt&amapress_adhesion_period=' . $this->ID );
		$archives_infos['count_adhesions'] = count( $adhesions );

		echo '<p>' . __( 'Stockage des infos du contrat pour archive', 'amapress' ) . '</p>';
		$this->setCustom( 'amapress_adhesion_period_archives_infos', $archives_infos );

		echo '<p>' . __( 'Archivage des adhésions et règlements', 'amapress' ) . '</p>';
		global $wpdb;
		//start transaction
		$wpdb->query( 'START TRANSACTION' );
		//delete related adhesion paiements
		foreach ( $adhesions as $adhesion ) {
			wp_delete_post( $adhesion->ID, true );
		}
		//mark archived
		$this->setCustom( 'amapress_adhesion_period_archived', 1 );
		//end transaction
		$wpdb->query( 'COMMIT' );
	}

	public function getArchiveInfo() {
		$res = $this->getCustomAsArray( 'amapress_adhesion_period_archives_infos' );
		if ( empty( $res ) ) {
			$res = [ 'count_adhesions' => 0 ];
		}

		return $res;
	}

	/** @return AmapressAdhesionPeriod[] */
	public static function getAll() {
		return array_map(
			function ( $p ) {
				return AmapressAdhesionPeriod::getBy( $p );
			},
			get_posts(
				array(
					'post_type'      => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
				)
			)
		);
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret                         = [];
			$ret['nom']                  = [
				'desc' => __( 'Nom de la période d\'adhésion (par ex, saison 15)', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return date_i18n( 'd/m/Y', $adh->getName() );
				}
			];
			$ret['date_debut']           = [
				'desc' => __( 'Date début de la période d\'adhésion (par ex, 01/09/2018)', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate_debut() );
				}
			];
			$ret['date_fin']             = [
				'desc' => __( 'Date fin de période d\'adhésion (par ex, 31/08/2019)', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate_fin() );
				}
			];
			$ret['date_debut_annee']     = [
				'desc' => __( 'Année de début de période d\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return date_i18n( 'Y', $adh->getDate_debut() );
				}
			];
			$ret['date_fin_annee']       = [
				'desc' => __( 'Année de fin de période d\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return date_i18n( 'Y', $adh->getDate_fin() );
				}
			];
			$ret['montant_amap']         = [
				'desc' => __( 'Montant versé à l\'AMAP (Amapiens)', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return Amapress::formatPrice( $adh->getMontantAmap( false ) );
				}
			];
			$ret['montant_reseau']       = [
				'desc' => __( 'Montant versé au réseau de l\'AMAP (Amapiens)', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return Amapress::formatPrice( $adh->getMontantReseau( false ) );
				}
			];
			$ret['inter_montant_amap']   = [
				'desc' => __( 'Montant versé à l\'AMAP (Intermittents)', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return Amapress::formatPrice( $adh->getMontantAmap( true ) );
				}
			];
			$ret['inter_montant_reseau'] = [
				'desc' => __( 'Montant versé au réseau de l\'AMAP (Intermittents)', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return Amapress::formatPrice( $adh->getMontantReseau( true ) );
				}
			];
			$ret['tresoriers']           = [
				'desc' => __( 'Nom des référents de l\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName();
						},
						get_users( "role=tresorier" )
					) ) );
				}
			];
			$ret['tresoriers_emails']    = [
				'desc' => __( 'Nom des trésoriers avec emails', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName() . '(' . $ref->getEmail() . ')';
						},
						get_users( "role=tresorier" )
					) ) );
				}
			];
			$ret['paiements_mention']    = [
				'desc' => __( 'Mention pour les paiements', 'amapress' ),
				'func' => function ( AmapressAdhesionPeriod $adh ) {
					return wp_strip_all_tags( html_entity_decode( wp_unslash( $adh->getPaymentInfo() ) ) );
				}
			];
			self::$properties            = $ret;
		}

		return self::$properties;
	}

	public static function getPlaceholders() {
		$ret = [];

		foreach ( amapress_replace_mail_placeholders_help( '', false, false ) as $k => $v ) {
			$ret[ $k ] = $v;
		}
		foreach ( Amapress::getPlaceholdersHelpForProperties( self::getProperties() ) as $prop_name => $prop_desc ) {
			$ret[ $prop_name ] = $prop_desc;
		}

		return $ret;
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_word = false, $show_toggler = true ) {
		$ret = self::getPlaceholders();

		return Amapress::getPlaceholdersHelpTable( 'adhesion-period-placeholders', $ret,
			null, $additional_helps, false,
			$for_word ? '${' : '%%', $for_word ? '}' : '%%',
			$show_toggler );
	}
}


