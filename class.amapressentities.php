<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressEntities {
	public static $settings_help = array(
		'amapress' => array(
			'add_help_tab' => array(
				array(
					'id'      => 'settings-overview',
					'title'   => 'Overview',
					'content' => array(
						'This is the place where you can customize the behavior of IdeaStream.',
						'Please see the additional help tabs for more information on each individual section.',
					),
				),
				array(
					'id'      => 'settings-main',
					'title'   => 'Main Settings',
					'content' => array(),
				),
			),
		),
	);
	public static $special_pages = array(
//        '/adhesion' => array('name' => 'Adhérer'),
//        '/mes-adhesions' => array('name' => 'Mes contrats'),
//        '/distributions' => array('name' => 'Porchains distributions'),
//        '/mes-evenements' => array('name' => 'Prochains évènements'),
//        '/visites' => array('name' => 'Prochaines visites à la ferme'),
//        '/assemblees' => array('name' => 'Prochaine assemblée'),
//        '/amapiens-autour-de-moi' => array('name' => 'Les amapiens autours de moi'),
//        '/mon-profile' => array('name' => 'Mon profile'),
//        '/trombinoscope' => array('name' => 'Le trombinoscope'),
	);
	private static $post_types = array();
	private static $post_types_initialized = false;
	private static $post_types_options = array();

//	public static $admin_bar_menu = array();

	public static function setTfOption( $post_type, $field, $option ) {
		$post_type                                                               = amapress_simplify_post_type( $post_type );
		self::$post_types_options["{$post_type}_{$field}"]                       = $option;
		self::$post_types_options["{$post_type}_amapress_{$post_type}_{$field}"] = $option;
	}

	/** @return TitanFrameworkOption */
	public static function getTfOption( $post_type, $field ) {
		$post_type = amapress_simplify_post_type( $post_type );
//        if (!isset(self::$post_types_options["{$post_type}_{$field}"])) {
//            var_dump($post_type);
//            var_dump($field);
//            die();
//        }
		return isset( self::$post_types_options["{$post_type}_{$field}"] ) ?
			self::$post_types_options["{$post_type}_{$field}"] :
			null;
	}
//add_menu_page($m['title'], $m['menu_title'],
//$m['capability'], $m['slug'],
//$m['function'], $m['icon'], $m['position']);

	private static $menu;

	static function getMenu() {
		if ( empty( AmapressEntities::$menu ) ) {
			$contrats_model_buttons = [];
			//required for overall optimize
			if ( amapress_is_user_logged_in() ) {
				$contrat_instances = AmapressContrats::get_active_contrat_instances();
				usort( $contrat_instances, function ( $a, $b ) {
					/** @var AmapressContrat_instance $a */
					/** @var AmapressContrat_instance $b */
					return strcmp( $a->getTitle(), $b->getTitle() );
				} );
				foreach ( $contrat_instances as $contrat_instance ) {
					$contrats_model_buttons[] = array(
						'type'   => 'action',
						'class'  => 'button button-primary button-import-model',
						'text'   => 'Télécharger le modèle "' . $contrat_instance->getTitle() . '"',
						'action' => 'generate_model_' . AmapressAdhesion::POST_TYPE . '_contrat_' . $contrat_instance->ID,
					);
				}
			}
			AmapressEntities::$menu = array(
				array(
					'type'       => 'page',
					'title'      => 'Demande d\'adhésions',
					'icon'       => 'dashicons-universal-access',
					'menu_title' => 'Demande d\'adhésions [adhesion-request-count]',
					'capability' => 'edit_adhesion_request',
					'slug'       => 'edit.php?post_type=amps_adh_req&amapress_date=active&amapress_status=to_confirm',
					'position'   => '27',
					'function'   => null,
				),
				array(
					'id'       => 'amapress_gestion_mailinggroup_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Emails groupés [moderation-mlgrp-count]',
						'position'   => '27',
						'capability' => 'read_mailing_group',
						'icon'       => 'dashicons-email-alt',
						'long_desc'  => '<div class="amapress-section-note"><p>Dans cette section, vous pouvez configurer et administrer les <strong>Emails groupés</strong>.</p>
<p>Un <strong>Email groupé</strong> est une <em>liste de diffusion simplifiée</em> à partir d’un compte email classique (accessible en IMAP ou POP3) et gérée depuis le site de votre AMAP (par Amapress).
Tout email envoyé à ces comptes email spécifiques seront (après modération ou non), envoyés à tous les membres de l’email groupé configuré sur le site.</p>
<h4>Dans cette section, vous pouvez :</h4>
<ul style="margin-left: 1em; list-style-type: disc">
<li>Modérer les emails en attente : sous-section <a href="' . admin_url( 'admin.php?page=mailinggroup_moderation' ) . '">Emails en attente</a></li>
<li>Consulter les archives des emails envoyés : sous-section <a href="' . admin_url( 'admin.php?page=mailinggroup_archives' ) . '">Archives</a></li>
' . ( current_user_can( 'manage_options' ) ? '<li>Configurer un nouvel Email groupé : sous-section <a href="' . admin_url( 'edit.php?post_type=amps_mlgrp' ) . '">Comptes</a></li>' : '' ) . '
' . ( current_user_can( 'manage_options' ) ? '<li>Configurer les <a href="' . admin_url( 'admin.php?page=mailingroup_recalls_page' ) . '">rappels</a> et <a href="' . admin_url( 'admin.php?page=mailinggroup_config_page' ) . '">autres paramètres</a></li>' : '' ) . '
</ul>
' . ( current_user_can( 'manage_options' ) ? '<h4 id="amapress_gestion_mailinggroup_page_cron">Important</h4><p>Cette fonctionnalité est basée sur le Cron de WordPress. Afin d\'assurer un envoi régulier des emails, vous pouvez créer un cron externe depuis votre hébergement ou toutes les 1 à 5 minutes depuis <a href="https://cron-job.org/" target="_blank">Cron-Job.Org</a> avec l\'url : <code>' . site_url( 'wp-cron.php?doing_wp_cron' ) . '</code> et ajouter <code>define(\'DISABLE_WP_CRON\', true);</code> à votre <code>wp-config.php</code></p>' : '' )
						                . '<p>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/email_groupe' ) . '</p>' . '</div>'
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'subpage'  => true,
							'id'       => 'mailinggroup_moderation',
							'settings' => array(
								'name'       => 'Modération - Emails en attente [moderation-mlgrp-count]',
								'menu_title' => 'Modération [moderation-mlgrp-count]',
								'capability' => 'read_mailing_group',
								'menu_icon'  => 'dashicons-shield',
							),
							'options'  => array(),
							'tabs'     => function () {
								if ( ! amapress_is_user_logged_in() ) {
									return [];
								}
								$tabs = array();
								$mls  = AmapressMailingGroup::getAll();
								usort( $mls, function ( $a, $b ) {
									return strcmp( $a->getSimpleName(), $b->getSimpleName() );
								} );
								foreach ( $mls as $ml ) {
									$ml_id                                                                                                                           = $ml->ID;
									$tabs[ sprintf( '%s - <span class="badge">%d</span> Emails en attente', $ml->getName(), $ml->getMailWaitingModerationCount() ) ] = array(
										'id'      => 'mailgrp-moderate-tab-' . $ml_id,
										'desc'    => '',
										'options' => array(
											array(
												'id'     => 'mailgrp-moderate-' . $ml_id,
												'name'   => 'Emails en attente',
												'bare'   => true,
												'type'   => 'custom',
												'custom' => function () use ( $ml_id ) {
													return amapress_get_mailing_group_waiting_list( $ml_id );
												},
											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'subpage'  => true,
							'id'       => 'mailinggroup_archives',
							'settings' => array(
								'name'       => 'Archives',
								'menu_title' => 'Archives',
								'capability' => 'read_mailing_group',
								'menu_icon'  => 'dashicons-book',
							),
							'options'  => array(),
							'tabs'     => function () {
								$tabs = array();
								$mls  = AmapressMailingGroup::getAll();
								usort( $mls, function ( $a, $b ) {
									return strcmp( $a->getSimpleName(), $b->getSimpleName() );
								} );
								foreach ( $mls as $ml ) {
									$ml_id                                                = $ml->ID;
									$tabs[ $ml->getName() . amapress__( ' - Archives' ) ] = array(
										'id'      => 'mailgrp-archives-tab-' . $ml_id,
										'desc'    => '',
										'options' => array(
											array(
												'id'     => 'mailgrp-archives-' . $ml_id,
												'name'   => 'Archives',
												'bare'   => true,
												'type'   => 'custom',
												'custom' => function () use ( $ml_id ) {
													return amapress_get_mailing_group_archive_list( $ml_id, 'accepted' );
												},
											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'subpage'  => true,
							'id'       => 'mailinggroup_mailqueue',
							'settings' => array(
								'name'       => 'Emails sortants en attente [waiting-mlgrp-count]',
								'menu_title' => 'Files attente [waiting-mlgrp-count]',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-clock',
							),
							'options'  => array(),
							'tabs'     => function () {
								$tabs = array();
								$mls  = AmapressMailingGroup::getAll();
								usort( $mls, function ( $a, $b ) {
									return strcmp( $a->getSimpleName(), $b->getSimpleName() );
								} );
								foreach ( $mls as $ml ) {
									$ml_id                                                                           = $ml->ID;
									$tabs[ sprintf( '%s  - File d\'attente <span class="badge">%d</span>',
										$ml->getName(), amapress_mailing_queue_waiting_mail_list_count( $ml_id ) ) ] = array(
										'id'      => 'mailgrp-mailqueue-tab-' . $ml_id,
										'desc'    => '',
										'options' => array(
											array(
												'id'     => 'mailgrp-mailqueue-' . $ml_id,
												'name'   => 'File d\'attente',
												'bare'   => true,
												'type'   => 'custom',
												'custom' => function () use ( $ml_id ) {
													return amapress_mailing_queue_waiting_mail_list( $ml_id );
												},
											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'subpage'  => true,
							'id'       => 'mailinggroup_mailerrors',
							'settings' => array(
								'name'       => 'Emails sortants en erreur [errored-mlgrp-count]',
								'menu_title' => 'Erreurs [errored-mlgrp-count]',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-dismiss',
							),
							'options'  => array(),
							'tabs'     => function () {
								$tabs = array();
								$mls  = AmapressMailingGroup::getAll();
								usort( $mls, function ( $a, $b ) {
									return strcmp( $a->getSimpleName(), $b->getSimpleName() );
								} );
								foreach ( $mls as $ml ) {
									$ml_id                                                                           = $ml->ID;
									$tabs[ sprintf( '%s  - Erreurs <span class="badge">%d</span>',
										$ml->getName(), amapress_mailing_queue_errored_mail_list_count( $ml_id ) ) ] = array(
										'id'      => 'mailgrp-mailerrors-tab-' . $ml_id,
										'desc'    => '',
										'options' => array(
											array(
												'id'     => 'mailgrp-mailerrors-' . $ml_id,
												'name'   => 'Erreurs',
												'bare'   => true,
												'type'   => 'custom',
												'custom' => function () use ( $ml_id ) {
													return amapress_mailing_queue_errored_mail_list( $ml_id );
												},
											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'subpage'  => true,
							'id'       => 'mailinggroup_maillog',
							'settings' => array(
								'name'       => 'Log des emails sortants',
								'menu_title' => 'Logs',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-text-page',
							),
							'options'  => array(),
							'tabs'     => function () {
								$tabs = array();
								$mls  = AmapressMailingGroup::getAll();
								usort( $mls, function ( $a, $b ) {
									return strcmp( $a->getSimpleName(), $b->getSimpleName() );
								} );
								foreach ( $mls as $ml ) {
									$ml_id                                            = $ml->ID;
									$tabs[ $ml->getName() . amapress__( ' - Logs' ) ] = array(
										'id'      => 'mailgrp-maillog-tab-' . $ml_id,
										'desc'    => '',
										'options' => array(
											array(
												'id'     => 'mailgrp-maillog-' . $ml_id,
												'name'   => 'Logs',
												'bare'   => true,
												'type'   => 'custom',
												'custom' => function () use ( $ml_id ) {
													return amapress_mailing_queue_logged_mail_list( $ml_id );
												},
											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'type'       => 'page',
							'title'      => 'Comptes',
							'menu_icon'  => 'dashicons-admin-tools',
							'menu_title' => 'Comptes',
							'post_type'  => AmapressMailingGroup::INTERNAL_POST_TYPE,
							'capability' => 'manage_options',
							'slug'       => 'edit.php?post_type=' . AmapressMailingGroup::INTERNAL_POST_TYPE,
							'function'   => null,
						),
						array(
							'subpage'  => true,
							'id'       => 'mailingroup_recalls_page',
							'settings' => array(
								'name'       => 'Emails et rappels',
								'menu_title' => 'Emails/Rappels',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(
								array(
									'name' => 'Email de notification à l\'émetteur d\'envoi pour modération',
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-waiting-sender-mail-subject',
									'name'     => 'Sujet de l\'email',
									'sanitize' => false,
									'type'     => 'text',
									'default'  => 'Email pour la liste %%liste_nom%% transmis au(x) modérateur(s)',
								),
								array(
									'id'      => 'mailinggroup-waiting-sender-mail-content',
									'name'    => 'Contenu de l\'email',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n\nVotre email pour la liste %%liste_nom%% a été transmis au(x) modérateur(s)\n\n%%nom_site%%" ),
									'desc'    => AmapressMailingGroup::getPlaceholdersHelp(),
								),
								array(
									'name' => 'Email de notification d\'un email à modérer aux modérateurs',
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-waiting-mods-mail-subject',
									'name'     => 'Sujet de l\'email',
									'sanitize' => false,
									'type'     => 'text',
									'default'  => 'Email à modérer de %%sender%% pour la liste %%liste_nom%%',
								),
								array(
									'id'      => 'mailinggroup-waiting-mods-mail-content',
									'name'    => 'Contenu de l\'email',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n\nUn nouvel email pour la liste %%liste_nom%% est arrivé de %%sender%%.\n\n%%msg_summary%%\n\nPour voir les messages en attente, cliquez ici : %%msg_waiting_link%%\n\nPour accepter sa diffusion (il sera distribué), cliquez ici : %%msg_distrib_link%%\n\nPour refuser sa diffusion avec notification (il sera effacé avec notification à l'émetteur), cliquez ici : %%msg_reject_notif_link%%\n\nPour refuser sa diffusion sans notification (il sera effacé sans notification), cliquez ici : %%msg_reject_silent_link%%\n\n%%nom_site%%" ),
									'desc'    => AmapressMailingGroup::getPlaceholdersHelp(),
								),
								array(
									'name' => 'Email de notification du rejet d\'un email à l\'émetteur',
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-reject-sender-mail-subject',
									'name'     => 'Sujet de l\'email',
									'sanitize' => false,
									'type'     => 'text',
									'default'  => 'Rejet de votre email à %%liste_nom%% - %%msg_subject%%',
								),
								array(
									'id'      => 'mailinggroup-reject-sender-mail-content',
									'name'    => 'Contenu de l\'email',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n\nVotre email pour la liste %%liste_nom%% a été rejeté par %%moderated_by%%, modérateur de la liste.\n\n(L'objet de votre email : %%msg_subject%%)\n\n%%nom_site%%" ),
									'desc'    => AmapressMailingGroup::getPlaceholdersHelp(),
								),
								array(
									'name' => 'Email de notification de distribution d\'un email à l\'émetteur',
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-distrib-sender-mail-subject',
									'name'     => 'Sujet de l\'email',
									'sanitize' => false,
									'type'     => 'text',
									'default'  => 'Diffusion de votre email à %%liste_nom%%',
								),
								array(
									'id'      => 'mailinggroup-distrib-sender-mail-content',
									'name'    => 'Contenu de l\'email',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n\nVotre email pour la liste %%liste_nom%% a été accepté et distribué par %%moderated_by%%, modérateur de la liste.\n\n(L'objet de votre email : %%msg_subject%%)\n\n%%nom_site%%" ),
									'desc'    => AmapressMailingGroup::getPlaceholdersHelp(),
								),
								array(
									'type' => 'save',
								),
							),
							'tabs'     => array(),
						),
						array(
							'subpage'  => true,
							'id'       => 'mailinggroup_config_page',
							'settings' => array(
								'name'       => 'Configuration',
								'menu_title' => 'Configuration',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(
								array(
									'id'      => 'mailgroup_interval',
									'name'    => 'Interval',
									'type'    => 'number',
									'desc'    => 'Interval d\'exécution du fetcher des Emails groupés. Nécessite un appel cron externe régulier pour ne pas dépendre du traffic sur le site.',
									'default' => '300',
								),
								array(
									'id'      => 'mailinggroup-unk-action',
									'name'    => 'Action pour expéditeur inconnu',
									'type'    => 'select',
									'options' => [
										'moderate' => 'Modérer',
										'reject'   => 'Rejeté',
									],
									'desc'    => 'Action à appliquer aux expéditeurs inconnus du site',
									'default' => 'moderate',
								),
								array(
									'id'   => 'mailinggroup-bl-regex',
									'name' => 'Blacklist',
									'type' => 'text',
									'desc' => 'Regex de blacklist',
								),
								array(
									'id'      => 'mailinggroup-send-confirm-unk',
									'name'    => 'Envoyer confirmation aux expéditeurs inconnus',
									'type'    => 'checkbox',
									'desc'    => 'Envoyer les confirmations aux expéditeurs inconnus',
									'default' => false,
								),
								array(
									'id'      => 'mail_group_log_clean_days',
									'type'    => 'number',
									'step'    => 1,
									'default' => 90,
									'name'    => 'Nettoyer les archives des Emails groupés (jours)',
								),
								array(
									'id'      => 'mail_group_waiting_log_clean_days',
									'type'    => 'number',
									'step'    => 1,
									'default' => 7,
									'name'    => 'Nettoyer les logs des files d\'attente d\'envoi (jours)',
								),
								array(
									'type' => 'save',
								),
							),
							'tabs'     => array(),
						),
					),
				),
				array(
					'id'       => 'amapress_mailinglist_sync_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Listes de diffusions',
						'position'   => '83',
						'capability' => 'manage_options',
						'icon'       => 'dashicons-share-alt',
						'long_desc'  => '<div class="amapress-section-note"><h4>Dans cette section, vous pouvez :</h4>
<ul style="margin-left: 1em; list-style-type: disc">
<li>configurer la synchronisation de <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_mailing' ) . '">listes de diffusions</a></li>
<li>configurer les <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_mailinglist_options_page' ) . '">systèmes de listes de diffusions</a></li>
</ul></div>'
					),
					'subpages' => array(
						array(
							'title'      => 'Comptes',
							'menu_icon'  => 'dashicons-email-alt',
							'menu_title' => 'Comptes',
							'post_type'  => Amapress_MailingListConfiguration::INTERNAL_POST_TYPE,
							'position'   => '82',
							'capability' => 'manage_options',
							'slug'       => 'edit.php?post_type=' . Amapress_MailingListConfiguration::INTERNAL_POST_TYPE,
							'function'   => null,
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_mailinglist_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Configuration',
								'capability' => 'manage_options',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(
//								array(
//									'type' => 'note',
//									'desc' => 'ici vous pouvez gérer...'
//								),
							),
							'tabs'     => array(
								'Général'                             => array(
									'id'      => 'amapress_mailinglist_sync_generic_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'           => 'mailing_other_users',
											'name'         => amapress__( 'Utilisateurs inclus dans toutes les listes' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Sync by SQL (ie. Ouvaton) - Sympa'   => array(
									'id'      => 'amapress_mailinglist_sync_sql_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'ouvaton_mailing_domain',
											'name'    => 'Domaine de la liste de diffusion',
											'type'    => 'text',
											'default' => '',
										),
										array(
											'id'           => 'ouvaton_admin_user',
											'name'         => 'Email de l\'admin',
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
										),
										array(
											'id'           => 'ouvaton_admin_pass',
											'name'         => 'Mot de passe',
											'type'         => 'text',
											'autocomplete' => false,
											'is_password'  => true,
											'default'      => '',
										),
										array(
											'id'      => 'ouvaton_manage_waiting',
											'name'    => 'Gérer la modération des emails dans Amapress',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Sync by Url (ie. Sud Ouest) - Sympa' => array(
									'id'      => 'amapress_mailinglist_sync_url_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'sud-ouest_mailing_domain',
											'name'    => 'Domaine de la liste de diffusion',
											'type'    => 'text',
											'default' => '',
										),
										array(
											'id'           => 'sud-ouest_admin_user',
											'name'         => 'Email de l\'admin',
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
										),
										array(
											'id'           => 'sud-ouest_admin_pass',
											'name'         => 'Mot de passe',
											'type'         => 'text',
											'autocomplete' => false,
											'is_password'  => true,
											'default'      => '',
										),
										array(
											'id'      => 'sud-ouest_secret',
											'name'    => 'Secret pour la mise à jour des membres',
											'type'    => 'text',
											'default' => uniqid(),
										),
										array(
											'id'      => 'sud-ouest_manage_waiting',
											'name'    => 'Gérer la modération des emails dans Amapress',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'type' => 'save',
										),
									)
								),
							),
						),
					),
				),
				array(
					'type'       => 'page',
					'title'      => 'Etat d\'Amapress',
					'icon'       => 'dashicons-none flaticon-buildings',
					'menu_title' => 'Etat d\'Amapress',
					'capability' => 'manage_amapress',
					'slug'       => 'amapress_state',
					'position'   => '40',
					'function'   => 'amapress_echo_and_check_amapress_state_page',
				),
				array(
					'id'       => 'amapress_gestion_distrib_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Distributions',
						'position'   => '28',
						'capability' => 'edit_distribution',
						'icon'       => 'dashicons-store',
						'long_desc'  => '<div class="amapress-section-note"><h4>Dans cette section, vous pouvez :</h4>
<ul style="margin-left: 1em; list-style-type: disc">
<li>gérer les <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_distribution&amapress_date=thismonth' ) . '">distributions</a> (modification de lieu et d\'heure), le déplacement de livraison de panier, se fait dans <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_panier&amapress_date=thismonth' ) . '">Tableau de bord&gt;Contenus&gt;Paniers</a></li>' .
						                ( amapress_current_user_can( 'edit_panier' ) ? '<li>publier le <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_panier&amapress_date=thismonth' ) . '">contenu des paniers</a> (par exemple, pour un contrat <em>légumes hebdomadaire</em>)</li>' : '' ) .
						                ( amapress_current_user_can( 'edit_panier' ) ? '<li>déplacer/annuler les <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_panier&amapress_date=thismonth' ) . '">livraisons de paniers</a></li>' : '' ) .
						                '<li>configurer les <a target="_blank" href="' . admin_url( 'admin.php?page=event_mails_page' ) . '">emails de rappels</a> (pour les événements, les responsables de distribution...)</li>
<li>obtenir des <a target="_blank" href="' . admin_url( 'admin.php?page=distrib_page_stats' ) . '">statistiques</a> d\'inscriptions aux distributions</li>
<li>attribuer des <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_events_conf_opt_page&tab=amp_tab_role_resp_distrib' ) . '">rôles aux différents responsables de distributions</a> requis</li>
<li>configurer la présentation de la <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_emargement_options_tab' ) . '">liste d\'émargement</a></li>
</ul></div>'
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => 'Distributions hebdomadaires',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Distributions hebdomadaires',
							'post_type'  => 'amps_distribution',
							'capability' => 'edit_distribution',
							'slug'       => 'edit.php?post_type=amps_distribution&amapress_date=thismonth',
						),
						array(
							'title'      => 'Paniers',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Paniers',
							'post_type'  => 'amps_panier',
							'capability' => 'edit_panier',
							'slug'       => 'edit.php?post_type=amps_panier&amapress_date=thismonth',
						),
						array(
							'subpage'  => true,
							'id'       => 'distrib_page_stats',
							'settings' => array(
								'name'       => 'Statistiques des distributions',
								'menu_title' => 'Statistiques',
//								'position'   => '25.2',
								'capability' => 'edit_distribution',
								'menu_icon'  => 'dashicons-chart-bar',
							),
							'options'  => array(
								array(
									'id'     => 'distrib_stats',
									'bare'   => true,
									'type'   => 'custom',
									'custom' => function () {
										$start_date_fmt = ! empty( $_REQUEST['amp_stats_start_date'] ) ? $_REQUEST['amp_stats_start_date'] : date_i18n( 'd/m/Y', Amapress::add_a_month( amapress_time(), - 12 ) );
										$end_date_fmt   = ! empty( $_REQUEST['amp_stats_end_date'] ) ? $_REQUEST['amp_stats_end_date'] : date_i18n( 'd/m/Y', amapress_time() );
										ob_start();
										TitanFrameworkOptionDate::createCalendarScript();

										echo '<p>Obtenir des statistisque pour la période suivante :</p>';
										echo '<label class="tf-date" for="amp_stats_start_date">Début: <input id="amp_stats_start_date" class="input-date date required " name="amp_stats_start_date" type="text" value="' . $start_date_fmt . '" /></label>';
										echo '<label class="tf-date" for="amp_stats_end_date">Fin: <input id="amp_stats_end_date" class="input-date date required " name="amp_stats_end_date" type="text" value="' . $end_date_fmt . '" /></label>';
										echo '<input type="submit" class="button button-primary" value="Voir les statistiques" />';
										echo '<hr />';


										echo '<h4>Inscriptions aux distributions du ' . $start_date_fmt . ' au ' . $end_date_fmt . '</h4>';

										$columns         = [];
										$columns[]       = array(
											'title' => 'Amapien',
											'data'  => array(
												'_'    => 'user',
												'sort' => 'sort_user',
											),
										);
										$columns[]       = array(
											'title' => 'Lieu',
											'data'  => 'lieu',
										);
										$columns[]       = array(
											'title' => 'Contrats',
											'data'  => 'contrats',
										);
										$columns[]       = array(
											'title' => 'Dates',
											'data'  => 'resp_dates',
										);
										$columns[]       = array(
											'title' => 'Inscriptions',
											'data'  => 'resp_nb',
										);
										$user_names      = [];
										$user_sort_names = [];
										$user_contrats   = [];
										$user_lieux      = [];
										$user_resps      = [];
										$start_date      = DateTime::createFromFormat( 'd/m/Y', $start_date_fmt )->getTimestamp();
										$end_date        = DateTime::createFromFormat( 'd/m/Y', $end_date_fmt )->getTimestamp();
										$contrat_ids     = [];
										foreach ( AmapressDistribution::get_distributions( $start_date, $end_date, 'ASC' ) as $distribution ) {
											foreach ( $distribution->getContrats() as $c ) {
												if ( ! in_array( $c->ID, $contrat_ids ) ) {
													foreach ( AmapressContrats::get_all_adhesions( $c->ID ) as $adh ) {
														if ( empty( $adh->getAdherent() ) ) {
															continue;
														}
														$rid = strval( $adh->getAdherentId() );
														if ( ! isset( $user_resps[ $rid ] ) ) {
															$user_resps[ $rid ] = [];
														}
														if ( ! isset( $user_names[ $rid ] ) ) {
															$user_names[ $rid ] = Amapress::makeLink( $adh->getAdherent()->getEditLink(), $adh->getAdherent()->getDisplayName() . '(' . $adh->getAdherent()->getUser()->user_email . ')' );
														}
														if ( ! isset( $user_sort_names[ $rid ] ) ) {
															$user_sort_names[ $rid ] = $adh->getAdherent()->getSortableDisplayName();
														}
														if ( ! isset( $user_lieux[ $rid ] ) ) {
															$user_lieux[ $rid ] = $adh->getLieu()->getLieuTitle();
														}
														if ( ! isset( $user_contrats[ $rid ] ) ) {
															$user_contrats[ $rid ] = [];
														}
														$user_contrats[ $rid ][] = Amapress::makeLink( $adh->getContrat_instance()->getAdminEditLink(), $adh->getContrat_instance()->getTitle() );
													}
													$contrat_ids[] = $c->ID;
												}
											}
											foreach ( $distribution->getResponsables() as $r ) {
												if ( empty( $r ) ) {
													continue;
												}
												$rid = strval( $r->ID );
												if ( ! isset( $user_resps[ $rid ] ) ) {
													$user_resps[ $rid ] = [];
												}
												$user_resps[ $rid ][] = Amapress::makeLink( $distribution->getAdminEditLink(), date_i18n( 'd/m/Y', $distribution->getDate() ) );
												if ( ! isset( $user_names[ $rid ] ) ) {
													$user_names[ $rid ] = Amapress::makeLink( $r->getEditLink(), $r->getDisplayName() . '(' . $r->getUser()->user_email . ')' );
												}
												if ( ! isset( $user_sort_names[ $rid ] ) ) {
													$user_sort_names[ $rid ] = $r->getSortableDisplayName();
												}
												if ( ! isset( $user_lieux[ $rid ] ) ) {
													$user_lieux[ $rid ] = $distribution->getLieu()->getLieuTitle();
												}
												if ( ! isset( $user_contrats[ $rid ] ) ) {
													$user_contrats[ $rid ] = [];
												}
											}
										}
//										sort( $user_names );
										$lines = [];
										foreach ( $user_names as $user_id => $user_name ) {
											$lines[] = array(
												'user'       => $user_name,
												'sort_user'  => $user_sort_names[ $user_id ],
												'lieu'       => $user_lieux[ $user_id ],
												'contrats'   => implode( ', ', $user_contrats[ $user_id ] ),
												'resp_dates' => implode( ', ', $user_resps[ $user_id ] ),
												'resp_nb'    => count( $user_resps[ $user_id ] ),
											);
										}
										amapress_echo_datatable( 'amp_distrib_stats_table',
											$columns, $lines,
											array(
												'paging'       => false,
												'searching'    => true,
												'nowrap'       => false,
												'responsive'   => false,
												'init_as_html' => true,
												'fixedHeader'  => array(
													'headerOffset' => 32
												),
											),
											array(
												Amapress::DATATABLES_EXPORT_EXCEL
											)
										);

										return ob_get_clean();
									}
								),
							),
							'tabs'     => array(),
						),
						array(
							'subpage'  => true,
							'id'       => 'distrib_mails_page',
							'settings' => array(
								'name'       => 'Emails et rappels',
								'menu_title' => 'Emails/Rappels',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								'Emails - Inscriptions - Distribution'                       => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'inscr-distrib-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Votre inscription à %%post:title%%',
										),
										array(
											'id'      => 'inscr-distrib-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre inscription à %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [
													'resp_role'          => 'Nom du rôle de responsable choisi',
													'resp_role_desc'     => 'Description du rôle de responsable choisi',
													'resp_role_contrats' => 'Contrats associés au rôle de responsable choisi',
												], false ),
										),
										array(
											'id'           => 'inscr-distrib-mail-cc',
											'name'         => amapress__( 'Cc' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => 'Emails en copie',
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Emails - Désinscriptions - Distribution'                    => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'desinscr-distrib-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Désinscription de %%post:title%%',
										),
										array(
											'id'      => 'desinscr-distrib-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre désinscription de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [], false ),
										),
										array(
											'id'           => 'desinscr-distrib-mail-cc',
											'name'         => amapress__( 'Cc' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => 'Emails en copie',
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Emails - Responsables de distribution - Rappel'             => array(
									'id'      => 'amp_tab_recall_resp_distrib',
									'desc'    => '',
									'options' => amapress_distribution_responsable_recall_options(),
								),
								'Emails - Responsables de distribution - Rappel 2'           => array(
									'id'      => 'amp_tab_recall_resp_distrib2',
									'desc'    => '',
									'options' => amapress_distribution_responsable_recall2_options(),
								),
								'Emails - Gardiens de paniers - Rappel'                      => array(
									'id'      => 'amp_tab_recall_gardien_paniers',
									'desc'    => '',
									'options' => amapress_distribution_gardiens_recall_options(),
								),
								'Emails - Vérification de distribution - Rappel'             => array(
									'id'      => 'amp_tab_recall_verif_distrib',
									'desc'    => '',
									'options' => amapress_distribution_verify_recall_options(),
								),
								'Emails - A tous les amapiens à la distribution - Rappel'    => array(
									'id'      => 'amp_tab_recall_all_amapiens',
									'desc'    => '',
									'options' => amapress_distribution_all_amapiens_recall_options(),
								),
								'Emails - Envoi liste émargement Excel/PDF'                  => array(
									'id'      => 'amp_tab_recall_emarg',
									'desc'    => '',
									'options' => amapress_distribution_emargement_recall_options(),
								),
								'Emails - Responsable(s) manquant(s) - Rappel'               => array(
									'id'      => 'amp_tab_recall_miss_resps',
									'desc'    => '',
									'options' => amapress_distribution_missing_responsables_recall_options(),
								),
								'Emails - Distribution - Modification livraisons - Rappel'   => array(
									'id'      => 'amp_tab_recall_modif_distrib',
									'desc'    => '',
									'options' => amapress_distribution_changes_recall_options(),
								),
								'Emails - Inscription aux créneaux de distribution - Rappel' => array(
									'id'      => 'amp_tab_recall_slot_inscr',
									'desc'    => '',
									'options' => amapress_distribution_slots_inscr_recall_options(),
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_distribs_conf_opt_page',
							'settings' => array(
								'name'       => 'Configuration',
								'menu_title' => 'Configuration',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								'Liste émargement'                       => array(
									'id'      => 'amp_emargement_options_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'liste-emargement-show-lieu-instructions',
											'name'    => 'Afficher les instructions des lieux',
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'      => 'liste-emargement-show-dist-instructions',
											'name'    => 'Afficher les instructions de distribution des contrats',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'   => 'liste-emargement-general-message',
											'name' => 'Message général',
											'type' => 'editor',
										),
										array(
											'id'      => 'liste-emargement-disable-liste',
											'name'    => 'Masquer la liste d\'émargement',
											'desc'    => 'Pour une amap qui n\'a que des paniers modulables, la liste d\'émargement est inutile car elle ne contient que des Var.',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-phone',
											'name'    => 'Afficher les numéros de téléphone',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-address',
											'name'    => 'Afficher les adresses',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-mail',
											'name'    => 'Afficher les emails',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-comment',
											'name'    => 'Afficher la colonne Commentaire',
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'      => 'liste-emargement-print-font-size',
											'name'    => 'Taille d\'impression',
											'desc'    => 'Taille (en pt) d\'impression de la liste d\'émargement',
											'type'    => 'number',
											'step'    => 0.5,
											'default' => '8',
										),
										array(
											'id'      => 'liste-emargement-next-resp-count',
											'name'    => 'Responsables prochaines distributions',
											'desc'    => 'Nombre de distribution à afficher pour inscrire les prochains responsables de distribution',
											'type'    => 'number',
											'step'    => 1,
											'default' => '8',
										),
										//liste-emargement-next-resp-count
										array(
											'type' => 'save',
										),
									)
								),
								'Inscription distribution'               => array(
									'id'      => 'amp_inscr_distrib_options_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'inscr-distrib-allow-multi',
											'name'    => 'Multi-inscriptions',
											'type'    => 'checkbox',
											'desc'    => 'Autoriser un amapien à s\'inscrire plusieurs fois comme responsable de distribution',
											'default' => true,
										),
										array(
											'id'      => 'close-subscribe-distrib-hours',
											'name'    => 'Clôture inscriptions',
											'desc'    => 'Clôturer les inscriptions x heures avant la distribution',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)',
										),
										array(
											'id'      => 'close-unsubscribe-distrib-hours',
											'name'    => 'Clôture désinscriptions',
											'desc'    => 'Clôturer les désinscriptions x heures avant la distribution',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)',
										),
										array(
											'id'      => 'inscr-distrib-button-first',
											'name'    => 'Boutons d\'inscription',
											'type'    => 'checkbox',
											'desc'    => 'Placer les boutons d\'inscription en premier et les inscrits ensuite (sauf si des rôles de responsables sont utilisés)',
											'default' => true,
										),
										array(
											'id'      => 'inscr-distrib-co-adh',
											'name'    => 'Inscriptions co-adhérents',
											'type'    => 'checkbox',
											'desc'    => 'Autoriser l\'inscription des co-adhérents par l\'adhérent principal',
											'default' => false,
										),
										array(
											'id'      => 'inscr-distrib-co-foyer',
											'name'    => 'Inscriptions membres foyers',
											'type'    => 'checkbox',
											'desc'    => 'Autoriser l\'inscription des membres du foyer par l\'adhérent principal',
											'default' => true,
										),
										array(
											'id'      => 'inscr-distrib-font-size',
											'name'    => 'Taille de police',
											'desc'    => 'Taille (avec unité, par ex, pt/px/em/rem) du tablau d\'inscription des responsables de distribution',
											'type'    => 'text',
											'default' => '11px',
										),
										array(
											'id'      => 'inscr-distrib-max-dates',
											'name'    => 'Distributions',
											'desc'    => 'Nombre de distribution à afficher pour inscrire les prochains responsables de distribution (-1 = toutes)',
											'type'    => 'number',
											'min'     => - 1,
											'step'    => 1,
											'default' => - 1,
											'slider'  => false,
										),
										array(
											'id'      => 'inscr-distrib-column-date-width',
											'name'    => 'Largeur colonne Date',
											'desc'    => 'Largeur de la colonne Date en em/rem/px (ne pas mettre de valeur en %)',
											'type'    => 'text',
											'default' => '6rem',
										),
										array(
											'id'      => 'inscr-distrib-column-resp-width',
											'name'    => 'Largeur colonne Responsable',
											'desc'    => 'Largeur de la colonne Responsable (et Gardien de paniers/Créneaux) en em/rem/px (ne pas mettre de valeur en %)',
											'type'    => 'text',
											'default' => '8rem',
										),
										array(
											'id'      => 'inscr-distrib-scroll-y',
											'name'    => 'Hauteur',
											'desc'    => 'Hauteur du défilement vertical en px',
											'type'    => 'number',
											'min'     => 100,
											'step'    => 1,
											'default' => 300,
											'slider'  => false,
											'unit'    => 'px'
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Roles des Responsables de distribution' => array(
									'id'      => 'amp_tab_role_resp_distrib',
									'desc'    => '',
									'options' => amapress_distribution_responsable_roles_options(),
								),
								'Gardiens de paniers'                    => array(
									'id'      => 'amp_tab_gardiens_paniers_distrib',
									'desc'    => '',
									'options' => [
										array(
											'id'   => 'enable-gardiens-paniers',
											'name' => 'Activer',
											'desc' => 'Activer le système de gardiens de paniers',
											'type' => 'checkbox',
										),
										array(
											'id'   => 'allow-affect-gardiens',
											'name' => 'Autoriser',
											'desc' => 'Autoriser les amapiens à choisir directement leur gardien de paniers',
											'type' => 'checkbox',
										),
										array(
											'id'      => 'gardiens-paniers-message',
											'name'    => 'Information',
											'type'    => 'textarea',
											'default' => 'La garde de panier se fait sur la base du volontariat. Si vous ne trouvez pas de gardiens, veuillez écrire à [à compléter]',
											'desc'    => 'Message au sujet des gardiens de paniers à afficher sur la page de chaque distribution pour informer sur la procédure de garde de paniers.',
										),
										array(
											'name' => 'Email à l\'amapien faisant garder son panier',
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-distrib-gardiened-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Garde de vos paniers par %%gardien%% à %%post:title%%',
										),
										array(
											'id'      => 'inscr-distrib-gardiened-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\n%%gardien%% (%%gardien_contact%% / %%gardien_comment%%) gardera vos paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [
													'amapien'          => 'Nom de l\'amapien demandeur de garde de son panier',
													'amapien_contacts' => 'Coordonnées de l\'amapien demandeur de garde de son panier',
													'gardien'          => 'Nom du gardien de panier choisi',
													'gardien_contact'  => 'Coordonnées du gardien de panier choisi',
													'gardien_comment'  => 'Message/commentaire du gardien de panier choisi',
												], false ),
										),
										array(
											'name' => 'Email au gardien de panier (affectation)',
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-distrib-gardieneur-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Garde de panier de %%amapien%% à %%post:title%%',
										),
										array(
											'id'      => 'inscr-distrib-gardieneur-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\n%%amapien%% (%%amapien_contact%%) vous a attribué la garde de ses paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [
													'amapien'          => 'Nom de l\'amapien demandeur de garde de son panier',
													'amapien_contacts' => 'Coordonnées de l\'amapien demandeur de garde de son panier',
													'gardien'          => 'Nom du gardien de panier choisi',
													'gardien_contact'  => 'Coordonnées du gardien de panier choisi',
													'gardien_comment'  => 'Message/commentaire du gardien de panier choisi',
												], false ),
										),
										array(
											'name' => 'Email au gardien de panier (désaffectation)',
											'type' => 'heading',
										),
										array(
											'id'       => 'desinscr-distrib-gardieneur-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Désaffectation garde de panier de %%amapien%% à %%post:title%%',
										),
										array(
											'id'      => 'desinscr-distrib-gardieneur-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\n%%amapien%% vous a désattribué la garde de ses paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [
													'amapien'          => 'Nom de l\'amapien demandeur de garde de son panier',
													'amapien_contacts' => 'Coordonnées de l\'amapien demandeur de garde de son panier',
													'gardien'          => 'Nom du gardien de panier choisi',
													'gardien_contact'  => 'Coordonnées du gardien de panier choisi',
													'gardien_comment'  => 'Message/commentaire du gardien de panier choisi',
												], false ),
										),
										array(
											'name' => 'Email au gardien de panier (inscription)',
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-distrib-gardien-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Inscription gardien de panier de %%post:title%%',
										),
										array(
											'id'      => 'inscr-distrib-gardien-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre inscription en tant que gardien de panier de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [], false ),
										),
										array(
											'name' => 'Email au gardien de panier (désinscription)',
											'type' => 'heading',
										),
										array(
											'id'       => 'desinscr-distrib-gardien-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Désinscription gardien de panier de %%post:title%%',
										),
										array(
											'id'      => 'desinscr-distrib-gardien-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre désinscription en tant que gardien de panier de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									],
								),
								'Créneaux de distributions'              => array(
									'id'      => 'amp_tab_distrib_slots',
									'desc'    => '',
									'options' => [
										array(
											'id'      => 'inscr-distribution-slot-close',
											'type'    => 'number',
											'step'    => 1,
											'default' => 24,
											'name'    => 'Fermer l\'inscription aux créneaux de distributions X heures avant la distribution',
										),
										array(
											'name' => 'Email à l\'amapien choisissant un créneau',
											'type' => 'heading',
										),
										array(
											'id'      => 'inscr-distribution-slot-send',
											'name'    => 'Envoyer',
											'type'    => 'checkbox',
											'desc'    => 'Envoyer l\'email ci-dessous',
											'default' => true,
										),
										array(
											'id'       => 'inscr-distribution-slot-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Choix du créneau %%creneau%% à %%post:title%%',
										),
										array(
											'id'      => 'inscr-distribution-slot-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVous avez choisi le créneau %%creneau%% pour la récupération de vos paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [
													'creneau'            => 'Créneau choisi',
													'creneau_date_heure' => 'Date et heure du créneau choisi'
												], false ),
										),
										array(
											'name' => 'Email à l\'amapien affecté à un créneau',
											'type' => 'heading',
										),
										array(
											'id'      => 'inscr-distribution-admin-slot-send',
											'name'    => 'Envoyer',
											'type'    => 'checkbox',
											'desc'    => 'Envoyer l\'email ci-dessous',
											'default' => true,
										),
										array(
											'id'       => 'inscr-distribution-admin-slot-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Affectation du créneau %%creneau%% à %%post:title%%',
										),
										array(
											'id'      => 'inscr-distribution-admin-slot-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nUn responsable '%%responsable%%' vous a affecté le créneau %%creneau%% pour la récupération de vos paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressDistribution::getPlaceholdersHelp( [
													'creneau'            => 'Créneau choisi',
													'creneau_date_heure' => 'Date et heure du créneau choisi',
													'responsable'        => 'Nom et coordonnées du responsable ayant fait l\'affectation du créneau'
												], false ),
										),
										array(
											'type' => 'save',
										),
									],
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_distribs_tools_page',
							'settings' => array(
								'name'       => 'Outils',
								'menu_title' => 'Outils',
								'capability' => 'edit_distribution',
								'menu_icon'  => 'dashicons-admin-tools',
							),
							'options'  => array(),
							'tabs'     => array(
								'Distributions - Définir horaires particuliers' => array(
									'id'      => 'amp_tab_distrib_hours_setter',
									'desc'    => '',
									'options' => [
										array(
											'id'     => 'distrib-hours-setter',
											'bare'   => true,
//									'name'                => 'Rappel 1',
//									'desc'                => 'Inscription à une visite',
											'type'   => 'custom',
											'custom' => 'amapress_distribution_hours_setter',
										),
									],
								),
							),
						),
						array(
							'title'      => 'Lieux de distributions',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Lieux de distributions',
							'post_type'  => 'amps_lieu',
							'capability' => 'edit_lieu_distribution',
							'slug'       => 'edit.php?post_type=amps_lieu',
						),
					),
				),
				array(
					'id'       => 'amapress_gestion_contenu_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Contenus',
						'position'   => '32',
						'capability' => 'manage_contenu',
						'icon'       => 'dashicons-none flaticon-water',
						'long_desc'  => '<div class="amapress-section-note"><h4>Dans cette section, vous pouvez :</h4>
<ul style="margin-left: 1em; list-style-type: disc">' .
						                ( amapress_current_user_can( 'edit_recette' ) ? '<li>publier des <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_recette' ) . '">recettes</a> (<a target="_blank" href="https://wiki.amapress.fr/collectif/recette_publier">Aide</a>) et définir leurs <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=amps_recette_category' ) . '">étiquettes</a></li>' : '' ) .
						                ( amapress_current_user_can( 'edit_produit' ) ? ( '<li>éditer les <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_produit' ) . '">produits de vos différents producteurs</a>, par exemples les espèces de légumes cultivés, les races de poules, les types de produits ainsi que leur associer des <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=amps_produit_category' ) . '">étiquettes</a>. (Le <a target="_blank" href="https://wiki.amapress.fr/contrats/creation">renseignement des types et tailles de paniers</a> de fait au sein des <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ) . '">contrats</a>)</li>' ) : '' ) .
						                '</ul></div>'
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => 'Recettes',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Recettes',
							'post_type'  => 'amps_recette',
							'capability' => 'edit_recette',
							'slug'       => 'edit.php?post_type=amps_recette',
						),
						array(
							'title'      => 'Catégories de recettes',
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => 'Catégories de recettes',
							'capability' => 'edit_recette',
							'post_type'  => 'amps_recette_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_recette_category',
						),
						array(
							'title'      => 'Produits',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Produits',
							'post_type'  => 'amps_produit',
							'capability' => 'edit_produit',
							'slug'       => 'edit.php?post_type=amps_produit',
						),
						array(
							'title'      => 'Catégories de produit',
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => 'Catégories de produits',
							'capability' => 'edit_produit',
							'post_type'  => 'amps_produit_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_produit_category',
						),
//						array(
//							'title'      => 'News',
//							'menu_icon'  => 'post_type',
//							'menu_title' => 'News',
//							'post_type'  => 'amps_news',
//							'capability' => 'edit_news',
//							'slug'       => 'edit.php?post_type=amps_news',
//						),

					),
				),
				array(
					'id'       => 'amapress_gestion_events_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Évènements',
						'position'   => '31',
						'capability' => 'manage_events',
						'icon'       => 'dashicons-none flaticon-interface-2',
						'long_desc'  => '<div class="amapress-section-note"><h4>Dans cette section, vous pouvez :</h4>
<ul style="margin-left: 1em; list-style-type: disc">
<li>planifier et publier des <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_visite&amapress_date=next' ) . '">Visites à la Ferme</a></li>
<li>planifier et publier des <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_assemblee&amapress_date=next' ) . '">Assemblées Générales</a></li>
<li>planifier et publier des <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_amap_event&amapress_date=next' ) . '">événements</a> de tous <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=amps_amap_event_category' ) . '">types</a></li>
</ul></div>'
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => 'Visites à la ferme',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Visites à la ferme',
							'post_type'  => 'amps_visite',
							'capability' => 'edit_visite',
							'slug'       => 'edit.php?post_type=amps_visite&amapress_date=next',
						),
						array(
							'title'      => 'Assemblées',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Assemblées',
							'post_type'  => 'amps_assemblee',
							'capability' => 'edit_assemblee_generale',
							'slug'       => 'edit.php?post_type=amps_assemblee&amapress_date=next',
						),
						array(
							'title'      => 'Evènement',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Evènements',
							'post_type'  => 'amps_amap_event',
							'capability' => 'edit_amap_event',
							'slug'       => 'edit.php?post_type=amps_amap_event&amapress_date=next',
						),
						array(
							'title'      => 'Catégories d\'évènements',
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => 'Catégories d\'évènements',
							'capability' => 'edit_amap_event',
							'post_type'  => 'amps_amap_event_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_amap_event_category',
						),
						array(
							'subpage'  => true,
							'id'       => 'event_mails_page',
							'settings' => array(
								'name'       => 'Emails et rappels',
								'menu_title' => 'Emails/Rappels',
//								'position'   => '25.2',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								'Emails - Inscriptions - Evènements (visite, évènement...)'        => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'inscr-event-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Votre inscription à %%post:title%%',
										),
										array(
											'id'      => 'inscr-event-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre inscription à %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Emails - Désinscriptions - Evènements (visite, évènement...)'     => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'desinscr-event-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Désinscription de %%post:title%%',
										),
										array(
											'id'      => 'desinscr-event-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre désinscription de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Emails - Nouveau commentaire - Evènements (visite, évènement...)' => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'comment-event-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Nouveau commentaire pour %%post:title%%',
										),
										array(
											'id'      => 'comment-event-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nUn nouveau commentaire a été ajouté à %%post:titre%% (%%post:lien%%):\n%%commentaire%%\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [
													'commentaire' => 'Contenu du commentaire'
												], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Emails - Visite - Inscription - Rappel'                           => array(
									'id'      => 'amp_tab_recall_visite_inscr',
									'desc'    => '',
									'options' => amapress_visite_inscription_recall_options(),
								),
								'Emails - Visite - Inscription possible - Rappel'                  => array(
									'id'      => 'amp_tab_recall_visite_avail',
									'desc'    => '',
									'options' => amapress_visite_available_recall_options(),
								),
								'Emails - Evènement AMAP - Inscription - Rappel'                   => array(
									'id'      => 'amp_tab_recall_amap_event_inscr',
									'desc'    => '',
									'options' => amapress_amap_event_inscription_recall_options(),
								),
								'Emails - Evènement AMAP - Inscription possible - Rappel'          => array(
									'id'      => 'amp_tab_recall_amap_event_avail',
									'desc'    => '',
									'options' => amapress_amap_event_available_recall_options(),
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_events_conf_opt_page',
							'settings' => array(
								'name'       => 'Configuration',
								'menu_title' => 'Configuration',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								'Inscription visites'    => array(
									'id'      => 'amp_inscr_visite_options_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'close-subscribe-visite-hours',
											'name'    => 'Clôture inscriptions',
											'desc'    => 'Clôturer les inscriptions x heures avant la visite',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'id'      => 'close-unsubscribe-visite-hours',
											'name'    => 'Clôture désinscriptions',
											'desc'    => 'Clôturer les désinscriptions x heures avant la visite',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Créneaux de visites'    => array(
									'id'      => 'amp_tab_visite_slots',
									'desc'    => '',
									'options' => [
										array(
											'name' => 'Email à l\'amapien choisissant un créneau',
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-visite-slot-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Votre inscription de %%creneau%% à %%post:title%%',
										),
										array(
											'id'      => 'inscr-visite-slot-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVous êtes inscrit pour %%creneau%% à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%" ),
											'desc'    =>
												Amapress_EventBase::getPlaceholdersHelp( [
													'creneau'            => 'Créneau choisi',
													'creneau_date_heure' => 'Date et heure du créneau choisi'
												], false ),
										),
										array(
											'type' => 'save',
										),
									],
								),
								'Inscription évènements' => array(
									'id'      => 'amp_inscr_event_options_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'close-subscribe-event-hours',
											'name'    => 'Clôture inscriptions',
											'desc'    => 'Clôturer les inscriptions x heures avant l\'évènement',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'id'      => 'close-unsubscribe-event-hours',
											'name'    => 'Clôture désinscriptions',
											'desc'    => 'Clôturer les désinscriptions x heures avant l\'évènement',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Inscription assemblées' => array(
									'id'      => 'amp_inscr_ag_options_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'close-subscribe-assemblee-hours',
											'name'    => 'Clôture inscriptions',
											'desc'    => 'Clôturer les inscriptions x heures avant l\'AG',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'id'      => 'close-unsubscribe-assemblee-hours',
											'name'    => 'Clôture désinscriptions',
											'desc'    => 'Clôturer les désinscriptions x heures avant l\'assemblee',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'type' => 'save',
										),
									),
								),
							),
						),
					),
				),
//                array(
//                    'id' => 'amapress_gestion_contrats_page',
//                    'type' => 'panel',
//                    'settings' => array(
//                        'name' => 'Contrats &amp; Distributions',
//                        'position' => '25.6',
//                        'capability' => 'manage_contrats',
//                        'icon' => 'dashicons-none flaticon-tool',
//                    ),
//                    'options' => array(
//                        array(
//                            'type' => 'note',
//                            'desc' => 'ici vous pouvez gérer...'
//                        ),
//                    ),
//                    'tabs' => array(
//                        'Contrats' => array(
//                            'desc' => '',
//                            'options' => array(
//                                array(
//                                    'type' => 'note',
//                                    'desc' => 'ici vous pouvez gérer...'
//                                ),
//                                array(
//                                    'type' => 'save',
//                                ),
//                            )
//                        ),
//                    ),
//                    'subpages' => array(
//                    ),
//                ),
				array(
					'id'       => 'amapress_gestion_amapiens_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Gestion Contrats',
						'position'   => '30',
						'capability' => 'edit_contrat',
						'icon'       => 'dashicons-none flaticon-pen',
						'long_desc'  => '<div class="amapress-section-note"><h4>Dans cette section, vous pouvez :</h4>
<ul style="margin-left: 1em; list-style-type: disc">
<li>gérer tous vos <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ) . '">contrats</a> (Aide <a target="_blank" href="https://wiki.amapress.fr/contrats/creation">Création</a> et <a target="_blank" href="https://wiki.amapress.fr/contrats/gestion">Gestion</a>), leurs <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active' ) . '">inscriptions</a>, les <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_contrat' ) . '">présentations des productions</a> (présentation des contrats), les <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_cont_pmt&amapress_date=active' ) . '">règlements</a></li>
<li>obtenir des <a target="_blank" href="' . admin_url( 'admin.php?page=contrats_quantites_stats' ) . '">statistiques</a> sur les inscriptions, la <a target="_blank" href="' . admin_url( 'admin.php?page=calendar_contrat_paiements' ) . '">répartition des règlements</a>, la <a target="_blank" href="' . admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) . '">répartition des paniers</a> et des <a target="_blank" href="' . admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) . '">quantités à livrer</a> par les prodcuteurs</li> 
<li>gérer <a target="_blank" href="' . admin_url( 'admin.php?page=contrats_archives' ) . '">l\'archivage des saisons précédentes</a></li>
<li>configurer les <a target="_blank" href="' . admin_url( 'admin.php?page=contrats_mails_page' ) . '">emails de rappels</a></li>
<li>configurer <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ) . '">l\'assistant de préinscription en ligne</a> et obtenir un <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) . '">modèle de contrat Word/DOCX générique</a> pour le préremplissage automatique des contrats lors des inscriptions.</li>' .
						                ( amapress_current_user_can( 'edit_producteur' ) ? ( '<li>les <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_producteur' ) . '">producteurs</a> : la présentation des exploitations de vos différents producteurs ainsi que <a href="' . admin_url( 'users.php?page=amapress_collectif&tab=amapress_edit_ref_prods' ) . '" target="_blank">l\'affectation de leurs référents</a>. Vous pouvez également affecter des référents sur les contrats de vos producteurs (<a target="_blank" href="https://wiki.amapress.fr/referent_producteur/fiche_producteur">Voir l\'aide</a>)</li>' ) : '' ) .
						                '</ul>
<p>Consultez l\'aide <a target="_blank" href="https://wiki.amapress.fr/referent_producteur/accueil">Référent Producteur</a></p></div>'
					),
					'options'  => array(
//						array(
//							'type' => 'note',
//							'bare' => true,
//							'desc' => ''
//						)
					),
					'tabs'     => array(
						'Ajouter Inscription Contrat '   => array(
							'id'         => 'add_inscription',
							'desc'       => '',
							'use_form'   => false,
							'use_table'  => false,
							'capability' => 'edit_contrat_instance',
							'options'    => array(
								array(
									'id'         => 'add_user_inscr',
									'type'       => 'custom',
									'bare'       => true,
									'capability' => 'edit_contrat_instance',
									'custom'     => 'amapress_create_user_and_adhesion_assistant',
								)
							),
						),
						'Ajouter un coadhérent'          => array(
							'id'         => 'add_coadherent',
							'desc'       => '',
							'use_form'   => false,
							'use_table'  => false,
							'capability' => 'edit_contrat_instance',
							'options'    => array(
								array(
									'id'         => 'add_user_coinscr',
									'type'       => 'custom',
									'bare'       => true,
									'capability' => 'edit_contrat_instance',
									'custom'     => 'amapress_create_coadhesion_assistant',
								)
							),
						),
						'Ajouter une personne hors AMAP' => array(
							'id'         => 'add_other_user',
							'desc'       => '',
							'use_form'   => false,
							'use_table'  => false,
							'capability' => 'edit_contrat_instance',
							'options'    => array(
								array(
									'id'         => 'add_user_other',
									'type'       => 'custom',
									'bare'       => true,
									'capability' => 'edit_contrat_instance',
									'custom'     => 'amapress_create_user_for_distribution',
								)
							),
						),
					),
					'subpages' => array(
						array(
							'title'      => 'Etat d\'encaissement des contrats',
							'menu_icon'  => 'dashicons-none flaticon-business',
							'menu_title' => 'Synthèse',
							'capability' => 'edit_contrat_paiement',
							'post_type'  => 'contrat_paiements',
							'slug'       => 'contrat_paiements',
							'function'   => 'amapress_render_contrat_paiements_list',
							'hook'       => 'amapress_contrat_paiements_list_options',
						),
						array(
							'title'      => 'Inscriptions aux contrats',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Inscriptions',
							'post_type'  => 'amps_adhesion',
							'capability' => 'edit_adhesion',
							'slug'       => 'edit.php?post_type=amps_adhesion&amapress_date=active',
//                            'description' => 'description',
						),
						array(
							'title'      => 'Encaissements des contrats',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Règlements',
							'post_type'  => 'amps_cont_pmt',
							'capability' => 'edit_contrat_paiement',
							'slug'       => 'edit.php?post_type=amps_cont_pmt&amapress_date=active',
						),
						array(
							'title'      => 'Présentations des contrats',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Productions',
							'post_type'  => 'amps_contrat',
							'capability' => 'edit_contrat',
							'slug'       => 'edit.php?post_type=amps_contrat',
						),
						array(
							'title'      => 'Contrats Annuels',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Edition',
							'post_type'  => 'amps_contrat_inst',
							'capability' => 'edit_contrat_instance',
							'slug'       => 'edit.php?post_type=amps_contrat_inst&amapress_date=active',
						),
						array(
							'subpage'  => true,
							'id'       => 'contrats_quantites_next_distrib',
							'settings' => array(
								'name'       => 'Quantités à la prochaine distribution',
								'menu_title' => 'Quantités',
								'capability' => 'edit_contrat',
								'menu_icon'  => 'dashicons-chart-pie',
							),
							'options'  => array(),
							'tabs'     => function () {
								$tabs              = array();
								$contrat_instances = AmapressContrats::get_active_contrat_instances();
								usort( $contrat_instances, function ( $a, $b ) {
									/** @var AmapressContrat_instance $a */
									/** @var AmapressContrat_instance $b */
									if ( $a->getDate_debut() == $b->getDate_debut() ) {
										return strcmp( $a->getTitle(), $b->getTitle() );
									} else {
										return $a->getDate_debut() < $b->getDate_debut() ? - 1 : 1;
									}
								} );
								foreach ( $contrat_instances as $contrat_instance ) {
									$contrat_id                            = $contrat_instance->ID;
									$tabs[ $contrat_instance->getTitle() ] = array(
										'id'      => 'contrat-quant-tab-' . $contrat_id,
										'desc'    => '',
										'options' => array(
											array(
												'id'     => 'contrat-quant-summary-' . $contrat_id,
												'bare'   => true,
												'type'   => 'custom',
												'custom' => function () use ( $contrat_id ) {
													$date = null;
													if ( isset( $_GET['date'] ) ) {
														if ( 'first' != $_GET['date'] ) {
															$date = DateTime::createFromFormat( 'Y-m-d', $_GET['date'] );
															if ( $date ) {
																$date = $date->getTimestamp();
															} else {
																$date = null;
															}
														} else {
															$date = 'first';
														}
													}

													$is_all = isset( $_GET['all'] );

//													if ( isset( $_GET['columns'] ) ) {
//														return amapress_get_contrat_column_quantite_datatables(
//															$contrat_id, $date );
//													}

													return amapress_get_contrat_quantite_datatable( $contrat_id, null, $date, [
														'show_all_dates'       => $is_all,
														'show_adherents_count' => ! $is_all,
														'show_empty_lines'     => ! $is_all && ! isset( $_GET['without_empty'] ),
														'show_price'           => isset( $_GET['with_prices'] ),
														'show_adherents'       => isset( $_GET['with_adherent'] ),
														'group_by'             => $is_all && isset( $_GET['by'] ) ? $_GET['by'] : 'none',
														'group_by_group'       => ! isset( $_GET['grp_by_grp'] ) || 'F' != $_GET['grp_by_grp'],
													] );
												},
											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'subpage'  => true,
							'id'       => 'contrats_quantites_stats',
							'settings' => array(
								'name'       => 'Statistiques des contrats',
								'menu_title' => 'Statistiques',
								'capability' => 'edit_contrat',
								'menu_icon'  => 'dashicons-chart-bar',
							),
							'options'  => array(
								array(
									'id'     => 'contrat_quantite_stats',
									'bare'   => true,
									'type'   => 'custom',
									'custom' => function () {
										/** @var WP_Post[] $contrat_instances */
										$contrat_instances = get_posts(
											[
												'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
												'post_status'    => [ 'publish', 'archived' ],
												'posts_per_page' => - 1,
												'orderby'        => 'title',
												'order'          => 'ASC',
											]
										);
										$options           = [];
										foreach ( $contrat_instances as $contrat_instance ) {
											$options[ strval( $contrat_instance->ID ) ] = $contrat_instance->post_title;
										}

										ob_start();
										echo '<label for="amp_stats_contrat">Obtenir des statistisque pour le contrat suivant :</label>';
										echo '<select id="amp_stats_contrat" name="amp_stats_contrat">';
										tf_parse_select_options( $options, isset( $_REQUEST['amp_stats_contrat'] ) ? [ $_REQUEST['amp_stats_contrat'] ] : null );
										echo '</select>';
										echo '<input type="submit" class="button button-primary" value="Voir les statistiques" />';
										echo '<hr />';

										if ( ! empty( $_REQUEST['amp_stats_contrat'] ) ) {
											$contrat_instance = AmapressContrat_instance::getBy( intval( $_REQUEST['amp_stats_contrat'] ) );
											if ( ! empty( $contrat_instance ) ) {
												echo '<h4>Inscriptions au contrat "' . esc_html( $contrat_instance->getTitle() ) . '"</h4>';
											}

											$stats = $contrat_instance->getInscriptionsStats();
											amapress_echo_datatable( 'amp_contrat_stats_table',
												$stats['columns'], $stats['lines'],
												array(
													'paging'       => false,
													'sorting'      => false,
													'searching'    => true,
													'nowrap'       => false,
													'responsive'   => false,
													'bAutoWidth'   => true,
													'scrollX'      => true,
													'scrollY'      => '350px',
													'fixedColumns' => [ 'leftColumns' => 2 ],
												),
												array(
													Amapress::DATATABLES_EXPORT_EXCEL
												)
											);
										}

										return ob_get_clean();
//									    return amapress_get_datatable()
									}
								),
							),
							'tabs'     => array(),
						),
						array(
							'subpage'  => true,
							'id'       => 'calendar_contrat_paiements',
							'settings' => array(
								'name'       => 'Calendrier des encaissements des contrats',
								'menu_title' => 'Calendrier',
								'capability' => 'edit_contrat_paiement',
								'menu_icon'  => 'dashicons-calendar-alt',
							),
							'options'  => array(),
							'tabs'     => function () {
								if ( ! amapress_is_user_logged_in() ) {
									return [];
								}
								$tabs              = array();
								$contrat_instances = AmapressContrats::get_active_contrat_instances(
									null, Amapress::add_a_month( amapress_time(), - 3 )
								);
								usort( $contrat_instances, function ( $a, $b ) {
									return strcmp( $a->getTitle(), $b->getTitle() );
								} );
								foreach ( $contrat_instances as $contrat_instance ) {
									$contrat_id                            = $contrat_instance->ID;
									$tabs[ $contrat_instance->getTitle() ] = array(
										'id'      => 'contrat-paiement-tab-' . $contrat_id,
										'desc'    => '',
										'options' => array(
											array(
												'id'         => 'contrat-paiement-summary-' . $contrat_id,
												'bare'       => true,
												'type'       => 'custom',
												'contrat_id' => $contrat_id,
												'custom'     => function ( $post_id, $option ) {
													$ret = '';
													foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
														$ret .= amapress_get_paiement_table_by_dates( intval( $option->settings['contrat_id'] ), $lieu_id );
													}

													return $ret;
												},
											),
//											array(
//												'type' => 'note',
//												'desc' => 'ici vous pouvez gérer...'
//											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'subpage'  => true,
							'id'       => 'contrats_finances',
							'settings' => array(
								'name'       => 'Finances',
								'menu_title' => 'Finances',
								'capability' => 'edit_contrat',
								'menu_icon'  => 'dashicons-chart-line',
							),
							'options'  => array(
								array(
									'name'   => amapress__( 'Statistiques financières' ),
									'bare'   => 'true',
									'type'   => 'custom',
									'custom' => 'amapress_producteurs_finances_custom'
								)
							),
							'tabs'     => array()
						),
						array(
							'subpage'  => true,
							'id'       => 'contrats_archives',
							'settings' => array(
								'name'       => 'Archivage des contrats et inscriptions',
								'menu_title' => 'Archivage',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-book',
							),
							'options'  => array(),
							'tabs'     => array(
								'Archivables' => array(
									'id'      => 'contrats_archivables_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'     => 'contrats_archivables',
											'name'   => 'Contrats archivables',
											'type'   => 'custom',
											'custom' => 'amapress_contrat_instance_archivables_view',
										),
									)
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'contrats_mails_page',
							'settings' => array(
								'name'       => 'Emails et rappels',
								'menu_title' => 'Emails/Rappels',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								'Emails - Envoi des quantités à livrer'                => array(
									'id'      => 'amp_tab_recall_quantites_distrib',
									'desc'    => '',
									'options' => amapress_contrat_quantites_recall_options(),
								),
								'Emails - Contrats à renouveler'                       => array(
									'id'      => 'amp_tab_recall_contrat_renew',
									'desc'    => '',
									'options' => amapress_contrat_renew_recall_options(),
								),
								'Emails - Contrats ouverts ou bientôt ouverts'         => array(
									'id'      => 'amp_tab_recall_contrat_open',
									'desc'    => '',
									'options' => amapress_contrat_open_recall_options(),
								),
								'Emails - Contrats bientôt fermés'                     => array(
									'id'      => 'amp_tab_recall_contrat_close',
									'desc'    => '',
									'options' => amapress_contrat_close_recall_options(),
								),
								'Emails - Récapitulatif à la clôture des inscriptions' => array(
									'id'      => 'amp_tab_recall_contrat_recap_close',
									'desc'    => '',
									'options' => amapress_contrat_recap_cloture_recall_options(),
								),
								'Emails - Inscriptions à valider'                      => array(
									'id'      => 'amp_tab_inscr_validate_distrib',
									'desc'    => '',
									'options' => amapress_inscriptions_to_validate_recall_options(),
								),
								'Emails - Envoi liste des règlements'                  => array(
									'id'      => 'amp_tab_recall_liste_cheques',
									'desc'    => '',
									'options' => amapress_contrat_paiements_recall_options(),
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_gest_contrat_conf_opt_page',
							'settings' => array(
								'name'       => 'Configuration',
								'menu_title' => 'Configuration',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								'Contrats'                                  => array(
									'id'      => 'contrat_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'disable_principal',
											'name'    => 'AMAP sans contrat obligatoire/principal',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'allow_partial_coadh',
											'name'    => 'Autoriser la co-adhésion partielle',
											'type'    => 'checkbox',
											'default' => false,
											'desc'    => 'L\'amapien peut choisir les contrats sur lesquels il souhaite un co-adhérent',
										),
										array(
											'id'      => 'check_adh_rcv',
											'name'    => 'L\'adhésion doit avoir été validée avant de pouvoir s\'inscrire aux contrats',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'check_adh_rcv_p',
											'name'    => 'L\'adhésion ou une adhésion précédente doit avoir été validée avant de pouvoir s\'inscrire aux contrats',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'renouv_days',
											'name'    => 'Durée en jour de la période de renouvellement',
											'type'    => 'number',
											'default' => 30,
											'min'     => 1,
											'unit'    => 'jours',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'type' => 'note',
											'desc' => 'Le renouvellement des contrats se fait dans ' . Amapress::makeLink( admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ), 'Tableau de bord>Gestion Contrats> Edition' )
										),
										array(
											'id'      => 'archive_months',
											'name'    => 'Délai d\'archivage minimum',
											'type'    => 'number',
											'unit'    => 'mois',
											'default' => 3,
											'min'     => 1,
											'desc'    => 'Délai en mois après la fin d\'un contrat après lequel l\'archivage devient possible',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Assistant - Inscription en ligne - Etapes' => array(
									'id'      => 'config_online_inscriptions_messages',
									'desc'    => 'Configuration de l\'assistant d\'inscription en ligne (inscription-en-ligne-connecte/inscription-en-ligne).<br/>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), 'Aller à la configuration de l\'adhésion' ),
									'options' => [
										array(
											'id'      => 'online_subscription_welcome_inscr_message',
											'name'    => 'Message de bienvenue',
											'type'    => 'text',
											'default' => '',
											'desc'    => 'Message de bienvenue (non connecté) (shortcode [inscription-en-ligne]), par défaut, "Bienvenue dans l\'assistant d\'inscription aux contrats producteurs de « AMAP »"',
										),
										array(
											'type' => 'heading',
											'name' => 'Message aux adhérents principaux',
										),
										array(
											'id'      => 'online_principal_user_message',
											'name'    => 'Message',
											'type'    => 'editor',
											'default' => '',
											'desc'    => 'Message aux adhérents principaux sur les Etapes 2/Coordonnées et 4/Contrats',
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Message aux co-adhérents',
										),
										array(
											'id'      => 'online_coadh_user_message',
											'name'    => 'Message',
											'type'    => 'editor',
											'default' => '',
											'desc'    => 'Message aux co-adhérents sur les Etapes 2/Coordonnées et 4/Contrats',
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Message - Etape 4/8 - Les contrats',
										),
										array(
											'id'      => 'online_contrats_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => '',
											'desc'    => '<strong>Nom de remplacement</strong> de l\'étape 4/8 - Les contrats ; par défaut, "Les contrats" ou "Les commandes" suivant l\'argument use_contrat_term',
										),
										array(
											'id'      => 'online_contrats_step_message',
											'name'    => 'Message',
											'type'    => 'editor',
											'default' => '',
											'desc'    => 'Message supplémentaire à l\'étape 4/8 - Les contrats<br/>' .
											             Amapress::getPlaceholdersHelpTable( 'online_contrats_step_message-placeholders', [], null, [], false ),
										),
										array(
											'id'      => 'online_inscr_adhesion_required_message',
											'name'    => 'Message',
											'type'    => 'editor',
											'default' => wpautop( 'Votre adhésion doit être validée avant que vous puissiez vous inscrire aux contrats.' ),
											'desc'    => 'Message supplémentaire à l\'étape 4/8 - Les contrats <strong>si une adhésion validée ou antérieure est nécessaire pour pouvoir s\'inscrire aux contrats</strong><br/>' .
											             Amapress::getPlaceholdersHelpTable( 'online_inscr_adhesion_required_message-placeholders', [], null, [], false ),
										),
										array(
											'id'      => 'online_inscr_closed_message',
											'name'    => 'Message',
											'type'    => 'editor',
											'default' => '',
											'desc'    => 'Message supplémentaire lorsque les inscriptions en ligne sont closes (toutes les étapes)',
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape 7/8 - Date et lieu',
										),
										array(
											'id'      => 'online_subscription_date_lieu_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Date et lieu',
										),
										array(
											'id'   => 'online_subscription_date_lieu_step_message',
											'name' => 'Message supplémentaire',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_date_lieu_step_message-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape 6/8 - Panier',
										),
										array(
											'id'      => 'online_subscription_panier_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Panier',
										),
										array(
											'id'   => 'online_subscription_panier_step_message',
											'name' => 'Message supplémentaire',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_panier_step_message-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape 7/8 - Règlement',
										),
										array(
											'id'      => 'online_subscription_pay_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Règlement',
										),
										array(
											'id'   => 'online_subscription_pay_step_message',
											'name' => 'Message supplémentaire',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_pay_step_message-placeholders', [], null, [], false ),
										),
										array(
											'id'      => 'online_subscription_pay_num_label',
											'name'    => 'Saisie numéros de chèques',
											'type'    => 'text',
											'desc'    => 'Intitulé des champs de saisie des numéros de chèques',
											'default' => 'Numéro chèque',
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Message - Etape 8/8 Félicitations',
										),
										array(
											'id'      => 'online_contrats_end_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Félicitations !',
										),
										array(
											'id'      => 'online_contrats_end_confirm_msg',
											'name'    => 'Message de confirmation',
											'type'    => 'editor',
											'default' => wpautop( 'Votre pré-inscription a bien été prise en compte.' ),
											'desc'    => Amapress::getPlaceholdersHelpTable( 'online_contrats_end_confirm_msg-placeholders', [], null, [], false ),
										),
										array(
											'id'      => 'online_contrats_end_continue_msg',
											'name'    => 'Message de confirmation',
											'type'    => 'editor',
											'default' => wpautop( 'Vous pouvez également découvrir et éventuellement adhérer aux contrats suivants (%%remaining_contrats%%)' ),
											'desc'    => Amapress::getPlaceholdersHelpTable( 'online_contrats_end_continue_msg-placeholders', [
												'remaining_contrats'      => 'Contrat disponibles à l\'inscription',
												'remaining_contrats_list' => 'Contrat disponibles à l\'inscription (en liste)'
											], null, [], false ),
										),
										array(
											'id'      => 'online_contrats_end_confirm_mail_msg',
											'name'    => 'Message au sujet de l\'email de confirmation',
											'type'    => 'editor',
											'default' => wpautop( 'Vous allez recevoir un email de confirmation avec votre contrat dans quelques minutes. (Pensez à regarder vos spams, cet email peut s\'y trouver à cause du contrat joint ou pour expéditeur inconnu de votre carnet d\'adresses)' ),
											'desc'    => Amapress::getPlaceholdersHelpTable( 'online_contrats_end_confirm_mail_msg-placeholders', [], null, [], false ),
										),
										array(
											'id'      => 'online_contrats_end_step_message',
											'name'    => 'Inscription terminée',
											'type'    => 'editor',
											'default' => wpautop( "Pour finaliser votre inscription, vous devez imprimer ce contrat et le remettre aux référents concernés (%%tous_referents%%) avec les chèques/règlements correspondants lors de la prochaine distribution\n%%print_button%%" ),
											'desc'    => 'Instruction en fin d\'inscription à l\'étape 8/8 pour chaque inscription<br/>Le placeholder %%print_button%% permet d\'afficher le bouton Imprimer le contrat<br/>' .
											             Amapress::getPlaceholdersHelpTable( 'online_contrats_end_step_message-placeholders', [
												             'print_button' => 'Bouton Imprimer/Télécharger le contrat'
											             ], null, [], false ),
										),
										array(
											'id'      => 'online_contrats_end_step_edit_message',
											'name'    => 'Inscription terminée - Possibilité édition/annulation',
											'type'    => 'editor',
											'default' => wpautop( "Veuillez vérifier le contrat : %%print_button%%\nSi vous constatez une erreur, vous pouvez modifier votre inscription : %%modify_button%%\nVous pouvez également l'annuler : %%cancel_button%%" ),
											'desc'    => 'Instruction pour l\'édition ou l\'annulation à la fin d\'inscription à l\'étape 8/8 pour chaque inscription<br/>Les placeholders %%print_button%%, %%modify_button%%, %%cancel_button%% permettent d\'afficher respectivement le bouton Imprimer/Télécharger, Modifier et Annuler l\inscription<br/>' .
											             Amapress::getPlaceholdersHelpTable( 'online_contrats_end_step_edit_message-placeholders', [
												             'print_button'  => 'Bouton Imprimer/Télécharger le contrat',
												             'modify_button' => 'Bouton Modifier l\'inscription',
												             'cancel_button' => 'Bouton Annuler l\'inscription'
											             ], null, [], false ),
										),
										array(
											'id'      => 'online_contrats_inscription_distrib_msg',
											'name'    => 'Message inscription aux distributions',
											'type'    => 'editor',
											'default' => '',
											'desc'    => 'Message au sujet des inscriptions nécessaires en tant que responsable de distribution<br/>' .
											             Amapress::getPlaceholdersHelpTable( 'online_contrats_inscription_distrib_msg-placeholders', [
												             'nb_inscriptions'    => 'Nombre d\'inscription comme responsable de distribution sur la période à venir',
												             'dates_inscriptions' => 'Dates d\'inscription comme responsable de distribution sur la période à venir'
											             ], null, [], false ),
										),
										array(
											'id'      => 'online_final_step_name',
											'name'    => 'Nom de l\'étape finale',
											'type'    => 'text',
											'default' => 'Félicitations, vous avez terminé vos inscriptions !',
										),
										array(
											'id'      => 'online_final_step_message',
											'name'    => 'Message final',
											'type'    => 'editor',
											'default' => wpautop( "Si vous êtes nouvel adhérent vous allez recevoir un email vous indiquant comment vous connecter au site et choisir votre mot de passe.\n
Vous allez recevoir un email de confirmation pour chacune de vos inscriptions avec le contrat à imprimer et les instructions pour remettre vos chèques/règlements aux référents.\n
(Pensez à regarder vos spams, ces emails peuvent s\'y trouver à cause des contrats joints ou pour expéditeur inconnu de votre carnet d\'adresses)\n
Vous pouvez maintenant fermer cette fenêtre/onglet et regarder votre messagerie" ),
											'desc'    => 'Message à l\'amapien à la fin de toutes ses inscriptions',
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'CSS Personnalisé',
										),
										array(
											'id'      => 'online_inscr_css',
											'name'    => 'CSS',
											'type'    => 'textarea',
											'default' => '',
											'desc'    => 'CSS additionnel (hors shortcode mes-contrats), par exemple, pour masquer les entêtes et menu',
										),
										array(
											'type' => 'save',
										),
									]
								),
								'Assistant - Inscription en ligne - Emails' => array(
									'id'      => 'config_online_inscriptions_mails',
									'desc'    => 'Configuration des emails de l\'assistant d\'inscription en ligne (inscription-en-ligne-connecte/inscription-en-ligne).<br/>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_mails' ), 'Aller à la configuration de l\'adhésion' ),
									'options' => [
										array(
											'type' => 'heading',
											'name' => 'Emails - Confirmation Inscription Contrat',
										),
										array(
											'id'       => 'online_subscription_confirm-mail-subject',
											'name'     => 'Objet',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Confirmation de votre inscription au contrat %%contrat_titre_complet%% à partir du %%date_debut_complete%%',
										),
										array(
											'id'      => 'online_subscription_confirm-mail-content',
											'name'    => 'Contenu',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour %%user:nom_complet%%,\nNous vous confirmons votre inscription au contrat %%contrat_titre%% 
									\n-> du %%date_debut_complete%% au %%date_fin_complete%% 
									\n-> pour %%nb_distributions%% distributions
									\n-> quantités : %%quantites%%
									\n-> pour un montant de %%total%%€
									\n[avec_contrat]Merci d'imprimer le contrat joint à cet email et le remettre aux référents (%%referents%%) avec %%option_paiements%% à la première distribution[/avec_contrat]
									[sans_contrat]Merci de contacter les référents (%%referents%%) avec %%option_paiements%% à la première distribution pour signer votre contrat[/sans_contrat]
									\n\n%%nom_site%%" ),
											'desc'    => 'Les syntaxes [avec_contrat]xxx[/avec_contrat] et [sans_contrat]xxx[/sans_contrat] permettent de cibler le texte respectivement lorsqu\'un contrat Word est attaché ou non.<br />Les placeholders suivants sont disponibles:' .
											             AmapressAdhesion::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Emails - Notification Référents Nouvelle Inscription Contrat',
										),
										array(
											'id'       => 'online_subscription_referents-mail-subject',
											'name'     => 'Objet',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Nouvelle inscription - %%contrat_titre_complet%% - %%adherent%%',
										),
										array(
											'id'      => 'online_subscription_referents-mail-content',
											'name'    => 'Contenu',
											'type'    => 'editor',
											'default' => wpautop(
												"Bonjour,\n\nUne nouvelle inscription est en attente de validation : %%inscription_admin_link%%" .
												"\n-> du %%date_debut_complete%% au %%date_fin_complete%%\n-> pour %%nb_distributions%% distributions\n-> quantités : %%quantites%%\n-> pour un montant de %%total%%€\n" .
												"\nMessage de l'amapien: %%message%%" .
												"\n%%nom_site%%" ),
											'desc'    => 'Les placeholders suivants sont disponibles:' .
											             AmapressAdhesion::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Emails - Notification Référents Modification Inscription Contrat',
										),
										array(
											'id'       => 'online_subscription_referents_modif-mail-subject',
											'name'     => 'Objet',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Inscription modifiée - %%contrat_titre_complet%% - %%adherent%%',
										),
										array(
											'id'      => 'online_subscription_referents_modif-mail-content',
											'name'    => 'Contenu',
											'type'    => 'editor',
											'default' => wpautop(
												"Bonjour,\n\nUne inscription a été modifiée et est en attente de validation : %%inscription_admin_link%%" .
												"\n-> du %%date_debut_complete%% au %%date_fin_complete%%\n-> pour %%nb_distributions%% distributions\n-> quantités : %%quantites%%\n-> pour un montant de %%total%%€\n" .
												"\nMessage de l'amapien: %%message%%" .
												"\n%%nom_site%%" ),
											'desc'    => 'Les placeholders suivants sont disponibles:' .
											             AmapressAdhesion::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Emails - Notification Référents Annulation Inscription Contrat',
										),
										array(
											'id'       => 'online_subscription_referents_cancel-mail-subject',
											'name'     => 'Objet',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Annulation inscription - %%contrat_titre_complet%% - %%adherent%%',
										),
										array(
											'id'      => 'online_subscription_referents_cancel-mail-content',
											'name'    => 'Contenu',
											'type'    => 'editor',
											'default' => wpautop(
												"Bonjour,\n\nUne inscription a été annulée" .
												"\n-> du %%date_debut_complete%% au %%date_fin_complete%%\n-> pour %%nb_distributions%% distributions\n-> quantités : %%quantites%%\n-> pour un montant de %%total%%€\n" .
												"\nMessage de l'amapien: %%message%%" .
												"\n%%nom_site%%" ),
											'desc'    => 'Les placeholders suivants sont disponibles (hormis %%inscription_admin_link%% car l\'inscription est supprimée suite à l\'envoi de ce mail):' .
											             AmapressAdhesion::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									]
								),
								'Contrat Word (DOCX) général'               => array(
									'id'      => 'config_default_contrat_docx',
									'desc'    => '',
									'options' => [
										array(
											'type' => 'note',
											'desc' => '
                                    <h4>Modèles génériques de contrats</h4>
									<p>Vous pouvez télécharger ci-dessous l\'un des modèles DOCX génériques utilisables comme contrat personnalisé et vierge :</p>
                                    <ul style="list-style-type: disc; padding-left: 1em">
                                    <li><a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl() ) . '">modèle générique (paniers en tableau)</a></li>
                                    <li><a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl( 'simple' ) ) . '">modèle générique simple (paniers en texte)</a></li>
                                    <li><a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl( 'modulables' ) ) . '">modèle générique pour les paniers modulables</a></li>
                                    <li><a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl( 'modulables_complet' ) ) . '">modèle générique pour les paniers modulables avec résumé, détails et groupes</a></li>
                                    </ul>
                                    <h4>Configuration des modèles par défaut</h4>
									<p>Vous pouvez configurer les modèles DOCX par défaut pour tous les contrats sans modèle spécifique.</p>
									<p>La procédure est la suivante: <ul style="list-style-type: decimal; padding-left: 1em">
									<li>Téléchargez le <a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl() ) . '">modèle générique</a></li>
									<li>changez le logo d\'entête</li>
									<li>personnalisez les engagements</li>
									<li>uploadez votre fichier DOCX modifié dans les deux champs ci-dessous</li>
									<li>enregistrez</li>
									<li>Si vous avez des contrats avec paniers modulables, recommencez cette procédure avec le <a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl( 'modulables' ) ) . '">modèle générique paniers modulables</a> ou le <a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl( 'modulables_complet' ) ) . '">modèle complet</a></li>
									<li>Si vous avez des contrats avec paniers modulables <em>avec groupes de produits</em>, recommencez cette procédure avec le <a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl( 'modulables_complet' ) ) . '">modèle générique paniers modulables avec groupes</a></li>
									</ul></p>
									<p>Votre AMAP est prête pour la génération/remplissage automatique des contrats</p>',
										),
										array(
											'id'              => 'default_word_model',
											'name'            => amapress__( 'Contrat personnalisé par défaut' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => 'Utiliser ce modèle',
											'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat papier DOCX',
											'desc'            => 'Configurer un modèle de contrat (par défaut pour tous les contrats sans modèle spécifique) à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'id'              => 'default_word_paper_model',
											'name'            => amapress__( 'Contrat vierge par défaut' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => 'Utiliser ce modèle',
											'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat personnalisé DOCX',
											'desc'            => 'Générer un contrat vierge (par défaut pour tous les contrats sans modèle spécifique) à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'type' => 'heading',
											'name' => 'Pour les contrats avec paniers modulables',
										),
										array(
											'id'              => 'default_word_modulable_model',
											'name'            => amapress__( 'Contrat personnalisé "paniers modulables" par défaut' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => 'Utiliser ce modèle',
											'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat papier DOCX',
											'desc'            => 'Configurer un modèle de contrat "paniers modulables" (par défaut pour tous les contrats sans modèle spécifique) à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'id'              => 'default_word_modulable_paper_model',
											'name'            => amapress__( 'Contrat vierge "paniers modulables" par défaut' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => 'Utiliser ce modèle',
											'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat personnalisé DOCX',
											'desc'            => 'Générer un contrat vierge "paniers modulables" (par défaut pour tous les contrats sans modèle spécifique) à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'type' => 'heading',
											'name' => 'Pour les contrats avec paniers modulables avec groupes de produits',
										),
										array(
											'id'              => 'default_word_modulable_group_model',
											'name'            => amapress__( 'Contrat personnalisé "paniers modulables avec groupes" par défaut' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => 'Utiliser ce modèle',
											'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat papier DOCX',
											'desc'            => 'Configurer un modèle de contrat "paniers modulables avec groupes" (par défaut pour tous les contrats avec groupes sans modèle spécifique) à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'id'              => 'default_word_modulable_group_paper_model',
											'name'            => amapress__( 'Contrat vierge "paniers modulables avec groupe" par défaut' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => 'Utiliser ce modèle',
											'selector-title'  => 'Sélectionnez/téléversez un modèle de contrat personnalisé DOCX',
											'desc'            => 'Générer un contrat vierge "paniers modulables avec groupes" (par défaut pour tous les contrats avec groupes sans modèle spécifique) à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'type' => 'save',
										),
									]
								),
							),
						),
						array(
							'title'      => 'Producteurs',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Producteurs',
							'post_type'  => 'amps_producteur',
							'capability' => 'edit_producteur',
							'slug'       => 'edit.php?post_type=amps_producteur',
						),
					),
				),
				array(
					'id'       => 'amapress_gestion_adhesions_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Gestion Adhésions',
						'position'   => '29',
						'capability' => 'edit_adhesion_paiement',
						'icon'       => 'dashicons-none flaticon-pen',
						'long_desc'  => '<div class="amapress-section-note">' . '<h4>Dans cette section vous pouvez gérer les adhésions/cotisation à votre AMAP :</h4>
<ul style="margin-left: 1em; list-style-type: disc">
<li>les <a target="_blank" href="' . admin_url( 'admin.php?page=adhesion_paiements&amapress_contrat=active' ) . '">adhésions</a>, leurs <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_adh_pmt&amapress_date=active' ) . '">règlements</a> et la répartition des types de cotisations
<li>la <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_adh_per&amapress_date=active' ) . '">période d\'adhésion</a> et le bulletin sous format Word/DOCX
<li>les <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ) . '">types de cotisations</a>
</ul>
<p>L\'association des deux <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ) . '">types de cotisation</a> par défaut (AMAP et Réseau AMAP) se fait dans <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=amp_paiements_config' ) . '">Tableau de bord&gt;Gestion Adhésions&gt;Configuration, onglet Paiements</a></p>' . '</div>'
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => 'Etat des règlements Adhésions',
							'menu_icon'  => 'dashicons-none flaticon-business',
							'menu_title' => 'Synthèse',
							'capability' => 'edit_adhesion_paiement',
							'post_type'  => 'adhesion_paiements',
							'slug'       => 'adhesion_paiements',
							'function'   => 'amapress_render_adhesion_list',
							'hook'       => 'amapress_adhesion_list_options',
						),
						array(
							'title'      => 'Encaissements des règlements Adhésions',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Règlements',
							'post_type'  => 'amps_adh_pmt',
							'capability' => 'edit_adhesion_paiement',
							'slug'       => 'edit.php?post_type=amps_adh_pmt&amapress_date=active',
						),
						array(
							'title'      => 'Répartitions bénéficiaires',
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => 'Types de paiement',
							'capability' => 'edit_adhesion_paiement',
							'post_type'  => 'amps_paiement_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_paiement_category',
						),
						array(
							'title'      => 'Périodes Adhésions',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Edition',
							'post_type'  => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
							'capability' => 'edit_adhesion_period',
							'slug'       => 'edit.php?post_type=amps_adh_per&amapress_date=active',
						),
						array(
							'subpage'  => true,
							'id'       => 'adh_period_archives',
							'settings' => array(
								'name'       => 'Archivage des adhésions et périodes d\'adhésion',
								'menu_title' => 'Archivage',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-book',
							),
							'options'  => array(),
							'tabs'     => array(
								'Archivables' => array(
									'id'      => 'adh_period_archivables_tab',
									'desc'    => '',
									'options' => array(
										array(
											'id'     => 'adh_period_archivables',
											'name'   => 'Périodes d\'adhésion archivables',
											'type'   => 'custom',
											'custom' => 'amapress_adhesion_period_archivables_view',
										),
									)
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_gest_adhesions_conf_opt_page',
							'settings' => array(
								'name'       => 'Configuration',
								'menu_title' => 'Configuration',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								'Types de cotisation'                    => array(
									'id'      => 'amp_paiements_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'adhesion_amap_term',
											'name'       => 'Catégorie Adhésion AMAP',
											'taxonomy'   => 'amps_paiement_category',
											'type'       => 'select-categories',
											'capability' => 'edit_contrat_paiement',
										),
										array(
											'id'         => 'adhesion_reseau_amap_term',
											'name'       => 'Catégorie Adhésion Réseau AMAP',
											'taxonomy'   => 'amps_paiement_category',
											'type'       => 'select-categories',
											'capability' => 'edit_contrat_paiement',
										),
//                        array(
//                            'id' => 'adhesion_contrat_term',
//                            'name' => 'Catégorie Adhésion Contrat',
//                            'taxonomy' => 'amps_paiement_category',
//                            'type' => 'select-categories',
//                            'capability' => 'edit_contrat_paiement',
//                        ),
										array(
											'type' => 'save',
										),
									)
								),
								'Assistant - Adhésion en ligne - Etapes' => array(
									'id'      => 'config_online_adhesions_messages',
									'desc'    => 'Configuration de l\'assistant d\'adhésion en ligne (adhesion-en-ligne-connecte/adhesion-en-ligne).<br/>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), 'Aller à la configuration de l\'inscription' ),
									'options' => array(
										array(
											'id'      => 'online_subscription_welcome_adh_message',
											'name'    => 'Message de bienvenue',
											'type'    => 'text',
											'default' => '',
											'desc'    => 'Message de bienvenue (non connecté) (shortcode [adhesion-en-ligne]), par défaut, "Bienvenue dans l\'assistant d\'adhésion de « AMAP »"',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape 1 - Email',
										),
										array(
											'id'      => 'online_subscription_email_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Email',
										),
										array(
											'id'   => 'online_subscription_email_step_message',
											'name' => 'Message supplémentaire',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_email_step_message-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape 2 - Coordonnées, co-adhérents et membres du foyer',
										),
										array(
											'id'      => 'online_subscription_coords_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Coordonnées, co-adhérents et membres du foyer',
										),
										array(
											'id'   => 'online_subscription_coords_step_message',
											'name' => 'Message supplémentaire',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_coords_step_message-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape Règlement intérieur de l\'AMAP',
										),
										array(
											'id'      => 'online_subscription_agreement_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Charte et règlement intérieur de l\'AMAP',
										),
										array(
											'id'      => 'online_subscription_agreement_step_checkbox',
											'name'    => 'Texte de la case à cocher',
											'type'    => 'text',
											'default' => 'J\'ai pris connaissance du règlement et l\'accepte',
										),
										array(
											'id'   => 'online_subscription_agreement',
											'name' => 'Contenu du règlement intérieur et Contenu de la Charte des AMAPS',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_agreement-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Message - Questions aux nouveaux amapiens (Etapes 2 - Coordonnées)',
										),
										array(
											'id'      => 'online_new_user_quest1',
											'name'    => 'Question 1',
											'type'    => 'editor',
											'default' => '',
											'desc'    => 'Question au nouvel amapien (par ex, comment avez-vous connu l\'AMAP)',
										),
										array(
											'id'      => 'online_new_user_quest2',
											'name'    => 'Question 2',
											'type'    => 'editor',
											'default' => '',
											'desc'    => 'Question au nouvel amapien',
										),
										array(
											'id'       => 'online_new_user_quest_email',
											'name'     => 'Réponses à',
											'type'     => 'text',
											'is_email' => true,
											'desc'     => 'Envoyer les réponses à l\'email renseigné',
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Message - Message aux amapiens qui ne renouvelent pas',
										),
										array(
											'id'      => 'online_norenew_message',
											'name'    => 'Message',
											'type'    => 'editor',
											'default' => '<p>Merci pour votre participation à %%site_name%% et bonne continuation.</p>',
											'desc'    => 'Message aux amapiens qui ne renouvelent pas',
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape Adhésion AMAP',
										),
										array(
											'id'      => 'online_subscription_req_adhesion',
											'name'    => 'Message adhésion requise',
											'type'    => 'editor',
											'desc'    => Amapress::getPlaceholdersHelpTable( 'online_subscription_req_adhesion-placeholders', [], null, [], false ),
											'default' => '<p><strong>Pour vous engager dans l’AMAP et pouvoir s\'inscrire aux contrats disponibles, vous devez adhérer à notre Association.</strong></p>',
										),
										array(
											'id'      => 'online_subscription_adh_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Adhésion (obligatoire)',
										),
										array(
											'id'      => 'online_subscription_adh_num_label',
											'name'    => 'Saisie numéros de chèques',
											'type'    => 'text',
											'desc'    => 'Intitulé du champs de saisie du numéro de chèque/virement',
											'default' => 'Numéro de chèque/virement :',
										),
										array(
											'id'      => 'online_subscription_adh_valid_step_name',
											'name'    => 'Nom de l\'étape de validation',
											'type'    => 'text',
											'default' => 'Validation du Bulletin d\'adhésion',
										),
										array(
											'id'      => 'online_subscription_greating_adhesion',
											'name'    => 'Contenu du message de validation',
											'type'    => 'editor',
											'desc'    => 'Le placeholder %%print_button%% permet d\'afficher le bouton Imprimer le bulletin<br/>' . Amapress::getPlaceholdersHelpTable( 'online_subscription_greating_adhesion-placeholders', [
													'print_button' => 'Bouton Imprimer le bulletin'
												], 'de l\'amapien', [], false ),
											'default' => wpautop( "Merci pour votre adhésion à l'AMAP !\nUn courriel de confirmation vient de vous être envoyé. Pensez à consulter les éléments indésirables.\nVeuillez imprimer le bulletin et le remettre avec votre chèque/règlement à l'ordre de l'AMAP lors de la première distribution.\n%%print_button%%" ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Message - Cotisation des co-adhérents',
										),
										array(
											'id'      => 'online_adhesion_coadh_message',
											'name'    => 'Message',
											'type'    => 'editor',
											'default' => wpautop( 'Les co-adhérents qui ne font pas partie du même foyer doivent régler la cotisation de l’adhésion à l\'AMAP par foyer' ),
											'desc'    => 'Message au sujet des adhésions des co-adhérents<br/>' .
											             Amapress::getPlaceholdersHelpTable( 'online_adhesion_coadh_message-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Assistant - Adhésion en ligne - Emails' => array(
									'id'      => 'config_online_adhesions_mails',
									'desc'    => 'Configuration des emails de l\'assistant d\'adhésion en ligne (adhesion-en-ligne-connecte/adhesion-en-ligne).<br/>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_mails' ), 'Aller à la configuration de l\'inscription' ),
									'options' => array(
										array(
											'type' => 'heading',
											'name' => 'Confirmation - Pré-inscription en ligne',
										),
										array(
											'id'       => 'online_adhesion_confirm-mail-subject',
											'name'     => 'Objet',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Confirmation de votre adhésion à %%nom_site%%',
										),
										array(
											'id'      => 'online_adhesion_confirm-mail-content',
											'name'    => 'Contenu',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour %%user:nom_complet%%,\n\n
Nous vous confirmons votre adhésion à %%nom_site%%\n
[avec_bulletin]Merci d'imprimer le bulletin joint à cet email et le remettre aux trésoriers (%%tresoriers%%) avec votre chèque de %%montant%% à la première distribution[/avec_bulletin]
[sans_bulletin]Merci de contacter les trésoriers (%%tresoriers%%) avec votre chèque de %%total%% à la première distribution pour signer votre bulletin[/sans_bulletin]
\n\n%%nom_site%%" ),
											'desc'    => 'Les syntaxes [avec_bulletin]xxx[/avec_bulletin] et [sans_bulletin]xxx[/sans_bulletin] permettent de cibler le texte respectivement lorsqu\'un contrat Word est attaché ou non.<br />Les placeholders suivants sont disponibles:' .
											             AmapressAdhesion_paiement::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									),
								),
							),
						),
					),
				),
				array(
					'id'         => 'amapress_gestion_intermittence_page',
					'type'       => 'panel',
					'capability' => 'edit_intermittence_panier',
					'settings'   => array(
						'name'       => 'Espace intermittents',
						'position'   => '58',
						'capability' => 'edit_intermittence_panier',
						'icon'       => 'dashicons-none flaticon-business-2',
						'long_desc'  => '<div class="amapress-section-note">' . '<h4>Dans cette section vous pouvez :</h4>
<ul style="margin-left: 1em; list-style-type: disc">
<li>configurer les <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_intermit_conf_opt_page' ) . '">options</a> de l\'espace intermittents</li>
<li>voir les <a target="_blank" href="' . admin_url( 'edit.php?post_type=amps_inter_panier&amapress_date=active' ) . '">paniers échangés</a></li>
<li>configurer les <a target="_blank" href="' . admin_url( 'admin.php?page=intermit_mails_page' ) . '">emails de rappels</a> relatifs à l\'espace intermittents</li>
<li>gérer les <a target="_blank" href="' . admin_url( 'users.php?amapress_contrat=intermittent' ) . '">amapiens membres</a> de la liste des intermittents</li>
<li>consulter les <a target="_blank" href="' . admin_url( 'admin.php?page=intermittent_page_stats' ) . '">statistiques d\'échanges de paniers</a></li>
</ul>
<p><a target="_blank" href="https://wiki.amapress.fr/admin/espace_intermittents">Consulter l\'aide</a></p>' . '</div>'
					),
					'options'    => array(),
					'tabs'       => array(),
					'subpages'   => array(
						array(
							'title'      => 'Intermittents',
							'menu_icon'  => 'dashicons-admin-users',
							'menu_title' => 'Intermittents',
							'capability' => 'edit_users',
							'slug'       => 'users.php?amapress_contrat=intermittent',
						),
						array(
							'title'      => 'Paniers à échanger',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Paniers à échanger',
							'post_type'  => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
							'capability' => 'edit_intermittence_panier',
							'slug'       => 'edit.php?post_type=amps_inter_panier&amapress_date=active',
						),
						array(
							'subpage'  => true,
							'id'       => 'intermittent_page_stats',
							'settings' => array(
								'name'       => 'Statistiques des échanges',
								'menu_title' => 'Statistiques',
								'capability' => 'edit_intermittence_panier',
								'menu_icon'  => 'dashicons-chart-bar',
							),
							'options'  => array(
								array(
									'id'     => 'intermittent_stats',
									'bare'   => true,
									'type'   => 'custom',
									'custom' => function () {
										$start_date_fmt = ! empty( $_REQUEST['amp_stats_start_date'] ) ? $_REQUEST['amp_stats_start_date'] : date_i18n( 'd/m/Y', Amapress::add_a_month( amapress_time(), - 12 ) );
										$end_date_fmt   = ! empty( $_REQUEST['amp_stats_end_date'] ) ? $_REQUEST['amp_stats_end_date'] : date_i18n( 'd/m/Y', amapress_time() );
										ob_start();
										TitanFrameworkOptionDate::createCalendarScript();

										echo '<p>Obtenir des statistisque pour la période suivante :</p>';
										echo '<label class="tf-date" for="amp_stats_start_date">Début: <input id="amp_stats_start_date" class="input-date date required " name="amp_stats_start_date" type="text" value="' . $start_date_fmt . '" /></label>';
										echo '<label class="tf-date" for="amp_stats_end_date">Fin: <input id="amp_stats_end_date" class="input-date date required " name="amp_stats_end_date" type="text" value="' . $end_date_fmt . '" /></label>';
										echo '<input type="submit" class="button button-primary" value="Voir les statistiques" />';
										echo '<hr />';


										echo '<h4>Echanges de paniers du ' . $start_date_fmt . ' au ' . $end_date_fmt . '</h4>';

										$columns          = [];
										$columns[]        = array(
											'title' => 'Amapien',
											'data'  => array(
												'_'    => 'user',
												'sort' => 'sort_user',
											),
										);
										$columns[]        = array(
											'title' => 'Lieu',
											'data'  => 'lieu',
										);
										$columns[]        = array(
											'title' => 'Proposés',
											'data'  => 'exchanged_nb',
										);
										$columns[]        = array(
											'title' => 'Dates échange',
											'data'  => 'exchanged_dates',
										);
										$columns[]        = array(
											'title' => 'Repris',
											'data'  => 'taken_nb',
										);
										$columns[]        = array(
											'title' => 'Dates reprise',
											'data'  => 'taken_dates',
										);
										$user_names       = [];
										$user_sort_names  = [];
										$user_lieux       = [];
										$user_takens      = [];
										$user_exchangeds  = [];
										$month_sort       = [];
										$month_takens     = [];
										$month_exchangeds = [];
										$start_date       = DateTime::createFromFormat( 'd/m/Y', $start_date_fmt )->getTimestamp();
										$end_date         = DateTime::createFromFormat( 'd/m/Y', $end_date_fmt )->getTimestamp();
										foreach ( AmapressIntermittence_panier::get_paniers_intermittents( $start_date, $end_date, 'ASC' ) as $panier ) {
											foreach ( [ $panier->getAdherent(), $panier->getRepreneur() ] as $r ) {
												if ( empty( $r ) ) {
													continue;
												}
												$month = date_i18n( 'm/Y', $panier->getDate() );
												if ( ! isset( $month_takens[ $month ] ) ) {
													$month_takens[ $month ] = 0;
												}
												if ( ! isset( $month_exchangeds[ $month ] ) ) {
													$month_exchangeds[ $month ] = 0;
												}
												if ( ! isset( $month_sort[ $month ] ) ) {
													$month_sort[ $month ] = date_i18n( 'Y-m', $panier->getDate() );
												}
												if ( $r->ID == $panier->getAdherentId() ) {
													$month_exchangeds[ $month ] += 1;
												} else if ( $r->ID == $panier->getRepreneurId() ) {
													$month_takens[ $month ] += 1;
												}

												$rid = strval( $r->ID );
												if ( ! isset( $user_takens[ $rid ] ) ) {
													$user_takens[ $rid ] = [];
												}
												if ( ! isset( $user_exchangeds[ $rid ] ) ) {
													$user_exchangeds[ $rid ] = [];
												}
												if ( $r->ID == $panier->getAdherentId() ) {
													$user_exchangeds[ $rid ][] = Amapress::makeLink( $panier->getAdminEditLink(), date_i18n( 'd/m/Y', $panier->getDate() ) );
												} else if ( $r->ID == $panier->getRepreneurId() ) {
													$user_takens[ $rid ][] = Amapress::makeLink( $panier->getAdminEditLink(), date_i18n( 'd/m/Y', $panier->getDate() ) );
												}
												if ( ! isset( $user_names[ $rid ] ) ) {
													$user_names[ $rid ] = Amapress::makeLink( $r->getEditLink(), $r->getDisplayName() . '(' . $r->getUser()->user_email . ')' );
												}
												if ( ! isset( $user_sort_names[ $rid ] ) ) {
													$user_sort_names[ $rid ] = $r->getSortableDisplayName();
												}
												if ( ! isset( $user_lieux[ $rid ] ) ) {
													$user_lieux[ $rid ] = $panier->getLieu()->getLieuTitle();
												}
											}
										}
										$lines = [];
										foreach ( $user_names as $user_id => $user_name ) {
											$lines[] = array(
												'user'            => $user_name,
												'sort_user'       => $user_sort_names[ $user_id ],
												'lieu'            => $user_lieux[ $user_id ],
												'exchanged_dates' => implode( ', ', $user_exchangeds[ $user_id ] ),
												'exchanged_nb'    => count( $user_exchangeds[ $user_id ] ),
												'taken_dates'     => implode( ', ', $user_takens[ $user_id ] ),
												'taken_nb'        => count( $user_takens[ $user_id ] ),
											);
										}
										amapress_echo_datatable( 'amp_intermit_stats_table',
											$columns, $lines,
											array(
												'paging'       => false,
												'searching'    => true,
												'nowrap'       => false,
												'responsive'   => false,
												'init_as_html' => true,
												'fixedHeader'  => array(
													'headerOffset' => 32
												),
											),
											array(
												Amapress::DATATABLES_EXPORT_EXCEL
											)
										);

										$columns = [
											array(
												'title' => 'Mois',
												'data'  => [
													'_'    => 'month',
													'sort' => 'sort_month',
												],
											),
											array(
												'title' => 'Paniers proposés',
												'data'  => 'exchanged_nb',
											),
											array(
												'title' => 'Paniers repris',
												'data'  => 'taken_nb',
											)
										];
										$lines   = [];
										foreach ( $month_takens as $month => $cnt ) {
											$lines[] = [
												'month'        => Amapress::makeLink( admin_url( 'edit.php?post_type=amps_inter_panier&amapress_date=' . $month_sort[ $month ] ), $month, true, true ),
												'sort_month'   => $month_sort[ $month ],
												'exchanged_nb' => $month_exchangeds[ $month ],
												'taken_nb'     => $month_takens[ $month ],
											];
										}
										amapress_echo_datatable( 'amp_intermit_month_stats_table',
											$columns, $lines,
											array(
												'paging'       => false,
												'searching'    => true,
												'nowrap'       => false,
												'responsive'   => false,
												'init_as_html' => true,
												'fixedHeader'  => array(
													'headerOffset' => 32
												),
											),
											array(
												Amapress::DATATABLES_EXPORT_EXCEL
											)
										);

										return ob_get_clean();
									}
								),
							),
							'tabs'     => array(),
						),
						array(
							'subpage'  => true,
							'id'       => 'intermit_mails_page',
							'settings' => array(
								'name'       => 'Emails et rappels',
								'menu_title' => 'Emails/Rappels',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								'Email - Inscriptions'                      => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'intermittence-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Votre demande d\'adhésion à l\'espace intermittents',
										),
										array(
											'id'      => 'intermittence-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre demande d'adhésion à l'espace intermittents (%%post:lien_intermittence%%) a bien été enregistrée\n\n%%nom_site%%" ),
											'desc'    => Amapress::getPlaceholdersHelpTable( 'intermit-inscr-placeholders', amapress_replace_mail_user_placeholders_help(), 'de l\'amapien' ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Désinscriptions'                   => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'intermittence-desincr-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Votre demande de désinscription de l\'espace intermittents',
										),
										array(
											'id'      => 'intermittence-desincr-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVotre demande de désincription de l'espace intermittents a bien été enregistrée\n\n%%nom_site%%" ),
											'desc'    => Amapress::getPlaceholdersHelpTable( 'intermit-desinscr-placeholders', amapress_replace_mail_user_placeholders_help(), 'de l\'amapien' ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Panier disponible'                 => array(
									'desc'    => '',
									'options' => array(
										array(
											'name' => 'Email aux intermittents',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-dispo-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:panier%% à réserver',
										),
										array(
											'id'      => 'intermittence-panier-dispo-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n\nVous recevez cet email en tant qu'amapien ou intermittent de l'AMAP %%nom_site%%.\n\nUn panier (%%post:panier-desc%%) est proposé à la distribution de %%post:distribution-link%%\n\nSi vous souhaitez le réserver, rendez-vous sur le site de l'AMAP %%nom_site%%, sur la page %%post:liste-paniers%%\n\nPour vous désinscrire de la liste des intermittents : %%lien_desinscription_intermittent%%\n\nEn cas de problème ou de questions sur le fonctionnement des intermittents, veuillez contacter : %%admin_email_link%%.\n\nSi vous avez des questions plus générale sur l'AMAP %%nom_site%%, vous pouvez écrire à %%admin_email_link%%.\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp(),
										),
										array(
											'name' => 'Email à l\'amapien proposant son panier',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-on-list-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Votre %%post:panier%% a été mis sur la liste des paniers à échanger',
										),
										array(
											'id'      => 'intermittence-panier-on-list-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nVotre %%post:panier-desc-date%% a été mis sur la liste des paniers à échanger\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Paniers disponibles - Rappels'     => array(
									'desc'    => '',
									'options' => amapress_intermittence_dispo_recall_options(),
								),
								'Email - Panier reprise - demande'          => array(
									'desc'    => '',
									'options' => array(
										array(
											'name' => 'Email à l\'amapien proposant son panier',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-ask-adherent-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Demande de reprise %%post:panier%% par %%post:repreneur-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-repris-ask-adherent-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nUne demande a été faite par %%post:repreneur%% (%%post:repreneur-coords%%) pour votre panier (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\nVeuillez valider ou rejeter cette demande dans %%post:mes-echanges%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'name' => 'Email à l\'amapien repreneur',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-ask-repreneur-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'La demande de reprise %%post:panier%% a été envoyée',
										),
										array(
											'id'      => 'intermittence-panier-repris-ask-repreneur-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nVotre demande pour le panier (%%post:panier-desc%%) à la distribution %%post:distribution%% a été envoyée à %%adherent-coords%%.\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Panier reprise - demande - Rappel' => array(
									'desc'    => '',
									'options' => amapress_intermittence_validation_recall_options(),
								),
								'Email - Panier reprise - validation'       => array(
									'desc'    => '',
									'options' => array(
										array(
											'name' => 'Email à l\'amapien proposant son panier',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-validation-adherent-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:panier%% repris par %%post:repreneur-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-repris-validation-adherent-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nVotre panier (%%post:panier-desc%%) sera repris par %%post:repreneur%% (%%post:repreneur-coords%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'name' => 'Email à l\'amapien repreneur',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-validation-repreneur-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:adherent-nom%% a accepté la reprise de %%post:panier%%',
										),
										array(
											'id'      => 'intermittence-panier-repris-validation-repreneur-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n%%post:adherent-nom%% (%%post:adherent-coords%%) a accepté la reprise de (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Panier reprise - rejet'            => array(
									'desc'    => '',
									'options' => array(
										array(
											'name' => 'Email à l\'amapien repreneur',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-rejet-repreneur-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:adherent-nom%% a refusé la reprise de %%post:panier%%',
										),
										array(
											'id'      => 'intermittence-panier-repris-rejet-repreneur-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n%%post:adherent-nom%% (%%post:adherent-coords%%) a refusé la reprise de (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Panier annulation - adherent'      => array(
									'desc'    => '',
									'options' => array(
										array(
											'name' => 'Email à l\'amapien proposant son panier',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-adherent-adherent-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Annulation de votre proposition de reprise %%post:panier%%',
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-adherent-adherent-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nVotre panier (%%post:panier-desc-date%%) a été retiré de l'espace intermittents\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'name' => 'Email à l\'amapien repreneur',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Annulation de repise %%post:panier%% de %%post:adherent-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-adherent-repreneur-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n%%post:adherent%% (%%post:adherent-coords%%) a annulé la reprise de son panier (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Panier annulation - repreneur'                 => array(
									'desc'    => '',
									'options' => array(
										array(
											'name' => 'Email à l\'amapien proposant son panier',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-repreneur-adherent-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Annulation de repise %%post:panier%% par %%post:repreneur-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-repreneur-adherent-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\n%%post:repreneur%% (%%post:repreneur-coords%%) a annulé la reprise de votre panier (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'name' => 'Email à l\'amapien repreneur',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-repreneur-repreneur-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Confirmation d\'annulation de repise de %%post:panier%% de %%post:adherent-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-repreneur-repreneur-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nVous avez annulé la reprise du panier (%%post:panier-desc%%) de %%post:adherent%% (%%post:adherent-coords%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email - Panier reprise - définie par Responsable AMAP' => array(
									'desc'    => '',
									'options' => array(
										array(
											'name' => 'Email à l\'amapien',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-admin-adh-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Attribution de votre panier %%post:panier%% à %%post:repreneur-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-admin-adh-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nVotre panier (%%post:panier-desc%%) a été attribué par un responsable de l\'AMAP '%%responsable%%' à %%post:repreneur-nom%% (%%post:repreneur-coords%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [
													'responsable' => 'Nom et coordonnées du responsable ayant fait l\'affectation du panier'
												], false ),
										),
										array(
											'name' => 'Email à l\'amapien repreneur',
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-admin-rep-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Attribution de %%post:panier%% de %%post:adherent-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-admin-rep-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nUn responsable de l\'AMAP '%%responsable%%' vous a attribué la reprise du panier de %%post:adherent-nom%% (%%post:adherent-coords%%) : (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressIntermittence_panier::getPlaceholdersHelp( [
													'responsable' => 'Nom et coordonnées du responsable ayant fait l\'affectation du panier'
												], false ),
										),
										array(
											'type' => 'save',
										),
									)
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_intermit_conf_opt_page',
							'settings' => array(
								'name'       => 'Configuration',
								'menu_title' => 'Configuration',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								'Configuration de l\'espace intermittents'             => array(
									'id'         => 'amapress_intermit_conf_tab',
									'desc'       => '',
									'capability' => 'manage_options',
									'options'    => array(
										array(
											'id'      => 'intermittence_enabled',
											'name'    => 'Activer le système des intermittents',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'intermit_self_inscr',
											'name'    => 'Autoriser les amapiens à inscrire des intermittents',
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'      => 'intermit_adhesion_req',
											'name'    => 'Adhésion obligatoire pour les intermittents',
											'type'    => 'checkbox',
											'desc'    => 'Les intermittents doivent passer par un assistant d\'adhésion (shortcode [intermittent-adhesion-en-ligne] ou [intermittent-adhesion-en-ligne-connecte])',
											'default' => false,
										),
										array(
											'id'      => 'allow_partial_exchange',
											'name'    => 'Autoriser les la cession partielle de paniers',
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'close-subscribe-intermit-hours',
											'name'    => 'Cession paniers',
											'desc'    => 'Clôturer les cessions de paniers x heures avant la distribution',
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Assistant - Adhésion en ligne intermittents - Etapes' => array(
									'id'      => 'config_online_inter_adhesions_messages',
									'desc'    => 'Configuration de l\'assistant d\'adhésion en ligne (intermittent-adhesion-en-ligne-connecte/intermittent-adhesion-en-ligne).',
									'options' => array(
										array(
											'id'      => 'online_subscription_welcome_adh_inter_message',
											'name'    => 'Message de bienvenue',
											'type'    => 'text',
											'default' => '',
											'desc'    => 'Message de bienvenue (non connecté) (shortcode [intermittent-adhesion-en-ligne]), par défaut, "Bienvenue dans l\'assistant d\'adhésion des intermittents de « AMAP »"',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape 1 - Email',
										),
										array(
											'id'      => 'online_subscription_inter_email_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Email',
										),
										array(
											'id'   => 'online_subscription_inter_email_step_message',
											'name' => 'Message supplémentaire',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_email_step_message-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape 2 - Coordonnées, co-adhérents et membres du foyer',
										),
										array(
											'id'      => 'online_subscription_inter_coords_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Coordonnées',
										),
										array(
											'id'   => 'online_subscription_inter_coords_step_message',
											'name' => 'Message supplémentaire',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_inter_coords_step_message-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape Règlement intérieur de l\'AMAP',
										),
										array(
											'id'      => 'online_subscription_inter_agreement_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Charte et règlement intérieur de l\'AMAP',
										),
										array(
											'id'      => 'online_subscription_inter_agreement_step_checkbox',
											'name'    => 'Texte de la case à cocher',
											'type'    => 'text',
											'default' => 'J\'ai pris connaissance du règlement et l\'accepte',
										),
										array(
											'id'   => 'online_subscription_inter_agreement',
											'name' => 'Contenu du règlement intérieur et Contenu de la Charte des AMAPS',
											'type' => 'editor',
											'desc' => Amapress::getPlaceholdersHelpTable( 'online_subscription_inter_agreement-placeholders', [], null, [], false ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => 'Assistant - Étape Adhésion AMAP',
										),
										array(
											'id'      => 'online_subscription_inter_req_adhesion',
											'name'    => 'Message adhésion requise',
											'type'    => 'editor',
											'desc'    => Amapress::getPlaceholdersHelpTable( 'online_subscription_inter_req_adhesion-placeholders', [], null, [], false ),
											'default' => '<p><strong>Pour vous engager dans l’AMAP et pouvoir s\'inscrire aux contrats disponibles, vous devez adhérer à notre Association.</strong></p>',
										),
										array(
											'id'      => 'online_subscription_inter_adh_step_name',
											'name'    => 'Nom de l\'étape',
											'type'    => 'text',
											'default' => 'Adhésion (obligatoire)',
										),
										array(
											'id'      => 'online_subscription_inter_adh_valid_step_name',
											'name'    => 'Nom de l\'étape de validation',
											'type'    => 'text',
											'default' => 'Validation du Bulletin d\'adhésion',
										),
										array(
											'id'      => 'online_subscription_inter_greating_adhesion',
											'name'    => 'Contenu du message de validation',
											'type'    => 'editor',
											'desc'    => 'Le placeholder %%print_button%% permet d\'afficher le bouton Imprimer le bulletin<br/>' . Amapress::getPlaceholdersHelpTable( 'online_subscription_greating_adhesion-placeholders', [
													'print_button' => 'Bouton Imprimer le bulletin'
												], 'de l\'amapien', [], false ),
											'default' => wpautop( "Merci pour votre adhésion à l'AMAP !\nUn courriel de confirmation vient de vous être envoyé. Pensez à consulter les éléments indésirables.\nVeuillez imprimer le bulletin et le remettre avec votre chèque/règlement à l'ordre de l'AMAP lors de la première distribution.\n%%print_button%%" ),
										),
										array(
											'type' => 'save',
										),
									),
								),
							),
						),
					),
				),
				array(
					'id'       => 'options-general.php',
					'type'     => 'builtin',
					'subpages' => array(
						amapress_mailing_queue_menu_options(),
						array(
							'subpage'  => true,
							'id'       => 'amapress_pwa_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'PWA',
								'capability' => 'manage_options',
								'menu_icon'  => 'dashicons-smartphone',
							),
							'options'  => array(
								array(
									'type' => 'note',
									'desc' => 'Une <a href="https://fr.wikipedia.org/wiki/Progressive_web_app" target="_blank">progressive web app</a> (<strong>PWA</strong>, applications web progressives en français) est une application web qui consiste en des pages ou des sites web, et qui peuvent apparaître à l\'utilisateur de la même manière que les applications natives ou les applications mobiles.',
									'bare' => true,
								),
								array(
									'id'         => 'pwa_short_name',
									'name'       => 'Nom de l\'application',
									'type'       => 'text',
									'desc'       => 'Nom du raccourci de l\'application (12 caractères maximum)',
									'capability' => 'manage_options',
									'maxlength'  => 25,
								),
								array(
									'id'         => 'pwa_theme_color',
									'name'       => 'Couleur du thème',
									'type'       => 'color',
									'default'    => '',
									'desc'       => 'Couleur du thème de l\'application',
									'capability' => 'manage_options',
								),
								array(
									'id'         => 'pwa_display',
									'name'       => 'Affichage',
									'type'       => 'select',
									'default'    => 'minimal-ui',
									'desc'       => 'Type d\'affichage de l\'application',
									'options'    => [
										'fullscreen' => 'Plein écran',
										'standalone' => 'Application native',
										'minimal-ui' => 'Navigateur minimal',
										'browser'    => 'Navigateur complet',
									],
									'capability' => 'manage_options',
								),
								array(
									'id'         => 'pwa_ios_prompt',
									'name'       => 'Popup iOs',
									'type'       => 'checkbox',
									'desc'       => 'Afficher un popup explicatif sur comment installer le site sur l\'écran d\'accueil des iPhone/iPad',
									'capability' => 'manage_options',
									'default'    => 0,
								),
								array(
									'id'         => 'pwa_android_prompt',
									'name'       => 'Bouton Installer Android',
									'type'       => 'checkbox',
									'desc'       => 'Afficher un bouton "Installer" avant le popup natif "Ajouter à l\'écran d\'accueil" d\'Android/Chrome',
									'capability' => 'manage_options',
									'default'    => 0,
								),
								array(
									'id'         => 'pwa_android_btn_text',
									'name'       => 'Texte Bouton Installer',
									'type'       => 'text',
									'desc'       => 'Texte du bouton "Installer" pour Android/Chrome',
									'capability' => 'manage_options',
									'default'    => 'Installer l\'application',
								),
								array(
									'id'         => 'pwa_prompt_logged',
									'name'       => 'Connecté seulement',
									'type'       => 'checkbox',
									'desc'       => 'Afficher le popup iOs/iPhone/iPad et le bouton Installer Android/Chrome pour les amapiens connectés seulement',
									'capability' => 'manage_options',
									'default'    => 1,
								),
								array(
									'id'         => 'pwa_prompt_discard',
									'name'       => 'Masquer',
									'type'       => 'number',
									'desc'       => 'Masquer le popup iOs/iPhone/iPad et le bouton Installer Android/Chrome après X secondes',
									'capability' => 'manage_options',
									'default'    => 30,
									'step'       => 1,
								),
								array(
									'type' => 'save',
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_site_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Site',
								'capability' => 'manage_options',
								'menu_icon'  => 'dashicons-admin-site-alt',
							),
							'tabs'     => array(
								'Référencement'                                   => array(
									'id'      => 'amp_site_reference',
									'desc'    => '',
									'options' => array(
										array(
											'id'   => 'site_verif_google_id',
											'name' => 'Google - Code de vérification du site',
											'type' => 'text',
											'desc' => 'Entrer le code de vérification (contenu <em>xxx</em> de l\'attribut <code>content</code> de la balise <code>&lt;meta name=\'google-site-verification\' content=\'<em>xxx</em>\' /&gt;</code>) récupéré de la <a target="_blank" href="https://search.google.com/search-console/about">Google Search Console</a>'
										),
										array(
											'id'   => 'site_verif_bing_id',
											'name' => 'Bing - Code de vérification du site',
											'type' => 'text',
											'desc' => 'Entrer le code de vérification (contenu <em>xxx</em> de l\'attribut <code>content</code> de la balise <code>&lt;meta name=\'msvalidate.01\' content=\'<em>xxx</em>\' /&gt;</code>) récupéré des <a target="_blank" href="https://www.bing.com/toolbox/webmaster">outils pour webmaster Bing</a>'
										),
										array(
											'id'   => 'other_site_html_header',
											'name' => 'Autres entêtes html pour le site',
											'type' => 'textarea',
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Connexion'                                       => array(
									'id'      => 'amp_connection_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'auth_expiration',
											'name'       => amapress__( 'Expiration de session courte' ),
											'desc'       => 'Délai d\'expiration des sessions (par défaut)',
											'type'       => 'number',
											'default'    => 30,
											'min'        => 1,
											'max'        => 365,
											'slider'     => false,
											'unit'       => 'jour(s)',
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'auth_expiration_remember',
											'name'       => amapress__( 'Expiration de session longue' ),
											'desc'       => 'Délai d\'expiration des sessions (Se souvenir de moi, coché)',
											'type'       => 'number',
											'default'    => 90,
											'min'        => 1,
											'max'        => 365,
											'slider'     => false,
											'unit'       => 'jour(s)',
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'below_login_message',
											'name'       => 'Message à afficher en dessous du formulaire de connexion',
											'type'       => 'editor',
											'default'    => wpautop( "Bienvenue sur le site de %%site_name%%.\n\n
Le lien de connexion pour modifier votre mot de passe a une durée de %%expiration_reset_pass%% jours.\n
Si ce délai est passé, merci de suivre la procédure suivante :\n
=================================================\n
Cliquez sur \"Mot de passe oublié ?\" en bas de cette page\n
Vous serez redirigé vers une nouvelle page. Indiquez votre nom d'utilisateur et l'adresse email associée à ce compte.\n
Attendez tranquillement votre nouveau mot de passe par courriel.\n
Vérifiez que l'email ne s'est pas glissé dans vos spams\n
Après obtention de votre nouveau mot de passe, connectez-vous. Vous pouvez le personnaliser sur votre page de profil.\n
=================================================\n" ),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'note',
											'desc' => 'Le réglage du délai d\'expiration du lien de réinitialisation de mot de passe se fait dans ' . Amapress::makeLink( admin_url( 'options-general.php?page=amapress_site_options_page&tab=welcome_mail' ), 'Tableau de bord>Réglages>Site, onglet Email de bienvenue' ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email du site'                                   => array(
									'id'      => 'amp_site_mail_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'email_from_name',
											'name'       => 'Nom de l\'expéditeur des emails du site',
											'type'       => 'text',
											'default'    => get_bloginfo( 'blogname' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'email_from_mail',
											'name'       => 'Adresse email de l\'expéditeur des emails sortants du site',
											'type'       => 'text',
											'default'    => amapress_get_default_wordpress_from_email(),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email de bienvenue'                              => array(
									'id'      => 'welcome_mail',
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'welcome_mail_subject',
											'name'    => 'Sujet de l\'email d\'enregistrement',
											'type'    => 'text',
											'default' => '[%%nom_site%%] Votre compte utilisateur',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'welcome_mail',
											'name'    => 'Contenu de l\'email d\'enregistrement',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour %%dest%%\n\nVotre identifiant est : %%dest:login%%. (Vous pouvez également utiliser votre email : %%dest:mail%%)\nPour configurer votre mot de passe, rendez-vous à l’adresse suivante :\n%%password_url%%\n\nBien cordialement,\n%%nom_site%%" ),
											'desc'    => Amapress::getPlaceholdersHelpTable( 'welcome-placeholders', amapress_replace_mail_user_placeholders_help(), 'de l\'amapien' ),
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'password_lost_mail_subject',
											'name'    => 'Sujet de l\'email de récupération de mot de passe',
											'type'    => 'text',
											'default' => '[%%nom_site%%] Récupération de votre mot de passe',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'password_lost_mail',
											'name'    => 'Contenu de l\'email de récupération de mot de passe',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour %%dest%%\n\nQuelqu'un a demandé la récupération de votre mot de passe. Si ce n'est pas vous, veuillez ignorer cet email et votre mot de passe restera inchangé.\n\nVotre identifiant est : %%dest:login%%. Vous pouvez également utiliser votre email : %%dest:mail%%\nPour changer votre mot de passe, rendez-vous à l’adresse suivante :\n%%password_url%%\n\nBien cordialement,\n%%nom_site%%" ),
											'desc'    => Amapress::getPlaceholdersHelpTable( 'passlost-placeholders', amapress_replace_mail_user_placeholders_help(), 'de l\'amapien' ),
										),
										array(
											'id'      => 'welcome-mail-expiration',
											'name'    => 'Durée d\'expiration',
											'desc'    => 'Expiration de l\'email de bienvenue/mot de passe perdu en jours',
											'type'    => 'number',
											'step'    => 0.5,
											'default' => '180',
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Email de réponse type aux demandes d\'adhésions' => array(
									'id'      => 'adh_req_reply_mail',
									'desc'    => '',
									'options' => array(
										array(
											'id'       => 'adh-request-reply-mail-subject',
											'name'     => 'Sujet de l\'email',
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Votre demande d\'adhésion à %%site_name%%',
										),
										array(
											'id'      => 'adh-request-reply-mail-content',
											'name'    => 'Contenu de l\'email',
											'type'    => 'editor',
											'default' => wpautop( "Bonjour %%prenom%% %%nom%%,\nVotre demande d\'adhésion a bien été prise en compte. Voici la procédure à suivre:[[à compléter]]\n\n%%nom_site%%" ),
											'desc'    =>
												AmapressAdhesionRequest::getPlaceholdersHelp(),
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Notifications'                                   => array(
									'id'      => 'amp_notif_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'notify_admin_new_user',
											'name'       => 'Nouveau compte',
											'type'       => 'checkbox',
											'desc'       => 'Notifier l\'administrateur des inscriptions de nouveaux comptes utilisateurs',
											'capability' => 'manage_options',
											'default'    => true,
										),
										array(
											'id'         => 'notify_admin_pwd_resp',
											'name'       => 'Changement de mot de passe (Responsables)',
											'type'       => 'checkbox',
											'desc'       => 'Notifier l\'administrateur des changements de mots de passe des comptes avec accès au Tableau de bord',
											'capability' => 'manage_options',
											'default'    => true,
										),
										array(
											'id'         => 'notify_admin_pwd_amapien',
											'name'       => 'Changement de mot de passe (Amapiens)',
											'type'       => 'checkbox',
											'desc'       => 'Notifier l\'administrateur des changements de mots de passe des amapiens',
											'capability' => 'manage_options',
											'default'    => true,
										),
										array(
											'id'           => 'admin-notify-cc',
											'name'         => amapress__( 'Cc' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => 'Destinataires en copie des emails de notification',
										),
										array(
											'type' => 'save',
										),
									)
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Amapress',
								'capability' => 'manage_options',
								'menu_icon'  => 'dashicons-carrot'
							),
							'tabs'     => array(
								'Pages'                    => array(
									'id'         => 'amp_pages_config',
									'desc'       => '',
									'capability' => 'manage_options',
									'options'    => array(
//                                array(
//                                    'id' => 'agenda-page',
//                                    'name' => 'Page de l\'Agenda',
//                                    'type' => 'select-pages',
//                                ),
//                                array(
//                                    'id' => 'trombinoscope-page',
//                                    'name' => 'Page du trombinoscope',
//                                    'type' => 'select-pages',
//                                ),
//                                array(
//                                    'id' => 'recettes-page',
//                                    'name' => 'Page des recettes',
//                                    'type' => 'select-pages',
//                                ),
										array(
											'id'   => 'auto-post-thumb',
											'name' => 'Première image à la Une',
											'desc' => 'Utiliser la première image de chaque article comme image à la Une',
											'type' => 'checkbox',
										),
										array(
											'id'   => 'mes-infos-page',
											'name' => 'Page des informations personnelles',
											'type' => 'select-pages',
										),
										array(
											'id'   => 'paniers-intermittents-page',
											'name' => 'Page des paniers intermittents',
											'type' => 'select-pages',
										),
										array(
											'id'   => 'mes-paniers-intermittents-page',
											'name' => 'Page des paniers intermittents de l\'amapien',
											'type' => 'select-pages',
										),
										array(
											'id'   => 'amps-tmpl-file',
											'name' => 'Fichier template (Simple)',
											'desc' => 'Fichier template de votre thème à utiliser pour l\'affichage des informations Amapress (Producteurs/Productions/Produits/Distributions/Recettes...)',
											'type' => 'text',
										),
										array(
											'id'   => 'amps-arch-tmpl-file',
											'name' => 'Fichier template (Archive)',
											'desc' => 'Fichier template de votre thème à utiliser pour l\'affichage Archives des informations Amapress (Producteurs/Productions/Produits/Distributions/Recettes...)',
											'type' => 'text',
										),
//								array(
//									'id'   => 'archive-page-template',
//									'name' => 'Modèle pour les pages d\'archive',
//									'type' => 'select-page-templates',
//								),
										array(
											'type' => 'save',
										),
									)
								),
								'Géolocalisation'          => array(
									'id'      => 'amp_google_api_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'geocode_provider',
											'name'       => 'Fournisseur de géocodage',
											'type'       => 'select',
											'default'    => 'nominatim',
											'desc'       => 'Choisissez le fournisseur utilisé pour résoudre les adresses',
											'options'    => [
												'google'    => 'Google Maps',
												'nominatim' => 'Nominatim (Open Street Map)',
												'here'      => 'Here Maps',
											],
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'map_provider',
											'name'       => 'Fournisseur de cartes',
											'type'       => 'select',
											'default'    => 'openstreetmap',
											'desc'       => 'Choisissez le fournisseur utilisé pour afficher les cartes',
											'options'    => [
												'google'        => 'Google Maps',
												'openstreetmap' => 'OpenStreetMap',
											],
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'google_map_key',
											'name'       => 'Clé Google API',
											'type'       => 'text',
											'default'    => '',
											'desc'       => function ( $option = null ) {
												ob_start();
												$gm_api_url = 'https://console.developers.google.com/henhouse/?pb=["hh-1","maps_backend",null,[],"https://developers.google.com",null,["static_maps_backend","street_view_image_backend","maps_embed_backend","places_backend","geocoding_backend","directions_backend","distance_matrix_backend","geolocation","elevation_backend","timezone_backend","maps_backend"],null]';
												?>
                                                <a
                                                        onclick='window.open("<?php echo wp_slash( $gm_api_url ); ?>", "newwindow", "width=600, height=400"); return false;'
                                                        href='<?php echo $gm_api_url; ?>'
                                                        class="button-primary"
                                                        title="<?php _e( 'Générer une clé d\'API - ( vous devez être connecté à votre compte Google )', 'gmaps-api-key' ); ?>">
													<?php _e( 'Générer une clé d\'API', 'gmaps-api-key' ); ?>
                                                </a>
												<?php echo sprintf( __( 'ou %scliquez ici%s pour Obtenir une clé Google Map', 'geodirectory' ), '<a target="_blank" href="https://console.developers.google.com/flows/enableapi?apiid=static_maps_backend,street_view_image_backend,maps_embed_backend,places_backend,geocoding_backend,directions_backend,distance_matrix_backend,geolocation,elevation_backend,timezone_backend,maps_backend&keyType=CLIENT_SIDE&reusekey=true">', '</a>' ) ?>
												<?php
												return ob_get_clean();
											},
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'here_map_app_id',
											'name'       => 'Here Maps - APP ID',
											'type'       => 'text',
											'default'    => '',
											'desc'       => 'APP ID pour la géolocalisation par Here Maps. Vous pouvez créer un compte <a target="_blank" href="https://developer.here.com/sign-up?create=Freemium-Basic">ici</a> et récupérer vos codes APP ID et APP CODE dans la section "REST & XYZ HUB API/CLI"',
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'here_map_app_code',
											'name'       => 'Here Maps - APP CODE',
											'type'       => 'text',
											'default'    => '',
											'desc'       => 'APP CODE pour la géolocalisation par Here Maps.',
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Conversion PDF et autres' => array(
									'id'      => 'amp_convertws_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'convertws_url',
											'name'       => 'Url du WebService de conversion',
											'type'       => 'text',
											'capability' => 'manage_options',
											'default'    => 'https://convert.amapress.fr',
										),
										array(
											'id'           => 'convertws_user',
											'name'         => 'Compte utilisateur du  WebService de conversion',
											'type'         => 'text',
											'autocomplete' => false,
											'capability'   => 'manage_options',
										),
										array(
											'id'           => 'convertws_pass',
											'name'         => 'Mot de passe du compte du  WebService de conversion',
											'type'         => 'text',
											'autocomplete' => false,
											'capability'   => 'manage_options',
											'is_password'  => true,
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'note',
											'desc' => 'Après avoir enregistré les paramètres ci-dessous, cliquez sur le bouton Tester. Les paramètres sont correctes si un PDF se télécharge et s\'ouvre. Dans le cas contraire, vous obtiendrez un message décrivant le problème.'
										),
										array(
											'name'    => 'Tester',
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => 'Tester la connexion',
													'action' => 'test_convert_ws',
												]
											]
										),
									),
								),
								'Espaces documents'        => array(
									'id'      => 'amp_docspaces_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'docspace_resps_folders',
											'name'       => 'Sous dossiers - Responsables',
											'type'       => 'text',
											'classes'    => 'docspaceSubfolders',
											'desc'       => 'Indiquez le nom du sous-dossier en minuscule et sans espace. Séparez par des virgules pour créer plusieurs sous dossiers. <br/>
Retrouvez le shortcode associé avec le filtre docspace-responsables.
' . Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ), 'Aide-Shortcode' ) . '  <br/>
' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/espaces_documents' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'docspace_amapiens_folders',
											'name'       => 'Sous dossiers - Amapiens',
											'type'       => 'text',
											'classes'    => 'docspaceSubfolders',
											'desc'       => 'Indiquez le nom du sous-dossier en minuscule et sans espace. Séparez par des virgules pour créer plusieurs sous dossiers. <br/>
Retrouvez le shortcode associé avec le filtre docspace-amapiens.
' . Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ), 'Aide-Shortcode' ) . '  <br/>
' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/espaces_documents' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'docspace_public_folders',
											'name'       => 'Sous dossiers - Public',
											'type'       => 'text',
											'classes'    => 'docspaceSubfolders',
											'desc'       => 'Indiquez le nom du sous-dossier en minuscule et sans espace. Séparez par des virgules pour créer plusieurs sous dossiers. <br/>
Retrouvez le shortcode associé avec le filtre docspace-public.
' . Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ), 'Aide-Shortcode' ) . '  <br/>
' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/espaces_documents' ),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Tests'                    => array(
									'id'      => 'amp_tests_config',
									'desc'    => '',
									'options' => array(
										array(
											'id'         => 'test_mail_key',
											'name'       => 'Clé de test emails',
											'type'       => 'text',
											'default'    => uniqid(),
											'desc'       => '',
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'test_mail_mode',
											'name'       => 'Mode de test',
											'type'       => 'checkbox',
											'desc'       => 'Envoie tous les emails aux adresses ci-dessous',
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'test_mail_target',
											'name'       => 'Emails test',
											'type'       => 'text',
											'default'    => function () {
												return get_option( 'admin_email' );
											},
											'desc'       => 'Emails destinataire du mode de test',
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'feedback',
											'name'       => 'Activer le bouton Feedback',
											'type'       => 'checkbox',
											'desc'       => 'Activer le bouton Feedback',
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Confidentialité'          => array(
									'id'      => 'amp_confident_config',
									'desc'    => '',
									'options' => array(
										'allow_show_email'            => array(
											'name'    => amapress__( 'Autoriser les emails à être affichés' ),
											'type'    => 'select',
											'desc'    => 'Autorisation à être affiché aux autres amapiens',
											'default' => 'false',
											'options' => array(
												'false' => 'Ne pas autoriser',
												'true'  => 'Autoriser',
											),
										),
										'allow_show_adresse'          => array(
											'name'    => amapress__( 'Autoriser les adresses à être affichés' ),
											'type'    => 'select',
											'desc'    => 'Autorisation à être affiché aux autres amapiens',
											'default' => 'false',
											'options' => array(
												'false' => 'Ne pas autoriser',
												'true'  => 'Autoriser',
											),
										),
										'allow_show_tel_fixe'         => array(
											'name'    => amapress__( 'Autoriser les téléphones fixes à être affichés' ),
											'type'    => 'select',
											'desc'    => 'Autorisation à être affiché aux autres amapiens',
											'default' => 'false',
											'options' => array(
												'false' => 'Ne pas autoriser',
												'true'  => 'Autoriser',
											),
										),
										'allow_show_tel_mobile'       => array(
											'name'    => amapress__( 'Autoriser les téléphones mobiles à être affichés' ),
											'type'    => 'select',
											'desc'    => 'Autorisation à être affiché aux autres amapiens',
											'default' => 'false',
											'options' => array(
												'false' => 'Ne pas autoriser',
												'true'  => 'Autoriser',
											),
										),
										'allow_show_resp_distrib_tel' => array(
											'name'    => amapress__( 'Autoriser les téléphones mobiles des reponsables de distributions à être affichés' ),
											'type'    => 'select',
											'desc'    => 'Autorisation à être affiché aux autres amapiens la semaine où ils sont responsables',
											'default' => 'false',
											'options' => array(
												'false' => 'Ne pas autoriser',
												'true'  => 'Autoriser',
											),
										),
									),
								),
								'Contacts public'          => array(
									'id'      => 'amp_public_contacts_config',
									'desc'    => '',
									'options' => array(
//                                array(
//                                    'type' => 'save',
//                                    'save' => 'Créer le formulaire de contact',
//                                    'action' => 'init_contact_form'
//                                ),
//                                array(
//                                    'id' => 'preinscription-button-text',
//                                    'name' => 'Texte des boutons d\'inscription',
//                                    'type' => 'text',
//                                    'default' => 'Je m\'inscris',
//                                ),
										array(
											'id'        => 'preinscription-form',
											'name'      => 'Formulaire de préinscription',
											'type'      => 'select-posts',
											'edit_link' => false,
											'post_type' => Amapress::WPCF7_POST_TYPE,
											'desc'      => 'Sélectionner une formulaire de contact dans la liste ci-dessus. Vous les éditer depuis la <a href="' . admin_url( 'admin.php?page=wpcf7' ) . '">page suivante</a>. Ce formulaire sera automatiquement ajouté aux infos de contact ci-dessous.',
										),
//                                array(
//                                    'type' => 'save',
//                                ),
										array(
											'id'           => 'contrat_info_anonymous',
											'name'         => 'Information de contact pour les contrats',
											'type'         => 'editor',
											'capability'   => 'edit_contrat_instances',
											'default'      => '<p><strong>NOUS RENCONTRER</strong><br />Si vous souhaitez nous rencontrer, vous pouvez nous rendre visite lors d’une distribution :<br /> – [[à compléter contact distribution]]</p>
<p><strong>NOUS CONTACTER</strong><br /> Et pour nous contacter, vous pouvez nous envoyer un email à :<br /> [[à définir avec l\'adresse de contact]]<br /> <a href="mailto:' . get_option( 'admin_email' ) . '">' . get_option( 'admin_email' ) . '</a></p>',
											'after_option' => function ( $options ) {
												$links = [];
												foreach ( AmapressContrats::get_contrats() as $contrat ) {
													$links[] = Amapress::makeLink(
														$contrat->getPermalink( 'details' ),
														sprintf( 'Détails du contrat %s', $contrat->getTitle() ),
														true, true
													);
												}
												$href = Amapress::get_page_with_shortcode_href( 'nous-contacter', false );
												if ( ! empty( $href ) ) {
													$links[] = Amapress::makeLink(
														$href,
														'Page Contact (shortcode [nous-contacter])'
													);
												}

												echo '<p>Ce texte peut s\'afficher dans : ' . implode( ', ', $links ) . '</p>';
											},
										),
										array(
											'type' => 'save',
										),
									),
								),
								'Droits'                   => array(
									'id'      => 'amp_rights_config',
									'desc'    => '',
									'options' => array(
										array(
											'name'    => 'Droits des rôles Amapress',
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => 'Remettre par défaut',
													'action' => 'reset_rights',
												]
											]
										),
									),
								),
							),
						),
					),
				),
				array(
					'id'       => 'tools.php',
					'type'     => 'builtin',
					'subpages' => array(
						array(
							'type'       => 'page',
							'title'      => 'Rappels libres',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Rappels libres',
							'post_type'  => AmapressReminder::INTERNAL_POST_TYPE,
							'capability' => 'manage_contenu',
							'slug'       => 'edit.php?post_type=' . AmapressReminder::INTERNAL_POST_TYPE,
							'function'   => null,
						),
					),
				),
				array(
					'id'       => 'users.php',
					'type'     => 'builtin',
					'subpages' => array(
						array(
							'subpage'  => true,
							'id'       => 'amapress_collectif',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Le collectif',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-groups',
							),
							'tabs'     => array(
								'Roles Amapress'                                   => array(
									'id'      => 'amapress_edit_wp_roles',
									'desc'    => '',
									'options' => array(
										array(
											'type'            => 'related-users',
											'name'            => 'Administrateurs',
											'query'           => 'role=administrator',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => 'Responsables Amap',
											'query'           => 'role=responsable_amap',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => 'Coordinateurs Amap',
											'query'           => 'role=coordinateur_amap',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => 'Rédacteurs Amap',
											'query'           => 'role=redacteur_amap',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => 'Référents Producteurs',
											'query'           => 'role=referent',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => 'Producteurs',
											'query'           => 'role=producteur',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => 'Trésoriers',
											'query'           => 'role=tresorier',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
									),
								),
								'Référents producteurs'                            => array(
									'id'      => 'amapress_edit_ref_prods',
									'desc'    => '',
									'options' => array(
										array(
											'id'     => 'amap_referents_view',
											'name'   => '',
											'type'   => 'custom',
											'custom' => function ( $option ) {
												return amapress_get_referent_prods_grid();
											}
										)
									),
								),
								'Coordinateurs Amap'                               => array(
									'id'      => 'amapress_edit_roles_collectif',
									'desc'    => '',
									'options' => array(
										array(
											'id'     => 'amap_role_editor',
											'bare'   => true,
											'type'   => 'custom',
											'custom' => function ( $option ) {
												return amapress_get_amap_roles_editor();
											},
											'save'   => function ( $option ) {
												amapress_save_amap_role_editor();
											},
										),
										array(
											'type'      => 'save',
											'use_reset' => false,
										),
										array(
											'id'      => 'amap_role_add',
											'type'    => 'action-buttons',
											'name'    => 'Rôles supplémentaires',
											'buttons' => array(
												array(
													'text'   => 'Ajouter un rôle',
													'href'   => admin_url( 'edit-tags.php?taxonomy=amps_amap_role_category' ),
													'target' => '_blank',
												),
											),
										),

									)
								),
								'Responsables des réponses aux mails automatiques' => array(
									'id'         => 'amp_amap_roles_config',
									'desc'       => '',
									'capability' => 'manage_options',
									'options'    => array(
										array(
											'type' => 'note',
											'desc' => 'Le fonctionnement d\'Amapress repose sur l\'envoi d\'emails automatiques.<br/>
Il est nécessaire de rediriger les réponsent d\'amapiens à ces mails vers des Coordinateurs Amap.<br/>
Sélectionnez les Coordinateurs Amap en charge des réponses à l\'aide des menu déroulant.',
										),
										array(
											'id'       => 'resp-distrib-gardien-amap-role',
											'name'     => 'Rôle des responsables des gardiens de paniers',
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-distrib-amap-role',
											'name'     => 'Rôle des responsables des responsables des distributions',
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-visite-amap-role',
											'name'     => 'Rôle des responsables des visites',
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-intermittents-amap-role',
											'name'     => 'Rôle des responsables des intermittents',
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-amap_event-amap-role',
											'name'     => 'Rôle des responsables des évènements Amap',
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Historique'                                       => array(
									'id'      => 'amapress_collectif_history',
									'desc'    => '',
									'options' => array(
										array(
											'id'     => 'amapress_collectif_history_view',
											'name'   => '',
											'type'   => 'custom',
											'custom' => function ( $option ) {
												$content  = '';
												$log_file = Amapress::getRolesLogFile();
												if ( file_exists( $log_file ) ) {
													$content = file_get_contents( $log_file );
												}

												return '<pre>' . $content . '</pre>';
											}
										)
									),
								),
							),
						),
						array(
							'title'      => 'Utilisateurs archivables',
							'menu_icon'  => 'dashicons-book',
							'menu_title' => 'Archivables',
							'capability' => 'manage_amapress',
							'slug'       => 'users.php?amapress_role=archivable',
						),
					),
				),
				array(
					'id'       => 'amapress_import_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Imports CSV',
						'position'   => '61',
						'capability' => 'import_csv',
						'icon'       => 'dashicons-none flaticon-farmer',
					),
					'options'  => array(
//						array(
//							'type' => 'note',
//							'desc' => 'ici vous pouvez gérer...'
//						),
					),
					'tabs'     => array(
						'Utilisateurs'              => array(
							'id'         => 'import_users_tab',
							'desc'       => '',
							'capability' => 'edit_users',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet la création des comptes utilisateur et de leurs coordonnées : amapien, co-adhérents. ' . Amapress::makeLink( 'https://wiki.amapress.fr/admin/import#import_utilisateurs', 'Aide', true, true ) .
									          '<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)',
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_user'
								),
								array(
									'id'     => 'import_users',
									'name'   => 'Importer des utilisateurs',
									'type'   => 'custom',
									'bare'   => true,
									'custom' => 'Amapress_Import_Users_CSV::get_import_users_page',
//                            'save' => 'Amapress_Import_Users_CSV::process_users_csv_import',
								),
							)
						),
						'Inscriptions contrats'     => array(
							'id'         => 'import_adhesions_tab',
							'desc'       => '',
							'capability' => 'edit_adhesion',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet d\'inscrire les utilisateurs aux contrats producteurs en fonction du choix de leurs paniers. ' . Amapress::makeLink( 'https://wiki.amapress.fr/admin/import#import_inscriptions', 'Aide', true, true ) .
									          '<br/>Utilisez le bouton <strong>Télécharger le modèle multi contrat</strong> (import avec contrats en colonnes) ou les boutons <strong>Télécharger le modèle "<em>Nom du contrat</em>"</strong> (import avec les configurations de paniers en colonnes) pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)',
								),
								array(
									'name'    => 'Modèle multi contrat',
									'type'    => 'action-buttons',
									'buttons' => [
										[
											'class'  => 'button button-primary  button-import-model',
											'text'   => 'Télécharger le modèle multi contrat',
											'action' => 'generate_model_' . AmapressAdhesion::POST_TYPE . '_multi',
										]
									]
								),
								array(
									'name'    => 'Modèles mono contrat',
									'type'    => 'action-buttons',
									'buttons' => $contrats_model_buttons,
								),
//								array(
//									'type'      => 'save',
//									'use_reset' => false,
//									'save'      => 'Télécharger le modèle - mono contrat',
//									'action'    => 'generate_model_' . AmapressAdhesion::POST_TYPE,
//								),
								array(
									'id'   => 'import_adhesion_default_date_debut',
									'name' => amapress__( 'Date de début par défaut' ),
									'type' => 'date',
									'desc' => 'Date de début',
//                                    'default' => function($option) {
//									        return Amapress::start_of_day(amapress_time());
//                                    }
								),
								array(
									'id'                => 'import_adhesion_default_contrat_instance',
									'name'              => amapress__( 'Contrat par défaut' ),
									'type'              => 'select',
									'post_type'         => 'amps_contrat_inst',
									'autoselect_single' => true,
									'desc'              => 'Contrat',
									'options'           => function ( $option ) {
										$ret      = [];
										$ret[]    = '-- Sélectionner un contrat --';
										$contrats = AmapressContrats::get_active_contrat_instances( null, null, true );
										usort( $contrats, function ( $a, $b ) {
											/** @var AmapressContrat_instance $a */
											/** @var AmapressContrat_instance $b */
											return strcmp( $a->getTitle(), $b->getTitle() );
										} );
										foreach ( $contrats as $c ) {
											$ret[ $c->ID ] = $c->getTitle();
										}

										return $ret;
									}
								),
								array(
									'id'                => 'import_adhesion_default_lieu',
									'name'              => amapress__( 'Lieu par défaut' ),
									'type'              => 'select-posts',
									'post_type'         => 'amps_lieu',
									'autoselect_single' => true,
									'desc'              => 'Lieu',
								),
								array(
									'id'     => 'import_adhesions',
									'name'   => 'Importer des adhésions aux contrats',
									'type'   => 'custom',
									'custom' => 'amapress_get_adhesions_import_page',
									'bare'   => true,
//                            'save' => 'amapress_process_adhesions_csv_import',
								),
							)
						),
						'Configuration des paniers' => array(
							'id'         => 'import_quant_paniers',
							'desc'       => '',
							'capability' => 'edit_contrat_instance',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet d\'importer les configurations de paniers pour vos contrats
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>La colonne "Titre" correspond au nom du produit et la colonne "Contenu" à sa description.
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)',
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_' . AmapressContrat_quantite::POST_TYPE,
								),
//                                array(
//                                    'type'=> 'separator',
//                                ),
								array(
									'id'                => 'import_contrat_quantite_default_contrat_instance',
									'name'              => amapress__( 'Contrat par défaut' ),
									'type'              => 'select-posts',
									'post_type'         => 'amps_contrat_inst',
									'autoselect_single' => true,
									'desc'              => 'Contrat',
								),
								array(
									'id'         => 'ignore_contrat_quantites_unknown_columns',
									'input_name' => 'amapress_ignore_unknown_columns',
									'name'       => amapress__( 'Ignorer les colonnes inconnues' ),
									'type'       => 'checkbox',
									'desc'       => 'Ignorer les colonnes dont l\'entête ne correspond pas à un champ existant',
								),
								array(
									'id'         => 'contrat_quantite_override_contrat_with_inscriptions',
									'input_name' => 'amapress_override_contrat_with_inscriptions',
									'name'       => amapress__( 'Mise à jour avec inscriptions en cours' ),
									'type'       => 'checkbox',
									'desc'       => 'Autoriser la mise à jour de contrats avec inscriptions actives<br/><strong style="color:red">Attention : modifier les configurations de paniers d\'un contrat peut modifier ou annuler ses inscriptions en cours</strong>',
								),
								array(
									'id'         => 'override_all_contrat_quantites',
									'input_name' => 'amapress_override_all_contrat_quantites',
									'name'       => amapress__( 'Réimporter toutes les configurations de paniers' ),
									'type'       => 'checkbox',
									'desc'       => 'Réimporter toutes les configurations de paniers des contrats présents dans l\'excel (permet de conserver l\'ordre)<br/><strong style="color:red">Attention : cette option n\'est pas pas possible pour les contrats ayant déjà des inscriptions. Pour ces contrats, Vous devez mettre à jour les configurations de paniers directement dans la configuration du contrat.</strong>',
								),
								array(
									'id'     => 'import_contrat_quantites',
									'name'   => 'Importer des quantités pour les contrats',
									'type'   => 'custom',
									'custom' => 'amapress_get_contrat_quantites_import_page',
									'bare'   => true,
//                            'save' => 'amapress_process_adhesions_csv_import',
								),
							)
						),
						'Producteurs'               => array(
							'id'         => 'import_producteurs_tab',
							'desc'       => '',
							'capability' => 'manage_amapress',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet d\'importer les producteurs
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>La colonne "Titre" correspond au nom du producteur ou de sa ferme et la colonne "Contenu" à son historique. Les utilisateurs correspondant doivent être créés au préalable
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)',

								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_' . AmapressProducteur::POST_TYPE,
								),
								array(
									'id'     => 'import_producteurs',
									'name'   => 'Importer des producteurs',
									'type'   => 'custom',
									'custom' => 'amapress_get_producteurs_import_page',
									'bare'   => true,
								),
							)
						),
						'Productions'               => array(
							'id'         => 'import_productions_tab',
							'desc'       => '',
							'capability' => 'manage_amapress',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet d\'importer les productions des producteurs
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>La colonne "Titre" correspond au nom de la production (par ex, <i>Légumes, Champignons</i>) et la colonne "Contenu" à sa présentation. Les producteurs correspondant doivent être créés au préalable
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)'
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_' . AmapressContrat::POST_TYPE,
								),
								array(
									'id'     => 'import_productions',
									'name'   => 'Importer des productions',
									'type'   => 'custom',
									'custom' => 'amapress_get_productions_import_page',
									'bare'   => true,
								),
							)
						),
						'Contrats'                  => array(
							'id'         => 'import_contrats_tab',
							'desc'       => '',
							'capability' => 'manage_amapress',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet d\'importer les contrats
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>Les producteurs et productions correspondant doivent être créés au préalable
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)'
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_' . AmapressContrat_instance::POST_TYPE,
								),
								array(
									'id'         => 'ignore_contrats_unknown_columns',
									'input_name' => 'amapress_ignore_unknown_columns',
									'name'       => amapress__( 'Ignorer les colonnes inconnues' ),
									'type'       => 'checkbox',
									'desc'       => 'Ignorer les colonnes dont l\'entête ne correspond pas à un champ existant',
								),
								array(
									'id'         => 'contrat_override_contrat_with_inscriptions',
									'input_name' => 'amapress_override_contrat_with_inscriptions',
									'name'       => amapress__( 'Mise à jour avec inscriptions en cours' ),
									'type'       => 'checkbox',
									'desc'       => 'Autoriser la mise à jour de contrats avec inscriptions actives<br/><strong style="color:red">Attention : modifier les configurations de paniers d\'un contrat peut modifier ou annuler ses inscriptions en cours</strong>',
								),
								array(
									'id'     => 'import_contrats',
									'name'   => 'Importer des contrats',
									'type'   => 'custom',
									'custom' => 'amapress_get_contrats_import_page',
									'bare'   => true,
								),
							)
						),
						'Adhésions AMAP'            => array(
							'id'         => 'import_adh_pmt_tab',
							'desc'       => '',
							'capability' => 'edit_adhesion_paiement',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet d\'importer les adhésions à l\'AMAP (<strong>sans la répartition des montants/avec les valeurs de montants par défaut</strong>)
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)'
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_' . AmapressAdhesion_paiement::POST_TYPE,
								),
								array(
									'id'                => 'import_adhesion_paiement_default_period',
									'name'              => amapress__( 'Période d\'adhésion par défaut' ),
									'type'              => 'select-posts',
									'post_type'         => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
									'autoselect_single' => true,
									'desc'              => 'Période d\'adhésion',
								),
								array(
									'id'     => 'import_adh_pmt',
									'name'   => 'Importer des adhésions',
									'type'   => 'custom',
									'custom' => 'amapress_get_adh_pmt_import_page',
									'bare'   => true,
								),
							)
						),
						'Produits'                  => array(
							'id'         => 'import_produits_tab',
							'desc'       => '',
							'capability' => 'edit_produit',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => '
Cette page permet la création des produits des producteurs
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>La colonne "Titre" correspond au nom du produit (par ex, <i>Radis ronds, Batavia</i>) et la colonne "Contenu" à sa présentation. Les producteurs correspondant doivent être créés au préalable
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)',
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_' . AmapressProduit::POST_TYPE,
								),
								array(
									'id'                => 'import_produit_default_producteur',
									'name'              => amapress__( 'Producteur par défaut' ),
									'type'              => 'select-posts',
									'post_type'         => AmapressProducteur::INTERNAL_POST_TYPE,
									'autoselect_single' => true,
									'desc'              => 'Producteur',
								),
								array(
									'id'     => 'import_produits',
									'name'   => 'Importer des produits',
									'type'   => 'custom',
									'custom' => 'amapress_get_produits_import_page',
									'bare'   => true,
								),
							)
						),
//                        'Adhésions intermittence' => array(
//                            'desc' => '',
//                            'capability' => 'edit_adhesion_intermittence',
//                            'options' => array(
//                                array(
//                                    'type' => 'save',
//                                    'use_reset' => false,
//                                    'save' => 'Télécharger le modèle',
//                                    'action' => 'generate_model_' . AmapressAdhesion_intermittence::POST_TYPE,
//                                ),
//                                array(
//                                    'id' => 'import_adhesions_intermittence',
//                                    'name' => 'Importer des adhésions intermittentes',
//                                    'type' => 'custom',
//                                    'custom' => 'amapress_get_adhesions_intermittence_import_page',
//                                    'bare' => true,
////                            'save' => 'amapress_process_adhesions_csv_import',
//                                ),
//                            )
//                        ),
//                        'Produits' => array(
//                            'desc' => '',
//                            'capability' => 'edit_produit',
//                            'options' => array(
//                                array(
//                                    'id' => 'import_produits',
//                                    'name' => 'Importer des produits',
//                                    'type' => 'custom',
//                                    'custom' => 'amapress_get_produits_import_page',
//                                    'bare' => true,
//                            'save' => 'amapress_process_adhesions_csv_import',
//                                ),
//                            )
//                        ),
//                        'Visites' => array(
//                            'desc' => '',
//                            'capability' => 'edit_visite',
//                            'options' => array(
//                                array(
//                                    'id' => 'import_visites',
//                                    'name' => 'Importer des visites',
//                                    'type' => 'custom',
//                                    'custom' => 'amapress_get_visites_import_page',
//                                    'bare' => true,
////                            'save' => 'amapress_process_adhesions_csv_import',
//                                ),
//                            )
//                        ),
//                        'Chèques contrats' => array(
//                            'desc' => '',
//                            'capability' => 'edit_contrat_paiement',
//                            'options' => array(
//                                array(
//                                    'id' => 'import_contrat_paiement_default_contrat_instance',
//                                    'name' => amapress__('Contrat par défaut'),
//                                    'type' => 'select-posts',
//                                    'post_type' => 'amps_contrat_inst',
//                                    'autoselect_single' => true,
//                                    'desc' => 'Contrat',
//                                ),
//                                array(
//                                    'id' => 'import_paiements',
//                                    'name' => 'Importer des paiements amapiens',
//                                    'type' => 'custom',
//                                    'custom' => 'amapress_get_paiements_import_page',
//                                    'bare' => true,
////                            'save' => 'amapress_process_adhesions_csv_import',
//                                ),
//                            )
//                        ),
					),
					'subpages' => array(),
				),
				array(
					'id'       => 'amapress_help_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Aide',
						'position'   => '85',
						'capability' => 'read',
						'icon'       => 'dashicons-sos',
					),
					'tabs'     => array(
						'Documentation externe'                         => array(
							'id'      => 'wiki',
							'desc'    => '',
							'options' => array(
								array(
									'type' => 'note',
									'bare' => true,
									'desc' => '<p>Consultez la ' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/accueil' ) . '</p>' .
									          '<p>Accédez au ' . Amapress::makeExternalLink( 'https://forum.amapress.fr', 'Forum des Amap' ) . ' si vous ne trouvez pas la réponse à votre question</p>' .
									          '<h5>Un espace dédié pour chaque rôle Amap</h5><p><ul><li>' .
									          Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/accueil', 'Accueil de l’Administrateur, du Responsable Amap' ) .
									          '</li><li>' .
									          Amapress::makeWikiLink( 'https://wiki.amapress.fr/amapien/accueil', 'Accueil de l’Amapien' ) .
									          '</li><li>' .
									          Amapress::makeWikiLink( 'https://wiki.amapress.fr/collectif/accueil', 'Accueil du Collectif' ) .
									          '</li><li>' .
									          Amapress::makeWikiLink( 'https://wiki.amapress.fr/referent_producteur/accueil', 'Accueil du Référent producteur' ) .
									          '</li><li>' .
									          Amapress::makeWikiLink( 'https://wiki.amapress.fr/producteur/accueil', 'Accueil du Producteur' ) .
									          '</li><li>' .
									          Amapress::makeWikiLink( 'https://wiki.amapress.fr/tresorier/accueil', 'Accueil du Trésorier' ) .
									          '</li><li>' .
									          Amapress::makeWikiLink( 'https://wiki.amapress.fr/contrats/gestion', 'Accueil de l\'espace contrat' ) .
									          '</li></ul></p>',
								),
							)
						),
						'Shortcodes'                                    => array(
							'id'         => 'shortcodes',
							'desc'       => '',
							'capability' => 'edit_pages',
							'options'    => array(
								array(
									'id'     => 'shortcodes_cust',
									'name'   => 'Shortcodes',
									'type'   => 'custom',
									'custom' => function () {
										$ret = '<p>Un <strong>shortcode</strong> est un type de balisage qui permet l\'ajout de <strong><em>fonctionnalités interactives configurables</em></strong> dans le <em>contenu</em> des <strong>pages, articles, présentations, widgets</strong> du <em>site vitrine</em>. La syntaxe est la suivante : <code>[<em>nom-du-shortcode</em> argument1=valeur1 argument2=valeur2]</code> (<code>argument1</code> et <code>argument2</code> permettent la configuration du shortcode <code><em>nom-du-shortcode</em></code>) ou <code>[<em>nom-du-shortcode</em>]</code> (sans paramètre) ou encore <code>[<em>nom-du-shortcode</em> argument1=valeur1 argument2=valeur2]xxx[/<em>nom-du-shortcode</em>]</code> (si le shortcode <code><em>nom-du-shortcode</em></code> prend en charge son contenu)
Par exemple :</p>
<ul>
<li><code>[inscription-distrib]</code> : permet d\'afficher le tableau d\'inscription comme responsable de distribution</li>
<li><code>[inscription-en-ligne key=xxx email=un.mail@votre-amap.fr]Les inscriptions en ligne sont ouvertes sur notre espace privé ![/inscription-en-ligne]</code> : permet l\'inscription en ligne aux contrats</li>
<li><code>[amapiens-role-list show_tel=false show_lieu=false]</code> : permet d\'afficher le tableau des membres du collectif</li>
</ul>
<p>Amapress expose les shortcodes suivants :</p>';
										$ret .= '<table class="placeholders-help display">';
										$ret .= '<thead><tr><th>Shortcode</th><th>Description</th></tr></thead>';
										$ret .= '<tbody>';
										global $all_amapress_shortcodes_descs;
										ksort( $all_amapress_shortcodes_descs );
										foreach ( $all_amapress_shortcodes_descs as $k => $desc ) {
											if ( empty( $desc['desc'] ) ) {
												continue;
											}
											$args = '';
											if ( ! empty( $desc['args'] ) ) {
												$args = '<ul><li>' . implode( '</li><li>',
														array_map( function ( $kk, $vv ) {
															return '<strong>' . esc_html( $kk ) . '</strong>: ' . ( strip_tags( $vv ) != $vv ? $vv : esc_html( $vv ) );
														}, array_keys( $desc['args'] ), array_values( $desc['args'] ) ) ) . '</li></ul>';
											}
											$ret .= '<tr><td>' . esc_html( $k ) . '</td><td>' . esc_html( $desc['desc'] ) . $args . '</td></tr>';
										}

										$ret .= '</tbody>';
										$ret .= '</table>';

										return $ret;
									}
								)
							)
						),
						'Placeholders - contrat vierge'                 => array(
							'id'         => 'paper_contrat_placeholders',
							'desc'       => '',
							'capability' => 'manage_contrats',
							'options'    => array(
								array(
									'id'     => 'paper_contrat_placeholders_cust',
									'name'   => 'Placeholders - contrat vierge',
									'type'   => 'custom',
									'custom' => function () {
										return AmapressContrat_instance::getPlaceholdersHelp( [], 'paper', false );
									}
								)
							)
						),
						'Placeholders - production'                     => array(
							'id'         => 'pres_prod_contrat_placeholders',
							'desc'       => '',
							'capability' => 'manage_contrats',
							'options'    => array(
								array(
									'id'     => 'pres_prod_contrat_placeholders_cust',
									'name'   => 'production',
									'type'   => 'custom',
									'custom' => function () {
										return AmapressContrat_instance::getPlaceholdersHelp( [], 'pres', false );
									}
								)
							)
						),
						'Placeholders - contrat personnalisé'           => array(
							'id'         => 'adhesion_contrat_placeholders',
							'desc'       => '',
							'capability' => 'manage_contrats',
							'options'    => array(
								array(
									'id'     => 'adhesion_contrat_placeholders_cust',
									'name'   => 'Placeholders - contrat personnalisé',
									'type'   => 'custom',
									'custom' => function () {
										return AmapressAdhesion::getPlaceholdersHelp( [], true, false );
									}
								)
							)
						),
						'Placeholders - bulletin adhésion personnalisé' => array(
							'id'         => 'adhesion_placeholders',
							'desc'       => '',
							'capability' => 'edit_adhesion_paiement',
							'options'    => array(
								array(
									'id'     => 'adhesion_placeholders_cust',
									'name'   => 'Placeholders - bulletin adhésion personnalisé',
									'type'   => 'custom',
									'custom' => function () {
										return AmapressAdhesion_paiement::getPlaceholdersHelp( [], true, false );
									}
								)
							)
						),
					),
					'options'  => [
					],
					'subpages' => array(),
				),
				array(
					'id'       => 'amapress_messages_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Messagerie',
						'position'   => '27',
						'capability' => 'publish_messages',
						'icon'       => 'dashicons-email-alt',
					),
					'options'  => array(
						array(
							'id'     => 'msg_ml_lists_desc',
							'bare'   => true,
							'type'   => 'custom',
							'custom' => function ( $option ) {
								echo '<p>Vous pouvez également envoyer un mail via les listes suivantes:</p>';
								echo do_shortcode( '[listes-diffusions]' );
							}
						),
						array(
							'id'           => 'msg_target',
							'name'         => 'Destinataire',
							'type'         => 'select',
							'options'      => 'amapress_message_get_targets',
							'required'     => true,
							'after_option' => function ( $option ) {
								echo '<p>Sera envoyé à : <span id="amapress_msg_target_members"></span></p>';
								echo '<script type="text/javascript">
jQuery(function($) {
    var on_change = function() {
       var opt = JSON.parse($(this).val());
       $("#amapress_msg_target_members").html(opt["members"]);
   };
   $("#amapress_msg_target").change(on_change).each(on_change); 
});
</script>';
							},
						),
						array(
							'id'       => 'send_mode',
							'name'     => 'Type d\'envoi',
							'type'     => 'select',
							'options'  => array(
								'bcc'   => 'Email groupé (Bcc)',
								'cc'    => 'Email groupé avec tout le monde en copie (Cc)',
								'indiv' => 'Email individuel',
							),
							'required' => true,
						),
						array(
							'id'       => 'send_from_me',
							'name'     => 'Envoyer de ma part',
							'type'     => 'checkbox',
							'default'  => true,
							'required' => true,
						),
						array(
							'id'       => 'msg_subject',
							'name'     => 'Sujet de l\'email',
							'type'     => 'text',
							'required' => true,
							'default'  => '[AMAP] ',
							'desc'     => 'Pensez à préfixer le sujet de votre mail avec un motif du type [AMAP] ou [' . get_bloginfo( 'name' ) . '] pour être mieux identifié par les destinataires'
						),
						array(
							'id'       => 'msg_content',
							'name'     => 'Contenu de l\'email',
							'type'     => 'editor',
							'required' => true,
						),
//						array(
//							'id'   => 'msg_content_for_sms',
//							'name' => 'Contenu du sms associé',
//							'type' => 'textarea',
//						),
						array(
							'type'      => 'save',
							'save'      => 'Envoyer',
							'action'    => 'send_message',
							'use_reset' => false,
						),
					),
					'subpages' => array(
						array(
							'title'      => 'Messages envoyés',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Messages envoyés',
							'post_type'  => 'amps_message',
							'capability' => 'read_message',
							'slug'       => 'edit.php?post_type=amps_message&order=post_date&orderby=DESC',
						),
					),
				),
			);
		}

		return AmapressEntities::$menu;
	}


	public
	static $predef_subpages = array();

	public
	static function getPostType(
		$type_name
	) {
		$post_types = self::getPostTypes();

		return isset( $post_types[ $type_name ] ) ? $post_types[ $type_name ] : array();
	}

	public
	static function getPostTypes() {
		if ( ! self::$post_types_initialized ) {
			self::init_posts();
			self::$post_types_initialized = true;
		}

		return self::$post_types;
	}

	private
	static function init_posts() {
		self::$post_types = apply_filters( 'amapress_register_entities', array() );
	}

	public
	static function getPostFieldsLabels(
		$post_type = null
	) {
		$key    = "amapress_getPostFieldsLabels_{$post_type}";
		$labels = wp_cache_get( $key );
		if ( false === $labels ) {
			$ents   = AmapressEntities::getPostTypes();
			$labels = array();
			foreach ( $ents as $pt => $arr ) {
				if ( ! empty( $post_type ) && $post_type != $pt ) {
					continue;
				}

				foreach ( AmapressEntities::getFilteredFields( $post_type ) as $key => $value ) {
					if ( isset( $value['conditional'] ) ) {
						foreach ( $value['conditional'] as $kk => $opt ) {
							if ( is_array( $opt ) ) {
								foreach ( $opt as $k => $v ) {
									$labels["amapress_{$pt}_$k"] = $v['name'];
								}
							}
						}
					}
					$labels["amapress_{$pt}_$key"] = $value['name'];
				}
			}
			wp_cache_set( $key, $labels );
		}

		return $labels;
	}

	public
	static function getPostFieldsValidators() {
		$key    = "amapress_getPostFieldsValidators";
		$labels = wp_cache_get( $key );
		if ( false === $labels ) {
			$ents   = AmapressEntities::getPostTypes();
			$labels = array();
			foreach ( $ents as $post_type => $arr ) {
				foreach ( AmapressEntities::getFilteredFields( $post_type ) as $key => $value ) {
					if ( isset( $value['conditional'] ) ) {
						foreach ( $value['conditional'] as $opt ) {
							if ( is_array( $opt ) ) {
								foreach ( $opt as $k => $v ) {
									$labels["amapress_{$post_type}_$k"] = amapress_get_validator( $post_type, $k, $v );
								}
							}
						}
					}
					$labels["amapress_{$post_type}_$key"] = amapress_get_validator( $post_type, $key, $value );
				}
			}
			wp_cache_set( $key, $labels );
		}

		return $labels;
	}

	public
	static function getFilteredFields(
		$post_type
	) {
		$key    = "amapress_getFilteredFields_{$post_type}";
		$fields = wp_cache_get( $key );
		if ( false === $fields ) {
			$ents = AmapressEntities::getPostTypes();
			if ( ! isset( $ents[ $post_type ] ) ) {
				return array();
			}
			$post_conf = $ents[ $post_type ];
			if ( ! isset( $post_conf['fields'] ) ) {
				return array();
			}

			$fields = $post_conf['fields'];
			$fields = apply_filters( "amapress_{$post_type}_fields", $fields );
			wp_cache_set( $key, $fields );
		}

		return $fields;
	}

	public
	static function getPostTypeFields(
		$post_type
	) {
		$key = "amapress_getPostTypeFields_{$post_type}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = array();
			foreach ( AmapressEntities::getFilteredFields( $post_type ) as $key => $value ) {
				if ( isset( $value['conditional'] ) ) {
					foreach ( $value['conditional'] as $opt ) {
						if ( is_array( $opt ) ) {
							foreach ( $opt as $k => $v ) {
								$res["amapress_{$post_type}_$k"] = $v;
							}
						}
					}
				}
				$res["amapress_{$post_type}_$key"] = $value;
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public
	static function getPostFieldsFormatters() {
		$key    = "amapress_getPostFieldsFormatters";
		$labels = wp_cache_get( $key );
		if ( false === $labels ) {
			$ents   = AmapressEntities::getPostTypes();
			$labels = array();
			foreach ( $ents as $post_type => $arr ) {
				foreach ( AmapressEntities::getFilteredFields( $post_type ) as $key => $value ) {
					if ( isset( $value['conditional'] ) ) {
						foreach ( $value['conditional'] as $kk => $opt ) {
							if ( is_array( $opt ) ) {
								foreach ( $opt as $k => $v ) {
									$labels["amapress_{$post_type}_$k"] = amapress_get_formatter( $post_type, $k, $v['type'], isset( $v['name'] ) ? $v['name'] : '' );
								}
							}
						}
					}
					$labels["amapress_{$post_type}_$key"] = amapress_get_formatter( $post_type, $key, $value['type'], isset( $value['name'] ) ? $value['name'] : '' );
				}
			}
			wp_cache_set( $key, $labels );
		}

		return $labels;
	}
}