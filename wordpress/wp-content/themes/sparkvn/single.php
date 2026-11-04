<?php
/**
 * Single post — html/blog-post.html article chrome.
 *
 * @package Spark_VN
 */

get_header();
sparkvn_breadcrumb();
?>
<main id="primary" class="article-wrap">
	<?php
	while ( have_posts() ) :
		the_post();
		$cats = get_the_category();
		$cat  = $cats ? $cats[0]->name : __( 'Blog', 'sparkvn' );
		?>
		<div class="container">
			<header class="article-head">
				<span class="cat-label"><?php echo esc_html( $cat ); ?></span>
				<h1 class="article-title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="standfirst"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<div class="article-meta">
					<div class="byline">
						<div class="who">
							<b><?php echo esc_html( get_the_author() ); ?></b>
							<span><?php echo esc_html( get_the_date() ); ?></span>
						</div>
					</div>
				</div>
			</header>
			<figure class="featured"><img src="<?php echo esc_url( sparkvn_media_url( get_the_ID(), 'assets/img/cdn/IMG_3499-1920w.jpeg' ) ); ?>" alt="" /></figure>
			<article class="prose" style="margin:clamp(40px,4.6vw,64px) auto 0">
				<?php the_content(); ?>
			</article>
			<?php sparkvn_render_post_nav(); ?>
			<?php echo do_blocks( '<!-- wp:sparkvn/query-posts {"variation":"more","title":"More Posts","perPage":3} /-->' ); ?>
		</div>
		<section class="section--tight section">
			<div class="container">
				<div class="related" style="margin-top:0;padding-top:0;border-top:0">
					<p class="rel-head"><?php esc_html_e( 'Related pages', 'sparkvn' ); ?></p>
					<div class="rel-links">
						<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( home_url( '/programs/phonics/' ) ); ?>"><?php esc_html_e( 'Phonics & Literacy', 'sparkvn' ); ?></a>
						<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( home_url( '/assessment/' ) ); ?>"><?php esc_html_e( 'Book a Free Assessment', 'sparkvn' ); ?></a>
						<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( sparkvn_blog_url() ); ?>"><?php esc_html_e( 'Blog', 'sparkvn' ); ?></a>
					</div>
				</div>
			</div>
		</section>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
