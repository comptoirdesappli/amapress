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

	public function setStatus( $status ) {
		$this->setCustom( 'amapress_adhesion_paiement_status', $status );
	}

	/** @return string */
	public function getMainPaiementType() {
		return $this->getCustom( 'amapress_adhesion_paiement_pmt_type', 'chq' );
	}

	public function isForIntermittent() {
		return $this->getCustom( 'amapress_adhesion_paiement_intermittent', 0 );
	}

	public function getAdhesionType() {
		if ( $this->isForIntermittent() ) {
			return 'Intermittent';
		} else {
			return 'Amapien';
		}
	}

	public function getStatusDisplay() {
		$this->ensure_init();
		switch ( $this->getStatus() ) {

			case 'not_received':
				return 'Non reçu';
			case 'received':
				return 'Reçu';
			case 'bank':
				return 'Encaissé';
			default:
				return $this->getStatus();
		}
	}

	public function isNotReceived() {
		return self::NOT_RECEIVED == $this->getStatus();
	}

	public function getStatus() {
		return $this->getCustom( 'amapress_adhesion_paiement_status', self::NOT_RECEIVED );
	}

	public function getNumero() {
		return $this->getCustom( 'amapress_adhesion_paiement_numero' );
	}

	public function getBanque() {
		return $this->getCustom( 'amapress_adhesion_paiement_banque' );
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
			$price     = $this->getAmount();
			$num       = $this->getNumero();
			$date      = $this->getDate();
			$adhesions = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id );
			if ( empty( $adhesions ) ) {
				return $ret;
			}

			$adh = array_shift( $adhesions );
			//TODO page link
			$ret[] = new Amapress_EventEntry( array(
				'ev_id'    => "upmt-{$this->ID}",
				'date'     => $date,
				'date_end' => $date,
				'type'     => 'user-paiement adhesion-paiement',
				'category' => 'Encaissements',
				'label'    => "Encaissement {$price}€",
				'class'    => "agenda-user-paiement",
				'lieu'     => $adh->getLieu(),
				'priority' => 0,
				'icon'     => 'flaticon-business',
				'alt'      => 'Vous allez être encaissé ' . ( 'Esp.' == $num ? 'des espèces remises ' : ( 'Vir.' == $num ? 'du virement ' : ( 'Mon.' == $num ? 'du paiement en monnaie locale ' : 'du chèque numéro ' . $num ) ) ) . ' d\'un montante de ' . $price . '€ à la date du ' . date_i18n( 'd/m/Y', $date ),
				'href'     => '/mes-adhesions'
			) );
		}

		return $ret;
	}

	public static function getAllActiveByUserId( $date = null ) {
		$key = "amapress_AmapressAdhesionPaiement_getAllActiveByUserId_{$date}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$period    = AmapressAdhesionPeriod::getCurrent( $date );
			$period_id = $period ? $period->ID : 0;
			$res       = array_group_by( array_map(
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
								'value'   => $period_id,
								'compare' => '=',
							),
//							array(
//								'key'     => 'amapress_adhesion_paiement_period',
//								'compare' => 'NOT EXISTS',
//							),
						),
					)
				) ),
				function ( $p ) use ( $period ) {
					/** @var AmapressAdhesion_paiement $p */
//					if ( $period && ! $p->getPeriodId() ) {
//						update_post_meta( $p->ID, 'amapress_adhesion_paiement_period', $period->ID );
//					}

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

	public static function hadUserAnyValidated( $user_id ) {
		foreach ( self::getAllForUserId( $user_id ) as $adh ) {
			if ( ! $adh->isNotReceived() ) {
				return true;
			}
		}

		return false;
	}

	/** @return AmapressAdhesion_paiement */
	public static function getForUser( $user_id, $date = null, $create = true ) {
		$adhs = AmapressAdhesion_paiement::getAllActiveByUserId( $date );
		if ( empty( $adhs[ $user_id ] ) ) {
			$user_ids = AmapressContrats::get_related_users( $user_id,
				true, null, null, true, false );
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
			$adh_period = AmapressAdhesionPeriod::getCurrent( $date );
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

		return Phptemplate_withnewline::getPlaceholderStatus( $model_file, $placeholders, 'Bulletin adhésion' );
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
			'de l\'adhésion', $additional_helps, ! $for_word,
			$for_word ? '${' : '%%', $for_word ? '}' : '%%',
			$show_toggler );
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret                     = [];
			$ret['nom']              = [
				'desc' => 'Nom de la période d\'adhésion (par ex, saison 15)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getPeriod()->getName() );
				}
			];
			$ret['date_debut']       = [
				'desc' => 'Date début de la période d\'adhésion (par ex, 01/09/2018)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getPeriod()->getDate_debut() );
				}
			];
			$ret['date_fin']         = [
				'desc' => 'Date fin de période d\'adhésion (par ex, 31/08/2019)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getPeriod()->getDate_fin() );
				}
			];
			$ret['date_debut_annee'] = [
				'desc' => 'Année de début de période d\'adhésion',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'Y', $adh->getPeriod()->getDate_debut() );
				}
			];
			$ret['date_fin_annee']   = [
				'desc' => 'Année de fin de période d\'adhésion',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'Y', $adh->getPeriod()->getDate_fin() );
				}
			];
			$ret['paiement_date'] = [
				'desc' => 'Date du paiement/adhésion à l\'AMAP',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate() );
				}
			];
			$ret['type_adhesion'] = [
				'desc' => 'Type d\'adhésion (Amapien ou intermittent)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getAdhesionType();
				}
			];
			$ret['montant_amap'] = [
				'desc' => 'Montant versé à l\'AMAP',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getPeriod()->getMontantAmap( $adh->isForIntermittent() ) );
				}
			];
			$ret['montant_reseau'] = [
				'desc' => 'Montant versé au réseau de l\'AMAP',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getPeriod()->getMontantReseau( $adh->isForIntermittent() ) );
				}
			];
			$ret['tresoriers'] = [
				'desc' => 'Nom des référents de l\'adhésion',
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
			$ret['tresoriers_emails']     = [
				'desc' => 'Nom des trésoriers avec emails',
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
			$ret['adherent']              = [
				'desc' => 'Prénom Nom adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getDisplayName();
				}
			];
			$ret['adherent.type']         = [
				'desc' => 'Type d\'adhérent (Principal, Co-adhérent...)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getAdherentTypeDisplay();
				}
			];
			$ret['adherent.pseudo']       = [
				'desc' => 'Pseudo adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->nickname;
				}
			];
			$ret['adherent.nom_public']   = [
				'desc' => 'Nom public adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->display_name;
				}
			];
			$ret['adherent.nom']          = [
				'desc' => 'Nom adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->last_name;
				}
			];
			$ret['adherent.prenom']       = [
				'desc' => 'Prénom adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getUser()->first_name;
				}
			];
			$ret['adherent.adresse']      = [
				'desc' => 'Adresse adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getFormattedAdresse();
				}
			];
			$ret['adherent.code_postal']  = [
				'desc' => 'Code postal adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCode_postal();
				}
			];
			$ret['adherent.ville']        = [
				'desc' => 'Ville adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getVille();
				}
			];
			$ret['adherent.rue']          = [
				'desc' => 'Rue (adresse) adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getAdresse();
				}
			];
			$ret['adherent.tel']          = [
				'desc' => 'Téléphone adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getTelephone();
				}
			];
			$ret['adherent.email']        = [
				'desc' => 'Email adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getEmail();
				}
			];
			$ret['coadherents.noms']      = [
				'desc' => 'Liste des co-adhérents (Prénom, Nom)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList();
				}
			];
			$ret['coadherents.contacts']  = [
				'desc' => 'Liste des co-adhérents (Prénom, Nom, Emails, Tel)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getUser()->getCoAdherentsList( true );
				}
			];
			$ret['coadherent']            = [
				'desc' => 'Prénom Nom co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getDisplayName();
				}
			];
			$ret['coadherent.pseudo']     = [
				'desc' => 'Pseudo co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->nickname;
				}
			];
			$ret['coadherent.nom_public'] = [
				'desc' => 'Nom public co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->display_name;
				}
			];
			$ret['coadherent.nom']        = [
				'desc' => 'Nom co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->last_name;
				}
			];
			$ret['coadherent.prenom']     = [
				'desc' => 'Prénom co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getUser()->first_name;
				}
			];
			$ret['coadherent.adresse']    = [
				'desc' => 'Adresse co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getFormattedAdresse();
				}
			];
			$ret['coadherent.tel']        = [
				'desc' => 'Téléphone co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getTelephone();
				}
			];
			$ret['coadherent.email']      = [
				'desc' => 'Email co-adhérent',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$coadh = $adh->getUser()->getFirstCoAdherent();
					if ( ! $coadh ) {
						return '';
					}

					return $coadh->getEmail();
				}
			];
			$taxes                        = get_categories( array(
				'orderby'    => 'name',
				'order'      => 'ASC',
				'taxonomy'   => 'amps_paiement_category',
				'hide_empty' => false,
			) );
			/** @var WP_Term $tax */
			foreach ( $taxes as $tax ) {
				$tax_id                             = $tax->term_id;
				$ret[ 'montant_cat_' . $tax->slug ] = [
					'desc' => 'Montant relatif à ' . $tax->name,
					'func' => function ( AmapressAdhesion_paiement $adh ) use ( $tax_id ) {
						return Amapress::formatPrice( $adh->getAmount( $tax_id ) );
					}
				];
			}
			$ret['total']             = [
				'desc' => 'Total de l\'adhésion',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getAmount() );
				}
			];
			$ret['montant']           = [
				'desc' => 'Total de l\'adhésion',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return Amapress::formatPrice( $adh->getAmount() );
				}
			];
			$ret['paiement_numero']   = [
				'desc' => 'Numéro du chèque',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getNumero();
				}
			];
			$ret['paiement_banque']   = [
				'desc' => 'Banque du chèque',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getBanque();
				}
			];
			$ret['paiements_mention'] = [
				'desc' => 'Mention pour les paiements',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return wp_strip_all_tags( html_entity_decode( wp_unslash( $adh->getPeriod()->getPaymentInfo() ) ) );
				}
			];
			$ret['option_paiements']  = [
				'desc' => 'Option de paiement choisie',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					if ( 'esp' == $adh->getMainPaiementType() ) {
						return 'En espèces';
					}
					if ( 'vir' == $adh->getMainPaiementType() ) {
						return 'Par virement';
					}
					if ( 'mon' == $adh->getMainPaiementType() ) {
						return 'En monnaie locale';
					}

					return 'Par chèque';
				}
			];
			$ret['id']                = [
				'desc' => 'ID/Réference de l\'adhésion',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getID();
				}
			];
			$ret['message']           = [
				'desc' => 'Mssage à l\'AMAP lors de l\'inscription',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					return $adh->getMessage();
				}
			];
			$ret['lieu']              = [
				'desc' => 'Lieu de distribution souhaité',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return $lieu->getLieuTitle();
				}
			];
			$ret['lieu_court']        = [
				'desc' => 'Lieu de distribution souhaité (nom court)',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return $lieu->getShortName();
				}
			];
			$ret['lieu_heure_debut']  = [
				'desc' => 'Heure de début de distribution du lieu souhaité',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return date_i18n( 'H:i', $lieu->getHeure_debut() );
				}
			];
			$ret['lieu_heure_fin']    = [
				'desc' => 'Heure de fin de distribution du lieu souhaité',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return date_i18n( 'H:i', $lieu->getHeure_fin() );
				}
			];
			$ret['lieu_adresse']      = [
				'desc' => 'Adresse du lieu de distribution souhaité',
				'func' => function ( AmapressAdhesion_paiement $adh ) {
					$lieu = $adh->getLieu();
					if ( ! $lieu ) {
						return '';
					}

					return $lieu->getFormattedAdresse();
				}
			];
			self::$properties         = $ret;
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

	public function getMessage() {
		return $this->getCustom( 'amapress_adhesion_paiement_message' );
	}
}

