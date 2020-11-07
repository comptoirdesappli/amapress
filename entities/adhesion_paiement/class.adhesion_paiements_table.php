<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Adhesion_Paiements_List_Table extends WP_List_Table {
	function __construct() {
		global $status, $page;
		parent::__construct( array(
			'singular' => __( 'Adhésion', 'amapress' ),     //singular name of the listed records
			'plural'   => __( 'Adhésions', 'amapress' ),   //plural name of the listed records
			'ajax'     => false        //does this table support ajax?
		) );

	}

	function no_items() {
		_e( 'Pas d\'adhésion ???.' );
	}

	public function get_columns() {
		$c = array(
			'cb'       => '<input type="checkbox" />',
			'username' => __( 'Username' ),
			'name'     => __( 'Name' ),
			'email'    => __( 'Email' ),
			'role'     => __( 'Role' ),
		);

		$c = apply_filters( 'manage_users_columns', $c );

		return $c;
	}

	protected function get_sortable_columns() {
		$c = array(
			'username' => 'login',
			'email'    => 'email',
		);

		$c = apply_filters( 'manage_users_sortable_columns', $c );

		return $c;
	}

	protected function get_role_list( $user_object ) {
		$wp_roles  = wp_roles();
		$role_list = array();
		foreach ( $user_object->roles as $role ) {
			if ( isset( $wp_roles->role_names[ $role ] ) ) {
				$role_list[ $role ] = translate_user_role( $wp_roles->role_names[ $role ] );
			}
		}
		if ( empty( $role_list ) ) {
			$role_list['none'] = _x( 'None', 'no user roles' );
		}

		/**
		 * Filters the returned array of roles for a user.
		 *
		 * @since 4.4.0
		 *
		 * @param array $role_list An array of user roles.
		 * @param WP_User $user_object A WP_User object.
		 */
		return apply_filters( 'get_role_list', $role_list, $user_object );
	}

	public function single_row( $user_object, $style = '', $role = '', $numposts = 0 ) {
		if ( ! ( $user_object instanceof WP_User ) ) {
			$user_object = get_userdata( (int) $user_object );
		}
		$user_object->filter = 'display';
		$email               = $user_object->user_email;
		$user_roles          = $this->get_role_list( $user_object );
		// Set up the hover actions for this user
		$actions  = array();
		$checkbox = '';
		// Check if the user for this row is editable
		if ( current_user_can( 'list_users' ) ) {
			// Set up the user editing link
			$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user_object->ID ) ) );
			if ( current_user_can( 'edit_users', $user_object->ID ) ) {
				$edit            = "<strong><a href=\"$edit_link\">$user_object->user_login</a></strong><br />";
				$actions['edit'] = '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
			} else {
				$edit = "<strong>$user_object->user_login</strong><br />";
			}
			/**
			 * Filters the action links displayed under each user in the Users list table.
			 *
			 * @since 2.8.0
			 *
			 * @param array $actions An array of action links to be displayed.
			 *                             Default 'Edit', 'Delete' for single site, and
			 *                             'Edit', 'Remove' for Multisite.
			 * @param WP_User $user_object WP_User object for the currently-listed user.
			 */
			$actions = apply_filters( 'user_row_actions', $actions, $user_object );
			// Role classes.
			$role_classes = esc_attr( implode( ' ', array_keys( $user_roles ) ) );
			// Set up the checkbox ( because the user is editable, otherwise it's empty )
			$checkbox = '<label class="screen-reader-text" for="user_' . $user_object->ID . '">' . sprintf( __( 'Select %s' ), $user_object->user_login ) . '</label>'
			            . "<input type='checkbox' name='users[]' id='user_{$user_object->ID}' class='{$role_classes}' value='{$user_object->ID}' />";
		} else {
			$edit = '<strong>' . $user_object->user_login . '</strong>';
		}
		$avatar = get_avatar( $user_object->ID, 32 );
		// Comma-separated list of user roles.
		$roles_list = implode( ', ', $user_roles );
		$r          = "<tr id='user-$user_object->ID'>";
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}
			if ( 'posts' === $column_name ) {
				$classes .= ' num'; // Special case for that column
			}
			if ( in_array( $column_name, $hidden ) ) {
				$classes .= ' hidden';
			}
			$data       = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';
			$attributes = "class='$classes' $data";
			if ( 'cb' === $column_name ) {
				$r .= "<th scope='row' class='check-column'>$checkbox</th>";
			} else {
				$r .= "<td $attributes>";
				switch ( $column_name ) {
					case 'username':
						$r .= "$avatar $edit";
						break;
					case 'name':
						$r .= "$user_object->first_name $user_object->last_name";
						break;
					case 'email':
						$r .= "<a href='" . esc_url( "mailto:$email" ) . "'>$email</a>";
						break;
					case 'role':
						$r .= esc_html( $roles_list );
						break;
					case 'posts':
						if ( $numposts > 0 ) {
							$r .= "<a href='edit.php?author=$user_object->ID' class='edit'>";
							$r .= '<span aria-hidden="true">' . $numposts . '</span>';
							$r .= '<span class="screen-reader-text">' . sprintf( _n( '%s post by this author', '%s posts by this author', $numposts ), number_format_i18n( $numposts ) ) . '</span>';
							$r .= '</a>';
						} else {
							$r .= 0;
						}
						break;
					default:
						/**
						 * Filters the display output of custom columns in the Users list table.
						 *
						 * @since 2.8.0
						 *
						 * @param string $output Custom column output. Default empty.
						 * @param string $column_name Column name.
						 * @param int $user_id ID of the currently-listed user.
						 */
						$r .= apply_filters( 'manage_users_custom_column', '', $column_name, $user_object->ID );
				}
				if ( $primary === $column_name ) {
					$r .= $this->row_actions( $actions );
				}
				$r .= "</td>";
			}
		}
		$r .= '</tr>';
		echo $r;
	}

	function get_bulk_actions() {
		$actions = array(//            'delete'    => 'Delete'
		);

		return $actions;
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="adhesion[]" value="%s" />', $item['ID']
		);
	}

	function prepare_items() {
		global $role, $usersearch;
		$usersearch     = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
		$role           = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';
		$users_per_page = $this->get_items_per_page( 'adhesions_per_page' );
		$paged          = $this->get_pagenum();
		if ( 'none' === $role ) {
			$args = array(
				'number'  => $users_per_page,
				'offset'  => ( $paged - 1 ) * $users_per_page,
				'include' => wp_get_users_with_no_role(),
				'search'  => $usersearch,
				'fields'  => 'all_with_meta'
			);
		} else {
			$args = array(
				'number' => $users_per_page,
				'offset' => ( $paged - 1 ) * $users_per_page,
				'role'   => $role,
				'search' => $usersearch,
				'fields' => 'all_with_meta'
			);
		}
		if ( '' !== $args['search'] ) {
			$args['search'] = '*' . $args['search'] . '*';
		}
		if ( isset( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
		}
		if ( isset( $_REQUEST['order'] ) ) {
			$args['order'] = $_REQUEST['order'];
		}
		/**
		 * Filters the query arguments used to retrieve users for the current users list table.
		 *
		 * @since 4.4.0
		 *
		 * @param array $args Arguments passed to WP_User_Query to retrieve items for the current
		 *                    users list table.
		 */
		$args = apply_filters( 'users_list_table_query_args', $args );
		// Query the user IDs for this page
		$wp_user_search = new WP_User_Query( $args );
		$this->items    = $wp_user_search->get_results();
		$this->set_pagination_args( array(
			'total_items' => $wp_user_search->get_total(),
			'per_page'    => $users_per_page,
		) );
	}

	protected function get_default_primary_column_name() {
		return 'username';
	}

} //class
function amapress_adhesion_list_options() {
	global $adhesions_table;
	$option = 'per_page';
	$args   = array(
		'label'   => 'Adhésions',
		'default' => 10,
		'option'  => 'adhesions_per_page'
	);
	add_screen_option( $option, $args );
	$adhesions_table = new Adhesion_Paiements_List_Table();
	add_filter( 'views_adhesions-paiements_page_adhesion_paiements', 'amapress_users_views_filter' );
}

function amapress_render_adhesion_list() {
	global $adhesions_table;
	$adhesions_table->prepare_items();

	echo '</pre><div class="wrap"><h2>' . 'Adhésions' . '</h2>';
	if ( current_user_can( 'create_users' ) ) { ?>
        <a href="<?php echo admin_url( 'user-new.php' ); ?>"
           class="page-title-action"><?php echo esc_html_x( 'Add New', 'user' ); ?></a>
	<?php } elseif ( is_multisite() && current_user_can( 'promote_users' ) ) { ?>
        <a href="<?php echo admin_url( 'user-new.php' ); ?>"
           class="page-title-action"><?php echo esc_html_x( 'Add Existing', 'user' ); ?></a>
	<?php }

	$adhesions_table->views();

	?>
    <form method="post">
        <input type="hidden" name="page" value="adhesion_paiements">
	<?php
	$adhesions_table->search_box( 'search', 'search_id' );
	$adhesions_table->display();
	echo '</form></div>';
}
