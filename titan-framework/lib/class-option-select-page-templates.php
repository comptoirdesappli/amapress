<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSelectPageTemplates extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
	);

	private static $allPages;

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		// Remember the pages so as not to perform any more lookups
		if ( ! isset( self::$allPages ) ) {
			self::$allPages = get_page_templates();
		}

		echo "<select name='" . esc_attr( $this->getID() ) . "' class='" . ( $this->settings['required'] ? 'required' : '' ) . "'>";

		// The default value (nothing is selected)
		printf( "<option value='%s' %s>%s</option>",
			'page.php',
			selected( $this->getValue(), 'page.php', false ),
			__( 'Modèle par défaut', TF_I18NDOMAIN )
		);

		// Print all the other pages
		foreach ( self::$allPages as $title => $filename ) {
			printf( "<option value='%s' %s>%s</option>",
				esc_attr( $filename ),
				selected( $this->getValue(), $filename, false ),
				$title
			);
		}
		echo '</select>';

		$this->echoOptionFooter();
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionSelectPageTemplatesControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'required'    => $this->settings['required'],
			'priority'    => $priority,
		) ) );
	}
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectPageTemplatesControl', 1 );
function registerTitanFrameworkOptionSelectPageTemplatesControl() {
	class TitanFrameworkOptionSelectPageTemplatesControl extends WP_Customize_Control {
		public $description;
		public $num;
		public $required;

		public function render_content() {
			$page_templates = get_page_templates();

			?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <select <?php $this->link(); ?> class="<?php echo( $this->required ? 'required' : '' ) ?>">
					<?php
					// The default value (nothing is selected)
					printf( "<option value='%s' %s>%s</option>",
						'page.php',
						selected( $this->value(), 'page.php', false ),
						__( 'Modèle par défaut', TF_I18NDOMAIN )
					);

					// Print all the other pages
					foreach ( $page_templates as $title => $filename ) {
						printf( "<option value='%s' %s>%s</option>",
							esc_attr( $filename ),
							selected( $this->value(), $filename, false ),
							$title
						);
					}
					?>
                </select>
            </label>
			<?php
			echo "<p class='description'>{$this->description}</p>";
		}
	}
}

