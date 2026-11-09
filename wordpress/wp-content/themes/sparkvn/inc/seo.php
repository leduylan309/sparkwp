<?php
/**
 * Blog SEO + GEO helpers (title, meta, Open Graph, JSON-LD).
 *
 * @package Spark_VN
 */

/**
 * Document title for single posts.
 *
 * @param array $parts Title parts.
 * @return array
 */
function sparkvn_document_title_parts( array $parts ): array {
	if ( ! is_singular( 'post' ) ) {
		return $parts;
	}
	$custom = (string) get_post_meta( get_the_ID(), '_spark_seo_title', true );
	if ( $custom ) {
		$parts['title'] = $custom;
		unset( $parts['site'] ); // avoid "Title - Site - Site"
	}
	return $parts;
}
add_filter( 'document_title_parts', 'sparkvn_document_title_parts' );

/**
 * Meta description, canonical, OG/Twitter, geo hints, JSON-LD.
 */
function sparkvn_seo_wp_head(): void {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post_id = get_the_ID();
	$title   = (string) get_post_meta( $post_id, '_spark_seo_title', true );
	if ( ! $title ) {
		$title = wp_get_document_title();
	}
	$desc = (string) get_post_meta( $post_id, '_spark_seo_description', true );
	if ( ! $desc ) {
		$desc = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 36 );
	}
	$desc = wp_strip_all_tags( $desc );
	$url  = get_permalink( $post_id );
	$img  = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( ! $img ) {
		$img = sparkvn_media_url( $post_id, 'assets/img/campus-reading.jpg' );
	}
	$author = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
	$published = get_the_date( 'c', $post_id );
	$modified  = get_the_modified_date( 'c', $post_id );
	$cats      = get_the_category( $post_id );
	$cat_names = $cats ? wp_list_pluck( $cats, 'name' ) : array( 'Blog' );

	echo '<meta name="description" content="' . esc_attr( $desc ) . "\" />\n";
	echo '<link rel="canonical" href="' . esc_url( $url ) . "\" />\n";

	// Local GEO signals for Spark Thao Dien / HCMC.
	echo '<meta name="geo.region" content="VN-SG" />' . "\n";
	echo '<meta name="geo.placename" content="Thao Dien, Ho Chi Minh City" />' . "\n";
	echo '<meta name="geo.position" content="10.8027;106.7331" />' . "\n";
	echo '<meta name="ICBM" content="10.8027, 106.7331" />' . "\n";

	echo '<meta property="og:type" content="article" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . "\" />\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . "\" />\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . "\" />\n";
	echo '<meta property="og:image" content="' . esc_url( $img ) . "\" />\n";
	echo '<meta property="og:site_name" content="Spark English Center Vietnam" />' . "\n";
	echo '<meta property="og:locale" content="en_US" />' . "\n";
	echo '<meta property="article:published_time" content="' . esc_attr( $published ) . "\" />\n";
	echo '<meta property="article:modified_time" content="' . esc_attr( $modified ) . "\" />\n";
	foreach ( $cat_names as $cn ) {
		echo '<meta property="article:section" content="' . esc_attr( $cn ) . "\" />\n";
	}
	echo '<meta property="article:author" content="' . esc_attr( $author ) . "\" />\n";

	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . "\" />\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . "\" />\n";
	echo '<meta name="twitter:image" content="' . esc_url( $img ) . "\" />\n";

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'headline'         => $title,
		'description'      => $desc,
		'image'            => array( $img ),
		'datePublished'    => $published,
		'dateModified'     => $modified,
		'author'           => array(
			'@type' => 'Organization',
			'name'  => $author ?: 'Spark English Center Vietnam',
			'url'   => home_url( '/' ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => 'Spark English Center Vietnam',
			'url'   => home_url( '/' ),
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => get_template_directory_uri() . '/assets/logo-purple.png',
			),
		),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $url,
		),
		'articleSection'   => $cat_names,
		'inLanguage'       => 'en-US',
		'isPartOf'         => array(
			'@type' => 'Blog',
			'name'  => 'Spark English Center Blog',
			'url'   => sparkvn_blog_url(),
		),
		'about'            => array(
			array(
				'@type' => 'Place',
				'name'  => 'Thao Dien, Ho Chi Minh City',
				'address' => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => 'Thao Dien',
					'addressRegion'   => 'Ho Chi Minh City',
					'addressCountry'  => 'VN',
				),
			),
		),
	);

	$faq = sparkvn_extract_faq_pairs( (string) get_post_field( 'post_content', $post_id ) );
	if ( $faq ) {
		$schema_faq = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array(),
		);
		foreach ( $faq as $pair ) {
			$schema_faq['mainEntity'][] = array(
				'@type'          => 'Question',
				'name'           => $pair['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $pair['a'],
				),
			);
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema_faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}
add_action( 'wp_head', 'sparkvn_seo_wp_head', 5 );

/**
 * Pull Q/A pairs after a Frequently Asked Questions heading.
 *
 * @param string $content Post content HTML.
 * @return array<int,array{q:string,a:string}>
 */
function sparkvn_extract_faq_pairs( string $content ): array {
	if ( ! preg_match( '/Frequently Asked Questions/i', $content ) ) {
		return array();
	}
	$parts = preg_split( '/Frequently Asked Questions/i', $content, 2 );
	$tail  = $parts[1] ?? '';
	// Stop at next major closing-ish final thought heading if present.
	$tail  = preg_split( '/<h[23][^>]*>\s*Final Thought/i', $tail )[0] ?? $tail;

	$out = array();
	// Q often wrapped in <strong>…?</strong>; A is the next paragraph (Gutenberg comments may sit between).
	if ( preg_match_all( '/<p[^>]*>\s*(?:<strong>)?\s*([^<]{8,200}\?)\s*(?:<\/strong>)?\s*<\/p>(?:\s|<!--.*?-->)*<p[^>]*>(.*?)<\/p>/is', $tail, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $row ) {
			$q = trim( wp_strip_all_tags( html_entity_decode( $row[1] ) ) );
			$a = trim( wp_strip_all_tags( html_entity_decode( $row[2] ) ) );
			if ( strlen( $q ) < 8 || strlen( $a ) < 20 ) {
				continue;
			}
			$out[] = array( 'q' => $q, 'a' => $a );
			if ( count( $out ) >= 12 ) {
				break;
			}
		}
	}
	return $out;
}

/**
 * Mark FAQ question paragraphs with .faq-q for article design.
 *
 * @param string $content Content.
 * @return string
 */
function sparkvn_content_faq_classes( string $content ): string {
	if ( ! is_singular( 'post' ) || ! preg_match( '/Frequently Asked Questions/i', $content ) ) {
		return $content;
	}
	$parts = preg_split( '/Frequently Asked Questions/i', $content, 2 );
	if ( count( $parts ) < 2 ) {
		return $content;
	}
	$head = $parts[0] . 'Frequently Asked Questions';
	$tail = $parts[1];
	$tail = preg_replace_callback(
		'/<p([^>]*)>\s*<strong>\s*([^<]{8,200}\?)\s*<\/strong>\s*<\/p>/u',
		static function ( $m ) {
			$attrs = $m[1];
			$q     = $m[2];
			if ( preg_match( '/class=("|\')([^"\']*)\1/', $attrs, $cm ) ) {
				$attrs = preg_replace( '/class=("|\')([^"\']*)\1/', 'class=$1' . trim( $cm[2] . ' faq-q' ) . '$1', $attrs, 1 );
			} else {
				$attrs .= ' class="faq-q"';
			}
			return '<p' . $attrs . '><strong>' . $q . '</strong></p>';
		},
		$tail,
		12
	);
	return $head . $tail;
}
add_filter( 'the_content', 'sparkvn_content_faq_classes', 12 );

/**
 * Estimated reading time minutes.
 */
function sparkvn_reading_minutes( int $post_id = 0 ): int {
	$post_id = $post_id ?: get_the_ID();
	$words   = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
	return max( 1, (int) ceil( $words / 220 ) );
}
