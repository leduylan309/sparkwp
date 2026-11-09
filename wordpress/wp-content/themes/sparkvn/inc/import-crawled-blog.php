<?php
/**
 * Import crawled blog posts from uploads/_blog-import/upload-payload.json
 *
 * Run: wp eval-file wp-content/themes/sparkvn/inc/import-crawled-blog.php
 *
 * @package Spark_VN
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$root    = WP_CONTENT_DIR . '/uploads/_blog-import';
$payload = $root . '/upload-payload.json';
$progress_file = $root . '/upload-progress.json';

if ( ! file_exists( $payload ) ) {
	WP_CLI::error( "Missing payload: $payload" );
}

$items = json_decode( (string) file_get_contents( $payload ), true );
if ( ! is_array( $items ) ) {
	WP_CLI::error( 'Invalid payload JSON' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

kses_remove_filters();

$author_id = 1;
$users     = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( $users ) {
	$author_id = (int) $users[0];
}

$write_progress = static function ( array $items ) use ( $progress_file, $root ): void {
	file_put_contents( $progress_file, wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );

	$done  = 0;
	$total = count( $items );
	foreach ( $items as $it ) {
		if ( ( $it['upload_status'] ?? '' ) === 'done' ) {
			++$done;
		}
	}

	$lines   = array();
	$lines[] = '# Blog Upload Checklist';
	$lines[] = '';
	$lines[] = '**Đích:** WordPress local `http://localhost:8080/blog/`  ';
	$lines[] = "**Tổng bài:** $total  ";
	$lines[] = "**Tiến độ upload:** $done/$total  ";
	$lines[] = '';
	$lines[] = '> Duplicate từ danh sách crawl. `[x]` = đã upload lên WP theo layout mới.';
	$lines[] = '';
	$lines[] = '| Trạng thái | # | Slug | Tiêu đề | Categories | WP ID |';
	$lines[] = '|------------|---|------|---------|------------|-------|';
	foreach ( $items as $it ) {
		$status = $it['upload_status'] ?? 'pending';
		$mark   = 'done' === $status ? '[x]' : ( 'error' === $status ? '[!]' : '[ ]' );
		$num    = isset( $it['num'] ) ? str_pad( (string) (int) $it['num'], 3, '0', STR_PAD_LEFT ) : '000';
		$slug   = $it['slug'] ?? '';
		$title  = str_replace( '|', '\\|', (string) ( $it['title'] ?? '' ) );
		$cats   = implode( ', ', array_map( 'strval', (array) ( $it['categories'] ?? array() ) ) );
		$wid    = $it['wp_id'] ?? '';
		$lines[] = "| $mark | $num | `$slug` | $title | $cats | $wid |";
	}
	$md = implode( "\n", $lines ) . "\n";
	file_put_contents( $root . '/UPLOAD-CHECKLIST.md', $md );
};

$sideload = static function ( string $abs, int $post_id, string $title ) {
	if ( ! file_exists( $abs ) ) {
		return 0;
	}
	$tmp = wp_tempnam( basename( $abs ) );
	if ( ! $tmp ) {
		return 0;
	}
	copy( $abs, $tmp );
	$file_array = array(
		'name'     => basename( $abs ),
		'tmp_name' => $tmp,
	);
	$id = media_handle_sideload( $file_array, $post_id, $title );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		WP_CLI::warning( 'media fail: ' . $id->get_error_message() );
		return 0;
	}
	return (int) $id;
};

$total = count( $items );
WP_CLI::log( "Importing $total posts…" );

foreach ( $items as $i => &$it ) {
	$n     = $i + 1;
	$slug  = sanitize_title( (string) ( $it['slug'] ?? '' ) );
	$title = (string) ( $it['title'] ?? $slug );
	WP_CLI::log( sprintf( '[%d/%d] %s', $n, $total, $slug ) );

	try {
		$existing = get_page_by_path( $slug, OBJECT, 'post' );
		$date     = '';
		if ( ! empty( $it['lastmod'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}/', (string) $it['lastmod'] ) ) {
			$date = substr( (string) $it['lastmod'], 0, 10 ) . ' 10:00:00';
		}

		$postarr = array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => (string) ( $it['content'] ?? '' ),
			'post_excerpt' => (string) ( $it['excerpt'] ?? '' ),
			'post_author'  => $author_id,
		);
		if ( $date ) {
			$postarr['post_date']     = $date;
			$postarr['post_date_gmt'] = get_gmt_from_date( $date );
		}

		if ( $existing ) {
			$postarr['ID'] = (int) $existing->ID;
			$pid           = wp_update_post( $postarr, true );
		} else {
			$pid = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $pid ) ) {
			throw new RuntimeException( $pid->get_error_message() );
		}
		$pid = (int) $pid;

		$cats = array_values(
			array_filter(
				array_map( 'sanitize_title', (array) ( $it['categories'] ?? array( 'parents' ) ) )
			)
		);
		if ( ! $cats ) {
			$cats = array( 'parents' );
		}
		foreach ( $cats as $cslug ) {
			if ( ! term_exists( $cslug, 'category' ) ) {
				wp_insert_term( ucfirst( $cslug ), 'category', array( 'slug' => $cslug ) );
			}
		}
		wp_set_object_terms( $pid, $cats, 'category' );

		$feat_rel = (string) ( $it['featured_image'] ?? '' );
		if ( $feat_rel ) {
			$abs = $root . '/' . ltrim( $feat_rel, '/' );
			$mid = $sideload( $abs, $pid, $title );
			if ( $mid > 0 ) {
				set_post_thumbnail( $pid, $mid );
				$url = wp_get_attachment_url( $mid );
				if ( $url ) {
					update_post_meta( $pid, 'spark_image', $url );
				}
			}
		}

		if ( ! empty( $it['source_url'] ) ) {
			update_post_meta( $pid, '_spark_source_url', esc_url_raw( (string) $it['source_url'] ) );
		}

		$it['wp_id']         = $pid;
		$it['upload_status'] = 'done';
		unset( $it['error'] );
		WP_CLI::log( "  OK id=$pid cats=" . implode( ',', $cats ) );
	} catch ( Throwable $e ) {
		$it['upload_status'] = 'error';
		$it['error']         = $e->getMessage();
		WP_CLI::warning( '  ERROR ' . $e->getMessage() );
	}

	// Persist lightweight progress (drop heavy content to keep file small).
	$light = array();
	foreach ( $items as $row ) {
		$light[] = array(
			'num'           => $row['num'] ?? null,
			'slug'          => $row['slug'] ?? '',
			'title'         => $row['title'] ?? '',
			'categories'    => $row['categories'] ?? array(),
			'upload_status' => $row['upload_status'] ?? 'pending',
			'wp_id'         => $row['wp_id'] ?? null,
			'error'         => $row['error'] ?? null,
		);
	}
	$write_progress( $light );

	if ( 0 === $n % 10 ) {
		WP_CLI::log( "  …checkpoint $n/$total" );
	}
}
unset( $it );

$done = 0;
$err  = 0;
foreach ( $items as $it ) {
	if ( ( $it['upload_status'] ?? '' ) === 'done' ) {
		++$done;
	}
	if ( ( $it['upload_status'] ?? '' ) === 'error' ) {
		++$err;
	}
}

WP_CLI::success( "Import complete: done=$done error=$err total=$total" );
