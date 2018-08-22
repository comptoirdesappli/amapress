<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_row_action_href( $action, $id, $other_args = [] ) {
	global $pagenow;
	$g_query = $_SERVER['QUERY_STRING'];
	$href    = "$pagenow?$g_query";

	$args = array_merge(
		array(
			'action' => $action,
			'amp_id' => $id,
		),
		$other_args
	);

	return wp_nonce_url( add_query_arg(
		$args, admin_url( $href ) ), "{$action}_{$id}" );
}

function amapress_get_row_action_html( $action, $id, $label ) {

	return sprintf( '<a href="%1$s" class="%3$s" aria-label="%4$s">%2$s</a>',
		esc_url( amapress_get_row_action_href( $action, $id ) ),
		esc_html( $label ),
		esc_attr( $action ),
		esc_attr( $label ) );
}

add_filter( 'user_row_actions', 'amapress_row_actions_registration', 10, 2 );
add_filter( 'post_row_actions', 'amapress_row_actions_registration', 10, 2 );
add_filter( 'page_row_actions', 'amapress_row_actions_registration', 10, 2 );
function amapress_row_actions_registration( $actions, $post_or_user, $type = 'list' ) {
	if ( is_a( $post_or_user, 'WP_User' ) ) {
		$post_type = 'user';
	} else {
		$post_type = amapress_simplify_post_type( $post_or_user->post_type );
	}
	$post_type_config = AmapressEntities::getPostType( $post_type );
	if ( $post_type_config ) {
		if ( ! empty( $post_type_config['row_actions'] ) ) {
			foreach ( $post_type_config['row_actions'] as $row_action => $row_action_label_or_config ) {
				$row_action_config = is_array( $row_action_label_or_config ) ? $row_action_label_or_config : array();
				if ( is_string( $row_action_label_or_config ) ) {
					$row_action_config['label'] = $row_action_label_or_config;
				}

				$row_action_config = wp_parse_args( $row_action_config,
					array(
						'label'      => '',
						'capability' => '',
						'condition'  => null,
						'href'       => '',
						'target'     => '',
					) );
				if ( empty( $row_action_config['label'] ) ) {
					continue;
				}
				if ( ! empty( $row_action_config['capability'] )
				     && ! current_user_can( $row_action_config['capability'] ) ) {
					continue;
				}
				if ( ! empty( $row_action_config['show_on'] ) &&
				     ! in_array( $type, explode( ',', $row_action_config['show_on'] ) ) ) {
					continue;
				}
				if ( ! empty( $row_action_config['condition'] )
				     && is_callable( $row_action_config['condition'], false )
				     && ! call_user_func( $row_action_config['condition'], $post_or_user ) ) {
					continue;
				}
				if ( ! empty( $row_action_config['href'] ) ) {
					$label = $row_action_config['label'];
					$href  = $row_action_config['href'];
					if ( is_callable( $href, false ) ) {
						$href = call_user_func( $href, $post_or_user->ID );
					} else {
						$href = str_replace( '%id%', $post_or_user->ID, $href );
					}
					$actions[ $row_action ] = sprintf( '<a href="%1$s" class="%3$s" aria-label="%4$s"%5$s>%2$s</a>',
						$href,
						esc_html( $label ),
						esc_attr( $row_action ),
						esc_attr( $label ),
						! empty( $row_action_config['target'] ) ? ' target="' . $row_action_config['target'] . '"' : '' );
				} else {
					$actions[ $row_action ] = amapress_get_row_action_html( $row_action, $post_or_user->ID, $row_action_config['label'] );
				}
			}
		}
	}
	$actions = apply_filters( "amapress_row_actions", $actions, $post_or_user->ID, $post_type );
	$actions = apply_filters( "amapress_row_actions_{$post_type}", $actions, $post_or_user->ID );

	//var_dump($actions);
	return $actions;
}

add_action( 'load-users.php', 'amapress_row_actions_handler' );
add_action( 'load-edit.php', 'amapress_row_actions_handler' );
add_action( 'load-post.php', 'amapress_row_actions_handler' );
add_action( 'load-user-edit.php', 'amapress_row_actions_handler' );
add_action( 'load-profile.php', 'amapress_row_actions_handler' );
function amapress_row_actions_handler() {
	if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['amp_id'] ) ) {
		global $typenow, $pagenow;
		$post_type = $typenow;
		$id        = $_REQUEST['amp_id'];

		$post_type = amapress_simplify_post_type( 'users.php' == $pagenow ? 'user' : $post_type );
		$type      = AmapressEntities::getPostType( $post_type );
		$action    = $_REQUEST['action'];
		if ( ! empty( $type['row_actions'][ $action ] ) ) {
			check_admin_referer( "{$action}_{$id}" );

			do_action( "amapress_row_action", $id, $post_type, $action );
			do_action( "amapress_row_action_{$post_type}", $id, $action );
			do_action( "amapress_row_action_{$post_type}_{$action}", $id );
		}
	}
}

add_action( 'edit_form_after_title', 'amapress_add_row_actions_to_post_editor', 15 );
function amapress_add_row_actions_to_post_editor( WP_Post $post ) {
	$actions = amapress_row_actions_registration( [], $post, 'editor' );
	if ( ! empty( $actions ) ) {
		$actions = implode( ', ', $actions );
		echo "<p>Actions possibles : $actions</p>";
	}
}

add_action( 'personal_options', 'amapress_add_row_actions_to_user_editor', 15 );
function amapress_add_row_actions_to_user_editor( WP_User $user ) {
	$actions = amapress_row_actions_registration( [], $user, 'editor' );
	if ( ! empty( $actions ) ) {
		$actions = implode( ', ', $actions );
		echo "<tr class='row-action-wrap'><th scope='row'><label>Actions possibles</label></th><td>$actions</td></tr>";
	}
}