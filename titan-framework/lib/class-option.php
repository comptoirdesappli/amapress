<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * People can extend this class to create their own options
 */

class TitanFrameworkOption {
	const TYPE_META = 'meta';
	const TYPE_ADMIN = 'option';
	const TYPE_CUSTOMIZER = 'customizer';
	public $settings;
	public $type; // One of the TYPE_* constants above
	public $owner;
	private static $rowIndex = 0;

	public static $csvImportExcludedTypes = [
		'action-buttons',
		'ajax-button',
		'edd-licence',
		'gallery',
		'heading',
		'iframe',
		'note',
		'post-title',
		'related-posts',
		'related-users',
		'save',
		'separator',
		'show-posts',
		'show-users',
		'upload'
	];

	/**
	 * Default settings across all options
	 * @var array
	 */
	private static $defaultSettings = array(
		'type'        => 'text',
		/**
		 * The name of the option, for display purposes only.
		 *
		 * @since 1.0
		 * @var string
		 */
		'name'        => '',
		/**
		 * The description to display together with this option.
		 *
		 * @since 1.0
		 * @var string
		 */
		'desc'        => '',
		/**
		 * A unique ID for this option. This ID will be used to get the value for this option.
		 *
		 * @since 1.0
		 * @var string
		 */
		'id'          => '',
		'input_name'  => '',
		/**
		 * (Optional) The default value for this option.
		 *
		 * @since 1.0
		 * @var mixed
		 */
		'default'     => '',
		/**
		 * (Optional) jQuery code that updates something in your site in the live preview. Only used when the option is placed in a theme customizer section.
		 *
		 * @since 1.0
		 * @var string
		 * @see http://www.titanframework.net/livepreview-parameter
		 */
		'livepreview' => '', // jQuery script to update something in the site. For theme customizer only
		/**
		 * (Optional) CSS rules to be used with this option. Only used when the option is placed in an admin page / panel or a theme customizer section.
		 * @since 1.0
		 * @var string
		 * @see http://www.titanframework.net/generate-css-automatically-for-your-options/
		 */
		'css'         => '',
		/**
		 * (Optional) If true, the option will not be displayed, but will still be accessible using `getOption`. This is helpful for deprecating old settings, while still making your project backward compatible.
		 * @since 1.8
		 * @var bool
		 */
		'hidden'      => false,
		/**
		 * (Optional) The transport parameter in the Customizer is automatically set. Use this to override the transport value. Value can be blank, 'refresh' or 'postMessage'
		 * @since 1.9.3
		 * @var string
		 */
		'transport'   => '',
		'example'     => '', // An example value for this field, will be displayed in a <code>

		'show_column'   => true,
		'sort_column'   => true,
		'required'      => false,
		'visible_class' => '',
		'readonly'      => false,
		'show_on'       => 'new-edit',
		'top_filter'    => false,
		'bottom_filter' => false,
		'searchable'    => false,
		'custom_column' => null,
		'custom_export' => null,
	);
	/**
	 * Default settings specific for this option. This is overridden by each option class
	 * @var array
	 */
	public $defaultSecondarySettings = array();

	public static function factory( $settings, $owner ) {
		//$settings = array_merge( self::$defaultSettings, $settings );
		$className = 'TitanFrameworkOption' . str_replace( ' ', '', ucwords( str_replace( '-', ' ', $settings['type'] ) ) );
		// assume all the classes are already required
		if ( ! class_exists( $className ) && ! class_exists( $settings['type'] ) ) {
			TitanFramework::displayFrameworkError(
				sprintf( __( 'Option type or extended class %s does not exist.', TF_I18NDOMAIN ), '<code>' . $settings['type'] . '</code>', $settings ),
				$settings );

			return null;
		}
		if ( class_exists( $className ) ) {
			$obj = new $className( $settings, $owner );

			return $obj;
		}
		$className = $settings['type'];
		$obj       = new $className( $settings, $owner );

		return $obj;
	}

	function __construct( $settings, $owner ) {
		$this->owner = $owner;
		//var_dump($settings);
		$this->settings = array_merge( self::$defaultSettings, $this->defaultSecondarySettings );
		$this->settings = array_merge( $this->settings, $settings );
		$this->type     = is_a( $owner, 'TitanFrameworkMetaBox' ) ? self::TYPE_META : self::TYPE_ADMIN;
		$this->type     = is_a( $owner, 'TitanFrameworkCustomizer' ) ? self::TYPE_CUSTOMIZER : $this->type;
		// Generate a unique ID depending on the settings for those without IDs
		if ( empty( $this->settings['id'] ) && $this->settings['type'] != 'save' ) {
			$option_settings = array_merge( $this->settings );
			if ( ! empty( $option_settings['desc'] ) && ! is_string( $option_settings['desc'] ) ) {
				$option_settings['desc'] = uniqid();
			}
			$this->settings['id'] = substr( md5( serialize( $option_settings ) . serialize( $this->owner->settings ) ), 0, 16 );
		}

		if ( $this->settings['top_filter'] !== false || $this->settings['bottom_filter'] !== false ) {
			add_action( 'restrict_manage_posts', array( $this, 'add_filter_for_posts' ), 10, 2 );
			add_action( 'restrict_manage_users', array( $this, 'add_filter_for_users' ) );
		}
	}

	function add_filter_for_posts( $post_type, $which ) {
		$postTypes = array();

		// accomodate multiple post types
		if ( is_array( $this->owner->settings['post_type'] ) ) {
			$postTypes = $this->owner->settings['post_type'];
		} else {
			$postTypes[] = $this->owner->settings['post_type'];
		}

		if ( ! in_array( $post_type, $postTypes ) ) {
			return;
		}


		$filter = $this->settings["{$which}_filter"];
		if ( is_array( $filter ) ) {
			$args = wp_parse_args(
				$filter,
				array(
					'name'           => $this->getID(),
					'placeholder'    => null,
					'custom'         => null,
					'custom_options' => null,
				)
			);
			if ( is_callable( $args['custom'], false ) ) {
				call_user_func( $args['custom'], $args );
			} else {
				$this->echoFilter( $args );
			}
		}
	}

	public function getSamplesForCSV( $arg = null ) {
		return array();
	}

	public function echoReadonly() {
		$this->echoOptionHeader();
		$this->columnDisplayValue( $this->getPostID() );
		$this->echoOptionFooter( true );
	}

	function add_filter_for_users( $which ) {
		$this->add_filter_for_posts( 'user', $which );
	}

	public static function isOnNewScreen() {
		global $pagenow;

		return ( $pagenow == 'post-new.php' || $pagenow == 'user-new.php'
		         || strpos( isset( $_REQUEST['_wp_http_referer'] ) ? $_REQUEST['_wp_http_referer'] : '', '/post-new.php' ) !== false
		         || ( isset( $_REQUEST['original_post_status'] )
		              && $_REQUEST['original_post_status'] != 'publish'
		              && ( ! isset( $_REQUEST['action'] ) || 'editpost' != $_REQUEST['action'] ) ) );
	}

	public static function isOnEditScreen() {
		global $pagenow;

		if ( strpos( isset( $_REQUEST['_wp_http_referer'] ) ? $_REQUEST['_wp_http_referer'] : '', '/post-new.php' ) !== false ) {
			return false;
		}

		return ( ( $pagenow == 'post.php' && ! isset( $_REQUEST['original_post_status'] ) )
		         || $pagenow == 'user-edit.php' || $pagenow == 'profile.php'
		         || ( isset( $_REQUEST['action'] ) && 'editpost' == $_REQUEST['action'] )
		         || ( $pagenow == 'post.php' && isset( $_REQUEST['original_post_status'] ) && $_REQUEST['original_post_status'] == 'publish' ) );
	}

	public static function echoFullEditLinkAndWarning(
		$link_title = null,
		$state_title = null
	) {
		if ( empty( $link_title ) ) {
			$link_title = __( 'Passer en mode Édition avancée', 'amapress' );
		}
		if ( empty( $state_title ) ) {
			$link_title = __( 'Mode Édition avancée', 'amapress' );
		}

		if ( ! isset( $_REQUEST['full_edit'] ) ) {
			if ( ! empty( $link_title ) ) {
				echo '<p><a href="' . esc_attr( add_query_arg( 'full_edit', 'true' ) ) . '">' . $link_title . '</a></p>';
			}
		} else {
			if ( ! empty( $state_title ) ) {
				echo '<p style="color:red"><span class="dashicons dashicons-warning"></span> ' . $state_title . '</p>';
			}
		}
	}

	function is_post_full_edit() {
		if ( TitanFrameworkOption::isOnNewScreen() ) {
			return false;
		} else if ( TitanFrameworkOption::isOnEditScreen() ) {
			if ( isset( $_REQUEST['full_edit'] ) ) {
				return true;
			}


			$referer = parse_url( wp_get_referer() );
			if ( isset( $referer['query'] ) ) {
				parse_str( $referer['query'], $path );
				if ( ( isset( $path['full_edit'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public function isReadonly() {
		if ( ! empty( $this->settings['readonly_for'] ) ) {
			foreach ( $this->settings['readonly_for'] as $role ) {
				if ( current_user_can( $role ) ) {
					return true;
				}
			}
		}

		if ( $this->is_post_full_edit() ) {
			return false;
		}

		$readonly = $this->settings['readonly'];
		if ( $readonly === true ) {
			return true;
		}
		if ( is_callable( $readonly, false ) ) {
			return call_user_func( $readonly, $this );
		}
		if ( is_string( $readonly ) ) {
			switch ( $readonly ) {
				case 'new':
				case 'new-only':
					return ! self::isOnNewScreen();
				case 'edit':
				case 'edit-only':
					return ! self::isOnEditScreen();
			}
		}

		return apply_filters( 'tf_is_option_readonly', false, $this );
	}

	public function isHidden() {
		if ( ! empty( $this->settings['hidden_for'] ) ) {
			foreach ( $this->settings['hidden_for'] as $role ) {
				if ( current_user_can( $role ) ) {
					return true;
				}
			}
		}

		$hidden = $this->settings['hidden'];
		if ( $hidden === true ) {
			return true;
		}
		if ( is_callable( $hidden, false ) ) {
			return call_user_func( $hidden, $this );
		}
		if ( ! is_a( $this->owner, 'TitanFrameworkAdminTab' ) ) {
			if ( is_string( $hidden ) ) {
				switch ( $hidden ) {
					case 'new':
					case 'on-new':
						return self::isOnNewScreen();
					case 'edit':
					case 'on-edit':
						return self::isOnEditScreen();
				}
			}
			switch ( $this->settings['show_on'] ) {
				case 'new':
				case 'new-only':
					return ! self::isOnNewScreen();
				case 'edit':
				case 'edit-only':
					return ! self::isOnEditScreen();
			}
		}

		return false;
	}

	public function getValue( $postID = null ) {
		if ( isset( $this->settings['custom_get'] ) && is_callable( $this->settings['custom_get'], true ) ) {
			return call_user_func( $this->settings['custom_get'], $postID );
		}

		$default_value = $this->settings['default'];
		if ( is_callable( $default_value, false ) ) {
			$default_value = call_user_func( $default_value, $this );
		}

		$value = false;
		if ( empty( $this->settings['id'] ) ) {
			return $value;
		}
		if ( $this->type == self::TYPE_ADMIN ) {
			$value = $this->getFramework()->getInternalAdminPageOption( $this->settings['id'], $default_value );
			if ( ! empty( $_GET[ $this->getInputName() ] ) ) {
				return $this->cleanValueForSaving( $_GET[ $this->getInputName() ] );
			}
		} else if ( $this->type == self::TYPE_META ) {
			if ( empty( $postID ) ) {
				$postID = $this->owner->postID;
			}
			// If no $postID is given, try and get it if we are in a loop.
			if ( empty( $postID ) && ! is_admin() && get_post() != null ) {
				$postID = get_the_ID();
			}

			if ( self::isOnNewScreen() && ! empty( $_GET[ $this->getInputName() ] ) ) {
				return $this->cleanValueForSaving( $_GET[ $this->getInputName() ] );
			}

			// for meta options, use the default value for new posts/pages
			if ( $this->owner->isUser === true ) {
				$value = get_user_meta( $postID, $this->getID(), true );
			} else if ( metadata_exists( 'post', $postID, $this->getID() ) ) {
				$value = get_post_meta( $postID, $this->getID(), true );
			} else if ( self::isOnNewScreen() && ! empty( $_GET[ $this->getInputName() ] ) ) {
				$value = $_GET[ $this->getInputName() ];
			} else {
				$value = $default_value;
			}
		} else if ( $this->type == self::TYPE_CUSTOMIZER ) {
			$value = get_theme_mod( $this->getID(), $default_value );
		}
		/**
		 * Allow others to change the value of the option before it gets cleaned
		 *
		 * @since 1.9.2
		 */
		$value = apply_filters( 'tf_pre_get_value_' . $this->getOptionNamespace(), $value, $postID, $this );
		// Apply cleaning method for the value (for serialized data, slashes, etc).
		$value = $this->cleanValueForGetting( $value );

		/**
		 * Allow others to change the value of the option after it gets cleaned
		 *
		 * @since 1.9
		 */
		return apply_filters( 'tf_get_value_' . $this->settings['type'] . '_' . $this->getOptionNamespace(), $value, $postID, $this );
	}

	public function customSave( $postID ) {
		if ( isset( $this->settings['custom_save'] ) && is_callable( $this->settings['custom_save'], true ) ) {
			return call_user_func( $this->settings['custom_save'], $postID );
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	public function setValue( $value, $postID = null ) {
		// Apply cleaning method for the value (for serialized data, slashes, etc).
		$value = $this->cleanValueForSaving( $value );
		if ( $this->type == self::TYPE_ADMIN ) {
			$this->getFramework()->setInternalAdminPageOption( $this->settings['id'], $value );
		} else if ( $this->type == self::TYPE_META ) {
			$postID        = $this->getPostID( $postID );
			$default_value = $this->settings['default'];
			if ( $this->owner->isUser === true ) {
				$metas = get_user_meta( $postID, $this->getID() );
				if ( ! empty( $metas ) && count( $metas ) > 1 ) {
					delete_user_meta( $postID, $this->getID() );
				}
				if ( ( is_callable( $default_value, false ) || $value != $default_value ) && ( ! empty( $default_value ) || ! empty( $value ) ) ) {
					update_user_meta( $postID, $this->getID(), $value );
				} else {
					delete_user_meta( $postID, $this->getID() );
				}
			} else {
				$metas = get_post_meta( $postID, $this->getID() );
				if ( ! empty( $metas ) && count( $metas ) > 1 ) {
					delete_post_meta( $postID, $this->getID() );
				}
				if ( ( is_callable( $default_value, false ) || $value != $default_value ) && ( ! empty( $default_value ) || ! empty( $value ) ) ) {
					update_post_meta( $postID, $this->getID(), $value );
				} else {
					delete_post_meta( $postID, $this->getID() );
				}
			}

		} else if ( $this->type == self::TYPE_CUSTOMIZER ) {
			set_theme_mod( $this->getID(), $value );
		}
		do_action( 'tf_set_value_' . $this->getID(), $value, $postID, $this );
		do_action( 'tf_set_value_' . $this->settings['type'] . '_' . $this->getOptionNamespace(), $value, $postID, $this );

		return true;
	}

	/**
	 * Gets the framework instance currently used
	 *
	 * @return    TitanFramework
	 * @since    1.3
	 */
	protected function getFramework() {
		if ( is_a( $this->owner, 'TitanFrameworkAdminTab' ) ) {
			// a tab's parent is an admin panel
			return $this->owner->owner->owner;
		} else {
			// an admin panel's parent is the framework
			// a meta panel's parent is the framework
			// a theme customizer's parent is the framework
			return $this->owner->owner;
		}
	}

	/**
	 * Gets the option namespace used in the framework instance currently used
	 *
	 * @return    string The option namespace
	 * @since    1.0
	 */
	public function getOptionNamespace() {
		return $this->getFramework()->optionNamespace;
	}

	public function getID() {
		if ( isset( $this->settings['bare_id'] ) && true === $this->settings['bare_id'] ) {
			return $this->settings['id'];
		} else {
			return $this->getOptionNamespace() . '_' . $this->settings['id'];
		}
	}

	public function getInputName() {
		if ( ! empty( $this->settings['input_name'] ) ) {
			return $this->settings['input_name'];
		} else {
			return $this->getID();
		}
	}

	private $set_post_id = null;

	public function setPostID( $postID ) {
		$this->set_post_id = $postID;
	}

	public function getPostID( $postID = null ) {
		if ( $this->type == self::TYPE_META ) {
			if ( empty( $postID ) ) {
				$postID = $this->owner->postID;
			}
			// If no $postID is given, try and get it if we are in a loop.
			if ( empty( $postID ) && ! is_admin() && get_post() != null ) {
				$postID = get_the_ID();
			}

			if ( empty( $postID ) && is_admin() && isset( $_REQUEST['post_ID'] ) ) {
				$postID = intval( $_REQUEST['post_ID'] );
			}

			if ( ! empty( $this->set_post_id ) ) {
				return $this->set_post_id;
			}
		}

		return $postID;
	}

	public function __call( $name, $args ) {
		$default = is_array( $args ) && count( $args ) ? $args[0] : '';
		if ( stripos( $name, 'get' ) == 0 ) {
			$setting = strtolower( substr( $name, 3 ) );

			return empty( $this->settings[ $setting ] ) ? $default : $this->settings[ $setting ];
		}

		return $default;
	}

	protected function echoOptionHeader( $showDesc = false ) {
		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_header', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_header_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_header', $this );
			do_action( 'tf_custom_option_header_' . $this->getOptionNamespace(), $this );

			return;
		}
		$default_value = $this->settings['default'];
		if ( is_callable( $default_value, false ) ) {
			$default_value = call_user_func( $default_value, $this );
		}

		$id = $this->getID();
		if ( isset( $this->settings['conditional'] ) && is_array( $this->settings['conditional'] ) ) {
			$defVal = ! empty( $default_value ) ?
				$default_value :
				! empty( $this->settings['conditional']['_default_'] ) ?
					$this->settings['conditional']['_default_'] :
					'';
			$val    = $this->getValue();
			if ( ! empty( $val ) ) {
				$defVal = $val;
			}
			echo "<script type='text/javascript'>jQuery(function($) {
	$('.tf_conditional').hide();
	$('.tf_conditional.tf_{$defVal}').show();
$('#$id').change(function(){
	$('.tf_conditional').hide();
	$('.tf_conditional.tf_'+$(this).val()).show();
});
});</script>";
		}
		$name    = $this->getName();
		$evenOdd = self::$rowIndex ++ % 2 == 0 ? 'odd' : 'even';
		$style   = $this->isHidden() ? 'style="display: none"' : '';

		?>
        <tr valign="top" class="row-<?php echo self::$rowIndex ?> <?php echo $evenOdd ?> <?php echo $this->settings['visible_class'] ?>" <?php echo $style ?>>
        <th scope="row" class="first">
            <label for="<?php echo ! empty( $id ) ? $id : '' ?>"><?php echo ! empty( $name ) ? $name : '' ?></label>
        </th>
        <td class="second tf-<?php echo $this->settings['type'] ?>">
		<?php
		$desc = $this->getDesc();
		if ( ! empty( $desc ) && $showDesc ) :
			?>
            <p class='description'><?php echo $desc ?></p>
		<?php
		endif;

		if ( isset( $this->settings['before_option'] ) ) {
			$before_option = $this->settings['before_option'];
			if ( is_callable( $before_option ) ) {
				call_user_func( $before_option, $this );
			} else {
				echo $before_option;
			}
		}
	}

	protected function echoOptionHeaderBare( $showDesc = false ) {
		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_header', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_header_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_header', $this );
			do_action( 'tf_custom_option_header_' . $this->getOptionNamespace(), $this );

			return;
		}
		$id      = $this->getID();
		$name    = $this->getName();
		$evenOdd = self::$rowIndex ++ % 2 == 0 ? 'odd' : 'even';
		$style   = $this->isHidden() ? 'style="display: none"' : '';
		?>
        <tr valign="top" class="row-<?php echo self::$rowIndex ?> <?php echo $evenOdd ?>" <?php echo $style ?>>
        <td colspan="2" class="second tf-<?php echo $this->settings['type'] ?>">
		<?php
		$desc = $this->getDesc();
		if ( ! empty( $desc ) && $showDesc ) :
			?>
            <p class='description'><?php echo $desc ?></p>
		<?php
		endif;

		if ( isset( $this->settings['before_option'] ) ) {
			$before_option = $this->settings['before_option'];
			if ( is_callable( $before_option ) ) {
				call_user_func( $before_option, $this );
			} else {
				echo $before_option;
			}
		}
	}

	protected function echoOptionFooter( $showDesc = true ) {
		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_footer', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_footer_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_footer', $this );
			do_action( 'tf_custom_option_footer_' . $this->getOptionNamespace(), $this );

			return;
		}

		if ( isset( $this->settings['after_option'] ) ) {
			$after_option = $this->settings['after_option'];
			if ( is_callable( $after_option ) ) {
				call_user_func( $after_option, $this );
			} else {
				echo $after_option;
			}
		}

		$desc = $this->getDesc();
		if ( ! empty( $desc ) && $showDesc ) :
			?>
            <p class='description'><?php echo $desc ?></p>
		<?php
		endif;
		$example = $this->getExample();
		if ( ! empty( $example ) ) :
			?>
            <p class="description"><code><?php echo htmlentities( $example ) ?></code></p>
		<?php
		endif;
		?>
        </td>
        </tr>
		<?php
	}

	protected function echoOptionFooterBare( $showDesc = true ) {
		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_footer', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_footer_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_footer', $this );
			do_action( 'tf_custom_option_footer_' . $this->getOptionNamespace(), $this );

			return;
		}

		if ( isset( $this->settings['after_option'] ) ) {
			$after_option = $this->settings['after_option'];
			if ( is_callable( $after_option ) ) {
				call_user_func( $after_option, $this );
			} else {
				echo $after_option;
			}
		}

		?>
        </td>
        </tr>
		<?php
	}

	public function columnDisplayValue( $post_id ) {
		echo $this->getValue( $post_id );
	}

	public function columnExportValue( $post_id ) {
		echo $this->getValue( $post_id );
	}

	public function display_with_check() {
		if ( ! empty( $this->settings['capability'] ) && ! current_user_can( $this->settings['capability'] ) ) {
			return;
		}
		$this->display();
	}

	/* overridden */
	public function display() {
	}

	/* overridden */
	public function cleanValueForSaving( $value ) {
		return $value;
	}

	/* overridden */
	public function cleanValueForGetting( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}

//		if (!is_string($value)) var_dump($value);
		return stripslashes( $value );
	}

	/* overridden */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
	}

	public function getMemberName() {
		$id        = $this->getID();
		$pt        = $this->owner->settings['post_type'];
		$post_type = amapress_simplify_post_type( is_array( $this->owner->settings['post_type'] ) ? array_shift( $pt ) : $this->owner->settings['post_type'] );
		$ns        = $this->getOptionNamespace();

		return ucfirst( substr( $id, strlen( $ns ) + 1 + strlen( $post_type ) + 1 ) );
	}

	public function isNumericForSort() {
		return false;
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
			$wpdb->prepare( " LEFT JOIN $meta_table as amp_pm ON $main_table.ID = amp_pm.$id_column and amp_pm.meta_key=%s ", $this->getID() );
	}

	public function getSQLOrderBy( $order, $type ) {
		return ( 'user' == $type ? 'ORDER BY' : '' ) . ' amp_pm.meta_value ' . ( strpos( $order, ' DESC' ) === false ? 'ASC' : 'DESC' );
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

		return
			$join .
			$wpdb->prepare( " LEFT JOIN $meta_table as $meta_table_alias ON $main_table.ID = $meta_table_alias.$id_column and $meta_table_alias.meta_key=%s ", $this->getID() );
	}

	public function getSQLSearchLike( $type ) {
		if ( ! $this->settings['searchable'] ) {
			return null;
		}

		$meta_table_alias = "amp_srch_meta_{$this->getSqlID()}";

		return "($meta_table_alias.meta_value LIKE $1)";
	}

	protected function getSqlID() {
		return str_replace( '-', '_', sanitize_html_class( $this->getID() ) );
	}


	public function echoFilter( $args ) {
		$placeholder = empty( $args['placeholder'] ) ? '— ' . __( 'Tous', TF_I18NDOMAIN ) . ' —' : $args['placeholder'];
		$name        = $args['name'];

		$options = [];
		if ( is_callable( $args['custom_options'], false ) ) {
			$options[] = $placeholder;
			$options   = $options + call_user_func( $args['custom_options'], $args );
		}

		if ( count( $options ) <= 2 ) {
			return;
		}

		?><select id="<?php echo $name ?>"
                  name="<?php echo $name; ?>"
                  data-placeholder="<?php echo esc_attr( $placeholder ) ?>">
		<?php
		tf_parse_select_options( $options, isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : null );
		?></select><?php
	}

	public function replacePostPlaceholders( $url, $post_id ) {
		$post = get_post( $post_id, ARRAY_A );

		return preg_replace_callback( '/%%(?<name>[^%]+)%%/', function ( $m ) use ( $post, $post_id ) {
			if ( ! empty( $post[ $m['name'] ] ) ) {
				return $post[ $m['name'] ];
			}

			$val = get_post_meta( $post_id, $m['name'], true );
			$val = maybe_unserialize( $val );
			if ( is_array( $val ) ) {
				return implode( ',', $val );
			} else {
				return $val;
			}
		}, $url );
	}

	function getDesc() {
		$desc = $this->settings['desc'];
		if ( is_callable( $desc, false ) ) {
			return call_user_func( $desc, $this );
		} else {
			return $desc;
		}

	}

	function isOnMeta() {
		return $this->type == self::TYPE_META;
	}
}
