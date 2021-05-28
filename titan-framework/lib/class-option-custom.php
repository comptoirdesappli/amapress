<?php /** * Custom option * * @package Titan Framework */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/** * Custom option class * * @since 1.0 */
class TitanFrameworkOptionCustom extends TitanFrameworkOption {
	/**
	 * Default settings specific to this option
	 * @var array
	 */
	public $defaultSecondarySettings = array(
		'custom'               => '', // Custom HTML
		'bare'                 => false,
		'show_desc'            => false,
		'custom_csv_sample'    => null,
		'join_meta_key'        => null,
		'join_on'              => null,
		'sort_column'          => null,
		'column'               => null,
		'use_custom_as_column' => false,
		'export'               => null,
		'save'                 => null,
	);

	public function getSamplesForCSV( $arg = null ) {
		if ( is_callable( $this->settings['custom_csv_sample'], false ) ) {
			return call_user_func( $this->settings['custom_csv_sample'], $this, $arg );
		} else if ( is_array( $this->settings['custom_csv_sample'] ) ) {
			return $this->settings['custom_csv_sample'];
		} else {
			return array();
		}
	}

	public function customSave( $postID ) {
		$save = $this->settings['save'];
		if ( $save && is_callable( $save, false ) ) {
			return call_user_func( $save, $postID, $this );
		} else {
			return true;
		}
	}

	public function columnDisplayValue( $postID ) {
		$fn = $this->settings['use_custom_as_column'] ? 'custom' : 'column';
		if ( ! isset( $this->settings[ $fn ] ) ) {
			return;
		}

		$column = $this->settings[ $fn ];
		if ( $column && is_callable( $column ) ) {
			echo call_user_func( $column, $postID, $this );
		}
	}

	public function columnExportValue( $postID ) {
		$fn = $this->settings['use_custom_as_column'] ? 'custom' : 'column';
		if ( ! isset( $this->settings[ $fn ] ) ) {
			return;
		}

		$column = $this->settings[ $fn ];
		if ( ! empty( $this->settings['export'] ) && is_callable( $this->settings['export'], false ) ) {
			$column = $this->settings['export'];
		}
		if ( $column && is_callable( $column, false ) ) {
			echo wp_strip_all_tags( call_user_func( $column, $postID, $this ) );
		}
	}

	/**
	 * Display for options and meta
	 */
	public function display( $postID = null ) {
		if ( empty( $postID ) && ! is_a( $this->owner, 'TitanFrameworkAdminTab' ) && ! is_a( $this->owner, 'TitanFrameworkAdminPage' ) ) {
			$postID = $this->owner->postID;
		}
// If no $postID is given, try and get it if we are in a loop.
		if ( empty( $postID ) && ! is_admin() && get_post() != null ) {
			$postID = get_the_ID();
		}
		$custom = $this->settings['custom'];
		if ( $custom && is_callable( $custom, false ) ) {
			$cnt = call_user_func( $custom, $postID, $this );
		} else {
			$cnt = $custom;
		}

		if ( $this->settings['bare'] === true ) {
			echo '<tr><td colspan="2" style="margin: 0; padding: 0">';
			echo $cnt;
			echo '</td></tr>';

			return;
		}

		if ( ! empty( $this->settings['name'] ) ) {
			$this->echoOptionHeader( 'before' == $this->settings['show_desc'] );
			echo $cnt;
			$this->echoOptionFooter( 'after' == $this->settings['show_desc'] );
		} else {
			$this->echoOptionHeaderBare( 'before' == $this->settings['show_desc'] );
			echo $cnt;
			$this->echoOptionFooterBare( 'after' == $this->settings['show_desc'] );
		}
	}

	public function appendSQLJoinForSorting( $join, $type ) {
		if ( empty( $this->settings['join_meta_key'] ) ) {
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

		$sort_column = $this->settings['sort_column'];

		switch ( $this->settings['join_on'] ) {
			case 'post':
				return
					$join .
					$wpdb->prepare( " LEFT JOIN $meta_table as amp_pm ON $main_table.ID = amp_pm.$id_column and amp_pm.meta_key=%s ", $this->settings['join_meta_key'] ) .
					" LEFT JOIN $wpdb->posts as amp_sort ON amp_pm.meta_value = amp_sort.ID ";
			case 'post_meta':
				return
					$join .
					$wpdb->prepare( " LEFT JOIN $meta_table as amp_pm ON $main_table.ID = amp_pm.$id_column and amp_pm.meta_key=%s ", $this->settings['join_meta_key'] ) .
					$wpdb->prepare( " LEFT JOIN $wpdb->postmeta as amp_sort ON amp_pm.meta_value = amp_sort.post_id AND amp_sort.meta_key=%s", $sort_column );
			case  'user_meta':
				return
					$join .
					$wpdb->prepare( " LEFT JOIN $meta_table as amp_pm ON $main_table.ID = amp_pm.$id_column and amp_pm.meta_key=%s ", $this->settings['join_meta_key'] ) .
					$wpdb->prepare( " LEFT JOIN $wpdb->usermeta as amp_sort ON amp_pm.meta_value = amp_sort.user_id AND amp_sort.meta_key=%s", $sort_column );
			default:
				return
					$join .
					$wpdb->prepare( " LEFT JOIN $meta_table as amp_pm ON $main_table.ID = amp_pm.$id_column and amp_pm.meta_key=%s ", $this->settings['join_meta_key'] ) .
					" LEFT JOIN $wpdb->users as amp_sort ON amp_pm.meta_value = amp_sort.ID ";
		}
	}

	public function getSQLOrderBy( $order, $type ) {
		if ( empty( $this->settings['join_meta_key'] ) || empty( $this->settings['sort_column'] ) ) {
			return $order;
		}

		$sort_column = $this->settings['sort_column'];

		switch ( $this->settings['join_on'] ) {
			case 'post_meta':
			case  'user_meta':
				$sort_column = 'meta_value';
				break;
		}

		return ( 'user' == $type ? 'ORDER BY' : '' ) . " amp_sort.$sort_column " . ( strpos( $order, ' DESC' ) === false ? 'ASC' : 'DESC' );
	}

	/**
	 * Display for theme customizer
	 *
	 * @param WP_Customize $wp_customize The customizer object.
	 * @param TitanFrameworkCustomizer $section The customizer section.
	 * @param int $priority The display priority of the control.
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionCustomControl( $wp_customize, $this->getID(), array(
			'label'    => $this->settings['name'],
			'section'  => $section->settings['id'],
			'type'     => 'select',
			'settings' => $this->getID(),
			'priority' => $priority,
			'custom'   => $this->settings['custom'],
		) ) );
	}
}

// We create a new control for the theme customizer.
add_action( 'customize_register', 'register_titan_framework_option_custom_control', 1 );

/** * Register the customizer control */

function register_titan_framework_option_custom_control() {
	/**
	 * Custom option class
	 *
	 * @since 1.0
	 */
	class TitanFrameworkOptionCustomControl extends WP_Customize_Control {
		/**
		 * The custom content control
		 *
		 * @var bool
		 */
		public $custom;

		/**
		 * Renders the control
		 */
		public function render_content() {
			if ( is_callable( $this->$custom, true ) ) {
				$cnt = call_user_func( $this->$custom );
			} else {
				$cnt = $this->$custom;
			}
			echo $cnt;
		}
	}
}