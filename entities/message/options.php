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
		case "nom_complet":
		case "display_name":
			return $user->getDisplayName();
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

			return "<a href='sms:$phone$cnt'>SMS</a>";
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

			return "<a href='tel:$phone'>" . __( 'Téléphone', 'amapress' ) . "</a>";
		case "locto":
			$fmt = empty( $fmt ) ? __( 'Voir sur la carte', 'amapress' ) : $fmt;
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
			if ( empty( $subopt ) ) {
				return $user->getDisplayName();
			}

			return $user->getProperty( $subopt );
	}
}

function amapress_replace_mail_user_placeholders_help() {
	$ret                     = [];
	$ret["nom_complet"]      = __( 'Prénom nom de l\'amapien', 'amapress' );
	$ret["display_name"]     = __( 'Prénom nom de l\'amapien', 'amapress' );
	$ret["prenom"]           = __( 'Prénom de l\'amapien', 'amapress' );
	$ret["first_name"]       = __( 'Prénom de l\'amapien', 'amapress' );
	$ret["nom"]              = __( 'Nom de l\'amapien', 'amapress' );
	$ret["last_name"]        = __( 'Nom de l\'amapien', 'amapress' );
	$ret["mail"]             = __( 'Email de l\'amapien', 'amapress' );
	$ret["mailto"]           = __( 'Lien email de l\'amapien', 'amapress' );
	$ret["sms"]              = __( 'Téléphone de l\'amapien', 'amapress' );
	$ret["tel"]              = __( 'Téléphone de l\'amapien', 'amapress' );
	$ret["smsto"]            = __( 'Lien sms de l\'amapien', 'amapress' );
	$ret["whatsappto"]       = __( 'Lien vers WhatsApp de l\'amapien', 'amapress' );
	$ret["telto"]            = __( 'Lien vers d\'appel de l\'amapien', 'amapress' );
	$ret["locto"]            = __( 'Lien vers la localisation de l\'amapien', 'amapress' );
	$ret["adresse"]          = __( 'Adresse de l\'amapien', 'amapress' );
	$ret["address"]          = __( 'Adresse de l\'amapien', 'amapress' );
	$ret["adresse_html"]     = __( 'Adresse de l\'amapien', 'amapress' );
	$ret["address_html"]     = __( 'Adresse de l\'amapien', 'amapress' );
	$ret["login"]            = __( 'Identifiant de l\'amapien', 'amapress' );
	$ret["identifiant"]      = __( 'Identifiant de l\'amapien', 'amapress' );
	$ret["avatar"]           = __( 'Avatar de l\'amapien', 'amapress' );
	$ret['full_name']        = __( 'Prénom Nom de l\'amapien', 'amapress' );
	$ret['adherent_type']    = __( 'Type d\'adhérent (Principal, Co-adhérent...) de l\'amapien', 'amapress' );
	$ret['pseudo']           = __( 'Pseudo de l\'amapien', 'amapress' );
	$ret['nom_public']       = __( 'Nom public de l\'amapien', 'amapress' );
	$ret['nom']              = __( 'Nom de l\'amapien', 'amapress' );
	$ret['prenom']           = __( 'Prénom de l\'amapien', 'amapress' );
	$ret['adresse']          = __( 'Adresse de l\'amapien', 'amapress' );
	$ret['code_postal']      = __( 'Code postal de l\'amapien', 'amapress' );
	$ret['ville']            = __( 'Ville de l\'amapien', 'amapress' );
	$ret['rue']              = __( 'Rue (adresse) de l\'amapien', 'amapress' );
	$ret['tel']              = __( 'Téléphone de l\'amapien', 'amapress' );
	$ret['email']            = __( 'Email de l\'amapien', 'amapress' );
	$ret['coadhesion_infos'] = __( 'Infos sur les coadhésions de l\'amapien', 'amapress' );
	$ret['contacts']         = __( 'Moyens de contacts de l\'amapien', 'amapress' );
	$ret['roles']            = __( 'Rôles de l\'amapien', 'amapress' );

	return $ret;
}

/**
 * @param string $mail_content
 * @param AmapressUser|null $user
 * @param TitanEntity|null $post
 *
 * @return string
 */
function amapress_replace_mail_placeholders( $mail_content, $user, TitanEntity $post = null ) {
	$res = preg_replace_callback( '/\%\%(?<opt>[\w\d_\.-]+)(?:\:(?<subopt>[\w\d_\.-]+))?(?:,(?<fmt>[^%]+))?\%\%/i',
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
				case 'admin_email_link':
					return Amapress::makeLink( 'mailto:' . get_option( 'admin_email' ) );
				case 'admin_email':
					return get_option( 'admin_email' );
				case "description":
				case "site_description":
					return get_bloginfo( 'description' );
				case "site":
					switch ( $opt ) {
						default:
							return get_bloginfo( $subopt );
					}
				case 'expiration_reset_pass':
					return Amapress::getOption( 'welcome-mail-expiration' );

				case 'lien_inscription_distrib':
					$inscription_distrib_link = Amapress::get_inscription_distrib_page_href();
					if ( ! empty( $inscription_distrib_link ) ) {
						$inscription_distrib_link = Amapress::makeLink( $inscription_distrib_link, __( 'S\'inscrire comme responsable de distribution', 'amapress' ) );
					} else {
						$inscription_distrib_link = __( '#page inscription aux distributions non configurée#', 'amapress' );
					}

					return $inscription_distrib_link;
				case 'lien_mes_contrats':
					$mes_contrats_link = Amapress::get_mes_contrats_page_href();
					if ( ! empty( $mes_contrats_link ) ) {
						$mes_contrats_link = Amapress::makeLink( $mes_contrats_link, __( 'Mes contrats', 'amapress' ) );
					} else {
						$mes_contrats_link = '#page mes contrats non configurée#';
					}

					return $mes_contrats_link;
				case 'lien_inscription_contrats':
					$inscription_contrats_link = Amapress::get_pre_inscription_page_href();
					if ( empty( $inscription_contrats_link ) ) {
						$inscription_contrats_link = Amapress::get_logged_inscription_page_href();
						if ( empty( $inscription_contrats_link ) ) {
							$inscription_contrats_link = Amapress::get_mes_contrats_page_href();
						}
					}
					if ( ! empty( $inscription_contrats_link ) ) {
						$inscription_contrats_link = Amapress::makeLink( $inscription_contrats_link, __( 'S\'inscrire aux contrats', 'amapress' ) );
					} else {
						$inscription_contrats_link = '#page inscription contrats non configurée#';
					}

					return $inscription_contrats_link;
				case 'lien_carte_amapiens':
					$amapiens_map_link = Amapress::get_page_with_shortcode_href( 'amapiens-map', 'amps_amapiens_map_href' );
					if ( ! empty( $amapiens_map_link ) ) {
						$amapiens_map_link = Amapress::makeLink( $amapiens_map_link, __( 'Carte des amapiens', 'amapress' ) );
					} else {
						$amapiens_map_link = '#page [amapiens-map] non configurée#';
					}

					return $amapiens_map_link;
				case 'lien_intermittence':
				case 'lien_paniers_intermittence':
					$url = get_permalink( intval( Amapress::getOption( 'paniers-intermittents-page' ) ) );

					return Amapress::makeLink( $url );
				case 'lien_desinscription_intermittent':
					return Amapress::makeLink( amapress_intermittence_desinscription_link() );//Amapress::makeLink( $this->getDesinscriptionIntermittenceLink() );
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

					return "<a href='{$url}'>{$url}</a>";
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
						case 'title-edit-link':
						case 'titre-edit-lien':
							if ( $post != null ) {
								return Amapress::makeLink( $post->getAdminEditLink(), $post->getTitle() );
							} else {
								return '';
							}
							break;
						case 'edit-href':
							if ( $post != null ) {
								return $post->getAdminEditLink();
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

function amapress_replace_mail_placeholders_help(
	$post_type_desc,
	$include_sender = true,
	$include_target = true
) {
	$key = "amapress_replace_mail_placeholders_help_{$post_type_desc}_{$include_sender}_{$include_target}";
	$ret = wp_cache_get( $key );
	if ( false === $ret ) {
		$ret                              = [];
		$ret["nom_site"]                  = __( 'Nom de l\'AMAP', 'amapress' );
		$ret["site_name"]                 = __( 'Nom de l\'AMAP', 'amapress' );
		$ret["expiration_reset_pass"]     = __( 'Durée d\'expiration (en jours) du lien de Récupération de mot de passe', 'amapress' );
		$ret['lien_inscription_distrib']  = __( 'Lien vers la page d\'inscription comme responsable de distribution', 'amapress' );
		$ret['lien_inscription_contrats'] = __( 'Lien vers la page d\'inscription aux contrats (ou Mes contrats à défaut)', 'amapress' );
		$ret['lien_mes_contrats']         = __( 'Lien vers la page Mes contrats', 'amapress' );
		$ret['lien_carte_amapiens']       = __( 'Lien vers la page Carte des amapiens', 'amapress' );
		$ret["url"]                       = __( 'Url du site de l\'AMAP', 'amapress' );
		$ret["site_url"]                  = __( 'Url du site de l\'AMAP', 'amapress' );
		$ret["description"]               = __( 'Description du site de l\'AMAP', 'amapress' );
		$ret["site_description"]          = __( 'Description du site de l\'AMAP', 'amapress' );
		$ret["site:admin_email"]          = __( 'Email de l\'admin du site', 'amapress' ); //subopt
//	$ret["site:language"]               = __('Langue du site', 'amapress'); //subopt
		$ret["site:rss_url"]                     = __( 'Lien RSS du site', 'amapress' ); //subopt
		$ret["site:rss2_url"]                    = __( 'Lien RSS2 du site', 'amapress' ); //subopt
		$ret['site_icon_url']                    = __( 'Url du logo du site de l\'AMAP', 'amapress' );
		$ret['site_icon_url_link']               = __( 'Lien du logo du site de l\'AMAP', 'amapress' );
		$ret['lien_intermittence']               = __( 'Lien vers la page des paniers intermittents disponibles', 'amapress' );
		$ret['lien_paniers_intermittence']       = __( 'Lien vers la page des paniers intermittents disponibles', 'amapress' );
		$ret['lien_desinscription_intermittent'] = __( 'Lien de désinscription de la liste des intermittents', 'amapress' );

		if ( $include_sender ) {
			foreach ( amapress_replace_mail_user_placeholders_help() as $k => $v ) {
				$ret["me:$k"] = __( 'Expéditeur: ', 'amapress' ) . $v; //subopt
			}
		} elseif ( ! empty( $post_type_desc ) && 0 === strpos( $post_type_desc, 'user:' ) ) {
			foreach ( amapress_replace_mail_user_placeholders_help() as $k => $v ) {
				$ret["me:$k"] = $v; //subopt
			}
		}

		if ( $include_target ) {
			foreach ( amapress_replace_mail_user_placeholders_help() as $k => $v ) {
				$ret["dest:$k"] = __( 'Destinataire: ', 'amapress' ) . $v; //subopt
			}
		}
		$ret["login_url"]      = __( 'Url de login du site de l\'AMAP', 'amapress' );
		$ret["login_url_link"] = __( 'Lien vers la page login du site de l\'AMAP', 'amapress' );
		if ( $include_sender ) {
			$ret["password_url"]      = __( 'Lien de la page de Récupération de mot de passe', 'amapress' );
			$ret["password_url_raw"]  = __( 'Url de la page de Récupération de mot de passe', 'amapress' );
			$ret["registration_text"] = __( 'Texte de l\'email de récupération de mot de passe', 'amapress' );
		}
		$ret["now"] = __( 'Date courante', 'amapress' );
		if ( ! empty( $post_type_desc ) && 0 !== strpos( $post_type_desc, 'user:' ) ) {
			$ret['post:id']              = 'ID ' . $post_type_desc;
			$ret['post:title']           = __( 'Titre ', 'amapress' ) . $post_type_desc;
			$ret['post:titre']           = __( 'Titre ', 'amapress' ) . $post_type_desc;
			$ret['post:link']            = __( 'Lien vers la page info ', 'amapress' ) . $post_type_desc;
			$ret['post:lien']            = __( 'Lien vers la page info ', 'amapress' ) . $post_type_desc;
			$ret['post:title-edit-link'] = __( 'Lien avec titre vers la page d\'édition ', 'amapress' ) . $post_type_desc;
			$ret['post:titre-edit-lien'] = __( 'Lien avec titre vers la page d\'édition ', 'amapress' ) . $post_type_desc;
			$ret['post:title-link']      = __( 'Lien avec titre vers la page info ', 'amapress' ) . $post_type_desc;
			$ret['post:titre-lien']      = __( 'Lien avec titre vers la page info ', 'amapress' ) . $post_type_desc;
			$ret['post:href']            = __( 'Url de la page info ', 'amapress' ) . $post_type_desc;
			$ret['post:edit-href']       = __( 'Url de la page d\'édition ', 'amapress' ) . $post_type_desc;
		}
		wp_cache_set( $key, $ret );
	}

	return $ret;
}