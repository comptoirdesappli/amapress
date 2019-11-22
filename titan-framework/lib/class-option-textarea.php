<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionTextarea extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'placeholder'        => '', // show this when blank
		'is_code'            => false, // if true, a more code-like font will be used
		'sanitize_callbacks' => array(),
	);

	public function customSave( $postID ) {
		$save = isset( $this->settings['save'] ) ? $this->settings['save'] : null;
		if ( is_callable( $save, true ) ) {
			return call_user_func( $save, $postID );
		} else {
			return false;
		}
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();
		printf( "<textarea class='large-text %s %s' name=\"%s\" placeholder=\"%s\" id=\"%s\" rows='10' cols='50'>%s</textarea>",
			$this->settings['is_code'] ? 'code' : '',
			$this->settings['required'] ? 'required' : '',
			$this->getInputName(),
			$this->settings['placeholder'],
			$this->getID(),
			esc_textarea( stripslashes( $this->getValue() ) )
		);
		$this->echoOptionFooter();
	}

	public function columnDisplayValue( $post_id ) {
		printf( '<span class="large-text %s" >%s</span>',
			$this->settings['is_code'] ? 'code' : '',
			$this->getValue( $post_id )
		);
	}

	public function cleanValueForSaving( $value ) {
		if ( ! empty( $this->settings['sanitize_callbacks'] ) ) {
			foreach ( $this->settings['sanitize_callbacks'] as $callback ) {
				$value = call_user_func_array( $callback, array( $value, $this ) );
			}
		}

		return $value;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionTextareaControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->getID(),
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'is_code'     => $this->settings['is_code'],
			'reduired'    => $this->settings['required'],
			'priority'    => $priority,
		) ) );
	}
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionTextareaControl', 1 );
function registerTitanFrameworkOptionTextareaControl() {
	class TitanFrameworkOptionTextareaControl extends WP_Customize_Control {
		public $description;
		public $is_code;
		public $required;

		public function render_content() {
			?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <textarea
                        class='large-text <?php echo $this->is_code ? 'code' : '' ?> <?php echo $this->required ? 'required' : '' ?>'
                        rows='7'
                        cols='50' <?php $this->link(); ?>><?php echo esc_textarea( stripslashes( $this->value() ) ) ?></textarea>
            </label>
			<?php
			echo "<p class='description'>{$this->description}</p>";
		}
	}
}
