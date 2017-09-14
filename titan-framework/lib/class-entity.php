<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
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
		$this->ensure_init();

		return $this->post->ID;
	}

	public function getTitle() {
		$this->ensure_init();

		return $this->post->post_title;
	}

	public function getContent() {
		$this->ensure_init();

		return $this->post->post_content;
	}

	public function getSlug() {
		$this->ensure_init();

		return $this->post->post_name;
	}

	public function getPost() {
		$this->ensure_init();

		return $this->post;
	}

	public function getDefaultSortValue() {
		return 0;
	}

	public function getProperty( $name ) {
		$this->ensure_init();

		return '';
	}

	public function linkToPermalink( $html, $classes = '', $relative_url = null ) {
		return "<a href='{$this->getPermalink($relative_url)}' class='$classes'>$html</a>";
	}

	public function getPermalink( $relative_url = null ) {
		$this->ensure_init();

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
		if ( empty( $customs ) ) {
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
				return $value;
			} else {
				return maybe_unserialize( $value[0] );
			}
		} else {
			return maybe_unserialize( $value );
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

		return ! empty( $this->custom[ $name ] ) ? $this->custom[ $name ] : $default;
	}

	protected function getCustomAsEntity( $name, $classname, $default = null ) {
		$this->ensure_init();

		$ret = ! empty( $this->custom[ $name ] ) ? new $classname( intval( $this->custom[ $name ] ) ) : $default;
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

		return $value;
	}

	public static function to_date( $s ) {
		if ( empty( $s ) ) {
			return 0;
		}
		$d = DateTime::createFromFormat( 'd#m#Y', trim( $s ) );
		if ( empty( $d ) ) {
			$d = DateTime::createFromFormat( 'U', trim( $s ) );
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