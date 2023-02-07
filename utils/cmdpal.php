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
						'category'    => __( 'Etat d\'Amapress > ', 'amapress' ) . $labels[ $categ ],
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

function amapress_cmdpalette_amapress_items( $items ) {
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
}

add_filter( 'command_palette_items', 'amapress_cmdpalette_items' );

function amapress_cmdpalette_adminmenu_items( $items ) {
	global $menu, $submenu;

	function processAdminUrl( $text ) {
		if ( strpos( $text, '.php' ) !== false ) {
			return admin_url( $text );
		}

		return admin_url( 'admin.php?page=' . $text );
	}

	function removeSpan( $text ) {
		$text = preg_replace( '/<span.*<\/span>|<span.*\/>/s', '', $text );

		return trim( $text );
	}

	$items = array_merge( $items, array_map(
		function ( $menuItem ) use ( $items ) {
			return
				[
					'id'         => $menuItem[2],
					'category'   => __( 'Menu Tableau de bord', 'amapress' ),
					'capability' => $menuItem[1],
					'title'      => removeSpan( $menuItem[0] ),
					'url'        => processAdminUrl( $menuItem[2] ),
				];
		},
		array_filter( $menu, function ( $menuItem ) {
			return ! empty( $menuItem[0] );
		} )
	) );

	$items_by_id = array_combine( array_map( function ( $item ) {
		return $item['id'];
	}, $items ), $items );
	foreach ( $submenu as $parentMenu => $submenuItems ) {
		if ( ! isset( $items_by_id[ $parentMenu ] ) ) {
			continue;
		}
		foreach ( $submenuItems as $menuItem ) {
			$items[] =
				[
					'id'         => $menuItem[2] . '-' . $menuItem[1],
					'capability' => $menuItem[1],
					'title'      => removeSpan( $menuItem[0] ),
					'url'        => processAdminUrl( $menuItem[2] ),
					'category'   => __( 'Menu Tableau de bord > ', 'amapress' ) . $items_by_id[ $parentMenu ]['title'],
				];
		}
	}

	return $items;
}

function amapress_searchparams_tab() {
	ob_start();
	echo '<p>' . __( 'Cette page vous permet de rechercher dans le backoffice pour les paramètres, options et menus.', 'amapress' ) . '</p>';
	echo '<br/>';

	$searchparams = ! empty( $_REQUEST['searchparam'] ) ? $_REQUEST['searchparam'] : '';
	echo '<label for="searchparam">' . __( 'Recherche : ', 'amapress' ) . '</label>
<input type="search" style="display: inline-block; min-width: 50%;" id="searchparam" name="searchparam" value="' . esc_attr( $searchparams ) . '" placeholder="' . __( 'option, paramètre, menu', 'amapress' ) . '" />
<button type="submit" class="button button-primary"><i class="dashicons dashicons-search" style="vertical-align:middle"></i></button>';


	$items = [];
	if ( ! empty( $_REQUEST['searchparam'] ) ) {
		$items = amapress_cmdpalette_adminmenu_items( $items );
		$items = amapress_cmdpalette_amapress_items( $items );

		$searchparams_noaccent = strtolower( remove_accents( $searchparams ) );
		$items                 = array_filter( $items, function ( $item ) use ( $searchparams_noaccent ) {
			return ( false !== strpos( remove_accents( strtolower( $item['title'] ) ), $searchparams_noaccent )
			         || ( ! empty( $item['description'] ) && false !== strpos( remove_accents( strtolower( $item['description'] ) ), $searchparams_noaccent ) ) )
			       && amapress_current_user_can( $item['capability'] );
		} );
	}

	echo '<hr/>';
	echo '<table id="searchparamstable" class="table display responsive" style="width: 100%">';
	echo '<thead>
<th>' . __( 'Catégorie', 'amapress' ) . '</th>
<th>' . __( 'Elément', 'amapress' ) . '</th>
</thead>';
	echo '<tbody>';
	foreach ( $items as $item ) {
		echo '<tr>
<td>' . esc_html( ! empty( $item['category'] ) ? $item['category'] : '' ) . '</td>
<td>' . Amapress::makeLink( $item['url'], $item['title'], true, true ) .
		     ( ! empty( $item['description'] ) ? '<br/>' . esc_html( $item['description'] ) : '' ) . '</td>
</tr>';
	}
	echo '</tbody>';
	echo '</table>';
	echo '<script type="application/javascript">jQuery(function($) { $("#searchparamstable").DataTable(); })</script>';


	return ob_get_clean();
}
