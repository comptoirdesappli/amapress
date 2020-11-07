<?php

function amapress_adhesion_list_options() {
	global $adhesions_table;
	$option = 'per_page';
	$args   = array(
		'label'   => 'Nombre d’éléments par page :',
		'default' => 10,
		'option'  => 'adhesions_per_page'
	);
	add_screen_option( $option, $args );
	$adhesions_table = new Amapress_Users_List_Table( 'Adhésion', 'Adhésions', 'adhesions_per_page' );

	add_filter( 'views_gestion-adhesions_page_adhesion_paiements', 'amapress_views_adhesion_list' );
}

add_filter( 'set-screen-option', 'amapress_adhesion_set_option', 10, 3 );
function amapress_adhesion_set_option( $status, $option, $value ) {
	if ( 'adhesions_per_page' == $option ) {
		return $value;
	}

	return $status;
}

function amapress_views_adhesion_list( $views ) {
	amapress_add_view_button(
		$views, 'all',
		"page=adhesion_paiements&amapress_adhesion=all",
		'Tous', true );

	return amapress_users_views_filter( $views );
}

function amapress_render_adhesion_list() {
	global $adhesions_table;
	$adhesions_table->prepare_items();

	echo '</pre><div class="wrap"><h2>' . 'Règlements Adhésions';
	if ( current_user_can( 'publish_adhesion_paiement' ) ) { ?>
        <a href="<?php echo admin_url( 'post-new.php?post_type=amps_adh_pmt' ); ?>"
           class="page-title-action"><?php echo esc_html( 'Ajouter' ); ?></a>
	<?php }
	echo '</h2>';

	$adhesions_table->views();

	?>
    <form method="post">
        <input type="hidden" name="page" value="adhesion_paiements">
	<?php
	$adhesions_table->search_box( 'search', 'search_id' );
	$adhesions_table->display();
	echo '</form></div>';
}
