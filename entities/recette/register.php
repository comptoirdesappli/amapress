<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_recette' );
function amapress_register_entities_recette( $entities ) {
	$entities['recette'] = array(
		'singular'                => __( 'Recette', 'amapress' ),
		'plural'                  => __( 'Recettes', 'amapress' ),
		'public'                   => true,
		'editor'                   => true,
		'special_options'          => array(),
		'slug'                     => 'recettes',
		'thumb'                    => true,
		'show_in_menu'             => false,
		'comments'                 => true,
		'public_comments'          => true,
		'quick_edit'               => false,
		'menu_icon'                => 'flaticon-cooking',
		'custom_archive_template'  => true,
		'default_orderby'          => 'post_title',
		'default_order'            => 'ASC',
		'other_def_hidden_columns' => array( 'amps_lo' ),
		'show_admin_bar_new'       => true,
		'fields'                   => array(
			'produits'      => array(
				'name'         => __( 'Produits associés', 'amapress' ),
				'type'         => 'select-posts',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'post_type'    => 'amps_produit',
				'group'        => __( 'Produits', 'amapress' ),
				'desc'         => __( 'Produits associés', 'amapress' ),
			),
		),
	);

	return $entities;
}