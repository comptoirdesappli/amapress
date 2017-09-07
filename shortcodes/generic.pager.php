<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function amapress_generic_gallery( $objects, $id = '0', $render_func = null, $if_empty = '', $columns = 5, $size = 'thumbnail' ) {
	static $amapress_gallery_instance = 0;
	$amapress_gallery_instance ++;

	$output = '';

	$selector = "slick-gallery-{$amapress_gallery_instance}";
//    $float = 'left';
//    $itemwidth = 100.0 / $columns;

	$gallery_style = $gallery_div = '';
//    if (apply_filters('use_default_gallery_style', true))
//        $gallery_style = "
//            <style type='text/css'>
//                    #{$selector} {
//                            margin: auto;
//                    }
//                    #{$selector} .gallery-item {
//                            float: {$float};
//                            margin-top: 10px;
//                            text-align: center;
//                            width: {$itemwidth}%;
//                    }
//                    .clearfix {
//                        clear:both;
//                    }
//                    #{$selector} img {
//                            border: 2px solid #cfcfcf;
//                    }
//                    #{$selector} .gallery-caption {
//                            margin-left: 0;
//                    }
//                    #{$selector}-pag-nav {
//                    }
//            </style>";
	$size_class  = sanitize_html_class( $size );
	$gallery_div = "<div id='$selector' class='slick-gallery slick-galleryid-{$id} slick-gallery-columns-{$columns} slick-gallery-size-{$size_class}'>";
//    $gallery_clearfix = '<br class="clearfix" />';

	$cols               = $columns;
	$has_custom_content = ! empty( $render_func );
	if ( count( $objects ) > 0 ) {
//        $output .= apply_filters('gallery_style', $gallery_style . "\n\t\t" . $gallery_div);
		$output .= $gallery_div;
		foreach ( $objects as $o ) {
			$output .= '<div class="slick-gallery-item">';
			if ( $has_custom_content ) {
				$output .= apply_filters( "amapress_gallery_render_{$render_func}", $o );
			} else {
				$output .= wp_get_attachment_image( $o->ID, $size );
			}
			$output .= '</div>';
			$cols --;
//            if ($cols == 0) {
//                $output .= $gallery_clearfix;
//                $cols = $columns;
//            }
		}
		$output .= '</div>';
	} else {
		$output .= urldecode( $if_empty );
	}

	return $output;
}

function amapress_generic_paged_gallery_shortcode( $attr ) {
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

	$id      = $post ? $post->ID : 0;
	$order   = 'ASC';
	$columns = 3;
	$rows    = 2;
	$orderby = 'title';
//    $posts_per_page = -1;
	$query_uri   = '';
	$query_func  = '';
	$render_func = '';
	$post_type   = 'post';
	$size        = 'thumbnail';
//    $gallery_page_name = 'gallery_page';
//    $gallery_page_slug = 'page';
	$if_empty     = '';
	$post__in     = '';
	$post__not_in = '';
	extract( shortcode_atts( array(
//        'posts_per_page' => $posts_per_page,
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
//        'gallery_page_name' => $gallery_page_name,
//        'gallery_page_slug' => $gallery_page_slug,
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

	$selector = "slick-gallery-{$amapress_gallery_instance}";
//    $float = 'left';
//    $itemwidth = 100.0 / $columns;

	$gallery_style = $gallery_div = '';
//    if (apply_filters('use_default_gallery_style', true))
//        $gallery_style = "
//            <style type='text/css'>
//                    #{$selector} {
//                            margin: auto;
//                    }
//                    #{$selector} .gallery-item {
//                            float: {$float};
//                            margin-top: 10px;
//                            text-align: center;
//                            width: {$itemwidth}%;
//                    }
//                    .clearfix {
//                        clear:both;
//                    }
//                    #{$selector} img {
//                            border: 2px solid #cfcfcf;
//                    }
//                    #{$selector} .gallery-caption {
//                            margin-left: 0;
//                    }
//                    #{$selector}-pag-nav {
//                    }
//            </style>";
	$slick_options = array(
		'slidesPerRow' => $columns,
		'rows'         => $rows,
		'infinite'     => false,
//        'adaptiveHeight' => true,
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
	$slick_options = json_encode( $slick_options );

	$size_class  = sanitize_html_class( $size );
	$gallery_div = "<div id='$selector' class='slick-gallery slick-galleryid-{$id} slick-gallery-columns-{$columns} slick-gallery-size-{$size_class}' data-slick='{$slick_options}'>";
//    $gallery_clearfix = '<br class="clearfix"/>';
	$gallery_nav_div = "<div id='{$selector}-pag-nav' class='slick-gallery slick-galleryid-{$id} slick-gallery-size-{$size_class}'>";

//    $gallery_page = (get_query_var($gallery_page_name)) ? get_query_var($gallery_page_name) : 1;

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
//            'paged' => $gallery_page,
			'post__in'       => $post__in,
			'post__not_in'   => $post__not_in
		);
		if ( $post_type == 'user' ) {
			$gallery = new WP_User_Query();
		} else {
			$gallery = new WP_Query();
		}
		$gallery->parse_query( $args );
	}

//    if (!empty($posts_per_page)) $gallery->set('posts_per_page', $posts_per_page);
//    if (!empty($gallery_page)) $gallery->set('paged', $gallery_page);
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

	$gallery->get_posts();
	$cols               = $columns;
	$has_custom_content = ! empty( $render_func );
	if ( $gallery->have_posts() ) :
//        $output .= apply_filters('gallery_style', $gallery_style . "\n\t\t" . $gallery_div);
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
//            if ($cols == 0) {
//                $output .= $gallery_clearfix;
//                $cols = $columns;
//            }
		endwhile;
		$output .= '</div>';
		$output .= $gallery_nav_div;
//        if (get_option('permalink_structure')) {
//            $format = '?' . $gallery_page_name . '=%#%';
//            $format = $gallery_page_slug.'/%#%';
//        } else {
//            $format = '&' . $gallery_page_name . '=%#%';
//        }
//        $args = array(
//            'base' => $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '%_%',
//            'format' => $format,
//            'current' => $gallery_page,
//            'total' => $gallery->max_num_pages
//        );
//        $output .= '<div style="clear:both">';
//        $output .= paginate_links($args);
//        $output .= '</div>';
		wp_reset_postdata();
		$output .= '</div>';
	else:
		$output .= urldecode( $if_empty );
	endif;

	return $output;
}
