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

function amapress_get_panel_start_no_esc( $title, $icon = null, $other_class = 'amap-panel-default', $id = null ) {
	$ret = '<div class="amap-panel ' . $other_class . '" ' . ( ! empty( $id ) ? 'id="' . esc_attr( $id ) . '"' : '' ) . '>';
	if ( ! empty( $title ) ) {
		$ret .= '<div class="amap-panel-heading">
            <h3 class="amap-panel-title">' . ( ! empty( $icon ) ? amapress_get_font_icon( $icon ) . ' ' : '' ) . $title . '</h3>
          </div>';
	}

	return $ret . '<div class="amap-panel-body">';
}

function amapress_get_panel_start( $title, $icon = null, $other_class = 'amap-panel-default', $id = null ) {
	return amapress_get_panel_start_no_esc( esc_html( $title ), $icon, $other_class, $id );
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
			'columns'        => $columns,
			'responsive'     => true,
			'paging'         => false,
			'initComplete'   => null,
			'info'           => false,
			'empty_desc'     => __( 'Aucune données dans le tableau', 'amapress' ),
			'nowrap'         => true,
			'data'           => $data,
			'cell-border'    => false,
			'init_as_html'   => false,
			'no_script'      => false,
			'table-layout'   => 'auto',
			'language'       => array( 'url' => '//cdn.datatables.net/plug-ins/1.10.11/i18n/French.json' ),
			'raw_js_options' => '',
		) );

	if ( 'auto' === $options['responsive'] ) {
		$options['responsive'] = wp_is_mobile();
	}

	$init_as_html = $options['init_as_html'];
	if ( $init_as_html ) {
		unset( $options['columns'] );
		unset( $options['data'] );
	}
	unset( $options['init_as_html'] );

	$initComplete = $options['initComplete'];
	unset( $options['initComplete'] );
	$init = '';
	if ( ! empty( $initComplete ) ) {
		$init = ".on('init.dt', $initComplete)";
	}

	$raw_js_options = $options['raw_js_options'];
	unset( $options['raw_js_options'] );

	$nowrap      = $options['nowrap'] ? 'nowrap' : '';
	$cellborder  = $options['cell-border'] ? 'cell-border' : '';
	$table_style = "table-layout:{$options['table-layout']}";

	if ( ! empty( $options['empty_desc'] ) ) {
		if ( empty( $options["language"] ) ) {
			$options["language"] = [];
		}
		$options["language"] = array_merge( $options["language"], array( 'emptyTable' => $options['empty_desc'] ) );
	}

	unset( $options['table-layout'] );
	if ( ! empty( $exports ) ) {
		$options['buttons'] = array_values( $exports );
		$options['dom']     = 'Bfrtip';
	}

	$table_content = '';
	if ( $init_as_html ) {
		$table_content .= '<thead><tr>';
		foreach ( $columns as $col ) {
			$title = isset( $col['title'] ) ? $col['title'] : '&#xA0;';
			$attr  = '';
			if ( ! empty( $col['responsivePriority'] ) ) {
				$attr = ' data-priority="' . $col['responsivePriority'] . '"';
			}
			if ( ! empty( $col['width'] ) ) {
				$attr .= ' width="' . $col['width'] . '"';
			}
			if ( ! empty( $col['className'] ) ) {
				$attr .= ' class="' . $col['className'] . '"';
			}
			$table_content .= "<th$attr>" . $title . '</th>';
		}
		$table_content .= '</tr></thead>';

		$table_content .= '<tbody>';
		$row           = 0;
		foreach ( $data as $d ) {
			$table_content .= '<tr class="' . ( $row ++ % 2 == 0 ? 'even' : 'odd' ) . '">';
			foreach ( $columns as $col ) {
				$data_k      = is_array( $col['data'] ) ? $col['data']['_'] : $col['data'];
				$data_sort_k = is_array( $col['data'] ) ? $col['data']['sort'] : $col['data'];

				$data_v      = isset( $d[ $data_k ] ) ? $d[ $data_k ] : '';
				$data_sort_v = isset( $d[ $data_sort_k ] ) ? $d[ $data_sort_k ] : '';

				$style = '';
				if ( is_array( $data_v ) ) {
					$style  = ! empty( $data_v['style'] ) ? $data_v['style'] : '';
					$data_v = isset( $data_v['value'] ) ? $data_v['value'] : '';
				}
				if ( is_array( $data_sort_v ) ) {
					$data_sort_v = isset( $data_sort_v['value'] ) ? $data_sort_v['value'] : '';
				}

				$attr = '';
				if ( ! empty( $col['width'] ) ) {
					$attr .= ' width="' . $col['width'] . '"';
				}
				if ( ! empty( $col['className'] ) ) {
					$attr .= ' class="' . $col['className'] . '"';
				}
				if ( $data_v != $data_sort_v ) {
					$table_content .= '<td' . $attr . ' style="' . $style . '" data-sort="' . esc_attr( $data_sort_v ) . '">' . $data_v . '</td>';
				} else {
					$table_content .= '<td' . $attr . ' style="' . $style . '">' . $data_v . '</td>';
				}
			}
			$table_content .= '</tr>';
		}
//		if ( empty( $data ) ) {
//			$table_content .= '<tr><td colspan="' . count( $columns ) . '">' . esc_html( $options['empty_desc'] ) . '</td></tr>';
//		}
		$table_content .= '</tbody>';
	}
	unset( $options['empty_desc'] );

	$ret = '';
//    $ret  = "<div class='table-responsive'>"; class='display responsive nowrap'
	$ret .= "<table id='$id' class='display $nowrap $cellborder' style='margin:0;$table_style' width='100%' cellspacing='0'>$table_content</table>";
//    $ret .= "</div>\n";
	if ( ! $options['no_script'] ) {
		$json = json_encode( $options );
		if ( ! empty( $raw_js_options ) ) {
			$json = substr( $json, 0, strlen( $json ) - 1 );
			$json .= ',' . $raw_js_options . '}';
		}
		$ret .= "<script type='text/javascript'>\n";
		$ret .= "    //<![CDATA[\n";
		$ret .= "    jQuery(document).ready(function ($) {\n";
		$ret .= "        $('#$id')$init.DataTable(" . $json . ")\n";
		$ret .= "    });\n";
		$ret .= "    //]]>\n";
		$ret .= '</script>';
	}

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

function amapress_print_qrcode( $text, $size = 4, $level = 'Q' ) {
	return '<img alt="' . esc_attr( $text ) . '" src="' . esc_attr( admin_url( 'admin-post.php?action=qrcode&text=' . urlencode( $text ) . '&level=' . $level . '&size=' . $size ) ) . '" />';
}

add_action( 'admin_post_qrcode', function () {
	include AMAPRESS__PLUGIN_DIR . 'utils/phpqrcode.php';

	$text  = isset( $_REQUEST['text'] ) ? $_REQUEST['text'] : '';
	$size  = intval( isset( $_REQUEST['size'] ) ? $_REQUEST['size'] : '4' );
	$level = isset( $_REQUEST['level'] ) ? $_REQUEST['level'] : 'Q';
	switch ( $level ) {
		case 'L':
			$level = QR_ECLEVEL_L;
			break;
		case 'M':
			$level = QR_ECLEVEL_M;
			break;
		case 'Q':
			$level = QR_ECLEVEL_Q;
			break;
		case 'H':
			$level = QR_ECLEVEL_H;
			break;
	}

	QRcode::png( $text, false, $level, $size );
} );

