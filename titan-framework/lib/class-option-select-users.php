<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSelectUsers extends TitanFrameworkOptionSelect {

	public $defaultSecondarySettings = array(
		'default'           => '', // show this when blank
		'role'              => '',
		'autocomplete'      => false,
		'tags'              => false,
		'autoselect_single' => false,
		'placeholder'       => '',
		'orderby'           => 'post_date',
		'order'             => 'DESC',
		'custom_csv_sample' => null,
		'refresh_button'    => true,
		'wrap_edit'         => true,
	);

	function isReadonly() {
		if ( $this->isOnMeta() && ! empty( $this->settings['required'] ) && $this->settings['required'] === true ) {
			if ( isset( $_POST[ $this->getID() ] ) || ( empty( $this->getValue() ) && empty( $_POST ) ) ) {
				return false;
			}
		}

		return parent::isReadonly();
	}

	public function getSamplesForCSV( $arg = null ) {
		if ( is_callable( $this->settings['custom_csv_sample'], false ) ) {
			return call_user_func( $this->settings['custom_csv_sample'], $this, $arg );
		} else if ( is_array( $this->settings['custom_csv_sample'] ) ) {
			return $this->settings['custom_csv_sample'];
		} else {
			return array(
				'Login',
				'Prénom Nom',
				'Nom Prénom',
				'Nom',
				'Email'
			);
		}
	}

	protected function getEditLink( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		return admin_url( "user-edit.php?user_id=$value" );
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
			" LEFT JOIN $wpdb->users as amp_sort ON amp_pm.meta_value = amp_sort.ID ";
	}

	public function getSQLOrderBy( $order, $type ) {
		return ( 'user' == $type ? 'ORDER BY' : '' ) . ' amp_sort.user_nicename ' . ( strpos( $order, ' DESC' ) === false ? 'ASC' : 'DESC' );
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
		$user_table_alias = "amp_srch_user_{$this->getSqlID()}";

		return
			$join .
			$wpdb->prepare( " LEFT JOIN $meta_table as $meta_table_alias ON $main_table.ID = $meta_table_alias.$id_column and $meta_table_alias.meta_key=%s ", $this->getID() ) .
			" LEFT JOIN $wpdb->users as $user_table_alias ON $meta_table_alias.meta_value = $user_table_alias.ID ";
	}

	public function getSQLSearchLike( $type ) {
		if ( ! $this->settings['searchable'] ) {
			return null;
		}
		$user_table_alias = "amp_srch_user_{$this->getSqlID()}";

		return "($user_table_alias.display_name LIKE $1 OR $user_table_alias.user_email LIKE $1 OR $user_table_alias.user_login LIKE $1 OR $user_table_alias.user_nicename LIKE $1)";
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
//			$post = get_user_by( 'ID', intval( $v ) );
//			if ( $post ) {
//				$dn               = "{$post->display_name} ({$post->user_email})";
//				$titles[] = $this->wrapEditLink( $v,
//					apply_filters( "tf_select_users_title", $dn, $post, $this ) );
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
		$args = array(
			'autocomplete' => $this->settings['autocomplete'],
			'number'       => - 1,
			'orderby'      => $this->settings['orderby'],
			'order'        => $this->settings['order'],
		);
		if ( is_array( $this->settings['role'] ) ) {
			$args['role__in'] = $this->settings['role'];
		} else {
			$args['role'] = $this->settings['role'];
		}

		$args = apply_filters( "tf_select_users_query_args", $args, $this );
		$args = apply_filters( "tf_{$this->getID()}_query_args", $args );

		$args['fields'] = 'all_with_meta';
		$posts          = get_users_cached( $args );

		$autocomplete = $args['autocomplete'] === true || ( is_int( $args['autocomplete'] ) && count( $posts ) > $args['autocomplete'] );
		$placeholder  = empty( $this->settings['placeholder'] ) ? '— ' . __( 'Select', TF_I18NDOMAIN ) . ' —' : $this->settings['placeholder'];

		$ret = array();
		if ( count( $posts ) > 1 || ! $this->settings['autoselect_single'] ) {
			$ret[''] = $autocomplete ? '' : $placeholder;
		}
		foreach ( $posts as $post ) {
			$dn               = "{$post->display_name} ({$post->user_email})";
			$ret[ $post->ID ] = apply_filters( "tf_select_users_title", $dn, $post, $this );
		}

		return $ret;
	}

	public function getArchived( $id ) {
		$user = get_user_by( 'ID', $id );
		if ( ! $user ) {
			return "Archivé $id";
		}
		$dn = "{$user->display_name} ({$user->user_email})";

		return apply_filters( "tf_select_users_title", $dn, $user, $this );
	}

	public function generateMember() {
		$mn = strtolower( $this->getMemberName() );

		return '
		private $' . $mn . ' = null;
		public function get' . $this->getMemberName() . '() {
			$this->ensure_init();
			$v = $this->custom[\'' . $this->getID() . '\'];
			if (empty($v)) return null;
			if ($this->' . $mn . ' == null) $this->' . $mn . ' = AmapressUser::getBy($v);
			return $this->' . $mn . ';
		}
		public function set' . $this->getMemberName() . '($value) {
			update_post_meta($this->post->ID, \'' . $this->getID() . '\', $value);
			$this->' . $mn . ' = null;
		}
		';
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionSelectUsersControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'required'    => $this->settings['required'],
			'role'        => $this->settings['role'],
			'orderby'     => $this->settings['orderby'],
			'order'       => $this->settings['order'],
			'priority'    => $priority,
		) ) );
	}
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectUsersControl', 1 );
function registerTitanFrameworkOptionSelectUsersControl() {
	class TitanFrameworkOptionSelectUsersControl extends WP_Customize_Control {
		public $description;
		public $orderby;
		public $order;
		public $required;
		public $role;

		public function render_content() {
			$args = array(
				'role'    => $this->role,
				'orderby' => $this->orderby,
				'order'   => $this->order,
			);

			$posts = get_users( $args );

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
