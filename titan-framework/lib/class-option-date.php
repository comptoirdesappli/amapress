<?php

/**
 * Date Option Class
 *
 * @author Ardalan Naghshineh (www.ardalan.me)
 * @package Titan Framework Core
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Date Option Class
 *
 * @since    1.0
 **/
class TitanFrameworkOptionDate extends TitanFrameworkOption {
	public static $default_jquery_date_format = 'yy-mm-dd';
	public static $default_date_format = 'Y-m-d';
	public static $default_date_placeholder = 'YYYY-MM-DD';
	public static $default_time_format = 'H:i';
	public static $default_time_placeholder = 'HH:MM';

	// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'date'         => true,
		'time'         => false,
		'column_link'  => false,
		'autocomplete' => false,
	);

	private static $date_epoch;
	private static $time_epoch;

	/**
	 * Constructor
	 *
	 * @since    1.4
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		tf_add_action_once( 'admin_enqueue_scripts', array( $this, 'enqueueDatepicker' ) );
		tf_add_action_once( 'customize_controls_enqueue_scripts', array( $this, 'enqueueDatepicker' ) );
		add_action( 'admin_head', array( __CLASS__, 'createCalendarScript' ) );

		if ( empty( self::$date_epoch ) ) {
			self::$date_epoch = date( self::$default_date_format, 0 );
		}
		if ( empty( self::$time_epoch ) ) {
			self::$time_epoch = date( self::$default_time_format, 0 );
		}
	}

	public function getSQLOrderBy( $order, $type ) {
		return ( 'user' == $type ? 'ORDER BY' : '' ) . ' CAST(amp_pm.meta_value as SIGNED) ' . ( strpos( $order, ' DESC' ) === false ? 'ASC' : 'DESC' );
	}

	public function getSamplesForCSV( $arg = null ) {
		$fmt = self::$default_date_format . ' ' . self::$default_time_format;
		if ( $this->settings['time'] && ! $this->settings['date'] ) {
			$fmt = self::$default_time_format;
		} else if ( ! $this->settings['time'] && $this->settings['date'] ) {
			$fmt = self::$default_date_format;
		}

		return array(
			date_i18n( $fmt, time() ),
		);
	}

	/**
	 * Cleans up the serialized value before saving
	 *
	 * @param string $value The serialized value
	 *
	 * @return    string The cleaned value
	 * @since    1.4
	 */
	public function cleanValueForSaving( $value ) {
		if ( $value == '' ) {
			return 0;
		}
		if ( ! $this->settings['date'] && $this->settings['time'] ) {
			self::$date_epoch = date( self::$default_date_format, 0 );
			$value            = self::$date_epoch . ' ' . $value;
		}
		if ( $this->settings['date'] && ! $this->settings['time'] ) {
			self::$time_epoch = date( self::$default_time_format, 0 );
			$value            = $value . ' ' . self::$time_epoch;
		}

		$dateTimeParsed = DateTime::createFromFormat( self::$default_date_format . ' ' . self::$default_time_format, $value );
		if ( ! $dateTimeParsed ) {
			return 0;
		}

		return $dateTimeParsed->getTimestamp();
	}


	/**
	 * Cleans the value for getOption
	 *
	 * @param string $value The raw value of the option
	 *
	 * @return    mixes The cleaned value
	 * @since    1.4
	 */
	public function cleanValueForGetting( $value ) {
		if ( $value == 0 ) {
			return '';
		}

		return $value;
	}


	/**
	 * Enqueues the jQuery UI scripts
	 *
	 * @return    void
	 * @since    1.4
	 */
	public function enqueueDatepicker() {
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'tf-jquery-ui-timepicker-addon', TitanFramework::getURL( '../js/min/jquery-ui-timepicker-addon-min.js', __FILE__ ), array(
			'jquery-ui-datepicker',
			'jquery-ui-slider'
		) );
	}


	/**
	 * Prints out the script the initializes the jQuery Datepicker
	 *
	 * @return    void
	 * @since    1.4
	 */
	public static function createCalendarScript() {
		?>
        <script>
            jQuery(document).ready(function ($) {
                "use strict";

                jQuery.validator.addMethod("date", function (value, element) {
                        // parseDate throws exception if the value is invalid
                        try {
                            jQuery.datepicker.parseDate('<?php echo self::$default_jquery_date_format ?>', value);
                            return true;
                        } catch (e) {
                            return false;
                        }
                    },
                    "Please enter a valid date"
                );
                var datepickerSettings = {
                    dateFormat: '<?php echo self::$default_jquery_date_format ?>',

                    beforeShow: function (input, inst) {
                        $('#ui-datepicker-div').addClass('tf-date-datepicker');

                        // Fix the button styles
                        setTimeout(function () {
                            jQuery('#ui-datepicker-div')
                                .find('[type=button]').addClass('button').end()
                                .find('.ui-datepicker-close[type=button]').addClass('button-primary');
                        }, 0);
                    },

                    // Fix the button styles
                    onChangeMonthYear: function () {
                        setTimeout(function () {
                            jQuery('#ui-datepicker-div')
                                .find('[type=button]').addClass('button').end()
                                .find('.ui-datepicker-close[type=button]').addClass('button-primary');
                        }, 0);
                    }
                };
                $('.tf-date input[type=text]').each(function () {
                    var $this = $(this);
                    if ($this.hasClass('date') && !$this.hasClass('time')) {
                        $this.datepicker(datepickerSettings);
                    } else if (!$this.hasClass('date') && $this.hasClass('time')) {
                        $this.timepicker(datepickerSettings);
                    } else {
                        $this.datetimepicker(datepickerSettings);
                    }
                });
            });
        </script>
		<?php
	}


	/**
	 * Displays the option for admin pages and meta boxes
	 *
	 * @return    void
	 * @since    1.0
	 */
	public function display() {
		$this->echoOptionHeader();
		$dateFormat  = self::$default_date_format . ' ' . self::$default_time_format;
		$placeholder = self::$default_date_placeholder . ' ' . self::$default_time_placeholder;
		if ( $this->settings['date'] && ! $this->settings['time'] ) {
			$dateFormat  = self::$default_date_format;
			$placeholder = self::$default_date_placeholder;
		} else if ( ! $this->settings['date'] && $this->settings['time'] ) {
			$dateFormat  = self::$default_time_format;
			$placeholder = self::$default_time_placeholder;
		}

		printf( '<input class="input-date%s%s %s" name="%s" placeholder="%s" id="%s" type="text" value="%s" autocomplete="%s" /> <p class="description">%s</p>',
			( $this->settings['date'] ? ' date dpDate' : '' ),
			( $this->settings['time'] ? ' time' : '' ),
			$this->settings['required'] ? 'required' : '',
			$this->getInputName(),
			$placeholder,
			$this->getID(),
			esc_attr( ( $this->getValue() > 0 ) ? date( $dateFormat, $this->getValue() ) : '' ),
			isset( $this->settings['autocomplete'] ) && $this->settings['autocomplete'] ? 'on' : 'off',
			$this->getDesc()
		);
		$this->echoOptionFooter( false );
	}

	protected function wrapColumnLink( $display_html, $post_id ) {
		$column_link = $this->settings['column_link'];
		$href        = null;
		if ( is_callable( $column_link, false ) ) {
			$href = call_user_func( $column_link, $this, $post_id );
		}

		if ( ! empty( $href ) ) {
			return "<a class='option-link' href='$href' target='_blank'>$display_html</a>";
		} else {
			return $display_html;
		}
	}

	public function columnDisplayValue( $post_id ) {
		$dateFormat = self::$default_date_format . ' ' . self::$default_time_format;
		if ( $this->settings['date'] && ! $this->settings['time'] ) {
			$dateFormat = self::$default_date_format;
		} else if ( ! $this->settings['date'] && $this->settings['time'] ) {
			$dateFormat = self::$default_time_format;
		}

		$date = intval( $this->getValue( $post_id ) );
		echo $this->wrapColumnLink(
			sprintf( '<span class="input-date%s%s">%s</span>',
				( $this->settings['date'] ? ' date' : '' ),
				( $this->settings['time'] ? ' time' : '' ),
				( $date > 0 ) ? date_i18n( $dateFormat, $date ) : ''
			),
			$post_id
		);
	}

	public function columnExportValue( $post_id ) {
		echo ( $this->getValue( $post_id ) > 0 ) ? $this->getValue( $post_id ) : '';
	}


	/**
	 * Registers the theme customizer control, for displaying the option
	 *
	 * @param WP_Customize $wp_enqueue_script The customize object
	 * @param TitanFrameworkCustomizerSection $section The section where this option will be placed
	 * @param int $priority The order of this control in the section
	 *
	 * @return    void
	 * @since    1.7
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionDateControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'priority'    => $priority,
			'required'    => $this->settings['required'],
			'date'        => $this->settings['date'],
			'time'        => $this->settings['time'],
		) ) );
	}
}


/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionDateControl', 1 );


/**
 * Creates the option for the theme customizer
 *
 * @return    void
 * @since    1.3
 */
function registerTitanFrameworkOptionDateControl() {
	class TitanFrameworkOptionDateControl extends WP_Customize_Control {
		public $description;
		public $date;
		public $time;
		public $required;

		public function render_content() {

			TitanFrameworkOptionDate::createCalendarScript();

			$dateFormat  = self::$default_date_format . ' ' . self::$default_time_format;
			$placeholder = self::$default_date_placeholder . ' ' . self::$default_time_placeholder;
			if ( $this->date && ! $this->time ) {
				$dateFormat  = self::$default_date_format;
				$placeholder = self::$default_date_placeholder;
			} else if ( ! $this->date && $this->time ) {
				$dateFormat  = self::$default_time_format;
				$placeholder = self::$default_time_placeholder;
			}

			$class = $this->date ? ' date' : '';
			$class .= $this->time ? ' time' : ''
			?>
            <label class='tf-date'>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <input
                        class="input-date<?php echo $class ?> <?php echo( $this->required ? 'required' : '' ) ?>" <?php $this->link(); ?>
                        placeholder="<?php echo $placeholder ?>" type="text" value="<?php echo $this->value() ?>"/>

				<?php
				if ( ! empty( $this->description ) ) {
					echo "<p class='description'>{$this->description}</p>";
				}
				?>
            </label>
			<?php
		}
	}
}