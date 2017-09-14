<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion' );
function amapress_register_entities_adhesion( $entities ) {
	$entities['adhesion'] = array(
		'singular'         => amapress__( 'Inscription Contrat' ),
		'plural'           => amapress__( 'Inscriptions Contrat' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => false,
		'editor'           => false,
		'slug'             => 'adhesions',
		'title_format'     => 'amapress_adhesion_title_formatter',
		'slug_format'      => 'from_title',
		'menu_icon'        => 'flaticon-signature',
		'labels'           => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Ajout Inscription',
		),
		'row_actions'      => array(
			'renew' => 'Renouveler',
		),
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_adhesion_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'adherent'         => array(
				'name'         => amapress__( 'Adhérent' ),
				'type'         => 'select-users',
				'required'     => true,
				'desc'         => 'Sélectionner un amapien. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la vue en cours (raccourci : F5)',
				'group'        => '1/ Informations',
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'searchable'   => true,
			),
			'status'           => array(
				'name'     => amapress__( 'Statut' ),
				'type'     => 'select',
				'group'    => '1/ Informations',
				'options'  => array(
					'to_confirm' => 'En attente de confirmation',
					'confirmed'  => 'Confirmée',
				),
				'default'  => 'confirmed',
				'required' => true,
				'desc'     => 'Statut',
			),
			'quantites_editor' => array(
				'name'        => amapress__( 'Contrat et Quantité(s)' ),
				'type'        => 'custom',
				'show_column' => false,
				'custom'      => 'amapress_adhesion_contrat_quantite_editor',
				'save'        => 'amapress_save_adhesion_contrat_quantite_editor',
				'desc'        => 'Contrat et Quantité(s)',
				'group'       => '2/ Contrat',
				'csv'         => false,
//                'show_on' => 'edit',
			),
			'contrat_instance' => array(
				'name'              => amapress__( 'Contrat' ),
				'type'              => 'select-posts',
//                'readonly' => 'edit',
				'hidden'            => true,
				'group'             => '2/ Contrat',
				'post_type'         => 'amps_contrat_inst',
				'desc'              => 'Contrat',
				'import_key'        => true,
//                'required' => true,
				'autoselect_single' => true,
				'top_filter'        => array(
					'name'        => 'amapress_contrat_inst',
					'placeholder' => 'Tous les contrats'
				),
				'csv_required'      => true,
				'searchable'        => true,
			),
			'contrat_quantite' => array(
				'name'         => amapress__( 'Quantité' ),
				'type'         => 'multicheck-posts',
				'readonly'     => true,
				'hidden'       => true,
				'group'        => '2/ Contrat',
				'required'     => true,
				'post_type'    => 'amps_contrat_quant',
				'desc'         => 'Quantité',
				'top_filter'   => array(
					'name'        => 'amapress_contrat_qt',
					'placeholder' => 'Toutes les quantités'
				),
				'csv_required' => true,
				'wrap_edit'    => false,
//                'import_key' => true,
//                'csv_required' => true,
			),
			'date_debut'       => array(
				'name'         => amapress__( 'Date de début' ),
				'type'         => 'date',
				'required'     => true,
				'group'        => '1/ Informations',
				'desc'         => 'Date à laquelle démarre le contrat',
				'csv_required' => true,
			),
			'paiements'        => array(
				'name'     => amapress__( 'Nombre de chèque' ),
				'type'     => 'custom',
				'group'    => '3/ Paiements',
				'required' => true,
				'desc'     => 'Nombre de paiements. <b>Lorsque vous changer la valeur de ce champs, il est nécessaire d\'enregistrer l\'adhésion</b>',
				'custom'   => 'amapress_paiements_count_editor',
				'show_on'  => 'edit-only',
//                'csv_required' => true,
			),
			'paiements_editor' => array(
				'name'        => amapress__( 'Details des paiements' ),
				'type'        => 'custom',
				'show_column' => false,
				'custom'      => 'amapress_paiements_editor',
				'save'        => 'amapress_save_paiements_editor',
//                'desc' => 'Details des',
				'group'       => '3/ Paiements',
				'csv'         => false,
				'show_on'     => 'edit-only',
			),
			'lieu'             => array(
				'name'              => amapress__( 'Lieu' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_lieu',
				'required'          => true,
				'desc'              => 'Sélectionner le lieu de distribution',
				'group'             => '2/ Contrat',
				'import_key'        => true,
				'csv_required'      => true,
				'autoselect_single' => true,
				'searchable'        => true,
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Tous les lieux'
				)
			),
			'message'          => array(
				'name'        => amapress__( 'Message' ),
				'type'        => 'textarea',
				'readonly'    => true,
				'show_column' => false,
				'hidden'      => true,
				'desc'        => 'Message',
				'csv'         => false,
			),
			'adherent2'        => array(
				'name'         => amapress__( 'Co-Adhérent 1' ),
				'type'         => 'select-users',
				'required'     => false,
				'desc'         => 'Sélectionner un Co-Adhérent 1. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la vue en cours (raccourci : F5)',
				'group'        => '1/ Informations',
				'autocomplete' => true,
				'searchable'   => true,
			),
			'adherent3'        => array(
				'name'         => amapress__( 'Co-Adhérent 2' ),
				'type'         => 'select-users',
				'required'     => false,
				'desc'         => 'Sélectionner un Co-Adhérent 2. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la vue en cours (raccourci : F5)',
				'group'        => '1/ Informations',
				'autocomplete' => true,
				'searchable'   => true,
			),
			'date_fin'         => array(
				'name'        => amapress__( 'Date de fin' ),
				'type'        => 'date',
				'group'       => '4/ Fin de contrat avant terme',
				'desc'        => 'Date à laquelle se termine le contrat',
				'show_column' => false,
			),
			'fin_raison'       => array(
				'name'        => amapress__( 'Raison de fin' ),
				'type'        => 'textarea',
				'group'       => '4/ Fin de contrat avant terme',
				'desc'        => 'Raison de fin du contrat',
				'show_column' => false,
			),
//            'co-adherents' => array(
//                'name' => amapress__('Binômes'),
//                'type' => 'multicheck-users',
//                'desc' => 'Binômes',
//                'csv' => false,
//            ),
//            'pstatus' => array(
//                'name' => amapress__('Statut'),
//                'type' => 'custom',
//                'column' => array('AmapressContrats', "paiementStatus"),
//                'save' => null,
//                'desc' => 'Statut',
//                'csv' => false,
//            ),
		),
	);

	return $entities;
}


function amapress_adhesion_contrat_quantite_editor( $post_id ) {
	$ret               = '';
	$adh               = new AmapressAdhesion( $post_id );
	$date_debut        = $adh->getDate_debut() ? $adh->getDate_debut() : amapress_time();
	$quants            = $adh->getContrat_instance() ? $adh->getContrat_quantites_IDs() : array();
	$paniers_variables = $adh->getPaniersVariables();
	$ret               .= '<fieldset style="min-width: inherit">';
	$contrats          = AmapressContrats::get_active_contrat_instances( $adh->getContrat_instance() ? $adh->getContrat_instance()->ID : null, $date_debut );
	$had_contrat       = false;
	foreach ( $contrats as $contrat_instance ) {
		if ( $contrat_instance->isPanierVariable() ) {
			if ( TitanFrameworkOption::isOnNewScreen() ) {
				$had_contrat = true;
				$id          = 'contrat-' . $contrat_instance->ID;
				$ret         .= sprintf( '<label for="%s"><input class="%s" id="%s" type="checkbox" name="%s[]" value="%s" %s/> <b>%s</b></label><br>',
					$id,
					'multicheckReq', //multicheckReq
					$id,
					'amapress_adhesion_contrat_vars',
					esc_attr( $contrat_instance->ID ),
					'',
					esc_html( $contrat_instance->getTitle() )
				);
			} else {
				if ( ! $paniers_variables ) {
					$paniers_variables = array();
				}

				$columns = array(
					array(
						'title' => 'Produit',
						'data'  => 'produit',
					),
				);
				foreach ( $contrat_instance->getListe_dates() as $date ) {
					$columns[] = array(
						'title' => date_i18n( 'd/m/y', $date ),
						'data'  => 'd-' . $date,
					);
				}

				$data = array();
				foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance->ID ) as $quant ) {
					$row     = array(
						'produit' => esc_html( $quant->getTitle() ),
					);
					$options = $quant->getQuantiteOptions();
					foreach ( $contrat_instance->getListe_dates() as $date ) {
						$val         = isset( $paniers_variables[ $date ][ $quant->ID ] ) ? $paniers_variables[ $date ][ $quant->ID ] : '';
						$is_empty    = empty( $val );
						$empty_class = $is_empty ? 'contrat_panier_vars-empty' : 'contrat_panier_vars-with-value';
//                        $display_val = $is_empty ? '' : esc_html($val);
						$ed = '';
//                        $ed .= '<div class="contrat_panier_vars-wrapper">';
//                        $ed .= "<span class='contrat_panier_vars-value $empty_class' id='panier_vars_{$date}_{$quant->ID}-value'>{$display_val}</span>";
//                        $ed .= "<select id='panier_vars_{$date}_{$quant->ID}-select'
//data-value-id='panier_vars_{$date}_{$quant->ID}-value'
//name='amapress_adhesion_contrat_panier_vars[$date][{$quant->ID}]' class='contrat_panier_vars-select $empty_class'>";
						$ed .= "<select name='amapress_adhesion_contrat_panier_vars[$date][{$quant->ID}]' class='contrat_panier_vars-select $empty_class'>";
						$ed .= tf_parse_select_options( $options, $val, false );
						$ed .= '</select>';
//                        $ed .= '</div>';
						if ( $quant->getAvailFrom() && $quant->getAvailTo() ) {
							if ( $date < Amapress::start_of_day( $quant->getAvailFrom() ) || $date > Amapress::end_of_day( $quant->getAvailTo() ) ) {
								$ed = '<span class="contrat_panier_vars-na">NA</span>';
							}
						}
						$row[ 'd-' . $date ] = $ed;
					}
					$data[] = $row;
				}

//                $ret .= '<table class="display nowrap dataTable no-footer" width="100%" cellspacing="0" role="grid" style="margin-left: 0px; width: 9875px;"><thead><tr role="row"><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 60px;">Produit</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">29/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">31/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">31/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">30/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/09/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/09/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">30/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">29/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">17/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">24/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">31/08/17</th></tr></thead></table>';

				$had_contrat = true;
				$ret         .= amapress_get_datatable( 'quant-commandes', $columns, $data, array(
					'bSort'        => true,
					'paging'       => false,
					'searching'    => true,
					'bAutoWidth'   => true,
					'responsive'   => false,
					'initComplete' => 'function() {
                    jQuery(".contrat_panier_vars-select").parent().click(
                        function() {
                            jQuery(this).find(".contrat_panier_vars-select").css(\'visibility\', \'visible\');
                        }
                    );
                    jQuery(".contrat_panier_vars-select.contrat_panier_vars-empty").css(\'visibility\', \'hidden\');
                    }',
					'scrollX'      => true,
//                    'fixedHeader' => array(
//                        'headerOffset' => 32,
//                    ),
					'fixedColumns' => array( 'leftColumns' => 1 ),
				) );
			}
		} else {
			$contrat_quants     = AmapressContrats::get_contrat_quantites( $contrat_instance->ID );
			$contrat_quants_ids = array_map( function ( $c ) {
				return $c->ID;
			}, $contrat_quants );
			if ( empty( $contrat_quants ) || count( $contrat_quants ) == 0 ) {
				continue;
			}
			if ( count( $quants ) > 0 && count( array_intersect( $quants, $contrat_quants_ids ) ) == 0 ) {
				continue;
			}

			$had_contrat = true;
			$ret         .= '<b>' . esc_html( $contrat_instance->getTitle() ) . '</b>';
			$ret         .= '<div>';
			foreach ( $contrat_quants as $quantite ) {
				$id  = 'contrat-' . $contrat_instance->ID . '-quant-' . $quantite->ID;
				$ret .= sprintf( '<label for="%s"><input class="%s" id="%s" type="checkbox" name="%s[]" value="%s" %s/> %s</label><br>',
					$id,
					'multicheckReq', //multicheckReq
					$id,
					'amapress_adhesion_contrat_quants',
					esc_attr( $quantite->ID ),
					checked( in_array( $quantite->ID, $quants ), true, false ),
					esc_html( $quantite->getTitle() )
				);
			}
			$ret .= '</div>';
		}
	}
	if ( ! $had_contrat ) {
		$ret .= '<p class="adhesion-date-error">La date de début (' . esc_html( date_i18n( 'd/m/Y', $date_debut ) ) . ') est en dehors des dates du contrat associé</p>';
	}
	$ret .= '</fieldset>';

	return $ret;
}

function amapress_save_adhesion_contrat_quantite_editor( $adhesion_id ) {
	if ( ! empty( $_REQUEST['amapress_adhesion_contrat_vars'] ) ) {
		update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_instance', intval( $_REQUEST['amapress_adhesion_contrat_vars'][0] ) );
	} else if ( isset( $_REQUEST['amapress_adhesion_contrat_panier_vars'] ) ) {
//        var_dump($_REQUEST['amapress_adhesion_contrat_panier_vars']);
//        die();
		update_post_meta( $adhesion_id, 'amapress_adhesion_panier_variables', $_REQUEST['amapress_adhesion_contrat_panier_vars'] );
	} else if ( isset( $_REQUEST['amapress_adhesion_contrat_quants'] ) ) {
//        var_dump($adhesion_id);
//        var_dump($_REQUEST['amapress_adhesion_contrat_quants']);
		$quants = array_map( 'intval', $_REQUEST['amapress_adhesion_contrat_quants'] );
		if ( ! empty( $quants ) ) {
			$first_quant = new AmapressContrat_quantite( $quants[0] );
			update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_instance', $first_quant->getContrat_instance()->ID );
			update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_quantite', $quants );
		}
	}
}

//add_filter('amapress_can_delete_contrat_adhesion', 'amapress_can_delete_contrat_adhesion', 10, 2);
//function amapress_can_delete_contrat_adhesion($can, $post_id) {
//    return false;
//}

add_action( 'amapress_row_action_adhesion_renew', 'amapress_row_action_adhesion_renew' );
function amapress_row_action_adhesion_renew( $post_id ) {
	$adhesion     = new AmapressAdhesion( $post_id );
	$new_adhesion = $adhesion->cloneAdhesion();
	if ( ! $new_adhesion ) {
		wp_die( 'Une erreur s\'est produit lors du renouvèlement de l\'adhésion. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_adhesion->ID}&action=edit" ) );
}

add_filter( 'amapress_row_actions_adhesion', 'amapress_row_actions_adhesion', 10, 2 );
function amapress_row_actions_adhesion( $actions, $adhesion_id ) {
	$adh = new AmapressAdhesion( $adhesion_id );

//    $contrat_instance_id = $adh->getContrat_instanceId();
//    $contrat_instances_ids = AmapressContrats::get_active_contrat_instances_ids_by_contrat($adh->getContrat_instance()->getModel()->ID,
//        null, true);
//    $contrat_instances_ids = array_filter(
//        $contrat_instances_ids,
//        function($id) use ($contrat_instance_id) {
//            return $id != $contrat_instance_id;
//        }
//    );

	$new_contrat_id = $adh->getNextContratInstanceId();

	if ( ! $new_contrat_id ) {
		unset( $actions['renew'] );
	}

	return $actions;
}

//function amapress_echo_all_contrat_quantite() {
//	$ret    = '';
//	$ret    .= '<div><ul class="nav nav-tabs" role="tablist">';
//	$active = 'active';
//	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat_instance ) {
//		$ret    .= '<li role="presentation" class="'.$active.'"><a href="#contrat-instance-'.$contrat_instance->ID.'" aria-controls="'.$contrat_instance->ID . '" role="tab" data-toggle="tab">'.esc_html($contrat_instance->getTitle()).'</a></li>';
//		$active = '';
//	}
//	$ret .= '</ul>';
//
//	$ret    .= '<div class="tab-content">';
//	$active = 'active';
//	foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
//		$ret    .= '<div role = "tabpanel" class="tab-pane '.$active.'" id="contrat-instance-' . $contrat_instance->ID . '" >'.
//		           amapress_get_contrat_quantite_datatable( $contrat_instances_id ) .'</div >';
//		$active = '';
//	}
//	$ret .= '</div >';
//
//	return $ret;
//}

function amapress_get_contrat_quantite_datatable( $contrat_instance_id, $lieu_id = null, $date = null ) {
	$contrat_instance = new AmapressContrat_instance( $contrat_instance_id );

	$columns = array(
		array(
			'title' => 'Quantité',
			'data'  => array(
				'_'    => 'quant',
				'sort' => 'quant',
			)
		),
	);
	$lieux   = Amapress::get_lieux();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$columns[] = array(
				'title' => $lieu->getShortName(),
				'data'  => array(
					'_'    => "lieu_{$lieu->ID}",
					'sort' => "lieu_{$lieu->ID}",
				)
			);
		}
	}
	$columns[] = array(
		'title' => 'Tous',
		'data'  => array(
			'_'    => 'all',
			'sort' => 'all',
		)
	);

	$data      = array();
	$adhesions = AmapressContrats::get_active_adhesions( $contrat_instance_id, null, $lieu_id, $date, true );
	$quants    = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
	foreach ( $quants as $quant ) {
		/** @var AmapressContrat_quantite $quant */
		$row          = array();
		$row['quant'] = $quant->getTitle();
		if ( count( $lieux ) > 1 ) {
			foreach ( $lieux as $lieu ) {
				$lieu_quant_count = 0;
				$lieu_quant_sum   = 0;
				foreach ( $adhesions as $adh ) {
					if ( $adh->getLieuId() != $lieu->ID ) {
						continue;
					}
					if ( $contrat_instance->isPanierVariable() ) {
						foreach ( $adh->getVariables_Contrat_quantites( $date ) as $quant ) {
							if ( $quant['contrat_quantite']->ID != $quant->ID ) {
								continue;
							}

							$lieu_quant_count += 1;
							$lieu_quant_sum   += $quant['quantite'];
						}
					} else {
						foreach ( $adh->getContrat_quantites_IDs() as $adh_quant_id ) {
							if ( $adh_quant_id != $quant->ID ) {
								continue;
							}

							$lieu_quant_count += 1;
							$lieu_quant_sum   += $quant->getQuantite();
						}
					}
				}
				$row["lieu_{$lieu->ID}"] = "$lieu_quant_count ($lieu_quant_sum)";
			}
		}
		$all_quant_count = 0;
		$all_quant_sum   = 0;
		foreach ( $adhesions as $adh ) {
			foreach ( $adh->getContrat_quantites_IDs() as $adh_quant_id ) {
				if ( $adh_quant_id != $quant->ID ) {
					continue;
				}

				$all_quant_count += 1;
				$all_quant_sum   += $quant->getQuantite();
			}
		}
		$row['all'] = "$all_quant_count ($all_quant_sum)";
		$data[]     = $row;
	}

//	<h4>' . esc_html( $contrat_instance->getTitle() ) . '</h4>

	/** @var AmapressDistribution $dist */
	$dist = array_shift( AmapressDistribution::get_next_distributions( $date, 'ASC' ) );

	return '<div class="contrat-instance-recap contrat-instance-' . $contrat_instance_id . '">
<p>Prochaine distribution: ' . esc_html( $dist ? date_i18n( 'd/m/Y H:i', $dist->getStartDateAndHour() ) : 'non planifiée' ) . '</p>' .
	       //	       '<p class="producteur">' . $contrat_instance->getModel()->getProducteur()->getUser()->getDisplay() . '</p>' .
	       amapress_get_datatable( 'contrat-instance-recap-' . $contrat_instance_id,
		       $columns, $data,
		       array(
			       'paging' => false,
			       'bSort'  => true,
		       ),
		       array(
			       Amapress::DATATABLES_EXPORT_EXCEL,
			       Amapress::DATATABLES_EXPORT_PDF,
			       Amapress::DATATABLES_EXPORT_PRINT
		       ) ) . '</div>';
}

//function amapress_echo_all_contrat_paiements_by_date() {
//	$ret = '';
//	foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
//		$ret .= amapress_get_paiement_table_by_dates( $contrat_instances_id );
//	}
//
//	return $ret;
//}

function amapress_get_paiement_table_by_dates( $contrat_instance_id ) {
	$contrat_instance = new AmapressContrat_instance( $contrat_instance_id );
	$paiements        = AmapressContrats::get_all_paiements( $contrat_instance_id );
	$dates            = array_map(
		function ( $p ) {
			/** @var AmapressAmapien_paiement $p */
			return $p->getDate();
		}, $paiements );
	$dates            = array_merge( $dates, $contrat_instance->getPaiements_Liste_dates() );
	$dates            = array_unique( $dates );
	sort( $dates );
	$emetteurs = array_map(
		function ( $p ) use ( $paiements ) {
			/** @var AmapressAmapien_paiement $p */
			$all_emetteurs = array_unique(
				array_map(
					function ( $op ) use ( $p ) {
						/** @var AmapressAmapien_paiement $op */
						if ( $op->getEmetteur() == $p->getEmetteur() ) {
							return '<strong>' . esc_html( $op->getEmetteur() ) . '</strong>';
						} else {
							return esc_html( $op->getEmetteur() );
						}
					},
					array_filter(
						$paiements,
						function ( $op ) use ( $p ) {
							/** @var AmapressAmapien_paiement $op */
							return $op->getAdhesionId() == $p->getAdhesionId();
						}
					)
				)
			);
			usort( $all_emetteurs,
				function ( $a, $b ) {
					$a_emetteur = strip_tags( $a );
					$b_emetteur = strip_tags( $b );
					if ( $a_emetteur == $b_emetteur ) {
						return 0;
					}

					return $a_emetteur < $b_emetteur ? - 1 : 1;
				} );

			return array(
				'emetteur' => $p->getEmetteur(),
				'label'    => implode( ', ', $all_emetteurs ),
				'href'     => $p->getAdhesion()->getAdminEditLink(),
			);
		}, $paiements );
	$emitters  = array();
	foreach ( $emetteurs as $emetteur ) {
		$emitters[ $emetteur['emetteur'] ] = $emetteur;
	}
	usort( $emitters,
		function ( $a, $b ) {
			$a_emetteur = $a['emetteur'];
			$b_emetteur = $b['emetteur'];
			if ( $a_emetteur == $b_emetteur ) {
				return 0;
			}

			return $a_emetteur < $b_emetteur ? - 1 : 1;
		} );

	$columns = array(
		array(
			'title' => 'Emetteur',
			'data'  => 'emetteur',
		),
	);
	foreach ( $dates as $date ) {
		$columns[] = array(
			'title' => date_i18n( 'd/m/Y', $date ),
			'data'  => "date_{$date}",
		);
	}

	$data = array();
	foreach ( $emitters as $emetteur_obj ) {
		$emetteur           = $emetteur_obj['emetteur'];
		$emetteur_label     = $emetteur_obj['label'];
		$emetteur_href      = $emetteur_obj['href'];
		$row                = array(
			'emetteur' => Amapress::makeLink( $emetteur_href, $emetteur_label, false),
		);
		$emetteur_paiements = array_filter(
			$paiements,
			function ( $p ) use ( $emetteur ) {
				/** @var AmapressAmapien_paiement $p */
				return $p->getEmetteur() == $emetteur;
			}
		);
		foreach ( $dates as $date ) {
			$emetteur_date_paiements = array_filter(
				$emetteur_paiements,
				function ( $p ) use ( $date ) {
					/** @var AmapressAmapien_paiement $p */
					return $p->getDate() == $date;
				}
			);
			$contrat_adhesion        = null;
			if ( count( $emetteur_paiements ) > 0 ) {
				$contrat_adhesion = array_shift( array_values( $emetteur_paiements ) )->getAdhesion();
			}
			if ( $contrat_adhesion && ( $date < $contrat_adhesion->getDate_debut() || $date > $contrat_adhesion->getDate_fin() ) ) {
				$val = '###';
			} else {
				$val = implode( ',', array_map(
						function ( $p ) {
							/** @var AmapressAmapien_paiement $p */
							$banque = $p->getBanque();
							if ( ! empty( $banque ) ) {
								return "{$p->getNumero()} ({$banque})";
							} else {
								return "{$p->getNumero()}";
							}
						}, $emetteur_date_paiements )
				);
			}
			$row["date_{$date}"] = $val;
		}
		$data[] = $row;
	}

//	<h4>' . esc_html( $contrat_instance->getTitle() ) . '</h4>
	$dist = array_shift( AmapressDistribution::get_next_distributions( $date ) );

	return '<div class="contrat-instance-recap contrat-instance-' . $contrat_instance_id . '">
<p>Prochaine distribution: ' . esc_html( $dist ? date_i18n( 'd/m/Y H:i', $dist->getStartDateAndHour() ) : 'non planifiée' ) . '</p>' .
	       amapress_get_datatable( "contrat-$contrat_instance_id-paiements-month", $columns, $data, array(
		       'bSort'        => true,
		       'paging'       => false,
		       'searching'    => true,
		       'bAutoWidth'   => true,
		       'responsive'   => false,
		       'scrollX'      => true,
		       'fixedColumns' => array( 'leftColumns' => 1 ),
	       ),
		       array(
			       Amapress::DATATABLES_EXPORT_EXCEL,
			       Amapress::DATATABLES_EXPORT_PDF,
			       Amapress::DATATABLES_EXPORT_PRINT
		       ) ) .
	       '</div>';
}