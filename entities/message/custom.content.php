<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//add_filter('amapress_get_user_infos_title_messagerie', 'amapress_get_user_infos_title_messagerie', 10, 2);
//function amapress_get_user_infos_title_messagerie($content, $subview) {
//    return 'Messagerie';
//}
//add_filter('amapress_get_user_infos_content_messagerie', 'amapress_get_user_infos_content_messagerie', 10, 2);
function amapress_user_messages_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	ob_start();
	amapress_display_messages_for_user( 'my-messages', amapress_current_user_id() );
	$content = ob_get_clean();

	return $content;
}

function amapress_user_messages_count_shortcode( $atts ) {
	if ( ! amapress_is_user_logged_in() ) {
		return '';
	}

	return '<span class="badge">' . amapress_get_user_unread_message( amapress_current_user_id() ) . '</span>';
}