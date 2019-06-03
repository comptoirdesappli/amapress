<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_init', function () {
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_coords' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
				wp_die( 'Accès interdit' );
			}
		}
		$email          = sanitize_email( $_REQUEST['email'] );
		$user_firt_name = sanitize_text_field( ! empty( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '' );
		$user_last_name = sanitize_text_field( ! empty( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '' );
		$user_address   = sanitize_textarea_field( $_REQUEST['address'] );
		$user_phones    = sanitize_text_field( $_REQUEST['tel'] );

		$user_id = amapress_create_user_if_not_exists( $email, $user_firt_name, $user_last_name, $user_address, $user_phones );
		if ( ! $user_id ) {
			wp_redirect_and_exit( add_query_arg( 'message', 'cannot_create_user' ) );
		}

		if ( ! empty( $_REQUEST['coadh1_email'] ) ) {
			$coadh1_email          = sanitize_email( $_REQUEST['coadh1_email'] );
			$coadh1_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['coadh1_first_name'] ) ? $_REQUEST['coadh1_first_name'] : '' );
			$coadh1_user_last_name = sanitize_text_field( ! empty( $_REQUEST['coadh1_last_name'] ) ? $_REQUEST['coadh1_last_name'] : '' );

			$coadh1_user_id = amapress_create_user_if_not_exists( $coadh1_email, $coadh1_user_firt_name, $coadh1_user_last_name, null, null );
			if ( $coadh1_user_id ) {
				$amapien = AmapressUser::getBy( $user_id, true );
				$amapien->addCoadherent( $coadh1_user_id );
			}
		}

		if ( ! empty( $_REQUEST['coadh2_email'] ) ) {
			$coadh2_email          = sanitize_email( $_REQUEST['coadh2_email'] );
			$coadh2_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['coadh2_first_name'] ) ? $_REQUEST['coadh2_first_name'] : '' );
			$coadh2_user_last_name = sanitize_text_field( ! empty( $_REQUEST['coadh2_last_name'] ) ? $_REQUEST['coadh2_last_name'] : '' );

			$coadh2_user_id = amapress_create_user_if_not_exists( $coadh2_email, $coadh2_user_firt_name, $coadh2_user_last_name, null, null );
			if ( $coadh2_user_id ) {
				$amapien = AmapressUser::getBy( $user_id, true );
				$amapien->addCoadherent( $coadh2_user_id );
			}
		}

		if ( ! empty( $_REQUEST['coadh3_email'] ) ) {
			$coadh3_email          = sanitize_email( $_REQUEST['coadh3_email'] );
			$coadh3_user_firt_name = sanitize_text_field( ! empty( $_REQUEST['coadh3_first_name'] ) ? $_REQUEST['coadh3_first_name'] : '' );
			$coadh3_user_last_name = sanitize_text_field( ! empty( $_REQUEST['coadh3_last_name'] ) ? $_REQUEST['coadh3_last_name'] : '' );

			$coadh3_user_id = amapress_create_user_if_not_exists( $coadh3_email, $coadh3_user_firt_name, $coadh3_user_last_name, null, null );
			if ( $coadh3_user_id ) {
				$amapien = AmapressUser::getBy( $user_id, true );
				$amapien->addCoadherent( $coadh3_user_id );
			}
		}

		wp_redirect_and_exit(
			add_query_arg( [
				'step'    => ! empty( $_REQUEST['coords_next_step'] ) ? $_REQUEST['coords_next_step'] : 'contrats',
				'user_id' => $user_id,
			] )
		);
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'validate_agreement' == $_REQUEST['inscr_assistant'] ) {
		$step = ! empty( $_REQUEST['coords_next_step'] ) ? $_REQUEST['coords_next_step'] : 'contrats';
		if ( isset( $_REQUEST['accept'] ) && ! $_REQUEST['accept'] ) {
			$step = 'agreement';
		}
		$user_id = intval( $_REQUEST['user_id'] );
		wp_redirect_and_exit(
			add_query_arg( [
				'step'    => $step,
				'user_id' => $user_id,
			] )
		);
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'generate_contrat' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
				wp_die( 'Accès interdit' );
			}
		}

		$inscr_id = intval( $_REQUEST['inscr_id'] );
		if ( empty( $inscr_id ) ) {
			wp_die( 'Accès interdit' );
		}
		$adhesion = AmapressAdhesion::getBy( $inscr_id );
		if ( empty( $adhesion ) ) {
			wp_die( 'Accès interdit' );
		}

		$full_file_name = $adhesion->generateContratDoc( false );
		$file_name      = basename( $full_file_name );
		Amapress::sendDocumentFile( $full_file_name, $file_name );
	}
	if ( isset( $_REQUEST['inscr_assistant'] ) && 'generate_bulletin' == $_REQUEST['inscr_assistant'] ) {
		if ( ! amapress_is_user_logged_in() ) {
			if ( ! isset( $_REQUEST['inscr_key'] ) || ! isset( $_REQUEST['key'] ) || $_REQUEST['inscr_key'] != $_REQUEST['key'] ) {
				wp_die( 'Accès interdit' );
			}
		}

		$adh_id = intval( $_REQUEST['adh_id'] );
		if ( empty( $adh_id ) ) {
			wp_die( 'Accès interdit' );
		}
		$adhesion_paiement = AmapressAdhesion_paiement::getBy( $adh_id );
		if ( empty( $adhesion_paiement ) ) {
			wp_die( 'Accès interdit' );
		}

		$full_file_name = $adhesion_paiement->generateBulletinDoc();
		$file_name      = basename( $full_file_name );
		Amapress::sendDocumentFile( $full_file_name, $file_name );
	}
} );

function amapress_mes_contrats( $atts, $content = null ) {
	$atts                  = wp_parse_args( $atts );
	$atts['for_logged']    = 'true';
	$atts['show_contrats'] = 'true';

	return amapress_self_inscription( $atts, $content );
}

/**
 * @param $atts
 */
function amapress_self_inscription( $atts, $content = null ) {
	amapress_ensure_no_cache();

	$step = isset( $_REQUEST['step'] ) ? $_REQUEST['step'] : 'email';

	$atts = shortcode_atts(
		[
			'key'                  => '',
			'for_logged'           => 'false',
			'show_contrats'        => 'false',
			'filter_multi_contrat' => 'false',
			'admin_mode'           => 'false',
			'agreement'            => 'false',
			'check_principal'      => 'true',
			'adhesion'             => 'true',
			'send_referents'       => 'true',
			'send_tresoriers'      => 'true',
			'edit_names'           => 'true',
			'only_contrats'        => '',
			'shorturl'             => '',
			'adhesion_shift_weeks' => 0,
			'before_close_hours'   => 24,
			'email'                => get_option( 'admin_email' ),
		]
		, $atts );

	$for_logged         = Amapress::toBool( $atts['for_logged'] );
	$ret                = '';
	$admin_mode         = Amapress::toBool( $atts['admin_mode'] );
	$activate_adhesion  = Amapress::toBool( $atts['adhesion'] );
	$activate_agreement = Amapress::toBool( $atts['agreement'] );
	$key                = $atts['key'];
	if ( $admin_mode && amapress_is_user_logged_in() && amapress_can_access_admin() ) {
		if ( ! isset( $_REQUEST['step'] ) ) {
			$step = 'contrats';
		}
	} else if ( $for_logged ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '<div class="alert alert-danger">Accès interdit</div>';
		}
		if ( ! isset( $_REQUEST['step'] ) ) {
			if ( Amapress::toBool( $atts['show_contrats'] ) ) {
				$step = 'contrats';
			} else {
				$step = 'coords_logged';
			}
		}
	} else {
		if ( amapress_can_access_admin() ) {
			$url = add_query_arg( 'key', $key, get_permalink() );
			if ( empty( $_REQUEST['key'] ) ) {
				$ret .= amapress_get_panel_start( 'Information d\'accès pour le collectif' );
				$ret .= '<div class="alert alert-info">Pour donner accès à cet assistant aux nouveaux amapiens, veuillez leur envoyer le lien suivant : 
<pre>' . $url . '</pre>
Pour y accéder cliquez <a href="' . $url . '">ici</a>.<br />
Vous pouvez également utiliser un service de réduction d\'URL tel que <a href="https://bit.ly">bit.ly</a> pour obtenir une URL plus courte à partir du lien ci-dessus.<br/>
' . ( ! empty( $atts['shorturl'] ) ? 'Lien court sauvegardé : <code>' . $atts['shorturl'] . '</code><br />' : '' ) . '
Vous pouvez également utiliser l\'un des QRCode suivants : 
<div>' . amapress_print_qrcode( $url ) . amapress_print_qrcode( $url, 3 ) . amapress_print_qrcode( $url, 2 ) . '</div><br/>
<strong>Attention : les lien ci-dessus, QR code et bit.ly NE doivent PAS être visible publiquement sur le site. Ce lien permet de créer des comptes sur le site et l\'exposer sur internet pourrait permettre à une personne malvaillante de polluer le site avec des comptes de SPAM.</strong><br />
Vous pouvez configurer le mail envoyé en fin de chaque inscription <a href="' . admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=config_online_inscriptions' ) . '">ici</a>.</div>';
				$ret .= amapress_get_panel_end();
			} else {
				$ret .= '<div class="alert alert-info"><a href="' . esc_attr( get_permalink() ) . '">Afficher les instructions d\'accès à cet assistant.</a></div>';
			}
		}
		if ( empty( $key ) || empty( $_REQUEST['key'] ) || $_REQUEST['key'] != $key ) {
			$ret .= '<div class="alert alert-danger">Vous êtes dans un espace sécurisé. Accès interdit</div>';

			$ret .= $content;

			return $ret;
		}
	}

	ob_start();

	echo $ret;

	$min_total = 0;
	Amapress::setFilterForReferent( false );
	$subscribable_contrats = AmapressContrats::get_subscribable_contrat_instances_by_contrat( null );
	Amapress::setFilterForReferent( true );
	if ( ! $admin_mode ) {
		$subscribable_contrats = array_filter( $subscribable_contrats, function ( $c ) {
			/** @var AmapressContrat_instance $c */
			return $c->canSelfSubscribe();
		} );
		if ( ! empty( $atts['only_contrats'] ) ) {
			$only_contrats         = array_map( function ( $c ) {
				return Amapress::resolve_post_id( $c, AmapressContrat::INTERNAL_POST_TYPE );
			}, explode( ',', $atts['only_contrats'] ) );
			$subscribable_contrats = array_filter( $subscribable_contrats, function ( $c ) use ( $only_contrats ) {
				/** @var AmapressContrat_instance $c */
				return in_array( $c->getModelId(), $only_contrats );
			} );
		}
	}
	usort( $subscribable_contrats, function ( $a, $b ) {
		/** @var AmapressContrat_instance $a */
		/** @var AmapressContrat_instance $b */
		return strcmp( $a->getTitle(), $b->getTitle() );
	} );
//	$subscribable_contrats     = array_filter( $subscribable_contrats, function ( $c ) {
//		/** @var AmapressContrat_instance $c */
//		return ! $c->isPanierVariable();
//	} );
	$subscribable_contrats_ids = array_map( function ( $c ) {
		return $c->ID;
	}, $subscribable_contrats );
	$principal_contrats        = [];
	$principal_contrats_ids    = [];
	$min_contrat_date          = - 1;
	$max_contrat_date          = - 1;
	foreach ( $subscribable_contrats as $c ) {
		if ( $c->isPrincipal() ) {
			$principal_contrats[]     = $c;
			$principal_contrats_ids[] = $c->ID;
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
		ob_clean();

		return ( 'Aucun contrat ne permet l\'inscription en ligne. Veuillez activer l\'inscription en ligne depuis ' . admin_url( 'edit.php?post_type=amps_contrat_inst' ) );
	}
	if ( Amapress::toBool( $atts['check_principal'] ) && ! $admin_mode && empty( $principal_contrats ) ) {
		ob_clean();

		return ( 'Aucun contrat principal. Veuillez définir un contrat principal depuis ' . admin_url( 'edit.php?post_type=amps_contrat_inst' ) );
	}
	//TODO better ???
	$adh_period_date = Amapress::add_a_week( $min_contrat_date, $atts['adhesion_shift_weeks'] );

//	if ( ! $admin_mode && count( $principal_contrats ) > 1 ) {
//		wp_die( 'Il y a plusieurs contrat principaux. Veuillez vérifier la configuration (erreur de dates d\'ouverture/clôture) : <br/>' .
//		        implode( '<br/>', array_map( function ( $c ) {
//			        /** @var AmapressContrat_instance $c */
//			        return Amapress::makeLink( $c->getAdminEditLink(), $c->getTitle(), true, true );
//		        }, $principal_contrats ) ) );
//	}

	$contrats_step_url = add_query_arg( 'step', 'contrats', remove_query_arg( [ 'contrat_id', 'message' ] ) );
	$adhesion_step_url = add_query_arg( 'step', 'adhesion', remove_query_arg( [ 'contrat_id', 'message' ] ) );
	$the_end_url       = add_query_arg( 'step', 'the_end', remove_query_arg( [ 'contrat_id', 'message' ] ) );

	if ( isset( $_REQUEST['contrat_id'] ) && isset( $_REQUEST['user_id'] ) ) {
		$user_id    = intval( $_REQUEST['user_id'] );
		$contrat_id = intval( $_REQUEST['contrat_id'] );

		Amapress::setFilterForReferent( false );
		$adhs = AmapressAdhesion::getUserActiveAdhesions( $user_id, null, null, false, true );
		Amapress::setFilterForReferent( true );
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
				ob_clean();

				return '<p>' . esc_html( $amapien->getDisplayName() ) . ' déjà une inscription à ce contrat. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Contrats</a></p>';
			} else {
				ob_clean();

				return ( '<p>Vous avez déjà une inscription à ce contrat. Veuillez retourner à la page <a href="' . $contrats_step_url . '">Contrats</a></p>' );
			}
		}
	}

	if ( is_admin() ) {
		$start_step_url = esc_attr(
			admin_url( 'admin.php?page=amapress_gestion_amapiens_page&tab=add_inscription' ) );
	} else {
		$start_step_url = esc_attr( add_query_arg( 'step', 'email',
			remove_query_arg( [
				'contrat_id',
				'message'
			] ) ) );
	}

	$invalid_access_message = '<p>Accès invalide : veuillez repartir de la <a href="' . $start_step_url . '">première étape</a></p>';

	if ( ! empty( $_REQUEST['message'] ) ) {
		$message = '';
		switch ( $_REQUEST['message'] ) {
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
        <h2>Bienvenue dans l’assistant d’inscription aux contrats producteurs
            de <?php echo get_bloginfo( 'name' ); ?></h2>
        <h4>Étape 1/8 : Email</h4>
        <form method="post" action="<?php echo esc_attr( add_query_arg( 'step', 'coords' ) ) ?>" id="inscr_email"
              class="amapress_validate">
            <label for="email">Pour démarrer votre inscription à l’AMAP pour la saison
	            <?php echo date_i18n( 'F Y', $min_contrat_date ) . ' - ' . date_i18n( 'F Y', $max_contrat_date ) ?>
                , renseignez votre
                adresse mail :</label>
            <input id="email" name="email" type="text" class="email required" placeholder="email"/>
            <input type="submit" value="Valider" class="btn btn-default btn-assist-inscr"/>
        </form>
		<?php
	} else if ( 'coords' == $step || 'coords_logged' == $step ) {
		if ( 'coords_logged' == $step && amapress_is_user_logged_in() ) {
			$email = wp_get_current_user()->user_email;
		} else {
			if ( empty( $_REQUEST['email'] ) ) {
				wp_die( $invalid_access_message );
			}
			$email = sanitize_email( $_REQUEST['email'] );
		}

		$user           = get_user_by( 'email', $email );
		$user_firt_name = '';
		$user_last_name = '';
		$user_address   = '';
		$user_phones    = '';

		$coadh1_user_firt_name = '';
		$coadh1_user_last_name = '';
		$coadh1_email          = '';

		$coadh2_user_firt_name = '';
		$coadh2_user_last_name = '';
		$coadh2_email          = '';

		$coadh3_user_firt_name = '';
		$coadh3_user_last_name = '';
		$coadh3_email          = '';

		$user_message   = 'Vous êtes nouveau dans l’AMAP, complétez vos coordonnées :';
		$member_message = '<p>Si vous êtes déjà membre de l’AMAP, vous avez certainement utilisé une adresse mail différente.</p>
<p><a href="' . $start_step_url . '">Changer d’email</a></p>';

		$edit_names = Amapress::toBool( $atts['edit_names'] ) || empty( $user );

		if ( $user ) {
//			if ( is_multisite() ) {
//				if ( ! is_user_member_of_blog( $user->ID ) ) {
//					add_user_to_blog( get_current_blog_id(), $user->ID, 'amapien' );
//				}
//			}
			$amapien        = AmapressUser::getBy( $user );
			$user_message   = 'Vous êtes déjà membre de l’AMAP, vérifiez vos coordonnées :';
			$user_firt_name = $user->first_name;
			$user_last_name = $user->last_name;
			$user_address   = $amapien->getFormattedAdresse();
			$user_phones    = implode( '/', $amapien->getPhoneNumbers() );
			$member_message = '';

			if ( $amapien->getCoAdherent1() ) {
				$coadh1_user_firt_name = $amapien->getCoAdherent1()->getUser()->first_name;
				$coadh1_user_last_name = $amapien->getCoAdherent1()->getUser()->last_name;
				$coadh1_email          = $amapien->getCoAdherent1()->getUser()->user_email;
			}

			if ( $amapien->getCoAdherent2() ) {
				$coadh2_user_firt_name = $amapien->getCoAdherent2()->getUser()->first_name;
				$coadh2_user_last_name = $amapien->getCoAdherent2()->getUser()->last_name;
				$coadh2_email          = $amapien->getCoAdherent2()->getUser()->user_email;
			}

			if ( $amapien->getCoAdherent3() ) {
				$coadh3_user_firt_name = $amapien->getCoAdherent3()->getUser()->first_name;
				$coadh3_user_last_name = $amapien->getCoAdherent3()->getUser()->last_name;
				$coadh3_email          = $amapien->getCoAdherent3()->getUser()->user_email;
			}
		}

		$adh_pmt = $user ? AmapressAdhesion_paiement::getForUser( $user->ID, $adh_period_date, false ) : null;
		?>
        <h4>Étape 2/8 : Coordonnées</h4>
        <p><?php echo $user_message; ?></p>
        <form method="post" id="inscr_coords" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_coords' ) ) ?>">
            <input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>"/>
            <input type="hidden" name="inscr_assistant" value="validate_coords"/>
	        <?php if ( $activate_agreement ) { ?>
                <input type="hidden" name="coords_next_step" value="agreement"/>
	        <?php } else if ( $activate_adhesion && empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step" value="adhesion"/>
	        <?php } ?>
            <input type="hidden" name="inscr_key" value="<?php echo esc_attr( $key ); ?>"/>
            <table style="min-width: 50%">
                <tr>
                    <th style="text-align: left; width: auto"><label for="email">Email : </label>
                    </th>
                    <td><span style="width: 100%"><?php echo esc_html( $email ) ?></span></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="last_name">Nom* : </label></th>
                    <td><input style="width: 100%" type="text" id="last_name" name="last_name"
                               class="required single_name"
                               value="<?php echo esc_attr( $user_last_name ) ?>" <?php disabled( ! $edit_names ) ?>/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="first_name">Prénom* : </label></th>
                    <td><input style="width: 100%" type="text" id="first_name" name="first_name"
                               class="required single_name"
                               value="<?php echo esc_attr( $user_firt_name ) ?>" <?php disabled( ! $edit_names ) ?>/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="tel">Téléphone : </label></th>
                    <td><input style="width: 100%" type="text" id="tel" name="tel" class=""
                               value="<?php echo esc_attr( $user_phones ) ?>"/></td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="address">Adresse : </label></th>
                    <td><textarea style="width: 100%" rows="4" id="address" name="address"
                                  class=""><?php echo esc_textarea( $user_address ); ?></textarea></td>
                </tr>
            </table>
            <div>
	            <?php echo Amapress::getOption( 'online_adhesion_coadh_message' ); ?>
            </div>
            <table style="min-width: 50%">
                <tr>
                    <th colspan="2">Co adhérent 1 <em>(si vous payez les contrats à plusieurs)</em> / Conjoint</th>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh1_email">Son email
                            : </label>
                    </th>
                    <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh1_email" name="coadh1_email"
                                                                              class="email"
                                                                              value="<?php echo esc_attr( $coadh1_email ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh1_last_name">Son nom : </label></th>
                    <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh1_last_name"
                                                                              name="coadh1_last_name"
                                                                              class="required_if_not_empty single_name"
                                                                              data-if-id="coadh1_email"
                                                                              value="<?php echo esc_attr( $coadh1_user_last_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh1_first_name">Son prénom : </label></th>
                    <td><input <?php disabled( ! empty( $coadh1_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh1_first_name"
                                                                              name="coadh1_first_name"
                                                                              class="required_if_not_empty single_name"
                                                                              data-if-id="coadh1_email"
                                                                              value="<?php echo esc_attr( $coadh1_user_firt_name ) ?>"/>
                    </td>
                </tr>
            </table>
	        <?php
	        //            if (!empty($coadh1_email)) {
	        //                echo '<p></p>';
	        //            }
	        ?>
            <table style="min-width: 50%">
                <tr>
                    <th colspan="2">Co adhérent 2 <em>(si vous payez les contrats à plusieurs)</em></th>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh2_email">Son email
                            : </label>
                    </th>
                    <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh2_email" name="coadh2_email"
                                                                              class="email"
                                                                              value="<?php echo esc_attr( $coadh2_email ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh2_last_name">Son nom : </label></th>
                    <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh2_last_name"
                                                                              name="coadh2_last_name"
                                                                              class="required_if_not_empty single_name"
                                                                              data-if-id="coadh2_email"
                                                                              value="<?php echo esc_attr( $coadh2_user_last_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh2_first_name">Son prénom : </label></th>
                    <td><input <?php disabled( ! empty( $coadh2_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh2_first_name"
                                                                              name="coadh2_first_name"
                                                                              class="required_if_not_empty single_name"
                                                                              data-if-id="coadh2_email"
                                                                              value="<?php echo esc_attr( $coadh2_user_firt_name ) ?>"/>
                    </td>
                </tr>
            </table>
            <table style="min-width: 50%">
                <tr>
                    <th colspan="2">Co adhérent 3 <em>(si vous payez les contrats à plusieurs)</em></th>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh3_email">Son email
                            : </label>
                    </th>
                    <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh3_email" name="coadh3_email"
                                                                              class="email"
                                                                              value="<?php echo esc_attr( $coadh3_email ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh3_last_name">Son nom : </label></th>
                    <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh3_last_name"
                                                                              name="coadh3_last_name"
                                                                              class="required_if_not_empty single_name"
                                                                              data-if-id="coadh3_email"
                                                                              value="<?php echo esc_attr( $coadh3_user_last_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="coadh3_first_name">Son prénom : </label></th>
                    <td><input <?php disabled( ! empty( $coadh3_email ) ); ?> style="width: 100%" type="text"
                                                                              id="coadh3_first_name"
                                                                              name="coadh3_first_name"
                                                                              class="required_if_not_empty single_name"
                                                                              data-if-id="coadh3_email"
                                                                              value="<?php echo esc_attr( $coadh3_user_firt_name ) ?>"/>
                    </td>
                </tr>
            </table>
            <p style="color:red">* Champ obligatoire</p>
	        <?php echo $member_message; ?>
            <input style="min-width: 50%" type="submit" class="btn btn-default btn-assist-inscr" value="Valider"/>
        </form>
		<?php
	} else if ( 'agreement' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );

		$adh_pmt = $user_id ? AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false ) : null;
		?>
        <h4><?php echo wp_unslash( esc_html( Amapress::getOption( 'online_subscription_agreement_step_name' ) ) ) ?></h4>
        <form method="post" id="agreement" class="amapress_validate"
              action="<?php echo esc_attr( add_query_arg( 'step', 'validate_agreement' ) ) ?>">
            <input type="hidden" name="inscr_assistant" value="validate_agreement"/>
            <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
	        <?php if ( $activate_adhesion && empty( $adh_pmt ) ) { ?>
                <input type="hidden" name="coords_next_step" value="adhesion"/>
			<?php } ?>
            <div class="amap-agreement">
				<?php echo Amapress::getOption( 'online_subscription_agreement' ); ?>
            </div>
            <p class="accept-agreement">
                <label for="accept_agreement"><input type="checkbox" name="accept" id="accept_agreement"
                                                     class="required"
                                                     data-msg="Veuillez cocher la case ci-dessous"/> <?php echo esc_html( wp_unslash( Amapress::getOption( 'online_subscription_agreement_step_checkbox' ) ) ); ?>
                </label>
            </p>
            <p>
                <input style="min-width: 50%" type="submit" class="btn btn-default btn-assist-inscr" value="Valider"/>
            </p>
        </form>
		<?php
	} else if ( 'adhesion' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false );
		if ( $adh_paiement ) {
			ob_clean();

			return ( 'Vous avez déjà une adhésion' );
		}

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( 'Aucune période d\'adhésion n\'est configurée.' );
		}

		echo '<h4>Étape 3/8 : Adhésion (obligatoire)</h4>';
		echo $adh_period->getOnlineDescription();

		$taxes            = get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => 'amps_paiement_category',
			'hide_empty' => false,
		) );
		$ret              = '';
		$ret              .= '<form method="post" id="inscr_adhesion" class="amapress_validate" action="' . esc_attr( add_query_arg( 'step', 'save_adhesion' ) ) . '">';
		$ret              .= '<input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '"/>';
		$amap_term        = Amapress::getOption( 'adhesion_amap_term' );
		$reseau_amap_term = Amapress::getOption( 'adhesion_reseau_amap_term' );
		$ret              .= '<table style="max-width: 50%">';
		foreach ( $taxes as $tax ) {
			$tax_amount = 0;
			if ( $tax->term_id == $amap_term ) {
				$tax_amount = $adh_period->getMontantAmap();
			}
			if ( $tax->term_id == $reseau_amap_term ) {
				$tax_amount = $adh_period->getMontantReseau();
			}
			$ret .= '<tr>';
			$ret .= '<th style="text-align: left; width: auto">
<label for="amapress_pmt_amount-' . $tax->term_id . '">' . esc_html( $tax->name ) . '</label>
' . ( ! empty( $tax->description ) ? '<p style="font-style: italic; font-weight: normal">' . $tax->description . '</p>' : '' ) . '
</th>';
			if ( $tax->term_id == $amap_term || $tax->term_id == $reseau_amap_term ) {
				$ret .= '<td><input type="hidden" id="amapress_pmt_amount-' . $tax->term_id . '" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="amapress_pmt_cat_amount" value="' . $tax_amount . '" />' . $tax_amount . '&nbsp;€</td>';
			} else {
				$ret .= '<td><input type="number" id="amapress_pmt_amount-' . $tax->term_id . '" style="width: 80%" name="amapress_pmt_amounts[' . $tax->term_id . ']" class="price required amapress_pmt_cat_amount" value="' . $tax_amount . '" />&nbsp;€</td>';
			}
			$ret .= '</tr>';
		}
		$ret .= '</table>';
		$ret .= '<p>Montant total : <span id="amapress_adhesion_paiement_amount"></span> €</p>';
		$ret .= '<input type="submit" class="btn btn-default btn-assist-adh" value="Valider"/>';
		$ret .= '</form>';

		echo $ret;

	} else if ( 'save_adhesion' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );

		if ( empty( $_REQUEST['amapress_pmt_amounts'] ) ) {
			wp_die( $invalid_access_message );
		}

		$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
		if ( empty( $adh_period ) ) {
			ob_clean();

			return ( 'Aucune période d\'adhésion n\'est configurée.' );
		}

		$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date );

		$terms   = array();
		$amounts = array();
		foreach ( $_POST['amapress_pmt_amounts'] as $tax_id => $amount ) {
			if ( $amount > 0 ) {
				$terms[]            = intval( $tax_id );
				$amounts[ $tax_id ] = floatval( $amount );
			}
		}
		$total_amount = 0;
		foreach ( $amounts as $k => $v ) {
			$total_amount += $v;
		}
		update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_repartition', $amounts );
		update_post_meta( $adh_paiement->ID, 'amapress_adhesion_paiement_amount', $total_amount );
		wp_set_post_terms( $adh_paiement->ID, $terms, 'amps_paiement_category' );

		amapress_compute_post_slug_and_title( $adh_paiement->getPost() );

		$amapien      = AmapressUser::getBy( $user_id );
		$mail_subject = Amapress::getOption( 'online_adhesion_confirm-mail-subject' );
		$mail_content = Amapress::getOption( 'online_adhesion_confirm-mail-content' );

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $amapien, $adh_paiement );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, $amapien, $adh_paiement );

		$tresoriers = [];
		foreach ( get_users( "role=tresorier" ) as $tresorier ) {
			$user_obj   = AmapressUser::getBy( $tresorier );
			$tresoriers = array_merge( $tresoriers, $user_obj->getAllEmails() );
		}

		$attachments = [];
		$doc_file    = $adh_paiement->generateBulletinDoc();
		if ( ! empty( $doc_file ) ) {
			$attachments[] = $doc_file;
			$mail_content  = preg_replace( '/\[sans_bulletin\].+?\[\/sans_bulletin\]/', '', $mail_content );
			$mail_content  = preg_replace( '/\[\/?avec_bulletin\]/', '', $mail_content );
		} else {
			$mail_content = preg_replace( '/\[avec_bulletin\].+?\[\/avec_bulletin\]/', '', $mail_content );
			$mail_content = preg_replace( '/\[\/?sans_bulletin\]/', '', $mail_content );
		}

		amapress_wp_mail( $amapien->getAllEmails(), $mail_subject, $mail_content, [
			'Reply-To: ' . implode( ',', $tresoriers )
		], $attachments );

		if ( Amapress::toBool( $atts['send_tresoriers'] ) ) {
			amapress_wp_mail(
				$tresoriers,
				'Nouvelle adhésion ' . $amapien->getDisplayName(),
				wpautop( "Bonjour,\nUne nouvelle adhésion est en attente : " . Amapress::makeLink( $adh_paiement->getAdminEditLink(), $amapien->getDisplayName() ) . "\n\n" . get_bloginfo( 'name' ) )
			);
		}

		echo '<h4>Validation du Bulletin d\'adhésion</h4>';

		echo Amapress::getOption( 'online_subscription_greating_adhesion' );

		if ( $adh_paiement->getPeriod()->getWordModelId() ) {
			$print_bulletin = Amapress::makeButtonLink(
				add_query_arg( [
					'inscr_assistant' => 'generate_bulletin',
					'adh_id'          => $adh_paiement->ID,
					'inscr_key'       => $key
				] ),
				'Imprimer', true, true, 'btn btn-default'
			);
			echo '<p>Veuillez imprimer le bulletin et le remettre avec votre chèque/règlement lors de la première distribution<br/>' . $print_bulletin . '</p>';
		} else {
			echo '<p>Veuillez remettre le chèque/règlement lors de la première distribution</p>';
		}

		echo '<p>Vous pouvez maintenant vous inscrires aux contrats de l\'AMAP :<br/>
<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
	} else if ( 'contrats' == $step ) {
		if ( $for_logged && amapress_is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_die( $invalid_access_message );
			}
			$user_id = intval( $_REQUEST['user_id'] );
		}
		$has_principal_contrat = false;

		Amapress::setFilterForReferent( false );
		$adhs = AmapressAdhesion::getUserActiveAdhesions( $user_id, null, null, false, true );
		Amapress::setFilterForReferent( true );
		$adhs = array_filter( $adhs,
			function ( $adh ) use ( $subscribable_contrats_ids ) {
				/** @var AmapressAdhesion $adh */
				return in_array( $adh->getContrat_instanceId(), $subscribable_contrats_ids );
			} );
		if ( Amapress::toBool( $atts['check_principal'] ) ) {
			foreach ( $adhs as $adh ) {
				if ( $adh->getContrat_instance()->isPrincipal() ) {
					$has_principal_contrat = true;
				}
			}
		} else {
			$has_principal_contrat = true;
		}
		usort( $adhs, function ( $a, $b ) {
			return strcmp( $a->getTitle(), $b->getTitle() );
		} );
		$amapien = AmapressUser::getBy( $user_id );
		if ( ! $admin_mode ) {
			if ( ! Amapress::toBool( $atts['show_contrats'] ) ) {
				echo '<h4>Étape 4/8 : les contrats</h4>';
			}
		} else {
			echo '<h4>Les contrats de ' . esc_html( $amapien->getDisplayName() ) . '</h4>';
		}
		if ( ! $admin_mode ) {
			$adh_period = AmapressAdhesionPeriod::getCurrent( $adh_period_date );
			if ( empty( $adh_period ) ) {
				ob_clean();

				return ( 'Aucune période d\'adhésion n\'est configurée.' );
			}

			$adh_paiement = AmapressAdhesion_paiement::getForUser( $user_id, $adh_period_date, false );

			if ( empty( $adh_paiement ) ) {
				echo '<p><strong>Pour vous engager dans l’AMAP, vous devez adhérer à notre Association.</strong><br/>
<form method="get" action="' . esc_attr( $adhesion_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="adhesion" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Adhérer" />
</form></p>';
			} else {
				$print_bulletin = '';
				if ( $adh_paiement->getPeriod()->getWordModelId() ) {
					$print_bulletin = Amapress::makeButtonLink(
						add_query_arg( [
							'inscr_assistant' => 'generate_bulletin',
							'adh_id'          => $adh_paiement->ID,
							'inscr_key'       => $key
						] ),
						'Imprimer', true, true, 'btn btn-default'
					);
				}
				echo '<p>Votre adhésion à l\'AMAP est valable jusqu\'au ' . date_i18n( 'd/m/Y', $adh_period->getDate_fin() ) . '.<br />
' . $print_bulletin . '</p>';
			}
		}

		$display_remaining_contrats = true;
		if ( ! $admin_mode && ! $has_principal_contrat ) {
			$display_remaining_contrats = false;
			if ( count( $principal_contrats ) == 1 ) {
				?>
                <p>Pour accéder à tous nos contrats en ligne,
                    vous devez d’abord vous inscrire au contrat
                    “<strong><?php echo esc_html( $principal_contrats[0]->getTitle() ); ?></strong>”
                    (<?php echo $principal_contrats[0]->getModel()->linkToPermalinkBlank( 'plus d\'infos' ); ?>)
                </p>
                <p><?php
					$inscription_url = add_query_arg( [
						'step'       => 'inscr_contrat_date_lieu',
						'contrat_id' => $principal_contrats[0]->ID
					] );
					echo '<form action="' . esc_attr( $inscription_url ) . '" method="get">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu"/>
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $principal_contrats[0]->ID . '"/>
<input type="submit" class="btn btn-default btn-assist-inscr" value="Confirmer"/>
</form>';
					?>
                </p>
				<?php
			} else {
				?>
                <p>Pour accéder à tous nos contrats en ligne, vous devez d’abord vous
                    inscrire à l’un des contrats suivants :</p>
				<?php
				foreach ( $principal_contrats as $principal_contrat ) {
					?>
                    <p>
                        “<strong><?php echo esc_html( $principal_contrat->getTitle() ); ?></strong>”
                        (<?php echo $principal_contrat->getModel()->linkToPermalinkBlank( 'plus d\'infos' ); ?>)
                    </p>
                    <p><?php
						$inscription_url = add_query_arg( [
							'step'       => 'inscr_contrat_date_lieu',
							'contrat_id' => $principal_contrat->ID
						] );
						echo '<form action="' . esc_attr( $inscription_url ) . '" method="get">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu"/>
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $principal_contrat->ID . '"/>
<input type="submit" class="btn btn-default btn-assist-inscr" value="Confirmer"/>
</form>';
						?>
                    </p>
					<?php
				}
			}
		} else if ( ! empty( $adhs ) ) {
			if ( ! $admin_mode ) {
				echo '<p>Vos contrats :</p>';
			} else {
				echo '<p>Ses contrats :</p>';
			}
			echo '<ul style="list-style-type: disc">';
			foreach ( $adhs as $adh ) {
				$print_contrat = '';
				if ( ! empty( $adh->getContrat_instance()->getContratModelDocFileName() ) ) {
					$print_contrat = Amapress::makeButtonLink(
						add_query_arg( [
							'inscr_assistant' => 'generate_contrat',
							'inscr_id'        => $adh->ID,
							'inscr_key'       => $key
						] ),
						'Imprimer', true, true, 'btn btn-default'
					);
				}
				if ( $admin_mode ) {
					echo '<li style="margin-left: 35px">' . esc_html( $adh->getTitle() ) .
					     ( current_user_can( 'edit_post', $adh->ID ) ?
						     ' (' . Amapress::makeLink( $adh->getAdminEditLink(), 'Editer', true, true ) . ')<br/>' . $print_contrat . '</li>' : '' );
				} else {
					$rattrapage   = $adh->getProperty( 'dates_rattrapages' );
					$contrat_info = 'Vous avez choisi le(s) panier(s) "' . $adh->getProperty( 'quantites' ) . '" pour '
					                . $adh->getProperty( 'nb_distributions' ) . ' distribution(s) pour un montant total de ' . $adh->getProperty( 'total' ) . ' € (' . $adh->getProperty( 'option_paiements' ) . ')'
					                . '<br/>' . $adh->getProperty( 'nb_dates' ) . ' dates distributions : ' . $adh->getProperty( 'dates_distribution_par_mois' )
					                . ( ! empty( $rattrapage ) ? '<br/>Dates de rattrages : ' . $rattrapage : '' );
					echo '<li style="margin-left: 35px">' . esc_html( $adh->getTitle() ) . '<br/><em style="font-size: 0.9em">' . $contrat_info . '</em><br/>' . $print_contrat . '</li>';
				}
			}
			echo '</ul>';
			if ( ! empty( $user_subscribable_contrats ) ) {
				if ( ! $admin_mode ) {
					echo '<p>A quel contrat souhaitez-vous vous inscrire ?</p>';
				} else {
					echo '<p>A quel contrat souhaitez-vous vous inscrire cet amapien ?</p>';
				}
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

		if ( $display_remaining_contrats ) {
			$adhs_contrat_ids           = array_map( function ( $a ) {
				/** @var AmapressAdhesion $a */
				return $a->getContrat_instanceId();
			}, $adhs );
			$user_subscribable_contrats = array_filter( $subscribable_contrats, function ( $c ) use ( $adhs_contrat_ids ) {
				return ! in_array( $c->ID, $adhs_contrat_ids );
			} );
			if ( ! $admin_mode && ! $has_principal_contrat ) {
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $principal_contrats_ids ) {
					return in_array( $c->ID, $principal_contrats_ids );
				} );
			}
			if ( Amapress::toBool( $atts['filter_multi_contrat'] ) ) {
				$adhs_contrat_ids           = array_map( function ( $a ) {
					/** @var AmapressAdhesion $a */
					return $a->getContrat_instance()->getModelId();
				}, $adhs );
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $adhs_contrat_ids ) {
					/** @var AmapressContrat_instance $c */
					return ! in_array( $c->getModelId(), $adhs_contrat_ids );
				} );
			}
			if ( $admin_mode ) {
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $c ) use ( $principal_contrats_ids ) {
					return current_user_can( 'edit_post', $c->ID );
				} );
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $contrat ) use ( $atts ) {
					/** @var AmapressContrat_instance $contrat */
					$before_close_hours = 0;
					if ( 0 == $before_close_hours ) {
						$before_close_hours = intval( $atts['before_close_hours'] );
					}
					$dates = array_values( $contrat->getListe_dates() );
					$dates = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours ) {
						$real_date = $contrat->getRealDateForDistribution( $d );

						return ( Amapress::start_of_day( $real_date ) - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time();
					} );

					return ! empty( $dates );
				} );
			} else {
				$user_subscribable_contrats = array_filter( $user_subscribable_contrats, function ( $contrat ) use ( $atts ) {
					/** @var AmapressContrat_instance $contrat */
					$before_close_hours = 0;
					if ( 0 == $before_close_hours ) {
						$before_close_hours = intval( $atts['before_close_hours'] );
					}
					$dates                = array_values( $contrat->getListe_dates() );
					$dates_before_cloture = array_filter( $dates, function ( $d ) use ( $contrat ) {
						$real_date = $contrat->getRealDateForDistribution( $d );

						return Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() );
					} );
					$dates                = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours, $dates_before_cloture ) {
						$real_date = $contrat->getRealDateForDistribution( $d );

						return ( Amapress::start_of_day( $real_date ) - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time()
						       && ( empty( $dates_before_cloture ) || Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() ) );
					} );

					return ! empty( $dates );
				} );
			}
			if ( ! empty( $user_subscribable_contrats ) ) {
				echo '<p>Contrats disponibles :</p>';
				echo '<ul style="list-style-type: disc">';
				foreach ( $user_subscribable_contrats as $contrat ) {
					$inscription_url = add_query_arg( [
						'step'       => 'inscr_contrat_date_lieu',
						'contrat_id' => $contrat->ID
					] );
					if ( $admin_mode ) {
						echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ' (' . Amapress::makeLink( $contrat->getAdminEditLink(), 'Editer', true, true ) . ') : <br/><a class="button button-secondary" href="' . esc_attr( $inscription_url ) . '">Ajouter une inscription</a></li>';
					} else {
						echo '<li style="margin-left: 35px">' . esc_html( $contrat->getTitle() ) . ' (' . $contrat->getModel()->linkToPermalinkBlank( 'plus d\'infos' ) . ') : 
<br/>
<form method="get" action="' . esc_attr( $inscription_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="inscr_contrat_date_lieu" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="hidden" name="contrat_id" value="' . $contrat->ID . '" />
<input type="submit" value="M\'inscrire" class="btn btn-default btn-assist-inscr" />
</form></li>';
					}
				}
				echo '</ul>';
			} else {
				if ( ! $admin_mode ) {
					echo '<p>Vous êtes déjà inscrit à tous les contrats.</p>';
				} else {
					echo '<p>Il est inscrit à tous les contrats que vous gérez.</p>';
				}
			}
		}

		if ( ! $admin_mode && $has_principal_contrat && ! Amapress::toBool( $atts['show_contrats'] ) ) {
			echo '<p>J\'ai fini :<br/>
<form method="get" action="' . esc_attr( $the_end_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="the_end" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input type="submit" value="Terminer" class="btn btn-default btn-assist-end" />
</form></p>';
		}
	} else if ( 'inscr_contrat_date_lieu' == $step ) {
		$next_step_url = add_query_arg( 'step', 'inscr_contrat_engage' );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		$contrat    = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message );
		}

		$lieux = $contrat->getLieux();
		if ( empty( $lieux ) ) {
			ob_clean();

			return ( '<p><strong>Attention</strong> : le contrat ' . Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle() ) . ' n\'a aucun lieu de livraison associé. Veuillez corriger ce contrat avant de poursuivre.</p>' );
		}
		?>
        <h4>Étape 5/8 : Date et lieu</h4>
        <form action="<?php echo $next_step_url; ?>" method="post" class="amapress_validate">
			<?php
			$before_close_hours = 0;
			if ( 0 == $before_close_hours ) {
				$before_close_hours = intval( $atts['before_close_hours'] );
			}
			if ( $admin_mode ) {
				$before_close_hours = - 24;
			}
			$dates              = array_values( $contrat->getListe_dates() );
			$first_contrat_date = $dates[0];
			//			$last_contrat_date = $dates[count($dates) - 1];
			if ( ! $admin_mode ) {
				$dates_before_cloture = array_filter( $dates, function ( $d ) use ( $contrat ) {
					$real_date = $contrat->getRealDateForDistribution( $d );

					return Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() );
				} );
				$dates                = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours, $dates_before_cloture ) {
					$real_date = $contrat->getRealDateForDistribution( $d );

					return ( Amapress::start_of_day( $real_date ) - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time()
					       && ( empty( $dates_before_cloture ) || Amapress::start_of_day( $real_date ) < Amapress::end_of_day( $contrat->getDate_cloture() ) );
				} );
			} else {
				$dates = array_filter( $dates, function ( $d ) use ( $contrat, $before_close_hours ) {
					$real_date = $contrat->getRealDateForDistribution( $d );

					return Amapress::end_of_week( $real_date ) > amapress_time();
				} );
			}
			$dates            = array_values( $dates );
			$first_avail_date = $dates[0];
			$is_started       = $first_avail_date != $first_contrat_date;
			if ( ! $admin_mode ) {
				echo '<p>Les inscriptions en ligne sont ouvertes du “' . date_i18n( 'd/m/Y', $contrat->getDate_ouverture() ) . '” au “' . date_i18n( 'd/m/Y', $contrat->getDate_cloture() ) . '”, hors de cette période, je contacte l\'AMAP pour préciser ma demande : “<a href="mailto:' . esc_attr( $atts['email'] ) . '">' . esc_html( $atts['email'] ) . '</a>”</p>';
			}
			echo '<p><strong>Date</strong></p>';
			if ( ! $is_started && ! $admin_mode ) {
				echo '<input type="hidden" name="start_date" value="' . $first_avail_date . '" />';
				$first_date_dist = $contrat->getRealDateForDistribution( $first_contrat_date );
				$last_date_dist  = $contrat->getDate_fin();
				if ( 1 == count( $contrat->getListe_dates() ) ) {
					echo '<p>Je m’inscris pour la distribution ponctuelle du ' . date_i18n( 'l d F Y', $first_date_dist ) . '</p>';
				} else {
					echo '<p>Je m’inscris pour la saison complète : du ' . date_i18n( 'l d F Y', $first_date_dist ) . ' au ' . date_i18n( 'l d F Y', $last_date_dist ) . '
 (' . count( $contrat->getListe_dates() ) . ' dates de distributions)</p>';
				}
			} else {
				?>
                <p><?php
					if ( ! $admin_mode ) {
						echo 'Je m\'inscris en cours de saison, je récupère mon panier à la prochaine distribution ou je choisis une
                    date ultérieure :';
					} else {
						echo 'A partir de quel date doit-il commencer son contrat :';
					}
					?>
                    <br/>
                    <select name="start_date" id="start_date" class="required">
						<?php
						foreach ( $dates as $date ) {
							$real_date = $contrat->getRealDateForDistribution( $date );
							$val_date  = date_i18n( 'd/m/Y', $real_date );
							if ( Amapress::start_of_day( $date ) != Amapress::start_of_day( $real_date ) ) {
								$val_date .= ' initialement le ' . date_i18n( 'd/m/Y', $date );
							}
							if ( $date == $first_avail_date ) {
								if ( $is_started ) {
									$val_date = "Prochaine distribution ($val_date)";
								} else {
									$val_date = "Première distribution ($val_date)";
								}
							}
							echo '<option value="' . esc_attr( $date ) . '">' . esc_html( $val_date ) . '</option>';
						}
						?>
                    </select>
                </p>
				<?php
			}

			echo '<p><strong>Lieu</strong></p>';
			if ( count( $lieux ) > 1 ) {
				if ( ! $admin_mode ) {
					echo '<p style="margin-bottom: 0">Je récupérerai mon panier à :</p>';
				} else {
					echo '<p style="margin-bottom: 0">Veuillez chosir son lieu de distribution :</p>';
				}
				foreach ( $lieux as $lieu ) {
					$lieu_id    = $lieu->ID;
					$lieu_title = $lieu->linkToPermalinkBlank( esc_html( $lieu->getLieuTitle() ) ) . ' (' . esc_html( $lieu->getFormattedAdresse() ) . ')';
					echo "<p style='margin-top: 0;margin-bottom: 0'><input id='lieu-$lieu_id' name='lieu_id' value='$lieu_id' type='radio' class='required' /><label for='lieu-$lieu_id'>$lieu_title</label></p>";
				}
			} else {
				echo '<p>Je récupérerai mon panier à ' . esc_html( $lieux[0]->getLieuTitle() ) . '</p>';
				echo '<input name="lieu_id" value="' . $lieux[0]->ID . '" type="hidden" />';
			}
			//			foreach ( $dates as $date ) {
			//				echo '<option value="' . esc_attr( $date ) . '">' . esc_html( date_i18n( 'd/m/Y', $date ) ) . '</option>';
			//			}
			?>
            <br/>
            <input type="submit" value="Valider" class="btn btn-default btn-assist-inscr"/>
        </form>
		<?php
	} else if ( 'inscr_contrat_engage' == $step ) {
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['lieu_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$lieu_id = intval( $_REQUEST['lieu_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message );
		}
		$start_date = intval( $_REQUEST['start_date'] );

		$next_step_url = add_query_arg( [
			'step'       => 'inscr_contrat_paiements',
			'start_date' => $start_date,
			'lieu_id'    => $lieu_id
		] );

		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message );
		}

		$dates             = $contrat->getListe_dates();
		$dates             = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return $d >= $start_date;
		} );
		$rattrapage        = [];
		$double_rattrapage = [];
		$un5_rattrapage    = [];
		foreach ( $dates as $d ) {
			$the_factor = $contrat->getDateFactor( $d );
			if ( abs( $the_factor - 2 ) < 0.001 ) {
				$double_rattrapage[] = date_i18n( 'd/m/Y', $d );
			} else if ( abs( $the_factor - 1.5 ) < 0.001 ) {
				$un5_rattrapage[] = date_i18n( 'd/m/Y', $d );
			} else if ( abs( $the_factor - 1 ) > 0.001 ) {
				$rattrapage[] = $the_factor . ' distribution le ' . date_i18n( 'd/m/Y', $d );
			}
		}

		if ( ! empty( $double_rattrapage ) ) {
			$rattrapage[] = 'double distribution ' . _n( 'le', 'les', count( $double_rattrapage ) ) . ' ' . implode( ', ', $double_rattrapage );
		}
		if ( ! empty( $un5_rattrapage ) ) {
			$rattrapage[] = '1.5 distribution ' . _n( 'le', 'les', count( $un5_rattrapage ) ) . ' ' . implode( ', ', $un5_rattrapage );
		}

		if ( ! $admin_mode ) {
			?>
            <h4>Étape 6/8 : Panier</h4>
			<?php
		} else {
			?>
            <h4>Étape 6/8 : Panier - <?php echo esc_html( $contrat->getTitle() ); ?></h4>
			<?php
		}
		$min_total = $contrat->getMinEngagement();

		$grouped_dates = from( $dates )->groupBy( function ( $d ) {
			return date_i18n( 'F Y', $d );
		} );

		$grouped_dates_array = [];
		foreach ( $grouped_dates as $k => $v ) {
			$grouped_dates_array[] = $k . ' : ' . ( count( $v ) > 1 ? 'les ' : 'le ' ) . implode( ', ', array_map(
					function ( $d ) {
						return date_i18n( 'd', $d );
					}, $v
				) );
		}

		$dates_factors = 0;
		foreach ( $dates as $d ) {
			$dates_factors += $contrat->getDateFactor( $d );
		}

		$rattrapage_renvoi = '';
		if ( ! empty( $rattrapage ) ) {
			$rattrapage_renvoi = '<a href="#dist_rattrapages">*</a>';
		}
//		echo $contrat->getOnlineContrat();
		if ( count( $contrat->getListe_dates() ) == count( $dates ) ) {
			echo '<p style="padding-bottom: 0; margin-bottom: 0">Ce contrat comporte “<strong>' . $dates_factors . '</strong>” distributions (étalées sur “<strong>' . count( $dates ) . '</strong>” dates' . $rattrapage_renvoi . ') :</p>';
		} else {
			echo '<p style="padding-bottom: 0; margin-bottom: 0">Il reste “<strong>' . $dates_factors . '</strong>” distributions (étalées sur “<strong>' . count( $dates ) . '</strong>” dates' . $rattrapage_renvoi . ') avant la fin de la saison :</p>';
		}
		echo '<ul style="list-style-type: disc; padding-top: 0; margin-top: 0">';
		foreach ( $grouped_dates_array as $entry ) {
			echo '<li style="margin-left: 35px">' . esc_html( $entry ) . '</li>';
		}
		echo '</ul>';

		$reports = [];
		foreach ( $dates as $d ) {
			$real_date = $contrat->getRealDateForDistribution( $d );
			if ( Amapress::start_of_day( $real_date ) != Amapress::start_of_day( $d ) ) {
				$reports[] = 'livraison du ' . date_i18n( 'd/m/Y', $d ) . ' reportée au ' . date_i18n( 'd/m/Y', $real_date );
			}
		}
		if ( ! empty( $reports ) ) {
			echo '<p>Report(s) de livraison : ' . implode( ', ', $reports ) . '</p>';
		}

		if ( ! empty( $rattrapage ) ) {
			echo '<p><a id="dist_rattrapages">*</a>Distribution(s) de rattrapage : ' . implode( ', ', $rattrapage ) . '</p>';
		}

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
				array(
					'title' => 'Prix',
					'data'  => 'prix_unitaire',
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
					'produit'       => esc_html( $quant->getTitle() ),
					'prix_unitaire' => esc_html( sprintf( '%.2f€', $quant->getPrix_unitaire() ) ),
				);
				$options = $quant->getQuantiteOptions();
				if ( ! isset( $options['0'] ) ) {
					$options = [ '0' => '0' ] + $options;
				}
				foreach ( $dates as $date ) {
					$price_unit = esc_attr( $quant->getPrix_unitaire() );
					$ed         = '';
					$ed         .= "<select style='max-width: none;min-width: 0' data-price='0' data-price-unit='$price_unit' name='panier_vars[$date][{$quant->ID}]' id='panier_vars-$date-{$quant->ID}' class='quant-var'>";
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
				'fixedColumns' => array( 'leftColumns' => 2 ),
			) );
			echo '<p>* Cliquez sur la case pour faire apparaître le choix de quantités</p>';
		} else {
			$contrat_quants = AmapressContrats::get_contrat_quantites( $contrat->ID );
			foreach ( $contrat_quants as $quantite ) {
				$dates_factors = 0;
				foreach ( $dates as $d ) {
					$dates_factors += $contrat->getDateFactor( $d, $quantite->ID );
				}

				if ( abs( $dates_factors ) < 0.001 ) {
					continue;
				}

				$quant_var_editor   = '';
				$id_quant           = 'quant' . $quantite->ID;
				$id_factor          = 'factor' . $quantite->ID;
				$id_price           = 'price' . $quantite->ID;
				$price              = $dates_factors * $quantite->getPrix_unitaire();
				$price_compute_text = esc_html( $dates_factors ) . ' x ' . esc_html( $quantite->getPrix_unitaire() ) . '€';
				if ( $contrat->isQuantiteVariable() ) {
					$quant_var_editor .= "<select  style='max-width: none;min-width: 0' id='$id_factor' class='quant-factor' data-quant-id='$id_quant' data-price-id='$id_price' data-price-unit='$price' name='factors[{$quantite->ID}]' style='display: inline-block'>";
					$quant_var_editor .= tf_parse_select_options(
						$quantite->getQuantiteOptions(),
						null,
						false );
					$quant_var_editor .= '</select>';
				}

				$type = $contrat->isQuantiteMultiple() ? 'checkbox' : 'radio';
				echo '<p style="margin-top: 1em; margin-bottom: 0"><label for="' . $id_quant . '">
			<input id="' . $id_quant . '" name="quants[]" class="quant" value="' . $quantite->ID . '" type="' . $type . '" data-factor-id="' . $id_factor . '" data-price="' . $price . '"/> 
			' . $quant_var_editor . ' ' . esc_html( $quantite->getTitle() ) . ' ' . $price_compute_text . ' = <span id="' . $id_price . '">' . $price . '</span>€</label></p>';

				$spec_dates = $quantite->getSpecificDistributionDates();
				if ( ! empty( $spec_dates ) ) {
					$spec_dates = array_filter( $spec_dates, function ( $d ) use ( $start_date ) {
						return $d >= $start_date;
					} );
				}
				if ( ! empty( $spec_dates ) ) {

					$grouped_dates = from( $spec_dates )->groupBy( function ( $d ) {
						return date_i18n( 'F Y', $d );
					} );

					$grouped_dates_array = [];
					foreach ( $grouped_dates as $k => $v ) {
						$grouped_dates_array[] = $k . ' : ' . ( count( $v ) > 1 ? 'les ' : 'le ' ) . implode( ', ', array_map(
								function ( $d ) {
									return date_i18n( 'd', $d );
								}, $v
							) );
					}
					echo '<p style="font-style: italic; font-size: 0.8em; padding-left: 15pt; padding-top: 0; margin-top: 0">Dates spécifiques : ' . implode( ' ; ', $grouped_dates_array ) . '</p>';
				}
			}
		}
		echo '<p style="margin-top: 1em;">Total: <span id="total">0</span>€</p>';
		echo '<p><input type="submit" class="btn btn-default btn-assist-inscr" value="Valider" /></p>';
		echo '</form>';

	} else if ( 'inscr_contrat_paiements' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message );
		}
		$start_date = intval( $_REQUEST['start_date'] );

		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( empty( $contrat ) ) {
			wp_die( $invalid_access_message );
		}
		$next_step_url = add_query_arg( [ 'step' => 'inscr_contrat_create' ] );

		echo '<h4>Étape 7/8 : Règlement</h4>';
		if ( $contrat->isPanierVariable() ) {
			$panier_vars = isset( $_REQUEST['panier_vars'] ) ? $_REQUEST['panier_vars'] : [];
			if ( empty( $panier_vars ) ) {
				wp_die( $invalid_access_message );
			}

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
				echo '<ul style="list-style-type: disc">';
				foreach ( $quant_descs as $quant_desc ) {
					echo '<li style="margin-left: 15px">' . esc_html( $quant_desc ) . '</li>';
				}
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			$quants = isset( $_REQUEST['quants'] ) ? $_REQUEST['quants'] : [];
			if ( ! is_array( $quants ) ) {
				$quants = [ $quants ];
			}

			if ( empty( $quants ) ) {
				wp_die( $invalid_access_message );
			}

			$factors = isset( $_REQUEST['factors'] ) ? $_REQUEST['factors'] : [];

			$dates = $contrat->getListe_dates();
			$dates = array_filter( $dates, function ( $d ) use ( $start_date ) {
				return $d >= $start_date;
			} );

			$total         = 0;
			$chosen_quants = [];
			$serial_quants = [];
			foreach ( $quants as $q ) {
				$q_id = intval( $q );

				$dates_factors = 0;
				foreach ( $dates as $d ) {
					$dates_factors += $contrat->getDateFactor( $d, $q_id );
				}

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
					echo '<p style="margin-bottom: 0">Vous avez choisi les options suivantes du contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€ :</p>';
				} else {
					$amapien = AmapressUser::getBy( $user_id );
					echo '<p style="margin-bottom: 0">Vous allez inscrire ' . esc_html( $amapien->getDisplayName() ) . ' au contrat ' . esc_html( $contrat->getTitle() ) . ' pour un montant de ' . $total . '€ avec les options suivantes:</p>';
				}
				echo '<ul style="list-style-type: disc">';
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
		if ( $contrat->getAllow_Cash() ) {
			echo "<input type='radio' name='cheques' id='cheques-esp' value='-1' class='required' /><label for='cheques-esp'>En espèces</label><br/>";
		}
		echo '<br />';
		if ( ! $admin_mode ) {
			echo '<label for="inscr_message">Message pour le référent :</label><textarea id="inscr_message" name="message"></textarea>';
		} else {
			echo '<p><input type="checkbox" checked="checked" id="inscr_confirm_mail" name="inscr_confirm_mail" /><label for="inscr_confirm_mail"> Confirmer par mail à l\'adhérent</label></p>';
		}
		echo '<input type="submit" value="Valider" class="btn btn-default btn-assist-inscr" />';
		echo '</form>';
	} else if ( 'inscr_contrat_create' == $step ) {
		if ( empty( $_REQUEST['user_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$user_id = intval( $_REQUEST['user_id'] );
		if ( empty( $_REQUEST['contrat_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$contrat_id = intval( $_REQUEST['contrat_id'] );
		if ( empty( $_REQUEST['lieu_id'] ) ) {
			wp_die( $invalid_access_message );
		}
		$lieu_id = intval( $_REQUEST['lieu_id'] );
		if ( empty( $_REQUEST['start_date'] ) ) {
			wp_die( $invalid_access_message );
		}
		$start_date = intval( $_REQUEST['start_date'] );

		$message = sanitize_textarea_field( isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '' );

		$amapien = AmapressUser::getBy( $user_id );
		$lieu    = AmapressLieu_distribution::getBy( $lieu_id );
		$contrat = AmapressContrat_instance::getBy( $contrat_id );
		if ( ! $amapien || ! $lieu || ! $contrat ) {
			wp_die( $invalid_access_message );
		}

		if ( empty( $_REQUEST['cheques'] ) ) {
			wp_die( $invalid_access_message );
		}
		$cheques = intval( $_REQUEST['cheques'] );
		if ( empty( $_REQUEST['quants'] ) ) {
			wp_die( $invalid_access_message );
		}
		$quants = unserialize( stripslashes( $_REQUEST['quants'] ) );
		if ( empty( $quants ) ) {
			wp_die( $invalid_access_message );
		}

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
				$f              = floatval( $q['f'] );
				if ( abs( $f - 1.0 ) > 0.001 ) {
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
			'amapress_adhesion_paiements'        => ( - 1 == $cheques ? 1 : $cheques ),
			'amapress_adhesion_lieu'             => $lieu_id,
		];
		if ( - 1 == $cheques ) {
			$meta['amapress_adhesion_pmt_type'] = 'esp';
		}
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
		if ( $inscription->getContrat_instance()->getManage_Cheques() ) {
			$inscription->preparePaiements();
		}

		if ( ! $admin_mode || isset( $_REQUEST['inscr_confirm_mail'] ) ) {
			$inscription->sendConfirmationMail();
		}

		if ( ! $admin_mode ) {
			if ( Amapress::toBool( $atts['send_referents'] ) ) {
				$referents = [];
				foreach ( $inscription->getContrat_instance()->getModel()->getProducteur()->getReferentsIds() as $ref_id ) {
					$user_obj  = AmapressUser::getBy( $ref_id );
					$referents = array_merge( $referents, $user_obj->getAllEmails() );
				}

				amapress_wp_mail(
					$referents,
					'Nouvelle inscription - ' . $inscription->getContrat_instance()->getTitle() . ' - ' . $inscription->getAdherent()->getDisplayName(),
					wpautop( "Bonjour,\nUne nouvelle inscription est en attente de validation : " . Amapress::makeLink( $inscription->getAdminEditLink(), $inscription->getTitle() ) . "\n\n" . get_bloginfo( 'name' ) )
				);
			}

			$adhs                               = AmapressAdhesion::getUserActiveAdhesions( $user_id, null, null, false, true );
			$adhs_contrat_ids                   = array_map( function ( $a ) {
				/** @var AmapressAdhesion $a */
				return $a->getContrat_instance()->ID;
			}, $adhs );
			$adhs_contrat_ids[]                 = $inscription->getContrat_instance()->ID;
			$user_subscribable_contrats         = array_filter( $subscribable_contrats, function ( $c ) use ( $adhs_contrat_ids ) {
				return ! in_array( $c->ID, $adhs_contrat_ids );
			} );
			$user_subscribable_contrats_display = implode( ', ', array_unique( array_map( function ( $c ) {
				/** @var AmapressContrat_instance $c */
				return $c->getModelTitle() . ( ! empty( $c->getSubName() ) ? ' - ' . $c->getSubName() : '' );
			}, $user_subscribable_contrats ) ) );
			echo '<h4>étape 8/8 : Félicitations !</h4>';
			echo '<div class="alert alert-success">Votre pré-inscription a bien été prise en compte. 
Vous allez recevoir un mail de confirmation avec votre contrat dans quelques minutes. (Pensez à regarder vos spams, ce mail peut s\'y trouver à cause du contrat joint ou pour expéditeur inconnu de votre carnet d\'adresses)</div>';
			if ( ! empty( $inscription->getContrat_instance()->getContratModelDocFileName() ) ) {
				$print_contrat = Amapress::makeButtonLink(
					add_query_arg( [
						'inscr_assistant' => 'generate_contrat',
						'inscr_id'        => $inscription->ID,
						'inscr_key'       => $key
					] ),
					'Imprimer', true, true, 'btn btn-default'
				);
				echo '<p>Pour finaliser votre inscription, vous devez imprimer ce contrat et le remettre aux référents concernés (' . $inscription->getProperty( 'referents' ) . ') avec les chèques/règlements correspondants lors de la prochaine distribution<br />
' . $print_contrat . '</p>';
			}
			if ( Amapress::toBool( $atts['show_contrats'] ) ) {
				echo '<p>Retourner à la liste de mes contrats :<br/>
<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
			} else {
				if ( ! empty( $user_subscribable_contrats ) ) {
					echo '<p>Vous pouvez également découvrir et éventuellement adhérer aux contrats suivants (' . $user_subscribable_contrats_display . ') :<br/>
<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="contrats" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Poursuivre" />
</form></p>';
				} else {
					echo '<p>Vous êtes déjà inscrit à tous les contrats.</p>';
				}
			}

			if ( ! $admin_mode && ! Amapress::toBool( $atts['show_contrats'] ) ) {
				echo '<p>J\'ai fini :<br/>
<form method="get" action="' . esc_attr( $contrats_step_url ) . '">
<input type="hidden" name="key" value="' . $key . '" />
<input type="hidden" name="step" value="the_end" />
<input type="hidden" name="user_id" value="' . $user_id . '" />
<input class="btn btn-default btn-assist-inscr" type="submit" value="Terminer" />
</form></p>';
			}
		} else {
			echo '<div class="alert alert-success">L\'inscription a bien été prise en compte : ' . Amapress::makeLink( $inscription->getAdminEditLink(), 'Editer l\'inscription', true, true ) . '</div>';
			echo '<p><a href="' . esc_attr( $contrats_step_url ) . '" >Retourner à la liste de ses contrats</a></p>';
		}

	} else if ( 'the_end' == $step ) {
		echo '<h4>Félicitations, vous avez terminé vos inscriptions !</h4>';
		echo '<p>Si vous êtes nouvel adhérent vous allez recevoir un mail vous indiquant comment vous connecter au site et choisir votre mot de passe.</p>';
		echo '<p>Vous allez recevoir un mail de confirmation pour chacune de vos inscriptions avec le contrat à imprimer et les instructions pour remettre vos chèques/règlements aux référents.</p>';
		echo '<p>(Pensez à regarder vos spams, ces mails peuvent s\'y trouver à cause des contrats joints ou pour expéditeur inconnu de votre carnet d\'adresses)</p>';
		echo '<p>Vous pouvez maintenant fermer cette fenêtre/onglet et regarder votre messagerie</p>';
	}
	?>
    <style type="text/css">
        #quant-commandes td {
            text-align: center
        }

        label {
            display: inline !important;
        }

        label#accept-error {
            display: block !important;
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
                        if ("radio" === element.attr("type") || "checkbox" === element.attr("type")) {
                            error.insertBefore(element);
                        } else {
                            error.insertAfter(element);
                        }
                    }
                }
                }
            );

            jQuery.validator.addMethod("required_if_not_empty", function (value, element) {
                if (jQuery('#' + jQuery(element).data('if-id')).val().length > 0) {
                    return jQuery(element).val().trim().length > 0;
                }
                return true;
            }, "Champ requis");

            jQuery.validator.addMethod("single_name", function (value, element) {
                return !(/[;\/\\,]/.test(jQuery(element).val()));
            }, "Merci de ne saisir qu'un seul nom ou prénom. Utilisez les champs de coadhérents pour vos coadhérents.");

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
                jQuery('#total').text(total.toFixed(2));
            }

            function computePrices() {
                var $this = jQuery(this);
                var priceUnit = parseFloat($this.data('price-unit'));
                var val = parseFloat($this.val());
                var quantElt = jQuery('#' + $this.data('quant-id'));
                var priceElt = jQuery('#' + $this.data('price-id'));
                priceElt.text((val * priceUnit).toFixed(2));
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
            });
            jQuery('.quant-var:first,.quant:first').each(function () {
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
            });
            computeTotal();

            var updateAmount = function () {
                var sum = 0;
                jQuery(".amapress_pmt_cat_amount").each(function () {
                    sum += parseFloat(jQuery(this).val());
                });
                jQuery("#amapress_adhesion_paiement_amount").text(sum.toFixed(2));
            };
            jQuery(".amapress_pmt_cat_amount").on("change paste keyup", updateAmount);
            updateAmount();
        });
        //]]>
    </script>
	<?php

	return ob_get_clean();
}