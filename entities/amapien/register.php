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
			'head_amapress0'     => array(
				'name' => amapress__( 'Amapress' ),
				'type' => 'heading',
			),
			'avatar'             => array(
				'name'        => amapress__( 'Avatar' ),
				'type'        => 'upload',
				'custom_save' => 'amapress_save_user_avatar',
				'desc'        => 'Avatar',
				'show_column' => false,
			),
			'head_amapress'      => array(
				'name' => amapress__( 'Adresses' ),
				'type' => 'heading',
			),
			'adresse'            => array(
				'name'       => amapress__( 'Adresse' ),
				'type'       => 'textarea',
				'desc'       => 'Adresse',
				'searchable' => true,
//                'required' => true,
			),
			'code_postal'        => array(
				'name'       => amapress__( 'Code postal' ),
				'type'       => 'text',
				'desc'       => 'Code postal',
				'searchable' => true,
//                'required' => true,
			),
			'ville'              => array(
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
			'head_amapress3'     => array(
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
//            'co-adherents-mail' => array(
//                'name' => amapress__('Co-adhérent - email'),
//                'type' => 'text',
//                'desc' => 'Co-adhérent(s) - email',
//            ),
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
			'head_amapress4'     => array(
				'name' => amapress__( 'Fonctions' ),
				'type' => 'heading',
			),
			'amap_roles'         => array(
				'name'        => amapress__( 'Rôles dans l\'AMAP' ),
				'type'        => 'multicheck-categories',
				'taxonomy'    => AmapressUser::AMAP_ROLE,
				'desc'        => 'Rôles dans le Collectif de l\'AMAP',
				'show_column' => false,
//                'searchable' => true,
			),
			'intermittent'       => array(
				'name'        => amapress__( 'Intermittent' ),
				'type'        => 'checkbox',
				'desc'        => 'Cocher pour que l\'utilisateur devienne intermittent et reçoive des alertes lorsque des paniers sont occasionnellement disponibles',
				'show_column' => false,
			),
			'head_amapress5'     => array(
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
				'href'   => admin_url( 'post-new.php?post_type=amps_adhesion&amapress_adhesion_adherent=%id%' ),
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
	$key        = 'amapress_can_delete_user';
	$used_users = wp_cache_get( $key );
	if ( false === $used_users ) {
		$single_user_keys = array(
			'amapress_adhesion_paiement_user',
			'amapress_adhesion_adherent',
			'amapress_adhesion_adherent2',
			'amapress_adhesion_adherent3',
			'amapress_adhesion_adherent4',
			'amapress_intermittence_panier_repreneur',
			'amapress_intermittence_panier_adherent',
			'amapress_lieu_distribution_referent',
			'amapress_producteur_user',
			'amapress_producteur_referent',
			'amapress_producteur_referent2',
			'amapress_producteur_referent3',
			'amapress_user_commande_amapien',
			'amapress_visite_participants',
			'amapress_distribution_responsables',
			'amapress_amap_event_participants',
			'amapress_assemblee_generale_participants',
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
//		$posts = get_posts_count( array(
//			'post_status'    => 'any',
//			'post_type'      => array(
//				AmapressAdhesion::INTERNAL_POST_TYPE,
//				AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
//				AmapressIntermittence_panier::INTERNAL_POST_TYPE,
//				AmapressProducteur::INTERNAL_POST_TYPE,
//				AmapressUser_commande::INTERNAL_POST_TYPE,
//				AmapressLieu_distribution::INTERNAL_POST_TYPE
//			),
//			'posts_per_page' => - 1,
//			'meta_query'     => array( $meta_query ),
//		) );

		$where  = implode( ' OR ', $meta_query );
		$values = $wpdb->get_col( "SELECT DISTINCT $wpdb->postmeta.meta_value FROM $wpdb->postmeta WHERE $where" );
		foreach ( $values as $v ) {
			$v = maybe_unserialize( $v );
			if ( is_array( $v ) ) {
				$used_users += $v;
			} else {
				$used_users[] = $v;
			}
		}
		$used_users = array_unique( array_map( 'intval', $used_users ) );
		wp_cache_set( $key, $used_users );
	}

	return ! in_array( $user_id, $used_users ) && count( AmapressContrats::get_related_users( $user_id ) ) <= 1;
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
			'href'       => $dist->getListeEmargementHref(),
		);
	}
	$liste_emargement_items[] = array(
		'id'         => 'amapress_emargement_other',
		'title'      => 'Autres',
		'capability' => 'edit_distribution',
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

	$items[] = array(
		'id'        => 'amapress-site-name',
		'title'     => 'Bienvenu sur le site de ' . get_bloginfo( 'name' ),
		'condition' => function () {
			return ! amapress_can_access_admin();
		}
	);
	$items[] = array(
		'id'        => 'amapress',
		'title'     => 'Amapress',
		'condition' => function () {
			return amapress_can_access_admin();
		},
		'items'     => array(
			array(
				'id'         => 'amapress_add_inscription',
				'title'      => 'Ajouter une inscription',
				'capability' => 'manage_contrats',
				'href'       => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription' ),
			),
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
				'title'      => '<span class="badge">' . $contrat_to_renew . '</span> Contrats à renouveller',
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
//admin.php?page=amapress_collectif
//			array(
//				'id' => 'amapress_',
//				'title' => '',
//				'href' => admin_url(),
//			),
		),
	);

	return $items;
}

add_filter( 'tf_replace_placeholders_user', function ( $text, $post_id ) {
	$current_user = AmapressUser::getBy( amapress_current_user_id() );
	$text         = amapress_replace_mail_placeholders( $text, $current_user );

	return $text;
}, 10, 2 );