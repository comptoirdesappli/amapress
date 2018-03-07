<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_user_reset_password_url( WP_User $user ) {
	global $wpdb, $wp_hasher;

	// Generate something random for a password reset key.
	$key = wp_generate_password( 20, false );

	/** This action is documented in wp-login.php */
	do_action( 'retrieve_password_key', $user->user_login, $key );

	// Now insert the key, hashed, into the DB.
	if ( empty( $wp_hasher ) ) {
		require_once( ABSPATH . 'wp-includes/class-phpass.php' );
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

	return network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
}

/**
 * @param AmapressUser $user
 * @param $subopt
 *
 * @return string
 */
function amapress_replace_mail_user_placeholder( $user, $subopt, $fmt ) {
	if ( ! $user ) {
		return '';
	}
	$email = stripslashes( $user->getUser()->user_email );
	$phone = stripslashes( $user->getTelephone() );
	switch ( $subopt ) {
		case "prenom":
		case "first_name":
			return $user->getUser()->first_name;
		case "nom":
		case "last_name":
			return $user->getUser()->last_name;
		case "mail":
			if ( empty( $email ) ) {
				return '';
			}

			return $email;
		case "mailto":
			if ( empty( $email ) ) {
				return '';
			}
			$subj = ! empty( $fmt ) ? "?subject=$fmt" : '';

			return "<a href='mailto:{$email}{$subj}'>{$email}</a>";
		case "sms":
		case "tel":
			return $phone;
		case "smsto":
			if ( empty( $phone ) ) {
				return '';
			}
			$cnt = ! empty( $fmt ) ? "?body=$fmt" : '';

			return "<a href='smsto:$phone$cnt'>SMS</a>";
		case "whatsappto":
			if ( empty( $phone ) ) {
				return '';
			}
			$cnt = ! empty( $fmt ) ? "?text=$fmt" : '?text=';

			return "<a href='whatsapp://send/$phone$cnt'>WhatsApp</a>";
		case "telto":
			if ( empty( $phone ) ) {
				return '';
			}

			return "<a href='tel:$phone'>T�l�phone</a>";
		case "locto":
			$fmt = empty( $fmt ) ? 'Voir sur la carte' : $fmt;
			if ( ! $user->isAdresse_localized() ) {
				return "<a href='geopoint:{$user->getUserLatitude()},{$user->getUserLongitude()}'>$fmt</a>";
			} else {
				return '';
			}
		case "adresse":
		case "address":
			return $user->getFormattedAdresse();
		case "adresse_html":
		case "address_html":
			return $user->getFormattedAdresseHtml();
		case "login":
		case "identifiant":
			return $user->getUser()->user_login;
		case "avatar":
			$img = get_avatar( $user->ID );

			return '<div class="user-photo">' . $img . '</div>';
		default:
			return $user->getDisplayName();
	}
}

/**
 * @param string $mail_content
 * @param AmapressUser|null $user
 * @param TitanEntity|null $post
 *
 * @return string
 */
function amapress_replace_mail_placeholders( $mail_content, $user, TitanEntity $post = null ) {
	$res = preg_replace_callback( '/\%\%(?<opt>[\w\d_-]+)(?:\:(?<subopt>[\w\d_-]+))?(?:,(?<fmt>[^%]+))?\%\%/i',
		function ( $m ) use ( $user, $post ) {
			/** @var TitanEntity $post */
			$opt    = isset( $m['opt'] ) ? $m['opt'] : '';
			$subopt = isset( $m['subopt'] ) ? $m['subopt'] : '';
			$fmt    = isset( $m['fmt'] ) ? $m['fmt'] : '';


			switch ( $opt ) {
				case "nom_site":
				case "site_name":
					return get_bloginfo( 'name' );
				case "url":
				case "site_url":
					return get_bloginfo( 'url' );
				case "description":
				case "site_description":
					return get_bloginfo( 'description' );
				case "site":
					switch ( $opt ) {
						default:
							return get_bloginfo( $subopt );
					}
				case 'site_icon_url':
					$size = empty( $fmt ) ? 'thumbnail' : $fmt;
					preg_match( '/(?<w>\d+)x(?<h>\d+)/', $fmt, $ma );
					if ( $ma ) {
						$size = array( intval( $ma['w'] ), intval( $ma['h'] ) );
					}
					$site_icon_id = get_option( 'site_icon' );
					$image        = wp_get_attachment_image_src( $site_icon_id, $size );
					if ( empty( $image ) ) {
						return '';
					}

					return esc_url( $image[0] );
				case 'site_icon_url_link':
					$size = empty( $fmt ) ? 'thumbnail' : $fmt;
					preg_match( '/(?<w>\d+)x(?<h>\d+)/', $fmt, $ma );
					if ( $ma ) {
						$size = array( intval( $ma['w'] ), intval( $ma['h'] ) );
					}
					$site_icon_id = get_option( 'site_icon' );
					$image        = wp_get_attachment_image_src( $site_icon_id, $size );
					if ( empty( $image ) ) {
						return '';
					}
					$url = esc_url( $image[0] );

					return "<img src='{$url}' />";
				case "moi":
				case "me":
					$curr = AmapressUser::getBy( amapress_current_user_id() );

					if ( ! $curr ) {
						return '';
					}

					return amapress_replace_mail_user_placeholder( $curr, $subopt, $fmt );
				case "user":
				case "dest":
					if ( ! $user ) {
						return '';
					}

					$curr = AmapressUser::getBy( $user->ID );

					if ( ! $curr ) {
						return '';
					}

					return amapress_replace_mail_user_placeholder( $curr, $subopt, $fmt );
				case "login_url":
					return wp_login_url();
				case "login_url_link":
					$url = wp_login_url();

					return "<a href='mailto:{$url}'>{$url}</a>";
				case "password_url":
					if ( ! $user ) {
						return '';
					}

					$url = amapress_get_user_reset_password_url( $user->getUser() );

					return '<a href="' . esc_attr( $url ) . '">' . esc_html( $url ) . '</a>';
					break;
				case "password_url_raw":
					if ( ! $user ) {
						return '';
					}

					return amapress_get_user_reset_password_url( $user->getUser() );
				case "registration_text":
					if ( ! $user ) {
						return '';
					}

					$message = sprintf( __( 'Username: %s' ), $user->getUser()->user_login ) . "\r\n\r\n";
					$message .= __( 'To set your password, visit the following address:' ) . "\r\n\r\n";
					$message .= '<' . amapress_get_user_reset_password_url( $user->getUser() ) . ">\r\n\r\n";

					return $message;

				case "now":
					if ( empty( $fmt ) ) {
						$fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
					}

					return date_i18n( $fmt );
					break;
				case 'post':
					switch ( $subopt ) {
						case 'id':
							if ( $post != null ) {
								return $post->ID;
							} else {
								if ( ! $user ) {
									return '';
								}

								return $user->ID;
							}
						case 'title':
						case 'titre':
							if ( $post != null ) {
								return $post->getTitle();
							} else {
								if ( ! $user ) {
									return '';
								}

								return $user->getDisplayName();
							}
							break;
						case 'link':
						case 'lien':
							if ( $post != null ) {
								return Amapress::makeLink( $post->getPermalink() );
							} else {
								return '';
							}
							break;
						case 'title-link':
						case 'titre-lien':
							if ( $post != null ) {
								return Amapress::makeLink( $post->getPermalink(), $post->getTitle() );
							} else {
								return '';
							}
							break;
						case 'href':
							if ( $post != null ) {
								return $post->getPermalink();
							} else {
								return '';
							}
							break;

						default:
							if ( null != $post ) {
								return $post->getProperty( $subopt );
							} else if ( null != $user ) {
								return $user->getProperty( $subopt );
							} else {
								return $m[0];
							}
					}
					break;
				default:
					if ( null != $post ) {
						return $post->getProperty( $opt );
					} else if ( null != $user ) {
						return $user->getProperty( $opt );
					} else {
						return $m[0];
					}
			}
		}, $mail_content );

	return $res;
}