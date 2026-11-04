<?php
/**
 * Spark VN functions and definitions.
 *
 * @package Spark_VN
 */

if ( ! defined( 'SPARKVN_VERSION' ) ) {
	define( 'SPARKVN_VERSION', '1.0.2' );
}

/**
 * Theme setup.
 */
function sparkvn_setup() {
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);

	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'sparkvn' ),
			'footer' => esc_html__( 'Footer', 'sparkvn' ),
		)
	);

	add_theme_support( 'wp-block-styles' );
	// Spark owns vertical rhythm. WP layout flow injects
	// `:root :where(.is-layout-flow) > :first-child { margin-block-start: 0 }`
	// and `> * { margin-block-start: 24px }`, which beat Spark widget margins.
	add_theme_support( 'disable-layout-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'css/main.css', 'css/editor.css' ) );
	add_theme_support( 'custom-line-height' );
	add_theme_support( 'custom-spacing' );
	add_theme_support( 'custom-units' );

	add_image_size( 'sparkvn-card', 800, 600, true );
	add_image_size( 'sparkvn-hero', 1920, 1080, true );
}
add_action( 'after_setup_theme', 'sparkvn_setup' );

/**
 * Load translations after init so WP 6.7+ does not warn.
 */
function sparkvn_load_textdomain(): void {
	load_theme_textdomain( 'sparkvn', get_template_directory() . '/languages' );
}
add_action( 'init', 'sparkvn_load_textdomain' );

/**
 * Content width.
 */
function sparkvn_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'sparkvn_content_width', 720 );
}
add_action( 'after_setup_theme', 'sparkvn_content_width', 0 );

/**
 * Front-end assets.
 */
function sparkvn_scripts() {
	wp_enqueue_style(
		'sparkvn-fonts',
		'https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,400;0,6..12,500;0,6..12,600;0,6..12,700;0,6..12,800;0,6..12,900;1,6..12,400;1,6..12,600&family=Frank+Ruhl+Libre:wght@300;400;500;600;700;800;900&display=swap',
		array(),
		null
	);
	$main_css = get_template_directory() . '/css/main.css';
	$ver      = file_exists( $main_css ) ? (string) filemtime( $main_css ) : SPARKVN_VERSION;
	wp_enqueue_style( 'sparkvn-main', get_template_directory_uri() . '/css/main.css', array( 'sparkvn-fonts' ), $ver );

	wp_enqueue_script( 'sparkvn-spark', get_template_directory_uri() . '/js/spark.js', array(), $ver, true );
	wp_enqueue_script( 'sparkvn-site', get_template_directory_uri() . '/js/site.js', array( 'sparkvn-spark' ), $ver, true );
	wp_enqueue_script( 'sparkvn-blog-list', get_template_directory_uri() . '/js/blog-list.js', array(), $ver, true );
	wp_enqueue_script( 'sparkvn-form', get_template_directory_uri() . '/js/form.js', array(), $ver, true );
	wp_localize_script(
		'sparkvn-form',
		'sparkvnForm',
		array(
			'rest'  => esc_url_raw( rest_url( 'sparkvn/v1/lead' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'sparkvn_scripts' );

/**
 * Resource hints for Google Fonts.
 *
 * @param array  $urls          URLs.
 * @param string $relation_type Relation.
 * @return array
 */
function sparkvn_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href'        => 'https://fonts.googleapis.com',
			'crossorigin' => false,
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'sparkvn_resource_hints', 10, 2 );

require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/nav-walker.php';
require get_template_directory() . '/inc/post-types.php';
require get_template_directory() . '/inc/block-styles.php';
require get_template_directory() . '/inc/patterns.php';
require get_template_directory() . '/inc/blocks.php';
require get_template_directory() . '/inc/rest-leads.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require get_template_directory() . '/inc/cli.php';
}
