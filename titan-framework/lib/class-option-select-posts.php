<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSelectPosts extends TitanFrameworkOptionSelect {

	public $defaultSecondarySettings = array(
		'default'           => '', // show this when blank
		'post_type'         => 'post',
		'num'               => - 1,
		'post_status'       => 'publish',
		'autocomplete'      => false,
		'tags'              => false,
		'autoselect_single' => false,
		'placeholder'       => '',
		'orderby'           => 'post_date',
		'order'             => 'DESC',
		'wrap_edit'         => true,
		'custom_csv_sample' => null,
		'custom_fetch'      => null,
		'refresh_button'    => true,
	);

	public function getSamplesForCSV( $arg = null ) {
		if ( is_callable( $this->settings['custom_csv_sample'], false ) ) {
			return call_user_func( $this->settings['custom_csv_sample'], $this, $arg );
		} else if ( is_array( $this->settings['custom_csv_sample'] ) ) {
			return $this->settings['custom_csv_sample'];
		} else {
			return $this->fetchOptionsWithCache();
		}
	}

	function isReadonly() {
		if ( $this->isOnMeta() && ! empty( $this->settings['required'] ) && $this->settings['required'] === true ) {
			if ( isset( $_POST[ $this->getID() ] ) || ( empty( $this->getValue() ) && empty( $_POST ) ) ) {
				return false;
			}
		}

		return parent::isReadonly();
	}

	public function getArchived( $id ) {
		$post = get_post( $id );
		if ( ! $post ) {
			return sprintf( __( "Archivé %s", 'amapress' ), $id );
		}

		return apply_filters( "tf_select_posts_title", $post->post_title, $post, $this );
	}

	protected function getEditLink( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		return admin_url( "post.php?post=$value&action=edit" );
	}

//	public function columnDisplayValue( $post_id ) {
//		$values = $this->getValue( $post_id );
//		if ( ! is_array( $values ) ) {
//			$values = array( $values );
//		}
//
//		$titles      = array();
//		$used_values = array();
//
//		foreach ( $values as $v ) {
//			$post = get_post( intval( $v ) );
//			if ( $post ) {
//				$titles[] = $this->wrapEditLink( $v,
//					apply_filters( "tf_select_posts_title", $post->post_title, $post, $this ) );
//			} else {
//				$titles[] = "Archivé $v";
//			}
//		}
//
//		foreach ( $values as $v ) {
//			if ( in_array( $v, $used_values ) ) {
//				continue;
//			}
//			$titles[] = "Archivé $v";
//		}
//
//		echo implode( ',', $titles );
//	}


	public function fetchOptions() {
//	    if (isset($this->settings['custom_fetch']) && is_callable($this->settings['custom_fetch'], false)) {
//	        return call_user_func($this->settings['custom_fetch'], $this);
//        }

		$args = array(
			'post_type'      => $this->settings['post_type'],
			'posts_per_page' => $this->settings['num'],
			'post_status'    => $this->settings['post_status'],
			'autocomplete'   => $this->settings['autocomplete'],
			'orderby'        => $this->settings['orderby'],
			'order'          => $this->settings['order'],
		);

		$args         = apply_filters( "tf_select_posts_query_args", $args, $this );
		$args         = apply_filters( "tf_{$this->getID()}_query_args", $args );
		$posts        = get_posts( $args );
		$autocomplete = $args['autocomplete'] === true || ( is_int( $args['autocomplete'] ) && count( $posts ) > $args['autocomplete'] );
		$placeholder  = empty( $this->settings['placeholder'] ) ? '— ' . __( 'Select', TF_I18NDOMAIN ) . ' —' : $this->settings['placeholder'];

		$ret = array();
		if ( count( $posts ) > 1 || ! $this->settings['autoselect_single'] ) {
			$ret[''] = $autocomplete ? '' : $placeholder;
		}
		foreach ( $posts as $post ) {
			$ret[ $post->ID ] = apply_filters( "tf_select_posts_title", $post->post_title, $post, $this );
		}

		return $ret;
	}

	public function appendSQLJoinForSorting( $join, $type ) {
		global $wpdb;
		if ( 'user' == $type ) {
			$main_table = $wpdb->users;
			$meta_table = $wpdb->usermeta;
			$id_column  = 'user_id';
		} else {
			$main_table = $wpdb->posts;
			$meta_table = $wpdb->postmeta;
			$id_column  = 'post_id';
		}

		return
			$join .
			$wpdb->prepare( " LEFT JOIN $meta_table as amp_pm ON $main_table.ID = amp_pm.$id_column and amp_pm.meta_key=%s ", $this->getID() ) .
			" LEFT JOIN $wpdb->posts as amp_sort ON amp_pm.meta_value = amp_sort.ID ";
	}

	public function getSQLOrderBy( $order, $type ) {
//        global $wp_query;
//        return ' amp_sort.post_title ' . ($wp_query->query_vars['order']);
		return ( 'user' == $type ? 'ORDER BY' : '' ) . ' amp_sort.post_title ' . ( strpos( $order, ' DESC' ) === false ? 'ASC' : 'DESC' );
	}

	public function appendSQLJoinForSearching( $join, $type ) {
		if ( ! $this->settings['searchable'] ) {
			return $join;
		}

		global $wpdb;
		if ( 'user' == $type ) {
			$main_table = $wpdb->users;
			$meta_table = $wpdb->usermeta;
			$id_column  = 'user_id';
		} else {
			$main_table = $wpdb->posts;
			$meta_table = $wpdb->postmeta;
			$id_column  = 'post_id';
		}

		$meta_table_alias = "amp_srch_meta_{$this->getSqlID()}";
		$post_table_alias = "amp_srch_post_{$this->getSqlID()}";

		return
			$join .
			$wpdb->prepare( " LEFT JOIN $meta_table as $meta_table_alias ON $main_table.ID = $meta_table_alias.$id_column and $meta_table_alias.meta_key=%s ", $this->getID() ) .
			" LEFT JOIN $wpdb->posts as $post_table_alias ON $meta_table_alias.meta_value = $post_table_alias.ID ";
	}

	public function getSQLSearchLike( $type ) {
		if ( ! $this->settings['searchable'] ) {
			return null;
		}

		$post_table_alias = "amp_srch_post_{$this->getSqlID()}";

		return "($post_table_alias.post_title LIKE $1)";
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionSelectPostsControl( $wp_customize, $this->getID(), array(
			'label'          => $this->settings['name'],
			'section'        => $section->settings['id'],
			'settings'       => $this->getID(),
			'description'    => $this->settings['desc'],
			'post_type'      => $this->settings['post_type'],
			'posts_per_page' => $this->settings['num'],
			'post_status'    => $this->settings['post_status'],
			'required'       => $this->settings['required'],
			'orderby'        => $this->settings['orderby'],
			'order'          => $this->settings['order'],
			'priority'       => $priority,
		) ) );
	}
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectPostsControl', 1 );
function registerTitanFrameworkOptionSelectPostsControl() {
	class TitanFrameworkOptionSelectPostsControl extends WP_Customize_Control {
		public $description;
		public $post_type;
		public $num;
		public $post_status;
		public $orderby;
		public $order;
		public $required;

		public function render_content() {
			$args = array(
				'post_type'      => $this->post_type,
				'posts_per_page' => $this->num,
				'post_status'    => $this->post_status,
				'orderby'        => $this->orderby,
				'order'          => $this->order,
			);

			$posts = get_posts( $args );

			?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <select <?php $this->link(); ?> class="<?php echo( $this->required ? 'required' : '' ) ?>">
					<?php
					// The default value (nothing is selected)
					printf( "<option value='%s' %s>%s</option>",
						'0',
						selected( $this->value(), '0', false ),
						'— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
					);

					// Print all the other pages
					foreach ( $posts as $post ) {
						printf( "<option value='%s' %s>%s</option>",
							esc_attr( $post->ID ),
							selected( $this->value(), $post->ID, false ),
							$post->post_title
						);
					}
					?>
                </select>
            </label>
			<?php
			echo "<p class='description'>{$this->description}</p>";
		}
	}
}
