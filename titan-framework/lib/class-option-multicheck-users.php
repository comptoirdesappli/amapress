<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionMulticheckUsers extends TitanFrameworkOptionMulticheck {

	public $defaultSecondarySettings = array(
		'options'           => array(),
		'role'              => '',
		'orderby'           => 'display_name',
		'order'             => 'ASC',
		'custom_csv_sample' => null,
	);

	public function getSamplesForCSV( $arg = null ) {
		if ( is_callable( $this->settings['custom_csv_sample'], false ) ) {
			return call_user_func( $this->settings['custom_csv_sample'], $this, $arg );
		} else if ( is_array( $this->settings['custom_csv_sample'] ) ) {
			return $this->settings['custom_csv_sample'];
		} else {
			return array(
				'Login',
				'Prénom Nom',
				'Nom Prénom',
				'Nom',
				'Email'
			);
		}
	}

	protected function getEditLink( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		return admin_url( "user-edit.php?user_id=$value" );
	}

	public function fetchOptions() {
		$args = array(
			'orderby' => $this->settings['orderby'],
			'order'   => $this->settings['order'],
		);

		if ( is_array( $this->settings['role'] ) ) {
			$args['role__in'] = $this->settings['role'];
		} else {
			$args['role'] = $this->settings['role'];
		}

		$args = apply_filters( "tf_multicheck_users_query_args", $args, $this );
		$args = apply_filters( "tf_{$this->getID()}_query_args", $args );

		$posts = get_users( $args );

		$ret = array();
		foreach ( $posts as $post ) {
			$title = $post->display_name;
			if ( empty( $title ) ) {
				$title = sprintf( __( 'Untitled %s', TF_I18NDOMAIN ), '(ID #' . $post->ID . ')' );
			}
			$ret[ $post->ID ] = $title;
		}

		return $ret;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$args = array(
			'role'    => $this->settings['role'],
			'orderby' => $this->settings['orderby'],
			'order'   => $this->settings['order'],
		);

		$posts = get_users( $args );

		$this->settings['options'] = array();
		foreach ( $posts as $post ) {
			$title = $post->display_name;
			if ( empty( $title ) ) {
				$title = sprintf( __( 'Untitled %s', TF_I18NDOMAIN ), '(ID #' . $post->ID . ')' );
			}
			$this->settings['options'][ $post->ID ] = $title;
		}

		$wp_customize->add_control( new TitanFrameworkOptionMulticheckControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'required'    => $this->settings['required'],
			'description' => $this->settings['desc'],
			'options'     => $this->settings['options'],
			'priority'    => $priority,
		) ) );
	}
}
