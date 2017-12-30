<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Alters the User query
 * to return a different list based on query vars on users.php
 *
 * @param WP_User_Query $query
 */
function amapress_amap_role_user_query( $query ) {
	global $wpdb;
	$args       = array(
		'object_type'       => array( 'user' ),
	);
	$taxonomies = get_taxonomies( $args, "names" );
	foreach ( $taxonomies as $taxonomy ) {
		if ( ! empty( $query->query_vars[ $taxonomy ] ) ) {
			$term_ids = array();
			if ( '*' == $query->query_vars[ $taxonomy ] ) {
				$terms    = array_map(
					function ( $t ) {
						return $t->term_id;
					}, get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				) );
				$term_ids = get_objects_in_term( $terms, $taxonomy );
			} else {
				$term = get_term_by( 'slug', esc_attr( $query->query_vars[ $taxonomy ] ), $taxonomy );
				if ( $term ) {
					$term_ids = get_objects_in_term( $term->term_id, $taxonomy );
				}
			}
			if ( ! isset( $ids ) || empty( $ids ) ) {
				$ids = $term_ids;
			} else {
				$ids = array_intersect( $ids, $term_ids );
			}
		}
	}
	if ( isset( $ids ) ) {
		$ids = implode( ',', wp_parse_id_list( $ids ) );
		if ( empty( $ids ) ) {
			$ids = '0';
		}
		$query->query_where .= " AND $wpdb->users.ID IN ($ids)";
	}
}

function amapress_amap_role_table_query_args( $args ) {
	$taxonomies = get_taxonomies( array(
		'object_type'       => array( 'user' ),
		'show_admin_column' => true
	), "objects" );
	foreach ( $taxonomies as $taxonomy ) {
		if ( ! empty( $_GET[ $taxonomy->name ] ) ) {
			$args[ $taxonomy->name ] = $_GET[ $taxonomy->name ];
		}
	}

	return $args;
}

/**
 * Fix a bug with highlighting the parent menu item
 * By default, when on the edit taxonomy page for a user taxonomy, the Posts tab is highlighted
 * This will correct that bug
 */
function amapress_amap_role_parent_menu( $parent = '' ) {
	global $pagenow;

	// If we're editing one of the user taxonomies
	// We must be within the users menu, so highlight that
	if ( ! empty( $_GET['taxonomy'] ) && $pagenow == 'edit-tags.php' && $_GET['taxonomy'] == AmapressUser::AMAP_ROLE ) {
		$parent = 'users.php';
	}

	return $parent;
}

/**
 * Add each of the taxonomies to the Users menu
 * They will behave in the same was as post taxonomies under the Posts menu item
 * Taxonomies will appear in alphabetical order
 */
function amapress_amap_role_admin_menu() {
	$key      = AmapressUser::AMAP_ROLE;
	$taxonomy = get_taxonomy( $key );
	add_users_page(
		$taxonomy->labels->menu_name,
		$taxonomy->labels->menu_name,
		$taxonomy->cap->manage_terms,
		"edit-tags.php?taxonomy={$key}"
	);
}

add_action( 'init', function () {
	add_filter( 'users_list_table_query_args', 'amapress_amap_role_table_query_args' );
	add_action( 'pre_user_query', 'amapress_amap_role_user_query', 12 );
	add_action( 'admin_menu', 'amapress_amap_role_admin_menu' );
	add_filter( 'parent_file', 'amapress_amap_role_parent_menu' );
} );