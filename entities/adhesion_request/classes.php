<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesionRequest extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_adh_req';
	const POST_TYPE = 'adhesion_request';

	private static $entities_cache = array();

	public static function clearCache() {
		self::$entities_cache = array();
	}

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAdhesionRequest
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAdhesionRequest' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressAdhesionRequest( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

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

	public function getSendReplyCount() {
		return $this->getCustomAsInt( 'amapress_adhesion_request_rep_cnt' );
	}

	public function incrSendReplyCount() {
		$cnt = $this->getSendReplyCount() + 1;
		$this->setCustom( 'amapress_adhesion_request_rep_cnt', $cnt );
	}

	public function getAmapienIfExists() {
		$user = get_user_by( 'email', $this->getEmail() );
		if ( $user ) {
			return AmapressUser::getBy( $user );
		}

		return null;
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


	public static function getPlaceholdersHelp( $additional_helps = [], $for_recall = true ) {
		return Amapress::getPlaceholdersHelpTable( 'adh-request-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'de la demande d\'adhésion',
			$additional_helps, $for_recall ? 'recall' : true );
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret = array_merge( parent::getProperties(), [
					'email'     => [
						'desc' => __( 'Adresse Email', 'amapress' ),
						'func' => function ( AmapressAdhesionRequest $req ) {
							return $req->getEmail();
						}
					],
					'prenom'    => [
						'desc' => __( 'Adresse Email', 'amapress' ),
						'func' => function ( AmapressAdhesionRequest $req ) {
							return $req->getFirstName();
						}
					],
					'nom'       => [
						'desc' => __( 'Adresse Email', 'amapress' ),
						'func' => function ( AmapressAdhesionRequest $req ) {
							return $req->getLastName();
						}
					],
					'telephone' => [
						'desc' => __( 'Adresse Email', 'amapress' ),
						'func' => function ( AmapressAdhesionRequest $req ) {
							return $req->getTelephone();
						}
					],
					'adresse'   => [
						'desc' => __( 'Adresse Email', 'amapress' ),
						'func' => function ( AmapressAdhesionRequest $req ) {
							return $req->getAdresse();
						}
					],
				]
			);

			self::$properties = $ret;
		}

		return self::$properties;
	}

	public function sendReplyMail() {
		$mail_subject = Amapress::getOption( 'adh-request-reply-mail-subject' );
		$mail_content = Amapress::getOption( 'adh-request-reply-mail-content' );

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, null, $this );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, null, $this );

		$current_user = AmapressUser::getBy( amapress_current_user_id() );

		amapress_wp_mail( [ $this->getEmail() ], $mail_subject, $mail_content, [
			'Reply-To: ' . implode( ',', $current_user->getAllEmails() )
		] );
	}

	public function getFormattedReplyMail() {
		$mail_subject = Amapress::getOption( 'adh-request-reply-mail-subject' );
		$mail_content = Amapress::getOption( 'adh-request-reply-mail-content' );

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, null, $this );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, null, $this );

		return sprintf( __( '<p>A envoyer à %s</p><p><strong>Sujet: %s</strong></p><br/>%s', 'amapress' ),
			$this->getEmail(), $mail_subject, $mail_content
		);
	}
}
