<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_user_info_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'name'   => '',
		'format' => '',
	), $atts );

	return amapress_replace_mail_user_placeholder( AmapressUser::getBy( amapress_current_user_id() ), $atts['name'], $atts['format'] );
}

function amapress_producteur_map_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'producteur'      => null,
		'show_email'      => 'false',
		'show_tel'        => 'false',
		'show_tel_fixe'   => 'false',
		'show_tel_mobile' => 'false',
		'show_adresse'    => 'false',
		'show_avatar'     => 'default',
		'mode'            => 'map',
	), $atts );

//    if (!amapress_is_user_logged_in()) return '';

	$prod_id = Amapress::resolve_post_id( $atts['producteur'], AmapressProducteur::INTERNAL_POST_TYPE );
	if ( $prod_id <= 0 ) {
		return '';
	}
	$producteur = new AmapressProducteur( $prod_id );
	if ( ! $producteur->isAdresseExploitationLocalized() ) {
		return '';
	}
	$markers   = array();
	$markers[] = array(
		'longitude' => $producteur->getAdresseExploitationLongitude(),
		'latitude'  => $producteur->getAdresseExploitationLatitude(),
		'url'       => ( $atts['show_email'] == true ? 'mailto:' . $producteur->getUser()->getEmail() : null ),
		'title'     => $producteur->getNomExploitation(),
		'content'   => $producteur->getUser()->getDisplay( $atts ),
	);

	return amapress_generate_map( $markers, $atts['mode'] );
}

function amapress_user_map_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'user'            => amapress_current_user_id(),
		'show_email'      => 'default',
		'show_tel'        => 'default',
		'show_tel_fixe'   => 'default',
		'show_tel_mobile' => 'default',
		'show_adresse'    => 'default',
		'show_avatar'     => 'default',
		'mode'            => 'map',
	), $atts );

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$user_id = Amapress::resolve_user_id( $atts['user'] );
	if ( $user_id <= 0 ) {
		return '';
	}
	$user = AmapressUser::getBy( $user_id );
	if ( ! $user->isAdresse_localized() ) {
		return '';
	}
	$markers   = array();
	$markers[] = array(
		'longitude' => $user->getUserLongitude(),
		'latitude'  => $user->getUserLatitude(),
		'url'       => ( $atts['show_email'] == true ? 'mailto:' . $user->getUser()->user_email : null ),
		'title'     => $user->getDisplayName(),
		'content'   => $user->getDisplay( $atts ),
	);

	return amapress_generate_map( $markers, $atts['mode'] );
}

function amapress_amapien_avatar_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'user' => '',
		'mode' => 'thumb',
	), $atts );

	$user = $atts['user'];

	if ( empty( $user ) ) {
		$user = amapress_current_user_id();
	}
	if ( is_numeric( $user ) ) {
		$usr = get_user_by( 'id', intval( $user ) );
	} else {
		$usr = get_user_by( 'login', $user );
		if ( ! $usr ) {
			$usr = get_user_by( 'email', $user );
		}
	}

	if ( ! $usr ) {
		return '';
	}

	ob_start();

	AmapressUsers::echoUser( $usr, explode( '+', $atts['mode'] ) );

	$content = ob_get_clean();

	return $content;
}

function amapress_amapiens_map_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'lieu'            => null,
		'show_email'      => 'default',
		'show_tel'        => 'default',
		'show_tel_fixe'   => 'default',
		'show_tel_mobile' => 'default',
		'show_adresse'    => 'default',
		'show_avatar'     => 'default',
		'show_lieu'       => 'default',
	), $atts );

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	if ( ! empty( $atts['lieu'] ) ) {
		$lieu_id = Amapress::resolve_post_id( $atts['lieu'], AmapressLieu_distribution::INTERNAL_POST_TYPE );
		if ( $lieu_id ) {
			$lieux = array( new AmapressLieu_distribution( $lieu_id ) );
		} else {
			$lieux = Amapress::get_lieux();
		}
	} else {
		if ( amapress_can_access_admin() ) {
			$lieux = Amapress::get_lieux();
		} else {
			$lieux = array_map(
				function ( $id ) {
					return new AmapressLieu_distribution( $id );
				},
				AmapressUsers::get_user_lieu_ids( amapress_current_user_id() ) );
		}
	}

	$icons = array( 'red', 'green', 'blue', 'yellow' );

	$lix     = 0;
	$markers = array();
	foreach ( $lieux as $lieu ) {
		$lieu_name = $lieu->getShortName();

		if ( $lieu->isAdresseLocalized() ) {
			$m = array(
				'longitude' => $lieu->getAdresseLongitude(),
				'latitude'  => $lieu->getAdresseLatitude(),
				'url'       => $lieu->getPermalink(),
				'icon'      => 'lieu',
				'title'     => $lieu->getShortName(),
				'content'   => '<p>' . esc_html( $lieu->getTitle() ) . '</p><p>' . esc_html( $lieu->getFormattedAdresse() ) . '</p>',
			);
			if ( $lieu->isAdresseAccesLocalized() ) {
				$m['access'] = array(
					'longitude' => $lieu->getAdresseAccesLongitude(),
					'latitude'  => $lieu->getAdresseAccesLatitude(),
				);
			}
			$markers[] = $m;
		}
		$query = array(
			'meta_query'    => array(
				array(
					'relation' => 'OR',
					array( 'key' => 'pw_user_status', 'compare' => 'NOT EXISTS' ),
					array( 'key' => 'pw_user_status', 'value' => 'approved', 'compare' => '=' )
				),
			),
			'amapress_lieu' => $lieu->ID,
		);
		$me_id = amapress_current_user_id();
		$users = get_users( $query );
		foreach ( $users as $user ) {
			$auser = AmapressUser::getBy( $user->ID );
			if ( ! $auser->isAdresse_localized() ) {
				continue;
			}
			$markers[] = array(
				'longitude' => $auser->getUserLongitude(),
				'latitude'  => $auser->getUserLatitude(),
				'url'       => ( $atts['show_email'] == true ? 'mailto:' . $auser->getUser()->user_email : null ),
				'title'     => $auser->getDisplayName(),
				'icon'      => ( $auser->ID == $me_id ? 'man' : $icons[ $lix % count( $icons ) ] ),
				'content'   => $auser->getDisplay( $atts ),
			);
		}
		$lix += 1;
	}

	return amapress_generate_map( $markers, 'map' );
}

function amapress_amapiens_role_list_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'lieu'            => null,
		'show_email'      => 'default',
		'show_tel'        => 'default',
		'show_tel_fixe'   => 'default',
		'show_tel_mobile' => 'default',
		'show_adresse'    => 'default',
		'show_avatar'     => 'default',
	), $atts );

	$atts['show_roles'] = 'false';

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$all_lieu_ids = Amapress::get_lieu_ids();

	if ( ! empty( $atts['lieu'] ) ) {
		$lieu_id = Amapress::resolve_post_id( $atts['lieu'], AmapressLieu_distribution::INTERNAL_POST_TYPE );
		if ( $lieu_id ) {
			$lieu_ids = array( $lieu_id );
		} else {
			$lieu_ids = $all_lieu_ids;
		}
	} else {
		if ( amapress_can_access_admin() ) {
			$lieu_ids = $all_lieu_ids;
		} else {
			$lieu_ids = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
		}
	}

	$columns   = array();
	$columns[] = array(
		'title' => 'Rôle',
		'width' => '30%',
		'data'  => array(
			'_'    => 'role',
			'sort' => 'role',
		)
	);
	if ( count( $all_lieu_ids ) > 1 ) {
		$columns[] = array(
			'title' => 'Lieu',
			'width' => '20%',
			'data'  => array(
				'_'    => 'lieu',
				'sort' => 'lieu',
			)
		);
	}
	$columns[] = array(
		'title' => 'Amapien',
		'width' => '50%',
		'data'  => array(
			'_'    => 'user',
			'sort' => 'user',
		)
	);

	$data = array();

	$users = array();
	foreach (
		get_users(
			array(
				'amapress_role' => 'referent_producteur'
			)
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}
	foreach (
		get_users(
			array(
				'amapress_role' => 'referent_lieu'
			)
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}
	foreach (
		get_users(
			array(
				'role__in' => amapress_can_access_admin_roles()
			)
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}
	foreach (
		get_users(
			array(
				'amapress_role' => 'amap_role_any'
			)
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}

	$lieux        = Amapress::get_lieux();
	$lieux_by_ids = array();
	foreach ( $lieux as $lieu ) {
		$lieux_by_ids[ $lieu->ID ] = $lieu;
	}
	foreach ( $users as $user ) {
		$amapien = AmapressUser::getBy( $user->ID );
		foreach ( $amapien->getAmapRoles() as $role ) {
			$type    = $role['type'];
			$lieu_id = $role['lieu'];
			if ( 'intermittent' == $type ) {
				continue;
			}
			if ( $lieu_id && ! in_array( $lieu_id, $lieu_ids ) ) {
				continue;
			}
			if ( 'wp_role' == $type && ! in_array( $role['role'], amapress_can_access_admin_roles() ) ) {
				continue;
			}

			if ( $lieu_id ) {
				$data[] = array(
					'user' => $amapien->getDisplay( $atts ),
					'lieu' => $lieux_by_ids[ $lieu_id ]->getShortName(),
					'role' => $role['title'],
				);
			} else {
				foreach ( AmapressUsers::get_user_lieu_ids( $user->ID ) as $lieu_id ) {
					$data[] = array(
						'user' => $amapien->getDisplay( $atts ),
						'lieu' => $lieux_by_ids[ $lieu_id ]->getShortName(),
						'role' => $role['title'],
					);
				}
			}
		}
	}

//    $used_user_ids = array();
//    //referents producteurs
////    $used_prods = array();
//    foreach (AmapressContrats::get_contrats() as $contrat) {
//        $prod = $contrat->getProducteur();
////        if (in_array($prod->ID, $used_prods)) continue;
//        $had_local_referents = false;
//        foreach ($lieu_ids as $lieu_id) {
//            if ($prod->getReferent($lieu_id) == null) continue;
//            $had_local_referents = true;
//            $lieu = new AmapressLieu_distribution($lieu_id);
//            $used_user_ids[] = $prod->getReferent($lieu_id)->ID;
//            $data[] =
//                array(
//                    'user' => $prod->getReferent($lieu->ID)->getDisplay($atts),
//                    'lieu' => $lieu->getShortName(),
//                    'role' => sprintf('Référent %s', $contrat->getTitle()),
//                );
//        }
//        if (!$had_local_referents) {
//            if ($prod->getReferent() != null) $used_user_ids[] = $prod->getReferent()->ID;
//            foreach ($lieu_ids as $lieu_id) {
//                if ($prod->getReferent() == null) continue;
//                $lieu = new AmapressLieu_distribution($lieu_id);
//                $data[] =
//                    array(
//                        'user' => $prod->getReferent()->getDisplay($atts),
//                        'lieu' => $lieu->getShortName(),
//                        'role' => sprintf('Référent %s', $contrat->getTitle()),
//                    );
//            }
//        }
//    }
//
//    //référent lieu
//    foreach ($lieu_ids as $lieu_id) {
//        $lieu = new AmapressLieu_distribution($lieu_id);
//        if ($lieu->getReferent() == null) continue;
//        $used_user_ids[] = $lieu->getReferent()->ID;
//        $data[] =
//            array(
//                'lieu' => $lieu->getShortName(),
//                'user' => $lieu->getReferent()->getDisplay($atts),
//                'role' => sprintf('Référent %s', $lieu->getShortName()),
//            );
//    }
//    //responsables
//
//    $terms = get_terms(array(
//        'hide_empty' => false,
//        'taxonomy' => AmapressUser::AMAP_ROLE,
//        'fields' => 'all',
//    ));
//    /** @var WP_Term $term */
//    foreach ($terms as $term) {
//        $user_ids = array_map(function ($u) {
//            return $u->ID;
//        }, get_users(array(
//            'meta_query' => array(
//                'relation' => 'OR',
//                array(
//                    'key' => 'amapress_user_amap_roles',
//                    'value' => $term->term_id,
//                    'compare' => '=',
//                ),
//                array(
//                    'key' => 'amapress_user_amap_roles',
//                    'value' => '"' . $term->term_id . '"',
//                    'compare' => 'like',
//                )
//            ))));
//        foreach ($user_ids as $user_id) {
////            if (in_array($user_id, $used_user_ids)) continue;
//            foreach (AmapressUsers::get_user_lieu_ids($user_id) as $lieu_id) {
//                if (!in_array($lieu_id, $lieu_ids)) continue;
//                $lieu = new AmapressLieu_distribution($lieu_id);
//                $u = AmapressUser::getBy($user_id);
//                $used_user_ids[] = $user_id;
//                $data[] =
//                    array(
//                        'lieu' => $lieu->getShortName(),
//                        'user' => $u->getDisplay($atts),
//                        'role' => $term->name,
//                    );
//            }
//        }
//    }
//
////    var_dump($used_user_ids);
//
//    $query = array('meta_query' => array(
//        'relation' => 'OR',
//        array('key' => 'pw_user_status', 'compare' => 'NOT EXISTS'),
//        array('key' => 'pw_user_status', 'value' => 'approved', 'compare' => '=')
//    ),
//        'order' => 'ASC',
//        'orderby' => 'display_name',
//    );
//    $query['role__in'] = array('administrator', 'responsable_amap', 'coordinateur_amap', 'tresorier');
//    $query['exclude'] = $used_user_ids;
//    $users = get_users($query);
//
//    global $wp_roles;
//
//    foreach ($users as $user) {
//        foreach (AmapressUsers::get_user_lieu_ids($user->ID) as $lieu_id) {
//            if (!in_array($lieu_id, $lieu_ids)) continue;
//            $lieu = new AmapressLieu_distribution($lieu_id);
//            $u = AmapressUser::getBy($user->ID);
//            foreach ($u->getUser()->roles as $r) {
//                $data[] =
//                    array(
//                        'lieu' => $lieu->getShortName(),
//                        'user' => $u->getDisplay($atts),
//                        'role' => translate_user_role($wp_roles->roles[$r]['name']),
//                    );
//            }
//        }
//    }


	return amapress_get_datatable( 'amapiens-role-list', $columns, $data,
		[
			'nowrap'     => false,
			'responsive' => false,
		],
		array( Amapress::DATATABLES_EXPORT_EXCEL, Amapress::DATATABLES_EXPORT_PDF ) );
}