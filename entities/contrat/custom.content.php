<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_get_custom_content_contrat_details', 'amapress_get_custom_content_contrat_details', 10, 2 );
function amapress_get_custom_content_contrat_details( $content, $subview ) {
	global $post;
//    $prod_id = get_post_meta(get_the_ID(), 'amapress_contrat_producteur', true);
	if ( ! empty( $subview ) ) {
		$contrat_instance = AmapressContrat_instance::getBy( Amapress::resolve_post_id( $subview, AmapressContrat_instance::INTERNAL_POST_TYPE ) );
	} else {
		$contrat_instances = AmapressContrats::get_active_contrat_instances_by_contrat( get_the_ID() );
		if ( count( $contrat_instances ) == 0 ) {
			return Amapress::getContactInfos();
		}
		$contrat_instance = $contrat_instances[0];
	}

	if ( ! amapress_is_user_logged_in() ) {
		return Amapress::getContactInfos();
	}

	$contrat_cnt = $contrat_instance->getOnlineContratRaw();
	if ( empty( $contrat_cnt ) || strlen( wp_strip_all_tags( $contrat_cnt ) ) < 15 ) {
//        $ret = '<p>Ce contrat n\'est pas encore souscritible en ligne</p>';
		$ret = Amapress::getContactInfos();

		return $ret;
	}

	$cls          = 'contrat-public';
	$new_adhesion = false;
	if ( amapress_is_user_logged_in() ) {
		$adhesions = $contrat_instance->getAdhesionsForUser();
		if ( count( $adhesions ) > 0 ) {
			$post = $adhesions[0]->getPost();
			setup_postdata( $post );
			$cls = 'adhesion';
		} else {
			$cls          = 'new-adhesion';
			$new_adhesion = true;
		}
	}
	$contrat = do_shortcode( amapress_get_post_field_as_html( $contrat_instance->ID, 'contrat_instance', 'contrat' ) );
	wp_reset_postdata();

	if ( $new_adhesion ) {
		$url = trailingslashit( get_permalink( get_the_ID() ) ) . 'inscription';
		ob_start();

//        amapress_handle_action_messages();

		echo "<form method='post' action='$url'>";
		echo "    <input type='hidden' name='contrat_instance_id' value='{$contrat_instance->ID}' />";
//            amapress_echo_panel_start('');
		echo "    <div class='$cls'>$contrat</div>";
		echo "    <label for='inscr_message'>Message</label>";
		echo "    <textarea id='inscr_message' name='message'></textarea>";
		echo "    <input type='submit' value='Soumettre la demande d&apos;inscription' />";
		echo "</form>";
		$content = ob_get_contents();
		ob_clean();
	} else {
		$content = "<div class='$cls'>$contrat</div>";
	}

	return $content;
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

	$content = amapress_get_panel_start( Amapress::getOption( 'pres_producteur_title', 'Présentation de la production' ), null, 'amap-panel-pres-prod amap-panel-pres-prod-' . $prod_id );
	$content .= '<div class="contrat-prod-user">' . do_shortcode( '[amapien-avatar user=' . $prod_user . ']' ) . '</div>';
	$content .= '<div class="contrat-pres-prod">' . wpautop( get_the_content() ) . '</div>';
	if ( $edit_contrat_url = get_edit_post_link( get_the_ID() ) ) {
		$content .= '<div><a href="' . esc_url( $edit_contrat_url ) . '" class="post-edit-link">Modifier cette présentation</a></div>';
	}
	$content .= Amapress::get_know_more( get_permalink( $prod_id ) );
	$content .= amapress_get_panel_end();
	$content .= amapress_get_panel_start( Amapress::getOption( 'pres_produits_title', 'Ses produits' ), null, 'amap-panel-produits amap-panel-produits-' . $prod_id );
	$content .= '<div class="contrat-produits">';
	$content .= $prouits_html;
	$content .= '</div>';
	$content .= amapress_get_panel_end();

	foreach ( AmapressContrats::get_active_contrat_instances_by_contrat( $contrat_id ) as $c ) {
		$content .= amapress_get_panel_start( 'Contrat - ' . esc_html( $c->getTitle() ) );
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
							'Inscrit',
							true, true ) . '</div>';
				}
			} else {
				if ( ! empty( $inscription_contrats_link ) ) {
					$content .= '<div>' . Amapress::makeButtonLink( $inscription_contrats_link,
							'S\'inscrire',
							true, true ) . '</div>';
				}
			}
		}
		if ( $edit_contrat_url = get_edit_post_link( $c->ID ) ) {
			$content .= '<div><a href="' . esc_url( $edit_contrat_url ) . '" class="post-edit-link">Modifier ce contrat</a></div>';
		}
		$content .= amapress_get_panel_end();
	}

	return $content;
}