<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Amapress_Categories_Widget widget.
 */
class Amapress_Categories_Widget extends WP_Widget {

	/**
	 * Sets up a new Amapress Categories widget instance.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'description'                 => __( 'Liste ou liste déroulante des catégories Amapress (produits, recettes, évènements)' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'Amapress_Categories_Widget', __( 'Amapress - Catégories', 'amapress' ), $widget_ops );
	}

	/**
	 * Outputs the content for the current Categories widget instance.
	 *
	 * @param array $args Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Categories widget instance.
	 *
	 * @since 2.8.0
	 * @since 4.2.0 Creates a unique HTML ID for the `<select>` element
	 *              if more than one instance is displayed on the page.
	 *
	 */
	public function widget( $args, $instance ) {
		static $first_dropdown = true;

		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Categories' );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$count        = ! empty( $instance['count'] ) ? '1' : '0';
		$hierarchical = ! empty( $instance['hierarchical'] ) ? '1' : '0';
		$dropdown     = ! empty( $instance['dropdown'] ) ? '1' : '0';
		$taxonomy     = ! empty( $instance['taxonomy'] ) ? $instance['taxonomy'] : AmapressProduit::CATEGORY;

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$cat_args = array(
			'orderby'      => 'name',
			'show_count'   => $count,
			'hierarchical' => $hierarchical,
			'taxonomy'     => $taxonomy,
		);

		if ( $dropdown ) {
			printf( '<form action="%s" method="get">', esc_url( home_url() ) );
			$dropdown_id    = ( $first_dropdown ) ? 'cat' : "{$this->id_base}-dropdown-{$this->number}";
			$first_dropdown = false;

			echo '<label class="screen-reader-text" for="' . esc_attr( $dropdown_id ) . '">' . $title . '</label>';

			$cat_args['show_option_none'] = __( 'Select Category' );
			$cat_args['id']               = $dropdown_id;

			$post_type = 'post';
			switch ( $cat_args['taxonomy'] ) {
				case AmapressProduit::CATEGORY:
					$cat_args['name'] = 'amapress_produit_tag';
					$post_type        = AmapressProduit::INTERNAL_POST_TYPE;
					break;
				case AmapressRecette::CATEGORY:
					$cat_args['name'] = 'amapress_recette_tag';
					$post_type        = AmapressRecette::INTERNAL_POST_TYPE;
					break;
				case AmapressAmap_event::CATEGORY:
					$cat_args['name'] = 'amapress_event_tag';
					$post_type        = AmapressAmap_event::INTERNAL_POST_TYPE;
					break;
			}

			echo '<input type="hidden" name="post_type" value="' . esc_attr( $post_type ) . '"/>';

			/**
			 * Filters the arguments for the Amapress Categories widget drop-down.
			 *
			 * @param array $cat_args An array of Amapress Categories widget drop-down arguments.
			 * @param array $instance Array of settings for the current widget.
			 *
			 * @see wp_dropdown_categories()
			 *
			 */
			wp_dropdown_categories( apply_filters( 'amapress_widget_categories_dropdown_args', $cat_args, $instance ) );

			echo '</form>';

			$type_attr = current_theme_supports( 'html5', 'script' ) ? '' : ' type="text/javascript"';
			?>

            <script<?php echo $type_attr; ?>>
                /* <![CDATA[ */
                (function () {
                    var dropdown = document.getElementById("<?php echo esc_js( $dropdown_id ); ?>");

                    function onCatChange() {
                        if (dropdown.options[dropdown.selectedIndex].value > 0) {
                            dropdown.parentNode.submit();
                        }
                    }

                    dropdown.onchange = onCatChange;
                })();
                /* ]]> */
            </script>

			<?php
		} else {
			?>
            <ul>
				<?php
				$cat_args['title_li'] = '';

				/**
				 * Filters the arguments for the Amapress Categories widget.
				 *
				 * @param array $cat_args An array of Categories widget options.
				 * @param array $instance Array of settings for the current widget.
				 */
				wp_list_categories( apply_filters( 'amapress_widget_categories_args', $cat_args, $instance ) );
				?>
            </ul>
			<?php
		}

		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current Categories widget instance.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Updated settings to save.
	 * @since 2.8.0
	 *
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                 = $old_instance;
		$instance['title']        = sanitize_text_field( $new_instance['title'] );
		$instance['count']        = ! empty( $new_instance['count'] ) ? 1 : 0;
		$instance['hierarchical'] = ! empty( $new_instance['hierarchical'] ) ? 1 : 0;
		$instance['dropdown']     = ! empty( $new_instance['dropdown'] ) ? 1 : 0;
		$instance['taxonomy']     = sanitize_text_field( $new_instance['taxonomy'] );

		return $instance;
	}

	/**
	 * Outputs the settings form for the Categories widget.
	 *
	 * @param array $instance Current settings.
	 *
	 * @since 2.8.0
	 *
	 */
	public function form( $instance ) {
		// Defaults.
		$instance     = wp_parse_args( (array) $instance, array( 'title' => '', 'taxonomy' => 'category' ) );
		$count        = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		$hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
		$dropdown     = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;
		$taxonomy     = $instance['taxonomy'];
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $instance['title'] ); ?>"/>
        </p>

        <p>
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ); ?>"
                   name="<?php echo $this->get_field_name( 'dropdown' ); ?>"<?php checked( $dropdown ); ?> />
            <label for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Display as dropdown' ); ?></label>
            <br/>

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>"
                   name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
            <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts' ); ?></label>
            <br/>

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hierarchical' ); ?>"
                   name="<?php echo $this->get_field_name( 'hierarchical' ); ?>"<?php checked( $hierarchical ); ?> />
            <label for="<?php echo $this->get_field_id( 'hierarchical' ); ?>"><?php _e( 'Show hierarchy' ); ?></label>
            <br/>

            <label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Type de catégorie', 'amapress' ); ?></label>
            <select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>"
                    name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" class="widefat" style="width: 100%">
                <option <?php selected( $taxonomy, AmapressProduit::CATEGORY ) ?>
                        value="<?php echo AmapressProduit::CATEGORY ?>"><?php _e( 'Catégories de produits', 'amapress' ); ?></option>
                <option <?php selected( $taxonomy, AmapressRecette::CATEGORY ) ?>
                        value="<?php echo AmapressRecette::CATEGORY ?>"><?php _e( 'Catégories de recettes', 'amapress' ); ?></option>
                <option <?php selected( $taxonomy, AmapressAmap_event::CATEGORY ) ?>
                        value="<?php echo AmapressAmap_event::CATEGORY ?>"><?php _e( 'Catégories d\'évènements', 'amapress' ); ?></option>
            </select>
        </p>
		<?php
	}

}