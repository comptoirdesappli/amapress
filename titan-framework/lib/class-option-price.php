<?php

/**
 * Number Option Class
 *
 * @author Benjamin Intal
 * @package Titan Framework Core
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Number Option Class
 *
 * @since    1.0
 **/
class TitanFrameworkOptionPrice extends TitanFrameworkOption {

	// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'size'        => 'small', // or medium or large
		'placeholder' => '', // show this when blank
		'default'     => 0,
		'unit'        => '',
	);


	/**
	 * Constructor
	 *
	 * @since    1.4
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
	}


	/**
	 * Cleans up the serialized value before saving
	 *
	 * @param    string $value The serialized value
	 *
	 * @return    string The cleaned value
	 * @since    1.4
	 */
	public function cleanValueForSaving( $value ) {
		if ( $value == '' ) {
			return 0;
		}

		return $value;
	}


	/**
	 * Cleans the value for getOption
	 *
	 * @param    string $value The raw value of the option
	 *
	 * @return    mixes The cleaned value
	 * @since    1.4
	 */
	public function cleanValueForGetting( $value ) {
		if ( $value == '' ) {
			return 0;
		}

		return $value;
	}

	public function getSamplesForCSV( $arg = null ) {
		return array(
			'15.50',
			'15,50',
			'15.50€',
			'15,50€',
		);
	}


	/**
	 * Enqueues the jQuery UI scripts
	 *
	 * @return    void
	 * @since    1.4
	 */
	public function enqueueSlider() {
		wp_enqueue_script( 'jquery-ui-core' );
	}

	/**
	 * Displays the option for admin pages and meta boxes
	 *
	 * @return    void
	 * @since    1.0
	 */
	public function display() {
		$this->echoOptionHeader();
		echo "<div class='number-slider'></div>";
		printf( '<input class="%s-text %s" name="%s" placeholder="%s" id="%s" type="number" value="%s"/> %s <p class="description">%s</p>',
			$this->settings['size'],
			$this->settings['required'] ? 'required' : '',
			$this->getInputName(),
			$this->settings['placeholder'],
			$this->getID(),
			esc_attr( $this->getValue() ),
			$this->settings['unit'],
			$this->settings['desc']
		);
		$this->echoOptionFooter( false );
	}

	public function generateMember() {
		$mn = $this->getMemberName();

		return '
		public function get' . $mn . '() {
			$this->ensure_init();
			return floatval($this->custom[\'' . $this->getID() . '\']);
		}
		public function set' . $mn . '($value) {
			update_post_meta($this->post->ID, \'' . $this->getID() . '\', $value);
		}
		';
	}

	/**
	 * Registers the theme customizer control, for displaying the option
	 *
	 * @param    WP_Customize $wp_enqueue_script The customize object
	 * @param    TitanFrameworkCustomizerSection $section The section where this option will be placed
	 * @param    int $priority The order of this control in the section
	 *
	 * @return    void
	 * @since    1.0
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionPriceControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->getID(),
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'priority'    => $priority,
			'required'    => $this->settings['required'],
			'size'        => $this->settings['size'],
			'unit'        => $this->settings['unit'],
		) ) );
	}
}


/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionPriceControl', 1 );


/**
 * Creates the option for the theme customizer
 *
 * @return    void
 * @since    1.0
 */
function registerTitanFrameworkOptionPriceControl() {
	class TitanFrameworkOptionPriceControl extends WP_Customize_Control {
		public $description;
		public $size;
		public $unit;
		public $required;

		private static $firstLoad = true;

		public function render_content() {
			self::$firstLoad = false;

			?>
            <label class='tf-price'>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <input class="<?php echo esc_attr( $this->size ) ?>-text number <?php echo( $this->required ? 'required' : '' ) ?>"
                       type="text" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
				<?php echo esc_html( $this->unit ) ?>
            </label>
			<?php
			if ( ! empty( $this->description ) ) {
				echo "<p class='description'>{$this->description}</p>";
			}
		}
	}
}
