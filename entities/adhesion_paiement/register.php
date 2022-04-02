<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_paiement' );
function amapress_register_entities_adhesion_paiement( $entities ) {
	$entities['adhesion_paiement'] = array(
		'internal_name'    => 'amps_adh_pmt',
		'singular'         => __( 'Chèque règlement Adhésions', 'amapress' ),
		'plural'           => __( 'Chèques règlements Adhésions', 'amapress' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'adhesions_paiements',
		'title_format'     => 'amapress_adhesion_paiement_title_formatter',
		'slug_format'      => 'from_title',
		'title'            => false,
		'editor'           => false,
		'menu_icon'        => 'flaticon-business',
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'labels'           => array(
			'add_new'      => __( 'Ajouter', 'amapress' ),
			'add_new_item' => __( 'Saisie chèques adhésion', 'amapress' ),
//            'items_list' => 'xxx',
		),
		'row_actions'      => array(
			'mark_rcv'               => [
				'label'     => __( 'Marquer reçu', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion_paiement::NOT_RECEIVED == AmapressAdhesion_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'mark_rcv_valid'         => [
				'label'     => __( 'Marquer reçu et envoi adhésion validée', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion_paiement::NOT_RECEIVED == AmapressAdhesion_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'send_valid'             => [
				'label' => __( 'Envoi adhésion validée', 'amapress' ),
			],
			'unmark_rcv'             => [
				'label'     => __( 'Marquer Non reçu', 'amapress' ),
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion_paiement::RECEIVED == AmapressAdhesion_paiement::getBy( $adh_id )->getStatus();
				},
			],
			'approve_user'           => [
				'label'     => __( 'Approuver amapien', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $adh_id );

					if ( AmapressAdhesion_paiement::NOT_RECEIVED == $adh->getStatus() ) {
						return false;
					}

					$status = amapress_get_new_user_approve_user_status( $adh->getUserId() );
					if ( empty( $status ) ) {
						return false;
					}

					return 'approved' != $status;
				},
			],
			'generate_bulletin_docx' => [
				'label'     => __( 'Générer le bulletin (DOCX)', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $adh_id );

					return ! empty( $adh ) && ! empty( $adh->getPeriod() ) && ! empty( $adh->getPeriod()->getWordModelId() );
				},
			],
			'generate_bulletin_pdf'  => [
				'label'     => __( 'Générer le bulletin (PDF)', 'amapress' ),
				'condition' => function ( $adh_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $adh_id );

					return ! empty( $adh ) && ! empty( $adh->getPeriod() ) && ! empty( $adh->getPeriod()->getWordModelId() );
				},
			],
		),
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_adhesion_paiement_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'user'               => array(
				'name'         => __( 'Amapien', 'amapress' ),
				'type'         => 'select-users',
				'required'     => true,
				'desc'         => function ( $o ) {
					return sprintf( __( 'Sélectionner un amapien. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="%s" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs', 'amapress' ), admin_url( 'user-new.php' ) );
				},
				'group'        => __( '1/ Adhérent', 'amapress' ),
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'searchable'   => true,
			),
			'adherent_email'     => array(
				'csv_import'           => false,
				'csv_export'           => true,
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'group'                => __( '1/ Adhérent', 'amapress' ),
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'name'                 => __( 'Email', 'amapress' ),
				'type'                 => 'custom',
				'custom'               => function ( $post_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $post_id );
					if ( $adh->getUser() ) {
						$email = $adh->getUser()->getEmail();

						return Amapress::makeLink( 'mailto:' . $email, $email );
					}

					return '';
				}
			),
			'adherent_lastname'  => array(
				'csv_import'           => false,
				'csv_export'           => true,
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'group'                => __( '1/ Adhérent', 'amapress' ),
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'name'                 => __( 'Nom', 'amapress' ),
				'type'                 => 'custom',
				'custom'               => function ( $post_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $post_id );
					if ( $adh->getUser() && $adh->getUser()->getUser() ) {
						return $adh->getUser()->getUser()->last_name;
					}

					return '';
				}
			),
			'adherent_firstname' => array(
				'csv_import'           => false,
				'csv_export'           => true,
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'group'                => __( '1/ Adhérent', 'amapress' ),
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'name'                 => __( 'Prénom', 'amapress' ),
				'type'                 => 'custom',
				'custom'               => function ( $post_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $post_id );
					if ( $adh->getUser() && $adh->getUser()->getUser() ) {
						return $adh->getUser()->getUser()->first_name;
					}

					return '';
				}
			),
			'adherent_address' => array(
				'csv_import'           => false,
				'csv_export'           => true,
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'group'                => __( '1/ Adhérent', 'amapress' ),
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'name'                 => __( 'Adresse', 'amapress' ),
				'type'                 => 'custom',
				'custom'               => function ( $post_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $post_id );
					if ( $adh->getUser() ) {
						return $adh->getUser()->getFormattedAdresse();
					}

					return '';
				}
			),
			'adherent_tel'     => array(
				'csv_import'           => false,
				'csv_export'           => true,
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'group'                => __( '1/ Adhérent', 'amapress' ),
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'name'                 => __( 'Téléphone', 'amapress' ),
				'type'                 => 'custom',
				'custom'               => function ( $post_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $post_id );
					if ( $adh->getUser() ) {
						return implode( ' / ', $adh->getUser()->getPhoneNumbers() );
					}

					return '';
				}
			),
			'adherent_num'     => array(
				'csv_import'           => false,
				'csv_export'           => true,
				'show_column'          => true,
				'show_on'              => 'edit-only',
				'group'                => __( '1/ Adhérent', 'amapress' ),
				'use_custom_as_column' => true,
				'col_def_hidden'       => true,
				'name'                 => __( 'Numéro d\'adhérent', 'amapress' ),
				'type'                 => 'custom',
				'custom'               => function ( $post_id ) {
					$adh = AmapressAdhesion_paiement::getBy( $post_id );
					if ( $adh->getUser() ) {
						return $adh->getUser()->getAdherentNumber();
					}

					return '';
				}
			),
			'period'           => array(
				'name'              => __( 'Période adhésion', 'amapress' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressAdhesionPeriod::INTERNAL_POST_TYPE,
				'desc'              => __( 'Période adhésion', 'amapress' ),
				'group'             => __( '2/ Adhésion', 'amapress' ),
				'import_key'        => true,
				'required'          => true,
				'autoselect_single' => true,
				'top_filter'        => array(
					'name'        => 'amapress_adhesion_period',
					'placeholder' => __( 'Toutes les périodes', 'amapress' )
				),
				'csv_required'      => true,
			),
			'date'             => array(
				'name'         => __( 'Date', 'amapress' ),
				'type'         => 'date',
				'required'     => true,
				'desc'         => __( 'Date d\'émission', 'amapress' ),
				'csv_required' => true,
				'group'        => __( '2/ Adhésion', 'amapress' ),
			),
//            'date_emission' => array(
//                'name' => amapress__('Date d\'émission'),
//                'type' => 'date',
//                'required' => true,
//                'desc' => __('Date d\'émission', 'amapress'),
////                'import_key' => true,
//                'csv_required' => true,
//            ),
			'status'             => array(
				'name'         => __( 'Statut', 'amapress' ),
				'type'         => 'select',
				'group'        => __( '2/ Adhésion', 'amapress' ),
				'options'      => array(
					'not_received' => __( 'Non reçu', 'amapress' ),
					'received'     => __( 'Reçu', 'amapress' ),
					'bank'         => __( 'Remis', 'amapress' ),
				),
				'top_filter'   => array(
					'name'        => 'amapress_status',
					'placeholder' => __( 'Toutes les statuts', 'amapress' )
				),
				'required'     => true,
				'desc'         => __( 'Sélectionner l’option qui convient : Reçu à l’Amap, non reçu à l’Amap, Remis', 'amapress' ),
				'csv_required' => true,
			),
			'intermittent'       => array(
				'name'    => __( 'Intermittent?', 'amapress' ),
				'type'    => 'checkbox',
				'default' => false,
				'group'   => __( '2/ Adhésion', 'amapress' ),
				'desc'    => __( 'Indique une adhésion d\'un intermittent', 'amapress' ),
			),
			'pmt_type'           => array(
				'name'           => __( 'Moyen de règlement principal', 'amapress' ),
				'type'           => 'select',
				'options'        => array(
					'chq' => __( 'Chèque', 'amapress' ),
					'esp' => __( 'Espèces', 'amapress' ),
					'vir' => __( 'Virement', 'amapress' ),
					'mon' => __( 'Monnaie locale', 'amapress' ),
					'hla' => __( 'HelloAsso', 'amapress' ),
				),
				'default'        => 'chq',
				'required'       => true,
				'desc'           => __( 'Moyen de règlement principal : chèques ou espèces ou virement', 'amapress' ),
				'group'          => __( '3/ Règlement', 'amapress' ),
				'show_column'    => true,
				'col_def_hidden' => true,
				'top_filter'     => array(
					'name'        => 'amapress_pmt_type',
					'placeholder' => __( 'Tous les type de paiement', 'amapress' ),
				),
			),
			'numero'             => array(
				'name'       => __( 'Numéro du règlement', 'amapress' ),
				'type'       => 'text',
				'desc'       => __( 'Numéro du chèque ou "Esp." pour un règlement en espèces ou "Vir." pour un virement ou "Mon." pour un règlement en monnaie locale ou HelloAsso', 'amapress' ),
				'group'      => __( '3/ Règlement', 'amapress' ),
				'searchable' => true,
			),
			'banque'             => array(
				'name'       => __( 'Banque', 'amapress' ),
				'type'       => 'text',
				'desc'       => __( 'Banque émettrice', 'amapress' ),
				'group'      => __( '3/ Règlement', 'amapress' ),
				'searchable' => true,
			),
			'emetteur'           => array(
				'name'       => __( 'Emetteur', 'amapress' ),
				'type'       => 'text',
				'desc'       => __( 'Emetteur du règlement', 'amapress' ),
				'group'      => __( '3/ Règlement', 'amapress' ),
				'searchable' => true,
			),
			'categ_editor'       => array(
				'name'       => __( 'Répartition', 'amapress' ),
				'type'       => 'custom',
				'column'     => 'amapress_get_adhesion_paiements_summary',
				'custom'     => 'amapress_get_adhesion_paiements_categories',
				'save'       => 'amapress_save_adhesion_paiements_categories',
				'csv_import' => false,
				'csv_export' => true,
				'group'      => __( '3/ Règlement', 'amapress' ),
			),
			'amount'             => array(
				'name'       => __( 'Montant total', 'amapress' ),
				'type'       => 'readonly',
				'unit'       => '€',
				'desc'       => __( 'Montant total', 'amapress' ),
				'csv_import' => false,
				'group'      => __( '3/ Règlement', 'amapress' ),
			),
			'hla_url'            => array(
				'name'           => __( 'Lien HelloAsso', 'amapress' ),
				'type'           => 'custom',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( '4/ HelloAsso', 'amapress' ),
				'custom'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() || empty( $adh_pmt->getHelloAssoUrl() ) ) {
						return 'NA';
					}

					return Amapress::makeLink( $adh_pmt->getHelloAssoUrl(), __( 'Voir dans le backoffice HelloAsso', 'amapress' ), true, true );
				},
				'column'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() || empty( $adh_pmt->getHelloAssoUrl() ) ) {
						return '';
					}

					return Amapress::makeLink( $adh_pmt->getHelloAssoUrl(), __( 'Voir', 'amapress' ), true, true );
				},
				'export'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() || empty( $adh_pmt->getHelloAssoUrl() ) ) {
						return '';
					}

					return $adh_pmt->getHelloAssoUrl();
				},
			),
			'hla_amount'         => array(
				'name'           => __( 'Montant HelloAsso', 'amapress' ),
				'type'           => 'custom',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( '4/ HelloAsso', 'amapress' ),
				'custom'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() ) {
						return 'NA';
					}

					return Amapress::formatPrice( $adh_pmt->getHelloAssoAmount(), true );
				},
				'column'         => function ( $post_id ) {
					$adh_pmt = AmapressAdhesion_paiement::getBy( $post_id );
					if ( ! $adh_pmt->isHelloAsso() ) {
						return '';
					}

					return Amapress::formatPrice( $adh_pmt->getHelloAssoAmount(), false );
				},
			),
			'lieu'               => array(
				'name'           => __( 'Lieu dist.', 'amapress' ),
				'type'           => 'select-posts',
				'post_type'      => 'amps_lieu',
				'group'          => __( '5/ Informations', 'amapress' ),
				'desc'           => __( 'Lieu de distribution souhaité', 'amapress' ),
				'searchable'     => true,
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'lieu_type'          => array(
				'name'           => __( 'Choix du lieu', 'amapress' ),
				'type'           => 'select',
				'group'          => __( '5/ Informations', 'amapress' ),
				'options'        => array(
					'none' => __( 'Aucun', 'amapress' ),
					'any'  => __( 'N\'importe lequel', 'amapress' ),
					'one'  => __( 'Spécifique', 'amapress' ),
				),
				'default'        => 'one',
				'required'       => true,
				'show_column'    => true,
				'col_def_hidden' => true,
			),
			'message'            => array(
				'name'           => __( 'Message', 'amapress' ),
				'type'           => 'textarea',
				'group'          => __( '4/ Informations', 'amapress' ),
				'desc'           => __( 'Message à l\'AMAP lors de l\'inscription en ligne', 'amapress' ),
				'col_def_hidden' => true,
			),
			'custom_check1'      => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK1,
				'type'           => 'checkbox',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( '5/ Informations', 'amapress' ),
			),
			'custom_check2'      => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK2,
				'type'           => 'checkbox',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( '5/ Informations', 'amapress' ),
			),
			'custom_check3'      => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK3,
				'type'           => 'checkbox',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( '5/ Informations', 'amapress' ),
			),
		),
		'bulk_actions'     => array(
			'amp_adh_pmt_mark_recv'       => array(
				'label'    => __( 'Marquer reçu', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Un règlement a été marqué comme reçu avec succès', 'amapress' ),
					'>1' => '%s règlements ont été marqués comme reçus avec succès',
				),
			),
			'amp_adh_pmt_mark_recv_valid' => array(
				'label'    => __( 'Marquer reçu et envoi adhésion validée', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Un règlement a été marqué comme reçu suivi de l\'envoi adhésion validée', 'amapress' ),
					'>1' => '%s règlements ont été marqués comme reçus suivi d\'envoi adhésions validées',
				),
			),
			'amp_adh_pmt_send_valid'      => array(
				'label'    => __( 'Envoi adhésion validée', 'amapress' ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => __( 'Un envoi adhésion validée effectué avec succès', 'amapress' ),
					'>1' => '%s envois adhésions validées effectués avec succès',
				),
			),
			'amp_adh_pmt_check_1'         => array(
				'label'    => sprintf( __( 'Cocher %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK1 ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => sprintf( __( '%s coché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK1 ),
					'>1' => sprintf( __( '%s coché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK1 ),
				),
			),
			'amp_adh_pmt_uncheck_1'       => array(
				'label'    => sprintf( __( 'Décocher %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK1 ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => sprintf( __( '%s décoché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK1 ),
					'>1' => sprintf( __( '%s décoché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK1 ),
				),
			),
			'amp_adh_pmt_check_2'         => array(
				'label'    => sprintf( __( 'Cocher %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => sprintf( __( '%s coché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
					'>1' => sprintf( __( '%s coché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
				),
			),
			'amp_adh_pmt_uncheck_2'       => array(
				'label'    => sprintf( __( 'Décocher %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => sprintf( __( '%s décoché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
					'>1' => sprintf( __( '%s décoché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
				),
			),
			'amp_adh_pmt_check_3'         => array(
				'label'    => sprintf( __( 'Cocher %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => sprintf( __( '%s coché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
					'>1' => sprintf( __( '%s coché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
				),
			),
			'amp_adh_pmt_uncheck_3'       => array(
				'label'    => sprintf( __( 'Décocher %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
				'messages' => array(
					'<0' => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'0'  => __( 'Une erreur s\'est produit pendant l\'opération', 'amapress' ),
					'1'  => sprintf( __( '%s décoché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
					'>1' => sprintf( __( '%s décoché avec succès', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
				),
			),
		),
	);

	return $entities;
}


function amapress_get_adhesion_paiements_summary( $paiement_id ) {
	$taxes = get_categories( array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'taxonomy'   => 'amps_paiement_category',
		'hide_empty' => false,
	) );
//	$terms   = array_map( function ( $t ) {
//		return $t->term_id;
//	}, wp_get_post_terms( $paiement_id, 'amps_paiement_category' ) );
	$amounts = Amapress::get_post_meta_array( $paiement_id, 'amapress_adhesion_paiement_repartition' );
	if ( empty( $amounts ) ) {
		$amounts = array();
	}
	$ret = array();
	foreach ( $taxes as $tax ) {
//		$tax_used   = in_array( $tax->term_id, $terms );
		$tax_amount = isset( $amounts[ $tax->term_id ] ) ? $amounts[ $tax->term_id ] : 0;
		if ( empty( $tax_amount ) ) {
			$tax_amount = 0;
		}
		if ( $tax_amount < 0.001 ) {
			continue;
		}

		$ret[] = esc_html( sprintf( __( '%s=%.00f€', 'amapress' ), $tax->name, $tax_amount ) );
	}

	return implode( '<br/>', $ret );
}

function amapress_get_adhesion_paiements_categories( $paiement_id ) {
	$taxes   = get_categories( array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'taxonomy'   => 'amps_paiement_category',
		'hide_empty' => false,
	) );
	$terms   = array_map( function ( $t ) {
		return $t->term_id;
	}, wp_get_post_terms( $paiement_id, 'amps_paiement_category' ) );
	$amounts = Amapress::get_post_meta_array( $paiement_id, 'amapress_adhesion_paiement_repartition' );
	if ( empty( $amounts ) ) {
		$amounts = array();
	}
	$ret = '<table>';
	foreach ( $taxes as $tax ) {
		$tax_used   = in_array( $tax->term_id, $terms );
		$tax_amount = $tax_used && isset( $amounts[ $tax->term_id ] ) ? $amounts[ $tax->term_id ] : 0;
		if ( empty( $tax_amount ) ) {
			$tax_amount = 0;
		}
		$ret .= '<tr>';
		$ret .= '<td><label for="amapress_pmt_amount-' . $tax->term_id . '">' . esc_html( $tax->name ) . '</label></td>';
		$ret .= '<td><input type="number" id="amapress_pmt_amount-' . $tax->term_id . '" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="price amapress_pmt_cat_amount" value="' . $tax_amount . '" />€</td>';
		$ret .= '</tr>';
	}
	$ret .= '</table>';
	$ret .= '<script type="text/javascript">
jQuery(function($) {
    var updateAmount = function() {
        var sum = 0;
        $(".amapress_pmt_cat_amount").each(function() {
            sum += parseFloat($(this).val());
        });
        $("#amapress_adhesion_paiement_amount").text(sum.toFixed(2));
     };
    $(".amapress_pmt_cat_amount").on("change paste keyup", updateAmount);
    updateAmount();
});
 </script>';

	return $ret;
}

add_filter( 'amapress_import_adhesion_paiement_apply_default_values_to_posts_meta', 'amapress_import_adhesion_paiement_apply_default_values_to_posts_meta' );
function amapress_import_adhesion_paiement_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_adhesion_paiement_default_period'] )
	     && empty( $postmeta['amapress_adhesion_paiement_period'] ) ) {
		$postmeta['amapress_adhesion_paiement_period'] = $_REQUEST['amapress_import_adhesion_paiement_default_period'];
	}

	return $postmeta;
}

add_filter( 'amapress_posts_adhesion_paiement_model_fields', function ( $fields ) {
	$terms = get_terms( AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
		array(
			'taxonomy'   => AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
			'hide_empty' => false,
		) );
	foreach ( $terms as $term ) {
		/** @var WP_Term $term */
		$key            = "amapress_adhesion_paiement_pmt_{$term->term_id}";
		$fields[ $key ] = $key;
	}

	return $fields;
} );

add_filter( 'amapress_import_posts_adhesion_paiement_custom_fields', function ( $fields ) {
	$terms = get_terms( AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
		array(
			'taxonomy'   => AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
			'hide_empty' => false,
		) );
	foreach ( $terms as $term ) {
		/** @var WP_Term $term */
		$key            = "amapress_adhesion_paiement_pmt_{$term->term_id}";
		$fields[ $key ] = $key;
	}

	return $fields;
} );

add_action( 'amapress_post_adhesion_paiement_import', 'amapress_post_adhesion_paiement_import', 10, 5 );
function amapress_post_adhesion_paiement_import( $post_id, $postdata, $postmeta, $posttaxo, $postcustom ) {
	$import_reps = [];
	$terms       = get_terms( AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
		array(
			'taxonomy'   => AmapressAdhesion_paiement::PAIEMENT_TAXONOMY,
			'hide_empty' => false,
		) );
	foreach ( $terms as $term ) {
		/** @var WP_Term $term */
		if ( ! empty( $postcustom["amapress_adhesion_paiement_pmt_{$term->term_id}"] ) ) {
			$import_reps[ $term->term_id ] = floatval( $postcustom["amapress_adhesion_paiement_pmt_{$term->term_id}"] );
		}
	}

	if ( ! empty( $import_reps ) ) {
		$postmeta['amapress_adhesion_paiement_repartition'] = [];
	} else {
		$postmeta['amapress_adhesion_paiement_repartition'] = get_post_meta( $post_id, 'amapress_adhesion_paiement_repartition', true );
	}
	if ( empty( $postmeta['amapress_adhesion_paiement_repartition'] ) ) {
		$period = AmapressAdhesionPeriod::getBy( $postmeta['amapress_adhesion_paiement_period'] );
		if ( $period ) {
			$rep       = $import_reps;
			$amap_term = intval( Amapress::getOption( 'adhesion_amap_term' ) );
			if ( $amap_term && ! isset( $rep[ $amap_term ] ) ) {
				$rep[ $amap_term ] = $period->getMontantAmap();
			}
			$reseau_amap_term = intval( Amapress::getOption( 'adhesion_reseau_amap_term' ) );
			if ( $reseau_amap_term && ! isset( $rep[ $reseau_amap_term ] ) ) {
				$rep[ $reseau_amap_term ] = $period->getMontantReseau();
			}
			$postmeta['amapress_adhesion_paiement_repartition'] = $rep;
		}
	}
	if ( ! empty( $postmeta['amapress_adhesion_paiement_repartition'] ) ) {
		wp_set_post_terms(
			$post_id,
			array_keys( $postmeta['amapress_adhesion_paiement_repartition'] ),
			'amps_paiement_category' );
		update_post_meta( $post_id, 'amapress_adhesion_paiement_repartition',
			$postmeta['amapress_adhesion_paiement_repartition'] );
		$sum = 0;
		foreach ( $postmeta['amapress_adhesion_paiement_repartition'] as $k => $v ) {
			$sum += $v;
		}
		update_post_meta( $post_id, 'amapress_adhesion_paiement_amount', $sum );
	}
}


function amapress_save_adhesion_paiements_categories( $paiement_id ) {
	if ( isset( $_POST['amapress_pmt_amounts'] ) ) {
		$terms   = array();
		$amounts = array();
		foreach ( $_POST['amapress_pmt_amounts'] as $tax_id => $amount ) {
			if ( $amount > 0 ) {
				$terms[]            = intval( $tax_id );
				$amounts[ $tax_id ] = floatval( $amount );
			}
		}
		$total_amount = 0;
		foreach ( $amounts as $k => $v ) {
			$total_amount += $v;
		}
//        var_dump($terms);
//        var_dump($total_amount);
//        die();
		update_post_meta( $paiement_id, 'amapress_adhesion_paiement_repartition', $amounts );
		update_post_meta( $paiement_id, 'amapress_adhesion_paiement_amount', $total_amount );
		wp_set_post_terms( $paiement_id, $terms, 'amps_paiement_category' );
	}
}

add_filter( 'tf_select_users_title', 'amapress_adhesion_paiement_select_user_title', 10, 3 );
function amapress_adhesion_paiement_select_user_title( $title, $user, $option ) {
	if ( isset( $option->owner->settings['post_type'] )
	     && ( in_array( AmapressAdhesion_paiement::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] )
	          || in_array( AmapressAmapien_paiement::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] )
	          || in_array( AmapressAdhesion::INTERNAL_POST_TYPE, $option->owner->settings['post_type'] ) ) ) {
//    if ($option_id == 'amapress_adhesion_paiement_user') {
		$amapien = AmapressUser::getBy( $user->ID );
		$co      = $amapien->getAdditionalCoAdherents();
		$address = $amapien->getFormattedAdresse();
		if ( ! empty( $address ) ) {
			$name = sprintf( __( '%s (%s)', 'amapress' ), $amapien->getDisplayName(), $address );
		} else {
			$name = $amapien->getDisplayName();
		}
		if ( ! empty( $co ) ) {
			return sprintf( __( '%s (%s) - %s', 'amapress' ), $name, $user->user_email, $co );
		} else {
			return sprintf( __( '%s (%s)', 'amapress' ), $name, $user->user_email );
		}
	}

	return $title;
//    }
//    return $title;
}

add_action( 'amapress_row_action_adhesion_paiement_generate_bulletin_docx', 'amapress_row_action_adhesion_paiement_generate_bulletin_docx' );
function amapress_row_action_adhesion_paiement_generate_bulletin_docx( $post_id ) {
	$adhesion       = AmapressAdhesion_paiement::getBy( $post_id );
	$full_file_name = $adhesion->generateBulletinDoc( true );
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'amapress_row_action_adhesion_paiement_approve_user', 'amapress_row_action_adhesion_paiement_approve_user' );
function amapress_row_action_adhesion_paiement_approve_user( $post_id ) {
	$adhesion = AmapressAdhesion_paiement::getBy( $post_id );
	delete_user_meta( $adhesion->getUserId(), 'pw_user_status' );
	delete_transient( 'new_user_approve_user_statuses' );
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_paiement_generate_bulletin_pdf', 'amapress_row_action_adhesion_paiement_generate_bulletin_pdf' );
function amapress_row_action_adhesion_paiement_generate_bulletin_pdf( $post_id ) {
	$adhesion       = AmapressAdhesion_paiement::getBy( $post_id );
	$full_file_name = $adhesion->generateBulletinDoc( false );
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'init', function () {
	global $pagenow;
	if ( is_main_query() && 'admin.php' == $pagenow
	     && count( $_GET ) == 1
	     && isset( $_GET['page'] )
	     && ( 'adhesion_paiements' == $_GET['page'] || 'contrat_paiements' == $_GET['page'] ) ) {
		wp_redirect_and_exit( add_query_arg( 'amapress_contrat', 'active' ) );
	}
} );

add_filter( 'amapress_gestion-adhesions_page_adhesion_paiements_default_hidden_columns', function ( $hidden ) {
	return array_merge( $hidden, [
		'amapress_user_no_renew',
		'amapress_user_no_renew_reason',
		'amapress_user_last_login',
		'amapress_user_adresse',
		'amapress_user_hidaddr',
		'amapress_user_telephone2',
		'amapress_user_telephone3',
		'amapress_user_telephone4',
		'amapress_user_co-adherent-1',
		'amapress_user_co-adherent-2',
		'amapress_user_co-adherent-3',
		'amapress_user_co-foyer-1',
		'amapress_user_co-foyer-2',
		'amapress_user_co-foyer-3',
		'amapress_user_co-adherents',
		'amapress_user_co-adherents-infos',
	] );
} );

add_action( 'amps_ha_synchro', function () {
	amapress_fetch_helloasso_orders();
} );

add_action( 'tf_custom_admin_amapress_action_test_helloasso_access', function () {
	amapress_fetch_helloasso_orders( null, true );
	echo '<p style="color:green">Connecté à l\'API HelloAsso avec succès !</p>';
	die();
} );

function amapress_fetch_helloasso_orders( $date = null, $interactive = false ) {
	$ha_cid  = Amapress::getOption( 'ha_cid' );
	$ha_csec = Amapress::getOption( 'ha_csec' );
	if ( defined( 'AMAPRESS_HELLOASSO_API_CLIENT_SECRET' ) ) {
		$ha_csec = AMAPRESS_HELLOASSO_API_CLIENT_SECRET;
	}
	if ( empty( $ha_cid ) || empty( $ha_csec ) ) {
		if ( $interactive ) {
			wp_die( 'Clés API HelloAsso non définies (Client Id et Client Secret)' );
		}

		return;
	}

	$adh_period = AmapressAdhesionPeriod::getCurrent( $date );
	if ( ! $adh_period ) {
		if ( $interactive ) {
			wp_die( 'Pas de période d\'adhésion en cours' );
		}

		return;
	}
	try {
		// Returns true if the item exists in cache
		$exists = function () {
			return false !== get_transient( 'amps_ha_tk' );
		};
		$set    = function ( array $value ) {
			set_transient( 'amps_ha_tk', $value );
		};
		$get    = function () {
			$token = get_transient( 'amps_ha_tk' );

			return false !== $token ? $token : null;
		};
		$delete = function () {
			delete_transient( 'amps_ha_tk' );
		};

		$reauth_client      = new GuzzleHttp\Client( [
			'base_uri' => 'https://api.helloasso.com/oauth2/token',
		] );
		$reauth_config      = [
			'client_id'     => $ha_cid,
			'client_secret' => $ha_csec,
		];
		$grant_type         = new kamermans\OAuth2\GrantType\ClientCredentials(
			$reauth_client, $reauth_config );
		$refresh_grant_type = new kamermans\OAuth2\GrantType\RefreshToken(
			$reauth_client, $reauth_config );
		$oauth              = new kamermans\OAuth2\OAuth2Middleware(
			$grant_type, $refresh_grant_type );
		$oauth->setTokenPersistence( new kamermans\OAuth2\Persistence\ClosureTokenPersistence(
			$set, $get, $delete, $exists ) );

		$stack = GuzzleHttp\HandlerStack::create();
		$stack->push( $oauth );

		$client = new GuzzleHttp\Client( [
			'handler' => $stack,
			'auth'    => 'oauth',
		] );

		$organizationSlug = $adh_period->getHelloAssoOrganizationSlug();
		$formSlug         = $adh_period->getHelloAssoFormSlug();
		if ( empty( $organizationSlug ) || empty( $formSlug ) ) {
			if ( $interactive ) {
				wp_die( 'Pas de formulaire d\'adhésion HelloAsso pour la période d\'adhsion en cours' );
			}

			return;
		}
		$response = $client->get( "https://api.helloasso.com/v5/organizations/{$organizationSlug}/forms/Membership/{$formSlug}/orders",
			[
				'query' => [
					'pageSize'    => 200,
					'from'        => date( 'c', Amapress::add_days( amapress_time(), - 14 ) ),
					'withDetails' => 'true'
				]
			]
		);

		$orders = json_decode( $response->getBody() );
		foreach ( $orders->data as $order ) {
			if ( $interactive ) {
				echo '<p>Importing ' . $order->id . '</p>';
			}
			amapress_process_helloasso_order( $order );
		}
	} catch ( Exception $exception ) {
		$php_errormsg = 'Error fetching HelloAsso : ' . $exception->__toString();
		if ( $interactive ) {
			wp_die( esc_html( $php_errormsg ) );
		} else {
			error_log( $php_errormsg ); //phpcs:ignore
		}
	}
}

/**
 * @param $order
 *
 * @return AmapressAdhesionPeriod[]|void
 */
function amapress_process_helloasso_order( $order ) {
	$total            = $order->amount->total;
	$payer            = ! empty( $order->payer ) ? $order->payer : null;
	$formSlug         = $order->formSlug;
	$organizationSlug = $order->organizationSlug;
	$numero           = $order->id;
	$order_date       = strtotime( $order->date );
	if ( empty( $order_date ) ) {
		$order_date = amapress_time();
	}

	if ( AmapressAdhesion_paiement::hasHelloAssoId( $numero ) ) {
		return;
	}

	$periods    = array_filter(
		AmapressAdhesionPeriod::getAll(),
		function ( $p ) use ( $formSlug ) {
			/** @var AmapressAdhesionPeriod $p */
			return 0 == strcasecmp( trim( $p->getHelloAssoFormSlug() ), trim( $formSlug ) );
		} );
	$adh_period = array_shift( $periods );
	if ( empty( $adh_period ) ) {
		$adh_period = AmapressAdhesionPeriod::getCurrent( $order_date );
	}
	if ( $adh_period ) {
		$default_email        = empty( $payer ) || ! isset( $payer->email ) ? '' : $payer->email;
		$default_firstName    = empty( $payer ) || ! isset( $payer->firstName ) ? '' : $payer->firstName;
		$default_lastName     = empty( $payer ) || ! isset( $payer->lastName ) ? '' : $payer->lastName;
		$default_address      = empty( $payer ) || ! isset( $payer->address ) ? '' : $payer->address;
		$default_zipCode      = empty( $payer ) || ! isset( $payer->zipCode ) ? '' : $payer->zipCode;
		$default_city         = empty( $payer ) || ! isset( $payer->city ) ? '' : $payer->city;
		$default_phone        = '';
		$multiple_used_emails = [];
		$existing_adhesions   = [];
		if ( isset( $order->items ) && is_array( $order->items ) ) {
			foreach ( $order->items as $item ) {
				if ( 'Membership' == $item->type ) {
					if ( isset( $item->user ) ) {
						$default_firstName = $item->user->firstName;
						$default_lastName  = $item->user->lastName;
					}
					if ( isset( $item->amount ) ) {
						$total = $item->amount;
					}
					if ( isset( $item->customFields ) && is_array( $item->customFields ) ) {
						foreach ( $item->customFields as $custom_field ) {
							if ( 0 === strcasecmp( Amapress::getOption( 'helloasso-email-field-name' ), $custom_field->name ) ) {
								$default_email = $custom_field->answer;
							} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-phone-field-name' ), $custom_field->name ) ) {
								$default_phone = $custom_field->answer;
							} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-address-field-name' ), $custom_field->name ) ) {
								$default_address = $custom_field->answer;
							} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-zipcode-field-name' ), $custom_field->name ) ) {
								$default_zipCode = $custom_field->answer;
							} elseif ( 0 === strcasecmp( Amapress::getOption( 'helloasso-city-field-name' ), $custom_field->name ) ) {
								$default_city = $custom_field->answer;
							}
						}
					}

					if ( ! isset( $multiple_used_emails[ $default_email ] ) ) {
						$multiple_used_emails[ $default_email ] = 0;
					}
					$multiple_used_emails[ $default_email ] += 1;

					$user_id = null;
					$user    = get_user_by( 'email', $default_email );
					if ( $user ) {
						$user_id = $user->ID;
					}
					if ( ! $user_id || Amapress::getOption( 'helloasso-upd-exist' ) ) {
						$user_id = amapress_create_user_if_not_exists(
							$default_email,
							$default_firstName,
							$default_lastName,
							sprintf( __( '%s, %s %s', 'amapress' ), $default_address, $default_zipCode, $default_city ),
							$default_phone,
							'both'
						);
					}

					delete_user_meta( $user_id, 'pw_user_status' );
					delete_transient( 'new_user_approve_user_statuses' );

					$pmt = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period );
					if ( $pmt && $pmt->isHelloAsso() && $pmt->getNumero() != $numero ) {
						$existing_adhesions[] = $pmt;
					}
					$pmt = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period, true );
					//is helloasso
					$pmt->setHelloAsso(
						$total / 100.0,
						"https://www.helloasso.com/associations/{$organizationSlug}/adhesions/{$formSlug}/administration",
						$numero,
						$order_date,
						Amapress::toBool( Amapress::getOption( 'helloasso-auto-confirm' ) )
					);

					$pmt->sendConfirmationsAndNotifications(
						Amapress::toBool( Amapress::getOption( 'helloasso-send-confirm' ) ),
						Amapress::toBool( Amapress::getOption( 'helloasso-notif-tresoriers' ) ),
						Amapress::getOption( 'helloasso-notif-others' ),
						false
					);
				}
			}
		}

		foreach ( $multiple_used_emails as $used_email => $cnt ) {
			if ( $cnt <= 1 ) {
				unset( $multiple_used_emails[ $used_email ] );
			}
		}
		if ( ! empty( $multiple_used_emails ) || ! empty( $existing_adhesions ) ) {
			$tresoriers = [];
			foreach ( get_users( "role=tresorier" ) as $tresorier ) {
				$user_obj   = AmapressUser::getBy( $tresorier );
				$tresoriers = array_merge( $tresoriers, $user_obj->getAllEmails() );
			}
			$tresoriers[] = get_option( 'admin_email' );

			amapress_wp_mail(
				$tresoriers,
				'Adhésion HelloAsso non importable',
				"Bonjour,\nUne adhésion HelloAsso ne peut pas être importée :\n" .
				( ! empty( $multiple_used_emails ) ? sprintf( "- %s ont été utilisées pour plusieurs adhésions\n", implode( ', ', array_keys( $multiple_used_emails ) ) ) : '' ) .
				( ! empty( $existing_adhesions ) ? implode( "\n", array_map( function ( $adh ) {
					/** @var AmapressAdhesion_paiement $adh */
					return sprintf( '- L\'adhérent %s (s%) a fait une nouvelle adhésion',
						$adh->getUser()->getDisplayName(), $adh->getUser()->getEmail() );
				}, $existing_adhesions ) ) : '' )
			);
		}
	}

	return $periods;
}

add_action( 'admin_post_nopriv_helloasso', function () {
	if ( ! isset( $_REQUEST['key'] ) || amapress_sha_secret( 'helloasso' ) != $_REQUEST['key'] ) {
		$body = file_get_contents( 'php://input' );
		@error_log( sprintf( 'Invalid HelloAsso callback on (%s): %s', $_SERVER['REQUEST_URI'], $body ) );
		wp_die( __( 'Accès invalide', 'amapress' ) );
	}

	$body = file_get_contents( 'php://input' );
	@error_log( __( 'HelloAsso callback: ', 'amapress' ) . $body );

	$json = json_decode( $body );
	if ( empty( $json ) ) {
		wp_die( __( 'Accès invalide', 'amapress' ) );
	}

	if ( __( 'Order', 'amapress' ) == $json->eventType ) {
		$order    = $json->data;
		$formType = $order->formType;
		if ( 'Membership' != $formType ) {
			exit();
		}

		$numero = $order->id;
		if ( AmapressAdhesion_paiement::hasHelloAssoId( $numero ) ) {
			@error_log( __( 'HelloAsso callback: already received', 'amapress' ) . $body );

			return;
		}

		amapress_process_helloasso_order( $order, $body );
	}
} );

add_filter( 'cron_schedules', function ( $new_schedules ) {
	$ha_fetch = intval( Amapress::getOption( 'ha_fetch' ) );
	if ( $ha_fetch <= 0 ) {
		$ha_fetch = 1;
	}
	$schedules['amps_ha_fetch'] = array(
		'interval' => $ha_fetch * 3600,
		'display'  => __( 'HelloAsso fetch interval' )
	);

	return $new_schedules;
} );

function amapress_create_user_and_adhesion_assistant( $post_id, TitanFrameworkOption $option ) {
	if ( isset( $_REQUEST['user_id'] ) ) {
		echo '<h4>2/ Adhésion</h4>';

		$user = AmapressUser::getBy( $_REQUEST['user_id'] );

		echo '<hr />';
		echo $user->getDisplay();
		echo '<p>' . Amapress::makeButtonLink( $user->getEditLink(), __( 'Modifier', 'amapress' ), true, true ) . '</p>';
		echo '<hr />';


		$periods = array_filter(
			AmapressAdhesionPeriod::getAll(),
			function ( $p ) {
				return $p->getDate_fin() > Amapress::start_of_day( amapress_time() );
			} );
		usort( $periods, function ( $a, $b ) {
			/** @var AmapressAdhesionPeriod $a */
			/** @var AmapressAdhesionPeriod $b */
			return strcmp( $a->getDate_debut(), $b->getDate_debut() );
		} );
		echo '<p><strong>' . __( 'Ses adhésions :', 'amapress' ) . '</strong></p>';
		echo '<ul style="list-style-type: circle">';
		foreach ( $periods as $period ) {
			$adh = AmapressAdhesion_paiement::getForUser( $user->ID, $period );
			echo '<li style="margin-left: 35px">';
			echo esc_html( $period->getTitle() ) . __( ' : ', 'amapress' );
			if ( $adh ) {
				$lnk = current_user_can( 'edit_post', $adh->ID ) ?
					'<a target="_blank" href="' . esc_attr( $adh->getAdminEditLink() ) . '" >' . __( 'Voir', 'amapress' ) . '</a>&nbsp;:&nbsp;' : '';
				echo $lnk . esc_html( $adh->getTitle() );
			} else {
				echo '<a target="_blank" href="' .
				     esc_attr( admin_url( sprintf( 'post-new.php?post_type=amps_adh_pmt&amapress_adhesion_paiement_user=%d&amapress_adhesion_paiement_period=%d&amapress_adhesion_paiement_date=%s',
					     $user->ID, $period->ID, date_i18n( TitanFrameworkOptionDate::$default_date_format, amapress_time() ) ) ) ) .
				     '" >' . __( 'Ajouter une adhésion pour la période', 'amapress' ) . '</a>';
			}

			echo '</li>';
		}
		if ( empty( $periods ) ) {
			echo '<li>' . __( 'Aucune période d\'adhésion', 'amapress' ) . '</li>';
		}
		echo '</ul>';
	} else {
		echo '<h4>1/ Choisir un utilisateur ou le créer</h4>';
		$options       = [];
		$all_user_adhs = AmapressAdhesion_paiement::getAllActiveByUserId();
		amapress_precache_all_users();
		/** @var WP_User $user */
		foreach ( get_users() as $user ) {
			if ( ! empty( $all_user_adhs[ $user->ID ] ) ) {
				$options[ $user->ID ] = sprintf( __( '%s[%s] (adhérent)', 'amapress' ), $user->display_name, $user->user_email );
			} else {
				$options[ $user->ID ] = sprintf( __( '%s[%s] (non adhérent)', 'amapress' ), $user->display_name, $user->user_email );
			}
		}

		echo '<form method="post" id="existing_user">';
		echo '<input type="hidden" name="action" value="existing_user" />';
		wp_nonce_field( 'amapress_gestion_adhesions_page', TF . '_nonce' );
		echo '<select style="max-width: none; min-width: 50%;" id="user_id" name="user_id" class="autocomplete" data-placeholder="' . esc_attr__( 'Sélectionner un utilisateur', 'amapress' ) . '">';
		tf_parse_select_options( $options, isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null );
		echo '</select><br />';
		echo '<input type="submit" class="button button-primary" value="' . esc_attr__( 'Choisir', 'amapress' ) . '" />';
		echo '</form>';

		echo '<p><strong>' . __( 'OU', 'amapress' ) . '</strong></p>';

		echo '<form method="post" id="new_user">';
		echo '<input type="hidden" name="action" value="new_user" />';
		wp_nonce_field( 'amapress_gestion_adhesions_page', TF . '_nonce' );
		echo '<table style="min-width: 50%">';
		echo '<tr>';
		echo '<th style="text-align: left; width: auto"><label style="width: 10%" for="email">' . __( 'Email: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="email" name="email" class="required email emailDoesNotExists" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="last_name">' . __( 'Nom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="first_name">' . __( 'Prénom: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="tel">' . __( 'Téléphone: ', 'amapress' ) . '</label></th>
<td><input style="width: 100%" type="text" id="tel" name="tel" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="address">' . __( 'Adresse: ', 'amapress' ) . '</label></th>
<td><textarea style="width: 100%" rows="8" id="address" name="address" class=""></textarea>';
		echo '</tr>';
		echo '</table>';
		echo '<input style="min-width: 50%" type="submit" class="button button-primary" value="' . esc_attr__( 'Créer l\'amapien', 'amapress' ) . '" />';
		echo '</form>';
	}
	echo '<hr />';
	echo '<p><a href="' . remove_query_arg( [
			'user_id',
		] ) . '" class="button button-primary">' . __( 'Choisir un autre amapien', 'amapress' ) . '</a></p>';
	echo '<script type="text/javascript">jQuery(function($) {
    $("#user_id").select2({
        allowClear: true,
		  escapeMarkup: function(markup) {
		return markup;
	},
		  templateResult: function(data) {
		return $("<span>"+data.text+"</span>");
	},
		  templateSelection: function(data) {
		return $("<span>"+data.text+"</span>");
	},
    });
    $("form#new_user").validate({
                onkeyup: false,
        }
    );
});
</script>
<style type="text/css">
	.error {
		font-weight: bold;
		color: red;
	}
</style>';
}
