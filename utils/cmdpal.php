<?php

function amapress_command_palette_menu_items( $items ) {
	foreach ( AmapressEntities::getMenu() as $item ) {
		if ( empty( $item['id'] ) ) {
			continue;
		}

		$page_id           = $item['id'];
		$page_url          = admin_url( 'admin.php?page=' . $page_id );
		$parent_capability = 'read';
//		$icon       = '';
		if ( ! empty( $item['settings']['capability'] ) ) {
			$parent_capability = $item['settings']['capability'];
		}
//		if ( ! empty( $item['settings']['icon'] ) ) {
//			$icon = $item['settings']['icon'];
//		} elseif ( ! empty( $item['settings']['menu_icon'] ) ) {
//			$icon = $item['settings']['menu_icon'];
//		}
		$subtitle = '';
		if ( ! empty( $item['settings']['name'] ) ) {
			$subtitle = $item['settings']['name'];
		}
		if ( ! empty( $item['settings']['menu_title'] ) ) {
			$menu_subtitle = $item['settings']['menu_title'];
			$subtitle      = ! empty( $subtitle ) && $menu_subtitle != $subtitle ? "$menu_subtitle ($subtitle)" : $menu_subtitle;
		}
		$desc = '';
		if ( ! empty( $item['settings']['search_desc'] ) ) {
			$desc = $item['settings']['search_desc'];
		}
		foreach ( $items as $it ) {
			if ( $it['url'] == $page_url ) {
				if ( $it['title'] != $desc ) {
					if ( empty( $it['description'] ) ) {
						$it['description'] = $desc;
					}
				}
			}
		}
		if ( ! empty( $item['tabs'] ) && is_array( $item['tabs'] ) ) {
			foreach ( $item['tabs'] as $tab_name => $tab ) {
				if ( ! empty( $tab['capability'] ) ) {
					$capability = $tab['capability'];
				} else {
					$capability = $parent_capability;
				}
//				if ( ! empty( $tab['icon'] ) ) {
//					$icon = $tab['icon'];
//				} elseif ( ! empty( $tab['menu_icon'] ) ) {
//					$icon = $tab['menu_icon'];
//				}
				if ( isset( $tab['id'] ) ) {
					$tab_id = $tab['id'];
				} else {
					$tab_id = $tab_name;
				}

				$desc = '';
				if ( ! empty( $tab['search_desc'] ) ) {
					$desc = $tab['search_desc'];
				}
				$items[] = [
					'type'        => __( 'Link', 'command-palette' ),
					'id'          => $page_id . '-' . $tab_id,
					'capability'  => $capability,
					'title'       => $tab_name,
					'url'         => $page_url . '&tab=' . $tab_id,
					'description' => wp_strip_all_tags( $desc ),
					'category'    => $subtitle,
				];
			}
		}
		if ( ! empty( $item['subpages'] ) ) {
			foreach ( $item['subpages'] as $subitem ) {
				if ( ! is_array( $subitem ) ) {
					continue;
				}
				if ( empty( $subitem['id'] ) ) {
					continue;
				}

				$page_id  = $subitem['id'];
				$page_url = admin_url( 'admin.php?page=' . $page_id );

				if ( ! empty( $subitem['settings']['capability'] ) ) {
					$capability = $subitem['settings']['capability'];
				} else {
					$capability = $parent_capability;
				}
//				if ( ! empty( $subitem['settings']['icon'] ) ) {
//					$icon = $subitem['settings']['icon'];
//				} elseif ( ! empty( $item['settings']['menu_icon'] ) ) {
//					$icon = $subitem['settings']['menu_icon'];
//				}
				$subsubtitle = '';
				if ( ! empty( $subitem['settings']['name'] ) ) {
					$subsubtitle = $subitem['settings']['name'];
				}
				if ( ! empty( $subitem['settings']['menu_title'] ) ) {
					$menu_subtitle = $subitem['settings']['menu_title'];
					$subsubtitle   = ! empty( $subsubtitle ) && $subsubtitle != $menu_subtitle ? "$menu_subtitle ($subsubtitle)" : $menu_subtitle;
				}
				$desc = '';
				if ( ! empty( $subitem['settings']['search_desc'] ) ) {
					$desc = $subitem['settings']['search_desc'];
				}
				foreach ( $items as $it ) {
					if ( $it['url'] == $page_url ) {
						if ( $it['title'] != $desc ) {
							if ( empty( $it['description'] ) ) {
								$it['description'] = $desc;
							}
						}
					}
				}
				if ( ! empty( $subitem['tabs'] ) && is_array( $subitem['tabs'] ) ) {
					foreach ( $subitem['tabs'] as $tab_name => $tab ) {
						if ( ! empty( $tab['capability'] ) ) {
							$capability = $tab['capability'];
						} else {
							$capability = $parent_capability;
						}
//						if ( ! empty( $tab['icon'] ) ) {
//							$icon = $tab['icon'];
//						} elseif ( ! empty( $tab['menu_icon'] ) ) {
//							$icon = $tab['menu_icon'];
//						}

						if ( isset( $tab['id'] ) ) {
							$tab_id = $tab['id'];
						} else {
							$tab_id = $tab_name;
						}

						$desc = '';
						if ( ! empty( $subitem['settings']['search_desc'] ) ) {
							$desc = $subitem['settings']['search_desc'];
						}

						$items[] = [
							'type'        => __( 'Link', 'command-palette' ),
							'id'          => $page_id . '-' . $tab_id,
							'capability'  => $capability,
							'title'       => $tab_name,
							'url'         => $page_url . '&tab=' . $tab_id,
							'description' => wp_strip_all_tags( $desc ),
							'category'    => $subsubtitle,
						];
					}
				}
			}
		}
	}

	return $items;
}

function amapress_command_palette_state_items( $items ) {
	if ( current_user_can( 'manage_options' ) ) {
		$key         = 'amapress_state_check_titles';
		$state_items = get_transient( $key );
		if ( false === $state_items ) {
			$state       = amapress_get_state();
			$labels      = amapress_state_labels();
			$state_items = [];
			foreach ( $state as $categ => $checks ) {
				foreach ( $checks as $check ) {
					$url = admin_url( 'admin.php?page=amapress_state' );
					if ( false === strpos( $check['link'], 'update.php' ) ) {
						$url = $check['link'];
					}
					$state_items[] = [
						'type'        => __( 'Link', 'command-palette' ),
						'id'          => 'state' . uniqid(),
						'capability'  => 'manage_options',
						'title'       => wp_strip_all_tags( $check['name'] ),
						'url'         => $url,
						'description' => '',
						'category'    => __( 'Etat d\'Amapress/', 'amapress' ) . $labels[ $categ ],
					];
				}
			}
			set_transient( $key, $state_items );
		}
		$items = array_merge( $items, $state_items );
	}

	return $items;
}

function amapress_command_palette_pages_items( $items ) {
	if ( current_user_can( 'edit_pages' ) ) {
		/** @var WP_Post $page */
		foreach ( get_pages() as $page ) {
			$items[] = [
				'type'        => __( 'Link', 'command-palette' ),
				'id'          => 'page' . $page->ID,
				'capability'  => 'edit_pages',
				'title'       => $page->post_title,
				'url'         => admin_url( 'post.php?post=' . $page->ID . '&action=edit' ),
				'description' => '',
				'category'    => __( 'Pages', 'amapress' ),
			];
		}
	}

	return $items;
}

function amapress_command_palette_shortcodes_items( $items ) {
	global $all_amapress_shortcodes_descs;
	ksort( $all_amapress_shortcodes_descs );
	foreach ( $all_amapress_shortcodes_descs as $k => $desc ) {
		if ( empty( $desc['desc'] ) ) {
			continue;
		}
		$items[] = [
			'type'        => __( 'Link', 'command-palette' ),
			'id'          => 'sh' . uniqid(),
			'capability'  => 'read',
			'title'       => $k,
			'url'         => admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ),
			'description' => $desc['desc'],
			'category'    => __( 'Shortcodes', 'amapress' ),
		];
	}


	return $items;
}

add_filter( 'command_palette_items', function ( $items ) {
	$items = array_merge(
		[
			[
				'type'        => __( 'Documentation', 'amapress' ),
				'id'          => 'sh' . uniqid(),
				'capability'  => 'read',
				'title'       => __( 'Documentation Amapress', 'amapress' ),
				'url'         => 'https://wiki.amapress.fr/accueil',
				'description' => '',
				'category'    => __( 'Documentation', 'amapress' ),
			]
		], $items
	);
	$items = amapress_command_palette_menu_items( $items );
	$items = amapress_command_palette_pages_items( $items );
	$items = amapress_command_palette_state_items( $items );
	$items = amapress_command_palette_shortcodes_items( $items );

	return $items;
} );