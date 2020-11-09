<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion_period' );
function amapress_register_entities_adhesion_period( $entities ) {
	if ( ! defined( 'AMAPRESS_ADHESION_PERIOD_CHECK1' ) ) {
		define( 'AMAPRESS_ADHESION_PERIOD_CHECK1', __( 'Checkbox 1', 'amapress' ) );
	}
	if ( ! defined( 'AMAPRESS_ADHESION_PERIOD_CHECK2' ) ) {
		define( 'AMAPRESS_ADHESION_PERIOD_CHECK2', __( 'Checkbox 2', 'amapress' ) );
	}
	if ( ! defined( 'AMAPRESS_ADHESION_PERIOD_CHECK3' ) ) {
		define( 'AMAPRESS_ADHESION_PERIOD_CHECK3', __( 'Checkbox 3', 'amapress' ) );
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
			'add_new'      => __( 'Ajouter', 'amapress' ),
			'add_new_item' => __( 'Ajouter une période d\'adhésion', 'amapress' ),
		),
		'edit_header'      => function ( $post ) {
			$period = AmapressAdhesionPeriod::getBy( $post->ID );
			$result = $period->getModelDocStatus();
			if ( true !== $result ) {
				echo amapress_get_admin_notice( $result['message'], $result['status'], false );
			}

			if ( $period->isArchived() ) {
				echo '<h4>' . __( 'TELECHARGER ARCHIVES', 'amapress' ) . '</h4>';
				echo '<p>';
				echo '<a href="' . admin_url( 'admin-post.php?action=archives_adhesions&period=' . $post->ID ) . '">' . __( 'Adhésions (XLSX)', 'amapress' ) . '</a>, ';
				echo '<a href="' . admin_url( 'admin-post.php?action=archives_adhesions&type=paiements&period=' . $post->ID ) . '">' . __( 'Adhésions (Répartition) (XLSX)', 'amapress' ) . '</a>,';
				echo '</p>';
			}
		},
		'row_actions'      => array(
			'renew' => [
				'label'   => __( 'Renouveler', 'amapress' ),
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
				'group'        => __( '1/ Informations', 'amapress' ),
				'desc'         => __( 'Date à laquelle démarre la période', 'amapress' ),
				'csv_required' => true,
			),
			'date_fin'    => array(
				'name'         => __( 'Date de fin', 'amapress' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => __( '1/ Informations', 'amapress' ),
				'desc'         => __( 'Date à laquelle finit la période', 'amapress' ),
				'csv_required' => true,
			),
			'name'        => array(
				'name'  => __( 'Nom de la période', 'amapress' ),
				'type'  => 'text',
				'group' => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'  => __( '(Facultatif) Nom de la saison (par exemple, saison 15)', 'amapress' ),
			),
			'online_desc' => array(
				'name'  => __( 'Contenu bulletin', 'amapress' ),
				'type'  => 'editor',
				'group' => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'  => __( 'Contenu à afficher lors de l\'adhésion en ligne', 'amapress' ),
			),
			'word_model'  => array(
				'name'            => __( 'Bulletin personnalisé', 'amapress' ),
				'media-type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'type'            => 'upload',
				'show_column'     => false,
				'show_download'   => true,
				'show_title'      => true,
				'selector-title'  => __( 'Sélectionnez/téléversez un modèle de bulletin DOCX', 'amapress' ),
				'selector-button' => __( 'Utiliser ce modèle', 'amapress' ),
				'group'           => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'            => sprintf( __( '<p>Configurer un modèle de bulletin à imprimer  pour chaque adhérent (Pour les utilisateurs avancés : à configurer avec des marquages substitutifs de type "${xxx}" <a target="_blank" href="%s">Plus d\'info</a>)</p>
<p>Vous pouvez télécharger <a target="_blank" href="%s">ici</a> un modèle DOCX générique utilisable comme bulletin d\'adhésion. Vous aurez à personnaliser le logo de votre AMAP et les élements de l\'adhésion (don, panier solidaire, règlement, explications...).</p>', 'amapress' ), admin_url( 'admin.php?page=amapress_help_page&tab=adhesion_placeholders' ), esc_attr( Amapress::getBulletinGenericUrl() ) ),
			),
			'mnt_reseau'  => array(
				'name'     => __( 'Montant adhésion au réseau', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'     => __( 'Montant adhésion au réseau (indiquer -1 pour autoriser un montant libre)', 'amapress' ),
			),
			'mnt_amap'         => array(
				'name'     => __( 'Montant adhésion AMAP', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'     => __( 'Montant adhésion AMAP (indiquer -1 pour autoriser un montant libre)', 'amapress' ),
			),
			'mnt_reseau_inter' => array(
				'name'     => __( 'Intermittents - Montant adhésion au réseau', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'     => __( 'Montant adhésion au réseau pour les intermittents (indiquer -1 pour autoriser un montant libre)', 'amapress' ),
			),
			'mnt_amap_inter' => array(
				'name'     => __( 'Intermittents - Montant adhésion AMAP', 'amapress' ),
				'type'     => 'number',
				'required' => true,
				'min'      => - 1,
				'group'    => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'     => __( 'Montant adhésion AMAP pour les intermittents (indiquer -1 pour autoriser un montant libre)', 'amapress' ),
			),
			'allow_chq'      => array(
				'name'        => __( 'Chèque', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( 'Règlements', 'amapress' ),
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en chèque', 'amapress' ),
			),
			'allow_cash'     => array(
				'name'        => __( 'Espèces', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( 'Règlements', 'amapress' ),
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en espèce', 'amapress' ),
			),
			'allow_bktrfr'   => array(
				'name'        => __( 'Virement', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( 'Règlements', 'amapress' ),
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement par virement', 'amapress' ),
			),
			'allow_locmon'   => array(
				'name'        => __( 'Monnaie locale', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( 'Règlements', 'amapress' ),
				'required'    => true,
				'default'     => false,
				'show_column' => false,
				'desc'        => __( 'Active une option dans l’assistant de pré-inscription en ligne pour permettre à l’amapien de signaler un règlement en monnaie locale', 'amapress' ),
			),
			'pmt_info'       => array(
				'name'  => __( 'Info règlements', 'amapress' ),
				'type'  => 'editor',
				'group' => __( 'Pré-inscription en ligne', 'amapress' ),
				'desc'  => __( 'Informations relatives aux règlements (ordres des chèques, virement) à afficher lors de l\'adhésion en ligne', 'amapress' ),
			),
			'pmt_user_input' => array(
				'name'        => __( 'Libellé règlements', 'amapress' ),
				'type'        => 'checkbox',
				'group'       => __( 'Règlements', 'amapress' ),
				'required'    => true,
				'default'     => true,
				'show_column' => false,
				'desc'        => __( 'Permettre aux amapiens de renseigner les numéros des chèques dans l’assistant d\'adhésion en ligne', 'amapress' ),
			),
			'custom_check1'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK1,
				'type'           => 'editor',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( 'Questions personnalisées', 'amapress' ),
				'desc'           => __( 'Intitulé de la checkbox personnalisée ', 'amapress' ) . AMAPRESS_ADHESION_PERIOD_CHECK1,
			),
			'custom_check2'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK2,
				'type'           => 'editor',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( 'Questions personnalisées', 'amapress' ),
				'desc'           => sprintf( __( 'Intitulé de la checkbox personnalisée %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK2 ),
			),
			'custom_check3'  => array(
				'name'           => AMAPRESS_ADHESION_PERIOD_CHECK3,
				'type'           => 'editor',
				'show_column'    => true,
				'col_def_hidden' => true,
				'group'          => __( 'Questions personnalisées', 'amapress' ),
				'desc'           => sprintf( __( 'Intitulé de la checkbox personnalisée %s', 'amapress' ), AMAPRESS_ADHESION_PERIOD_CHECK3 ),
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
		wp_die( __( 'Une erreur s\'est produit lors du renouvellement de la période d\'adhésion. Veuillez réessayer', 'amapress' ) );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_period->ID}&action=edit" ) );
}

function amapress_adhesion_period_archivables_view() {
	$columns = array(
		array(
			'title' => __( 'Période', 'amapress' ),
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
			'archive' => Amapress::makeLink( $archive_link, __( 'Archiver', 'amapress' ) ),
		);
	}

	return amapress_get_datatable( 'adh_period-archivables-table', $columns, $data );
}

add_action( 'admin_post_archive_adh_period', function () {
	$period_id  = isset( $_REQUEST['period_id'] ) ? intval( $_REQUEST['period_id'] ) : 0;
	$adh_period = AmapressAdhesionPeriod::getBy( $period_id );
	if ( empty( $adh_period ) ) {
		wp_die( __( 'Période d\'adhésion', 'amapress' ) );
	}

	if ( ! current_user_can( 'edit_adhesion_period', $period_id ) ) {
		wp_die( __( 'Vous n\'avez pas le droit d\'archiver cette période d\'adhésion', 'amapress' ) );
	}

	if ( $adh_period->isArchived() ) {
		wp_die( __( 'Période d\'adhésion déjà archivée', 'amapress' ) );
	}

	if ( ! $adh_period->canBeArchived() ) {
		wp_die( __( 'Période d\'adhésion non archivable', 'amapress' ) );
	}

	if ( ! isset( $_REQUEST['confirm'] ) ) {
		echo sprintf( __( '<p>Etes-vous sûr de vouloir archiver %s ? 
<br />
<a href="%s">Confirmer l\'archivage</a>', 'amapress' ), esc_html( $adh_period->getTitle() ), add_query_arg( 'confirm', 'yes' ) );
		die();
	}

	if ( 'yes' != $_REQUEST['confirm'] ) {
		wp_die( sprintf( __( 'Archivage de %s abandonné.', 'amapress' ), esc_html( $adh_period->getTitle() ) ) );
	}

	$adh_period->archive();

	echo '<p style="color: green">' . __( 'Archivage effectué', 'amapress' ) . '</p>';

	echo '<p><a href="' . esc_attr( admin_url( 'admin.php?page=adh_period_archives' ) ) . '">' . __( 'Retour à la liste des périodes d\'adhésion archivables', 'amapress' ) . '</a></p>';
	die();
} );