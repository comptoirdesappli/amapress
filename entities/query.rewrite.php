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
//    $infos_slug = get_post(Amapress::getOption('mes-infos-page'))->post_name;

//    add_rewrite_rule('^contrats/([^/]+)/details/(\d+)/?', 'index.php?post_type=amps_contrat&name=$matches[1]&paged=$matches[2]&viewmode=details', 'top');
	add_rewrite_rule( '^contrats/([^/]+)/details/([^/]+)/sample/pdf/?', 'index.php?post_type=amps_contrat&name=$matches[1]&amp_action=sample_pdf&subview=$matches[2]', 'top' );
	add_rewrite_rule( '^contrats/([^/]+)/details/([^/]+)/pdf/?', 'index.php?post_type=amps_contrat&name=$matches[1]&amp_action=pdf&subview=$matches[2]', 'top' );
	add_rewrite_rule( '^contrats/([^/]+)/details/([^/]+)/?', 'index.php?post_type=amps_contrat&name=$matches[1]&viewmode=details&subview=$matches[2]', 'top' );
	add_rewrite_rule( '^contrats/([^/]+)/details/?', 'index.php?post_type=amps_contrat&name=$matches[1]&viewmode=details', 'top' );
	add_rewrite_rule( '^contrats/([^/]+)/(inscription|s-inscrire|s-abonner|souscrire)/?', 'index.php?post_type=amps_contrat&name=$matches[1]&amp_action=souscription', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/(inscription|s-inscrire|sinscrire|participer|devenir-responsable)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=inscr_resp', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/(desinscription|desinscrire)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=desinscr_resp', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/garder/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=panier_garder', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/([^/]+)/([^/]+)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amapress_contrat=$matches[2]&amapress_contrat_qt=$matches[3]&amp_action=liste-emargement', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/excel/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=liste-emargement-excel', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/pdf/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=liste-emargement-pdf', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/([^/]+)/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amapress_contrat=$matches[2]&amp_action=liste-emargement', 'top' );
	add_rewrite_rule( '^distributions/([^/]+)/liste-emargement/?', 'index.php?post_type=amps_distribution&name=$matches[1]&amp_action=liste-emargement', 'top' );
	//add_rewrite_rule('^distributions/([^/]+)/(inscription|s-inscrire|participer|devenir-responsable)/?', 'index.php?post_type=distribution&name=$matches[1]&amp_action=resp_distrib', 'top');
//    add_rewrite_rule('^paniers/(a-echanger|intermittents)/([^/]+)/?', 'index.php?pagename='.$infos_slug.'&viewmode=paniers_a_echanger&subview=$matches[2]', 'top');
//    add_rewrite_rule('^paniers/(a-echanger|intermittents)/?', 'index.php?pagename='.$infos_slug.'&viewmode=paniers_a_echanger', 'top');
//    add_rewrite_rule('^amapiens/([^/]+)/echanger/?', 'index.php?post_type=amps_panier&name=$matches[1]&amp_action=echanger', 'top');
//	add_rewrite_rule( '^paniers/([^/]+)/echanger/?', 'index.php?post_type=amps_panier&name=$matches[1]&amp_action=echanger', 'top' );
//	add_rewrite_rule( '^visites/([^/]+)/(inscription|s-inscrire|sinscrire|participer|y-aller)/?', 'index.php?post_type=amps_visite&name=$matches[1]&amp_action=participer', 'top' );
	add_rewrite_rule( '^assemblees/([^/]+)/(inscription|s-inscrire|sinscrire|participer|y-aller)/?', 'index.php?post_type=amps_assemblee&name=$matches[1]&amp_action=participer', 'top' );
	add_rewrite_rule( '^evenements/([^/]+)/(inscription|s-inscrire|sinscrire|participer|y-aller)/?', 'index.php?post_type=amps_amap_event&name=$matches[1]&amp_action=participer', 'top' );
	add_rewrite_rule( '^commandes/([^/]+)/(commander)/?', 'index.php?post_type=amps_commande&name=$matches[1]&amp_action=commander', 'top' );
//    add_rewrite_rule('^recettes/([^/]+)/(commander)/?', 'index.php?post_type=amps_commande&name=$matches[1]&amp_action=commander', 'top');

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

//    amapress_add_page_if_not_exists('agenda-page','Agenda','agenda');
//    $agenda_slug = get_post(Amapress::getOption('agenda-page'))->post_name;
//    add_rewrite_rule('^'.$agenda_slug.'/page/(\d+)/?', 'index.php?pagename='.$agenda_slug.'&gallery_page=$matches[1]', 'top');

//    amapress_add_page_if_not_exists('trombinoscope-page','Trombinoscope','trombinoscope', true);
//    $trombinoscope_slug = get_post(Amapress::getOption('trombinoscope-page'))->post_name;
//    add_rewrite_rule('^'.$trombinoscope_slug.'/page/(\d+)/?', 'index.php?pagename='.$trombinoscope_slug.'&gallery_page=$matches[1]', 'top');

//    add_rewrite_rule('^'.$infos_slug.'/adhesions/intermittence/inscription/?', 'index.php?pagename='.$infos_slug.'&viewmode=intermittence&amp_action=inscription', 'top');
//    add_rewrite_rule('^'.$infos_slug.'/adhesions/intermittence/([^/]+)/?', 'index.php?pagename='.$infos_slug.'&viewmode=intermittence&subview=$matches[1]', 'top');
//    add_rewrite_rule('^'.$infos_slug.'/adhesions/intermittence/?', 'index.php?pagename='.$infos_slug.'&viewmode=intermittence', 'top');
//    add_rewrite_rule('^'.$infos_slug.'/adhesions/([^/]+)/([^/]+)/?', 'index.php?p=$matches[2]', 'top');
//    add_rewrite_rule('^'.$infos_slug.'/(echange-paniers|echanges)/?', 'index.php?pagename='.$infos_slug.'&viewmode=echange_paniers', 'top');
//    add_rewrite_rule('^'.$infos_slug.'/([^/]+)/([^/]+)/?', 'index.php?pagename='.$infos_slug.'&viewmode=$matches[1]&subview=$matches[2]', 'top');
//    add_rewrite_rule('^'.$infos_slug.'/([^/]+)/?', 'index.php?pagename='.$infos_slug.'&viewmode=$matches[1]', 'top');

//    amapress_add_page_if_not_exists('recettes-page','Les recettes','les-recettes');
//    $recettes_slug = get_post(Amapress::getOption('recettes-page'))->post_name;
	//add_rewrite_rule('^'.$recettes_slug.'/(\d+)/?', 'index.php?name='.$recettes_slug.'&paged=$matches[1]', 'top');
//    add_rewrite_rule('^'.$recettes_slug.'/proposer-une-nouvelle-recette/?', 'index.php?pagename='.$recettes_slug.'&amp_action=proposer', 'top');
//    add_rewrite_rule('^'.$recettes_slug.'/categorie/([^/]+)/?', 'index.php?pagename='.$recettes_slug.'&amapress_recette_tag=$matches[1]', 'top');
//    add_rewrite_rule('^'.$recettes_slug.'/produit/([^/]+)/?', 'index.php?pagename='.$recettes_slug.'&amapress_recette_produits=$matches[1]', 'top');
	//add_rewrite_rule('^'.$recettes_slug.'/([^/]+)/(\d+)/?', 'index.php?name='.$recettes_slug.'&amps_recette_category=$matches[1]&paged=$matches[2]', 'top');

//    add_rewrite_tag('%gallery_page%', '([^&]+)');
//    add_rewrite_rule('([^/]+)/gallery/item//?([0-9]{1,})/?$', 'index.php?name=$matches[1]&gallery_page=$matches[2]', 'top');

}

function amapress_add_page_if_not_exists( $option_name, $title, $slug, $protected = false, $content = '' ) {
//    $posts = get_posts(array(
//        'post_type' => 'page',
//        'post_title' => $title,
//        'posts_per_page' => -1,
//    ));
//    foreach($posts as $p) {
//        wp_delete_post($p->ID);
//    }
	if ( ! amapress_is_user_logged_in() ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$option_value = intval( Amapress::getOption( $option_name ) );
	if ( empty( $option_value ) || false == get_post_status( $option_value ) ) {
//        var_dump($option_name);
//        var_dump(Amapress::getOption($option_name));
//        die();
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
