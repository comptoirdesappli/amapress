<?php

/**
 * Event ICAL feed
 */
class Amapress_Agenda_ICAL_Export {
	/**
	 * Finds valid timezone for timezone_string setting in wp
	 *
	 * @return string Valid timezone
	 *
	 * @see: https://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
	 */
	private static function getTimezoneString() {
		// if site timezone string exists, return it
		if ( $timezone = get_option( 'timezone_string' ) ) {
			return $timezone;
		}
		// get UTC offset, if it isn't set then return UTC
		if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
			return 'UTC';
		}
		// adjust UTC offset from hours to seconds
		$utc_offset *= 3600;
		// attempt to guess the timezone string from the UTC offset
		if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
			return $timezone;
		}
		// last try, guess timezone string manually
		$is_dst = date( 'I' );
		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
					return $city['timezone_id'];
				}
			}
		}

		// fallback to UTC
		return 'UTC';
	}

	private static function ical_split( $preamble, $value ) {
		$value        = trim( $value );
		$value        = strip_tags( $value );
		$value        = preg_replace( '/\n+/', ' ', $value );
		$value        = preg_replace( '/\s{2,}/', ' ', $value );
		$preamble_len = strlen( $preamble );
		$lines        = array();
		while ( strlen( $value ) > ( 70 - $preamble_len ) ) {
			$space = ( 70 - $preamble_len );
			$mbcc  = $space;
			while ( $mbcc ) {
				$line = mb_substr( $value, 0, $mbcc );
				$oct  = strlen( $line );
				if ( $oct > $space ) {
					$mbcc -= $oct - $space;
				} else {
					$lines[]      = $line;
					$preamble_len = 1; // Still take the tab into account
					$value        = mb_substr( $value, $mbcc );
					break;
				}
			}
		}
		if ( ! empty( $value ) ) {
			$lines[] = $value;
		}

		return join( $lines, "\r\n\t" );
	}

	public static function load() {
		add_feed( 'agenda-ical', array( __CLASS__, 'export_events' ) );
	}

	public static function get_link_href( $public_ics = false ) {
		$lnk = get_feed_link( 'agenda-ical' );
		if ( ! $public_ics && amapress_is_user_logged_in() ) {
			$user = AmapressUser::getBy( amapress_current_user_id() );

			return $user->addUserLoginKey( $lnk );
		} else {
			return add_query_arg( 'public', '', $lnk );
		}
	}

	/**
	 * Creates an ICAL file of events in the database
	 */
	public static function export_events() {
		//Give the ICAL a filename

//        $user_id = null;
		if ( isset( $_GET['key'] ) ) {
			AmapressUser::logUserByLoginKey( $_GET['key'] );
		}

		if ( isset( $_GET['public'] ) ) {
			amapress_consider_logged( false );
		}

		$events_types = ! empty( $_GET['events_types'] ) ? explode( ',', $_GET['events_types'] ) : [];
		$events       = [];
		if ( ! empty( $_GET['events_id'] ) ) {
			$events_id  = $_GET['events_id'];
			$filename   = urlencode( 'events-' . $events_id . '-ical-' . date( 'Y-m-d-H-i' ) . '.ics' );
			$all_events = Amapress_Calendar::get_events( $events_id );
		} else {
			$filename   = urlencode( 'agenda-ical-' . date( 'Y-m-d-H-i' ) . '.ics' );
			$all_events = Amapress_Calendar::get_next_events();
		}

		foreach ( $all_events as $ev ) {
			if ( empty( $events_types ) || in_array( $ev->getType(), $events_types ) ) {
				$events[] = $ev;
			}
		}

		//Collect output
		ob_start();

		// File header
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: inline; filename=' . $filename );
		header( 'Content-type: text/calendar; charset=UTF-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' ); // HTTP 1.1
		header( 'Pragma: no-cache' ); // HTTP 1.0
		header( 'Expires: 0' ); // Proxies

		echo "BEGIN:VCALENDAR\n";
		echo "VERSION:2.0\n";
		echo "PRODID:-//Amapress//NONSGML Events//FR\n";
		echo "CALSCALE:GREGORIAN\n";
		echo "X-WR-CALNAME:" . self::ical_split( 'X-WR-CALNAME:', get_bloginfo( 'name' ) ) . " - Agenda\n";
		echo "X-WR-TIMEZONE:" . self::ical_split( 'X-WR-TIMEZONE:', self::getTimezoneString() ) . "\n";

		$offset = get_option( 'gmt_offset' ) * 3600;
		foreach ( $events as $event ) {
			$uid               = md5( $event->getEventId() );                                           //Universal unique ID
			$title             = $event->getLabel();
			$url               = $event->getLink();
			$desc              = $event->getAlt();
			$categories        = $event->getCategory();
			$dtstamp           = gmdate( 'Ymd\THis\Z', current_time( 'timestamp' ) - $offset );                  //date stamp for now.
			$created_date      = gmdate( 'Ymd\THis\Z', $event->getStartDate() - $offset );    //time event created
			$start_date        = gmdate( 'Ymd\THis\Z', $event->getStartDate() - $offset );      //event start date
			$end_date          = gmdate( 'Ymd\THis\Z', $event->getEndDate() - $offset );    //event end date
			$reoccurrence_rule = false;                                       //event reoccurrence rule.
			$location          = $event->getLieu()->getLieuTitle();                          //event location
			$organiser         = get_bloginfo( 'name' );                                //event organiser

			echo "BEGIN:VEVENT\n";
			echo "UID:" . $uid . "\n";
			echo "SUMMARY:" . self::ical_split( 'SUMMARY:', $title ) . "\n";
			echo "CATEGORIES:" . self::ical_split( 'CATEGORIES:', $categories ) . "\n";
			echo "DTSTAMP:" . $dtstamp . "\n";
			echo "CREATED:" . $created_date . "\n";
			echo "DTSTART:" . $start_date . "\n";
			echo "DTEND:" . $end_date . "\n";
			if ( $reoccurrence_rule ) {
				echo "RRULE:" . $reoccurrence_rule . "\n";
			}
			echo "LOCATION:" . self::ical_split( 'LOCATION:', $location ) . "\n";
			echo "ORGANIZER:" . self::ical_split( 'ORGANIZER:', $organiser ) . "\n";
			echo "URL:" . self::ical_split( 'URL:', $url ) . "\n";
			echo "DESCRIPTION:" . self::ical_split( 'DESCRIPTION:', $desc ) . "\n";
			echo "END:VEVENT\n";
		}
		echo "END:VCALENDAR\n";

		//Collect output and echo
		$eventsical = ob_get_contents();
		ob_end_clean();
		echo $eventsical;
		exit();
	}

} // end class

add_action( 'init', 'Amapress_Agenda_ICAL_Export::load' );