<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionRadio extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options' => array(),
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader( true );

		echo '<fieldset>';

		foreach ( $this->fetchOptions() as $value => $label ) {
			printf( '<label for="%s"><input id="%s" type="radio" name="%s" value="%s" %s/> %s</label><br>',
				$this->getID() . $value,
				$this->getID() . $value,
				$this->getInputName(),
				esc_attr( $value ),
				checked( $this->getValue(), $value, false ),
				$label
			);
		}

		echo '</fieldset>';

		$this->echoOptionFooter( false );
	}

	public function fetchOptions() {
		$options = $this->settings['options'];
		if ( is_callable( $options, false ) ) {
			return call_user_func( $options, $this );
		} else {
			return $options;
		}
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkCustomizeControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'choices'     => $this->fetchOptions(),
			'type'        => 'radio',
			'description' => $this->settings['desc'],
			'priority'    => $priority,
		) ) );
	}
}
