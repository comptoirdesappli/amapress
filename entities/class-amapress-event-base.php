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
}