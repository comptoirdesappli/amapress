<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'init', function () {
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_coords' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
				wp_die( 'Accès interdit' );
			}
		}
		$email          = sanitize_email( $_POST['email'] );
		$user_firt_name = sanitize_text_field( $_POST['first_name'] );
		$user_last_name = sanitize_text_field( $_POST['last_name'] );
		$user_address   = sanitize_textarea_field( $_POST['address'] );
		$user_phones    = sanitize_text_field( $_POST['tel'] );

		$user_id = amapress_create_user_if_not_exists( $email, $user_firt_name, $user_last_name, $user_address, $user_phones );
		if ( ! $user_id ) {
			wp_redirect_and_exit( add_query_arg( 'message', 'cannot_create_user' ) );
		}

		wp_redirect_and_exit(
			add_query_arg( [
				'step'    => 'contrats',
				'user_id' => $user_id,
			] )
		);
	}
} );

/**
 * @param $atts
 */
function amapress_self_inscription( $atts ) {
	$step = isset( $_GET['step'] ) ? $_GET['step'] : 'email';

	$atts = shortcode_atts(
		[
			'key'        => '',
			'for_logged' => 'false',
			'admin_mode' => 'false',
		]
		, $atts );

	$admin_mode = Amapress::toBool( $atts['admin_mode'] );
	$key        = $atts['key'];
	if ( $admin_mode && amapress_is_user_logged_in() && amapress_can_access_admin() ) {
		if ( ! isset( $_GET['step'] ) ) {
			$step = 'contrats';
		}
	} else if ( Amapress::toBool( $atts['for_logged'] ) ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '<div class="alert alert-danger">Accès interdit</div>';
		}
		if ( ! isset( $_GET['step'] ) ) {
			$step = 'coords_logged';
		}
	} else {
		if ( empty( $key ) || empty( $_GET['key'] ) || $_GET['key'] != $key ) {
			return '<div class="alert alert-danger">Accès interdit</div>';
		}
		if ( amapress_can_access_admin() ) {
			echo '<div class="alert alert-info">Pour donner accès à cet assistant aux nouveaux amapiens, veuillez leur envoyer le lien suivant : <pre>' .
			     add_query_arg( 'key', $key, get_permalink() ) . '</pre></div>';
		}
	}

	ob_start();


	$min_total             = 0;
	$subscribable_contrats = AmapressContrats::get_subscribable_contrat_instances_by_contrat( null );
	if ( ! $admin_mode ) {
		$subscribable_contrats = array_filter( $subscribable_contrats, function ( $c ) {
			/** @var AmapressContrat_instance $c */
			return $c->canSelfSubscribe();
		} );
	}
//	$subscribable_contrats     = array_filter( $subscribable_contrats, function ( $c ) {
//		/** @var AmapressContrat_instance $c */
//		return ! $c->isPanierVariable();
//	} );
	$subscribable_contrats_ids = array_map( function ( $c ) {
		return $c->ID;
	}, $subscribable_contrats );
	$principal_contrat         = null;
	$principal_contrats        = [];
	$min_contrat_date          = - 1;
	$max_contrat_date          = - 1;
	foreach ( $subscribable_contrats as $c ) {
		if ( $c->isPrincipal() ) {
			$principal_contrat    = $c;
			$principal_contrats[] = $c;
		}
		if ( $min_contrat_date < 0 ) {
			$min_contrat_date = $c->getDate_debut();
		}
		if ( $max_contrat_date < 0 ) {
			$max_contrat_date = $c->getDate_fin();
		}
		if ( $min_contrat_date > $c->getDate_debut() ) {
			$min_contrat_date = $c->getDate_debut();
		}
		if ( $max_contrat_date < $c->getDate_fin() ) {
			$max_contrat_date = $c->getDate_fin();
		}
	}
	if ( empty( $subscribable_contrats ) ) {
		wp_die( 'Aucun contrat ne permet l\'inscription en ligne. Veuillez activer l\'inscription en ligne depuis ' . admin_url( 'edit.php?post_type=amps_contrat_inst' ) );
	}
	if ( empty( $principal_contrat ) ) {
		wp_die( 'Aucun contrat principal. Veuillez définir un contrat principal depuis ' . admin_url( 'edit.php?post_type=amps_contrat_inst' ) );
	}

	if ( ! $admin_mode && count( $principal_contrats ) > 1 ) {
		wp_die( 'Il y a plusieurs contrat principaux. Veuillez vérifier la configuration (erreur de dates d\'ouverture/clôture) : <br/>' .
		        implode( '<br/>', array_map( function ( $c ) {
			        /** @var AmapressContrat_instance $c */
			        return Amapress::makeLink( $c->getAdminEditLink(), $c->getTitle(), true, true );
		        }, $principal_contrats ) ) );
	}

	$contrats_step_url = add_query_arg( 'step', 'contrats', remove_query_arg( [ 'contrat_id', 'message' ] ) );

	if ( isset( $_GET['contrat_id'] ) && isset( $_GET['user_id'] ) ) {
		$user_id    = intval( $_GET['user_id'] );
		$contrat_id = intval( $_GET['contrat_id'] );

		$adhs             = AmapressAdhesion::getUserActiveAdhesions( $user_id );
		$adhs             = array_filter( $adhs,
			function ( $adh ) use ( $subscribable_contrats_ids ) {
				/** @var AmapressAdhesion $adh */
				return in_array( $adh->getContrat_instanceId(), $subscribable_contrats_ids );
			} );
		$adhs_contrat_ids = array_map( function ( $a ) {
			/** @var AmapressAdhesion $a */
			return $a->getContrat_instance()->ID;
		}, $adhs );

		if ( in_array( $contrat_id, $adhs_contrat_ids ) ) {
			$amapien = AmapressUser::getBy( $user_id );
			if ( $admin_mode ) {
				return '<p>' . esc_html( $amapien->getDisplayName() ) . ' déjà une inscription à ce contrat. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Contrats</a></p>';
			} else {
				wp_die( '<p>Vous avez déjà une inscription à ce contrat. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Contrats</a></p>' );
			}
		}
	}

//	$start_step_url = add_query_arg( 'step', 'email', remove_query_arg( [ 'contrat_id', 'message' ] ) );

	if ( ! empty( $_GET['message'] ) ) {
		$message = '';
		switch ( $_GET['message'] ) {
			case 'empty_email':
				$message = 'Le mail saisi est invalide';
				break;
			case 'cannot_create_user':
				$message = 'Impossible de créer votre compte.';
				break;
		}
		echo '<div class="alert alert-danger">' . $message . '</div>';
	}

	if ( 'email' == $step ) {
		?>
        <h2>Bienvenue dans l'assistant d'inscription aux contrats producteurs
            de <?php echo get_bloginfo( 'name' ); ?></h2>
        <h4>Étape 1/7 : Email</h4>
        <form method="post" action="<?php echo esc_attr( add_query_arg( 'step', 'coords' ) ) ?>" id="inscr_email"
              class="amapress_validate">
            <label for="email">Pour démarrer votre inscription à l’Amap pour la
                saison <?php echo date_i18n( 'm/Y', $min_contrat_date ) . '/' . date_i18n( 'm/Y', $max_contrat_date ) ?>
                , renseignez votre
                adresse mail :</label>
            <input id="email" name="email" type="text" class="email required" placeholder="email"/>
            <input type="submit" value="Valider" class="btn btn-default"/>
        </form>
		<?php
	} else if ( 'coords' == $step || 'coords_logged' == $step ) {
		if ( 'coords_logged' == $step && amapress_is_user_logged_in() ) {
			$email = wp_get_current_user()->user_email;
		} else {
			$email = sanitize_email( $_POST['email'] );
			if ( empty( $email ) ) {
				wp_die( 'Aucun email saisi' );
			}
		}

		$user           = get_user_by( 'email', $email );
		$user_firt_name = '';
		$user_last_name = '';
		$user_address   = '';
		$user_phones    = '';
		$user_message   = 'Vous êtes nouveau dans l\'AMAP (si ce n\'est pas le cas c\'est que vous avez saisi une adresse email inconnue). Complétez vos coordonnées :';
		if ( $user ) {
			$amapien        = AmapressUser::getBy( $user );
			$user_message   = 'Vous êtes déjà membre de l’Amap, vérifiez vos coordonnées :';
			$user_firt_name = $user->first_name;
			$user_last_name = $user->last_name;
			$user_address   = $amapien->getFormattedAdresse();
			$user_phones    = implode( '/', $amapien->getPhoneNumbers() );
		}
		?>
        <h4>Étape 2/7 : Coordonnées</h4>
        <p><?php echo $user_message; ?></p>
        <form method="post" id="inscr_coords" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_coords' ) ) ?>">
            <input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>"/>
            <input type="hidden" name="inscr_assistant" value="validate_coords"/>
            <input type="hidden" name="inscr_key" value="<?php echo esc_attr( $key ); ?>"/>
            <table style="min-width: 50%">
                <tr>
                    <th style="text-align: left; width: auto"><label style="width: 10%" for="email">Email : </label>
                    </th>
                    <td><span style="width: 100%"><?php echo esc_html( $email ) ?></span></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="last_name">Nom* : </label></th>
                    <td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required"
                               value="<?php echo esc_attr( $user_last_name ) ?>"/>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="first_name">Prénom* : </label></th>
                    <td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required"
                               value="<?php echo esc_attr( $user_firt_name ) ?>"/>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="tel">Téléphone : </label></th>
                    <td><input style="width: 100%" type="text" id="tel" name="tel" class=""
                               value="<?php echo esc_attr( $user_phones ) ?>"/>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="address">Adresse : </label></th>
                    <td><textarea style="width: 100%" rows="8" id="address" name="address"
                                  class=""><?php echo esc_textarea( $user_address ); ?></textarea>
                </tr>
            </table>
            <input style="min-width: 50%" type="submit" class="btn btn-default" value="Valider"/>
        </form>
		<?php
	} else if ( 'contrats' == $step ) {
		$user_id               = intval( $_GET['user_id'] );
		$has_principal_contrat = false;

		$adhs = AmapressAdhesion::getUserActiveAdhesions( $user_id );
		$adhs = array_filter( $adhs,
			function ( $adh ) use ( $subscribable_contrats_ids ) {
				/** @var AmapressAdhesion $adh */
				return in_array( $adh->getContrat_instanceId(), $subscribable_contrats_ids );
			} );
		foreach ( $adhs as $adh ) {
			if ( $adh->getContrat_instance()->isPrincipal() ) {
				$has_principal_contrat = true;
			}
		}
		usort( $adhs, function ( $a, $b ) {
			return strcmp( $a->getTitle(), $b->getTitle() );
		} );
		$amapien = AmapressUser::getBy( $user_id );
		if ( ! $admin_mode ) {
			echo '<h4>Étape 3/7 : les contrats</h4>';
		} else {
			echo '<h4>Les contrats de ' . esc_html( $amapien->getDisplayName() ) . '</h4>';
		}
		echo '<p>Ci-dessous nos contrats accessibles en ligne.</p>';
		if ( ! $has_principal_contrat ) { ?>
            <p>Vous devez d’abord vous inscrire au contrat
                <strong><?php echo esc_html( $principal_contrat->getTitle() ); ?></strong>
                avant d'accéder aux autres contrats de l'AMAP. Vous pouvez vous y inscrire ci-dessous :</p>
		<?php } else if ( ! empty( $adhs ) ) {
			if ( ! $admin_mode ) {
				echo '<p>Vos contrats :</p>';
			} else {
				echo '<p>Ses contrats :</p>';
			}
			echo '<ul style="list-style-type: circle">';
			foreach ( $adhs as $adh ) {
				if ( $admin_mode ) {
					echo '<li style="margin-left: 35px">' . esc_html( $adh->getTitle() ) . ' (' . Amapress::makeLink( $adh->getAdminEditLink(), 'Editer', true, true ) . ')</li>';
				} else {
					echo '<li style="margin-left: 35px">' . esc_html( $adh->getTitle() ) . '</li>';
				}
			}
			echo '</ul>';
			if ( ! $admin_mode ) {
				echo '<p>Vous pouvez vous inscrire aux autres contrats ci-dessous :</p>';
			} else {
				echo '<p>Vous pouvez l\'inscrire aux autres contrats ci-dessous :</p>';
			}
		} else {
			if ( ! $admin_mode ) {
				echo '<p>Vous n\'avez pas encore de contrats</p>';
				echo '<p>Vous pouvez vous inscrire aux contrats ci-dessous :</p>';
			} else {
				echo '<p>Il n\'a pas encore de contrats</p>';
				echo '<p>Vous pouvez l\'inscrire aux autres contrats ci-dessous :</p>';
			}
		}

		$adhs_contrat_ids           = array_map( function ( $a ) {
			/** @var AmapressAdhesion $a */
			return $a->getContrat_instance()->ID;
		}, $adhs );
		$user_subscribable_contrats = array_filter( $subscribable_contrats, function ( $c ) use ( $adhs_contrat_ids ) {
			return ! in_array( $c->ID, $adhs_contrat_ids );
		} );
		if ( ! $has_principal_contrat ) {
			$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $principal_contrat ) {
				return $c->ID == $principal_contrat->ID;
			} );
		}
		if ( ! empty( $user_subscribable_contrats ) ) {
			echo '<ul style="list-style-type: circle">';
			foreach ( $user_subscribable_contrats as $contrat ) {
				$inscription_url = add_query_arg( [
					'step'       => 'inscr_contrat_date_lieu',
					'contrat_id' => $contrat->ID
				] );
				if ( $admin_mode ) {
					echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ' (' . Amapress::makeLink( $contrat->getAdminEditLink(), 'Editer', true, true ) . ') : <br/><a class="button button-secondary" href="' . esc_attr( $inscription_url ) . '">Ajouter une inscription</a></li>';
				} else {
					echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ' (' . $contrat->getModel()->linkToPermalinkBlank( 'plus d\'infos' ) . ') : <br/><a class="btn btn-default" href="' . esc_attr( $inscription_url ) . '">s\'inscrire</a></li>';
				}
			}
			echo '</ul>';
		} else {
			if ( ! $admin_mode ) {
				echo '<p>Vous êtes inscrit à tous les contrats accessibles en ligne</p>';
			} else {
				echo '<p>Il est inscrit à tous les contrats</p>';
			}
		}


	} else if ( 'inscr_contrat_date_lieu' == $step ) {
		$next_step_url = add_query_arg( 'step', 'inscr_contrat_engage' );
		$user_id       = intval( $_GET['user_id'] );
		$contrat_id    = intval( $_GET['contrat_id'] );
		$contrat       = AmapressContrat_instance::getBy( $contrat_id );

		?>
        <h4>Étape 4/7 : Date et lieux</h4>
        <p style="margin-bottom: 0">A quelle date souhaitez vous faire commencer l'inscription au
            contrat <?php echo esc_html( $contrat->getTitle() ); ?></p>
		<?php
		$dates = $contrat->getListe_dates();
		$dates = array_filter( $dates, function ( $d ) use ( $contrat ) {
			return Amapress::end_of_day( amapress_time() ) < $d && $d < $contrat->getDate_cloture();
		} );
		?>
        <form action="<?php echo $next_step_url; ?>" method="post">
            <label for="start_date">Date de début</label>
            <select name="start_date" id="start_date" class="required">
				<?php
				foreach ( $dates as $date ) {
					echo '<option value="' . esc_attr( $date ) . '">' . esc_html( date_i18n( 'd/m/Y', $date ) ) . '</option>';
				}
				?>
            </select>
			<?php
			$lieux = Amapress::get_lieux();
			if ( count( $lieux ) > 1 ) {
				if ( ! $admin_mode ) {
					echo '<p>Veuillez chosir votre lieu de distribution :</p>';
				} else {
					echo '<p>Veuillez chosir son lieu de distribution :</p>';
				}
				foreach ( $lieux as $lieu ) {
					$lieu_id    = $lieu->ID;
					$lieu_title = $lieu->linkToPermalinkBlank( esc_html( $lieu->getLieuTitle() ) ) . '(' . esc_html( $lieu->getFormattedAdresse() ) . ')';
					echo "<input id='lieu-$lieu_id' name='lieu_id' value='' type='radio' class='required' /><label for='lieu-$lieu_id'>$lieu_title</label>";
				}
			} else {
				echo '<p>La distribution s\'effectue à ' . esc_html( $lieux[0]->getLieuTitle() ) . '</p>';
				echo '<input name="lieu_id" value="' . $lieux[0]->ID . '" type="hidden" />';
			}
			//			foreach ( $dates as $date ) {
			//				echo '<option value="' . esc_attr( $date ) . '">' . esc_html( date_i18n( 'd/m/Y', $date ) ) . '</option>';
			//			}
			?>
            <input type="submit" value="Valider" class="btn btn-default"/>
        </form>
		<?php
	} else if ( 'inscr_contrat_engage' == $step ) {
		$user_id    = intval( $_GET['user_id'] );
		$contrat_id = intval( $_GET['contrat_id'] );
		$lieu_id    = intval( $_REQUEST['lieu_id'] );
		$start_date = intval( $_REQUEST['start_date'] );

		$next_step_url = add_query_arg( [
			'step'       => 'inscr_contrat_paiements',
			'start_date' => $start_date,
			'lieu_id'    => $lieu_id
		] );

		$contrat = AmapressContrat_instance::getBy( $contrat_id );
//		if ( ! $contrat->hasOnlineContrat() ) {
//			wp_safe_redirect( $next_step_url );
//		}

		$dates         = $contrat->getListe_dates();
		$dates         = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return $d >= $start_date;
		} );
		$dates_factors = 0;
		foreach ( $dates as $d ) {
			$dates_factors += $contrat->getDateFactor( $d );
		}

		?>
        <h4>Étape 5/7 : Panier - <?php echo esc_html( $contrat->getTitle() ); ?></h4>
		<?php
		$min_total = $contrat->getMinEngagement();

		//TODO lien vers contrat PDF ?
		echo $contrat->getOnlineContrat();
		echo '<p>Il reste “<strong>' . count( $dates ) . '</strong>” distributions avant la fin de la saison : ' . esc_html( implode( ', ', array_map( function ( $d ) {
				return date_i18n( 'd/m/Y', $d );
			}, $dates ) ) ) . '</p>';
		if ( $contrat->isQuantiteMultiple() || $contrat->isPanierVariable() ) {
			echo '<p>Composez votre panier :</p>';
		} else {
			echo '<p>Choisissez la quantité ou la taille de votre panier :</p>';
		}
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		if ( $contrat->isPanierVariable() ) {
			$columns = array(
				array(
					'title' => 'Produit',
					'data'  => 'produit',
				),
			);
			foreach ( $dates as $date ) {
				$columns[] = array(
					'title' => date_i18n( 'd/m/y', $date ),
					'data'  => 'd-' . $date,
				);
			}

			$data = array();
			foreach ( AmapressContrats::get_contrat_quantites( $contrat->ID ) as $quant ) {
				$row     = array(
					'produit' => esc_html( $quant->getTitle() ),
				);
				$options = $quant->getQuantiteOptions();
				if ( ! isset( $options['0'] ) ) {
					$options = [ '0' => '0' ] + $options;
				}
				foreach ( $dates as $date ) {
					$price_unit = esc_attr( $quant->getPrix_unitaire() );
					$ed         = '';
					$ed         .= "<select data-price='0' data-price-unit='$price_unit' name='panier_vars[$date][{$quant->ID}]' id='panier_vars-$date-{$quant->ID}' class='quant-var'>";
					$ed         .= tf_parse_select_options( $options, null, false );
					$ed         .= '</select>';
					if ( $quant->getAvailFrom() && $quant->getAvailTo() ) {
						if ( $date < Amapress::start_of_day( $quant->getAvailFrom() ) || $date > Amapress::end_of_day( $quant->getAvailTo() ) ) {
							$ed = '<span class="contrat_panier_vars-na">NA</span>';
						}
					}
					$row[ 'd-' . $date ] = $ed;
				}
				$data[] = $row;
			}

			echo amapress_get_datatable( 'quant-commandes', $columns, $data, array(
				'bSort'        => true,
				'paging'       => false,
				'searching'    => true,
				'bAutoWidth'   => true,
				'responsive'   => false,
				'init_as_html' => true,
				'scrollX'      => true,
				'fixedColumns' => array( 'leftColumns' => 1 ),
			) );
		} else {
			$contrat_quants = AmapressContrats::get_contrat_quantites( $contrat->ID );
			foreach ( $contrat_quants as $quantite ) {
				$quant_var_editor   = '';
				$id_quant           = 'quant' . $quantite->ID;
				$id_factor          = 'factor' . $quantite->ID;
				$id_price           = 'price' . $quantite->ID;
				$price              = $dates_factors * $quantite->getPrix_unitaire();
				$price_compute_text = esc_html( $dates_factors ) . ' x ' . esc_html( $quantite->getPrix_unitaire() ) . '€';
				if ( $contrat->isQuantiteVariable() ) {
					$quant_var_editor .= "<select id='$id_factor' class='quant-factor' data-quant-id='$id_quant' data-price-id='$id_price' data-price-unit='$price' name='factors[{$quantite->ID}]' style='display: inline-block'>";
					$quant_var_editor .= tf_parse_select_options(
						$quantite->getQuantiteOptions(),
						null,
						false );
					$quant_var_editor .= '</select>';
				}

				$type = $contrat->isQuantiteMultiple() ? 'checkbox' : 'radio';
				echo '<p><label for="' . $id_quant . '">
			<input id="' . $id_quant . '" name="quants[]" class="quant" value="' . $quantite->ID . '" type="' . $type . '" data-factor-id="' . $id_factor . '" data-price="' . $price . '"/> 
			' . $quant_var_editor . ' ' . esc_html( $quantite->getTitle() ) . ' ' . $price_compute_text . ' = <span id="' . $id_price . '">' . $price . '</span>€</label></p>';
			}
		}
		echo '<p>Total: <span id="total">0</span>€</p>';
		echo '<p><input type="submit" class="btn btn-default" value="Valider" /></p>';
		echo '</form>';

	} else if ( 'inscr_contrat_paiements' == $step ) {
		$user_id    = intval( $_GET['user_id'] );
		$contrat_id = intval( $_GET['contrat_id'] );
		$start_date = intval( $_REQUEST['start_date'] );

		$contrat       = AmapressContrat_instance::getBy( $contrat_id );
		$next_step_url = add_query_arg( [ 'step' => 'inscr_contrat_create' ] );

		echo '<h4>Étape 6/7 : Réglement</h4>';
		if ( $contrat->isPanierVariable() ) {
			$panier_vars = isset( $_POST['panier_vars'] ) ? $_POST['panier_vars'] : [];

			$total         = 0;
			$chosen_quants = [];
			foreach ( $panier_vars as $date_k => $quant_factors ) {
				$date_values = [];
				foreach ( $quant_factors as $quant_k => $factor_v ) {
					$q_id   = intval( $quant_k );
					$factor = floatval( $factor_v );
					if ( $factor <= 0 ) {
						unset( $panier_vars[ $date_k ][ $quant_k ] );
						continue;
					}
					$quant         = AmapressContrat_quantite::getBy( $q_id );
					$date_values[] = $quant->getFormattedTitle( $factor );
					$total         += $factor * $quant->getPrix_unitaire();
				}
				if ( ! empty( $date_values ) ) {
					$chosen_quants[ $date_k ] = $date_values;
				} else {
					unset( $panier_vars[ $date_k ] );
				}
			}
			$serial_quants = $panier_vars;

			if ( ! $admin_mode ) {
				echo '<p style="margin-bottom: 0">Vous allez vous inscrire au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€ avec les options suivantes:</p>';
			} else {
				$amapien = AmapressUser::getBy( $user_id );
				echo '<p style="margin-bottom: 0">Vous allez inscrire ' . esc_html( $amapien->getDisplayName() ) . ' au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€ avec les options suivantes:</p>';
			}
			echo '<ul style="list-style-type: square">';
			foreach ( $chosen_quants as $dt => $quant_descs ) {
				echo '<li style="margin-left: 35px">' . esc_html( date_i18n( 'd/m/Y', intval( $dt ) ) );
				echo '<ul style="list-style-type: circle">';
				foreach ( $quant_descs as $quant_desc ) {
					echo '<li style="margin-left: 15px">' . esc_html( $quant_desc ) . '</li>';
				}
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			$quants = isset( $_POST['quants'] ) ? $_POST['quants'] : [];
			if ( ! is_array( $quants ) ) {
				$quants = [ $quants ];
			}

			$factors = isset( $_POST['factors'] ) ? $_POST['factors'] : [];
//		$factors = array_map( 'floatval', $factors);


			$dates         = $contrat->getListe_dates();
			$dates         = array_filter( $dates, function ( $d ) use ( $start_date ) {
				return $d >= $start_date;
			} );
			$dates_factors = 0;
			foreach ( $dates as $d ) {
				$dates_factors += $contrat->getDateFactor( $d );
			}

			$total         = 0;
			$chosen_quants = [];
			$serial_quants = [];
			foreach ( $quants as $q ) {
				$q_id            = intval( $q );
				$factor          = isset( $factors[ $q ] ) ? floatval( $factors[ $q ] ) : 1;
				$serial_quants[] = [
					'q' => $q_id,
					'f' => $factor,
				];
				$quant           = AmapressContrat_quantite::getBy( $q_id );
				$chosen_quants[] = $quant->getFormattedTitle( $factor );
				$total           += $dates_factors * $factor * $quant->getPrix_unitaire();
			}

			if ( count( $chosen_quants ) == 1 && ! $admin_mode ) {
				echo '<p style="margin-bottom: 0">Vous avez choisi l\'option “' . esc_html( $chosen_quants[0] ) . '” du contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€</p>';
			} else {
				if ( ! $admin_mode ) {
					echo '<p style="margin-bottom: 0">Vous allez vous inscrire au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€ avec les options suivantes:</p>';
				} else {
					$amapien = AmapressUser::getBy( $user_id );
					echo '<p style="margin-bottom: 0">Vous allez inscrire ' . esc_html( $amapien->getDisplayName() ) . ' au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€ avec les options suivantes:</p>';
				}
				echo '<ul style="list-style-type: circle">';
				foreach ( $chosen_quants as $q ) {
					echo '<li style="margin-left: 35px">' . esc_html( $q ) . '</li>';
				}
				echo '</ul>';
			}
		}


		if ( ! $admin_mode ) {
			echo '<p style="margin-bottom: 0">Vous pouvez régler cette somme en :</p>';
		} else {
			echo '<p style="margin-bottom: 0">Règlement :</p>';
		}
		$serial_quants = esc_attr( serialize( $serial_quants ) );
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		echo "<input type='hidden' name='quants' value='$serial_quants'/>";
		$min_cheque_amount = $contrat->getMinChequeAmount();
		foreach ( $contrat->getPossiblePaiements() as $nb_cheque ) {
			if ( $total / $nb_cheque < $min_cheque_amount ) {
				continue;
			}

			$cheques = $contrat->getChequeOptionsForTotal( $nb_cheque, $total );
			$option  = esc_html( $cheques['desc'] );
//			$cheque_main_amount = $cheques['main_amount'];
//			$last_cheque        = $cheques['remain_amount'];
			echo "<input type='radio' name='cheques' id='cheques-$nb_cheque' value='$nb_cheque' class='required' /><label for='cheques-$nb_cheque'>$option</label><br/>";
		}
		echo '<br />';
		if ( ! $admin_mode ) {
			echo '<label for="inscr_message">Message pour le référent :</label><textarea id="inscr_message" name="message"></textarea>';
		} else {
			echo '<p><input type="checkbox" checked="checked" id="inscr_confirm_mail" name="inscr_confirm_mail" /><label for="inscr_confirm_mail"> Confirmer par mail à l\'adhérent</label></p>';
		}
		echo '<input type="submit" value="Valider" class="btn btn-default" />';
		echo '</form>';
	} else if ( 'inscr_contrat_create' == $step ) {
		$user_id    = intval( $_GET['user_id'] );
		$contrat_id = intval( $_GET['contrat_id'] );
		$lieu_id    = intval( $_GET['lieu_id'] );
		$start_date = intval( $_REQUEST['start_date'] );
		$message    = sanitize_textarea_field( isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' );

		$amapien = AmapressUser::getBy( $user_id );
		$lieu    = AmapressLieu_distribution::getBy( $lieu_id );
		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( ! $amapien || ! $lieu || ! $contrat ) {
			wp_die( 'Accès non autorisé ou erroné' );
		}


		$cheques = intval( $_REQUEST['cheques'] );
		$quants  = unserialize( stripslashes( $_REQUEST['quants'] ) );

		$referents_ids = $contrat->getModel()->getProducteur()->getReferentsIds( $lieu_id );
		/** @var AmapressUser[] $referents */
		$referents       = array_map( function ( $rid ) {
			return AmapressUser::getBy( $rid );
		}, $referents_ids );
		$referents_mails = [];
		foreach ( $referents as $r ) {
			if ( ! $r ) {
				continue;
			}
			$referents_mails += $r->getAllEmails();
		}

		$quantite_ids     = [];
		$quantite_factors = [];
		if ( $contrat->isPanierVariable() ) {
			$quantite_variables = $quants;
		} else {
			foreach ( $quants as $q ) {
				$q_id           = intval( $q['q'] );
				$quantite_ids[] = $q_id;
				$f              = intval( $q['f'] );
				if ( $f > 1 ) {
					$quantite_factors[ strval( $q_id ) ] = $f;
				}
			}
		}

		$meta = [
			'amapress_adhesion_adherent'         => $user_id,
			'amapress_adhesion_status'           => 'to_confirm',
			'amapress_adhesion_date_debut'       => $start_date,
			'amapress_adhesion_contrat_instance' => $contrat_id,
			'amapress_adhesion_message'          => $message,
			'amapress_adhesion_paiements'        => $cheques,
			'amapress_adhesion_lieu'             => $lieu_id,
		];
		if ( ! empty( $quantite_ids ) ) {
			$meta['amapress_adhesion_contrat_quantite'] = $quantite_ids;
		}
		if ( ! empty( $quantite_factors ) ) {
			$meta['amapress_adhesion_contrat_quantite_factors'] = $quantite_factors;
		}
		if ( ! empty( $quantite_variables ) ) {
			$meta['amapress_adhesion_panier_variables'] = $quantite_variables;
		}
		$my_post = array(
			'post_title'   => 'Inscription',
			'post_type'    => AmapressAdhesion::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			//TODO ???
			wp_die( 'Une erreur s\'est produite' );
		}

		$inscription = AmapressAdhesion::getBy( $new_id );
		$inscription->preparePaiements();

		if ( ! $admin_mode || isset( $_REQUEST['inscr_confirm_mail'] ) ) {
			$mail_subject = Amapress::getOption( 'online_subscription_confirm-mail-subject' );
			$mail_content = Amapress::getOption( 'online_subscription_confirm-mail-content' );

			$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $amapien, $inscription );
			$mail_content = amapress_replace_mail_placeholders( $mail_content, $amapien, $inscription );

			amapress_wp_mail( $amapien->getAllEmails(), $mail_subject, $mail_content );
		}

		//TODO contrat en word

		if ( ! $admin_mode ) {
			echo '<h4>étape 7/7 : Félicitations !</h4>';
			echo '<div class="alert alert-success">Votre inscription a bien été prise en compte. Vous allez recevoir une confirmation par mail d’ici quelques minutes.</div>';
			echo '<p><a href="' . esc_attr( $contrats_step_url ) . '" >Poursuivre mon inscription à d’autres contrats producteurs.</a></p>';
		} else {
			echo '<div class="alert alert-success">Inscription a bien été prise en compte : ' . Amapress::makeLink( $inscription->getAdminEditLink(), 'Editer l\'inscription', true, true ) . '</div>';
			echo '<p><a href="' . esc_attr( $contrats_step_url ) . '" >Retourner à la liste de ses contrats</a></p>';
		}
	}

	?>
    <style type="text/css">
        #quant-commandes td {
            text-align: center
        }
    </style>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(function ($) {
            jQuery('#quant-commandes').on('click', 'td', function () {
                jQuery(this).find(".quant-var").css('visibility', 'visible');
            });
            jQuery(".amapress_validate").validate({
                    onkeyup: false,
                errorPlacement: function (error, element) {
                    var $commandes = element.closest('.dataTables_wrapper');
                    if ($commandes.length) {
                        error.insertAfter($commandes);
                    } else {
                        if (element.attr("type") == "radio") {
                            error.insertBefore(element);
                        } else {
                            error.insertAfter(element);
                        }
                    }
                }
                }
            );

            jQuery.validator.addMethod(
                "min_sum",
                function (value, element, params) {
                    var sumOfVals = 0;
                    var parent = $(element).closest("form");
                    jQuery(parent).find(".quant:checked,.quant-var").each(function () {
                        sumOfVals = sumOfVals + parseFloat(jQuery(this).data('price'));
                    });
                    if (sumOfVals > params) return true;
                    return false;
                },
                "Le montant total doit être supérieur à {0}€<br/>"
            );

            function computeTotal() {
                var total = 0;
                jQuery('.quant:checked,.quant-var').each(function () {
                    total += parseFloat(jQuery(this).data('price'));
                });
                jQuery('#total').text(total);
            }

            function computePrices() {
                var $this = jQuery(this);
                var priceUnit = parseFloat($this.data('price-unit'));
                var val = parseFloat($this.val());
                var quantElt = jQuery('#' + $this.data('quant-id'));
                var priceElt = jQuery('#' + $this.data('price-id'));
                priceElt.text(val * priceUnit);
                quantElt.data('price', val * priceUnit);
                computeTotal();
            }

            jQuery('.quant-factor').change(computePrices).each(computePrices);
            jQuery('.quant-var').each(function () {
                var $this = jQuery(this);
                var val = parseFloat($this.val());
                if (val <= 0) {
                    $this.css('visibility', 'hidden');
                }
            }).change(function () {
                var $this = jQuery(this);
                var priceUnit = parseFloat($this.data('price-unit'));
                var val = parseFloat($this.val());
//                var priceElt = jQuery('#' + $this.data('price-id'));
//                priceElt.text(val * priceUnit);
                $this.data('price', val * priceUnit);
                computeTotal();
            }).each(function () {
                var $this = jQuery(this);
                $this.rules('add', {
                    min_sum: <?php echo $min_total; ?>,
                });
            });
            jQuery('.amapress_validate .quant').change(function () {
                var $this = jQuery(this);
                var factorElt = jQuery('#' + $this.data('factor-id'));
                factorElt.prop('disabled', !$this.is(':checked'));
                computeTotal();
            }).each(function () {
                var $this = jQuery(this);
                var factorElt = jQuery('#' + $this.data('factor-id'));
                factorElt.prop('disabled', !$this.is(':checked'));
                $this.rules('add', {
                    min_sum: <?php echo $min_total; ?>,
                });
            });
            computeTotal();
        });
        //]]>
    </script>
	<?php

	return ob_get_clean();
}