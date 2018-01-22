<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'customize_nav_menu_available_item_types', function ( $item_types ) {
	//var_dump($item_types);
	return $item_types;
} );
add_filter( 'customize_nav_menu_available_items', function ( $items, $type, $object, $page ) {
	//var_dump($items);
	//var_dump($type);
	//var_dump($object);
	//var_dump($page);
	return $items;
}, 10, 4 );

add_action( 'admin_menu', 'amapress_change_admin_menu' );
function amapress_change_admin_menu() {
	foreach ( AmapressEntities::getMenu() as $m ) {
		if ( $m['type'] != 'page' ) {
			continue;
		}
		add_menu_page( do_shortcode( $m['title'] ), do_shortcode( $m['menu_title'] ),
			$m['capability'], $m['slug'],
			$m['function'], $m['icon'], $m['position'] );
	}

	foreach ( AmapressEntities::getMenu() as $m ) {
		if ( $m['type'] != 'panel' ) {
			continue;
		}

		foreach ( $m['subpages'] as $subpage ) {
			if ( isset( $subpage['subpage'] ) ) {
				continue;
			}
			$menu_icon = $subpage['menu_icon'];
			if ( $menu_icon == 'post_type' && isset( $subpage['post_type'] ) ) {
				$pt = get_post_type_object( amapress_unsimplify_post_type( $subpage['post_type'] ) );
				if ( ! $pt ) {
					die( $subpage['post_type'] );
				}
				$menu_icon = $pt->menu_icon;
			}
			$hook = add_submenu_page( $m['id'],
				do_shortcode( $subpage['title'] ),
				'<span class="dashicons-before ' . ( empty( $menu_icon ) ? 'dashicons-admin-post' : $menu_icon ) . '" /> ' . do_shortcode( $subpage['menu_title'] ),
				$subpage['capability'], $subpage['slug'], isset( $subpage['function'] ) ? $subpage['function'] : null );
			if ( isset( $subpage['hook'] ) ) {
				add_action( "load-$hook", $subpage['hook'] );
			}
		}
	}
}

add_filter( 'sanitize_html_class', 'menu_sanitize_html_class', 10, 2 );
function menu_sanitize_html_class( $sanitized, $class ) {
	if ( 0 === strpos( $class, 'dashicons-none' ) ) {
		return $class;
	}

	return $sanitized;
}

add_filter( 'submenu_file', 'amapress_highlight_menu', 99, 3 );
function amapress_highlight_menu( $submenu, $parent ) {
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
jQuery(function() {
              function search_user() {
                var val = jQuery(\'#amapress_search_user_text\').val();
                if (val == null || val == \'\') {
                    alert("Champs de recherche vide");
                    return;
                }
                window.location.href = \'' . admin_url( '/users.php' ) . '?s=\' + encodeURIComponent(val);
            }

            jQuery(\'#amapress_search_user_btn\').click(function () {
                search_user();
            });
            jQuery(\'#amapress_search_user_text\').keypress(function (e) {
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
			'title'  => '<input id="amapress_search_user_text" style="display: inline" type="text" placeholder="Recherche utilisateur" class=\'amapress_search_user form-control\' />
<span role="button" id="amapress_search_user_btn" style="display: inline" class="amapress_search_user dashicons-before dashicons-search"></span>' . $style . $script,
			'href'   => '#',
		) );
	}

	if ( 'post.php' == $pagenow || 'post-new.php' == $pagenow ) {
		$admin_bar->add_menu( array(
			'id'     => 'amapress_publish_admin_bar',
			'parent' => 'top-secondary',
			'title'  => '<button class=\'amapress_publish button button-primary\'>Enregistrer</button>',
			'href'   => '#',
		) );
	}

	if ( 'user-edit.php' == $pagenow || 'profile.php' == $pagenow ) {
		$admin_bar->add_menu( array(
			'id'     => 'amapress_update_user_admin_bar',
			'parent' => 'top-secondary',
			'title'  => '<button class=\'amapress_update_user button button-primary\'>Enregistrer</button>',
			'href'   => '#',
		) );
	}

	amapress_admin_bar_add_items( AmapressEntities::$admin_bar_menu, $admin_bar, null );
}

add_action( 'admin_bar_menu', 'amapress_admin_bar_new_entities', 900, 1 );
function amapress_admin_bar_new_entities( WP_Admin_Bar $admin_bar ) {
	$create_new_items = [];
	foreach ( AmapressEntities::getPostTypes() as $name => $conf ) {
		if ( isset( $conf['show_admin_bar_new'] ) && true === $conf['show_admin_bar_new'] && current_user_can( "publish_$name" ) ) {
			$internal_post_type = isset( $conf['internal_name'] ) ? $conf['internal_name'] : 'amps_' . $name;
			$create_new_items[] = [
				'id'    => "new-$internal_post_type",
//				'icon' => isset($conf['menu_icon']) ? $conf['menu_icon'] : null,
				'title' => $conf['singular'],
				'href'  => admin_url( "post-new.php?post_type=$internal_post_type" ),
			];
		}
	}
	amapress_admin_bar_add_items( $create_new_items, $admin_bar, 'new-content' );
}

function amapress_admin_bar_add_items( $items, WP_Admin_Bar $admin_bar, $parent ) {
	if ( ! empty( $items ) ) {
		foreach ( $items as $item ) {
			$item_with_default = wp_parse_args(
				$item,
				array(
					'id'        => null,
//					'icon'      => '',
					'title'     => '',
					'href'      => '#',
					'parent'    => $parent,
					'condition' => function () {
						return true;
					},
					'items'     => array(),
				)
			);

			if ( call_user_func( $item_with_default['condition'] ) ) {
				$title = $item_with_default['title'];
//				if ( ! empty( $item_with_default['icon'] ) ) {
//					$title = amapress_get_font_icon( $item_with_default['icon'] ) . $title;
//				}
				$admin_bar->add_menu( array(
					'id'     => $item_with_default['id'],
					'parent' => $item_with_default['parent'],
					'title'  => $title,
					'href'   => $item_with_default['href'],
				) );

				amapress_admin_bar_add_items( $item_with_default['items'], $admin_bar, $item_with_default['id'] );
			}
		}
	}
}