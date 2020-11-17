<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_get_custom_title_contrat', 'amapress_get_custom_title_contrat' );
function amapress_get_custom_title_contrat( $content ) {
	$amapress_icon_id = get_post_meta( get_the_ID(), 'amapress_icon_id' );
	if ( $amapress_icon_id ) {
		$url = amapress_get_avatar_url( get_the_ID(), null, 'produit-thumb', 'default_contrat.jpg' );

		return '<span class="contrat-icon"><img src="' . $url . '" alt="" width="32" height="32" /></span>' . $content;
	}

	return $content;
}

add_filter( 'amapress_get_custom_content_contrat_default', 'amapress_get_custom_content_contrat_default' );
function amapress_get_custom_content_contrat_default( $content ) {
	$contrat_id          = get_the_ID();
	$contrat             = AmapressContrat::getBy( $contrat_id );
	$prod                = $contrat->getProducteur();
	$prod_id             = $prod->ID;
	$prouits_html        = amapress_produits_shortcode(
		[ 'producteur' => $prod_id, 'columns' => 4 ]
	);
	$prod_user           = $prod->getUserId();
	$active_contrats_ids = [];
	if ( amapress_is_user_logged_in() ) {
		$active_contrats_ids = array_map( function ( $a ) {
			/** @var AmapressAdhesion $a */
			return $a->getModelId();
		}, AmapressAdhesion::getUserActiveAdhesions() );
	}

	$content = amapress_get_panel_start( Amapress::getOption( 'pres_producteur_title', __( 'Présentation de la production', 'amapress' ) ), null, 'amap-panel-pres-prod amap-panel-pres-prod-' . $prod_id );
	$content .= '<div class="contrat-prod-user">' . do_shortcode( '[amapien-avatar user=' . $prod_user . ']' ) . '</div>';
	$content .= '<div class="contrat-pres-prod">' . wpautop( get_the_content() ) . '</div>';
	if ( amapress_can_access_admin() ) {
		if ( $edit_contrat_url = get_edit_post_link( get_the_ID() ) ) {
			$content .= '<div><a href="' . esc_url( $edit_contrat_url ) . '" class="post-edit-link">' . __( 'Modifier cette présentation', 'amapress' ) . '</a></div>';
		}
	}
	$content .= Amapress::get_know_more( get_permalink( $prod_id ) );
	$content .= amapress_get_panel_end();
	$content .= amapress_get_panel_start( Amapress::getOption( 'pres_produits_title', __( 'Ses produits', 'amapress' ) ), null, 'amap-panel-produits amap-panel-produits-' . $prod_id );
	$content .= '<div class="contrat-produits">';
	$content .= $prouits_html;
	$content .= '</div>';
	$content .= amapress_get_panel_end();

	foreach ( AmapressContrats::get_active_contrat_instances_by_contrat( $contrat_id ) as $c ) {
		$content .= amapress_get_panel_start( sprintf( __( 'Contrat - %s', 'amapress' ), esc_html( $c->getTitle() ) ) );
		$content .= wpautop( $c->getContratInfo() );

		if ( $c->getDate_ouverture() < amapress_time() && amapress_time() < $c->getDate_cloture() ) {
			$mes_contrats_href = Amapress::get_mes_contrats_page_href();
			if ( amapress_is_user_logged_in() ) {
				$inscription_contrats_link = Amapress::get_logged_inscription_page_href();
				if ( empty( $inscription_contrats_link ) ) {
					$inscription_contrats_link = $mes_contrats_href;
				}
			} else {
				$inscription_contrats_link = Amapress::get_pre_inscription_page_href();
			}
			if ( in_array( $contrat->ID, $active_contrats_ids ) ) {
				if ( ! empty( $mes_contrats_href ) ) {
					$content .= '<div>' . Amapress::makeButtonLink( $mes_contrats_href,
							__( 'Inscrit', 'amapress' ),
							true, true ) . '</div>';
				}
			} else {
				if ( ! empty( $inscription_contrats_link ) ) {
					$content .= '<div>' . Amapress::makeButtonLink( $inscription_contrats_link,
							__( 'S\'inscrire', 'amapress' ),
							true, true ) . '</div>';
				}
			}
		}
		if ( amapress_can_access_admin() ) {
			if ( $edit_contrat_url = get_edit_post_link( $c->ID ) ) {
				$content .= '<div><a href="' . esc_url( $edit_contrat_url ) . '" class="post-edit-link">' . __( 'Modifier ce contrat', 'amapress' ) . '</a></div>';
			}
		}
		$content .= amapress_get_panel_end();
	}

	return $content;
}