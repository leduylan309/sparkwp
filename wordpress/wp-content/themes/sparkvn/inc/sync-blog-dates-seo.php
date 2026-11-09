<?php
/**
 * Sync publish dates (old site), titles, SEO meta, and clean slugs.
 *
 * wp eval-file wp-content/themes/sparkvn/inc/sync-blog-dates-seo.php
 *
 * @package Spark_VN
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dates_file = '/var/www/html/wp-content/uploads/_blog-import/../uploads/_blog-import';
// Prefer theme-docs path via uploads copy.
$candidates = array(
	WP_CONTENT_DIR . '/uploads/_blog-import/sparkvn-blog-dates.json',
	ABSPATH . '../docs/sparkvn-blog-dates.json',
);
// Host bind: wordpress is /var/www/html — docs is sibling on host, not mounted.
// Use copy placed next to import payload.
$dates_path = WP_CONTENT_DIR . '/uploads/_blog-import/sparkvn-blog-dates.json';
if ( ! file_exists( $dates_path ) ) {
	WP_CLI::error( "Missing $dates_path — copy docs/sparkvn-blog-dates.json there first." );
}

$dates = json_decode( (string) file_get_contents( $dates_path ), true );
if ( ! is_array( $dates ) ) {
	WP_CLI::error( 'Invalid dates JSON' );
}

/**
 * Normalize sparkvn article URLs for matching.
 */
$norm_url = static function ( string $url ): string {
	$url = untrailingslashit( trim( $url ) );
	$url = str_replace( 'http://', 'https://', $url );
	$url = preg_replace( '#^https://www\.sparkvn\.com#', 'https://www.sparkvn.com', $url ) ?: $url;
	// Decode path for stable compare, collapse spaces.
	$parts = wp_parse_url( $url );
	$path  = rawurldecode( (string) ( $parts['path'] ?? '' ) );
	$path  = preg_replace( '/\s+/', ' ', $path ) ?: $path;
	$path  = strtolower( trim( $path, '/' ) );
	$path  = preg_replace( '/[^a-z0-9\/\- %]/', '', $path ) ?: $path;
	$path  = preg_replace( '/\s+/', '-', $path ) ?: $path;
	$path  = preg_replace( '/-+/', '-', $path ) ?: $path;
	return 'https://www.sparkvn.com/' . $path;
};

$by_url = array();
foreach ( $dates as $row ) {
	$url = (string) ( $row['url'] ?? '' );
	if ( ! $url ) {
		continue;
	}
	$by_url[ $norm_url( $url ) ] = $row;
	$by_url[ untrailingslashit( $url ) ] = $row;
}

/** Prefer English SEO title when listing title is Korean. */
$force_title = array(
	'https://www.sparkvn.com/international%20students%20english%20vietnam' => 'Why International School Students Struggle with English in Vietnam | Spark Thao Dien',
	'https://www.sparkvn.com/international students english vietnam'         => 'Why International School Students Struggle with English in Vietnam | Spark Thao Dien',
	'https://www.sparkvn.com/my-post'                                       => 'Why Is My Child Falling Behind in English at International School? Parent Deep Dive | Spark',
);

kses_remove_filters();

// Ensure author display name.
$author_id = 1;
$user      = get_user_by( 'id', $author_id );
if ( $user && 'Spark English Center Vietnam' !== $user->display_name ) {
	wp_update_user(
		array(
			'ID'           => $author_id,
			'display_name' => 'Spark English Center Vietnam',
			'nickname'     => 'Spark English Center Vietnam',
		)
	);
	WP_CLI::log( 'Updated author display name' );
}

$q = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

$updated = 0;
$miss    = 0;
$same_day_index = array();

foreach ( $q->posts as $pid ) {
	$pid = (int) $pid;
	$src = (string) get_post_meta( $pid, '_spark_source_url', true );
	$row = $by_url[ $norm_url( $src ) ] ?? $by_url[ untrailingslashit( $src ) ] ?? null;
	if ( ! $row || empty( $row['date'] ) ) {
		++$miss;
		WP_CLI::warning( "No date for #$pid $src" );
		continue;
	}

	$date = (string) $row['date']; // Y-m-d
	// Stable newest→oldest within same calendar day using list position.
	$key = $date;
	if ( ! isset( $same_day_index[ $key ] ) ) {
		$same_day_index[ $key ] = 0;
	}
	$seq   = $same_day_index[ $key ]++;
	// Earlier in listing = newer → higher hour of day.
	$hour  = max( 8, 20 - $seq );
	$min   = ( $seq * 7 ) % 60;
	$local = sprintf( '%s %02d:%02d:00', $date, $hour, $min );

	$title = (string) ( $force_title[ $src ] ?? $force_title[ str_replace( '%20', ' ', $src ) ] ?? ( $row['title'] ?? '' ) );
	$title = trim( html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	// Strip wrapping quotes sometimes present in Duda titles.
	$title = trim( $title, " \t\n\r\0\x0B\"“”" );

	$excerpt = (string) get_post_field( 'post_excerpt', $pid );
	if ( strlen( $excerpt ) < 40 ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $pid ) ), 36 );
	}

	// SEO slug: keep good slugs; replace my-post* with sanitized title slug when needed.
	$current_slug = (string) get_post_field( 'post_name', $pid );
	$new_slug     = $current_slug;
	if ( preg_match( '/^my-post/i', $current_slug ) || false !== strpos( $current_slug, '%20' ) || preg_match( '/\s/', rawurldecode( $current_slug ) ) ) {
		$new_slug = sanitize_title( $title );
		if ( strlen( $new_slug ) < 8 ) {
			$new_slug = sanitize_title( wp_parse_url( $src, PHP_URL_PATH ) ?: $title );
		}
	}

	$postarr = array(
		'ID'            => $pid,
		'post_date'     => $local,
		'post_date_gmt' => get_gmt_from_date( $local ),
		'post_title'    => $title ?: get_the_title( $pid ),
		'post_excerpt'  => $excerpt,
		'post_author'   => $author_id,
	);
	if ( $new_slug && $new_slug !== $current_slug ) {
		$postarr['post_name'] = wp_unique_post_slug( $new_slug, $pid, 'publish', 'post', 0 );
	}

	$r = wp_update_post( $postarr, true );
	if ( is_wp_error( $r ) ) {
		WP_CLI::warning( $r->get_error_message() );
		continue;
	}

	// Theme SEO meta (used by sparkvn_seo_*).
	$seo_title = $title;
	if ( stripos( $seo_title, 'spark' ) === false ) {
		$seo_title .= ' | Spark English Center Vietnam';
	}
	$seo_desc = $excerpt;
	if ( strlen( $seo_desc ) > 160 ) {
		$seo_desc = rtrim( substr( $seo_desc, 0, 157 ) ) . '…';
	}
	update_post_meta( $pid, '_spark_seo_title', $seo_title );
	update_post_meta( $pid, '_spark_seo_description', $seo_desc );
	update_post_meta( $pid, '_spark_publish_date_source', $date );
	update_post_meta( $pid, '_spark_list_page', (int) ( $row['list_page'] ?? 0 ) );
	update_post_meta( $pid, '_spark_list_pos', (int) ( $row['pos'] ?? 0 ) );

	++$updated;
	if ( 0 === $updated % 40 ) {
		WP_CLI::log( "… $updated updated" );
	}
}

WP_CLI::success( "Synced dates/SEO: updated=$updated missing=$miss" );
