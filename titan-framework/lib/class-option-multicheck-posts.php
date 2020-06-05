<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionMulticheckPosts extends TitanFrameworkOptionMulticheck {

	public $defaultSecondarySettings = array(
		'options'           => array(),
		'post_type'         => 'post',
		'num'               => - 1,
		'post_status'       => 'any',
		'orderby'           => 'post_date',
		'order'             => 'DESC',
		'custom_csv_sample' => null,
		'wrap_edit'         => true,
	);

	public function getSamplesForCSV( $arg = null ) {
		if ( is_callable( $this->settings['custom_csv_sample'], false ) ) {
			return call_user_func( $this->settings['custom_csv_sample'], $this, $arg );
		} else if ( is_array( $this->settings['custom_csv_sample'] ) ) {
			return $this->settings['custom_csv_sample'];
		} else {
			return $this->fetchOptionsWithCache();
		}
	}

	protected function getEditLink( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		return admin_url( "post.php?post=$value&action=edit" );
	}

	public function fetchOptions() {
		$args = array(
			'post_type'      => $this->settings['post_type'],
			'posts_per_page' => $this->settings['num'],
			'post_status'    => $this->settings['post_status'],
			'orderby'        => $this->settings['orderby'],
			'order'          => $this->settings['order'],
		);

		$args = apply_filters( "tf_multicheck_posts_query_args", $args, $this );
		$args = apply_filters( "tf_{$this->getID()}_query_args", $args );

		$posts = get_posts( $args );

		$ret = array();
		foreach ( $posts as $post ) {
			$title = $post->post_title;
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
			'post_type'      => $this->settings['post_type'],
			'posts_per_page' => $this->settings['num'],
			'post_status'    => $this->settings['post_status'],
			'orderby'        => $this->settings['orderby'],
			'order'          => $this->settings['order'],
		);

		$posts = get_posts( $args );

		$this->settings['options'] = array();
		foreach ( $posts as $post ) {
			$title = $post->post_title;
			if ( empty( $title ) ) {
				$title = sprintf( __( 'Untitled %s', TF_I18NDOMAIN ), '(ID #' . $post->ID . ')' );
			}
			$this->settings['options'][ $post->ID ] = $title;
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
