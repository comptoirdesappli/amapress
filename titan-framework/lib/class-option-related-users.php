<?php
/**
 * Post Title option
 *
 * @package Titan Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


class TitanFrameworkOptionRelatedUsers extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'query'             => null,
		'show_link'         => true,
		'link_text'         => 'Voir les (%%count%%) utilisateurs',
		'show_table'        => true,
		'bare'              => false,
		'include_columns'   => array(),
		'exclude_columns'   => array(),
		'datatable_options' => array(),
		'column_options'    => array(),
	);

	private function evalQuery( $postID = null ) {
		$post_type = null;
		if ( $this->type == self::TYPE_META ) {
			$postID = $this->getPostID( $postID );
			if ( $this->owner->isUser === true ) {
				$post_type = 'user';
			} else {
				$post_type = get_post_type( $postID );
			}
		}
		$option_id = $this->getID();

		$query = $this->settings['query'];
		if ( is_callable( $query, false ) ) {
			$query = call_user_func( $query, $postID );
		}

		$query = str_replace( '%%id%%', $postID, $query );
		$query = apply_filters( "tf_replace_placeholders_{$post_type}", $query, $postID );
		$query = apply_filters( "tf_replace_placeholders_{$option_id}", $query, $postID );

		return $query;
	}

	public function columnDisplayValue( $post_id ) {
		$query = $this->evalQuery( $post_id );
		$count = get_users_count( $query );
		$edit  = admin_url( 'users.php' );
		echo "<a href='$edit?{$query}'>{$count}</a>";
	}

	public function columnExportValue( $post_id ) {
		echo get_users_count( $this->evalQuery( $post_id ) );
	}

	public function display() {
		if ( ! $this->settings['bare'] ) {
			$this->echoOptionHeader();
		} else {
			$this->echoOptionHeaderBare();
		}

		$query               = $this->evalQuery();
		$args                = wp_parse_args( $query );
		$args['count_total'] = false;

		if ( $this->settings['show_link'] ) {
			$count = get_users_count( $query );
			if ( $count > 0 ) {
				$edit      = admin_url( 'users.php' );
				$link_text = esc_html( str_replace( '%%count%%', $count, $this->settings['link_text'] ) );
				echo "<a href='$edit?{$query}'>{$link_text}</a>";
			}
		}

		if ( $this->settings['show_table'] ) {
			$include_columns = $this->settings['include_columns'];
			$exclude_columns = $this->settings['exclude_columns'];
			$post_columns    = array();
			foreach ( $this->get_columns() as $col_name => $col_title ) {
				if ( ! empty( $include_columns ) && ! in_array( $col_name, $include_columns ) ) {
					continue;
				}
				if ( ! empty( $exclude_columns ) && in_array( $col_name, $exclude_columns ) ) {
					continue;
				}
				$post_columns[ $col_name ] = $col_title;
			}

			$columns = array();
			foreach ( $post_columns as $col_name => $col_title ) {
				$col_def = array(
					'title' => $col_title,
					'data'  => $col_name,
				);
				if ( isset( $this->settings['column_options'][ $col_name ] ) ) {
					$col_def = array_merge( $col_def, $this->settings['column_options'][ $col_name ] );
				}
				$columns[] = $col_def;
			}

//            var_dump($args['post_type']);
//            var_dump($post_columns);

			$posts = get_users( $args );
			$data  = array();
			foreach ( $posts as $post ) {
				$entry = array();
				foreach ( $post_columns as $col_name => $col_title ) {
					ob_start();
					$content = $this->column_default( $post, $col_name );
					echo $content;
					$entry[ $col_name ] = ob_get_clean();
				}
				$data[] = $entry;
			}

			echo amapress_get_datatable( $this->getID() . '-table', $columns, $data, $this->settings['datatable_options'] );
//        $posts =
		}


		if ( ! $this->settings['bare'] ) {
			$this->echoOptionFooter();
		} else {
			$this->echoOptionFooterBare();
		}
	}

	/**
	 *
	 * @return array
	 */
	private function get_columns() {
		$posts_columns = array(
			'username' => __( 'Username' ),
			'name'     => __( 'Name' ),
			'email'    => __( 'Email' ),
			'role'     => __( 'Role' ),
			'posts'    => __( 'Posts' )
		);

		/**
		 * Filter the columns displayed in the Posts list table for a specific post type.
		 *
		 * The dynamic portion of the hook name, `$post_type`, refers to the post type slug.
		 *
		 * @since 3.0.0
		 *
		 * @param array $post_columns An array of column names.
		 */
		return apply_filters( "manage_users_columns", $posts_columns );
	}

	/**
	 * Handles the default column output.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @param WP_Post $post The current WP_Post object.
	 * @param string $column_name The current column name.
	 */
	private function column_default( $user_object, $column_name ) {
		if ( ! ( $user_object instanceof WP_User ) ) {
			$user_object = get_userdata( (int) $user_object );
		}
		$user_object->filter = 'display';
		$email               = $user_object->user_email;
		$url                 = 'users.php?';
		$user_roles          = $this->get_role_list( $user_object );
		// Set up the hover actions for this user
		$actions     = array();
		$super_admin = '';
		if ( is_multisite() && current_user_can( 'manage_network_users' ) ) {
			if ( in_array( $user_object->user_login, get_super_admins(), true ) ) {
				$super_admin = ' &mdash; ' . __( 'Super Admin' );
			}
		}
		// Check if the user for this row is editable
		if ( current_user_can( 'list_users' ) ) {
			// Set up the user editing link
			$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user_object->ID ) ) );
			if ( current_user_can( 'edit_user', $user_object->ID ) ) {
				$edit            = "<strong><a href=\"{$edit_link}\">{$user_object->user_login}</a>{$super_admin}</strong><br />";
				$actions['edit'] = '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
			} else {
				$edit = "<strong>{$user_object->user_login}{$super_admin}</strong><br />";
			}
			if ( ! is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'delete_user', $user_object->ID ) ) {
				$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=delete&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Delete' ) . "</a>";
			}
			if ( is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'remove_user', $user_object->ID ) ) {
				$actions['remove'] = "<a class='submitdelete' href='" . wp_nonce_url( $url . "action=remove&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Remove' ) . "</a>";
			}
			// Add a link to the user's author archive, if not empty.
			$author_posts_url = get_author_posts_url( $user_object->ID );
			if ( $author_posts_url ) {
				$actions['view'] = sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url( $author_posts_url ),
					/* translators: %s: author's display name */
					esc_attr( sprintf( __( 'View posts by %s' ), $user_object->display_name ) ),
					__( 'View' )
				);
			}
			// Set up the checkbox ( because the user is editable, otherwise it's empty )
		} else {
			$edit = "<strong>{$user_object->user_login}{$super_admin}</strong>";
		}
		$avatar = get_avatar( $user_object->ID, 32 );
		// Comma-separated list of user roles.
		$roles_list = implode( ', ', $user_roles );

		switch ( $column_name ) {
			case 'username':
				echo "$avatar $edit";
				break;
			case 'name':
				echo "$user_object->first_name $user_object->last_name";
				break;
			case 'email':
				echo "<a href='" . esc_url( "mailto:$email" ) . "'>$email</a>";
				break;
			case 'role':
				echo esc_html( $roles_list );
				break;
			case 'posts':
				$numposts = count_user_posts( $user_object->ID );
				if ( $numposts > 0 ) {
					echo "<a href='edit.php?author=$user_object->ID' class='edit'>";
					echo '<span aria-hidden="true">' . $numposts . '</span>';
					echo '<span class="screen-reader-text">' . sprintf( _n( '%s post by this author', '%s posts by this author', $numposts ), number_format_i18n( $numposts ) ) . '</span>';
					echo '</a>';
				} else {
					echo 0;
				}
				break;
			default:
				/**
				 * Filter the display output of custom columns in the Users list table.
				 *
				 * @since 2.8.0
				 *
				 * @param string $output Custom column output. Default empty.
				 * @param string $column_name Column name.
				 * @param int $user_id ID of the currently-listed user.
				 */
				echo apply_filters( 'manage_users_custom_column', '', $column_name, $user_object->ID );
		}
	}

	/**
	 * Returns an array of user roles for a given user object.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_User $user_object The WP_User object.
	 *
	 * @return array An array of user roles.
	 */
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
}