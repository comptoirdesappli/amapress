<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressUser extends TitanUserEntity {
	const AMAP_ROLE = 'amps_amap_role_category';

	/** @var WP_Term[] */
	private $amap_roles = null;

	function __construct( $user_or_id ) {
		parent::__construct( $user_or_id );
	}

	private static $users_cache = array();

	private static $user_ids_with_roles = null;

	/**
	 * @param $user_or_id
	 *
	 * @return AmapressUser
	 */
	public static function getBy( $user_or_id, $no_cache = false ) {
		if ( is_a( $user_or_id, 'WP_User' ) ) {
			$user_id = $user_or_id->ID;
			if ( ! isset( self::$users_cache[ $user_id ] ) ) {
				self::$users_cache[ $user_id ] = new AmapressUser( $user_or_id );
			}
		} else if ( is_a( $user_or_id, 'AmapressUser' ) ) {
			$user_id = $user_or_id->ID;
			if ( ! isset( self::$users_cache[ $user_id ] ) ) {
				self::$users_cache[ $user_id ] = $user_or_id;
			}
		} else {
			$user_id = intval( $user_or_id );
			if ( $user_id <= 0 ) {
				return null;
			}
		}
		if ( ! isset( self::$users_cache[ $user_id ] ) || $no_cache ) {
			self::$users_cache[ $user_id ] = new AmapressUser( $user_id );
		}

		return self::$users_cache[ $user_id ];
	}

	//TODO gérer l'enregistrement de AMAP_ROLES avant le premier appel wp_set_current_user
	private $amap_roles_errored = false;

	private function ensure_amap_roles() {
		if ( null !== $this->amap_roles && ! $this->amap_roles_errored ) {
			return;
		}

		if ( null === self::$user_ids_with_roles ) {
			global $wpdb;
			self::$user_ids_with_roles = $wpdb->get_col( "SELECT DISTINCT tr.object_id
FROM $wpdb->term_taxonomy AS tt
INNER JOIN $wpdb->term_relationships AS tr
ON tr.term_taxonomy_id = tt.term_taxonomy_id
WHERE tt.taxonomy = 'amps_amap_role_category'" );
		}
		if ( ! in_array( $this->getID(), self::$user_ids_with_roles ) ) {
			$this->amap_roles         = [];
			$this->amap_roles_errored = false;

			return;
		}

		$res = wp_get_object_terms( $this->getID(), self::AMAP_ROLE,
			array( 'fields' => 'all', 'orderby' => 'term_id' ) );
		if ( ! is_wp_error( $res ) ) {
			$this->amap_roles         = $res;
			$this->amap_roles_errored = false;
		} else {
			$this->amap_roles         = [];
			$this->amap_roles_errored = true;
		}
	}

	public function getAmapRoleCapabilities() {
		$this->ensure_amap_roles();

		$ret = array();
		foreach ( $this->amap_roles as $term ) {
			$cap = get_term_meta( $term->term_id, 'amapress_caps', true );
			if ( ! empty( $cap ) ) {
				$ret = array_merge( $ret, explode( ',', $cap ) );
			}
		}

		return $ret;
	}

	private $user_roles = null;

	public function getAmapRoles() {
		$this->ensure_init();
		$this->ensure_amap_roles();

		if ( null === $this->user_roles ) {
			$this_user_roles = array();
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
			$lieu_ids = Amapress::get_lieu_ids();

			//référent producteur
			foreach ( AmapressContrats::get_contrats() as $contrat ) {
				$prod = $contrat->getProducteur();
				if ( ! $prod ) {
					continue;
				}
				$had_local_referents = false;
				foreach ( $lieu_ids as $lieu_id ) {
					if ( ! in_array( $this->ID, $contrat->getReferentsIds( $lieu_id ) ) ) {
						continue;
					}
					$owner_id                                      = in_array( $this->ID, $prod->getReferentsIds( $lieu_id ) ) ? $prod->ID : $contrat->ID;
					$had_local_referents                           = true;
					$this_user_roles[ 'ref_prod_' . $contrat->ID ] =
						array(
							'title'      => sprintf( 'Référent %s', $contrat->getTitle() ),
							'type'       => 'referent_producteur',
							'lieu'       => $lieu_id,
							'object_id'  => $contrat->ID,
							'edit_link'  => admin_url( "post.php?post={$owner_id}&action=edit" ),
							'other_link' => admin_url( "users.php?amapress_role=referent_producteur" ),
						);
				}
				if ( ! $had_local_referents ) {
					if ( ! in_array( $this->ID, $contrat->getReferentsIds() ) ) {
						continue;
					}
					$owner_id                                      = in_array( $this->ID, $prod->getReferentsIds() ) ? $prod->ID : $contrat->ID;
					$this_user_roles[ 'ref_prod_' . $contrat->ID ] =
						array(
							'title'      => sprintf( 'Référent %s', $contrat->getTitle() ),
							'type'       => 'referent_producteur',
							'lieu'       => null,
							'object_id'  => $contrat->ID,
							'edit_link'  => admin_url( "post.php?post={$owner_id}&action=edit" ),
							'other_link' => admin_url( "users.php?amapress_role=referent_producteur" ),
						);
				}
			}

			//référent lieu
			foreach ( $lieu_ids as $lieu_id ) {
				$lieu = AmapressLieu_distribution::getBy( $lieu_id );
				if ( ! $lieu->getReferentId() ) {
					continue;
				}
				if ( $lieu->getReferentId() != $this->ID ) {
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
						if ( $prod->getUserId() == $this->ID ) {
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
				} else if ( 'tresorier' == $r ) {
					$this_user_roles[ 'role_' . $r ] =
						array(
							'title'      => 'Trésorier',
							'type'       => 'tresorier',
							'lieu'       => null,
							'object_id'  => $this->ID,
							'edit_link'  => admin_url( "user-edit.php?user_id={$this->ID}" ),
							'other_link' => admin_url( "users.php?role={$r}" ),
						);
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
			$this->user_roles = $this_user_roles;
		}


		return $this->user_roles;
	}

	public
	function getAmapRolesString() {
		return implode( ', ', array_unique( array_map(
			function ( $role ) {
//            return '<a href="'.esc_attr($role['edit_link']).'">'.esc_html($role['title']).'</a>';
				return $role['title'];
			}, $this->getAmapRoles() ) ) );
	}

	public
	function getFormattedAdresse() {
		$cp = $this->getCode_postal();
		$v  = $this->getVille();
		if ( ! empty( $v ) ) {
			return preg_replace( '/(?:\s+-\s+)(\d{5})\s+([^,]+),\s*\1\s+\2/', ', $1 $2',
				sprintf( '%s, %s %s', $this->getAdresse(), $cp, $v ) );
		} else {
			return $this->getAdresse();
		}
	}

	public
	function getFormattedAdresseHtml() {
		$cp = $this->getCode_postal();
		$v  = $this->getVille();
		if ( ! empty( $v ) ) {
			return preg_replace( '/(?:\s+-\s+)(\d{5})\s+([^,]+)\<br\/\>\s*\1\s+\2/', ', $1 $2',
				sprintf( '%s<br/>%s %s', $this->getAdresse(), $cp, $v ) );
		} else {
			return $this->getAdresse();
		}
	}

	public
	function getAvatar() {
		return get_avatar( $this->ID );
	}

	public
	function getUserLatitude() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_lat'] ) ? $this->custom['amapress_user_lat'] : 0;
	}

	public
	function getUserLongitude() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_long'] ) ? $this->custom['amapress_user_long'] : 0;
	}

	public
	function getAdresse() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_adresse'] ) ? $this->custom['amapress_user_adresse'] : '';
	}

	public
	function getCode_postal() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_code_postal'] ) ? $this->custom['amapress_user_code_postal'] : '';
	}

	public
	function getVille() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_ville'] ) ? $this->custom['amapress_user_ville'] : '';
	}

	public
	function getCommentEmargement() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_comment_emargement'] ) ? $this->custom['amapress_user_comment_emargement'] : '';
	}

	public
	function isAdresse_localized() {
		$this->ensure_init();

		return ! empty( $this->custom['amapress_user_location_type'] );
	}

	public
	function getTelephone() {
		$this->ensure_init();
		if ( empty( $this->custom['amapress_user_telephone'] ) ) {
			return '';
		}

		return $this->custom['amapress_user_telephone'];
	}

	public function getPhoneNumbers(
		$mobile = 'both'
	) {
		$tel = $this->getTelephone() . ' ' . $this->getTelephone2();
		if ( empty( $tel ) ) {
			return [];
		}
		$tel     = preg_replace( '/\+33/', '0', $tel );
		$tel     = preg_replace( '/\D+/', '', $tel );
		$matches = array();
		$ret     = array();
		preg_match_all( '/\d{10}/', $tel, $matches, PREG_SET_ORDER );
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
			if ( isset( $ret[ $tel_norm ] ) ) {
				continue;
			}
			$ret[ $tel_norm ] = $m[0];
		}

		return $ret;
	}

	public function getTelTo(
		$mobile = 'both', $sms = false, $first_only = false, $separator = '<br/>'
	) {
		$phone_numbers = $this->getPhoneNumbers( $mobile );
		if ( empty( $phone_numbers ) ) {
			return '';
		}
		$ret = array();
		foreach ( $phone_numbers as $tel_norm => $tel_display ) {
			$ret[] = '<a href="' . ( $sms ? 'sms' : 'tel' ) . ':' . $tel_norm . '">' . esc_html( $tel_display ) . '</a>';
		}
		if ( $first_only ) {
			$ret = [ array_shift( $ret ) ];
		}

		return implode( $separator, $ret );
	}

	public
	function getTelephone2() {
		$this->ensure_init();
		if ( empty( $this->custom['amapress_user_telephone2'] ) ) {
			return '';
		}

		return $this->custom['amapress_user_telephone2'];
	}

	public
	function getCoAdherents() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_co-adherents'] ) ? $this->custom['amapress_user_co-adherents'] : '';
	}

	public
	function getCoAdherentsInfos() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_co-adherents-infos'] ) ? trim( $this->custom['amapress_user_co-adherents-infos'] ) : '';
	}

	public
	function getMoyenDisplay() {
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

	public
	function getMoyen() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_moyen'] ) ? $this->custom['amapress_user_moyen'] : 'mail';
	}

	public
	function getDisplayNameWithAdminEditLink() {
		return Amapress::makeLink( $this->getEditLink(), $this->getDisplayName(), true, true );
	}

	public
	function getDisplayName() {
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

	public
	function getSortableDisplayName() {
		$this->ensure_init();
		if ( ! empty( $this->getUser()->last_name ) ) {
			$dn = sprintf( '%s %s', $this->getUser()->last_name, $this->getUser()->first_name );
		}
		if ( empty( $dn ) ) {
			$dn = $this->getUser()->user_login;
		}

		return $dn;
	}

	public
	function resolveAddress() {
		return AmapressUsers::resolveUserAddress( $this->getID(),
			$this->getFormattedAdresse() );
	}

	public
	function getCoAdherent1Id() {
		$this->ensure_init();

		$v = intval( isset( $this->custom['amapress_user_co-adherent-1'] ) ? $this->custom['amapress_user_co-adherent-1'] : null );
		if ( empty( $v ) ) {
			return null;
		}

		return $v;
	}

	public
	function getCoAdherent2Id() {
		$this->ensure_init();

		$v = intval( isset( $this->custom['amapress_user_co-adherent-2'] ) ? $this->custom['amapress_user_co-adherent-2'] : null );
		if ( empty( $v ) ) {
			return null;
		}

		return $v;
	}

	public
	function getCoAdherent3Id() {
		$this->ensure_init();

		$v = intval( isset( $this->custom['amapress_user_co-adherent-3'] ) ? $this->custom['amapress_user_co-adherent-3'] : null );
		if ( empty( $v ) ) {
			return null;
		}

		return $v;
	}

	public
	function getFirstCoAdherent() {
		$co_adh = $this->getCoAdherent1();
		if ( ! $co_adh ) {
			$co_adh = $this->getCoAdherent2();
		}
		if ( ! $co_adh ) {
			$co_adh = $this->getCoAdherent3();
		}

		return $co_adh;
	}


	private
		$adherent1 = null;

	public
	function getCoAdherent1() {
		if ( $this->adherent1 == null ) {
			$this->adherent1 = AmapressUser::getBy( $this->getCoAdherent1Id() );
		}

		return $this->adherent1;
	}

	private
		$adherent2 = null;

	public
	function getCoAdherent2() {
		if ( $this->adherent2 == null ) {
			$this->adherent2 = AmapressUser::getBy( $this->getCoAdherent2Id() );
		}

		return $this->adherent2;
	}

	private
		$adherent3 = null;

	public
	function getCoAdherent3() {
		if ( $this->adherent3 == null ) {
			$this->adherent3 = AmapressUser::getBy( $this->getCoAdherent3Id() );
		}

		return $this->adherent3;
	}

	private $principal_user_ids = null;

	private static $coadherents = null;

	private static function ensureInitCoadherents() {
		if ( null === self::$coadherents ) {
			global $wpdb;
			self::$coadherents = array_group_by(
				$wpdb->get_results(
					"SELECT DISTINCT $wpdb->usermeta.meta_value, $wpdb->usermeta.user_id
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3')" ),
				function ( $o ) {
					return intval( $o->meta_value );
				} );
		}
	}

	public function addCoadherent( $coadhrent_id ) {
		$this->ensure_init();

		if ( empty( $coadhrent_id ) ) {
			return false;
		}

		foreach ( [ '1', '2', '3' ] as $id ) {
			if ( ! empty( $this->custom[ 'amapress_user_co-adherent-' . $id ] ) ) {
				if ( $this->custom[ 'amapress_user_co-adherent-' . $id ] == $coadhrent_id ) {
					return true;
				}
			}
		}

		foreach ( [ '1', '2', '3' ] as $id ) {
			if ( empty( $this->custom[ 'amapress_user_co-adherent-' . $id ] ) ) {
				$this->custom[ 'amapress_user_co-adherent-' . $id ] = $coadhrent_id;
				update_user_meta( $this->ID, 'amapress_user_co-adherent-' . $id, $coadhrent_id );
				self::$coadherents = null;
				$this->adh_type    = null;

				return true;
			}
		}

		return false;
	}

	public function removeCoadherent( $coadhrent_id ) {
		$this->ensure_init();

		if ( empty( $coadhrent_id ) ) {
			return false;
		}

		foreach ( [ '1', '2', '3' ] as $id ) {
			if ( ! empty( $this->custom[ 'amapress_user_co-adherent-' . $id ] ) ) {
				if ( $this->custom[ 'amapress_user_co-adherent-' . $id ] == $coadhrent_id ) {
					$this->custom[ 'amapress_user_co-adherent-' . $id ] = $coadhrent_id;
					delete_user_meta( $this->ID, 'amapress_user_co-adherent-' . $id );
					self::$coadherents = null;
					$this->adh_type    = null;

					return true;
				}
			}
		}

		return false;
	}

	public function getPrincipalUserIds() {
		$this->ensureInitCoadherents();
		if ( null === $this->principal_user_ids ) {
			$this->principal_user_ids = [];
			if ( isset( self::$coadherents[ $this->getID() ] ) ) {
				foreach ( self::$coadherents[ $this->getID() ] as $o ) {
					if ( ! $o->user_id || in_array( $o->user_id, $this->principal_user_ids ) ) {
						continue;
					}

					$this->principal_user_ids[] = $o->user_id;
				}
			}
		}

		return $this->principal_user_ids;
	}

	public
	function getEmail() {
		return $this->getUser()->user_email;
	}

	public
	function getDisplay(
		$args = array()
	) {
		$args = wp_parse_args( $args, array(
			'show_avatar'     => 'default',
			'show_email'      => 'default',
			'show_tel'        => 'default',
			'show_tel_fixe'   => 'default',
			'show_tel_mobile' => 'default',
			'show_sms'        => 'default',
			'show_adresse'    => 'default',
			'show_roles'      => 'default',
		) );
		$ret  = '';
		$ret  .= '<div class="user-profile-info">';
		$ret  .= ( amapress_check_info_visibility( $args['show_avatar'], 'avatar', $this ) == true ?
			$this->wrapIfNotEmpty( '<div class="user-photo">', $this->getAvatar(), '</div>' ) :
			'' );
		if ( amapress_can_access_admin() ) {
			$ret .= '<div class="user-name"><a href="' . admin_url( 'user-edit.php?user_id=' . $this->ID ) . '">' . esc_html( $this->getDisplayName() ) . '</a></div>';
		} else {
			$ret .= '<div class="user-name">' . esc_html( $this->getDisplayName() ) . '</div>';
		}
		$ret             .= ( amapress_check_info_visibility( $args['show_email'], 'email', $this ) == true ?
			$this->wrapIfNotEmpty( '<div class="user-email"><a href="mailto:', esc_attr( $this->getEmail() ) . '">', esc_html( $this->getEmail() ) . '</a></div>' ) :
			'' );
		$roles           = $this->getAmapRolesString();
		$ret             .= ( amapress_check_info_visibility( $args['show_roles'], 'roles', $this ) && ! empty( $roles ) ?
			$this->wrapIfNotEmpty( '<div class="user-roles">', esc_html( $roles ), '</div>' ) :
			'' );
		$ret             .= ( amapress_check_info_visibility( $args['show_tel'], 'tel', $this ) || amapress_check_info_visibility( $args['show_tel_fixe'], 'tel_fixe', $this ) ?
			$this->wrapIfNotEmpty( '<div class="user-tel-fixe">Fix: ', $this->getTelTo( false ), '</div>' ) :
			'' );
		$show_tel_mobile = amapress_check_info_visibility( $args['show_tel'], 'tel', $this ) || amapress_check_info_visibility( $args['show_tel_mobile'], 'tel_mobile', $this );
		$show_sms        = amapress_check_info_visibility( $args['show_sms'], 'tel', $this );
		$ret             .= ( $show_tel_mobile || $show_sms ?
			$this->wrapIfNotEmpty(
				'<div class="user-tel-mobile">Mob: ', $this->getTelTo( true, $show_sms ), '</div>' ) :
			'' );
//		$ret   .= ( $show_sms ?
//			$this->wrapIfNotEmpty(
//				'<div class="user-sms">SMS: ', $this->getTelTo( true, true ), '</div>' ) :
//			'' );
		$ret .= ( amapress_check_info_visibility( $args['show_adresse'], 'adresse', $this ) ?
			$this->wrapIfNotEmpty( '<div class="user-adresse">', $this->getFormattedAdresseHtml(), '</div>' ) :
			'' );
		$ret .= '</div>';

		return $ret;
	}

	private
	function wrapIfNotEmpty(
		$start_tags, $html, $end_tags
	) {
		if ( empty( $html ) ) {
			return '';
		}

		return $start_tags . $html . $end_tags;
	}

	public
	function getDisplayRight(
		$name
	) {
		return isset( $this->custom["allow_show_$name"] ) ? $this->custom["allow_show_$name"] : null;
	}

	public
	function getAllEmails() {
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
		$ret = array_filter( $ret, function ( $email ) {
			return false === strpos( $email, '@nomail.org' );
		} );

		return array_unique( $ret );
	}

	public function getAllEmailsWithCoAdherents() {
		$user_ids = AmapressContrats::get_related_users( $this->ID );
		$ret      = [];
		foreach ( $user_ids as $user_id ) {
			$amapien = AmapressUser::getBy( $user_id );
			if ( $amapien ) {
				$ret = array_merge( $ret, $amapien->getAllEmails() );
			}
		}

		return array_unique( $ret );
	}

	public
	function isIntermittent() {
		$this->ensure_init();

		return isset( $this->custom['amapress_user_intermittent'] ) ? Amapress::toBool( $this->custom['amapress_user_intermittent'] ) : false;
	}


	public
	function inscriptionIntermittence(
		$send_mail = true
	) {
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
				$this->ID, null, [], null, null,
				AmapressIntermittence_panier::getResponsableIntermittentsReplyto( null ) );
		}

		return true;
	}

//	public
//	function getDesinscriptionIntermittenceLink() {
//		$admin_post_url = admin_url( 'admin-post.php' );
//		$my_email       = $this->getEmail();
//		$key            = $this->getUserLoginKey();
//
//		return "$admin_post_url?action=desinscription_intermittent&email=$my_email&key=$key";
//	}

	public
	function desinscriptionIntermittence() {
		if ( ! $this->isIntermittent() ) {
			return false;
		}

		if ( delete_user_meta( $this->ID, 'amapress_user_intermittent' ) ) {
			amapress_mail_to_current_user(
				Amapress::getOption( 'intermittence-desincr-mail-subject' ),
				Amapress::getOption( 'intermittence-desincr-mail-content' ),
				$this->ID, null, [], null, null,
				AmapressIntermittence_panier::getResponsableIntermittentsReplyto( null ) );
		}
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret = [
				'lien_intermittence'               => [
					'desc' => 'Lien vers la page des paniers intermittents disponibles',
					'func' => function ( AmapressUser $amapien ) {
						$url = get_permalink( intval( Amapress::getOption( 'paniers-intermittents-page' ) ) );

						return Amapress::makeLink( $url );
					}
				],
				'lien_paniers_intermittence'       => [
					'desc' => 'Lien vers la page des paniers intermittents disponibles',
					'func' => function ( AmapressUser $amapien ) {
						$url = get_permalink( intval( Amapress::getOption( 'paniers-intermittents-page' ) ) );

						return Amapress::makeLink( $url );
					}
				],
				'lien_desinscription_intermittent' => [
					'desc' => 'Lien de désinscription de la liste des intermittents',
					'func' => function ( AmapressUser $amapien ) {
						return Amapress::makeLink( amapress_intermittence_desinscription_link() );//Amapress::makeLink( $this->getDesinscriptionIntermittenceLink() );
					}
				],
			];

			self::$properties = $ret;
		}

		return self::$properties;
	}

	public function getProperty( $name ) {
		$this->ensure_init();
		$props = static::getProperties();
		if ( isset( $props[ $name ] ) ) {
			return call_user_func( $props[ $name ]['func'], $this );
		}

		return "##UNKNOW:$name##";
	}

	public
	function getUserLoginKey() {
		$key = get_user_meta( $this->ID, 'amapress_user_key', true );
		if ( empty( $key ) ) {
			$key = md5( uniqid() );
			update_user_meta( $this->ID, 'amapress_user_key', $key );
		}

		return $key;
	}

	public
	function addUserLoginKey(
		$url
	) {
		return add_query_arg( 'key', $this->getUserLoginKey(), $url );
	}

	public
	static function logUserByLoginKey(
		$key
	) {

		global $wpdb;
		$user_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'amapress_user_key' AND meta_value = %s", $key
		) );
		$user    = get_user_by( 'ID', $user_id );
		wp_set_current_user( $user_id, $user->user_login );
		wp_set_auth_cookie( $user_id );
		do_action( 'wp_login', $user->user_login, $user );
	}

	public function getEditLink() {
		return admin_url( 'user-edit.php?user_id=' . $this->getUser()->ID );
	}

	public function getContacts() {
		$mailto = Amapress::makeLink( 'mailto:' . implode( ',', $this->getAllEmails() ), 'Joindre par mail' );
		$telto  = $this->getTelTo( 'both', false, false, ', ' );
		$smsto  = $this->getTelTo( true, true, false, ', ' );

		return $mailto .
		       ( ! empty( $telto ) ? ' / Par téléphone : ' . $telto : '' ) .
		       ( ! empty( $smsto ) ? ' / Par SMS : ' . $smsto : '' );
	}

	public static function getEmailsForAmapRole( $role_id, $lieu_id = null ) {
		$term = get_term( $role_id, AmapressUser::AMAP_ROLE );
		if ( empty( $term ) || is_wp_error( $term ) ) {
			return null;
		}
		$users  = get_users( AmapressUser::AMAP_ROLE . '=' . $term->slug . ( $lieu_id ? '&amapress_lieu=' . $lieu_id : '' ) );
		$emails = [];
		foreach ( $users as $user ) {
			$emails = array_merge( $emails, AmapressUser::getBy( $user )->getAllEmails() );
		}

		return $emails;
	}

	public
	function getAllDirectlyLinkedCoAdherents() {
		$ret    = [];
		$co_adh = $this->getCoAdherent1();
		if ( $co_adh ) {
			$ret[] = $co_adh;
		}
		$co_adh = $this->getCoAdherent2();
		if ( $co_adh ) {
			$ret[] = $co_adh;
		}
		$co_adh = $this->getCoAdherent3();
		if ( $co_adh ) {
			$ret[] = $co_adh;
		}

		return $ret;
	}

	private $adh_type = null;

	public function getAdherentType() {
		if ( empty( $this->adh_type ) ) {
			$users_ids                 = AmapressContrats::get_related_users( $this->ID );
			$others_linked_users_count = 0;
			$this_linked_users_count   = 0;
			foreach ( $users_ids as $user_id ) {
				$user         = AmapressUser::getBy( $user_id );
				$linked_users = $user->getAllDirectlyLinkedCoAdherents();
				if ( $user->ID == $this->ID ) {
					$this_linked_users_count += ( ! empty( $linked_users ) ? 1 : 0 );
				} else {
					$others_linked_users_count += ( ! empty( $linked_users ) ? 1 : 0 );
				}
			}

			if ( 0 == $this_linked_users_count && 0 == $others_linked_users_count ) {
				$this->adh_type = 'alone';
			} else if ( 0 <= $this_linked_users_count && 0 == $others_linked_users_count ) {
				$this->adh_type = 'main';
			} else if ( 0 == $this_linked_users_count && 0 <= $others_linked_users_count ) {
				$this->adh_type = 'co';
			} else {
				Amapress::setFilterForReferent( false );
				$adhs = AmapressAdhesion::getUserActiveAdhesions( $this->ID, null, null, false, false );
				Amapress::setFilterForReferent( true );
				$adh_user_ids = [];
				foreach ( $adhs as $adh ) {
					$adh_user_ids[] = $adh->getAdherentId();
				}
				$adh_user_ids = array_unique( $adh_user_ids );
				if ( 1 == count( $adh_user_ids ) && in_array( $this->ID, $adh_user_ids ) ) {
					$this->adh_type = 'main';
				} else {
					$this->adh_type = 'mix';
				}
			}
		}

		return $this->adh_type;
	}

	public function getAdherentTypeDisplay() {
		switch ( $this->getAdherentType() ) {
			case 'alone':
				return 'Adhérent principal (sans co-adhérent)';
			case 'main':
				return 'Adhérent principal (avec co-adhérent)';
			case 'co':
				return 'Co-adhérent';
			case 'mix':
				return 'Principal et co-adhérent';
			default:
				return 'Inconnu';
		}
	}

	public function isPrincipalAdherent() {
		$adh_type = $this->getAdherentType();

		return 'alone' == $adh_type || 'main' == $adh_type;
	}

	public function isCoAdherent() {
		$adh_type = $this->getAdherentType();

		return 'co' == $adh_type;
	}

	public function getCoAdherentsList( $with_contacts = false, $include_me = false ) {
		$users = AmapressContrats::get_related_users( $this->ID );
		$res   = [];
		foreach ( $users as $user_id ) {
			if ( ! $include_me && $user_id == $this->ID ) {
				continue;
			}

			$amapien = AmapressUser::getBy( $user_id );
			if ( $with_contacts ) {
				$res[] = $amapien->getDisplayName() . ' (' . $amapien->getContacts() . ')';
			} else {
				$res[] = $amapien->getDisplayName();
			}
		}

		if ( empty( $res ) ) {
			return 'Aucun';
		} else {
			return implode( ', ', $res );
		}
	}

	public function getPrincipalAdherentList( $with_contacts = false, $include_me = false ) {
		$users = $this->getPrincipalUserIds();
		$res   = [];
		foreach ( $users as $user_id ) {
			if ( ! $include_me && $user_id == $this->ID ) {
				continue;
			}

			$amapien = AmapressUser::getBy( $user_id );
			if ( $with_contacts ) {
				$res[] = $amapien->getDisplayName() . ' (' . $amapien->getContacts() . ')';
			} else {
				$res[] = $amapien->getDisplayName();
			}
		}

		if ( empty( $res ) ) {
			return 'Aucun';
		} else {
			return implode( ', ', $res );
		}
	}
}
