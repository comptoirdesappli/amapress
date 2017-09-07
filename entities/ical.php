<?php

/**
 * Event ICAL feed
 */
class Amapress_Agenda_ICAL_Export {

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

	public static function get_link_href() {
		$lnk = get_feed_link( 'agenda-ical' );
		if ( amapress_is_user_logged_in() ) {
			$user = AmapressUser::getBy( amapress_current_user_id() );

			return $user->addUserLoginKey( $lnk );
		} else {
			return $lnk;
		}
	}

	/**
	 * Creates an ICAL file of events in the database
	 */
	public static function export_events() {
		//Give the ICAL a filename
		$filename = urlencode( 'agenda-ical-' . date( 'Y-m-d-H-i' ) . '.ics' );

//        $user_id = null;
		if ( isset( $_GET['key'] ) ) {
			AmapressUser::logUserByLoginKey( $_GET['key'] );
		}
		$events = Amapress_Calendar::get_next_events();

		//Collect output
		ob_start();

		// File header
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: inline; filename=' . $filename );
		header( 'Content-type: text/calendar; charset=UTF-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' ); // HTTP 1.1
		header( 'Pragma: no-cache' ); // HTTP 1.0
		header( 'Expires: 0' ); // Proxies
		?>
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Amapress//NONSGML Events//FR
        CALSCALE:GREGORIAN
        X-WR-CALNAME:<?php echo self::ical_split( 'X-WR-CALNAME:', get_bloginfo( 'name' ) ); ?> - Agenda
		<?php

//        if (amapress_is_user_logged_in()) {
//            if (AmapressContrats::is_user_active_intermittent()) {
//                $events = AmapressIntermittence_panier::get_intermittent_next_events();
//            } else {
//                $events = AmapressEvents::get_user_next_events();
//            }
//        }
//        else
//            $events = AmapressEvents::get_everyone_next_events();


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
			?>
            BEGIN:VEVENT
            UID:<?php echo $uid; ?>

            SUMMARY:<?php echo self::ical_split( 'SUMMARY:', $title ); ?>

            CATEGORIES:<?php echo self::ical_split( 'CATEGORIES:', $categories ); ?>

            DTSTAMP:<?php echo $dtstamp; ?>

            CREATED:<?php echo $created_date; ?>

            DTSTART:<?php echo $start_date; ?>

            DTEND:<?php echo $end_date; ?>

			<?php if ( $reoccurrence_rule ): ?>
                RRULE:<?php echo $reoccurrence_rule; ?>

			<?php endif; ?>
            LOCATION:<?php echo self::ical_split( 'LOCATION:', $location ); ?>

            ORGANIZER:<?php echo self::ical_split( 'ORGANIZER:', $organiser ); ?>

            URL:<?php echo self::ical_split( 'URL:', $url ); ?>

            DESCRIPTION:<?php echo self::ical_split( 'DESCRIPTION:', $desc ); ?>

            END:VEVENT
			<?php
		}
		?>
        END:VCALENDAR
		<?php

		//Collect output and echo
		$eventsical = ob_get_contents();
		ob_end_clean();
		echo $eventsical;
		exit();
	}

} // end class

add_action( 'init', 'Amapress_Agenda_ICAL_Export::load' );