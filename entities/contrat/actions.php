<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//add_action( 'amapress_do_query_action_contrat_souscription', 'amapress_do_query_action_contrat_souscription' );
//function amapress_do_query_action_contrat_souscription() {
//	if ( ! amapress_is_user_logged_in() ) {
//		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
//	}
//
//	$redir_url = trailingslashit( get_permalink( get_the_ID() ) ) . 'details';
//
//	$contrat_instances = AmapressContrats::get_active_contrat_instances_by_contrat( get_the_ID() );
//	if ( count( $contrat_instances ) == 0 ) {
//		wp_redirect_and_exit( $redir_url );
//	}
//
//	$contrat_instance = $contrat_instances[0];
//
//	$contrat_instance_id = $_POST['contrat_instance_id'];
//	$quantite            = $_POST['quantite'];
//	$paiements           = $_POST[ 'paiement_' . $quantite ];
//	$lieu                = $_POST['lieu'];
//	$message             = $_POST['message'];
//
//	if ( empty( $contrat_instance_id ) || empty( $quantite ) || empty( $paiements ) || empty( $lieu ) ) {
//		wp_redirect_and_exit( add_query_arg( 'message', 'fill_fields', $redir_url ) );
//	}
//
//	$user_contrat_ids = AmapressContrat_instance::getContratInstanceIdsForUser();
//	if ( in_array( intval( $contrat_instance_id ), $user_contrat_ids ) ) {
//		wp_redirect_and_exit( add_query_arg( 'message', 'already_inscr', $redir_url ) );
//	}
//	$contrat_ids = AmapressContrats::get_active_contrat_instances_ids();
//	if ( ! in_array( intval( $contrat_instance_id ), $contrat_ids ) ) {
//		wp_redirect_and_exit( $redir_url );
//	}
//
//	$optionsPage = Amapress::resolve_post_id( Amapress::getOption( 'mes-infos-page' ), 'page' );
//	$base_url    = trailingslashit( get_page_link( $optionsPage ) );
//
//	$my_post = array(
////        'post_title' => sprintf('Adhésion à %s',
////            $contrat_instance->post_title),
//		'post_type'    => 'amps_adhesion',
//		'post_content' => '',
//		'post_status'  => 'publish',
//		'meta_input'   => array(
//			'amapress_adhesion_date_debut'       => amapress_time(),
//			'amapress_adhesion_contrat_instance' => $contrat_instance_id,
//			'amapress_adhesion_contrat_quantite' => $quantite,
//			'amapress_adhesion_paiements'        => $paiements,
//			'amapress_adhesion_adherent'         => amapress_current_user_id(),
//			'amapress_adhesion_lieu'             => $lieu,
//			'amapress_adhesion_message'          => $message,
//			'amapress_adhesion_status'           => 'to_confirm',
//		),
//	);
//	$post_id = wp_insert_post( $my_post );
//
//	$adh = AmapressAdhesion::getBy( $post_id );
//	amapress_mail_to_current_user( Amapress::getOption( 'adhesion-contrat-mail-subject' ), Amapress::getOption( 'adhesion-contrat-mail-content' ), null, $adh );
//
//	wp_redirect_and_exit( add_query_arg( 'message', 'adhesion_success', $base_url . 'adhesions' ) );
//}

add_action( 'amapress_do_query_action_contrat_pdf', 'amapress_do_query_action_contrat_pdf', 10, 2 );
function amapress_do_query_action_contrat_pdf() {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	global $post;
	$subview = get_query_var( 'subview' );
	if ( ! empty( $subview ) ) {
		$contrat_instance = AmapressContrat_instance::getBy( Amapress::resolve_post_id( $subview, AmapressContrat_instance::INTERNAL_POST_TYPE ) );
	} else {
		$contrat_instances = AmapressContrats::get_active_contrat_instances_by_contrat( get_the_ID() );
		if ( count( $contrat_instances ) == 0 ) {
			return '';
		}
		$contrat_instance = $contrat_instances[0];
	}
	$new_adhesion = false;
	if ( amapress_is_user_logged_in() ) {
		$adhesions = $contrat_instance->getAdhesionsForUser();
		if ( count( $adhesions ) > 0 ) {
			$post = $adhesions[0]->getPost();
			setup_postdata( $post );
		} else {
			$new_adhesion = true;
		}
	}
	if ( $new_adhesion ) {
		wp_die( 'Accès non autorisé' );
	}

	$contrat = do_shortcode( amapress_get_post_field_as_html( $contrat_instance->ID, 'contrat_instance', 'contrat' ) );
	wp_reset_postdata();

//    echo $contrat;
//    die();

	try {
		require_once( AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php' );
		$html2pdf = new HTML2PDF( 'P', 'A4', 'en' );
		$html2pdf->writeHTML( $contrat );
		ob_clean();
		$html2pdf->Output( 'contrat.pdf' );
	} catch ( Exception $e ) {
		echo $e;
		exit;
	}
}

add_action( 'admin_post_paiement_table_pdf', function () {
	$contrat_instance_id = intval( $_GET['contrat'] );
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

	$contrat   = AmapressContrat_instance::getBy( $contrat_instance_id );
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

	$contrat   = AmapressContrat_instance::getBy( $contrat_instance_id );
	$lieu      = AmapressLieu_distribution::getBy( $lieu_id );
	$lieu_name = 'tous';
	if ( $lieu ) {
		$lieu_name = $lieu->getShortName();
	}
	$date    = date_i18n( 'd-m-Y' );
	Amapress::sendXLSXFromHtml( $html, strtolower( sanitize_file_name( "cheques-{$contrat->getModelTitle()}-{$lieu_name}-au-$date.xlsx" ) ), "Chèques - {$contrat->getModelTitle()} - {$lieu_name}" );
} );