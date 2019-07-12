<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


//add_action('amapress_init', 'amapress_add_rewrite_rules');
function amapress_add_rewrite_rules() {
	add_rewrite_tag( '%amapress_date%', '([^&]+)' );
	add_rewrite_tag( '%viewmode%', '([^&]+)' );
	add_rewrite_tag( '%subview%', '([^&]+)' );
	add_rewrite_tag( '%action%', '([^&]+)' );
	add_rewrite_tag( '%amapress_producteur%', '([^&]+)' );
	add_rewrite_tag( '%amapress_lieu%', '([^&]+)' );
	add_rewrite_tag( '%amapress_contrat_inst%', '([^&]+)' );
	add_rewrite_tag( '%amapress_post%', '([^&]+)' );

	amapress_add_page_if_not_exists( 'mes-infos-page', 'Mes infos', 'mes-infos', false, '[amapien-edit-infos]' );

	add_rewrite_rule( '^contrats/([^/]+)/details/([^/]+)/sample/pdf/?', 'index.php?post_type=amps_contrat&name=$matches[1]&amp_action=sample_pdf&subview=$matches[2]', 'top' );
	add_rewrite_rule( '^contrats/([^/]+)/details/([^/]+)/pdf/?', 'index.php?post_type=amps_contrat&name=$matches[1]&amp_action=pdf&subview=$matches[2]', 'top' );
	add_rewrite_rule( '^contrats/([^/]+)/details/([^/]+)/?', 'index.php?post_type=amps_contrat&name=$matches[1]&viewmode=details&subview=$matches[2]', 'top' );
	add_rewrite_rule( '^contrats/([^/]+)/details/?', 'index.php?post_type=amps_contrat&name=$matches[1]&viewmode=details', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/(inscription|s-inscrire|sinscrire|participer|devenir-responsable)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=inscr_resp', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/(desinscription|desinscrire)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=desinscr_resp', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/garder/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=panier_garder', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/([^/]+)/([^/]+)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amapress_contrat=$matches[2]&amapress_contrat_qt=$matches[3]&amp_action=liste-emargement', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/excel/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=liste-emargement-excel', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/pdf/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=liste-emargement-pdf', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/([^/]+)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amapress_contrat=$matches[2]&amp_action=liste-emargement', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=liste-emargement', 'top' );
	add_rewrite_rule( '^assemblees/([^/]+)/(inscription|s-inscrire|sinscrire|participer|y-aller)/?', 'index.php?post_type=amps_assemblee&name=$matches[1]&amp_action=participer', 'top' );
	add_rewrite_rule( '^commandes/([^/]+)/(commander)/?', 'index.php?post_type=amps_commande&name=$matches[1]&amp_action=commander', 'top' );

	amapress_add_page_if_not_exists( 'paniers-intermittents-page',
		'Intermittent - Réserver un panier',
		'intermittent-reserver-un-panier',
		true, '[les-paniers-intermittents]' );
	amapress_add_page_if_not_exists(
		'mes-paniers-intermittents-page',
		'Mes paniers échangés',
		'mes-paniers-echanges',
		true, '<h2>Les paniers&nbsp;que j\'ai proposé</h2>
[amapien-paniers-intermittents]
<h2>Les paniers&nbsp;que j\'ai réservé</h2>
[intermittent-paniers]' );
}

function amapress_add_page_if_not_exists( $option_name, $title, $slug, $protected = false, $content = '' ) {
	if ( ! amapress_is_user_logged_in() ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$option_value = intval( Amapress::getOption( $option_name ) );
	if ( empty( $option_value ) || false == get_post_status( $option_value ) ) {
		$option_value = wp_insert_post( array(
			'post_title'     => $title,
			'post_type'      => 'page',
			'post_name'      => $slug,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_content'   => $content,
			'post_status'    => 'publish',
			'menu_order'     => 0
		) );
		if ( empty( $option_value ) ) {
			return;
		}
		Amapress::setOption( $option_name, $option_value );

		if ( $protected ) {
			update_post_meta( $option_value, 'amps_lo', 1 );
		} else {
			delete_post_meta( $option_value, 'amps_lo' );
		}
	}
}
