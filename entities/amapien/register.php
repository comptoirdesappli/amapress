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
					'0'  => 'Une erreur s\'est produit pendant l\'envoie des mails de bienvenue',
					'1'  => 'Un mail de bienvenue a été envoyé avec succès',
					'>1' => '%s mails de bienvenue ont été envoyés avec succès',
				),
			),
			'amp_relocate'       => array(
				'label'    => 'Localiser les adresses',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant la localisation des adresses',
					'0'  => 'Aucune adresse n\'a été localisée',
					'1'  => 'Une adresse a été localisée',
					'>1' => '%s adresses ont été localisées',
				),
			),
		),
		'fields'              => array(
//            'role_desc' => array(
//                'name' => amapress__('Rôle dans l\'AMAP'),
//                'type' => 'text',
//                'desc' => 'Rôle dans l\'AMAP',
//            ),
			'head_amapress4' => array(
				'id'   => 'fonctions_sect',
				'name' => amapress__( 'Fonctions' ),
				'type' => 'heading',
			),
			'role_desc'      => array(
				'type'   => 'custom',
				'name'   => amapress__( 'Rôle sur le site' ),
				'custom' => function ( $user_id ) {
					return '
<p id="fonctions_role_desc">Les rôles suivants donnent des accès spécifiques selon l’intitulé sélectionné</p>
<p><strong>Amap Référent producteur</strong> : <em>Accède aux informations relatives au producteur dont il est référent : contrats, inscriptions…</em>
<br/><span style="text-decoration: underline">Important :</span> Sélectionner et compléter la fiche producteur avec l’utilisateur correspondant</p>
<p><strong>Amap Coordinateur</strong> : <em>Peut éditer le collectif, créer un compte utilisateur, accède aux listes d’émargement, ...</em>
<br /><span style="text-decoration: underline">Important :</span> Membre du collectif, cocher l’étiquette Fonction correspondante ci-dessous</p>
<p><strong>Amapien</strong> : <em>Accède aux information personnalisées disponible sur le site vitrine</em>
<br />Rôle par défaut</p>
<p><strong>Amap Trésorier</strong> : <em>Accède au menu “Gestion adhésion”</em></p>
<p><strong>Amap Producteur</strong> : <em>Accès  à son contrat, aux liste des inscriptions à son contrat, aux produits</em></p>
<p><strong>Amap Responsable</strong> : <em>Accède à toutes les fonctions de gestion de l\'AMAP.</em></p>
<p><strong>Abonné, Contributeur, Auteur, Editeur</strong> sont des rôles Wordpress : ne pas utiliser </p>
<p><strong>Amap Administrateur</strong> : <em>Responsable informatique</em>
<br />Ouvre tous les droits sur le site</p>';
				}
			),
			'amap_roles'     => array(
				'name'        => amapress__( 'Membre du collectif - Rôle dans l’Amap' ),
				'type'        => 'multicheck-categories',
				'taxonomy'    => AmapressUser::AMAP_ROLE,
				'desc'        => '
<p>Pour identifier ou contacter un membre du collectif via la fonctionnalité trombinoscope du site, sélectionner l’étiquette correspondante ci-dessous ou la <a href="' . admin_url( 'edit-tags.php?taxonomy=amps_amap_role_category' ) . '">créer</a> :</p>
<p><em>Exemple : Accueil nouveaux,  Boite Contact,  Convivialité-apéro,  Coordination associative,  Distributions, Feuille de chou,  Responsable Intermittents,  Ouverture vers l\'extérieur,  Panier solidaire,  Référent miel,  Relais Réseau AMAP IdF,  Responsable légal,  Site internet,  Sortie à la ferme…</em></p>
<p>Pour modifier le collectif : <a href="' . admin_url( 'admin.php?page=amapress_collectif' ) . '">Editer le collectif</a></p>',
				'show_column' => false,
				'csv'         => false,
//                'searchable' => true,
			),
			'intermittent'   => array(
				'name'        => amapress__( 'Intermittent' ),
				'type'        => 'custom',
				'custom'      => function ( $user_id ) {
					$ret     = '';
					$amapien = AmapressUser::getBy( $user_id, true );
					if ( $amapien ) {
						if ( $amapien->isIntermittent() ) {
							$ret .= '<p>L\'utilisateur est intermittent et reçoit des alertes lorsque des paniers sont occasionnellement disponibles</p>';
							$ret .= '<input class="button button-secondary" type="submit" name="desinscr_intermittent" value="Désinscrire de la liste des intermittents" />';
						} else {
							$ret .= '<p>L\'utilisateur n\'est pas intermittent</p>';
							$ret .= '<input class="button button-secondary" type="submit" name="inscr_intermittent" value="Inscrire sur la liste des intermittents" />';
						}
					}

					return $ret;
				},
				'save'        => function ( $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );
					if ( $amapien ) {
						if ( ! $amapien->isIntermittent() && isset( $_REQUEST['inscr_intermittent'] ) ) {
							$amapien->inscriptionIntermittence( true );
						} else if ( $amapien->isIntermittent() && isset( $_REQUEST['desinscr_intermittent'] ) ) {
							$amapien->desinscriptionIntermittence();
						}
					}

					return true;
				},
				'show_column' => false,
			),

			'head_amapress0' => array(
				'id'   => 'amapress_sect',
				'name' => amapress__( 'Amapress' ),
				'type' => 'heading',
			),
			'avatar'         => array(
				'name'            => amapress__( 'Avatar' ),
				'selector-title'  => 'Sélectionnez/téléversez votre photo',
				'selector-button' => 'Utiliser cette photo',
				'type'            => 'upload',
				'custom_save'     => 'amapress_save_user_avatar',
				'desc'            => 'Avatar',
				'show_column'     => false,
			),
			'head_amapress'  => array(
				'id'   => 'address_sect',
				'name' => amapress__( 'Adresses' ),
				'type' => 'heading',
			),
			'adresse'        => array(
				'name'       => amapress__( 'Adresse' ),
				'type'       => 'textarea',
				'desc'       => 'Adresse',
				'searchable' => true,
//                'required' => true,
			),
			'code_postal'    => array(
				'name'       => amapress__( 'Code postal' ),
				'type'       => 'text',
				'desc'       => 'Code postal',
				'searchable' => true,
//                'required' => true,
			),
			'ville'          => array(
				'name'       => amapress__( 'Ville' ),
				'type'       => 'text',
				'desc'       => 'Ville',
				'searchable' => true,
//                'required' => true,
			),
			'adresse_localized'  => array(
				'name'                   => amapress__( 'Localisé' ),
				'type'                   => 'address',
				'use_as_field'           => false,
				'user'                   => true,
				'csv'                    => false,
				'field_name_prefix'      => 'amapress_user',
				'address_field_name'     => 'amapress_user_adresse',
				'postal_code_field_name' => 'amapress_user_code_postal',
				'town_field_name'        => 'amapress_user_ville',
				'show_on'                => 'edit-only',
			),
			'head_amapress2'     => array(
				'id'   => 'phones_sect',
				'name' => amapress__( 'Téléphones' ),
				'type' => 'heading',
			),
			'telephone'          => array(
				'name'       => amapress__( 'Téléphone' ),
				'type'       => 'text',
				'desc'       => 'Téléphone',
				'searchable' => true,
			),
			'telephone2'         => array(
				'name'       => amapress__( 'Téléphone 2' ),
				'type'       => 'text',
				'desc'       => 'Téléphone 2',
				'searchable' => true,
			),
			'telephone3'         => array(
				'name'        => amapress__( 'Téléphone 3' ),
				'type'        => 'text',
				'desc'        => 'Téléphone 3',
				'searchable'  => true,
				'show_on'     => 'edit-only',
				'show_column' => false,
			),
			'telephone4'         => array(
				'name'        => amapress__( 'Téléphone 4' ),
				'type'        => 'text',
				'desc'        => 'Téléphone 4',
				'searchable'  => true,
				'show_on'     => 'edit-only',
				'show_column' => false,
			),
			'moyen'              => array(
				'name'        => amapress__( 'Moyen préféré' ),
				'type'        => 'select',
				'show_column' => false,
				'options'     => array(
					'mail' => 'Email',
					'tel'  => 'Téléphone',
				),
				'desc'        => 'Moyen de communication préféré',
			),
			'head_amapress6'     => array(
				'id'      => 'contrats_sect',
				'name'    => amapress__( 'Contrats' ),
				'type'    => 'heading',
				'show_on' => 'edit-only',
			),
			'contrats'           => array(
				'name'                     => amapress__( 'Contrats' ),
				'show_column'              => true,
				'related_posts_count_func' => function ( $user_id ) {
					$adhesions = AmapressAdhesion::getAllActiveByUserId();
					if ( isset( $adhesions[ $user_id ] ) ) {
						return count( $adhesions[ $user_id ] );
					}

					return 0;
				},
				'include_columns'          => array(
					'title',
					'amapress_adhesion_quantite',
					'amapress_adhesion_lieu',
					'amapress_adhesion_date_debut',
					'amapress_total_amount',
				),
				'datatable_options'        => array(
					'paging' => false,
					'bSort'  => false,
					'info'   => false,
				),
				'type'                     => 'related-posts',
				'query'                    => 'post_type=amps_adhesion&amapress_date=active&amapress_user=%%id%%&orderby=title&order=asc',
			),
			'contrats-past'      => array(
				'name'              => amapress__( 'Contrats passés' ),
				'show_column'       => false,
				'include_columns'   => array(
					'title',
					'amapress_adhesion_quantite',
					'amapress_adhesion_lieu',
					'amapress_adhesion_date_debut',
					'amapress_total_amount',
				),
				'datatable_options' => array(
					'paging' => false,
					'bSort'  => false,
					'info'   => false,
				),
				'type'              => 'related-posts',
				'query'             => 'post_type=amps_adhesion&amapress_date=past&amapress_user=%%id%%&orderby=amapress_adhesion_date_debut&order=desc',
			),
			'head_amapress3'     => array(
				'id'   => 'coadh_sect',
				'name' => amapress__( 'Co-adhérents' ),
				'type' => 'heading',
			),
			'co-adherent-1'      => array(
				'name'         => amapress__( 'Co-adhérent 1' ),
				'type'         => 'select-users',
				'desc'         => 'Co-adhérent 1',
				'autocomplete' => true,
				'searchable'   => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'co-adherent-2'      => array(
				'name'         => amapress__( 'Co-adhérent 2' ),
				'type'         => 'select-users',
				'desc'         => 'Co-adhérent 2',
				'show_column'  => false,
				'searchable'   => true,
				'autocomplete' => true,
				'show_on'      => 'edit-only',
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'co-adherent-3'      => array(
				'name'         => amapress__( 'Co-adhérent 3' ),
				'type'         => 'select-users',
				'desc'         => 'Co-adhérent 3',
				'show_column'  => false,
				'searchable'   => true,
				'autocomplete' => true,
				'show_on'      => 'edit-only',
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'co-adherents'       => array(
				'name'       => amapress__( 'Co-adhérent(s) - sans mail' ),
				'type'       => 'text',
				'desc'       => 'Co-adhérent(s) - sans mail',
				'searchable' => true,
			),
			'co-adherents-infos' => array(
				'name'       => amapress__( 'Co-adhérent(s) - sans mail - infos' ),
				'type'       => 'text',
				'desc'       => 'Co-adhérent(s) - sans mail - autres infos',
				'searchable' => true,
			),
			'all-coadherents'    => array(
				'name'            => amapress__( 'Co-adhérents' ),
				'show_column'     => false,
				'include_columns' => array(
					'name',
					'email',
					'role',
					'amapress_user_telephone',
					'amapress_user_adresse',
				),
				'type'            => 'related-users',
				'query'           => 'amapress_coadherents=%%id%%',
			),
//            'co-adherents-mail' => array(
//                'name' => amapress__('Co-adhérent - email'),
//                'type' => 'text',
//                'desc' => 'Co-adhérent(s) - email',
//            ),


			'head_amapress5'     => array(
				'id'   => 'emarg_sect',
				'name' => amapress__( 'Liste émargement' ),
				'type' => 'heading',
			),
			'comment_emargement' => array(
				'name'        => amapress__( 'Commentaire pour la liste émargement' ),
				'type'        => 'textarea',
				'desc'        => 'Commentaire pour la liste émargement',
				'show_column' => false,
				'csv'         => false,
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
		'row_actions'         => array(
			'add_inscription' => [
				'label'  => 'Ajouter une inscription',
				'href'   => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription&user_id=%id%' ),
				'target' => '_blank',
			],
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
	foreach ( AmapressAdhesion::getUserActiveAdhesions( $userID ) as $adh ) {
		if ( $adh->getAdherentId() == $userID ) {
			$adh->setAdherent2( $user->getCoAdherent1() );
			$adh->setAdherent3( $user->getCoAdherent2() );
			$adh->setAdherent4( $user->getCoAdherent3() );
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
//	$key        = 'amapress_can_delete_user_safe';
//	$used_users_safe = wp_cache_get( $key );
//	if ( false === $used_users_safe ) {
//		$used_users_safe = [];
//		$single_user_keys = array(
//			'amapress_adhesion_paiement_user',
//			'amapress_adhesion_adherent',
//			'amapress_adhesion_adherent2',
//			'amapress_adhesion_adherent3',
//			'amapress_adhesion_adherent4',
//			'amapress_intermittence_panier_repreneur',
//			'amapress_intermittence_panier_adherent',
//			'amapress_user_commande_amapien',
//			'amapress_visite_participants',
//			'amapress_distribution_responsables',
//			'amapress_amap_event_participants',
//			'amapress_assemblee_generale_participants',
//		);
//		global $wpdb;
//		$meta_query = [];
//		foreach ( $single_user_keys as $single_user_key ) {
//			$meta_query[] = $wpdb->prepare( "($wpdb->postmeta.meta_key = %s)", $single_user_key );
//		}
//
//		$where  = implode( ' OR ', $meta_query );
//		$values = $wpdb->get_col( "SELECT DISTINCT $wpdb->postmeta.meta_value FROM $wpdb->postmeta WHERE $where" );
//		foreach ( $values as $v ) {
//			$v = maybe_unserialize( $v );
//			if ( is_array( $v ) ) {
//				$used_users_safe += $v;
//			} else {
//				$used_users_safe[] = $v;
//			}
//		}
//		$used_users_safe = array_unique( array_map( 'intval', $used_users_safe ) );
//		wp_cache_set( $key, $used_users_safe );
//	}

	$key             = 'amapress_can_delete_user_referents';
	$users_referents = wp_cache_get( $key );
	if ( false === $users_referents ) {
		$users_referents  = [];
		$single_user_keys = array(
			'amapress_lieu_distribution_referent',
			'amapress_producteur_user',
			'amapress_producteur_referent',
			'amapress_producteur_referent2',
			'amapress_producteur_referent3',
		);
		$lieux_ids        = Amapress::get_lieu_ids();
		if ( count( $lieux_ids ) > 1 ) {
			foreach ( $lieux_ids as $lieu_id ) {
				$single_user_keys[] = "amapress_producteur_referent_{$lieu_id}";
				$single_user_keys[] = "amapress_producteur_referent2_{$lieu_id}";
				$single_user_keys[] = "amapress_producteur_referent3_{$lieu_id}";
			}
		}
		global $wpdb;
		$meta_query = [];
		foreach ( $single_user_keys as $single_user_key ) {
			$meta_query[] = $wpdb->prepare( "($wpdb->postmeta.meta_key = %s)", $single_user_key );
		}

		$where  = implode( ' OR ', $meta_query );
		$values = $wpdb->get_col( "SELECT DISTINCT $wpdb->postmeta.meta_value FROM $wpdb->postmeta WHERE $where" );
		foreach ( $values as $v ) {
			$v = maybe_unserialize( $v );
			if ( is_array( $v ) ) {
				$users_referents = array_merge( $users_referents, $v );
			} else {
				$users_referents[] = $v;
			}
		}
		$users_referents = array_unique( array_map( 'intval', $users_referents ) );
		wp_cache_set( $key, $users_referents );
	}

//	$key             = 'amapress_can_delete_user_collectif';
//	$collectif_users = wp_cache_get( $key );
//	if ( false === $collectif_users ) {
//		$collectif_users = get_users( wp_parse_args( 'amapress_role=collectif&fields=id' ) );
//		wp_cache_set( $key, $collectif_users );
//	}

	$key                 = 'amapress_can_delete_user_contrats';
	$users_with_contrats = wp_cache_get( $key );
	if ( false === $users_with_contrats ) {
		$users_with_contrats = get_users( wp_parse_args( 'amapress_contrat=active&fields=id' ) );
		wp_cache_set( $key, $users_with_contrats );
	}

	$related_users   = AmapressContrats::get_related_users( $user_id );
	$related_users[] = $user_id;

	$can_delete = true;
	foreach ( $related_users as $id ) {
		if ( in_array( $id, $users_with_contrats ) ) {
			$can_delete = false;
			break;
		}
		if ( in_array( $id, $users_referents ) ) {
			$can_delete = false;
			break;
		}
	}

	//return ! in_array( $user_id, $users_referents ) && count( $related_users ) < ( in_array( $related_users, $user_id ) ? 2 : 1 );
	return $can_delete;
}

//function amapress_import_user_data_validate($v, $k) {
//    if ('user_email' == $k || 'first_name' == $k || )
//}
//add_filter('amapress_import_user_data', 'amapress_import_user_data_validate', 11, 2);

add_filter( 'amapress_register_admin_bar_menu_items', 'amapress_register_admin_bar_menu_items' );
function amapress_register_admin_bar_menu_items( $items ) {
	$contrat_to_renew = get_posts_count( 'post_type=amps_contrat_inst&amapress_date=renew' );
	$this_week_start  = Amapress::start_of_week( amapress_time() );
	$this_week_end    = Amapress::end_of_week( amapress_time() );
	$next_distribs    = AmapressDistribution::getNextDistribs( Amapress::add_a_week( amapress_time(), - 1 ), 3, null );
	usort( $next_distribs, function ( $a, $b ) {
		/** @var AmapressDistribution $a */
		/** @var AmapressDistribution $b */
		if ( $b->getDate() == $a->getDate() ) {
			return 0;
		}

		return $a->getDate() < $b->getDate() ? - 1 : 1;
	} );
	$liste_emargement_items   = [];
	$liste_emargement_items[] = array(
		'id'         => 'amapress_emargement_last_week',
		'title'      => 'Semaine dernière',
		'capability' => 'edit_distribution',
		'target'     => '_blank',
		'href'       => admin_url( 'edit.php?post_type=amps_distribution&amapress_date=lastweek' ),
	);
	foreach ( $next_distribs as $dist ) {
		if ( $dist->getDate() < $this_week_start ) {
			continue;
		}
		$tit = esc_html( $dist->getTitle() );
		if ( $this_week_start < $dist->getDate() && $dist->getDate() < $this_week_end ) {
			$tit = '<strong><em>' . $tit . '</em></strong>';
		}
		$liste_emargement_items[] = array(
			'id'         => 'amapress_emargement_' . $dist->ID,
			'title'      => $tit,
			'capability' => 'edit_distribution',
			'target'     => '_blank',
			'href'       => $dist->getListeEmargementHref(),
		);
	}
	$liste_emargement_items[] = array(
		'id'         => 'amapress_emargement_other',
		'title'      => 'Autres',
		'capability' => 'edit_distribution',
		'target'     => '_blank',
		'href'       => admin_url( 'edit.php?post_type=amps_distribution&amapress_date=active' ),
	);

	$dist_items = [];
	$dist_dates = [];
	foreach ( $next_distribs as $dist ) {
		if ( ! in_array( $dist->getDate(), $dist_dates ) ) {
			$dist_dates[] = $dist->getDate();
		}
		$tit = esc_html( $dist->getTitle() );
		if ( $this_week_start < $dist->getDate() && $dist->getDate() < $this_week_end ) {
			$tit = '<strong><em>' . $tit . '</em></strong>';
		}
		$dist_items[] = array(
			'id'         => 'amapress_dist_' . $dist->ID,
			'title'      => $tit,
			'capability' => 'edit_distribution',
			'href'       => $dist->getAdminEditLink(),
		);
	}
	$dist_items[] = array(
		'id'         => 'amapress_dist_other',
		'title'      => 'Autres',
		'capability' => 'edit_distribution',
		'href'       => admin_url( 'edit.php?post_type=amps_distribution&amapress_date=active' ),
	);

	$panier_rens_items = [];
	$panier_edit_items = [];
	foreach ( $dist_dates as $dist_date ) {
		foreach ( AmapressPaniers::getPaniersForDist( $dist_date ) as $panier ) {
			if ( $panier->getRealDate() != $dist_date ) {
				continue;
			}
			$tit = esc_html( $panier->getTitle() );
			if ( $this_week_start < $panier->getRealDate() && $panier->getRealDate() < $this_week_end ) {
				$tit = '<strong><em>' . $tit . '</em></strong>';
			}
			$panier_rens_items[] = array(
				'id'         => 'amapress_rens_panier_' . $panier->ID,
				'title'      => $tit,
				'capability' => 'edit_panier',
				'href'       => $panier->getAdminEditLink(),
			);
			$panier_edit_items[] = array(
				'id'         => 'amapress_edit_panier_' . $panier->ID,
				'title'      => $tit,
				'capability' => 'edit_panier',
				'href'       => $panier->getAdminEditLink(),
			);
		}
	}
	$panier_rens_items[] = array(
		'id'         => 'amapress_rens_panier_other',
		'title'      => 'Autres',
		'capability' => 'edit_panier',
		'href'       => admin_url( 'edit.php?post_type=amps_panier&amapress_date=active' ),
	);
	$panier_edit_items[] = array(
		'id'         => 'amapress_edit_panier_other',
		'title'      => 'Autres',
		'capability' => 'edit_panier',
		'href'       => admin_url( 'edit.php?post_type=amps_panier&amapress_date=active' ),
	);

	$items[]    = array(
		'id'        => 'amapress-site-name',
		'title'     => 'Bienvenu sur le site de ' . get_bloginfo( 'name' ),
		'condition' => function () {
			return ! amapress_can_access_admin();
		}
	);
	$main_items = array(
		array(
			'id'         => 'amapress_add_inscription',
			'title'      => 'Ajouter une inscription',
			'capability' => 'manage_contrats',
			'href'       => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription' ),
		),
		array(
			'id'         => 'amapress_inscriptions',
			'title'      => 'Les inscriptions',
			'capability' => 'manage_contrats',
			'href'       => admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active' ),
		),
	);
	$cnt        = AmapressAdhesion::getAdhesionToConfirmCount();
	if ( $cnt ) {
		$main_items[] = array(
			'id'         => 'amapress_inscr_to_confirm',
			'title'      => "<span class='badge'>$cnt</span> inscriptions à confirmer",
			'capability' => 'manage_contrats',
			'href'       => admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active&amapress_status=to_confirm' ),
		);
	}
	$main_items = array_merge( $main_items,
		array(
			'id'         => 'amapress_contrats',
			'title'      => 'Les contrats',
			'capability' => 'manage_contrats',
			'href'       => admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ),
		),
		array(
			'id'         => 'amapress_add_coinscription',
			'title'      => 'Ajouter un coadhérent',
			'capability' => 'manage_contrats',
			'href'       => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_coadherent' ),
		)
	);

	$inscr_distrib_href = Amapress::get_inscription_distrib_page_href();
	if ( ! empty( $inscr_distrib_href ) ) {
		$main_items[] = array(
			'id'         => 'amapress_inscription_distribution',
			'title'      => 'Inscription aux distributions',
			'capability' => 'edit_distribution',
			'href'       => $inscr_distrib_href,
		);
	}

	$main_items = array_merge(
		$main_items,
		array(
			array(
				'id'         => 'amapress_quantite_contrats',
				'title'      => 'Quantités prochaine distrib',
				'capability' => 'edit_distribution',
				'href'       => admin_url( 'admin.php?page=contrats_quantites_next_distrib' ),
			),
			//TODO : prochaine date de remise si referent
			array(
				'id'         => 'amapress_calendar_paiements',
				'title'      => 'Calendrier chèques producteurs',
				'capability' => 'manage_contrats',
				'href'       => admin_url( 'admin.php?page=calendar_contrat_paiements' ),
			),
			array(
				'id'         => 'amapress_contrat_to_renew',
				'title'      => '<span class="badge">' . $contrat_to_renew . '</span> Contrats à renouveler/clôturer',
				'capability' => 'edit_contrat_instance',
				'condition'  => function () use ( $contrat_to_renew ) {
					return $contrat_to_renew > 0;
				},
				'href'       => admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=renew' ),
			),
			array(
				'id'         => 'amapress_emargement',
				'title'      => 'Listes émargement',
				'capability' => 'edit_distribution',
				'items'      => $liste_emargement_items
			),
			array(
				'id'         => 'amapress_dists',
				'title'      => 'Info Distrib/Changer de lieu',
				'capability' => 'edit_distribution',
				'items'      => $dist_items
			),
			array(
				'id'         => 'amapress_rens_paniers',
				'title'      => 'Renseigner paniers',
				'capability' => 'edit_panier',
				'items'      => $panier_rens_items
			),
			array(
				'id'         => 'amapress_change_paniers',
				'title'      => 'Déplacer/annuler paniers',
				'capability' => 'edit_panier',
				'items'      => $panier_edit_items
			),
			array(
				'id'         => 'amapress_edit_collectif',
				'title'      => 'Editer le collectif',
				'capability' => 'edit_users',
				'href'       => admin_url( 'admin.php?page=amapress_collectif' ),
			),
			array(
				'id'         => 'amapress_edit_intermittents',
				'title'      => 'Intermittents',
				'capability' => 'edit_users',
				'href'       => admin_url( 'users.php?amapress_contrat=intermittent' ),
			),
			array(
				'id'         => 'amapress_admin_submenu',
				'title'      => 'Admin',
				'capability' => 'manage_options',
				'items'      => [
					array(
						'id'         => 'amapress_state',
						'title'      => 'Etat Amapress',
						'capability' => 'manage_options',
						'href'       => admin_url( 'admin.php?page=amapress_state' ),
					),
					array(
						'id'         => 'amapress_welcome_mail',
						'title'      => 'Mail de bienvenue',
						'capability' => 'manage_amapress',
						'href'       => admin_url( 'admin.php?page=amapress_mail_options_page&tab=welcome_mail' ),
					),
					array(
						'id'         => 'amapress_public_contacts',
						'title'      => 'Contacts public',
						'capability' => 'manage_amapress',
						'href'       => admin_url( 'admin.php?page=amapress_contact_options_page' ),
					),
					array(
						'id'         => 'amapress_log_mails',
						'title'      => 'Logs des mails envoyés',
						'capability' => 'manage_options',
						'href'       => admin_url( 'admin.php?page=amapress_mailqueue_options_page&tab=amapress_mailqueue_mail_logs' ),
					),
					array(
						'id'         => 'amapress_backup',
						'title'      => ( amapress_is_plugin_active( 'backupwordpress' ) ? '<span class="dashicons dashicons-yes" style="color: green"></span>' : '<span class="dashicons dashicons-warning" style="color:red"></span>' ) . 'Sauvegardes',
						'capability' => 'manage_options',
						'href'       => amapress_is_plugin_active( 'backupwordpress' ) ? admin_url( 'tools.php?page=backupwordpress' ) : admin_url( 'admin.php?page=amapress_state' ),
					),
				]
			),
		)
	);
	$items[]    = array(
		'id'        => 'amapress',
		'title'     => '<span class="ab-icon"></span><span class="ab-label">Amapress</span>',
		'condition' => function () {
			return amapress_can_access_admin();
		},
		'items'     => $main_items,
	);

	return $items;
}

add_filter( 'tf_replace_placeholders_user', function ( $text, $post_id ) {
	$current_user = AmapressUser::getBy( amapress_current_user_id() );
	$text         = amapress_replace_mail_placeholders( $text, $current_user );

	return $text;
}, 10, 2 );

add_action( 'personal_options', 'amapress_add_infos_to_user_editor', 20 );
function amapress_add_infos_to_user_editor( WP_User $user ) {
	$amapien = AmapressUser::getBy( $user );
	$roles   = esc_html( $amapien->getAmapRolesString() );
	echo "<tr class='row-action-wrap'><th scope='row'><label>Rôles</label></th><td>$roles</td></tr>";
	echo "<tr class='row-action-wrap'><th scope='row'><label>Liens</label></th><td>
<a href='#contrats_sect'>Contrats</a>, <a href='#fonctions_sect'>Fonctions</a>, <a href='#address_sect'>Coordonnées</a>, <a href='#phones_sect'>Téléphones</a>, <a href='#coadh_sect'>Co-adhérents</a>, 
	</td></tr>";
	$last_login = get_user_meta( $user->ID, 'last_login', true );
	$user_infos = 'Utilisateur créé le ' . date_i18n( 'd/m/Y H:i:s', strtotime( $user->user_registered ) );
	$user_infos .= ' ; Dernière connexion : ' . ( empty( $last_login ) ? 'jamais' : date_i18n( 'd/m/Y H:i:s', intval( $last_login ) ) );
	echo "<tr class='row-action-wrap'><th scope='row'><label>Infos</label></th><td>$user_infos</td></tr>";

	$is_ref_prod_list   = AmapressContrats::getReferentProducteursAndLieux( $user->ID );
	$is_ref_prod        = ! empty( $is_ref_prod_list );
	$ref_prod_message   = implode( ', ', array_unique( array_map(
		function ( $r ) {
			$prod = AmapressProducteur::getBy( $r['producteur'] );
			if ( empty( $prod ) ) {
				return '';
			}

			return 'référent de <a href=\'' . $prod->getAdminEditLink() . '\'>' . esc_html( $prod->getTitle() ) . '</a>';
		}, $is_ref_prod_list
	) ) );
	$check_role_js_code = 'return true;';
	if ( $is_ref_prod ) {
		$check_role_js_code = 'return ("referent" === value) || ("responsable_amap" === value) || ("producteur" === value);';
	}
	echo '<script type="text/javascript">
jQuery(function() {
  jQuery(".user-role-wrap").insertAfter(jQuery("#fonctions_role_desc").closest("tr"));
  jQuery("#role").addClass("check_amap_role");
  jQuery.validator.addMethod("check_amap_role", function (value, element) {
	' . $check_role_js_code . '
  }, "<p class=\'error\'>Vous ne pouvez pas diminuer son rôle. L\'utilisateur est actuellement ' . $ref_prod_message . '. Vous devez le déassocier de ce(s) producteur(s) avant de changer son rôle.</p>");
});
</script>';
}