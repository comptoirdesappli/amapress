<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * class short summary.
 *
 * class description.
 *
 * @version 1.0
 * @author Guillaume
 */
class AmapressPaniers {
	public static function init() {
//        add_shortcode('distribution_panier', array(__CLASS__, 'panier_shortcode'));
	}

	public static function generate_paniers( $contrat_id, $from_now = true, $eval = false ) {
		$res      = array();
		$contrats = AmapressContrats::get_active_contrat_instances( $contrat_id );
		foreach ( $contrats as $contrat ) {
			$res[ $contrat->ID ] = array();
			$contrat_model       = $contrat->getModel();
			$lieux               = $contrat->getLieuxIds();
			$liste_dates         = array_unique( $contrat->getListe_dates() );
			if ( empty( $liste_dates ) ) {
				continue;
			}

			$now = Amapress::start_of_day( amapress_time() );
			foreach ( $liste_dates as $date ) {
				if ( $from_now && $date < $now ) {
					continue;
				}
				$paniers = get_posts( array(
					'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => 'amapress_panier_date',
							'value'   => array(
								Amapress::start_of_day( $date ),
								Amapress::end_of_day( $date )
							),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'amapress_panier_contrat_instance',
							'value'   => $contrat->ID,
							'compare' => '=',
							'type'    => 'NUMERIC'
						),
					)
				) );
				if ( count( $paniers ) == 0 ) {
					$my_post = array(
						'post_title'   => sprintf( 'Panier de %s du %02d-%02d-%04d',
							$contrat_model->post_title,
							date( 'd', $date ), date( 'm', $date ), date( 'Y', $date ) ),
						'post_type'    => AmapressPanier::INTERNAL_POST_TYPE,
						'post_content' => '',
						'post_status'  => 'publish',
						'meta_input'   => array(
							'amapress_panier_contrat_instance' => $contrat->ID,
							'amapress_panier_date'             => Amapress::start_of_day( $date ),
						),
					);

					$res[ $contrat->ID ][] = array( 'lieux' => $lieux, 'date' => Amapress::start_of_day( $date ) );
					// Insert the post into the database.
					if ( ! $eval ) {
						wp_insert_post( $my_post );
					}
				}
			}
		}

		return $res;
	}

	public
	static function getPanierVariableCommandes(
		$contrat_instance_id, $date, $lieu_id = null
	) {
		$columns = array(
			array(
				'title' => 'Nom',
				'data'  => array(
					'_'    => 'last_name',
					'sort' => 'last_name',
				)
			),
			array(
				'title' => 'Prénom',
				'data'  => array(
					'_'    => 'first_name',
					'sort' => 'first_name',
				)
			),
			array(
				'title' => 'Lieu',
				'data'  => array(
					'_'    => 'lieu',
					'sort' => 'lieu',
				)
			),
			array(
				'title' => 'Contenu',
				'data'  => array(
					'_'    => 'content',
					'sort' => 'content',
				)
			),
		);

		$adhesions = array_group_by(
			AmapressContrats::get_active_adhesions( array( $contrat_instance_id ) ),
			function ( $adh ) {
				/** @var AmapressAdhesion $adh */
				$user_ids = AmapressContrats::get_related_users( $adh->getAdherent()->getUser()->ID );

				return implode( '_', $user_ids );
			} );
		$liste     = array();
		/** @var AmapressAdhesion[] $adhs */
		foreach ( $adhesions as $user_ids => $adhs ) {
			$line = array();

			$user_ids = explode( '_', $user_ids );
			$users    = array_map( function ( $user_id ) {
				return get_user_by( 'ID', intval( $user_id ) );
			}, $user_ids );

			$line['first_name'] = implode( ' / ', array_map( function ( $user ) {
				return $user->first_name;
			}, $users ) );
			$line['last_name']  = implode( ' / ', array_map( function ( $user ) {
				return ! empty( $user->last_name ) ? $user->last_name : $user->display_name;
			}, $users ) );
			$line['tel']        = implode( '<br/>', array_map( function ( $user ) {
				$adh = AmapressUser::getBy( $user );

				return $adh->getTelTo();
			}, $users ) );

			foreach ( $adhs as $adh ) {
				if ( $adh->getContrat_instanceId() == $contrat_instance_id &&
				     ( $lieu_id == null || $adh->getLieuId() == $lieu_id )
				) {
					$line['lieu']    = $adh->getLieu()->getShortName();
					$line['content'] = $adh->getContrat_quantites_AsString( $date );
					if ( ! empty( $line['content'] ) ) {
						$liste[] = $line;
					}
				}
			}
		}

		return array(
			'columns' => $columns,
			'data'    => $liste
		);
	}

	public
	static function panierTable(
		$panier_id
	) {
		if ( empty( $panier_id ) ) {
			$panier_id = $_GET['post'];
		}
		$panier = get_post( intval( $panier_id ) );
		if ( ! $panier ) {
			return '';
		}
		$panier = new AmapressPanier( $panier );

		$contrat = intval( get_post_meta( intval( $panier_id ), 'amapress_panier_contrat_instance', true ) );
		if ( ! $contrat ) {
			return 'Merci de sélectionner le contrat associé';
		}
		$c = new AmapressContrat_instance( $contrat );
		if ( $c->isPanierVariable() ) {
			$panier_commandes = self::getPanierVariableCommandes( $c->ID, $panier->getDate() );

//            var_dump($liste);
//            if (!empty($liste)) {
//                echo '<br/>';
//                echo '<h3 class="liste-emargement-contrat-variable">Détails des paniers - '.esc_html($c->getTitle()).'</h3>';
			return amapress_get_datatable( 'liste-emargement-contrat-variable-' . $c->ID,
				$panier_commandes['columns'], $panier_commandes['data'],
				array( 'paging' => false, 'searching' => false ),
				array(
					Amapress::DATATABLES_EXPORT_EXCEL,
					Amapress::DATATABLES_EXPORT_PDF,
					Amapress::DATATABLES_EXPORT_PRINT
				) );
//            }
//            return;
		}
		//$abos = get_posts(array('post_type' => 'amps_contrat','order'=>'ASC','orderby'=>'value','key'=>'amapress_contrat_instance_code', 'include'=>$abo_ids));
		$abos = get_posts( array(
			'post_type'      => AmapressContrat_quantite::INTERNAL_POST_TYPE,
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'amapress_contrat_quantite_contrat_instance',
					'value'   => $contrat,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			)
		) );
		//var_dump($abos);
		$produit_ids = Amapress::get_post_meta_array( intval( $panier_id ), 'amapress_panier_produits_selected' );
		//var_dump($produit_ids);
		if ( ! $produit_ids ) {
			$produit_ids = array();
		}
		if ( $produit_ids ) {
			$produit_ids = array_map( 'intval', $produit_ids );
		}
		$lieu_ids = Amapress::get_post_meta_array( $contrat, 'amapress_contrat_instance_lieux' );
		if ( $lieu_ids ) {
			$lieu_ids = array_map( 'intval', $lieu_ids );
		}
		if ( count( $lieu_ids ) == 0 ) {
			return 'Merci de sélectionner les lieux associés';
		}

		$contrat_modele = get_post_meta( intval( $contrat ), 'amapress_contrat_instance_model', true );
		$producteur     = get_post_meta( intval( $contrat_modele ), 'amapress_contrat_producteur', true );

		$produits             = get_posts( array(
			'post_type'      => 'amps_produit',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'amapress_produit_producteur',
					'value'   => $producteur,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			'order'          => 'ASC',
			'orderby'        => 'title'
		) );
		$lieux                = get_posts( array(
			'post_type'      => 'amps_lieu',
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'title',
			'include'        => $lieu_ids
		) );
		$adhesion_abo_counts  = array();
		$adhesion_lieu_counts = array();

		ob_start();
		echo '<table class="panier">';
		echo '<tr>
					<th class="panier-produit-col-head"></th>
					<th class="panier-base-col-head"></th>
					<th class="panier-unit-col-head"></th>';
		foreach ( $abos as $abo ) {
			if ( ! array_key_exists( $abo->ID, $adhesion_abo_counts ) ) {
				$adhesion_abo_counts[ $abo->ID ] = count( get_posts( array(
					'post_type'      => 'amps_adhesion',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'   => 'amapress_adhesion_contrat_quantite',
							'value' => $abo->ID,
						),
						array(
							'key'     => 'amapress_adhesion_contrat_quantite',
							'value'   => '"' . $abo->ID . '"',
							'compare' => 'like'
						)
					),
				) ) );
			}
			printf( '<th class="panier-abo-col-head">%s (%d)</th>', $abo->post_title, $adhesion_abo_counts[ $abo->ID ] );
		}
		$total_quant = 0.0;
		foreach ( $lieux as $lieu ) {
			if ( ! array_key_exists( $lieu->ID, $adhesion_lieu_counts ) ) {
				$adhesions = get_posts( array(
					'post_type'      => 'amps_adhesion',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						array(
							'key'   => 'amapress_adhesion_lieu',
							'value' => $lieu->ID,
						)
					),
				) );
				$factor    = 0.0;
				foreach ( $adhesions as $sous ) {
					$adh = new AmapressAdhesion( $sous->ID );
					foreach ( $adh->getContrat_quantites() as $q ) {
						$quant  = floatval( $q->getQuantite() );
						$factor += $quant;
					}
				}
				$adhesion_lieu_counts[ $lieu->ID ] = $factor;
				$total_quant                       += $factor;
			}
			printf( '<th class="panier-lieu-col-head">%s (%.1f)</th>', $lieu->post_title, $adhesion_lieu_counts[ $lieu->ID ] );
		}

		echo( '<th class="panier-total-col">Total</th>' );
		echo '</tr>';

		//if ($total_quant == 0) $total_quant = 1.0;

		foreach ( $produits as $produit ) {
			$produits_abo = Amapress::get_post_meta_array( intval( $panier_id ), 'amapress_panier_produits_' . $produit->ID . '_quants' );
			echo '<tr class="panier-tr">';
			echo( '<td class="panier-produit-col"><input id="amapress_select_' . $produit->ID . '" name="amapress_select_' . $produit->ID . '" value="sel" type="checkbox" ' . checked( in_array( $produit->ID, $produit_ids ) ) . '/>' . $produit->post_title . '</td>' );
			echo( '<td class="panier-base-col"><input class="number base-val" id="amapress_base_' . $produit->ID . '" type="text" style="width:60px"/><button class="amapress_base_btn" id="amapress_base_btn_' . $produit->ID . '">&gt;&gt;</button></td>' );
			echo '<td class="panier-unit-col"><select id="amapress_panier_produits_' . $produit->ID . '_unit" name="amapress_panier_produits_' . $produit->ID . '_unit">';
			foreach ( array( 'unit' => 'Unité', 'kg' => 'kg' ) as $unit => $unit_label ) {
				echo '<option value="' . $unit . '" ' . ( $unit == $produits_abo['unit'] ? 'selected="selected"' : '' ) . '>' . $unit_label . '</option>';
			}
			echo '</select></td>';
			foreach ( $abos as $abo ) {
				$base_id = 'amapress_panier_produits_' . $produit->ID . '_' . $abo->ID . '_quant';
				//$factor = get_post_meta($abo->ID,'amapress_contrat_instance_quantite',true);
				//$contrat_quant_id = intval(get_post_meta($abo->ID,'amapress_adhesion_contrat_quantite',true));
				$factor = floatval( get_post_meta( $abo->ID, 'amapress_contrat_quantite_quantite', true ) );
				echo '<td class="panier-abo-col"><input class="number" data-count="' . $adhesion_abo_counts[ $abo->ID ] . '" data-factor="' . $factor . '" id="' . $base_id . '" name="' . $base_id . '" type="text" value="' . $produits_abo[ $abo->ID ] . '" /></td>';
			}
			foreach ( $lieux as $lieu ) {
				$factor = $adhesion_lieu_counts[ $lieu->ID ];
				echo '<td class="panier-lieu-col" data-factor="' . ( $factor / $total_quant ) . '"></td>';
			}
			echo '<td class="panier-total-col"><input class="number total-val" id="amapress_total_' . $produit->ID . '" type="text" style="width:60px"/><button class="amapress_total_btn" id="amapress_total_btn_' . $produit->ID . '">&lt;&lt;</button></td>';
			echo '</tr>';
		}
		echo '</table>';

		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}

	public
	static function savePanierTable(
		$postID
	) {
		$panier_id = $postID;

		$contrat = intval( get_post_meta( intval( $panier_id ), 'amapress_panier_contrat_instance', true ) );
		if ( ! $contrat ) {
			return 'Merci de sélectionner le contrat associé';
		}
		//$abos = get_posts(array('post_type' => 'amps_contrat','order'=>'ASC','orderby'=>'value','key'=>'amapress_contrat_instance_code', 'include'=>$abo_ids));
		$abos = get_posts( array(
			'post_type'      => 'amps_contrat_quant',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'amapress_contrat_quantite_contrat_instance',
					'value'   => $contrat,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			)
		) );

		//$abo_ids = Amapress::get_post_meta_array(intval($panier_id),'amapress_panier_contrats');
		$abo_ids = array_map( array( 'Amapress', 'to_id' ), $abos );
		if ( ! $abo_ids ) {
			$abo_ids = array();
		}

		$contrat_model = intval( get_post_meta( intval( $contrat ), 'amapress_contrat_instance_model', true ) );
		$producteur    = intval( get_post_meta( intval( $contrat_model ), 'amapress_contrat_producteur', true ) );

		$produits = get_posts( array(
			'post_type'      => 'amps_produit',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'amapress_produit_producteur',
					'value'   => $producteur,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			'order'          => 'ASC',
			'orderby'        => 'title'
		) );

		$leg_ids = array();
		foreach ( $produits as $produit ) {
			$quants = array();
			foreach ( $abo_ids as $abo_id ) {
				$quant = $_POST[ 'amapress_panier_produits_' . $produit->ID . '_' . $abo_id . '_quant' ];
				if ( ! empty( $quant ) ) {
					$quants[ $abo_id ] = $quant;
				}
			}
			$selected = $_POST[ 'amapress_select_' . $produit->ID ];
			if ( count( $quants ) > 0 ) {
				$quants['unit'] = $_POST[ 'amapress_panier_produits_' . $produit->ID . '_unit' ];
				update_post_meta( intval( $panier_id ), 'amapress_panier_produits_' . $produit->ID . '_quants', $quants );
				$leg_ids[] = $produit->ID;
			} else {
				delete_post_meta( intval( $panier_id ), 'amapress_panier_produits_' . $produit->ID . '_quants' );
				if ( isset( $selected ) ) {
					$leg_ids[] = $produit->ID;
				}
			}
		}
		update_post_meta( intval( $panier_id ), 'amapress_panier_produits_selected', $leg_ids );

		return true;
	}

	public
	static function get_selected_produits(
		$panier_id
	) {
		return Amapress::get_post_meta_array( $panier_id, 'amapress_panier_produits_selected' );
	}

//    public static function panier_shortcode($atts)
//    {
//        return getPanier();
//    }

	/** @return AmapressDistribution */
	public
	static function getDistribution(
		$date, $lieu
	) {
		$dists = get_posts( array(
			'post_type'      => 'amps_distribution',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_distribution_date',
					'value'   => array(
						Amapress::start_of_day( $date ),
						Amapress::end_of_day( $date )
					),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				),
				array(
					'key'     => 'amapress_distribution_lieu',
					'value'   => intval( $lieu ),
					'compare' => '=',
					'type'    => 'NUMERIC'
				),
			),
		) );
		if ( count( $dists ) ) {
			return new AmapressDistribution( $dists[0] );
		}

		return null;
	}

	public
	static function isIntermittent(
		$panier_id, $contrat_instance_id, $lieu_id, $user_id = null
	) {
//        $panier = new AmapressPanier($panier_id);
		if ( empty( $user_id ) ) {
			$user_id = amapress_current_user_id();
		}
		$args  = array(
			'post_type'      => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'   => 'amapress_intermittence_panier_panier',
					'value' => $panier_id,
				),
				array(
					'key'   => 'amapress_intermittence_panier_contrat_instance',
					'value' => $contrat_instance_id,
				),
				array(
					'key'     => 'amapress_intermittence_panier_adherent',
					'value'   => AmapressContrats::get_related_users( $user_id ),
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				),
				array(
					'key'   => 'amapress_intermittence_panier_lieu',
					'value' => $lieu_id,
				),
				array(
					'key'     => 'amapress_intermittence_panier_status',
					'value'   => 'cancelled',
					'compare' => '!='
				),
			)
		);
		$posts = get_posts(
			$args
		);
		if ( count( $posts ) > 0 ) {
			$echange = new AmapressIntermittence_panier( $posts[0] );

			return $echange->getStatus();
		}

		return false;
	}

	public
	static function getUserPaniersIntermittents() {
		return self::getPanierIntermittents( array(
			'adherent' => amapress_current_user_id(),
		) );
	}

	public
	static function getPaniersIntermittentsToBuy() {
		return self::getPanierIntermittents( array(
			'status' => 'to_exchange',
		) );
	}

	/**
	 * @return AmapressIntermittence_panier[]
	 */
	public
	static function getPanierIntermittents(
		$args
	) {
		$args = wp_parse_args(
			$args,
			array(
				'panier_id'           => null,
				'contrat_instance_id' => null,
				'adherent'            => null,
				'repreneur'           => null,
				'lieu_id'             => null,
				'status'              => null,
				'date'                => null,
			) );
//        $panier = new AmapressPanier($panier_id);
		$meta_query = array();
		if ( ! empty( $args['panier_id'] ) ) {
			$meta_query[] = array(
				'key'   => 'amapress_intermittence_panier_panier',
				'value' => $args['panier_id'],
			);
		}
		if ( ! empty( $args['contrat_instance_id'] ) ) {
			$meta_query[] = array(
				'key'     => 'amapress_intermittence_panier_contrat_instance',
				'value'   => $args['contrat_instance_id'],
				'compare' => is_array( $args['contrat_instance_id'] ) ? 'IN' : '=',
			);
		}
		if ( ! empty( $args['adherent'] ) ) {
			$adherent = $args['adherent'];
			if ( ! is_array( $adherent ) ) {
				$adherent = AmapressContrats::get_related_users( $adherent );
			}
			$meta_query[] = array(
				'key'     => 'amapress_intermittence_panier_adherent',
				'value'   => $adherent,
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			);
		}
		if ( ! empty( $args['repreneur'] ) ) {
			$meta_query[] = array(
				'key'   => 'amapress_intermittence_panier_repreneur',
				'value' => $args['repreneur'],
			);
		}
		if ( ! empty( $args['lieu_id'] ) ) {
			$meta_query[] = array(
				'key'   => 'amapress_intermittence_panier_lieu',
				'value' => $args['lieu_id'],
			);
		}
		if ( ! empty( $args['status'] ) ) {
			$meta_query[] = array(
				'key'   => 'amapress_intermittence_panier_status',
				'value' => $args['status'],
			);
			if ( $args['status'] == 'to_exchange' ) {
				$meta_query[] = array(
					'key'     => 'amapress_intermittence_panier_adherent',
					'value'   => AmapressContrats::get_related_users( amapress_current_user_id() ),
					'compare' => 'NOT IN',
					'type'    => 'NUMERIC',
				);
			}
		}
		if ( ! empty( $args['date'] ) ) {
			$meta_query[] = array(
				'key'   => 'amapress_intermittence_panier_date',
				'value' => Amapress::start_of_day( $args['date'] ),
			);
		} else {
			$meta_query[] = array(
				'key'     => 'amapress_intermittence_panier_date',
				'value'   => Amapress::start_of_day( amapress_time() ),
				'compare' => '>='
			);
		}

		$args  = array(
			'post_type'      => AmapressIntermittence_panier::INTERNAL_POST_TYPE,
			'posts_per_page' => - 1,
			'meta_query'     => $meta_query
		);
		$posts = get_posts(
			$args
		);

		return array_map( function ( $p ) {
			return new AmapressIntermittence_panier( $p );
		}, $posts );
	}

	/** @return AmapressPanier */
	public
	static function getPanierForDist(
		$dist_date, $contrat_instance_id
	) {
		$res = get_posts(
			array(
				'post_type'      => 'amps_panier',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_panier_date',
							'value'   => array(
								Amapress::start_of_day( $dist_date ),
								Amapress::end_of_day( $dist_date )
							),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						),
						array(
							'key'     => 'amapress_panier_date_subst',
							'value'   => array(
								Amapress::start_of_day( $dist_date ),
								Amapress::end_of_day( $dist_date )
							),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						),
					),
					array(
						'key'     => 'amapress_panier_contrat_instance',
						'value'   => $contrat_instance_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				)
			) );
		if ( count( $res ) == 0 ) {
			return null;
		}

		return new AmapressPanier( array_shift( $res ) );
	}

	/** @return AmapressPanier[] */
	public
	static function getPaniersForDist(
		$dist_date
	) {
		$res = get_posts( array(
			'post_type'      => 'amps_panier',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_panier_date',
						'value'   => array( Amapress::start_of_day( $dist_date ), Amapress::end_of_day( $dist_date ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'amapress_panier_date_subst',
						'value'   => array( Amapress::start_of_day( $dist_date ), Amapress::end_of_day( $dist_date ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
				)
			)
		) );

		return array_map( function ( $r ) {
			return new AmapressPanier( $r );
		}, $res );
	}

	public
	static function getPanierQuantiteTable(
		$quant_id, $quantites, $options = array()
	) {
		$options = wp_parse_args( $options,
			array(
				'show_price'    => false,
				'show_quantite' => true,
				'empty_desc'    => 'Pas de produit à cette distribution',
			) );
		$columns = array();
		if ( $options['show_quantite'] ) {
			$columns[] = array(
				'title' => 'Quantité',
				'data'  => array(
					'_'    => 'quantite',
					'sort' => 'quantite',
				)
			);
		}
//        $columns[] = array(
//            'title' => '',
//            'data' => array(
//                '_' => 'produit_photo',
//                'sort' => 'produit_photo',
//            )
//        );
		$columns[] = array(
			'title' => 'Produit',
			'data'  => array(
				'_'    => 'produit',
				'sort' => 'produit',
			)
		);
		if ( $options['show_price'] ) {
			$columns[] = array(
				'title' => 'Prix',
				'data'  => array(
					'_'    => 'price',
					'sort' => 'price',
				)
			);
		}
		if ( empty( $quantites ) ) {
			return '<div class="panier-vide">' . esc_html( $options['empty_desc'] ) . '</div>';
		} else {
			return amapress_get_datatable( 'liste-emargement-contrat-variable-' . $quant_id, $columns, $quantites,
				array(
					'paging'    => false,
					'searching' => false,
					"language"  => array( 'emptyTable' => $options['empty_desc'] )
				),
				array(
					Amapress::DATATABLES_EXPORT_EXCEL,
					Amapress::DATATABLES_EXPORT_PDF,
					Amapress::DATATABLES_EXPORT_PRINT
				) );
		}
	}

	public
	static function getPanierContentHtml(
		$panier_id, $lieu_id = null
	) {
		global $post;
		if ( ! $panier_id && $post && $post->post_type == 'amps_panier' ) {
			$panier_id = $post->ID;
		}
		$panier = get_post( intval( $panier_id ) );
		if ( ! $panier ) {
			return '';
		}

		$pani = new AmapressPanier( $panier );
		if ( $pani->getContrat_instance() == null ) {
			return 'Merci de sélectionner le contrat associé';
		}
		$quantites = AmapressContrats::get_contrat_quantites( $pani->getContrat_instance()->ID );

		$produits_in_panier = array();
		if ( $pani->getContrat_instance()->isPanierVariable() ) {
			$adhs = AmapressContrats::get_user_active_adhesion( null, $pani->getContrat_instance()->ID, $pani->getDate() );
			if ( amapress_is_user_logged_in() && ! empty( $adhs ) ) {
				$produits_objects = array();
				/** @var AmapressAdhesion $adh */
				foreach ( $adhs as $adh ) {
					foreach ( $adh->getVariables_Contrat_quantites( $pani->getDate() ) as $quant ) {
						$quantite = $quant['quantite'];
						/** @var AmapressContrat_quantite $contrat_quantite */
						$contrat_quantite = $quant['contrat_quantite'];

						$produits_in_panier = array_merge( $produits_in_panier, $contrat_quantite->getProduitsIds() );

						$produits_objects[] = array(
							'produit'  => '<img class="panier-produit-photo" alt="' . esc_attr( $contrat_quantite->getTitle() ) . '" src="' . amapress_get_avatar_url( $contrat_quantite->ID, null, 'produit-thumb', 'default_contrat.jpg' ) . '" />' . esc_html( $contrat_quantite->getTitle() ),
							'price'    => $contrat_quantite->getPrix_unitaire(),
							'quantite' => $contrat_quantite->formatValue( $quantite ),
						);
					}
				}
				echo '<h4>Les produits de cette livraison</h4>';
				echo self::getPanierQuantiteTable( 'all', $produits_objects );
			} else {
				$produits_objects = array();
				foreach ( AmapressContrats::get_contrat_quantites( $pani->getContrat_instance()->ID ) as $contrat_quantite ) {
					$produits_objects[] = array(
						'produit'  => '<img class="panier-produit-photo" alt="' . esc_attr( $contrat_quantite->getTitle() ) . '" src="' . amapress_get_avatar_url( $contrat_quantite->ID, null, 'produit-thumb', 'default_contrat.jpg' ) . '" />' . esc_html( $contrat_quantite->getTitle() ),
						'price'    => $contrat_quantite->getPrix_unitaire(),
						'quantite' => $contrat_quantite->getPriceUnitDisplay(),
					);
				}
				echo '<h4>Les produits disponibles pour ce contrat</h4>';
				echo self::getPanierQuantiteTable( 'public', $produits_objects );
			}
		} else {
			$dist_is_today = Amapress::start_of_day( $pani->getDate() ) == Amapress::start_of_day( amapress_time() );
			$dist_is_after = Amapress::start_of_day( $pani->getDate() ) >= Amapress::start_of_day( amapress_time() );

			$produits         = $pani->getSelectedProduits();
			$produits_objects = array();
			foreach ( $produits as $produit ) {
				$produits_abo = Amapress::get_post_meta_array( intval( $panier_id ), 'amapress_panier_produits_' . $produit->ID . '_quants' );
				$u            = '';
				foreach ( array( 'unit' => 'Unité', 'kg' => 'kg' ) as $unit => $unit_label ) {
					if ( $unit == $produits_abo['unit'] ) {
						$u = $unit_label;
					}
				}
				foreach ( $quantites as $quantite ) {
					if ( ! isset( $produits_objects[ $quantite->ID ] ) ) {
						$produits_objects[ $quantite->ID ] = array();
					}
					$produits_objects[ $quantite->ID ][] = array(
						'produit'  => '<img class="panier-produit-photo" alt="' . esc_attr( $produit->getTitle() ) . '" src="' . amapress_get_avatar_url( $quantite->ID, null, 'produit-thumb', 'default_produit.jpg' ) . '" />' . '<a href="' . esc_attr( $quantite->getPermalink() ) . '">' . esc_html( $quantite->getTitle() ) . '</a>',
						'price'    => $quantite->getPrix_unitaire(),
						'quantite' => $produits_abo[ $quantite->ID ] . $u,
					);
				}
			}

			ob_start();

			$user_quantites = null;
			if ( amapress_is_user_logged_in() ) {
				$user_quantites = array_map( function ( $v ) {
					return $v->ID;
				}, AmapressContrats::get_user_active_adhesion_quantites( null, null, $pani->getDate() ) );
			}

			foreach ( $quantites as $quantite ) {
				if ( $user_quantites && ! in_array( $quantite->ID, $user_quantites ) ) {
					continue;
				}
				$url = amapress_get_avatar_url( $quantite->ID, null, 'produit-thumb', 'default_contrat.jpg' );
				echo '<h3><img class="dist-panier-quantite-img" src="' . $url . '" alt="" /> ' . esc_html( $quantite->getTitle() );
//                if (amapress_is_user_logged_in()) {
//                    if ($dist_is_after && !$dist_is_today) {
//                        $inter_status = self::isIntermittent($panier_id, $pani->getContrat_instance()->ID, $lieu_id);
//                        if ($inter_status) {
//                            if ($inter_status == 'to_exchange') {
//                                amapress_echo_button('Panier sur la liste d\'intermittence', Amapress::getPageLink('mes-paniers-intermittents-page'),
//                                    'fa-fa', false, null, 'amap-button-echanger');
//                            } else {
//                                amapress_echo_button('Panier échangé', Amapress::getPageLink('mes-paniers-intermittents-page'),
//                                    'fa-fa', false, null, 'amap-button-echanger');
//                            }
//                        } else {
//                            amapress_echo_button('Céder mon panier', amapress_action_link($panier_id, 'echanger', array($quantite->getPost()->post_name)),
//                                'fa-fa', false, 'Etes-vous sûr de vouloir céder votre panier ?', 'amap-button-echanger');
//                        }
//                    }
//                }
				echo '</h3>';
				echo '<div class="dist-panier-quantite-content">';

				echo '<p class="panier-quantite-description">' . $quantite->getDescription() . '</p>';

				if ( isset( $produits_objects[ $quantite->ID ] ) ) {
					echo self::getPanierQuantiteTable( $quantite->ID, $produits_objects[ $quantite->ID ] );

					foreach ( $produits_objects[ $quantite->ID ] as $prod ) {
						$produits_in_panier[] = $prod['produit']->ID;
					}
				} else {
					echo self::getPanierQuantiteTable( $quantite->ID, array(), array( 'empty_desc' => 'Contenu du panier de cette distribution non rempli.' ) );
					if ( amapress_current_user_can( 'edit_panier' ) ) {
						echo '<br/>' . amapress_get_button( 'Renseigner le contenu du panier', admin_url( "post.php?post=$panier_id&action=edit" ), 'fa-fa' );
					}
				}

//                    $arr = isset($produits_objects[$quantite->ID]) ? $produits_objects[$quantite->ID] : null;
//                    if (!$arr) $arr = array();
//                    echo amapress_generic_gallery($arr, 'panier', 'produit_panier_cell', $panier_empty);

				echo '</div>';
			}
			//$quantites = array_filter($quantites, '');
//            } else {
//                foreach ($quantites as $quantite) {
//                    $url = amapress_get_avatar_url($quantite->ID, null, 'produit-thumb', 'default_contrat.jpg');
//                    echo '<h3><img class="dist-panier-quantite-img" src="' . $url . '" alt="" /> ' . esc_html($quantite->post_title) . '</h3>';
//                    if (isset($produits_objects[$quantite->ID])) {
//                        echo amapress_generic_gallery($produits_objects[$quantite->ID], 'panier', 'produit_panier_cell', $panier_empty);
//
//                        foreach ($produits_objects[$quantite->ID] as $prod)
//                            $produits_in_panier[] = $prod['produit']->ID;
//                    }
//                }
//            }
		}

		$produits_in_panier = implode( ',', array_unique( $produits_in_panier ) );
		if ( ! empty( $produits_in_panier ) ) {
			echo '<h3>' . amapress_get_font_icon( 'fa-fa' ) . ' Recettes associées</h3>';
			$recette_edit_link = ( amapress_current_user_can( 'publish_recette' ) ?
				amapress_get_button( 'Publier une nouvelle recette', admin_url( "post-new.php?post_type=amps_recette" ), 'fa-fa' ) :
				amapress_get_button( 'Proposer une nouvelle recette', get_post_permalink( Amapress::getOption( 'publier-recette-page' ) ), 'fa-fa' ) );
			$recette_empty     = '<span class="recette-empty">Pas de recettes pour les produits présents dans le panier</span> ' . $recette_edit_link;
			$no_recettes       = urlencode( $recette_empty );
			echo do_shortcode( "[recettes produits=$produits_in_panier if_empty=$no_recettes]" );
		}

		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}
}
