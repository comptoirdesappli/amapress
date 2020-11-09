<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_produit' );
function amapress_register_entities_produit( $entities ) {
	$entities['produit']           = array(
		'singular'                => __( 'Produit', 'amapress' ),
		'plural'                  => __( 'Produits', 'amapress' ),
		'public'                  => true,
		'thumb'                    => true,
		'editor'                   => true,
		'slug'                     => __( 'produits', 'amapress' ),
		'show_in_menu'             => false,
		'comments'                 => true,
		'public_comments'          => true,
		'quick_edit'               => false,
		'has_archive'              => true,
		'import_by_meta'           => false,
		'menu_icon'                => 'dashicons-carrot',
		'custom_archive_template'  => true,
		'default_orderby'          => 'post_title',
		'default_order'            => 'ASC',
		'other_def_hidden_columns' => array( 'amps_lo', 'comments' ),
		'show_admin_bar_new'       => true,
		'views'                    => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_produit_views',
			'exp_csv' => true,
		),
		'csv_required_fields'      => 'post_title',
		'groups'                   => array(
			__( 'Producteur', 'amapress' ) => [
				'context' => 'side',
			],
		),
		'fields'                   => array(
			'producteur'    => array(
				'name'              => __( 'Producteur(s)', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'group'             => __( 'Producteur(s)', 'amapress' ),
				'required'          => true,
				'multiple'          => true,
				'tags'              => true,
				'autocomplete'      => true,
				'desc'              => __( 'Producteur(s) associés', 'amapress' ),
				'csv_required'      => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => __( 'Toutes les producteurs', 'amapress' ),
				),
				'searchable'        => true,
			),
		),
	);
//	$entities['user_produit_like'] = array(
//		'internal_name' => 'amps_user_plike',
//		'singular'      => __( 'user_produit_like', 'amapress' ),
//		'plural'        => __( 'user_produit_like', 'amapress' ),
//		'public'        => false,
//		'fields'        => array(
//			'user'    => array(
//				'name'     => __( 'Utilisateur', 'amapress' ),
//				'type'     => 'select-users',
//				'required' => true,
//				'desc'     => __('Utilisateur', 'amapress'),
//			),
//			'produit' => array(
//				'name'     => __( 'Utilisateur', 'amapress' ),
//				'type'     => 'select-users',
//				'required' => true,
//				'desc'     => __('Utilisateur', 'amapress'),
//			),
//			'like'    => array(
//				'name'     => __( 'Like', 'amapress' ),
//				'type'     => 'number',
//				'required' => true,
//				'desc'     => __('Like', 'amapress'),
//			),
//		),
//	);

	return $entities;
}