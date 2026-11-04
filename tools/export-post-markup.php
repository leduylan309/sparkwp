<?php
/**
 * Dump published post_content for Gutenberg markup rewrite.
 *
 * @package Spark_VN
 */

$dir = WP_CONTENT_DIR . '/uploads/_markup-fix';
if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
	fwrite( STDERR, "Cannot create $dir\n" );
	exit( 1 );
}

$q     = new WP_Query(
	array(
		'post_type'      => array( 'page', 'post', 'spark_program' ),
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
$count = 0;
foreach ( $q->posts as $id ) {
	$content = get_post_field( 'post_content', $id, 'raw' );
	if ( ! is_string( $content ) || $content === '' ) {
		continue;
	}
	file_put_contents( $dir . '/' . (int) $id . '.html', $content );
	++$count;
}
WP_CLI::success( "exported $count posts to $dir" );
