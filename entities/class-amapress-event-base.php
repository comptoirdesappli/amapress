<?php

class Amapress_EventBase extends TitanEntity {
	const INTERNAL_POST_TYPE = '';
	const POST_TYPE = '';

	/** @return int */
	public function getStartDateAndHour() {
		return 0;
	}

	/** @return int */
	public function getEndDateAndHour() {
		return 0;
	}

	/**
	 * @param Amapress_EventBase[] $events
	 * @param string $order
	 */
	public static function sort_events( &$events, $order = 'ASC' ) {
		$order_num = ( $order == 'ASC' ? 1 : - 1 );
		usort( $events, function ( Amapress_EventBase $a, Amapress_EventBase $b ) use ( $order_num ) {
			$da = $a->getDefaultSortValue();
			$db = $b->getDefaultSortValue();
			if ( $da < $db ) {
				return $order_num * - 1;
			}
			if ( $da > $db ) {
				return $order_num * 1;
			}

			return 0;
		} );
	}

	/**
	 * @param array $meta_query
	 * @param string $order
	 *
	 * @return Amapress_EventBase[]
	 */
	public static function query_events( $meta_query, $order = 'NONE' ) {
		$class = get_called_class();
		$ret   = array_map( function ( $p ) use ( $class ) {
			return new $class( $p );
		},
			get_posts( array(
				'posts_per_page' => - 1,
				'post_type'      => static::INTERNAL_POST_TYPE,
				'meta_query'     => $meta_query,
			) ) );
		if ( $order != 'NONE' ) {
			self::sort_events( $ret, $order );
		}

		return $ret;
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		return array();
	}

	public function getLieuInformation() {
		return '';
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_recall = true ) {
		return Amapress::getPlaceholdersHelpTable( 'event-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'de l\'évènement',
			$additional_helps, $for_recall ? 'recall' : true );
	}

	public static function getProperties() {
		return [
			'evenement'           => [
				'desc' => 'Nom de l\'évènement',
				'func' => function ( Amapress_EventBase $ev ) {
					return esc_html( $ev->getTitle() );
				}
			],
			'lien-evenement'      => [
				'desc' => 'Lien vers la présentation de l\'évènement',
				'func' => function ( Amapress_EventBase $ev ) {
					return Amapress::makeLink( $ev->getPermalink() );
				}
			],
			'lien-evenement-ical' => [
				'desc' => 'Lien ical de l\'évènement',
				'func' => function ( Amapress_EventBase $ev ) {
					return add_query_arg( 'events_id', $ev->ID, Amapress_Agenda_ICAL_Export::get_link_href() );
				}
			],
			'lieu-info'           => [
				'desc' => 'Information sur le lieu',
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getLieuInformation();
				}
			],
			'horaires-evenement'  => [
				'desc' => 'Date et horaires évènement',
				'func' => function ( Amapress_EventBase $ev ) {
					return date_i18n( 'D j M Y', $ev->getStartDateAndHour() ) .
					       ' de ' . date_i18n( 'H:i', $ev->getStartDateAndHour() ) .
					       ' à ' . date_i18n( 'H:i', $ev->getEndDateAndHour() );
				}
			],
		];
	}
}