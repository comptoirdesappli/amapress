<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressUser extends TitanUserEntity {
	const AMAP_ROLE = 'amps_amap_role_category';

	/** @var WP_Term[] */
	private $amap_roles = array();

	function __construct( $user_or_id ) {
		parent::__construct( $user_or_id );
	}

	private static $users_cache = array();

	/**
	 * @param $user_or_id
	 *
	 * @return AmapressUser
	 */
	public static function getBy( $user_or_id ) {
		if ( is_a( $user_or_id, 'WP_User' ) ) {
			$user_id = $user_or_id->ID;
		} else if ( is_a( $user_or_id, 'AmapressUser' ) ) {
			$user_id = $user_or_id->ID;
		} else {
			$user_id = intval( $user_or_id );
		}
		if ( ! isset( self::$users_cache[ $user_id ] ) ) {
			$user = get_user_by( 'ID', $user_id );
			if ( ! $user ) {
				self::$users_cache[ $user_id ] = null;
			} else {
				self::$users_cache[ $user_id ] = new AmapressUser( $user );
			}
		}

		return self::$users_cache[ $user_id ];
	}

	private function ensure_amap_roles() {
		if ( ! empty( $this->amap_roles ) ) {
			return;
		}

		$res = wp_get_post_terms( $this->ID, self::AMAP_ROLE, array( 'fields' => 'all' ) );
		if ( ! is_wp_error( $res ) ) {
			$this->amap_roles = $res;
		}
	}

	public function getAmapRoleCapabilities() {
		$this->ensure_amap_roles();
		$ret = array();
		if ( ! empty( $this->amap_roles ) ) {
			foreach ( $this->amap_roles as $term ) {
				$cap = get_term_meta( $term->term_id, 'amapress_caps', true );
				if ( ! empty( $cap ) ) {
					$ret = array_merge( $ret, explode( ',', $cap ) );
				}
			}
		}

		return $ret;
	}

	public function getAmapRoles() {
		$this->ensure_init();
		$this->ensure_amap_roles();

//        $args = wp_parse_args(
//            $args,
//            array(
//
//            )
//        );

		$this_user_roles = array();
		if ( ! empty( $this->amap_roles ) ) {
			foreach ( $this->amap_roles as $amap_role ) {
				$this_user_roles["amap_role_{$amap_role->term_id}"] =
					array(
						'title'      => $amap_role->name,
						'type'       => 'amap_role',
						'lieu'       => null,
						'object_id'  => $this->ID,
						'edit_link'  => admin_url( "user-edit.php?user_id={$this->ID}" ),
						'other_link' => admin_url( "users.php?{$amap_role->taxonomy}={$amap_role->slug}" ),
					);
			}
		}
		$lieu_ids = Amapress::get_lieu_ids();

		//référent producteur
		foreach ( AmapressContrats::get_contrats() as $contrat ) {
			$prod                = $contrat->getProducteur();
			$had_local_referents = false;
			foreach ( $lieu_ids as $lieu_id ) {
				if ( $prod->getReferent( $lieu_id ) == null ) {
					continue;
				}
				if ( $prod->getReferent( $lieu_id )->ID != $this->ID ) {
					continue;
				}
				$had_local_referents                           = true;
				$this_user_roles[ 'ref_prod_' . $contrat->ID ] =
					array(
						'title'      => sprintf( 'Référent %s', $contrat->getTitle() ),
						'type'       => 'referent_producteur',
						'lieu'       => $lieu_id,
						'object_id'  => $contrat->ID,
						'edit_link'  => admin_url( "post.php?post={$prod->ID}&action=edit" ),
						'other_link' => admin_url( "users.php?amapress_role=referent_producteur" ),
					);
			}
			if ( ! $had_local_referents ) {
				if ( $prod->getReferent() == null ) {
					continue;
				}
				if ( $prod->getReferent()->ID != $this->ID ) {
					continue;
				}
				$this_user_roles[ 'ref_prod_' . $contrat->ID ] =
					array(
						'title'      => sprintf( 'Référent %s', $contrat->getTitle() ),
						'type'       => 'referent_producteur',
						'lieu'       => null,
						'object_id'  => $contrat->ID,
						'edit_link'  => admin_url( "post.php?post={$prod->ID}&action=edit" ),
						'other_link' => admin_url( "users.php?amapress_role=referent_producteur" ),
					);
			}
		}

		//référent lieu
		foreach ( $lieu_ids as $lieu_id ) {
			$lieu = new AmapressLieu_distribution( $lieu_id );
			if ( $lieu->getReferent() == null ) {
				continue;
			}
			if ( $lieu->getReferent()->ID != $this->ID ) {
				continue;
			}
			$this_user_roles[ 'ref_lieu_' . $lieu->ID ] =
				array(
					'title'      => sprintf( 'Référent %s', $lieu->getShortName() ),
					'type'       => 'referent_lieu',
					'lieu'       => $lieu_id,
					'object_id'  => $lieu->ID,
					'edit_link'  => admin_url( "post.php?post={$lieu->ID}&action=edit" ),
					'other_link' => admin_url( "users.php?amapress_role=referent_lieu" ),
				);
		}


//        if (count($this_user_roles) == 0) {
		global $wp_roles;
		foreach ( $this->getUser()->roles as $r ) {
			if ( $r == 'producteur' ) {
				foreach ( Amapress::get_producteurs() as $prod ) {
					if ( $prod->getUser() != null && $prod->getUser()->ID == $this->ID ) {
						$this_user_roles[ 'role_' . $r ] =
							array(
								'title'      => sprintf( 'Producteur - %s', $prod->getTitle() ),
								'type'       => 'producteur',
								'lieu'       => null,
								'object_id'  => $this->ID,
								'edit_link'  => admin_url( "post.php?post={$prod->ID}&action=edit" ),
								'other_link' => admin_url( 'edit.php?post_type=' . AmapressProducteur::INTERNAL_POST_TYPE ),
							);
					}
				}
			} else {
				$this_user_roles[ 'role_' . $r ] =
					array(
						'title'      => translate_user_role( $wp_roles->roles[ $r ]['name'] ),
						'type'       => 'wp_role',
						'lieu'       => null,
						'object_id'  => $this->ID,
						'role'       => $r,
						'edit_link'  => admin_url( "user-edit.php?user_id={$this->ID}" ),
						'other_link' => admin_url( "users.php?role={$r}" ),
					);
			}
		}
//        }

		if ( $this->isIntermittent() ) {
			$this_user_roles["intermittent"] =
				array(
					'title'      => 'Intermittent',
					'type'       => 'intermittent',
					'lieu'       => null,
					'object_id'  => $this->ID,
					'edit_link'  => admin_url( "user-edit.php?user_id={$this->ID}" ),
					'other_link' => admin_url( "users.php?amapress_contrat=intermittent" ),
				);
		}

		return $this_user_roles;
	}

	public function getAmapRolesString() {
		return implode( ', ', array_unique( array_map(
			function ( $role ) {
//            return '<a href="'.esc_attr($role['edit_link']).'">'.esc_html($role['title']).'</a>';
				return $role['title'];
			}, $this->getAmapRoles() ) ) );
	}

	public function getFormattedAdresse() {
		$cp = $this->getCode_postal();
		$v  = $this->getVille();
		if ( ! empty( $v ) ) {
			return sprintf( '%s, %s %s', $this->getAdresse(), $cp, $v );
		} else {
			return $this->getAdresse();
		}
	}

	public function getFormattedAdresseHtml() {
		$cp = $this->getCode_postal();
		$v  = $this->getVille();
		if ( ! empty( $v ) ) {
			return sprintf( '%s<br/>%s %s', $this->getAdresse(), $cp, $v );
		} else {
			return $this->getAdresse();
		}
	}

	public function getAvatar() {
		return get_avatar( $this->ID );
	}

	public function getUserLatitude() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_lat'] ) ? $this->custom['amapress_user_lat'] : 0;
	}

	public function getUserLongitude() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_long'] ) ? $this->custom['amapress_user_long'] : 0;
	}

	public function getAdresse() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_adresse'] ) ? $this->custom['amapress_user_adresse'] : '';
	}

	public function getCode_postal() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_code_postal'] ) ? $this->custom['amapress_user_code_postal'] : '';
	}

	public function getVille() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_ville'] ) ? $this->custom['amapress_user_ville'] : '';
	}

	public function isAdresse_localized() {
		$this->ensure_init();

		return ! empty( $this->custom['amapress_user_location_type'] );
	}

	public function getTelephone() {
		$this->ensure_init();
		if ( empty( $this->custom['amapress_user_telephone'] ) ) {
			return '';
		}

		return $this->custom['amapress_user_telephone'];
	}

	public function getTelTo( $mobile = 'both' ) {
		$tel = $this->getTelephone() . ' ' . $this->getTelephone2();
		if ( empty( $tel ) ) {
			return '';
		}
		$matches = array();
		$ret     = array();
		$used    = array();
		preg_match_all( '/(?:\d\s*){10}/', $tel, $matches, PREG_SET_ORDER );
		foreach ( $matches as $m ) {
			if ( is_bool( $mobile ) ) {
				if ( $mobile && ! preg_match( '/^(?:06|07)/', $m[0] ) ) {
					continue;
				}
				if ( ! $mobile && preg_match( '/^(?:06|07)/', $m[0] ) ) {
					continue;
				}
			}
			$tel_norm = str_replace( ' ', '', $m[0] );
			if ( in_array( $tel_norm, $used ) ) {
				continue;
			}
			$used[] = $tel_norm;
			$ret[]  = '<a href="tel:' . $tel_norm . '">' . esc_html( $m[0] ) . '</a>';
		}

		return implode( '<br/>', $ret );
	}

	public function getTelephone2() {
		$this->ensure_init();
		if ( empty( $this->custom['amapress_user_telephone2'] ) ) {
			return '';
		}

		return $this->custom['amapress_user_telephone2'];
	}

	public function getCoAdherents() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_co-adherents'] ) ? $this->custom['amapress_user_co-adherents'] : '';
	}

	public function getMoyenDisplay() {
		$this->ensure_init();
		$m = isset( $this->custom['amapress_user_moyen'] ) ? $this->custom['amapress_user_moyen'] : null;
		switch ( $m ) {

			case 'mail':
				return 'Email';
			case 'tel':
				return 'Téléphone';
			default:
				return $m;
		}
	}

	public function getMoyen() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_moyen'] ) ? $this->custom['amapress_user_moyen'] : 'mail';
	}

	public function getDisplayName() {
		$this->ensure_init();
		$dn = $this->getUser()->display_name;
		if ( ! empty( $this->getUser()->last_name ) ) {
			$dn = sprintf( '%s %s', $this->getUser()->first_name, $this->getUser()->last_name );
		}
		if ( empty( $dn ) ) {
			$dn = $this->getUser()->user_login;
		}

		return $dn;
	}

	public function resolveAddress() {
		AmapressUsers::resolveUserAddress( $this->getID(),
			$this->getFormattedAdresse() );
	}

	private $adherent1 = null;

	public function getCoAdherent1() {
		$this->ensure_init();
		$v = intval( isset( $this->custom['amapress_user_co-adherent-1'] ) ? $this->custom['amapress_user_co-adherent-1'] : null );
		if ( empty( $v ) ) {
			return null;
		}
		if ( $this->adherent1 == null ) {
			$this->adherent1 = AmapressUser::getBy( $v );
		}

		return $this->adherent1;
	}

	private $adherent2 = null;

	public function getCoAdherent2() {
		$this->ensure_init();
		$v = intval( isset( $this->custom['amapress_user_co-adherent-2'] ) ? $this->custom['amapress_user_co-adherent-2'] : null );
		if ( empty( $v ) ) {
			return null;
		}
		if ( $this->adherent2 == null ) {
			$this->adherent2 = AmapressUser::getBy( $v );
		}

		return $this->adherent2;
	}

	public function getPrincipalUserIds() {
		$ret = array_map( function ( $u ) {
			return $u->ID;
		},
			get_users(
				array(
					'meta_query' => array(
						array(
							'relation' => 'OR',
							array(
								'key'     => 'amapress_user_co-adherent-1',
								'value'   => $this->ID,
								'compare' => '=',
								'type'    => 'NUMERIC',
							),
							array(
								'key'     => 'amapress_user_co-adherent-2',
								'value'   => $this->ID,
								'compare' => '=',
								'type'    => 'NUMERIC',
							),
						),
					)
				)
			) );

		return $ret;
	}

	public function getEmail() {
		return $this->getUser()->user_email;
	}

	public function getDisplay( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'show_avatar'     => 'default',
			'show_email'      => 'default',
			'show_tel'        => 'default',
			'show_tel_fixe'   => 'default',
			'show_tel_mobile' => 'default',
			'show_adresse'    => 'default',
			'show_roles'      => 'true',
		) );
		$ret  = '<div class="user-profile-info">';
		$ret  .= ( amapress_check_info_visibility( $args['show_avatar'], 'avatar', $this ) == true ?
			$this->wrapIfNotEmpty( '<div class="user-photo">', $this->getAvatar(), '</div>' . "\r\n" ) :
			'' );
		if ( amapress_can_access_admin() ) {
			$ret .= '<p><a href="' . admin_url( 'user-edit.php?user_id=' . $this->ID ) . '">' . esc_html( $this->getDisplayName() ) . '</a></p>' . "\n";
		} else {
			$ret .= '<p>' . esc_html( $this->getDisplayName() ) . '</p>' . "\n";
		}
		$ret   .= ( amapress_check_info_visibility( $args['show_email'], 'email', $this ) == true ?
			$this->wrapIfNotEmpty( '<p><a href="mailto:', esc_attr( $this->getEmail() ) . '">', esc_html( $this->getEmail() ) . '</a></p>' . "\r\n" ) :
			'' );
		$roles = $this->getAmapRolesString();
		$ret   .= ( amapress_check_info_visibility( $args['show_roles'], 'roles', $this ) && ! empty( $roles ) ?
			$this->wrapIfNotEmpty( '<p class="user-roles">', esc_html( $roles ), '</p>' . "\r\n" ) :
			'' );
		$ret   .= ( amapress_check_info_visibility( $args['show_tel'], 'tel', $this ) || amapress_check_info_visibility( $args['show_tel_fixe'], 'tel_fixe', $this ) ?
			$this->wrapIfNotEmpty( '<p>', $this->getTelTo( false ), '</p>' . "\r\n" ) :
			'' );
		$ret   .= ( amapress_check_info_visibility( $args['show_tel'], 'tel', $this ) || amapress_check_info_visibility( $args['show_tel_mobile'], 'tel_mobile', $this ) ?
			$this->wrapIfNotEmpty( '<p>', $this->getTelTo( true ), '</p>' . "\r\n" ) :
			'' );
		$ret   .= ( amapress_check_info_visibility( $args['show_adresse'], 'adresse', $this ) ?
			$this->wrapIfNotEmpty( '<p>', $this->getFormattedAdresseHtml(), '</p>' ) :
			'' );
		$ret   .= '</div>';

		return $ret;
	}

	private function wrapIfNotEmpty( $start_tags, $html, $end_tags ) {
		if ( empty( $html ) ) {
			return '';
		}

		return $start_tags . $html . $end_tags;
	}

	public function getDisplayRight( $name ) {
		return isset( $this->custom["allow_show_$name"] ) ? $this->custom["allow_show_$name"] : null;
	}

	public function getAllEmails() {
		$ret   = array();
		$ret[] = $this->getUser()->user_email;
		if ( ! empty( $this->custom['email2'] ) ) {
			$ret[] = trim( $this->custom['email2'] );
		}
		if ( ! empty( $this->custom['email3'] ) ) {
			$ret[] = trim( $this->custom['email3'] );
		}
		if ( ! empty( $this->custom['email4'] ) ) {
			$ret[] = trim( $this->custom['email4'] );
		}

		return array_unique( $ret );
	}


	public function isIntermittent() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_intermittent'] ) ? Amapress::toBool( $this->custom['amapress_user_intermittent'] ) : false;
	}


	public function inscriptionIntermittence( $send_mail = true ) {
		if ( $this->isIntermittent() ) {
			return false;
		}

		$this->custom['amapress_user_intermittent']      = 1;
		$this->custom['amapress_user_intermittent_date'] = amapress_time();
		update_user_meta( $this->ID, 'amapress_user_intermittent', 1 );
		update_user_meta( $this->ID, 'amapress_user_intermittent_date', $this->custom['amapress_user_intermittent_date'] );

		if ( $send_mail ) {
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-mail-subject' ),
				Amapress::getOption( 'intermittence-mail-content' ),
				$this->ID );
		}

		return true;
	}

	public function getDesinscriptionIntermittenceLink() {
		$admin_post_url = admin_url( 'admin-post.php' );
		$my_email       = $this->getEmail();
		$key            = $this->getUserLoginKey();

		return "$admin_post_url?action=desinscription_intermittent&email=$my_email&key=$key";
	}

	public function desinscriptionIntermittence() {
		if ( ! $this->isIntermittent() ) {
			return false;
		}

		if ( delete_user_meta( $this->ID, 'amapress_user_intermittent' ) ) {
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-desincr-mail-subject' ),
				Amapress::getOption( 'intermittence-desincr-mail-content' ),
				$this->ID );
		}
	}

	public function getProperty( $name ) {
		if ( 'lien_intermittence' == $name || 'lien_paniers_intermittence' == $name ) {
			$url = get_permalink( intval( Amapress::getOption( 'paniers-intermittents-page' ) ) );

			return Amapress::makeLink( $url );
		}
		if ( 'lien_desinscription_intermittent' == $name ) {
			return Amapress::makeLink( $this->getDesinscriptionIntermittenceLink() );
		}
	}

	public function getUserLoginKey() {
		$key = get_user_meta( $this->ID, 'amapress_user_key', true );
		if ( empty( $key ) ) {
			$key = md5( uniqid() );
			update_user_meta( $this->ID, 'amapress_user_key', $key );
		}

		return $key;
	}

	public function addUserLoginKey( $url ) {
		return add_query_arg( 'key', $this->getUserLoginKey(), $url );
	}

	public static function logUserByLoginKey( $key ) {

		global $wpdb;
		$user_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'amapress_user_key' AND meta_value = %s", $key
		) );
		$user    = get_user_by( 'ID', $user_id );
		wp_set_current_user( $user_id, $user->user_login );
		wp_set_auth_cookie( $user_id );
		do_action( 'wp_login', $user->user_login, $user );
	}
}
