<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_produit' );
function amapress_register_entities_produit( $entities ) {
	$entities['produit']           = array(
		'singular'                => amapress__( 'Produit' ),
		'plural'                  => amapress__( 'Produits' ),
		'public'                  => true,
		'thumb'                    => true,
		'editor'                   => true,
		'slug'                     => amapress__( 'produits' ),
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
			'Producteur' => [
				'context' => 'side',
			],
		),
		'fields'                   => array(
			'producteur'    => array(
				'name'              => amapress__( 'Producteur(s)' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'group'             => 'Producteur(s)',
				'required'          => true,
				'multiple'          => true,
				'tags'              => true,
				'autocomplete'      => true,
				'desc'              => 'Producteur(s) associés',
				'csv_required'      => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => 'Toutes les producteurs',
				),
				'searchable'        => true,
			),
		),
	);
//	$entities['user_produit_like'] = array(
//		'internal_name' => 'amps_user_plike',
//		'singular'      => amapress__( 'user_produit_like' ),
//		'plural'        => amapress__( 'user_produit_like' ),
//		'public'        => false,
//		'fields'        => array(
//			'user'    => array(
//				'name'     => amapress__( 'Utilisateur' ),
//				'type'     => 'select-users',
//				'required' => true,
//				'desc'     => 'Utilisateur',
//			),
//			'produit' => array(
//				'name'     => amapress__( 'Utilisateur' ),
//				'type'     => 'select-users',
//				'required' => true,
//				'desc'     => 'Utilisateur',
//			),
//			'like'    => array(
//				'name'     => amapress__( 'Like' ),
//				'type'     => 'number',
//				'required' => true,
//				'desc'     => 'Like',
//			),
//		),
//	);

	return $entities;
}