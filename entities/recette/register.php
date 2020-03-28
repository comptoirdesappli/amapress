<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_recette' );
function amapress_register_entities_recette( $entities ) {
	$entities['recette'] = array(
		'singular'                => amapress__( 'Recette' ),
		'plural'                  => amapress__( 'Recettes' ),
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
//            'photo' => array(
//                'name' => amapress__('Photo'),
//                'type' => 'upload',
//                'desc' => 'Photo',
//            ),
//			'content_model' => array(
//				'name'       => amapress__( 'Modèle' ),
//				'type'       => 'select',
//				'group'      => 'Contenu',
//				'required'   => true,
//				'assoc_prop' => 'recette_models',
//				'desc'       => 'Modèle de recette',
//				'options'    => 'amapress_tabs_model_get_options',
//			),
//			'content'       => array(
//				'name'            => amapress__( 'Contenu' ),
//				'type'            => 'custom',
//				'group'           => 'Contenu',
//				'tabs_conf'       => 'recette_models',
//				'tabs_default'    => 'recette_default_model',
//				'tabs_model_prop' => 'amapress_recette_content_model',
//				'custom'          => 'amapress_tabs_model_metabox_editor',
//				'save'            => 'amapress_tabs_model_metabox_editor_save',
//				'searchable'      => true,
//			),
			'produits'      => array(
				'name'         => amapress__( 'Produits associés' ),
				'type'         => 'select-posts',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'post_type'    => 'amps_produit',
				'group'        => 'Produits',
				'desc'         => 'Produits associés',
			),
		),
	);

	return $entities;
}