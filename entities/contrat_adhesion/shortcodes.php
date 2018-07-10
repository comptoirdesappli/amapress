<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'init', function () {
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_coords' == $_REQUEST['inscr_assistant'] ) {
		if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
			wp_die( 'Accès interdit' );
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
			'key' => '',
		]
		, $atts );

	//TODO check key + afficher lien vers cette page sécurisée + assistant for logged users

	$key = $atts['key'];
	if ( empty( $key ) || empty( $_GET['key'] ) || $_GET['key'] != $key ) {
		return '<div class="alert alert-danger">Accès interdit</div>';
	}

	ob_start();

	$min_total                 = 0;
	$subscribable_contrats     = AmapressContrats::get_subscribable_contrat_instances_by_contrat( null );
	$subscribable_contrats     = array_filter( $subscribable_contrats, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->canSelfSubscribe();
	} );
	$subscribable_contrats_ids = array_map( function ( $c ) {
		return $c->ID;
	}, $subscribable_contrats );
	$principal_contrat         = null;
	foreach ( $subscribable_contrats as $c ) {
		if ( $c->isPrincipal() ) {
			$principal_contrat = $c;
		}
	}
	if ( empty( $subscribable_contrats ) ) {
		wp_die( 'Aucun contrat ne permet l\'inscription en ligne. Veuillez activer l\'inscription en ligne depuis ' . admin_url( 'edit.php?post_type=amps_contrat_inst' ) );
	}
	if ( empty( $principal_contrat ) ) {
		wp_die( 'Aucun contrat principal. Veuillez définir un contrat principal depuis ' . admin_url( 'edit.php?post_type=amps_contrat_inst' ) );
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
			wp_die( '<p>Vous avez déjà une inscription à ce contrat. Veuillez retourner à la page <a href="' . $contrats_step_url . '"/></p>' );
		}
	}

	$start_step_url    = add_query_arg( 'step', 'email', remove_query_arg( [ 'contrat_id', 'message' ] ) );

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
        <h2>Bienvenue dans l'assistant d'inscription aux contrats de <?php echo get_bloginfo( 'name' ); ?></h2>
        <h4>Première étape : votre email</h4>
        <form method="post" action="<?php echo esc_attr( add_query_arg( 'step', 'coords' ) ) ?>" id="inscr_email"
              class="amapress_validate">
            <label for="email">Entrez votre adresse mail:</label>
            <input id="email" name="email" type="text" class="email required" placeholder="email"/>
            <input type="submit" value="Valider" class="button button-primary"/>
        </form>
		<?php
	} else if ( 'coords' == $step ) {
		$email = sanitize_email( $_POST['email'] );
		if ( empty( $email ) ) {
			wp_safe_redirect( add_query_arg( 'message', 'empty_email' ) );
		}

		$user           = get_user_by( 'email', $email );
		$user_firt_name = '';
		$user_last_name = '';
		$user_address   = '';
		$user_phones    = '';
		$user_message   = 'Vous êtes nouveau dans l\'AMAP (si ce n\'est pas le cas c\'est que vous avez saisi une adresse email inconnue). Veuillez saisir vos coordonnées:';
		if ( $user ) {
			$amapien        = AmapressUser::getBy( $user );
			$user_message   = 'Vous étiez déjà membre de l\'AMAP. Veuillez vérifier vos coordonnées:';
			$user_firt_name = $user->first_name;
			$user_last_name = $user->last_name;
			$user_address   = $amapien->getFormattedAdresse();
			$user_phones    = implode( '/', $amapien->getPhoneNumbers() );
		}
		?>
        <h4>Deuxième étape : vos coordonnées</h4>
        <p><?php echo $user_message; ?></p>
        <form method="post" id="inscr_coords" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_coords' ) ) ?>">
            <input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>"/>
            <input type="hidden" name="inscr_assistant" value="validate_coords"/>
            <input type="hidden" name="inscr_key" value="<?php echo esc_attr( $key ); ?>"/>
            <table style="min-width: 50%">
                <tr>
                    <th style="text-align: left; width: auto"><label style="width: 10%" for="email">Email: </label></th>
                    <td><span style="width: 100%"><?php echo esc_html( $email ) ?></span></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="last_name">Nom: </label></th>
                    <td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required"
                               value="<?php echo esc_attr( $user_last_name ) ?>"/>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="first_name">Prénom: </label></th>
                    <td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required"
                               value="<?php echo esc_attr( $user_firt_name ) ?>"/>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="tel">Téléphone: </label></th>
                    <td><input style="width: 100%" type="text" id="tel" name="tel" class=""
                               value="<?php echo esc_attr( $user_phones ) ?>"/>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="address">Adresse: </label></th>
                    <td><textarea style="width: 100%" rows="8" id="address" name="address"
                                  class=""><?php echo esc_textarea( $user_address ); ?></textarea>
                </tr>
            </table>
            <input style="min-width: 50%" type="submit" class="button button-primary" value="Valider vos coordonnées"/>
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
		?>
        <h4>Troisième étape : vos contrats</h4>
		<?php if ( ! $has_principal_contrat ) { ?>
            <p>Une inscription au contrat <strong><?php echo esc_html( $principal_contrat->getTitle() ); ?></strong> est
                obligatoire
                avant d'accéder aux autres contrats de l'AMAP. Vous pouvez vous y inscrire ci-dessous :</p>
		<?php } else if ( ! empty( $adhs ) ) {
			echo '<p>Vos contrats :</p>';
			echo '<ul style="list-style-type: circle">';
			foreach ( $adhs as $adh ) {
				echo '<li style="margin-left: 35px">' . esc_html( $adh->getTitle() ) . '</li>';
			}
			echo '</ul>';
			echo '<p>Vous pouvez vous inscrire à d\'autres contrats ci-dessous :</p>';
		} else {
			echo '<p>Vous n\'avez pas encore de contrats</p>';
			echo '<p>Vous pouvez vous inscrire aux contrats ci-dessous :</p>';
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
				echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ' (' . $contrat->getModel()->linkToPermalinkBlank( 'plus d\'infos' ) . ') : <br/><a class="btn btn-default" href="' . esc_attr( $inscription_url ) . '">s\'inscrire</a></li>';
			}
			echo '</ul>';
		} else {
			echo '<p>Vous êtes inscrit à tous les contrats accessibles en ligne</p>';
		}


	} else if ( 'inscr_contrat_date_lieu' == $step ) {
		$next_step_url = add_query_arg( 'step', 'inscr_contrat_engage' );
		$user_id       = intval( $_GET['user_id'] );
		$contrat_id    = intval( $_GET['contrat_id'] );
		$contrat       = AmapressContrat_instance::getBy( $contrat_id );

		?>
        <h4>Date de début d'inscription et lieu de distribution</h4>
        <p style="margin-bottom: 0">A quelle date souhaitez vous commencer l'inscription au
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
				echo '<p>Veuillez chosir votre lieu de distribution :</p>';
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
        <h4>Inscription au contrat <?php echo esc_html( $contrat->getTitle() ); ?></h4>
		<?php
		$min_total = $contrat->getMinEngagement();

		//TODO lien vers contrat PDF ?
		echo $contrat->getOnlineContrat();
		echo '<p><strong>' . count( $dates ) . '</strong> distributions restantes : ' . esc_html( implode( ', ', array_map( function ( $d ) {
				return date_i18n( 'd/m/Y', $d );
			}, $dates ) ) ) . '</p>';
		echo '<p>Veuillez choisir parmi les quantités disponibles:</p>';
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		$contrat_quants = AmapressContrats::get_contrat_quantites( $contrat->ID );
		foreach ( $contrat_quants as $quantite ) {
			$quant_var_editor = '';
			$id_quant         = 'quant' . $quantite->ID;
			$id_factor        = 'factor' . $quantite->ID;
			$id_price         = 'price' . $quantite->ID;
			$price            = $dates_factors * $quantite->getPrix_unitaire();
			if ( $contrat->isQuantiteVariable() ) {
				$quant_var_editor .= "<select id='$id_factor' class='quant-factor' data-quant='$id_quant' data-price='$id_price' data-price-unit='$price' disabled='disabled' name='factors[{$quantite->ID}]' style='display: inline-block'>";
				$quant_var_editor .= tf_parse_select_options(
					$quantite->getQuantiteOptions(),
					null,
					false );
				$quant_var_editor .= '</select>';
			}

			$type = $contrat->isQuantiteMultiple() ? 'checkbox' : 'radio';
			echo '<p><label for="' . $id_quant . '">
			<input id="' . $id_quant . '" name="quants" class="quant" value="' . $quantite->ID . '" type="' . $type . '" data-factor="' . $id_factor . '" data-price="' . $price . '"/> 
			' . $quant_var_editor . ' ' . esc_html( $quantite->getTitle() ) . ' soit <span id="' . $id_price . '">' . $price . '</span>€</label></p>';
		}
		echo '<p>Total: <span id="total">0</span>€</p>';
		echo '<p><input type="submit" class="btn btn-default" value="Valider et choisir le paiement" /></p>';
		echo '</form>';

	} else if ( 'inscr_contrat_paiements' == $step ) {
		$user_id    = intval( $_GET['user_id'] );
		$contrat_id = intval( $_GET['contrat_id'] );
		$start_date = intval( $_REQUEST['start_date'] );

		$quants = isset( $_POST['quants'] ) ? $_POST['quants'] : [];
		if ( ! is_array( $quants ) ) {
			$quants = [ $quants ];
		}
//		$quants = array_map( 'intval', $quants);
		$factors = isset( $_POST['factors'] ) ? $_POST['factors'] : [];
//		$factors = array_map( 'floatval', $factors);
		$contrat = AmapressContrat_instance::getBy( $contrat_id );


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
		$next_step_url = add_query_arg( [ 'step' => 'inscr_contrat_create' ] );

		echo '<h4>Résumé des options choisies</h4>';
		echo '<p style="margin-bottom: 0">Vous allez vous inscrire au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€ avec les options suivantes:</p>';
		echo '<ul style="list-style-type: circle">';
		foreach ( $chosen_quants as $q ) {
			echo '<li style="margin-left: 35px">' . esc_html( $q ) . '</li>';
		}
		echo '</ul>';

		echo '<p style="margin-bottom: 0">Vous pouvez régler cette somme en :</p>';
		$serial_quants = esc_attr( serialize( $serial_quants ) );
		echo '<form method="post" action="' . $next_step_url . '" class="amapress_validate">';
		echo "<input type='hidden' name='quants' value='$serial_quants'/>";
		$min_cheque_amount = $contrat->getMinChequeAmount();
		foreach ( $contrat->getPossiblePaiements() as $nb_cheque ) {
			if ( $total / $nb_cheque < $min_cheque_amount ) {
				continue;
			}

			$cheques = $contrat->getChequeOptionsForTotal( $nb_cheque, $total );
			$option  = esc_html( $cheques['desc']);
//			$cheque_main_amount = $cheques['main_amount'];
//			$last_cheque        = $cheques['remain_amount'];
			echo "<input type='radio' name='cheques' id='cheques-$nb_cheque' value='$nb_cheque' class='required' /><label for='cheques-$nb_cheque'>$option</label><br/>";
		}
		echo '<br />';
		echo '<label for="inscr_message">Message aux référents:</label><textarea id="inscr_message" name="message"></textarea>';
		echo '<input type="submit" value="Validez l\'inscription" class="btn btn-default" />';
		echo '</form>';
	} else if ( 'inscr_contrat_create' == $step ) {
		$user_id    = intval( $_GET['user_id'] );
		$contrat_id = intval( $_GET['contrat_id'] );
		$lieu_id    = intval( $_GET['lieu_id'] );
		$start_date = intval( $_REQUEST['start_date'] );
		$message    = sanitize_textarea_field( $_REQUEST['message'] );

		$amapien = AmapressUser::getBy( $user_id );
		$lieu    = AmapressLieu_distribution::getBy( $lieu_id );
		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( ! $amapien || ! $lieu || ! $contrat ) {
			wp_die( 'Accès non autorisé ou erroné' );
		}



		$cheques = intval( $_REQUEST['cheques'] );
		$quants  = unserialize( stripslashes( $_REQUEST['quants'] ));

		$referents_ids = $contrat->getModel()->getProducteur()->getReferentsIds( $lieu_id );
		/** @var AmapressUser[] $referents */
		$referents       = array_map( function ( $rid ) {
			return AmapressUser::getBy( $rid );
		}, $referents_ids );
		$referents_mails = [];
		foreach ( $referents as $r ) {
			if ( ! $r )
				continue;
			$referents_mails += $r->getAllEmails();
		}

		$quantite_ids     = [];
		$quantite_factors = [];
		foreach ( $quants as $q ) {
			$q_id           = intval( $q['q'] );
			$quantite_ids[] = $q_id;
			$f              = intval( $q['f'] );
			if ( $f > 1 ) {
				$quantite_factors[ strval( $q_id ) ] = $f;
			}
		}

		$meta = [
			'amapress_adhesion_adherent'         => $user_id,
			'amapress_adhesion_status'           => 'to_confirm',
			'amapress_adhesion_date_debut'       => $start_date,
			'amapress_adhesion_contrat_instance' => $contrat_id,
			'amapress_adhesion_contrat_quantite' => $quantite_ids,
			'amapress_adhesion_message'          => $message,
			'amapress_adhesion_paiements'        => $cheques,
			'amapress_adhesion_lieu'             => $lieu_id,
		];
		if ( ! empty( $quantite_factors ) ) {
			$meta['amapress_adhesion_contrat_quantite_factors'] = $quantite_factors;
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

		$mail_subject = Amapress::getOption( 'online_subscription_confirm-mail-subject' );
		$mail_content = Amapress::getOption( 'online_subscription_confirm-mail-content' );

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $amapien, $inscription );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, $amapien, $inscription );

		amapress_wp_mail( $amapien->getAllEmails(), $mail_subject, $mail_content );

		//TODO contrat en word

		echo '<div class="alert alert-success">Votre inscription a bien été prise en compte. Vous allez recevoir une confirmation par mail d\'ici peu.</div>';
		echo '<p><a href="' . esc_attr( $contrats_step_url ) . '" >Retourner à la liste de vos contrats</a></p>';
	}

	?>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(function ($) {
            jQuery(".amapress_validate").validate({
                    onkeyup: false,
                errorPlacement: function (error, element) {
                    if (element.attr("type") == "radio") {
                        error.insertBefore(element);
                    } else {
                        error.insertAfter(element);
                    }
                }
                }
            );

            jQuery.validator.addMethod(
                "min_sum",
                function (value, element, params) {
                    var sumOfVals = 0;
                    var parent = $(element).closest("form");
                    jQuery(parent).find(".quant:checked").each(function () {
                        sumOfVals = sumOfVals + parseFloat(jQuery(this).data('price'));
                    });
                    if (sumOfVals > params) return true;
                    return false;
                },
                "Le montant total doit être supérieur à {0}€<br/>"
            );

            function computeTotal() {
                var total = 0;
                jQuery('.quant:checked').each(function () {
                    total += parseFloat(jQuery(this).data('price'));
                });
                jQuery('#total').text(total);
            }

            jQuery('.quant-factor').change(function () {
                var $this = jQuery(this);
                var priceUnit = parseFloat($this.data('price-unit'));
                var val = parseFloat($this.val());
                var quantElt = jQuery('#' + $this.data('quant'));
                var priceElt = jQuery('#' + $this.data('price'));
                priceElt.text(val * priceUnit);
                quantElt.data('price', val * priceUnit);
                computeTotal();
            });
            jQuery('.amapress_validate .quant').change(computeTotal).each(function () {
                jQuery(this).rules('add', {
                    min_sum: <?php echo $min_total; ?>,
                });
            });
        });
        //]]>
    </script>
	<?php

	return ob_get_clean();
}