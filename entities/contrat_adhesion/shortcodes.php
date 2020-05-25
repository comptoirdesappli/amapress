<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_init', function () {
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_coords' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
				wp_die( 'Accès interdit' );
			}
		}
		$email              = sanitize_email( $_REQUEST['email'] );
		$user_firt_name     = sanitize_text_field( ! empty( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '' );
		$user_last_name     = sanitize_text_field( ! empty( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '' );
		$user_address       = sanitize_textarea_field( $_REQUEST['address'] );
		$user_mobile_phones = sanitize_text_field( $_REQUEST['telm'] );
		$user_fix_phones    = sanitize_text_field( $_REQUEST['telf'] );
		$user_phones        = array_filter( [ $user_mobile_phones, $user_fix_phones ], function ( $s ) {
			return ! empty( $s );
		} );

		$notify_email = get_option( 'admin_email' );
		if ( ! empty( $_REQUEST['notify_email'] ) ) {
			if ( empty( $notify_email ) ) {
				$notify_email = $_REQUEST['notify_email'];
			} else {
				$notify_email .= ',' . $_REQUEST['notify_email'];
			}
		}

		add_filter( 'wp_new_user_notification_email_admin', function ( $wp_new_user_notification_email_admin ) use ( $notify_email ) {
			$wp_new_user_notification_email_admin['to'] .= ',' . $notify_email;

			return $wp_new_user_notification_email_admin;
		} );

		add_filter( 'new_user_approve_email_admins', function ( $emails ) use ( $notify_email ) {
			return array_merge( $emails, explode( ',', $notify_email ) );
		} );

		$notify = null;
		if ( ! Amapress::toBool( $_REQUEST['send_welcome'] ) ) {
			$notify = 'admin';
		}
		$user_id = amapress_create_user_if_not_exists( $email, $user_firt_name, $user_last_name, $user_address, $user_phones, $notify );
		if ( ! $user_id ) {
			wp_redirect_and_exit( add_query_arg( 'message', 'cannot_create_user' ) );
		}
		if ( isset( $_REQUEST['hidaddr'] ) ) {
			update_user_meta( $user_id, 'amapress_user_hidaddr', 1 );
		} else {
			delete_user_meta( $user_id, 'amapress_user_hidaddr' );
		}

		if ( ! empty( $_REQUEST['coadh1_email'] ) ) {
			$coadh1_email = sanitize_email( $_REQUEST['coadh1_email'] );
			if ( ! empty( $coadh1_email ) ) {
				$coadh1_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['coadh1_first_name'] ) ? $_REQUEST['coadh1_first_name'] : '' );
				$coadh1_user_last_name = sanitize_text_field( ! empty( $_REQUEST['coadh1_last_name'] ) ? $_REQUEST['coadh1_last_name'] : '' );
				$coadh1_user_phones    = sanitize_text_field( ! empty( $_REQUEST['coadh1_tels'] ) ? $_REQUEST['coadh1_tels'] : '' );
				$coadh1_user_address   = sanitize_text_field( ! empty( $_REQUEST['coadh1_address'] ) ? $_REQUEST['coadh1_address'] : '' );

				$coadh1_user_id = amapress_create_user_if_not_exists( $coadh1_email, $coadh1_user_firt_name, $coadh1_user_last_name, $coadh1_user_address, $coadh1_user_phones );
				if ( $coadh1_user_id ) {
					$amapien = AmapressUser::getBy( $user_id, true );
					$amapien->addCoadherent( $coadh1_user_id, $notify_email );
				}
			}
		} else if ( isset( $_REQUEST['coadh1_remove'] ) ) {
			$amapien = AmapressUser::getBy( $user_id, true );
			$amapien->removeCoadherent( $amapien->getCoAdherent1Id(), $notify_email );
		}

		if ( ! empty( $_REQUEST['coadh2_email'] ) ) {
			$coadh2_email = sanitize_email( $_REQUEST['coadh2_email'] );
			if ( ! empty( $coadh2_email ) ) {
				$coadh2_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['coadh2_first_name'] ) ? $_REQUEST['coadh2_first_name'] : '' );
				$coadh2_user_last_name = sanitize_text_field( ! empty( $_REQUEST['coadh2_last_name'] ) ? $_REQUEST['coadh2_last_name'] : '' );
				$coadh2_user_phones    = sanitize_text_field( ! empty( $_REQUEST['coadh2_tels'] ) ? $_REQUEST['coadh2_tels'] : '' );
				$coadh2_user_address   = sanitize_text_field( ! empty( $_REQUEST['coadh2_address'] ) ? $_REQUEST['coadh2_address'] : '' );

				$coadh2_user_id = amapress_create_user_if_not_exists( $coadh2_email, $coadh2_user_firt_name, $coadh2_user_last_name, $coadh2_user_address, $coadh2_user_phones );
				if ( $coadh2_user_id ) {
					$amapien = AmapressUser::getBy( $user_id, true );
					$amapien->addCoadherent( $coadh2_user_id, $notify_email );
				}
			}
		} else if ( isset( $_REQUEST['coadh2_remove'] ) ) {
			$amapien = AmapressUser::getBy( $user_id, true );
			$amapien->removeCoadherent( $amapien->getCoAdherent2Id(), $notify_email );
		}

		if ( ! empty( $_REQUEST['coadh3_email'] ) ) {
			$coadh3_email = sanitize_email( $_REQUEST['coadh3_email'] );
			if ( ! empty( $coadh3_email ) ) {
				$coadh3_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['coadh3_first_name'] ) ? $_REQUEST['coadh3_first_name'] : '' );
				$coadh3_user_last_name = sanitize_text_field( ! empty( $_REQUEST['coadh3_last_name'] ) ? $_REQUEST['coadh3_last_name'] : '' );
				$coadh3_user_phones    = sanitize_text_field( ! empty( $_REQUEST['coadh3_tels'] ) ? $_REQUEST['coadh3_tels'] : '' );
				$coadh3_user_address   = sanitize_text_field( ! empty( $_REQUEST['coadh3_address'] ) ? $_REQUEST['coadh3_address'] : '' );

				$coadh3_user_id = amapress_create_user_if_not_exists( $coadh3_email, $coadh3_user_firt_name, $coadh3_user_last_name, $coadh3_user_address, $coadh3_user_phones );
				if ( $coadh3_user_id ) {
					$amapien = AmapressUser::getBy( $user_id, true );
					$amapien->addCoadherent( $coadh3_user_id, $notify_email );
				}
			}
		} else if ( isset( $_REQUEST['coadh3_remove'] ) ) {
			$amapien = AmapressUser::getBy( $user_id, true );
			$amapien->removeCoadherent( $amapien->getCoAdherent3Id(), $notify_email );
		}

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


		$quest1         = wp_unslash( Amapress::getOption( 'online_new_user_quest1' ) );
		$quest1_answser = '';
		if ( isset( $_REQUEST['online_new_user_quest1'] ) ) {
			$quest1_answser = sanitize_textarea_field( $_REQUEST['online_new_user_quest1'] );
		}
		$quest2         = wp_unslash( Amapress::getOption( 'online_new_user_quest2' ) );
		$quest2_answser = '';
		if ( isset( $_REQUEST['online_new_user_quest2'] ) ) {
			$quest2_answser = sanitize_textarea_field( $_REQUEST['online_new_user_quest2'] );
		}
		$quest_email = Amapress::getOption( 'online_new_user_quest_email' );
		if ( empty( $quest_email ) ) {
			$quest_email = get_option( 'admin_email' );
		}

		if ( ! Amapress::isHtmlEmpty( $quest1_answser ) || ! Amapress::isHtmlEmpty( $quest2_answser ) ) {
			$amapien           = AmapressUser::getBy( $user_id );
			$user_display_name = $amapien->getDisplayName();
			$user_email        = $amapien->getEmail();
			$user_link         = Amapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName() );
			$quest1            = wp_strip_all_tags( $quest1, true );
			$quest2            = wp_strip_all_tags( $quest2, true );
			amapress_wp_mail(
				$quest_email,
				"Réponses nouvel adhérent - $user_display_name ($user_email)",
				wpautop(
					"Bonjour,\n\nLe nouvel ahdérent $user_link a répondu aux questions:\n" .
					( ! Amapress::isHtmlEmpty( $quest1 ) ? "- $quest1:\n$quest1_answser\n" : '' ) .
					( ! Amapress::isHtmlEmpty( $quest2 ) ? "- $quest2:\n$quest2_answser\n" : '' ) .
					"\n\n" . get_bloginfo( 'name' )
				)
			);
		}

		wp_redirect_and_exit(
			add_query_arg( [
				'step'    => ! empty( $_REQUEST['coords_next_step'] ) ? $_REQUEST['coords_next_step'] : 'contrats',
				'user_id' => $user_id,
			] )
		);
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_agreement' == $_REQUEST['inscr_assistant'] ) {
		$step = ! empty( $_REQUEST['coords_next_step'] ) ? $_REQUEST['coords_next_step'] : 'contrats';
		if ( isset( $_REQUEST['accept'] ) && ! $_REQUEST['accept'] ) {
			$step = 'agreement';
		}
		$user_id = intval( $_REQUEST['user_id'] );
		wp_redirect_and_exit(
			add_query_arg( [
				'step'    => $step,
				'user_id' => $user_id,
			] )
		);
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'generate_contrat' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
				wp_die( 'Accès interdit' );
			}
		}

		$inscr_id = intval( $_REQUEST['inscr_id'] );
		if ( empty( $inscr_id ) ) {
			wp_die( 'Accès interdit' );
		}
		$adhesion = AmapressAdhesion::getBy( $inscr_id );
		if ( empty( $adhesion ) ) {
			wp_die( 'Accès interdit' );
		}

		$full_file_name = $adhesion->generateContratDoc( false );
		$file_name      = basename( $full_file_name );
		Amapress::sendDocumentFile( $full_file_name, $file_name );
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'generate_bulletin' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
				wp_die( 'Accès interdit' );
			}
		}

		$adh_id = intval( $_REQUEST['adh_id'] );
		if ( empty( $adh_id ) ) {
			wp_die( 'Accès interdit' );
		}
		$adhesion_paiement = AmapressAdhesion_paiement::getBy( $adh_id );
		if ( empty( $adhesion_paiement ) ) {
			wp_die( 'Accès interdit' );
		}

		$full_file_name = $adhesion_paiement->generateBulletinDoc( false );
		$file_name      = basename( $full_file_name );
		Amapress::sendDocumentFile( $full_file_name, $file_name );
	}
} );

function amapress_logged_self_inscription( $atts, $content = null, $tag ) {
	$atts               = wp_parse_args( $atts );
	$atts['for_logged'] = 'true';
	unset( $atts['key'] );

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_mes_contrats( $atts, $content = null, $tag ) {
	$atts               = wp_parse_args( $atts );
	$atts['for_logged'] = 'true';
	unset( $atts['edit_names'] );
	unset( $atts['shorturl'] );
	unset( $atts['max_coadherents'] );
	unset( $atts['mob_phone_required'] );
	unset( $atts['allow_remove_coadhs'] );
	unset( $atts['track_no_renews'] );
	unset( $atts['track_no_renews_email'] );
	unset( $atts['notify_email'] );
	unset( $atts['allow_coadherents_inscription'] );
	unset( $atts['allow_coadherents_access'] );
	unset( $atts['allow_coadherents_adhesion'] );
	unset( $atts['show_coadherents_address'] );
	if ( isset( $atts['allow_adhesion'] ) ) {
		$atts['adhesion'] = $atts['allow_adhesion'];
	} else {
		$atts['adhesion'] = 'false';
	}

	return amapress_self_inscription( $atts, $content, $tag );
}

/**
 * @param $atts
 */
function amapress_self_inscription( $atts, $content = null, $tag ) {
	amapress_ensure_no_cache();

	$step              = isset( $_REQUEST['step'] ) ? $_REQUEST['step'] : 'email';
	$disable_principal = Amapress::getOption( 'disable_principal', false );

	$atts = shortcode_atts(
		[
			'key'                              => '',
			'for_logged'                       => 'false',
			'filter_multi_contrat'             => 'false',
			'admin_mode'                       => 'false',
			'agreement'                        => 'false',
			'mob_phone_required'               => 'false',
			'check_principal'                  => 'true',
			'adhesion'                         => 'true',
			'send_adhesion_confirm'            => 'true',
			'send_contrat_confirm'             => 'true',
			'send_referents'                   => 'true',
			'allow_inscription_all_dates'      => 'false',
			'send_tresoriers'                  => 'true',
			'ignore_renouv_delta'              => 'true',
			'allow_inscriptions'               => 'true',
			'allow_new_mail'                   => 'true',
			'track_no_renews'                  => 'false',
			'track_no_renews_email'            => get_option( 'admin_email' ),
			'notify_email'                     => '',
			'max_produit_label_width'          => '10em',
			'paiements_info_required'          => 'false',
			'paniers_modulables_editor_height' => 350,
			'send_welcome'                     => 'true',
			'edit_names'                       => 'true',
			'allow_remove_cofoyers'            => 'true',
			'allow_remove_coadhs'              => 'false',
			'contact_referents'                => 'true',
			'show_adherents_infos'             => 'true',
			'show_details_button'              => 'false',
			'allow_coadherents_access'         => 'true',
			'allow_coadherents_inscription'    => 'true',
			'allow_coadherents_adhesion'       => 'true',
			'show_coadherents_address'         => 'false',
			'show_cofoyers_address'            => 'false',
			'contrat_print_button_text'        => 'Imprimer',
			'adhesion_print_button_text'       => 'Imprimer',
			'only_contrats'                    => '',
			'shorturl'                         => '',
			'show_modify_coords'               => 'inscription-en-ligne' == $tag ? 'false' : 'true',
			'show_due_amounts'                 => 'false',
			'show_delivery_details'            => 'false',
			'show_calendar_delivs'             => 'false',
			'show_current_inscriptions'        => 'inscription-en-ligne-connecte' == $tag ? 'false' : 'true',
			'show_editable_inscriptions'       => 'true',
			'adhesion_shift_weeks'             => 0,
			'before_close_hours'               => 24,
			'max_coadherents'                  => 3,
			'max_cofoyers'                     => 3,
			'use_contrat_term'                 => 'true',
			'skip_coords'                      => 'false',
			'email'                            => get_option( 'admin_email' ),
		]
		, $atts );

	$ignore_renouv_delta        = Amapress::toBool( $atts['ignore_renouv_delta'] );
	$contrat_print_button_text  = $atts['contrat_print_button_text'];
	$adhesion_print_button_text = $atts['adhesion_print_button_text'];
	$for_logged                 = Amapress::toBool( $atts['for_logged'] );
	$ret                        = '';
	$use_contrat_term           = Amapress::toBool( $atts['use_contrat_term'] );
	$admin_mode                 = Amapress::toBool( $atts['admin_mode'] );
	if ( $admin_mode && ! is_admin() ) {
		wp_die( 'admin_mode ne peut pas être utilisé directement' );
	}

	$paiements_info_required       = Amapress::toBool( $atts['paiements_info_required'] );
	$activate_adhesion             = Amapress::toBool( $atts['adhesion'] );
	$activate_agreement            = Amapress::toBool( $atts['agreement'] );
	$allow_remove_coadhs           = Amapress::toBool( $atts['allow_remove_coadhs'] );
	$allow_remove_cofoys           = Amapress::toBool( $atts['allow_remove_cofoyers'] );
	$allow_coadherents_inscription = Amapress::toBool( $atts['allow_coadherents_inscription'] );
	$allow_coadherents_adhesion    = Amapress::toBool( $atts['allow_coadherents_adhesion'] );
	$show_adherents_infos          = Amapress::toBool( $atts['show_adherents_infos'] );
	$track_no_renews               = Amapress::toBool( $atts['track_no_renews'] );
	$show_coadherents_address      = Amapress::toBool( $atts['show_coadherents_address'] );
	$show_cofoys_address           = Amapress::toBool( $atts['show_cofoyers_address'] );
	$show_due_amounts              = Amapress::toBool( $atts['show_due_amounts'] );
	$show_modify_coords            = Amapress::toBool( $atts['show_modify_coords'] );
	$show_current_inscriptions     = Amapress::toBool( $atts['show_current_inscriptions'] ) || $admin_mode;
	$show_editable_inscriptions    = Amapress::toBool( $atts['show_editable_inscriptions'] ) || $admin_mode;
	$show_delivery_details         = Amapress::toBool( $atts['show_delivery_details'] );
//	$allow_edit_inscriptions       = Amapress::toBool( $atts['allow_edit_inscriptions'] );
	$notify_email = $atts['notify_email'];
	if ( ! $allow_coadherents_inscription ) {
		$show_adherents_infos = true;
	}
	$key          = $atts['key'];
	$max_cofoyers = intval( $atts['max_cofoyers'] );
	$max_coadhs   = intval( $atts['max_coadherents'] );
	if ( $admin_mode && amapress_is_user_logged_in() && amapress_can_access_admin() ) {
		if ( ! isset( $_REQUEST['step'] ) ) {
			$step = 'contrats';
		}
	} else if ( $for_logged ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '<div class="alert alert-danger">Accès interdit</div>';
		}
		if ( ! isset( $_REQUEST['step'] ) ) {
			if ( 'mes-contrats' == $tag || Amapress::toBool( $atts['skip_coords'] ) ) {
				$step = 'contrats';
			} else {
				$step = 'coords_logged';
			}
		}
	} else {
		if ( amapress_can_access_admin() ) {
			$url = add_query_arg( 'key', $key, get_permalink() );
			if ( empty( $_REQUEST['key'] ) ) {
				$ret .= amapress_get_panel_start( 'Information d\'accès pour le collectif' );
				$ret .= '<div class="alert alert-info">Pour donner accès à cet assistant aux nouveaux amapiens, veuillez leur envoyer le lien suivant : 
<pre>' . $url . '</pre>
Pour y accéder cliquez <a href="' . $url . '">ici</a>.<br />
Vous pouvez également utiliser un service de réduction d\'URL tel que <a href="https://bit.ly">bit.ly</a> pour obtenir une URL plus courte à partir du lien ci-dessus.<br/>
' . ( ! empty( $atts['shorturl'] ) ? 'Lien court sauvegardé : <code>' . $atts['shorturl'] . '</code><br />' : '' ) . '
Vous pouvez également utiliser l\'un des QRCode suivants : 
<div>' . amapress_print_qrcode( $url ) . amapress_print_qrcode( $url, 3 ) . amapress_print_qrcode( $url, 2 ) . '</div><br/>
<strong>Attention : les lien ci-dessus, QR code et bit.ly NE doivent PAS être visible publiquement sur le site. Ce lien permet de créer des comptes sur le site et l\'exposer sur internet pourrait permettre à une personne malvaillante de polluer le site avec des comptes de SPAM.</strong><br />
Vous pouvez configurer l\'email envoyé en fin de chaque inscription <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_mails' ) . '">ici</a> et retrouver toutes les options de ce shortcode dans l\'<a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ) . '">Aide</a>.</div>';
				$ret .= amapress_get_panel_end();
			} else {
				$ret .= '<div class="alert alert-info"><a href="' . esc_attr( get_permalink() ) . '">Afficher les instructions d\'accès à cet assistant.</a></div>';
			}
		}
		if ( empty( $key ) || empty( $_REQUEST['key'] ) || $_REQUEST['key'] != $key ) {
			if ( empty( $key ) && amapress_can_access_admin() ) {
				$ret .= '<div style="color:red">L\'argument key (par ex, key="' . uniqid() . uniqid() . '") doit être défini sur le shortcode [inscription-en-ligne] de cette page : par exemple "[inscription-en-ligne key='
				        . uniqid() . uniqid() . ']". L\'accès à cette page ne peut se faire que de manière non connectée avec cette clé par la amapiens pour s\'inscrire.</div>';
			} elseif ( ! empty( $key ) && empty( $_REQUEST['key'] ) && amapress_is_user_logged_in() ) {
				$url              = esc_attr( add_query_arg( 'key', $key, get_permalink() ) );
				$mes_contrat_href = esc_attr( Amapress::get_mes_contrats_page_href() );
				$ret              .= "<p>Pour accéder à l'assistant d'inscription, cliquez <a href='$url'>ici</a></p>";
				if ( ! empty( $mes_contrat_href ) ) {
					if ( ! $use_contrat_term ) {
						$ret .= "<p>Pour accéder à vos commandes, cliquez <a href='$mes_contrat_href'>ici</a></p>";
					} else {
						$ret .= "<p>Pour accéder à vos contrats, cliquez <a href='$mes_contrat_href'>ici</a></p>";
					}
				}
			} else {
				$ret .= '<div class="alert alert-danger">Vous êtes dans un espace sécurisé. Accès interdit</div>';
			}

			$ret .= $content;

			return $ret;
		}
	}

	//optimize loading of producteur and user
	$all_prods          = Amapress::get_producteurs();
	$all_prods_user_ids = array_map( function ( $p ) {
		/** @var AmapressProducteur $p */
		return $p->getUserId();
	}, $all_prods );
	cache_users( $all_prods_user_ids );

	//optimize loading of referents and user
	$all_refs          = AmapressContrats::getAllReferentProducteursAndLieux();
	$all_refs_user_ids = array_map( function ( $r ) {
		return $r['ref_id'];
	}, $all_refs );
	update_meta_cache( 'user', $all_refs_user_ids );
	cache_users( $all_refs_user_ids );

	$additional_css = '<style type="text/css">' . esc_html( wp_unslash( Amapress::getOption( 'online_inscr_css' ) ) ) . '</style>';

	ob_start();

	echo $additional_css;

	echo $ret;

	$min_total = 0;
	Amapress::setFilterForReferent( false );
	$subscribable_contrats = AmapressContrats::get_subscribable_contrat_instances_by_contrat( null );
	Amapress::setFilterForReferent( true );
	$all_subscribable_contrats_ids = array_map( function ( $c ) {
		return $c->ID;
	}, $subscribable_contrats );
	if ( ! $admin_mode ) {
		$subscribable_contrats         = array_filter( $subscribable_contrats, function ( $c ) {
			/** @var AmapressContrat_instance $c */
			return $c->canSelfSubscribe() && ! $c->isEnded();
		} );
		$all_subscribable_contrats_ids = array_map( function ( $c ) {
			return $c->ID;
		}, $subscribable_contrats );
		$subscribable_contrats         = array_filter( $subscribable_contrats, function ( $c ) {
			/** @var AmapressContrat_instance $c */
			return ! $c->isFull();
		} );
		if ( ! empty( $atts['only_contrats'] ) ) {
			$only_contrats         = array_map( function ( $c ) {
				return Amapress::resolve_post_id( $c, AmapressContrat::INTERNAL_POST_TYPE );
			}, explode( ',', $atts['only_contrats'] ) );
			$subscribable_contrats = array_filter( $subscribable_contrats, function ( $c ) use ( $only_contrats ) {
				/** @var AmapressContrat_instance $c */
				return in_array( $c->getModelId(), $only_contrats );
			} );
		}
	}
	usort( $subscribable_contrats, function ( $a, $b ) {
		/** @var AmapressContrat_instance $a */
		/** @var AmapressContrat_instance $b */
		return strcmp( $a->getTitle(), $b->getTitle() );
	} );
	$subscribable_contrats_ids = array_map( function ( $c ) {
		return $c->ID;
	}, $subscribable_contrats );
	$principal_contrats        = [];
	$principal_contrats_ids    = [];
	$min_contrat_date          = - 1;
	$max_contrat_date          = - 1;
	foreach ( $subscribable_contrats as $c ) {
		if ( $c->isPrincipal() ) {
			$principal_contrats[]     = $c;
			$principal_contrats_ids[] = $c->ID;
		}
		if ( $min_contrat_date < 0 ) {
			$min_contrat_date = $c->getDate_debut();
		}
		if ( $max_contrat_date < 0 ) {
			$max_contrat_date = $c->getDate_fin();
		}
		if ( $min_contrat_date > $c->getDate_debut() ) {
			$min_contrat_date = $c->getDate_debut();
		}
		if ( $max_contrat_date < $c->getDate_fin() ) {
			$max_contrat_date = $c->getDate_fin();
		}
	}
	if ( 'mes-contrats' != $tag && empty( $subscribable_contrats ) ) {
		ob_clean();

		if ( amapress_can_access_admin() ) {
			return 'Aucun contrat ne permet l\'inscription en ligne. Veuillez activer l\'inscription en ligne depuis ' . Amapress::makeLink( admin_url( 'edit.php?post_type=amps_contrat_inst' ), 'Edition des contrats' );
		} else {
			if ( ! $use_contrat_term ) {
				return 'Les commandes en ligne sont closes.';
			} else {
				return 'Les inscriptions en ligne sont closes.';
			}
		}
	}

	//TODO better ???
	$adh_period_date = Amapress::add_a_week( $min_contrat_date <= 0 ? amapress_time() : $min_contrat_date, $atts['adhesion_shift_weeks'] );

	$contrats_step_url = add_query_arg( 'step', 'contrats', remove_query_arg( [ 'contrat_id', 'message' ] ) );
	$adhesion_step_url = add_query_arg( 'step', 'adhesion', remove_query_arg( [ 'contrat_id', 'message' ] ) );
	$the_end_url       = add_query_arg( 'step', 'the_end', remove_query_arg( [ 'contrat_id', 'message' ] ) );

	$user_has_contrat = false;
	if ( isset( $_REQUEST['contrat_id'] ) && isset( $_REQUEST['user_id'] ) ) {
		$user_id    = intval( $_REQUEST['user_id'] );
		$contrat_id = intval( $_REQUEST['contrat_id'] );

		Amapress::setFilterForReferent( false );
		$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
		Amapress::setFilterForReferent( true );
		$adhs             = array_filter( $adhs,
			function ( $adh ) use ( $all_subscribable_contrats_ids ) {
				/** @var AmapressAdhesion $adh */
				return in_array( $adh->getContrat_instanceId(), $all_subscribable_contrats_ids );
			} );
		$adhs_contrat_ids = array_map( function ( $a ) {
			/** @var AmapressAdhesion $a */
			return $a->getContrat_instance()->ID;
		}, $adhs );

		if ( empty( $_REQUEST['edit_inscr_id'] ) && in_array( $contrat_id, $adhs_contrat_ids ) ) {
			$amapien = AmapressUser::getBy( $user_id );
			if ( $admin_mode ) {
				ob_clean();

				if ( ! $use_contrat_term ) {
					return $additional_css . '<p>' . esc_html( $amapien->getDisplayName() ) . ' déjà une inscription à cette commande. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Commandes</a></p>';
				} else {
					return $additional_css . '<p>' . esc_html( $amapien->getDisplayName() ) . ' déjà une inscription à ce contrat. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Contrats</a></p>';
				}
			} else {
				ob_clean();

				if ( ! $use_contrat_term ) {
					return $additional_css . '<p>Vous avez déjà passé cette commande. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Commandes</a></p>';
				} else {
					return $additional_css . '<p>Vous avez déjà une inscription à ce contrat. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Contrats</a></p>';
				}
			}
		}

		$user_has_contrat = ! empty( $adhs );
	} else if ( isset( $_REQUEST['user_id'] ) ) {
		$user_id = intval( $_REQUEST['user_id'] );

		Amapress::setFilterForReferent( false );
		$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
		Amapress::setFilterForReferent( true );
		$adhs = array_filter( $adhs,
			function ( $adh ) use ( $all_subscribable_contrats_ids ) {
				/** @var AmapressAdhesion $adh */
				return in_array( $adh->getContrat_instanceId(), $all_subscribable_contrats_ids );
			} );

		$user_has_contrat = ! empty( $adhs );
	}

	if ( defined( 'AMAPRESS_DEMO_MODE' ) && isset( $_REQUEST['user_id'] ) ) {
		$user_id = intval( $_REQUEST['user_id'] );
		$amapien = AmapressUser::getBy( $user_id );
		if ( $amapien ) {
			global $amapress_send_mail_to;
			$amapress_send_mail_to = $amapien->getEmail();
		}
	}

	if ( Amapress::toBool( $atts['check_principal'] ) && ! $disable_principal && ! $admin_mode && empty( $principal_contrats ) ) {
		if ( amapress_can_access_admin() ) {
			ob_clean();

			return 'Aucun contrat principal. Veuillez définir un contrat principal depuis ' . Amapress::makeLink( admin_url( 'edit.php?post_type=amps_contrat_inst' ), 'Edition des contrats' );
		} else if ( 'mes-contrats' != $tag && ! $user_has_contrat ) {
			ob_clean();

			if ( ! $use_contrat_term ) {
				return 'Les commandes en ligne sont closes.';
			} else {
				return 'Les inscriptions en ligne sont closes.';
			}
		}
	}

	if ( is_admin() ) {
		$start_step_url = admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription' );
		if ( isset( $_REQUEST['user_id'] ) ) {
			$start_step_url = add_query_arg(
				[
					'user_id'   => $_REQUEST['user_id'],
					'assistant' => 1,
				], $start_step_url
			);
		}
	} else {
		$start_step_url = add_query_arg( 'step', 'email',
			remove_query_arg( [
				'contrat_id',
				'message'
			] ) );
	}

	$invalid_access_message = '<p>Accès invalide : veuillez repartir de la <a href="' . esc_attr( $start_step_url ) . '">première étape</a></p>';

	$edit_inscription = null;
	if ( ! empty( $_REQUEST['edit_inscr_id'] ) ) {
		Amapress::setFilterForReferent( false );
		$edit_inscription = AmapressAdhesion::getBy( intval( $_REQUEST['edit_inscr_id'] ) );
		Amapress::setFilterForReferent( true );

		if ( ! $edit_inscription ) {
			wp_die( $invalid_access_message );
		}
		if ( ! $edit_inscription->canSelfEdit() ) {
			ob_clean();

			return 'Cette incription n\'est pas éditable';
		}
		if ( empty( $_REQUEST['user_id'] ) || $edit_inscription->getAdherentId() != intval( $_REQUEST['user_id'] ) ) {
			ob_clean();

			return 'Cette incription n\'est pas à vous';
		}
	}

	if ( ! empty( $_REQUEST['message'] ) ) {
		$message = '';
		switch ( $_REQUEST['message'] ) {
			case 'empty_email':
				$message = 'L\'adresse email saisie est invalide';
				break;
			case 'cannot_create_user':
				$message = 'Impossible de créer votre compte.';
				break;
		}
		echo '<div class="alert alert-danger">' . $message . '</div>';
	}

	if ( 'email' == $step ) {
		?>
        <h2>Bienvenue dans
            l’assistant <?php echo( $use_contrat_term ? 'd’inscription aux contrats producteurs' : 'de commande aux producteurs' ) ?>
            de <?php echo get_bloginfo( 'name' ); ?></h2>
        <h4>Étape 1/8 : Email</h4>
        <form method="post" action="<?php echo esc_attr( add_query_arg( 'step', 'coords' ) ) ?>" id="inscr_email"
              class="amapress_validate">
            <label for="email">Pour démarrer votre inscription à l’AMAP pour la saison
				<?php echo date_i18n( 'F Y', $min_contrat_date ) . ' - ' . date_i18n( 'F Y', $max_contrat_date ) ?>
                , renseignez votre
                adresse mail :</label>
            <input id="email" name="email" type="text" class="email required" placeholder="email"/>
			<?php
			if ( $track_no_renews ) {
				?>
                <div class="amap-preinscr-norenew">
                    <label for="no_renew"><input type="checkbox" id="no_renew" name="no_renew"/> Je ne souhaite pas
                        renouveler.</label>
                    <label for="no_renew_reason">Motif (facultatif):</label>
                    <textarea id="no_renew_reason" name="no_renew_reason"
                              disabled="disabled" placeholder="Motif (facultatif)"></textarea>
                    <script type="text/javascript">
                        jQuery(function ($) {
                            var $no_renew = $('#no_renew');
                            var $no_renew_reason = $('#no_renew_reason');
                            $no_renew.change(function () {
                                $no_renew_reason.prop('disabled', !$no_renew.is(':checked'));
                            });
                            $no_renew_reason.prop('disabled', !$no_renew.is(':checked'));
                        });
                    </script>
                </div>
				<?php
			}
			?>
            <input type="submit" value="Valider" class="btn btn-default btn-assist-inscr"/>
        </form>
		<?php
	} else if ( 'coords' == $step || 'coords_logged' == $step ) {
		if ( 'coords_logged' == $step && amapress_is_user_logged_in() ) {
			$email = wp_get_current_user()->user_email;
		} else {
			if ( empty( $_REQUEST['email'] ) ) {
				wp_die( $invalid_access_message );
			}
			$email = sanitize_email( $_REQUEST['email'] );
		}

		$user = get_user_by( 'email', $email );

		if ( ! Amapress::toBool( $atts['allow_new_mail'] ) && ! $user ) {
			ob_clean();

			return $additional_css . '<p style="font-weight: bold">Les inscriptions avec une nouvelle adresse email ne sont pas autorisées.</p>
<p>Si vous êtes déjà membre de l’AMAP, vous avez certainement utilisé une adresse email différente.</p>
<p><a href="' . $start_step_url . '">Changer d’email</a></p>';
		}

		if ( $user && ! Amapress::toBool( $atts['allow_coadherents_access'] ) ) {
			$amapien = AmapressUser::getBy( $user );
			if ( $amapien->isCoAdherent() ) {
				return $additional_css . '<p style="font-weight: bold">Les inscriptions ne sont pas autorisées pour les co-adhérents.</p>
<p><a href="' . $start_step_url . '">Changer d’email</a></p>';
			}
		}

		if ( $user ) {
			if ( isset( $_REQUEST['no_renew'] ) ) {
				$reason = sanitize_textarea_field( isset( $_REQUEST['no_renew_reason'] ) ? $_REQUEST['no_renew_reason'] : '' );
				update_user_meta( $user->ID, 'amapress_user_no_renew', 1 );
				update_user_meta( $user->ID, 'amapress_user_no_renew_reason', $reason );
				ob_clean();

				$track_no_renews_email = $atts['track_no_renews_email'];
				if ( empty( $track_no_renews_email ) ) {
					$track_no_renews_email = get_option( 'admin_email' );
				}
				if ( ! empty( $track_no_renews_email ) ) {
					$amapien   = AmapressUser::getBy( $user );
					$edit_link = Amapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName() );
					amapress_wp_mail(
						$track_no_renews_email,
						'Préinscription - Non renouvellement - ' . $amapien->getDisplayName(),
						amapress_replace_mail_placeholders(
							wpautop( "Bonjour,\n\nL\'amapien $edit_link ne souhaite pas renouveler. Motif:$reason\n\n%%site_name%%" ), $amapien ),
						'', [], $notify_email
					);
				}

				return $additional_css . wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_norenew_message' ), null ) );
			} else {
				delete_user_meta( $user->ID, 'amapress_user_no_renew' );
				delete_user_meta( $user->ID, 'amapress_user_no_renew_reason' );
			}
		}

		$user_firt_name     = '';
		$user_last_name     = '';
		$user_address       = '';
		$user_mobile_phones = '';
		$user_fix_phones    = '';

		$coadh1_user_firt_name = '';
		$coadh1_user_last_name = '';
		$coadh1_email          = '';
		$coadh1_mobile_phones  = '';
		$coadh1_address        = '';

		$coadh2_user_firt_name = '';
		$coadh2_user_last_name = '';
		$coadh2_email          = '';
		$coadh2_mobile_phones  = '';
		$coadh2_address        = '';

		$coadh3_user_firt_name = '';
		$coadh3_user_last_name = '';
		$coadh3_email          = '';
		$coadh3_mobile_phones  = '';
		$coadh3_address        = '';

		$cofoy1_user_firt_name = '';
		$cofoy1_user_last_name = '';
		$cofoy1_email          = '';
		$cofoy1_mobile_phones  = '';
		$cofoy1_address        = '';

		$cofoy2_user_firt_name = '';
		$cofoy2_user_last_name = '';
		$cofoy2_email          = '';
		$cofoy2_mobile_phones  = '';
		$cofoy2_address        = '';

		$cofoy3_user_firt_name = '';
		$cofoy3_user_last_name = '';
		$cofoy3_email          = '';
		$cofoy3_mobile_phones  = '';
		$cofoy3_address        = '';

		$user_message   = 'Vous êtes nouveau dans l’AMAP, complétez vos coordonnées :';
		$member_message = '<p>Si vous êtes déjà membre de l’AMAP, vous avez certainement utilisé une adresse email différente.</p>
<p><a href="' . $start_step_url . '">Changer d’email</a></p>';

		$edit_names               = Amapress::toBool( $atts['edit_names'] ) || empty( $user );
		$adherents_infos          = '';
		$adherents_custom_message = '';
		$hidaddr                  = false;

		if ( $user ) {
//			if ( is_multisite() ) {
//				if ( ! is_user_member_of_blog( $user->ID ) ) {
//					add_user_to_blog( get_current_blog_id(), $user->ID, 'amapien' );
//				}
//			}
			$amapien = AmapressUser::getBy( $user );

			if ( ! $allow_coadherents_adhesion && $amapien->isCoAdherent() ) {
				$activate_adhesion = false;
			}

			$hidaddr            = $amapien->isHiddenFromTrombi();
			$user_message       = 'Vous êtes déjà membre de l’AMAP, vérifiez vos coordonnées :';
			$user_firt_name     = $user->first_name;
			$user_last_name     = $user->last_name;
			$user_address       = $amapien->getFormattedAdresse();
			$user_mobile_phones = implode( '/', $amapien->getPhoneNumbers( true ) );
			$user_fix_phones    = implode( '/', $amapien->getPhoneNumbers( false ) );
			$member_message     = '';

			if ( $show_adherents_infos ) {
				$adherents_infos = $amapien->getAdherentInfo( $admin_mode );
				if ( $amapien->isPrincipalAdherent() ) {
					$adherents_custom_message = wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_principal_user_message' ), null ) );
				} else if ( $amapien->isCoAdherent() ) {
					$max_coadhs               = 0;
					$max_cofoyers             = 0;
					$adherents_custom_message = wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_coadh_user_message' ), null ) );
				} else {
					$adherents_custom_message = wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_principal_user_message' ), null ) );
					$adherents_custom_message .= wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_coadh_user_message' ), null ) );
				}
			}
//		if ( ! Amapress::toBool( $atts[''] ) ) {

			if ( $amapien->getCoAdherent1() ) {
				$coadh1_user_firt_name = $amapien->getCoAdherent1()->getUser()->first_name;
				$coadh1_user_last_name = $amapien->getCoAdherent1()->getUser()->last_name;
				$coadh1_email          = $amapien->getCoAdherent1()->getUser()->user_email;
				$coadh1_mobile_phones  = implode( '/', $amapien->getCoAdherent1()->getPhoneNumbers() );
				$coadh1_address        = $amapien->getCoAdherent1()->getFormattedAdresse();
			}

			if ( $amapien->getCoAdherent2() ) {
				$coadh2_user_firt_name = $amapien->getCoAdherent2()->getUser()->first_name;
				$coadh2_user_last_name = $amapien->getCoAdherent2()->getUser()->last_name;
				$coadh2_email          = $amapien->getCoAdherent2()->getUser()->user_email;
				$coadh2_mobile_phones  = implode( '/', $amapien->getCoAdherent2()->getPhoneNumbers() );
				$coadh2_address        = $amapien->getCoAdherent2()->getFormattedAdresse();
			}

			if ( $amapien->getCoAdherent3() ) {
				$coadh3_user_firt_name = $amapien->getCoAdherent3()->getUser()->first_name;
				$coadh3_user_last_name = $amapien->getCoAdherent3()->getUser()->last_name;
				$coadh3_email          = $amapien->getCoAdherent3()->getUser()->user_email;
				$coadh3_mobile_phones  = implode( '/', $amapien->getCoAdherent3()->getPhoneNumbers() );
				$coadh3_address        = $amapien->getCoAdherent3()->getFormattedAdresse();
			}

			if ( $amapien->getCoFoyer1() ) {
				$cofoy1_user_firt_name = $amapien->getCoFoyer1()->getUser()->first_name;
				$cofoy1_user_last_name = $amapien->getCoFoyer1()->getUser()->last_name;
				$cofoy1_email          = $amapien->getCoFoyer1()->getUser()->user_email;
				$cofoy1_mobile_phones  = implode( '/', $amapien->getCoFoyer1()->getPhoneNumbers() );
				$cofoy1_address        = $amapien->getCoFoyer1()->getFormattedAdresse();
			}

			if ( $amapien->getCoFoyer2() ) {
				$cofoy2_user_firt_name = $amapien->getCoFoyer2()->getUser()->first_name;
				$cofoy2_user_last_name = $amapien->getCoFoyer2()->getUser()->last_name;
				$cofoy2_email          = $amapien->getCoFoyer2()->getUser()->user_email;
				$cofoy2_mobile_phones  = implode( '/', $amapien->getCoFoyer2()->getPhoneNumbers() );
				$cofoy2_address        = $amapien->getCoFoyer2()->getFormattedAdresse();
			}

			if ( $amapien->getCoFoyer3() ) {
				$cofoy3_user_firt_name = $amapien->getCoFoyer3()->getUser()->first_name;
				$cofoy3_user_last_name = $amapien->getCoFoyer3()->getUser()->last_name;
				$cofoy3_email          = $amapien->getCoFoyer3()->getUser()->user_email;
				$cofoy3_mobile_phones  = implode( '/', $amapien->getCoFoyer3()->getPhoneNumbers() );
				$cofoy3_address        = $amapien->getCoFoyer3()->getFormattedAdresse();
			}
		}

		$adh_pmt = $user ? AmapressAdhesion_paiement::getForUser( $user->ID, $adh_period_date, false ) : null;
		?>
        <h4>
			<?php
			if ( 'mes-contrats' == $tag ) {
				echo 'Coordonnées, co-adhérents et membres du foyer';
			} else {
				echo 'Étape 2/8 : Coordonnées, co-adhérents et membres du foyer';
			}
			?>
        </h4>
        <p><?php echo $adherents_infos; ?></p>
		<?php echo $adherents_custom_message; ?>
        <p><?php echo $user_message; ?></p>
        <form method="post" id="inscr_coords" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_coords' ) ) ?>">
            <input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>"/>
            <input type="hidden" name="notify_email" value="<?php echo esc_attr( $notify_email ); ?>"/>
            <input type="hidden" name="send_welcome" value="<?php echo esc_attr( $atts['send_welcome'] ); ?>"/>
            <input type="hidden" name="inscr_assistant" value="validate_coords"/>
			<?php if ( 'mes-contrats' == $tag ) { ?>
                <input type="hidden" name="coords_next_step" value="contrats"/>
			<?php } elseif ( $activate_agreement ) { ?>
                <input type="hidden" name="coords_next_step" value="agreement"/>
			<?php } elseif ( $activate_adhesion && empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step" value="adhesion"/>
			<?php } ?>
            <input type="hidden" name="inscr_key" value="<?php echo esc_attr( $key ); ?>"/>
            <table style="min-width: 50%">
                <tr>
                    <th style="text-align: left; width: auto"><label for="email">Email : </label>
                    </th>
                    <td><span style="width: 100%"><?php echo esc_html( $email ) ?></span></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="last_name">Nom* : </label></th>
                    <td><input style="width: 100%" type="text" id="last_name" name="last_name"
                               class="required single_name"
                               value="<?php echo esc_attr( $user_last_name ) ?>" <?php disabled( ! $edit_names ) ?>/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="first_name">Prénom* : </label></th>
                    <td><input style="width: 100%" type="text" id="first_name" name="first_name"
                               class="required single_name"
                               value="<?php echo esc_attr( $user_firt_name ) ?>" <?php disabled( ! $edit_names ) ?>/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="telm">Téléphone
                            mobile<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? '*' : '' ) ?> : </label>
                    </th>
                    <td><input style="width: 100%" type="text" id="telm" name="telm"
                               class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required' : '' ) ?>"
                               value="<?php echo esc_attr( $user_mobile_phones ) ?>"/></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="telf">Téléphone fixe : </label></th>
                    <td><input style="width: 100%" type="text" id="telf" name="telf" class=""
                               value="<?php echo esc_attr( $user_fix_phones ) ?>"/></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="address">Adresse : </label></th>
                    <td><textarea style="width: 100%" rows="4" id="address" name="address"
                                  class=""><?php echo esc_textarea( $user_address ); ?></textarea></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"></th>
                    <td>
                        <label for="hidaddr"><input type="checkbox" name="hidaddr" <?php checked( $hidaddr ); ?>
                                                    id="hidaddr"/> Ne pas apparaître sur le trombinoscope
                        </label>
                    </td>
                </tr>
            </table>
            <div>
				<?php echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_adhesion_coadh_message' ), null ) ); ?>
            </div>
			<?php if ( $max_cofoyers >= 1 ) { ?>
                <table style="min-width: 50%">
                    <tr>
                        <th colspan="2">Membre du foyer 1 / Conjoint
                        </th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy1_email">Son email
                                : </label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                   type="email"
                                                                                                   id="cofoy1_email"
                                                                                                   name="cofoy1_email"
                                                                                                   class="email <?php echo( ! empty( $cofoy1_email ) ? 'required' : '' ); ?>"
                                                                                                   value="<?php echo esc_attr( $cofoy1_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy1_last_name">Son nom : </label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy1_last_name"
                                                                                                   name="cofoy1_last_name"
                                                                                                   class="required_if_not_empty single_name"
                                                                                                   data-if-id="cofoy1_email"
                                                                                                   value="<?php echo esc_attr( $cofoy1_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy1_first_name">Son prénom : </label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy1_first_name"
                                                                                                   name="cofoy1_first_name"
                                                                                                   class="required_if_not_empty single_name"
                                                                                                   data-if-id="cofoy1_email"
                                                                                                   value="<?php echo esc_attr( $cofoy1_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy1_tels">Téléphone(s) : </label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy1_tels"
                                                                                                   name="cofoy1_tels"
                                                                                                   class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                                   data-if-id="cofoy1_email"
                                                                                                   value="<?php echo esc_attr( $cofoy1_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_cofoys_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label for="cofoy1_address">Adresse : </label>
                            </th>
                            <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                          rows="4"
                                                                                                          id="cofoy1_address"
                                                                                                          name="cofoy1_address"
                                                                                                          class=""><?php echo esc_textarea( $cofoy1_address ); ?></textarea>
                            </td>
                        </tr>
					<?php } ?>
					<?php if ( $allow_remove_cofoys && ! empty( $cofoy1_email ) ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"></th>
                            <td>
                                <label for="cofoy1_remove"><input type="checkbox" name="cofoy1_remove"
                                                                  id="cofoy1_remove"/> Je ne suis plus coadhérent
                                    avec <?php echo esc_html( "$cofoy1_user_firt_name $cofoy1_user_last_name" ) ?>
                                </label>
                            </td>
                        </tr>
					<?php } ?>
                </table>
			<?php } ?>
			<?php if ( $max_cofoyers >= 2 ) { ?>
                <table style="min-width: 50%">
                    <tr>
                        <th colspan="2">Membre du foyer 2
                        </th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy2_email">Son email
                                : </label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                   type="email"
                                                                                                   id="cofoy2_email"
                                                                                                   name="cofoy2_email"
                                                                                                   class="email <?php echo( ! empty( $cofoy2_email ) ? 'required' : '' ); ?>"
                                                                                                   value="<?php echo esc_attr( $cofoy2_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy2_last_name">Son nom : </label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy2_last_name"
                                                                                                   name="cofoy2_last_name"
                                                                                                   class="required_if_not_empty single_name"
                                                                                                   data-if-id="cofoy2_email"
                                                                                                   value="<?php echo esc_attr( $cofoy2_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy2_first_name">Son prénom : </label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy2_first_name"
                                                                                                   name="cofoy2_first_name"
                                                                                                   class="required_if_not_empty single_name"
                                                                                                   data-if-id="cofoy2_email"
                                                                                                   value="<?php echo esc_attr( $cofoy2_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy2_tels">Téléphone(s) : </label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy2_tels"
                                                                                                   name="cofoy2_tels"
                                                                                                   value="<?php echo esc_attr( $cofoy2_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_cofoys_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label for="cofoy2_address">Adresse : </label>
                            </th>
                            <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                          rows="4"
                                                                                                          id="cofoy2_address"
                                                                                                          name="cofoy2_address"
                                                                                                          class=""><?php echo esc_textarea( $cofoy2_address ); ?></textarea>
                            </td>
                        </tr>
					<?php } ?>
					<?php if ( $allow_remove_cofoys && ! empty( $cofoy2_email ) ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"></th>
                            <td>
                                <label for="cofoy2_remove"><input type="checkbox" name="cofoy2_remove"
                                                                  id="cofoy2_remove"/> Je ne suis plus coadhérent
                                    avec <?php echo esc_html( "$cofoy2_user_firt_name $cofoy2_user_last_name" ) ?>
                                </label>
                            </td>
                        </tr>
					<?php } ?>
                </table>
			<?php } ?>
			<?php if ( $max_cofoyers >= 3 ) { ?>
                <table style="min-width: 50%">
                    <tr>
                        <th colspan="2">Membre du foyer 3</th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy3_email">Son email
                                : </label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                   type="email"
                                                                                                   id="cofoy3_email"
                                                                                                   name="cofoy3_email"
                                                                                                   class="email <?php echo( ! empty( $cofoy3_email ) ? 'required' : '' ); ?>"
                                                                                                   value="<?php echo esc_attr( $cofoy3_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy3_last_name">Son nom : </label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy3_last_name"
                                                                                                   name="cofoy3_last_name"
                                                                                                   class="required_if_not_empty single_name"
                                                                                                   data-if-id="cofoy3_email"
                                                                                                   value="<?php echo esc_attr( $cofoy3_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy3_first_name">Son prénom : </label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy3_first_name"
                                                                                                   name="cofoy3_first_name"
                                                                                                   class="required_if_not_empty single_name"
                                                                                                   data-if-id="cofoy3_email"
                                                                                                   value="<?php echo esc_attr( $cofoy3_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy3_tels">Téléphone(s) : </label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy3_tels"
                                                                                                   name="cofoy3_tels"
                                                                                                   value="<?php echo esc_attr( $cofoy3_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_cofoys_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label for="cofoy3_address">Adresse : </label>
                            </th>
                            <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                          rows="4"
                                                                                                          id="cofoy3_address"
                                                                                                          name="cofoy3_address"
                                                                                                          class=""><?php echo esc_textarea( $cofoy3_address ); ?></textarea>
                            </td>
                        </tr>
					<?php } ?>
					<?php if ( $allow_remove_cofoys && ! empty( $cofoy3_email ) ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"></th>
                            <td>
                                <label for="cofoy3_remove"><input type="checkbox" name="cofoy3_remove"
                                                                  id="cofoy3_remove"/> Je ne suis plus coadhérent
                                    avec <?php echo esc_html( "$cofoy3_user_firt_name $cofoy3_user_last_name" ) ?>
                                </label>
                            </td>
                        </tr>
					<?php } ?>
                </table>
			<?php } ?>

			<?php if ( $max_coadhs >= 1 ) { ?>
                <table style="min-width: 50%">
                    <tr>
                        <th colspan="2">Co adhérent 1 <em>(si vous payez les contrats à plusieurs)</em></th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh1_email">Son email
                                : </label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="email"
                                                                                  id="coadh1_email" name="coadh1_email"
                                                                                  class="email"
                                                                                  value="<?php echo esc_attr( $coadh1_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh1_last_name">Son nom : </label></th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh1_last_name"
                                                                                  name="coadh1_last_name"
                                                                                  class="required_if_not_empty single_name"
                                                                                  data-if-id="coadh1_email"
                                                                                  value="<?php echo esc_attr( $coadh1_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh1_first_name">Son prénom : </label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh1_first_name"
                                                                                  name="coadh1_first_name"
                                                                                  class="required_if_not_empty single_name"
                                                                                  data-if-id="coadh1_email"
                                                                                  value="<?php echo esc_attr( $coadh1_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh1_tels">Téléphone(s) : </label></th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh1_tels" name="coadh1_tels"
                                                                                  class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                  data-if-id="coadh1_email"
                                                                                  value="<?php echo esc_attr( $coadh1_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_coadherents_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label for="coadh1_address">Adresse : </label>
                            </th>
                            <td><textarea style="width: 100%" rows="4" id="coadh1_address" name="coadh1_address"
                                          class=""><?php echo esc_textarea( $coadh1_address ); ?></textarea></td>
                        </tr>
					<?php } ?>
					<?php if ( $allow_remove_coadhs && ! empty( $coadh1_email ) ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"></th>
                            <td>
                                <label for="coadh1_remove"><input type="checkbox" name="coadh1_remove"
                                                                  id="coadh1_remove"/> Je ne suis plus coadhérent
                                    avec <?php echo esc_html( "$coadh1_user_firt_name $coadh1_user_last_name" ) ?>
                                </label>
                            </td>
                        </tr>
					<?php } ?>
                </table>
			<?php } ?>
			<?php if ( $max_coadhs >= 2 ) { ?>
                <table style="min-width: 50%">
                    <tr>
                        <th colspan="2">Co adhérent 2 <em>(si vous payez les contrats à plusieurs)</em></th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh2_email">Son email
                                : </label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="email"
                                                                                  id="coadh2_email" name="coadh2_email"
                                                                                  class="email"
                                                                                  value="<?php echo esc_attr( $coadh2_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh2_last_name">Son nom : </label></th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh2_last_name"
                                                                                  name="coadh2_last_name"
                                                                                  class="required_if_not_empty single_name"
                                                                                  data-if-id="coadh2_email"
                                                                                  value="<?php echo esc_attr( $coadh2_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh2_first_name">Son prénom : </label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh2_first_name"
                                                                                  name="coadh2_first_name"
                                                                                  class="required_if_not_empty single_name"
                                                                                  data-if-id="coadh2_email"
                                                                                  value="<?php echo esc_attr( $coadh2_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh2_tels">Téléphone(s) : </label></th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh2_tels" name="coadh2_tels"
                                                                                  class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                  data-if-id="coadh2_email"
                                                                                  value="<?php echo esc_attr( $coadh2_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_coadherents_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label for="coadh2_address">Adresse : </label>
                            </th>
                            <td><textarea style="width: 100%" rows="4" id="coadh2_address" name="coadh2_address"
                                          class=""><?php echo esc_textarea( $coadh2_address ); ?></textarea></td>
                        </tr>
					<?php } ?>
					<?php if ( $allow_remove_coadhs && ! empty( $coadh2_email ) ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"></th>
                            <td>
                                <label for="coadh2_remove"><input type="checkbox" name="coadh2_remove"
                                                                  id="coadh2_remove"/> Je ne suis plus coadhérent
                                    avec <?php echo esc_html( "$coadh2_user_firt_name $coadh2_user_last_name" ) ?>
                                </label>
                            </td>
                        </tr>
					<?php } ?>
                </table>
			<?php } ?>
			<?php if ( $max_coadhs >= 3 ) { ?>
                <table style="min-width: 50%">
                    <tr>
                        <th colspan="2">Co adhérent 3 <em>(si vous payez les contrats à plusieurs)</em></th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh3_email">Son email
                                : </label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="email"
                                                                                  id="coadh3_email" name="coadh3_email"
                                                                                  class="email"
                                                                                  value="<?php echo esc_attr( $coadh3_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh3_last_name">Son nom : </label></th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh3_last_name"
                                                                                  name="coadh3_last_name"
                                                                                  class="required_if_not_empty single_name"
                                                                                  data-if-id="coadh3_email"
                                                                                  value="<?php echo esc_attr( $coadh3_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh3_first_name">Son prénom : </label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh3_first_name"
                                                                                  name="coadh3_first_name"
                                                                                  class="required_if_not_empty single_name"
                                                                                  data-if-id="coadh3_email"
                                                                                  value="<?php echo esc_attr( $coadh3_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="coadh3_tels">Téléphone(s) : </label></th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh3_tels" name="coadh3_tels"
                                                                                  class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                  data-if-id="coadh3_email"
                                                                                  value="<?php echo esc_attr( $coadh3_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_coadherents_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label for="coadh3_address">Adresse : </label>
                            </th>
                            <td><textarea style="width: 100%" rows="4" id="coadh3_address" name="coadh3_address"
                                          class=""><?php echo esc_textarea( $coadh3_address ); ?></textarea></td>
                        </tr>
					<?php } ?>
					<?php if ( $allow_remove_coadhs && ! empty( $coadh3_email ) ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"></th>
                            <td>
                                <label for="coadh3_remove"><input type="checkbox" name="coadh3_remove"
                                                                  id="coadh3_remove"/> Je ne suis plus coadhérent
                                    avec <?php echo esc_html( "$coadh3_user_firt_name $coadh3_user_last_name" ) ?>
                                </label>
                            </td>
                        </tr>
					<?php } ?>
                </table>
			<?php } ?>
            <p style="color:red">* Champ obligatoire</p>
			<?php echo $member_message; ?>
			<?php if ( ! $user ) {
				$quest1 = wp_unslash( Amapress::getOption( 'online_new_user_quest1' ) );
				if ( ! Amapress::isHtmlEmpty( $quest1 ) ) {
					?>
                    <label for="online_new_user_quest1"><?php echo $quest1 ?></label>
                    <textarea style="width: 100%" rows="4" id="online_new_user_quest1"
                              name="online_new_user_quest1"></textarea>
					<?php
				}
				$quest2 = wp_unslash( Amapress::getOption( 'online_new_user_quest2' ) );
				if ( ! Amapress::isHtmlEmpty( $quest2 ) ) {
					?>
                    <label for="online_new_user_quest2"><?php echo $quest2 ?></label>
                    <textarea style="width: 100%" rows="4" id="online_new_user_quest2"
                              name="online_new_user_quest2"></textarea>
					<?php
				}
			}
			?>
            <input style="min-width: 50%" type="submit" class="btn btn-default btn-assist-inscr" value="Valider"/>
        </form>
		<?php
	} else if ( 'agreement' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );

		$amapien = AmapressUser::getBy( $user_id );
		if ( ! $allow_coadherents_adhesion && $amapien->isCoAdherent() ) {
			$activate_adhesion = false;
		}

		$adh_pmt = $user_id ? AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false ) : null;
		?>
        <h4><?php echo wp_unslash( esc_html( Amapress::getOption( 'online_subscription_agreement_step_name' ) ) ) ?></h4>
        <form method="post" id="agreement" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_agreement' ) ) ?>">
            <input type="hidden" name="inscr_assistant" value="validate_agreement"/>
            <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
			<?php if ( $activate_adhesion && empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step" value="adhesion"/>
			<?php } ?>
            <div class="amap-agreement">
				<?php echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_subscription_agreement' ), null ) ); ?>
            </div>
            <p class="accept-agreement">
                <label for="accept_agreement"><input type="checkbox" name="accept" id="accept_agreement"
                                                     class="required"
                                                     data-msg="Veuillez cocher la case ci-dessous"/> <?php echo esc_html( wp_unslash( Amapress::getOption( 'online_subscription_agreement_step_checkbox' ) ) ); ?>
                </label>
            </p>
            <p>
                <input style="min-width: 50%" type="submit" class="btn btn-default btn-assist-inscr" value="Valider"/>
            </p>
        </form>
		<?php
	} else if ( 'adhesion' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false );
		if ( $adh_paiement ) {
			ob_clean();

			return ( $additional_css . '<p>Vous avez déjà une adhésion</p>' );
		}

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( 'Aucune période d\'adhésion n\'est configurée.' );
		}

		if ( $for_logged ) {
			echo '<h4>Étape Adhésion (obligatoire)</h4>';
		} else {
			echo '<h4>Étape 3/8 : Adhésion (obligatoire)</h4>';
		}
		echo $adh_period->getOnlineDescription();

		$taxes            = get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => 'amps_paiement_category',
			'hide_empty' => false,
		) );
		$ret              = '';
		$ret              .= '<form method="post" id="inscr_adhesion" class="amapress_validate" action="' . esc_attr( add_query_arg( 'step', 'save_adhesion' ) ) . '">';
		$ret              .= '<input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '"/>';
		$amap_term        = Amapress::getOption( 'adhesion_amap_term' );
		$reseau_amap_term = Amapress::getOption( 'adhesion_reseau_amap_term' );
		$ret              .= '<table style="max-width: 70%">';
		foreach ( $taxes as $tax ) {
			$tax_amount = 0;
			if ( $tax->term_id == $amap_term ) {
				$tax_amount = $adh_period->getMontantAmap();
			}
			if ( $tax->term_id == $reseau_amap_term ) {
				$tax_amount = $adh_period->getMontantReseau();
			}
			if ( ( $tax->term_id == $amap_term || $tax->term_id == $reseau_amap_term ) && abs( $tax_amount ) < 0.001 ) {
				$ret .= '<input type="hidden" id="amapress_pmt_amount-' . $tax->term_id . '" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="amapress_pmt_cat_amount" value="' . $tax_amount . '" />';
			} else {
				$ret .= '<tr>';
				$ret .= '<th style="text-align: left; width: auto">
<label for="amapress_pmt_amount-' . $tax->term_id . '">' . esc_html( $tax->name ) . '</label>
' . ( ! empty( $tax->description ) ? '<p style="font-style: italic; font-weight: normal">' . $tax->description . '</p>' : '' ) . '
</th>';
				if ( $tax->term_id == $amap_term || $tax->term_id == $reseau_amap_term ) {
					$ret .= '<td style="min-width: 8em"><input type="hidden" id="amapress_pmt_amount-' . $tax->term_id . '" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="amapress_pmt_cat_amount" value="' . $tax_amount . '" />' . $tax_amount . '&nbsp;€</td>';
				} else {
					$ret .= '<td style="min-width: 8em"><input type="number" id="amapress_pmt_amount-' . $tax->term_id . '" style="width: 80%" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="price required amapress_pmt_cat_amount" value="' . $tax_amount . '" />&nbsp;€</td>';
				}
				$ret .= '</tr>';
			}
		}
		$ret .= '</table>';
		$ret .= '<p>Montant total : <span id="amapress_adhesion_paiement_amount"></span> €</p>';
		$ret .= '<p><label for="amapress_adhesion_paiement_numero">Numéro de chèque :</label><input type="text" id="amapress_adhesion_paiement_numero" class="' . ( $paiements_info_required ? 'required' : '' ) . '" name="amapress_adhesion_paiement_numero"/></p>';
		$ret .= '<p><label for="amapress_adhesion_paiement_banque">Banque :</label><input type="text" id="amapress_adhesion_paiement_banque" class="' . ( $paiements_info_required ? 'required' : '' ) . '" name="amapress_adhesion_paiement_banque"/></p>';
		$ret .= '<input type="submit" class="btn btn-default btn-assist-adh" value="Valider"/>';
		$ret .= '</form>';

		echo $ret;

	} else if ( 'save_adhesion' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );

		if ( empty( $_REQUEST['amapress_pmt_amounts'] ) ) {
			wp_die( $invalid_access_message );
		}

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( 'Aucune période d\'adhésion n\'est configurée.' );
		}

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date );

		$terms   = array();
		$amounts = array();
		foreach ( $_POST['amapress_pmt_amounts'] as $tax_id => $amount ) {
			if ( $amount > 0 ) {
				$terms[]            = intval( $tax_id );
				$amounts[ $tax_id ] = floatval( $amount );
			}
		}
		$total_amount = 0;
		foreach ( $amounts as $k => $v ) {
			$total_amount += $v;
		}
		update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_repartition', $amounts );
		update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_amount', $total_amount );
		if ( isset( $_REQUEST['amapress_adhesion_paiement_banque'] ) ) {
			update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_banque', $_REQUEST['amapress_adhesion_paiement_banque'] );
		}
		if ( isset( $_REQUEST['amapress_adhesion_paiement_numero'] ) ) {
			update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_numero', $_REQUEST['amapress_adhesion_paiement_numero'] );
		}
		wp_set_post_terms( $adh_paiement->ID, $terms, 'amps_paiement_category' );

		amapress_compute_post_slug_and_title( $adh_paiement->getPost() );

		$amapien      = AmapressUser::getBy( $user_id );
		$mail_subject = Amapress::getOption( 'online_adhesion_confirm-mail-subject' );
		$mail_content = Amapress::getOption( 'online_adhesion_confirm-mail-content' );

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $amapien, $adh_paiement );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, $amapien, $adh_paiement );

		$tresoriers = [];
		foreach ( get_users( "role=tresorier" ) as $tresorier ) {
			$user_obj   = AmapressUser::getBy( $tresorier );
			$tresoriers = array_merge( $tresoriers, $user_obj->getAllEmails() );
		}

		$attachments = [];
		try {
			$doc_file = $adh_paiement->generateBulletinDoc( false );
		} catch ( Exception $ex ) {
			wp_die( 'Impossible de générer le bulletin d\'adhésion. Merci de réessayer en appuyant sur F5' );
		}
		if ( ! empty( $doc_file ) ) {
			$attachments[] = $doc_file;
			$mail_content  = preg_replace( '/\[sans_bulletin\].+?\[\/sans_bulletin\]/', '', $mail_content );
			$mail_content  = preg_replace( '/\[\/?avec_bulletin\]/', '', $mail_content );
		} else {
			$mail_content = preg_replace( '/\[avec_bulletin\].+?\[\/avec_bulletin\]/', '', $mail_content );
			$mail_content = preg_replace( '/\[\/?sans_bulletin\]/', '', $mail_content );
		}

		if ( Amapress::toBool( $atts['send_adhesion_confirm'] ) ) {
			amapress_wp_mail( $amapien->getAllEmails(), $mail_subject, $mail_content, [
				'Reply-To: ' . implode( ',', $tresoriers )
			], $attachments );
		}

		if ( Amapress::toBool( $atts['send_tresoriers'] ) ) {
			amapress_wp_mail(
				$tresoriers,
				'Nouvelle adhésion ' . $amapien->getDisplayName(),
				wpautop( "Bonjour,\nUne nouvelle adhésion est en attente : " . Amapress::makeLink( $adh_paiement->getAdminEditLink(), $amapien->getDisplayName() ) . "\n\n" . get_bloginfo( 'name' ) ),
				'', [], $notify_email
			);
		}

		echo '<h4>Validation du Bulletin d\'adhésion</h4>';

		$online_subscription_greating_adhesion = wp_unslash( Amapress::getOption( 'online_subscription_greating_adhesion' ) );

		if ( $adh_paiement->getPeriod()->getWordModelId() ) {
			$print_bulletin                        = Amapress::makeButtonLink(
				add_query_arg( [
					'inscr_assistant' => 'generate_bulletin',
					'adh_id'          => $adh_paiement->ID,
					'inscr_key'       => $key
				] ),
				$adhesion_print_button_text, true, true, 'btn btn-default'
			);
			$online_subscription_greating_adhesion = str_replace( '%%print_button%%', $print_bulletin, $online_subscription_greating_adhesion );
		} else {
			$online_subscription_greating_adhesion = str_replace( '%%print_button%%', '', $online_subscription_greating_adhesion );
		}
		echo amapress_replace_mail_placeholders( $online_subscription_greating_adhesion, null );

		if ( ! $use_contrat_term ) {
			echo '<p>Vous pouvez maintenant passer commandes :<br/>';
		} else {
			echo '<p>Vous pouvez maintenant vous inscrire aux contrats de l\'AMAP :<br/>';
		}
		echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
	} else if ( 'contrats' == $step ) {
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message );
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}
		$has_principal_contrat = $user_has_contrat;

		Amapress::setFilterForReferent( false );
		$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
		Amapress::setFilterForReferent( true );
//		$adhs = array_filter( $adhs,
//			function ( $adh ) use ( $all_subscribable_contrats_ids ) {
//				/** @var AmapressAdhesion $adh */
//				return in_array( $adh->getContrat_instanceId(), $all_subscribable_contrats_ids );
//			} );
		if ( Amapress::toBool( $atts['check_principal'] ) && ! $disable_principal ) {
			foreach ( $adhs as $adh ) {
				if ( $adh->getContrat_instance()->isPrincipal() ) {
					$has_principal_contrat = true;
				}
			}
		} else {
			$has_principal_contrat = true;
		}
		usort( $adhs, function ( $a, $b ) {
			return strcmp( $a->getTitle(), $b->getTitle() );
		} );
		$amapien = AmapressUser::getBy( $user_id );
		if ( ! $admin_mode ) {
			if ( ! $for_logged ) {
				if ( ! $use_contrat_term ) {
					echo '<h4>Étape 4/8 : les commandes</h4>';
				} else {
					echo '<h4>Étape 4/8 : les contrats</h4>';
				}
			}
		} else {
			if ( ! $use_contrat_term ) {
				echo '<h4>Les commandes de ' . esc_html( $amapien->getDisplayName() ) . '</h4>';
			} else {
				echo '<h4>Les contrats de ' . esc_html( $amapien->getDisplayName() ) . '</h4>';
			}
		}
		if ( ! $admin_mode ) {
			if ( $allow_coadherents_adhesion || ! $amapien->isCoAdherent() ) {
				$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
				if ( empty( $adh_period ) ) {
					ob_clean();

					return ( 'Aucune période d\'adhésion n\'est configurée.' );
				}

				$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false );

				if ( empty( $adh_paiement ) ) {
					if ( 'mes-contrats' == $tag ) {
						echo '<p><strong>Vous n\'avez pas d\'adhésion à l\'AMAP sur la période ' . esc_html( $adh_period->getTitle() ) . '</strong></p>';
					} else {
						echo '<p><strong>Pour vous engager dans l’AMAP et pouvoir s\'inscrire aux contrats disponibles, vous devez adhérer à notre Association.</strong><br/>
<form method="get" action="' . esc_attr( $adhesion_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="' . ( $activate_agreement ? 'agreement' : 'adhesion' ) . '" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Adhérer" />
</form></p>';
						if ( $activate_adhesion ) {
							return ob_get_clean();
						}
					}
				} else {
					$print_bulletin = '';
					if ( $adh_paiement->getPeriod()->getWordModelId() ) {
						$print_bulletin = Amapress::makeButtonLink(
							add_query_arg( [
								'inscr_assistant' => 'generate_bulletin',
								'adh_id'          => $adh_paiement->ID,
								'inscr_key'       => $key
							] ),
							$adhesion_print_button_text, true, true, 'btn btn-default'
						);
					}
					echo '<p>Votre adhésion à l\'AMAP est valable jusqu\'au ' . date_i18n( 'd/m/Y', $adh_period->getDate_fin() ) . '.<br />
' . $print_bulletin . '</p>';
				}
			}

			echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_contrats_step_message' ), null ) );
		}

		if ( $show_adherents_infos ) {
			$adherents_infos = $amapien->getAdherentInfo( $admin_mode );
			if ( $amapien->isPrincipalAdherent() ) {
				$adherents_custom_message = wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_principal_user_message' ), null ) );
			} else if ( $amapien->isCoAdherent() ) {
				$adherents_custom_message = wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_coadh_user_message' ), null ) );
			} else {
				$adherents_custom_message = wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_principal_user_message' ), null ) );
				$adherents_custom_message .= wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_coadh_user_message' ), null ) );
			}
			?>
            <p><?php echo $adherents_infos; ?></p>
			<?php echo $adherents_custom_message; ?>
			<?php
		}

		if ( Amapress::toBool( $atts['show_calendar_delivs'] ) ) {
			echo '<p>';
			echo Amapress::makeButtonLink( add_query_arg( [
				'step' => 'calendar_delivs',
			] ), 'Calendrier des livraisons', true, true, 'btn btn-default' );
			echo '</p>';
		}

		$allow_inscriptions         = Amapress::toBool( $atts['allow_inscriptions'] );
		$display_remaining_contrats = true;
		if ( ! $admin_mode && ! $allow_inscriptions ) {
			$display_remaining_contrats = false;
		}
		if ( ! $admin_mode && ! $has_principal_contrat && $allow_inscriptions ) {
			$display_remaining_contrats = false;
			if ( ! $allow_coadherents_inscription && $amapien->isCoAdherent() ) {
				if ( ! $use_contrat_term ) {
					echo '<p><strong>Les commandes doivent être faites par l\'adhérent principal.</strong></p>';
				} else {
					echo '<p><strong>L\'inscription aux contrats doit être faite par l\'adhérent principal.</strong></p>';
				}
				$display_remaining_contrats = false;
			} else {
				if ( count( $principal_contrats ) == 1 ) {
					?>
                    <p>Pour accéder à tous nos contrats en ligne,
                        vous devez d’abord vous inscrire au contrat
                        “<strong><?php echo esc_html( $principal_contrats[0]->getTitle() ); ?></strong>”
                        (<?php echo $principal_contrats[0]->getModel()->linkToPermalinkBlank( 'plus d\'infos' ); ?>)
                    </p>
                    <p><?php
						$inscription_url = add_query_arg( [
							'step'       => 'inscr_contrat_date_lieu',
							'contrat_id' => $principal_contrats[0]->ID
						] );
						echo '<form action="' . esc_attr( $inscription_url ) . '" method="get">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu"/>
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $principal_contrats[0]->ID . '"/>
<input type="submit" class="btn btn-default btn-assist-inscr" value="Confirmer"/>
</form>';
						?>
                    </p>
					<?php
				} else {
					?>
                    <p>Pour accéder à tous nos contrats en ligne, vous devez d’abord vous
                        inscrire à l’un des contrats suivants :</p>
					<?php
					foreach ( $principal_contrats as $principal_contrat ) {
						?>
                        <p>
                            “<strong><?php echo esc_html( $principal_contrat->getTitle() ); ?></strong>”
                            (<?php echo $principal_contrat->getModel()->linkToPermalinkBlank( 'plus d\'infos' ); ?>)
                        </p>
                        <p><?php
							$inscription_url = add_query_arg( [
								'step'       => 'inscr_contrat_date_lieu',
								'contrat_id' => $principal_contrat->ID
							] );
							echo '<form action="' . esc_attr( $inscription_url ) . '" method="get">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu"/>
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $principal_contrat->ID . '"/>
<input type="submit" class="btn btn-default btn-assist-inscr" value="Confirmer"/>
</form>';
							?>
                        </p>
						<?php
					}
				}
			}
		} else if ( ! empty( $adhs ) ) {
			if ( $show_due_amounts || $show_delivery_details ) {
				echo '<p>';
				if ( $show_due_amounts ) {
					echo Amapress::makeButtonLink( add_query_arg( [
						'step' => 'details_all_paiements',
					] ), 'Récapitulatif des sommes dues', true, true, 'btn btn-default' );
				}
				if ( $show_delivery_details ) {
					echo Amapress::makeButtonLink( add_query_arg( [
						'step' => 'details_all_delivs',
					] ), 'Récapitulatif des livraisons (par date)', true, true, 'btn btn-default' );
					echo Amapress::makeButtonLink( add_query_arg( [
						'step'    => 'details_all_delivs',
						'by_prod' => '1',
					] ), 'Récapitulatif des livraisons (par producteur)', true, true, 'btn btn-default' );
				}
				echo '</p>';
			}
			if ( $amapien->isPrincipalAdherent() && Amapress::toBool( $atts['show_modify_coords'] ) ) {
				echo '<p>';
				echo Amapress::makeButtonLink( add_query_arg( [
					'step' => amapress_is_user_logged_in() ? 'coords_logged' : 'coords',
				] ), 'Coordonnées, co-adhérents, membres du foyer', true, false, 'btn btn-default' );
				echo '</p>';
			}
			$editable_adhs = array_filter( $adhs, function ( $adh ) {
				/** @var AmapressAdhesion $adh */
				return $adh->canSelfEdit();
			} );
			if ( $admin_mode || ( $show_editable_inscriptions && ! empty( $editable_adhs ) ) || $show_current_inscriptions ) {
				if ( ! $admin_mode ) {
					if ( $show_current_inscriptions ) {
						if ( ! $use_contrat_term ) {
							echo '<p>Vos commandes :</p>';
						} else {
							echo '<p>Vos contrats :</p>';
						}
					} else {
						if ( ! $use_contrat_term ) {
							echo '<p>Vos commandes éditables :</p>';
						} else {
							echo '<p>Vos contrats éditables :</p>';
						}
					}
				} else {
					if ( ! $use_contrat_term ) {
						echo '<p>Ses commandes :</p>';
					} else {
						echo '<p>Ses contrats :</p>';
					}
				}
				echo '<ul style="list-style-type: disc">';
				foreach ( ( $admin_mode || $show_current_inscriptions ? $adhs : $editable_adhs ) as $adh ) {
					$print_contrat = '';
					if ( ! empty( $adh->getContrat_instance()->getContratModelDocFileName() ) ) {
						$print_contrat = Amapress::makeButtonLink(
							add_query_arg( [
								'inscr_assistant' => 'generate_contrat',
								'inscr_id'        => $adh->ID,
								'inscr_key'       => $key
							] ),
							$contrat_print_button_text, true, true, 'btn btn-default'
						);
					}
					if ( $admin_mode ) {
						echo '<li style="margin-left: 35px">' . esc_html( $adh->getTitle() ) .
						     ( current_user_can( 'edit_post', $adh->ID ) ?
							     ' (' . Amapress::makeLink( $adh->getAdminEditLink(), 'Editer', true, true ) . ')<br/>' . $print_contrat . '</li>' : '' );
					} else {
						$rattrapage = $adh->getProperty( 'dates_rattrapages' );
						if ( ! Amapress::toBool( $atts['show_details_button'] ) ) {
							$contrat_info = ( $adh->getContrat_instance()->isPanierVariable() ?
									'Vous avez composé votre panier "' . $adh->getContrat_instance()->getModelTitle() . '" (' . Amapress::makeLink( add_query_arg( [
										'step'       => 'details',
										'contrat_id' => $adh->ID
									] ), 'Détails', true, true ) . ') pour ' :
									'Vous avez choisi le(s) panier(s) "' . $adh->getProperty( 'quantites' ) . '" pour ' )
							                . $adh->getProperty( 'nb_distributions' ) . ' distribution(s) pour un montant total de ' . $adh->getProperty( 'total' ) . ' € (' . $adh->getProperty( 'option_paiements' ) . ')'
							                . '<br/>' . $adh->getProperty( 'nb_dates' ) . ' dates distributions : ' . $adh->getProperty( 'dates_distribution_par_mois' )
							                . ( ! empty( $rattrapage ) ? '<br/>Dates de rattrages : ' . $rattrapage : '' );
						} else {
							$contrat_info = '';
							if ( ! $adh->getContrat_instance()->isPanierVariable() ) {
								$contrat_info .= 'Vous avez choisi le(s) panier(s) "' . $adh->getProperty( 'quantites' ) . '. ';
							}
							$contrat_info .= Amapress::makeButtonLink( add_query_arg( [
								'step'       => 'details',
								'contrat_id' => $adh->ID
							] ), 'Détails', true, true );
						}

						if ( Amapress::toBool( $atts['contact_referents'] ) ) {
							$refs_emails  = $adh->getContrat_instance()->getAllReferentsEmails( $adh->getLieuId() );
							$contrat_info .= '<br/>' . Amapress::makeLink( 'mailto:' . urlencode( implode( ',', $refs_emails ) ) . '?subject=' . urlencode( 'Mon inscription ' . $adh->getTitle() ), 'Contacter les référents' );
						}
						$coadherents_info = $adh->getAdherent()->getCoAdherentsList( true, false, false, $adh->getContrat_instanceId() );
						if ( empty( $coadherents_info ) ) {
							$coadherents_info = 'aucun';
						}
						$coadherents_info = '<br /><strong>Co-adhérents</strong> : ' . $coadherents_info;
						$edit_contrat     = '';
						if ( $adh->canSelfEdit() ) {
							$inscription_url = add_query_arg( [
								'step'       => 'inscr_contrat_date_lieu',
								'contrat_id' => $adh->getContrat_instanceId()
							] );
							$edit_contrat    = '<br/>
<form style="display: inline-block; margin-left: 5px" method="get" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $adh->getContrat_instanceId() . '" />
<input type="hidden" name="edit_inscr_id" value="' . $adh->ID . '" />
<input type="submit" value="Modifier" class="btn btn-default btn-assist-inscr" />
</form>';
							$edit_contrat    .= '<form method="get" style="display: inline-block; margin-left: 5px" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="details" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $adh->ID . '" />
<input type="hidden" name="cancel_inscr_id" value="' . $adh->ID . '" />
<input type="submit" value="Annuler" class="btn btn-default btn-assist-inscr" />
</form>';
						}
						echo '<li style="margin-left: 35px"><strong>' . esc_html( $adh->getTitle() ) . '</strong>' . $coadherents_info . '<br/><em style="font-size: 0.9em">' . $contrat_info . '</em>' . $edit_contrat . '<br/>' . $print_contrat . '</li>';
					}
				}
				echo '</ul>';
			}
			if ( $allow_inscriptions ) {
				if ( ! $admin_mode && ! $allow_coadherents_inscription && $amapien->isCoAdherent() ) {
					if ( ! $use_contrat_term ) {
						echo '<p><strong>Les commandes doivent être passées par l\'adhérent principal.</strong></p>';
					} else {
						echo '<p><strong>L\'inscription aux contrats doit être faite par l\'adhérent principal.</strong></p>';
					}
					$display_remaining_contrats = false;
				} else {
					if ( ! empty( $user_subscribable_contrats ) ) {
						if ( ! $admin_mode ) {
							if ( ! $use_contrat_term ) {
								echo '<p>Quelle commande souhaitez vous passer ?</p>';
							} else {
								echo '<p>A quel contrat souhaitez-vous vous inscrire ?</p>';
							}
						} else {
							if ( ! $use_contrat_term ) {
								echo '<p>Quelle commande souhaitez vous passer pour cet amapien ?</p>';
							} else {
								echo '<p>A quel contrat souhaitez-vous vous inscrire cet amapien ?</p>';
							}
						}
					}
				}
			}
		} else {
			if ( ! $admin_mode && ! $allow_coadherents_inscription && $amapien->isCoAdherent() && $allow_inscriptions ) {
				if ( ! $use_contrat_term ) {
					echo '<p><strong>Les commandes doivent être passées par l\'adhérent principal.</strong></p>';
				} else {
					echo '<p><strong>L\'inscription aux contrats doit être faite par l\'adhérent principal.</strong></p>';
				}
				$display_remaining_contrats = false;
			} else {
				if ( ! $admin_mode ) {
					if ( ! $use_contrat_term ) {
						echo '<p>Vous n\'avez pas encore de passé de commandes</p>';
					} else {
						echo '<p>Vous n\'avez pas encore de contrats</p>';
					}
					if ( $allow_inscriptions ) {
						if ( ! $use_contrat_term ) {
							echo '<p>Vous pouvez vous passer les commandes ci-dessous :</p>';
						} else {
							echo '<p>Vous pouvez vous inscrire aux contrats ci-dessous :</p>';
						}
					}
				} else {
					echo '<p>Il/Elle n\'a pas encore de contrats</p>';
					echo '<p>Vous pouvez l\'inscrire aux autres contrats ci-dessous :</p>';
				}
			}
		}

		if ( $display_remaining_contrats ) {
			$adhs_contrat_ids           = array_map( function ( $a ) {
				/** @var AmapressAdhesion $a */
				return $a->getContrat_instanceId();
			}, $adhs );
			$user_subscribable_contrats = array_filter( $subscribable_contrats, function ( $c ) use ( $adhs_contrat_ids ) {
				return ! in_array( $c->ID, $adhs_contrat_ids );
			} );
			if ( ! $admin_mode && ! $has_principal_contrat ) {
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $principal_contrats_ids ) {
					return in_array( $c->ID, $principal_contrats_ids );
				} );
			}
			if ( Amapress::toBool( $atts['filter_multi_contrat'] ) ) {
				$adhs_contrat_ids           = array_map( function ( $a ) {
					/** @var AmapressAdhesion $a */
					return $a->getContrat_instance()->getModelId();
				}, $adhs );
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $adhs_contrat_ids ) {
					/** @var AmapressContrat_instance $c */
					return ! in_array( $c->getModelId(), $adhs_contrat_ids );
				} );
			}
			if ( $admin_mode ) {
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $principal_contrats_ids ) {
					return current_user_can( 'edit_post', $c->ID );
				} );
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $contrat ) use ( $atts ) {
					/** @var AmapressContrat_instance $contrat */
					$before_close_hours = 0;
					if ( 0 == $before_close_hours ) {
						$before_close_hours = intval( $atts['before_close_hours'] );
					}
					$dates = array_values( $contrat->getListe_dates() );
					$dates = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours ) {
						$real_date = $contrat->getRealDateForDistribution( $d );

						return ( Amapress::start_of_day( $real_date ) - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time();
					} );

					return ! empty( $dates );
				} );
			} else {
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $contrat ) use ( $atts ) {
					/** @var AmapressContrat_instance $contrat */
					$before_close_hours = 0;
					if ( 0 == $before_close_hours ) {
						$before_close_hours = intval( $atts['before_close_hours'] );
					}
					$dates                = array_values( $contrat->getListe_dates() );
					$dates_before_cloture = array_filter( $dates, function ( $d ) use ( $contrat ) {
						$real_date = $contrat->getRealDateForDistribution( $d );

						return Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() );
					} );
					$dates                = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours, $dates_before_cloture ) {
						$real_date = $contrat->getRealDateForDistribution( $d );

						return ( Amapress::start_of_day( $real_date ) - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time()
						       && ( empty( $dates_before_cloture ) || Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() ) );
					} );

					return ! empty( $dates );
				} );
			}
			if ( ! empty( $user_subscribable_contrats ) ) {
				if ( ! $use_contrat_term ) {
					echo '<p>Commandes disponibles :</p>';
				} else {
					echo '<p>Contrats disponibles :</p>';
				}
				echo '<ul style="list-style-type: disc">';
				foreach ( $user_subscribable_contrats as $contrat ) {
					/** @var AmapressContrat_instance $contrat */
					$inscription_url = add_query_arg( [
						'step'       => 'inscr_contrat_date_lieu',
						'contrat_id' => $contrat->ID
					] );
					if ( $admin_mode ) {
						if ( $contrat->isFull() ) {
							echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ', contrat <strong>COMPLET</strong> :<br/>' . Amapress::makeLink( $contrat->getAdminEditLink(), 'Editer ses quota', true, true ) . ' (nb maximum d\'amapiens et/ou nb maximum d\'amapiens par panier)</li>';
						} else {
							echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ' (' . Amapress::makeLink( $contrat->getAdminEditLink(), 'Editer', true, true ) . ') : <br/><a class="button button-secondary" href="' . esc_attr( $inscription_url ) . '">Ajouter une inscription</a></li>';
						}
					} else {
						echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ' (' . $contrat->getModel()->linkToPermalinkBlank( 'plus d\'infos' ) . ') : 
<br/>
<form method="get" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $contrat->ID . '" />
<input type="submit" value="M\'inscrire" class="btn btn-default btn-assist-inscr" />
</form></li>';
					}
				}
				echo '</ul>';
			} else {
				if ( ! $admin_mode ) {
					if ( empty( $subscribable_contrats ) ) {
						if ( ! $use_contrat_term ) {
							echo '<p>Les commandes sont closes.</p>';
						} else {
							echo '<p>Les inscriptions sont closes.</p>';
						}
					} else {
						if ( ! $use_contrat_term ) {
							echo '<p>Vous êtes déjà passé toutes les commandes disponibles.</p>';
						} else {
							echo '<p>Vous êtes déjà inscrit à tous les contrats.</p>';
						}
					}
				} else {
					echo '<p>Il/Elle est inscrit à tous les contrats que vous gérez.</p>';
				}
			}
		}

		if ( ! $admin_mode && $has_principal_contrat && 'mes-contrats' != $tag ) {
			echo '<p>J\'ai fini :<br/>
<form method="get" action="' . esc_attr( $the_end_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="the_end" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="submit" value="Terminer" class="btn btn-default btn-assist-end" />
</form></p>';
		}
	} else if ( 'inscr_contrat_date_lieu' == $step ) {
		$next_step_url = add_query_arg( 'step', 'inscr_contrat_engage' );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		$contrat    = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message );
		}

		$lieux = $contrat->getLieux();
		if ( empty( $lieux ) ) {
			ob_clean();

			return ( $additional_css . '<p><strong>Attention</strong> : le contrat ' . Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle() ) . ' n\'a aucun lieu de livraison associé. Veuillez corriger ce contrat avant de poursuivre.</p>' );
		}
		if ( $for_logged ) {
			echo '<h4>Étape 1/4 : Date et lieu - ' . esc_html( $contrat->getTitle() ) . '</h4>';
		} else {
			echo '<h4>Étape 5/8 : Date et lieu - ' . esc_html( $contrat->getTitle() ) . '</h4>';
		}
		?>

        <form action="<?php echo $next_step_url; ?>" method="post" class="amapress_validate">
			<?php
			$before_close_hours = 0;
			if ( 0 == $before_close_hours ) {
				$before_close_hours = intval( $atts['before_close_hours'] );
			}
			if ( $admin_mode ) {
				$before_close_hours = - 24;
			}
			$dates              = array_values( $contrat->getListe_dates() );
			$first_contrat_date = $dates[0];
			//			$last_contrat_date = $dates[count($dates) - 1];
			if ( ! $admin_mode ) {
				$dates_before_cloture = array_filter( $dates, function ( $d ) use ( $contrat ) {
					$real_date = $contrat->getRealDateForDistribution( $d );

					return Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() );
				} );
				$allow_all_dates      = Amapress::toBool( $atts['allow_inscription_all_dates'] );
				$dates                = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours, $dates_before_cloture, $allow_all_dates ) {
					$real_date = $contrat->getRealDateForDistribution( $d );

					return ( Amapress::start_of_day( $real_date ) - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time()
					       && ( $allow_all_dates || empty( $dates_before_cloture ) || Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() ) );
				} );
			} else {
				$dates = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours ) {
					$real_date = $contrat->getRealDateForDistribution( $d );

					return Amapress::end_of_week( $real_date ) > amapress_time();
				} );
			}
			$dates            = array_values( $dates );
			$first_avail_date = $dates[0];
			$is_started       = $first_avail_date != $first_contrat_date;
			if ( ! $admin_mode ) {
				if ( ! $use_contrat_term ) {
					echo '<p>Les commandes en ligne sont ouvertes du “' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) . '” au “' . date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) . '”, hors de cette période, je contacte l\'AMAP pour préciser ma demande : “<a href="mailto:' . esc_attr( $atts['email'] ) . '">' . esc_html( $atts['email'] ) . '</a>”</p>';
				} else {
					echo '<p>Les inscriptions en ligne sont ouvertes du “' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) . '” au “' . date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) . '”, hors de cette période, je contacte l\'AMAP pour préciser ma demande : “<a href="mailto:' . esc_attr( $atts['email'] ) . '">' . esc_html( $atts['email'] ) . '</a>”</p>';
				}
			}
			echo '<p><strong>Date</strong></p>';
			if ( ! $is_started && ! $admin_mode ) {
				echo '<input type="hidden" name="start_date" value="' . $first_avail_date . '" />';
				$first_date_dist = $contrat->getRealDateForDistribution( $first_contrat_date );
				$last_date_dist  = $contrat->getDate_fin();
				if ( 1 == count( $contrat->getListe_dates() ) ) {
					echo '<p>Je m’inscris pour la distribution ponctuelle du ' . date_i18n( 'l d F Y', $first_date_dist ) . '</p>';
				} else {
					if ( ! $use_contrat_term ) {
						echo '<p>Je passe commande pour la période du ' . date_i18n( 'l d F Y', $first_date_dist ) . ' au ' . date_i18n( 'l d F Y', $last_date_dist ) . '
 (' . count( $contrat->getListe_dates() ) . ' dates de distributions)</p>';

					} else {
						echo '<p>Je m’inscris pour la saison complète : du ' . date_i18n( 'l d F Y', $first_date_dist ) . ' au ' . date_i18n( 'l d F Y', $last_date_dist ) . '
 (' . count( $contrat->getListe_dates() ) . ' dates de distributions)</p>';
					}
				}
			} else {
				?>
                <p><?php
					if ( ! $admin_mode ) {
						if ( ! $use_contrat_term ) {
							echo 'La première date de livraison est passée, je récupère mon panier à la prochaine distribution ou je choisis une date ultérieure :';
						} else {
							echo 'Je m\'inscris en cours de saison, je récupère mon panier à la prochaine distribution ou je choisis une date ultérieure :';
						}
					} else {
						echo 'A partir de quel date doit-il commencer son contrat :';
					}
					?>
                    <br/>
                    <select name="start_date" id="start_date" class="required">
						<?php
						foreach ( $dates as $date ) {
							$real_date = $contrat->getRealDateForDistribution( $date );
							$val_date  = date_i18n( 'd/m/Y', $real_date );
							if ( Amapress::start_of_day( $date ) != Amapress::start_of_day( $real_date ) ) {
								$val_date .= ' initialement le ' . date_i18n( 'd/m/Y', $date );
							}
							if ( $date == $first_avail_date ) {
								if ( $is_started ) {
									$val_date = "Prochaine distribution ($val_date)";
								} else {
									$val_date = "Première distribution ($val_date)";
								}
							}
							$selected = selected( $edit_inscription && Amapress::start_of_day( $date ) == Amapress::start_of_day( $edit_inscription->getDate_debut() ), true, false );
							echo '<option ' . $selected . ' value="' . esc_attr( $date ) . '">' . esc_html( $val_date ) . '</option>';
						}
						?>
                    </select>
                </p>
				<?php
			}

			echo '<p><strong>Lieu</strong></p>';
			if ( count( $lieux ) > 1 ) {
				if ( ! $admin_mode ) {
					echo '<p style="margin-bottom: 0">Je récupérerai mon panier à :</p>';
				} else {
					echo '<p style="margin-bottom: 0">Veuillez chosir son lieu de distribution :</p>';
				}
				foreach ( $lieux as $lieu ) {
					$lieu_id    = $lieu->ID;
					$lieu_title = $lieu->linkToPermalinkBlank( esc_html( $lieu->getLieuTitle() ) ) . ' (' . esc_html( $lieu->getFormattedAdresse() ) . ')';
					$checked    = checked( $edit_inscription && $lieu_id == $edit_inscription->getLieuId(), true, false );
					echo "<p style='margin-top: 0;margin-bottom: 0'><input id='lieu-$lieu_id' name='lieu_id' $checked value='$lieu_id' type='radio' class='required' /><label for='lieu-$lieu_id'>$lieu_title</label></p>";
				}
			} else {
				$lieu_title = $lieux[0]->linkToPermalinkBlank( esc_html( $lieux[0]->getLieuTitle() ) ) . ' (' . esc_html( $lieux[0]->getFormattedAdresse() ) . ')';
				echo '<p>Je récupérerai mon panier à ' . $lieu_title . '</p>';
				echo '<input name="lieu_id" value="' . $lieux[0]->ID . '" type="hidden" />';
			}

			if ( Amapress::hasPartialCoAdhesion() ) {
				echo '<p><strong>Co-adhérents</strong></p>';

				if ( $for_logged && amapress_is_user_logged_in() ) {
					$user_id = wp_get_current_user()->ID;
				} else {
					if ( empty( $_REQUEST['user_id'] ) ) {
						wp_die( $invalid_access_message );
					}
					$user_id = intval( $_REQUEST['user_id'] );
				}
				$amapien = AmapressUser::getBy( $user_id );
				$coadhs  = $amapien->getAllDirectlyLinkedCoUsers( true, false );
				if ( empty( $coadhs ) ) {
					echo '<p>Vous n\'avez aucun co-adhérent déclaré</p>';
				} else {
					if ( $edit_inscription ) {
						$inscr_coadhs = [
							$edit_inscription->getAdherentId(),
							$edit_inscription->getAdherent2Id(),
							$edit_inscription->getAdherent3Id(),
							$edit_inscription->getAdherent4Id()
						];
					} else {
						$inscr_coadhs = [];
					}
					echo '<p>';
					/** @var AmapressUser $coadh */
					foreach ( $coadhs as $coadh ) {
						echo '<label for="coadh-' . $coadh->ID . '"><input id="coadh-' . $coadh->ID . '" ' .
						     checked( in_array( $coadh->ID, $inscr_coadhs ), true, false ) . ' type="checkbox" name="coadhs[]" value="' . $coadh->ID . '" /> ' . esc_html( $coadh->getDisplayName() ) . '</label>';
					}
					echo '</p>';
				}
			}


			//			foreach ( $dates as $date ) {
			//				echo '<option value="' . esc_attr( $date ) . '">' . esc_html( date_i18n( 'd/m/Y', $date ) ) . '</option>';
			//			}
			?>
            <br/>
            <input type="submit" value="Valider" class="btn btn-default btn-assist-inscr"/>
        </form>
		<?php
	} else if ( 'details_all_paiements' == $step ) {
		if ( ! $show_due_amounts ) {
			wp_die( $invalid_access_message );
		}
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message );
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}

		echo amapress_get_details_all_paiements( $user_id, $ignore_renouv_delta );
	} else if ( 'details_all_delivs' == $step ) {
		if ( ! $show_delivery_details ) {
			wp_die( $invalid_access_message );
		}
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message );
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}

		$by_prod = isset( $_GET['by_prod'] );

		echo amapress_get_details_all_deliveries( $user_id, $ignore_renouv_delta, $by_prod );
	} else if ( 'calendar_delivs' == $step ) {
		echo amapress_get_contrats_calendar( $subscribable_contrats );
	} else if ( 'details' == $step ) {
		if ( empty( $_GET['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message );
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}

		$adh = AmapressAdhesion::getBy( intval( $_GET['contrat_id'] ) );
		if ( $adh->getAdherentId() != intval( $user_id ) ) {
			wp_die( $invalid_access_message );
		}
		if ( ! empty( $_GET['cancel_inscr_id'] ) ) {
			if ( intval( $_GET['cancel_inscr_id'] ) != $adh->ID ) {
				wp_die( $invalid_access_message );
			}
			if ( ! $adh->canSelfEdit() ) {
				wp_die( $invalid_access_message );
			}
			if ( isset( $_GET['confirm'] ) ) {
				if ( ! wp_delete_post( $adh->ID, true ) ) {
					wp_die( $invalid_access_message );
				}
				if ( ! $use_contrat_term ) {
					echo '<p>Votre commande ' . esc_html( $adh->getTitle() ) . ' a été annulée avec succès.</p>';
				} else {
					echo '<p>Votre inscription ' . esc_html( $adh->getTitle() ) . ' a été annulée avec succès.</p>';
				}
				if ( ! $use_contrat_term ) {
					echo '<p>' . Amapress::makeLink( $contrats_step_url, 'Retourner à la liste des commandes' ) . '</p>';
				} else {
					echo '<p>' . Amapress::makeLink( $contrats_step_url, 'Retourner à la liste des contrats' ) . '</p>';
				}

				return ob_get_clean();
			} else {
				if ( ! $use_contrat_term ) {
					echo '<p>Vous avez demandé l\'annulation de la commande suivante :<br/>';
				} else {
					echo '<p>Vous avez demandé l\'annulation de l\'inscription suivante :<br/>';
				}
				echo Amapress::makeLink( add_query_arg( 'confirm', 'T' ), 'Confirmer l\'annulation' ) . '<br/>
' . Amapress::makeLink( $contrats_step_url, 'Retourner à la liste des contrats' ) . '</p>';
			}
		}
		$print_contrat = '';
		if ( ! empty( $adh->getContrat_instance()->getContratModelDocFileName() ) ) {
			$print_contrat = Amapress::makeButtonLink(
				add_query_arg( [
					'inscr_assistant' => 'generate_contrat',
					'inscr_id'        => $adh->ID,
					'inscr_key'       => $key
				] ),
				$contrat_print_button_text, true, true, 'btn btn-default'
			);
		}
		$rattrapage   = $adh->getProperty( 'dates_rattrapages' );
		$contrat_info = ( $adh->getContrat_instance()->isPanierVariable() ?
				'Vous avez composé votre panier "' . $adh->getContrat_instance()->getModelTitle() . '" pour ' :
				'Vous avez choisi le(s) panier(s) "' . $adh->getProperty( 'quantites' ) . '" pour ' )
		                . $adh->getProperty( 'nb_distributions' ) . ' distribution(s) pour un montant total de ' . $adh->getProperty( 'total' ) . ' €'
		                . '<h3>Distributions</h3><p>' . $adh->getProperty( 'nb_dates' ) . ' dates distributions : ' . $adh->getProperty( 'dates_distribution_par_mois' )
		                . ( ! empty( $rattrapage ) ? '<br/>Dates de rattrages : ' . $rattrapage : '' ) . '</p>';
		$contrat_info .= '<h3>Lieu</h3><p>' . Amapress::makeLink( $adh->getLieu()->getPermalink(), $adh->getProperty( 'lieu' ), true, true ) . '</p>';
		$contrat_info .= '<h3>Détails</h3><p>' . $adh->getProperty( 'quantites_prix' ) . '</p><p>' . $print_contrat . '</p>';
		$contrat_info .= '<h3>Options de paiements</h3><p>' . $adh->getProperty( 'option_paiements' ) . '</p><p>' . $adh->getProperty( 'paiements_mention' ) . '</p>';
		$refs_emails  = $adh->getContrat_instance()->getAllReferentsEmails( $adh->getLieuId() );
		$contrat_info .= '<h3>Référents</h3>';
		$contrat_info .= '<p>' . $adh->getProperty( 'referents' ) . '</p>';
		$contrat_info .= '<p>' . Amapress::makeLink( 'mailto:' . urlencode( implode( ',', $refs_emails ) ) . '?subject=' . urlencode( 'Mon inscription ' . $adh->getTitle() ), 'Contacter les référents' ) . '</p>';
		echo '<h4>' . esc_html( $adh->getTitle() ) . '</h4><p>' . $contrat_info . '</p>';
	} else if ( 'inscr_contrat_engage' == $step ) {
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['lieu_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$lieu_id = intval( $_REQUEST['lieu_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message );
		}
		$start_date = intval( $_REQUEST['start_date'] );
		$coadhs     = isset( $_REQUEST['coadhs'] ) ? $_REQUEST['coadhs'] : '';
		if ( is_array( $coadhs ) ) {
			$coadhs = implode( ',', $coadhs );
		}

		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message );
		}

		$next_step_url = add_query_arg( [
			'step'       => 'inscr_contrat_paiements',
			'start_date' => $start_date,
			'coadhs'     => $coadhs,
			'lieu_id'    => $lieu_id
		] );

		$dates      = $contrat->getListe_dates();
		$dates      = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return $d >= $start_date;
		} );
		$rattrapage = $contrat->getFormattedRattrapages( $dates );

		if ( $for_logged ) {
			echo '<h4>Étape 2/4 : Panier - ' . esc_html( $contrat->getTitle() ) . '</h4>';
		} else {
			echo '<h4>Étape 6/8 : Panier - ' . esc_html( $contrat->getTitle() ) . '</h4>';
		}
		$min_total = $contrat->getMinEngagement();

		$grouped_dates = from( $dates )->groupBy( function ( $d ) {
			return date_i18n( 'F Y', $d );
		} );

		$grouped_dates_array = [];
		foreach ( $grouped_dates as $k => $v ) {
			$grouped_dates_array[] = $k . ' : ' . ( count( $v ) > 1 ? 'les ' : 'le ' ) . implode( ', ', array_map(
					function ( $d ) {
						return date_i18n( 'd', $d );
					}, $v
				) );
		}

		$dates_factors = 0;
		foreach ( $dates as $d ) {
			$dates_factors += $contrat->getDateFactor( $d );
		}

		$rattrapage_renvoi = '';
		if ( ! empty( $rattrapage ) ) {
			$rattrapage_renvoi = '<a href="#dist_rattrapages">*</a>';
		}
//		echo $contrat->getOnlineContrat();
		if ( count( $contrat->getListe_dates() ) == count( $dates ) ) {
			if ( ! $use_contrat_term ) {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">Cette commande comporte “<strong>' . $dates_factors . '</strong>” distributions (étalées sur “<strong>' . count( $dates ) . '</strong>” dates' . $rattrapage_renvoi . ') :</p>';
			} else {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">Ce contrat comporte “<strong>' . $dates_factors . '</strong>” distributions (étalées sur “<strong>' . count( $dates ) . '</strong>” dates' . $rattrapage_renvoi . ') :</p>';
			}
		} else {
			if ( ! $use_contrat_term ) {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">Il reste “<strong>' . $dates_factors . '</strong>” distributions (étalées sur “<strong>' . count( $dates ) . '</strong>” dates' . $rattrapage_renvoi . ') avant la fin de cette commande :</p>';
			} else {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">Il reste “<strong>' . $dates_factors . '</strong>” distributions (étalées sur “<strong>' . count( $dates ) . '</strong>” dates' . $rattrapage_renvoi . ') avant la fin de ce contrat :</p>';
			}
		}
		echo '<ul style="list-style-type: disc; padding-top: 0; margin-top: 0">';
		foreach ( $grouped_dates_array as $entry ) {
			echo '<li style="margin-left: 35px">' . esc_html( $entry ) . '</li>';
		}
		echo '</ul>';

		$reports = [];
		foreach ( $dates as $d ) {
			$real_date = $contrat->getRealDateForDistribution( $d );
			if ( Amapress::start_of_day( $real_date ) != Amapress::start_of_day( $d ) ) {
				$reports[] = 'livraison du ' . date_i18n( 'd/m/Y', $d ) . ' reportée au ' . date_i18n( 'd/m/Y', $real_date );
			}
		}
		if ( ! empty( $reports ) ) {
			echo '<p>Report(s) de livraison : ' . implode( ', ', $reports ) . '</p>';
		}

		if ( ! empty( $rattrapage ) ) {
			echo '<p><a id="dist_rattrapages">*</a>Distribution(s) de rattrapage : ' . implode( ', ', $rattrapage ) . '</p>';
		}

		if ( $contrat->isQuantiteMultiple() || $contrat->isPanierVariable() ) {
			echo '<p>Composez votre panier :</p>';
		} else {
			echo '<p>Choisissez la quantité ou la taille de votre panier :</p>';
		}
		$multiple_rules = [];
		$quants_full    = [];
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		if ( $contrat->isPanierVariable() ) {
			$columns = array(
				array(
					'title' => 'Produit',
					'data'  => 'produit',
				),
				array(
					'title' => 'Prix',
					'data'  => 'prix_unitaire',
				),
			);
			foreach ( $dates as $date ) {
				$columns[] = array(
					'title' => date_i18n( 'd/m/y', $date ),
					'data'  => 'd-' . $date,
				);
			}

			$has_groups = false;
			$grp_data   = array();
			foreach ( AmapressContrats::get_contrat_quantites( $contrat->ID ) as $quant ) {
				$multiple       = $quant->getGroupMultiple();
				$grp_class_name = '';
				$has_group      = preg_match( '/^\s*\[([^\]]+)\]/', $quant->getTitle(), $matches );
				if ( $has_group ) {
					if ( $multiple > 1 && isset( $matches[1] ) ) {
						$grp_class_name = 'quant_grp_' . sanitize_html_class( $matches[1] );
						$grp_name       = $matches[1];
						if ( ! isset( $multiple_rules[ $grp_class_name ] ) ) {
							$multiple_rules[ $grp_class_name ] = [
								'class'    => $grp_class_name,
								'display'  => $grp_name,
								'multiple' => $multiple,
							];
						}
						$grp_name = esc_attr( $grp_name );
					}
					$has_groups = true;
				}
				$row     = array(
					'produit'       => '<span class="panier-mod-produit-label">' . esc_html( $quant->getTitle() ) . ( ! empty( $quant->getDescription() ) ? '<br/><em>' . esc_html( $quant->getDescription() ) . '</em>' : '' ) . '</span>',
					'prix_unitaire' => esc_html( $quant->getPrix_unitaireDisplay() ),
				);
				$options = $quant->getQuantiteOptions();
				if ( ! isset( $options['0'] ) ) {
					$options = [ '0' => '0' ] + $options;
				}
				$price_unit = esc_attr( $quant->getPrix_unitaire() );
				foreach ( $dates as $date ) {
					$ed = '';
					$ed .= "<select style='max-width: none;min-width: 0' data-grp-class='$grp_class_name' data-price='0' data-price-unit='$price_unit' name='panier_vars[$date][{$quant->ID}]' id='panier_vars-$date-{$quant->ID}' class='quant-var $grp_class_name'>";
					$ed .= tf_parse_select_options( $options,
						$edit_inscription
							? $edit_inscription->getContrat_quantite_factor( $quant->ID, $date )
							: null,
						false );
					$ed .= '</select>';
					$ed .= '<a title="Recopier la même quantité sur les dates suivantes" href="#" class="quant-var-recopier">&gt;</a>';
					if ( ! $quant->isInDistributionDates( $date ) ) {
						$ed = '<span class="contrat_panier_vars-na">NA</span>';
					}
					$row[ 'd-' . $date ] = $ed;
				}
				if ( ! isset( $grp_data[ $grp_class_name ] ) ) {
					$grp_data[ $grp_class_name ] = [];
				}
				$grp_data[ $grp_class_name ][] = $row;
			}

			$data = [];
			foreach ( $grp_data as $k => $grp ) {
				$data = array_merge( $data, array_values( $grp ) );
			}

			foreach ( $multiple_rules as $grp_class_name => $grp_conf ) {
				if ( $grp_conf['multiple'] <= 1 ) {
					continue;
				}
				echo '
<script type="text/javascript">
    //<![CDATA[
    jQuery(function ($) {
        jQuery.validator.addMethod(
            "' . $grp_class_name . '",
            function (value, element, params) {
                var parent = $(element).closest("form");
                if(!$(element).data(\'reval\')) {
			        var fields = $(".' . $grp_class_name . '", parent);
			        fields.data(\'reval\', true).valid();
			        fields.data(\'reval\', false);
			    }
                var sumOfVals = 0;
                jQuery(parent).find(".quant-var.' . $grp_class_name . '").each(function () {
                    sumOfVals = sumOfVals + parseInt(jQuery(this).val());
                });
                if (0 === (sumOfVals % ' . $grp_conf['multiple'] . ')) return true;
                return false;
            },
            "La quantité pour ' . esc_js( $grp_conf['display'] ) . ' doit être multiple de ' . $grp_conf['multiple'] . '<br/>"
        );
    });
    //]]>
</script>';
			}

			echo '<style type="text/css">.quant-var-recopier{text-shadow: none !important; text-decoration: none !important;}.panier-mod-produit-label{display: inline-block;white-space: normal;word-wrap: break-word; max-width: ' . $atts['max_produit_label_width'] . ';}</style>';

			$js_options = array(
				'bSort'        => false,
				'paging'       => false,
				'searching'    => true,
				'bAutoWidth'   => true,
				'responsive'   => false,
				'init_as_html' => true,
				'scrollX'      => true,
				'scrollY'      => intval( $atts['paniers_modulables_editor_height'] ),
				'fixedColumns' => array( 'leftColumns' => 2 ),
			);
			if ( $has_groups ) {
				$js_options['raw_js_options'] = 'rowGroup: {
                    dataSrc: function ( row ) {
                        var grp = row[0].match(/\[([^\]]+)\]/);
                        if (grp && grp.length > 1)
                            return grp[1];
                        return "Autres";
                    }
                }';
				//Datatables rowGroup does not support fixedColumns, so for now, disable it
				unset( $js_options['fixedColumns'] );
			}

			echo amapress_get_datatable( 'quant-commandes', $columns, $data, $js_options );
			echo '<p>* Cliquez sur la case pour faire apparaître le choix de quantités</p>';
		} else {
			$contrat_quants = AmapressContrats::get_contrat_quantites( $contrat->ID );
			foreach ( $contrat_quants as $quantite ) {
				if ( $contrat->isFull( $quantite->ID ) ) {
					if ( ! $edit_inscription || ! in_array( $quantite->ID, $edit_inscription->getContrat_quantites_IDs() ) ) {
						$quants_full[] = $quantite->getTitle();
						continue;
					}
				}

				$dates_factors = 0;
				foreach ( $dates as $d ) {
					$dates_factors += $contrat->getDateFactor( $d, $quantite->ID );
				}

				if ( abs( $dates_factors ) < 0.001 ) {
					continue;
				}

				$quant_var_editor   = '';
				$id_quant           = 'quant' . $quantite->ID;
				$id_factor          = 'factor' . $quantite->ID;
				$id_price           = 'price' . $quantite->ID;
				$price              = $dates_factors * $quantite->getPrix_unitaire();
				$price_compute_text = esc_html( $dates_factors ) . ' x ' . esc_html( $quantite->getPrix_unitaireDisplay() );
				if ( $contrat->isQuantiteVariable() ) {
					$quant_var_editor .= "<select  style='max-width: none;min-width: 0' id='$id_factor' class='quant-factor' data-quant-id='$id_quant' data-price-id='$id_price' data-price-unit='$price' name='factors[{$quantite->ID}]' style='display: inline-block'>";
					$quant_var_editor .= tf_parse_select_options(
						$quantite->getQuantiteOptions(),
						$edit_inscription
							? $edit_inscription->getContrat_quantite_factor( $quantite->ID )
							: null,
						false );
					$quant_var_editor .= '</select>';
				}

				$checked = checked( $edit_inscription && $edit_inscription->getContrat_quantite_factor( $quantite->ID ) > 0, true, false );
				$type    = $contrat->isQuantiteMultiple() ? 'checkbox' : 'radio';
				echo '<p style="margin-top: 1em; margin-bottom: 0"><label for="' . $id_quant . '">
			<input id="' . $id_quant . '" name="quants[]" ' . $checked . ' class="quant" value="' . $quantite->ID . '" type="' . $type . '" data-factor-id="' . $id_factor . '" data-price="' . $price . '" data-pricew="' . ( abs( $quantite->getPrix_unitaire() ) < 0.001 ? 1 : 0 ) . '"/> 
			' . $quant_var_editor . ' ' . esc_html( $quantite->getTitle() ) . ' ' . $price_compute_text . ( abs( $quantite->getPrix_unitaire() ) > 0.001 ? ' = <span id="' . $id_price . '">' . $price . '</span>€</label></p>' : '' );

				$spec_dates = $quantite->getSpecificDistributionDates();
				if ( ! empty( $spec_dates ) ) {
					$spec_dates = array_filter( $spec_dates, function ( $d ) use ( $start_date ) {
						return $d >= $start_date;
					} );
				}
				if ( ! empty( $spec_dates ) ) {

					$grouped_dates = from( $spec_dates )->groupBy( function ( $d ) {
						return date_i18n( 'F Y', $d );
					} );

					$grouped_dates_array = [];
					foreach ( $grouped_dates as $k => $v ) {
						$grouped_dates_array[] = $k . ' : ' . ( count( $v ) > 1 ? 'les ' : 'le ' ) . implode( ', ', array_map(
								function ( $d ) {
									return date_i18n( 'd', $d );
								}, $v
							) );
					}
					echo '<p style="font-style: italic; font-size: 0.8em; padding-left: 15pt; padding-top: 0; margin-top: 0">Dates spécifiques : ' . implode( ' ; ', $grouped_dates_array ) . '</p>';
				}
			}
		}
		echo '<p style="margin-top: 1em;">Total: <span id="total">0</span>€</p>';
		echo '<p><input type="submit" class="btn btn-default btn-assist-inscr" value="Valider" /></p>';
		echo '</form>';

		if ( $admin_mode && ! empty( $quants_full ) ) {
			echo '<p>Les paniers "' . implode( ',', $quants_full ) . '" sont <strong>COMPLETS</strong> et n\'apparaissent donc pas ci-dessus. Pour augmenter les quotas, ' .
			     Amapress::makeLink( $contrat->getAdminEditLink(), 'éditez le contrat ' . $contrat->getTitle(), true, true ) . '</p>';
		}

	} else if ( 'inscr_contrat_paiements' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message );
		}
		$start_date = intval( $_REQUEST['start_date'] );
		$coadhs     = isset( $_REQUEST['coadhs'] ) ? $_REQUEST['coadhs'] : '';
		if ( is_array( $coadhs ) ) {
			$coadhs = implode( ',', $coadhs );
		}

		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message );
		}
		$next_step_url = add_query_arg( [ 'step' => 'inscr_contrat_create' ] );

		$pay_at_deliv = [];
		if ( $for_logged ) {
			echo '<h4>Étape 3/4 : Règlement</h4>';
		} else {
			echo '<h4>Étape 7/8 : Règlement</h4>';
		}
		$by_month_totals = [];
		if ( $contrat->isPanierVariable() ) {
			$panier_vars = isset( $_REQUEST['panier_vars'] ) ? $_REQUEST['panier_vars'] : [];
			if ( empty( $panier_vars ) ) {
				wp_die( $invalid_access_message );
			}

			$total         = 0;
			$chosen_quants = [];
			foreach ( $panier_vars as $date_k => $quant_factors ) {
				$date_values = [];
				foreach ( $quant_factors as $quant_k => $factor_v ) {
					$q_id   = intval( $quant_k );
					$factor = floatval( $factor_v );
					if ( $factor <= 0 ) {
						unset( $panier_vars[ $date_k ][ $quant_k ] );
						continue;
					}
					$quant         = AmapressContrat_quantite::getBy( $q_id );
					$date_values[] = $quant->getFormattedTitle( $factor );
					$total         += $factor * $quant->getPrix_unitaire();
					if ( abs( $quant->getPrix_unitaire() ) < 0.001 ) {
						$pay_at_deliv[] = $quant->getTitle();
					} else {
						$month = date_i18n( 'M', $date_k );
						if ( empty( $by_month_totals[ $month ] ) ) {
							$by_month_totals[ $month ] = 0;
						}
						$by_month_totals[ $month ] += $factor * $quant->getPrix_unitaire();
					}
				}
				if ( ! empty( $date_values ) ) {
					$chosen_quants[ $date_k ] = $date_values;
				} else {
					unset( $panier_vars[ $date_k ] );
				}
			}
			$serial_quants = $panier_vars;

			if ( ! $admin_mode ) {
				if ( ! $use_contrat_term ) {
					echo '<p style="margin-bottom: 0">Vous allez vous passer commande de ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . ' avec les options suivantes:</p>';
				} else {
					echo '<p style="margin-bottom: 0">Vous allez vous inscrire au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . ' avec les options suivantes:</p>';
				}
			} else {
				$amapien = AmapressUser::getBy( $user_id );
				echo '<p style="margin-bottom: 0">Vous allez inscrire ' . esc_html( $amapien->getDisplayName() ) . ' au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . ' avec les options suivantes:</p>';
			}
			echo '<ul style="list-style-type: square">';
			foreach ( $chosen_quants as $dt => $quant_descs ) {
				echo '<li style="margin-left: 35px">' . esc_html( date_i18n( 'd/m/Y', intval( $dt ) ) );
				echo '<ul style="list-style-type: disc">';
				foreach ( $quant_descs as $quant_desc ) {
					echo '<li style="margin-left: 15px">' . esc_html( $quant_desc ) . '</li>';
				}
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			$quants = isset( $_REQUEST['quants'] ) ? $_REQUEST['quants'] : [];
			if ( ! is_array( $quants ) ) {
				$quants = [ $quants ];
			}

			if ( empty( $quants ) ) {
				wp_die( $invalid_access_message );
			}

			$factors = isset( $_REQUEST['factors'] ) ? $_REQUEST['factors'] : [];

			$dates = $contrat->getListe_dates();
			$dates = array_filter( $dates, function ( $d ) use ( $start_date ) {
				return $d >= $start_date;
			} );

			$total         = 0;
			$chosen_quants = [];
			$serial_quants = [];
			foreach ( $quants as $q ) {
				$q_id = intval( $q );

				$dates_factors = 0;
				foreach ( $dates as $d ) {
					$dates_factors += $contrat->getDateFactor( $d, $q_id );
				}

				$factor          = isset( $factors[ $q ] ) ? floatval( $factors[ $q ] ) : 1;
				$serial_quants[] = [
					'q' => $q_id,
					'f' => $factor,
				];
				$quant           = AmapressContrat_quantite::getBy( $q_id );
				$chosen_quants[] = $quant->getFormattedTitle( $factor );
				$total           += $dates_factors * $factor * $quant->getPrix_unitaire();
				if ( abs( $quant->getPrix_unitaire() ) < 0.001 ) {
					$pay_at_deliv[] = $quant->getTitle();
				} else {
					foreach ( $dates as $date_k ) {
						$month = date_i18n( 'M', $date_k );
						if ( empty( $by_month_totals[ $month ] ) ) {
							$by_month_totals[ $month ] = 0;
						}
						$by_month_totals[ $month ] += $contrat->getDateFactor( $d, $q_id ) * $factor * $quant->getPrix_unitaire();
					}
				}
			}

			if ( count( $chosen_quants ) == 1 && ! $admin_mode ) {
				if ( ! $use_contrat_term ) {
					echo '<p style="margin-bottom: 0">Vous avez choisi l\'option “' . esc_html( $chosen_quants[0] ) . '” de la commande ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				} else {
					echo '<p style="margin-bottom: 0">Vous avez choisi l\'option “' . esc_html( $chosen_quants[0] ) . '” du contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				}
			} else {
				if ( ! $admin_mode ) {
					if ( ! $use_contrat_term ) {
						echo '<p style="margin-bottom: 0">Vous avez choisi les options suivantes de la commande ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . ' :</p>';
					} else {
						echo '<p style="margin-bottom: 0">Vous avez choisi les options suivantes du contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . ' :</p>';
					}
				} else {
					$amapien = AmapressUser::getBy( $user_id );
					echo '<p style="margin-bottom: 0">Vous allez inscrire ' . esc_html( $amapien->getDisplayName() ) . ' au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . ' avec les options suivantes:</p>';
				}
				echo '<ul style="list-style-type: disc">';
				foreach ( $chosen_quants as $q ) {
					echo '<li style="margin-left: 35px">' . esc_html( $q ) . '</li>';
				}
				echo '</ul>';
			}
		}

		echo wp_unslash( $contrat->getPaiementsMention() );

		if ( $contrat->getManage_Cheques() ) {
			if ( ! $admin_mode ) {
				echo '<p style="margin-bottom: 0">Vous pouvez régler cette somme en :</p>';
			} else {
				echo '<p style="margin-bottom: 0">Règlement :</p>';
			}
		}
		$serial_quants = esc_attr( serialize( $serial_quants ) );
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		echo "<input type='hidden' name='quants' value='$serial_quants'/>";
		if ( $contrat->getManage_Cheques() ) {
			$min_cheque_amount = $contrat->getMinChequeAmount();
			if ( $total > 0 ) {
				$possible_cheques = $contrat->getPossiblePaiements();
				if ( $contrat->getPayByMonth() ) {
					$max_cheques      = count( array_filter( $by_month_totals, function ( $v ) {
						return $v > 0;
					} ) );
					$possible_cheques = [ 1, $max_cheques ];
				}
				foreach ( $possible_cheques as $nb_cheque ) {
					if ( $total / $nb_cheque < $min_cheque_amount ) {
						continue;
					}

					$checked = checked( $edit_inscription && $edit_inscription->getPaiements() == $nb_cheque, true, false );
					if ( $contrat->getPayByMonth() ) {
						if ( 1 === $nb_cheque ) {
							$cheques   = Amapress::formatPrice( $total, true );
							$chq_label = sprintf( "1 chèque de %0.2f €", $total );
						} else {
							$cheques   = implode( '|', array_map( function ( $month_amount ) {
								return Amapress::formatPrice( $month_amount, true );
							}, $by_month_totals ) );
							$chq_label = implode( ' ; ', array_map( function ( $month, $month_amount ) {
								return sprintf( "%s: 1 chèque de %0.2f €",
									$month,
									$month_amount );
							}, array_keys( $by_month_totals ), array_values( $by_month_totals ) ) );
						}
						echo "<label for='cheques-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='cheques-$nb_cheque' data-cheques-details='$cheques' value='$nb_cheque' class='input-nb-cheques required' />$chq_label</label><br/>";
					} else {
						$cheques            = $contrat->getChequeOptionsForTotal( $nb_cheque, $total );
						$option             = esc_html( $cheques['desc'] );
						$cheque_main_amount = esc_attr( Amapress::formatPrice( $cheques['main_amount'] ) );
						$last_cheque        = esc_attr( Amapress::formatPrice( ! empty( $cheques['remain_amount'] ) ? $cheques['remain_amount'] : $cheques['main_amount'] ) );
						$chq_label          = '';
						if ( $cheque_main_amount != $last_cheque ) {
							$chq_label = "$nb_cheque chèque(s) : ";
						}
						echo "<label for='cheques-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='cheques-$nb_cheque' data-main-amount='$cheque_main_amount €' data-last-amount='$last_cheque €' value='$nb_cheque' class='input-nb-cheques required' />$chq_label$option</label><br/>";
					}
				}
			} else {
				echo '<p><strong>Paiement à la livraison</strong></p>';
			}
			if ( $contrat->getAllow_Delivery_Pay() || abs( $total ) < 0.001 ) {
				$checked = checked( $edit_inscription && 'dlv' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-dlv' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-dlv' $checked value='-3' class='input-nb-cheques required' />Paiement à la livraison</label><br/>";
			}
			if ( $contrat->getAllow_Cash() ) {
				$checked = checked( $edit_inscription && 'esp' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-esp' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-esp' $checked value='-1' class='input-nb-cheques required' />En espèces</label><br/>";
			}
			if ( $contrat->getAllow_Transfer() ) {
				$checked = checked( $edit_inscription && 'vir' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-vir' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-vir' $checked value='-2' class='input-nb-cheques required' />Par virement</label><br/>";
			}
			if ( $contrat->getAllow_LocalMoney() ) {
				$checked = checked( $edit_inscription && 'mon' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-mon' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-mon' $checked value='-4' class='input-nb-cheques required' />En monnaie locale</label><br/>";
			}
			if ( $contrat->getAllowAmapienInputPaiementsDetails() && $total > 0 ) {
				$amapien  = AmapressUser::getBy( $user_id );
				$emetteur = esc_attr( $amapien->getDisplayName() );
				echo '<script type="application/javascript">
jQuery(function($) {
    var show_cheque_line = function() {
        if (!$(this).is(":checked"))
            return;
        var nb_cheques = parseInt($(this).val());
        var cheques_details = $(this).data("cheques-details");
        if (cheques_details) {
            cheques_details = cheques_details.split("|");
            var i = 0;
            $("#cheques-details tr").each(function() {
               if (i<=nb_cheques) {
                    $(this).show();
               } else {
                    $(this).hide();
               }
               //skip header
               if (i > 0)
                    $(".amps-pmt-amount", this).text(cheques_details[i - 1]);
               i++;
            }); 
        } else {
            var main_amount = $(this).data("main-amount");
            var last_amount = $(this).data("last-amount");
            var i = 0;
            $("#cheques-details tr").each(function() {
               if (i<=nb_cheques) {
                    $(this).show();
               } else {
                    $(this).hide();
               }
               $(".amps-pmt-amount", this).text(main_amount);
               if (i == nb_cheques)
                   $(".amps-pmt-amount", this).text(last_amount);
               i++;
            });  
        }
    };
    $(\'.input-nb-cheques\').each(show_cheque_line).change(show_cheque_line);
});
</script>';
				echo '<table id="cheques-details"><thead>
<th>Date encaissement</th>
<th>Numéro chèque</th>
<th>Banque</th>
<th>Emetteur</th>
<th>Montant</th>
</thead><tbody>';
				Amapress::setFilterForReferent( false );
				$edit_all_paiements = $edit_inscription ? $edit_inscription->getAllPaiements() : null;
				Amapress::setFilterForReferent( true );
				$req = ( $paiements_info_required ? 'required' : '' );
				for ( $i = 1; $i <= 12; $i ++ ) {
					$edit_paiement   = $edit_all_paiements && isset( $edit_all_paiements[ $i - 1 ] ) ? $edit_all_paiements[ $i - 1 ] : null;
					$paiements_dates = array_map(
						function ( $d ) use ( $edit_paiement ) {
							$selected = selected( $edit_paiement && Amapress::start_of_day( $edit_paiement->getDate() ) == Amapress::start_of_day( $d ), true, false );

							return '<option ' . $selected . ' value="' . esc_attr( $d ) . '">' . esc_html( date_i18n( 'd/m/Y', $d ) ) . '</option>';
						}, $contrat->getPaiements_Liste_dates()
					);
					if ( ! $edit_inscription ) {
						if ( isset( $paiements_dates[ $i - 1 ] ) ) {
							$paiements_dates[ $i - 1 ] = str_replace( '<option ', '<option selected="selected" ', $paiements_dates[ $i - 1 ] );
						}
					}

					$paiement_num      = esc_attr( $edit_paiement ? $edit_paiement->getNumero() : '' );
					$paiement_banque   = esc_attr( $edit_paiement ? $edit_paiement->getBanque() : '' );
					$paiement_emetteur = esc_attr( $edit_paiement ? $edit_paiement->getEmetteur() : $emetteur );
					$paiements_dates   = implode( '', $paiements_dates );
					echo "<tr style='display: none'>
<td><select id='pmt-$i-date' name='pmt[$i][date]' class='$req'>
$paiements_dates
</select></td>
<td><input type='text' id='pmt-$i-num' name='pmt[$i][num]' class='$req' value='$paiement_num' /></td>
<td><input type='text' id='pmt-$i-banque' name='pmt[$i][banque]' class='$req' value='$paiement_banque' /></td>
<td><input type='text' id='pmt-$i-emetteur' name='pmt[$i][emetteur]' class='$req' value='$paiement_emetteur' /></td>
<td class='amps-pmt-amount'></td>
</tr>";
				}
				echo '</tbody></table>';
			}
		} else {
			echo "<input type='hidden' name='cheques' value='0'/>";
		}
		echo '<br />';
		if ( ! empty( $pay_at_deliv ) ) {
			echo '<p><strong>Produits payables à la livraison</strong> : ' . implode( ', ', $pay_at_deliv ) . '</p>';
			echo '<br />';
		}
		if ( ! $admin_mode ) {
			echo '<label for="inscr_message">Message pour le référent :</label><textarea id="inscr_message" name="message">' . ( $edit_inscription ? esc_textarea( $edit_inscription->getMessage() ) : '' ) . '</textarea>';
		} else {
			echo '<p><input type="checkbox" checked="checked" id="inscr_confirm_mail" name="inscr_confirm_mail" /><label for="inscr_confirm_mail"> Confirmer par email à l\'adhérent</label></p>';
		}
		echo '<input type="submit" value="Valider" class="btn btn-default btn-assist-inscr" />';
		echo '</form>';
	} else if ( 'inscr_contrat_create' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['lieu_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$lieu_id = intval( $_REQUEST['lieu_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message );
		}
		$start_date = intval( $_REQUEST['start_date'] );
		$coadhs     = isset( $_REQUEST['coadhs'] ) ? $_REQUEST['coadhs'] : '';
		if ( ! is_array( $coadhs ) ) {
			$coadhs = explode( ',', $coadhs );
		}

		$message = sanitize_textarea_field( isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' );

		$amapien = AmapressUser::getBy( $user_id );
		$lieu    = AmapressLieu_distribution::getBy( $lieu_id );
		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( ! $amapien || ! $lieu || ! $contrat ) {
			wp_die( $invalid_access_message );
		}

		if ( $contrat->getManage_Cheques() && empty( $_REQUEST['cheques'] ) ) {
			wp_die( $invalid_access_message );
		}
		$cheques = ! isset( $_REQUEST['cheques'] ) ? 0 : intval( $_REQUEST['cheques'] );
		if ( empty( $_REQUEST['quants'] ) ) {
			wp_die( $invalid_access_message );
		}
		$quants = unserialize( stripslashes( $_REQUEST['quants'] ) );
		if ( empty( $quants ) ) {
			wp_die( $invalid_access_message );
		}

		$referents_ids = $contrat->getReferentsIds( $lieu_id );
		/** @var AmapressUser[] $referents */
		$referents       = array_map( function ( $rid ) {
			return AmapressUser::getBy( $rid );
		}, $referents_ids );
		$referents_mails = [];
		foreach ( $referents as $r ) {
			if ( ! $r ) {
				continue;
			}
			$referents_mails = array_merge( $referents_mails, $r->getAllEmails() );
		}

		$any_full = $contrat->isFull();

		$quantite_ids     = [];
		$quantite_factors = [];
		if ( $contrat->isPanierVariable() ) {
			$quantite_variables = $quants;
		} else {
			foreach ( $quants as $q ) {
				$q_id           = intval( $q['q'] );
				$any_full       = $any_full || $contrat->isFull( $q_id );
				$quantite_ids[] = $q_id;
				$f              = floatval( $q['f'] );
				if ( abs( $f - 1.0 ) > 0.001 ) {
					$quantite_factors[ strval( $q_id ) ] = $f;
				}
			}
		}


		if ( $any_full ) {
			if ( $admin_mode ) {
				$contrat_edit_link      = Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle(), true, true );
				$contrats_step_url_attr = esc_attr( $contrats_step_url );
				$mailto_refs            = esc_attr( "mailto:$referents_mails" );
				wp_die( "<p>Désolé, ce contrat/commande ou l'un des paniers que vous avez choisi est complet<br/>
<a href='{$contrats_step_url_attr}'>Retour aux contrats/commandes</a><br/>
Pour augmenter les quota du contrat ou de ses paniers, cliquez sur le lien suivant : $contrat_edit_link<br/>
LE cas écheant, une fois les quota mis à jour, appuyer sur F5 pour terminer l'inscription en cours.
</p>" );
			} else {
				$contrats_step_url_attr = esc_attr( $contrats_step_url );
				$mailto_refs            = esc_attr( "mailto:$referents_mails" );
				wp_die( "<p>Désolé, ce contrat/commande ou l'un des paniers que vous avez choisi est complet<br/>
<a href='{$contrats_step_url_attr}'>Retour aux contrats/commandes</a><br/>
<a href='$mailto_refs'>Contacter les référents</a>
</p>" );
			}
		}

		$meta = [
			'amapress_adhesion_adherent'         => $user_id,
			'amapress_adhesion_status'           => 'to_confirm',
			'amapress_adhesion_date_debut'       => $start_date,
			'amapress_adhesion_contrat_instance' => $contrat_id,
			'amapress_adhesion_message'          => $message,
			'amapress_adhesion_paiements'        => ( $cheques < 0 ? 1 : ( $cheques > 0 ? $cheques : 0 ) ),
			'amapress_adhesion_lieu'             => $lieu_id,
		];
		for ( $i = 2; $i <= 4; $i ++ ) {
			if ( ! empty( $coadhs[ $i - 2 ] ) ) {
				$meta["amapress_adhesion_adherent{$i}"] = $coadhs[ $i - 2 ];
			} elseif ( $edit_inscription ) {
				delete_post_meta( $edit_inscription->ID, "amapress_adhesion_adherent{$i}" );
			}
		}
		if ( - 1 == $cheques ) {
			$meta['amapress_adhesion_pmt_type'] = 'esp';
		}
		if ( - 2 == $cheques ) {
			$meta['amapress_adhesion_pmt_type'] = 'vir';
		}
		if ( - 3 == $cheques ) {
			$meta['amapress_adhesion_pmt_type'] = 'dlv';
		}
		if ( - 4 == $cheques ) {
			$meta['amapress_adhesion_pmt_type'] = 'mon';
		}
		if ( ! empty( $quantite_ids ) ) {
			$meta['amapress_adhesion_contrat_quantite'] = $quantite_ids;
		}
		if ( ! empty( $quantite_factors ) ) {
			$meta['amapress_adhesion_contrat_quantite_factors'] = $quantite_factors;
		}
		if ( ! empty( $quantite_variables ) ) {
			$meta['amapress_adhesion_panier_variables'] = $quantite_variables;
		}
		$my_post = array(
			'post_title'   => 'Inscription',
			'post_type'    => AmapressAdhesion::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => 'publish',
			'meta_input'   => $meta,
		);
		if ( $edit_inscription ) {
			$my_post['ID'] = $edit_inscription->ID;
			$new_id        = wp_update_post( $my_post, true );
		} else {
			$new_id = wp_insert_post( $my_post, true );
		}
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			//TODO ???
			wp_die( 'Une erreur s\'est produite' );
		}
		if ( $edit_inscription && $cheques > 0 ) {
			delete_post_meta( $new_id, 'amapress_adhesion_pmt_type' );
		}

		Amapress::setFilterForReferent( false );
		$inscription = AmapressAdhesion::getBy( $new_id, true );
		Amapress::setFilterForReferent( true );
		if ( $inscription->getContrat_instance()->getManage_Cheques() ) {
			$inscription->preparePaiements( isset( $_REQUEST['pmt'] ) ? $_REQUEST['pmt'] : [] );
		}

		if ( ! $admin_mode || isset( $_REQUEST['inscr_confirm_mail'] ) ) {
			if ( Amapress::toBool( $atts['send_contrat_confirm'] ) ) {
				$inscription->sendConfirmationMail();
			}
		}

		if ( ! $admin_mode ) {
			if ( Amapress::toBool( $atts['send_referents'] ) ) {
				$inscription->sendReferentsNotificationMail( false, $notify_email );
			}

			Amapress::setFilterForReferent( false );
			$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
			Amapress::setFilterForReferent( true );
			$adhs_contrat_ids                   = array_map( function ( $a ) {
				/** @var AmapressAdhesion $a */
				return $a->getContrat_instance()->ID;
			}, $adhs );
			$adhs_contrat_ids[]                 = $inscription->getContrat_instance()->ID;
			$user_subscribable_contrats         = array_filter( $subscribable_contrats, function ( $c ) use ( $adhs_contrat_ids ) {
				return ! in_array( $c->ID, $adhs_contrat_ids );
			} );
			$user_subscribable_contrats_display = implode( ', ', array_unique( array_map( function ( $c ) {
				/** @var AmapressContrat_instance $c */
				return $c->getModelTitle() . ( ! empty( $c->getSubName() ) ? ' - ' . $c->getSubName() : '' );
			}, $user_subscribable_contrats ) ) );
			if ( $for_logged ) {
				echo '<h4>étape 4/4 : Félicitations !</h4>';
			} else {
				echo '<h4>étape 8/8 : Félicitations !</h4>';
			}

			$online_contrats_end_step_message      = wp_unslash( Amapress::getOption( 'online_contrats_end_step_message' ) );
			$online_contrats_end_step_edit_message = wp_unslash( Amapress::getOption( 'online_contrats_end_step_edit_message' ) );
			if ( ! $use_contrat_term ) {
				echo '<div class="alert alert-success">Votre pré-commande a bien été prise en compte.</div>';
			} else {
				echo '<div class="alert alert-success">Votre pré-inscription a bien été prise en compte.</div>';
			}
			if ( Amapress::toBool( $atts['send_contrat_confirm'] ) ) {
				if ( ! $use_contrat_term ) {
					echo '<p>Vous allez recevoir un email de confirmation avec votre récapitulatif de commande dans quelques minutes. (Pensez à regarder vos spams, cet email peut s\'y trouver à cause du récapitulatif de commande joint ou pour expéditeur inconnu de votre carnet d\'adresses)</p>';
				} else {
					echo '<p>Vous allez recevoir un email de confirmation avec votre contrat dans quelques minutes. (Pensez à regarder vos spams, cet email peut s\'y trouver à cause du contrat joint ou pour expéditeur inconnu de votre carnet d\'adresses)</p>';
				}
			}
			if ( ! empty( $inscription->getContrat_instance()->getContratModelDocFileName() ) ) {
				$print_contrat                         = Amapress::makeButtonLink(
					add_query_arg( [
						'inscr_assistant' => 'generate_contrat',
						'inscr_id'        => $inscription->ID,
						'inscr_key'       => $key
					] ),
					$contrat_print_button_text, true, true, 'btn btn-default'
				);
				$online_contrats_end_step_message      = str_replace( '%%print_button%%', $print_contrat, $online_contrats_end_step_message );
				$online_contrats_end_step_edit_message = str_replace( '%%print_button%%', $print_contrat, $online_contrats_end_step_edit_message );
			} else {
				$online_contrats_end_step_message      = str_replace( '%%print_button%%', '', $online_contrats_end_step_message );
				$online_contrats_end_step_edit_message = str_replace( '%%print_button%%', '', $online_contrats_end_step_edit_message );
			}
			if ( $inscription->canSelfEdit() ) {
				$inscription_url = add_query_arg( [
					'step'       => 'inscr_contrat_date_lieu',
					'contrat_id' => $inscription->getContrat_instanceId()
				] );
				$modify_button   = '<form method="get" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $inscription->getContrat_instanceId() . '" />
<input type="hidden" name="edit_inscr_id" value="' . $inscription->ID . '" />
<input type="submit" value="Modifier" class="btn btn-default btn-assist-inscr" />
</form>';
				$cancel_button   = '<form method="get" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="details" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $inscription->ID . '" />
<input type="hidden" name="cancel_inscr_id" value="' . $inscription->ID . '" />
<input type="submit" value="Annuler" class="btn btn-default btn-assist-inscr" />
</form>';
				if ( strpos( $online_contrats_end_step_edit_message, '%%modify_button%%' ) !== false ) {
					$online_contrats_end_step_edit_message = str_replace( '%%modify_button%%', $modify_button, $online_contrats_end_step_edit_message );
				} else {
					echo '<br/>' . $modify_button;
				}
				if ( strpos( $online_contrats_end_step_edit_message, '%%cancel_button%%' ) !== false ) {
					$online_contrats_end_step_edit_message = str_replace( '%%cancel_button%%', $cancel_button, $online_contrats_end_step_edit_message );
				} else {
					echo '<br/>' . $cancel_button;
				}
			} else {
				$online_contrats_end_step_edit_message = '';
			}
			echo amapress_replace_mail_placeholders( $online_contrats_end_step_edit_message, $amapien, $inscription );
			echo amapress_replace_mail_placeholders( $online_contrats_end_step_message, $amapien, $inscription );

			if ( 'mes-contrats' == $tag ) {
				if ( ! $use_contrat_term ) {
					echo '<p>Retourner à la liste de mes commandes :<br/>';
				} else {
					echo '<p>Retourner à la liste de mes contrats :<br/>';
				}
				echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
			} else {
				if ( ! empty( $user_subscribable_contrats ) ) {
					if ( ! $use_contrat_term ) {
						echo '<p>Vous pouvez également découvrir et éventuellement passer les commandes suivantes (' . $user_subscribable_contrats_display . ') :<br/>';
					} else {
						echo '<p>Vous pouvez également découvrir et éventuellement adhérer aux contrats suivants (' . $user_subscribable_contrats_display . ') :<br/>';
					}
					echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
				} else {
					if ( ! $use_contrat_term ) {
						echo '<p>Vous avez déjà passé toutes les commandes disponibles.</p>';
						echo '<p>Retourner à la liste de mes commandes :<br/>';
					} else {
						echo '<p>Vous êtes déjà inscrit à tous les contrats.</p>';
						echo '<p>Retourner à la liste de mes contrats :<br/>';
					}
					echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
				}
			}

			if ( ! $admin_mode && 'mes-contrats' != $tag ) {
				echo '<p>J\'ai fini :<br/>
<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="the_end" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Terminer" />
</form></p>';
			}
		} else {
			echo '<div class="alert alert-success">L\'inscription a bien été prise en compte : ' . Amapress::makeLink( $inscription->getAdminEditLink(), 'Editer l\'inscription', true, true ) . '</div>';
			echo '<p><a href="' . esc_attr( $contrats_step_url ) . '" >Retourner à la liste de ses contrats/commandes</a></p>';
		}

	} else if ( 'the_end' == $step ) {
		if ( ! $use_contrat_term ) {
			echo '<h4>Félicitations, vous avez terminé vos commandes !</h4>';
		} else {
			echo '<h4>Félicitations, vous avez terminé vos inscriptions !</h4>';
		}
		echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_final_step_message' ), null ) );
	}
	?>
    <style type="text/css">
        #quant-commandes td {
            text-align: center
        }

        label {
            display: inline !important;
        }

        label#accept-error {
            display: block !important;
        }
    </style>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(function ($) {
            $('#quant-commandes').on('click', 'td', function () {
                jQuery(this).find(".quant-var, .quant-var-recopier").css('visibility', 'visible');
            });
            $(".amapress_validate").validate({
                    onkeyup: false,
                    errorPlacement: function (error, element) {
                        var $commandes = element.closest('.dataTables_wrapper');
                        if ($commandes.length) {
                            error.insertAfter($commandes);
                        } else {
                            if ("radio" === element.attr("type") || "checkbox" === element.attr("type")) {
                                error.insertBefore(element);
                            } else {
                                error.insertAfter(element);
                            }
                        }
                    }
                }
            );

            jQuery.validator.addMethod("required_if_not_empty", function (value, element) {
                if (jQuery('#' + jQuery(element).data('if-id')).val().length > 0) {
                    return jQuery(element).val().trim().length > 0;
                }
                return true;
            }, "Champ requis");

            jQuery.validator.addMethod("single_name", function (value, element) {
                return !(/[;\/\\,]/.test(jQuery(element).val()));
            }, "Merci de ne saisir qu'un seul nom ou prénom. Utilisez les champs de coadhérents pour vos coadhérents.");

            jQuery.validator.addMethod(
                "min_sum",
                function (value, element, params) {
                    var sumOfVals = 0;
                    var priceW = 0;
                    var parent = $(element).closest("form");
                    jQuery(parent).find(".quant:checked,.quant-var").each(function () {
                        sumOfVals = sumOfVals + parseFloat(jQuery(this).data('price'));
                        priceW = priceW + parseInt(jQuery(this).data('pricew'));
                    });
                    if (sumOfVals > params) return true;
                    if (priceW > 0) return true;
                    return false;
                },
                "Le montant total doit être supérieur à {0}€<br/>"
            );

            function computeTotal() {
                var total = 0;
                jQuery('.quant:checked,.quant-var').each(function () {
                    total += parseFloat(jQuery(this).data('price'));
                });
                jQuery('#total').text(total.toFixed(2));
            }

            function computePrices() {
                var $this = jQuery(this);
                var priceUnit = parseFloat($this.data('price-unit'));
                var val = parseFloat($this.val());
                var quantElt = jQuery('#' + $this.data('quant-id'));
                var priceElt = jQuery('#' + $this.data('price-id'));
                if (Math.abs(priceUnit) < 0.001) {
                    priceElt.text('Prix au poids');
                    quantElt.data('price', 0);
                    quantElt.data('pricew', 1);
                } else {
                    priceElt.text((val * priceUnit).toFixed(2));
                    quantElt.data('price', val * priceUnit);
                    quantElt.data('pricew', 0);
                }
                computeTotal();
            }

            jQuery('.quant-factor').change(computePrices).each(computePrices);
            $(".quant-var-recopier").click(function () {
                var $td = $(this).closest("td");
                var val = parseFloat($td.find("select").val());
                $td.nextAll().find("select, a").each(function () {
                    if (val <= 0) {
                        $(this).css('visibility', 'hidden');
                    } else {
                        $(this).css('visibility', 'visible');
                    }
                });
                $td.nextAll().find("select").each(function () {
                    $(this).val(val);
                });
                return false;
            });
            jQuery('.quant-var').each(function () {
                var $this = jQuery(this);
                var priceUnit = parseFloat($this.data('price-unit'));
                var val = parseFloat($this.val());
                $this.data('price', val * priceUnit);
                if (Math.abs(priceUnit) < 0.001) {
                    $this.data('pricew', 1);
                } else {
                    $this.data('pricew', 0);
                }
                if (val <= 0 && priceUnit > 0) {
                    $this.css('visibility', 'hidden');
                    $this.parent().find('a').css('visibility', 'hidden');
                }
            }).change(function () {
                var $this = jQuery(this);
                var priceUnit = parseFloat($this.data('price-unit'));
                var val = parseFloat($this.val());
                $this.data('price', val * priceUnit);
                if (val > 0) {
                    $this.css('visibility', 'visible');
                    $this.parent().find('a').css('visibility', 'visible');
                }
                computeTotal();
            });
            jQuery('.quant-var,.quant:first').each(function () {
                var $this = jQuery(this);
                var opt = {
                    min_sum: <?php echo $min_total; ?>,
                };
                if ($this.data('grp-class')) {
                    opt[$this.data('grp-class')] = true;
                }
                $this.rules('add', opt);
            });
            jQuery('.amapress_validate .quant').change(function () {
                var $this = jQuery(this);
                var factorElt = jQuery('#' + $this.data('factor-id'));
                factorElt.toggle($this.is(':checked'));
                computeTotal();
            }).each(function () {
                var $this = jQuery(this);
                var factorElt = jQuery('#' + $this.data('factor-id'));
                factorElt.toggle($this.is(':checked'));
            });
            computeTotal();

            var updateAmount = function () {
                var sum = 0;
                jQuery(".amapress_pmt_cat_amount").each(function () {
                    sum += parseFloat(jQuery(this).val());
                });
                jQuery("#amapress_adhesion_paiement_amount").text(sum.toFixed(2));
            };
            jQuery(".amapress_pmt_cat_amount").on("change paste keyup", updateAmount);
            updateAmount();
        });
        //]]>
    </script>
	<?php

	return ob_get_clean();
}

/**
 * @param AmapressContrat_instance[] $contrats
 *
 * @return array
 */
function amapress_get_contrats_calendar( $contrats ) {
	$print_title = 'Calendrier des livraisons';
	$ret         = '<h4>' . esc_html( $print_title ) . '</h4>';
	$dates       = [];
	foreach ( $contrats as $contrat ) {
		foreach ( $contrat->getRemainingDates() as $d ) {
			$dates[] = $d;
		}
	}
	$dates = array_unique( $dates );
	sort( $dates );
	$columns   = [];
	$columns[] = array(
		'title' => 'Producteur',
		'data'  => array(
			'_'    => 'prod',
			'sort' => 'prod',
		)
	);
	foreach ( $dates as $date ) {
		$columns[] = array(
			'title' => date_i18n( 'd/m/Y', $date ),
			'data'  => array(
				'_'    => 'date_' . $date,
				'sort' => 'date_' . $date,
			)
		);
	}

	$data = [];
	foreach ( $contrats as $contrat ) {
		$row             = [];
		$row['prod']     = $contrat->getModel()->getTitle()
		                   . '<br />'
		                   . '<em>' . $contrat->getModel()->getProducteur()->getTitle() . '</em>';
		$remaining_dates = $contrat->getRemainingDates();
		foreach ( $dates as $date ) {
			if ( in_array( $date, $remaining_dates ) ) {
				$row[ 'date_' . $date ] = 'X';
			} else {
				$row[ 'date_' . $date ] = '';
			}
		}
		$data[] = $row;
	}

	$ret .= amapress_get_datatable( 'calend_delivs', $columns, $data,
		array(
			'paging'      => false,
			'searching'   => false,
			'responsive'  => false,
			'scrollX'     => true,
			'scrollY'     => '300px',
			'fixedHeader' => true,
		),
		array(
			[
				'extend' => Amapress::DATATABLES_EXPORT_EXCEL,
				'title'  => $print_title
			],
			[
				'extend' => Amapress::DATATABLES_EXPORT_PRINT,
				'title'  => $print_title,
			],
		) );

	return $ret;
}

/**
 * @param int $user_id
 * @param bool $ignore_renouv_delta
 * @param bool $by_prod
 *
 * @return string
 */
function amapress_get_details_all_deliveries( $user_id, $ignore_renouv_delta, $by_prod ) {
	Amapress::setFilterForReferent( false );
	$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
	Amapress::setFilterForReferent( true );

	$print_title = 'Récapitulatif des livraisons';
	$ret         = '<h4>' . esc_html( $print_title ) . '</h4>';
	$columns     = [];
	if ( $by_prod ) {
		$columns[] = array(
			'title' => 'Producteur',
			'data'  => array(
				'_'    => 'prod',
				'sort' => 'prod',
			)
		);
		$columns[] = array(
			'title' => 'Date',
			'data'  => array(
				'_'    => 'date_d',
				'sort' => 'date',
			)
		);
	} else {
		$columns[] = array(
			'title' => 'Date',
			'data'  => array(
				'_'    => 'date_d',
				'sort' => 'date',
			)
		);
		$columns[] = array(
			'title' => 'Producteur',
			'data'  => array(
				'_'    => 'prod',
				'sort' => 'prod',
			)
		);
	}

	$columns[] = array(
		'title' => 'Description',
		'data'  => array(
			'_'    => 'desc',
			'sort' => 'desc',
		)
	);
	$columns[] = array(
		'title' => 'Quantité',
		'data'  => array(
			'_'    => 'fact',
			'sort' => 'fact',
		)
	);
	$columns[] = array(
		'title' => 'Total',
		'data'  => array(
			'_'    => 'total_d',
			'sort' => 'total',
		)
	);

	$data = [];
	foreach ( $adhs as $adh ) {
		if ( $adh->getContrat_instance()->isPanierVariable() ) {
			$paniers = $adh->getPaniersVariables();
			foreach ( $adh->getRemainingDates() as $date ) {
				foreach ( AmapressContrats::get_contrat_quantites( $adh->getContrat_instanceId() ) as $quant ) {
					if ( ! empty( $paniers[ $date ][ $quant->ID ] ) ) {
						$row           = [];
						$row['date_d'] = date_i18n( 'd/m/Y', $date );
						$row['date']   = $date;

						$row['prod']    = $adh->getContrat_instance()->getModel()->getTitle()
						                  . '<br />'
						                  . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
						$row['desc']    = $quant->getTitle();
						$row['fact']    = $paniers[ $date ][ $quant->ID ];
						$price          = $paniers[ $date ][ $quant->ID ] * $quant->getPrix_unitaire();
						$row['total_d'] = Amapress::formatPrice( $price, true );
						$row['total']   = $price;
						$data[]         = $row;
					}
				}
			}

		} else {
			foreach ( $adh->getRemainingDates() as $date ) {
				foreach ( $adh->getContrat_quantites( $date ) as $quant ) {
					$row           = [];
					$row['date_d'] = date_i18n( 'd/m/Y', $date );
					$row['date']   = $date;

					$row['prod']    = $adh->getContrat_instance()->getModel()->getTitle()
					                  . '<br />'
					                  . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
					$row['desc']    = $quant->getTitle();
					$row['fact']    = $quant->getFactor();
					$row['total_d'] = Amapress::formatPrice( $quant->getPrice(), true );
					$row['total']   = $quant->getPrice();
					$data[]         = $row;
				}
			}
		}
	}
	if ( $by_prod ) {
		usort( $data, function ( $a, $b ) {
			return strcmp( $a['prod'], $b['prod'] );
		} );
	} else {
		usort( $data, function ( $a, $b ) {
			if ( $a['date'] == $b['date'] ) {
				return 0;
			}

			return $a['date'] > $b['date'] ? 1 : - 1;
		} );
	}
	$ret .= amapress_get_datatable( 'details_all_delivs', $columns, $data,
		array(
			'paging'    => false,
			'searching' => false,
			'rowGroup'  => [
				'dataSrc' => $by_prod ? 'prod' : 'date_d',
			]
		),
		array(
			[
				'extend' => Amapress::DATATABLES_EXPORT_EXCEL,
				'title'  => $print_title
			],
			[
				'extend'        => Amapress::DATATABLES_EXPORT_PRINT,
				'title'         => $print_title,
				'exportOptions' => [
					'rowGroup' => true
				]
			],
		) );

	return $ret;
}

/**
 * @param int $user_id
 * @param bool $ignore_renouv_delta
 */
function amapress_get_details_all_paiements( $user_id, $ignore_renouv_delta ) {
	Amapress::setFilterForReferent( false );
	$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
	Amapress::setFilterForReferent( true );

	$print_title = 'Récapitulatif des sommes dues';
	$ret         = '<h4>' . esc_html( $print_title ) . '</h4>';
	$columns     = [];
	$columns[]   = array(
		'title' => 'Producteur',
		'data'  => array(
			'_'    => 'prod',
			'sort' => 'prod_sort',
		)
	);
	$columns[]   = array(
		'title' => 'Total',
		'data'  => array(
			'_'    => 'total_d',
			'sort' => 'total',
		)
	);
	$columns[]   = array(
		'title' => 'Option paiements',
		'data'  => array(
			'_'    => 'opt_pmts',
			'sort' => 'opt_pmts',
		)
	);
	$columns[]   = array(
		'title' => 'Info',
		'data'  => array(
			'_'    => 'info',
			'sort' => 'info',
		)
	);
	$columns[]   = array(
		'title' => 'Statut',
		'data'  => array(
			'_'    => 'status',
			'sort' => 'status',
		)
	);

	$data = [];
	foreach ( $adhs as $adh ) {
		$paiements        = $adh->getAllPaiements();
		$paiements_status = [];
		foreach ( $paiements as $paiement ) {
			$paiements_status[] = sprintf(
				'%s %s (<span style="color: %s">%s</span>)',
				$paiement->getTypeFormatted(),
				Amapress::formatPrice( $paiement->getAmount(), true ),
				'not_received' == $paiement->getStatus() ? 'orange' : 'green',
				$paiement->getStatusDisplay()
			);
			$paiement->getStatus();
		}
		$row              = [];
		$row['prod']      = date_i18n( 'd/m/Y', $adh->getContrat_instance()->getDate_debut() ) .
		                    ' - ' . $adh->getContrat_instance()->getModel()->getTitle()
		                    . '<br />'
		                    . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
		$row['prod_sort'] = date_i18n( 'Y-m-d', $adh->getContrat_instance()->getDate_debut() ) .
		                    $adh->getContrat_instance()->getModel()->getTitle();
		$row['opt_pmts']  = $adh->getProperty( 'option_paiements' );
		$info             = 'Ordre: ' . $adh->getProperty( 'paiements_ordre' );
		$info             .= ! empty( $adh->getProperty( 'paiements_mention' ) ) ? '<br/>' . $adh->getProperty( 'paiements_mention' ) : '';
		$row['info']      = $info;
		$row['total_d']   = Amapress::formatPrice( $adh->getTotalAmount(), true );
		$row['total']     = $adh->getTotalAmount();
		$row['status']    = implode( ' ; ', $paiements_status );
		$data[]           = $row;
	}
	$ret .= amapress_get_datatable( 'details_all_paiements', $columns, $data,
		array(
			'paging'     => false,
			'searching'  => false,
			'responsive' => false,
			'nowrap'     => false,
		),
		array(
			[
				'extend' => Amapress::DATATABLES_EXPORT_EXCEL,
				'title'  => $print_title
			],
			[
				'extend'        => Amapress::DATATABLES_EXPORT_PRINT,
				'title'         => $print_title,
				'exportOptions' => [
					'rowGroup' => true
				]
			],
		) );

	return $ret;
}