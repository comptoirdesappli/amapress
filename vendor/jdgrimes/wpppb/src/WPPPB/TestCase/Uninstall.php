<?php

/**
 * WordPress plugin uninstall test case.
 *
 * @package WPPPB
 * @since 0.1.0
 */

/**
 * Test WordPress plugin installation and uninstallation.
 *
 * @since 0.1.0
 */
abstract class WPPPB_TestCase_Uninstall extends WP_UnitTestCase {

	//
	// Protected properties.
	//

	/**
	 * The path to the main plugin file, relative to the plugin directory.
	 *
	 * E.g.: my-plugin/my-plugin.php
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Whether to run the tests with the plugin network-activated.
	 *
	 * @since 0.1.0
	 *
	 * @var bool
	 */
	protected $network_wide = false;

	/**
	 * Full path to a file to simulate plugin usage.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $simulation_file;

	/**
	 * Whether the usage simulator file has been run yet.
	 *
	 * @since 0.1.0
	 *
	 * @var bool
	 */
	protected $simulated_usage = false;

	/**
	 * The ID of the blog created for multisite tests.
	 *
	 * @since 0.1.0
	 *
	 * @var int
	 */
	protected $_blog_id;

	//
	// Methods.
	//

	/**
	 * Set up for the tests.
	 *
	 * If you need to set any of the class properties (like $plugin_file), you'll
	 * need to have a setUp() method in your child class. Don't forget to call
	 * parent::setUp() at the end of it.
	 *
	 * @since 0.1.0
	 */
	public function setUp() {

		// Create another site on multisite.
		if ( is_multisite() ) {

			// $this->factory isn't available until after setup.
			$factory = new WP_UnitTest_Factory;
			$this->_blog_id = $factory->blog->create();
		}

		$loader = WPPPB_Loader::instance();
		$loader->install_plugins();

		if ( ! isset( $this->plugin_file ) ) {

			$plugins = $loader->get_plugins();

			$this->plugin_file = key( $plugins );
			$this->network_wide = $plugins[ $this->plugin_file ]['network_wide'];
		}

		if ( ! isset( $this->simulation_file ) ) {

			$default = dirname( __FILE__ )
				. '/../../../../../../tests/phpunit/includes/usage-simulator.php';

			if ( file_exists( $default ) ) {
				$this->simulation_file = $default;
			}
		}

		parent::setUp();
	}

	/**
	 * Clean up after the tests.
	 *
	 * @since 0.1.0
	 */
	public function tearDown() {

		parent::tearDown();

		if ( is_multisite() ) {
			wpmu_delete_blog( $this->_blog_id, true );
		}
	}

	/**
	 * Simulate the usage of the plugin, by including a simulation file remotely.
	 *
	 * Called by uninstall() to simulate the usage of the plugin. This is useful to
	 * help make sure that the plugin really uninstalls itself completely, by undoing
	 * everything that might be done while it is active, not just reversing the
	 * install routine (though in some cases that may be all that is necessary).
	 *
	 * @since 0.1.0
	 */
	public function simulate_usage() {

		if ( empty( $this->simulation_file ) || $this->simulated_usage ) {
			return;
		}

		global $wpdb;

		$wpdb->query( 'ROLLBACK' );

		system(
			WP_PHP_BINARY
			. ' ' . escapeshellarg( dirname( __FILE__ ) . '/../../bin/simulate-plugin-use.php' )
			. ' ' . escapeshellarg( $this->plugin_file )
			. ' ' . escapeshellarg( $this->simulation_file )
			. ' ' . escapeshellarg( WPPPB_Loader::instance()->locate_wp_tests_config() )
			. ' ' . (int) is_multisite()
			. ' ' . (int) $this->network_wide
			, $exit_code
		);

		if ( 0 !== $exit_code ) {
			$this->fail( 'Usage simulation failed with exit code ' . $exit_code );
		}

		$this->flush_cache();

		$this->simulated_usage = true;
	}

	/**
	 * Run the plugin's uninstall script.
	 *
	 * Call it and then run your uninstall assertions. You should always test
	 * installation before testing uninstallation.
	 *
	 * @since 0.1.0
	 */
	public function uninstall() {

		global $wpdb;

		if ( ! $this->simulated_usage ) {

			$wpdb->query( 'ROLLBACK' );

			// If the plugin has a usage simulation file, run it remotely.
			$this->simulate_usage();
		}

		// We're going to do real table dropping, not temporary tables.
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		if ( empty( $this->plugin_file ) ) {
			$this->fail( 'Error: $plugin_file property not set.' );
		}

		uninstall_plugin( $this->plugin_file );

		$this->flush_cache();
	}

	/**
	 * Assert that everything with a prefix has been uninstalled from the database.
	 *
	 * @since 0.2.0
	 *
	 * @param string $prefix The prefix that everything should be uninstalled for.
	 */
	public static function assertUninstalledPrefix( $prefix ) {

		global $wpdb;

		self::assertNoTablesWithPrefix( $wpdb->prefix . $prefix );
		self::assertNoTablesWithPrefix( $wpdb->base_prefix . $prefix );
		self::assertNoOptionsWithPrefix( $prefix );
		self::assertNoUserMetaWithPrefix( $prefix );
		self::assertNoUserOptionsWithPrefix( $prefix );
		self::assertNoPostMetaWithPrefix( $prefix );
		self::assertNoCommentMetaWithPrefix( $prefix );

		if ( is_multisite() ) {
			self::assertNoSiteOptionsWithPrefix( $prefix );
		}
	}

	/**
	 * Asserts that no database tables with a given prefix exist.
	 *
	 * @since 0.2.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoTablesWithPrefix( $prefix, $message = '' ) {

		self::assertThat( $prefix, self::databaseHasNoTablesWithPrefix( $prefix ), $message );
	}

	/**
	 * Asserts that a database table does not exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table	  The table name.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertTableNotExists( $table, $message = '' ) {

		self::assertThat( $table, self::isNotInDatabase(), $message );
	}

	/**
	 * Asserts that a database table exsists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table The table name.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertTableExists( $table, $message = '' ) {

		self::assertThat( $table, self::isInDatabase(), $message );
	}

	/**
	 * Asserts that no options with a given prefix exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoOptionsWithPrefix( $prefix, $message = '' ) {

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $GLOBALS['wpdb']->options, 'option_name', $prefix ), $message );
	}

	/**
	 * Asserts that no site options with a given prefix exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoSiteOptionsWithPrefix( $prefix, $message = '' ) {

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $GLOBALS['wpdb']->sitemeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no usermeta with a given prefix exists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoUserMetaWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->usermeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no user options with a given prefix exist.
	 *
	 * User options are usermeta, prefixed with the current blog's prefix. They are
	 * mainly used in multisite, or multisite compatible plugins.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoUserOptionsWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		$prefix = $wpdb->get_blog_prefix() . $prefix;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->usermeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no postmeta with a given prefix exists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoPostMetaWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->postmeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no commentmeta with a given prefix exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoCommentMetaWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->commentmeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Database table not exists constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return WPPPB_Constraint_TableNotExists
	 */
	public static function isNotInDatabase() {

		return new WPPPB_Constraint_TableNotExists;
	}

	/**
	 * Database table exists constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return WPPPB_Constraint_TableExists
	 */
	public static function isInDatabase() {

		return new WPPPB_Constraint_TableExists;
	}

	/**
	 * No tables with prefix in DB constraint.
	 *
	 * @since 0.2.0
	 *
	 * @param string $prefix The prefix that no tables should have.
	 *
	 * @return WPPPB_Constraint_NoTablesWithPrefix
	 */
	public static function databaseHasNoTablesWithPrefix( $prefix ) {

		return new WPPPB_Constraint_NoTablesWithPrefix( $prefix );
	}

	/**
	 * No row values with prefix in DB table constraint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table  The name of the table.
	 * @param string $column The name of the row in the table to check.
	 * @param string $prefix The prefix that no rows should have.
	 *
	 * @return WPPPB_Constraint_NoRowsWithPrefix
	 */
	public static function tableColumnHasNoRowsWithPrefix( $table, $column, $prefix ) {

		return new WPPPB_Constraint_NoRowsWithPrefix( $table, $column, $prefix );
	}
}

// EOF
