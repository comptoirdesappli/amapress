<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressCommande extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_commande';
	const POST_TYPE = 'commande';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getDefaultSortValue() {
		return $this->getDate_distrib();
	}

	public function getStartDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate_distrib(), $this->getLieu()->getHeure_debut() );
	}

	public function getEndDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate_distrib(), $this->getLieu()->getHeure_fin() );
	}

	public function getDate_distrib() {
		$this->ensure_init();

		return $this->custom['amapress_commande_date_distrib'];
	}

	private $lieu = null;

	public function getLieu() {
		$this->ensure_init();
		$v = $this->custom['amapress_commande_lieu'];
		if ( empty( $v ) ) {
			return null;
		}
		if ( $this->lieu == null ) {
			$this->lieu = new AmapressLieu_distribution( $v );
		}

		return $this->lieu;
	}

	public function setLieu( $value ) {
		update_post_meta( $this->post->ID, 'amapress_commande_lieu', $value );
		$this->lieu = null;
	}


	private $Responsables = null;

	public function getResponsables() {
		$this->ensure_init();
		$v = $this->custom['amapress_commande_responsables'];
		if ( empty( $v ) ) {
			return array();
		}
		if ( $this->Responsables == null ) {
			$this->Responsables = array_map( function ( $o ) {
				return AmapressUser::getBy( $o );
			}, $v );
		}

		return $this->Responsables;
	}

	public function setResponsables( $value ) {
		update_post_meta( $this->post->ID, 'amapress_commande_responsables', $value );
		$this->Responsables = null;
	}


	public function getDate_debut() {
		$this->ensure_init();

		return $this->custom['amapress_commande_date_debut'];
	}

	public function setDate_debut( $value ) {
		update_post_meta( $this->post->ID, 'amapress_commande_date_debut', $value );
	}


	public function getDate_fin() {
		$this->ensure_init();

		return $this->custom['amapress_commande_date_fin'];
	}

	public function setDate_fin( $value ) {
		update_post_meta( $this->post->ID, 'amapress_commande_date_fin', $value );
	}


	private $contrat_instance = null;

	public function getContrat_instance() {
		$this->ensure_init();
		$v = $this->custom['amapress_commande_contrat_instance'];
		if ( empty( $v ) ) {
			return null;
		}
		if ( $this->contrat_instance == null ) {
			$this->contrat_instance = new AmapressContrat_instance( $v );
		}

		return $this->contrat_instance;
	}

	public function setContrat_instance( $value ) {
		update_post_meta( $this->post->ID, 'amapress_commande_contrat_instance', $value );
		$this->contrat_instance = null;
	}

	/** @return AmapressCommande[] */
	public static function get_next_deliverable_commandes( $date = null, $order = 'NONE' ) {        //var_dump(date('c',Amapress::start_of_day(time())));
		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_commande_date_distrib',
					'value'   => Amapress::end_of_day( $date ),
					'compare' => '>=',
					'type'    => 'INT'
				),
			),
			$order );
	}

	/** @return AmapressCommande[] */
	public static function get_next_orderable_commandes( $date = null, $order = 'NONE' ) {        //var_dump(date('c',Amapress::start_of_day(time())));
		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_commande_date_fin',
					'value'   => Amapress::end_of_day( $date ),
					'compare' => '>=',
					'type'    => 'INT'
				),
			),
			$order );
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		if ( empty( $user_id ) || $user_id <= 0 ) {

		} else {
//            $commande_date_debut = intval(get_post_meta($event->ID, 'amapress_commande_date_debut', true));
//            $commande_date_fin = intval(get_post_meta($event->ID, 'amapress_commande_date_fin', true));
//            $commande_can_modify = intval(get_post_meta($event->ID, 'amapress_commande_can_modify', true));
//            if (!$commande_can_modify) continue;
//            $start_of_day = Amapress::start_of_day(amapress_time());
//            $dist_date = $commande_date_debut < $start_of_day ?  $start_of_day : $commande_date_debut;
//            $dist_date_end = $commande_date_fin > $start_of_day ?  $commande_date_fin : $start_of_day;
//            $commande_contrat = intval(get_post_meta($event->ID, 'amapress_commande_contrat_instance', true));
//            $commande_contrat_model = intval(get_post_meta($commande_contrat, 'amapress_contrat_instance_model', true));
//            $contrat_model_post = get_post($commande_contrat_model);
//            $lieu = intval(get_post_meta($event->ID, 'amapress_commande_lieu', true));
//            //$contrat_instance = get_post_meta($event->ID, 'amapress_commande_contrat_instance', true);
//            $commandes = get_posts(array(
//                'post_type' => 'amps_user_commande',
//                'posts_per_page' => -1,
//                'meta_query' => array(
//                    'relation' => 'AND',
//                    array(
//                        'key' => 'amapress_user_commande_commande',
//                        'value' => $event->ID,
//                        'compare' => '=',
//                    )
//                )
//            ));
//            $ret[] = array(
//                'ev_id' => "cmd-{$event->ID}",
//                'date' => $dist_date,
//                'date_end' => $dist_date_end,
//                'class' => "agenda-commande-{$commande_contrat_model}-".(count($commandes) == 0 ? 'modify' : 'order'),
//                'type' => 'commande',
//                'lieu' => $lieu,
//                'label' => (count($commandes) == 0 ? 'Réservation' : 'Modifier').' commande '.$contrat_model_post->post_title,
//                'icon' => self::get_icon(Amapress::getOption("agenda_contrat_{$commande_contrat_model}_icon")),
//                'alt' => (count($commandes) == 0 ? 'Réservation' : 'Modification').' commande de ' . $contrat_model_post->post_title . ' à ' . get_post($lieu)->post_title,
//                'href' => (count($commandes) == 0 ? AmapressCommandes::get_user_commande_href($commandes[0]->ID) : AmapressCommandes::get_commande_href($event->ID)));
//
		}

		return array();
	}
}
