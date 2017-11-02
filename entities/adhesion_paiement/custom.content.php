<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'manage_users_columns', 'adhesion_paiements_manage_users_columns', 15 );
//add_filter('manage_edit-amps_adhesion_sortable_columns', 'amapress_adhesion_paiements_columns', 15);
function adhesion_paiements_manage_users_columns( $columns ) {
	$columns['adh_nb_contrats'] = 'Contrats';

	if ( isset( $_GET['page'] ) ) {
		if ( $_GET['page'] == 'adhesion_paiements' ) {
			unset( $columns['pw_user_status'] );
//            unset($columns['amapress_user_avatar']);
			unset( $columns['amapress_user_telephone2'] );
			unset( $columns['amapress_user_adresse_localized'] );
			unset( $columns['amapress_user_role_desc'] );
//            unset($columns['amapress_user_amap_roles']);
			unset( $columns['amapress_user_moyen'] );
			unset( $columns['role'] );
			unset( $columns['bbp_user_role'] );

//    $columns['amapress_total_amount'] = 'Montant';
			$terms = get_terms( 'amps_paiement_category',
				array(
					'taxonomy'   => 'amps_paiement_category',
					'hide_empty' => false,
				) );
//    $adhesion_tax_id = Amapress::getOption('adhesion_contrat_term');
//    $tax = get_term_by('id', $adhesion_tax_id, 'amps_paiement_category');
//    $columns[$tax->slug] = $tax->name;
			foreach ( $terms as $tax ) {
//        if ($tax->term_id == $adhesion_tax_id) continue;

				$columns[ $tax->slug ] = $tax->name;
			}
		} else if ( $_GET['page'] == 'contrat_paiements' ) {
			unset( $columns['pw_user_status'] );
//            unset($columns['amapress_user_avatar']);
			unset( $columns['amapress_user_telephone2'] );
			unset( $columns['amapress_user_adresse_localized'] );
			unset( $columns['amapress_user_role_desc'] );
//            unset($columns['amapress_user_amap_roles']);
			unset( $columns['amapress_user_moyen'] );
			unset( $columns['role'] );
			unset( $columns['bbp_user_role'] );

			$contrat_id = null;
			if ( isset( $_GET['amapress_contrat'] ) ) {
				$contrat_id = Amapress::resolve_post_id( $_GET['amapress_contrat'], AmapressContrat_instance::INTERNAL_POST_TYPE );
			}
			foreach ( AmapressContrats::get_active_contrat_instances( $contrat_id ) as $c ) {
				$columns[ 'contrat_amount_' . $c->getID() ] = $c->getTitle();
			}
		}
	}

	return $columns;
}

add_filter( 'manage_users_custom_column', 'amapress_paiements_column_display', 10, 3 );
function amapress_paiements_column_display( $output, $colname, $user_id ) {
	$adhesions = AmapressAdhesion::getAllActiveByUserId();
	if ( $colname == 'adh_nb_contrats' ) {
//        return count(AmapressContrats::get_user_active_contrat_instances($user_id));
		$cnt  = isset( $adhesions[ $user_id ] ) ? count( $adhesions[ $user_id ] ) : 0;
		$href = admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active&amapress_user=' . $user_id );

		return "<a href='$href'>$cnt</a>";
	}

	if ( strpos( $colname, 'contrat_amount_' ) === 0 ) {
//        $contrat_id = intval(substr($colname, 15));
//        $adhesions = AmapressContrats::get_user_active_adhesion($user_id, $contrat_id);
//        $expected_amount = 0;
//        foreach ($adhesions as $adh) {
//            $expected_amount += $adh->getTotalAmount();
//        }
//
//        $adhesion_ids = array_map('Amapress::to_id', $adhesions);
		$contrat_id = intval( substr( $colname, 15 ) );
		//$adhesions = AmapressContrats::get_user_active_adhesion($user_id, $contrat_id);
		$expected_amount = 0;
		/** @var AmapressAdhesion $adh */
		foreach ( ( isset( $adhesions[ $user_id ] ) ? $adhesions[ $user_id ] : array() ) as $adh ) {
			if ( $adh->getContrat_instanceId() != $contrat_id ) {
				continue;
			}
			$expected_amount += $adh->getTotalAmount();
		}

//        if (count($adhesion_ids) == 0) {
		if ( empty( $adhesions[ $user_id ] ) || $expected_amount == 0 ) {
			return '';
		} else {
			if ( count( $adhesions[ $user_id ] ) == 1 ) {
				$href = admin_url( "post.php?post={$adhesions[$user_id][0]->ID}&action=edit" );
			} else {
				$href = admin_url( "edit.php?post_type=amps_adhesion&amapress_contrat_inst={$contrat_id}&amapress_user=$user_id" );
			}
//            $args = array(
//                'post_type' => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
//                'posts_per_page' => -1,
//                'meta_query' => array(
//                    array(
//                        'key' => 'amapress_contrat_paiement_adhesion',
//                        'value' => $adhesion_ids,
//                        'compare' => 'IN',
//                        'type' => 'INT',
//                    ),
//                    array(
//                        'key' => 'amapress_contrat_paiement_status',
//                        'value' => array('received', 'bank'),
//                        'compare' => 'IN'
//                    ),
//                ),
//            );
//
//            $posts = get_posts($args);
//            $amount = 0;
//            foreach ($posts as $post) {
//                $p = new AmapressAmapien_paiement($post);
//                $amount += $p->getAmount();
//            }

			$all_paiements = AmapressAmapien_paiement::getAllActiveByAdhesionId();
			$amount        = 0;
			foreach ( $adhesions[ $user_id ] as $adh ) {
				if ( $adh->getContrat_instanceId() != $contrat_id ) {
					continue;
				}
//                $p = new AmapressAdhesion_paiement($post);
				if ( isset( $all_paiements[ $adh->getID() ] ) ) {
					/** @var AmapressAdhesion_paiement $p */
					foreach ( $all_paiements[ $adh->getID() ] as $p ) {
						$status = $p->getStatus();
						if ( 'received' != $status && 'bank' != $status ) {
							continue;
						}
						$amount += $p->getAmount( $colname );
					}
				}
			}

			if ( round( $amount ) == 0 ) {
				$status = array( 'icon' => 'dashicons-before dashicons-no-alt', 'status' => 'paiement-not-paid' );
			} else if ( round( $amount ) < $expected_amount ) {
				$status = array(
					'icon'   => 'dashicons-before dashicons-star-half',
					'status' => 'paiement-partial-paid'
				);
			} else if ( round( $amount ) > $expected_amount ) {
				$status = array( 'icon' => 'dashicons-before dashicons-arrow-up-alt', 'status' => 'paiement-too-paid' );
			} else {
				$status = array( 'icon' => 'dashicons-before dashicons-yes', 'status' => 'paiement-ok' );
			}
			$amount_fmt          = sprintf( '%.02f', $amount );
			$expected_amount_fmt = sprintf( '%.02f', $expected_amount );

			return "<a href='$href'><span class='{$status['status']}'><span class='{$status['icon']}'></span> $amount_fmt / $expected_amount_fmt</span></a>";
		}
	}

	if ( ! term_exists( $colname, 'amps_paiement_category' ) ) {
		return $output;
	}

//    $adh = new AmapressAdhesion($post_id);

//    $args = array(
//        'post_type' => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
//        'posts_per_page' => -1,
//        'meta_query' => array(
//            array(
//                'key' => 'amapress_adhesion_paiement_user',
//                'value' => $user_id,
//            ),
//            array(
//                'key' => 'amapress_adhesion_paiement_status',
//                'value' => array('received', 'bank'),
//                'compare' => 'IN'
//            ),
//        ),
//        'tax_query' => array(
//            array(
//                'taxonomy' => 'amps_paiement_category',
//                'field' => 'slug',
//                'terms' => $colname,
//                'operator' => 'IN'
//            ),
//        ),
//    );
//
//    $posts = get_posts($args);
//    $amount = 0;
//    foreach ($posts as $post) {
//        $p = new AmapressAdhesion_paiement($post);
//        $amount += $p->getAmount($colname);
//    }

	$term = get_term_by( 'slug', $colname, 'amps_paiement_category' );
	if ( empty( $term ) ) {
		return '';
	}

	$all_paiements = AmapressAdhesion_paiement::getAllActiveByUserId();
	$amount        = 0;
	if ( isset( $all_paiements[ $user_id ] ) ) {
		/** @var AmapressAdhesion_paiement $p */
		foreach ( $all_paiements[ $user_id ] as $p ) {
			$status = $p->getStatus();
			if ( 'received' != $status && 'bank' != $status ) {
				continue;
			}
			$amount += $p->getAmount( $colname );
		}
	}

	if ( Amapress::getOption( 'adhesion_amap_term' ) == $term->term_id || Amapress::getOption( 'adhesion_reseau_amap_term' ) == $term->term_id ) {
		if ( round( $amount ) == 0 ) {
			$status = array( 'icon' => 'dashicons-before dashicons-no-alt', 'status' => 'paiement-not-paid' );
		} else {
			$status = array( 'icon' => 'dashicons-before dashicons-yes', 'status' => 'paiement-ok' );
		}
	} else {
		$status = array( 'icon' => 'dashicons-before dashicons-none', 'status' => 'paiement-na' );
	}

	$amount_fmt = sprintf( '%.02f', $amount );
	if ( count( $all_paiements[ $user_id ] ) > 1 ) {
		return "<span class='{$status['status']}'><span class='{$status['icon']}'></span> $amount_fmt</span>";
	} else {
		$href = admin_url( 'post.php?post=' . $all_paiements[ $user_id ][0]->ID . '&action=edit' );

		return "<a href='$href'><span class='{$status['status']}'><span class='{$status['icon']}'></span> $amount_fmt</span></a>";
	}
}

add_filter( 'manage_users_custom_column_export', 'amapress_adhesion_paiements_column_export', 10, 3 );
function amapress_adhesion_paiements_column_export( $output, $colname, $user_id ) {
	$adhesions = AmapressAdhesion::getAllActiveByUserId();
	if ( $colname == 'adh_nb_contrats' ) {
//        return count(AmapressContrats::get_user_active_contrat_instances($user_id));
		return isset( $adhesions[ $user_id ] ) ? count( $adhesions[ $user_id ] ) : 0;
	}

	if ( strpos( $colname, 'contrat_amount_' ) === 0 ) {
		$contrat_id = intval( substr( $colname, 15 ) );
		//$adhesions = AmapressContrats::get_user_active_adhesion($user_id, $contrat_id);
		$expected_amount = 0;
		/** @var AmapressAdhesion $adh */
		foreach ( ( isset( $adhesions[ $user_id ] ) ? $adhesions[ $user_id ] : array() ) as $adh ) {
			if ( $adh->getContrat_instanceId() != $contrat_id ) {
				continue;
			}
			$expected_amount += $adh->getTotalAmount();
		}

//        $adhesion_ids = array_map('Amapress::to_id', $adhesions);
		if ( empty( $adhesions[ $user_id ] ) || $expected_amount == 0 ) {
			return '';
		} else {
//            $args = array(
//                'post_type' => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
//                'posts_per_page' => -1,
//                'meta_query' => array(
//                    array(
//                        'key' => 'amapress_contrat_paiement_adhesion',
//                        'value' => $adhesion_ids,
//                        'compare' => 'IN',
//                        'type' => 'INT',
//                    ),
//                    array(
//                        'key' => 'amapress_adhesion_paiement_status',
//                        'value' => array('received', 'bank'),
//                        'compare' => 'IN'
//                    ),
//                ),
//            );
//
//            $posts = get_posts($args);
			$all_paiements = AmapressAmapien_paiement::getAllActiveByAdhesionId();
			$amount        = 0;
			foreach ( $adhesions[ $user_id ] as $adh ) {
				if ( $adh->getContrat_instanceId() != $contrat_id ) {
					continue;
				}
//                $p = new AmapressAdhesion_paiement($post);
				if ( isset( $all_paiements[ $adh->getID() ] ) ) {
					/** @var AmapressAdhesion_paiement $p */
					foreach ( $all_paiements[ $adh->getID() ] as $p ) {
						$status = $p->getStatus();
						if ( 'received' != $status && 'bank' != $status ) {
							continue;
						}
						$amount += $p->getAmount( $colname );
					}
				}
			}

			return $amount;
		}
	}

	if ( ! term_exists( $colname, 'amps_paiement_category' ) ) {
		return $output;
	}

//    $adh = new AmapressAdhesion($post_id);

//    $args = array(
//        'post_type' => AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
//        'posts_per_page' => -1,
//        'meta_query' => array(
//            array(
//                'key' => 'amapress_adhesion_paiement_user',
//                'value' => $user_id,
//            ),
//            array(
//                'key' => 'amapress_adhesion_paiement_status',
//                'value' => array('received', 'bank'),
//                'compare' => 'IN'
//            ),
//        ),
////        'tax_query' => array(
////            array(
////                'taxonomy' => 'amps_paiement_category',
////                'field' => 'slug',
////                'terms' => $colname,
////                'operator' => 'IN'
////            ),
////        ),
//    );
//
//    $posts = get_posts($args);
//    $amount = 0;
//    foreach ($posts as $post) {
//        $p = new AmapressAdhesion_paiement($post);
//        $amount += $p->getAmount($colname);
//    }

	$term = get_term_by( 'slug', $colname, 'amps_paiement_category' );
	if ( empty( $term ) ) {
		return '';
	}

	$all_paiements = AmapressAdhesion_paiement::getAllActiveByUserId();
	$amount        = 0;
	if ( isset( $all_paiements[ $user_id ] ) ) {
		/** @var AmapressAdhesion_paiement $p */
		foreach ( $all_paiements[ $user_id ] as $p ) {
			$status = $p->getStatus();
			if ( 'received' != $status && 'bank' != $status ) {
				continue;
			}
			$amount += $p->getAmount( $colname );
		}
	}

	return $amount;
}

add_action( 'admin_footer', function () {
	echo '<style type="text/css">
        .paiement-not-paid, .paiement-too-paid {
            color:red;
        }
        .paiement-partial-paid {
            color:orange;
        }
        .paiement-ok {
            color:green;
        }
    </style>';
} );

//add_filter('get_terms', 'amapress_get_terms', 10, 2);
//function amapress_get_terms($terms, $taxo)
//{
//    if (in_array('amps_paiement_category', $taxo)) {
//        remove_filter('get_terms', 'amapress_get_terms', 10);
//        $terms = get_terms('amps_paiement_category', array(
//            'taxonomy' => 'amps_paiement_category',
//            'hide_empty' => false,
//        ));
//        add_filter('get_terms', 'amapress_get_terms', 10, 2);
//    }
//    return $terms;
//}
function amapress_paiements_count_editor( $post_id ) {
	$adhesion = new AmapressAdhesion( $post_id );
	$ret      = '<div><input class="small-text required" name="amapress_adhesion_paiements" placeholder="" min="0" max="12" id="amapress_adhesion_paiements" type="number" value="' . $adhesion->getPaiements() . '" min="0" max="1000" step="1" aria-required="true">';
	$ret      .= '&nbsp;&nbsp;<button id="amapress_paiements_save" class="button button-primary">Préparer la saisie des chèques</button></div>';
	$ret      .= '<script type="text/javascript">
        //<![CDATA[        
        jQuery(function ($) {
            var publishBtn = jQuery("form#post #publish");
            jQuery("#amapress_paiements_save").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                }
                catch (ee) {}
                if (jQuery("form#post").valid()) {
                    jQuery("#amapress_paiements").attr("value", "set_count");
                    publishBtn.click();
                } else {
                    alert("Certains champs ne sont pas valides");
                }
            });
        });
        //]]>
</script>';
	if ( $adhesion->getContrat_instance() != null ) {
		$amount = $adhesion->getTotalAmount();
		$ret    .= '<div><strong>Montant :</strong> ' . sprintf( '%.2f€', $amount ) . '</div>
                 <div><strong>Chèques prévus :</strong> ' . implode( ', ', array_map( function ( $v ) use ( $amount ) {
				return sprintf( '%d (%.2f€)', $v, $amount / $v );
			}, $adhesion->getContrat_instance()->getPossiblePaiements() ) ) . '</div>';
	}

	return $ret;
}

function amapress_paiements_editor( $post_id ) {
	$adhesion = new AmapressAdhesion( $post_id );
	if ( $adhesion->getContrat_instance() == null || 'draft' == $adhesion->getPost()->post_status ) {
		echo '<p style="color:red">Les chèques ne peuvent être renseignés qu\'une fois l\'adhésion au contrat enregistrée</p>';

		return;
	}

//    $refresh = ($_REQUEST['amapress_paiements'] == 'reset');

	$contrat_instance        = $adhesion->getContrat_instance();
	$contrat_paiements_dates = $contrat_instance->getPaiements_Liste_dates();
	$nb_paiements            = $adhesion->getPaiements();
	$contrat_paiements       = $adhesion->getAllPaiements();
	$all_paiements           = AmapressContrats::get_all_paiements( $contrat_instance->ID );
//    $all_paiements = array_filter($all_paiements,
//        function (AmapressAmapien_paiement $p) use ($adhesion) {
//            return $p->getAdhesion()->ID != $adhesion->ID;
//        }
//    );
	$all_paiements_by_dates = array_group_by( $all_paiements,
		function ( AmapressAmapien_paiement $p ) {
			return Amapress::start_of_day( $p->getDate() );
		}
	);
	foreach ( $contrat_paiements_dates as $d ) {
		if ( ! isset( $all_paiements_by_dates[ $d ] ) ) {
			$all_paiements_by_dates[ $d ] = array();
		}
	}
	$dates_by_cheque_count = array_combine(
		array_map( function ( $v, $k ) {
			return sprintf( '%05d-%8x', count( $v ), $k );
		}, array_values( $all_paiements_by_dates ), array_keys( $all_paiements_by_dates ) ),
		array_keys( $all_paiements_by_dates )
	);
	ksort( $dates_by_cheque_count );
	$dates_by_cheque_count = array_values( $dates_by_cheque_count );
	$all_quants            = array_merge( array( '_all' ),
		array_map( function ( AmapressContrat_quantite $p ) {
			$code = $p->getCode();

			return ! empty( $code ) ? $code : $p->getQuantite();
		}, AmapressContrats::get_contrat_quantites( $contrat_instance->ID ) ) );
	foreach ( $all_paiements_by_dates as $k => $v ) {
		$all_paiements_by_dates[ $k ] = array_merge( array( '_all' => $v ),
			array_group_by( $v, function ( AmapressAmapien_paiement $p ) {
				return implode( ',', array_map( function ( $vv ) {
					/** @var AmapressAdhesionQuantite $vv */
					$code = $vv->getContratQuantite()->getCode();

					return ! empty( $code ) ? $code : $vv->getContratQuantite()->getTitle();
				}, $p->getAdhesion()->getContrat_quantites() ) );
			} )
		);
	}

	if ( count( $contrat_paiements ) < $nb_paiements ) {
		$diff = $nb_paiements - count( $contrat_paiements );
		for ( $i = 0; $i < $diff; $i ++ ) {
			$contrat_paiements[] = null;
		}
	}
	//AmapressContrats::

	echo '<script type="text/javascript">
        //<![CDATA[
        jQuery(function ($) {
                        $.contextMenu({
	            selector: \'.recopy-context-menu\', 
	            callback: function(key, options) {
	                var val = $(this).val();
	                var is_num = !isNaN(parseInt(val)) && isFinite(val);
	                var val_num = is_num ? parseInt(val) : 0;
	                var columnNo = $(this).closest(\'td\').index();
	                var rowNo = $(this).closest(\'tr\').index();
	                var $table = $(this).closest("table");
	                var rowCount = $(\'tr\', $table).length;
	                for (var i = rowNo + 1; i < rowCount; i++) {
				        var $input = $table.find("tr:nth-child(" + (i+1) + ") td:nth-child(" + (columnNo+1) + ") input");
				        if (is_num) {
					        val_num += 1;
					        $input.val(val_num);
				        } else {
					        $input.val(val);
				        }
				    }
	            },
	            items: {
	                "recopy": {name: "Copier vers le bas", icon: "fa-arrow-down"}
	            }
	        });
            var publishBtn = jQuery("form#post #publish");
            jQuery("#amapress_paiement_reset").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                }
                catch (ee) {}
                if (jQuery("form#post").valid()) {
                    jQuery("#amapress_paiements").attr("value", "reset");
                    publishBtn.click();
                } else {
                    alert("Certains champs ne sont pas valides");
                }
            });
            jQuery("#adhesion_paiement_table").mouseover(function() {
                if (jQuery("#amapress_paiements").attr("value").length == 0) {
                    jQuery("#amapress_paiements").attr("value", "in_paiements");
                }
            });
        });
        function amapress_del_paiement(e) {
            if (!confirm("Voulez-vous vraiment supprimer cette quantité ?")) return;
            jQuery(e).closest("tr").remove();
        };
        //]]>
</script>';

	echo '<input id="amapress_paiements" name="amapress_paiements" type="hidden" value="">';

	echo '<table class="adhesion_paiement_table" id="adhesion_paiement_table" width="100%" style="">';
	echo "<tr>
<th>Numéro de chèque</th>
<th>Adhérent</th>
<th>Banque</th>
<th>Montant<br/><button id='amapress_paiement_reset'>Recalculer</button>
</th>
<th>Date</th>
<th>Statut</th>
<th></th>
</tr>";

	$new_paiement_date = array();
	$def_date          = 0;
	foreach ( $contrat_paiements as $paiement ) {
		if ( ! $paiement ) {
			$new_paiement_date[] = ( isset( $dates_by_cheque_count[ $def_date ] ) ? $dates_by_cheque_count[ $def_date ++ ] : 0 );
		}
	}
	sort( $new_paiement_date );

	$def_date = 0;
	$def_id   = - 1;
	foreach ( $contrat_paiements as $paiement ) {
		$id       = $paiement ? $paiement->ID : $def_id --;
		$numero   = esc_attr( $paiement ? $paiement->getNumero() : '' );
		$banque   = esc_attr( $paiement ? $paiement->getBanque() : '' );
		$adherent = esc_attr( $paiement ? $paiement->getEmetteur() : $adhesion->getAdherent()->getDisplayName() );
		if ( empty( $adherent ) ) {
			$adherent = $adhesion->getAdherent()->getDisplayName();
		}
		$amount = esc_attr( ( $paiement && $paiement->getAmount() > 0 ) ? $paiement->getAmount() : $adhesion->getTotalAmount() / $nb_paiements );
//        if ($refresh) $amount = $adhesion->getTotalAmount() / $nb_paiements;
		$status      = esc_attr( $paiement ? $paiement->getStatus() : 'not_received' );
		$paiement_dt = $paiement ? Amapress::start_of_day( $paiement->getDate() ) : ( isset( $new_paiement_date[ $def_date ] ) ? $new_paiement_date[ $def_date ++ ] : 0 );

		$status_options = '<option value="not_received" ' . selected( $status, 'not_received', false ) . '>Non reçu</option>
<option value="received" ' . selected( $status, 'received', false ) . '>Reçu</option>
<option value="bank" ' . selected( $status, 'bank', false ) . '>Encaissé</option>';

		$date_options = '';
		$date_option  = ' <span class="paiement-date">Date</span> ';
		foreach ( $all_quants as $quant ) {
			if ( $quant == '_all' ) {
				$quant = 'Tous';
			}
			$date_option .= '<span class="paiement-quant paiement-quant-' . count( $all_quants ) . '">' . $quant . '</span>';
		}
		$date_options .= '<option value="" disabled="disabled" data-html="' . esc_attr( $date_option ) . '">';
		$date_options .= $date_option;
		$date_options .= '</option>';
		foreach ( $contrat_paiements_dates as $date ) {
			$v           = isset( $all_paiements_by_dates[ $date ] ) ? $all_paiements_by_dates[ $date ] : array();
			$date_option = ' <span class="paiement-date">' . date_i18n( 'd/m/Y', $date ) . '</span> ';
			foreach ( $all_quants as $quant ) {
				$paiements = isset( $v[ $quant ] ) ? $v[ $quant ] : array();
				$sum       = 0;
				foreach ( $paiements as $p ) {
					$sum += $p->getAmount();
				}
				$date_option .= ' <span class="paiement-quant paiement-quant-' . count( $all_quants ) . '">' . sprintf( '%d(%.2f€)',
						count( $paiements ),
						$sum ) .
				                '</span> ';
			}

			$date_options .= '<option value="' . $date . '" ' . selected( $paiement_dt, $date, false ) . ' data-html="' . esc_attr( $date_option ) . '">';
			$date_options .= $date_option;
			$date_options .= '</option>';
		}

		echo "<tr>
<td class='paiement-numero'><input class='recopy-context-menu' style=\"width: 100%\"  name='amapress_paiements_details[$id][numero]' placeholder='' maxlength='1000' type='text' value='$numero' /></td>
<td class='paiement-adherent'><input class='recopy-context-menu adherent_select' style=\"width: 100%\" name='amapress_paiements_details[$id][adherent]' placeholder='' maxlength='1000' type='text' value='$adherent' /></td>
<td class='paiement-banque'><input class='recopy-context-menu' style=\"width: 100%\" name='amapress_paiements_details[$id][banque]' placeholder='' maxlength='1000' type='text' value='$banque' /></td>
<td class='paiement-amount'><input class='small-text paiement-amount-val' style=\"width: 100%\" name='amapress_paiements_details[$id][amount]' placeholder='' type='number' min='0' step='0.01' value='$amount' />&nbsp;€</td>
<td><select name='amapress_paiements_details[$id][date]' class='paiements_details' style=\"width: 100%\">
$date_options
</select>
<td><select name='amapress_paiements_details[$id][status]' class=''>
$status_options
</select></td>
<td style='width: 32px'><span class='btn del-model-tab dashicons dashicons-dismiss' onclick='amapress_del_paiement(this)'></span></td>
</tr>";
	}
	echo '<tr><td></td><td></td><td id="paiement-amount-total" data-sum="' . $adhesion->getTotalAmount() . '"></td><td></td><td></td><td></td></tr>';
	echo '</table>';
}

function amapress_save_paiements_editor( $adhesion_id ) {
	if ( isset( $_POST['amapress_paiements_details'] ) ) {
		$adh           = new AmapressAdhesion( $adhesion_id );
		$paiements     = $adh->getAllPaiements();
		$paiements_ids = array_map( function ( $q ) {
			return $q->ID;
		}, $paiements );
		$refresh       = ( $_REQUEST['amapress_paiements'] == 'reset' );

		foreach ( array_diff( $paiements_ids, array_keys( $_POST['amapress_paiements_details'] ) ) as $qid ) {
			wp_delete_post( $qid );
		}
		if ( isset( $_POST['amapress_adhesion_paiements'] ) &&
		     $_POST['amapress_adhesion_paiements'] < count( $_POST['amapress_paiements_details'] ) ) {
			$_POST['amapress_adhesion_paiements'] = count( $_POST['amapress_paiements_details'] );
		}
		if ( $_REQUEST['amapress_paiements'] != 'set_count' ) {
			$_POST['amapress_adhesion_paiements'] = count( $_POST['amapress_paiements_details'] );
		}
		$quants      = array_map( 'intval', $_REQUEST['amapress_adhesion_contrat_quants'] );
		$first_quant = new AmapressContrat_quantite( $quants[0] );
		foreach ( $_POST['amapress_paiements_details'] as $quant_id => $quant_data ) {
			$quant_id = intval( $quant_id );
			$my_post  = array(
				'post_type'    => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'post_content' => '',
				'post_status'  => 'publish',
				'meta_input'   => array(
					'amapress_contrat_paiement_adhesion'         => $adhesion_id,
					'amapress_contrat_paiement_contrat_instance' => $first_quant->getContrat_instance()->ID,
					'amapress_contrat_paiement_date'             => $quant_data['date'],
					'amapress_contrat_paiement_status'           => $quant_data['status'],
					'amapress_contrat_paiement_amount'           => $refresh ? 0 : $quant_data['amount'],
					'amapress_contrat_paiement_numero'           => $quant_data['numero'],
					'amapress_contrat_paiement_emetteur'         => $quant_data['adherent'],
					'amapress_contrat_paiement_banque'           => $quant_data['banque'],
				),
			);
			if ( $quant_id < 0 ) {
				wp_insert_post( $my_post );
			} else {
				$my_post['ID'] = $quant_id;
				wp_update_post( $my_post, true );
			}
		}
		unset( $_POST['amapress_paiements_details'] );
	}
	if ( isset( $_POST['amapress_adhesion_paiements'] ) ) {
		update_post_meta( $adhesion_id, 'amapress_adhesion_paiements', $_POST['amapress_adhesion_paiements'] );
	}
}

//amapress_paiements

function amapress_redirect_post_location( $location, $post_id ) {

	if ( ! empty( $_POST['amapress_paiements'] ) ) {
		return $location . '#3/-paiements';
	}

	return $location;
}

add_filter( 'redirect_post_location', 'amapress_redirect_post_location', 10, 2 );