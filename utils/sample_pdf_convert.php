<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 21/06/2019
 * Time: 08:24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_test_convertpdf_ws() {
	$convertws_url  = Amapress::getOption( 'convertws_url' );
	$convertws_user = Amapress::getOption( 'convertws_user' );
	$convertws_pass = Amapress::getOption( 'convertws_pass' );

	if ( empty( $convertws_url ) ) {
		wp_die( __( 'L\'url du convertisseur n\'est pas définie.', 'amapress' ) );
	}
	if ( empty( $convertws_user ) ) {
		wp_die( __( 'Le nom d\'utilisateur du convertisseur n\'est pas défini.', 'amapress' ) );
	}
	if ( empty( $convertws_pass ) ) {
		wp_die( __( 'Le mot de passe du convertisseur n\'est pas défini.', 'amapress' ) );
	}

	$out_docx = trailingslashit( Amapress::getContratDir() ) . 'sample_pdf_convert.docx';
	$out_pdf  = trailingslashit( Amapress::getContratDir() ) . 'sample_pdf_convert.pdf';
	copy( AMAPRESS__PLUGIN_DIR . 'utils/sample_pdf_convert.docx', $out_docx );
	if ( file_exists( $out_pdf ) ) {
		@unlink( $out_pdf );
	}

	$pdf_filename = Amapress::convertToPDF( $out_docx, true );
	$file_name    = basename( $pdf_filename );
	Amapress::sendDocumentFile( $pdf_filename, $file_name );
	@unlink( $out_pdf );
	@unlink( $out_docx );
}