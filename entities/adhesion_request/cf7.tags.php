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
//    return '<strong style="color:red">Invalid USE</strong>';
}

//    $tag = new WPCF7_FormTag( $tag );
//
//    if ( empty( $tag->name ) )
//        return '';
//
//    WPCF7_FormTagsManager::get_instance()->scan()
//}

//add_filter( 'wpcf7_validate_text', 'wpcf7_text_validation_filter', 10, 2 );  // in init
function amapress_cf7_amapress_field_substitute( $tag, $replace ) {
//    $tag = new WPCF7_FormTag( $tag );
//    var_dump($tag);
	if ( 'amapress_field' == $tag['basetype'] ) {
		$is_required = ( $tag['basetype'] != $tag['type'] );
		switch ( $tag['name'] ) {
			case 'prenom':
				$tag['basetype'] = 'text';
				break;
			case 'nom':
				$tag['basetype'] = 'text';
				break;
			case 'email':
				$tag['basetype']  = 'email';
				$tag['options'][] = 'akismet:author_email';
				break;
			case 'message':
				$tag['basetype'] = 'textarea';
				break;
			case 'adresse':
				$tag['basetype'] = 'text';
				break;
//            case 'code_postal':
//                $tag['basetype'] = 'text';
//            case 'ville':
			case 'telephone':
				$tag['basetype'] = 'tel';
				break;
			case 'lieux':
				$tag['basetype'] = 'radio';
				foreach ( Amapress::get_lieux() as $lieu ) {
					$tag['raw_values'][] = $lieu->ID;
					$tag['values'][]     = $lieu->ID;
					$tag['labels'][]     = sprintf( '%s (%s)', $lieu->getTitle(), $lieu->getFormattedAdresse() );
				}
				break;
			case 'contrats':
				$tag['basetype'] = 'checkbox';
				foreach ( AmapressContrats::get_subscribable_contrat_instances() as $contrat ) {
					$tag['raw_values'][] = $contrat->ID;
					$tag['values'][]     = $contrat->ID;
					$tag['labels'][]     = $contrat->getTitle();
				}
				break;
			default:
				$tag['basetype'] = 'text';
		}
		$tag['type'] = $tag['basetype'] . ( $is_required ? '*' : '' );
	}

//    var_dump($tag);
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
			$description = __( "Gère les champs de contact Amapress", 'contact-form-7' );
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
                            <option value="prenom">Prénom</option>
                            <option value="nom">Nom</option>
                            <option value="email">Email</option>
                            <option value="adresse">Adresse</option>
                            <option value="telephone">Téléphone</option>
                            <option value="lieux">Lieux de distribution</option>
                            <option value="contrats">Contrats</option>
                            <option value="message">Message</option>
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