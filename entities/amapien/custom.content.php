<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'file_is_displayable_image' ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	require_once ABSPATH . '/wp-admin/includes/image.php';
}

function amapress_edit_user_info_shortcode( $atts ) {
	if ( is_admin() || ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts                  = shortcode_atts(
		[
			'edit_names'            => 'true',
			'max_cofoyers'          => 3,
			'show_cofoyers_address' => 'false',
			'show_adherents_infos'  => 'true',
			'allow_remove_cofoyers' => 'true',
			'mob_phone_required'    => 'false',
		], $atts
	);
	$show_adherents_infos  = Amapress::toBool( $atts['show_adherents_infos'] );
	$edit_names            = Amapress::toBool( $atts['edit_names'] );
	$max_cofoyers          = intval( $atts['max_cofoyers'] );
	$allow_remove_cofoys   = Amapress::toBool( $atts['allow_remove_cofoyers'] );
	$show_cofoyers_address = Amapress::toBool( $atts['show_cofoyers_address'] );

	ob_start();

	?>
	<?php
	$user_id = amapress_current_user_id();
	$user    = AmapressUser::getBy( $user_id );

	if ( $show_adherents_infos ) {
		echo '<p>' . $user->getAdherentInfo() . '</p>';
	}

	$cofoy1_user_firt_name = '';
	$cofoy1_user_last_name = '';
	$cofoy1_email          = '';
	$cofoy1_mobile_phones  = '';
	$cofoy1_address        = '';

	$cofoy2_user_firt_name = '';
	$cofoy2_user_last_name = '';
	$cofoy2_email          = '';
	$cofoy2_mobile_phones  = '';
	$cofoy2_address        = '';

	$cofoy3_user_firt_name = '';
	$cofoy3_user_last_name = '';
	$cofoy3_email          = '';
	$cofoy3_mobile_phones  = '';
	$cofoy3_address        = '';

	if ( $user->getCoFoyer1() ) {
		$cofoy1_user_firt_name = $user->getCoFoyer1()->getUser()->first_name;
		$cofoy1_user_last_name = $user->getCoFoyer1()->getUser()->last_name;
		$cofoy1_email          = $user->getCoFoyer1()->getUser()->user_email;
		$cofoy1_mobile_phones  = implode( '/', $user->getCoFoyer1()->getPhoneNumbers() );
		$cofoy1_address        = $user->getCoFoyer1()->getFormattedAdresse();
	}

	if ( $user->getCoFoyer2() ) {
		$cofoy2_user_firt_name = $user->getCoFoyer2()->getUser()->first_name;
		$cofoy2_user_last_name = $user->getCoFoyer2()->getUser()->last_name;
		$cofoy2_email          = $user->getCoFoyer2()->getUser()->user_email;
		$cofoy2_mobile_phones  = implode( '/', $user->getCoFoyer2()->getPhoneNumbers() );
		$cofoy2_address        = $user->getCoFoyer2()->getFormattedAdresse();
	}

	if ( $user->getCoFoyer3() ) {
		$cofoy3_user_firt_name = $user->getCoFoyer3()->getUser()->first_name;
		$cofoy3_user_last_name = $user->getCoFoyer3()->getUser()->last_name;
		$cofoy3_email          = $user->getCoFoyer3()->getUser()->user_email;
		$cofoy3_mobile_phones  = implode( '/', $user->getCoFoyer3()->getPhoneNumbers() );
		$cofoy3_address        = $user->getCoFoyer3()->getFormattedAdresse();
	}
	?>

    <script type="text/javascript">
        //<![CDATA[
        jQuery(function ($) {
            $(".amapress_validate").validate();

            jQuery.validator.addMethod("required_if_not_empty", function (value, element) {
                if (jQuery('#' + jQuery(element).data('if-id')).val().length > 0) {
                    return jQuery(element).val().trim().length > 0;
                }
                return true;
            }, "Champ requis");
        });
        //]]>
    </script>
    <form role="form" action="" id="user_profile" method="POST" enctype="multipart/form-data" class="amapress_validate">
		<?php wp_nonce_field( 'user_profile_nonce', 'user_profile_nonce_field' ); ?>
        <div class="form-group">
            <label for="first_name">Prénom</label>
            <input type="text" class="form-control required" id="first_name" name="first_name" placeholder="Prénom"
                   value="<?php esc_attr_e( $user->getUser()->first_name ); ?>" <?php disabled( ! $edit_names ) ?>>
        </div>
        <div class="form-group">
            <label for="last_name">Nom</label>
            <input type="text" class="form-control required" id="last_name" name="last_name" placeholder="Nom"
                   value="<?php esc_attr_e( $user->getUser()->last_name ); ?>" <?php disabled( ! $edit_names ) ?>>
        </div>
        <div class="form-group">
            <label for="last_name">Nom d'affichage</label>
            <input type="text" class="form-control required" id="display_name" name="display_name"
                   placeholder="Nom d'affichage"
                   value="<?php esc_attr_e( $user->getUser()->display_name ); ?>" <?php disabled( ! $edit_names ) ?>>
        </div>
        <div class="form-group">
            <label for="amapress_user_telephone"><?php _e( 'Téléphone mobile', 'amapress' ) ?></label>
            <input type="text" name="amapress_user_telephone" id="amapress_user_telephone"
                   class="form-control <?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required' : '' ) ?>"
                   value="<?php esc_attr_e( implode( '/', $user->getPhoneNumbers( true ) ) ); ?>"/>
        </div>
        <div class="form-group">
            <label for="amapress_user_telephone2"><?php _e( 'Téléphone fixe', 'amapress' ) ?></label>
            <input class="form-control" type="text" name="amapress_user_telephone2" id="amapress_user_telephone2"
                   value="<?php esc_attr_e( implode( '/', $user->getPhoneNumbers( false ) ) ); ?>"/>
        </div>
        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" class="form-control email required" id="email" name="email" placeholder="Email"
                   value="<?php esc_attr_e( $user->getEmail() ); ?>">
        </div>
        <div class="form-group">
            <label for="email">Adresse email 2</label>
            <input type="email" class="form-control email" id="email2" name="email2" placeholder="Email 2"
                   value="<?php esc_attr_e( $user->getEmail( 2 ) ); ?>">
        </div>
        <div class="form-group">
            <label for="email">Adresse email 3</label>
            <input type="email" class="form-control email" id="email3" name="email3" placeholder="Email 3"
                   value="<?php esc_attr_e( $user->getEmail( 3 ) ); ?>">
        </div>
        <div class="form-group">
            <label for="email">Adresse email 4</label>
            <input type="email" class="form-control email" id="email4" name="email4" placeholder="Email 4"
                   value="<?php esc_attr_e( $user->getEmail( 4 ) ); ?>">
        </div>
        <div class="form-group">
            <label for="amapress_user_adresse">
			    <?php _e( 'Adresse', 'amapress' ) ?><br/>
            </label>
            <textarea class="form-control" name="amapress_user_adresse" id="amapress_user_adresse" rows="4"
                      cols="40"
                      placeholder="Adresse"><?php echo esc_textarea( $user->getAdresse() ); ?></textarea>
        </div>
        <div class="form-group">
            <label for="amapress_user_code_postal">
			    <?php _e( 'Code postal', 'amapress' ) ?><br/>
            </label>
            <input class="form-control" type="text" name="amapress_user_code_postal" id="amapress_user_code_postal"
                   value="<?php esc_attr_e( wp_unslash( $user->getCode_postal() ) ); ?>"
                   size="5"/>
        </div>
        <div class="form-group">
            <label for="amapress_user_ville">
			    <?php _e( 'Ville', 'amapress' ) ?><br/>
            </label>
            <input class="form-control" type="text" name="amapress_user_ville" id="amapress_user_ville"
                   value="<?php esc_attr_e( wp_unslash( $user->getVille() ) ); ?>"/>
        </div>
        <div class="form-group">
            <label for="amapress_user_hidaddr">
                <input class="form-control" type="checkbox" name="amapress_user_hidaddr" id="amapress_user_hidaddr"
					<?php echo checked( 1, $user->isHiddenFromTrombi() ); ?>/>
				<?php _e( 'Ne pas apparaître sur le trombinoscope', 'amapress' ) ?><br/>
            </label>
        </div>
        <div class="form-group">
            <label for="amapress_user_moyen">
				<?php _e( 'Moyen de communication préféré', 'amapress' ) ?><br/>
            </label>
            <select class="form-control" name="amapress_user_moyen" id="amapress_user_moyen">
                <option
                        value="mail" <?php selected( $user->getMoyen(), 'mail' ) ?>>
                    Mail
                </option>
                <option
                        value="tel" <?php selected( $user->getMoyen(), 'tel' ) ?>>
                    Téléphone
                </option>
            </select>
        </div>
        <div class="form-group">
            <label for="amapress_user_avatar-upload">Mettre à jour ma photo : <br/><input class="form-control"
                                                                                          type="file" accept="image/*"
                                                                                          name="amapress_user_avatar-upload"
                                                                                          id="amapress_user_avatar-upload"/></label>

			<?php
			echo get_avatar( $user->ID );
			?>
            <div>
                <label for="amapress_user_avatar-delete"><input type="checkbox"
                                                                name="amapress_user_avatar-delete"
                                                                id="amapress_user_avatar-delete" value="1"/>Supprimer ma
                    photo</label>
            </div>
        </div>

		<?php if ( $max_cofoyers >= 1 ) { ?>
            <table style="min-width: 50%">
                <tr>
                    <th colspan="2">Membre du foyer 1 / Conjoint
                    </th>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy1_email">Son email
                            : </label>
                    </th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?>
                                style="width: 100%" type="email"
                                id="cofoy1_email" name="cofoy1_email"
                                class="email <?php echo( ! empty( $cofoy1_email ) ? 'required' : '' ); ?>"
                                value="<?php echo esc_attr( $cofoy1_email ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy1_last_name">Son nom : </label></th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy1_last_name"
                                                                                               name="cofoy1_last_name"
                                                                                               class="required_if_not_empty single_name"
                                                                                               data-if-id="cofoy1_email"
                                                                                               value="<?php echo esc_attr( $cofoy1_user_last_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy1_first_name">Son prénom : </label>
                    </th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy1_first_name"
                                                                                               name="cofoy1_first_name"
                                                                                               class="required_if_not_empty single_name"
                                                                                               data-if-id="cofoy1_email"
                                                                                               value="<?php echo esc_attr( $cofoy1_user_firt_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy1_tels">Téléphone(s) : </label></th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy1_tels"
                                                                                               name="cofoy1_tels"
                                                                                               class="<?php echo( Amapress::toBool( $atts['mob_phone_required'] ) ? 'required_if_not_empty' : '' ) ?>"
                                                                                               data-if-id="cofoy1_email"
                                                                                               value="<?php echo esc_attr( $cofoy1_mobile_phones ) ?>"/>
                    </td>
                </tr>
				<?php if ( $show_cofoyers_address ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy1_address">Adresse : </label>
                        </th>
                        <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy1_email ) ); ?> style="width: 100%"
                                                                                                      rows="4"
                                                                                                      id="cofoy1_address"
                                                                                                      name="cofoy1_address"
                                                                                                      class=""><?php echo esc_textarea( $cofoy1_address ); ?></textarea>
                        </td>
                    </tr>
				<?php } ?>
				<?php if ( $allow_remove_cofoys && ! empty( $cofoy1_email ) ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"></th>
                        <td>
                            <label for="cofoy1_remove"><input type="checkbox" name="cofoy1_remove"
                                                              id="cofoy1_remove"/> Je ne suis plus coadhérent
                                avec <?php echo esc_html( "$cofoy1_user_firt_name $cofoy1_user_last_name" ) ?>
                            </label>
                        </td>
                    </tr>
				<?php } ?>
            </table>
		<?php } ?>
		<?php if ( $max_cofoyers >= 2 ) { ?>
            <table style="min-width: 50%">
                <tr>
                    <th colspan="2">Membre du foyer 2
                    </th>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy2_email">Son email
                            : </label>
                    </th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                               type="email"
                                                                                               id="cofoy2_email"
                                                                                               name="cofoy2_email"
                                                                                               class="email <?php echo( ! empty( $cofoy2_email ) ? 'required' : '' ); ?>"
                                                                                               value="<?php echo esc_attr( $cofoy2_email ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy2_last_name">Son nom : </label></th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy2_last_name"
                                                                                               name="cofoy2_last_name"
                                                                                               class="required_if_not_empty single_name"
                                                                                               data-if-id="cofoy2_email"
                                                                                               value="<?php echo esc_attr( $cofoy2_user_last_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy2_first_name">Son prénom : </label>
                    </th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy2_first_name"
                                                                                               name="cofoy2_first_name"
                                                                                               class="required_if_not_empty single_name"
                                                                                               data-if-id="cofoy2_email"
                                                                                               value="<?php echo esc_attr( $cofoy2_user_firt_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy2_tels">Téléphone(s) : </label></th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy2_tels"
                                                                                               name="cofoy2_tels"
                                                                                               value="<?php echo esc_attr( $cofoy2_mobile_phones ) ?>"/>
                    </td>
                </tr>
				<?php if ( $show_cofoyers_address ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy2_address">Adresse : </label>
                        </th>
                        <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy2_email ) ); ?> style="width: 100%"
                                                                                                      rows="4"
                                                                                                      id="cofoy2_address"
                                                                                                      name="cofoy2_address"
                                                                                                      class=""><?php echo esc_textarea( $cofoy2_address ); ?></textarea>
                        </td>
                    </tr>
				<?php } ?>
				<?php if ( $allow_remove_cofoys && ! empty( $cofoy2_email ) ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"></th>
                        <td>
                            <label for="cofoy2_remove"><input type="checkbox" name="cofoy2_remove"
                                                              id="cofoy2_remove"/> Je ne suis plus coadhérent
                                avec <?php echo esc_html( "$cofoy2_user_firt_name $cofoy2_user_last_name" ) ?>
                            </label>
                        </td>
                    </tr>
				<?php } ?>
            </table>
		<?php } ?>
		<?php if ( $max_cofoyers >= 3 ) { ?>
            <table style="min-width: 50%">
                <tr>
                    <th colspan="2">Membre du foyer 3</th>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy3_email">Son email
                            : </label>
                    </th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                               type="email"
                                                                                               id="cofoy3_email"
                                                                                               name="cofoy3_email"
                                                                                               class="email <?php echo( ! empty( $cofoy3_email ) ? 'required' : '' ); ?>"
                                                                                               value="<?php echo esc_attr( $cofoy3_email ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy3_last_name">Son nom : </label></th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy3_last_name"
                                                                                               name="cofoy3_last_name"
                                                                                               class="required_if_not_empty single_name"
                                                                                               data-if-id="cofoy3_email"
                                                                                               value="<?php echo esc_attr( $cofoy3_user_last_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy3_first_name">Son prénom : </label>
                    </th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy3_first_name"
                                                                                               name="cofoy3_first_name"
                                                                                               class="required_if_not_empty single_name"
                                                                                               data-if-id="cofoy3_email"
                                                                                               value="<?php echo esc_attr( $cofoy3_user_firt_name ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: left; width: auto"><label for="cofoy3_tels">Téléphone(s) : </label></th>
                    <td><input <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                               type="text"
                                                                                               id="cofoy3_tels"
                                                                                               name="cofoy3_tels"
                                                                                               value="<?php echo esc_attr( $cofoy3_mobile_phones ) ?>"/>
                    </td>
                </tr>
				<?php if ( $show_cofoyers_address ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"><label for="cofoy3_address">Adresse : </label>
                        </th>
                        <td><textarea <?php disabled( ! $edit_names && ! empty( $cofoy3_email ) ); ?> style="width: 100%"
                                                                                                      rows="4"
                                                                                                      id="cofoy3_address"
                                                                                                      name="cofoy3_address"
                                                                                                      class=""><?php echo esc_textarea( $cofoy3_address ); ?></textarea>
                        </td>
                    </tr>
				<?php } ?>
				<?php if ( $allow_remove_cofoys && ! empty( $cofoy3_email ) ) { ?>
                    <tr>
                        <th style="text-align: left; width: auto"></th>
                        <td>
                            <label for="cofoy3_remove"><input type="checkbox" name="cofoy3_remove"
                                                              id="cofoy3_remove"/> Je ne suis plus coadhérent
                                avec <?php echo esc_html( "$cofoy3_user_firt_name $cofoy3_user_last_name" ) ?>
                            </label>
                        </td>
                    </tr>
				<?php } ?>
            </table>
		<?php } ?>

        <button type="submit" class="btn btn-default">Enregistrer</button>
    </form>
	<?php
	return ob_get_clean();
}