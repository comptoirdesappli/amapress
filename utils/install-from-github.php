<?php

/**
 * Install a plugin from GitHub
 * Derived from https://github.com/mgibbs189/install-github-updater
 * Derived from WP Install Dependencies
 * <https://github.com/afragen/wp-install-dependencies>
 *
 * @author    ShareVB/Matt Gibbs
 * @license   GPL-2.0+
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Amapress_Install_Plugin_From_GitHub' ) ) {

	class Amapress_Install_Plugin_From_GitHub {
		public $message = array();
		public $display_name;
		public $slug;
		public $zip;

		/**
		 * Amapress_Install_Plugin_From_GitHub constructor.
		 *
		 * @param string $display_name
		 * @param string $slug
		 */
		public function __construct( $slug, $display_name, $github_name, $branch = 'master' ) {
			$this->display_name = $display_name;
			$this->slug         = $slug;
			$this->zip          = "https://github.com/$github_name/archive/$branch.zip";
		}

		/**
		 * Is GHU installed?
		 */
		function is_installed() {
			$plugins = get_plugins();

			return isset( $plugins[ $this->slug ] );
		}


		/**
		 * Install GHU
		 */
		function install() {
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 2 );

			$skin = new Amapress_Plugin_Installer_Skin( array(
				'type'  => 'plugin',
				'nonce' => wp_nonce_url( $this->zip ),
			) );

			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $this->zip );

			if ( is_wp_error( $result ) ) {
				return array( 'status' => 'error', 'message' => $result->get_error_message() );
			}

			wp_cache_flush();

			$result = $this->activate();

			if ( 'error' === $result['status'] ) {
				return $result;
			}

			return array(
				'status'  => 'ok',
				'message' => sprintf( __( '%s has been installed and activated.' ), $this->display_name )
			);
		}


		/**
		 * Rename the plugin folder to "github-updater"
		 */
		function upgrader_source_selection( $source, $remote_source ) {
			/** @var WP_Filesystem_Base $wp_filesystem */
			global $wp_filesystem;
			$new_source = trailingslashit( $remote_source ) . dirname( $this->slug );
			$wp_filesystem->move( $source, $new_source );

			return trailingslashit( $new_source );
		}


		/**
		 * Activate GHU
		 */
		function activate() {
			$result = activate_plugin( $this->slug );

			if ( is_wp_error( $result ) ) {
				return array( 'status' => 'error', 'message' => $result->get_error_message() );
			}

			return array(
				'status'  => 'ok',
				'message' => sprintf( __( '%s has been activated.' ), $this->display_name )
			);
		}
	}


	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';


	/**
	 * Override Plugin_Installer_Skin to disable automatic output
	 */
	class Amapress_Plugin_Installer_Skin extends Plugin_Installer_Skin {
		public function header() {
		}

		public function footer() {
		}

		public function error( $errors ) {
		}

		public function feedback( $string, ...$args ) {
		}
	}
}

add_action( 'admin_action_install_github_plugin', function () {
	if ( ! current_user_can( 'update_core' ) ) {
		wp_die( 'Access denied' );
	}

	if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'install_github_plugin' ) ) {
		wp_die( 'Access denied' );
	}

	$installer = new Amapress_Install_Plugin_From_GitHub(
		$_REQUEST['slug'],
		$_REQUEST['name'],
		$_REQUEST['github'] );
	if ( ! $installer->is_installed() ) {
		$result = $installer->install();
	} else {
		$result = $installer->activate();
	}
	echo $result['message'];
} );

function amapress_install_plugin_from_github_url( $slug, $name, $github_repo ) {
	return wp_nonce_url( add_query_arg( [
		'action' => 'install_github_plugin',
		'slug'   => $slug,
		'name'   => $name,
		'github' => $github_repo,
	], admin_url( 'admin.php' ) ),
		'install_github_plugin' );
}