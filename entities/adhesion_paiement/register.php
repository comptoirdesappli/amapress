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
		'special_options' => array(),
		'slug'            => 'adhesions_paiements',
		'title_format'    => 'amapress_adhesion_paiement_title_formatter',
		'slug_format'     => 'from_title',
		'title'           => false,
		'editor'          => false,
		'menu_icon'       => 'flaticon-business',
		'default_orderby' => 'post_title',
		'default_order'   => 'ASC',
		'labels'          => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Saisie chèques adhésion',
//            'items_list' => 'xxx',
		),
		'row_actions'     => array(
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
		'views'           => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_adhesion_paiement_views',
			'exp_csv' => true,
		),
		'fields'          => array(
			'user'         => array(
				'name'         => amapress__( 'Amapien' ),
				'type'         => 'select-users',
				'required'     => true,
				'desc'         => 'Sélectionner un amapien. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs',
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'searchable'   => true,
			),
			'period'       => array(
				'name'              => amapress__( 'Période adhésion' ),
				'type'              => 'select-posts',
//                'readonly' => 'edit',
//                'hidden' => true,
//                'group' => '2/ Contrat',
				'post_type'         => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
				'desc'              => 'Période adhésion',
				'import_key'        => true,
//                'required' => true,
				'autoselect_single' => true,
				'top_filter'        => array(
					'name'        => 'amapress_adhesion_period',
					'placeholder' => 'Toutes les périodes'
				),
				'csv_required'      => true,
			),
			'date'         => array(
				'name'         => amapress__( 'Date' ),
				'type'         => 'date',
				'required'     => true,
				'desc'         => 'Date d\'émission',
//                'import_key' => true,
				'csv_required' => true,
			),
//            'date_emission' => array(
//                'name' => amapress__('Date d\'émission'),
//                'type' => 'date',
//                'required' => true,
//                'desc' => 'Date d\'émission',
////                'import_key' => true,
//                'csv_required' => true,
//            ),
			'status'       => array(
				'name'         => amapress__( 'Statut' ),
				'type'         => 'select',
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
			'numero'       => array(
				'name'         => amapress__( 'Numéro du chèque' ),
				'type'         => 'text',
				'required'     => true,
				'desc'         => 'Numéro du chèque ou "Esp." pour un règlement en espèces',
				'import_key'   => true,
				'csv_required' => true,
				'searchable'   => true,
			),
			'banque'       => array(
				'name'       => amapress__( 'Banque' ),
				'type'       => 'text',
				'desc'       => 'Banque émettrice',
				'searchable' => true,
			),
			'categ_editor' => array(
				'name'   => amapress__( 'Répartitions' ),
				'type'   => 'custom',
				'column' => 'amapress_get_adhesion_paiements_summary',
				'custom' => 'amapress_get_adhesion_paiements_categories',
				'save'   => 'amapress_save_adhesion_paiements_categories',
//                'desc' => 'Répartitions',
			),
			'amount'       => array(
				'name' => amapress__( 'Montant' ),
				'type' => 'readonly',
				'unit' => '€',
				'desc' => 'Montant',
			),
		),
	);

	return $entities;
}


function amapress_get_adhesion_paiements_summary( $paiement_id ) {
	$taxes   = get_categories( array(
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
jQuery(function() {
    var updateAmount = function() {
        var sum = 0;
        jQuery(".amapress_pmt_cat_amount").each(function() {
            sum += parseFloat(jQuery(this).val());
        });
        jQuery("#amapress_adhesion_paiement_amount").text(sum.toFixed(2));
     };
    jQuery(".amapress_pmt_cat_amount").on("change paste keyup", updateAmount);
    updateAmount();
});
 </script>';

	return $ret;
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
		$co      = $amapien->getCoAdherents();
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