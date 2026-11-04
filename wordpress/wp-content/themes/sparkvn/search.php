<?php
/**
 * Search results — same card grid as the blog.
 *
 * @package Spark_VN
 */

get_header();
$hero_img = sparkvn_asset_uri( 'assets/img/campus-reading.jpg' );
$count    = (int) $GLOBALS['wp_query']->found_posts;
?>
<section class="hero hero--page center short" aria-label="<?php esc_attr_e( 'Search', 'sparkvn' ); ?>">
	<div class="hero-bg"><img src="<?php echo esc_url( $hero_img ); ?>" alt="" /></div>
	<div class="hero-overlay"></div>
	<div class="container"><div class="hero-inner"><div class="hero-copy">
		<span class="eyebrow"><?php esc_html_e( 'Spark Blog', 'sparkvn' ); ?></span>
		<h1><?php esc_html_e( 'Search results', 'sparkvn' ); ?></h1>
		<p class="lead"><?php echo esc_html( sprintf( /* translators: %s: search query */ __( 'Results for “%s”', 'sparkvn' ), get_search_query() ) ); ?></p>
	</div></div></div>
</section>
<?php sparkvn_breadcrumb(); ?>
<main id="primary">
	<section class="section">
		<div class="container">
			<div class="blog-toolbar">
				<?php sparkvn_render_blog_search(); ?>
				<p class="blog-count"><?php echo esc_html( sprintf( _n( '%s article', '%s articles', $count, 'sparkvn' ), number_format_i18n( $count ) ) ); ?></p>
			</div>
			<?php if ( have_posts() ) : ?>
				<div class="post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						sparkvn_render_post_card();
					endwhile;
					?>
				</div>
				<?php sparkvn_render_pagination(); ?>
			<?php else : ?>
				<p class="no-results show"><?php esc_html_e( 'No articles match that search.', 'sparkvn' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
