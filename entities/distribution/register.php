<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_distribution' );
function amapress_register_entities_distribution( $entities ) {
	$entities['distribution'] = array(
		'singular'         => amapress__( 'Distribution hebdomadaire' ),
		'plural'           => amapress__( 'Distributions hebdomadaires' ),
		'public'           => true,
//                'logged_or_public' => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'title_format'     => 'amapress_distribution_title_formatter',
		'slug_format'      => 'from_title',
		'slug'             => amapress__( 'distributions' ),
		'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'dashicons-store',
		'row_actions'      => array(
			'emargement'  => [
				'label'  => 'Liste émargement',
				'target' => '_blank',
				'href'   => function ( $dist_id ) {
					return AmapressDistribution::getBy( $dist_id )->getListeEmargementHref();
				},
			],
			'mailto_resp' => [
				'label'     => 'Mail aux responsable',
				'target'    => '_blank',
				'href'      => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return $dist->getMailtoResponsables();
				},
				'condition' => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return ! empty( $dist->getMailtoResponsables() );
				}
			],
			'smsto_resp'  => [
				'label'     => 'Sms aux responsables',
				'target'    => '_blank',
				'href'      => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return $dist->getSMStoResponsables();
				},
				'condition' => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return ! empty( $dist->getSMStoResponsables() );
				}
			],
		),
		'views'            => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_distribution_views',
		),
		'groups'           => array(
			'Infos' => [
				'context' => 'side',
			],
		),
		'default_orderby'  => 'amapress_distribution_date',
		'default_order'    => 'ASC',
		'fields'           => array(
			'info'              => array(
				'name'  => amapress__( 'Informations spécifiques' ),
				'type'  => 'editor',
				'group' => '3/ Informations',
				'desc'  => 'Informations complémentaires',
			),
			'date'              => array(
				'name'       => amapress__( 'Date de distribution' ),
				'type'       => 'date',
				'time'       => true,
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'group'      => 'Infos',
				'readonly'   => true,
				'desc'       => 'Date de distribution',
			),
			'lieu'              => array(
				'name'       => amapress__( 'Lieu de distribution' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => 'Infos',
				'orderby'    => 'post_title',
				'order'      => 'ASC',
				'top_filter' => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Toutes les lieux',
				),
				'readonly'   => true,
				'desc'       => 'Lieu de distribution',
				'searchable' => true,
			),
			'lieu_substitution' => array(
				'name'       => amapress__( 'Lieu de substitution' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => '1/ Livraison',
				'desc'       => 'Lieu de substitution',
				'searchable' => true,
			),
			'nb_resp_supp'      => array(
				'name'        => amapress__( 'Nombre de responsables de distributions supplémentaires' ),
				'type'        => 'number',
				'required'    => true,
				'desc'        => 'Nombre de responsables de distributions supplémentaires',
				'group'       => '2/ Responsables',
				'default'     => 0,
				'show_column' => false,
			),
			'contrats'          => array(
				'name'      => amapress__( 'Contrats' ),
				'type'      => 'multicheck-posts',
				'post_type' => 'amps_contrat_inst',
				'group'     => '1/ Livraison',
				'readonly'  => true,
				'hidden'    => true,
				'desc'      => 'Contrats',
//                'searchable' => true,
			),
			'responsables'      => array(
				'name'          => amapress__( 'Responsables' ),
				'group'         => '2/ Responsables',
				'type'          => 'select-users',
				'autocomplete'  => true,
				'multiple'      => true,
				'tags'          => true,
				'desc'          => 'Responsables',
				'before_option' => function ( $o ) {
					if ( Amapress::hasRespDistribRoles() ) {
						echo '<p style="color: orange">Lorsqu\'il existe des rôles de responsables de distribution, l\'inscription ne peut se faire que depuis la page d\'inscription par dates.</p>';
					}
				},
				'readonly'      => function ( $o ) {
					return Amapress::hasRespDistribRoles();
				}
//                'searchable' => true,
			),
			'paniers'           => array(
				'name'              => amapress__( 'Panier(s)' ),
				'group'             => '1/ Livraison',
				'desc'              => 'Paniers à cette distribution',
				'show_column'       => false,
//				'bare'              => true,
				'include_columns'   => array(
					'title',
					'amapress_panier_contrat_instance',
					'amapress_panier_status',
					'amapress_panier_date_subst',
				),
				'datatable_options' => array(
					'ordering' => false,
					'paging'   => true,
				),
				'type'              => 'related-posts',
				'query'             => function ( $postID ) {
					$dist = AmapressDistribution::getBy( $postID );

					return array(
						'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'relation' => 'OR',
								array(
									'key'     => 'amapress_panier_date',
									'value'   => $dist->getDate(),
									'compare' => '=',
									'type'    => 'NUMERIC'
								),
								array(
									array(
										'key'     => 'amapress_panier_status',
										'value'   => 'delayed',
										'compare' => '=',
									),
									array(
										'key'     => 'amapress_panier_date_subst',
										'value'   => $dist->getDate(),
										'compare' => '=',
										'type'    => 'NUMERIC'
									),
								)
							)
						)
					);
				}
			)
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_distribution', 'amapress_can_delete_distribution', 10, 2 );
function amapress_can_delete_distribution( $can, $post_id ) {
	return false;
}

function amapress_get_active_contrat_month_options( $args ) {
	$months    = array();
	$min_month = amapress_time();
	$max_month = amapress_time();
	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
		$min_month = $contrat->getDate_debut() < $min_month ? $contrat->getDate_debut() : $min_month;
		$max_month = $contrat->getDate_fin() > $max_month ? $contrat->getDate_fin() : $max_month;
	}
	$min_month = Amapress::start_of_month( $min_month );
	$max_month = Amapress::end_of_month( $max_month );
	$month     = $min_month;
	while ( $month <= $max_month ) {
		$months[ date_i18n( 'Y-m', $month ) ] = date_i18n( 'F Y', $month );
		$month                                = Amapress::add_a_month( $month );
	}

	return $months;
}

function amapress_distribution_responsable_roles_options() {
	$ret = [];
	for ( $i = 1; $i < 6; $i ++ ) {
		$ret[] = array(
			'id'   => "resp_role_$i-name",
			'name' => amapress__( "Nom du rôle $i" ),
			'type' => 'text',
			'desc' => 'Nom du rôle de responsable de distribution',
		);
		$ret[] = array(
			'id'   => "resp_role_$i-desc",
			'name' => amapress__( "Description du rôle $i" ),
			'type' => 'editor',
			'desc' => 'Description du rôle de responsable de distribution',
		);
	}
	$ret[] = array(
		'type' => 'save',
	);

	return $ret;
}