<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressDistribution extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_distribution';
	const POST_TYPE = 'distribution';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getStartDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getLieu()->getHeure_debut() );
	}

	public function getEndDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getLieu()->getHeure_fin() );
	}

	public function getNb_responsables_Supplementaires() {
		return $this->getCustomAsInt( 'amapress_distribution_nb_resp_supp', 0 );
	}

	public function getInformations() {
		return $this->getCustom( 'amapress_distribution_info', '' );
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
		return $this->getCustomAsInt( 'amapress_distribution_lieu_substitution', - 1 );
	}

	/** @return AmapressUser[] */
	public function getResponsables() {
		return $this->getCustomAsEntityArray( 'amapress_distribution_responsables', 'AmapressUser' );
	}

	public function getMailtoResponsables() {
		$resp_mails = [];
		foreach ( $this->getResponsables() as $user ) {
			$resp_mails = array_merge( $resp_mails, $user->getAllEmails() );
		}
		if ( empty( $resp_mails ) ) {
			return '';
		}

		return 'mailto:' . urlencode( implode( ',', $resp_mails ) ) . '&subject=Distribution du ' .
		       date_i18n( 'D j M Y' );
	}

	/** @return int[] */
	public function getResponsablesIds() {
		return $this->getCustomAsIntArray( 'amapress_distribution_responsables' );
	}

	/** @return AmapressContrat_instance[] */
	public function getContrats() {
		return array_map(
			function ( $id ) {
				return AmapressContrat_instance::getBy( $id );
			}, $this->getContratIds()
		);
	}

	public function getDelayedToThisPaniers() {
		return AmapressPanier::get_delayed_paniers( null, $this->getDate(), null, [ 'delayed' ] );
	}

	public function getCancelledPaniers() {
		return AmapressPanier::get_delayed_paniers( null, null, $this->getDate() );
	}

	/** @return int[] */
	public function getContratIds() {
		$res = $this->getCustomAsIntArray( 'amapress_distribution_contrats' );

		$cancelled_contrat_ids = array_map( function ( $p ) {
			/** @var AmapressPanier $p */
			return $p->getContrat_instanceId();
		}, $this->getCancelledPaniers() );
		$delayed_contrat_ids   = array_map( function ( $p ) {
			/** @var AmapressPanier $p */
			return $p->getContrat_instanceId();
		}, $this->getDelayedToThisPaniers() );
		$res                   = array_merge( $res, $delayed_contrat_ids );
		$res                   = array_diff( $res, $cancelled_contrat_ids );

		return array_unique( $res );
	}

	public function isUserMemberOf( $user_id, $guess_renew = false ) {
		$user_contrats_ids = AmapressContrat_instance::getContratInstanceIdsForUser( $user_id,
			null,
			$this->getDate(),
			$guess_renew );
		$dist_contrat_ids  = array_map( function ( $c ) {
			return $c->ID;
		}, $this->getContrats() );

		if ( count( array_intersect( $user_contrats_ids, $dist_contrat_ids ) ) > 0 ) {
			return true;
		}
		if ( ! $guess_renew ) {
			return false;
		}

		$user_lieu_ids = AmapressUsers::get_user_lieu_ids( $user_id,
			$this->getDate() );

		return in_array( $this->getLieuId(), $user_lieu_ids );
	}

	public function inscrireResponsable( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		if ( ! $this->isUserMemberOf( $user_id, true ) ) {
			wp_die( 'Vous ne faites pas partie de cette distribution.' );
		}

		$responsables        = Amapress::get_post_meta_array( $this->ID, 'amapress_distribution_responsables' );
		$needed_responsables = AmapressDistributions::get_required_responsables( $this->ID );
		if ( ! $responsables ) {
			$responsables = array();
		}
		if ( in_array( $user_id, $responsables ) ) {
			return 'already_in_list';
		} else if ( count( $responsables ) >= $needed_responsables ) {
			return 'list_full';
		} else {
			$responsables[] = $user_id;
			update_post_meta( $this->ID, 'amapress_distribution_responsables', $responsables );

			amapress_mail_current_user_inscr( $this, $user_id, 'distrib' );

			return 'ok';
		}
	}

	public function desinscrireResponsable( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		$responsables = Amapress::get_post_meta_array( $this->ID, 'amapress_distribution_responsables' );
		if ( ! $responsables ) {
			$responsables = array();
		}

		if ( ( $key = array_search( $user_id, $responsables ) ) !== false ) {
			unset( $responsables[ $key ] );

			update_post_meta( $this->ID, 'amapress_distribution_responsables', $responsables );

			amapress_mail_current_user_desinscr( $this, $user_id, 'distrib' );

			return 'ok';
		} else {
			return 'not_inscr';
		}
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
				'compare' => '>',
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
						amapress_prepare_like_in_array( "amapress_distribution_contrats", $contrat_instance_id )
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
			'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
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
		$ret = array();
		if ( empty( $user_id ) || $user_id <= 0 ) {
			$lieu              = $this->getLieu();
			$lieu_substitution = $this->getLieuSubstitution();
			if ( ! empty( $lieu_substitution ) ) {
				$lieu = $lieu_substitution;
			}
			$dist_date     = $this->getStartDateAndHour();
			$dist_date_end = $this->getEndDateAndHour();
			$contrats      = $this->getContrats();
			foreach ( $contrats as $contrat ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "dist-{$this->ID}",
					'date'     => $dist_date,
					'date_end' => $dist_date_end,
					'type'     => 'distribution',
					'category' => 'Distributions',
					'priority' => 30,
					'lieu'     => $lieu,
					'label'    => $contrat->getModel()->getTitle(),
					'alt'      => 'Distribution de ' . $contrat->getModel()->getTitle() . ' à ' . $lieu->getShortName(),
					'class'    => "agenda-contrat-{$contrat->getModel()->ID}",
					'icon'     => Amapress::coalesce_icons( Amapress::getOption( "contrat_{$contrat->getModel()->ID}_icon" ), amapress_get_avatar_url( $contrat->getModel()->ID, null, 'produit-thumb', 'default_contrat.jpg' ) ),
					'href'     => $this->getPermalink()
				) );
			}
		} else {
			$adhesions         = AmapressAdhesion::getUserActiveAdhesions();
			$lieu              = $this->getLieu();
			$lieu_substitution = $this->getLieuSubstitution();
			if ( ! empty( $lieu_substitution ) ) {
				$lieu = $lieu_substitution;
			}
			$dist_date     = $this->getStartDateAndHour();
			$dist_date_end = $this->getEndDateAndHour();
			$resps         = $this->getResponsablesIds();
			if ( in_array( $user_id, $resps ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "dist-{$this->ID}-resp",
					'date'     => $dist_date,
					'date_end' => $dist_date_end,
					'class'    => 'agenda-resp-distrib',
					'category' => 'Responsable de distribution',
					'lieu'     => $lieu,
					'type'     => 'resp-distribution',
					'priority' => 45,
					'label'    => 'Responsable de distribution',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_resp_distrib_icon" ) ),
					'alt'      => 'Vous êtes responsable de distribution à ' . $lieu->getShortName(),
					'href'     => $this->getPermalink()
				) );
			}
			$contrats = $this->getContratIds();
			foreach ( $adhesions as $adhesion ) {
				if ( $adhesion->getLieuId() == $this->getLieuId()
				     && in_array( $adhesion->getContrat_instanceId(), $contrats )
				) {
					$ret[] = new Amapress_EventEntry( array(
						'ev_id'    => "dist-{$this->ID}",
						'id'       => $this->ID,
						'date'     => $dist_date,
						'date_end' => $dist_date_end,
						'class'    => "agenda-contrat-{$adhesion->getContrat_instance()->getModel()->getTitle()}",
						'type'     => 'distribution',
						'category' => 'Distributions',
						'priority' => 30,
						'lieu'     => $lieu,
						'label'    => $adhesion->getContrat_instance()->getModel()->getTitle(),
						'icon'     => Amapress::coalesce_icons( Amapress::getOption( "contrat_{$adhesion->getContrat_instance()->getModel()->ID}_icon" ), amapress_get_avatar_url( $adhesion->getContrat_instance()->getModel()->ID, null, 'produit-thumb', 'default_contrat.jpg' ) ),
						'alt'      => 'Distribution de ' . $adhesion->getContrat_instance()->getModel()->getTitle() . ' à ' . $lieu->getShortName(),
						'href'     => $this->getPermalink()
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
				} else if ( $panier->getRepreneurId() == $user_id ) {
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
					'class'    => "agenda-intermittence",
					'type'     => 'intermittence',
					'category' => 'Paniers à échanger',
					'priority' => 10,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['me_to_exchange'] . '</span> à échanger',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => $status_count['me_to_exchange'] . ' à échanger',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}
			if ( $status_count['me_exchanged'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-exchanged",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-intermittence",
					'type'     => 'intermittence',
					'category' => 'Paniers échangé',
					'priority' => 5,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['me_exchanged'] . '</span> échangé(s)',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => $status_count['me_exchanged'] . ' échangé(s)',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}

			if ( $status_count['me_recup'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-recup",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-inter-panier-recup",
					'type'     => 'inter-recup',
					'category' => 'Paniers à récupérer',
					'priority' => 15,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['me_recup'] . '</span> à récupérer',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => $status_count['me_recup'] . ' à récupérer',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
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
					'class'    => "agenda-intermittence",
					'type'     => 'intermittence',
					'category' => 'Paniers dispo',
					'priority' => 10,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['other_to_exchange'] . '</span> à échanger',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => $status_count['other_to_exchange'] . ' à échanger',
					'href'     => $paniers_url
				) );
//				}
			}
		}

		return $ret;
	}

	public function getProperty( $name ) {
		switch ( $name ) {
			case 'lien_liste_emargement':
				return Amapress::makeLink( $this->getListeEmargementHref() );
			case 'lieu_instruction':
				return $this->getLieu()->getInstructions_privee();
			case  'liste_contrats':
				return implode( ', ', array_map(
					function ( $c ) {
						/** @var AmapressContrat_instance $c */
						return $c->getModel()->getTitle();
					}, $this->getContrats()
				) );
			case 'lien_distrib':
				return Amapress::makeLink( $this->getPermalink() );
			case 'lien_instructions_lieu':
				return Amapress::makeLink( $this->getPermalink() . '#instructions-lieu' );
			case  'lien-resp-distrib-ical':
				return add_query_arg(
					[
						'events_id'    => $this->ID,
						'events_types' => 'distribution,resp-distribution'
					],
					Amapress_Agenda_ICAL_Export::get_link_href() );
			case  'lien-distrib-ical':
				return parent::getProperty( 'lien-evenement-ical' );
			case 'lien-liste-paniers':
			case 'liste-paniers':
				return Amapress::makeLink( Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $this->getSlug() );
			case 'nb-paniers-intermittents':
				return count( $this->getPaniersIntermittents() );
			case 'paniers-intermittents':
				return implode( ', ', array_map( function ( $p ) {
					/** @var AmapressIntermittence_panier $p */
					return $p->getPaniersDescription();
				}, $this->getPaniersIntermittents() ) );
		}


		return parent::getProperty( $name );
	}

	public function getListeEmargementHref() {
		return $this->getPermalink( 'liste-emargement' );
	}

	/** @return AmapressDistribution[] */
	public static function getNextDistribs( $date = null, $weeks = 1, $user_id = null ) {
		$is_resp_amap = amapress_can_access_admin();

		if ( ! $date ) {
			$date = amapress_time();
		}
		$next_week_date = Amapress::add_a_week( amapress_time(), $weeks - 1 );
		$next_distribs  = AmapressDistribution::get_distributions( Amapress::start_of_week( Amapress::end_of_week( $date ) ), Amapress::end_of_week( $next_week_date ) );

		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}

		$ret = [];
		foreach ( $next_distribs as $dist ) {
			if ( ! $is_resp_amap && ! in_array( $user_id, $dist->getResponsablesIds() ) ) {
				continue;
			}

			$ret[] = $dist;
		}

		return $ret;
	}

	/** @return AmapressIntermittence_panier[] */
	public function getPaniersIntermittents() {
		return AmapressPaniers::getPanierIntermittents(
			[ 'date' => $this->getDate() ]
		);
	}
}
