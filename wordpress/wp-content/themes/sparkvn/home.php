<?php
/**
 * Blog posts index (page_for_posts) — html/blog.html.
 *
 * @package Spark_VN
 */

get_header();
$hero_img = sparkvn_asset_uri( 'assets/img/real/exclusivetop-kids-writing.jpg' );
$topic    = sparkvn_blog_topic();
$term     = $topic ? get_term_by( 'slug', $topic, 'category' ) : null;
?>
<section class="hero hero--page center short" aria-label="<?php esc_attr_e( 'Spark Blog', 'sparkvn' ); ?>">
	<div class="hero-bg"><img src="<?php echo esc_url( $hero_img ); ?>" alt="" /></div>
	<div class="hero-overlay"></div>
	<div class="container"><div class="hero-inner"><div class="hero-copy">
		<span class="eyebrow"><?php esc_html_e( 'Spark Blog', 'sparkvn' ); ?></span>
		<h1><?php echo $term instanceof \WP_Term ? esc_html( $term->name ) : esc_html__( 'Blog', 'sparkvn' ); ?></h1>
		<p class="lead"><?php esc_html_e( 'Spark English Center Vietnam in Thao Dien helps children ages 3–13 improve reading, spelling, speaking, and school English. Find more information in the blog.', 'sparkvn' ); ?></p>
	</div></div></div>
</section>
<?php sparkvn_breadcrumb(); ?>
<main id="primary">
	<?php echo do_blocks( '<!-- wp:sparkvn/query-posts {"perPage":9} /-->' ); ?>
	<section class="section assess bg-purple cta-band" aria-label="<?php esc_attr_e( 'Book a free assessment', 'sparkvn' ); ?>">
		<div class="container">
			<span class="eyebrow"><?php esc_html_e( "Your child's next step", 'sparkvn' ); ?></span>
			<h2><?php esc_html_e( "Assess your child's English proficiency", 'sparkvn' ); ?></h2>
			<p class="lead"><?php esc_html_e( 'Schedule session with a teacher', 'sparkvn' ); ?></p>
			<div class="cta-row"><a href="<?php echo esc_url( home_url( '/assessment/' ) ); ?>" class="btn btn-gold btn-lg"><?php esc_html_e( 'Book now', 'sparkvn' ); ?></a></div>
		</div>
	</section>
</main>
<?php
get_footer();
