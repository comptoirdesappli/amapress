<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

class TitanFrameworkOptionUpload extends TitanFrameworkOption {

	private static $firstLoad = true;

	public $defaultSecondarySettings = array(
		'size'            => 'full', // The size of the image to use in the generated CSS
		'placeholder'     => '', // show this when blank
		'media-type'      => 'image',
		'selector-title'  => '',
		'selector-button' => '',
		'multiselect'     => false,
		'show_title'      => false,
		'show_download'   => false,
	);


	/**
	 * Constructor
	 *
	 * @return    void
	 * @since    1.5
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		add_filter( 'tf_generate_css_upload_' . $this->getOptionNamespace(), array( $this, 'generateCSS' ), 10, 2 );

		add_action( 'tf_livepreview_pre_' . $this->getOptionNamespace(), array( $this, 'preLivePreview' ), 10, 3 );
		add_action( 'tf_livepreview_post_' . $this->getOptionNamespace(), array( $this, 'postLivePreview' ), 10, 3 );
	}


	/**
	 * Generates CSS for the font, this is used in TitanFrameworkCSS
	 *
	 * @param    string $css The CSS generated
	 * @param    TitanFrameworkOption $option The current option being processed
	 *
	 * @return    string The CSS generated
	 * @since    1.5
	 */
	public function generateCSS( $css, $option ) {
		if ( $this->settings['id'] != $option->settings['id'] ) {
			return $css;
		}

		$value = $this->getValue();

		if ( empty( $value ) ) {
			return $css;
		}

		if ( is_numeric( $value ) ) {
			$size       = ! empty( $option->settings['size'] ) ? $option->settings['size'] : 'thumbnail';
			$attachment = wp_get_attachment_image_src( $value, $size );
			$value      = $attachment[0];
		}

		$css .= '$' . $option->settings['id'] . ': url(' . $value . ');';

		if ( ! empty( $option->settings['css'] ) ) {
			// In the css parameter, we accept the term `value` as our current value,
			// translate it into the SaSS variable for the current option
			$css .= str_replace( 'value', '#{$' . $option->settings['id'] . '}', $option->settings['css'] );
		}

		return $css;
	}


	/**
	 * The upload option gives out an attachment ID. Live previews will not work since we cannot get
	 * the upload URL from an ID easily. Use a specially created Ajax Handler for just getting the URL.
	 *
	 * @since 1.9
	 *
	 * @see tf_upload_option_customizer_get_value()
	 */
	public function preLivePreview( $optionID, $optionType, $option ) {
		if ( $optionID != $this->settings['id'] ) {
			return;
		}

		$nonce = wp_create_nonce( 'tf_upload_option_nonce' );
		$size  = ! empty( $this->settings['size'] ) ? $this->settings['size'] : 'thumbnail';

		?>
        wp.ajax.send( 'tf_upload_option_customizer_get_value', {
        data: {
        nonce: '<?php echo esc_attr( $nonce ) ?>',
        size: '<?php echo esc_attr( $size ) ?>',
        id: value
        },
        success: function( data ) {
        var $ = jQuery;
        var value = data;
		<?php
	}


	/**
	 * Closes the Javascript code created in preLivePreview()
	 *
	 * @since 1.9
	 *
	 * @see preLivePreview()
	 */
	public function postLivePreview( $optionID, $optionType, $option ) {
		if ( $optionID != $this->settings['id'] ) {
			return;
		}

		// Close the ajax call
		?>
        }
        });
		<?php
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		self::createUploaderScript();

		$this->echoOptionHeader();

		$wnd_title   = empty( $this->settings['selector-title'] ) ? __( 'Select Image', TF_I18NDOMAIN ) : $this->settings['selector-title'];
		$button_text = empty( $this->settings['selector-button'] ) ? __( 'Use image', TF_I18NDOMAIN ) : $this->settings['selector-button'];
		$media_type  = $this->settings['media-type'];
		$multiselect = $this->settings['multiselect'];

		$values = $this->getValue();
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		self::echo_uploader( $this->getID(), $values, $this->settings['placeholder'],
			$wnd_title, $button_text, $media_type, $multiselect, $this->settings['show_title'], $this->settings['show_download'] );

		$this->echoOptionFooter();
	}

	public static function echo_uploader(
		$id, $values, $placeholder,
		$wnd_title = null, $button_text = null, $media_type = null,
		$multiselect = false, $show_attachment_title = false, $show_download_button = false
	) {
		self::createUploaderScript();

		$wnd_title   = empty( $wnd_title ) ? __( 'Select Image', TF_I18NDOMAIN ) : $wnd_title;
		$button_text = empty( $button_text ) ? __( 'Use image', TF_I18NDOMAIN ) : $button_text;

		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		echo '<div class="tf-upload-container">';
		foreach ( $values as $val ) {
			$value = $val;
			echo '<div class="tf-upload-inner" data-selector-title="' . $wnd_title . '" data-selector-button="' . $button_text . '" data-media-type="' . $media_type . '" data-multiselect="' . $multiselect . '">';

			// display the preview image

			if ( is_numeric( $value ) ) {
				// gives us an array with the first element as the src or false on fail
				$value = wp_get_attachment_image_src( $value, array( 150, 150 ) );
				if ( empty( $value ) ) {
					$value = wp_get_attachment_image_src( $val, 'thumbnail', true );
				}
			}

			if ( ! is_array( $value ) ) {
				$value = $val;
			} else {
				$value = $value[0];
			}

			$previewImage = '';
			if ( ! empty( $value ) ) {
				$previewImage = "<i class='dashicons dashicons-no-alt remove'></i><img src='" . esc_url( $value ) . "' style='display: none'/>";
			}
			if ( $show_attachment_title ) {
				$attachment = get_post( $val );
				if ( $attachment ) {
					$previewImage .= '<div class="tf-upload-title">' . $attachment->post_title . '</div>';
				}
			}
			if ( $show_download_button ) {
				$dwn_url = wp_get_attachment_url( $val );
				if ( ! empty( $dwn_url ) ) {
					$previewImage .= '<div class="tf-upload-dl-btn"><a target="_blank" href="' . $dwn_url . '"><span class="dashicons dashicons-welcome-write-blog"></span></a></div>';
				}
			}
			echo "<div class='thumbnail tf-image-preview'>" . $previewImage . '</div>';


			printf( '<input name="%s%s" placeholder="%s" type="hidden" value="%s" />',
				$id,
				$multiselect ? '[]' : '',
				$placeholder,
				esc_attr( $val )
			);

			echo '</div>';
		}
		echo '</div>';
	}

	public function columnDisplayValue( $post_id ) {
		// display the preview image

		$values = $this->getValue( $post_id );
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$ret = '';
		foreach ( $values as $val ) {
			$value = $val;
			if ( is_numeric( $value ) ) {
				//            $size = ! empty( $option->settings['size'] ) ? $option->settings['size'] : 'thumbnail';
				// gives us an array with the first element as the src or false on fail
				$value = wp_get_attachment_image_src( $value, array( 100, 100 ) );
				if ( empty( $value ) ) {
					$value = wp_get_attachment_image_src( $val, 'thumbnail', true );
				}
			}
			if ( ! is_array( $value ) ) {
				$value = $val;
			} else {
				$value = $value[0];
			}

			$previewImage = '';
			if ( ! empty( $value ) ) {
				$previewImage = "<img src='" . esc_url( $value ) . "' style='max-width: 100px' />";
			}
			$ret .= "<div class='thumbnail tf-image-preview'>" . $previewImage . '</div>';
		}
		echo $ret;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionUploadControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->getID(),
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'priority'    => $priority,
		) ) );
	}

	public static function createUploaderScript() {
		if ( ! self::$firstLoad ) {
			return;
		}
		self::$firstLoad = false;

		?>
        <script>
            jQuery(document).ready(function ($) {
                "use strict";

                function tfUploadOptionCenterImage($this) {
                    // console.log('preview image loaded');
                    var _preview = $this.parents('.tf-upload-inner').find('.thumbnail');
                    $this.css({
                        'marginTop': ( _preview.height() - $this.height() ) / 2,
                        'marginLeft': ( _preview.width() - $this.width() ) / 2,
                        'top': 0,
                        'left': 0,
                        'transform': 'translate(0)',
                        '-moz-transform': 'translate(0)',
                        '-ms-transform': 'translate(0)',
                        '-webkit-transform': 'translate(0)'
                    }).show();
                }


                // Calculate display offset of preview image on load
                $('.tf-upload .thumbnail img').load(function () {
                    tfUploadOptionCenterImage($(this));
                }).each(function () {
                    // Sometimes the load event might not trigger due to cache
                    if (this.complete) {
                        $(this).trigger('load');
                    }
                    ;
                });


                // In the theme customizer, the load event above doesn't work because of the accordion,
                // the image's height & width are detected as 0. We bind to the opening of an accordion
                // and adjust the image placement from there.
                var tfUploadAccordionSections = [];
                $('.tf-upload').each(function () {
                    var $accordion = $(this).parents('.control-section.accordion-section');
                    if ($accordion.length > 0) {
                        if ($.inArray($accordion, tfUploadAccordionSections) == -1) {
                            tfUploadAccordionSections.push($accordion);
                        }
                    }
                });
                $.each(tfUploadAccordionSections, function () {
                    var $title = $(this).find('.accordion-section-title:eq(0)'); // just opening the section
                    $title.click(function () {
                        var $accordion = $(this).parents('.control-section.accordion-section');
                        if (!$accordion.is('.open')) {
                            $accordion.find('.tf-upload .thumbnail img').each(function () {
                                var $this = $(this);
                                setTimeout(function () {
                                    tfUploadOptionCenterImage($this);
                                }, 1);
                            });
                        }
                    });
                });


                var onImageDeleteClick = function (event) {
                    event.preventDefault();
                    var $tfUpload = $(this).parents('.tf-upload-container');
                    var $tfUploadInner = $(this).parents('.tf-upload-inner');

                    if ($('.tf-upload-inner', $tfUpload).length > 1) {
                        $tfUploadInner.remove();
                        return;
                    }

                    var _input = $tfUploadInner.find('input');
                    var _preview = $tfUploadInner.find('div.thumbnail');

                    _preview.find('img').remove().end().find('i').remove();
                    _input.val('').trigger('change');

                    return false;
                };
                // remove the image when the remove link is clicked
                $('body').on('click', '.tf-upload i.remove', onImageDeleteClick);
//                $('body').on('click', '.tf-upload .tf-upload-dl-btn', function() {
//                    return false;
//                });


                var onImageClick = function (event) {
                    if ($(event.target).is('.tf-upload-dl-btn') || $(event.target).closest('.tf-upload-dl-btn').length > 0)
                        return true;

                    event.preventDefault();

                    var $tfUpload = $(this).parents('.tf-upload-container');
                    var $tfUploadInner = $(this).parents('.tf-upload-inner');
                    // If we have a smaller image, users can click on the thumbnail
                    if ($(this).is('.thumbnail')) {
                        if ($tfUploadInner.find('img').length != 0) {
                            $tfUploadInner.find('img').trigger('click');
                            return true;
                        }
                    }

                    var o_input = $tfUploadInner.find('input');
                    var o_preview = $tfUploadInner.find('div.thumbnail');
                    var o_remove = $tfUploadInner.find('.tf-upload-image-remove');
                    var is_multiselect = ($tfUploadInner.data('multiselect') == 'true');

                    // uploader frame properties
                    var frame = wp.media({
                        title: $tfUploadInner.data('selector-title'),
                        multiple: is_multiselect,
                        library: {type: $tfUploadInner.data('media-type')},
                        button: {text: $tfUploadInner.data('selector-button')}
                    });

                    frame.on('open', function () {
                        var selection = frame.state().get('selection');
                        var ids = o_input.val().split(',');
                        ids.forEach(function (id) {
                            var attachment = wp.media.attachment(id);
                            attachment.fetch();
                            selection.add(attachment ? [attachment] : []);
                        });
                    });

                    // get the url when done
                    frame.on('select', function () {
                        var selection = frame.state().get('selection');
                        selection.each(function (attachment) {
                            var _input = o_input;
                            var _preview = o_preview;
                            var _remove = o_remove;
                            if (is_multiselect && o_input.val() && o_input.val().length > 0) {
                                var $clonedTfUploadInner = $tfUploadInner.clone().appendTo($tfUpload);
                                $clonedTfUploadInner.on('click', 'i.remove', onImageDeleteClick);
                                $clonedTfUploadInner.on('click', '.thumbnail, img', onImageClick);

                                _input = $clonedTfUploadInner.find('input');
                                _preview = $clonedTfUploadInner.find('div.thumbnail');
                                _remove = $clonedTfUploadInner.find('.tf-upload-image-remove');
                            }

                            if (_input.length > 0) {
                                _input.val(attachment.id);
                            }

                            if (_preview.length > 0) {
                                // remove current preview
                                if (_preview.find('img').length > 0) {
                                    _preview.find('img').remove();
                                }
                                if (_preview.find('i.remove').length > 0) {
                                    _preview.find('i.remove').remove();
                                }

                                // Get the preview image
                                var url = null;
                                if (typeof attachment.attributes.sizes != 'undefined') {
                                    var image = attachment.attributes.sizes.full;
                                    if (typeof attachment.attributes.sizes.thumbnail != 'undefined') {
                                        image = attachment.attributes.sizes.thumbnail;
                                    }
                                    url = image.url;
                                }
                                else {
                                    url = attachment.attributes.icon;
                                }

                                $("<img src='" + url + "'/>").appendTo(_preview);
                                $("<i class='dashicons dashicons-no-alt remove'></i>").prependTo(_preview);
                            }
                            // we need to trigger a change so that WP would detect that we changed the value
                            // or else the save button won't be enabled
                            _input.trigger('change');

                            _remove.show();
                        });
                        frame.off('select');
                    });


                    // open the uploader
                    frame.open();

                    return false;
                };
                // open the upload media lightbox when the upload button is clicked
                $('body').on('click', '.tf-upload .thumbnail, .tf-upload img', onImageClick);
            });
        </script>
		<?php
	}
}

/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionUploadControl', 1 );
function registerTitanFrameworkOptionUploadControl() {
	class TitanFrameworkOptionUploadControl extends WP_Customize_Control {
		public $description;

		public function render_content() {
			TitanFrameworkOptionUpload::createUploaderScript();

			$previewImage = '';
			$value        = $this->value();
			if ( is_numeric( $value ) ) {
				// gives us an array with the first element as the src or false on fail
				$value = wp_get_attachment_image_src( $value, array( 150, 150 ) );
			}
			if ( ! is_array( $value ) ) {
				$value = $this->value();
			} else {
				$value = $value[0];
			}

			if ( ! empty( $value ) ) {
				$previewImage = "<i class='dashicons dashicons-no-alt remove'></i><img src='" . esc_url( $value ) . "' style='display: none'/>";
			}

			?>
            <div class='tf-upload'>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <div class='thumbnail tf-image-preview'><?php echo $previewImage ?></div>
                <input type='hidden' value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?>/>
            </div>
			<?php

			if ( ! empty( $this->description ) ) {
				echo "<p class='description'>{$this->description}</p>";
			}
		}
	}
}


if ( ! function_exists( 'tf_upload_option_customizer_get_value' ) ) {

	add_action( 'wp_ajax_tf_upload_option_customizer_get_value', 'tf_upload_option_customizer_get_value' );

	/**
	 * Returns the image URL from an attachment ID & size
	 *
	 * @see TitanFrameworkOptionUpload->preLivePreview()
	 */
	function tf_upload_option_customizer_get_value() {

		if ( ! empty( $_POST['nonce'] ) && ! empty( $_POST['id'] ) && ! empty( $_POST['size'] ) ) {

			$nonce        = sanitize_text_field( $_POST['nonce'] );
			$attachmentID = sanitize_text_field( $_POST['id'] );
			$size         = sanitize_text_field( $_POST['size'] );

			if ( wp_verify_nonce( $nonce, 'tf_upload_option_nonce' ) ) {
				$attachment = wp_get_attachment_image_src( $attachmentID, $size );
				if ( ! empty( $attachment ) ) {
					wp_send_json_success( $attachment[0] );
				}
			}
		}

		// Instead of doing a wp_send_json_error, send a blank value instead so
		// Javascript adjustments still get executed
		wp_send_json_success( '' );
	}
}
