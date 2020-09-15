<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//add_action('restrict_manage_posts','amapress_add_posts_filters', 10, 2);
//function amapress_add_posts_filters($post_type, $which) {
//    if ('top' == $which) {
//        $post_type = amapress_simplify_post_type($post_type);
//        $fields = AmapressEntities::getPostTypeFields($post_type);
//    }
//}
//add_action('restrict_manage_users','amapress_add_users_filters');
//function amapress_add_users_filters($which) {
//    if ('top' == $which) {
//        $fields = AmapressEntities::getPostTypeFields('user');
//        foreach ($fields as $field) {
//            if (isset($field['filter'])) {
//                $filter = $field['filter'];
//            }
//        }
//    }
//}

add_action( 'amapress_init', 'amapress_init_views_filter' );
function amapress_init_views_filter() {
	foreach ( AmapressEntities::getPostTypes() as $name => $conf ) {
		if ( array_key_exists( 'views', $conf ) ) {
			if ( $name == 'user' ) {
				add_filter( 'views_users', 'amapress_users_views_filter' );
			} else {
				add_filter( 'views_edit-' . ( ! empty( $conf['internal_name'] ) ? $conf['internal_name'] : 'amps_' . $name ), 'amapress_posts_views_filter' );
			}
		}
	}
}

add_filter( 'months_dropdown_results', 'amapress_months_dropdown_results', 10, 2 );
function amapress_months_dropdown_results( $months, $post_type ) {
	$post_type = amapress_simplify_post_type( $post_type );
	$pts       = AmapressEntities::getPostTypes();
	if ( isset( $pts[ $post_type ] ) ) {
		if ( ! isset( $pts[ $post_type ]['months_dropdown'] ) || $pts[ $post_type ]['months_dropdown'] !== true ) {
			return array();
		}
	}

	return $months;
}

function amapress_posts_views_filter( $views ) {
	return amapress_views_process( $views, amapress_simplify_post_type( get_query_var( 'post_type' ) ) );
}

function amapress_users_views_filter( $views ) {
	return amapress_views_process( $views, 'user' );
}

function amapress_views_process( $views, $pt ) {
	$types = AmapressEntities::getPostTypes();
	if ( ! $pt || ! array_key_exists( $pt, $types ) ) {
		return $views;
	}

	$t = $types[ $pt ];
	foreach ( $t['views'] as $k => $v ) {
		if ( $k == 'exp_csv' ) {
			if ( $v !== false ) {
				$views[ $k ] = amapress_add_view_export_csv( $v, $pt == 'user' );
			}
		} else if ( $k == 'remove' && is_array( $v ) ) {
			foreach ( $v as $vv ) {
				unset( $views[ $vv ] );
			}
		} else if ( $k == '_dyn_' && is_callable( $v, false ) ) {
			$views = array_merge( $views, call_user_func( $v, $views ) );
		} else if ( $k == '_dyn_all_' && is_callable( $v, false ) ) {
			$views = call_user_func( $v, $views );
		} else if ( is_callable( $v, false ) ) {
			$views[ $k ] = call_user_func( $v );

		}
	}

	return $views;
}

function amapress_distribution_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'thisweek',
		"post_type=amps_distribution&amapress_date=thisweek",
		'Cette semaine' );
	amapress_add_view_button(
		$ret, 'nextweek',
		"post_type=amps_distribution&amapress_date=nextweek",
		'Semaine prochaine' );
	amapress_add_view_button(
		$ret, 'thismonth',
		"post_type=amps_distribution&amapress_date=thismonth",
		'Ce mois' );
	amapress_add_view_button(
		$ret, 'nextmonth',
		"post_type=amps_distribution&amapress_date=nextmonth",
		'Le mois prochain' );
	amapress_add_view_button(
		$ret, 'prevmonth',
		"post_type=amps_distribution&amapress_date=prevmonth",
		'Le mois précédent' );
	amapress_add_view_button(
		$ret, 'lastyear',
		"post_type=amps_distribution&amapress_date=lastyear",
		'L\'année précédente' );

	if ( count( Amapress::get_lieu_ids() ) > 1 ) {
		amapress_add_view_button(
			$ret, 'change_lieu',
			"post_type=amps_distribution&amapress_status=change_lieu",
			'Changement lieu' );
	}
	amapress_add_view_button(
		$ret, 'change_hours',
		"post_type=amps_distribution&amapress_status=change_hours",
		'Changement horaires' );
	amapress_add_view_button(
		$ret, 'change_paniers',
		"post_type=amps_distribution&amapress_status=change_paniers",
		'Changement livraisons' );

//        $prods = get_posts(array(
//                'posts_per_page' => -1,
//                'post_type' => 'amps_producteur')
//        );
//        foreach ($prods as $prod) {
//            amapress_add_view_button(
//                $ret, 'for_' . $prod->ID,
//                "post_type=distribution&amapress_producteur={$prod->ID}",
//                $prod->post_title);
//        }

//    $lieux = get_posts(array(
//            'posts_per_page' => -1,
//            'post_type' => 'amps_lieu')
//    );
//    foreach ($lieux as $lieu) {
//        amapress_add_view_button(
//            $ret, 'for_' . $lieu->ID,
//            "post_type=amps_distribution&amapress_lieu={$lieu->ID}",
//            $lieu->post_title);
//    }
	return $ret;
}

function amapress_visite_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'thisweek',
		"post_type=amps_visite&amapress_date=thisweek",
		'Cette semaine' );
	amapress_add_view_button(
		$ret, 'thismonth',
		"post_type=amps_visite&amapress_date=thismonth",
		'Ce mois' );
	amapress_add_view_button(
		$ret, 'nnext',
		"post_type=amps_visite&amapress_date=next",
		'A venir' );

	return $ret;
}

function amapress_assemblee_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'thisweek',
		"post_type=amps_assemblee&amapress_date=thisweek",
		'Cette semaine' );
	amapress_add_view_button(
		$ret, 'thismonth',
		"post_type=amps_assemblee&amapress_date=thismonth",
		'Ce mois' );
	amapress_add_view_button(
		$ret, 'nnext',
		"post_type=amps_assemblee&amapress_date=next",
		'A venir' );

	return $ret;
}

function amapress_amap_event_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'next_events',
		"post_type=amps_amap_event&amapress_date=next",
		'A venir' );
	amapress_add_view_button(
		$ret, 'thismonth',
		"post_type=amps_amap_event&amapress_date=thismonth",
		'Ce mois' );

	return $ret;
}

function amapress_adhesion_paiement_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'active',
		"post_type=amps_adh_pmt&amapress_date=active",
		'En cours' );

	amapress_add_view_button(
		$ret, 'not_received',
		"post_type=amps_adh_pmt&amapress_status=not_received",
		'Non reçu', false, true );

	amapress_add_view_button(
		$ret, 'pmt_esp',
		"post_type=amps_adh_pmt&amapress_date=active&amapress_pmt_type=esp",
		'Espèces' );
	amapress_add_view_button(
		$ret, 'pmt_vir',
		"post_type=amps_adh_pmt&amapress_date=active&amapress_pmt_type=vir",
		'Virement' );
	amapress_add_view_button(
		$ret, 'pmt_vir',
		"post_type=amps_adh_pmt&amapress_date=active&amapress_pmt_type=mon",
		'Monnaie locale' );

	return $ret;
}

function amapress_adhesion_period_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'active',
		"post_type=amps_adh_per&amapress_date=active",
		'En cours' );

	return $ret;
}

function amapress_adhesion_request_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'to_confirm',
		"post_type=amps_adh_req&amapress_date=active&amapress_status=to_confirm",
		'A confirmer' );

	return $ret;
}

function amapress_panier_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'lastweek',
		"post_type=amps_panier&amapress_date=lastweek",
		'Semaine dernière' );
	amapress_add_view_button(
		$ret, 'thisweek',
		"post_type=amps_panier&amapress_date=thisweek",
		'Cette semaine' );
	amapress_add_view_button(
		$ret, 'nextweek',
		"post_type=amps_panier&amapress_date=nextweek",
		'Semaine prochaine' );
	amapress_add_view_button(
		$ret, 'thismonth',
		"post_type=amps_panier&amapress_date=thismonth",
		'Ce mois' );
	amapress_add_view_button(
		$ret, 'nextmonth',
		"post_type=amps_panier&amapress_date=nextmonth",
		'Mois prochain' );
	amapress_add_view_button(
		$ret, 'prevmonth',
		"post_type=amps_panier&amapress_date=prevmonth",
		'Mois précédent' );

	amapress_add_view_button(
		$ret, 'delayed',
		"post_type=amps_panier&amapress_status=delayed&amapress_date=thisyear",
		'Reporté' );
	amapress_add_view_button(
		$ret, 'cancelled',
		"post_type=amps_panier&amapress_status=cancelled&amapress_date=thisyear",
		'Annulé' );

	$contrats = AmapressContrats::get_active_contrat_instances();
	foreach ( $contrats as $contrat ) {
		amapress_add_view_button(
			$ret, 'for_' . $contrat->ID,
			"post_type=amps_panier&amapress_date=active&amapress_contrat_inst={$contrat->ID}",
			$contrat->getTitle() );
	}

	return $ret;
}

function amapress_contrat_instance_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'actives',
		"post_type=amps_contrat_inst&amapress_date=active",
		'En cours' );

	foreach ( AmapressContrats::get_contrats() as $contrat ) {
		amapress_add_view_button(
			$ret, "contrat-{$contrat->ID}",
			"post_type=amps_contrat_inst&amapress_contrat={$contrat->ID}",
			$contrat->getTitle() );
	}

	amapress_add_view_button(
		$ret, 'to_renew',
		"post_type=amps_contrat_inst&amapress_date=renew",
		'A renouveler' );

	return $ret;
}

function amapress_adhesion_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'actives',
		"post_type=amps_adhesion&amapress_date=active",
		'En cours' );

//	$contrats = AmapressContrats::get_active_contrat_instances( null, Amapress::remove_a_year( amapress_time() ) );
	$contrats = AmapressContrats::get_active_contrat_instances();
	usort( $contrats, function ( $c1, $c2 ) {
		/** @var AmapressContrat_instance $c1 */
		/** @var AmapressContrat_instance $c2 */
		$c1_date = $c1->getDate_debut();
		$c2_date = $c2->getDate_debut();
		if ( $c1_date == $c2_date ) {
			return strcasecmp( $c1->getTitle(), $c2->getTitle() );
		}

		return $c1_date < $c2_date ? 1 : - 1;
	} );
	foreach ( $contrats as $contrat ) {
		amapress_add_view_button(
			$ret, 'for_' . $contrat->ID,
			"post_type=amps_adhesion&amapress_contrat_inst={$contrat->ID}",
			$contrat->getTitle() );
	}

	$lieux = Amapress::get_lieux();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			amapress_add_view_button(
				$ret, 'for_' . $lieu->ID,
				"post_type=amps_adhesion&amapress_date=active&amapress_lieu={$lieu->ID}",
				$lieu->getTitle() );
		}
	}
	amapress_add_view_button(
		$ret, 'with_coadh',
		"post_type=amps_adhesion&amapress_date=active&amapress_with_coadherents=true",
		'Avec coadhérents' );
	amapress_add_view_button(
		$ret, 'to_confirm',
		"post_type=amps_adhesion&amapress_date=active&amapress_status=to_confirm",
		'A confirmer', false, true );

	amapress_add_view_button(
		$ret, 'to_renew',
		"post_type=amps_adhesion&amapress_date=active&amapress_date=renew",
		'A renouveler' );

	amapress_add_view_button(
		$ret, 'ended',
		"post_type=amps_adhesion&amapress_date=ended",
		'Arrêtées/Clotûrées' );

	amapress_add_view_button(
		$ret, 'pmt_esp',
		"post_type=amps_adhesion&amapress_date=active&amapress_pmt_type=esp",
		'Espèces' );
	amapress_add_view_button(
		$ret, 'pmt_vir',
		"post_type=amps_adhesion&amapress_date=active&amapress_pmt_type=vir",
		'Virement' );
	amapress_add_view_button(
		$ret, 'pmt_vir',
		"post_type=amps_adhesion&amapress_date=active&amapress_pmt_type=mon",
		'Monnaie locale' );
	amapress_add_view_button(
		$ret, 'pmt_dlv',
		"post_type=amps_adhesion&amapress_date=active&amapress_pmt_type=dlv",
		'A la livraison' );
	amapress_add_view_button(
		$ret, 'pmt_prl',
		"post_type=amps_adhesion&amapress_date=active&amapress_pmt_type=prl",
		'Prélèvement' );
	//    amapress_add_view_button(
//        $ret, 'lastyear',
//        "post_type=amps_adhesion&amapress_date=lastyear",
//        'Année précédente');

	return $ret;
}

//function amapress_adhesion_intermittence_views()
//{
//    $ret = array();
//    amapress_add_view_button(
//        $ret, 'actives',
//        "post_type=amps_inter_adhe&amapress_date=active",
//        'En cours');
//
//    $lieux = get_posts(array(
//            'posts_per_page' => -1,
//            'post_type' => 'amps_lieu')
//    );
//    foreach ($lieux as $lieu) {
//        amapress_add_view_button(
//            $ret, 'for_' . $lieu->ID,
//            "post_type=amps_inter_adhe&amapress_date=active&amapress_lieu={$lieu->ID}",
//            $lieu->post_title);
//    }
//
//    return $ret;
//}

function amapress_user_views( $ret ) {
	$query_add             = '';
	$page                  = '';
	$is_adhesion_paiements = isset( $_GET['page'] ) && $_GET['page'] == 'adhesion_paiements';
	$is_contrat_paiements  = isset( $_GET['page'] ) && $_GET['page'] == 'contrat_paiements';
	if ( $is_adhesion_paiements ) {
		$page = 'adhesion_paiements';
		foreach ( $ret as $k => $v ) {
			if ( $k != 'all' ) {
				unset( $ret[ $k ] );
			}
		}
		$query_add = '&page=adhesion_paiements';
	}
	if ( $is_contrat_paiements ) {
		$page = 'contrat_paiements';
		foreach ( $ret as $k => $v ) {
			if ( $k != 'all' ) {
				unset( $ret[ $k ] );
			}
		}
		$query_add = '&page=contrat_paiements';
	}

	if ( ! isset( $_REQUEST['s'] )
	     && ( empty( $_GET['amapress_role'] ) || 'archivable' != $_GET['amapress_role'] ) ) {
		if ( empty( $page ) || $page == 'adhesion_paiements' ) {
			amapress_add_view_button(
				$ret, 'adhe_nok',
				"amapress_adhesion=nok$query_add",
				'Adhésions non réglées',
				true, true );
		}
		amapress_add_view_button(
			$ret, 'w_adhe',
			"amapress_contrat=active$query_add",
			'Amapiens avec contrats',
			true );
		amapress_add_view_button(
			$ret, 'norenew',
			"amapress_info=norenew$query_add",
			'Non renouvellement',
			true );
		amapress_add_view_button(
			$ret, 'no_loc',
			"amapress_info=address_unk&amapress_contrat=active$query_add",
			'Amapiens non localisés',
			true );
		amapress_add_view_button(
			$ret, 'no_tel',
			"amapress_info=phone_unk&amapress_contrat=active$query_add",
			'Amapiens sans téléphone',
			true );
		amapress_add_view_button(
			$ret, 'wo_adhe',
			"amapress_contrat=no$query_add",
			'Amapiens sans contrats',
			true );
		amapress_add_view_button(
			$ret, 'intermittents',
			"amapress_contrat=intermittent$query_add",
			'Intermittents',
			true );
		amapress_add_view_button(
			$ret, 'coadh',
			"amapress_contrat=coadherent$query_add",
			'Co-adhérents',
			true );
		amapress_add_view_button(
			$ret, 'with_coadh',
			"amapress_contrat=with_coadherent$query_add",
			'Amapiens avec co-adhérents',
			true );
		amapress_add_view_button(
			$ret, 'principal',
			"amapress_contrat=principal$query_add",
			'Amapiens Principaux',
			true );
		amapress_add_view_button(
			$ret, 'principal_contrat',
			"amapress_contrat=principal_contrat$query_add",
			'Amapiens Principaux (contrat)',
			true );
		amapress_add_view_button(
			$ret, 'collectif',
			"amapress_role=collectif$query_add",
			'Membres du collectif',
			true );
		if ( ! $is_adhesion_paiements ) {
			amapress_add_view_button(
				$ret, 'never_logged',
				"amapress_role=never_logged$query_add",
				'Jamais connecté',
				true );
			amapress_add_view_button(
				$ret, 'resp_distrib',
				"amapress_role=resp_distrib$query_add",
				'Responsable Distribution - Semaine',
				true );
			amapress_add_view_button(
				$ret, 'resp_distrib_next',
				"amapress_role=resp_distrib_next$query_add",
				'Responsable Distribution - Semaine prochaine',
				true );
			amapress_add_view_button(
				$ret, 'resp_distrib_month',
				"amapress_role=resp_distrib_month$query_add",
				'Responsable Distribution - Mois',
				true );

//			$contrats = AmapressContrats::get_active_contrat_instances( null, Amapress::remove_a_year( amapress_time() ) );
			$contrats = AmapressContrats::get_active_contrat_instances();
			usort( $contrats, function ( $c1, $c2 ) {
				/** @var AmapressContrat_instance $c1 */
				/** @var AmapressContrat_instance $c2 */
				$c1_date = $c1->getDate_debut();
				$c2_date = $c2->getDate_debut();
				if ( $c1_date == $c2_date ) {
					return strcasecmp( $c1->getTitle(), $c2->getTitle() );
				}

				return $c1_date < $c2_date ? 1 : - 1;
			} );
			foreach ( $contrats as $contrat ) {
				amapress_add_view_button(
					$ret, 'for_' . $contrat->ID,
					"amapress_contrat={$contrat->ID}$query_add",
					$contrat->getTitle(),
					true );
			}
		}

		$lieux = Amapress::get_lieux();
		if ( count( $lieux ) > 1 ) {
			foreach ( $lieux as $lieu ) {
				amapress_add_view_button(
					$ret, 'for_' . $lieu->ID,
					"amapress_lieu={$lieu->ID}$query_add",
					$lieu->getShortName(),
					true );
			}
		}
	}

	if ( amapress_is_admin_or_responsable() ) {
		amapress_add_view_button(
			$ret, 'archivable',
			"amapress_role=archivable$query_add",
			'Comptes archivables',
			true );
	}

//    amapress_add_view_button(
//        $ret, 'lastyear',
//        "post_type=amps_adhesion&amapress_date=lastyear",
//        'Année précédente');

	return $ret;
}

function amapress_paiements_views() {
	$ret = array();
	amapress_add_view_button(
		$ret, 'thisweek',
		"post_type=amps_cont_pmt&amapress_date=thisweek",
		'Cette semaine' );
	amapress_add_view_button(
		$ret, 'thismonth',
		"post_type=amps_cont_pmt&amapress_date=thismonth",
		'Ce mois' );

	$lieux    = Amapress::get_lieux();
	$contrats = AmapressContrats::get_active_contrat_instances();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
//		amapress_add_view_button(
//			$ret, 'for_' . $lieu->ID,
//			"post_type=amps_cont_pmt&amapress_lieu={$lieu->ID}",
//			$lieu->getShortName() );
			foreach ( $contrats as $contrat ) {
				amapress_add_view_button(
					$ret, 'for_' . $contrat->ID . '_' . $lieu->ID,
					"post_type=amps_cont_pmt&amapress_contrat_inst={$contrat->ID}&amapress_lieu={$lieu->ID}",
					$lieu->getShortName() . ' - ' . $contrat->getTitle() );
			}
		}
	}

	foreach ( $contrats as $contrat ) {
		amapress_add_view_button(
			$ret, 'for_' . $contrat->ID,
			"post_type=amps_cont_pmt&amapress_contrat_inst={$contrat->ID}",
			$contrat->getTitle() );
	}

	amapress_add_view_button(
		$ret, 'not_received',
		"post_type=amps_cont_pmt&amapress_status=not_received",
		'Non reçu', false, true );

	amapress_add_view_button(
		$ret, 'pmt_esp',
		"post_type=amps_cont_pmt&amapress_date=active&amapress_pmt_type=esp",
		'Espèces' );
	amapress_add_view_button(
		$ret, 'pmt_vir',
		"post_type=amps_cont_pmt&amapress_date=active&amapress_pmt_type=vir",
		'Virement' );
//	amapress_add_view_button(
//		$ret, 'pmt_dlv',
//		"post_type=amps_cont_pmt&amapress_date=active&amapress_pmt_type=dlv",
//		'A la livraison' );

	return $ret;
}

function amapress_intermittence_panier_views() {
	$ret = array();

	amapress_add_view_button(
		$ret, 'actives',
		"post_type=amps_inter_panier&amapress_date=active",
		'En cours' );
	amapress_add_view_button(
		$ret, 'thismonth',
		"post_type=amps_inter_panier&amapress_date=thismonth",
		'Ce mois' );
	amapress_add_view_button(
		$ret, 'nextmonth',
		"post_type=amps_inter_panier&amapress_date=nextmonth",
		'Mois prochain' );

	$lieux = Amapress::get_lieux();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			amapress_add_view_button(
				$ret, 'for_' . $lieu->ID,
				"post_type=amps_inter_panier&amapress_date=active&amapress_lieu={$lieu->ID}",
				$lieu->getShortName() );
		}
	}
	amapress_add_view_button(
		$ret, 'to_exchange',
		"post_type=amps_inter_panier&amapress_status=to_exchange",
		'A réserver' );
	amapress_add_view_button(
		$ret, 'exch_valid_wait',
		"post_type=amps_inter_panier&amapress_status=exch_valid_wait",
		'En attente validation' );
	amapress_add_view_button(
		$ret, 'exchanged',
		"post_type=amps_inter_panier&amapress_status=exchanged",
		'Réservé' );
	amapress_add_view_button(
		$ret, 'cancelled',
		"post_type=amps_inter_panier&amapress_status=cancelled",
		'Annulé' );
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			amapress_add_view_button(
				$ret, 'for_all_' . $lieu->ID,
				"post_type=amps_inter_panier&amapress_lieu={$lieu->ID}",
				$lieu->getShortName() );
		}
	}
//
//    $contrats = AmapressContrats::get_active_contrat_instances();
//    foreach ($contrats as $contrat) {
//        amapress_add_view_button(
//            $ret, 'for_' . $contrat->ID,
//            "post_type=amps_inter_adhe&amapress_date=active&amapress_contrat_inst={$contrat->ID}",
//            $contrat->getTitle());
//    }

	return $ret;
}

function amapress_produit_views() {
	$prods = get_posts( array(
			'posts_per_page' => - 1,
			'post_type'      => 'amps_producteur'
		)
	);
	$ret   = array();
	foreach ( $prods as $prod ) {
		amapress_add_view_button(
			$ret, 'for_' . $prod->ID,
			"post_type=amps_produit&amapress_producteur={$prod->ID}",
			$prod->post_title );
	}

	return $ret;
}

function amapress_add_view_button( &$arr, $id, $query, $title, $user = false, $show_zero = false ) {
	$btn = amapress_generate_view_button(
		$query,
		$title,
		$user,
		$show_zero );
	if ( ! $btn ) {
		return;
	}
	$arr[ $id ] = $btn;
}

function amapress_add_view_export_csv( $name = null, $is_user = false ) {
	$name = is_string( $name ) ? $name : null;
	if ( $is_user ) {
		$url = AmapressExport_Users::get_export_url( $name );
	} else {
		$url = AmapressExport_Posts::get_export_url( $name );
	}

	return "<a href='$url'>Exporter <span class='dashicons dashicons-external'></span></a>";
}

function amapress_generate_view_button( $query, $title, $user = false, $show_zero = false ) {
	global $wp_query, $pagenow;

	if ( $user ) {
		$cnt = get_users_count( $query );
	} else {
		$cnt = get_posts_count( $query );
	}

	$g_query = $_SERVER['QUERY_STRING'];
	$href    = "$pagenow?$query";
	$cls     = '';
	$arr     = array_diff_assoc( wp_parse_args( $query ), wp_parse_args( $g_query ) );
	unset( $arr['s'] );
	unset( $arr['order'] );
	unset( $arr['orderby'] );
	unset( $arr['action'] );
	unset( $arr['action2'] );
	unset( $arr['m'] );
	unset( $arr['meta_key'] );
	unset( $arr['filter_action'] );
	unset( $arr['posts_per_page'] );
	unset( $arr['post_status'] );
	unset( $arr['paged'] );
	$is_current = count( $arr ) == 0;
	if ( $is_current ) {
		$cls = 'class="current"';
	}

	if ( ! $show_zero && $cnt == 0 && ! $is_current ) {
		return null;
	}

	return "<a href='$href' $cls>$title <span class='count'>($cnt)</span></a>";
}