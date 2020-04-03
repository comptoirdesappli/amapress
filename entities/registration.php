<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'register_form', 'amapress_register_form_edit' );
/**
 * Adding the HTML to the existing registration form
 */
function amapress_register_form_edit() {

	$amapress_user_telephone = ( ! empty( $_POST['amapress_user_telephone'] ) ) ? trim( $_POST['amapress_user_telephone'] ) : ''; ?>
    <p>
        <label for="amapress_user_telephone">
			<?php _e( 'Téléphone', 'amapress' ) ?><br/>
            <input type="text" name="amapress_user_telephone" id="amapress_user_telephone" class="input"
                   value="<?php echo esc_attr( wp_unslash( $amapress_user_telephone ) ); ?>" size="10"/>
        </label>
    </p>

	<?php
	$amapress_user_adresse = ( ! empty( $_POST['amapress_user_adresse'] ) ) ? trim( $_POST['amapress_user_adresse'] ) : ''; ?>
    <p>
        <label for="amapress_user_adresse">
			<?php _e( 'Adresse', 'amapress' ) ?><br/>
            <textarea name="amapress_user_adresse" id="amapress_user_adresse" rows="4"
                      cols="40"><?php echo $amapress_user_adresse; ?></textarea>
        </label>
    </p>

	<?php
	$amapress_user_code_postal = ( ! empty( $_POST['amapress_user_code_postal'] ) ) ? trim( $_POST['amapress_user_code_postal'] ) : ''; ?>
    <p>
        <label for="amapress_user_code_postal">
			<?php _e( 'Code Postal', 'amapress' ) ?><br/>
            <input type="text" name="amapress_user_code_postal" id="amapress_user_code_postal"
                   value="<?php echo esc_attr( wp_unslash( $amapress_user_code_postal ) ); ?>" size="10"/>
        </label>
    </p>

	<?php
	$amapress_user_ville = ( ! empty( $_POST['amapress_user_ville'] ) ) ? trim( $_POST['amapress_user_ville'] ) : ''; ?>
    <p>
        <label for="amapress_user_ville">
			<?php _e( 'Ville', 'amapress' ) ?><br/>
            <input type="text" name="amapress_user_ville" id="amapress_user_ville"
                   value="<?php echo esc_attr( wp_unslash( $amapress_user_ville ) ); ?>"/>
        </label>
    </p>

	<?php
	$amapress_user_moyen = ( ! empty( $_POST['amapress_user_moyen'] ) ) ? intval( trim( $_POST['amapress_user_moyen'] ) ) : 0; ?>
    <p>
        <label for="amapress_user_moyen">
			<?php _e( 'Moyen de communication favori', 'amapress' ) ?><br/>
            <select name="amapress_user_moyen" id="amapress_user_moyen">
                <option value="0" <?php selected( $amapress_user_moyen, 0 ) ?>>Mail</option>
                <option value="1" <?php selected( $amapress_user_moyen, 1 ) ?>>Téléphone</option>
            </select>
        </label>
    </p>

	<?php
	/*		<?php $terms = ( ! empty( $_POST['terms'] ) ) ? $_POST['terms'] : ''; ?>
	<p>
	<label for="terms">
		<input type="checkbox" name="terms" id="terms" class="input" value="agreed" <?php checked( $_POST['terms'], 'agreed', true ); ?> />
		<?php _e( 'J\'ai lu les termes du contrat', 'amapress' ) ?>
	</label>
	</p>
	<?php*/
}

add_filter( 'registration_errors', 'amapress_validate_registration', 10, 3 );
/**
 * Validate our new feilds
 *
 * @param error object
 * @param user login
 * @param user email
 */
function amapress_validate_registration( $errors, $sanitized_user_login, $user_email ) {

	if ( empty( $_POST['amapress_user_telephone'] ) || ! empty( $_POST['amapress_user_telephone'] ) && trim( $_POST['amapress_user_telephone'] ) == '' ) {

		$errors->add( 'amapress_user_telephone_error', __( '<strong>ERREUR</strong>: Merci de remplir votre numéro de téléphone.', 'amapress' ) );
	}
	if ( empty( $_POST['amapress_user_adresse'] ) || ! empty( $_POST['amapress_user_adresse'] ) && trim( $_POST['amapress_user_adresse'] ) == '' ) {

		$errors->add( 'amapress_user_adresse_error', __( '<strong>ERREUR</strong>: Merci de remplir votre adresse.', 'amapress' ) );
	}
	if ( empty( $_POST['amapress_user_code_postal'] ) || ! empty( $_POST['amapress_user_code_postal'] ) && trim( $_POST['amapress_user_code_postal'] ) == '' ) {

		$errors->add( 'amapress_user_code_postal_error', __( '<strong>ERREUR</strong>: Merci de remplir votre code postal.', 'amapress' ) );
	}
	if ( empty( $_POST['amapress_user_ville'] ) || ! empty( $_POST['amapress_user_ville'] ) && trim( $_POST['amapress_user_ville'] ) == '' ) {

		$errors->add( 'amapress_user_ville_error', __( '<strong>ERREUR</strong>: Merci de remplir votre ville.', 'amapress' ) );
	}
	if ( ! preg_match( '/^\d{5}$/', $_POST['amapress_user_code_postal'] ) ) {

		$errors->add( 'amapress_user_code_postal_error', __( '<strong>ERREUR</strong>: Ce n\'est pas un code postal valide.', 'amapress' ) );
	}
	if ( ! preg_match( '/^\d{10}$/', $_POST['amapress_user_telephone'] ) ) {

		$errors->add( 'amapress_user_telephone_error', __( '<strong>ERREUR</strong>: Ce n\'est pas un numéro de téléphone valide.', 'amapress' ) );
	}

	/*if ( empty( $_POST['twitter_name'] ) || !empty( $_POST['twitter_name'] ) && trim( $_POST['twitter_name'] ) == '' ) {

		$errors->add( 'twitter_name_error', amapress__( '<strong>ERROR</strong>: Please enter your Twitter name.', 'sage' ) );
	}

	if ( preg_match('/[^a-z_\-0-9]/i', $_POST['twitter_name']) ) {

		$errors->add( 'twitter_name_error', amapress__( '<strong>ERROR</strong>: Please use letters, numbers, spaces and underscores only.', 'sage' ) );
	}

	if ( empty( $_POST['terms'] ) ) {

		$errors->add( 'terms_error', amapress__( '<strong>ERREUR</strong>: You must agree to the terms.', 'sage' ) );
	}*/

	return $errors;
}

add_action( 'user_register', 'amapress_process_registration' );
/**
 * Process the additional fields
 *
 * @param user_id
 */
function amapress_process_registration( $user_id ) {

	if ( ! empty( $_POST['amapress_user_code_postal'] ) ) {

		update_user_meta( $user_id, 'amapress_user_code_postal', trim( $_POST['amapress_user_code_postal'] ) );
	}
	if ( ! empty( $_POST['amapress_user_ville'] ) ) {

		update_user_meta( $user_id, 'amapress_user_ville', trim( $_POST['amapress_user_ville'] ) );
	}
	if ( ! empty( $_POST['amapress_user_adresse'] ) ) {

		update_user_meta( $user_id, 'amapress_user_adresse', trim( $_POST['amapress_user_adresse'] ) );
//			AmapressUsers::
//			AmapressUsers::re($user_id);
	}
	if ( ! empty( $_POST['amapress_user_telephone'] ) ) {

		update_user_meta( $user_id, 'amapress_user_telephone', trim( $_POST['amapress_user_telephone'] ) );
	}
	if ( ! empty( $_POST['amapress_user_moyen'] ) ) {

		update_user_meta( $user_id, 'amapress_user_moyen', intval( trim( $_POST['amapress_user_moyen'] ) ) );
	}

	if ( ! empty( $_POST['terms'] ) ) {

		update_user_meta( $user_id, 'terms', trim( $_POST['terms'] ) );
	}
}

if ( ! function_exists( 'wp_new_user_notification' ) ) {
	function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
		if ( $deprecated !== null ) {
			_deprecated_argument( __FUNCTION__, '4.3.1' );
		}

		$user = AmapressUser::getBy( $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		if ( 'user' !== $notify && Amapress::getOption( 'notify_admin_new_user' ) ) {
			$message = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
			$message .= sprintf( __( 'Username: %s' ), $user->getUser()->user_login ) . "\r\n\r\n";
			$message .= sprintf( __( 'Email: %s' ), $user->getUser()->user_email ) . "\r\n";

			@wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] New User Registration' ), $blogname ), $message );
		}

		if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
			return;
		}

		$subject = amapress_replace_mail_placeholders( Amapress::getOption( 'welcome_mail_subject' ), $user );
		$message = amapress_replace_mail_placeholders( Amapress::getOption( 'welcome_mail' ), $user );

		amapress_wp_mail( $user->getUser()->user_email, $subject, $message );
	}
}

if ( ! function_exists( 'wp_password_change_notification' ) ) {
	function wp_password_change_notification( $user ) {
		/** @var WP_User $user */
		// Send a copy of password change notification to the admin,
		// but check to see if it's the admin whose password we're changing, and skip this.
		if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
			$can_access_admin = false;
			if ( isset( $user->roles ) && is_array( $user->roles ) ) {
				foreach ( amapress_can_access_admin_roles() as $r ) {
					if ( in_array( $r, $user->roles ) ) {
						$can_access_admin = true;
					}
				}
			}
			if ( ! Amapress::getOption( $can_access_admin ? 'notify_admin_pwd_resp' : 'notify_admin_pwd_amapien' ) ) {
				return;
			}
			/* translators: %s: User name. */
			$message = sprintf( __( 'Password changed for user: %s' ), $user->user_login ) . "\r\n";
			// The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
			// We want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

			$wp_password_change_notification_email = array(
				'to'      => get_option( 'admin_email' ),
				/* translators: Password change notification email subject. %s: Site title. */
				'subject' => __( '[%s] Password Changed' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the password change notification email sent to the site admin.
			 *
			 * @param array $wp_password_change_notification_email {
			 *     Used to build wp_mail().
			 *
			 * @type string $to The intended recipient - site admin email address.
			 * @type string $subject The subject of the email.
			 * @type string $message The body of the email.
			 * @type string $headers The headers of the email.
			 * }
			 *
			 * @param WP_User $user User object for user whose password was changed.
			 * @param string $blogname The site title.
			 *
			 * @since 4.9.0
			 *
			 */
			$wp_password_change_notification_email = apply_filters( 'wp_password_change_notification_email', $wp_password_change_notification_email, $user, $blogname );

			wp_mail(
				$wp_password_change_notification_email['to'],
				wp_specialchars_decode( sprintf( $wp_password_change_notification_email['subject'], $blogname ) ),
				$wp_password_change_notification_email['message'],
				$wp_password_change_notification_email['headers']
			);
		}
	}
}

add_filter( 'retrieve_password_title', function ( $title ) {
	if ( empty( $_POST['user_login'] ) ) {
		return $title;
	}

	if ( strpos( $_POST['user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( wp_unslash( $_POST['user_login'] ) ) );
	} else {
		$login     = trim( $_POST['user_login'] );
		$user_data = get_user_by( 'login', $login );
	}
	$user = AmapressUser::getBy( $user_data->ID );

	return amapress_replace_mail_placeholders( Amapress::getOption( 'password_lost_mail_subject' ), $user );
} );

add_filter( 'retrieve_password_message', function ( $message, $key ) {
	if ( empty( $_POST['user_login'] ) ) {
		return $message;
	}

	if ( strpos( $_POST['user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( wp_unslash( $_POST['user_login'] ) ) );
	} else {
		$login     = trim( $_POST['user_login'] );
		$user_data = get_user_by( 'login', $login );
	}
	$user = AmapressUser::getBy( $user_data->ID );

	add_filter( 'wp_mail_content_type', function ( $t ) {
		if ( false !== stripos( $t, 'multipart' ) ) {
			return $t;
		}

		return 'text/html';
	} );
	add_filter( 'amapress_mail_queue_bypass', function ( $t ) {
		return true;
	} );
	add_filter( 'amapress_mail_queue_retries', function ( $t ) {
		return 3;
	} );

	return amapress_replace_mail_placeholders( Amapress::getOption( 'password_lost_mail' ), $user );
}, 10, 2 );