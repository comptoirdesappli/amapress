<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressReminder extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_rmd';
	const POST_TYPE = 'reminder';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	/** @return AmapressReminder[] */
	public static function getAll() {
		$key = 'amapress_reminder_all_list';
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = array_map(
				function ( $p ) {
					return new AmapressReminder( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressReminder::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
					)
				)
			);
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public function getStart_date() {
		return $this->getCustom( 'amapress_reminder_start_date' );
	}

	public function getEnd_date() {
		return $this->getCustom( 'amapress_reminder_end_date' );
	}

	public function getInterval() {
		return $this->getCustom( 'amapress_reminder_interval' );
	}

	public function getOtherDays() {
		return $this->getCustomAsIntArray( 'amapress_reminder_other_days' );
	}

	public function getSubject() {
		return stripslashes( $this->getCustom( 'amapress_reminder_subject' ) );
	}

	public function getContent() {
		return stripslashes( $this->getCustom( 'amapress_reminder_content' ) );
	}

	public function getMembersIds() {
		$ids = [];
		foreach ( $this->getMembersQueries() as $user_query ) {
			if ( is_array( $user_query ) ) {
				$user_query['fields'] = 'id';
			} else {
				$user_query .= '&fields=id';
			}
			foreach ( get_users( $user_query ) as $user_id ) {
				$ids[] = intval( $user_id );
			}
		}

		return array_unique( $ids );
	}

	public function getRawEmails() {
		$raw_emails = $this->getCustom( 'amapress_reminder_raw_users' );
		if ( ! empty( $raw_emails ) ) {
			$raw_emails = preg_replace( '/\s+/', ',', $raw_emails );
			$raw_emails = explode( ',', $raw_emails );

			return array_filter( $raw_emails, function ( $e ) {
				return ! empty( $e );
			} );
		}

		return [];
	}

	public function getMembersQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_reminder_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_reminder_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		return $ret;
	}

	public function addInterval( $date ) {
		switch ( $this->getInterval() ) {
			case 'daily':
				return Amapress::add_days( $date, 1 );
//			case 'weekly':
//				return Amapress::add_a_week($date, 1);
			case 'monthly':
				return Amapress::add_a_month( $date, 1 );
			case 'two_monthly':
				return Amapress::add_a_month( $date, 2 );
			case 'quarterly':
				return Amapress::add_a_month( $date, 3 );
			case 'half_yearly':
				return Amapress::add_a_month( $date, 6 );
			case 'yearly':
				return Amapress::add_a_month( $date, 12 );
			default:
				return Amapress::add_a_week( $date, 1 );
		}
	}

	public function refreshCron() {
		$hook_name = 'amps_reminder';
		wp_clear_scheduled_hook( $hook_name, [ $this->ID ] );

		$now      = amapress_time();
		$end_date = $this->getEnd_date();
		if ( ! empty( $end_date ) ) {
			$end_date = Amapress::end_of_day( $end_date );
			if ( $end_date < $now ) {
				return;
			}
		}

		$start_date = $this->getStart_date();
		$start_date = TitanFrameworkOptionEventScheduler::adjustTimezone( $start_date );
		$now        = TitanFrameworkOptionEventScheduler::adjustTimezone( $now );

		$filter_end   = Amapress::add_days( $now, 100 );
		$before_after = array_merge( [ 0 ], $this->getOtherDays() );
		$dates        = [];
		while ( $start_date < $filter_end ) {
			foreach ( $before_after as $days ) {
				$dates[] = Amapress::add_days( $start_date, $days );
			}
			$start_date = $this->addInterval( $start_date );
		}
		$dates = array_filter( $dates, function ( $d ) use ( $now, $end_date ) {
			return $d > $now && ( empty( $end_date ) || $d < $end_date );
		} );
		sort( $dates );
		$i = 0;
		foreach ( $dates as $date ) {
			if ( $i > 5 ) {
				break;
			}
			wp_schedule_single_event( $date, $hook_name, [ $this->ID ] );
			$i += 1;
		}
	}
}


