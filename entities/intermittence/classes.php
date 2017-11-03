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

	/** @return AmapressPanier */
	public function getPanier() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_panier', 'AmapressPanier' );
	}

	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_contrat_instance', 'AmapressContrat_instance' );
	}

	/** @return AmapressUser */
	public function getRepreneur() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_repreneur', 'AmapressUser' );
	}

	public function setRepreneur( $value ) {
		$this->setCustom( 'amapress_intermittence_panier_repreneur', $value );
	}

	/** @return AmapressUser */
	public function getAdherent() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_adherent', 'AmapressUser' );
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_intermittence_panier_lieu', 'AmapressLieu_distribution' );
	}

	/** @return AmapressDistribution */
	public function getDistribution() {
		return AmapressPaniers::getDistribution( $this->getDate(), $this->getLieu()->ID );
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
			$this );

		return 'ok';
	}

	public function validateReprise( $repreneur_id ) {
		if ( $this->getRepreneur() != null || 'exch_valid_wait' != $this->getStatus() ) {
			return 'already';
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
		$this->setAsk( array() );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-validation-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-validation-adherent-mail-content' ),
			$this->getAdherent()->ID,
			$this );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-validation-repreneur-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-validation-repreneur-mail-content' ),
			$repreneur->ID,
			$this );

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
			$this->getAdherent()->ID,
			$this );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-repris-ask-repreneur-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-repris-ask-repreneur-mail-content' ),
			$user_id,
			$this );

		return 'ok';
	}

	public function cancelFromAdherent( $user_id = null, $message = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$this->setStatus( 'cancelled' );
		$this->setAdherentCancelMessage( $message );
		$this->setAsk( array() );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-cancel-from-adherent-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-cancel-from-adherent-adherent-mail-content' ),
			$user_id,
			$this );


		if ( $this->getRepreneur() ) {
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject' ),
				Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-content' ),
				$this->getRepreneur()->ID,
				$this );
		} else {
			foreach ( $this->getAsk() as $ask ) {
				amapress_mail_to_current_user(
					Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject' ),
					Amapress::getOption( 'intermittence-panier-cancel-from-adherent-repreneur-mail-content' ),
					$ask['user'],
					$this );
			}
		}

		return 'ok';
	}

	public function cancelFromRepreneur( $user_id = null, $message = null ) {
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$repreneur = $this->getRepreneur();
		$ask       = $this->getAsk();
		if ( isset( $ask[ $user_id ] ) ) {
			unset( $ask[ $user_id ] );
			if ( empty( $ask ) ) {
				$this->setStatus( 'to_exchange' );
			}
		} else if ( $repreneur && $repreneur->ID == $user_id ) {
			$this->setStatus( 'to_exchange' );
		} else {
			return 'unknown';
		}
		$this->setRepreneur( null );

		amapress_mail_to_current_user(
			Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-adherent-mail-subject' ),
			Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-adherent-mail-content' ),
			$user_id,
			$this );

		if ( $repreneur ) {
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-repreneur-mail-subject' ),
				Amapress::getOption( 'intermittence-panier-cancel-from-repreneur-repreneur-mail-content' ),
				$repreneur->ID,
				$this );
		}

		return 'ok';
	}

	public function getProperty( $name ) {
		switch ( $name ) {
			case 'liste-paniers':
				if ( $this->getPanier() == null ) {
					return '';
				}
				$dist = $this->getDistribution();
				if ( $dist == null ) {
					return '';
				}

				return Amapress::makeLink( Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $dist->getSlug() );

			case 'mes-echanges':
				if ( $this->getPanier() == null ) {
					return '';
				}
				$dist = $this->getDistribution();
				if ( $dist == null ) {
					return '';
				}

				return Amapress::makeLink( Amapress::getPageLink( 'mes-paniers-intermittents-page' ) . '#' . $dist->getSlug() );
			case 'date':
				return date_i18n( 'd/m/Y', $this->getDate() );
			case 'panier':
				if ( $this->getPanier() == null ) {
					return '';
				}

				return $this->getPanier()->getTitle();
			case 'adherent-nom':
				if ( $this->getAdherent() == null ) {
					return '';
				}

				return $this->getAdherent()->getDisplayName();
			case 'adherent':
				if ( $this->getAdherent() == null ) {
					return '';
				}

				return $this->getAdherent()->getDisplay();
			case 'adherent-message':
				return $this->getAdherentMessage();
			case 'adherent-cancel-message':
				return $this->getAdherentCancelMessage();
			case 'repreneur-nom':
				if ( $this->last_ask_id ) {
					$user = AmapressUser::getBy( $this->last_ask_id );
				} else {
					if ( $this->getRepreneur() == null ) {
						return '';
					}
					$user = $this->getRepreneur();
				}

				return $user->getDisplayName();
			case 'repreneur':
				if ( $this->last_ask_id ) {
					$user = AmapressUser::getBy( $this->last_ask_id );
				} else {
					if ( $this->getRepreneur() == null ) {
						return '';
					}
					$user = $this->getRepreneur();
				}

				return $user->getDisplay();
			case 'contrat':
				if ( $this->getPanier() == null ) {
					return '';
				}

				return $this->getPanier()->getContrat_instance()->getModel()->getTitle();
			case 'distribution':
				if ( $this->getPanier() == null ) {
					return '';
				}
				$dist = $this->getDistribution();
				if ( $dist == null ) {
					return '';
				}

				return $dist->getTitle();
			case 'distribution-href':
				if ( $this->getPanier() == null ) {
					return '';
				}
				$dist = $this->getDistribution();
				if ( $dist == null ) {
					return '';
				}

				return $dist->getPermalink();
			case 'distribution-link':
				if ( $this->getPanier() == null ) {
					return '';
				}
				$dist = $this->getDistribution();
				if ( $dist == null ) {
					return '';
				}

				return Amapress::makeLink( $dist->getPermalink(), $dist->getTitle() );
			case 'lien_desinscription_intermittent':
				return '';
//				$current_amapien = AmapressUser::getBy(amapress_current_user_id());
//				return $current_amapien->getProperty($name);
			default:
				if ( strpos( $name, 'adherent-' ) === 0 ) {
					return $this->getAdherent()->getProperty( substr( $name, strlen( 'adherent-' ) ) );
				}
				if ( strpos( $name, 'repreneur-' ) === 0 ) {
					return $this->getAdherent()->getProperty( substr( $name, strlen( 'repreneur-' ) ) );
				}

				return parent::getProperty( $name );
		}
	}

	public static function get_next_panier_intermittent( $date = null, $order = 'NONE' ) {
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
	public function get_related_events( $user_id ) {
		$ret = array();
		if ( empty( $user_id ) || $user_id <= 0 ) {

		} else {
			$date     = $this->getStartDateAndHour();
			$date_end = $this->getEndDateAndHour();
			if ( $this->getAdherent()->ID == $user_id ) {
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
						'label'    => 'A échanger ' . $this->getPanier()->getTitle(),
						'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
						'alt'      => 'Votre panier ' . $this->getPanier()->getTitle() . ' reste à échanger',
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
						'label'    => 'Echange ' . $this->getPanier()->getTitle(),
						'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
						'alt'      => 'Votre panier ' . $this->getPanier()->getTitle() . ' a été échanger',
						'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
					) );
				}
			} else if ( $this->getRepreneur() != null && $this->getRepreneur()->ID == $user_id ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-recup",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-inter-panier-recup",
					'type'     => 'inter-recup',
					'category' => 'Paniers à récupérer',
					'priority' => 15,
					'lieu'     => $this->getRealLieu(),
					'label'    => 'Récupérer panier ' . $this->getPanier()->getTitle(),
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => 'Panier ' . $this->getPanier()->getTitle() . ' de ' . $this->getAdherent()->getDisplayName() . ' à récupérer',
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
							'label'    => 'A échanger ' . $this->getPanier()->getTitle(),
							'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
							'alt'      => 'Panier ' . $this->getPanier()->getTitle() . ' à échanger',
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