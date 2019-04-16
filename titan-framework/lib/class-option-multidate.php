<?php /** * Multi Date Option Class * * @author Ardalan Naghshineh (www.ardalan.me) * @package Titan Framework Core * */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

/**
 * Multi Date Option Class
 *
 * @since   1.0
 **/
class TitanFrameworkOptionMultiDate extends TitanFrameworkOption {
	public static $default_jquery_date_format = 'yy-mm-dd';
	public static $default_date_format = '!Y-m-d';
	public static $default_date_placeholder = 'YYYY-MM-DD';
// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'date'             => true,
		'show_dates_count' => false,
		'show_dates_list'  => false,
		'show_weeks'       => true,
		'column_value'     => 'dates_count',
	);
	private static $date_epoch;

	/**
	 * Constructor
	 *
	 * @since
	1.4
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
		tf_add_action_once( 'admin_enqueue_scripts', array( $this, 'enqueueDatepicker' ) );
		tf_add_action_once( 'customize_controls_enqueue_scripts', array( $this, 'enqueueDatepicker' ) );
		add_action( 'admin_head', array( __CLASS__, 'createCalendarScript' ) );
		if ( empty( self::$date_epoch ) ) {
			self::$date_epoch = date( self::$default_date_format, 0 );
		}
	}

	/**
	 * Cleans up the serialized value before saving
	 *
	 * @param        string $value The serialized value
	 *
	 * @return       string The cleaned value
	 * @since        1.4
	 */
	public function cleanValueForSaving( $value ) {
		return $value;
	}

	/**
	 * Cleans the value for getOption
	 *
	 * @param        string $value The raw value of the option
	 *
	 * @return        mixes The cleaned value
	 * @since        1.4
	 */
	public function cleanValueForGetting( $value ) {
		return $value;
	}

	public function getSamplesForCSV( $arg = null ) {
		return array(
			date_i18n( self::$default_date_format, mktime( 15, 32, 55, 11, 22, date( 'Y' ) ) ),
			date_i18n( self::$default_date_format, mktime( 15, 32, 55, 10, 16, date( 'Y' ) ) ) . ',' .
			date_i18n( self::$default_date_format, mktime( 15, 32, 55, 11, 22, date( 'Y' ) ) ),
		);
	}

	/**
	 * Enqueues the jQuery UI scripts
	 *
	 * @return  void
	 * @since   1.4
	 */
	public function enqueueDatepicker() {
		global $wp_scripts;
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'tf-jquery-ui-multidates-picker', TitanFramework::getURL( '../js/jquery-ui.multidatespicker.js', __FILE__ ), array(
			'jquery-ui-datepicker',
			'jquery-ui-slider'
		) );
		wp_enqueue_style( "jquery-ui-css", TitanFramework::getURL( '../css/jquery-ui.css', __FILE__ ) );
		wp_enqueue_style( "jquery-ui-structure-css", TitanFramework::getURL( '../css/jquery-ui.structure.css', __FILE__ ) );
		wp_enqueue_style( "jquery-ui-theme-css", TitanFramework::getURL( '../css/jquery-ui.theme.css', __FILE__ ) );
		wp_enqueue_style( "jpepper-ginder-custom-css", TitanFramework::getURL( '../css/pepper-ginder-custom.css', __FILE__ ) );
	}

	/**
	 * Prints out the script the initializes the jQuery Datepicker
	 *
	 * @return  void
	 * @since   1.4
	 */
	public static function createCalendarScript() {
		?>
        <style>
            /* begin: jQuery UI Datepicker moving pixels fix */
            table.ui-datepicker-calendar {
                border-collapse: separate;
            }

            .ui-datepicker-calendar td {
                border: 1px solid transparent;
            }

            /* end: jQuery UI Datepicker moving pixels fix */
            /* begin: jQuery UI Datepicker emphasis on selected dates */
            .ui-datepicker .ui-datepicker-calendar .ui-state-highlight a {
                background: #743620 none; /* a color that fits the widget theme */
                color: white; /* a color that is readeable with the color above */
            }

            /* end: jQuery UI Datepicker emphasis on selected dates */
        </style>
        <script>
            jQuery(document).ready(function ($) {
                "use strict";
                $('.tf-multidate').each(function () {
                    var $input = $('.input-multidate', this);
                    var $dt = $('.multidate-calendar', this);
                    var updateInfos = function () {
                        var dates = $dt.multiDatesPicker('getDates');
                        var dateCount = 0;
                        if (dates) {
                            dateCount = dates.length;
                        }
                        $('#' + $input.attr('id') + '-cnt').text(dateCount);
                        $('#' + $input.attr('id') + '-list').text($dt.multiDatesPicker('value'));
                    };
                    var val = $input.val();
                    if (val != null && val.length > 0) {
                        var dates = val.split(/\s*,\s*/);
                        $dt.multiDatesPicker({
                            dateFormat: '<?php echo self::$default_jquery_date_format ?>',
                            altField: '#' + $input.attr('id'),
                            addDates: dates,
                            showWeek: $input.data('weeks'),
                            onSelect: function (dateText, inst) {
                                updateInfos();
                            }
                        });
                    }
                    else {
                        $dt.multiDatesPicker({
                            dateFormat: '<?php echo self::$default_jquery_date_format ?>',
                            altField: '#' + $input.attr('id'),
                            showWeek: $input.data('weeks'),
                            onSelect: function (dateText, inst) {
                                updateInfos();
                            }
                        });
                    }
                    $input.on("change", updateInfos);
                    updateInfos();
                });
            });
        </script>
		<?php
	}

	public function echoReadonly() {
		$this->echoOptionHeader();
		if ( $this->settings['show_dates_count'] ) {
			printf( '<div class="multidate-count"><span id="%s-cnt">%s</span> date(s)</div>',
				$this->getID(),
				count( explode( ',', $this->getValue() ) ) );
		}
		if ( $this->settings['show_dates_list'] ) {
			printf( '<span class="input-multidate">%s</span>',
				$this->getValue()
			);
		}
		$this->echoOptionFooter( true );
	}

	/**
	 * Displays the option for admin pages and meta boxes
	 *
	 * @return  void
	 * @since   1.0
	 */
	public function display() {
		$this->echoOptionHeader();
		printf( '<div class="multidate-calendar" id="%s-cal" />
                    <input class="input-multidate %s dpDate" name="%s" id="%s" type="hidden" value="%s" data-weeks="%s" />',
			$this->getID(),
			$this->settings['required'] ? 'required' : '',
			$this->getID(),
			$this->getID(),
			esc_attr( $this->getValue() ),
			$this->settings['show_weeks']
		);
		if ( $this->settings['show_dates_count'] ) {
			printf( '<div class="multidate-count"><span id="%s-cnt">0</span> date(s)</div>',
				$this->getID() );
		}
		if ( $this->settings['show_dates_list'] ) {
			printf( '<div class="multidate-list" id="%s-list"></div>',
				$this->getID() );
		}
		$this->echoOptionFooter();
	}

	public function columnDisplayValue( $post_id ) {
		$column_value = $this->settings['column_value'];
		if ( 'dates' == $column_value || 'dates+dates_count' == $column_value ) {
			printf( '<span class="input-multidate">%s</span>',
				$this->getValue( $post_id )
			);
		}
		if ( 'dates_count' == $column_value || 'dates+dates_count' == $column_value ) {
			printf( '<div class="multidate-count"><span id="%s-cnt">%s</span> date(s)</div>',
				$this->getID(),
				count( explode( ',', $this->getValue( $post_id ) ) ) );
		}
	}

	/**
	 * Registers the theme customizer control, for displaying the option
	 *
	 * @param   WP_Customize $wp_enqueue_script The customize object
	 * @param   TitanFrameworkCustomizerSection $section The section where this option will be placed
	 * @param   int $priority The order of this control in the section
	 *
	 * @return  void
	 * @since   1.7
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionMultiDateControl( $wp_customize, $this->getID(), array(
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
}/* * We create a new control for the theme customizer */
add_action( 'customize_register', 'registerTitanFrameworkOptionMultiDateControl', 1 );
/**
 * Creates the option for the theme customizer
 *
 * @return  void
 * @since   1.3
 */
function registerTitanFrameworkOptionMultiDateControl() {
	class TitanFrameworkOptionMultiDateControl extends WP_Customize_Control {
		public $description;
		public $required;

		public function render_content() {
			TitanFrameworkOptionDate::createCalendarScript();
			?>
            <label class='tf-multidate'>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>

                <div class="multidate-calendar"/>
                <input
                        class="input-multidate <?php echo( $this->required ? 'required' : '' ) ?>" <?php $this->link(); ?>
                        type="hidden" value="<?php echo $this->value() ?>"/>
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

?>