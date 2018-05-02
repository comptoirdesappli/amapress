<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_producteur' );
function amapress_register_entities_producteur( $entities ) {
	$entities['producteur'] = array(
		'singular'                => amapress__( 'Producteur' ),
		'plural'                  => amapress__( 'Producteurs' ),
		'public'                  => true,
		'editor'                  => true,
		'thumb'                   => true,
		'slug'                    => amapress__( 'producteurs' ),
		'menu_icon'               => 'flaticon-tractor',
		'show_in_menu'            => false,
		'quick_edit'              => false,
		'custom_archive_template' => true,
		'default_orderby'         => 'post_title',
		'default_order'           => 'ASC',
		'views'                   => array(
			'remove'  => array( 'mine' ),
			'exp_csv' => true,
		),
		'fields'                  => array(
//			'resume'               => array(
//				'name'       => amapress__( 'Présentation du producteur' ),
//				'type'       => 'editor',
//				'group'      => 'Information',
//				'required'   => true,
//				'desc'       => 'Présentation du producteur',
//				'searchable' => true,
//			),
//			'presentation'         => array(
//				'name'       => amapress__( 'Histoire - En savoir plus' ),
//				'type'       => 'editor',
//				'group'      => 'Information',
//				'required'   => true,
//				'desc'       => 'Histoire - En savoir plus',
//				'searchable' => true,
//			),
//			'historique'           => array(
//				'name'       => amapress__( 'Historique' ),
//				'type'       => 'editor',
//				'group'      => 'Information',
//				'required'   => true,
//				'desc'       => 'Historique',
//				'searchable' => true,
//			),
			'nom_exploitation'     => array(
				'name'       => amapress__( 'Nom de l\'exploitation' ),
				'type'       => 'text',
				'desc'       => 'Nom de la ferme',
				'group'      => 'Emplacement',
				'searchable' => true,
			),
			'adresse_exploitation' => array(
				'name'         => amapress__( 'Adresse de la ferme' ),
				'type'         => 'address',
				'use_as_field' => true,
				'desc'         => 'Adresse de la ferme',
				'group'        => 'Emplacement',
				'searchable'   => true,
			),
			'acces'                => array(
				'name'       => amapress__( 'Accès' ),
				'type'       => 'editor',
				'group'      => 'Emplacement',
				'desc'       => 'Accès',
				'searchable' => true,
			),
			'user'      => array(
				'name'       => amapress__( 'Compte utilisateur du producteur' ),
				'type'       => 'select-users',
				'role'       => 'producteur',
				'group'      => 'Gestion',
				'required'   => true,
				'desc'       => 'Sélectionner le compte utilisateur du producteur. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs',
				'searchable' => true,
			),
			'referent'  => array(
				'name'         => amapress__( 'Référent' ),
				'type'         => 'select-users',
				'role'         => amapress_can_access_admin_roles(),
				'group'        => 'Référents',
//                'required' => true,
				'desc'         => 'Référent',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent2' => array(
				'name'         => amapress__( 'Référent 2' ),
				'type'         => 'select-users',
				'role'         => amapress_can_access_admin_roles(),
				'group'        => 'Référents',
//                'required' => true,
				'desc'         => 'Référent 2',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent3' => array(
				'name'         => amapress__( 'Référent 3' ),
				'type'         => 'select-users',
				'role'         => amapress_can_access_admin_roles(),
				'group'        => 'Référents',
//                'required' => true,
				'desc'         => 'Référent 3',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
//            'actions' => array(
//                'name' => amapress__('Actions'),
//                'type' => 'action-buttons',
//                'group' => 'Actions',
//                'desc' => '',
//                'buttons' => array(
//                    'contrats' => array(
//                        'text' => 'Voir ses contrats',
//                        'href' => admin_url('edit.php?post_type=amps_contrat_inst&amapress_producteur=%%id%%'),
//                    )
//                )
//            ),
//            'contrats' => array(
//                'name' => amapress__('Contrats'),
//                'type' => 'related-posts',
//                'group' => 'Contrats',
//                'desc' => '',
//                'link_text' => 'Voir ses %%count%% contrats',
//                'query' => 'post_type=amps_contrat_inst&amapress_producteur=%%id%%',
//            ),
//            'other_prods' => array(
//                'name' => amapress__('Other prods'),
//                'type' => 'related-users',
//                'group' => 'Other prods',
//                'desc' => '',
//                'link_text' => 'Voir ses %%count%% autres',
//                'query' => 'role=producteur',
//            ),
		),
		'help_edit'               => array(),
		'help_view'               => array(),
	);

	return $entities;
}

add_filter( 'amapress_producteur_fields', 'amapress_producteur_fields' );
function amapress_producteur_fields( $fields ) {
	$lieux = Amapress::get_lieux();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$fields[ 'referent_' . $lieu->ID ] = array(
				'name'         => amapress__( 'Référent ' . $lieu->getShortName() ),
				'type'         => 'select-users',
				'role'         => amapress_can_access_admin_roles(),
				'group'        => 'Référents',
				'searchable'   => true,
				'autocomplete' => true,
//                'required' => true,
				'desc'         => 'Référent',
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			);
		}
	}

	return $fields;
}

add_filter( 'the_posts', 'amapress_order_producteurs_and_contrats', 10, 2 );
function amapress_order_producteurs_and_contrats( array $posts, WP_Query $query ) {
	if ( ! $query->is_admin ) {
		$post_type = $query->get( 'post_type' );
		if ( $post_type == AmapressProducteur::INTERNAL_POST_TYPE || $post_type == AmapressContrat::INTERNAL_POST_TYPE ) {
			$contrat_order = Amapress::getOption( 'contrats_order' );
			if ( ! empty( $contrat_order ) ) {
				$ids_order = array_map( function ( $cid ) use ( $post_type ) {
					if ( $post_type == AmapressContrat::INTERNAL_POST_TYPE ) {
						return intval( $cid );
					} else if ( $post_type == AmapressProducteur::INTERNAL_POST_TYPE ) {
						$c = AmapressContrat::getBy( intval( $cid ) );

						return $c->getProducteurId();
					} else {
						return 0;
					}
				}, $contrat_order );
				usort( $posts, function ( $a, $b ) use ( $ids_order ) {
					if ( $a->ID == $b->ID ) {
						return 0;
					}
					$aix = array_search( $a->ID, $ids_order );
					$bix = array_search( $b->ID, $ids_order );
					if ( $aix === false && $bix === false ) {
						return 0;
					}
					if ( $aix === false ) {
						return 1;
					}
					if ( $bix === false ) {
						return - 1;
					}

					return ( $aix < $bix ? - 1 : 1 );
				} );
			}
		}
	}

	return $posts;
}

add_filter( 'amapress_can_delete_producteur', 'amapress_can_delete_producteur', 10, 2 );
function amapress_can_delete_producteur( $can, $post_id ) {
	$prod = AmapressProducteur::getBy( $post_id );

	return count( $prod->getProduitIds() ) == 0
	       && count( $prod->getContrats() ) == 0;
}

add_filter( 'tf_select_users_title', 'amapress_producteurs_user_mails', 10, 3 );
function amapress_producteurs_user_mails( $display_name, WP_User $user, TitanFrameworkOptionSelectUsers $option ) {
	if ( isset( $option->owner->settings['post_type'] ) && in_array( AmapressProducteur::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] ) ) {
		$amapien = AmapressUser::getBy( $user->ID );

		return sprintf( '%s (%s)', $user->display_name, implode( ',', $amapien->getAllEmails() ) );
	}

	return $display_name;
	//if ($id == '')
}

add_filter( 'tf_replace_placeholders_' . AmapressProducteur::INTERNAL_POST_TYPE, function ( $text, $post_id ) {
	$current_user = AmapressUser::getBy( amapress_current_user_id() );
	$prod         = AmapressProducteur::getBy( $post_id );
	$text         = amapress_replace_mail_placeholders( $text, $current_user, $prod );

	return $text;
}, 10, 2 );