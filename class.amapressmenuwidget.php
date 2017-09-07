<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Amapress_Menu_Widget widget.
 */
class Amapress_Menu_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'Amapress_Menu_Widget', // Base ID
			__( 'Amapress - Menu Utilisateurs', 'amapress' ), // Name
			array( 'description' => __( 'Menu pour les amapiens loggués', 'amapress' ), ) // Args
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
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		echo '<ul class="navbar">';
//		echo '<li><a href="/mes-evenements">Mes évènements</a></li>';
//		echo '<li><a href="/trombinoscope">Les amapiens</a></li>';
//		echo '<li><a href="/mes-adhesions">Mes adhesions</a></li>';
//		echo '<li><a href="/mon-profile">Mon profile</a></li>';
//		echo '<li><a href="/adhesion">Souscrire</a></li>';
		//echo '<li><a href=""></a></li>';
		//echo '<li><a href=""></a></li>';
		//echo '<li><a href=""></a></li>';
		//echo '<li><a href=""></a></li>';
		//echo '<li><a href=""></a></li>';
		//echo '<li><a href=""></a></li>';
		echo '</ul>';

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
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Titre', 'amapress' );
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Titre :' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
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
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class Amapress_Menu_Widget
?>