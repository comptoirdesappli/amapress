<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * class short summary.
 *
 * class description.
 *
 * @version 1.0
 * @author Guillaume
 */
class AmapressUsers {
	public static $initiated = false;

//	private static $vp = null;

	public static function to_displayname( $user ) {
		$dn = $user->display_name;
		if ( ! empty( $user->last_name ) ) {
			$dn = sprintf( '%s %s', $user->first_name, $user->last_name );
		}

		return $dn;
//        return '<a href="' . get_author_posts_url($user->ID) . '">' . $dn . '</a>';
	}

	public static function init() {
		add_action( 'admin_head-user-edit.php', array( 'AmapressUsers', 'remove_user_unused_fields' ) );
		add_action( 'admin_head-profile.php', array( 'AmapressUsers', 'remove_user_unused_fields' ) );
		amapress_register_shortcode( 'users_near', array( 'AmapressUsers', 'users_near_shortcode' ) );
		amapress_register_shortcode( 'trombinoscope', array( 'AmapressUsers', 'trombinoscope_shortcode' ) );
		amapress_register_shortcode( 'trombinoscope_lieu', array( 'AmapressUsers', 'trombinoscope_lieu_shortcode' ) );
		amapress_register_shortcode( 'trombinoscope_role', array( 'AmapressUsers', 'trombinoscope_role_shortcode' ) );
		// enqueue and localise scripts
//        wp_enqueue_script('userlikes-handle', plugin_dir_url(__FILE__) . 'js/ajax-userlikes.js', array('jquery'));
//        wp_localize_script('userlikes-handle', 'user_produit_likebox', array('ajax_url' => admin_url('admin-ajax.php')));
		// THE AJAX ADD ACTIONS
//        add_action('wp_ajax_user_likebox_action', array('AmapressUsers', 'user_likebox_produit_action'));
//        add_action('wp_ajax_nopriv_user_likebox_action', array('AmapressUsers', 'user_likebox_produit_action'));

		add_filter( 'amapress_gallery_render_user_cell', 'AmapressUsers::amapress_gallery_render_user_cell' );
		add_filter( 'amapress_gallery_render_user_cell_contact', 'AmapressUsers::amapress_gallery_render_user_cell_contact' );
		add_filter( 'amapress_gallery_render_user_cell_with_role', 'AmapressUsers::amapress_gallery_render_user_cell_with_role' );


//        if (!self::$vp) self::$vp = new Virtual_Themed_Pages_BC();
//		self::$vp->add('#/amapiens-autour-de-(moi|.+)#i', array('AmapressUsers','virtual_aroundme'));
//		self::$vp->add('#/mon-profile#i', array('AmapressUsers','virtual_mon_profile'));
//		self::$vp->add('#/trombinoscope#i', array('AmapressUsers','virtual_trombi'));
	}

	public static function amapress_gallery_render_user_cell( $user ) {
		$usr = $user;
		if ( is_int( $usr ) ) {
			$usr = get_user_by( 'id', $usr );
		}

		if ( ! $usr ) {
			return '';
		}

		ob_start();

		self::echoUser( $usr, 'thumb' );

		$content = ob_get_clean();

		return $content;
	}

	public static function amapress_gallery_render_user_cell_contact( $user ) {
		$usr = $user;
		if ( is_int( $usr ) ) {
			$usr = get_user_by( 'id', $usr );
		}

		if ( ! $usr ) {
			return '';
		}

		ob_start();

		self::echoUser( $usr, array( 'telephone', 'mail' ) );

		$content = ob_get_clean();

		return $content;
	}

	public static function amapress_gallery_render_user_cell_with_role( $user ) {
		$usr = $user['user'];
		if ( is_int( $usr ) ) {
			$usr = get_user_by( 'id', $usr );
		}
		if ( ! $usr ) {
			return '';
		}

		ob_start();

		self::echoUser( $usr, 'thumb', $user['link'], $user['role'] );

		$content = ob_get_clean();

		return $content;
	}

	static function remove_user_unused_fields() {
		echo '<style>
                tr.user-rich-editing-wrap{ display: none; }
                tr.user-admin-color-wrap{ display: none; }
                tr.user-comment-shortcuts-wrap{ display: none; }
                tr.user-admin-bar-front-wrap{ display: none; }
                /*tr.user-profile-picture{ display: none; }*/
                /*tr.user-description-wrap{ display: none; }*/
                tr.user-url-wrap{ display: none; }
              </style>';
	}

	public static function echoUserById( $user_id, $type, $custom_link = null, $custom_role = null ) {
		$user = get_user_by( 'id', $user_id );
		if ( empty( $user ) ) {
			return;
		}
		AmapressUsers::echoUser( $user, $type, $custom_link, $custom_role );
	}

	public static function echoUser( WP_User $user, $type, $custom_link = null, $custom_role = null ) {
		if ( ! amapress_is_user_logged_in() ) {
			$type = 'thumb';
		}
		$types = array();
		if ( is_string( $type ) ) {
			$types[] = $type;
		}

		$amapien = AmapressUser::getBy( $user );

		echo '<div class="user user-' . implode( '_', $user->roles ) . '">';
//        $url = amapress_get_avatar_url($user->ID, null, 'user-thumb', 'default_amapien.jpg', 1);
		$img = get_avatar( $user->ID );
		echo '<div class="user-photo">' . $img . '</div>';

//        $dn = $user->display_name;
//        if (!empty($user->last_name)) {
//            $dn = sprintf('%s %s', $user->first_name, $user->last_name);
//        }
		$dn = $amapien->getDisplayName();

		//echo '<p><a href="'.get_author_posts_url($user->ID).'">'.$user->display_name.'</a></p>';
		if ( ! in_array( 'no-name', $types ) ) {
			echo '<p class="user-name">' . ( ! empty( $custom_link ) ? '<a href="' . $custom_link . '">' . $dn . '</a>' : $dn ) . '</p>';
			//echo '<p class="user-name">'.$dn.'</p>';
		}
		if ( ! empty( $custom_role ) ) {
			echo '<p class="user-role">' . $custom_role . '</p>';
		} else {
			if ( ! in_array( 'no-role', $types ) ) {
				$role_desc = $amapien->getAmapRolesString();
				if ( ! empty( $role_desc ) ) {
					echo '<p class="user-role">' . $role_desc . '</p>';
				}
			}
		}
		if ( $type == 'thumb' ) {
			echo '</div>';

			return;
		}

		if ( in_array( 'telephone', $types ) || $type == 'full' ) {
			if ( get_post_meta( $user->ID, 'amapress_user_telephone', true ) ) {
				echo '<p class="user-phone">Téléphone : ' . get_user_meta( $user->ID, 'amapress_user_telephone', true ) . '</p>';
			}
			if ( get_post_meta( $user->ID, 'amapress_user_telephone2', true ) ) {
				echo '<p class="user-phone2">Téléphone 2 : ' . get_user_meta( $user->ID, 'amapress_user_telephone2', true ) . '</p>';
			}
		}
		if ( in_array( 'mail', $types ) || $type == 'full' ) {
			if ( $user->user_email ) {
				echo '<p class="user-mail">Mail : <a href="mailto:' . $user->user_email . '">' . $user->user_email . '</a></p>';
			}
		}
		if ( get_post_meta( $user->ID, 'amapress_user_adresse', true ) &&
		     ( amapress_current_user_can( 'responsable_amap' ) || amapress_current_user_can( 'administrator' ) || in_array( 'adresse', $types ) || $type == 'full' )
		) {
			echo '<p>Adresse : <pre>' . get_user_meta( $user->ID, 'amapress_user_adresse', true ) . '\n' . get_user_meta( $user->ID, 'amapress_user_code_postal', true ) . ' ' . get_user_meta( $user->ID, 'amapress_user_ville', true ) . '</pre></p>';
		}
		if ( get_post_meta( $user->ID, 'amapress_user_location_type', true ) &&
		     ( amapress_current_user_can( 'responsable_amap' ) || amapress_current_user_can( 'administrator' ) || in_array( 'adresse-loc-link', $types ) || $type == 'full' )
		) {
			echo '<a href="http://maps.google.com/maps?q=' . get_post_meta( $user->ID, 'amapress_user_lat', true ) . ',' . get_post_meta( $user->ID, 'amapress_user_long', true ) . '">Voir sur Google Maps</a>';
		}
		if ( get_post_meta( $user->ID, 'amapress_user_location_type', true ) &&
		     ( amapress_current_user_can( 'responsable_amap' ) || amapress_current_user_can( 'administrator' ) || in_array( 'adresse-loc-map', $types ) || $type == 'full' )
		) {
			echo do_shortcode( "[user-map user={$user->ID} mode=map" );
		}
		echo '</div>';
	}

	static function virtual_aroundme( $v, $url ) {
		if ( is_admin() ) {
			return;
		}
		if ( ! amapress_is_user_logged_in() ) {
			$v->redirect = '/wp-login.php';

			return;
		}
		$v->template = 'page'; // optional
		if ( preg_match( '#amapiens-autour-de-(moi|.+)#', $url, $m ) ) {
			if ( $m[1] == 'moi' ) {
				$v->body  = do_shortcode( '[users_near]' );
				$v->title = 'Les amapiens proches de moi';
			} else {
				$v->body  = do_shortcode( '[users_near user="' . $m[1] . '"]' );
				$v->title = 'Les amapiens proches de ' . $m[1];
			}
		}
	}

	public static function distance( $lat1, $lon1, $lat2, $lon2, $unit ) {
		$theta = $lon1 - $lon2;
		$dist  = sin( deg2rad( $lat1 ) ) * sin( deg2rad( $lat2 ) ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * cos( deg2rad( $theta ) );
		$dist  = acos( $dist );
		$dist  = rad2deg( $dist );
		$miles = $dist * 60 * 1.1515;
		$unit  = strtoupper( $unit );

		if ( $unit == "K" ) {
			return ( $miles * 1.609344 );
		} else if ( $unit == "N" ) {
			return ( $miles * 0.8684 );
		} else {
			return $miles;
		}
	}

	public static function users_near_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'count' => 10,
			'user'  => amapress_current_user_id(),
		), $atts, 'users_near' );

//        $lieu_ids = AmapressUsers::get_current_user_lieu_ids();

		ob_start();
		$user_id = AmapressUsers::get_user_id( $atts['user'] );
		$loc     = get_user_meta( $user_id, 'amapress_user_location_type', true );
		if ( empty( $loc ) ) {
			if ( $user_id == amapress_current_user_id() ) {
				return 'Votre adresse n\'est pas localisée.';
			} else {
				return 'Adresse non localisée.';
			}
		}

		$lat = floatval( get_user_meta( $user_id, 'amapress_user_lat', true ) );
		$lng = floatval( get_user_meta( $user_id, 'amapress_user_long', true ) );

		$users = get_users( array(
			'meta_query' => array(
				'relation' => 'OR',
				array( 'key' => 'pw_user_status', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'pw_user_status', 'value' => 'approved', 'compare' => '=' ),
			),
			'order'      => 'ASC',
			'orderby'    => 'display_name',
			'exclude'    => array( $user_id ),
		) );

		$users_dists = array();
		foreach ( $users as $user ) {
			$loc = get_user_meta( $user_id, 'amapress_user_location_type', true );
			if ( ! empty( $loc ) ) {
				$u_lat         = floatval( get_user_meta( $user->ID, 'amapress_user_lat', true ) );
				$u_lng         = floatval( get_user_meta( $user->ID, 'amapress_user_long', true ) );
				$users_dists[] = array(
					'user' => $user,
					'dist' => AmapressUsers::distance( $lat, $lng, $u_lat, $u_lng, 'K' )
				);
			}
		}
		usort( $users_dists, array( 'AmapressUsers', 'sort_user_dist' ) );

		$cnt = count( $users_dists );
		if ( $cnt > $atts['count'] ) {
			$cnt = $atts['count'];
		}
		echo '<table>';
		echo '<tr><th>Amapien</th><th>Distance</th></tr>';
		for ( $i = 0; $i < $cnt; $i ++ ) {
			echo '<tr><td><a href="' . get_author_posts_url( $users_dists[ $i ]['user']->ID ) . '">' . $users_dists[ $i ]['user']->display_name . '</a></td><td>' . $users_dists[ $i ]['dist'] . ' km</td></tr>';
		}
		echo '</table>';

		$t = ob_get_contents();
		ob_end_clean();

		return $t;
	}

	public static function sort_user_dist( $a, $b ) {
		if ( $a['dist'] < $b['dist'] ) {
			return - 1;
		} else if ( $a['dist'] > $b['dist'] ) {
			return 1;
		} else {
			return 0;
		}
	}

	public static function trombinoscope_role_shortcode( $atts ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'role' => 'all',
			'lieu' => null,
		), $atts, 'trombinoscope_role' );

		if ( ! empty( $atts['lieu'] ) ) {
			$lieu_id = Amapress::resolve_post_id( $atts['lieu'], AmapressLieu_distribution::INTERNAL_POST_TYPE );
			if ( $lieu_id ) {
				$lieu_ids = array( $lieu_id );
			} else {
				$lieu_ids = Amapress::get_lieu_ids();
			}
		} else {
			if ( amapress_can_access_admin() ) {
				$lieu_ids = Amapress::get_lieu_ids();
			} else {
				$lieu_ids = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
			}
		}

		$base_query = array(
			'meta_query'    => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array( 'key' => 'pw_user_status', 'compare' => 'NOT EXISTS' ),
					array( 'key' => 'pw_user_status', 'value' => 'approved', 'compare' => '=' ),
				)
			),
			'amapress_lieu' => $lieu_ids,
			'order'         => 'ASC',
			'orderby'       => 'display_name',
		);

		$role = $atts['role'];
		if ( $role == 'producteurs' ) {
			$args = wp_parse_args(
				array( 'role' => 'producteur' ),
				$base_query );
			unset( $args['amapress_lieu'] );
			$users = get_users( $args );
		} else if ( $role == 'responsables' ) {
			$users    = get_users( wp_parse_args(
				array( 'amapress_role' => 'amap_role_any' ),
				$base_query ) );
			$user_ids = array_map( function ( $u ) {
				return $u->ID;
			}, $users );
			$admins   = get_users( wp_parse_args(
				array( 'amapress_role' => 'access_admin' ),
				$base_query ) );
			foreach ( $admins as $user ) {
				if ( in_array( $user->ID, $user_ids ) ) {
					continue;
				}
				$users[] = $user;
			}
		} else if ( $role == 'referents_lieux' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_role' => 'referent_lieu' ),
				$base_query ) );

		} else if ( $role == 'referents_producteurs' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_role' => 'referent_producteur' ),
				$base_query ) );
		} else if ( $role == 'amapiens' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_contrat' => 'active' ),
				$base_query ) );
		} else if ( $role == 'prochaine_distrib' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_role' => 'resp_distrib' ),
				$base_query ) );

			if ( count( $users ) == 0 ) {
				return 'Pas de responsable(s) inscrit(s)';
			}
		} else {
			$users = array();
		}

		usort( $users, function ( $a, $b ) {
			return strcmp( $a->display_name, $b->display_name );
		} );

		$n = implode( '-', $lieu_ids );

		return amapress_generic_gallery( $users, "trombi-$n-$role", 'user_cell' );
	}

	public static function get_user_id( $user ) {
		if ( is_numeric( $user ) ) {
			return intval( $user );
		}
		$user_id = - 1;
		if ( is_string( $user ) ) {
			$user_object = get_user_by( 'slug', $user );
			if ( $user_object ) {
				return $user_object->ID;
			}
			$user_object = get_user_by( 'login', $user );
			if ( $user_object ) {
				return $user_object->ID;
			}
			$user_object = get_user_by( 'email', $user );
			if ( $user_object ) {
				return $user_object->ID;
			}
		}

		return $user_id;
	}

	public static function trombinoscope_shortcode() {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$lieu_ids = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
		$lieux    = get_posts( array(
			'post_type'      => 'amps_lieu',
			'posts_per_page' => - 1,
			'include'        => $lieu_ids
		) );

		amapress_echo_panel_start( 'Les responsables de l\'AMAP' );
		echo do_shortcode( '[trombinoscope_role role=responsables]' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les producteurs' );
		echo do_shortcode( '[trombinoscope_role role=producteurs]' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les référents producteurs' );
		echo do_shortcode( '[trombinoscope_role role=referents_producteurs]' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les référents lieux de distribution' );
		echo do_shortcode( '[trombinoscope_role role=referents_lieux]' );
		amapress_echo_panel_end();

		foreach ( $lieux as $lieu ) {
			if ( count( $lieux ) > 1 ) {
				echo '<h2>' . $lieu->post_title . '</h2>';
			}
			echo do_shortcode( '[trombinoscope_lieu lieu=' . $lieu->ID . ']' );
		}

		$t = ob_get_clean();

		return $t;
	}

	public static function trombinoscope_lieu_shortcode( $atts ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'lieu' => null,
		), $atts, 'trombinoscope_lieu' );

		$lieu_id = Amapress::get_lieu_id( $atts['lieu'] );
		//$lieu = get_post($lieu_id);
		ob_start();

		//echo '<h2>'.$lieu->post_title.'</h2>';
		amapress_echo_panel_start( 'Les responsables à la prochaine distribution', null, 'amap-panel-resp-dist' );
		echo do_shortcode( '[trombinoscope_role role=prochaine_distrib lieu=' . $lieu_id . ']' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les amapiens', null, 'amap-panel-amapiens' );
		echo do_shortcode( '[trombinoscope_role role=amapiens lieu=' . $lieu_id . ']' );
		amapress_echo_panel_end();

		$t = ob_get_clean();

		return $t;
	}

//    public static function get_current_user_lieu_ids()
//    {
//        if (amapress_current_user_can('responsable_amap') || amapress_current_user_can('producteur') || amapress_current_user_can('administrator'))
//            $lieu_ids = array_map(array('Amapress', 'to_id'), get_posts(array(
//                'posts_per_page' => -1,
//                'post_type' => 'amps_lieu'
//            )));
//        else {
//            $abo_ids = AmapressContrats::get_active_contrat_instances_ids();
//            $user_ids = AmapressContrats::get_related_users(amapress_current_user_id(), false);
//            $lieu_ids = array_map(array('Amapress', 'to_adhesion_lieu'), get_posts(array(
//                'post_type' => 'amps_adhesion',
//                'posts_per_page' => -1,
//                'meta_query' => array(
//                    'relation' => 'AND',
//                    array(
//                        'key_num' => 'amapress_adhesion_contrat_instance',
//                        'value' => $abo_ids,
//                        'compare' => 'IN'),
//                    array('relation' => 'OR',
//                        array(
//                            'key' => 'amapress_adhesion_adherent',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_adhesion_adherent2',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_adhesion_adherent3',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                    ),
//                ))));
//        }
//        return $lieu_ids;
//    }

	public static function get_user_lieu_ids( $user_id, $date = null, $ignore_renouv_delta = false ) {
		$abo_ids = AmapressContrats::get_active_contrat_instances_ids( null, $date, $ignore_renouv_delta );
		$abo_key = implode( '-', $abo_ids );
		$key     = "amapress_get_user_lieu_ids_$user_id-$abo_key";

		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$user_ids = AmapressContrats::get_related_users( $user_id );
			$lieu_ids = array_map( array( 'Amapress', 'to_adhesion_lieu' ), get_posts( array(
				'post_type'      => 'amps_adhesion',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_contrat_instance',
						'value'   => amapress_prepare_in( $abo_ids ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_adhesion_adherent',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'amapress_adhesion_adherent2',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'amapress_adhesion_adherent3',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'NUMERIC'
						),
					),
				)
			) ) );

			$res = array_unique( $lieu_ids );
			wp_cache_set( $key, $res );
		}

		return $res;
	}


//    public static function like_unlike_produit($user_id, $produit_id, $like)
//    {
//        $produit = get_post($produit_id);
//        $user = get_user_by('id', $user_id);
//        $user_produit_likes = get_posts(array(
//            'posts_per_page' => -1,
//            'post_type' => 'amps_user_produit_like',
//            'meta_query' => array(
//                'relation' => 'AND',
//                array(
//                    'key' => 'amapress_user_produit_like_user',
//                    'value' => $user_id,
//                ),
//                array(
//                    'key' => 'amapress_user_produit_like_produit',
//                    'value' => $produit_id,
//                ),
//            ),
//        ));
//        $like_cnt = get_post_meta($produit_id, 'amapress_produit_likes', true);
//        if (!$like_cnt) $like_cnt = 0;
//        $unlike_cnt = get_post_meta($produit_id, 'amapress_produit_unlikes', true);
//        if (!$unlike_cnt) $unlike_cnt = 0;
//
//        $insert = true;
//        if ($like == 0) {
//            $insert = false;
//            if (count($user_produit_likes) > 0) {
//                foreach ($user_produit_likes as $user_produit_like) {
//                    $v = get_post_meta($user_produit_like->ID, '', true);
//                    if ($v < 0)
//                        $unlike_cnt--;
//                    else if ($v > 0)
//                        $like_cnt--;
//
//                    delete_post($user_produit_like->ID);
//                }
//            }
//        } else if ($like > 0) {
//            if (count($user_produit_likes) > 0) {
//                foreach ($user_produit_likes as $user_produit_like) {
//                    $del = true;
//                    $v = get_post_meta($user_produit_like->ID, 'amapress_user_produit_like_vote', true);
//                    if ($v < 0)
//                        $unlike_cnt--;
//                    else if ($v > 0) {
//                        $insert = false;
//                        $del = false;
//                    }
//
//                    if ($del) delete_post($user_produit_like->ID);
//                }
//            } else
//                $like_cnt++;
//        } else {
//            if (count($user_produit_likes) > 0) {
//                foreach ($user_produit_likes as $user_produit_like) {
//                    $del = true;
//                    $v = get_post_meta($user_produit_like->ID, 'amapress_user_produit_like_vote', true);
//                    if ($v > 0)
//                        $like_cnt--;
//                    else if ($v < 0) {
//                        $insert = false;
//                        $del = false;
//                    }
//
//                    if ($del) delete_post($user_produit_like->ID);
//                }
//            } else
//                $unlike_cnt++;
//        }
//        if ($insert) {
//            $my_post = array(
//                'post_title' => 'L',
//                'post_content' => ($like > 0 ? 'L' : 'U'),
//                'post_status' => 'publish',
//            );
//            $id = wp_insert_post($my_post);
//            if ($id > 0) {
//                update_post_meta($id, 'amapress_user_produit_like_user', $user_id);
//                update_post_meta($id, 'amapress_user_produit_like_produit', $produit_id);
//                update_post_meta($id, 'amapress_user_produit_like_vote', $like);
//            } else
//                return;
//        }
//        update_post_meta($produit_id, 'amapress_produit_likes', $like_cnt);
//        update_post_meta($produit_id, 'amapress_produit_unlikes', $unlike_cnt);
//    }

//    public static function get_user_produit_likebox($user_id, $produit_id)
//    {
//        $produit = get_post($produit_id);
//        $user = get_user_by('id', $user_id);
//        $user_produit_likes = get_posts(array(
//            'posts_per_page' => -1,
//            'post_type' => 'amps_user_produit_like',
//            'meta_query' => array(
//                'relation' => 'AND',
//                array(
//                    'key' => 'amapress_user_produit_like_user',
//                    'value' => $user_id,
//                ),
//                array(
//                    'key' => 'amapress_user_produit_like_produit',
//                    'value' => $produit_id,
//                ),
//            ),
//        ));
//        $like_cnt = get_post_meta($produit_id, 'amapress_produit_likes', true);
//        if (!$like_cnt) $like_cnt = 0;
//        $unlike_cnt = get_post_meta($produit_id, 'amapress_produit_unlikes', true);
//        if (!$unlike_cnt) $unlike_cnt = 0;
//
//        $user_like = 0;
//        foreach ($user_produit_likes as $user_produit_like) {
//            $user_like = intval(get_post_meta($user_produit_like->ID, 'amapress_user_produit_like_vote', true));
//        }
//
//        $cls = 'none';
//        if ($user_like > 0) $cls = 'like';
//        if ($user_like < 0) $cls = 'unlike';
//        return '<div class="produit-likebox">' . sprintf('%d likes / %d unlikes', $like_cnt, $unlike_cnt) . ' - <span class="produit-like-button like-' . $cls . '" data-produit="' . $produit_id . '" data-like="' . ($user_like <= 0 ? 1 : 0) . '">Like</span> - <span class="produit-unlike-button unlike-' . $cls . '" data-produit="' . $produit_id . '" data-like="' . ($user_like >= 0 ? -1 : 0) . '">Unlike</span></div>';
//    }
//
//    function user_likebox_produit_action()
//    {
//        /* this area is very simple but being serverside it affords the possibility of retreiving data from the server and passing it back to the javascript function */
//        $produit_id = intval($_POST['produit']);
//        $user_id = amapress_current_user_id();
//        $like = intval($_POST['like']);
//        AmapressUsers::like_unlike_produit($user_id, $produit_id, $like);
//        echo AmapressUsers::get_user_produit_likebox($user_id, $produit_id);// this is passed back to the javascript function
//        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
//    }

//    public static function isUserLocalized($user_id) {
//        $loc = get_user_meta($user_id,'amapress_user_location_type',true);
//        $lat = get_user_meta($user_id,'amapress_user_lat',true);
//        $lng = get_user_meta($user_id,'amapress_user_long',true);
//        return (!empty($loc) ? 'Localisé <a href="http://maps.google.com/maps?q='.$lng.','.$lat.'">Voir sur Google Maps</a>' : 'Adresse non localisée');
//    }

	public static function resolveUserAddress( $user_id = null, $address_text = null ) {
		if ( empty( $user_id ) && ! empty( $_REQUEST['user_id'] ) ) {
			$user_id = $_REQUEST['user_id'];
		}
		if ( empty( $address_text ) && ! empty( $_REQUEST['amapress_user_adresse'] ) ) {
			$address_text = $_REQUEST['amapress_user_adresse'] . ', ' . $_REQUEST['amapress_user_code_postal'] . ' ' . $_REQUEST['amapress_user_ville'];
		}

		$address = TitanFrameworkOptionAddress::lookup_address( $address_text );
		if ( $address ) {
			update_user_meta( $user_id, 'amapress_user_long', $address['longitude'] );
			update_user_meta( $user_id, 'amapress_user_lat', $address['latitude'] );
			update_user_meta( $user_id, 'amapress_user_location_type', $address['location_type'] );

			return true;
		} else {
			delete_user_meta( $user_id, 'amapress_user_long' );
			delete_user_meta( $user_id, 'amapress_user_lat' );
			delete_user_meta( $user_id, 'amapress_user_location_type' );

			return false;
		}
	}
}
