<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_post-its', 'amapress_register_resp_distrib_post_its' );
function amapress_register_resp_distrib_post_its( $post_its ) {
	if ( ! amapress_is_user_logged_in() ) {
		return $post_its;
	}

//	$is_resp_amap = amapress_can_access_admin();

//	$date           = amapress_time();
//	$next_week_date = Amapress::add_a_week( amapress_time() );
//	$next_distribs  = AmapressDistribution::get_distributions( Amapress::start_of_week( Amapress::end_of_week( $date ) ), Amapress::end_of_week( $next_week_date ) );

	$user_id = amapress_current_user_id();

	$next_distribs = AmapressDistribution::getNextDistribs( null, 2, null );

	foreach ( $next_distribs as $dist ) {
//		if ( ! $is_resp_amap && ! in_array( $user_id, $dist->getResponsablesIds() ) ) {
//			continue;
//		}

		$content = '';
		$lieu = $dist->getLieu();
		if ( in_array( $user_id, $dist->getResponsablesIds() ) ) {
			$content .= '<p class="resp-distribution">Vous êtes responsable de distribution</p>';
		}
		$content .= '<p>' . esc_html( $lieu->getShortName() ) . '</p>';
		$content .= amapress_get_button( 'Liste d\'émargement',
			amapress_action_link( $dist->ID, 'liste-emargement' ), 'fa-fa',
			true );


		$post_its[] = array(
			'date'       => $dist->getStartDateAndHour(),
			'type'       => 'distrib',
			'title_html' => Amapress::makeLink( $dist->getPermalink(), date_i18n( 'd/m/Y', $dist->getDate() ) . ' - Distribution' ),
			'content'    => $content,
		);

	}

	return $post_its;
}

function amapress_inscription_distrib_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'show_past'       => 'false',
		'show_next'       => 'true',
		'show_email'      => 'default',
		'show_tel'        => 'default',
		'show_tel_fixe'   => 'default',
		'show_tel_mobile' => 'default',
		'show_adresse'    => 'default',
		'show_avatar'     => 'default',
		'show_roles'      => 'default',
		'show_for_resp'   => 'true',
		'for_pdf'         => 'false',
		'max_dates'       => - 1,
		'responsive'      => 'false',
		'user'            => null,
		'lieu'            => null,
		'date'            => null,
	), $atts );

	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$user_id = amapress_current_user_id();
	if ( ! empty( $atts['user'] ) ) {
		$user_id = Amapress::resolve_user_id( $atts['user'] );
	}

	$required_lieu_id = null;
	if ( ! empty( $atts['lieu'] ) ) {
		$required_lieu_id = Amapress::resolve_post_id( $atts['lieu'], AmapressLieu_distribution::INTERNAL_POST_TYPE );
	}

	$from_date = amapress_time();
	if ( ! empty( $atts['date'] ) ) {
		$from_date = intval( $atts['date'] );
	}

	$max_dates = intval( $atts['max_dates'] );
	if ( $max_dates <= 0 ) {
		$max_dates = 1000;
	}

	$adhesions             = AmapressAdhesion::getUserActiveAdhesions( $user_id, null, $from_date );
	$adhesions_contrat_ids = array_map( function ( $a ) {
		/** @var AmapressAdhesion $a */
		return $a->getContrat_instanceId();
	}, $adhesions );
	$contrat_instances     = AmapressContrats::get_active_contrat_instances( null, $from_date );
	if ( Amapress::toBool( $atts['show_past'] ) ) {
		foreach ( $contrat_instances as $contrat_instance ) {
			if ( $contrat_instance->getDate_debut() < $from_date ) {
				$from_date = $contrat_instance->getDate_debut();
			}
		}
	}
	$to_date = amapress_time();
	if ( Amapress::toBool( $atts['show_next'] ) ) {
		$to_date = null;
	}
	$is_current_user_resp_amap = amapress_can_access_admin() || user_can( $user_id, 'manage_distributions' );
	$is_resp_distrib           = $is_current_user_resp_amap || AmapressDistributions::isCurrentUserResponsableThisWeek( null, $from_date ) || AmapressDistributions::isCurrentUserResponsableNextWeek( null, $from_date );
	$current_post              = get_post();
	if ( $current_post && $current_post->post_type == AmapressDistribution::INTERNAL_POST_TYPE ) {
		$is_resp_distrib = $is_current_user_resp_amap || AmapressDistributions::isCurrentUserResponsable( $current_post->ID, $user_id );
	}
	if ( ! Amapress::toBool( $atts['show_for_resp'] ) ) {
		$is_current_user_resp_amap = false;
	}

	$for_pdf = Amapress::toBool( $atts['for_pdf'] );
	if ( $for_pdf ) {
		$is_current_user_resp_amap = false;
		$is_resp_distrib           = false;
	}

	$lieux              = Amapress::get_lieux();
	$lieu_ids           = array_map( function ( $l ) {
		return $l->ID;
	}, $lieux );
	$lieux_needed_resps = array();
	foreach ( $lieux as $lieu ) {
		$lieux_needed_resps[ $lieu->ID ] = 0;
	}
	if ( ! $to_date ) {
		$all_dists = AmapressDistribution::get_next_distributions( $from_date, 'ASC' );
	} else {
		$all_dists = AmapressDistribution::get_distributions( $from_date, $to_date, 'ASC' );
	}

	if ( $required_lieu_id ) {
		$all_dists = from( $all_dists )->where( function ( $d ) use ( $required_lieu_id ) {
			/** @var AmapressDistribution $d */
			return $d->getLieuId() == $required_lieu_id;
		} )->orderBy( function ( $d ) {
			/** @var AmapressDistribution $d */
			return $d->getDate();
		} )->toArray();
	}

	$dists = array();
	if ( $is_current_user_resp_amap || $required_lieu_id ) {
		foreach ( $all_dists as $dist ) {
			$max_dates --;
			if ( $max_dates < 0 ) {
				continue;
			}
			$dists[] = $dist;
		}
	} else {
		$user_lieux_ids = AmapressUsers::get_user_lieu_ids( amapress_current_user_id(), $from_date );
		/** @var AmapressDistribution $dist */
		foreach ( $all_dists as $dist ) {
			if ( ! in_array( $dist->getLieuId(), $user_lieux_ids ) ) {
				continue;
			}
			$dist_contrat_ids = $dist->getContratIds();
			if ( count( array_intersect( $adhesions_contrat_ids, $dist_contrat_ids ) ) > 0 ) {
				if ( $max_dates -- <= 0 ) {
					continue;
				}
				$dists[] = $dist;
			}
		}
	}

	$all_user_lieux = array();
	foreach ( $dists as $dist ) {
		$lieu_id = $dist->getLieuId();
		if ( ! in_array( $lieu_id, $lieu_ids ) ) {
			continue;
		}
//        if ($dist->getLieuSubstitutionId() > 0) {
//            $lieu_id = $dist->getLieuSubstitutionId();
//            if (!in_array($lieu_id, $user_lieux_substs)) $user_lieux_substs[] = $lieu_id;
//            if (!in_array($dist->getDate(), $user_date_substs)) $user_date_substs[] = $dist->getDate();
//        }
		$needed = AmapressDistributions::get_required_responsables( $dist->ID );
		if ( ! in_array( $lieu_id, $all_user_lieux ) ) {
			$all_user_lieux[] = $lieu_id;
		}
		if ( ! isset( $lieux_needed_resps[ $lieu_id ] ) ) {
			continue;
		}
		if ( $lieux_needed_resps[ $lieu_id ] < $needed ) {
			$lieux_needed_resps[ $lieu_id ] = $needed;
		}
	}

	$ret = '';
	foreach ( $all_user_lieux as $lieu_id ) {
		$user_lieu = AmapressLieu_distribution::getBy( $lieu_id );
		$ret       .= '<h4 class="distrib-inscr-lieu">' . esc_html( $user_lieu->getShortName() ) . '</h4>';
		if ( ! $for_pdf && current_user_can( 'edit_lieu_distribution' ) ) {
			$ret .= '<p style="text-align: center"><a class="btn btn-default" href="' . $user_lieu->getAdminEditLink() . '#amapress_lieu_distribution_nb_responsables">Changer le nombre de responsables du lieu</a></p>';
		}
		$ret                     .= '<table class="table display ' . ( Amapress::toBool( $atts['responsive'] ) ? 'responsive' : '' ) . ' distrib-inscr-list" width="100%" style="table-layout: fixed; word-break: break-all;" cellspacing="0">';
		$ret                     .= '<thead>';
		$ret                     .= '<tr>';
		if ( $for_pdf ) {
			$ret .= '<th>Date</th>';
		} else {
			$ret .= '<th style="width: 5em">Date</th>';
		}
		/** @var AmapressLieu_distribution $user_lieu */
		/** @var AmapressLieu_distribution $user_lieu */
//        foreach ($user_lieux as $lieu_id) {
		for ( $i = 1; $i <= $lieux_needed_resps[ $lieu_id ]; $i ++ ) {
			$width = ! $for_pdf ? 'width: calc(100 / ' . $lieux_needed_resps[ $lieu_id ] . ' - 5em)' : '';
			$ret   .= '<th class="distrib-resp-head" style="' . $width . '">Responsable ' . $i . '</th>';
		}
		$ret .= '</tr>';
		$ret .= '</thead>';


		$dates = array_group_by( $dists, function ( $d ) {
			/** @var AmapressDistribution $d */
			return $d->getDate();
		} );
		$today = Amapress::start_of_day( $from_date );
		uksort( $dates, function ( $a, $b ) use ( $today ) {
			$da = $a;
			$db = $b;

//        if ($da < $today && $db < $today) {
//            return ($da > $db ? -1 : 1);
//        } else if ($da > $today && $db > $today){
//            return ($da < $db ? -1 : 1);
//        } else {
			return ( $da < $db ? - 1 : 1 );
//        }
		} );

		$ret .= '<tbody>';
		foreach ( $dates as $date => $date_dists ) {
			$ret           .= '<tr>';
			$contrat_names = array();
			$subst_lieux   = array();
			foreach ( $date_dists as $dist ) {
				if ( $dist->getLieuSubstitutionId() > 0 ) {
					if ( ! in_array( $dist->getLieuSubstitution()->getLieuTitle(), $subst_lieux ) ) {
						$subst_lieux[] = $dist->getLieuSubstitution()->getLieuTitle();
					}
				}
				foreach ( $dist->getContrats() as $c ) {
					if ( $c->getModel() == null ) {
						continue;
					}
					if ( ! in_array( $c->getModel()->getTitle(), $contrat_names ) ) {
						$contrat_names[] = $c->getModel()->getTitle();
					}
				}
			}
			sort( $contrat_names );
			$lieu_users    = array();
			$contrat_names = implode( ', ', $contrat_names );
			$ret   .= '<th scope="row" class="inscr-list-info">
<p class="inscr-list-date">' . esc_html( date_i18n( 'D j M Y', $date ) ) . '</p>
<p class="inscr-list-contrats">' . esc_html( $contrat_names ) . '</p>';
			if ( ! empty( $subst_lieux ) ) {
				$subst_lieux = implode( ', ', $subst_lieux );
				$ret         .= '<p class="inscr-list-lieux-substitution">exceptionnellement à ' . esc_html( $subst_lieux ) . '</p>';
			}
			$ret .= '</th>';
//            foreach ($user_lieux as $lieu_id) {
			$user_lieu = AmapressLieu_distribution::getBy( $lieu_id );
			foreach ( $date_dists as $dist ) {
//                if ($dist->getLieuId() != $user_lieu->ID && $dist->getLieuSubstitutionId() != $user_lieu->ID) continue;
				if ( $dist->getLieuId() != $user_lieu->ID ) {
					continue;
				}

//                $ret .= '<td>';
				$is_user_part_of = $dist->isUserMemberOf( amapress_current_user_id(), true ); // || (in_array($dist->getDate(), $user_date_substs) && in_array($dist->getLieuId(), $user_lieux_substs));
				$resps           = $dist->getResponsables();
				$needed          = AmapressDistributions::get_required_responsables( $dist->ID );
				$can_unsubscribe = ! $for_pdf && Amapress::start_of_week( $date ) > Amapress::start_of_week( amapress_time() );
				$can_subscribe   = ! $for_pdf && Amapress::start_of_day( $date ) >= Amapress::start_of_day( amapress_time() );
				$colspan_cls     = 'resp-col resp-col-' . ( $lieux_needed_resps[ $lieu_id ] + ( $is_current_user_resp_amap ? 1 : 0 ) );

				if ( ! isset( $lieu_users[ $lieu_id ] ) ) {
					$arr = array( '' => '-amapien-' );
					/** @var WP_User $user */
					foreach (
						get_users_cached( array(
							'amapress_lieu' => $lieu_id,
							'fields'        => 'all_with_meta',
						) ) as $user
					) {
						$arr[ $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
					}
					$lieu_users[ $lieu_id ] = $arr;
				}
				$users         = $lieu_users[ $lieu_id ];
				$inscr_another = '';
				if ( ( $is_resp_distrib || $is_current_user_resp_amap ) && $can_subscribe ) {
					$inscr_another = '<form class="inscription-distrib-other-user" action="#">
<select name="user" class="autocomplete required">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="btn btn-default dist-inscrire-button" data-dist="' . $dist->ID . '">Inscrire</button>
</form>';
				}
//                $desinscr_another = '';
//                if ($is_resp_distrib && $can_subscribe) {
//                    $desinscr_another = '<button type="button" class="btn btn-default dist-inscrire-button" data-dist="' . $dist->ID . '">Désinscrire</button>';
//                }

				usort( $resps, function ( $resp ) {
					if ( $resp->ID == amapress_current_user_id() ) {
						return - 1;
					} else {
						return 0;
					}
				} );
				$is_resp = false;
				foreach ( $resps as $resp ) {
					$is_resp = $is_resp || $resp->ID == amapress_current_user_id();
					if ( $is_resp ) {
						break;
					}
				}
				if ( $needed - count( $resps ) > 0 ) {
					if ( $is_resp ) {
						$missing = ! $for_pdf ? "<span class='distrib-resp-missing'>manquant</span>" : '';
						$ret     .= "<td class='$colspan_cls incr-list-resp'>$missing$inscr_another</td>";
//                        $ret .= "<div class='$colspan_cls incr-list-resp'><span class='distrib-resp-missing'>manquant</span></div>";
					} else {
//                        $ret .= "<div class='$colspan_cls incr-list-resp'>";
						$ret .= "<td class='$colspan_cls incr-list-resp'>";
						if ( $is_user_part_of ) {
							if ( $can_subscribe ) {
								$ret .= '<button type="button" class="btn btn-default dist-inscrire-button" data-dist="' . $dist->ID . '">M\'inscrire</button>';
								$ret .= $inscr_another;
							} else {
								$ret .= '<span class="distrib-inscr-closed">Inscriptions closes</span>';
							}
						} else if ( ! empty( $inscr_another ) ) {
							$ret .= $inscr_another;
						} else {
							$ret .= '<span class="distrib-not-part-of">Inscription impossible</span>';
						}
//                        $ret .= '</div>';
						$ret .= '</td>';
					}

					for ( $i = 0; $i < $needed - count( $resps ) - 1; $i ++ ) {
//                        $ret .= "<div class='$colspan_cls incr-list-resp'><span class='distrib-resp-missing'>manquant</span></div>";
						$missing = ! $for_pdf ? "<span class='distrib-resp-missing'>manquant</span>" : '';
						if ( $is_user_part_of ) {
							$ret .= "<td class='$colspan_cls incr-list-resp incr-missing'>$missing$inscr_another</td>";
						} else {
							$ret .= "<td class='$colspan_cls incr-list-resp incr-not-part'>$inscr_another</td>";
						}
					}
				}
				foreach ( $resps as $resp ) {
					$ret .= '<td style="text-align: center">';
					$ret .= $resp->getDisplay( $atts );
					if ( $is_user_part_of || $is_current_user_resp_amap ) {
						$is_resp = $is_resp || $resp->ID == amapress_current_user_id();
						if ( $can_unsubscribe ) {
							if ( $resp->ID == amapress_current_user_id() ) {
								$ret .= '<button type="button" class="btn btn-default dist-desinscrire-button" data-dist="' . $dist->ID . '">Me désinscrire</button>';
							} else if ( $is_resp_distrib || $is_current_user_resp_amap ) {
								$ret .= '<button type="button" class="btn btn-default dist-desinscrire-button" data-dist="' . $dist->ID . '" data-user="' . $resp->ID . '">Désinscrire</button>';
							}
						}
					}
					$ret .= '</td>';
//                    $ret .= '</div>';
				}
//                if ($is_current_user_resp_amap) {
////                    $ret .= '<div class="' . $colspan_cls . '"><a href="' . admin_url('post.php?post=' . $dist->ID . '&action=edit') . '" class="btn btn-default distib-inscr-other" target="_blank">Inscrire d\'autres amapiens</a></div>';
//                    $ret .= '<td class="' . $colspan_cls . ' incr-list-resp inscr-admin"><a href="' . admin_url('post.php?post=' . $dist->ID . '&action=edit') . '" class="btn btn-default distib-inscr-other" target="_blank">Inscrire d\'autres amapiens</a></td>';
//                }

//                $ret .= '</td>';
//                }
			}
			$ret .= '</tr>';
		}

		$ret .= '</tbody>';
		$ret .= '</table>';
	}

//    $ret .= '</div>';

	return $ret;
}

//add_action('init', function () {
add_action( 'wp_ajax_desinscrire_distrib_action', function () {
	$dist_id    = intval( $_POST['dist'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsable( $dist_id ) || amapress_can_access_admin() ) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}


	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $dist->desinscrireResponsable( $user_id ) ) {
		case 'not_inscr':
			if ( $is_current ) {
				echo '<p class="error">Vous n\'êtes pas inscrit</p>';
			} else {
				echo '<p class="error">Non inscrit</p>';
			}
			break;
		case 'ok':
			if ( $is_current ) {
				echo '<p class="success">Votre désinscription a bien été prise en compte</p>';
			} else {
				echo '<p class="success">Désinscription bien prise en compte</p>';
			}
			break;
	}
	die();
} );
add_action( 'wp_ajax_inscrire_distrib_action', function () {
	$dist_id    = intval( $_POST['dist'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = amapress_current_user_id() == $user_id;
	if ( ! $is_current && ! ( AmapressDistributions::isCurrentUserResponsable( $dist_id )
	                          || amapress_can_access_admin()
	                          || AmapressDistributions::isCurrentUserResponsableThisWeek()
	                          || AmapressDistributions::isCurrentUserResponsableNextWeek()
		) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $dist->inscrireResponsable( $user_id ) ) {
		case 'already_in_list':
			if ( $is_current ) {
				echo '<p class="error">Vous êtes déjà inscrit</p>';
			} else {
				echo '<p class="error">Déjà inscrit</p>';
			}
			break;
		case 'list_full':
			echo '<p class="error">La distribution est déjà complète</p>';
			break;
		case 'ok':
			if ( $is_current ) {
				echo '<p class="success">Votre inscription a bien été prise en compte</p>';
			} else {
				echo '<p class="success">Inscription bien prise en compte</p>';
			}
			break;
	}
	die();
} );

function amapress_next_distrib_shortcode( $atts, $content = null, $tag = null ) {
	$atts    = shortcode_atts(
		array(
			'lieu'    => null,
			'contrat' => null,
		), $atts
	);
	$lieu_id = null;
	if ( ! empty( $atts['lieu'] ) ) {
		$lieu_id = Amapress::resolve_post_id( $atts['lieu'], AmapressLieu_distribution::INTERNAL_POST_TYPE );
	}
	$contrat_instance_id = null;
	if ( ! empty( $atts['contrat'] ) ) {
		$contrat_instance_id = Amapress::resolve_post_id( $atts['contrat'], AmapressContrat_instance::INTERNAL_POST_TYPE );
	}
	$next_distrib = AmapressDistribution::getNextDistribution( $lieu_id, $contrat_instance_id );

	switch ( $tag ) {
		case 'next-distrib-href';
			if ( $next_distrib ) {
				return $next_distrib->getPermalink();
			}
			break;
		case 'next-distrib-link';
			if ( $next_distrib ) {
				return Amapress::makeLink( $next_distrib->getPermalink(), $content, false );
			}
			break;
		case 'next-distrib-date';
			if ( $next_distrib ) {
				return date_i18n( 'd/m/Y H:i', $next_distrib->getStartDateAndHour() );
			} else {
				return 'Pas de prochaine distribution';
			}
		case 'next-emargement-href';
			if ( $next_distrib ) {
				return $next_distrib->getListeEmargementHref();
			}
			break;
		case 'next-emargement-link';
			if ( $next_distrib ) {
				return Amapress::makeLink( $next_distrib->getListeEmargementHref(), $content, false );
			}
			break;
		case 'amapress-redirect-next-distrib';
			wp_redirect_and_exit( $next_distrib->getPermalink() );
			break;
		case 'amapress-redirect-next-emargement';
			wp_redirect_and_exit( $next_distrib->getListeEmargementHref() );
			break;
	}

	return $content;
}
//});