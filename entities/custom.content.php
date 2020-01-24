<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'the_title', 'amapress_custom_post_title' );
add_filter( 'wp_title', 'amapress_custom_post_title_no_tags', 99 );
add_filter( 'document_title_parts', 'amapress_document_title_parts' );
function amapress_document_title_parts( $parts ) {
	$parts['title'] = trim( strip_tags( amapress_unfiltered_custom_post_title( $parts['title'] ) ) );

	return $parts;
}

function amapress_custom_post_title_no_tags( $title ) {
	return trim( strip_tags( amapress_custom_post_title( $title ) ) );
}

function amapress_custom_post_title( $title ) {
	if ( is_main_query() && in_the_loop() ) {
		$title = amapress_unfiltered_custom_post_title( $title );
	}

	return $title;
}

function amapress_unfiltered_custom_post_title( $title ) {
	if ( ! is_user_logged_in() && get_post_meta( get_the_ID(), 'amps_lo', true ) == 1 ) {
		return '';
	}

	if ( is_page() ) {
//        if (is_page(Amapress::getOption('agenda-page'))) {
//            $title = apply_filters('amapress_get_custom_title_page_agenda', $title);
//        } else
//            if (is_page(Amapress::getOption('trombinoscope-page'))) {
//            $title = apply_filters('amapress_get_custom_title_page_trombinoscope', $title);
//        } else if (is_page(Amapress::getOption('recettes-page'))) {
//            $title = apply_filters('amapress_get_custom_title_page_recettes', $title);
//        } else
//        if (is_page(Amapress::getOption('mes-infos-page'))) {
//            $viewmode = get_query_var('viewmode');
//            $subview = get_query_var('subview');
//            if (empty($viewmode)) $viewmode = 'default';
//            $title = apply_filters("amapress_get_user_infos_title_{$viewmode}", $title, $subview);
//        }
	} else if ( is_single() ) {
		$post_type = amapress_simplify_post_type( get_post_type() );
		$action    = get_query_var( 'amp_action' );
		if ( ! empty( $action ) ) {
			$title = apply_filters( "amapress_get_custom_title_{$post_type}_$action", $title );
		} else {
			$title = apply_filters( "amapress_get_custom_title_{$post_type}", $title );
		}
	} else if ( is_archive() ) {
		$post_type = amapress_simplify_post_type( get_post_type() );
		$title     = apply_filters( "amapress_get_custom_archive_title_{$post_type}", $title );
	}

	return $title;
}

add_filter( 'the_excerpt', 'amapress_custom_post_excerpt' );
function amapress_custom_post_excerpt( $content ) {
	if ( ! is_user_logged_in() && get_post_meta( get_the_ID(), 'amps_lo', true ) == 1 ) {
		return '';
	}

	if ( is_main_query() ) {
		if ( is_front_page() ) {
			$content = apply_filters( 'amapress_get_custom_excerpt_page_front', $content );
//        } else if (is_author()) {
//            ob_start();
//            AmapressUsers::echoUserById(get_the_ID(), 'thumb');
//            $content = ob_get_contents();
//            ob_end_clean();
		} else if ( is_single() || is_search() ) {
			$post_type = amapress_simplify_post_type( get_post_type() );
			$action    = get_query_var( 'amp_action' );
			$viewmode  = get_query_var( 'viewmode' );
			if ( empty( $viewmode ) ) {
				$viewmode = 'default';
			}
			$subview = get_query_var( 'subview' );
			if ( ! empty( $action ) ) {
				$content = apply_filters( "amapress_get_custom_excerpt_{$post_type}_{$viewmode}_$action", $content, $subview );
				$content = apply_filters( "amapress_get_custom_excerpt_{$post_type}_$action", $content, $viewmode, $subview );
			} else {
				$content = apply_filters( "amapress_get_custom_excerpt_{$post_type}_{$viewmode}", $content, $subview );
				$content = apply_filters( "amapress_get_custom_excerpt_{$post_type}", $content, $viewmode, $subview );
			}
		} else if ( is_archive() || is_category() ) {
			$post_type = amapress_simplify_post_type( get_post_type() );
			$content   = apply_filters( "amapress_get_custom_archive_excerpt_{$post_type}", $content );
		}
		$content = amapress_handle_and_get_action_messages() . $content;
	}

	return $content;
}

add_filter( 'the_content', 'amapress_custom_post_content' );
function amapress_custom_post_content( $content ) {
	if ( ! is_user_logged_in() && get_post_meta( get_the_ID(), 'amps_lo', true ) == 1 ) {
		return '';
	}
	if ( is_admin() ) {
		return '';
	}

	if ( is_main_query() ) {
		$old_content = $content;
		if ( is_front_page() && get_the_ID() == get_option( 'page_on_front' ) ) {
			$content = apply_filters( 'amapress_get_custom_content_page_front', $content );
		} else if ( is_single() || is_search() ) {
			$post_type = amapress_simplify_post_type( get_post_type() );
			$action    = get_query_var( 'amp_action' );
			$viewmode  = get_query_var( 'viewmode' );
			if ( empty( $viewmode ) ) {
				$viewmode = 'default';
			}
			$subview = get_query_var( 'subview' );
			if ( ! empty( $action ) ) {
				$content = apply_filters( "amapress_get_custom_content_{$post_type}_{$viewmode}_$action", $content, $subview );
				$content = apply_filters( "amapress_get_custom_content_{$post_type}_$action", $content, $viewmode, $subview );
			} else {
				$content = apply_filters( "amapress_get_custom_content_{$post_type}_{$viewmode}", $content, $subview );
				$content = apply_filters( "amapress_get_custom_content_{$post_type}", $content, $viewmode, $subview );
			}
		} else if ( is_archive() || is_category() ) {
			$post_type = amapress_simplify_post_type( get_post_type() );
			$content   = apply_filters( "amapress_get_custom_archive_content_{$post_type}", $content );
		}
		$content = amapress_handle_and_get_action_messages() . $content;

		if ( $old_content != $content ) {
			amapress_ensure_no_cache();
		}
	}

	return $content;
}

