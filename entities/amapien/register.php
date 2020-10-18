<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_amapien' );
function amapress_register_entities_amapien( $entities ) {
	$entities['user'] = array(
		'internal_name'            => 'user',
		'csv_required_fields'      => array( 'user_email', 'first_name', 'last_name' ),
		'other_def_hidden_columns' => array( 'bbp_user_role', 'posts', 'last_name' ),
		'bulk_actions'             => array(
			'amp_resend_welcome' => array(
				'label'    => 'Renvoyer l\'email de bienvenue',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant l\'envoie des emails de bienvenue',
					'0'  => 'Une erreur s\'est produit pendant l\'envoie des emails de bienvenue',
					'1'  => 'Un email de bienvenue a été envoyé avec succès',
					'>1' => '%s emails de bienvenue ont été envoyés avec succès',
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
		'fields'                   => array(
//            'role_desc' => array(
//                'name' => amapress__('Rôle dans l\'AMAP'),
//                'type' => 'text',
//                'desc' => 'Rôle dans l\'AMAP',
//            ),
			'head_amapress4'    => array(
				'id'   => 'fonctions_sect',
				'name' => __( 'Fonctions', 'amapress' ),
				'type' => 'heading',
			),
			'all_roles'         => array(
				'name'        => __( 'Fonctions actuelles', 'amapress' ),
				'show_column' => false,
				'type'        => 'custom',
				'csv_import'  => false,
				'csv_export'  => true,
				'custom'      => function ( $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );
					if ( ! $amapien ) {
						return '';
					}
					$roles = $amapien->getAmapRolesStringLinks();

					return $roles;
				},
				'export'      => function ( $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );
					if ( ! $amapien ) {
						return '';
					}
					$roles = $amapien->getAmapRolesString();

					return $roles;
				}
			),
			'role_desc'         => array(
				'type'        => 'custom',
				'name'        => __( 'Rôle sur le site', 'amapress' ),
				'show_column' => false,
				'csv_import'  => false,
				'custom'      => function ( $user_id ) {
					return '
<p id="fonctions_role_desc">Les rôles suivants donnent des accès spécifiques selon l’intitulé sélectionné</p>
<p><strong>Amap Référent producteur</strong> : <em>Accède aux informations relatives au producteur dont il est référent : contrats, inscriptions…</em>
<br/><span style="text-decoration: underline">Important :</span> Sélectionner et compléter la fiche producteur avec l’utilisateur correspondant</p>
<p><strong>Amap Coordinateur</strong> : <em>Peut éditer le collectif, créer un compte utilisateur, accède aux listes d’émargement, ...</em>
<br /><span style="text-decoration: underline">Important :</span> Membre du collectif, cocher l’étiquette Fonction correspondante ci-dessous</p>
<p><strong>Amap Rédacteur</strong> : <em>Accède à la publication d’articles et de recettes</em></p>
<p><strong>Amapien</strong> : <em>Accède aux information personnalisées disponible sur le site vitrine</em>
<br />Rôle par défaut</p>
<p><strong>Amap Trésorier</strong> : <em>Accède au menu “Gestion adhésion”</em></p>
<p><strong>Amap Producteur</strong> : <em>Accès  à son contrat, aux liste des inscriptions à son contrat, aux produits</em></p>
<p><strong>Amap Responsable</strong> : <em>Accède à toutes les fonctions de gestion de l\'AMAP.</em></p>
<p><strong>Abonné, Contributeur, Auteur, Editeur</strong> sont des rôles Wordpress : ne pas utiliser </p>
<p><strong>Amap Administrateur</strong> : <em>Responsable informatique</em>
<br />Ouvre tous les droits sur le site</p>';
				}
			),
			'amap_roles'        => array(
				'name'        => __( 'Membre du collectif - Rôle dans l’Amap', 'amapress' ),
				'type'        => 'multicheck-categories',
				'taxonomy'    => AmapressUser::AMAP_ROLE,
				'desc'        => '
<p id="amapress_user_amap_roles">Pour identifier ou contacter un membre du collectif via la fonctionnalité trombinoscope du site, sélectionner l’étiquette correspondante ci-dessous ou la <a href="' . admin_url( 'edit-tags.php?taxonomy=amps_amap_role_category' ) . '">créer</a> :</p>
<p><em>Exemple : Accueil nouveaux,  Boite Contact,  Convivialité-apéro,  Coordination associative,  Distributions, Feuille de chou,  Responsable Intermittents,  Ouverture vers l\'extérieur,  Panier solidaire,  Référent miel,  Relais Réseau AMAP IdF,  Responsable légal,  Site internet,  Sortie à la ferme…</em></p>
<p>Pour modifier le collectif : <a href="' . admin_url( 'users.php?page=amapress_collectif' ) . '">Editer le collectif</a></p>',
				'show_column' => false,
				'csv'         => false,
//                'searchable' => true,
			),
			'adh_type'          => array(
				'name'          => __( 'Type', 'amapress' ),
				'type'          => 'custom',
				'show_column'   => true,
				'csv_import'    => false,
				'show_on'       => 'edit-only',
				'desc'          => 'Type d\'adhérent (Principal, Co-adhérent...)',
				'custom'        => function ( $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );

					return $amapien ? $amapien->getAdherentTypeDisplay() : '';
				},
				'custom_column' => function ( $option, $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );
					echo $amapien ? $amapien->getAdherentTypeDisplay() : '';
				}
			),
			'amapien_groups'    => array(
				'name'        => __( 'Groupes d\'amapien', 'amapress' ),
				'type'        => 'multicheck-categories',
				'taxonomy'    => AmapressUser::AMAPIEN_GROUP,
				'desc'        => '
<p id="amapress_user_amapien_groups">Pour indiquer l\'appartenance à un groupe d\'amapiens sans être membre du collectif (par ex, Donateur, Membre d\'une autre AMAP pour l\'organisation de visite à la ferme en commun, Ancien producteur...)</p>',
				'show_column' => false,
				'csv'         => false,
//                'searchable' => true,
			),
			'diffusion'         => array(
				'name'        => __( 'Diffusion', 'amapress' ),
				'type'        => 'custom',
				'show_column' => false,
				'show_on'     => 'edit-only',
				'csv_import'  => false,
				'custom'      => function ( $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );

					$mlgrps = [];
					foreach ( AmapressMailingGroup::getAll() as $mlgrp ) {
						if ( in_array( $amapien->ID, $mlgrp->getMembersIds() ) ) {
							$mlgrps[] = Amapress::makeLink( $mlgrp->getAdminMembersLink(), $mlgrp->getName(), true, true );
						}
					}
					$mllsts = [];
					foreach ( Amapress_MailingListConfiguration::getAll() as $mllst ) {
						if ( in_array( $amapien->ID, $mllst->getMembersIds() ) ) {
							$mllsts[] = Amapress::makeLink( $mllst->getAdminMembersLink(), $mllst->getName(), true, true );
						}
					}
					$ret = '<div id="amapress_user_diffusion">';
					$ret .= '<p>Membres des mailing-listes: ' . ( empty( $mllsts ) ? '<em>aucune</em>' : implode( ', ', $mllsts ) ) . '</p>';
					$ret .= '<p>Membres des Emails groupés: ' . ( empty( $mlgrps ) ? '<em>aucun</em>' : implode( ', ', $mlgrps ) ) . '</p>';
					$ret .= '</div>';

					return $ret;
				},
			),
			'intermittent'      => array(
				'name'              => __( 'Intermittent', 'amapress' ),
				'type'              => 'custom',
				'show_on'           => 'edit-only',
				'custom_csv_sample' => function ( $option, $arg ) {
					return array(
						'true',
						'vrai',
						'oui',
						'1',
						'false',
						'faux',
						'non',
						'0',
					);
				},
				'custom'            => function ( $user_id ) {
					$ret     = '';
					$amapien = AmapressUser::getBy( $user_id, true );
					if ( $amapien ) {
						if ( $amapien->isIntermittent() ) {
							$ret .= '<p id="amapress_user_intermittent">L\'utilisateur est intermittent et reçoit des alertes lorsque des paniers sont occasionnellement disponibles</p>';
							$ret .= '<input class="button button-secondary" type="submit" name="desinscr_intermittent" value="Désinscrire de la liste des intermittents" />';
						} else {
							$ret .= '<p id="amapress_user_intermittent">L\'utilisateur n\'est pas intermittent</p>';
							$ret .= '<input class="button button-secondary" type="submit" name="inscr_intermittent" value="Inscrire sur la liste des intermittents" />';
						}
					}

					return $ret;
				},
				'save'              => function ( $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );
					if ( $amapien ) {
						if ( ! $amapien->isIntermittent() && isset( $_REQUEST['inscr_intermittent'] ) ) {
							$amapien->inscriptionIntermittence( true );
						} else if ( $amapien->isIntermittent() && isset( $_REQUEST['desinscr_intermittent'] ) ) {
							$amapien->desinscriptionIntermittence();
						}
					}

					return true;
				},
				'show_column'       => false,
			),
			'no_renew'          => array(
				'name'           => __( 'Non renouvellement', 'amapress' ),
				'type'           => 'checkbox',
				'desc'           => 'L\'amapien n\'a pas renouvelé',
				'show_on'        => 'edit-only',
				'csv_import'     => false,
				'default'        => 0,
				'col_def_hidden' => true,
			),
			'no_renew_reason' => array(
				'name'           => __( 'Motif', 'amapress' ),
				'type'           => 'text',
				'default'        => '',
				'show_on'        => 'edit-only',
				'csv_import'     => false,
				'desc'           => 'Motif de non renouvellement',
				'col_def_hidden' => true,
			),
			'last_login'      => array(
				'name'                 => __( 'Dernière connexion', 'amapress' ),
				'type'                 => 'custom',
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'csv'                  => false,
				'sort_column'          => 'last_login',
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'custom'               => function ( $user_id ) {
					$last_login = get_user_meta( $user_id, 'last_login', true );
					if ( empty( $last_login ) ) {
						return '';
					}

					return date_i18n( 'd/m/Y H:i:s', intval( $last_login ) );
				}
			),
			'create_date'     => array(
				'name'                 => __( 'Date création', 'amapress' ),
				'type'                 => 'custom',
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'csv'                  => false,
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'custom'               => function ( $user_id ) {
					$amapien       = AmapressUser::getBy( $user_id );
					$creation_date = strtotime( $amapien->getUser()->user_registered );
					if ( empty( $creation_date ) ) {
						return '';
					}

					return date_i18n( 'd/m/Y H:i:s', intval( $creation_date ) );
				}
			),
			'head_amapress0'  => array(
				'id'   => 'amapress_sect',
				'name' => __( 'Amapress', 'amapress' ),
				'type' => 'heading',
			),
			'avatar'          => array(
				'name'            => __( 'Avatar', 'amapress' ),
				'selector-title'  => 'Sélectionnez/téléversez votre photo',
				'selector-button' => 'Utiliser cette photo',
				'type'            => 'upload',
				'custom_save'     => 'amapress_save_user_avatar',
				'desc'            => 'Avatar',
				'show_column'     => false,
			),
			'head_amapress'   => array(
				'id'   => 'address_sect',
				'name' => __( 'Adresses', 'amapress' ),
				'type' => 'heading',
			),
			'adresse'           => array(
				'name'          => __( 'Adresse', 'amapress' ),
				'type'          => 'textarea',
				'desc'          => 'Adresse',
				'searchable'    => true,
				'custom_column' => function ( $option, $user_id ) {
					$amapien = AmapressUser::getBy( $user_id );
					echo $amapien ? $amapien->getFormattedAdresseHtml() : '';
				}
//                'required' => true,
			),
			'code_postal'       => array(
				'name'           => __( 'Code postal', 'amapress' ),
				'type'           => 'text',
				'desc'           => 'Code postal',
				'searchable'     => true,
				'col_def_hidden' => true,
//                'required' => true,
			),
			'ville'             => array(
				'name'           => __( 'Ville', 'amapress' ),
				'type'           => 'text',
				'desc'           => 'Ville',
				'searchable'     => true,
				'col_def_hidden' => true,
//                'required' => true,
			),
			'adresse_localized' => array(
				'name'                   => __( 'Localisé', 'amapress' ),
				'type'                   => 'address',
				'use_as_field'           => false,
				'use_enter_gps'          => true,
				'user'                   => true,
				'csv'                    => false,
				'field_name_prefix'      => 'amapress_user',
				'address_field_name'     => 'amapress_user_adresse',
				'postal_code_field_name' => 'amapress_user_code_postal',
				'town_field_name'        => 'amapress_user_ville',
				'show_on'                => 'edit-only',
			),
			'hidaddr'           => array(
				'name'           => __( 'Trombinoscope', 'amapress' ),
				'type'           => 'checkbox',
				'desc'           => 'Ne pas apparaître sur le trombinoscope',
				'csv_import'     => false,
				'col_def_hidden' => true,
				'default'        => 0,
			),
			'head_amapress2'    => array(
				'id'   => 'phones_sect',
				'name' => __( 'Téléphones', 'amapress' ),
				'type' => 'heading',
			),
			'telephone'         => array(
				'name'       => __( 'Téléphone', 'amapress' ),
				'type'       => 'text',
				'desc'       => 'Téléphone',
				'searchable' => true,
			),
			'telephone2'        => array(
				'name'       => __( 'Téléphone 2', 'amapress' ),
				'type'       => 'text',
				'desc'       => 'Téléphone 2',
				'searchable' => true,
			),
			'telephone3'        => array(
				'name'           => __( 'Téléphone 3', 'amapress' ),
				'type'           => 'text',
				'desc'           => 'Téléphone 3',
				'searchable'     => true,
				'show_on'        => 'edit-only',
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'telephone4'     => array(
				'name'           => __( 'Téléphone 4', 'amapress' ),
				'type'           => 'text',
				'desc'           => 'Téléphone 4',
				'searchable'     => true,
				'show_on'        => 'edit-only',
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'moyen'          => array(
				'name'           => __( 'Moyen préféré', 'amapress' ),
				'type'           => 'select',
				'show_column'    => true,
				'col_def_hidden' => true,
				'options'        => array(
					'mail' => 'Email',
					'tel'  => 'Téléphone',
				),
				'desc'           => 'Moyen de communication préféré',
			),
			'head_amapress6' => array(
				'id'      => 'contrats_sect',
				'name'    => __( 'Contrats', 'amapress' ),
				'type'    => 'heading',
				'show_on' => 'edit-only',
			),
			'adhesions'      => array(
				'name'                     => __( 'Adhésions', 'amapress' ),
				'show_column'              => true,
				'col_def_hidden'           => true,
				'related_posts_count_func' => function ( $user_id ) {
					$adhesions = AmapressAdhesion_paiement::getAllActiveByUserId();
					if ( isset( $adhesions[ $user_id ] ) ) {
						return count( $adhesions[ $user_id ] );
					}

					return 0;
				},
				'include_columns'          => array(
					'title',
					'amapress_adhesion_paiement_period',
					'amapress_adhesion_paiement_date',
					'amapress_adhesion_paiement_amount',
				),
				'datatable_options'        => array(
					'paging' => false,
					'bSort'  => false,
					'info'   => false,
				),
				'type'                     => 'related-posts',
				'query'                    => 'post_type=amps_adh_pmt&amapress_user=%%id%%&orderby=title&order=asc',
			),
			'contrats'       => array(
				'name'                     => __( 'Contrats', 'amapress' ),
				'show_column'              => true,
				'related_posts_count_func' => function ( $user_id ) {
					$adhesions = AmapressAdhesion::getAllActiveByUserId();
					if ( isset( $adhesions[ $user_id ] ) ) {
						return count( $adhesions[ $user_id ] );
					}

					return 0;
				},
				'include_columns'          => array(
					'title',
					'amapress_adhesion_quantite',
					'amapress_adhesion_lieu',
					'amapress_adhesion_date_debut',
					'amapress_total_amount',
				),
				'datatable_options'        => array(
					'paging' => false,
					'bSort'  => false,
					'info'   => false,
				),
				'type'                     => 'related-posts',
				'query'                    => 'post_type=amps_adhesion&amapress_date=active&amapress_user=%%id%%&orderby=title&order=asc',
			),
			'contrats-past'  => array(
				'name'              => __( 'Contrats passés', 'amapress' ),
				'show_column'       => false,
				'include_columns'   => array(
					'title',
					'amapress_adhesion_quantite',
					'amapress_adhesion_lieu',
					'amapress_adhesion_date_debut',
					'amapress_total_amount',
				),
				'datatable_options' => array(
					'paging' => false,
					'bSort'  => false,
					'info'   => false,
				),
				'type'              => 'related-posts',
				'query'             => 'post_type=amps_adhesion&amapress_date=past&amapress_user=%%id%%&orderby=amapress_adhesion_date_debut&order=desc',
			),

			'head_amapress3'     => array(
				'id'   => 'coadh_sect',
				'name' => __( 'Co-paniers', 'amapress' ),
				'type' => 'heading',
			),
			'co-foyer-1'         => array(
				'name'         => __( 'Membre du foyer 1', 'amapress' ),
				'type'         => 'select-users',
				'desc'         => 'Membre du foyer 1',
				'autocomplete' => true,
				'searchable'   => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'co-foyer-2'         => array(
				'name'           => __( 'Membre du foyer 2', 'amapress' ),
				'type'           => 'select-users',
				'desc'           => 'Membre du foyer 2',
				'show_column'    => true,
				'col_def_hidden' => true,
				'searchable'     => true,
				'autocomplete'   => true,
				'show_on'        => 'edit-only',
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			),
			'co-foyer-3'         => array(
				'name'           => __( 'Membre du foyer 3', 'amapress' ),
				'type'           => 'select-users',
				'desc'           => 'Membre du foyer 3',
				'show_column'    => true,
				'col_def_hidden' => true,
				'searchable'     => true,
				'autocomplete'   => true,
				'show_on'        => 'edit-only',
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			),
			'co-adherent-1'      => array(
				'name'         => __( 'Co-adhérent 1', 'amapress' ),
				'type'         => 'select-users',
				'desc'         => 'Co-adhérent 1',
				'autocomplete' => true,
				'searchable'   => true,
				'orderby'      => 'display_name',
				'order'        => 'ASC',
			),
			'co-adherent-2'      => array(
				'name'           => __( 'Co-adhérent 2', 'amapress' ),
				'type'           => 'select-users',
				'desc'           => 'Co-adhérent 2',
				'show_column'    => true,
				'col_def_hidden' => true,
				'searchable'     => true,
				'autocomplete'   => true,
				'show_on'        => 'edit-only',
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			),
			'co-adherent-3'      => array(
				'name'           => __( 'Co-adhérent 3', 'amapress' ),
				'type'           => 'select-users',
				'desc'           => 'Co-adhérent 3',
				'show_column'    => true,
				'col_def_hidden' => true,
				'searchable'     => true,
				'autocomplete'   => true,
				'show_on'        => 'edit-only',
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			),
			'co-adherents'       => array(
				'name'           => __( 'Co-adhérent(s) - sans email', 'amapress' ),
				'type'           => 'text',
				'desc'           => 'Co-adhérent(s) - sans email - nom(s)',
				'searchable'     => true,
				'col_def_hidden' => true,
			),
			'co-adherents-infos' => array(
				'name'        => ' ',
				'type'        => 'text',
				'desc'        => 'Co-adhérent(s) - sans email - téléphone(s)',
				'searchable'  => true,
				'show_column' => false,
			),
			'all-coadherents'    => array(
				'name'            => __( 'Co-adhérents', 'amapress' ),
				'show_column'     => false,
				'include_columns' => array(
					'name',
					'email',
					'role',
					'amapress_user_telephone',
					'amapress_user_adresse',
				),
				'type'            => 'related-users',
				'query'           => 'amapress_coadherents=%%id%%',
			),
//            'co-adherents-mail' => array(
//                'name' => amapress__('Co-adhérent - email'),
//                'type' => 'text',
//                'desc' => 'Co-adhérent(s) - email',
//            ),


			'head_amapress5'     => array(
				'id'   => 'emarg_sect',
				'name' => __( 'Liste Emargement', 'amapress' ),
//				'icon' => 'dashicons-clipboard',
				'type' => 'heading',
			),
			'comment_emargement' => array(
				'name'        => __( 'Commentaire pour la liste émargement', 'amapress' ),
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
		'help_new'                 => array(),
		'help_edit'                => array(),
		'help_view'                => array(),
		'help_profile'             => array(),
		'views'                    => array(
			'_dyn_all_' => 'amapress_user_views',
			'exp_csv'   => true,
		),
		'row_actions'              => array(
			'add_inscription'  => [
				'label'  => 'Ajout Inscription Contrat',
				'href'   => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription&user_id=%id%' ),
				'target' => '_blank',
			],
			'relocate'         => array(
				'label'     => 'Géolocaliser',
				'condition' => function ( $user_id ) {
					$user = AmapressUser::getBy( $user_id );

					return $user && ! empty( $user->getFormattedAdresse() ) && ! $user->isAdresse_localized();
				},
				'confirm'   => true,
			),
			'resend_welcome'   => array(
				'label'   => 'Renvoyer l\'email de bienvenue',
				'confirm' => true,
			),
			'remove_collectif' => array(
				'label'      => 'Supprimer du collectif',
				'confirm'    => true,
				'capability' => 'manage_contrats',
				'condition'  => function ( $user_id ) {
					$user = AmapressUser::getBy( $user_id );

					$user_ids = get_users(
						[ 'amapress_role' => 'collectif', 'fields' => 'ids' ]
					);

					return $user && in_array( $user->ID, $user_ids );
				},
				'show_on'    => 'editor',
			)
		),
	);

	return $entities;
}

//add_action( 'tf_post_save_options_amapress', 'amapress_amapien_affect_coadherents', 10, 2 );
//function amapress_amapien_affect_coadherents( TitanFrameworkMetaBox $metabox, $userID ) {
//	if ( $metabox->post_type != 'user' ) {
//		return;
//	}
//	$allow_partial_coadh = Amapress::getOption( 'allow_partial_coadh' );
//	if ( ! $allow_partial_coadh ) {
//		$user = AmapressUser::getBy( $userID );
//		foreach ( AmapressAdhesion::getUserActiveAdhesions( $userID ) as $adh ) {
//			if ( $adh->getAdherentId() == $userID ) {
//				$adh->setAdherent2( $user->getCoAdherent1() );
//				$adh->setAdherent3( $user->getCoAdherent2() );
//				$adh->setAdherent4( $user->getCoAdherent3() );
//			}
//		}
//	}
//}

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
//	$key        = 'amapress_can_delete_user_safe';
//	$used_users_safe = wp_cache_get( $key );
//	if ( false === $used_users_safe ) {
//		$used_users_safe = [];
//		$single_user_keys = array(
//			'amapress_adhesion_paiement_user',
//			'amapress_adhesion_adherent',
//			'amapress_adhesion_adherent2',
//			'amapress_adhesion_adherent3',
//			'amapress_adhesion_adherent4',
//			'amapress_intermittence_panier_repreneur',
//			'amapress_intermittence_panier_adherent',
//			'amapress_user_commande_amapien',
//			'amapress_visite_participants',
//			'amapress_distribution_responsables',
//			'amapress_amap_event_participants',
//			'amapress_assemblee_generale_participants',
//		);
//		global $wpdb;
//		$meta_query = [];
//		foreach ( $single_user_keys as $single_user_key ) {
//			$meta_query[] = $wpdb->prepare( "($wpdb->postmeta.meta_key = %s)", $single_user_key );
//		}
//
//		$where  = implode( ' OR ', $meta_query );
//		$values = $wpdb->get_col( "SELECT DISTINCT $wpdb->postmeta.meta_value FROM $wpdb->postmeta WHERE $where" );
//		foreach ( $values as $v ) {
//			$v = maybe_unserialize( $v );
//			if ( is_array( $v ) ) {
//				$used_users_safe += $v;
//			} else {
//				$used_users_safe[] = $v;
//			}
//		}
//		$used_users_safe = array_unique( array_map( 'intval', $used_users_safe ) );
//		wp_cache_set( $key, $used_users_safe );
//	}

	$key             = 'amapress_can_delete_user_referents';
	$users_referents = wp_cache_get( $key );
	if ( false === $users_referents ) {
		$users_referents  = [];
		$single_user_keys = array(
			'amapress_lieu_distribution_referent',
			'amapress_producteur_user',
			'amapress_producteur_referent',
			'amapress_producteur_referent2',
			'amapress_producteur_referent3',
			'amapress_contrat_referent',
			'amapress_contrat_referent2',
			'amapress_contrat_referent3',
		);
		$lieux_ids        = Amapress::get_lieu_ids();
		if ( count( $lieux_ids ) > 1 ) {
			foreach ( $lieux_ids as $lieu_id ) {
				$single_user_keys[] = "amapress_producteur_referent_{$lieu_id}";
				$single_user_keys[] = "amapress_producteur_referent2_{$lieu_id}";
				$single_user_keys[] = "amapress_producteur_referent3_{$lieu_id}";
				$single_user_keys[] = "amapress_contrat_referent_{$lieu_id}";
				$single_user_keys[] = "amapress_contrat_referent2_{$lieu_id}";
				$single_user_keys[] = "amapress_contrat_referent3_{$lieu_id}";
			}
		}
		global $wpdb;
		$meta_query = [];
		foreach ( $single_user_keys as $single_user_key ) {
			$meta_query[] = $wpdb->prepare( "($wpdb->postmeta.meta_key = %s)", $single_user_key );
		}

		$where  = implode( ' OR ', $meta_query );
		$values = amapress_get_col_cached( "SELECT DISTINCT $wpdb->postmeta.meta_value FROM $wpdb->postmeta WHERE $where" );
		foreach ( $values as $v ) {
			$v = maybe_unserialize( $v );
			if ( is_array( $v ) ) {
				$users_referents = array_merge( $users_referents, $v );
			} else {
				$users_referents[] = $v;
			}
		}
		$users_referents = array_unique( array_map( 'intval', $users_referents ) );
		wp_cache_set( $key, $users_referents );
	}

//	$key             = 'amapress_can_delete_user_collectif';
//	$collectif_users = wp_cache_get( $key );
//	if ( false === $collectif_users ) {
//		$collectif_users = get_users( wp_parse_args( 'amapress_role=collectif&fields=id' ) );
//		wp_cache_set( $key, $collectif_users );
//	}

	$key                 = 'amapress_can_delete_user_contrats';
	$users_with_contrats = wp_cache_get( $key );
	if ( false === $users_with_contrats ) {
		$users_with_contrats = get_users( wp_parse_args( 'amapress_contrat=active&fields=id' ) );
		wp_cache_set( $key, $users_with_contrats );
	}

	$related_users   = AmapressContrats::get_related_users( $user_id,
		false, null, null, true, true );
	$related_users[] = $user_id;

	$can_delete = true;
	foreach ( $related_users as $id ) {
		if ( in_array( $id, $users_with_contrats ) ) {
			$can_delete = false;
			break;
		}
		if ( in_array( $id, $users_referents ) ) {
			$can_delete = false;
			break;
		}
	}

	//return ! in_array( $user_id, $users_referents ) && count( $related_users ) < ( in_array( $related_users, $user_id ) ? 2 : 1 );
	return $can_delete;
}

//function amapress_import_user_data_validate($v, $k) {
//    if ('user_email' == $k || 'first_name' == $k || )
//}
//add_filter('amapress_import_user_data', 'amapress_import_user_data_validate', 11, 2);

add_filter( 'amapress_register_admin_bar_menu_items', 'amapress_register_admin_bar_menu_items' );
function amapress_register_admin_bar_menu_items( $items ) {
//	$cls_state  = '';
	$dash_state = '';
	if ( current_user_can( 'manage_options' ) ) {
		$state_summary = amapress_get_state_summary();
		if ( $state_summary['error'] > 0 ) {
//			$cls_state  = 'amps-error';
			$dash_state = '<span class="dashicons dashicons-warning" style="color: red"></span>';
		} else if ( $state_summary['warning'] > 0 ) {
//			$cls_state  = 'amps-warning';
			$dash_state = '<span class="dashicons dashicons-admin-tools" style="color: orange"></span>';
		} else {
			$dash_state = '<span class="dashicons dashicons-yes"></span>';
		}
	}

	$contrat_to_renew = get_posts_count( 'post_type=amps_contrat_inst&amapress_date=renew' );
	$this_week_start  = Amapress::start_of_week( amapress_time() );
	$this_week_end    = Amapress::end_of_week( amapress_time() );
	$next_distribs    = AmapressDistribution::getNextDistribs( Amapress::add_a_week( amapress_time(), - 1 ), 3, 3 );
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
		'title'      => 'Semaine passée',
		'capability' => 'edit_distribution',
		'target'     => '_blank',
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
			'target'     => '_blank',
			'href'       => $dist->getListeEmargementHref(),
		);
	}
	$liste_emargement_items[] = array(
		'id'         => 'amapress_emargement_other',
		'title'      => 'Autres',
		'capability' => 'edit_distribution',
		'target'     => '_blank',
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

	$items[]    = array(
		'id'        => 'amapress-site-name',
		'title'     => 'Bienvenu sur le site de ' . get_bloginfo( 'name' ),
		'condition' => function () {
			return ! amapress_can_access_admin();
		}
	);
	$main_items = array(
		array(
			'id'         => 'amapress_add_inscription',
			'title'      => 'Ajout Inscription Contrat',
			'icon'       => 'dashicons-id',
			'capability' => 'edit_contrat_instance',
			'href'       => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription' ),
		),
//		array(
//			'id'         => 'amapress_inscriptions',
//			'title'      => 'Les inscriptions',
//			'capability' => 'edit_contrat_instance',
//			'href'       => admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active' ),
//		),
	);
	$cnt        = AmapressAdhesion::getAdhesionToConfirmCount();
	if ( $cnt ) {
		$main_items[] = array(
			'id'         => 'amapress_inscr_to_confirm',
			'title'      => "<span class='badge'>$cnt</span> Inscriptions à confirmer",
			'capability' => 'edit_contrat_instance',
			'href'       => admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active&amapress_status=to_confirm' ),
		);
	}
	$main_items = array_merge( $main_items,
		array(
			'id'         => 'amapress_contrats',
			'title'      => 'Les contrats',
			'capability' => 'edit_contrat_instance',
			'href'       => admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ),
		),
		array(
			'id'         => 'amapress_add_coinscription',
			'title'      => 'Ajouter un coadhérent',
			'capability' => 'edit_contrat_instance',
			'href'       => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_coadherent' ),
		)
	);

	$inscr_distrib_href = Amapress::get_inscription_distrib_page_href();
	if ( ! empty( $inscr_distrib_href ) ) {
		$main_items[] = array(
			'id'         => 'amapress_inscription_distribution',
			'title'      => 'Responsables Distribution',
			'icon'       => 'dashicons-universal-access-alt',
			'capability' => 'edit_distribution',
			'href'       => $inscr_distrib_href,
		);
	}

	$main_items = array_merge(
		$main_items,
		array(
			array(
				'id'         => 'amapress_quantite_contrats',
				'title'      => 'Quantités Producteurs',
				'capability' => 'edit_distribution',
				'icon'       => 'dashicons-chart-pie',
				'href'       => admin_url( 'admin.php?page=contrats_quantites_next_distrib' ),
			),
			//TODO : prochaine date de remise si referent
//			array(
//				'id'         => 'amapress_calendar_paiements',
//				'title'      => 'Calendrier chèques producteurs',
//				'icon'       => 'fa-menu dashicons-before fa-money-bill',
//				'capability' => 'edit_contrat_paiement',
//				'href'       => admin_url( 'admin.php?page=calendar_contrat_paiements' ),
//			),
			array(
				'id'         => 'amapress_contrat_to_renew',
				'title'      => '<span class="badge">' . $contrat_to_renew . '</span> Contrats à renouveler/clôturer',
				'capability' => 'edit_contrat_instance',
				'icon'       => '',
				'condition'  => function () use ( $contrat_to_renew ) {
					return $contrat_to_renew > 0;
				},
				'href'       => admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=renew' ),
			),
			array(
				'id'         => 'amapress_emargement',
				'title'      => 'Listes Emargement',
				'capability' => 'edit_distribution',
				'icon'       => 'dashicons-clipboard',
				'items'      => $liste_emargement_items
			),
			array(
				'id'         => 'amapress_dists',
				'title'      => 'Distribution Changer Lieu',
				'icon'       => 'dashicons-store',
				'capability' => 'edit_distribution',
				'items'      => $dist_items
			),
//			array(
//				'id'         => 'amapress_rens_paniers',
//				'title'      => 'Distribution Décaler Date',
//				'icon'       => 'dashicons-calendar-alt',
//				'capability' => 'edit_panier',
//				'items'      => $panier_rens_items
//			),
			array(
				'id'         => 'amapress_change_paniers',
				'title'      => 'Distribution Décaler Date',
				'icon'       => 'dashicons-calendar-alt',
				'capability' => 'edit_panier',
				'items'      => $panier_edit_items
			),
			array(
				'id'         => 'amapress_edit_collectif',
				'title'      => 'Editer le collectif',
				'icon'       => 'dashicons-groups',
				'capability' => 'edit_users',
				'href'       => admin_url( 'users.php?page=amapress_collectif' ),
			),
//			array(
//				'id'         => 'amapress_edit_intermittents',
//				'title'      => 'Intermittents',
//				'capability' => 'edit_users',
//				'href'       => admin_url( 'users.php?amapress_contrat=intermittent' ),
//			),
		)
	);

	if ( amapress_current_user_can( 'manage_contrats' ) ) {
		$pre_inscr_href = Amapress::get_logged_inscription_page_href();
		if ( empty( $pre_inscr_href ) ) {
			$pre_inscr_href = Amapress::get_pre_inscription_page_href();
		}
		if ( ! empty( $pre_inscr_href ) ) {
			$main_items[] = array(
				'id'         => 'amapress_goto_preinscr_page',
				'title'      => 'Pré-inscription en ligne',
				'icon'       => 'dashicons-pressthis',
				'capability' => 'read',
				'href'       => $pre_inscr_href,
			);
		}
	}

	if ( amapress_can_access_admin() ) {
		$inter_inscr_href = Amapress::get_page_with_shortcode_href( 'intermittents-inscription', 'amps_inscr_int_page' );
		if ( ! empty( $inter_inscr_href ) ) {
			$main_items[] = array(
				'id'         => 'amapress_goto_interinscr_page',
				'title'      => 'Inscription Intermittent',
				'icon'       => 'dashicons-none flaticon-business-2',
				'capability' => 'read',
				'href'       => $inter_inscr_href,
			);
		}
	}

	if ( amapress_can_access_admin() ) {
		$distrib_inscr_href = Amapress::get_inscription_distrib_page_href();
		if ( ! empty( $distrib_inscr_href ) ) {
			$main_items[] = array(
				'id'         => 'amapress_goto_distinscr_page',
				'title'      => 'Inscription Distrib',
				'icon'       => 'dashicons-store',
				'capability' => 'read',
				'href'       => $distrib_inscr_href,
			);
		}
	}

	$backup_status = amapress_get_updraftplus_backup_status();
	$main_items    = array_merge(
		$main_items,
		array(
			array(
				'id'         => 'amapress_state',
				'title'      => $dash_state . 'Etat Amapress',
				'capability' => 'manage_amapress',
				'href'       => admin_url( 'admin.php?page=amapress_state' ),
			),
			array(
				'id'         => 'amapress_admin_submenu',
				'title'      => 'Admin',
				'capability' => 'manage_options',
				'items'      => [
					array(
						'id'         => 'amapress_pages',
						'title'      => 'Pages du site',
						'capability' => 'manage_options',
						'href'       => admin_url( 'edit.php?post_type=page' ),
					),
					array(
						'id'         => 'amapress_renew_config',
						'title'      => 'Renouvellement',
						'capability' => 'manage_options',
						'href'       => admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=contrat_config' ),
					),
					array(
						'id'         => 'amapress_welcome_mail',
						'title'      => 'Email de bienvenue',
						'capability' => 'manage_amapress',
						'href'       => admin_url( 'options-general.php?page=amapress_site_options_page&tab=welcome_mail' ),
					),
					array(
						'id'         => 'amapress_public_contacts',
						'title'      => 'Contacts public',
						'capability' => 'manage_amapress',
						'href'       => admin_url( 'options-general.php?page=amapress_options_page&tab=amp_public_contacts_config' ),
					),
					array(
						'id'         => 'amapress_log_mails',
						'title'      => 'Logs des emails envoyés',
						'capability' => 'manage_options',
						'href'       => admin_url( 'options-general.php?page=amapress_mailqueue_options_page&tab=amapress_mailqueue_mail_logs' ),
					),
					array(
						'id'         => 'amapress_backup',
						'title'      => ( 'inactive' == $backup_status || 'plugin_missing' == $backup_status ?
								'<span class="dashicons dashicons-warning" style="color:red"></span>' :
								( 'local' == $backup_status ?
									'<span class="dashicons dashicons-warning" style="color:orange"></span>' :
									'<span class="dashicons dashicons-yes" style="color: green"></span>' )
						                ) . 'Sauvegardes',
						'capability' => 'manage_options',
						'href'       => 'active' == amapress_is_plugin_active( 'updraftplus' ) ? admin_url( 'options-general.php?page=updraftplus' ) : admin_url( 'admin.php?page=amapress_state' ),
					),
				]
			),
		)
	);

	$items[] = array(
		'id'        => 'amapress',
		'title'     => '<span class="ab-icon amps-icon"></span><span class="ab-label">Amapress</span>',
		'condition' => function () {
			return amapress_can_access_admin();
		},
		'items'     => $main_items,
	);

	$items[] = array(
		'id'        => 'amapress_help',
		'title'     => '<span class="ab-icon amps-aide dashicons-sos"></span><span class="ab-label">Aide</span>',
		'condition' => function () {
			return amapress_can_access_admin();
		},
		'href'      => admin_url( 'admin.php?page=amapress_help_page&tab=wiki' ),
		'target'    => '_blank',
	);

	$items[] = array(
		'id'         => 'amapress_forum',
		'title'      => '<span class="ab-icon amps-forum dashicons-format-chat"></span><span class="ab-label">Forum des Amap</span>',
		'capability' => 'read',
		'href'       => 'https://forum.amapress.fr',
		'target'     => '_blank',
	);

	return $items;
}

add_filter( 'tf_replace_placeholders_user', function ( $text, $post_id ) {
	$current_user = AmapressUser::getBy( amapress_current_user_id() );
	$text         = amapress_replace_mail_placeholders( $text, $current_user );

	return $text;
}, 10, 2 );

add_action( 'personal_options', 'amapress_add_infos_to_user_editor', 20 );
function amapress_add_infos_to_user_editor( WP_User $user ) {
	$amapien = AmapressUser::getBy( $user );
	echo "<tr class='row-action-wrap'><th scope='row'><label>Liens</label></th><td>
<a href='#contrats_sect'>Contrats</a>, <a href='#fonctions_sect'>Fonctions</a>, <a href='#amapress_user_diffusion'>Diffusion</a>, <a href='#address_sect'>Coordonnées</a>, <a href='#phones_sect'>Téléphones</a>, <a href='#coadh_sect'>Co-paniers</a>, 
	</td></tr>";
	$last_login = get_user_meta( $user->ID, 'last_login', true );
	$user_infos = 'Utilisateur créé le ' . date_i18n( 'd/m/Y H:i:s', strtotime( $user->user_registered ) );
	$user_infos .= ' ; Dernière connexion : ' . ( empty( $last_login ) ? 'jamais' : date_i18n( 'd/m/Y H:i:s', intval( $last_login ) ) );
	echo "<tr class='row-action-wrap'><th scope='row'><label>Infos</label></th><td>$user_infos</td></tr>";

	$is_ref_prod_list   = AmapressContrats::getReferentProducteursAndLieux( $user->ID );
	$is_ref_prod        = ! empty( $is_ref_prod_list );
	$ref_prod_message   = implode( ', ', array_unique( array_map(
		function ( $r ) {
			$prod = AmapressProducteur::getBy( $r['producteur'] );
			if ( empty( $prod ) ) {
				return '';
			}
			$res = [];
			foreach ( $r['contrat_ids'] as $contrat_id ) {
				$contrat = AmapressContrat::getBy( $contrat_id );
				if ( empty( $prod ) || empty( $contrat ) ) {
					continue;
				}
				$res[] = sprintf( 'référent de %1$s / %2$s',
					Amapress::makeLink( $prod->getAdminEditLink(), $prod->getTitle() ),
					Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle() ) );
			}

			return implode( ', ', $res );
		}, $is_ref_prod_list
	) ) );
	$check_role_js_code = 'return true;';
	if ( $is_ref_prod ) {
		$check_role_js_code = 'return ("referent" === value) || ("administrator" === value) || ("responsable_amap" === value) || ("producteur" === value);';
	}
	echo '<script type="text/javascript">
jQuery(function($) {
  $(".user-role-wrap").insertAfter($("#fonctions_role_desc").closest("tr"));
  $("#role").addClass("check_amap_role");
  jQuery.validator.addMethod("check_amap_role", function (value, element) {
	' . $check_role_js_code . '
  }, "<p class=\'error\'>Vous ne pouvez pas diminuer son rôle. L\'utilisateur est actuellement : ' . str_replace( '"', "'", $ref_prod_message ) . '. Vous devez le déassocier de ce(s) producteur(s) avant de changer son rôle.</p>");
});
</script>';
}

add_filter( 'manage_users_columns', 'amapress_add_user_lastname_column' );
function amapress_add_user_lastname_column( $columns ) {
	$columns['last_name'] = 'Nom';

	return $columns;
}

add_filter( 'manage_users_sortable_columns', 'amapress_add_user_lastname_sort_column' );
function amapress_add_user_lastname_sort_column( $columns ) {
	$columns['last_name'] = 'last_name';

	return $columns;
}

add_action( 'manage_users_custom_column', 'amapress_show_user_lastname_column_content', 10, 3 );
function amapress_show_user_lastname_column_content( $value, $column_name, $user_id ) {
	$user = get_userdata( $user_id );
	if ( $user && 'last_name' == $column_name ) {
		return $user->last_name;
	}

	return $value;
}

add_action( 'pre_user_query', 'amapress_lastname_sort_pre_user_query', 15 );
function amapress_lastname_sort_pre_user_query( WP_User_Query $query ) {
	if ( 'last_name' == $query->query_vars['orderby'] ) {
		global $wpdb;
		$query->query_from    .= " LEFT OUTER JOIN $wpdb->usermeta amps_last_name ON $wpdb->users.ID = amps_last_name.user_id AND amps_last_name.meta_key='last_name'";
		$query->query_orderby = "ORDER BY amps_last_name.meta_value " . ( strpos( $query->query_orderby, ' DESC' ) === false ? 'ASC' : 'DESC' );
	}
}

add_action( 'amapress_row_action_user_relocate', 'amapress_row_action_user_relocate' );
function amapress_row_action_user_relocate( $user_id ) {
	$user = AmapressUser::getBy( $user_id );
	if ( $user ) {
		AmapressUsers::resolveUserAddress( $user_id, $user->getFormattedAdresse() );
	}
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_user_resend_welcome', 'amapress_row_action_user_resend_welcome' );
function amapress_row_action_user_resend_welcome( $user_id ) {
	wp_send_new_user_notifications( $user_id, 'user' );

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_user_remove_collectif', 'amapress_row_action_user_remove_collectif' );
function amapress_row_action_user_remove_collectif( $user_id ) {
	$user = AmapressUser::getBy( $user_id );
	if ( ! $user ) {
		wp_die( 'Amapien introuvable' );
	}

	$terms = wp_get_object_terms( $user_id, AmapressUser::AMAP_ROLE,
		array( 'fields' => 'all' ) );
	if ( ! is_wp_error( $terms ) ) {
		/** @var WP_Term $term */
		foreach (
			$terms as $term
		) {
			wp_remove_object_terms( $user_id, $term->term_id, AmapressUser::AMAP_ROLE );
		}
	}
	$user->getUser()->set_role( 'amapien' );

	$refs = AmapressContrats::getReferentProducteursAndLieux( $user->ID );
	foreach ( $refs as $ref ) {
		foreach ( $ref['contrat_ids'] as $contrat_id ) {
			$contrat = AmapressContrat::getBy( $contrat_id );
			if ( $contrat ) {
				$contrat->removeReferent( $user->ID );
			}
		}
		$producteur = AmapressProducteur::getBy( $ref['producteur'] );
		if ( $producteur ) {
			$producteur->removeReferent( $user->ID );
		}
	}

	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'admin_head-users.php', function () {
	$override_title = '';
	if ( ! empty( $_GET['amapress_mllst_id'] ) ) {
		$ml             = Amapress_MailingListConfiguration::getBy( $_GET['amapress_mllst_id'] );
		$override_title = 'Membres de ' . $ml->getName();
	} elseif ( ! empty( $_GET['amapress_mlgrp_id'] ) ) {
		$ml             = AmapressMailingGroup::getBy( $_GET['amapress_mlgrp_id'] );
		$override_title = 'Membres de ' . $ml->getName();
		if ( $ml->getIncludeAdhesionRequest() ) {
			echo amapress_get_admin_notice(
				'Cet Email groupé contient également les ' .
				Amapress::makeLink( admin_url( 'edit.php?post_type=amps_adh_req&amapress_date=active&amapress_status=to_confirm' ),
					'demandes d\'adhésions non confirmées' ),
				'info',
				false, false
			);
		}
	} elseif ( ! empty( $_GET['amapress_role'] ) && 'archivable' == $_GET['amapress_role'] ) {
		$override_title = 'Utilisateurs archivables';
		echo amapress_get_admin_notice(
			'Cette vue contient les comptes utilisateurs archivables (sans contrats en cours, 
			non intermittents, non membres du collectif, non membres de groupes Amapiens). 
			Il est conseillé d\'archiver les contrats terminés (' . Amapress::makeLink( admin_url( 'admin.php?page=contrats_archives' ),
				'Archivage contrats', true, true ) . ') avant d\'archiver les comptes utilisateurs.<br/>
			Pour <strong>archiver</strong> les utilisateurs avant suppression, cliquez sur le lien <em>Exporter<span class="dashicons dashicons-external"></span></em><br/>
			Pour <strong>supprimer</strong> les utilisateurs, cliquez sur la case de sélection globale et choissez l\'action groupée <em>Supprimer</em>',
			'info',
			false, false
		);
	}
	if ( ! empty( $override_title ) ) {
		echo '<script type="">jQuery(function($) { $(".wp-heading-inline").text(' . wp_json_encode( $override_title ) . ');});</script>';
	}
} );

add_action( 'admin_head-user-new.php', function () {
	echo '<script type="">jQuery(function($) { 
      $("#role").closest("tr").insertAfter($("#fonctions_role_desc").closest("tr"));
});</script>';
} );

function amapress_get_user_meta_filter() {
	$ret = array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			array( 'key' => 'pw_user_status', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'pw_user_status', 'value' => 'approved', 'compare' => '=' ),
		),
	);
	if ( ! amapress_can_access_admin() ) {
		$ret[] = array(
			'relation' => 'OR',
			array( 'key' => 'amapress_user_hidaddr', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'amapress_user_hidaddr', 'value' => '0', 'compare' => '=' ),
		);
	}

	return $ret;
}

function amapress_get_new_user_approve_user_status( $user_id ) {
	if ( ! class_exists( 'pw_new_user_approve' ) ) {
		return '';
	}

	$user_status = get_user_meta( $user_id, 'pw_user_status', true );
	if ( empty( $user_status ) ) {
		$user_status = 'approved';
	}

	return $user_status;
}
