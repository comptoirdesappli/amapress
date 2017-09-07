<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'wp_ajax_amapress_message_read', 'amapress_message_read_callback' );
function amapress_message_read_callback() {
	$msg = intval( $_POST['msg'] );
	amapress_get_message_read_for_user( amapress_current_user_id(), $msg );
	wp_die(); // this is required to terminate immediately and return a proper response
}

function amapress_get_user_unread_message( $user_id ) {
	$messages = Amapress::get_user_meta_array( $user_id, "amapress_user_messages" );
	if ( empty( $messages ) ) {
		return 0;
	}
	$read_messages = Amapress::get_user_meta_array( $user_id, "amapress_user_read_messages" );
	if ( empty( $read_messages ) ) {
		return count( $messages );
	}

	return count( array_intersect( $messages, $read_messages ) );
}

function amapress_get_message_read_for_user( $user_id, $message_id ) {
	//$post = get_user_by('id',$user_id);
	$messages = Amapress::get_user_meta_array( $user_id, "amapress_user_messages" );
	if ( ! empty( $messages ) && in_array( $message_id, $messages ) ) {
		$read_messages = Amapress::get_user_meta_array( $user_id, "amapress_user_read_messages" );
		if ( empty( $read_messages ) ) {
			$read_messages = array();
		}
		if ( ! in_array( $message_id, $read_messages ) ) {
			$read_messages[] = $message_id;
		}

		return get_post( $message_id );
	} else {
		return null;
	}
}

function amapress_display_messages_for_user( $container_id, $user_id ) {
	//$post = get_user_by('id',$user_id);
	$messages = Amapress::get_user_meta_array( $user_id, "amapress_user_messages" );
//    var_dump($messages);
	amapress_display_messages_by_list( $container_id, $messages );
}

function amapress_display_messages_for_post( $container_id, $post_id ) {
	$post     = get_post( $post_id );
	$pt       = amapress_simplify_post_type( $post->post_type );
	$messages = Amapress::get_post_meta_array( $post_id, "amapress_{$pt}_messages" );
	amapress_display_messages_by_list( $container_id, $messages );
}

function amapress_display_messages_by_list( $container_id, $messages ) {
	if ( empty( $messages ) ) {
		return;
	}
//     $messages = implode(',', $messages);
	amapress_display_messages_by_query( $container_id,
		array(
			'post__in'  => $messages,
//            'amapress_date' => 'active',
			'post_type' => AmapressMessage::INTERNAL_POST_TYPE,
			'orderby'   => 'post_date',
			'order'     => 'DESC',
		)
	);
}

function amapress_display_messages_by_query( $container_id, $messages_query ) {
//    var_dump($messages_query);
	$query    = new WP_Query( $messages_query );
	$messages = $query->get_posts();
//    var_dump($messages);

	amapress_echo_panel_start( 'Messages' );
	if ( count( $messages ) > 0 ) {
		echo '<div class="panel-group" id="' . $container_id . '" role="tablist" aria-multiselectable="true">';
	}
	$i = 1;
	foreach ( $messages as $message ) {
		$panel_id            = $container_id . '-panel' . $i;
		$collapse_id         = $container_id . '-collapse' . $i;
		$message_target_type = get_post_meta( $message->ID, 'amapress_message_target_type', true );
		$message_target_name = get_post_meta( $message->ID, 'amapress_message_target_name', true );
		$message_title       = $message->post_title;
		$message_content     = $message->post_content;
		$message_date        = date_i18n( 'd/m/Y à H:i', strtotime( $message->post_date ) );
		$read_messages       = Amapress::get_user_meta_array( amapress_current_user_id(), "amapress_user_read_messages" );
		if ( ! $read_messages ) {
			$read_messages = array();
		}
		$is_read = in_array( $message->ID, $read_messages );

		echo '<div class="panel panel-default">
            <div class="panel-heading" role="tab" id="' . $panel_id . '">
                <h4 class="panel-title ' . ( $is_read ? 'read' : 'unread' ) . '">
                    <a role="button" data-toggle="collapse" data-parent="#' . $container_id . '" href="#' . $collapse_id . '" aria-expanded="false" aria-controls="' . $collapse_id . '" data-message-id="' . $message->ID . '">
                        <span class="message-target-type message-target-' . $message_target_type . '"></span>
                        <span class="message-title">' . $message_title . '</span>
                        <span class="message-date">' . $message_date . '</span>
                    </a>
                </h4>
            </div>
            <div id="' . $collapse_id . '" class="panel-collapse collapse" role="tabpanel" aria-labelledby="' . $panel_id . '">
                <div class="panel-body">
                <p class="message-to">Pour "' . $message_target_name . '"</p>
                ' . $message_content . '
                </div>
            </div>
        </div>';

		$i ++;
	}
	if ( count( $messages ) > 0 ) {
		echo '</div>';
	} else {
		echo '<p class="no-message">Pas de message</p>';
	}
	amapress_echo_panel_end();

	echo '<script type="text/javascript">
jQuery(function($) {
    $(".unread").click(function() {
      var $this = $(this);
      if (!$this.has(".unread")) return;
      $.post("' . admin_url( 'admin-ajax.php' ) . '", {
        "msg": $this.data("message-id"),
        "action": "amapress_message_read"
      },
      function (response) {
        $this.removeClass("unread").addClass("read");
      });
    });
});
</script>';
}