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
	//$adh = new AmapressAdhesion($post_id);
	$all_adhesions = AmapressContrats::get_active_adhesions();
	$adh           = isset( $all_adhesions[ $post_id ] ) ? $all_adhesions[ $post_id ] : new AmapressAdhesion( $post_id );
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
			$status = $p->getStatus();
			if ( 'received' != $status && 'bank' != $status ) {
				continue;
			}
			$amount += $p->getAmount( $colname );
		}
	}

	$amount_fmt = sprintf( '%.02f', $amount );

	return $amount_fmt;
}

add_filter( 'manage_amps_adhesion_posts_custom_column', 'amapress_user_paiments_column_display', 10, 2 );
function amapress_user_paiments_column_display( $colname, $post_id ) {
//    $adh = new AmapressAdhesion($post_id);
	$all_adhesions = AmapressContrats::get_active_adhesions();
//    var_dump(array_keys($all_adhesions));
	$adh = isset( $all_adhesions[ $post_id ] ) ? $all_adhesions[ $post_id ] : new AmapressAdhesion( $post_id );
	if ( $colname == 'amapress_total_amount' ) {
		echo sprintf( '%.02f', $adh->getTotalAmount() );

		return;
	}

	if ( $colname != 'amapress_received_amount' ) {
		return;
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
//            ),),
//    );
//
//
//    $posts = get_posts($args);
//    $amount = 0;
//    foreach ($posts as $post) {
//        $p = new AmapressAmapien_paiement($post);
//        $amount += $p->getAmount();
//    }

	$all_paiements = AmapressAmapien_paiement::getAllActiveByAdhesionId();
//    var_dump(array_keys($all_paiements));
	$amount = 0;
	if ( isset( $all_paiements[ $post_id ] ) ) {
		/** @var AmapressAdhesion_paiement $p */
		foreach ( $all_paiements[ $post_id ] as $p ) {
			$status = $p->getStatus();
			if ( 'received' != $status && 'bank' != $status ) {
				continue;
			}
			$amount += $p->getAmount( $colname );
		}
	}

	$href = admin_url( "post.php?post={$post_id}&action=edit" );

	if ( round( $amount ) == 0 ) {
		$status = array( 'icon' => 'dashicons-before dashicons-no-alt', 'status' => 'paiement-not-paid' );
	} else if ( round( $amount ) < $adh->getTotalAmount() ) {
		$status = array( 'icon' => 'dashicons-before dashicons-star-half', 'status' => 'paiement-partial-paid' );
	} else if ( round( $amount ) > $adh->getTotalAmount() ) {
		$status = array( 'icon' => 'dashicons-before dashicons-arrow-up-alt', 'status' => 'paiement-too-paid' );
	} else {
		$status = array( 'icon' => 'dashicons-before dashicons-yes', 'status' => 'paiement-ok' );
	}

	$amount_fmt = sprintf( '%.02f', $amount );

	echo "<a href='$href'><span class='{$status['status']}'><span class='{$status['icon']}'></span> $amount_fmt</span></a>";
}
