<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_enqueue_scripts', 'amapress_admin_add_validation_js' );
function amapress_admin_add_validation_js() {
	wp_enqueue_script( 'jquery.validate', plugins_url( '/js/jquery-validate/jquery.validate.min.js', AMAPRESS__PLUGIN_FILE ), array( 'jquery' ) );
	wp_enqueue_script( 'jquery.validate-fr', plugins_url( '/js/jquery-validate/localization/messages_fr.js', AMAPRESS__PLUGIN_FILE ), array( 'jquery.validate' ) );
	wp_enqueue_script( 'jquery.ui.datepicker.validation', plugins_url( '/js/jquery.ui.datepicker.validation.min.js', AMAPRESS__PLUGIN_FILE ), array(
		'jquery.validate',
		'jquery-ui-datepicker'
	) );
}

add_action( 'admin_footer', 'amapress_post_validation' );
function amapress_post_validation() {
	if ( ! is_super_admin() ) {
		if ( isset( $_GET['import_users'] ) ) {
			?>
            <style type="text/css">form#createuser, #create-new-user, #create-new-user ~ p {
                    display: none;
                }

                form#adduser, #add-existing-user, #add-existing-user ~ p {
                    display: block;
                }</style>
			<?php
		} else {
			?>
            <style type="text/css">form#createuser, #create-new-user, #create-new-user ~ p {
                    display: block;
                }

                form#adduser, #add-existing-user, #add-existing-user ~ p {
                    display: none;
                }</style>
			<?php
		}
	}
	?>
    <style type="text/css">
        .amapress-error {
            color: red;
            font-weight: bold;
            display: block;
        }
    </style>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(function ($) {
            jQuery('.postbox:has(.form-table):not(:has(tr))').css('display', 'none');
            var exclusiveGroupCheckFunction = function (value, element) {
                var $checked = jQuery('input:checkbox:checked', jQuery(element).closest('fieldset'));
                var dataExclusives = [];
                $checked.each(function () {
                    dataExclusives.push(jQuery(this).data('excl'));
                });
                return jQuery.unique(dataExclusives).length <= 1;
            };
            jQuery.validator.addMethod("multicheckReq", function (value, element) {
                return jQuery('input:checkbox:checked,input:radio:checked', jQuery(element).closest('fieldset')).length > 0;
            }, "<?php echo esc_js( __( 'Sélectionner au moins un élément', 'amapress' ) ); ?>");
            jQuery.validator.addMethod("exclusiveCheckgroup", exclusiveGroupCheckFunction,
                "<?php echo esc_js( __( 'Sélectionner des élements dans un seul groupe', 'amapress' ) ); ?>");
            jQuery.validator.addMethod("exclusiveContrat", exclusiveGroupCheckFunction,
                "<?php echo esc_js( __( 'Attention, vous avez sélectionné des produits/quantités concernant des contrats différents !', 'amapress' ) ); ?>");
            jQuery.validator.addMethod("tinymcerequired", function (value, element) {
                var content = tinymce.get(element.id).getContent({format: 'text'});
                return jQuery.trim(content) != '';
            }, "<?php echo esc_js( __( 'Doit être rempli', 'amapress' ) ); ?>");
            jQuery.validator.addMethod(
                "docspaceSubfolders",
                function (value, element) {
                    var re = new RegExp(/^(([a-z0-9]+)(\s*,\s*([a-z0-9]+))*)?$/);
                    return this.optional(element) || re.test(value);
                },
                "<?php echo esc_js( __( 'Les sous dossiers doivent avoir la forme xxx,yyy,... et ne doivent être composé que de minuscules ou chiffres', 'amapress' ) ); ?>"
            );
            jQuery.validator.addMethod(
                "repartitionCheck",
                function (value, element) {
                    if (this.optional(element))
                        return true;
                    var re = new RegExp(/^((\d+)(,(\d+))*)?$/);
                    if (!re.test(value))
                        return false;
                    var arr = value.split(',');
                    if (arr.length != parseInt(jQuery(element).data('num')))
                        return false;
                    var sum = 0;
                    arr.forEach(function (v) {
                        sum += parseInt(v);
                    });
                    return 100 == sum;
                },
                "<?php echo esc_js( __( 'Doit être une liste de pourcentages entiers, avoir le nombre de valeurs du nombre de règlements et leur somme doit être égale à 100', 'amapress' ) ); ?>"
            );
            jQuery.validator.addMethod(
                "repartitionDatesCheck",
                function (value, element) {
                    if (this.optional(element))
                        return true;
                    var selected = jQuery(':selected', element).length;
                    var needed = parseInt(jQuery(element).data('max'));
                    return 0 == selected || needed == selected;
                },
                "<?php echo esc_js( __( 'Le nombre de dates sélectionnées doit être égale au nombre de paiement ou aucune date', 'amapress' ) ); ?>"
            );
            jQuery.validator.addMethod('positiveNumber',
                function (value) {
                    return Number(value) > 0;
                }, '<?php echo esc_js( __( 'Doit être supérieur à 0', 'amapress' ) ); ?>');
            jQuery.validator.setDefaults({
                ignore: ''
            });
            var createBtn = jQuery("form#createuser #createusersub");
            createBtn.hide().after("<input type=\'button\' value=\'<?php echo esc_js( __( 'Ajouter un utilisateur', 'amapress' ) ); ?>\' id=\'amapress_add_user\' class=\'amapress_add_user button-primary\' />");
            jQuery("#amapress_add_user").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                } catch (ee) {
                }
                createBtn.prop('disabled', false);
                if (jQuery('form#createuser').valid()) {
                    createBtn.click();
                } else {
                    alert('<?php echo esc_js( __( 'Certains champs ne sont pas valides', 'amapress' ) ); ?>');
                }
            });

            var updateBtn = jQuery("form#your-profile #submit");
            updateBtn.hide().after("<input type=\'button\' value=\'<?php echo esc_js( __( 'Mettre à jour', 'amapress' ) ); ?>\' id=\'amapress_update_user\' class=\'amapress_update_user button-primary\' />");
            jQuery("#amapress_update_user, #wp-admin-bar-amapress_update_user_admin_bar button.amapress_update_user").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                } catch (ee) {
                }
                updateBtn.prop('disabled', false);
                if (jQuery('form#your-profile').valid()) {
                    updateBtn.click();
                } else {
                    alert('<?php echo esc_js( __( 'Certains champs ne sont pas valides', 'amapress' ) ); ?>');
                }
            });

            var publishBtn = jQuery("form#post #publish");
            publishBtn.hide().after("<input type=\'button\' value=\'<?php echo esc_js( __( 'Enregistrer', 'amapress' ) ); ?>\' id=\'amapress_publish\' class=\'amapress_publish button-primary\' />");
            jQuery("#amapress_publish, #wp-admin-bar-amapress_publish_admin_bar button.amapress_publish").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                } catch (ee) {
                }
                publishBtn.prop('disabled', false);
                if (jQuery('form#post').valid()) {
                    publishBtn.click();
                } else {
                    alert('<?php echo esc_js( __( 'Certains champs ne sont pas valides', 'amapress' ) ); ?>');
                }
            }).css('display', publishBtn.length > 0 ? 'block' : 'none');

            jQuery.expr[':'].parentHidden = function (a) {
                return jQuery(a).parent().is(':hidden');
            };
            jQuery.validator.addClassRules('emailDoesNotExists', {
                remote: function (element) {
                    return {
                        "url": "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
                        "type": "post",
                        "data": {
                            "action": "check_email_exists",
                            "email": function () {
                                return $(element).val();
                            },
                        }
                    }
                }
            });
            jQuery.validator.addClassRules('onlyOneInscription', {
                remote: function (element) {
                    return {
                        "url": "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
                        "type": "post",
                        "data": {
                            "action": "check_inscription_unique",
                            "contrats": function () {
                                var contrats = [];
                                jQuery('input.contrat-quantite:checked').each(function () {
                                    contrats.push(jQuery(this).data('excl'));
                                });
                                return contrats.join(',');
                            },
                            "user": function () {
                                return jQuery('#amapress_adhesion_adherent').val();
                            },
                            "related": function () {
                                return jQuery('#amapress_adhesion_related').val();
                            },
                            "post_ID": function () {
                                return jQuery('#post_ID').val();
                            }
                        }
                    }
                }
            });
            var amapress_validator = jQuery('form#post, form#createuser, .titan-framework-panel-wrap form, form form').validate({
                ignore: ":parentHidden",
                onkeyup: false,
                errorPlacement: function (error, element) {
                    error.addClass('amapress-error');
                    if (element.hasClass("exclusiveCheckgroup") || element.hasClass("exclusiveContrat")) {
                        error.insertBefore(element.closest("fieldset"));
                    }
                    else if (element.hasClass("multicheckReq")) {
                        error.insertAfter(element.closest("fieldset"));
                    }
                    else
                        error.insertAfter(element);
                },
                showErrors: function (errorMap, errorList) {
//                    console.log(errorMap);
//                    console.log(errorList);
                    this.defaultShowErrors();
                },
                rules: {
                    "multicheckReq": {
                        "multicheckReq": true,
                    },
                    "exclusiveContrat": {
                        "exclusiveContrat": true,
                    },
                },
            });
        });
        //]]>
    </script>';
	<?php
}

add_action( 'wp_print_footer_scripts', function () {
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
            }, "<?php echo esc_js( __( 'Champ requis', 'amapress' ) ); ?>");

            jQuery.validator.addMethod(
                "mobilePhoneCheck",
                function (value, element) {
                    var re = new RegExp(/^(\+33\s?[67]|0[67])\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/);
                    return this.optional(element) || null == value || 0 === value.length || re.test(value);
                },
                "<?php echo esc_js( __( 'Numéro de téléphone mobile invalide', 'amapress' ) ); ?>"
            );
            jQuery.validator.addMethod(
                "fixPhoneCheck",
                function (value, element) {
                    var re = new RegExp(/^(\+33\s?[123459]|0[123459])\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/);
                    return this.optional(element) || null == value || 0 === value.length || re.test(value);
                },
                "<?php echo esc_js( __( 'Numéro de téléphone fixe invalide', 'amapress' ) ); ?>"
            );
        });
        //]]>
    </script>
	<?php
} );
