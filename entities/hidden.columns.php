<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'default_hidden_columns', function ( $hidden, $screen ) {
	/** @var WP_Screen $screen */
	if ( ! $screen ) {
		return $hidden;
	}

	if ( ! empty( $screen->post_type ) ) {
		$pt = amapress_simplify_post_type( $screen->post_type );
	} elseif ( 'users' == $screen->id ) {
		$pt = 'user';
	} else {
		return apply_filters( "amapress_{$screen->id}_default_hidden_columns", $hidden, $screen );
	}

	$fields     = AmapressEntities::getPostTypeFields( $pt );
	$pt_options = AmapressEntities::getPostType( $pt );
	if ( ! empty( $pt_options['other_def_hidden_columns'] ) ) {
		$hidden = array_merge( $pt_options['other_def_hidden_columns'], $hidden );
	}
	foreach ( $fields as $field_name => $field_options ) {
		if ( isset( $field_options['col_def_hidden'] ) && $field_options['col_def_hidden'] ) {
			$hidden[] = $field_name;
		}
	}

	return $hidden;
}, 10, 2 );
