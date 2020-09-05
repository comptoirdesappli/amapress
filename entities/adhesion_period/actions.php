<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_post_archives_adhesions', function () {
	if ( ! amapress_can_access_admin() ) {
		wp_die( 'Accès interdit' );
	}

	$period_id  = intval( $_GET['period'] );
	$type       = isset( $_GET['type'] ) ? $_GET['type'] : 'adhesions';
	$adh_period = AmapressAdhesionPeriod::getBy( $period_id );
	if ( ! $adh_period ) {
		return;
	}

	$archives_infos = $adh_period->getArchiveInfo();
	if ( empty( $archives_infos ) ) {
		return;
	}

	if ( isset( $archives_infos["file_$type"] ) ) {
		Amapress::sendDocumentFile(
			Amapress::getArchivesDir() . '/' . $archives_infos["file_$type"],
			$archives_infos["file_$type"] );
	} else {
		wp_die( 'Fichier introuvable' );
	}
} );
