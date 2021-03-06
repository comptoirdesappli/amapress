<?php /** * Custom option * * @package Titan Framework */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/** * Custom option class * * @since 1.0 */
class TitanFrameworkOptionEventScheduler extends TitanFrameworkOption {


	/**
	 * Default settings specific to this option
	 * @var array
	 */
	public $defaultSecondarySettings = array(
		'scheduler_type'      => 'days',
		'default'             => null,
		'hook_name'           => null,
		'hook_args_generator' => null,
		'show_desc'           => true,
		'bare'                => false,
		'show_after'          => false,
		'show_before'         => true,
		'show_resend_links'   => true,
		'show_test_links'     => true,
	);
	private static $default_config = array(
		'days'    => 0,
		'weekday' => null,
		'hours'   => 0,
		'minutes' => 0,
		'pos'     => 'before',
		'enabled' => 0,
	);

	private static $scheduler_options = [];

	/**
	 * TitanFrameworkOptionEventScheduler constructor.
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		self::$scheduler_options[] = $this;
	}

	public function cleanValueForSaving( $value ) {
		$enabled = isset( $_POST[ $this->getID() . '-enabled' ] );
		$hours   = isset( $_POST[ $this->getID() . '-hours' ] ) ? $_POST[ $this->getID() . '-hours' ] : 0;
		$minutes = isset( $_POST[ $this->getID() . '-minutes' ] ) ? $_POST[ $this->getID() . '-minutes' ] : 0;
		$days    = isset( $_POST[ $this->getID() . '-days' ] ) ? $_POST[ $this->getID() . '-days' ] : 0;
		$pos     = isset( $_POST[ $this->getID() . '-pos' ] ) ? $_POST[ $this->getID() . '-pos' ] : 0;
		$weekday = isset( $_POST[ $this->getID() . '-weekday' ] ) ? $_POST[ $this->getID() . '-weekday' ] : null;

		return array(
			'enabled' => $enabled,
			'days'    => $days,
			'weekday' => $weekday,
			'hours'   => $hours,
			'minutes' => $minutes,
			'pos'     => $pos,
		);
	}

	public function setValue( $value, $postID = null ) {
		$ret = parent::setValue( $value, $postID );

		do_action( 'tf_scheduler_option_changed', $this->getID(), $this );

		$this->updateScheduler();

		return $ret;
	}

	public function columnDisplayValue( $postID ) {
		return '';
	}

	public function columnExportValue( $postID ) {
		return '';
	}

	public static function updateAllSchedulers() {
		/** @var TitanFrameworkOptionEventScheduler $option */
		foreach ( self::$scheduler_options as $option ) {
			$option->updateScheduler();
		}
	}

	private function updateScheduler() {
		$now                 = time();
		$hook_name           = $this->settings['hook_name'];
		$hook_args_generator = $this->settings['hook_args_generator'];
		if ( ! empty( $hook_name ) && ! empty( $hook_args_generator ) ) {
			$all_args = is_callable( $hook_args_generator, false ) ?
				call_user_func( $hook_args_generator, $this ) :
				[];
			if ( ! empty( $all_args ) ) {
				$value = $this->getValue();
				$this->clear_all_scheduled_hook( $hook_name );
				if ( isset( $value['enabled'] ) && $value['enabled'] ) {
					foreach ( $all_args as $args ) {
						if ( ! isset( $args['time'] ) ) {
							continue;
						}
						$time              = $args['time'];
						$args['option_id'] = $this->getID();
						unset( $args['time'] );
						$event_date = self::getEventDateTime( $time, $value );
						if ( $event_date > $now ) {
							wp_schedule_single_event( $event_date, $hook_name, [ $args ] );
						}
					}
				}
			}
		}
	}

	private function clear_all_scheduled_hook( $hook ) {
		if ( function_exists( '_get_cron_array' ) && function_exists( '_set_cron_array' ) ) {
			$crons = _get_cron_array();
			if ( empty( $crons ) ) {
				return 0;
			}

			$now     = time();
			$results = 0;
			foreach ( $crons as $timestamp => $cron ) {
				if ( $timestamp < $now ) {
					continue;
				}
				if ( isset( $cron[ $hook ] ) ) {
					foreach ( $crons[ $timestamp ][ $hook ] as $key => $value ) {
						if ( empty( $value['args'][0]['option_id'] ) || $this->getID() == $value['args'][0]['option_id'] ) {
							$results += 1;
							unset( $crons[ $timestamp ][ $hook ][ $key ] );
						}
					}
					if ( empty( $crons[ $timestamp ][ $hook ] ) ) {
						unset( $crons[ $timestamp ][ $hook ] );
					}
				}
				if ( empty( $crons[ $timestamp ] ) ) {
					unset( $crons[ $timestamp ] );
				}
			}

			_set_cron_array( $crons );

			return $results;
		}

		return false;
	}

	/** @return int */
	public static function getEventDateTime( $from_time, $conf ) {
		$conf = wp_parse_args( $conf, self::$default_config );
		//TODO : dont use Amapress::

		$days    = intval( $conf['days'] );
		$hours   = intval( $conf['hours'] );
		$minutes = intval( $conf['minutes'] );
		$weekday = $conf['weekday'];
		$days    = 'after' == $conf['pos'] ? $days : - $days;

		$time = Amapress::start_of_day( $from_time );
		if ( $days != 0 ) {
			$time = Amapress::add_days( $time, $days );
		}

		if ( ! empty( $weekday ) ) {
			$time = strtotime( ( 'after' == $conf['pos'] ? 'next ' : 'last ' ) . $weekday, $time );
		}

		$time += $hours * HOUR_IN_SECONDS + $minutes * 60;

		$time = self::adjustTimezone( $time );

		return $time;
	}

	public static function adjustTimezone( $time, $to_utc = true ) {
		$tz = get_option( 'timezone_string' );
		if ( $tz ) {
			$timezone = new DateTimeZone( $tz );
			$t        = new DateTime();
			$t->setTimestamp( $time );
			$t->setTimezone( $timezone );
			$time += ( $to_utc ? - 1 : 1 ) * $timezone->getOffset( $t );
		} else {
			$time += ( $to_utc ? - 1 : 1 ) * get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		}

		return $time;
	}
//
//	public static function ensureScheduled( $from_time, $conf, $hook_name ) {
//
//	}
	public function cleanValueForGetting( $value ) {
		$value = parent::cleanValueForGetting( $value );

		$default = wp_parse_args( $this->settings['default'],
			self::$default_config );
		if ( empty( $value ) || empty( $value['pos'] ) ) {
			$value = $default;
		} else {
			$value = wp_parse_args( $value, $default );
		}

		return $value;
	}

	public static function getFormattedEventDate( $value, $scheduler_type ) {
		if ( ! $value || ! isset( $value['enabled'] ) || ! $value['enabled'] ) {
			return __( 'Désactivé', 'amapress' );
		}

		$days_input          = $value['days'];
		$hours_minutes_input = $value['hours'] . ':' . $value['minutes'];
		$pos_input           = 'before' == $value['pos'] ? __( 'avant', 'amapress' ) : __( 'après', 'amapress' );
		$days_names          = [
			'Monday'    => __( 'Lundi', 'amapress' ),
			'Tuesday'   => __( 'Mardi', 'amapress' ),
			'Wednesday' => __( 'Mercredi', 'amapress' ),
			'Thursday'  => __( 'Jeudi', 'amapress' ),
			'Friday'    => __( 'Vendredi', 'amapress' ),
			'Saturday'  => __( 'Samedi', 'amapress' ),
			'Sunday'    => __( 'Dimanche', 'amapress' ),
		];
		$weekday_input       = isset( $days_names[ $value['weekday'] ] ) ? $days_names[ $value['weekday'] ] : $value['weekday'];

		$cnt = '';
		if ( 'days' == $scheduler_type ) {
			$cnt = sprintf( __( 'Programmer %s jours %s à %s', 'amapress' ), $days_input, $pos_input, $hours_minutes_input );
		} else if ( 'hours' == $scheduler_type ) {
			$cnt = sprintf( __( 'Programmer %s %s', 'amapress' ), $hours_minutes_input, $pos_input );
		} else if ( 'the_day' == $scheduler_type ) {
			$cnt = sprintf( __( 'Programmer le jour même à %s', 'amapress' ), $hours_minutes_input );
		} else if ( 'some_day' == $scheduler_type ) {
			$cnt = sprintf( __( 'Programmer le %s d\'%s à %s', 'amapress' ), $weekday_input, $pos_input, $hours_minutes_input );
		}

		return $cnt;
	}

	/**
	 * Display for options and meta
	 */
	public function display( $postID = null ) {
		$value = $this->getValue( $postID );

		$days_input          = '<span><input id="' . $this->getID() . '-days" name="' . $this->getID() . '-days" type="number" style="width: 4em" class="number required" min="0" step="1" value="' . $value['days'] . '" ' . disabled( ! $value['enabled'], true, false ) . ' /></span>';
		$hours_minutes_input = '<span><input id="' . $this->getID() . '-hours" name="' . $this->getID() . '-hours" type="number" style="width: 4em"  class="number required" min="0" max="23" step="1" value="' . $value['hours'] . '" ' . disabled( ! $value['enabled'], true, false ) . ' />h<input id="' . $this->getID() . '-minutes" name="' . $this->getID() . '-minutes" type="number" style="width: 4em"  class="number required" min="0" max="59" step="1" value="' . $value['minutes'] . '" ' . disabled( ! $value['enabled'], true, false ) . ' /></span>';
		$pos_input           = '<select id="' . $this->getID() . '-pos" style="width: 5em;min-width: 5em" name="' . $this->getID() . '-pos" class="required" ' . disabled( ! $value['enabled'], true, false ) . '>' .
		                       tf_parse_select_options(
			                       ( ! $this->settings['show_after'] ?
				                       [
					                       'before' => __( 'avant', 'amapress' ),
				                       ]
				                       : ( ! $this->settings['show_before'] ?
					                       [
						                       'after' => __( 'après', 'amapress' ),
					                       ]
					                       :
					                       [
						                       'before' => __( 'avant', 'amapress' ),
						                       'after'  => __( 'après', 'amapress' ),
					                       ] ) ), [ $value['pos'] ], false ) .
		                       '</select>';
		$weekday_input       = '<select id="' . $this->getID() . '-pos" style="width: 5em;min-width: 5em" name="' . $this->getID() . '-pos" class="required" ' . disabled( ! $value['enabled'], true, false ) . '>' .
		                       tf_parse_select_options( [
			                       'Monday'    => __( 'Lundi', 'amapress' ),
			                       'Tuesday'   => __( 'Mardi', 'amapress' ),
			                       'Wednesday' => __( 'Mercredi', 'amapress' ),
			                       'Thursday'  => __( 'Jeudi', 'amapress' ),
			                       'Friday'    => __( 'Vendredi', 'amapress' ),
			                       'Saturday'  => __( 'Samedi', 'amapress' ),
			                       'Sunday'    => __( 'Dimanche', 'amapress' ),
		                       ], [ $value['weekday'] ], false ) .
		                       '</select>';

		$cnt          = '<span><input id="' . $this->getID() . '-enabled" name="' . $this->getID() . '-enabled" type="checkbox" ' . checked( $value['enabled'], true, false ) . ' /></span>';
		$span_enabler = sprintf( __( '<span id="%s-enabler">', 'amapress' ), $this->getID() );
		if ( 'days' == $this->settings['scheduler_type'] ) {
			$cnt .= $span_enabler . sprintf( __( 'Programmer %s jours %s à %s', 'amapress' ), $days_input, $pos_input, $hours_minutes_input ) . '</span>';
		} elseif ( 'hours' == $this->settings['scheduler_type'] ) {
			$cnt .= $span_enabler . sprintf( __( 'Programmer %s %s', 'amapress' ), $hours_minutes_input, $pos_input ) . '</span>';
		} elseif ( 'the_day' == $this->settings['scheduler_type'] ) {
			$cnt .= $span_enabler . sprintf( __( 'Programmer le jour même à %s', 'amapress' ), $hours_minutes_input ) . '</span>';
		} elseif ( 'some_day' == $this->settings['scheduler_type'] ) {
			$cnt .= $span_enabler . sprintf( __( 'Programmer le %s d\'%s à %s', 'amapress' ), $weekday_input, $pos_input, $hours_minutes_input ) . '</span>';
		}

		$cnt .= '<script type="text/javascript">
jQuery(function($) {
  $("#' . $this->getID() . '-enabled").on("change", function() {
    $("*", $("#' . $this->getID() . '-enabler")).prop("disabled", !$(this).is(":checked"));
});  
});
</script>';

		if ( $this->settings['bare'] === true ) {
			echo '<tr><td colspan="2" style="margin: 0; padding: 0">';
			echo $cnt;
			echo '</td></tr>';

			return;
		}

		$hooks               = [];
		$hook_name           = $this->settings['hook_name'];
		$hook_args_generator = $this->settings['hook_args_generator'];
		if ( ! empty( $hook_name ) && ! empty( $hook_args_generator ) ) {
			$all_args = is_callable( $hook_args_generator, false ) ?
				call_user_func( $hook_args_generator, $this ) :
				[];
			if ( ! empty( $all_args ) ) {
				foreach ( $all_args as $args ) {
					if ( ! isset( $args['time'] ) ) {
						continue;
					}
					if ( ! empty( $args['title'] ) ) {
						$hooks[] = [
							'hook_name' => $hook_name,
							'title'     => $args['title'],
							'time'      => $args['time'],
							'args'      => wp_json_encode( [ $args ] )
						];
					}
					unset( $args['time'] );
				}
			}
		}
		$links = '';
		if ( ! empty( $hooks ) ) {
			if ( $this->settings['show_resend_links'] ) {
				$links .= '<p>' . __( 'Liens de renvoi: ', 'amapress' ) . implode( ', ', array_map(
						function ( $hook ) use ( $value ) {
							$hook['action'] = 'tf_event_scheduler_resend';
							$href           = esc_attr( add_query_arg( $hook, admin_url( 'admin-post.php' ) ) );
							$title          = esc_html( $hook['title'] );
							$sent_on        = ! empty( $value['enabled'] ) ? self::adjustTimezone( self::getEventDateTime( $hook['time'], $value ), false ) : 0;
							if ( $sent_on ) {
								$sent_on = __( 'envoyé le ', 'amapress' ) . date_i18n( 'd/m/Y H:i', $sent_on );
							} else {
								$sent_on = __( 'non programmé', 'amapress' );
							}

							return "<a href='$href' target='_blank'>$title ($sent_on)</a>";
						}, $hooks ) ) . '</p>';
			} elseif ( ! empty( $value['enabled'] ) ) {
				$links .= '<p>' . __( 'Envois: ', 'amapress' ) . implode( ', ', array_map(
						function ( $hook ) use ( $value ) {
							$title   = esc_html( $hook['title'] );
							$sent_on = ! empty( $value['enabled'] ) ? self::adjustTimezone( self::getEventDateTime( $hook['time'], $value ), false ) : 0;
							if ( $sent_on ) {
								$sent_on = __( 'envoyé le ', 'amapress' ) . date_i18n( 'd/m/Y H:i', $sent_on );
							} else {
								$sent_on = __( 'non programmé', 'amapress' );
							}

							return esc_html( "$title ($sent_on)" );
						}, $hooks ) ) . '</p>';
			}
		}

		if ( $this->settings['show_test_links'] && ! empty( $hooks ) ) {
			$links .= '<p>' . __( 'Liens de test: ', 'amapress' ) . implode( ', ', array_map(
					function ( $hook ) use ( $value ) {
						$hook['action'] = 'tf_event_scheduler_test';
						$href           = esc_attr( add_query_arg( $hook, admin_url( 'admin-post.php' ) ) );
						$title          = esc_html( $hook['title'] );

						return "<a href='$href' target='_blank'>$title</a>";
					}, $hooks ) ) . '</p>';
		}

		if ( ! empty( $this->settings['name'] ) ) {
			$this->echoOptionHeader( 'before' === $this->settings['show_desc'] );
			echo $cnt;
			echo $links;
			$this->echoOptionFooter( 'before' !== $this->settings['show_desc'] );
		} else {
			$this->echoOptionHeaderBare( 'before' === $this->settings['show_desc'] );
			echo $cnt;
			echo $links;
			$this->echoOptionFooterBare( 'before' !== $this->settings['show_desc'] );
		}
	}
}
