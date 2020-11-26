<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_contrat_paiement' );
function amapress_register_entities_contrat_paiement( $entities ) {
	$entities['contrat_paiement'] = array(
		'internal_name'    => 'amps_cont_pmt',
		'singular'         => __( 'Chèque/Règlement Inscription Contrat', 'amapress' ),
		'plural'           => __( 'Chèques/Règlement Inscription Contrat', 'amapress' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'contrat_paiements',
		'title_format'     => 'amapress_contrat_paiement_title_formatter',
		'slug_format'      => 'from_title',
		'title'            => false,
		'editor'           => false,
		'menu_icon'        => 'flaticon-business',
		'default_orderby'  => 'amapress_contrat_paiement_date',
		'default_order'    => 'ASC',
		'labels'           => array(
			'add_new'      => __( 'Ajouter', 'amapress' ),
			'add_new_item' => __( 'Saisie Chèque/Règlement Inscription Contrat', 'amapress' ),
		),
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_paiements_views',
			'exp_csv' => true,
		),
		'fields'           => array(
//            'user' => array(
//                'name' => amapress__('Amapien'),
//                'type' => 'select-users',
//                'required' => true,
//                'desc' => __('Amapien', 'amapress'),
//                'import_key' => true,
//                'csv_required' => true,
//            ),
			'date'          => array(
				'name'         => __( 'Date de paiement', 'amapress' ),
				'type'         => 'date',
				'required'     => true,
				'desc'         => __( 'Indiquez la date prévisionnelle d\'encaissement du chèque par le producteur', 'amapress' ),
//                'import_key' => true,
				'csv_required' => true,
				'searchable'   => true,
				'top_filter'   => array(
					'name'           => 'amapress_date',
					'placeholder'    => __( 'Toutes les dates', 'amapress' ),
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'column_link'  => function ( $option, $post_id ) {
					$value = $option->getValue( $post_id );

					if ( empty( $value ) ) {
						return '';
					}

					return add_query_arg( 'amapress_date', date( 'Y-m-d', @intval( $value ) ) );
				}
			),
			'date_month'    => array(
				'name'           => __( 'Mois/Année', 'amapress' ),
				'type'           => 'custom',
				'hidden'         => true,
				'col_def_hidden' => true,
				'show_column'    => true,
				'column'         => function ( $post_id ) {
					$paiement = AmapressAmapien_paiement::getBy( $post_id );
					if ( ! $paiement || ! $paiement->getAdhesion() ) {
						return '';
					}

					return date_i18n( 'm/Y', $paiement->getDate() );
				},
			),
			'date_emission' => array(
				'name'         => __( 'Date d\'émission', 'amapress' ),
				'type'         => 'date',
				'required'     => false,
				'desc'         => __( 'Date d\'émission', 'amapress' ),
//                'import_key' => true,
				'csv_required' => true,
				'searchable'   => true,
			),
			'adhesion'      => array(
				'name'         => __( 'Inscription', 'amapress' ),
				'type'         => 'select-posts',
				'post_type'    => 'amps_adhesion',
				'required'     => true,
				'desc'         => __( 'Sélectionner le contrat relatif au règlement', 'amapress' ),
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'custom_save'  => 'amapress_contrat_paiement_set_contrat_instance',
				'searchable'   => true,
				'show_column'  => false,
			),
			'contrat'       => array(
				'name'       => __( 'Contrat', 'amapress' ),
				'type'       => 'custom',
				'hidden'     => true,
				'column'     => function ( $post_id ) {
					$paiement = AmapressAmapien_paiement::getBy( $post_id );
					if ( ! $paiement || ! $paiement->getAdhesion() ) {
						return '';
					}

					return Amapress::makeLink(
						$paiement->getAdhesion()->getContrat_instance()->getAdminEditLink(),
						$paiement->getAdhesion()->getContrat_instance()->getTitle() );
				},
				'top_filter' => array(
					'name'           => 'amapress_contrat_inst',
					'placeholder'    => __( 'Tous les contrats', 'amapress' ),
					'custom_options' => function ( $args ) {
						$ret      = [];
						$contrats = AmapressContrats::get_active_contrat_instances();
						usort( $contrats, function ( $a, $b ) {
							return strcmp( $a->getTitle(), $b->getTitle() );
						} );
						foreach ( $contrats as $contrat ) {
							$ret[ strval( $contrat->ID ) ] = $contrat->getTitle();
						}

						return $ret;
					}
				),
			),
			'lieu'          => array(
				'name'       => __( 'Lieu', 'amapress' ),
				'type'       => 'custom',
				'hidden'     => true,
				'column'     => function ( $post_id ) {
					$paiement = AmapressAmapien_paiement::getBy( $post_id );
					if ( ! $paiement || ! $paiement->getAdhesion() ) {
						return '';
					}

					return Amapress::makeLink( add_query_arg( 'amapress_lieu', $paiement->getAdhesion()->getLieuId() ),
						$paiement->getAdhesion()->getLieu()->getLieuTitle() );
				},
				'top_filter' => array(
					'name'           => 'amapress_lieu',
					'placeholder'    => __( 'Tous les lieux', 'amapress' ),
					'custom_options' => function ( $args ) {
						$ret   = [];
						$lieux = Amapress::get_lieux();
						usort( $lieux, function ( $a, $b ) {
							return strcmp( $a->getTitle(), $b->getTitle() );
						} );
						foreach ( $lieux as $lieu ) {
							$ret[ strval( $lieu->ID ) ] = $lieu->getTitle();
						}

						return $ret;
					}
				),
			),
			'status'        => array(
				'name'         => __( 'Statut', 'amapress' ),
				'type'         => 'select',
				'options'      => array(
					'not_received' => __( 'Non reçu', 'amapress' ),
					'received'     => __( 'Reçu', 'amapress' ),
					'bank'         => __( 'Remis', 'amapress' ),
				),
				'required'     => true,
				'desc'         => __( 'Sélectionner l’option qui convient : Reçu à l’Amap, non reçu à l’Amap, Remis au producteur', 'amapress' ),
				'csv_required' => true,
				'top_filter'   => array(
					'name'        => 'amapress_status',
					'placeholder' => __( 'Tous les statut', 'amapress' ),
				),
			),
			'type'          => array(
				'name'       => __( 'Type', 'amapress' ),
				'type'       => 'select',
				'options'    => array(
					'chq' => __( 'Chèque', 'amapress' ),
					'esp' => __( 'Espèces', 'amapress' ),
					'stp' => __( 'Paiement en ligne (Stripe)', 'amapress' ),
					'vir' => __( 'Virement', 'amapress' ),
					'mon' => __( 'Monnaie locale', 'amapress' ),
					'dlv' => __( 'A la livraison', 'amapress' ),
					'prl' => __( 'Prélèvement', 'amapress' ),
				),
				'default'    => 'chq',
				'required'   => true,
				'desc'       => __( 'Sélectionner le type de règlement', 'amapress' ),
				'top_filter' => array(
					'name'        => 'amapress_pmt_type',
					'placeholder' => __( 'Tous les type', 'amapress' ),
				),
			),
			'amount'        => array(
				'name'         => __( 'Montant', 'amapress' ),
				'type'         => 'float',
				'unit'         => '€',
				'required'     => true,
				'desc'         => __( 'Montant du chèque/espèces/virement/prélèvement', 'amapress' ),
				'csv_required' => true,
			),
			'numero'        => array(
				'name'         => __( 'Numéro du chèque', 'amapress' ),
				'type'         => 'text',
				'required'     => false,
				'desc'         => __( 'Numéro du chèque ou "Esp." pour des règlements en espèces ou "Vir." pour virement ou "Mon." pour des règlements en monnaie locale', 'amapress' ),
				'import_key'   => true,
				'csv_required' => true,
				'searchable'   => true,
			),
			'banque'        => array(
				'name'       => __( 'Banque', 'amapress' ),
				'type'       => 'text',
				'desc'       => __( 'Banque émettrice ou "Esp." pour des règlements en espèces ou "Vir." pour virement ou "Mon." pour des règlements en monnaie locale', 'amapress' ),
				'searchable' => true,
			),
			'emetteur'      => array(
				'name'       => __( 'Emetteur', 'amapress' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => __( 'Emetteur du règlement. Pour les chèques, renseigner obligatoirement le nom de l’émetteur qui figure sur le chèque (peut être différent du nom de l’amapien détenteur du contrat)', 'amapress' ),
				'searchable' => true,
			),
//            'categ_editor' => array(
//                'name' => amapress__('Répartitions'),
//                'type' => 'custom',
//                'column' => null,
//                'custom' => 'amapress_get_contrat_paiements_categories',
//                'save' => 'amapress_save_contrat_paiements_categories',
//                'desc' => __('Répartitions', 'amapress'),
//            ),
		),
		'row_actions'      => array(
			'mark_rcv'           => [
				'label'     => __( 'Marquer reçu', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAmapien_paiement::NOT_RECEIVED == AmapressAmapien_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'unmark_rcv'         => [
				'label'     => __( 'Marquer Non reçu', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAmapien_paiement::NOT_RECEIVED != AmapressAmapien_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'send_notrcv_recall' => [
				'label'     => __( 'Envoyer rappel', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAmapien_paiement::NOT_RECEIVED == AmapressAmapien_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'mark_bank'          => [
				'label'     => __( 'Marquer remis', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAmapien_paiement::RECEIVED == AmapressAmapien_paiement::getBy( $adh_id )->getStatus();
				},
			],
		),
		'bulk_actions'     => array(
			'amp_cnt_pmt_mark_recv'          => array(
				'label'    => __( 'Marquer reçu', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Un règlement a été marqué comme reçu avec succès', 'amapress' ),
					'>1' => '%s règlements ont été marqués comme reçus avec succès',
				),
			),
			'amp_cnt_pmt_mark_bank'          => array(
				'label'    => __( 'Marquer remis', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Un règlement a été marqué comme remis avec succès', 'amapress' ),
					'>1' => '%s règlements ont été marqués comme remis avec succès',
				),
			),
			'amp_cnt_pmt_send_notrcv_recall' => array(
				'label'    => __( 'Envoyer rappel', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Un rappel de règlement en attente a été envoyé avec succès', 'amapress' ),
					'>1' => '%s rappels de règlement en attente ont été envoyés avec succès',
				),
			),
		),
	);

	return $entities;
}

function amapress_contrat_paiement_set_contrat_instance( $contrat_paiement_id ) {
	if ( ! empty( $_POST['amapress_contrat_paiement_adhesion'] ) ) {
		$adh = AmapressAdhesion::getBy( intval( $_POST['amapress_contrat_paiement_adhesion'] ) );
		update_post_meta( $contrat_paiement_id, 'amapress_contrat_paiement_contrat_instance', $adh->getContrat_instanceId() );
	}
}

//
//
//function amapress_get_contrat_paiements_categories($paiement_id)
//{
//    $taxes = get_categories(array(
//        'orderby' => 'name',
//        'order' => 'ASC',
//        'taxonomy' => 'amps_paiement_category',
//        'hide_empty' => false,
//    ));
//    $terms = array_map(function($t) {
//        return $t->term_id;
//    }, wp_get_post_terms($paiement_id, 'amps_paiement_category'));
//    $amounts = Amapress::get_post_meta_array($paiement_id, 'amapress_contrat_paiement_repartition');
//    if (empty($amounts)) $amounts = array();
//    $ret = '<table>';
//    foreach ($taxes as $tax) {
//        $tax_used = in_array($tax->term_id, $terms);
//        $tax_amount = $tax_used && isset($amounts[$tax->term_id]) ? $amounts[$tax->term_id] : 0;
//        if (empty($tax_amount)) $tax_amount = 0;
//        $ret .= '<tr>';
//        $ret .= '<td><input type="checkbox" name="amapress_pmt_cat[' . $tax->term_id . ']" id="amapress_pmt_cat-' . $tax->term_id . '" class="checkbox" '.checked($tax_used, true, false).' value="1" /><label for="amapress_pmt_cat-' . $tax->term_id . '">'.esc_html($tax->name).'</label></td>';
//        $ret .= '<td><input type="number" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="price" value="' . $tax_amount . '" />€</td>';
//        $ret .= '</tr>';
//    }
//    $ret .= '</table>';
//    return $ret;
//}
//
//function amapress_save_contrat_paiements_categories($paiement_id)
//{
//    if (isset($_POST['amapress_pmt_cat']) && isset($_POST['amapress_pmt_amounts'])) {
//        $terms = array();
//        $amounts = array();
//        foreach ($_POST['amapress_pmt_cat'] as $tax_id => $used) {
//            $amount = isset($_POST['amapress_pmt_amounts'][$tax_id]) ? floatval($_POST['amapress_pmt_amounts'][$tax_id]) : 0;
//            if ($used) $terms[] = $tax_id;
//            if (!$used || !$amount) {
//                delete_post_meta($paiement_id, 'amapress_contrat_paiement_'.$tax_id);
//                continue;
//            }
//            $amounts[$tax_id] = $amount;
//        }
//        update_post_meta($paiement_id, 'amapress_contrat_paiement_repartition', $amounts);
//        wp_set_post_terms($paiement_id, $terms, 'amps_paiement_category');
//    }
//}

add_filter( 'amapress_can_edit_contrat_paiement', function ( $can, $post_id ) {
	if ( is_admin() && amapress_can_access_admin() && ! amapress_is_admin_or_responsable() && ! TitanFrameworkOption::isOnNewScreen() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		if ( count( $refs ) > 0 ) {
			$paiement = AmapressAmapien_paiement::getBy( $post_id );
			if ( $paiement && $paiement->getAdhesion() ) {
				foreach ( $refs as $r ) {
					if ( in_array( $paiement->getAdhesion()->getContrat_instanceId(), $r['contrat_instance_ids'] ) ) {
						return $can;
					}
				}
			}

			return false;
		}
	}

	return $can;
}, 10, 2 );