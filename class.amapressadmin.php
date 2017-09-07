<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressLoginAdmin {
	public static $initiated = false;


	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	public static function init_hooks() {
		self::$initiated = true;
		// Hook is used to save custom fields that have been added to the WordPress profile page (if current user)
		//add_action( 'personal_options_update', array('AmapressLoginAdmin','update_extra_profile_fields') );
		// Hook is used to save custom fields that have been added to the WordPress profile page (if not current user)
		// Hooks near the bottom of profile page (if current user)
		//add_action( 'show_user_profile', array('AmapressLoginAdmin','custom_user_profile_fields') );
		// Hooks near the bottom of the profile page (if not current user)
		//add_action( 'edit_user_profile', array('AmapressLoginAdmin','custom_user_profile_fields') );
		//add_action( 'edit_user_profile_update', array('AmapressLoginAdmin','update_extra_profile_fields') );
	}


}