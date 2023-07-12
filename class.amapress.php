<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Amapress {
	const WPCF7_POST_TYPE = 'wpcf7_contact_form';
	const DATATABLES_EXPORT_EXCEL = 'excel';
	const DATATABLES_EXPORT_CSV = 'csv';
	const DATATABLES_EXPORT_PDF = 'pdf';
	const DATATABLES_EXPORT_PRINT = 'print';

	public static $initiated = false;
//    private static $vp = null;
	private static $titan = null;

	public static function getTitanInstance() {
		if ( null == self::$titan ) {
			self::$titan = TitanFramework::getInstance( 'amapress' );
		}

		return self::$titan;
	}

	public static function hasRespDistribRoles() {
		for ( $i = 1; $i <= 10; $i ++ ) {
			if ( ! empty( self::getOption( "resp_role_$i-name" ) ) ) {
				return true;
			}

			foreach ( self::get_lieu_ids() as $lieu_id ) {
				if ( ! empty( self::getOption( "resp_role_{$lieu_id}_$i-name" ) ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function getOption( $name, $default = null ) {
		$val = self::getTitanInstance()->getInternalAdminPageOption( $name, null );
		if ( null !== $val ) {
			return $val;
		}

		$val = maybe_unserialize( get_theme_mod( 'amapress_' . $name ) );

		if ( empty( $val ) ) {
			$val = $default;
		}

		if ( empty( $val ) && isset( self::$options_default[ $name ] ) ) {
			return self::$options_default[ $name ];
		}

		return $val;
	}

	public static function setOption( $name, $value ) {
		$inst = self::getTitanInstance();
		$inst->setInternalAdminPageOption( $name, $value );
		$inst->saveInternalAdminPageOptions();
	}

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();

			add_image_size( 'produit-thumb', 200, 200, true );
			add_image_size( 'user-thumb', 100, 100, true );

			self::init_post_types();
			amapress_add_rewrite_rules();

			$db_version = get_option( 'AMAPRESS_DB_VERSION' );
			if ( empty( $db_version ) ) {
				$db_version = 0;
			}
			if ( intval( $db_version ) < AMAPRESS_DB_VERSION ) {
				if ( $db_version < 70 ) {
					amapress_update_all_posts( [
						AmapressAdhesion_paiement::POST_TYPE,
						AmapressAdhesion::POST_TYPE,
						AmapressAmapien_paiement::POST_TYPE,
					] );
				}
				if ( $db_version < 73 ) {
					amapress_update_all_posts( [
						AmapressContrat_instance::POST_TYPE,
					] );
				}
				self::init_roles();
				flush_rewrite_rules();
				update_option( 'AMAPRESS_DB_VERSION', AMAPRESS_DB_VERSION );
			}

			self::rename_roles();

			$intermittent_migrated = get_option( 'amapress_intermittent_migrated' );
			if ( ! $intermittent_migrated ) {
				global $wpdb;
				$res = $wpdb->get_results( "SELECT p.ID, pm.meta_value as user_id FROM $wpdb->posts p INNER JOIN $wpdb->postmeta pm ON pm.post_id=p.ID AND pm.meta_key='amapress_adhesion_intermittence_user' WHERE p.post_type='amps_inter_adhe'" );
				foreach ( $res as $adh ) {
					$user = AmapressUser::getBy( (int) $adh->user_id );
					$user->inscriptionIntermittence( false );
					wp_delete_post( $adh->ID, true );
				}
				update_option( 'amapress_intermittent_migrated', 1 );
			}

			$amapress_init_adh_terms = get_option( 'amapress_init_adh_terms' );
			if ( ! $amapress_init_adh_terms ) {
				foreach (
					array(
						'adhesion_amap_term'        => __( 'Adhésion AMAP', 'amapress' ),
						'adhesion_reseau_amap_term' => __( 'Adhésion Réseau AMAP', 'amapress' ),
					) as $k => $v
				) {
					if ( ! term_exists( $v, 'amps_paiement_category' ) ) {
						$t = wp_insert_term( $v, 'amps_paiement_category' );
					} else {
						$t = term_exists( $v, 'amps_paiement_category' );
					}
					self::setOption( $k, $t['term_id'] );
				}
				update_option( 'amapress_init_adh_terms', 1 );
			}

//            update_option('amapress_amap_role_migrated', 0);
			$amap_role_migrated = get_option( 'amapress_amap_role_migrated' );
			if ( ! $amap_role_migrated ) {
				global $wpdb;
				$res = $wpdb->get_results( "SELECT user_id, meta_value as amap_roles FROM $wpdb->usermeta WHERE meta_key = 'amapress_user_amap_roles'" );
				foreach ( $res as $user_roles ) {
					//Wordpress level : simulate get_post_meta with single=true
					$roles = maybe_unserialize( $user_roles->amap_roles );
					//role array
					$roles = maybe_unserialize( $roles );
					if ( $roles ) {
						//cast to int as term id
						$roles = array_map( function ( $v ) {
							return (int) $v;
						}, $roles );
						wp_set_object_terms( $user_roles->user_id, $roles, AmapressUser::AMAP_ROLE );
					}
				}
				update_option( 'amapress_amap_role_migrated', 1 );
			}


//            add_shortcode('paged_gallery', array(__('Amapress', 'amapress'), 'generic_paged_gallery_shortcode'));

			TitanFrameworkOptionDate::$default_jquery_date_format      = 'dd/mm/yy';
			TitanFrameworkOptionDate::$default_date_format             = 'd/m/Y';
			TitanFrameworkOptionDate::$default_date_placeholder        = 'JJ/MM/AAAA';
			TitanFrameworkOptionDate::$default_time_format             = 'H:i';
			TitanFrameworkOptionDate::$default_time_placeholder        = 'HH:MM';
			TitanFrameworkOptionMultiDate::$default_jquery_date_format = 'dd/mm/yy';
			TitanFrameworkOptionMultiDate::$default_date_format        = 'd/m/Y';
			TitanFrameworkOptionMultiDate::$default_date_placeholder   = 'JJ/MM/AAAA';
		}
	}

	/*public static function user_produit_likebox_shortcode() {

    }*/
	public static function datediffInWeeks( $date1, $date2 ) {
		if ( $date1 > $date2 ) {
			return self::datediffInWeeks( $date2, $date1 );
		}
		$first  = DateTime::createFromFormat( 'U', $date1 );
		$second = DateTime::createFromFormat( 'U', $date2 );

		return ceil( $first->diff( $second )->days / 7.0 );
	}

	public static function wrapIf( $content, $condition, $open_tag = 'strong' ) {
		list( $close_tag ) = explode( ' ', $open_tag );
		if ( $condition ) {
			return "<$open_tag>$content</$close_tag>";
		} else {
			return $content;
		}
	}

	public static function makeWikiLink( $url, $title = null, $escape_title = true ) {
		if ( empty( $title ) ) {
			$title = __( 'Documentation Amapress', 'amapress' );
		}

		return '<span class="dashicons dashicons-admin-site-alt"></span>&nbsp;' . self::makeLink( $url,
				$title,
				$escape_title, true );
	}

	public static function makeInternalLink( $url, $title = null, $escape_title = true ) {
		return '<span class="dashicons dashicons-sos"></span>&nbsp;' . self::makeLink( $url,
				$title,
				$escape_title, true );
	}

	public static function makeExternalLink( $url, $title = null, $escape_title = true ) {
		return '<span class="dashicons dashicons-external"></span>&nbsp;' . self::makeLink( $url,
				$title,
				$escape_title, true );
	}

	public static function makeLink( $url, $title = null, $escape_title = true, $blank = false ) {
		if ( empty( $title ) && '0' !== $title ) {
			$title = $url;
		}

		return '<a href="' . esc_attr( $url ) . '"' . ( $blank ? ' target="_blank"' : '' ) . '>' . ( $escape_title ? esc_html( $title ) : $title ) . '</a>';
	}

	public static function makeButtonLink( $url, $title = null, $escape_title = true, $blank = false, $classes = null ) {
		if ( empty( $title ) ) {
			$title = $url;
		}
		if ( empty( $classes ) ) {
			$classes = is_admin() ? 'button button-secondary' : 'btn btn-default';
		}

		return '<a class="' . $classes . '" href="' . esc_attr( $url ) . '"' . ( $blank ? ' target="_blank"' : '' ) . '>' . ( $escape_title ? esc_html( $title ) : $title ) . '</a>';
	}

	public static function getPageLink( $name ) {
		return get_page_link( self::getOption( $name ) );
	}

	public static function getMesInfosSublink( $relative ) {
		$optionsPage = self::resolve_post_id( self::getOption( 'mes-infos-page' ), 'page' );

		return trailingslashit( get_page_link( $optionsPage ) ) . $relative;
	}

	/**
	 * @return AmapressLieu_distribution[]
	 */
	public static function get_principal_lieux() {
		return array_filter( self::get_lieux(), function ( $l ) {
			return $l->isPrincipal();
		} );
	}

	/**
	 * @return AmapressLieu_distribution[]
	 */
	public static function get_lieux() {
		$ret = get_option( 'amps_lieux' );
		if ( ! $ret ) {
			$ret = array_map( function ( $p ) {
				return AmapressLieu_distribution::getBy( $p );
			}, get_posts(
				array(
					'post_type'      => AmapressLieu_distribution::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
				)
			) );
			add_option( 'amps_lieux', $ret );
		}

		return $ret;
	}

	/**
	 * @return AmapressProducteur[]
	 */
	public static function get_producteurs( $no_cache = false ) {
		$key = 'get_producteurs';
		$res = wp_cache_get( $key );
		if ( $no_cache || false === $res ) {
			$res = array_map( function ( $p ) {
				return AmapressProducteur::getBy( $p );
			}, get_posts(
				array(
					'post_type'      => AmapressProducteur::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
				)
			) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public static function get_lieu_ids() {
		return array_map( 'Amapress::to_id', self::get_lieux() );
	}

	public static function resolve_post_id( $name, $post_type ) {
		if ( is_numeric( $name ) ) {
			return intval( $name );
		}
		$id        = - 1;
		$post_type = amapress_unsimplify_post_type( $post_type );
		if ( is_string( $name ) ) {
			$orig_name = $name;
			$object    = get_page_by_path( $name, OBJECT, $post_type );
			if ( $object ) {
				return $object->ID;
			}
			$object = amapress_get_page_by_title( $name, $post_type );
			if ( $object ) {
				return $object->ID;
			} else {
				$name = preg_replace( '/\x{2019}/u', '\'', $name );
				if ( $orig_name != $name ) {
					$object = amapress_get_page_by_title( $name, $post_type );
					if ( $object ) {
						return $object->ID;
					} else {
						$name = wptexturize( $name );
						if ( $orig_name != $name ) {
							$object = amapress_get_page_by_title( $name, $post_type );
							if ( $object ) {
								return $object->ID;
							}
						}
					}
				}
			}
		}
		if ( is_a( $name, 'WP_Post' ) ) {
			return $name->ID;
		}

		if ( $id < 0 ) {
			$id = apply_filters( "amapress_resolve_{$post_type}_id", $id, $name );
			$id = apply_filters( 'amapress_resolve_post_id', $id, $name, $post_type );
		}

		return $id;
	}

	public static function resolve_user_id( $name ) {
		if ( empty( $name ) ) {
			return - 1;
		}

		if ( is_numeric( $name ) ) {
			return intval( $name );
		}
		$id = - 1;
		if ( is_string( $name ) ) {
			$args   = array(
				'search'        => $name, // or login or nicename in this example
				'search_fields' => array( 'user_login', 'user_nicename', 'display_name', 'user_email' )
			);
			$user   = new WP_User_Query( $args );
			$object = $user->get_results();
			if ( ! empty( $object ) && count( $object ) == 1 ) {
				return $object[0]->ID;
			}
			$object = get_user_by( 'email', $name );
			if ( $object ) {
				return $object->ID;
			}
//            $object = get_user_by('slug', $name);
//            if ($object) return $object->ID;
			$object = get_user_by( 'login', $name );
			if ( $object ) {
				return $object->ID;
			}
			if ( ! empty( $name ) ) {
				$args   = array(
					'meta_key'   => 'last_name',
					'meta_value' => $name
				);
				$user   = new WP_User_Query( $args );
				$object = $user->get_results();
				if ( ! empty( $object ) && count( $object ) == 1 ) {
					return $object[0]->ID;
				}

				$args   = array(
					'search'        => '*' . $name . '*', // or login or nicename in this example
					'search_fields' => array( 'display_name' )
				);
				$user   = new WP_User_Query( $args );
				$object = $user->get_results();
				if ( ! empty( $object ) && count( $object ) == 1 ) {
					return $object[0]->ID;
				}
			}
		}
		if ( is_a( $name, 'WP_User' ) ) {
			return $name->ID;
		}

		if ( $id < 0 ) {
			$id = apply_filters( 'amapress_resolve_user_id', $id, $name );
		}

		return $id;
	}

	public static function resolve_tax_id( $name, $tax_type ) {
		if ( is_numeric( $name ) ) {
			return intval( $name );
		}
		$id = - 1;
		if ( is_string( $name ) ) {
			$object = get_term_by( 'slug', $name, $tax_type );
			if ( $object ) {
				$id = intval( $object->term_id );
			}
			if ( $id < 0 ) {
				$object = get_term_by( 'name', $name, $tax_type );
				if ( $object ) {
					$id = intval( $object->term_id );
				}
			}
		}
		if ( is_a( $name, 'WP_Term' ) ) {
			return intval( $name->term_id );
		}

		if ( $id < 0 ) {
			$id = apply_filters( 'amapress_resolve_tax_id', $id, $name, $tax_type );
		}

		return $id;
	}

	public static function get_lieu_id( $lieu ) {
		return self::resolve_post_id( $lieu, AmapressLieu_distribution::INTERNAL_POST_TYPE );
	}

//	public static function init_post_capabilities( $singular, $plural ) {
//		return array(
//			'edit_post'           => 'edit_' . $singular,
//			'edit_posts'          => 'edit_' . $plural,
//			'edit_private_posts'   => 'edit_private_' . $plural,
//			'edit_published_posts'   => 'edit_published_' . $plural,
//			'edit_others_posts'   => 'edit_others_' . $plural,
//			'publish_posts'       => 'publish_' . $plural,
//			'create_posts'        => 'publish_' . $plural,
//			'read_post'           => 'read_' . $singular,
//			'read_private_posts'  => 'read_private_' . $plural,
//			'delete_post'         => 'delete_' . $singular,
//			'delete_posts'        => 'delete_' . $plural,
//			'delete_others_posts' => 'delete_others_' . $plural,
//			'delete_private_posts' => 'delete_private_' . $plural,
//			'delete_published_posts' => 'delete_published_' . $plural,
//		);
//	}

	public static function add_role_cap( $role_name, $cap ) {
		$role = get_role( $role_name );
		if ( ! $role ) {
			return false;
		}
		$role->add_cap( $cap );

		return true;
	}

	public static function remove_role_cap( $role_name, $cap ) {
		$role = get_role( $role_name );
		if ( ! $role ) {
			return false;
		}
		$role->remove_cap( $cap );

		return true;
	}

	public static function add_post_role( $role_name, $singular, $plural, $args ) {
		$args = wp_parse_args( $args,
			array(
				'read'          => true,
				'edit'          => false,
				'delete'        => false,
				'publish'       => false,
				'edit_others'   => null,
				'delete_others' => null,
			) );

		$read          = $args['read'];
		$edit          = $args['edit'];
		$delete        = $args['delete'];
		$publish       = $args['publish'];
		$delete_others = $args['delete_others'];
		if ( null === $delete_others ) {
			$delete_others = $delete;
		}
		$edit_others = $args['edit_others'];
		if ( null === $edit_others ) {
			$edit_others = $edit;
		}

		$admins = get_role( $role_name );

		if ( $read ) {
			$admins->add_cap( 'read_' . $singular );
			$admins->add_cap( 'read_private_' . $plural );
		} else {
			$admins->remove_cap( 'read_' . $singular );
			$admins->remove_cap( 'read_private_' . $plural );
		}
		if ( $edit ) {
			$admins->add_cap( 'edit_' . $singular );
			$admins->add_cap( 'edit_' . $plural );
			$admins->add_cap( 'edit_published_' . $plural );
			$admins->add_cap( 'edit_private_' . $plural );
		} else {
			$admins->remove_cap( 'edit_' . $singular );
			$admins->remove_cap( 'edit_' . $plural );
			$admins->remove_cap( 'edit_published_' . $plural );
			$admins->remove_cap( 'edit_private_' . $plural );
		}
		if ( $edit_others ) {
			$admins->add_cap( 'edit_others_' . $plural );
		} else {
			$admins->remove_cap( 'edit_others_' . $plural );
		}
		if ( $publish ) {
			$admins->add_cap( 'publish_' . $plural );
			$admins->add_cap( 'publish_' . $singular );
		} else {
			$admins->remove_cap( 'publish_' . $plural );
			$admins->remove_cap( 'publish_' . $singular );
		}

		if ( $delete ) {
			if ( 'post' == $singular ) {
				$admins->add_cap( 'delete_' . $singular );
			} else {
				$admins->remove_cap( 'delete_' . $singular );
			}
			$admins->add_cap( 'delete_' . $plural );
			$admins->add_cap( 'delete_private_' . $plural );
			$admins->add_cap( 'delete_published_' . $plural );
		} else {
			$admins->remove_cap( 'delete_' . $singular );
			$admins->remove_cap( 'delete_' . $plural );
			$admins->remove_cap( 'delete_private_' . $plural );
			$admins->remove_cap( 'delete_published_' . $plural );
		}
		if ( $delete_others ) {
			$admins->add_cap( 'delete_others_' . $plural );
		} else {
			$admins->remove_cap( 'delete_others_' . $plural );
		}
	}

	public static function get_array( $value ) {
		if ( empty( $value ) ) {
			return array();
		}
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( is_serialized( $value ) ) {
			return self::get_array( unserialize( $value ) );
		}
		if ( is_string( $value ) ) {
			return explode( ',', $value );
		}

		return $value;
	}

	public static function get_user_meta_array( $id, $name ) {
		return self::get_array( get_user_meta( $id, $name, true ) );
	}

	public static function get_post_meta_array( $id, $name ) {
		return self::get_array( get_post_meta( $id, $name, true ) );
	}

	public static function start_of_day( $date ) {
		return strtotime( 'today', $date );
	}

	public static function hour_of_day( $date, $hour, $minute = 0 ) {
		$date = self::start_of_day( $date );

		return $date + $hour * 3600 + $minute * 60;
	}

	public static function end_of_day( $date ) {
		$date = self::start_of_day( $date );

		return $date + 86399;
	}

	public static function add_days( $date, $days ) {
		if ( $days < 0 ) {
			return strtotime( "$days day", $date );
		} else {
			return strtotime( "+$days day", $date );
		}
	}

	public static function add_a_week( $date, $weeks = 1 ) {
		return strtotime( "+{$weeks} week", $date );
	}

	public static function add_a_month( $date, $months = 1 ) {
		return strtotime( "+{$months} month", $date );
	}

	public static function add_a_year( $date, $years = 1 ) {
		if ( $years < 0 ) {
			return strtotime( "{$years} year", $date );
		} else {
			return strtotime( "+{$years} year", $date );
		}
	}

	public static function remove_a_year( $date ) {
		return strtotime( '-1 year', $date );
	}

	public static function start_of_month( $date ) {
		return self::start_of_day( strtotime( date( 'Y-m-01', $date ) ) );
	}

	public static function end_of_month( $date ) {
		return self::end_of_day( strtotime( date( 'Y-m-t', $date ) ) );
	}

	public static function start_of_year( $date ) {
		return self::start_of_day( strtotime( date( 'Y-01-01', $date ) ) );
	}

	public static function end_of_year( $date ) {
		return self::end_of_day( strtotime( date( 'Y-12-31', $date ) ) );
	}


	public static function start_of_week( $date ) {
		return self::start_of_day( strtotime( 'Monday this week', $date ) );
	}

	public static function end_of_week( $date ) {
		return self::end_of_day( strtotime( 'Next Sunday', $date ) );
	}

	public static function to_adhesion_lieu( $u ) {
		return get_post_meta( $u->ID, 'amapress_adhesion_lieu', true );
	}

	public static function to_id( $u ) {
		return $u->ID;
	}

	private static function add_role( $role, $display_name, $capabilities ) {
		$r = get_role( $role );
		if ( $r ) {
			foreach ( $capabilities as $k => $v ) {
				$r->add_cap( $k, $v );
			}
		} else {
			add_role( $role, $display_name, $capabilities );
		}
	}

	private static function clear_role( $role ) {
		$r = get_role( $role );
		if ( $r ) {
			foreach ( array_keys( $r->capabilities ) as $role_name ) {
				$r->remove_cap( $role_name );
			}
		}
	}

	/**
	 * @return null|WP_Role
	 */
	public static function init_admin_role() {
		self::add_post_role( 'administrator', 'contrat', 'contrats', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'contrat_instance', 'contrat_instances', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'contrat_quantite', 'contrat_quantites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
//        self::add_post_role('administrator', 'contrat_paiement', 'contrat_paiements', $read = true, $edit = true, $delete = true, $delete_others = true);
		self::add_post_role( 'administrator', 'contrat_paiement', 'contrat_paiements', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'adhesion', 'adhesions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'produit', 'produits', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'panier', 'paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'administrator', 'visite', 'visites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'assemblee_generale', 'assemblee_generales', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'producteur', 'producteurs', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'lieu_distribution', 'lieu_distributions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'distribution', 'distributions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'recette', 'recettes', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => true,
		) );
		self::add_post_role( 'administrator', 'message', 'messages', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => true,
		) );
		self::add_post_role( 'administrator', 'amap_event', 'amap_events', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'intermittence_panier', 'intermittence_paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => false,
		) );
//        self::add_post_role('administrator', 'adhesion_intermittence', 'adhesion_intermittences', array(
//            'read' => true,
//            'edit' => true,
//            'delete' => true,
//            'publish' => true,
//        ));
		self::add_post_role( 'administrator', 'adhesion_paiement', 'adhesion_paiements', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'adhesion_period', 'adhesion_periods', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'news', 'newss', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'post', 'posts', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'adhesion_request', 'adhesion_requests', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'mailinglist', 'mailinglists', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'mailing_group', 'mailing_groups', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'administrator', 'reminder', 'reminders', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );

		$r = get_role( 'administrator' );
		$r->add_cap( 'manage_fournisseurs' );
		$r->add_cap( 'manage_contrats' );
		$r->add_cap( 'manage_events' );
		$r->add_cap( 'manage_amapiens' );
		$r->add_cap( 'manage_contenu' );
		$r->add_cap( 'manage_intermittence' );
		$r->add_cap( 'manage_amapress' );
		$r->add_cap( 'manage_categories' );
		$r->add_cap( 'import_csv' );
		$r->add_cap( 'list_users' );
		$r->add_cap( 'edit_users' );
		$r->add_cap( 'add_users' );
		$r->add_cap( 'create_users' );
		$r->add_cap( 'delete_users' );
		$r->add_cap( 'upload_files' );

		if ( class_exists( 'bbPress' ) ) {
			$caps = bbp_get_caps_for_role( bbp_get_keymaster_role() );
//            $caps = bbp_get_caps_for_role(bbp_get_moderator_role());
//            $caps = bbp_get_caps_for_role(bbp_get_participant_role());
			foreach ( $caps as $cap => $enabled ) {
				if ( $enabled ) {
					$r->add_cap( $cap );
				} else {
					$r->remove_cap( $cap );
				}
			}
		}
	}

	public static function init_producteur_role() {
		self::clear_role( 'producteur' );
		self::add_role( 'producteur', __( 'Amap Producteur', 'amapress' ), array( 'read' => true ) );
		self::add_post_role( 'producteur', 'produit', 'produits', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'producteur', 'producteur', 'producteurs', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'producteur', 'contrat', 'contrats', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'producteur', 'panier', 'paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'producteur', 'recette', 'recettes', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
		) );
		self::add_post_role( 'producteur', 'post', 'posts', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'edit_others'   => false,
			'publish'       => true,
			'delete_others' => false,
		) );
		self::add_post_role( 'producteur', 'reminder', 'reminders', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
			'edit_others'   => false,
		) );
		self::add_post_role( 'producteur', 'amap_event', 'amap_events', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
//		self::add_post_role( 'producteur', 'contrat_instance', 'contrat_instances', array(
//			'read'    => true,
//			'edit'    => true,
//			'delete'  => false,
//			'publish' => false,
//		) );
//		self::add_post_role( 'producteur', 'contrat_quantite', 'contrat_quantites', array(
//			'read'    => true,
//			'edit'    => true,
//			'delete'  => false,
//			'publish' => false,
//		) );
		self::add_post_role( 'producteur', 'contrat_paiement', 'contrat_paiements', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'producteur', 'adhesion', 'adhesions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'producteur', 'visite', 'visites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'producteur', 'message', 'messages', array(
			'read'          => true,
			'edit'          => true,
			'edit_others'   => false,
			'delete'        => true,
			'delete_others' => false,
			'publish'       => true,
		) );

		$r = get_role( 'producteur' );
		$r->add_cap( 'upload_files' );
		$r->add_cap( 'manage_contenu' );
		$r->add_cap( 'manage_contrats' );
		$r->add_cap( 'manage_events' );
		$r->add_cap( 'manage_amapiens' );
		$r->add_cap( 'manage_amapien_contrat' );
		if ( class_exists( 'bbPress' ) ) {
//            $caps = bbp_get_caps_for_role(bbp_get_keymaster_role());
//            $caps = bbp_get_caps_for_role(bbp_get_moderator_role());
			$caps = bbp_get_caps_for_role( bbp_get_participant_role() );
			foreach ( $caps as $cap => $enabled ) {
				if ( $enabled ) {
					$r->add_cap( $cap );
				} else {
					$r->remove_cap( $cap );
				}
			}
		}
	}

	public static function init_tresorier_role() {
		self::clear_role( 'tresorier' );
		self::add_role( 'tresorier', __( 'Amap Trésorier', 'amapress' ),
			array(
				'read'         => true,
				'list_users'   => true,
				'edit_users'   => true,
				'add_users'    => true,
				'create_users' => true,
				'delete_users' => true
			) );

		self::add_post_role( 'tresorier', 'adhesion', 'adhesions', array(
			'read'    => false,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'tresorier', 'visite', 'visites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'tresorier', 'assemblee_generale', 'assemblee_generales', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'tresorier', 'recette', 'recettes', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'tresorier', 'message', 'messages', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'tresorier', 'amap_event', 'amap_events', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'tresorier', 'adhesion_paiement', 'adhesion_paiements', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'tresorier', 'adhesion_period', 'adhesion_periods', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'tresorier', 'post', 'posts', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
		) );
		self::add_post_role( 'tresorier', 'mailing_group', 'mailing_groups', array(
			'read'    => true,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'tresorier', 'reminder', 'reminders', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
			'edit_others'   => false,
		) );

		$r = get_role( 'tresorier' );
		$r->add_cap( 'manage_tresorerie' );
		$r->add_cap( 'manage_events' );
		$r->add_cap( 'manage_amapiens' );
		$r->add_cap( 'manage_contenu' );
		$r->add_cap( 'manage_intermittence' );
		$r->add_cap( 'manage_categories' );
		$r->add_cap( 'import_csv' );
		$r->add_cap( 'list_users' );
		$r->add_cap( 'edit_users' );
		$r->add_cap( 'add_users' );
		$r->add_cap( 'create_users' );
		$r->add_cap( 'delete_users' );
		$r->add_cap( 'remove_users' );
		$r->add_cap( 'promote_users' );
		$r->add_cap( 'upload_files' );

		if ( class_exists( 'bbPress' ) ) {
			$caps = bbp_get_caps_for_role( bbp_get_moderator_role() );
			foreach ( $caps as $cap => $enabled ) {
				if ( $enabled ) {
					$r->add_cap( $cap );
				} else {
					$r->remove_cap( $cap );
				}
			}
		}
	}

	public static function init_coordinateur_role() {
		self::clear_role( 'coordinateur_amap' );

		self::add_role( 'coordinateur_amap', __( 'Amap Coordinateur', 'amapress' ),
			array(
				'read'         => true,
				'list_users'   => true,
				'edit_users'   => true,
				'add_users'    => true,
				'create_users' => true,
				'delete_users' => false
			) );

		//allow "post" type access to all roles, else an access denied is triggered (https://core.trac.wordpress.org/ticket/32088)
		//see also : user_can_access_admin_page

		self::add_post_role( 'coordinateur_amap', 'contrat', 'contrats', array(
			'read' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'contrat_instance', 'contrat_instances', array(
			'read' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'contrat_quantite', 'contrat_quantites', array(
			'read' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'adhesion', 'adhesions', array(
			'read' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'visite', 'visites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'assemblee_generale', 'assemblee_generales', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'produit', 'produits', array(
			'read'    => true,
			'edit'    => true,
			'publish' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'producteur', 'producteurs', array(
			'read' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'panier', 'paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'coordinateur_amap', 'lieu_distribution', 'lieu_distributions', array(
			'read' => true,
			'edit' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'distribution', 'distributions', array(
			'read' => true,
			'edit' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'recette', 'recettes', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'message', 'messages', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'amap_event', 'amap_events', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'post', 'posts', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
		) );
		self::add_post_role( 'coordinateur_amap', 'page', 'pages', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );

		self::add_post_role( 'coordinateur_amap', 'adhesion_request', 'adhesion_requests', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'coordinateur_amap', 'mailinglist', 'mailinglists', array(
			'read'    => true,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'coordinateur_amap', 'mailing_group', 'mailing_groups', array(
			'read'    => true,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'coordinateur_amap', 'reminder', 'reminders', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
			'edit_others'   => false,
		) );

		$r = get_role( 'coordinateur_amap' );
		$r->add_cap( 'manage_events' );
		$r->add_cap( 'manage_amapiens' );
		$r->add_cap( 'manage_contenu' );
		$r->add_cap( 'manage_categories' );
		$r->add_cap( 'list_users' );
		$r->add_cap( 'edit_users' );
		$r->add_cap( 'add_users' );
		$r->add_cap( 'create_users' );
		$r->add_cap( 'delete_users' );
		$r->add_cap( 'remove_users' );
		$r->add_cap( 'promote_users' );
		$r->add_cap( 'read_page' );
		$r->add_cap( 'upload_files' );

		if ( class_exists( 'bbPress' ) ) {
			$caps = bbp_get_caps_for_role( bbp_get_moderator_role() );
			foreach ( $caps as $cap => $enabled ) {
				if ( $enabled ) {
					$r->add_cap( $cap );
				} else {
					$r->remove_cap( $cap );
				}
			}
		}
	}

	public static function init_redacteur_role() {
		self::clear_role( 'redacteur_amap' );

		self::add_role( 'redacteur_amap', __( 'Amap Rédacteur', 'amapress' ),
			array(
				'read'         => true,
				'list_users'   => false,
				'edit_users'   => false,
				'add_users'    => false,
				'create_users' => false,
				'delete_users' => false
			) );

		self::add_post_role( 'redacteur_amap', 'recette', 'recettes', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'redacteur_amap', 'post', 'posts', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
		) );

		$r = get_role( 'redacteur_amap' );
		$r->add_cap( 'manage_contenu' );
		$r->add_cap( 'upload_files' );
	}

	public static function init_responsable_role() {
		self::clear_role( 'responsable_amap' );

		self::add_role( 'responsable_amap', __( 'Amap Responsable', 'amapress' ),
			array(
				'read'         => true,
				'list_users'   => true,
				'edit_users'   => true,
				'add_users'    => true,
				'create_users' => true,
				'delete_users' => false
			) );

		//allow "post" type access to all roles, else an access denied is triggered (https://core.trac.wordpress.org/ticket/32088)
		//see also : user_can_access_admin_page

		self::add_post_role( 'responsable_amap', 'page', 'pages', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'contrat', 'contrats', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'contrat_instance', 'contrat_instances', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'contrat_quantite', 'contrat_quantites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'adhesion_request', 'adhesion_requests', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'contrat_paiement', 'contrat_paiements', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'adhesion', 'adhesions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'visite', 'visites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'assemblee_generale', 'assemblee_generales', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'produit', 'produits', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'producteur', 'producteurs', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'panier', 'paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'responsable_amap', 'lieu_distribution', 'lieu_distributions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'responsable_amap', 'distribution', 'distributions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'responsable_amap', 'recette', 'recettes', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'message', 'messages', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'amap_event', 'amap_events', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'intermittence_panier', 'intermittence_paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => false,
		) );
		self::add_post_role( 'responsable_amap', 'adhesion_paiement', 'adhesion_paiements', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'adhesion_period', 'adhesion_periods', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'responsable_amap', 'post', 'posts', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );

		self::add_post_role( 'responsable_amap', 'adhesion_request', 'adhesion_requests', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'responsable_amap', 'mailinglist', 'mailinglists', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'responsable_amap', 'mailing_group', 'mailing_groups', array(
			'read'    => true,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'responsable_amap', 'reminder', 'reminders', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
		) );

		$r = get_role( 'responsable_amap' );
		$r->add_cap( 'manage_fournisseurs' );
		$r->add_cap( 'manage_contrats' );
		$r->add_cap( 'manage_events' );
		$r->add_cap( 'manage_amapiens' );
		$r->add_cap( 'manage_contenu' );
		$r->add_cap( 'manage_intermittence' );
		$r->add_cap( 'manage_amapress' );
		$r->add_cap( 'manage_categories' );
		$r->add_cap( 'manage_tresorerie' );
		$r->add_cap( 'manage_amapien_contrat' );
		$r->add_cap( 'import_csv' );
		$r->add_cap( 'list_users' );
		$r->add_cap( 'edit_users' );
		$r->add_cap( 'add_users' );
		$r->add_cap( 'create_users' );
		$r->remove_cap( 'delete_users' );
		$r->remove_cap( 'remove_users' );
		$r->add_cap( 'promote_users' );
		$r->add_cap( 'read_page' );
		$r->add_cap( 'upload_files' );

		if ( class_exists( 'bbPress' ) ) {
			$caps = bbp_get_caps_for_role( bbp_get_moderator_role() );
			foreach ( $caps as $cap => $enabled ) {
				if ( $enabled ) {
					$r->add_cap( $cap );
				} else {
					$r->remove_cap( $cap );
				}
			}
		}
	}

	public static function init_referent_role() {
		self::clear_role( 'referent' );

		self::add_role( 'referent', __( 'Amap Référent producteur', 'amapress' ), array( 'read' => true ) );
		self::add_post_role( 'referent', 'contrat', 'contrats', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'contrat_instance', 'contrat_instances', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'contrat_quantite', 'contrat_quantites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'contrat_paiement', 'contrat_paiements', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'adhesion', 'adhesions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'visite', 'visites', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'produit', 'produits', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'producteur', 'producteurs', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'panier', 'paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'lieu_distribution', 'lieu_distributions', array(
			'read'    => true,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'distribution', 'distributions', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'recette', 'recettes', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'message', 'messages', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => true,
			'publish' => true,
		) );
		self::add_post_role( 'referent', 'amap_event', 'amap_events', array(
			'read'         => true,
			'edit'         => true,
			'edit_others'  => false,
			'delete'       => true,
			'delete_othes' => false,
			'publish'      => true,
		) );
		self::add_post_role( 'referent', 'intermittence_panier', 'intermittence_paniers', array(
			'read'    => true,
			'edit'    => true,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'post', 'posts', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'delete_others' => false,
			'publish'       => true,
		) );

		self::add_post_role( 'referent', 'adhesion_request', 'adhesion_requests', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'delete_others' => false,
			'publish'       => true,
		) );
		self::add_post_role( 'referent', 'mailinglist', 'mailinglists', array(
			'read'    => true,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'mailing_group', 'mailing_groups', array(
			'read'    => true,
			'edit'    => false,
			'delete'  => false,
			'publish' => false,
		) );
		self::add_post_role( 'referent', 'reminder', 'reminders', array(
			'read'          => true,
			'edit'          => true,
			'delete'        => true,
			'publish'       => true,
			'delete_others' => false,
			'edit_others'   => false,
		) );

		$r = get_role( 'referent' );
		$r->add_cap( 'manage_contrats' );
		$r->add_cap( 'manage_events' );
		$r->add_cap( 'manage_amapiens' );
		$r->add_cap( 'manage_contenu' );
		$r->add_cap( 'manage_amapien_contrat' );
		$r->add_cap( 'import_csv' );
		$r->add_cap( 'list_users' );
		$r->add_cap( 'edit_users' );
		$r->add_cap( 'add_users' );
		$r->add_cap( 'create_users' );
		$r->add_cap( 'delete_users' );
		$r->add_cap( 'upload_files' );

		if ( class_exists( 'bbPress' ) ) {
			$caps = bbp_get_caps_for_role( bbp_get_moderator_role() );
			foreach ( $caps as $cap => $enabled ) {
				if ( $enabled ) {
					$r->add_cap( $cap );
				} else {
					$r->remove_cap( $cap );
				}
			}
		}
	}

	public static function init_amapien_role() {
		self::clear_role( 'amapien' );

		self::add_role( 'amapien', __( 'Amapien', 'amapress' ), array( 'read' => true ) );
		$r = get_role( 'amapien' );
		if ( class_exists( 'bbPress' ) ) {
			$caps = bbp_get_caps_for_role( bbp_get_participant_role() );
			foreach ( $caps as $cap => $enabled ) {
				if ( $enabled ) {
					$r->add_cap( $cap );
				} else {
					$r->remove_cap( $cap );
				}
			}
		}
	}

	public static function init_paiement_category() {
		register_taxonomy( 'amps_paiement_category', 'amps_adh_pmt', array(
			'label'             => __( 'Types de paiement', 'amapress' ),
			'show_ui'           => true,
			'show_admin_column' => false,
			'public'            => false,
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		) );
	}

	public static function toBool( $var ) {
		if ( is_int( $var ) ) {
			return $var != 0;
		}
		if ( ! is_string( $var ) ) {
			return (bool) $var;
		}
		switch ( strtolower( $var ) ) {
			case 'force':
			case '1':
			case 'true':
			case 'on':
			case 'yes':
			case 'o':
			case 'oui':
			case 'x':
			case 'y':
				return true;
			default:
				return false;
		}
	}

	public static function init_amap_role_category() {
		$res = register_taxonomy( AmapressUser::AMAP_ROLE, 'user', array(
			'label'             => __( 'Rôle dans l\'AMAP', 'amapress' ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'public'            => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_amapress',
				'edit_terms'   => 'manage_amapress',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'manage_amapress',
			),
		) );
	}

	public static function init_amapien_group_category() {
		$res = register_taxonomy( AmapressUser::AMAPIEN_GROUP, 'user', array(
			'label'             => __( 'Groupe Amapien', 'amapress' ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'public'            => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_amapress',
				'edit_terms'   => 'manage_amapress',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'manage_amapress',
			),
		) );
	}

	public static function init_amap_event_category() {
		register_taxonomy( AmapressAmap_event::CATEGORY, AmapressAmap_event::INTERNAL_POST_TYPE, array(
			'label'             => __( 'Catégorie d\'évènement', 'amapress' ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		) );
	}

	public static function init_prpduit_category() {
		register_taxonomy( AmapressProduit::CATEGORY, AmapressProduit::INTERNAL_POST_TYPE, array(
			'label'             => __( 'Catégorie de produit', 'amapress' ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug' => 'categorie-produits',
			),
			'capabilities'      => array(
				'manage_terms' => 'publish_produit',
				'edit_terms'   => 'publish_produit',
				'delete_terms' => 'publish_produit',
				'assign_terms' => 'edit_produits',
			),
		) );
	}

	public static function init_recette_category() {
		register_taxonomy( AmapressRecette::CATEGORY, AmapressRecette::INTERNAL_POST_TYPE, array(
			'label'             => __( 'Catégorie de recette', 'amapress' ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug' => 'categorie-recettes',
			),
			'capabilities'      => array(
				'manage_terms' => 'publish_recette',
				'edit_terms'   => 'publish_recette',
				'delete_terms' => 'publish_recette',
				'assign_terms' => 'edit_recettes',
			),
		) );
	}

	public static function amapress_default_role( $default_role ) {
		// You can also add conditional tags here and return whatever
		return 'amapien'; // This is changed
		//return $default_role; // This allows default
	}

	public static function init_roles() {
//        remove_role('editor');
//        remove_role('subscriber');
//        remove_role('author');
//        remove_role('contributor');
		self::init_producteur_role();
		self::init_responsable_role();
		self::init_coordinateur_role();
		self::init_redacteur_role();
		self::init_tresorier_role();
		self::init_referent_role();
		self::init_amapien_role();
		self::init_admin_role();
	}


	//route single- template
	public static function amapress_provide_default_single( $single_template ) {
//            return locate_template(array('page.php'), false);
//        die($single_template);
		global $post;
		$post_types = AmapressEntities::getPostTypes();
		$pt         = amapress_simplify_post_type( $post->post_type );
		if ( array_key_exists( $pt, $post_types ) ) {
			$found = locate_template( 'single-' . $pt . '.php' );
			if ( $found != '' ) {
				$file = AMAPRESS__PLUGIN_DIR . 'templates/single-' . $pt . '.php';
				if ( file_exists( $file ) ) {
					return $file;
				}
			}
		}

		return $single_template;
	}

	//route archive- template
//    public static function amapress_provide_default_archive($template)
//    {
//        if (is_author()) {
////            var_dump(get_the_author());
//            die('xxx');
//            return locate_template(array('page.php'));
//        }
////        if (is_single())
////            return locate_template(array('page.php'), false);
//
//        $post_types = AmapressEntities::getPostTypes();
//        $post_type = get_query_var('post_type');
//        if (array_key_exists($post_type, $post_types)) {
//            if (is_post_type_archive($post_type)) {
//                $theme_files = array('archive-' . $post_type . '.php');
//                $exists_in_theme = locate_template($theme_files, false);
//                if ($exists_in_theme == '') {
//                    $file = AMAPRESS__PLUGIN_DIR . 'templates/archive-' . $post_type . '.php';
//                    if (file_exists($file)) return $file;
//                }
//            }
//        }
//        return $template;
//    }

	public static function get_post_labels( $singular, $plural, $custom_labels = array() ) {
		return wp_parse_args( $custom_labels, array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'name_admin_bar'     => $singular,
			'add_new'            => __( 'Ajouter', 'amapress' ),
			'add_new_item'       => sprintf( __( 'Ajouter %s', 'amapress' ), $singular ),
			'new_item'           => sprintf( __( 'Nouveau %s', 'amapress' ), $singular ),
			'edit_item'          => sprintf( __( 'Éditer - %s', 'amapress' ), $singular ),
			'view_item'          => sprintf( __( 'Voir %s', 'amapress' ), $singular ),
			'all_items'          => sprintf( __( 'Tous les %s', 'amapress' ), $plural ),
			'search_items'       => sprintf( __( 'Rechercher de %s', 'amapress' ), $plural ),
			'parent_item_colon'  => sprintf( __( '%s parent : ', 'amapress' ), $singular ),
			'not_found'          => sprintf( __( 'Pas de %s trouvé.', 'amapress' ), $singular ),
			'not_found_in_trash' => sprintf( __( 'Pas de %s trouvé dans la corbeille.', 'amapress' ), $singular )
		) );
	}

	public static function image_crop_dimensions( $default, $orig_w, $orig_h, $new_w, $new_h, $crop ) {
		if ( ! $crop ) {
			return null;
		} // let the WordPress default function handle this

		$aspect_ratio = $orig_w / $orig_h;
		$size_ratio   = max( $new_w / $orig_w, $new_h / $orig_h );

		$crop_w = round( $new_w / $size_ratio );
		$crop_h = round( $new_h / $size_ratio );

		$s_x = floor( ( $orig_w - $crop_w ) / 2 );
		$s_y = floor( ( $orig_h - $crop_h ) / 2 );

		return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
	}

	public static function userCanRegister() {
		return get_option( 'users_can_register' ) && ! is_multisite();
	}

	public static function isIntermittenceEnabled() {
		return self::getOption( 'intermittence_enabled' );
	}

	public static function isPredefinedPostType( $name ) {
		return $name == 'user' || $name == 'post' || $name == 'page';
	}

	public static function init_post_types() {
		foreach ( AmapressEntities::getPostTypes() as $name => $conf ) {
			if ( self::isPredefinedPostType( $name ) ) {
				continue;
			}

			$scope = array();
			if ( $conf['public'] === true ) {
				$scope['public']            = true;
				$scope['has_archive']       = true;
				$scope['show_in_nav_menus'] = true;
				$scope['show_in_admin_bar'] = true;
			}
			if ( $conf['public'] === 'adminonly' ) {
				$scope['public']            = false;
				$scope['show_ui']           = true;
				$scope['show_in_menu']      = true;
				$scope['has_archive']       = false;
				$scope['show_in_nav_menus'] = false;
			}

			if ( array_key_exists( 'show_in_menu', $conf ) && $conf['show_in_menu'] === false ) {
				$scope['show_in_menu'] = false;
			}
			if ( array_key_exists( 'show_in_nav_menu', $conf ) && $conf['show_in_nav_menu'] === false ) {
				$scope['show_in_nav_menus'] = false;
			}
			if ( array_key_exists( 'show_in_admin_bar', $conf ) && $conf['show_in_admin_bar'] === false ) {
				$scope['show_in_admin_bar'] = false;
			}
			if ( array_key_exists( 'has_archive', $conf ) ) {
				$scope['has_archive'] = $conf['has_archive'];
			}

			$create_options = array(
				'labels' => self::get_post_labels(
					$conf['singular'],
					$conf['plural'],
					isset( $conf['labels'] ) ? $conf['labels'] : array() ),
			);
			$supports       = array( 'custom-fields' );
			if ( array_key_exists( 'thumb', $conf ) && $conf['thumb'] === true ) {
				$supports[] = 'thumbnail';
			}
			if ( ! array_key_exists( 'title', $conf ) || $conf['title'] !== false ) {
				$supports[] = 'title';
			}
			if ( ! array_key_exists( 'editor', $conf ) || $conf['editor'] !== false ) {
				$supports[] = 'editor';
			}
			if ( array_key_exists( 'excerpt', $conf ) && $conf['excerpt'] === true ) {
				$supports[] = 'excerpt';
			}
			if ( array_key_exists( 'comments', $conf ) && $conf['comments'] === true ) {
				$supports[] = 'comments';
			}

			if ( array_key_exists( 'slug', $conf ) && ! empty( $conf['slug'] ) ) {
				//TODO do faster if needed
//				if ( isset( $conf['public'] ) && $conf['public'] === true ) {
//					$conf['slug'] = get_option( "amps_{$name}_slug", $conf['slug'] );
//				}
				$create_options['rewrite'] = array( 'slug' => $conf['slug'] );
			}
			$create_options['capabilities']    = [
				'delete_posts' => "delete_{$name}s"
			];
			$create_options['capability_type'] = $name;
			$create_options['supports']        = $supports;
			if ( array_key_exists( 'special_options', $conf ) && is_array( $conf['special_options'] ) ) {
				$create_options = array_merge( $create_options, $conf['special_options'] );
			}
			$create_options = array_merge( $create_options, $scope );
			if ( array_key_exists( 'menu_icon', $conf ) && ! empty( $conf['menu_icon'] ) ) {
				$create_options['menu_icon'] = $conf['menu_icon'];
			}
			if ( array_key_exists( 'menu_position', $conf ) ) {
				$create_options['menu_position'] = $conf['menu_position'];
			}
			if ( array_key_exists( 'can_export', $conf ) ) {
				$create_options['can_export'] = $conf['can_export'];
			} else {
				$create_options['can_export'] = false;
			}
			$internal_post_type = isset( $conf['internal_name'] ) ? $conf['internal_name'] : 'amps_' . $name;
			register_post_type( $internal_post_type, $create_options );

			add_filter( 'manage_' . $internal_post_type . '_posts_custom_column', function ( $colname, $post_id ) {
				if ( $colname == 'thumb-preview' ) {
					if ( has_post_thumbnail( $post_id ) ) {
//                        echo '<div style="max-width: 100%; height: auto">';
						echo get_the_post_thumbnail( $post_id, 'thumbnail' );
//                        echo '</div>';
					}

					return;
				}
			}, 10, 2 );
			add_filter( 'manage_edit-' . $internal_post_type . '_columns', function ( $columns ) use ( $internal_post_type ) {
				if ( post_type_supports( $internal_post_type, 'thumbnail' ) ) {
					$columns['thumb-preview'] = __( 'Image', 'amapress' );
				}

				return $columns;
			}, 9 );
		}

		self::init_amap_role_category();
		self::init_amapien_group_category();
		self::init_prpduit_category();
		self::init_recette_category();
		self::init_amap_event_category();
		self::init_paiement_category();
	}

	private static $options_default = [];

	public static function init_options_default() {
		foreach ( AmapressEntities::getMenu() as $m ) {
			$tabs = isset( $m['tabs'] ) ? $m['tabs'] : null;
			if ( $tabs && is_callable( $tabs, false ) ) {
				$tabs = call_user_func( $tabs );
			}
			if ( ! empty( $tabs ) ) {
				foreach ( $tabs as $tab_name => $tab ) {
					foreach ( $tab['options'] as $opt ) {
						if ( ! isset( $opt['id'] ) || ! isset( $opt['default'] ) ) {
							continue;
						}

						self::$options_default[ $opt['id'] ] =
							is_callable( $opt['default'], false ) ?
								call_user_func( $opt['default'], $opt ) :
								$opt['default'];
					}
				}
			}

			if ( ! empty( $m['options'] ) ) {
				foreach ( $m['options'] as $opt ) {
					if ( ! isset( $opt['id'] ) || ! isset( $opt['default'] ) ) {
						continue;
					}

					self::$options_default[ $opt['id'] ] =
						is_callable( $opt['default'], false ) ?
							call_user_func( $opt['default'], $opt ) :
							$opt['default'];
				}
			}

			if ( ! empty( $m['subpages'] ) ) {
				foreach ( $m['subpages'] as $mm ) {
					if ( ! isset( $mm['subpage'] ) ) {
						continue;
					}

					$ttabs = isset( $mm['tabs'] ) ? $mm['tabs'] : null;
					if ( $ttabs && is_callable( $ttabs, false ) ) {
						$ttabs = call_user_func( $ttabs );
					}
					if ( ! empty( $ttabs ) ) {
						foreach ( $ttabs as $tab_name => $tab ) {
							foreach ( $tab['options'] as $opt ) {
								if ( ! isset( $opt['id'] ) || ! isset( $opt['default'] ) ) {
									continue;
								}

								self::$options_default[ $opt['id'] ] =
									is_callable( $opt['default'], false ) ?
										call_user_func( $opt['default'], $opt ) :
										$opt['default'];
							}
						}
					}
					if ( ! empty( $mm['options'] ) ) {
						foreach ( $mm['options'] as $opt ) {
							if ( ! isset( $opt['id'] ) || ! isset( $opt['default'] ) ) {
								continue;
							}

							self::$options_default[ $opt['id'] ] =
								is_callable( $opt['default'], false ) ?
									call_user_func( $opt['default'], $opt ) :
									$opt['default'];
						}
					}
				}
			}
		}
	}


	public static function init_pages() {
		$titan = TitanFramework::getInstance( 'amapress' );

		foreach ( AmapressEntities::getMenu() as $m ) {
			if ( 'page' === $m['type'] ) {
				add_action( 'admin_menu', function () use ( $m ) {
					add_menu_page( do_shortcode( $m['title'] ), do_shortcode( $m['menu_title'] ),
						$m['capability'], $m['slug'],
						$m['function'], $m['icon'], $m['position'] );
				} );
			} elseif ( 'builtin' === $m['type'] ) {
				self::init_subpages( $m, $titan );
			} elseif ( 'panel' === $m['type'] ) {
				$p = $titan->createAdminPage( array_merge( array( 'id' => $m['id'] ), $m['settings'] ) );

				$tabs = isset( $m['tabs'] ) ? $m['tabs'] : null;
				if ( $tabs && is_callable( $tabs, false ) ) {
					$tabs = call_user_func( $tabs );
				}
				if ( ! empty( $tabs ) ) {
					foreach ( $tabs as $tab_name => $tab ) {
//                    if (!empty($tab['capability']) && !amapress_current_user_can($tab['capability'])) continue;

						$t = $p->createTab( array(
							'name'       => $tab_name,
							'id'         => ! empty( $tab['id'] ) ? $tab['id'] : $tab_name,
							'desc'       => ! empty( $tab['desc'] ) ? $tab['desc'] : '',
							'use_form'   => ( isset ( $tab['use_form'] ) ? $tab['use_form'] : true ),
							'use_table'  => ( isset ( $tab['use_table'] ) ? $tab['use_table'] : true ),
							'capability' => ( ! empty( $tab['capability'] ) ? $tab['capability'] : null )
						) );
						foreach ( $tab['options'] as $opt ) {
							$t->createOption( $opt );
						}
					}
				}

				if ( ! empty( $m['options'] ) ) {
					foreach ( $m['options'] as $opt ) {
						$p->createOption( $opt );
					}
				}

				self::init_subpages( $m, $titan );
			}
		}
	}

	public static function init_post_fields(
		$fields, $post_type, &$metaboxes, $name, $conf, $conditional_val = null
	) {
		$pt = amapress_simplify_post_type( $post_type );
		foreach ( $fields as $field => $options ) {
			$group = ! empty( $options['group'] ) ? $options['group'] : __( 'Options', 'amapress' );
			if ( ! array_key_exists( $group, $metaboxes ) ) {
				$group_conf = isset( $conf['groups'] ) && isset( $conf['groups'][ $group ] ) ? $conf['groups'][ $group ] : array();
				unset( $group_conf['post_type'] );
				if ( isset( $conf['show_date_column'] ) ) {
					$group_conf['show_date_column'] = $conf['show_date_column'];
				}
				$metaboxes[ $group ] = self::getTitanInstance()->createMetaBox(
					wp_parse_args( $group_conf,
						array(
							'name'      => $group,
							'post_type' => array( $post_type ),
						) )
				);
			}
			/** @var TitanFrameworkMetaBox $metaBox */
			$metaBox                           = $metaboxes[ $group ];
			$creation_options                  = array( 'id' => isset( $options['bare_id'] ) && $options['bare_id'] === true ? $field : $pt . '_' . $field );
			$creation_options                  = array_merge( $creation_options, $options );
			$creation_options['visible_class'] = ! empty( $conditional_val ) ? "tf_conditional tf_$conditional_val" : '';
			AmapressEntities::setTfOption( $pt, $field, $metaBox->createOption( $creation_options ) );

			if ( isset( $options['conditional'] ) && is_array( $options['conditional'] ) ) {
				foreach ( $options['conditional'] as $val => $suboptions ) {
					if ( $val == '_default_' ) {
						continue;
					}
					self::init_post_fields( $options['conditional'][ $val ], $post_type, $metaboxes, $name, $conf, $val );
				}
			}
		}
	}

	public static function generate_full_amap(
		$anonymize = true
	) {
		require_once 'demos/AmapDemoBase.php';

		$ret                = '';
		$generated_ids      = [];
		$around_address_lat = self::get_lieux()[0]->getAdresseLatitude();
		$around_address_lng = self::get_lieux()[0]->getAdresseLongitude();
		$relative_time      = 0;

		$user_roles_terms      = AmapDemoBase::dumpTerms( AmapressUser::AMAP_ROLE );
		$amapiens_groups_terms = AmapDemoBase::dumpTerms( AmapressUser::AMAPIEN_GROUP );
		$produits_terms        = AmapDemoBase::dumpTerms( AmapressProduit::CATEGORY );
		$recettes_terms        = AmapDemoBase::dumpTerms( AmapressRecette::CATEGORY );

		$ret .= '$this->createTerms(' . var_export( $amapiens_groups_terms, true ) . ', \'' . AmapressUser::AMAPIEN_GROUP . '\');' . "\n";
		$ret .= '$this->createTerms(' . var_export( $user_roles_terms, true ) . ', \'' . AmapressUser::AMAP_ROLE . '\');' . "\n";
		$ret .= '$this->createTerms(' . var_export( $produits_terms, true ) . ', \'' . AmapressProduit::CATEGORY . '\');' . "\n";
		$ret .= '$this->createTerms(' . var_export( $recettes_terms, true ) . ', \'' . AmapressRecette::CATEGORY . '\');' . "\n";

		$update_user_callback = function ( $user, &$userdata, &$usermeta ) use ( $around_address_lat, $around_address_lng, $anonymize ) {
			if ( $anonymize ) {
				$rnd                                     = AmapDemoBase::generateRandomAddress( $around_address_lat, $around_address_lng, 2000 );
				$usermeta['amapress_user_long']          = ! empty( $rnd ) ? $rnd['lon'] : '';
				$usermeta['amapress_user_lat']           = ! empty( $rnd ) ? $rnd['lat'] : '';
				$usermeta['amapress_user_location_type'] = 'ROOFTOP';
				$usermeta['amapress_user_adresse']       = ! empty( $rnd ) ? $rnd['address'] : '';
				$usermeta['amapress_user_code_postal']   = ! empty( $rnd ) ? $rnd['postcode'] : '';
				$usermeta['amapress_user_ville']         = ! empty( $rnd ) ? $rnd['city'] : '';
			}
			unset( $usermeta['amapress_user_co-adherents'] );
			unset( $usermeta['amapress_user_allow_show_email'] );
			unset( $usermeta['amapress_user_allow_show_adresse'] );
			unset( $usermeta['amapress_user_allow_show_tel_fixe'] );
			unset( $usermeta['amapress_user_allow_show_tel_mobile'] );
			unset( $usermeta['amapress_user_allow_show_avatar'] );
		};
		$update_post_callback = function ( $post, &$postdata, &$postmeta ) use ( $relative_time ) {
			if ( AmapressLieu_distribution::INTERNAL_POST_TYPE == $post['post_type'] ) {
//'amapress_lieu_distribution_adresse
//'amapress_lieu_distribution_code_postal
//'amapress_lieu_distribution_ville
//'amapress_lieu_distribution_long
//'amapress_lieu_distribution_lat
//'amapress_lieu_distribution_location_type
			} elseif ( AmapressProducteur::INTERNAL_POST_TYPE == $post['post_type'] ) {
				//amapress_producteur_adresse_exploitation
				//amapress_producteur_adresse_exploitation_lat
				//amapress_producteur_adresse_exploitation_long
				//amapress_producteur_adresse_exploitation_location_type
				unset( $postmeta['amapress_producteur_resume'] );
				unset( $postmeta['amapress_producteur_presentation'] );
				unset( $postmeta['amapress_producteur_historique'] );
			} elseif ( AmapressAdhesion::INTERNAL_POST_TYPE == $post['post_type'] ) {
				if ( isset( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
					$arr  = $postmeta['amapress_adhesion_contrat_quantite'];
					$arr2 = [];
					foreach ( $arr as $k => $v ) {
						$arr2[ $k ] = "posts[$v]";
					}
					$postmeta['amapress_adhesion_contrat_quantite'] = $arr2;
				}
				if ( ! empty( $postmeta['amapress_adhesion_panier_variables'] ) ) {
					$rt = $relative_time;
					if ( ! $rt ) {
						$rt = amapress_time();
					}
					$new_paniers = [];
					foreach ( $postmeta['amapress_adhesion_panier_variables'] as $k => $v ) {
						$new_date_panier = [];
						foreach ( $v as $kk => $vv ) {
							$new_date_panier["posts[$kk]"] = $vv;
						}
						$new_paniers[ 'now+' . ( intval( $k ) - Amapress::start_of_day( $rt ) ) ] = $new_date_panier;
					}
					$postmeta['amapress_adhesion_panier_variables'] = $new_paniers;
				}
				if ( isset( $postmeta['amapress_adhesion_contrat_quantite_factors'] ) ) {
					$arr  = $postmeta['amapress_adhesion_contrat_quantite_factors'];
					$arr2 = [];
					foreach ( $arr as $k => $v ) {
						$arr2["posts[$k]"] = $v;
					}
					$postmeta['amapress_adhesion_contrat_quantite_factors'] = $arr2;
				}
				unset( $postmeta['amapress_adhesion_message'] );
			}
			unset( $postmeta['amapress_intermittence_panier_adh_cancel_message'] );
			unset( $postmeta['amapress_intermittence_panier_adh_message'] );
			unset( $postmeta['amapress_contrat_instr_distrib'] );
			unset( $postmeta['amapress_lieu_distribution_instructions_privee'] );
			unset( $postmeta['amapress_lieu_distribution_contact_externe'] );
		};
		$media                = [];


		//TODO paniers intermittents
		$ret .= self::generate_test( AmapressAdhesionPeriod::getCurrent()->ID, AmapressAdhesionPeriod::POST_TYPE, $generated_ids, true, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
		foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
			$ret .= self::generate_test( $contrat_instances_id, AmapressContrat_instance::POST_TYPE, $generated_ids, false, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
			foreach ( AmapressContrats::get_contrat_quantites( $contrat_instances_id ) as $q ) {
				$ret .= self::generate_test( $q->ID, AmapressContrat_quantite::POST_TYPE, $generated_ids, false, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
			}
		}
		foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
			foreach ( AmapressContrats::get_active_adhesions_ids( $contrat_instances_id ) as $adhesion_id ) {
				$ret .= self::generate_test( $adhesion_id, AmapressAdhesion::POST_TYPE, $generated_ids, true, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
			}
//		    foreach (AmapressContrats::get_all_paiements($contrat_instances_id) as $amapien_paiement) {
//			    $ret .= self::generate_test($amapien_paiement->ID, AmapressAmapien_paiement::POST_TYPE, $generated_ids, true);
//		    }
			foreach ( get_posts( 'post_type=amps_panier&posts_per_page=-1&amapress_contrat_inst=' . $contrat_instances_id ) as $post ) {
				$ret .= self::generate_test( $post->ID, AmapressPanier::POST_TYPE, $generated_ids, true, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
			}
			foreach ( get_posts( 'post_type=amps_distribution&posts_per_page=-1&amapress_contrat_inst=' . $contrat_instances_id ) as $post ) {
				$ret .= self::generate_test( $post->ID, AmapressDistribution::POST_TYPE, $generated_ids, true, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
			}
		}
		foreach ( get_posts( 'post_type=amps_inter_panier&posts_per_page=-1' ) as $post ) {
			$ret .= self::generate_test( $post->ID, AmapressIntermittence_panier::POST_TYPE, $generated_ids, true, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
		}
		//post_type=amps_visite
		//post_type=amps_amap_event
		//post_type=amps_produit
		//post_type=amps_recette
		foreach ( get_posts( 'post_type=amps_visite&posts_per_page=-1' ) as $post ) {
			$ret .= self::generate_test( $post->ID, AmapressVisite::POST_TYPE, $generated_ids, true, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
		}
		foreach ( get_posts( 'post_type=amps_amap_event&posts_per_page=-1' ) as $post ) {
			$ret .= self::generate_test( $post->ID, AmapressAmap_event::POST_TYPE, $generated_ids, false, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
		}
		foreach ( get_posts( 'post_type=amps_produit&posts_per_page=-1' ) as $post ) {
			$ret .= self::generate_test( $post->ID, AmapressProduit::POST_TYPE, $generated_ids, false, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
		}
		foreach ( get_posts( 'post_type=amps_recette&posts_per_page=-1' ) as $post ) {
			$ret .= self::generate_test( $post->ID, AmapressRecette::POST_TYPE, $generated_ids, false, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize );
		}

		$ret = preg_replace( '/amapress_producteur_referent_(\d+)&#039;/', 'amapress_producteur_referent_\'. $this->posts[\'$1\']', $ret );
		$ret = preg_replace( '/amapress_contrat_referent_(\d+)&#039;/', 'amapress_contrat_referent_\'. $this->posts[\'$1\']', $ret );

		foreach ( $media as $k => $v ) {
			$ret = "\$this->medias['$k'] = '$v';\n" . $ret;
		}

		return $ret;
	}

	private static function generate_test(
		$id, $name,
		&$generated_ids = array(),
		$unset_post_title = false,
		$relative_time = 0,
		$update_user_callback = null,
		$update_post_callback = null,
		&$media = array(),
		$anonymize = true
	) {

		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		if ( ! $relative_time ) {
			$relative_time = amapress_time();
		}
//	    global $menu, $submenu;
//	    $the_menu = [];
//		foreach ($menu as $m) {
//		    $the_menu[$m[0]] = strpos($m[2], '.php') ? $m[2] : 'admin.php?page='.$m[2];
//		    if (isset($submenu[$m[2]])) {
//			    foreach ($submenu[$m[2]] as $mm) {
//				    $the_menu[$mm[0]] = strpos($mm[2], '.php') ? $mm[2] : 'admin.php?page='.$mm[2];
//			    }
//            }
//        }
//		var_export($the_menu);
//		die();

		require_once 'vendor/fzaninotto/faker/src/autoload.php';
		$faker = Faker\Factory::create( 'fr_FR' );

		$fields      = AmapressEntities::getPostTypeFields( $name );
		$field_names = array_keys( $fields );

		$id_affect = '';
		$ret       = '';
		if ( 'user' == $name ) {
			if ( in_array( "u$id", $generated_ids ) ) {
				return '';
			}
			$generated_ids[] = "u$id";
			$user            = get_user_by( 'ID', $id );
			if ( ! $user ) {
				return '';
			}
			$user_meta                 = array_map( function ( $v ) {
				if ( is_array( $v ) ) {
					if ( count( $v ) > 1 ) {
						return $v;
					} elseif ( isset( $v[0] ) ) {
						return $v[0];
					} else {
						return array_shift( $v );
					}
				} else {
					return $v;
				}
			}, get_user_meta( $id ) );
			$user_data                 = [
				'role'       => $user->roles[0],
				'first_name' => $anonymize ? $faker->firstName : $user->first_name,
				'last_name'  => $anonymize ? $faker->lastName : $user->last_name,
			];
			$user_data['display_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
			if ( $anonymize ) {
				$user_data['user_login'] = AmapressUsers::generate_unique_username( $user_data['first_name'] . '.' . $user_data['last_name'] );
				$user_data['user_email'] = $user_data['user_login'] . '@' . $faker->safeEmailDomain;
			} else {
				$user_data['user_login'] = $user->user_login;
				$user_data['user_email'] = $user->user_email;
			}
			if ( null != $update_user_callback && is_callable( $update_user_callback, false ) ) {
				call_user_func_array( $update_user_callback, [ $user, &$user_data, &$user_meta ] );
			}

			$ret .= '<pre>';
			$ret .= "\$this->users['$id'] = \$this->createUser(";
			$ret .= var_export( $user_data, true );
			$ret .= ");\n";

			if ( $anonymize ) {
				$user_meta['amapress_user_telephone']  = $faker->phoneNumber;
				$user_meta['amapress_user_telephone2'] = $faker->mobileNumber;
			}
			unset( $user_meta['amapress_user_telephone3'] );
			unset( $user_meta['amapress_user_telephone4'] );
			unset( $user_meta['amapress_user_avatar'] );
			unset( $user_meta['amapress_user_messages'] );
			$user_meta['amapress_user_autogen'] = 'true';
			foreach ( $user_meta as $k => $v ) {
				if ( ( ! in_array( $k, $field_names ) && ( strpos( $k, 'amapress_' ) !== 0 ) ) || empty( $v ) ) {
					continue;
				}
				$v_export = esc_html( var_export( $v, true ) );
				if ( isset( $fields[ $k ] ) ) {
					if ( 'select-users' == $fields[ $k ]['type'] || 'select-posts' == $fields[ $k ]['type']
					     || 'multicheck-users' == $fields[ $k ]['type'] || 'multicheck-posts' == $fields[ $k ]['type'] ) {
						$v_exports = [];
						foreach ( self::get_array( $v ) as $sub_id ) {
							$ret = self::generate_test( intval( $sub_id ),
									amapress_simplify_post_type( isset( $fields[ $k ]['post_type'] ) ? $fields[ $k ]['post_type'] : 'user' ),
									$generated_ids, $unset_post_title, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize ) . $ret;

							if ( 'select-users' == $fields[ $k ]['type'] || 'multicheck-users' == $fields[ $k ]['type'] ) {
								$v_exports[] = "users[$sub_id]";
							} else {
								$v_exports[] = "posts[$sub_id]";
							}
						}
						if ( 'select-users' == $fields[ $k ]['type'] || 'select-posts' == $fields[ $k ]['type'] ) {
							$v_export = $v_exports[0];
						} else {
							$v_export = $v_exports;
						}
					}
				}

				$ret .= "update_user_meta(\$this->users['$id'], '$k', $v_export);\n";
			}
			$ret       .= '</pre>';
			$id_affect = "\$this->users['$id']";
		} else {
			if ( in_array( "p$id", $generated_ids ) ) {
				return '';
			}

			$fields['_thumbnail_id'] = [
				'type' => 'upload',
			];
			$field_names[]           = '_thumbnail_id';

			$generated_ids[] = "p$id";
			$post            = get_post( $id, ARRAY_A );
			if ( ! is_array( $post ) ) {
				return '';
			}
			$post_meta = get_post_custom( $id );
			if ( empty( $post_meta ) ) {
				$post_meta = array();
			}
			if ( $unset_post_title ) {
				unset( $post['post_title'] );
			}
			//foreach ( $post_meta as $k => $v ) {
			//	amapress_dump( $k );
			//	amapress_dump( $v );
			//}
			$post_meta = array_map( function ( $v ) {
				return TitanEntity::prepare_custom_field_value( $v );
			}, $post_meta );
			if ( null != $update_post_callback && is_callable( $update_post_callback, false ) ) {
				call_user_func_array( $update_post_callback, [ $post, &$post, &$post_meta ] );
			}
			$filtered_post_meta = [];
			foreach ( $post_meta as $k => $v ) {
				if ( ( ! in_array( $k, $field_names ) && strpos( $k, 'amapress_' ) !== 0 ) || empty( $v ) ) {
					continue;
				}
				if ( isset( $fields[ $k ] ) && ( 'select-users' == $fields[ $k ]['type'] || 'select-posts' == $fields[ $k ]['type']
				                                 || 'multicheck-users' == $fields[ $k ]['type'] || 'multicheck-posts' == $fields[ $k ]['type'] ) ) {
					$vs = [];
					foreach ( self::get_array( $v ) as $sub_id ) {
						$ret = self::generate_test( intval( $sub_id ),
								amapress_simplify_post_type( isset( $fields[ $k ]['post_type'] ) ? $fields[ $k ]['post_type'] : 'user' ),
								$generated_ids, $unset_post_title, $relative_time, $update_user_callback, $update_post_callback, $media, $anonymize ) . $ret;

						if ( 'select-users' == $fields[ $k ]['type'] || 'multicheck-users' == $fields[ $k ]['type'] ) {
							$vs[] = "users[$sub_id]";
						} else {
							$vs[] = "posts[$sub_id]";
						}
					}
					if ( 'select-users' == $fields[ $k ]['type'] || 'select-posts' == $fields[ $k ]['type'] ) {
						$v = $vs[0];
					} else {
						$v = $vs;
					}
				} elseif ( '_thumbnail_id' == $k || isset( $fields[ $k ] ) && ( 'upload' == $fields[ $k ]['type'] ) ) {
					if ( is_array( $v ) ) {
						$v = array_shift( $v );
					}

					$bits_base64 = '';
					$ext         = '';
					if ( ! isset( $_GET['no_attach'] ) ) {
						$file = get_attached_file( intval( $v ) );
						$ext  = '.' . strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
						if ( filesize( $file ) > 512 * 1024 ) {
							$bits_base64 = false;
						} else {
							$bits_base64 = base64_encode( @file_get_contents( $file ) );
							$bits_base64 = chunk_split( $bits_base64, 76, "\r\n" );
						}
					}
					if ( ! empty( $bits_base64 ) ) {
						$attach_name           = 'amp_attach' . $v . $ext;
						$media[ $attach_name ] = $bits_base64;
						$v                     = 'attachm("' . $attach_name . '", $this->medias["' . $attach_name . '"])¤';
					} else {
						$v = 0;
					}
				} elseif ( isset( $fields[ $k ] ) && 'multidate' == $fields[ $k ]['type'] ) {
					$v = 'implode(", ", [' . implode( ', ', array_map( function ( $d ) use ( $relative_time ) {
							return 'date_i18n("d/m/Y", $now+' . ( intval( $d ) - Amapress::start_of_day( $relative_time ) ) . ')';
						}, array_map(
							'TitanEntity::to_date',
							TitanEntity::get_array( $v ) ) ) ) . '])¤';
				} elseif ( isset( $fields[ $k ] )
				           && ( 'date' == $fields[ $k ]['type']
				                || 'amapress_panier_date_subst' == $k ) ) {
					$v = 'now+' . ( intval( $v ) - self::start_of_day( $relative_time ) );
				}
				$filtered_post_meta[ $k ] = $v;
			}

			unset( $post['ID'] );
			unset( $post['post_author'] );
			unset( $post['guid'] );
			unset( $post['post_name'] );
			unset( $post['post_date'] );
			unset( $post['post_date_gmt'] );
			unset( $post['post_modified'] );
			unset( $post['post_modified_gmt'] );
			unset( $post['filter'] );
			foreach ( $post as $k => $v ) {
				if ( empty( $v ) ) {
					unset( $post[ $k ] );
				}
			}
			$post['meta_input'] = $filtered_post_meta;

			$ret       .= '<pre>';
			$ret       .= "\$this->posts['$id'] = \$this->createPost(\n";
			$ret       .= esc_html( var_export( $post, true ) );
			$ret       .= ");\n";
			$ret       .= '</pre>';
			$id_affect = "\$this->posts['$id']";
		}

		$ret = preg_replace( '/&#039;(posts|users)\[(\d+)\]&#039;/', '\$this->$1[\'$2\']', $ret );
		$ret = preg_replace( '/&#039;(now\s*\+\s*-?\d+)&#039;/', '\$$1', $ret );
		$ret = preg_replace( '/&#039;(implode\([^¤]+)¤&#039;/', '$1', $ret );
		$ret = preg_replace( '/&#039;attachm(\([^¤]+)¤&#039;/', '\$this->insertPostFromBitsBase64$1', $ret );

		foreach (
			[
				AmapressUser::AMAP_ROLE,
				AmapressUser::AMAPIEN_GROUP,
				AmapressRecette::CATEGORY,
				AmapressProduit::CATEGORY
			] as $taxonomy
		) {
			/** @var WP_Term[] $terms */
			$terms     = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'all', 'orderby' => 'term_id' ) );
			$new_terms = [];
			foreach ( $terms as $term ) {
				$new_terms[] = '$this->taxonomies[\'' . $taxonomy . '\'][\'' . $term->term_id . '\']';
			}
			if ( ! empty( $new_terms ) ) {
				$ret .= 'wp_set_object_terms(' . $id_affect . ', [' . implode( ',', $new_terms ) . '], \'' . $taxonomy . '\');' . "\n";
			}
		}

//		$amapress = [];
//		foreach (wp_load_alloptions() as $k => $v) {
//			if (strpos($k, 'amapress_') === 0)
//				$amapress[$k] = $v;
//		}

		return $ret;
	}

	public static function num2alpha(
		$n
	) {
		$r = '';
		for ( $i = 1; $n >= 0 && $i < 10; $i ++ ) {
			$r = chr( 0x41 + ( $n % pow( 26, $i ) / pow( 26, $i - 1 ) ) ) . $r;
			$n -= pow( 26, $i );
		}

		return $r;
	}

	private static $generated_id = [];

	public static function isBackOfficePage() {
		return is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX );
	}

	public static function isDoingCron() {
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	public static function init_post_metaboxes() {
		self::init_options_default();

		if ( ! self::isBackOfficePage() && ! self::isDoingCron() && ( ! isset( $_POST['action'] ) || strpos( $_POST['action'], 'tf_select' ) === false ) ) {
			return;
		}

		self::init_pages();

		$pts = AmapressEntities::getPostTypes();
		foreach ( $pts as $name => $conf ) {
			$metaboxes          = array();
			$internal_post_type = isset( $conf['internal_name'] ) ? $conf['internal_name'] : 'amps_' . $name;
			$fields             = AmapressEntities::getFilteredFields( $name );
			if ( isset( $_GET['generate_test'] ) ) {
				$fields['generate_test'] = array(
					'name'   => __( 'Dev - Creation test code', 'amapress' ),
					'type'   => 'custom',
					'desc'   => '===',
					'custom' => function ( $id ) use ( $name ) {
						return Amapress::generate_test( $id, $name, self::$generated_id );
					},
					'column' => function ( $id ) use ( $name ) {
						return Amapress::generate_test( $id, $name, self::$generated_id );
					},
					'group'  => 'Dev',
				);
			}
			if ( ! empty( $fields ) ) {
				self::init_post_fields( $fields, $internal_post_type, $metaboxes, $name, $conf );
			}
		}

		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = array_filter( $post_types, function ( $post_type ) {
			return ! strpos( $post_type, 'amps_' ) !== 0;
		} );

		$m = self::getTitanInstance()->createMetaBox(
			array(
				'name'        => __( 'Aide', 'amapress' ),
				'context'     => 'side',
				'priority'    => 'high',
				'post_type'   => $post_types,
				'show_column' => false,
			)
		);
		$m->createOption(
			array(
				'id'   => 'amps_sc_edit_help',
				'type' => 'note',
				'desc' => self::makeInternalLink(
						admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ),
						__( 'Liste des shortcodes', 'amapress' ) ) .
				          '<br/>' .
				          self::makeWikiLink( 'https://wiki.amapress.fr/admin/shortcodes' ),
			)
		);

		$m = self::getTitanInstance()->createMetaBox(
			array(
				'name'             => __( 'Amapress Protection', 'amapress' ),
				'context'          => 'side',
				'priority'         => 'high',
				'post_type'        => $post_types,
				'show_date_column' => true,
			)
		);
		$m->createOption(
			array(
				'id'      => 'amps_lo',
				'bare_id' => true,
//                'name' => amapress__('Amapiens seulement'),
				'type'    => 'checkbox',
				'desc'    => __( 'Amapiens connectés', 'amapress' ),
			)
		);
		$m->createOption(
			array(
				'id'          => 'amps_rd',
				'bare_id'     => true,
				'name'        => __( 'Rediriger non connectés vers', 'amapress' ),
				'type'        => 'select-pages',
				'desc'        => __( 'Par défaut : les internautes non connectés sont redirigés vers la page de connexion<br/>', 'amapress' )
				                 . self::makeWikiLink( 'https://wiki.amapress.fr/admin/protection' ),
				'show_column' => false,
			)
		);


//        var_dump(count($m->options));
	}

	public static function get_help_tabs(
		$screen_id
	) {
		$tp = AmapressEntities::getPostTypes();
		//https://codex.wordpress.org/Plugin_API/Admin_Screen_Reference
		if ( $screen_id == 'users' ) {
		} elseif ( $screen_id == 'user-new' ) {
			return $tp['user']['help_new'];
		} elseif ( $screen_id == 'user-edit' ) {
			return $tp['user']['help_edit'];
		} elseif ( $screen_id == 'profile' ) {
			return $tp['user']['help_profile'];
		} elseif ( $screen_id == 'admin' ) { //Import Tool
		} else {
			$matches = array();
			preg_match( '/settings_page_(.+)/', $screen_id, $matches );
			if ( ! empty( $matches[1] ) ) {
				$pt = amapress_simplify_post_type( $matches[1] );

				return isset( AmapressEntities::$settings_help[ $pt ] ) ? AmapressEntities::$settings_help[ $pt ] : null;
			} else {
				preg_match( '/(edit_)?(.+)/', $screen_id, $matches );
				if ( ! empty( $matches[2] ) && array_key_exists( amapress_simplify_post_type( $matches[2] ), $tp ) ) {
					$aa = $tp[ amapress_simplify_post_type( $matches[2] ) ];
					if ( ! empty( $matches[1] ) ) {
						return ! empty( $aa['help_edit'] ) ? $aa['help_edit'] : '';
					} else {
						return ! empty( $aa['help_view'] ) ? $aa['help_view'] : '';
					}
				}
			}
		}

		return null;
	}

	public static function help_admin_notices() {
		if ( is_admin() ) {
//			global $pagenow;
//			if ( $pagenow == 'nav-menus.php' ) {
//				echo '<div class="notice notice-warning">
//                        <p>Pour éditer le menu d\'un site utisant Amapress, il faut utiliser <a href="' . admin_url( 'customize.php?autofocus[panel]=nav_menus' ) . '">Apparence &gt; Personnaliser</a></p>
//</div>';
//			}
			$screen = get_current_screen();
			if ( ! empty( $screen->id ) ) {
				$help_tabs_list = self::get_help_tabs( $screen->id );
				if ( $help_tabs_list && ! empty( $help_tabs_list['summary'] ) ) {
					?>
                    <div class="notice notice-info">
                        <p><?php echo $help_tabs_list['summary']; ?></p>
                    </div>
					<?php
				}
			}
		}
	}

	public static function handle_help() {
		//Generate help if one is available for the current screen
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( ! empty( $screen->id ) && ! $screen->get_help_tabs() ) {
				$help_tabs_list = self::get_help_tabs( $screen->id );
				if ( ! empty( $help_tabs_list ) ) {
					// Loop through tabs
					foreach ( $help_tabs_list as $key => $help_tabs ) {
						// Make sure types are a screen method
						if ( ! in_array( $key, array( 'add_help_tab', 'set_help_sidebar' ) ) ) {
							continue;
						}
						foreach ( $help_tabs as $help_tab ) {
							$content = '';
							if ( empty( $help_tab['content'] ) || ! is_array( $help_tab['content'] ) ) {
								continue;
							}
							if ( ! empty( $help_tab['strong'] ) ) {
								$content .= '<p><strong>' . $help_tab['strong'] . '</strong></p>';
							}
							foreach ( $help_tab['content'] as $tab_content ) {
								if ( is_array( $tab_content ) ) {
									$content .= '<ul><li>' . join( '</li><li>', $tab_content ) . '</li></ul>';
								} else {
									$content .= '<p>' . $tab_content . '</p>';
								}
							}
							$help_tab['content'] = $content;
							if ( 'add_help_tab' == $key ) {
								$screen->add_help_tab( $help_tab );
							} else {
								$screen->set_help_sidebar( $content );
							}
						}
					}
				}
			}
		}
	}


	/**
	 * Disable the quick edit row action
	 *
	 * @param array $actions list of available row actions
	 *
	 * @return array           the new list
	 * @since 2.0.0
	 *
	 * @package WP Idea Stream
	 * @subpackage admin/admin
	 *
	 */
	public static function amapress_row_actions(
		$actions = array(), $post = null
	) {
		$types = AmapressEntities::getPostTypes();
		$pt    = amapress_simplify_post_type( $post->post_type );
		if ( empty( $post ) || ! array_key_exists( $pt, $types ) ) {
			return $actions;
		}

		$type = $types[ $pt ];
//        if (isset($type['row_actions']) && is_array($type['row_actions'])) {
//            foreach ($type['row_actions'] as $row_action_name => $row_action_value) {
//                if (is_callable($row_action_value)) {
//                    $actions[$row_action_name] = call_user_func($row_action_value, $post);
//                } else {
//                    $actions[$row_action_name] = $row_action_value;
//                }
//            }
//        }
		/**
		 * I don't know yet if inline edit is well supported by the plugin, so if you
		 * want to test, just return true to this filter
		 * eg: add_filter( 'wp_idea_stream_admin_ideas_inline_edit', '__return_true' );
		 *
		 * @param bool true to allow inline edit, false otherwise (default is false)
		 */
		$keep_inline_edit = apply_filters( "amapress_admin_{$pt}_inline_edit",
			isset( $type['quick_edit'] ) && $type['quick_edit'] === true );
		if ( $keep_inline_edit == true ) {
			return $actions;
		}
		if ( ! empty( $actions['inline hide-if-no-js'] ) ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	public static function amapress_insert_admin_scripts() {
		$plugin_version = AMAPRESS_VERSION;
		global $pagenow;

		if ( 'customize.php' != $pagenow ) {

			if ( self::getOption( 'feedback' ) ) {
				wp_enqueue_style( 'amapress-feedback', plugins_url( '/css/feedback.css', __FILE__ ) );
				wp_enqueue_script( 'amapress-feedback', plugins_url( '/js/feedback.js', __FILE__ ), array( 'jquery' ) );
				//https://html2canvas.hertzen.com/dist/html2canvas.min.js
			}
			wp_enqueue_script( 'autofill-event', plugins_url( '/js/autofill-event.js', __FILE__ ), array( 'jquery' ) );

			wp_enqueue_style( 'amapress-icons', plugins_url( 'css/flaticon.css', __FILE__ ) );
			wp_enqueue_style( 'font-awesome', plugins_url( '/css/font-awesome.min.css', __FILE__ ) );
			wp_enqueue_style( 'select2', plugins_url( '/css/select2/select2.min.css', __FILE__ ) );
			wp_enqueue_script( 'select2', plugins_url( '/js/select2/select2.full.min.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'select2cb', plugins_url( '/js/select2/select2.multi-checkboxes.js', __FILE__ ), array( 'select2' ) );
			wp_enqueue_style( 'amapress-bo', plugins_url( '/css/bo.css?v=' . $plugin_version, __FILE__ ) );
			wp_enqueue_style( 'amapress-adminbar', plugins_url( '/css/adminbar.css?v=' . $plugin_version, __FILE__ ) );
			wp_enqueue_script( 'amapress-script', plugins_url( '/js/amapress.js?v=' . $plugin_version, __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'jquery-ui-selectmenu' );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'contrat-status-handle', plugin_dir_url( __FILE__ ) . 'js/ajax-contrats.js', array( 'jquery' ) );
			wp_localize_script( 'contrat-status-handle', 'update_contrat_status', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'paiement-status-handle', plugin_dir_url( __FILE__ ) . 'js/ajax-paiements.js', array( 'jquery' ) );
			wp_localize_script( 'paiement-status-handle', 'update_paiement_status', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			wp_enqueue_script( 'inscriptions-handle', plugin_dir_url( __FILE__ ) . 'js/ajax-inscriptions.js', array( 'jquery' ) );
			wp_localize_script( 'inscriptions-handle', 'inscriptions', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			wp_enqueue_script( 'jquery.contextMenu', plugin_dir_url( __FILE__ ) . 'js/jquery.contextMenu.min.js', array( 'jquery' ) );
			wp_enqueue_style( 'jquery.contextMenu', plugin_dir_url( __FILE__ ) . 'css/contextMenu/jquery.contextMenu.min.css' );

			global $wp_scripts;
			// get registered script object for jquery-ui
			$ui = $wp_scripts->query( 'jquery-ui-core' );

			// tell WordPress to load the Smoothness theme from Google CDN
			$protocol = is_ssl() ? 'https' : 'http';
			$url      = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
			wp_enqueue_style( 'jquery-ui-smoothness', $url, false, null );

			wp_enqueue_script( 'datatable', plugin_dir_url( __FILE__ ) . 'js/datatables.min.js', array( 'jquery' ), true );
			wp_enqueue_script( 'datatable-row-print-btn', plugin_dir_url( __FILE__ ) . 'js/dt.rowgroup.print.js', array( 'datatable' ), true );
			wp_enqueue_script( 'datatable-rowsGroup', plugin_dir_url( __FILE__ ) . 'js/dt.rowsGroup.js', array( 'datatable' ), true );
			wp_enqueue_style( 'datatable', plugin_dir_url( __FILE__ ) . 'css/datatables.min.css' );
		}

//        global $wp_scripts;
//        var_dump($wp_scripts->queue);
//        die();
	}

	public static function amapress_insert_front_scripts() {
		$plugin_version = AMAPRESS_VERSION;

		if ( self::getOption( 'feedback' ) ) {
			wp_enqueue_style( 'amapress-feedback', plugins_url( '/css/feedback.css', __FILE__ ) );
			wp_enqueue_script( 'amapress-feedback', plugins_url( '/js/feedback.js', __FILE__ ), array( 'jquery' ) );
		}

		wp_enqueue_style( 'amapress-grid', plugins_url( '/css/grid.css', __FILE__ ) );

//		wp_enqueue_style( 'bootstrapstyle', plugins_url( '/css/bootstrap.min.css', __FILE__ ) );
//		wp_enqueue_script( 'responsive-tabs', plugins_url( '/js/responsive-tabs.js', __FILE__ ), array( 'bootstrap' ), true );
//		wp_enqueue_script( 'bootstrap', plugins_url( '/js/bootstrap.min.js', __FILE__ ), array( 'jquery' ), true );
		wp_enqueue_script( 'autofill-event', plugins_url( '/js/autofill-event.js', __FILE__ ), array( 'jquery' ) );

		wp_enqueue_style( 'select2', plugins_url( '/css/select2/select2.min.css', __FILE__ ) );
		wp_enqueue_script( 'select2', plugins_url( '/js/select2/select2.full.min.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'select2cb', plugins_url( '/js/select2/select2.multi-checkboxes.js', __FILE__ ), array( 'select2' ) );
		wp_enqueue_style( 'font-awesome', plugins_url( '/css/font-awesome.min.css', __FILE__ ) );
//		wp_enqueue_style( 'awesome-bootstrap-checkbox-css', plugins_url( 'css/awesome-bootstrap-checkbox.css', __FILE__ ) );
		wp_enqueue_style( 'amapress-css', plugins_url( 'css/front.css?v=' . $plugin_version, __FILE__ ) );
		wp_enqueue_style( 'amapress-adminbar', plugins_url( '/css/adminbar.css?v=' . $plugin_version, __FILE__ ) );
		wp_enqueue_style( 'amapress-icons-css', plugins_url( 'css/flaticon.css', __FILE__ ) );
		wp_enqueue_script( 'inscriptions-handle', plugin_dir_url( __FILE__ ) . 'js/ajax-inscriptions.js', array( 'jquery' ) );
		wp_localize_script( 'inscriptions-handle', 'inscriptions', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		wp_enqueue_script( 'clipboard', plugin_dir_url( __FILE__ ) . 'js/clipboard.min.js', array( 'jquery' ) );
		//
		wp_enqueue_script( 'isotope', plugin_dir_url( __FILE__ ) . 'js/isotope.pkgd.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'slick', plugin_dir_url( __FILE__ ) . 'js/slick/slick.min.js', array( 'jquery' ) );
		wp_enqueue_style( 'slick', plugin_dir_url( __FILE__ ) . 'css/slick/slick.css' );
		wp_enqueue_style( 'slick-theme', plugin_dir_url( __FILE__ ) . 'css/slick/slick-theme.css', array( 'slick' ) );

		wp_enqueue_script( 'moment', plugin_dir_url( __FILE__ ) . 'js/moment.min.js' );
		wp_enqueue_script( 'fullcalendar', plugin_dir_url( __FILE__ ) . 'js/fullcalendar/fullcalendar.min.js', array(
			'jquery',
			'moment'
		) );
		wp_enqueue_script( 'fullcalendar-locale', plugin_dir_url( __FILE__ ) . 'js/fullcalendar/locale-all.js', array(
			'fullcalendar'
		) );
		wp_enqueue_style( 'fullcalendar', plugin_dir_url( __FILE__ ) . 'css/fullcalendar/fullcalendar.min.css' );
		wp_enqueue_style( 'fullcalendar-print', plugin_dir_url( __FILE__ ) . 'css/fullcalendar/fullcalendar.print.min.css', array( 'fullcalendar' ), false, 'print' );
		wp_enqueue_script( 'ical', plugin_dir_url( __FILE__ ) . 'js/fullcalendar/ical.min.js', array(
			'fullcalendar'
		) );

		wp_enqueue_script( 'leaflet', plugin_dir_url( __FILE__ ) . 'js/leaflet.js' );
		wp_enqueue_style( 'leaflet', plugin_dir_url( __FILE__ ) . 'css/leaflet.css' );

		wp_enqueue_script( 'datatable', plugin_dir_url( __FILE__ ) . 'js/datatables.min.js', array( 'jquery' ), true );
		wp_enqueue_script( 'datatable-row-print-btn', plugin_dir_url( __FILE__ ) . 'js/dt.rowgroup.print.js', array( 'datatable' ), true );
		wp_enqueue_script( 'datatable-rowsGroup', plugin_dir_url( __FILE__ ) . 'js/dt.rowsGroup.js', array( 'datatable' ), true );
		wp_enqueue_style( 'datatable', plugin_dir_url( __FILE__ ) . 'css/datatables.min.css' );
		wp_enqueue_script( 'amapress-front', plugins_url( '/js/front.js?v=' . $plugin_version, __FILE__ ), array( 'jquery' ), true );
		wp_localize_script( 'amapress-front', 'amapress', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		wp_enqueue_script( 'jquery.validate', plugins_url( '/js/jquery-validate/jquery.validate.min.js', AMAPRESS__PLUGIN_FILE ), array( 'jquery' ) );
		wp_enqueue_script( 'jquery.validate-fr', plugins_url( '/js/jquery-validate/localization/messages_fr.js', AMAPRESS__PLUGIN_FILE ), array( 'jquery.validate' ) );
		wp_enqueue_script( 'jquery.ui.datepicker.validation', plugins_url( '/js/jquery.ui.datepicker.validation.min.js', AMAPRESS__PLUGIN_FILE ), array(
			'jquery.validate',
			'jquery-ui-datepicker'
		) );

		wp_enqueue_style( 'dashicons' );
	}

	public static function to_title(
		$post
	) {
		return $post->post_title;
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 * This function is hooked into the 'wp_dashboard_setup' action below.
	 */
	public static function add_dashboard_widgets() {
		wp_add_dashboard_widget(
			'amapress_entities_dashboard_widget',         // Widget slug.
			__( 'Information Amapress', 'amapress' ),         // Title.
			array( 'Amapress', 'amapress_entities_dashboard_widget_function' ) // Display function.
		);
		wp_add_dashboard_widget(
			'amapress_this_month_dashboard_widget',         // Widget slug.
			__( 'Ce mois-ci avec Amapress', 'amapress' ),         // Title.
			array( 'Amapress', 'amapress_this_month_dashboard_widget_function' ) // Display function.
		);
	}

	static function amapress_this_month_dashboard_widget_function() {
		$start_date    = self::start_of_week( amapress_time() );
		$end_date      = self::add_a_month( amapress_time() );
		$month_paniers = array_filter( AmapressPanier::get_paniers( $start_date, $end_date ),
			function ( $panier ) {
				return $panier->getContrat_instance()->hasPanier_CustomContent();
			} );
		$month_dists   = AmapressDistribution::get_distributions( $start_date, $end_date );
		$month_visites = AmapressVisite::get_visites( $start_date, $end_date );

		echo '<p>' . self::makeLink(
				admin_url( 'admin.php?page=contrats_quantites_next_distrib' ),
				__( 'Quantités à la prochaine distribution', 'amapress' ), true, true ) . '</p>';

		if ( ! empty( $month_paniers ) ) {
			echo '<p>' . __( 'Contenu de paniers à remplir :', 'amapress' ) . '</p>';
			echo '<ul>';
			foreach ( $month_paniers as $panier ) {
				if ( ! $panier->getContrat_instance()->hasPanier_CustomContent() ) {
					continue;
				}
				$prods = AmapressPaniers::get_selected_produits( $panier->ID );
				$cnt   = count( $prods );
				$url   = admin_url( 'post.php?post=' . $panier->ID . '&action=edit' );
				if ( $cnt == 0 ) {
					echo "<li><a href='$url'>{$panier->getTitle()}</a> - Pas de produits sélectionnés</li>";
				} else {
					echo "<li><a href='$url'>{$panier->getTitle()}</a> - $cnt produit(s)</li>";
				}
			}
			echo '</ul>';
		}

		if ( empty( $month_dists ) ) {
			echo '<i>' . __( 'Pas de distribution ce mois-ci', 'amapress' ) . '</i>';
		} else {
			echo '<p>' . sprintf(
					__( 'Distributions / <a target="_blank" href="%s">Inscriptions des responsables</a> :', 'amapress' ),
					self::get_inscription_distrib_page_href() ) . '</p>';
			echo '<ul>';
			foreach ( $month_dists as $dist ) {
				$url   = admin_url( 'post.php?post=' . $dist->ID . '&action=edit' );
				$resps = $dist->getResponsablesIds();
				$req   = AmapressDistributions::get_required_responsables( $dist->ID );
				echo '<li><a target="_blank" href=' . $url . '>';
				if ( count( $resps ) == 0 ) {
					echo $dist->getTitle() . '</a> - <strong style="color:red">' . __( 'Pas de responsables', 'amapress' ) . '</strong>';
				} elseif ( $req > count( $resps ) ) {
					$miss = $req - count( $resps );
					echo $dist->getTitle() . '</a> - <strong>' . sprintf( __( '%d responsable(s) manquants', 'amapress' ), $miss ) . '</strong>';
				} else {
					echo $dist->getTitle() . '</a> - ' . __( 'Complet', 'amapress' );
				}
				echo ' / ' . self::makeLink( $dist->getListeEmargementHref(), __( 'Liste d\'émargement', 'amapress' ), true, true );
				echo '</li>';
			}
			echo '</ul>';
		}

		echo '<p>' . __( 'Visites à la ferme :', 'amapress' ) . '</p>';
		if ( count( $month_visites ) == 0 ) {
			echo '<i>' . __( 'Pas de visite à la ferme ce mois-ci', 'amapress' ) . '</i>';
		} else {
			echo '<ul>';
			foreach ( $month_visites as $dist ) {
				$url   = admin_url( 'post.php?post=' . $dist->ID . '&action=edit' );
				$resps = AmapressDistributions::get_visite_participants( $dist->ID );
				if ( count( $resps ) == 0 ) {
					echo "<li><a href='$url'>{$dist->getTitle()}</a> - <strong style='color:red'>" . __( 'Pas de participants', 'amapress' ) . '</strong></li>';
				} else {
					$cnt = count( $resps );
					echo "<li><a href='$url'>{$dist->getTitle()}</a> - " . sprintf( __( '%d participant(s)', 'amapress' ), $cnt ) . '</li>';
				}
			}
			echo '</ul>';
		}
	}

	static function amapress_entities_dashboard_widget_function() {
		$lieux    = self::get_lieux();
		$contrats = AmapressContrats::get_active_contrat_instances();

		//contrats
		$adm = admin_url( 'edit.php?post_type=amps_contrat_inst' );
		$cnt = count( $contrats );
		echo sprintf( __( '<p><a href=\'%1$s\'>(%2$s)</a> contrats actifs. Contrat(s) ouvert(s) à l\'inscription :</p>', 'amapress' ), $adm, $cnt );
		echo wpautop( amapress_replace_mail_placeholders( '%%contrats_en_cours_by_inscr_end%%', null, null ) );

		//adhérents
		$cnt = get_posts_count(
			array(
				'posts_per_page' => - 1,
				'post_type'      => 'amps_adh_pmt',
				'amapress_date'  => 'active'
			)
		);
		$adm = admin_url( 'edit.php?post_type=amps_adh_pmt&amapress_date=active' );
		echo sprintf( __( '<p><a href=\'%1$s\'>(%2$s)</a> adhésions</p>', 'amapress' ), $adm, $cnt );

		//inscriptions
		$ads = get_posts(
			array(
				'posts_per_page' => - 1,
				'post_type'      => 'amps_adhesion'
			)
		);
		$adm = admin_url( 'edit.php?post_type=amps_adhesion' );
		$cnt = count( $ads );
		echo sprintf( __( '<p><a href=\'%1$s\'>(%2$s)</a> inscriptions :</p>', 'amapress' ), $adm, $cnt );
		echo '<ul>';
		foreach ( $lieux as $lieu ) {
			$ads_lieu = get_posts(
				array(
					'post_type'      => 'amps_adhesion',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						array(
							'key'     => 'amapress_adhesion_lieu',
							'value'   => $lieu->ID,
							'compare' => '=',
						)
					)
				)
			);
			$adm      = admin_url( 'edit.php?post_type=amps_adhesion&meta_key=amapress_adhesion_lieu&meta_value=' . $lieu->ID );
			$cnt      = count( $ads_lieu );
			echo sprintf( __( '<li>%1$s: <a href=\'%2$s\'>(%3$s)</a> inscriptions</li>', 'amapress' ), $lieu->getTitle(), $adm, $cnt );
		}
		echo '</ul>';
		echo '<ul>';
		foreach ( $contrats as $contrat ) {
			$ads_contrat = get_posts(
				array(
					'post_type'      => 'amps_adhesion',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						array(
							'key'     => 'amapress_adhesion_contrat_instance',
							'value'   => $contrat->ID,
							'compare' => '=',
						)
					)
				)
			);
			$adm         = admin_url( 'edit.php?post_type=amps_adhesion&meta_key=amapress_adhesion_contrat_instance&meta_value=' . $contrat->ID );
			$cnt         = count( $ads_contrat );
			echo sprintf( __( '<li>%1$s: <a href=\'%2$s\'>(%3$s)</a> inscriptions</li>', 'amapress' ), $contrat->getTitle(), $adm, $cnt );
		}
		echo '</ul>';

		//lieux distrib
		$adm = admin_url( 'edit.php?post_type=amps_lieu' );
		$cnt = count( $lieux );
		echo sprintf( __( '<p><a href=\'%1$s\'>(%2$s)</a> lieu(x) de distribution</p>', 'amapress' ), $adm, $cnt );

		//producteurs
		$cnt = get_posts_count(
			array(
				'posts_per_page' => - 1,
				'post_type'      => 'amps_producteur'
			)
		);
		$adm = admin_url( 'edit.php?post_type=amps_producteur' );
		echo sprintf( __( '<p><a href=\'%1$s\'>(%2$s)</a> producteurs</p>', 'amapress' ), $adm, $cnt );
	}


	static function remove_dashboard_meta() {
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );//since 3.8
	}

	public static function amapress_disable_months_dropdown(
		$post_type
	) {
		$types = AmapressEntities::getPostTypes();
		$pt    = amapress_simplify_post_type( get_post_type() );
		if ( ! $pt || ! array_key_exists( $pt, $types ) ) {
			return false;
		}

		$t = $types[ $pt ];

		return isset( $t['months_dropdown'] ) && $t['months_dropdown'] !== true;
	}

	/**
	 * Initializes WordPress hooks
	 */
	public static function init_hooks() {
		self::$initiated = true;
		add_action( 'wp_dashboard_setup', array( 'Amapress', 'add_dashboard_widgets' ) );
		add_action( 'admin_init', array( 'Amapress', 'remove_dashboard_meta' ) );
		add_filter( 'pre_option_default_role', array( 'Amapress', 'amapress_default_role' ) );
		add_action( 'admin_enqueue_scripts', array( 'Amapress', 'amapress_insert_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( 'Amapress', 'amapress_insert_front_scripts' ), 5 );
		add_action( 'admin_head', array( 'Amapress', 'handle_help' ) );
		add_action( 'admin_notices', array( 'Amapress', 'help_admin_notices' ) );
//        add_filter('single_template', array('Amapress', 'amapress_provide_default_single'));
//        add_filter('archive_template', array('Amapress', 'amapress_provide_default_archive'));
		add_filter( 'disable_months_dropdown', array( 'Amapress', 'amapress_disable_months_dropdown' ) );
		//add_action('init', array('Amapress', 'amapress_add_rewrite_rules'), 10, 0);
		// Set-up Action and Filter Hooks
		add_action( 'admin_head-nav-menus.php', array( 'Amapress', 'inject_amapress_menu_meta_box' ) );
		add_filter( 'wp_get_nav_menu_items', array( 'Amapress', 'amapress_menu_filter' ), 10, 3 );
		//add_filter('wp_insert_post_data', array('Amapress', 'amapress_default_post_title'), '99', 2);
		add_filter( 'post_row_actions', array( 'Amapress', 'amapress_row_actions' ), 10, 2 );
		add_filter( 'image_resize_dimensions', array( 'Amapress', 'image_crop_dimensions' ), 10, 6 );
//        add_filter('wp_unique_post_slug', array('Amapress', 'amapress_unique_post_slug'));

	}


//    public static function amapress_unique_post_slug( $slug, $post_ID = null, $post_status = null, $post_type= null, $post_parent = null ) {
//        if ($post_type=='user_commande') {
//            return $post_ID;
//        }
//        return $slug;
//    }

//    public static function amapress_default_post_title($data)
//    {
//        $types = AmapressEntities::getPostTypes();
//        if (!array_key_exists($data['post_type'], $types)) {
//            return $data;
//        }
//
//        $t = $types[$data['post_type']];
//        if (array_key_exists('title_format', $t) && is_callable($t['title_format'])) {
//            $data['post_title'] = call_user_func($t['title_format'], $data);
//        }
//        if (array_key_exists('slug_format', $t)) {
//            if (is_callable($t['slug_format'])) {
//                $data['post_name'] = wp_unique_post_slug(call_user_func($t['slug_format'], $data));
//            } else if ($t['slug_format'] == 'from_title') {
//                $data['post_name'] = wp_unique_post_slug(sanitize_title($data['post_title']));
//            } else if ($t['slug_format'] == 'from_id') {
//                $data['post_name'] = wp_unique_post_slug(sanitize_title($data['ID']));
//            }
//        }
//
//        //wp_unique_post_slug( sanitize_title( $article_title ) )
//
//        return $data;
//    }

	public static function inject_amapress_menu_meta_box() {
		add_meta_box( 'add-amapress',
			__( 'Amapress', 'default' ),
			array( __CLASS__, 'wp_nav_menu_amapress_meta_box' ),
			'nav-menus', 'side', 'default' );
	}

	/* render custom post type archives meta box */
	public static function wp_nav_menu_amapress_meta_box() {
		/* get custom post types with archive support */

		$items  = array();
		$items2 = array();
		$i      = 10000;

		foreach ( AmapressEntities::getPostTypes() as $post_type => $post_conf ) {
			if ( empty( $post_conf['public'] ) || $post_conf['public'] !== true ) {
				continue;
			}

			$item = new stdClass();

			$item->object_id        = $i ++;
			$item->ID               = 0;
			$item->db_id            = 0;
			$item->post_parent      = 0;
			$item->object           = 'archive_' . $post_type;
			$item->menu_item_parent = 0;
			$item->type             = 'amapress-custom';
			$item->type_label       = __( 'Archives', 'amapress' );
			$item->title            = $post_conf['plural'];
			$item->url              = get_post_type_archive_link( amapress_unsimplify_post_type( $post_type ) );
			$item->target           = '';
			$item->attr_title       = '';
			if ( empty( $item->classes ) ) {
				$item->classes = array();
			}
			$item->xfn = '';

			$items[] = $item;

			$item = new stdClass();

			$item->object_id        = $i ++;
			$item->ID               = 0;
			$item->db_id            = 0;
			$item->object           = 'latest_' . $post_type;
			$item->post_parent      = 0;
			$item->menu_item_parent = 0;
			$item->type             = 'amapress-custom-latest';
			$item->title            = sprintf( __( 'Derniers %s', 'amapress' ), $post_conf['plural'] );
			$item->type_label       = __( 'Récents', 'amapress' );
			$item->url              = get_post_type_archive_link( amapress_unsimplify_post_type( $post_type ) );
			$item->target           = '';
			$item->attr_title       = '';
			if ( empty( $item->classes ) ) {
				$item->classes = array();
			}
			$item->xfn = '';

			$items[] = $item;
		}

		$item = new stdClass();

		$item->object_id        = $i ++;
		$item->ID               = 0;
		$item->db_id            = 0;
		$item->object           = 'latest_post';
		$item->menu_item_parent = 0;
		$item->post_parent      = 0;
		$item->type             = 'amapress-custom-latest';
		$item->title            = __( 'Derniers articles', 'amapress' );
		$item->type_label       = __( 'Derniers articles', 'amapress' );
		$item->url              = get_post_type_archive_link( 'post' );
		$item->target           = '';
		$item->attr_title       = '';
		if ( empty( $item->classes ) ) {
			$item->classes = array();
		}
		$item->xfn = '';

		$items[] = $item;

		$item = new stdClass();

		$item->object_id        = $i ++;
		$item->ID               = 0;
		$item->db_id            = 0;
		$item->object           = 'next_distribution';
		$item->menu_item_parent = 0;
		$item->post_parent      = 0;
		$item->type             = 'amapress-next-distribution';
		$item->title            = __( 'Prochaine distribution', 'amapress' );
		$item->type_label       = __( 'Prochaine distribution', 'amapress' );
		$item->url              = get_post_type_archive_link( AmapressDistribution::INTERNAL_POST_TYPE );
		$item->target           = '';
		$item->attr_title       = '';
		if ( empty( $item->classes ) ) {
			$item->classes = array();
		}
		$item->xfn = '';

		$items[] = $item;

		foreach ( AmapressEntities::$special_pages as $post_type => $post_conf ) {
			$item = new stdClass();

			$item->object_id        = $i ++;
			$item->ID               = 0;
			$item->db_id            = 0;
			$item->object           = 'amapress_link_' . trim( $post_type, '/' );
			$item->menu_item_parent = 0;
			$item->post_parent      = 0;
			$item->type             = 'amapress-custom-link';
			$item->title            = $post_conf['name'];
			$item->url              = $post_type;
			$item->target           = '';
			$item->attr_title       = '';
			if ( empty( $item->classes ) ) {
				$item->classes = array();
			}
			$item->xfn = '';

			$items2[] = $item;
		}

		$walker                     = new Walker_Nav_Menu_Checklist( array() );
		$walker2                    = new Walker_Nav_Menu_Checklist( array() );

		?>
        <div id="amapress" class="posttypediv">
            <h4><?php _e( 'Types', 'amapress' ); ?></h4>

            <div id="tabs-panel-amapress" class="tabs-panel tabs-panel-active">
                <ul id="amapress-checklist" class="categorychecklist form-no-clear">
					<?php
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $items ), 0, (object) array( 'walker' => $walker ) );
					?>
                </ul>
            </div>
            <!-- /.tabs-panel -->
            <h4><?php _e( 'Liens', 'amapress' ); ?></h4>

            <div id="tabs-panel-amapress-links" class="tabs-panel tabs-panel-active">
                <ul id="amapress-checklist-links" class="categorychecklist form-no-clear">
					<?php
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $items2 ), 0, (object) array( 'walker' => $walker2 ) );
					?>
                </ul>
            </div>
            <!-- /.tabs-panel -->
        </div>
        <p class="button-controls">
      <span class="add-to-menu">
        <input type="submit" class="button-secondary submit-add-to-menu right"
               value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-amapress-menu-item" id="submit-amapress"/>
        <span class="spinner"></span>
      </span>
        </p>
		<?php
	}


	/* take care of the urls */
	public static function amapress_menu_filter(
		$items, $menu, $args
	) {
		$menu_order = count( $items ); /* Offset menu order */
		$i          = 20000;

		/* alter the URL for cpt-archive objects */
		foreach ( $items as &$item ) {
			//var_dump($item->type);
			if ( $item->type == 'amapress-custom' ) {

				foreach ( AmapressEntities::getPostTypes() as $post_type => $post_conf ) {
					if ( $item->object == 'archive_' . $post_type ) {
						$item->url = get_post_type_archive_link( amapress_unsimplify_post_type( $post_type ) );
						break;
					}
				}
			} elseif ( $item->type == 'amapress-custom-latest' && ! is_admin() ) {
				amapress_ensure_no_cache();

				$types = array_merge( [ 'post' ], array_keys( AmapressEntities::getPostTypes() ) );
				foreach ( $types as $post_type ) {
					if ( $item->object == 'latest_' . $post_type ) {
						$item->url = get_post_type_archive_link( amapress_unsimplify_post_type( $post_type ) );
						break;
					}
				}
			} elseif ( $item->type == 'amapress-next-distribution' ) {
				amapress_ensure_no_cache();

				$next_distribs = AmapressDistribution::getUserNextDistributions( null, null, 1 );
				/** @var AmapressDistribution $next_distrib */
				$next_distrib = array_shift( $next_distribs );
				if ( ! $next_distrib ) {
					$next_distrib = AmapressDistribution::getNextDistribution();
				}
				if ( $next_distrib ) {
					$item->url        = $next_distrib->getPermalink();
					$item->attr_title = $next_distrib->getTitle();
				}
			} elseif ( $item->type == 'amapress-custom-link' ) {
				foreach ( AmapressEntities::$special_pages as $post_type => $post_conf ) {
					if ( $item->object == 'amapress_link_' . trim( $post_type, '/' ) ) {
						$item->url = $post_type;
						break;
					}
				}
			}

			/* set current */
			if ( get_query_var( 'post_type' ) == $item->type ) {
				$item->classes [] = 'current-menu-item';
				$item->current    = true;
			}
		}

		$child_items = array();
		foreach ( $items as &$item ) {
			if ( $item->type == 'amapress-custom-latest' && ! is_admin() ) {
				$types = array_merge( [ 'post' ], array_keys( AmapressEntities::getPostTypes() ) );
				foreach ( $types as $post_type ) {
					if ( $item->object == 'latest_' . $post_type ) {
						if ( ! is_customize_preview() ) {
							$pt    = AmapressEntities::getPostType( $post_type );
							$query = [
								'post_type'      => amapress_unsimplify_post_type( $post_type ),
								'posts_per_page' => 10,
								'amapress_date'  => 'active'
							];
							if ( isset( $pt ) ) {
								if ( isset( $pt['default_orderby'] ) ) {
									$default_orderby = $pt['default_orderby'];
									if ( false !== strpos( $default_orderby, 'amapress_' ) ) {
										$query['orderby']  = 'meta_value_num';
										$query['meta_key'] = $default_orderby;
									} else {
										$query['orderby'] = 'meta_value_num';
									}
								}
								if ( isset( $pt['default_order'] ) ) {
									$query['order'] = $pt['default_order'];
								}
							}
							foreach (
								get_posts( $query ) as $post
							) {
								$subitem                   = new stdClass();
								$subitem->object_id        = $i ++;
								$subitem->ID               = 0;
								$subitem->db_id            = 0;
								$subitem->post_parent      = 0;
								$subitem->menu_item_parent = $item->ID;
								$subitem->post_type        = 'nav_menu_item';
								$subitem->object           = 'custom';
								$subitem->type             = 'custom';
								$subitem->menu_order       = ++ $menu_order;
								$subitem->title            = $post->post_title;
								$subitem->url              = get_permalink( $post->ID );
								$subitem->target           = '';
								$subitem->attr_title       = '';
								if ( empty( $post->classes ) ) {
									$subitem->classes = array();
								}
								$subitem->xfn = '';
								/* add children */
								$child_items [] = $subitem;
							}
						}
						break;
					}
				}
			}

			if ( empty( $item->url ) ) {
				$item->url = '#';
			}
		}
		$items = array_merge( $items, $child_items );

		return $items;
	}


	public static function day_name(
		$day
	) {
		switch ( $day ) {
			case 1:
				return __( 'Lundi', 'amapress' );
			case 2:
				return __( 'Mardi', 'amapress' );
			case 3:
				return __( 'Mercredi', 'amapress' );
			case 4:
				return __( 'Jeudi', 'amapress' );
			case 5:
				return __( 'Vendredi', 'amapress' );
			case 6:
				return __( 'Samedi', 'amapress' );
			case 0:
				return __( 'Dimanche', 'amapress' );
		}

		return '';
	}

	public static function getIDs(
		$objects
	) {
		return array_map( function ( $c ) {
			return $c->ID;
		}, $objects );
	}

	public static function get_contrats_list(
		$producteur_id = null
	) {
		$contrats = AmapressContrats::get_contrats( $producteur_id, true, true );
		if ( empty( $contrats ) ) {
			return '<p class="">' . __( 'Aucun contrat n\'est configuré', 'amapress' ) . '</p>';
		}
		$ret                 = '<ul class="contrat-list">';
		$active_contrats_ids = array_map( function ( $a ) {
			/** @var AmapressAdhesion $a */
			return $a->getModelId();
		}, AmapressAdhesion::getUserActiveAdhesions() );
		$used                = array();
		foreach ( $contrats as $contrat ) {
			if ( in_array( $contrat->ID, $used ) ) {
				continue;
			}
			$used[] = $contrat->ID;
			$lbl    = in_array( $contrat->ID, $active_contrats_ids ) ?
				self::getOption( 'front_produits_button_text_if_adherent', __( 'Adhérent', 'amapress' ) ) :
				self::getOption( 'front_produits_button_text_if_not_adherent', __( 'Découvrir', 'amapress' ) );
			//$btn_url = in_array($contrat->ID, $active_contrats) ? trailingslashit(get_post_permalink($contrat->ID)).'details/' : __('Je m\'inscris', 'amapress');
			$btn_url = trailingslashit( get_post_permalink( $contrat->ID ) );
			$url     = amapress_get_avatar_url( $contrat->ID, null, 'produit-thumb', 'default_contrat.jpg' );
			$ret     .= '<li>
                <div class="contrat-img"><img src="' . $url . '" alt="' . esc_attr( $contrat->getTitle() ) . '"  /></div>
                <div class="contrat-desc">
                    <div>
                    <div class="contrat-link">' . esc_html( $contrat->getTitle() ) . '</div>
                    <div><a href="' . $btn_url . '" class="btn btn-default btn-abonnement">' . esc_html( $lbl ) . '</a></div>
                    </div>
                </div>
            </li>';
		}
//		<div class="contrat-link"><a href="' . get_permalink( $contrat->ID ) . '" >' . $contrat->getTitle() . '</a></div>
		$ret .= '</ul>';

		return $ret;
	}

	public static function get_know_more(
		$url
	) {
		return '<p class="know-more"><a href="' . $url . '"><i class="fa fa-star-o"></i>&#xA0;' . __( 'En savoir plus', 'amapress' ) . '</a></p>';
	}

//    public static function amapress_produit_cell($produit, $add_class)
//    {
//
//    }


	public static function do_nothing(
		$user_id
	) {
		return true;
	}

	public static function make_date_and_hour(
		$date, $time
	) {
		return mktime( gmdate( 'H', $time ), gmdate( 'i', $time ), gmdate( 's', $time ), gmdate( 'n', $date ), gmdate( 'j', $date ), gmdate( 'Y', $date ) );
	}

	/** @param mixed $args,...
	 * @return string
	 */
	public static function coalesce_icons(
		$args
	) {
		foreach ( func_get_args() as $name_or_url ) {
			$res = self::get_icon( $name_or_url );
			if ( ! empty( $res ) ) {
				return $res;
			}
		}

		return null;
	}

	public static function get_icon(
		$name, $alt = ''
	) {
		if ( empty( $name ) ) {
			return '';
		}
		if ( preg_match( '/\/|\.|\</', $name ) ) {
			return $name;
		}

		$alt = esc_attr( $alt );
		if ( preg_match( '/fa-|glyphicon-|ion-|wi-|map-icon-|octicon-|typcn-|el-|md-/', $name ) ) {
			return "<i class='$name' title='$alt'></i>";
		} else {
			return "<span class='$name' title='$alt'></span>";
		}
	}

	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 *
	 * @static
	 */
	public static function plugin_activation() {
		//if ( version_compare( $GLOBALS['wp_version'], AKISMET__MINIMUM_WP_VERSION, '<' ) ) {
		//}

		if ( - 1 === version_compare( phpversion(), AMAPRESS_MINIMUM_PHP_VERSION ) ) {
			/* translators: 1: Current PHP version 2: Required PHP version. */
			die( sprintf( esc_html__( 'Votre version de PHP (%1$s) est en dessous de la version requise par Amapress : %2$s.', 'amapress' ), esc_html( phpversion() ), esc_html( AMAPRESS_MINIMUM_PHP_VERSION ) ) );
		}
	}

	/**
	 * Removes all connection options
	 *
	 * @static
	 */
	public static function plugin_deactivation() {
	}

	public static function sendPdfFromHtml(
		$html, $pdf_name = null, $orientation = 'P', $format = 'A4'
	) {
		self::createPdfFromHtml( $html, $pdf_name, 'D', $orientation, $format );
	}

	public static function getPdfFromHtml(
		$html, $pdf_name = null, $orientation = 'P', $format = 'A4'
	) {
		return self::createPdfFromHtml( $html, $pdf_name, 'S', $orientation, $format );
	}

	public static function createICalForEventsAsMailAttachment(
		Amapress_EventEntry $event, $is_cancel
	) {
		$ical     = Amapress_Agenda_ICAL_Export::getICALFromEvents( [ $event ], '',
			$is_cancel ? 'cancel' : 'request' );
		$filename = self::getAttachmentDir() . '/' . $event->getType() . '-' . uniqid() . '.ics';

		file_put_contents( $filename, $ical );

		return $filename;
	}

	public static function createPdfFromHtmlAsMailAttachment(
		$html, $pdf_name = null, $orientation = 'P', $format = 'A4'
	) {
		$pdf_bytes = self::createPdfFromHtml( $html, $pdf_name, 'S', $orientation, $format );
		$filename  = self::getAttachmentDir() . '/' . $pdf_name;

		file_put_contents( $filename, $pdf_bytes );

		return $filename;
	}

	public static function createPdfFromHtml(
		$html, $pdf_name = null, $dest = false, $orientation = 'P', $format = 'A4'
	) {
		require_once AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php';
		if ( isset( $_GET['test'] ) ) {
			wp_die( $html );
		}
		try {
			//$html = '<style>script, button { display: none; }</style>' . $html;
			$pdf = new TCPDF( $orientation, 'pt', $format, true, 'UTF-8', false );
			$pdf->SetCreator( 'TcPDF' );
			$pdf->SetAuthor( 'Amapress' );
			$pdf->SetTitle( $pdf_name );

//			$pdf->SetAutoPageBreak( true, 36 );
			$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );
			$pdf->setPrintHeader( false );
			$pdf->setPrintFooter( false );

			$pdf->setFooterMargin( 0 );
			$pdf->setHeaderMargin( 0 );

			$pdf->SetMargins( 15, 15, - 1, true );
			$pdf->AddPage();
			@$pdf->writeHTML( $html );

			return $pdf->Output( $pdf_name, $dest );
		} catch ( Exception $exception ) {
			amapress_dump( esc_html( $html ) );
			wp_die( $exception );
		}
	}

	public static function createXLSXFromDatatable(
		$columns, $data, $title = null
	) {
		$dt_options = array(
			'paging'       => false,
			'init_as_html' => true,
			'no_script'    => true,
			'bSort'        => false,
		);
		$html       = amapress_get_datatable(
			uniqid( 'ht' ),
			$columns, $data,
			$dt_options
		);

		return self::createXLSXFromHtml( $html, $title );
	}

	public static function createXLSXFromDatatableAsMailAttachment(
		$columns, $data, $excel_file_name, $title
	) {
		$objPHPExcel = self::createXLSXFromDatatable( $columns, $data, $title );

		return self::createXLSXFromPHPExcelAsMailAttachment( $objPHPExcel, $excel_file_name );
	}

	public static function createXLSXFromPHPExcelAsMailAttachment(
		$objPHPExcel, $excel_file_name
	) {
		$filename = self::getAttachmentDir() . '/' . $excel_file_name;

		return self::createXLSXFromPHPExcelAsFile( $objPHPExcel, $filename );
	}

	public static function createXLSXFromPHPExcelAsFile(
		$objPHPExcel, $full_excel_file_name
	) {
		$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( $full_excel_file_name );

		return $full_excel_file_name;
	}

	public static function createXLSXFromDatatableAsFile(
		$columns, $data, $full_excel_file_name, $title
	) {
		$objPHPExcel = self::createXLSXFromDatatable( $columns, $data, $title );
		$objWriter   = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( $full_excel_file_name );

		return $full_excel_file_name;
	}

	public static function createXLSXFromHtml(
		$html, $title = null
	) {
		require_once AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php';
		if ( isset( $_GET['test'] ) ) {
			wp_die( $html );
		}
		try {
			$reader = new PHPExcel_Reader_HTML();
			$tmp    = wp_tempnam();
			file_put_contents( $tmp, utf8_decode( $html ) );
			$objPHPExcel = $reader->load( $tmp );
			$objPHPExcel->getProperties()->setCreator( 'Amapress' );
			if ( ! empty( $title ) ) {
				$objPHPExcel->getProperties()->setTitle( $title );
			}

			return $objPHPExcel;
		} catch ( Exception $exception ) {
			amapress_dump( esc_html( $html ) );
			wp_die( $exception );
		}
	}

	public static function sendDocumentFile(
		$full_file_name, $out_file_name
	) {
		if ( ! file_exists( $full_file_name ) ) {
			wp_die( sprintf( __( 'Le fichier %1$s n\'existe pas (%2$s)', 'amapress' ), $out_file_name, $full_file_name ) );
		}
		if ( strpos( $out_file_name, '.docx' ) !== false ) {
			header( 'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document' );
		} elseif ( strpos( $out_file_name, '.xlsx' ) !== false ) {
			header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		} elseif ( strpos( $out_file_name, '.odt' ) !== false ) {
			header( 'Content-Type: application/vnd.oasis.opendocument.text' );
		} elseif ( strpos( $out_file_name, '.pdf' ) !== false ) {
			header( 'Content-Type: application/pdf' );
		} elseif ( strpos( $out_file_name, '.eml' ) !== false ) {
			header( 'Content-Type: message/rfc822' );

		} else {
			header( 'Content-Type: application/octet-stream' );
		}
		header( 'Content-Disposition: attachment;filename="' . $out_file_name . '"' );
		header( 'Content-Length: ' . filesize( $full_file_name ) );
		header( 'Cache-Control: max-age=0' );
		// If you're serving to IE 9, then the following may be needed
		header( 'Cache-Control: max-age=1' );
		// If you're serving to IE over SSL, then the following may be needed
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' ); // Date in the past
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); // always modified
		header( 'Cache-Control: cache, must-revalidate' ); // HTTP/1.1
		header( 'Pragma: public' ); // HTTP/1.0
		readfile( $full_file_name );
		die();
	}

	public static function getPlaceholdersHelpForProperties(
		$props
	) {
		$ret = [];
		foreach ( $props as $prop_name => $prop ) {
			if ( ! isset( $prop['desc'] ) ) {
				continue;
			}

			$ret[ $prop_name ] = $prop['desc'];
		}

		return $ret;
	}

	public static function getPlaceholdersHelpTable(
		$id, $helps, $prop_type_desc,
		$additional_helps = [], $for_mail = true,
		$marker_start = '%%', $marker_end = '%%',
		$show_toggler = true
	) {
		static $id_counter = 0;
		$id_counter ++;

		$final = [];
		foreach ( amapress_replace_mail_placeholders_help( $prop_type_desc, true === $for_mail, true === $for_mail ) as $prop_name => $prop_desc ) {
			$final[ $prop_name ] = $prop_desc;
		}
		foreach ( $helps as $prop_name => $prop_desc ) {
			$final[ $prop_name ] = $prop_desc;
		}
		foreach ( $additional_helps as $prop_name => $prop_desc ) {
			$final[ $prop_name ] = $prop_desc;
		}

		$id  .= $id_counter;
		$ret = '';
		if ( $show_toggler ) {
			$ret .= '<p>' .
			        sprintf( __( 'Consulter les <a href="#" id="show_%s">marqueurs de substitution</a> disponibles (%%%%xxx%%%%)', 'amapress' ),
				        $id ) . '</p>';
		}
		$ret .= '<div id="' . $id . '-container"><table id="' . $id . '" class="placeholders-help display"><thead><tr><th>' . __( 'Placeholder', 'amapress' ) . '</th><th>' . __( 'Description', 'amapress' ) . '</th></tr></thead><tbody>' .
		        implode( '', array_map( function ( $pn, $p ) use ( $marker_start, $marker_end ) {
			        return '<tr><td>' . $marker_start . esc_html( $pn ) . $marker_end . '</td><td>' . esc_html( $p ) . '</td></tr>';
		        }, array_keys( $final ), array_values( $final ) ) )
		        . '</tbody></table></div>';

		if ( $show_toggler ) {
			$ret .= '<style>#' . $id . '-container { display: none; }#' . $id . '-container.opened { display: block; }</style>';
			$ret .= '<script type="text/javascript">jQuery(function($) {$("#' . $id . '-container").addClass("closed");$("#show_' . $id . '").click(function() { $("#' . $id . '-container").toggleClass("opened"); return false; }); });</script>';
		}

		return $ret;
	}

	public static function sendXLSXFromPHPExcelObject(
		$objPHPExcel, $excel_file_name
	) {
		@ob_clean();
		// Redirect output to a client’s web browser (Excel2007)
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="' . $excel_file_name . '"' );
		header( 'Cache-Control: max-age=0' );
		// If you're serving to IE 9, then the following may be needed
		header( 'Cache-Control: max-age=1' );
		// If you're serving to IE over SSL, then the following may be needed
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' ); // Date in the past
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); // always modified
		header( 'Cache-Control: cache, must-revalidate' ); // HTTP/1.1
		header( 'Pragma: public' ); // HTTP/1.0
		$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		self::outputExcel( $objWriter );
		die();
	}

	public static function sendXLSXFromHtml(
		$html, $excel_file_name, $title
	) {
		self::sendXLSXFromPHPExcelObject( self::createXLSXFromHtml( $html, $title ), $excel_file_name );
	}

	public static function createXLSXFromHtmlAsMailAttachment(
		$html, $excel_file_name, $title
	) {
		$objPHPExcel = self::createXLSXFromHtml( $html, $title );
		$filename    = self::getAttachmentDir() . '/' . $excel_file_name;
		$objWriter   = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( $filename );

		return $filename;
	}

	public static function createXLSXFromPostQueryAsMailAttachment(
		$query, $excel_file_name, $title
	) {
		$objPHPExcel = AmapressExport_Posts::generate_phpexcel_sheet( $query, null, $title );
		$filename    = self::getAttachmentDir() . '/' . $excel_file_name;
		$objWriter   = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( $filename );

		return $filename;
	}

	public static function getContratGenericUrl(
		$type = 'default'
	) {
		switch ( $type ) {
			case 'modulables_complet':
				return trailingslashit( AMAPRESS__PLUGIN_URL ) . 'templates/contrat_generique_modulables_complet.docx';
			case 'modulables':
				return trailingslashit( AMAPRESS__PLUGIN_URL ) . 'templates/contrat_generique_modulables.docx';
			case 'simple':
				return trailingslashit( AMAPRESS__PLUGIN_URL ) . 'templates/contrat_generique_simple.docx';
			default:
				return trailingslashit( AMAPRESS__PLUGIN_URL ) . 'templates/contrat_generique.docx';
		}
	}

	public static function getBulletinGenericUrl() {
		return trailingslashit( AMAPRESS__PLUGIN_URL ) . 'templates/bulletin_adhesion_generique.docx';
	}

	public static function cleanFilesOlderThanDays(
		$dir, $days
	) {
		$files = glob( trailingslashit( $dir ) . '*' );
		$now   = time();

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				$filename = basename( $file );
				if ( 'index.php' != $filename && '.htaccess' != $filename ) {
					if ( $now - filemtime( $file ) >= 60 * 60 * 24 * $days ) { // 2 days
						@unlink( $file );
					}
				}
			}
		}
	}

	public static function getArchivesDir() {
		$dir     = wp_upload_dir()['basedir'] . '/amapress-archives/';
		$created = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', 'w' );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', 'w' );
			fclose( $handle );
		}

		return $dir;
	}

	/**
	 * Create direcgtory for attachments
	 *
	 * @return string upload dir
	 */
	public static function getContratDir() {
		$dir     = wp_upload_dir()['basedir'] . '/amapress-contrats/';
		$created = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', 'w' );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', 'w' );
			fclose( $handle );
		}

		return $dir;
	}

	/**
	 * Create direcgtory for attachments
	 *
	 * @return string upload dir
	 */
	public static function getAttachmentDir() {
		$dir     = wp_upload_dir()['basedir'] . '/amapress-mail-attachments/';
		$created = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', 'w' );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', 'w' );
			fclose( $handle );
		}

		return $dir;
	}

	public static function getTempDir() {
		$dir     = wp_upload_dir()['basedir'] . '/amapress-tmp/';
		$created = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', 'w' );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', 'w' );
			fclose( $handle );
		}

		return $dir;
	}

	public static function getRolesLogFile() {
		$dir     = wp_upload_dir()['basedir'] . '/amapress-role-log/';
		$created = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', 'w' );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', 'w' );
			fclose( $handle );
		}

		$log_file = $dir . 'amapress-role-log.log';

		if ( ! file_exists( $log_file ) || 0 == filesize( $log_file ) ) {
			foreach (
				get_users(
					[ 'amapress_role' => 'collectif' ]
				) as $user
			) {
				$amapien = AmapressUser::getBy( $user );
				amapress_log_to_role_log_file( $log_file,
					sprintf( __( '%1$s est "%2$s"', 'amapress' ),
						amapress_get_user_edit_link( $amapien ),
						$amapien->getAmapRolesString() ) . "\n",
					$amapien->getAmapRolesString(), '' );
			}
		}

		return $log_file;
	}

	/** @param PHPExcel_Writer_IWriter $objWriter */
	public static function outputExcel(
		$objWriter
	) {
		$filePath = wp_upload_dir()['basedir'] . '/' . rand( 0, getrandmax() ) . rand( 0, getrandmax() ) . '.tmp';
		$objWriter->save( $filePath );
		readfile( $filePath );
		unlink( $filePath );
	}

	public static function getContactInfos() {
		$contact_page = '';
		if ( amapress_can_access_admin() ) {
			$contact_page = '<p>' .
			                self::makeButtonLink(
				                admin_url( 'options-general.php?page=amapress_options_page&tab=amp_public_contacts_config' ),
				                __( 'Editer les informations de Contacts public', 'amapress' ),
				                true, true
			                ) . '</p>';
		}
		$contact_page .= wp_unslash( self::getOption( 'contrat_info_anonymous' ) );
		$cf_id        = self::getOption( 'preinscription-form' );
		if ( $cf_id ) {
			$cf_post = get_post( $cf_id );
			if ( $cf_post ) {
				$cf_shortcode = '[contact-form-7 id="' . $cf_id . '" title="' . esc_attr( $cf_post->post_title ) . '"]';
				$contact_page .= '<br/>';
				$contact_page .= do_shortcode( $cf_shortcode );
			}
		}

		return $contact_page;
	}

	public static function get_page_with_shortcode_href(
		$shortcodes, $transient_name,
		$other_filter_regex = ''
	) {
		if ( $transient_name ) {
			$href = get_transient( $transient_name );
		} else {
			$href = null;
		}
		if ( empty( $href ) ) {
			foreach ( ! is_array( $shortcodes ) ? [ $shortcodes ] : $shortcodes as $shortcode ) {
				/** @var WP_Post $page */
				foreach ( get_pages() as $page ) {
					if ( false !== strpos( $page->post_content, '[' . $shortcode . ' ' )
					     || false !== strpos( $page->post_content, '[' . $shortcode . ']' ) ) {
						if ( ! empty( $other_filter_regex ) ) {
							if ( preg_match( '/\[' . $shortcode . '[^\]]*\]/', $page->post_content, $matches ) ) {
								if ( ! preg_match( $other_filter_regex, $matches[0] ) ) {
									continue;
								}
							}
						}
						$href = get_permalink( $page->ID );
						break;
					}
				}
			}
			if ( ! empty( $other_filter_regex ) && empty( $href ) ) {
				return self::get_page_with_shortcode_href( $shortcodes, $transient_name );
			}
			if ( $transient_name ) {
				set_transient( $transient_name, $href );
			}
		}

		return $href;
	}

	public static function get_collectif_page_href() {
		return self::get_page_with_shortcode_href( 'amapiens-role-list', 'amp_collectif_href' );
	}

	/**
	 * @param null|AmapressLieu_distribution $lieu
	 *
	 * @return false|mixed|string|null
	 */
	public static function get_inscription_distrib_page_href( $lieu = null ) {
		$filter = '';
		if ( $lieu ) {
			$filter = '/lieu="?' . $lieu->ID . '"?|lieu="?' . preg_quote( $lieu->getSlug(), '/' ) . '"?|lieu="?' . preg_quote( $lieu->getTitle(), '/' ) . '"?/i';
		}

		return self::get_page_with_shortcode_href( [
			'inscription-distrib',
			'anon-inscription-distrib'
		], 'amp_inscr_distrib_href' . ( $lieu ? $lieu->ID : '' ),
			$filter );
	}

	public static function get_intermittent_adhesion_page_href() {
		return self::get_page_with_shortcode_href( [
			'intermittent-adhesion-en-ligne',
			'intermittent-adhesion-en-ligne-connecte'
		], 'amp_inter_adh_href' );
	}

	public static function get_mes_contrats_page_href() {
		return self::get_page_with_shortcode_href( 'mes-contrats', 'amp_mes_contrats_href' );
	}

	public static function get_logged_inscription_page_href() {
		return self::get_page_with_shortcode_href( 'inscription-en-ligne-connecte', 'amp_inscrlog_href' );
	}

	public static function get_pre_inscription_page_href() {
		return self::get_page_with_shortcode_href( 'inscription-en-ligne', 'amp_preinscr_href' );
	}

	public static function formatPaymentType( $type ) {
		switch ( $type ) {
			case 'chq':
				return __( 'Chèque', 'amapress' );
			case 'esp':
				return __( 'Espèces', 'amapress' );
			case 'stp':
				return __( 'Paiement en ligne (Stripe)', 'amapress' );
			case 'vir':
				return __( 'Virement', 'amapress' );
			case 'mon':
				return __( 'Monnaie locale', 'amapress' );
			case 'dlv':
				return 'A la livraison';
			case 'prl':
				return __( 'Prélèvement', 'amapress' );
		}
	}

	public static function formatPrice(
		$number, $with_unit = false
	) {
		return number_format( floatval( $number ), 2, ',', "\xC2\xA0" ) . ( $with_unit ? '€' : '' );
	}

	public static function rename_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$role_names = [
			'administrator'     => __( 'Amap Administrateur', 'amapress' ),
			'producteur'        => __( 'Amap Producteur', 'amapress' ),
			'tresorier'         => __( 'Amap Trésorier', 'amapress' ),
			'coordinateur_amap' => __( 'Amap Coordinateur', 'amapress' ),
			'redacteur_amap'    => __( 'Amap Rédacteur', 'amapress' ),
			'responsable_amap'  => __( 'Amap Responsable', 'amapress' ),
			'referent'          => __( 'Amap Référent producteur', 'amapress' ),
		];

		foreach ( $role_names as $k => $v ) {
			if ( isset( $wp_roles->roles[ $k ] ) ) {
				$wp_roles->roles[ $k ]['name'] = $v;
			}
			if ( isset( $wp_roles->role_names[ $k ] ) ) {
				$wp_roles->role_names[ $k ] = $v;
			}
		}
	}

	public static function getFilterForReferent() {
		global $amapress_no_filter_referent;

		return $amapress_no_filter_referent;
	}

	public static function setFilterForReferent(
		$set
	) {
		global $amapress_no_filter_referent;
		global $amapress_no_filter_referent_nesting;

		if ( null == $amapress_no_filter_referent_nesting || $amapress_no_filter_referent_nesting < 0 ) {
			$amapress_no_filter_referent_nesting = 0;
		}

		if ( 0 == $amapress_no_filter_referent_nesting ) {
			$amapress_no_filter_referent = ! $set;
		}

		if ( ! $set ) {
			$amapress_no_filter_referent_nesting += 1;
		} else {
			$amapress_no_filter_referent_nesting -= 1;
		}

		if ( 0 == $amapress_no_filter_referent_nesting ) {
			$amapress_no_filter_referent = ! $set;
		}
	}

	public static function convertToPDF(
		$filename, $throw_if_fail = false
	) {
		$convertws_url  = self::getOption( 'convertws_url' );
		$convertws_user = self::getOption( 'convertws_user' );
		$convertws_pass = self::getOption( 'convertws_pass' );

		if ( empty( $convertws_url ) || empty( $convertws_user ) || empty( $convertws_pass ) ) {
			return $filename;
		}

		$convertws_url = trailingslashit( $convertws_url ) . 'convert2pdf.php';

		$info         = pathinfo( $filename );
		$pdf_filename = ( $info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '' )
		                . $info['filename']
		                . '.pdf';
		try {
			$pdf_handle = fopen( $pdf_filename, 'w+' );
			if ( ! $pdf_handle ) {
				throw new Exception( sprintf( __( 'Cannot open %s for writing, please check rights', 'amapress' ), $pdf_filename ) );
			}
			$fileContent = file_get_contents( $filename );
			if ( ! $fileContent ) {
				throw new Exception( sprintf( __( 'Cannot get content of %s', 'amapress' ), $filename ) );
			}
			$client = new GuzzleHttp\Client( [ 'verify' => ! defined( 'AMAPRESS_VALIDATE_SSL' ) || AMAPRESS_VALIDATE_SSL ] );
			$resp   = $client->post( $convertws_url, [
				'auth'      => [
					$convertws_user,
					$convertws_pass
				],
				'save_to'   => $pdf_handle,
				'multipart' => [
					[
						'name'     => 'input',
						'contents' => $fileContent,
						'filename' => $info['basename'],
					],
				],
			] );

			if ( 200 == $resp->getStatusCode() ) {
				@fclose( $pdf_handle );

				return $pdf_filename;
			} else {
				if ( $throw_if_fail ) {
					throw new Exception( $resp->getReasonPhrase() );
				} else {
					error_log( $resp->getReasonPhrase() );
				}

				return $filename;
			}
		} catch ( Exception $ex ) {
			if ( $throw_if_fail ) {
				wp_die( $ex->getMessage() );
			} else {
				error_log( $ex->getMessage() );
			}

			return $filename;
		}
	}

	public static function updateLocalisation(
		$postID, $is_user, $root_meta_name, $address_content
	) {
		$save_fn   = $is_user ? 'update_user_meta' : 'update_post_meta';
		$delete_fn = $is_user ? 'delete_user_meta' : 'delete_post_meta';
		$get_fn    = $is_user ? 'get_user_meta' : 'get_post_meta';
		if ( $is_user ) {
			$root_meta_name = 'amapress_user';
		}

		$geo = call_user_func( $get_fn, $postID, "{$root_meta_name}_cusgeo", true );
		if ( ! empty( $geo ) ) {
			call_user_func( $save_fn, $postID, "{$root_meta_name}_lat", $geo[0] );
			call_user_func( $save_fn, $postID, "{$root_meta_name}_long", $geo[1] );
			call_user_func( $save_fn, $postID, "{$root_meta_name}_location_type", 'm' );
			call_user_func( $delete_fn, $postID, "{$root_meta_name}_loc_err" );

			return true;
		} else {
			$address = TitanFrameworkOptionAddress::lookup_address( $address_content );
			if ( $address && ! is_wp_error( $address ) ) {
				call_user_func( $save_fn, $postID, "{$root_meta_name}_long", $address['longitude'] );
				call_user_func( $save_fn, $postID, "{$root_meta_name}_lat", $address['latitude'] );
				call_user_func( $save_fn, $postID, "{$root_meta_name}_location_type", $address['location_type'] );
				call_user_func( $delete_fn, $postID, "{$root_meta_name}_loc_err" );

				return true;
			} else {
				call_user_func( $delete_fn, $postID, "{$root_meta_name}_long" );
				call_user_func( $delete_fn, $postID, "{$root_meta_name}_lat" );
				call_user_func( $delete_fn, $postID, "{$root_meta_name}_location_type" );
				if ( is_wp_error( $address ) ) {
					/** @var WP_Error $address */
					call_user_func( $save_fn, $postID, "{$root_meta_name}_loc_err", $address->get_error_message() );
				} else {
					call_user_func( $delete_fn, $postID, "{$root_meta_name}_loc_err" );
				}

				return false;
			}
		}
	}

	public static function isHtmlEmpty(
		$html
	) {
		return empty( trim( wp_strip_all_tags( $html, true ) ) );
	}

	public static function formatPhone( $tel, $with_spaces = false ) {
		$tel = preg_replace( '/\+33\s*(?:\(\s*0\s*\)|0)?/', '0', $tel );
		$tel = preg_replace( '/\D+/', '', $tel );
		if ( $with_spaces ) {
			$tel = preg_replace( '/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $tel );
			$tel = str_replace( ' ', "\xC2\xA0", $tel );
		}

		return $tel;
	}

	public static function getSiteDomainName(
		$tld = false
	) {
		$domain = parse_url( home_url() )['host'];

		if ( $tld ) {
			//get the TLD and domain
			$domainparts = explode( '.', $domain );
			$domain      = $domainparts[ count( $domainparts ) - 2 ] . '.' . $domainparts[ count( $domainparts ) - 1 ];
		}

		return $domain;
	}

	private static $has_partial_coadhesion = null;

	public static function hasPartialCoAdhesion() {
		if ( null === self::$has_partial_coadhesion ) {
			self::$has_partial_coadhesion = self::getOption( 'allow_partial_coadh' );
		}

		return self::$has_partial_coadhesion;
	}

	public static function getWpConfigSecretHelp(
		$const_name,
		$sample_value = 'xxx'
	) {
		return sprintf(
			__( 'Pour une sécurité renforcée, vous pouvez définir une constante <code>define(\'%1$s\', \'%2$s\');</code> dans <code>wp-config.php</code> et laisser ce champs vide', 'amapress' ),
			$const_name, $sample_value
		);
	}

	/**
	 * @param array $m
	 * @param TitanFramework $titan
	 */
	private static function init_subpages( $m, TitanFramework $titan ) {
		if ( ! empty( $m['subpages'] ) ) {
			foreach ( $m['subpages'] as $subpage ) {
				if ( ! isset( $subpage['subpage'] ) ) {
					$menu_icon = $subpage['menu_icon'];
					if ( $menu_icon == 'post_type' && isset( $subpage['post_type'] ) ) {
						$pt = get_post_type_object( amapress_unsimplify_post_type( $subpage['post_type'] ) );
						if ( ! $pt ) {
							wp_die( esc_html( 'Unknown post_type: ' . $subpage['post_type'] ) );
						}
						$menu_icon = $pt->menu_icon;
					}
					add_action( 'admin_menu', function () use ( $m, $subpage, $menu_icon ) {
						$hook = add_submenu_page( $m['id'],
							do_shortcode( $subpage['title'] ),
							( ! empty( $menu_icon ) ? '<span class="dashicons-before ' . $menu_icon . '" /> ' : '' ) . do_shortcode( $subpage['menu_title'] ),
							$subpage['capability'], $subpage['slug'], isset( $subpage['function'] ) ? $subpage['function'] : null );
						if ( isset( $subpage['hook'] ) ) {
							add_action( "load-$hook", $subpage['hook'] );
						}
					}, 0 );
				} else {
					$pp = $titan->createAdminPage( array_merge(
						array(
							'id'       => $subpage['id'],
							'parent'   => $m['id'],
							'position' => 0,
						),
						$subpage['settings'] ) );

					$ttabs = isset( $subpage['tabs'] ) ? $subpage['tabs'] : null;
					if ( $ttabs && is_callable( $ttabs, false ) ) {
						$ttabs = call_user_func( $ttabs );
					}
					if ( ! empty( $ttabs ) ) {
						foreach ( $ttabs as $tab_name => $tab ) {
							$t = $pp->createTab( array(
								'name'       => $tab_name,
								'desc'       => ! empty( $tab['desc'] ) ? $tab['desc'] : '',
								'id'         => ! empty( $tab['id'] ) ? $tab['id'] : $tab_name,
								'capability' => ( ! empty( $tab['capability'] ) ? $tab['capability'] : null )
							) );
							foreach ( $tab['options'] as $opt ) {
								$t->createOption( $opt );
							}
						}
					}
					if ( ! empty( $subpage['options'] ) ) {
						foreach ( $subpage['options'] as $opt ) {
							$pp->createOption( $opt );
						}
					}
				}
			}
		}
	}
}
