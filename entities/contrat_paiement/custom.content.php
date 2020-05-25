<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


//add_action('amapress_get_user_infos_content_paiements', 'amapress_get_user_infos_content_paiements');
//function amapress_get_user_infos_content_paiements()
//{
//    TODO
//}

add_filter( 'manage_edit-amps_adhesion_columns', 'amapress_user_paiments_columns', 15 );
//add_filter('manage_edit-amps_adhesion_sortable_columns', 'amapress_user_paiments_columns', 15);
function amapress_user_paiments_columns( $columns ) {
	$columns['amapress_total_amount']    = 'Montant';
	$columns['amapress_received_amount'] = 'Reçu';

	return $columns;
}

add_filter( 'manage_amps_adhesion_posts_custom_column_export', 'amapress_user_paiments_column_export', 10, 3 );
function amapress_user_paiments_column_export( $value, $colname, $post_id ) {
	//$adh = AmapressAdhesion::getBy($post_id);
	$all_adhesions = AmapressContrats::get_active_adhesions();
	$adh           = isset( $all_adhesions[ $post_id ] ) ? $all_adhesions[ $post_id ] : AmapressAdhesion::getBy( $post_id );
	if ( $colname == 'amapress_total_amount' ) {
		return sprintf( '%.02f', $adh->getTotalAmount() );
	}

	if ( $colname != 'amapress_received_amount' ) {
		return $value;
	}
//    $args = array(
//        'post_type' => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
//        'posts_per_page' => -1,
//        'meta_query' => array(
//            array(
//                'key' => 'amapress_contrat_paiement_adhesion',
//                'value' => $post_id,
//            ),
//            array(
//                'key' => 'amapress_contrat_paiement_status',
//                'value' => array('received', 'bank'),
//                'compare' => 'IN'
//            ),
//        ),
//    );
//
//    $posts = get_posts($args);
//    $amount = 0;
//    foreach ($posts as $post) {
//        $p = new AmapressAmapien_paiement($post);
//        $amount += $p->getAmount();
//    }

	$all_paiements = AmapressAmapien_paiement::getAllActiveByAdhesionId();
	$amount        = 0;
	if ( isset( $all_paiements[ $post_id ] ) ) {
		/** @var AmapressAdhesion_paiement $p */
		foreach ( $all_paiements[ $post_id ] as $p ) {
			if ( $p->isNotReceived() ) {
				continue;
			}
			$amount += $p->getAmount( $colname );
		}
	}

	$amount_fmt = sprintf( '%.02f', $amount );

	return $amount_fmt;
}

add_action( 'load-edit.php', function () {
	//optimize loading of corresponding screen in backoffice
	$screen = get_current_screen();
	if ( 'edit-amps_adhesion' == $screen->id ) {
		$adhesions = AmapressContrats::get_active_adhesions();
		$user_ids  = array_map( function ( $a ) {
			/** @var AmapressAdhesion $a */
			return $a->getAdherentId();
		}, $adhesions );
		$user_ids  = array_merge( $user_ids, array_map( function ( $a ) {
			/** @var AmapressAdhesion $a */
			return $a->getAdherent2Id();
		}, $adhesions ) );
		$user_ids  = array_unique( $user_ids );
		update_meta_cache( 'user', $user_ids );
		cache_users( $user_ids );
	} elseif ( 'edit-amps_cont_pmt' == $screen->id ) {
		AmapressContrats::get_active_adhesions();
	}
} );

add_filter( 'manage_amps_adhesion_posts_custom_column', 'amapress_user_paiments_column_display', 10, 2 );
function amapress_user_paiments_column_display( $colname, $post_id ) {
	$all_adhesions = AmapressContrats::get_active_adhesions();
	$adh           = isset( $all_adhesions[ $post_id ] ) ? $all_adhesions[ $post_id ] : AmapressAdhesion::getBy( $post_id );
	if ( $colname == 'amapress_total_amount' ) {
		echo sprintf( '%.02f', $adh->getTotalAmount() );

		return;
	}

	if ( $colname != 'amapress_received_amount' ) {
		return;
	}

	$all_paiements = AmapressAmapien_paiement::getAllActiveByAdhesionId();

	$amount = 0;
	$related_amount       = 0;
	$related_total_amount = 0;

	$all_related_adhesions_ids = AmapressAdhesion::getAllRelatedAdhesions();
	$related_adhesions_ids     = ! empty( $all_related_adhesions_ids[ $adh->ID ] ) ? $all_related_adhesions_ids[ $adh->ID ] : [];
	if ( ! in_array( $post_id, $related_adhesions_ids ) ) {
		$related_adhesions_ids[] = $post_id;
	}
	sort( $related_adhesions_ids );

	foreach ( $related_adhesions_ids as $related_adhesion_id ) {
//			$amount -= $related_adhesion->getTotalAmount();
		if ( isset( $all_paiements[ $related_adhesion_id ] ) ) {
			/** @var AmapressAmapien_paiement $p */
			foreach ( $all_paiements[ $related_adhesion_id ] as $p ) {
				$status = $p->getStatus();
				if ( 'received' != $status && 'bank' != $status ) {
					continue;
				}
				$related_amount += $p->getAmount();
				if ( $related_adhesion_id == $post_id ) {
					$amount += $p->getAmount();
				}
			}
		}
		$related_adhesion = AmapressAdhesion::getBy( $related_adhesion_id );
		if ( $related_adhesion ) {
			$related_total_amount += $related_adhesion->getTotalAmount();
		}
	}
	if ( count( $related_adhesions_ids ) > 1 ) {
		$affected_adhesion_ids = [];
		foreach ( $related_adhesions_ids as $related_adhesion_id ) {
			$related_adhesion = AmapressAdhesion::getBy( $related_adhesion_id );
			if ( $related_adhesion && $related_amount >= $related_adhesion->getTotalAmount() ) {
				$affected_adhesion_ids[] = $related_adhesion_id;
				$related_amount          -= $related_adhesion->getTotalAmount();
				$related_total_amount    -= $related_adhesion->getTotalAmount();
				if ( $related_adhesion_id == $post_id ) {
					$amount = $related_adhesion->getTotalAmount();
				}
			}
		}
		foreach ( $related_adhesions_ids as $related_adhesion_id ) {
			if ( in_array( $related_adhesion_id, $affected_adhesion_ids ) ) {
				continue;
			}
			$related_adhesion = AmapressAdhesion::getBy( $related_adhesion_id );
			if ( $related_adhesion && $related_amount > 0 && $related_amount < $related_adhesion->getTotalAmount() ) {
				if ( $related_adhesion_id == $post_id ) {
					$amount = $related_total_amount - $related_amount;
				}
				break;
			}
		}
	}


	$href = admin_url( "post.php?post={$post_id}&action=edit" );

	if ( abs( $related_amount ) < 0.001 ) {
		$status = array( 'icon' => 'dashicons-before dashicons-no-alt', 'status' => 'paiement-not-paid' );
	} else if ( ( $related_amount - $related_total_amount ) < - 0.001 ) {
		$status = array( 'icon' => 'dashicons-before dashicons-star-half', 'status' => 'paiement-partial-paid' );
	} else if ( ( $related_amount - $related_total_amount ) > 0.001 ) {
		$status = array( 'icon' => 'dashicons-before dashicons-arrow-up-alt', 'status' => 'paiement-too-paid' );
	} else {
		$status = array( 'icon' => 'dashicons-before dashicons-yes', 'status' => 'paiement-ok' );
	}

	$amount_fmt = sprintf( '%.02f', $amount );

	echo "<a href='$href'><span class='{$status['status']}'><span class='{$status['icon']}'></span> $amount_fmt</span></a>";
}
