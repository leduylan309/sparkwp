<?php
/**
 * Template helpers.
 *
 * @package Spark_VN
 */

/**
 * Public search is blog posts only, same card grid as Teacher Blogs / Blog.
 *
 * @param \WP_Query $query Query.
 */
function sparkvn_search_posts_only( $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_search() ) {
		$query->set( 'post_type', 'post' );
		$query->set( 'posts_per_page', 9 );
		return;
	}
	if ( $query->is_home() ) {
		$query->set( 'post_type', 'post' );
		$query->set( 'posts_per_page', 9 );
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
		$topic = sanitize_title( (string) $query->get( 'spark_topic' ) );
		if ( ! $topic ) {
			$topic = sanitize_title( (string) ( $_GET['category_name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( $topic ) {
			$query->set( 'category_name', $topic );
		}
	}
}
add_action( 'pre_get_posts', 'sparkvn_search_posts_only' );

/**
 * Extra query var so /blog/?category_name= stays the posts index.
 *
 * @param array $vars Query vars.
 * @return array
 */
function sparkvn_query_vars( array $vars ): array {
	$vars[] = 'spark_topic';
	return $vars;
}
add_filter( 'query_vars', 'sparkvn_query_vars' );

/**
 * Keep /blog/?category_name=academic as the blog index, not a category archive.
 *
 * @param array $vars Request vars.
 * @return array
 */
function sparkvn_blog_topic_request( array $vars ): array {
	$blog_id = (int) get_option( 'page_for_posts' );
	if ( ! $blog_id || empty( $vars['category_name'] ) ) {
		return $vars;
	}
	$blog = get_post( $blog_id );
	if ( ! $blog instanceof \WP_Post ) {
		return $vars;
	}
	$on_blog = ( isset( $vars['pagename'] ) && $vars['pagename'] === $blog->post_name )
		|| ( isset( $vars['page_id'] ) && (int) $vars['page_id'] === $blog_id );
	if ( ! $on_blog ) {
		return $vars;
	}
	$vars['spark_topic'] = sanitize_title( (string) $vars['category_name'] );
	unset( $vars['category_name'] );
	return $vars;
}
add_filter( 'request', 'sparkvn_blog_topic_request' );

/**
 * Body classes.
 *
 * @param array $classes Classes.
 * @return array
 */
function sparkvn_body_classes( $classes ) {
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}
	$classes[] = 'no-sidebar';
	if ( sparkvn_has_solid_header() ) {
		$classes[] = 'spark-solid-header';
	}
	return $classes;
}
add_filter( 'body_class', 'sparkvn_body_classes' );


/**
 * Whether the page uses a solid (non-transparent) header.
 */
function sparkvn_has_solid_header(): bool {
	if ( is_front_page() ) {
		return false;
	}
	if ( is_singular( 'post' ) || is_home() || is_search() || is_category() ) {
		return true;
	}
	if ( is_page() ) {
		$flag = get_post_meta( get_the_ID(), '_spark_solid_header', true );
		if ( '1' === $flag ) {
			return true;
		}
		$slug = get_post_field( 'post_name', get_the_ID() );
		return in_array( $slug, array( 'policy', 'joe-evans', 'contact' ), true );
	}
	return ! is_singular( 'spark_program' );
}

/**
 * Fallback primary nav when no menu is assigned.
 */
function sparkvn_fallback_nav() {
	$items = array(
		array( __( 'Home', 'sparkvn' ), home_url( '/' ) ),
		array( __( 'About', 'sparkvn' ), home_url( '/about/' ) ),
		array( __( 'Assessment', 'sparkvn' ), home_url( '/assessment/' ) ),
		array( __( 'Programs', 'sparkvn' ), home_url( '/programs/' ) ),
		array( __( 'Exclusive', 'sparkvn' ), home_url( '/exclusive/' ) ),
		array( __( 'Contact', 'sparkvn' ), home_url( '/contact/' ) ),
		array( __( 'Blog', 'sparkvn' ), home_url( '/blog/' ) ),
	);
	foreach ( $items as $item ) {
		printf( '<a href="%s">%s</a>', esc_url( $item[1] ), esc_html( $item[0] ) );
	}
}

/**
 * Breadcrumb HTML matching .crumb-bar.
 */
function sparkvn_breadcrumb(): void {
	if ( is_front_page() ) {
		return;
	}

	$trail = array(
		array(
			'url'   => home_url( '/' ),
			'label' => __( 'Home', 'sparkvn' ),
		),
	);

	if ( is_singular( 'spark_program' ) ) {
		$trail[] = array(
			'url'   => home_url( '/programs/' ),
			'label' => __( 'Programs', 'sparkvn' ),
		);
		$trail[] = array(
			'url'   => '',
			'label' => get_the_title(),
		);
	} elseif ( is_singular( 'post' ) ) {
		$trail[] = array(
			'url'   => get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ),
			'label' => __( 'Blog', 'sparkvn' ),
		);
		$trail[] = array(
			'url'   => '',
			'label' => get_the_title(),
		);
	} elseif ( is_home() ) {
		$topic = sparkvn_blog_topic();
		$term  = $topic ? get_term_by( 'slug', $topic, 'category' ) : null;
		if ( $term instanceof \WP_Term ) {
			$trail[] = array(
				'url'   => sparkvn_blog_url(),
				'label' => __( 'Blog', 'sparkvn' ),
			);
			$trail[] = array(
				'url'   => '',
				'label' => $term->name,
			);
		} else {
			$trail[] = array(
				'url'   => '',
				'label' => __( 'Blog', 'sparkvn' ),
			);
		}
	} elseif ( is_page() ) {
		$chain       = array();
		$parent_slug = (string) get_post_meta( get_the_ID(), '_spark_parent_slug', true );
		$guard       = 0;
		while ( $parent_slug && $guard < 5 ) {
			$parent = get_page_by_path( $parent_slug );
			if ( ! $parent ) {
				break;
			}
			array_unshift( $chain, $parent );
			$parent_slug = (string) get_post_meta( $parent->ID, '_spark_parent_slug', true );
			++$guard;
		}
		foreach ( $chain as $ancestor ) {
			$trail[] = array(
				'url'   => get_permalink( $ancestor ),
				'label' => get_the_title( $ancestor ),
			);
		}
		$trail[] = array(
			'url'   => '',
			'label' => get_the_title(),
		);
	} else {
		$trail[] = array(
			'url'   => '',
			'label' => wp_get_document_title(),
		);
	}

	echo '<div class="crumb-bar"><div class="container"><nav class="crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'sparkvn' ) . '"><ol>';
	$last = count( $trail ) - 1;
	foreach ( $trail as $i => $crumb ) {
		if ( $i === $last || '' === $crumb['url'] ) {
			echo '<li aria-current="page"><span class="sep">/</span>' . esc_html( $crumb['label'] ) . '</li>';
		} else {
			echo '<li><a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['label'] ) . '</a></li>';
		}
	}
	echo '</ol></nav></div></div>';
}

/**
 * Theme asset URI.
 */
function sparkvn_asset_uri( string $path ): string {
	return trailingslashit( get_template_directory_uri() ) . ltrim( $path, '/' );
}

/**
 * Resolve a theme-relative, site-relative, or absolute media URL.
 */
function sparkvn_resolve_uri( string $url ): string {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}
	if ( preg_match( '#^(https?:)?//#i', $url ) || str_starts_with( $url, 'data:' ) ) {
		return $url;
	}
	if ( str_starts_with( $url, '/wp-content/' ) || str_starts_with( $url, '/wp-includes/' ) ) {
		return $url;
	}
	if ( preg_match( '#^(assets|css|js)/#', $url ) ) {
		return sparkvn_asset_uri( $url );
	}
	return $url;
}

/**
 * Rewrite theme-relative asset paths inside block markup.
 */
function sparkvn_rewrite_theme_assets( string $html ): string {
	$base = trailingslashit( get_template_directory_uri() );
	return (string) preg_replace( '#(?<=["\'])(?:\./)?assets/(img|video|files)/#', $base . 'assets/$1/', $html );
}

/**
 * True when rendering a block for the editor canvas or REST preview.
 */
function sparkvn_is_block_canvas(): bool {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}
	return is_admin();
}

/**
 * Split rendered HTML into top-level <div> elements (tab panels, etc.).
 *
 * @return array<int,string>
 */
function sparkvn_split_top_level_divs( string $html ): array {
	$html = trim( $html );
	if ( '' === $html ) {
		return array();
	}
	$parts = array();
	$len   = strlen( $html );
	$i     = 0;
	while ( $i < $len ) {
		while ( $i < $len && ctype_space( $html[ $i ] ) ) {
			++$i;
		}
		if ( $i >= $len ) {
			break;
		}
		if ( 0 !== substr_compare( $html, '<div', $i, 4, true ) ) {
			$next = stripos( $html, '<div', $i );
			if ( false === $next ) {
				break;
			}
			$i = $next;
			continue;
		}
		$depth = 0;
		$j     = $i;
		while ( $j < $len ) {
			$open  = stripos( $html, '<div', $j );
			$close = stripos( $html, '</div>', $j );
			if ( false === $close ) {
				$parts[] = substr( $html, $i );
				return $parts;
			}
			if ( false !== $open && $open < $close ) {
				++$depth;
				$j = $open + 4;
			} else {
				--$depth;
				$j = $close + 6;
				if ( 0 === $depth ) {
					$parts[] = substr( $html, $i, $j - $i );
					$i       = $j;
					break;
				}
			}
		}
		if ( $j >= $len && $i < $len ) {
			$parts[] = substr( $html, $i );
			break;
		}
	}
	return $parts;
}

/**
 * Sideload a theme-relative file into the Media Library (cached per request).
 *
 * @return array{id:int,url:string}
 */
function sparkvn_sideload_theme_file( string $rel ): array {
	static $cache = array();
	$rel           = ltrim( $rel, '/' );
	$rel           = preg_replace( '#^\./#', '', $rel );
	if ( isset( $cache[ $rel ] ) ) {
		return $cache[ $rel ];
	}
	if ( preg_match( '#^https?://#', $rel ) ) {
		$cache[ $rel ] = array(
			'id'  => 0,
			'url' => $rel,
		);
		return $cache[ $rel ];
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'meta_key'       => '_spark_theme_path',
			'meta_value'     => $rel,
		)
	);
	if ( $existing ) {
		$id            = (int) $existing[0]->ID;
		$cache[ $rel ] = array(
			'id'  => $id,
			'url' => (string) wp_get_attachment_url( $id ),
		);
		return $cache[ $rel ];
	}

	$abs = get_template_directory() . '/' . $rel;
	if ( ! file_exists( $abs ) ) {
		$cache[ $rel ] = array(
			'id'  => 0,
			'url' => sparkvn_asset_uri( $rel ),
		);
		return $cache[ $rel ];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$filename = basename( $abs );
	$tmp      = wp_tempnam( $filename );
	copy( $abs, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		),
		0
	);
	if ( is_wp_error( $id ) ) {
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		$cache[ $rel ] = array(
			'id'  => 0,
			'url' => sparkvn_asset_uri( $rel ),
		);
		return $cache[ $rel ];
	}
	update_post_meta( $id, '_spark_theme_path', $rel );
	$cache[ $rel ] = array(
		'id'  => (int) $id,
		'url' => (string) wp_get_attachment_url( $id ),
	);
	return $cache[ $rel ];
}

/**
 * Replace theme-relative asset paths in block markup with Media Library URLs.
 */
function sparkvn_sideload_content( string $content ): string {
	$updated = (string) preg_replace_callback(
		'#assets/(?:img|video|files)/[^\s"\']+#',
		static function ( $m ) {
			return sparkvn_sideload_theme_file( $m[0] )['url'];
		},
		$content
	);
	return (string) preg_replace_callback(
		'/<!-- wp:image ({.*?}) -->/',
		static function ( $m ) {
			$attrs = json_decode( $m[1], true );
			if ( ! is_array( $attrs ) || empty( $attrs['url'] ) ) {
				return $m[0];
			}
			$id = attachment_url_to_postid( (string) $attrs['url'] );
			if ( $id ) {
				$attrs['id'] = $id;
			}
			return '<!-- wp:image ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' -->';
		},
		$updated
	);
}

/**
 * Featured image or spark_image meta (theme-relative path allowed).
 */
function sparkvn_media_url( int $post_id, string $fallback = 'assets/img/campus-classroom.jpg' ): string {
	$url = get_the_post_thumbnail_url( $post_id, 'sparkvn-card' );
	if ( $url ) {
		return $url;
	}
	$meta = (string) get_post_meta( $post_id, 'spark_image', true );
	if ( $meta ) {
		if ( preg_match( '#^https?://#', $meta ) || str_starts_with( $meta, '/' ) ) {
			return $meta;
		}
		return sparkvn_asset_uri( $meta );
	}
	return sparkvn_asset_uri( $fallback );
}

/**
 * Blog index URL, optionally filtered to a category slug and page.
 */
function sparkvn_blog_url( string $category_slug = '', int $page = 1 ): string {
	$blog = (int) get_option( 'page_for_posts' );
	$url  = $blog ? (string) get_permalink( $blog ) : home_url( '/blog/' );
	if ( $page > 1 ) {
		$url = trailingslashit( $url ) . user_trailingslashit( 'page/' . $page, 'paged' );
	}
	if ( $category_slug ) {
		$url = add_query_arg( 'category_name', $category_slug, $url );
	}
	return $url;
}

/**
 * Active topic slug on the blog index (from /blog/?category_name= or spark_topic).
 */
function sparkvn_blog_topic(): string {
	$topic = sanitize_title( (string) get_query_var( 'spark_topic' ) );
	if ( $topic ) {
		return $topic;
	}
	return sanitize_title( (string) ( $_GET['category_name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * One Spark post card (blog / featured / search). Compact = More Posts on singles.
 */
function sparkvn_render_post_card( ?\WP_Post $post = null, string $style = 'full' ): void {
	$post_id = $post ? (int) $post->ID : (int) get_the_ID();
	$pcats   = get_the_category( $post_id );
	$slugs   = implode( ' ', wp_list_pluck( $pcats, 'slug' ) );
	$label   = $pcats ? $pcats[0]->name : __( 'Blog', 'sparkvn' );
	$thumb   = sparkvn_media_url( $post_id, 'assets/img/campus-reading.jpg' );
	$title   = get_the_title( $post_id );
	$link    = get_permalink( $post_id );
	if ( 'compact' === $style ) {
		$excerpt = get_the_date( '', $post_id ) . ' — ' . wp_trim_words( get_the_excerpt( $post_id ), 28 );
		?>
	<article class="post-card">
		<div class="pc-body">
			<h3><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<p class="pc-ex"><?php echo esc_html( $excerpt ); ?></p>
		</div>
	</article>
		<?php
		return;
	}
	?>
	<?php
	$mins = function_exists( 'sparkvn_reading_minutes' ) ? sparkvn_reading_minutes( $post_id ) : 4;
	?>
	<article class="post-card show" data-cat="<?php echo esc_attr( $slugs ); ?>" data-title="<?php echo esc_attr( $title ); ?>">
		<a class="thumb" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
		</a>
		<div class="pc-body">
			<span class="pc-cat"><?php echo esc_html( $label ); ?></span>
			<h3><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<p class="pc-ex"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 28 ) ); ?></p>
			<div class="pc-meta"><span><?php echo esc_html( get_the_date( '', $post_id ) ); ?></span></div>
			<span class="pc-foot">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: minutes */
						_n( '%d min read', '%d min read', $mins, 'sparkvn' ),
						$mins
					)
				);
				?>
			</span>
		</div>
	</article>
	<?php
}

/**
 * Prev / next on a single post — html/blog-post.html ghost buttons.
 */
function sparkvn_render_post_nav(): void {
	$prev = get_previous_post();
	$next = get_next_post();
	?>
	<nav class="post-nav" aria-label="<?php echo esc_attr__( 'Post navigation', 'sparkvn' ); ?>">
		<?php if ( $prev instanceof \WP_Post ) : ?>
			<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">&lt; <?php echo esc_html__( 'Older Post', 'sparkvn' ); ?></a>
		<?php endif; ?>
		<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( sparkvn_blog_url() ); ?>"><?php echo esc_html__( 'All posts', 'sparkvn' ); ?></a>
		<?php if ( $next instanceof \WP_Post ) : ?>
			<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><?php echo esc_html__( 'Newer Post', 'sparkvn' ); ?> &gt;</a>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * Spark pagination (Prev / numbered pills / Next) matching html/blog.html.
 *
 * @param \WP_Query|null $query Custom query; defaults to main query.
 * @param string         $category_slug Topic slug to preserve on blog pages.
 */
function sparkvn_render_pagination( ?\WP_Query $query = null, string $category_slug = '' ): void {
	global $wp_query;
	$q     = $query instanceof \WP_Query ? $query : $wp_query;
	$total = (int) $q->max_num_pages;
	if ( $total <= 1 ) {
		return;
	}
	$current = (int) $q->get( 'paged' );
	if ( $current < 1 ) {
		$current = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	}
	$svg_prev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>';
	$svg_next = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
	?>
	<nav class="pagination" aria-label="<?php echo esc_attr__( 'Blog pagination', 'sparkvn' ); ?>">
		<?php
		sparkvn_pagination_item( $current > 1 ? sparkvn_page_url( $current - 1, $category_slug ) : '', $svg_prev . esc_html__( 'Prev', 'sparkvn' ), 'page-btn nav', $current <= 1 );
		for ( $i = 1; $i <= $total; $i++ ) {
			sparkvn_pagination_item(
				$i === $current ? '' : sparkvn_page_url( $i, $category_slug ),
				(string) $i,
				'page-btn' . ( $i === $current ? ' active' : '' ),
				false,
				$i === $current
			);
		}
		sparkvn_pagination_item( $current < $total ? sparkvn_page_url( $current + 1, $category_slug ) : '', esc_html__( 'Next', 'sparkvn' ) . $svg_next, 'page-btn nav', $current >= $total );
		?>
	</nav>
	<?php
}

/**
 * One pagination pill.
 *
 * @param string $url      Target URL; empty renders a non-link.
 * @param string $html     Inner HTML (already escaped text + svg).
 * @param string $class    Class list.
 * @param bool   $disabled Disabled prev/next.
 * @param bool   $current  Current page.
 */
function sparkvn_pagination_item( string $url, string $html, string $class, bool $disabled = false, bool $current = false ): void {
	if ( $url && ! $disabled && ! $current ) {
		echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . $html . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	$extra = $disabled ? ' aria-disabled="true"' : '';
	if ( $current ) {
		$extra .= ' aria-current="page"';
	}
	echo '<span class="' . esc_attr( $class ) . '"' . $extra . '>' . $html . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Page URL for Spark pagination (blog topic or search).
 */
function sparkvn_page_url( int $page, string $category_slug = '' ): string {
	if ( is_search() ) {
		return html_entity_decode( get_pagenum_link( $page ) );
	}
	return sparkvn_blog_url( $category_slug, $page );
}

/**
 * Search field used on blog, teacher blogs, and search results.
 */
function sparkvn_render_blog_search( string $placeholder = '' ): void {
	if ( ! $placeholder ) {
		$placeholder = __( 'Search articles by topic, keyword or exam…', 'sparkvn' );
	}
	?>
	<form class="blog-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
		<input type="search" id="blogSearch" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" aria-label="<?php echo esc_attr__( 'Search the blog', 'sparkvn' ); ?>" />
	</form>
	<?php
}

/**
 * Rewrite leftover .html links if any remain.
 *
 * @param string $content Content.
 * @return string
 */
function sparkvn_content_html_links( $content ) {
	$map = sparkvn_html_permalink_map();
	foreach ( $map as $html => $url ) {
		$content = str_replace( 'href="' . $html . '"', 'href="' . esc_url( $url ) . '"', $content );
		$content = str_replace( "href='" . $html . "'", "href='" . esc_url( $url ) . "'", $content );
	}
	return sparkvn_rewrite_theme_assets( $content );
}
add_filter( 'the_content', 'sparkvn_content_html_links', 8 );

/**
 * Resolve theme-relative asset paths in Gutenberg's raw content payload.
 *
 * @param WP_REST_Response $response Response.
 * @return WP_REST_Response
 */
function sparkvn_rest_rewrite_assets( $response ) {
	$data = $response->get_data();
	if ( ! is_array( $data ) ) {
		return $response;
	}
	foreach ( array( 'content', 'excerpt' ) as $key ) {
		if ( ! empty( $data[ $key ]['raw'] ) && is_string( $data[ $key ]['raw'] ) ) {
			$data[ $key ]['raw'] = sparkvn_rewrite_theme_assets( $data[ $key ]['raw'] );
		}
		if ( ! empty( $data[ $key ]['rendered'] ) && is_string( $data[ $key ]['rendered'] ) ) {
			$data[ $key ]['rendered'] = sparkvn_rewrite_theme_assets( $data[ $key ]['rendered'] );
		}
	}
	$response->set_data( $data );
	return $response;
}
add_filter( 'rest_prepare_page', 'sparkvn_rest_rewrite_assets' );
add_filter( 'rest_prepare_post', 'sparkvn_rest_rewrite_assets' );
add_filter( 'rest_prepare_spark_program', 'sparkvn_rest_rewrite_assets' );

/**
 * Allow Spark media tags in post content.
 *
 * @param array  $tags    Tags.
 * @param string $context Context.
 * @return array
 */
function sparkvn_kses_allowed_html( $tags, $context ) {
	if ( 'post' !== $context ) {
		return $tags;
	}
	$tags['iframe'] = array(
		'src'             => true,
		'title'           => true,
		'loading'         => true,
		'width'           => true,
		'height'          => true,
		'style'           => true,
		'allowfullscreen' => true,
		'referrerpolicy'  => true,
		'frameborder'     => true,
	);
	$tags['video']  = array(
		'src'         => true,
		'poster'      => true,
		'autoplay'    => true,
		'muted'       => true,
		'loop'        => true,
		'playsinline' => true,
		'preload'     => true,
		'class'       => true,
		'aria-hidden' => true,
	);
	$tags['source'] = array(
		'src'  => true,
		'type' => true,
	);
	$tags['svg']    = array(
		'viewbox'        => true,
		'viewBox'        => true,
		'width'          => true,
		'height'         => true,
		'fill'           => true,
		'stroke'         => true,
		'xmlns'          => true,
		'class'          => true,
		'aria-hidden'    => true,
		'stroke-width'   => true,
		'stroke-linecap' => true,
		'stroke-linejoin'=> true,
	);
	$tags['path']   = array(
		'd'                 => true,
		'fill'              => true,
		'stroke'            => true,
		'stroke-width'      => true,
		'stroke-linecap'    => true,
		'stroke-linejoin'   => true,
	);
	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'sparkvn_kses_allowed_html', 10, 2 );

/**
 * HTML filename → permalink map.
 *
 * @return array<string,string>
 */
function sparkvn_html_permalink_map(): array {
	$home = trailingslashit( home_url( '/' ) );
	return array(
		'index.html'                             => $home,
		'about.html'                             => $home . 'about/',
		'our-story.html'                         => $home . 'our-story/',
		'joe-evans.html'                         => $home . 'joe-evans/',
		'english-support.html'                   => $home . 'english-support/',
		'the-spark-method.html'                  => $home . 'the-spark-method/',
		'class-size.html'                        => $home . 'class-size/',
		'community.html'                         => $home . 'community/',
		'assessment.html'                        => $home . 'assessment/',
		'programs.html'                          => $home . 'programs/',
		'phonics.html'                           => $home . 'programs/phonics/',
		'esl.html'                               => $home . 'programs/esl/',
		'ib-english.html'                        => $home . 'programs/ib-english/',
		'tides.html'                             => $home . 'programs/tides/',
		'ielts-program.html'                     => $home . 'programs/ielts/',
		'policy.html'                            => $home . 'policy/',
		'access.html'                            => $home . 'exclusive/',
		'teachers-corner.html'                   => $home . 'teachers-corner/',
		'ask-a-teacher.html'                     => $home . 'ask-a-teacher/',
		'teacher-blogs.html'                     => $home . 'teacher-blogs/',
		'parent-center.html'                     => $home . 'parent-center/',
		'why-academic-english-matters.html'      => $home . 'why-academic-english-matters/',
		'academic-roadmap.html'                  => $home . 'academic-roadmap/',
		'results-dashboard.html'                 => $home . 'results-dashboard/',
		'parent-success-roadmap.html'            => $home . 'parent-success-roadmap/',
		'resource-library.html'                  => $home . 'resource-library/',
		'international-school-support-guide.html'=> $home . 'international-school-support-guide/',
		'ib-school-guide.html'                   => $home . 'ib-school-guide/',
		'reading-age-growth-center.html'         => $home . 'reading-age-growth-center/',
		'student-portal.html'                    => $home . 'student-portal/',
		'spark-store.html'                       => $home . 'spark-store/',
		'contact.html'                           => $home . 'contact/',
		'blog.html'                              => $home . 'blog/',
		'blog-post.html'                         => $home . 'blog/',
	);
}
