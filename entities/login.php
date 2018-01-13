<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_can_access_admin_roles() {
	return array(
		'administrator',
		'responsable_amap',
		'referent',
		'tresorier',
		'coordinateur_amap',
	);
}

function amapress_can_access_admin() {
	$user = wp_get_current_user();
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( amapress_can_access_admin_roles() as $r ) {
			if ( in_array( $r, $user->roles ) ) {
				return true;
			}
		}
	}
	if ( $user->has_cap( 'access_admin' ) ) {
		return true;
	}

	return false;
}

//add_filter('login_redirect', 'amapress_redirect_on_login', 10, 3);
///**
// * Redirect user after successful login.
// *
// * @param string $redirect_to URL to redirect to.
// * @param string $request URL the user is coming from.
// * @param object $user Logged user's data.
// * @return string
// */
//function amapress_redirect_on_login($redirect_to, $request, $user)
//{
//    if (is_admin()) {
//        if (amapress_can_access_admin()) {
//            return $redirect_to;
//        } else {
//            return home_url();
//        }
//    }
//    return $redirect_to;
//}


add_action( 'init', 'amapress_check_access' );
function amapress_check_access() {
	global $pagenow;

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( 'admin-post.php' == $pagenow ) {
		return;
	}

	if ( is_user_logged_in() && is_admin() && ! amapress_can_access_admin() ) {
		if ( 'profile.php' == $pagenow ) {
			amapress_redirect_info();
		} else {
			amapress_redirect_home();
		}
	}
}

add_action( 'login_head', 'amapress_login_head' );
function amapress_login_head() {
	if ( isset( $_GET['action'] ) && $_GET['action'] === 'register' ) {
		wp_enqueue_style( 'register_css', AMAPRESS__PLUGIN_URL . '/admin/css/register-form.css' );
	} else {
		wp_enqueue_style( 'login_css', AMAPRESS__PLUGIN_URL . '/admin/css/custom-login.css' );
	}
}

add_action( 'login_headerurl', 'amapress_login_headerurl' );
/**
 * Change the link so the the replaced WP logo links to the site
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/login_headerurl
 */
function amapress_login_headerurl( $url ) {
	return get_bloginfo( 'url' );
}

add_action( 'login_message', 'amapress_login_message' );
function amapress_login_message( $message ) {
	$message .= Amapress::getOption( 'below_login_message' );

	return $message;
}

function wpmu_no_username_error( $result ) {
	$error_name = $result['errors']->get_error_messages( 'user_name' );
	if ( empty ( $error_name )
	     || ( false === ( $key = array_search( __( 'Only lowercase letters (a-z) and numbers are allowed.' ), $error_name ) )
	          && false === ( $key = array_search( __( 'Usernames can only contain lowercase letters (a-z) and numbers.' ), $error_name ) ) )
	) {
		return $result;
	}

//  only remove the error we are disabling, leaving all others
	unset ( $result['errors']->errors['user_name'][ $key ] );
	/**
	 *  re-sequence errors in case a non sequential array matters
	 *  e.g. if a core change put this message in element 0 then get_error_message() would not behave as expected)
	 */
	$result['errors']->errors['user_name'] = array_values( $result['errors']->errors['user_name'] );

	if ( count( $result['errors']->errors['user_name'] ) == 0 ) {
		unset( $result['errors']->errors['user_name'] );
	}

	return $result;
}

add_filter( 'wpmu_validate_user_signup', 'wpmu_no_username_error' );