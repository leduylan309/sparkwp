<?php
/**
 * Resource cards.
 *
 * @package Spark_VN
 */

$q = new WP_Query(
	array(
		'post_type'      => 'spark_resource',
		'posts_per_page' => 12,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);
if ( ! $q->have_posts() ) {
	return;
}
echo '<div class="res-grid" id="guides">';
while ( $q->have_posts() ) {
	$q->the_post();
	$kind = get_post_meta( get_the_ID(), 'spark_kind', true );
	$best = get_post_meta( get_the_ID(), 'spark_best_for', true );
	$file = get_post_meta( get_the_ID(), 'spark_file_url', true );
	?>
	<article class="res-card">
		<?php if ( $kind ) : ?><span class="kind"><?php echo esc_html( $kind ); ?></span><?php endif; ?>
		<h3><?php the_title(); ?></h3>
		<?php the_excerpt(); ?>
		<?php if ( $best ) : ?><p class="best"><b><?php esc_html_e( 'Best for', 'sparkvn' ); ?></b><?php echo esc_html( $best ); ?></p><?php endif; ?>
		<?php if ( $file ) : ?>
			<a class="btn btn-primary btn-sm" href="<?php echo esc_url( $file ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Download', 'sparkvn' ); ?></a>
		<?php endif; ?>
	</article>
	<?php
}
echo '</div>';
wp_reset_postdata();
