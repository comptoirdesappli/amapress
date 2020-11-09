<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'file_is_displayable_image' ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	require_once ABSPATH . '/wp-admin/includes/image.php';
}

add_filter( 'avatar_defaults', 'amapress_default_avatar' );
function amapress_default_avatar( $avatar_defaults ) {
	//Set the URL where the image file for your avatar is located
	$new_avatar_url                     = AMAPRESS__PLUGIN_URL . 'images/default_amapien.jpg';
	$avatar_defaults[ $new_avatar_url ] = __( 'Amapien Amapress', 'amapress' );

	return $avatar_defaults;
}

add_filter( 'get_avatar', 'amapress_get_avatar_filter', 10, 5 );
function amapress_get_avatar_filter( $avatar, $id_or_email = "", $size = "", $default = "", $alt = "" ) {
	global $all_sizes, $avatar_default, $blog_id, $post, $wpdb, $_wp_additional_image_sizes;
	$email = 'unknown@gravatar.com';
	// Checks if comment

	if ( is_object( $id_or_email ) ) {
		// Checks if comment author is registered user by user ID
		if ( $id_or_email->user_id != 0 ) {
			$email = $id_or_email->user_id;
			// Checks that comment author isn't anonymous
		} elseif ( ! empty( $id_or_email->comment_author_email ) ) {
			// Checks if comment author is registered user by e-mail address
			$user = get_user_by( 'email', $id_or_email->comment_author_email );
			// Get registered user info from profile, otherwise e-mail address should be value
			$email = ! empty( $user ) ? $user->ID : $id_or_email->comment_author_email;
		}
		$alt = $id_or_email->comment_author;
	} else {
		if ( ! empty( $id_or_email ) ) {
			// Find user by ID or e-mail address
			$user = is_numeric( $id_or_email ) ? get_user_by( 'id', $id_or_email ) : get_user_by( 'email', $id_or_email );
		} else {
			// Find author's name if id_or_email is empty
			$author_name = get_query_var( 'author_name' );
			if ( is_author() ) {
				// On author page, get user by page slug
				$user = get_user_by( 'slug', $author_name );
			} else {
				// On post, get user by author meta
				$user_id = get_the_author_meta( 'ID' );
				$user    = get_user_by( 'id', $user_id );
			}
		}
		// Set user's ID and name
		if ( ! empty( $user ) ) {
			$email = $user->ID;
			$alt   = $user->display_name;
		}
	}
	// Checks if user has WPUA
	$amapress_meta = get_the_author_meta( amapress_get_avatar_meta_name(), $email );
	// User has WPUA, check if on excluded list and bypass get_avatar
	if ( ! empty( $amapress_meta ) && wp_attachment_is_image( $amapress_meta ) ) {
		// Numeric size use size array
		$get_size = is_numeric( $size ) ? array( $size, $size ) : $size;
		// Get image src
		$amapress_image = wp_get_attachment_image_src( $amapress_meta, $get_size );
		// Add dimensions to img only if numeric size was specified
		$dimensions = is_numeric( $size ) ? ' width="' . $amapress_image[1] . '" height="' . $amapress_image[2] . '"' : "";
		// Construct the img tag
		$avatar = '<img src="' . $amapress_image[0] . '"' . $dimensions . ' alt="' . $alt . '" class="avatar avatar-' . $size . ' amapress-user-avatar amapress-user-avatar-' . $size . ' photo" />';
	}

	return $avatar;
}

function amapress_save_user_avatar( $userID ) {
	if ( isset( $_REQUEST['amapress_user_avatar'] ) ) {
		update_user_meta( $userID, amapress_get_avatar_meta_name(), $_REQUEST['amapress_user_avatar'] );
	}
}

add_filter( 'the_content', 'amapress_display_messages', 1 );
//add_action('after_setup_theme', 'amapress_lock_it_down');
add_action( 'template_redirect', 'amapress_process_user_profile_data' );

function amapress_user_last_login( $user_login, $user ) {
	update_user_meta( $user->ID, 'last_login', time() );
}

add_action( 'wp_login', 'amapress_user_last_login', 10, 2 );

function amapress_handle_image_upload( $upload ) {
	if ( file_is_displayable_image( $upload['tmp_name'] ) ) /*Check if image*/ {
		/*handle the uploaded file*/
		$overrides = array( 'test_form' => false );
		$file      = wp_handle_upload( $upload, $overrides );
	}

	return $file;
}

/**
 * Pocess the profile editor form
 */
function amapress_process_user_profile_data() {
	if ( isset( $_POST['user_profile_nonce_field'] ) && wp_verify_nonce( $_POST['user_profile_nonce_field'], 'user_profile_nonce' ) ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
		}

		// Get the current user id
		$user_id = amapress_current_user_id();

		// Put our data into a better looking array and sanitize it
		$user_data = array(
			'first_name'                => sanitize_text_field( ! empty( $_POST['first_name'] ) ? $_POST['first_name'] : '' ),
			'last_name'                 => sanitize_text_field( ! empty( $_POST['last_name'] ) ? $_POST['last_name'] : '' ),
			'user_email'                => sanitize_email( $_POST['email'] ),
			'email2'                    => sanitize_email( $_POST['email2'] ),
			'email3'                    => sanitize_email( $_POST['email3'] ),
			'email4'                    => sanitize_email( $_POST['email4'] ),
			'display_name'              => sanitize_text_field( ! empty( $_POST['display_name'] ) ? $_POST['display_name'] : '' ),
			'amapress_user_adresse'     => sanitize_text_field( $_POST['amapress_user_adresse'] ),
			'amapress_user_code_postal' => sanitize_text_field( $_POST['amapress_user_code_postal'] ),
			'amapress_user_ville'       => sanitize_text_field( $_POST['amapress_user_ville'] ),
			'amapress_user_telephone'   => sanitize_text_field( $_POST['amapress_user_telephone'] ),
			'amapress_user_telephone2'  => sanitize_text_field( $_POST['amapress_user_telephone2'] ),
			'amapress_user_moyen'       => sanitize_text_field( $_POST['amapress_user_moyen'] ),
			'amapress_user_hidaddr'     => isset( $_POST['amapress_user_hidaddr'] ) ? 1 : 0,
			'user_pass'                 => isset( $_POST['pass1'] ) ? $_POST['pass1'] : null,
		);

		if ( empty( $user_data['first_name'] ) ) {
			unset( $user_data['first_name'] );
		}
		if ( empty( $user_data['last_name'] ) ) {
			unset( $user_data['last_name'] );
		}
		if ( empty( $user_data['display_name'] ) ) {
			unset( $user_data['display_name'] );
		}

		if ( ! empty( $user_data['user_pass'] ) ) {

			// Validate the passwords to check they are the same
			if ( strcmp( $user_data['user_pass'], $_POST['pass2'] ) !== 0 ) {

				wp_redirect( '?password-error = true' );
				exit();
			}

		} else {
			// If the password fields are not set don't save
			unset( $user_data['user_pass'] );
		}

		$upload = $_FILES['amapress_user_avatar-upload'];
		if ( $upload ) {
			$uploads = wp_upload_dir(); /*Get path of upload dir of wordpress*/
			if ( is_writable( $uploads['path'] ) )  /*Check if upload dir is writable*/ {
				if ( ( ! empty( $upload['tmp_name'] ) ) )  /*Check if uploaded image is not empty*/ {
					if ( $upload['tmp_name'] )   /*Check if image has been uploaded in temp directory*/ {
						$file = amapress_handle_image_upload( $upload ); /*Call our custom function to ACTUALLY upload the image*/

						$attachment = array  /*Create attachment for our post*/
						(
							'post_mime_type' => $file['type'],  /*Type of attachment*/
							'post_parent'    => 0,  /*Post id*/
						);

						$aid      = wp_insert_attachment( $attachment, $file['file'] );  /*Insert post attachment and return the attachment id*/
						$a        = wp_generate_attachment_metadata( $aid, $file['file'] );  /*Generate metadata for new attacment*/
						$prev_img = get_user_meta( $user_id, amapress_get_avatar_meta_name(), true );  /*Get previously uploaded image*/
						if ( is_numeric( $prev_img ) ) {
							wp_delete_attachment( $prev_img );  /*Delete previous image*/
						}
						update_user_meta( $user_id, amapress_get_avatar_meta_name(), $aid );  /*Save the attachment id in meta data*/

						if ( ! is_wp_error( $aid ) ) {
							wp_update_attachment_metadata( $aid, $a );  /*If there is no error, update the metadata of the newly uploaded image*/
						}
					}
				}
			}
		} else if ( $_POST['amapress_user_avatar-delete'] ) {
			$prev_img = get_user_meta( $user_id, amapress_get_avatar_meta_name(), true );  /*Get previously uploaded image*/
			if ( is_numeric( $prev_img ) ) {
				wp_delete_attachment( $prev_img );  /*Delete previous image*/
			}
			update_user_meta( $user_id, amapress_get_avatar_meta_name(), null );  /*Save the attachment id in meta data*/
		}

		// Save the values to the post
		foreach ( $user_data as $key => $value ) {

			// http://codex.wordpress.org/Function_Reference/wp_update_user
			if ( $key == 'amapress_user_adresse'
			     || $key == 'amapress_user_code_postal'
			     || $key == 'amapress_user_ville'
			     || $key == 'amapress_user_telephone'
			     || $key == 'amapress_user_telephone2'
			     || $key == 'amapress_user_moyen'
			     || $key == 'email2'
			     || $key == 'email3'
			     || $key == 'email4'
			) {

				$res = update_user_meta( $user_id, $key, $value );
				unset( $user_data[ $key ] );

				if ( $key == 'amapress_user_adresse' ) {
					AmapressUsers::resolveUserAddress( $user_id );
				}

			} elseif ( $key == 'amapress_user_hidaddr' ) {
				if ( empty( $value ) ) {
					delete_user_meta( $user_id, $key );
				} else {
					update_user_meta( $user_id, $key, $value );
				}
			} elseif ( $key == 'user_pass' ) {

				$res = wp_set_password( $user_data['user_pass'], $user_id );
				unset( $user_data['user_pass'] );

				// Save the remaining values
			}
		}

		if ( ! empty( $user_data['first_name'] ) && ! empty( $user_data['last_name'] ) && empty( $user_data['display_name'] ) ) {
			$user_data['display_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
		}

		$res = wp_update_user( array_merge( $user_data, array( 'ID' => $user_id ) ) );

		// Display the messages error/success
		if ( ! is_wp_error( $res ) ) {
			$notify_email = get_option( 'admin_email' );

			if ( isset( $_REQUEST['cofoy1_remove'] ) ) {
				$amapien = AmapressUser::getBy( $user_id, true );
				$amapien->removeCoadherent( $amapien->getCoFoyer1Id(), $notify_email, true );
			} elseif ( ! empty( $_REQUEST['cofoy1_email'] ) ) {
				$cofoy1_email = sanitize_email( $_REQUEST['cofoy1_email'] );
				if ( ! empty( $cofoy1_email ) ) {
					$cofoy1_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['cofoy1_first_name'] ) ? $_REQUEST['cofoy1_first_name'] : '' );
					$cofoy1_user_last_name = sanitize_text_field( ! empty( $_REQUEST['cofoy1_last_name'] ) ? $_REQUEST['cofoy1_last_name'] : '' );
					$cofoy1_user_phones    = sanitize_text_field( ! empty( $_REQUEST['cofoy1_tels'] ) ? $_REQUEST['cofoy1_tels'] : '' );
					$cofoy1_user_address   = sanitize_text_field( ! empty( $_REQUEST['cofoy1_address'] ) ? $_REQUEST['cofoy1_address'] : '' );

					$cofoy1_user_id = amapress_create_user_if_not_exists( $cofoy1_email, $cofoy1_user_firt_name, $cofoy1_user_last_name, $cofoy1_user_address, $cofoy1_user_phones );
					if ( $cofoy1_user_id ) {
						$amapien = AmapressUser::getBy( $user_id, true );
						if ( $amapien->getCoFoyer1Id() != $cofoy1_user_id ) {
							$amapien->removeCoadherent( $amapien->getCoFoyer1Id(), $notify_email, true );
						}
						$amapien->addCoadherent( $cofoy1_user_id, $notify_email, true );
					}
				}
			}

			if ( isset( $_REQUEST['cofoy2_remove'] ) ) {
				$amapien = AmapressUser::getBy( $user_id, true );
				$amapien->removeCoadherent( $amapien->getCoFoyer2Id(), $notify_email, true );
			} elseif ( ! empty( $_REQUEST['cofoy2_email'] ) ) {
				$cofoy2_email = sanitize_email( $_REQUEST['cofoy2_email'] );
				if ( ! empty( $cofoy2_email ) ) {
					$cofoy2_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['cofoy2_first_name'] ) ? $_REQUEST['cofoy2_first_name'] : '' );
					$cofoy2_user_last_name = sanitize_text_field( ! empty( $_REQUEST['cofoy2_last_name'] ) ? $_REQUEST['cofoy2_last_name'] : '' );
					$cofoy2_user_phones    = sanitize_text_field( ! empty( $_REQUEST['cofoy2_tels'] ) ? $_REQUEST['cofoy2_tels'] : '' );
					$cofoy2_user_address   = sanitize_text_field( ! empty( $_REQUEST['cofoy2_address'] ) ? $_REQUEST['cofoy2_address'] : '' );

					$cofoy2_user_id = amapress_create_user_if_not_exists( $cofoy2_email, $cofoy2_user_firt_name, $cofoy2_user_last_name, $cofoy2_user_address, $cofoy2_user_phones );
					if ( $cofoy2_user_id ) {
						$amapien = AmapressUser::getBy( $user_id, true );
						if ( $amapien->getCoFoyer2Id() != $cofoy2_user_id ) {
							$amapien->removeCoadherent( $amapien->getCoFoyer2Id(), $notify_email, true );
						}
						$amapien->addCoadherent( $cofoy2_user_id, $notify_email, true );
					}
				}
			}

			if ( isset( $_REQUEST['cofoy3_remove'] ) ) {
				$amapien = AmapressUser::getBy( $user_id, true );
				$amapien->removeCoadherent( $amapien->getCoFoyer3Id(), $notify_email, true );
			} elseif ( ! empty( $_REQUEST['cofoy3_email'] ) ) {
				$cofoy3_email = sanitize_email( $_REQUEST['cofoy3_email'] );
				if ( ! empty( $cofoy3_email ) ) {
					$cofoy3_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['cofoy3_first_name'] ) ? $_REQUEST['cofoy3_first_name'] : '' );
					$cofoy3_user_last_name = sanitize_text_field( ! empty( $_REQUEST['cofoy3_last_name'] ) ? $_REQUEST['cofoy3_last_name'] : '' );
					$cofoy3_user_phones    = sanitize_text_field( ! empty( $_REQUEST['cofoy3_tels'] ) ? $_REQUEST['cofoy3_tels'] : '' );
					$cofoy3_user_address   = sanitize_text_field( ! empty( $_REQUEST['cofoy3_address'] ) ? $_REQUEST['cofoy3_address'] : '' );

					$cofoy3_user_id = amapress_create_user_if_not_exists( $cofoy3_email, $cofoy3_user_firt_name, $cofoy3_user_last_name, $cofoy3_user_address, $cofoy3_user_phones );
					if ( $cofoy3_user_id ) {
						$amapien = AmapressUser::getBy( $user_id, true );
						if ( $amapien->getCoFoyer3Id() != $cofoy3_user_id ) {
							$amapien->removeCoadherent( $amapien->getCoFoyer3Id(), $notify_email, true );
						}
						$amapien->addCoadherent( $cofoy3_user_id, $notify_email, true );
					}
				}
			}

			wp_redirect( '?profile-updated = true' );
		} else {
			wp_redirect( '?profile-updated = false' );
		}
		exit;
	}
}


/**
 * Display the correct message based on the query string.
 *
 * @param string $content Post content.
 *
 * @return string Message and content.
 */
function amapress_display_messages( $content ) {
	$message = '';
	if ( in_array( 'profile-updated', $_GET ) && 'true' == $_GET['profile-updated'] ) {
		$message = amapress_get_message_markup( __( 'Votre profile a été mis à jour avec succès.', 'amapress' ), 'success' );
	} else if ( in_array( 'profile-updated', $_GET ) && 'false' == $_GET['profile-updated'] ) {
		$message = amapress_get_message_markup( __( 'Il y a une erreur pendant l\'enregistrement.', 'amapress' ), 'danger' );
	} else if ( in_array( 'password-error', $_GET ) && 'true' == $_GET['password-error'] ) {
		$message = amapress_get_message_markup( __( 'Les mots de passe que vous avez entré ne correspondent pas.', 'amapress' ), 'danger' );
	}

	return $message . $content;
}

/**
 * A little helper function to generate the Bootstrap alerts markup.
 *
 * @param string $message Message to display.
 * @param string $severity Severity of message to display.
 *
 * @return string Message markup.
 */
function amapress_get_message_markup( $message, $severity ) {
	$output = '<div class="alert alert-' . $severity . ' alert-dismissable">';
	$output .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">';
	$output .= '<i class="fa fa-times-circle"></i>';
	$output .= '</button>';
	$output .= '<p class="text-center">' . $message . '</p>';
	$output .= '</div>';

	return $output;
}

add_filter( 'amapress_bulk_action_amp_resend_welcome', 'amapress_amp_resend_welcome_bulk_action', 10, 2 );
function amapress_amp_resend_welcome_bulk_action( $sendback, $post_ids ) {
	foreach ( $post_ids as $user_id ) {
		wp_send_new_user_notifications( $user_id, 'user' );
	}

	return amapress_add_bulk_count( $sendback, count( $post_ids ) );
}

add_filter( 'amapress_bulk_action_amp_relocate', 'amapress_bulk_action_amp_relocate', 10, 2 );
function amapress_bulk_action_amp_relocate( $sendback, $user_ids ) {
	$localized_users = [];
	foreach ( $user_ids as $user_id ) {
		$user = AmapressUser::getBy( $user_id );
		if ( $user ) {
			if ( AmapressUsers::resolveUserAddress( $user_id, $user->getFormattedAdresse() ) ) {
				$localized_users[] = $user_id;
			}
		}
	}

	return amapress_add_bulk_count( $sendback, count( $localized_users ) );
}

add_action( 'admin_post_inscription_amap_extern', 'amapress_admin_action_nopriv_inscription_amap_extern' );
add_action( 'admin_post_nopriv_inscription_amap_extern', 'amapress_admin_action_nopriv_inscription_amap_extern' );
function amapress_admin_action_nopriv_inscription_amap_extern() {
	header( 'Content-Type: text/html; charset=UTF-8' );
	if ( ! isset( $_REQUEST['email'] ) ) {
		die( __( 'Pas d\'email spécifié', 'amapress' ) );
	}
	if ( ! isset( $_REQUEST['group'] ) ) {
		die( __( 'Pas de groupe', 'amapress' ) );
	}
	$group_id = Amapress::resolve_tax_id( trim( sanitize_text_field( $_REQUEST['group'] ) ), AmapressUser::AMAPIEN_GROUP );
	if ( empty( $group_id ) ) {
		die( __( 'Groupe inconnu', 'amapress' ) );
	}
	/** @var WP_Term $term */
	$term = get_term( $group_id, AmapressUser::AMAPIEN_GROUP );

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
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}

	$user_firt_name = isset( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '';
	$user_last_name = isset( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '';
	$user_phone     = isset( $_REQUEST['phone'] ) ? $_REQUEST['phone'] : '';
	$user_address   = isset( $_REQUEST['address'] ) ? $_REQUEST['address'] : '';
	$user_email     = sanitize_email( $_REQUEST['email'] );

	$user = get_user_by( 'email', $user_email );
	if ( $user ) {
		echo '<p class="error">' . sprintf( __( 'L\'adresse email %s est déjà utilisée.', 'amapress' ), $user_email ) . '</p>';
		die();
	}

	$user_id = amapress_create_user_if_not_exists( $user_email, $user_firt_name, $user_last_name, $user_address, $user_phone );
	wp_set_object_terms( $user_id, $group_id, AmapressUser::AMAPIEN_GROUP );
	echo '<p class="success">' . sprintf(
			__( 'Vous êtes désormais inscrit sur le site %s en tant qu\'utilisateur %s. Vous allez recevoir un mail de bienvenue avec les instructions dans votre boîte mail.', 'amapress' ),
			esc_html( get_bloginfo( 'name' ) ),
			esc_html( $term->name )
		) . '</p>';
}
