<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class GenericTitanEntity extends TitanEntity {
	function __construct( $post_or_id ) {
		parent::__construct( $post_or_id );
	}
}

class TitanEntity {
	private $initialized = false;
	private $post_id = null;
	protected $post;
	protected $custom = null;
	protected $customEntities = array();
	public $ID;

	protected function ensure_init() {
		if ( ! $this->initialized ) {
			$this->init_post();
		}
	}

	public function getID() {
		if ( ! $this->post ) {
			$this->ensure_init();
		}

		return $this->post->ID;
	}

	public function getTitle() {
		if ( ! $this->post ) {
			$this->ensure_init();
		}

		return $this->post->post_title;
	}

	public function getContent() {
		if ( ! $this->post ) {
			$this->ensure_init();
		}

		return $this->post->post_content;
	}

	public function getSlug() {
		if ( ! $this->post ) {
			$this->ensure_init();
		}

		return $this->post->post_name;
	}

	/** @return WP_Post */
	public function getPost() {
		if ( ! $this->post ) {
			$this->ensure_init();
		}

		return $this->post;
	}

	public function getDefaultSortValue() {
		return 0;
	}

	public static function getProperties() {
		return [];
	}

	public function getProperty( $name ) {
		$this->ensure_init();
		$props = static::getProperties();
		if ( isset( $props[ $name ] ) ) {
			return call_user_func( $props[ $name ]['func'], $this );
		}

		return "##UNKNOW:$name##";
	}

	public function linkToPermalink( $html, $classes = '', $relative_url = null ) {
		return "<a href='{$this->getPermalink($relative_url)}' class='$classes'>$html</a>";
	}

	public function linkToPermalinkBlank( $html, $classes = '', $relative_url = null ) {
		return "<a href='{$this->getPermalink($relative_url)}' class='$classes' target='_blank'>$html</a>";
	}

	public function getPermalink( $relative_url = null ) {
		if ( ! $this->post ) {
			$this->ensure_init();
		}

		$url = get_permalink( $this->post->ID );
		if ( empty( $relative_url ) ) {
			return $url;
		}

		return trailingslashit( $url ) . $relative_url;
	}

	public function getAdminEditLink() {
		return admin_url( "post.php?post={$this->ID}&action=edit" );
	}

	private function init_post() {
		if ( $this->post == null ) {
			$this->post = get_post( $this->post_id );
		}
		$customs = get_post_custom( $this->post_id );
		if ( empty( $customs ) || is_wp_error( $customs ) ) {
			$customs = array();
		}
//        if (!is_array($customs)) {
//            echo '<pre>';
//            debug_print_backtrace();
//            echo '</pre>';
//            die();
//        }
		$this->custom      = array_map( function ( $v ) {
			return self::prepare_custom_field_value( $v );
		}, $customs );
		$this->initialized = true;
	}

	public static function prepare_custom_field_value( $value ) {
		if ( is_array( $value ) ) {
			if ( count( $value ) > 1 ) {
				$ret = $value;
			} else {
				$ret = maybe_unserialize( $value[0] );
			}
		} else {
			$ret = maybe_unserialize( $value );
		}
		if ( is_wp_error( $ret ) ) {
			return null;
		} else {
			return $ret;
		}
	}

	protected function __construct( $post_or_id ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$this->post_id = $post_or_id->ID;
			$this->post    = $post_or_id;
		} else {
			$this->post_id = intval( $post_or_id );
		}
		$this->ID = $this->post_id;
	}

	protected function deleteCustom( $name ) {
		if ( delete_post_meta( $this->ID, $name ) !== false ) {
			unset( $this->custom[ $name ] );
			unset( $this->customEntities[ $name ] );
		}
	}

	protected function setCustom( $name, $value ) {
		if ( update_post_meta( $this->ID, $name, $value ) !== false ) {
			$this->custom[ $name ] = $value;
			unset( $this->customEntities[ $name ] );
		}
	}

	protected function getCustomAsInt( $name, $default = 0 ) {
		return intval( $this->getCustom( $name, $default ) );
	}

	protected function getCustomAsFloat( $name, $default = 0 ) {
		return floatval( $this->getCustom( $name, $default ) );
	}

	protected function getCustom( $name, $default = null ) {
		$this->ensure_init();

		return isset( $this->custom[ $name ] ) ? $this->custom[ $name ] : $default;
	}

	protected function getCustomAsEntity( $name, $classname, $default = null ) {
		$this->ensure_init();

		//TODO refactor, this should not be here, it's a quick fix
		$create = function () use ( $classname, $name ) {
			$id = intval( $this->custom[ $name ] );
			switch ( $classname ) {
				case 'AmapressUser':
					return AmapressUser::getBy( $id );
				case 'AmapressContrat':
					return AmapressContrat::getBy( $id );
				case 'AmapressContrat_instance':
					return AmapressContrat_instance::getBy( $id );
				case 'AmapressProducteur':
					return AmapressProducteur::getBy( $id );
				case 'AmapressAdhesion':
					return AmapressAdhesion::getBy( $id );
				case 'AmapressLieu_distribution':
					return AmapressLieu_distribution::getBy( $id );
				case 'AmapressContrat_quantite':
					return AmapressContrat_quantite::getBy( $id );
				case 'AmapressIntermittence_panier':
					return AmapressIntermittence_panier::getBy( $id );
				case 'AmapressPanier':
					return AmapressPanier::getBy( $id );
				default:
					return new $classname( $id );
			}
		};
		$ret    = ! empty( $this->custom[ $name ] ) ?
			call_user_func( $create ) :
			$default;
		if ( isset( $this->customEntities[ $name ] ) ) {
			return $this->customEntities[ $name ];
		}
		$this->customEntities[ $name ] = $ret;

		return $ret;
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

		return [ $value ];
	}

	public static function to_date( $s ) {
		if ( empty( $s ) ) {
			return 0;
		}
		$s = trim( $s );
		if ( is_numeric( $s ) ) {
			return Amapress::start_of_day( intval( $s ) );
		}
		$d = DateTime::createFromFormat( 'd#m#Y', $s );
		if ( empty( $d ) ) {
			$d = DateTime::createFromFormat( 'U', $s );
		}
		if ( empty( $d ) ) {
			return 0;
		}

		return Amapress::start_of_day( $d->getTimestamp() );
	}

	protected function getCustomAsArray( $name ) {
		$this->ensure_init();

		return self::get_array( $this->getCustom( $name ) );
	}

	protected function getCustomAsIntArray( $name ) {
		$this->ensure_init();

		return array_map( 'intval', $this->getCustomAsArray( $name ) );
	}

	protected function getCustomAsDateArray( $name ) {
		$this->ensure_init();

		return array_map( 'self::to_date', $this->getCustomAsArray( $name ) );
	}

	protected function getCustomAsDate( $name ) {
		$this->ensure_init();

		return self::to_date( $this->getCustom( $name ) );
	}

	protected function getCustomAsFloatArray( $name ) {
		$this->ensure_init();

		return array_map( 'floatval', $this->getCustomAsArray( $name ) );
	}

	protected function getCustomAsEntityArray( $name, $classname ) {
		$this->ensure_init();

		if ( isset( $this->customEntities[ $name ] ) ) {
			return $this->customEntities[ $name ];
		}
		$ret                           = array_map( function ( $id ) use ( $classname ) {
			return new $classname( $id );
		}, $this->getCustomAsIntArray( $name ) );
		$this->customEntities[ $name ] = $ret;

		return $ret;
	}
}