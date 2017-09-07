<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionActionButtons extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'buttons' => array(),
		'column'  => false,
	);

	public function columnDisplayValue( $post_id ) {
		$this->echoButtons( $post_id );
	}

	public function columnExportValue( $post_id ) {
	}

	public function display() {
		$this->echoOptionHeader();
//		printf('<span id="%s" class="readonly-text" >%s</span> %s',
//			$this->getID(),
//			$this->getValue(),
//			$this->settings['unit']
//		);
		$this->echoButtons();
		$this->echoOptionFooter();
	}

	private function echoButtons( $postID = null ) {
		$post_type = null;
		if ( $this->type == self::TYPE_META ) {
			$postID = $this->getPostID( $postID );
			if ( $this->owner->isUser === true ) {
				$post_type = 'user';
			} else {
				$post_type = get_post_type( $postID );
			}
		}
		$option_id = $this->getID();

		foreach ( $this->settings['buttons'] as $button ) {

			$button = wp_parse_args( $button,
				array(
					'class'        => 'button button-secondary',
					'href'         => '#',
					'text'         => '',
					'text_is_html' => false,
					'type'         => 'button',
					'capability'   => '',
				) );
			if ( ! empty( $button['capability'] ) && ! current_user_can( $button['capability'] ) ) {
				continue;
			}

			$text = $button['text'];
			if ( is_callable( $text, false ) ) {
				$text = call_user_func( $text, $postID );
			}
			$href = $button['href'];
			if ( is_callable( $href, false ) ) {
				$href = call_user_func( $href, $postID );
			}

			$text = str_replace( '%%id%%', $postID, $text );
			$href = str_replace( '%%id%%', $postID, $href );

			$text = apply_filters( "tf_replace_placeholders_{$post_type}", $text, $postID );
			$text = apply_filters( "tf_replace_placeholders_{$option_id}", $text, $postID );
			$href = apply_filters( "tf_replace_placeholders_{$post_type}", $href, $postID );
			$href = apply_filters( "tf_replace_placeholders_{$option_id}", $href, $postID );

			if ( 'link' == $button['type'] ) {
				printf( '<a class="%s" href="%s">%s</a>',
					esc_attr( $button['class'] ),
					esc_attr( $href ),
					$button['text_is_html'] ? $text : esc_html( $text ) );
			} else {
				printf( '<button type="button" class="%s" onclick="location.href=\'%s\'">%s</button>',
					esc_attr( $button['class'] ),
					esc_url( $href ),
					$button['text_is_html'] ? $text : esc_html( $text ) );
			}
		}
	}
}
