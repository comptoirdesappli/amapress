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
		'thumb'                   => true,
		'editor'                  => true,
		'slug'                    => amapress__( 'produits' ),
		'show_in_menu'            => false,
		'quick_edit'              => false,
		'has_archive'             => true,
		'import_by_meta'          => false,
		'menu_icon'               => 'dashicons-carrot',
		'custom_archive_template' => true,
		'default_orderby'         => 'post_title',
		'default_order'           => 'ASC',
		'show_admin_bar_new'      => true,
		'views'                   => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_produit_views',
			'exp_csv' => true,
		),
		'csv_required_fields'     => 'post_title',
		'groups'                  => array(
			'Producteur' => [
				'context' => 'side',
			],
		),
		'fields'                  => array(
//            'photo' => array(
//                'name' => amapress__('Photo'),
//                'type' => 'upload',
//                'group' => 'Information',
//                'desc' => 'Photo',
//            ),
//			'content_model' => array(
//				'name'         => amapress__( 'Modèle' ),
//				'type'         => 'select',
//				'group'        => 'Contenu',
//				'required'     => true,
//				'assoc_prop'   => 'produit_models',
//				'desc'         => 'Modèle de produit',
//				'options'      => 'amapress_tabs_model_get_options',
//				'csv_required' => true,
//			),
//			'content'       => array(
//				'name'            => amapress__( 'Contenu' ),
//				'type'            => 'custom',
//				'group'           => 'Contenu',
//				'tabs_conf'       => 'produit_models',
//				'tabs_default'    => 'produit_default_model',
//				'tabs_model_prop' => 'amapress_produit_content_model',
//				'custom'          => 'amapress_tabs_model_metabox_editor',
//				'save'            => 'amapress_tabs_model_metabox_editor_save',
//				'searchable'      => true,
//			),
//                    'conservation' => array(
//                        'name' => amapress__('Conservation'),
//                        'type' => 'editor',
//                        'group' => 'Information',
//                        'desc' => 'Conservation',
//                    ),
//                    'variete' => array(
//                        'name' => amapress__('Variété'),
//                        'type' => 'editor',
//                        'group' => 'Information',
//                        'desc' => 'Variété',
//                    ),
//                    'presentation' => array(
//                        'name' => amapress__('Présentation'),
//                        'type' => 'editor',
//                        'group' => 'Information',
//                        'desc' => 'Présentation',
//                    ),
//                    'saison' => array(
//                        'name' => amapress__('Saison'),
//                        'type' => 'editor',
//                        'group' => 'Information',
//                        'desc' => 'Saison',
//                    ),
//            'likes' => array(
//                'name' => amapress__('J\'aime'),
//                'hidden' => true,
//                'show_column' => true,
//                'group' => 'Avis',
//                'type' => 'number',
//            ),
//            'unlikes' => array(
//                'name' => amapress__('J\'aime moins'),
//                'hidden' => true,
//                'show_column' => true,
//                'group' => 'Avis',
//                'type' => 'number',
//            ),
			'producteur'    => array(
				'name'              => amapress__( 'Producteur' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'group'             => 'Producteur',
				'required'          => true,
				'desc'              => 'Producteur',
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