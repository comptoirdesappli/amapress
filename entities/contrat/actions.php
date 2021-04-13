<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_post_paiement_table_pdf', function () {
	if ( ! amapress_can_access_admin() ) {
		wp_die( __( 'Accès interdit', 'amapress' ) );
	}

	$contrat_instance_id = intval( $_GET['contrat'] );
	$contrat             = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat ) {
		return;
	}
	if ( $contrat->isArchived() ) {
		wp_die( __( 'Action impossible pour un contrat archivé', 'amapress' ) );
	}

	$lieu_id = isset( $_GET['lieu'] ) ? intval( $_GET['lieu'] ) : 0;
	$format  = isset( $_GET['format'] ) ? $_GET['format'] : 'A3';
	$html    = amapress_get_paiement_table_by_dates(
		$contrat_instance_id,
		$lieu_id,
		array(
			'show_next_distrib'       => false,
			'show_contact_producteur' => false,
			'for_pdf'                 => true,
		) );

	$lieu      = AmapressLieu_distribution::getBy( $lieu_id );
	$lieu_name = 'tous';
	if ( $lieu ) {
		$lieu_name = $lieu->getShortName();
	}
	$date     = date_i18n( 'd-m-Y' );
	$filename = strtolower( sanitize_file_name( "cheques-{$contrat->getModelTitle()}-{$lieu_name}-au-$date.pdf" ) );
	Amapress::sendPdfFromHtml( $html, $filename, 'L', $format );
} );

add_action( 'admin_post_paiement_table_xlsx', function () {
	if ( ! amapress_can_access_admin() ) {
		wp_die( __( 'Accès interdit', 'amapress' ) );
	}

	$contrat_instance_id = intval( $_GET['contrat'] );
	$contrat             = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat ) {
		return;
	}
	if ( $contrat->isArchived() ) {
		wp_die( __( 'Action impossible pour un contrat archivé', 'amapress' ) );
	}

	$lieu_id = isset( $_GET['lieu'] ) ? intval( $_GET['lieu'] ) : 0;
	$format  = isset( $_GET['format'] ) ? $_GET['format'] : 'A3';
	$html    = amapress_get_paiement_table_by_dates(
		$contrat_instance_id,
		$lieu_id,
		array(
			'show_next_distrib'       => false,
			'show_contact_producteur' => false,
			'for_pdf'                 => true,
		) );

	$lieu      = AmapressLieu_distribution::getBy( $lieu_id );
	$lieu_name = 'tous';
	if ( $lieu ) {
		$lieu_name = $lieu->getShortName();
	}
	$date = date_i18n( 'd-m-Y' );
	Amapress::sendXLSXFromHtml( $html, strtolower( sanitize_file_name( "reglements-{$contrat->getModelTitle()}-{$lieu_name}-au-$date.xlsx" ) ), sprintf( __( 'Règlements - %s - %s', 'amapress' ), $contrat->getModelTitle(), $lieu_name ) );
} );

add_action( 'admin_post_delivery_table_xlsx', function () {
	if ( ! amapress_can_access_admin() ) {
		wp_die( __( 'Accès interdit', 'amapress' ) );
	}

	$contrat_instance_id = intval( $_GET['contrat'] );
	$type                = isset( $_GET['type'] ) ? $_GET['type'] : 'date';
	$contrat_instance    = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat_instance ) {
		return;
	}

	if ( 'adherents_columns' == $type ) {
		$xl = amapress_get_contrat_column_quantite( $contrat_instance_id );
		Amapress::sendXLSXFromPHPExcelObject( $xl['xl'], $xl['filename'] );
	} else {
		$xlsx = amapress_get_contrat_quantite_xlsx( $contrat_instance_id, $type );
		$xl   = Amapress::createXLSXFromDatatable(
			$xlsx['columns'], $xlsx['data'], $xlsx['title']
		);
		Amapress::sendXLSXFromPHPExcelObject( $xl, $xlsx['filename'] );
	}
} );

add_action( 'admin_post_archives_inscriptions', function () {
	if ( ! amapress_can_access_admin() ) {
		wp_die( __( 'Accès interdit', 'amapress' ) );
	}

	$contrat_instance_id = intval( $_GET['contrat'] );
	$type                = isset( $_GET['type'] ) ? $_GET['type'] : 'inscriptions';
	$contrat_instance    = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat_instance ) {
		return;
	}

	$archives_infos = $contrat_instance->getArchiveInfo();
	if ( empty( $archives_infos ) ) {
		return;
	}

	if ( 'delete_all' == $type ) {
		$contrat_instance->cleanArchived();
		wp_die( __( 'Archives supprimées', 'amapress' ) );
	}

	if ( isset( $archives_infos["file_$type"] ) ) {
		Amapress::sendDocumentFile(
			Amapress::getArchivesDir() . '/' . $archives_infos["file_$type"],
			$archives_infos["file_$type"] );
	} else {
		wp_die( __( 'Fichier introuvable', 'amapress' ) );
	}
} );

add_action( 'admin_post_archives_cheques', function () {
	if ( ! amapress_can_access_admin() ) {
		wp_die( __( 'Accès interdit', 'amapress' ) );
	}

	$contrat_instance_id = intval( $_GET['contrat'] );
	$lieu_id             = isset( $_GET['lieu'] ) ? intval( $_GET['lieu'] ) : 0;
	$contrat_instance    = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat_instance ) {
		return;
	}

	$archives_infos = $contrat_instance->getArchiveInfo();
	if ( empty( $archives_infos ) ) {
		return;
	}

	Amapress::sendDocumentFile(
		Amapress::getArchivesDir() . '/' . $archives_infos["file_cheques_$lieu_id"],
		$archives_infos["file_cheques_$lieu_id"] );
} );