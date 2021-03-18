<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAmapien_paiement extends Amapress_EventBase {
	private static $entities_cache = array();
	const INTERNAL_POST_TYPE = 'amps_cont_pmt';
	const POST_TYPE = 'contrat_paiement';
	const NOT_RECEIVED = 'not_received';
	const RECEIVED = 'received';
	const BANK = 'bank';

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAmapien_paiement
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAmapien_paiement' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressAmapien_paiement( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function isNotReceived() {
		return self::NOT_RECEIVED == $this->getStatus();
	}

	public function getDate() {
		$this->ensure_init();

		return $this->getCustomAsInt( 'amapress_contrat_paiement_date' );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	/** @return AmapressAdhesion */
	public function getAdhesion() {
		return $this->getCustomAsEntity( 'amapress_contrat_paiement_adhesion', 'AmapressAdhesion' );
	}

	/** @return int */
	public function getAdhesionId() {
		return $this->getCustomAsInt( 'amapress_contrat_paiement_adhesion' );
	}

	public function setStatus( $status ) {
		$this->setCustom( 'amapress_contrat_paiement_status', $status );
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

	public function getStatus() {
		return $this->getCustom( 'amapress_contrat_paiement_status', self::NOT_RECEIVED );
	}

	public function getAmount() {
		return $this->getCustomAsFloat( 'amapress_contrat_paiement_amount', 0 );
	}

	public function getNumero() {
		return $this->getCustom( 'amapress_contrat_paiement_numero', '' );
	}

	public function getType() {
		$ret = $this->getCustom( 'amapress_contrat_paiement_type', '' );
		if ( empty( $ret ) ) {
			$ret = 'chq';
		}

		return $ret;
	}

	public function getTypeFormatted() {
		return Amapress::formatPaymentType( $this->getType() );
	}

	public function getBanque() {
		return $this->getCustom( 'amapress_contrat_paiement_banque', '' );
	}


	public function getEmetteur() {
		return $this->getCustom( 'amapress_contrat_paiement_emetteur', '' );
	}

	public static function cleanOrphans() {
		global $wpdb;
		$orphans = $wpdb->get_col( "SELECT $wpdb->posts.ID
FROM $wpdb->posts 
INNER JOIN $wpdb->postmeta
ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
WHERE 1=1 
AND ( ( $wpdb->postmeta.meta_key = 'amapress_contrat_paiement_adhesion'
AND CAST($wpdb->postmeta.meta_value as SIGNED) NOT IN (
SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = '" . AmapressAdhesion::INTERNAL_POST_TYPE . "'
) ) )
AND $wpdb->posts.post_type = '" . AmapressAmapien_paiement::INTERNAL_POST_TYPE . "'
GROUP BY $wpdb->posts.ID" );

		$wpdb->query( 'START TRANSACTION' );
		foreach ( $orphans as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$wpdb->query( 'COMMIT' );

		$count = count( $orphans );
		if ( $count > 0 ) {
			return sprintf( __( '%s règlements orphelins nettoyés', 'amapress' ), $count );
		} else {
			return __( 'Aucun règlement orphelin', 'amapress' );
		}
//		$orphans = get_posts(
//			[
//				'post_type' => self::INTERNAL_POST_TYPE,
//				'posts_per_page' => -1,
//				'fields' => 'ids',
//				'meta_query' => [
//					[
//						'key' => 'amapress_contrat_paiement_adhesion',
//						'value' => [
//							12, 24
//						],
//						'compare' => 'NOT IN'
//					]
//				]
//			]
//		);
	}

	/** @return AmapressAmapien_paiement[] */
	public static function get_next_paiements( $user_id = null, $date = null, $order = 'NONE' ) {
		if ( ! amapress_is_user_logged_in() ) {
			return [];
		}

		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}
		$adhs_ids = AmapressAdhesion::getUserActiveAdhesionIds( $user_id );
		if ( empty( $adhs_ids ) ) {
			return [];
		}

		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_contrat_paiement_date',
					'value'   => Amapress::add_days( $date, - 15 ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				),
				array(
					'key'     => 'amapress_contrat_paiement_adhesion',
					'value'   => amapress_prepare_in( $adhs_ids ),
					'compare' => 'IN',
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
			$price = Amapress::formatPrice( $this->getAmount(), true );
			$adh   = $this->getAdhesion();
			if ( $adh ) {
				$contrat = $adh->getContrat_instance();
				if ( $contrat ) {
					$date                = $this->getDate();
					$all_paiements       = $adh->getAllPaiements();
					$remaining_paiements = count( $all_paiements );
					$pmt_no              = 1;
					foreach ( $all_paiements as $pmt ) {
						$remaining_paiements --;
						if ( $this->ID == $pmt->ID ) {
							break;
						}
						$pmt_no ++;
					}
					if ( 1 == $adh->getPaiements() ) {
						$desc = sprintf(
							__( 'Règlement en une fois d\'un montant de %s', 'amapress' ),
							$price );
					} elseif ( $remaining_paiements > 0 ) {
						$desc = sprintf(
							__( 'Règlement %d sur %d d\'un montant de %s - il reste %d règlements à venir à ce contrat', 'amapress' ),
							$pmt_no, $adh->getPaiements(), $price, $remaining_paiements );
					} else {
						$desc = sprintf(
							__( 'Règlement %d sur %d d\'un montant de %s - dernier règlement pour ce contrat', 'amapress' ),
							$pmt_no, $adh->getPaiements(), $price );
					}
					$desc  .= sprintf( __( "\nType: %s\nEtat: %s\nNuméro: %s\nEmetteur: %s\nMontant: %s", 'amapress' ),
						$this->getTypeFormatted(),
						$this->getStatusDisplay(),
						( ! empty( $this->getBanque() ) ? $this->getBanque() . ' - ' : '' ) .
						( ! empty( $this->getNumero() ) ? $this->getNumero() : __( '-non renseigné-', 'amapress' ) ),
						! empty( $this->getEmetteur() ) ? $this->getEmetteur() : __( '-non renseigné-', 'amapress' ),
						$price
					);
					$ret[] = new Amapress_EventEntry( array(
						'ev_id'    => "upmt-{$this->ID}",
						'date'     => Amapress::start_of_day( $date ),
						'date_end' => Amapress::end_of_day( $date ),
						'type'     => 'user-paiement contrat-paiement',
						'category' => __( 'Paiements', 'amapress' ),
						'label'    => sprintf( __( 'Paiement du contrat "%s" - %s', 'amapress' ),
							$contrat->getModelTitleWithSubName(),
							$price
						),
						'class'    => "agenda-user-paiement",
						'priority' => 0,
						'lieu'     => $adh->getLieu(),
						'icon'     => 'flaticon-business',
						'alt'      => $desc,
						'href'     => Amapress::get_mes_contrats_page_href()
					) );
				}
			}
		}

		return $ret;
	}

	private static $paiement_cache = null;

	public static function getAllActiveByAdhesionId() {
		if ( ! self::$paiement_cache ) {
			$adhesions     = AmapressContrats::get_all_adhesions( AmapressContrats::get_active_contrat_instances_ids() );
			$adhesions_ids = [];
			foreach ( $adhesions as $adhesion ) {
				$adhesions_ids[] = $adhesion->ID;
			}

			do {
				$changed = count( $adhesions_ids );
				foreach (
					get_posts(
						array(
							'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
							'posts_per_page' => - 1,
							'meta_query'     => array(
								array(
									'key'     => 'amapress_adhesion_related',
									'value'   => amapress_prepare_in( $adhesions_ids ),
									'compare' => 'IN',
									'type'    => 'NUMERIC',
								),
							),
						)
					) as $adhesion
				) {
					if ( ! in_array( $adhesion->ID, $adhesions_ids ) ) {
						$adhesions_ids[] = $adhesion->ID;
					}
				}
			} while ( $changed != count( $adhesions_ids ) );
			self::$paiement_cache = array_group_by( array_map(
				function ( $p ) {
					return new AmapressAmapien_paiement( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'key'     => 'amapress_contrat_paiement_adhesion',
								'value'   => amapress_prepare_in( $adhesions_ids ),
								'compare' => 'IN',
								'type'    => 'NUMERIC',
							),
						),
					)
				) ),
				function ( $p ) {
					/** @var AmapressAmapien_paiement $p */
					return $p->getAdhesionId();
				}
			);
		}

		return self::$paiement_cache;
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret = [];
			foreach ( AmapressAdhesion::getProperties() as $name => $conf ) {
				$func         = $conf['func'];
				$ret[ $name ] = [
					'desc' => $conf['desc'],
					'func' => function ( AmapressAmapien_paiement $adh ) use ( $func ) {
						return call_user_func( $func, $adh->getAdhesion() );
					}
				];
			}

			$ret['paiement_type']     = [
				'desc' => __( 'Type de paiement (Chèque, espèces, virement...)', 'amapress' ),
				'func' => function ( AmapressAmapien_paiement $adh ) {
					return $adh->getTypeFormatted();
				}
			];
			$ret['paiement_numero']   = [
				'desc' => __( 'Numéro du chèque', 'amapress' ),
				'func' => function ( AmapressAmapien_paiement $adh ) {
					return $adh->getNumero();
				}
			];
			$ret['paiement_emetteur'] = [
				'desc' => __( 'Nom de l\'adhérent émetteur', 'amapress' ),
				'func' => function ( AmapressAmapien_paiement $adh ) {
					return $adh->getEmetteur();
				}
			];
			$ret['paiement_banque']   = [
				'desc' => __( 'Banque du chèque', 'amapress' ),
				'func' => function ( AmapressAmapien_paiement $adh ) {
					return $adh->getBanque();
				}
			];
			$ret['paiement_montant']  = [
				'desc' => __( 'Montant du paiement', 'amapress' ),
				'func' => function ( AmapressAmapien_paiement $adh ) {
					return Amapress::formatPrice( $adh->getAmount() );
				}
			];
			$ret['paiement_date']     = [
				'desc' => __( 'Date d\'encaissement du paiement', 'amapress' ),
				'func' => function ( AmapressAmapien_paiement $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate() );
				}
			];
			$ret['paiement_status']   = [
				'desc' => __( 'Etat du paiement', 'amapress' ),
				'func' => function ( AmapressAmapien_paiement $adh ) {
					return $adh->getStatusDisplay();
				}
			];

			self::$properties = $ret;
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

	public static function getPlaceholdersHelp( $additional_helps = [], $for_contrat = false, $show_toggler = true ) {
		$ret = self::getPlaceholders();

		return Amapress::getPlaceholdersHelpTable( 'contrat-pmt-placeholders', $ret,
			'du règlement', $additional_helps, false,
			$for_contrat ? '${' : '%%', $for_contrat ? '}' : '%%',
			$show_toggler );
	}

	public function sendAwaitingRecall() {
		if ( self::NOT_RECEIVED != $this->getStatus() ) {
			return false;
		}
		amapress_mail_to_current_user(
			Amapress::getOption( 'paiement-awaiting-mail-subject' ),
			Amapress::getOption( 'paiement-awaiting-mail-content' ),
			$this->getAdhesion()->getAdherentId(),
			$this, [],
			amapress_get_recall_cc_from_option( 'paiement-awaiting-cc' ),
			null, [
			'Reply-To: ' . implode( ',', $this->getAdhesion()->getContrat_instance()->getAllReferentsEmails(
				$this->getAdhesion()->getLieuId() ) )
		] );

		return true;
	}
}

