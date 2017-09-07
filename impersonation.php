<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_current_user_id() {
	if ( isset( $_SESSION['amapress_current_user'] ) ) {
		if ( $_SESSION['amapress_current_user'] == '_everyone_' ) {
			return 0;
		} else {
			return intval( $_SESSION['amapress_current_user'] );
		}
	}

	return get_current_user_id();
}

function amapress_is_user_logged_in() {
	if ( isset( $_SESSION['amapress_current_user'] ) ) {
		if ( $_SESSION['amapress_current_user'] == '_everyone_' ) {
			return false;
		} else {
			return true;
		}
	}

	return is_user_logged_in();
}

function amapress_current_user_can( $capability ) {
	return amapress_is_user_logged_in() && user_can( amapress_current_user_id(), $capability );
}

function amapress_time() {
	if ( isset( $_SESSION['amapress_current_date'] ) && $_SESSION['amapress_current_date'] > 0 ) {
		return $_SESSION['amapress_current_date'];
	} else {
		return current_time( 'timestamp' );
	}
}

function amapress_get_user_display_name( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = amapress_current_user_id();
	}
	$user = AmapressUser::getBy( $user_id );

	return $user->getDisplayName();
}


add_action( 'init', 'amapress_session_start', 1 );
function amapress_session_start() {
	if ( ! Amapress::getOption( 'enable_timesetter' ) ) {
		return;
	}
	if ( ! session_id() ) {
		@session_start();
	}
}

add_action( 'wp_logout', 'amapress_session_logout' );
function amapress_session_logout() {
	if ( isset( $_SESSION['amapress_current_user'] ) ) {
		unset( $_SESSION['amapress_current_user'] );
	}
	if ( isset( $_SESSION['amapress_current_date'] ) ) {
		unset( $_SESSION['amapress_current_date'] );
	}
}


add_action( 'wp_footer', 'amapress_impersonation_control' ); // Write our JS below here
add_action( 'admin_footer', 'amapress_impersonation_control' ); // Write our JS below here
function amapress_impersonation_control() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( ! amapress_can_access_admin() ) {
		return;
	}
	if ( ! Amapress::getOption( 'enable_timesetter' ) || ! is_user_logged_in() ) {
		return;
	}

	$current_user_id = amapress_current_user_id();
	if ( isset( $_SESSION['amapress_current_user'] ) && is_int( $_SESSION['amapress_current_user'] ) ) {
		$current_user_id = $_SESSION['amapress_current_user'];
	}
	$current_date = current_time( 'timestamp' );
	if ( isset( $_SESSION['amapress_current_date'] ) ) {
		$current_date = $_SESSION['amapress_current_date'];
	}

	?>
    <div id="amapress_impersonation_control">
        <input type="radio" id="amapress_impersonation_who" name="amapress_impersonation_who"
               value="_everyone_" <?php checked( isset( $_SESSION['amapress_current_user'] ) && $_SESSION['amapress_current_user'] == '_everyone_' ) ?>>Everyone</input>
        <input type="radio" id="amapress_impersonation_who" name="amapress_impersonation_who"
               value="_me_" <?php checked( ! isset( $_SESSION['amapress_current_user'] ) ) ?>>Me</input>
        <input type="radio" id="amapress_impersonation_who" name="amapress_impersonation_who"
               value="other" <?php checked( isset( $_SESSION['amapress_current_user'] ) && is_int( $_SESSION['amapress_current_user'] ) ) ?>>
        <select id="amapress_impersonation_user" <?php disabled( ! is_int( $_SESSION['amapress_current_user'] ) ) ?>>
			<?php
			foreach ( get_users() as $user ) {
				echo '<option value="' . $user->ID . '" ' . selected( $current_user_id, $user->ID, false ) . '>' . amapress_get_user_display_name( $user->ID ) . '</option>';
			}
			?>
        </select>
        </input>
        <input type="checkbox"
               id="amapress_impersonation_set_date" <?php checked( isset( $_SESSION['amapress_current_date'] ) ) ?>>
        <input type="text"
               id="amapress_impersonation_date" <?php disabled( ! isset( $_SESSION['amapress_current_date'] ) ) ?>
               value="<?php echo date( 'd/m/Y H:i', $current_date ) ?>"/>
        </input>
        <button id="amapress_impersonation_control_set">Set</button>
    </div>
    <script type="text/javascript">
        jQuery('#amapress_impersonation_control_set').click(amapress_send_impersonation);
        jQuery('input[name=amapress_impersonation_who]').change(function () {
            var who = jQuery('#amapress_impersonation_who:checked').val();
            jQuery('#amapress_impersonation_user').prop('disabled', who == '_everyone_' || who == '_me_');
        });
        jQuery('#amapress_impersonation_set_date').change(function () {
            jQuery('#amapress_impersonation_date').prop('disabled', !jQuery(this).is(':checked'));
        });

        function amapress_send_impersonation() {
            var user;
            var who = jQuery('#amapress_impersonation_who:checked').val();
            if (who == '_everyone_')
                user = '_everyone_';
            else if (who == '_me_')
                user = '_me_';
            else
                user = jQuery('#amapress_impersonation_user').val();

            var date = null;
            if (jQuery('#amapress_impersonation_set_date').is(':checked'))
                date = jQuery('#amapress_impersonation_date').val();

            var data = {
                'action': 'amapress_impersonation',
                'user': user,
                'date': date
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post('<?php echo admin_url( 'admin-ajax.php' ) ?>', data, function (response) {
                location.reload();
            });
        };
    </script>
    <style type="text/css">
        #amapress_impersonation_control {
            position: absolute;
            right: 0;
            top: 32px;
            background-color: #1b6d85;
            border: 1px solid #23282d;
            z-index: 99999;
            padding: 5px;
            color: #eee;
            font-family: "Open Sans", sans-serif;
            vertical-align: baseline;
            font-size: 1em;
            line-height: 1;
        }

        #amapress_impersonation_user, #amapress_impersonation_date {
            color: #080808;
            opacity: 1;
            width: auto;
            padding: 0;
        }

        #amapress_impersonation_user[disabled], #amapress_impersonation_date[disabled] {
            color: #555555;
            background-color: #e0e0e0;
        }
    </style>
	<?php
}

add_action( 'wp_ajax_amapress_impersonation', 'amapress_impersonation_callback' );
function amapress_impersonation_callback() {
	$user = $_POST['user'];
	if ( $user == '_me_' ) {
		unset( $_SESSION['amapress_current_user'] );
	} else if ( $user == '_everyone_' ) {
		$_SESSION['amapress_current_user'] = '_everyone_';
	} else {
		$_SESSION['amapress_current_user'] = intval( $user );
	}

	$date = $_POST['date'];
	if ( ! $date ) {
		unset( $_SESSION['amapress_current_date'] );
	} else {
		$_SESSION['amapress_current_date'] = DateTime::createFromFormat( 'd/m/Y H:i', $date )->getTimestamp();
	}

	//var_dump($_SESSION);
	wp_die(); // this is required to terminate immediately and return a proper response
}