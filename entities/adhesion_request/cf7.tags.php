<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'plugins_loaded', 'amapress_cf7_init', 20 );
function amapress_cf7_init() {
	add_action( 'wpcf7_init', 'amapress_cf7_add_shortcode_amapress_field' );
	add_filter( 'wpcf7_form_tag', 'amapress_cf7_amapress_field_substitute', 10, 2 );
}

function amapress_get_wfcf7_tags() {
	$refl = new ReflectionClass( 'WPCF7_FormTagsManager' );
	try {
		$prop = $refl->getProperty( 'tags' );
	} catch ( Exception $ex ) {
		$prop = $refl->getProperty( 'tag_types' );
	}
	$prop->setAccessible( true );

	return $prop->getValue( WPCF7_FormTagsManager::get_instance() );
}

function amapress_cf7_add_shortcode_amapress_field() {
	wpcf7_add_form_tag(
		array( 'amapress_field', 'amapress_field*' ),
		'amapress_cf7_amapress_field_shortcode_handler', true );
}

function amapress_cf7_amapress_field_shortcode_handler( $tag ) {
	$tags = amapress_get_wfcf7_tags();

	return call_user_func( $tags[ $tag['type'] ]['function'], $tag );
}

function amapress_cf7_amapress_field_substitute( $tag, $replace ) {
	if ( 'amapress_field' == $tag['basetype'] ) {
		$is_required = ( $tag['basetype'] != $tag['type'] );
		switch ( $tag['name'] ) {
			case 'prenom':
			case 'nom':
				$tag['basetype'] = 'text';
				break;
			case 'email':
				$tag['basetype']  = 'email';
				$tag['options'][] = 'akismet:author_email';
				break;
			case 'adresse':
			case 'message':
				$tag['basetype'] = 'textarea';
				break;
			case 'telephone':
				$tag['basetype'] = 'tel';
				break;
			case 'lieux':
				$tag['basetype']  = 'radio';
				$tag['options'][] = 'use_label_element';
				foreach ( Amapress::get_lieux() as $lieu ) {
					if ( ! $lieu->isPrincipal() ) {
						continue;
					}
					$tag['raw_values'][] = $lieu->ID;
					$tag['values'][]     = $lieu->ID;
					$tag['labels'][]     = sprintf( __( '%s (%s)', 'amapress' ), $lieu->getTitle(), $lieu->getFormattedAdresse() );
				}
				break;
			case 'contrats':
				$tag['basetype']  = 'checkbox';
				$tag['options'][] = 'use_label_element';
				$contrat_in_order = AmapressContrats::get_contrats( null, true, true );
				if ( in_array( 'subscribable', $tag['options'] ) ) {
					$contrat_instances = AmapressContrats::get_subscribable_contrat_instances();
				} else {
					$contrat_instances = AmapressContrats::get_active_contrat_instances();
				}
				$subscribable_contrats = [];
				foreach ( $contrat_in_order as $contrat ) {
					foreach ( $contrat_instances as $contrat_instance ) {
						if ( $contrat_instance->getModelId() == $contrat->ID ) {
							$subscribable_contrats[] = $contrat_instance;
						}
					}
				}
				if ( in_array( 'order', $tag['options'] ) ) {
					usort( $subscribable_contrats, function ( $a, $b ) {
						/** @var AmapressContrat_instance $a */
						/** @var AmapressContrat_instance $b */
						if ( $a->isPrincipal() && ! $b->isPrincipal() ) {
							return - 1;
						}
						if ( ! $a->isPrincipal() && $b->isPrincipal() ) {
							return 1;
						}

						return strcmp( $a->getTitle(), $b->getTitle() );
					} );
				}
				foreach ( $subscribable_contrats as $contrat ) {
					$tag['raw_values'][] = $contrat->ID;
					$tag['values'][]     = $contrat->ID;
					$tag['labels'][]     = $contrat->getTitle();
				}
				break;
			case 'intermittent':
				$tag['basetype']     = 'checkbox';
				$tag['options'][]    = 'use_label_element';
				$tag['raw_values'][] = 1;
				$tag['values'][]     = 1;
				$tag['labels'][]     = __( 'Devenir intermittent', 'amapress' );
				break;
			default:
				$tag['basetype'] = 'text';
		}
		$tag['type'] = $tag['basetype'] . ( $is_required ? '*' : '' );
	}

	return $tag;
}

if ( is_admin() ) {
	add_action( 'admin_init', 'amapress_cf7_add_tag_generator_amapress_field', 25 );
}

function amapress_cf7_add_tag_generator_amapress_field() {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'amapress_field', __( 'Champs Amapress', 'contact-form-7' ),
			'amapress_cf7_tag_generator_amapress_field' );
	}
}


function amapress_cf7_tag_generator_amapress_field( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = $args['id'];

	$description = '';
	switch ( $type ) {
		case 'amapress_field':
			$description = __( 'Gère les champs de contact Amapress', 'contact-form-7' );
			//$type = 'text';
			break;
		default:
			//$type = 'text';
			break;
	}

	?>
    <div class="control-box">
        <fieldset>
            <legend><?php echo $description; ?></legend>

            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
                            <label><input type="checkbox"
                                          name="required"/> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label
                                for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label>
                    </th>
                    <td>
                        <input type="hidden" name="name" class="tg-name oneline"
                               id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" value="prenom"/>
                        <select onchange="<?php echo esc_attr( 'javascript:jQuery(\'#' . $args['content'] . '-name\').val(jQuery(this).val()).trigger(\'change\');' ); ?>">
                            <option value="prenom"><?php _e( 'Prénom', 'amapress' ) ?></option>
                            <option value="nom"><?php _e( 'Nom', 'amapress' ) ?></option>
                            <option value="email"><?php _e( 'Email', 'amapress' ) ?></option>
                            <option value="adresse"><?php _e( 'Adresse', 'amapress' ) ?></option>
                            <option value="telephone"><?php _e( 'Téléphone', 'amapress' ) ?></option>
                            <option value="lieux"><?php _e( 'Lieux de distribution', 'amapress' ) ?></option>
                            <option value="contrats"><?php _e( 'Contrats', 'amapress' ) ?></option>
                            <option value="intermittent"><?php _e( 'Intermittent', 'amapress' ) ?></option>
                            <option value="message"><?php _e( 'Message', 'amapress' ) ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label
                                for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label>
                    </th>
                    <td><input type="text" name="id" class="idvalue oneline option"
                               id="<?php echo esc_attr( $args['content'] . '-id' ); ?>"/></td>
                </tr>

                <tr>
                    <th scope="row"><label
                                for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label>
                    </th>
                    <td><input type="text" name="class" class="classvalue oneline option"
                               id="<?php echo esc_attr( $args['content'] . '-class' ); ?>"/></td>
                </tr>

                <tr>
                    <th scope="row"><label
                                for="<?php echo esc_attr( $args['content'] . '-placeholder' ); ?>"><?php echo esc_html( __( 'Placeholder attribute', 'contact-form-7' ) ); ?></label>
                    </th>
                    <td><input type="text" name="placeholder" class="placeholdervalue oneline option"
                               id="<?php echo esc_attr( $args['content'] . '-placeholder' ); ?>"/></td>
                </tr>
                </tbody>
            </table>
        </fieldset>
    </div>

    <div class="insert-box">
        <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()"/>

        <div class="submitbox">
            <input type="button" class="button button-primary insert-tag"
                   value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>"/>
        </div>

        <br class="clear"/>

    </div>
	<?php
}