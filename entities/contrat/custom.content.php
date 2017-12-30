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
			return Amapress::getOption( 'contrat_info_anonymous' );
		}
		$contrat_instance = $contrat_instances[0];
	}

	if ( ! amapress_is_user_logged_in() ) {
		return Amapress::getOption( 'contrat_info_anonymous' );
	}

	$contrat_cnt = $contrat_instance->getContratRaw();
	if ( empty( $contrat_cnt ) || strlen( wp_strip_all_tags( $contrat_cnt ) ) < 15 ) {
//        $ret = '<p>Ce contrat n\'est pas encore souscritible en ligne</p>';
		$ret = Amapress::getOption( 'contrat_info_anonymous' );

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
	$contrat_id   = get_the_ID();
	$contrat      = AmapressContrat::getBy( $contrat_id );
	$prod         = $contrat->getProducteur();
	$prod_id      = $prod->ID;
	$prouits_html = do_shortcode( '[produits columns=4 producteur=' . $prod_id . ']' );
	$prod_user    = $prod->getUserId();

	$user_contrats = AmapressAdhesion::getUserActiveAdhesionIds();
	$links         = '';
	if ( in_array( $contrat_id, $user_contrats ) ) {
		$links .= '<div><a href="' . trailingslashit( get_permalink( $contrat_id ) ) . 'details' . '" class="btn btn-default btn-abonnement">S\'abonner</a></div>';
	} else {
		foreach ( AmapressContrats::get_subscribable_contrat_instances_by_contrat( $contrat_id ) as $contrat_inst ) {
			$contrat_cnt = $contrat_inst->getContratRaw();
			if ( empty( $contrat_cnt ) || strlen( wp_strip_all_tags( $contrat_cnt ) ) < 15 ) {
				continue;
			}
			$links .= '<div><a href="' . trailingslashit( get_permalink( $contrat_id ) ) . 'details/' . $contrat_inst->getSlug() . '" class="btn btn-default btn-abonnement">' . esc_html( $contrat_inst->getTitle() ) . '</a></div>';
		}
//        if (empty($links)) {
//            $links .= '<div><a href="' . trailingslashit(get_permalink(get_the_ID())) . 'details' . '" class="btn btn-default btn-abonnement">S\'abonner</a></div>';
//        }
	}
	if ( ! empty( $links ) ) {
		$links = '<h3>Ses contrats</h3>' . $links;
	}

	$content = amapress_get_panel_start( Amapress::getOption( 'pres_producteur_title' ), null, 'amap-panel-pres-prod amap-panel-pres-prod-' . $prod_id ) .
	           '<div class="contrat-prod-user">' . do_shortcode( '[amapien-avatar user=' . $prod_user . ']' ) . '</div>
                <div class="contrat-prod-summary">' . get_post_meta( $prod_id, 'amapress_producteur_resume', true ) . '</div>
                ' . Amapress::get_know_more( get_permalink( $prod_id ) ) .
	           amapress_get_panel_end() .
	           amapress_get_panel_start( Amapress::getOption( 'pres_contrat_title' ), null, 'amap-panel-pres-contrat amap-panel-pres-contrat-' . get_the_ID() ) .
	           '<div class="contrat-abonnement">' . get_the_content() . '</div>
                            ' . $links .
	           amapress_get_panel_end() .
	           amapress_get_panel_start( Amapress::getOption( 'pres_produits_title' ), null, 'amap-panel-produits amap-panel-produits-' . $prod_id ) . '
                    <div class="row contrat-produits">
                    ' . $prouits_html .
	           amapress_get_panel_end();

	return $content;
}