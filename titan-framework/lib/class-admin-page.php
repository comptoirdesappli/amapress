<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkAdminPage {

	private $defaultSettings = array(
		'name'       => '',
		// Name of the menu item
		'title'      => '',
		// Title displayed on the top of the admin panel
		'parent'     => null,
		// id of parent, if blank, then this is a top level menu
		'id'         => '',
		// Unique ID of the menu item
		'capability' => 'manage_options',
		// User role
		'icon'       => 'dashicons-admin-generic',
		// Menu icon for top level menus only http://melchoyce.github.io/dashicons/
		'position'   => null,
		// Menu position. Can be used for both top and sub level menus
		'use_form'   => true,
		'use_table'  => true,
		// If false, options will not be wrapped in a form
		'desc'       => '',
		// Description displayed below the title
	);

	public $settings;
	public $options = array();
	public $tabs = array();
	public $owner;

	public $panelID;

	private $activeTab = null;
	private static $idsUsed = array();

	function __construct( $settings, $owner ) {
		$this->owner = $owner;

		if ( ! is_admin() ) {
			return;
		}

		$this->settings = array_merge( $this->defaultSettings, $settings );
		// $this->options = $options;
		if ( empty( $this->settings['name'] ) ) {
			return;
		}

		if ( empty( $this->settings['title'] ) ) {
			$this->settings['title'] = $this->settings['name'];
		}

		if ( empty( $this->settings['id'] ) ) {
			$prefix = '';
			if ( ! empty( $this->settings['parent'] ) ) {
				$prefix = str_replace( ' ', '-', trim( strtolower( $this->settings['parent'] ) ) ) . '-';
			}
			$this->settings['id'] = $prefix . str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
			$this->settings['id'] = str_replace( '&', '-', $this->settings['id'] );
		}

		// make sure all our IDs are unique
		$suffix = '';
		while ( in_array( $this->settings['id'] . $suffix, self::$idsUsed ) ) {
			if ( $suffix == '' ) {
				$suffix = 2;
			} else {
				$suffix ++;
			}
		}
		$this->settings['id'] .= $suffix;

		// keep track of all IDs used
		self::$idsUsed[] = $this->settings['id'];

		$priority = - 1;
		if ( $this->settings['parent'] ) {
			$priority = intval( $this->settings['position'] );
		}

		add_action( 'admin_menu', array( $this, 'register' ), $priority );
	}

	public function createAdminPanel( $settings ) {
		$settings['parent'] = $this->settings['id'];

		return $this->owner->createAdminPanel( $settings );
	}

	public function register() {
		// Parent menu
		if ( empty( $this->settings['parent'] ) ) {
			$this->panelID = add_menu_page( $this->settings['name'],
				do_shortcode( ! empty( $this->settings['menu_title'] ) ? $this->settings['menu_title'] : $this->settings['name'] ),
				$this->settings['capability'],
				$this->settings['id'],
				array( $this, 'createAdminPage' ),
				$this->settings['icon'],
				$this->settings['position'] );
			// Sub menu
		} else {
			$this->panelID = add_submenu_page( $this->settings['parent'],
				$this->settings['name'],
				( ! empty( $this->settings['menu_icon'] ) ? '<span class="dashicons-before ' . $this->settings['menu_icon'] . '" /> ' : '' ) .
				do_shortcode( ( ! empty( $this->settings['menu_title'] ) ? $this->settings['menu_title'] : $this->settings['name'] ) ),
				$this->settings['capability'],
				$this->settings['id'],
				array( $this, 'createAdminPage' ) );
		}

		add_action( 'load-' . $this->panelID, array( $this, 'saveOptions' ) );

//		add_action( 'load-' . $this->panelID, array( $this, 'addTitanCredit' ) );
	}


//	public function addTitanCredit() {
//		add_filter( 'admin_footer_text', array( $this, 'addTitanCreditText' ) );
//	}
//
//
//	public function addTitanCreditText() {
//		echo __( "<em>Options Page Created with <a href='http://titanframework.net?utm_source=admin&utm_medium=admin footer'>Titan Framework</a></em>", TF_I18NDOMAIN );
//	}


	public function getOptionNamespace() {
		return $this->owner->optionNamespace;
	}


	public function saveOptions() {
		if ( ! $this->verifySecurity() ) {
			return;
		}

		$message   = '';
		$activeTab = $this->getActiveTab();

		/*
		 *  Save
		 */

		if ( $_POST['action'] == 'save' ) {

			// we are in a tab
			if ( ! empty( $activeTab ) ) {
				foreach ( $activeTab->options as $option ) {
					if ( empty( $option->settings['id'] ) ) {
						continue;
					}

					if ( isset( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) ) {
						$value = $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ];
					} else {
						$value = '';
					}

					if ( ! $option->customSave( null ) ) {
						$option->setValue( $value );
					}
				}
			}

			foreach ( $this->options as $option ) {
				if ( empty( $option->settings['id'] ) ) {
					continue;
				}

				if ( isset( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) ) {
					$value = $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ];
				} else {
					$value = '';
				}

				$option->setValue( $value );
			}

			// Hook 'tf_pre_save_options_{namespace}' - action pre-saving
			/**
			 * Fired right before options are saved.
			 *
			 * @param TitanFrameworkAdminPage|TitanFrameworkCustomizer|TitanFrameworkMetaBox $this The container currently being saved.
			 *
			 * @since 1.0
			 *
			 */
			$namespace = $this->getOptionNamespace();
			do_action( "tf_pre_save_options_{$namespace}", $this );
			do_action( "tf_pre_save_admin_{$namespace}", $this, $activeTab, $this->options );

			$this->owner->saveInternalAdminPageOptions();

			do_action( 'tf_save_admin_' . $this->getOptionNamespace(), $this, $activeTab, $this->options );

			$message = 'saved';

			/*
			* Reset
			*/

		} else if ( $_POST['action'] == 'reset' ) {

			// we are in a tab
			if ( ! empty( $activeTab ) ) {
				foreach ( $activeTab->options as $option ) {
					if ( empty( $option->settings['id'] ) ) {
						continue;
					}

					$option->setValue( $option->settings['default'] );
				}
			}

			foreach ( $this->options as $option ) {
				if ( empty( $option->settings['id'] ) ) {
					continue;
				}

				$option->setValue( $option->settings['default'] );
			}

			// Hook 'tf_pre_reset_options_{namespace}' - action pre-saving
			do_action( 'tf_pre_reset_options_' . $this->getOptionNamespace(), $this );
			do_action( 'tf_pre_reset_admin_' . $this->getOptionNamespace(), $this, $activeTab, $this->options );

			$this->owner->saveInternalAdminPageOptions();

			do_action( 'tf_reset_admin_' . $this->getOptionNamespace(), $this, $activeTab, $this->options );

			$message = 'reset';
		} else if ( isset ( $_POST['action'] ) ) {
			do_action( 'tf_custom_admin_' . $this->getOptionNamespace() . '_action_' . $_POST['action'], $this, $activeTab, $this->options );
		}

		/*
		 * Redirect to prevent refresh saving
		 */

		// urlencode to allow special characters in the url
		$url       = wp_get_referer();
		$activeTab = $this->getActiveTab();
		$url       = add_query_arg( 'page', urlencode( $this->settings['id'] ), $url );
		if ( ! empty( $activeTab ) ) {
			$url = add_query_arg( 'tab', urlencode( $activeTab->settings['id'] ), $url );
		}
		if ( ! empty( $message ) ) {
			$url = add_query_arg( 'message', $message, $url );
		}

		do_action( 'tf_admin_options_saved_' . $this->getOptionNamespace() );

		wp_redirect( esc_url_raw( $url ) );
	}

	private function verifySecurity() {
		if ( empty( $_POST ) || empty( $_POST['action'] ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( $screen->id != $this->panelID ) {
			return false;
		}

		if ( ! current_user_can( $this->settings['capability'] ) ) {
			return false;
		}

		if ( ! check_admin_referer( $this->settings['id'], TF . '_nonce' ) ) {
			return false;
		}

		return true;
	}

	public function getActiveTab() {
		if ( ! count( $this->tabs ) ) {
			return '';
		}
		if ( ! empty( $this->activeTab ) ) {
			return $this->activeTab;
		}

		if ( empty( $_GET['tab'] ) ) {
			$this->activeTab = $this->tabs[0];

			return $this->activeTab;
		}

		foreach ( $this->tabs as $tab ) {
			if ( $tab->settings['id'] == $_GET['tab'] ) {
				$this->activeTab = $tab;

				return $this->activeTab;
			}
		}

		$this->activeTab = $this->tabs[0];

		return $this->activeTab;
	}

	public function createAdminPage() {
		if ( ! empty( $this->settings['capability'] ) && ! current_user_can( $this->settings['capability'] ) ) {
			return;
		}

		do_action( 'tf_admin_page_before' );
		do_action( 'tf_admin_page_before_' . $this->getOptionNamespace() );

		?>
        <div class="wrap">
            <h2><?php echo do_shortcode( $this->settings['title'] ) ?></h2>
			<?php
			if ( ! empty( $this->settings['desc'] ) ) {
				$desc = $this->settings['desc'];
				if ( is_callable( $desc, false ) ) {
					$desc = call_user_func( $desc );
				}
				?><p class='description'><?php echo $desc; ?></p><?php
			}
			if ( ! empty( $this->settings['long_desc'] ) ) {
				$long_desc = $this->settings['long_desc'];
				if ( is_callable( $long_desc, false ) ) {
					$long_desc = call_user_func( $long_desc );
				}
				echo $long_desc;
			}
			?>

            <div class='titan-framework-panel-wrap'>
				<?php

				do_action( 'tf_admin_page_start' );
				do_action( 'tf_admin_page_start_' . $this->getOptionNamespace() );

				if ( count( $this->tabs ) ) :
					?>
                    <h2 class="nav-tab-wrapper">
						<?php

						do_action( 'tf_admin_page_tab_start' );
						do_action( 'tf_admin_page_tab_start_' . $this->getOptionNamespace() );

						foreach ( $this->tabs as $tab ) {
							$tab->displayTab();
						}

						do_action( 'tf_admin_page_tab_end' );
						do_action( 'tf_admin_page_tab_end_' . $this->getOptionNamespace() );

						?>
                    </h2>
				<?php
				endif;

				?>
                <div class='options-container'>
					<?php

					// Display notification if we did something
					if ( ! empty( $_GET['message'] ) ) {
						if ( $_GET['message'] == 'saved' ) {
							echo TitanFrameworkAdminNotification::formNotification( __( 'Settings saved.', TF_I18NDOMAIN ), esc_html( $_GET['message'] ) );
						} else if ( $_GET['message'] == 'reset' ) {
							echo TitanFrameworkAdminNotification::formNotification( __( 'Settings reset to default.', TF_I18NDOMAIN ), esc_html( $_GET['message'] ) );
						}
					}

					$use_form  = ! empty( $this->getActiveTab() )
					             && isset( $this->getActiveTab()->settings['use_form'] ) ?
						$this->getActiveTab()->settings['use_form'] :
						$this->settings['use_form'];
					$use_table = ! empty( $this->getActiveTab() )
					             && isset( $this->getActiveTab()->settings['use_table'] ) ?
						$this->getActiveTab()->settings['use_table'] :
						$this->settings['use_table'];
					if ( $use_form ) {
					?>
                    <form method='post' enctype="multipart/form-data">
		                <?php
		                // security
		                wp_nonce_field( $this->settings['id'], TF . '_nonce' );
		                }

						if ( $use_table ) {

						?>
                        <table class='form-table tf-form-table'>
                            <tbody>
							<?php

							do_action( 'tf_admin_page_table_start' );
							do_action( 'tf_admin_page_table_start_' . $this->getOptionNamespace() );
							}


							$activeTab = $this->getActiveTab();
							if ( ! empty( $activeTab ) ) {

								if ( ! empty( $activeTab->settings['desc'] ) ) {
									?><p class='description'><?php echo $activeTab->settings['desc'] ?></p><?php
								}

								$activeTab->displayOptions();
							}

							foreach ( $this->options as $option ) {
								$option->display_with_check();
							}

							if ( $use_table ) {

							do_action( 'tf_admin_page_table_end' );
							do_action( 'tf_admin_page_table_end_' . $this->getOptionNamespace() );

							?>
                            </tbody>
                        </table>
	                <?php
	                }
	                if ( $use_form ) {

	                ?>
                    </form>
                <?php
                }

                // Reset form. We use JS to trigger a reset from other buttons within the main form
                // This is used by class-option-save.php
                if ( $use_form ) :
	                ?>
                    <form method='post' id='tf-reset-form'>
		                <?php
		                // security
		                wp_nonce_field( $this->settings['id'], TF . '_nonce' );
		                ?>
                        <input type='hidden' name='action' value='reset'/>
                    </form>
                <?php
                endif;

                do_action( 'tf_admin_page_end' );
                do_action( 'tf_admin_page_end_' . $this->getOptionNamespace() );

                ?>
                    <div class='options-container'>
                    </div>
                </div>
            </div>
        </div>
		<?php

		do_action( 'tf_admin_page_after' );
		do_action( 'tf_admin_page_after_' . $this->getOptionNamespace() );
	}

	public function createTab( $settings ) {
		$obj          = new TitanFrameworkAdminTab( $settings, $this );
		$this->tabs[] = $obj;

		do_action( 'tf_admin_tab_created_' . $this->getOptionNamespace(), $obj );

		return $obj;
	}

	public function createOption( $settings ) {
		if ( ! apply_filters( 'tf_create_option_continue_' . $this->getOptionNamespace(), true, $settings ) ) {
			return null;
		}
//		if (!empty($settings['capability']) && !current_user_can($settings['capability'])) return null;

		$obj             = TitanFrameworkOption::factory( $settings, $this );
		$this->options[] = $obj;

		do_action( 'tf_create_option_' . $this->getOptionNamespace(), $obj );

		return $obj;
	}
}
