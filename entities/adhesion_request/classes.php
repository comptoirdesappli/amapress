<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesionRequest extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_adh_req';
	const POST_TYPE = 'adhesion_request';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

}
