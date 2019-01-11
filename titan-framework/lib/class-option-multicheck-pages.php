<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionMulticheckPages extends TitanFrameworkOptionMulticheck {

	public $defaultSecondarySettings = array(
		'options' => array(),
	);

	protected function getEditLink( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		return admin_url( "post.php?post=$value&action=edit" );
	}

	private static $allPages;

	public function fetchOptions() {

		// Remember the pages so as not to perform any more lookups
		if ( ! isset( self::$allPages ) ) {
			self::$allPages = get_pages();
		}

		$ret = array();
		foreach ( self::$allPages as $page ) {
			$title = $page->post_title;
			if ( empty( $title ) ) {
				$title = sprintf( __( 'Untitled %s', TF_I18NDOMAIN ), '(ID #' . $page->ID . ')' );
			}
			$ret[ $page->ID ] = $title;
		}

		return $ret;
	}

	public function generateMember() {
		$mn        = $this->getMemberName();
		$post_type = ucfirst( amapress_simplify_post_type( $this->settings['post_type'] ) );

		return '
		private $' . $mn . ' = null;
		public function get' . $mn . '() {
			$this->ensure_init();
			$v = $this->custom[\'' . $this->getID() . '\'];
			if (empty($v)) return array();
			if ($this->' . $mn . ' == null) $this->' . $mn . ' = array_map(function($o) { return new Amapress' . $post_type . '($o); }, $v);
			return $this->' . $mn . ';
		}
		public function set' . $mn . '($value) {
			update_post_meta($this->post->ID, \'' . $this->getID() . '\', $value);
			$this->' . $mn . ' = null;
		}
		';
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		// Remember the pages so as not to perform any more lookups
		if ( ! isset( self::$allPages ) ) {
			self::$allPages = get_pages();
		}

		$this->settings['options'] = array();
		foreach ( self::$allPages as $page ) {
			$title = $page->post_title;
			if ( empty( $title ) ) {
				$title = sprintf( __( 'Untitled %s', TF_I18NDOMAIN ), '(ID #' . $page->ID . ')' );
			}
			$this->settings['options'][ $page->ID ] = $title;
		}

		$wp_customize->add_control( new TitanFrameworkOptionMulticheckControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'required'    => $this->settings['required'],
			'options'     => $this->settings['options'],
			'priority'    => $priority,
		) ) );
	}
}
