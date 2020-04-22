<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_create_user_if_not_exists(
	$email_address,
	$first_name = null, $last_name = null,
	$address = null, $tel = null,
	$notify = 'both',
	$update_existing = true
) {
	$user = get_user_by( 'email', $email_address );
	if ( null == $user ) {
		// Generate the password and create the user
		$password = wp_generate_password( 12, false );
		$username = sanitize_user( $email_address );
		if ( ! empty( $last_name ) && ! empty( $first_name ) ) {
			$username = "$first_name.$last_name";
		} else {
			$username = preg_replace( '/@.+$/', '', $username );
		}

		$username = AmapressUsers::generate_unique_username( $username );
		$user_id  = wp_create_user( $username, $password, $email_address );

		if ( empty( $user_id ) ) {
			return null;
		}

		// Set the nickname
		wp_update_user(
			array(
				'ID'           => $user_id,
				'nickname'     => $email_address,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => $first_name . ' ' . $last_name,
			)
		);

		// Set the role
		$user = new WP_User( $user_id );
		$user->set_role( 'amapien' );

		// Email the user
//        wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );
		wp_new_user_notification( $user_id, null, $notify );

		if ( ! empty( $address ) ) {
			$address = preg_replace( '/(?:\s+-\s+|,\s*)?(\d\s*\d\s*\d\s*\d\s*\d|2\s*[AB]\s*\d\s*\d\s*\d)\s+([^,]+)(?:,\s*\1\s+\2)+/i', ', $1 $2', $address );
			preg_match( '/^(.+?),\s*(\d\s*\d\s*\d\s*\d\s*\d|2\s*[AB]\s*\d\s*\d\s*\d)\s+([^,]+)$/', $address, $matches );
			if ( $matches ) {
				update_user_meta( $user->ID, 'amapress_user_adresse', $matches[1] );
				update_user_meta( $user->ID, 'amapress_user_code_postal', $matches[2] );
				update_user_meta( $user->ID, 'amapress_user_ville', $matches[3] );
			} else {
				update_user_meta( $user->ID, 'amapress_user_adresse', $address );
				delete_user_meta( $user->ID, 'amapress_user_code_postal' );
				delete_user_meta( $user->ID, 'amapress_user_ville' );
			}
			AmapressUsers::resolveUserFullAdress( $user->ID, $address );
		}
		if ( ! empty( $tel ) ) {
			if ( is_array( $tel ) ) {
				if ( 2 == count( $tel ) ) {
					update_user_meta( $user->ID, 'amapress_user_telephone', $tel[0] );
					update_user_meta( $user->ID, 'amapress_user_telephone2', $tel[1] );
				} else {
					update_user_meta( $user->ID, 'amapress_user_telephone', implode( ' / ', $tel ) );
				}
			} else {
				update_user_meta( $user->ID, 'amapress_user_telephone', $tel );
			}
		}
	} else if ( $update_existing ) {
		if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			wp_update_user(
				array(
					'ID'           => $user->ID,
					'first_name'   => $first_name,
					'last_name'    => $last_name,
					'display_name' => $first_name . ' ' . $last_name,
				)
			);
		}
		if ( ! empty( $address ) ) {
			$address = preg_replace( '/(?:\s+-\s+|,\s*)?(\d\s*\d\s*\d\s*\d\s*\d|2\s*[AB]\s*\d\s*\d\s*\d)\s+([^,]+)(?:,\s*\1\s+\2)+/i', ', $1 $2', $address );
			preg_match( '/^(.+?),\s*(\d\s*\d\s*\d\s*\d\s*\d|2\s*[AB]\s*\d\s*\d\s*\d)\s+([^,]+)$/', $address, $matches );
			if ( $matches ) {
				update_user_meta( $user->ID, 'amapress_user_adresse', $matches[1] );
				update_user_meta( $user->ID, 'amapress_user_code_postal', $matches[2] );
				update_user_meta( $user->ID, 'amapress_user_ville', $matches[3] );
			} else {
				update_user_meta( $user->ID, 'amapress_user_adresse', $address );
				delete_user_meta( $user->ID, 'amapress_user_code_postal' );
				delete_user_meta( $user->ID, 'amapress_user_ville' );
			}
			AmapressUsers::resolveUserFullAdress( $user->ID, $address );
		}
		if ( ! empty( $tel ) ) {
			if ( is_array( $tel ) ) {
				if ( 2 == count( $tel ) ) {
					update_user_meta( $user->ID, 'amapress_user_telephone', $tel[0] );
					update_user_meta( $user->ID, 'amapress_user_telephone2', $tel[1] );
				} else {
					update_user_meta( $user->ID, 'amapress_user_telephone', implode( ' / ', $tel ) );
				}
			} else {
				update_user_meta( $user->ID, 'amapress_user_telephone', $tel );
			}
		}
	}


	if ( is_multisite() ) {
		if ( ! is_user_member_of_blog( $user->ID, get_current_blog_id() ) ) {
			add_user_to_blog( get_current_blog_id(), $user->ID, 'amapien' );
		}
	}

	return $user->ID;
}

add_action( 'admin_post_nopriv_inscription_intermittent', 'amapress_admin_action_nopriv_inscription_intermittent' );
function amapress_admin_action_nopriv_inscription_intermittent() {
	if ( ! Amapress::toBool( Amapress::getOption( 'intermit_self_inscr' ) ) && ! amapress_can_access_admin() ) {
		wp_die( 'Les inscriptions à l\'Espace intermittents sont gérées par le collectif' );
	}

	header( 'Content-Type: text/html; charset=UTF-8' );
	if ( ! isset( $_REQUEST['email'] ) ) {
		die( 'Pas d\'email spécifié' );
	}

	$key     = ! empty( $_POST['key'] ) ? $_POST['key'] : '';
	$post_id = ! empty( $_POST['post-id'] ) ? intval( $_POST['post-id'] ) : 0;
	$is_ok   = false;
	if ( ! empty( $key ) && ! empty( $post_id ) ) {
		$post = get_post( $post_id );
		if ( $post ) {
			if ( false !== strpos( $post->post_content, "key=$key" ) ) {
				$is_ok = true;
			}
		}
	}

	if ( ! $is_ok ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$user_firt_name = isset( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '';
	$user_last_name = isset( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '';
	$user_phone     = isset( $_REQUEST['phone'] ) ? $_REQUEST['phone'] : '';
	$user_address   = isset( $_REQUEST['address'] ) ? $_REQUEST['address'] : '';
	$user_email     = sanitize_email( $_REQUEST['email'] );

	$user = get_user_by( 'email', $user_email );
	if ( $user ) {
		echo '<p class="error">L\'adresse email ' . $user_email . ' est déjà utilisée.</p>';
		die();
	}

	$user_id = amapress_create_user_if_not_exists( $user_email, $user_firt_name, $user_last_name, $user_address, $user_phone );
	$user    = AmapressUser::getBy( $user_id );
	if ( false === $user->inscriptionIntermittence() ) {
		echo 'Vous êtes déjà inscrit sur la liste des intermittents';
	} else {
		echo 'Vous êtes inscrit sur la liste des intermittents';
	}
}

add_action( 'admin_post_inscription_intermittent', 'amapress_admin_action_inscription_intermittent' );
function amapress_admin_action_inscription_intermittent() {
	if ( ! Amapress::toBool( Amapress::getOption( 'intermit_self_inscr' ) ) && ! amapress_can_access_admin() ) {
		wp_die( 'Les inscriptions à l\'Espace intermittents sont gérées par le collectif' );
	}

	header( 'Content-Type: text/html; charset=UTF-8' );
	if ( ! isset( $_REQUEST['email'] ) ) {
		die( 'Pas d\'email spécifié' );
	}

	$user_firt_name = isset( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '';
	$user_last_name = isset( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '';
	$user_phone     = isset( $_REQUEST['phone'] ) ? $_REQUEST['phone'] : '';
	$user_address   = isset( $_REQUEST['address'] ) ? $_REQUEST['address'] : '';
	$user_email     = sanitize_email( $_REQUEST['email'] );
	$me             = AmapressUser::getBy( amapress_current_user_id() );
	$is_me          = in_array( $user_email, $me->getAllEmails() );

	if ( ! isset( $_REQUEST['confirm'] ) ) {
		echo 'Etes-vous sûr de vouloir ' . ( $is_me ? 'vous inscrire' : "inscrire {$user_email}" ) . ' en tant qu\'intermittent ? 
<br/>
<a href="' . add_query_arg( 'confirm', 'yes' ) . '">Confirmer l\'inscription</a>';
	} else {
		$return_to_sender = isset( $_REQUEST['return_sender'] );
		$user_id          = amapress_create_user_if_not_exists( $user_email, $user_firt_name, $user_last_name, $user_address, $user_phone );
		$user             = AmapressUser::getBy( $user_id );
		if ( false === $user->inscriptionIntermittence() ) {
			if ( $return_to_sender ) {
				wp_redirect_and_exit( add_query_arg( $_SERVER['HTTP_REFERER'], 'inscription_intermittent', 'already' ) );
			} else {
				echo ( $is_me ? 'Vous êtes déjà inscrit' : "{$user_email} est déjà inscrit" ) . ' sur la liste des intermittents';
			}
		} else {
			if ( $return_to_sender ) {
				wp_redirect_and_exit( add_query_arg( 'inscription_intermittent', 'ok', $_SERVER['HTTP_REFERER'] ) );
			} else {
				echo ( $is_me ? 'Vous êtes inscrit' : "{$user_email} a été inscrit" ) . ' sur la liste des intermittents';
			}
		}
	}
}


add_action( 'admin_post_nopriv_desinscription_intermittent', 'amapress_admin_action_nopriv_desinscription_intermittent' );
function amapress_admin_action_nopriv_desinscription_intermittent() {
	if ( ! empty( $_GET['desinter_nonce'] ) ) {
		$nonce = $_GET['desinter_nonce'];
		header( 'Content-Type: text/html; charset=UTF-8' );
		if ( get_transient( 'amps_desinscr_inter_' . $nonce ) != $nonce ) {
			wp_die( 'Ce lien de désinscription de la liste des intermittents est périmé.' );
		}
		if ( ! empty( $_REQUEST['email'] ) ) {
			amapress_admin_action_desinscription_intermittent();
		} else {
			echo '<form method="post" action="' . amapress_intermittence_desinscription_link() . '">
	<p>Veuillez entrer votre email pour vous désinscrire de la liste des intermittents.</p>
	<label for="email">Email :</label>
	<input type="text" name="email" />
	<input type="submit" value="Désinscrire" />
</form>';
		}
	} else {
		amapress_redirect_login();
	}
}

add_action( 'admin_post_desinscription_intermittent', 'amapress_admin_action_desinscription_intermittent' );
function amapress_admin_action_desinscription_intermittent() {
	if ( ! empty( $_REQUEST['desinter_nonce'] ) && empty( $_REQUEST['email'] ) ) {
		amapress_admin_action_nopriv_desinscription_intermittent();

		return;
	}
	header( 'Content-Type: text/html; charset=UTF-8' );

	if ( ! isset( $_REQUEST['email'] ) ) {
		die( 'Pas d\'email spécifié' );
	}

	$user_email = sanitize_email( $_REQUEST['email'] );
	if ( amapress_is_user_logged_in() ) {
		$me    = AmapressUser::getBy( amapress_current_user_id() );
		$is_me = in_array( $user_email, $me->getAllEmails() );
	} else {
		$is_me = true;
	}

	$user = get_user_by( 'email', $user_email );
	if ( ! $user ) {
		die( 'Utilisateur inconnu' );
	}
	$amapien = AmapressUser::getBy( $user );

	if ( ! $amapien->isIntermittent() ) {
		die( ( $is_me ? 'Vous n\'êtes pas inscrit' : "{$user_email} n'est pas inscrit" ) . ' sur la liste des intermittents' );
	}

	if ( ! isset( $_REQUEST['confirm'] ) ) {
		echo 'Etes-vous sûr de vouloir ' . ( $is_me ? 'vous désinscrire' : "désinscrire {$user_email}" ) . ' en tant qu\'intermittent ? 
<br/>
<a href="' . add_query_arg(
				[
					'confirm' => 'yes',
					'email'   => $user_email,
				] ) . '">Confirmer la désinscription</a>';
	} else {
		$amapien->desinscriptionIntermittence();

		echo ( $is_me ? 'Vous êtes désinscrit' : "{$user_email} a été désinscrit" ) . ' de la liste des intermittents';
	}
}

//add_action('amapress_do_query_action_intermittence_inscription','amapress_do_query_action_intermittence_inscription');
//function amapress_do_query_action_intermittence_inscription() {
//    if (!amapress_is_user_logged_in())
//        wp_die('Vous devez avoir un compte pour effectuer cette opération.');
//
//    $optionsPage = Amapress::resolve_post_id(Amapress::getOption('mes-infos-page'), 'page');
//    $base_url = trailingslashit(get_page_link($optionsPage));
//
//    $redir_url = $base_url . 'adhesions/intermittence';
//
//    $lieu = $_POST['lieu'];
//    $message = $_POST['message'];
//
//    if (empty($lieu)) {
//        wp_redirect_and_exit(add_query_arg('message', 'fill_fields', $redir_url));
//    }
//
//    $my_post = array(
//        'post_type' => 'amps_inter_adhe',
//        'post_content'  => '',
//        'post_status'   => 'publish',
//        'meta_input'    => array(
//            'amapress_adhesion_intermittence_date_debut' => amapress_time(),
//            'amapress_adhesion_intermittence_user' => amapress_current_user_id(),
//            'amapress_adhesion_intermittence_lieu' => $lieu,
//            'amapress_adhesion_intermittence_message' => $message,
//            'amapress_adhesion_intermittence_status' => 'to_confirm',
//        ),
//    );
//    $post_id = wp_insert_post($my_post);
//
//    $inter = AmapressAdhesion::getBy_intermittence($post_id);
//    amapress_mail_to_current_user(Amapress::getOption('intermittence-mail-subject'), Amapress::getOption('intermittence-mail-content'), null, $inter);
//
//    wp_redirect_and_exit(add_query_arg('message','intermittence_success', $base_url.'adhesions'));
//}

add_action( 'wp_ajax_reprendre_panier', function () {
	$panier_ids = explode( ',', $_REQUEST['panier'] );

	$result = 'ok';
	foreach ( $panier_ids as $panier_id ) {
		$panier = AmapressIntermittence_panier::getBy( intval( $panier_id ) );

		if ( 'ok' != ( $result = $panier->askReprise() ) ) {
			break;
		}
	}

	switch ( $result ) {
		case 'ok':
			echo '<p class="success">Demande de reprise de panier enregistrée</p>';
			break;
		case 'already':
			echo '<p class="error">Panier déjà échangé</p>';
			break;
	}

	die();
} );

add_action( 'wp_ajax_annuler_adherent', function () {
	$panier_ids = explode( ',', $_REQUEST['panier'] );

	$result = 'ok';
	foreach ( $panier_ids as $panier_id ) {
		$panier = AmapressIntermittence_panier::getBy( intval( $panier_id ) );

		if ( 'ok' != ( $result = $panier->cancelFromAdherent( null, isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' ) ) ) {
			break;
		}
	}

	switch ( $result ) {
		case 'ok':
			echo '<p class="success">Annulation de l\'échange de panier enregistrée</p>';
			break;
		case 'already':
			echo '<p class="error">Echange de panier déjà annulé</p>';
			break;
	}

	die();
} );
add_action( 'wp_ajax_annuler_repreneur', function () {
	$panier_ids = explode( ',', $_REQUEST['panier'] );

	$result = 'ok';
	foreach ( $panier_ids as $panier_id ) {
		$panier = AmapressIntermittence_panier::getBy( intval( $panier_id ) );

		if ( 'ok' != ( $result = $panier->cancelFromRepreneur( null, isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' ) ) ) {
			break;
		}
	}

	switch ( $result ) {
		case 'ok':
			echo '<p class="success">Annulation de l\'échange de panier enregistrée</p>';
			break;
		case 'already':
			echo '<p class="error">Echange de panier déjà annulé</p>';
			break;
	}

	die();
} );
add_action( 'wp_ajax_validate_reprise', function () {
	$panier_ids = explode( ',', $_REQUEST['panier'] );
	$user_id    = intval( $_REQUEST['user'] ? $_REQUEST['user'] : 0 );

	$result = 'ok';
	foreach ( $panier_ids as $panier_id ) {
		$panier = AmapressIntermittence_panier::getBy( intval( $panier_id ) );

		if ( 'ok' != ( $result = $panier->validateReprise( $user_id ) ) ) {
			break;
		}
	}

	switch ( $result ) {
		case 'ok':
			echo '<p class="success">Reprise du panier enregistrée</p>';
			break;
		default:
			echo '<p class="error">Opération impossible. ' . $result . '</p>';
			break;
	}

	die();
} );
add_action( 'wp_ajax_reject_reprise', function () {
	$panier_ids = explode( ',', $_REQUEST['panier'] );
	$user_id    = intval( $_REQUEST['user'] ? $_REQUEST['user'] : 0 );

	$result = 'ok';
	foreach ( $panier_ids as $panier_id ) {
		$panier = AmapressIntermittence_panier::getBy( intval( $panier_id ) );

		if ( 'ok' != ( $result = $panier->rejectReprise( $user_id ) ) ) {
			break;
		}
	}

	switch ( $result ) {
		case 'ok':
			echo '<p class="success">Demande de reprise rejettée</p>';
			break;
		default:
			echo '<p class="error">Opération impossible</p>';
			break;
	}

	die();
} );

