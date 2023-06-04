<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesion_paiement extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_adh_pmt';
	const POST_TYPE = 'adhesion_paiement';
	const PAIEMENT_TAXONOMY = 'amps_paiement_category';
	const NOT_RECEIVED = 'not_received';
	const RECEIVED = 'received';
	const BANK = 'bank';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAdhesion_paiement
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAdhesion_paiement' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressAdhesion_paiement( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	/** @return AmapressUser */
	public function getUser() {
		return $this->getCustomAsEntity( 'amapress_adhesion_paiement_user', 'AmapressUser' );
	}

	public function getUserId() {
		return $this->getCustomAsInt( 'amapress_adhesion_paiement_user' );
	}

	/** @return AmapressAdhesionPeriod */
	public function getPeriod() {
		return $this->getCustomAsEntity( 'amapress_adhesion_paiement_period', 'AmapressAdhesionPeriod' );
	}

	public function getPeriodId() {
		return $this->getCustomAsInt( 'amapress_adhesion_paiement_period' );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getDate() {
		return $this->getCustom( 'amapress_adhesion_paiement_date' );
	}

	public function getCustomCheck( $num ) {
		return $this->getCustom( 'amapress_adhesion_paiement_custom_check' . $num );
	}

	public function setStatus( $status ) {
		$this->setCustom( 'amapress_adhesion_paiement_status', $status );
		delete_transient( 'amps_adhpmt_to_confirm' );
	}

	public function setCustomCheck( $num, $value ) {
		$this->setCustom( 'amapress_adhesion_paiement_custom_check' . $num, $value );
	}

	/** @return string */
	public function getMainPaiementType() {
		return $this->getCustomAsString( 'amapress_adhesion_paiement_pmt_type', 'chq' );
	}

	public function getMainPaiementTypeFormatted() {
		return Amapress::formatPaymentType( $this->getMainPaiementType() );
	}

	public function isForIntermittent() {
		return $this->getCustom( 'amapress_adhesion_paiement_intermittent', 0 );
	}

	public function getAdhesionType() {
		if ( $this->isForIntermittent() ) {
			return __( 'Intermittent', 'amapress' );
		} else {
			return __( 'Amapien', 'amapress' );
		}
	}

	public function getStatusDisplay() {
		$this->ensure_init();
		switch ( $this->getStatus() ) {

			case 'not_received':
				return __( 'Non reçu', 'amapress' );
			case 'received':
				return __( 'Reçu', 'amapress' );
			case 'bank':
				return __( 'Encaissé', 'amapress' );
			default:
				return $this->getStatus();
		}
	}

	public function isNotReceived() {
		return self::NOT_RECEIVED == $this->getStatus();
	}

	public function getStatus() {
		return $this->getCustomAsString( 'amapress_adhesion_paiement_status', self::NOT_RECEIVED );
	}

	public function getNumero() {
		return $this->getCustomAsString( 'amapress_adhesion_paiement_numero' );
	}

	public function getBanque() {
		return $this->getCustomAsString( 'amapress_adhesion_paiement_banque' );
	}

	public function getEmetteur() {
		return $this->getCustomAsString( 'amapress_adhesion_paiement_emetteur' );
	}

	public function getAmount( $type = null ) {
		$this->ensure_init();

		if ( $type ) {
			$specific_amount = $this->getCustomAsArray( 'amapress_adhesion_paiement_repartition' );
			if ( ! empty( $specific_amount ) ) {
				$tax_id = Amapress::resolve_tax_id( $type, self::PAIEMENT_TAXONOMY );
				if ( isset( $specific_amount[ $tax_id ] ) ) {
					return $specific_amount[ $tax_id ];
				}
			}

			return 0;
		}

		return $this->getCustomAsFloat( 'amapress_adhesion_paiement_amount' );
	}

	/** @return AmapressAmapien_paiement[] */
	public static function get_next_paiements( $user_id = null, $date = null, $order = 'NONE' ) {
		if ( ! amapress_is_user_logged_in() ) {
			return [];
		}

		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}
		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_adhesion_paiement_date',
					'value'   => Amapress::add_days( $date, - 15 ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				),
				array(
					'key'     => 'amapress_adhesion_paiement_user',
					'value'   => $user_id,
					'compare' => '=',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return AmapressAmapien_paiement[] */
	public static function get_paiements( $start_date = null, $end_date = null, $order = 'NONE' ) {
		if ( ! $start_date ) {
			$start_date = Amapress::start_of_day( amapress_time() );
		}
		if ( ! $end_date ) {
			$end_date = Amapress::end_of_week( amapress_time() );
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_contrat_paiement_date',
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
			$price  = Amapress::formatPrice( $this->getAmount(), true );
			$date   = $this->getDate();
			$period = $this->getPeriod();
			if ( $period ) {
				$desc  = sprintf( __( 'Règlement de l\'adhésion "%s" d\'un montant de %s', 'amapress' ),
					$period->getTitle(), $price );
				$desc  .= sprintf( __( "\nType: %s\nEtat: %s\nNuméro: %s\nMontant: %s", 'amapress' ),
					$this->getMainPaiementTypeFormatted(),
					$this->getStatusDisplay(),
					( ! empty( $this->getBanque() ) ? $this->getBanque() . ' - ' : '' ) .
					( ! empty( $this->getEmetteur() ) ? $this->getEmetteur() . ' - ' : '' ) .
					( ! empty( $this->getNumero() ) ? $this->getNumero() : __( '-non renseigné-', 'amapress' ) ),
					$price
				);
				$lieu  = $this->getLieu();
				$lieux = array_filter( Amapress::get_lieux(), function ( $l ) {
					return $l->isPrincipal();
				} );
				if ( 1 == count( $lieux ) ) {
					$lieu = array_shift( $lieux );
				}
				if ( empty( $lieu ) ) {
					$adhesions = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id );
					if ( ! empty( $adhesions ) && isset( $adhesions[0] ) ) {
						$lieu = $adhesions[0]->getLieu();
					}
				}

				if ( $lieu ) {
					$ret[] = new Amapress_EventEntry( array(
						'ev_id'    => "upmt-{$this->ID}",
						'date'     => Amapress::start_of_day( $date ),
						'date_end' => Amapress::end_of_day( $date ),
						'type'     => 'user-paiement adhesion-paiement',
						'category' => __( 'Paiements', 'amapress' ),
						'label'    => sprintf( __( 'Paiement de l\'adhésion %s - %s', 'amapress' ),
							get_bloginfo( 'name' ),
							$price ),
						'class'    => "agenda-user-paiement",
						'lieu'     => $lieu,
						'priority' => 0,
						'icon'     => 'flaticon-business',
						'alt'      => $desc,
						'href'     => Amapress::get_mes_contrats_page_href()
					) );
				}
			}
		}

		return $ret;
	}

	public static function getAllActiveAndFutureByUserId( $date = null ) {
		$key = "amapress_AmapressAdhesionPaiement_getAllActiveAndFutureByUserId_{$date}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$adh_per_ids = array_map( function ( $p ) {
				return $p->ID;
			}, AmapressAdhesionPeriod::getAllCurrent() );
			$res         = array_group_by( array_map(
				function ( $p ) {
					return new AmapressAdhesion_paiement( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'relation' => 'OR',
								array(
									'key'     => 'amapress_adhesion_paiement_period',
									'value'   => amapress_prepare_in( $adh_per_ids ),
									'compare' => 'IN',
									'type'    => 'NUMERIC',
								),
								array(
									'key'     => 'amapress_adhesion_paiement_date',
									'compare' => '>=',
									'value'   => Amapress::start_of_day( amapress_time() ),
								),
							),
						),
					)
				) ),
				function ( $p ) {
					/** @var AmapressAdhesion_paiement $p */
					return $p->getUserId();
				}
			);
			wp_cache_set( $key, $res );
		}

		return $res;
	}

/** @return AmapressAdhesion_paiement[] */
	public static function getAllActiveByUserId( $date = null, $adhesion_period_id = null ) {
		$key = "amapress_AmapressAdhesionPaiement_getAllActiveByUserId_{$date}_{$adhesion_period_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$period_ids = $adhesion_period_id ?
				[ $adhesion_period_id ] :
				array_map( function ( $p ) {
					return $p->ID;
				}, AmapressAdhesionPeriod::getAllCurrent( $date ) );
			$res        = array_group_by( array_map(
				function ( $p ) {
					return new AmapressAdhesion_paiement( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
//							'relation' => 'OR',
							array(
								'key'     => 'amapress_adhesion_paiement_period',
								'value'   => amapress_prepare_in( $period_ids ),
								'compare' => 'IN',
								'type'    => 'NUMERIC',
							),
//							array(
//								'key'     => 'amapress_adhesion_paiement_period',
//								'compare' => 'NOT EXISTS',
//							),
						),
					)
				) ),
				function ( $p ) {
					/** @var AmapressAdhesion_paiement $p */
					return $p->getUserId();
				}
			);
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/** @return AmapressAdhesion_paiement[] */
	public static function getAllForUserId( $user_id ) {
		$key = "amapress_AmapressAdhesionPaiement_getAllForUserId_{$user_id}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = array_map(
				function ( $p ) {
					return new AmapressAdhesion_paiement( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'key'     => 'amapress_adhesion_paiement_user',
								'value'   => $user_id,
								'compare' => '=',
							),
						),
					)
				) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/** @return AmapressAdhesion_paiement[] */
	public static function hasHelloAssoId( $id ) {
		return ! empty(
		get_posts(
			array(
				'post_type'      => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_paiement_numero',
						'value'   => $id,
						'compare' => '=',
					),
					array(
						'key'     => 'amapress_adhesion_paiement_pmt_type',
						'value'   => 'hla',
						'compare' => '=',
					),
				),
			)
		)
		);
	}

	public static function hadUserAnyValidated( $user_id ) {
		foreach ( self::getAllForUserId( $user_id ) as $adh ) {
			if ( ! $adh->isNotReceived() ) {
				return true;
			}
		}

		return false;
	}

	/** @return AmapressAdhesion_paiement */
	public static function getForUser( $user_id, $date_or_period = null, $create = false ) {
		/** @var AmapressAdhesionPeriod $date_period */
		$date_period = null;
		$date        = $date_or_period;
		if ( is_a( $date_or_period, 'AmapressAdhesionPeriod' ) ) {
			$date_period = $date_or_period;
			$date        = $date_period->getDate_debut();
		}

		$adhs = self::getAllActiveByUserId( $date, $date_period ? $date_period->ID : null );
		if ( empty( $adhs[ $user_id ] ) ) {
			$user_ids = AmapressContrats::get_related_users( $user_id,
				true, null, null, true, false,
				! Amapress::toBool( Amapress::getOption( 'coadh_self_adh' ) )
			);
			foreach ( $user_ids as $rel_user_id ) {
				if ( ! empty( $adhs[ $rel_user_id ] ) ) {
					$adhs[ $user_id ] = $adhs[ $rel_user_id ];
				}
			}
		}
		if ( empty( $adhs[ $user_id ] ) ) {
			if ( ! $create ) {
				return null;
			}
			$adh_period = $date_period ? $date_period : AmapressAdhesionPeriod::getCurrent( $date );
			if ( empty( $adh_period ) ) {
				return null;
			}
			$my_post          = array(
				'post_type'    => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
				'post_content' => '',
				'post_status'  => 'publish',
				'meta_input'   => array(
					'amapress_adhesion_paiement_user'   => $user_id,
					'amapress_adhesion_paiement_period' => $adh_period->ID,
					'amapress_adhesion_paiement_date'   => amapress_time(),
					'amapress_adhesion_paiement_status' => 'not_received',
				),
			);
			$adh_pmt_id       = wp_insert_post( $my_post );
			$adhs[ $user_id ] = [ AmapressAdhesion_paiement::getBy( $adh_pmt_id ) ];
		}

		$adhs[ $user_id ] = array_values( $adhs[ $user_id ] );

		return $adhs[ $user_id ][0];
	}

	/** @return AmapressAdhesion_paiement */
	public static function createFakeForUser( $user_id, $date = null ) {
		$adhs = AmapressAdhesion_paiement::getAllActiveByUserId( $date );
		if ( empty( $adhs[ $user_id ] ) ) {
			$adh_period = AmapressAdhesionPeriod::getCurrent( $date );
			if ( empty( $adh_period ) ) {
				return null;
			}
			$adh                                              = new AmapressAdhesion_paiement( 0 );
			$adh->custom['amapress_adhesion_paiement_user']   = $user_id;
			$adh->custom['amapress_adhesion_paiement_period'] = $adh_period->ID;
			$adh->custom['amapress_adhesion_paiement_date']   = amapress_time();
			$adh->custom['amapress_adhesion_paiement_status'] = 'not_received';

			return $adh;
		}

		$adhs[ $user_id ] = array_values( $adhs[ $user_id ] );

		return $adhs[ $user_id ][0];
	}

	public function getBulletinDocDocStatus() {
		$model_file   = $this->getBulletinDocFileName();
		$placeholders = $this->generateBulletinDoc( false, true );

		return Phptemplate_withnewline::getPlaceholderStatus( $model_file, $placeholders, __( 'Bulletin adhésion', 'amapress' ) );
	}

	public function getBulletinDocFileName() {
		if ( ! $this->getUser() ) {
			return '';
		}
		$model_filename = $this->getPeriod()->getModelDocFileName();
		$ext            = strpos( $model_filename, '.docx' ) !== false ? '.docx' : '.odt';

		return trailingslashit( Amapress::getContratDir() ) . sanitize_file_name(
				'bulletin-adhesion-' . $this->ID . '-' . $this->getUser()->getSortableDisplayName() . '-' . date_i18n( 'Y-m-d', $this->getPeriod()->getDate_debut() ) . $ext );
	}

	public function generateBulletinDoc( $editable, $check_only = false ) {
		$out_filename   = $this->getBulletinDocFileName();
		$model_filename = $this->getPeriod()->getModelDocFileName();
		if ( ! $check_only && empty( $model_filename ) ) {
			return '';
		}

		$placeholders = [];
		foreach ( amapress_replace_mail_placeholders_help( '', false, false ) as $k => $v ) {
			$prop_name                  = $k;
			$placeholders[ $prop_name ] = amapress_replace_mail_placeholders( "%%$prop_name%%", null );
		}
		foreach ( self::getProperties() as $prop_name => $prop_config ) {
			$placeholders[ $prop_name ] = call_user_func( $prop_config['func'], $this );
		}

		if ( $check_only ) {
			return $placeholders;
		}

		\PhpOffice\PhpWord\Settings::setTempDir( Amapress::getTempDir() );
		$templateProcessor = new Phptemplate_withnewline( $model_filename );

		foreach ( $placeholders as $k => $v ) {
			$templateProcessor->setValue( $k, $v );
		}

		$templateProcessor->saveAs( $out_filename );

		if ( ! $editable ) {
			$out_filename = Amapress::convertToPDF( $out_filename );
		}

		return $out_filename;
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

		return Amapress::getPlaceholdersHelpTable( 'adhesion-placeholders', $ret,
			'de l\'adhésion', $additional_helps, false,
			$for_word ? '${' : '%%', $for_word ? '}' : '%%',
			$show_toggler );
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret                             = [];
			$ret['nom']                      = [
				'desc' => __( 'Nom de la période d\'adhésion (par ex, saison 15)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getPeriod()->getName() );
				}
			];
			$ret['date_debut']               = [
				'desc' => __( 'Date début de la période d\'adhésion (par ex, 01/09/2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getPeriod()->getDate_debut() );
				}
			];
			$ret['date_fin']                 = [
				'desc' => __( 'Date fin de période d\'adhésion (par ex, 31/08/2019)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getPeriod()->getDate_fin() );
				}
			];
			$ret['date_debut_annee']         = [
				'desc' => __( 'Année de début de période d\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'Y', $adh->getPeriod()->getDate_debut() );
				}
			];
			$ret['date_fin_annee']           = [
				'desc' => __( 'Année de fin de période d\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'Y', $adh->getPeriod()->getDate_fin() );
				}
			];
			$ret['paiement_date']            = [
				'desc' => __( 'Date du paiement/adhésion à l\'AMAP', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate() );
				}
			];
			$ret['type_adhesion']            = [
				'desc' => __( 'Type d\'adhésion (Amapien ou intermittent)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getAdhesionType();
				}
			];
			$ret['montant_amap']             = [
				'desc' => __( 'Montant versé à l\'AMAP', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getPeriod()->getMontantAmap( $adh->isForIntermittent() ) );
				}
			];
			$ret['montant_reseau']           = [
				'desc' => __( 'Montant versé au réseau de l\'AMAP', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getPeriod()->getMontantReseau( $adh->isForIntermittent() ) );
				}
			];
			$ret['tresoriers']               = [
				'desc' => __( 'Nom des référents de l\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
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
			$ret['tresoriers_emails']        = [
				'desc' => __( 'Nom des trésoriers avec emails', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
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
			$ret['adherent']                 = [
				'desc' => __( 'Prénom Nom adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getDisplayName();
				}
			];
			$ret['adherent.type']            = [
				'desc' => __( 'Type d\'adhérent (Principal, Co-adhérent...)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getAdherentTypeDisplay();
				}
			];
			$ret['adherent.pseudo']          = [
				'desc' => __( 'Pseudo adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->nickname;
				}
			];
			$ret['adherent.nom_public']      = [
				'desc' => __( 'Nom public adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->display_name;
				}
			];
			$ret['adherent.nom']             = [
				'desc' => __( 'Nom adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->last_name;
				}
			];
			$ret['adherent.prenom']          = [
				'desc' => __( 'Prénom adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->first_name;
				}
			];
			$ret['adherent.adresse']         = [
				'desc' => __( 'Adresse adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getFormattedAdresse();
				}
			];
			$ret['adherent.code_postal']     = [
				'desc' => __( 'Code postal adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCode_postal();
				}
			];
			$ret['adherent.ville']           = [
				'desc' => __( 'Ville adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getVille();
				}
			];
			$ret['adherent.rue']             = [
				'desc' => __( 'Rue (adresse) adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getAdresse();
				}
			];
			$ret['adherent.tel']             = [
				'desc' => __( 'Téléphone adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getTelephone();
				}
			];
			$ret['adherent.email']           = [
				'desc' => __( 'Email adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getEmail();
				}
			];
			$ret['cofoyers.noms']            = [
				'desc' => __( 'Liste des membres du foyer (Prénom, Nom)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList( false, false, true, null, false );
				}
			];
			$ret['cofoyers.contacts']        = [
				'desc' => __( 'Liste des membres du foyer (Prénom, Nom, Emails, Tel)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList( true, false, true, null, false );
				}
			];
			$ret['coadherents.noms']         = [
				'desc' => __( 'Liste des co-adhérents (non membres du foyer) (Prénom, Nom)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList( false, false, false );
				}
			];
			$ret['coadherents.contacts']     = [
				'desc' => __( 'Liste des co-adhérents (non membres du foyer) (Prénom, Nom, Emails, Tel)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList( true, false, false );
				}
			];
			$ret['touscoadherents.noms']     = [
				'desc' => __( 'Liste tous les co-adhérents/membres du foyer (Prénom, Nom)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList( false );
				}
			];
			$ret['touscoadherents.contacts'] = [
				'desc' => __( 'Liste de tous les co-adhérents/membres du foyer (Prénom, Nom, Emails, Tel)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList( true );
				}
			];
			$ret['coadherent']               = [
				'desc' => __( 'Prénom Nom co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getDisplayName();
				}
			];
			$ret['coadherent.pseudo']        = [
				'desc' => __( 'Pseudo co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->nickname;
				}
			];
			$ret['coadherent.nom_public']    = [
				'desc' => __( 'Nom public co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->display_name;
				}
			];
			$ret['coadherent.nom']           = [
				'desc' => __( 'Nom co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->last_name;
				}
			];
			$ret['coadherent.prenom']        = [
				'desc' => __( 'Prénom co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->first_name;
				}
			];
			$ret['coadherent.adresse']       = [
				'desc' => __( 'Adresse co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getFormattedAdresse();
				}
			];
			$ret['coadherent.tel']           = [
				'desc' => __( 'Téléphone co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getTelephone();
				}
			];
			$ret['coadherent.email']         = [
				'desc' => __( 'Email co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getEmail();
				}
			];
			$taxes                           = get_categories( array(
				'orderby'    => 'name',
				'order'      => 'ASC',
				'taxonomy'   => 'amps_paiement_category',
				'hide_empty' => false,
			) );
			/** @var WP_Term $tax */
			foreach ( $taxes as $tax ) {
				$tax_id                             = $tax->term_id;
				$ret[ 'montant_cat_' . $tax->slug ] = [
					'desc' => sprintf( __( 'Montant relatif à %s', 'amapress' ), $tax->name ),
					'func' => function ( AmapressAdhesion_paiement $adh ) use ( $tax_id ) {
						return Amapress::formatPrice( $adh->getAmount( $tax_id ) );
					}
				];
			}
			$ret['total']             = [
				'desc' => __( 'Total de l\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getAmount() );
				}
			];
			$ret['montant']           = [
				'desc' => __( 'Total de l\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getAmount() );
				}
			];
			$ret['montant_helloasso'] = [
				'desc' => __( 'Total de l\'adhésion (HelloAsso)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getHelloAssoAmount() );
				}
			];
			$ret['paiement_numero']   = [
				'desc' => __( 'Numéro du chèque', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getNumero();
				}
			];
			$ret['paiement_banque']   = [
				'desc' => __( 'Banque du chèque', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getBanque();
				}
			];
			$ret['paiement_emetteur'] = [
				'desc' => __( 'Emetteur du chèque', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getEmetteur();
				}
			];
			$ret['paiements_mention'] = [
				'desc' => __( 'Mention pour les paiements', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return wp_strip_all_tags( html_entity_decode( wp_unslash( $adh->getPeriod()->getPaymentInfo() ) ) );
				}
			];
			$ret['option_paiements']  = [
				'desc' => __( 'Option de paiement choisie', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					if ( 'esp' == $adh->getMainPaiementType() ) {
						return __( 'En espèces', 'amapress' );
					}
					if ( 'vir' == $adh->getMainPaiementType() ) {
						return __( 'Par virement', 'amapress' );
					}
					if ( 'mon' == $adh->getMainPaiementType() ) {
						return __( 'En monnaie locale', 'amapress' );
					}
					if ( 'hla' == $adh->getMainPaiementType() ) {
						return __( 'Via HelloAsso', 'amapress' );
					}

					return __( 'Par chèque', 'amapress' );
				}
			];
			$ret['id']                = [
				'desc' => __( 'ID/Réference de l\'adhésion', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getID();
				}
			];
			$ret['message']           = [
				'desc' => __( 'Mssage à l\'AMAP lors de l\'inscription', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getMessage();
				}
			];
			$ret['lieu']              = [
				'desc' => __( 'Lieu de distribution souhaité', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return $lieu->getLieuTitle();
				}
			];
			$ret['lieu_court']        = [
				'desc' => __( 'Lieu de distribution souhaité (nom court)', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return $lieu->getShortName();
				}
			];
			$ret['lieu_heure_debut']  = [
				'desc' => __( 'Heure de début de distribution du lieu souhaité', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return date_i18n( 'H:i', $lieu->getHeure_debut() );
				}
			];
			$ret['lieu_heure_fin']    = [
				'desc' => __( 'Heure de fin de distribution du lieu souhaité', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return date_i18n( 'H:i', $lieu->getHeure_fin() );
				}
			];
			$ret['lieu_adresse']      = [
				'desc' => __( 'Adresse du lieu de distribution souhaité', 'amapress' ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return $lieu->getFormattedAdresse();
				}
			];
			$ret['custom_check1'] = [
				'desc' => sprintf( __( 'Valeur de "%s"', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK1 ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getCustomCheck( 1 ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' );
				}
			];
			$ret['custom_check2'] = [
				'desc' => sprintf( __( 'Valeur de "%s"', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getCustomCheck( 2 ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' );
				}
			];
			$ret['custom_check3'] = [
				'desc' => sprintf( __( 'Valeur de "%s"', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getCustomCheck( 3 ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' );
				}
			];
			self::$properties = $ret;
		}

		return self::$properties;
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_adhesion_paiement_lieu', 'AmapressLieu_distribution' );
	}

	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_adhesion_paiement_lieu' );
	}

	public function isHelloAsso() {
		return 'hla' == $this->getMainPaiementType();
	}

	public function getMessage() {
		return $this->getCustomAsString( 'amapress_adhesion_paiement_message' );
	}


	public function setHelloAsso( $amount, $url, $numero, $date, $set_received = true ) {
		$this->setCustom( 'amapress_adhesion_paiement_hla_url', $url );
		$this->setCustom( 'amapress_adhesion_paiement_date', $date );
		$this->setCustom( 'amapress_adhesion_paiement_amount', $amount );
		$this->setCustom( 'amapress_adhesion_paiement_numero', $numero );
		$this->setCustom( 'amapress_adhesion_paiement_hla_amount', $amount );
		$this->setCustom( 'amapress_adhesion_paiement_pmt_type', 'hla' );
		if ( $set_received ) {
			$this->setStatus( self::RECEIVED );
		}

		$rep       = [];
		$amap_term = intval( Amapress::getOption( 'adhesion_amap_term' ) );
		if ( $amap_term ) {
			$rep[ $amap_term ] = $this->getPeriod()->getMontantAmap();
		}
		$reseau_amap_term = intval( Amapress::getOption( 'adhesion_reseau_amap_term' ) );
		if ( $reseau_amap_term ) {
			$rep[ $reseau_amap_term ] = $this->getPeriod()->getMontantReseau();
		}
		wp_set_post_terms( $this->ID,
			array_keys( $rep ),
			'amps_paiement_category' );
		$this->setCustom( 'amapress_adhesion_paiement_repartition', $rep );
	}

	public function getHelloAssoUrl() {
		return $this->getCustomAsString( 'amapress_adhesion_paiement_hla_url' );
	}

	public function getHelloAssoAmount() {
		return $this->getCustomAsFloat( 'amapress_adhesion_paiement_hla_amount' );
	}

	public function sendConfirmationsAndNotifications(
		$send_adherent_confirm,
		$send_tresoriers_notif,
		$notify_email = '',
		$throw_invalid_bulletin = false,
		$send_bulletin = true
	) {
		$tresoriers = [];
		foreach ( get_users( "role=tresorier" ) as $tresorier ) {
			$user_obj   = AmapressUser::getBy( $tresorier );
			$tresoriers = array_merge( $tresoriers, $user_obj->getAllEmails() );
		}

		if ( $send_adherent_confirm ) {
			if ( $this->isHelloAsso() ) {
				$mail_subject = Amapress::getOption( 'online_hla_adhesion_confirm-mail-subject' );
				$mail_content = Amapress::getOption( 'online_hla_adhesion_confirm-mail-content' );
			} else {
				$mail_subject = Amapress::getOption( 'online_adhesion_confirm-mail-subject' );
				$mail_content = Amapress::getOption( 'online_adhesion_confirm-mail-content' );
			}
			$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $this->getUser(), $this );
			$mail_content = amapress_replace_mail_placeholders( $mail_content, $this->getUser(), $this );

			$attachments = [];
			$doc_file    = null;
			if ( $send_bulletin ) {
				try {
					$doc_file = $this->generateBulletinDoc( false );
				} catch ( Exception $ex ) {
					if ( $throw_invalid_bulletin ) {
						wp_die( __( 'Impossible de générer le bulletin d\'adhésion. Merci de réessayer en appuyant sur F5', 'amapress' ) );
					}
				}
			}
			if ( ! empty( $doc_file ) ) {
				$attachments[] = $doc_file;
				$mail_content  = preg_replace( '/\[sans_bulletin\].+?\[\/sans_bulletin\]/', '', $mail_content );
				$mail_content  = preg_replace( '/\[\/?avec_bulletin\]/', '', $mail_content );
			} else {
				$mail_content = preg_replace( '/\[avec_bulletin\].+?\[\/avec_bulletin\]/', '', $mail_content );
				$mail_content = preg_replace( '/\[\/?sans_bulletin\]/', '', $mail_content );
			}

			amapress_wp_mail( $this->getUser()->getAllEmails(), $mail_subject, $mail_content, [
				'Reply-To: ' . implode( ',', $tresoriers )
			], $attachments );
		}

		if ( $send_tresoriers_notif ) {
			$mail_subject = Amapress::getOption( 'online_adhesion_notif-mail-subject' );
			$mail_content = Amapress::getOption( 'online_adhesion_notif-mail-content' );
			$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $this->getUser(), $this );
			$mail_content = amapress_replace_mail_placeholders( $mail_content, $this->getUser(), $this );
			amapress_wp_mail(
				$tresoriers,
				$mail_subject,
				$mail_content,
				'', [], $notify_email
			);
		}
	}

	public function sendValidation() {
		$tresoriers = [];
		foreach ( get_users( "role=tresorier" ) as $tresorier ) {
			$user_obj   = AmapressUser::getBy( $tresorier );
			$tresoriers = array_merge( $tresoriers, $user_obj->getAllEmails() );
		}

		$mail_subject = Amapress::getOption( 'online_adhesion_valid-mail-subject' );
		$mail_content = Amapress::getOption( 'online_adhesion_valid-mail-content' );

		$attachments = [];
		try {
			$doc_file = $this->generateBulletinDoc( false );
		} catch ( Exception $ex ) {
			$doc_file = '';
		}
		if ( ! empty( $doc_file ) ) {
			$attachments[] = $doc_file;
			$mail_content  = preg_replace( '/\[sans_bulletin\].+?\[\/sans_bulletin\]/', '', $mail_content );
			$mail_content  = preg_replace( '/\[\/?avec_bulletin\]/', '', $mail_content );
		} else {
			$mail_content = preg_replace( '/\[avec_bulletin\].+?\[\/avec_bulletin\]/', '', $mail_content );
			$mail_content = preg_replace( '/\[\/?sans_bulletin\]/', '', $mail_content );
		}

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $this->getUser(), $this );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, $this->getUser(), $this );

		amapress_wp_mail( $this->getUser()->getAllEmails(), $mail_subject, $mail_content, [
			'Reply-To: ' . implode( ',', $tresoriers )
		], $attachments );
	}

	public static function getAdhesionToConfirmCount() {
		$current_user_id = amapress_current_user_id();
		$cnt             = get_transient( 'amps_adhpmt_to_confirm' );
		if ( false === $cnt ) {
			$cnt = [];
		}
		if ( ! isset( $cnt[ $current_user_id ] ) ) {
			$cnt[ $current_user_id ] = get_posts_count( 'post_type=amps_adh_pmt&amapress_status=not_received' );
			set_transient( 'amps_adhpmt_to_confirm', $cnt, HOUR_IN_SECONDS );
		}

		return $cnt[ $current_user_id ];
	}
}

