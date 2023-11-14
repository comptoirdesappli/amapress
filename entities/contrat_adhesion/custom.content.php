<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//add_filter('amapress_get_user_infos_content_adhesions', 'amapress_get_user_infos_content_adhesions', 10, 2);
function amapress_display_user_adhesions_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	$adhs             = AmapressAdhesion::getUserActiveAdhesionsWithAllowPartialCheck();
	$adhesion_columns = array(
		array(
			'title' => __( 'Adhérent', 'amapress' ),
			'data'  => array(
				'_'    => 'adherent',
				'sort' => 'adherent',
			)
		),
		array(
			'title' => __( 'Contrat', 'amapress' ),
			'data'  => array(
				'_'    => 'contrat',
				'sort' => 'contrat',
			)
		),
		array(
			'title' => __( 'Quantité', 'amapress' ),
			'data'  => array(
				'_'    => 'quantite',
				'sort' => 'quantite',
			)
		),
		array(
			'title' => __( 'Du', 'amapress' ),
			'data'  => array(
				'_'    => 'from_date.display',
				'sort' => 'from_date.value',
			)
		),
		array(
			'title' => __( 'Au', 'amapress' ),
			'data'  => array(
				'_'    => 'to_date.display',
				'sort' => 'to_date.value',
			)
		),
		array(
			'title' => __( 'Lieu', 'amapress' ),
			'data'  => array(
				'_'    => 'lieu',
				'sort' => 'lieu',
			)
		),
		array(
			'title' => __( 'Etat', 'amapress' ),
			'data'  => array(
				'_'    => 'state',
				'sort' => 'state',
			)
		),
	);
	$adhesion_data    = array();
	foreach ( $adhs as $ad ) {
		$contrat       = $ad->getContrat_instance();
		$contrat_model = $contrat->getModel();
//        $status = get_post_meta($ad->ID, 'amapress_adhesion_status', true);
//        $status_text = $status=='to_confirm' ? __('En attente de confirmation', 'amapress') : __('ConfirmÃ©e', 'amapress');
		$date_debut      = $ad->getDate_debut();
		$date_fin        = $ad->getDate_fin();
		$url             = $contrat_model->getPermalink(); //trailingslashit(get_post_permalink($contrat_model->ID)).'details/';
		$adhesion_data[] = array(
			'contrat'   => amapress_get_html_a( $url, $contrat_model->getTitle() ),
			'quantite'  => $ad->getContrat_quantites_AsString(),
			'from_date' => array(
				'display' => date_i18n( 'd/m/Y', $date_debut ),
				'value'   => $date_debut
			),
			'to_date'   => array(
				'display' => date_i18n( 'd/m/Y', $date_fin ),
				'value'   => $date_fin
			),
			'adherent'  => $ad->getAdherent()->getDisplayName(),
			'lieu'      => $ad->getLieu()->getTitle(),
			'state'     => amapress_get_html_tag( 'span', $ad->getStatusDisplay(), $ad->getStatus() ),
		);
	}


	return amapress_get_datatable( 'my-subscriptions', $adhesion_columns, $adhesion_data );
}

//function amapress_display_intermittent_adhesions_shortcode($atts)
//{
//    if (!amapress_is_user_logged_in()) return '';
//    if (!Amapress::isIntermittenceEnabled()) return '';
//
//    $intermittence_columns = array(
//        array(
//            'title' => __('Du', 'amapress'),
//            'data' => array(
//                '_' => 'from_date.display',
//                'sort' => 'from_date.value',
//            )
//        ),
////        array(
////            'title' => __('Au', 'amapress'),
////            'data' => array(
////                '_' => 'to_date.display',
////                'sort' => 'to_date.value',
////            )
////        ),
//        array(
//            'title' => __('Etat', 'amapress'),
//            'data' => array(
//                '_' => 'state',
//                'sort' => 'state',
//            )
//        ),
//    );
//    $intermittence_data = array();
//    $adhs = AmapressContrats::get_user_active_intermittence();
//    foreach ($adhs as $ad) {
//        $date_debut = $ad->getDate_debut();
//        $date_fin = $ad->getDate_fin();
//        $intermittence_data[] = array(
//            'from_date' => array(
//                'display' => date_i18n('d/m/Y', $date_debut),
//                'value' => $date_debut
//            ),
//            'to_date' => array(
//                'display' => date_i18n('d/m/Y', $date_fin),
//                'value' => $date_fin
//            ),
//            'state' => amapress_get_html_tag('span', $ad->getStatusDisplay(), $ad->getStatus()),
//        );
//    }
//
//    return amapress_get_datatable('my-intermittence', $intermittence_columns, $intermittence_data);
//}
