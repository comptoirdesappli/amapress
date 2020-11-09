<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_filter( 'amapress_register_entities', 'amapress_register_entities_mailing_groups' );
function amapress_register_entities_mailing_groups( $entities ) {
	$entities['mailing_group'] = array(
		'singular'         => __( 'Email groupé', 'amapress' ),
		'plural'           => __( 'Emails groupés', 'amapress' ),
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
							echo amapress_get_admin_notice( __( 'Erreur de configuration IMAP/POP : ', 'amapress' ) . $res, 'error', false );
						} else {
							echo amapress_get_admin_notice( __( 'Configuration IMAP/POP OK', 'amapress' ), 'success', false );
						}
					} else {
						echo amapress_get_admin_notice( __( 'Erreur de configuration : l\'extension IMAP n\'est pas installée, les emails groupés sont désactivés.', 'amapress' ), 'error', false );
					}
					if ( $ml->isExternalSmtp() ) {
						$res = $ml->testSMTP();
						if ( true !== $res ) {
							echo amapress_get_admin_notice( sprintf( __( 'Erreur de configuration, connexion au SMTP %s impossible : %s', 'amapress' ), $ml->getSmtpHost(), $res ), 'error', false );
						} else {
							echo amapress_get_admin_notice( __( 'Configuration SMTP OK', 'amapress' ), 'success', false );
						}
					} elseif ( $ml->shouldUseSmtp() ) {
						echo amapress_get_admin_notice(
							sprintf( __( 'Cette Email Groupé contient %d membres, le SMTP du compte IMAP devrait être configuré pour les envois.', 'amapress' ), $ml->getMembersCount() ),
							'warning', false );
					}
				}
			}
		},
//		'labels'           => array(
//			'add_new'      => __('Configurer une liste de diffusion existante', 'amapress'),
//			'add_new_item' => __('Configurer une liste de diffusion existante', 'amapress'),
//		),
		'views'            => array(
			'remove' => array( 'mine' )
		),
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'slug'             => __( 'mailinggroups', 'amapress' ),
		'menu_icon'        => 'dashicons-email-alt',
		'fields'           => array(
			'name'                   => array(
				'group'    => __( 'Description', 'amapress' ),
				'name'     => __( 'Email', 'amapress' ),
				'type'     => 'text',
				'desc'     => __( 'Adresse email de la liste groupée', 'amapress' ),
				'required' => true,
				'is_email' => true,
			),
			'desc'         => array(
				'group' => __( 'Description', 'amapress' ),
				'name'  => __( 'Description', 'amapress' ),
				'type'  => 'text',
			),
			'subject_pref' => array(
				'group'       => __( 'Description', 'amapress' ),
				'name'        => __( 'Préfixe Sujet', 'amapress' ),
				'type'        => 'text',
				'show_column' => false,
				'desc'        => __( 'Préfixe à ajouter au sujet des emails relayés', 'amapress' )
			),
			'reply_to'     => array(
				'group'       => __( 'Description', 'amapress' ),
				'name'        => __( 'Réponse à', 'amapress' ),
				'type'        => 'select',
				'desc'        => __( 'Choisir à qui répondent les destinataires de la liste', 'amapress' ),
				'options'     => [
					'sender' => __( 'Emetteur', 'amapress' ),
					'list'   => __( 'Liste', 'amapress' ),
				],
				'required'    => true,
				'show_column' => false,
			),
			'keep_sender'  => array(
				'group'       => __( 'Description', 'amapress' ),
				'name'        => __( 'Emetteur', 'amapress' ),
				'type'        => 'checkbox',
				'default'     => true,
				'show_column' => false,
				'desc'        => __( 'Préserver (si possible) l\'émetteur original du mail lors de la diffusion. Décoché : envoi de la part de la liste', 'amapress' )
			),
			'host'         => array(
				'group'       => __( 'Serveur', 'amapress' ),
				'name'        => __( 'Serveur', 'amapress' ),
				'desc'        => __( 'Adresse du serveur IMAP/POP3<br/>Par exemple, pour OVH, le serveur IMAP/POP3 est ssl0.ovh.net', 'amapress' ),
				'type'        => 'text',
				'required'    => true,
				'show_column' => false,
			),
			'port'         => array(
				'group'       => __( 'Serveur', 'amapress' ),
				'name'        => __( 'Port', 'amapress' ),
				'desc'        => __( 'Port d\'accès au serveur IMAP/POP3<br/>Ports par défaut : IMAP 143; IMAP SSL 993; POP3 110 ; POP3 SSL 995', 'amapress' ),
				'type'        => 'number',
				'default'     => 993,
				'max'         => 65535,
				'slider'      => false,
				'required'    => true,
				'show_column' => false,
			),
			'username'     => array(
				'group'        => __( 'Serveur', 'amapress' ),
				'name'         => __( 'Utilisateur', 'amapress' ),
				'desc'         => __( 'Nom d\'utilisateur<br/>Par ex, chez OVH, l\'adresse email complète', 'amapress' ),
				'type'         => 'text',
				'autocomplete' => false,
				'required'     => true,
				'show_column'  => false,
			),
			'password'               => array(
				'group'        => __( 'Serveur', 'amapress' ),
				'name'         => __( 'Mot de passe', 'amapress' ),
				'desc'         => __( 'Mod de passe', 'amapress' ),
				'type'         => 'text',
				'autocomplete' => false,
				'is_password'  => 'true',
				'required'     => true,
				'show_column'  => false,
			),
			'protocol'               => array(
				'group'       => __( 'Serveur', 'amapress' ),
				'name'        => __( 'Protocole', 'amapress' ),
				'type'        => 'select',
				'cache'       => false,
				'desc'        => __( 'Choisir le type de serveur', 'amapress' ),
				'show_column' => false,
				'required'    => true,
				'default'     => 'imap',
				'options'     => [
					'pop3' => 'POP3',
					'imap' => 'IMAP',
				],
			),
			'encryption'             => array(
				'group'       => __( 'Serveur', 'amapress' ),
				'name'        => __( 'Sécurité', 'amapress' ),
				'type'        => 'select',
				'cache'       => false,
				'desc'        => __( 'Choisir le type connexion sécurisée', 'amapress' ),
				'required'    => true,
				'show_column' => false,
				'default'     => 'ssl',
				'options'     => [
					'none' => __( 'Aucune', 'amapress' ),
					'ssl'  => 'SSL',
					'tls'  => 'TLS',
				],
			),
			'self_signed'            => array(
				'group'       => __( 'Serveur', 'amapress' ),
				'name'        => __( 'Certificat autosigné', 'amapress' ),
				'type'        => 'checkbox',
				'show_column' => false,
			),
			'smtp_out_note'          => array(
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'note',
				'desc'        => __( 'Laisser vide pour utiliser la configuration mail de l\'hébergement (recommandé) ou saisir la configuration SMTP pour l\'envoi des mails par cet boîte mail', 'amapress' ),
				'show_column' => false,
			),
			'smtp_host'              => array(
				'name'        => __( 'Host', 'amapress' ),
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'text',
				'show_column' => false,
			),
			'smtp_port'              => array(
				'name'        => __( 'Port', 'amapress' ),
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'number',
				'desc'        => __( 'Ports par défaut : SMTP 25; SMTP SSL 465; SMTP TLS 587', 'amapress' ),
				'max'         => 65535,
				'slider'      => false,
				'show_column' => false,
			),
			'smtp_encryption'        => array(
				'name'        => __( 'Encryption', 'amapress' ),
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'select',
				'show_column' => false,
				'options'     => array(
					''    => __( 'None', 'amapress' ),
					'tls' => 'TLS',
					'ssl' => 'SSL',
				)
			),
			'smtp_timeout'           => array(
				'name'        => __( 'Timeout', 'amapress' ),
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'number',
				'show_column' => false,
				'default'     => 30,
			),
			'smtp_use_auth'          => array(
				'name'        => __( 'Avec authentication ?', 'amapress' ),
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'checkbox',
				'show_column' => false,
			),
			'smtp_out_note2'         => array(
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'note',
				'desc'        => __( 'Si les identifiants sont les mêmes que l\'accès IMAP/POP, laissez les champs vides (et cocher la case "Avec authentication ?")', 'amapress' ),
				'show_column' => false,
			),
			'smtp_auth_username'     => array(
				'name'         => __( 'Username', 'amapress' ),
				'group'        => __( 'Serveur sortant', 'amapress' ),
				'autocomplete' => false,
				'type'         => 'text',
				'show_column'  => false,
			),
			'smtp_auth_password'     => array(
				'name'         => __( 'Password', 'amapress' ),
				'group'        => __( 'Serveur sortant', 'amapress' ),
				'type'         => 'text',
				'autocomplete' => false,
				'is_password'  => true,
				'show_column'  => false,
			),
			'smtp_max_per_hour'      => array(
				'name'        => __( 'Emails par heure', 'amapress' ),
				'group'       => __( 'Serveur sortant', 'amapress' ),
				'type'        => 'number',
				'desc'        => __( 'Nombre maximum d\'envoi de mail par heure autorisé', 'amapress' ),
				'max'         => 10000,
				'show_column' => false,
			),
			'moderation'             => array(
				'group'    => __( 'Modération', 'amapress' ),
				'name'     => __( 'Modération', 'amapress' ),
				'type'     => 'select',
				'cache'    => false,
				'required' => true,
				'desc'     => __( 'Choisir le type de modération', 'amapress' ),
				'options'  => [
					'all'         => __( 'Modérée pour les membres et les non membres', 'amapress' ),
					'not_members' => __( 'Modérée pour les non membres uniquement', 'amapress' ),
					'none'        => __( 'Ouverte', 'amapress' ),
				],
			),
			'moderators_queries'     => array(
				'group'       => __( 'Modérateurs', 'amapress' ),
				'name'        => __( 'Groupes inclus dans les modérateurs', 'amapress' ),
				'type'        => 'multicheck',
				'desc'        => __( 'Cocher le ou les groupes à intégrer.', 'amapress' ),
				'options'     => 'amapress_get_mailinglist_moderators_queries',
//				'required'    => true,
				'show_column' => true,
			),
			'moderators_other_users' => array(
				'group'        => __( 'Modérateurs', 'amapress' ),
				'name'         => __( 'Modérateurs hors groupe', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'show_column'  => true,
			),
			'inc_moderators'         => array(
				'group'       => __( 'Modérateurs', 'amapress' ),
				'name'        => __( 'Membres ?', 'amapress' ),
				'type'        => 'checkbox',
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Inclure les modérateurs dans les membres', 'amapress' )
			),
			'free_queries'           => array(
				'group'       => __( 'Non modérés', 'amapress' ),
				'name'        => __( 'Groupes sans modération', 'amapress' ),
				'type'        => 'multicheck',
				'desc'        => __( 'Cocher le ou les groupes à intégrer.', 'amapress' ),
				'options'     => 'amapress_get_mailinglist_queries',
				'show_column' => false,
			),
			'free_other_users'       => array(
				'group'        => __( 'Non modérés', 'amapress' ),
				'name'         => __( 'Sans modération hors groupe', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'show_column'  => false,
			),
			'inc_free'               => array(
				'group'       => __( 'Non modérés', 'amapress' ),
				'name'        => __( 'Membres ?', 'amapress' ),
				'type'        => 'checkbox',
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Inclure les sans modération dans les membres', 'amapress' )
			),
			'waiting'                => array(
				'group'   => __( 'Modération', 'amapress' ),
				'name'    => __( 'Emails en attente modération', 'amapress' ),
				'type'    => 'custom',
				'desc'    => __( 'Liste du ou des emails à valider.', 'amapress' ),
				'column'  => 'amapress_get_mailing_group_waiting',
				'custom'  => 'amapress_get_mailing_group_waiting_list',
				'show_on' => 'edit-only',
			),
			'members_count'          => array(
				'group'   => __( 'Membres', 'amapress' ),
				'name'    => __( 'Membres', 'amapress' ),
				'type'    => 'custom',
				'desc'    => __( 'Liste des membres', 'amapress' ),
				'column'  => 'amapress_get_mailing_group_members_count',
				'custom'  => 'amapress_get_mailing_group_members_count',
				'show_on' => 'edit-only',
			),
			'queries'                => array(
				'group'       => __( 'Membres', 'amapress' ),
				'name'        => __( 'Groupes inclus', 'amapress' ),
				'type'        => 'multicheck',
				'desc'        => __( 'Cocher le ou les groupes à intégrer.', 'amapress' ),
				'options'     => 'amapress_get_mailinglist_queries',
//				'required'    => true,
				'show_column' => false,
			),
			'inc_adh_requests'       => array(
				'group'       => __( 'Membres', 'amapress' ),
				'name'        => __( 'Inclure les demandes d\'adhésion', 'amapress' ),
				'type'        => 'checkbox',
				'desc'        => __( 'Inclure les demandes d\'adhésion non confirmées (Liste d\'attente)', 'amapress' ),
				'show_column' => false,
			),
			'other_users'            => array(
				'group'        => __( 'Membres', 'amapress' ),
				'name'         => __( 'Amapiens hors groupe', 'amapress' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => __( 'Sélectionner un ou plusieurs amapien(s) ne faisant pas partie d’un des groupes précédents.', 'amapress' ),
				'show_column'  => false,
			),
			'raw_users'              => array(
				'group'       => __( 'Membres', 'amapress' ),
				'name'        => __( 'Membres supplémentaires (emails)', 'amapress' ),
				'type'        => 'textarea',
				'desc'        => __( 'Liste d\'adresses emails à ajouter à cet Email groupé', 'amapress' ),
				'show_column' => false,
				'searchable'  => true,
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
	);

	return $entities;
}

function amapress_get_mailing_group_members_count( $mailing_group_id ) {
	$ml = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( ! $ml ) {
		return '';
	}

	return Amapress::makeLink(
		$ml->getAdminMembersLink(),
		sprintf( __( '%d membre(s) / %d email(s)', 'amapress' ),
			count( $ml->getMembersIds() ),
			$ml->getMembersCount()
		), true, true );
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
			'title' => __( 'Date', 'amapress' ),
			'data'  => array(
				'_'    => 'date',
				'sort' => 'date_sort',
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
			'title' => __( 'Type', 'amapress' ),
			'data'  => array(
				'_'    => 'type',
				'sort' => 'type',
			)
		),
		array(
			'title' => __( 'Modérateur', 'amapress' ),
			'data'  => array(
				'_'    => 'moderator',
				'sort' => 'moderator',
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
			'data'  => 'dl_eml'
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
			'date_sort' => ! empty( $m['date'] ) ? date_i18n( 'Y-m-d-H-i-s', $m['date'] ) : '',
			'subject'   => $m['subject'],
			'content'   => $m['content'],
			'type'      => $m['type'] == 'accepted' ? __( 'Distribué', 'amapress' ) : __( 'Rejetté', 'amapress' ),
			'moderator' => ( $moderator_user ? $moderator_user->getDisplayName() : '' ) .
			               isset( $m['mod_date'] ) ? ' le ' . date_i18n( 'd/m/Y H:i:s', $m['mod_date'] ) : '',
			'dl_eml'    => amapress_get_mailgroup_action_form(
				__( 'Télécharger le message', 'amapress' ), 'amapress_mailgroup_download_eml', $ml->ID, $m['id'], [
				'type' => $type
			] ),
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
		array(
			'title' => '',
			'data'  => 'dl_eml'
		),
	);
	$data    = array();
	foreach ( $ml->getMailWaitingModeration() as $m ) {
		$data[] = array(
			'from'         => esc_html( $m['from'] ),
			'date'         => ! empty( $m['date'] ) ? date_i18n( 'd/m/Y H:i:s', $m['date'] ) : '',
			'subject'      => $m['subject'],
			'content'      => $m['content'],
			'resend_mods'  => amapress_get_mailgroup_action_form( __( 'Renvoyer la demande de modération', 'amapress' ), 'amapress_mailgroup_resend_mods', $ml->ID, $m['id'] ),
			'distribute'   => amapress_get_mailgroup_action_form( __( 'Distribuer', 'amapress' ), 'amapress_mailgroup_distribute', $ml->ID, $m['id'] ),
			'reject_quiet' => amapress_get_mailgroup_action_form( __( 'Rejetter sans prévenir', 'amapress' ), 'amapress_mailgroup_reject_quiet', $ml->ID, $m['id'] ),
			'reject'       => amapress_get_mailgroup_action_form( __( 'Rejetter', 'amapress' ), 'amapress_mailgroup_reject', $ml->ID, $m['id'] ),
			'dl_eml'       => amapress_get_mailgroup_action_form(
				__( 'Télécharger le message', 'amapress' ), 'amapress_mailgroup_download_eml', $ml->ID, $m['id'], [
				'type' => 'waiting'
			] ),
		);
	}

	return amapress_get_datatable( 'waiting-mails', $columns, $data );
}

function amapress_get_mailgroup_action_form( $button_text, $action, $mailgroup_id, $msg_id, $others = [] ) {
	$href = add_query_arg(
		array_merge(
			array(
				'action'       => $action,
				'mailgroup_id' => $mailgroup_id,
				'msg_id'       => $msg_id,
			), $others ),
		admin_url( 'admin.php' )
	);

	return '<a href="' . esc_attr( $href ) . '">' . esc_html( $button_text ) . '</a>';
}

add_action( 'admin_action_amapress_mailgroup_reject', 'admin_action_amapress_mailgroup_reject' );
function admin_action_amapress_mailgroup_reject() {
	if ( empty( $_REQUEST['mailgroup_id'] ) || empty( $_REQUEST['msg_id'] ) ) {
		wp_die( __( 'Invalid request', 'amapress' ) );
	}
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->rejectMail( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo sprintf( __( 'Email %s rejetté avec succès', 'amapress' ), $msg_id );
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_mailgroup_reject_quiet', 'admin_action_amapress_mailgroup_reject_quiet' );
function admin_action_amapress_mailgroup_reject_quiet() {
	if ( empty( $_REQUEST['mailgroup_id'] ) || empty( $_REQUEST['msg_id'] ) ) {
		wp_die( __( 'Invalid request', 'amapress' ) );
	}
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->rejectMailQuiet( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo sprintf( __( 'Email %s rejetté avec succès', 'amapress' ), $msg_id );
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_mailgroup_distribute', 'admin_action_amapress_mailgroup_distribute' );
function admin_action_amapress_mailgroup_distribute() {
	if ( empty( $_REQUEST['mailgroup_id'] ) || empty( $_REQUEST['msg_id'] ) ) {
		wp_die( __( 'Invalid request', 'amapress' ) );
	}
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->distributeMail( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo sprintf( __( 'Email %s distribué avec succès', 'amapress' ), $msg_id );
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_mailgroup_resend_mods', 'admin_action_amapress_mailgroup_resend_mods' );
function admin_action_amapress_mailgroup_resend_mods() {
	if ( empty( $_REQUEST['mailgroup_id'] ) || empty( $_REQUEST['msg_id'] ) ) {
		wp_die( __( 'Invalid request', 'amapress' ) );
	}
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->resendModerationMail( $msg_id );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo sprintf( __( 'Mail de demande de modération renvoyé avec succès pour le message %s ', 'amapress' ), $msg_id );
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_mailgroup_download_eml', 'admin_action_amapress_mailgroup_download_eml' );
function admin_action_amapress_mailgroup_download_eml() {
	if ( empty( $_REQUEST['mailgroup_id'] ) || empty( $_REQUEST['msg_id'] ) ) {
		wp_die( __( 'Invalid request', 'amapress' ) );
	}
	$mailing_group_id = $_REQUEST['mailgroup_id'];
	$msg_id           = $_REQUEST['msg_id'];
	$type             = $_REQUEST['type'];
	$ml               = AmapressMailingGroup::getBy( $mailing_group_id );
	if ( $ml ) {
		$ml->downloadEml( $msg_id, $type );
	} else {
		wp_die( __( 'Mailing group not found', 'amapress' ) );
	}
	exit();
}


function amapress_fetch_mailinggroups() {
	foreach ( AmapressMailingGroup::getAll() as $ml ) {
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
			$interval               = ! empty( $mail_queue_interval ) ? intval( $mail_queue_interval ) : AMAPRESS_MAIL_QUEUE_DEFAULT_INTERVAL;
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
					$message   = sprintf( __( "Bonjour,\n%s mail(s) sont en attente de modération pour %s.\n<a href='%s'>Voir les mails en attente</a>\n%s", 'amapress' ), $waiting_count, $mlgrp->getName(), $url, $signature );
					amapress_wp_mail( $mlgrp->getModeratorsEmails(), $subject, $message );
				}
			}
		} );


		amapress_register_shortcode( 'moderation-mlgrp-count', function () {
			$cnt = AmapressMailingGroup::getAllWaitingForModerationCount();

			return "<span class='update-plugins count-$cnt' style='background-color:white;color:black;margin-left:5px;'><span class='plugin-count'>$cnt</span></span>";
		} );
		amapress_register_shortcode( 'waiting-mlgrp-count', function () {
			$cnt = AmapressMailingGroup::getAllWaitingCount();

			return "<span class='update-plugins count-$cnt' style='background-color:white;color:black;margin-left:5px;'><span class='plugin-count'>$cnt</span></span>";
		} );
		amapress_register_shortcode( 'errored-mlgrp-count', function () {
			$cnt = AmapressMailingGroup::getAllErroredCount();

			return "<span class='update-plugins count-$cnt' style='background-color:white;color:black;margin-left:5px;'><span class='plugin-count'>$cnt</span></span>";
		} );
	} else {
		amapress_register_shortcode( 'moderation-mlgrp-count', function () {
			return '';
		} );
		amapress_register_shortcode( 'waiting-mlgrp-count', function () {
			return '';
		} );
		amapress_register_shortcode( 'errored-mlgrp-count', function () {
			return '';
		} );
	}
} );