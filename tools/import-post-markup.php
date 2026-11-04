<?php
/**
 * Write rewritten post_content back onto posts.
 *
 * @package Spark_VN
 */

$dir = WP_CONTENT_DIR . '/uploads/_markup-fix';
if ( ! is_dir( $dir ) ) {
	fwrite( STDERR, "Missing $dir\n" );
	exit( 1 );
}

$updated = 0;
foreach ( glob( $dir . '/*.html' ) ?: array() as $file ) {
	$id = (int) basename( $file, '.html' );
	if ( $id < 1 ) {
		continue;
	}
	$next = (string) file_get_contents( $file );
	$prev = (string) get_post_field( 'post_content', $id, 'raw' );
	if ( $next === $prev ) {
		continue;
	}
	wp_update_post(
		array(
			'ID'           => $id,
			'post_content' => $next,
		)
	);
	++$updated;
}
WP_CLI::success( "updated $updated posts" );
