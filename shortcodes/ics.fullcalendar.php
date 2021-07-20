<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 31/01/2019
 * Time: 08:24
 * Original code from https://github.com/larrybolt/online-ics-feed-viewer
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_fullcalendar( $atts ) {
	static $amapress_fullcalendar = 1;
	$id   = 'amp_fullcalendar' . $amapress_fullcalendar ++;
	$atts = shortcode_atts(
		[
			'header_left'   => 'prev,next today',
			'header_center' => 'title',
			'header_right'  => 'month,listMonth,listWeek',
			'min_time'      => '08:00:00',
			'max_time'      => '22:00:00',
			'icon_size'     => '1em',
			'default_view'  => 'listMonth',
			'hidden_days'   => '',
			'url'           => '',
		],
		$atts
	);

	if ( empty( $atts['url'] ) ) {
		return __( 'Aucune source configurée pour le calendrier', 'amapress' );
	}

	//'https://cors-anywhere.herokuapp.com/'

	ob_start();
	?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('#<?php echo $id; ?>').fullCalendar({
                defaultView: '<?php echo esc_js( $atts['default_view'] ); ?>',
                locale: '<?php echo esc_js( __( 'fr', 'amapress' ) ); ?>',
                hiddenDays: <?php echo wp_json_encode(
		            empty( $atts['hidden_days'] ) ? [] : array_map( 'intval', explode( ',', $atts['hidden_days'] ) )
	            ); ?>,
                timezone: 'local',
                header: {
                    left: '<?php echo esc_js( $atts['header_left'] ); ?>',
                    center: '<?php echo esc_js( $atts['header_center'] ); ?>',
                    right: '<?php echo esc_js( $atts['header_right'] ); ?>'
                },
                views: {
                    listDay: {buttonText: '<?php echo esc_js( __( 'Par jour', 'amapress' ) ); ?>'},
                    listWeek: {buttonText: '<?php echo esc_js( __( 'Par semaine', 'amapress' ) ); ?>'},
                    listMonth: {buttonText: '<?php echo esc_js( __( 'Par mois', 'amapress' ) ); ?>'}
                },
                navLinks: true, // can click day/week names to navigate views
                editable: false,
                minTime: '<?php echo esc_js( $atts['min_time'] ); ?>',
                maxTime: '<?php echo esc_js( $atts['max_time'] ); ?>',
                eventRender: function (event, eventElement) {
                    if (event.imageurl) {
                        $('.fc-title, .fc-list-item-title', eventElement).prepend("<img src='" + event.imageurl + "' style='display:inline-block;vertical-align:middle;width:<?php echo $atts['icon_size']; ?>; height:<?php echo $atts['icon_size']; ?>' />");
                    }
                }
            });
            $.get('<?php echo esc_js( $atts['url'] ); ?>', function (res) {
                var events = [];
                var parsed = ICAL.parse(res);
                parsed[2].forEach(function (event) {
                    if (event[0] !== 'vevent') return;
                    var summary, location, start, end, url, description, css, icon;
                    event[1].forEach(function (event_item) {
                        switch (event_item[0]) {
                            case 'location':
                                location = event_item[3];
                                break;
                            case 'summary':
                                summary = event_item[3];
                                break;
                            case 'description':
                                description = event_item[3];
                                break;
                            case 'url':
                                url = event_item[3];
                                break;
                            case 'dtstart':
                                start = event_item[3];
                                break;
                            case 'dtend':
                                end = event_item[3];
                                break;
                            case 'x-amps-css':
                                css = event_item[3];
                                break;
                            case 'x-amps-icon':
                                icon = event_item[3];
                                break;
                        }
                    });
                    if (summary && location && start && end) {
                        // console.log(summary, 'at', start);
                        var title = summary;
                        if (description)
                            title += ' / ' + description;
                        // if (location)
                        //     title += ' (' + location + ')';
                        events.push({
                            title: title,
                            start: start,
                            end: end,
                            url: url,
                            location: location,
                            className: css ? css.split(' ') : '',
                            imageurl: icon,
                        })
                    }
                });
                $('#<?php echo esc_js( $id ); ?>').fullCalendar('removeEventSources');
                $('#<?php echo esc_js( $id ); ?>').fullCalendar('addEventSource', events);
            })
        });
    </script>
    <div id="<?php echo $id; ?>"></div>
	<?php

	return ob_get_clean();
}