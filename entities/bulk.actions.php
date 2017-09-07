<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_footer', 'amapress_bulk_actions_admin_footer' );
function amapress_bulk_actions_admin_footer() {
	global $post_type, $pagenow;

	$pt   = amapress_simplify_post_type( 'users.php' == $pagenow ? 'user' : $post_type );
	$type = AmapressEntities::getPostType( $pt );
	if ( ! empty( $type['bulk_actions'] ) ) {
		?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
				<?php
				foreach ( $type['bulk_actions'] as $bulk_name => $bulk_desc ) {
					echo "jQuery('<option>').val('{$bulk_name}').text('{$bulk_desc['label']}').appendTo(\"select[name='action']\");";
					echo "jQuery('<option>').val('{$bulk_name}').text('{$bulk_desc['label']}').appendTo(\"select[name='action2']\");";
				}
				?>
            });
        </script>
		<?php
	}
}

add_action( 'load-users.php', 'amapress_custom_bulk_action' );
add_action( 'load-edit.php', 'amapress_custom_bulk_action' );
function amapress_custom_bulk_action() {
	global $typenow, $pagenow;
	$post_type = $typenow;

	$pt   = amapress_simplify_post_type( 'users.php' == $pagenow ? 'user' : $post_type );
	$type = AmapressEntities::getPostType( $pt );
	if ( ! empty( $type['bulk_actions'] ) ) {

		// get the action
		$wp_list_table = _get_list_table( 'users.php' == $pagenow ? 'WP_Users_List_Table' : 'WP_Posts_List_Table' );  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
		$action        = $wp_list_table->current_action();

		$allowed_actions = array_keys( $type['bulk_actions'] );
		if ( ! in_array( $action, $allowed_actions ) ) {
			return;
		}

		// security check
//        check_admin_referer('users.php' == $pagenow ? 'bulk-users' : 'bulk-posts');

		// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
		if ( isset( $_REQUEST['post'] ) ) {
			$post_ids = array_map( 'intval', $_REQUEST['post'] );
		}
		if ( isset( $_REQUEST['users'] ) ) {
			$post_ids = array_map( 'intval', $_REQUEST['users'] );
		}

		if ( empty( $post_ids ) ) {
			return;
		}

		// this is based on wp-admin/edit.php
		$sendback = remove_query_arg( array( 'amp_bulk_count', 'untrashed', 'deleted', 'ids' ), wp_get_referer() );
		if ( ! $sendback ) {
			$sendback = admin_url( 'users.php' == $pagenow ? 'users.php' : "edit.php?post_type=$post_type" );
		}

		$pagenum  = $wp_list_table->get_pagenum();
		$sendback = add_query_arg( 'paged', $pagenum, $sendback );

		$sendback = remove_query_arg( array(
			'action',
			'action2',
			'tags_input',
			'post_author',
			'comment_status',
			'ping_status',
			'_status',
			'post',
			'bulk_edit',
			'post_view'
		), $sendback );
		$sendback = add_query_arg( array( 'amp_bulk_action' => $action, 'ids' => join( ',', $post_ids ) ), $sendback );
		$sendback = apply_filters( "amapress_bulk_action_{$action}", $sendback, $post_ids );

		wp_redirect( $sendback );
		exit();
	}
}

function amapress_add_bulk_count( $url, $count ) {
	return add_query_arg( $url, 'amp_bulk_count', $count );
}

add_action( 'admin_notices', 'amapress_custom_bulk_actions_admin_notices' );
function amapress_custom_bulk_actions_admin_notices() {
	global $post_type, $pagenow;

	if ( 'edit.php' == $pagenow || 'users.php' == $pagenow ) {
		$pt   = amapress_simplify_post_type( 'users.php' == $pagenow ? 'user' : $post_type );
		$type = AmapressEntities::getPostType( $pt );
		if ( ! empty( $type['bulk_actions'] ) ) {
			if ( isset( $_REQUEST['amp_bulk_count'] ) && (int) $_REQUEST['amp_bulk_count'] ) {
				$n                   = (int) $_REQUEST['amp_bulk_count'];
				$action              = $_REQUEST['amp_bulk_action'];
				$bulk_action_results = isset( $type['bulk_actions'][ $action ]['messages'] ) ?
					$type['bulk_actions'][ $action ]['messages'] :
					array(
						'<=0' => "Une erreur est survenue pendant l'exécution de {$action}",
						'>1'  => "%s {$post_type} mis à jour par {$action}",
					);
				$message             = '';
				$message_index       = strval( $n );
				if ( isset( $bulk_action_results[ $message_index ] ) ) {
					$message = $bulk_action_results[ $message_index ];
				} else {
					if ( $n < 0 && isset( $bulk_action_results['<=0'] ) ) {
						$message = $bulk_action_results['<=0'];
					} else if ( $n > 1 && isset( $bulk_action_results['>1'] ) ) {
						$message = $bulk_action_results['>1'];
					}
				}
				$message = sprintf( $message, number_format_i18n( $n ) );
				if ( $n < 0 ) {
					$class = 'notice-error';
				} else if ( $n > 0 ) {
					$class = 'notice-success';
				} else {
					$class = 'notice-warning';
				}
				//number_format_i18n($_REQUEST['exported'])

				printf( '<div class="notice %1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}
		}
	}
}