<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_post-its', 'amapress_register_resp_distrib_post_its', 10, 2 );
function amapress_register_resp_distrib_post_its( $post_its, $args ) {
	if ( ! amapress_is_user_logged_in() ) {
		return $post_its;
	}

	$is_resp_amap = amapress_can_access_admin();
	$user_id      = amapress_current_user_id();

	$arg_next_distribs = ! empty( $args['distrib'] ) ? $args['distrib'] : 2;
	$next_distribs     = AmapressDistribution::getNextDistribs( null, $arg_next_distribs, $arg_next_distribs );

	foreach ( $next_distribs as $dist ) {
		if ( empty( $dist->getContratIds() ) ) {
			continue;
		}

		$content = '';
		$lieu    = $dist->getLieu();
		if ( in_array( $user_id, $dist->getResponsablesIds() ) ) {
			$content .= '<p class="resp-distribution">Vous êtes responsable de distribution</p>';
		} elseif ( ! $is_resp_amap ) {
			continue;
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

function amapress_responsables_distrib_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts = shortcode_atts( array(
		'distrib' => 2,
	), $atts );

	$arg_next_distribs = ! empty( $atts['distrib'] ) ? $atts['distrib'] : 2;
	$next_distribs     = AmapressDistribution::getNextDistribs( null, $arg_next_distribs, $arg_next_distribs );

	$ret = '';
	foreach ( $next_distribs as $dist ) {
		if ( empty( $dist->getContratIds() ) ) {
			continue;
		}

		$content = '';
		$lieu    = $dist->getLieu();
		$content .= '<p>' . esc_html( $lieu->getShortName() ) . '</p>';
		if ( empty( $dist->getResponsables() ) ) {
			$content .= '<p><strong>Aucun responsable inscrit</strong></p>';
		} else {
			foreach ( $dist->getResponsables() as $responsable ) {
				$content .= '<p>' . esc_html( $responsable->getDisplayName() ) . ' : ' . $responsable->getTelTo() . '</p>';
			}
		}

		$content .= '<p>' . Amapress::makeLink( Amapress::get_collectif_page_href(), 'Contacts collectif' ) . '</p>';

		$ret .= amapress_get_panel_start_no_esc( Amapress::makeLink( $dist->getPermalink(), date_i18n( 'd/m/Y', $dist->getDate() ) . ' - Responsables' ) );
		$ret .= $content;
		$ret .= amapress_get_panel_end();
	}

	return '<div class="resp-distribution-contacts">' . $ret . '</div>';
}

function amapress_inscription_distrib_shortcode( $atts, $content = null, $tag = '' ) {
	$atts = shortcode_atts( array(
		'show_past'                 => 'false',
		'show_next'                 => 'true',
		'show_email'                => 'default',
		'show_tel'                  => 'default',
		'show_tel_fixe'             => 'default',
		'show_tel_mobile'           => 'default',
		'show_adresse'              => 'false',
		'show_avatar'               => 'default',
		'show_roles'                => 'false',
		'show_for_resp'             => 'true',
		'show_title'                => 'true',
		'for_emargement'            => 'false',
		'for_pdf'                   => 'false',
		'past_weeks'                => 5,
		'max_dates'                 => - 1,
		'responsive'                => 'scroll',
		'user'                      => null,
		'lieu'                      => null,
		'date'                      => null,
		'distrib_links'             => 'true',
		'show_contrats_desc'        => 'true',
		'show_contrats_count'       => 'false',
		'show_no_contrat'           => 'true',
		'inscr_all_distrib'         => 'false',
		'allow_resp_dist_manage'    => 'false',
		'allow_gardiens'            => Amapress::getOption( 'enable-gardiens-paniers' ) ? 'true' : 'false',
		'allow_gardiens_comments'   => 'true',
		'allow_slots'               => 'true',
		'show_responsables'         => 'true',
		'manage_all_subscriptions'  => 'false',
		'prefer_inscr_button_first' => 'true',
		'column_date_width'         => '4em',
		'fixed_column_width'        => '%',
		'scroll_x'                  => '',
		'scroll_y'                  => '',
		'font_size'                 => '11px',
		'key'                       => '',
	), $atts );

	$for_pdf                 = Amapress::toBool( $atts['for_pdf'] );
	$for_emargement          = Amapress::toBool( $atts['for_emargement'] );
	$fixed_column_width      = $atts['fixed_column_width'];
	$column_date_width       = $atts['column_date_width'];
	$show_contrats_desc      = Amapress::toBool( $atts['show_contrats_desc'] );
	$show_contrats_count     = Amapress::toBool( $atts['show_contrats_count'] );
	$allow_gardiens          = Amapress::toBool( $atts['allow_gardiens'] );
	$allow_gardiens_comments = Amapress::toBool( $atts['allow_gardiens_comments'] );
	$distrib_links           = Amapress::toBool( $atts['distrib_links'] );
	$responsive              = $atts['responsive'];
	if ( $for_pdf ) {
		$responsive = false;
	}

	if ( 'auto' === $responsive ) {
		$responsive = wp_is_mobile();
	} elseif ( 'scroll' === $responsive ) {
		if ( empty( $column_date_width ) ) {
			$column_date_width = '4em';
		}
		if ( empty( $fixed_column_width ) || '%' === $fixed_column_width ) {
			$fixed_column_width = '8em';
		}
		$atts['scroll_x'] = 'true';
		if ( empty( $atts['scroll_y'] ) ) {
			$atts['scroll_y'] = '300px';
		}
	} else {
		$responsive = Amapress::toBool( $responsive );
	}
	if ( ! empty( $atts['scroll_x'] ) ) {
		$responsive = false;
	}

	if ( '%' == $fixed_column_width ) {
		$atts['scroll_x'] = '';
	}

	$allow_anonymous_access = false;
	$ret                    = '';
	$key                    = $atts['key'];
	if ( 'anon-inscription-distrib' == $tag ) {
		if ( amapress_can_access_admin() ) {
			$sample_key = uniqid() . uniqid();
			$url        = add_query_arg( 'key', $key, get_permalink() );
			if ( empty( $_REQUEST['key'] ) ) {
				if ( empty( $key ) ) {
					$ret .= amapress_get_panel_start( 'Configuration' );
					$ret .= '<div style="color:red">Ajoutez la clé suivante à votre shortcode : ' . $sample_key . '<br/>De la forme : [' . $tag . ' key=' . $sample_key . ']</div>';
				} else {
					$ret .= amapress_get_panel_start( 'Information d\'accès pour le collectif' );
					$ret .= '<div class="alert alert-info">Pour donner accès à cette page d\'inscription aux distributions, veuillez envoyer à vos amapiens le lien suivant : 
<pre>' . $url . '</pre>
Pour y accéder cliquez <a href="' . $url . '">ici</a>.<br />
Vous pouvez également utiliser un service de réduction d\'URL tel que <a href="https://bit.ly">bit.ly</a> pour obtenir une URL plus courte à partir du lien ci-dessus.<br/>
' . ( ! empty( $atts['shorturl'] ) ? 'Lien court sauvegardé : <code>' . $atts['shorturl'] . '</code><br />' : '' ) . '
Vous pouvez également utiliser l\'un des QRCode suivants : 
<div>' . amapress_print_qrcode( $url ) . amapress_print_qrcode( $url, 3 ) . amapress_print_qrcode( $url, 2 ) . '</div><br/>
<strong>Attention : les lien ci-dessus, QR code et bit.ly NE doivent PAS être visible publiquement sur le site. Ce lien permet d\'accéder à la page d\'inscription aux distributions (mais uniquement) sans saisir son mot de passe sur le site et l\'exposer sur internet pourrait permettre à une personne malvaillante de polluer le site.</strong></div>';
					$ret .= amapress_get_panel_end();
				}
			} else {
				$ret .= '<div class="alert alert-info"><a href="' . esc_attr( get_permalink() ) . '">Afficher les instructions d\'accès à cette page.</a></div>';
			}
		}
		if ( empty( $key ) || empty( $_REQUEST['key'] ) || $_REQUEST['key'] != $key ) {
			if ( empty( $key ) && amapress_can_access_admin() ) {
				$ret .= 'Une fois le shortcode configuré : seuls les amapiens dirigés depuis l\'url contenant cette clé pourront s\'inscrire sans mot de passe utilisateur.';
				$ret .= $content;

				return $ret;
			} elseif ( ! amapress_is_user_logged_in() ) {
				$ret .= '<div class="alert alert-danger">Vous êtes dans un espace sécurisé. Accès interdit</div>';
				$ret .= $content;

				return $ret;
			}
		}

		if ( amapress_is_user_logged_in() ) {
			$user_id = amapress_current_user_id();
			if ( ! empty( $atts['user'] ) ) {
				$user_id = Amapress::resolve_user_id( $atts['user'] );
			}
		} else {
			if ( empty( $_REQUEST['email'] ) ) {
				ob_start();
				?>
                <form method="post" action="<?php echo add_query_arg( 'key', $key, get_permalink() ); ?>"
                      id="inscr_email"
                      class="amapress_validate">
                    <label for="email">Pour pouvoir vous inscrire en tant que responsable de distribution, renseignez
                        votre
                        adresse mail :</label>
                    <input id="email" name="email" type="text" class="email required" placeholder="email"/>
                    <input type="submit" value="Valider" class="btn btn-default"/>
                </form>
				<?php
				return ob_get_clean();
			} else {
				$email = sanitize_email( $_REQUEST['email'] );
				$user  = get_user_by( 'email', $email );
				if ( ! $user ) {
					return '<p style="font-weight: bold">Adresse email inconnue, accès interdit.</p>
<p>Si vous êtes déjà membre de l’AMAP, vous avez certainement utilisé une adresse email différente.</p>
<p><a href="' . get_permalink() . '">Changer d’email</a></p>';
				}

				$allow_anonymous_access = true;
				$user_id                = $user->ID;
			}
		}
	} else {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$user_id = amapress_current_user_id();
		if ( ! empty( $atts['user'] ) ) {
			$user_id = Amapress::resolve_user_id( $atts['user'] );
		}
	}

	$allow_resp_dist_manage    = Amapress::toBool( $atts['allow_resp_dist_manage'] );
	$inscr_all_distrib         = Amapress::toBool( $atts['inscr_all_distrib'] );
	$manage_all_subscriptions  = Amapress::toBool( $atts['manage_all_subscriptions'] ) && amapress_can_access_admin();
	$prefer_inscr_button_first = Amapress::toBool( $atts['prefer_inscr_button_first'] );
	if ( $for_emargement || $for_pdf ) {
		$prefer_inscr_button_first = false;
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

	$adhesions             = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( $user_id, null, $from_date, false, $allow_anonymous_access );
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
		$from_date = max( Amapress::add_a_week( amapress_time(), - intval( $atts['past_weeks'] ) ), $from_date );
	}
	$to_date = amapress_time();
	if ( Amapress::toBool( $atts['show_next'] ) ) {
		$to_date = null;
	}
	$is_current_user_resp_amap = amapress_can_access_admin() || user_can( $user_id, 'manage_distributions' );
	$is_resp_distrib           = $is_current_user_resp_amap || AmapressDistributions::isCurrentUserResponsableThisWeek( $user_id, $from_date ) || AmapressDistributions::isCurrentUserResponsableNextWeek( $user_id, $from_date );
	$current_post              = get_post();
	if ( $current_post && $current_post->post_type == AmapressDistribution::INTERNAL_POST_TYPE ) {
		$is_resp_distrib = $is_current_user_resp_amap || AmapressDistributions::isCurrentUserResponsable( $current_post->ID, $user_id );
	}

	$allow_manage_others = ( ( $is_resp_distrib && $allow_resp_dist_manage ) || $is_current_user_resp_amap );
	if ( ! Amapress::toBool( $atts['show_for_resp'] ) ) {
		$allow_manage_others = false;
	} elseif ( $allow_manage_others ) {
		$allow_manage_others = is_admin() || ! empty( $_GET['for_resp'] );
	}


	if ( $for_pdf ) {
		$is_current_user_resp_amap = false;
		$is_resp_distrib           = false;
		$allow_manage_others       = false;
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

	$btn_class = ( is_admin() ? 'button button-secondary' : 'btn btn-default' );

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
		$user_lieux_ids = AmapressUsers::get_user_lieu_ids( $user_id, $from_date );
		/** @var AmapressDistribution $dist */
		foreach ( $all_dists as $dist ) {
			if ( ! $inscr_all_distrib && ! in_array( $dist->getLieuId(), $user_lieux_ids ) ) {
				continue;
			}
			$dist_contrat_ids = $dist->getContratIds();
			if ( $inscr_all_distrib || count( array_intersect( $adhesions_contrat_ids, $dist_contrat_ids ) ) > 0 ) {
				$max_dates --;
				if ( $max_dates < 0 ) {
					continue;
				}
				$dists[] = $dist;
			}
		}
	}

	$has_slots = false;
	foreach ( $dists as $dist ) {
		if ( ! empty( $dist->getSlotsConf() ) ) {
			$has_slots = true;
			break;
		}
	}
	if ( ! Amapress::toBool( $atts['allow_slots'] ) ) {
		$has_slots = false;
	}

	if ( ! is_admin() && $is_current_user_resp_amap ) {
		if ( ! $allow_manage_others ) {
			$ret .= '<p style="text-align: center">' . Amapress::makeButtonLink( add_query_arg( 'for_resp', 'T' ), 'Choisir le mode Administrateur' ) . '</p>';
		} else {
			$ret .= '<p style="text-align: center">' . Amapress::makeButtonLink( remove_query_arg( 'for_resp' ), 'Choisir le mode Amapien' ) . '</p>';
		}
	}

	$show_responsables = Amapress::toBool( $atts['show_responsables'] );
	//optimize producteur load
	Amapress::get_producteurs();

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

//	$ret .= '<script type="">jQuery(function($) {
//    $(".distrib-inscr-list").DataTable({
//    	"paginate": true
//    });
//});</script>';

	if ( is_numeric( $show_contrats_desc ) ) {
		$ret .= '<style type="text/css">.inscr-list-contrats {
-webkit-line-clamp: ' . $show_contrats_desc . ' !important;overflow: hidden;display: -webkit-box;-webkit-box-orient: vertical; }</style>';
	}

	foreach ( $all_user_lieux as $lieu_id ) {
		$user_lieu = AmapressLieu_distribution::getBy( $lieu_id );
		if ( Amapress::toBool( $atts['show_title'] ) ) {
			$ret .= '<h4 class="distrib-inscr-lieu">' . esc_html( $user_lieu->getShortName() ) . '</h4>';
		}
		if ( ! $for_pdf && current_user_can( 'edit_lieu_distribution' ) && ! is_admin() ) {
			$ret .= '<p style="text-align: center"><a class="' . $btn_class . '" href="' . $user_lieu->getAdminEditLink() . '#amapress_lieu_distribution_nb_responsables">Modifier le nombre de responsables de distribution du lieu</a></p>';
		}

		if ( $for_pdf ) {
			$responsive = false;
			$data_atts  = '';
		} else {
			if ( ! empty( $atts['font_size'] ) ) {
				$ret .= '<style type="text/css">.distrib-inscr-list { font-size: ' . $atts['font_size'] . ' !important;}</style>';
			}
			$data_atts = [];
			if ( ! empty( $atts['scroll_x'] ) ) {
				$data_atts['scroll-x']        = $atts['scroll_x'];
				$data_atts['fixed-columns']   = 'true';
				$data_atts['scroll-collapse'] = 'false';
			}
			if ( ! empty( $atts['scroll_y'] ) ) {
				$data_atts['scroll-y'] = $atts['scroll_y'];
			}
			$data_atts['auto-width'] = 'false';
			$data_atts               = implode( ' ', array_map( function ( $k, $v ) {
				return 'data-' . $k . '="' . esc_attr( $v ) . '"';
			}, array_keys( $data_atts ), array_values( $data_atts ) ) );
		}
		$table_id = 'inscr-distrib-table-' . $lieu_id;
		if ( $for_pdf || '%' == $fixed_column_width ) {
			$ret .= '<table ' . $data_atts . ' id="' . $table_id . '" class="distrib-inscr-list table display smart-word-break ' . ( $responsive ? 'responsive ' : '' ) . '" width="100%" style="table-layout: fixed;" cellspacing="0">';
		} else {
			$ret .= '<table ' . $data_atts . ' id="' . $table_id . '" class="distrib-inscr-list table display smart-word-break ' . ( $responsive ? 'responsive ' : '' ) . '" style="width:auto;table-layout: fixed;" cellspacing="0">';
		}
		$calc = [];
		$ret  .= '<thead >';
		$ret  .= '<tr>';
		if ( $for_pdf ) {
			$ret .= '<th class="dist-col-date">Date</th>';
		} else {
			$calc[] = $column_date_width;
			$ret    .= '<th class="dist-col-date" data-width="' . $column_date_width . '"  style="width: ' . $column_date_width . ';min-width: ' . $column_date_width . '">Date</th>';
		}
		if ( $for_emargement ) {
			if ( $for_pdf ) {
				$ret .= '<th>Produits</th>';
			} else {
				$calc[] = $column_date_width;
				$ret    .= '<th data-width="' . $column_date_width . '" style="width: ' . $column_date_width . ';min-width: ' . $column_date_width . '">Produits</th>';
			}
		}
		if ( '%' == $fixed_column_width ) {
			$width = ! $for_pdf ? 'calc(100% / ' . $lieux_needed_resps[ $lieu_id ] . ' - ' . $column_date_width . ')' : '';
		} else {
			$width = ! $for_pdf ? $fixed_column_width : '';
		}
		$css_width = '';
		if ( ! empty( $width ) ) {
			$css_width = 'width:' . $width;
		}
		if ( ! $for_emargement && ! $for_pdf ) {
			if ( $has_slots ) {
				$calc[] = $fixed_column_width;
				$ret    .= '<th data-width="' . $width . '" style="width:' . $width . ';min-width:' . $width . ';text-align: center">Créneau horaire</th>';
			}
			if ( $allow_gardiens ) {
				$calc[] = $fixed_column_width;
				$ret    .= '<th data-width="' . $width . '" style="width:' . $width . ';min-width:' . $width . ';text-align: center">Garde panier</th>';
			}
		}

		$has_role_names = false;
		/** @var AmapressLieu_distribution $user_lieu */
		/** @var AmapressLieu_distribution $user_lieu */
//        foreach ($user_lieux as $lieu_id) {
		for ( $i = 1; $i <= ( $show_responsables ? $lieux_needed_resps[ $lieu_id ] : 0 ); $i ++ ) {
			$role_name = stripslashes( Amapress::getOption( "resp_role_{$lieu_id}_$i-name" ) );
			if ( empty( $role_name ) ) {
				$role_name = stripslashes( Amapress::getOption( "resp_role_$i-name" ) );
			}
			if ( empty( $role_name ) ) {
				$role_name = "Responsable $i";
			} else {
				$has_role_names = true;
			}
			$role_desc = stripslashes( Amapress::getOption( "resp_role_{$lieu_id}_$i-desc" ) );
			if ( empty( $role_name ) ) {
				$role_desc = stripslashes( Amapress::getOption( "resp_role_$i-desc" ) );
			}
			if ( $has_role_names ) {
				$role_desc = '<br/><span class="role-distrib-desc">' . $role_desc . '</span>';
			}
			if ( ! $for_pdf ) {
				$calc[] = $fixed_column_width;
				$ret    .= '<th class="distrib-resp-head" data-width="' . $width . '" style="width:' . $width . ';min-width:' . $width . '" title="' . esc_attr( strip_tags( $role_desc ) ) . '">' . esc_html( $role_name ) . $role_desc . '</th>';
			} else {
				$ret .= '<th class="distrib-resp-head" data-width="' . $width . '" title="' . esc_attr( strip_tags( $role_desc ) ) . '">' . esc_html( $role_name ) . $role_desc . '</th>';
			}
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

			return ( $da < $db ? - 1 : 1 );
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
					if ( ! in_array( $c->getModelTitle(), $contrat_names ) ) {
						$contrat_names[] = $c->getModelTitle();
					}
				}
			}
			sort( $contrat_names );
			$no_contrat_users = [];
			/** @var WP_User $user */
			foreach (
				get_users_cached( array(
					'amapress_contrat' => 'no',
					'fields'           => 'all_with_meta',
				) ) as $user
			) {
				$user_name                     = sprintf( '%s (%s)', $user->display_name, $user->user_email );
				$no_contrat_users[ $user->ID ] = $user_name;
			}
			$hours = '';
			if ( ! empty( $dist->getSpecialHeure_debut() ) || ! empty( $dist->getSpecialHeure_fin() ) ) {
				$hours .= sprintf( ' (%s à %s)',
					date_i18n( 'H:i', $dist->getStartDateAndHour() ),
					date_i18n( 'H:i', $dist->getEndDateAndHour() ) );
			}
			$lieu_users       = array();
			$contrat_count    = count( $contrat_names ) . ' contrat(s) livré(s)';
			$contrat_names    = implode( ', ', $contrat_names );
			$contrats_content = '';
			if ( $show_contrats_desc ) {
				$contrats_content .= '<p class="inscr-list-contrats" title="' . esc_attr( $contrat_names ) . '">' . esc_html( $contrat_names ) . '</p>';
			}
			if ( $show_contrats_count ) {
				$contrats_content .= '<p class="inscr-list-contrats" title="' . esc_attr( $contrat_names ) . '">' . esc_html( $contrat_count ) . '</p>';
			}
			if ( ! $for_pdf && ! $for_emargement && Amapress::toBool( $atts['show_no_contrat'] ) ) {
				if ( ! $dist->isUserMemberOf( $user_id, true, $adhesions ) ) {
					$contrats_content .= '<p class="inscr-list-contrats"><strong>Pas de livraison pour vous</strong></p>';
				}
			}
			$date_display = date_i18n( 'D j M Y', $date ) . $hours;
			$date_content = '<p class="inscr-list-date">' .
			                ( $distrib_links ? Amapress::makeLink( $dist->getPermalink(), $date_display, true, true ) : esc_html( $date_display ) ) .
			                '</p>';
			if ( ! $for_pdf ) {
				$ret .= '<th scope="row" class="inscr-list-info dist-col-date smart-word-break; padding:5px" style="width: ' . $column_date_width . ';min-width: ' . $column_date_width . ';vertical-align: middle">';
			} else {
				$ret .= '<th scope="row" class="inscr-list-info dist-col-date smart-word-break; padding:5px" style="vertical-align: middle">';
			}
			$ret .= $date_content;
			if ( ! $for_emargement ) {
				$ret .= $contrats_content;
			}
			if ( ! empty( $subst_lieux ) ) {
				$subst_lieux = implode( ', ', $subst_lieux );
				$ret         .= '<p class="inscr-list-lieux-substitution">exceptionnellement à ' . esc_html( $subst_lieux ) . '</p>';
			}
			$ret .= '</th>';
			if ( $for_emargement ) {
				$ret .= '<td>' . $contrats_content . '</td>';
			}
//            foreach ($user_lieux as $lieu_id) {
			$needed    = 0;
			$user_lieu = AmapressLieu_distribution::getBy( $lieu_id );
			foreach ( $date_dists as $dist ) {
				if ( $dist->getLieuId() != $user_lieu->ID ) {
					continue;
				}

//                $ret .= '<td>';
				$is_user_part_of = $inscr_all_distrib || $dist->isUserMemberOf( $user_id, true, $adhesions );
				$resps           = $dist->getResponsables();
				$needed          = AmapressDistributions::get_required_responsables( $dist->ID );
				$row_resps       = [];
				for ( $i = 0; $i < $needed; $i ++ ) {
					$row_resps[ $i ] = null;
				}
				$can_unsubscribe = ! $for_pdf && ( $manage_all_subscriptions || amapress_can_access_admin() || Amapress::start_of_week( $date ) > Amapress::start_of_week( amapress_time() ) );
				$can_subscribe   = ! $for_pdf && ( $manage_all_subscriptions || amapress_can_access_admin() || Amapress::start_of_day( $date ) >= Amapress::start_of_day( amapress_time() ) );

				if ( ! isset( $lieu_users[ $lieu_id ] ) ) {
					$arr = array( '' => '-amapien-' );
					/** @var WP_User $user */
					foreach (
						get_users_cached( array(
							'amapress_lieu' => $lieu_id,
							'fields'        => 'all_with_meta',
						) ) as $user
					) {
						$amapien   = AmapressUser::getBy( $user->ID );
						$user_name = sprintf( '%s (%s)', $user->display_name, $user->user_email );
						if ( ! empty( $amapien->getCoAdherents() ) ) {
							$user_name .= ' (' . $amapien->getCoAdherents() . ')';
						}
						$arr[ $user->ID ] = $user_name;
					}
					$lieu_users[ $lieu_id ] = $arr;
				}
				$users = $lieu_users[ $lieu_id ];
				$users += $no_contrat_users;
//                $desinscr_another = '';
//                if ($is_resp_distrib && $can_subscribe) {
//                    $desinscr_another = '<button type="button" class="btn btn-default dist-inscrire-button" data-dist="' . $dist->ID . '">Désinscrire</button>';
//                }
				$is_resp = false;
				foreach ( $resps as $resp ) {
					$is_resp = $is_resp || $resp->ID == $user_id;
					if ( $is_resp ) {
						break;
					}
				}

				if ( ! $for_emargement && ! $for_pdf && $has_slots ) {
					$ret                   .= "<td style='$css_width' class='resp-col incr-list-resp incr-missing'>";
					$can_change_slot       = $is_user_part_of && amapress_time() < ( $dist->getStartDateAndHour()
					                                                                 - Amapress::getOption( 'inscr-distribution-slot-close' ) * HOUR_IN_SECONDS );
					$slot_for_current_user = $dist->getSlotInfoForUser( $user_id );
					if ( $slot_for_current_user ) {
						$ret .= esc_html( $slot_for_current_user['display'] );
						if ( $can_change_slot ) {
							$ret .= '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="distrib_desinscrire_slot" data-confirm="Etes-vous sûr vous désinscrire de ce créneau ?"
					data-dist="' . $dist->ID . '" data-slot="' . strval( $slot_for_current_user['date'] ) . '">Désinscrire</button>';
						}
					} else {
						if ( $can_change_slot ) {
							$dist_slots_options = [];
							foreach ( $dist->getAvailableSlots() as $k => $conf ) {
								if ( $conf['max'] <= 0 ) {
									$dist_slots_options[ $k ] = $conf['display'];
								} else {
									$dist_slots_options[ $k ] = sprintf( '%s (%d/%d)', $conf['display'], intval( $conf['current'] ), intval( $conf['max'] ) );
								}
							}

							if ( ! empty( $dist_slots_options ) ) {
								$affect_slot_id = 'affect-slot-' . $dist->ID;
								$affect_slot    = "<select id='$affect_slot_id'>";
								$affect_slot    .= tf_parse_select_options( $dist_slots_options, null, false );
								$affect_slot    .= '</select>';
								$affect_slot    .= '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="distrib_inscrire_slot" data-confirm="Etes-vous sûr de vous inscrire à ce créneau ?"
					data-dist="' . $dist->ID . '" data-slot="val:#' . $affect_slot_id . '">Réserver</button>';
								$ret            .= $affect_slot;
							} else {
								$ret .= 'aucun';
							}
						} elseif ( $is_user_part_of ) {
							$ret .= '<span style="color: orange">clos</span>';
						}
					}
					$ret .= "</td>";
				}

				if ( ! $for_emargement && ! $for_pdf && $allow_gardiens ) {
					$inscr_another = '';
					if ( $allow_manage_others && $can_subscribe ) {
						$inscr_another = '';
						if ( ! is_admin() ) {
							$inscr_another .= '<form class="inscription-distrib-other-user" action="#">';
						}
						$inscr_another .= '<div class="inscription-other-user">
<select name="user" class="autocomplete ' . ( is_admin() ? '' : 'required' ) . '">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="' . $btn_class . ' dist-inscrire-button" data-confirm="Etes-vous sûr de vouloir inscrire cet amapien comme gardien de panier ?" data-message="val:#garde-msg-' . $dist->ID . '" data-gardien="T" data-dist="' . $dist->ID . '">Inscrire</button>
</div>';
						if ( ! is_admin() ) {
							$inscr_another .= '</form>';
						}
						$inscr_another .= '<p><a href="' . admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_other_user' ) . '" title="Si la personne est introuvable dans la liste ci-dessus, vous pouvez l\'inscrire avec son nom et/ou email et/ou téléphone">Ajouter un utilisateur</a></a></p>';
					}

					if ( $allow_gardiens_comments ) {
						$inscr_self = '<textarea id="garde-msg-' . $dist->ID . '" rows="2" style="display: block; width: 100%; padding: 0" placeholder="Message"></textarea>';
					} else {
						$inscr_self = '';
					}
					$inscr_self .= '<button type="button" class="' . $btn_class . ' dist-inscrire-button"  data-confirm="Etes-vous sûr de vouloir vous proposer comme gardien de panier ?" data-not_member="' . $inscr_all_distrib . '" data-message="val:#garde-msg-' . $dist->ID . '" data-gardien="T" data-dist="' . $dist->ID . '" data-user="' . $user_id . '" data-post-id="' . ( $current_post ? $current_post->ID : 0 ) . '" data-key="' . $key . '">Me proposer</button>';
					$info       = '';
					if ( ! $for_pdf ) {
						if ( ! $can_subscribe ) {
							$inscr_self = '';
						}
						if ( in_array( amapress_current_user_id(), $dist->getGardiensIds() ) ) {
							if ( $can_unsubscribe ) {
								$inscr_self = '<button type="button" class="' . $btn_class . ' dist-desinscrire-button"  data-confirm="Etes-vous sûr de vouloir ne plus vous proposer comme gardien de panier ?" data-not_member="' . $inscr_all_distrib . '" data-gardien="T" data-dist="' . $dist->ID . '" data-user="' . $user_id . '" data-post-id="' . ( $current_post ? $current_post->ID : 0 ) . '" data-key="' . $key . '">Ne plus me proposer</button>';
							} else {
								$inscr_self = '';
							}
						}
						if ( empty( $dist->getGardiensIds() ) ) {
							$info = "<span class='distrib-resp-missing'>0 gardien</span>";
						} else {
							$info .= '<a href="' . $dist->getPermalink() . '" target="_blank">' . count( $dist->getGardiensIds() ) . ' gardien(s)</a>';
						}
					}
					if ( $is_user_part_of ) {
						$ret .= "<td style='$css_width' class='resp-col incr-list-resp incr-missing'>$info$inscr_self$inscr_another</td>";
					} else {
						$ret .= "<td style='$css_width' class='resp-col incr-list-resp incr-not-part'>$info$inscr_another</td>";
					}
				}

				if ( $show_responsables ) {
					if ( $has_role_names ) {
						usort( $resps, function ( $a, $b ) use ( $dist ) {
							$role_a = $dist->getResponsableRoleId( $a );
							if ( empty( $role_a ) ) {
								$role_a = 999;
							}
							$role_b = $dist->getResponsableRoleId( $b );
							if ( empty( $role_b ) ) {
								$role_b = 999;
							}
							if ( $role_a == $role_b ) {
								return 0;
							}

							return $role_a < $role_b ? - 1 : 1;
						} );
						$max_resp_role = 0;
						foreach ( $resps as $r ) {
							$role = $dist->getResponsableRoleId( $r->ID );
							if ( $role > 0 ) {
								$row_resps[ $role - 1 ] = $r;
							}
							$max_resp_role = $role > $max_resp_role ?
								$role : $max_resp_role;
						}

						$start = $max_resp_role;
						foreach ( $resps as $r ) {
							if ( $start >= $needed ) {
								if ( ! is_array( $row_resps[ $needed - 1 ] ) ) {
									$row_resps[ $needed - 1 ] = [ $row_resps[ $needed - 1 ] ];
								}
								$role = $dist->getResponsableRoleId( $r->ID );
								if ( $role <= 0 ) {
									$row_resps[ $needed - 1 ][] = $r;
								}
								continue;
							}
							$role = $dist->getResponsableRoleId( $r->ID );
							if ( $role <= 0 ) {
								$row_resps[ $start ] = $r;
								$start               += 1;
							}
						}
					} else {
						usort( $resps, function ( $resp, $b ) use ( $user_id ) {
							if ( $resp->ID == $user_id ) {
								return - 1;
							} else {
								return 0;
							}
						} );

						if ( $prefer_inscr_button_first ) {
							$start = $needed - count( $resps ) >= 0 ? $needed - count( $resps ) : 0;
						} else {
							$start = 0;
						}
						foreach ( $resps as $r ) {
							if ( $start >= $needed ) {
								break;
							}

							$row_resps[ $start ] = $r;
							$start               += 1;
						}
					}
				} else {
					$row_resps = [];
				}

				$added_inscr_button = false;
				$i                  = 1;
				foreach ( $row_resps as $resp ) {
					$resp_idx = ! $has_role_names ? 0 : $i;
					if ( null == $resp ) {
						$inscr_another = '';
						if ( $allow_manage_others && $can_subscribe ) {
							$inscr_another = '';
							if ( ! is_admin() ) {
								$inscr_another .= '<form class="inscription-distrib-other-user" action="#">';
							}
							$inscr_another .= '<div class="inscription-other-user">
<select name="user" class="autocomplete ' . ( is_admin() ? '' : 'required' ) . '">' . tf_parse_select_options( $users, null, false ) . '</select>
<button type="button" class="' . $btn_class . ' dist-inscrire-button" data-confirm="Etes-vous sûr de vouloir inscrire cet amapien ?" data-role="' . $resp_idx . '" data-dist="' . $dist->ID . '">Inscrire</button>
</div>';
							if ( ! is_admin() ) {
								$inscr_another .= '</form>';
							}
							$inscr_another .= '<p><a href="' . admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_other_user' ) . '" title="Si la personne est introuvable dans la liste ci-dessus, vous pouvez l\'inscrire avec son nom et/ou email et/ou téléphone">Ajouter un utilisateur</a></a></p>';
						}

						$inscr_self = '<button type="button" class="' . $btn_class . ' dist-inscrire-button"  data-confirm="Etes-vous sûr de vouloir vous inscrire ?" data-not_member="' . $inscr_all_distrib . '" data-role="' . $resp_idx . '" data-dist="' . $dist->ID . '" data-user="' . $user_id . '" data-post-id="' . ( $current_post ? $current_post->ID : 0 ) . '" data-key="' . $key . '">M\'inscrire</button>';
						$missing    = '';
						if ( ! $for_pdf ) {
							if ( ( $has_role_names || ! $added_inscr_button ) && ! $is_resp && $can_subscribe ) {
								$missing            = $inscr_self;
								$added_inscr_button = true;
							} else {
								$missing = "<span class='distrib-resp-missing'>manquant</span>";
							}
						}
						if ( $is_user_part_of ) {
							$ret .= "<td style='$css_width' class='resp-col incr-list-resp incr-missing'>$missing$inscr_another</td>";
						} else {
							$ret .= "<td style='$css_width' class='resp-col incr-list-resp incr-not-part'>$inscr_another</td>";
						}
					} else {
						$ret      .= '<td style="' . $css_width . ';text-align: center">';
						$td_resps = is_array( $resp ) ? $resp : [ $resp ];
						foreach ( $td_resps as $r ) {
							if ( ! $r ) {
								continue;
							}
							$ret .= $r->getDisplay( $atts );
							if ( $is_user_part_of || $allow_manage_others ) {
								$is_resp = $is_resp || $r->ID == $user_id;
								if ( $can_unsubscribe ) {
									if ( $r->ID == $user_id ) {
										$ret .= '<button type="button" class="' . $btn_class . ' dist-desinscrire-button" data-confirm="Etes-vous sûr de vouloir vous désinscrire ?" data-dist="' . $dist->ID . '" data-user="' . $user_id . '" data-post-id="' . $current_post->ID . '" data-key="' . $key . '">Me désinscrire</button>';
									} else if ( $allow_manage_others ) {
										$ret .= '<button type="button" class="' . $btn_class . ' dist-desinscrire-button" data-confirm="Etes-vous sûr de vouloir désinscrire cet amapien ?" data-dist="' . $dist->ID . '" data-user="' . $r->ID . '">Désinscrire</button>';
									}
								}
							}
						}
						$ret .= '</td>';
					}
					$i += 1;
				}
			}
			for ( $j = $needed; $j < $lieux_needed_resps[ $lieu_id ]; $j ++ ) {
				$ret .= "<td style='$css_width' class='resp-col'>&nbsp;</td>";
			}
			if ( 0 == $needed ) {
				if ( $has_slots ) {
					$ret .= "<td style='$css_width' class='resp-col'>&nbsp;</td>";
				}
				if ( $allow_gardiens ) {
					$ret .= "<td style='$css_width' class='resp-col'>&nbsp;</td>";
				}
			}
			$ret .= '</tr>';
		}

		$ret .= '</tbody>';
		$ret .= '</table>';

		if ( '%' !== $fixed_column_width && ! empty( $calc ) ) {
			$ret .= '<style type="text/css">#' . $table_id . '_wrapper { width: calc( 20px + ' . implode( ' + ', $calc ) . '); margin: 0 auto; }</style>';
		}

//		$ret .= '<script type="text/javascript">jQuery(function($) {$(".distrib-inscr-list").DataTable().fixedHeader.enable(true);});</script>';
	}

	$ret .= '<style type="text/css">.inscr-list-info * {
    white-space: normal !important;
}</style>';
	if ( is_admin() ) {
		$ret .= '<style type="text/css">.dist-col-date {display: none}</style>';
		$ret .= '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    jQuery(".autocomplete").each(function() { $(this).select2({
        allowClear: true,
		  escapeMarkup: function(markup) {
			return markup;
		  },
		  templateResult: function(data) {
			return $("<span>"+data.text+"</span>");
		  },
		  templateSelection: function(data) {
			return $("<span>"+data.text+"</span>");
		  },
		  width: \'auto\'
    }) });
});
//]]>
</script>';
	}

//    $ret .= '</div>';

	return $ret;
}

function amapress_histo_inscription_distrib_shortcode( $atts ) {
	$atts              = wp_parse_args( $atts );
	$atts['show_past'] = 'true';
	$atts['show_next'] = 'false';

	return amapress_inscription_distrib_shortcode( $atts );
}

add_action( 'wp_ajax_desinscrire_distrib_action', function () {
	$dist_id     = intval( $_POST['dist'] );
	$for_gardien = isset( $_POST['gardien'] ) && 'T' == $_POST['gardien'];
	$user_id     = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current  = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsable( $dist_id ) || amapress_can_access_admin() ) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}


	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $for_gardien ?
		$dist->desinscrireGardien( $user_id ) :
		$dist->desinscrireResponsable( $user_id ) ) {
		case 'not_inscr':
			if ( $is_current ) {
				echo '<p class="error">Vous n\'êtes pas inscrit</p>';
			} else {
				echo '<p class="error">Non inscrit</p>';
			}
			break;
		case 'has_gardes':
			if ( $is_current ) {
				echo '<p class="error">Des amapiens vous ont confiés des paniers à cette distributions</p>';
			} else {
				echo '<p class="error">Gardes de paniers en cours</p>';
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
	$dist_id     = intval( $_POST['dist'] );
	$for_gardien = isset( $_POST['gardien'] ) && 'T' == $_POST['gardien'];
	$user_id     = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current  = amapress_current_user_id() == $user_id;
	if ( ! $is_current && ! ( AmapressDistributions::isCurrentUserResponsable( $dist_id )
	                          || amapress_can_access_admin()
	                          || AmapressDistributions::isCurrentUserResponsableThisWeek()
	                          || AmapressDistributions::isCurrentUserResponsableNextWeek()
		) ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $for_gardien ?
		$dist->inscrireGardien( $user_id,
			false,
			isset( $_REQUEST['not_member'] ) ? Amapress::toBool( $_REQUEST['not_member'] ) : false,
			isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' ) :
		$dist->inscrireResponsable( $user_id,
			isset( $_REQUEST['role'] ) ? intval( $_REQUEST['role'] ) : 0,
			false,
			isset( $_REQUEST['not_member'] ) ? Amapress::toBool( $_REQUEST['not_member'] ) : false ) ) {
		case 'already_in_list':
			if ( $is_current ) {
				echo '<p class="error">Vous êtes déjà inscrit</p>';
			} else {
				echo '<p class="error">Déjà inscrit</p>';
			}
			break;
		case 'already_taken':
			echo '<p class="error">Rôle déjà pris</p>';
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

add_action( 'wp_ajax_nopriv_desinscrire_distrib_action', function () {
	$dist_id     = intval( $_POST['dist'] );
	$for_gardien = isset( $_POST['gardien'] ) && 'T' == $_POST['gardien'];
	$user_id     = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : 0;
	$key         = ! empty( $_POST['key'] ) ? $_POST['key'] : '';
	$post_id     = ! empty( $_POST['post-id'] ) ? intval( $_POST['post-id'] ) : 0;
	$is_ok       = false;
	if ( ! empty( $user_id ) && ! empty( $dist_id ) && ! empty( $key ) && ! empty( $post_id ) ) {
		$post = get_post( $post_id );
		if ( $post ) {
			if ( false !== strpos( $post->post_content, "key=$key" ) ) {
				$is_ok = true;
			}
		}
	}

	if ( ! $is_ok ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $for_gardien ?
		$dist->desinscrireGardien( $user_id, true ) :
		$dist->desinscrireResponsable( $user_id, true ) ) {
		case 'not_inscr':
			echo '<p class="error">Vous n\'êtes pas inscrit</p>';
			break;
		case 'ok':
			echo '<p class="success">Votre désinscription a bien été prise en compte</p>';
			break;
	}
	die();
} );
add_action( 'wp_ajax_nopriv_inscrire_distrib_action', function () {
	$dist_id     = intval( $_POST['dist'] );
	$for_gardien = isset( $_POST['gardien'] ) && 'T' == $_POST['gardien'];
	$user_id     = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$key         = ! empty( $_POST['key'] ) ? $_POST['key'] : '';
	$post_id     = ! empty( $_POST['post-id'] ) ? intval( $_POST['post-id'] ) : 0;
	$is_ok       = false;
	if ( ! empty( $user_id ) && ! empty( $dist_id ) && ! empty( $key ) && ! empty( $post_id ) ) {
		$post = get_post( $post_id );
		if ( $post ) {
			if ( false !== strpos( $post->post_content, "key=$key" ) ) {
				$is_ok = true;
			}
		}
	}

	if ( ! $is_ok ) {
		echo '<p class="error">Non autorisé</p>';
		die();
	}

	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $for_gardien ?
		$dist->inscrireGardien( $user_id,
			true,
			isset( $_REQUEST['not_member'] ) ? Amapress::toBool( $_REQUEST['not_member'] ) : false,
			isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' ) :
		$dist->inscrireResponsable( $user_id,
			isset( $_REQUEST['role'] ) ? intval( $_REQUEST['role'] ) : 0,
			true,
			isset( $_REQUEST['not_member'] ) ? Amapress::toBool( $_REQUEST['not_member'] ) : false ) ) {
		case 'already_in_list':
			echo '<p class="error">Vous êtes déjà inscrit</p>';
			break;
		case 'already_taken':
			echo '<p class="error">Rôle déjà pris</p>';
			break;
		case 'list_full':
			echo '<p class="error">La distribution est déjà complète</p>';
			break;
		case 'ok':
			echo '<p class="success">Votre inscription a bien été prise en compte</p>';
			break;
	}
	die();
} );


function amapress_next_distrib_shortcode( $atts, $content = null, $tag = null ) {
	amapress_ensure_no_cache();

	$atts          = shortcode_atts(
		array(
			'distrib' => 5,
		), $atts
	);
	$next_distribs = AmapressDistribution::getUserNextDistributions( null, null, intval( $atts['distrib'] ) );
	$next_distrib  = ! empty( $next_distribs ) ? $next_distribs[0] : null;

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
		case 'next-distrib-deliv':
			$content = '<ul>';
			foreach ( $next_distribs as $dist ) {
				/** @var AmapressDistribution $dist */

				$adhesions = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck( null, null, $dist->getDate() );
				$content   .= '<li>' . Amapress::makeLink( $dist->getPermalink(), $dist->getTitle(), true, true ) .
				              ' : ' .
				              implode( ', ', array_map(
					              function ( $adhesion ) {
						              /** @var AmapressAdhesion $adhesion */
						              return $adhesion->getContrat_instance()->getModel()->getTitle();
					              }, array_filter( $adhesions, function ( $adhesion ) use ( $dist ) {
						              /** @var AmapressAdhesion $adhesion */
						              return ! empty( $adhesion->getContrat_quantites( $dist->getDate() ) );
					              } )
				              ) ) . '</li>';
			}
			$content .= '</ul>';
			break;
	}

	return $content;
}
//});