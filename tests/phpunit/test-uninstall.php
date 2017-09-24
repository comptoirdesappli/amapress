<?php

/**
 * Uninstall test case.
 */

/**
 * Tests uninstalling the plugin.
 */
class My_Plugin_Uninstall_Test extends WPPPB_TestCase_Uninstall {

	/**
	 * Test installation and uninstallation.
	 */
	public function test_uninstall() {

		global $wpdb;

		/*
		 * First test that the plugin installed itself properly.
		 */

		// Check that a database table was added.
		$this->assertTableExists( $wpdb->prefix . 'myplugin_table' );

		// Check that an option was added to the database.
		$this->assertEquals( 'default', get_option( 'myplugin_option' ) );

		/*
		 * Now, test that it uninstalls itself properly.
		 */

		// You must call this to perform uninstallation.
		$this->uninstall();

		// Check that everything with this plugin's prefix has been uninstalled.
		$this->assertUninstalledPrefix( 'myplugin' );

		// Or, if we need to, we can also run more granular checks, like this:

		// Check that the table was deleted.
		$this->assertTableNotExists( $wpdb->prefix . 'myplugin_table' );

		// Check that all options with a prefix was deleted.
		$this->assertNoOptionsWithPrefix( 'myplugin' );

		// Same for usermeta and comment meta.
		$this->assertNoUserMetaWithPrefix( 'myplugin' );
		$this->assertNoCommentMetaWithPrefix( 'myplugin' );
	}
}

// EOF
