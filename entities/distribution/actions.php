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

add_action( 'amapress_do_query_action_distribution_panier_garder', 'amapress_do_query_action_distribution_panier_garder' );
function amapress_do_query_action_distribution_panier_garder() {
	//TODO
}

add_filter( 'amapress_get_custom_title_distribution_liste-emargement', 'amapress_get_custom_title_distribution_liste_emargement' );
function amapress_get_custom_title_distribution_liste_emargement( $content ) {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
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

	$content = sprintf( 'Liste d\'émargement de %s du %s', $dist->getLieu()->getTitle(), date_i18n( 'd/m/Y', $dist->getDate() ), implode( ', ', $contrat_names ) );

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
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	$dist = AmapressDistribution::getBy( get_the_ID() );
	if ( ! AmapressDistributions::isCurrentUserResponsable( $dist->ID )
	     && ! amapress_can_access_admin()
	) {
		wp_die( 'Accès non autorisé' );
	}

	return getListeEmargement( get_the_ID(), isset( $_GET['all'] ) );
}

function getListeEmargement( $dist_id, $show_all_contrats, $for_pdf = false ) {
	$dist = AmapressDistribution::getBy( $dist_id );

	$date                     = $dist->getDate();
	$dist_contrat_ids         = $dist->getContratIds();
	$all_contrat_instances    = ! $show_all_contrats ?
		$dist->getContrats() :
		AmapressContrats::get_active_contrat_instances( null, $date, false, false );
	$all_contrat_instance_ids = array_map(
		function ( $c ) {
			return $c->ID;
		}, $all_contrat_instances
	);
	$dist_lieu_id             = $dist->getLieuId();

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

	$columns = array(
//		array(
//			'title' => 'Passage',
//			'data'  => 'check',
//		),
		array(
			'title' => 'Nom',
			'data'  => array(
				'_'    => 'last_name',
				'sort' => 'last_name',
			)
		),
		array(
			'title' => 'Prénom',
			'data'  => array(
				'_'    => 'first_name',
				'sort' => 'first_name',
			)
		),

	);
	if ( $show_address ) {
		$columns[] = array(
			'title' => 'Adresse',
			'data'  => array(
				'_'    => 'adresse_full',
				'sort' => 'adresse_ville',
			)
		);
	}
	if ( $show_emails ) {
		$columns[] = array(
			'title' => 'Email',
			'data'  => array(
				'_'    => 'email',
				'sort' => 'email',
			)
		);
	}
	if ( $show_phone ) {
		$columns[] = array(
			'title' => 'Téléphone',
			'data'  => array(
				'_'    => 'tel',
				'sort' => 'tel',
			)
		);
	}

	foreach ( $all_contrat_instances as $contrat ) {
		$tit = '<span style="word-break: break-all; white-space: normal;">' . esc_html( $contrat->getModel()->getTitle() ) . '</span>';
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

	$columns[] = array(
		'title' => 'Commentaire',
		'data'  => 'comment',
	);

	$all_adhs  = AmapressContrats::get_active_adhesions( $all_contrat_instance_ids,
		null, $dist_lieu_id, $date, true, false );
	$liste     = array();
	$adhesions = array_group_by(
		$all_adhs,
		function ( $adh ) {
			/** @var AmapressAdhesion $adh */
			$user     = $adh->getAdherent()->getUser();
			$user_ids = array_unique( AmapressContrats::get_related_users( $user->ID ) );

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

		$line['first_name'] = implode( ' / ', array_map( function ( $user ) {
				return $user->first_name;
			}, $users ) ) . $panier_intermittent;
		$line['last_name']  = implode( ' / ', array_map( function ( $user ) use ( $for_pdf ) {
			$val = ! empty( $user->last_name ) ? $user->last_name : $user->display_name;
			if ( ! $for_pdf && current_user_can( 'edit_users' ) ) {
				$val = Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $user->ID ), $val );
			}
			$adh = AmapressUser::getBy( $user );
			if ( ! empty( $adh->getCoAdherents() ) ) {
				$val .= ' / ' . esc_html( $adh->getCoAdherents() );
			}

			return $val;
		}, $users ) );

		if ( $for_pdf ) {
			$line['last_name'] = '<span>[]</span>&nbsp;' . $line['last_name'];
		}

		if ( $show_phone ) {
			$phones = array_unique( array_map( function ( $user ) use ( $for_pdf ) {
				$adh = AmapressUser::getBy( $user );

				return $adh->getTelTo( true, false, $for_pdf ) . ( ! empty( $adh->getCoAdherentsInfos() ) ? ' / ' . esc_html( $adh->getCoAdherentsInfos() ) : '' );
			}, $users ) );
			if ( $for_pdf ) {
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
			$line[ 'contrat_' . $adh->getContrat_instance()->ID ] .= $adh->getContrat_quantites_Codes_AsString( $date );
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

		if ( $for_pdf ) {
			$line['comment'] = '';
		} else {
			$comment = esc_html( $principal_user->getCommentEmargement() );
			if ( empty( $comment ) ) {
				$comment = '<span class="edit-user-comment">Editer</span>';
			}
			$line['comment'] = Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $principal_user->ID . '#amapress_user_comment_emargement' ), $comment, false );//;
		}

		$liste[] = $line;
	}

	$liste = from( $liste )->orderBy( function ( $l ) {
		return strip_tags( $l['last_name'] );
	} )->toArray();

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
			echo '<a href="' . esc_attr( remove_query_arg( 'all' ) ) . '" class="btn btn-default btn-print">Uniqument contrats à cette distribution</a>';
		} else {
			echo '<a href="' . esc_attr( add_query_arg( 'all', '' ) ) . '" class="btn btn-default btn-print">Afficher tous les contrats</a>';
		}
		echo '<a href="' . esc_attr( $pdf_url ) . '" class="btn btn-default btn-print">Imprimer en PDF</a>';
		echo '<br/>';
		if ( current_user_can( 'edit_distribution' ) ) {
			echo '<a href="' . esc_attr( $dist->getAdminEditLink() ) . '" class="btn btn-default">Editer la distribution</a>';
			echo '<a href="' . esc_attr( admin_url( 'admin.php?page=amapress_emargement_options_page' ) ) . '" class="btn btn-default">Editer les paramètres de la liste de distribution</a>';
		}
		$mailto = $dist->getMailtoResponsables();
		if ( ! empty( $mailto ) ) {
			echo '<a href="' . $mailto . '" class="btn btn-default">Mail aux responsables</a>';
		}
		$smsto = $dist->getSMStoResponsables();
		if ( ! empty( $smsto ) ) {
			echo '<a href="' . $mailto . '" class="btn btn-default">SMS aux responsables</a>';
		}
		if ( current_user_can( 'edit_lieu_distribution' ) ) {
			echo '<a href="' . esc_attr( $dist->getLieu()->getAdminEditLink() ) . '" class="btn btn-default">Editer les infos du lieu</a>';
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

	if ( ! $for_pdf ) {
		echo '<h3 class="liste-emargement">Liste</h3>';
	}
	amapress_echo_datatable( 'liste-emargement', $columns, $liste,
		array(
			'paging'       => false,
			'searching'    => false,
			'nowrap'       => false,
			'responsive'   => false,
			'init_as_html' => true,
			'no_script'    => $for_pdf,
			'aaSorting'    => [ [ 1, 'asc' ] ]
		),
		array(
			Amapress::DATATABLES_EXPORT_EXCEL
		) );

	foreach ( $dist->getContrats() as $contrat ) {
		if ( $contrat->isPanierVariable() ) {
			$panier_commandes = AmapressPaniers::getPanierVariableCommandes( $contrat->ID, $date );

			if ( ! empty( $panier_commandes['data'] ) ) {
				echo '<br/>';
				echo '<h3 class="liste-emargement-contrat-variable">Détails des paniers - ' . esc_html( $contrat->getTitle() ) . '</h3>';
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
			}
		}
	}

	$from_date = Amapress::start_of_day( $date );
	if ( ! $for_pdf ) {
		echo '<br/>';
	}
	echo '<h3 class="liste-emargement-next-resps">' . esc_html( 'Responsables aux prochaines distributions - ' . $dist->getLieu()->getTitle() ) . '</h3>';
//	echo '<br/>';
	echo do_shortcode( '[inscription-distrib show_title=false for_emargement=true for_pdf=' . $for_pdf . ' show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=' . ( ! $for_pdf ) . ' max_dates=' . Amapress::getOption( 'liste-emargement-next-resp-count', 8 ) . ' date=' . $from_date . ' lieu=' . $dist_lieu_id . ']' );

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
			echo '<h3 class="liste-emargement-next-resps">' . esc_html( 'Responsables du jour - ' . $lieu->getTitle() ) . '</h3>';
//			echo '<br/>';
			echo do_shortcode( '[inscription-distrib show_title=false for_emargement=true for_pdf=' . $for_pdf . ' show_past=false show_for_resp=true max_dates=1 show_adresse=false show_avatar=' . ( ! $for_pdf ) . ' show_roles=false date=' . $from_date . ' lieu=' . $lieu_id . ']' );
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
				'show_tel_mobile' => 'false',
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

	if ( Amapress::toBool( Amapress::getOption( 'liste-emargement-show-lieu-instructions' ) ) ) {
		$lieu = ( $dist->getLieuSubstitution() ? $dist->getLieuSubstitution() : $dist->getLieu() );

		if ( strlen( trim( strip_tags( $lieu->getInstructions_privee() ) ) ) > 0 ) {
			echo '<br pagebreak="true"/>';
			echo '<h3 class="liste-emargement-instructions">' . esc_html( 'Instructions pour ' . $lieu->getShortName() ) . '</h3>';
			echo $lieu->getInstructions_privee();
			echo '<br/>';
			echo '<h4 class="liste-emargement-contact">Contacts</h4>';
			echo $lieu->getContact_externe();
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
	Amapress::sendPdfFromHtml(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, isset( $_GET['all'] ), true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.pdf' ) ) );
	die();
} );
add_action( 'amapress_do_query_action_distribution_liste-emargement-excel', function () {
	$dist = AmapressDistribution::getBy( get_the_ID() );
	Amapress::sendXLSXFromHtml(
		'<div style="font-size: ' . Amapress::getOption( 'liste-emargement-print-font-size', 8 ) . 'pt">' .
		getListeEmargement( $dist->ID, isset( $_GET['all'] ), true ) .
		'</div>',
		strtolower( sanitize_file_name( 'liste-emargement-' . $dist->getTitle() . '.pdf' ) ),
		'Liste d\'émargement - ' . $dist->getTitle() );
	die();
} );
