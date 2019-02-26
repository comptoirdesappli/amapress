<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'file_is_displayable_image' ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	require_once ABSPATH . '/wp-admin/includes/image.php';
}

//add_filter('amapress_get_user_infos_content_commandes', 'amapress_get_user_infos_content_commandes', 10, 2);
//function amapress_get_user_infos_content_commandes($content, $subview) {
//
//}

//add_filter('amapress_get_user_infos_title_default', 'amapress_get_user_infos_title_default');
//function amapress_get_user_infos_title_default($content)
//{
//    return 'Mon profile';
//}

//add_filter('amapress_get_user_infos_content_default', 'amapress_get_user_infos_content_default');
function amapress_edit_user_info_shortcode( $atts ) {
	if ( is_admin() || ! amapress_is_user_logged_in() ) {
		return '';
	}

	$atts       = shortcode_atts(
		[
			'edit_names' => 'true'
		], $atts
	);
	$edit_names = Amapress::toBool( $atts['edit_names'] );

	ob_start();

//    do_action('amapress_process_user_profile');
	?>
	<?php
	/**
	 * Get's the user info
	 * Returned in an object
	 * http://codex.wordpress.org/Function_Reference/get_userdata
	 */
	$user_id   = amapress_current_user_id();
	$user_info = get_userdata( $user_id );
	$user      = AmapressUser::getBy( $user_id );
	?>

    <form role="form" action="" id="user_profile" method="POST" enctype="multipart/form-data">
		<?php wp_nonce_field( 'user_profile_nonce', 'user_profile_nonce_field' ); ?>
        <div class="form-group">
            <label for="first_name">Prénom</label>
            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Prénom"
                   value="<?php echo $user_info->first_name; ?>" <?php disabled( ! $edit_names ) ?>>
        </div>
        <div class="form-group">
            <label for="last_name">Nom</label>
            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Nom"
                   value="<?php echo $user_info->last_name; ?>" <?php disabled( ! $edit_names ) ?>>
        </div>
        <div class="form-group">
            <label for="last_name">Nom d'affichage</label>
            <input type="text" class="form-control" id="display_name" name="display_name"
                   placeholder="Nom d'affichage"
                   value="<?php echo $user_info->display_name; ?>" <?php disabled( ! $edit_names ) ?>>
        </div>
        <div class="form-group">
            <label for="amapress_user_telephone"><?php _e( 'Téléphone', 'amapress' ) ?></label>
            <input class="form-control" type="text" name="amapress_user_telephone" id="amapress_user_telephone"
                   class="input"
                   value="<?php echo esc_attr( wp_unslash( get_user_meta( $user_info->ID, 'amapress_user_telephone', true ) ) ); ?>"
                   size="10"/>
        </div>
        <div class="form-group">
            <label for="amapress_user_telephone2"><?php _e( 'Téléphone 2', 'amapress' ) ?></label>
            <input class="form-control" type="text" name="amapress_user_telephone2" id="amapress_user_telephone2"
                   value="<?php echo esc_attr( wp_unslash( get_user_meta( $user_info->ID, 'amapress_user_telephone2', true ) ) ); ?>"
                   size="10"/>
        </div>
        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Email"
                   value="<?php echo $user_info->user_email; ?>">
        </div>
        <div class="form-group">
            <label for="email">Adresse email 2</label>
            <input type="email" class="form-control" id="email2" name="email2" placeholder="Email 2"
                   value="<?php echo esc_attr( wp_unslash( get_user_meta( $user_info->ID, 'email2', true ) ) ); ?>">
        </div>
        <div class="form-group">
            <label for="email">Adresse email 3</label>
            <input type="email" class="form-control" id="email3" name="email3" placeholder="Email 3"
                   value="<?php echo esc_attr( wp_unslash( get_user_meta( $user_info->ID, 'email3', true ) ) ); ?>">
        </div>
        <div class="form-group">
            <label for="email">Adresse email 3</label>
            <input type="email" class="form-control" id="email4" name="email4" placeholder="Email 4"
                   value="<?php echo esc_attr( wp_unslash( get_user_meta( $user_info->ID, 'email4', true ) ) ); ?>">
        </div>
        <div class="form-group">
            <label for="amapress_user_adresse">
				<?php _e( 'Adresse', 'amapress' ) ?><br/>
            </label>
            <textarea class="form-control" name="amapress_user_adresse" id="amapress_user_adresse" rows="4"
                      cols="40"
                      placeholder="Adresse"><?php echo get_user_meta( $user_info->ID, 'amapress_user_adresse', true ); ?></textarea>
        </div>
        <div class="form-group">
            <label for="amapress_user_code_postal">
				<?php _e( 'Code postal', 'amapress' ) ?><br/>
            </label>
            <input class="form-control" type="text" name="amapress_user_code_postal" id="amapress_user_code_postal"
                   value="<?php echo esc_attr( wp_unslash( get_user_meta( $user_info->ID, 'amapress_user_code_postal', true ) ) ); ?>"
                   size="5"/>
        </div>
        <div class="form-group">
            <label for="amapress_user_ville">
				<?php _e( 'Ville', 'amapress' ) ?><br/>
            </label>
            <input class="form-control" type="text" name="amapress_user_ville" id="amapress_user_ville"
                   value="<?php echo esc_attr( wp_unslash( get_user_meta( $user_info->ID, 'amapress_user_ville', true ) ) ); ?>"/>
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
            <label for="amapress_user_avatar-upload">Mettre à jour ma photo : <input class="form-control"
                                                                                     type="file" accept="image/*"
                                                                                     name="amapress_user_avatar-upload"
                                                                                     id="amapress_user_avatar-upload"/></label>

			<?php
			/*Retrieving the image*/
			//            $attachment = get_user_meta($user_info->ID, 'amapress_user_avatar', true);
			//            if ($attachment) {
			echo get_avatar( $user_info->ID );
			//            }
			?>
            <div>
                <label for="amapress_user_avatar-delete"><input type="checkbox"
                                                                name="amapress_user_avatar-delete"
                                                                id="amapress_user_avatar-delete" value="1"/>Supprimer ma
                    photo</label>
            </div>
        </div>
        <button type="submit" class="btn btn-default">Enregistrer</button>
    </form>
	<?php

	$content = ob_get_clean();

	return $content;
}