<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_button_no_esc( $title, $href, $icon = null, $blank = false, $confirm_message = null, $other_class = 'amap-button-default' ) {
	return '<a role="button" href="' . $href . '" class="btn btn-default amap-button ' . $other_class . '"' .
	       ( $blank === true ? ' target="_blank"' : '' ) .
	       ( $confirm_message ? 'onclick="return confirm(\'' . esc_js( $confirm_message ) . '\')"' : '' ) . '>' . ( ! empty( $icon ) ? amapress_get_font_icon( $icon ) . ' ' : '' ) . esc_html( $title ) . '</a>';
}

function amapress_get_button( $title, $href, $icon = null, $blank = false, $confirm_message = null, $other_class = 'amap-button-default' ) {
	return amapress_get_button_no_esc( esc_html( $title ), $href, $icon, $blank, $confirm_message, $other_class );
}

function amapress_echo_button( $title, $href, $icon = null, $blank = false, $confirm_message = null, $other_class = 'amap-button-default' ) {
	echo amapress_get_button( $title, $href, $icon, $blank, $confirm_message, $other_class );
}

function amapress_echo_panel_start( $title, $icon = null, $other_class = 'amap-panel-default' ) {
	amapress_echo_panel_start_no_esc( esc_html( $title ), $icon, $other_class );
}

function amapress_echo_panel_start_no_esc( $title, $icon = null, $other_class = 'amap-panel-default' ) {
	echo amapress_get_panel_start_no_esc( $title, $icon, $other_class );
}

function amapress_echo_panel_end() {
//    echo '<br class="clearfix" />';
	echo amapress_get_panel_end();
}

function amapress_get_panel_start_no_esc( $title, $icon = null, $other_class = 'amap-panel-default' ) {
	$ret = '<div class="amap-panel ' . $other_class . '">';
	if ( ! empty( $title ) ) {
		$ret .= '<div class="amap-panel-heading">
            <h3 class="amap-panel-title">' . ( ! empty( $icon ) ? amapress_get_font_icon( $icon ) . ' ' : '' ) . $title . '</h3>
          </div>';
	}

	return $ret . '<div class="amap-panel-body">';
}

function amapress_get_panel_start( $title, $icon = null, $other_class = 'amap-panel-default' ) {
	return amapress_get_panel_start_no_esc( esc_html( $title ), $icon, $other_class );
}

function amapress_get_panel_end() {
//    echo '<br class="clearfix" />';
	return '</div>
          </div>';
}

function amapress_echo_datatable( $id, $columns, $data, $options = array(), $exports = array() ) {
	echo amapress_get_datatable( $id, $columns, $data, $options, $exports );
}

function amapress_get_datatable( $id, $columns, $data, $options = array(), $exports = array() ) {
//    if (!$options) $options = array();
	$options = wp_parse_args(
		$options,
		array(
			'columns'      => $columns,
			'responsive'   => true,
			'paging'       => false,
			'initComplete' => null,
			'info'         => false,
			'data'         => $data,
			'table-layout' => 'auto',
			'language'     => array( 'url' => '//cdn.datatables.net/plug-ins/1.10.11/i18n/French.json' ),
		) );

	$initComplete = $options['initComplete'];
	unset( $options['initComplete'] );
	$init = '';
	if ( ! empty( $initComplete ) ) {
		$init = ".on('init.dt', $initComplete)";
	}

	$style = "table-layout:{$options['table-layout']}";

	unset( $options['table-layout'] );
	if ( ! empty( $exports ) ) {
		$options['buttons'] = array_values( $exports );
		$options['dom']     = 'Bfrtip';
	}

	$ret = '';
//    $ret  = "<div class='table-responsive'>"; class='display responsive nowrap'
	$ret .= "<table id='$id' class='display nowrap' style='$style' width='100%' cellspacing='0'></table>";
//    $ret .= "</div>\n";
	$ret .= "<script type='text/javascript'>\n";
	$ret .= "    //<![CDATA[\n";
	$ret .= "    jQuery(document).ready(function ($) {\n";
	$ret .= "        $('#$id')$init.dataTable(" . json_encode( $options ) . ")\n";
	$ret .= "    });\n";
	$ret .= "    //]]>\n";
	$ret .= "</script>";

	return $ret;
}

function amapress_get_radio( $name, $value, $checked, $title, $id = null ) {
	static $cnt = 1;

	if ( empty( $id ) ) {
		$id = 'rad-' . $cnt ++;
	}

	return '<div class="radio radio-default">
                <input class="radio" type="radio" name="' . $name . '" id="' . $id . '" value="' . esc_attr( $value ) . '" ' . checked( $checked, $value, false ) . '/>
                <label for="' . $id . '">' . esc_html( $title ) . '</label>
            </div>';
}

function amapress_get_html_h1( $content, $class = null, $style = null, $escape_content = true ) {
	return amapress_get_html_tag( 'h1', $content, $class, $style, $escape_content );
}

function amapress_get_html_h2( $content, $class = null, $style = null, $escape_content = true ) {
	return amapress_get_html_tag( 'h2', $content, $class, $style, $escape_content );
}

function amapress_get_html_h3( $content, $class = null, $style = null, $escape_content = true ) {
	return amapress_get_html_tag( 'h3', $content, $class, $style, $escape_content );
}

function amapress_get_html_h4( $content, $class = null, $style = null, $escape_content = true ) {
	return amapress_get_html_tag( 'h4', $content, $class, $style, $escape_content );
}

function amapress_get_html_p( $content, $class = null, $style = null, $escape_content = true ) {
	return amapress_get_html_tag( 'p', $content, $class, $style, $escape_content );
}

function amapress_get_html_div( $content, $class = null, $style = null, $escape_content = true ) {
	return amapress_get_html_tag( 'div', $content, $class, $style, $escape_content );
}

function amapress_get_html_img( $src, $alt, $class = null, $style = null, $escape_content = true ) {
	return amapress_get_html_tag( 'img', null, $class, $style, $escape_content, true,
		array( 'src' => $src, 'alt' => $alt ) );
}

function amapress_get_html_a( $href, $content, $class = null, $style = null, $title = null, $escape_content = true ) {
	return amapress_get_html_tag( 'img', $content, $class, $style, $escape_content, true,
		array( 'href' => $href, 'title' => $title ) );
}

function amapress_get_html_tag( $tag, $content, $class = null, $style = null, $escape_content = true, $allow_autoclosed = false, $other_atts = null ) {
	if ( $other_atts == null ) {
		$other_atts = array();
	}
	if ( ! empty( $style ) ) {
		$other_atts['style'] = $style;
	}
	if ( ! empty( $class ) ) {
		$other_atts['class'] = $class;
	}
	if ( count( $other_atts ) > 0 ) {
		$other_atts = ' ' . implode( ' ', array_map( function ( $k, $v ) {
				if ( $v == null ) {
					return '';
				}

				return $k . '="' . esc_attr( $v ) . '"';
			}, array_keys( $other_atts ), $other_atts ) );
	}
	if ( empty( $content ) && $allow_autoclosed ) {
		return "<$tag $other_atts/>";
	} else {
		return "<$tag $other_atts>" . ( ! empty( $content ) && $escape_content ? esc_html( $content ) : $content ) . "</$tag>";
	}
}

function amapress_get_html_start_tag( $tag, $class = null, $style = null, $other_atts = null ) {
	if ( $other_atts == null ) {
		$other_atts = array();
	}
	if ( ! empty( $style ) ) {
		$other_atts['style'] = $style;
	}
	if ( ! empty( $class ) ) {
		$other_atts['class'] = $class;
	}
	if ( $other_atts ) {
		$other_atts = ' ' . implode( ' ', array_map( function ( $k, $v ) {
				if ( $v == null ) {
					return '';
				}

				return $k . '="' . esc_attr( $v ) . '"';
			}, array_keys( $other_atts ), $other_atts ) );
	}

	return "<$tag $other_atts>";
}

function amapress_get_html_end_tag( $tag ) {
	return "</$tag>";
}

