<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_update_all_posts() {
	foreach ( AmapressEntities::getPostTypes() as $k => $v ) {
		if ( $k == 'user' ) {
			continue;
		}
		$posts = get_posts( array( 'post_type' => $k, 'posts_per_page' => - 1 ) );
		foreach ( $posts as $post ) {
			amapress_compute_post_slug_and_title( $post );
		}
	}
}

function amapress_set_slugs_and_titles_on_save( $post_id, WP_Post $post = null ) {
	//Check it's not an auto save routine
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
//
//    if (wp_is_post_revision( $post_id )) return;

	//Perform permission checks! For example:
//    if ( !current_user_can('edit_post', $post_id) )
//        return;

	//If calling wp_update_post, unhook this function so it doesn't loop infinitely
	remove_action( 'wp_insert_post', 'amapress_set_slugs_and_titles_on_save', 12 );


	if ( ! $post ) {
		$post = get_post( $post_id );
	}
	amapress_compute_post_slug_and_title( $post );

	// re-hook this function
	add_action( 'wp_insert_post', 'amapress_set_slugs_and_titles_on_save', 12, 2 );
}

add_action( 'wp_insert_post', 'amapress_set_slugs_and_titles_on_save', 12, 2 );

/**
 * @param $post_id
 * @param WP_Post $post
 */
function amapress_compute_post_slug_and_title( WP_Post $post ) {
	$types = AmapressEntities::getPostTypes();
	$pt    = amapress_simplify_post_type( $post->post_type );
	if ( array_key_exists( $pt, $types ) ) {
		$t              = $types[ $pt ];
		$post_title     = $post->post_title;
		$new_post_title = apply_filters( "amapress_{$pt}_title_formatter", $post_title, $post );
		$new_post_slug  = null;
		if ( array_key_exists( 'slug_format', $t ) ) {
			if ( $t['slug_format'] == 'from_title' ) {
				$new_post_slug = wp_unique_post_slug( sanitize_title( $new_post_title ), $post->ID, $post->post_status, $post->post_type, $post->post_parent );
			} else if ( $t['slug_format'] == 'from_id' ) {
				$new_post_slug = wp_unique_post_slug( $post->ID, $post->ID, $post->post_status, $post->post_type, $post->post_parent );
			} else {
				$new_post_slug = wp_unique_post_slug(
					apply_filters( "amapress_{$pt}_slug_formatter", $post->post_name, $post ),
					$post->ID, $post->post_status, $post->post_type, $post->post_parent );
			}
		}
		$upt = array( 'ID' => $post->ID );
		if ( $new_post_slug ) {
			$upt['post_name'] = $new_post_slug;
		}
		if ( $new_post_title ) {
			$upt['post_title'] = $new_post_title;
		}

		if ( count( $upt ) > 1 ) {
			wp_update_post( $upt );
		}
	}
}

add_action( 'amapress_posts_import', 'amapress_after_post_import' );
function amapress_after_post_import( $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		amapress_compute_post_slug_and_title( get_post( $post_id ) );
	}
}