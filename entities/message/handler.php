<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_query_for_message( $users_query, $users_query_fields ) {

}

function amapress_get_users_for_message( $users_query, $users_query_fields, $with_coadherents = false ) {
	$users = array();
	if ( $users_query == 'no_adhesion' ) {
		$users = get_users(
			array(
				'amapress_contrat' => 'no'
			)
		);
	} else if ( $users_query == 'never_logged' ) {
		$users = get_users(
			array(
				'meta_query' => array(
					array(
						'key'     => 'last_login',
						'compare' => "NOT EXISTS"
					)
				)
			)
		);
	} else if ( strpos( $users_query, 'user:' ) === 0 ) {
		$users_query = substr( $users_query, 5 );
		if ( 'include=' === $users_query || 'include=0' === $users_query ) {
			$users = [];
		} elseif ( $users_query == 'me' ) {
			$users = array( amapress_get_user_by_id_or_archived( amapress_current_user_id() ) );
		} else {
			$query = new WP_User_Query( $users_query );
			$users = $query->get_results();
		}
	} else {
		$query = new WP_Query();
		$query->parse_query( $users_query . '&posts_per_page=-1&post_status=publish' );
		foreach ( $query->get_posts() as $post ) {
			foreach ( $users_query_fields as $query_field ) {
				$o = Amapress::get_post_meta_array( $post->ID, $query_field );
				if ( is_array( $o ) ) {
					$users = array_merge( $users, $o );
				} else if ( ! in_array( $o, $users ) ) {
					$users[] = $o;
				}
			}
		}
	}
	$all_users = array();
	foreach ( $users as $user_id ) {
		if ( $user_id ) {
			if ( is_a( $user_id, "WP_User" ) ) {
				$all_users[ $user_id->ID ] = $user_id;
			} else {
				$all_users[ $user_id ] = amapress_get_user_by_id_or_archived( $user_id );
			}
		}
	}

	if ( $with_coadherents ) {
		foreach ( $all_users as $user_id => $user ) {
			foreach ( AmapressContrats::get_related_users( $user_id ) as $related_user_id ) {
				if ( ! isset( $all_users[ $related_user_id ] ) ) {
					$all_users[ $related_user_id ] = amapress_get_user_by_id_or_archived( $related_user_id );
				}
			}
		}
	}

	return $all_users;
}

function amapress_send_message_and_record(
	$subject, $content, $content_sms, $opt, TitanEntity $entity = null,
	$attachments = array(), $cc = null, $bcc = null, $headers = array()
) {
	$opt['record'] = true;
	Amapress::setFilterForReferent( false );
	amapress_send_message( $subject, $content, $content_sms, $opt, $entity, $attachments, $cc, $bcc, $headers );
	Amapress::setFilterForReferent( true );
}

function amapress_send_message(
	$subject, $content, $content_sms, $opt, TitanEntity $entity = null,
	$attachments = array(), $cc = null, $bcc = null, $headers = array()
) {
	$subject     = wp_unslash( $subject );
	$content     = wp_unslash( $content );
	$content_sms = wp_unslash( $content_sms );

	if ( is_string( $headers ) ) {
		$headers = [ $headers ];
	}

	$new_id = null;
	/** @var AmapressUser $current_user */
	$current_user = AmapressUser::getBy( amapress_current_user_id() );
	$is_indiv     = ( isset( $opt['send_mode'] ) && $opt['send_mode'] == 'indiv' );
	$record       = ( isset( $opt['record'] ) && $opt['record'] );
	if ( ! $is_indiv && $record ) {
		$my_post = array(
			'post_title'   => amapress_replace_mail_placeholders( $subject, $current_user, $entity ),
			'post_type'    => 'amps_message',
			'post_content' => amapress_replace_mail_placeholders( $content, $current_user, $entity ),
			'post_status'  => 'publish',
			'meta_input'   => array(
				'amapress_message_target_name'     => $opt['target_name'],
				'amapress_message_query_string'    => wp_json_encode( $opt ),
				'amapress_message_content_for_sms' => $content_sms,
				'amapress_message_target_type'     => $opt['target_type']
			),
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id ) {
			return;
		}
	}

	if ( ! $is_indiv && $record && ! empty( $opt['post_query'] ) ) {
		$query = new WP_Query( $opt['post_query'] );
		foreach ( $query->get_posts() as $post ) {
			$pt       = amapress_simplify_post_type( $post->post_type );
			$messages = Amapress::get_post_meta_array( $post->ID, "amapress_{$pt}_messages" );
			if ( ! $messages ) {
				$messages = array();
			}
			if ( ! in_array( $new_id, $messages ) ) {
				$messages[] = $new_id;
			}
			update_post_meta( $post->ID, "amapress_{$pt}_messages", $messages );

			if ( isset( $opt['post_date_query_field'] ) ) {
				update_post_meta( $new_id, 'amapress_message_associated_date', get_post_meta( $post->ID, $opt['post_date_query_field'], true ) );
			}
		}
	}
	if ( ! empty( $opt['users_query'] ) ) {
		$all_users = amapress_get_users_for_message( $opt['users_query'], $opt['users_query_fields'], $opt['with_coadherents'] );

		$emails       = array();
		$user_ids     = array();
		$emails_indiv = array();
		foreach ( $all_users as $user ) {
			if ( ! $is_indiv && $record ) {
				$messages = Amapress::get_user_meta_array( $user->ID, "amapress_user_messages" );
				if ( ! $messages ) {
					$messages = array();
				}
				if ( ! in_array( $new_id, $messages ) ) {
					$messages[] = $new_id;
				}
				update_user_meta( $user->ID, "amapress_user_messages", $messages );
			}

			if ( $user ) {
				if ( ! empty( $user->user_email ) ) {
					$user_ids[] = $user->ID;
					$amapien    = AmapressUser::getBy( $user );
					foreach ( $amapien->getAllEmails() as $email ) {
						$emails[]               = $email;
						$emails_indiv[ $email ] = $amapien;
					}
				}
			}
		}

		if ( ! $is_indiv && $new_id ) {
			update_post_meta( $new_id, 'amapress_message_user_ids', $user_ids );
		}

		$from_email = amapress_mail_from( null );
		$from_dn    = amapress_mail_from_name( null );

		if ( empty( $from_email ) ) {
			// Get the site domain and get rid of www.
			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}
			$from_email = 'wordpress@' . $sitename;
		}

		$reply_to_mail = '';
		if ( ! empty( $opt['send_from_me'] ) && $current_user ) {
			$from_dn    = $current_user->getDisplayName();
			$user_email = $current_user->getUser()->user_email;
			if ( AmapressMailingGroup::hasRestrictiveDMARC( $user_email ) ) {
				$headers[]     = 'X-Original-From: ' . $user_email;
				$reply_to_mail = $user_email;
			} else {
				$from_email = $user_email;
			}

			$set_from      = function ( $old ) use ( $from_email ) {
				return $from_email;
			};
			$set_from_name = function ( $old ) use ( $from_dn ) {
				return $from_dn;
			};
			add_filter( 'wp_mail_from', $set_from );
			add_filter( 'wp_mail_from_name', $set_from_name );
		}

		if ( empty( $reply_to_mail ) ) {
			$reply_to_mail = $from_email;
		}

		$from_dn = trim( str_replace( array( '"', "'", "\r", "\n" ), '', $from_dn ) );
		$from_dn = '"' . $from_dn . '"';

		$reply_to_headers = array_filter( $headers,
			function ( $h ) {
				return 0 === stripos( $h, 'Reply-To' );
			} );
		$headers[]        = 'Content-Type: text/html; charset=UTF-8';
		switch ( isset( $opt['send_mode'] ) ? $opt['send_mode'] : '' ) {
			case "indiv":
				$headers[] = "From: \"$from_dn\" <$from_email>";
				if ( empty( $reply_to_headers ) ) {
					$headers[] = "Reply-To: \"$from_dn\" <$reply_to_mail>";
				}
				/** @var AmapressUser $name */
				foreach ( $emails_indiv as $email => $name ) {
					$name_string = $name->getDisplayName();
					$name_string = trim( str_replace( array( '"', "'", "\r", "\n" ), '', $name_string ) );
					$name_string = '"' . $name_string . '"';
					$to          = "$name_string <$email>";
					$new_subject = amapress_replace_mail_placeholders( $subject, $name, $entity );
					$new_content = amapress_replace_mail_placeholders( $content, $name, $entity );
					amapress_wp_mail( $to, $new_subject, $new_content, $headers, $attachments, $cc, $bcc );
				}
				break;
			case 'cc':
			case "to":
				$to        = implode( ',', $emails );
				$headers[] = "From: \"$from_dn\" <$from_email>";
				if ( empty( $reply_to_headers ) ) {
					$headers[] = "Reply-To: \"$from_dn\" <$reply_to_mail>";
				}
				if ( $current_user ) {
					$headers[] = 'Cc: ' . $current_user->getUser()->user_email;
				}
				$subject = amapress_replace_mail_placeholders( $subject, $current_user, $entity );
				$content = amapress_replace_mail_placeholders( $content, $current_user, $entity );
				amapress_wp_mail( $to, $subject, $content, $headers, $attachments, $cc, $bcc );
				break;
//			case "cc":
//				$to        = '$from_dn <$from_email>';
//				if ( $current_user ) {
//					$to = $current_user->getEmail();
//				}
//				$headers[] = "From: $from_dn <$from_email>";
//				$headers[] = "Reply-to: $from_dn <$from_email>";
//				$headers[] = __('Cc: ', 'amapress') . implode( ',', $emails );
//				$subject   = amapress_replace_mail_placeholders( $subject, $current_user, $entity );
//				$content   = amapress_replace_mail_placeholders( $content, $current_user, $entity );
//				amapress_wp_mail( $to, $subject, $content, $headers, $attachments, $cc, $bcc );
//				break;
			case "bcc":
			default:
			$to = "\"{$opt['target_name']}\" <$from_email>";
			if ( $current_user ) {
				$emails[] = $current_user->getEmail();
			}
			$headers[] = "From: \"$from_dn\" <$from_email>";
			if ( empty( $reply_to_headers ) ) {
				$headers[] = "Reply-To: \"$from_dn\" <$reply_to_mail>";
			}
			$headers[] = 'Bcc: ' . implode( ',', $emails );
			$subject   = amapress_replace_mail_placeholders( $subject, $current_user, $entity );
			$content   = amapress_replace_mail_placeholders( $content, $current_user, $entity );
			amapress_wp_mail( $to, $subject, $content, $headers, $attachments, $cc, $bcc );
		}

		if ( isset( $opt['send_from_me'] ) && $opt['send_from_me'] ) {
			remove_filter( 'wp_mail_from', $set_from );
			remove_filter( 'wp_mail_from_name', $set_from_name );
		}
//        remove_filter( 'wp_mail_content_type', 'amapress_wpmail_content_type' );
	}
}


add_action( 'tf_custom_admin_amapress_action_send_message', 'amapress_handle_send_message' );
function amapress_handle_send_message() {
	if ( ! amapress_is_user_logged_in()
	     || ! amapress_current_user_can( 'publish_message' ) ) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}


	$opt                 = json_decode( wp_unslash( $_REQUEST['amapress_msg_target'] ), true );
	$opt['send_mode']    = $_REQUEST['amapress_send_mode'];
	$opt['send_from_me'] = isset( $_REQUEST['amapress_send_from'] ) && 'user' == $_REQUEST['amapress_send_from'];

//    $target_name = $opt['target_name'];
//    $target_type = $opt['target_type'];
//    unset($opt['target_name']);
//    unset($opt['target_type']);

	$subject     = $_REQUEST['amapress_msg_subject'];
	$content     = $_REQUEST['amapress_msg_content'];
	$content_sms = isset( $_REQUEST['amapress_msg_content_for_sms'] ) ? $_REQUEST['amapress_msg_content_for_sms'] : '';

	$amapien   = AmapressUser::getBy( amapress_current_user_id() );
	$headers   = [];
	$headers[] = 'Reply-To: ' . implode( ',', $amapien->getAllEmails() );

	amapress_send_message_and_record( $subject, $content, $content_sms, $opt, null, [], null, null, $headers );
//    'target_name' => array(
//            'target_type' => array(
//            'target_ids' => array(
//            'content_for_sms' => array(
//            'sms_sent' => array(
//    ),
	wp_redirect_and_exit( admin_url( 'edit.php?post_type=amps_message&order=post_date&orderby=DESC&message=mail_sent' ) );
}

function amapress_prepare_message_target_to( $query_string, $title, $target_type, $with_coadherents = false ) {
	$ret              = amapress_prepare_message_target( $query_string, $title, $target_type, $with_coadherents );
	$ret['send_mode'] = 'to';

	return $ret;
}

function amapress_prepare_message_target_bcc( $query_string, $title, $target_type, $with_coadherents = false ) {
	$ret              = amapress_prepare_message_target( $query_string, $title, $target_type, $with_coadherents );
	$ret['send_mode'] = 'bcc';

	return $ret;
}

function amapress_get_collectif_target_queries() {
	$ret = array();

	$ret["amapress_role=referent_producteur"] = __( 'Référents producteurs', 'amapress' );
	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
		$ret["amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = sprintf( __( 'Référents producteurs - %s', 'amapress' ), $contrat->getTitle() );
	}

	$ret["amapress_role=referent_lieu"]     = __( 'Référents lieux', 'amapress' );
	$ret["amapress_role=collectif_no_prod"] = __( 'Membres du collectif (sans les producteurs)', 'amapress' );
	$ret["amapress_role=collectif"]         = __( 'Membres du collectif (avec les producteurs)', 'amapress' );
	$ret["role=administrator"]              = __( 'Administrateurs', 'amapress' );
	$ret["role=tresorier"]                  = __( 'Trésoriers', 'amapress' );

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAP_ROLE,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ 'amps_amap_role_category=' . $role->slug ] = sprintf( __( 'Rôle "%s"', 'amapress' ), $role->name );
	}

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAPIEN_GROUP,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ AmapressUser::AMAPIEN_GROUP . '=' . $role->slug ] = sprintf( __( 'Groupe amapiens "%s"', 'amapress' ), $role->name );
	}

	return amapress_user_queries_link_wrap( $ret );
}


function amapress_prepare_message_target( $query_string, $title, $target_type, $with_coadherents = false ) {
	$opt                = array();
	$s                  = explode( '|', $query_string );
	$opt['users_query'] = $s[0];
	$opt['target_type'] = $target_type;
	if ( isset( $s[1] ) ) {
		$opt['users_query_fields'] = explode( ',', $s[1] );
	} else {
		$opt['users_query_fields'] = array();
	}
	if ( isset( $s[2] ) ) {
		$opt['post_query'] = $s[2];
	}
	if ( isset( $s[3] ) ) {
		$opt['post_date_query_field'] = $s[3];
	}
	$opt['target_name']      = str_replace( ',', ';', $title );
	$opt['with_coadherents'] = $with_coadherents;

	return $opt;
}


function amapress_add_message_target( &$arr, $query_string, $title, $target_type ) {
	$opt                           = amapress_prepare_message_target( $query_string, $title, $target_type );
	$members                       = amapress_get_users_for_message( $opt['users_query'], $opt['users_query_fields'] );
	$count                         = count( $members );
	$opt['members']                = implode( ', ', array_map(
		function ( $u ) {
			/** @var WP_User $u */
			return Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $u->ID ), sprintf( __( '%s (%s)', 'amapress' ), $u->display_name, $u->user_email ), true, true );
		}, $members
	) );
	$arr[ wp_json_encode( $opt ) ] = sprintf( _n( '%s (%d destinataires)', '%s (%d destinataires)', $count, 'amapress' ),
		$title,
		$count );
}

function amapress_message_get_targets() {
	amapress_precache_all_users();

	Amapress::setFilterForReferent( false );

	$ret = array();
	amapress_add_message_target( $ret, "user:me", __( 'Moi - Test', 'amapress' ), 'me' );
	$res[ __( 'Test', 'amapress' ) ] = $ret;

	$ret = array();
	amapress_add_message_target( $ret, "post_type=amps_producteur|amapress_producteur_user", __( "Les producteurs", 'amapress' ), 'producteur' );
	amapress_add_message_target( $ret, "user:amapress_role=collectif", __( "Les membres du collectif AMAP", 'amapress' ), 'collectif' );
	amapress_add_message_target( $ret, "user:amapress_role=referent_producteur", __( "Les referents producteurs", 'amapress' ), "referent-producteur" );
	amapress_add_message_target( $ret, "post_type=amps_lieu|amapress_lieu_distribution_referent", __( "Les referents lieux de distribution", 'amapress' ), "referent-lieu" );

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAP_ROLE,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		amapress_add_message_target( $ret, "user:amapress_role=amap_role_{$role->slug}", sprintf( __( 'Rôle "%s"', 'amapress' ), $role->name ), "referents" );
	}

	$res[ __( 'Responsables', 'amapress' ) ] = $ret;

	$ret = array();
	//amapiens incrits lieu
	foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
		$lieu = get_post( $lieu_id );
		amapress_add_message_target( $ret, "post_type=amps_adhesion&amapress_lieu=$lieu_id&amapress_date=active|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4", sprintf( __( 'Les amapiens de %s', 'amapress' ), $lieu->post_title ), "lieu lieu-{$lieu->ID}" );
		//contrats
		foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
			$lieux = Amapress::get_post_meta_array( $contrat->ID, 'amapress_contrat_instance_lieux' );
			if ( ! in_array( $lieu_id, $lieux ) ) {
				continue;
			}
			$contrat_id = $contrat->ID;
			amapress_add_message_target( $ret, "post_type=amps_adhesion&amapress_lieu=$lieu_id&amapress_contrat_inst=$contrat_id&amapress_date=active|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4", sprintf( __( "Les amapiens \"%s\" de %s", 'amapress' ), $contrat->getTitle(), $lieu->post_title ), "lieu lieu-{$lieu->ID} contrat contrat-{$contrat->ID}" );
		}
	}

	//amapiens contrats
	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
		$contrat_id = $contrat->ID;
		amapress_add_message_target( $ret, "post_type=amps_adhesion&amapress_contrat_inst=$contrat_id&amapress_date=active|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4", sprintf( __( 'Les amapiens "%s"', 'amapress' ), $contrat->getTitle() ), "contrat contrat-{$contrat->ID}" );
	}
	$res[ __( 'Lieux et contrats', 'amapress' ) ] = $ret;

	$ret = array();
	//amapiens distributions
	$cnt   = array();
	$query = new WP_Query( 'post_type=amps_distribution&amapress_date=active&meta_key=amapress_distribution_date&orderby=meta_key&order=ASC' );
	foreach ( $query->get_posts() as $distrib ) {
		$dist_id = $distrib->ID;
		$lieu_id = intval( get_post_meta( $dist_id, 'amapress_distribution_lieu', true ) );
		if ( ! isset( $cnt[ $lieu_id ] ) ) {
			$cnt[ $lieu_id ] = 5;
		}
		if ( $cnt[ $lieu_id ] == 0 ) {
			continue;
		}

		$contrat_ids = Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_contrats' );
		$contrat_ids = implode( ',', $contrat_ids );

		amapress_add_message_target( $ret, "post_type=amps_adhesion&amapress_contrat_inst=$contrat_ids&amapress_date=active|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4|amapress_post=$dist_id|amapress_distribution_date", sprintf( __( "Les amapiens inscrit à %s", 'amapress' ), $distrib->post_title ), "distribution" );

		$cnt[ $lieu_id ] -= 1;
	}
	$res[ __( 'Distributions', 'amapress' ) ] = $ret;

	$ret = array();
	//responsables disributions
	$cnt   = array();
	$query = new WP_Query( 'post_type=amps_distribution&amapress_date=active&meta_key=amapress_distribution_date&orderby=meta_key&order=ASC' );
	foreach ( $query->get_posts() as $distrib ) {
		$dist_id = $distrib->ID;
		$lieu_id = intval( get_post_meta( $dist_id, 'amapress_distribution_lieu', true ) );
		if ( ! isset( $cnt[ $lieu_id ] ) ) {
			$cnt[ $lieu_id ] = 5;
		}
		if ( $cnt[ $lieu_id ] == 0 ) {
			continue;
		}

		amapress_add_message_target( $ret, "amapress_post=$dist_id|amapress_distribution_responsables|amapress_post=$dist_id|amapress_distribution_date", sprintf( __( "Les responsables de distribution inscrit à %s", 'amapress' ), $distrib->post_title ), "resp-distribution" );

		$cnt[ $lieu_id ] -= 1;
	}
	$res[ __( 'Responsables de distributions', 'amapress' ) ] = $ret;

	$ret = array();
	//visite à la ferme
	$cnt = 5;
	foreach ( AmapressVisite::get_next_visites() as $visite ) {
		if ( $cnt == 0 ) {
			continue;
		}

		amapress_add_message_target( $ret, "amapress_post={$visite->ID}|amapress_visite_participants|amapress_post={$visite->ID}|amapress_visite_date", sprintf( __( "Les inscrits à %s", 'amapress' ), $visite->getTitle() ), "visite" );

		$cnt -= 1;
	}
	$res[ __( 'Visites à la ferme', 'amapress' ) ] = $ret;

	$ret = array();
	//ag
	$cnt = 5;
	foreach ( AmapressAssemblee_generale::get_next_assemblees() as $ag ) {
		if ( $cnt == 0 ) {
			continue;
		}

		amapress_add_message_target( $ret, "amapress_post={$ag->ID}|amapress_assemblee_generale_participants|amapress_post={$ag->ID}|amapress_assemblee_generale_date", sprintf( __( "Les inscrits à %s", 'amapress' ), $ag->getTitle() ), "assemblee" );

		$cnt -= 1;
	}
	$res[ __( 'Assemblées générales', 'amapress' ) ] = $ret;

	$ret = array();
	//event
	$cnt = 5;
	foreach ( AmapressAmap_event::get_next_amap_events() as $ev ) {
		if ( $cnt == 0 ) {
			continue;
		}

		amapress_add_message_target( $ret, "amapress_post={$ev->ID}|amapress_amap_event_participants|amapress_post={$ev->ID}|amapress_amap_event_date", sprintf( __( "Les inscrits à %s", 'amapress' ), $ev->getTitle() ), "event" );

		$cnt -= 1;
	}
	$res[ __( 'Evènements', 'amapress' ) ] = $ret;

	$ret = array();
	//avec adhésion
	amapress_add_message_target( $ret, "post_type=amps_adhesion&amapress_date=active|amapress_adhesion_adherent,amapress_adhesion_adherent2,amapress_adhesion_adherent3,amapress_adhesion_adherent4", __( "Les amapiens avec contrats", 'amapress' ), "with-contrats" );
	//intermittants
	amapress_add_message_target( $ret, "user:amapress_contrat=intermittent", __( "Les intermittents", 'amapress' ), "intermittent" );
	amapress_add_message_target( $ret, "user:amapress_contrat=principal&amapress_role=active", __( "Les amapiens principaux", 'amapress' ), "principaux" );
	amapress_add_message_target( $ret, "user:amapress_contrat=principal_contrat", __( "Les amapiens principaux (avec contrat)", 'amapress' ), "principaux" );
	amapress_add_message_target( $ret, "user:amapress_role=active", __( "Tous les amapiens (adhésions/contrats/collectif sauf producteurs)", 'amapress' ), "principaux" );
	//sans adhésion
	amapress_add_message_target( $ret, "no_adhesion", __( "Les amapiens sans contrat", 'amapress' ), "sans-adhesion" );
	amapress_add_message_target( $ret, "never_logged", __( "Les amapiens jamais connectés", 'amapress' ), "never-logged" );
	amapress_add_message_target( $ret, 'user:amapress_role=archivable', __( "Les amapiens archivables", 'amapress' ), 'archivables' );

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAPIEN_GROUP,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		amapress_add_message_target( $ret, "user:amapress_role=amapien_group_{$role->slug}", __( 'Groupe amapiens "', 'amapress' ) . $role->name . '"', "amapiens" );
	}


	$res[ __( 'Amapiens', 'amapress' ) ] = $ret;

	$ret = array();
	amapress_add_message_target( $ret, "user:amapress_adhesion=nok", __( "Les amapiens avec adhésion AMAP non réglée", 'amapress' ), 'adh-nok' );
	$res[ __( 'Trésorerie', 'amapress' ) ] = $ret;

	Amapress::setFilterForReferent( true );


	return $res;
}
