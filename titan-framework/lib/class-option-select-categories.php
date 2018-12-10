<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSelectCategories extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'default'        => '0', // show this when blank
		'orderby'        => 'name',
		'order'          => 'ASC',
		'taxonomy'       => 'category',
		'hide_empty'     => false,
		'show_count'     => false,
		'refresh_button' => true,
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		$args = array(
			'orderby'    => $this->settings['orderby'],
			'order'      => $this->settings['order'],
			'taxonomy'   => $this->settings['taxonomy'],
			'hide_empty' => $this->settings['hide_empty'] ? '1' : '0',
		);

		$args = apply_filters( "tf_select_categories_query_args", $args, $this );
		$args = apply_filters( "tf_{$this->getID()}_query_args", $args );

		$categories = get_categories( $args );

		echo "<select name='" . esc_attr( $this->getID() ) . "' class='" . ( $this->settings['required'] ? 'required' : '' ) . "'>";

		// The default value (nothing is selected)
		printf( "<option value='%s' %s>%s</option>",
			'0',
			selected( $this->getValue(), '0', false ),
			'— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
		);

		// Print all the other pages
		foreach ( $categories as $category ) {
			printf( "<option value='%s' %s>%s</option>",
				esc_attr( $category->term_id ),
				selected( $this->getValue(), $category->term_id, false ),
				$category->name . ( $this->settings['show_count'] ? ' (' . $category->count . ')' : '' )
			);
		}
		echo '</select>';

		$this->echoOptionFooter();
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionSelectCategoriesControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'orderby'     => $this->settings['orderby'],
			'order'       => $this->settings['order'],
			'taxonomy'    => $this->settings['taxonomy'],
			'required'    => $this->settings['required'],
			'hide_empty'  => $this->settings['hide_empty'],
			'show_count'  => $this->settings['show_count'],
			'priority'    => $priority,
		) ) );
	}
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectCategoriesControl', 1 );
function registerTitanFrameworkOptionSelectCategoriesControl() {
	class TitanFrameworkOptionSelectCategoriesControl extends WP_Customize_Control {
		public $description;
		public $orderby;
		public $order;
		public $taxonomy;
		public $hide_empty;
		public $show_count;
		public $required;

		public function render_content() {
			$args = array(
				'orderby'    => $this->orderby,
				'order'      => $this->order,
				'taxonomy'   => $this->taxonomy,
				'hide_empty' => $this->hide_empty ? '1' : '0',
			);

			$categories = get_categories( $args );

			?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <select <?php $this->link(); ?> class="<?php echo( $this->required ? 'required' : '' ) ?>">
					<?php
					// The default value (nothing is selected)
					printf( "<option value='%s' %s>%s</option>",
						'0',
						selected( $this->value(), '0', false ),
						'— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
					);

					// Print all the other pages
					foreach ( $categories as $category ) {
						printf( "<option value='%s' %s>%s</option>",
							esc_attr( $category->term_id ),
							selected( $this->value(), $category->term_id, false ),
							$category->name . ( $this->show_count ? ' (' . $category->count . ')' : '' )
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
