<?php

/**
 * Plugin loader class.
 *
 * @package WPPPB
 * @since 0.1.0
 */

/**
 * Takes care of loading plugins.
 *
 * @since 0.1.0
 */
class WPPPB_Loader {

	/**
	 * Keys are the plugins, the values are info for each plugin.
	 *
	 * Info for a plugin currently only includes whether it should be network
	 * -activated on multisite or not.
	 *
	 * @since 0.1.0
	 *
	 * @var array[]
	 */
	protected $plugins = array();

	/**
	 * PHP files to include after installing the plugins.
	 *
	 * @since 0.1.0
	 *
	 * @var string[][]
	 */
	protected $files = array(
		'before' => array(),
		'after'  => array(),
	);

	/**
	 * The single instance of the loader used throughout a test run.
	 *
	 * @since 0.1.0
	 *
	 * @var WPPPB_Loader
	 */
	protected static $instance;

	//
	// Public Static Methods.
	//

	/**
	 * Get the main instance of this class.
	 *
	 * @since 0.1.0
	 *
	 * @return WPPPB_Loader
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new WPPPB_Loader();
		}

		return self::$instance;
	}

	//
	// Public Methods.
	//

	/**
	 * @since 0.1.0
	 */
	protected function __construct() {
		if ( $this->should_install_plugins() ) {
			$this->hook_up_installer();
		}
	}

	/**
	 * Add a plugin to load.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin The basename slug of the plugin. Example:
	 *                             'plugin/plugin.php'.
	 * @param bool $network_wide Whether to activate the plugin network-wide.
	 */
	public function add_plugin( $plugin, $network_wide = false ) {
		$this->plugins[ $plugin ] = array( 'network_wide' => $network_wide );
	}

	/**
	 * Get a list of the plugins to be loaded.
	 *
	 * @since 0.1.0
	 *
	 * @return array[] The plugins to be loaded. Keys are plugin basename slugs,
	 *                 values arrays of data for the plugins.
	 */
	public function get_plugins() {
		return $this->plugins;
	}

	/**
	 * Add a PHP file to include after activating the plugins.
	 *
	 * You can use this to perform other custom actions after the plugins are
	 * activated. Plugin activation is performed remotely, that is in a separate
	 * process from the PHPUnit tests themselves, so these files will also be
	 * included remotely.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file The full path to the file.
	 * @param string $when When this file should be included. Current possibilities
	 *                     are 'before' and 'after', for before and after plugins
	 *                     are installed, respectively. The default is 'after'.
	 * @param array $data Data to make available to this file when it is included.
	 *                     The file can access this information through the $data
	 *                     variable.
	 */
	public function add_php_file( $file, $when = 'after', $data = array() ) {
		$this->files[ $when ][ $file ] = $data;
	}

	/**
	 * Installs the plugins via a separate PHP process.
	 *
	 * You do not need to call this directly, it is only public because it is hooked
	 * to an action by self::hook_up_installer().
	 *
	 * @since 0.1.0
	 */
	public function install_plugins() {

		system(
			WP_PHP_BINARY
			. ' ' . escapeshellarg( dirname( dirname( __FILE__ ) ) . '/bin/install-plugins.php' )
			. ' ' . escapeshellarg( json_encode( $this->plugins ) )
			. ' ' . escapeshellarg( $this->locate_wp_tests_config() )
			. ' ' . (int) is_multisite()
			. ' ' . escapeshellarg( json_encode( $this->files ) )
			, $exit_code
		);

		if ( 0 !== $exit_code ) {
			echo( 'Remote plugin installation failed with exit code ' . $exit_code );
			exit( 1 );
		}

		// The caching functions may not be loaded yet.
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		remove_action( 'all', array( $this, 'install_plugins' ) );
	}

	/**
	 * Get the path to WordPress's PHPUnit tests.
	 *
	 * @since 0.1.0
	 *
	 * @return string The full path to WordPress's PHPUnit tests.
	 */
	public function get_wp_tests_dir() {

		$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

		if ( ! $wp_tests_dir ) {
			echo( '$_ENV["WP_TESTS_DIR"] is not set.' . PHP_EOL );
			exit( 1 );
		}

		return $wp_tests_dir;
	}

	/**
	 * Locate the config file for the WordPress tests.
	 *
	 * The script is exited with an error message if no config file can be found.
	 *
	 * @since 0.1.0
	 *
	 * @return string The path to the file, if found.
	 */
	public function locate_wp_tests_config() {

		$config_file_path = $this->get_wp_tests_dir();

		if ( ! file_exists( $config_file_path . '/wp-tests-config.php' ) ) {

			// Support the config file from the root of the develop repository.
			if (
				basename( $config_file_path ) === 'phpunit'
				&& basename( dirname( $config_file_path ) ) === 'tests'
			) {
				$config_file_path = dirname( dirname( $config_file_path ) );
			}
		}

		$config_file_path .= '/wp-tests-config.php';

		if ( ! is_readable( $config_file_path ) ) {
			echo( 'Error: Unable to locate the wp-tests-config.php file.' );
			exit( 1 );
		}

		return $config_file_path;
	}

	/**
	 * Check whether the plugins should be installed.
	 *
	 * If uninstallation tests are being performed, we don't install the plugins
	 * ourselves as the uninstall tester library will take care of that.
	 *
	 * @since 0.1.0
	 *
	 * @return bool Whether we should install the plugins.
	 */
	public function should_install_plugins() {

		if ( $this->running_uninstall_tests() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the uninstall tests are being run.
	 *
	 * @since 0.1.0
	 *
	 * @return bool Whether the uninstall tests are being run.
	 */
	public function running_uninstall_tests() {

		static $uninstall_tests;

		if ( ! isset( $uninstall_tests ) ) {

			global $argv;

			$option_parser = new WPPPB_Util_GetOpt( $argv );

			$uninstall_tests = $option_parser->running_uninstall_group();
		}

		return $uninstall_tests;
	}

	/**
	 * Loads WordPress and its test environment.
	 *
	 * @since 0.2.0
	 */
	public function load_wordpress() {

		$wp_tests_dir = $this->get_wp_tests_dir();

		/**
		 * Sets up the WordPress test environment.
		 *
		 * @since 0.2.0
		 */
		require( $wp_tests_dir . '/includes/bootstrap.php' );

		// The listener is not loaded by default. It was added in WordPress 4.4.
		if ( file_exists( $wp_tests_dir . '/includes/speed-trap-listener.php' ) ) {

			/**
			 * The speed trap listener from WordPress's test suite.
			 *
			 * @since 0.2.2
			 */
			require_once( $wp_tests_dir . '/includes/speed-trap-listener.php' );
		}
	}

	//
	// Protected Methods.
	//

	/**
	 * Hooks up the function that installs the plugins.
	 *
	 * @since 0.1.0
	 */
	protected function hook_up_installer() {

		if ( ! function_exists( 'tests_add_filter' ) ) {
			/**
			 * The WordPress tests functions.
			 *
			 * @since 0.1.0
			 */
			require_once( $this->get_wp_tests_dir() . '/includes/functions.php' );
		}

		tests_add_filter( 'all', array( $this, 'install_plugins' ) );
	}
}

// EOF
