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
			'add_new'      => 'Configurer une liste de diffusion existante',
			'add_new_item' => 'Configurer une liste de diffusion existante',
		),
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'views'            => array(
			'_dyn_' => function () {
				$ret = array();
				amapress_add_view_button(
					$ret, 'sync_all',
					"post_type=amps_mailing&sync_all",
					'Tout synchroniser' );

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
				'group'    => 'Nom',
				'name'     => __( 'Nom', 'amapress' ),
				'type'     => 'select',
				'desc'     => 'Choisir la liste de diffusion à configurer.',
				'options'  => 'amapress_get_mailinglists',
				'required' => true,
			),
			'desc'                   => array(
				'group' => 'Description',
				'name'  => __( 'Description', 'amapress' ),
				'type'  => 'text',
			),
			'moderation'             => array(
				'group'       => 'Modération',
				'name'        => __( 'Modération', 'amapress' ),
				'type'        => 'select',
				'cache'       => false,
				'desc'        => 'Choisir le type de modération – option proposée par votre gestionnaire de liste (Sympa, Mailchimp, …).',
				'options'     => 'amapress_get_mailinglist_moderation_options',
				'column'      => 'amapress_get_mailinglist_moderation_column',
				'custom_get'  => 'amapress_get_mailinglist_moderation',
				'custom_save' => 'amapress_set_mailinglist_moderation',
				'show_on'     => 'edit-only',
			),
			'moderators'             => array(
				'group'        => 'Modérateurs',
				'name'         => __( 'Modérateurs', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Choisir un ou plusieurs modérateurs en charge de valider les mails avant diffusion.',
				'readonly'     => function ( TitanFrameworkOption $option ) {
					return amapress_has_mailinglist_moderators_queries( $option )
					       || amapress_mailinglist_should_moderators_readonly( $option );
				},
				'after_option' => function ( TitanFrameworkOption $option ) {
					if ( amapress_mailinglist_should_moderators_readonly( $option ) ) {
						$ml = new Amapress_MailingListConfiguration( $option->getPostID() );

						echo '<p style="color:red;font-weight: bold">La gestion des modérateurs se fait manuellement dans ' .
						     Amapress::makeLink( $ml->getMailingList()->getModeratorsLink(),
							     'Gestion des modérateurs', true, true )
						     . '</p>';
					}
				},
				'custom_get'   => 'amapress_get_mailinglist_moderators',
				'custom_save'  => 'amapress_set_mailinglist_moderators',
				'show_on'      => 'edit-only',
//                'show_column' => false,
			),
			'moderators_queries'     => array(
				'group'    => 'Modérateurs',
				'name'     => __( 'Groupes inclus dans les modérateurs', 'amapress' ),
				'type'     => 'multicheck',
				'desc'     => 'Cocher le ou les groupes à intégrer.',
				'options'  => 'amapress_get_mailinglist_moderators_queries',
				'readonly' => 'amapress_mailinglist_should_moderators_readonly',
//                'required' => true,
			),
			'moderators_other_users' => array(
				'group'        => 'Modérateurs',
				'name'         => __( 'Modérateurs hors groupe', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'readonly'     => 'amapress_mailinglist_should_moderators_readonly',
//                'desc' => 'Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.',
			),
			'waiting'                => array(
				'group'   => 'Modération',
				'name'    => __( 'Emails en attente modération', 'amapress' ),
				'type'    => 'custom',
				'desc'    => 'Liste du ou des emails à valider.',
				'column'  => 'amapress_get_mailinglist_waiting',
				'custom'  => 'amapress_get_mailinglist_waiting_list',
				'show_on' => 'edit-only',
			),
			'members_count'          => array(
				'group'   => 'Membres',
				'name'    => __( 'Membres', 'amapress' ),
				'type'    => 'custom',
				'desc'    => 'Liste des membres',
				'column'  => 'amapress_get_mailinglist_members_count',
				'custom'  => 'amapress_get_mailinglist_members_count',
				'show_on' => 'edit-only',
			),
			'queries'                => array(
				'group'    => 'Membres',
				'name'     => __( 'Groupes inclus', 'amapress' ),
				'type'     => 'multicheck',
				'desc'     => 'Cocher le ou les groupes à intégrer.',
				'options'  => 'amapress_get_mailinglist_queries',
				'required' => true,
			),
			'other_users'            => array(
				'group'        => 'Membres',
				'name'         => __( 'Amapiens hors groupe', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.',
			),
			'bounces'                => array(
				'group'   => 'Membres',
				'name'    => __( 'Taux d\'erreur', 'amapress' ),
				'type'    => 'custom',
				'desc'    => 'Taux d\erreur lors de l\'envoi d\{emails par la liste',
				'custom'  => 'amapress_get_mailinglist_bounce_rate',
				'column'  => 'amapress_get_mailinglist_bounce_rate',
				'show_on' => 'edit-only',
			),
			'reply_to'               => array(
				'group'       => 'Membres',
				'name'        => __( 'Reply to', 'amapress' ),
				'type'        => 'select',
				'desc'        => 'Choisir à qui répondent les destinataires de la liste',
				'options'     => 'amapress_get_mailinglist_reply_to_options',
				'custom_get'  => 'amapress_get_mailinglist_reply_to',
				'custom_save' => 'amapress_set_mailinglist_reply_to',
				'show_on'     => 'edit-only',
			),
			'status'                 => array(
				'group'   => 'Nom',
				'name'    => __( 'Statut', 'amapress' ),
				'type'    => 'custom',
				'desc'    => 'Statut',
				'custom'  => 'amapress_get_mailinglist_status',
				'column'  => 'amapress_get_mailinglist_status',
				'show_on' => 'edit-only',
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

		return sprintf( '%s (%s)', $user->display_name, implode( ',', $amapien->getAllEmails() ) );
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
			$ret["amapress_lieu={$lieu->ID}"] = "Amapiens de {$lieu->getTitle()}";
			foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
				$ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}"] = "Amapiens  de {$lieu->getTitle()} - Contrat {$contrat->getTitle()}";
			}
		}
	}


	$ret['all=T']                                    = 'Tous les utilisateurs enregistrés';
	$ret["amapress_role=never_logged"]               = "Amapiens jamais connectés";
	$ret["amapress_adhesion=ok"]                     = "Amapiens avec adhésion";
	$ret["amapress_adhesion=nok"]                    = "Amapiens sans adhésion";
	$ret["amapress_contrat=no&amapress_adhesion=ok"] = "Amapiens avec adhésion sans contrat";
	$ret["amapress_contrat=no"]                      = "Amapiens sans contrat";
	$ret["amapress_contrat=active"]                  = "Amapiens avec contrat";
	$ret["amapress_contrat=coadherent"]              = "Co-adhérents";
	$ret["amapress_contrat=with_coadherent"]         = "Amapiens avec co-adhérents";


	$sub_names = [];
	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
		$ret["amapress_contrat={$contrat->ID}"] = "Amapiens - Contrat {$contrat->getTitle()}";
		$sub_contrats                           = AmapressContrats::get_active_contrat_instances_by_contrat( $contrat->ID );
		foreach ( $sub_contrats as $contrat_instance ) {
			if ( ! empty( $contrat_instance->getSubName() ) ) {
				$sub_names[] = trim( $contrat_instance->getSubName() );
				if ( count( $sub_contrats ) > 1 ) {
					$ret[ "amapress_contrat={$contrat->ID}&amapress_subcontrat=" .
					      urlencode( $contrat_instance->getSubName() ) ] = "Amapiens - Contrat {$contrat->getTitle()} - {$contrat_instance->getSubName()}";
				}
			}
		}
	}
	if ( ! empty( $sub_names ) ) {
		$sub_names = array_unique( $sub_names );
		foreach ( $sub_names as $sub_name ) {
			$ret[ 'amapress_contrat=active&amapress_subcontrat=' . urlencode( $sub_name ) ] = "Amapiens avec contrats - {$sub_name}";
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

	$ret["amapress_role=collectif_no_prod"]                         = "Membres du collectif (sans les producteurs)";
	$ret["amapress_role=collectif"]                                 = 'Membres du collectif (avec les producteurs)';
	$ret["amapress_role=collectif_no_prod&amapress_contrat=active"] = "Membres du collectif avec contrat(s) (sans les producteurs)";
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif_no_prod"]                         = "Membres du collectif (sans les producteurs) de {$lieu->getTitle()}";
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif"]                                 = "Membres du collectif (avec les producteurs) de {$lieu->getTitle()}";
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif_no_prod&amapress_contrat=active"] = "Membres du collectif avec contrat(s) (sans les producteurs) de {$lieu->getTitle()}";
		}
	}
//    foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//        $ret["amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP - {$contrat->getModelTitle()}";
//    }

	$ret["amapress_contrat=intermittent"]     = "Intermittents";
	$ret["amapress_role=referent_lieu"]       = "Référents lieux";
	$ret["amapress_role=referent_producteur"] = "Référents Producteurs";
	$ret["amapress_role=resp_distrib"]        = 'Prochains responsables de distributions';
	$ret["role=producteur"]                   = "Producteurs";
	$ret["role=administrator"]                = "Administrateurs Amap";
	$ret["role=responsable_amap"]             = "Responsables Amap";
	$ret["role=tresorier"]                    = "Trésoriers Amap";
	$ret["role=redacteur_amap"]               = "Rédacteurs Amap";
	$ret["role=coordinateur_amap"]            = "Coordinateurs Amap";

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAP_ROLE,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ 'amps_amap_role_category=' . $role->slug ] = 'Membres du collectif avec rôle "' . $role->name . '"';
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
		$ret[ AmapressUser::AMAPIEN_GROUP . '=' . $role->slug ] = 'Groupe amapiens "' . $role->name . '"';
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

	$ret["amapress_role=referent_producteur"] = "Référents producteurs";
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=referent_producteur"] = "Référents producteurs de {$lieu->getTitle()}";
			foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
				$ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = "Référents producteurs  de {$lieu->getTitle()} - {$contrat->getTitle()}";
			}
		}
	}
	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
		$ret["amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = "Référents producteurs - {$contrat->getTitle()}";
	}

	$ret["amapress_role=collectif"] = 'Membres du collectif (avec les producteurs)';
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=collectif"] = "Membres du collectif (avec les producteurs) de {$lieu->getTitle()}";
		}
	}
//    foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//        $ret["amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP - {$contrat->getModelTitle()}";
//    }

	$ret["amapress_role=referent_lieu"]     = "Référents lieux";
	$ret["amapress_role=amap_role_any"]     = "Amapiens avec rôles";
	$ret["amapress_role=amapien_group_any"] = "Amapiens avec groupes";
	$ret["role=administrator"]              = "Administrateurs";

	foreach (
		get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => AmapressUser::AMAP_ROLE,
			'hide_empty' => false,
		) ) as $role
	) {
		/** @var WP_Term $role */
		$ret[ 'amps_amap_role_category=' . $role->slug ] = 'Membres du collectif avec rôle "' . $role->name . '"';
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
		$ret[ AmapressUser::AMAPIEN_GROUP . '=' . $role->slug ] = 'Groupe amapiens "' . $role->name . '"';
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
	return Amapress::makeLink( admin_url( 'users.php?amapress_mllst_id=' . $mailing_list_id ), count( $ml->getMembersIds() ), true, true );
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
			return "<a href='$link' target='_blank'>Modérer les emails en attente</a>";
		} else {
			return '<p>La modération des emails n\'est pas gérée pour ce système de listes de diffusion</p>';
		}
	} else {
		$columns = array(
			array(
				'title' => 'Date',
				'data'  => array(
					'_'    => 'date',
					'sort' => 'date',
				)
			),
			array(
				'title' => 'De',
				'data'  => array(
					'_'    => 'from',
					'sort' => 'from',
				)
			),
			array(
				'title' => 'Subject',
				'data'  => array(
					'_'    => 'subject',
					'sort' => 'subject',
				)
			),
			array(
				'title' => 'Contenu',
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
				'distribute'   => amapress_get_mail_action_form( 'Distribuer', 'amapress_mail_distribute', $ml->ID, $m->getId() ),
				'reject_quiet' => amapress_get_mail_action_form( 'Rejetter sans prévenir', 'amapress_mail_reject_quiet', $ml->ID, $m->getId() ),
				'reject'       => amapress_get_mail_action_form( 'Rejetter', 'amapress_mail_reject', $ml->ID, $m->getId() ),
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
		return '';
	}

	$ret = '';
	switch ( $ml_obj->isSync( $ml ) ) {
		case 'manual':
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: gray;">Synchro manuelle</div>' .
			        amapress_get_mail_action_form( 'Configurer et synchroniser', 'amapress_mailing_sync', $ml->ID, '' ) . '</div>';
			break;
		case 'sync':
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: green;">OK</div></div>';
			break;
		case 'not_sync':
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: orange;">NOK</div>' .
			        amapress_get_mail_action_form( 'Synchroniser', 'amapress_mailing_sync', $ml->ID, '' ) . '</div>';
			break;
		default:
			$ret .= '<div class="status"><div class="mailinglist-status" style="color: orange;">Inconnu</div></div>';
			break;
	}
	$ret .= '<div class="mailing-conf-link"><a href="' . $ml_obj->getConfigurationLink() . '">Voir la configuration complète</a></div>';

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
			$status[] = sprintf( '%s(%s): %s',
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
		if ( $force ) {
			$sync = 'not_sync';
		} else {
			$sync = $ml->isSync( $conf );
		}
		switch ( $sync ) {
			case 'not_sync':
				$ml->syncMembers( $conf );
				if ( 'sync' != $ml->isSync( $conf ) ) {
					$messages[] = "La synchro de {$conf->getTitle()} a échouée. Voir {$conf->getAdminEditLink()}";
				}
				break;
			case 'manual':
				$messages[] = "La synchro de {$conf->getTitle()} doit être faite manuellement (ou n'est pas configurée). Voir {$conf->getAdminEditLink()}";
				break;
		}
	}
	if ( ! empty( $messages ) ) {
		$all_sync_link = Amapress::makeLink( admin_url( 'edit.php?post_type=amps_mailing&sync_all' ) );
		amapress_mail_to_admin( 'Synchronisation des listes de diffusions',
			"Les listes suivantes n'ont pas pu être synchronisées:\r\n" .
			implode( "\r\n", $messages ) .
			"\r\nPour lancer la synchronisation de toutes les listes manuellement, cliquer sur le lien suivant : {$all_sync_link}" );
	}
}

add_action( 'amapress_mailinglists_autosync', 'amapress_mailinglists_autosync' );