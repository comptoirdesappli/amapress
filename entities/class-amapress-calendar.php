<?php

interface iAmapress_Event_Lieu {
	public function getLieuId();

	public function getLieuPermalink();

	public function getLieuTitle();
}

class Amapress_EventEntry {
	private $args;

	public function __construct( $args ) {
		$this->args = wp_parse_args(
			$args,
			array(
				'id'       => null,
				'ev_id'    => null,
				'date'     => null,
				'date_end' => null,
				'class'    => null,
				'type'     => null,
				'category' => null,
				'lieu'     => null,
				'label'    => null,
				'icon'     => null,
				'priority' => 0,
				'alt'      => null,
				'href'     => null
			)
		);
	}

	public function getId() {
		return $this->args['id'];
	}

	public function getEventId() {
		return $this->args['ev_id'];
	}

	public function getStartDate() {
		return $this->args['date'];
	}

	public function getEndDate() {
		return $this->args['date_end'];
	}

	public function getClass() {
		return $this->args['class'];
	}

	public function getType() {
		return $this->args['type'];
	}

	public function getCategory() {
		return $this->args['category'];
	}

	public function getPriority() {
		return $this->args['priority'];
	}

	/** @return iAmapress_Event_Lieu */
	public function getLieu() {
		return $this->args['lieu'];
	}

	public function getLabel() {
		return $this->args['label'];
	}

	public function getIcon() {
		return $this->args['icon'];
	}

	public function getAlt() {
		return $this->args['alt'];
	}

	public function getLink() {
		return $this->args['href'];
	}

}


class Amapress_Calendar {
	public static function amapress_get_agenda_date_separator_monthly( $v, $last_date, $date ) {
		if ( date( 'm', $last_date ) == date( 'm', $date ) ) {
			return $v;
		}
		if ( ! $date ) {
			return $v;
		}

		return '<h2 class="month-separator">' . date_i18n( 'F Y', $date ) . '</h2>';
	}

	public static function init() {
		add_filter( 'amapress_get_agenda_date_separator_monthly', 'self::amapress_get_agenda_date_separator_monthly', 10, 3 );
	}

	/** @return Amapress_EventEntry[] */
	public static function get_next_events( $date = null, $user_id = null ) {
		if ( ! $date ) {
			$date = amapress_time();
		}
		if ( ! $user_id ) {
			$user_id = amapress_current_user_id();
		}
		/** @var Amapress_EventBase[] $events */
		$events = array();
		$t      = AmapressDistribution::get_next_distributions( $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
		$t = AmapressVisite::get_next_visites( $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
		$t = AmapressAssemblee_generale::get_next_assemblees( $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
		$t = AmapressAmapien_paiement::get_next_paiements( $user_id, $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
		$t = AmapressAdhesion_paiement::get_next_paiements( $user_id, $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
		$t = AmapressAmap_event::get_next_amap_events( $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
		$t = AmapressCommande::get_next_orderable_commandes( $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
		$t = AmapressUser_commande::get_next_user_commandes( $date );
		if ( $t ) {
			$events = array_merge( $events, $t );
		}
//        $t = AmapressIntermittence_panier::get_next_panier_intermittent($date);
//        if ($t) $events = array_merge($events, $t);

		Amapress_EventBase::sort_events( $events );

		$ret = array();
		foreach ( $events as $ev ) {
			$ret = array_merge( $ret, $ev->get_related_events( $user_id ) );
		}

		return $ret;
	}
}