<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_panier' );
function amapress_register_entities_panier( $entities ) {
	$entities['panier'] = array(
		'singular'         => amapress__( 'Panier' ),
		'plural'           => amapress__( 'Paniers' ),
		'public'           => true,
		'logged_or_public' => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'title_format'     => 'amapress_panier_title_formatter',
		'slug_format'      => 'from_title',
		'slug'             => amapress__( 'paniers' ),
		'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'fa-menu fa-shopping-basket',
		'views'            => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_panier_views',
		),
		'groups'           => [
			'Modification' => [
				'context' => 'side',
			]
		],
		'default_orderby'  => 'amapress_panier_date',
		'default_order'    => 'ASC',
		'fields'           => array(
			'date'              => array(
				'name'       => amapress__( 'Livraison du panier' ),
				'type'       => 'date',
				'readonly'   => true,
				'desc'       => 'Date de distribution',
				'group'      => '1/ Informations',
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'contrat_instance'  => array(
				'name'       => amapress__( 'Contrat' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_contrat_inst',
				'readonly'   => true,
				'desc'       => 'Contrat',
				'searchable' => true,
				'group'      => '1/ Informations',
				'orderby'    => 'post_title',
				'order'      => 'ASC',
				'top_filter' => array(
					'name'        => 'amapress_contrat',
					'placeholder' => 'Toutes les contrats',
				),
			),
			'paniers'           => array(
				'name'              => amapress__( 'Distribution(s)' ),
				'group'             => '1/ Informations',
				'show_column'       => false,
				'include_columns'   => array(
					'title',
				),
				'datatable_options' => array(
					'ordering' => false,
					'paging'   => false,
				),
				'type'              => 'related-posts',
				'query'             => function ( $postID ) {
					$panier = AmapressPanier::getBy( $postID );

					return 'post_type=amps_distribution&amapress_date=' . date( 'Y-m-d', $panier->getDate() );
				}
			),
			'produits_selected' => array(
				'name'         => amapress__( 'Produits associés' ),
				'type'         => 'select-posts',
				'post_type'    => AmapressProduit::INTERNAL_POST_TYPE,
				'desc'         => 'Produits associés aux paniers',
				'multiple'     => true,
				'tags'         => true,
				'autocomplete' => true,
				'group'        => '2/ Contenu',
			),
//			'produits'         => array(
//				'name'   => amapress__( 'Panier' ),
//				'type'   => 'custom',
//				'custom' => array( 'AmapressPaniers', "panierTable" ),
//				'save'   => array( 'AmapressPaniers', 'savePanierTable' ),
//				'desc'   => 'Produits',
//			),
			'status'            => array(
				'name'          => amapress__( 'Statut' ),
				'type'          => 'select',
				'options'       => array(
					''          => 'Date prévue',
					'cancelled' => 'Annulé',
					'delayed'   => 'Reporté',
				),
				'top_filter'    => array(
					'name'        => 'amapress_status',
					'placeholder' => 'Tous les status',
				),
				'group'         => 'Modification',
				'before_option' => '<script type="text/javascript">
jQuery(function() {
    var $status_field = jQuery("#amapress_panier_status");
    var $date_subst_field = jQuery("#amapress_panier_date_subst");
    function activate_date_subst() {
        if ("delayed" === $status_field.val()) {
	        $date_subst_field.show();
        } else {
    	    $date_subst_field.hide();
        }
    }
    activate_date_subst();
    $status_field.on("change", activate_date_subst);
});
</script>',
			),
			'date_subst'        => array(
				'name'    => amapress__( 'Nouvelle date' ),
				'type'    => 'select',
				'cache'   => false,
				'options' => function ( $option ) {
					$ret = [ '' => '--Aucune--' ];
					/** @var TitanFrameworkOptionSelect $option */
					$panier = AmapressPanier::getBy( $option->getPostID() );
					if ( ! $panier ) {
						return $ret;
					}
					$dists = AmapressDistribution::get_distributions(
						Amapress::add_a_month( $panier->getDate(), - 2 ),
						Amapress::add_a_month( $panier->getDate(), 2 ),
						'ASC' );

					foreach ( $dists as $dist ) {
						$ret[ strval( $dist->getDate() ) ] = date_i18n( 'd/m/Y', $dist->getDate() );
					}

					return $ret;
				},
				'desc'    => 'Choisir une nouvelle date pour une livraison de panier dont le statut est "<strong>Reporté</strong>"',
				'group'   => 'Modification',
			),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_panier', 'amapress_can_delete_panier', 10, 2 );
function amapress_can_delete_panier( $can, $post_id ) {
	return false;
}

add_filter( 'amapress_panier_fields', 'amapress_panier_fields' );
function amapress_panier_fields( $fields ) {
	if ( isset( $_REQUEST['post'] ) || isset( $_REQUEST['post_ID'] ) ) {
		$post_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : $_REQUEST['post_ID'];
		if ( get_post_type( $post_id ) == AmapressPanier::INTERNAL_POST_TYPE ) {
			$panier = AmapressPanier::getBy( $post_id );
			if ( $panier->getContrat_instanceId()
			     && ( $panier->getContrat_instance()->hasPanier_CustomContent() ) ) {
				foreach ( AmapressContrats::get_contrat_quantites( $panier->getContrat_instanceId() ) as $quantite ) {
					$fields[ 'contenu_' . $quantite->ID ] = array(
						'name'  => amapress__( 'Contenu pour ' . $quantite->getTitle() ),
						'type'  => 'editor',
						'group' => '2/ Contenu',
					);
				}
			}
		}
	}


	return $fields;
}