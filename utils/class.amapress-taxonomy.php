<?php


class AmapressUserTaxonomy {

	/**
	 * AmapressUserTaxonomy constructor.
	 */
	public function __construct() {
		add_action( 'registered_taxonomy', array( $this, 'registered_taxonomy' ), 10, 3 );
	}

	/**
	 * Correct the column names for user taxonomies
	 * Need to replace "Posts" with "Users"
	 */
	public function set_user_column( $columns ) {
		unset( $columns['posts'] );
		$columns['users'] = __( 'Users' );

		return $columns;
	}

	/**
	 * Set values for custom columns in user taxonomies
	 */
	public function set_user_column_values( $display, $column, $term_id ) {
		if ( 'users' === $column ) {
			$taxonomy = $_GET['taxonomy'];
			$term     = get_term( $term_id, $taxonomy );
			$url      = admin_url( "users.php?$taxonomy=$term->slug" );
			echo "<a href='$url'>{$term->count}</a>";
		}
	}

	/**
	 * This is our way into manipulating registered taxonomies
	 * It's fired at the end of the register_taxonomy function
	 *
	 * @param String $taxonomy - The name of the taxonomy being registered
	 * @param String $object - The object type the taxonomy is for; We only care if this is "user"
	 * @param Array $args - The user supplied + default arguments for registering the taxonomy
	 */
	function registered_taxonomy( $taxonomy, $object, $args ) {
		global $wp_taxonomies;

		// Only modify user taxonomies, everything else can stay as is
		if ( $object != 'user' ) {
			return;
		}

		// We're given an array, but expected to work with an object later on
		$args = (object) $args;

		// Register any hooks/filters that rely on knowing the taxonomy now
		add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'set_user_column' ) );
		add_action( "manage_{$taxonomy}_custom_column", array( $this, 'set_user_column_values' ), 10, 3 );

		// Set the callback to update the count if not already set
		if ( empty( $args->update_count_callback ) ) {
			$args->update_count_callback = array( $this, 'update_count' );
		}

		// We're finished, make sure we save out changes
		$wp_taxonomies[ $taxonomy ] = $args;
	}

	/**
	 * We need to manually update the number of users for a taxonomy term
	 *
	 * @see    _update_post_term_count()
	 *
	 * @param Array $terms - List of Term taxonomy IDs
	 * @param Object $taxonomy - Current taxonomy object of terms
	 */
	public function update_count( $terms, $taxonomy ) {
		global $wpdb;

		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->users WHERE $wpdb->term_relationships.object_id = $wpdb->users.ID and $wpdb->term_relationships.term_taxonomy_id = %d", $term ) );

			do_action( 'edit_term_taxonomy', $term, $taxonomy );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}
	}
}