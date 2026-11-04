<?php
/**
 * Programs query — .prog-feature list.
 *
 * @package Spark_VN
 */

$q = new WP_Query(
	array(
		'post_type'      => 'spark_program',
		'posts_per_page' => 12,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'post_status'    => 'publish',
	)
);
if ( ! $q->have_posts() ) {
	return;
}
$n = 1;
echo '<div class="prog-feature">';
while ( $q->have_posts() ) {
	$q->the_post();
	$num   = str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
	$tag   = get_post_meta( get_the_ID(), 'spark_menu_order_label', true );
	$ideal = get_the_excerpt();
	$url   = get_permalink();
	$cta   = get_post_meta( get_the_ID(), 'spark_cta_url', true );
	if ( $cta ) {
		$url = $cta;
	}
	$img = sparkvn_media_url( get_the_ID() );
	?>
	<article class="pf">
		<div class="pf-media">
			<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" />
			<span class="pf-num"><?php echo esc_html( $num ); ?></span>
		</div>
		<div class="pf-body">
			<h3><?php the_title(); ?></h3>
			<?php if ( $tag ) : ?><p class="tag"><?php echo esc_html( $tag ); ?></p><?php endif; ?>
			<?php the_excerpt(); ?>
			<?php if ( $ideal ) : ?>
				<div class="pf-ideal"><b><?php esc_html_e( 'Ideal for', 'sparkvn' ); ?></b><span><?php echo esc_html( $ideal ); ?></span></div>
			<?php endif; ?>
			<a class="btn btn-primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Explore the Program', 'sparkvn' ); ?></a>
		</div>
	</article>
	<?php
	++$n;
}
echo '</div>';
wp_reset_postdata();
