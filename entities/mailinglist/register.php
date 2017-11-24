<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_mailinglist' );
function amapress_register_entities_mailinglist( $entities ) {
	$entities['mailinglist'] = array(
		'internal_name'    => 'amps_mailing',
		'singular'         => amapress__( 'Liste de diffusion' ),
		'plural'           => amapress__( 'Listes de diffusions' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'labels'           => array(
			'add_new'      => 'Configurer une liste de diffusion existante',
			'add_new_item' => 'Configurer une liste de diffusion existante',
		),
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
		'slug'             => amapress__( 'mailinglists' ),
//        'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'dashicons-email-alt',
		'fields'           => array(
			'name'                   => array(
				'group'    => 'Nom',
				'name'     => amapress__( 'Nom' ),
				'type'     => 'select',
				'desc'     => 'Choisir la liste de diffusion à configurer.',
				'options'  => 'amapress_get_mailinglists',
				'required' => true,
			),
			'moderation'             => array(
				'group'       => 'Modération',
				'name'        => amapress__( 'Modération' ),
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
				'name'         => amapress__( 'Modérateurs' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Choisir un ou plusieurs modérateurs en charge de valider les mails avant diffusion.',
				'readonly'     => 'amapress_has_mailinglist_moderators_queries',
				'custom_get'   => 'amapress_get_mailinglist_moderators',
				'custom_save'  => 'amapress_set_mailinglist_moderators',
				'show_on'      => 'edit-only',
//                'show_column' => false,
			),
			'moderators_queries'     => array(
				'group'   => 'Modérateurs',
				'name'    => amapress__( 'Groupes inclus dans les modérateurs' ),
				'type'    => 'multicheck',
				'desc'    => 'Cocher le ou les groupes à intégrer.',
				'options' => 'amapress_get_mailinglist_moderators_queries',
//                'required' => true,
			),
			'moderators_other_users' => array(
				'group'        => 'Modérateurs',
				'name'         => amapress__( 'Modérateurs hors groupe' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
//                'desc' => 'Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.',
			),
			'waiting'                => array(
				'group'   => 'Modération',
				'name'    => amapress__( 'Mails en attente modération' ),
				'type'    => 'custom',
				'desc'    => 'Liste du ou des mails à valider.',
				'column'  => 'amapress_get_mailinglist_waiting',
				'custom'  => 'amapress_get_mailinglist_waiting_list',
				'show_on' => 'edit-only',
			),
			'members_count'          => array(
				'group'   => 'Membres',
				'name'    => amapress__( 'Membres' ),
				'type'    => 'custom',
				'desc'    => 'Liste des membres',
				'column'  => 'amapress_get_mailinglist_members_count',
				'custom'  => 'amapress_get_mailinglist_members_count',
				'show_on' => 'edit-only',
			),
			'queries'                => array(
				'group'    => 'Membres',
				'name'     => amapress__( 'Groupes inclus' ),
				'type'     => 'multicheck',
				'desc'     => 'Cocher le ou les groupes à intégrer.',
				'options'  => 'amapress_get_mailinglist_queries',
				'required' => true,
			),
			'other_users'            => array(
				'group'        => 'Membres',
				'name'         => amapress__( 'Amapiens hors groupe' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.',
			),
			'bounces'                => array(
				'group'   => 'Membres',
				'name'    => amapress__( 'Taux d\'erreur' ),
				'type'    => 'custom',
				'desc'    => 'Taux d\erreur lors de l\'envoi de mails par la liste',
				'custom'  => 'amapress_get_mailinglist_bounce_rate',
				'column'  => 'amapress_get_mailinglist_bounce_rate',
				'show_on' => 'edit-only',
			),
			'reply_to'               => array(
				'group'       => 'Membres',
				'name'        => amapress__( 'Reply to' ),
				'type'        => 'select',
				'desc'        => 'Choisir à qui répondent les destinataires de la liste',
				'options'     => 'amapress_get_mailinglist_reply_to_options',
				'custom_get'  => 'amapress_get_mailinglist_reply_to',
				'custom_save' => 'amapress_set_mailinglist_reply_to',
				'show_on'     => 'edit-only',
			),
			'status'                 => array(
				'group'   => 'Nom',
				'name'    => amapress__( 'Statut' ),
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
	if ( in_array( Amapress_MailingListConfiguration::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] ) ) {
		$amapien = AmapressUser::getBy( $user->ID );

		return sprintf( '%s (%s)', $user->display_name, implode( ',', $amapien->getAllEmails() ) );
	}

	return $display_name;
	//if ($id == '')
}

function amapress_get_mailinglist_queries() {
	$ret = array();

	$lieux = Amapress::get_lieux();

	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}"] = "Amapiens de {$lieu->getTitle()}";
			foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
				$ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}"] = "Amapiens  de {$lieu->getTitle()} - Contrat {$contrat->getTitle()}";
			}
		}
	}


	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
		$ret["amapress_contrat={$contrat->ID}"] = "Amapiens - Contrat {$contrat->getTitle()}";
	}

	$ret["amapress_role=referent_producteur"] = "Référents producteurs";
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=referent_producteur"] = "Référents producteurs de {$lieu->getTitle()}";
//        foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//            $ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = "Référents producteurs  de {$lieu->getTitle()} - {$contrat->getModel()->getTitle()}";
//        }
		}
	}
	foreach ( AmapressContrats::get_contrats( null, false, false ) as $contrat ) {
		$ret["amapress_contrat={$contrat->ID}&amapress_role=referent_producteur"] = "Référents producteurs - {$contrat->getTitle()}";
	}

	$ret["amapress_role=access_admin"] = "Responsables AMAP";
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=access_admin"] = "Responsables AMAP de {$lieu->getTitle()}";
//        foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//            $ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP  de {$lieu->getTitle()} - {$contrat->getModel()->getTitle()}";
//        }
		}
	}
//    foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//        $ret["amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP - {$contrat->getModel()->getTitle()}";
//    }

	$ret["amapress_contrat=intermittent"] = "Intermittents";
	$ret["amapress_role=referent_lieu"]   = "Référents lieux";
	$ret["amapress_role=amap_role_any"]   = "Amapiens avec rôles";
	$ret["amapress_role=resp_distrib"]    = "Prochains responsables de distributions";

//    $ret["role=administrator"] = "Prochains responsables de distributions";

	return $ret;
}

function amapress_has_mailinglist_moderators_queries( TitanFrameworkOption $option ) {
	$ml = new Amapress_MailingListConfiguration( $option->getPostID() );
	$qs = $ml->getModeratorsQueries();

	return ! empty( $qs );
}

function amapress_get_mailinglist_moderators_queries() {
	$ret = array();

	$lieux = Amapress::get_lieux();

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

	$ret["amapress_role=access_admin"] = "Responsables AMAP";
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret["amapress_lieu={$lieu->ID}&amapress_role=access_admin"] = "Responsables AMAP de {$lieu->getTitle()}";
//        foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//            $ret["amapress_lieu={$lieu->ID}&amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP  de {$lieu->getTitle()} - {$contrat->getModel()->getTitle()}";
//        }
		}
	}
//    foreach (AmapressContrats::get_active_contrat_instances() as $contrat) {
//        $ret["amapress_contrat={$contrat->ID}&amapress_role=access_admin"] = "Responsables AMAP - {$contrat->getModel()->getTitle()}";
//    }

	$ret["amapress_role=referent_lieu"] = "Référents lieux";
	$ret["amapress_role=amap_role_any"] = "Amapiens avec rôles";
	$ret["role=administrator"]          = "Administrateurs";

//    $ret["amapress_role=resp_distrib"] = "Prochains responsables de distributions";

	return $ret;
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
	$ml     = new Amapress_MailingListConfiguration( $mailing_list_id );
	$ml_obj = $ml->getMailingList();
	if ( ! $ml_obj ) {
		return '';
	}

	return "<a target='_blank' href='{$ml_obj->getMembersLink()}'>{$ml_obj->getMembersCount()}</a>";
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
			return "<a href='$link' target='_blank'>Modérer les messages en attente</a>";
		} else {
			return '<p>La modération des messages n\'est pas gérée pour ce système de listes de diffusion</p>';
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

function amapress_mailinglists_autosync() {
	$messages = array();
	foreach ( Amapress_MailingListConfiguration::getAll() as $conf ) {
		$ml   = $conf->getMailingList();
		$sync = $ml->isSync( $conf );
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