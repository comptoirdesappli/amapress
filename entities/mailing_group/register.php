<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_filter( 'amapress_register_entities', 'amapress_register_entities_mailing_groups' );
function amapress_register_entities_mailing_groups( $entities ) {
	$entities['mailing_group'] = array(
		'singular'         => amapress__( 'Email groupé' ),
		'plural'           => amapress__( 'Emails groupés' ),
		'internal_name'    => 'amps_mlgrp',
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'title_format'     => 'amapress_mailing_group_title_formatter',
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'edit_header'      => function ( $post ) {
			if ( TitanFrameworkOption::isOnEditScreen() ) {
				$ml = AmapressMailingGroup::getBy( $post, true );
				if ( $ml ) {
					if ( extension_loaded( 'imap' ) ) {
						$res = $ml->testParams();
						if ( true !== $res ) {
							echo amapress_get_admin_notice( 'Erreur de configuration IMAP/POP : ' . $res, 'error', false );
						} else {
							echo amapress_get_admin_notice( 'Configuration IMAP/POP OK', 'success', false );
						}
					} else {
						echo amapress_get_admin_notice( 'Erreur de configuration : l\'extension IMAP n\'est pas installée, les emails groupés sont désactivés.', 'error', false );
					}
					if ( ! empty( $ml->getSmtpHost() ) ) {
						if ( $ml->testSMTP() ) {
							echo amapress_get_admin_notice( 'Configuration SMTP OK', 'success', false );
						} else {
							echo amapress_get_admin_notice( 'Erreur de configuration : connexion au SMTP ' . $ml->getSmtpHost() . ' impossible', 'error', false );
						}
					}
				}
			}
		},
//		'labels'           => array(
//			'add_new'      => 'Configurer une liste de diffusion existante',
//			'add_new_item' => 'Configurer une liste de diffusion existante',
//		),
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'slug'             => amapress__( 'mailinggroups' ),
		'menu_icon'        => 'dashicons-email-alt',
		'fields'           => array(
			'name'                   => array(
				'group'    => 'Description',
				'name'     => amapress__( 'Email' ),
				'type'     => 'text',
				'desc'     => 'Adresse email de la liste groupée',
				'required' => true,
				'is_email' => true,
			),
			'desc'                   => array(
				'group' => 'Description',
				'name'  => amapress__( 'Description' ),
				'type'  => 'text',
			),
			'host'                   => array(
				'group'       => 'Serveur',
				'name'        => amapress__( 'Serveur' ),
				'desc'        => 'Adresse du serveur IMAP/POP3<br/>Par exemple, pour OVH, le serveur IMAP/POP3 est ssl0.ovh.net',
				'type'        => 'text',
				'required'    => true,
				'show_column' => false,
			),
			'port'                   => array(
				'group'       => 'Serveur',
				'name'        => amapress__( 'Port' ),
				'desc'        => 'Port d\'accès au serveur IMAP/POP3<br/>Ports par défaut : IMAP 143; IMAP SSL 993; POP3 110 ; POP3 SSL 995',
				'type'        => 'number',
				'default'     => 993,
				'required'    => true,
				'show_column' => false,
			),
			'username'               => array(
				'group'        => 'Serveur',
				'name'         => amapress__( 'Utilisateur' ),
				'desc'         => 'Nom d\'utilisateur<br/>Par ex, chez OVH, l\'adresse email complète',
				'type'         => 'text',
				'autocomplete' => false,
				'required'     => true,
				'show_column'  => false,
			),
			'password'               => array(
				'group'        => 'Serveur',
				'name'         => amapress__( 'Mot de passe' ),
				'desc'         => 'Mod de passe',
				'type'         => 'text',
				'autocomplete' => false,
				'is_password'  => 'true',
				'required'     => true,
				'show_column'  => false,
			),
			'protocol'               => array(
				'group'       => 'Serveur',
				'name'        => amapress__( 'Protocole' ),
				'type'        => 'select',
				'cache'       => false,
				'desc'        => 'Choisir le type de serveur',
				'show_column' => false,
				'required'    => true,
				'default'     => 'imap',
				'options'     => [
					'pop3' => 'POP3',
					'imap' => 'IMAP',
				],
			),
			'encryption'             => array(
				'group'       => 'Serveur',
				'name'        => amapress__( 'Sécurité' ),
				'type'        => 'select',
				'cache'       => false,
				'desc'        => 'Choisir le type connexion sécurisée',
				'required'    => true,
				'show_column' => false,
				'default'     => 'ssl',
				'options'     => [
					'none' => 'Aucune',
					'ssl'  => 'SSL',
					'tls'  => 'TLS',
				],
			),
			'self_signed'            => array(
				'group'       => 'Serveur',
				'name'        => amapress__( 'Certificat autosigné' ),
				'type'        => 'checkbox',
				'show_column' => false,
			),
			'smtp_out_note'          => array(
				'group'       => 'Serveur sortant',
				'type'        => 'note',
				'desc'        => 'Laisser vide pour utiliser la configuration mail de l\'hébergement (recommandé) ou saisir la configuration SMTP pour l\'envoi des mails par cet boîte mail',
				'show_column' => false,
			),
			'smtp_host'              => array(
				'name'        => 'Host',
				'group'       => 'Serveur sortant',
				'type'        => 'text',
				'show_column' => false,
			),
			'smtp_port'              => array(
				'name'        => 'Port',
				'group'       => 'Serveur sortant<br/>Ports par défaut : SMTP 25; SMTP SSL 465; SMTP TLS 587',
				'type'        => 'number',
				'show_column' => false,
			),
			'smtp_encryption'        => array(
				'name'        => 'Encryption',
				'group'       => 'Serveur sortant',
				'type'        => 'select',
				'show_column' => false,
				'options'     => array(
					''    => 'None',
					'tls' => 'TLS',
					'ssl' => 'SSL',
				)
			),
			'smtp_timeout'           => array(
				'name'        => 'Timeout',
				'group'       => 'Serveur sortant',
				'type'        => 'number',
				'show_column' => false,
				'default'     => 30,
			),
			'smtp_use_auth'          => array(
				'name'        => 'Avec authentication ?',
				'group'       => 'Serveur sortant',
				'type'        => 'checkbox',
				'show_column' => false,
			),
			'smtp_out_note2'         => array(
				'group'       => 'Serveur sortant',
				'type'        => 'note',
				'desc'        => 'Si les identifiants sont les mêmes que l\'accès IMAP/POP, laissez les champs vides (et cocher la case "Avec authentication ?")',
				'show_column' => false,
			),
			'smtp_auth_username'     => array(
				'name'         => 'Username',
				'group'        => 'Serveur sortant',
				'autocomplete' => false,
				'type'         => 'text',
				'show_column'  => false,
			),
			'smtp_auth_password'     => array(
				'name'         => 'Password',
				'group'        => 'Serveur sortant',
				'type'         => 'text',
				'autocomplete' => false,
				'is_password'  => true,
				'show_column'  => false,
			),
			'subject_pref'           => array(
				'group'       => 'Description',
				'name'        => amapress__( 'Préfixe Sujet' ),
				'type'        => 'text',
				'show_column' => false,
				'desc'        => 'Préfixe à ajouter au sujet des emails relayés'
			),
			'moderation'             => array(
				'group'    => 'Modération',
				'name'     => amapress__( 'Modération' ),
				'type'     => 'select',
				'cache'    => false,
				'required' => true,
				'desc'     => 'Choisir le type de modération',
				'options'  => [
					'all'         => 'Modérée pour les membres et les non membres',
					'not_members' => 'Modérée pour les non membres uniquement',
					'none'        => 'Ouverte',
				],
			),
			'moderators_queries'     => array(
				'group'       => 'Modérateurs',
				'name'        => amapress__( 'Groupes inclus dans les modérateurs' ),
				'type'        => 'multicheck',
				'desc'        => 'Cocher le ou les groupes à intégrer.',
				'options'     => 'amapress_get_mailinglist_moderators_queries',
//				'required'    => true,
				'show_column' => true,
			),
			'moderators_other_users' => array(
				'group'        => 'Modérateurs',
				'name'         => amapress__( 'Modérateurs hors groupe' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'show_column'  => true,
			),
			'free_queries'           => array(
				'group'       => 'Non modérés',
				'name'        => amapress__( 'Groupes sans modération' ),
				'type'        => 'multicheck',
				'desc'        => 'Cocher le ou les groupes à intégrer.',
				'options'     => 'amapress_get_mailinglist_queries',
				'show_column' => false,
			),
			'free_other_users'       => array(
				'group'        => 'Non modérés',
				'name'         => amapress__( 'Sans modération hors groupe' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'show_column'  => false,
			),
			'waiting'                => array(
				'group'   => 'Modération',
				'name'    => amapress__( 'Emails en attente modération' ),
				'type'    => 'custom',
				'desc'    => 'Liste du ou des emails à valider.',
				'column'  => 'amapress_get_mailing_group_waiting',
				'custom'  => 'amapress_get_mailing_group_waiting_list',
				'show_on' => 'edit-only',
			),
			'members_count'          => array(
				'group'   => 'Membres',
				'name'    => amapress__( 'Membres' ),
				'type'    => 'custom',
				'desc'    => 'Liste des membres',
				'column'  => 'amapress_get_mailing_group_members_count',
				'custom'  => 'amapress_get_mailing_group_members_count',
				'show_on' => 'edit-only',
			),
			'queries'                => array(
				'group'       => 'Membres',
				'name'        => amapress__( 'Groupes inclus' ),
				'type'        => 'multicheck',
				'desc'        => 'Cocher le ou les groupes à intégrer.',
				'options'     => 'amapress_get_mailinglist_queries',
//				'required'    => true,
				'show_column' => false,
			),
			'other_users'            => array(
				'group'        => 'Membres',
				'name'         => amapress__( 'Amapiens hors groupe' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.',
				'show_column'  => false,
			),
			'raw_users'              => array(
				'group'       => 'Membres',
				'name'        => amapress__( 'Membres supplémentaires (emails)' ),
				'type'        => 'textarea',
				'desc'        => 'Liste d\'adresses emails à ajouter à cet Email groupé',
				'show_column' => false,
				'searchable'  => true,
			),
			'reply_to'               => array(
				'group'       => 'Membres',
				'name'        => amapress__( 'Reply to' ),
				'type'        => 'select',
				'desc'        => 'Choisir à qui répondent les destinataires de la liste',
				'options'     => [
					'sender' => 'Emetteur',
					'list'   => 'Liste',
				],
				'required'    => true,
				'show_column' => false,
			),
		),
	);

	return $entities;
}

function amapress_get_mailing_group_members_count( $mailing_group_id ) {
	$ml = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( ! $ml ) {
		return '';
	}

	$members_url = $ml->getAdminMembersLink();

	return "<a target='_blank' href='$members_url'>{$ml->getMembersCount()}</a>";
}

function amapress_get_mailing_group_waiting( $mailing_group_id ) {
	$ml = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( ! $ml ) {
		return '';
	}

	return $ml->getMailWaitingModerationCount();
}

function amapress_get_mailing_group_archive_list( $mailing_group_id, $type ) {
	$ml = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( ! $ml ) {
		return '';
	}
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
			'title' => 'Type',
			'data'  => array(
				'_'    => 'type',
				'sort' => 'type',
			)
		),
		array(
			'title' => 'Modérateur',
			'data'  => array(
				'_'    => 'moderator',
				'sort' => 'moderator',
			)
		),
		array(
			'title' => 'Contenu',
			'data'  => array(
				'_'    => 'content',
				'sort' => 'content',
			)
		),
	);
	$data    = array();
	foreach ( $ml->getMailArchives() as $m ) {
		$moderator      = isset( $m['moderator'] ) ? $m['moderator'] : 0;
		$moderator_user = null;
		if ( $moderator ) {
			$moderator_user = AmapressUser::getBy( $moderator );
		}
		$data[] = array(
			'from'      => esc_html( $m['from'] ),
			'date'      => ! empty( $m['date'] ) ? date_i18n( 'd/m/Y H:i:s', $m['date'] ) : '',
			'subject'   => $m['subject'],
			'content'   => $m['content'],
			'type'      => $m['type'] == 'accepted' ? 'Distribué' : 'Rejetté',
			'moderator' => ( $moderator_user ? $moderator_user->getDisplayName() : '' ) .
			               isset( $m['mod_date'] ) ? ' le ' . date_i18n( 'd/m/Y H:i:s', $m['mod_date'] ) : '',
		);
	}

	return amapress_get_datatable( $type . '-mails', $columns, $data,
		array(
			'aaSorting' => [ [ 0, 'desc' ] ]
		) );
}

function amapress_get_mailing_group_waiting_list( $mailing_group_id ) {
	$ml = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( ! $ml ) {
		return '';
	}
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
			'data'  => 'reject_quiet'
		),
		array(
			'title' => '',
			'data'  => 'reject'
		),
		array(
			'title' => '',
			'data'  => 'resend_mods'
		),
	);
	$data    = array();
	foreach ( $ml->getMailWaitingModeration() as $m ) {
		$data[] = array(
			'from'         => esc_html( $m['from'] ),
			'date'         => ! empty( $m['date'] ) ? date_i18n( 'd/m/Y H:i:s', $m['date'] ) : '',
			'subject'      => $m['subject'],
			'content'      => $m['content'],
			'resend_mods'  => amapress_get_mailgroup_action_form( 'Renvoyer la demande de modération', 'amapress_mailgroup_resend_mods', $ml->ID, $m['id'] ),
			'distribute'   => amapress_get_mailgroup_action_form( 'Distribuer', 'amapress_mailgroup_distribute', $ml->ID, $m['id'] ),
			'reject_quiet' => amapress_get_mailgroup_action_form( 'Rejetter sans prévenir', 'amapress_mailgroup_reject_quiet', $ml->ID, $m['id'] ),
			'reject'       => amapress_get_mailgroup_action_form( 'Rejetter', 'amapress_mailgroup_reject', $ml->ID, $m['id'] ),
		);
	}

	return amapress_get_datatable( 'waiting-mails', $columns, $data );
}

function amapress_get_mailgroup_action_form( $button_text, $action, $mailgroup_id, $msg_id ) {
	$href = add_query_arg(
		array(
			'action'       => $action,
			'mailgroup_id' => $mailgroup_id,
			'msg_id'       => $msg_id,
		),
		admin_url( 'admin.php' )
	);

	return '<a href="' . esc_attr( $href ) . '">' . esc_html( $button_text ) . '</a>';
}

add_action( 'admin_action_amapress_mailgroup_reject', 'admin_action_amapress_mailgroup_reject' );
function admin_action_amapress_mailgroup_reject() {
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->rejectMail( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo "Email $msg_id rejetté avec succès";
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_mailgroup_reject_quiet', 'admin_action_amapress_mailgroup_reject_quiet' );
function admin_action_amapress_mailgroup_reject_quiet() {
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->rejectMailQuiet( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo "Email $msg_id rejetté avec succès";
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_mailgroup_distribute', 'admin_action_amapress_mailgroup_distribute' );
function admin_action_amapress_mailgroup_distribute() {
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->distributeMail( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo "Email $msg_id distribué avec succès";
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_mailgroup_resend_mods', 'admin_action_amapress_mailgroup_resend_mods' );
function admin_action_amapress_mailgroup_resend_mods() {
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->resendModerationMail( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo "Mail de demande de modération renvoyé avec succès pour le message $msg_id ";
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

function amapress_fetch_mailinggroups() {
	foreach (
		get_posts( [
			'post_type' => AmapressMailingGroup::INTERNAL_POST_TYPE
		] ) as $post
	) {
		$ml = AmapressMailingGroup::getBy( $post );
		$ml->fetchMails();
	}
}

function amapress_clean_mailinggroups_archives() {
	foreach ( AmapressMailingGroup::getAll() as $ml ) {
		$ml->cleanLogs();
	}
}

add_action( 'init', function () {
	if ( extension_loaded( 'imap' ) ) {
		add_filter( 'cron_schedules', function ( $schedules ) {
			$mail_queue_interval    = Amapress::getOption( 'mailgroup_interval' );
			$interval               = ! empty( $mail_queue_interval ) ? intval( $mail_queue_interval ) : 30;
			$schedules['amps_mlgf'] = [
				'interval' => $interval,
				'display'  => __( 'Interval for fetching mailing groups', 'amapress' )
			];

			return $schedules;
		} );

		add_action( 'amps_mlgf_fetch', 'amapress_fetch_mailinggroups' );

		if ( ! wp_next_scheduled( 'amps_mlgf_fetch' ) ) {
			if ( wp_next_scheduled( 'amps_mlgf_fetch' ) ) {
				wp_clear_scheduled_hook( 'amps_mlgf_fetch' );
			}
			wp_schedule_event( time(), 'amps_mlgf', 'amps_mlgf_fetch' );
		}

		if ( ! wp_next_scheduled( 'amps_mlgf_clean_arc' ) ) {
			wp_schedule_event( time(), 'daily', 'amps_mlgf_clean_arc' );
		}
		add_action( 'amps_mlgf_clean_arc', 'amapress_clean_mailinggroups_archives' );

		if ( ! wp_next_scheduled( 'amps_mlgf_notif_waiting' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'amps_mlgf_notif_waiting' );
		}
		add_action( 'amps_mlgf_notif_waiting', function () {
			foreach ( AmapressMailingGroup::getAll() as $mlgrp ) {
				$waiting_count = $mlgrp->getMailWaitingModerationCount();
				if ( $waiting_count > 0 ) {
					$signature = get_bloginfo( 'name' );
					$subject   = "[{$mlgrp->getSimpleName()}] $waiting_count mail(s) sont en attente de modération";
					$url       = admin_url( 'admin.php?page=mailinggroup_moderation&tab=mailgrp-moderate-tab-' . $mlgrp->ID );
					$message   = "Bonjour,\n$waiting_count mail(s) sont en attente de modération pour {$mlgrp->getName()}.\n<a href='$url'>Voir les mails en attente</a>\n$signature";
					amapress_wp_mail( $mlgrp->getModeratorsEmails(), $subject, $message );
				}
			}
		} );


		amapress_register_shortcode( 'waiting-mlgrp-count', function () {
			$cnt = 0;
			foreach ( AmapressMailingGroup::getAll() as $ml ) {
				$cnt += $ml->getMailWaitingModerationCount();
			}

			return "<span class='update-plugins count-$cnt' style='background-color:white;color:black;margin-left:5px;'><span class='plugin-count'>$cnt</span></span>";
		} );
	} else {
		amapress_register_shortcode( 'waiting-mlgrp-count', function () {
			return '';
		} );
	}
} );