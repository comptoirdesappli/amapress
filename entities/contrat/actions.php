<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_post_paiement_table_pdf', function () {
	$contrat_instance_id = intval( $_GET['contrat'] );
	$contrat             = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat ) {
		return;
	}
	if ( $contrat->isArchived() ) {
		wp_die( "Action impossible pour un contrat archivé" );
	}

	$lieu_id             = isset( $_GET['lieu'] ) ? intval( $_GET['lieu'] ) : 0;
	$format              = isset( $_GET['format'] ) ? $_GET['format'] : 'A3';
	$html                = amapress_get_paiement_table_by_dates(
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
	$contrat_instance_id = intval( $_GET['contrat'] );
	$contrat             = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat ) {
		return;
	}
	if ( $contrat->isArchived() ) {
		wp_die( "Action impossible pour un contrat archivé" );
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
	Amapress::sendXLSXFromHtml( $html, strtolower( sanitize_file_name( "cheques-{$contrat->getModelTitle()}-{$lieu_name}-au-$date.xlsx" ) ), "Chèques - {$contrat->getModelTitle()} - {$lieu_name}" );
} );

add_action( 'admin_post_archives_inscriptions', function () {
	$contrat_instance_id = intval( $_GET['contrat'] );
	$contrat_instance    = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( ! $contrat_instance ) {
		return;
	}

	$archives_infos = $contrat_instance->getArchiveInfo();
	if ( empty( $archives_infos ) ) {
		return;
	}

	Amapress::sendDocumentFile( Amapress::getArchivesDir() . '/' . $archives_infos['file_inscriptions'], $archives_infos['file_inscriptions'] );
} );

add_action( 'admin_post_archives_cheques', function () {
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

	Amapress::sendDocumentFile( Amapress::getArchivesDir() . '/' . $archives_infos["file_cheques_$lieu_id"], $archives_infos["file_cheques_$lieu_id"] );
} );