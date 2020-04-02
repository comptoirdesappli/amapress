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

	/** @return AmapressAdhesionRequest[] */
	public static function getAllToConfirm() {
		return self::getAll( 'to_confirm' );
	}

	/** @return AmapressAdhesionRequest[] */
	public static function getAll( $status = null ) {
		$key = "amapress_mlgrp_all_list_$status";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$filter = array(
				'post_type'      => AmapressAdhesionRequest::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
			);
			if ( $status ) {
				$filter['amapress_status'] = $status;
			}
			$res = array_map(
				function ( $p ) {
					return new AmapressAdhesionRequest( $p );
				},
				get_posts(
					$filter
				)
			);
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public function getEmail() {
		return $this->getCustom( 'amapress_adhesion_request_email' );
	}

	public function getFirstName() {
		return $this->getCustom( 'amapress_adhesion_request_first_name' );
	}

	public function getLastName() {
		return $this->getCustom( 'amapress_adhesion_request_last_name' );
	}

	public function getAdresse() {
		return $this->getCustom( 'amapress_adhesion_request_adresse' );
	}

	public function getTelephone() {
		return $this->getCustom( 'amapress_adhesion_request_telephone' );
	}

	public function getOtherInfo() {
		return $this->getCustom( 'amapress_adhesion_request_other_info' );
	}

	public function getIntermittent() {
		return $this->getCustom( 'amapress_adhesion_request_intermittent' );
	}

	/** @return AmapressContrat_instance[] */
	public function getContratInstances() {
		return $this->getCustomAsEntityArray( 'amapress_adhesion_request_contrat_intances', 'AmapressContrat_instance' );
	}

	/** @return AmapressLieu_distribution[] */
	public function getLieux() {
		return $this->getCustomAsEntityArray( 'amapress_adhesion_request_lieux', 'AmapressLieu_distribution' );
	}

	/** @return AmapressContrat[] */
	public function getContrats() {
		return $this->getCustomAsEntityArray( 'amapress_adhesion_request_contrats', 'AmapressContrat' );
	}
}
