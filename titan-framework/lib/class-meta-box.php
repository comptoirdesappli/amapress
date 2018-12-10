<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkMetaBox {

	private $defaultSettings = array(
		'name'               => '', // Name of the menu item
		// 'parent' => null, // slug of parent, if blank, then this is a top level menu
		'id'                 => '', // Unique ID of the menu item
		// 'capability' => 'manage_options', // User role
		// 'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only
		// 'position' => 100.01 // Menu position for top level menus only
		'post_type'          => 'page', // Post type, can be an array of post types
		'context'            => 'normal', // normal, advanced, or side
		'hide_custom_fields' => true, // If true, the custom fields box will not be shown
		'priority'           => 'high', // high, core, default, low
		'desc'               => '', // Description displayed below the title
	);

	public $settings;
	public $options = array();
	public $owner;
	public $postID; // Temporary holder for the current post ID being edited in the admin
	public $isUser;
	public $post_type;

	function __construct( $settings, $owner ) {
		$this->owner = $owner;

		if ( ! is_admin() ) {
			return;
		}

		$this->settings = array_merge( $this->defaultSettings, $settings );
		// $this->options = $options;
		if ( empty( $this->settings['name'] ) ) {
			$this->settings['name'] = __( 'More Options', TF_I18NDOMAIN );
		}

		if ( empty( $this->settings['id'] ) ) {
			$this->settings['id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
		}

		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'saveOptions' ), 10, 2 );
		add_action( 'edit_user_profile', array( $this, 'registerForUser' ) );
		add_action( 'edit_user_profile_update', array( $this, 'saveOptions' ) );
		add_action( 'personal_options_update', array( $this, 'saveOptions' ) );
		add_action( 'user_register', array( $this, 'saveOptions' ) );
		add_action( 'show_user_profile', array( $this, 'registerForUser' ) );
		add_action( 'user_new_form', array( $this, 'registerForUser' ) );

		// The action save_post isn't performed for attachments. edit_attachments
		// is a specific action only for attachments.
		add_action( 'edit_attachment', array( $this, 'saveOptions' ) );

		// Taxonomies

		$this->add_columns_for_type();
	}


	function add_columns_for_type() {
		$postTypes = array();

		// accomodate multiple post types
		if ( is_array( $this->settings['post_type'] ) ) {
			$postTypes = $this->settings['post_type'];
		} else {
			$postTypes[] = $this->settings['post_type'];
		}

		foreach ( $postTypes as $postType ) {
			if ( $postType == 'user' ) {
//                add_filter('posts_join', array($this, 'posts_join'));
//                add_filter('posts_orderby', array($this, 'posts_orderby'));
//                add_filter('posts_join', array($this, 'posts_join'));
//                add_filter('posts_orderby', array($this, 'posts_orderby'));
				add_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
				add_filter( 'request', array( $this, 'sort_metabox' ) );
				add_filter( 'manage_users_columns', array( $this, 'add_columns' ) );
				add_filter( 'manage_users_sortable_columns', array( $this, 'add_sort_columns' ) );
				add_filter( 'manage_users_custom_column', array( $this, 'column_user_display' ), 15, 3 );
				add_filter( 'manage_users_custom_column_export', array( $this, 'column_user_export' ), 15, 3 );
			} else {
				add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
				add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
				add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
				add_filter( 'request', array( $this, 'sort_metabox' ) );
				add_filter( 'manage_edit-' . $postType . '_columns', array( $this, 'add_columns' ) );
				add_filter( 'manage_edit-' . $postType . '_sortable_columns', array( $this, 'add_sort_columns' ) );
				add_filter( 'manage_' . $postType . '_posts_custom_column', array( $this, 'column_display' ), 10, 2 );
				add_filter( 'manage_' . $postType . '_posts_custom_column_export', array(
					$this,
					'column_export'
				), 10, 3 );
			}
		}
	}

	function column_display( $colname, $post_id ) {
		$exists = false;
		/** @var TitanFrameworkOption $opt */
		$opt = null;
		/** @var TitanFrameworkOption $option */
		foreach ( $this->options as $option ) {
			if ( $option->getID() == $colname ) {
				$exists = true;
				$opt    = $option;
			}
			//$columns[$option->getID()] = $option->getName();
		}
		if ( $exists ) {
			$custom_column = isset( $opt->settings['custom_column'] ) ? $opt->settings['custom_column'] : null;
			if ( is_callable( $custom_column, false ) ) {
				call_user_func( $custom_column, $this, $post_id );
			} else {
				$opt->columnDisplayValue( $post_id );
			}
		}
	}

	function column_user_display( $v = '', $column_name, $user_id ) {
		$exists = false;
		/** @var TitanFrameworkOption $opt */
		$opt = null;
		/** @var TitanFrameworkOption $option */
		foreach ( $this->options as $option ) {
			if ( $option->getID() == $column_name ) {
				$exists             = true;
				$opt                = $option;
				$opt->owner->isUser = true;
			}
			//$columns[$option->getID()] = $option->getName();
		}
		if ( $exists ) {
			ob_start();
			$custom_column = isset( $opt->settings['custom_column'] ) ? $opt->settings['custom_column'] : null;
			if ( is_callable( $custom_column, false ) ) {
				call_user_func( $custom_column, $this, $user_id );
			} else {
				$opt->columnDisplayValue( $user_id );
			}
			$ret = ob_get_contents();
			ob_end_clean();

			return $ret;
		}

		return $v;
	}

	function column_export( $v = '', $colname, $cptid ) {
		$exists = false;
		/** @var TitanFrameworkOption $opt */
		$opt = null;
		/** @var TitanFrameworkOption $option */
		foreach ( $this->options as $option ) {
			if ( $option->getID() == $colname ) {
				$exists = true;
				$opt    = $option;
			}
			//$columns[$option->getID()] = $option->getName();
		}
		if ( $exists ) {
			ob_start();
			$custom_export = isset( $opt->settings['custom_export'] ) ? $opt->settings['custom_export'] : null;
			$custom_column = isset( $opt->settings['custom_column'] ) ? $opt->settings['custom_column'] : null;
			if ( is_callable( $custom_export, false ) ) {
				call_user_func( $custom_export, $this, $cptid );
			} else if ( is_callable( $custom_column, false ) ) {
				call_user_func( $custom_column, $this, $cptid );
			} else {
				$opt->columnExportValue( $cptid );
			}
			$ret = ob_get_contents();
			ob_end_clean();

			return $ret;
		} else {
			return $v;
		}
	}

	function column_user_export( $v = '', $column_name, $user_id ) {
		$exists = false;
		/** @var TitanFrameworkOption $opt */
		$opt = null;
		/** @var TitanFrameworkOption $option */
		foreach ( $this->options as $option ) {
			if ( $option->getID() == $column_name ) {
				$exists             = true;
				$opt                = $option;
				$opt->owner->isUser = true;
			}
		}
		if ( $exists ) {
			ob_start();
			$custom_export = isset( $opt->settings['custom_export'] ) ? $opt->settings['custom_export'] : null;
			$custom_column = isset( $opt->settings['custom_column'] ) ? $opt->settings['custom_column'] : null;
			if ( is_callable( $custom_export, false ) ) {
				call_user_func( $custom_export, $this, $user_id );
			} else if ( is_callable( $custom_column, false ) ) {
				call_user_func( $custom_column, $this, $user_id );
			} else {
				$opt->columnExportValue( $user_id );
			}
			$ret = ob_get_contents();
			ob_end_clean();

			return $ret;
		}

		return $v;
	}

	/** @var TitanFrameworkOption $custom_orderby_options * */
	private static $custom_orderby_options = null;

	function pre_user_query( WP_User_Query $query ) {
		global $wpdb;

		if ( ! empty( $query->query_vars['custom_orderby'] ) ) {
			/** @var TitanFrameworkOption $option */
			foreach ( $this->options as $option ) {
				if ( $option->getID() == $query->query_vars['custom_orderby'] ) {
					self::$custom_orderby_options = $option;
//                    var_dump($option);
					break;
				}
			}
		}
		if ( self::$custom_orderby_options && self::$custom_orderby_options->owner == $this ) {
			$query->query_orderby = self::$custom_orderby_options->getSQLOrderBy( $query->query_orderby, 'user' );
		}
		if ( self::$custom_orderby_options && self::$custom_orderby_options->owner == $this ) {
			$query->query_from = self::$custom_orderby_options->appendSQLJoinForSorting( $query->query_from, 'user' );
//            var_dump($query->query_from);
		}
		if ( ! empty( $query->query_vars['search'] ) ) {
			$join = $query->query_from;
			/** @var TitanFrameworkOption $option */
			foreach ( $this->options as $option ) {
				$join = $option->appendSQLJoinForSearching( $join, 'user' );
			}
			$query->query_from = $join;

//            var_dump($join);
//            die();

			$likes = array();
			/** @var TitanFrameworkOption $option */
			foreach ( $this->options as $option ) {
				$like = $option->getSQLSearchLike( 'user' );
				if ( ! empty( $like ) ) {
					$likes[] = $like;
				}
			}
			if ( ! empty( $likes ) ) {
				$query->query_where = preg_replace(
					'/\(user_\S+\s+LIKE\s*(\'[^\']+\').+/',
					"($wpdb->users.user_login LIKE $1 OR $wpdb->users.user_url LIKE $1 OR $wpdb->users.user_email LIKE $1 OR $wpdb->users.user_nicename LIKE $1 OR $wpdb->users.display_name LIKE $1 OR (" . implode( ' OR ', $likes ) . '))', $query->query_where );
			}
			$query->query_orderby = preg_replace( '/ORDER\s+BY\s+([^\.]+)\s+/', "ORDER BY $wpdb->users.$1 ", $query->query_orderby );
//            var_dump("SELECT $query->query_fields $query->query_from $query->query_where $query->query_orderby");
//            die();
		}
//        if (is_main_query())
//            var_dump($query->query_vars['s']);
	}

	/**
	 * @param WP_Query $query
	 *
	 * @return bool
	 */
	private function isQueryOfThesePostTypes( $query ) {
		$postTypes = array();
		// accomodate multiple post types
		if ( is_array( $this->settings['post_type'] ) ) {
			$postTypes = $this->settings['post_type'];
		} else {
			$postTypes[] = $this->settings['post_type'];
		}
		$postTypes        = array_map( 'amapress_unsimplify_post_type', $postTypes );
		$query_post_types = isset( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : array();
		if ( ! is_array( $query_post_types ) ) {
			$query_post_types = array( $query_post_types );
		}
//            $is_post_type = false;
//            foreach ($postTypes as $pt) {
//                $is_post_type = $is_post_type || is
//            }
		return count( array_intersect( $query_post_types, $postTypes ) ) > 0;
	}

	/**
	 * @param string $join
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	function posts_join( $join, $query ) {
		if ( self::$custom_orderby_options && self::$custom_orderby_options->owner == $this ) {
			$join = self::$custom_orderby_options->appendSQLJoinForSorting( $join, 'post' );
		}
		if ( $query->is_search() ) {
			if ( $this->isQueryOfThesePostTypes( $query ) ) {
				/** @var TitanFrameworkOption $option */
				foreach ( $this->options as $option ) {
					$join = $option->appendSQLJoinForSearching( $join, 'post' );
				}
			}
		}

//        var_dump($join);
		return $join;
	}

	/**
	 * @param string $where
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	function posts_where( $where, $query ) {
		if ( $query->is_search() ) {
			if ( $this->isQueryOfThesePostTypes( $query ) ) {
				$likes = array();
				/** @var TitanFrameworkOption $option */
				foreach ( $this->options as $option ) {
					$like = $option->getSQLSearchLike( 'post' );
					if ( ! empty( $like ) ) {
						$likes[] = $like;
					}
				}
				if ( ! empty( $likes ) ) {
					global $wpdb;
					$where = preg_replace(
						'/\(\s*' . $wpdb->posts . '.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/',
						'(' . $wpdb->posts . '.post_title LIKE $1) OR (' . implode( ' OR ', $likes ) . ')', $where );
				}
			}
		}
//        var_dump($where);
//        die();
		return $where;
	}

	function posts_orderby( $order ) {
		if ( self::$custom_orderby_options && self::$custom_orderby_options->owner == $this ) {
			$order = self::$custom_orderby_options->getSQLOrderBy( $order, 'post' );
		}

		return $order;
	}

	function sort_metabox( $vars ) {
		if ( ! empty( $vars['post_type'] ) ) {
			$pt = AmapressEntities::getPostType( amapress_simplify_post_type( $vars['post_type'] ) );
			if ( ! empty( $pt['default_orderby'] ) ) {
				if ( empty( $vars['orderby'] ) ) {
					$orderby         = $pt['default_orderby'];
					$vars['orderby'] = $orderby;
				}
			}
			if ( ! empty( $pt['default_order'] ) ) {
				if ( empty( $vars['order'] ) ) {
					$vars['order'] = $pt['default_order'];
				}
			}
		}

//        var_dump($vars);
		if ( array_key_exists( 'orderby', $vars ) ) {
//			$exists = false;
//			$is_num = false;
//			$is_custom = false;
			/** @var TitanFrameworkOption $option */
			foreach ( $this->options as $option ) {
				if ( $option->getID() == $vars['orderby'] ) {
//					$exists                       = true;
//					$is_num                       = $option->isNumericForSort();
					self::$custom_orderby_options = $option;
					break;
				}
			}
//			if ( $exists ) {
//				$vars['meta_key'] = $vars['orderby'];
//				$vars['orderby']  = $is_num ? 'meta_value_num' : 'meta_value';
//			}
		}

		return $vars;
	}

	function add_columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : null;
		unset( $columns['date'] );

//        $postTypes = array();
//        // accomodate multiple post types
//        if (is_array($this->settings['post_type'])) {
//            $postTypes = $this->settings['post_type'];
//        } else {
//            $postTypes[] = $this->settings['post_type'];
//        }

		/** @var TitanFrameworkOption $option */
		foreach ( $this->options as $option ) {
			if ( ! empty( $option->settings['capability'] ) && ! current_user_can( $option->settings['capability'] ) ) {
				continue;
			}

			if ( $option->settings['show_column'] != false ) {
				$columns[ $option->getID() ] = ! empty( $option->settings['name'] ) ? $option->settings['name'] : $option->settings['desc'];
			}
		}

		if ( $date && isset( $this->settings['show_date_column'] ) && $this->settings['show_date_column'] === true ) {
			$columns['date'] = $date;
		}


		return $columns;
	}

	function add_sort_columns( $columns ) {
		/** @var TitanFrameworkOption $option */
		foreach ( $this->options as $option ) {
			if ( ! empty( $option->settings['capability'] ) && ! current_user_can( $option->settings['capability'] ) ) {
				continue;
			}

			if ( $option->settings['sort_column'] != false ) {
				$columns[ $option->getID() ] = $option->getID();
			}
		}

		return $columns;
	}

	public
	function registerForUser(
		$user
	) {
		if ( ! empty( $this->settings['capability'] ) && ! current_user_can( $this->settings['capability'] ) ) {
			return;
		}
		if ( $user == 'add-existing-user' ) {
			return;
		}

		$postTypes = array();

		// accomodate multiple post types
		if ( is_array( $this->settings['post_type'] ) ) {
			$postTypes = $this->settings['post_type'];
		} else {
			$postTypes[] = $this->settings['post_type'];
		}

		if ( ! in_array( 'user', $postTypes ) ) {
			return;
		}

		$this->postID = is_a( $user, 'WP_User' ) ? $user->ID : 0;
		$this->isUser = true;

		if ( ! empty( $this->settings['desc'] ) ) {
			?><p class='description'><?php echo $this->settings['desc'] ?></p><?php
		}

		?>
        <table class="form-table tf-form-table">
            <tbody>
			<?php
			/** @var TitanFrameworkOption $option */
			foreach ( $this->options as $option ) {
				if ( $option->isHidden() ) {
					continue;
				}
				if ( $option->isReadonly() ) {
					$option->columnDisplayValue( $this->postID );
				} else {
					$option->display_with_check();
				}
			}
			?>
            </tbody>
        </table>
		<?php
	}

	public
	function register() {
		$postTypes = array();

		// accomodate multiple post types
		if ( is_array( $this->settings['post_type'] ) ) {
			$postTypes = $this->settings['post_type'];
		} else {
			$postTypes[] = $this->settings['post_type'];
		}

		foreach ( $postTypes as $postType ) {
//            if ($postType == 'page')
//                wp_die('page');
			// Hide the custom fields
			if ( $this->settings['hide_custom_fields'] ) {
				remove_meta_box( 'postcustom', $postType, 'normal' );
			}

			add_meta_box(
				$this->settings['id'],
				$this->settings['name'],
				array( $this, 'display' ),
				$postType,
				$this->settings['context'],
				$this->settings['priority']
			);
		}
	}

	public
	function display(
		$post
	) {
		if ( ! empty( $this->settings['capability'] ) && ! current_user_can( $this->settings['capability'] ) ) {
			return;
		}

		$this->postID = $post->ID;

		wp_nonce_field( $this->settings['id'], TF . '_' . $this->settings['id'] . '_nonce' );

		if ( ! empty( $this->settings['desc'] ) ) {
			?><p class='description'><?php echo $this->settings['desc'] ?></p><?php
		}

		?>
        <table class="form-table tf-form-table">
            <tbody>
			<?php
			/** @var TitanFrameworkOption $option */
			foreach ( $this->options as $option ) {
				if ( $option->isHidden() ) {
					continue;
				}
				if ( $option->isReadonly() ) {
					$option->echoReadonly();
				} else {
					$option->display_with_check();
				}
			}
			?>
            </tbody>
        </table>
		<?php
	}

//    private $ids = array();
	public
	function saveOptions(
		$postID, $post = null
	) {
		$postTypes = array();

//		if ($option->getID() == 'amapress_adhesion_adherent')


		// accomodate multiple post types
		if ( is_array( $this->settings['post_type'] ) ) {
			$postTypes = $this->settings['post_type'];
		} else {
			$postTypes[] = $this->settings['post_type'];
		}

		$this->isUser = in_array( 'user', $postTypes );

		if ( ! $this->isUser ) {
			// Verify nonces and other stuff
			if ( ! $this->verifySecurity( $postID, $post ) ) {
				return;
			}
		}

		/** This action is documented in class-admin-page.php */
		$namespace = $this->owner->optionNamespace;
		do_action( "tf_pre_save_options_{$namespace}", $this, $postID );

		// Save the options one by one
		/** @var TitanFrameworkOption $option */
		foreach ( $this->options as $option ) {
			if ( empty( $option->settings['id'] ) || $option->isReadonly() || $option->isHidden() ) {
				continue;
			}

			if ( ! empty( $_POST[ $option->getID() ] ) ) {
				$value = $_POST[ $option->getID() ];
			} else {
				$value = '';
			}

			if ( ! $option->customSave( $postID ) ) {
				$option->setValue( $value, $postID );
			}
		}

		do_action( "tf_post_save_options_{$namespace}", $this, $postID );
	}

	private function verifySecurity(
		$postID, WP_Post $post = null
	) {
		// Verify edit submission
		if ( empty( $_POST ) ) {
			return false;
		}
		if ( empty( $_POST['post_type'] ) ) {
			return false;
		}

		// Don't save on revisions
		if ( wp_is_post_revision( $postID ) ) {
			return false;
		}

		// Don't save on autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Verify that we are editing the correct post type
		if ( is_array( $this->settings['post_type'] ) ) {
			if ( ! in_array( $_POST['post_type'], $this->settings['post_type'] ) ) {
				return false;
			}
			if ( null !== $post && ! in_array( $post->post_type, $this->settings['post_type'] ) ) {
				return false;
			}
		} else {
			if ( $_POST['post_type'] != $this->settings['post_type'] ) {
				return false;
			}
			if ( null !== $post && $post->post_type != $this->settings['post_type'] ) {
				return false;
			}
		}


		// Verify our nonce
		if ( ! check_admin_referer( $this->settings['id'], TF . '_' . $this->settings['id'] . '_nonce' ) ) {
			return false;
		}

//        $post_types = $this->settings['post_type'];
//        if (!is_array($post_types)) $post_types = array($post_types);
		// Check permissions
//        if (is_array()) {

//        if (in_array('page', $post_types)) {
		if ( empty( $post ) ) {
			return false;
		}

		$the_post_type = get_post_type_object( $post->post_type );
		if ( empty( $the_post_type ) || ! current_user_can( $the_post_type->cap->edit_posts, $postID ) ) {
			return false;
		}
//        } else if (!current_user_can('edit_post', $postID)) {
//            return false;
//        }
//        } else {
//            if ($this->settings['post_type'] == 'page') {
//                if (!current_user_can('edit_page', $postID)) {
//                    return false;
//                }
//            } else if (!current_user_can('edit_post', $postID)) {
//                return false;
//            }
//        }

		return true;
	}

	public
	function createOption(
		$settings
	) {
		if ( ! apply_filters( 'tf_create_option_continue_' . $this->owner->optionNamespace, true, $settings ) ) {
			return null;
		}
//        if (!empty($settings['capability']) && !current_user_can($settings['capability'])) return null;

		$obj             = TitanFrameworkOption::factory( $settings, $this );
		$this->options[] = $obj;

		do_action( 'tf_create_option_' . $this->owner->optionNamespace, $obj );

		return $obj;
	}

	public
	function generateClass(
		$internal_post_type, $options = null
	) {
		$pt        = $this->settings['post_type'];
		$post_type = amapress_simplify_post_type( is_array( $this->settings['post_type'] ) ? array_shift( $pt ) : $this->settings['post_type'] );

		return 'class ' . ucfirst( $this->owner->optionNamespace ) . ucfirst( $post_type ) . ' extends TitanEntity {
			const INTERNAL_POST_TYPE = \'' . $internal_post_type . '\';
			const POST_TYPE = \'' . $post_type . '\';

			function __construct($post_id) {
				parent::__construct($post_id);
			}

			' . implode( "\n", array_map( function ( TitanFrameworkOption $o ) {
				return $o->generateMember();
			}, $options ? $options : $this->options ) ) . '
		}';
	}
}