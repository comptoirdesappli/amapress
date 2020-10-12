<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_sha_secret( $d ) {
	return sha1( AUTH_KEY . $d . SECURE_AUTH_KEY );
}

add_action( 'amapress_init', function () {
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_coords' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
			if ( ! isset( $_REQUEST['inscr_key'] ) || sanitize_text_field( $_REQUEST['inscr_key'] ) != amapress_sha_secret( $request_key ) ) {
				wp_die( 'Accès interdit' );
			}
		}
		$email              = sanitize_email( isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : '' );
		$user_firt_name     = sanitize_text_field( ! empty( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '' );
		$user_last_name     = sanitize_text_field( ! empty( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '' );
		$user_address       = sanitize_textarea_field( isset( $_REQUEST['address'] ) ? $_REQUEST['address'] : '' );
		$user_mobile_phones = sanitize_text_field( isset( $_REQUEST['telm'] ) ? $_REQUEST['telm'] : '' );
		$user_fix_phones    = sanitize_text_field( isset( $_REQUEST['telf'] ) ? $_REQUEST['telf'] : '' );
		$user_phones        = array_filter( [ $user_mobile_phones, $user_fix_phones ], function ( $s ) {
			return ! empty( $s );
		} );

		$notify_email = get_option( 'admin_email' );
		if ( ! empty( $_REQUEST['notify_email'] ) ) {
			if ( empty( $notify_email ) ) {
				$notify_email = sanitize_email( $_REQUEST['notify_email'] );
			} else {
				$notify_email .= ',' . sanitize_email( $_REQUEST['notify_email'] );
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
		if ( isset( $_REQUEST['send_welcome'] ) && ! Amapress::toBool( sanitize_text_field( $_REQUEST['send_welcome'] ) ) ) {
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
				'step'    => ! empty( $_REQUEST['coords_next_step'] ) ? sanitize_key( $_REQUEST['coords_next_step'] ) : 'contrats',
				'user_id' => $user_id,
			] )
		);
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_agreement' == $_REQUEST['inscr_assistant'] ) {
		$step = ! empty( $_REQUEST['coords_next_step'] ) ? sanitize_key( $_REQUEST['coords_next_step'] ) : 'contrats';
		if ( ! isset( $_REQUEST['accept'] ) ) {
			$step = 'agreement';
		}
		$user_id = isset( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : 0;
		wp_redirect_and_exit(
			add_query_arg( [
				'step'    => $step,
				'user_id' => $user_id,
			] )
		);
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'generate_contrat' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
			if ( ! isset( $_REQUEST['inscr_key'] ) || sanitize_text_field( $_REQUEST['inscr_key'] ) != amapress_sha_secret( $request_key ) ) {
				wp_die( 'Accès interdit' );
			}
		}

		$inscr_id = isset( $_REQUEST['inscr_id'] ) ? intval( $_REQUEST['inscr_id'] ) : 0;
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
			$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
			if ( ! isset( $_REQUEST['inscr_key'] ) || sanitize_text_field( $_REQUEST['inscr_key'] ) != amapress_sha_secret( $request_key ) ) {
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

function amapress_self_adhesion( $atts, $content = null, $tag ) {
	$atts                       = wp_parse_args( $atts );
	$atts['for_logged']         = 'false';
	$atts['allow_inscriptions'] = 'false';
	$atts['adhesion']           = 'true';

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_logged_self_adhesion( $atts, $content = null, $tag ) {
	$atts                       = wp_parse_args( $atts );
	$atts['for_logged']         = 'true';
	$atts['check_honeypots']    = 'false';
	$atts['allow_inscriptions'] = 'false';
	$atts['adhesion']           = 'true';
	unset( $atts['key'] );

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_inter_self_adhesion( $atts, $content = null, $tag ) {
	$atts                         = wp_parse_args( $atts );
	$atts['for_logged']           = 'false';
	$atts['allow_inscriptions']   = 'false';
	$atts['adhesion']             = 'true';
	$atts['for_intermittent']     = 'true';
	$atts['max_coadherents']      = 0;
	$atts['max_cofoyers']         = 0;
	$atts['show_adherents_infos'] = 'false';
	$atts['allow_adhesion_lieu']  = 'false';
	$atts['show_modify_coords']   = 'false';
	$atts['allow_adhesion_alone'] = 'true';

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_inter_logged_self_adhesion( $atts, $content = null, $tag ) {
	$atts                         = wp_parse_args( $atts );
	$atts['for_logged']           = 'true';
	$atts['check_honeypots']      = 'false';
	$atts['allow_inscriptions']   = 'false';
	$atts['adhesion']             = 'true';
	$atts['for_intermittent']     = 'true';
	$atts['max_coadherents']      = 0;
	$atts['max_cofoyers']         = 0;
	$atts['show_adherents_infos'] = 'false';
	$atts['allow_adhesion_lieu']  = 'false';
	$atts['show_modify_coords']   = 'false';
	$atts['allow_adhesion_alone'] = 'true';
	unset( $atts['key'] );

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_logged_self_inscription( $atts, $content = null, $tag ) {
	$atts                    = wp_parse_args( $atts );
	$atts['for_logged']      = 'true';
	$atts['check_honeypots'] = 'false';
	unset( $atts['key'] );

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_mes_contrats( $atts, $content = null, $tag ) {
	$atts                    = wp_parse_args( $atts );
	$atts['for_logged']      = 'true';
	$atts['check_honeypots'] = 'false';
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

function amapress_step_text( $step_id, $steps_nums, $steps_count ) {
	if ( empty( $steps_nums[ $step_id ] ) ) {
		return '';
	}

	return sprintf( 'Étape %d/%d : ', $steps_nums[ $step_id ], $steps_count );
}

/**
 * @param $atts
 */
function amapress_self_inscription( $atts, $content = null, $tag ) {
	amapress_ensure_no_cache();

	$step              = isset( $_REQUEST['step'] ) ? sanitize_key( $_REQUEST['step'] ) : 'email';
	$disable_principal = Amapress::getOption( 'disable_principal', false );

	$is_mes_contrats     = 'mes-contrats' == $tag;
	$is_inscription_mode = 'inscription-en-ligne' == $tag || 'inscription-en-ligne-connecte' == $tag;
	$is_adhesion_mode    = 'adhesion-en-ligne' == $tag || 'adhesion-en-ligne-connecte' == $tag
	                       || 'intermittent-adhesion-en-ligne' == $tag || 'intermittent-adhesion-en-ligne-connecte' == $tag;

	$atts = shortcode_atts(
		[
			'key'                                 => '',
			'use_steps_nums'                      => 'true',
			'for_logged'                          => 'false',
			'filter_multi_contrat'                => 'false',
			'admin_mode'                          => 'false',
			'agreement'                           => 'true',
			'mob_phone_required'                  => 'false',
			'address_required'                    => 'false',
			'check_principal'                     => 'true',
			'adhesion'                            => $is_adhesion_mode || $is_inscription_mode ? 'true' : 'false',
			'send_adhesion_confirm'               => 'true',
			'send_contrat_confirm'                => 'true',
			'send_referents'                      => 'true',
			'allow_inscription_all_dates'         => 'false',
			'send_tresoriers'                     => 'true',
			'ignore_renouv_delta'                 => 'true',
			'allow_inscriptions'                  => 'true',
			'allow_new_mail'                      => 'true',
			'check_adhesion_received'             => Amapress::getOption( 'check_adh_rcv' ),
			'check_adhesion_received_or_previous' => Amapress::getOption( 'check_adh_rcv_p' ),
			'track_no_renews'                     => 'false',
			'track_no_renews_email'               => get_option( 'admin_email' ),
			'notify_email'                        => '',
			'max_produit_label_width'             => '10em',
			'paiements_info_required'             => 'false',
			'paniers_modulables_editor_height'    => 350,
			'send_welcome'                        => 'true',
			'edit_names'                          => 'true',
			'allow_remove_cofoyers'               => 'true',
			'allow_remove_coadhs'                 => 'false',
			'contact_referents'                   => 'true',
			'show_adherents_infos'                => 'true',
			'show_details_button'                 => 'false',
			'allow_adhesion_lieu'                 => 'false',
			'allow_adhesion_message'              => 'false',
			'allow_coadherents_access'            => 'true',
			'allow_coadherents_inscription'       => 'true',
			'allow_coadherents_adhesion'          => 'true',
			'show_coadherents_address'            => 'false',
			'show_cofoyers_address'               => 'false',
			'contrat_print_button_text'           => 'Imprimer',
			'adhesion_print_button_text'          => 'Imprimer',
			'only_contrats'                       => '',
			'shorturl'                            => '',
			'show_modify_coords'                  => 'inscription-en-ligne' == $tag || 'adhesion-en-ligne' == $tag ? 'false' : 'true',
			'show_due_amounts'                    => 'false',
			'show_delivery_details'               => 'false',
			'show_calendar_delivs'                => 'false',
			'show_current_inscriptions'           => 'inscription-en-ligne-connecte' == $tag || $is_adhesion_mode ? 'false' : 'true',
			'show_only_subscribable_inscriptions' => $is_inscription_mode ? 'true' : 'false',
			'show_editable_inscriptions'          => 'true',
			'adhesion_shift_weeks'                => 0,
			'before_close_hours'                  => 24,
			'show_close_date'                     => false,
			'show_max_deliv_dates'                => 3,
			'max_coadherents'                     => 3,
			'max_cofoyers'                        => 3,
			'include_contrat_subnames'            => '',
			'exclude_contrat_subnames'            => '',
			'use_contrat_term'                    => 'true',
			'show_adhesion_infos'                 => 'true',
			'allow_adhesion_alone'                => $is_adhesion_mode ? 'true' : 'false',
			'skip_coords'                         => 'inscription-en-ligne-connecte' == $tag ? 'true' : 'false',
			'check_honeypots'                     => 'true',
			'email'                               => get_option( 'admin_email' ),
			'use_quantite_tables'                 => 'false',
			'allow_trombi_decline'                => 'true',
			'for_intermittent'                    => 'false',
		]
		, $atts );

	$ignore_renouv_delta        = Amapress::toBool( $atts['ignore_renouv_delta'] );
	$contrat_print_button_text  = $atts['contrat_print_button_text'];
	$adhesion_print_button_text = $atts['adhesion_print_button_text'];
	$for_logged                 = Amapress::toBool( $atts['for_logged'] );
	$ret                        = '';
	$allow_adhesion_alone       = Amapress::toBool( $atts['allow_adhesion_alone'] );
	$use_contrat_term           = Amapress::toBool( $atts['use_contrat_term'] );
	$admin_mode                 = Amapress::toBool( $atts['admin_mode'] );
	$show_close_date            = Amapress::toBool( $atts['show_close_date'] );
	$adhesion_intermittent      = Amapress::toBool( $atts['for_intermittent'] );
	$show_max_deliv_dates       = intval( $atts['show_max_deliv_dates'] );
	if ( $admin_mode && ! is_admin() ) {
		wp_die( 'admin_mode ne peut pas être utilisé directement' );
	}

	$amapien                             = null;
	$paiements_info_required             = Amapress::toBool( $atts['paiements_info_required'] );
	$allow_trombi_decline                = Amapress::toBool( $atts['allow_trombi_decline'] );
	$activate_adhesion                   = Amapress::toBool( $atts['adhesion'] );
	$activate_agreement                  = Amapress::toBool( $atts['agreement'] );
	$allow_remove_coadhs                 = Amapress::toBool( $atts['allow_remove_coadhs'] );
	$allow_remove_cofoys                 = Amapress::toBool( $atts['allow_remove_cofoyers'] );
	$allow_coadherents_inscription       = Amapress::toBool( $atts['allow_coadherents_inscription'] );
	$allow_coadherents_adhesion          = Amapress::toBool( $atts['allow_coadherents_adhesion'] );
	$show_adherents_infos                = Amapress::toBool( $atts['show_adherents_infos'] );
	$track_no_renews                     = Amapress::toBool( $atts['track_no_renews'] );
	$show_coadherents_address            = Amapress::toBool( $atts['show_coadherents_address'] );
	$show_cofoys_address                 = Amapress::toBool( $atts['show_cofoyers_address'] );
	$show_due_amounts                    = Amapress::toBool( $atts['show_due_amounts'] );
	$show_modify_coords                  = Amapress::toBool( $atts['show_modify_coords'] );
	$show_only_subscribable_inscriptions = Amapress::toBool( $atts['show_only_subscribable_inscriptions'] ) || $admin_mode;
	$show_current_inscriptions           = Amapress::toBool( $atts['show_current_inscriptions'] ) || $admin_mode;
	$show_editable_inscriptions          = Amapress::toBool( $atts['show_editable_inscriptions'] ) || $admin_mode;
	$show_delivery_details               = Amapress::toBool( $atts['show_delivery_details'] );
	$check_adhesion_received             = Amapress::toBool( $atts['check_adhesion_received'] );
	$check_adhesion_received_or_previous = Amapress::toBool( $atts['check_adhesion_received_or_previous'] );
	$skip_coords                         = Amapress::toBool( $atts['skip_coords'] );
	if ( $check_adhesion_received_or_previous ) {
		$check_adhesion_received = true;
	}
	$allow_inscriptions = Amapress::toBool( $atts['allow_inscriptions'] );
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
			if ( $is_mes_contrats || $skip_coords || $is_adhesion_mode ) {
				$step = 'contrats';
			} else {
				$step = 'coords_logged';
			}
		}
	} else {
		if ( amapress_can_access_admin() ) {
			$url = add_query_arg( 'key', $key, get_permalink() );
			if ( 'public' != $key ) {
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
		}
		$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
		if ( empty( $key ) || $request_key != $key ) {
			if ( empty( $key ) && amapress_can_access_admin() ) {
				$ret .= '<div style="color:red">L\'argument key (par ex, key="' . uniqid() . uniqid() . '") doit être défini sur le shortcode [inscription-en-ligne] de cette page : par exemple "[inscription-en-ligne key='
				        . uniqid() . uniqid() . ']". L\'accès à cette page ne peut se faire que de manière non connectée avec cette clé par la amapiens pour s\'inscrire.
<br/>Pour une utilisation publique, utilisez key=public</div>';
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

	$steps_nums = [];
	if ( $is_inscription_mode ) {
		if ( $for_logged ) {
			$steps_count = 4;
			if ( ! $activate_agreement || $skip_coords ) {
				$steps_nums['coords_logged'] = 0;
				$steps_nums['agreement']     = 0;
				$steps_nums['adhesion']      = 0;
				$steps_nums['save_adhesion'] = 0;
			} else {
				$steps_nums['coords_logged'] = 1;
				$steps_nums['agreement']     = 2;
				$steps_nums['adhesion']      = 3;
				$steps_nums['save_adhesion'] = 4;
			}
			$steps_nums['contrats']                = 0;
			$steps_nums['inscr_contrat_date_lieu'] = 1;
			$steps_nums['inscr_contrat_engage']    = 2;
			$steps_nums['inscr_contrat_paiements'] = 3;
			$steps_nums['inscr_contrat_create']    = 4;
		} else {
			$steps_count                           = 8;
			$steps_nums['email']                   = 1;
			$steps_nums['coords']                  = 2;
			$steps_nums['agreement']               = 0;
			$steps_nums['adhesion']                = 3;
			$steps_nums['save_adhesion']           = 0;
			$steps_nums['contrats']                = 4;
			$steps_nums['inscr_contrat_date_lieu'] = 5;
			$steps_nums['inscr_contrat_engage']    = 6;
			$steps_nums['inscr_contrat_paiements'] = 7;
			$steps_nums['inscr_contrat_create']    = 8;
		}
	} elseif ( $is_adhesion_mode ) {
		if ( $for_logged ) {
			$steps_count = 4;
			if ( $activate_agreement ) {
				if ( $skip_coords ) {
					$steps_count                 -= 1;
					$steps_nums['agreement']     = 1;
					$steps_nums['adhesion']      = 2;
					$steps_nums['save_adhesion'] = 3;
				} else {
					$steps_nums['coords_logged'] = 1;
					$steps_nums['agreement']     = 2;
					$steps_nums['adhesion']      = 3;
					$steps_nums['save_adhesion'] = 4;
				}
			} else {
				$steps_count -= 1;
				if ( $skip_coords ) {
					$steps_count                 -= 1;
					$steps_nums['adhesion']      = 1;
					$steps_nums['save_adhesion'] = 2;
				} else {
					$steps_nums['coords_logged'] = 1;
					$steps_nums['adhesion']      = 2;
					$steps_nums['save_adhesion'] = 3;
				}
			}
		} else {
			$steps_count = 5;
			if ( $activate_agreement ) {
				$steps_nums['email']         = 1;
				$steps_nums['coords']        = 2;
				$steps_nums['agreement']     = 3;
				$steps_nums['adhesion']      = 4;
				$steps_nums['save_adhesion'] = 5;
			} else {
				$steps_count                 -= 1;
				$steps_nums['email']         = 1;
				$steps_nums['coords']        = 2;
				$steps_nums['adhesion']      = 3;
				$steps_nums['save_adhesion'] = 4;
			}
		}
	} else {
		$steps_count                           = 4;
		$steps_nums['adhesion']                = 0;
		$steps_nums['save_adhesion']           = 0;
		$steps_nums['contrats']                = 0;
		$steps_nums['inscr_contrat_date_lieu'] = 1;
		$steps_nums['inscr_contrat_engage']    = 2;
		$steps_nums['inscr_contrat_paiements'] = 3;
		$steps_nums['inscr_contrat_create']    = 4;
	}
	if ( ! Amapress::toBool( $atts['use_steps_nums'] ) ) {
		$steps_nums = [];
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

	if ( $is_mes_contrats ) {
		$additional_css = '';
	} else {
		$additional_css = '<style type="text/css">' . esc_html( wp_unslash( Amapress::getOption( 'online_inscr_css' ) ) ) . '</style>';
	}

	ob_start();

	echo $additional_css;

	echo $ret;

	$min_total = 0;
	Amapress::setFilterForReferent( false );
	if ( $admin_mode ) {
		$subscribable_contrats = AmapressContrats::get_active_contrat_instances( null );
	} else {
		$subscribable_contrats = AmapressContrats::get_subscribable_contrat_instances_by_contrat( null );
	}
	Amapress::setFilterForReferent( true );
	$all_subscribable_contrats_ids = array_map( function ( $c ) {
		return $c->ID;
	}, $subscribable_contrats );
	if ( ! $admin_mode ) {
		$user_id = null;
		if ( isset( $_REQUEST['user_id'] ) ) {
			$user_id = intval( $_REQUEST['user_id'] );
		}
		if ( ! empty( $atts['include_contrat_subnames'] ) ) {
			$include_contrat_subnames = explode( ',', $atts['include_contrat_subnames'] );
			$subscribable_contrats    = array_filter( $subscribable_contrats, function ( $c ) use ( $include_contrat_subnames ) {
				/** @var AmapressContrat_instance $c */
				$include = false;
				foreach ( $include_contrat_subnames as $include_contrat_subname ) {
					if ( ! empty( $c->getSubName() )
					     && 0 === strcasecmp( $include_contrat_subname, $c->getSubName() ) ) {
						$include = true;
						break;
					}
				}

				return $include;
			} );
		}
		if ( ! empty( $atts['exclude_contrat_subnames'] ) ) {
			$exclude_contrat_subnames = explode( ',', $atts['exclude_contrat_subnames'] );
			$subscribable_contrats    = array_filter( $subscribable_contrats, function ( $c ) use ( $exclude_contrat_subnames ) {
				/** @var AmapressContrat_instance $c */
				$include = true;
				foreach ( $exclude_contrat_subnames as $exclude_contrat_subname ) {
					if ( ! empty( $c->getSubName() )
					     && 0 === strcasecmp( $exclude_contrat_subname, $c->getSubName() ) ) {
						$include = false;
						break;
					}
				}

				return $include;
			} );
		}
		$subscribable_contrats         = array_filter( $subscribable_contrats, function ( $c ) use ( $user_id ) {
			/** @var AmapressContrat_instance $c */
			return $c->canSelfSubscribe( $user_id ) && ! $c->isEnded();
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
	if ( ! $admin_mode && ! $is_mes_contrats && empty( $subscribable_contrats ) && ! $allow_adhesion_alone ) {
		ob_clean();

		if ( amapress_can_access_admin() ) {
			return 'Aucun contrat ne permet l\'inscription en ligne. Veuillez activer l\'inscription en ligne depuis ' . Amapress::makeLink( admin_url( 'edit.php?post_type=amps_contrat_inst' ), 'Edition des contrats' );
		} else {
			$closed_message = wp_unslash( Amapress::getOption( 'online_inscr_closed_message' ) );
			if ( ! $use_contrat_term ) {
				return '<p>Les commandes en ligne sont closes.</p>' . $closed_message;
			} else {
				return '<p>Les inscriptions en ligne sont closes.</p>' . $closed_message;
			}
		}
	}

	//TODO better ???
	$adh_period_date = Amapress::add_a_week( ( $min_contrat_date <= 0 || $min_contrat_date < amapress_time() ) ? amapress_time() : $min_contrat_date, $atts['adhesion_shift_weeks'] );

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

	if ( Amapress::toBool( $atts['check_principal'] ) && $is_inscription_mode && ! $disable_principal && ! $admin_mode && empty( $principal_contrats ) ) {
		if ( ! $is_mes_contrats ) {
			if ( amapress_can_access_admin() ) {
				ob_clean();

				return 'Aucun contrat principal. Veuillez définir un contrat principal depuis ' . Amapress::makeLink( admin_url( 'edit.php?post_type=amps_contrat_inst' ), 'Edition des contrats' );
			} elseif ( ! $allow_adhesion_alone && ! $user_has_contrat ) {
				ob_clean();

				$closed_message = wp_unslash( Amapress::getOption( 'online_inscr_closed_message' ) );
				if ( ! $use_contrat_term ) {
					return '<p>Les commandes en ligne sont closes.</p>' . $closed_message;
				} else {
					return '<p>Les inscriptions en ligne sont closes.</p>' . $closed_message;
				}
			}
		}
	}

	if ( is_admin() ) {
		$start_step_url = admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription' );
		if ( isset( $_REQUEST['user_id'] ) ) {
			$start_step_url = add_query_arg(
				[
					'user_id'   => intval( $_REQUEST['user_id'] ),
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
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		if ( ! $edit_inscription->canSelfEdit() ) {
			ob_clean();

			return 'Cette incription n\'est pas éditable';
		}
		$user_id  = ! empty( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : 0;
		$user_ids = $user_id ? AmapressContrats::get_related_users( $user_id, true,
			null, null, true, false ) : [];
		if ( ! in_array( $edit_inscription->getAdherentId(), $user_ids ) ) {
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
		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
		if ( $adh_period && ( $is_adhesion_mode || $min_contrat_date <= 0 ) ) {
			$saison = date_i18n( 'F Y', $adh_period->getDate_debut() ) . ' - ' . date_i18n( 'F Y', $adh_period->getDate_fin() );
		} else {
			$saison = date_i18n( 'F Y', $min_contrat_date ) . ' - ' . date_i18n( 'F Y', $max_contrat_date );
		}

		$welcome_message = wp_unslash(
			Amapress::getOption( $is_adhesion_mode ?
				( $adhesion_intermittent ? 'online_subscription_welcome_adh_inter_message' : 'online_subscription_welcome_adh_message' ) :
				'online_subscription_welcome_inscr_message' )
		);
		if ( empty( $welcome_message ) ) {
			if ( $is_inscription_mode ) {
				$welcome_type = ( $use_contrat_term ? 'd’inscription aux contrats producteurs' : 'de commande aux producteurs' );
			} else {
				if ( $adhesion_intermittent ) {
					$welcome_type = 'd’adhésion des intermittents';
				} else {
					$welcome_type = 'd’adhésion';
				}
			}
			$welcome_message = sprintf( 'Bienvenue dans l’assistant %s « %%%%nom_site%%%% »', $welcome_type );
		}
		$welcome_message = amapress_replace_mail_placeholders( $welcome_message, null );

		$saison_start_message = wp_unslash(
			Amapress::getOption( $is_adhesion_mode ?
				( $adhesion_intermittent ? 'online_subscription_start_saison_inter_message' : 'online_subscription_start_saison_adh_message' ) :
				'online_subscription_start_saison_message' )
		);
		if ( empty( $saison_start_message ) ) {
			$saison_start_message = sprintf( 'Pour démarrer votre %s pour la saison %s, veuillez renseigner votre adresse mail :',
				$is_adhesion_mode ? 'adhésion' : 'inscription',
				$saison );
		}
		$saison_start_message = amapress_replace_mail_placeholders( $saison_start_message, null, $adh_period );

		?>
        <h2><?php echo $welcome_message; ?></h2>
        <h4>
			<?php
			echo amapress_step_text( $step, $steps_nums, $steps_count );
			echo esc_html( wp_unslash( Amapress::getOption( $adhesion_intermittent ? 'online_subscription_inter_email_step_name' : 'online_subscription_email_step_name' ) ) );
			?>
        </h4>
        <form method="post" action="<?php echo esc_attr( add_query_arg( 'step', 'coords' ) ) ?>" id="inscr_email"
              class="amapress_validate">
			<?php echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption(
				$adhesion_intermittent ? 'online_subscription_inter_email_step_message' : 'online_subscription_email_step_message' ), null ) ); ?>
            <label for="email" style="display: block"><?php echo $saison_start_message ?></label>
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

			$honey_1_id = uniqid( 'amps-firstname' );
			$honey_2_id = uniqid( 'amps-lastname' );
			?>
            <span id="<?php echo $honey_1_id; ?>">
                <label for="amps-firstname">Laisser vide</label>
                <input type="text" value="" name="amps-firstname"
                       id="amps-firstname"
                       size="40" tabindex="-1" autocomplete="off"/>
            </span>
            <span id="<?php echo $honey_2_id; ?>" style="display:none !important; visibility:hidden !important;">
                <label for="amps-lastname">Laisser vide</label>
                <input type="text" value="" name="amps-lastname"
                       id="amps-lastname"
                       size="40" tabindex="-1" autocomplete="off"/>
            </span>
            <input type="submit" value="Valider" class="btn btn-default btn-assist-inscr"/>
        </form>
		<?php

		$hp_css = '#' . $honey_1_id . ' {display:none !important; visibility:hidden !important}';
		wp_register_style( 'inscr-' . $honey_1_id . '-inline', false );
		wp_enqueue_style( 'inscr-' . $honey_1_id . '-inline' );
		wp_add_inline_style( 'inscr-' . $honey_1_id . '-inline', $hp_css );
	} else if ( 'coords' == $step || 'coords_logged' == $step ) {
		if ( Amapress::toBool( $atts['check_honeypots'] ) ) {
			if ( ! empty( $_REQUEST['amps-firstname'] ) || ! isset( $_REQUEST['amps-firstname'] )
			     || ! empty( $_REQUEST['amps-lastname'] ) || ! isset( $_REQUEST['amps-lastname'] ) ) {
				wp_die( 'Spam detected !!!' );
			}
		}
		if ( 'coords_logged' == $step && amapress_is_user_logged_in() ) {
			$email = wp_get_current_user()->user_email;
		} else {
			if ( empty( $_REQUEST['email'] ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$email = sanitize_email( $_REQUEST['email'] );
		}

		$user = get_user_by( 'email', $email );

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
						'Adhésion/Préinscription - Non renouvellement - ' . $amapien->getDisplayName(),
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

		if ( ! Amapress::toBool( $atts['allow_new_mail'] ) && ! $user ) {
			ob_clean();

			return $additional_css . '<p style="font-weight: bold">Les ' . ( $is_adhesion_mode ? 'adhésions' : 'inscriptions' ) . ' avec une nouvelle adresse email ne sont pas autorisées.</p>
<p>Si vous êtes déjà membre, vous avez certainement utilisé une adresse email différente.</p>
<p><a href="' . $start_step_url . '">Changer d’email</a></p>';
		}

		if ( ! $for_logged && 'public' == $key && $user ) {
			ob_clean();

			return $additional_css . '<p style="font-weight: bold">Les ' . ( $is_adhesion_mode ? 'adhésions' : 'inscriptions' ) . ' avec une adresse email existante ne sont pas autorisées.</p>
<p>Si vous êtes déjà membre, veuillez vous connecter.</p>
<p><a href="' . $start_step_url . '">Changer d’email</a></p>';
		}

		if ( $user && ! Amapress::toBool( $atts['allow_coadherents_access'] ) ) {
			$amapien = AmapressUser::getBy( $user );
			if ( $amapien->isCoAdherent() ) {
				return $additional_css . '<p style="font-weight: bold">Les ' . ( $is_adhesion_mode ? 'adhésions' : 'inscriptions' ) . ' ne sont pas autorisées pour les co-adhérents.</p>
<p><a href="' . $start_step_url . '">Changer d’email</a></p>';
			}
		}

		if ( ! $admin_mode && $user ) {
			$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
			if ( empty( $adh_period ) ) {
				ob_clean();

				return ( sprintf( 'Aucune période d\'adhésion n\'est configurée au %s', date_i18n( 'd/m/Y', $adh_period_date ) ) );
			}

			$adh_paiement = AmapressAdhesion_paiement::getForUser( $user->ID, $adh_period_date, false );
			if ( ! empty( $adh_paiement ) ) {
				if ( $check_adhesion_received && $adh_paiement->isNotReceived() ) {
					ob_clean();

					return $additional_css . wp_unslash( Amapress::getOption( 'online_inscr_adhesion_required_message' ) );
				}
				if ( $is_adhesion_mode ) {
					ob_clean();

					return $additional_css . '<p>Vous avez déjà une adhésion !</p>';
				}
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

		$user_message   = 'Vous êtes nouveau, complétez vos coordonnées :';
		$member_message = '<p>Si vous êtes déjà membre, vous avez certainement utilisé une adresse email différente.</p>
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
			if ( ! $user->isPrincipalAdherent() ) {
				$max_cofoyers = 0;
				$max_coadhs   = 0;
			}

			if ( ! $allow_coadherents_adhesion && $amapien->isCoAdherent() ) {
				$activate_adhesion = false;
			}

			$hidaddr            = $amapien->isHiddenFromTrombi();
			$user_message       = 'Vous êtes déjà membre, vérifiez vos coordonnées :';
			$user_firt_name     = $user->first_name;
			$user_last_name     = $user->last_name;
			$user_address       = $amapien->getFormattedAdresse();
			$user_mobile_phones = implode( '/', $amapien->getPhoneNumbers( true ) );
			$user_fix_phones    = implode( '/', $amapien->getPhoneNumbers( false ) );
			$member_message     = '';

			if ( $show_adherents_infos ) {
				$adherents_infos = $amapien->getAdherentInfo( $admin_mode, true, $max_cofoyers > 0 );
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
			$coords_step_title = esc_html( wp_unslash( Amapress::getOption(
				$adhesion_intermittent ? 'online_subscription_inter_coords_step_name' : 'online_subscription_coords_step_name' ) ) );
			echo amapress_step_text( $step, $steps_nums, $steps_count ) . $coords_step_title;
			?>
        </h4>
        <p><?php echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption(
				$adhesion_intermittent ? 'online_subscription_inter_coords_step_message' : 'online_subscription_coords_step_message' ), null ) ); ?></p>
        <p><?php echo $adherents_infos; ?></p>
		<?php echo $adherents_custom_message; ?>
        <p><?php echo $user_message; ?></p>
        <form method="post" id="inscr_coords" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_coords' ) ) ?>">
            <input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>"/>
            <input type="hidden" name="notify_email" value="<?php echo esc_attr( $notify_email ); ?>"/>
            <input type="hidden" name="send_welcome" value="<?php echo esc_attr( $atts['send_welcome'] ); ?>"/>
            <input type="hidden" name="inscr_assistant" value="validate_coords"/>
			<?php if ( $is_mes_contrats ) { ?>
                <input type="hidden" name="coords_next_step" value="contrats"/>
			<?php } elseif ( $activate_agreement ) { ?>
                <input type="hidden" name="coords_next_step" value="agreement"/>
			<?php } elseif ( $activate_adhesion && empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step" value="adhesion"/>
			<?php } ?>
            <input type="hidden" name="inscr_key" value="<?php echo esc_attr( amapress_sha_secret( $key ) ); ?>"/>
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
                    <th style="text-align: left; width: auto"><label
                                for="address">Adresse<?php echo( Amapress::toBool( $atts['address_required'] ) ? '*' : '' ); ?>
                            : </label></th>
                    <td><textarea style="width: 100%" rows="4" id="address" name="address"
                                  class="<?php echo( Amapress::toBool( $atts['address_required'] ) ? 'required' : '' ) ?>"><?php echo esc_textarea( $user_address ); ?></textarea>
                    </td>
                </tr>
	            <?php if ( $allow_trombi_decline ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"></th>
                        <td>
                            <label for="hidaddr"><input type="checkbox" name="hidaddr" <?php checked( $hidaddr ); ?>
                                                        id="hidaddr"/> Ne pas apparaître sur le trombinoscope
                            </label>
                        </td>
                    </tr>
	            <?php } ?>
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
                                                                  id="cofoy1_remove"/> Je ne partage plus de panier
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
                                                                  id="cofoy2_remove"/> Je ne partage plus de panier
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
                                                                  id="cofoy3_remove"/> Je ne partage plus de panier
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
                        <th colspan="2">Co adhérent 1 <em>(Partage du contrat et de son règlement)</em></th>
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
                                                                  id="coadh1_remove"/> Je ne partage plus de panier
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
                        <th colspan="2">Co adhérent 2 <em>(Partage du contrat et de son règlement)</em></th>
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
                                                                  id="coadh2_remove"/> Je ne partage plus de panier
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
                        <th colspan="2">Co adhérent 3 <em>(Partage du contrat et de son règlement)</em></th>
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
                                                                  id="coadh3_remove"/> Je ne partage plus de panier
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
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$user_id = intval( $_REQUEST['user_id'] );

		$amapien = AmapressUser::getBy( $user_id );
		if ( ! $allow_coadherents_adhesion && $amapien->isCoAdherent() ) {
			$activate_adhesion = false;
		}

		$adh_pmt = $user_id ? AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false ) : null;
		?>
        <h4>
			<?php
			echo amapress_step_text( $step, $steps_nums, $steps_count );
			echo esc_html( wp_unslash( Amapress::getOption(
				$adhesion_intermittent ? 'online_subscription_inter_agreement_step_name' : 'online_subscription_agreement_step_name' ) ) );
			?>
        </h4>
        <form method="post" id="agreement" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_agreement' ) ) ?>">
            <input type="hidden" name="inscr_assistant" value="validate_agreement"/>
            <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
			<?php if ( $activate_adhesion && empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step" value="adhesion"/>
			<?php } ?>
            <div class="amap-agreement">
				<?php echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption(
					$adhesion_intermittent ? 'online_subscription_inter_agreement' : 'online_subscription_agreement' ), null ) ); ?>
            </div>
            <p class="accept-agreement">
                <label for="accept_agreement"><input type="checkbox" name="accept" id="accept_agreement"
                                                     class="required" value="1"
                                                     data-msg="Veuillez cocher la case ci-dessous"/> <?php echo esc_html( wp_unslash( Amapress::getOption(
						$adhesion_intermittent ? 'online_subscription_inter_agreement_step_checkbox' : 'online_subscription_agreement_step_checkbox' ) ) ); ?>
                </label>
            </p>
            <p>
                <input style="min-width: 50%" type="submit" class="btn btn-default btn-assist-inscr" value="Valider"/>
            </p>
        </form>
		<?php
	} else if ( 'adhesion' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$user_id = intval( $_REQUEST['user_id'] );

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false );
		if ( $adh_paiement ) {
			ob_clean();

			return ( $additional_css . '<p>Vous avez déjà une adhésion. Vous pouvez aller vers l\'étape <a href="' . $contrats_step_url . '">Contrats</a></p>' );
		}

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( sprintf( 'Aucune période d\'adhésion n\'est configurée au %s', date_i18n( 'd/m/Y', $adh_period_date ) ) );
		}

		$step_name = esc_html( wp_unslash( Amapress::getOption(
			$adhesion_intermittent ? 'online_subscription_inter_adh_step_name' : 'online_subscription_adh_step_name' ) ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . '</h4>';
		echo $adh_period->getOnlineDescription();

		$taxes = get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => 'amps_paiement_category',
			'hide_empty' => false,
		) );
		$ret   = '';
		$ret   .= '<form method="post" id="inscr_adhesion" class="amapress_validate" action="' . esc_attr( add_query_arg( 'step', 'save_adhesion' ) ) . '">';
		$ret   .= '<input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '"/>';
		if ( Amapress::toBool( $atts['allow_adhesion_lieu'] ) ) {
			$ret .= '<div>';
			$ret .= '<p>Lieu de distribution souhaité :</p>';
			foreach ( Amapress::get_lieux() as $lieu ) {
				if ( ! $lieu->isPrincipal() ) {
					continue;
				}

				$ret .= '<label for="adh-lieu-' . $lieu->ID . '"><input class="required" name="amapress_adhesion_lieu" value="' . $lieu->ID . '" type="radio" id="adh-lieu-' . $lieu->ID . '" /> ' . esc_html( $lieu->getTitle() ) . '</label><br/>';
			}
			$ret .= '<label for="adh-lieu-any"><input class="required" name="amapress_adhesion_lieu" value="any" type="radio" id="adh-lieu-any" /> N\'importe lequel</label><br/>';
			$ret .= '<label for="adh-lieu-none"><input class="required" name="amapress_adhesion_lieu" value="none" type="radio" id="adh-lieu-none" /> Aucun</label>';
			$ret .= '</div>';
		}
		for ( $custom_check_ix = 1; $custom_check_ix < 4; $custom_check_ix ++ ) {
			$custom_check = $adh_period->getCustomCheck( $custom_check_ix );
			if ( ! empty( $custom_check ) ) {
				$ret .= '<label for="adh-custom-' . $custom_check_ix . '">
<input name="amapress_adhesion_custom_check' . $custom_check_ix . '" value="1" type="checkbox" id="adh-custom-' . $custom_check_ix . '" /> ' .
				        strip_tags( wp_unslash( $custom_check ), '<em><i><strong><b><br><a>' ) . '</label>';
			}
		}
		if ( Amapress::toBool( $atts['allow_adhesion_message'] ) ) {
			$ret .= '<div>';
			$ret .= '<label for="adh-message" style="display: block">Message personnel :</label><br/>
<textarea id="adh-message" name="amapress_adhesion_message"></textarea>';
			$ret .= '</div>';
		}
		//allow_adhesion_lieu
		//allow_adhesion_message
		$amap_term        = Amapress::getOption( 'adhesion_amap_term' );
		$reseau_amap_term = Amapress::getOption( 'adhesion_reseau_amap_term' );
		$ret              .= '<table style="max-width: 70%">';
		foreach ( $taxes as $tax ) {
			$tax_amount = 0;
			if ( $tax->term_id == $amap_term ) {
				$tax_amount = $adh_period->getMontantAmap( $adhesion_intermittent );
			}
			if ( $tax->term_id == $reseau_amap_term ) {
				$tax_amount = $adh_period->getMontantReseau( $adhesion_intermittent );
			}
			$tax_amount_free = $tax_amount < 0;
			$tax_amount      = $tax_amount < 0 ? 0 : $tax_amount;
			if ( ! $tax_amount_free && ( $tax->term_id == $amap_term || $tax->term_id == $reseau_amap_term ) && abs( $tax_amount ) < 0.001 ) {
				$ret .= '<input type="hidden" id="amapress_pmt_amount-' . $tax->term_id . '" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="amapress_pmt_cat_amount" value="' . $tax_amount . '" />';
			} else {
				$ret .= '<tr>';
				$ret .= '<th style="text-align: left; width: auto">
<label for="amapress_pmt_amount-' . $tax->term_id . '">' . esc_html( $tax->name ) . '</label>
' . ( ! empty( $tax->description ) ? '<p style="font-style: italic; font-weight: normal">' . $tax->description . '</p>' : '' ) . '
</th>';
				if ( ! $tax_amount_free && ( $tax->term_id == $amap_term || $tax->term_id == $reseau_amap_term ) ) {
					$ret .= '<td style="min-width: 8em"><input type="hidden" id="amapress_pmt_amount-' . $tax->term_id . '" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="amapress_pmt_cat_amount" value="' . $tax_amount . '" />' . $tax_amount . '&nbsp;€</td>';
				} else {
					$ret .= '<td style="min-width: 8em"><input type="number" id="amapress_pmt_amount-' . $tax->term_id . '" style="width: 80%;display:inline-block" min="0" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="price required amapress_pmt_cat_amount" value="' . $tax_amount . '" />&nbsp;€</td>';
				}
				$ret .= '</tr>';
			}
		}
		$ret .= '</table>';
		$ret .= '<p>Montant total : <span id="amapress_adhesion_paiement_amount"></span> €</p>';
		$ret .= $adh_period->getPaymentInfo();
		$ret .= '<p>';
		if ( $adh_period->getAllow_Cheque() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="chq" checked="checked" /> Chèque</label>';
		}
		if ( $adh_period->getAllow_Transfer() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="vir"/> Virement</label>';
		}
		if ( $adh_period->getAllow_Cash() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="esp" /> Espèces</label>';
		}
		if ( $adh_period->getAllow_LocalMoney() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="mon" /> Monnaie locale</label>';
		}
		$ret .= '</p>';
		if ( $adh_period->getAllowAmapienInputPaiementsDetails() ) {
			$ret .= '<p><label for="amapress_adhesion_paiement_numero">' . esc_html( wp_unslash( Amapress::getOption( 'online_subscription_adh_num_label' ) ) ) . '</label><input type="text" id="amapress_adhesion_paiement_numero" class="' . ( $paiements_info_required ? 'required' : '' ) . '" name="amapress_adhesion_paiement_numero"/></p>';
			$ret .= '<p><label for="amapress_adhesion_paiement_banque">Banque :</label><input type="text" id="amapress_adhesion_paiement_banque" class="' . ( $paiements_info_required ? 'required' : '' ) . '" name="amapress_adhesion_paiement_banque"/></p>';
		}
		$ret .= '<input type="submit" class="btn btn-default btn-assist-adh" value="Valider"/>';
		$ret .= '</form>';

		echo $ret;

	} else if ( 'save_adhesion' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$user_id = intval( $_REQUEST['user_id'] );

		if ( empty( $_REQUEST['amapress_pmt_amounts'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( sprintf( 'Aucune période d\'adhésion n\'est configurée au %s', date_i18n( 'd/m/Y', $adh_period_date ) ) );
		}

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date );

		delete_user_meta( $user_id, 'amapress_user_no_renew' );
		delete_user_meta( $user_id, 'amapress_user_no_renew_reason' );

		$terms                = array();
		$amounts              = array();
		$amapress_pmt_amounts = isset( $_POST['amapress_pmt_amounts'] ) ? (array) $_POST['amapress_pmt_amounts'] : []; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $amapress_pmt_amounts as $tax_id => $amount ) {
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
		if ( $adhesion_intermittent ) {
			update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_intermittent', 1 );
		} else {
			delete_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_intermittent' );
		}
		if ( isset( $_REQUEST['amapress_adhesion_paiement_banque'] ) ) {
			update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_banque', sanitize_text_field( $_REQUEST['amapress_adhesion_paiement_banque'] ) );
		}
		if ( isset( $_REQUEST['amapress_adhesion_paiement_numero'] ) ) {
			update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_numero', sanitize_text_field( $_REQUEST['amapress_adhesion_paiement_numero'] ) );
		}
		if ( isset( $_REQUEST['amapress_adhesion_paiement_pmt_type'] ) ) {
			$pmt_type = sanitize_key( $_REQUEST['amapress_adhesion_paiement_pmt_type'] );
			if ( 'mon' == $pmt_type || 'esp' == $pmt_type || 'vir' == $pmt_type || 'stp' == $pmt_type ) {
				update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_pmt_type', $pmt_type );
			}
		}
		wp_set_post_terms( $adh_paiement->ID, $terms, 'amps_paiement_category' );

		if ( isset( $_REQUEST['amapress_adhesion_message'] ) ) {
			update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_message', sanitize_textarea_field( $_REQUEST['amapress_adhesion_message'] ) );
		}

		for ( $custom_check_ix = 1; $custom_check_ix < 4; $custom_check_ix ++ ) {
			if ( isset( $_REQUEST[ 'amapress_adhesion_custom_check' . $custom_check_ix ] ) ) {
				update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_custom_check' . $custom_check_ix, 1 );
			} else {
				delete_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_custom_check' . $custom_check_ix );
			}
		}

		if ( isset( $_REQUEST['amapress_adhesion_lieu'] ) ) {
			if ( 'none' == $_REQUEST['amapress_adhesion_lieu'] ) {
				update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_lieu_type', 'none' );
			} elseif ( 'any' == $_REQUEST['amapress_adhesion_lieu'] ) {
				update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_lieu_type', 'any' );
			} else {
				delete_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_lieu_type' );
				update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_lieu', intval( $_REQUEST['amapress_adhesion_lieu'] ) );
			}
		}

		amapress_compute_post_slug_and_title( $adh_paiement->getPost() );

		$adh_paiement = AmapressAdhesion_paiement::getBy( $adh_paiement->ID, true );
		$amapien      = AmapressUser::getBy( $user_id );
		if ( $adhesion_intermittent ) {
			if ( ! $amapien->isIntermittent() ) {
				$amapien->inscriptionIntermittence();
			}
		}

		$adh_paiement->sendConfirmationsAndNotifications(
			Amapress::toBool( $atts['send_adhesion_confirm'] ),
			Amapress::toBool( $atts['send_tresoriers'] ),
			$notify_email,
			true
		);

		$step_name = esc_html( wp_unslash( Amapress::getOption(
			$adhesion_intermittent ? 'online_subscription_inter_adh_valid_step_name' : 'online_subscription_adh_valid_step_name' ) ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . '</h4>';

		$online_subscription_greating_adhesion = wp_unslash( Amapress::getOption(
			$adhesion_intermittent ? 'online_subscription_inter_greating_adhesion' : 'online_subscription_greating_adhesion' ) );

		if ( $adh_paiement->getPeriod()->getWordModelId() ) {
			$print_bulletin                        = Amapress::makeButtonLink(
				add_query_arg( [
					'inscr_assistant' => 'generate_bulletin',
					'adh_id'          => $adh_paiement->ID,
					'inscr_key'       => amapress_sha_secret( $key )
				] ),
				$adhesion_print_button_text, true, true, 'btn btn-default'
			);
			$online_subscription_greating_adhesion = str_replace( '%%print_button%%', $print_bulletin, $online_subscription_greating_adhesion );
		} else {
			$online_subscription_greating_adhesion = str_replace( '%%print_button%%', '', $online_subscription_greating_adhesion );
		}
		echo amapress_replace_mail_placeholders( $online_subscription_greating_adhesion, null );

		if ( ! $is_adhesion_mode ) {
			if ( ! $use_contrat_term ) {
				echo '<p>Vous pouvez maintenant passer commandes<br/>';
			} else {
				echo '<p>Vous pouvez maintenant vous inscrire aux contrats disponibles<br/>';
			}
			echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
		}
	} else if ( 'norenew' == $step ) {
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}
		$user = AmapressUser::getBy( $user_id );
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
					'Adhésion/Préinscription - Non renouvellement - ' . $amapien->getDisplayName(),
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
	} else if ( 'contrats' == $step ) {
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}
		$has_principal_contrat = $user_has_contrat;

		Amapress::setFilterForReferent( false );
		$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
		Amapress::setFilterForReferent( true );
		if ( $show_only_subscribable_inscriptions ) {
			$adhs = array_filter( $adhs,
				function ( $adh ) use ( $all_subscribable_contrats_ids ) {
					/** @var AmapressAdhesion $adh */
					return in_array( $adh->getContrat_instanceId(), $all_subscribable_contrats_ids );
				} );
		}
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
				$online_contrats_step_name = wp_unslash( Amapress::getOption( 'online_contrats_step_name' ) );
				if ( empty( $online_contrats_step_name ) ) {
					if ( ! $use_contrat_term ) {
						$online_contrats_step_name = 'Les commandes';
					} else {
						$online_contrats_step_name = 'Les contrats';
					}
				}
				echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $online_contrats_step_name . '</h4>';
			}
		} else {
			if ( ! $use_contrat_term ) {
				echo '<h4>Les commandes de ' . esc_html( $amapien->getDisplayName() ) . '</h4>';
			} else {
				echo '<h4>Les contrats de ' . esc_html( $amapien->getDisplayName() ) . '</h4>';
			}
		}
		$adh_paiement = null;
		if ( ! $admin_mode ) {
			if ( $allow_coadherents_adhesion || ! $amapien->isCoAdherent() ) {
				$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
				if ( empty( $adh_period ) ) {
					ob_clean();

					return ( sprintf( 'Aucune période d\'adhésion n\'est configurée au %s', date_i18n( 'd/m/Y', $adh_period_date ) ) );
				}

				$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false );

				if ( empty( $adh_paiement ) ) {
					if ( ! $activate_adhesion ) {
						echo '<p><strong>Vous n\'avez pas d\'adhésion sur la ' .
						     esc_html( sprintf( 'Période - %s > %s',
							     date_i18n( 'd/m/Y', $adh_period->getDate_debut() ),
							     date_i18n( 'd/m/Y', $adh_period->getDate_fin() ) ) ) .
						     '</strong></p>';
						$allow_inscriptions = false;
					} else {
						echo amapress_replace_mail_placeholders( wp_unslash( Amapress::getOption(
							$adhesion_intermittent ? 'online_subscription_inter_req_adhesion' : 'online_subscription_req_adhesion' ) ), null );
						echo '<p><form method="get" action="' . esc_attr( $adhesion_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="' . ( $skip_coords ? ( $activate_agreement ? 'agreement' : 'adhesion' ) : ( $for_logged ? 'coords_logged' : 'coords' ) ) . '" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Adhérer" />
</form></p>';
						if ( $track_no_renews ) {
							?>
                            <form method="post"
                                  action="<?php echo esc_attr( add_query_arg( 'step', 'norenew', remove_query_arg( [
								      'contrat_id',
								      'message'
							      ] ) ) ) ?>">
                                <div class="amap-preinscr-norenew">
                                    <h4>Non renouvellement</h4>
									<?php if ( $amapien->getNoRenew() ) {
										echo '<p>Votre non renouvellement a été pris en compte !</p>';
									} ?>
                                    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
                                    <label for="no_renew_reason">Motif (facultatif):</label>
                                    <textarea id="no_renew_reason" name="no_renew_reason"
                                              placeholder="Motif (facultatif)"><?php echo esc_textarea( $amapien->getNoRenewReason() ); ?></textarea>
                                    <input type="submit" name="no_renew" value="Je ne souhaite pas renouveler."/>
                                </div>
                            </form>
							<?php
						}
						if ( $activate_adhesion || $check_adhesion_received ) {
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
								'inscr_key'       => amapress_sha_secret( $key )
							] ),
							$adhesion_print_button_text, true, true, 'btn btn-default'
						);
					}
					if ( $check_adhesion_received_or_previous ) {
						if ( AmapressAdhesion_paiement::hadUserAnyValidated( $user_id ) ) {
							$check_adhesion_received = false;
						}
					}
					if ( Amapress::toBool( $atts['show_adhesion_infos'] ) ) {
						if ( $check_adhesion_received && $adh_paiement->isNotReceived() ) {
							echo sprintf( '<p>Votre adhésion sera valable du %s au %s<br />%s</p>',
								date_i18n( 'd/m/Y', $adh_period->getDate_debut() ),
								date_i18n( 'd/m/Y', $adh_period->getDate_fin() ),
								$print_bulletin );
						} else {
							echo '<p>Votre adhésion est valable jusqu\'au ' . date_i18n( 'd/m/Y', $adh_period->getDate_fin() ) . '.<br />
' . $print_bulletin . '</p>';
						}
					}
				}
			}

			echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_contrats_step_message' ), null ) );
		}

		if ( $show_adherents_infos ) {
			$adherents_infos = $amapien->getAdherentInfo( $admin_mode, true, $max_cofoyers > 0 );
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

		$display_remaining_contrats = true;
		if ( $check_adhesion_received && ( empty( $adh_paiement ) || $adh_paiement->isNotReceived() ) ) {
			echo wp_unslash( Amapress::getOption( 'online_inscr_adhesion_required_message' ) );
			$allow_inscriptions = false;
		}
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
				$coords_step_title = wp_unslash( Amapress::getOption(
					$adhesion_intermittent ? 'online_subscription_inter_coords_step_name' : 'online_subscription_coords_step_name'
				) );
				echo '<p>';
				echo Amapress::makeButtonLink( add_query_arg( [
					'step' => amapress_is_user_logged_in() ? 'coords_logged' : 'coords',
				] ), $coords_step_title, true, false, 'btn btn-default' );
				echo '</p>';
			}
			$editable_adhs = array_filter( $adhs, function ( $adh ) {
				/** @var AmapressAdhesion $adh */
				return $adh->canSelfEdit();
			} );
			if ( $admin_mode || ( $show_editable_inscriptions && ! empty( $editable_adhs ) ) || $show_current_inscriptions || $show_only_subscribable_inscriptions ) {
				if ( ! $admin_mode ) {
					if ( $show_current_inscriptions || $show_only_subscribable_inscriptions ) {
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
				foreach ( ( $admin_mode || $show_current_inscriptions || $show_only_subscribable_inscriptions ? $adhs : $editable_adhs ) as $adh ) {
					$inscription_title = wp_unslash( Amapress::getOption( 'online_subscription_inscription_format' ) );
					if ( empty( $inscription_title ) ) {
						$inscription_title = esc_html( $adh->getTitle() );
					} else {
						$inscription_title = amapress_replace_mail_placeholders(
							$inscription_title, $amapien, $adh
						);
					}
					$print_contrat = '';
					if ( ! empty( $adh->getContrat_instance()->getContratModelDocFileName() ) ) {
						$print_contrat = Amapress::makeButtonLink(
							add_query_arg( [
								'inscr_assistant' => 'generate_contrat',
								'inscr_id'        => $adh->ID,
								'inscr_key'       => amapress_sha_secret( $key )
							] ),
							$contrat_print_button_text, true, true, 'btn btn-default'
						);
					}
					if ( $adh->getContrat_instance()->isPanierVariable() ) {
						$print_contrat .= Amapress::makeButtonLink( add_query_arg( [
							'step'       => 'details_all_delivs',
							'contrat_id' => $adh->ID,
						] ), 'Livraisons', true, true, 'btn btn-default' );

					}
					if ( $admin_mode ) {
						echo '<li style="margin-left: 35px">' . $inscription_title .
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
						if ( 'stp' == $adh->getMainPaiementType() && AmapressAdhesion::TO_CONFIRM == $adh->getStatus() ) {
							$edit_contrat .= '<br/><form method="get" action="' . esc_attr( get_permalink() ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="stripe" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="inscr_id" value="' . $adh->ID . '" />
<input type="submit" value="Payer en ligne" class="btn btn-danger btn-assist-inscr"
 	title="' . esc_attr( 'Payer en ligne et valider l\'inscription. ' . ( $adh->canSelfEdit() ? 'Une fois payée, l\'inscription ne sera plus modifiable.' : '' ) ) . '"/>
</form>';
						}
						if ( $adh->canSelfEdit() ) {
							$inscription_url = add_query_arg( [
								'step'       => 'inscr_contrat_date_lieu',
								'contrat_id' => $adh->getContrat_instanceId()
							] );
							$edit_contrat    .= '<br/>
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
						echo '<li style="margin-left: 35px"><strong>' . $inscription_title . '</strong>' . $coadherents_info . '<br/><em style="font-size: 0.9em">' . $contrat_info . '</em>' . $edit_contrat . '<br/>' . $print_contrat . '</li>';
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
					} elseif ( $allow_inscriptions ) {
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
			if ( ! $admin_mode && Amapress::toBool( $atts['filter_multi_contrat'] ) ) {
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
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $contrat ) use ( $atts, $adh_paiement ) {
					/** @var AmapressContrat_instance $contrat */
					$before_close_hours = 0;
					if ( 0 == $before_close_hours ) {
						$before_close_hours = intval( $atts['before_close_hours'] );
					}
					if ( ! empty( $adh_paiement ) && ! empty( $adh_paiement->getLieuId() ) ) {
						if ( ! in_array( $adh_paiement->getLieuId(), $contrat->getLieuxIds() ) ) {
							return false;
						}
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
					$contrat_title   = wp_unslash( Amapress::getOption( 'online_subscription_contrat_avail_format' ) );
					if ( empty( $contrat_title ) ) {
						$contrat_title = esc_html( $contrat->getTitle() );
					} else {
						$contrat_title = amapress_replace_mail_placeholders(
							$contrat_title, $amapien, $contrat
						);
					}
					if ( $admin_mode ) {
						if ( $contrat->isFull() ) {
							if ( $contrat->hasEquivalentQuant() ) {
								echo '<li style="margin-left: 35px">' . $contrat_title . ', contrat <strong>COMPLET (' . $contrat->getAdherentsEquivalentQuantites() . ' parts)</strong> :<br/>' . Amapress::makeLink( $contrat->getAdminEditLink(), 'Editer ses quota', true, true ) . ' (nb maximum de parts et/ou nb maximum de parts par panier)</li>';
							} else {
								echo '<li style="margin-left: 35px">' . $contrat_title . ', contrat <strong>COMPLET (' . $contrat->getAdherentsCount() . ' amapiens)</strong> :<br/>' . Amapress::makeLink( $contrat->getAdminEditLink(), 'Editer ses quota', true, true ) . ' (nb maximum d\'amapiens/parts et/ou nb maximum d\'amapiens/parts par panier)</li>';
							}
						} else {
							echo '<li style="margin-left: 35px">' . $contrat_title . ' (' . Amapress::makeLink( $contrat->getAdminEditLink(), 'Editer', true, true ) . ') : <br/><a class="button button-secondary" href="' . esc_attr( $inscription_url ) . '">Ajouter une inscription</a></li>';
						}
					} else {
						$deliveries_dates = '';
						if ( count( $contrat->getListe_dates() ) <= $show_max_deliv_dates ) {
							$deliveries_dates = sprintf( ' - Livraison(s) %s -',
								implode( ', ', array_map( function ( $d ) {
									return date_i18n( 'd/m/Y', $d );
								}, $contrat->getListe_dates() ) )
							);
						}
						if ( $show_close_date ) {
							$deliveries_dates .= sprintf( ' - <strong>Clôture inscriptions %s</strong>',
								date_i18n( 'd/m/Y', $contrat->getDate_cloture() )
							);
						}
						echo '<li style="margin-left: 35px">' . $contrat_title . $deliveries_dates . ' (' . $contrat->getModel()->linkToPermalinkBlank( 'plus d\'infos' ) . ') : 
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
						$closed_message = wp_unslash( Amapress::getOption( 'online_inscr_closed_message' ) );
						if ( ! $use_contrat_term ) {
							echo '<p>Les commandes sont closes.</p>' . $closed_message;
						} else {
							echo '<p>Les inscriptions en ligne sont closes.</p>' . $closed_message;
						}
					} else {
						$mes_contrat_href = esc_attr( Amapress::get_mes_contrats_page_href() );
						if ( ! $use_contrat_term ) {
							echo '<p>Vous êtes déjà passé toutes les commandes disponibles.';
							if ( ! empty( $mes_contrat_href ) ) {
								echo "<br />Pour accéder à vos commandes, cliquez <a href='$mes_contrat_href'>ici</a>";
							}
							echo '</p>';
						} else {
							echo '<p>Vous êtes déjà inscrit à tous les contrats.';
							if ( ! empty( $mes_contrat_href ) ) {
								echo "<br />Pour accéder à vos contrats, cliquez <a href='$mes_contrat_href'>ici</a>";
							}
							echo '</p>';
						}
					}
				} else {
					echo '<p>Il/Elle est inscrit à tous les contrats que vous gérez.</p>';
				}
			}
		}

		if ( ! $is_mes_contrats && ! $adhesion_intermittent ) {
			$online_contrats_inscription_distrib_msg = wp_unslash( Amapress::getOption( 'online_contrats_inscription_distrib_msg' ) );
			if ( ! empty( $online_contrats_inscription_distrib_msg ) ) {
				$dist_inscriptions                       = AmapressDistributions::getResponsableDistribForCurrentAdhesions( $user_id, null, $min_contrat_date );
				$online_contrats_inscription_distrib_msg = str_replace( '%%nb_inscriptions%%', count( $dist_inscriptions ), $online_contrats_inscription_distrib_msg );
				$online_contrats_inscription_distrib_msg = str_replace( '%%dates_inscriptions%%',
					empty( $dist_inscriptions ) ? 'aucune' : implode( ', ', array_map(
						function ( $d ) {
							/** @var AmapressDistribution $d */
							return date_i18n( 'd/m/Y', $d->getDate() );
						}, $dist_inscriptions
					) ), $online_contrats_inscription_distrib_msg );
				echo amapress_replace_mail_placeholders( $online_contrats_inscription_distrib_msg, $amapien );
			}
		}

		if ( ! $admin_mode && $has_principal_contrat && ! $is_mes_contrats && ! $for_logged ) {
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
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		$contrat    = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}

		$lieux = $contrat->getLieux();
		if ( empty( $lieux ) ) {
			ob_clean();

			return ( $additional_css . '<p><strong>Attention</strong> : le contrat ' . Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle() ) . ' n\'a aucun lieu de livraison associé. Veuillez corriger ce contrat avant de poursuivre.</p>' );
		}
		$step_name = wp_unslash( Amapress::getOption( 'online_subscription_date_lieu_step_name' ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . ' - ' . esc_html( $contrat->getTitle() ) . '</h4>';
		?>
		<?php echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_subscription_date_lieu_step_message' ), null ) ); ?>
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
			$dates = array_values( $dates );
			if ( empty( $dates ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$first_avail_date = $dates[0];
			$is_started       = $first_avail_date != $first_contrat_date;
			if ( ! $admin_mode ) {
				if ( ! $use_contrat_term ) {
					echo '<p>Les commandes en ligne sont ouvertes du “' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) . '” au “' . date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) . '”, hors de cette période, je prends contact pour préciser ma demande : “<a href="mailto:' . esc_attr( $atts['email'] ) . '">' . esc_html( $atts['email'] ) . '</a>”</p>';
				} else {
					echo '<p>Les inscriptions en ligne sont ouvertes du “' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) . '” au “' . date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) . '”, hors de cette période, je prends contact pour préciser ma demande : “<a href="mailto:' . esc_attr( $atts['email'] ) . '">' . esc_html( $atts['email'] ) . '</a>”</p>';
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
						echo '<p>Je m’inscris pour la période complète : du ' . date_i18n( 'l d F Y', $first_date_dist ) . ' au ' . date_i18n( 'l d F Y', $last_date_dist ) . '
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
						wp_die( $invalid_access_message ); //phpcs:ignore
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
	} else if ( 'stripe_callback' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$user_id = intval( $_REQUEST['user_id'] );
		if ( $for_logged && $user_id != amapress_current_user_id() ) {
			wp_die( $invalid_access_message );  //phpcs:ignore
		}

		if ( empty( $_REQUEST['inscr_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}

		$inscr_id = intval( $_GET['inscr_id'] );
		if ( empty( $_REQUEST['hash'] ) || amapress_sha_secret( "{$user_id}:{$inscr_id}" ) != $_REQUEST['hash'] ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}

		$user_ids = AmapressContrats::get_related_users( $user_id, true );

		$adh = AmapressAdhesion::getBy( $inscr_id );
		if ( empty( $adh ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		if ( in_array( $adh->getAdherentId(), $user_ids )
		     && in_array( $adh->getAdherent2Id(), $user_ids )
		     && in_array( $adh->getAdherent3Id(), $user_ids )
		     && in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( 'Ce contrat n\'est pas à vous !' ); //phpcs:ignore
		}

		$is_success = isset( $_REQUEST['success'] );

		if ( $is_success ) {
			$adh->setStatus( AmapressAdhesion::CONFIRMED );
			$adh->preparePaiements( [
				1 => [
					'num'      => '',
					'date'     => amapress_time(),
					'banque'   => 'Stripe',
					'emetteur' => '',
				]
			], true, AmapressAdhesion_paiement::RECEIVED, false );
		}

		$message = wp_unslash( $is_success ?
			Amapress::getOption( 'online_subscription_stripe_success' ) :
			Amapress::getOption( 'online_subscription_stripe_cancel' ) );
		$message = str_replace( '%%contrats_step_link%%', Amapress::makeButtonLink( $contrats_step_url, 'Poursuivre' ), $message );
		$message = str_replace( '%%contrats_step_href%%', $contrats_step_url, $message );


		$print_contrat = Amapress::makeButtonLink(
			add_query_arg( [
				'inscr_assistant' => 'generate_contrat',
				'inscr_id'        => $adh->ID,
				'inscr_key'       => amapress_sha_secret( $key )
			] ),
			$contrat_print_button_text, true, true, 'btn btn-default'
		);
		$message       = str_replace( '%%print_button%%', $print_contrat, $message );

		$message = amapress_replace_mail_placeholders( $message, AmapressUser::getBy( $user_id ), $adh );

		echo $message; //phpcs:ignore
	} else if ( 'stripe' == $step ) {
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}

		if ( empty( $_REQUEST['inscr_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}

		$user_ids = AmapressContrats::get_related_users( $user_id, true );

		$inscr_id = null;
		$adh      = AmapressAdhesion::getBy( intval( $_GET['inscr_id'] ) );
		if ( empty( $adh ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		if ( in_array( $adh->getAdherentId(), $user_ids )
		     && in_array( $adh->getAdherent2Id(), $user_ids )
		     && in_array( $adh->getAdherent3Id(), $user_ids )
		     && in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( 'Ce contrat n\'est pas à vous !' ); //phpcs:ignore
		}
		$min_stripe_amount = $adh->getContrat_instance()->getStripeMinAmount();
		if ( $min_stripe_amount > 0 ) {
			if ( $adh->getTotalAmount() < $min_stripe_amount ) {
				wp_die( esc_html( sprintf( 'Le paiement en ligne est autorisé à partir de %s', Amapress::formatPrice( $min_stripe_amount ) ) ) );
			}
		}
		$inscr_id = $adh->ID;

		$callback_url = add_query_arg(
			[
				'step'     => 'stripe_callback',
				'user_id'  => $user_id,
				'inscr_id' => $inscr_id,
				'hash'     => amapress_sha_secret( "{$user_id}:{$inscr_id}" ),
			], get_permalink()
		);

		require_once AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php';

		\Stripe\Stripe::setApiKey( $adh->getContrat_instance()->getStripeSecretKey() );

		try {
			$session = \Stripe\Checkout\Session::create( [
				'payment_method_types' => [ 'card' ],
				'client_reference_id'  => $adh->getAdminEditLink(),
				'customer_email'       => $adh->getAdherent()->getEmail(),
				'line_items'           => [
					[
						'name'        => $adh->getTitle(),
						'description' => $adh->getContrat_instance()->getTitle(),
						'amount'      => (int) ( $adh->getTotalAmount() * 100 ),
						'currency'    => 'eur',
						'quantity'    => 1,
					]
				],
				'success_url'          => add_query_arg( 'success', 'T', $callback_url ),
				'cancel_url'           => add_query_arg( 'cancel', 'T', $callback_url ),
			] );


			echo '<script src="https://js.stripe.com/v3/"></script>
  <script>
  const stripe = Stripe(\'' . $adh->getContrat_instance()->getStripePublicKey() . '\');
  stripe.redirectToCheckout({
    sessionId: \'' . $session->id . '\'
  }).then((result) => {
    console.log(result.error.message);
  });
  </script>';
			echo '<p>Réglement en ligne en cours...</p>';
		} catch ( \Stripe\Exception\ApiErrorException $ex ) {
			echo '<p class="error" style="color:red">' . esc_html( $ex->getMessage() ) . '</p>';
		}
	} else if ( 'details_all_paiements' == $step ) {
		if ( ! $show_due_amounts ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}

		echo amapress_get_details_all_paiements( $user_id, $ignore_renouv_delta );
	} else if ( 'details_all_delivs' == $step ) {
		if ( ! $show_delivery_details ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}

		$user_ids = AmapressContrats::get_related_users( $user_id, true );

		$by_prod    = isset( $_GET['by_prod'] );
		$contrat_id = null;
		if ( isset( $_GET['contrat_id'] ) ) {
			$adh = AmapressAdhesion::getBy( intval( $_GET['contrat_id'] ) );
			if ( in_array( $adh->getAdherentId(), $user_ids )
			     && in_array( $adh->getAdherent2Id(), $user_ids )
			     && in_array( $adh->getAdherent3Id(), $user_ids )
			     && in_array( $adh->getAdherent4Id(), $user_ids )
			) {
				wp_die( 'Ce contrat n\'est pas à vous !' ); //phpcs:ignore
			}
			$contrat_id = $adh->getContrat_instanceId();
		}

		echo amapress_get_details_all_deliveries( $user_id, $ignore_renouv_delta, $by_prod, $contrat_id, isset( $_GET['grp_by_grp'] ) );
	} else if ( 'calendar_delivs' == $step ) {
		echo amapress_get_contrats_calendar( $subscribable_contrats );
	} else if ( 'details' == $step ) {
		if ( empty( $_GET['contrat_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}
		$user_ids = AmapressContrats::get_related_users( $user_id, true );

		$adh = AmapressAdhesion::getBy( intval( $_GET['contrat_id'] ) );
		if ( in_array( $adh->getAdherentId(), $user_ids )
		     && in_array( $adh->getAdherent2Id(), $user_ids )
		     && in_array( $adh->getAdherent3Id(), $user_ids )
		     && in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( 'Ce contrat n\'est pas à vous !' ); //phpcs:ignore
		}
		if ( ! empty( $_GET['cancel_inscr_id'] ) ) {
			if ( intval( $_GET['cancel_inscr_id'] ) != $adh->ID ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			if ( ! $adh->canSelfEdit() ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			if ( isset( $_GET['confirm'] ) ) {
				if ( Amapress::toBool( $atts['send_referents'] ) ) {
					$adh->sendReferentsNotificationMail( false, $notify_email, 'cancel' );
				}

				if ( ! wp_delete_post( $adh->ID, true ) ) {
					wp_die( $invalid_access_message ); //phpcs:ignore
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
					'inscr_key'       => amapress_sha_secret( $key )
				] ),
				$contrat_print_button_text, true, true, 'btn btn-default'
			);
		}
		if ( $adh->getContrat_instance()->isPanierVariable() ) {
			$print_contrat .= Amapress::makeButtonLink( add_query_arg( [
				'step'       => 'details_all_delivs',
				'contrat_id' => $adh->ID,
			] ), 'Livraisons', true, true, 'btn btn-default' );

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
		$contrat_info .= '<h3>Options de paiements</h3><p>' . $adh->getProperty( 'option_paiements' ) . '</p><p>' . $adh->getProperty( 'paiements_mention' ) . '</p><p>Ordre: ' . $adh->getProperty( 'paiements_ordre' ) . '</p>';
		$refs_emails  = $adh->getContrat_instance()->getAllReferentsEmails( $adh->getLieuId() );
		$contrat_info .= '<h3>Référents</h3>';
		$contrat_info .= '<p>' . $adh->getProperty( 'referents' ) . '</p>';
		$contrat_info .= '<p>' . Amapress::makeLink( 'mailto:' . urlencode( implode( ',', $refs_emails ) ) . '?subject=' . urlencode( 'Mon inscription ' . $adh->getTitle() ), 'Contacter les référents' ) . '</p>';
		echo '<h4>' . esc_html( $adh->getTitle() ) . '</h4><p>' . $contrat_info . '</p>';
	} else if ( 'inscr_contrat_engage' == $step ) {
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['lieu_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$lieu_id = intval( $_REQUEST['lieu_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$start_date = intval( $_REQUEST['start_date'] );
		$coadhs     = isset( $_REQUEST['coadhs'] ) ? $_REQUEST['coadhs'] : []; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $coadhs ) ) {
			$coadhs = explode( ',', $coadhs );
		}
		$coadhs = array_map( 'intval', $coadhs );
		$coadhs = implode( ',', $coadhs );

		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
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

		$step_name = wp_unslash( Amapress::getOption( 'online_subscription_panier_step_name' ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . ' - ' . esc_html( $contrat->getTitle() ) . '</h4>';

		echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_subscription_panier_step_message' ), null ) );

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
			$date_ix = 0;
			foreach ( $dates as $date ) {
				$columns[] = array(
					'title'     => date_i18n( 'd/m/y', $date ),
					'data'      => 'd-' . $date,
					'className' => ( $date_ix ++ % 2 == 0 ) ? 'date-even' : 'date-odd',
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
                var $element = $(element);
                var parent = $element.closest("form");
                if(!$element.data(\'reval\')) {
			        var fields = $(".' . $grp_class_name . '", parent);
			        fields.data(\'reval\', true).valid();
			        fields.data(\'reval\', false);
			    }
                var sumOfVals = 0;
                jQuery(parent).find(".quant-var.' . $grp_class_name . '").each(function () {
                    sumOfVals = sumOfVals + parseInt(jQuery(this).val());
                });
                $element.data("mlcnt",sumOfVals);
                if (0 === (sumOfVals % ' . $grp_conf['multiple'] . ')) return true;
                return false;
            },
            function(params, element) {
                return "La quantité pour ' . esc_js( $grp_conf['display'] ) . ' doit être multiple de ' . $grp_conf['multiple'] . '. Actuellement: " + $(element).data("mlcnt") + "<br/>";
            }
        );
    });
    //]]>
</script>';
			}

			echo '<style type="text/css">.quant-var-recopier{text-shadow: none !important; text-decoration: none !important;}.panier-mod-produit-label{display: inline-block;white-space: normal;word-wrap: break-word; max-width: ' . $atts['max_produit_label_width'] . ';}</style>';

			$js_options = array(
				'bSort'          => false,
				'paging'         => false,
				'searching'      => true,
				'bAutoWidth'     => true,
				'responsive'     => false,
				'init_as_html'   => true,
				'scrollCollapse' => true,
				'scrollX'        => true,
				'scrollY'        => $atts['paniers_modulables_editor_height'],
				'fixedColumns'   => array( 'leftColumns' => 2 ),
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
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$user_id = intval( $_REQUEST['user_id'] );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$start_date = intval( $_REQUEST['start_date'] );

		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$next_step_url = add_query_arg( [ 'step' => 'inscr_contrat_create' ] );

		$pay_at_deliv = [];
		$step_name    = wp_unslash( Amapress::getOption( 'online_subscription_pay_step_name' ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . '</h4>';
		echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_subscription_pay_step_message' ), null ) );

		$by_month_totals = [];
		if ( $contrat->isPanierVariable() ) {
			$panier_vars = isset( $_REQUEST['panier_vars'] ) ? (array) $_REQUEST['panier_vars'] : []; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( empty( $panier_vars ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}

			$columns         = [];
			$columns['date'] = array(
				'title' => 'Date',
				'data'  => array(
					'_'    => 'date',
					'sort' => 'date_sort',
				)
			);
			$data            = [];

			$total         = 0;
			$chosen_quants = [];
			foreach ( $panier_vars as $date_k => $quant_factors ) {
				$date_values = [];
				$row         = [
					'date'      => date_i18n( 'd/m/Y', $date_k ),
					'date_sort' => date_i18n( 'Y-m-d', $date_k ),
				];
				foreach ( (array) $quant_factors as $quant_k => $factor_v ) {
					$q_id   = intval( $quant_k );
					$factor = floatval( $factor_v );
					if ( $factor <= 0 ) {
						unset( $panier_vars[ $date_k ][ $quant_k ] );
						continue;
					}
					$quant = AmapressContrat_quantite::getBy( $q_id );

					$columns["q$q_id"] = array(
						'title' => $quant->getTitle(),
						'data'  => "q$q_id",
					);
					$row["q$q_id"]     = $factor;

					$date_values[] = $quant->getFormattedTitle( $factor, true );
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
					$data[] = $row;
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
			if ( Amapress::toBool( $atts['use_quantite_tables'] ) ) {
				amapress_echo_datatable( 'quants-recap', $columns, $data,
					array(
						'paging'       => false,
						'searching'    => false,
						'nowrap'       => true,
						'responsive'   => false,
						'scrollX'      => false,
						'init_as_html' => true,
					),
					array(
						Amapress::DATATABLES_EXPORT_EXCEL
					) );
			} else {
				echo '<ul style="list-style-type: square">';
				foreach ( $chosen_quants as $dt => $quant_descs ) {
					echo '<li style="margin-left: 35px">' . esc_html( date_i18n( 'd/m/Y', intval( $dt ) ) );
					echo '<ul style="list-style-type: disc">';
					foreach ( $quant_descs as $quant_desc ) {
						echo '<li style="margin-left: 15px">' . $quant_desc . '</li>';
					}
					echo '</ul>';
					echo '</li>';
				}
				echo '</ul>';
			}
		} else {
			$quants = isset( $_REQUEST['quants'] ) ? (array) $_REQUEST['quants'] : []; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! is_array( $quants ) ) {
				$quants = [ $quants ];
			}

			if ( empty( $quants ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}

			$factors = isset( $_REQUEST['factors'] ) ? (array) $_REQUEST['factors'] : []; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$dates = $contrat->getListe_dates();
			$dates = array_filter( $dates, function ( $d ) use ( $start_date ) {
				return $d >= $start_date;
			} );

			$total         = 0;
			$chosen_quants = [];
			$serial_quants = [];
			foreach ( $quants as $q ) {
				$q_id = intval( $q );
				if ( empty( $q_id ) ) {
					continue;
				}

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
				$chosen_quants[] = $quant->getFormattedTitle( $factor, true );
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
					echo '<p style="margin-bottom: 0">Vous avez choisi l\'option “' . ( $chosen_quants[0] ) . '” de la commande ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				} else {
					echo '<p style="margin-bottom: 0">Vous avez choisi l\'option “' . ( $chosen_quants[0] ) . '” du contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant ' . ( $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
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
					echo '<li style="margin-left: 35px">' . $q . '</li>';
				}
				echo '</ul>';
			}
		}

		echo wp_unslash( $contrat->getPaiementsMention() );

		if ( $contrat->getManage_Cheques() ) {
			if ( ! $admin_mode ) {
				echo '<p style="margin-bottom: 0">Propositions de règlement :</p>';
			} else {
				echo '<p style="margin-bottom: 0">Propositions de règlement :</p>';
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
					$max_cheques = count( array_filter( $by_month_totals, function ( $v ) {
						return $v > 0;
					} ) );
					if ( $contrat->getPayByMonthOnly() || 1 == $max_cheques ) {
						$possible_cheques = [ $max_cheques ];
					} else {
						$possible_cheques = [ 1, $max_cheques ];
					}
				}
				foreach ( $possible_cheques as $nb_cheque ) {
					if ( $total / $nb_cheque < $min_cheque_amount ) {
						continue;
					}

					$checked = checked( $edit_inscription && 'chq' == $edit_inscription->getMainPaiementType() && $edit_inscription->getPaiements() == $nb_cheque, true, false );
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
					} elseif ( $contrat->hasCustomMultiplePaiements() ) {
						$amounts   = $contrat->getTotalAmountByCustom( $nb_cheque, $total );
						$cheques   = implode( '|', array_map( function ( $amount ) {
							return Amapress::formatPrice( $amount, true );
						}, $amounts ) );
						$chq_label = implode( ' ; ', array_map( function ( $amount ) {
							return sprintf( "1 chèque de %0.2f €",
								$amount );
						}, $amounts ) );

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
			if ( $total > 0 && $contrat->getAllow_Stripe() ) {
				$min_stripe_amount = $contrat->getStripeMinAmount();
				if ( $total > $min_stripe_amount ) {
					$checked = checked( $edit_inscription && 'stp' == $edit_inscription->getMainPaiementType(), true, false );
					echo "<label for='cheques-stp' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-stp' $checked value='-5' class='input-nb-cheques required' />Paiement en ligne</label><br/>";
				}
			}
			if ( $contrat->getAllow_Transfer() ) {
				$checked = checked( $edit_inscription && 'vir' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-vir' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-vir' $checked value='-2' class='input-nb-cheques required' />Par virement</label><br/>";
			}
			if ( $contrat->getAllow_LocalMoney() ) {
				$checked = checked( $edit_inscription && 'mon' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-mon' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-mon' $checked value='-4' class='input-nb-cheques required' />En monnaie locale</label><br/>";
			}
			if ( $contrat->getAllow_Prelevement() ) {
				if ( $total > 0 ) {
					$possible_cheques = $contrat->getPossiblePaiements();
					if ( $contrat->getPayByMonth() ) {
						$max_cheques = count( array_filter( $by_month_totals, function ( $v ) {
							return $v > 0;
						} ) );
						if ( $contrat->getPayByMonthOnly() || 1 == $max_cheques ) {
							$possible_cheques = [ $max_cheques ];
						} else {
							$possible_cheques = [ 1, $max_cheques ];
						}
					}
					foreach ( $possible_cheques as $nb_cheque ) {
						if ( $total / $nb_cheque < $min_cheque_amount ) {
							continue;
						}

						$nb_cheque_val = $nb_cheque + 100;
						$checked       = checked( $edit_inscription && 'prl' == $edit_inscription->getMainPaiementType() && $edit_inscription->getPaiements() == $nb_cheque, true, false );
						if ( $contrat->getPayByMonth() ) {
							if ( 1 === $nb_cheque ) {
								$cheques   = Amapress::formatPrice( $total, true );
								$chq_label = sprintf( "1 prélèvement de %0.2f €", $total );
							} else {
								$cheques   = implode( '|', array_map( function ( $month_amount ) {
									return Amapress::formatPrice( $month_amount, true );
								}, $by_month_totals ) );
								$chq_label = implode( ' ; ', array_map( function ( $month, $month_amount ) {
									return sprintf( "%s: 1 prélèvement de %0.2f €",
										$month,
										$month_amount );
								}, array_keys( $by_month_totals ), array_values( $by_month_totals ) ) );
							}
							echo "<label for='prlv-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='prlv-$nb_cheque' value='{$nb_cheque_val}' class='input-nb-cheques required' />$chq_label</label><br/>";
						} elseif ( $contrat->hasCustomMultiplePaiements() ) {
							$amounts   = $contrat->getTotalAmountByCustom( $nb_cheque, $total );
							$cheques   = implode( '|', array_map( function ( $amount ) {
								return Amapress::formatPrice( $amount, true );
							}, $amounts ) );
							$chq_label = implode( ' ; ', array_map( function ( $amount ) {
								return sprintf( "1 prélèvement de %0.2f €",
									$amount );
							}, $amounts ) );
							echo "<label for='prlv-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='prlv-$nb_cheque' value='$nb_cheque_val' class='input-nb-cheques required' />$chq_label</label><br/>";
						} else {
							$cheques            = $contrat->getChequeOptionsForTotal( $nb_cheque, $total, 'prélèvement' );
							$option             = esc_html( $cheques['desc'] );
							$cheque_main_amount = esc_attr( Amapress::formatPrice( $cheques['main_amount'] ) );
							$last_cheque        = esc_attr( Amapress::formatPrice( ! empty( $cheques['remain_amount'] ) ? $cheques['remain_amount'] : $cheques['main_amount'] ) );
							$chq_label          = '';
							if ( $cheque_main_amount != $last_cheque ) {
								$chq_label = "$nb_cheque prélèvement(s) : ";
							}
							echo "<label for='prlv-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='prlv-$nb_cheque' value='$nb_cheque_val' class='input-nb-cheques required' />$chq_label$option</label><br/>";
						}
					}
				}
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
        if (nb_cheques >= 100)
            nb_cheques = -1;
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
<th>' . esc_html( wp_unslash( Amapress::getOption( 'online_subscription_pay_num_label' ) ) ) . '</th>
<th>Banque</th>
<th>Emetteur</th>
<th>Montant</th>
</thead><tbody>';
				Amapress::setFilterForReferent( false );
				$edit_all_paiements = $edit_inscription ? $edit_inscription->getAllPaiements() : null;
				Amapress::setFilterForReferent( true );
				$req = ( $paiements_info_required ? 'required' : '' );
				for ( $i = 1; $i <= 12; $i ++ ) {
					$edit_paiement       = $edit_all_paiements && isset( $edit_all_paiements[ $i - 1 ] ) ? $edit_all_paiements[ $i - 1 ] : null;
					$paiements_raw_dates = $contrat->getPaiements_Liste_dates();
					if ( ! $admin_mode ) {
						$paiements_raw_dates = array_filter( $paiements_raw_dates,
							function ( $d ) {
								return $d > Amapress::start_of_week( amapress_time() );
							} );
					}
					$paiements_dates = array_map(
						function ( $d ) use ( $edit_paiement ) {
							$selected = selected( $edit_paiement && Amapress::start_of_day( $edit_paiement->getDate() ) == Amapress::start_of_day( $d ), true, false );

							return '<option ' . $selected . ' value="' . esc_attr( $d ) . '">' . esc_html( date_i18n( 'd/m/Y', $d ) ) . '</option>';
						}, $paiements_raw_dates
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
		echo '<p>Information pour le réglement :<br/>';
		echo 'Ordre: ' . wp_unslash( $contrat->getPaiementsOrdre() );
		echo '<br />' . wp_unslash( $contrat->getPaiementsMention() );
		echo '</p>';
		echo '<br />';
		if ( ! empty( $pay_at_deliv ) ) {
			echo '<p><strong>Produits payables à la livraison</strong> : ' . esc_html( implode( ', ', $pay_at_deliv ) ) . '</p>';
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
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$user_id = intval( $_REQUEST['user_id'] );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['lieu_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$lieu_id = intval( $_REQUEST['lieu_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$start_date = intval( $_REQUEST['start_date'] );
		$coadhs     = isset( $_REQUEST['coadhs'] ) ? $_REQUEST['coadhs'] : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $coadhs ) ) {
			$coadhs = explode( ',', $coadhs );
		}
		$coadhs = array_map( 'intval', $coadhs );

		$message = sanitize_textarea_field( isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' );

		$amapien = AmapressUser::getBy( $user_id );
		$lieu    = AmapressLieu_distribution::getBy( $lieu_id );
		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( ! $amapien || ! $lieu || ! $contrat ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}

		if ( $contrat->getManage_Cheques() && empty( $_REQUEST['cheques'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$cheques = ! isset( $_REQUEST['cheques'] ) ? 0 : intval( $_REQUEST['cheques'] );
		if ( empty( $_REQUEST['quants'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$quants = unserialize( stripslashes( $_REQUEST['quants'] ) ); //phpcs:ignore
		if ( empty( $quants ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
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
			'amapress_adhesion_paiements'        => ( $cheques < 0 ? 1 : ( $cheques > 0 ? ( $cheques >= 100 ? $cheques - 100 : $cheques ) : 0 ) ),
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
		if ( - 5 == $cheques ) {
			$meta['amapress_adhesion_pmt_type'] = 'stp';
		}
		if ( $cheques >= 100 ) {
			$meta['amapress_adhesion_pmt_type'] = 'prl';
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
		if ( $edit_inscription && $cheques > 0 && $cheques < 100 ) {
			delete_post_meta( $new_id, 'amapress_adhesion_pmt_type' );
		}

		Amapress::setFilterForReferent( false );
		$inscription = AmapressAdhesion::getBy( $new_id, true );
		Amapress::setFilterForReferent( true );
		if ( $inscription->getContrat_instance()->getManage_Cheques() ) {
			if ( 'stp' != $inscription->getMainPaiementType() ) {
				$inscription->preparePaiements( isset( $_REQUEST['pmt'] ) ? (array) $_REQUEST['pmt'] : [] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}

		if ( ! $admin_mode || isset( $_REQUEST['inscr_confirm_mail'] ) ) {
			if ( Amapress::toBool( $atts['send_contrat_confirm'] ) ) {
				$inscription->sendConfirmationMail();
			}
		}

		if ( ! $admin_mode ) {
			if ( Amapress::toBool( $atts['send_referents'] ) ) {
				$inscription->sendReferentsNotificationMail( false, $notify_email,
					$edit_inscription ? 'modif' : 'new' );
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
			$step_name                          = esc_html( wp_unslash( Amapress::getOption( 'online_contrats_end_step_name' ) ) );
			echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . '</h4>';

			$online_contrats_end_step_message      = wp_unslash( Amapress::getOption( 'online_contrats_end_step_message' ) );
			$online_contrats_end_step_edit_message = wp_unslash( Amapress::getOption( 'online_contrats_end_step_edit_message' ) );
			echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_contrats_end_confirm_msg' ), null ) );
			if ( Amapress::toBool( $atts['send_contrat_confirm'] ) ) {
				echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_contrats_end_confirm_mail_msg' ), null ) );
			}
			$print_contrat = '';
			if ( ! empty( $inscription->getContrat_instance()->getContratModelDocFileName() ) ) {
				$print_contrat = Amapress::makeButtonLink(
					add_query_arg( [
						'inscr_assistant' => 'generate_contrat',
						'inscr_id'        => $inscription->ID,
						'inscr_key'       => amapress_sha_secret( $key )
					] ),
					$contrat_print_button_text, true, true, 'btn btn-default'
				);
			}
			if ( $inscription->getContrat_instance()->isPanierVariable() ) {
				$print_contrat .= Amapress::makeButtonLink( add_query_arg( [
					'step'       => 'details_all_delivs',
					'contrat_id' => $inscription->ID,
				] ), 'Livraisons', true, true, 'btn btn-default' );
			}
			$online_contrats_end_step_message      = str_replace( '%%print_button%%', $print_contrat, $online_contrats_end_step_message );
			$online_contrats_end_step_edit_message = str_replace( '%%print_button%%', $print_contrat, $online_contrats_end_step_edit_message );
			if ( 'stp' == $inscription->getMainPaiementType() && AmapressAdhesion::TO_CONFIRM == $inscription->getStatus() ) {
				$online_subscription_contrat_end_stripe = wp_unslash( Amapress::getOption( 'online_subscription_contrat_end_stripe' ) );
				$online_subscription_contrat_end_stripe = amapress_replace_mail_placeholders(
					$online_subscription_contrat_end_stripe,
					$inscription->getAdherent(), $inscription
				);

				echo $online_subscription_contrat_end_stripe;
				echo '<form method="get" action="' . esc_attr( get_permalink() ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="stripe" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="inscr_id" value="' . $inscription->ID . '" />
<input type="submit" value="Payer en ligne et valider l\'inscription" class="btn btn-danger btn-assist-inscr" />
</form>';
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

			if ( $is_mes_contrats ) {
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
					$online_contrats_end_continue_msg = wp_unslash( Amapress::getOption( 'online_contrats_end_continue_msg' ) );
					$online_contrats_end_continue_msg = str_replace( '%%remaining_contrats%%', $user_subscribable_contrats_display, $online_contrats_end_continue_msg );
					$remain_contrats_list             = '<ul style="list-style-type: disc; display: block">';
					$remain_contrats_list             .= implode( '', array_map(
						function ( $c ) {
							/** @var AmapressContrat_instance $c */
							return '<li style="margin-left: 35px">' . esc_html( $c->getModelTitleWithSubName() ) . '</li>';
						}, $user_subscribable_contrats
					) );
					$remain_contrats_list             .= '</ul>';
					$online_contrats_end_continue_msg = str_replace( '%%remaining_contrats_list%%', $remain_contrats_list, $online_contrats_end_continue_msg );


					echo amapress_replace_mail_placeholders( $online_contrats_end_continue_msg, $inscription->getAdherent() );
					//
					echo '<br />';
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

			if ( ! $is_mes_contrats && ! $adhesion_intermittent ) {
				$online_contrats_inscription_distrib_msg = wp_unslash( Amapress::getOption( 'online_contrats_inscription_distrib_msg' ) );
				if ( ! empty( $online_contrats_inscription_distrib_msg ) ) {
					$dist_inscriptions                       = AmapressDistributions::getResponsableDistribForCurrentAdhesions( $user_id, null, $min_contrat_date );
					$online_contrats_inscription_distrib_msg = str_replace( '%%nb_inscriptions%%', count( $dist_inscriptions ), $online_contrats_inscription_distrib_msg );
					$online_contrats_inscription_distrib_msg = str_replace( '%%dates_inscriptions%%',
						empty( $dist_inscriptions ) ? 'aucune' : implode( ', ', array_map(
							function ( $d ) {
								/** @var AmapressDistribution $d */
								return date_i18n( 'd/m/Y', $d->getDate() );
							}, $dist_inscriptions
						) ), $online_contrats_inscription_distrib_msg );
					echo amapress_replace_mail_placeholders( $online_contrats_inscription_distrib_msg, $inscription->getAdherent() );
				}
			}

			if ( ! $admin_mode && ! $is_mes_contrats ) {
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
		echo '<h4>' . wp_unslash( Amapress::getOption( 'online_final_step_name' ) ) . '</h4>';
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
 * @param int[]|int $contrats_ids
 *
 * @return string
 */
function amapress_get_details_all_deliveries(
	$user_id,
	$ignore_renouv_delta,
	$by_prod,
	$contrats_ids = null,
	$group_by_group = true,
	$for_mail = false
) {
	Amapress::setFilterForReferent( false );
	$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
	Amapress::setFilterForReferent( true );

	if ( empty( $contrats_ids ) ) {
		$contrats_ids = [];
	} elseif ( ! is_array( $contrats_ids ) ) {
		$contrats_ids = [ $contrats_ids ];
	} else {
		$contrats_ids = array_values( $contrats_ids );
	}

	$is_single_producteur = 1 === count( $contrats_ids );
	if ( ! empty( $contrats_ids ) ) {
		$adhs = array_filter( $adhs, function ( $adh ) use ( $contrats_ids ) {
			/** @var AmapressAdhesion $adh */
			return in_array( $adh->getContrat_instanceId(), $contrats_ids );
		} );
	}

	$has_groups       = false;
	$contrat_instance = null;
	$print_title      = 'Récapitulatif des livraisons';
	if ( $is_single_producteur ) {
		$contrat_instance = AmapressContrat_instance::getBy( $contrats_ids[0] );
		if ( ! empty( $adhs ) ) {
			$print_title .= ' : ' . array_values( $adhs )[0]->getTitle();
		} else {
			$print_title .= ' : ADHESION INVALIDE OU EN BROUILLON !';
		}
	}
	$ret     = '<h4>' . esc_html( $print_title ) . '</h4>';
	$columns = [];
	if ( $is_single_producteur ) {
		$columns[] = array(
			'title' => 'Date',
			'data'  => array(
				'_'    => 'date_d',
				'sort' => 'date',
			)
		);
		if ( $contrat_instance->hasGroups() ) {
			$has_groups = true;
			$columns[]  = array(
				'title' => 'Groupe',
				'data'  => array(
					'_'    => 'group',
					'sort' => 'group',
				)
			);
		}
	} elseif ( $by_prod ) {
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
	if ( ! $has_groups ) {
		$group_by_group = false;
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
						if ( $has_groups ) {
							$row['group'] = $quant->getGroupName();
							$row['desc']  = $quant->getTitleWithoutGroup();
						} else {
							$row['desc'] = $quant->getTitle();
						}

						$row['prod'] = $adh->getContrat_instance()->getModel()->getTitle()
						               . '<br />'
						               . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
						$row['fact'] = $paniers[ $date ][ $quant->ID ];
						if ( $group_by_group ) {
							$row['fact_mult'] = $quant->getGroupMultiple();
						}

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
					if ( $has_groups ) {
						$row['group'] = $quant->getGroupName();
						$row['desc']  = $quant->getTitleWithoutGroup();
					} else {
						$row['desc'] = $quant->getTitleWithoutFactor();
					}

					$row['prod'] = $adh->getContrat_instance()->getModel()->getTitle()
					               . '<br />'
					               . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
					$row['fact'] = $quant->getFactor();
					if ( $group_by_group ) {
						$row['fact_mult'] = $quant->getGroupMultiple();
					}

					$row['total_d'] = Amapress::formatPrice( $quant->getPrice(), true );
					$row['total']   = $quant->getPrice();
					$data[]         = $row;
				}
			}
		}
	}

	if ( $has_groups && $group_by_group ) {
		$grouped_data = [];
		$groupe_mult  = [];
		foreach ( $data as $row ) {
			$key                 = $row['date_d'] . $row['prod'] . $row['group'];
			$groupe_mult[ $key ] = $row['fact_mult'];
			if ( isset( $grouped_data[ $key ] ) ) {
				foreach ( $row as $k => $v ) {
					if ( 'desc' == $k ) {
						$grouped_data[ $key ][ $k ] .= '<br/> + <strong>' . $row['fact'] . '</strong> x ' . $v;
						continue;
					}
					if ( 'desc' == $k || 'date' == $k || 'date_d' == $k || 'prod' == $k || 'group' == $k ) {
						continue;
					}
					if ( ! is_numeric( $v ) ) {
						if ( ! empty( $grouped_data[ $key ][ $k ] ) && ! empty( $v ) ) {
							$grouped_data[ $key ][ $k ] .= ' + ' . $v;
						} else {
							$grouped_data[ $key ][ $k ] .= $v;
						}
					} else {
						$grouped_data[ $key ][ $k ] += $v;
					}
				}
				$grouped_data[ $key ]['total_d'] = Amapress::formatPrice( $grouped_data[ $key ]['total'], true );
			} else {
				$row['desc']          = '<strong>' . $row['fact'] . '</strong> x ' . $row['desc'];
				$row['fact']          = (float) $row['fact'];
				$grouped_data[ $key ] = $row;
			}
		}
		foreach ( $grouped_data as $key => $row ) {
			$grouped_data[ $key ]['fact'] = $row['fact'] / (float) $groupe_mult[ $key ];
		}
		$data = $grouped_data;
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
	if ( ! $for_mail && $has_groups ) {
		$ret .= '<p>';
		if ( $group_by_group ) {
			$ret .= Amapress::makeLink( remove_query_arg( 'grp_by_grp' ), 'Ne pas grouper les produits' );
		} else {
			$ret .= Amapress::makeLink( add_query_arg( 'grp_by_grp', 'T' ), 'Grouper les produits' );
		}
		$ret .= '</p>';
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
function amapress_get_details_all_paiements(
	$user_id, $ignore_renouv_delta,
	$show_dates_encaissement = false,
	$show_dates_livraison = false
) {
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
	if ( $show_dates_encaissement ) {
		$columns[] = array(
			'title' => 'Dates encaissement',
			'data'  => array(
				'_'    => 'date_enc',
				'sort' => 'date_enc',
			)
		);
	}
	if ( $show_dates_livraison ) {
		$columns[] = array(
			'title' => 'Dates livraison',
			'data'  => array(
				'_'    => 'date_liv',
				'sort' => 'date_liv',
			)
		);
	}
	$columns[] = array(
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
		$row['date_enc']  = implode( ', ', array_map( function ( $d ) {
			return date_i18n( 'd/m/Y', $d );
		}, $adh->getContrat_instance()->getPaiements_Liste_dates() ) );
		$row['date_liv']  = implode( ', ', array_map( function ( $d ) {
			return date_i18n( 'd/m/Y', $d );
		}, array_filter( $adh->getContrat_instance()->getListe_dates(), function ( $d ) {
			return Amapress::start_of_day( $d ) >= Amapress::start_of_day( amapress_time() );
		} ) ) );
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
