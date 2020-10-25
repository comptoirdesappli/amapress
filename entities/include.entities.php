<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( AMAPRESS__PLUGIN_DIR . 'entities/transients.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/class.amapress-users-list-table.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/post-its.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/custom-menu-items.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/slug.updater.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/titles.formatter.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/views.filters.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/validation.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/query.filters.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/query.vars.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/query.rewrite.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/actions.handlers.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/actions.messages.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/admin.menu.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/login.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/registration.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/ical.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/message/options.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amapien/amap_roles.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_paiement/tables.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_adhesion/tables.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_request/cf7.handler.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_request/cf7.tags.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_request/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_request/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_request/register.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_period/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_period/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_period/register.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/shortcodes.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailing_group/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailing_group/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailing_group/shortcodes.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/ouvaton.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/sudouest.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/google.groups.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/ovh.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/framalist.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/mailchimp.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/mailinglist/campaignmonitor.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/reminder/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/reminder/register.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/produit/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/recette/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/producteur/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/distribution/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/lieu_distribution/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/visite/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amap_event/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/assemblee/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/panier/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amapien/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_paiement/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_paiement/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/message/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_adhesion/classes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/intermittence/classes.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/produit/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/recette/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/producteur/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/distribution/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/lieu_distribution/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/visite/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amap_event/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/assemblee/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/panier/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_adhesion/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amapien/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_paiement/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_paiement/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/pages/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/message/custom.content.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/intermittence/custom.content.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/distribution/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/lieu_distribution/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/produit/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/recette/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amapien/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/visite/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/intermittence/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amap_event/shortcodes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_adhesion/shortcodes.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/pages/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/produit/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/recette/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/producteur/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/distribution/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/lieu_distribution/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/visite/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amap_event/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/assemblee/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/panier/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amapien/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_paiement/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_paiement/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/message/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_adhesion/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/intermittence/register.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/news/register.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/csv.import.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/csv.export.php' );


require_once( AMAPRESS__PLUGIN_DIR . 'entities/distribution/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_adhesion/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_paiement/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/adhesion_paiement/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/panier/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/recette/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/assemblee/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/visite/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amap_event/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/intermittence/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat/actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amapien/actions.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/distribution/recalls.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/visite/recalls.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/amap_event/recalls.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/intermittence/recalls.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat/recalls.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/contrat_adhesion/recalls.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/assemblee/recalls.php' );


require_once( AMAPRESS__PLUGIN_DIR . 'entities/produit/pager.views.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/recette/pager.views.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/message/handler.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/message/display.php' );
