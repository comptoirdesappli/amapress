<?php

/*
Plugin Name: Cusotm Post Status
Description: Easily create custom post statuses within WordPress for one or more custom post types.
Author: Jen Wachter
Version: 0.1
*/


class CustomPostStatus {
	/**
	 * The machine name of the custom post status
	 * @var string
	 */
	protected $post_status;

	/**
	 * Array of post types that the custom post
	 * status will be applied to.
	 * @var array
	 */
	protected $post_types = array( "post" );

	/**
	 * Text used to display the post status
	 * when it can be applied to a post.
	 * For example, "Archive"
	 * @var string
	 */
	protected $action_label;

	/**
	 * Text used to display the post status
	 * when it has been applied to a post.
	 * For example, "Archived"
	 * @var string
	 */
	protected $applied_label;

	/**
	 * Arguments to pass to register_post_status()
	 * @var array
	 */
	protected $args = array();

	/**
	 * Constructor
	 *
	 * @param string $post_status Machine name of the post status
	 * @param array $post_types Array of post types to apply this status to. Default: post
	 * @param array $args Array of arguments. All arguments from:
	 *                              http://codex.wordpress.org/Function_Reference/register_post_status
	 *                            Plus:
	 *                              applied_label (string) Optional. Status label used when the user
	 *                                            has applied this post status (for example, "Archived").
	 *                                            Default: $args["label"] || $post_status
	 *
	 */
	public function __construct( $post_status, $post_types, $args ) {
		$this->post_status   = $post_status;
		$this->post_types    = $post_types;
		$this->action_label  = isset( $args["label"] ) ? $args["label"] : $post_status;
		$this->applied_label = isset( $args["applied_label"] ) ? $args["applied_label"] : $this->action_label;
		$this->args          = $args;

		// get rid of elements that don't belong in the args for register_post_status
		unset( $this->args["applied_label"] );

		// set a default label count
		if ( ! isset( $this->args["label_count"] ) ) {
			$this->args["label_count"] = _n_noop( "{$this->applied_label} <span class=\"count\">(%s)</span>", "{$this->applied_label} <span class=\"count\">(%s)</span>" );
		}

		// setup the actions
		add_action( "init", array( $this, "register_post_status" ) );
		add_action( "admin_footer-post.php", array( $this, "append_to_post_status_dropdown" ) );
		add_action( "admin_footer-edit.php", array( $this, "append_to_inline_status_dropdown" ) );
		add_filter( "display_post_states", array( $this, "update_post_status" ) );
	}

	/**
	 * Register the custom post status with WordPress
	 *
	 * @param  string $status Machine name of the post status
	 * @param  array $args Array of arguments
	 *
	 * @return null
	 */
	public function register_post_status() {
		register_post_status( $this->post_status, $this->args );
	}

	/**
	 * Append the custom post type to the post status
	 * dropdown on the edit pages of posts.
	 * @return null
	 */
	public function append_to_post_status_dropdown() {
		global $post;
		$selected       = '';
		$label          = '';
		$status_display = '';

		if ( in_array( $post->post_type, $this->post_types ) ) {

			if ( $post->post_status === $this->post_status ) {
				$selected       = " selected=\"selected\"";
				$label          = "<span id=\"post-status-display\"> {$this->applied_label}</span>";
				$status_display = '$("#post-status-display").text("' . $this->applied_label . '");';
			}

			echo "
		  <script type='text/javascript'>
		  jQuery(document).ready(function ($){
		       $('select#post_status').append('<option value=\"{$this->post_status}\"{$selected}>{$this->action_label}</option>');
		       $('.misc-pub-section label').append('{$label}');
		       $status_display
		  });
		  </script>";
		}
	}

	/**
	 * Append the custom post type to the post status
	 * dropdown in the quick edit area on the post
	 * listing page.
	 * @return null
	 */
	public function append_to_inline_status_dropdown() {
		global $post;

		// no posts
		if ( ! $post ) {
			return;
		}

		if ( in_array( $post->post_type, $this->post_types ) ) {

			echo "
			<script>
			jQuery(document).ready(function ($){
				$('.inline-edit-status select').append('<option value=\"{$this->post_status}\">{$this->action_label}</option>');
			});
			</script>
			";

		}
	}

	/**
	 * Update the text on edit.php to be more
	 * descriptive of the type of post (text
	 * that labels each post)
	 * @return null
	 */
	public function update_post_status( $states ) {
		global $post;

		$status = get_query_var( "post_status" );

		if ( $post && $status !== $this->post_status && $post->post_status === $this->post_status ) {
			return array( $this->applied_label );
		}

		return $states;
	}

	/**
	 * Constructor
	 *
	 * @param string $post_status Machine name of the post status
	 * @param array $post_types Array of post types to apply this status to. Default: post
	 * @param array $args Array of arguments. All arguments from:
	 *                              http://codex.wordpress.org/Function_Reference/register_post_status
	 *                            Plus:
	 *                              applied_label (string) Optional. Status label used when the user
	 *                                            has applied this post status (for example, "Archived").
	 *                                            Default: $args["label"] || $post_status
	 *
	 */
	public static function register( $post_status, $post_types, $args ) {
		new CustomPostStatus( $post_status, $post_types, $args );
	}
}