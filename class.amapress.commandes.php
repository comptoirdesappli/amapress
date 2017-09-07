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
class AmapressCommandes {
	public static $initiated = false;

	//private static $vp = null;

	public static function init() {
		//if (!self::$vp) self::$vp = new Virtual_Themed_Pages_BC();
		//self::$vp->add('#/commande#i', array('AmapressCommandes','virtual_commandes'));
		//self::$vp->add('#/m(a|es)-commande#i', array('AmapressCommandes','virtual_user_commandes'));
		add_shortcode( 'commandes', array( 'AmapressCommandes', 'commandes_shortcode' ) );
		add_shortcode( 'commande', array( 'AmapressCommandes', 'commande_shortcode' ) );
		add_shortcode( 'user_commandes', array( 'AmapressCommandes', 'user_commandes_shortcode' ) );
		add_shortcode( 'user_commande', array( 'AmapressCommandes', 'user_commande_shortcode' ) );

		add_action( 'admin_post_save_user_commande', array( 'AmapressCommandes', 'save_user_commande' ) );
	}

	static function virtual_user_commandes( $v, $url ) {
		if ( is_admin() ) {
			return;
		}
		if ( ! amapress_is_user_logged_in() ) {
			$v->redirect = '/wp-login.php';

			return;
		}
		// extract an id from the URL
		if ( preg_match( '#ma-commande-(\d{2}-\d{2}-\d{4})-a-(.+)#', $url, $m ) ) {
			$v->body  = do_shortcode( '[user_commande lieu="' . $m[2] . '" date="' . $m[1] . '"]' );
			$v->title = 'Livraisons de commandes du ' . $m[1] . ' à ' . Amapress::get_lieu_display( $m[2] );
		} else if ( preg_match( '#ma-commande-(\d{2}-\d{2}-\d{4})#', $url, $m ) ) {
			$v->body  = do_shortcode( '[user_commande date="' . $m[1] . '"]' );
			$v->title = 'Livraisons de commandes du ' . $m[1];
		} else if ( preg_match( '#mes-commandes-a-(.+)#', $url, $m ) ) {
			$v->body  = do_shortcode( '[user_commande lieu="' . $m[1] . '"]' );
			$v->title = 'Prochaines livraisons de commandes à "' . Amapress::get_lieu_display( $m[1] ) . '"';
		} else {
			$v->body  = do_shortcode( '[user_commandes]' );
			$v->title = 'Les prochaines livraisons de commandes';
		}
		// could wp_die() if id not extracted successfully...

		$v->template    = 'page'; // optional
		$v->subtemplate = 'page'; // optional
	}

	public static function user_commandes_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'count' => 10,
			'lieu'  => null,
		), $atts, 'user_commandes' );

		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$lieu     = $atts['lieu'];
		$lieu_ids = Amapress::get_lieu_ids();
		if ( $lieu ) {
			$lieu_id = Amapress::get_lieu_id( $lieu );
			if ( ! in_array( $lieu_id, $lieu_ids ) ) {
				return '';
			}
			$lieu_ids = array( $lieu_id );
		}

		$next_commandes = AmapressCommande::get_next_deliverable_commandes();
		ob_start();
		$posts = get_posts( array(
			'post_type'      => 'amps_user_commande',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'key'            => 'amapress_user_commande_date',
			'posts_per_page' => intval( $atts['count'] ),
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key_num' => 'amapress_user_commande_commande',
					'value'   => $next_commandes,
					'compare' => 'IN',
					'type'    => 'INT',
				),
				array(
					'key_num' => 'amapress_user_commande_amapien',
					'value'   => amapress_current_user_id(),
					'compare' => '=',
					'type'    => 'INT',
				),
			)
		) );
		foreach ( $posts as $post ) {
			AmapressCommandes::echoUserCommande( $post->ID );
		}

		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}

	public static function user_commande_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'lieu' => null,
			'date' => null,
		), $atts, 'user_commande' );

		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$lieu     = $atts['lieu'];
		$lieu_ids = Amapress::get_lieu_ids();
		if ( $lieu ) {
			$lieu_id = Amapress::get_lieu_id( $lieu );
			if ( ! in_array( $lieu_id, $lieu_ids ) ) {
				return '';
			}
			$lieu_ids = array( $lieu_id );
		}
		$date = array(
			'key'     => 'amapress_commande_date_distrib',
			'value'   => array( Amapress::start_of_week( amapress_time() ), Amapress::end_of_week( amapress_time() ) ),
			'compare' => 'BETWEEN',
			'type'    => 'INT'
		);
		if ( $atts['date'] ) {
			$dt   = DateTime::createFromFormat( 'd#m#Y', $atts['date'] )->getTimestamp();
			$date = array(
				'key'     => 'amapress_commande_date_distrib',
				'value'   => array( Amapress::start_of_week( $dt ), Amapress::end_of_week( $dt ) ),
				'compare' => 'BETWEEN',
				'type'    => 'INT'
			);
		}

		$next_commandes = array_map( 'Amapress::to_id', get_posts( array(
			'post_type'      => 'amps_commande',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'meta_key'       => 'amapress_commande_date_distrib',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				'relation' => 'AND',
				$date,
				array(
					'key'     => 'amapress_commande_lieu',
					'value'   => $lieu_ids,
					'compare' => 'IN',
					'type'    => 'INT',
				),
			)
		) ) );

		$posts = get_posts( array(
			'post_type'      => 'amps_user_commande',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'key'            => 'amapress_user_commande_date',
			'posts_per_page' => intval( $atts['count'] ),
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key_num' => 'amapress_user_commande_commande',
					'value'   => $next_commandes,
					'compare' => 'IN',
					'type'    => 'INT',
				),
				array(
					'key_num' => 'amapress_user_commande_amapien',
					'value'   => amapress_current_user_id(),
					'compare' => '=',
					'type'    => 'INT',
				),
			)
		) );
		if ( count( $posts ) == 0 ) {
			if ( ! $atts['date'] ) {
				return 'Pas de commande cette semaine';
			} else {
				return 'Pas de commande pour la date demandée : ' . $atts['date'];
			}
		}

		ob_start();
		foreach ( $posts as $post ) {
			AmapressCommandes::echoUserCommande( $post->ID );
		}
		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}

	static function virtual_commandes( $v, $url ) {
		if ( is_admin() ) {
			return;
		}
		if ( ! amapress_is_user_logged_in() ) {
			$v->redirect = '/wp-login.php';

			return;
		}
		// extract an id from the URL
		if ( preg_match( '#commande-(\d{2}-\d{2}-\d{4})-a-(.+)#', $url, $m ) ) {
			$v->body  = do_shortcode( '[commande lieu="' . $m[2] . '" date="' . $m[1] . '"]' );
			$v->title = 'Commandes du ' . $m[1] . ' à ' . Amapress::get_lieu_display( $m[2] );
		} else if ( preg_match( '#commande-(\d{2}-\d{2}-\d{4})#', $url, $m ) ) {
			$v->body  = do_shortcode( '[commande date="' . $m[1] . '"]' );
			$v->title = 'Commandes du ' . $m[1];
		} else if ( preg_match( '#commandes-a-(.+)#', $url, $m ) ) {
			$v->body  = do_shortcode( '[commande lieu="' . $m[1] . '"]' );
			$v->title = 'Prochaines commandes à "' . Amapress::get_lieu_display( $m[1] ) . '"';
		} else {
			$v->body  = do_shortcode( '[commandes]' );
			$v->title = 'Les prochaines commandes';
		}
		// could wp_die() if id not extracted successfully...

		$v->template    = 'page'; // optional
		$v->subtemplate = 'page'; // optional
	}

	public static function commandes_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'count' => 10,
			'lieu'  => null,
		), $atts, 'commandes' );

		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$lieu     = $atts['lieu'];
		$lieu_ids = AmapressUsers::get_current_user_lieu_ids();
		if ( $lieu ) {
			$lieu_id = Amapress::get_lieu_id( $lieu );
			if ( ! in_array( $lieu_id, $lieu_ids ) ) {
				return '';
			}
			$lieu_ids = array( $lieu_id );
		}

		ob_start();
		$posts = get_posts( array(
			'post_type'      => 'amps_commande',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'key'            => 'amapress_commande_date_debut',
			'posts_per_page' => intval( $atts['count'] ),
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key_num' => 'amapress_commande_date_distrib',
					'value'   => Amapress::start_of_day( amapress_time() ),
					'compare' => '>='
				),
				array(
					'key'     => 'amapress_commande_lieu',
					'value'   => $lieu_ids,
					'compare' => 'IN',
					'type'    => 'INT',
				),
			)
		) );
		foreach ( $posts as $post ) {
			AmapressCommandes::echoCommande( $post->ID );
		}

		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}

	public static function commande_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'lieu' => null,
			'date' => null,
		), $atts, 'commande' );

		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$lieu     = $atts['lieu'];
		$lieu_ids = Amapress::get_lieu_ids();
		if ( $lieu ) {
			$lieu_id = Amapress::get_lieu_id( $lieu );
			if ( ! in_array( $lieu_id, $lieu_ids ) ) {
				return '';
			}
			$lieu_ids = array( $lieu_id );
		}
		$date = array(
			'key'     => 'amapress_commande_date_distrib',
			'value'   => array( Amapress::start_of_week( amapress_time() ), Amapress::end_of_week( amapress_time() ) ),
			'compare' => 'BETWEEN',
			'type'    => 'INT'
		);
		if ( $atts['date'] ) {
			$dt   = DateTime::createFromFormat( 'd#m#Y', $atts['date'] )->getTimestamp();
			$date = array(
				'key'     => 'amapress_commande_date_distrib',
				'value'   => array( Amapress::start_of_week( $dt ), Amapress::end_of_week( $dt ) ),
				'compare' => 'BETWEEN',
				'type'    => 'INT'
			);
		}

		$posts = get_posts( array(
			'post_type'     => 'amps_commande',
			'orderby'       => 'meta_value_num',
			'order'         => 'ASC',
			'meta_key'      => 'amapress_commande_date_distrib',
			'post_per_page' => intval( $atts['count'] ),
			'meta_query'    => array(
				'relation' => 'AND',
				$date,
				array(
					'key'     => 'amapress_commande_lieu',
					'value'   => $lieu_ids,
					'compare' => 'IN',
					'type'    => 'INT',
				),
			)
		) );
		if ( count( $posts ) == 0 ) {
			if ( ! $atts['date'] ) {
				return 'Pas de commande cette semaine';
			} else {
				return 'Pas de commande pour la date demandée : ' . $atts['date'];
			}
		}

		ob_start();
		foreach ( $posts as $post ) {
			AmapressCommandes::echoCommande( $post->ID );
		}
		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}

	public static function get_commande_href( $id ) {
		return get_post_permalink( $id );
//		$date = get_post_meta($id,'amapress_commande_date', true);
//		return sprintf('/commande-%02d-%02d-%04d-a-%s',date('d',$date),date('m',$date),date('Y',$date),get_post(intval(get_post_meta($id,'amapress_commande_lieu',true)))->post_name);
	}

	public static function get_user_commande_href( $id ) {
		return get_post_permalink( $id );
//		$commande = get_post_meta($id,'amapress_user_commande_commande', true);
//		$date = get_post_meta($commande,'amapress_commande_date_distrib', true);
//		return sprintf('/ma-commande-%02d-%02d-%04d-a-%s',date('d',$date),date('m',$date),date('Y',$date),get_post(intval(get_post_meta($id,'amapress_commande_lieu',true)))->post_name);
	}

	public static function get_responsables( $dist_id ) {
		return Amapress::get_post_meta_array( $dist_id, 'amapress_commande_responsables' );
	}

	public static function get_required_responsables( $dist_id ) {
		$default              = get_option( 'amapress_nb_responsables' );
		$lieu_id              = intval( get_post_meta( $dist_id, 'amapress_commande_lieu', true ) );
		$dist_nb_responsables = intval( get_post_meta( $dist_id, 'amapress_commande_nb_responsables', true ) );
		$lieu_nb_responsables = intval( get_post_meta( $lieu_id, 'amapress_lieu_disribution_nb_responsables', true ) );
		if ( $dist_nb_responsables && $dist_nb_responsables > 0 ) {
			return $dist_nb_responsables;
		} else if ( $lieu_nb_responsables && $lieu_nb_responsables > 0 ) {
			return $lieu_nb_responsables;
		} else {
			return $default;
		}
	}

	public static function to_date( $s ) {
		$d = DateTime::createFromFormat( 'd#m#Y', trim( $s ) );

		return Amapress::start_of_day( $d->getTimestamp() );
	}

	public static function generate_commandes( $contrat_id, $from_now = true, $eval = false ) {
		$res      = array();
		$contrats = AmapressContrats::get_active_contrat_instances( $contrat_id );
		foreach ( $contrats as $contrat ) {
			$res[ $contrat->ID ] = array( 'missing' => array(), 'orphan' => array() );
			$liste_dates         = Amapress::get_array( get_post_meta( $contrat->ID, 'amapress_contrat_instance_commande_liste_dates', true ) );
			if ( $liste_dates ) {
				$liste_dates = array_map( array( 'AmapressCommandes', 'to_date' ), $liste_dates );
			} else {
				continue;
			}

			$lieux         = Amapress::get_array( get_post_meta( $contrat->ID, 'amapress_contrat_instance_lieux', true ) );
			$open_before   = intval( get_post_meta( $contrat->ID, 'amapress_contrat_instance_commande_open_before', true ) );
			$close_before  = intval( get_post_meta( $contrat->ID, 'amapress_contrat_instance_commande_close_before', true ) );
			$contrat_model = get_post( get_post_meta( $contrat->ID, 'amapress_contrat_instance_model', true ) );

			$now = Amapress::start_of_day( amapress_time() );
			foreach ( $liste_dates as $date ) {
				if ( $from_now && $date < $now ) {
					continue;
				}
				foreach ( $lieux as $lieu ) {
					$dists = get_posts( array(
						'post_type'      => 'amps_commande',
						'posts_per_page' => - 1,
						'meta_query'     => array(
							'relation' => 'AND',
							array(
								'key'     => 'amapress_commande_date_distrib',
								'value'   => array(
									Amapress::start_of_day( $date ),
									Amapress::end_of_day( $date )
								),
								'compare' => 'BETWEEN',
								'type'    => 'INT'
							),
							array(
								'key'     => 'amapress_commande_lieu',
								'value'   => intval( $lieu ),
								'compare' => '=',
								'type'    => 'INT'
							),
						),
					) );
					if ( count( $dists ) == 0 ) {
						// Gather post data.
						$lieu_post = get_post( $lieu );
						$my_post   = array(
							'post_title'   => sprintf( 'Commande de %s du %02d-%02d-%04d à %s',
								$contrat_model->post_title,
								date( 'd', $date ), date( 'm', $date ), date( 'Y', $date ),
								$lieu_post->post_title ),
							'post_type'    => 'amps_commande',
							'post_content' => '',
							'post_status'  => 'publish',
							'meta_input'   => array(
								'amapress_commande_lieu'             => $lieu,
								'amapress_commande_contrat_instance' => $contrat->ID,
								'amapress_commande_date_distrib'     => Amapress::start_of_day( $date ),
								'amapress_commande_date_debut'       => $open_before > 0 ? Amapress::add_days( Amapress::start_of_day( $date ), - $open_before ) : Amapress::start_of_day( amapress_time() ),
								'amapress_commande_date_fin'         => $close_before > 0 ? Amapress::add_days( Amapress::end_of_day( $date ), - $close_before ) : Amapress::end_of_day( $date ),
							),
						);

						$res[ $contrat->ID ]['missing'][] = array(
							'lieu' => $lieu,
							'date' => Amapress::start_of_day( $date )
						);

						// Insert the post into the database.
						if ( ! $eval ) {
							wp_insert_post( $my_post );
						}
					}
				}
			}

			$commandes = get_posts( array(
				'post_type'      => 'amps_commande',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'amapress_commande_contrat_instance',
						'value'   => $contrat->ID,
						'compare' => '=',
						'type'    => 'INT'
					),
				)
			) );
			foreach ( $commandes as $dist ) {
				$dist_date = Amapress::start_of_day( get_post_meta( $dist->ID, 'amapress_commande_date', true ) );
				$dist_lieu = intval( get_post_meta( $dist->ID, 'amapress_commande_lieu', true ) );
//                $dist_contrats = Amapress::get_post_meta_array($dist->ID,'amapress_commande_contrats');
				$rem = false;
				$add = false;
				if ( in_array( $dist_date, $liste_dates ) ) {
					if ( ! in_array( $dist_lieu, $lieux ) ) {
						$rem = true;
					}
				} else {
					$rem = true;
				}
				if ( $rem ) {
					$res[ $contrat->ID ]['orphan'][] = array( 'lieu' => $dist_lieu, 'date' => $dist_date );
				}
				//
				if ( ! $eval && $rem ) {
					update_post_meta( $dist->ID, 'amapress_commande_status', 'cancelled' );
				}
			}
		}

		return $res;
	}

	public static function save_user_commande() {
		if (
			! isset( $_POST['save_user_commande_nonce'] )
			|| ! wp_verify_nonce( $_POST['save_user_commande_nonce'], 'save_user_commande' )
		) {
			print 'Sorry, your nonce did not verify.';
			exit;
		} else {
			$commande_id = $_REQUEST['commande_id'];

			$commande      = intval( get_post_meta( $commande_id, 'amapress_user_commande_commande', true ) );
			$contrat       = intval( get_post_meta( $commande, 'amapress_commande_contrat_instance', true ) );
			$sel_produits  = Amapress::get_post_meta_array( $commande, 'amapress_commande_produits', true );
			$contrat_model = intval( get_post_meta( $contrat, 'amapress_contrat_instance_model', true ) );
			$producteur    = intval( get_post_meta( $contrat_model, 'amapress_contrat_producteur', true ) );

			if ( is_array( $sel_produits ) && count( $sel_produits ) > 0 ) {
				$sel_produits = array_map( 'intval', $sel_produits );
				$prods        = get_posts(
					array(
						'post_type'      => 'amps_produit',
						'posts_per_page' => - 1,
						'post__in'       => $sel_produits,
					)
				);
			} else {
				$prods = get_posts(
					array(
						'post_type'      => 'amps_produit',
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'key'   => 'amapress_produit_producteur',
								'value' => $producteur,
								'type'  => 'INT'
							),
						)
					)
				);
			}

			$cmd = array();
			foreach ( $prods as $prod ) {
				$k = 'amapress_commande_' . $prod->ID . '_quant';
				if ( isset( $_REQUEST[ $k ] ) ) {
					if ( ! isset( $cmd[ $prod->ID ] ) ) {
						$cmd[ $prod->ID ] = array();
					}
					$cmd[ $prod->ID ]['quant'] = $_REQUEST[ $k ];
				}
			}

			update_post_meta( $commande_id, 'amapress_user_commande_content', $cmd );
		}
	}

	public static function echoUserCommande( $dist_id ) {
		$commande      = intval( get_post_meta( $dist_id, 'amapress_user_commande_commande', true ) );
		$contrat       = intval( get_post_meta( $commande, 'amapress_commande_contrat_instance', true ) );
		$sel_produits  = Amapress::get_post_meta_array( $commande, 'amapress_commande_produits', true );
		$contrat_model = intval( get_post_meta( $contrat, 'amapress_contrat_instance_model', true ) );
		$producteur    = intval( get_post_meta( $contrat_model, 'amapress_contrat_producteur', true ) );

		if ( is_array( $sel_produits ) && count( $sel_produits ) > 0 ) {
			$sel_produits = array_map( 'intval', $sel_produits );
			$prods        = get_posts(
				array(
					'post_type'      => 'amps_produit',
					'posts_per_page' => - 1,
					'post__in'       => $sel_produits,
				)
			);
		} else {
			$prods = get_posts(
				array(
					'post_type'      => 'amps_produit',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						array(
							'key'   => 'amapress_produit_producteur',
							'value' => $producteur,
							'type'  => 'INT'
						),
					)
				)
			);
		}

		$dist_date = intval( get_post_meta( $commande, 'amapress_commande_date_distrib', true ) );
		$lieu      = get_post( get_post_meta( $commande, 'amapress_commande_lieu', true ) );
		$cmd       = Amapress::get_post_meta_array( $dist_date, 'amapress_user_commande_content' );
		?>

        <div class="commande">
            <h3>Commande du <a
                        href="<?php AmapressCommandes::get_commande_href( $dist_id ) ?>"><?php echo date_i18n( 'l j F Y', $dist_date ); ?></a>
                à <a href="<?php echo get_permalink( $lieu->ID ); ?>">"<?php echo $lieu->post_title; ?>"</a>
            </h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="save_user_commande"/>
                <input type="hidden" name="commande_id" value="<?php echo $dist_id; ?>"/>
				<?php wp_nonce_field( "save_user_commande", "save_user_commande_nonce" ) ?>
                <table>
                    <thead>
                    <tr>
                        <td>Produit</td>
                        <td>Quantité</td>
                        <td>Unité</td>
                        <td>Prix unitaire</td>
                        <td>Total</td>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $prods as $prod ) {
						$quant = isset( $cmd[ $prod->ID ] ) ? $cmd[ $prod->ID ]['quant'] : 0;
						$unit  = get_post_meta( $prod->ID, 'amapress_produit_unit', true );
						$price = get_post_meta( $prod->ID, 'amapress_produit_price', true );
						$var   = get_post_meta( $prod->ID, 'amapress_produit_quantite_variable', true );
						echo '<tr>';
						echo '<td><a href="' . get_post_permalink( $prod->ID ) . '">' . $prod->post_title . '</a></td>';
						if ( $var === true ) {
							echo '<td><input type="text" class="number" id="amapress_commande_' . $prod->ID . '_quant" name="amapress_commande_' . $prod->ID . '_quant" value="' . $quant . '" /></td>';
						} else {
							echo '<td><input type="text" class="digits" id="amapress_commande_' . $prod->ID . '_quant" name="amapress_commande_' . $prod->ID . '_quant" value="' . $quant . '" /></td>';
						}
						echo '<td id="amapress_commande_' . $prod->ID . '_unit" data-unit="' . $unit . '">' . $unit . '</td>';
						echo '<td id="amapress_commande_' . $prod->ID . '_price" data-price="' . $price . '">' . $price . '</td>';
						echo '<td id="amapress_commande_' . $prod->ID . '_total" ></td>';
						echo '</tr>';
					} ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td id="commande-total-price"></td>
                    </tr>
                    </tfoot>
                </table>
            </form>
        </div>
		<?php
	}

	public static function echoCommande( $dist_id ) {
		$dist_date           = intval( get_post_meta( $dist_id, 'amapress_commande_date_distrib', true ) );
		$responsables        = get_users( array( 'include' => array_map( 'intval', Amapress::get_post_meta_array( $dist_id, 'amapress_commande_responsables' ) ) ) );
		$responsables_names  = array_map( array( 'AmapressUsers', 'to_displayname' ), $responsables );
		$needed_responsables = AmapressCommandes::get_required_responsables( $dist_id );
		$lieu                = get_post( get_post_meta( $dist_id, 'amapress_commande_lieu', true ) );
		$contrat_instance    = intval( get_post_meta( $dist_id, 'amapress_commande_contrat_instance', true ) );

		$posts = get_posts( array(
			'post_type'      => 'amps_user_commande',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'key'            => 'amapress_user_commande_date',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key_num' => 'amapress_user_commande_commande',
					'value'   => $dist_id,
					'compare' => '=',
					'type'    => 'INT',
				),
				array(
					'key_num' => 'amapress_user_commande_amapien',
					'value'   => amapress_current_user_id(),
					'compare' => '=',
					'type'    => 'INT',
				),
			)
		) );

		$can_modify = get_post_meta( $contrat_instance, 'amapress_contrat_instance_commande_cannot_modify', true ) != true;
		$deb        = date_i18n( 'l j F Y', get_post_meta( $dist_id, 'amapress_commande_date_debut', true ) );
		$dt_fin     = intval( get_post_meta( $dist_id, 'amapress_commande_date_fin', true ) );
		$fin        = date_i18n( 'l j F Y', $dt_fin );
		if ( count( $posts ) > 0 ) {
			if ( $can_modify && Amapress::start_of_day( amapress_time() ) < $dt_fin ) {
				$cmd_link = '<a href="' . self::get_user_commande_href( $posts[0]->ID ) . 'Modifier ma commande</a>';
			} else {
				$cmd_link = '<a href="' . self::get_user_commande_href( $posts[0]->ID ) . 'Voir ma commande</a>';
			}
		} else {
			$cmd_link = '<a href="' . self::get_commande_href( $dist_id ) . '/commander">Passer commande</a>';
		}
		?>

        <div class="commande">
            <h3>Commande groupée du <a
                        href="<?php AmapressCommandes::get_commande_href( $dist_id ) ?>"><?php echo date_i18n( 'l j F Y', $dist_date ); ?></a>
                à <a href="<?php echo get_permalink( $lieu->ID ); ?>">"<?php echo $lieu->post_title; ?>"</a>
            </h3>
            <p><?php echo "Vous pouvez passer commande du $deb au $fin : $cmd_link" ?></p>
			<?php if ( count( $responsables ) > 0 ) { ?>
                <p>Responsables: <?php echo implode( ', ', $responsables_names ) ?></p>
			<?php } else { ?>
                <p>Aucun responsable</p>
			<?php } ?>
			<?php if ( count( $responsables ) < $needed_responsables ) { ?>
                <p>
                    Il manque encore <?php echo $needed_responsables - count( $responsables ) ?> responsable(s) de
                    commandes.
                    <br/>
                    <a href="<?php echo Amapress::get_inscription_link( $dist_id, 'dist_resp' ) ?>"
                       onclick="return confirm('Confirmez-vous votre inscription ?')">S'incrire</a>
                </p>
			<?php } ?>
        </div>
		<?php
	}
}
