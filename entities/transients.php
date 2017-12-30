<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 06/12/2017
 * Time: 08:03
 */

do_action( 'save_post', function ( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( AmapressLieu_distribution::INTERNAL_POST_TYPE == $post_type ) {
		delete_option( 'amps_lieux' );
	}
//	if ( AmapressContrat_instance::INTERNAL_POST_TYPE == $post_type ) {
//		delete_option( 'amps_active_contrats' );
//	}

	if ( AmapressContrat::INTERNAL_POST_TYPE == $post_type
	     || AmapressContrat_instance::INTERNAL_POST_TYPE == $post_type
	     || AmapressProducteur::INTERNAL_POST_TYPE == $post_type
	     || AmapressLieu_distribution::INTERNAL_POST_TYPE == $post_type ) {
		delete_transient( 'amps_refs_prods' );
	}
}, 1000 );

