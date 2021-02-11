<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_intermittence_anon_inscription_shortcode( $atts, $content = null, $tag = '' ) {
	$atts = shortcode_atts( array(
		'key'         => '',
		'shorturl'    => '',
		'force_upper' => 'false',
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
				$ret .= '<div class="alert alert-info">' . sprintf( __( 'Pour donner accès à cette page d\'inscription à la liste des intermittents, veuillez envoyer aux nouveaux intermittents le lien suivant : 
<pre>%s</pre>
Pour y accéder cliquez <a href="%s">ici</a>.<br />
Vous pouvez également utiliser un service de réduction d\'URL tel que <a href="https://bit.ly">bit.ly</a> pour obtenir une URL plus courte à partir du lien ci-dessus.<br/>
%s
Vous pouvez également utiliser l\'un des QRCode suivants : 
<div>%s%s%s</div><br/>
<strong>Attention : les lien ci-dessus, QR code et bit.ly NE doivent PAS être visible publiquement sur le site. Ce lien permet d\'accéder à la page d\'inscription à la liste des intermittents sans être connecté sur le site et l\'exposer sur internet pourrait permettre à une personne malvaillante de polluer le site.</strong>', 'amapress' ), $url, $url, ! empty( $atts['shorturl'] ) ? __( 'Lien court sauvegardé : <code>', 'amapress' ) . $atts['shorturl'] . '</code><br />' : '', amapress_print_qrcode( $url ), amapress_print_qrcode( $url, 3 ), amapress_print_qrcode( $url, 2 ) ) . '</div>';
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

	if ( Amapress::toBool( Amapress::getOption( 'intermit_adhesion_req' ) ) ) {
		$href = Amapress::get_intermittent_adhesion_page_href();
		$link = ! empty( $href ) ? Amapress::makeLink( $href, __( 'Assistant d\'adhésion Intermittents', 'amapress' ) ) : __( 'Non configuré', 'amapress' );
		wp_die( __( 'Les inscriptions à l\'Espace intermittents doivent se faire via l\'', 'amapress' ) . $link );
	}

	$current_post = get_post();

	$admin_post_url = admin_url( 'admin-post.php' );
	if ( Amapress::toBool( Amapress::getOption( 'intermit_self_inscr' ) ) ) {
		$ret .= '<form action="' . $admin_post_url . '?action=inscription_intermittent" method="post">
  <input type="hidden" name="key" value="' . esc_attr( $key ) . '" />
  <input type="hidden" name="post-id" value="' . esc_attr( $current_post ? $current_post->ID : 0 ) . '" />
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
  <button type="submit" class="btn btn-default" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous votre inscription ?', 'amapress' ) ) . '\')">' . __( 'S\'inscrire', 'amapress' ) . '</button>
</form>';
	} else {
		$ret .= '<p class="intermittence inscr-collectif">' . __( 'L\'inscription à l\'Espace intermittents est gérée par le collectif', 'amapress' ) . '</p>';
	}

	return $ret;
}

function amapress_intermittence_inscription_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'view'        => 'me',
			'show_info'   => 'yes',
			'force_upper' => 'false',
		)
		, $atts );

	$ret                      = '';
	$force_upper              = Amapress::toBool( $atts['force_upper'] );
	$inscription_intermittent = isset( $_REQUEST['inscription_intermittent'] ) ? $_REQUEST['inscription_intermittent'] : null;
	$admin_post_url           = admin_url( 'admin-post.php' );
	switch ( $atts['view'] ) {
		case 'me':
			if ( AmapressContrats::is_user_active_intermittent() ) {
				if ( 'ok' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">' . __( 'Votre inscription dans l\'espace intermittents a été prise en compte', 'amapress' ) . '</div>';
				} else if ( 'already' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">' . __( 'Vous êtes déjà inscrit dans l\'espace intermittents', 'amapress' ) . '</div>';
				}
				if ( $atts['show_info'] == 'yes' ) {
					$ret .= "<p class='intermittence inscription already-in-list'>" . __( 'Vous êtes déjà inscrit dans l\'espace intermittents', 'amapress' ) . "</p>";
				} else {
					$ret .= '';
				}
				$ret .= do_shortcode( '[intermittents-desinscription]' );
			} elseif ( Amapress::toBool( Amapress::getOption( 'intermit_adhesion_req' ) ) ) {
				$href = Amapress::get_intermittent_adhesion_page_href();
				$link = ! empty( $href ) ? Amapress::makeLink( $href, __( 'l\'Assistant d\'adhésion Intermittents', 'amapress' ) ) : __( 'Non configuré', 'amapress' );
				$ret  .= '<p>' . __( 'Les inscriptions à l\'Espace intermittents doivent se faire, par l\'intermittent lui-même, via', 'amapress' ) . $link . '</p>';
			} else if ( Amapress::toBool( Amapress::getOption( 'intermit_self_inscr' ) ) ) {
				$my_email = wp_get_current_user()->user_email;
				$ret      .= "<p class='intermittence inscription in-list'><a class='btn btn-default' target='_blank' href='$admin_post_url?action=inscription_intermittent&confirm=true&email=$my_email' onclick=\"return confirm('" . esc_js( __( 'Confirmez-vous votre inscription ?', 'amapress' ) ) . "')\">" . __( 'Devenir intermittent', 'amapress' ) . "</a></p>";
			}
			break;
		case 'other':
		case 'other_user':
			if ( Amapress::toBool( Amapress::getOption( 'intermit_adhesion_req' ) ) ) {
				$href = Amapress::get_intermittent_adhesion_page_href();
				$link = ! empty( $href ) ? Amapress::makeLink( $href, __( 'l\'Assistant d\'adhésion Intermittents', 'amapress' ) ) : __( 'Non configuré', 'amapress' );
				$ret  .= '<p>' . __( 'Les inscriptions à l\'Espace intermittents doivent se faire, par l\'intermittent lui-même, via ', 'amapress' ) . $link . '</p>';
			} elseif ( Amapress::toBool( Amapress::getOption( 'intermit_self_inscr' ) ) || amapress_can_access_admin() ) {
				if ( 'ok' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">' . __( 'Inscription dans l\'espace intermittents prise en compte', 'amapress' ) . '</div>';
				} else if ( 'already' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">' . __( 'Déjà inscrit dans l\'espace intermittents', 'amapress' ) . '</div>';
				}
				$ret .= '<form action="' . $admin_post_url . '?action=inscription_intermittent&return_sender&confirm=yes" method="post">
  <div class="form-group">
    <label for="email"><strong>*' . __( 'Email:', 'amapress' ) . '</strong></label>
    <input type="email" class="form-control required" id="email" name="email">
  </div>
  <div class="form-group">
    <label for="first_name">' . __( 'Prénom:', 'amapress' ) . '</label>
    <input type="text" class="form-control' . ( $force_upper ? 'force-upper' : '' ) . '" id="first_name" name="first_name">
  </div>
  <div class="form-group">
    <label for="last_name">' . __( 'Nom:', 'amapress' ) . '</label>
    <input type="text" class="form-control' . ( $force_upper ? 'force-upper' : '' ) . '" id="last_name" name="last_name">
  </div>
  <div class="form-group">
    <label for="phone"><em>' . __( 'Téléphone', 'amapress' ) . '</em>:</label>
    <input type="text" class="form-control" id="phone" name="phone">
  </div>
  <div class="form-group">
    <label for="address"><em>' . __( 'Adresse', 'amapress' ) . '</em>:</label>
    <input type="text" class="form-control' . ( $force_upper ? 'force-upper' : '' ) . '" id="address" name="address">
  </div>
  <button type="submit" class="btn btn-default" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous votre inscription ?', 'amapress' ) ) . '\')">' . __( 'Inscrire', 'amapress' ) . '</button>
</form>';
			} else {
				$ret .= '<p class="intermittence inscr-collectif">' . __( 'L\'inscription à l\'Espace intermittents est gérée par le collectif', 'amapress' ) . '</p>';
			}
			break;

	}

	return $ret;
}

function amapress_intermittence_desinscription_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'view' => 'me',
		)
		, $atts );

	//onclick="return confirm(__('Confirmez-vous votre inscription ?', 'amapress'))"
	$admin_post_url = admin_url( 'admin-post.php' );
	switch ( $atts['view'] ) {
		case 'me':
			if ( ! AmapressContrats::is_user_active_intermittent() ) {
				return "<p class='intermittence desinscription not-in-list'>" . __( 'Vous n\'êtes pas sur la liste des intermittents', 'amapress' ) . "</p>";
			} else {
				$my_email = wp_get_current_user()->user_email;

				return "<p class='intermittence desinscription in-list'><a class='btn btn-default' target='_blank' href='$admin_post_url?action=desinscription_intermittent&confirm=true&email=$my_email' onclick=\"return confirm('" . esc_js( __( 'Confirmez-vous votre désinscription ?', 'amapress' ) ) . "')\">" . __( 'Se désinscrire', 'amapress' ) . "</a></p>";
				//admin-post.php?action=inscription_intermittent&confirm=true&email=
			}
			break;
		case 'other':
		case 'other_user':
			return '<p class="not-implemented">' . __( 'Pas encore implémenté', 'amapress' ) . '</p>';
			break;

	}
}

add_filter( 'wpcf7_collect_mail_tags', 'amapress_wpcf7_add_intermittence_tags' );
function amapress_wpcf7_add_intermittence_tags( $mailtags ) {
	$mailtags[] = 'paniers-intermittents-link';
	$mailtags[] = 'mes-paniers-intermittents-link';
	$mailtags[] = 'desinscrire-intermittents-link';

	return $mailtags;
}

add_action( 'wpcf7_before_send_mail', 'amapress_intermittence_tags_handler' );
function amapress_intermittence_tags_handler( WPCF7_ContactForm $cf7 ) {
	$panier_inter_page     = get_post( Amapress::getOption( 'paniers-intermittents-page' ) );
	$mes_panier_inter_page = get_post( Amapress::getOption( 'mes-paniers-intermittents-page' ) );

	$admin_post_url = admin_url( 'admin-post.php' );
	$my_email       = wp_get_current_user()->user_email;

	$mail = $cf7->prop( 'mail' );
	$mail = str_replace(
		array(
			'[paniers-intermittents-link]',
			'[mes-paniers-intermittents-link]',
			'[desinscrire-intermittents-link]',
		),
		array(
			$panier_inter_page ? '<a href="' . get_permalink( $panier_inter_page->ID ) . '">' . esc_html( $panier_inter_page->post_title ) . '</a>' : '',
			$mes_panier_inter_page ? '<a href="' . get_permalink( $mes_panier_inter_page->ID ) . '">' . esc_html( $mes_panier_inter_page->post_title ) . '</a>' : '',
			amapress_intermittence_desinscription_link(),
		),
		$mail
	);

	$cf7->set_properties( array(
		'mail' => $mail,
	) );
}

function amapress_intermittence_desinscription_link( $atts = null ) {
	$uuid = wp_generate_uuid4();
	set_transient( 'amps_desinscr_inter_' . $uuid, $uuid, 5 * 24 * HOUR_IN_SECONDS );

	return add_query_arg(
		'desinter_nonce',
		$uuid,
		admin_url( 'admin-post.php?action=desinscription_intermittent' )
	);
}


//function amapress_intermittents_paniers_list_shortcode()
//{
//    $query = array(
//        'status' => 'to_exchange',
//    );
//    if (!empty($subview)) {
//        $dist_id = Amapress::resolve_post_id($subview, AmapressDistribution::INTERNAL_POST_TYPE);
//        if ($dist_id) {
//            $dist = new AmapressDistribution($dist_id);
//            $query['contrat_instance_id'] = array_map(function ($a) {
//                return $a->ID;
//            },
//                $dist->getContrats());
//            $query['date'] = $dist->getDate();
//        }
//    }
//    $adhs = AmapressPaniers::getPanierIntermittents($query);
//    return amapress_get_paniers_intermittents_table($adhs);
//}

function amapress_echanger_panier_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'user'            => null,
		'for_other_users' => 'no',
	), $atts );

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$for_other_users = Amapress::toBool( $atts['for_other_users'] );

	$is_current_user_resp_amap = amapress_current_user_can( 'responsable_amap' ) || amapress_current_user_can( 'administrator' ) || amapress_current_user_can( 'manage_distributions' );
	$is_resp_distrib           = $is_current_user_resp_amap || AmapressDistributions::isCurrentUserResponsableThisWeek() || AmapressDistributions::isCurrentUserResponsableNextWeek();

	$user_id = amapress_current_user_id();
	if ( ! empty( $atts['user'] ) ) {
		if ( ! $is_resp_distrib ) {
			return '';
		}

		$user_id = Amapress::resolve_user_id( $atts['user'] );
	}

	$ret = '';
	if ( amapress_can_access_admin() ) {
		if ( ! $for_other_users && isset( $_GET['admin_mode'] ) ) {
			$for_other_users = true;
		}
		if ( ! $for_other_users ) {
			$ret .= '<p>' . Amapress::makeButtonLink( remove_query_arg( 'user_id', add_query_arg( 'admin_mode', 'T' ) ),
					__( 'Passer en mode Admin', 'amapress' ) ) . '</p>';
		}
	}

	if ( $for_other_users && $is_resp_distrib ) {
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			$user_id = intval( $_REQUEST['user_id'] );
			$ret     .= '<p>' . Amapress::makeButtonLink( remove_query_arg( 'user_id', add_query_arg( 'admin_mode', 'T' ) ),
					__( 'Choisir un autre amapien', 'amapress' ) ) . '</p>';
			$amapien = AmapressUser::getBy( $user_id );
			if ( $amapien ) {
				$ret .= '<h3>' . __( 'Paniers de ', 'amapress' ) . esc_html( $amapien->getDisplayName() ) . '</h3>';
			}
		} else {
			$users = array( '' => '--Sélectionner un amapien--' );
			/** @var WP_User $user */
			foreach ( get_users( 'amapress_contrat=active' ) as $user ) {
				$users[ $user->ID ] = sprintf( __( '%s (%s)', 'amapress' ), $user->display_name, $user->user_email );
			}
			$user_select = '<form class="echanger-panier-other-user">
<select name="user_id" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<input type="hidden" name="admin_mode" value="T" />
<button type="submit" class="btn btn-default panier-echanger-other-button">' . __( 'Afficher l\'échange de paniers', 'amapress' ) . '</button>
</form>';
			$ret         .= $user_select;
		}
	}

	$adhesions             = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id );
	$adhesions_contrat_ids = array_map( function ( $a ) {
		/** @var AmapressAdhesion $a */
		return $a->getContrat_instanceId();
	}, $adhesions );
	$from_date             = amapress_time();

	$all_dists = AmapressDistribution::get_next_distributions( $from_date );
	$lieu_ids  = AmapressUsers::get_user_lieu_ids( $user_id );
	$dists     = array();
	/** @var AmapressDistribution $dist */
	foreach ( $all_dists as $dist ) {
		$dist_contrat_ids = $dist->getContratIds();
		if ( count( array_intersect( $adhesions_contrat_ids, $dist_contrat_ids ) ) > 0 ) {
			if ( in_array( $dist->getLieuId(), $lieu_ids ) ) {
				$dists[] = $dist;
			}
		}
	}

	$ret .= '<div>';
	$ret .= '<table class="table echange-paniers-list" width="100%">';
	$ret .= '<thead>';
	$ret .= '<tr>';
	$ret .= '<th width="30%">' . __( 'Date', 'amapress' ) . '</th>';
	$ret .= '<th></th>';
	$ret .= '</tr>';
	$ret .= '</thead>';

	$ret           .= '<tbody>';
	$dists_by_date = array_group_by( $dists, function ( $d ) {
		/** @var AmapressDistribution $d */
		return $d->getDate();
	} );
	ksort( $dists_by_date );
	foreach ( $dists_by_date as $date => $date_dists ) {
		$ret           .= '<tr>';
		$contrat_names = array();
		foreach ( $date_dists as $dist ) {
			foreach ( $dist->getContrats() as $c ) {
				if ( ! in_array( $c->ID, $adhesions_contrat_ids ) ) {
					continue;
				}

				if ( ! in_array( $c->getTitle(), $contrat_names ) ) {
					$contrat_names[] = $c->getTitle();
				}
			}
		}
		sort( $contrat_names );
		if ( $user_id != amapress_current_user_id() ) {
			$ceder_title = count( $contrat_names ) > 1 ? sprintf( __( 'Céder ses %s paniers', 'amapress' ), count( $contrat_names ) ) : __( 'Céder son panier', 'amapress' );
		} else {
			$ceder_title = count( $contrat_names ) > 1 ? sprintf( __( 'Céder mes %s paniers', 'amapress' ), count( $contrat_names ) ) : __( 'Céder mon panier', 'amapress' );
		}
		$contrat_names = implode( ', ', $contrat_names );
		$ret           .= '<th scope="row" style="width: 30%">';
		$ret           .= '<p class="inscr-list-date">' . esc_html( date_i18n( 'D j M Y', $date ) ) . '</p>';
		$ret           .= '<p class="inscr-list-contrats smart-word-break"><a href="' . $dist->getPermalink() . '">' . esc_html( $contrat_names ) . '</a></p></th>';

		if ( count( $date_dists ) > 1 ) {
			$date_dists = [ array_shift( $date_dists ) ];
		}

		$manage_my_exchanges_link = '';
		if ( $user_id == amapress_current_user_id() ) {
			$manage_my_exchanges_href = Amapress::get_page_with_shortcode_href( 'amapien-paniers-intermittents', 'amps_manage_paniers_inter' );
			if ( ! empty( $manage_my_exchanges_href ) ) {
				$manage_my_exchanges_link = '<p><a href="' . esc_attr( $manage_my_exchanges_href ) . '">' . __( 'Gérer l\'échange', 'amapress' ) . '</a></p>';
			}
		}

		$users = array( '' => __( '--Sélectionner un amapien--', 'amapress' ) );
		if ( $for_other_users ) {
			amapress_precache_all_users();
			/** @var WP_User $user */
			foreach ( get_users() as $user ) {
				if ( $user->ID != $user_id ) {
					$users[ $user->ID ] = sprintf( __( '%s (%s)', 'amapress' ), $user->display_name, $user->user_email );
				}
			}
		}

		foreach ( $date_dists as $dist ) {
			if ( ! in_array( $dist->getLieuId(), $lieu_ids ) ) {
				continue;
			}

			$can_subscribe = $dist->canCease();

			$is_intermittent = 'exchangeable';
			foreach ( AmapressPaniers::getPaniersForDist( $date ) as $panier ) {
				$status = AmapressPaniers::isIntermittent( $panier->ID, $dist->getLieuId(), $user_id );
				if ( ! empty( $status ) ) {
					$is_intermittent = $status;
				}
			}

			$user_paniers_count = count( array_intersect( $adhesions_contrat_ids, $dist->getContratIds() ) );

			$ret .= '<td>';
			switch ( $is_intermittent ) {
				case 'exchangeable':
					if ( $can_subscribe ) {
						$id  = "info_{$dist->ID}";
						$ret .= '<div class="echange-panier-info amapress-ajax-parent"><h4 class="echange-panier-info-title">' . __( 'Informations', 'amapress' ) . '</h4>';
						$ret .= '<textarea id="' . $id . '" style="box-sizing: border-box"></textarea><br/>';
						$ret .= '<button  type="button" class="btn btn-default amapress-ajax-button echange-panier" 
						data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir céder ce panier ?', 'amapress' ) . '" data-action="echanger_panier" 
						data-dist="' . $dist->ID . '" data-message="val:#' . $id . '" data-user="' . $user_id . '">' . $ceder_title . '</button>';
						if ( ! $for_other_users && Amapress::getOption( 'allow_partial_exchange' )
						     && $user_paniers_count > 1 ) {
							$ret .= '&#xA0;';
							$ret .= Amapress::makeButtonLink(
								$dist->getPermalink() . '#inter_partial_exchanges',
								__( 'Faire un échange partiel', 'amapress' ) . ' <span class="dashicons dashicons-external"></span>',
								false, true, 'btn btn-default'
							);
						}
						if ( Amapress::getOption( 'enable-gardiens-paniers' ) ) {
							$ret .= '&#xA0;';
							$ret .= Amapress::makeButtonLink(
								$dist->getPermalink() . '#panel_gardiens_paniers',
								__( 'Trouver un gardien de paniers', 'amapress' ) . ' <span class="dashicons dashicons-external"></span>', false, true );
						}
						if ( $for_other_users && amapress_can_access_admin() ) {

							$target_select_id = 'target_user_id' . $dist->ID;
							$ret              .= '<hr/>';
							$ret              .= '<select id="' . $target_select_id . '" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>';
							$ret              .= '<button  type="button" class="btn btn-default amapress-ajax-button echange-panier" 
						data-confirm="' . esc_attr__( 'Etes-vous sûr de vouloir céder ce panier à l\'amapien sélectionné ?', 'amapress' ) . '" data-action="echanger_panier" 
						data-dist="' . $dist->ID . '" data-target="val:#' . $target_select_id . '" 
						data-message="val:#' . $id . '" data-user="' . $user_id . '">' . $ceder_title . __( ' pour le repreneur sélectionné', 'amapress' ) . '</button>';
						}
						$ret .= '</div>';
					} else {
						$ret .= '<span class="echange-closed">' . __( 'Cessions de paniers closes', 'amapress' ) . '</span>';
					}
					break;
				case AmapressIntermittence_panier::EXCHANGE_VALIDATE_WAIT:
					$ret .= '<span class="repreneur-waiting">' . __( 'Repreneur(s) en attente de validation', 'amapress' ) . '</span>';
					$ret .= $manage_my_exchanges_link;
					break;
				case 'to_exchange':
					$ret .= '<span class="panier-to-exchange">' . __( 'Panier(s) en attente de repreneur', 'amapress' ) . '</span>';
					$ret .= $manage_my_exchanges_link;
					break;
				case 'exchanged':
					$ret .= '<span class="panier-exchanged">' . __( 'Panier(s) cédé(s)', 'amapress' ) . '</span>';
					$ret .= $manage_my_exchanges_link;
					break;
				case 'closed':
					$ret .= '<span class="echange-done">' . __( 'Cession effectuée', 'amapress' ) . '</span>';
					$ret .= $manage_my_exchanges_link;
					break;
			}
			$ret .= '</td>';

		}
		$ret .= '</tr>';
	}
	$ret .= '</tbody>';
	$ret .= '</table>';
	$ret .= '</div>';

	return $ret;
}