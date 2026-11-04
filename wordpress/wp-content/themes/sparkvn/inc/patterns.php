<?php
/**
 * Pattern categories, asset rewrite, and isolated previews.
 *
 * @package Spark_VN
 */

/**
 * Register pattern categories before theme patterns are read.
 */
function sparkvn_register_pattern_categories(): void {
	$cats = array(
		'sparkvn-pages'   => __( 'Spark pages', 'sparkvn' ),
		'sparkvn-layout'  => __( 'Spark layout', 'sparkvn' ),
		'sparkvn-cards'   => __( 'Spark cards', 'sparkvn' ),
		'sparkvn-content' => __( 'Spark content', 'sparkvn' ),
		'sparkvn-cta'     => __( 'Spark CTAs', 'sparkvn' ),
		'sparkvn-connect' => __( 'Spark forms', 'sparkvn' ),
	);
	foreach ( $cats as $slug => $label ) {
		register_block_pattern_category( $slug, array( 'label' => $label ) );
	}
}
add_action( 'init', 'sparkvn_register_pattern_categories', 1 );

/**
 * File headers change often in this theme. Drop WP's pattern-file cache
 * so Categories: in /patterns/*.php always win (stale cache → Uncategorized).
 */
function sparkvn_refresh_pattern_cache(): void {
	$theme = wp_get_theme();
	if ( $theme instanceof WP_Theme ) {
		$theme->delete_pattern_cache();
	}
}
add_action( 'after_setup_theme', 'sparkvn_refresh_pattern_cache', 1 );

/**
 * Hide default core patterns that fight Spark layout.
 */
function sparkvn_unregister_core_patterns(): void {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'sparkvn_unregister_core_patterns' );

/**
 * Point pattern markup at theme URIs so editor previews load images.
 */
function sparkvn_normalize_theme_patterns(): void {
	$registry = WP_Block_Patterns_Registry::get_instance();
	foreach ( $registry->get_all_registered() as $pattern ) {
		$name = $pattern['name'] ?? '';
		if ( ! str_starts_with( $name, 'sparkvn/' ) ) {
			continue;
		}
		$content = sparkvn_rewrite_theme_assets( (string) ( $pattern['content'] ?? '' ) );
		if ( $content === ( $pattern['content'] ?? '' ) ) {
			continue;
		}
		unregister_block_pattern( $name );
		unset( $pattern['name'], $pattern['filePath'] );
		$pattern['content'] = $content;
		register_block_pattern( $name, $pattern );
	}
}
add_action( 'init', 'sparkvn_normalize_theme_patterns', 30 );

/**
 * Isolated pattern preview used to capture inserter screenshots.
 */
function sparkvn_pattern_preview(): void {
	if ( empty( $_GET['sparkvn_pattern'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$slug    = sanitize_title( wp_unslash( (string) $_GET['sparkvn_pattern'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'sparkvn/' . $slug );
	if ( ! $pattern ) {
		wp_die( esc_html__( 'Unknown pattern.', 'sparkvn' ), 404 );
	}

	status_header( 200 );
	nocache_headers();
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
	<style>
		.site-header, .site-footer, .skip-link { display: none !important; }
		body.sparkvn-pattern-preview { margin: 0; background: var(--ivory, #FBF7F0); }
		#primary { margin: 0; padding: 0; }
	</style>
</head>
<body <?php body_class( 'sparkvn-pattern-preview' ); ?>>
	<main id="primary">
		<?php echo do_blocks( $pattern['content'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'sparkvn_pattern_preview', 0 );
