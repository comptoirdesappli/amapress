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

		return join( $lines, "\n\t" );
	}

	public static function load() {
		add_feed( 'agenda-ical', array( __CLASS__, 'export_events' ) );
	}

	public static function get_link_href( $public_ics = false, $since_days = 30 ) {
		$lnk = get_feed_link( 'agenda-ical' );
		if ( ! $public_ics && amapress_is_user_logged_in() ) {
			$user = AmapressUser::getBy( amapress_current_user_id() );

			return add_query_arg( 'since_days', $since_days, $user->addUserLoginKey( $lnk ) );
		} else {
			return add_query_arg( [ 'public' => '', 'since_days' => $since_days ], $lnk );
		}
	}

	private static function timezone_offset_seconds( $timestamp = 0 ) {
		if ( ! $timezone_string = get_option( 'timezone_string' ) ) {
			return get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		}

		$timezone_object = timezone_open( $timezone_string );
		if ( ! $timezone_object ) {
			return get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		}

		$datetime_object = new DateTime();
		if ( $timestamp ) {
			$datetime_object->setTimestamp( $timestamp );
		}

		return round( timezone_offset_get( $timezone_object, $datetime_object ), 2 );
	}

	private static function toUTCString( $timestamp ) {
		return gmdate( 'Ymd\THis\Z', $timestamp - self::timezone_offset_seconds( $timestamp ) );
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
			$date       = amapress_time();
			$since_days = 30;
			if ( ! empty( $_GET['since_days'] ) ) {
				$since_days = intval( $_GET['since_days'] );
			}
			$date       = Amapress::add_days( $date, - $since_days );
			$all_events = Amapress_Calendar::get_next_events( $date );
		}

		foreach ( $all_events as $ev ) {
			if ( empty( $events_types ) || in_array( $ev->getType(), $events_types ) ) {
				$events[] = $ev;
			}
		}

		// File header
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: inline; filename=' . $filename );
		header( 'Content-type: text/calendar; charset=UTF-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' ); // HTTP 1.1
		header( 'Pragma: no-cache' ); // HTTP 1.0
		header( 'Expires: 0' ); // Proxies

		echo self::getICALFromEvents( $events );
		exit();
	}

	private static function ical_escape( $t ) {
		$t = str_replace( ',', '\,', $t );
		$t = str_replace( ';', '\;', $t );
		//add a trailing space in case \n is not properly interpreted (ie, Outlook, see issue #22)
		$t = str_replace( "\n", ' \n', $t );

		return $t;
	}

	public static function getICALFromEvents( $events, $calendar_title = '', $type = null ) {
		if ( empty( $calendar_title ) ) {
			$calendar_title = get_bloginfo( 'name' ) . ' - Agenda';
		}

		//Collect output
		ob_start();

		echo "BEGIN:VCALENDAR\n";
		echo "VERSION:2.0\n";
		echo "PRODID:-//Amapress//NONSGML Events//FR\n";
		echo "CALSCALE:GREGORIAN\n";
		echo "X-WR-CALNAME:" . self::ical_split( 'X-WR-CALNAME:', self::ical_escape( $calendar_title ) ) . "\n";
		echo "X-WR-TIMEZONE:" . self::ical_split( 'X-WR-TIMEZONE:', self::getTimezoneString() ) . "\n";
		if ( 'cancel' == $type ) {
			echo "METHOD:CANCEL\n";
			echo "STATUS:CANCELLED\n";
		} elseif ( 'request' == $type ) {
			echo "METHOD:REQUEST\n";
			echo "STATUS:CONFIRMED\n";
		}

		date_default_timezone_set( "UTC" );
		foreach ( $events as $event ) {
			/** @var Amapress_EventEntry $event */
			$uid               = md5( $event->getEventId() );                                           //Universal unique ID
			$title             = $event->getLabel();
			$url               = $event->getLink();
			$desc              = $event->getAlt();
			$categories        = $event->getCategory();
			$css               = $event->getClass();
			$icon              = $event->getIcon();
			$dtstamp           = self::toUTCString( current_time( 'timestamp' ) );                  //date stamp for now.
			$created_date      = self::toUTCString( $event->getStartDate() );    //time event created
			$start_date        = self::toUTCString( $event->getStartDate() );      //event start date
			$end_date          = self::toUTCString( $event->getEndDate() );    //event end date
			$reoccurrence_rule = false;                                       //event reoccurrence rule.
			$lieu              = $event->getLieu();                          //event location
			$location          = $lieu->getLieuTitle();                          //event location
			if ( ! empty( $lieu->getLieuAddress() ) ) {
				$location .= "\n" . $lieu->getLieuAddress();
			}
			$geo = null;
			if ( ! empty( $lieu->getLieuLatitude() ) && ! empty( $lieu->getLieuLongitude() ) ) {
				$geo = $lieu->getLieuLatitude() . ';' . $lieu->getLieuLongitude();
			}
			$organiser = get_bloginfo( 'name' );                                //event organiser

			echo "BEGIN:VEVENT\n";
			echo "UID:" . $uid . "\n";
			echo "SUMMARY:" . self::ical_split( 'SUMMARY:', self::ical_escape( $title ) ) . "\n";
			echo "CATEGORIES:" . self::ical_split( 'CATEGORIES:', $categories ) . "\n";
			echo "DTSTAMP:" . $dtstamp . "\n";
			echo "CREATED:" . $created_date . "\n";
			echo "DTSTART:" . $start_date . "\n";
			echo "DTEND:" . $end_date . "\n";
			echo "X-AMPS-CSS:" . self::ical_split( 'X-AMPS-CSS:', $css ) . "\n";
			if ( ! empty( $icon ) && strpos( $icon, '/' ) !== false && strpos( $icon, '<' ) !== 0 ) {
				echo "X-AMPS-ICON:" . self::ical_split( 'X-AMPS-ICON:', $icon ) . "\n";
			}
			if ( $reoccurrence_rule ) {
				echo "RRULE:" . $reoccurrence_rule . "\n";
			}
			echo 'LOCATION:' . self::ical_split( 'LOCATION:', self::ical_escape( $location ) ) . "\n";
			if ( ! empty( $geo ) ) {
				echo 'GEO:' . self::ical_split( 'GEO:', $geo ) . "\n";
			}
			$site_email = $new = Amapress::getOption( 'email_from_mail' );
			if ( empty( $site_email ) ) {
				$site_email = amapress_get_default_wordpress_from_email();
			}
			echo 'ORGANIZER;' . self::ical_split( 'ORGANIZER;', "CN=\"$organiser\":MAILTO:$site_email" ) . "\n";
			echo 'URL:' . self::ical_split( 'URL:', $url ) . "\n";
			echo 'DESCRIPTION:' . self::ical_split( 'DESCRIPTION:', self::ical_escape( $desc ) ) . "\n";
			echo "END:VEVENT\n";
		}
		echo "END:VCALENDAR\n";

		//Collect output and echo
		return str_replace( "\n", "\r\n", ob_get_clean() );
	}
} // end class

add_action( 'init', 'Amapress_Agenda_ICAL_Export::load' );