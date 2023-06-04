<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressMessage extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_message';
	const POST_TYPE = 'message';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getTarget_name() {
		return $this->getCustomAsString( 'amapress_message_target_name' );
	}

	public function getQuery_string() {
		return $this->getCustomAsString( 'amapress_message_query_string' );
	}

	public function getTarget_type() {
		return $this->getCustomAsString( 'amapress_message_target_type' );
	}

	public function getAssociated_date() {
		return $this->getCustom( 'amapress_message_associated_date' );
	}

	public function getContent_for_sms() {
		return $this->getCustomAsString( 'amapress_message_content_for_sms' );
	}

	public function getSms_sent() {
		return $this->getCustom( 'amapress_message_sms_sent' );
	}
}
