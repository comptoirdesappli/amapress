<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_do_query_action_distribution_inscr_resp', 'amapress_do_query_action_distribution_inscr_resp' );
function amapress_do_query_action_distribution_inscr_resp() {
	$amap_event = AmapressDistribution::getBy( get_the_ID() );
	$res        = $amap_event->inscrireResponsable( amapress_current_user_id() );
	if ( 'list_full' == $res ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'list_full' ), $amap_event->getPermalink() ) );
	} else if ( 'already_taken' == $res ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'already_taken' ), $amap_event->getPermalink() ) );
	} else if ( 'already_in_list' == $res ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'already_in_list' ), $amap_event->getPermalink() ) );
	} else {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'inscr_success' ), $amap_event->getPermalink() ) );
	}
}

add_action( 'amapress_do_query_action_distribution_desinscr_resp', 'amapress_do_query_action_distribution_desinscr_resp' );
function amapress_do_query_action_distribution_desinscr_resp() {
	$amap_event = AmapressDistribution::getBy( get_the_ID() );
	$res        = $amap_event->desinscrireResponsable( amapress_current_user_id() );
	if ( 'not_inscr' == $res ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'not_inscr' ), $amap_event->getPermalink() ) );
	} else {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'desinscr_success' ), $amap_event->getPermalink() ) );
	}
}

add_filter( 'amapress_get_custom_title_distribution_liste-emargement', 'amapress_get_custom_title_distribution_liste_emargement' );
function amapress_get_custom_title_distribution_liste_emargement( $content ) {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
	}

	$dist                = AmapressDistribution::getBy( get_the_ID() );
	$amapress_contrat    = get_query_var( 'amapress_contrat' );
	$amapress_contrat_qt = get_query_var( 'amapress_contrat_qt' );

	if ( ! empty( $amapress_contrat ) ) {
		$contrat = get_post( Amapress::resolve_post_id( $amapress_contrat, AmapressContrat::INTERNAL_POST_TYPE ) );
		if ( $contrat ) {
			$contrat_names = array( $contrat->post_title );
		} else {
			$contrat_names = array();
		}
	} else {
		$contrat_names = array_map( function ( $c ) {
			/** @var AmapressContrat_instance $c */
			return $c->getTitle();
		}, $dist->getContrats() );
	}

	$content = sprintf( __( 'Liste d\'émargement de %s du %s', 'amapress' ), $dist->getLieu()->getTitle(), date_i18n( 'd/m/Y', $dist->getDate() ), implode( ', ', $contrat_names ) );

	if ( ! empty( $amapress_contrat_qt ) ) {
		$contrat_qt = get_post( Amapress::resolve_post_id( $amapress_contrat_qt, AmapressContrat_quantite::INTERNAL_POST_TYPE ) );
		$content    .= ' - ' . $contrat_qt->post_title;
	}

	return $content;
}

add_filter( 'amapress_get_query_action_template_distribution_liste-emargement', 'amapress_get_query_action_template_distribution_liste_emargement' );
function amapress_get_query_action_template_distribution_liste_emargement( $template ) {
	$name            = 'liste-emargement.php';
	$exists_in_theme = locate_template( $name, false );
	if ( $exists_in_theme == '' ) {
		$file = AMAPRESS__PLUGIN_DIR . "templates/$name";
		if ( file_exists( $file ) ) {
			return $file;
		}
	} else {
		return $exists_in_theme;
	}

	return $template;
}

add_filter( 'edit_post_link', function ( $content ) {
	if ( 'liste-emargement' == get_query_var( 'amp_action' ) ) {
		return '';
	}

	return $content;
} );

add_filter( 'amapress_get_custom_content_distribution_liste-emargement', 'amapress_get_custom_content_distribution_liste_emargement' );
function amapress_get_custom_content_distribution_liste_emargement( $content ) {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
	}

	$dist = AmapressDistribution::getBy( get_the_ID() );
	if ( ! AmapressDistributions::isCurrentUserResponsable( $dist->ID )
	     && ! amapress_can_access_admin()
	) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}

	return getListeEmargement( get_the_ID(), isset( $_GET['all'] ) );
}

function getListeEmargement( $dist_id, $show_all_contrats, $for_pdf = false ) {
	$dist = AmapressDistribution::getBy( $dist_id );

	add_filter( 'autoptimize_filter_imgopt_should_lazyload', function ( $should_do ) {
		return false;
	} );

	if ( isset( $_GET['multi_liste'] ) ) {
		$months = isset( $_GET['months'] ) ? intval( $_GET['months'] ) : 1;
		if ( $months < 1 ) {
			$months = 1;
		}

		return getMultiListeEmargement(
			$dist->getDate(),
			Amapress::add_a_month( $dist->getDate(), $months ),
			$dist->getLieu()
		);
	}

	$date             = $dist->getDate();
	$dist_contrat_ids = $dist->getContratIds();
	$active_contrats  = AmapressContrats::get_active_contrat_instances( null, $date, false, false );
	foreach ( $dist->getDelayedToThisContrats() as $c ) {
		$found = false;
		foreach ( $active_contrats as $cc ) {
			if ( $c && $c->ID == $cc->ID ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$active_contrats[] = $c;
		}
	}
	$active_contrats_ids      = array_map(
		function ( $c ) {
			return $c->ID;
		}, $active_contrats
	);
	$all_contrat_instances    = ! $show_all_contrats ?
		$dist->getContrats() :
		$active_contrats;
	$all_contrat_instances    = array_filter( $all_contrat_instances,
		function ( $c ) use ( $active_contrats_ids ) {
			return in_array( $c->ID, $active_contrats_ids );
		} );
	$all_contrat_instance_ids = array_map(
		function ( $c ) {
			return $c->ID;
		}, $all_contrat_instances
	);
	$dist_lieu_id             = $dist->getLieuId();
	$dist_slots_conf          = $dist->getSlotsConf();
	$dist_slots_options       = [];
	foreach ( $dist_slots_conf as $k => $conf ) {
		if ( $conf['max'] <= 0 ) {
			$dist_slots_options[ $k ] = $conf['display'];
		} else {
			$dist_slots_options[ $k ] = sprintf( __( '%s (%d/%d)', 'amapress' ), $conf['display'], intval( $conf['current'] ), intval( $conf['max'] ) );
		}
	}
	$show_address = Amapress::getOption( 'liste-emargement-show-address' );
	if ( isset( $_GET['show_address'] ) ) {
		$show_address = Amapress::toBool( $_GET['show_address'] );
	}
	$show_emails = Amapress::getOption( 'liste-emargement-show-mail' );
	if ( isset( $_GET['show_email'] ) ) {
		$show_emails = Amapress::toBool( $_GET['show_email'] );
	}
	$show_phone = Amapress::getOption( 'liste-emargement-show-phone' );
	if ( isset( $_GET['show_phone'] ) ) {
		$show_phone = Amapress::toBool( $_GET['show_phone'] );
	}
	$show_comment = Amapress::getOption( 'liste-emargement-show-comment', true );
	if ( isset( $_GET['show_comment'] ) ) {
		$show_comment = Amapress::toBool( $_GET['show_comment'] );
	}

	$columns = array(
//		array(
//			'title' => __('Passage', 'amapress'),
//			'data'  => 'check',
//		),
		array(
			'title' => __( 'Nom', 'amapress' ),
			'data'  => array(
				'_'    => 'last_name',
				'sort' => 'last_name',
			)
		),
		array(
			'title' => __( 'Prénom', 'amapress' ),
			'data'  => array(
				'_'    => 'first_name',
				'sort' => 'first_name',
			)
		),

	);
	if ( ! empty( $dist_slots_conf ) ) {
		$columns[] = array(
			'title' => __( 'Créneau', 'amapress' ),
			'data'  => array(
				'_'    => 'slot',
				'sort' => 'slot_sort',
			)
		);
	}
	if ( $show_address ) {
		$columns[] = array(
			'title' => __( 'Adresse', 'amapress' ),
			'data'  => array(
				'_'    => 'adresse_full',
				'sort' => 'adresse_ville',
			)
		);
	}
	if ( $show_emails ) {
		$columns[] = array(
			'title' => __( 'Email', 'amapress' ),
			'data'  => array(
				'_'    => 'email',
				'sort' => 'email',
			)
		);
	}
	if ( $show_phone ) {
		$columns[] = array(
			'title' => __( 'Téléphone', 'amapress' ),
			'data'  => array(
				'_'    => 'tel',
				'sort' => 'tel',
			)
		);
	}

	foreach ( $all_contrat_instances as $contrat ) {
		$tit = '<span class="smart-word-break">' . esc_html( $contrat->getModelTitle() ) . '</span>';
		if ( ! in_array( $contrat->ID, $dist_contrat_ids ) ) {
			$tit = '<span class="not-this-dist">' . $tit . '</span>';
		}
		$columns[] = array(
			'title' => $tit,
			'data'  => array(
				'_'    => 'contrat_' . $contrat->ID,
				'sort' => 'contrat_' . $contrat->ID,
			)
		);
	}

	if ( $show_comment ) {
		$columns[] = array(
			'title' => __( 'Commentaire', 'amapress' ),
			'data'  => 'comment',
		);
	}

	$allow_partial_coadh = Amapress::hasPartialCoAdhesion();
	$all_adhs            = AmapressContrats::get_active_adhesions( $all_contrat_instance_ids,
		null, $dist_lieu_id, $date, true, false );
	$liste               = array();
	$adhesions           = array_group_by(
		$all_adhs,
		function ( $adh ) use ( $date, $allow_partial_coadh ) {
			/** @var AmapressAdhesion $adh */
			if ( ! $adh->getAdherentId() ) {
				return '';
			}
			$user = $adh->getAdherent()->getUser();
			if ( $allow_partial_coadh ) {
				$user_ids = array_unique( AmapressContrats::get_related_users( $user->ID, false, $date, $adh->getContrat_instanceId() ) );
			} else {
				$user_ids = array_unique( AmapressContrats::get_related_users( $user->ID, false, $date ) );
			}

			return implode( '_', $user_ids );
		} );

	$query                        = array(//        'status' => 'to_exchange',
	);
	$query['contrat_instance_id'] = $dist->getContratIds();
	$query['lieu_id']             = $dist_lieu_id;
	$query['date']                = $date;
	$paniers                      = AmapressPaniers::getPanierIntermittents( $query );

	$users_ids_paniers_intermittents = array_map( function ( $p ) {
		/** @var AmapressIntermittence_panier $p */
		return $p->getAdherent()->ID;
	}, $paniers );
	/** @var AmapressAdhesion[] $adhs */
	foreach ( $adhesions as $user_ids => $adhs ) {
		$line = array();

		$user_ids            = explode( '_', $user_ids );
		$panier_intermittent = '';
		foreach ( $user_ids as $user_id ) {
			if ( in_array( $user_id, $users_ids_paniers_intermittents ) ) {
				$panier_intermittent = '*';
			}
		}
		$users = array_map( function ( $user_id ) {
			return amapress_get_user_by_id_or_archived( intval( $user_id ) );
		}, $user_ids );

		if ( ! empty( $dist_slots_conf ) ) {
			$slots     = [];
			$slot_sort = Amapress::end_of_week( $dist->getDate() );
			foreach ( $user_ids as $user_id ) {
				$slot = $dist->getSlotInfoForUser( $user_id );
				if ( $slot ) {
					$slots[ strval( $slot['date'] ) ] = $slot;
					if ( $slot['date'] < $slot_sort ) {
						$slot_sort = $slot['date'];
					}
				}
			}
			$line['slot_sort'] = date_i18n( 'Y-m-d-H-i', $slot_sort );
			$slot_display      = implode( ',', array_map( function ( $s ) {
				return $s['display'];
			}, $slots ) );
			if ( amapress_can_access_admin() && ! $for_pdf && ! isset( $_GET['for_export'] ) ) {
				$affect_slot_id = 'affect-slot-' . $user_ids[0];
				$affect_slot    = "<select id='$affect_slot_id'>";
				$affect_slot    .= tf_parse_select_options( $dist_slots_options, $slot_sort, false );
				$affect_slot    .= '</select>';
				$affect_slot    .= '<button  type="button" class="btn btn-default amapress-ajax-button" 
					data-action="distrib_inscrire_slot" data-confirm="' . esc_attr__( 'Etes-vous sûr d\'attribuer ce créneau à cet amapien ?', 'amapress' ) . '"
					data-dist="' . $dist_id . '" data-slot="val:#' . $affect_slot_id . '" data-user="' . $user_ids[0] . '">' . __( 'Attribuer', 'amapress' ) . '</button>';

				$slot_display .= '<br/>' . $affect_slot;
			}
			$line['slot'] = $slot_display;
		}

		$line['first_name'] = implode( ' / ', array_map( function ( $user ) {
				return $user->first_name;
			}, $users ) ) . $panier_intermittent;
		$line['last_name']  = implode( ' / ', array_map( function ( $user ) use ( $for_pdf ) {
			$val = ! empty( $user->last_name ) ? $user->last_name : $user->display_name;
			if ( ! $for_pdf && current_user_can( 'edit_users' ) ) {
				$val = Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $user->ID ), $val, true, true );
			}
			$adh = AmapressUser::getBy( $user );
			if ( ! empty( $adh->getAdditionalCoAdherents() ) ) {
				$val .= ' / ' . esc_html( $adh->getAdditionalCoAdherents() );
			}

			return $val;
		}, $users ) );

		if ( $for_pdf ) {
			$line['last_name'] = '<span style="font-family: zapfdingbats">q</span>&nbsp;' . $line['last_name'];
		}

		if ( $show_phone ) {
			$phones = array_unique( array_map( function ( $user ) use ( $for_pdf ) {
				$adh = AmapressUser::getBy( $user );

				return $adh->getTelTo( true, false, $for_pdf ) . ( ! empty( $adh->getAdditionalCoAdherentsInfos() ) ? ' / ' . esc_html( $adh->getAdditionalCoAdherentsInfos() ) : '' );
			}, $users ) );
			$phones = array_filter( $phones, function ( $s ) {
				return ! empty( trim( $s ) );
			} );
			if ( $for_pdf && ! empty( $phones ) ) {
				$phones = [ array_shift( $phones ) ];
			}
			$line['tel'] = implode( ' / ', $phones );
		}
		if ( $show_emails ) {
			$line['email'] = implode( '<br/>', array_map( function ( $user ) {
				$adh = AmapressUser::getBy( $user );

				return implode( ',', $adh->getAllEmails() );
			}, $users ) );
		}
		if ( $show_address ) {
			$line['adresse_full'] = implode( '<br/>', array_map( function ( $user ) {
				$adh = AmapressUser::getBy( $user );

				return $adh->getAdresse();
			}, $users ) );

			$line['adresse_ville'] = implode( '<br/>', array_map( function ( $user ) {
				$adh = AmapressUser::getBy( $user );

				return $adh->getVille();
			}, $users ) );
		}
		$to_confirm = false;
		foreach ( $adhs as $adh ) {
			if ( ! isset( $line[ 'contrat_' . $adh->getContrat_instance()->ID ] ) ) {
				$line[ 'contrat_' . $adh->getContrat_instance()->ID ] = '';
			}
			if ( ! empty( $line[ 'contrat_' . $adh->getContrat_instance()->ID ] ) ) {
				$line[ 'contrat_' . $adh->getContrat_instance()->ID ] .= ',';
			}
			$panier_date                                          = ! $show_all_contrats ? $dist->getRealDateForContrat( $adh->getContrat_instanceId() ) : null;
			$line[ 'contrat_' . $adh->getContrat_instance()->ID ] .= $adh->getContrat_quantites_Codes_AsString( $panier_date );
			if ( AmapressAdhesion::TO_CONFIRM == $adh->getStatus() ) {
				$to_confirm = true;
			}
		}
		foreach ( $all_contrat_instances as $contrat ) {
			if ( ! isset( $line[ 'contrat_' . $contrat->ID ] ) ) {
				$line[ 'contrat_' . $contrat->ID ] = '';
			} else {
				if ( ! in_array( $contrat->ID, $dist_contrat_ids ) ) {
					$line[ 'contrat_' . $contrat->ID ] = '<span class="not-this-dist">' . esc_html( $line[ 'contrat_' . $contrat->ID ] ) . '</span>';
				}
			}
			if ( $to_confirm ) {
				$line[ 'contrat_' . $contrat->ID ] = '<em>' . $line[ 'contrat_' . $contrat->ID ] . '</em>';
			}
		}

		$principal_user = AmapressUser::getBy( $users[0] );
		$line['check']  = '<span style="display: inline-block; width: 32px">&#xA0;</span>';


		if ( $show_comment ) {
			if ( $for_pdf ) {
				$line['comment'] = esc_html( $principal_user->getCommentEmargement() );
			} else {
				$comment = esc_html( $principal_user->getCommentEmargement() );
				if ( empty( $comment ) ) {
					$comment = '<span class="edit-user-comment">' . __( 'Editer', 'amapress' ) . '</span>';
				}
				$line['comment'] = Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $principal_user->ID . '#amapress_user_comment_emargement' ), $comment, false );//;
			}
		}

		$liste[] = $line;
	}

	usort( $liste, function ( $a, $b ) use ( $dist_slots_conf ) {
		if ( ! empty( $dist_slots_conf ) ) {
			if ( $a['slot_sort'] < $b['slot_sort'] ) {
				return - 1;
			} elseif ( $a['slot_sort'] > $b['slot_sort'] ) {
				return 1;
			}
		}

		return strcasecmp( wp_strip_all_tags( $a['last_name'] ), wp_strip_all_tags( $b['last_name'] ) );
	} );

	ob_start();
	if ( $for_pdf ) {
		echo '<style type="text/css">
a {
	color: black;
	text-decoration: none;
}
table, td, th { 
	border: 1px solid black; 
	border-collapse: collapse; 
	padding: 2px; 
}
.odd { 
	background-color: #EEEEEE; 
}
.distrib-resp-missing {
	display: none;
}
.distrib-inscr-closed {
	display: none;
}
.distrib-not-part-of {
	display: none;
}
.inscr-list-contrats {
	font-size: 0.6em;
}
.not-this-dist { background-color: #AAA; }
h2, h3, h4 {
font-size: 10pt;
padding: 0;
margin: 0;
line-height: 1.1;
}
</style>';
	} else {
		echo '<style type="text/css">
            p {
                margin: 0 !important;
                padding:0 !important;
            }
            body { margin: 15px; }
            .edit-user-comment { color: white; }
            .edit-user-comment:hover { color: black; !important; }
            .not-this-dist { background-color: #AAA; }
            @media print {
            	.user-sms { display: none; }
            	.edit-user-comment {display: none;}
                * { margin: 0 !important; 
                	padding: 0 !important; 
                	color:black !important;
                	font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt !important; }
                #liste-emargement a.contrat { box-shadow: none !important; text-decoration: none !important; color: #000000!important; border: none !important;}
                #paniers-a-echanger a { box-shadow: none !important; text-decoration: none !important; color: #000000!important; border: none !important;}
                a:after {
                    content: \'\' !important;
                }
                td, th {white-space:normal !important; word-wrap: break-word !important;}
                .user-photo {display: none; }
                .liste-emargement-contrat-variable, .liste-emargement-instructions { page-break-before: always; }
                #liste-emargement_filter { display: none !important}
                #paniers-a-echanger_filter { display: none !important}
                #liste-emargement_info { display: none !important}
                #paniers-a-echanger_info { display: none !important}
                .distrib-resp-missing, .dist-inscrire-button, .dist-desinscrire-button, .btn-print-liste { display: none !important}
                div.btns { display: none !important}
                td, th { padding: 2px !important; line-height: normal !important; }
                body {
                    background-color:#FFFFFF !important;
                    border: none !important;
                    margin: 0 !important;  /* the margin on the content before printing */
                }
                @page { margin: 0 !important; }
            }
            </style>';
	}

	echo '<h2>' . esc_html( $dist->getTitle() ) . '</h2>';
	if ( ! $for_pdf ) {
		echo '<br/>';
		$pdf_url = $dist->getPermalink( 'liste-emargement/pdf/' );
		if ( isset( $_GET['all'] ) ) {
			$pdf_url = add_query_arg( 'all', '', $pdf_url );
		}
		echo '<div class="btns">';
		if ( isset( $_GET['all'] ) ) {
			echo '<a href="' . esc_attr( remove_query_arg( 'all' ) ) . '" class="btn btn-default btn-print">' . __( 'Uniquement contrats à cette distribution', 'amapress' ) . '</a>';
		} else {
			echo '<a href="' . esc_attr( add_query_arg( 'all', '' ) ) . '" class="btn btn-default btn-print">' . __( 'Afficher tous les contrats', 'amapress' ) . '</a>';
		}
		if ( ! empty( $dist_slots_conf ) ) {
			if ( ! isset( $_GET['for_export'] ) ) {
				echo '<a href="' . esc_attr( add_query_arg( 'for_export', '' ) ) . '" class="btn btn-default btn-print">' . __( 'Vue pour export XLSX si créneaux de distribution', 'amapress' ) . '</a>';
			} else {
				echo '<a href="' . esc_attr( remove_query_arg( 'for_export' ) ) . '" class="btn btn-default btn-print">' . __( 'Revenir à la vue normale', 'amapress' ) . '</a>';
			}
		}
		echo '<a href="' . esc_attr( $pdf_url ) . '" class="btn btn-default btn-print">' . __( 'Imprimer en PDF', 'amapress' ) . '</a>';
		echo '<a href="' . esc_attr( $dist->getPermalink() ) . '" class="btn btn-default">' . __( 'Revenir à la distribution', 'amapress' ) . '</a>';
		echo '<a href="' . esc_attr( add_query_arg( 'multi_liste', 'T' ) ) . '" class="btn btn-default">' . __( 'Passer au mode multi-dates', 'amapress' ) . '</a>';

		echo '<br/>';
		if ( current_user_can( 'edit_distribution' ) ) {
			echo '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '#amapress_distribution_nb_resp_supp" class="btn btn-default">' . __( 'Ajouter des responsables supplémentaires', 'amapress' ) . '</a>';
			echo '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '" class="btn btn-default">' . __( 'Editer la distribution (infos, horaires, créneaux...)', 'amapress' ) . '</a>';
			echo '<br/>';
			echo '<a href="' . esc_attr( admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_emargement_options_tab' ) ) . '" class="btn btn-default">' . __( 'Editer les paramètres de la liste de distribution', 'amapress' ) . '</a>';
		}
		$mailto = $dist->getMailtoResponsables();
		if ( ! empty( $mailto ) ) {
			echo '<a href="' . $mailto . '" class="btn btn-default">' . __( 'Email aux responsables', 'amapress' ) . '</a>';
		}
		echo '<a target="_blank" href="' . admin_url( 'admin.php?page=amapress_messages_page' ) . '" class="btn btn-default">' . __( 'Email aux amapiens', 'amapress' ) . '</a>';
		if ( current_user_can( 'edit_lieu_distribution' ) ) {
			echo '<a href="' . esc_attr( $dist->getLieu()->getAdminEditLink() ) . '" class="btn btn-default">' . __( 'Editer les infos du lieu', 'amapress' ) . '</a>';
		}
		echo '</div>';
		echo '<br/>';
	}

	$general_message = stripslashes( Amapress::getOption( 'liste-emargement-general-message' ) );
	if ( strlen( wp_strip_all_tags( $general_message ) ) > 0 ) {
		echo $general_message;
		echo '<br/>';
	}
	$dist_infos = $dist->getInformations();
	if ( strlen( wp_strip_all_tags( $dist_infos ) ) > 0 ) {
		echo $dist_infos;
		echo '<br/>';
	}

//    amapress_display_messages_for_post('dist-messages', $dist->ID);

	$show_liste = ! Amapress::getOption( 'liste-emargement-disable-liste' );
	if ( $show_liste ) {
		if ( ! $for_pdf ) {
			echo '<h3 class="liste-emargement">' . __( 'Liste', 'amapress' ) . '</h3>';
		}
		amapress_echo_datatable( 'liste-emargement', $columns, $liste,
			array(
				'paging'       => false,
				'searching'    => false,
				'nowrap'       => false,
				'responsive'   => false,
				'init_as_html' => true,
				'no_script'    => $for_pdf,
				'aaSorting'    => [ [ 0, 'asc' ] ]
			),
			( empty( $dist_slots_conf ) || isset( $_GET['for_export'] ) ) ?
				array(
					Amapress::DATATABLES_EXPORT_EXCEL
				) : array()
		);
	}

	$had_paniers_variables = false;
	foreach ( $dist->getContrats() as $contrat ) {
		if ( $contrat->isPanierVariable() ) {
			$panier_date      = ! $show_all_contrats ? $dist->getRealDateForContrat( $contrat->ID ) : null;
			$panier_commandes = AmapressPaniers::getPanierVariableCommandes( $contrat->ID, $panier_date );

			if ( ! empty( $panier_commandes['data'] ) ) {
//				if ( ! $show_liste && $had_paniers_variables ) {
				echo '<br pagebreak="true"/>';
//				}
				$had_paniers_variables = true;
				echo '<h3 class="liste-emargement-contrat-variable">';
				echo sprintf( __( 'Détails des paniers - %s - Distribution du %s', 'amapress' ), esc_html( $contrat->getTitle() ), date_i18n( 'd/m/Y', $dist->getDate() ) );
				echo '</h3>';

				amapress_echo_datatable( 'liste-emargement-contrat-variable-' . $contrat->ID,
					$panier_commandes['columns'], $panier_commandes['data'],
					array(
						'paging'       => false,
						'searching'    => false,
						'nowrap'       => false,
						'responsive'   => false,
						'init_as_html' => true,
						'no_script'    => $for_pdf,
					),
					array(
						Amapress::DATATABLES_EXPORT_EXCEL,
						Amapress::DATATABLES_EXPORT_PRINT
					) );
				echo '<p>';
				echo sprintf( __( 'En tout: %s adhérent(s) ; ', 'amapress' ), $panier_commandes['adhs'] );
				echo esc_html( $panier_commandes['resume'] ) . '</p>';
			}
		}
	}

	$from_date = Amapress::start_of_day( $date );
	if ( ! $for_pdf ) {
		echo '<br/>';
	} elseif ( $had_paniers_variables ) {
		echo '<br pagebreak="true"/>';
	}
	echo '<h3 class="liste-emargement-next-resps">';
	echo esc_html( sprintf( __( 'Responsables aux prochaines distributions - %s', 'amapress' ), $dist->getLieu()->getTitle() ) );
	echo '</h3>';
//	echo '<br/>';
	echo do_shortcode( '[inscription-distrib show_title=false for_emargement=true for_pdf=' . $for_pdf . ' show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=' . ( $for_pdf ? 0 : 1 ) . ' max_dates=' . Amapress::getOption( 'liste-emargement-next-resp-count', 8 ) . ' date=' . $from_date . ' lieu=' . $dist_lieu_id . ']' );

	$lieux_ids = Amapress::get_lieu_ids();
	if ( count( $lieux_ids ) > 1 ) {
		if ( ! $for_pdf ) {
			echo '<br/>';
		}
		foreach ( $lieux_ids as $lieu_id ) {
			if ( $lieu_id == $dist_lieu_id ) {
				continue;
			}
			$lieu = AmapressLieu_distribution::getBy( $lieu_id );
			if ( ! $lieu ) {
				continue;
			}
			echo '<h3 class="liste-emargement-next-resps">';
			echo esc_html( sprintf( __( 'Responsables du jour - %s', 'amapress' ), $lieu->getTitle() ) );
			echo '</h3>';
//			echo '<br/>';
			echo do_shortcode( '[inscription-distrib show_title=false for_emargement=true for_pdf=' . $for_pdf . ' show_past=false show_for_resp=true max_dates=1 show_adresse=false show_avatar=' . ( $for_pdf ? 0 : 1 ) . ' show_roles=false date=' . $from_date . ' lieu=' . $lieu_id . ']' );
		}
	}

	if ( ! $for_pdf ) {
		echo '<br/>';
	}
	if ( count( $paniers ) > 0 ) {
		static $id_incr = 0;
		$id_incr += 1;
		echo '<h3 class="liste-emargement-intermittent">* Panier(s) intermittent(s)</h3>';
		echo amapress_get_paniers_intermittents_table( 'paniers-exchs' . $id_incr, $paniers,
			function ( $state, $status, $adh ) {
				return $state;
			},
			array(
				'paging'       => false,
				'searching'    => false,
				'nowrap'       => false,
				'responsive'   => false,
				'init_as_html' => true,
				'no_script'    => $for_pdf,
			),
			//TODO params for other amap
			array(
				'show_avatar'     => 'false',
				'show_email'      => 'false',
				'show_sms'        => 'false',
				'show_tel'        => 'false',
				'show_tel_fixe'   => 'false',
				'show_tel_mobile' => $show_phone,
				'show_adresse'    => 'false',
				'show_roles'      => 'false',
			),
			array(
				'date'      => false,
				'panier'    => true,
				'quantite'  => true,
				'lieu'      => false,
				'prix'      => false,
				'adherent'  => true,
				'repreneur' => true,
				'etat'      => true,
				'for_print' => true,
			) );
	}


	if ( Amapress::getOption( 'enable-gardiens-paniers' ) ) { // && ! empty( $dist->getPaniersGarde() ) ) {
		if ( ! $for_pdf ) {
			echo '<br/>';
		}
		echo '<h3>* Garde(s) de panier(s)</h3>';
		$col_gardiens  = array(
			array(
				'title' => __( 'Gardien', 'amapress' ),
				'data'  => 'gardien'
			),
			array(
				'title' => __( 'Amapien', 'amapress' ),
				'data'  => 'amapien'
			),
			array(
				'title' => __( 'Paniers', 'amapress' ),
				'data'  => 'paniers'
			),
		);
		$data_gardiens = [];
		foreach ( $dist->getGardiensIds( true ) as $gardien_id ) {
			$gardien         = AmapressUser::getBy( $gardien_id );
			$gardien_comment = $dist->getGardienComment( $gardien_id );
			if ( ! empty( $gardien_comment ) ) {
				$gardien_comment = '<br /><em>' . esc_html( $gardien_comment ) . '</em>';
			}
			$gardes_amapiens = $dist->getGardiensPaniersAmapiensIds( $gardien_id );
			foreach ( $gardes_amapiens as $amapien_id ) {
				$amapien         = AmapressUser::getBy( $amapien_id );
				$data_gardiens[] = [
					'gardien' => $gardien->getSortableDisplayName() . '(' . $gardien->getTelTo( true, false, false, ',' ) . ')'
					             . $gardien_comment,
					'amapien' => $amapien->getSortableDisplayName() . '(' . $amapien->getTelTo( true, false, false, ',' ) . ')',
					'paniers' => $dist->getPaniersDescription( $amapien_id ),
				];
				$gardien_comment = '';
			}
			if ( empty( $gardes_amapiens ) && ! empty( $gardien_comment ) ) {
				$data_gardiens[] = [
					'gardien' => $gardien->getSortableDisplayName() . '(' . $gardien->getTelTo( true, false, false, ',' ) . ')'
					             . $gardien_comment,
					'amapien' => '',
					'paniers' => '',
				];
			}
		}
		amapress_echo_datatable( 'gardes-paniers',
			$col_gardiens, $data_gardiens,
			array(
				'paging'       => false,
				'searching'    => false,
				'nowrap'       => false,
				'responsive'   => false,
				'init_as_html' => true,
				'no_script'    => $for_pdf,
			)
		);
	}

	if ( Amapress::toBool( Amapress::getOption( 'liste-emargement-show-lieu-instructions' ) ) ) {
		$lieu = ( $dist->getLieuSubstitution() ? $dist->getLieuSubstitution() : $dist->getLieu() );

		if ( strlen( trim( strip_tags( $lieu->getInstructions_privee() ) ) ) > 0 ) {
			echo '<br pagebreak="true"/>';
			echo '<h3 class="liste-emargement-instructions">';
			echo esc_html( sprintf( __( 'Instructions pour %s', 'amapress' ), $lieu->getShortName() ) );
			echo '</h3>';
			echo $lieu->getInstructions_privee();
			echo '<br/>';
			echo '<h4 class="liste-emargement-contact">' . __( 'Contacts', 'amapress' ) . '</h4>';
			echo $lieu->getContact_externe();
		}
	}

	if ( Amapress::toBool( Amapress::getOption( 'liste-emargement-show-dist-instructions' ) ) ) {
		$paniers_instructions_distribution = $dist->getProperty( 'paniers_instructions_distribution' );
		if ( ! empty( $paniers_instructions_distribution ) ) {
			echo '<br pagebreak="true"/>';
			echo '<h3 class="liste-emargement-instructions">';
			echo esc_html( __( 'Instructions de distribution', 'amapress' ) );
			echo '</h3>';
			echo $paniers_instructions_distribution;
		}
	}

	$content = ob_get_contents();
	ob_clean();
	//|amapress_adhesion_adherent,amapress_adhesion_co-adherents|amapress_post=$dist_id|amapress_distribution_date", "Les amapiens inscrit à {$distrib->post_title}", "distribution");

	//$cnt[$lieu_id] -= 1;

	if ( $for_pdf ) {
		$content = preg_replace( '/\<div[^\>]*\>/', '', $content );
		$content = preg_replace( '#(\</div\>)+#', '<br/>', $content );
		$content = preg_replace( '#\<br\/\>\<\/td\>#', '</td>', $content );
	}

	return $content;
}

//add_action('amapress_get_query_action_template_distribution_liste-emargement','amapress_get_query_action_template_distribution_liste_emargement');
//function amapress_get_query_action_template_distribution_liste_emargement($template) {
//    return AMAPRESS__PLUGIN_DIR . 'templates/blank_page.php';
//}

add_action( 'amapress_do_query_action_distribution_liste-emargement-pdf', function () {
	$dist = AmapressDistribution::getBy( get_the_ID() );
	if ( ! AmapressDistributions::isCurrentUserResponsable( $dist->ID )
	     && ! amapress_can_access_admin()
	) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}
	Amapress::sendPdfFromHtml(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, isset( $_GET['all'] ), true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.pdf' ) ) );
	die();
} );
add_action( 'amapress_do_query_action_distribution_liste-emargement-excel', function () {
	$dist = AmapressDistribution::getBy( get_the_ID() );
	if ( ! AmapressDistributions::isCurrentUserResponsable( $dist->ID )
	     && ! amapress_can_access_admin()
	) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}
	Amapress::sendXLSXFromHtml(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, isset( $_GET['all'] ), true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.xlsx' ) ),
		sprintf( __( 'Liste d\'émargement - %s', 'amapress' ), $dist->getTitle() ) );
	die();
} );


add_action( 'wp_ajax_desinscrire_garde', function () {
	$dist_id    = intval( $_POST['dist'] );
	$gardien_id = intval( $_POST['gardien'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsable( $dist_id ) || amapress_can_access_admin() ) ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}


	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $dist->faireGarder( $user_id, $gardien_id, false, false,
		isset( $_REQUEST['not_member'] ) ? Amapress::toBool( $_REQUEST['not_member'] ) : false ) ) {
		case 'not_inscr':
			echo '<p class="error">' . __( 'Non gardé', 'amapress' ) . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . __( 'Désaffectation du gardien de paniers prise en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );
add_action( 'wp_ajax_inscrire_garde', function () {
	$dist_id    = intval( $_POST['dist'] );
	$gardien_id = intval( $_POST['gardien'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = amapress_current_user_id() == $user_id;
	if ( ! $is_current && ! ( AmapressDistributions::isCurrentUserResponsable( $dist_id )
	                          || amapress_can_access_admin()
	                          || AmapressDistributions::isCurrentUserResponsableThisWeek()
	                          || AmapressDistributions::isCurrentUserResponsableNextWeek()
		) ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}

	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $dist->faireGarder( $user_id, $gardien_id, true, false,
		isset( $_REQUEST['not_member'] ) ? Amapress::toBool( $_REQUEST['not_member'] ) : false ) ) {
		case 'already_in_list':
			echo '<p class="error">' . __( 'Panier(s) déjà gardé(s)', 'amapress' ) . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . __( 'Garde de panier(s) prise en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );

add_action( 'wp_ajax_distrib_desinscrire_slot', function () {
	$dist_id    = intval( $_POST['dist'] );
	$slot       = strval( $_POST['slot'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = ( amapress_current_user_id() == $user_id );
	if ( ! $is_current && ! ( ! AmapressDistributions::isCurrentUserResponsable( $dist_id ) || amapress_can_access_admin() ) ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}


	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $dist->manageSlot( $user_id, $slot, false ) ) {
		case 'not_inscr':
			echo '<p class="error">' . __( 'Vous n\'aviez pas choisi de créneau', 'amapress' ) . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . __( 'Désaffectation du créneau prise en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );
add_action( 'wp_ajax_distrib_inscrire_slot', function () {
	$dist_id    = intval( $_POST['dist'] );
	$slot       = strval( $_POST['slot'] );
	$user_id    = ! empty( $_POST['user'] ) ? intval( $_POST['user'] ) : amapress_current_user_id();
	$is_current = amapress_current_user_id() == $user_id;
	if ( ! $is_current && ! ( AmapressDistributions::isCurrentUserResponsable( $dist_id )
	                          || amapress_can_access_admin()
	                          || AmapressDistributions::isCurrentUserResponsableThisWeek()
	                          || AmapressDistributions::isCurrentUserResponsableNextWeek()
		) ) {
		echo '<p class="error">' . __( 'Non autorisé', 'amapress' ) . '</p>';
		die();
	}

	$dist = AmapressDistribution::getBy( $dist_id );
	switch ( $dist->manageSlot( $user_id, $slot, true ) ) {
		case 'already_in_list':
			echo '<p class="error">' . __( 'Vous avez déjà choisi un créneau', 'amapress' ) . '</p>';
			break;
		case 'full':
			echo '<p class="error">' . __( 'Ce créneau est complet', 'amapress' ) . '</p>';
			break;
		case 'ok':
			echo '<p class="success">' . __( 'Choix du créneau pris en compte', 'amapress' ) . '</p>';
			break;
	}
	die();
} );

function getMultiListeEmargement( $start_date, $end_date, $lieu ) {
	$dists = AmapressDistribution::get_distributions( $start_date, $end_date, 'ASC' );
	$dists = array_filter( $dists, function ( $d ) use ( $lieu ) {
		return $d->getLieuId() == $lieu->ID;
	} );

	$all_adhs = [];
	foreach ( $dists as $dist ) {
		foreach ( AmapressContrats::get_active_adhesions( null, null, $dist->getLieuId(), $dist->getDate() ) as $adh ) {
			$all_adhs[ $adh->ID ] = $adh;
		}
	}

	$show_address = Amapress::getOption( 'liste-emargement-show-address' );
	if ( isset( $_GET['show_address'] ) ) {
		$show_address = Amapress::toBool( $_GET['show_address'] );
	}
	$show_emails = Amapress::getOption( 'liste-emargement-show-mail' );
	if ( isset( $_GET['show_email'] ) ) {
		$show_emails = Amapress::toBool( $_GET['show_email'] );
	}
	$show_phone = Amapress::getOption( 'liste-emargement-show-phone' );
	if ( isset( $_GET['show_phone'] ) ) {
		$show_phone = Amapress::toBool( $_GET['show_phone'] );
	}

	$merge   = [ 0, 1 ];
	$columns = array(
		array(
			'title' => __( 'Nom', 'amapress' ),
			'data'  => array(
				'_'    => 'last_name',
				'sort' => 'last_name',
			)
		),
		array(
			'title' => __( 'Prénom', 'amapress' ),
			'data'  => array(
				'_'    => 'first_name',
				'sort' => 'first_name',
			)
		),

	);
	if ( $show_address ) {
		$columns[] = array(
			'title' => __( 'Adresse', 'amapress' ),
			'data'  => array(
				'_'    => 'adresse_full',
				'sort' => 'adresse_ville',
			)
		);
		$merge[]   = count( $merge );
	}
	if ( $show_emails ) {
		$columns[] = array(
			'title' => __( 'Email', 'amapress' ),
			'data'  => array(
				'_'    => 'email',
				'sort' => 'email',
			)
		);
		$merge[]   = count( $merge );
	}
	if ( $show_phone ) {
		$columns[] = array(
			'title' => __( 'Téléphone', 'amapress' ),
			'data'  => array(
				'_'    => 'tel',
				'sort' => 'tel',
			)
		);
		$merge[]   = count( $merge );
	}
	$columns[] = array(
		'title' => __( 'Contrat', 'amapress' ),
		'data'  => array(
			'_'    => 'contrat',
			'sort' => 'contrat',
		)
	);
	foreach ( $dists as $dist ) {
		$columns[] = array(
			'title' => date_i18n( 'd/m/Y', $dist->getDate() ),
			'data'  => 'dist_' . $dist->getDate(),
		);
	}

	$allow_partial_coadh = Amapress::hasPartialCoAdhesion();
	$adhesions           = array_group_by(
		array_values( $all_adhs ),
		function ( $adh ) use ( $allow_partial_coadh, $start_date ) {
			/** @var AmapressAdhesion $adh */
			if ( ! $adh->getAdherentId() ) {
				return '';
			}
			$user = $adh->getAdherent()->getUser();
			if ( $allow_partial_coadh ) {
				$user_ids = array_unique( AmapressContrats::get_related_users( $user->ID, false, $start_date, $adh->getContrat_instanceId() ) );
			} else {
				$user_ids = array_unique( AmapressContrats::get_related_users( $user->ID, false, $start_date ) );
			}

			return implode( '_', $user_ids );
		} );

	$data = [];
	/** @var AmapressAdhesion[] $adhs */
	foreach ( $adhesions as $user_ids => $adhs ) {
		$user_ids = explode( '_', $user_ids );
		$users    = array_map( function ( $user_id ) {
			return amapress_get_user_by_id_or_archived( intval( $user_id ) );
		}, $user_ids );

		foreach ( $adhs as $adhesion ) {

			$line = array();

			$line['first_name'] = implode( ' / ', array_map( function ( $user ) {
				return $user->first_name;
			}, $users ) );
			$line['last_name']  = implode( ' / ', array_map( function ( $user ) {
				$val = ! empty( $user->last_name ) ? $user->last_name : $user->display_name;
				if ( current_user_can( 'edit_users' ) ) {
					$val = Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $user->ID ), $val, true, true );
				}
				$adh = AmapressUser::getBy( $user );
				if ( ! empty( $adh->getAdditionalCoAdherents() ) ) {
					$val .= ' / ' . esc_html( $adh->getAdditionalCoAdherents() );
				}

				return $val;
			}, $users ) );

			if ( $show_phone ) {
				$phones      = array_unique( array_map( function ( $user ) {
					$adh = AmapressUser::getBy( $user );

					return $adh->getTelTo( true, false ) . ( ! empty( $adh->getAdditionalCoAdherentsInfos() ) ? '<br/>' . esc_html( $adh->getAdditionalCoAdherentsInfos() ) : '' );
				}, $users ) );
				$phones      = array_filter( $phones, function ( $s ) {
					return ! empty( trim( $s ) );
				} );
				$line['tel'] = implode( '<br/>', $phones );
			}
			if ( $show_emails ) {
				$line['email'] = implode( '<br/>', array_map( function ( $user ) {
					$adh = AmapressUser::getBy( $user );

					return implode( ',', $adh->getAllEmails() );
				}, $users ) );
			}
			if ( $show_address ) {
				$line['adresse_full'] = implode( '<br/>', array_map( function ( $user ) {
					$adh = AmapressUser::getBy( $user );

					return $adh->getAdresse();
				}, $users ) );

				$line['adresse_ville'] = implode( '<br/>', array_map( function ( $user ) {
					$adh = AmapressUser::getBy( $user );

					return $adh->getVille();
				}, $users ) );
			}
			$line['contrat'] = $adhesion->getContrat_instance()->getModelTitleWithSubName();

			foreach ( $dists as $dist ) {
				$quant = $adhesion->getContrat_quantites_Codes_AsString( $dist->getDate() );
				if ( $adhesion->getContrat_instance()->isPanierVariable() && ! empty( $quant ) ) {
					$quant = 'Var.';
				}

				$line[ 'dist_' . $dist->getDate() ] = $quant;
			}

			$data[] = $line;
		}
	}

	ob_start();
	echo '<style type="text/css">
a {
	color: black !important;
	text-decoration: none;
}
table, td, th { 
	border: 1px solid black; 
	border-collapse: collapse; 
	padding: 2px; 
}
.odd { 
	background-color: #EEEEEE; 
}
@media print {
	.emarge-multi-actions {
		display: none;
	}
}
</style>';

	usort( $data, function ( $a, $b ) {
		$ret = strcmp( $a['last_name'], $b['last_name'] );
		if ( 0 == $ret ) {
			$ret = strcmp( $a['first_name'], $b['first_name'] );
		}
		if ( 0 == $ret ) {
			$ret = strcmp( $a['contrat'], $b['contrat'] );
		}

		return $ret;
	} );

	echo '<h2>' . sprintf( 'Liste d\'émargement du %s au %s - %s',
			date_i18n( 'd/m/Y', $start_date ), date_i18n( 'd/m/Y', $end_date ), $lieu->getTitle()
		) . '</h2>';

	echo '<div class="emarge-multi-actions">';
	echo Amapress::makeButtonLink( add_query_arg( 'months', '1' ), __( 'Pour un mois', 'amapress' ) );
	echo Amapress::makeButtonLink( add_query_arg( 'months', '2' ), __( 'Pour deux mois', 'amapress' ) );
	echo Amapress::makeButtonLink( add_query_arg( 'months', '3' ), __( 'Pour trois mois', 'amapress' ) );
	echo Amapress::makeButtonLink( add_query_arg( 'months', '6' ), __( 'Pour six mois', 'amapress' ) );
	echo '</div>';

	amapress_echo_datatable( 'liste-emargement', $columns, $data,
		array(
			'paging'        => false,
			'searching'     => false,
			'nowrap'        => false,
			'responsive'    => false,
			'rowsGroup'     => $merge,
			'init_as_html'  => true,
			'other-classes' => 'compact',
			'aaSorting'     => [ [ 0, 'asc' ] ]
		)
	);

	return ob_get_clean();
}