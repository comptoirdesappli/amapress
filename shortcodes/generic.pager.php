<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function amapress_generic_gallery(
	$objects,
	$render_func = null, $options = [], $context = null
) {
	$options = wp_parse_args(
		$options,
		[
			'size'      => 'thumbnail',
			'if_empty'  => '',
			'searchbox' => false,
		]
	);

	static $amapress_gallery_instance = 0;
	$amapress_gallery_instance ++;

	$output = '';

	$selector        = "iso-gallery-{$amapress_gallery_instance}";
	$selector_search = $selector . '-search';

	if ( Amapress::toBool( $options['searchbox'] ) ) {
		$output .= "<div><label for='$selector_search'>" . __( 'Rechercher: ', 'amapress' ) . "</label><input type='text' data-gallery='$selector' id='$selector_search' class='iso-gallery-search' /></div>";
	}

	$gallery_div = "<div id='$selector' class='iso-gallery'><div class='iso-gallery-sizer'></div>";

	$has_custom_content = ! empty( $render_func );
	if ( count( $objects ) > 0 ) {
		$output .= $gallery_div;
		foreach ( $objects as $o ) {
			$category = apply_filters( "amapress_gallery_category_{$render_func}", '', $o, $context );
			$sort     = esc_attr( apply_filters( "amapress_gallery_sort_{$render_func}", '', $o, $context ) );
			$output   .= "<div class='iso-gallery-item $category' data-sort='$sort'>";
			if ( $has_custom_content ) {
				$output .= apply_filters( "amapress_gallery_render_{$render_func}", $o, $context );
			} else {
				$output .= wp_get_attachment_image( $o->ID, $options['size'] );
			}
			$output .= '</div>';
		}
		$output .= '</div>';
	} else {
		$output .= urldecode( $options['if_empty'] );
	}

	return $output;
}

function amapress_generic_paged_gallery_shortcode( $attr ) {
	amapress_ensure_no_cache();

	global $post;

	static $amapress_gallery_instance = 0;
	$amapress_gallery_instance ++;

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( ! $attr['orderby'] ) {
			unset( $attr['orderby'] );
		}
	}

	$id           = $post ? $post->ID : 0;
	$order        = 'ASC';
	$columns      = 3;
	$rows         = 2;
	$orderby      = 'title';
	$query_uri    = '';
	$query_func   = '';
	$render_func  = '';
	$post_type    = 'post';
	$size         = 'thumbnail';
	$if_empty     = '';
	$post__in     = '';
	$post__not_in = '';
	extract( shortcode_atts( array(
		'order'        => $order,
		'orderby'      => $orderby,
		'id'           => $id,
		'if_empty'     => '',
		'size'         => $size,
		'post__in'     => '',
		'post__not_in' => '',
		'post_type'    => $post_type,
		'query_uri'    => $query_uri,
		'query_func'   => $query_func,
		'render_func'  => $render_func,
		'columns'      => $columns,
		'rows'         => $rows,
	), $attr ), EXTR_OVERWRITE );

	if ( ! empty( $post__in ) ) {
		$post__in = array_map( 'intval', explode( ',', $post__in ) );
	}

	if ( ! empty( $post__not_in ) ) {
		$post__not_in = array_map( 'intval', explode( ',', $post__not_in ) );
	}

	$id = intval( $id );
	if ( 'RAND' == $order ) {
		$orderby = 'none';
	}

	$output = '';

	$selector      = "slick-gallery-{$amapress_gallery_instance}";
	$slick_options = array(
		'slidesPerRow' => $columns,
		'rows'         => $rows,
		'infinite'     => false,
		'responsive'   => array(
			array(
				'breakpoint' => 1024,
				'settings'   => array(
					'slidesPerRow' => 2,
					'rows'         => 2,
				),
			),
			array(
				'breakpoint' => 600,
				'settings'   => array(
					'slidesPerRow' => 1,
					'rows'         => 3,
				),
			),
			array(
				'breakpoint' => 600,
				'settings'   => array(
					'slidesPerRow' => 1,
					'rows'         => 2,
				),
			),
		),
	);
	$slick_options = wp_json_encode( $slick_options );

	$size_class      = sanitize_html_class( $size );
	$gallery_div     = "<div id='$selector' class='slick-gallery slick-galleryid-{$id} slick-gallery-columns-{$columns} slick-gallery-size-{$size_class}' data-slick='{$slick_options}'>";
	$gallery_nav_div = "<div id='{$selector}-pag-nav' class='slick-gallery slick-galleryid-{$id} slick-gallery-size-{$size_class}'>";

	if ( ! empty( $query_func ) ) {
		$gallery = apply_filters( "amapress_gallery_query_{$query_func}", $attr );
	} else if ( ! empty( $query_uri ) ) {
		$query_uri = urldecode( $query_uri );
		if ( $post_type == 'user' ) {
			$gallery = new WP_User_Query();
		} else {
			$gallery = new WP_Query();
		}
		$gallery->parse_query( $query_uri );
	} else {
		$args = array(
			'posts_per_page' => - 1,
			'order'          => $order,
			'orderby'        => $orderby,
			'post_type'      => $post_type,
			'post__in'       => $post__in,
			'post__not_in'   => $post__not_in,
		);
		if ( $post_type == 'user' ) {
			$gallery = new WP_User_Query();
		} else {
			$gallery = new WP_Query();
		}
		$gallery->parse_query( $args );
	}

	if ( ! empty( $post_type ) ) {
		$gallery->set( 'post_type', $post_type );
	}
	if ( ! empty( $order ) ) {
		$gallery->set( 'order', $order );
	}
	if ( ! empty( $orderby ) ) {
		$gallery->set( 'orderby', $orderby );
	}
	if ( ! empty( $post__not_in ) ) {
		$gallery->set( 'post__not_in', $post__not_in );
	}

	$gallery->set( 'posts_per_page', - 1 );
	$gallery->set( 'no_found_rows', true );
	$gallery->get_posts();
	$cols               = $columns;
	$has_custom_content = ! empty( $render_func );
	if ( $gallery->have_posts() ) :
		$output .= $gallery_div;
		while ( $gallery->have_posts() ) : $gallery->the_post();
			$output .= '<div class="slick-gallery-item">';
			if ( $has_custom_content ) {
				$output .= apply_filters( "amapress_gallery_render_{$render_func}", $post );
			} else {
				$output .= wp_get_attachment_image( $post->ID, $size );
			}
			$output .= '</div>';
			$cols --;
		endwhile;
		$output .= '</div>';
		$output .= $gallery_nav_div;
		wp_reset_postdata();
		$output .= '</div>';
	else:
		$output .= urldecode( $if_empty );
	endif;

	return $output;
}
