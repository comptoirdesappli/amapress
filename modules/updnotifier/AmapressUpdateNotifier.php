<?php
/*  Originally from https://github.com/l3rady/wp-updates-notifier
	Copyright 2015  Scott Cariss  (email : scott@l3rady.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class AmapressUpdateNotifier {
	/**
	 * Checks to see if any WP core updates
	 *
	 * @param string $message holds message to be sent via notification
	 *
	 * @return bool
	 */
	private static function core_update_check( &$message ) {
		global $wp_version;
		do_action( "wp_version_check" ); // force WP to check its core for updates
		$update_core = get_site_transient( "update_core" ); // get information of updates
		if ( 'upgrade' == $update_core->updates[0]->response ) { // is WP core update available?
			require_once( ABSPATH . WPINC . '/version.php' ); // Including this because some plugins can mess with the real version stored in the DB.
			$new_core_ver = $update_core->updates[0]->current; // The new WP core version
			$old_core_ver = $wp_version; // the old WP core version
			$message      .= "\n" . sprintf( __( "WP-Core: WordPress n'est pas à jour. Merci de faire la mise à jour de la version %s à la version %s", 'amapress' ), $old_core_ver, $new_core_ver ) . "\n";

			return true; // we have updates so return true
		}

		return false; // no updates return false
	}

	/**
	 * Check to see if any plugin updates.
	 *
	 * @param string $message holds message to be sent via notification
	 * @param int $allOrActive should we look for all plugins (1) or just active ones (2)
	 *
	 * @return bool
	 */
	private static function plugins_update_check( &$message, $allOrActive ) {
		global $wp_version;
		$cur_wp_version = preg_replace( '/-.*$/', '', $wp_version );
		do_action( "wp_update_plugins" ); // force WP to check plugins for updates
		$update_plugins = get_site_transient( 'update_plugins' ); // get information of updates
		if ( ! empty( $update_plugins->response ) ) { // any plugin updates available?
			$plugins_need_update = $update_plugins->response; // plugins that need updating
			if ( 2 == $allOrActive ) { // are we to check just active plugins?
				$active_plugins      = array_flip( get_option( 'active_plugins' ) ); // find which plugins are active
				$plugins_need_update = array_intersect_key( $plugins_need_update, $active_plugins ); // only keep plugins that are active
			}
			$plugins_need_update = apply_filters( 'amapress_plugins_need_update', $plugins_need_update ); // additional filtering of plugins need update
			if ( count( $plugins_need_update ) >= 1 ) { // any plugins need updating after all the filtering gone on above?
				require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); // Required for plugin API
				require_once( ABSPATH . WPINC . '/version.php' ); // Required for WP core version
				foreach ( $plugins_need_update as $key => $data ) { // loop through the plugins that need updating
					$plugin_info = get_plugin_data( WP_PLUGIN_DIR . "/" . $key ); // get local plugin info
					$info        = plugins_api( 'plugin_information', array( 'slug' => $data->slug ) ); // get repository plugin info
					$message     .= "\n" . sprintf( __( "Extension: %s n'est pas à jour. Merci de mettre à jour de la version %s à la version %s", "amapress" ), $plugin_info[ __( 'Name', 'amapress' ) ], $plugin_info[ __( 'Version', 'amapress' ) ], $data->new_version ) . "\n";
					$message     .= "\t" . sprintf( __( "Détails: %s", "amapress" ), $data->url ) . "\n";
					//$message     .= "\t" . sprintf( __( "Changelog: %s%s", "amapress" ), $data->url, "changelog/" ) . "\n";
					if ( isset( $info->tested ) && version_compare( $info->tested, $wp_version, '>=' ) ) {
						$compat = sprintf( __( 'Compatibility with WordPress %1$s: 100%% (according to its author)' ), $cur_wp_version );
					} elseif ( isset( $info->compatibility[ $wp_version ][ $data->new_version ] ) ) {
						$compat = $info->compatibility[ $wp_version ][ $data->new_version ];
						$compat = sprintf( __( 'Compatibility with WordPress %1$s: %2$d%% (%3$d "works" votes out of %4$d total)' ), $wp_version, $compat[0], $compat[2], $compat[1] );
					} else {
						$compat = sprintf( __( 'Compatibility with WordPress %1$s: Unknown' ), $wp_version );
					}
					$message .= "\t" . sprintf( __( "Compatibilité: %s", "amapress" ), $compat ) . "\n";
				}

				return true; // we have plugin updates return true
			}
		}

		return false; // No plugin updates so return false
	}

	/**
	 * Check to see if any theme updates.
	 *
	 * @param string $message holds message to be sent via notification
	 * @param int $allOrActive should we look for all themes (1) or just active ones (2)
	 *
	 * @return bool
	 */
	private static function themes_update_check( &$message, $allOrActive ) {
		do_action( "wp_update_themes" ); // force WP to check for theme updates
		$update_themes = get_site_transient( 'update_themes' ); // get information of updates
		if ( ! empty( $update_themes->response ) ) { // any theme updates available?
			$themes_need_update = $update_themes->response; // themes that need updating
			if ( 2 == $allOrActive ) { // are we to check just active themes?
				$active_theme       = array( get_option( 'template' ) => array() ); // find current theme that is active
				$themes_need_update = array_intersect_key( $themes_need_update, $active_theme ); // only keep theme that is active
			}
			$themes_need_update = apply_filters( 'amapress_themes_need_update', $themes_need_update ); // additional filtering of themes need update
			if ( count( $themes_need_update ) >= 1 ) { // any themes need updating after all the filtering gone on above?
				foreach ( $themes_need_update as $key => $data ) { // loop through the themes that need updating
					$theme_info                            = wp_get_theme( $key ); // get theme info
					$message                               .= "\n" . sprintf( __( "Thème: %s n'est pas à jour. Merci de faire la mise à jour de la version %s à la version %s", "amapress" ), $theme_info[ __( 'Name', 'amapress' ) ], $theme_info[ __( 'Version', 'amapress' ) ], $data['new_version'] ) . "\n";
					$settings['notified']['theme'][ $key ] = $data['new_version']; // set theme version we are notifying about
				}

				return true; // we have theme updates return true
			}
		}

		return false; // No theme updates so return false
	}

	public static function getUpdateMessage( $allOrActive = 2 ) {
		$message     = '';
		$res_core    = self::core_update_check( $message );
		$res_plugins = self::plugins_update_check( $message, $allOrActive );
		$res_themes  = self::themes_update_check( $message, $allOrActive );
		if ( $res_core || $res_plugins || $res_themes ) {
			return $message;
		}

		return null;
	}
}