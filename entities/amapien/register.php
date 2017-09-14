<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_amapien' );
function amapress_register_entities_amapien( $entities ) {
	$entities['user'] = array(
		'internal_name'       => 'user',
		'csv_required_fields' => array( 'user_email', 'first_name', 'last_name' ),
		'bulk_actions'        => array(
			'amp_resend_welcome' => array(
				'label'    => 'Réenvoyer le mail de bienvenue',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant l\'envoie des mails de bienvenue',
					'1'  => 'Un mail de bienvenue a été envoyé avec succès',
					'>1' => '%s mails de bienvenue ont été envoyés avec succès',
				),
			),
		),
		'fields'              => array(
//            'role_desc' => array(
//                'name' => amapress__('Rôle dans l\'AMAP'),
//                'type' => 'text',
//                'desc' => 'Rôle dans l\'AMAP',
//            ),
			'adresse'           => array(
				'name'       => amapress__( 'Adresse' ),
				'type'       => 'textarea',
				'desc'       => 'Adresse',
				'searchable' => true,
//                'required' => true,
			),
			'code_postal'       => array(
				'name'       => amapress__( 'Code postal' ),
				'type'       => 'text',
				'desc'       => 'Code postal',
				'searchable' => true,
//                'required' => true,
			),
			'ville'             => array(
				'name'       => amapress__( 'Ville' ),
				'type'       => 'text',
				'desc'       => 'Ville',
				'searchable' => true,
//                'required' => true,
			),
			'adresse_localized' => array(
				'name'                   => amapress__( 'Localisé' ),
				'type'                   => 'address',
				'use_as_field'           => false,
				'user'                   => true,
				'csv'                    => false,
				'field_name_prefix'      => 'amapress_user',
				'address_field_name'     => 'amapress_user_adresse',
				'postal_code_field_name' => 'amapress_user_code_postal',
				'town_field_name'        => 'amapress_user_ville',
			),
			'telephone'         => array(
				'name'       => amapress__( 'Téléphone' ),
				'type'       => 'text',
				'desc'       => 'Téléphone',
				'searchable' => true,
			),
			'telephone2'        => array(
				'name'       => amapress__( 'Téléphone 2' ),
				'type'       => 'text',
				'desc'       => 'Téléphone 2',
				'searchable' => true,
			),
			'telephone3'        => array(
				'name'       => amapress__( 'Téléphone 3' ),
				'type'       => 'text',
				'desc'       => 'Téléphone 3',
				'searchable' => true,
			),
			'telephone4'        => array(
				'name'       => amapress__( 'Téléphone 4' ),
				'type'       => 'text',
				'desc'       => 'Téléphone 4',
				'searchable' => true,
			),
			'co-adherent-1'     => array(
				'name'       => amapress__( 'Co-adhérent 1' ),
				'type'       => 'select-users',
				'desc'       => 'Co-adhérent 1',
				'searchable' => true,
			),
			'co-adherent-2'     => array(
				'name'        => amapress__( 'Co-adhérent 2' ),
				'type'        => 'select-users',
				'desc'        => 'Co-adhérent 2',
				'show_column' => false,
				'searchable'  => true,
			),
			'co-adherents'      => array(
				'name'       => amapress__( 'Co-adhérent(s) - sans mail' ),
				'type'       => 'text',
				'desc'       => 'Co-adhérent(s) - sans mail',
				'searchable' => true,
			),
//            'co-adherents-mail' => array(
//                'name' => amapress__('Co-adhérent - email'),
//                'type' => 'text',
//                'desc' => 'Co-adhérent(s) - email',
//            ),
			'moyen'             => array(
				'name'        => amapress__( 'Moyen préféré' ),
				'type'        => 'select',
				'show_column' => false,
				'options'     => array(
					'mail' => 'Email',
					'tel'  => 'Téléphone',
				),
				'desc'        => 'Moyen préféré',
			),
			'avatar'            => array(
				'name'        => amapress__( 'Avatar' ),
				'type'        => 'upload',
				'custom_save' => 'amapress_save_user_avatar',
				'desc'        => 'Avatar',
				'show_column' => false,
			),
			'amap_roles'        => array(
				'name'        => amapress__( 'Rôles dans l\'AMAP' ),
				'type'        => 'multicheck-categories',
				'taxonomy'    => AmapressUser::AMAP_ROLE,
				'desc'        => 'Rôles dans le Collectif de l\'AMAP',
				'show_column' => false,
//                'searchable' => true,
			),
			'intermittent'      => array(
				'name'        => amapress__( 'Intermittent?' ),
				'type'        => 'checkbox',
				'desc'        => 'Intermittent?',
				'show_column' => false,
			),
//            'allow_show_email' => array(
//                'name' => amapress__('Autoriser mon email à être affiché'),
//                'type' => 'select',
//                'show_column' => false,
//                'desc' => 'Autorisation à être affiché aux autres amapiens',
//                'options' => array(
//                    'default' => 'Par défaut',
//                    'false' => 'Ne pas autoriser',
//                    'true' => 'Autoriser',
//                ),
//            ),
//            'allow_show_adresse' => array(
//                'name' => amapress__('Autoriser mon adresse à être affiché'),
//                'type' => 'select',
//                'show_column' => false,
//                'desc' => 'Autorisation à être affiché aux autres amapiens',
//                'options' => array(
//                    'default' => 'Par défaut',
//                    'false' => 'Ne pas autoriser',
//                    'true' => 'Autoriser',
//                ),
//            ),
//            'allow_show_tel_fixe' => array(
//                'name' => amapress__('Autoriser mon téléphone fixe à être affiché'),
//                'type' => 'select',
//                'show_column' => false,
//                'desc' => 'Autorisation à être affiché aux autres amapiens',
//                'options' => array(
//                    'default' => 'Par défaut',
//                    'false' => 'Ne pas autoriser',
//                    'true' => 'Autoriser',
//                ),
//            ),
//            'allow_show_tel_mobile' => array(
//                'name' => amapress__('Autoriser mon téléphone mobile à être affiché'),
//                'type' => 'select',
//                'show_column' => false,
//                'desc' => 'Autorisation à être affiché aux autres amapiens',
//                'options' => array(
//                    'default' => 'Par défaut',
//                    'false' => 'Ne pas autoriser',
//                    'true' => 'Autoriser',
//                ),
//            ),
//            'allow_show_avatar' => array(
//                'name' => amapress__('Autoriser mon avatar à être affiché'),
//                'type' => 'select',
//                'show_column' => false,
//                'desc' => 'Autorisation à être affiché aux autres amapiens',
//                'options' => array(
//                    'default' => 'Par défaut',
//                    'false' => 'Ne pas autoriser',
//                    'true' => 'Autoriser',
//                ),
//            ),
		),
		'help_new'            => array(),
		'help_edit'           => array(),
		'help_view'           => array(),
		'help_profile'        => array(),
		'views'               => array(
			'_dyn_all_' => 'amapress_user_views',
			'exp_csv'   => true,
		),
	);

	return $entities;
}

add_action( 'tf_post_save_options_amapress', 'amapress_amapien_affect_coadherents', 10, 2 );
function amapress_amapien_affect_coadherents( TitanFrameworkMetaBox $metabox, $userID ) {
	if ( $metabox->post_type != 'user' ) {
		return;
	}
	$user = AmapressUser::getBy( $userID );
	foreach ( AmapressContrats::get_user_active_adhesion( $userID, null, null ) as $adh ) {
		if ( $adh->getAdherent()->ID == $userID ) {
			$adh->setAdherent2( $user->getCoAdherent1() );
			$adh->setAdherent3( $user->getCoAdherent2() );
		}
	}
}

add_filter( 'get_role_list', 'amapress_get_role_list', 10, 2 );
function amapress_get_role_list( $role_list, $user_object ) {
	$amapien = AmapressUser::getBy( $user_object->ID );

//    $role_list = array_unique(array_merge($role_list, array_values($amapien->getAmapRoleIds())));
	return array_map(
		function ( $role ) {
//            return '<a href="'.esc_attr($role['edit_link']).'">'.esc_html($role['title']).'</a>';
			return $role['title'];
		}, $amapien->getAmapRoles() );
}

add_filter( 'amapress_can_delete_user', 'amapress_can_delete_user', 10, 2 );
function amapress_can_delete_user( $can, $user_id ) {
	$meta_query       = array(
		'relation' => 'OR',
	);
	$single_user_keys = array(
		'amapress_adhesion_paiement_user',
		'amapress_adhesion_adherent',
		'amapress_adhesion_adherent2',
		'amapress_adhesion_adherent3',
//        'amapress_adhesion_intermittence_user',
		'amapress_intermittence_panier_repreneur',
		'amapress_intermittence_panier_adherent',
		'amapress_lieu_distribution_referent',
		'amapress_producteur_user',
		'amapress_producteur_referent',
		'amapress_user_commande_amapien'
	);
	foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
		$single_user_keys[] = "amapress_producteur_referent_{$lieu_id}";
	}
	foreach ( $single_user_keys as $single_user_key ) {
		$meta_query[] = array(
			'key'     => $single_user_key,
			'value'   => $user_id,
			'type'    => 'NUMERIC',
			'compare' => '=',
		);
	}
	$posts = get_posts( array(
		'post_status'    => 'any',
		'post_type'      => array(
			AmapressAdhesion::INTERNAL_POST_TYPE,
			AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
//            AmapressAdhesion_intermittence::INTERNAL_POST_TYPE,
			AmapressIntermittence_panier::INTERNAL_POST_TYPE,
			AmapressProducteur::INTERNAL_POST_TYPE,
			AmapressUser_commande::INTERNAL_POST_TYPE,
			AmapressLieu_distribution::INTERNAL_POST_TYPE
		),
		'posts_per_page' => - 1,
		'meta_query'     => array( $meta_query ),
	) );
//amapress_visite_participants
//amapress_distribution_responsables
//amapress_amap_event_participants
//amapress_assemblee_generale_participants
//    if (108 == $user_id) {
//        var_dump(AmapressContrats::get_related_users($user_id));
//        var_dump($posts);
//    }
	return count( $posts ) == 0 && count( AmapressContrats::get_related_users( $user_id ) ) <= 1;
}

//function amapress_import_user_data_validate($v, $k) {
//    if ('user_email' == $k || 'first_name' == $k || )
//}
//add_filter('amapress_import_user_data', 'amapress_import_user_data_validate', 11, 2);

add_filter( 'amapress_register_admin_bar_menu_items', 'amapress_register_admin_bar_menu_items' );
function amapress_register_admin_bar_menu_items( $items ) {
	$items['referent-producteur'] = array(
		'icon'      => '',
		'title'     => '',
		'position'  => 0,
		'condition' => function () {
			return true;
		},
		'items'     => array(),
	);

	return $items;
}

add_filter( 'tf_replace_placeholders_user', function ( $text, $post_id ) {
	$current_user = AmapressUser::getBy( amapress_current_user_id() );
	$text         = amapress_replace_mail_placeholders( $text, $current_user );

	return $text;
}, 10, 2 );