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
	amapress_ensure_no_cache();

	$atts = shortcode_atts( array(
		'producteur'      => null,
		'show_email'      => 'false',
		'show_tel'        => 'false',
		'show_tel_fixe'   => 'false',
		'show_tel_mobile' => 'false',
		'show_adresse'    => 'false',
		'show_avatar'     => 'default',
		'mode'            => 'map',
		'padding'         => '0',
		'max_zoom'        => null,
	), $atts );

//    if (!amapress_is_user_logged_in()) return '';

	$prod_id = Amapress::resolve_post_id( $atts['producteur'], AmapressProducteur::INTERNAL_POST_TYPE );
	if ( $prod_id <= 0 ) {
		return '';
	}
	$producteur = AmapressProducteur::getBy( $prod_id );
	if ( empty( $producteur ) || ! $producteur->isAdresseExploitationLocalized() ) {
		return '';
	}
	$markers   = array();
	$markers[] = array(
		'longitude' => $producteur->getAdresseExploitationLongitude(),
		'latitude'  => $producteur->getAdresseExploitationLatitude(),
		'url'       => ( $atts['show_email'] == true && $producteur->getUser() ? 'mailto:' . $producteur->getUser()->getEmail() : null ),
		'title'     => $producteur->getNomExploitation(),
		'content'   => $producteur->getUser() ? $producteur->getUser()->getDisplay( $atts ) : '',
	);

	return amapress_generate_map( $markers, $atts['mode'], [
		'padding'  => $atts['padding'],
		'max_zoom' => $atts['max_zoom'],
	] );
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
		'padding'         => '0',
		'max_zoom'        => null,
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

	return amapress_generate_map( $markers, $atts['mode'], [
		'padding'  => $atts['padding'],
		'max_zoom' => $atts['max_zoom'],
	] );
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
		$usr = amapress_get_user_by_id_or_archived( intval( $user ) );
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
		'padding'         => '0',
		'max_zoom'        => null,
	), $atts );

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	if ( ! empty( $atts['lieu'] ) ) {
		$lieu_id = Amapress::resolve_post_id( $atts['lieu'], AmapressLieu_distribution::INTERNAL_POST_TYPE );
		if ( $lieu_id ) {
			$lieu = AmapressLieu_distribution::getBy( $lieu_id );
			if ( $lieu ) {
				$lieux = array( $lieu );
			} else {
				$lieux = Amapress::get_lieux();
			}
		} else {
			$lieux = Amapress::get_lieux();
		}
	} else {
		if ( amapress_can_access_admin() ) {
			$lieux = Amapress::get_lieux();
		} else {
			$lieux = array_map(
				function ( $id ) {
					return AmapressLieu_distribution::getBy( $id );
				},
				AmapressUsers::get_user_lieu_ids( amapress_current_user_id() ) );
		}
	}

	$icons = array( 'red', 'green', 'blue', 'yellow' );

	$lix     = 0;
	$markers = array();
	foreach ( $lieux as $lieu ) {
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
		$query           = array(
			'meta_query'    => array(
				amapress_get_user_meta_filter(),
			),
			'amapress_lieu' => $lieu->ID,
		);
		$me_id           = amapress_current_user_id();
		$query['fields'] = 'all_with_meta';
		$users           = get_users( $query );
		foreach ( $users as $user ) {
			$auser = AmapressUser::getBy( $user );
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

	return amapress_generate_map( $markers, 'map', [
		'padding'  => $atts['padding'],
		'max_zoom' => $atts['max_zoom'],
	] );
}

function amapress_amapiens_role_list_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'lieu'            => null,
		'show_prod'       => 'false',
		'show_email'      => 'force',
		'show_tel'        => 'default',
		'show_tel_fixe'   => 'default',
		'show_tel_mobile' => 'force',
		'show_adresse'    => 'default',
		'show_avatar'     => 'default',
		'searchbox'       => true,
	), $atts );

	$atts['show_roles'] = 'false';

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$current_user    = AmapressUser::getBy( amapress_current_user_id() );
	$show_tel_fix    = amapress_check_info_visibility( $atts['show_tel'], 'tel', $current_user ) || amapress_check_info_visibility( $atts['show_tel_fixe'], 'tel_fixe', $current_user );
	$show_tel_mobile = amapress_check_info_visibility( $atts['show_tel'], 'tel', $current_user ) || amapress_check_info_visibility( $atts['show_tel_mobile'], 'tel_mobile', $current_user );
	$show_address    = amapress_check_info_visibility( $atts['show_adresse'], 'adresse', $current_user );
	$show_email      = amapress_check_info_visibility( $atts['show_email'], 'email', $current_user );

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
		'title' => __( 'Rôle', 'amapress' ),
		'width' => '30%',
		'data'  => array(
			'_'    => 'role',
			'sort' => 'role',
		)
	);
	if ( count( $all_lieu_ids ) > 1 ) {
		$columns[] = array(
			'title' => __( 'Lieu', 'amapress' ),
			'width' => '20%',
			'data'  => array(
				'_'    => 'lieu',
				'sort' => 'lieu',
			)
		);
	}
	$columns[] = array(
		'title' => __( 'Amapien', 'amapress' ),
		'width' => '50%',
		'data'  => array(
			'_'    => 'user',
			'sort' => 'user',
		)
	);
	$columns[] = array(
		'name'    => 'user_name',
		'title'   => __( 'Membre collectif', 'amapress' ),
		'visible' => false,
		'data'    => array(
			'_'    => 'user_name',
			'sort' => 'user_name',
		)
	);
	if ( $show_address ) {
		$columns[] = array(
			'name'    => 'user_address',
			'title'   => __( 'Adresse', 'amapress' ),
			'visible' => false,
			'data'    => array(
				'_'    => 'user_address',
				'sort' => 'user_address',
			)
		);
	}
	if ( $show_tel_fix ) {
		$columns[] = array(
			'name'    => 'user_phone_fix',
			'title'   => __( 'Tel Fixe', 'amapress' ),
			'visible' => false,
			'data'    => array(
				'_'    => 'user_phone_fix',
				'sort' => 'user_phone_fix',
			)
		);
	}
	if ( $show_tel_mobile ) {
		$columns[] = array(
			'name'    => 'user_phone_mob',
			'title'   => __( 'Tel Mobile', 'amapress' ),
			'visible' => false,
			'data'    => array(
				'_'    => 'user_phone_mob',
				'sort' => 'user_phone_mob',
			)
		);
	}
	if ( $show_email ) {
		$columns[] = array(
			'name'    => 'user_email',
			'title'   => __( 'Email', 'amapress' ),
			'visible' => false,
			'data'    => array(
				'_'    => 'user_email',
				'sort' => 'user_email',
			)
		);
	}

	$data = array();

	$users = array();
	foreach (
		get_users(
			array(
				'amapress_role' => 'referent_producteur',
				'fields'        => 'all_with_meta',
			)
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}
	foreach (
		get_users(
			array(
				'amapress_role' => 'referent_lieu',
				'fields'        => 'all_with_meta',
			)
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}
	foreach (
		get_users(
			array(
				'role__in' => amapress_can_access_admin_roles(),
				'fields'   => 'all_with_meta',
			)
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}
	foreach (
		get_users(
			array(
				'amapress_role' => 'amap_role_any',
				'fields'        => 'all_with_meta',
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
			if ( ! Amapress::toBool( $atts['show_prod'] ) && 'producteur' == $type ) {
				continue;
			}
			if ( $lieu_id && ! in_array( $lieu_id, $lieu_ids ) ) {
				continue;
			}
			if ( 'wp_role' == $type ) {
				continue;
			}

			if ( $lieu_id ) {
				if ( empty( $lieux_by_ids[ $lieu_id ] ) ) {
					continue;
				}
				$data[] = array(
					'user'           => $amapien->getDisplay( $atts ),
					'user_name'      => $amapien->getDisplayName(),
					'user_address'   => ! $show_address ? '' : $amapien->getFormattedAdresse(),
					'user_phone_fix' => ! $show_tel_fix ? '' : $amapien->getTelTo( false, false, false, ', ' ),
					'user_phone_mob' => ! $show_tel_mobile ? '' : $amapien->getTelTo( true, false, false, ', ' ),
					'user_email'     => ! $show_email ? '' : $amapien->getEmail(),
					'lieu'           => $lieux_by_ids[ $lieu_id ]->getShortName(),
					'role'           => $role['title'],
				);
			} else {
				$user_lieu_ids = AmapressUsers::get_user_lieu_ids( $user->ID );
				if ( empty( $user_lieu_ids ) ) {
					$user_lieu_ids = $all_lieu_ids;
				}
				foreach ( $user_lieu_ids as $lieu_id ) {
					if ( empty( $lieux_by_ids[ $lieu_id ] ) ) {
						continue;
					}
					$data[] = array(
						'user'           => $amapien->getDisplay( $atts ),
						'user_name'      => $amapien->getDisplayName(),
						'user_address'   => ! $show_address ? '' : $amapien->getFormattedAdresse(),
						'user_phone_fix' => ! $show_tel_fix ? '' : $amapien->getTelTo( false, false, false, ', ' ),
						'user_phone_mob' => ! $show_tel_mobile ? '' : $amapien->getTelTo( true, false, false, ', ' ),
						'user_email'     => ! $show_email ? '' : $amapien->getEmail(),
						'lieu'           => $lieux_by_ids[ $lieu_id ]->getShortName(),
						'role'           => $role['title'],
					);
				}
			}
		}
	}

	return amapress_get_datatable( 'amapiens-role-list', $columns, $data,
		[
			'nowrap'     => false,
			'responsive' => false,
			'searching'  => Amapress::toBool( $atts['searchbox'] ),
//			'init_as_html' => true,
		],
		array(
			[
				'extend'        => Amapress::DATATABLES_EXPORT_EXCEL,
				'exportOptions' => [
					'columns' => [
						'user_name:name',
						'user_email:name',
						'user_address:name',
						'user_phone_fix:name',
						'user_phone_mob:name',
						'lieu:name',
						'role:name',
					],
				],
			]
		) );
}

function amapress_extern_user_inscription_shortcode( $atts, $content = null, $tag = '' ) {
	$atts = shortcode_atts( array(
		'key'         => '',
		'shorturl'    => '',
		'force_upper' => 'false',
		'group'       => '',
	), $atts );

	$ret         = '';
	$force_upper = Amapress::toBool( $atts['force_upper'] );
	$key         = $atts['key'];
	if ( amapress_can_access_admin() ) {
		$sample_key = uniqid() . uniqid();
		$url        = add_query_arg( 'key', $key, get_permalink() );
		if ( empty( $_REQUEST['key'] ) ) {
			if ( empty( $key ) ) {
				$ret .= amapress_get_panel_start( __( 'Configuration', 'amapress' ) );
				$ret .= '<div style="color:red">' . sprintf( __( 'Ajoutez la clé suivante à votre shortcode : %s<br/>De la forme : [%s key=%s]', 'amapress' ), $sample_key, $tag, $sample_key ) . '</div>';
			} else {
				$ret .= '<div class="alert alert-info">' . sprintf( __( 'Pour donner accès à cette page d\'inscription à des amapiens externes, veuillez leur envoyer le lien suivant : 
<pre>%s</pre>
Pour y accéder cliquez <a href="%s">ici</a>.<br />
Vous pouvez également utiliser un service de réduction d\'URL tel que <a href="https://bit.ly">bit.ly</a> pour obtenir une URL plus courte à partir du lien ci-dessus.<br/>
%s
Vous pouvez également utiliser l\'un des QRCode suivants : 
<div>%s%s%s</div><br/>
<strong>Attention : les lien ci-dessus, QR code et bit.ly NE doivent PAS être visible publiquement sur le site. Ce lien permet d\'accéder à la page d\'inscription comme amapien externe sans être connecté sur le site et l\'exposer sur internet pourrait permettre à une personne malvaillante de polluer le site.</strong>', 'amapress' ), $url, $url, ! empty( $atts['shorturl'] ) ? __( 'Lien court sauvegardé : <code>', 'amapress' ) . $atts['shorturl'] . '</code><br />' : '', amapress_print_qrcode( $url ), amapress_print_qrcode( $url, 3 ), amapress_print_qrcode( $url, 2 ) ) . '</div>';
				$ret .= amapress_get_panel_end();
			}
		} else {
			$ret .= '<div class="alert alert-info"><a href="' . esc_attr( get_permalink() ) . '">' . __( 'Afficher les instructions d\'accès à cette page.', 'amapress' ) . '</a></div>';
		}
	}
	if ( empty( $key ) || empty( $_REQUEST['key'] ) || $_REQUEST['key'] != $key ) {
		if ( empty( $key ) && amapress_can_access_admin() ) {
			$ret .= __( 'Une fois le shortcode configuré : seuls les personnes dirigées depuis l\'url contenant cette clé pourront s\'inscrire sans mot de passe utilisateur.', 'amapress' );
			$ret .= $content;

			return $ret;
		} elseif ( ! amapress_is_user_logged_in() ) {
			$ret .= '<div class="alert alert-danger">' . __( 'Vous êtes dans un espace sécurisé. Accès interdit', 'amapress' ) . '</div>';
			$ret .= $content;

			return $ret;
		}
	}

	$admin_post_url = add_query_arg( 'action', 'inscription_amap_extern', admin_url( 'admin-post.php' ) );
	$ret            .= '<form action="' . esc_attr( $admin_post_url ) . '" method="post">
  <input type="hidden" name="key" value="' . esc_attr( $key ) . '" />
  <input type="hidden" name="inscr-key" value="' . esc_attr( amapress_sha_secret( $key ) ) . '" />
  <input type="hidden" name="group" value="' . esc_attr( $atts['group'] ) . '" />
  <div class="form-group">
    <label for="email"><strong>*' . __( 'Email:', 'amapress' ) . '</strong></label>
    <input type="email" class="form-control required" id="email" name="email">
  </div>
  <div class="form-group">
    <label for="first_name">' . __( 'Prénom:', 'amapress' ) . '</label>
    <input type="text" class="form-control required ' . ( $force_upper ? 'force-upper' : '' ) . '" id="first_name" name="first_name">
  </div>
  <div class="form-group">
    <label for="last_name">' . __( 'Nom:', 'amapress' ) . '</label>
    <input type="text" class="form-control required ' . ( $force_upper ? 'force-upper' : '' ) . '" id="last_name" name="last_name">
  </div>
  <div class="form-group">
    <label for="phone"><em>' . __( 'Téléphone', 'amapress' ) . '</em>:</label>
    <input type="text" class="form-control" id="phone" name="phone">
  </div>
  <div class="form-group">
    <label for="address"><em>' . __( 'Adresse', 'amapress' ) . '</em>:</label>
    <input type="text" class="form-control ' . ( $force_upper ? 'force-upper' : '' ) . '" id="address" name="address">
  </div>
  ' . amapress_get_honeypots() . '
  <button type="submit" class="btn btn-default" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous votre inscription ?', 'amapress' ) ) . '\')">' . __( 'S\'inscrire', 'amapress' ) . '</button>
</form>';

	return $ret;
}
