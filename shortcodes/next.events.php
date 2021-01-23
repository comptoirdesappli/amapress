<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_next_events_shortcode( $atts ) {
	amapress_ensure_no_cache();

	$atts           = shortcode_atts( array(
		'user'           => amapress_current_user_id(),
		'date_separator' => '',
		'past_days'      => 6 * 30,
		'next_days'      => 6 * 30,
		'show_picto'     => 'yes',
		'navigable'      => 'yes',
		'show_text'      => 'no',
		'max'            => 3,
		'slidesToShow'   => 1,
		'slidesToScroll' => 1,
	), $atts, 'next_events' );
	$orig_max       = intval( $atts['max'] );
	$date_separator = $atts['date_separator'];
	$slidesToShow   = intval( $atts['slidesToShow'] );
	$slidesToScroll = intval( $atts['slidesToScroll'] );
//    $up_to_date = 0;
//    if (!empty($atts['up_to_date']))
//        $up_to_date = is_int($atts['up_to_date']) ? intval($atts['up_to_date']) : DateTime::createFromFormat('Y-m-d', $atts['up_to_date'])->getTimestamp();

	$show_icon = Amapress::toBool( $atts['show_picto'] );
	$show_text = Amapress::toBool( $atts['show_text'] );
	$navigable = Amapress::toBool( $atts['navigable'] );
	$past_days = intval( $atts['past_days'] );
	$next_days = intval( $atts['next_days'] );
	if ( ! $navigable ) {
		$past_days = 0;
		$next_days = 0;
	}

	$from_date = Amapress::add_days( amapress_time(), - $past_days );
	$to_date   = Amapress::add_days( amapress_time(), $next_days );
	if ( ! $navigable ) {
		$to_date = 0;
	}

	/** @var Amapress_EventEntry[] $next_events */
	$next_events = null;
	$is_public   = false;
	if ( ! amapress_is_user_logged_in() ) {
		$next_events = Amapress_Calendar::get_next_events( $from_date );
		$is_public   = true;
	} else {
		$user_id = AmapressUsers::get_user_id( $atts['user'] );
		if ( AmapressContrats::is_user_active_intermittent( $user_id ) ) {
			$next_events = Amapress_Calendar::get_next_events( $from_date );
			if ( empty( $next_events ) || count( $next_events ) == 0 ) {
				return '<span>' . __( 'Il n\'y a pas de paniers disponibles', 'amapress' ) . '</span>';
			}
		} else {
			$next_events = Amapress_Calendar::get_next_events( $from_date );
			if ( empty( $next_events ) || count( $next_events ) == 0 ) {
				return '<span>' . __( 'Vous n\'avez pas de contrat en cours avec l\'un de nos producteurs', 'amapress' ) . '</span>';
			}
		}
	}
	$had_events = false;
//    var_dump($next_events);
	$lieu_hour_dic   = array();
	$dt_dic          = array();
	$evts            = array();
	$today_date      = Amapress::start_of_day( amapress_time() );
	$type_priorities = array();
//    $is_last_date = false;
	foreach ( $next_events as $event ) {
		$dt      = $event->getStartDate();
		$dt_end  = $event->getEndDate();
		$ev_date = date( 'd/m/Y', $dt );

		$dt_dic[ $ev_date ] = $dt;
		if ( ( $to_date > 0 ) && ( $dt > $to_date ) ) {
			break;
		}

		$days = ( Amapress::start_of_day( $dt_end ) - Amapress::start_of_day( $dt ) ) / DAY_IN_SECONDS + 1;
		for ( $day = 0; $day < $days; $day ++ ) {
			$ev_date = date( 'd/m/Y', $dt );

			$dt_dic[ $ev_date ]          = $dt;
			$lieu_hour                   = $event->getLieu()->getLieuId() . '_' . date( 'H:i', $dt ) . '_' . date( 'H:i', $dt_end );
			$lieu_hour_dic[ $lieu_hour ] = array( 'lieu' => $event->getLieu(), 'date' => $dt, 'date_end' => $dt_end );
			$type                        = $event->getType();
			$type_priorities[ $type ]    = $event->getPriority();
			if ( ! isset( $evts[ $ev_date ] ) ) {
				$evts[ $ev_date ] = array();
			}
			if ( ! isset( $evts[ $ev_date ][ $lieu_hour ] ) ) {
				$evts[ $ev_date ][ $lieu_hour ] = array();
			}
			if ( ! isset( $evts[ $ev_date ][ $lieu_hour ][ $type ] ) ) {
				$evts[ $ev_date ][ $lieu_hour ][ $type ] = array();
			}
			$evts[ $ev_date ][ $lieu_hour ][ $type ][] = $event;

			$dt += DAY_IN_SECONDS;
		}
	}

	$month_seps = array();
	$last_date  = null;
	foreach ( $evts as $dt => $arr ) {
		$ev_date = $dt_dic[ $dt ];
		if ( ! $last_date || date( 'm', $last_date ) != date( 'm', $ev_date ) ) {
			$month_seps[ $ev_date ] = $ev_date;
		}
		$last_date = $ev_date;
	}


	$next_events_id = uniqid( 'ampsnevts_' );
	$ret            = '';
//    $ret = $is_public ? '<h4>Exemple</h4>' : '';
	$ret .= '<div class="next-events">';
	$ret .= '<div id="' . $next_events_id . '" class="next-events-slick">';

	$last_date   = null;
	$first_lieu  = null;
	$rett        = '';
	$past_events = array();
	$i           = 0;
	foreach ( array_reverse( $evts, true ) as $dt => $arr ) {
		$ev_date = $dt_dic[ $dt ];
		if ( $ev_date >= $today_date ) {
			continue;
		}
		if ( ! empty( $date_separator ) ) {
			$rett = apply_filters( "amapress_get_agenda_date_separator_$date_separator", '', $last_date, $ev_date ) . $rett;
		}
//        if ($i == 0) {
//            if ($ev_date < $today_date) {
//                $first_visible_event++;
//            }
//        }
		if ( isset( $month_seps[ $ev_date ] ) ) {
			$rett = '<div class="event-month-sep"><h4>' . date_i18n( 'F Y', $ev_date ) . '</h4></div>' . $rett;
		}
		$had_events = true;
		$rett       = amapress_get_event_html( $ev_date, $arr, $lieu_hour_dic, $first_lieu, $is_public, $type_priorities, $show_text, $show_icon ) . $rett;
		if ( $i == $orig_max - 1 ) {
			$past_events[] = $rett;
			$i             = 0;
			$rett          = '';
		} else {
			$i ++;
		}
		$last_date = $ev_date;
	}
	if ( ! empty( $rett ) ) {
		$past_events[] = $rett;
	}
	foreach ( array_reverse( $past_events ) as $ev ) {
		$ret .= '<div class="event-slide">';
		$ret .= $ev;
		$ret .= '</div>';
	}

	$first_visible_event = count( $past_events );
	$last_date           = null;
	$had_futur_event     = false;
	$i                   = 0;
	foreach ( $evts as $dt => $arr ) {
		$ev_date = $dt_dic[ $dt ];
		if ( $ev_date < $today_date ) {
			continue;
		}
		if ( ! empty( $date_separator ) ) {
			$ret .= apply_filters( "amapress_get_agenda_date_separator_$date_separator", '', $last_date, $ev_date );
		}
		$had_futur_event = true;
		if ( $i == 0 ) {
//            if ($ev_date < $today_date) {
//                $first_visible_event++;
//            }
			$ret .= '<div class="event-slide">';
		}
		if ( isset( $month_seps[ $ev_date ] ) ) {
			$ret .= '<div class="event-month-sep"><h4>' . date_i18n( 'F Y', $ev_date ) . '</h4></div>';
		}
		$had_events = true;
		$ret        .= amapress_get_event_html( $ev_date, $arr, $lieu_hour_dic, $first_lieu, $is_public, $type_priorities, $show_text, $show_icon );
		if ( $i == $orig_max - 1 ) {
			$ret .= '</div>';
			$i   = 0;
		} else {
			$i ++;
		}

		$last_date = $ev_date;
	}
	if ( $i > 0 && $i <= $orig_max - 1 ) {
		$ret .= '</div>';
	}
	if ( count( $evts ) > 0 ) {
		if ( ! empty( $date_separator ) ) {
			$ret .= apply_filters( "amapress_get_agenda_date_separator_$date_separator", '', $last_date, null );
		}
	}
	if ( ! $had_futur_event ) {
		$ret .= '<div class="event-slide">';
		$ret .= '<span>' . __( 'Pas d\'évènements à venir', 'amapress' ) . '</span>';
		$ret .= '</div>';
	}

	$ret .= '</div>';
	$ret .= '</div>';
//  rows: '.$max.',

	if ( ! $had_events ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '<span>' . __( 'Il n\'y a rien dans la calendrier public', 'amapress' ) . '</span>';
		} else {
			$user_id = AmapressUsers::get_user_id( $atts['user'] );
			if ( AmapressContrats::is_user_active_intermittent( $user_id ) ) {
				return '<span>' . __( 'Il n\'y a pas de paniers disponibles', 'amapress' ) . '</span>';
			} else {
				return '<span>' . __( 'Vous n\'avez pas de contrat en cours avec l\'un de nos producteurs', 'amapress' ) . '</span>';
			}
		}
	}

	if ( $navigable ) {
		$ret .= '<script type="text/javascript">jQuery(function($) {
$("#' . $next_events_id . '").slick({
  adaptiveHeight: true,
  infinite: false,
//  variableWidth: true,
  initialSlide: ' . $first_visible_event . ',
  slidesToShow: ' . $slidesToShow . ',
  slidesToScroll: ' . $slidesToScroll . ',
//  /*dots: true,*/
});
});</script>';
	}
//    var_dump(esc_html($ret));
	//'.$first_visible_event.'
	return $ret;
}

function amapress_get_event_html( $ev_date, $arr, $lieu_hour_dic, $first_lieu, $is_public, $type_priorities, $show_text = true, $show_icon = true ) {
	$types = array();
	foreach ( $arr as $lieu_hour => $arr2 ) {
		foreach ( $arr2 as $type => $evs ) {
			$types[] = $type;
		}
	}
	usort( $types, function ( $a, $b ) use ( $type_priorities ) {
		$pa = $type_priorities[ $a ];
		$pb = $type_priorities[ $b ];
		if ( $pa == $pb ) {
			return 0;
		}

		return $pa < $pb ? - 1 : 1;
	} );

	$ret = '';
	$ret .= '<div class="event">';
	$ret .= '<div class="evt-date evt-date-type-' . array_shift( $types ) . '">';
	$ret .= '<div class="evt-dayname">' . date_i18n( 'D', $ev_date ) . '</div>';
	$ret .= '<div class="evt-daynum">' . date_i18n( 'j', $ev_date ) . '</div>';
	$ret .= '<div class="evt-month">' . date_i18n( 'M', $ev_date ) . '</div>';
	$ret .= '</div>';
	$ret .= '<div class="evt-cnt">';
	foreach ( $arr as $lieu_hour => $arr2 ) {
		/** @var iAmapress_Event_Lieu $lieu */
		$lieu = $lieu_hour_dic[ $lieu_hour ]['lieu'];
		if ( empty( $first_lieu ) ) {
			$first_lieu = $lieu;
		}
//        if ($is_public && $first_lieu->getLieuId() != $lieu->getLieuId()) continue;

		$ret      .= '<div class="evt-cnt-inner">';
		$hour     = date_i18n( 'H:i', $lieu_hour_dic[ $lieu_hour ]['date'] );
		$hour_end = date_i18n( 'H:i', $lieu_hour_dic[ $lieu_hour ]['date_end'] );
		$ret      .= '<div class="evt-lieu-hours">';
		$ret      .= '<div class="evt-hours"><div class="evt-hour">' . $hour . '</div><div class="evt-hour-sep"><i class="fa fa-chevron-down" aria-hidden="true"></i></div><div class="evt-hour-end">' . $hour_end . '</div></div>';

		foreach ( $arr2 as $type => $evs ) {
			$ret      .= '<div class="evt-type evt-type-' . $type . '">';
			$ret      .= ''; //amapress_get_icon_html('agenda_resp_distrib_icon', 'fa-balance-scale')
			$init     = true;
			$ids      = array();
			$has_text = false;
			/** @var Amapress_EventEntry $ev */
			foreach ( $evs as $ev ) {
				$id = $ev->getId();
				if ( ! empty( $id ) && ! in_array( $id, $ids ) ) {
					$ids[] = $id;
				}
				if ( ! $init && $has_text ) {
					$ret .= ', ';
				}
				$icon    = $ev->getIcon();
				$has_img = false;
				if ( ! empty( $icon ) && strpos( $icon, '/' ) !== false && strpos( $icon, '<' ) !== 0 ) {
					$alt     = esc_attr( $ev->getAlt() );
					$icon    = "<img src='$icon' alt='{$alt}'/>";
					$has_img = true;
				}
				$has_text = $show_text || empty( $icon );
				$ret      .= '<a class="evt-link ' . ( $has_text ? ' evt-text ' : '' ) . ( $has_img ? ' evt-img ' : '' ) . ' ' . $ev->getClass() . '" href="' . $ev->getLink() . '">' . ( $show_icon ? $icon : '' ) . ( $has_text ? $ev->getLabel() : '' ) . '</a>';
				$init     = false;
			}
			$ret .= '</div>';

			if ( $type == 'distribution' && count( $ids ) > 0 ) {
				$dist_id = Amapress::resolve_post_id( $ids[0], AmapressDistribution::INTERNAL_POST_TYPE );
				if ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );
					if ( $dist ) {
						$query = array(
							'status' => 'to_exchange',
						);

						$query['contrat_instance_id'] = $dist->getContratIds();
						$query['date']                = intval( $dist->getDate() );
						$query['lieu_id']             = $lieu->getLieuId();
					}
				}
			}
		}

//            $lieu_shortname = $lieu->getShortName();
//            $lieu_name = !empty($lieu_shortname) ? $lieu_shortname : $lieu->getTitle();

		$ret .= '<div class="evt-lieu evt-lieu-' . $lieu->getLieuId() . '"><span class="evt-lieu-cnt"><i class="fa fa-map-marker" aria-hidden="true"></i> <a href="' . $lieu->getLieuPermalink() . '" class="evt-lieu-link">' . esc_html( $lieu->getLieuTitle() ) . '</a></span></div>';
		$ret .= '</div>';
		$ret .= '</div>';
	}
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}
