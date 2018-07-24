<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 06/12/2017
 * Time: 08:03
 */

function amapress_clean_transients( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( AmapressLieu_distribution::INTERNAL_POST_TYPE == $post_type ) {
		delete_option( 'amps_lieux' );
	}

	if ( 'page' == $post_type ) {
		delete_transient( 'amp_inscr_distrib_href' );
	}
	if ( AmapressContrat_instance::INTERNAL_POST_TYPE == $post_type ) {
		delete_option( 'amps_gen_pan_' . $post_id );
		delete_option( 'amps_gen_dist_' . $post_id );
	}
	if ( AmapressAdhesionRequest::INTERNAL_POST_TYPE == $post_type ) {
		delete_option( 'amps_adh_req_count' );
	}

	if ( AmapressPanier::INTERNAL_POST_TYPE == $post_type ) {
		delete_option( 'amps_delay_pan' );
	}

	if ( AmapressContrat::INTERNAL_POST_TYPE == $post_type
	     || AmapressContrat_instance::INTERNAL_POST_TYPE == $post_type
	     || AmapressProducteur::INTERNAL_POST_TYPE == $post_type
	     || AmapressLieu_distribution::INTERNAL_POST_TYPE == $post_type ) {
		delete_transient( 'amps_refs_prods' );
	}
}

add_action( 'save_post', 'amapress_clean_transients', 1000 );

