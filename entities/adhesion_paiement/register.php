<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_paiement' );
function amapress_register_entities_adhesion_paiement( $entities ) {
	$entities['adhesion_paiement'] = array(
		'internal_name'    => 'amps_adh_pmt',
		'singular'         => amapress__( 'Chèque règlement Adhésions' ),
		'plural'           => amapress__( 'Chèques règlements Adhésions' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'adhesions_paiements',
		'title_format'     => 'amapress_adhesion_paiement_title_formatter',
		'slug_format'      => 'from_title',
		'title'            => false,
		'editor'           => false,
		'menu_icon'        => 'flaticon-business',
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'labels'           => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Saisie chèques adhésion',
//            'items_list' => 'xxx',
		),
		'row_actions'      => array(
			'mark_rcv'               => [
				'label'     => 'Marquer reçu',
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion_paiement::NOT_RECEIVED == AmapressAdhesion_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'unmark_rcv'             => [
				'label'     => 'Marquer Non reçu',
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion_paiement::RECEIVED == AmapressAdhesion_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'approve_user'           => [
				'label'     => 'Approuver amapien',
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $adh_id );

					if ( AmapressAdhesion_paiement::NOT_RECEIVED == $adh->getStatus() ) {
						return false;
					}

					$status = amapress_get_new_user_approve_user_status( $adh->getUserId() );
					if ( empty( $status ) ) {
						return false;
					}

					return 'approved' != $status;
				},
			],
			'generate_bulletin_docx' => [
				'label'     => 'Générer le bulletin (DOCX)',
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $adh_id );

					return ! empty( $adh ) && ! empty( $adh->getPeriod() ) && ! empty( $adh->getPeriod()->getWordModelId() );
				},
			],
			'generate_bulletin_pdf'  => [
				'label'     => 'Générer le bulletin (PDF)',
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $adh_id );

					return ! empty( $adh ) && ! empty( $adh->getPeriod() ) && ! empty( $adh->getPeriod()->getWordModelId() );
				},
			],
		),
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_adhesion_paiement_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'user'           => array(
				'name'         => amapress__( 'Amapien' ),
				'type'         => 'select-users',
				'required'     => true,
				'desc'         => 'Sélectionner un amapien. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs',
				'group'        => '1/ Adhérent',
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'searchable'   => true,
			),
			'adherent_email' => array(
				'csv_import'           => false,
				'csv_export'           => true,
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'group'                => '1/ Adhérent',
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'name'                 => amapress__( 'Email' ),
				'type'                 => 'custom',
				'custom'               => function ( $post_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $post_id );
					if ( $adh->getUser() ) {
						$email = $adh->getUser()->getEmail();

						return Amapress::makeLink( 'mailto:' . $email, $email );
					}

					return '';
				}
			),
			'period'         => array(
				'name'              => amapress__( 'Période adhésion' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
				'desc'              => 'Période adhésion',
				'group'             => '2/ Adhésion',
				'import_key'        => true,
				'required'          => true,
				'autoselect_single' => true,
				'top_filter'        => array(
					'name'        => 'amapress_adhesion_period',
					'placeholder' => 'Toutes les périodes'
				),
				'csv_required'      => true,
			),
			'date'           => array(
				'name'         => amapress__( 'Date' ),
				'type'         => 'date',
				'required'     => true,
				'desc'         => 'Date d\'émission',
				'csv_required' => true,
				'group'        => '2/ Adhésion',
			),
//            'date_emission' => array(
//                'name' => amapress__('Date d\'émission'),
//                'type' => 'date',
//                'required' => true,
//                'desc' => 'Date d\'émission',
////                'import_key' => true,
//                'csv_required' => true,
//            ),
			'status'         => array(
				'name'         => amapress__( 'Statut' ),
				'type'         => 'select',
				'group'        => '2/ Adhésion',
				'options'      => array(
					'not_received' => 'Non reçu',
					'received'     => 'Reçu',
					'bank'         => 'Remis',
				),
				'top_filter'   => array(
					'name'        => 'amapress_status',
					'placeholder' => 'Toutes les statuts'
				),
				'required'     => true,
				'desc'         => 'Sélectionner l’option qui convient : Reçu à l’Amap, non reçu à l’Amap, Remis',
				'csv_required' => true,
			),
			'intermittent'   => array(
				'name'    => amapress__( 'Intermittent?' ),
				'type'    => 'checkbox',
				'default' => false,
				'group'   => '2/ Adhésion',
				'desc'    => 'Indique une adhésion d\'un intermittent',
			),
			'pmt_type'       => array(
				'name'           => amapress__( 'Moyen de règlement principal' ),
				'type'           => 'select',
				'options'        => array(
					'chq' => 'Chèque',
					'esp' => 'Espèces',
					'vir' => 'Virement',
					'mon' => 'Monnaie locale',
					'hla' => 'HelloAsso',
				),
				'default'        => 'chq',
				'required'       => true,
				'desc'           => 'Moyen de règlement principal : chèques ou espèces ou virement',
				'group'          => '3/ Règlement',
				'show_column'    => true,
				'col_def_hidden' => true,
				'top_filter'     => array(
					'name'        => 'amapress_pmt_type',
					'placeholder' => 'Tous les type de paiement',
				),
			),
			'numero'         => array(
				'name'       => amapress__( 'Numéro du règlement' ),
				'type'       => 'text',
				'desc'       => 'Numéro du chèque ou "Esp." pour un règlement en espèces ou "Vir." pour un virement ou "Mon." pour un règlement en monnaie locale ou HelloAsso',
				'group'      => '3/ Règlement',
				'searchable' => true,
			),
			'banque'         => array(
				'name'       => amapress__( 'Banque' ),
				'type'       => 'text',
				'desc'       => 'Banque émettrice',
				'group'      => '3/ Règlement',
				'searchable' => true,
			),
			'categ_editor'   => array(
				'name'       => amapress__( 'Répartitions' ),
				'type'       => 'custom',
				'column'     => 'amapress_get_adhesion_paiements_summary',
				'custom'     => 'amapress_get_adhesion_paiements_categories',
				'save'       => 'amapress_save_adhesion_paiements_categories',
				'csv_import' => false,
				'csv_export' => true,
				'group'      => '3/ Règlement',
			),
			'amount'         => array(
				'name'       => amapress__( 'Montant' ),
				'type'       => 'readonly',
				'unit'       => '€',
				'desc'       => 'Montant',
				'csv_import' => false,
				'group'      => '3/ Règlement',
			),
			'hla_url'        => array(
				'name'           => amapress__( 'Lien HelloAsso' ),
				'type'           => 'custom',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => '4/ HelloAsso',
				'custom'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() ) {
						return 'NA';
					}

					return Amapress::makeLink( $adh_pmt->getHelloAssoUrl(), 'Voir dans le backoffice HelloAsso', true, true );
				},
				'column'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() ) {
						return '';
					}

					return Amapress::makeLink( $adh_pmt->getHelloAssoUrl(), 'Voir', true, true );
				},
				'export'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() ) {
						return '';
					}

					return $adh_pmt->getHelloAssoUrl();
				},
			),
			'hla_amount'     => array(
				'name'           => amapress__( 'Montant HelloAsso' ),
				'type'           => 'custom',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => '4/ HelloAsso',
				'custom'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() ) {
						return 'NA';
					}

					return Amapress::formatPrice( $adh_pmt->getHelloAssoAmount(), true );
				},
				'column'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() ) {
						return '';
					}

					return Amapress::formatPrice( $adh_pmt->getHelloAssoAmount(), false );
				},
			),
			'lieu'           => array(
				'name'           => amapress__( 'Lieu dist.' ),
				'type'           => 'select-posts',
				'post_type'      => 'amps_lieu',
				'group'          => '5/ Informations',
				'desc'           => 'Lieu de distribution souhaité',
				'searchable'     => true,
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'lieu_type'      => array(
				'name'           => amapress__( 'Choix du lieu' ),
				'type'           => 'select',
				'group'          => '5/ Informations',
				'options'        => array(
					'none' => 'Aucun',
					'any'  => 'N\'importe lequel',
					'one'  => 'Spécifique',
				),
				'default'        => 'one',
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'message'        => array(
				'name'           => amapress__( 'Message' ),
				'type'           => 'textarea',
				'group'          => '4/ Informations',
				'desc'           => 'Message à l\'AMAP lors de l\'inscription en ligne',
				'col_def_hidden' => true,
			),
			'custom_check1'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK1,
				'type'           => 'checkbox',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => '5/ Informations',
			),
			'custom_check2'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK2,
				'type'           => 'checkbox',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => '5/ Informations',
			),
			'custom_check3'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK3,
				'type'           => 'checkbox',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => '5/ Informations',
			),
		),
		'bulk_actions'     => array(
			'amp_adh_pmt_mark_recv' => array(
				'label'    => 'Marquer reçu',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant l\'opération',
					'0'  => 'Une erreur s\'est produit pendant l\'opération',
					'1'  => 'Un règlement a été marqué comme reçu avec succès',
					'>1' => '%s règlements ont été marqués comme reçus avec succès',
				),
			),
		),
	);

	return $entities;
}


function amapress_get_adhesion_paiements_summary( $paiement_id ) {
	$taxes = get_categories( array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'taxonomy'   => 'amps_paiement_category',
		'hide_empty' => false,
	) );
//	$terms   = array_map( function ( $t ) {
//		return $t->term_id;
//	}, wp_get_post_terms( $paiement_id, 'amps_paiement_category' ) );
	$amounts = Amapress::get_post_meta_array( $paiement_id, 'amapress_adhesion_paiement_repartition' );
	if ( empty( $amounts ) ) {
		$amounts = array();
	}
	$ret = array();
	foreach ( $taxes as $tax ) {
//		$tax_used   = in_array( $tax->term_id, $terms );
		$tax_amount = isset( $amounts[ $tax->term_id ] ) ? $amounts[ $tax->term_id ] : 0;
		if ( empty( $tax_amount ) ) {
			$tax_amount = 0;
		}
		if ( $tax_amount < 0.001 ) {
			continue;
		}

		$ret[] = esc_html( sprintf( '%s=%.00f€', $tax->name, $tax_amount ) );
	}

	return implode( '<br/>', $ret );
}

function amapress_get_adhesion_paiements_categories( $paiement_id ) {
	$taxes   = get_categories( array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'taxonomy'   => 'amps_paiement_category',
		'hide_empty' => false,
	) );
	$terms   = array_map( function ( $t ) {
		return $t->term_id;
	}, wp_get_post_terms( $paiement_id, 'amps_paiement_category' ) );
	$amounts = Amapress::get_post_meta_array( $paiement_id, 'amapress_adhesion_paiement_repartition' );
	if ( empty( $amounts ) ) {
		$amounts = array();
	}
	$ret = '<table>';
	foreach ( $taxes as $tax ) {
		$tax_used   = in_array( $tax->term_id, $terms );
		$tax_amount = $tax_used && isset( $amounts[ $tax->term_id ] ) ? $amounts[ $tax->term_id ] : 0;
		if ( empty( $tax_amount ) ) {
			$tax_amount = 0;
		}
		$ret .= '<tr>';
		$ret .= '<td><label for="amapress_pmt_amount-' . $tax->term_id . '">' . esc_html( $tax->name ) . '</label></td>';
		$ret .= '<td><input type="number" id="amapress_pmt_amount-' . $tax->term_id . '" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="price amapress_pmt_cat_amount" value="' . $tax_amount . '" />€</td>';
		$ret .= '</tr>';
	}
	$ret .= '</table>';
	$ret .= '<script type="text/javascript">
jQuery(function($) {
    var updateAmount = function() {
        var sum = 0;
        $(".amapress_pmt_cat_amount").each(function() {
            sum += parseFloat($(this).val());
        });
        $("#amapress_adhesion_paiement_amount").text(sum.toFixed(2));
     };
    $(".amapress_pmt_cat_amount").on("change paste keyup", updateAmount);
    updateAmount();
});
 </script>';

	return $ret;
}

add_filter( 'amapress_import_adhesion_paiement_apply_default_values_to_posts_meta', 'amapress_import_adhesion_paiement_apply_default_values_to_posts_meta' );
function amapress_import_adhesion_paiement_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_adhesion_paiement_default_period'] )
	     && empty( $postmeta['amapress_adhesion_paiement_period'] ) ) {
		$postmeta['amapress_adhesion_paiement_period'] = $_REQUEST['amapress_import_adhesion_paiement_default_period'];
	}

	return $postmeta;
}

add_action( 'amapress_post_adhesion_paiement_import', 'amapress_post_adhesion_paiement_import', 10, 3 );
function amapress_post_adhesion_paiement_import( $post_id, $postdata, $postmeta ) {
	$postmeta['amapress_adhesion_paiement_repartition'] = get_post_meta( $post_id, 'amapress_adhesion_paiement_repartition', true );
	if ( empty( $postmeta['amapress_adhesion_paiement_repartition'] ) ) {
		$period = AmapressAdhesionPeriod::getBy( $postmeta['amapress_adhesion_paiement_period'] );
		if ( $period ) {
			$rep       = [];
			$amap_term = intval( Amapress::getOption( 'adhesion_amap_term' ) );
			if ( $amap_term ) {
				$rep[ $amap_term ] = $period->getMontantAmap();
			}
			$reseau_amap_term = intval( Amapress::getOption( 'adhesion_reseau_amap_term' ) );
			if ( $reseau_amap_term ) {
				$rep[ $reseau_amap_term ] = $period->getMontantReseau();
			}
			$postmeta['amapress_adhesion_paiement_repartition'] = $rep;
		}
	}
	if ( ! empty( $postmeta['amapress_adhesion_paiement_repartition'] ) ) {
		wp_set_post_terms( $post_id, array_keys( $postmeta['amapress_adhesion_paiement_repartition'] ), 'amps_paiement_category' );
		$sum = 0;
		foreach ( $postmeta['amapress_adhesion_paiement_repartition'] as $k => $v ) {
			$sum += $v;
		}
		update_post_meta( $post_id, 'amapress_adhesion_paiement_amount', $sum );
	}
}


function amapress_save_adhesion_paiements_categories( $paiement_id ) {
	if ( isset( $_POST['amapress_pmt_amounts'] ) ) {
		$terms   = array();
		$amounts = array();
		foreach ( $_POST['amapress_pmt_amounts'] as $tax_id => $amount ) {
			if ( $amount > 0 ) {
				$terms[]            = intval( $tax_id );
				$amounts[ $tax_id ] = floatval( $amount );
			}
		}
		$total_amount = 0;
		foreach ( $amounts as $k => $v ) {
			$total_amount += $v;
		}
//        var_dump($terms);
//        var_dump($total_amount);
//        die();
		update_post_meta( $paiement_id, 'amapress_adhesion_paiement_repartition', $amounts );
		update_post_meta( $paiement_id, 'amapress_adhesion_paiement_amount', $total_amount );
		wp_set_post_terms( $paiement_id, $terms, 'amps_paiement_category' );
	}
}

add_filter( 'tf_select_users_title', 'amapress_adhesion_paiement_select_user_title', 10, 3 );
function amapress_adhesion_paiement_select_user_title( $title, $user, $option ) {
	if ( isset( $option->owner->settings['post_type'] )
	     && ( in_array( AmapressAdhesion_paiement::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] )
	          || in_array( AmapressAmapien_paiement::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] )
	          || in_array( AmapressAdhesion::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] ) ) ) {
//    if ($option_id == 'amapress_adhesion_paiement_user') {
		$amapien = AmapressUser::getBy( $user->ID );
		$co      = $amapien->getAdditionalCoAdherents();
		$address = $amapien->getFormattedAdresse();
		if ( ! empty( $address ) ) {
			$name = sprintf( '%s (%s)', $amapien->getDisplayName(), $address );
		} else {
			$name = $amapien->getDisplayName();
		}
		if ( ! empty( $co ) ) {
			return sprintf( '%s (%s) - %s', $name, $user->user_email, $co );
		} else {
			return sprintf( '%s (%s)', $name, $user->user_email );
		}
	}

	return $title;
//    }
//    return $title;
}

add_action( 'amapress_row_action_adhesion_paiement_generate_bulletin_docx', 'amapress_row_action_adhesion_paiement_generate_bulletin_docx' );
function amapress_row_action_adhesion_paiement_generate_bulletin_docx( $post_id ) {
	$adhesion       = AmapressAdhesion_paiement::getBy( $post_id );
	$full_file_name = $adhesion->generateBulletinDoc( true );
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'amapress_row_action_adhesion_paiement_approve_user', 'amapress_row_action_adhesion_paiement_approve_user' );
function amapress_row_action_adhesion_paiement_approve_user( $post_id ) {
	$adhesion = AmapressAdhesion_paiement::getBy( $post_id );
	delete_user_meta( $adhesion->getUserId(), 'pw_user_status' );
	delete_transient( 'new_user_approve_user_statuses' );
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_paiement_generate_bulletin_pdf', 'amapress_row_action_adhesion_paiement_generate_bulletin_pdf' );
function amapress_row_action_adhesion_paiement_generate_bulletin_pdf( $post_id ) {
	$adhesion       = AmapressAdhesion_paiement::getBy( $post_id );
	$full_file_name = $adhesion->generateBulletinDoc( false );
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'init', function () {
	global $pagenow;
	if ( is_main_query() && 'admin.php' == $pagenow
	     && count( $_GET ) == 1
	     && isset( $_GET['page'] )
	     && ( 'adhesion_paiements' == $_GET['page'] || 'contrat_paiements' == $_GET['page'] ) ) {
		wp_redirect_and_exit( add_query_arg( 'amapress_contrat', 'active' ) );
	}
} );

add_filter( 'amapress_gestion-adhesions_page_adhesion_paiements_default_hidden_columns', function ( $hidden ) {
	return array_merge( $hidden, [
		'amapress_user_no_renew',
		'amapress_user_no_renew_reason',
		'amapress_user_last_login',
		'amapress_user_adresse',
		'amapress_user_hidaddr',
		'amapress_user_telephone2',
		'amapress_user_telephone3',
		'amapress_user_telephone4',
		'amapress_user_co-adherent-1',
		'amapress_user_co-adherent-2',
		'amapress_user_co-adherent-3',
		'amapress_user_co-foyer-1',
		'amapress_user_co-foyer-2',
		'amapress_user_co-foyer-3',
		'amapress_user_co-adherents',
		'amapress_user_co-adherents-infos',
	] );
} );

add_action( 'admin_post_nopriv_helloasso', function () {
	if ( ! isset( $_REQUEST['key'] ) || amapress_sha_secret( 'helloasso' ) != $_REQUEST['key'] ) {
		wp_die( 'Accès invalide' );
	}

	$body = file_get_contents( 'php://input' );
	@error_log( 'HelloAsso callback: ' . $body );

	$json = json_decode( $body );
	if ( empty( $json ) ) {
		wp_die( 'Accès invalide' );
	}

	if ( 'Order' == $json->eventType ) {
		$order    = $json->data;
		$formType = $order->formType;
		if ( 'Membership' != $formType ) {
			wp_die( 'Type de formulaire non reconnu : doit être un formulaire d\'adhésion' );
		}
		$date  = DateTime::createFromFormat( DateTime::ISO8601, $order->date );
		$total = $order->amount->total;
		$payer = $order->payer;

		$adh_period = AmapressAdhesionPeriod::getCurrent( $date );
		if ( $adh_period ) {
			$default_email     = $payer->email;
			$default_firstName = $payer->firstName;
			$default_lastName  = $payer->lastName;
			$default_address   = $payer->address;
			$default_zipCode   = $payer->zipCode;
			$default_city      = $payer->city;
			$default_phone     = '';
			if ( isset( $order->items ) && is_array( $order->items ) ) {
				foreach ( $order->items as $item ) {
					if ( 'Membership' == $item->type ) {
						if ( isset( $item->user ) ) {
							$default_firstName = $item->user->firstName;
							$default_lastName  = $item->user->lastName;
						}
						if ( isset( $item->customFields ) && is_array( $item->customFields ) ) {
							foreach ( $item->customFields as $custom_field ) {
								if ( 0 === strcasecmp( Amapress::getOption( 'helloasso-email-field-name' ), $custom_field->name ) ) {
									$default_email = $custom_field->answer;
								} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-phone-field-name' ), $custom_field->name ) ) {
									$default_phone = $custom_field->answer;
								} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-address-field-name' ), $custom_field->name ) ) {
									$default_address = $custom_field->answer;
								} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-zipcode-field-name' ), $custom_field->name ) ) {
									$default_zipCode = $custom_field->answer;
								} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-city-field-name' ), $custom_field->name ) ) {
									$default_city = $custom_field->answer;
								}
							}
						}

						$user_id = amapress_create_user_if_not_exists(
							$default_email,
							$default_firstName,
							$default_lastName,
							sprintf( '%s, %s %s', $default_address, $default_zipCode, $default_city ),
							$default_phone
						);

						delete_user_meta( $user_id, 'pw_user_status' );
						delete_transient( 'new_user_approve_user_statuses' );

						$formSlug         = $order->formSlug;
						$organizationSlug = $order->organizationSlug;
						$numero           = $order->id;
						$order_date       = strtotime( $order->date );
						if ( empty( $order_date ) ) {
							$order_date = amapress_time();
						}
						$pmt = AmapressAdhesion_paiement::getForUser( $user_id, $date, true );
						$pmt->setHelloAsso(
							$total / 100.0,
							"https://www.helloasso.com/associations/{$organizationSlug}/adhesions/{$formSlug}/administration",
							$numero,
							$order_date,
							Amapress::toBool( Amapress::getOption( 'helloasso-auto-confirm' ) )
						);

						$pmt->sendConfirmationsAndNotifications(
							Amapress::toBool( Amapress::getOption( 'helloasso-send-confirm' ) ),
							Amapress::toBool( Amapress::getOption( 'helloasso-notif-tresoriers' ) ),
							Amapress::getOption( 'helloasso-notif-others' ),
							false
						);
					}
				}
			}
		}
	}
} );
