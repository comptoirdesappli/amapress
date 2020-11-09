<?php


function amapress_contrat_paiements_list_options() {
	global $contrat_paiements_table;
	$option = 'per_page';
	$args   = array(
		'label'   => __( 'Nombre d’éléments par page :', 'amapress' ),
		'default' => 10,
		'option'  => 'contrat_paiements_per_page'
	);
	add_screen_option( $option, $args );
	$contrat_paiements_table = new Amapress_Users_List_Table( __( 'Contrat', 'amapress' ), __( 'Contrats', 'amapress' ), 'contrat_paiements_per_page' );

	add_filter( 'views_gestion-contrats_page_contrat_paiements', 'amapress_views_contrat_paiements_list' );
}

add_filter( 'set-screen-option', 'amapress_contrat_paiements_set_option', 10, 3 );
function amapress_contrat_paiements_set_option( $status, $option, $value ) {
	if ( 'contrat_paiements_per_page' == $option ) {
		return $value;
	}

	return $status;
}

function amapress_views_contrat_paiements_list( $views ) {
	amapress_add_view_button(
		$views, 'all',
		"page=contrat_paiements&amapress_adhesion=all",
		__( 'Tous', 'amapress' ), true );

	return amapress_users_views_filter( $views );
}

function amapress_render_contrat_paiements_list() {
	/** @var Amapress_Users_List_Table $contrat_paiements_table */
	global $contrat_paiements_table;
	$contrat_paiements_table->prepare_items();

	echo '</pre><div class="wrap"><h2>' . __( 'État des règlements de l’ensemble des amapiens', 'amapress' );
	echo '</h2>';

	$contrat_paiements_table->views();
	?>
    <form method="post">
        <input type="hidden" name="page" value="contrat_paiements">
	<?php
	$contrat_paiements_table->search_box( 'search', 'search_id' );
	$contrat_paiements_table->display();
	echo '</form></div>';
}
