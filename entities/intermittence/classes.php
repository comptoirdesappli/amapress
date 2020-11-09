<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressIntermittence_panier extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_inter_panier';
	const POST_TYPE = 'intermittence_panier';

	const TO_EXCHANGE = 'to_exchange';
	const EXCHANGE_VALIDATE_WAIT = 'exch_valid_wait';
	const EXCHANGED = 'exchanged';
	const CLOSED = 'closed';
	const CANCELLED = 'cancelled';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressIntermittence_panier
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressIntermittence_panier' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressIntermittence_panier( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getStartDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getRealLieu()->getHeure_debut() );
	}

	public function getEndDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getRealLieu()->getHeure_fin() );
	}

	public function getDate() {
		return $this->getCustom( 'amapress_intermittence_panier_date' );
	}

	/** @return bool */
	public function hasPaniers() {
		return ! empty( $this->getPanierIds() );
	}

	/** @return string */
	public function getPaniersTitles( $show_date = true, $show_desc = false ) {
		return ( count( $this->getPanierIds() ) > 1 ? __( 'Paniers', 'amapress' ) : __( 'Panier', 'amapress' ) )
		       . __( ' de ', 'amapress' ) . ( $show_desc ? $this->getPaniersDescription() : $this->getContratTitles() )
		       . ( $show_date ? ' du ' . date_i18n( 'd/m/Y', $this->getDate() ) : '' );
	}

	public function getPaniersDescription() {
		$quantites = array();
		foreach ( $this->getContrat_instances() as $contrat_instance ) {
			$adhesions = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $this->getAdherent()->ID, $contrat_instance->ID );
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

	/** @return AmapressPanier[] */
	public function getPaniers() {
		return $this->getCustomAsEntityArray( 'amapress_intermittence_panier_panier', 'AmapressPanier' );
	}

	/** @return int[] */
	public function getPanierIds() {
		return $this->getCustomAsIntArray( 'amapress_intermittence_panier_panier' );
	}

	/** @return AmapressContrat_instance[] */
	public function getContrat_instances() {
		return $this->getCustomAsEntityArray(
			'amapress_intermittence_panier_contrat_instance',
			'AmapressContrat_instance' );
	}

	/** @return int[] */
	public function getContrat_instanceIds() {
		return $this->getCustomAsIntArray( 'amapress_intermittence_panier_contrat_instance' );
	}

	/** @return string */
	public function getContratTitles() {
		return implode( ', ', array_map(
			function ( $p ) {
				/** @var AmapressContrat_instance $p */
				return $p->getModelTitle();
			},
			$this->getContrat_instances()
		) );
	}


	/** @return AmapressUser */
	public function getRepreneur() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_repreneur', 'AmapressUser' );
	}

	/** @return int */
	public function getRepreneurId() {
		return $this->getCustomAsInt( 'amapress_intermittence_panier_repreneur' );
	}

	public function setRepreneur( $value ) {
		$this->setCustom( 'amapress_intermittence_panier_repreneur', $value );
	}

	/** @return AmapressUser */
	public function getAdherent() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_adherent', 'AmapressUser' );
	}

	/** @return int */
	public function getAdherentId() {
		return $this->getCustomAsInt( 'amapress_intermittence_panier_adherent' );
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_lieu', 'AmapressLieu_distribution' );
	}

	/** @return int */
	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_intermittence_panier_lieu' );
	}

	/** @return AmapressDistribution */
	public function getDistribution() {
		return AmapressPaniers::getDistribution( $this->getDate(), $this->getLieuId() );
	}

	/** @return AmapressLieu_distribution */
	public function getRealLieu() {
		$distrib = $this->getDistribution();
		if ( ! $distrib ) {
			return $this->getLieu();
		}

		return $distrib->getRealLieu();
	}

	public function getStatusDisplay() {
		$this->ensure_init();
		switch ( $this->getStatus() ) {

			case 'to_exchange':
				return 'A réserver';
			case 'exch_valid_wait':
				return __( 'En attente de validation de l\'échange', 'amapress' );
			case 'exchanged':
				return __( 'Réservé', 'amapress' );
			case 'closed':
				return __( 'Terminé', 'amapress' );
			case 'cancelled':
				return __( 'Annulé', 'amapress' );
			default:
				return $this->getStatus();
		}
	}

	public function getStatus() {
		return $this->getCustom( 'amapress_intermittence_panier_status' );
	}

	public function setStatus( $value ) {
		$this->setCustom( 'amapress_intermittence_panier_status', $value );
	}

	public function getAdherentMessage() {
		$this->ensure_init();

		return $this->getCustom( 'amapress_intermittence_panier_adh_message' );
	}

	public function getAdherentCancelMessage() {
		$this->ensure_init();

		return $this->getCustom( 'amapress_intermittence_panier_adh_cancel_message' );
	}

	public function setAdherentMessage( $value ) {
		$this->setCustom( 'amapress_intermittence_panier_adh_message', $value );
	}

	public function setAdherentCancelMessage( $value ) {
		$this->setCustom( 'amapress_intermittence_panier_adh_cancel_message', $value );
	}

	public function getMessage() {
		switch ( $this->getStatus() ) {
			case self::TO_EXCHANGE:
				return $this->getAdherentMessage();
			case self::EXCHANGE_VALIDATE_WAIT:
				return $this->getAdherentMessage();
			case self::EXCHANGED:
				return $this->getAdherentMessage();
			case self::CANCELLED:
				return $this->getAdherentCancelMessage();
		}

		return null;
	}

	public function rejectReprise( $repreneur_id ) {
		if ( $this->getRepreneur() != null || 'exch_valid_wait' != $this->getStatus() ) {
			return 'already';
		}

		if ( $this->getStartDateAndHour() < amapress_time() ) {
			return 'too_late';
		}

		$ask = $this->getAsk();
		if ( ! isset( $ask[ $repreneur_id ] ) ) {
			return 'unknown';
		}

		$repreneur = AmapressUser::getBy( $ask[ $repreneur_id ]['user'] );
		if ( ! $repreneur ) {
			return 'unknown';
		}

		unset( $ask[ $repreneur_id ] );
		$this->setAsk( $ask );
		if ( empty( $ask ) ) {
			$this->setStatus( self::TO_EXCHANGE );
		}

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-rejet-repreneur-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-rejet-repreneur-mail-content' ),
			$repreneur->ID,
			$this, [], null, null, [
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
		] );

		return 'ok';
	}

	public function validateReprise( $repreneur_id, $force = false ) {
		if ( $this->getRepreneur() != null || 'exch_valid_wait' != $this->getStatus() ) {
			return 'already';
		}

		if ( ! $force && $this->getStartDateAndHour() < amapress_time() ) {
			return 'too_late';
		}

		$ask = $this->getAsk();
		if ( ! isset( $ask[ $repreneur_id ] ) ) {
			return 'unknown';
		}

		$repreneur = AmapressUser::getBy( $ask[ $repreneur_id ]['user'] );
		if ( ! $repreneur ) {
			return 'unknown';
		}

		$this->setRepreneur( $repreneur->ID );
		$this->setStatus( self::EXCHANGED );

		unset( $ask[ $repreneur_id ] );
		foreach ( $ask as $user ) {
			$rejected_repreneur = AmapressUser::getBy( $user['user'] );

			if ( ! $rejected_repreneur ) {
				continue;
			}

			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-panier-repris-rejet-repreneur-mail-subject' ),
				Amapress::getOption( 'intermittence-panier-repris-rejet-repreneur-mail-content' ),
				$rejected_repreneur->ID,
				$this, [], null, null, [
				'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
			] );
		}

		$this->setAsk( array() );

		$attachments = $this->getIntermittenceICal( $this->getAdherentId() );
		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-validation-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-validation-adherent-mail-content' ),
			$this->getAdherentId(),
			$this, $attachments, null, null, [
			'Reply-To: ' . implode( ',', $repreneur->getAllEmails() )
		] );

		$attachments = $this->getIntermittenceICal( $repreneur->ID );
		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-validation-repreneur-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-validation-repreneur-mail-content' ),
			$repreneur->ID,
			$this, $attachments, null, null, [
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
		] );

		return 'ok';
	}

	public function getAsk() {
		return Amapress::get_post_meta_array( $this->ID, 'amapress_intermittence_panier_ask' );
	}

	private $last_ask_id;

	public function setLastAskId( $user_id ) {
		$this->last_ask_id = $user_id;
	}

	public function setAsk( $ask ) {
		update_post_meta( $this->ID, 'amapress_intermittence_panier_ask', $ask );
	}

	public function askReprise( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		if ( $this->getRepreneur() != null || 'to_exchange' != $this->getStatus() ) {
			return 'already';
		}

		if ( $this->getStartDateAndHour() < amapress_time() ) {
			return 'too_late';
		}

		$ask             = $this->getAsk();
		$ask[ $user_id ] = array(
			'user' => $user_id,
			'date' => amapress_time()
		);
		$this->setAsk( $ask );

		$this->setStatus( 'exch_valid_wait' );

		$this->setLastAskId( $user_id );

		$asker = AmapressUser::getBy( $user_id );
		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-ask-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-ask-adherent-mail-content' ),
			$this->getAdherentId(),
			$this, [], null, null, [
			$asker ? 'Reply-To: ' . implode( ',', $asker->getAllEmails() ) : ''
		] );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-ask-repreneur-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-ask-repreneur-mail-content' ),
			$user_id,
			$this, [], null, null, [
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
		] );

		return 'ok';
	}

	public function cancelFromAdherent( $user_id = null, $message = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		if ( $this->getStartDateAndHour() < amapress_time() ) {
			return 'too_late';
		}

		$this->setStatus( 'cancelled' );
		$this->setAdherentCancelMessage( $message );
		$this->setAsk( array() );

		$attachments = $this->getIntermittenceICal( $user_id, true );
		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-cancel-from-adherent-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-cancel-from-adherent-adherent-mail-content' ),
			$user_id,
			$this, $attachments, null, null, $this->getRepreneur() ? [
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
		] : [] );


		if ( $this->getRepreneur() ) {
			$attachments = $this->getIntermittenceICal( $this->getRepreneurId(), true );
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject' ),
				Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-content' ),
				$this->getRepreneurId(),
				$this, $attachments, null, null, [
				'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
			] );
		} else {
			foreach ( $this->getAsk() as $ask ) {
				$attachments = $this->getIntermittenceICal( $ask['user'], true );
				amapress_mail_to_current_user(
					Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject' ),
					Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-content' ),
					$ask['user'],
					$this, $attachments, null, null, [
					'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
				] );
			}
		}

		return 'ok';
	}

	public function cancelFromRepreneur( $user_id = null, $message = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		if ( $this->getStartDateAndHour() < amapress_time() ) {
			return 'too_late';
		}

		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$repreneur = $this->getRepreneur();
		$ask       = $this->getAsk();
		if ( isset( $ask[ $user_id ] ) ) {
			$repreneur = AmapressUser::getBy( $user_id );
		}

		if ( $repreneur ) {
			$this->setLastAskId( $repreneur->ID );
		}

		$adherent_subject = amapress_replace_mail_placeholders(
			Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-adherent-mail-subject' ),
			$this->getAdherent(), $this );
		$adherent_message = amapress_replace_mail_placeholders(
			Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-adherent-mail-content' ),
			$this->getAdherent(), $this );
		if ( $repreneur ) {
			$repreneur_subject = amapress_replace_mail_placeholders(
				Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-repreneur-mail-subject' ),
				$repreneur, $this );
			$repreneur_message = amapress_replace_mail_placeholders(
				Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-repreneur-mail-content' ),
				$repreneur, $this );
		}

		if ( isset( $ask[ $user_id ] ) ) {
			unset( $ask[ $user_id ] );
			if ( empty( $ask ) ) {
				$this->setStatus( 'to_exchange' );
			}
			$this->setAsk( $ask );
			$repreneur = AmapressUser::getBy( $user_id );
		} else if ( $repreneur && $repreneur->ID == $user_id ) {
			$this->setRepreneur( null );
			$this->setStatus( 'to_exchange' );
		} else {
			return 'unknown';
		}

		$attachments = $this->getIntermittenceICal( $this->getAdherentId(), true );
		amapress_wp_mail( implode( ',', $this->getAdherent()->getAllEmails() ), $adherent_subject, $adherent_message,
			$repreneur ? [
				'Reply-To: ' . implode( ',', $repreneur->getAllEmails() )
			] : [], $attachments, null, null );

		if ( $repreneur ) {
			$attachments = $this->getIntermittenceICal( $repreneur->ID, true );
			amapress_wp_mail(
				implode( ',', $repreneur->getAllEmails() ),
				$repreneur_subject, $repreneur_message, [
				'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmailsWithCoAdherents() )
			], $attachments, null, null );
		}

		return 'ok';
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_recall = true ) {
		return Amapress::getPlaceholdersHelpTable( 'intermit-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'du panier intermittent',
			$additional_helps, $for_recall ? 'recall' : true );
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret = array_merge( parent::getProperties(), [
				'lien-liste-paniers'               => [
					'desc' => __( 'Lien vers la page "Paniers disponibles"', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}
						$dist = $panier->getDistribution();
						if ( $dist == null ) {
							return '';
						}

						return Amapress::makeLink( Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $dist->getSlug() );
					}
				],
				'liste-paniers'                    => [
					'desc' => __( 'Lien vers la page "Paniers disponibles"', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}
						$dist = $panier->getDistribution();
						if ( $dist == null ) {
							return '';
						}

						return Amapress::makeLink( Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $dist->getSlug() );
					}
				],
				'mes-echanges'                     => [
					'desc' => __( 'Lien vers la page "Mes paniers échangés"', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}
						$dist = $panier->getDistribution();
						if ( $dist == null ) {
							return '';
						}

						return Amapress::makeLink( Amapress::getPageLink( 'mes-paniers-intermittents-page' ) . '#' . $dist->getSlug() );
					}
				],
				'date'                             => [
					'desc' => __( 'Date de distribution de ce panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						return date_i18n( 'd/m/Y', $panier->getDate() );
					}
				],
				'panier'                           => [
					'desc' => __( 'Panier(s) distribué(s) à cette distribution (titre des paniers)', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}

						return $panier->getPaniersTitles( true, false );
					}
				],
				'panier-desc'                      => [
					'desc' => __( 'Panier(s) distribué(s) à cette distribution (avec contenu des paniers)', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}

						return $panier->getPaniersTitles( false, true );
					}
				],
				'panier-desc-date'                 => [
					'desc' => __( 'Panier(s) distribué(s) à cette distribution (avec contenu des paniers et date)', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}

						return $panier->getPaniersTitles( true, true );
					}
				],
				'adherent-pseudo'                  => [
					'desc' => __( 'Pseudo de l\'adhérent proposant son panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->getAdherent() == null ) {
							return '';
						}

						return $panier->getAdherent()->getUser()->display_name;
					}
				],
				'adherent-nom'                     => [
					'desc' => __( 'Nom de l\'adhérent proposant son panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->getAdherent() == null ) {
							return '';
						}

						return $panier->getAdherent()->getDisplayName();
					}
				],
				'adherent'                         => [
					'desc' => __( 'Adhérent proposant son panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->getAdherent() == null ) {
							return '';
						}

						return $panier->getAdherent()->getDisplay(
							[
								'show_avatar' => 'false',
								'show_roles'  => 'false',
							]
						);
					}
				],
				'adherent-coords'                  => [
					'desc' => __( 'Coordonnées adhérent proposant son panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->getAdherent() == null ) {
							return '';
						}

						return $panier->getAdherent()->getContacts();
					}
				],
				'adherent-message'                 => [
					'desc' => __( 'Message de mise à disposition du panier de la part de l\'adhérent', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						return $panier->getAdherentMessage();
					}
				],
				'adherent-cancel-message'          => [
					'desc' => __( 'Message d\'annulation de la part de l\'adhérent', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						return $panier->getAdherentCancelMessage();
					}
				],
				'repreneur-pseudo'                 => [
					'desc' => __( 'Pseudo du repreneur du panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->last_ask_id ) {
							$user = AmapressUser::getBy( $panier->last_ask_id );
						} else {
							$user = $panier->getRepreneur();
						}

						if ( ! $user ) {
							return '';
						}

						return $user->getUser()->display_name;
					}
				],
				'repreneur-nom'                    => [
					'desc' => __( 'Nom du repreneur du panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->last_ask_id ) {
							$user = AmapressUser::getBy( $panier->last_ask_id );
						} else {
							$user = $panier->getRepreneur();
						}

						if ( ! $user ) {
							return '';
						}

						return $user->getDisplayName();
					}
				],
				'repreneur'                        => [
					'desc' => __( 'Repreneur du panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->last_ask_id ) {
							$user = AmapressUser::getBy( $panier->last_ask_id );
						} else {
							if ( $panier->getRepreneur() == null ) {
								return '';
							}
							$user = $panier->getRepreneur();
						}

						return $user->getDisplay(
							[
								'show_avatar' => 'false',
								'show_roles'  => 'false',
							]
						);
					}
				],
				'repreneur-coords'                 => [
					'desc' => __( 'Coordonnées du repreneur du panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->last_ask_id ) {
							$user = AmapressUser::getBy( $panier->last_ask_id );
						} else {
							if ( $panier->getRepreneur() == null ) {
								return '';
							}
							$user = $panier->getRepreneur();
						}

						return $user->getContacts();
					}
				],
				'contrat'                          => [
					'desc' => __( 'Panier(s) distribué(s) à cette distribution (nom des contrats)', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}

						return $panier->getContratTitles();
					}
				],
				'distribution'                     => [
					'desc' => __( 'Titre de la distribution auquel appartient ce panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}
						$dist = $panier->getDistribution();
						if ( $dist == null ) {
							return '';
						}

						return $dist->getTitle();
					}
				],
				'distribution-href'                => [
					'desc' => __( 'Url de la distribution auquel appartient ce panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}
						$dist = $panier->getDistribution();
						if ( $dist == null ) {
							return '';
						}

						return $dist->getPermalink();
					}
				],
				'distribution-link'                => [
					'desc' => __( 'Lien html vers la distribution auquel appartient ce panier', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}
						$dist = $panier->getDistribution();
						if ( $dist == null ) {
							return '';
						}

						return Amapress::makeLink( $dist->getPermalink(), $dist->getTitle() );
					}
				],
				'lien_desinscription_intermittent' => [
					'desc' => __( 'Lien vers la page de désinscription de la liste des intermittents', 'amapress' ),
					'func' => function ( AmapressIntermittence_panier $panier ) {
						return Amapress::makeLink( amapress_intermittence_desinscription_link() );
					}
				],
			] );

			foreach ( AmapressUser::getProperties() as $prop_name => $prop ) {
				$pn         = "adherent-$prop_name";
				$ret[ $pn ] = [
					'desc' => $prop['desc'] . ' de \'adhérent',
					'func' => function ( AmapressIntermittence_panier $panier ) use ( $pn ) {
						return $panier->getAdherent()->getProperty( substr( $pn, strlen( 'adherent-' ) ) );
					}
				];
				$pn         = "repreneur-$prop_name";
				$ret[ $pn ] = [
					'desc' => $prop['desc'] . ' du repreneur',
					'func' => function ( AmapressIntermittence_panier $panier ) use ( $pn ) {
						if ( ! $panier->getRepreneurId() ) {
							return '';
						}

						return $panier->getRepreneur()->getProperty( substr( $pn, strlen( 'repreneur-' ) ) );
					}
				];
			}
			self::$properties = $ret;
		}

		return self::$properties;
	}

	/** @return AmapressIntermittence_panier[] */
	public
	static function get_paniers_intermittents(
		$start_date, $end_date, $order = 'NONE'
	) {
		return self::query_events(
			array(
				array(
					'key'     => 'amapress_intermittence_panier_date',
					'value'   => [ Amapress::start_of_day( $start_date ), Amapress::end_of_day( $end_date ) ],
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	public
	static function get_next_panier_intermittent(
		$date = null, $order = 'NONE'
	) {
		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_intermittence_panier_date',
					'value'   => Amapress::start_of_day( $date ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return Amapress_EventEntry */
	public
	function get_related_events(
		$user_id
	) {
		$ret = array();
		if ( empty( $user_id ) || $user_id <= 0 ) {

		} else {
			$date     = $this->getStartDateAndHour();
			$date_end = $this->getEndDateAndHour();
			if ( $this->getAdherentId() == $user_id ) {
				if ( $this->getStatus() == 'to_exchange' ) {
					$ret[] = new Amapress_EventEntry( array(
						'ev_id'    => "intermittence-{$this->ID}-to-exchange",
						'date'     => $date,
						'date_end' => $date_end,
						'class'    => "agenda-inter agenda-inter-my-to-exchange",
						'type'     => 'intermittence',
						'category' => __( 'Paniers à échanger', 'amapress' ),
						'priority' => 10,
						'lieu'     => $this->getRealLieu(),
						'label'    => __( 'A échanger ', 'amapress' ) . $this->getPaniersTitles( false ),
						'icon'     => AMAPRESS__PLUGIN_URL . 'images/panier_mytoexchange.jpg',
						'alt'      => sprintf( __( 'Votre panier %s reste à échanger', 'amapress' ), $this->getPaniersTitles( true, true ) ),
						'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
					) );
				} else {
					$ret[] = new Amapress_EventEntry( array(
						'ev_id'    => "intermittence-{$this->ID}-exchanged",
						'date'     => $date,
						'date_end' => $date_end,
						'class'    => "agenda-inter agenda-inter-exchanged",
						'type'     => 'intermittence',
						'category' => __( 'Paniers échangé', 'amapress' ),
						'priority' => 5,
						'lieu'     => $this->getRealLieu(),
						'label'    => __( 'Echange ', 'amapress' ) . $this->getPaniersTitles( false ),
						'icon'     => AMAPRESS__PLUGIN_URL . 'images/panier_exchanged.jpg',
						'alt'      => sprintf( __( 'Votre panier %s a été échanger', 'amapress' ), $this->getPaniersTitles( true, true ) ),
						'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
					) );
				}
			} else if ( $this->getRepreneurId() == $user_id ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'       => "intermittence-{$this->ID}-recup",
					'date'        => $date,
					'date_end'    => $date_end,
					'class'       => "agenda-inter agenda-inter-panier-recup",
					'type'        => 'inter-recup',
					'category'    => __( 'Paniers à récupérer', 'amapress' ),
					'priority'    => 15,
					'inscr_types' => [ 'intermittence' ],
					'lieu'        => $this->getRealLieu(),
					'label'       => __( 'Récupérer panier ', 'amapress' ) . $this->getPaniersTitles( false ),
					'icon'        => AMAPRESS__PLUGIN_URL . 'images/panier_torecup.jpg',
					'alt'         => sprintf( __( 'Panier %s de %s à récupérer', 'amapress' ), $this->getPaniersTitles( true, true ), $this->getAdherent()->getDisplayName() ),
					'href'        => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			} else {
				if ( $this->getStatus() == 'to_exchange' ) {
					$dist = $this->getDistribution();
					if ( $dist ) {
						$paniers_url = Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $dist->getSlug();
						$ret[]       = new Amapress_EventEntry( array(
							'ev_id'    => "intermittence-{$this->ID}-to-exchange",
							'date'     => $date,
							'date_end' => $date_end,
							'class'    => "agenda-inter agenda-inter-to-exchange",
							'type'     => 'intermittence',
							'category' => __( 'Paniers dispo', 'amapress' ),
							'priority' => 10,
							'lieu'     => $this->getRealLieu(),
							'label'    => __( 'A échanger ', 'amapress' ) . $this->getPaniersTitles( false ),
							'icon'     => AMAPRESS__PLUGIN_URL . 'images/panier_avail.jpg',
							'alt'      => sprintf( __( 'Panier %s à échanger', 'amapress' ), $this->getPaniersTitles( true, true ) ),
							'href'     => $paniers_url
						) );
					}
				}
			}
		}

		return $ret;
	}

	public static function getRespIntermittentsEmails( $lieu_id ) {
		return AmapressUser::getEmailsForAmapRole( intval( Amapress::getOption( 'resp-intermittents-amap-role' ), $lieu_id ) );
	}

	public static function getResponsableIntermittentsReplyto( $lieu_id ) {
		$emails = self::getRespIntermittentsEmails( $lieu_id );
		if ( empty( $emails ) ) {
			$emails = self::getRespIntermittentsEmails( null );
		}
		if ( empty( $emails ) ) {
			return [];
		}

		return 'Reply-To: ' . implode( ',', $emails );
	}

	public static function getStats(
		$start_date, $end_date
	) {
		$ret = [];

		$start_date  = Amapress::start_of_month( $start_date );
		$end_date    = Amapress::end_of_month( $end_date );
		$take_months = [];
		$start_month = $start_date;
		while ( $start_month <= $end_date ) {
			$take_months[] = date_i18n( 'm/Y', $start_month );
			$start_month   = Amapress::add_a_month( $start_month );
		}

		$user_names       = [];
		$user_sort_names  = [];
		$user_lieux       = [];
		$user_takens      = [];
		$user_exchangeds  = [];
		$month_sort       = [];
		$month_takens     = [];
		$month_exchangeds = [];


		$stored = get_option( 'amapress_inter_stats' );
		if ( ! empty( $stored ) ) {
			$month_sort       = array_merge( $stored['month_sort'] );
			$month_takens     = array_merge( $stored['month_takens'] );
			$month_exchangeds = array_merge( $stored['month_exchangeds'] );
		}
		$paniers = self::get_paniers_intermittents( $start_date, $end_date, 'ASC' );
		foreach ( $paniers as $panier ) {
			$month = date_i18n( 'm/Y', $panier->getDate() );
			unset( $month_sort[ $month ] );
			unset( $month_takens[ $month ] );
			unset( $month_exchangeds[ $month ] );
		}
		foreach ( $month_sort as $month => $cnt ) {
			if ( ! in_array( $month, $take_months ) ) {
				unset( $month_sort[ $month ] );
			}
		}
		foreach ( $month_takens as $month => $cnt ) {
			if ( ! in_array( $month, $take_months ) ) {
				unset( $month_takens[ $month ] );
			}
		}
		foreach ( $month_exchangeds as $month => $cnt ) {
			if ( ! in_array( $month, $take_months ) ) {
				unset( $month_exchangeds[ $month ] );
			}
		}

		foreach ( $paniers as $panier ) {
			foreach ( [ $panier->getAdherent(), $panier->getRepreneur() ] as $r ) {
				if ( empty( $r ) ) {
					continue;
				}
				$month = date_i18n( 'm/Y', $panier->getDate() );
				if ( ! isset( $month_takens[ $month ] ) ) {
					$month_takens[ $month ] = 0;
				}
				if ( ! isset( $month_exchangeds[ $month ] ) ) {
					$month_exchangeds[ $month ] = 0;
				}
				if ( ! isset( $month_sort[ $month ] ) ) {
					$month_sort[ $month ] = date_i18n( 'Y-m', $panier->getDate() );
				}
				if ( $r->ID == $panier->getAdherentId() ) {
					$month_exchangeds[ $month ] += 1;
				} else if ( $r->ID == $panier->getRepreneurId() ) {
					$month_takens[ $month ] += 1;
				}

				$rid = strval( $r->ID );
				if ( ! isset( $user_takens[ $rid ] ) ) {
					$user_takens[ $rid ] = [];
				}
				if ( ! isset( $user_exchangeds[ $rid ] ) ) {
					$user_exchangeds[ $rid ] = [];
				}
				if ( $r->ID == $panier->getAdherentId() ) {
					$user_exchangeds[ $rid ][] = Amapress::makeLink( $panier->getAdminEditLink(), date_i18n( 'd/m/Y', $panier->getDate() ) );
				} else if ( $r->ID == $panier->getRepreneurId() ) {
					$user_takens[ $rid ][] = Amapress::makeLink( $panier->getAdminEditLink(), date_i18n( 'd/m/Y', $panier->getDate() ) );
				}
				if ( ! isset( $user_names[ $rid ] ) ) {
					$user_names[ $rid ] = Amapress::makeLink( $r->getEditLink(), $r->getDisplayName() . '(' . $r->getUser()->user_email . ')' );
				}
				if ( ! isset( $user_sort_names[ $rid ] ) ) {
					$user_sort_names[ $rid ] = $r->getSortableDisplayName();
				}
				if ( ! isset( $user_lieux[ $rid ] ) ) {
					$user_lieux[ $rid ] = $panier->getLieu()->getLieuTitle();
				}
			}
		}

		if ( ! empty( $stored ) ) {
			foreach ( $month_sort as $month => $cnt ) {
				$stored['month_sort'][ $month ] = $cnt;
			}
			foreach ( $month_takens as $month => $cnt ) {
				$stored['month_takens'][ $month ] = $cnt;
			}
			foreach ( $month_exchangeds as $month => $cnt ) {
				$stored['month_exchangeds'][ $month ] = $cnt;
			}
		} else {
			$stored['month_sort']       = $month_sort;
			$stored['month_takens']     = $month_takens;
			$stored['month_exchangeds'] = $month_exchangeds;
		}
		update_option( 'amapress_inter_stats', $stored );

		$lines = [];
		foreach ( $user_names as $user_id => $user_name ) {
			$lines[] = array(
				'user'            => $user_name,
				'sort_user'       => $user_sort_names[ $user_id ],
				'lieu'            => $user_lieux[ $user_id ],
				'exchanged_dates' => implode( ', ', $user_exchangeds[ $user_id ] ),
				'exchanged_nb'    => count( $user_exchangeds[ $user_id ] ),
				'taken_dates'     => implode( ', ', $user_takens[ $user_id ] ),
				'taken_nb'        => count( $user_takens[ $user_id ] ),
			);
		}
		$ret['users'] = $lines;

		$lines = [];
		foreach ( $month_takens as $month => $cnt ) {
			$lines[] = [
				'month'        => Amapress::makeLink( admin_url( 'edit.php?post_type=amps_inter_panier&amapress_date=' . $month_sort[ $month ] ), $month, true, true ),
				'sort_month'   => $month_sort[ $month ],
				'exchanged_nb' => $month_exchangeds[ $month ],
				'taken_nb'     => $month_takens[ $month ],
			];
		}
		$ret['months'] = $lines;

		return $ret;
	}

	public function getIntermittenceICal( $user_id, $is_cancel = false ) {
		$attachments = [];
		$event       = null;
		foreach ( $this->get_related_events( $user_id ) as $ev ) {
			/** @var Amapress_EventEntry $ev */
			if ( in_array( 'intermittence', $ev->getIncriptionsTypes() ) ) {
				$event = $ev;
			}
		}
		if ( $event ) {
			$attachments[] = Amapress::createICalForEventsAsMailAttachment( $event, $is_cancel );
		}

		return $attachments;
	}
}