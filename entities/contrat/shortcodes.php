<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//
//function amapress_contrat_info_shortcode( $atts ) {
//	global $post;
//	$atts = shortcode_atts( array(
//		'quantite' => '',
//		'info'     => '',
//		'mode'     => 'list',
//		'remain'   => true,
//		'fmt'      => '',
//		'sep'      => ',',
//	), $atts );
//
//	$info   = $atts['info'];
//	$mode   = $atts['mode'];
//	$remain = $atts['remain'];
//	$fmt    = $atts['fmt'];
//	$sep    = $atts['sep'];
//
////    if ($info == 'lieux' && get_query_var('viewmode') == 'intermittence') {
////        $inter_lieux = array_map(function(AmapressAdhesion_intermittence $i) { return $i->getLieu()->ID; },
////            AmapressContrats::get_user_active_intermittence());
////        $lieux = Amapress::get_lieux();
////        $r = '<ul class="intermittence lieu-list ' . $mode . '">';
////        foreach ($lieux as $l) {
////            $has_adhe = in_array($l->ID, $inter_lieux);
////            $r .= '<li class="lieu ' . ($has_adhe ? 'active' : '') . '">';
////            $r .= amapress_get_radio('lieu', $l->ID, ($has_adhe ? $l->ID : 0), $l->post_title);
////            $r .= '</li>';
////        }
////        $r .= '</ul>';
////        return $r;
////    }
//
//	$contrat            = null;
//	$adhesion           = null;
//	$adhesion_paiements = - 1;
//	$adhesion_quantite  = null;
//	$adhesion_lieu      = null;
//	$adhesion_date      = 0;
//	if ( $post != null ) {
//		$pt = amapress_simplify_post_type( $post->post_type );
//		if ( $pt == 'contrat_instance' ) {
//			$contrat = $post;
//		} else if ( $pt == 'contrat' ) {
//			$contrats = AmapressContrats::get_active_contrat_instances_by_contrat( $post->ID );
//			$contrat  = $contrats[0];
//		} else if ( $pt == 'adhesion' ) {
//			$adhesion           = $post;
//			$adhesion_quantite  = get_post( intval( get_post_meta( $adhesion->ID, 'amapress_adhesion_contrat_quantite', true ) ) );
//			$adhesion_lieu      = get_post( intval( get_post_meta( $adhesion->ID, 'amapress_adhesion_lieu', true ) ) );
//			$adhesion_paiements = intval( get_post_meta( $adhesion->ID, 'amapress_adhesion_paiements', true ) );
//			$adhesion_date      = intval( get_post_meta( $adhesion->ID, 'amapress_adhesion_date_debut', true ) );
//			$contrat            = get_post( intval( get_post_meta( $adhesion->ID, 'amapress_adhesion_contrat_instance', true ) ) );
//		}
//	} else {
//		return '<span class="error">Should be included only in Contrat or Adh�sion</span>';
//	}
//	$quantite   = ! empty( $atts['quantite'] ) ? get_post( Amapress::resolve_post_id( $atts['quantite'], 'amps_contrat_quant' ) ) : $adhesion_quantite;
//	$date_debut = $adhesion_date > 0 ? $adhesion_date : intval( get_post_meta( $contrat->ID, 'amapress_contrat_instance_date_debut', true ) );
//	$date_fin   = intval( get_post_meta( $contrat->ID, 'amapress_contrat_instance_date_fin', true ) );
//	$paiements  = Amapress::get_post_meta_array( $contrat->ID, 'amapress_contrat_instance_paiements' );
//	$quantites  = get_posts( array(
//		'post_type'  => 'amps_contrat_quant',
//		'meta_query' => array(
//			array(
//				'key'     => 'amapress_contrat_quantite_contrat_instance',
//				'value'   => $contrat->ID,
//				'type'    => 'NUMERIC',
//				'compare' => '=',
//			)
//		),
//	) );
////    var_dump($contrat);
////    var_dump($quantites);
////    die();
//	$lieux = get_posts( array(
//		'post_type' => 'amps_lieu',
//		'include'   => Amapress::get_post_meta_array( $contrat->ID, 'amapress_contrat_instance_lieux' ),
//	) );
//	//dates distrib : range, by month, all
//	//price per week, per month, per year
//	//nb distrib restantes/totales, valeur contrat sur date restantes/totales
//	//quantités checkable, options paiements checkable
//
//	$liste_dates = Amapress::get_array( get_post_meta( $contrat->ID, 'amapress_contrat_instance_liste_dates', true ) );
//	if ( ! $liste_dates ) {
//		$liste_dates = Amapress::get_array( get_post_meta( $contrat->ID, 'amapress_contrat_instance_commande_liste_dates', true ) );
//	}
//	$liste_dates = array_map( array( 'AmapressDistributions', 'to_date' ), $liste_dates );
//
//	if ( $remain ) {
//		if ( $adhesion ) {
//			$liste_dates = array_filter( $liste_dates, function ( $d ) use ( $adhesion_date ) {
//				return Amapress::start_of_day( $d ) >= Amapress::start_of_day( $adhesion_date );
//			} );
//		} else {
//			$liste_dates = array_filter( $liste_dates, function ( $d ) {
//				return Amapress::start_of_day( $d ) >= Amapress::start_of_day( amapress_time() );
//			} );
//		}
//	}
//	$price_unit = $quantite ? floatval( get_post_meta( $quantite->ID, 'amapress_contrat_quantite_prix_unitaire', true ) ) : 0;
//
//	switch ( $info ) {
//		case 'dates-by-range':
//			if ( empty( $fmt ) ) {
//				$fmt = 'd/m/Y';
//			}
//			$info = array_mode( array_map( function ( $prev, $curr ) {
//				if ( ! $prev || ! $curr ) {
//					return 0;
//				}
//
//				return floor( ( $curr - $prev ) / 604800 );
//			}, array_shift( $liste_dates ), $liste_dates ) );
//
//			$dates     = array();
//			$prev_date = null;
//			foreach ( $liste_dates as $dt ) {
//				if ( ! $prev_date ) {
//					$prev_date = $dt;
//					$dates[]   = $dt;
//					continue;
//				}
//
//				if ( floor( ( $dt - $prev_date ) / 604800 ) != $info ) {
//					$dates[] = $prev_date;
//					$dates[] = $dt;
//				}
//				$prev_date = $dt;
//			}
//			if ( $prev_date ) {
//				$dates[] = $prev_date;
//			}
//
//			$dates_str = array();
//			for ( $i = 0; $i < count( $dates ); $i += 2 ) {
//				$sd = $dates[ $i ];
//				$ed = $dates[ $i + 1 ];
//
//				if ( $sd != $ed ) {
//					$dates_str[] = 'du ' . date_i18n( $fmt, $sd ) . ' au ' . date_i18n( $fmt, $ed );
//				} else {
//					$dates_str[] = date_i18n( $fmt, $sd );
//				}
//			}
//
//			return "<span class='$info'>" . esc_html( implode( $sep, $dates_str ) ) . "</span>";
//
//		case 'dates-by-month':
//			$by_month  = array_group_by( $liste_dates, function ( $d ) {
//				return date_i18n( 'F', $d );
//			} );
//			$dates_str = array_map( function ( $k, $v ) {
//				return 'En ' . $k . ' : ' . implode( ', ', array_map( function ( $d ) {
//						return date_i18n( 'd', $d );
//					}, $v ) );
//			}, array_keys( $by_month ), array_values( $by_month ) );
//
//			return "<span class='$info'>" . esc_html( implode( $sep, $dates_str ) ) . "</span>";
//
//		case 'dates-all':
//			if ( empty( $fmt ) ) {
//				$fmt = 'd/m/Y';
//			}
//			$dates_str = array_map( function ( $d ) use ( $fmt ) {
//				return date_i18n( $fmt, $d );
//			}, $liste_dates );
//
//			return "<span class='$info'>" . esc_html( implode( $sep, $dates_str ) ) . "</span>";
//
//		case 'first-dist-date':
//			if ( empty( $fmt ) ) {
//				$fmt = 'd/m/Y';
//			}
//			$t = array_slice( $liste_dates, 0, 1 );
//
//			return "<span class='$info'>" . esc_html( date_i18n( $fmt, array_shift( $t ) ) ) . "</span>";
//
//		case 'last-dist-date':
//			if ( empty( $fmt ) ) {
//				$fmt = 'd/m/Y';
//			}
//			$t = array_slice( $liste_dates, - 1, 1, true );
//
//			return "<span class='$info'>" . esc_html( date_i18n( $fmt, array_shift( $t ) ) ) . "</span>";
//
//		case 'start-date':
//			if ( empty( $fmt ) ) {
//				$fmt = 'd/m/Y';
//			}
//
//			return "<span class='$info'>" . esc_html( date_i18n( $fmt, $date_debut ) ) . "</span>";
//
//		case 'end-date':
//			if ( empty( $fmt ) ) {
//				$fmt = 'd/m/Y';
//			}
//
//			return "<span class='$info'>" . esc_html( date_i18n( $fmt, $date_fin ) ) . "</span>";
//
//		case 'price-per-unit':
//			if ( empty( $fmt ) ) {
//				$fmt = '%01.2f�';
//			}
//
//			return "<span class='$info'>" . esc_html( sprintf( $fmt, $price_unit ) ) . "</span>";
//
//		case 'price-total':
//			if ( empty( $fmt ) ) {
//				$fmt = '%01.2f�';
//			}
//
//			return "<span class='$info'>" . esc_html( sprintf( $fmt, count( $liste_dates ) * $price_unit ) ) . "</span>";
//
//		case 'count-dates':
//			return "<span class='$info'>" . esc_html( count( $liste_dates ) ) . "</span>";
//
//		case 'quantites-with-paiements':
//			$r = '<ul class="' . ( $adhesion ? 'adhesion' : 'contrat' ) . ' quantite-list ' . $mode . '">';
//			foreach ( $quantites as $q ) {
//				$r .= '<li class="quantite ' . ( $q->ID == ( $quantite ? $quantite->ID : 0 ) ? 'active' : '' ) . '">';
//				$r .= amapress_get_radio( 'quantite', $q->ID, ( $quantite ? $quantite->ID : 0 ),
//					$q->post_title . ', soit ' );
//				$r .= '[contrat-info quantite=' . $q->ID . ' info=paiements mode=' . $mode . ' remain=' . $remain . ']';
//				$r .= '</li>';
//			}
//			$r .= '</ul>';
//
//			return do_shortcode( $r );
//		case 'quantites':
//			$r = '<ul class="' . ( $adhesion ? 'adhesion' : 'contrat' ) . ' quantite-list ' . $mode . '">';
//			foreach ( $quantites as $q ) {
//				$r .= '<li class="quantite ' . ( $q->ID == ( $quantite ? $quantite->ID : 0 ) ? 'active' : '' ) . '">';
//				$r .= amapress_get_radio( 'quantite', $q->ID, ( $quantite ? $quantite->ID : 0 ),
//					$q->post_title );
//				$r .= '</li>';
//			}
//			$r .= '</ul>';
//
//			return do_shortcode( $r );
//		case 'paiements':
//			$total = count( $liste_dates ) * $price_unit;
//			$r     = '<ul class="' . ( $adhesion ? 'adhesion' : 'contrat' ) . ' paiement-list quantite-' . $quantite->ID . ' ' . $mode . '">';
//			foreach ( $paiements as $p ) {
//				$r .= '<li class="paiement ' . ( $p == $adhesion_paiements ? 'active' : '' ) . '">';
//				$r .= amapress_get_radio( 'paiement_' . $quantite->ID, $p, $adhesion_paiements,
//					sprintf( '%d ch�que%s de %01.2f�', $p, ( $p > 2 ? 's' : '' ), $total / $p ) );
//				$r .= '</li>';
//			}
//			$r .= '</ul>';
//
//			return $r;
//		case 'lieux':
//			$r = '<ul class="' . ( $adhesion ? 'adhesion' : 'contrat' ) . ' lieu-list ' . $mode . '">';
//			foreach ( $lieux as $l ) {
//				$r .= '<li class="lieu ' . ( $l->ID == ( $adhesion_lieu ? $adhesion_lieu->ID : 0 ) ? 'active' : '' ) . '">';
//				$r .= amapress_get_radio( 'lieu', $l->ID, ( $adhesion_lieu ? $adhesion_lieu->ID : 0 ), $l->post_title );
//				$r .= '</li>';
//			}
//			$r .= '</ul>';
//
//			return $r;
//
//		default:
//			if ( strpos( $info, 'field-' ) === 0 ) {
//				if ( empty( $fmt ) ) {
//					$fmt = '%s';
//				}
//				if ( $quantite ) {
//					return sprintf( $fmt, get_post_meta( $quantite->ID, 'amapress_contrat_quantite_' . substr( $info, 6 ), true ) );
//				} else {
//					return sprintf( $fmt, get_post_meta( $contrat->ID, 'amapress_contrat_instance_' . substr( $info, 6 ), true ) );
//				}
//			} else if ( strpos( $info, 'date-field-' ) === 0 ) {
//				if ( empty( $fmt ) ) {
//					$fmt = 'd/m/Y';
//				}
//				if ( $quantite ) {
//					return date_i18n( $fmt, intval( get_post_meta( $quantite->ID, 'amapress_contrat_quantite_' . substr( $info, 6 ), true ) ) );
//				} else {
//					return date_i18n( $fmt, intval( get_post_meta( $contrat->ID, 'amapress_contrat_instance_' . substr( $info, 6 ), true ) ) );
//				}
//			}
//
//			return '<span class="error">Unknown mode</span>';
//	}
//}