<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_get_custom_content_producteur', 'amapress_get_custom_content_producteur' );
function amapress_get_custom_content_producteur( $content ) {
	if ( is_search() ) {
		return amapress_get_custom_archive_content_producteur( $content );
	}

	$producteur = AmapressProducteur::getBy( get_the_ID() );
	ob_start();

	echo $content;

//	amapress_echo_panel_start( 'Présentation', null, 'amap-panel-prod amap-panel-prod-' . $producteur->ID . ' amap-panel-prod-resume' );
//	echo wpautop( $producteur->getResume() );
//	amapress_echo_panel_end();

//	amapress_echo_panel_start( 'En résumé', null, 'amap-panel-prod amap-panel-prod-' . $producteur->ID . ' amap-panel-prod-presentation' );
//	echo wpautop( $producteur->getPresentation() );
//	amapress_echo_panel_end();

//    amapress_echo_panel_start('Coordonnées', null, 'amap-panel-prod amap-panel-prod-'.$producteur->ID.' amap-panel-prod-address');
//    //AmapressUsers::echoUserById(get_post_meta($producteur_id, 'amapress_producteur_user', true), array('adresse'));
//    echo $producteur->getUser()->getDisplay(array(
//        'show_avatar' => 'true',
//        'show_email' => 'true',
//        'show_tel' => 'true',
//        'show_tel_fixe' => 'true',
//        'show_tel_mobile' => 'true',
//        'show_adresse' => 'true',
//        'show_roles' => 'false',
//    ));
//    amapress_echo_panel_end();

	amapress_echo_panel_start( 'Adresse de la ferme', null, 'amap-panel-prod amap-panel-prod-' . $producteur->ID . ' amap-panel-prod-address' );
	echo '<p class="visite-nom-exploitation">' .
	     '<a href="' . $producteur->getPermalink() . '">' . $producteur->getNomExploitation() . '</a>' .
	     '</p>';
	echo '<p class="visite-adresse-exploitation">' .
	     $producteur->getFormattedAdresseExploitationHtml() .
	     '</p>';
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Accès', null, 'amap-panel-prod amap-panel-prod-' . $producteur->ID . ' amap-panel-prod-access' );
	echo '<p>' .
	     $producteur->getAcces() .
	     '</p>' .
	     do_shortcode( "[producteur-map producteur={$producteur->ID} mode=map+streeview]" );
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Coordonnées du producteur', null, 'amap-panel-prod amap-panel-prod-' . $producteur->ID . ' amap-panel-prod-coords' );
	//AmapressUsers::echoUserById(get_post_meta($producteur_id, 'amapress_producteur_user', true), array('adresse'));
	echo $producteur->getUser()->getDisplay( array(
		'show_avatar'     => 'true',
		'show_email'      => 'true',
		'show_tel'        => 'true',
		'show_tel_fixe'   => 'true',
		'show_tel_mobile' => 'true',
		'show_adresse'    => 'true',
		'show_roles'      => 'false',
	) );
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Référent', null, 'amap-panel-prod amap-panel-prod-' . $producteur->ID . ' amap-panel-prod-referent' );
	$used_ids = array();
	foreach ( $producteur->getAllReferentsIds() as $ref_id ) {
		$ref = AmapressUser::getBy( $ref_id );
		if ( $ref_id && $ref && ! in_array( $ref->ID, $used_ids ) ) {
			echo $ref->getDisplay( array(
				'show_roles' => 'true',
			) );
			$used_ids[] = $ref->ID;
		}
	}
//    AmapressUsers::echoUserById(get_post_meta($producteur_id, 'amapress_producteur_referent', true), is_user_logged_in() ? 'full' : 'thumb');
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Contrats', null, 'amap-panel-prod amap-panel-prod-' . $producteur->ID . ' amap-panel-prod-contrats' );
	echo Amapress::get_contrats_list( $producteur->ID );
	amapress_echo_panel_end();

	echo '<h3>Produits</h3>';
	echo do_shortcode( "[produits producteur={$producteur->ID}]" );

	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}

add_filter( 'amapress_get_custom_archive_content_producteur', 'amapress_get_custom_archive_content_producteur' );
function amapress_get_custom_archive_content_producteur( $content ) {
	$producteur = AmapressProducteur::getBy( get_the_ID() );
	ob_start();
//	echo wpautop( $producteur->getResume() );
//	echo wpautop( $producteur->getPresentation() );
	echo $content;

	echo '<h3>Contrats</h3>';
	echo Amapress::get_contrats_list( $producteur->ID );

	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}