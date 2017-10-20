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

	public static $admin_bar_menu = array();

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
			AmapressEntities::$menu = array(
				array(
					'type'       => 'page',
					'title'      => 'Demande d\'adhésions',
					'icon'       => 'dashicons-universal-access',
					'menu_title' => 'Demande d\'adhésions [adhesion-request-count]',
					'capability' => 'edit_adhesion_request',
					'slug'       => 'edit.php?post_type=amps_adh_req&amapress_date=active',
					'position'   => '25.0',
					'function'   => null,
				),
				array(
					'type'       => 'page',
					'title'      => 'Listes de diffusion',
					'icon'       => 'dashicons-email-alt',
					'menu_title' => 'Listes de diffusion',
					'post_type'  => Amapress_MailingListConfiguration::INTERNAL_POST_TYPE,
					'position'   => '25.1',
					'capability' => 'edit_mailinglist',
					'slug'       => 'edit.php?post_type=' . Amapress_MailingListConfiguration::INTERNAL_POST_TYPE,
					'function'   => null,
				),
				array(
					'type'       => 'page',
					'title'      => 'Etat d\'Amapress',
					'icon'       => 'dashicons-none flaticon-buildings',
					'menu_title' => 'Etat d\'Amapress',
					'capability' => 'manage_amapress',
					'slug'       => 'amapress_state',
					'position'   => '25.14',
					'function'   => 'amapress_echo_and_check_amapress_state_page',
				),
				array(
					'id'       => 'amapress_gestion_fournisseurs_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Producteurs',
						'position'   => '25.2',
						'capability' => 'edit_producteur',
						'icon'       => 'dashicons-none flaticon-tractor',
					),
					'options'  => array(
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
					'tabs'     => array(),
					'subpages' => array(
						array(
							'title'      => 'Producteurs',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Producteurs',
							'post_type'  => 'amps_producteur',
							'capability' => 'edit_producteur',
							'slug'       => 'edit.php?post_type=amps_producteur',
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
							'menu_icon'  => 'dashicons-none ',
							'menu_title' => 'Catégories de produits',
							'capability' => 'edit_produit',
							'slug'       => 'edit-tags.php?taxonomy=amps_produit_category',
						),
					),
				),
				array(
					'id'       => 'amapress_gestion_contenu_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Contenus',
						'position'   => '25.8',
						'capability' => 'manage_contenu',
						'icon'       => 'dashicons-none flaticon-water',
					),
					'options'  => array(
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
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
							'title'      => 'News',
							'menu_icon'  => 'post_type',
							'menu_title' => 'News',
							'post_type'  => 'amps_news',
							'capability' => 'edit_news',
							'slug'       => 'edit.php?post_type=amps_news',
						),
						array(
							'title'      => 'Paniers',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Paniers',
							'post_type'  => 'amps_panier',
							'capability' => 'edit_panier',
							'slug'       => 'edit.php?post_type=amps_panier&amapress_date=thisweek',
						),
					),
				),
				array(
					'id'       => 'amapress_gestion_events_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Évènements',
						'position'   => '25.12',
						'capability' => 'manage_events',
						'icon'       => 'dashicons-none flaticon-interface-2',
					),
					'options'  => array(
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
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
//                        array(
//                            'title' => 'Distributions ponctuelles',
//                            'menu_icon' => 'post_type',
//                            'menu_title' => 'Distributions ponctuelles',
//                            'post_type' => 'amps_commande',
//                            'capability' => 'edit_commande',
//                            'slug' => 'edit.php?post_type=amps_commande&amapress_date=thismonth',
//                        ),
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
							'menu_icon'  => 'dashicons-none flaticon-tool',
							'menu_title' => 'Catégories d\'évènements',
							'capability' => 'edit_amap_event',
							'slug'       => 'edit-tags.php?taxonomy=amps_amap_event_category',
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
						'position'   => '25.4',
						'capability' => 'manage_contrats',
						'icon'       => 'dashicons-none flaticon-pen',
					),
					'options'  => array(
//						array(
//							'name'   => 'Contrat quantités',
//							'type'   => 'custom',
//							'custom' => 'amapress_echo_all_contrat_quantite',
//						),
//						array(
//							'name'   => 'Contrat paiements',
//							'type'   => 'custom',
//							'custom' => 'amapress_echo_all_contrat_paiements_by_date',
//						),
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
					'tabs'     => array(
						'Renouvèlement' => array(
							'desc'    => '',
							'options' => array(
								array(
									'id'      => 'renouv_days',
									'name'    => 'Durée en jour de la période de renouvellement',
									'type'    => 'number',
									'default' => 30,
//                                            'capability' => 'manage_amapress',
								),
								array(
									'type' => 'save',
								),
							)
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
							'subpage'  => true,
							'id'       => 'calendar_contrat_paiements',
							'settings' => array(
								'name'       => 'Calendrier des encaissements des contrats',
								'menu_title' => 'Calendrier',
								'position'   => '25.2',
								'capability' => 'edit_contrat_paiement',
								'icon'       => 'dashicons-none flaticon-pen',
							),
							'options'  => array(),
							'tabs'     => function () {
								$tabs = array();
								foreach ( AmapressContrats::get_active_contrat_instances() as $contrat_instance ) {
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
													return amapress_get_paiement_table_by_dates( intval( $option->settings['contrat_id'] ) );
												},
											),
											array(
												'type' => 'note',
												'desc' => 'ici vous pouvez gérer...'
											),
										)
									);
								}

								return $tabs;
							},
						),
						array(
							'subpage'  => true,
							'id'       => 'contrats_quantites_next_distrib',
							'settings' => array(
								'name'       => 'Quantités à la prochaine distribution',
								'menu_title' => 'Quantités',
								'position'   => '25.2',
								'capability' => 'edit_contrat_instance',
								'icon'       => 'dashicons-none flaticon-pen',
							),
							'options'  => array(),
							'tabs'     => function () {
								$tabs = array();
								foreach ( AmapressContrats::get_active_contrat_instances() as $contrat_instance ) {
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
													return amapress_get_contrat_quantite_datatable( $contrat_id );
												},
											),
											array(
												'type' => 'note',
												'desc' => 'ici vous pouvez gérer...'
											),
										)
									);
								}

								return $tabs;
							},
						),
						//Calendrier
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
							'menu_title' => 'Présentations web',
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
//                        array(
//                            'title' => 'Commandes',
//                            'menu_icon' => 'post_type',
//                            'menu_title' => 'Commandes',
//                            'post_type' => 'amps_user_commande',
//                            'capability' => 'edit_user_commande',
//                            'slug' => 'edit.php?post_type=amps_user_commande&amapress_date=thisweek',
//                        ),
					),
				),
				array(
					'id'       => 'amapress_gestion_adhesions_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Gestion Adhésions',
						'position'   => '25.3',
						'capability' => 'edit_adhesion_paiement',
						'icon'       => 'dashicons-none flaticon-pen',
					),
					'options'  => array(
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
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
							'menu_icon'  => 'dashicons-none flaticon-business-1',
							'menu_title' => 'Configuration',
							'capability' => 'edit_adhesion_paiement',
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
					),
				),
				array(
					'id'       => 'amapress_gestion_intermittence_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Espace intermittents',
						'position'   => '60.4',
						'capability' => 'manage_intermittence',
						'icon'       => 'dashicons-none flaticon-business-2',
					),
					'options'  => array(
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
					'tabs'     => array(
						'Configuration de l\'espace intermittents' => array(
							'desc'       => '',
							'capability' => 'manage_intermittence',
							'options'    => array(
								array(
									'id'      => 'intermittence_enabled',
									'name'    => 'Activer le système des intermittents',
									'type'    => 'checkbox',
									'default' => false,
								),
								array(
									'id'   => 'intermittence_contrat_model',
									'name' => 'Modèle de contrat des intermittents',
									'type' => 'editor',
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Inscriptions'                      => array(
							'desc'    => '',
							'options' => array(
								array(
									'id'      => 'intermittence-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Votre demande d\'adhésion à l\'espace intermittents',
								),
								array(
									'id'      => 'intermittence-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n\nVotre demande d'adhésion à l'espace intermittents (%%post:lien_intermittence%%) a bien été enregistrée\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Désinscriptions'                   => array(
							'desc'    => '',
							'options' => array(
								array(
									'id'      => 'intermittence-desincr-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Votre demande de désinscription de l\'espace intermittents',
								),
								array(
									'id'      => 'intermittence-desincr-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n\nVotre demande de désincription de l'espace intermittents a bien été enregistrée\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Panier disponible'                 => array(
							'desc'    => '',
							'options' => array(
								array(
									'name' => 'Mail aux intermittents',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-dispo-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => '%%post:panier%% à réserver',
								),
								array(
									'id'      => 'intermittence-panier-dispo-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n\nVous recevez ce mail en tant qu'amapien ou intermittent de l'AMAP %%nom_site%%.\n\nUn panier (%%post:panier%%) est proposé à la distribution de %%post:distribution-link%%\n\nSi vous souhaitez le réserver, rendez-vous sur le site des Lapereaux, sur la page %%post:liste-paniers%%\n\nEn cas de problème ou de questions sur le fonctionnement des intermittents, veuillez contacter : xxxx.\n\nSi vous avez des questions plus générale sur l’AMAP des Lapereaux, vous pouvez écrire à xxxx.\n\n%%nom_site%%" ),
								),
								array(
									'name' => 'Mail à l\'amapien proposant son panier',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-on-list-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Votre %%post:panier%% a été mis sur la liste des paniers à échanger',
								),
								array(
									'id'      => 'intermittence-panier-on-list-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\nVotre %%post:panier%% a été mis sur la liste des paniers à échanger\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Panier reprise - demande'          => array(
							'desc'    => '',
							'options' => array(
								array(
									'name' => 'Mail à l\'amapien proposant son panier',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-repris-ask-adherent-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Demande de reprise %%post:panier%% par %%post:repreneur-nom%%',
								),
								array(
									'id'      => 'intermittence-panier-repris-ask-adherent-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\nUne demande a été faite par %%post:repreneur%% pour votre panier (%%post:panier%%) à la distribution %%post:distribution%%\n\nVeuillez valider ou rejeter cette demande dans %%post:mes-echanges%%\n\n%%nom_site%%" ),
								),
								array(
									'name' => 'Mail à l\'amapien repreneur',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-repris-ask-repreneur-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'La demande de reprise %%post:panier%% a été envoyée',
								),
								array(
									'id'      => 'intermittence-panier-repris-ask-repreneur-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\nVotre demande pour le panier (%%post:panier%%) à la distribution %%post:distribution%% a été envoyée.\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Panier reprise - validation'       => array(
							'desc'    => '',
							'options' => array(
								array(
									'name' => 'Mail à l\'amapien proposant son panier',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-repris-validation-adherent-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => '%%post:panier%% repris par %%post:repreneur-nom%%',
								),
								array(
									'id'      => 'intermittence-panier-repris-validation-adherent-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\nVotre panier (%%post:panier%%) sera repris par %%post:repreneur%% à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
								),
								array(
									'name' => 'Mail à l\'amapien repreneur',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-repris-validation-repreneur-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => '%%post:adherent-nom%% a accepté la reprise de %%post:panier%%',
								),
								array(
									'id'      => 'intermittence-panier-repris-validation-repreneur-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n%%post:adherent-nom%% a accepté la reprise de (%%post:panier%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Panier reprise - rejet'            => array(
							'desc'    => '',
							'options' => array(
								array(
									'name' => 'Mail à l\'amapien repreneur',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-repris-rejet-adherent-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => '%%post:adherent-nom%% a refusé la reprise de %%post:panier%%',
								),
								array(
									'id'      => 'intermittence-panier-repris-rejet-adherent-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n%%post:adherent-nom%% a refusé la reprise de (%%post:panier%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Panier annulation - adherent'      => array(
							'desc'    => '',
							'options' => array(
								array(
									'name' => 'Mail à l\'amapien proposant son panier',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-adherent-adherent-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Annulation de votre proposition de reprise %%post:panier%%',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-adherent-adherent-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\nVotre panier (%%post:panier%%) a été retiré de l'espace intermittents\n\n%%nom_site%%" ),
								),
								array(
									'name' => 'Mail à l\'amapien repreneur',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-adherent-repreneur-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Annulation de repise %%post:panier%% de %%post:adherent-nom%%',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-adherent-repreneur-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n%%post:adherent%% a annulé la reprise de son panier (%%post:panier%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Mail - Panier annulation - repreneur'     => array(
							'desc'    => '',
							'options' => array(
								array(
									'name' => 'Mail à l\'amapien proposant son panier',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-repreneur-adherent-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Annulation de repise %%post:panier%% par %%post:repreneur-nom%%',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-repreneur-adherent-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\n%%post:repreneur%% a annulé la reprise de votre panier (%%post:panier%%) à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
								),
								array(
									'name' => 'Mail à l\'amapien repreneur',
									'type' => 'heading',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-repreneur-repreneur-mail-subject',
									'name'    => 'Sujet du mail',
									'type'    => 'text',
									'default' => 'Confirmation d\'annulation de repise de %%post:panier%% de %%post:adherent-nom%%',
								),
								array(
									'id'      => 'intermittence-panier-cancel-from-repreneur-repreneur-mail-content',
									'name'    => 'Contenu du mail',
									'type'    => 'editor',
									'default' => wpautop( "Bonjour,\nVous avez annumé la reprise du panier (%%post:panier%%) de %%post:adherent%% à la distribution %%post:distribution%%\n\n%%nom_site%%" ),
								),
								array(
									'type' => 'save',
								),
							)
						),
					),
					'subpages' => array(
						array(
							'title'      => 'Intermittents',
							'menu_icon'  => 'post_type',
							'menu_title' => 'Intermittents',
//                            'post_type' => AmapressAdhesion_intermittence::INTERNAL_POST_TYPE,
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
					),
				),
				array(
					'id'       => 'amapress_options_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Paramétrage',
						'position'   => '25.16',
						'capability' => 'manage_amapress',
						'icon'       => 'dashicons-none flaticon-food-29',
					),
					'options'  => array(
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
					'tabs'     => array(
						'Recettes'   => array(
							'desc'       => '',
							'capability' => 'edit_recette',
							'options'    => array(
								array(
									'id'      => 'recette_models',
									'name'    => 'Modèles de recettes',
									'type'    => 'custom',
									'custom'  => 'amapress_tabs_model_editor',
									'save'    => 'amapress_tabs_model_save',
									'default' => array(
										1 =>
											array(
												'id'   => 'classique',
												'name' => 'Classique',
												'tabs' =>
													array(
														0 => 'ingred=Ingrédients',
														1 => 'prepa=Préparation',
														2 => 'other=Autour de la recette',
														3 => 'source=Source',
													),
											),
									),
								),
								array(
									'id'         => 'recette_default_model',
									'name'       => 'Modèle de recette par défaut',
									'type'       => 'select',
									'options'    => 'amapress_tabs_model_get_options',
									'assoc_prop' => 'recette_models',
									'default'    => 'classique',
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Produits'   => array(
							'desc'       => '',
							'capability' => 'edit_produit',
							'options'    => array(
								array(
									'id'      => 'produit_models',
									'name'    => 'Modèles de produits',
									'type'    => 'custom',
									'custom'  => 'amapress_tabs_model_editor',
									'save'    => 'amapress_tabs_model_save',
									'default' => array(
										1 =>
											array(
												'id'   => 'classique',
												'name' => 'Classique',
												'tabs' =>
													array(
														0 => 'saison=Saison',
														1 => 'histo=Histoire',
														2 => 'conserv=Conservation',
														3 => 'desc=Description',
													),
											),
									),
								),
								array(
									'id'         => 'produit_default_model',
									'name'       => 'Modèle de produit par défaut',
									'type'       => 'select',
									'options'    => 'amapress_tabs_model_get_options',
									'assoc_prop' => 'produit_models',
									'default'    => 'classique',
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Pages'      => array(
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
									'id'   => 'archive-page-template',
									'name' => 'Modèle pour les pages d\'archive',
									'type' => 'select-page-templates',
								),
								array(
									'type' => 'save',
								),
							)
						),
						'Général'    => array(
							'desc'    => '',
							'options' => array(
//                                array(
//                                    'id' => 'enable_timesetter',
//                                    'name' => 'Activer le Time Setter',
//                                    'type' => 'checkbox',
//                                    'capability' => 'manage_options',
//                                ),
								array(
									'id'         => 'email_from_name',
									'name'       => 'Nom de l\'expéditeur des mails du site',
									'type'       => 'text',
									'default'    => get_bloginfo( 'blogname' ),
									'capability' => 'manage_options',
								),
								array(
									'id'         => 'email_from_mail',
									'name'       => 'Adresse mail de l\'expéditeur des mails du site',
									'type'       => 'text',
									'default'    => amapress_get_default_wordpress_from_email(),
									'capability' => 'manage_options',
								),
								array(
									'id'         => 'below_login_message',
									'name'       => 'Message à afficher en dessous du formulaire de connexion',
									'type'       => 'editor',
									'default'    => '',
									'capability' => 'manage_options',
								),
//                                array(
//                                    'id' => 'email_replyto_name',
//                                    'name' => 'Nom de réponse aux mails du site',
//                                    'type' => 'text',
//                                    'default' => '',
//                                    'capability' => 'manage_options',
//                                ),
//                                array(
//                                    'id' => 'email_replyto_mail',
//                                    'name' => 'Adresse mail de réponse aux mails du site',
//                                    'type' => 'text',
//                                    'default' => '',
//                                    'capability' => 'manage_options',
//                                ),
								array(
									'type' => 'save',
								),
							)
						),
						'Google API' => array(
							'desc'    => '',
							'options' => array(
								array(
									'id'         => 'google_map_key',
									'name'       => 'Clé Google API',
									'type'       => 'text',
									'default'    => '',
									'desc'       => function ( $option = null ) {
										ob_start();
										?>
                                        <a
                                                href='https://console.developers.google.com/henhouse/?pb=["hh-1","maps_backend",null,[],"https://developers.google.com",null,["maps_backend","geocoding_backend","directions_backend","distance_matrix_backend","elevation_backend","places_backend"],null]&TB_iframe=true&width=600&height=400'
                                                class="thickbox button-primary"
                                                title="<?php _e( 'Générer une clé d\'API - ( vous devez être connecté à votre compte Google )', 'gmaps-api-key' ); ?>">
											<?php _e( 'Générer une clé d\'API', 'gmaps-api-key' ); ?>
                                        </a>
										<?php echo sprintf( __( 'ou %scliquez ici%s pour Obtenir une clé Google Map', 'geodirectory' ), '<a target="_blank" href=\'https://console.developers.google.com/flows/enableapi?apiid=maps_backend,geocoding_backend,directions_backend,distance_matrix_backend,elevation_backend&keyType=CLIENT_SIDE&reusekey=true\'>', '</a>' ) ?>
										<?php
										return ob_get_clean();
									},
									'capability' => 'manage_options',
								),
								array(
									'type' => 'save',
								),
							),
						),
						//
						'Paiements'  => array(
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
					),
					'subpages' => array(
						array(
							'subpage'  => true,
							'id'       => 'amapress_mail_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Mails',
								'position'   => '25.16',
								'capability' => 'manage_amapress',
								'icon'       => 'dashicons-admin-tools',
							),
							'options'  => array(
								array(
									'type' => 'note',
									'desc' => 'ici vous pouvez gérer...'
								),
							),
							'tabs'     => array(
								'Mail de bienvenue'                                      => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'welcome_mail_subject',
											'name'    => 'Sujet du mail d\'enregistrement',
											'type'    => 'text',
											'default' => '[%%nom_site%%] Votre compte utilisateur',
//                                            'capability' => 'manage_amapress',
										),
										array(
											'id'      => 'welcome_mail',
											'name'    => 'Contenu du mail d\'enregistrement',
											'type'    => 'textarea',
											'default' => "Bonjour %%dest%%\n\nVotre identifiant est : %%dest:login%%\nPour configurer votre mot de passe, rendez-vous à l’adresse suivante :\n%%password_url%%\n\nBien cordialement,\n%%nom_site%%\n%%site_icon_url_link%%",
//                                            'capability' => 'manage_amapress',
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Inscriptions - Evènements (distribution, visite...)'    => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'inscr-event-mail-subject',
											'name'    => 'Sujet du mail',
											'type'    => 'text',
											'default' => 'Votre inscription à %%post:title%%',
										),
										array(
											'id'      => 'inscr-event-mail-content',
											'name'    => 'Contenu du mail',
											'type'    => 'textarea',
											'default' => "Bonjour,\n\nVotre inscription à %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%",
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Désinscriptions - Evènements (distribution, visite...)' => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'desinscr-event-mail-subject',
											'name'    => 'Sujet du mail',
											'type'    => 'text',
											'default' => 'Désinscription de %%post:title%%',
										),
										array(
											'id'      => 'desinscr-event-mail-content',
											'name'    => 'Contenu du mail',
											'type'    => 'textarea',
											'default' => "Bonjour,\n\nVotre désinscription de %%post:titre%% (%%post:lien%%) a bien été prise en compte\n\n%%nom_site%%",
										),
										array(
											'type' => 'save',
										),
									)
								),
								'Adhésions - Contrat'                                    => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'adhesion-contrat-mail-subject',
											'name'    => 'Sujet du mail',
											'type'    => 'text',
											'default' => 'Votre demande d\'adhésion à %%post:title%%',
										),
										array(
											'id'      => 'adhesion-contrat-mail-content',
											'name'    => 'Contenu du mail',
											'type'    => 'textarea',
											'default' => "Bonjour,\n\nVotre demande d'adhésion à %%post:contrat_titre%% (%%post:contrat_lien%%) a bien été enregistrée\n\n%%nom_site%%",
										),
										array(
											'type' => 'save',
										),
									)
								),
							),
						),
						amapress_mailing_queue_menu_options(),
						array(
							'subpage'  => true,
							'id'       => 'amapress_emargement_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Liste émargement',
								'position'   => '25.16',
								'capability' => 'edit_distribution',
								'icon'       => 'dashicons-admin-tools',
							),
							'options'  => array(
								array(
									'type' => 'note',
									'desc' => 'Ici vous pouvez gérer les paramètres de la liste d\'émargement'
								),
							),
							'tabs'     => array(
								'Général' => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'liste-emargement-show-lieu-instructions',
											'name'    => 'Afficher les instructions des lieux',
											'type'    => 'checkbox',
											'default' => true,
										),
										array(
											'id'   => 'liste-emargement-general-message',
											'name' => 'Message général',
											'type' => 'editor',
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
//                                'Mail de rappel' => array(
//                                    'desc' => '',
//                                    'options' => array(
//                                        array(
//                                            'id' => 'welcome_mail_subject',
//                                            'name' => 'Sujet du mail d\'enregistrement',
//                                            'type' => 'text',
//                                            'default' => '[%%nom_site%%] Votre compte utilisateur',
////                                            'capability' => 'manage_amapress',
//                                        ),
//                                        array(
//                                            'id' => 'welcome_mail',
//                                            'name' => 'Contenu du mail d\'enregistrement',
//                                            'type' => 'textarea',
//                                            'default' => "Bonjour %%dest%%\n\nVotre identifiant est : %%dest:login%%\nPour configurer votre mot de passe, rendez-vous à l’adresse suivante :\n%%password_url%%\n\nBien cordialement,\n%%nom_site%%\n%%site_icon_url_link%%",
////                                            'capability' => 'manage_amapress',
//                                        ),
//                                        array(
//                                            'type' => 'save',
//                                        ),
//                                    )
//                                ),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_confident_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Confidentialité',
								'position'   => '25.16',
								'capability' => 'manage_amapress',
								'icon'       => 'dashicons-admin-tools',
							),
							'options'  => array(
//                                'amap_roles' => array(
//                                    'name' => amapress__('Rôles dans l\'AMAP'),
//                                    'type' => 'multicheck-categories',
//                                    'taxonomy' => AmapressUser::AMAP_ROLE,
//                                    'desc' => 'Rôles dans l\'AMAP',
//                'show_column' => false,
//                                ),
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
						array(
							'subpage'  => true,
							'id'       => 'amapress_contact_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Contacts public',
								'position'   => '25.16',
								'capability' => 'manage_amapress',
								'icon'       => 'dashicons-admin-tools',
							),
							'options'  => array(
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
									'post_type' => Amapress::WPCF7_POST_TYPE,
								),
//                                array(
//                                    'type' => 'save',
//                                ),
								array(
									'id'         => 'contrat_info_anonymous',
									'name'       => 'Information de contact pour les contrats',
									'type'       => 'editor',
									'capability' => 'edit_contrat_instances',
									'default'    => '<p><strong>NOUS RENCONTRER</strong><br />Si vous souhaitez nous rencontrer, vous pouvez nous rendre visite lors d’une distribution :<br /> – à compléter</p>
<p><strong>NOUS CONTACTER</strong><br /> Et pour nous contacter, vous pouvez nous envoyer un message à :<br /> <a href="mailto:' . get_option( 'admin_email' ) . '">' . get_option( 'admin_email' ) . '</a></p>'
								),
								array(
									'type' => 'save',
								),
							),
						),
						array(
							'subpage'  => true,
							'id'       => 'amapress_mailinglist_options_page',
							'type'     => 'panel',
							'settings' => array(
								'name'       => 'Listes de diffusion',
								'position'   => '25.16',
								'capability' => 'manage_amapress',
								'icon'       => 'dashicons-admin-tools',
							),
							'options'  => array(
								array(
									'type' => 'note',
									'desc' => 'ici vous pouvez gérer...'
								),
							),
							'tabs'     => array(
								'Ouvaton - Sympa' => array(
									'desc'    => '',
									'options' => array(
										array(
											'id'      => 'ouvaton_mailing_domain',
											'name'    => 'Domaine de la liste de diffusion',
											'type'    => 'text',
											'default' => '',
										),
										array(
											'id'      => 'ouvaton_admin_user',
											'name'    => 'Email de l\'admin',
											'type'    => 'text',
											'default' => '',
										),
										array(
											'id'          => 'ouvaton_admin_pass',
											'name'        => 'Mot de passe',
											'type'        => 'text',
											'is_password' => true,
											'default'     => '',
										),
										array(
											'type' => 'save',
										),
									)
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
					'id'       => 'amapress_import_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Imports CSV',
						'position'   => '60.2',
						'capability' => 'import_csv',
						'icon'       => 'dashicons-none flaticon-farmer',
					),
					'options'  => array(
						array(
							'type' => 'note',
							'desc' => 'ici vous pouvez gérer...'
						),
					),
					'tabs'     => array(
						'Utilisateurs'          => array(
							'desc'       => '',
							'capability' => 'edit_users',
							'options'    => array(
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle',
									'action'    => 'generate_model_user'
								),
								array(
									'type' => 'separator',
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
						'Inscriptions contrats' => array(
							'desc'       => '',
							'capability' => 'edit_adhesion',
							'options'    => array(
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle - mono contrat',
									'action'    => 'generate_model_' . AmapressAdhesion::POST_TYPE,
								),
								array(
									'type'      => 'save',
									'use_reset' => false,
									'save'      => 'Télécharger le modèle - multi contrats',
									'action'    => 'generate_model_' . AmapressAdhesion::POST_TYPE . '_multi',
								),
								array(
									'id'   => 'import_adhesion_default_date_debut',
									'name' => amapress__( 'Date de début par défaut' ),
									'type' => 'date',
									'desc' => 'Date de début',
								),
								array(
									'id'                => 'import_adhesion_default_contrat_instance',
									'name'              => amapress__( 'Contrat par défaut' ),
									'type'              => 'select-posts',
									'post_type'         => 'amps_contrat_inst',
									'autoselect_single' => true,
									'desc'              => 'Contrat',
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
						'Quantités des paniers' => array(
							'desc'       => '',
							'capability' => 'edit_contrat_instance',
							'options'    => array(
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
									'id'     => 'import_contrat_quantites',
									'name'   => 'Importer des quantités pour les contrats',
									'type'   => 'custom',
									'custom' => 'amapress_get_contrat_quantites_import_page',
									'bare'   => true,
//                            'save' => 'amapress_process_adhesions_csv_import',
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
					'id'       => 'amapress_messages_page',
					'type'     => 'panel',
					'settings' => array(
						'name'       => 'Messagerie',
						'position'   => '25.10',
						'capability' => 'publish_messages',
						'icon'       => 'dashicons-email-alt',
					),
					'options'  => array(
						array(
							'id'       => 'msg_target',
							'name'     => 'Destinataire',
							'type'     => 'select',
							'options'  => 'amapress_message_get_targets',
							'required' => true,
						),
						array(
							'id'       => 'send_mode',
							'name'     => 'Type d\'envoi',
							'type'     => 'select',
							'options'  => array(
								'bcc'   => 'Message groupé (Bcc)',
								'cc'    => 'Message groupé avec tout le monde en copie (Cc)',
								'indiv' => 'Message individuel',
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
							'name'     => 'Sujet du mail',
							'type'     => 'text',
							'required' => true,
						),
						array(
							'id'       => 'msg_content',
							'name'     => 'Contenu du mail',
							'type'     => 'editor',
							'required' => true,
						),
						array(
							'id'   => 'msg_content_for_sms',
							'name' => 'Contenu du sms associé',
							'type' => 'textarea',
						),
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


	public static $predef_subpages = array();

	public static function getPostType( $type_name ) {
		$post_types = self::getPostTypes();

		return isset( $post_types[ $type_name ] ) ? $post_types[ $type_name ] : array();
	}

	public static function getPostTypes() {
		if ( ! self::$post_types_initialized ) {
			self::init_posts();
			self::$post_types_initialized = true;
		}

		return self::$post_types;
	}

	private static function init_posts() {
		self::$post_types     = apply_filters( 'amapress_register_entities', array() );
		self::$admin_bar_menu = apply_filters( 'amapress_register_admin_bar_menu_items', array() );
	}

	public static function getPostFieldsLabels( $post_type = null ) {
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

	public static function getPostFieldsValidators() {
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

	public static function getFilteredFields( $post_type ) {
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

	public static function getPostTypeFields( $post_type ) {
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

	public static function getPostFieldsFormatters() {
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
									$labels["amapress_{$post_type}_$k"] = amapress_get_formatter( $post_type, $k, $v['type'], $v['name'] );
								}
							}
						}
					}
					$labels["amapress_{$post_type}_$key"] = amapress_get_formatter( $post_type, $key, $value['type'], $value['name'] );
				}
			}
			wp_cache_set( $key, $labels );
		}

		return $labels;
	}
}