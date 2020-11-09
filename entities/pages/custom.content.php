<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//add_filter('amapress_get_custom_content_page_front', 'amapress_get_custom_content_page_front');
//function amapress_get_custom_content_page_front($content)
//{
////    $max = amapress_is_user_logged_in() ? Amapress::getOption('agenda_max_dates') : Amapress::getOption('agenda_max_public_dates');
////    $agenda_content = do_shortcode('[next_events max=' . $max . ']');
//////    $agenda = '';
//////    if (trim(wp_strip_all_tags($agenda_content, true)) != '') {
//////    (amapress_is_user_logged_in() ? ' (<a href="' . Amapress_Agenda_ICAL_Export::get_link_href() . '"><i class="fa fa-calendar" aria-hidden="true"></i> iCal</a>)' : '') . '</h2>' .
////        $agenda = '<h2 id="front-adgenda-title">' . (amapress_is_user_logged_in() ?  Amapress::getOption('front_agenda_title') : Amapress::getOption('front_agenda_public_title')) . '</h2>' .
////            $agenda_content;
//////    }
////
////    $produits_content = Amapress::get_contrats_list();
////    $produits = '<h2 id="front-produits-title">' . Amapress::getOption('front_produits_title') . '</h2>';
////    if (trim(wp_strip_all_tags($produits_content, true)) != '') {
////        $interm = '';
//////        if (Amapress::isIntermittenceEnabled() && Amapress::userCanRegister()) {
//////            $interm = amapress_get_button(__('Devenir intermittent', 'amapress'), Amapress::getMesInfosSublink('adhesions/intermittence/inscription'));
//////        }
////        if (Amapress::isIntermittenceEnabled()) {
////            $interm = do_shortcode('[intermittents-inscription view=me show_info=no]');
////        }
////
////        $produits .= $produits_content . $interm;
////    }
////
//////    $map_content = do_shortcode('[nous-trouver]');
//////    $map = '<h2 id="front-map-title">' . Amapress::getOption('front_map_title') . '</h2>
//////                            ' . $map_content;
//
//    return $content;
//}