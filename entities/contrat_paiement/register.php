<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_contrat_paiement' );
function amapress_register_entities_contrat_paiement( $entities ) {
	$entities['contrat_paiement'] = array(
		'internal_name'    => 'amps_cont_pmt',
		'singular'         => amapress__( 'Chèque Inscription Contrat' ),
		'plural'           => amapress__( 'Chèques Inscription Contrat' ),
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
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Saisie Chèque Inscription Contrat',
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
//                'desc' => 'Amapien',
//                'import_key' => true,
//                'csv_required' => true,
//            ),
			'date'          => array(
				'name'         => amapress__( 'Date de remise' ),
				'type'         => 'date',
				'required'     => true,
				'desc'         => 'Date de remise du chèque au producteur',
//                'import_key' => true,
				'csv_required' => true,
				'searchable'   => true,
			),
			'date_emission' => array(
				'name'         => amapress__( 'Date d\'émission' ),
				'type'         => 'date',
				'required'     => true,
				'desc'         => 'Date d\'émission',
//                'import_key' => true,
				'csv_required' => true,
				'searchable'   => true,
			),
			'adhesion'      => array(
				'name'         => amapress__( 'Inscription' ),
				'type'         => 'select-posts',
				'post_type'    => 'amps_adhesion',
				'required'     => true,
				'desc'         => 'Sélectionner le contrat relatif au règlement',
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'custom_save'  => 'amapress_contrat_paiement_set_contrat_instance',
				'searchable'   => true,
			),
			'status'        => array(
				'name'         => amapress__( 'Statut' ),
				'type'         => 'select',
				'options'      => array(
					'not_received' => 'Non reçu',
					'received'     => 'Reçu',
					'bank'         => 'Remis',
				),
				'required'     => true,
				'desc'         => 'Sélectionner l’option qui convient : Reçu à l’Amap, non reçu à l’Amap, Remis au producteur',
				'csv_required' => true,
			),
			'amount'        => array(
				'name'         => amapress__( 'Montant' ),
				'type'         => 'float',
				'unit'         => '€',
				'required'     => true,
				'desc'         => 'Montant du chèque',
				'csv_required' => true,
			),
			'numero'        => array(
				'name'         => amapress__( 'Numéro du chèque' ),
				'type'         => 'text',
				'required'     => true,
				'desc'         => 'Numéro du chèque',
				'import_key'   => true,
				'csv_required' => true,
				'searchable'   => true,
			),
			'banque'        => array(
				'name'       => amapress__( 'Banque' ),
				'type'       => 'text',
				'desc'       => 'Banque émettrice',
				'searchable' => true,
			),
			'emetteur'      => array(
				'name'       => amapress__( 'Emetteur du chèque' ),
				'type'       => 'text',
				'required'   => true,
				'desc'       => 'Renseigner obligatoirement le nom de l’émetteur qui figure sur le chèque (peut être différent du nom de l’amapien détenteur du contrat)',
				'searchable' => true,
			),
//            'categ_editor' => array(
//                'name' => amapress__('Répartitions'),
//                'type' => 'custom',
//                'column' => null,
//                'custom' => 'amapress_get_contrat_paiements_categories',
//                'save' => 'amapress_save_contrat_paiements_categories',
//                'desc' => 'Répartitions',
//            ),
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