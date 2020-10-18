<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_period' );
function amapress_register_entities_adhesion_period( $entities ) {
	if ( ! defined( 'AMAPRESS_ADHESION_PERIOD_CHECK1' ) ) {
		define( 'AMAPRESS_ADHESION_PERIOD_CHECK1', 'Checkbox 1' );
	}
	if ( ! defined( 'AMAPRESS_ADHESION_PERIOD_CHECK2' ) ) {
		define( 'AMAPRESS_ADHESION_PERIOD_CHECK2', 'Checkbox 2' );
	}
	if ( ! defined( 'AMAPRESS_ADHESION_PERIOD_CHECK3' ) ) {
		define( 'AMAPRESS_ADHESION_PERIOD_CHECK3', 'Checkbox 3' );
	}

	$entities['adhesion_period'] = array(
		'internal_name'    => 'amps_adh_per',
		'singular'         => __( 'Période Adhésion', 'amapress' ),
		'plural'           => __( 'Périodes Adhésion', 'amapress' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => false,
		'editor'           => false,
		'slug'             => 'adhesion_periods',
		'title_format'     => 'amapress_adhesion_period_title_formatter',
		'slug_format'      => 'from_title',
		'menu_icon'        => 'flaticon-signature',
		'labels'           => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Ajouter une période d\'adhésion',
		),
		'edit_header'      => function ( $post ) {
			$period = AmapressAdhesionPeriod::getBy( $post->ID );
			$result = $period->getModelDocStatus();
			if ( true !== $result ) {
				echo amapress_get_admin_notice( $result['message'], $result['status'], false );
			}

			if ( $period->isArchived() ) {
				echo '<h4>TELECHARGER ARCHIVES</h4>';
				echo '<p>';
				echo '<a href="' . admin_url( 'admin-post.php?action=archives_adhesions&period=' . $post->ID ) . '">Adhésions (XLSX)</a>, ';
				echo '<a href="' . admin_url( 'admin-post.php?action=archives_adhesions&type=paiements&period=' . $post->ID ) . '">Adhésions (Répartition) (XLSX)</a>,';
				echo '</p>';
			}
		},
		'row_actions'      => array(
			'renew' => [
				'label'   => 'Renouveler',
				'confirm' => true,
			],
		),
		'views'            => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_adhesion_period_views',
//            'exp_csv' => true,
		),
		'fields'           => array(
			'date_debut'  => array(
				'name'         => __( 'Date de début', 'amapress' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => '1/ Informations',
				'desc'         => 'Date à laquelle démarre la période',
				'csv_required' => true,
			),
			'date_fin'    => array(
				'name'         => __( 'Date de fin', 'amapress' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => '1/ Informations',
				'desc'         => 'Date à laquelle finit la période',
				'csv_required' => true,
			),
			'name'        => array(
				'name'  => __( 'Nom de la période', 'amapress' ),
				'type'  => 'text',
				'group' => 'Pré-inscription en ligne',
				'desc'  => '(Facultatif) Nom de la saison (par exemple, saison 15)',
			),
			'online_desc' => array(
				'name'  => __( 'Contenu bulletin', 'amapress' ),
				'type'  => 'editor',
				'group' => 'Pré-inscription en ligne',
				'desc'  => 'Contenu à afficher lors de l\'adhésion en ligne',
			),
			'word_model'  => array(
				'name'            => __( 'Bulletin personnalisé', 'amapress' ),
				'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'type'            => 'upload',
				'show_column'     => false,
				'show_download'   => true,
				'show_title'      => true,
				'selector-title'  => 'Sélectionnez/téléversez un modèle de bulletin DOCX',
				'selector-button' => 'Utiliser ce modèle',
				'group'           => 'Pré-inscription en ligne',
				'desc'            => '<p>Configurer un modèle de bulletin à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_placeholders' ) . '">Plus d\'info</a>)</p>
<p>Vous pouvez télécharger <a target="_blank" href="' . esc_attr( Amapress::getBulletinGenericUrl() ) . '">ici</a> un modèle DOCX générique utilisable comme bulletin d\'adhésion. Vous aurez à personnaliser le logo de votre AMAP et les élements de l\'adhésion (don, panier solidaire, règlement, explications...).</p>',
			),
			'mnt_reseau'  => array(
				'name'     => __( 'Montant adhésion au réseau', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion au réseau (indiquer -1 pour autoriser un montant libre)',
			),
			'mnt_amap'         => array(
				'name'     => __( 'Montant adhésion AMAP', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion AMAP (indiquer -1 pour autoriser un montant libre)',
			),
			'mnt_reseau_inter' => array(
				'name'     => __( 'Intermittents - Montant adhésion au réseau', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion au réseau pour les intermittents (indiquer -1 pour autoriser un montant libre)',
			),
			'mnt_amap_inter' => array(
				'name'     => __( 'Intermittents - Montant adhésion AMAP', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => 'Pré-inscription en ligne',
				'desc'     => 'Montant adhésion AMAP pour les intermittents (indiquer -1 pour autoriser un montant libre)',
			),
			'allow_chq'      => array(
				'name'        => __( 'Chèque', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en chèque',
			),
			'allow_cash'     => array(
				'name'        => __( 'Espèces', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en espèce',
			),
			'allow_bktrfr'   => array(
				'name'        => __( 'Virement', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement par virement',
			),
			'allow_locmon'   => array(
				'name'        => __( 'Monnaie locale', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en monnaie locale',
			),
			'pmt_info'       => array(
				'name'  => __( 'Info règlements', 'amapress' ),
				'type'  => 'editor',
				'group' => 'Pré-inscription en ligne',
				'desc'  => 'Informations relatives aux règlements (ordres des chèques, virement) à afficher lors de l\'adhésion en ligne',
			),
			'pmt_user_input' => array(
				'name'        => __( 'Libellé règlements', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => 'Règlements',
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => 'Permettre aux amapiens de renseigner les numéros des chèques dans l’assistant d\'adhésion en ligne',
			),
			'custom_check1'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK1,
				'type'           => 'editor',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => 'Questions personnalisées',
				'desc'           => 'Intitulé de la checkbox personnalisée ' . AMAPRESS_ADHESION_PERIOD_CHECK1,
			),
			'custom_check2'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK2,
				'type'           => 'editor',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => 'Questions personnalisées',
				'desc'           => 'Intitulé de la checkbox personnalisée ' . AMAPRESS_ADHESION_PERIOD_CHECK2,
			),
			'custom_check3'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK3,
				'type'           => 'editor',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => 'Questions personnalisées',
				'desc'           => 'Intitulé de la checkbox personnalisée ' . AMAPRESS_ADHESION_PERIOD_CHECK3,
			),
		),
	);

	return $entities;
}

add_action( 'amapress_row_action_adhesion_period_renew', 'amapress_row_action_adhesion_period_renew' );
function amapress_row_action_adhesion_period_renew( $post_id ) {
	$period     = AmapressAdhesionPeriod::getBy( $post_id );
	$new_period = $period->clonePeriod();
	if ( ! $new_period ) {
		wp_die( 'Une erreur s\'est produit lors du renouvellement de la période d\'adhésion. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_period->ID}&action=edit" ) );
}

function amapress_adhesion_period_archivables_view() {
	$columns = array(
		array(
			'title' => 'Période',
			'data'  => array(
				'_'    => 'period',
				'sort' => 'period',
			)
		),
		array(
			'title' => '',
			'data'  => 'archive'
		),
	);

	$data = array();
	foreach ( AmapressAdhesionPeriod::getAll() as $period ) {
		if ( ! $period->canBeArchived() ) {
			continue;
		}

		$archive_link = add_query_arg(
			array(
				'action'    => 'archive_adh_period',
				'period_id' => $period->ID,
			),
			admin_url( 'admin-post.php' )
		);
		$data[]       = array(
			'period'  => Amapress::makeLink( $period->getAdminEditLink(), $period->getTitle(), true, true ),
			'archive' => Amapress::makeLink( $archive_link, 'Archiver' ),
		);
	}

	return amapress_get_datatable( 'adh_period-archivables-table', $columns, $data );
}

add_action( 'admin_post_archive_adh_period', function () {
	$period_id  = isset( $_REQUEST['period_id'] ) ? intval( $_REQUEST['period_id'] ) : 0;
	$adh_period = AmapressAdhesionPeriod::getBy( $period_id );
	if ( empty( $adh_period ) ) {
		wp_die( 'Période d\'adhésion' );
	}

	if ( ! current_user_can( 'edit_adhesion_period', $period_id ) ) {
		wp_die( 'Vous n\'avez pas le droit d\'archiver cette période d\'adhésion' );
	}

	if ( $adh_period->isArchived() ) {
		wp_die( 'Période d\'adhésion déjà archivée' );
	}

	if ( ! $adh_period->canBeArchived() ) {
		wp_die( 'Période d\'adhésion non archivable' );
	}

	if ( ! isset( $_REQUEST['confirm'] ) ) {
		echo '<p>Etes-vous sûr de vouloir archiver ' . esc_html( $adh_period->getTitle() ) . ' ? 
<br />
<a href = "' . add_query_arg( 'confirm', 'yes' ) . '"> Confirmer l\'archivage</a>';
		die();
	}

	if ( 'yes' != $_REQUEST['confirm'] ) {
		wp_die( 'Archivage de ' . esc_html( $adh_period->getTitle() ) . ' abandonné.' );
	}

	$adh_period->archive();

	echo '<p style="color: green">Archivage effectué</p>';

	echo '<p><a href="' . esc_attr( admin_url( 'admin.php?page=adh_period_archives' ) ) . '">Retour à la liste des périodes d\'adhésion archivables</a></p>';
	die();
} );