<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function amapress_intermittence_inscription_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'view'      => 'me',
			'show_info' => 'yes',
		)
		, $atts );

	$ret                      = '';
	$inscription_intermittent = isset( $_REQUEST['inscription_intermittent'] ) ? $_REQUEST['inscription_intermittent'] : null;
	$admin_post_url           = admin_url( 'admin-post.php' );
	switch ( $atts['view'] ) {
		case 'me':
			if ( AmapressContrats::is_user_active_intermittent() ) {
				if ( 'ok' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">Votre inscription dans l\'espace intermittents a été prise en compte</div>';
				} else if ( 'already' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">Vous êtes déjà inscrit dans l\'espace intermittents</div>';
				}
				if ( $atts['show_info'] == 'yes' ) {
					$ret .= "<p class='intermittence inscription already-in-list'>Vous êtes déjà inscrit dans l'espace intermittents</p>";
				} else {
					$ret .= '';
				}
				$ret .= do_shortcode( '[intermittents-desinscription]' );
			} else if ( Amapress::toBool( Amapress::getOption( 'intermit_self_inscr' ) ) ) {
				$my_email = wp_get_current_user()->user_email;
				$ret      .= "<p class='intermittence inscription in-list'><a class='btn btn-default' target='_blank' href='$admin_post_url?action=inscription_intermittent&confirm=true&email=$my_email' onclick=\"return confirm('Confirmez-vous votre inscription ?')\">Devenir intermittent</a></p>";
			}
			break;
		case 'other':
		case 'other_user':
			if ( Amapress::toBool( Amapress::getOption( 'intermit_self_inscr' ) ) || amapress_can_access_admin() ) {
				if ( 'ok' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">Inscription dans l\'espace intermittents prise en compte</div>';
				} else if ( 'already' == $inscription_intermittent ) {
					$ret .= '<div class="alert alert-success">Déjà inscrit dans l\'espace intermittents</div>';
				}
				$ret .= '<form action="' . $admin_post_url . '?action=inscription_intermittent&return_sender&confirm=yes" method="post">
  <div class="form-group">
    <label for="email"><strong>*Email:</strong></label>
    <input type="email" class="form-control required" id="email" name="email">
  </div>
  <div class="form-group">
    <label for="first_name">Prénom:</label>
    <input type="text" class="form-control" id="first_name" name="first_name">
  </div>
  <div class="form-group">
    <label for="last_name">Nom:</label>
    <input type="text" class="form-control" id="last_name" name="last_name">
  </div>
  <div class="form-group">
    <label for="phone"><em>Téléphone</em>:</label>
    <input type="text" class="form-control" id="phone" name="phone">
  </div>
  <div class="form-group">
    <label for="address"><em>Adresse</em>:</label>
    <input type="text" class="form-control" id="address" name="address">
  </div>
  <button type="submit" class="btn btn-default" onclick="return confirm(\'Confirmez-vous votre inscription ?\')">Inscrire</button>
</form>';
			} else {
				$ret .= '<p class="intermittence inscr-collectif">L\'inscription à l\'Espace intermittents est gérée par le collectif</p>';
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

	//onclick="return confirm('Confirmez-vous votre inscription ?')"
	$admin_post_url = admin_url( 'admin-post.php' );
	switch ( $atts['view'] ) {
		case 'me':
			if ( ! AmapressContrats::is_user_active_intermittent() ) {
				return "<p class='intermittence desinscription not-in-list'>Vous n'êtes pas sur la liste des intermittents</p>";
			} else {
				$my_email = wp_get_current_user()->user_email;

				return "<p class='intermittence desinscription in-list'><a class='btn btn-default' target='_blank' href='$admin_post_url?action=desinscription_intermittent&confirm=true&email=$my_email' onclick=\"return confirm('Confirmez-vous votre désinscription ?')\">Se désinscrire</a></p>";
				//admin-post.php?action=inscription_intermittent&confirm=true&email=
			}
			break;
		case 'other':
		case 'other_user':
			return '<p class="not-implemented">Pas encore implémenté</p>';
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
	return wp_nonce_url(
		admin_url( 'admin-post.php?action=desinscription_intermittent' ),
		'desinscription_intermittent',
		'desinter_nonce'
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
	if ( $for_other_users && $is_resp_distrib ) {
		$users = array( '' => '--Sélectionner un amapien--' );
		/** @var WP_User $user */
		foreach ( get_users() as $user ) {
			$users[ $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
		}
		$user_select = '<form class="echanger-panier-other-user">
<select name="user" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="btn btn-default panier-echanger-other-button">Afficher l\'échange de paniers</button>
</form>';
		$ret         .= $user_select;
	}

	$adhesions             = AmapressAdhesion::getUserActiveAdhesions( $user_id );
	$adhesions_contrat_ids = array_map( function ( $a ) {
		/** @var AmapressAdhesion $a */
		return $a->getContrat_instanceId();
	}, $adhesions );
//    $contrat_instances = AmapressContrats::get_active_contrat_instances();
	$from_date = amapress_time();
//    if ($atts['show_past']) {
//        foreach ($contrat_instances as $contrat_instance) {
//            if ($contrat_instance->getDate_debut() < $from_date)
//                $from_date = $contrat_instance->getDate_debut();
//        }
//    }
//    $is_current_user_resp_amap = amapress_current_user_can('responsable_amap') || amapress_current_user_can('administrator') || amapress_current_user_can('manage_distributions');

//    $lieux = Amapress::get_lieux();
//    $lieux_needed_resps = array();
//    foreach ($lieux as $lieu) {
//        $lieux_needed_resps[$lieu->ID] = 0;
//    }
	$all_dists = AmapressDistribution::get_next_distributions( $from_date );
//    if ($is_current_user_resp_amap) {
//        $dists = $all_dists;
//    } else {
	$lieu_ids = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
	$dists    = array();
	/** @var AmapressDistribution $dist */
	foreach ( $all_dists as $dist ) {
		$dist_contrat_ids = $dist->getContratIds();
		if ( count( array_intersect( $adhesions_contrat_ids, $dist_contrat_ids ) ) > 0 ) {
			if ( in_array( $dist->getLieuId(), $lieu_ids ) ) {
				$dists[] = $dist;
			}
		}
	}
//    $user_lieux = array();
//    foreach ($dists as $dist) {
//        $lieu_id = $dist->getLieuId();
//        if ($dist->getLieuSubstitutionId() > 0)
//            $lieu_id = $dist->getLieuSubstitutionId();
//        if (!in_array($lieu_id, $user_lieux)) $user_lieux[] = $lieu_id;
//    }

	$ret .= '<div>';
	$ret .= '<table class="table echange-paniers-list" width="100%">';
	$ret .= '<thead>';
	$ret .= '<tr>';
	$ret .= '<th width="30%">Date</th>';
	$ret .= '<th></th>';
//    /** @var AmapressLieu_distribution $user_lieu */
//    foreach ($user_lieux as $lieu_id) {
//        $user_lieu = AmapressLieu_distribution::getBy($lieu_id);
//        $ret .= '<th>' . esc_html($user_lieu->getShortName()) . '</th>';
//    }
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
		$ceder_title   = count( $contrat_names ) > 1 ? 'Céder mes ' . count( $contrat_names ) . ' paniers' : 'Céder mon panier';
		$contrat_names = implode( ', ', $contrat_names );
		$ret           .= '<th scope="row" width="30%">';
		$ret           .= '<p class="inscr-list-date">' . esc_html( date_i18n( 'D j M Y', $date ) ) . '</p>';
		$ret           .= '<p class="inscr-list-contrats"><a href="' . $dist->getPermalink() . '">' . esc_html( $contrat_names ) . '</a></p></th>';

		if ( count( $date_dists ) > 1 ) {
			$date_dists = [ array_shift( $date_dists ) ];
		}

		foreach ( $date_dists as $dist ) {
			if ( ! in_array( $dist->getLieuId(), $lieu_ids ) ) {
				continue;
			}

//                $can_unsubscribe = Amapress::start_of_week($date) <= Amapress::start_of_week(amapress_time());
			$can_subscribe = Amapress::start_of_day( $date ) >= Amapress::start_of_day( amapress_time() );

			$is_intermittent = 'exchangeable';
			foreach ( AmapressPaniers::getPaniersForDist( $date ) as $panier ) {
				$status = AmapressPaniers::isIntermittent( $panier->ID, $dist->getLieuId() );
//                    var_dump($status);
				if ( ! empty( $status ) ) {
					$is_intermittent = $status;
				}
			}


			$ret .= '<td>';
			switch ( $is_intermittent ) {
				case 'exchangeable':
					if ( $can_subscribe ) {
						$id  = "info_{$dist->ID}";
						$ret .= '<div class="echange-panier-info amapress-ajax-parent"><h4 class="echange-panier-info-title">Informations</h4><textarea id="' . $id . '" style="box-sizing: border-box"></textarea><br/>';
						$ret .= '<button  type="button" class="btn btn-default amapress-ajax-button echange-panier" 
						data-confirm="Etes-vous sûr de vouloir céder votre panier ?" data-action="echanger_panier" data-dist="' . $dist->ID . '" data-message="val:#' . $id . '" data-user="' . $user_id . '">' . $ceder_title . '</button></div>';
					} else {
						$ret .= '<span class="echange-closed">Cessions de paniers closes</span>';
					}
					break;
				case AmapressIntermittence_panier::EXCHANGE_VALIDATE_WAIT:
					$ret .= '<span class="repreneurè-waiting">Repreneur(s) en attente de validation</span>';
					break;
				case 'to_exchange':
					$ret .= '<span class="panier-to-exchange">Panier(s) en attente de repreneur</span>';
					break;
				case 'exchanged':
					$ret .= '<span class="panier-exchanged">Panier(s) cédé(s)</span>';
					break;
				case 'closed':
					$ret .= '<span class="echange-done">Cession effectuée</span>';
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