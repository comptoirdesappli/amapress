<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_do_query_action_commande_commander', 'amapress_do_query_action_commande_commander' );
function amapress_do_query_action_commande_commander() {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	//TODO
	if ( is_singular( 'commande' ) ) {
		$commande_id   = get_post()->ID;
		$contrat       = intval( get_post_meta( $commande_id, 'amapress_commande_contrat_instance', true ) );
		$date          = intval( get_post_meta( $commande_id, 'amapress_commande_date_distrib', true ) );
		$lieu_post     = get_post( intval( get_post_meta( $commande_id, 'amapress_commande_lieu', true ) ) );
		$contrat_model = get_post( intval( get_post_meta( $contrat, 'amapress_contrat_instance_model', true ) ) );
		//$contrat_model = get_post(intval(get_post_meta($commande_id,'amapress_commande_contrat_instance',true)));
//            $producteur = intval(get_post_meta($contrat_model,'amapress_contrat_producteur',true));

		$my_post = array(
//            'post_title' => sprintf('Commande %s de %s du %02d-%02d-%04d � %s',
//                amapress_get_user_display_name(amapress_current_user_id()),
//                $contrat_model->post_title,
//                date('d', $date), date('m', $date), date('Y', $date),
//                $lieu_post->post_title),
			'post_type'    => 'amps_commande',
			'post_content' => '',
			'post_status'  => 'publish',
			'meta_input'   => array(
				'amapress_user_commande_commande' => $commande_id,
				'amapress_user_commande_amapien'  => amapress_current_user_id(),
			),
		);
		$new_id  = wp_insert_post( $my_post );
		wp_redirect_and_exit( get_post_permalink( $new_id ) );
	}
}