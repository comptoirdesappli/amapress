<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_init', function () {
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_coords' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
			if ( ! isset( $_REQUEST['inscr_key'] ) || sanitize_text_field( $_REQUEST['inscr_key'] ) != amapress_sha_secret( $request_key ) ) {
				wp_die( __( 'Accès interdit', 'amapress' ) );
			}
		}

		amapress_checkhoneypots();

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

		if ( isset( $_REQUEST['coadh1_remove'] ) ) {
			$amapien = AmapressUser::getBy( $user_id, true );
			$amapien->removeCoadherent( $amapien->getCoAdherent1Id(), $notify_email );
		} elseif ( ! empty( $_REQUEST['coadh1_email'] ) ) {
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
		}

		if ( isset( $_REQUEST['coadh2_remove'] ) ) {
			$amapien = AmapressUser::getBy( $user_id, true );
			$amapien->removeCoadherent( $amapien->getCoAdherent2Id(), $notify_email );
		} elseif ( ! empty( $_REQUEST['coadh2_email'] ) ) {
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
		}

		if ( isset( $_REQUEST['coadh3_remove'] ) ) {
			$amapien = AmapressUser::getBy( $user_id, true );
			$amapien->removeCoadherent( $amapien->getCoAdherent3Id(), $notify_email );
		} elseif ( ! empty( $_REQUEST['coadh3_email'] ) ) {
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
				sprintf( __( 'Réponses nouvel adhérent - %s (%s)', 'amapress' ), $user_display_name, $user_email ),
				wpautop(
					sprintf( __( "Bonjour,\n\nLe nouvel ahdérent %s a répondu aux questions:\n%s%s\n\n%s", 'amapress' ), $user_link, ! Amapress::isHtmlEmpty( $quest1 ) ? "- $quest1:\n$quest1_answser\n" : '', ! Amapress::isHtmlEmpty( $quest2 ) ? "- $quest2:\n$quest2_answser\n" : '', get_bloginfo( 'name' ) )
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
				wp_die( __( 'Accès interdit', 'amapress' ) );
			}
		}

		$inscr_id = isset( $_REQUEST['inscr_id'] ) ? intval( $_REQUEST['inscr_id'] ) : 0;
		if ( empty( $inscr_id ) ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}
		$adhesion = AmapressAdhesion::getBy( $inscr_id );
		if ( empty( $adhesion ) ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}

		$full_file_name = $adhesion->generateContratDoc( false );
		if ( empty( $full_file_name ) ) {
			wp_die( __( 'Ne peut pas générer le fichier DOCX', 'amapress' ) );
		}
		$file_name = basename( $full_file_name );
		Amapress::sendDocumentFile( $full_file_name, $file_name );
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'generate_bulletin' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
			if ( ! isset( $_REQUEST['inscr_key'] ) || sanitize_text_field( $_REQUEST['inscr_key'] ) != amapress_sha_secret( $request_key ) ) {
				wp_die( __( 'Accès interdit', 'amapress' ) );
			}
		}

		$adh_id = intval( $_REQUEST['adh_id'] );
		if ( empty( $adh_id ) ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}
		$adhesion_paiement = AmapressAdhesion_paiement::getBy( $adh_id );
		if ( empty( $adhesion_paiement ) ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}

		$full_file_name = $adhesion_paiement->generateBulletinDoc( false );
		$file_name      = basename( $full_file_name );
		Amapress::sendDocumentFile( $full_file_name, $file_name );
	}

	if ( isset( $_REQUEST['inscr_assistant'] ) && 'calendar_save' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
			if ( ! isset( $_REQUEST['inscr_key'] ) || sanitize_text_field( $_REQUEST['inscr_key'] ) != amapress_sha_secret( $request_key ) ) {
				wp_die( __( 'Accès interdit', 'amapress' ) );
			}
		}

		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}
		$user_id = intval( $_REQUEST['user_id'] );

		if ( empty( $_REQUEST['inscr_id'] ) ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}

		$inscr_id = intval( $_REQUEST['inscr_id'] );

		if ( empty( $_REQUEST['hash'] ) || amapress_sha_secret( "{$user_id}:{$inscr_id}" ) != $_REQUEST['hash'] ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}

		$user_ids = AmapressContrats::get_related_users( $user_id, true );

		$adh = AmapressAdhesion::getBy( $inscr_id );
		if ( empty( $adh ) ) {
			wp_die( __( 'Accès interdit', 'amapress' ) );
		}
		if ( ! in_array( $adh->getAdherentId(), $user_ids )
		     && ! in_array( $adh->getAdherent2Id(), $user_ids )
		     && ! in_array( $adh->getAdherent3Id(), $user_ids )
		     && ! in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( __( 'Ce contrat n\'est pas à vous !', 'amapress' ) ); //phpcs:ignore
		}

		$current_calendar = $adh->getShareCalendar();
		//if (empty($current_calendar))
		$current_calendar = [];

		foreach ( $_POST['calendar'] as $date => $coadh_id ) {
			$current_calendar[ strval( $date ) ] = $coadh_id;
		}
		$adh->setShareCalendar( $current_calendar );

		wp_redirect_and_exit(
			add_query_arg( [
				'step'    => 'contrats',
				'key'     => $_REQUEST['key'],
				'user_id' => $user_id,
			] )
		);
	}
} );

function amapress_self_adhesion( $atts, $content = '', $tag = '' ) {
	$atts                       = wp_parse_args( $atts );
	$atts['for_logged']         = 'false';
	$atts['allow_inscriptions'] = 'false';
	$atts['adhesion']           = 'true';

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_logged_self_adhesion( $atts, $content = '', $tag = '' ) {
	$atts                       = wp_parse_args( $atts );
	$atts['for_logged']         = 'true';
	$atts['check_honeypots']    = 'false';
	$atts['allow_inscriptions'] = 'false';
	$atts['adhesion']           = 'true';
	unset( $atts['key'] );

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_inter_self_adhesion( $atts, $content = '', $tag = '' ) {
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

function amapress_inter_logged_self_adhesion( $atts, $content = '', $tag = '' ) {
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

function amapress_logged_self_inscription( $atts, $content = '', $tag = '' ) {
	$atts                    = wp_parse_args( $atts );
	$atts['for_logged']      = 'true';
	$atts['check_honeypots'] = 'false';
	unset( $atts['key'] );

	return amapress_self_inscription( $atts, $content, $tag );
}

function amapress_mes_contrats( $atts, $content = '', $tag = '' ) {
	$atts                    = wp_parse_args( $atts );
	$atts['for_logged']      = 'true';
	$atts['check_honeypots'] = 'false';
	unset( $atts['edit_names'] );
	unset( $atts['shorturl'] );
	unset( $atts['track_no_renews'] );
	unset( $atts['track_no_renews_email'] );
	unset( $atts['send_no_renews_message'] );
	unset( $atts['allow_intermittents_inscription'] );
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

	return sprintf( __( 'Étape %d/%d : ', 'amapress' ), $steps_nums[ $step_id ], $steps_count );
}

/**
 * @param $atts
 */
function amapress_self_inscription( $atts, $content = '', $tag = '' ) {
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
			'agreement_new_only'                  => 'false',
			'allow_classic_adhesion'              => 'true',
			'mob_phone_required'                  => Amapress::getOption( 'mob_phone_req' ) ? 'true' : 'false',
			'address_required'                    => 'false',
			'check_principal'                     => 'true',
			'adhesion'                            => $is_adhesion_mode || $is_inscription_mode ? 'true' : 'false',
			'send_adhesion_bulletin'              => 'true',
			'send_adhesion_confirm'               => 'true',
			'send_contrat_confirm'                => 'true',
			'send_referents'                      => 'true',
			'allow_inscription_all_dates'         => 'false',
			'send_tresoriers'                     => 'true',
			'ignore_renouv_delta'                 => 'true',
			'allow_inscriptions'                  => 'true',
			'allow_new_mail'                      => 'true',
			'allow_inscriptions_without_adhesion' => 'false',
			'adhesion_category'                   => '',
			'check_adhesion_received'             => Amapress::getOption( 'check_adh_rcv' ),
			'check_adhesion_received_or_previous' => Amapress::getOption( 'check_adh_rcv_p' ),
			'track_no_renews'                     => 'false',
			'track_no_renews_email'               => get_option( 'admin_email' ),
			'no_renew_deassociate'                => 'true',
			'send_no_renews_message'              => 'false',
			'notify_email'                        => '',
			'max_produit_label_width'             => '10em',
			'paiements_info_required'             => 'false',
			'paiements_numero_required'           => 'false',
			'paniers_modulables_editor_height'    => 350,
			'send_welcome'                        => 'true',
			'edit_names'                          => 'true',
			'allow_remove_cofoyers'               => 'true',
			'allow_remove_coadhs'                 => 'false',
			'contact_referents'                   => 'true',
			'show_adherents_infos'                => 'true',
			'show_details_button'                 => 'false',
			'allow_adhesion_lieu'                 => 'false',
			'custom_checks_label'                 => '',
			'allow_adhesion_message'              => 'false',
			'allow_intermittents_inscription'     => 'true',
			'allow_coadherents_access'            => 'true',
			'allow_coadherents_inscription'       => 'true',
			'allow_coadherents_adhesion'          => 'true',
			'show_coadherents_address'            => 'false',
			'show_cofoyers_address'               => 'false',
			'contrat_print_button_text'           => __( 'Imprimer', 'amapress' ),
			'adhesion_print_button_text'          => __( 'Imprimer', 'amapress' ),
			'sort_contrats'                       => 'title',
			'only_contrats'                       => '',
			'shorturl'                            => '',
			'show_modify_coords'                  => 'inscription-en-ligne' == $tag || 'adhesion-en-ligne' == $tag ? 'false' : 'true',
			'show_due_amounts'                    => 'false',
			'show_delivery_details'               => 'false',
			'show_calendar_delivs'                => 'false',
			'show_current_inscriptions'           => 'inscription-en-ligne-connecte' == $tag || $is_adhesion_mode ? 'false' : 'true',
			'show_only_subscribable_inscriptions' => $is_inscription_mode ? 'true' : 'false',
			'show_editable_inscriptions'          => 'true',
			'adhesion_shift_weeks'                => intval( Amapress::getOption( 'adhesion_shift_weeks' ) ),
			'before_close_hours'                  => intval( Amapress::getOption( 'before_close_hours' ) ),
			'show_close_date'                     => false,
			'show_max_deliv_dates'                => 3,
			'max_coadherents'                     => intval( Amapress::getOption( 'def_max_coadh', 3 ) ),
			'max_cofoyers'                        => intval( Amapress::getOption( 'def_max_cofoy', 3 ) ),
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
			'force_upper'                         => 'false',
			'for_intermittent'                    => 'false',
			'allow_existing_mail_for_public'      => 'false',
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
	$paiements_numero_required           = Amapress::toBool( $atts['paiements_numero_required'] );
	$force_upper                         = Amapress::toBool( $atts['force_upper'] );
	$allow_trombi_decline                = Amapress::toBool( $atts['allow_trombi_decline'] );
	$allow_classic_adhesion              = Amapress::toBool( $atts['allow_classic_adhesion'] );
	$activate_adhesion                   = Amapress::toBool( $atts['adhesion'] );
	$activate_agreement                  = Amapress::toBool( $atts['agreement'] );
	$activate_agreement_if_noadh         = Amapress::toBool( $atts['agreement_new_only'] );
	$allow_remove_coadhs                 = Amapress::toBool( $atts['allow_remove_coadhs'] );
	$allow_remove_cofoys                 = Amapress::toBool( $atts['allow_remove_cofoyers'] );
	$allow_intermittents_inscription     = Amapress::toBool( $atts['allow_intermittents_inscription'] );
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
	$allow_inscriptions_without_adhesion = Amapress::toBool( $atts['allow_inscriptions_without_adhesion'] );
	$skip_coords                         = Amapress::toBool( $atts['skip_coords'] );
	$adh_category                        = $atts['adhesion_category'];
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
			return '<div class="alert alert-danger">' . __( 'Accès interdit', 'amapress' ) . '</div>';
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
					$ret .= amapress_get_panel_start( __( 'Information d\'accès pour le collectif', 'amapress' ) );
					$ret .= '<div class="alert alert-info">' . sprintf( __( 'Pour donner accès à cet assistant aux nouveaux amapiens, veuillez leur envoyer le lien suivant : 
<pre>%s</pre>
Pour y accéder cliquez <a href="%s">ici</a>.<br />
Vous pouvez également utiliser un service de réduction d\'URL tel que <a href="https://bit.ly">bit.ly</a> pour obtenir une URL plus courte à partir du lien ci-dessus.<br/>
%s
Vous pouvez également utiliser l\'un des QRCode suivants : 
<div>%s%s%s</div><br/>
<strong>Attention : les lien ci-dessus, QR code et bit.ly NE doivent PAS être visible publiquement sur le site. Ce lien permet de créer des comptes sur le site et l\'exposer sur internet pourrait permettre à une personne malvaillante de polluer le site avec des comptes de SPAM.</strong><br />
Vous pouvez configurer l\'email envoyé en fin de chaque inscription <a target="_blank" href="%s">ici</a> et retrouver toutes les options de ce shortcode dans l\'<a target="_blank" href="%s">Aide</a>.', 'amapress' ), $url, $url, ! empty( $atts['shorturl'] ) ? __( 'Lien court sauvegardé : <code>', 'amapress' ) . $atts['shorturl'] . '</code><br />' : '', amapress_print_qrcode( $url ), amapress_print_qrcode( $url, 3 ), amapress_print_qrcode( $url, 2 ), admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_mails' ), admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ) ) . '</div>';
					$ret .= amapress_get_panel_end();
				} else {
					$ret .= '<div class="alert alert-info"><a href="' . esc_attr( get_permalink() ) . '">' . __( 'Afficher les instructions d\'accès à cet assistant.', 'amapress' ) . '</a></div>';
				}
			}
		}
		$request_key = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : 'public';
		if ( empty( $key ) || $request_key != $key ) {
			if ( empty( $key ) && amapress_can_access_admin() ) {
				$sample_key = uniqid() . uniqid();
				$ret        .= '<div style="color:red">' . sprintf( __( 'L\'argument key (par ex, key="%s") doit être défini sur le shortcode [inscription-en-ligne] de cette page : par exemple "[inscription-en-ligne key=%s]". L\'accès à cette page ne peut se faire que de manière non connectée avec cette clé par la amapiens pour s\'inscrire.
<br/>Pour une utilisation publique, utilisez key=public', 'amapress' ), $sample_key, $sample_key ) . '</div>';
			} elseif ( ! empty( $key ) && empty( $_REQUEST['key'] ) && amapress_is_user_logged_in() ) {
				$url              = esc_attr( add_query_arg( 'key', $key, get_permalink() ) );
				$mes_contrat_href = esc_attr( Amapress::get_mes_contrats_page_href() );
				$ret              .= '<p>' . sprintf( __( 'Pour accéder à l\'assistant d\'inscription, cliquez <a href=\'%s\'>ici</a>', 'amapress' ), $url ) . '</p>';
				if ( ! empty( $mes_contrat_href ) ) {
					if ( ! $use_contrat_term ) {
						$ret .= '<p>' . sprintf( __( 'Pour accéder à vos commandes, cliquez <a href=\'%s\'>ici</a>', 'amapress' ), $mes_contrat_href ) . '</p>';
					} else {
						$ret .= '<p>' . sprintf( __( 'Pour accéder à vos contrats, cliquez <a href=\'%s\'>ici</a>', 'amapress' ), $mes_contrat_href ) . '</p>';
					}
				}
			} else {
				$ret .= '<div class="alert alert-danger">' . __( 'Vous êtes dans un espace sécurisé. Accès interdit', 'amapress' ) . '</div>';
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

	$contrat       = null;
	$min_total     = 0;
	$max_no_panier = - 1;
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
	$sort_contrats = $atts['sort_contrats'];
	if ( 'title' == $sort_contrats ) {
		usort( $subscribable_contrats, function ( $a, $b ) {
			/** @var AmapressContrat_instance $a */
			/** @var AmapressContrat_instance $b */
			return strcmp( $a->getTitle(), $b->getTitle() );
		} );
	} elseif ( 'inscr_start' == $sort_contrats ) {
		usort( $subscribable_contrats, function ( $a, $b ) {
			/** @var AmapressContrat_instance $a */
			/** @var AmapressContrat_instance $b */
			return $a->getDate_ouverture() - $b->getDate_ouverture();
		} );
	} elseif ( 'inscr_end' == $sort_contrats ) {
		usort( $subscribable_contrats, function ( $a, $b ) {
			/** @var AmapressContrat_instance $a */
			/** @var AmapressContrat_instance $b */
			return $a->getDate_cloture() - $b->getDate_cloture();
		} );
	} elseif ( 'contrat_start' == $sort_contrats ) {
		usort( $subscribable_contrats, function ( $a, $b ) {
			/** @var AmapressContrat_instance $a */
			/** @var AmapressContrat_instance $b */
			return $a->getDate_debut() - $b->getDate_debut();
		} );
	}

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
			return __( 'Aucun contrat ne permet l\'inscription en ligne. Veuillez activer l\'inscription en ligne depuis ', 'amapress' ) . Amapress::makeLink( admin_url( 'edit.php?post_type=amps_contrat_inst' ), __( 'Edition des contrats', 'amapress' ) );
		} else {
			$closed_message = wp_unslash( Amapress::getOption( 'online_inscr_closed_message' ) );
			if ( ! $use_contrat_term ) {
				return '<p>' . __( 'Les commandes en ligne sont closes.', 'amapress' ) . '</p>' . $closed_message;
			} else {
				return '<p>' . __( 'Les inscriptions en ligne sont closes.', 'amapress' ) . '</p>' . $closed_message;
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
					$ret = '<p>' . sprintf( __( '%s a déjà une inscription à cette commande. Veuillez retourner à la page <a href="%s">Commandes</a>', 'amapress' ), esc_html( $amapien->getDisplayName() ), $contrats_step_url ) . '</p>';
				} else {
					$ret = '<p>' . sprintf( __( '%s a déjà une inscription à ce contrat. Veuillez retourner à la page <a href="%s">Contrats</a>', 'amapress' ), esc_html( $amapien->getDisplayName() ), $contrats_step_url ) . '</p>';
				}

				return $additional_css . $ret;
			} else {
				ob_clean();

				if ( ! $use_contrat_term ) {
					$ret = '<p>' . sprintf( __( 'Vous avez déjà passé cette commande. Veuillez retourner à la page <a href="%s">Commandes</a>', 'amapress' ), $contrats_step_url ) . '</p>';
				} else {
					$ret = '<p>' . sprintf( __( 'Vous avez déjà une inscription à ce contrat. Veuillez retourner à la page <a href="%s">Contrats</a>', 'amapress' ), $contrats_step_url ) . '</p>';
				}

				return $additional_css . $ret;
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

	$invalid_access_message = '<p>' . sprintf( __( 'Accès invalide : veuillez repartir de la <a href="%s">première étape</a>', 'amapress' ), esc_attr( $start_step_url ) ) . '</p>';

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

			return __( 'Cette incription n\'est pas éditable', 'amapress' );
		}
		$user_id  = ! empty( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : 0;
		$user_ids = $user_id ? AmapressContrats::get_related_users( $user_id, true,
			null, null, true, false ) : [];
		if ( ! in_array( $edit_inscription->getAdherentId(), $user_ids ) ) {
			ob_clean();

			return __( 'Cette incription n\'est pas à vous', 'amapress' );
		}
	}

	if ( ! empty( $_REQUEST['message'] ) ) {
		$message = '';
		switch ( $_REQUEST['message'] ) {
			case 'empty_email':
				$message = __( 'L\'adresse email saisie est invalide', 'amapress' );
				break;
			case 'cannot_create_user':
				$message = __( 'Impossible de créer votre compte.', 'amapress' );
				break;
		}
		echo '<div class="alert alert-danger">' . $message . '</div>';
	}

	/** @var AmapressAdhesionPeriod $adh_period */
	if ( 'email' == $step ) {
		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date, $adh_category );
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
			$welcome_message = sprintf( __( 'Bienvenue dans l’assistant %s « %%%%nom_site%%%% »', 'amapress' ), $welcome_type );
		}
		$welcome_message = amapress_replace_mail_placeholders( $welcome_message, null );

		$saison_start_message = wp_unslash(
			Amapress::getOption( $is_adhesion_mode ?
				( $adhesion_intermittent ? 'online_subscription_start_saison_inter_message' : 'online_subscription_start_saison_adh_message' ) :
				'online_subscription_start_saison_message' )
		);
		if ( empty( $saison_start_message ) ) {
			$saison_start_message = sprintf( __( 'Pour démarrer votre %s pour la saison %s, veuillez renseigner votre adresse mail :', 'amapress' ),
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
                    <label for="no_renew"><input type="checkbox" id="no_renew" name="no_renew"/> <?php _e( 'Je ne souhaite pas
                        renouveler.', 'amapress' ) ?></label>
                    <label for="no_renew_reason"><?php _e( 'Motif (facultatif):', 'amapress' ) ?></label>
                    <textarea id="no_renew_reason" name="no_renew_reason"
                              disabled="disabled"
                              placeholder="<?php echo esc_attr__( 'Motif (facultatif)', 'amapress' ) ?>"></textarea>
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

			amapress_echo_honeypots();
			?>
            <input type="submit" value="<?php _e( 'Valider', 'amapress' ); ?>"
                   class="btn btn-default btn-assist-inscr btn-assist-inscr-validate"/>
        </form>
		<?php
	} else if ( 'coords' == $step || 'coords_logged' == $step ) {
		if ( Amapress::toBool( $atts['check_honeypots'] ) ) {
			amapress_checkhoneypots();
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

				if ( Amapress::toBool( $atts['no_renew_deassociate'] ) ) {
					$amapien = AmapressUser::getBy( $user );
					$amapien->deassociateAllCoadherents( $notify_email );
				}

				$track_no_renews_email = $atts['track_no_renews_email'];
				if ( empty( $track_no_renews_email ) ) {
					$track_no_renews_email = get_option( 'admin_email' );
				}
				if ( ! empty( $track_no_renews_email ) ) {
					$amapien   = AmapressUser::getBy( $user );
					$edit_link = Amapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName() );
					amapress_wp_mail(
						$track_no_renews_email,
						sprintf( __( 'Adhésion/Préinscription - Non renouvellement - %s', 'amapress' ), $amapien->getDisplayName() ),
						amapress_replace_mail_placeholders(
							wpautop( sprintf( "Bonjour,\n\nL\'amapien %s ne souhaite pas renouveler. Motif:%s\n\n%%%%site_name%%%%", $edit_link, $reason ) ), $amapien ),
						'', [], $notify_email
					);
				}

				if ( Amapress::toBool( $atts['send_no_renews_message'] ) ) {
					$amapien = AmapressUser::getBy( $user );
					$content = amapress_replace_mail_placeholders(
						Amapress::getOption( 'online_adhesion_no_renew-mail-content' ), $amapien );
					$content = str_replace( '%%reason%%', $reason, $content );
					amapress_wp_mail(
						implode( ',', $amapien->getAllEmails() ),
						amapress_replace_mail_placeholders(
							Amapress::getOption( 'online_adhesion_no_renew-mail-subject' ), $amapien ),
						$content,
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

			return $additional_css . '<p style="font-weight: bold">' . ( $is_adhesion_mode ? __( 'Les adhésions', 'amapress' ) : __( 'Les inscriptions', 'amapress' ) ) . __( ' avec une nouvelle adresse email ne sont pas autorisées.', 'amapress' ) . '</p>
<p>' . __( 'Si vous êtes déjà membre, vous avez certainement utilisé une adresse email différente.', 'amapress' ) . '</p>
<p><a href="' . $start_step_url . '">' . __( 'Changer d’email', 'amapress' ) . '</a></p>';
		}

		if ( ! Amapress::toBool( $atts['allow_existing_mail_for_public'] ) && ! $for_logged && 'public' == $key && $user ) {
			ob_clean();

			return $additional_css . '<p style="font-weight: bold">' . ( $is_adhesion_mode ? __( 'Les adhésions', 'amapress' ) : __( 'Les inscriptions', 'amapress' ) ) . __( ' avec une adresse email existante ne sont pas autorisées.', 'amapress' ) . '</p>
<p>' . __( 'Si vous êtes déjà membre, veuillez vous connecter.', 'amapress' ) . '</p>
<p><a href="' . $start_step_url . '">' . __( 'Changer d’email', 'amapress' ) . '</a></p>';
		}

		if ( $user && ! Amapress::toBool( $atts['allow_coadherents_access'] ) ) {
			$amapien = AmapressUser::getBy( $user );
			if ( $amapien->isCoAdherent() ) {
				return $additional_css . '<p style="font-weight: bold">' . ( $is_adhesion_mode ? __( 'Les adhésions', 'amapress' ) : __( 'Les inscriptions', 'amapress' ) ) . __( ' ne sont pas autorisées pour les co-adhérents.', 'amapress' ) . '</p>
<p><a href="' . $start_step_url . '">' . __( 'Changer d’email', 'amapress' ) . '</a></p>';
			}
		}

		if ( ! $admin_mode && $user ) {
			$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date, $adh_category );
			if ( empty( $adh_period ) ) {
				ob_clean();

				return ( sprintf( __( 'Aucune période d\'adhésion n\'est configurée au %s', 'amapress' ), date_i18n( 'd/m/Y', $adh_period_date ) ) );
			}

			$adh_paiement = AmapressAdhesion_paiement::getForUser( $user->ID, $adh_period_date );
			if ( ! empty( $adh_paiement ) ) {
				if ( $check_adhesion_received && $adh_paiement->isNotReceived() ) {
					ob_clean();

					return $additional_css . wp_unslash( Amapress::getOption( 'online_inscr_adhesion_required_message' ) );
				}
				if ( $is_adhesion_mode ) {
					ob_clean();

					return $additional_css . '<p>' . __( 'Vous avez déjà une adhésion !', 'amapress' ) . '</p>';
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

		$user_message   = __( 'Vous êtes nouveau, complétez vos coordonnées :', 'amapress' );
		$member_message = '<p>' . __( 'Si vous êtes déjà membre, vous avez certainement utilisé une adresse email différente.', 'amapress' ) . '</p>
<p><a href="' . $start_step_url . '">' . __( 'Changer d’email', 'amapress' ) . '</a></p>';

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
			if ( ! $amapien->isPrincipalAdherent() ) {
				$max_cofoyers = 0;
				$max_coadhs   = 0;
			}

			if ( ! $allow_coadherents_adhesion && $amapien->isCoAdherent() ) {
				$activate_adhesion = false;
			}

			$hidaddr            = $amapien->isHiddenFromTrombi();
			$user_message       = __( 'Vous êtes déjà membre, vérifiez vos coordonnées :', 'amapress' );
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

		$adh_pmt                       = $user ? AmapressAdhesion_paiement::getForUser( $user->ID, $adh_period_date ) : null;
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
			<?php if ( $is_mes_contrats && ! $activate_adhesion ) { ?>
                <input type="hidden" name="coords_next_step" value="contrats"/>
			<?php } elseif ( $activate_agreement ) { ?>
                <input type="hidden" name="coords_next_step" value="agreement"/>
			<?php } elseif ( $activate_agreement_if_noadh && ! empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step"
                       value="<?php echo( $for_logged ? 'coords_logged' : 'coords' ); ?>"/>
			<?php } elseif ( $activate_adhesion && empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step" value="adhesion"/>
			<?php } ?>
			<?php amapress_echo_honeypots(); ?>
            <input type="hidden" name="inscr_key" value="<?php echo esc_attr( amapress_sha_secret( $key ) ); ?>"/>
            <table style="min-width: 50%">
                <tr>
                    <th style="text-align: left; width: auto"><label
                                for="email"><?php _e( 'Email : ', 'amapress' ) ?></label>
                    </th>
                    <td><span style="width: 100%"><?php echo esc_html( $email ) ?></span></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label
                                for="last_name"><?php _e( 'Nom* : ', 'amapress' ) ?></label></th>
                    <td><input style="width: 100%" type="text" id="last_name" name="last_name"
                               class="required single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                               value="<?php echo esc_attr( $user_last_name ) ?>" <?php disabled( ! $edit_names ) ?>/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label
                                for="first_name"><?php _e( 'Prénom* : ', 'amapress' ) ?></label></th>
                    <td><input style="width: 100%" type="text" id="first_name" name="first_name"
                               class="required single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                               value="<?php echo esc_attr( $user_firt_name ) ?>" <?php disabled( ! $edit_names ) ?>/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label
                                for="telm"><?php _e( 'Téléphone mobile', 'amapress' ) ?><?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? '*' : '' ) ?>
                            : </label>
                    </th>
                    <td><input style="width: 100%" type="text" id="telm" name="telm"
                               class="mobilePhoneCheck <?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required' : '' ) ?>"
                               value="<?php echo esc_attr( $user_mobile_phones ) ?>"/></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label
                                for="telf"><?php _e( 'Téléphone fixe : ', 'amapress' ) ?></label></th>
                    <td><input style="width: 100%" type="text" id="telf" name="telf" class="fixPhoneCheck"
                               value="<?php echo esc_attr( $user_fix_phones ) ?>"/></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label
                                for="address"><?php _e( 'Adresse', 'amapress' ) ?><?php echo( Amapress::toBool( $atts['address_required'] ) ? '*' : '' ); ?>
                            : </label></th>
                    <td><textarea style="width: 100%" rows="4" id="address" name="address"
                                  class="<?php echo( $force_upper ? 'force-upper' : '' ); ?> <?php echo( Amapress::toBool( $atts['address_required'] ) ? 'required' : '' ) ?>"><?php echo esc_textarea( $user_address ); ?></textarea>
                    </td>
                </tr>
				<?php if ( $allow_trombi_decline ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"></th>
                        <td>
                            <label for="hidaddr"><input type="checkbox" name="hidaddr" <?php checked( $hidaddr ); ?>
                                                        id="hidaddr"/> <?php _e( 'Ne pas apparaître sur le trombinoscope', 'amapress' ) ?>
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
                        <th colspan="2"><?php _e( 'Membre du foyer 1 / Conjoint', 'amapress' ) ?>
                        </th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy1_email"><?php _e( 'Son email : ', 'amapress' ) ?></label>
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
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy1_last_name"><?php _e( 'Son nom : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy1_last_name"
                                                                                                   name="cofoy1_last_name"
                                                                                                   class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                                   data-if-id="cofoy1_email"
                                                                                                   value="<?php echo esc_attr( $cofoy1_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy1_first_name"><?php _e( 'Son prénom : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy1_first_name"
                                                                                                   name="cofoy1_first_name"
                                                                                                   class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                                   data-if-id="cofoy1_email"
                                                                                                   value="<?php echo esc_attr( $cofoy1_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy1_tels"><?php _e( 'Téléphone(s) : ', 'amapress' ) ?></label></th>
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
                            <th style="text-align: left; width: auto"><label
                                        for="cofoy1_address"><?php _e( 'Adresse : ', 'amapress' ) ?></label>
                            </th>
                            <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                          rows="4"
                                                                                                          id="cofoy1_address"
                                                                                                          name="cofoy1_address"
                                                                                                          class="<?php echo( $force_upper ? 'force-upper' : '' ); ?>"><?php echo esc_textarea( $cofoy1_address ); ?></textarea>
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
                        <th colspan="2"><?php _e( 'Membre du foyer 2', 'amapress' ) ?>
                        </th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy2_email"><?php _e( 'Son email : ', 'amapress' ) ?></label>
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
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy2_last_name"><?php _e( 'Son nom : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy2_last_name"
                                                                                                   name="cofoy2_last_name"
                                                                                                   class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                                   data-if-id="cofoy2_email"
                                                                                                   value="<?php echo esc_attr( $cofoy2_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy2_first_name"><?php _e( 'Son prénom : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy2_first_name"
                                                                                                   name="cofoy2_first_name"
                                                                                                   class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                                   data-if-id="cofoy2_email"
                                                                                                   value="<?php echo esc_attr( $cofoy2_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy2_tels"><?php _e( 'Téléphone(s) : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy2_tels"
                                                                                                   name="cofoy2_tels"
                                                                                                   value="<?php echo esc_attr( $cofoy2_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_cofoys_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label
                                        for="cofoy2_address"><?php _e( 'Adresse : ', 'amapress' ) ?></label>
                            </th>
                            <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                          rows="4"
                                                                                                          id="cofoy2_address"
                                                                                                          name="cofoy2_address"
                                                                                                          class="<?php echo( $force_upper ? 'force-upper' : '' ); ?>"><?php echo esc_textarea( $cofoy2_address ); ?></textarea>
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
                        <th colspan="2"><?php _e( 'Membre du foyer 3', 'amapress' ) ?></th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy3_email"><?php _e( 'Son email : ', 'amapress' ) ?></label>
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
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy3_last_name"><?php _e( 'Son nom : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy3_last_name"
                                                                                                   name="cofoy3_last_name"
                                                                                                   class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                                   data-if-id="cofoy3_email"
                                                                                                   value="<?php echo esc_attr( $cofoy3_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy3_first_name"><?php _e( 'Son prénom : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy3_first_name"
                                                                                                   name="cofoy3_first_name"
                                                                                                   class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                                   data-if-id="cofoy3_email"
                                                                                                   value="<?php echo esc_attr( $cofoy3_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="cofoy3_tels"><?php _e( 'Téléphone(s) : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                   type="text"
                                                                                                   id="cofoy3_tels"
                                                                                                   name="cofoy3_tels"
                                                                                                   value="<?php echo esc_attr( $cofoy3_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_cofoys_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label
                                        for="cofoy3_address"><?php _e( 'Adresse : ', 'amapress' ) ?></label>
                            </th>
                            <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                          rows="4"
                                                                                                          id="cofoy3_address"
                                                                                                          name="cofoy3_address"
                                                                                                          class="<?php echo( $force_upper ? 'force-upper' : '' ); ?>"><?php echo esc_textarea( $cofoy3_address ); ?></textarea>
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
                        <th colspan="2"><?php _e( 'Co adhérent 1 <em>(Partage du contrat et de son règlement)</em>', 'amapress' ) ?></th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh1_email"><?php _e( 'Son email : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="email"
                                                                                  id="coadh1_email" name="coadh1_email"
                                                                                  class="email"
                                                                                  value="<?php echo esc_attr( $coadh1_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh1_last_name"><?php _e( 'Son nom : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh1_last_name"
                                                                                  name="coadh1_last_name"
                                                                                  class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                  data-if-id="coadh1_email"
                                                                                  value="<?php echo esc_attr( $coadh1_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh1_first_name"><?php _e( 'Son prénom : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh1_first_name"
                                                                                  name="coadh1_first_name"
                                                                                  class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                  data-if-id="coadh1_email"
                                                                                  value="<?php echo esc_attr( $coadh1_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh1_tels"><?php _e( 'Téléphone(s) : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh1_tels" name="coadh1_tels"
                                                                                  class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                  data-if-id="coadh1_email"
                                                                                  value="<?php echo esc_attr( $coadh1_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_coadherents_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label
                                        for="coadh1_address"><?php _e( 'Adresse : ', 'amapress' ) ?></label>
                            </th>
                            <td><textarea style="width: 100%" rows="4" id="coadh1_address" name="coadh1_address"
                                          class="<?php echo( $force_upper ? 'force-upper' : '' ); ?>"><?php echo esc_textarea( $coadh1_address ); ?></textarea>
                            </td>
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
                        <th colspan="2"><?php _e( 'Co adhérent 2 <em>(Partage du contrat et de son règlement)</em>', 'amapress' ) ?></th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh2_email"><?php _e( 'Son email : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="email"
                                                                                  id="coadh2_email" name="coadh2_email"
                                                                                  class="email"
                                                                                  value="<?php echo esc_attr( $coadh2_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh2_last_name"><?php _e( 'Son nom : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh2_last_name"
                                                                                  name="coadh2_last_name"
                                                                                  class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                  data-if-id="coadh2_email"
                                                                                  value="<?php echo esc_attr( $coadh2_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh2_first_name"><?php _e( 'Son prénom : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh2_first_name"
                                                                                  name="coadh2_first_name"
                                                                                  class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                  data-if-id="coadh2_email"
                                                                                  value="<?php echo esc_attr( $coadh2_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh2_tels"><?php _e( 'Téléphone(s) : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh2_tels" name="coadh2_tels"
                                                                                  class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                  data-if-id="coadh2_email"
                                                                                  value="<?php echo esc_attr( $coadh2_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_coadherents_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label
                                        for="coadh2_address"><?php _e( 'Adresse : ', 'amapress' ) ?></label>
                            </th>
                            <td><textarea style="width: 100%" rows="4" id="coadh2_address" name="coadh2_address"
                                          class="<?php echo( $force_upper ? 'force-upper' : '' ); ?>"><?php echo esc_textarea( $coadh2_address ); ?></textarea>
                            </td>
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
                        <th colspan="2"><?php _e( 'Co adhérent 3 <em>(Partage du contrat et de son règlement)</em>', 'amapress' ) ?></th>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh3_email"><?php _e( 'Son email : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="email"
                                                                                  id="coadh3_email" name="coadh3_email"
                                                                                  class="email"
                                                                                  value="<?php echo esc_attr( $coadh3_email ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh3_last_name"><?php _e( 'Son nom : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh3_last_name"
                                                                                  name="coadh3_last_name"
                                                                                  class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                  data-if-id="coadh3_email"
                                                                                  value="<?php echo esc_attr( $coadh3_user_last_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh3_first_name"><?php _e( 'Son prénom : ', 'amapress' ) ?></label>
                        </th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh3_first_name"
                                                                                  name="coadh3_first_name"
                                                                                  class="required_if_not_empty single_name <?php echo( $force_upper ? 'force-upper' : '' ); ?>"
                                                                                  data-if-id="coadh3_email"
                                                                                  value="<?php echo esc_attr( $coadh3_user_firt_name ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align: left; width: auto"><label
                                    for="coadh3_tels"><?php _e( 'Téléphone(s) : ', 'amapress' ) ?></label></th>
                        <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                                  id="coadh3_tels" name="coadh3_tels"
                                                                                  class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                  data-if-id="coadh3_email"
                                                                                  value="<?php echo esc_attr( $coadh3_mobile_phones ) ?>"/>
                        </td>
                    </tr>
					<?php if ( $show_coadherents_address ) { ?>
                        <tr>
                            <th style="text-align: left; width: auto"><label
                                        for="coadh3_address"><?php _e( 'Adresse : ', 'amapress' ) ?></label>
                            </th>
                            <td><textarea style="width: 100%" rows="4" id="coadh3_address" name="coadh3_address"
                                          class="<?php echo( $force_upper ? 'force-upper' : '' ); ?>"><?php echo esc_textarea( $coadh3_address ); ?></textarea>
                            </td>
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
            <input style="min-width: 50%" type="submit"
                   class="btn btn-default btn-assist-inscr btn-assist-inscr-validate"
                   value="<?php echo esc_attr__( 'Valider', 'amapress' ) ?>"/>
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

		$adh_pmt = $user_id ? AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date ) : null;
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
					$adhesion_intermittent ? 'online_subscription_inter_agreement' : 'online_subscription_agreement' ),
					$amapien ) ); ?>
            </div>
            <p class="accept-agreement">
                <label for="accept_agreement"><input type="checkbox" name="accept" id="accept_agreement"
                                                     class="required" value="1"
                                                     data-msg="<?php echo esc_attr__( 'Veuillez cocher la case ci-dessous', 'amapress' ) ?>"/> <?php echo esc_html( wp_unslash( Amapress::getOption(
						$adhesion_intermittent ? 'online_subscription_inter_agreement_step_checkbox' : 'online_subscription_agreement_step_checkbox' ) ) ); ?>
                </label>
            </p>
            <p>
                <input style="min-width: 50%" type="submit"
                       class="btn btn-default btn-assist-inscr btn-assist-inscr-validate"
                       value="<?php echo esc_attr__( 'Valider', 'amapress' ) ?>"/>
            </p>
        </form>
		<?php
	} else if ( 'adhesion' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$user_id = intval( $_REQUEST['user_id'] );

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date );
		if ( $adh_paiement ) {
			ob_clean();

			return $additional_css . '<p>' . sprintf( __( 'Vous avez déjà une adhésion. Vous pouvez aller vers l\'étape <a href="%s">Contrats</a>', 'amapress' ), $contrats_step_url ) . '</p>';
		}

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date, $adh_category );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( sprintf( __( 'Aucune période d\'adhésion n\'est configurée au %s', 'amapress' ), date_i18n( 'd/m/Y', $adh_period_date ) ) );
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
			$ret .= '<p>' . __( 'Lieu de distribution souhaité :', 'amapress' ) . '</p>';
			foreach ( Amapress::get_lieux() as $lieu ) {
				if ( ! $lieu->isPrincipal() ) {
					continue;
				}

				$ret .= '<label for="adh-lieu-' . $lieu->ID . '"><input class="required" name="amapress_adhesion_lieu" value="' . $lieu->ID . '" type="radio" id="adh-lieu-' . $lieu->ID . '" /> ' . esc_html( $lieu->getTitle() ) . '</label><br/>';
			}
			$ret .= '<label for="adh-lieu-any"><input class="required" name="amapress_adhesion_lieu" value="any" type="radio" id="adh-lieu-any" /> ' . __( 'N\'importe lequel', 'amapress' ) . '</label><br/>';
			$ret .= '<label for="adh-lieu-none"><input class="required" name="amapress_adhesion_lieu" value="none" type="radio" id="adh-lieu-none" /> ' . __( 'Aucun', 'amapress' ) . '</label>';
			$ret .= '</div>';
		}
		$custom_checks = '';
		for ( $custom_check_ix = 1; $custom_check_ix < 4; $custom_check_ix ++ ) {
			$custom_check = $adh_period->getCustomCheck( $custom_check_ix );
			if ( ! empty( $custom_check ) ) {
				$custom_checks .= '<label for="adh-custom-' . $custom_check_ix . '">
<input name="amapress_adhesion_custom_check' . $custom_check_ix . '" value="1" type="checkbox" id="adh-custom-' . $custom_check_ix . '" /> ' .
				                  strip_tags( wp_unslash( $custom_check ), '<em><i><strong><b><br><a>' ) . '</label>';
			}
		}
		if ( ! empty( $custom_checks ) ) {
			$ret .= '<p class="amps-custom-checks-label">' .
			        ( ! empty( $atts['custom_checks_label'] ) ? $atts['custom_checks_label'] : __( 'Options : ', 'amapress' ) ) .
			        '</p>' . $custom_checks;
		}

		if ( Amapress::toBool( $atts['allow_adhesion_message'] ) ) {
			$ret .= '<div>';
			$ret .= '<label for="adh-message" style="display: block">' . __( 'Message personnel :', 'amapress' ) . '</label><br/>
<textarea id="adh-message" name="amapress_adhesion_message"></textarea>';
			$ret .= '</div>';
		}
		//allow_adhesion_lieu
		//allow_adhesion_message
		$amap_term        = intval( Amapress::getOption( 'adhesion_amap_term' ) );
		$reseau_amap_term = intval( Amapress::getOption( 'adhesion_reseau_amap_term' ) );
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
		$ret .= '<p>' . __( 'Montant total :', 'amapress' ) . ' <span id="amapress_adhesion_paiement_amount"></span> €</p>';
		$ret .= $adh_period->getPaymentInfo();
		$ret .= '<p>';
		if ( $adh_period->getAllow_Cheque() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="chq" checked="checked" /> ' . __( 'Chèque', 'amapress' ) . '</label>';
		}
		if ( $adh_period->getAllow_Transfer() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="vir"/> ' . __( 'Virement', 'amapress' ) . '</label>';
		}
		if ( $adh_period->getAllow_Cash() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="esp" /> ' . __( 'Espèces', 'amapress' ) . '</label>';
		}
		if ( $adh_period->getAllow_LocalMoney() ) {
			$ret .= '<label><input type="radio" name="amapress_adhesion_paiement_pmt_type" value="mon" /> ' . __( 'Monnaie locale', 'amapress' ) . '</label>';
		}
		$ret .= '</p>';
		if ( $adh_period->getAllowAmapienInputPaiementsDetails() ) {
			$ret .= '<p><label for="amapress_adhesion_paiement_numero">' . esc_html( wp_unslash( Amapress::getOption( 'online_subscription_adh_num_label' ) ) ) . '</label><input type="text" id="amapress_adhesion_paiement_numero" class="' . ( $paiements_numero_required ? 'required' : '' ) . '" name="amapress_adhesion_paiement_numero"/></p>';
			$ret .= '<p><label for="amapress_adhesion_paiement_banque">' . __( 'Banque :', 'amapress' ) . '</label><input type="text" id="amapress_adhesion_paiement_banque" class="' . ( $paiements_info_required ? 'required' : '' ) . '" name="amapress_adhesion_paiement_banque"/></p>';
			$ret .= '<p><label for="amapress_adhesion_paiement_emetteur">' . __( 'Emetteur :', 'amapress' ) . '</label><input type="text" id="amapress_adhesion_paiement_emetteur" class="' . ( $paiements_info_required ? 'required' : '' ) . '" name="amapress_adhesion_paiement_emetteur"/></p>';
		}
		$ret .= '<input type="submit" class="btn btn-default btn-assist-adh" value="' . esc_attr__( 'Valider', 'amapress' ) . '"/>';
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

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date, $adh_category );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( sprintf( __( 'Aucune période d\'adhésion n\'est configurée au %s', 'amapress' ), date_i18n( 'd/m/Y', $adh_period_date ) ) );
		}

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period, true );

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
		if ( isset( $_REQUEST['amapress_adhesion_paiement_emetteur'] ) ) {
			update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_emetteur', sanitize_text_field( $_REQUEST['amapress_adhesion_paiement_emetteur'] ) );
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
			true,
			Amapress::toBool( $atts['send_adhesion_bulletin'] )
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
				$adhesion_print_button_text, true, true, 'btn btn-default btn-assist-inscr btn-assist-inscr-print'
			);
			$online_subscription_greating_adhesion = str_replace( '%%print_button%%', $print_bulletin, $online_subscription_greating_adhesion );
		} else {
			$online_subscription_greating_adhesion = str_replace( '%%print_button%%', '', $online_subscription_greating_adhesion );
		}
		echo amapress_replace_mail_placeholders( $online_subscription_greating_adhesion,
			$amapien, $adh_paiement );

		if ( ! $is_adhesion_mode ) {
			if ( ! $use_contrat_term ) {
				echo '<p>' . __( 'Vous pouvez maintenant passer commandes', 'amapress' ) . '<br/>';
			} else {
				echo '<p>' . __( 'Vous pouvez maintenant vous inscrire aux contrats disponibles', 'amapress' ) . '<br/>';
			}
			echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr btn-assist-inscr-next" type="submit" value="' . esc_attr__( 'Poursuivre', 'amapress' ) . '" />
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
					sprintf( __( 'Adhésion/Préinscription - Non renouvellement - %s', 'amapress' ), $amapien->getDisplayName() ),
					amapress_replace_mail_placeholders(
						wpautop( sprintf( __( "Bonjour,\n\nL\'amapien %s ne souhaite pas renouveler. Motif:%s\n\n%%%%site_name%%%%", 'amapress' ), $edit_link, $reason ) ), $amapien ),
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
		$has_principal_contrat = false;

		Amapress::setFilterForReferent( false );
		$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, null, $ignore_renouv_delta, true );
		Amapress::setFilterForReferent( true );
		if ( Amapress::toBool( $atts['check_principal'] ) && ! $disable_principal ) {
			foreach ( $adhs as $adh ) {
				/** @var AmapressAdhesion $adh */
				if ( $adh->getContrat_instance()->isPrincipal()
				     && ( empty( $principal_contrats_ids ) || in_array( $adh->getContrat_instanceId(), $principal_contrats_ids ) ) ) {
					$has_principal_contrat = true;
				}
			}
		} else {
			$has_principal_contrat = true;
		}
		if ( $show_only_subscribable_inscriptions ) {
			$adhs = array_filter( $adhs,
				function ( $adh ) use ( $all_subscribable_contrats_ids ) {
					/** @var AmapressAdhesion $adh */
					return in_array( $adh->getContrat_instanceId(), $all_subscribable_contrats_ids );
				} );
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
						$online_contrats_step_name = __( 'Les commandes', 'amapress' );
					} else {
						$online_contrats_step_name = __( 'Les contrats', 'amapress' );
					}
				}
				echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $online_contrats_step_name . '</h4>';
			}
		} else {
			if ( ! $use_contrat_term ) {
				echo '<h4>';
				echo sprintf( __( 'Les commandes de %s', 'amapress' ), esc_html( $amapien->getDisplayName() ) );
				echo '</h4>';
			} else {
				echo '<h4>';
				echo sprintf( __( 'Les contrats de %s', 'amapress' ), esc_html( $amapien->getDisplayName() ) );
				echo '</h4>';
			}
		}
		$adh_paiement = null;
		if ( ! $admin_mode ) {
			if ( $allow_coadherents_adhesion || ! $amapien->isCoAdherent() ) {
				$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date, $adh_category );
				if ( empty( $adh_period ) ) {
					ob_clean();

					return ( sprintf( __( 'Aucune période d\'adhésion n\'est configurée au %s', 'amapress' ), date_i18n( 'd/m/Y', $adh_period_date ) ) );
				}

				$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date );

				if ( empty( $adh_paiement ) ) {
					if ( ! $activate_adhesion ) {
						echo '<p><strong>' .
						     esc_html( sprintf( __( 'Vous n\'avez pas d\'adhésion sur la Période - %s > %s', 'amapress' ),
							     date_i18n( 'd/m/Y', $adh_period->getDate_debut() ),
							     date_i18n( 'd/m/Y', $adh_period->getDate_fin() ) ) ) .
						     '</strong></p>';
						if ( ! $allow_inscriptions_without_adhesion ) {
							$allow_inscriptions = false;
						}
					} else {
						echo amapress_replace_mail_placeholders( wp_unslash( Amapress::getOption(
							$adhesion_intermittent ? 'online_subscription_inter_req_adhesion' : 'online_subscription_req_adhesion' ) ),
							$amapien );

						if ( empty( $adh_period->getHelloAssoFormSlug() ) ) {
							$allow_classic_adhesion = true;
						}

						if ( $allow_classic_adhesion ) {
							echo '<p><form method="get" action="' . esc_attr( $adhesion_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="' . ( $skip_coords ? ( $activate_agreement ? 'agreement' : 'adhesion' ) : ( $for_logged ? 'coords_logged' : 'coords' ) ) . '" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr btn-assist-inscr-adh" type="submit" value="' . esc_attr( wp_unslash( Amapress::getOption( 'online_subscription_adh_button_text' ) ) ) . '" />
</form></p>';
						}
						if ( ! empty( $adh_period->getHelloAssoFormUrl() ) ) {
							echo '<p>' . Amapress::makeButtonLink(
									$adh_period->getHelloAssoFormUrl(),
									wp_unslash( Amapress::getOption( 'online_subscription_adh_hla_button_text' ) ),
									true, false, 'btn btn-default btn-assist-inscr btn-assist-inscr-helloasso' ) . '</p>';
							echo '<p class="helloasso-adhesion-note"><em>' . sprintf( __( 'L\'adhésion via HelloAsso nécessite d\'utiliser l\'adresse email et le nom associé au compte avec laquelle vous êtes actuellement connecté. Vous êtes connecté en tant que <strong>%s</strong>. Votre adresse email est <strong>%s</strong>', 'amapress' ),
									$amapien->getDisplayName(), $amapien->getEmail() ) . '</em></p>';
						}
						if ( $track_no_renews ) {
							?>
                            <form method="post"
                                  action="<?php echo esc_attr( add_query_arg( 'step', 'norenew', remove_query_arg( [
								      'contrat_id',
								      'message'
							      ] ) ) ) ?>">
                                <div class="amap-preinscr-norenew">
                                    <h4><?php _e( 'Non renouvellement', 'amapress' ) ?></h4>
									<?php if ( $amapien->getNoRenew() ) {
										echo '<p>' . __( 'Votre non renouvellement a été pris en compte !', 'amapress' ) . '</p>';
									} ?>
                                    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
                                    <label for="no_renew_reason"><?php _e( 'Motif (facultatif):', 'amapress' ) ?></label>
                                    <textarea id="no_renew_reason" name="no_renew_reason"
                                              placeholder="<?php echo esc_attr__( 'Motif (facultatif)', 'amapress' ) ?>"><?php echo esc_textarea( $amapien->getNoRenewReason() ); ?></textarea>
                                    <input type="submit" name="no_renew"
                                           value="<?php echo esc_attr__( 'Je ne souhaite pas renouveler.', 'amapress' ) ?>"/>
                                </div>
                            </form>
							<?php
						}
						if ( $activate_adhesion || $check_adhesion_received ) {
							return ob_get_clean();
						}
					}
				} else {
					if ( $check_adhesion_received_or_previous ) {
						if ( AmapressAdhesion_paiement::hadUserAnyValidated( $user_id ) ) {
							$check_adhesion_received = false;
						}
					}
					if ( Amapress::toBool( $atts['show_adhesion_infos'] ) ) {
						$all_adh_for_user = AmapressAdhesion_paiement::getAllForUserId( $user_id );
						/** @var AmapressAdhesion_paiement $adh_cur_pmt */
						foreach ( $all_adh_for_user as $adh_cur_pmt ) {
							if ( empty( $adh_cur_pmt ) || Amapress::start_of_day( $adh_cur_pmt->getPeriod()->getDate_fin() ) < Amapress::start_of_day( amapress_time() ) ) {
								continue;
							}

							$print_bulletin = '';
							if ( $adh_cur_pmt->getPeriod()->getWordModelId() ) {
								$print_bulletin = Amapress::makeButtonLink(
									add_query_arg( [
										'inscr_assistant' => 'generate_bulletin',
										'adh_id'          => $adh_cur_pmt->ID,
										'inscr_key'       => amapress_sha_secret( $key )
									] ),
									$adhesion_print_button_text, true, true, 'btn btn-default btn-assist-inscr btn-assist-inscr-print'
								);
							}

							if ( $check_adhesion_received && $adh_cur_pmt->isNotReceived() ) {
								echo '<p>' . sprintf( __( 'Vous avez une adhésion qui sera valable du %s au %s', 'amapress' ),
										date_i18n( 'd/m/Y', $adh_cur_pmt->getPeriod()->getDate_debut() ),
										date_i18n( 'd/m/Y', $adh_cur_pmt->getPeriod()->getDate_fin() )
									) . '<br />' . $print_bulletin . '</p>';
							} else {
								echo '<p>';
								echo sprintf( __( 'Vous avez une adhésion qui est valable du %s jusqu\'au %s', 'amapress' ),
									date_i18n( 'd/m/Y', $adh_cur_pmt->getPeriod()->getDate_debut() ),
									date_i18n( 'd/m/Y', $adh_cur_pmt->getPeriod()->getDate_fin() ) );
								echo '.<br />
' . $print_bulletin . '</p>';
							}
						}
					}
				}
			}

			echo wp_unslash( amapress_replace_mail_placeholders(
				Amapress::getOption( 'online_contrats_step_message' ),
				$amapien ) );
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
			] ), __( 'Calendrier des livraisons', 'amapress' ), true, true, 'btn btn-default' );
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
			if ( ! $allow_intermittents_inscription && $amapien->isIntermittent() ) {
				if ( ! $use_contrat_term ) {
					echo '<p><strong>' . __( 'Les commandes ne sont pas ouvertes aux intermittents.', 'amapress' ) . '</strong></p>';
				} else {
					echo '<p><strong>' . __( 'L\'inscription aux contrats n\'est pas ouverte aux intermittents.', 'amapress' ) . '</strong></p>';
				}
				$display_remaining_contrats = false;
			} elseif ( ! $allow_coadherents_inscription && $amapien->isCoAdherent() ) {
				if ( ! $use_contrat_term ) {
					echo '<p><strong>' . __( 'Les commandes doivent être faites par l\'adhérent principal.', 'amapress' ) . '</strong></p>';
				} else {
					echo '<p><strong>' . __( 'L\'inscription aux contrats doit être faite par l\'adhérent principal.', 'amapress' ) . '</strong></p>';
				}
				$display_remaining_contrats = false;
			} else {
				if ( empty( $principal_contrats ) ) {
					$adm = '';
					if ( amapress_can_access_admin() ) {
						$adm = __( 'Aucun contrat principal. Veuillez définir un contrat principal depuis ', 'amapress' )
						       . Amapress::makeLink(
								admin_url( 'edit.php?post_type=amps_contrat_inst' ),
								__( 'Edition des contrats', 'amapress' ) )
						       . '<br/>';
					}
					$closed_message = wp_unslash( Amapress::getOption( 'online_inscr_closed_message' ) );
					if ( ! $use_contrat_term ) {
						echo '<p>' . __( 'Les commandes en ligne sont closes aux amapiens sans contrat principal.', 'amapress' ) . '</p>' . $closed_message;
					} else {
						echo '<p>' . __( 'Les inscriptions en ligne sont closes aux amapiens sans contrat principal.', 'amapress' ) . '</p>' . $closed_message;
					}

					return ob_get_clean();
				} elseif ( count( $principal_contrats ) == 1 ) {
					?>
                    <p><?php echo sprintf( __( 'Pour accéder à tous nos contrats en ligne,
                        vous devez d’abord vous inscrire au contrat “<strong>%s</strong>” (%s)', 'amapress' ),
							esc_html( $principal_contrats[0]->getTitle() ),
							$principal_contrats[0]->getModel()->linkToPermalinkBlank( __( 'plus d\'infos', 'amapress' ) ) ); ?>
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
<input type="submit" class="btn btn-default btn-assist-inscr btn-assist-inscr-validate" value="' . esc_attr__( 'Confirmer', 'amapress' ) . '"/>
</form>';
						?>
                    </p>
					<?php
				} else {
					?>
                    <p><?php _e( 'Pour accéder à tous nos contrats en ligne, vous devez d’abord vous
                        inscrire à l’un des contrats suivants :', 'amapress' ) ?></p>
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
<input type="submit" class="btn btn-default btn-assist-inscr btn-assist-inscr-validate" value="' . esc_attr__( 'Confirmer', 'amapress' ) . '"/>
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
					] ), __( 'Récapitulatif des sommes dues', 'amapress' ), true, true, 'btn btn-default' );
				}
				if ( $show_delivery_details ) {
					echo Amapress::makeButtonLink( add_query_arg( [
						'step' => 'details_all_delivs',
					] ), __( 'Récapitulatif des livraisons (par date)', 'amapress' ), true, true, 'btn btn-default' );
					echo Amapress::makeButtonLink( add_query_arg( [
						'step'    => 'details_all_delivs',
						'by_prod' => '1',
					] ), __( 'Récapitulatif des livraisons (par producteur)', 'amapress' ), true, true, 'btn btn-default' );
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
							echo '<p>' . __( 'Vos commandes :', 'amapress' ) . '</p>';
						} else {
							echo '<p>' . __( 'Vos contrats :', 'amapress' ) . '</p>';
						}
					} else {
						if ( ! $use_contrat_term ) {
							echo '<p>' . __( 'Vos commandes éditables :', 'amapress' ) . '</p>';
						} else {
							echo '<p>' . __( 'Vos contrats éditables :', 'amapress' ) . '</p>';
						}
					}
				} else {
					if ( ! $use_contrat_term ) {
						echo '<p>' . __( 'Ses commandes :', 'amapress' ) . '</p>';
					} else {
						echo '<p>' . __( 'Ses contrats :', 'amapress' ) . '</p>';
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
							$contrat_print_button_text, true, true, 'btn btn-default btn-assist-inscr btn-assist-inscr-print'
						);
					}
					if ( $adh->getContrat_instance()->isPanierVariable() ) {
						$print_contrat .= Amapress::makeButtonLink( add_query_arg( [
							'step'       => 'details_all_delivs',
							'contrat_id' => $adh->ID,
						] ), __( 'Livraisons', 'amapress' ), true, true, 'btn btn-default' );

					}
					if ( $admin_mode ) {
						echo '<li style="margin-left: 35px">' . $inscription_title .
						     ( current_user_can( 'edit_post', $adh->ID ) ?
							     ' (' . Amapress::makeLink( $adh->getAdminEditLink(), __( 'Editer', 'amapress' ), true, true ) . ')<br/>' . $print_contrat . '</li>' : '' );
					} else {
						$rattrapage = $adh->getProperty( 'dates_rattrapages' );
						if ( ! Amapress::toBool( $atts['show_details_button'] ) ) {
							$show_distrib_dates     = $adh->getContrat_instance()->allowShowDistributionDates();
							$distrib_dates_end_text = $show_distrib_dates ? __( ' : ', 'amapress' ) : __( '.', 'amapress' );

							if ( $adh->getContrat_instance()->isPanierVariable() ) {
								$contrat_info = sprintf( __( 'Vous avez composé votre panier "%s" (%s) pour %s distribution(s) pour un montant total de %s € (%s)<br/>%s dates distributions%s%s%s', 'amapress' ),
									$adh->getContrat_instance()->getModelTitle(), Amapress::makeLink( add_query_arg( [
										'step'       => 'details',
										'contrat_id' => $adh->ID
									] ), __( 'Détails', 'amapress' ), true, true ), $adh->getProperty( 'nb_distributions' ), $adh->getProperty( 'total' ), $adh->getProperty( 'option_paiements' ),
									$adh->getProperty( 'nb_dates' ),
									$show_distrib_dates ? $adh->getProperty( 'dates_distribution_par_mois' ) : '', $distrib_dates_end_text,
									$show_distrib_dates && ! empty( $rattrapage ) ? '<br/>Dates de rattrages : ' . $rattrapage : '' );
							} else {
								$contrat_info = sprintf( __( 'Vous avez choisi le(s) panier(s) "%s" pour %s distribution(s) pour un montant total de %s € (%s)<br/>%s dates distributions%s%s%s', 'amapress' ),
									$adh->getProperty( 'quantites' ), $adh->getProperty( 'nb_distributions' ), $adh->getProperty( 'total' ), $adh->getProperty( 'option_paiements' ),
									$adh->getProperty( 'nb_dates' ),
									$show_distrib_dates ? $adh->getProperty( 'dates_distribution_par_mois' ) : '', $distrib_dates_end_text,
									$show_distrib_dates && ! empty( $rattrapage ) ? '<br/>Dates de rattrages : ' . $rattrapage : '' );
							}
						} else {
							$contrat_info = '';
							if ( ! $adh->getContrat_instance()->isPanierVariable() ) {
								$contrat_info .= sprintf( __( 'Vous avez choisi le(s) panier(s) "%s". ', 'amapress' ), $adh->getProperty( 'quantites' ) );
							}
							$contrat_info .= Amapress::makeButtonLink( add_query_arg( [
								'step'       => 'details',
								'contrat_id' => $adh->ID
							] ), __( 'Détails', 'amapress' ), true, true );
						}

						if ( Amapress::toBool( $atts['contact_referents'] ) ) {
							$refs_emails  = $adh->getContrat_instance()->getAllReferentsEmails( $adh->getLieuId() );
							$contrat_info .= '<br/>' . Amapress::makeLink( 'mailto:' . urlencode( implode( ',', $refs_emails ) ) . '?subject=' . urlencode( sprintf( __( 'Mon inscription %s', 'amapress' ), $adh->getTitle() ) ), __( 'Contacter les référents', 'amapress' ) );
						}
						$coadherents_info = $adh->getAdherent()->getCoAdherentsList( true, false, false, $adh->getContrat_instanceId() );
						if ( Amapress::hasPartialCoAdhesion() ) {
							$coadherents_ids = AmapressContrats::get_related_users( $adh->getAdherentId(),
								false, null, $adh->getContrat_instanceId(), false );
						} else {
							$coadherents_ids = AmapressContrats::get_related_users( $adh->getAdherentId(), false, null, null, false );
						}
						if ( count( $coadherents_ids ) > 1 ) {
							$coadherents_info .= '<br/><form method="get" action="' . esc_attr( get_permalink() ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="coadhcalendar" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="inscr_id" value="' . $adh->ID . '" />
<input type="submit" value="' . esc_attr__( 'Editer le calendrier de partage', 'amapress' ) . '" class="btn btn-secondary btn-assist-inscr btn-assist-inscr-calendar"
 	title="' . esc_attr( __( 'Editer le calendrier de partage des paniers entre co-adhérents', 'amapress' ) ) . '"/>
</form>';
						}
						$coadherents_info = '<br /><strong>' . __( 'Co-adhérents', 'amapress' ) . '</strong> : ' . $coadherents_info;
						$edit_contrat     = '';
						if ( 'stp' == $adh->getMainPaiementType() && AmapressAdhesion::TO_CONFIRM == $adh->getStatus() ) {
							$edit_contrat .= '<br/><form method="get" action="' . esc_attr( get_permalink() ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="stripe" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="inscr_id" value="' . $adh->ID . '" />
<input type="submit" value="' . esc_attr__( 'Payer en ligne', 'amapress' ) . '" class="btn btn-danger btn-assist-inscr btn-assist-inscr-stripe"
 	title="' . esc_attr( __( 'Payer en ligne et valider l\'inscription. ', 'amapress' ) . ( $adh->canSelfEdit() ? __( 'Une fois payée, l\'inscription ne sera plus modifiable.', 'amapress' ) : '' ) ) . '"/>
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
<input type="submit" value="' . esc_attr__( 'Modifier', 'amapress' ) . '" class="btn btn-default btn-assist-inscr btn-assist-inscr-edit" />
</form>';
							if ( ! $adh->getContrat_instance()->isCommandeVariable() ) {
								$edit_contrat .= '<form method="get" style="display: inline-block; margin-left: 5px" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="details" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $adh->ID . '" />
<input type="hidden" name="cancel_inscr_id" value="' . $adh->ID . '" />
<input type="submit" value="' . esc_attr__( 'Annuler', 'amapress' ) . '" class="btn btn-default btn-assist-inscr btn-assist-inscr-cancel" />
</form>';
							}
						}
						echo '<li style="margin-left: 35px"><strong>' . $inscription_title . '</strong>' . $coadherents_info . '<br/><em style="font-size: 0.9em">' . $contrat_info . '</em>' . $edit_contrat . '<br/>' . $print_contrat . '</li>';
					}
				}
				echo '</ul>';
			}
			if ( $allow_inscriptions ) {
				if ( ! $admin_mode && ! $allow_coadherents_inscription && $amapien->isCoAdherent() ) {
					if ( ! $use_contrat_term ) {
						echo '<p><strong>' . __( 'Les commandes doivent être passées par l\'adhérent principal.', 'amapress' ) . '</strong></p>';
					} else {
						echo '<p><strong>' . __( 'L\'inscription aux contrats doit être faite par l\'adhérent principal.', 'amapress' ) . '</strong></p>';
					}
					$display_remaining_contrats = false;
				} else {
					if ( ! empty( $user_subscribable_contrats ) ) {
						if ( ! $admin_mode ) {
							if ( ! $use_contrat_term ) {
								echo '<p>' . __( 'Quelle commande souhaitez vous passer ?', 'amapress' ) . '</p>';
							} else {
								echo '<p>' . __( 'A quel contrat souhaitez-vous vous inscrire ?', 'amapress' ) . '</p>';
							}
						} else {
							if ( ! $use_contrat_term ) {
								echo '<p>' . __( 'Quelle commande souhaitez vous passer pour cet amapien ?', 'amapress' ) . '</p>';
							} else {
								echo '<p>' . __( 'A quel contrat souhaitez-vous vous inscrire cet amapien ?', 'amapress' ) . '</p>';
							}
						}
					}
				}
			}
		} else {
			if ( ! $admin_mode && ! $allow_coadherents_inscription && $amapien->isCoAdherent() && $allow_inscriptions ) {
				if ( ! $use_contrat_term ) {
					echo '<p><strong>' . __( 'Les commandes doivent être passées par l\'adhérent principal.', 'amapress' ) . '</strong></p>';
				} else {
					echo '<p><strong>' . __( 'L\'inscription aux contrats doit être faite par l\'adhérent principal.', 'amapress' ) . '</strong></p>';
				}
				$display_remaining_contrats = false;
			} else {
				if ( ! $admin_mode ) {
					if ( $show_current_inscriptions ) {
						if ( ! $use_contrat_term ) {
							echo '<p>' . __( 'Vous n\'avez pas encore de passé de commandes', 'amapress' ) . '</p>';
						} elseif ( $allow_inscriptions ) {
							echo '<p>' . __( 'Vous n\'avez pas encore de contrats', 'amapress' ) . '</p>';
						}
					}
					if ( $allow_inscriptions ) {
						if ( ! $use_contrat_term ) {
							echo '<p>' . __( 'Vous pouvez vous passer les commandes ci-dessous :', 'amapress' ) . '</p>';
						} else {
							echo '<p>' . __( 'Vous pouvez vous inscrire aux contrats ci-dessous :', 'amapress' ) . '</p>';
						}
					}
				} else {
					echo '<p>' . __( 'Il/Elle n\'a pas encore de contrats', 'amapress' ) . '</p>';
					echo '<p>' . __( 'Vous pouvez l\'inscrire aux autres contrats ci-dessous :', 'amapress' ) . '</p>';
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
				$adhs_model_ids             = array_map( function ( $a ) {
					/** @var AmapressAdhesion $a */
					return $a->getContrat_instance()->getModelId();
				}, array_filter(
					$adhs,
					function ( $a ) use ( $subscribable_contrats_ids ) {
						/** @var AmapressAdhesion $a */
						return in_array( $a->getContrat_instanceId(), $subscribable_contrats_ids );
					}
				) );
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $adhs_model_ids ) {
					/** @var AmapressContrat_instance $c */
					return ! in_array( $c->getModelId(), $adhs_model_ids );
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
						$real_date   = $contrat->getRealDateForDistribution( $d );
						$close_hours = $contrat->getCloseHours( $before_close_hours );

						return ( Amapress::start_of_day( $real_date ) - $close_hours * HOUR_IN_SECONDS ) > amapress_time();
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
						$contrat_principal_lieux = array_filter(
							$contrat->getLieux(),
							function ( $l ) {
								return $l->isPrincipal();
							}
						);
						if ( ! empty( $contrat_principal_lieux )
						     && ! in_array( $adh_paiement->getLieuId(), $contrat->getLieuxIds() ) ) {
							return false;
						}
					}
					$dates           = array_values( $contrat->getListe_dates() );
					$allow_all_dates = Amapress::toBool( $atts['allow_inscription_all_dates'] );
					if ( $contrat->isPanierVariable() ) {
						$allow_all_dates = true;
					}
					$dates_before_cloture = array_filter( $dates, function ( $d ) use ( $contrat ) {
						$real_date = $contrat->getRealDateForDistribution( $d );

						return Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() );
					} );
					$dates                = array_filter( $dates, function ( $d ) use ( $contrat, $allow_all_dates, $before_close_hours, $dates_before_cloture ) {
						$real_date   = $contrat->getRealDateForDistribution( $d );
						$close_hours = $contrat->getCloseHours( $before_close_hours );

						return ( Amapress::start_of_day( $real_date ) - $close_hours * HOUR_IN_SECONDS ) > amapress_time()
						       && ( $allow_all_dates || empty( $dates_before_cloture ) || Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() ) );
					} );

					return ! empty( $dates );
				} );
			}
			if ( ! empty( $user_subscribable_contrats ) ) {
				if ( Amapress::hasPartialCoAdhesion() && $amapien->isCoAdherent() ) {
					echo '<p>' . __( 'Vous êtes co-adhérent. Vous pouvez inscrire uniquement en votre nom propre. L\'adhérent principal doit effectuer vos inscriptions communes.', 'amapress' ) . '</p>';
				}
				if ( ! $use_contrat_term ) {
					echo '<p>' . __( 'Commandes disponibles :', 'amapress' ) . '</p>';
				} else {
					echo '<p>' . __( 'Contrats disponibles :', 'amapress' ) . '</p>';
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
								echo '<li style="margin-left: 35px">';
								echo sprintf( __( '%s, contrat <strong>COMPLET (%s parts)</strong> :<br/>%s (nb maximum de parts et/ou nb maximum de parts par panier)', 'amapress' ), $contrat_title, $contrat->getAdherentsEquivalentQuantites(), Amapress::makeLink( $contrat->getAdminEditLink(), __( 'Editer ses quota', 'amapress' ), true, true ) );
								echo '</li>';
							} else {
								echo '<li style="margin-left: 35px">';
								echo sprintf( __( '%s, contrat <strong>COMPLET (%s amapiens)</strong> :<br/>%s (nb maximum d\'amapiens/parts et/ou nb maximum d\'amapiens/parts par panier)', 'amapress' ), $contrat_title, $contrat->getAdherentsCount(), Amapress::makeLink( $contrat->getAdminEditLink(), __( 'Editer ses quota', 'amapress' ), true, true ) );
								echo '</li>';
							}
						} else {
							echo '<li style="margin-left: 35px">';
							echo $contrat_title . ' (' . Amapress::makeLink( $contrat->getAdminEditLink(), __( 'Editer', 'amapress' ), true, true ) . ') : <br/><a class="button button-secondary" href="' . esc_attr( $inscription_url ) . '">' . __( 'Ajouter une inscription', 'amapress' ) . '</a>';
							echo '</li>';
						}
					} else {
						$deliveries_dates = '';
						if ( $contrat->allowShowDistributionDates() && count( $contrat->getListe_dates() ) <= $show_max_deliv_dates ) {
							$deliveries_dates = sprintf( __( ' - Livraison(s) %s -', 'amapress' ),
								implode( ', ', array_map( function ( $d ) {
									return date_i18n( 'd/m/Y', $d );
								}, $contrat->getListe_dates() ) )
							);
						}
						if ( $show_close_date ) {
							$deliveries_dates .= sprintf( __( ' - <strong>Clôture inscriptions %s</strong>', 'amapress' ),
								date_i18n( 'd/m/Y', $contrat->getDate_cloture() )
							);
						}
						echo '<li style="margin-left: 35px">' . $contrat_title . $deliveries_dates . ' (' . $contrat->getModel()->linkToPermalinkBlank( __( 'plus d\'infos', 'amapress' ) ) . ') : 
<br/>
<form method="get" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $contrat->ID . '" />
<input type="submit" value="' . esc_attr__( 'M\'inscrire', 'amapress' ) . '" class="btn btn-default btn-assist-inscr" />
</form></li>';
					}
				}
				echo '</ul>';
			} else {
				if ( ! $admin_mode ) {
					if ( empty( $subscribable_contrats ) ) {
						$closed_message = wp_unslash( Amapress::getOption( 'online_inscr_closed_message' ) );
						if ( ! $use_contrat_term ) {
							echo '<p>' . __( 'Les commandes sont closes.', 'amapress' ) . '</p>' . $closed_message;
						} else {
							echo '<p>' . __( 'Les inscriptions en ligne sont closes.', 'amapress' ) . '</p>' . $closed_message;
						}
					} else {
						$mes_contrat_href = esc_attr( Amapress::get_mes_contrats_page_href() );
						if ( ! $use_contrat_term ) {
							echo '<p>';
							echo __( 'Vous êtes déjà passé toutes les commandes disponibles.', 'amapress' );
							if ( ! empty( $mes_contrat_href ) ) {
								echo '<br />' . __( 'Pour accéder à vos commandes, cliquez ', 'amapress' ) . "<a href='$mes_contrat_href'>" . __( 'ici', 'amapress' ) . '</a>';
							}
							echo '</p>';
						} else {
							echo '<p>' . __( 'Vous êtes déjà inscrit à tous les contrats.', 'amapress' );
							if ( ! empty( $mes_contrat_href ) ) {
								echo '<br />' . __( 'Pour accéder à vos contrats, cliquez ', 'amapress' ) . "<a href='$mes_contrat_href'>" . __( 'ici', 'amapress' ) . '</a>';
							}
							echo '</p>';
						}
					}
				} else {
					echo '<p>' . __( 'Il/Elle est inscrit à tous les contrats que vous gérez.', 'amapress' ) . '</p>';
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
			echo '<p>' . __( 'J\'ai fini :', 'amapress' ) . '<br/>
<form method="get" action="' . esc_attr( $the_end_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="the_end" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="submit" value="' . esc_attr__( 'Terminer', 'amapress' ) . '" class="btn btn-default btn-assist-end" />
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

			return $additional_css . '<p>' . ( sprintf( __( '<strong>Attention</strong> : le contrat %s n\'a aucun lieu de livraison associé. Veuillez corriger ce contrat avant de poursuivre.', 'amapress' ), Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle() ) ) ) . '</p>';
		}
		$step_name = wp_unslash( Amapress::getOption( 'online_subscription_date_lieu_step_name' ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . ' - ' . esc_html( $contrat->getTitle() ) . '</h4>';
		?>
		<?php echo wp_unslash( amapress_replace_mail_placeholders(
			Amapress::getOption( 'online_subscription_date_lieu_step_message' ),
			null, $contrat ) ); ?>
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
				if ( $contrat->isPanierVariable() ) {
					$allow_all_dates = true;
				}
				$dates = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours, $dates_before_cloture, $allow_all_dates ) {
					$real_date   = $contrat->getRealDateForDistribution( $d );
					$close_hours = $contrat->getCloseHours( $before_close_hours );

					return ( Amapress::start_of_day( $real_date ) - $close_hours * HOUR_IN_SECONDS ) > amapress_time()
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
					echo '<p>' . sprintf( __( 'Les commandes en ligne sont ouvertes du “%s” au “%s”, hors de cette période, je prends contact pour préciser ma demande : “<a href="mailto:%s">%s</a>”', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ), date_i18n( 'd/m/Y', $contrat->getDate_cloture() ), esc_attr( $atts['email'] ), esc_html( $atts['email'] ) ) . '</p>';
				} else {
					echo '<p>' . sprintf( __( 'Les inscriptions en ligne sont ouvertes du “%s” au “%s”, hors de cette période, je prends contact pour préciser ma demande : “<a href="mailto:%s">%s</a>”', 'amapress' ), date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ), date_i18n( 'd/m/Y', $contrat->getDate_cloture() ), esc_attr( $atts['email'] ), esc_html( $atts['email'] ) ) . '</p>';
				}
			}
			echo '<p><strong>' . __( 'Date', 'amapress' ) . '</strong></p>';
			/** @var AmapressContrat_instance $contrat */
			if ( $contrat->isCommandeVariable() ) {
				echo '<input type="hidden" name="start_date" value="' . $first_avail_date . '" />';
				$first_date_dist = $contrat->getRealDateForDistribution( $first_contrat_date );
				$last_date_dist  = $contrat->getDate_fin();

				echo '<p>' . sprintf( __( 'Choix libre pour chaque date de distributions sur la période du %s au %s (%s dates de distributions)', 'amapress' ),
						date_i18n( 'l d F Y', $first_date_dist ),
						date_i18n( 'l d F Y', $last_date_dist ),
						count( $contrat->getListe_dates() ) ) . '</p>';
			} else {
				if ( ! $is_started && ! $admin_mode ) {
					echo '<input type="hidden" name="start_date" value="' . $first_avail_date . '" />';
					$first_date_dist = $contrat->getRealDateForDistribution( $first_contrat_date );
					$last_date_dist  = $contrat->getDate_fin();
					if ( $contrat->getMaxContratMonths() > 0 ) {
						$last_date_dist = Amapress::add_a_month( $contrat->getDate_debut(), $contrat->getMaxContratMonths() );
					}
					if ( 1 == count( $contrat->getListe_dates() ) ) {
						echo '<p>' . sprintf( __( 'Je m’inscris pour la distribution ponctuelle du %s', 'amapress' ), date_i18n( 'l d F Y', $first_date_dist ) ) . '</p>';
					} else {
						if ( ! $use_contrat_term ) {
							echo '<p>' . sprintf( __( 'Je passe commande pour la période du %s au %s (%s dates de distributions)', 'amapress' ), date_i18n( 'l d F Y', $first_date_dist ), date_i18n( 'l d F Y', $last_date_dist ), count( $contrat->getListe_dates() ) ) . '</p>';

						} else {
							echo '<p>' . sprintf( __( 'Je m’inscris pour la période complète : du %s au %s (%s dates de distributions)', 'amapress' ), date_i18n( 'l d F Y', $first_date_dist ), date_i18n( 'l d F Y', $last_date_dist ), count( $contrat->getListe_dates() ) ) . '</p>';
						}
					}
				} else {
					?>
                    <p><?php
						if ( ! $admin_mode ) {
							if ( ! $use_contrat_term ) {
								echo __( 'La première date de livraison est passée, je récupère mon panier à la prochaine distribution ou je choisis une date ultérieure :', 'amapress' );
							} else {
								echo __( 'Je m\'inscris en cours de saison, je récupère mon panier à la prochaine distribution ou je choisis une date ultérieure :', 'amapress' );
							}
						} else {
							echo __( 'A partir de quel date doit-il commencer son contrat :', 'amapress' );
						}
						?>
                        <br/>
                        <select name="start_date" id="start_date" class="required">
							<?php
							foreach ( $dates as $date ) {
								$real_date = $contrat->getRealDateForDistribution( $date );
								$val_date  = date_i18n( 'd/m/Y', $real_date );
								if ( Amapress::start_of_day( $date ) != Amapress::start_of_day( $real_date ) ) {
									$val_date .= sprintf( __( ' initialement le %s', 'amapress' ), date_i18n( 'd/m/Y', $date ) );
								}
								if ( $date == $first_avail_date ) {
									if ( $is_started ) {
										$val_date = sprintf( __( 'Prochaine distribution (%s)', 'amapress' ), $val_date );
									} else {
										$val_date = sprintf( __( 'Première distribution (%s)', 'amapress' ), $val_date );
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
			}

			echo '<p><strong>' . __( 'Lieu', 'amapress' ) . '</strong></p>';
			if ( count( $lieux ) > 1 ) {
				if ( ! $admin_mode ) {
					echo '<p style="margin-bottom: 0">' . __( 'Je récupérerai mon panier à :', 'amapress' ) . '</p>';
				} else {
					echo '<p style="margin-bottom: 0">' . __( 'Veuillez chosir son lieu de distribution :', 'amapress' ) . '</p>';
				}
				foreach ( $lieux as $lieu ) {
					$lieu_id    = $lieu->ID;
					$lieu_title = $lieu->linkToPermalinkBlank( esc_html( $lieu->getLieuTitle() ) ) . ' (' . esc_html( $lieu->getFormattedAdresse() ) . ')';
					$checked    = checked( $edit_inscription && $lieu_id == $edit_inscription->getLieuId(), true, false );
					echo "<p style='margin-top: 0;margin-bottom: 0'><input id='lieu-$lieu_id' name='lieu_id' $checked value='$lieu_id' type='radio' class='required' /><label for='lieu-$lieu_id'>$lieu_title</label></p>";
				}
			} else {
				$lieu_title = $lieux[0]->linkToPermalinkBlank( esc_html( $lieux[0]->getLieuTitle() ) ) . ' (' . esc_html( $lieux[0]->getFormattedAdresse() ) . ')';
				echo '<p>' . sprintf( __( 'Je récupérerai mon panier à %s', 'amapress' ), $lieu_title ) . '</p>';
				echo '<input name="lieu_id" value="' . $lieux[0]->ID . '" type="hidden" />';
			}

			if ( Amapress::hasPartialCoAdhesion() && $contrat->getAllowCoadherents() ) {
				echo '<br/><p><strong>' . __( 'Co-adhérents', 'amapress' ) . '</strong></p>';

				echo $contrat->getCoadherentsMention();

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
					echo '<p>' . __( 'Vous n\'avez aucun co-adhérent déclaré', 'amapress' ) . '</p>';
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
            <input type="submit" value="<?php echo esc_attr__( 'Valider', 'amapress' ) ?>"
                   class="btn btn-default btn-assist-inscr btn-assist-inscr-validate"/>
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
		if ( ! in_array( $adh->getAdherentId(), $user_ids )
		     && ! in_array( $adh->getAdherent2Id(), $user_ids )
		     && ! in_array( $adh->getAdherent3Id(), $user_ids )
		     && ! in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( __( 'Ce contrat n\'est pas à vous !', 'amapress' ) ); //phpcs:ignore
		}

		$is_success = isset( $_REQUEST['success'] );

		if ( $is_success ) {
			$adh->setStatus( AmapressAdhesion::CONFIRMED );
			$adh->preparePaiements( [
				1 => [
					'num'      => '',
					'date'     => amapress_time(),
					'banque'   => __( 'Stripe', 'amapress' ),
					'emetteur' => '',
				]
			], true, AmapressAdhesion_paiement::RECEIVED, false );
		}

		$message = wp_unslash( $is_success ?
			Amapress::getOption( 'online_subscription_stripe_success' ) :
			Amapress::getOption( 'online_subscription_stripe_cancel' ) );
		$message = str_replace( '%%contrats_step_link%%', Amapress::makeButtonLink( $contrats_step_url, __( 'Poursuivre', 'amapress' ) ), $message );
		$message = str_replace( '%%contrats_step_href%%', $contrats_step_url, $message );


		$print_contrat = Amapress::makeButtonLink(
			add_query_arg( [
				'inscr_assistant' => 'generate_contrat',
				'inscr_id'        => $adh->ID,
				'inscr_key'       => amapress_sha_secret( $key )
			] ),
			$contrat_print_button_text, true, true, 'btn btn-default btn-assist-inscr btn-assist-inscr-print'
		);
		$message       = str_replace( '%%print_button%%', $print_contrat, $message );

		$message = amapress_replace_mail_placeholders( $message, AmapressUser::getBy( $user_id ), $adh );

		echo $message; //phpcs:ignore

	} else if ( 'coadhcalendar' == $step ) {
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

		/* @var AmapressAdhesion $adh */
		$adh = AmapressAdhesion::getBy( intval( $_GET['inscr_id'] ) );
		if ( empty( $adh ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		if ( ! in_array( $adh->getAdherentId(), $user_ids )
		     && ! in_array( $adh->getAdherent2Id(), $user_ids )
		     && ! in_array( $adh->getAdherent3Id(), $user_ids )
		     && ! in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( __( 'Ce contrat n\'est pas à vous !', 'amapress' ) ); //phpcs:ignore
		}

		$contrat          = $adh->getContrat_instance();
		$dates            = $contrat->getRemainingDates();
		$current_calendar = $adh->getShareCalendar();
		if ( empty( $current_calendar ) ) {
			$current_calendar = [];
			foreach ( $dates as $date ) {
				$current_calendar[ strval( $date ) ] = null;
			}

		}

		if ( Amapress::hasPartialCoAdhesion() ) {
			$coadh_user_ids = AmapressContrats::get_related_users( $adh->getAdherent()->getUser()->ID,
				false, null, $adh->getContrat_instanceId(), false );
		} else {
			$coadh_user_ids = AmapressContrats::get_related_users( $adh->getAdherent()->getUser()->ID,
				false, null, null, false );
		}

		$coadherents = [];
		foreach ( $coadh_user_ids as $coadh_id ) {
			$coadh = AmapressUser::getBy( $coadh_id );

			$coadherents[ $coadh->ID ] = $coadh->getDisplayName();
		}

		echo '<h4>' . __( 'Calendrier de partage entre co-adhérents', 'amapress' ) . '</h4>';

		echo Amapress::getOption( 'online_subscription_share_calendar' );

		?>
        <p>
            <button id="btnAlternate" class="btn btn-secondary">Alterner co-adhérents</button>
        </p>
        <script type="text/javascript">
            jQuery(function ($) {
                $('#btnAlternate').on('click', function () {
                    var firstSelect = $(".coadh_select:first");
                    var optionsCount = $('option', firstSelect).length;
                    var startIndex = firstSelect.prop('selectedIndex');
                    $('.coadh_select').each(function () {
                        $(this).prop('selectedIndex', startIndex);
                        startIndex = (startIndex + 1) % optionsCount;
                    });
                });
            });
        </script>
        <form action="<?php echo esc_attr( get_permalink() ); ?>" method="post" class="amapress_validate">
            <input type="hidden" name="hash" value="<?php echo amapress_sha_secret( "{$user_id}:{$adh->ID}" ) ?>"/>
            <input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>"/>
            <input type="hidden" name="inscr_key" value="<?php echo esc_attr( amapress_sha_secret( $key ) ); ?>"/>
            <input type="hidden" name="inscr_assistant" value="calendar_save"/>
            <input type="hidden" name="user_id" value="<?php echo $user_id ?>"/>
            <input type="hidden" name="inscr_id" value="<?php echo $adh->ID ?>"/>

            <table>
                <thead>
                <tr>
                    <th>Distribution</th>
                    <th>Co-adhérent</th>
                </thead>
                <tbody>
				<?php
				$current_date = Amapress::start_of_day( amapress_time() );
				foreach ( $current_calendar as $date => $coadh_id ) {
					echo '<tr>';
					echo '<td>' . date_i18n( 'd/m/Y', intval( $date ) ) . '</td>';
					echo '<td><select class="coadh_select" ' . disabled( Amapress::start_of_day( $date ) < $current_date, true, false ) . ' name="calendar[' . $date . ']">' . tf_parse_select_options( $coadherents,
							$coadh_id,
							false ) . '</select></td>';
					echo '</tr>';
				}
				?>
                </tbody>
            </table>

            <br/>
            <input type="submit" value="<?php echo esc_attr__( 'Enregistrer', 'amapress' ) ?>"
                   class="btn btn-default btn-assist-inscr btn-assist-inscr-save"/>
        </form>
		<?php
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
		if ( ! in_array( $adh->getAdherentId(), $user_ids )
		     && ! in_array( $adh->getAdherent2Id(), $user_ids )
		     && ! in_array( $adh->getAdherent3Id(), $user_ids )
		     && ! in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( __( 'Ce contrat n\'est pas à vous !', 'amapress' ) ); //phpcs:ignore
		}
		$min_stripe_amount = $adh->getContrat_instance()->getStripeMinAmount();
		if ( $min_stripe_amount > 0 ) {
			if ( $adh->getTotalAmount() < $min_stripe_amount ) {
				wp_die( esc_html( sprintf( __( 'Le paiement en ligne est autorisé à partir de %s', 'amapress' ), Amapress::formatPrice( $min_stripe_amount ) ) ) );
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
						'name'        => get_bloginfo( 'name' ) . ' - ' . $adh->getContrat_instance()->getTitle(),
						'description' => $adh->getTitle(),
						'amount'      => (int) ( $adh->getTotalAmount() * 100 ),
						'currency'    => 'eur',
						'quantity'    => 1,
					]
				],
				'payment_intent_data'  => [
					'description' => get_bloginfo( 'name' ) . ' - ' . $adh->getContrat_instance()->getTitle(),
					'metadata'    => [
						'inscription_url' => $adh->getAdminEditLink(),
						'quantites_url'   => admin_url( 'admin.php?page=contrats_quantites_next_distrib&tab=contrat-quant-tab-' . $adh->getContrat_instanceId() . '&with_adherent=T&with_prices=T' ),
						'contrat'         => $adh->getContrat_instance()->getTitle(),
						'amap'            => get_bloginfo( 'name' ),
					],
				],
				'metadata'             => [
					'inscriptions_url' => $adh->getAdminEditLink(),
					'contrat'          => $adh->getContrat_instance()->getTitle(),
					'amap'             => get_bloginfo( 'name' ),
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
			echo '<p>' . __( 'Réglement en ligne en cours...', 'amapress' ) . '</p>';
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
//		if ( ! $show_delivery_details ) {
//			wp_die( $invalid_access_message ); //phpcs:ignore
//		}
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
			if ( ! in_array( $adh->getAdherentId(), $user_ids )
			     && ! in_array( $adh->getAdherent2Id(), $user_ids )
			     && ! in_array( $adh->getAdherent3Id(), $user_ids )
			     && ! in_array( $adh->getAdherent4Id(), $user_ids )
			) {
				wp_die( __( 'Ce contrat n\'est pas à vous !', 'amapress' ) ); //phpcs:ignore
			}
			$contrat_id = $adh->getContrat_instanceId();
		}

		echo amapress_get_details_all_deliveries( $user_id, $ignore_renouv_delta, $by_prod,
			$contrat_id, isset( $_GET['grp_by_grp'] ), false, true );
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

		/** @var AmapressAdhesion $adh */
		$adh = AmapressAdhesion::getBy( intval( $_GET['contrat_id'] ) );
		if ( ! in_array( $adh->getAdherentId(), $user_ids )
		     && ! in_array( $adh->getAdherent2Id(), $user_ids )
		     && ! in_array( $adh->getAdherent3Id(), $user_ids )
		     && ! in_array( $adh->getAdherent4Id(), $user_ids )
		) {
			wp_die( __( 'Ce contrat n\'est pas à vous !', 'amapress' ) ); //phpcs:ignore
		}
		if ( ! empty( $_GET['cancel_inscr_id'] ) ) {
			if ( intval( $_GET['cancel_inscr_id'] ) != $adh->ID ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			if ( ! $adh->canSelfEdit() ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			if ( $adh->getContrat_instance()->isCommandeVariable() ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}
			if ( isset( $_GET['confirm'] ) ) {
				if ( Amapress::toBool( $atts['send_referents'] ) ) {
					$adh->sendReferentsNotificationMail( false, $notify_email, 'cancel' );
				}

				$adh->sendCancelMail();

				if ( ! wp_delete_post( $adh->ID, true ) ) {
					wp_die( $invalid_access_message ); //phpcs:ignore
				}

				if ( ! $use_contrat_term ) {
					echo '<p>' . sprintf( __( 'Votre commande %s a été annulée avec succès.', 'amapress' ), esc_html( $adh->getTitle() ) ) . '</p>';
				} else {
					echo '<p>' . sprintf( __( 'Votre inscription %s a été annulée avec succès.', 'amapress' ), esc_html( $adh->getTitle() ) ) . '</p>';
				}
				if ( ! $use_contrat_term ) {
					echo '<p>' . Amapress::makeLink( $contrats_step_url, __( 'Retourner à la liste des commandes', 'amapress' ) ) . '</p>';
				} else {
					echo '<p>' . Amapress::makeLink( $contrats_step_url, __( 'Retourner à la liste des contrats', 'amapress' ) ) . '</p>';
				}

				return ob_get_clean();
			} else {
				if ( ! $use_contrat_term ) {
					echo '<p>' . __( 'Vous avez demandé l\'annulation de la commande suivante :', 'amapress' ) . '<br/>';
				} else {
					echo '<p>' . __( 'Vous avez demandé l\'annulation de l\'inscription suivante :', 'amapress' ) . '<br/>';
				}
				echo Amapress::makeLink( add_query_arg( 'confirm', 'T' ), __( 'Confirmer l\'annulation', 'amapress' ) ) . '<br/>
' . Amapress::makeLink( $contrats_step_url, __( 'Retourner à la liste des contrats', 'amapress' ) ) . '</p>';
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
				$contrat_print_button_text, true, true, 'btn btn-default btn-assist-inscr btn-assist-inscr-print'
			);
		}
		if ( $adh->getContrat_instance()->isPanierVariable() ) {
			$print_contrat .= Amapress::makeButtonLink( add_query_arg( [
				'step'       => 'details_all_delivs',
				'contrat_id' => $adh->ID,
			] ), __( 'Livraisons', 'amapress' ), true, true, 'btn btn-default' );

		}

		$show_distrib_dates     = $adh->getContrat_instance()->allowShowDistributionDates();
		$distrib_dates_end_text = $show_distrib_dates ? __( ' : ', 'amapress' ) : __( '.', 'amapress' );

		$rattrapage = $adh->getProperty( 'dates_rattrapages' );
		if ( $adh->getContrat_instance()->isPanierVariable() ) {
			$contrat_info = sprintf( __( '<p>Vous avez composé votre panier "%s" pour %s distribution(s) pour un montant total de %s €</p><h3>Distributions</h3><p>%s dates distributions%s%s%s</p>', 'amapress' ),
				$adh->getContrat_instance()->getModelTitle(), $adh->getProperty( 'nb_distributions' ), $adh->getProperty( 'total' ),
				$adh->getProperty( 'nb_dates' ),
				$show_distrib_dates ? $adh->getProperty( 'dates_distribution_par_mois' ) : '',
				$show_distrib_dates && ! empty( $rattrapage ) ? '<br/>Dates de rattrages : ' . $rattrapage : '',
				$distrib_dates_end_text );
		} else {
			$contrat_info = sprintf( __( '<p>Vous avez choisi le(s) panier(s) "%s" pour %s distribution(s) pour un montant total de %s €</p><h3>Distributions</h3><p>%s dates distributions%s%s%s</p>', 'amapress' ),
				$adh->getProperty( 'quantites' ), $adh->getProperty( 'nb_distributions' ), $adh->getProperty( 'total' ),
				$adh->getProperty( 'nb_dates' ),
				$show_distrib_dates ? $adh->getProperty( 'dates_distribution_par_mois' ) : '',
				$show_distrib_dates && ! empty( $rattrapage ) ? '<br/>Dates de rattrages : ' . $rattrapage : '',
				$distrib_dates_end_text );
		}

		$contrat_info .= '<h3>' . __( 'Lieu', 'amapress' ) . '</h3><p>' . Amapress::makeLink( $adh->getLieu()->getPermalink(), $adh->getProperty( 'lieu' ), true, true ) . '</p>';
		$contrat_info .= '<h3>' . __( 'Détails', 'amapress' ) . '</h3><p>' . $adh->getProperty( 'quantites_prix' ) . '</p>';
		if ( ! empty( $adh->getProducteurMessage() ) ) {
			$contrat_info .= '<div>' . wpautop( $adh->getProducteurMessage() ) . '</div>';
		}
		$contrat_info .= '<p>' . $print_contrat . '</p>';

		if ( $adh->getDon_distribution() > 0 ) {
			$contrat_info .= '<h3>' . $adh->getContrat_instance()->getDon_distributionLabel() . '</h3><p>' . $adh->getProperty( 'don_distribution' ) . __( '€', 'amapress' ) . ' x ' . $adh->getProperty( 'nb_dates' ) . __( ' distribution(s)', 'amapress' ) . ' = ' . $adh->getProperty( 'don_total' ) . __( '€', 'amapress' ) . '</p>';
		}
		$contrat_info .= '<h3>' . __( 'Options de paiements', 'amapress' ) . '</h3><p>' . $adh->getProperty( 'option_paiements' ) . '</p><p>' . $adh->getProperty( 'paiements_mention' ) . '</p><p>' . __( 'Ordre: ', 'amapress' ) . $adh->getProperty( 'paiements_ordre' ) . '</p>';
		$refs_emails  = $adh->getContrat_instance()->getAllReferentsEmails( $adh->getLieuId() );
		if ( ! empty( $adh->getMessage() ) ) {
			$contrat_info .= '<h3>' . __( 'Message pour les référents', 'amapress' ) . '</h3>';
			$contrat_info .= '<div>' . wpautop( $adh->getMessage() ) . '</div>';
		}
		$contrat_info .= '<h3>' . __( 'Référents', 'amapress' ) . '</h3>';
		$contrat_info .= '<p>' . $adh->getProperty( 'referents' ) . '</p>';
		$contrat_info .= '<p>' . Amapress::makeLink( 'mailto:' . urlencode( implode( ',', $refs_emails ) ) . '?subject=' . urlencode( sprintf( __( 'Mon inscription %s', 'amapress' ), $adh->getTitle() ) ), __( 'Contacter les référents', 'amapress' ) ) . '</p>';
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

		$dates      = $contrat->getRemainingDates( $start_date );
		$rattrapage = $contrat->getFormattedRattrapages( $dates );

		$step_name = wp_unslash( Amapress::getOption( 'online_subscription_panier_step_name' ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name
		     . ' - ' . esc_html( $contrat->getTitle() )
		     . ' (' . $contrat->getModel()->linkToPermalinkBlank( __( 'plus d\'infos', 'amapress' ) ) . ')</h4>';

		echo wp_unslash( amapress_replace_mail_placeholders(
			Amapress::getOption( 'online_subscription_panier_step_message' ),
			null, $contrat ) );

		$min_total     = $contrat->getMinEngagement();
		$max_no_panier = $contrat->getMaxNoDistribution();

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

		$show_distrib_dates     = $contrat->allowShowDistributionDates();
		$distrib_dates_end_text = $show_distrib_dates ? __( ' :', 'amapress' ) : __( '.', 'amapress' );

		$rattrapage_renvoi = '';
		if ( $show_distrib_dates && ! empty( $rattrapage ) ) {
			$rattrapage_renvoi = '<a href="#dist_rattrapages">*</a>';
		}

		if ( count( $contrat->getListe_dates() ) == count( $dates ) ) {
			if ( ! $use_contrat_term ) {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">' . sprintf( __( 'Cette commande comporte “<strong>%d</strong>” distributions (étalées sur “<strong>%s</strong>” dates%s)%s', 'amapress' ),
						$dates_factors, count( $dates ), $rattrapage_renvoi, $distrib_dates_end_text ) . '</p>';
			} else {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">' . sprintf( __( 'Ce contrat comporte “<strong>%d</strong>” distributions (étalées sur “<strong>%s</strong>” dates%s)%s', 'amapress' ),
						$dates_factors, count( $dates ), $rattrapage_renvoi, $distrib_dates_end_text ) . '</p>';
			}
		} else {
			if ( ! $use_contrat_term ) {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">' . sprintf( __( 'Il reste “<strong>%d</strong>” distributions (étalées sur “<strong>%s</strong>” dates%s) avant la fin de cette commande%s', 'amapress' ),
						$dates_factors, count( $dates ), $rattrapage_renvoi, $distrib_dates_end_text ) . '</p>';
			} else {
				echo '<p style="padding-bottom: 0; margin-bottom: 0">' . sprintf( __( 'Il reste “<strong>%d</strong>” distributions (étalées sur “<strong>%s</strong>” dates%s) avant la fin de ce contrat%s', 'amapress' ),
						$dates_factors, count( $dates ), $rattrapage_renvoi, $distrib_dates_end_text ) . '</p>';
			}
		}

		if ( $show_distrib_dates ) {
			echo '<ul style="list-style-type: disc; padding-top: 0; margin-top: 0">';
			foreach ( $grouped_dates_array as $entry ) {
				echo '<li style="margin-left: 35px">' . esc_html( $entry ) . '</li>';
			}
			echo '</ul>';

			$reports = [];
			foreach ( $dates as $d ) {
				$real_date = $contrat->getRealDateForDistribution( $d );
				if ( Amapress::start_of_day( $real_date ) != Amapress::start_of_day( $d ) ) {
					$reports[] = sprintf( __( 'livraison du %s reportée au %s', 'amapress' ), date_i18n( 'd/m/Y', $d ), date_i18n( 'd/m/Y', $real_date ) );
				}
			}
			if ( ! empty( $reports ) ) {
				echo '<p>' . __( 'Report(s) de livraison : ', 'amapress' ) . implode( ', ', $reports ) . '</p>';
			}

			if ( ! empty( $rattrapage ) ) {
				echo '<p><a id="dist_rattrapages">*</a>' . __( 'Distribution(s) de rattrapage : ', 'amapress' ) . implode( ', ', $rattrapage ) . '</p>';
			}
		}

		if ( $contrat->isQuantiteMultiple() || $contrat->isPanierVariable() ) {
			echo '<p>' . __( 'Composez votre panier :', 'amapress' ) . '</p>';
		} else {
			echo '<p>' . __( 'Choisissez la quantité ou la taille de votre panier :', 'amapress' ) . '</p>';
		}
		$multiple_rules = [];
		$quants_full    = [];
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		if ( $contrat->isPanierVariable() ) {
			$columns = array(
				array(
					'title' => __( 'Produit', 'amapress' ),
					'data'  => 'produit',
				),
				array(
					'title' => __( 'Prix', 'amapress' ),
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
				if ( $contrat->isFull( $quant->ID ) ) {
					if ( ! $edit_inscription || ! in_array( $quant->ID, $edit_inscription->getContrat_quantites_IDs() ) ) {
						continue;
					}
				}

				$multiple       = $quant->getGroupMultiple();
				$grp_class_name = '';
				$quant_fix      = $contrat->isPanierFix() ? 'quant-fix' : '';
				$quant_cmd      = $contrat->isCommandeVariable() ? 'quant-cmd' : '';
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
				$row        = array(
					'produit'       => '<span class="panier-mod-produit-label">' . esc_html( $quant->getTitle() ) . ( ! empty( $quant->getDescription() ) ? '<br/><em>' . esc_html( $quant->getDescription() ) . '</em>' : '' ) . '</span>',
					'prix_unitaire' => esc_html( $quant->getPrix_unitaireDisplay() ),
				);
				$price_unit = esc_attr( $quant->getPrix_unitaire() );
				foreach ( $dates as $date ) {
					$options = $quant->getQuantiteOptions( $contrat->getRemainingQuantiteForMax( $quant->ID, $lieu_id, $date ) );
					$ed      = '';
					$ed      .= "<select style='max-width: none;min-width: 0' data-grp-class='$grp_class_name' data-price='0' data-price-unit='$price_unit' name='panier_vars[$date][{$quant->ID}]' id='panier_vars-$date-{$quant->ID}' class='quant-var $quant_fix $quant_cmd $grp_class_name'>";
					$ed      .= tf_parse_select_options( $options,
						$edit_inscription
							? $edit_inscription->getContrat_quantite_factor( $quant->ID, $date )
							: null,
						false );
					$ed      .= '</select>';
					$ed      .= '<a title="' . esc_attr__( 'Recopier la même quantité sur les dates suivantes', 'amapress' ) . '" href="#" class="quant-var-recopier">&gt;</a>';
					if ( ! $quant->isInDistributionDates( $date ) ) {
						$ed = '<span class="contrat_panier_vars-na">' . __( 'NA', 'amapress' ) . '</span>';
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
                return "' . esc_js( sprintf( __( 'La quantité pour %s doit être multiple de %s. Actuellement: ', 'amapress' ), $grp_conf['display'], $grp_conf['multiple'] ) ) . '" + $(element).data("mlcnt") + "<br/>";
            }
        );
    });
    //]]>
</script>';
			}

			echo '<style type="text/css">.DTFC_LeftBodyLiner{overflow-x: hidden;}.quant-var-recopier{text-shadow: none !important; text-decoration: none !important;}.panier-mod-produit-label{display: inline-block;white-space: normal;word-wrap: break-word; max-width: ' . $atts['max_produit_label_width'] . ';}</style>';

			$paniers_modulables_editor_height = $atts['paniers_modulables_editor_height'];
			$js_options                       = array(
				'bSort'          => false,
				'paging'         => false,
				'searching'      => true,
				'bAutoWidth'     => true,
				'responsive'     => false,
				'init_as_html'   => true,
				'scrollCollapse' => true,
				'scrollX'        => true,
				'scrollY'        => is_numeric( $paniers_modulables_editor_height ) ?
					$paniers_modulables_editor_height . 'px' : $paniers_modulables_editor_height,
				'fixedColumns'   => array( 'leftColumns' => 2 ),
			);
			if ( $has_groups ) {
				$js_options['raw_js_options'] = 'rowGroup: {
                    dataSrc: function ( row ) {
                        var grp = row[0].match(/\[([^\]]+)\]/);
                        if (grp && grp.length > 1)
                            return grp[1];
                        return "' . esc_js( __( 'Autres', 'amapress' ) ) . '";
                    }
                }';
				//Datatables rowGroup does not support fixedColumns, so for now, disable it
				unset( $js_options['fixedColumns'] );
			}

			echo amapress_get_datatable( 'quant-commandes', $columns, $data, $js_options );
			echo '<p>* Cliquez sur la case pour faire apparaître le choix de quantités et utilisez les 2 barres de défilement à droite et en bas pour voir toutes les dates et tous les produits.</p>';
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
					$options          = $quantite->getQuantiteOptions( $contrat->getRemainingQuantiteForMax( $quantite->ID, $lieu_id ) );
					$quant_var_editor .= "<select  style='max-width: none;min-width: 0' id='$id_factor' class='quant-factor' data-quant-id='$id_quant' data-price-id='$id_price' data-price-unit='$price' name='factors[{$quantite->ID}]' style='display: inline-block'>";
					$quant_var_editor .= tf_parse_select_options(
						$options,
						$edit_inscription
							? $edit_inscription->getContrat_quantite_factor( $quantite->ID )
							: ( isset( array_keys( $options )[1] ) ? $options[ array_keys( $options )[1] ] : null ),
						false );
					$quant_var_editor .= '</select>';
				}

				$checked = checked( $edit_inscription && $edit_inscription->getContrat_quantite_factor( $quantite->ID ) > 0, true, false );
				$type    = $contrat->isQuantiteMultiple() ? 'checkbox' : 'radio';
				$desc    = '';
				if ( ! empty( $quantite->getDescription() ) ) {
					$desc = '<br/><em>' . esc_html( $quantite->getDescription() ) . '</em>';
				}
				echo '<p style="margin-top: 1em; margin-bottom: 0"><label for="' . $id_quant . '">
			<input id="' . $id_quant . '" name="quants[]" ' . $checked . ' class="quant" value="' . $quantite->ID . '" type="' . $type . '" data-factor-id="' . $id_factor . '" data-price="' . $price . '" data-pricew="' . ( abs( $quantite->getPrix_unitaire() ) < 0.001 ? 1 : 0 ) . '"/> 
			' . $quant_var_editor . ' ' . esc_html( $quantite->getTitle() ) . ' ' . $price_compute_text . ( abs( $quantite->getPrix_unitaire() ) > 0.001 ? ' = <span id="' . $id_price . '">' . $price . '</span>€</label>' . $desc . '</p>' : '' );

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
						$grouped_dates_array[] = $k . ' : ' . ( count( $v ) > 1 ? __( 'les ', 'amapress' ) : __( 'le ', 'amapress' ) ) . implode( ', ', array_map(
								function ( $d ) {
									return date_i18n( 'd', $d );
								}, $v
							) );
					}
					echo '<p style="font-style: italic; font-size: 0.8em; padding-left: 15pt; padding-top: 0; margin-top: 0">' . __( 'Dates spécifiques : ', 'amapress' ) . implode( ' ; ', $grouped_dates_array ) . '</p>';
				}
			}
		}
		if ( $contrat->hasProducteur_Message() ) {
			echo '<label for="inscr_msg" style="display: block">' . __( 'Message de commande au producteur :', 'amapress' ) . '</label><textarea  style="display: block" id="inscr_msg" name="inscr_msg">' . ( $edit_inscription ? esc_textarea( $edit_inscription->getProducteurMessage() ) : '' ) . '</textarea>';
			echo $contrat->getProducteur_Message_Instructions();
		}
		if ( $contrat->getDon_Distribution() ) {
			echo '<p><hr/>
<label for="don-contrat-' . $contrat->ID . '">' . esc_html( $contrat->getDon_DistributionLabel() ) . __( ' : ', 'amapress' ) . '</label>
<input style="display: inline-block; max-width: 5em; font-size: 1em" class="don-input" step="0.01" value="' . ( $edit_inscription ? $edit_inscription->getDon_Distribution() : 0 ) . '" type="number" id="don-contrat-' . $contrat->ID . '" name="don_dist" min="0" data-dists="' . count( $dates ) . '" /> ' . __( '€', 'amapress' ) .
			     ' x ' . sprintf( __( '%d distribution(s)', 'amapress' ), count( $dates ) ) . ' = <span id="don-dist-total"></span>' . __( '€', 'amapress' ) .
			     '<br/> ' . $contrat->getDon_DistributionDescription() .
			     '<hr/></p>';
		}
		echo '<p style="margin-top: 1em;">' . __( 'Total: ', 'amapress' ) . '<span id="total">0</span>€</p>';
		echo '<p><input type="submit" class="btn btn-default btn-assist-inscr btn-assist-inscr-validate" value="' . esc_attr__( 'Valider', 'amapress' ) . '" /></p>';
		echo '</form>';

		if ( $admin_mode && ! empty( $quants_full ) ) {
			echo '<p>' . sprintf( __( 'Les paniers "%s" sont <strong>COMPLETS</strong> et n\'apparaissent donc pas ci-dessus. Pour augmenter les quotas, %s', 'amapress' ), implode( ',', $quants_full ), Amapress::makeLink( $contrat->getAdminEditLink(), sprintf( __( 'éditez le contrat %s', 'amapress' ), $contrat->getTitle() ), true, true ) ) . '</p>';
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

		$amapien = AmapressUser::getBy( $user_id );

		$pay_at_deliv = [];
		$step_name    = wp_unslash( Amapress::getOption( 'online_subscription_pay_step_name' ) );
		echo '<h4>' . amapress_step_text( $step, $steps_nums, $steps_count ) . $step_name . '</h4>';
		echo wp_unslash( amapress_replace_mail_placeholders(
			Amapress::getOption( 'online_subscription_pay_step_message' ),
			$amapien, $contrat ) );

		$dates = $contrat->getRemainingDates( $start_date );

		$by_month_totals = [];
		if ( $contrat->isPanierVariable() ) {
			$panier_vars = isset( $_REQUEST['panier_vars'] ) ? (array) $_REQUEST['panier_vars'] : []; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( empty( $panier_vars ) ) {
				wp_die( $invalid_access_message ); //phpcs:ignore
			}

			$columns         = [];
			$columns['date'] = array(
				'title' => __( 'Date', 'amapress' ),
				'data'  => array(
					'_'    => 'date',
					'sort' => 'date_sort',
				)
			);
			$data            = [];

			$total         = 0;
			$chosen_quants = [];
			$chosen_totals = [];
			foreach ( $panier_vars as $date_k => $quant_factors ) {
				$date_values = [];
				$row         = [
					'date'      => date_i18n( 'd/m/Y', $date_k ),
					'date_sort' => date_i18n( 'Y-m-d', $date_k ),
				];
				$date_total  = 0;
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
					$date_total    += $factor * $quant->getPrix_unitaire();
					if ( abs( $quant->getPrix_unitaire() ) < 0.001 ) {
						$pay_at_deliv[] = $quant->getTitle();
					} else {
						$month = date_i18n( 'M-y', $date_k );
						if ( empty( $by_month_totals[ $month ] ) ) {
							$by_month_totals[ $month ] = 0;
						}
						$by_month_totals[ $month ] += $factor * $quant->getPrix_unitaire();
					}
					$data[] = $row;
				}
				if ( ! empty( $date_values ) ) {
					$chosen_quants[ $date_k ] = $date_values;
					$chosen_totals[ $date_k ] = $date_total;
				} else {
					unset( $panier_vars[ $date_k ] );
				}
			}
			$serial_quants = $panier_vars;

			if ( ! $contrat->getDon_Distribution_Apart() ) {
				$don_dist = 0;
				if ( isset( $_REQUEST['don_dist'] ) ) {
					$don_dist = floatval( $_REQUEST['don_dist'] );
				}
				$total += $don_dist * count( $dates );
			}

			if ( ! $admin_mode ) {
				if ( ! $use_contrat_term ) {
					echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous allez passer commande de %s pour un montant %s avec les options suivantes:', 'amapress' ), esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				} else {
					echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous allez vous inscrire au contrat %s pour un montant %s avec les options suivantes:', 'amapress' ), esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				}
			} else {
				echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous allez inscrire %s au contrat %s pour un montant %s avec les options suivantes:', 'amapress' ), esc_html( $amapien->getDisplayName() ), esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
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
					echo '<li style="margin-left: 35px">' .
					     esc_html( date_i18n( 'd/m/Y', intval( $dt ) ) ) .
					     esc_html( sprintf( ' (total : %0.2f€)', $chosen_totals[ $dt ] ) );
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
						$month = date_i18n( 'M-y', $date_k );
						if ( empty( $by_month_totals[ $month ] ) ) {
							$by_month_totals[ $month ] = 0;
						}
						$by_month_totals[ $month ] += $contrat->getDateFactor( $d, $q_id ) * $factor * $quant->getPrix_unitaire();
					}
				}
			}

			if ( ! $contrat->getDon_Distribution_Apart() ) {
				$don_dist = 0;
				if ( isset( $_REQUEST['don_dist'] ) ) {
					$don_dist = floatval( $_REQUEST['don_dist'] );
				}
				$total += $don_dist * count( $dates );
			}

			if ( count( $chosen_quants ) == 1 && ! $admin_mode ) {
				if ( ! $use_contrat_term ) {
					echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous avez choisi l\'option “%s” de la commande %s pour un montant %s', 'amapress' ), $chosen_quants[0], esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				} else {
					echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous avez choisi l\'option “%s” du contrat %s pour un montant %s', 'amapress' ), $chosen_quants[0], esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				}
			} else {
				if ( ! $admin_mode ) {
					if ( ! $use_contrat_term ) {
						echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous avez choisi les options suivantes de la commande %s pour un montant %s :=', 'amapress' ), esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
					} else {
						echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous avez choisi les options suivantes du contrat %s pour un montant %s :=', 'amapress' ), esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
					}
				} else {
					$amapien = AmapressUser::getBy( $user_id );
					echo '<p style="margin-bottom: 0">' . sprintf( __( 'Vous allez inscrire %s au contrat %s pour un montant %s avec les options suivantes:', 'amapress' ), esc_html( $amapien->getDisplayName() ), esc_html( $contrat->getTitle() ), $total > 0 ? 'de ' . Amapress::formatPrice( $total, true ) : 'payable à la livraison' ) . '</p>';
				}
				echo '<ul style="list-style-type: disc">';
				foreach ( $chosen_quants as $q ) {
					echo '<li style="margin-left: 35px">' . $q . '</li>';
				}
				echo '</ul>';
			}
		}

		/** @var AmapressContrat_instance $contrat */
		if ( ! $contrat->isCommandeVariable() && $contrat->getManage_Cheques() ) {
			if ( ! $admin_mode ) {
				echo '<p style="margin-bottom: 0">' . __( 'Propositions de règlement :', 'amapress' ) . '</p>';
			} else {
				echo '<p style="margin-bottom: 0">' . __( 'Propositions de règlement :', 'amapress' ) . '</p>';
			}
		}
		$serial_quants = esc_attr( serialize( $serial_quants ) );
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		echo "<input type='hidden' name='quants' value='$serial_quants'/>";
		if ( isset( $_REQUEST['don_dist'] ) ) {
			echo '<input type="hidden" name="don_dist" value="' . esc_attr( $_REQUEST['don_dist'] ) . '"/>';
		}
		if ( isset( $_REQUEST['inscr_msg'] ) ) {
			echo '<input type="hidden" name="inscr_msg" value="' . esc_attr( sanitize_textarea_field( $_REQUEST['inscr_msg'] ) ) . '"/>';
		}
		if ( ! $contrat->isCommandeVariable() && $contrat->getManage_Cheques() ) {
			$remaining_cheques_dates = $contrat->getPaiements_Liste_dates();
			if ( ! $admin_mode ) {
				$remaining_cheques_dates = array_filter( $remaining_cheques_dates,
					function ( $d ) {
						return $d > Amapress::start_of_week( amapress_time() );
					} );
			}

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
							$chq_label = sprintf( __( '1 chèque de %0.2f €', 'amapress' ), $total );
						} else {
							$cheques   = implode( '|', array_map( function ( $month_amount ) {
								return Amapress::formatPrice( $month_amount, true );
							}, $by_month_totals ) );
							$chq_label = implode( ' ; ', array_map( function ( $month, $month_amount ) {
								return sprintf( __( '%s: 1 chèque de %0.2f €', 'amapress' ),
									$month,
									$month_amount );
							}, array_keys( $by_month_totals ), array_values( $by_month_totals ) ) );
						}
						echo "<label for='cheques-$nb_cheque' style='font-weight: normal'><input type='radio' $checked name='cheques' id='cheques-$nb_cheque' data-cheques-details='$cheques' value='$nb_cheque' class='input-nb-cheques required' />$chq_label</label><br/>";
					} elseif ( $contrat->hasCustomMultiplePaiements() ) {
						$amounts   = $contrat->getTotalAmountByCustom( $nb_cheque, $total );
						$cheques   = implode( '|', array_map( function ( $amount ) {
							return Amapress::formatPrice( $amount, true );
						}, $amounts ) );
						$chq_label = implode( ' ; ', array_map( function ( $amount ) {
							return sprintf( __( '1 chèque de %0.2f €', 'amapress' ),
								$amount );
						}, $amounts ) );

						$cheques_dates = $contrat->getDatesPaiementsByCustom( $nb_cheque );
						if ( empty( $cheques_dates ) ) {
							$cheques_dates = array_merge( $remaining_cheques_dates );
						}

						$cheques_dates_display = array_map( function ( $d ) {
							return date_i18n( 'd/m/Y', $d );
						}, $cheques_dates );

						$cheques_dates         = implode( '|', $cheques_dates );
						$cheques_dates_display = implode( '|', $cheques_dates_display );

						echo "<label for='cheques-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='cheques-$nb_cheque' data-cheques-details='$cheques' data-cheques-dates='$cheques_dates' data-cheques-dates-display='$cheques_dates_display'  value='$nb_cheque' class='input-nb-cheques required' />$chq_label</label><br/>";
					} else {
						$cheques            = $contrat->getChequeOptionsForTotal( $nb_cheque, $total );
						$option             = esc_html( $cheques['desc'] );
						$cheque_main_amount = esc_attr( Amapress::formatPrice( $cheques['main_amount'] ) );
						$last_cheque        = esc_attr( Amapress::formatPrice( ! empty( $cheques['remain_amount'] ) ? $cheques['remain_amount'] : $cheques['main_amount'] ) );
						$chq_label          = '';
						if ( $cheque_main_amount != $last_cheque ) {
							$chq_label = sprintf( __( '%s chèque(s) : ', 'amapress' ), $nb_cheque );
						}

						$cheques_dates_display = array_map( function ( $d ) {
							return date_i18n( 'd/m/Y', $d );
						}, $remaining_cheques_dates );

						$cheques_dates         = implode( '|', $remaining_cheques_dates );
						$cheques_dates_display = implode( '|', $cheques_dates_display );

						echo "<label for='cheques-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='cheques-$nb_cheque' data-cheques-dates='$cheques_dates' data-cheques-dates-display='$cheques_dates_display' data-main-amount='$cheque_main_amount €' data-last-amount='$last_cheque €' value='$nb_cheque' class='input-nb-cheques required' />$chq_label$option</label><br/>";
					}
				}
			} else {
				echo '<p><strong>' . __( 'Paiement à la livraison', 'amapress' ) . '</strong></p>';
			}
			if ( $contrat->getAllow_Delivery_Pay() || abs( $total ) < 0.001 ) {
				$checked = checked( $edit_inscription && 'dlv' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-dlv' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-dlv' $checked value='-3' class='input-nb-cheques required' />" . __( 'Paiement à la livraison', 'amapress' ) . "</label><br/>";
			}
			if ( $contrat->getAllow_Cash() ) {
				$checked = checked( $edit_inscription && 'esp' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-esp' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-esp' $checked value='-1' class='input-nb-cheques required' />" . __( 'En espèces', 'amapress' ) . "</label><br/>";
			}
			if ( $total > 0 && $contrat->getAllow_Stripe() ) {
				$min_stripe_amount = $contrat->getStripeMinAmount();
				if ( $total > $min_stripe_amount ) {
					$checked = checked( $edit_inscription && 'stp' == $edit_inscription->getMainPaiementType(), true, false );
					echo "<label for='cheques-stp' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-stp' $checked value='-5' class='input-nb-cheques required' />" . __( 'Paiement en ligne', 'amapress' ) . "</label><br/>";
				}
			}
			if ( $contrat->getAllow_Transfer() ) {
				$checked = checked( $edit_inscription && 'vir' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-vir' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-vir' $checked value='-2' class='input-nb-cheques required' />" . __( 'Par virement', 'amapress' ) . "</label><br/>";
			}
			if ( $contrat->getAllow_LocalMoney() ) {
				$checked = checked( $edit_inscription && 'mon' == $edit_inscription->getMainPaiementType(), true, false );
				echo "<label for='cheques-mon' style='font-weight: normal'><input type='radio' name='cheques' id='cheques-mon' $checked value='-4' class='input-nb-cheques required' />" . __( 'En monnaie locale', 'amapress' ) . "</label><br/>";
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
								$chq_label = sprintf( __( '1 prélèvement de %0.2f €', 'amapress' ), $total );
							} else {
								$cheques   = implode( '|', array_map( function ( $month_amount ) {
									return Amapress::formatPrice( $month_amount, true );
								}, $by_month_totals ) );
								$chq_label = implode( ' ; ', array_map( function ( $month, $month_amount ) {
									return sprintf( __( '%s: 1 prélèvement de %0.2f €', 'amapress' ),
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
								return sprintf( __( '1 prélèvement de %0.2f €', 'amapress' ),
									$amount );
							}, $amounts ) );

							$cheques_dates = $contrat->getDatesPaiementsByCustom( $nb_cheque );
							if ( empty( $cheques_dates ) ) {
								$cheques_dates = array_merge( $remaining_cheques_dates );
							}

							$cheques_dates_display = array_map( function ( $d ) {
								return date_i18n( 'd/m/Y', $d );
							}, $cheques_dates );

							$cheques_dates         = implode( '|', $cheques_dates );
							$cheques_dates_display = implode( '|', $cheques_dates_display );

							echo "<label for='prlv-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='prlv-$nb_cheque' value='$nb_cheque_val' data-cheques-details='$cheques' data-cheques-dates='$cheques_dates' data-cheques-dates-display='$cheques_dates_display' class='input-nb-cheques required' />$chq_label</label><br/>";
						} else {
							$cheques            = $contrat->getChequeOptionsForTotal( $nb_cheque, $total,
								__( 'prélèvement', 'amapress' ), __( 'prélèvements', 'amapress' ) );
							$option             = esc_html( $cheques['desc'] );
							$cheque_main_amount = esc_attr( Amapress::formatPrice( $cheques['main_amount'] ) );
							$last_cheque        = esc_attr( Amapress::formatPrice( ! empty( $cheques['remain_amount'] ) ? $cheques['remain_amount'] : $cheques['main_amount'] ) );
							$chq_label          = '';
							if ( $cheque_main_amount != $last_cheque ) {
								$chq_label = sprintf( __( '%s prélèvement(s) : ', 'amapress' ), $nb_cheque );
							}

							$cheques_dates_display = array_map( function ( $d ) {
								return date_i18n( 'd/m/Y', $d );
							}, $remaining_cheques_dates );

							$cheques_dates         = implode( '|', $remaining_cheques_dates );
							$cheques_dates_display = implode( '|', $cheques_dates_display );

							echo "<label for='prlv-$nb_cheque' style='font-weight: normal'><input type='radio' '.$checked.' name='cheques' id='prlv-$nb_cheque' value='$nb_cheque_val' data-cheques-dates='$cheques_dates' data-cheques-dates-display='$cheques_dates_display' class='input-nb-cheques required' />$chq_label$option</label><br/>";
						}
					}
				}
			}
			if ( ( $admin_mode || $contrat->getAllowAmapienInputPaiementsDetails() ) && $total > 0 ) {
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
        var cheques_dates = $(this).data("cheques-dates");
        if (cheques_dates) cheques_dates = cheques_dates.toString();
        var cheques_dates_display = $(this).data("cheques-dates-display")?.toString();
        if (cheques_dates_display) cheques_dates_display = cheques_dates_display.toString();
        if (cheques_dates) {
            cheques_dates = cheques_dates.split("|");
            cheques_dates_display = cheques_dates_display.split("|");
            var i = 0;
            $("#cheques-details tr").each(function() {
               //skip header
               if (i > 0  && cheques_dates.length > i - 1 && cheques_dates_display.length > i - 1)
                    $(".amps-pmt-date-display", this).text(cheques_dates_display[i - 1]);
                    $(".amps-pmt-date", this).val(cheques_dates[i - 1]);
               i++;
            }); 
        }
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
               if (i > 0 && cheques_details.length > i - 1)
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
<th>' . __( 'Date paiement', 'amapress' ) . '</th>
<th>' . esc_html( wp_unslash( Amapress::getOption( 'online_subscription_pay_num_label' ) ) ) . '</th>
<th>' . __( 'Banque', 'amapress' ) . '</th>
<th>' . __( 'Emetteur', 'amapress' ) . '</th>
<th>' . __( 'Montant', 'amapress' ) . '</th>
</thead><tbody>';
				Amapress::setFilterForReferent( false );
				$edit_all_paiements = $edit_inscription ? $edit_inscription->getAllPaiements() : null;
				Amapress::setFilterForReferent( true );
				$req_num                    = ( $paiements_numero_required ? 'required' : '' );
				$req                        = ( $paiements_info_required ? 'required' : '' );
				$pay_by_month_months        = [];
				$pay_by_month_encaiss_dates = [];
				if ( $contrat->getPayByMonth() ) {
					$pay_dates = $contrat->getPaiements_Liste_dates();
					sort( $pay_dates );
					foreach ( $by_month_totals as $month => $month_amount ) {
						$pay_by_month_months[] = $month;
						$found                 = false;
						foreach ( $pay_dates as $pay_date ) {
							if ( date_i18n( 'M-y', $pay_date ) == $month ) {
								$pay_by_month_encaiss_dates[] = $pay_date;
								$found                        = true;
								break;
							}
						}
						if ( ! $found ) {
							$pay_by_month_encaiss_dates[] = $pay_dates[0];
						}
					}
				}
				for ( $i = 1; $i <= 12; $i ++ ) {
					$edit_paiement       = $edit_all_paiements && isset( $edit_all_paiements[ $i - 1 ] ) ? $edit_all_paiements[ $i - 1 ] : null;
					$paiements_raw_dates = $contrat->getPaiements_Liste_dates();
					if ( ! $admin_mode ) {
						$paiements_raw_dates = array_filter( $paiements_raw_dates,
							function ( $d ) {
								return $d > Amapress::start_of_week( amapress_time() );
							} );
					}
					if ( $contrat->getPayByMonth() ) {
						$paiements_raw_dates = array_merge( $pay_by_month_encaiss_dates );
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
					$select            = "<select id='pmt-$i-date' name='pmt[$i][date]' class='$req amps-pmt-date'>
$paiements_dates
</select>";
					if ( $contrat->getPayByMonth() ) {
						if ( isset( $pay_by_month_encaiss_dates[ $i - 1 ] ) ) {
							$dt     = $pay_by_month_encaiss_dates[ $i - 1 ];
							$select = "<input type='hidden' name='pmt[$i][date]' value='$dt' />" .
							          date_i18n( 'd/m/Y', $dt );
						}
						if ( isset( $pay_by_month_months[ $i - 1 ] ) ) {
							$select .= '<br/>pour ' . $pay_by_month_months[ $i - 1 ];
						}
					} elseif ( ! $admin_mode && ! $contrat->getAllowAmapienInputPaiementsDates() ) {
						if ( $edit_paiement ) {
							$dt     = $edit_paiement->getDate();
							$select = "<input type='hidden' class='amps-pmt-date' name='pmt[$i][date]' value='$dt' /><span class='amps-pmt-date-display'>" .
							          date_i18n( 'd/m/Y', $dt ) . '</span>';
						} elseif ( isset( $paiements_raw_dates[ $i - 1 ] ) ) {
							$dt     = $paiements_raw_dates[ $i - 1 ];
							$select = "<input type='hidden' class='amps-pmt-date' name='pmt[$i][date]' value='$dt' /><span class='amps-pmt-date-display'>" .
							          date_i18n( 'd/m/Y', $dt ) . '</span>';
						} else {
							$select = '';
						}
					}
					echo "<tr style='display: none'>
<td>$select</td>
<td><input type='text' id='pmt-$i-num' name='pmt[$i][num]' class='$req_num' value='$paiement_num' /></td>
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
		echo '<p>' . __( 'Information pour le réglement :', 'amapress' ) . '<br/>';
		echo __( 'Ordre: ', 'amapress' ) . wp_unslash( $contrat->getPaiementsOrdre() );
		echo '<br />' . wp_unslash( $contrat->getPaiementsMention() );
		echo '</p>';

		$don_dist = 0;
		if ( isset( $_REQUEST['don_dist'] ) ) {
			$don_dist = floatval( $_REQUEST['don_dist'] );
		}
		if ( $don_dist > 0 ) {
			if ( $contrat->getDon_Distribution_Apart() ) {
				echo '<p><strong>' . sprintf( 'Règlement à part pour "%s" d\'un montant de %s',
						$contrat->getDon_DistributionLabel(),
						Amapress::formatPrice( $don_dist * count( $dates ), true )
					) . '</strong></p>';
			}
		}

		echo '<br />';
		if ( ! empty( $pay_at_deliv ) ) {
			echo '<p><strong>' . __( 'Produits payables à la livraison', 'amapress' ) . '</strong> : ' . esc_html( implode( ', ', $pay_at_deliv ) ) . '</p>';
			echo '<br />';
		}


		if ( ! $admin_mode ) {
			echo '<label for="inscr_message"  style="display: block">' . __( 'Message pour le référent :', 'amapress' ) . '</label><textarea  style="display: block" id="inscr_message" name="message">' . ( $edit_inscription ? esc_textarea( $edit_inscription->getMessage() ) : '' ) . '</textarea>';
		} else {
			echo '<p><input type="checkbox" checked="checked" id="inscr_confirm_mail" name="inscr_confirm_mail" /><label for="inscr_confirm_mail"> ' . __( 'Confirmer par email à l\'adhérent', 'amapress' ) . '</label></p>';
		}
		echo '<input type="submit" value="' . esc_attr__( 'Valider', 'amapress' ) . '" class="btn btn-default btn-assist-inscr btn-assist-inscr-validate" />';
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

		if ( ! $contrat->isCommandeVariable() && $contrat->getManage_Cheques() && empty( $_REQUEST['cheques'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$cheques = ! isset( $_REQUEST['cheques'] ) ? 0 : intval( $_REQUEST['cheques'] );
		if ( empty( $_REQUEST['quants'] ) ) {
			wp_die( $invalid_access_message ); //phpcs:ignore
		}
		$quants = unserialize( stripslashes( $_REQUEST['quants'] ) ); //phpcs:ignore
		if ( ! $contrat->isCommandeVariable() && empty( $quants ) ) {
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
			foreach ( $quants as $q_dt => $q_p ) {
				foreach ( $q_p as $q_id => $q_v ) {
					$f        = floatval( $q_v );
					$any_full = $any_full || $contrat->isFull( $q_id, $lieu_id, $q_dt );
					if ( ! $any_full ) {
						$remaining_quants = $contrat->getRemainingQuantiteForMax( $q_id, $lieu_id, $q_dt );
						if ( $remaining_quants >= 0 ) {
							if ( $f > $remaining_quants ) {
								$any_full = true;
							}
						}
					}
				}
			}
		} else {
			foreach ( $quants as $q ) {
				$q_id           = intval( $q['q'] );
				$any_full       = $any_full || $contrat->isFull( $q_id );
				$quantite_ids[] = $q_id;
				$f              = floatval( $q['f'] );
				if ( abs( $f - 1.0 ) > 0.001 ) {
					$quantite_factors[ strval( $q_id ) ] = $f;
				}
				if ( ! $any_full ) {
					$remaining_quants = $contrat->getRemainingQuantiteForMax( $q_id, $lieu_id, null );
					if ( $remaining_quants >= 0 ) {
						if ( $f > $remaining_quants ) {
							$any_full = true;
						}
					}
				}
			}
		}


		if ( ! $edit_inscription && $any_full ) {
			if ( $admin_mode ) {
				$contrat_edit_link      = Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle(), true, true );
				$contrats_step_url_attr = esc_attr( $contrats_step_url );
				$mailto_refs            = esc_attr( "mailto:$referents_mails" );
				wp_die( sprintf( __( '<p>Désolé, ce contrat/commande ou l\'un des paniers que vous avez choisi est complet<br/>
<a href=\'%s\'>Retour aux contrats/commandes</a><br/>
Pour augmenter les quota du contrat ou de ses paniers, cliquez sur le lien suivant : %s<br/>
LE cas écheant, une fois les quota mis à jour, appuyer sur F5 pour terminer l\'inscription en cours.
</p>', 'amapress' ), $contrats_step_url_attr, $contrat_edit_link ) );
			} else {
				$contrats_step_url_attr = esc_attr( $contrats_step_url );
				$mailto_refs            = esc_attr( "mailto:$referents_mails" );
				wp_die( sprintf( __( '<p>Désolé, ce contrat/commande ou l\'un des paniers que vous avez choisi est complet<br/>
<a href=\'%s\'>Retour aux contrats/commandes</a><br/>
<a href=\'%s\'>Contacter les référents</a>
</p>', 'amapress' ), $contrats_step_url_attr, $mailto_refs ) );
			}
		}

		/** @var AmapressContrat_instance $contrat */
		$meta = [
			'amapress_adhesion_adherent'         => $user_id,
			'amapress_adhesion_status'           => 'to_confirm',
			'amapress_adhesion_date_debut'       => $contrat->isCommandeVariable() && $edit_inscription ? $edit_inscription->getDate_debut() : $start_date,
			'amapress_adhesion_contrat_instance' => $contrat_id,
			'amapress_adhesion_message'          => $message,
			'amapress_adhesion_paiements'        => ( $cheques < 0 ? 1 : ( $cheques > 0 ? ( $cheques >= 100 ? $cheques - 100 : $cheques ) : 0 ) ),
			'amapress_adhesion_lieu'             => $lieu_id,
		];
		if ( isset( $_REQUEST['don_dist'] ) ) {
			$meta['amapress_adhesion_don_dist'] = floatval( $_REQUEST['don_dist'] );
		}
		if ( isset( $_REQUEST['inscr_msg'] ) ) {
			$meta['amapress_adhesion_prod_msg'] = $_REQUEST['inscr_msg'];
		}
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
		if ( ! empty( $quantite_variables ) || $contrat->isCommandeVariable() ) {
			/** @var AmapressAdhesion $edit_inscription */
			if ( $edit_inscription && $edit_inscription->getContrat_instance()->isCommandeVariable() ) {
				$rem_dates = $edit_inscription->getContrat_instance()->getRemainingDates( $start_date );
				foreach ( $edit_inscription->getPaniersVariables() as $k => $v ) {
					if ( in_array( $k, $rem_dates ) ) {
						continue;
					}
					if ( ! isset( $quantite_variables[ $k ] ) ) {
						$quantite_variables[ $k ] = $v;
					}
				}
				ksort( $quantite_variables );
			}
			$meta['amapress_adhesion_panier_variables'] = $quantite_variables;
		}
		if ( $contrat->getMaxContratMonths() > 0 ) {
			$end_date                           = Amapress::add_a_month( $start_date, $contrat->getMaxContratMonths() );
			$meta['amapress_adhesion_date_fin'] = $end_date;
			$meta['amapress_adhesion_pmt_fin']  = 1;
		}
		$my_post = array(
			'post_title'   => __( 'Inscription', 'amapress' ),
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
		if ( $edit_inscription && ( $contrat->isCommandeVariable() || ( $cheques > 0 && $cheques < 100 ) ) ) {
			delete_post_meta( $new_id, 'amapress_adhesion_pmt_type' );
		}

		Amapress::setFilterForReferent( false );
		$inscription = AmapressAdhesion::getBy( $new_id, true );
		Amapress::setFilterForReferent( true );
		if ( ! $contrat->isCommandeVariable() && $inscription->getContrat_instance()->getManage_Cheques() ) {
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
			echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_contrats_end_confirm_msg' ),
				$inscription->getAdherent(), $inscription ) );
			if ( Amapress::toBool( $atts['send_contrat_confirm'] ) ) {
				echo wp_unslash( amapress_replace_mail_placeholders( Amapress::getOption( 'online_contrats_end_confirm_mail_msg' ),
					$inscription->getAdherent(), $inscription ) );
			}
			$print_contrat = '';
			if ( ! empty( $inscription->getContrat_instance()->getContratModelDocFileName() ) ) {
				$print_contrat = Amapress::makeButtonLink(
					add_query_arg( [
						'inscr_assistant' => 'generate_contrat',
						'inscr_id'        => $inscription->ID,
						'inscr_key'       => amapress_sha_secret( $key )
					] ),
					$contrat_print_button_text, true, true, 'btn btn-default btn-assist-inscr btn-assist-inscr-print'
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
<input type="submit" value="' . esc_attr__( 'Payer en ligne et valider l\'inscription', 'amapress' ) . '" class="btn btn-danger btn-assist-inscr btn-assist-inscr-stripe" />
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
<input type="submit" value="' . esc_attr__( 'Modifier', 'amapress' ) . '" class="btn btn-default btn-assist-inscr btn-assist-inscr-edit" />
</form>';
				if ( strpos( $online_contrats_end_step_edit_message, '%%modify_button%%' ) !== false ) {
					$online_contrats_end_step_edit_message = str_replace( '%%modify_button%%', $modify_button, $online_contrats_end_step_edit_message );
				} else {
					echo '<br/>' . $modify_button;
				}

				if ( ! $inscription->getContrat_instance()->isCommandeVariable() ) {
					$cancel_button = '<form method="get" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="details" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $inscription->ID . '" />
<input type="hidden" name="cancel_inscr_id" value="' . $inscription->ID . '" />
<input type="submit" value="' . esc_attr__( 'Annuler', 'amapress' ) . '" class="btn btn-default btn-assist-inscr btn-assist-inscr-cancel" />
</form>';
					if ( strpos( $online_contrats_end_step_edit_message, '%%cancel_button%%' ) !== false ) {
						$online_contrats_end_step_edit_message = str_replace( '%%cancel_button%%', $cancel_button, $online_contrats_end_step_edit_message );
					} else {
						echo '<br/>' . $cancel_button;
					}
				} else {
					$online_contrats_end_step_edit_message = str_replace( '%%cancel_button%%', __( 'Les commandes variables sont seulement modifiables', 'amapress' ), $online_contrats_end_step_edit_message );
				}
			} else {
				$online_contrats_end_step_edit_message = '';
			}
			echo amapress_replace_mail_placeholders( $online_contrats_end_step_edit_message, $amapien, $inscription );
			echo amapress_replace_mail_placeholders( $online_contrats_end_step_message, $amapien, $inscription );

			if ( $is_mes_contrats ) {
				if ( ! $use_contrat_term ) {
					echo '<p>' . __( 'Retourner à la liste de mes commandes :', 'amapress' ) . '<br/>';
				} else {
					echo '<p>' . __( 'Retourner à la liste de mes contrats :', 'amapress' ) . '<br/>';
				}
				echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr btn-assist-inscr-add-new" type="submit" value="' . esc_attr__( 'Ajouter de nouveaux contrats', 'amapress' ) . '" />
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


					echo amapress_replace_mail_placeholders( $online_contrats_end_continue_msg,
						$inscription->getAdherent() );
					//
					echo '<br />';
					echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr btn-assist-inscr-add-new" type="submit" value="' . esc_attr__( 'Ajouter de nouveaux contrats', 'amapress' ) . '" />
</form></p>';
				} else {
					if ( ! $use_contrat_term ) {
						echo '<p>' . __( 'Vous avez déjà passé toutes les commandes disponibles.', 'amapress' ) . '</p>';
						echo '<p>' . __( 'Retourner à la liste de mes commandes :', 'amapress' ) . '<br/>';
					} else {
						echo '<p>' . __( 'Vous êtes déjà inscrit à tous les contrats.', 'amapress' ) . '</p>';
						echo '<p>' . __( 'Retourner à la liste de mes contrats :', 'amapress' ) . '<br/>';
					}
					echo '<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr btn-assist-inscr-add-new" type="submit" value="' . esc_attr__( 'Ajouter de nouveaux contrats', 'amapress' ) . '" />
</form></p>';
				}
			}

			if ( ! $is_mes_contrats && ! $adhesion_intermittent ) {
				$online_contrats_inscription_distrib_msg = wp_unslash( Amapress::getOption( 'online_contrats_inscription_distrib_msg' ) );
				if ( ! empty( $online_contrats_inscription_distrib_msg ) ) {
					$dist_inscriptions                       = AmapressDistributions::getResponsableDistribForCurrentAdhesions( $user_id, null, $min_contrat_date );
					$online_contrats_inscription_distrib_msg = str_replace( '%%nb_inscriptions%%', count( $dist_inscriptions ), $online_contrats_inscription_distrib_msg );
					$online_contrats_inscription_distrib_msg = str_replace( '%%dates_inscriptions%%',
						empty( $dist_inscriptions ) ? __( 'aucune', 'amapress' ) : implode( ', ', array_map(
							function ( $d ) {
								/** @var AmapressDistribution $d */
								return date_i18n( 'd/m/Y', $d->getDate() );
							}, $dist_inscriptions
						) ), $online_contrats_inscription_distrib_msg );
					echo amapress_replace_mail_placeholders( $online_contrats_inscription_distrib_msg, $inscription->getAdherent() );
				}
			}

			if ( ! $admin_mode && ! $is_mes_contrats ) {
				echo '<p>' . __( 'J\'ai fini :', 'amapress' ) . '<br/>
<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="the_end" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr btn-assist-inscr-end" type="submit" value="' . esc_attr__( 'Terminer', 'amapress' ) . '" />
</form></p>';
			}
		} else {
			echo '<div class="alert alert-success">' . __( 'L\'inscription a bien été prise en compte : ', 'amapress' ) . Amapress::makeLink( $inscription->getAdminEditLink(), 'Editer l\'inscription', true, true ) . '</div>';
			echo '<p><a href="' . esc_attr( $contrats_step_url ) . '" >' . __( 'Retourner à la liste de ses contrats/commandes', 'amapress' ) . '</a></p>';
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
                var quant_var = jQuery(this).find(".quant-var");
                if ('visible' !== quant_var.css("visibility")) {
                    quant_var.prop('selectedIndex', 1).trigger('change');
                    computeTotal();
                }

                jQuery(this).find(".quant-var, .quant-var-recopier")
                    .css('visibility', 'visible');
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

            jQuery.validator.addMethod("single_name", function (value, element) {
                return !(/[;\/\\,]/.test(jQuery(element).val()));
            }, "<?php echo esc_js( __( 'Merci de ne saisir qu\'un seul nom ou prénom. Utilisez les champs de coadhérents pour vos coadhérents.', 'amapress' ) ); ?>");

            jQuery.validator.addMethod(
                "max_no_panier",
                function (value, element, params) {
                    if (params < 0)
                        return true;
                    var noPaniers = 0;
                    var paniers = 0;
                    var parent = $(element).closest("tr");
                    jQuery(parent).find(".quant-var").each(function () {
                        var quant = parseFloat(jQuery(this).val());
                        if (quant > 0.01)
                            paniers += 1;
                        else
                            noPaniers += 1;
                    });
                    if (paniers > 0 && noPaniers > params) return false;
                    return true;
                },
                "<?php echo esc_js( __( 'Seule(s) {0} distribution(s) sont/est autorisée(s) à ne pas avoir de livraison', 'amapress' ) ); ?>"
            );

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
                "<?php echo esc_js( __( 'Le montant total doit être supérieur à {0}€', 'amapress' ) ); ?>"
            );

            jQuery.validator.addMethod(
                "quant_fix",
                function (value, element, params) {
                    var values = new Map();
                    var parent = $(element).closest("tr");
                    jQuery(parent).find(".quant-fix").each(function () {
                        var quant = parseFloat(jQuery(this).val());
                        if (quant > 0)
                            values.set(quant, 1);
                    });
                    if (values.size === 1) return true;
                    return false;
                },
                "<?php echo esc_js( __( 'La quantité doit être soit 0, soit la même à toutes les distributions', 'amapress' ) ); ?>"
            );

            jQuery('.don-input').change(function () {
                computeTotal();
            });

            function computeTotal() {
                var total = 0;
                jQuery('.quant:checked,.quant-var').each(function () {
                    total += parseFloat(jQuery(this).data('price'));
                });
                jQuery('.don-input').each(function () {
                    var $this = jQuery(this);
                    var don = parseFloat($this.val()) * parseFloat($this.data('dists'));
                    jQuery('#don-dist-total').text(don.toFixed(2));
					<?php if ($contrat && ! $contrat->getDon_Distribution_Apart()) { ?>
                    total += don;
					<?php } ?>
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
                    priceElt.text('<?php echo esc_js( __( 'Prix au poids', 'amapress' ) ); ?>');
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
                    $(this).val(val).trigger("change");
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
                    max_no_panier: <?php echo $max_no_panier; ?>,
                };
                if ($this.data('grp-class')) {
                    opt[$this.data('grp-class')] = true;
                }
                if (!$this.is('.quant-cmd'))
                    $this.rules('add', opt);
            });
            jQuery('.quant-fix').each(function () {
                var $this = jQuery(this);
                var opt = {
                    quant_fix: true,
                };
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
		'title' => __( 'Producteur', 'amapress' ),
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
				$row[ 'date_' . $date ] = '<span class="amps-bk-center">X</span>';
			} else {
				$row[ 'date_' . $date ] = '';
			}
		}
		$data[] = $row;
	}

	$ret .= amapress_get_datatable( 'calend_delivs', $columns, $data,
		array(
			'paging'       => false,
			'searching'    => false,
			'responsive'   => false,
			'scrollX'      => true,
			'scrollY'      => '300px',
			'fixedHeader'  => true,
			'fixedColumns' => [ 'leftColumns' => 1 ],
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
	$for_mail = false,
	$from_contrat_start = false
) {
	if ( empty( $contrats_ids ) ) {
		$contrats_ids = [];
	} elseif ( ! is_array( $contrats_ids ) ) {
		$contrats_ids = [ $contrats_ids ];
	} else {
		$contrats_ids = array_values( $contrats_ids );
	}
	$date = null;
	if ( $from_contrat_start ) {
		foreach ( $contrats_ids as $contrat_id ) {
			$contrat = AmapressContrat_instance::getBy( $contrat_id );
			if ( $contrat ) {
				if ( $date < $contrat->getDate_debut() ) {
					$date = $contrat->getDate_debut();
				}
			}
		}
	}

	Amapress::setFilterForReferent( false );
	$adhs = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, $date, $ignore_renouv_delta, true );
	Amapress::setFilterForReferent( true );

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
			'title' => __( 'Date', 'amapress' ),
			'data'  => array(
				'_'    => 'date_d',
				'sort' => 'date',
			)
		);
		if ( $contrat_instance->hasGroups() ) {
			$has_groups = true;
			$columns[]  = array(
				'title' => __( 'Groupe', 'amapress' ),
				'data'  => array(
					'_'    => 'group',
					'sort' => 'group',
				)
			);
		}
	} elseif ( $by_prod ) {
		$columns[] = array(
			'title' => __( 'Producteur', 'amapress' ),
			'data'  => array(
				'_'    => 'prod',
				'sort' => 'prod',
			)
		);
		$columns[] = array(
			'title' => __( 'Date', 'amapress' ),
			'data'  => array(
				'_'    => 'date_d',
				'sort' => 'date',
			)
		);
	} else {
		$columns[] = array(
			'title' => __( 'Date', 'amapress' ),
			'data'  => array(
				'_'    => 'date_d',
				'sort' => 'date',
			)
		);
		$columns[] = array(
			'title' => __( 'Producteur', 'amapress' ),
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
		'title' => __( 'Description', 'amapress' ),
		'data'  => array(
			'_'    => 'desc',
			'sort' => 'desc',
		)
	);
	$columns[] = array(
		'title' => __( 'Quantité', 'amapress' ),
		'data'  => array(
			'_'    => 'fact',
			'sort' => 'fact',
		)
	);
	$columns[] = array(
		'title' => __( 'Total', 'amapress' ),
		'data'  => array(
			'_'    => 'total_d',
			'sort' => 'total',
		)
	);

	$ix   = 1;
	$data = [];
	foreach ( $adhs as $adh ) {
		$don_dist = $adh->getDon_Distribution();
		if ( $adh->getContrat_instance()->isPanierVariable() ) {
			$paniers = $adh->getPaniersVariables();
			foreach ( $adh->getRemainingDates() as $date ) {
				foreach ( AmapressContrats::get_contrat_quantites( $adh->getContrat_instanceId() ) as $quant ) {
					if ( ! empty( $paniers[ $date ][ $quant->ID ] ) ) {
						$row           = [];
						$row['ix']     = $ix;
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
					$ix ++;
				}

				if ( $don_dist > 0 ) {
					$row           = [];
					$row['ix']     = $ix;
					$row['date_d'] = date_i18n( 'd/m/Y', $date );
					$row['date']   = $date;
					$row['desc']   = '<em>' . $adh->getContrat_instance()->getDon_DistributionLabel() . '</em>';

					$row['prod']    = $adh->getContrat_instance()->getModel()->getTitle()
					                  . '<br />'
					                  . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
					$row['fact']    = 1;
					$row['total_d'] = Amapress::formatPrice( $don_dist, true );
					$row['total']   = $don_dist;
					$data[]         = $row;
					$ix ++;
				}
			}

		} else {
			foreach ( $adh->getRemainingDates() as $date ) {
				foreach ( $adh->getContrat_quantites( $date ) as $quant ) {
					$row           = [];
					$row['ix']     = $ix;
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
					$ix ++;
				}

				if ( $don_dist > 0 ) {
					$row           = [];
					$row['ix']     = $ix;
					$row['date_d'] = date_i18n( 'd/m/Y', $date );
					$row['date']   = $date;
					$row['desc']   = '<em>' . $adh->getContrat_instance()->getDon_DistributionLabel() . '</em>';

					$row['prod']    = $adh->getContrat_instance()->getModel()->getTitle()
					                  . '<br />'
					                  . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
					$row['fact']    = 1;
					$row['total_d'] = Amapress::formatPrice( $don_dist, true );
					$row['total']   = $don_dist;
					$data[]         = $row;
					$ix ++;
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
					if ( 'desc' == $k || 'date' == $k || 'date_d' == $k || 'prod' == $k || 'group' == $k || 'ix' == $k ) {
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
			$cmp = strcmp( $a['prod'], $b['prod'] );
			if ( 0 == $cmp ) {
				if ( $a['date'] == $b['date'] ) {
					$cmp = $a['ix'] > $b['ix'] ? 1 : - 1;
				} else {
					$cmp = $a['date'] > $b['date'] ? 1 : - 1;
				}
			}

			return $cmp;
		} );
	} else {
		usort( $data, function ( $a, $b ) {
			if ( $a['date'] == $b['date'] ) {
				$cmp = strcmp( $a['prod'], $b['prod'] );
				if ( 0 == $cmp ) {
					$cmp = $a['ix'] > $b['ix'] ? 1 : - 1;
				}

				return $cmp;
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
			'paging'         => false,
			'searching'      => false,
			'raw_js_options' => 'rowGroup: {
                endRender: null,
                startRender: function ( rows, group ) {
                    var fn = jQuery.fn.dataTable.ext.oApi._fnGetObjectDataFn( "total" );
                    var total = 0.0;
                    var indexes = rows.indexes();
                    for ( var i=0, ien=indexes.length ; i < ien ; i++ ) {
                        total += parseFloat(fn( this.s.dt.row( indexes[i] ).data() ) );
                    }
                    total = total.toFixed(2) + "' . __( '€', 'amapress' ) . '";
     
                    var colspan = this.s.dt.columns().visible().reduce( function (a, b) {
                        return a + b;
                    }, 0 );
     
                    return $("<tr/>")
                        .append( "<td colspan=\'" + (colspan-1) + "\'>"+group+"</td>" )
                        .append( "<td>" + total + "</td>" );
                },
                dataSrc: ' . ( $by_prod ? '"prod"' : '"date_d"' ) . '
        }',
			'rowsGroup'      => [
				0,
				1
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
		'title' => __( 'Producteur', 'amapress' ),
		'data'  => array(
			'_'    => 'prod',
			'sort' => 'prod_sort',
		)
	);
	$columns[]   = array(
		'title' => __( 'Total', 'amapress' ),
		'data'  => array(
			'_'    => 'total_d',
			'sort' => 'total',
		)
	);
	$columns[]   = array(
		'title' => __( 'Option paiements', 'amapress' ),
		'data'  => array(
			'_'    => 'opt_pmts',
			'sort' => 'opt_pmts',
		)
	);
	$columns[]   = array(
		'title' => __( 'Info', 'amapress' ),
		'data'  => array(
			'_'    => 'info',
			'sort' => 'info',
		)
	);
	if ( $show_dates_encaissement ) {
		$columns[] = array(
			'title' => __( 'Dates paiement', 'amapress' ),
			'data'  => array(
				'_'    => 'date_enc',
				'sort' => 'date_enc',
			)
		);
	}
	if ( $show_dates_livraison ) {
		$columns[] = array(
			'title' => __( 'Dates livraison', 'amapress' ),
			'data'  => array(
				'_'    => 'date_liv',
				'sort' => 'date_liv',
			)
		);
	}
	$columns[] = array(
		'title' => __( 'Statut', 'amapress' ),
		'data'  => array(
			'_'    => 'status',
			'sort' => 'status',
		)
	);

	$data = [];
	foreach ( $adhs as $adh ) {
		$paiements        = $adh->getAllPaiements();
		$paiements_status = [];
		$sum_paiement     = 0;
		foreach ( $paiements as $paiement ) {
			$paiements_status[] = sprintf( __( '%s %s (<span style="color: %s">%s</span>)', 'amapress' ),
				$paiement->getTypeFormatted(),
				Amapress::formatPrice( $paiement->getAmount(), true ),
				'not_received' == $paiement->getStatus() ? 'orange' : 'green',
				$paiement->getStatusDisplay()
			);
			$sum_paiement       += $paiement->getAmount();
		}
		$row              = [];
		$row['prod']      = date_i18n( 'd/m/Y', $adh->getContrat_instance()->getDate_debut() ) .
		                    ' - ' . $adh->getContrat_instance()->getModel()->getTitle()
		                    . '<br />'
		                    . '<em>' . $adh->getContrat_instance()->getModel()->getProducteur()->getTitle() . '</em>';
		$row['prod_sort'] = date_i18n( 'Y-m-d', $adh->getContrat_instance()->getDate_debut() ) .
		                    $adh->getContrat_instance()->getModel()->getTitle();
		$row['opt_pmts']  = $adh->getProperty( 'option_paiements' );
		if ( $adh->getDon_Distribution() > 0 ) {
			$row['opt_pmts'] .= '<br/><em>' . sprintf(
					( $adh->getContrat_instance()->getDon_Distribution_Apart() ?
						__( '(réglement à part pour %s : %0.2f €)', 'amapress' ) : __( '(inclus %s : %0.2f €)', 'amapress' ) ),
					$adh->getContrat_instance()->getDon_DistributionLabel(),
					$adh->getTotalDon() ) . '</em>';
		}
		$info            = 'Ordre: ' . $adh->getProperty( 'paiements_ordre' );
		$info            .= ! empty( $adh->getProperty( 'paiements_mention' ) ) ? '<br/>' . $adh->getProperty( 'paiements_mention' ) : '';
		$row['info']     = $info;
		$row['date_enc'] = implode( ', ', array_map( function ( $d ) {
			return date_i18n( 'd/m/Y', $d );
		}, $adh->getContrat_instance()->getPaiements_Liste_dates() ) );
		$row['date_liv'] = implode( ', ', array_map( function ( $d ) {
			return date_i18n( 'd/m/Y', $d );
		}, array_filter( $adh->getContrat_instance()->getListe_dates(), function ( $d ) {
			return Amapress::start_of_day( $d ) >= Amapress::start_of_day( amapress_time() );
		} ) ) );
		$row['total_d']  = Amapress::formatPrice( $adh->getTotalAmount(), true );
		$row['total']    = $adh->getTotalAmount();
		$row['status']   = implode( ' ; ', $paiements_status )
		                   . '<br/>' . __( 'Total = ', 'amapress' )
		                   . Amapress::formatPrice( $sum_paiement, true );
		$data[]          = $row;
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
