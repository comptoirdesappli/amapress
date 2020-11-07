<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_is_current_user_producteur( $post_id = null ) {
	return amapress_current_user_can( 'producteur' );
}

function amapress_is_referents_fields_readonly( $post_id = null ) {
	return ! amapress_is_admin_or_responsable();
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_producteur' );
function amapress_register_entities_producteur( $entities ) {
	$entities['producteur'] = array(
		'singular'                 => __( 'Producteur', 'amapress' ),
		'plural'                   => __( 'Producteurs', 'amapress' ),
		'public'                   => true,
		'editor'                   => true,
		'thumb'                    => true,
		'slug'                     => __( 'producteurs', 'amapress' ),
		'menu_icon'                => 'flaticon-tractor',
		'show_in_menu'             => false,
		'quick_edit'               => false,
		'custom_archive_template'  => true,
		'default_orderby'          => 'post_title',
		'default_order'            => 'ASC',
		'other_def_hidden_columns' => array( 'amps_lo' ),
		'show_admin_bar_new'       => false,
		'row_actions'              => array(
			'relocate' => array(
				'label'     => 'Géolocaliser',
				'condition' => function ( $user_id ) {
					$prod = AmapressProducteur::getBy( $user_id );

					return $prod && ! $prod->isAdresseExploitationLocalized();
				},
				'confirm'   => true,
			),
		),
		'views'                    => array(
			'remove'  => array( 'mine' ),
			'exp_csv' => true,
		),
		'groups'                   => array(
			'Infos' => [
				'context' => 'side',
			],
		),
		'edit_header'              => function ( $post ) {
			if ( TitanFrameworkOption::isOnEditScreen() ) {
				$producteur = AmapressProducteur::getBy( $post );
				if ( empty( $producteur->getUser() ) ) {
					echo '<div class="notice notice-error"><p>' . 'Producteur invalide : pas d\'utilisateur associé' . '</p></div>';
				}
				if ( ! $producteur->isAdresseExploitationLocalized() ) {
					amapress_add_admin_notice( 'Adresse du producteur non localisée', 'warning', false );
				}
				if ( from( $producteur->getContrats() )->any( function ( $contrat ) {
					/** @var AmapressContrat $contrat */
					return empty( $contrat->getAllReferentsIds() );
				} ) ) {
					echo '<div class="notice notice-error"><p>' . 'Producteur sans référent' . '</p></div>';
				}
			}

			TitanFrameworkOption::echoFullEditLinkAndWarning();

			echo '<h2>' . 'Présentation du producteur <em>(Biographie, historique de la ferme...)</em>' . '</h2>';
		},
		'fields'                   => array(
			'nom_exploitation'     => array(
				'name'       => __( 'Nom de l\'exploitation', 'amapress' ),
				'type'       => 'text',
				'desc'       => 'Nom de la ferme',
				'group'      => '1/ Emplacement',
				'searchable' => true,
			),
			'adresse_exploitation' => array(
				'name'          => __( 'Adresse de la ferme', 'amapress' ),
				'type'          => 'address',
				'use_as_field'  => true,
				'use_enter_gps' => true,
				'desc'          => 'Adresse de la ferme',
				'group'         => '1/ Emplacement',
				'searchable'    => true,
			),
			'acces'                => array(
				'name'       => __( 'Accès', 'amapress' ),
				'type'       => 'editor',
				'group'      => '1/ Emplacement',
				'desc'       => 'Accès',
				'searchable' => true,
			),
			'user'                 => array(
				'name'       => __( 'Compte utilisateur du producteur', 'amapress' ),
				'type'       => 'select-users',
				'role'       => 'producteur',
				'group'      => 'Infos',
				'readonly'   => function ( $post_id ) {
					if ( TitanFrameworkOption::isOnEditScreen() ) {
						return true;
					}

					if ( amapress_is_current_user_producteur() ) {
						return true;
					}

					return false;
				},
				'required'   => true,
				'desc'       => sprintf( 'Sélectionner le compte utilisateur du producteur. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="%s" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs', admin_url( 'user-new.php' ) ),
				'searchable' => true,
			),
			'referent'             => array(
				'name'         => __( 'Référent', 'amapress' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents',
				'desc'         => 'Référent producteur pour tous les lieux',
				'searchable'   => true,
				'autocomplete' => true,
				'orderby'      => 'display_name',
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'order'        => 'ASC',
			),
			'referent2'            => array(
				'name'         => __( 'Référent 2', 'amapress' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents',
				'desc'         => 'Deuxième référent producteur pour tous les lieux',
				'searchable'   => true,
				'autocomplete' => true,
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'referent3'            => array(
				'name'         => __( 'Référent 3', 'amapress' ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents',
				'desc'         => 'Troisième référent producteur pour tous les lieux',
				'searchable'   => true,
				'autocomplete' => true,
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'presentations'        => array(
				'name'            => __( 'Productions', 'amapress' ),
				'show_column'     => true,
				'group'           => '3/ Présentations et contrats',
				'include_columns' => array(
					'title',
				),
				'type'            => 'related-posts',
				'query'           => 'post_type=amps_contrat&amapress_producteur=%%id%%',
			),
			'contrats'             => array(
				'name'               => __( 'Contrats', 'amapress' ),
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
		'help_edit'                => array(),
		'help_view'                => array(),
	);

	return $entities;
}

add_filter( 'amapress_producteur_fields', 'amapress_producteur_fields' );
function amapress_producteur_fields( $fields ) {
	$lieux = Amapress::get_lieux();
	if ( count( $lieux ) > 1 || isset( $_GET['all_lieux'] ) ) {
		foreach ( $lieux as $lieu ) {
			$fields[ 'referent_' . $lieu->ID ] = array(
				'name'         => sprintf( __( 'Référent %s', 'amapress' ), $lieu->getShortName() ),
				'type'         => 'select-users',
				'role'         => amapress_can_be_referent_roles(),
				'group'        => '2/ Référents',
				'searchable'   => true,
				'autocomplete' => true,
				'readonly'     => 'amapress_is_referents_fields_readonly',
				'desc'         => sprintf( 'Référent producteur spécifique à %s', $lieu->getTitle() ),
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

add_action( 'amapress_row_action_producteur_relocate', 'amapress_row_action_producteur_relocate' );
function amapress_row_action_producteur_relocate( $post_id ) {
	$prod = AmapressProducteur::getBy( $post_id );
	if ( $prod ) {
		$prod->resolveAddress();
	}
	wp_redirect_and_exit( wp_get_referer() );
}