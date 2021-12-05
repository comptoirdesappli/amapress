<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_mailinglist' );
function amapress_register_entities_mailinglist( $entities ) {
	$entities['mailinglist'] = array(
		'internal_name'    => 'amps_mailing',
		'singular'         => __( 'Liste de diffusion', 'amapress' ),
		'plural'           => __( 'Listes de diffusions', 'amapress' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'labels'           => array(
			'add_new'      => __( 'Configurer une liste de diffusion existante', 'amapress' ),
			'add_new_item' => __( 'Configurer une liste de diffusion existante', 'amapress' ),
		),
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'views'            => array(
			'_dyn_' => function () {
				$ret = array();
				amapress_add_view_button(
					$ret, 'sync_all',
					"post_type=amps_mailing&sync_all",
					__( 'Tout synchroniser', 'amapress' ) );

				return $ret;
			}
		),
//        'title_format' => 'amapress_visite_title_formatter',
//        'slug_format' => 'from_title',
		'slug'             => __( 'mailinglists', 'amapress' ),
//        'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'dashicons-email-alt',
		'fields'           => array(
			'name'                   => array(
				'group'    => __( 'Nom', 'amapress' ),
				'name'     => __( 'Nom', 'amapress' ),
				'type'     => 'select',
				'desc'     => __( 'Choisir la liste de diffusion à configurer.', 'amapress' ),
				'options'  => 'amapress_get_mailinglists',
				'required' => true,
			),
			'desc'                   => array(
				'group' => __( 'Description', 'amapress' ),
				'name'  => __( 'Description', 'amapress' ),
				'type'  => 'text',
			),
			'moderation'             => array(
				'group'        => __( 'Modération', 'amapress' ),
				'name'         => __( 'Modération', 'amapress' ),
				'type'         => 'select',
				'cache'        => false,
				'desc'         => __( 'Choisir le type de modération – option proposée par votre gestionnaire de liste (Sympa, Mailchimp, …).', 'amapress' ),
				'options'      => 'amapress_get_mailinglist_moderation_options',
				'column'       => 'amapress_get_mailinglist_moderation_column',
				'custom_get'   => 'amapress_get_mailinglist_moderation',
				'custom_save'  => 'amapress_set_mailinglist_moderation',
				'show_on'      => 'edit-only',
				'readonly'     => function ( TitanFrameworkOption $option ) {
					return amapress_mailinglist_should_moderation_readonly( $option );
				},
				'after_option' => function ( TitanFrameworkOption $option ) {
					if ( amapress_mailinglist_should_moderators_readonly( $option ) ) {
						$ml = new Amapress_MailingListConfiguration( $option->getPostID() );

						echo '<p style="color:red;font-weight: bold">' . __( 'La gestion de la modération se fait manuellement dans ', 'amapress' ) .
						     Amapress::makeLink( $ml->getMailingList()->getConfigurationLink(),
							     __( 'Configuration de la mailinglist', 'amapress' ), true, true )
						     . '</p>';
					}
				},
			),
			'moderators'             => array(
				'group'        => __( 'Modérateurs', 'amapress' ),
				'name'         => __( 'Modérateurs', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Choisir un ou plusieurs modérateurs en charge de valider les mails avant diffusion.', 'amapress' ),
				'readonly'     => function ( TitanFrameworkOption $option ) {
					return amapress_has_mailinglist_moderators_queries( $option )
					       || amapress_mailinglist_should_moderators_readonly( $option );
				},
				'after_option' => function ( TitanFrameworkOption $option ) {
					if ( amapress_mailinglist_should_moderators_readonly( $option ) ) {
						$ml = new Amapress_MailingListConfiguration( $option->getPostID() );

						echo '<p style="color:red;font-weight: bold">' . __( 'La gestion des modérateurs se fait manuellement dans ', 'amapress' ) .
						     Amapress::makeLink( $ml->getMailingList()->getModeratorsLink(),
							     __( 'Gestion des modérateurs', 'amapress' ), true, true )
						     . '</p>';
					}
				},
				'custom_get'   => 'amapress_get_mailinglist_moderators',
				'custom_save'  => 'amapress_set_mailinglist_moderators',
				'show_on'      => 'edit-only',
//                'show_column' => false,
			),
			'moderators_queries'     => array(
				'group'    => __( 'Modérateurs', 'amapress' ),
				'name'     => __( 'Groupes inclus dans les modérateurs', 'amapress' ),
				'type'     => 'multicheck',
				'desc'     => __( 'Cocher le ou les groupes à intégrer.', 'amapress' ),
				'options'  => 'amapress_get_mailinglist_moderators_queries',
				'readonly' => 'amapress_mailinglist_should_moderators_readonly',
//                'required' => true,
			),
			'moderators_other_users' => array(
				'group'        => __( 'Modérateurs', 'amapress' ),
				'name'         => __( 'Modérateurs hors groupe', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'readonly'     => 'amapress_mailinglist_should_moderators_readonly',
//                'desc' => __('Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.', 'amapress'),
			),
			'waiting'                => array(
				'group'   => __( 'Modération', 'amapress' ),
				'name'    => __( 'Emails en attente modération', 'amapress' ),
				'type'    => 'custom',
				'desc'    => __( 'Liste du ou des emails à valider.', 'amapress' ),
				'column'  => 'amapress_get_mailinglist_waiting',
				'custom'  => 'amapress_get_mailinglist_waiting_list',
				'show_on' => 'edit-only',
			),
			'members_count'          => array(
				'group'   => __( 'Membres', 'amapress' ),
				'name'    => __( 'Membres', 'amapress' ),
				'type'    => 'custom',
				'desc'    => __( 'Liste des membres', 'amapress' ),
				'column'  => 'amapress_get_mailinglist_members_count',
				'custom'  => 'amapress_get_mailinglist_members_count',
				'show_on' => 'edit-only',
			),
			'queries'                => array(
				'group'    => __( 'Membres', 'amapress' ),
				'name'     => __( 'Groupes inclus', 'amapress' ),
				'type'     => 'multicheck',
				'desc'     => __( 'Cocher le ou les groupes à intégrer.', 'amapress' ),
				'options'  => 'amapress_get_mailinglist_queries',
				'required' => true,
			),
			'other_users'            => array(
				'group'        => __( 'Membres', 'amapress' ),
				'name'         => __( 'Amapiens hors groupe', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.', 'amapress' ),
			),
			'bounces'                => array(
				'group'   => __( 'Membres', 'amapress' ),
				'name'    => __( 'Taux d\'erreur', 'amapress' ),
				'type'    => 'custom',
				'desc'    => 'Taux d\erreur lors de l\'envoi d\{emails par la liste',
				'custom'  => 'amapress_get_mailinglist_bounce_rate',
				'column'  => 'amapress_get_mailinglist_bounce_rate',
				'show_on' => 'edit-only',
			),
			'reply_to'               => array(
				'group'        => __( 'Membres', 'amapress' ),
				'name'         => __( 'Reply to', 'amapress' ),
				'type'         => 'select',
				'desc'         => __( 'Choisir à qui répondent les destinataires de la liste', 'amapress' ),
				'readonly'     => 'amapress_get_mailinglist_reply_to_readonly',
				'options'      => 'amapress_get_mailinglist_reply_to_options',
				'custom_get'   => 'amapress_get_mailinglist_reply_to',
				'custom_save'  => 'amapress_set_mailinglist_reply_to',
				'show_on'      => 'edit-only',
				'after_option' => function ( TitanFrameworkOption $option ) {
					if ( amapress_get_mailinglist_reply_to_readonly( $option ) ) {
						$ml = new Amapress_MailingListConfiguration( $option->getPostID() );

						echo '<p style="color:red;font-weight: bold">' . __( 'La gestion du reply-to se fait manuellement dans ', 'amapress' ) .
						     Amapress::makeLink( $ml->getMailingList()->getConfigurationLink(),
							     __( 'Configuration de la mailinglist', 'amapress' ), true, true )
						     . '</p>';
					}
				},
			),
			'status'                 => array(
				'group'   => __( 'Nom', 'amapress' ),
				'name'    => __( 'Statut', 'amapress' ),
				'type'    => 'custom',
				'desc'    => __( 'Statut', 'amapress' ),
				'custom'  => 'amapress_get_mailinglist_status',
				'column'  => 'amapress_get_mailinglist_status',
				'show_on' => 'edit-only',
			),
			'excl_queries'           => array(
				'group'       => __( 'Membres exclus', 'amapress' ),
				'name'        => __( 'Groupes exclus', 'amapress' ),
				'type'        => 'multicheck',
				'desc'        => __( 'Cocher le ou les groupes à exclure.', 'amapress' ),
				'options'     => 'amapress_get_mailinglist_queries',
				'show_column' => false,
			),
			'excl_other_users'       => array(
				'group'        => __( 'Membres exclus', 'amapress' ),
				'name'         => __( 'Amapiens exclus', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Sélectionner un ou plusieurs amapien(s) à exclure.', 'amapress' ),
				'show_column'  => false,
			),
		),
		'help_new'         => array(),
		'help_edit'        => array(),
		'help_view'        => array(),
		'help_profile'     => array(),
//        'views' => array(
//            '_dyn_all_' => 'amapress_user_views',
//            'exp_csv' => true,
//        ),
	);

	return $entities;
}

add_filter( 'tf_select_users_title', 'amapress_mailinglist_user_mails', 10, 3 );
function amapress_mailinglist_user_mails( $display_name, WP_User $user, TitanFrameworkOptionSelectUsers $option ) {
	if ( isset( $option->owner->settings['post_type'] ) && in_array( Amapress_MailingListConfiguration::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] ) ) {
		$amapien = AmapressUser::getBy( $user->ID );

		return sprintf( __( '%s (%s)', 'amapress' ), $user->display_name, implode( ',', $amapien->getAllEmails() ) );
	}

	return $display_name;
	//if ($id == '')
}

function amapress_user_queries_link_wrap( $queries ) {
	foreach ( $queries as $k => $v ) {
		$count = get_users_count( $k );;
		$queries[ $k ] = $v . ' (' . Amapress::makeLink(
				admin_url( 'users.php?' . $k ),
				sprintf( _n( '%s membre', '%s membres', $count, 'amapress' ), number_format_i18n( $count ) )
				, true, true ) . ')';
	}

	return $queries;
}

function amapress_get_mailinglist_queries() {
	$ret = array();

	$lieux = array_filter( Amapress::get_lieux(),
		function ( $lieu ) {
			/** @var AmapressLieu_distribution $lieu */
			return $lieu->isPrincipal();
		} );

	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}"] = sprintf( __( "Amapiens de %s", 'amapress' ), $lieu->getTitle() );
			foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
				$ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}"] = sprintf( __( "Amapiens  de %s - Contrat %s", 'amapress' ), $lieu->getTitle(), $contrat->getTitle() );
			}
		}
	}


	$ret['all=T']                                    = __( 'Tous les utilisateurs enregistrés', 'amapress' );
	$ret["amapress_role=never_logged"]               = __( "Amapiens jamais connectés", 'amapress' );
	$ret["amapress_adhesion=ok"]                     = __( "Amapiens avec adhésion", 'amapress' );
	$ret["amapress_adhesion=ok_co"]                  = __( "Amapiens avec adhésion (et co-adhérents/membres du foyer)", 'amapress' );
	$ret["amapress_adhesion=nok"]                    = __( "Amapiens avec adhésion non réglée", 'amapress' );
	$ret["amapress_adhesion=none"]                   = __( "Amapiens sans adhésion", 'amapress' );
	$ret["amapress_contrat=no&amapress_adhesion=ok"] = __( "Amapiens avec adhésion sans contrat", 'amapress' );
	$ret["amapress_contrat=no"]                      = __( "Amapiens sans contrat", 'amapress' );
	$ret["amapress_contrat=active"]                  = __( "Amapiens avec contrat", 'amapress' );
	$ret["amapress_contrat=coadherent"]              = __( "Co-adhérents", 'amapress' );
	$ret["amapress_contrat=with_coadherent"]         = __( "Amapiens avec co-adhérents", 'amapress' );


	$sub_names = [];
	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
		$ret["amapress_contrat={$contrat->ID}"] = sprintf( __( "Amapiens - Contrat %s", 'amapress' ), $contrat->getTitle() );
		$sub_contrats                           = AmapressContrats::get_active_contrat_instances_by_contrat( $contrat->ID );
		foreach ( $sub_contrats as $contrat_instance ) {
			if ( ! empty( $contrat_instance->getSubName() ) ) {
				$sub_names[] = trim( $contrat_instance->getSubName() );
				if ( count( $sub_contrats ) > 1 ) {
					$ret[ "amapress_contrat={$contrat->ID}&amapress_subcontrat=" .
					      urlencode( $contrat_instance->getSubName() ) ] = sprintf( __( "Amapiens - Contrat %s - %s", 'amapress' ), $contrat->getTitle(), $contrat_instance->getSubName() );
				}
			}
		}
	}
	if ( ! empty( $sub_names ) ) {
		$sub_names = array_unique( $sub_names );
		foreach ( $sub_names as $sub_name ) {
			$ret[ 'amapress_contrat=active&amapress_subcontrat=' . urlencode( $sub_name ) ] = sprintf( __( 'Amapiens avec contrats - %s', 'amapress' ), $sub_name );
		}
	}

//	$ret["amapress_role=referent_producteur"] = "Référents producteurs";
//	if ( count( $lieux ) > 1 ) {
//		foreach ( $lieux as $lieu ) {
//			$ret["amapress_lieu={$lieu->ID}&amapress_role=referent_producteur"] = "Référents producteurs de {$lieu->getTitle()}";
//		}
//	}
//	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
//		$ret["amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = "Référents producteurs - {$contrat->getTitle()}";
//	}

	$ret["amapress_role=collectif_no_prod"]                         = __( "Membres du collectif (sans les producteurs)", 'amapress' );
	$ret["amapress_role=collectif"]                                 = __( 'Membres du collectif (avec les producteurs)', 'amapress' );
	$ret["amapress_role=collectif_no_prod&amapress_contrat=active"] = __( "Membres du collectif avec contrat(s) (sans les producteurs)", 'amapress' );
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif_no_prod"]                         = sprintf( __( "Membres du collectif (sans les producteurs) de %s", 'amapress' ), $lieu->getTitle() );
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif"]                                 = sprintf( __( "Membres du collectif (avec les producteurs) de %s", 'amapress' ), $lieu->getTitle() );
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif_no_prod&amapress_contrat=active"] = sprintf( __( "Membres du collectif avec contrat(s) (sans les producteurs) de %s", 'amapress' ), $lieu->getTitle() );
		}
	}
//    foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//        $ret["amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP - {$contrat->getModelTitle()}";
//    }

	$ret["amapress_contrat=intermittent"]     = __( "Intermittents", 'amapress' );
	$ret["amapress_role=referent_lieu"]       = __( "Référents lieux", 'amapress' );
	$ret["amapress_role=referent_producteur"] = __( "Référents Producteurs", 'amapress' );
	$ret["amapress_role=resp_distrib"]        = __( 'Prochains responsables de distributions', 'amapress' );
	$ret["role=producteur"]                   = __( "Producteurs", 'amapress' );
	$ret["role=administrator"]                = __( "Administrateurs Amap", 'amapress' );
	$ret["role=responsable_amap"]             = __( "Responsables Amap", 'amapress' );
	$ret["role=tresorier"]                    = __( "Trésoriers Amap", 'amapress' );
	$ret["role=redacteur_amap"]               = __( "Rédacteurs Amap", 'amapress' );
	$ret["role=coordinateur_amap"]            = __( "Coordinateurs Amap", 'amapress' );

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAP_ROLE,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ 'amps_amap_role_category=' . $role->slug ] = sprintf( __( 'Membres du collectif avec rôle "%s"', 'amapress' ), $role->name );
	}

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAPIEN_GROUP,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ AmapressUser::AMAPIEN_GROUP . '=' . $role->slug ] = sprintf( __( 'Groupe amapiens "%s"', 'amapress' ), $role->name );
	}

	return amapress_user_queries_link_wrap( $ret );
}

function amapress_mailinglist_should_moderators_readonly( TitanFrameworkOption $option ) {
	$ml = new Amapress_MailingListConfiguration( $option->getPostID() );
	if ( null == $ml->getMailingList() ) {
		return true;
	}

	return ! $ml->getMailingList()->handleModerators();
}

function amapress_mailinglist_should_moderation_readonly( TitanFrameworkOption $option ) {
	$ml = new Amapress_MailingListConfiguration( $option->getPostID() );
	if ( null == $ml->getMailingList() ) {
		return true;
	}

	return ! $ml->getMailingList()->handleModerationSetting();
}

function amapress_has_mailinglist_moderators_queries( TitanFrameworkOption $option ) {
	$ml = new Amapress_MailingListConfiguration( $option->getPostID() );
	$qs = $ml->getModeratorsQueries();

	return ! empty( $qs );
}

function amapress_get_mailinglist_moderators_queries() {
	$ret = array();

	$lieux = array_filter( Amapress::get_lieux(),
		function ( $lieu ) {
			/** @var AmapressLieu_distribution $lieu */
			return $lieu->isPrincipal();
		} );

	$ret["amapress_role=referent_producteur"] = __( "Référents producteurs", 'amapress' );
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=referent_producteur"] = sprintf( __( "Référents producteurs de %s", 'amapress' ), $lieu->getTitle() );
			foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
				$ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = sprintf( __( "Référents producteurs  de %s - %s", 'amapress' ), $lieu->getTitle(), $contrat->getTitle() );
			}
		}
	}
	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
		$ret["amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = sprintf( __( "Référents producteurs - %s", 'amapress' ), $contrat->getTitle() );
	}

	$ret["amapress_role=collectif"] = __( 'Membres du collectif (avec les producteurs)', 'amapress' );
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif"] = sprintf( __( "Membres du collectif (avec les producteurs) de %s", 'amapress' ), $lieu->getTitle() );
		}
	}
//    foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//        $ret["amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP - {$contrat->getModelTitle()}";
//    }

	$ret["amapress_role=referent_lieu"]     = __( "Référents lieux", 'amapress' );
	$ret["amapress_role=amap_role_any"]     = __( "Amapiens avec rôles", 'amapress' );
	$ret["amapress_role=amapien_group_any"] = __( "Amapiens avec groupes", 'amapress' );
	$ret["role=administrator"]              = __( "Administrateurs", 'amapress' );

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAP_ROLE,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ 'amps_amap_role_category=' . $role->slug ] = sprintf( __( 'Membres du collectif avec rôle "%s"', 'amapress' ), $role->name );
	}

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAPIEN_GROUP,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ AmapressUser::AMAPIEN_GROUP . '=' . $role->slug ] = sprintf( __( 'Groupe amapiens "%s"', 'amapress' ), $role->name );
	}

//    $ret["amapress_role=resp_distrib"] = "Prochains responsables de distributions";

	return amapress_user_queries_link_wrap( $ret );
}

function amapress_get_mailinglists( TitanFrameworkOption $option ) {
	//todo filter used
	$res = array();
	foreach ( Amapress_MailingSystems::getMailingLists() as $m ) {
		$res[ $m->getId() ] = $m->getFullName();
	}

	return $res;
}

function amapress_get_mailinglist_members_count( $mailing_list_id ) {
	$ml = Amapress_MailingListConfiguration::getBy( $mailing_list_id );
//	$ml_obj = $ml->getMailingList();
//	if ( ! $ml_obj ) {
//		return '';
//	}
//
//	return "<a target='_blank' href='{$ml_obj->getMembersLink()}'>{$ml_obj->getMembersCount()}</a>";
	return Amapress::makeLink(
		admin_url( 'users.php?amapress_mllst_id=' . $mailing_list_id ),
		strval( count( $ml->getMembersIds() ) ),
		true, true );
}

function amapress_get_mailinglist_moderation_column( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	return $ml_obj->getModerationModeName();
}

function amapress_get_mailinglist_moderation_options( TitanFrameworkOption $option ) {
	$mailing_list_id = $option->getPostID();
	$ml              = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj          = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return array();
	}

	return $ml_obj->getModerationModes();
}

function amapress_get_mailinglist_moderation( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	return $ml_obj->getModerationMode();
}

function amapress_set_mailinglist_moderation( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	$ml_obj->setModerationMode( $_POST['amapress_mailinglist_moderation'] );
}

function amapress_get_mailinglist_moderators( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	return $ml_obj->getModeratorsIds();
}

function amapress_set_mailinglist_moderators( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	if ( ! empty( $_POST['amapress_mailinglist_moderators'] ) ) {
		$ml_obj->setModerators( $_POST['amapress_mailinglist_moderators'] );
	}
}

function amapress_get_mailinglist_bounce_rate( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	return '<a href="' . esc_attr( $ml_obj->getBouncesLink() ) . '">' . esc_html( $ml_obj->getBounceRate() ) . '</a>';
}

function amapress_get_mailinglist_reply_to( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	return $ml_obj->getReplyTo();
}

function amapress_get_mailinglist_reply_to_readonly( TitanFrameworkOption $option ) {
	return empty( amapress_get_mailinglist_reply_to_options( $option ) );
}

function amapress_get_mailinglist_reply_to_options( TitanFrameworkOption $option ) {
	$mailing_list_id = $option->getPostID();
	$ml              = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj          = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return array();
	}

	return $ml_obj->getReplyToOptions();
}

function amapress_set_mailinglist_reply_to( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	if ( ! empty( $_POST['amapress_mailinglist_reply_to'] ) ) {
		$ml_obj->setReplyTo( $_POST['amapress_mailinglist_reply_to'] );
	}
}

function amapress_get_mailinglist_waiting( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	return $ml_obj->getMailWaitingModerationCount();
}

function amapress_get_mailinglist_waiting_list( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	if ( ! $ml_obj->getSystem()->handleMessagesModeration() ) {
		$link = $ml_obj->getModerationLink();
		if ( ! empty( $link ) ) {
			return "<a href='$link' target='_blank'>" . __( 'Modérer les emails en attente', 'amapress' ) . "</a>";
		} else {
			return '<p>' . __( 'La modération des emails n\'est pas gérée pour ce système de listes de diffusion', 'amapress' ) . '</p>';
		}
	} else {
		$columns = array(
			array(
				'title' => __( 'Date', 'amapress' ),
				'data'  => array(
					'_'    => 'date',
					'sort' => 'date',
				)
			),
			array(
				'title' => __( 'De', 'amapress' ),
				'data'  => array(
					'_'    => 'from',
					'sort' => 'from',
				)
			),
			array(
				'title' => __( 'Subject', 'amapress' ),
				'data'  => array(
					'_'    => 'subject',
					'sort' => 'subject',
				)
			),
			array(
				'title' => __( 'Contenu', 'amapress' ),
				'data'  => array(
					'_'    => 'content',
					'sort' => 'content',
				)
			),
			array(
				'title' => '',
				'data'  => 'distribute'
			),
			array(
				'title' => '',
				'data'  => 'reject'
			),
			array(
				'title' => '',
				'data'  => 'reject_quiet'
			),
		);

		$data = array();
		foreach ( $ml_obj->getMailWaitingModeration() as $m ) {
			$data[] = array(
				'from'         => $m->getHeader( 'from' ),
				'date'         => $m->getHeader( 'date' ),
				'subject'      => $m->getHeader( 'subject' ),
				'content'      => $m->getContent(),
				'distribute'   => amapress_get_mail_action_form( __( 'Distribuer', 'amapress' ), 'amapress_mail_distribute', $ml->ID, $m->getId() ),
				'reject_quiet' => amapress_get_mail_action_form( __( 'Rejetter sans prévenir', 'amapress' ), 'amapress_mail_reject_quiet', $ml->ID, $m->getId() ),
				'reject'       => amapress_get_mail_action_form( __( 'Rejetter', 'amapress' ), 'amapress_mail_reject', $ml->ID, $m->getId() ),
			);
		}

		return amapress_get_datatable( 'waiting-mails', $columns, $data );
	}
}

function amapress_get_mail_action_form( $button_text, $action, $list_id, $msg_id ) {
	$href = add_query_arg(
		array(
			'action'  => $action,
			'list_id' => $list_id,
			'msg_id'  => $msg_id,
		),
		admin_url( 'admin.php' )
	);

	return '<a href="' . esc_attr( $href ) . '">' . esc_html( $button_text ) . '</a>';
//    return '<form method="POST" action="'.  . '">
//    <input type="hidden" id="'.$list_id.$msg_id.$action.'-action" class="ignore" name="action" value="'.$action.'" />
//    <input type="hidden" id="'.$list_id.$msg_id.$action.'-list_id" class="ignore" name="list_id" value="'.$list_id.'" />
//    <input type="hidden" id="'.$list_id.$msg_id.$action.'-msg_id" class="ignore" name="msg_id" value="'.$msg_id.'" />
//    <input type="submit" id="'.$list_id.$msg_id.$action.'-submit" class="ignore" value="'.$button_text.'" />
//</form>';
}

add_action( 'admin_action_amapress_mail_reject', 'admin_action_amapress_mail_reject' );
function admin_action_amapress_mail_reject() {
	$mailing_list_id = $_REQUEST['list_id'];
	$msg_id          = $_REQUEST['msg_id'];

	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( $ml_obj ) {
		$ml_obj->rejectMail( $msg_id );
	}

	wp_redirect( $_SERVER['HTTP_REFERER'] );
	exit();
}

add_action( 'admin_action_amapress_mail_reject_quiet', 'admin_action_amapress_mail_reject_quiet' );
function admin_action_amapress_mail_reject_quiet() {
	$mailing_list_id = $_REQUEST['list_id'];
	$msg_id          = $_REQUEST['msg_id'];

	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( $ml_obj ) {
		$ml_obj->rejectMailQuiet( $msg_id );
	}

	wp_redirect( $_SERVER['HTTP_REFERER'] );
	exit();
}

add_action( 'admin_action_amapress_mail_distribute', 'admin_action_amapress_mail_distribute' );
function admin_action_amapress_mail_distribute() {
	$mailing_list_id = $_REQUEST['list_id'];
	$msg_id          = $_REQUEST['msg_id'];

	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( $ml_obj ) {
		$ml_obj->distributeMail( $msg_id );
	}

	wp_redirect( $_SERVER['HTTP_REFERER'] );
	exit();
}

add_action( 'admin_action_amapress_mailing_sync', 'admin_action_amapress_mailing_sync' );
function admin_action_amapress_mailing_sync() {
	$mailing_list_id = $_REQUEST['list_id'];
//    $msg_id = $_POST['msg_id'];

	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( $ml_obj ) {
		$ml_obj->syncMembers( $ml );
	}

	wp_redirect( $_SERVER['HTTP_REFERER'] );
	exit();
}

function amapress_get_mailinglist_status( $mailing_list_id ) {
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '<strong style="color:red">Mailing liste introuvable. Veuillez vérifier la configuration.</strong>';
	}

	$ret = '';
	switch ( $ml_obj->isSync( $ml ) ) {
		case 'manual':
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: gray;">' . __( 'Synchro manuelle', 'amapress' ) . '</div>' .
			        amapress_get_mail_action_form( __( 'Configurer et synchroniser', 'amapress' ), 'amapress_mailing_sync', $ml->ID, '' ) . '</div>';
			break;
		case 'sync':
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: green;">OK</div></div>';
			break;
		case 'not_sync':
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: orange;">NOK</div>' .
			        amapress_get_mail_action_form( __( 'Synchroniser', 'amapress' ), 'amapress_mailing_sync', $ml->ID, '' ) . '</div>';
			break;
		default:
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: orange;">' . __( 'Inconnu', 'amapress' ) . '</div></div>';
			break;
	}
	$ret .= '<div class="mailing-conf-link"><a href="' . $ml_obj->getConfigurationLink() . '">' . __( 'Voir la configuration complète', 'amapress' ) . '</a></div>';

	return $ret;
//    $res = false;
//    $tt = self::get_contrat_status($contrat_id, $res);
//    if ($res) {
//        return '<div class="status"><div class="contrat-status"><button class="contrat-status-button" title="' . esc_attr($tt) . '" data-contrat-instance="' . $contrat_id . '">Mettre à jour distributions et paniers</button><div></div>';
//    } else
//        return '<div class="status"><div class="contrat-status" style="color: green;">OK</div></div>';
}

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'amapress_mailinglists_autosync' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'amapress_mailinglists_autosync' );
	}
} );

add_action( 'tf_custom_admin_amapress_action_test_mailinglist_access', function () {
	$systems = Amapress_MailingSystems::getSystems();
	if ( empty( $systems ) ) {
		echo '<p style="color: orange">Pas de système de liste de diffusion configuré !</p>';
		die();
	} else {
		$status    = [];
		$connected = true;
		foreach ( $systems as $system ) {
			$status[] = sprintf( __( '%s(%s): %s', 'amapress' ),
				$system->getSystemName(),
				$system->getSystemId(),
				$system->isConnected() ? 'OK' : $system->getErrorMessage() );
			if ( ! $system->isConnected() ) {
				$connected = false;
			}
		}
		echo '<p style="color:' . ( $connected ? 'green' : 'red' ) . '">' . implode( '<br/>', $status ) . '</p>';
		die();
	}
} );

function amapress_mailinglists_autosync( $force = false ) {
	$messages = array();
	foreach ( Amapress_MailingListConfiguration::getAll() as $conf ) {
		$ml = $conf->getMailingList();
		if ( null == $ml ) {
			$messages[] = sprintf( __( 'La synchro de %s n\'est possible. Cette liste est introuvable. Veuillez vérifier la configuration de la liste ou du fournisseur. Voir %s', 'amapress' ), $conf->getTitle(), $conf->getAdminEditLink() );
			continue;
		}
		try {
			if ( $force ) {
				$sync = 'not_sync';
			} else {
				$sync = $ml->isSync( $conf );
			}
			switch ( $sync ) {
				case 'not_sync':
					$ml->syncMembers( $conf );
					if ( 'sync' != $ml->isSync( $conf ) ) {
						$messages[] = sprintf( __( 'La synchro de %s a échouée. Voir %s', 'amapress' ), $conf->getTitle(), $conf->getAdminEditLink() );
					}
					break;
				case 'manual':
					$messages[] = sprintf( __( 'La synchro de %s doit être faite manuellement (ou n\'est pas configurée). Voir %s', 'amapress' ), $conf->getTitle(), $conf->getAdminEditLink() );
					break;
			}
		} catch ( Exception $ex ) {
			$messages[] = sprintf( __( 'La synchro de %s a échouée : %s. Voir %s', 'amapress' ),
				$conf->getTitle(), $ex->getMessage(), $conf->getAdminEditLink() );
		}
	}
	if ( ! empty( $messages ) ) {
		$all_sync_link = Amapress::makeLink( admin_url( 'edit.php?post_type=amps_mailing&sync_all' ) );
		amapress_mail_to_admin( __( 'Synchronisation des listes de diffusions', 'amapress' ),
			"Les listes suivantes n'ont pas pu être synchronisées:\r\n" .
			implode( "\r\n", $messages ) .
			"\r\nPour lancer la synchronisation de toutes les listes manuellement, cliquer sur le lien suivant : {$all_sync_link}" );
	}
}

add_action( 'amapress_mailinglists_autosync', 'amapress_mailinglists_autosync' );