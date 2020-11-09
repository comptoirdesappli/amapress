<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'sanitize_html_class', 'menu_sanitize_html_class', 10, 2 );
function menu_sanitize_html_class( $sanitized, $class ) {
	if ( 0 === strpos( $class, 'dashicons-none' ) ) {
		return $class;
	}

	return $sanitized;
}

add_filter( 'submenu_file', 'amapress_highlight_menu', 99, 1 );
function amapress_highlight_menu( $submenu ) {
	global $parent_file;
	global $submenu_file;

	$post      = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
	$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : null;
	if ( empty( $post_type ) && ! empty( $post ) ) {
		$post_type = get_post_type( intval( $post ) );
	}
	if ( empty( $post_type ) && isset( $_GET['view'] ) ) {
		$post_type = $_GET['view'];
	}

	foreach ( AmapressEntities::getMenu() as $m ) {
		if ( isset( $m['subpages'] ) ) {
			foreach ( $m['subpages'] as $subpage ) {
				if ( isset( $subpage['post_type'] ) && $subpage['post_type'] == $post_type ) { // admin_url($slug) == $full_url) {
					$parent_file  = $m['id'];
					$submenu_file = $subpage['slug'];

					return $submenu_file;
				}
			}
		}
	}

	return $submenu;
}

add_filter( 'parent_file', 'amapress_highlight_menu_parent', 99, 1 );
function amapress_highlight_menu_parent( $file ) {
	global $parent_file;
	global $submenu_file;

	$post      = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
	$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : null;
	if ( empty( $post_type ) && ! empty( $post ) ) {
		$post_type = get_post_type( intval( $post ) );
	}
	if ( empty( $post_type ) && isset( $_GET['view'] ) ) {
		$post_type = $_GET['view'];
	}
	if ( empty( $post_type ) && isset( $_GET['taxonomy'] ) ) {
		$post_type = $_GET['taxonomy'];
	}

	foreach ( AmapressEntities::getMenu() as $m ) {
		if ( isset( $m['subpages'] ) ) {
			foreach ( $m['subpages'] as $subpage ) {
				if ( isset( $subpage['post_type'] ) && $subpage['post_type'] == $post_type ) { // admin_url($slug) == $full_url) {
					$parent_file  = $m['id'];
					$submenu_file = $subpage['slug'];

					return $parent_file;
				}
			}
		}
	}

	return $file;
}

// Add to the admin_init action hook
add_filter( 'current_screen', 'amapress_current_screen' );
function amapress_current_screen( WP_Screen $screen ) {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return $screen;
	}
	add_filter( 'views_' . $screen->id, 'amapress_insert_edit_description' );

	return $screen;
}

function amapress_insert_edit_description( $views ) {
	$post      = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
	$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : null;
	if ( empty( $post_type ) && ! empty( $post ) ) {
		$post_type = get_post_type( intval( $post ) );
	}
	if ( empty( $post_type ) && isset( $_GET['view'] ) ) {
		$post_type = $_GET['view'];
	}

	foreach ( AmapressEntities::getMenu() as $m ) {
		if ( isset( $m['subpages'] ) ) {
			foreach ( $m['subpages'] as $subpage ) {
				if ( isset( $subpage['post_type'] ) && $subpage['post_type'] == $post_type ) {
					if ( ! empty( $subpage['description'] ) ) {
						echo '<p class="description">';
						echo $subpage['description'];
						echo '</p>';
					}
				}
			}
		}
	}

	return $views;
}

add_action( 'admin_bar_menu', 'amapress_admin_bar_menu' );
function amapress_admin_bar_menu( WP_Admin_Bar $admin_bar ) {
	global $pagenow;

	if ( current_user_can( 'list_users' ) ) {
		$script = '<script type="text/javascript">
jQuery(function($) {
              function search_user() {
                var val = $(\'#amapress_search_user_text\').val();
                if (val == null || val == \'\') {
                    alert("' . esc_js( __( 'Champs de recherche vide', 'amapress' ) ) . '");
                    return;
                }
                window.location.href = \'' . admin_url( '/users.php' ) . '?s=\' + encodeURIComponent(val);
            }

            $(\'#amapress_search_user_btn\').click(function () {
                search_user();
            });
            $(\'#amapress_search_user_text\').keypress(function (e) {
                if (e.which === 13) {
                    search_user();
                }
            }); 
});
</script>';
		$style  = '<style type="text/css">
#wp-admin-bar-amapress_search_user_admin_bar #amapress_search_user_text {
    height: 24px !important;
}
#wp-admin-bar-amapress_search_user_admin_bar {
    vertical-align: middle !important;
}
#amapress_search_user_btn::before {
	vertical-align: middle;
}
</style>';
		$admin_bar->add_menu( array(
			'id'     => 'amapress_search_user_admin_bar',
			'parent' => 'top-secondary',
			'title'  => '<input id="amapress_search_user_text" style="display: inline" type="text" placeholder="' . esc_attr__( 'Amapien ?', 'amapress' ) . '" class=\'amapress_search_user form-control\' />
<span role="button" id="amapress_search_user_btn" style="display: inline" class="amapress_search_user dashicons-before dashicons-search"></span>' . $style . $script,
			'href'   => '#',
		) );
	}

	if ( 'post.php' == $pagenow || 'post-new.php' == $pagenow ) {
		$admin_bar->add_menu( array(
			'id'     => 'amapress_publish_admin_bar',
			'parent' => 'top-secondary',
			'title'  => '<button class=\'amapress_publish button button-primary\' style="display: none">' . __( 'Enregistrer', 'amapress' ) . '</button>',
			'href'   => '#',
		) );
	}

	if ( 'user-edit.php' == $pagenow || 'profile.php' == $pagenow ) {
		$admin_bar->add_menu( array(
			'id'     => 'amapress_update_user_admin_bar',
			'parent' => 'top-secondary',
			'title'  => '<button class=\'amapress_update_user button button-primary\'>' . __( 'Enregistrer', 'amapress' ) . '</button>',
			'href'   => '#',
		) );
	}
}

add_action( 'admin_bar_menu', 'amapress_admin_bar_menu_entities', 900, 1 );
function amapress_admin_bar_menu_entities( WP_Admin_Bar $admin_bar ) {
	amapress_admin_bar_add_items(
		apply_filters( 'amapress_register_admin_bar_menu_items', array() ),
		$admin_bar, null );

	if ( ! amapress_can_access_admin() ) {
		$admin_bar->remove_node( 'site-name' );
		$admin_bar->remove_node( 'wp-logo' );
		$admin_bar->remove_node( 'gdbb-toolbar' );
	}
}

add_action( 'admin_bar_menu', 'amapress_admin_bar_new_entities_and_sitename', 900, 1 );
function amapress_admin_bar_new_entities_and_sitename( WP_Admin_Bar $admin_bar ) {
	$create_new_items = [];
	foreach ( AmapressEntities::getPostTypes() as $name => $conf ) {
		if ( isset( $conf['show_admin_bar_new'] )
		     && true === $conf['show_admin_bar_new']
		     && current_user_can( "publish_$name" ) ) {
			$internal_post_type = isset( $conf['internal_name'] ) ? $conf['internal_name'] : 'amps_' . $name;
			$create_new_items[] = [
				'id'    => "new-$internal_post_type",
//				'icon' => isset($conf['menu_icon']) ? $conf['menu_icon'] : null,
				'title' => $conf['singular'],
				'href'  => admin_url( "post-new.php?post_type=$internal_post_type" ),
			];
		} elseif ( isset( $conf['show_admin_bar_new'] )
		           && false === $conf['show_admin_bar_new'] ) {
			$internal_post_type = isset( $conf['internal_name'] ) ? $conf['internal_name'] : 'amps_' . $name;
			$admin_bar->remove_node( 'new-' . $internal_post_type );
		}
	}
	amapress_admin_bar_add_items( $create_new_items, $admin_bar, 'new-content' );

	$site_name_items   = [];
	$site_name_items[] = [
		'id'         => 'amps_admm-gest-adhs',
		'icon'       => 'dashicons-none flaticon-pen',
		'title'      => __( 'Gestion Adhésions', 'amapress' ),
		'capability' => 'edit_adhesion_paiement',
		'href'       => admin_url( 'admin.php?page=amapress_gestion_adhesions_page' ),
	];
	$site_name_items[] = [
		'id'         => 'amps_admm-gest-inscrs',
		'icon'       => 'dashicons-none flaticon-pen',
		'title'      => __( 'Gestion Contrats', 'amapress' ),
		'capability' => 'edit_contrat',
		'href'       => admin_url( 'admin.php?page=amapress_gestion_amapiens_page' ),
	];
	$site_name_items[] = [
		'id'         => 'amps_admm-gest-distribs',
		'icon'       => 'dashicons-before dashicons-store',
		'title'      => __( 'Distributions', 'amapress' ),
		'capability' => 'edit_distribution',
		'href'       => admin_url( 'admin.php?page=mapress_gestion_distrib_page' ),
	];
	$site_name_items[] = [
		'id'         => 'amps_admm-gest-events',
		'icon'       => 'dashicons-none flaticon-interface-2',
		'title'      => __( 'Évènements', 'amapress' ),
		'capability' => 'manage_events',
		'href'       => admin_url( 'admin.php?page=amapress_gestion_events_page' ),
	];
	$site_name_items[] = [
		'id'         => 'amps_admm-articles',
		'icon'       => 'dashicons-admin-post',
		'capability' => 'edit_posts',
		'title'      => __( 'Articles', 'amapress' ),
		'href'       => admin_url( 'edit.php' ),
	];
	$site_name_items[] = [
		'id'         => 'amps_admm-pages',
		'icon'       => 'dashicons-admin-page',
		'capability' => 'edit_pages',
		'title'      => __( 'Pages', 'amapress' ),
		'href'       => admin_url( 'edit.php?post_type=page' ),
	];
	$site_name_items[] = [
		'id'         => 'amps_admm-users',
		'icon'       => 'dashicons-admin-users',
		'capability' => 'edit_users',
		'title'      => __( 'Utilisateurs', 'amapress' ),
		'href'       => admin_url( 'users.php' ),
	];
	amapress_admin_bar_add_items( $site_name_items, $admin_bar, 'site-name' );
}

function amapress_admin_bar_add_items( $items, WP_Admin_Bar $admin_bar, $parent ) {
	if ( ! empty( $items ) ) {
		foreach ( $items as $item ) {
			$item_with_default = wp_parse_args(
				$item,
				array(
					'id'         => null,
//					'icon'      => '',
					'title'      => '',
					'href'       => '#',
					'target'     => null,
					'class'      => null,
					'html'       => null,
					'parent'     => $parent,
					'capability' => null,
					'condition'  => null,
					'items'      => array(),
				)
			);

			if ( ! empty( $item_with_default['capability'] ) && ! current_user_can( $item_with_default['capability'] ) ) {
				continue;
			}

			if ( ! empty( $item_with_default['condition'] && ! call_user_func( $item_with_default['condition'] ) ) ) {
				continue;
			}

			$title = $item_with_default['title'];
			if ( ! empty( $item_with_default['icon'] ) ) {
				$title = amapress_get_font_icon( $item_with_default['icon'], 'ab-icon-submenu' ) . $title;
			}
			$meta = [];
			if ( ! empty( $item_with_default['target'] ) ) {
				$meta['target'] = $item_with_default['target'];
			}
			if ( ! empty( $item_with_default['html'] ) ) {
				$meta['html'] = $item_with_default['html'];
			}
			if ( ! empty( $item_with_default['class'] ) ) {
				$meta['class'] = $item_with_default['class'];
			}
			$admin_bar->add_menu( array(
				'id'     => $item_with_default['id'],
				'parent' => $item_with_default['parent'],
				'title'  => $title,
				'meta'   => $meta,
				'href'   => $item_with_default['href'],
			) );

			amapress_admin_bar_add_items( $item_with_default['items'], $admin_bar, $item_with_default['id'] );
		}
	}
}