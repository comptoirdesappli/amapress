<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSelect extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options'           => array(),
		'placeholder'       => '',
		'autocomplete'      => false,
		'cache'             => true,
		'tags'              => false,
		'autoselect_single' => false,
		'custom_csv_sample' => null,
		'wrap_edit'         => true,
		'refresh_button'    => false,
	);

	private $optionsCache = null;

	/**
	 * TitanFrameworkOptionSelect constructor.
	 *
	 * @param array $defaultSecondarySettings
	 */
	public function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
		add_action( 'wp_ajax_tf_select_refresh_' . $this->getID(), array( $this, 'ajaxRefresh' ) );
	}

	public function fetchOptionsWithCache() {
		if ( $this->optionsCache == null || ( isset( $this->settings['cache'] ) && ! $this->settings['cache'] ) ) {
			$this->optionsCache = $this->fetchOptions();
		}

		return $this->optionsCache;
	}

	public function fetchOptions() {
		$options = $this->settings['options'];
		if ( is_callable( $options, false ) ) {
			return call_user_func( $options, $this );
		} else {
			return $options;
		}
	}

	public function getSamplesForCSV() {
		if ( is_callable( $this->settings['custom_csv_sample'], false ) ) {
			return call_user_func( $this->settings['custom_csv_sample'], $this );
		} else if ( is_array( $this->settings['custom_csv_sample'] ) ) {
			return $this->settings['custom_csv_sample'];
		} else {
			return $this->fetchOptionsWithCache();
		}
	}

	protected function getEditLink( $value ) {
		return null;
	}

	protected function wrapEditLink( $value, $label ) {
		$href = $this->getEditLink( $value );
		if ( empty( $href ) || false === $this->settings['wrap_edit'] ) {
			return $label;
		}

		return "<a class='option-link' href='$href' target='_blank'>$label</a>";
	}

	public function columnDisplayValue( $post_id ) {
		$values = $this->getValue( $post_id );
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$titles      = array();
		$used_values = array();

		$empty_display = ! empty( $this->settings['empty_display'] ) ? $this->settings['empty_display'] : '';

		foreach ( $this->fetchOptionsWithCache() as $value => $label ) {

			// this is if we have option groupings
			if ( is_array( $label ) ) {
				foreach ( $label as $subValue => $subLabel ) {
					if ( empty( $subValue ) ) {
						$subLabel = $empty_display;
					}
					if ( in_array( $subValue, $values ) ) {
						$titles[]      = $this->wrapEditLink( $subValue, $subLabel );
						$used_values[] = $subValue;
					}
				}
			} // this is for normal list of options
			else {
				if ( in_array( $value, $values ) ) {
					if ( empty( $value ) ) {
						$label = $empty_display;
					}
					$titles[]      = $this->wrapEditLink( $value, $label );
					$used_values[] = $value;
				}
			}
		}

		foreach ( $values as $v ) {
			if ( in_array( $v, $values ) ) {
				continue;
			}
			$titles[] = $v;
		}

		echo implode( ',', $titles );
	}

	public function columnExportValue( $post_id ) {
		$values = $this->getValue( $post_id );
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$titles      = array();
		$used_values = array();

		$empty_display = ! empty( $this->settings['empty_display'] ) ? $this->settings['empty_display'] : '';

		foreach ( $this->fetchOptionsWithCache() as $value => $label ) {

			// this is if we have option groupings
			if ( is_array( $label ) ) {
				foreach ( $label as $subValue => $subLabel ) {
					if ( empty( $subValue ) ) {
						$subLabel = $empty_display;
					}
					if ( in_array( $subValue, $values ) ) {
						$titles[]      = $subLabel;
						$used_values[] = $subValue;
					}
				}
			} // this is for normal list of options
			else {
				if ( empty( $value ) ) {
					$label = $empty_display;
				}
				if ( in_array( $value, $values ) ) {
					$titles[]      = $label;
					$used_values[] = $value;
				}
			}
		}

		foreach ( $values as $v ) {
			if ( in_array( $v, $values ) ) {
				continue;
			}
			$titles[] = $v;
		}

		echo implode( ',', $titles );
	}

	public function cleanValueForSaving( $value ) {
		$value = array_filter( (array) $value, function ( $v ) {
			return ! empty( $v );
		} );
		if ( count( $value ) <= 1 ) {
			return array_shift( $value );
		} else {
			return $value;
		}
	}

	public function cleanValueForGetting( $value ) {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}
		$value = array_filter( $value, function ( $v ) {
			return ! empty( $v );
		} );
		if ( count( $value ) <= 1 ) {
			return array_shift( $value );
		} else {
			return $value;
		}
	}


	public function echoSelect( $options = null, $post_id = null ) {
		$multiple = isset( $this->settings['multiple'] ) && true == $this->settings['multiple'] ? 'multiple' : '';
		$name     = $this->getID();
		$val      = $this->getValue( $post_id );
		if ( ! is_array( $val ) ) {
			$val = array( $val );
		}

		if ( ! empty( $multiple ) ) {
			$name = "{$name}[]";
		}

		if ( empty( $options ) ) {
			$options = $this->fetchOptions();
		}

		if ( $multiple ) {
			foreach ( $options as $k => $v ) {
				$options[ $k ] = $this->wrapEditLink( $k, $v );
			}
		}

		$placeholder = empty( $this->settings['placeholder'] ) ? '— ' . __( 'Select', TF_I18NDOMAIN ) . ' —' : $this->settings['placeholder'];

		?>
        <select id="<?php echo $this->getID() ?>"
                name="<?php echo $name; ?>"
                class="<?php echo( $this->settings['required'] ? 'required' : '' ) ?>" <?php echo $multiple; ?>
                data-placeholder="<?php echo esc_attr( $placeholder ) ?>"
        ><?php
			tf_parse_select_options( $options, $val );
			?></select>
		<?php
	}

	public function ajaxRefresh() {
		$this->echoSelect( null, intval( $_POST['post_id'] ) );
		wp_die();
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		if ( $this->isReadonly() ) {
			$this->columnDisplayValue( $this->getPostID() );
		} else {
			$multiple = isset( $this->settings['multiple'] ) && true == $this->settings['multiple'] ? 'multiple' : '';
//            $name = $this->getID();
			$val = (array) $this->getValue();

//            if (!empty($multiple)) {
//                $name = "{$name}[]";
//            }

			$options = $this->fetchOptions();

			$autocomplete = $this->settings['autocomplete'] === true || ( is_int( $this->settings['autocomplete'] ) && count( $options ) > $this->settings['autocomplete'] );

//            if ($multiple) {
//                foreach ($options as $k => $v) {
//                    $options[$k] = $this->wrapEditLink($k, $v);
//                }
//            }


			?><span id="<?php echo $this->getID() ?>-wrapper"
                    class="tf-select-wrapper"><?php $this->echoSelect( $options ); ?></span><?php

			if ( $this->settings['refresh_button'] ) {
				echo "<button id='{$this->getID()}-refresh' class='tf-select-refresh'><span class=\"dashicons dashicons-update\"></span></button>";
			}
			echo '<br/>';

			$init_select = '';
			if ( $autocomplete ) {
				$init_select = 'jQuery("#' . $this->getID() . '").select2({
        allowClear: true,
		  escapeMarkup: function(markup) {
			return markup;
		  },
		  templateResult: function(data) {
			return jQuery("<span>"+data.text+"</span>");
		  },
		  templateSelection: function(data) {
			return jQuery("<span>"+data.text+"</span>");
		  },
		  width: \'auto\'
    });';
				echo '<script type="text/javascript">
//<![CDATA[
jQuery(function() {
    ' . $init_select . '
});
//]]>
</script>';
			}

			echo '<script type="text/javascript">
//<![CDATA[
jQuery(function() {
    jQuery("#' . $this->getID() . '-refresh").click(
        function() {
        	var data = {
		    	\'action\': \'tf_select_refresh_' . $this->getID() . '\',
		    	\'post_id\': \'' . $this->getPostID() . '\'
	    	};
        	jQuery.post(ajaxurl, data, function(response) {
                jQuery("#' . $this->getID() . '-wrapper").html(response);
            	' . $init_select . '
        	});
        	return false;
        }
    );
});
//]]>
</script>';

			if ( ! $multiple ) {
				$val  = array_shift( $val );
				$href = $this->getEditLink( $val );
				if ( ! empty( $href ) ) {
					$label = isset( $options[ $val ] ) ? "Editer {$options[$val]}" : 'Editer';
					echo $this->wrapEditLink( $val, $label );
				}
			}
		}

		$this->echoOptionFooter();
	}

	public function echoFilter( $args ) {
		$placeholder = empty( $args['placeholder'] ) ? '— ' . __( 'Tous', TF_I18NDOMAIN ) . ' —' : $args['placeholder'];
		$name        = $args['name'];

		if ( is_callable( $args['custom_options'], false ) ) {
			$options = call_user_func( $args['custom_options'], $args );
		} else {
			$old_placeholder               = $this->settings['placeholder'];
			$this->settings['placeholder'] = $placeholder;
			$options                       = $this->fetchOptionsWithCache();
			$this->settings['placeholder'] = $old_placeholder;
		}

		if ( count( $options ) <= 2 ) {
			return;
		}

		?><select id="<?php echo $name ?>"
                  name="<?php echo $name; ?>"
                  data-placeholder="<?php echo esc_attr( $placeholder ) ?>"
        ><?php
		tf_parse_select_options( $options, isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : null );
		?></select><?php
	}


	public function generateMember() {
		$mn    = $this->getMemberName();
		$cases = '';
		foreach ( $this->fetchOptions() as $k => $v ) {
			if ( is_array( $v ) ) {
				foreach ( $v as $kk => $vv ) {
					$cases .= "\ncase '$kk':\n\treturn '$vv';";
				}
			} else {
				$cases .= "\ncase '$k':\n\treturn '$v';";
			}
		}

		return '
		public function get' . $mn . 'Display() {
			$this->ensure_init();
			switch ($this->custom[\'' . $this->getID() . '\']) {
				' . $cases . '
				default:
					return $this->custom[\'' . $this->getID() . '\'];
			}
		}
		public function get' . $mn . '() {
			$this->ensure_init();
			return $this->custom[\'' . $this->getID() . '\'];
		}
		public function set' . $mn . '($value) {
			update_post_meta($this->post->ID, \'' . $this->getID() . '\', $value);
		}
		';
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
//		$isAssociativeArray = false;
//
//		if ( count( $this->settings['options'] ) ) {
//			foreach ( $this->settings['options'] as $value => $label ) {
//				$isAssociativeArray = is_array( $label );
//				break;
//			}
//		}

		// Not associative array, do normal control
		// if ( ! $isAssociativeArray ) {
		// $class = "TitanFrameworkCustomizeControl";
		//
		// // Associative array, custom make the control
		// } else {
		$class = 'TitanFrameworkOptionSelectControl';
		// }
		$wp_customize->add_control( new $class( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'type'        => 'select',
			'choices'     => $this->fetchOptions( $this->settings['options'] ),
			'settings'    => $this->getID(),
			'required'    => $this->settings['required'],
			'description' => $this->settings['desc'],
			'priority'    => $priority,
		) ) );
	}
}


/*
 * We create a new control for the theme customizer (for the grouped options only)
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectControl', 1 );
function registerTitanFrameworkOptionSelectControl() {
	class TitanFrameworkOptionSelectControl extends WP_Customize_Control {
		public $description;
		public $required;

		public function render_content() {
			?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <select <?php $this->link(); ?> class="<?php echo( $this->required ? 'required' : '' ) ?>">
					<?php tf_parse_select_options( $this->choices, (array) $this->value() ); ?>
                </select>
            </label>
			<?php

			echo "<p class='description'>{$this->description}</p>";
		}
	}
}

/**
 * Helper function for parsing select options
 *
 * This function is used to reduce duplicated code between the TF option
 * and the customizer control.
 *
 * @since 1.9
 *
 * @param array $options List of options
 * @param array $val Current value
 *
 * @return void|string
 */
function tf_parse_select_options( $options, $val = array(), $echo = true ) {
	/* No options? Duh... */
	if ( empty( $options ) ) {
		return '';
	}

	/* Make sure the current value is an array (for multiple select) */
	if ( ! is_array( $val ) ) {
		$val = (array) $val;
	}

	$ret = '';
	foreach ( $options as $value => $label ) {

		// this is if we have option groupings
		if ( is_array( $label ) ) {
			$ret .= '<optgroup label="' . $value . '">';
			foreach ( $label as $subValue => $subLabel ) {
				$ret .= sprintf( '<option value="%s" %s %s>%s</option>',
					esc_attr( $subValue ),
					in_array( $subValue, $val ) ? 'selected="selected"' : '',
					disabled( stripos( $subValue, '!' ), 0, false ),
					esc_html( $subLabel )
				);
			}
			$ret .= '</optgroup>';
		} // this is for normal list of options
		else {
			$ret .= sprintf( '<option value="%s" %s %s>%s</option>',
				esc_attr( $value ),
				in_array( $value, $val ) ? 'selected="selected"' : '',
				disabled( stripos( $value, '!' ), 0, false ),
				esc_html( $label )
			);
		}
	}

	if ( $echo ) {
		echo $ret;
	} else {
		return $ret;
	}
}

