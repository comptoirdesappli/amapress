<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_make_item_obj( $id, $title, $url, $order = 0, $parent = 0 ) {
	// generic object made to look like a post object
	$item_obj                   = new stdClass();
	$item_obj->ID               = ( $id ) ? $id : 100000;
	$item_obj->title            = $title;
	$item_obj->url              = $url;
	$item_obj->menu_order       = $order;
	$item_obj->menu_item_parent = $parent;

	// menu specific properties
	$item_obj->db_id     = $item_obj->ID;
	$item_obj->type      = '';
	$item_obj->object    = '';
	$item_obj->object_id = '';

	// output attributes
	$item_obj->classes     = array();
	$item_obj->target      = '';
	$item_obj->attr_title  = '';
	$item_obj->description = '';
	$item_obj->xfn         = '';
	$item_obj->status      = '';

	return $item_obj;
}

add_filter( 'nav_menu_item_id', function ( $menu_id, $item, $args ) {
	$optionsPage = Amapress::resolve_post_id( Amapress::getOption( 'mes-infos-page' ), 'page' );
//    var_dump($item);
	if ( $optionsPage == $item->object_id ) {
		if ( amapress_is_user_logged_in() ) {
			return 'menu-item-amapress-mes-infos';
		} else {
			return 'menu-item-amapress-connecter';
		}
	}

	return $menu_id;
}, 10, 3 );

add_filter( 'wp_get_nav_menu_items', function ( $items, $menu ) {
	if ( is_admin() ) {
		return $items;
	}

//	$not_public_archive = [];
//	foreach ( AmapressEntities::getPostTypes() as $name => $config ) {
//		if ( isset( $config['logged_or_public'] ) && $config['logged_or_public'] ) {
//			$not_public_archive[] = get_post_type_archive_link( amapress_unsimplify_post_type( $name ) );
//		}
//	}

	$optionsPage = Amapress::resolve_post_id( Amapress::getOption( 'mes-infos-page' ), 'page' );
//    $base_url = trailingslashit(get_page_link($optionsPage));
	$all_items = array();
//    $item_id = count($items) + 1;
	foreach ( $items as $item ) {
		$item->title = do_shortcode( $item->title );
		if ( $item->object == 'page' && ! empty( $item->object_id ) ) {
			if ( $item->object_id == $optionsPage ) {
				$all_items[] = $item;
				if ( amapress_is_user_logged_in() ) {
//                    $item->title = __('Mes infos', 'amapress');
//                    $all_items[] = amapress_make_item_obj(0, __('Adhésions', 'amapress'), $base_url . 'adhesions/', $item_id++, $item->ID);
//                    if (Amapress::isIntermittenceEnabled()) {
//                        $all_items[] = amapress_make_item_obj(0, __('Mes paniers à céder <span class="badge">', 'amapress') . count(AmapressPaniers::getUserPaniersIntermittents()) . '</span>', $base_url . 'echange-paniers/', $item_id++, $item->ID);
//                        $all_items[] = amapress_make_item_obj(0, __('Paniers intermittents <span class="badge">', 'amapress') . count(AmapressPaniers::getPaniersIntermittentsToBuy()) . '</span>',
//                            Amapress::getPageLink('paniers-intermittents-page'), $item_id++, $item->ID);
//                    }
//                $items[] = amapress_make_item_obj(0, __('Commandes', 'amapress'), $base_url . 'commandes/', count($items) + 1, $item->ID);
//                    $all_items[] = amapress_make_item_obj(0, __('Messagerie <span class="badge">', 'amapress') . amapress_get_user_unread_message(amapress_current_user_id()) . '</span>', $base_url . 'messagerie/', $item_id++, $item->ID);
				} else {
					$item->title = __( 'Se connecter', 'amapress' );
					$item->url   = wp_login_url();
				}
			} else if ( $item->menu_item_parent == $optionsPage ) {
				if ( amapress_is_user_logged_in() ) {
					$all_items[] = $item;
				} else {
					if ( get_post_meta( $item->object_id, 'amps_lo', true ) != 1 || get_post_meta( $item->object_id, 'amps_rd', true ) ) {
						$all_items[] = $item;
					}
				}
			} else {
				if ( amapress_is_user_logged_in() ) {
					$all_items[] = $item;
				} else {
					if ( get_post_meta( $item->object_id, 'amps_lo', true ) != 1 || get_post_meta( $item->object_id, 'amps_rd', true ) ) {
						$all_items[] = $item;
					}
				}
			}
		} else {
			if ( amapress_is_user_logged_in() ) {
				$all_items[] = $item;
			} else {
				$the_id = $item->object_id;
				if ( function_exists( 'is_bbpress' ) ) {
					if ( is_bbpress() ) {
						$the_id = amapress_get_forum_id_from_post_id( $the_id );
					}
				}
				if ( 'custom' == $item->object && ! empty( $item->url ) ) {
					global $amapress_no_filter_amps_lo;
					$amapress_no_filter_amps_lo = true;
					$the_id                     = url_to_postid( $item->url );
					$amapress_no_filter_amps_lo = false;
					if ( ! $the_id && ! empty( $item->post_name ) ) {
						$the_id = get_page_by_path( $item->post_name );
					}
				}
				if ( get_post_meta( $the_id, 'amps_lo', true ) != 1 || get_post_meta( $the_id, 'amps_rd', true ) ) {
//					if ( ! in_array( $item->url, $not_public_archive ) ) {
					$all_items[] = $item;
//					}
				}
			}
		}
	}

	return $all_items;
}, 20, 2 );