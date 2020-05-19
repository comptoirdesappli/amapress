<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAmap_event extends Amapress_EventBase implements iAmapress_Event_Lieu {
	const INTERNAL_POST_TYPE = 'amps_amap_event';
	const POST_TYPE = 'amap_event';
	const CATEGORY = 'amps_amap_event_category';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAmap_event
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAmap_event' ) ) {
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
				self::$entities_cache[ $post_id ] = new AmapressAmap_event( $post );
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
		return Amapress::make_date_and_hour( $this->getDate(), $this->getHeure_debut() );
	}

	public function getEndDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getHeure_fin() );
	}

	public function getDate() {
		return $this->getCustom( 'amapress_amap_event_date' );
	}

	public function getHeure_debut() {
		return $this->getCustom( 'amapress_amap_event_heure_debut' );
	}

	public function getHeure_fin() {
		return $this->getCustom( 'amapress_amap_event_heure_fin' );
	}

	public function getLieu_externe_nom() {
		return $this->getCustom( 'amapress_amap_event_lieu_externe_nom' );
	}

	public function getLieu_externe_adresse() {
		return $this->getCustom( 'amapress_amap_event_lieu_externe_adresse' );
	}

	public function getLieu_externe_acces() {
		return stripslashes( $this->getCustom( 'amapress_amap_event_lieu_externe_acces' ) );
	}

	public function getLieu_externe_adresse_acces() {
		return stripslashes( $this->getCustom( 'amapress_amap_event_lieu_externe_adresse_acces' ) );
	}

	public function isLieu_externe_AdresseLocalized() {
		$v = $this->getCustom( 'amapress_amap_event_lieu_externe_adresse_location_type' );

		return ! empty( $v );
	}

	public function getLieu_externe_AdresseLongitude() {
		return $this->getCustom( 'amapress_amap_event_lieu_externe_adresse_long' );
	}

	public function getLieu_externe_AdresseLatitude() {
		return $this->getCustom( 'amapress_amap_event_lieu_externe_adresse_lat' );
	}

	public function getLieu_externe_AdresseAccesLatitude() {
		return $this->getCustom( 'amapress_amap_event_lieu_externe_adresse_acces_lat' );
	}

	public function getLieu_externe_AdresseAccesLongitude() {
		return $this->getCustom( 'amapress_amap_event_lieu_externe_adresse_acces_long' );
	}

	public function isLieu_externe_AdresseAccesLocalized() {
		$v = $this->getCustom( 'amapress_amap_event_lieu_externe_adresse_acces_location_type' );

		return ! empty( $v );
	}

	public function getTypeDisplay() {
		switch ( $this->getCustom( 'amapress_amap_event_type', 'lieu' ) ) {
			case 'lieu':
				return 'Lieu de distribution';
			case 'lieu_externe':
				return 'Adresse externe';
			default:
				return $this->getCustom( 'amapress_amap_event_type' );
		}
	}

	public function getType() {
		return $this->getCustom( 'amapress_amap_event_type', 'lieu' );
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_amap_event_lieu', 'AmapressLieu_distribution' );
	}

	public function getLieuId() {
		$id = $this->getCustomAsInt( 'amapress_amap_event_lieu' );
		if ( $id > 0 ) {
			return $id;
		} else {
			return $this->ID;
		}
	}

	public function getCategoriesCSS() {
		$this->ensure_init();
		$terms = get_the_terms( $this->ID, 'amps_amap_event_category' );
		if ( empty( $terms ) ) {
			return '';
		}
		$term_names = array_map( function ( $t ) {
			/** @var WP_Term $t */
			return 'evt_typ_' . $t->slug;
		}, $terms );

		return implode( ' ', $term_names );
	}

	public function getTypeIcon() {
		$this->ensure_init();
		$terms = get_the_terms( $this->ID, 'amps_amap_event_category' );
		if ( empty( $terms ) ) {
			return '';
		}
		$term_icons = array_map( function ( $t ) {
			/** @var WP_Term $t */
			$icon_id = Amapress::getOption( 'agenda_amap_event_' . $t->term_id . '_icon' );
			if ( empty( $icon_id ) ) {
				return null;
			}
			$url = wp_get_attachment_image_src( $icon_id, 'produit-thumb' );
			if ( $url ) {
				return $url[0];
			}

			return null;
		}, $terms );
		$term_icons = array_filter( $term_icons, function ( $i ) {
			return ! empty( $i );
		} );

		return array_shift( $term_icons );
	}

	public function getCategoriesDisplay() {
		$this->ensure_init();
		$terms = get_the_terms( $this->ID, 'amps_amap_event_category' );
		if ( empty( $terms ) ) {
			return '';
		}
		$term_names = array_map( function ( $t ) {
			/** @var WP_Term $t */
			return $t->name;
		}, $terms );

		return implode( ', ', $term_names );
	}

	/**
	 * @return AmapressUser[]|null
	 */
	public function getParticipants() {
		return $this->getCustomAsEntityArray( 'amapress_amap_event_participants', 'AmapressUser' );
	}

	/** @reurn int[] */
	public function getParticipantsIds() {
		return $this->getCustomAsIntArray( 'amapress_amap_event_participants' );
	}

	public function inscrireParticipant( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			wp_die( 'Clos et passé' );
		}

		$participants = $this->getParticipantsIds();
		if ( in_array( $user_id, $participants ) ) {
			return 'already_in_list';
		} else {
			$participants[] = $user_id;
			$this->setCustom( 'amapress_amap_event_participants', $participants );

			amapress_mail_current_user_inscr( $this, $user_id, 'amap_event' );

			return 'ok';
		}
	}

	public function desinscrireParticipant( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		if ( ! amapress_can_access_admin() && Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
			wp_die( 'Clos et passé' );
		}

		$participants = $this->getParticipantsIds();
		if ( ( $key = array_search( $user_id, $participants ) ) !== false ) {
			unset( $participants[ $key ] );
			$this->setCustom( 'amapress_amap_event_participants', $participants );

			amapress_mail_current_user_desinscr( $this, $user_id, 'amap_event' );

			return 'ok';
		} else {
			return 'not_inscr';
		}
	}

	/** @return AmapressAmap_event[] */
	public static function get_next_amap_events( $date = null, $order = 'NONE' ) {
		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_amap_event_date',
					'value'   => Amapress::start_of_day( $date ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		$ret         = array();
		$class_names = $this->getCategoriesCSS();
		$categories  = $this->getCategoriesDisplay();
		$icon        = Amapress::coalesce_icons( $this->getTypeIcon(), 'dashicons dashicons-groups' );
		if ( empty( $user_id ) || $user_id <= 0 ) {
			$date     = $this->getStartDateAndHour();
			$date_end = $this->getEndDateAndHour();
			$ret[]    = new Amapress_EventEntry( array(
				'ev_id'    => "ev-{$this->ID}",
				'date'     => $date,
				'date_end' => $date_end,
				'class'    => "agenda-amap-event agenda-inscription-amap-event $class_names",
				'type'     => 'amap_event',
				'category' => 'Évènements' . ( ! empty( $categories ) ? ' - ' . $categories : '' ),
				'lieu'     => $this,
				'priority' => 60,
				'label'    => $this->getTitle(),
				'icon'     => $icon,
				'alt'      => 'Un(e) ' . $this->getTitle() . ' est prévu(e) le ' . date_i18n( 'd/m/Y', $date ),
				'href'     => $this->getPermalink()
			) );
		} else {
			$resps    = $this->getParticipantsIds();
			$date     = $this->getStartDateAndHour();
			$date_end = $this->getEndDateAndHour();
			if ( in_array( $user_id, $resps ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "ev-{$this->ID}-resp",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-amap-event agenda-inscrit-amap-event $class_names",
					'type'     => 'amap_event',
					'category' => 'Évènements' . ( ! empty( $categories ) ? ' - ' . $categories : '' ),
					'lieu'     => $this,
					'priority' => 60,
					'label'    => $this->getTitle(),
					'icon'     => $icon,
					'alt'      => 'Vous êtes inscript pour ' . $this->getTitle() . ' le ' . date_i18n( 'd/m/Y', $date ),
					'href'     => $this->getPermalink()
				) );
			} else {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "ev-{$this->ID}",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-amap-event agenda-inscription-amap-event $class_names",
					'type'     => 'amap_event',
					'category' => 'Évènements',
					'lieu'     => $this,
					'priority' => 60,
					'label'    => $this->getTitle(),
					'icon'     => $icon,
					'alt'      => 'Un(e) ' . $this->getTitle() . ' est prévu(e) le ' . date_i18n( 'd/m/Y', $date ),
					'href'     => $this->getPermalink()
				) );
			}
		}

		return $ret;
	}

	public function getLieuPermalink() {
		/** @var AmapressLieu_distribution $lieu */
		$lieu = $this->getCustomAsEntity( 'amapress_amap_event_lieu', 'AmapressLieu_distribution' );
		if ( $lieu ) {
			return $lieu->getPermalink();
		} else {
			return $this->getPermalink();
		}
	}

	public function getLieuTitle() {
		/** @var AmapressLieu_distribution $lieu */
		$lieu = $this->getCustomAsEntity( 'amapress_amap_event_lieu', 'AmapressLieu_distribution' );
		if ( $lieu ) {
			return $lieu->getShortName();
		} else {
			return $this->getLieu_externe_nom();
		}
	}

	public static function getRespAmapEventsEmails( $lieu_id ) {
		return AmapressUser::getEmailsForAmapRole( intval( Amapress::getOption( 'resp-amap_event-amap-role' ), $lieu_id ) );
	}

	public static function getResponsableAmapEventsReplyto() {
		$emails = self::getRespAmapEventsEmails( null );
		if ( empty( $emails ) ) {
			return [];
		}

		return 'Reply-To: ' . implode( ',', $emails );
	}

	public function canSubscribe() {
		return $this->canSubscribeType( 'event' );
	}

	public function canUnsubscribe() {
		return $this->canUnsubscribeType( 'event' );
	}
}
