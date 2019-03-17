<?php

function amapress_gdrive_shortcode( $atts, $content = null ) {
	$a = shortcode_atts( array(
		'id'     => '',
		'style'  => 'list', //grid
		'width'  => '100%',
		'height' => '500px',
	), $atts );

	return '<iframe src="https://drive.google.com/embeddedfolderview?id=' . $a['id'] . '#' . $a['style'] . '" frameborder="0" width="' . $a['width'] . '" height="' . $a['height'] . '" scrolling="auto"> </iframe>';
}

amapress_register_shortcode( 'google-drive', 'amapress_gdrive_shortcode',
	[
		'desc' => 'Configure et affiche un google drive',
		'args' => [
		]
	] );