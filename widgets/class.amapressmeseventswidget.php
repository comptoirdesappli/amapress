<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Amapress_Next_Events_Widget widget.
 */
class Amapress_Next_Events_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'Amapress_Next_Events_Widget', // Base ID
			__( 'Amapress - Prochains évènements', 'amapress' ), // Name
			array( 'description' => __( 'Prochains évènements pour l\'amapien loggué', 'amapress' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		if ( isset( $instance['logged_only'] ) && $instance['logged_only'] && ! amapress_is_user_logged_in() ) {
			return;
		}

		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		echo do_shortcode( '[next_events]' );

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title       = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Titre', 'amapress' );
		$logged_only = isset( $instance['logged_only'] ) ? $instance['logged_only'] : false;
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Titre :', 'amapress' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $logged_only, true ); ?>
                   id="<?php echo $this->get_field_id( 'logged_only' ); ?>"
                   name="<?php echo $this->get_field_name( 'logged_only' ); ?>"/>
            <label for="<?php echo $this->get_field_id( 'logged_only' ); ?>"><?php _e( 'Utilisateurs connectés seulement ?', 'amapress' ) ?></label>
        </p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = array();
		$instance['title']       = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['logged_only'] = ! empty( $new_instance['logged_only'] );

		return $instance;
	}

} // class Amapress_Next_Events_Widget
?>