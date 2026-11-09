<?php
/**
 * Register Spark custom blocks.
 *
 * @package Spark_VN
 */

/**
 * Register editor script + all block.json folders.
 */
function sparkvn_register_blocks(): void {
	wp_register_script(
		'sparkvn-blocks-editor',
		get_template_directory_uri() . '/js/editor-blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-hooks', 'wp-server-side-render', 'wp-dom-ready', 'wp-data' ),
		(string) filemtime( get_template_directory() . '/js/editor-blocks.js' ),
		true
	);

	$blocks = array( 'hero', 'tabs', 'tab-panel', 'query-programs', 'query-posts', 'query-prizes', 'query-resources', 'form', 'card' );
	foreach ( $blocks as $block ) {
		register_block_type( get_template_directory() . '/blocks/' . $block );
	}
}
add_action( 'init', 'sparkvn_register_blocks' );

/**
 * Default attributes for isolated block screenshots / examples.
 *
 * @return array<string,array<string,mixed>>
 */
function sparkvn_block_preview_attributes(): array {
	$img = sparkvn_asset_uri( 'assets/img/campus-classroom.jpg' );
	$hub = sparkvn_asset_uri( 'assets/img/cdn/IMG_2107-1920w.jpeg' );
	return array(
		'hero'            => array(
			'variation' => 'page',
			'eyebrow'   => 'About Spark',
			'title'     => 'Explore Spark',
			'imageUrl'  => $img,
			'ariaLabel' => 'About Spark',
		),
		'card'            => array(
			'variation' => 'hub',
			'title'     => 'Our Story',
			'excerpt'   => 'Learn how Spark was founded and why reading and writing come first.',
			'url'       => '/our-story/',
			'imageUrl'  => $hub,
			'goLabel'   => 'Explore',
		),
		'form'            => array(
			'variation' => 'contact',
			'heading'   => 'Contact Us',
			'sub'       => 'Tell us about your child and we will get back to you.',
		),
		'tabs'            => array(
			'variation' => 'default',
			'heading'   => 'Program Levels',
			'labels'    => 'Level 1 | Level 2',
		),
		'query-programs'  => array(),
		'query-posts'     => array( 'title' => 'Latest articles' ),
		'query-prizes'    => array(),
		'query-resources' => array(),
	);
}

/**
 * Isolated block preview used to capture inserter screenshots.
 */
function sparkvn_block_preview(): void {
	if ( empty( $_GET['sparkvn_block'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$slug  = sanitize_title( wp_unslash( (string) $_GET['sparkvn_block'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$attrs = sparkvn_block_preview_attributes();
	if ( ! isset( $attrs[ $slug ] ) ) {
		wp_die( esc_html__( 'Unknown block.', 'sparkvn' ), 404 );
	}

	$inner = '';
	if ( 'tabs' === $slug ) {
		$inner = '<div class="tab-panel active copy"><h3>Level 1</h3><p>Phonics and early reading foundations.</p></div><div class="tab-panel copy"><h3>Level 2</h3><p>Fluency and writing confidence.</p></div>';
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
		<?php
		echo render_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'blockName'    => 'sparkvn/' . $slug,
				'attrs'        => $attrs[ $slug ],
				'innerHTML'    => $inner,
				'innerContent' => $inner ? array( $inner ) : array(),
				'innerBlocks'  => array(),
			)
		);
		?>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'sparkvn_block_preview', 0 );

/**
 * Editor chrome + theme URI for resolving pattern/block images.
 */
function sparkvn_enqueue_block_editor_assets(): void {
	$by_title = array();
	$dir      = get_template_directory() . '/assets/patterns';
	$uri      = trailingslashit( get_template_directory_uri() ) . 'assets/patterns/';
	if ( is_dir( $dir ) ) {
		$files = array();
		foreach ( glob( $dir . '/*.png' ) ?: array() as $file ) {
			$files[ basename( $file, '.png' ) ] = $uri . basename( $file ) . '?v=' . filemtime( $file );
		}
		foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
			$slug = preg_replace( '#^sparkvn/#', '', (string) ( $pattern['name'] ?? '' ) );
			if ( isset( $files[ $slug ], $pattern['title'] ) ) {
				$by_title[ $pattern['title'] ] = $files[ $slug ];
			}
		}
	}

	$block_shots = array();
	$bdir        = get_template_directory() . '/assets/blocks';
	$buri        = trailingslashit( get_template_directory_uri() ) . 'assets/blocks/';
	if ( is_dir( $bdir ) ) {
		foreach ( glob( $bdir . '/*.png' ) ?: array() as $file ) {
			$block_shots[ basename( $file, '.png' ) ] = $buri . basename( $file ) . '?v=' . filemtime( $file );
		}
	}

	wp_localize_script(
		'sparkvn-blocks-editor',
		'sparkvnEditor',
		array(
			'themeUri'         => get_template_directory_uri(),
			'screenshots'      => $by_title,
			'blockScreenshots' => $block_shots,
		)
	);

	wp_enqueue_style(
		'sparkvn-fonts',
		'https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,400;0,6..12,500;0,6..12,600;0,6..12,700;0,6..12,800;0,6..12,900;1,6..12,400;1,6..12,600&family=Frank+Ruhl+Libre:wght@300;400;500;600;700;800;900&display=swap',
		array(),
		null
	);

	$editor_css = get_template_directory() . '/css/editor.css';
	wp_enqueue_style(
		'sparkvn-editor',
		get_template_directory_uri() . '/css/editor.css',
		array( 'sparkvn-fonts' ),
		file_exists( $editor_css ) ? (string) filemtime( $editor_css ) : SPARKVN_VERSION
	);

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'page' === $screen->post_type ) {
		wp_add_inline_style(
			'sparkvn-editor',
			'.editor-visual-editor__post-title-wrapper,.edit-post-visual-editor__post-title-wrapper{display:none!important}'
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'sparkvn_enqueue_block_editor_assets' );

/**
 * Inject type-specific canvas CSS into the iframed block editor.
 *
 * Posts: mirror frontend .prose article (width, type, rhythm).
 * Pages: hide the WP title field (title lives in hero blocks).
 *
 * @param array                   $settings Editor settings.
 * @param WP_Block_Editor_Context $context  Editor context.
 * @return array
 */
function sparkvn_block_editor_type_styles( array $settings, $context ): array {
	$post = isset( $context->post ) ? $context->post : null;
	if ( ! $post instanceof WP_Post ) {
		return $settings;
	}

	$map = array(
		'post' => 'css/editor-post.css',
		'page' => 'css/editor-page.css',
	);
	$rel = $map[ $post->post_type ] ?? '';
	if ( ! $rel ) {
		return $settings;
	}

	$file = get_template_directory() . '/' . $rel;
	if ( ! is_readable( $file ) ) {
		return $settings;
	}

	$css = (string) file_get_contents( $file );
	if ( '' === trim( $css ) ) {
		return $settings;
	}

	if ( ! isset( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
		$settings['styles'] = array();
	}
	$settings['styles'][] = array(
		'css'            => $css,
		'__unstableType' => 'theme',
	);

	return $settings;
}
add_filter( 'block_editor_settings_all', 'sparkvn_block_editor_type_styles', 20, 2 );
