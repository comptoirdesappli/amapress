<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'query_vars', 'amapress_add_query_vars' );
function amapress_add_query_vars( $query_vars ) {
	$query_vars[] = 'amapress_date';
	$query_vars[] = 'viewmode';
	$query_vars[] = 'subview';
	$query_vars[] = 'gallery_page';
	$query_vars[] = 'amp_action';
	$query_vars[] = 'amapress_producteur';
	$query_vars[] = 'amapress_adhesion';
	$query_vars[] = 'amapress_lieu';
	$query_vars[] = 'amapress_role';
	$query_vars[] = 'amapress_user';
	$query_vars[] = 'amapress_referent';
	$query_vars[] = 'amapress_contrat_inst';
	$query_vars[] = 'amapress_contrat_qt';
	$query_vars[] = 'amapress_contrat';
	$query_vars[] = 'amapress_subcontrat';
	$query_vars[] = 'amapress_coadherents';
	$query_vars[] = 'amapress_post';
	$query_vars[] = 'amapress_status';
	$query_vars[] = 'amapress_pmt_type';
	$query_vars[] = 'amapress_adhesion_period';
	$query_vars[] = 'amapress_info';
	$query_vars[] = 'amapress_recette_produits';
	$query_vars[] = 'amapress_recette_tag';
	$query_vars[] = 'amapress_recette_tag_not_in';
	$query_vars[] = 'amapress_produit_recette';
	$query_vars[] = 'amapress_produit_tag';
	$query_vars[] = 'amapress_produit_tag_not_in';
	$query_vars[] = 'amapress_with_coadherents';
	$query_vars[] = 'amapress_no_filter_referent';
	$query_vars[] = 'amapress_mlgrp_id';

	return $query_vars;
}