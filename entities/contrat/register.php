<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 13/05/2016
 * Time: 11:14
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_contrat' );
function amapress_register_entities_contrat( $entities ) {
	$entities['contrat']          = array(
		'singular'                => amapress__( 'Présentation web' ),
		'plural'                  => amapress__( 'Présentations web' ),
		'public'                  => true,
		'thumb'                   => true,
		'editor'                  => true,
		'special_options'         => array(),
		'show_in_menu'            => false,
		'slug'                    => 'contrats',
		'custom_archive_template' => true,
		'menu_icon'               => 'flaticon-note',
		'default_orderby'         => 'post_title',
		'default_order'           => 'ASC',
		'views'                   => array(
			'remove' => array( 'mine' ),
		),
		'groups'                  => [
			'Producteur' => [
				'context' => 'side',
			],
		],
		'edit_header'             => function ( $post ) {
			echo '<h1>Termes du contrat :</h1>';
		},
		'fields'                  => array(
//			'amapress_icon_id' => array(
//				'name'    => amapress__( 'Icône' ),
//				'type'    => 'upload',
//				'group'   => 'Information',
//				'desc'    => 'Icône',
//				'bare_id' => true,
//			),
//            'presentation' => array(
//                'name' => amapress__('Présentation'),
//                'type' => 'editor',
//                'required' => true,
//                'desc' => 'Présentation',
//            ),
//            'nb_visites' => array(
//                'name' => amapress__('Nombre de visites obligatoires'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => 'Nombre de visites obligatoires',
//            ),
//            'max_adherents' => array(
//                'name' => amapress__('Nombre de maximum d\'adhérents'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => 'Nombre de maximum d\'adhérents',
//            ),
			'producteur' => array(
				'name'              => amapress__( 'Producteur' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'required'          => true,
				'desc'              => 'Producteur',
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'group'             => 'Producteur',
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => 'Toutes les producteurs',
				),
				'searchable'        => true,
			),
			'contrats'   => array(
				'name'            => amapress__( 'Contrats' ),
				'show_column'     => true,
				'group'           => 'Contrats',
				'include_columns' => array(
					'title',
					'amapress_contrat_instance_name',
					'amapress_contrat_instance_type',
				),
				'type'            => 'related-posts',
				'query'           => 'post_type=amps_contrat_inst&amapress_date=active&amapress_contrat=%%id%%',
			),
		),
	);
	$entities['contrat_instance'] = array(
		'internal_name'   => 'amps_contrat_inst',
		'singular'        => amapress__( 'Modèle de contrat' ),
		'plural'          => amapress__( 'Modèles de contrat' ),
		'public'          => 'adminonly',
		'show_in_menu'    => false,
		'special_options' => array(),
		'slug'            => 'contrat_instances',
		'title_format'    => 'amapress_contrat_instance_title_formatter',
		'title'           => false,
		'slug_format'     => 'from_title',
		'editor'          => false,
		'menu_icon'       => 'flaticon-interface',
		'default_orderby' => 'post_title',
		'default_order'   => 'ASC',
		'groups'          => array(
			'Statut' => [
				'context' => 'side',
			],
		),
		'edit_header'     => function ( $post ) {
			if ( empty( AmapressContrats::get_contrat_quantites( $post->ID ) ) && TitanFrameworkOption::isOnEditScreen() ) {
				$class   = 'notice notice-error';
				$message = 'Veuillez ajouter des quantités au contrat';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}
			$adhs = AmapressContrats::get_active_adhesions( $post->ID );
			if ( ! empty( $adhs ) ) {
				echo '<p>Ce contrat a déjà des inscriptions. Modifier ce contrat peut impacter les ' . count( $adhs ) . ' inscriptions associées. Par exemple si vous changez le nombre de dates de distribution le montant de l\'inscription sera adapté et les quantités saisies dans le cas d\'un contrat avec quantités variables peuvent être perdues.</p>';
				if ( ! isset( $_REQUEST['adv'] ) ) {
					echo '<p>Si vous voulez malgrès tout éditer le contrat, utiliser le lien suivant : <a href="' . esc_attr( add_query_arg( 'adv', 'true' ) ) . '">Confirmer l\'édition.</a></p>';
				} else {
					echo '<p style="color: red">/!\ Edition après saisie des inscriptions /!\</p>';
				}
			}
		},
		'row_actions'     => array(
			'renew'             => array(
				'label'     => 'Renouveler (prolongement)',
				'condition' => function ( $post_or_user ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_or_user );
					if ( ! $contrat_instance ) {
						return false;
					}

					return $contrat_instance->canRenew();
				}
			),
			'renew_same_period' => array(
				'label'     => 'Renouveler (même période)',
				'condition' => function ( $post_or_user ) {
					$contrat_instance = AmapressContrat_instance::getBy( $post_or_user );
					if ( ! $contrat_instance ) {
						return false;
					}

					$diff = Amapress::datediffInWeeks(
						Amapress::start_of_week( $contrat_instance->getDate_debut() ),
						Amapress::end_of_week( $contrat_instance->getDate_fin() )
					);

					return $diff < 52;
				}
			),
			'clone'             => 'Dupliquer',
		),
		'labels'          => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Ajout modèle de contrat',
			'edit_item'    => 'Éditer - Modèle de contrat',
		),
		'views'           => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_contrat_instance_views',
		),
		'fields'          => array(
			'model'                 => array(
				'name'              => amapress__( 'Présentation web' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressContrat::INTERNAL_POST_TYPE,
				'group'             => 'Gestion',
				'required'          => true,
				'desc'              => 'Sélectionner la présentation web. Si elle n’est pas présente dans la liste ci-dessus, la créer ici « <a href="' . admin_url( 'post-new.php?post_type=amps_contrat' ) . '" target="_blank">présentation web</a> »',
				'import_key'        => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_contrat',
					'placeholder' => 'Toutes les présentations web',
				),
				'readonly'          => 'amapress_is_contrat_instance_readonly',
				'searchable'        => true,
			),
			'name'                  => array(
				'name'     => amapress__( 'Nom complémentaire' ),
				'group'    => 'Gestion',
				'type'     => 'text',
				'desc'     => '(Facultatif) Complément de nom pour le contrat (par ex, "Semaine A")',
				'readonly' => 'amapress_is_contrat_instance_readonly',
			),
			'nb_visites'            => array(
				'name'        => amapress__( 'Nombre de visites obligatoires' ),
				'group'       => 'Information',
				'type'        => 'number',
				'required'    => true,
				'show_column' => false,
				'desc'        => 'Nombre de visites obligatoires chez le producteur',
				'max'         => 12,
			),
//			'type'           => array(
//				'name'          => amapress__( 'Type de contrat' ),
//				'type'          => 'select',
//				'options'       => array(
//					'panier' => 'Distributions régulières',
////					'commande' => 'Commandes',
//				),
//				'required'      => true,
//				'group'         => 'Gestion',
//				'desc'          => 'Type de contrat',
//				'import_key'    => true,
//				'default'       => 'panier',
//				'readonly'      => 'amapress_is_contrat_instance_readonly',
//				'custom_column' => function ( $option, $post_id ) {
//					$status           = [];
//					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
//					if ( $contrat_instance->isPanierVariable() ) {
//						$status[] = 'Paniers variables';
//					} else if ( $contrat_instance->isQuantiteVariable() ) {
//						if ( $contrat_instance->isQuantiteMultiple() ) {
//							$status[] = 'Quantités variables multiples';
//						} else {
//							$status[] = 'Quantités variables';
//						}
//					} else {
//						if ( $contrat_instance->isQuantiteMultiple() ) {
//							$status[] = 'Quantités fixes multiples';
//						} else {
//							$status[] = 'Quantités fixes';
//						}
//					}
//					if ( $contrat_instance->isPrincipal() ) {
//						$status[] = 'Principal';
//					}
//					if ( $contrat_instance->isEnded() ) {
//						$status[] = 'Clôturé';
//					}
//
//					echo implode( ', ', $status );
//				},
//				'conditional'   => array(
//					'_default_' => 'panier',
//					'panier'    => array(
//
//					),
////					'commande'  => array(
////						'commande_liste_dates'   => array(
////							'name'        => amapress__( 'Calendrier des commandes' ),
////							'type'        => 'multidate',
////							'group'       => 'Commandes',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'required'    => true,
////							'show_column' => false,
////							'desc'        => '',
////						),
////						'commande_cannot_modify' => array(
////							'name'        => amapress__( 'Commandes fermes' ),
////							'type'        => 'checkbox',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'group'       => 'Commandes',
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => '',
////						),
////						'commande_open_before'   => array(
////							'name'        => amapress__( 'Ouverture des commandes' ),
////							'type'        => 'number',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'group'       => 'Commandes',
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => 'Ouverture des commandes x jours avant (0=tout de suite)',
////						),
////						'commande_close_before'  => array(
////							'name'        => amapress__( 'Fermeture des commandes' ),
////							'group'       => 'Commandes',
////							'readonly'    => 'amapress_is_contrat_instance_readonly',
////							'type'        => 'number',
////							'required'    => false,
////							'show_column' => false,
////							'desc'        => 'Fermeture des commandes x jours avant',
////						),
////					),
//				)
//			),
			'quant_type'            => array(
				'name'     => amapress__( 'Type de quantités' ),
				'type'     => 'custom',
				'group'    => 'Gestion',
				'readonly' => 'amapress_is_contrat_instance_readonly',
				'column'   => function ( $post_id ) {
					$status           = [];
					$contrat_instance = AmapressContrat_instance::getBy( $post_id );
					if ( $contrat_instance->isPanierVariable() ) {
						$status[] = 'Paniers variables';
					} else if ( $contrat_instance->isQuantiteVariable() ) {
						if ( $contrat_instance->isQuantiteMultiple() ) {
							$status[] = 'Quantités variables multiples';
						} else {
							$status[] = 'Quantités variables';
						}
					} else {
						if ( $contrat_instance->isQuantiteMultiple() ) {
							$status[] = 'Quantités fixes multiples';
						} else {
							$status[] = 'Quantités fixes';
						}
					}
					if ( $contrat_instance->isPrincipal() ) {
						$status[] = 'Principal';
					}
					if ( $contrat_instance->isEnded() ) {
						$status[] = 'Clôturé';
					}

					echo implode( ', ', $status );
				},
				'custom'   => function ( $post_id ) {
					$type             = 'quant_fix';
					$contrat_instance = AmapressContrat_instance::getBy( $post_id, true );
					if ( $contrat_instance ) {
						if ( $contrat_instance->isPanierVariable() ) {
							$type = 'panier_var';
						} else if ( $contrat_instance->isQuantiteVariable() ) {
							if ( $contrat_instance->isQuantiteMultiple() ) {
								$type = 'quant_var_multi';
							} else {
								$type = 'quant_var';
							}
						} else {
							if ( $contrat_instance->isQuantiteMultiple() ) {
								$type = 'quant_fix_multi';
							} else {
								$type = 'quant_fix';
							}
						}
					}

					$types = [
						'quant_fix'       => 'Quantités fixes',
						'quant_fix_multi' => 'Quantités fixes multiples',
						'quant_var'       => 'Quantités variables',
						'quant_var_multi' => 'Quantités variables multiples',
						'panier_var'      => 'Paniers variables',
					];
					ob_start();
					?>
                    <select id="amapress_quantite_type"
                            name="amapress_quantite_type"
                    ><?php
						tf_parse_select_options( $types, $type );
						?>
                    </select>
                    <p class="description"><strong>Fixes</strong> : si les quantités sont fixes (par ex,
                        petit, moyen, grand ; demi, entier...)<br/>
                        <strong>Variables</strong> : si les quantités peuvent être modulées (par ex, 1L,
                        1.5L, 3L...)<br/>
                        <strong>Multiple</strong> : si plusieurs quantités peuvent être choisies<br/>
                        <strong>Paniers variables</strong> : si les paniers sont spécifiques pour chacune
                        des distributions</p>
					<?php
					return ob_get_clean();
				},
				'save'     => function ( $post_id ) {
					if ( isset( $_POST['amapress_quantite_type'] ) ) {
						$amapress_quantite_type = $_POST['amapress_quantite_type'];
						delete_post_meta(
							$post_id,
							'amapress_contrat_instance_panier_variable'
						);
						switch ( $amapress_quantite_type ) {
							case 'quant_fix':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									0 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									0 );
								break;
							case 'quant_fix_multi':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									1 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									0 );
								break;
							case 'quant_var':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									0 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									1 );
								break;
							case 'quant_var_multi':
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi',
									1 );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable',
									1 );
								break;
							case 'panier_var':
								delete_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_multi' );
								delete_post_meta(
									$post_id,
									'amapress_contrat_instance_quantite_variable' );
								update_post_meta(
									$post_id,
									'amapress_contrat_instance_panier_variable',
									1
								);
								break;
						}

						return true;
					}
				},
				'desc'     => '
							
							'
			),
			'is_principal'          => array(
				'name'        => amapress__( 'Contrat principal' ),
				'type'        => 'checkbox',
				'show_column' => false,
				'required'    => true,
				'group'       => 'Statut',
				'desc'        => 'Obligatoire (Par ex : Contrat légumes)',
			),
			'liste_dates'           => array(
				'name'             => amapress__( 'Calendrier des distributions' ),
				'type'             => 'multidate',
				'required'         => true,
				'group'            => 'Distributions',
				'readonly'         => 'amapress_is_contrat_instance_readonly',
				'show_column'      => true,
				'column_value'     => 'dates_count',
				'desc'             => 'Sélectionner les dates de distribution fournies par le producteur',
				'show_dates_count' => true,
				'show_dates_list'  => true,
				'before_option'    =>
					function ( $option ) {
						$is_readonly = amapress_is_contrat_instance_readonly( $option );
						if ( ! TitanFrameworkOption::isOnNewScreen() ) {
							if ( $is_readonly ) {
								echo '<p>Pour annuler ou reporter une distribution déjà planifiée, veuillez modifier la date dans le panier correspondant via le menu Contenus/Paniers ou la liste de paniers ci-dessous</p>';
							} else {
								$val_id = $option->getID() . '-validate';
								echo '<p><input type="checkbox" id="' . $val_id . '" ' . checked( ! $is_readonly, true, false ) . ' /><label for="' . $val_id . '">Cocher cette case pour modifier les dates lors du renouvellement du contrat. 
<br />Pour annuler ou reporter une distribution déjà planifiée, veuillez modifier la date dans le panier correspondant via le menu Contenus/Paniers ou la liste de paniers ci-dessous</label></p>';
								echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $("#' . $val_id . '").change(function() {
        $liste_dates.multiDatesPicker("option", {disabled: !$(this).is(\':checked\')});
    });
    $liste_dates.multiDatesPicker("option", {disabled: ' . ( $is_readonly ? 'true' : 'false' ) . '});
});
//]]>
</script>';
							}
						}
					},
			),
			'rattrapage'            => array(
				'name'        => amapress__( 'Quantités de rattrapage' ),
				'type'        => 'custom',
				'group'       => 'Distributions',
				'readonly'    => 'amapress_is_contrat_instance_readonly',
				'show_column' => false,
				'bare'        => true,
				'show_on'     => 'edit-only',
				'column'      => function ( $post_id ) {
					$contrat    = AmapressContrat_instance::getBy( $post_id, true );
					$rattrapage = [];
					foreach ( $contrat->getRattrapage() as $r ) {
						$rattrapage[] = sprintf( '%s (%.1f)', date_i18n( 'd/m/Y', intval( $r['date'] ) ), $r['quantite'] );
					}

					return implode( ', ', $rattrapage );
				},
				'custom'      => function ( $post_id ) {
					$contrat    = AmapressContrat_instance::getBy( $post_id, true );
					$rattrapage = [];
					$i          = 0;
					foreach ( $contrat->getRattrapage() as $r ) {
						$rattrapage[] = $r;
						$i ++;
					}
					while ( $i < 6 ) {
						$rattrapage[] = [
							'date'     => 0,
							'quantite' => 1,
						];
						$i ++;
					}

					$dates      = [];
					$dates["0"] = '--Date--';
					foreach ( $contrat->getListe_dates() as $date ) {
						$dates[ strval( $date ) ] = date_i18n( 'd/m/Y', $date );
					}

					ob_start();
					echo '<tr><td colspan="2" style="margin: 0; padding: 0">
                            <p><strong>Quantités de rattrapage</strong></p>';
					echo '<table id="quant_rattrapage" style="width: 100%"><tbody>';
					$i = 0;
					foreach ( $rattrapage as $r ) {
						?>
                        <tr>
                            <td>
                                <select id="<?php echo "amapress_quantite_rattrapage-date-$i"; ?>"
                                        name="<?php echo "amapress_quantite_rattrapage[$i][date]"; ?>"
                                ><?php
									tf_parse_select_options( $dates, $r['date'] );
									?>
                                </select>
                            </td>
                            <td>
                                <input id="<?php echo "amapress_quantite_rattrapage-date-$i"; ?>"
                                       name="<?php echo "amapress_quantite_rattrapage[$i][quantite]"; ?>"
                                       class="number positiveNumber"
                                       value="<?php echo $r['quantite']; ?>"
                                />
                            </td>
                        </tr>
						<?php
						$i ++;
					}
					echo '</tbody></table></td></tr>';

					return ob_get_clean();
				},
				'save'        => function ( $post_id ) {
					if ( isset( $_POST['amapress_quantite_rattrapage'] ) ) {
						$amapress_quantite_rattrapage = $_POST['amapress_quantite_rattrapage'];
						foreach ( $amapress_quantite_rattrapage as $i => $r ) {
							if ( "0" == $r['date'] ) {
								unset( $amapress_quantite_rattrapage[ $i ] );
							}
						}
						update_post_meta(
							$post_id,
							'amapress_contrat_instance_rattrapage',
							$amapress_quantite_rattrapage );

						return true;
					}
				}
			),
			'les-paniers'           => array(
				'name'              => amapress__( 'Paniers' ),
				'group'             => 'Distributions',
				'desc'              => 'Paniers de ce contrat',
				'show_column'       => false,
				'show_on'           => 'edit-only',
//							'bare'            => true,
				'include_columns'   => array(
					'title',
					'amapress_panier_status',
					'amapress_panier_date_subst',
				),
				'datatable_options' => array(
					'ordering' => false,
					'paging'   => true,
				),
				'type'              => 'related-posts',
				'query'             => 'post_type=amps_panier&amapress_contrat_inst=%%id%%',
			),
//						'quantite_multi'        => array(
//							'name'        => amapress__( 'Quantités multiples' ),
//							'type'        => 'checkbox',
//							'group'       => 'Gestion',
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'default'     => 1,
//							'desc'        => 'Cocher cette case si les quantités ',
//						),
//						'panier_variable'       => array(
//							'name'        => amapress__( 'Paniers personnalisés' ),
//							'type'        => 'checkbox',
//							'group'       => 'Gestion',
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'desc'        => 'Cocher cette case si les paniers sont spécifiques pour chacun des adhérents',
//						),
//						'quantite_variable'     => array(
//							'name'        => amapress__( 'Quantités personnalisées' ),
//							'type'        => 'checkbox',
//							'group'       => 'Gestion',
//							'readonly'    => 'amapress_is_contrat_instance_readonly',
//							'required'    => true,
//							'show_column' => false,
//							'desc'        => 'Cocher cette case si les quantités peuvent être modulées (par ex, 1L, 1.5L, 3L...)',
//						),
			'paiements'             => array(
				'name'     => amapress__( 'Nombres de chèques' ),
				'type'     => 'multicheck',
				'desc'     => 'Sélectionner le nombre de règlements autorisés par le producteur',
				'group'    => 'Paiements',
				'readonly' => 'amapress_is_contrat_instance_readonly',
				'required' => true,
				'options'  => array(
					'1'  => '1 chèque',
					'2'  => '2 chèques',
					'3'  => '3 chèques',
					'4'  => '4 chèques',
					'5'  => '5 chèques',
					'6'  => '6 chèques',
					'7'  => '7 chèques',
					'8'  => '8 chèques',
					'9'  => '9 chèques',
					'10' => '10 chèques',
					'11' => '11 chèques',
					'12' => '12 chèques',
				)
			),
			'liste_dates_paiements' => array(
				'name'             => amapress__( 'Calendrier des remises de chèques' ),
				'type'             => 'multidate',
				'readonly'         => 'amapress_is_contrat_instance_readonly',
				'required'         => true,
				'group'            => 'Paiements',
				'show_column'      => false,
				'show_dates_count' => true,
				'show_dates_list'  => true,
				'desc'             => 'Sélectionner les dates auxquelles le producteur souhaite recevoir les chèques',
			),
//                        'list_quantites' => array(
//                            'name' => amapress__('Quantités'),
//                            'type' => 'show-posts',
//                            'desc' => 'Quantités',
//                            'group' => 'Distributions',
//                            'post_type' => 'amps_contrat_quant',
//                            'parent' => 'amapress_contrat_quantite_contrat_instance',
//                        ),
			'date_debut'            => array(
				'name'          => amapress__( 'Début du contrat' ),
				'type'          => 'date',
				'group'         => 'Gestion',
				'required'      => true,
				'desc'          => 'Date de début du contrat',
				'import_key'    => true,
				'top_filter'    => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'readonly'      => 'amapress_is_contrat_instance_readonly',
				'before_option' =>
					function ( $option ) {
						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
							echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $date_debut = $("#amapress_contrat_instance_date_debut");
    var $date_fin = $("#amapress_contrat_instance_date_fin");
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $date_debut.change(function() {
        $liste_dates.multiDatesPicker("option", {minDate: $(this).val()});
        $date_fin.datepicker("option","minDate", $date_debut.val());
    });
    $liste_dates.multiDatesPicker("option", {minDate: $date_debut.val()});
    $date_fin.datepicker("option","minDate", $date_debut.val());
});
//]]>
</script>';
						}
					},
			),
			'date_fin'              => array(
				'name'          => amapress__( 'Fin du contrat' ),
				'type'          => 'date',
				'group'         => 'Gestion',
				'required'      => true,
				'desc'          => 'Date de fin du contrat',
				'import_key'    => true,
				'readonly'      => 'amapress_is_contrat_instance_readonly',
				'before_option' =>
					function ( $option ) {
						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
							echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $date_debut = $("#amapress_contrat_instance_date_debut");
    var $date_fin = $("#amapress_contrat_instance_date_fin");
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $date_fin.on("change", function() {
        $liste_dates.multiDatesPicker("option", {maxDate: $(this).val()});
        $date_debut.datepicker("option","maxDate", $date_fin.val());
    });
    $liste_dates.multiDatesPicker("option", {maxDate: $date_fin.val()});
    $date_debut.datepicker("option","maxDate", $date_fin.val());
});
//]]>
</script>';
						}
					},
			),
			'lieux'                 => array(
				'name'       => amapress__( 'Lieux' ),
				'type'       => 'multicheck-posts',
				'post_type'  => 'amps_lieu',
				'group'      => 'Gestion',
				'required'   => true,
				'desc'       => 'Lieux de distribution',
				'select_all' => true,
				'readonly'   => 'amapress_is_contrat_instance_readonly',
				'orderby'    => 'post_title',
				'order'      => 'ASC',
				'top_filter' => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Tous les lieux'
				),
			),
			'status'                => array(
				'name'    => amapress__( 'Statut' ),
				'type'    => 'custom',
				'column'  => array( 'AmapressContrats', "contratStatus" ),
				'custom'  => array( 'AmapressContrats', "contratStatus" ),
				'group'   => 'Statut',
				'save'    => null,
				'desc'    => 'Statut',
				'show_on' => 'edit-only',
			),
			'ended'                 => array(
				'name'        => amapress__( 'Clôturer' ),
				'type'        => 'checkbox',
				'group'       => 'Statut',
				'desc'        => 'Cocher cette case lorsque le contrat est terminé, penser à le renouveler d\'abord',
				'show_on'     => 'edit-only',
				'show_column' => false,
			),
			'max_adherents'         => array(
				'name'     => amapress__( 'Nombre maximum d\'amapiens' ),
				'type'     => 'number',
				'group'    => 'Information',
				'required' => true,
				'desc'     => 'Nombre maximum d\'amapiens',
			),
			'quant_editor'          => array(
				'name'        => amapress__( 'Quantités' ),
				'type'        => 'custom',
				'group'       => 'Gestion',
				'column'      => null,
				'custom'      => 'amapress_get_contrat_quantite_editor',
				'save'        => 'amapress_save_contrat_quantite_editor',
				'show_on'     => 'edit-only',
				'show_column' => false,
				'bare'        => true,
//                'desc' => 'Quantités',
			),
			'inscriptions'          => array(
				'name'        => amapress__( 'Inscriptions' ),
				'show_column' => true,
				'show_table'  => false,
				'empty_text'  => 'Pas encore d\'inscriptions',
//				'include_columns' => array(
//					'title',
//					'amapress_adhesion_quantite',
//					'amapress_adhesion_lieu',
//					'amapress_adhesion_date_debut',
//					'amapress_total_amount',
//				),
				'type'        => 'related-posts',
				'query'       => 'post_type=amps_adhesion&amapress_contrat_inst=%%id%%',
			),
			'self_subscribe'        => array(
				'name'        => amapress__( 'Inscriptions en ligne' ),
				'type'        => 'checkbox',
				'group'       => 'Pré-inscription en ligne',
				'desc'        => 'Activer',
				'show_column' => false,
			),
			'date_ouverture'        => array(
				'name'          => amapress__( 'Ouverture des inscriptions' ),
				'type'          => 'date',
				'group'         => 'Pré-inscription en ligne',
				'required'      => true,
				'desc'          => 'Date d\'ouverture des inscriptions en ligne',
				'import_key'    => true,
				'readonly'      => 'amapress_is_contrat_instance_readonly',
				'before_option' =>
					function ( $option ) {
						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
							echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $date_ouverture = $("#amapress_contrat_instance_date_ouverture");
    var $date_cloture = $("#amapress_contrat_instance_date_cloture");
    $date_ouverture.change(function() {
        $date_cloture.datepicker("option","minDate", $(this).val());
    });
    $date_cloture.datepicker("option","minDate", $date_ouverture.val());
});
//]]>
</script>';
						}
					},
			),
			'date_cloture'          => array(
				'name'          => amapress__( 'Clôture des inscriptions' ),
				'type'          => 'date',
				'group'         => 'Pré-inscription en ligne',
				'required'      => true,
				'desc'          => 'Date de clôture des inscriptions en ligne',
				'import_key'    => true,
				'readonly'      => 'amapress_is_contrat_instance_readonly',
				'before_option' =>
					function ( $option ) {
						if ( ! amapress_is_contrat_instance_readonly( $option ) ) {
							echo '<script type="text/javascript">
//<![CDATA[
jQuery(function($) {
    var $date_ouverture = $("#amapress_contrat_instance_date_ouverture");
    var $date_cloture = $("#amapress_contrat_instance_date_cloture");
    $date_cloture.on("change", function() {
        $date_ouverture.datepicker("option","maxDate", $date_cloture.val());
    });
    $date_ouverture.datepicker("option","maxDate", $date_cloture.val());
});
//]]>
</script>';
						}
					},
			),
			'min_engagement'        => array(
				'name'        => amapress__( 'Engagement minimal' ),
				'type'        => 'number',
				'group'       => 'Pré-inscription en ligne',
				'required'    => true,
				'show_column' => false,
				'desc'        => 'Montant minimal d\'engagement',
			),
			'min_cheque_amount'     => array(
				'name'        => amapress__( 'Montant minimal chèque' ),
				'type'        => 'number',
				'group'       => 'Pré-inscription en ligne',
				'required'    => true,
				'show_column' => false,
				'desc'        => 'Montant minimal du plus petit chèque',
			),
//			'contrat'           => array(
//				'name'       => amapress__( 'Info contrat en ligne' ),
//				'type'       => 'editor',
//				'group'      => 'Pré-inscription en ligne',
//				'desc'       => 'Configurer les informations supplémentaires à afficher lors de la souscription en ligne',
//				'wpautop'    => false,
//				'searchable' => true,
//				'readonly'   => 'amapress_is_contrat_instance_readonly',
//			),
		),
	);
	$entities['contrat_quantite'] = array(
		'internal_name'    => 'amps_contrat_quant',
		'singular'         => amapress__( 'Contrat quantité' ),
		'plural'           => amapress__( 'Contrats quantités' ),
		'public'           => 'adminonly',
		'thumb'            => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'contrat_quantites',
		'quick_edit'       => false,
		'fields'           => array(
			'contrat_instance' => array(
				'name'              => amapress__( 'Contrat' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'required'          => true,
				'csv_required'      => true,
				'desc'              => 'Contrat',
				'import_key'        => true,
				'autoselect_single' => true,
				'searchable'        => true,
				'custom_csv_sample' => function ( $option, $arg ) {
					$ret = [];
					foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
						$ret[ $c->ID ] = $c->getTitle();
					}

					return $ret;
				}
			),
			'code'             => array(
				'name'         => amapress__( 'Code' ),
				'type'         => 'text',
				'csv_required' => true,
				'desc'         => 'Code',
				'import_key'   => true,
				'searchable'   => true,
			),
			'prix_unitaire'    => array(
				'name'         => amapress__( 'Prix unitaire' ),
				'type'         => 'price',
				'required'     => true,
				'csv_required' => true,
				'unit'         => '€',
				'desc'         => 'Prix unitaire',
			),
			//que distrib
			'quantite'         => array(
				'name' => amapress__( 'Facteur quantité' ),
				'type' => 'float',
//                'required' => true,
				'desc' => 'Quantité',
//                'import_key' => true,
			),
			//commandes
			'produits'         => array(
				'name'         => amapress__( 'Produits' ),
				'type'         => 'select-posts',
				'post_type'    => AmapressProduit::INTERNAL_POST_TYPE,
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'csv'          => false,
			),
			'unit'             => array(
				'name'    => amapress__( 'Unité' ),
				'type'    => 'select',
				'options' => array(
					'unit' => 'A l\'unité',
					'kg'   => 'Au kg',
					'l'    => 'Au litre',
				),
			),
			'quantite_config'  => array(
				'name'              => amapress__( 'Config quantités disponibles' ),
				'type'              => 'text',
				'csv'               => false,
				'custom_csv_sample' => function ( $option, $arg ) {
					$ret = [];

					$ret[] = '1;3;5';
					$ret[] = '1-3:0.5';
					$ret[] = '1-3;5;10';

					return $ret;
				}
			),
			'avail_from'       => array(
				'name' => amapress__( 'Dispo de' ),
				'type' => 'date',
			),
			'avail_to'         => array(
				'name' => amapress__( 'Dispo jusqu\'à' ),
				'type' => 'date',
			),
		),
	);
//    $entities['contrat_paiement'] = array(
//        'internal_name' => 'amps_contrat_pmt',
//        'singular' => amapress__('Contrat paiment'),
//        'plural' => amapress__('Contrats paiements'),
//        'public' => 'adminonly',
//        'show_in_menu' => false,
//        'special_options' => array(),
//        'slug' => 'contrat_paiements',
//        'fields' => array(
//            'contrat_instance' => array(
//                'name' => amapress__('Contrat'),
//                'type' => 'select-posts',
//                'post_type' => 'amps_contrat_inst',
//                'required' => true,
//                'desc' => 'Contrat',
//            ),
//            'liste_dates' => array(
//                'name' => amapress__('Dates'),
//                'type' => 'custom',
//                'custom' => array('AmapressContrats', "displayPaiementListeDates"),
//                'save' => array('AmapressContrats', "savePaiementListeDates"),
//                'required' => true,
//                'desc' => 'Dates',
//            ),
//        ),
//    );
	return $entities;
}

add_filter( 'amapress_import_adhesion_multi', 'amapress_import_adhesion_multi', 5, 4 );
function amapress_import_adhesion_multi( $postmulti, $postdata, $postmeta, $posttaxo ) {
	foreach ( $postmulti as $k => $v ) {
		$postmulti[ $k ] = amapress_resolve_contrat_quantite_ids( $k, $v );
	}

	return $postmulti;
}

add_filter( 'amapress_get_edit_url_for_contrat_quantite', 'amapress_get_edit_url_for_contrat_quantite' );
function amapress_get_edit_url_for_contrat_quantite( $url ) {
	return admin_url( 'edit.php?post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE );
}

add_filter( 'amapress_import_adhesion_apply_default_values_to_posts_meta', 'amapress_import_adhesion_apply_default_values_to_posts_meta' );
function amapress_import_adhesion_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] )
	     && empty( $postmeta['amapress_adhesion_contrat_instance'] ) ) {
		$postmeta['amapress_adhesion_contrat_instance'] = $_REQUEST['amapress_import_adhesion_default_contrat_instance'];
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_lieu'] )
	     && empty( $postmeta['amapress_adhesion_lieu'] ) ) {
		$postmeta['amapress_adhesion_lieu'] = $_REQUEST['amapress_import_adhesion_default_lieu'];
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_date_debut'] )
	     && empty( $postmeta['amapress_adhesion_date_debut'] ) ) {
		$vals                                     = AmapressEntities::getPostFieldsValidators();
		$val                                      = $vals['amapress_adhesion_date_debut'];
		$postmeta['amapress_adhesion_date_debut'] = call_user_func( $val, $_REQUEST['amapress_import_adhesion_default_date_debut'] );
	}

//	$contrat_instance = AmapressContrat_instance::getBy( $postmeta['amapress_adhesion_contrat_instance'] );
//	if ( $postmeta['amapress_adhesion_date_debut'] < $contrat_instance->getDate_debut()
//	     || $postmeta['amapress_adhesion_date_debut'] > $contrat_instance->getDate_fin() ) {
//		$postmeta['amapress_adhesion_date_debut'] = $contrat_instance->getDate_debut();
//	}
//	$postmeta['amapress_adhesion_status'] = 'confirmed';

	return $postmeta;
}

add_filter( 'amapress_import_contrat_quantite_apply_default_values_to_posts_meta', 'amapress_import_contrat_quantite_apply_default_values_to_posts_meta' );
function amapress_import_contrat_quantite_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_contrat_quantite_default_contrat_instance'] ) && empty( $postmeta['amapress_contrat_quantite_contrat_instance'] ) ) {
		$postmeta['amapress_contrat_quantite_contrat_instance'] = $_REQUEST['amapress_import_contrat_quantite_default_contrat_instance'];
	}

	if ( empty( $postmeta['amapress_contrat_quantite_quantite'] ) ) {
		$postmeta['amapress_contrat_quantite_quantite'] = 1;
	}
//    if (empty($postmeta['amapress_contrat_quantite_unit']))
//        $postmeta['amapress_contrat_quantite_quantite'] = 'unit';

	return $postmeta;
}

add_filter( 'amapress_import_adhesion_meta', 'amapress_import_adhesion_meta', 5, 4 );
function amapress_import_adhesion_meta( $postmeta, $postdata, $posttaxo, $postmulti ) {
	if ( ! empty( $postmulti ) ) {
		return $postmeta;
	}

	if ( is_wp_error( $postmeta['amapress_adhesion_contrat_instance'] ) || is_wp_error( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
		return $postmeta;
	}

	$postmeta = apply_filters( "amapress_import_adhesion_apply_default_values_to_posts_meta", $postmeta, $postdata );
	$postmeta = apply_filters( "amapress_import_apply_default_values_to_posts_meta", $postmeta, $postdata );

	if ( empty( $postmeta['amapress_adhesion_contrat_instance'] ) ) {
		return new WP_Error( 'ignore_contrat', "Colonne contrat vide. La ligne sera ignorée." );
	}

	$quants = [];
	foreach ( $postmeta as $k => $v ) {
		if ( strpos( $k, 'contrat_quant_' ) === 0 ) {
			$quant_id = intval( substr( $k, 14 ) );
			$quant    = AmapressContrat_quantite::getBy( $quant_id );
			if ( null == $quant ) {
				return new WP_Error( 'cannot_find_quant', "Impossible de résoudre la quantité {$quant_id}" );
			}

			if ( Amapress::toBool( $v ) ) {
				$quants[] = $quant->getCode();
			} else {
				$quants[] = $v . ' ' . $quant->getCode();
			}
			unset( $postmeta[ $k ] );
		}
	}
	if ( ! empty( $quants ) ) {
		$postmeta['amapress_adhesion_contrat_quantite'] = implode( ', ', $quants );
	}

	if ( empty( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
		return new WP_Error( 'ignore_contrat_quantite', "Colonne quantité vide. La ligne sera ignorée." );
	}

	$contrat_instance_id = Amapress::resolve_post_id( $postmeta['amapress_adhesion_contrat_instance'], AmapressContrat_instance::INTERNAL_POST_TYPE );
	if ( empty( $contrat_instance_id ) || $contrat_instance_id <= 0 ) {
		return new WP_Error( 'cannot_find_contrat', "Impossible de trouver le contrat '{$postmeta['amapress_adhesion_contrat_instance']}'" );
	}

	$postmeta['amapress_adhesion_contrat_instance'] = $contrat_instance_id;

	$ids = amapress_resolve_contrat_quantite_ids( $contrat_instance_id, $postmeta['amapress_adhesion_contrat_quantite'] );
	if ( is_wp_error( $ids ) ) {
		return $ids;
	}

	$postmeta['amapress_adhesion_contrat_quantite']         = array_map(
		function ( $id ) {
			return $id['id'];
		}, $ids );
	$postmeta['amapress_adhesion_contrat_quantite_factors'] = array_combine(
		array_map(
			function ( $id ) {
				return $id['id'];
			}, $ids ),
		array_map(
			function ( $id ) {
				return $id['quant'];
			}, $ids )
	);

	$postmeta['amapress_adhesion_status'] = 'confirmed';

	return $postmeta;
}

add_filter( 'amapress_import_posts_meta', 'amapress_import_adhesion_meta2', 15, 4 );
function amapress_import_adhesion_meta2( $postmeta, $postdata, $posttaxo, $post_type ) {
	if ( $post_type != AmapressAdhesion::POST_TYPE ) {
		return $postmeta;
	}
	if ( ! empty( $postmulti ) ) {
		return $postmeta;
	}

	if ( is_wp_error( $postmeta ) ) {
		return $postmeta;
	}

	if ( is_wp_error( $postmeta['amapress_adhesion_contrat_instance'] )
	     || is_wp_error( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
		return $postmeta;
	}

	$contrat_instance = AmapressContrat_instance::getBy( $postmeta['amapress_adhesion_contrat_instance'] );
	$date_debut       = Amapress::start_of_day( $postmeta['amapress_adhesion_date_debut'] );
	if ( $date_debut < Amapress::start_of_day( $contrat_instance->getDate_debut() )
	     || $date_debut > Amapress::start_of_day( $contrat_instance->getDate_fin() ) ) {
		$dt            = date_i18n( 'd/m/Y', $postmeta['amapress_adhesion_date_debut'] );
		$contrat_debut = date_i18n( 'd/m/Y', $contrat_instance->getDate_debut() );
		$contrat_fin   = date_i18n( 'd/m/Y', $contrat_instance->getDate_fin() );

		return new WP_Error( 'invalid_date', "La date de début $dt est en dehors des dates ($contrat_debut - $contrat_fin) du contrat '{$contrat_instance->getTitle()}'" );
	}
	$postmeta['amapress_adhesion_status'] = 'confirmed';

	return $postmeta;
}

function amapress_resolve_contrat_quantite_ids( $contrat_instance_id, $contrat_quantite_name ) {
	if ( is_string( $contrat_quantite_name ) ) {
		$contrat_quantite_name = trim( $contrat_quantite_name );
		if ( empty( $contrat_quantite_name ) ) {
			return null;
		}

		$id = amapress_resolve_contrat_quantite_id( $contrat_instance_id, $contrat_quantite_name );
		if ( $id ) {
			return [ $id ];
		}
	}

	$values = Amapress::get_array( $contrat_quantite_name );
	if ( ! is_array( $values ) ) {
		$values = array( $values );
	}

	$errors = array();
	$res    = array();
	foreach ( $values as $v ) {
//        $v = trim($v);
		$id = amapress_resolve_contrat_quantite_id( $contrat_instance_id, $v );
		if ( empty( $id ) ) {
			$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
			$url              = admin_url( "post.php?post=$contrat_instance_id&action=edit" );
			$errors[]         = "Valeur '$v' non valide pour '{$contrat_instance->getTitle()}' (Voir <$url>)";
		} else {
			$res[] = $id;
		}
	}
	if ( ! empty( $errors ) ) {
		return new WP_Error( 'cannot_parse', implode( ' ; ', $errors ) );
	}

//	if ( count( $res ) == 1 ) {
	return $res;
//	} else {
//		return $res;
//	}
}

//add_filter('amapress_resolve_contrat_quantite_id','amapress_resolve_contrat_quantite_id', 10, 2);
function amapress_resolve_contrat_quantite_id( $contrat_instance_id, $contrat_quantite_name ) {
	$quants = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
	if ( ! empty( $quants ) && count( $quants ) == 1 && Amapress::toBool( $contrat_quantite_name ) ) {
		$fquant                = from( $quants )->first();
		$contrat_quantite_name = $fquant->getCode();
	}
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
//    $cn = $contrat_quantite_name;
	$contrat_quantite_name = wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $contrat_quantite_name ) ) );
	if ( empty( $contrat_quantite_name ) ) {
		return null;
	}

	foreach ( $quants as $quant ) {
		if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getCode() ) ) ), $contrat_quantite_name ) === 0 ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getSlug() ) ) ), $contrat_quantite_name ) === 0 ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getTitle() ) ) ), $contrat_quantite_name ) === 0 ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		} else if ( str_replace( ',', '.', strval( $quant->getQuantite() ) ) == str_replace( ',', '.', $contrat_quantite_name ) ) {
			return [
				'id'    => $quant->ID,
				'quant' => 1,
			];
		}

		if ( $contrat_instance->isQuantiteVariable() ) {
			foreach ( $quant->getQuantiteOptions() as $raw => $fmt ) {
				if ( empty( $raw ) ) {
					continue;
				}
				foreach ( [ $raw, $fmt, $raw . ' ', $fmt . ' ', $raw . ' x ', $fmt . ' x ' ] as $prefix ) {
					if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $prefix . $quant->getCode() ) ) ), $contrat_quantite_name ) === 0 ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $prefix . $quant->getSlug() ) ) ), $contrat_quantite_name ) === 0 ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $prefix . $quant->getTitle() ) ) ), $contrat_quantite_name ) === 0 ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					} else if ( str_replace( ',', '.', strval( $quant->getQuantite() ) ) == str_replace( ',', '.', $contrat_quantite_name ) ) {
						return [
							'id'    => $quant->ID,
							'quant' => floatval( $raw ),
						];
					}
				}
			}
		}
	}
//    var_dump($contrat_quantite_name);
//    var_dump($cn);
//    die();
	return null;
}

function amapress_quantite_editor_line( AmapressContrat_instance $contrat_instance, $id, $title, $code, $description, $price, $unit, $quantite_conf, $from, $to, $quantite, $produits, $photo ) {
	if ( $contrat_instance->getModel() == null ) {
		return '';
	}
	$contrat_produits = array();
	foreach ( $contrat_instance->getModel()->getProducteur()->getProduits() as $prod ) {
		$contrat_produits[ $prod->ID ] = $prod->getTitle();
	}
	echo '<tr style="vertical-align: top">';
	echo "<td><input style='width: 100%' type='text' class='required' name='amapress_quant_data[$id][title]' placeholder='Intitulé' value='$title' /></td>";
	echo "<td><input style='width: 100%' type='text' class='required' name='amapress_quant_data[$id][code]' placeholder='Code' value='$code' /></td>";
	echo "<td><textarea style='width: 100%' class='' name='amapress_quant_data[$id][desc]' placeholder='Description'>{$description}</textarea></td>";
	echo "<td><input style='width: 100%' type='number' class='required number' name='amapress_quant_data[$id][price]' min='0' step='0.01' placeholder='Prix unitaire' value='$price' /></td>";
	echo "<td><input style='width: 100%' type='number' class='required number' name='amapress_quant_data[$id][quant]' min='0' step='0.01' placeholder='Facteur quantité' value='$quantite' /></td>";
	if ( $contrat_instance->isPanierVariable() || $contrat_instance->isQuantiteVariable() ) {
		echo "<td><select style='width: 100%' class='required' name='amapress_quant_data[$id][unit]'>";
		echo '<option value="">--Unité de prix--</option>';
		echo '<option ' . selected( 'unit', $unit, false ) . ' value="unit">pièce</option>';
		echo '<option ' . selected( 'kg', $unit, false ) . ' value="kg">kg</option>';
		echo '<option ' . selected( 'l', $unit, false ) . ' value="l">L</option>';
		echo '</select></td>';
		echo "<td><input style='width: 100%' type='text' class='text' name='amapress_quant_data[$id][quant_conf]' placeholder='Config' value='$quantite_conf' /></td>";
	}
	if ( $contrat_instance->isPanierVariable() ) {
		echo "<td><input style='width: 100%' type='text' class='input-date date' name='amapress_quant_data[$id][avail_from]' placeholder='Date début' value='$from' /></td>";
		echo "<td><input style='width: 100%' type='text' class='input-date date' name='amapress_quant_data[$id][avail_to]' placeholder='Date fin' value='$to' /></td>";
	}
	?>
    <td><select style='width: 100%' id="<?php echo 'amapress_quant_data[$id][produits]' ?>"
                name="<?php echo 'amapress_quant_data[$id][produits]'; ?>"
                class="quant-produit" multiple="multiple"
                data-placeholder="Produits associés">
			<?php
			tf_parse_select_options( $contrat_produits, $produits );
			?>
        </select>
    </td>
	<?php
//	echo "<td class='quant-upload'  style='border-top: 1pt solid #8c8c8c; border-collapse: collapse'>";
//	TitanFrameworkOptionUpload::echo_uploader( "amapress_quant_data[$id][photo]", $photo, '' );
//	echo "</td>";
	if ( amapress_can_delete_contrat_quantite( '', $id ) === true ) {
		echo "<td><span class='btn del-model-tab dashicons dashicons-dismiss' onclick='amapress_del_quant(this)'></span></td>";
	} else {
		echo "<td></td>";
	}
	echo '</tr>';
}

function amapress_get_contrat_quantite_editor( $contrat_instance_id ) {
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
	if ( $contrat_instance->getModel() == null ) {
		return '';
	}

	ob_start();
	?>
    <tr>
        <td colspan="2" style="margin: 0; padding: 0">
            <p><strong>Quantités/Taille des paniers :</strong></p>
            <p>Pour importer des Quantités/Taille de panier depuis un excel, veuillez utiliser <a
                        href="<?php echo admin_url( 'admin.php?page=amapress_import_page&tab=import_quant_paniers&amapress_import_contrat_quantite_default_contrat_instance=' . $contrat_instance->ID ); ?>"
                        target="_blank" class="button button-secondary">Import CSV</a> (en indiquant
                "<?php echo esc_html( $contrat_instance->getTitle() ); ?>" dans la colonne Contrat ou le choisissant
                comme contrat par défaut)</p>
            <input type="hidden" name="amapress_quant_data_contrat_instance_id"
                   value="<?php echo $contrat_instance_id; ?>"/>
            <table id="quant_editor_table" class="table" style="width: 100%">
                <thead>
                <tr>
                    <th>Intitulé</th>
                    <th style="width: 100px">Code</th>
                    <th title="Description">Desc.</th>
                    <th style="width: 50px">Prix</th>
                    <th style="width: 40px" title="Facteur quantité">Fact. quant.</th>
					<?php if ( $contrat_instance->isPanierVariable() || $contrat_instance->isQuantiteVariable() ) { ?>
                        <th style="width: 60px">Unité</th>
                        <th style="width: 70px">Quantités config</th>
					<?php } ?>
					<?php if ( $contrat_instance->isPanierVariable() ) { ?>
                        <th style="width: 80px">Dispo de</th>
                        <th style="width: 80px"> - à</th>
					<?php } ?>
                    <th>Produits</th>
                    <!--            <th style="width: 30px">Photo</th>-->
                    <th style="width: 30px"></th>
                </tr>
                </thead>
                <tbody>
				<?php
				foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance_id ) as $quant ) {
					$id   = $quant->ID;
					$tit  = esc_attr( $quant->getTitle() );
					$q    = esc_attr( $quant->getQuantite() );
					$c    = esc_attr( $quant->getCode() );
					$pr   = esc_attr( $quant->getPrix_unitaire() );
					$qc   = esc_attr( $quant->getQuantiteConfig() );
					$desc = esc_textarea( stripslashes( $quant->getDescription() ) );
					$af   = esc_attr( $quant->getAvailFrom() ? date_i18n( TitanFrameworkOptionDate::$default_date_format, intval( $quant->getAvailFrom() ) ) : null );
					$at   = esc_attr( $quant->getAvailTo() ? date_i18n( TitanFrameworkOptionDate::$default_date_format, intval( $quant->getAvailTo() ) ) : null );

					amapress_quantite_editor_line( $contrat_instance, $id, $tit, $c, $desc, $pr, $quant->getPriceUnit(),
						$qc, $af, $at, $q, implode( ',', $quant->getProduitsIds() ), get_post_thumbnail_id( $quant->ID ) );
				}
				?>
                </tbody>
            </table>
            <p><span class="btn add-model dashicons dashicons-plus-alt"
                     onclick="amapress_add_quant(this)"></span> Ajouter une quantité</p>
        </td>
    </tr>
	<?php
	$contents = ob_get_clean();

	ob_start();
	amapress_quantite_editor_line( $contrat_instance, '%%id%%', '', '', '', 0, 0,
		'', null, null, 0, '', '' );

	$new_row = ob_get_clean();

	$new_row  = json_encode( array( 'html' => $new_row ) );
	$contents .= "<script type='text/javascript'>//<![CDATA[
    jQuery(function() {
        amapress_quant_load_tags();
    });
    function amapress_quant_load_tags() {
        jQuery('.quant-produit').select2({
            allowClear: true,
              escapeMarkup: function(markup) {
        return markup;
    },
              templateResult: function(data) {
        return jQuery('<span>'+data.text+'</span>');
    },
              templateSelection: function(data) {
        return jQuery('<span>'+data.text+'</span>');
    }
        });
    }
    function amapress_add_quant(e) {
        var max = jQuery(e).data('max') || 0;
        max -= 1;
        jQuery(e).data('max', max);
        var html = {$new_row}['html'];
        html = html.replace(/%%id%%/g, max);
        jQuery('#quant_editor_table tbody').append(jQuery(html));
        amapress_quant_load_tags();
    };
    function amapress_del_quant(e) {
        if (!confirm('Voulez-vous vraiment supprimer cette quantité ?')) return;
        jQuery(e).closest('tr').remove();
    };
    //]]>
</script>";

	return $contents;
}

function amapress_save_contrat_quantite_editor( $contrat_instance_id ) {
	if ( isset( $_POST['amapress_quant_data'] ) && isset( $_POST['amapress_quant_data_contrat_instance_id'] ) ) {

		$quants     = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
		$quants_ids = array_map( function ( $q ) {
			return $q->ID;
		}, $quants );

		foreach ( array_diff( $quants_ids, array_keys( $_POST['amapress_quant_data'] ) ) as $qid ) {
			wp_delete_post( $qid );
		}
		foreach ( $_POST['amapress_quant_data'] as $quant_id => $quant_data ) {
			$quant_id = intval( $quant_id );
			$my_post  = array(
				'post_title'   => $quant_data['title'],
				'post_type'    => AmapressContrat_quantite::INTERNAL_POST_TYPE,
				'post_content' => '',
				'post_status'  => get_post_status( $contrat_instance_id ),
				'meta_input'   => array(
					'amapress_contrat_quantite_contrat_instance' => $contrat_instance_id,
					'amapress_contrat_quantite_prix_unitaire'    => $quant_data['price'],
					'amapress_contrat_quantite_code'             => ! empty( $quant_data['code'] ) ? $quant_data['code'] : $quant_data['title'],
					'amapress_contrat_quantite_description'      => $quant_data['desc'],
					'amapress_contrat_quantite_quantite_config'  => isset( $quant_data['quant_conf'] ) ? $quant_data['quant_conf'] : null,
					'amapress_contrat_quantite_unit'             => isset( $quant_data['unit'] ) ? $quant_data['unit'] : null,
					'amapress_contrat_quantite_produits'         => isset( $quant_data['produits'] ) ? $quant_data['produits'] : null,
					'amapress_contrat_quantite_avail_from'       => ! empty( $quant_data['avail_from'] ) ? TitanEntity::to_date( $quant_data['avail_from'] ) : null,
					'amapress_contrat_quantite_avail_to'         => ! empty( $quant_data['avail_to'] ) ? TitanEntity::to_date( $quant_data['avail_to'] ) : null,
					'amapress_contrat_quantite_quantite'         => isset( $quant_data['quant'] ) ? $quant_data['quant'] : null,
					'_thumbnail_id'                              => isset( $quant_data['photo'] ) ? $quant_data['photo'] : null,
				),
			);
			if ( $quant_id < 0 ) {
				wp_insert_post( $my_post );
			} else {
				$my_post['ID'] = $quant_id;
//                $my_post['post_status'] = 'publish';
				$r = wp_update_post( $my_post );
			}
		}
		unset( $_POST['amapress_quant_data'] );
	}
}

add_filter( 'amapress_can_delete_contrat', 'amapress_can_delete_contrat', 10, 2 );
function amapress_can_delete_contrat( $can, $post_id ) {
	return count( AmapressContrats::get_all_contrat_instances_by_contrat_ids( $post_id ) ) == 0;
}

add_filter( 'amapress_can_delete_contrat_instance', 'amapress_can_delete_contrat_instance', 10, 2 );
function amapress_can_delete_contrat_instance( $can, $post_id ) {
	return count( AmapressContrats::get_all_adhesions( $post_id ) ) == 0;
}

add_filter( 'amapress_can_delete_contrat_quantite', 'amapress_can_delete_contrat_quantite', 10, 2 );
function amapress_can_delete_contrat_quantite( $can, $post_id ) {
	return count( AmapressContrats::get_all_adhesions( null, $post_id ) ) == 0;
}

add_action( 'amapress_row_action_contrat_instance_renew', 'amapress_row_action_contrat_instance_renew' );
function amapress_row_action_contrat_instance_renew( $post_id ) {
	$contrat_inst         = AmapressContrat_instance::getBy( $post_id );
	$new_contrat_instance = $contrat_inst->cloneContrat();
	if ( ! $new_contrat_instance ) {
		wp_die( 'Une erreur s\'est produit lors du renouvèlement du contrat. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_renew_same_period', 'amapress_row_action_contrat_instance_renew_same_period' );
function amapress_row_action_contrat_instance_renew_same_period( $post_id ) {
	$contrat_inst         = AmapressContrat_instance::getBy( $post_id );
	$new_contrat_instance = $contrat_inst->cloneContrat( true, true, true );
	if ( ! $new_contrat_instance ) {
		wp_die( 'Une erreur s\'est produit lors du renouvèlement du contrat. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_contrat_instance_clone', 'amapress_row_action_contrat_instance_clone' );
function amapress_row_action_contrat_instance_clone( $post_id ) {
	$contrat_inst         = AmapressContrat_instance::getBy( $post_id );
	$new_contrat_instance = $contrat_inst->cloneContrat( true, false );
	if ( ! $new_contrat_instance ) {
		wp_die( 'Une erreur s\'est produit lors de la duplication du contrat. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit" ) );
}

/** @param TitanFrameworkOption $option */
function amapress_is_contrat_instance_readonly( $option ) {
	if ( isset( $_REQUEST['adv'] ) ) {
		return false;
	}
	$referer = parse_url( wp_get_referer() );
	if ( isset( $referer['query'] ) ) {
		parse_str( $referer['query'], $path );
		if ( ( isset( $path['adv'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) ) {
			return false;
		}
	}

	$contrat_instance_id = $option->getPostID();
	if ( ! $contrat_instance_id ) {
		return false;
	}
	$adhs = AmapressContrats::get_active_adhesions( $contrat_instance_id );

	return ! empty( $adhs );
}
