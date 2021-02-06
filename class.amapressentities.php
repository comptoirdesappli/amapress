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
						'text'   => sprintf( __( 'Télécharger le modèle "%s"', 'amapress' ), $contrat_instance->getTitle() ),
						'action' => 'generate_model_' . AmapressAdhesion::POST_TYPE . '_contrat_' . $contrat_instance->ID,
					);
				}
			}
			AmapressEntities::$menu = array(
				array(
					'type'       => 'page',
					'title'      => __( 'Demande d\'adhésions', 'amapress' ),
					'icon'       => 'dashicons-universal-access',
					'menu_title' => __( 'Demande d\'adhésions [adhesion-request-count]', 'amapress' ),
					'capability' => 'edit_adhesion_request',
					'slug'       => 'edit.php?post_type=amps_adh_req&amapress_date=active&amapress_status=to_confirm',
					'position'   => '27',
					'function'   => null,
				),
				array(
					'id'       => 'amapress_gestion_mailinggroup_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => __( 'Emails groupés [moderation-mlgrp-count]', 'amapress' ),
						'position'   => '27',
						'capability' => 'read_mailing_group',
						'icon'       => 'dashicons-email-alt',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								'',
								'<p>' . __( 'Dans cette section, vous pouvez configurer et administrer les <strong>Emails groupés</strong>.', 'amapress' ) . '</p>' .
								'<p>' . __( 'Un <strong>Email groupé</strong> est une <em>liste de diffusion simplifiée</em> à partir d’un compte email classique (accessible en IMAP ou POP3) et gérée depuis le site de votre AMAP (par Amapress).
Tout email envoyé à ces comptes email spécifiques seront (après modération ou non), envoyés à tous les membres de l’email groupé configuré sur le site.', 'amapress' ) . '</p>',
								array(
									[
										'capability' => '',
										'item'       => sprintf( __( 'Modérer les emails en attente : sous-section <a href="%s">Emails en attente</a>', 'amapress' ), admin_url( 'admin.php?page=mailinggroup_moderation' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'Consulter les archives des emails envoyés : sous-section <a href="%s">Archives</a>', 'amapress' ), admin_url( 'admin.php?page=mailinggroup_archives' ) )
									],
									[
										'capability' => 'manage_options',
										'item'       => sprintf( __( 'Configurer un nouvel Email groupé : sous-section <a href="%s">Comptes</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_mlgrp' ) )
									],
									[
										'capability' => 'manage_options',
										'item'       => sprintf( __( 'Configurer les <a href="%s">rappels</a> et <a href="%s">autres paramètres</a>', 'amapress' ), admin_url( 'admin.php?page=mailingroup_recalls_page' ), admin_url( 'admin.php?page=mailinggroup_config_page' ) )
									],
								),
								( current_user_can( 'manage_options' ) ? sprintf( __( '<h4 id="amapress_gestion_mailinggroup_page_cron">Important</h4><p>Cette fonctionnalité est basée sur le Cron de WordPress. Afin d\'assurer un envoi régulier des emails, vous pouvez créer un cron externe depuis votre hébergement ou toutes les 1 à 5 minutes depuis <a href="https://cron-job.org/" target="_blank">Cron-Job.Org</a> avec l\'url : <code>%s</code> et ajouter <code>define(\'DISABLE_WP_CRON\', true);</code> à votre <code>wp-config.php</code></p>', 'amapress' ), site_url( 'wp-cron.php?doing_wp_cron' ) ) : '' ),
								'https://wiki.amapress.fr/admin/email_groupe'
							);
						},
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'subpage'  => true,
							'id'       => 'mailinggroup_moderation',
							'settings' => array(
								'name'       => __( 'Modération - Emails en attente [moderation-mlgrp-count]', 'amapress' ),
								'menu_title' => __( 'Modération [moderation-mlgrp-count]', 'amapress' ),
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
									$ml_id                                                                                                                                             = $ml->ID;
									$tabs[ sprintf( __( '%s - <span class="badge">%d</span> Emails en attente', 'amapress' ), $ml->getName(), $ml->getMailWaitingModerationCount() ) ] = array(
										'id'      => 'mailgrp-moderate-tab-' . $ml_id,
										'options' => array(
											array(
												'id'     => 'mailgrp-moderate-' . $ml_id,
												'name'   => __( 'Emails en attente', 'amapress' ),
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
								'name'       => __( 'Archives', 'amapress' ),
								'menu_title' => __( 'Archives', 'amapress' ),
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
									$ml_id                                                    = $ml->ID;
									$tabs[ $ml->getName() . __( ' - Archives', 'amapress' ) ] = array(
										'id'      => 'mailgrp-archives-tab-' . $ml_id,
										'options' => array(
											array(
												'id'     => 'mailgrp-archives-' . $ml_id,
												'name'   => __( 'Archives', 'amapress' ),
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
								'name'       => __( 'Emails sortants en attente [waiting-mlgrp-count]', 'amapress' ),
								'menu_title' => __( 'Files attente [waiting-mlgrp-count]', 'amapress' ),
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
									$tabs[ sprintf( __( '%s  - File d\'attente <span class="badge">%d</span>', 'amapress' ),
										$ml->getName(), amapress_mailing_queue_waiting_mail_list_count( $ml_id ) ) ] = array(
										'id'      => 'mailgrp-mailqueue-tab-' . $ml_id,
										'options' => array(
											array(
												'id'     => 'mailgrp-mailqueue-' . $ml_id,
												'name'   => __( 'File d\'attente', 'amapress' ),
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
								'name'       => __( 'Emails sortants en erreur [errored-mlgrp-count]', 'amapress' ),
								'menu_title' => __( 'Erreurs [errored-mlgrp-count]', 'amapress' ),
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
									$tabs[ sprintf( __( '%s  - Erreurs <span class="badge">%d</span>', 'amapress' ),
										$ml->getName(), amapress_mailing_queue_errored_mail_list_count( $ml_id ) ) ] = array(
										'id'      => 'mailgrp-mailerrors-tab-' . $ml_id,
										'options' => array(
											array(
												'id'     => 'mailgrp-mailerrors-' . $ml_id,
												'name'   => __( 'Erreurs', 'amapress' ),
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
								'name'       => __( 'Log des emails sortants', 'amapress' ),
								'menu_title' => __( 'Logs', 'amapress' ),
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
									$ml_id                                                = $ml->ID;
									$tabs[ $ml->getName() . __( ' - Logs', 'amapress' ) ] = array(
										'id'      => 'mailgrp-maillog-tab-' . $ml_id,
										'options' => array(
											array(
												'id'     => 'mailgrp-maillog-' . $ml_id,
												'name'   => __( 'Logs', 'amapress' ),
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
							'title'      => __( 'Comptes', 'amapress' ),
							'menu_icon'  => 'dashicons-admin-tools',
							'menu_title' => __( 'Comptes', 'amapress' ),
							'post_type'  => AmapressMailingGroup::INTERNAL_POST_TYPE,
							'capability' => 'manage_options',
							'slug'       => 'edit.php?post_type=' . AmapressMailingGroup::INTERNAL_POST_TYPE,
							'function'   => null,
						),
						array(
							'subpage'  => true,
							'id'       => 'mailingroup_recalls_page',
							'settings' => array(
								'name'       => __( 'Emails et rappels', 'amapress' ),
								'menu_title' => __( 'Emails/Rappels', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(
								array(
									'name' => __( 'Email de notification à l\'émetteur d\'envoi pour modération', 'amapress' ),
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-waiting-sender-mail-subject',
									'name'     => __( 'Objet de l\'email', 'amapress' ),
									'sanitize' => false,
									'type'     => 'text',
									'default'  => __( 'Email pour la liste %%liste_nom%% transmis au(x) modérateur(s)', 'amapress' ),
								),
								array(
									'id'      => 'mailinggroup-waiting-sender-mail-content',
									'name'    => __( 'Contenu de l\'email', 'amapress' ),
									'type'    => 'editor',
									'default' => wpautop( __( "Bonjour,\n\nVotre email pour la liste %%liste_nom%% a été transmis au(x) modérateur(s)\n\n%%nom_site%%", 'amapress' ) ),
									'desc'    => function ( $option ) {
										return AmapressMailingGroup::getPlaceholdersHelp();
									},
								),
								array(
									'name' => __( 'Email de notification d\'un email à modérer aux modérateurs', 'amapress' ),
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-waiting-mods-mail-subject',
									'name'     => __( 'Objet de l\'email', 'amapress' ),
									'sanitize' => false,
									'type'     => 'text',
									'default'  => __( 'Email à modérer de %%sender%% pour la liste %%liste_nom%%', 'amapress' ),
								),
								array(
									'id'      => 'mailinggroup-waiting-mods-mail-content',
									'name'    => __( 'Contenu de l\'email', 'amapress' ),
									'type'    => 'editor',
									'default' => wpautop( __( "Bonjour,\n\nUn nouvel email pour la liste %%liste_nom%% est arrivé de %%sender%%.\n\n%%msg_summary%%\n\nPour voir les messages en attente, cliquez ici : %%msg_waiting_link%%\n\nPour accepter sa diffusion (il sera distribué), cliquez ici : %%msg_distrib_link%%\n\nPour refuser sa diffusion avec notification (il sera effacé avec notification à l'émetteur), cliquez ici : %%msg_reject_notif_link%%\n\nPour refuser sa diffusion sans notification (il sera effacé sans notification), cliquez ici : %%msg_reject_silent_link%%\n\n%%nom_site%%", 'amapress' ) ),
									'desc'    => function ( $option ) {
										return AmapressMailingGroup::getPlaceholdersHelp();
									},
								),
								array(
									'name' => __( 'Email de notification du rejet d\'un email à l\'émetteur', 'amapress' ),
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-reject-sender-mail-subject',
									'name'     => __( 'Objet de l\'email', 'amapress' ),
									'sanitize' => false,
									'type'     => 'text',
									'default'  => __( 'Rejet de votre email à %%liste_nom%% - %%msg_subject%%', 'amapress' ),
								),
								array(
									'id'      => 'mailinggroup-reject-sender-mail-content',
									'name'    => __( 'Contenu de l\'email', 'amapress' ),
									'type'    => 'editor',
									'default' => wpautop( __( "Bonjour,\n\nVotre email pour la liste %%liste_nom%% a été rejeté par %%moderated_by%%, modérateur de la liste.\n\n(L'objet de votre email : %%msg_subject%%)\n\n%%nom_site%%", 'amapress' ) ),
									'desc'    => function ( $option ) {
										return AmapressMailingGroup::getPlaceholdersHelp();
									},
								),
								array(
									'name' => __( 'Email de notification de distribution d\'un email à l\'émetteur (avec modération)', 'amapress' ),
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-distrib-sender-mail-subject',
									'name'     => __( 'Objet de l\'email', 'amapress' ),
									'sanitize' => false,
									'type'     => 'text',
									'default'  => __( 'Diffusion de votre email à %%liste_nom%%', 'amapress' ),
								),
								array(
									'id'      => 'mailinggroup-distrib-sender-mail-content',
									'name'    => __( 'Contenu de l\'email', 'amapress' ),
									'type'    => 'editor',
									'default' => wpautop( __( "Bonjour,\n\nVotre email pour la liste %%liste_nom%% a été accepté et distribué par %%moderated_by%%, modérateur de la liste.\n\n(L'objet de votre email : %%msg_subject%%)\n\n%%nom_site%%", 'amapress' ) ),
									'desc'    => function ( $option ) {
										return AmapressMailingGroup::getPlaceholdersHelp();
									},
								),
								array(
									'type' => 'save',
								),
								array(
									'name' => __( 'Email de notification de distribution d\'un email à l\'émetteur (sans modération)', 'amapress' ),
									'type' => 'heading',
								),
								array(
									'id'       => 'mailinggroup-distrib-sender-auto-mail-subject',
									'name'     => __( 'Objet de l\'email', 'amapress' ),
									'sanitize' => false,
									'type'     => 'text',
									'default'  => __( 'Diffusion automatique de votre email à %%liste_nom%%', 'amapress' ),
								),
								array(
									'id'      => 'mailinggroup-distrib-sender-auto-mail-content',
									'name'    => __( 'Contenu de l\'email', 'amapress' ),
									'type'    => 'editor',
									'default' => wpautop( __( "Bonjour,\n\nVotre email pour la liste %%liste_nom%% a été accepté et distribué automatiquement.\n\n(L'objet de votre email : %%msg_subject%%)\n\n%%nom_site%%", 'amapress' ) ),
									'desc'    => function ( $option ) {
										return AmapressMailingGroup::getPlaceholdersHelp();
									},
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
								'name'       => __( 'Configuration', 'amapress' ),
								'menu_title' => __( 'Configuration', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(
								array(
									'id'      => 'mailgroup_interval',
									'name'    => __( 'Intervalle', 'amapress' ),
									'type'    => 'number',
									'desc'    => __( 'Intervalle d\'exécution du fetcher des Emails groupés. Nécessite un appel cron externe régulier pour ne pas dépendre du traffic sur le site.', 'amapress' ),
									'default' => '300',
								),
								array(
									'id'      => 'mailinggroup-unk-action',
									'name'    => __( 'Action pour expéditeur inconnu', 'amapress' ),
									'type'    => 'select',
									'options' => [
										'moderate' => __( 'Modérer', 'amapress' ),
										'reject'   => __( 'Rejeté', 'amapress' ),
									],
									'desc'    => __( 'Action à appliquer aux expéditeurs inconnus du site', 'amapress' ),
									'default' => 'moderate',
								),
								array(
									'id'   => 'mailinggroup-bl-regex',
									'name' => __( 'Blacklist', 'amapress' ),
									'type' => 'text',
									'desc' => __( 'Regex de blacklist', 'amapress' ),
								),
								array(
									'id'      => 'mailinggroup-send-confirm-unk',
									'name'    => __( 'Envoyer confirmation aux expéditeurs inconnus', 'amapress' ),
									'type'    => 'checkbox',
									'desc'    => __( 'Envoyer les confirmations aux expéditeurs inconnus', 'amapress' ),
									'default' => false,
								),
								array(
									'id'      => 'mail_group_log_clean_days',
									'type'    => 'number',
									'step'    => 1,
									'default' => 90,
									'name'    => __( 'Nettoyer les archives des Emails groupés (jours)', 'amapress' ),
								),
								array(
									'id'      => 'mail_group_waiting_log_clean_days',
									'type'    => 'number',
									'step'    => 1,
									'default' => 7,
									'name'    => __( 'Nettoyer les logs des files d\'attente d\'envoi (jours)', 'amapress' ),
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
						'name'       => __( 'Listes de diffusions', 'amapress' ),
						'position'   => '83',
						'capability' => 'manage_options',
						'icon'       => 'dashicons-share-alt',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								'',
								'',
								array(
									[
										'capability' => '',
										'item'       => sprintf( __( 'configurer la synchronisation de <a target="_blank" href="%s">listes de diffusions</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_mailing' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'configurer les <a target="_blank" href="%s">systèmes de listes de diffusions</a>', 'amapress' ), admin_url( 'admin.php?page=amapress_mailinglist_options_page' ) )
									],
								),
								'',
								''
							);
						}
					),
					'subpages' => array(
						array(
							'title'      => __( 'Comptes', 'amapress' ),
							'menu_icon'  => 'dashicons-email-alt',
							'menu_title' => __( 'Comptes', 'amapress' ),
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
								'name'       => __( 'Configuration', 'amapress' ),
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
								__( 'Général', 'amapress' )                             => array(
									'id'      => 'amapress_mailinglist_sync_generic_tab',
									'options' => array(
										array(
											'id'           => 'mailing_other_users',
											'name'         => __( 'Utilisateurs inclus dans toutes les listes', 'amapress' ),
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
								__( 'Sync by SQL (ie. Ouvaton) - Sympa', 'amapress' )   => array(
									'id'      => 'amapress_mailinglist_sync_sql_tab',
									'options' => array(
										array(
											'id'      => 'ouvaton_mailing_domain',
											'name'    => __( 'Domaine de la liste de diffusion', 'amapress' ),
											'type'    => 'text',
											'default' => '',
										),
										array(
											'id'           => 'ouvaton_admin_user',
											'name'         => __( 'Email de l\'admin Sympa', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
										),
										array(
											'id'           => 'ouvaton_admin_pass',
											'name'         => __( 'Mot de passe', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'is_password'  => true,
											'default'      => '',
											'desc'         => function ( $option ) {
												return Amapress::getWpConfigSecretHelp( 'AMAPRESS_OUVATON_SYMPA_ADMIN_PASSWORD' );
											}
										),
//										array(
//											'id'      => 'ouvaton_manage_waiting',
//											'name'    => __('Gérer la modération des emails dans Amapress', 'amapress'),
//											'type'    => 'checkbox',
//											'default' => false,
//										),
										array(
											'type' => 'save',
										),
										array(
											'name'    => __( 'Tester', 'amapress' ),
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => __( 'Tester la connexion', 'amapress' ),
													'action' => 'test_mailinglist_access',
												]
											]
										),
									)
								),
								__( 'Sync by Url (ie. Sud Ouest) - Sympa', 'amapress' ) => array(
									'id'      => 'amapress_mailinglist_sync_url_tab',
									'options' => array(
										array(
											'id'      => 'sud-ouest_mailing_domain',
											'name'    => __( 'Domaine de la liste de diffusion', 'amapress' ),
											'type'    => 'text',
											'default' => '',
										),
										array(
											'id'           => 'sud-ouest_admin_user',
											'name'         => __( 'Email de l\'admin', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
										),
										array(
											'id'           => 'sud-ouest_admin_pass',
											'name'         => __( 'Mot de passe', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'is_password'  => true,
											'default'      => '',
											'desc'         => function ( $option ) {
												return Amapress::getWpConfigSecretHelp( 'AMAPRESS_SUDOUEST_SYMPA_ADMIN_PASSWORD' );
											}
										),
										array(
											'id'      => 'sud-ouest_secret',
											'name'    => __( 'Secret pour la mise à jour des membres', 'amapress' ),
											'type'    => 'text',
											'default' => uniqid(),
										),
//										array(
//											'id'      => 'sud-ouest_manage_waiting',
//											'name'    => __('Gérer la modération des emails dans Amapress', 'amapress'),
//											'type'    => 'checkbox',
//											'default' => false,
//										),
										array(
											'type' => 'save',
										),
										array(
											'name'    => __( 'Tester', 'amapress' ),
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => __( 'Tester la connexion', 'amapress' ),
													'action' => 'test_mailinglist_access',
												]
											]
										),
									)
								),
								__( 'Framalistes - Sympa', 'amapress' )                 => array(
									'id'      => 'amapress_mailinglist_sync_frama_tab',
									'options' => array(
										array(
											'id'      => 'framalistes_enable',
											'name'    => __( 'Activer', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Activer la synchronisation', 'amapress' ),
											'default' => true,
										),
										array(
											'id'           => 'framalistes_admin_user',
											'name'         => __( 'Email de l\'admin', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
										),
										array(
											'id'           => 'framalistes_admin_pass',
											'name'         => __( 'Mot de passe', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'is_password'  => true,
											'default'      => '',
										),
//										array(
//											'id'      => 'framalistes_manage_waiting',
//											'name'    => __('Gérer la modération des emails dans Amapress', 'amapress'),
//											'type'    => 'checkbox',
//											'default' => false,
//										),
										array(
											'type' => 'save',
										),
										array(
											'name'    => __( 'Tester', 'amapress' ),
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => __( 'Tester la connexion', 'amapress' ),
													'action' => 'test_mailinglist_access',
												]
											]
										),
									)
								),
								'OVH - Mailinglist'                                     => array(
									'id'      => 'amapress_mailinglist_sync_ovh_tab',
									'options' => array(
										array(
											'type' => 'note',
											'desc' => __( 'Générer des clés pour l\'API avec une durée Illimitée depuis ', 'amapress' ) . Amapress::makeLink( 'https://api.ovh.com/createToken/index.cgi?GET=/*&PUT=/*&POST=/*&DELETE=/*' )
										),
										array(
											'id'      => 'ovh_mailing_domain',
											'name'    => __( 'Domaine de la liste de diffusion', 'amapress' ),
											'type'    => 'text',
											'default' => '',
										),
										array(
											'id'           => 'ovh_application_key',
											'name'         => __( 'Application Key', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
											'desc'         => function ( $option ) {
												return Amapress::getWpConfigSecretHelp( 'AMAPRESS_MAILING_OVH_APPLICATION_KEY' );
											}
										),
										array(
											'id'           => 'ovh_application_secret',
											'name'         => __( 'Application Secret', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
											'desc'         => function ( $option ) {
												return Amapress::getWpConfigSecretHelp( 'AMAPRESS_MAILING_OVH_APPLICATION_SECRET' );
											}
										),
										array(
											'id'           => 'ovh_consumer_key',
											'name'         => __( 'Consumer Key', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => '',
											'desc'         => function ( $option ) {
												return Amapress::getWpConfigSecretHelp( 'AMAPRESS_MAILING_OVH_CONSUMER_KEY' );
											}
										),
										array(
											'id'           => 'ovh_endpoint',
											'name'         => __( 'OVH Endpoint', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'default'      => 'ovh-eu',
										),
										array(
											'type' => 'save',
										),
										array(
											'name'    => __( 'Tester', 'amapress' ),
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => __( 'Tester la connexion', 'amapress' ),
													'action' => 'test_mailinglist_access',
												]
											]
										),
									)
								),
							),
						),
					),
				),
				array(
					'type'       => 'page',
					'title'      => __( 'Etat d\'Amapress', 'amapress' ),
					'icon'       => 'dashicons-none flaticon-buildings',
					'menu_title' => __( 'Etat d\'Amapress', 'amapress' ),
					'capability' => 'manage_amapress',
					'slug'       => 'amapress_state',
					'position'   => '40',
					'function'   => 'amapress_echo_and_check_amapress_state_page',
				),
				array(
					'id'       => 'amapress_gestion_distrib_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => __( 'Distributions', 'amapress' ),
						'position'   => '28',
						'capability' => 'edit_distribution',
						'icon'       => 'dashicons-store',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								'',
								'',
								array(
									[
										'capability' => '',
										'item'       => sprintf( __( 'gérer les <a target="_blank" href="%s">distributions</a> (modification de lieu et d\'heure), le déplacement de livraison de panier, se fait dans <a target="_blank" href="%s">Tableau de bord&gt;Contenus&gt;Paniers</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_distribution&amapress_date=thismonth' ), admin_url( 'edit.php?post_type=amps_panier&amapress_date=thismonth' ) )
									],
									[
										'capability' => 'edit_panier',
										'item'       => sprintf( __( 'publier le <a target="_blank" href="%s">contenu des paniers</a> (par exemple, pour un contrat <em>légumes hebdomadaire</em>)', 'amapress' ), admin_url( 'edit.php?post_type=amps_panier&amapress_date=thismonth' ) )
									],
									[
										'capability' => 'edit_panier',
										'item'       => sprintf( __( 'déplacer/annuler les <a target="_blank" href="%s">livraisons de paniers</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_panier&amapress_date=thismonth' ) )
									],

									[
										'capability' => '',
										'item'       => sprintf( __( 'configurer les <a target="_blank" href="%s">emails de rappels</a> (pour les événements, les responsables de distribution...)', 'amapress' ), admin_url( 'admin.php?page=event_mails_page' ) )
									],

									[
										'capability' => '',
										'item'       => sprintf( __( 'obtenir des <a target="_blank" href="%s">statistiques</a> d\'inscriptions aux distributions', 'amapress' ), admin_url( 'admin.php?page=distrib_page_stats' ) )
									],

									[
										'capability' => '',
										'item'       => sprintf( __( 'attribuer des <a target="_blank" href="%s">rôles aux différents responsables de distributions</a> requis', 'amapress' ), admin_url( 'admin.php?page=amapress_events_conf_opt_page&tab=amp_tab_role_resp_distrib' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'configurer la présentation de la <a target="_blank" href="%s">liste d\'émargement</a>', 'amapress' ), admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_emargement_options_tab' ) )
									],
								),
								'',
								''
							);
						}
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => __( 'Distributions hebdomadaires', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Distributions hebdomadaires', 'amapress' ),
							'post_type'  => 'amps_distribution',
							'capability' => 'edit_distribution',
							'slug'       => 'edit.php?post_type=amps_distribution&amapress_date=thismonth',
						),
						array(
							'title'      => __( 'Paniers', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Paniers', 'amapress' ),
							'post_type'  => 'amps_panier',
							'capability' => 'edit_panier',
							'slug'       => 'edit.php?post_type=amps_panier&amapress_date=thismonth',
						),
						array(
							'subpage'  => true,
							'id'       => 'distrib_page_stats',
							'settings' => array(
								'name'       => __( 'Statistiques des distributions', 'amapress' ),
								'menu_title' => __( 'Statistiques', 'amapress' ),
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

										echo '<p>' . __( 'Obtenir des statistisque pour la période suivante :', 'amapress' ) . '</p>';
										echo '<label class="tf-date" for="amp_stats_start_date">' . esc_html__( 'Début:', 'amapress' ) . ' <input id="amp_stats_start_date" class="input-date date required " name="amp_stats_start_date" type="text" value="' . $start_date_fmt . '" /></label>';
										echo '<label class="tf-date" for="amp_stats_end_date">' . esc_html__( 'Fin:', 'amapress' ) . ' <input id="amp_stats_end_date" class="input-date date required " name="amp_stats_end_date" type="text" value="' . $end_date_fmt . '" /></label>';
										echo '<input type="submit" class="button button-primary" value="' . esc_attr__( 'Voir les statistiques', 'amapress' ) . '" />';
										echo '<hr />';


										echo '<h4>' . sprintf( __( 'Inscriptions aux distributions du %s au %s', 'amapress' ), $start_date_fmt, $end_date_fmt ) . '</h4>';

										$columns         = [];
										$columns[]       = array(
											'title' => __( 'Amapien', 'amapress' ),
											'data'  => array(
												'_'    => 'user',
												'sort' => 'sort_user',
											),
										);
										$columns[]       = array(
											'title' => __( 'Lieu', 'amapress' ),
											'data'  => 'lieu',
										);
										$columns[]       = array(
											'title' => __( 'Contrats', 'amapress' ),
											'data'  => 'contrats',
										);
										$columns[]       = array(
											'title' => __( 'Dates', 'amapress' ),
											'data'  => 'resp_dates',
										);
										$columns[]       = array(
											'title' => __( 'Inscriptions', 'amapress' ),
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
								'name'       => __( 'Emails et rappels', 'amapress' ),
								'menu_title' => __( 'Emails/Rappels', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Emails - Inscriptions - Distribution', 'amapress' )                       => array(
									'options' => array(
										array(
											'id'       => 'inscr-distrib-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Votre inscription à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-distrib-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre inscription à %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [
														'resp_role'          => __( 'Nom du rôle de responsable choisi', 'amapress' ),
														'resp_role_desc'     => __( 'Description du rôle de responsable choisi', 'amapress' ),
														'resp_role_contrats' => __( 'Contrats associés au rôle de responsable choisi', 'amapress' ),
													], false );
												},
										),
										array(
											'id'           => 'inscr-distrib-mail-cc',
											'name'         => __( 'Cc', 'amapress' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => __( 'Emails en copie', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Emails - Désinscriptions - Distribution', 'amapress' )                    => array(
									'options' => array(
										array(
											'id'       => 'desinscr-distrib-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Désinscription de %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'desinscr-distrib-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre désinscription de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'id'           => 'desinscr-distrib-mail-cc',
											'name'         => __( 'Cc', 'amapress' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => __( 'Emails en copie', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Emails - Responsables de distribution - Rappel', 'amapress' )             => array(
									'id'      => 'amp_tab_recall_resp_distrib',
									'desc'    => 'Rappels aux responsables de distribution inscrits avant chaque distribution (avec possibilité d)',
									'options' => amapress_distribution_responsable_recall_options(),
								),
								__( 'Emails - Responsables de distribution - Rappel 2', 'amapress' )           => array(
									'id'      => 'amp_tab_recall_resp_distrib2',
									'desc'    => 'Rappels aux responsables de distribution inscrits avant chaque distribution',
									'options' => amapress_distribution_responsable_recall2_options(),
								),
								__( 'Emails - Gardiens de paniers - Rappel', 'amapress' )                      => array(
									'id'      => 'amp_tab_recall_gardien_paniers',
									'desc'    => 'Rappels individuels aux gardiens de paniers des paniers qu\'ils gardent à chaque distribution',
									'options' => amapress_distribution_gardiens_recall_options(),
								),
								__( 'Emails - Vérification de distribution - Rappel', 'amapress' )             => array(
									'id'      => 'amp_tab_recall_verif_distrib',
									'desc'    => 'Envoi de la liste d\'émargement aux membres du collectif pour vérification et éventuels reports de livraisons avant envoi aux responsables de distribution',
									'options' => amapress_distribution_verify_recall_options(),
								),
								__( 'Emails - A tous les amapiens à la distribution - Rappel', 'amapress' )    => array(
									'id'      => 'amp_tab_recall_all_amapiens',
									'desc'    => 'Rappel collectif (avec tous les contrats livrés) ou individuel (avec le détails des livraisons pour chaque amapien) envoyé avant chaque distribution aux amapiens concernés',
									'options' => amapress_distribution_all_amapiens_recall_options(),
								),
								__( 'Emails - Envoi liste émargement Excel/PDF', 'amapress' )                  => array(
									'id'      => 'amp_tab_recall_emarg',
									'desc'    => 'Envoi de la lite d\'émargement à des membres du collectif avant chaque distribution',
									'options' => amapress_distribution_emargement_recall_options(),
								),
								__( 'Emails - Responsable(s) manquant(s) - Rappel', 'amapress' )               => array(
									'id'      => 'amp_tab_recall_miss_resps',
									'desc'    => 'Rappels aux amapiens avant chaque distribution de s\'inscrire en tant que responsable de distribution s\'il n\'y a pas encore assez d\'inscriptions',
									'options' => amapress_distribution_missing_responsables_recall_options(),
								),
								__( 'Emails - Distribution - Modification livraisons - Rappel', 'amapress' )   => array(
									'id'      => 'amp_tab_recall_modif_distrib',
									'desc'    => 'Rappel envoyé aux amapiens avant chaque distribution lorsqu\'un ou plusieurs contrats ont été annulés ou changés de date',
									'options' => amapress_distribution_changes_recall_options(),
								),
								__( 'Emails - Inscription aux créneaux de distribution - Rappel', 'amapress' ) => array(
									'id'      => 'amp_tab_recall_slot_inscr',
									'desc'    => 'Rappels aux amapiens de la nécessité de leur inscriptions aux créneaux de distribution',
									'options' => amapress_distribution_slots_inscr_recall_options(),
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_distribs_conf_opt_page',
							'settings' => array(
								'name'       => __( 'Configuration', 'amapress' ),
								'menu_title' => __( 'Configuration', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Liste émargement', 'amapress' )                        => array(
									'id'      => 'amp_emargement_options_tab',
									'options' => array(
										array(
											'id'      => 'liste-emargement-show-lieu-instructions',
											'name'    => __( 'Afficher les instructions des lieux', 'amapress' ),
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'      => 'liste-emargement-show-dist-instructions',
											'name'    => __( 'Afficher les instructions de distribution des contrats', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'   => 'liste-emargement-general-message',
											'name' => __( 'Message général', 'amapress' ),
											'type' => 'editor',
										),
										array(
											'id'      => 'liste-emargement-disable-liste',
											'name'    => __( 'Masquer la liste d\'émargement', 'amapress' ),
											'desc'    => __( 'Pour une amap qui n\'a que des paniers modulables, la liste d\'émargement est inutile car elle ne contient que des Var.', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-phone',
											'name'    => __( 'Afficher les numéros de téléphone', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-address',
											'name'    => __( 'Afficher les adresses', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-mail',
											'name'    => __( 'Afficher les emails', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'liste-emargement-show-comment',
											'name'    => __( 'Afficher la colonne Commentaire', 'amapress' ),
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'      => 'liste-emargement-show-sums',
											'name'    => __( 'Afficher le résumé des quantités de produits livrés', 'amapress' ),
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'      => 'liste-emargement-print-font-size',
											'name'    => __( 'Taille d\'impression', 'amapress' ),
											'desc'    => __( 'Taille (en pt) d\'impression de la liste d\'émargement', 'amapress' ),
											'type'    => 'number',
											'step'    => 0.5,
											'default' => '8',
										),
										array(
											'id'      => 'liste-emargement-next-resp-count',
											'name'    => __( 'Responsables prochaines distributions', 'amapress' ),
											'desc'    => __( 'Nombre de distribution à afficher pour inscrire les prochains responsables de distribution', 'amapress' ),
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
								__( 'Inscription distribution', 'amapress' )                => array(
									'id'      => 'amp_inscr_distrib_options_tab',
									'options' => array(
										array(
											'id'      => 'inscr-distrib-allow-multi',
											'name'    => __( 'Multi-inscriptions', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Autoriser un amapien à s\'inscrire plusieurs fois comme responsable de distribution', 'amapress' ),
											'default' => true,
										),
										array(
											'id'      => 'close-subscribe-distrib-hours',
											'name'    => __( 'Clôture inscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les inscriptions x heures avant la distribution', 'amapress' ),
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)',
										),
										array(
											'id'      => 'close-unsubscribe-distrib-hours',
											'name'    => __( 'Clôture désinscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les désinscriptions x heures avant la distribution', 'amapress' ),
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)',
										),
										array(
											'id'      => 'inscr-distrib-button-first',
											'name'    => __( 'Boutons d\'inscription', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Placer les boutons d\'inscription en premier et les inscrits ensuite (sauf si des rôles de responsables sont utilisés)', 'amapress' ),
											'default' => true,
										),
										array(
											'id'      => 'inscr-distrib-co-adh',
											'name'    => __( 'Inscriptions co-adhérents', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Autoriser l\'inscription des co-adhérents par l\'adhérent principal', 'amapress' ),
											'default' => false,
										),
										array(
											'id'      => 'inscr-distrib-co-foyer',
											'name'    => __( 'Inscriptions membres foyers', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Autoriser l\'inscription des membres du foyer par l\'adhérent principal', 'amapress' ),
											'default' => true,
										),
										array(
											'id'      => 'inscr-distrib-font-size',
											'name'    => __( 'Taille de police', 'amapress' ),
											'desc'    => __( 'Taille (avec unité, par ex, pt/px/em/rem) du tablau d\'inscription des responsables de distribution', 'amapress' ),
											'type'    => 'text',
											'default' => '11px',
										),
										array(
											'id'      => 'inscr-distrib-max-dates',
											'name'    => __( 'Distributions', 'amapress' ),
											'desc'    => __( 'Nombre de distribution à afficher pour inscrire les prochains responsables de distribution (-1 = toutes)', 'amapress' ),
											'type'    => 'number',
											'min'     => - 1,
											'step'    => 1,
											'default' => - 1,
											'slider'  => false,
										),
										array(
											'id'      => 'inscr-distrib-column-date-width',
											'name'    => __( 'Largeur colonne Date', 'amapress' ),
											'desc'    => __( 'Largeur de la colonne Date en em/rem/px (ne pas mettre de valeur en %)', 'amapress' ),
											'type'    => 'text',
											'default' => '6rem',
										),
										array(
											'id'      => 'inscr-distrib-column-resp-width',
											'name'    => __( 'Largeur colonne Responsable', 'amapress' ),
											'desc'    => __( 'Largeur de la colonne Responsable (et Gardien de paniers/Créneaux) en em/rem/px (ne pas mettre de valeur en %)', 'amapress' ),
											'type'    => 'text',
											'default' => '8rem',
										),
										array(
											'id'      => 'inscr-distrib-scroll-y',
											'name'    => __( 'Hauteur', 'amapress' ),
											'desc'    => __( 'Hauteur du défilement vertical en px', 'amapress' ),
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
								__( 'Tâches des Responsables de distribution', 'amapress' ) => array(
									'id'      => 'amp_tab_role_resp_distrib',
									'options' => amapress_distribution_responsable_roles_options(),
								),
								__( 'Garde de paniers', 'amapress' )                        => array(
									'id'      => 'amp_tab_gardiens_paniers_distrib',
									'options' => [
										array(
											'id'   => 'enable-gardiens-paniers',
											'name' => __( 'Activer', 'amapress' ),
											'desc' => __( 'Activer le système de garde de paniers', 'amapress' ),
											'type' => 'checkbox',
										),
										array(
											'id'   => 'allow-affect-gardiens',
											'name' => __( 'Autoriser', 'amapress' ),
											'desc' => __( 'Autoriser les amapiens à choisir directement leur gardien de paniers', 'amapress' ),
											'type' => 'checkbox',
										),
										array(
											'id'      => 'gardiens-paniers-message',
											'name'    => __( 'Information', 'amapress' ),
											'type'    => 'textarea',
											'default' => __( 'La garde de panier se fait sur la base du volontariat. Si vous ne trouvez pas de gardien, veuillez écrire à [à compléter]', 'amapress' ),
											'desc'    => __( 'Complétez la procédure spécifique pour votre Amap : le message s\'affichera sur chaque page de distribution', 'amapress' ),
										),
										array(
											'name' => __( 'Email à l\'amapien faisant garder son panier', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-distrib-gardiened-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Garde de vos paniers par %%gardien%% à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-distrib-gardiened-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\n%%gardien%% (%%gardien_contact%% / %%gardien_comment%%) gardera vos paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [
														'amapien'          => __( 'Nom de l\'amapien demandeur de garde de son panier', 'amapress' ),
														'amapien_contacts' => __( 'Coordonnées de l\'amapien demandeur de garde de son panier', 'amapress' ),
														'gardien'          => __( 'Nom du gardien de panier choisi', 'amapress' ),
														'gardien_contact'  => __( 'Coordonnées du gardien de panier choisi', 'amapress' ),
														'gardien_comment'  => __( 'Message/commentaire du gardien de panier choisi', 'amapress' ),
													], false );
												},
										),
										array(
											'name' => __( 'Email au gardien de panier (affectation)', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-distrib-gardieneur-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Garde de panier de %%amapien%% à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-distrib-gardieneur-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\n%%amapien%% (%%amapien_contact%%) vous a attribué la garde de ses paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [
														'amapien'          => __( 'Nom de l\'amapien demandeur de garde de son panier', 'amapress' ),
														'amapien_contacts' => __( 'Coordonnées de l\'amapien demandeur de garde de son panier', 'amapress' ),
														'gardien'          => __( 'Nom du gardien de panier choisi', 'amapress' ),
														'gardien_contact'  => __( 'Coordonnées du gardien de panier choisi', 'amapress' ),
														'gardien_comment'  => __( 'Message/commentaire du gardien de panier choisi', 'amapress' ),
													], false );
												},
										),
										array(
											'name' => __( 'Email au gardien de panier (désaffectation)', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'desinscr-distrib-gardieneur-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Désaffectation garde de panier de %%amapien%% à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'desinscr-distrib-gardieneur-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\n%%amapien%% vous a désattribué la garde de ses paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [
														'amapien'          => __( 'Nom de l\'amapien demandeur de garde de son panier', 'amapress' ),
														'amapien_contacts' => __( 'Coordonnées de l\'amapien demandeur de garde de son panier', 'amapress' ),
														'gardien'          => __( 'Nom du gardien de panier choisi', 'amapress' ),
														'gardien_contact'  => __( 'Coordonnées du gardien de panier choisi', 'amapress' ),
														'gardien_comment'  => __( 'Message/commentaire du gardien de panier choisi', 'amapress' ),
													], false );
												},
										),
										array(
											'name' => __( 'Email au gardien de panier (inscription)', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-distrib-gardien-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Inscription gardien de panier de %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-distrib-gardien-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre inscription en tant que gardien de panier de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'name' => __( 'Email au gardien de panier (désinscription)', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'desinscr-distrib-gardien-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Désinscription gardien de panier de %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'desinscr-distrib-gardien-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre désinscription en tant que gardien de panier de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									],
								),
								__( 'Créneaux de distribution', 'amapress' )                => array(
									'id'      => 'amp_tab_distrib_slots',
									'options' => [
										array(
											'id'      => 'inscr-distribution-slot-close',
											'type'    => 'number',
											'step'    => 1,
											'default' => 24,
											'name'    => __( 'Délais de clôture des inscriptions aux créneaux de distribution<br/><em>X</em> heures avant la distribution', 'amapress' ),
										),
										array(
											'name' => __( 'Mail automatique de confirmation de réservation par l\'amapien', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'      => 'inscr-distribution-slot-send',
											'name'    => __( 'Activer', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Envoyer un email de confirmation de réservation de créneau à l\'amapien', 'amapress' ),
											'default' => true,
										),
										array(
											'id'       => 'inscr-distribution-slot-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Réservation du créneau %%creneau%% à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-distribution-slot-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVous avez choisi le créneau %%creneau%% pour récupérer vos paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [
														'creneau'            => __( 'Créneau choisi', 'amapress' ),
														'creneau_date_heure' => __( 'Date et heure du créneau choisi', 'amapress' )
													], false );
												},
										),
										array(
											'name' => __( 'Mail automatique de confirmation de réservation par un référent', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'      => 'inscr-distribution-admin-slot-send',
											'name'    => __( 'Activer', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Envoyer un email de confirmation de réservation de créneau à l\'amapien', 'amapress' ),
											'default' => true,
										),
										array(
											'id'       => 'inscr-distribution-admin-slot-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Attribution du créneau %%creneau%% à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-distribution-admin-slot-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nUn responsable '%%responsable%%' vous a attribué le créneau %%creneau%% pour récupérer vos paniers à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressDistribution::getPlaceholdersHelp( [
														'creneau'            => __( 'Créneau choisi', 'amapress' ),
														'creneau_date_heure' => __( 'Date et heure du créneau choisi', 'amapress' ),
														'responsable'        => __( 'Nom et coordonnées du responsable ayant fait l\'affectation du créneau', 'amapress' )
													], false );
												},
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
								'name'       => __( 'Outils', 'amapress' ),
								'menu_title' => __( 'Outils', 'amapress' ),
								'capability' => 'edit_distribution',
								'menu_icon'  => 'dashicons-admin-tools',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Distributions - Définir horaires particuliers', 'amapress' ) => array(
									'id'      => 'amp_tab_distrib_hours_setter',
									'options' => [
										array(
											'id'     => 'distrib-hours-setter',
											'bare'   => true,
//									'name'                => __('Rappel 1', 'amapress'),
//									'desc'                => __('Inscription à une visite', 'amapress'),
											'type'   => 'custom',
											'custom' => 'amapress_distribution_hours_setter',
										),
									],
								),
								__( 'Nettoyage', 'amapress' )                                     => array(
									'id'      => 'amp_tab_distrib_cleaner',
									'options' => [
										array(
											'id'     => 'distrib-cleaner',
											'bare'   => true,
											'type'   => 'custom',
											'custom' => function () {
												if ( isset( $_GET['dist_action'] ) ) {
													$res = '';
													switch ( $_GET['dist_action'] ) {
														case 'clean':
															$res = AmapressDistribution::cleanOrphans();
															break;
														case 'update_titles':
															amapress_update_all_posts(
																[
																	AmapressDistribution::POST_TYPE,
																	AmapressPanier::POST_TYPE,
																	AmapressAssemblee_generale::POST_TYPE,
																	AmapressAdhesion::POST_TYPE,
																]
															);
															$res = __( 'Titres et urls mis à jour', 'amapress' );
															break;
														case 'regenerate':
															foreach ( AmapressContrat_instance::getAll() as $contrat_instance ) {
																AmapressDistributions::generate_distributions( $contrat_instance->ID, false );
																AmapressPaniers::generate_paniers( $contrat_instance->ID, false );
															}
															$res = __( 'Distributions et paniers mis à jour', 'amapress' );
															break;
													}
													if ( ! empty( $res ) ) {
														echo amapress_get_admin_notice( $res, 'success', true );
													}
												}

												$base_url = remove_query_arg( 'dist_action' );
												echo '<p>' . Amapress::makeButtonLink(
														add_query_arg( 'dist_action', 'clean', $base_url ),
														__( 'Nettoyer les distributions sans contrat', 'amapress' )
													) . '</p>';
												echo '<p>' . Amapress::makeButtonLink(
														add_query_arg( 'dist_action', 'update_titles', $base_url ),
														__( 'Mettre à jour les titres et urls des distributions et paniers', 'amapress' )
													) . '</p>';
												echo '<p>' . Amapress::makeButtonLink(
														add_query_arg( 'dist_action', 'regenerate', $base_url ),
														__( 'Forcer la mise à jour des distributions et paniers', 'amapress' )
													) . '</p>';
											},
										),
									],
								),
							),
						),
						array(
							'title'      => __( 'Lieux de distributions', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Lieux de distributions', 'amapress' ),
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
						'name'       => __( 'Contenus', 'amapress' ),
						'position'   => '32',
						'capability' => 'manage_contenu',
						'icon'       => 'dashicons-none flaticon-water',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								'',
								'',
								array(
									[
										'capability' => 'edit_recette',
										'item'       => sprintf( __( 'publier des <a target="_blank" href="%s">recettes</a> (<a target="_blank" href="https://wiki.amapress.fr/collectif/recette_publier">Aide</a>) et définir leurs <a target="_blank" href="%s">étiquettes</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_recette' ), admin_url( 'edit-tags.php?taxonomy=amps_recette_category' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'éditer les <a target="_blank" href="%s">produits de vos différents producteurs</a>, par exemples les espèces de légumes cultivés, les races de poules, les types de produits ainsi que leur associer des <a target="_blank" href="%s">étiquettes</a>. (Le <a target="_blank" href="https://wiki.amapress.fr/contrats/creation">renseignement des types et tailles de paniers</a> de fait au sein des <a target="_blank" href="%s">contrats</a>)', 'amapress' ), admin_url( 'edit.php?post_type=amps_produit' ), admin_url( 'edit-tags.php?taxonomy=amps_produit_category' ), admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ) )
									],
								),
								'',
								''
							);
						}
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => __( 'Recettes', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Recettes', 'amapress' ),
							'post_type'  => 'amps_recette',
							'capability' => 'edit_recette',
							'slug'       => 'edit.php?post_type=amps_recette',
						),
						array(
							'title'      => __( 'Catégories de recettes', 'amapress' ),
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => __( 'Catégories de recettes', 'amapress' ),
							'capability' => 'edit_recette',
							'post_type'  => 'amps_recette_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_recette_category',
						),
						array(
							'title'      => __( 'Produits', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Produits', 'amapress' ),
							'post_type'  => 'amps_produit',
							'capability' => 'edit_produit',
							'slug'       => 'edit.php?post_type=amps_produit',
						),
						array(
							'title'      => __( 'Catégories de produit', 'amapress' ),
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => __( 'Catégories de produits', 'amapress' ),
							'capability' => 'edit_produit',
							'post_type'  => 'amps_produit_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_produit_category',
						),
//						array(
//							'title'      => __('News', 'amapress'),
//							'menu_icon'  => 'post_type',
//							'menu_title' => __('News', 'amapress'),
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
						'name'       => __( 'Évènements', 'amapress' ),
						'position'   => '31',
						'capability' => 'manage_events',
						'icon'       => 'dashicons-none flaticon-interface-2',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								'',
								'',
								array(
									[
										'capability' => '',
										'item'       => sprintf( __( 'planifier et publier des <a target="_blank" href="%s">Visites à la Ferme</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_visite&amapress_date=next' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'planifier et publier des <a target="_blank" href="%s">Assemblées Générales</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_assemblee&amapress_date=next' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'planifier et publier des <a target="_blank" href="%s">événements</a> de tous <a target="_blank" href="%s">types</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_amap_event&amapress_date=next' ), admin_url( 'edit-tags.php?taxonomy=amps_amap_event_category' ) )
									],
								),
								'',
								''
							);
						}
					),
					'options'  => array(),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => __( 'Visites à la ferme', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Visites à la ferme', 'amapress' ),
							'post_type'  => 'amps_visite',
							'capability' => 'edit_visite',
							'slug'       => 'edit.php?post_type=amps_visite&amapress_date=next',
						),
						array(
							'title'      => __( 'Assemblées', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Assemblées', 'amapress' ),
							'post_type'  => 'amps_assemblee',
							'capability' => 'edit_assemblee_generale',
							'slug'       => 'edit.php?post_type=amps_assemblee&amapress_date=next',
						),
						array(
							'title'      => __( 'Evènement', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Evènements', 'amapress' ),
							'post_type'  => 'amps_amap_event',
							'capability' => 'edit_amap_event',
							'slug'       => 'edit.php?post_type=amps_amap_event&amapress_date=next',
						),
						array(
							'title'      => __( 'Catégories d\'évènements', 'amapress' ),
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => __( 'Catégories d\'évènements', 'amapress' ),
							'capability' => 'edit_amap_event',
							'post_type'  => 'amps_amap_event_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_amap_event_category',
						),
						array(
							'subpage'  => true,
							'id'       => 'event_mails_page',
							'settings' => array(
								'name'       => __( 'Emails et rappels', 'amapress' ),
								'menu_title' => __( 'Emails/Rappels', 'amapress' ),
//								'position'   => '25.2',
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Emails - Inscriptions - Evènements (visite, évènement...)', 'amapress' )        => array(
									'options' => array(
										array(
											'id'       => 'inscr-event-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Votre inscription à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-event-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre inscription à %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Emails - Désinscriptions - Evènements (visite, évènement...)', 'amapress' )     => array(
									'options' => array(
										array(
											'id'       => 'desinscr-event-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Désinscription de %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'desinscr-event-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre désinscription de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Emails - Nouveau commentaire - Evènements (visite, évènement...)', 'amapress' ) => array(
									'options' => array(
										array(
											'id'       => 'comment-event-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Nouveau commentaire pour %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'comment-event-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nUn nouveau commentaire a été ajouté à %%post:titre%% (%%post:lien%%):\n%%commentaire%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [
														'commentaire' => __( 'Contenu du commentaire', 'amapress' )
													], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Emails - Visite - Inscription - Rappel', 'amapress' )                           => array(
									'id'      => 'amp_tab_recall_visite_inscr',
									'desc'    => 'Rappel de leur participation aux amapiens inscrits à une visite à la ferme',
									'options' => amapress_visite_inscription_recall_options(),
								),
								__( 'Emails - Visite - Inscription possible - Rappel', 'amapress' )                  => array(
									'id'      => 'amp_tab_recall_visite_avail',
									'desc'    => 'Rappel aux amapiens qu\'une visite à la ferme aura bientôt lieu',
									'options' => amapress_visite_available_recall_options(),
								),
								__( 'Emails - Evènement AMAP - Inscription - Rappel', 'amapress' )                   => array(
									'id'      => 'amp_tab_recall_amap_event_inscr',
									'desc'    => 'Rappel de leur participation aux amapiens inscrits à un évènement AMAP',
									'options' => amapress_amap_event_inscription_recall_options(),
								),
								__( 'Emails - Evènement AMAP - Inscription possible - Rappel', 'amapress' )          => array(
									'id'      => 'amp_tab_recall_amap_event_avail',
									'desc'    => 'Rappel aux amapiens qu\'un évènement AMAP aura bientôt lieu',
									'options' => amapress_amap_event_available_recall_options(),
								),
								__( 'Emails - Assemblée générale AMAP - Inscription - Rappel', 'amapress' )          => array(
									'id'      => 'amp_tab_recall_ag_inscr',
									'desc'    => 'Rappel de leur participation aux amapiens inscrits à une Assemblée générale',
									'options' => amapress_assemblee_generale_inscription_recall_options(),
								),
								__( 'Emails - Assemblée générale AMAP - Inscription possible - Rappel', 'amapress' ) => array(
									'id'      => 'amp_tab_recall_ag_avail',
									'desc'    => 'Rappel aux amapiens qu\'une Assemblée générale aura bientôt lieu',
									'options' => amapress_assemblee_generale_available_recall_options(),
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_events_conf_opt_page',
							'settings' => array(
								'name'       => __( 'Configuration', 'amapress' ),
								'menu_title' => __( 'Configuration', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Inscription visites', 'amapress' )           => array(
									'id'      => 'amp_inscr_visite_options_tab',
									'options' => array(
										array(
											'id'      => 'close-subscribe-visite-hours',
											'name'    => __( 'Clôture inscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les inscriptions x heures avant la visite', 'amapress' ),
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'id'      => 'close-unsubscribe-visite-hours',
											'name'    => __( 'Clôture désinscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les désinscriptions x heures avant la visite', 'amapress' ),
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
								__( 'Créneaux de visite à la ferme', 'amapress' ) => array(
									'id'      => 'amp_tab_visite_slots',
									'options' => [
										array(
											'name' => __( 'Email à l\'amapien choisissant un créneau', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'inscr-visite-slot-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Votre inscription de %%creneau%% à %%post:title%%', 'amapress' ),
										),
										array(
											'id'      => 'inscr-visite-slot-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVous êtes inscrit pour %%creneau%% à %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress_EventBase::getPlaceholdersHelp( [
														'creneau'            => __( 'Créneau choisi', 'amapress' ),
														'creneau_date_heure' => __( 'Date et heure du créneau choisi', 'amapress' )
													], false );
												},
										),
										array(
											'type' => 'save',
										),
									],
								),
								__( 'Inscription évènements', 'amapress' )        => array(
									'id'      => 'amp_inscr_event_options_tab',
									'options' => array(
										array(
											'id'      => 'close-subscribe-event-hours',
											'name'    => __( 'Clôture inscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les inscriptions x heures avant l\'évènement', 'amapress' ),
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'id'      => 'close-unsubscribe-event-hours',
											'name'    => __( 'Clôture désinscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les désinscriptions x heures avant l\'évènement', 'amapress' ),
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
								__( 'Inscription assemblées', 'amapress' )        => array(
									'id'      => 'amp_inscr_ag_options_tab',
									'options' => array(
										array(
											'id'      => 'close-subscribe-assemblee-hours',
											'name'    => __( 'Clôture inscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les inscriptions x heures avant l\'AG', 'amapress' ),
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'id'      => 'close-unsubscribe-assemblee-hours',
											'name'    => __( 'Clôture désinscriptions', 'amapress' ),
											'desc'    => __( 'Clôturer les désinscriptions x heures avant l\'assemblee', 'amapress' ),
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
//                        'name' => __('Contrats &amp; Distributions', 'amapress'),
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
//                        __('Contrats', 'amapress') => array(
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
						'name'       => __( 'Gestion Contrats', 'amapress' ),
						'position'   => '30',
						'capability' => 'edit_contrat',
						'icon'       => 'dashicons-none flaticon-pen',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								'',
								'',
								array(
									[
										'capability' => '',
										'item'       => sprintf( __( 'gérer tous vos <a target="_blank" href="%s">contrats</a> (Aide <a target="_blank" href="https://wiki.amapress.fr/contrats/creation">Création</a> et <a target="_blank" href="https://wiki.amapress.fr/contrats/gestion">Gestion</a>), leurs <a target="_blank" href="%s">inscriptions</a>, les <a target="_blank" href="%s">présentations des productions</a> (présentation des contrats), les <a target="_blank" href="%s">règlements</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ), admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active' ), admin_url( 'edit.php?post_type=amps_contrat' ), admin_url( 'edit.php?post_type=amps_cont_pmt&amapress_date=active' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'obtenir des <a target="_blank" href="%s">statistiques</a> sur les inscriptions, la <a target="_blank" href="%s">répartition des règlements</a>, la <a target="_blank" href="%s">répartition des paniers</a> et des <a target="_blank" href="%s">quantités à livrer</a> par les prodcuteurs', 'amapress' ), admin_url( 'admin.php?page=contrats_quantites_stats' ), admin_url( 'admin.php?page=calendar_contrat_paiements' ), admin_url( 'admin.php?page=contrats_quantites_next_distrib' ), admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'gérer <a target="_blank" href="%s">l\'archivage des saisons précédentes</a>', 'amapress' ), admin_url( 'admin.php?page=contrats_archives' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'configurer les <a target="_blank" href="%s">emails de rappels</a>', 'amapress' ), admin_url( 'admin.php?page=contrats_mails_page' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( "configurer <a target=\"_blank\" href=\"%s\">l'assistant de préinscription en ligne</a> et obtenir un <a target=\"_blank\" href=\"%s\">modèle de contrat Word/DOCX générique</a> pour le préremplissage automatique des contrats lors des inscriptions.", admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) )
									],
									[
										'capability' => 'edit_producteur',
										'item'       => sprintf( "les <a target=\"_blank\" href=\"%s\">producteurs</a> : la présentation des exploitations de vos différents producteurs ainsi que <a href=\"%s\" target=\"_blank\">l'affectation de leurs référents</a>. Vous pouvez également affecter des référents sur les contrats de vos producteurs (<a target=\"_blank\" href=\"https://wiki.amapress.fr/referent_producteur/fiche_producteur\">Voir l'aide</a>)", admin_url( 'edit.php?post_type=amps_producteur' ), admin_url( 'users.php?page=amapress_collectif&tab=amapress_edit_ref_prods' ) )
									],
								),
								'',
								'https://wiki.amapress.fr/referent_producteur/accueil',
								__( 'Consultez l\'aide Référent Producteur', 'amapress' )
							);
						}
					),
					'options'  => array(
//						array(
//							'type' => 'note',
//							'bare' => true,
//							'desc' => ''
//						)
					),
					'tabs'     => array(
						__( 'Ajouter Inscription Contrat ', 'amapress' )   => array(
							'id'         => 'add_inscription',
							'use_form'   => false,
							'use_table'  => false,
							'capability' => 'edit_contrat_instance',
							'options'    => array(
								array(
									'id'         => 'add_user_inscr',
									'type'       => 'custom',
									'bare'       => true,
									'capability' => 'edit_contrat_instance',
									'custom'     => 'amapress_create_user_and_inscription_assistant',
								)
							),
						),
						__( 'Ajouter un coadhérent', 'amapress' )          => array(
							'id'         => 'add_coadherent',
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
						__( 'Ajouter une personne hors AMAP', 'amapress' ) => array(
							'id'         => 'add_other_user',
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
							'title'      => __( 'Etat d\'encaissement des contrats', 'amapress' ),
							'menu_icon'  => 'dashicons-none flaticon-business',
							'menu_title' => __( 'Synthèse', 'amapress' ),
							'capability' => 'edit_contrat_paiement',
							'post_type'  => 'contrat_paiements',
							'slug'       => 'contrat_paiements',
							'function'   => 'amapress_render_contrat_paiements_list',
							'hook'       => 'amapress_contrat_paiements_list_options',
						),
						array(
							'title'      => __( 'Inscriptions aux contrats', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Inscriptions', 'amapress' ),
							'post_type'  => 'amps_adhesion',
							'capability' => 'edit_adhesion',
							'slug'       => 'edit.php?post_type=amps_adhesion&amapress_date=active',
//                            'description' => 'description',
						),
						array(
							'title'      => __( 'Encaissements des contrats', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Règlements', 'amapress' ),
							'post_type'  => 'amps_cont_pmt',
							'capability' => 'edit_contrat_paiement',
							'slug'       => 'edit.php?post_type=amps_cont_pmt&amapress_date=active',
						),
						array(
							'title'      => __( 'Présentations des contrats', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Productions', 'amapress' ),
							'post_type'  => 'amps_contrat',
							'capability' => 'edit_contrat',
							'slug'       => 'edit.php?post_type=amps_contrat',
						),
						array(
							'title'      => __( 'Contrats Annuels', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Edition', 'amapress' ),
							'post_type'  => 'amps_contrat_inst',
							'capability' => 'edit_contrat_instance',
							'slug'       => 'edit.php?post_type=amps_contrat_inst&amapress_date=active',
						),
						array(
							'subpage'  => true,
							'id'       => 'contrats_quantites_next_distrib',
							'settings' => array(
								'name'       => __( 'Quantités à la prochaine distribution', 'amapress' ),
								'menu_title' => __( 'Quantités', 'amapress' ),
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
								'name'       => __( 'Statistiques des contrats', 'amapress' ),
								'menu_title' => __( 'Statistiques', 'amapress' ),
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
										echo '<label for="amp_stats_contrat">' . __( 'Obtenir des statistisque pour le contrat suivant :', 'amapress' ) . '</label>';
										echo '<select id="amp_stats_contrat" name="amp_stats_contrat">';
										tf_parse_select_options( $options, isset( $_REQUEST['amp_stats_contrat'] ) ? [ $_REQUEST['amp_stats_contrat'] ] : null );
										echo '</select>';
										echo '<input type="submit" class="button button-primary" value="' . esc_attr__( 'Voir les statistiques', 'amapress' ) . '" />';
										echo '<hr />';

										if ( ! empty( $_REQUEST['amp_stats_contrat'] ) ) {
											$contrat_instance = AmapressContrat_instance::getBy( intval( $_REQUEST['amp_stats_contrat'] ) );
											if ( ! empty( $contrat_instance ) ) {
												echo '<h4>';
												echo sprintf( __( 'Inscriptions au contrat "%s"', 'amapress' ), esc_html( $contrat_instance->getTitle() ) );
												echo '</h4>';
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
								'name'       => __( 'Calendrier des encaissements des contrats', 'amapress' ),
								'menu_title' => __( 'Calendrier', 'amapress' ),
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
								'name'       => __( 'Finances', 'amapress' ),
								'menu_title' => __( 'Finances', 'amapress' ),
								'capability' => 'edit_contrat',
								'menu_icon'  => 'dashicons-chart-line',
							),
							'options'  => array(
								array(
									'name'   => __( 'Statistiques financières', 'amapress' ),
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
								'name'       => __( 'Archivage des contrats et inscriptions', 'amapress' ),
								'menu_title' => __( 'Archivage', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-book',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Archivables', 'amapress' ) => array(
									'id'      => 'contrats_archivables_tab',
									'options' => array(
										array(
											'id'     => 'contrats_archivables',
											'name'   => __( 'Contrats archivables', 'amapress' ),
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
								'name'       => __( 'Emails et rappels', 'amapress' ),
								'menu_title' => __( 'Emails/Rappels', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Emails - Envoi des quantités à livrer (1)', 'amapress' )            => array(
									'id'      => 'amp_tab_recall_quantites_distrib1',
									'desc'    => 'Rappel envoyés aux producteurs et/ou aux référents des quantités à livrer avant chauqe distribution',
									'options' => amapress_contrat_quantites_recall_options(),
								),
								__( 'Emails - Envoi des quantités à livrer (2)', 'amapress' )            => array(
									'id'      => 'amp_tab_recall_quantites_distrib2',
									'desc'    => 'Rappel envoyés aux producteurs et/ou aux référents des quantités à livrer avant chauqe distribution',
									'options' => amapress_contrat_quantites_recall_options( '2' ),
								),
								__( 'Emails - Envoi des quantités à livrer (3)', 'amapress' )            => array(
									'id'      => 'amp_tab_recall_quantites_distrib3',
									'desc'    => 'Rappel envoyés aux producteurs et/ou aux référents des quantités à livrer avant chauqe distribution',
									'options' => amapress_contrat_quantites_recall_options( '3' ),
								),
								__( 'Emails - Contrats à renouveler', 'amapress' )                       => array(
									'id'      => 'amp_tab_recall_contrat_renew',
									'desc'    => 'Rappels de contrats à renouveller envoyés aux référents producteur',
									'options' => amapress_contrat_renew_recall_options(),
								),
								__( 'Emails - Contrats ouverts ou bientôt ouverts', 'amapress' )         => array(
									'id'      => 'amp_tab_recall_contrat_open',
									'desc'    => 'Rappels envoyés aux amapiens avant (ou après) l\'ouverture des inscriptions aux contrats',
									'options' => amapress_contrat_open_recall_options(),
								),
								__( 'Emails - Contrats bientôt fermés', 'amapress' )                     => array(
									'id'      => 'amp_tab_recall_contrat_close',
									'desc'    => 'Rappels envoyés aux amapiens avant la clôture des inscriptions aux contrats',
									'options' => amapress_contrat_close_recall_options(),
								),
								__( 'Emails - Récapitulatif à la clôture des inscriptions', 'amapress' ) => array(
									'id'      => 'amp_tab_recall_contrat_recap_close',
									'desc'    => 'Rappels envoyés à la clôture des inscriptions aux référents et/ou aux producteurs avec différentes possibilités d\'excel des quantités à livrer tout au long du contrat',
									'options' => amapress_contrat_recap_cloture_recall_options(),
								),
								__( 'Emails - Inscriptions à valider', 'amapress' )                      => array(
									'id'      => 'amp_tab_inscr_validate_distrib',
									'desc'    => 'Rappel envoyé aux référents producteur avant chaque distribution pour signaler que des Inscriptions restent à valider',
									'options' => amapress_inscriptions_to_validate_recall_options(),
								),
								__( 'Emails - Envoi liste des règlements', 'amapress' )                  => array(
									'id'      => 'amp_tab_recall_liste_cheques',
									'desc'    => 'Rappel envoyé aux référents producteur avant chaque distribution avec la liste des chèques à remettre',
									'options' => amapress_contrat_paiements_recall_options(),
								),
								__( 'Emails - Envoi rappel remise règlements', 'amapress' )              => array(
									'id'      => 'amp_tab_recall_awaiting_cheques',
									'desc'    => 'Email envoyé à l\'amapien via l\'action Envoyer rappel sur les règlements Inscriptions non encore reçus',
									'options' => array(
										array(
											'id'       => 'paiement-awaiting-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Règlement à remettre pour %%contrat_titre_complet%%', 'amapress' ),
										),
										array(
											'id'      => 'paiement-awaiting-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nUn règlement en %%paiement_type%% d'un montant de %%paiement_montant%%€  est en attente de réception depuis le %%paiement_date%% pour le contrat %%contrat_titre_complet%%. Merci de le remettre au plus vite à  %%tous_referents_contacts%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressAmapien_paiement::getPlaceholdersHelp();
												},
										),
										array(
											'id'           => 'paiement-awaiting-cc',
											'name'         => __( 'Cc', 'amapress' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => __( 'Emails en copie', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
									),
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_gest_contrat_conf_opt_page',
							'settings' => array(
								'name'       => __( 'Configuration', 'amapress' ),
								'menu_title' => __( 'Configuration', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Contrats', 'amapress' )                                  => array(
									'id'      => 'contrat_config',
									'options' => array(
										array(
											'id'      => 'disable_principal',
											'name'    => __( 'AMAP sans contrat obligatoire/principal', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'allow_partial_coadh',
											'name'    => __( 'Autoriser la co-adhésion partielle', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
											'desc'    => __( 'L\'amapien peut choisir les contrats sur lesquels il souhaite un co-adhérent', 'amapress' ),
										),
										array(
											'id'      => 'def_max_cofoy',
											'name'    => __( 'Membres du foyers', 'amapress' ),
											'type'    => 'number',
											'default' => 3,
											'min'     => 0,
											'max'     => 3,
											'unit'    => 'membre(s) du foyer',
											'desc'    => 'Nombre maximum de membres du foyers (par défaut pour les paramètres max_cofoyers des shortcodes relatifs aux informations amapiens et aux adhésions/inscriptions)',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'def_max_coadh',
											'name'    => __( 'Co-adhérents', 'amapress' ),
											'type'    => 'number',
											'default' => 3,
											'min'     => 0,
											'max'     => 3,
											'unit'    => 'co-adhérent(s)',
											'desc'    => 'Nombre maximum de co-adhérents (par défaut pour les paramètres max_coadherents des shortcodes relatifs aux informations amapiens et aux adhésions/inscriptions)',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'coadh_self_adh',
											'name'    => __( 'Les co-adhérents doivent avoir une adhésion séparée', 'amapress' ),
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'          => 'adh-valid',
											'name'        => __( 'Accès inscriptions en ligne aux contrats producteurs', 'amapress' ),
											'type'        => 'radio',
											'options'     => [
												'check_adh_rcv'   => __( 'Adhésion obligatoire : pour tous', 'amapress' ) .
												                     '<br /><span class="description">' .
												                     __( 'Sélectionner pour rendre obligatoire la validation de l’adhésion par le Trésorier pour autoriser les amapiens à s’inscrire aux contrats producteurs', 'amapress' ) .
												                     '</span>',
												'check_adh_rcv_p' => __( 'Adhésion obligatoire : anciens autorisés', 'amapress' ) .
												                     '<br /><span class="description">' .
												                     __( 'Sélectionner pour autoriser les anciens adhérents à s’inscrire aux contrats producteurs, la validation des adhésions reste nécessaire pour les nouveaux', 'amapress' ) .
												                     '</span>',
												'none'            => __( 'Adhésion facultative', 'amapress' ) .
												                     '<br /><span class="description">' .
												                     __( 'Sélectionner pour autoriser les amapiens à s’inscrire aux contrats producteurs sans validation de l’adhésion par le Trésorier', 'amapress' ) .
												                     '</span>',
											],
											'default'     => 'all',
											'custom_get'  => function ( $post_id ) {
												$check_adh_rcv   = Amapress::getOption( 'check_adh_rcv' );
												$check_adh_rcv_p = Amapress::getOption( 'check_adh_rcv_p' );
												if ( $check_adh_rcv_p ) {
													return 'check_adh_rcv_p';
												} elseif ( $check_adh_rcv ) {
													return 'check_adh_rcv';
												} else {
													return 'none';
												}
											},
											'custom_save' => function ( $post_id ) {
												if ( isset( $_REQUEST['amapress_adh-valid'] ) ) {
													switch ( $_REQUEST['amapress_adh-valid'] ) {
														case 'none':
															Amapress::setOption( 'check_adh_rcv', 0 );
															Amapress::setOption( 'check_adh_rcv_p', 0 );
															break;
														case 'check_adh_rcv_p':
															Amapress::setOption( 'check_adh_rcv', 0 );
															Amapress::setOption( 'check_adh_rcv_p', 1 );
															break;
														case 'check_adh_rcv':
															Amapress::setOption( 'check_adh_rcv', 1 );
															Amapress::setOption( 'check_adh_rcv_p', 0 );
															break;
													}
												}

												return true;
											}
										),
										array(
											'id'      => 'mob_phone_req',
											'name'    => __( 'Numéro de portable', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
											'desc'    => __( 'Rendre le numéro de téléphone mobile obligatoire', 'amapress' ),
										),
										array(
											'id'      => 'before_close_hours',
											'name'    => __( 'Clôture des inscriptions', 'amapress' ),
											'type'    => 'number',
											'default' => 24,
											'min'     => 0,
											'unit'    => 'heure(s)',
											'desc'    => 'Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant le jour de distribution',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'adhesion_shift_weeks',
											'name'    => __( 'Décalage de la période d\'adhésion', 'amapress' ),
											'type'    => 'number',
											'default' => 0,
											'min'     => 0,
											'unit'    => 'semaine(s)',
											'desc'    => 'Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'renouv_days',
											'name'    => __( 'Durée en jour de la période de renouvellement', 'amapress' ),
											'type'    => 'number',
											'default' => 30,
											'min'     => 1,
											'unit'    => 'jours',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'type' => 'note',
											'desc' => function ( $o ) {
												return sprintf( __( 'Le renouvellement des contrats se fait dans %s', 'amapress' ), Amapress::makeLink( admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ), __( 'Tableau de bord>Gestion Contrats> Edition', 'amapress' ) ) );
											}
										),
										array(
											'id'      => 'archive_months',
											'name'    => __( 'Délai d\'archivage minimum', 'amapress' ),
											'type'    => 'number',
											'unit'    => 'mois',
											'default' => 3,
											'min'     => 1,
											'desc'    => __( 'Délai en mois après la fin d\'un contrat après lequel l\'archivage devient possible', 'amapress' ),
//                                            'capability' => 'manage_amapress',
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Assistant - Inscription en ligne - Etapes', 'amapress' ) => array(
									'id'      => 'config_online_inscriptions_messages',
									'desc'    => __( 'Configuration de l\'assistant d\'inscription en ligne (inscription-en-ligne-connecte/inscription-en-ligne).<br/>', 'amapress' ) . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' ), __( 'Aller à la configuration de l\'adhésion', 'amapress' ) ),
									'options' => [
										array(
											'id'       => 'online_subscription_start_saison_message',
											'name'     => __( 'Label email', 'amapress' ),
											'type'     => 'text',
											'default'  => '',
											'sanitize' => false,
											'desc'     => function ( $option ) {
												return __( 'Label du champ email (non connecté) (shortcode [inscription-en-ligne]), par défaut, "Pour démarrer votre inscription pour la saison xxx, veuillez renseigner votre adresse mail :"', 'amapress' )
												       . AmapressAdhesionPeriod::getPlaceholdersHelp();
											},
										),
										array(
											'id'      => 'online_subscription_welcome_inscr_message',
											'name'    => __( 'Message de bienvenue', 'amapress' ),
											'type'    => 'text',
											'default' => '',
											'desc'    => __( 'Message de bienvenue (non connecté) (shortcode [inscription-en-ligne]), par défaut, "Bienvenue dans l\'assistant d\'inscription aux contrats producteurs de « AMAP »"', 'amapress' ),
										),
										array(
											'type' => 'heading',
											'name' => __( 'Message aux adhérents principaux', 'amapress' ),
										),
										array(
											'id'      => 'online_principal_user_message',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => '',
											'desc'    => __( 'Message aux adhérents principaux sur les Etapes 2/Coordonnées et 4/Contrats', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Message aux co-adhérents', 'amapress' ),
										),
										array(
											'id'      => 'online_coadh_user_message',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => '',
											'desc'    => __( 'Message aux co-adhérents sur les Etapes 2/Coordonnées et 4/Contrats', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Message - Etape 4/8 - Les contrats', 'amapress' ),
										),
										array(
											'id'      => 'online_contrats_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => '',
											'desc'    => __( '<strong>Nom de remplacement</strong> de l\'étape 4/8 - Les contrats ; par défaut, "Les contrats" ou "Les commandes" suivant l\'argument use_contrat_term', 'amapress' ),
										),
										array(
											'id'      => 'online_contrats_step_message',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => '',
											'desc'    => function ( $option ) {
												return __( 'Message supplémentaire à l\'étape 4/8 - Les contrats<br/>', 'amapress' ) .
												       Amapress::getPlaceholdersHelpTable( 'online_contrats_step_message-placeholders', [], 'user:de l\'amapien', [], false );
											},
										),
										array(
											'id'      => 'online_inscr_adhesion_required_message',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( 'Votre adhésion doit être validée avant que vous puissiez vous inscrire aux contrats.', 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Message supplémentaire à l\'étape 4/8 - Les contrats <strong>si une adhésion validée ou antérieure est nécessaire pour pouvoir s\'inscrire aux contrats</strong><br/>', 'amapress' ) .
												       Amapress::getPlaceholdersHelpTable( 'online_inscr_adhesion_required_message-placeholders', [], null, [], false );
											},
										),
										array(
											'id'      => 'online_inscr_closed_message',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => '',
											'desc'    => __( 'Message supplémentaire lorsque les inscriptions en ligne sont closes (toutes les étapes)', 'amapress' ),
										),
										array(
											'id'       => 'online_subscription_contrat_avail_format',
											'name'     => __( 'Label contrat disponible', 'amapress' ),
											'type'     => 'text',
											'default'  => '',
											'sanitize' => false,
											'desc'     => function ( $option ) {
												return __( 'Label des contrats disponibles. Par défaut, "nom du contrat - mois début ~ mois fin"', 'amapress' )
												       . AmapressContrat_instance::getPlaceholdersHelp();
											},
										),
										array(
											'id'       => 'online_subscription_inscription_format',
											'name'     => __( 'Label inscription', 'amapress' ),
											'type'     => 'text',
											'default'  => '',
											'sanitize' => false,
											'desc'     => function ( $option ) {
												return __( 'Label des inscriptions des amapiens (par ex, dans [mes-contrats]). Par défaut, "amapien - nom du contrat - mois début ~ mois fin - date début > date fin (lieu) (numéro)"', 'amapress' )
												       . AmapressContrat_instance::getPlaceholdersHelp();
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape 7/8 - Date et lieu', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_date_lieu_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Date et lieu', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_date_lieu_step_message',
											'name' => __( 'Message supplémentaire', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_date_lieu_step_message-placeholders', [], null, [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape 6/8 - Panier', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_panier_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Panier', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_panier_step_message',
											'name' => __( 'Message supplémentaire', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_panier_step_message-placeholders', [], null, [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape 7/8 - Règlement', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_pay_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Règlement', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_pay_step_message',
											'name' => __( 'Message supplémentaire', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_pay_step_message-placeholders', [], 'user:de l\'amapien', [], false );
											},
										),
										array(
											'id'      => 'online_subscription_pay_num_label',
											'name'    => __( 'Saisie numéros de chèques', 'amapress' ),
											'type'    => 'text',
											'desc'    => __( 'Intitulé des champs de saisie des numéros de chèques', 'amapress' ),
											'default' => __( 'Numéro chèque', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Message - Etape 8/8 Félicitations', 'amapress' ),
										),
										array(
											'id'      => 'online_contrats_end_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Félicitations !', 'amapress' ),
										),
										array(
											'id'      => 'online_contrats_end_confirm_msg',
											'name'    => __( 'Message de confirmation', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( 'Votre pré-inscription a bien été prise en compte.', 'amapress' ) ),
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_contrats_end_confirm_msg-placeholders',
													AmapressAdhesion::getPlaceholders(), 'user:de l\'amapien', [], false );
											},
										),
										array(
											'id'      => 'online_contrats_end_continue_msg',
											'name'    => __( 'Message de confirmation', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( 'Vous pouvez également découvrir et éventuellement adhérer aux contrats : (%%remaining_contrats%%)', 'amapress' ) ),
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_contrats_end_continue_msg-placeholders', [
													'remaining_contrats'      => __( 'Contrat disponibles à l\'inscription', 'amapress' ),
													'remaining_contrats_list' => __( 'Contrat disponibles à l\'inscription (en liste)', 'amapress' )
												], 'user:de l\'amapien', [], false );
											},
										),
										array(
											'id'      => 'online_contrats_end_confirm_mail_msg',
											'name'    => __( 'Message au sujet de l\'email de confirmation', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( 'Vous allez recevoir un email de confirmation avec votre contrat dans quelques minutes. (Pensez à regarder vos spams, cet email peut s\'y trouver à cause du contrat joint ou pour expéditeur inconnu de votre carnet d\'adresses)', 'amapress' ) ),
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_contrats_end_confirm_mail_msg-placeholders',
													AmapressAdhesion::getPlaceholders(), 'user:de l\'amapien', [], false );
											},
										),
										array(
											'id'      => 'online_contrats_end_step_message',
											'name'    => __( 'Inscription terminée', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Pour finaliser votre inscription, vous devez imprimer ce contrat et le remettre aux référents concernés (%%tous_referents%%) avec les règlements correspondants lors de la prochaine distribution\n%%print_button%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Instruction en fin d\'inscription à l\'étape 8/8 pour chaque inscription<br/>Le placeholder %%print_button%% permet d\'afficher le bouton Imprimer le contrat<br/>', 'amapress' ) .
												       Amapress::getPlaceholdersHelpTable( 'online_contrats_end_step_message-placeholders',
													       AmapressAdhesion::getPlaceholders(), 'user:de l\'amapien', [
														       'print_button' => __( 'Bouton Imprimer/Télécharger le contrat', 'amapress' )
													       ], false );
											},
										),
										array(
											'id'      => 'online_contrats_end_step_edit_message',
											'name'    => __( 'Inscription terminée - Possibilité édition/annulation', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Veuillez vérifier le contrat : %%print_button%%\nSi vous constatez une erreur, vous pouvez modifier votre inscription : %%modify_button%%\nVous pouvez également l'annuler : %%cancel_button%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return 'Instruction pour l\'édition ou l\'annulation à la fin d\'inscription à l\'étape 8/8 pour chaque inscription<br/>Les placeholders %%print_button%%, %%modify_button%%, %%cancel_button%% permettent d\'afficher respectivement le bouton Imprimer/Télécharger, Modifier et Annuler l\inscription<br/>' .
												       Amapress::getPlaceholdersHelpTable( 'online_contrats_end_step_edit_message-placeholders',
													       AmapressAdhesion::getPlaceholders(), 'user:de l\'amapien', [
														       'print_button'  => __( 'Bouton Imprimer/Télécharger le contrat', 'amapress' ),
														       'modify_button' => __( 'Bouton Modifier l\'inscription', 'amapress' ),
														       'cancel_button' => __( 'Bouton Annuler l\'inscription', 'amapress' )
													       ], false );
											},
										),
										array(
											'id'      => 'online_contrats_inscription_distrib_msg',
											'name'    => __( 'Message inscription aux distributions', 'amapress' ),
											'type'    => 'editor',
											'default' => '',
											'desc'    => function ( $option ) {
												return __( 'Message au sujet des inscriptions nécessaires en tant que responsable de distribution<br/>', 'amapress' ) .
												       Amapress::getPlaceholdersHelpTable( 'online_contrats_inscription_distrib_msg-placeholders', [
													       'nb_inscriptions'    => __( 'Nombre d\'inscription comme responsable de distribution sur la période à venir', 'amapress' ),
													       'dates_inscriptions' => __( 'Dates d\'inscription comme responsable de distribution sur la période à venir', 'amapress' )
												       ], 'user:de l\'amapien', [], false );
											},
										),
										array(
											'id'      => 'online_final_step_name',
											'name'    => __( 'Nom de l\'étape finale', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Félicitations, vous avez terminé vos inscriptions !', 'amapress' ),
										),
										array(
											'id'      => 'online_final_step_message',
											'name'    => __( 'Message final', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( "Si vous êtes nouvel adhérent vous allez recevoir un email vous indiquant comment vous connecter au site et choisir votre mot de passe.\n
Vous allez recevoir un email de confirmation pour chacune de vos inscriptions avec le contrat à imprimer et les instructions pour remettre vos chèques/règlements aux référents.\n
(Pensez à regarder vos spams, ces emails peuvent s\'y trouver à cause des contrats joints ou pour expéditeur inconnu de votre carnet d\'adresses)\n
Vous pouvez maintenant fermer cette fenêtre/onglet et regarder votre messagerie" ),
											'desc'    => __( 'Message à l\'amapien à la fin de toutes ses inscriptions', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Paiements en ligne (Stripe)', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_contrat_end_stripe',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( "Pour confirmer votre inscription, veuillez procéder au paiement en ligne. La modification de l\'inscription ne sera plus possible ensuite." ),
											'desc'    => function ( $option ) {
												return __( 'Message au sujet des paiements en ligne à l\'étape de validation de l\'inscription', 'amapress' ) . '<br/>' . AmapressAdhesion::getPlaceholdersHelp();
											},
										),
										array(
											'id'      => 'online_subscription_stripe_success',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Merci pour votre réglement.\nVotre inscription %%post:title%% est confirmée.\n%%print_button%%\n\n%%contrats_step_link%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Message de confirmation de paiement en ligne', 'amapress' ) . '<br/>' . AmapressAdhesion::getPlaceholdersHelp(
														[
															'contrats_step_link' => __( 'Lien vers l\'étape Mes contrats', 'amapress' ),
															'contrats_step_href' => __( 'Url de l\'étape Mes contrats', 'amapress' ),
															'print_button'       => __( 'Bouton Imprimer/Télécharger le contrat', 'amapress' ),
														]
													);
											},
										),
										array(
											'id'      => 'online_subscription_stripe_cancel',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Vous avez annulé le règlement en ligne.\nVotre inscription %%post:title%% n'est pas encore confirmée.\n\n%%contrats_step_link%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Message d\'annulation de paiement en ligne', 'amapress' ) . '<br/>' . AmapressAdhesion::getPlaceholdersHelp(
														[
															'contrats_step_link' => __( 'Lien vers l\'étape Mes contrats', 'amapress' ),
															'contrats_step_href' => __( 'Url de l\'étape Mes contrats', 'amapress' ),
														]
													);
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'CSS Personnalisé', 'amapress' ),
										),
										array(
											'id'      => 'online_inscr_css',
											'name'    => __( 'CSS', 'amapress' ),
											'type'    => 'textarea',
											'default' => '',
											'desc'    => __( 'CSS additionnel (hors shortcode mes-contrats), par exemple, pour masquer les entêtes et menu', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
									]
								),
								__( 'Assistant - Inscription en ligne - Emails', 'amapress' ) => array(
									'id'      => 'config_online_inscriptions_mails',
									'desc'    => __( 'Configuration des emails de l\'assistant d\'inscription en ligne (inscription-en-ligne-connecte/inscription-en-ligne).', 'amapress' ) . '<br/>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_mails' ), __( 'Aller à la configuration de l\'adhésion', 'amapress' ) ),
									'options' => [
										array(
											'type' => 'heading',
											'name' => __( 'Emails - Confirmation Inscription Contrat', 'amapress' ),
										),
										array(
											'id'       => 'online_subscription_confirm-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Confirmation de votre inscription au contrat %%contrat_titre_complet%% à partir du %%date_debut_complete%%', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_confirm-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour %%user:nom_complet%%,\nNous vous confirmons votre inscription au contrat %%contrat_titre%% 
									\n-> du %%date_debut_complete%% au %%date_fin_complete%% 
									\n-> pour %%nb_distributions%% distributions
									\n-> quantités : %%quantites%%
									\n-> pour un montant de %%total%%€
									\n[avec_contrat]Merci d'imprimer le contrat joint à cet email et le remettre aux référents (%%referents%%) avec %%option_paiements%% à la première distribution[/avec_contrat]
									[sans_contrat]Merci de contacter les référents (%%referents%%) avec %%option_paiements%% à la première distribution pour signer votre contrat[/sans_contrat]
									\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les syntaxes [avec_contrat]xxx[/avec_contrat] et [sans_contrat]xxx[/sans_contrat] permettent de cibler le texte respectivement lorsqu\'un contrat Word est attaché ou non.<br />Les placeholders suivants sont disponibles:', 'amapress' ) .
												       AmapressAdhesion::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Emails - Notification Référents Nouvelle Inscription Contrat', 'amapress' ),
										),
										array(
											'id'       => 'online_subscription_referents-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Nouvelle inscription - %%contrat_titre_complet%% - %%adherent%%', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_referents-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nUne nouvelle inscription est en attente de validation : %%inscription_admin_link%%\n-> du %%date_debut_complete%% au %%date_fin_complete%%\n-> pour %%nb_distributions%% distributions\n-> quantités : %%quantites%%\n-> pour un montant de %%total%%€\n\nMessage de l'amapien: %%message%%\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
												       AmapressAdhesion::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Emails - Notification Référents Modification Inscription Contrat', 'amapress' ),
										),
										array(
											'id'       => 'online_subscription_referents_modif-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Inscription modifiée - %%contrat_titre_complet%% - %%adherent%%', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_referents_modif-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nUne inscription a été modifiée et est en attente de validation : %%inscription_admin_link%%\n-> du %%date_debut_complete%% au %%date_fin_complete%%\n-> pour %%nb_distributions%% distributions\n-> quantités : %%quantites%%\n-> pour un montant de %%total%%€\n\nMessage de l'amapien: %%message%%\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
												       AmapressAdhesion::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Emails - Notification Référents Annulation Inscription Contrat', 'amapress' ),
										),
										array(
											'id'       => 'online_subscription_referents_cancel-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Annulation inscription - %%contrat_titre_complet%% - %%adherent%%', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_referents_cancel-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nUne inscription a été annulée\n-> du %%date_debut_complete%% au %%date_fin_complete%%\n-> pour %%nb_distributions%% distributions\n-> quantités : %%quantites%%\n-> pour un montant de %%total%%€\n\nMessage de l'amapien: %%message%%\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les placeholders suivants sont disponibles (hormis %%inscription_admin_link%% car l\'inscription est supprimée suite à l\'envoi de ce mail):', 'amapress' ) .
												       AmapressAdhesion::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
									]
								),
								__( 'Contrat Word (DOCX) général', 'amapress' )               => array(
									'id'      => 'config_default_contrat_docx',
									'options' => [
										array(
											'type' => 'note',
											'desc' => function ( $o ) {
												return sprintf( __( '
                                    <h4>Modèles génériques de contrats</h4>
									<p>Vous pouvez télécharger ci-dessous l\'un des modèles DOCX génériques utilisables comme contrat personnalisé et vierge :</p>
                                    <ul style="list-style-type: disc; padding-left: 1em">
                                    <li><a target="_blank" href="%s">modèle générique (paniers en tableau)</a></li>
                                    <li><a target="_blank" href="%s">modèle générique simple (paniers en texte)</a></li>
                                    <li><a target="_blank" href="%s">modèle générique pour les paniers modulables</a></li>
                                    <li><a target="_blank" href="%s">modèle générique pour les paniers modulables avec résumé, détails et groupes</a></li>
                                    </ul>
                                    <h4>Configuration des modèles par défaut</h4>
									<p>Vous pouvez configurer les modèles DOCX par défaut pour tous les contrats sans modèle spécifique.</p>
									<p>La procédure est la suivante: <ul style="list-style-type: decimal; padding-left: 1em">
									<li>Téléchargez le <a target="_blank" href="%s">modèle générique</a></li>
									<li>changez le logo d\'entête</li>
									<li>personnalisez les engagements</li>
									<li>uploadez votre fichier DOCX modifié dans les deux champs ci-dessous</li>
									<li>enregistrez</li>
									<li>Si vous avez des contrats avec paniers modulables, recommencez cette procédure avec le <a target="_blank" href="%s">modèle générique paniers modulables</a> ou le <a target="_blank" href="%s">modèle complet</a></li>
									<li>Si vous avez des contrats avec paniers modulables <em>avec groupes de produits</em>, recommencez cette procédure avec le <a target="_blank" href="%s">modèle générique paniers modulables avec groupes</a></li>
									</ul></p>
									<p>Votre AMAP est prête pour la génération/remplissage automatique des contrats</p>', 'amapress' ), esc_attr( Amapress::getContratGenericUrl() ), esc_attr( Amapress::getContratGenericUrl( 'simple' ) ), esc_attr( Amapress::getContratGenericUrl( 'modulables' ) ), esc_attr( Amapress::getContratGenericUrl( 'modulables_complet' ) ), esc_attr( Amapress::getContratGenericUrl() ), esc_attr( Amapress::getContratGenericUrl( 'modulables' ) ), esc_attr( Amapress::getContratGenericUrl( 'modulables_complet' ) ), esc_attr( Amapress::getContratGenericUrl( 'modulables_complet' ) ) );
											},
										),
										array(
											'id'              => 'default_word_model',
											'name'            => __( 'Contrat personnalisé par défaut', 'amapress' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
											'selector-title'  => __( 'Sélectionnez/téléversez un modèle de contrat papier DOCX', 'amapress' ),
											'desc'            => function ( $o ) {
												return sprintf( __( 'Configurer un modèle de contrat (par défaut pour tous les contrats sans modèle spécifique) à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="%s">Plus d\'info</a>)', 'amapress' ),
													admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ) );
											},
										),
										array(
											'id'              => 'default_word_paper_model',
											'name'            => __( 'Contrat vierge par défaut', 'amapress' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
											'selector-title'  => __( 'Sélectionnez/téléversez un modèle de contrat personnalisé DOCX', 'amapress' ),
											'desc'            => __( 'Générer un contrat vierge (par défaut pour tous les contrats sans modèle spécifique) à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="', 'amapress' ) . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Pour les contrats avec paniers modulables', 'amapress' ),
										),
										array(
											'id'              => 'default_word_modulable_model',
											'name'            => __( 'Contrat personnalisé "paniers modulables" par défaut', 'amapress' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
											'selector-title'  => __( 'Sélectionnez/téléversez un modèle de contrat papier DOCX', 'amapress' ),
											'desc'            => function ( $o ) {
												return sprintf( __( 'Configurer un modèle de contrat "paniers modulables" (par défaut pour tous les contrats sans modèle spécifique) à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="%s">Plus d\'info</a>)', 'amapress' ), admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ) );
											},
										),
										array(
											'id'              => 'default_word_modulable_paper_model',
											'name'            => __( 'Contrat vierge "paniers modulables" par défaut', 'amapress' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
											'selector-title'  => __( 'Sélectionnez/téléversez un modèle de contrat personnalisé DOCX', 'amapress' ),
											'desc'            => __( 'Générer un contrat vierge "paniers modulables" (par défaut pour tous les contrats sans modèle spécifique) à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="', 'amapress' ) . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Pour les contrats avec paniers modulables avec groupes de produits', 'amapress' ),
										),
										array(
											'id'              => 'default_word_modulable_group_model',
											'name'            => __( 'Contrat personnalisé "paniers modulables avec groupes" par défaut', 'amapress' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
											'selector-title'  => __( 'Sélectionnez/téléversez un modèle de contrat papier DOCX', 'amapress' ),
											'desc'            => function ( $o ) {
												return sprintf( __( 'Configurer un modèle de contrat "paniers modulables avec groupes" (par défaut pour tous les contrats avec groupes sans modèle spécifique) à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="%s">Plus d\'info</a>)', 'amapress' ), admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_contrat_placeholders' ) );
											},
										),
										array(
											'id'              => 'default_word_modulable_group_paper_model',
											'name'            => __( 'Contrat vierge "paniers modulables avec groupe" par défaut', 'amapress' ),
											'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											'type'            => 'upload',
											'show_column'     => false,
											'show_download'   => true,
											'show_title'      => true,
											'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
											'selector-title'  => __( 'Sélectionnez/téléversez un modèle de contrat personnalisé DOCX', 'amapress' ),
											'desc'            => __( 'Générer un contrat vierge "paniers modulables avec groupes" (par défaut pour tous les contrats avec groupes sans modèle spécifique) à partir d’un contrat papier existant (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="', 'amapress' ) . admin_url( 'admin.php?page=amapress_help_page&tab=paper_contrat_placeholders' ) . '">Plus d\'info</a>)',
										),
										array(
											'type' => 'save',
										),
									]
								),
							),
						),
						array(
							'title'      => __( 'Producteurs', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Producteurs', 'amapress' ),
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
						'name'       => __( 'Gestion Adhésions', 'amapress' ),
						'position'   => '29',
						'capability' => 'edit_adhesion_paiement',
						'icon'       => 'dashicons-none flaticon-pen',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								__( 'Dans cette section vous pouvez gérer les adhésions/cotisation à votre AMAP :', 'amapress' ),
								'',
								array(
									[
										'capability' => '',
										'item'       => sprintf( __( 'les <a target="_blank" href="%s">adhésions</a>, leurs <a target="_blank" href="%s">règlements</a> et la répartition des types de cotisations', 'amapress' ), admin_url( 'admin.php?page=adhesion_paiements&amapress_contrat=active' ), admin_url( 'edit.php?post_type=amps_adh_pmt&amapress_date=active' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'la <a target="_blank" href="%s">période d\'adhésion</a> et le bulletin sous format Word/DOCX', 'amapress' ), admin_url( 'edit.php?post_type=amps_adh_per&amapress_date=active' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'les <a target="_blank" href="%s">types de cotisations</a>', 'amapress' ), admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ) )
									],
								),
								sprintf( __( '<p>L\'association des deux <a target="_blank" href="%s">types de cotisation</a> par défaut (AMAP et Réseau AMAP) se fait dans <a target="_blank" href="%s">Tableau de bord&gt;Gestion Adhésions&gt;Configuration, onglet Paiements</a></p>', 'amapress' ), admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ), admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=amp_paiements_config' ) ),
								''
							);
						}
					),
					'options'  => array(),
					'tabs'     => array(
						__( 'Ajouter une adhésion', 'amapress' )           => array(
							'id'         => 'add_adhesion',
							'use_form'   => false,
							'use_table'  => false,
							'capability' => 'edit_adhesion_paiement',
							'options'    => array(
								array(
									'id'         => 'add_user_adhes',
									'type'       => 'custom',
									'bare'       => true,
									'capability' => 'edit_adhesion_paiement',
									'custom'     => 'amapress_create_user_and_adhesion_assistant',
								)
							),
						),
						__( 'Ajouter un coadhérent', 'amapress' )          => array(
							'id'         => 'add_coadherent',
							'use_form'   => false,
							'use_table'  => false,
							'capability' => 'edit_adhesion_paiement',
							'options'    => array(
								array(
									'id'         => 'add_user_adh_coinscr',
									'type'       => 'custom',
									'bare'       => true,
									'capability' => 'edit_adhesion_paiement',
									'custom'     => 'amapress_create_coadhesion_assistant',
								)
							),
						),
						__( 'Ajouter une personne hors AMAP', 'amapress' ) => array(
							'id'         => 'add_other_adh_user',
							'use_form'   => false,
							'use_table'  => false,
							'capability' => 'edit_adhesion_paiement',
							'options'    => array(
								array(
									'id'         => 'add_user_adh_other',
									'type'       => 'custom',
									'bare'       => true,
									'capability' => 'edit_adhesion_paiement',
									'custom'     => 'amapress_create_user_for_distribution',
								)
							),
						),
					),
					'subpages' => array(
						array(
							'title'      => __( 'Etat des règlements Adhésions', 'amapress' ),
							'menu_icon'  => 'dashicons-none flaticon-business',
							'menu_title' => __( 'Synthèse', 'amapress' ),
							'capability' => 'edit_adhesion_paiement',
							'post_type'  => 'adhesion_paiements',
							'slug'       => 'adhesion_paiements',
							'function'   => 'amapress_render_adhesion_list',
							'hook'       => 'amapress_adhesion_list_options',
						),
						array(
							'title'      => __( 'Encaissements des règlements Adhésions', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Règlements', 'amapress' ),
							'post_type'  => 'amps_adh_pmt',
							'capability' => 'edit_adhesion_paiement',
							'slug'       => 'edit.php?post_type=amps_adh_pmt&amapress_date=active',
						),
						array(
							'title'      => __( 'Répartitions bénéficiaires', 'amapress' ),
							'menu_icon'  => 'dashicons-tag',
							'menu_title' => __( 'Types de paiement', 'amapress' ),
							'capability' => 'edit_adhesion_paiement',
							'post_type'  => 'amps_paiement_category',
							'slug'       => 'edit-tags.php?taxonomy=amps_paiement_category',
						),
						array(
							'title'      => __( 'Périodes Adhésions', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Edition', 'amapress' ),
							'post_type'  => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
							'capability' => 'edit_adhesion_period',
							'slug'       => 'edit.php?post_type=amps_adh_per&amapress_date=active',
						),
						array(
							'subpage'  => true,
							'id'       => 'adh_period_archives',
							'settings' => array(
								'name'       => __( 'Archivage des adhésions et périodes d\'adhésion', 'amapress' ),
								'menu_title' => __( 'Archivage', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-book',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Archivables', 'amapress' ) => array(
									'id'      => 'adh_period_archivables_tab',
									'options' => array(
										array(
											'id'     => 'adh_period_archivables',
											'name'   => __( 'Périodes d\'adhésion archivables', 'amapress' ),
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
								'name'       => __( 'Configuration', 'amapress' ),
								'menu_title' => __( 'Configuration', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Types de cotisation', 'amapress' )                    => array(
									'id'      => 'amp_paiements_config',
									'options' => array(
										array(
											'id'         => 'adhesion_amap_term',
											'name'       => __( 'Catégorie Adhésion AMAP', 'amapress' ),
											'taxonomy'   => 'amps_paiement_category',
											'type'       => 'select-categories',
											'capability' => 'edit_contrat_paiement',
										),
										array(
											'id'         => 'adhesion_reseau_amap_term',
											'name'       => __( 'Catégorie Adhésion Réseau AMAP', 'amapress' ),
											'taxonomy'   => 'amps_paiement_category',
											'type'       => 'select-categories',
											'capability' => 'edit_contrat_paiement',
										),
//                        array(
//                            'id' => 'adhesion_contrat_term',
//                            'name' => __('Catégorie Adhésion Contrat', 'amapress'),
//                            'taxonomy' => 'amps_paiement_category',
//                            'type' => 'select-categories',
//                            'capability' => 'edit_contrat_paiement',
//                        ),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Assistant - Adhésion en ligne - Etapes', 'amapress' ) => array(
									'id'      => 'config_online_adhesions_messages',
									'desc'    => __( 'Configuration de l\'assistant d\'adhésion en ligne (adhesion-en-ligne-connecte/adhesion-en-ligne).', 'amapress' ) . '<br/>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), __( 'Aller à la configuration de l\'inscription', 'amapress' ) ),
									'options' => array(
										array(
											'id'       => 'online_subscription_start_saison_adh_message',
											'name'     => __( 'Label email', 'amapress' ),
											'type'     => 'text',
											'default'  => '',
											'sanitize' => false,
											'desc'     => function ( $option ) {
												return __( 'Label du champ email (non connecté) (shortcode [adhesion-en-ligne]), par défaut, "Pour démarrer votre adhésion pour la saison xxx, veuillez renseigner votre adresse mail :"', 'amapress' )
												       . AmapressAdhesionPeriod::getPlaceholdersHelp();
											},
										),
										array(
											'id'      => 'online_subscription_welcome_adh_message',
											'name'    => __( 'Message de bienvenue', 'amapress' ),
											'type'    => 'text',
											'default' => '',
											'desc'    => __( 'Message de bienvenue (non connecté) (shortcode [adhesion-en-ligne]), par défaut, "Bienvenue dans l\'assistant d\'adhésion de « AMAP »"', 'amapress' ),
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape 1 - Email', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_email_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Email', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_email_step_message',
											'name' => __( 'Message supplémentaire', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_email_step_message-placeholders', [], null, [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape 2 - Coordonnées, co-adhérents et membres du foyer', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_coords_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Coordonnées, co-adhérents et membres du foyer', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_coords_step_message',
											'name' => __( 'Message supplémentaire', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_coords_step_message-placeholders', [], null, [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape Règlement intérieur de l\'AMAP', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_agreement_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Charte et règlement intérieur de l\'AMAP', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_agreement_step_checkbox',
											'name'    => __( 'Texte de la case à cocher', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'J\'ai pris connaissance du règlement et l\'accepte', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_agreement',
											'name' => __( 'Contenu du règlement intérieur et Contenu de la Charte des AMAPS', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_agreement-placeholders', [], 'user:de l\'amapien', [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Message - Questions aux nouveaux amapiens (Etapes 2 - Coordonnées)', 'amapress' ),
										),
										array(
											'id'      => 'online_new_user_quest1',
											'name'    => __( 'Question 1', 'amapress' ),
											'type'    => 'editor',
											'default' => '',
											'desc'    => __( 'Question au nouvel amapien (par ex, comment avez-vous connu l\'AMAP)', 'amapress' ),
										),
										array(
											'id'      => 'online_new_user_quest2',
											'name'    => __( 'Question 2', 'amapress' ),
											'type'    => 'editor',
											'default' => '',
											'desc'    => __( 'Question au nouvel amapien', 'amapress' ),
										),
										array(
											'id'       => 'online_new_user_quest_email',
											'name'     => __( 'Réponses à', 'amapress' ),
											'type'     => 'text',
											'is_email' => true,
											'desc'     => __( 'Envoyer les réponses à l\'email renseigné', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Message - Message aux amapiens qui ne renouvelent pas', 'amapress' ),
										),
										array(
											'id'      => 'online_norenew_message',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => '<p>' . __( 'Merci pour votre participation à %%site_name%% et bonne continuation.', 'amapress' ) . '</p>',
											'desc'    => __( 'Message aux amapiens qui ne renouvelent pas', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape Adhésion AMAP', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_req_adhesion',
											'name'    => __( 'Message adhésion requise', 'amapress' ),
											'type'    => 'editor',
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_req_adhesion-placeholders', [], 'user:de l\'amapien', [], false );
											},
											'default' => '<p><strong>' . __( 'Pour vous engager dans l’AMAP et pouvoir s\'inscrire aux contrats disponibles, vous devez adhérer à notre Association.', 'amapress' ) . '</strong></p>',
										),
										array(
											'id'      => 'online_subscription_adh_button_text',
											'name'    => __( 'Texte du bouton Adhérer', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Adhérer', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_adh_hla_button_text',
											'name'    => __( 'Texte du bouton Adhérer via HelloAsso', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Adhérer via HelloAsso', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_adh_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Adhésion (obligatoire)', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_adh_num_label',
											'name'    => __( 'Saisie numéros de chèques', 'amapress' ),
											'type'    => 'text',
											'desc'    => __( 'Intitulé du champs de saisie du numéro de chèque/virement', 'amapress' ),
											'default' => __( 'Numéro de chèque/virement :', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_adh_valid_step_name',
											'name'    => __( 'Nom de l\'étape de validation', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Validation du Bulletin d\'adhésion', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_greating_adhesion',
											'name'    => __( 'Contenu du message de validation', 'amapress' ),
											'type'    => 'editor',
											'desc'    => function ( $option ) {
												return __( 'Le placeholder %%print_button%% permet d\'afficher le bouton Imprimer le bulletin', 'amapress' ) . '<br/>' . Amapress::getPlaceholdersHelpTable( 'online_subscription_greating_adhesion-placeholders',
														AmapressAdhesion_paiement::getPlaceholders(), 'user:de l\'amapien', [
															'print_button' => __( 'Bouton Imprimer le bulletin', 'amapress' )
														], false );
											},
											'default' => wpautop( __( "Merci pour votre adhésion à l'AMAP !\nUn courriel de confirmation vient de vous être envoyé. Pensez à consulter les éléments indésirables.\nVeuillez imprimer le bulletin et le remettre avec votre chèque/règlement à l'ordre de l'AMAP lors de la première distribution.\n%%print_button%%", 'amapress' ) ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Message - Cotisation des co-adhérents', 'amapress' ),
										),
										array(
											'id'      => 'online_adhesion_coadh_message',
											'name'    => __( 'Message', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( 'Les co-adhérents qui ne font pas partie du même foyer doivent régler la cotisation de l’adhésion à l\'AMAP par foyer', 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Message au sujet des adhésions des co-adhérents<br/>', 'amapress' ) .
												       Amapress::getPlaceholdersHelpTable( 'online_adhesion_coadh_message-placeholders', [], null, [], false );
											},
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Assistant - Adhésion en ligne - Emails', 'amapress' ) => array(
									'id'      => 'config_online_adhesions_mails',
									'desc'    => __( 'Configuration des emails de l\'assistant d\'adhésion en ligne (adhesion-en-ligne-connecte/adhesion-en-ligne).', 'amapress' ) . '<br/>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_mails' ), __( 'Aller à la configuration de l\'inscription', 'amapress' ) ),
									'options' => array(
										array(
											'type' => 'heading',
											'name' => __( 'Confirmation à l\'Adhérent', 'amapress' ),
										),
										array(
											'id'       => 'online_adhesion_confirm-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Confirmation de votre adhésion à %%nom_site%%', 'amapress' ),
										),
										array(
											'id'      => 'online_adhesion_confirm-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour %%user:nom_complet%%,\n\n
Nous vous confirmons votre adhésion à %%nom_site%%\n
[avec_bulletin]Merci d'imprimer le bulletin joint à cet email et le remettre aux trésoriers (%%tresoriers%%) avec votre chèque de %%montant%% à la première distribution[/avec_bulletin]
[sans_bulletin]Merci de contacter les trésoriers (%%tresoriers%%) avec votre chèque de %%total%% à la première distribution pour signer votre bulletin[/sans_bulletin]
\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les syntaxes [avec_bulletin]xxx[/avec_bulletin] et [sans_bulletin]xxx[/sans_bulletin] permettent de cibler le texte respectivement lorsqu\'un contrat Word est attaché ou non.<br />Les placeholders suivants sont disponibles:', 'amapress' ) .
												       AmapressAdhesion_paiement::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Notification au trésorier', 'amapress' ),
										),
										array(
											'id'       => 'online_adhesion_notif-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Nouvelle adhésion de %%adherent%% (%%option_paiements%%)', 'amapress' ),
										),
										array(
											'id'      => 'online_adhesion_notif-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\n
Une nouvelle adhésion de %%adherent%% (%%adherent.email%%) est arrivée : %%post:titre-edit-lien%%\n
Date de début : %%paiement_date%%\n
Montant : %%total%% %%option_paiements%%\n
\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
												       AmapressAdhesion_paiement::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Validation de l\'adhésion à l\'Adhérent', 'amapress' ),
										),
										array(
											'id'       => 'online_adhesion_valid-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Validation de votre adhésion à %%nom_site%%', 'amapress' ),
										),
										array(
											'id'      => 'online_adhesion_valid-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour %%user:nom_complet%%,\n\n
Votre adhésion à %%nom_site%% vient d'être validée\n
Vous maintenant vous connecter au site et effectuer vos inscriptions aux contrats.\n
\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les syntaxes [avec_bulletin]xxx[/avec_bulletin] et [sans_bulletin]xxx[/sans_bulletin] permettent de cibler le texte respectivement lorsqu\'un contrat Word est attaché ou non.<br />Les placeholders suivants sont disponibles:', 'amapress' ) .
												       AmapressAdhesion_paiement::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Intégration HelloAsso', 'amapress' )                  => array(
									'id'      => 'amp_helloasso_config',
									'options' => array(
										array(
											'type' => 'note',
											'desc' => function ( $o ) {
												return '<p>' . sprintf( __( 'Pour intégrer HelloAsso à Amapress, consultez %s.', 'amapress' ), Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/helloasso', 'Adhésion : paiement en ligne' ) ) . '</p>' .
												       '<p>' . esc_html__( 'Voici votre url de callback à définir dans l’interface d’administration HelloAsso&gt;Mon Compte&gt;Intégration/API, section Notifications, Mon URL de callback :', 'amapress' ) .
												       sprintf( '<br/><code>%s</code>', admin_url( 'admin-post.php?action=helloasso&key=' . amapress_sha_secret( 'helloasso' ) ) ) . '</p>' .
												       '<p>' . sprintf( __( 'Configurez une <a href="%s">"Période d’adhésion"</a> correspondant à votre Campagne d’adhésion HelloAsso.', 'amapress' ),
														admin_url( 'edit.php?post_type=amps_adh_per&amapress_date=active' ) ) . '</p>';
											}
										),
										array(
											'type' => 'heading',
											'name' => __( 'Options d\'import des adhésions HelloAsso', 'amapress' ),
										),
										array(
											'id'      => 'helloasso-auto-confirm',
											'name'    => __( 'Confirmation automatique', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Confirmer automatiquement les adhésions HelloAsso effectuées', 'amapress' ),
											'default' => true,
										),
										array(
											'id'      => 'helloasso-upd-exist',
											'name'    => __( 'Mise à jour', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Mettre à jour les informations des comptes utilisateurs existants (nom/prénom/adresse/téléphone...)', 'amapress' ),
											'default' => false,
										),
										array(
											'id'      => 'helloasso-send-confirm',
											'name'    => __( 'Notifications d\'adhésion', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Envoyer un email de confirmation à chaque amapien pour son adhésion', 'amapress' ),
											'default' => false,
										),
										array(
											'id'      => 'helloasso-notif-tresoriers',
											'name'    => __( 'Notifications trésorier', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Envoyer un email au trésorier pour chaque nouvelle adhésion', 'amapress' ),
											'default' => true,
										),
										array(
											'id'   => 'helloasso-notif-others',
											'name' => __( 'Autres destinataires', 'amapress' ),
											'type' => 'text',
											'desc' => __( 'Emails des destinataires supplémentaires qui recevront une notification pour chaque nouvelle adhésion', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Concordance des champs prédéfinis HelloAsso', 'amapress' ),
										),
										array(
											'id'      => 'helloasso-email-field-name',
											'name'    => __( 'Champ Email', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Email', 'amapress' ),
										),
										array(
											'id'      => 'helloasso-phone-field-name',
											'name'    => __( 'Champ Numéro de téléphone', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Numéro de téléphone', 'amapress' ),
										),
										array(
											'id'      => 'helloasso-address-field-name',
											'name'    => __( 'Champ Adresse', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Adresse', 'amapress' ),
										),
										array(
											'id'      => 'helloasso-zipcode-field-name',
											'name'    => __( 'Champ Code postal', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Code Postal', 'amapress' ),
										),
										array(
											'id'      => 'helloasso-city-field-name',
											'name'    => __( 'Champ Ville', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Ville', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Confirmation d\'adhésion via HelloAsso', 'amapress' ),
										),
										array(
											'id'       => 'online_hla_adhesion_confirm-mail-subject',
											'name'     => __( 'Objet', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Confirmation de votre adhésion à %%nom_site%%', 'amapress' ),
										),
										array(
											'id'      => 'online_hla_adhesion_confirm-mail-content',
											'name'    => __( 'Contenu', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour %%user:nom_complet%%,\n\n
Nous vous confirmons votre adhésion à %%nom_site%%\n
[avec_bulletin]Merci d'imprimer le bulletin joint à cet email et le remettre aux trésoriers (%%tresoriers%%) avec votre chèque de %%montant%% à la première distribution[/avec_bulletin]
[sans_bulletin]Merci de contacter les trésoriers (%%tresoriers%%) avec votre chèque de %%total%% à la première distribution pour signer votre bulletin[/sans_bulletin]
\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return __( 'Les syntaxes [avec_bulletin]xxx[/avec_bulletin] et [sans_bulletin]xxx[/sans_bulletin] permettent de cibler le texte respectivement lorsqu\'un contrat Word est attaché ou non.<br />Les placeholders suivants sont disponibles:', 'amapress' ) .
												       AmapressAdhesion_paiement::getPlaceholdersHelp( [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Import des adhésions HelloAsso par API', 'amapress' ),
										),
										array(
											'id'      => 'ha_fetch',
											'name'    => __( 'API HelloAsso - Intervalle', 'amapress' ),
											'desc'    => __( 'Intervalle de récupération des nouvelles adhésions (0 désactivé)', 'amapress' ),
											'type'    => 'number',
											'min'     => 0,
											'max'     => 1000,
											'step'    => 1,
											'default' => 0,
											'slider'  => false,
											'unit'    => 'heure(s)',
										),
										array(
											'id'           => 'ha_cid',
											'name'         => __( 'API HelloAsso - Client Id', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
										),
										array(
											'id'           => 'ha_csec',
											'name'         => __( 'API HelloAsso - Client Secret', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'is_password'  => true,
											'desc'         => function ( $option ) {
												return Amapress::getWpConfigSecretHelp( 'AMAPRESS_HELLOASSO_API_CLIENT_SECRET' );
											}
										),
										array(
											'name'    => __( 'Tester', 'amapress' ),
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => __( 'Tester et importer', 'amapress' ),
													'action' => 'test_helloasso_access',
													'desc'   => 'Tester la connexion à l\'API HelloAsso et importer les adhésions existantes de la période d\'adhésion en cours'
												]
											]
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
					'id'         => 'amapress_gestion_intermittence_page',
					'type'       => 'panel',
					'capability' => 'edit_intermittence_panier',
					'settings'   => array(
						'name'       => __( 'Espace intermittents', 'amapress' ),
						'position'   => '58',
						'capability' => 'edit_intermittence_panier',
						'icon'       => 'dashicons-none flaticon-business-2',
						'long_desc'  => function () {
							return amapress_section_note_maker(
								'',
								'',
								array(
									[
										'capability' => '',
										'item'       => sprintf( __( 'configurer les <a target="_blank" href="%s">options</a> de l\'espace intermittents', 'amapress' ), admin_url( 'admin.php?page=amapress_intermit_conf_opt_page' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'voir les <a target="_blank" href="%s">paniers échangés</a>', 'amapress' ), admin_url( 'edit.php?post_type=amps_inter_panier&amapress_date=active' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'configurer les <a target="_blank" href="%s">emails de rappels</a> relatifs à l\'espace intermittents', 'amapress' ), admin_url( 'admin.php?page=intermit_mails_page' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'gérer les <a target="_blank" href="%s">amapiens membres</a> de la liste des intermittents', 'amapress' ), admin_url( 'users.php?amapress_contrat=intermittent' ) )
									],
									[
										'capability' => '',
										'item'       => sprintf( __( 'consulter les <a target="_blank" href="%s">statistiques d\'échanges de paniers</a>', 'amapress' ), admin_url( 'admin.php?page=intermittent_page_stats' ) )
									],
								),
								'',
								'https://wiki.amapress.fr/admin/espace_intermittents'
							);
						}
					),
					'options'    => array(),
					'tabs'       => array(),
					'subpages'   => array(
						array(
							'title'      => __( 'Intermittents', 'amapress' ),
							'menu_icon'  => 'dashicons-admin-users',
							'menu_title' => __( 'Intermittents', 'amapress' ),
							'capability' => 'edit_users',
							'slug'       => 'users.php?amapress_contrat=intermittent',
						),
						array(
							'title'      => __( 'Paniers à échanger', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Paniers à échanger', 'amapress' ),
							'post_type'  => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
							'capability' => 'edit_intermittence_panier',
							'slug'       => 'edit.php?post_type=amps_inter_panier&amapress_date=active',
						),
						array(
							'subpage'  => true,
							'id'       => 'intermittent_page_stats',
							'settings' => array(
								'name'       => __( 'Statistiques des échanges', 'amapress' ),
								'menu_title' => __( 'Statistiques', 'amapress' ),
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

										echo '<p>' . esc_html__( 'Obtenir des statistisque pour la période suivante :', 'amapress' ) . '</p>';
										echo '<label class="tf-date" for="amp_stats_start_date">' . __( 'Début:', 'amapress' ) . ' <input id="amp_stats_start_date" class="input-date date required " name="amp_stats_start_date" type="text" value="' . $start_date_fmt . '" /></label>';
										echo '<label class="tf-date" for="amp_stats_end_date">' . __( 'Fin:', 'amapress' ) . ' <input id="amp_stats_end_date" class="input-date date required " name="amp_stats_end_date" type="text" value="' . $end_date_fmt . '" /></label>';
										echo '<input type="submit" class="button button-primary" value="' . esc_attr__( 'Voir les statistiques', 'amapress' ) . '" />';
										echo '<hr />';


										echo '<h4>';
										echo sprintf( __( 'Echanges de paniers du %s au %s', 'amapress' ), $start_date_fmt, $end_date_fmt );
										echo '</h4>';

										$columns    = [];
										$columns[]  = array(
											'title' => __( 'Amapien', 'amapress' ),
											'data'  => array(
												'_'    => 'user',
												'sort' => 'sort_user',
											),
										);
										$columns[]  = array(
											'title' => __( 'Lieu', 'amapress' ),
											'data'  => 'lieu',
										);
										$columns[]  = array(
											'title' => __( 'Proposés', 'amapress' ),
											'data'  => 'exchanged_nb',
										);
										$columns[]  = array(
											'title' => __( 'Dates échange', 'amapress' ),
											'data'  => 'exchanged_dates',
										);
										$columns[]  = array(
											'title' => __( 'Repris', 'amapress' ),
											'data'  => 'taken_nb',
										);
										$columns[]  = array(
											'title' => __( 'Dates reprise', 'amapress' ),
											'data'  => 'taken_dates',
										);
										$start_date = DateTime::createFromFormat( 'd/m/Y', $start_date_fmt )->getTimestamp();
										$end_date   = DateTime::createFromFormat( 'd/m/Y', $end_date_fmt )->getTimestamp();

										$stats = AmapressIntermittence_panier::getStats( $start_date, $end_date );

										amapress_echo_datatable( 'amp_intermit_stats_table',
											$columns, $stats['users'],
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
												'title' => __( 'Mois', 'amapress' ),
												'data'  => [
													'_'    => 'month',
													'sort' => 'sort_month',
												],
											),
											array(
												'title' => __( 'Paniers proposés', 'amapress' ),
												'data'  => 'exchanged_nb',
											),
											array(
												'title' => __( 'Paniers repris', 'amapress' ),
												'data'  => 'taken_nb',
											)
										];

										amapress_echo_datatable( 'amp_intermit_month_stats_table',
											$columns, $stats['months'],
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
								'name'       => __( 'Emails et rappels', 'amapress' ),
								'menu_title' => __( 'Emails/Rappels', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Email - Inscriptions', 'amapress' )                                  => array(
									'options' => array(
										array(
											'id'       => 'intermittence-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Votre demande d\'adhésion à l\'espace intermittents', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre demande d'adhésion à l'espace intermittents (%%post:lien_intermittence%%) a bien été enregistrée\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'intermit-inscr-placeholders', [], '' );
											},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Désinscriptions', 'amapress' )                               => array(
									'options' => array(
										array(
											'id'       => 'intermittence-desincr-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Votre demande de désinscription de l\'espace intermittents', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-desincr-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVotre demande de désincription de l'espace intermittents a bien été enregistrée\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'intermit-desinscr-placeholders', [], '' );
											},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Panier disponible', 'amapress' )                             => array(
									'options' => array(
										array(
											'name' => __( 'Email aux intermittents', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-dispo-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:panier%% à réserver',
										),
										array(
											'id'      => 'intermittence-panier-dispo-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nVous recevez cet email en tant qu'amapien ou intermittent de l'AMAP %%nom_site%%.\n\nUn panier (%%post:panier-desc%%) est proposé à la distribution de %%post:distribution-link%%\n\nSi vous souhaitez le réserver, rendez-vous sur le site de l'AMAP %%nom_site%%, sur la page %%post:liste-paniers%%\n\nPour vous désinscrire de la liste des intermittents : %%lien_desinscription_intermittent%%\n\nEn cas de problème ou de questions sur le fonctionnement des intermittents, veuillez contacter : %%admin_email_link%%.\n\nSi vous avez des questions plus générale sur l'AMAP %%nom_site%%, vous pouvez écrire à %%admin_email_link%%.\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp();
												},
										),
										array(
											'name' => __( 'Email à l\'amapien proposant son panier', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-on-list-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Votre %%post:panier%% a été mis sur la liste des paniers à échanger', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-on-list-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\nVotre %%post:panier-desc-date%% a été mis sur la liste des paniers à échanger\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Paniers disponibles - Rappels', 'amapress' )                 => array(
									'options' => amapress_intermittence_dispo_recall_options(),
								),
								__( 'Email - Panier reprise - demande', 'amapress' )                      => array(
									'options' => array(
										array(
											'name' => __( 'Email à l\'amapien proposant son panier', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-ask-adherent-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Demande de reprise %%post:panier%% par %%post:repreneur-nom%%', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-repris-ask-adherent-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\nUne demande a été faite par %%post:repreneur%% (%%post:repreneur-coords%%) pour votre panier (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\nVeuillez valider ou rejeter cette demande dans %%post:mes-echanges%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'name' => __( 'Email à l\'amapien repreneur', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-ask-repreneur-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'La demande de reprise %%post:panier%% a été envoyée', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-repris-ask-repreneur-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\nVotre demande pour le panier (%%post:panier-desc%%) à la distribution %%post:distribution%% a été envoyée à %%adherent-coords%%.\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Panier reprise - demande - Rappel', 'amapress' )             => array(
									'options' => amapress_intermittence_validation_recall_options(),
								),
								__( 'Email - Panier reprise - validation', 'amapress' )                   => array(
									'options' => array(
										array(
											'name' => __( 'Email à l\'amapien proposant son panier', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-validation-adherent-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:panier%% repris par %%post:repreneur-nom%%',
										),
										array(
											'id'      => 'intermittence-panier-repris-validation-adherent-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\nVotre panier (%%post:panier-desc%%) sera repris par %%post:repreneur%% (%%post:repreneur-coords%%) à la distribution %%post:distribution%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'name' => __( 'Email à l\'amapien repreneur', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-validation-repreneur-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:adherent-nom%% a accepté la reprise de %%post:panier%%',
										),
										array(
											'id'      => 'intermittence-panier-repris-validation-repreneur-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n%%post:adherent-nom%% (%%post:adherent-coords%%) a accepté la reprise de (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Panier reprise - rejet', 'amapress' )                        => array(
									'options' => array(
										array(
											'name' => __( 'Email à l\'amapien repreneur', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-repris-rejet-repreneur-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => '%%post:adherent-nom%% a refusé la reprise de %%post:panier%%',
										),
										array(
											'id'      => 'intermittence-panier-repris-rejet-repreneur-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n%%post:adherent-nom%% (%%post:adherent-coords%%) a refusé la reprise de (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Panier annulation - adherent', 'amapress' )                  => array(
									'options' => array(
										array(
											'name' => __( 'Email à l\'amapien proposant son panier', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-adherent-adherent-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Annulation de votre proposition de reprise %%post:panier%%', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-adherent-adherent-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\nVotre panier (%%post:panier-desc-date%%) a été retiré de l'espace intermittents\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'name' => __( 'Email à l\'amapien repreneur', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Annulation de repise %%post:panier%% de %%post:adherent-nom%%', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-adherent-repreneur-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n%%post:adherent%% (%%post:adherent-coords%%) a annulé la reprise de son panier (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Panier annulation - repreneur', 'amapress' )                 => array(
									'options' => array(
										array(
											'name' => __( 'Email à l\'amapien proposant son panier', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-repreneur-adherent-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Annulation de repise %%post:panier%% par %%post:repreneur-nom%%', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-repreneur-adherent-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n%%post:repreneur%% (%%post:repreneur-coords%%) a annulé la reprise de votre panier (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'name' => __( 'Email à l\'amapien repreneur', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-cancel-from-repreneur-repreneur-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Confirmation d\'annulation de repise de %%post:panier%% de %%post:adherent-nom%%', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-cancel-from-repreneur-repreneur-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\nVous avez annulé la reprise du panier (%%post:panier-desc%%) de %%post:adherent%% (%%post:adherent-coords%%) à la distribution %%post:distribution%%\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [], false );
												},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email - Panier reprise - définie par Responsable AMAP', 'amapress' ) => array(
									'options' => array(
										array(
											'name' => __( 'Email à l\'amapien', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-admin-adh-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Attribution de votre panier %%post:panier%% à %%post:repreneur-nom%%', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-admin-adh-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nVotre panier (%%post:panier-desc%%) a été attribué par un responsable de l\'AMAP '%%responsable%%' à %%post:repreneur-nom%% (%%post:repreneur-coords%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [
														'responsable' => __( 'Nom et coordonnées du responsable ayant fait l\'affectation du panier', 'amapress' )
													], false );
												},
										),
										array(
											'name' => __( 'Email à l\'amapien repreneur', 'amapress' ),
											'type' => 'heading',
										),
										array(
											'id'       => 'intermittence-panier-admin-rep-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Attribution de %%post:panier%% de %%post:adherent-nom%%', 'amapress' ),
										),
										array(
											'id'      => 'intermittence-panier-admin-rep-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( "Bonjour,\nUn responsable de l\'AMAP '%%responsable%%' vous a attribué la reprise du panier de %%post:adherent-nom%% (%%post:adherent-coords%%) : (%%post:panier-desc%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
											'desc'    =>
												function ( $option ) {
													return AmapressIntermittence_panier::getPlaceholdersHelp( [
														'responsable' => __( 'Nom et coordonnées du responsable ayant fait l\'affectation du panier', 'amapress' )
													], false );
												},
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
								'name'       => __( 'Configuration', 'amapress' ),
								'menu_title' => __( 'Configuration', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-admin-generic',
							),
							'options'  => array(),
							'tabs'     => array(
								__( 'Configuration de l\'espace intermittents', 'amapress' )             => array(
									'id'         => 'amapress_intermit_conf_tab',
									'capability' => 'manage_options',
									'options'    => array(
										array(
											'type' => 'note',
											'desc' => function ( $o ) {
												return Amapress::makeWikiLink( 'https://wiki.amapress.fr/amapien/intermittents' );
											},
										),
										array(
											'id'      => 'intermittence_enabled',
											'name'    => __( 'Activer le système des intermittents', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'intermit_self_inscr',
											'name'    => __( 'Autoriser les amapiens à inscrire des intermittents', 'amapress' ),
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'      => 'intermit_adhesion_req',
											'name'    => __( 'Adhésion obligatoire pour les intermittents', 'amapress' ),
											'type'    => 'checkbox',
											'desc'    => __( 'Les intermittents doivent passer par un assistant d\'adhésion (shortcode [intermittent-adhesion-en-ligne] ou [intermittent-adhesion-en-ligne-connecte])', 'amapress' ),
											'default' => false,
										),
										array(
											'id'      => 'allow_partial_exchange',
											'name'    => __( 'Autoriser les la cession partielle de paniers', 'amapress' ),
											'type'    => 'checkbox',
											'default' => false,
										),
										array(
											'id'      => 'close-subscribe-intermit-hours',
											'name'    => __( 'Cession paniers', 'amapress' ),
											'desc'    => __( 'Clôturer les cessions de paniers x heures avant la distribution', 'amapress' ),
											'type'    => 'number',
											'min'     => 1,
											'step'    => 1,
											'default' => 24,
											'slider'  => false,
											'unit'    => 'heure(s)'
										),
										array(
											'id'      => 'delete_inter_paniers_months',
											'name'    => __( 'Délai de purge', 'amapress' ),
											'type'    => 'number',
											'unit'    => 'mois',
											'default' => 18,
											'min'     => 6,
											'desc'    => __( 'Délai en mois de purge des échanges de paniers', 'amapress' ),
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Assistant - Adhésion en ligne intermittents - Etapes', 'amapress' ) => array(
									'id'      => 'config_online_inter_adhesions_messages',
									'desc'    => __( 'Configuration de l\'assistant d\'adhésion en ligne (intermittent-adhesion-en-ligne-connecte/intermittent-adhesion-en-ligne).', 'amapress' ),
									'options' => array(
										array(
											'id'       => 'online_subscription_start_saison_inter_message',
											'name'     => __( 'Label email', 'amapress' ),
											'type'     => 'text',
											'default'  => '',
											'sanitize' => false,
											'desc'     => function ( $option ) {
												return __( 'Label du champ email (non connecté) (shortcode [adhesion-en-ligne]), par défaut, "Pour démarrer votre adhésion pour la saison xxx, veuillez renseigner votre adresse mail :"', 'amapress' )
												       . AmapressAdhesionPeriod::getPlaceholdersHelp();
											},
										),
										array(
											'id'      => 'online_subscription_welcome_adh_inter_message',
											'name'    => __( 'Message de bienvenue', 'amapress' ),
											'type'    => 'text',
											'default' => '',
											'desc'    => __( 'Message de bienvenue (non connecté) (shortcode [intermittent-adhesion-en-ligne]), par défaut, "Bienvenue dans l\'assistant d\'adhésion des intermittents de « AMAP »"', 'amapress' ),
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape 1 - Email', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_inter_email_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Email', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_inter_email_step_message',
											'name' => __( 'Message supplémentaire', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_email_step_message-placeholders', [], null, [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape 2 - Coordonnées, co-adhérents et membres du foyer', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_inter_coords_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Coordonnées', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_inter_coords_step_message',
											'name' => __( 'Message supplémentaire', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_inter_coords_step_message-placeholders', [], null, [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape Règlement intérieur de l\'AMAP', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_inter_agreement_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Charte et règlement intérieur de l\'AMAP', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_inter_agreement_step_checkbox',
											'name'    => __( 'Texte de la case à cocher', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'J\'ai pris connaissance du règlement et l\'accepte', 'amapress' ),
										),
										array(
											'id'   => 'online_subscription_inter_agreement',
											'name' => __( 'Contenu du règlement intérieur et Contenu de la Charte des AMAPS', 'amapress' ),
											'type' => 'editor',
											'desc' => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_inter_agreement-placeholders', [], 'user:de l\'amapien', [], false );
											},
										),
										array(
											'type' => 'save',
										),
										array(
											'type' => 'heading',
											'name' => __( 'Assistant - Étape Adhésion AMAP', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_inter_req_adhesion',
											'name'    => __( 'Message adhésion requise', 'amapress' ),
											'type'    => 'editor',
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'online_subscription_inter_req_adhesion-placeholders', [], 'user:de l\'amapien', [], false );
											},
											'default' => '<p><strong>' . __( 'Pour vous engager dans l’AMAP et pouvoir réserver des paniers disponibles, vous devez adhérer à notre Association.', 'amapress' ) . '</strong></p>',
										),
										array(
											'id'      => 'online_subscription_inter_adh_step_name',
											'name'    => __( 'Nom de l\'étape', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Adhésion (obligatoire)', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_inter_adh_valid_step_name',
											'name'    => __( 'Nom de l\'étape de validation', 'amapress' ),
											'type'    => 'text',
											'default' => __( 'Validation du Bulletin d\'adhésion', 'amapress' ),
										),
										array(
											'id'      => 'online_subscription_inter_greating_adhesion',
											'name'    => __( 'Contenu du message de validation', 'amapress' ),
											'type'    => 'editor',
											'desc'    => function ( $option ) {
												return __( 'Le placeholder %%print_button%% permet d\'afficher le bouton Imprimer le bulletin<br/>', 'amapress' ) . Amapress::getPlaceholdersHelpTable( 'online_subscription_greating_adhesion-placeholders',
														AmapressAdhesion_paiement::getPlaceholders(), 'user:de l\'amapien', [
															'print_button' => __( 'Bouton Imprimer le bulletin', 'amapress' )
														], false );
											},
											'default' => wpautop( __( "Merci pour votre adhésion à l'AMAP !\nUn courriel de confirmation vient de vous être envoyé. Pensez à consulter les éléments indésirables.\nVeuillez imprimer le bulletin et le remettre avec votre chèque/règlement à l'ordre de l'AMAP lors de la première distribution.\n%%print_button%%", 'amapress' ) ),
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
									'desc' => __( 'Une <a href="https://fr.wikipedia.org/wiki/Progressive_web_app" target="_blank">progressive web app</a> (<strong>PWA</strong>, applications web progressives en français) est une application web qui consiste en des pages ou des sites web, et qui peuvent apparaître à l\'utilisateur de la même manière que les applications natives ou les applications mobiles.', 'amapress' ),
									'bare' => true,
								),
								array(
									'id'         => 'pwa_short_name',
									'name'       => __( 'Nom de l\'application', 'amapress' ),
									'type'       => 'text',
									'desc'       => __( 'Nom du raccourci de l\'application (12 caractères maximum)', 'amapress' ),
									'capability' => 'manage_options',
									'maxlength'  => 25,
								),
								array(
									'id'         => 'pwa_theme_color',
									'name'       => __( 'Couleur du thème', 'amapress' ),
									'type'       => 'color',
									'default'    => '',
									'desc'       => __( 'Couleur du thème de l\'application', 'amapress' ),
									'capability' => 'manage_options',
								),
								array(
									'id'         => 'pwa_display',
									'name'       => __( 'Affichage', 'amapress' ),
									'type'       => 'select',
									'default'    => 'minimal-ui',
									'desc'       => __( 'Type d\'affichage de l\'application', 'amapress' ),
									'options'    => [
										'fullscreen' => __( 'Plein écran', 'amapress' ),
										'standalone' => __( 'Application native', 'amapress' ),
										'minimal-ui' => __( 'Navigateur minimal', 'amapress' ),
										'browser'    => __( 'Navigateur complet', 'amapress' ),
									],
									'capability' => 'manage_options',
								),
								array(
									'id'         => 'pwa_ios_prompt',
									'name'       => __( 'Popup iOs', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Afficher un popup explicatif sur comment installer le site sur l\'écran d\'accueil des iPhone/iPad', 'amapress' ),
									'capability' => 'manage_options',
									'default'    => 0,
								),
								array(
									'id'         => 'pwa_android_prompt',
									'name'       => __( 'Bouton Installer Android', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Afficher un bouton "Installer" avant le popup natif "Ajouter à l\'écran d\'accueil" d\'Android/Chrome', 'amapress' ),
									'capability' => 'manage_options',
									'default'    => 0,
								),
								array(
									'id'         => 'pwa_android_btn_text',
									'name'       => __( 'Texte Bouton Installer', 'amapress' ),
									'type'       => 'text',
									'desc'       => __( 'Texte du bouton "Installer" pour Android/Chrome', 'amapress' ),
									'capability' => 'manage_options',
									'default'    => __( 'Installer l\'application', 'amapress' ),
								),
								array(
									'id'         => 'pwa_prompt_logged',
									'name'       => __( 'Connecté seulement', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Afficher le popup iOs/iPhone/iPad et le bouton Installer Android/Chrome pour les amapiens connectés seulement', 'amapress' ),
									'capability' => 'manage_options',
									'default'    => 1,
								),
								array(
									'id'         => 'pwa_prompt_discard',
									'name'       => __( 'Masquer', 'amapress' ),
									'type'       => 'number',
									'desc'       => __( 'Masquer le popup iOs/iPhone/iPad et le bouton Installer Android/Chrome après X secondes', 'amapress' ),
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
								'name'       => __( 'Site', 'amapress' ),
								'capability' => 'manage_options',
								'menu_icon'  => 'dashicons-admin-site-alt',
							),
							'tabs'     => array(
								__( 'Référencement', 'amapress' )                                   => array(
									'id'      => 'amp_site_reference',
									'options' => array(
										array(
											'id'   => 'site_verif_google_id',
											'name' => __( 'Google - Code de vérification du site', 'amapress' ),
											'type' => 'text',
											'desc' => __( 'Entrer le code de vérification (contenu <em>xxx</em> de l\'attribut <code>content</code> de la balise <code>&lt;meta name=\'google-site-verification\' content=\'<em>xxx</em>\' /&gt;</code>) récupéré de la <a target="_blank" href="https://search.google.com/search-console/about">Google Search Console</a>', 'amapress' )
										),
										array(
											'id'   => 'site_verif_bing_id',
											'name' => __( 'Bing - Code de vérification du site', 'amapress' ),
											'type' => 'text',
											'desc' => __( 'Entrer le code de vérification (contenu <em>xxx</em> de l\'attribut <code>content</code> de la balise <code>&lt;meta name=\'msvalidate.01\' content=\'<em>xxx</em>\' /&gt;</code>) récupéré des <a target="_blank" href="https://www.bing.com/toolbox/webmaster">outils pour webmaster Bing</a>', 'amapress' )
										),
										array(
											'id'   => 'other_site_html_header',
											'name' => __( 'Autres entêtes html pour le site', 'amapress' ),
											'type' => 'textarea',
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Connexion', 'amapress' )                                       => array(
									'id'      => 'amp_connection_config',
									'options' => array(
										array(
											'id'         => 'auth_expiration',
											'name'       => __( 'Expiration de session courte', 'amapress' ),
											'desc'       => __( 'Délai d\'expiration des sessions (par défaut)', 'amapress' ),
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
											'name'       => __( 'Expiration de session longue', 'amapress' ),
											'desc'       => __( 'Délai d\'expiration des sessions (Se souvenir de moi, coché)', 'amapress' ),
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
											'name'       => __( 'Message à afficher en dessous du formulaire de connexion', 'amapress' ),
											'type'       => 'editor',
											'default'    => wpautop( __( "Bienvenue sur le site de %%site_name%%.\n\n
Le lien de connexion pour modifier votre mot de passe a une durée de %%expiration_reset_pass%% jours.\n
Si ce délai est passé, merci de suivre la procédure suivante :\n
=================================================\n
Cliquez sur \"Mot de passe oublié ?\" en bas de cette page\n
Vous serez redirigé vers une nouvelle page. Indiquez votre nom d'utilisateur et l'adresse email associée à ce compte.\n
Attendez tranquillement votre nouveau mot de passe par courriel.\n
Vérifiez que l'email ne s'est pas glissé dans vos spams\n
Après obtention de votre nouveau mot de passe, connectez-vous. Vous pouvez le personnaliser sur votre page de profil.\n
=================================================\n", 'amapress' ) ),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'note',
											'desc' => function ( $o ) {
												return sprintf( __( 'Le réglage du délai d\'expiration du lien de réinitialisation de mot de passe se fait dans %s', 'amapress' ), Amapress::makeLink( admin_url( 'options-general.php?page=amapress_site_options_page&tab=welcome_mail' ), __( 'Tableau de bord>Réglages>Site, onglet Email de bienvenue', 'amapress' ) ) );
											},
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email du site', 'amapress' )                                   => array(
									'id'      => 'amp_site_mail_config',
									'options' => array(
										array(
											'id'         => 'email_from_name',
											'name'       => __( 'Nom de l\'expéditeur des emails du site', 'amapress' ),
											'type'       => 'text',
											'default'    => get_bloginfo( 'blogname' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'email_from_mail',
											'name'       => __( 'Adresse email de l\'expéditeur des emails sortants du site', 'amapress' ),
											'type'       => 'text',
											'default'    => amapress_get_default_wordpress_from_email(),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email de bienvenue', 'amapress' )                              => array(
									'id'      => 'welcome_mail',
									'options' => array(
										array(
											'id'      => 'welcome_mail_subject',
											'name'    => __( 'Objet de l\'email d\'enregistrement', 'amapress' ),
											'type'    => 'text',
											'default' => '[%%nom_site%%] Votre compte utilisateur',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'welcome_mail',
											'name'    => __( 'Contenu de l\'email d\'enregistrement', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour %%dest%%\n\nVotre identifiant est : %%dest:login%%. (Vous pouvez également utiliser votre email : %%dest:mail%%)\nPour configurer votre mot de passe, rendez-vous à l’adresse suivante :\n%%password_url%%\n\nBien cordialement,\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'welcome-placeholders', [], '' );
											},
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'password_lost_mail_subject',
											'name'    => __( 'Objet de l\'email de récupération de mot de passe', 'amapress' ),
											'type'    => 'text',
											'default' => '[%%nom_site%%] Récupération de votre mot de passe',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'password_lost_mail',
											'name'    => __( 'Contenu de l\'email de récupération de mot de passe', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour %%dest%%\n\nQuelqu'un a demandé la récupération de votre mot de passe. Si ce n'est pas vous, veuillez ignorer cet email et votre mot de passe restera inchangé.\n\nVotre identifiant est : %%dest:login%%. Vous pouvez également utiliser votre email : %%dest:mail%%\nPour changer votre mot de passe, rendez-vous à l’adresse suivante :\n%%password_url%%\n\nBien cordialement,\n%%nom_site%%", 'amapress' ) ),
											'desc'    => function ( $option ) {
												return Amapress::getPlaceholdersHelpTable( 'passlost-placeholders', [], '' );
											},
										),
										array(
											'id'      => 'welcome-mail-expiration',
											'name'    => __( 'Durée d\'expiration', 'amapress' ),
											'desc'    => __( 'Expiration de l\'email de bienvenue/mot de passe perdu en jours', 'amapress' ),
											'type'    => 'number',
											'step'    => 0.5,
											'default' => '180',
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Email de réponse type aux demandes d\'adhésions', 'amapress' ) => array(
									'id'      => 'adh_req_reply_mail',
									'options' => array(
										array(
											'id'       => 'adh-request-reply-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => __( 'Votre demande d\'adhésion à %%site_name%%', 'amapress' ),
										),
										array(
											'id'      => 'adh-request-reply-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( "Bonjour %%prenom%% %%nom%%,\nVotre demande d\'adhésion a bien été prise en compte. Voici la procédure à suivre:[[à compléter]]\n\n%%nom_site%%" ),
											'desc'    =>
												function ( $option ) {
													return AmapressAdhesionRequest::getPlaceholdersHelp();
												},
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Notifications administrateur', 'amapress' )                    => array(
									'id'      => 'amp_notif_config',
									'options' => array(
										array(
											'id'         => 'notify_admin_new_user',
											'name'       => __( 'Nouveau compte', 'amapress' ),
											'type'       => 'checkbox',
											'desc'       => __( 'Notifier l\'administrateur des inscriptions de nouveaux comptes utilisateurs', 'amapress' ),
											'capability' => 'manage_options',
											'default'    => true,
										),
										array(
											'id'         => 'notify_admin_pwd_resp',
											'name'       => __( 'Changement de mot de passe (Responsables)', 'amapress' ),
											'type'       => 'checkbox',
											'desc'       => __( 'Notifier l\'administrateur des changements de mots de passe des comptes avec accès au Tableau de bord', 'amapress' ),
											'capability' => 'manage_options',
											'default'    => true,
										),
										array(
											'id'         => 'notify_admin_pwd_amapien',
											'name'       => __( 'Changement de mot de passe (Amapiens)', 'amapress' ),
											'type'       => 'checkbox',
											'desc'       => __( 'Notifier l\'administrateur des changements de mots de passe des amapiens', 'amapress' ),
											'capability' => 'manage_options',
											'default'    => true,
										),
										array(
											'id'           => 'admin-notify-cc',
											'name'         => __( 'Cc', 'amapress' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => __( 'Destinataires en copie des emails de notification', 'amapress' ),
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
								__( 'Pages', 'amapress' )                    => array(
									'id'         => 'amp_pages_config',
									'capability' => 'manage_options',
									'options'    => array(
//                                array(
//                                    'id' => 'agenda-page',
//                                    'name' => __('Page de l\'Agenda', 'amapress'),
//                                    'type' => 'select-pages',
//                                ),
//                                array(
//                                    'id' => 'trombinoscope-page',
//                                    'name' => __('Page du trombinoscope', 'amapress'),
//                                    'type' => 'select-pages',
//                                ),
//                                array(
//                                    'id' => 'recettes-page',
//                                    'name' => __('Page des recettes', 'amapress'),
//                                    'type' => 'select-pages',
//                                ),
										array(
											'id'   => 'auto-post-thumb',
											'name' => __( 'Première image à la Une', 'amapress' ),
											'desc' => __( 'Utiliser la première image de chaque article comme image à la Une', 'amapress' ),
											'type' => 'checkbox',
										),
										array(
											'id'   => 'mes-infos-page',
											'name' => __( 'Page des informations personnelles', 'amapress' ),
											'type' => 'select-pages',
										),
										array(
											'id'   => 'paniers-intermittents-page',
											'name' => __( 'Page des paniers intermittents', 'amapress' ),
											'type' => 'select-pages',
										),
										array(
											'id'   => 'mes-paniers-intermittents-page',
											'name' => __( 'Page des paniers intermittents de l\'amapien', 'amapress' ),
											'type' => 'select-pages',
										),
										array(
											'id'   => 'adhesion-page',
											'name' => __( 'Page de demande d\'adhésion (publique)', 'amapress' ),
											'type' => 'select-pages',
										),
										array(
											'id'   => 'amps-tmpl-file',
											'name' => __( 'Fichier template (Simple)', 'amapress' ),
											'desc' => __( 'Fichier template de votre thème à utiliser pour l\'affichage des informations Amapress (Producteurs/Productions/Produits/Distributions/Recettes...)', 'amapress' ),
											'type' => 'text',
										),
										array(
											'id'   => 'amps-arch-tmpl-file',
											'name' => __( 'Fichier template (Archive)', 'amapress' ),
											'desc' => __( 'Fichier template de votre thème à utiliser pour l\'affichage Archives des informations Amapress (Producteurs/Productions/Produits/Distributions/Recettes...)', 'amapress' ),
											'type' => 'text',
										),
//								array(
//									'id'   => 'archive-page-template',
//									'name' => __('Modèle pour les pages d\'archive', 'amapress'),
//									'type' => 'select-page-templates',
//								),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Géolocalisation', 'amapress' )          => array(
									'id'      => 'amp_google_api_config',
									'options' => array(
										array(
											'id'         => 'geocode_provider',
											'name'       => __( 'Fournisseur de géocodage', 'amapress' ),
											'type'       => 'select',
											'default'    => 'nominatim',
											'desc'       => __( 'Choisissez le fournisseur utilisé pour résoudre les adresses', 'amapress' ),
											'options'    => [
												'google'    => __( 'Google Maps', 'amapress' ),
												'nominatim' => __( 'Nominatim (Open Street Map)', 'amapress' ),
												'here'      => __( 'Here Maps', 'amapress' ),
											],
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'map_provider',
											'name'       => __( 'Fournisseur de cartes', 'amapress' ),
											'type'       => 'select',
											'default'    => 'openstreetmap',
											'desc'       => __( 'Choisissez le fournisseur utilisé pour afficher les cartes', 'amapress' ),
											'options'    => [
												'google'        => __( 'Google Maps', 'amapress' ),
												'openstreetmap' => __( 'OpenStreetMap', 'amapress' ),
											],
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'google_map_key',
											'name'       => __( 'Clé Google API', 'amapress' ),
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
                                                        title="<?php _e( 'Générer une clé d\'API - ( vous devez être connecté à votre compte Google )', 'amapress' ); ?>">
													<?php _e( 'Générer une clé d\'API', 'amapress' ); ?>
                                                </a>
												<?php echo sprintf( __( 'ou %scliquez ici%s pour Obtenir une clé Google Map', 'geodirectory' ), '<a target="_blank" href="https://console.developers.google.com/flows/enableapi?apiid=static_maps_backend,street_view_image_backend,maps_embed_backend,places_backend,geocoding_backend,directions_backend,distance_matrix_backend,geolocation,elevation_backend,timezone_backend,maps_backend&keyType=CLIENT_SIDE&reusekey=true">', '</a>' ) ?>
												<?php
												return ob_get_clean();
											},
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'here_map_app_id',
											'name'       => __( 'Here Maps - APP ID', 'amapress' ),
											'type'       => 'text',
											'default'    => '',
											'desc'       => __( 'APP ID pour la géolocalisation par Here Maps. Vous pouvez créer un compte <a target="_blank" href="https://developer.here.com/sign-up?create=Freemium-Basic">ici</a> et récupérer vos codes APP ID et APP CODE dans la section "REST & XYZ HUB API/CLI"', 'amapress' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'here_map_app_code',
											'name'       => __( 'Here Maps - APP CODE', 'amapress' ),
											'type'       => 'text',
											'default'    => '',
											'desc'       => __( 'APP CODE pour la géolocalisation par Here Maps.', 'amapress' ),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Conversion PDF et autres', 'amapress' ) => array(
									'id'      => 'amp_convertws_config',
									'options' => array(
										array(
											'id'         => 'convertws_url',
											'name'       => __( 'Url du WebService de conversion', 'amapress' ),
											'type'       => 'text',
											'capability' => 'manage_options',
											'default'    => 'https://convert.amapress.fr',
										),
										array(
											'id'           => 'convertws_user',
											'name'         => __( 'Compte utilisateur du  WebService de conversion', 'amapress' ),
											'type'         => 'text',
											'autocomplete' => false,
											'capability'   => 'manage_options',
										),
										array(
											'id'           => 'convertws_pass',
											'name'         => __( 'Mot de passe du compte du  WebService de conversion', 'amapress' ),
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
											'desc' => __( 'Après avoir enregistré les paramètres ci-dessous, cliquez sur le bouton Tester. Les paramètres sont correctes si un PDF se télécharge et s\'ouvre. Dans le cas contraire, vous obtiendrez un message décrivant le problème.', 'amapress' )
										),
										array(
											'name'    => __( 'Tester', 'amapress' ),
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => __( 'Tester la connexion', 'amapress' ),
													'action' => 'test_convert_ws',
												]
											]
										),
									),
								),
								__( 'Espaces documents', 'amapress' )        => array(
									'id'      => 'amp_docspaces_config',
									'options' => array(
										array(
											'id'         => 'docspace_resps_folders',
											'name'       => __( 'Sous dossiers - Responsables', 'amapress' ),
											'type'       => 'text',
											'classes'    => 'docspaceSubfolders',
											'desc'       => __( 'Indiquez le nom du sous-dossier en minuscule et sans espace. Séparez par des virgules pour créer plusieurs sous dossiers.', 'amapress' ) .
											                '<br/>' . __( 'Retrouvez le shortcode associé avec le filtre docspace-responsables.', 'amapress' ) . '
' . Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ), __( 'Aide-Shortcode', 'amapress' ) ) . '  <br/>
' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/espaces_documents' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'docspace_amapiens_folders',
											'name'       => __( 'Sous dossiers - Amapiens', 'amapress' ),
											'type'       => 'text',
											'classes'    => 'docspaceSubfolders',
											'desc'       => __( 'Indiquez le nom du sous-dossier en minuscule et sans espace. Séparez par des virgules pour créer plusieurs sous dossiers. <br/>
Retrouvez le shortcode associé avec le filtre docspace-amapiens.
', 'amapress' ) . Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ), __( 'Aide-Shortcode', 'amapress' ) ) . '  <br/>
' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/espaces_documents' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'docspace_public_folders',
											'name'       => __( 'Sous dossiers - Public', 'amapress' ),
											'type'       => 'text',
											'classes'    => 'docspaceSubfolders',
											'desc'       => __( 'Indiquez le nom du sous-dossier en minuscule et sans espace. Séparez par des virgules pour créer plusieurs sous dossiers.', 'amapress' ) .
											                ' <br/>' . __( 'Retrouvez le shortcode associé avec le filtre docspace-public.', 'amapress' ) .
											                Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_help_page&tab=shortcodes' ), __( 'Aide-Shortcode', 'amapress' ) ) . '  <br/>
' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/espaces_documents' ),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Tests', 'amapress' )                    => array(
									'id'      => 'amp_tests_config',
									'options' => array(
										array(
											'id'         => 'test_mail_key',
											'name'       => __( 'Clé de test emails', 'amapress' ),
											'type'       => 'text',
											'default'    => uniqid(),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'test_mail_mode',
											'name'       => __( 'Mode de test', 'amapress' ),
											'type'       => 'checkbox',
											'desc'       => __( 'Envoie tous les emails aux adresses ci-dessous', 'amapress' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'test_mail_target',
											'name'       => __( 'Emails test', 'amapress' ),
											'type'       => 'text',
											'default'    => function () {
												return get_option( 'admin_email' );
											},
											'desc'       => __( 'Emails destinataire du mode de test', 'amapress' ),
											'capability' => 'manage_options',
										),
										array(
											'id'         => 'feedback',
											'name'       => __( 'Activer le bouton Feedback', 'amapress' ),
											'type'       => 'checkbox',
											'desc'       => __( 'Activer le bouton Feedback', 'amapress' ),
											'capability' => 'manage_options',
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Confidentialité', 'amapress' )          => array(
									'id'      => 'amp_confident_config',
									'options' => array(
										'allow_show_email'            => array(
											'name'    => __( 'Autoriser les emails à être affichés', 'amapress' ),
											'type'    => 'select',
											'desc'    => __( 'Autorisation à être affiché aux autres amapiens', 'amapress' ),
											'default' => 'false',
											'options' => array(
												'false' => __( 'Ne pas autoriser', 'amapress' ),
												'true'  => __( 'Autoriser', 'amapress' ),
											),
										),
										'allow_show_adresse'          => array(
											'name'    => __( 'Autoriser les adresses à être affichés', 'amapress' ),
											'type'    => 'select',
											'desc'    => __( 'Autorisation à être affiché aux autres amapiens', 'amapress' ),
											'default' => 'false',
											'options' => array(
												'false' => __( 'Ne pas autoriser', 'amapress' ),
												'true'  => __( 'Autoriser', 'amapress' ),
											),
										),
										'allow_show_tel_fixe'         => array(
											'name'    => __( 'Autoriser les téléphones fixes à être affichés', 'amapress' ),
											'type'    => 'select',
											'desc'    => __( 'Autorisation à être affiché aux autres amapiens', 'amapress' ),
											'default' => 'false',
											'options' => array(
												'false' => __( 'Ne pas autoriser', 'amapress' ),
												'true'  => __( 'Autoriser', 'amapress' ),
											),
										),
										'allow_show_tel_mobile'       => array(
											'name'    => __( 'Autoriser les téléphones mobiles à être affichés', 'amapress' ),
											'type'    => 'select',
											'desc'    => __( 'Autorisation à être affiché aux autres amapiens', 'amapress' ),
											'default' => 'false',
											'options' => array(
												'false' => __( 'Ne pas autoriser', 'amapress' ),
												'true'  => __( 'Autoriser', 'amapress' ),
											),
										),
										'allow_show_resp_distrib_tel' => array(
											'name'    => __( 'Autoriser les téléphones mobiles des reponsables de distributions à être affichés', 'amapress' ),
											'type'    => 'select',
											'desc'    => __( 'Autorisation à être affiché aux autres amapiens la semaine où ils sont responsables', 'amapress' ),
											'default' => 'false',
											'options' => array(
												'false' => __( 'Ne pas autoriser', 'amapress' ),
												'true'  => __( 'Autoriser', 'amapress' ),
											),
										),
									),
								),
								__( 'Contacts public', 'amapress' )          => array(
									'id'      => 'amp_public_contacts_config',
									'options' => array(
//                                array(
//                                    'type' => 'save',
//                                    'save' => __('Créer le formulaire de contact', 'amapress'),
//                                    'action' => 'init_contact_form'
//                                ),
//                                array(
//                                    'id' => 'preinscription-button-text',
//                                    'name' => __('Texte des boutons d\'inscription', 'amapress'),
//                                    'type' => 'text',
//                                    'default' => __('Je m\'inscris', 'amapress'),
//                                ),
										array(
											'id'        => 'preinscription-form',
											'name'      => __( 'Formulaire de demande d\'adhésion', 'amapress' ),
											'type'      => 'select-posts',
											'edit_link' => false,
											'post_type' => Amapress::WPCF7_POST_TYPE,
											'desc'      => function ( $o ) {
												return sprintf( __( 'Sélectionner votre formulaire de demande d\'adhésion dans la liste ci-dessus. <br/>Vous pouvez en créer un depuis <a href="%s">Tableau de bord&gt;Contact&gt;Modifier le formulaire de contact</a>. <br/>Ce formulaire sera automatiquement ajouté en dessous des infos de contact ci-dessous.', 'amapress' ), admin_url( 'admin.php?page=wpcf7' ) )
												       . '<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/contact_form' );
											},
										),
//                                array(
//                                    'type' => 'save',
//                                ),
										array(
											'id'           => 'contrat_info_anonymous',
											'name'         => __( 'Information de contact pour les contrats', 'amapress' ),
											'type'         => 'editor',
											'capability'   => 'edit_contrat_instances',
											'default'      => '<p>' . sprintf( __( '<strong>NOUS RENCONTRER</strong><br />Si vous souhaitez nous rencontrer, vous pouvez nous rendre visite lors d’une distribution :<br /> – [[à compléter contact distribution]]</p>
<p><strong>NOUS CONTACTER</strong><br /> Et pour nous contacter, vous pouvez nous envoyer un email à :<br /> [[à définir avec l\'adresse de contact]]<br /> <a href="mailto:%s">%s</a>', 'amapress' ),
													get_option( 'admin_email' ), get_option( 'admin_email' ) ) . '</p>',
											'after_option' => function ( $options ) {
												$links = [];
												foreach ( AmapressContrats::get_contrats() as $contrat ) {
													$links[] = Amapress::makeLink(
														$contrat->getPermalink(),
														sprintf( __( 'Détails du contrat %s', 'amapress' ), $contrat->getTitle() ),
														true, true
													);
												}
												$href = Amapress::get_page_with_shortcode_href( 'nous-contacter', false );
												if ( ! empty( $href ) ) {
													$links[] = Amapress::makeLink(
														$href,
														__( 'Page Contact (shortcode [nous-contacter])', 'amapress' )
													);
												}

												echo '<p>' . __( 'Ce texte peut s\'afficher dans : ', 'amapress' ) . implode( ', ', $links ) . '</p>';
											},
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Droits', 'amapress' )                   => array(
									'id'      => 'amp_rights_config',
									'options' => array(
										array(
											'name'    => __( 'Droits des rôles Amapress', 'amapress' ),
											'type'    => 'action-buttons',
											'buttons' => [
												[
													'class'  => 'button button-primary',
													'text'   => __( 'Remettre par défaut', 'amapress' ),
													'action' => 'reset_rights',
												]
											]
										),
									),
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_nua_config',
							'type'     => 'panel',
							'settings' => array(
								'name'       => __( 'New User Approve', 'amapress' ),
								'capability' => 'manage_options',
							),
							'tabs'     => array(
								__( 'Messages', 'amapress' ) => array(
									'id'         => 'amp_nua_messages',
									'capability' => 'manage_options',
									'options'    => array(
										array(
											'id'      => 'nua_default_welcome_message',
											'name'    => __( 'Page de connexion', 'amapress' ),
											'default' => __( 'Bienvenue sur le site {sitename}. Ce site est accessible aux utilisateurs approuvés. Pour être approuvé, vous devez d\'abord vous inscrire.', 'amapress' ),
											'type'    => 'textarea',
										),
										array(
											'id'      => 'nua_default_registration_message',
											'name'    => __( 'Page d\'inscription', 'amapress' ),
											'default' => __( 'Après l\'inscription, votre demande sera envoyée à l\'administrateur pour approbation. Vous recevrez alors un courriel avec les informations supplémentaires.', 'amapress' ),
											'type'    => 'textarea',
										),
										array(
											'id'      => 'nua_default_registration_complete_message',
											'name'    => __( 'Message après inscription', 'amapress' ),
											'default' => __( 'Un courriel a été envoyé à l\'administrateur du site. Il va vérifier les informations que vous avez transmises et approuver ou refuser votre demande d\'inscription. Vous allez recevoir un courriel avec les instructions sur ce que vous devrez faire ensuite. Merci de votre patience.', 'amapress' ),
											'type'    => 'textarea',
										),
										array(
											'type' => 'save',
										),
									),
								),
								__( 'Emails', 'amapress' )   => array(
									'id'         => 'amp_nua_emails',
									'capability' => 'manage_options',
									'options'    => array(
										array(
											'id'      => 'nua_default_approve_user_message',
											'name'    => __( 'Message d\'approbation', 'amapress' ),
											'default' => "Votre inscription a été acceptée pour accéder au site {sitename}\r\n{username}\r\n{login_url}\r\nPour définir votre mot de passe, allez à l'adresse suivante :\r\n{reset_password_url}",
											'type'    => 'textarea',
										),
										array(
											'id'      => 'nua_default_deny_user_message',
											'name'    => __( 'Message de refus', 'amapress' ),
											'default' => __( 'Désolé, votre inscription pour accéder au site « {sitename} » a été refusée.', 'amapress' ),
											'type'    => 'textarea',
										),
										array(
											'id'      => 'nua_default_notification_message',
											'name'    => __( 'Message de notification à l\'admin', 'amapress' ),
											'default' => "{username} ({user_email}) a demandé l'approbation d'un compte sur {sitename}\n{site_url}\nPour approuver ou refuser cet utilisateur, aller sur le site {sitename}\n{admin_approve_url}",
											'type'    => 'textarea',
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
					'id'       => 'tools.php',
					'type'     => 'builtin',
					'subpages' => array(
						array(
							'type'       => 'page',
							'title'      => __( 'Rappels libres', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Rappels libres', 'amapress' ),
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
								'name'       => __( 'Le collectif', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-groups',
							),
							'tabs'     => array(
								__( 'Roles Amapress', 'amapress' )                                   => array(
									'id'      => 'amapress_edit_wp_roles',
									'options' => array(
										array(
											'type'            => 'related-users',
											'name'            => __( 'Administrateurs', 'amapress' ),
											'query'           => 'role=administrator',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => __( 'Responsables Amap', 'amapress' ),
											'query'           => 'role=responsable_amap',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => __( 'Coordinateurs Amap', 'amapress' ),
											'query'           => 'role=coordinateur_amap',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => __( 'Rédacteurs Amap', 'amapress' ),
											'query'           => 'role=redacteur_amap',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => __( 'Référents Producteurs', 'amapress' ),
											'query'           => 'role=referent',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => __( 'Producteurs', 'amapress' ),
											'query'           => 'role=producteur',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
										array(
											'type'            => 'related-users',
											'name'            => __( 'Trésoriers', 'amapress' ),
											'query'           => 'role=tresorier',
											'show_header'     => true,
											'include_columns' => array( 'username', 'name', 'email', 'role' ),
										),
									),
								),
								__( 'Référents producteurs', 'amapress' )                            => array(
									'id'      => 'amapress_edit_ref_prods',
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
								__( 'Coordinateurs Amap', 'amapress' )                               => array(
									'id'      => 'amapress_edit_roles_collectif',
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
											'name'    => __( 'Rôles supplémentaires', 'amapress' ),
											'buttons' => array(
												array(
													'text'   => __( 'Ajouter un rôle', 'amapress' ),
													'href'   => admin_url( 'edit-tags.php?taxonomy=amps_amap_role_category' ),
													'target' => '_blank',
												),
											),
										),

									)
								),
								__( 'Responsables des réponses aux mails automatiques', 'amapress' ) => array(
									'id'         => 'amp_amap_roles_config',
									'capability' => 'manage_options',
									'options'    => array(
										array(
											'type' => 'note',
											'desc' => __( 'Le fonctionnement d\'Amapress repose sur l\'envoi d\'emails automatiques.<br/>
Il est nécessaire de rediriger les réponsent d\'amapiens à ces mails vers des Coordinateurs Amap.<br/>
Sélectionnez les Coordinateurs Amap en charge des réponses à l\'aide des menu déroulant.', 'amapress' ),
										),
										array(
											'id'       => 'resp-distrib-gardien-amap-role',
											'name'     => __( 'Rôle des responsables des gardiens de paniers', 'amapress' ),
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-distrib-amap-role',
											'name'     => __( 'Rôle des responsables des responsables des distributions', 'amapress' ),
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-visite-amap-role',
											'name'     => __( 'Rôle des responsables des visites', 'amapress' ),
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-intermittents-amap-role',
											'name'     => __( 'Rôle des responsables des intermittents', 'amapress' ),
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'id'       => 'resp-amap_event-amap-role',
											'name'     => __( 'Rôle des responsables des évènements Amap', 'amapress' ),
											'type'     => 'select-categories',
											'taxonomy' => AmapressUser::AMAP_ROLE,
										),
										array(
											'type' => 'save',
										),
									)
								),
								__( 'Historique', 'amapress' )                                       => array(
									'id'      => 'amapress_collectif_history',
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
							'title'      => __( 'Utilisateurs archivables', 'amapress' ),
							'menu_icon'  => 'dashicons-book',
							'menu_title' => __( 'Archivables', 'amapress' ),
							'capability' => 'manage_amapress',
							'slug'       => 'users.php?amapress_role=archivable',
						),
					),
				),
				array(
					'id'       => 'edit.php',
					'type'     => 'builtin',
					'subpages' => array(
						array(
							'subpage'  => true,
							'id'       => 'post_emails_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => __( 'Emails et rappels', 'amapress' ),
								'menu_title' => __( 'Emails/Rappels', 'amapress' ),
								'capability' => 'manage_amapress',
								'menu_icon'  => 'dashicons-email',
							),
							'tabs'     => array(
								__( 'Notification', 'amapress' ) => array(
									'id'      => 'amapress_new_post_notif',
									'options' => array(
										array(
											'id'           => 'new-post-notif-mail-to-groups',
											'name'         => __( 'Groupes Destinataires', 'amapress' ),
											'type'         => 'select',
											'options'      => 'amapress_get_collectif_target_queries',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => __( 'Groupe(s) destinataire(s) de la notification', 'amapress' ),
										),
										array(
											'id'           => 'new-post-notif-mail-to',
											'name'         => __( 'Destinataires', 'amapress' ),
											'type'         => 'select-users',
											'autocomplete' => true,
											'multiple'     => true,
											'tags'         => true,
											'desc'         => __( 'Destinataires de la notification', 'amapress' ),
										),
										array(
											'id'      => 'new-post-notif-types',
											'name'    => __( 'Types d\'articles', 'amapress' ),
											'type'    => 'multicheck',
											'options' => [
												'post'                                         => __( 'Article', 'amapress' ),
												AmapressAmap_event::INTERNAL_POST_TYPE         => __( 'Evènements', 'amapress' ),
												AmapressAssemblee_generale::INTERNAL_POST_TYPE => __( 'Assemblées générales', 'amapress' ),
												AmapressVisite::INTERNAL_POST_TYPE             => __( 'Visites à la ferme', 'amapress' ),
												AmapressRecette::INTERNAL_POST_TYPE            => __( 'Recettes', 'amapress' ),
											],
											'default' => 'post',
										),
										array(
											'id'       => 'new-post-notif-mail-subject',
											'name'     => __( 'Objet de l\'email', 'amapress' ),
											'sanitize' => false,
											'type'     => 'text',
											'default'  => 'Nouvel article publié - %%post:titre%%',
										),
										array(
											'id'      => 'new-post-notif-mail-content',
											'name'    => __( 'Contenu de l\'email', 'amapress' ),
											'type'    => 'editor',
											'default' => wpautop( __( "Bonjour,\n\nUn nouvel article a été publié : %%post:titre%% (%%post:lien%%)\n\n%%nom_site%%", 'amapress' ) ),
											'desc'    =>
												function ( $option ) {
													return Amapress::getPlaceholdersHelpTable(
														'new-post-notif-phs',
														[], null, [], true
													);
												},
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
					'id'       => 'amapress_import_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => __( 'Imports CSV', 'amapress' ),
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
						__( 'Utilisateurs', 'amapress' )          => array(
							'id'         => 'import_users_tab',
							'capability' => 'edit_users',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => function ( $o ) {
										return sprintf( __( 'Cette page permet la création des comptes utilisateur et de leurs coordonnées : amapien, co-adhérents. %s<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)', 'amapress' ),
											Amapress::makeLink( 'https://wiki.amapress.fr/admin/import#import_utilisateurs', __( 'Aide', 'amapress' ), true, true ) );
									},
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => __( 'Télécharger le modèle', 'amapress' ),
									'action'    => 'generate_model_user'
								),
								array(
									'id'     => 'import_users',
									'name'   => __( 'Importer des utilisateurs', 'amapress' ),
									'type'   => 'custom',
									'bare'   => true,
									'custom' => 'Amapress_Import_Users_CSV::get_import_users_page',
//                            'save' => __('Amapress_Import_Users_CSV::process_users_csv_import', 'amapress'),
								),
							)
						),
						__( 'Inscriptions contrats', 'amapress' ) => array(
							'id'         => 'import_adhesions_tab',
							'capability' => 'edit_adhesion',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => function ( $o ) {
										return sprintf( __( 'Cette page permet d\'inscrire les utilisateurs aux contrats producteurs en fonction du choix de leurs paniers. %s<br/>Utilisez le bouton <strong>Télécharger le modèle multi contrat</strong> (import avec contrats en colonnes) ou les boutons <strong>Télécharger le modèle "<em>Nom du contrat</em>"</strong> (import avec les configurations de paniers en colonnes) pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)', 'amapress' ), Amapress::makeLink( 'https://wiki.amapress.fr/admin/import#import_inscriptions', __( 'Aide', 'amapress' ), true, true ) );
									},
								),
								array(
									'name'    => __( 'Modèle multi contrat', 'amapress' ),
									'type'    => 'action-buttons',
									'buttons' => [
										[
											'class'  => 'button button-primary  button-import-model',
											'text'   => __( 'Télécharger le modèle multi contrat', 'amapress' ),
											'action' => 'generate_model_' . AmapressAdhesion::POST_TYPE . '_multi',
										]
									]
								),
								array(
									'name'    => __( 'Modèles mono contrat', 'amapress' ),
									'type'    => 'action-buttons',
									'buttons' => $contrats_model_buttons,
								),
//								array(
//									'type'      => 'save',
//									'use_reset' => false,
//									'save'      => __('Télécharger le modèle - mono contrat', 'amapress'),
//									'action'    => 'generate_model_' . AmapressAdhesion::POST_TYPE,
//								),
								array(
									'id'   => 'import_adhesion_default_date_debut',
									'name' => __( 'Date de début par défaut', 'amapress' ),
									'type' => 'date',
									'desc' => __( 'Date de début', 'amapress' ),
//                                    'default' => function($option) {
//									        return Amapress::start_of_day(amapress_time());
//                                    }
								),
								array(
									'id'                => 'import_adhesion_default_contrat_instance',
									'name'              => __( 'Contrat par défaut', 'amapress' ),
									'type'              => 'select',
									'post_type'         => 'amps_contrat_inst',
									'autoselect_single' => true,
									'desc'              => __( 'Contrat', 'amapress' ),
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
									'name'              => __( 'Lieu par défaut', 'amapress' ),
									'type'              => 'select-posts',
									'post_type'         => 'amps_lieu',
									'autoselect_single' => true,
									'desc'              => __( 'Lieu', 'amapress' ),
								),
								array(
									'id'     => 'import_adhesions',
									'name'   => __( 'Importer des adhésions aux contrats', 'amapress' ),
									'type'   => 'custom',
									'custom' => 'amapress_get_adhesions_import_page',
									'bare'   => true,
//                            'save' => 'amapress_process_adhesions_csv_import',
								),
							)
						),
						'Configuration des paniers'               => array(
							'id'         => 'import_quant_paniers',
							'capability' => 'edit_contrat_instance',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => __( 'Cette page permet d\'importer les configurations de paniers pour vos contrats
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>La colonne "Titre" correspond au nom du produit et la colonne "Contenu" à sa description.
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)', 'amapress' ),
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => __( 'Télécharger le modèle', 'amapress' ),
									'action'    => 'generate_model_' . AmapressContrat_quantite::POST_TYPE,
								),
//                                array(
//                                    'type'=> 'separator',
//                                ),
								array(
									'id'                => 'import_contrat_quantite_default_contrat_instance',
									'name'              => __( 'Contrat par défaut', 'amapress' ),
									'type'              => 'select-posts',
									'post_type'         => 'amps_contrat_inst',
									'autoselect_single' => true,
									'desc'              => __( 'Contrat', 'amapress' ),
								),
								array(
									'id'         => 'ignore_contrat_quantites_unknown_columns',
									'input_name' => 'amapress_ignore_unknown_columns',
									'name'       => __( 'Ignorer les colonnes inconnues', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Ignorer les colonnes dont l\'entête ne correspond pas à un champ existant', 'amapress' ),
								),
								array(
									'id'         => 'contrat_quantite_override_contrat_with_inscriptions',
									'input_name' => 'amapress_override_contrat_with_inscriptions',
									'name'       => __( 'Mise à jour avec inscriptions en cours', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Autoriser la mise à jour de contrats avec inscriptions actives<br/><strong style="color:red">Attention : modifier les configurations de paniers d\'un contrat peut modifier ou annuler ses inscriptions en cours</strong>', 'amapress' ),
								),
								array(
									'id'         => 'override_all_contrat_quantites',
									'input_name' => 'amapress_override_all_contrat_quantites',
									'name'       => __( 'Réimporter toutes les configurations de paniers', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Réimporter toutes les configurations de paniers des contrats présents dans l\'excel (permet de conserver l\'ordre)<br/><strong style="color:red">Attention : cette option n\'est pas pas possible pour les contrats ayant déjà des inscriptions. Pour ces contrats, Vous devez mettre à jour les configurations de paniers directement dans la configuration du contrat.</strong>', 'amapress' ),
								),
								array(
									'id'     => 'import_contrat_quantites',
									'name'   => __( 'Importer des quantités pour les contrats', 'amapress' ),
									'type'   => 'custom',
									'custom' => 'amapress_get_contrat_quantites_import_page',
									'bare'   => true,
//                            'save' => 'amapress_process_adhesions_csv_import',
								),
							)
						),
						'Producteurs'                             => array(
							'id'         => 'import_producteurs_tab',
							'capability' => 'manage_amapress',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => __( 'Cette page permet d\'importer les producteurs
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>La colonne "Titre" correspond au nom du producteur ou de sa ferme et la colonne "Contenu" à son historique. Les utilisateurs correspondant doivent être créés au préalable
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)', 'amapress' ),

								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => __( 'Télécharger le modèle', 'amapress' ),
									'action'    => 'generate_model_' . AmapressProducteur::POST_TYPE,
								),
								array(
									'id'     => 'import_producteurs',
									'name'   => __( 'Importer des producteurs', 'amapress' ),
									'type'   => 'custom',
									'custom' => 'amapress_get_producteurs_import_page',
									'bare'   => true,
								),
							)
						),
						'Productions'                             => array(
							'id'         => 'import_productions_tab',
							'capability' => 'manage_amapress',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => __( 'Cette page permet d\'importer les productions des producteurs
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>La colonne "Titre" correspond au nom de la production (par ex, <i>Légumes, Champignons</i>) et la colonne "Contenu" à sa présentation. Les producteurs correspondant doivent être créés au préalable
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)'
										, 'amapress' )
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => __( 'Télécharger le modèle', 'amapress' ),
									'action'    => 'generate_model_' . AmapressContrat::POST_TYPE,
								),
								array(
									'id'     => 'import_productions',
									'name'   => __( 'Importer des productions', 'amapress' ),
									'type'   => 'custom',
									'custom' => 'amapress_get_productions_import_page',
									'bare'   => true,
								),
							)
						),
						'Contrats'                                => array(
							'id'         => 'import_contrats_tab',
							'capability' => 'manage_amapress',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => __( 'Cette page permet d\'importer les contrats
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>Les producteurs et productions correspondant doivent être créés au préalable
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)'
										, 'amapress' )
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => __( 'Télécharger le modèle', 'amapress' ),
									'action'    => 'generate_model_' . AmapressContrat_instance::POST_TYPE,
								),
								array(
									'id'         => 'ignore_contrats_unknown_columns',
									'input_name' => 'amapress_ignore_unknown_columns',
									'name'       => __( 'Ignorer les colonnes inconnues', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Ignorer les colonnes dont l\'entête ne correspond pas à un champ existant', 'amapress' ),
								),
								array(
									'id'         => 'contrat_override_contrat_with_inscriptions',
									'input_name' => 'amapress_override_contrat_with_inscriptions',
									'name'       => __( 'Mise à jour avec inscriptions en cours', 'amapress' ),
									'type'       => 'checkbox',
									'desc'       => __( 'Autoriser la mise à jour de contrats avec inscriptions actives<br/><strong style="color:red">Attention : modifier les configurations de paniers d\'un contrat peut modifier ou annuler ses inscriptions en cours</strong>', 'amapress' ),
								),
								array(
									'id'     => 'import_contrats',
									'name'   => __( 'Importer des contrats', 'amapress' ),
									'type'   => 'custom',
									'custom' => 'amapress_get_contrats_import_page',
									'bare'   => true,
								),
							)
						),
						'Adhésions AMAP'                          => array(
							'id'         => 'import_adh_pmt_tab',
							'capability' => 'edit_adhesion_paiement',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => __( 'Cette page permet d\'importer les adhésions à l\'AMAP (<strong>sans la répartition des montants/avec les valeurs de montants par défaut</strong>)
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)'
										, 'amapress' )
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => __( 'Télécharger le modèle', 'amapress' ),
									'action'    => 'generate_model_' . AmapressAdhesion_paiement::POST_TYPE,
								),
								array(
									'id'                => 'import_adhesion_paiement_default_period',
									'name'              => __( 'Période d\'adhésion par défaut', 'amapress' ),
									'type'              => 'select-posts',
									'post_type'         => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
									'autoselect_single' => true,
									'desc'              => __( 'Période d\'adhésion', 'amapress' ),
								),
								array(
									'id'     => 'import_adh_pmt',
									'name'   => __( 'Importer des adhésions', 'amapress' ),
									'type'   => 'custom',
									'custom' => 'amapress_get_adh_pmt_import_page',
									'bare'   => true,
								),
							)
						),
						'Produits'                                => array(
							'id'         => 'import_produits_tab',
							'capability' => 'edit_produit',
							'options'    => array(
								array(
									'type' => 'note',
									'desc' => __( 'Cette page permet la création des produits des producteurs
<br/>Utilisez le bouton <strong>Télécharger le modèle</strong> pour récupérer un XLSX contenant le modèle d\'import avec toutes les colonnes utilisables et leurs descritions en commentaires 
<br/>La colonne "Titre" correspond au nom du produit (par ex, <i>Radis ronds, Batavia</i>) et la colonne "Contenu" à sa présentation. Les producteurs correspondant doivent être créés au préalable
<br/>Les colonnes en italique sont facultatives et peuvent être supprimées
<br/>(Note : sous <em>LibreOffice</em>, les commentaires seront visibles par défaut, utilisez le menu <em>Affichage/Commentaires</em> pour les masquer et les retrouver uniquement au survol du titre de chaque colonne)', 'amapress' )
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => __( 'Télécharger le modèle', 'amapress' ),
									'action'    => 'generate_model_' . AmapressProduit::POST_TYPE,
								),
								array(
									'id'                => 'import_produit_default_producteur',
									'name'              => __( 'Producteur par défaut', 'amapress' ),
									'type'              => 'select-posts',
									'post_type'         => AmapressProducteur::INTERNAL_POST_TYPE,
									'autoselect_single' => true,
									'desc'              => __( 'Producteur', 'amapress' ),
								),
								array(
									'id'     => 'import_produits',
									'name'   => __( 'Importer des produits', 'amapress' ),
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
						'name'       => __( 'Aide', 'amapress' ),
						'position'   => '85',
						'capability' => 'read',
						'icon'       => 'dashicons-sos',
					),
					'tabs'     => array(
						'Documentation externe'                         => array(
							'id'      => 'wiki',
							'options' => array(
								array(
									'type' => 'note',
									'bare' => true,
									'desc' => function ( $o ) {
										return '<p>' . sprintf( __( 'Consultez la %s', 'amapress' ), Amapress::makeWikiLink( 'https://wiki.amapress.fr/accueil' ) ) . '</p>' .
										       '<p>' . sprintf( __( 'Accédez au %s si vous ne trouvez pas la réponse à votre question', 'amapress' ), Amapress::makeExternalLink( 'https://forum.amapress.fr', 'Forum des Amap' ) ) . '</p>' .
										       '<h5>' . __( 'Un espace dédié pour chaque rôle Amap', 'amapress' ) . '</h5><p><ul><li>' .
										       Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/accueil', __( 'Accueil de l’Administrateur, du Responsable Amap', 'amapress' ) ) .
										       '</li><li>' .
										       Amapress::makeWikiLink( 'https://wiki.amapress.fr/amapien/accueil', __( 'Accueil de l’Amapien', 'amapress' ) ) .
										       '</li><li>' .
										       Amapress::makeWikiLink( 'https://wiki.amapress.fr/collectif/accueil', __( 'Accueil du Collectif', 'amapress' ) ) .
										       '</li><li>' .
										       Amapress::makeWikiLink( 'https://wiki.amapress.fr/referent_producteur/accueil', __( 'Accueil du Référent producteur', 'amapress' ) ) .
										       '</li><li>' .
										       Amapress::makeWikiLink( 'https://wiki.amapress.fr/producteur/accueil', __( 'Accueil du Producteur', 'amapress' ) ) .
										       '</li><li>' .
										       Amapress::makeWikiLink( 'https://wiki.amapress.fr/tresorier/accueil', __( 'Accueil du Trésorier', 'amapress' ) ) .
										       '</li><li>' .
										       Amapress::makeWikiLink( 'https://wiki.amapress.fr/contrats/gestion', __( 'Accueil de l\'espace contrat', 'amapress' ) ) .
										       '</li></ul></p>';
									},
								),
							)
						),
						'Shortcodes'                                    => array(
							'id'         => 'shortcodes',
							'capability' => 'edit_pages',
							'options'    => array(
								array(
									'id'     => 'shortcodes_cust',
									'name'   => __( 'Shortcodes', 'amapress' ),
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
										$ret .= '<table class="display compact" id="shortcodes-desc-table">';
										$ret .= '<thead><tr><th>' . __( 'Shortcode', 'amapress' ) . '</th><th data-sortable="false">' . __( 'Shortcode &gt; Paramètres', 'amapress' ) . '</th></tr></thead>';
										$ret .= '<tbody>';
										global $all_amapress_shortcodes_descs;
										ksort( $all_amapress_shortcodes_descs );
										foreach ( $all_amapress_shortcodes_descs as $k => $desc ) {
											if ( empty( $desc['desc'] ) ) {
												continue;
											}
											ksort( $desc['args'] );
											if ( ! empty( $desc['args'] ) ) {
												foreach ( $desc['args'] as $kk => $vv ) {
													$ret .= '<tr><td><strong>' .
													        esc_html( $k ) . '</strong><br/><em>' .
													        esc_html( $desc['desc'] ) . '</em></td><td style="padding-left: 1em">' .
													        '<strong>' . esc_html( $kk ) . '</strong>: ' . ( wp_strip_all_tags( $vv ) != $vv ? $vv : esc_html( $vv ) ) .
													        '</td></tr>';
												}
											} else {
												$ret .= '<tr><td><strong>' .
												        esc_html( $k ) . '</strong><br/><em>' .
												        esc_html( $desc['desc'] ) . '</em></td><td style="padding-left: 1em"></td></tr>';
											}
										}

										$ret .= '</tbody>';
										$ret .= '</table>';
										$ret .= '<style type="text/css">#shortcodes-desc-table .group { background-color: #3c3c3c; color: #f0f0f0 !important; }</style>';
										$ret .= '<script type="text/javascript">
jQuery(document).ready(function($) {
    var groupColumn = 0;
    var table = $(\'#shortcodes-desc-table\').DataTable({
        "columnDefs": [
            { "visible": false, "targets": groupColumn }
        ],
        "order": [[ groupColumn, \'asc\' ]],
        "displayLength": 25,
        "drawCallback": function ( settings ) {
            var api = this.api();
            var rows = api.rows( {page:\'current\'} ).nodes();
            var last=null;
 
            api.column(groupColumn, {page:\'current\'} ).data().each( function ( group, i ) {
                if ( last !== group ) {
                    $(rows).eq( i ).before(
                        \'<tr class="group"><td>Shortcode - \'+group+\'</td></tr>\'
                    );
 
                    last = group;
                }
            } );
        }
    } );
 
    // Order by the grouping
    $(\'#shortcodes-desc-table tbody\').on( \'click\', \'tr.group\', function () {
        var currentOrder = table.order()[0];
        if ( currentOrder[0] === groupColumn && currentOrder[1] === \'asc\' ) {
            table.order( [ groupColumn, \'desc\' ] ).draw();
        }
        else {
            table.order( [ groupColumn, \'asc\' ] ).draw();
        }
    } );
} );
</script>';

										return $ret;
									}
								)
							)
						),
						'Placeholders - contrat vierge'                 => array(
							'id'         => 'paper_contrat_placeholders',
							'capability' => 'manage_contrats',
							'options'    => array(
								array(
									'id'     => 'paper_contrat_placeholders_cust',
									'name'   => __( 'Placeholders - contrat vierge', 'amapress' ),
									'type'   => 'custom',
									'custom' => function () {
										return AmapressContrat_instance::getPlaceholdersHelp( [], 'paper', false );
									}
								)
							)
						),
						'Placeholders - production'                     => array(
							'id'         => 'pres_prod_contrat_placeholders',
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
							'capability' => 'manage_contrats',
							'options'    => array(
								array(
									'id'     => 'adhesion_contrat_placeholders_cust',
									'name'   => __( 'Placeholders - contrat personnalisé', 'amapress' ),
									'type'   => 'custom',
									'custom' => function () {
										return AmapressAdhesion::getPlaceholdersHelp( [], true, false );
									}
								)
							)
						),
						'Placeholders - bulletin adhésion personnalisé' => array(
							'id'         => 'adhesion_placeholders',
							'capability' => 'edit_adhesion_paiement',
							'options'    => array(
								array(
									'id'     => 'adhesion_placeholders_cust',
									'name'   => __( 'Placeholders - bulletin adhésion personnalisé', 'amapress' ),
									'type'   => 'custom',
									'custom' => function () {
										return AmapressAdhesion_paiement::getPlaceholdersHelp( [], true, false );
									}
								)
							)
						),
						'Placeholders - amapien'                        => array(
							'id'         => 'amapien_placeholders',
							'capability' => 'edit_pages',
							'options'    => array(
								array(
									'id'     => 'amapien_placeholders_cust',
									'name'   => 'amapien',
									'type'   => 'custom',
									'custom' => function () {
										return Amapress::getPlaceholdersHelpTable(
											'amapien-placeholders',
											Amapress::getPlaceholdersHelpForProperties(
												AmapressUser::getProperties()
											),
											'user:de l\'amapien', [], false,
											'%%', '%%', false
										);
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
						'name'       => __( 'Messagerie', 'amapress' ),
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
								echo '<p>' . __( 'Vous pouvez également envoyer un mail via les listes suivantes:', 'amapress' ) . '</p>';
								echo do_shortcode( '[listes-diffusions]' );
							}
						),
						array(
							'name' => __( 'Personalisation de l\'email', 'amapress' ),
							'type' => 'heading',
						),
						array(
							'id'           => 'msg_target',
							'name'         => __( 'Destinataire', 'amapress' ),
							'type'         => 'select',
							'options'      => 'amapress_message_get_targets',
							'required'     => true,
							'after_option' => function ( $option ) {
								echo '<p>' . __( 'Envoyer à : ', 'amapress' ) . '<span id="amapress_msg_target_members"></span></p>';
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
							'name'     => __( 'Type d\'envoi', 'amapress' ),
							'type'     => 'select',
							'options'  => array(
								'bcc'   => __( 'Groupé Bcc : les destinaires sont en copie cachée', 'amapress' ),
								'cc'    => __( 'Groupé Cc : les destinaires sont en copie visible', 'amapress' ),
								'indiv' => __( 'Individuel : un mail par destinaire', 'amapress' ),
							),
							'required' => true,
						),
						array(
							'id'       => 'send_from',
							'name'     => __( 'Emetteur', 'amapress' ),
							'type'     => 'radio',
							'options'  => function () {
								$amapien    = AmapressUser::getBy( amapress_current_user_id() );
								$site_name  = $new = Amapress::getOption( 'email_from_name' );
								$site_email = $new = Amapress::getOption( 'email_from_mail' );
								if ( empty( $site_email ) ) {
									$site_email = amapress_get_default_wordpress_from_email();
								}
								$ret = array(
									'site' => sprintf( '%s (%s)', $site_name, $site_email ),
									'user' => sprintf( '%s (%s)', $amapien->getDisplayName(), $amapien->getEmail() ),
								);

								return $ret;
							},
							'default'  => 'site',
							'required' => true,
						),
						array(
							'id'       => 'msg_subject',
							'name'     => __( 'Objet', 'amapress' ),
							'type'     => 'text',
							'required' => true,
							'default'  => '[AMAP] ',
							'desc'     => function ( $o ) {
								return sprintf( __( 'Ajoutez un préfixe de la forme [%s] pour que le mail soit facilement identifié par vos destinataires', 'amapress' ),
									get_bloginfo( 'name' ) );
							},
						),
						array(
							'id'       => 'msg_content',
							'name'     => __( 'Contenu', 'amapress' ),
							'type'     => 'editor',
							'required' => true,
							'desc'     => __( 'Rédigez votre message', 'amapress' ),
						),
						array(
							'type'      => 'save',
							'save'      => __( 'Envoyer', 'amapress' ),
							'action'    => 'send_message',
							'use_reset' => false,
						),
					),
					'subpages' => array(
						array(
							'title'      => __( 'Messages envoyés', 'amapress' ),
							'menu_icon'  => 'post_type',
							'menu_title' => __( 'Messages envoyés', 'amapress' ),
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