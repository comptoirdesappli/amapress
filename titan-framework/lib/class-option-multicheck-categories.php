<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionMulticheckCategories extends TitanFrameworkOptionMulticheck {

	public $defaultSecondarySettings = array(
		'options'           => array(),
		'orderby'           => 'name',
		'order'             => 'ASC',
		'taxonomy'          => 'category',
		'hide_empty'        => false,
		'show_count'        => false,
		'custom_csv_sample' => null,
	);

	public function getValue( $postID = null ) {
		return wp_get_post_terms( $this->getPostID( $postID ), $this->settings['taxonomy'], array(
			'fields' => 'ids'
		) );
	}

	/**
	 *
	 */
	public function setValue( $value, $postID = null ) {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}
		wp_set_object_terms( $this->getPostID( $postID ), array_map( 'intval', $value ), $this->settings['taxonomy'] );
	}

	public function fetchOptions() {
		$args = array(
			'orderby'    => $this->settings['orderby'],
			'order'      => $this->settings['order'],
			'taxonomy'   => $this->settings['taxonomy'],
			'hide_empty' => $this->settings['hide_empty'] ? '1' : '0',
		);

		$args = apply_filters( "tf_multicheck_categories_query_args", $args, $this );
		$args = apply_filters( "tf_{$this->getID()}_query_args", $args );

		$categories = get_categories( $args );

		$ret = array();
		foreach ( $categories as $category ) {
			$ret[ $category->term_id ] = $category->name . ( $this->settings['show_count'] ? ' (' . $category->count . ')' : '' );
		}

		return $ret;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$args = array(
			'orderby'    => $this->settings['orderby'],
			'order'      => $this->settings['order'],
			'taxonomy'   => $this->settings['taxonomy'],
			'hide_empty' => $this->settings['hide_empty'] ? '1' : '0',
		);

		$categories = get_categories( $args );

		$this->settings['options'] = array();
		foreach ( $categories as $category ) {
			$this->settings['options'][ $category->term_id ] = $category->name . ( $this->settings['show_count'] ? ' (' . $category->count . ')' : '' );
		}

		$wp_customize->add_control( new TitanFrameworkOptionMulticheckControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'options'     => $this->settings['options'],
			'priority'    => $priority,
		) ) );
	}
}
