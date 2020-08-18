<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_post_test_mail', function () {
	$key = Amapress::getOption( 'test_mail_key' );
	if ( empty( $_GET['key'] ) || $_GET['key'] != $key ) {
		wp_die( 'Access denied' );
	}

	if ( ! defined( 'DOING_CRON' ) ) {
		define( 'DOING_CRON', true );
	}
	$args = $_GET;
	do_action( $_GET['call'], $args );
	die();
} );

function wp_redirect_and_exit( $location, $status = 302 ) {
	if ( headers_sent() || ! wp_redirect( $location, $status ) ) {
		die( 'Bad redirect usage' );
	}
	exit;
}

//add_action('amapress_redirect_agenda', 'amapress_redirect_agenda');
//function amapress_redirect_agenda()
//{
//    wp_redirect_and_exit(Amapress::getPageLink('agenda-page'));
//}
function amapress_redirect_info() {
	amapress_redirect_login();
	wp_redirect_and_exit( Amapress::getPageLink( 'mes-infos-page' ) );
}

function amapress_redirect_home() {
	amapress_redirect_login();
	wp_redirect_and_exit( home_url() );
}

function amapress_redirect_login() {
	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}
}

//add_action('amapress_redirect_archive_template_page', 'amapress_redirect_archive_template_page');
//function amapress_redirect_archive_template_page() {
//    wp_redirect(get_post_permalink(Amapress::getOption('agenda-page')));
//}
//

function amapress_action_link( $post_id, $action, $action_params = null, $add_nonce = false ) {
	$base = trailingslashit( get_post_permalink( $post_id ) );
	$base .= $action;
	if ( $action_params ) {
		foreach ( $action_params as $param ) {
			$base .= '/' . $param;
		}
	}
	$base .= '/';
	if ( $add_nonce === true ) {
		$base = wp_nonce_url( $base, $action, $action . '_nonce' );
	}

	return $base;
}

add_filter( 'template_include', 'amapress_handle_templates' );
function amapress_handle_templates( $template ) {
//    if (is_author()) {
//        var_dump(get_the_author());
//        die('xxx');
//        return locate_template(array('page.php'));
//    }

	$raw_pt = get_query_var( 'post_type' );
	$pt     = amapress_simplify_post_type( $raw_pt );
	if ( is_array( $pt ) ) {
		return $template;
	}

//	$pts = AmapressEntities::getPostTypes();
//	if ( is_main_query() && ! empty( $pt ) && array_key_exists( $pt, $pts ) ) {
//		if ( isset( $pts[ $pt ]['custom_archive_template'] ) ) {
//			$t = Amapress::getOption( 'archive-page-template' );
//			if ( empty( $t ) ) {
//				$t = 'page.php';
//			}
//			$tmpl = locate_template( $t );
//			if ( ! empty( $tmpl ) ) {
//				return $tmpl;
//			}
//		}
//	}

	$new_template = $template;
	$action       = get_query_var( 'amp_action' );
	if ( amapress_is_user_logged_in() ) {
		$new_template = apply_filters( "amapress_get_query_action_template_{$pt}_{$action}", $new_template );
		$new_template = apply_filters( "amapress_get_query_action_template_{$action}", $new_template );
	} else {
		$new_template = apply_filters( "amapress_get_public_query_action_template_{$pt}_{$action}", $new_template );
		$new_template = apply_filters( "amapress_get_public_query_action_template_{$action}", $new_template );
	}

	if ( $new_template === $template ) {
		if ( is_main_query() && 0 === strpos( $raw_pt, 'amps_' ) ) {
			if ( is_single() ) {
				$tmpl = Amapress::getOption( 'amps-tmpl-file' );
				if ( ! empty( $tmpl ) ) {
					return locate_template( array( $tmpl ) );
				}
			} elseif ( is_archive() ) {
				$tmpl = Amapress::getOption( 'amps-arch-tmpl-file' );
				if ( ! empty( $tmpl ) ) {
					return locate_template( array( $tmpl ) );
				}
			}
		}
	}

	return $new_template;
}

add_filter( 'get_the_archive_title', function ( $title ) {
	if ( is_post_type_archive() ) {
		$title = post_type_archive_title( 'Nos ', false );
	}

	return $title;
} );

/*
 * Returns the bbPress Forum ID from given Post ID and Post Type
 *
 * returns: bbPRess Forum ID
 */
function amapress_get_forum_id_from_post_id( $post_id, $post_type = null ) {
	if ( empty ( $post_type ) ) {
		$post_type = get_post_type( $post_id );
	}
	$forum_id = 0;

	// Check post type
	switch ( $post_type ) {
		// Forum
		case bbp_get_forum_post_type() :
			$forum_id = bbp_get_forum_id( $post_id );
			break;

		// Topic
		case bbp_get_topic_post_type() :
			$forum_id = bbp_get_topic_forum_id( $post_id );
			break;

		// Reply
		case bbp_get_reply_post_type() :
			$forum_id = bbp_get_reply_forum_id( $post_id );
			break;
	}

	return $forum_id;
}

add_action( 'bbp_template_redirect', 'amapress_handle_actions' );
add_action( 'template_redirect', 'amapress_handle_actions' );
function amapress_handle_actions() {
	global $wp_query, $wpdb;
//    var_dump($wp_query);
	if ( is_404() ) {
//        die();
		$private = $wpdb->get_row( $wp_query->request );
		if ( ! $private && isset( $wp_query->query_vars['name'] ) && isset( $wp_query->query_vars['post_type'] ) ) {
			foreach ( array( 'private', 'publish' ) as $status ) {
				$posts = get_posts( array(
					'name'           => $wp_query->query_vars['name'],
					'post_type'      => $wp_query->query_vars['post_type'],
					'post_status'    => $status,
					'posts_per_page' => 1
				) );
				if ( ! empty( $posts ) ) {
					$private = $posts[0];
					break;
				}
			}
		}
		if ( $private ) {
			$the_id = $private->ID;
			if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
				$the_id = amapress_get_forum_id_from_post_id( $the_id );
			}
			$redirect_page = intval( get_post_meta( $the_id, 'amps_rd', true ) );
			if ( $redirect_page ) {
				wp_redirect_and_exit( get_page_link( $redirect_page ) );
			} else {
				amapress_redirect_login();
			}
		} else {
			if ( isset( $wp_query->query_vars['post_type'] ) ) {
				$pt = $wp_query->query_vars['post_type'];
				if ( 'forum' == $pt || 'topic' == $pt || 'reply' == $pt ) {
					amapress_redirect_login();
				}
			}
		}
	}

	if ( ! amapress_is_user_logged_in() ) {
		if ( is_author() ) {
			amapress_redirect_login();
		}

		$the_id = get_the_ID();
		if ( function_exists( 'is_bbpress' ) ) {
			if ( ! empty( $the_id ) && is_bbpress() ) {
				$the_id = amapress_get_forum_id_from_post_id( $the_id );
			}
		}
		if ( get_post_meta( $the_id, 'amps_lo', true ) == 1 ) {
			$redirect_page = intval( get_post_meta( $the_id, 'amps_rd', true ) );
			if ( $redirect_page ) {
				wp_redirect_and_exit( get_page_link( $redirect_page ) );
			} else {
				amapress_redirect_login();
			}
		}
	}
	$pt = get_query_var( 'post_type' );
	if ( is_array( $pt ) ) {
		return;
	}

	if ( is_main_query() && ! empty( $pt ) ) {
		$pt  = amapress_simplify_post_type( $pt );
		$pts = AmapressEntities::getPostTypes();
		if ( array_key_exists( $pt, $pts ) && is_archive() ) {
			if ( isset( $pts[ $pt ]['redirect_archive'] ) ) {
				do_action( $pts[ $pt ]['redirect_archive'] );
			}
		}
	}

	if ( get_the_ID() && is_page() ) {
//        if (is_page(Amapress::getOption('trombinoscope-page'))) {
//            if (!amapress_is_user_logged_in()) amapress_redirect_login();
//        } else
		if ( is_page( Amapress::getOption( 'mes-infos-page' ) ) ) {
			if ( ! amapress_is_user_logged_in() ) {
				amapress_redirect_login();
			}
		}

		if ( is_singular() ) {
			global $post;

			if ( ! empty( $post->post_content ) ) {
				if ( strpos( $post->post_content, '[amapress-redirect-' ) !== false ) {
					do_shortcode( $post->post_content );
				}
			}
		}
	}

	$action = get_query_var( 'amp_action' );
	if ( amapress_is_user_logged_in() ) {
		do_action( "amapress_do_query_action_{$pt}_{$action}" );
		do_action( "amapress_do_query_action_{$action}" );
	} else {
		do_action( "amapress_do_public_query_action_{$pt}_{$action}" );
		do_action( "amapress_do_public_query_action_{$action}" );
	}
}

function amapress_mail_to_admin( $subject, $message, TitanEntity $post = null, $attachments = array(), $cc = null, $bcc = null, $headers = '' ) {
	$admin_email = get_option( 'admin_email' );
	$admin_user  = get_user_by( 'email', $admin_email );
	if ( $admin_user ) {
		$user    = AmapressUser::getBy( $admin_user );
		$subject = amapress_replace_mail_placeholders( $subject, $user, $post );
		$message = amapress_replace_mail_placeholders( $message, $user, $post );
	}
	amapress_wp_mail( $admin_email, $subject, $message, $headers, $attachments, $cc, $bcc );
}

function amapress_mail_to_current_user( $subject, $message, $user_id = null, TitanEntity $post = null, $attachments = array(), $cc = null, $bcc = null, $headers = '' ) {
	if ( ! $user_id ) {
		$user_id = amapress_current_user_id();
	}
	$user    = AmapressUser::getBy( $user_id );
	$subject = amapress_replace_mail_placeholders( $subject, $user, $post );
	$message = amapress_replace_mail_placeholders( $message, $user, $post );
	amapress_wp_mail( implode( ',', $user->getAllEmails() ), $subject, $message, $headers, $attachments, $cc, $bcc );
}

function amapress_mail_current_user_inscr(
	TitanEntity $post, $user_id = null, $event_type = 'event',
	$replace_callback = null, $mail_type = null, $reply_to = null,
	$cc = null
) {
	if ( empty( $mail_type ) ) {
		$mail_type = $event_type;
	}

	$subject = Amapress::getOption( "inscr-$mail_type-mail-subject" );
	if ( empty( $subject ) || empty( trim( $subject ) ) ) {
		$subject = Amapress::getOption( 'inscr-event-mail-subject' );
	}
	$content = Amapress::getOption( "inscr-$mail_type-mail-content" );
	if ( empty( $content ) || empty( trim( $content ) ) ) {
		$content = Amapress::getOption( 'inscr-event-mail-content' );
	}

	$headers = [];
	if ( ! empty( $reply_to ) ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	} else {
		$term_id = Amapress::getOption( "resp-$event_type-amap-role" );
		if ( ! empty( $term_id ) ) {
			$emails = AmapressUser::getEmailsForAmapRole( $term_id );
			if ( ! empty( $emails ) ) {
				$headers[] = 'Reply-To: ' . implode( ',', $emails );
			}
		}
	}

	if ( $replace_callback && is_callable( $replace_callback, false ) ) {
		$subject = call_user_func( $replace_callback, $subject, $user_id, $post );
		$content = call_user_func( $replace_callback, $content, $user_id, $post );
	}

	amapress_mail_to_current_user( $subject, $content, $user_id, $post, [], $cc, null, $headers );
}

function amapress_mail_current_user_desinscr(
	TitanEntity $post, $user_id = null, $event_type = 'event',
	$replace_callback = null, $mail_type = null, $reply_to = null
) {
	if ( empty( $mail_type ) ) {
		$mail_type = $event_type;
	}

	$subject = Amapress::getOption( "desinscr-$mail_type-mail-subject" );
	if ( empty( $subject ) || empty( trim( $subject ) ) ) {
		$subject = Amapress::getOption( 'desinscr-event-mail-subject' );
	}
	$content = Amapress::getOption( "desinscr-$mail_type-mail-content" );
	if ( empty( $content ) || empty( trim( $content ) ) ) {
		$content = Amapress::getOption( 'desinscr-event-mail-content' );
	}

	$headers = [];
	if ( ! empty( $reply_to ) ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	} else {
		$term_id = Amapress::getOption( "resp-$event_type-amap-role" );
		if ( ! empty( $term_id ) ) {
			$emails = AmapressUser::getEmailsForAmapRole( $term_id );
			if ( ! empty( $emails ) ) {
				$headers[] = 'Reply-To: ' . implode( ',', $emails );
			}
		}
	}

	if ( $replace_callback && is_callable( $replace_callback, false ) ) {
		$subject = call_user_func( $replace_callback, $subject, $user_id, $post );
		$content = call_user_func( $replace_callback, $content, $user_id, $post );
	}

	amapress_mail_to_current_user( $subject, $content,
		$user_id, $post, [],
		amapress_get_recall_cc_from_option( "desinscr-$mail_type-mail-cc" ),
		null, $headers );
}
