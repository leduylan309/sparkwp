<?php
/**
 * Category archives redirect to the blog index with backend pagination.
 *
 * @package Spark_VN
 */

$term = get_queried_object();
if ( $term instanceof \WP_Term ) {
	wp_safe_redirect( sparkvn_blog_url( $term->slug ), 301 );
	exit;
}
require get_template_directory() . '/home.php';
