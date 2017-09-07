<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressUser_commande extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_user_commande';
	const POST_TYPE = 'user_commande';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}


	private $commande = null;

	public function getCommande() {
		$this->ensure_init();
		$v = $this->custom['amapress_user_commande_commande'];
		if ( empty( $v ) ) {
			return null;
		}
		if ( $this->commande == null ) {
			$this->commande = new AmapressCommande( $v );
		}

		return $this->commande;
	}

	public function setCommande( $value ) {
		update_post_meta( $this->post->ID, 'amapress_user_commande_commande', $value );
		$this->commande = null;
	}


	private $amapien = null;

	public function getAmapien() {
		$this->ensure_init();
		$v = $this->custom['amapress_user_commande_amapien'];
		if ( empty( $v ) ) {
			return null;
		}
		if ( $this->amapien == null ) {
			$this->amapien = AmapressUser::getBy( $v );
		}

		return $this->amapien;
	}

	public function setAmapien( $value ) {
		update_post_meta( $this->post->ID, 'amapress_user_commande_amapien', $value );
		$this->amapien = null;
	}

	public static function get_next_user_commandes( $date = null ) {
		if ( ! $date ) {
			$date = amapress_time();
		}
		$deliverables_commande_ids = array_map( 'Amapress::to_id', AmapressCommande::get_next_deliverable_commandes() );
		$ret                       = get_posts( array(
			'posts_per_page' => - 1,
			'post_type'      => 'amps_user_commande',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'meta_key'       => 'amapress_user_commande_date',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_user_commande_date',
					'value'   => Amapress::start_of_day( $date ),
					'compare' => '>=',
					'type'    => 'INT'
				),
				array(
					'key'     => 'amapress_user_commande_commande',
					'value'   => $deliverables_commande_ids,
					'compare' => 'IN',
					'type'    => 'INT'
				),
			)
		) );

		return $ret;
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		if ( empty( $user_id ) || $user_id <= 0 ) {

		} else {
//            $commande = intval(get_post_meta($event->ID, 'amapress_user_commande_commande', true));
//            $lieu = intval(get_post_meta($commande, 'amapress_commande_lieu', true));
//            $dist_date = self::get_commande_distrib_date_and_hour($commande, 'start');
//            $dist_date_end = self::get_commande_distrib_date_and_hour($commande, 'end');
//            $resps = array_map('intval', Amapress::get_post_meta_array($commande, 'amapress_commande_responsables'));
//            if (in_array($user_id, $resps)) {
//                $ret[] = array(
//                    'ev_id' => "ucmd-{$event->ID}-resp",
//                    'date' => $dist_date,
//                    'date_end' => $dist_date_end,
//                    'class' => 'agenda-resp-commande',
//                    'lieu' => $lieu,
//                    'type' => 'resp-commanden',
//                    'label' => 'Responsable de distribution de commande',
//                    'icon' => self::get_icon(Amapress::getOption("agenda_resp_commande_icon")),
//                    'alt' => 'Vous êtes responsable de distribution de commande à ' . get_post($lieu)->post_title,
//                    'href' => AmapressCommandes::get_commande_href($commande));
//            }
//            $commande_contrat = intval(get_post_meta($commande, 'amapress_commande_contrat_instance', true));
//            $commande_contrat_model = intval(get_post_meta($commande_contrat, 'amapress_contrat_instance', true));
//            $contrat_model_post = get_post($commande_contrat_model);
//            $ret[] = array(
//                'ev_id' => "ucmd-{$event->ID}",
//                'date' => $dist_date,
//                'date_end' => $dist_date_end,
//                'class' => "agenda-commande-{$commande_contrat_model}",
//                'type' => 'commande',
//                'lieu' => $lieu,
//                'label' => 'Livraison commande '.$contrat_model_post->post_title,
//                'icon' => self::get_icon(Amapress::getOption("agenda_contrat_{$commande_contrat_model}_icon")),
//                'alt' => 'Livraison de ' . $contrat_model_post->post_title . ' à ' . get_post($lieu)->post_title,
//                'href' => AmapressCommandes::get_user_commande_href($event->ID));

		}

		return array();
	}
}
