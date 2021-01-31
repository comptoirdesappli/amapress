<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_current_user_id() {
	global $amapress_not_logged;
	if ( $amapress_not_logged ) {
		return 0;
	}

	return get_current_user_id();
}

function amapress_consider_logged( $logged ) {
	global $amapress_not_logged;
	$amapress_not_logged = ! $logged;
}

function amapress_is_user_logged_in() {
	global $amapress_not_logged;
	if ( $amapress_not_logged ) {
		return false;
	}

	return is_user_logged_in() || Amapress::isDoingCron();
}

function amapress_current_user_can( $capability ) {
	if ( is_multisite() && is_super_admin() ) {
		if ( in_array( $capability, amapress_can_access_admin_roles() ) ) {
			return 'administrator' === $capability;
		}
	}

	return amapress_is_user_logged_in() && user_can( amapress_current_user_id(), $capability );
}

function amapress_time() {
	return current_time( 'timestamp' );
}