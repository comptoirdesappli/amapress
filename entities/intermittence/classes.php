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
	public function getPaniersTitles() {
		return ( count( $this->getPanierIds() ) > 1 ? 'Paniers' : 'Panier' ) . ' de ' . $this->getContratTitles() . ' du ' . date_i18n( 'd/m/Y', $this->getDate() );
	}

	public function getPaniersDescription() {
		$quantites = array();
		foreach ( $this->getContrat_instances() as $contrat_instance ) {
			$adhesions = AmapressAdhesion::getUserActiveAdhesions( $this->getAdherent()->ID, $contrat_instance->ID );
			/** @var AmapressAdhesion $adhesion */
			$adhesion    = array_shift( $adhesions );
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
				return 'En attente de validation de l\'échange';
			case 'exchanged':
				return 'Réservé';
			case 'closed':
				return 'Terminé';
			case 'cancelled':
				return 'Annulé';
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
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
		] );

		return 'ok';
	}

	public function validateReprise( $repreneur_id, $force = false ) {
		if ( $this->getRepreneur() != null || 'exch_valid_wait' != $this->getStatus() ) {
			return 'already';
		}

		if ( $force || $this->getStartDateAndHour() < amapress_time() ) {
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
				'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
			] );
		}

		$this->setAsk( array() );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-validation-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-validation-adherent-mail-content' ),
			$this->getAdherentId(),
			$this, [], null, null, [
			'Reply-To: ' . implode( ',', $repreneur->getAllEmails() )
		] );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-validation-repreneur-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-validation-repreneur-mail-content' ),
			$repreneur->ID,
			$this, [], null, null, [
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
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

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-ask-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-ask-adherent-mail-content' ),
			$this->getAdherentId(),
			$this, [], null, null, [
			'Reply-To: ' . implode( ',', $this->getRepreneur()->getAllEmails() )
		] );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-ask-repreneur-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-ask-repreneur-mail-content' ),
			$user_id,
			$this, [], null, null, [
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
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

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-cancel-from-adherent-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-cancel-from-adherent-adherent-mail-content' ),
			$user_id,
			$this, [], null, null, $this->getRepreneur() ? [
			'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
		] : [] );


		if ( $this->getRepreneur() ) {
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject' ),
				Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-content' ),
				$this->getRepreneurId(),
				$this, [], null, null, [
				'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
			] );
		} else {
			foreach ( $this->getAsk() as $ask ) {
				amapress_mail_to_current_user(
					Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject' ),
					Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-content' ),
					$ask['user'],
					$this, [], null, null, [
					'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
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

		$repreneur = $this->getRepreneur();
		$ask       = $this->getAsk();
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

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-adherent-mail-content' ),
			$user_id,
			$this, [], null, null, $repreneur ? [
			'Reply-To: ' . implode( ',', $repreneur->getAllEmails() )
		] : [] );

		if ( $repreneur ) {
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-repreneur-mail-subject' ),
				Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-repreneur-mail-content' ),
				$repreneur->ID,
				$this, [], null, null, [
				'Reply-To: ' . implode( ',', $this->getAdherent()->getAllEmails() )
			] );
		}

		return 'ok';
	}

	public static function getPlaceholdersHelp( $additional_helps = [] ) {
		return Amapress::getPlaceholdersHelpTable( 'intermit-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'du panier intermittent', $additional_helps );
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret = array_merge( parent::getProperties(), [
				'lien-liste-paniers'      => [
					'desc' => 'Lien vers la page "Paniers disponibles"',
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
					'desc' => 'Lien vers la page "Paniers disponibles"',
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
					'desc' => 'Lien vers la page "Mes paniers échangés"',
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
				'date'                    => [
					'desc' => 'Date de distribution de ce panier',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						return date_i18n( 'd/m/Y', $panier->getDate() );
					}
				],
				'panier'                  => [
					'desc' => 'Panier(s) distribué(s) à cette distribution (titre des paniers)',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}

						return $panier->getPaniersTitles();
					}
				],
				'adherent-nom'            => [
					'desc' => 'Nom de l\'adhérent proposant son panier',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->getAdherent() == null ) {
							return '';
						}

						return $panier->getAdherent()->getDisplayName();
					}
				],
				'adherent'                => [
					'desc' => 'Adhérent proposant son panier',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->getAdherent() == null ) {
							return '';
						}

						return $panier->getAdherent()->getDisplay(
							[
								'show_avatar' => 'false',
							]
						);
					}
				],
				'adherent-coords'         => [
					'desc' => 'Coordonnées adhérent proposant son panier',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( $panier->getAdherent() == null ) {
							return '';
						}

						return $panier->getAdherent()->getContacts();
					}
				],
				'adherent-message'        => [
					'desc' => 'Message de mise à disposition du panier de la part de l\'adhérent',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						return $panier->getAdherentMessage();
					}
				],
				'adherent-cancel-message' => [
					'desc' => 'Message d\'annulation de la part de l\'adhérent',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						return $panier->getAdherentCancelMessage();
					}
				],
				'repreneur-nom'           => [
					'desc' => 'Nom du repreneur du panier',
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
				'repreneur'               => [
					'desc' => 'Repreneur du panier',
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
							]
						);
					}
				],
				'repreneur-coords'        => [
					'desc' => 'Coordonnées du repreneur du panier',
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
				'contrat'                 => [
					'desc' => 'Panier(s) distribué(s) à cette distribution (nom des contrats)',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						if ( ! $panier->hasPaniers() ) {
							return '';
						}

						return $panier->getContratTitles();
					}
				],
				'distribution'            => [
					'desc' => 'Titre de la distribution auquel appartient ce panier',
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
				'distribution-href'       => [
					'desc' => 'Url de la distribution auquel appartient ce panier',
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
					'desc' => 'Lien html vers la distribution auquel appartient ce panier',
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
					'desc' => 'Lien vers la page de désinscription de la liste des intermittents',
					'func' => function ( AmapressIntermittence_panier $panier ) {
						//TODO ???
						return '';
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
						'class'    => "agenda-intermittence",
						'type'     => 'intermittence',
						'category' => 'Paniers à échanger',
						'priority' => 10,
						'lieu'     => $this->getRealLieu(),
						'label'    => 'A échanger ' . $this->getPaniersTitles(),
						'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
						'alt'      => 'Votre panier ' . $this->getPaniersTitles() . ' reste à échanger',
						'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
					) );
				} else {
					$ret[] = new Amapress_EventEntry( array(
						'ev_id'    => "intermittence-{$this->ID}-exchanged",
						'date'     => $date,
						'date_end' => $date_end,
						'class'    => "agenda-intermittence",
						'type'     => 'intermittence',
						'category' => 'Paniers échangé',
						'priority' => 5,
						'lieu'     => $this->getRealLieu(),
						'label'    => 'Echange ' . $this->getPaniersTitles(),
						'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
						'alt'      => 'Votre panier ' . $this->getPaniersTitles() . ' a été échanger',
						'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
					) );
				}
			} else if ( $this->getRepreneurId() == $user_id ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-recup",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-inter-panier-recup",
					'type'     => 'inter-recup',
					'category' => 'Paniers à récupérer',
					'priority' => 15,
					'lieu'     => $this->getRealLieu(),
					'label'    => 'Récupérer panier ' . $this->getPaniersTitles(),
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => 'Panier ' . $this->getPaniersTitles() . ' de ' . $this->getAdherent()->getDisplayName() . ' à récupérer',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
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
							'class'    => "agenda-intermittence",
							'type'     => 'intermittence',
							'category' => 'Paniers dispo',
							'priority' => 10,
							'lieu'     => $this->getRealLieu(),
							'label'    => 'A échanger ' . $this->getPaniersTitles(),
							'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
							'alt'      => 'Panier ' . $this->getPaniersTitles() . ' à échanger',
							'href'     => $paniers_url
						) );
					}
				}
			}

//            //user
//            $date = self::get_intermittence_panier_date_and_hour($event->ID, 'start');
//            $date_end = self::get_intermittence_panier_date_and_hour($event->ID, 'end');
//            if ($panier->getAdherent()->ID == $user_id) {
//                if ($panier->getStatus() == 'to_exchange') {
//                    $ret[] = array(
//                        'ev_id' => "intermittence-{$event->ID}-to-exchange",
//                        'date' => $date,
//                        'date_end' => $date_end,
//                        'class' => "agenda-intermittence",
//                        'type' => 'intermittence',
//                        'lieu' => $panier->getLieu()->ID,
//                        'label' => 'A échanger '.$panier->getPanier()->getTitle(),
//                        'icon' => self::get_icon(Amapress::getOption("agenda_intermittence_icon")),
//                        'alt' => 'Votre panier '.$panier->getPanier()->getTitle().' reste à échanger',
//                        'href' => Amapress::getPageLink('mes-paniers-intermittents-page'));
//                } else {
//                    $ret[] = array(
//                        'ev_id' => "intermittence-{$event->ID}-exchanged",
//                        'date' => $date,
//                        'date_end' => $date_end,
//                        'class' => "agenda-intermittence",
//                        'type' => 'intermittence',
//                        'lieu' => $panier->getLieu()->ID,
//                        'label' => 'Echange '.$panier->getPanier()->getTitle(),
//                        'icon' => Amapress::get_icon(Amapress::getOption("agenda_intermittence_icon")),
//                        'alt' => 'Votre panier '.$panier->getPanier()->getTitle().' a été échanger',
//                        'href' => Amapress::getPageLink('mes-paniers-intermittents-page'));
//                }
//            } else if ($panier->getRepreneur() != null && $panier->getRepreneur()->ID == $user_id) {
//                $ret[] = array(
//                    'ev_id' => "intermittence-{$event->ID}-recup",
//                    'date' => $date,
//                    'date_end' => $date_end,
//                    'class' => "agenda-inter-panier-recup",
//                    'type' => 'inter-recup',
//                    'lieu' => $panier->getLieu()->ID,
//                    'label' => 'Récupérer panier '.$panier->getPanier()->getTitle(),
//                    'icon' => Amapress::get_icon(Amapress::getOption("agenda_intermittence_icon")),
//                    'alt' => 'Panier '.$panier->getPanier()->getTitle().' de '.$panier->getAdherent()->getDisplayName().' à récupérer',
//                    'href' => Amapress::getPageLink('mes-paniers-intermittents-page'));
//            }
		}

		return $ret;
	}
}