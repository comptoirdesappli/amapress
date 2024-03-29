<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressDistribution extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_distribution';
	const POST_TYPE = 'distribution';

	private static $entities_cache = array();

	public static function clearCache() {
		self::$entities_cache = array();
	}

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressDistribution
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} elseif ( is_a( $post_or_id, 'AmapressDistribution' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressDistribution( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	/** @return AmapressDistribution[] */
	public static function getAll() {
		return array_map(
			function ( $p ) {
				return AmapressDistribution::getBy( $p );
			},
			get_posts(
				array(
					'post_type'      => self::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
				)
			)
		);
	}

	public static function cleanOrphans() {
		global $wpdb;

		$dists = self::getAll();
		$count = 0;
		$wpdb->query( 'START TRANSACTION' );
		foreach ( $dists as $dist ) {
			if ( empty( $dist->getContratIds() ) ) {
				wp_delete_post( $dist->ID, true );
				$count ++;
			}
		}
		$wpdb->query( 'COMMIT' );

		if ( $count > 0 ) {
			return sprintf( __( '%d distributions orphelines nettoyées', 'amapress' ), $count );
		} else {
			return __( 'Aucun distribution orpheline', 'amapress' );
		}
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getSpecialHeure_debut() {
		return $this->getCustomAsInt( 'amapress_distribution_heure_debut_spec' );
	}

	public function getSpecialHeure_fin() {
		return $this->getCustomAsInt( 'amapress_distribution_heure_fin_spec' );
	}

	public function getStartDateAndHour() {
		if ( ! empty( $this->getSpecialHeure_debut() ) ) {
			return Amapress::make_date_and_hour( $this->getDate(), $this->getSpecialHeure_debut() );
		} else {
			return Amapress::make_date_and_hour( $this->getDate(), $this->getRealLieu()->getHeure_debut() );
		}
	}

	public function getEndDateAndHour() {
		if ( ! empty( $this->getSpecialHeure_fin() ) ) {
			return Amapress::make_date_and_hour( $this->getDate(), $this->getSpecialHeure_fin() );
		} else {
			return Amapress::make_date_and_hour( $this->getDate(), $this->getRealLieu()->getHeure_fin() );
		}
	}

	public function getNb_responsables_Supplementaires() {
		return $this->getCustomAsInt( 'amapress_distribution_nb_resp_supp', 0 );
	}

	public function getInformations() {
		return stripslashes( $this->getCustomAsString( 'amapress_distribution_info', '' ) );
	}

	public function getDate() {
		return $this->getCustomAsDate( 'amapress_distribution_date' );
	}

	/** @return AmapressLieu_distribution */
	public function getRealLieu() {
		$lieu_subst = $this->getLieuSubstitution();
		if ( $lieu_subst ) {
			return $lieu_subst;
		}

		return $this->getLieu();
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_distribution_lieu', 'AmapressLieu_distribution' );
	}

	/** @return int */
	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_distribution_lieu', - 1 );
	}

	/** @return AmapressLieu_distribution */
	public function getLieuSubstitution() {
		return $this->getCustomAsEntity( 'amapress_distribution_lieu_substitution', 'AmapressLieu_distribution' );
	}

	/** @return int */
	public function getLieuSubstitutionId() {
		return $this->getCustomAsInt( 'amapress_distribution_lieu_substitution', 0 );
	}

	/** @return AmapressUser[] */
	public function getGardiens( $include_private = false ) {
		return array_map( function ( $id ) {
			return AmapressUser::getBy( $id );
		}, $this->getGardiensIds( $include_private ) );
	}

	/** @return int[] */
	public function getGardiensIds( $include_private = false ) {

		$ids = $this->getCustomAsIntArray( 'amapress_distribution_gardiens' );
		if ( $include_private ) {
			foreach ( $this->getPaniersGarde() as $v ) {
				$ids[] = intval( $v );
			}
		}

		return array_unique( $ids );
	}

	public function getPaniersDescription( $amapien_id ) {
		$quantites = array();
		foreach ( $this->getContrats() as $contrat_instance ) {
			$adhesions = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $amapien_id, $contrat_instance->ID, $this->getDate() );
			/** @var AmapressAdhesion $adhesion */
			$adhesion = array_shift( $adhesions );
			if ( ! $adhesion ) {
				continue;
			}
			$quantites[] = $contrat_instance->getModelTitle() .
			               '(' . $adhesion->getContrat_quantites_AsString( $this->getDate() ) . ')';
		}

		return implode( ', ', $quantites );
	}

	public function inscrireGardien(
		$user_id, $allow_anonymous = false, $allow_not_member = false,
		$comment = null, $send_mail = true, $bulk = false
	) {
		if ( ! $allow_anonymous && ! amapress_is_user_logged_in() ) {
			if ( $bulk ) {
				return false;
			}
			wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
		}

		if ( ! $allow_not_member && ! amapress_can_access_admin() ) {
			if ( ! $this->isUserMemberOf( $user_id, true ) ) {
				if ( $bulk ) {
					return false;
				}
				wp_die( __( 'Vous ne faites pas partie de cette distribution.', 'amapress' ) );
			}
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			if ( $bulk ) {
				return false;
			}
			wp_die( __( 'Clos et passé', 'amapress' ) );
		}

		$gardiens = $this->getGardiensIds();
		if ( ! $gardiens ) {
			$gardiens = array();
		}
		if ( in_array( $user_id, $gardiens ) ) {
			return 'already_in_list';
		} else {
			$gardiens[] = $user_id;
			$this->setCustom( 'amapress_distribution_gardiens', $gardiens );
			if ( ! empty( $comment ) ) {
				$this->setCustom( "amapress_distribution_gardien_{$user_id}_comment", $comment );
			} else {
				$this->deleteCustom( "amapress_distribution_gardien_{$user_id}_comment" );
			}

			if ( $send_mail ) {
				amapress_mail_current_user_inscr( $this, $user_id, 'distrib-gardien' );
			}

			return 'ok';
		}
	}

	public function desinscrireGardien(
		$user_id, $allow_anonymous = false,
		$send_mail = true, $bulk = false
	) {
		if ( ! $allow_anonymous && ! amapress_is_user_logged_in() ) {
			if ( $bulk ) {
				return false;
			}
			wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			if ( $bulk ) {
				return false;
			}
			wp_die( __( 'Clos et passé', 'amapress' ) );
		}

		$gardiens = $this->getGardiensIds();
		if ( ! $gardiens ) {
			$gardiens = array();
		}

		if ( ( $key = array_search( $user_id, $gardiens ) ) !== false ) {
			if ( ! empty( $this->getGardiensPaniersAmapiensIds( $user_id ) ) ) {
				return 'has_gardes';
			}
			$events = $this->get_related_events( $user_id );
			unset( $gardiens[ $key ] );

			$this->deleteCustom( "amapress_distribution_gardien_{$user_id}_comment" );
			$this->setCustom( 'amapress_distribution_gardiens', $gardiens );

			if ( $send_mail ) {
				amapress_mail_current_user_desinscr( $this, $user_id, 'distrib-gardien',
					null, null, null, $events );
			}

			return 'ok';
		} else {
			return 'not_inscr';
		}
	}

	public function getGardienComment( $gardien_id ) {
		return $this->getCustomAsString( "amapress_distribution_gardien_{$gardien_id}_comment" );
	}

	public function getPaniersGarde() {
		return $this->getCustomAsArray( 'amapress_distribution_pan_garde' );
	}

	public function getPanierGardienId( $amapien_id ) {
		$gardes = $this->getPaniersGarde();
		if ( isset( $gardes["u{$amapien_id}"] ) ) {
			return $gardes["u{$amapien_id}"];
		}

		return 0;
	}

	public function getGardiensPaniersAmapiensIds( $gardien_id ) {
		$amapien_ids = [];
		foreach ( $this->getPaniersGarde() as $k => $v ) {
			if ( $v == $gardien_id ) {
				$amapien_ids[] = intval( substr( $k, 1 ) );
			}
		}

		return $amapien_ids;
	}

	public function faireGarder(
		$user_id, $gardien_id,
		$set = true,
		$allow_anonymous = false, $allow_not_member = false
	) {
		if ( ! $allow_anonymous && ! amapress_is_user_logged_in() ) {
			wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
		}

		if ( ! $allow_not_member && ! amapress_can_access_admin() ) {
			if ( ! $this->isUserMemberOf( $user_id, true ) ) {
				wp_die( __( 'Vous ne faites pas partie de cette distribution.', 'amapress' ) );
			}
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			wp_die( __( 'Clos et passé', 'amapress' ) );
		}

		if ( empty( $user_id ) ) {
			$user_id = amapress_current_user_id();
		}

		$gardiens = $this->getPaniersGarde();
		if ( ! $gardiens ) {
			$gardiens = array();
		}
		$amapien = AmapressUser::getBy( $user_id );
		if ( $set ) {
			if ( ! empty( $gardiens["u{$user_id}"] ) ) {
				return 'already_in_list';
			} else {
				$gardiens["u{$user_id}"] = $gardien_id;
				$this->setCustom( 'amapress_distribution_pan_garde', $gardiens );

				$gardien = AmapressUser::getBy( $gardien_id );
				amapress_mail_current_user_inscr( $this, $gardien_id, 'distrib-gardien',
					function ( $content, $user_id, $post ) use ( $amapien, $gardien ) {
						$content = str_replace( '%%amapien%%', $amapien->getDisplayName(), $content );
						$content = str_replace( '%%amapien_contacts%%', $amapien->getContacts(), $content );
						$content = str_replace( '%%gardien%%', $gardien->getDisplayName(), $content );
						$content = str_replace( '%%gardien_contact%%', $gardien->getContacts(), $content );
						$content = str_replace( '%%gardien_comment%%', $this->getGardienComment( $gardien->ID ), $content );

						return $content;
					}, 'distrib-gardieneur', $amapien->getEmail()
				);
				amapress_mail_current_user_inscr( $this, $user_id, 'distrib-gardien',
					function ( $content, $user_id, $post ) use ( $amapien, $gardien ) {
						$content = str_replace( '%%amapien%%', $amapien->getDisplayName(), $content );
						$content = str_replace( '%%amapien_contacts%%', $amapien->getContacts(), $content );
						$content = str_replace( '%%gardien%%', $gardien->getDisplayName(), $content );
						$content = str_replace( '%%gardien_contact%%', $gardien->getContacts(), $content );
						$content = str_replace( '%%gardien_comment%%', $this->getGardienComment( $gardien->ID ), $content );

						return $content;
					}, 'distrib-gardiened', $gardien->getEmail()
				);

				return 'ok';
			}
		} else {
			if ( ! isset( $gardiens["u{$user_id}"] ) ) {
				return 'not_inscr';
			} else {
				$gardien_id = $gardiens["u{$user_id}"];
				$events     = $this->get_related_events( $gardien_id );
				unset( $gardiens["u{$user_id}"] );
				$this->setCustom( 'amapress_distribution_pan_garde', $gardiens );

				$gardien = AmapressUser::getBy( $gardien_id );
				amapress_mail_current_user_desinscr( $this, $gardien_id, 'distrib-gardien',
					function ( $content, $user_id, $post ) use ( $amapien, $gardien ) {
						$content = str_replace( '%%amapien%%', $amapien->getDisplayName(), $content );
						$content = str_replace( '%%amapien_contacts%%', $amapien->getContacts(), $content );
						$content = str_replace( '%%gardien%%', $gardien->getDisplayName(), $content );
						$content = str_replace( '%%gardien_contact%%', $gardien->getContacts(), $content );
						$content = str_replace( '%%gardien_comment%%', $this->getGardienComment( $gardien->ID ), $content );

						return $content;
					}, 'distrib-gardieneur', $amapien->getEmail(), $events
				);
//				amapress_mail_current_user_desinscr( $this, $gardien_id, 'distrib-gardien',
//					function ( $content, $user_id, $post ) use ( $amapien, $gardien ) {
//						$content = str_replace( '%%amapien%%', $amapien->getDisplayName(), $content );
//						$content = str_replace( '%%amapien_contacts%%', $amapien->getContacts(), $content );
//						$content = str_replace( '%%gardien%%', $gardien->getDisplayName(), $content );
//						$content = str_replace( '%%gardien_contact%%', $gardien->getContacts(), $content );
//
//						return $content;
//					}, 'distrib-gardiened' );

				return 'ok';
			}
		}
	}

	/** @return AmapressUser[] */
	public function getResponsables() {
		$responsables_ids = $this->getResponsablesIds();
		$responsables_ids = array_unique( array_map( function ( $id ) {
			return $id & 0x0FFFFFFF;
		}, $responsables_ids ) );

		return array_map( function ( $user_id ) {
			return AmapressUser::getBy( $user_id );
		}, $responsables_ids );
	}

	public function getMultiResponsableInscriptionCount( $resp_id ) {
		$ret              = 0;
		$responsables_ids = $this->getResponsablesIds();
		for ( $i = 0; $i < 0xF; $i ++ ) {
			$multi_user_id = $resp_id | $i << 28;
			if ( in_array( $multi_user_id, $responsables_ids ) ) {
				$ret += 1;
			}
		}

		return $ret;
	}

	public function getMailtoResponsables( $bcc = false ) {
		$resp_mails = [];
		foreach ( $this->getResponsables() as $user ) {
			$resp_mails = array_merge( $resp_mails, $user->getAllEmails() );
		}
		if ( empty( $resp_mails ) ) {
			return '';
		}

		if ( $bcc ) {
			$site_email = Amapress::getOption( 'email_from_mail' );

			return 'mailto:' . rawurlencode( $site_email ) . '?bcc=' . rawurlencode( implode( ',', $resp_mails ) ) . '&subject=' . __( 'Distribution du ', 'amapress' ) .
			       date_i18n( 'D j F Y' );
		} else {
			return 'mailto:' . rawurlencode( implode( ',', $resp_mails ) ) . '&subject=' . __( 'Distribution du ', 'amapress' ) .
			       date_i18n( 'D j F Y' );
		}
	}

	/** @return int[] */
	public function getMainAdherentsIds( $include_coadherents = true ) {
		$ids = [];
		foreach ( AmapressContrats::get_active_adhesions( $this->getContratIds(), null, $this->getLieuId(), $this->getDate(), true, false ) as $adh ) {
			/** @var AmapressAdhesion $adh */
			if ( ! empty( $adh->getAdherentId() ) ) {
				$ids[] = $adh->getAdherentId();
				if ( $include_coadherents ) {
					$ids = array_merge( $ids, AmapressContrats::get_related_users( $adh->getAdherentId() ) );
				}
			}
		}

		return array_unique( $ids );
	}

	public function getMailtoAmapiens() {
		$mails = [];
		foreach ( AmapressContrats::get_active_adhesions( $this->getContratIds(), null, $this->getLieuId(), $this->getDate(), true, false ) as $adh ) {
			/** @var AmapressAdhesion $adh */
			if ( ! empty( $adh->getAdherent() ) ) {
				$mails = array_merge( $mails, $adh->getAdherent()->getAllEmails() );
			}
		}

		$query                        = array();
		$query['contrat_instance_id'] = $this->getContratIds();
		$query['lieu_id']             = $this->getLieuId();
		$query['date']                = $this->getDate();
		$paniers                      = AmapressPaniers::getPanierIntermittents( $query );
		foreach ( $paniers as $panier ) {
			if ( ! empty( $panier->getRepreneur() ) ) {
				$mails = array_merge( $mails, $panier->getRepreneur()->getAllEmails() );
			}
		}

		if ( empty( $mails ) ) {
			return '';
		}

		$site_email = Amapress::getOption( 'email_from_mail' );

		return 'mailto:' . rawurlencode( $site_email ) . '?bcc=' . rawurlencode( implode( ',', array_unique( $mails ) ) ) . '&subject=' . __( 'Distribution du ', 'amapress' ) .
		       date_i18n( 'D j F Y' );
	}

	/** @return int[] */
	public function getResponsablesIds() {
		return $this->getCustomAsIntArray( 'amapress_distribution_responsables' );
	}

	/** @return AmapressContrat_instance[] */
	public function getContrats(
		$include_cancelled = false
	) {
		$ret = array_map(
			function ( $id ) {
				return AmapressContrat_instance::getBy( $id );
			}, $this->getContratIds( $include_cancelled )
		);

		return array_filter( $ret, function ( $c ) {
			/** @var AmapressContrat_instance $c */
			return ! empty( $c ) && ! empty( $c->getModel() );
		} );
	}

	/** @return int[] */
	public function getContratModelIds() {
		return array_filter( array_map(
			function ( $id ) {
				$c = AmapressContrat_instance::getBy( $id );

				return $c ? $c->getModelId() : 0;
			}, $this->getContratIds()
		), function ( $i ) {
			return ! empty( $i );
		} );
	}

	public function getRealDateForContrat( $contrat_id ) {
		$paniers = $this->getDelayedToThisPaniers();
		foreach ( $paniers as $p ) {
			if ( $p->getContrat_instanceId() == $contrat_id ) {
				return Amapress::start_of_day( $p->getDate() );
			}
		}

		return Amapress::start_of_day( $this->getDate() );
	}

	/** @return AmapressPanier[] */
	public function getDelayedToThisPaniers() {
		$key     = 'AmapressDistribution-getDelayedToThisPaniers-' . $this->ID;
		$paniers = wp_cache_get( $key );
		if ( false === $paniers ) {
			$paniers = AmapressPanier::get_delayed_paniers(
				null,
				$this->getDate(), null,
				[ 'delayed' ],
				$this->getLieuId() );
			wp_cache_set( $key, $paniers );
		}

		return $paniers;
	}

	/** @return AmapressContrat_instance[] */
	public function getDelayedToThisContrats() {
		return array_map(
			function ( $p ) {
				/** @var AmapressPanier $p */
				return $p->getContrat_instance();
			},
			$this->getDelayedToThisPaniers()
		);
	}

	/** @return int[] */
	public function getDelayedToThisContratIds() {
		return array_map(
			function ( $p ) {
				/** @var AmapressPanier $p */
				return $p->getContrat_instanceId();
			},
			$this->getDelayedToThisPaniers()
		);
	}

	public function getCancelledPaniers() {
		return AmapressPanier::get_delayed_paniers(
			null,
			null, $this->getDate(),
			[ 'delayed', 'cancelled' ],
			$this->getLieuId()
		);
	}

	/** @return int[] */
	public function getCancelledContratIds() {
		return array_map( function ( $p ) {
			/** @var AmapressPanier $p */
			return $p->getContrat_instanceId();
		}, $this->getCancelledPaniers() );
	}

	/** @return int[] */
	public function getContratIds(
		$include_cancelled = false
	) {
		$res = $this->getCustomAsIntArray( 'amapress_distribution_contrats' );

		$cancelled_contrat_ids = $include_cancelled ? [] : $this->getCancelledContratIds();
		$delayed_contrat_ids   = array_map( function ( $p ) {
			/** @var AmapressPanier $p */
			return $p->getContrat_instanceId();
		}, $this->getDelayedToThisPaniers() );
		$res                   = array_diff( $res, $cancelled_contrat_ids );
		$res                   = array_merge( $res, $delayed_contrat_ids );

		return array_unique( $res );
	}

	public function getSlotInfoForUser( $user_id ) {
		$res = parent::getSlotInfoForUser( $user_id );
		if ( empty( $res ) ) {
			$inter_id = $this->getAdherentRelatedIntermittent( $user_id );
			if ( $inter_id ) {
				$res = parent::getSlotInfoForUser( $inter_id );
			}
		}
		if ( empty( $res ) ) {
			$inter_id = $this->getIntermittentRelatedAdherent( $user_id );
			if ( $inter_id ) {
				$res = parent::getSlotInfoForUser( $inter_id );
			}
		}

		return $res;
	}


	public function getIntermittentRelatedAdherent( $user_id ) {
		$query                        = [];
		$query['contrat_instance_id'] = $this->getContratIds();
		$query['lieu_id']             = $this->getLieuId();
		$query['date']                = $this->getDate();
		$query['repreneur']           = $user_id;
		$paniers                      = AmapressPaniers::getPanierIntermittents( $query );
		foreach ( $paniers as $panier ) {
			if ( $panier->getAdherentId() ) {
				return $panier->getAdherentId();
			}
		}

		return null;
	}

	public function getAdherentRelatedIntermittent( $user_id ) {
		$query                        = [];
		$query['contrat_instance_id'] = $this->getContratIds();
		$query['lieu_id']             = $this->getLieuId();
		$query['date']                = $this->getDate();
		$query['adherent']            = $user_id;
		$paniers                      = AmapressPaniers::getPanierIntermittents( $query );
		foreach ( $paniers as $panier ) {
			if ( $panier->getRepreneurId() ) {
				return $panier->getRepreneurId();
			}
		}

		return null;
	}

	public function getIntermittentIds() {
		$query                        = [];
		$query['contrat_instance_id'] = $this->getContratIds();
		$query['lieu_id']             = $this->getLieuId();
		$query['date']                = $this->getDate();
		$paniers                      = AmapressPaniers::getPanierIntermittents( $query );
		$res                          = [];
		foreach ( $paniers as $panier ) {
			if ( $panier->getRepreneurId() ) {
				$res[] = $panier->getRepreneurId();
			}
		}

		return $res;
	}

	/**
	 * @param int $user_id
	 * @param bool $guess_renew
	 * @param AmapressAdhesion[]|null $precache_user_adhesions
	 *
	 * @return bool
	 */
	public function isUserMemberOf( $user_id, $guess_renew = false, $precache_user_adhesions = null ) {
		if ( null !== $precache_user_adhesions ) {
			$user_contrats_ids = [];
			$user_lieu_ids     = [];
			foreach ( $precache_user_adhesions as $adh ) {
				if ( ! empty( $adh->getContrat_quantites( $this->getDate() ) ) ) {
					$user_contrats_ids[] = $adh->getContrat_instanceId();
					$user_lieu_ids[]     = $adh->getLieuId();
				}
			}
		} else {
			$user_contrats_ids = AmapressContrat_instance::getContratInstanceIdsForUser( $user_id,
				null,
				$this->getDate(),
				$guess_renew );
		}
		$dist_contrat_ids = array_map( function ( $c ) {
			return $c->ID;
		}, $this->getContrats() );

		if ( count( array_intersect( $user_contrats_ids, $dist_contrat_ids ) ) > 0 ) {
			return true;
		}
		$inter_adherent_id = $this->getIntermittentRelatedAdherent( $user_id );
		//if $user_id is an intermittent that exchanged with an adherent, it's ok
		if ( $inter_adherent_id ) {
			return true;
		}
		if ( ! $guess_renew ) {
			return false;
		}

		if ( null === $precache_user_adhesions ) {
			$user_lieu_ids = AmapressUsers::get_user_lieu_ids( $user_id,
				$this->getDate() );
		}

		return in_array( $this->getLieuId(), $user_lieu_ids );
	}

	public function inscrireResponsable(
		$user_id, $role = 0,
		$allow_anonymous = false, $allow_not_member = false
	) {
		if ( ! $allow_anonymous && ! amapress_is_user_logged_in() ) {
			wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
		}

		if ( ! $allow_not_member && ! amapress_can_access_admin() ) {
			if ( ! $this->isUserMemberOf( $user_id, true ) ) {
				wp_die( __( 'Vous ne faites pas partie de cette distribution.', 'amapress' ) );
			}
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			wp_die( __( 'Clos et passé', 'amapress' ) );
		}

		$responsables        = $this->getResponsablesIds();
		$needed_responsables = AmapressDistributions::get_required_responsables( $this->ID );
		if ( ! $responsables ) {
			$responsables = array();
		}

		$multi_user_id = $user_id;
		if ( Amapress::toBool( Amapress::getOption( 'inscr-distrib-allow-multi' ) ) ) {
			for ( $i = 0; $i < 0xF; $i ++ ) {
				$multi_user_id = $user_id | $i << 28;
				if ( ! in_array( $multi_user_id, $responsables ) ) {
					break;
				}
			}
		}
		if ( in_array( $multi_user_id, $responsables ) ) {
			return 'already_in_list';
		} elseif ( count( $responsables ) >= $needed_responsables ) {
			return 'list_full';
		} else {
			$responsables[] = $multi_user_id;
			if ( $role > 0 ) {
				$this->ensure_init();
				foreach ( $this->custom as $k => $v ) {
					if ( strpos( $k, 'amapress_distribution_resp_' ) === 0 ) {
						$role_user_id = intval( substr( $k, 27 ) );
						if ( ! in_array( $role_user_id, $responsables ) ) {
							$this->deleteCustom( $k );
							continue;
						}
						if ( $v == $role ) {
							return 'already_taken';
						}
					}
				}
				$this->setCustom( 'amapress_distribution_resp_' . $multi_user_id, $role );
			}
			$this->setCustom( 'amapress_distribution_responsables', $responsables );

			amapress_mail_current_user_inscr( $this, $user_id, 'distrib',
				function ( $cnt, $user_id, $post ) use ( $multi_user_id ) {
					$role = $this->getResponsableRoleId( $multi_user_id );
					if ( ! $role ) {
						return $cnt;
					}

					return str_replace(
						[
							'%%resp_role%%',
							'%%resp_role_desc%%',
							'%%resp_role_contrats%%',
						],
						[
							esc_html( $this->getResponsableRoleName( $multi_user_id ) ),
							esc_html( $this->getResponsableRoleDesc( $multi_user_id ) ),
							esc_html( $this->getResponsableRoleContrats( $multi_user_id ) ),
						], $cnt );
				},
				null, null,
				amapress_get_recall_cc_from_option( 'inscr-distrib-mail-cc' ) );

			return 'ok';
		}
	}

	public function desinscrireResponsable( $user_id, $allow_anonymous = false ) {
		if ( ! $allow_anonymous && ! amapress_is_user_logged_in() ) {
			wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			wp_die( __( 'Clos et passé', 'amapress' ) );
		}

		$responsables = $this->getResponsablesIds();
		if ( ! $responsables ) {
			$responsables = array();
		}

		$found = false;
		for ( $i = 0; $i < 0xF; $i ++ ) {
			if ( ( $key = array_search( $user_id | $i << 28, $responsables ) ) !== false ) {
				unset( $responsables[ $key ] );
				$found = true;
			}
		}

		if ( $found ) {
			$events = $this->get_related_events( $user_id );
			$this->setCustom( 'amapress_distribution_responsables', $responsables );
			$this->deleteCustom( 'amapress_distribution_resp_' . $user_id );

			amapress_mail_current_user_desinscr( $this, $user_id, 'distrib',
				null, null, null, $events );

			return 'ok';
		} else {
			return 'not_inscr';
		}
	}

	public function getResponsableRoleId( $user_id ) {
		if ( is_a( $user_id, 'AmapressUser' ) || is_a( $user_id, 'WP_User' ) ) {
			$user_id = $user_id->ID;
		}

		$role = $this->getCustom( 'amapress_distribution_resp_' . $user_id );
		if ( empty( $role ) ) {
			return '';
		}

		return $role;
	}

	public function getResponsableRoleName( $user_id ) {
		$role = $this->getResponsableRoleId( $user_id );
		if ( empty( $role ) ) {
			return '';
		}

		$name = Amapress::getOption( "resp_role_{$this->getLieuId()}_$role-name", '' );
		if ( empty( $name ) ) {
			$name = Amapress::getOption( "resp_role_$role-name", '' );
		}

		return stripslashes( $name );
	}

	public function getResponsableRoleDesc( $user_id ) {
		$role = $this->getResponsableRoleId( $user_id );
		if ( empty( $role ) ) {
			return '';
		}

		$desc = Amapress::getOption( "resp_role_{$this->getLieuId()}_$role-desc", '' );
		if ( empty( $desc ) ) {
			$desc = Amapress::getOption( "resp_role_$role-desc", '' );
		}

		return stripslashes( $desc );
	}

	public function getResponsableRoleContrats( $user_id, $default = '' ) {
		$role = $this->getResponsableRoleId( $user_id );
		if ( empty( $role ) ) {
			return $default;
		}

		$contrats = Amapress::get_array( Amapress::getOption( "resp_role_{$this->getLieuId()}_$role-contrats" ) );
		if ( empty( $contrats ) ) {
			$contrats = Amapress::get_array( Amapress::getOption( "resp_role_$role-name" ) );
		}

		return implode( ', ', array_map( function ( $contrat_id ) {
			$contrat = AmapressContrat::getBy( $contrat_id );
			if ( ! $contrat ) {
				return '#unk#';
			}

			return $contrat->getTitle();
		}, $contrats ) );
	}

//
	public static function getUserNextDistributions( $user_id = null, $date = null, $max_distribs = 0 ) {
		$distribs  = self::get_next_distributions( $date );
		$adhesions = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id );
		$ret       = [];
		foreach ( $distribs as $distrib ) {
			$contrats = $distrib->getContratIds();
			foreach ( $adhesions as $adhesion ) {
				if ( $adhesion->getLieuId() == $distrib->getLieuId()
				     && in_array( $adhesion->getContrat_instanceId(), $contrats )
				) {
					$quants = $adhesion->getContrat_quantites( $distrib->getDate() );
					if ( empty( $quants ) ) {
						continue;
					}

					$ret[] = $distrib;
					break;
				}
			}
			if ( $max_distribs && count( $ret ) == $max_distribs ) {
				break;
			}
		}

		return $ret;
	}

	/**
	 * @param int $lieu_id
	 * @param int $contrat_instance_id
	 * @param int $date
	 *
	 * @return AmapressDistribution
	 */
	public static function getNextDistribution( $lieu_id = null, $contrat_instance_id = null, $date = null ) {
		if ( ! $date ) {
			$date = amapress_time();
		}
		$meta = array(
			array(
				'key'     => 'amapress_distribution_date',
				'value'   => Amapress::start_of_day( $date ),
				'compare' => '>=',
				'type'    => 'NUMERIC'
			),
		);
		if ( $lieu_id ) {
			$meta[] = array(
				'key'     => 'amapress_distribution_lieu',
				'value'   => $lieu_id,
				'compare' => '=',
				'type'    => 'NUMERIC'
			);
		}
		if ( $contrat_instance_id ) {
			$cancelled_paniers_dates = array_map( function ( $p ) {
				/** @var AmapressPanier $p */
				return Amapress::start_of_day( $p->getDate() );
			}, AmapressPanier::get_delayed_paniers( $contrat_instance_id ) );
			$delayed_paniers_dates   = array_map( function ( $p ) {
				/** @var AmapressPanier $p */
				return Amapress::start_of_day( $p->getDateSubst() );
			}, AmapressPanier::get_delayed_paniers( $contrat_instance_id, null, null, [ 'delayed' ] ) );
			$meta[]                  =
				array(
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_distribution_date',
							'value'   => $delayed_paniers_dates,
							'compare' => 'IN',
							'type'    => 'NUMERIC',
						),
						amapress_prepare_like_in_array( 'amapress_distribution_contrats', $contrat_instance_id )
					),
					array(
						array(
							'key'     => 'amapress_distribution_date',
							'value'   => $cancelled_paniers_dates,
							'compare' => 'NOT IN',
							'type'    => 'NUMERIC',
						),
					)
				);
		}
		$dists = get_posts( array(
			'post_type'      => self::INTERNAL_POST_TYPE,
			'posts_per_page' => - 1,
			'meta_query'     => $meta,
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'meta_key'       => 'amapress_distribution_date',
		) );

		$dist = array_shift( $dists );
		if ( $dist ) {
			$dist = new AmapressDistribution( $dist );
		}

		return $dist;
	}

	/** @return AmapressDistribution[] */
	public static function get_next_distributions( $date = null, $order = 'ASC' ) {
		if ( ! $date ) {
			$date = amapress_time();
		}
		$date = Amapress::start_of_day( $date );

		$key = "amapress_get_next_distributions-$date-$order";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = self::query_events(
				array(
					array(
						'key'     => 'amapress_distribution_date',
						'value'   => $date,
						'compare' => '>=',
						'type'    => 'NUMERIC'
					),
				),
				$order );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/** @return AmapressDistribution[] */
	public static function get_distributions( $start_date = null, $end_date = null, $order = 'NONE' ) {
		if ( ! $start_date ) {
			$start_date = Amapress::start_of_day( amapress_time() );
		}
		if ( ! $end_date ) {
			$end_date = Amapress::end_of_week( amapress_time() );
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_distribution_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		$ret                   = array();
		$cancelled_contrat_ids = $this->getCancelledContratIds();
		if ( empty( $user_id ) || $user_id <= 0 ) {
			$lieu              = $this->getLieu();
			$lieu_substitution = $this->getLieuSubstitution();
			if ( ! empty( $lieu_substitution ) ) {
				$lieu = $lieu_substitution;
			}
			$dist_date_start = $this->getStartDateAndHour();
			$dist_date_end   = $this->getEndDateAndHour();
			$contrats        = $this->getContrats( true );
			foreach ( $contrats as $contrat ) {
				if ( empty( $contrat ) || empty( $contrat->getModel() ) ) {
					continue;
				}

				$contrat_status = in_array( $contrat->ID, $cancelled_contrat_ids ) ? 'cancelled' : '';
				$ret[]          = new Amapress_EventEntry( array(
					'ev_id'     => "dist-{$this->ID}-{$contrat->ID}",
					'date'      => $dist_date_start,
					'date_end'  => $dist_date_end,
					'type'      => 'distribution',
					'category'  => __( 'Distributions', 'amapress' ),
					'priority'  => 30,
					'lieu'      => $lieu,
					'label'     => $contrat->getModelTitle(),
					'cancelled' => in_array( $contrat->ID, $cancelled_contrat_ids ),
					'alt'       => sprintf(
						__( 'Distribution de %1$s à %2$s', 'amapress' ),
						$contrat->getModelTitle(),
						$lieu->getShortName() ),
					'class'     => "agenda-distrib agenda-contrat-{$contrat->getModel()->ID} $contrat_status",
					'icon'      => Amapress::coalesce_icons( amapress_get_avatar_url( $contrat->ID, null, 'produit-thumb', null ), Amapress::getOption( "contrat_{$contrat->getModel()->ID}_icon" ), amapress_get_avatar_url( $contrat->getModel()->ID, null, 'produit-thumb', 'default_contrat.jpg' ) ),
					'href'      => $this->getPermalink()
				) );
			}
		} else {
			$relative_date     = Amapress::start_of_year( Amapress::add_a_month( amapress_time(), - 12 ) );
			$adhesions         = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, $relative_date );
			$lieu              = $this->getLieu();
			$lieu_substitution = $this->getLieuSubstitution();
			if ( ! empty( $lieu_substitution ) ) {
				$lieu = $lieu_substitution;
			}
			$dist_date       = $this->getDate();
			$dist_date_start = $this->getStartDateAndHour();
			$dist_date_end   = $this->getEndDateAndHour();
			$resps           = $this->getResponsablesIds();
			if ( in_array( $user_id, $resps ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'       => "dist-{$this->ID}-resp",
					'date'        => $dist_date_start,
					'date_end'    => $dist_date_end,
					'class'       => 'agenda-distrib agenda-resp-distrib',
					'category'    => __( 'Responsable de distribution', 'amapress' ),
					'lieu'        => $lieu,
					'type'        => 'resp-distribution',
					'priority'    => 45,
					'inscr_types' => [ 'distrib' ],
					'label'       => __( 'Responsable de distribution', 'amapress' ),
					'icon'        => 'dashicons dashicons-universal-access-alt',
					'alt'         => __( 'Vous êtes responsable de distribution à ', 'amapress' ) . $lieu->getShortName(),
					'href'        => $this->getPermalink()
				) );
			}
			$current_user_slot = $this->getSlotInfoForUser( amapress_current_user_id() );
			if ( $current_user_slot ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'       => "dist-{$this->ID}-creneau",
					'date'        => $current_user_slot['date'],
					'date_end'    => $current_user_slot['date_end'],
					'class'       => 'agenda-distrib agenda-creneau-panier',
					'category'    => __( 'Créneau de récupération', 'amapress' ),
					'lieu'        => $lieu,
					'type'        => 'creneau-panier',
					'priority'    => 45,
					'inscr_types' => [ 'distrib-slot', 'distrib-admin-slot' ],
					'label'       => __( 'Créneau de récupération', 'amapress' ),
					'icon'        => 'dashicons dashicons-clock',
					'alt'         => __( 'Créneau pour récupérer vos paniers : ', 'amapress' ) . $current_user_slot['display'],
					'href'        => $this->getPermalink()
				) );
			}
			$gardiens = $this->getGardiensIds( true );
			if ( in_array( $user_id, $gardiens ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'       => "dist-{$this->ID}-gardien",
					'date'        => $dist_date_start,
					'date_end'    => $dist_date_end,
					'class'       => 'agenda-distrib agenda-gardien-panier',
					'category'    => __( 'Gardien de panier', 'amapress' ),
					'lieu'        => $lieu,
					'type'        => 'gardien-panier',
					'priority'    => 45,
					'inscr_types' => [ 'distrib-gardien' ],
					'label'       => __( 'Gardien de panier', 'amapress' ),
					'icon'        => 'dashicons dashicons-portfolio',
					'alt'         => sprintf( __( 'Vous êtes gardien de panier à %s', 'amapress' ), $lieu->getShortName() ),
					'href'        => $this->getPermalink()
				) );
			}
			$contrats = $this->getContratIds( true );
			foreach ( $adhesions as $adhesion ) {
				if ( $adhesion->getLieuId() == $this->getLieuId()
				     && in_array( $adhesion->getContrat_instanceId(), $contrats )
				) {
					$quants = $adhesion->getContrat_quantites( $dist_date );
					if ( empty( $quants ) ) {
						continue;
					}
					$coadh_info = '';
					$coadh_id   = $adhesion->getCoadhIdFromShareCalendarDate( $dist_date );
					if ( $coadh_id ) {
						$coadh = AmapressUser::getBy( $coadh_id );
						if ( ! empty( $coadh ) ) {
							if ( $coadh_id == $user_id ) {
								$coadh_info = __( 'A votre tour - ', 'amapress' );
							} else {
								$coadh_info = sprintf( __( 'Au tour de %s - ', 'amapress' ), $coadh->getDisplayName() );
							}
						}
					}
					$contrat_status = in_array( $adhesion->getContrat_instanceId(), $cancelled_contrat_ids ) ? 'cancelled' : '';
					$ret[]          = new Amapress_EventEntry( array(
						'ev_id'     => "dist-{$this->ID}-{$adhesion->ID}",
						'id'        => $this->ID,
						'date'      => $dist_date_start,
						'date_end'  => $dist_date_end,
						'class'     => "agenda-distrib agenda-contrat-{$adhesion->getModelId()} $contrat_status",
						'type'      => 'distribution',
						'category'  => __( 'Distributions', 'amapress' ),
						'priority'  => 30,
						'lieu'      => $lieu,
						'label'     => $coadh_info . $adhesion->getContrat_instance()->getModelTitle(),
						'icon'      => Amapress::coalesce_icons( Amapress::getOption( "contrat_{$adhesion->getContrat_instance()->getModel()->ID}_icon" ), amapress_get_avatar_url( $adhesion->getContrat_instance()->getModel()->ID, null, 'produit-thumb', 'default_contrat.jpg' ) ),
						'cancelled' => in_array( $adhesion->getContrat_instanceId(), $cancelled_contrat_ids ),
						'alt'       => $coadh_info . sprintf(
								__( 'Distribution de %1$s à %2$s', 'amapress' ),
								$adhesion->getContrat_instance()->getModelTitle(),
								$lieu->getShortName() ),
						'href'      => $this->getPermalink()
					) );
				}
			}
		}

		if ( Amapress::isIntermittenceEnabled() && amapress_is_user_logged_in() ) {
			$status_count = array(
				'me_to_exchange'    => 0,
				'other_to_exchange' => 0,
				'me_exchanged'      => 0,
				'me_recup'          => 0,
			);
			$paniers      = AmapressPaniers::getPanierIntermittents(
				array(
					'date' => $this->getDate(),
					'lieu' => $this->getLieuId(),
				)
			);
			foreach ( $paniers as $panier ) {
				if ( $panier->getAdherentId() == $user_id ) {
					if ( $panier->getStatus() == 'to_exchange' ) {
						$status_count['me_to_exchange'] += 1;
					} else {
						$status_count['me_exchanged'] += 1;
					}
				} elseif ( $panier->getRepreneurId() == $user_id ) {
					$status_count['me_recup'] += 1;
				} else {
					if ( $panier->getStatus() == 'to_exchange' ) {
						$status_count['other_to_exchange'] += 1;
					}
				}
			}

			$date     = $this->getStartDateAndHour();
			$date_end = $this->getEndDateAndHour();
			if ( $status_count['me_to_exchange'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-to-exchange",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => 'agenda-inter agenda-inter-my-to-exchange',
					'type'     => 'intermittence',
					'category' => __( 'Paniers à échanger', 'amapress' ),
					'priority' => 10,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['me_to_exchange'] . '</span> à échanger',
					'icon'     => AMAPRESS__PLUGIN_URL . 'images/panier_mytoexchange.jpg',
					'alt'      => $status_count['me_to_exchange'] . ' à échanger',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}
			if ( $status_count['me_exchanged'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'       => "intermittence-{$this->ID}-exchanged",
					'date'        => $date,
					'date_end'    => $date_end,
					'class'       => 'agenda-inter agenda-inter-exchanged',
					'type'        => 'intermittence',
					'category'    => __( 'Paniers échangé', 'amapress' ),
					'priority'    => 5,
					'inscr_types' => [ 'intermittence' ],
					'lieu'        => $this->getRealLieu(),
					'label'       => '<span class="badge">' . $status_count['me_exchanged'] . '</span>' . __( ' échangé(s)', 'amapress' ),
					'icon'        => AMAPRESS__PLUGIN_URL . 'images/panier_exchanged.jpg',
					'alt'         => $status_count['me_exchanged'] . __( ' échangé(s)', 'amapress' ),
					'href'        => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}

			if ( $status_count['me_recup'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'       => "intermittence-{$this->ID}-recup",
					'date'        => $date,
					'date_end'    => $date_end,
					'class'       => 'agenda-inter agenda-inter-panier-recup',
					'type'        => 'inter-recup',
					'category'    => __( 'Paniers à récupérer', 'amapress' ),
					'priority'    => 15,
					'inscr_types' => [ 'intermittence' ],
					'lieu'        => $this->getRealLieu(),
					'label'       => '<span class="badge">' . $status_count['me_recup'] . '</span>' . __( ' à récupérer', 'amapress' ),
					'icon'        => AMAPRESS__PLUGIN_URL . 'images/panier_torecup.jpg',
					'alt'         => $status_count['me_recup'] . __( ' à récupérer', 'amapress' ),
					'href'        => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}
			if ( $status_count['other_to_exchange'] > 0 ) {
//				$dist = $this;//AmapressPaniers::getDistribution( $this->getDate(), $this->getLieuId() );
//				if ( $dist ) {
				$paniers_url = Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $this->getSlug();
				$ret[]       = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-to-exchange",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => 'agenda-inter agenda-inter-to-exchange',
					'type'     => 'intermittence',
					'category' => __( 'Paniers dispo', 'amapress' ),
					'priority' => 10,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['other_to_exchange'] . '</span>' . __( ' à échanger', 'amapress' ),
					'icon'     => AMAPRESS__PLUGIN_URL . 'images/panier_avail.jpg',
					'alt'      => $status_count['other_to_exchange'] . __( ' à échanger', 'amapress' ),
					'href'     => $paniers_url
				) );
//				}
			}
		}

		return $ret;
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_recall = true ) {
		return Amapress::getPlaceholdersHelpTable( 'distrib-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'de la distribution',
			$additional_helps, $for_recall ? 'recall' : true );
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret = array_merge( parent::getProperties(), [
				'lien-liste-paniers'                => [
					'desc' => __( 'Lien vers la page "Paniers disponibles"', 'amapress' ),
					'func' => function ( AmapressDistribution $dist ) {
						return Amapress::makeLink( Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $dist->getSlug() );
					}
				],
				'mes-echanges'                      => [
					'desc' => __( 'Lien vers la page "Mes paniers échangés"', 'amapress' ),
					'func' => function ( AmapressDistribution $dist ) {
						return Amapress::makeLink( Amapress::getPageLink( 'mes-paniers-intermittents-page' ) . '#' . $dist->getSlug() );
					}
				],
				'lien_desinscription_intermittent'  => [
					'desc' => __( 'Lien vers la page de désinscription de la liste des intermittents', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( amapress_intermittence_desinscription_link() );
					}
				],
				'lien_liste_emargement'             => [
					'desc' => __( 'Lien vers la liste d\'émargement de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getListeEmargementHref() );
					}
				],
				'lieu'                              => [
					'desc' => __( 'Nom du lieu de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return $distrib->getRealLieu()->getTitle();
					}
				],
				'lieu_instruction'                  => [
					'desc' => __( 'Instructions du lieu de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$instructions = $distrib->getLieu()->getInstructions_privee();
						$instructions = str_replace( '[liste-emargement-button]', Amapress::makeLink( $distrib->getListeEmargementHref() ), $instructions );

						return $instructions;
					}
				],
				'lieu_instructions'                 => [
					'desc' => __( 'Instructions du lieu de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$instructions = $distrib->getLieu()->getInstructions_privee();
						$instructions = str_replace( '[liste-emargement-button]', Amapress::makeLink( $distrib->getListeEmargementHref() ), $instructions );

						return $instructions;
					}
				],
				'paniers_instructions_distribution' => [
					'desc' => __( 'Instructions de distribution des paniers', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$ret       = '';
						$model_ids = [];
						foreach ( AmapressPaniers::getPaniersForDist( $distrib->getDate() ) as $panier ) {
							$contrat_instance = $panier->getContrat_instance();
							if ( $contrat_instance && $contrat_instance->getModel() ) {
								if ( in_array( $contrat_instance->getModelId(), $model_ids ) ) {
									continue;
								}

								$model_ids[] = $contrat_instance->getModelId();

								$contrat = $contrat_instance->getModel();
								if ( strlen( trim( wp_strip_all_tags( $contrat->getInstructionsDistribution() ) ) ) > 0 ) {
									$ret .= '<h3>' . $contrat_instance->getModelTitle() . '</h3>';
									$ret .= $contrat->getInstructionsDistribution();
								}
							}
						}

						return $ret;
					}
				],
				'contenu_paniers'                   => [
					'desc' => __( 'Contenu des paniers', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$ret = '';
						foreach ( AmapressPaniers::getPaniersForDist( $distrib->getDate() ) as $panier ) {
							$contrat_instance = $panier->getContrat_instance();
							if ( $contrat_instance && $contrat_instance->hasPanier_CustomContent() ) {
								$had_content = false;
								$lret        = '<h3>' . $contrat_instance->getModelTitle() . '</h3>';
								foreach ( $contrat_instance->getContrat_quantites( $distrib->getDate() ) as $quant ) {
									$contenu = $panier->getContenu( $quant );
									if ( empty( $contenu ) ) {
										continue;
									}
									$had_content = true;
									$lret        .= '<h4>' . $quant->getTitle() . '</h4>';
									$lret        .= '<div>' . $contenu . '</div>';
								}
								if ( $had_content ) {
									$ret .= $lret;
								}
							}
						}

						return $ret;
					}
				],
				'liste_contrats'                    => [
					'desc' => __( 'Liste des contrats à cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return implode( ', ', array_map(
							function ( $c ) {
								/** @var AmapressContrat_instance $c */
								return $c->getModelTitleWithSubName();
							}, $distrib->getContrats()
						) );
					}
				],
				'heure_debut'                       => [
					'desc' => __( 'Heure de début de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return date_i18n( 'H:i', $distrib->getStartDateAndHour() );
					}
				],
				'heure_fin'                         => [
					'desc' => __( 'Heure de fin de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return date_i18n( 'H:i', $distrib->getEndDateAndHour() );
					}
				],
				'jour_date_distrib'                 => [
					'desc' => __( 'Date de cette distribution (par ex, jeudi 22/09/2018)', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return date_i18n( 'l d/m/Y', $distrib->getDate() );
					}
				],
				'date_distrib'                      => [
					'desc' => __( 'Date de cette distribution (par ex, 22/09/2018)', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return date_i18n( 'd/m/Y', $distrib->getDate() );
					}
				],
				'jour_distrib'                      => [
					'desc' => __( 'Jour de cette distribution (par ex, jeudi)', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return date_i18n( 'l', $distrib->getDate() );
					}
				],
				'lien_distrib'                      => [
					'desc' => __( 'Lien url vers la page info de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getPermalink() );
					}
				],
				'lien_distribution_title'           => [
					'desc' => __( 'Lien vers la page info de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getPermalink(), $distrib->getTitle() );
					}
				],
				'lien_distribution_titre'           => [
					'desc' => __( 'Lien vers la page info de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getPermalink(), $distrib->getTitle() );
					}
				],
				'lien_distrib_titre'                => [
					'desc' => __( 'Lien vers la page info de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getPermalink(), $distrib->getTitle() );
					}
				],
				'lien_distrib_title'                => [
					'desc' => __( 'Lien vers la page info de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getPermalink(), $distrib->getTitle() );
					}
				],
				'lien_distribution_title_admin'     => [
					'desc' => __( 'Lien pour éditer les infos de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getAdminEditLink(), $distrib->getTitle() );
					}
				],
				'lien_distribution_titre_admin'     => [
					'desc' => __( 'Lien pour éditer les infos de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getAdminEditLink(), $distrib->getTitle() );
					}
				],
				'lien_distrib_titre_admin'          => [
					'desc' => __( 'Lien pour éditer les infos de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getAdminEditLink(), $distrib->getTitle() );
					}
				],
				'lien_distrib_title_admin'          => [
					'desc' => __( 'Lien pour éditer les infos de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getAdminEditLink(), $distrib->getTitle() );
					}
				],
				'lien_instructions_lieu'            => [
					'desc' => __( 'Lien vers les instructions du lieu', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return Amapress::makeLink( $distrib->getPermalink() . '#instructions-lieu' );
					}
				],
				'resp-inscrits'                     => [
					'desc' => __( 'Nombre de responsable de distribution inscrits pour cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return count( $distrib->getResponsables() );
					}
				],
				'resp-requis'                       => [
					'desc' => __( 'Nombre de responsable de distribution requis pour cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return AmapressDistributions::get_required_responsables( $distrib->ID );
					}
				],
				'resp-manquants'                    => [
					'desc' => __( 'Nombre de responsable de distribution manquants pour cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return AmapressDistributions::get_required_responsables( $distrib->ID ) - count( $distrib->getResponsables() );
					}
				],
				'lien-resp-distrib-ical'        => [
					'desc' => __( 'Lien ical pour les responsables de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return add_query_arg(
							[
								'events_id'    => $distrib->ID,
								'events_types' => 'distribution,resp-distribution'
							],
							Amapress_Agenda_ICAL_Export::get_link_href() );
					}
				],
				'lien-distrib-ical'             => [
					'desc' => __( 'Lien ical de cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return $distrib->getProperty( 'lien-evenement-ical' );
					}
				],
				'liste-resp-email-phone'        => [
					'desc' => __( 'Liste des responsables de distribution avec emails et numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$responsables = $distrib->getResponsables();
						$responsables = array_map( function ( $p ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '<a href="mailto:%1$s">%2$s</a> (%3$s)', 'amapress' ), implode( ',', $p->getAllEmails() ), esc_html( $p->getDisplayName() ), $p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $responsables );

						return '<ul>' . implode( '', $responsables ) . '</ul>';
					}
				],
				'liste-resp-avatar-email-phone' => [
					'desc' => __( 'Liste des responsables de distribution avec avatar, emails et numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$responsables = $distrib->getResponsables();
						$responsables = array_map( function ( $p ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '%1$s <a href="mailto:%2$s">%3$s</a> (%4$s)', 'amapress' ),
									get_avatar( $p->ID ),
									implode( ',', $p->getAllEmails() ),
									esc_html( $p->getDisplayName() ),
									$p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $responsables );

						return '<ul>' . implode( '', $responsables ) . '</ul>';
					}
				],
				'liste-resp-email-phone-bcc'    => [
					'desc' => __( 'Liste des responsables de distribution avec emails et numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$responsables = $distrib->getResponsables();
						$site_email   = Amapress::getOption( 'email_from_mail' );
						$responsables = array_map( function ( $p ) use ( $site_email ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '<a href="mailto:%1$s?bcc=%2$s">%3$s</a> (%4$s)', 'amapress' ), $site_email, implode( ',', $p->getAllEmails() ), esc_html( $p->getDisplayName() ), $p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $responsables );

						return '<ul>' . implode( '', $responsables ) . '</ul>';
					}
				],
				'liste-resp-avatar-phone'       => [
					'desc' => __( 'Liste des responsables de distribution avec avatar et numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$responsables = $distrib->getResponsables();
						$responsables = array_map( function ( $p ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '%1$s %2$s (%3$s)', 'amapress' ),
									get_avatar( $p->ID ),
									esc_html( $p->getDisplayName() ),
									$p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $responsables );

						return '<ul>' . implode( '', $responsables ) . '</ul>';
					}
				],
				'liste-resp-phone'              => [
					'desc' => __( 'Liste des responsables de distribution avec numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$responsables = $distrib->getResponsables();
						$responsables = array_map( function ( $p ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '%1$s (%2$s)', 'amapress' ), esc_html( $p->getDisplayName() ), $p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $responsables );

						return '<ul>' . implode( '', $responsables ) . '</ul>';
					}
				],
				'liste-gardiens-email-phone'    => [
					'desc' => __( 'Liste des gardiens de paniers avec emails et numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$gardiens = $distrib->getGardiens();
						$gardiens = array_map( function ( $p ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '<a href="mailto:%1$s">%2$s</a> (%3$s)', 'amapress' ), implode( ',', $p->getAllEmails() ), esc_html( $p->getDisplayName() ), $p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $gardiens );

						return '<ul>' . implode( '', $gardiens ) . '</ul>';
					}
				],
				'liste-gardiens-email-phone-bcc'    => [
					'desc' => __( 'Liste des gardiens de paniers avec emails et numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$gardiens   = $distrib->getGardiens();
						$site_email = Amapress::getOption( 'email_from_mail' );
						$gardiens   = array_map( function ( $p ) use ( $site_email ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '<a href="mailto:%1$s?bcc=%2$s">%3$s</a> (%4$s)', 'amapress' ), $site_email, implode( ',', $p->getAllEmails() ), esc_html( $p->getDisplayName() ), $p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $gardiens );

						return '<ul>' . implode( '', $gardiens ) . '</ul>';
					}
				],
				'liste-gardiens-phone'              => [
					'desc' => __( 'Liste des gardiens de paniers avec numéros de téléphone', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$gardiens = $distrib->getGardiens();
						$gardiens = array_map( function ( $p ) {
							/** @var AmapressUser $p */
							return '<li>' . sprintf( __( '%1$s (%2$s)', 'amapress' ), esc_html( $p->getDisplayName() ), $p->getTelTo( 'both', false, false, ', ' ) ) . '</li>';
						}, $gardiens );

						return '<ul>' . implode( '', $gardiens ) . '</ul>';
					}
				],
				'liste-paniers-lien'                => [
					'desc' => __( 'Liste des paniers (avec lien) à cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$paniers = AmapressPaniers::getPaniersForDist( $distrib->getDate() );
						$paniers = array_map( function ( $p ) {
							/** @var AmapressPanier $p */
							return '<li>' . Amapress::makeLink( $p->getAdminEditLink(), $p->getTitle() ) . '</li>';
						}, $paniers );

						return '<ul>' . implode( '', $paniers ) . '</ul>';
					}
				],
				'liste-paniers'                     => [
					'desc' => __( 'Liste des paniers à cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$paniers = AmapressPaniers::getPaniersForDist( $distrib->getDate() );
						$paniers = array_map( function ( $p ) {
							/** @var AmapressPanier $p */
							return '<li>' . esc_html( $p->getTitle() ) . '</li>';
						}, $paniers );

						return '<ul>' . implode( '', $paniers ) . '</ul>';
					}
				],
				'nb-paniers-intermittents'          => [
					'desc' => __( 'Nombre de paniers intermittents à cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return count( $distrib->getPaniersIntermittents() );
					}
				],
				'paniers-intermittents'             => [
					'desc' => __( 'Liste des paniers intermittents à cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						return implode( ', ', array_map( function ( $p ) {
							/** @var AmapressIntermittence_panier $p */
							return $p->getPaniersDescription();
						}, $distrib->getPaniersIntermittents() ) );
					}
				],
				'paniers_modifies'                  => [
					'desc' => __( 'Liste des paniers modifiés à cette distribution', 'amapress' ),
					'func' => function ( AmapressDistribution $distrib ) {
						$paniers_modifies = array_merge(
							$distrib->getCancelledPaniers(),
							$distrib->getDelayedToThisPaniers()
						);
						$paniers_modifies = array_map( function ( $p ) {
							/** @var AmapressPanier $p */
							return '<li>' . esc_html( $p->getTitle() ) . '</li>';
						}, $paniers_modifies );

						return '<ul>' . implode( '', $paniers_modifies ) . '</ul>';
					}
				]
			] );

			self::$properties = $ret;
		}

		return self::$properties;
	}

	public
	function getListeEmargementHref() {
		return $this->getPermalink( 'liste-emargement' );
	}

	/** @return AmapressDistribution[] */
	public static function getNextDistribs(
		$date = null, $weeks = 1, $min_weeks = 0
	) {
		if ( ! $date ) {
			$date = amapress_time();
		}
		$next_week_date = Amapress::add_a_week( amapress_time(), $weeks - 1 );
		$dists          = self::get_distributions( Amapress::start_of_week( Amapress::end_of_week( $date ) ), Amapress::end_of_week( $next_week_date ) );

		$num_weeks = count( array_unique( array_map( function ( $d ) {
			/** @var AmapressDistribution $d */
			return Amapress::start_of_week( $d->getDate() );
		}, $dists ) ) );

		if ( $num_weeks < $min_weeks ) {
			$next_dists = self::get_next_distributions( Amapress::start_of_day( Amapress::add_days( Amapress::end_of_week( $next_week_date ), 1 ) ) );
			while ( ! empty( $next_dists ) && $num_weeks < $min_weeks ) {
				$dists[]   = array_shift( $next_dists );
				$num_weeks = count( array_unique( array_map( function ( $d ) {
					/** @var AmapressDistribution $d */
					return Amapress::start_of_week( $d->getDate() );
				}, $dists ) ) );
			}
		}

		return $dists;
	}

	/** @return AmapressIntermittence_panier[] */
	public
	function getPaniersIntermittents() {
		return AmapressPaniers::getPanierIntermittents(
			[
				'date' => $this->getDate()
			]
		);
	}

	/** @return AmapressIntermittence_panier[] */
	public
	function getPaniersIntermittentsDispo() {
		return AmapressPaniers::getPanierIntermittents(
			[
				'date'   => $this->getDate(),
				'status' => 'to_exchange',
			]
		);
	}

	public function setSpecialHeure_debut( $start_hour_date ) {
		if ( empty( $start_hour_date ) ) {
			$this->deleteCustom( 'amapress_distribution_heure_debut_spec' );
		} else {
			$this->setCustom( 'amapress_distribution_heure_debut_spec', $start_hour_date );
		}
	}

	public function setSpecialHeure_fin( $end_hour_date ) {
		if ( empty( $end_hour_date ) ) {
			$this->deleteCustom( 'amapress_distribution_heure_fin_spec' );
		} else {
			$this->setCustom( 'amapress_distribution_heure_fin_spec', $end_hour_date );
		}
	}

	public static function getRespRespDistribEmails( $lieu_id, $event_type = 'distrib' ) {
		return AmapressUser::getEmailsForAmapRole( intval( Amapress::getOption( "resp-$event_type-amap-role" ), $lieu_id ) );
	}

	public static function getResponsablesRespDistribReplyto( $lieu_id, $event_type = 'distrib' ) {
		$emails = self::getRespRespDistribEmails( $lieu_id, $event_type );
		if ( empty( $emails ) ) {
			$emails = self::getRespRespDistribEmails( null, $event_type );
		}
		if ( empty( $emails ) ) {
			return [];
		}

		return 'Reply-To: ' . implode( ',', $emails );
	}

	public function getResponsablesResponsablesDistributionsReplyto( $event_type = 'distrib' ) {
		return self::getResponsablesRespDistribReplyto( $this->getLieuId(), $event_type );
	}

	public function isMemberOf( $user_id ) {
		return $this->isUserMemberOf( $user_id, true );
	}

	public function getMailEventType() {
		return 'distrib';
	}

	public function getMembersIds() {
		return array_merge(
			$this->getMainAdherentsIds( false ),
			$this->getIntermittentIds()
		);
	}

	public function canSubscribe() {
		return $this->canSubscribeType( 'distrib' );
	}

	public function canUnsubscribe() {
		return $this->canUnsubscribeType( 'distrib' );
	}

	public function canCease() {
		return $this->canSubscribeType( 'intermit' );
	}
}
