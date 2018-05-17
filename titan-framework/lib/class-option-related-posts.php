<?php
/**
 * Post Title option
 *
 * @package Titan Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TitanFrameworkOptionRelatedPosts extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'query'             => null,
		'show_link'         => true,
		'bare'              => false,
		'link_text'         => 'Voir les (%%count%%) éléments',
		'show_table'        => true,
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

		if ( is_string( $query ) ) {
			$query = str_replace( '%%id%%', $postID, $query );
		}
		$query = apply_filters( "tf_replace_placeholders_{$post_type}", $query, $postID );
		$query = apply_filters( "tf_replace_placeholders_{$option_id}", $query, $postID );

		return $query;
	}

	public function columnDisplayValue( $post_id ) {
		$query = $this->evalQuery( $post_id );
		$count = get_posts_count( $query );
		$edit  = admin_url( 'edit.php' );
		echo "<a href='$edit?{$query}'>{$count}</a>";
	}

	public function columnExportValue( $post_id ) {
		echo get_posts_count( $this->evalQuery( $post_id ) );
	}

	public function display() {
		if ( ! $this->settings['bare'] ) {
			$this->echoOptionHeader();
		} else {
			$this->echoOptionHeaderBare();
		}

		$query = $this->evalQuery();
		if ( is_array( $query ) ) {
			$args = $query;
		} else {
			$args = wp_parse_args( $query );
		}
		$args['posts_per_page'] = - 1;

		if ( $this->settings['show_link'] && ! is_array( $query ) ) {
			$count = get_posts_count( $query );
			if ( $count > 0 ) {
				$edit      = admin_url( 'edit.php' );
				$link_text = esc_html( str_replace( '%%count%%', $count, $this->settings['link_text'] ) );
				echo "<a href='$edit?{$query}'>{$link_text}</a>";
			}
		}

		if ( $this->settings['show_table'] ) {
			$include_columns = $this->settings['include_columns'];
			$exclude_columns = $this->settings['exclude_columns'];
			$post_columns    = array();
			foreach ( $this->get_columns( $args['post_type'] ) as $col_name => $col_title ) {
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

			$posts    = get_posts( $args );
			$post_ids = array();
			foreach ( $posts as $a_post ) {
				$post_ids[] = $a_post->ID;
			}
			$this->comment_pending_count = get_pending_comments_num( $post_ids );

			$data = array();
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
	private function get_columns( $post_type ) {
		$posts_columns = array();

//        $posts_columns['cb'] = '<input type="checkbox" />';

		/* translators: manage posts column name */
		$posts_columns['title'] = _x( 'Title', 'column name' );

		if ( post_type_supports( $post_type, 'author' ) ) {
			$posts_columns['author'] = __( 'Author' );
		}

		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		$taxonomies = wp_filter_object_list( $taxonomies, array( 'show_admin_column' => true ), 'and', 'name' );

		/**
		 * Filter the taxonomy columns in the Posts list table.
		 *
		 * The dynamic portion of the hook name, `$post_type`, refers to the post
		 * type slug.
		 *
		 * @since 3.5.0
		 *
		 * @param array $taxonomies Array of taxonomies to show columns for.
		 * @param string $post_type The post type.
		 */
		$taxonomies = apply_filters( "manage_taxonomies_for_{$post_type}_columns", $taxonomies, $post_type );
		$taxonomies = array_filter( $taxonomies, 'taxonomy_exists' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( 'category' === $taxonomy ) {
				$column_key = 'categories';
			} elseif ( 'post_tag' === $taxonomy ) {
				$column_key = 'tags';
			} else {
				$column_key = 'taxonomy-' . $taxonomy;
			}

			$posts_columns[ $column_key ] = get_taxonomy( $taxonomy )->labels->name;
		}

		$post_status = 'publish'; //!( $this->has_this_table_empty_query_arg('post_status') ) ? $this->get_this_table_query_arg('post_status') : 'all';
		if ( post_type_supports( $post_type, 'comments' ) && ! in_array( $post_status, array(
				'pending',
				'draft',
				'future'
			) ) ) {
			$posts_columns['comments'] = '<span class="vers comment-grey-bubble" title="' . esc_attr__( 'Comments' ) . '"><span class="screen-reader-text">' . __( 'Comments' ) . '</span></span>';
		}

		$posts_columns['date'] = __( 'Date' );

		if ( 'page' === $post_type ) {

			/**
			 * Filter the columns displayed in the Pages list table.
			 *
			 * @since 2.5.0
			 *
			 * @param array $post_columns An array of column names.
			 */
			$posts_columns = apply_filters( 'manage_pages_columns', $posts_columns );
		} else {

			/**
			 * Filter the columns displayed in the Posts list table.
			 *
			 * @since 1.5.0
			 *
			 * @param array $posts_columns An array of column names.
			 * @param string $post_type The post type slug.
			 */
			$posts_columns = apply_filters( 'manage_posts_columns', $posts_columns, $post_type );
		}

		/**
		 * Filter the columns displayed in the Posts list table for a specific post type.
		 *
		 * The dynamic portion of the hook name, `$post_type`, refers to the post type slug.
		 *
		 * @since 3.0.0
		 *
		 * @param array $post_columns An array of column names.
		 */
		return apply_filters( "manage_edit-{$post_type}_columns", $posts_columns );
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
	private function column_default( $post, $column_name ) {
		if ( 'title' == $column_name ) {
			$this->column_title( $post );

			return;
		}
		if ( 'author' == $column_name ) {
			$this->column_author( $post );

			return;
		}
		if ( 'comments' == $column_name ) {
			$this->column_comments( $post );

			return;
		}
		if ( 'date' == $column_name ) {
			$this->column_date( $post );

			return;
		}

		if ( 'categories' === $column_name ) {
			$taxonomy = 'category';
		} elseif ( 'tags' === $column_name ) {
			$taxonomy = 'post_tag';
		} elseif ( 0 === strpos( $column_name, 'taxonomy-' ) ) {
			$taxonomy = substr( $column_name, 9 );
		} else {
			$taxonomy = false;
		}
		if ( $taxonomy ) {
			$taxonomy_object = get_taxonomy( $taxonomy );
			$terms           = get_the_terms( $post->ID, $taxonomy );
			if ( is_array( $terms ) ) {
				$out = array();
				foreach ( $terms as $t ) {
					$posts_in_term_qv = array();
					if ( 'post' != $post->post_type ) {
						$posts_in_term_qv['post_type'] = $post->post_type;
					}
					if ( $taxonomy_object->query_var ) {
						$posts_in_term_qv[ $taxonomy_object->query_var ] = $t->slug;
					} else {
						$posts_in_term_qv['taxonomy'] = $taxonomy;
						$posts_in_term_qv['term']     = $t->slug;
					}

					$label = esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) );
					$href  = add_query_arg( admin_url( 'edit.php' ), $posts_in_term_qv );
					$out[] = "<a href='$href'>$label</a>";
				}
				/* translators: used between list items, there is a space after the comma */
				echo join( __( ', ' ), $out );
			} else {
				echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . $taxonomy_object->labels->no_terms . '</span>';
			}

			return;
		}

		if ( is_post_type_hierarchical( $post->post_type ) ) {

			/**
			 * Fires in each custom column on the Posts list table.
			 *
			 * This hook only fires if the current post type is hierarchical,
			 * such as pages.
			 *
			 * @since 2.5.0
			 *
			 * @param string $column_name The name of the column to display.
			 * @param int $post_id The current post ID.
			 */
			do_action( 'manage_pages_custom_column', $column_name, $post->ID );
		} else {

			/**
			 * Fires in each custom column in the Posts list table.
			 *
			 * This hook only fires if the current post type is non-hierarchical,
			 * such as posts.
			 *
			 * @since 1.5.0
			 *
			 * @param string $column_name The name of the column to display.
			 * @param int $post_id The current post ID.
			 */
			do_action( 'manage_posts_custom_column', $column_name, $post->ID );
		}

		/**
		 * Fires for each custom column of a specific post type in the Posts list table.
		 *
		 * The dynamic portion of the hook name, `$post->post_type`, refers to the post type.
		 *
		 * @since 3.1.0
		 *
		 * @param string $column_name The name of the column to display.
		 * @param int $post_id The current post ID.
		 */
		do_action( "manage_{$post->post_type}_posts_custom_column", $column_name, $post->ID );
	}

	/**
	 * Helper to create links to edit.php with params.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args URL parameters for the link.
	 * @param string $label Link text.
	 * @param string $class Optional. Class attribute. Default empty string.
	 *
	 * @return string The formatted link string.
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		$url        = add_query_arg( $args, 'edit.php' );
		$class_html = '';
		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);
		}

		return sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$label
		);
	}

	/**
	 * Display a comment count bubble
	 *
	 * @since 3.1.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $pending_comments Number of pending comments.
	 */
	protected function comments_bubble( $post_id, $pending_comments ) {
		$approved_comments        = get_comments_number();
		$approved_comments_number = number_format_i18n( $approved_comments );
		$pending_comments_number  = number_format_i18n( $pending_comments );
		$approved_only_phrase     = sprintf( _n( '%s comment', '%s comments', $approved_comments ), $approved_comments_number );
		$approved_phrase          = sprintf( _n( '%s approved comment', '%s approved comments', $approved_comments ), $approved_comments_number );
		$pending_phrase           = sprintf( _n( '%s pending comment', '%s pending comments', $pending_comments ), $pending_comments_number );
		// No comments at all.
		if ( ! $approved_comments && ! $pending_comments ) {
			printf( '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>',
				__( 'No comments' )
			);
			// Approved comments have different display depending on some conditions.
		} elseif ( $approved_comments ) {
			printf( '<a href="%s" class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
				esc_url( add_query_arg( array(
					'p'              => $post_id,
					'comment_status' => 'approved'
				), admin_url( 'edit-comments.php' ) ) ),
				$approved_comments_number,
				$pending_comments ? $approved_phrase : $approved_only_phrase
			);
		} else {
			printf( '<span class="post-com-count post-com-count-no-comments"><span class="comment-count comment-count-no-comments" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
				$approved_comments_number,
				$pending_comments ? __( 'No approved comments' ) : __( 'No comments' )
			);
		}
		if ( $pending_comments ) {
			printf( '<a href="%s" class="post-com-count post-com-count-pending"><span class="comment-count-pending" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
				esc_url( add_query_arg( array(
					'p'              => $post_id,
					'comment_status' => 'moderated'
				), admin_url( 'edit-comments.php' ) ) ),
				$pending_comments_number,
				$pending_phrase
			);
		} else {
			printf( '<span class="post-com-count post-com-count-pending post-com-count-no-pending"><span class="comment-count comment-count-no-pending" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
				$pending_comments_number,
				$approved_comments ? __( 'No pending comments' ) : __( 'No comments' )
			);
		}
	}

	/**
	 * Handles the title column output.
	 *
	 * @since 4.3.0
	 *
	 * @global string $mode List table view mode.
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
	public function column_title( $post ) {
		global $mode;
		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		if ( $can_edit_post && $post->post_status != 'trash' ) {
			$lock_holder = wp_check_post_lock( $post->ID );
			if ( $lock_holder ) {
				$lock_holder   = get_userdata( $lock_holder );
				$locked_avatar = get_avatar( $lock_holder->ID, 18 );
				$locked_text   = esc_html( sprintf( __( '%s is currently editing' ), $lock_holder->display_name ) );
			} else {
				$locked_avatar = $locked_text = '';
			}
			echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
		}
		$pad = str_repeat( '&#8212; ', 0 );
		echo "<strong>";
		$format = get_post_format( $post->ID );
		if ( $format ) {
			$label        = get_post_format_string( $format );
			$format_class = 'post-state-format post-format-icon post-format-' . $format;
			$format_args  = array(
				'post_format' => $format,
				'post_type'   => $post->post_type
			);
			echo $this->get_edit_link( $format_args, $label . ':', $format_class );
		}
		$title = _draft_or_post_title( $post );
		if ( $can_edit_post && $post->post_status != 'trash' ) {
			printf(
				'<a class="row-title" href="%s" aria-label="%s">%s%s</a>',
				get_edit_post_link( $post->ID ),
				/* translators: %s: post title */
				esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)' ), $title ) ),
				$pad,
				$title
			);
		} else {
			echo $pad . $title;
		}
		_post_states( $post );
		if ( isset( $parent_name ) ) {
			$post_type_object = get_post_type_object( $post->post_type );
			echo ' | ' . $post_type_object->labels->parent_item_colon . ' ' . esc_html( $parent_name );
		}
		echo "</strong>\n";
		if ( ! is_post_type_hierarchical( $post->post_type ) && 'excerpt' === $mode && current_user_can( 'read_post', $post->ID ) ) {
			echo esc_html( get_the_excerpt() );
		}
		get_inline_data( $post );
	}

	/**
	 * Handles the post date column output.
	 *
	 * @since 4.3.0
	 *
	 * @global string $mode List table view mode.
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
	public function column_date( $post ) {
		global $mode;
		if ( '0000-00-00 00:00:00' === $post->post_date ) {
			$t_time    = $h_time = __( 'Unpublished' );
			$time_diff = 0;
		} else {
			$t_time    = get_the_time( __( 'Y/m/d g:i:s a' ) );
			$m_time    = $post->post_date;
			$time      = get_post_time( 'G', true, $post );
			$time_diff = time() - $time;
			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
			} else {
				$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
			}
		}
		if ( 'publish' === $post->post_status ) {
			$status = __( 'Published' );
		} elseif ( 'future' === $post->post_status ) {
			if ( $time_diff > 0 ) {
				$status = '<strong class="error-message">' . __( 'Missed schedule' ) . '</strong>';
			} else {
				$status = __( 'Scheduled' );
			}
		} else {
			$status = __( 'Last Modified' );
		}
		/**
		 * Filters the status text of the post.
		 *
		 * @since 4.8.0
		 *
		 * @param string $status The status text.
		 * @param WP_Post $post Post object.
		 * @param string $column_name The column name.
		 * @param string $mode The list display mode ('excerpt' or 'list').
		 */
		$status = apply_filters( 'post_date_column_status', $status, $post, 'date', $mode );
		if ( $status ) {
			echo $status . '<br />';
		}
		if ( 'excerpt' === $mode ) {
			/**
			 * Filters the published time of the post.
			 *
			 * If `$mode` equals 'excerpt', the published time and date are both displayed.
			 * If `$mode` equals 'list' (default), the publish date is displayed, with the
			 * time and date together available as an abbreviation definition.
			 *
			 * @since 2.5.1
			 *
			 * @param string $t_time The published time.
			 * @param WP_Post $post Post object.
			 * @param string $column_name The column name.
			 * @param string $mode The list display mode ('excerpt' or 'list').
			 */
			echo apply_filters( 'post_date_column_time', $t_time, $post, 'date', $mode );
		} else {
			/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, 'date', $mode ) . '</abbr>';
		}
	}

	private $comment_pending_count = array();

	/**
	 * Handles the comments column output.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
	public function column_comments( $post ) {
		?>
        <div class="post-com-count-wrapper">
			<?php
			$pending_comments = isset( $this->comment_pending_count[ $post->ID ] ) ? $this->comment_pending_count[ $post->ID ] : 0;
			$this->comments_bubble( $post->ID, $pending_comments );
			?>
        </div>
		<?php
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
	public function column_author( $post ) {
		$args = array(
			'post_type' => $post->post_type,
			'author'    => get_the_author_meta( 'ID' )
		);
		echo $this->get_edit_link( $args, get_the_author() );
	}
}