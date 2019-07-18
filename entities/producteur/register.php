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
		'groups'                  => array(
			'Infos' => [
				'context' => 'side',
			],
		),
		'edit_header'             => function ( $post ) {
			if ( TitanFrameworkOption::isOnEditScreen() ) {
				$producteur = AmapressProducteur::getBy( $post );
				if ( empty( $producteur->getUser() ) ) {
					echo '<div class="notice notice-error"><p>Producteur invalide : pas d\'utilisateur associé</p></div>';
				}
				if ( ! $producteur->isAdresseExploitationLocalized() ) {
					amapress_add_admin_notice( 'Adresse du producteur non localisée', 'warning', false );
				}
				if ( from( $producteur->getContrats() )->any( function ( $contrat ) {
					/** @var AmapressContrat $contrat */
					return empty( $contrat->getAllReferentsIds() );
				} ) ) {
					echo '<div class="notice notice-error"><p>Producteur sans référent</p></div>';
				}
			}

			TitanFrameworkOption::echoFullEditLinkAndWarning();

			echo '<h2>Présentation du producteur <em>(Biographie, historique de la ferme...)</em></h2>';
		},
		'fields'                  => array(
			'nom_exploitation'     => array(
				'name'       => amapress__( 'Nom de l\'exploitation' ),
				'type'       => 'text',
				'desc'       => 'Nom de la ferme',
				'group'      => '1/ Emplacement',
				'searchable' => true,
			),
			'adresse_exploitation' => array(
				'name'         => amapress__( 'Adresse de la ferme' ),
				'type'         => 'address',
				'use_as_field' => true,
				'desc'         => 'Adresse de la ferme',
				'group'        => '1/ Emplacement',
				'searchable'   => true,
			),
			'acces'                => array(
				'name'       => amapress__( 'Accès' ),
				'type'       => 'editor',
				'group'      => '1/ Emplacement',
				'desc'       => 'Accès',
				'searchable' => true,
			),
			'user'                 => array(
				'name'       => amapress__( 'Compte utilisateur du producteur' ),
				'type'       => 'select-users',
				'role'       => 'producteur',
				'group'      => 'Infos',
				'readonly'   => function ( $post_id ) {
					if ( TitanFrameworkOption::isOnEditScreen() ) {
						return true;
					}

					return false;
				},
				'required'   => true,
				'desc'       => 'Sélectionner le compte utilisateur du producteur. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs',
				'searchable' => true,
			),
			'referent'             => array(
				'name'         => amapress__( 'Référent' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents',
//                'required' => true,
				'desc'         => 'Référent',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent2'            => array(
				'name'         => amapress__( 'Référent 2' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents',
//                'required' => true,
				'desc'         => 'Référent 2',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent3'            => array(
				'name'         => amapress__( 'Référent 3' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents',
//                'required' => true,
				'desc'         => 'Référent 3',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'presentations'        => array(
				'name'            => amapress__( 'Productions' ),
				'show_column'     => true,
				'group'           => '3/ Présentations et contrats',
				'include_columns' => array(
					'title',
				),
				'type'            => 'related-posts',
				'query'           => 'post_type=amps_contrat&amapress_producteur=%%id%%',
			),
			'contrats'             => array(
				'name'               => amapress__( 'Contrats' ),
				'show_column'        => true,
				'show_column_values' => true,
				'group'              => '3/ Présentations et contrats',
				'include_columns'    => array(
					'title',
					'amapress_contrat_instance_name',
					'amapress_contrat_instance_type',
				),
				'type'               => 'related-posts',
				'query'              => 'post_type=amps_contrat_inst&amapress_date=active&amapress_producteur=%%id%%',
			),
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
				'role'         => amapress_can_be_referent_roles(),
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
						if ( empty( $c ) ) {
							return 0;
						}

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

add_filter( 'amapress_can_edit_producteur', function ( $can, $post_id ) {
	if ( is_admin() && amapress_can_access_admin() && ! amapress_is_admin_or_responsable() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		if ( count( $refs ) > 0 ) {
			foreach ( $refs as $r ) {
				if ( $post_id == $r['producteur'] ) {
					return $can;
				}
			}

			return false;
		}
	}

	return $can;
}, 10, 2 );