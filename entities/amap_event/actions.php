<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//add_action( 'amapress_do_query_action_amap_event_participer', 'amapress_do_query_action_amap_event_participer' );
//function amapress_do_query_action_amap_event_participer() {
//	$amap_event = new AmapressAmap_event( get_the_ID() );
//	$res        = $amap_event->inscrireParticipant( amapress_current_user_id() );
//	if ( 'already_in_list' == $res ) {
//		wp_redirect_and_exit( add_query_arg( array( 'message' => 'already_in_list' ), $amap_event->getPermalink() ) );
//	} else {
//		wp_redirect_and_exit( add_query_arg( array( 'message' => 'inscr_success' ), $amap_event->getPermalink() ) );
//	}
//}