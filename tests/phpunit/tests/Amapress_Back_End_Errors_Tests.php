<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 08/10/2017
 * Time: 22:24
 */

class Amapress_Back_End_Errors_Tests extends Amapress_UnitTestCase {
//	public function adminPage_DoesNot_NoticeOrThrow_Provider() {
//		$this->create_amap();
//
//		$ret = [];
//		foreach ( $this->users as $user_id ) {
//			foreach (
//				array(
//					'Tableau de bord'                          => 'index.php',
//					'Accueil'                                  => 'index.php',
//					'Mises à jour 1'                           => 'update-core.php',
//					''                                         => 'admin.php?page=separator2',
//					'Articles'                                 => 'edit.php',
//					'Tous les articles'                        => 'edit.php',
//					'Ajouter'                                  => 'user-new.php',
//					'Catégories'                               => 'edit-tags.php?taxonomy=category',
//					'Étiquettes'                               => 'edit-tags.php?taxonomy=post_tag',
//					'Médias'                                   => 'options-media.php',
//					'Bibliothèque'                             => 'upload.php',
//					'Pages'                                    => 'edit.php?post_type=page',
//					'Toutes les pages'                         => 'edit.php?post_type=page',
//					'Commentaires 0'                           => 'edit-comments.php',
//					'Demande d\'adhésions 0'                   => 'edit.php?post_type=amps_adh_req&amapress_date=active',
//					'Listes de diffusion'                      => 'edit.php?post_type=amps_mailing',
//					'Producteurs'                              => 'admin.php?page=amapress_gestion_fournisseurs_page',
//					' Producteurs'                             => 'edit.php?post_type=amps_producteur',
//					' Produits'                                => 'edit.php?post_type=amps_produit',
//					' Catégories de produits'                  => 'edit-tags.php?taxonomy=amps_produit_category',
//					'Gestion Adhésions'                        => 'admin.php?page=amapress_gestion_adhesions_page',
//					' Synthèse'                                => 'admin.php?page=contrat_paiements',
//					' Règlements'                              => 'edit.php?post_type=amps_cont_pmt&amapress_date=active',
//					' Configuration'                           => 'edit-tags.php?taxonomy=amps_paiement_category',
//					' GA Edition'                                 => 'edit.php?post_type=amps_adh_per&amapress_date=active',
//					'Gestion Contrats'                         => 'admin.php?page=amapress_gestion_amapiens_page',
//					' Inscriptions'                            => 'edit.php?post_type=amps_adhesion&amapress_date=active',
//					' Présentations web'                       => 'edit.php?post_type=amps_contrat',
//					' GC Edition'                                 => 'edit.php?post_type=amps_contrat_inst&amapress_date=active',
//					' Calendrier'                              => 'admin.php?page=calendar_contrat_paiements',
//					' Quantités'                               => 'admin.php?page=contrats_quantites_next_distrib',
//					'Contenus'                                 => 'admin.php?page=amapress_gestion_contenu_page',
//					' Recettes'                                => 'edit.php?post_type=amps_recette',
//					' News'                                    => 'edit.php?post_type=amps_news',
//					' Paniers'                                 => 'edit.php?post_type=amps_panier&amapress_date=thisweek',
//					'Messagerie'                               => 'admin.php?page=amapress_messages_page',
//					' Messages envoyés'                        => 'edit.php?post_type=amps_message&order=post_date&orderby=DESC',
//					'Évènements'                               => 'admin.php?page=amapress_gestion_events_page',
//					' Distributions hebdomadaires'             => 'edit.php?post_type=amps_distribution&amapress_date=thismonth',
//					' Visites à la ferme'                      => 'edit.php?post_type=amps_visite&amapress_date=next',
//					' Assemblées'                              => 'edit.php?post_type=amps_assemblee&amapress_date=next',
//					' Evènements'                              => 'edit.php?post_type=amps_amap_event&amapress_date=next',
//					' Catégories d\'évènements'                => 'edit-tags.php?taxonomy=amps_amap_event_category',
//					'Etat d\'Amapress'                         => 'admin.php?page=amapress_state',
//					'Paramétrage'                              => 'options-general.php?page=amapress_options_page',
//					' Lieux de distributions'                  => 'edit.php?post_type=amps_lieu',
//					' Mails'                                   => 'admin.php?page=amapress_mail_options_page',
//					' Queue & SMTP'                            => 'options-general.php?page=amapress_mailqueue_options_page',
//					' Liste émargement'                        => 'admin.php?page=amapress_emargement_options_page',
//					' Confidentialité'                         => 'admin.php?page=amapress_confident_options_page',
//					' Contacts public'                         => 'admin.php?page=amapress_contact_options_page',
//					' Listes de diffusion'                     => 'admin.php?page=amapress_mailinglist_options_page',
//					'Contact'                                  => 'admin.php?page=wpcf7',
//					'Formulaires de contact'                   => 'admin.php?page=wpcf7',
//					'Créer un formulaire'                      => 'admin.php?page=wpcf7-new',
//					'Intégration'                              => 'admin.php?page=wpcf7-integration',
//					'Forums'                                   => 'admin.php?page=bbpress',
//					'Tous les forums'                          => 'edit.php?post_type=forum',
//					'Nouveau Forum'                            => 'post-new.php?post_type=forum',
//					'Tools'                                    => 'admin.php?page=gdbbpress_tools',
//					'Sujets'                                   => 'edit.php?post_type=topic',
//					'Tous les Sujets'                          => 'edit.php?post_type=topic',
//					'Nouveau sujet'                            => 'post-new.php?post_type=topic',
//					'Mot-clés du sujet'                        => 'edit-tags.php?taxonomy=topic-tag&post_type=topic',
//					'Réponses'                                 => 'edit.php?post_type=reply',
//					'Toutes les réponses'                      => 'edit.php?post_type=reply',
//					'Nouvelle réponse'                         => 'post-new.php?post_type=reply',
//					'Apparence'                                => 'themes.php',
//					'Thèmes'                                   => 'themes.php',
//					'Personnaliser'                            => 'customize.php?return=%2Fwp-admin%2Fedit.php%3Fpost_type%3Damps_contrat%26generate_test',
//					'Widgets'                                  => 'widgets.php',
//					'Menus'                                    => 'nav-menus.php',
//					'En-tête'                                  => 'admin.php?page=custom-header',
//					'Arrière-plan'                             => 'admin.php?page=custom-background',
//					'Éditeur'                                  => 'plugin-editor.php',
//					'Imports CSV'                              => 'admin.php?page=amapress_import_page',
//					'Espace intermittents'                     => 'admin.php?page=amapress_gestion_intermittence_page',
//					' Intermittents'                           => 'users.php?amapress_contrat=intermittent',
//					' Paniers à échanger'                      => 'edit.php?post_type=amps_inter_panier&amapress_date=active',
//					'Extensions 1'                             => 'plugins.php',
//					'Extensions installées'                    => 'plugins.php',
//					'Utilisateurs'                             => 'users.php',
//					'Tous les utilisateurs'                    => 'users.php',
//					'Votre profil'                             => 'profile.php',
//					'Unconfirmed'                              => 'admin.php?page=unconfirmed',
//					'Rôle dans l\'AMAP'                        => 'edit-tags.php?taxonomy=amps_amap_role_category',
//					'Outils'                                   => 'tools.php',
//					'Outils disponibles'                       => 'tools.php',
//					'Importer'                                 => 'import.php',
//					'Exporter'                                 => 'export.php',
//					'Cron Events'                              => 'admin.php?page=crontrol_admin_manage_page',
//					'Sauvegardes'                              => 'admin.php?page=backupwordpress',
//					'Réglages'                                 => 'options-general.php',
//					'Général'                                  => 'options-general.php',
//					'Écriture'                                 => 'options-writing.php',
//					'Lecture'                                  => 'options-reading.php',
//					'Discussion'                               => 'options-discussion.php',
//					'Permaliens'                               => 'options-permalink.php',
//					'Akismet'                                  => 'admin.php?page=akismet-key-config',
//					' Image Regenerate & Select Crop Settings' => 'admin.php?page=image-regenerate-select-crop-settings',
//					'TinyMCE Advanced'                         => 'admin.php?page=tinymce-advanced',
//					'Cron Schedules'                           => 'admin.php?page=crontrol_admin_options_page',
//					'BBP pack Style'                           => 'admin.php?page=bbp-style-pack',
//					'GitHub Updater'                           => 'admin.php?page=github-updater',
//				) as $name => $url
//			) {
//				$ret["User $user_id / $name"] = [$user_id, $url];
//			}
//		}
//
//		return $ret;
//	}
//
//	/**
//	 * @dataProvider adminPage_DoesNot_NoticeOrThrow_Provider
//	 */
//	public function testAdminPage_DoesNot_NoticeOrThrow( $user_id, $url ) {
//		$this->create_amap();
//
//		$this->loginUser( $user_id );
//
//		$this->call_wp_admin( admin_url( $url ) );
//	}

}