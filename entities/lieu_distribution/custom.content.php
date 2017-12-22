<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_get_custom_archive_content_lieu_distribution', 'amapress_get_custom_archive_content_lieu_distribution' );
function amapress_get_custom_archive_content_lieu_distribution( $content ) {
	$lieu    = AmapressLieu_distribution::getBy( get_the_ID() );
	$content = '<p class="lieu-adresse">Adresse : ' . $lieu->getFormattedAdresseHtml() . '</p>' . $content;

	return $content;
}

add_filter( 'amapress_get_custom_content_lieu_distribution', 'amapress_get_custom_content_lieu_distribution' );
function amapress_get_custom_content_lieu_distribution( $content ) {
	$lieu = AmapressLieu_distribution::getBy( get_the_ID() );

	ob_start();

	echo $content;

	$addr_entry = '';
	if ( ! $lieu->isAdresseAccesLocalized() ) {
		$addr_entry = '<div class="lieu-adresse-acces"><h3>Adresse d\'accès</h3>' .
		              '<p>' .
		              esc_html( $lieu->getAdresseAcces() ) .
		              '</p>' .
		              '</div>';
	}

	amapress_echo_panel_start( 'Adresse', 'fa-fa', 'amap-panel-lieu amap-panel-lieu-' . $lieu->ID . ' amap-panel-lieu-address' );
	echo '<p class="lieu-adresse">' .
	     $lieu->getFormattedAdresseHtml() .
	     '</p>';
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Accès', null, 'amap-panel-lieu amap-panel-lieu-' . $lieu->ID . ' amap-panel-lieu-access' );
	echo $addr_entry;
	echo '<p class="lieu-acces">' .
	     $lieu->getAcces() .
	     '</p>';
	if ( $lieu->isAdresseLocalized() ) {
		echo '<div class="lieu-maps">' .
		     do_shortcode( "[lieu-map lieu=$lieu->ID mode=map+streeview]" ) .
		     '</div>';
	}
	amapress_echo_panel_end();

	amapress_echo_panel_start( 'Horaires', null, 'amap-panel-lieu amap-panel-lieu-' . $lieu->ID . ' amap-panel-lieu-hours' );
	echo '<p class="lieu-horaires">' .
	     ' de ' . date_i18n( 'H:i', $lieu->getHeure_debut() ) .
	     ' à ' . date_i18n( 'H:i', $lieu->getHeure_fin() ) .
	     '</p>';
	amapress_echo_panel_end();

	if ( amapress_is_user_logged_in() ) {
		amapress_echo_panel_start( 'Référent', null, 'amap-panel-lieu amap-panel-lieu-' . $lieu->ID . ' amap-panel-lieu-referent' );
		$ref = $lieu->getReferent();
		if ( ! $ref ) {
			echo '<p class="error">Pas de référent</p>';
		} else {
			echo $ref->getDisplay();
		}
//            AmapressUsers::echoUserById($ref, is_user_logged_in() ? 'full' : 'thumb');
		amapress_echo_panel_end();

		amapress_echo_panel_start( 'Contact externe', null, 'amap-panel-lieu amap-panel-lieu-' . $lieu->ID . ' amap-panel-lieu-extern' );
		echo '<p class="lieu-contact-externe">' .
		     $lieu->getContact_externe() .
		     '</p>';
		amapress_echo_panel_end();
	}

	if ( amapress_can_access_admin() || AmapressDistributions::isCurrentUserResponsableThisWeek() ) {
		amapress_echo_panel_start( 'Instructions du lieu', null, 'amap-panel-lieu amap-panel-lieu-' . $lieu->ID . ' amap-panel-lieu-instructions' );
		echo '<p class="lieu-instructions">' .
		     $lieu->getInstructions_privee() .
		     '</p>';
		amapress_echo_panel_end();
	}

	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}