<?php
/**
 * Blog grid + topic chips.
 *
 * variation=blog     — archive: chips + search; backend category + pagination
 * variation=featured — Teacher Blogs: CMS-picked posts; chips go to /blog/?category_name=
 * variation=more     — single post “More Posts”: 3 compact cards, no toolbar
 *
 * @var array $attributes Attributes.
 * @package Spark_VN
 */

$title    = $attributes['title'] ?? '';
$cat_slug = $attributes['category'] ?? '';
$per_page = isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 9;
$center   = ! empty( $attributes['center'] );
$mode     = $attributes['variation'] ?? 'blog';
$featured = ( 'featured' === $mode );
$more     = ( 'more' === $mode );
$ids      = array_values( array_filter( array_map( 'absint', (array) ( $attributes['ids'] ?? array() ) ) ) );
$blog_url = sparkvn_blog_url();
$topic    = $featured || $more ? '' : sparkvn_blog_topic();
$cats     = get_categories( array( 'hide_empty' => true ) );
$paged    = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

$args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
);

if ( $featured && $ids ) {
	$args['post__in']        = $ids;
	$args['orderby']         = 'post__in';
	$args['posts_per_page']  = max( 1, count( $ids ) );
	$args['no_found_rows']   = true;
} elseif ( $more ) {
	$args['posts_per_page'] = $per_page > 0 ? $per_page : 3;
	$args['no_found_rows']  = true;
	if ( is_singular( 'post' ) ) {
		$args['post__not_in'] = array( get_the_ID() );
	}
} else {
	$args['posts_per_page'] = $per_page > 0 ? $per_page : 9;
	$args['paged']          = $paged;
	$args['no_found_rows']  = false;
	$filter                 = $topic ?: $cat_slug;
	if ( $filter ) {
		$args['category_name'] = $filter;
	}
}

$q = new WP_Query( $args );

if ( $more ) :
	?>
<section class="section--tight section">
	<?php if ( $title ) : ?>
		<div class="sec-head"><h2><?php echo esc_html( $title ); ?></h2></div>
	<?php endif; ?>
	<div class="post-grid">
		<?php
		if ( $q->have_posts() ) :
			while ( $q->have_posts() ) :
				$q->the_post();
				sparkvn_render_post_card( null, 'compact' );
			endwhile;
			wp_reset_postdata();
		endif;
		?>
	</div>
</section>
	<?php
	return;
endif;

if ( ! $featured ) :
	?>
<section class="section">
	<div class="container">
	<?php
endif;

if ( $title ) :
	?>
		<div class="sec-head<?php echo $center ? ' center' : ''; ?>">
			<h2><?php echo esc_html( $title ); ?></h2>
		</div>
	<?php
endif;
?>
		<div class="blog-toolbar">
			<?php sparkvn_render_blog_search(); ?>
			<div class="blog-filters" id="<?php echo $featured ? 'featuredFilters' : 'blogFilters'; ?>" role="group" aria-label="<?php echo esc_attr__( 'Topics', 'sparkvn' ); ?>">
				<a class="chip chip--filter<?php echo ( $featured || $topic ) ? '' : ' active'; ?>" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'All Topics', 'sparkvn' ); ?></a>
				<?php foreach ( $cats as $cat ) : ?>
					<?php if ( 'uncategorized' === $cat->slug ) { continue; } ?>
					<a class="chip chip--filter<?php echo ( ! $featured && $topic === $cat->slug ) ? ' active' : ''; ?>" href="<?php echo esc_url( sparkvn_blog_url( $cat->slug ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
				<?php endforeach; ?>
			</div>
			<p class="blog-count" id="blogCount"><?php
			$count = (int) ( $q->found_posts ? $q->found_posts : $q->post_count );
			echo esc_html( sprintf( _n( '%s article', '%s articles', $count, 'sparkvn' ), number_format_i18n( $count ) ) );
			if ( ! $featured && $topic ) {
				echo ' ' . esc_html__( 'in this topic', 'sparkvn' );
			}
			?></p>
		</div>
		<div class="post-grid<?php echo $featured ? '' : ' filtered'; ?>">
			<?php
			if ( $q->have_posts() ) :
				while ( $q->have_posts() ) :
					$q->the_post();
					sparkvn_render_post_card();
				endwhile;
				wp_reset_postdata();
			endif;
			?>
		</div>
		<?php if ( ! $q->have_posts() ) : ?>
			<p class="no-results show"><?php echo $featured ? esc_html__( 'No featured articles selected yet.', 'sparkvn' ) : esc_html__( 'No articles match that filter.', 'sparkvn' ); ?></p>
		<?php endif; ?>
		<?php
		if ( ! $featured ) {
			sparkvn_render_pagination( $q, $topic );
		}
		?>
<?php
if ( ! $featured ) :
	?>
	</div>
</section>
	<?php
endif;
