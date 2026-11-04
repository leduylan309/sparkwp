<?php
/**
 * Store prize grids grouped by prize_group.
 *
 * @package Spark_VN
 */

$groups = get_terms(
	array(
		'taxonomy'   => 'prize_group',
		'hide_empty' => false,
		'orderby'    => 'term_id',
	)
);
if ( is_wp_error( $groups ) || ! $groups ) {
	return;
}
?>
<nav class="anchor-nav" aria-label="<?php esc_attr_e( 'Store sections', 'sparkvn' ); ?>">
	<?php foreach ( $groups as $group ) : ?>
		<a href="#<?php echo esc_attr( $group->slug ); ?>"><?php echo esc_html( $group->name ); ?></a>
	<?php endforeach; ?>
</nav>
<?php
foreach ( $groups as $group ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'spark_prize',
			'posts_per_page' => 40,
			'tax_query'      => array(
				array(
					'taxonomy' => 'prize_group',
					'field'    => 'term_id',
					'terms'    => $group->term_id,
				),
			),
		)
	);
	if ( ! $q->have_posts() ) {
		continue;
	}
	echo '<div class="store-h" id="' . esc_attr( $group->slug ) . '"><h2>' . esc_html( $group->name ) . '</h2>';
	if ( $group->description ) {
		echo '<small>' . esc_html( $group->description ) . '</small>';
	}
	echo '</div><div class="store-grid">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$pts  = get_post_meta( get_the_ID(), 'spark_points', true );
		$note = get_post_meta( get_the_ID(), 'spark_note', true );
		$img = sparkvn_media_url( get_the_ID(), 'assets/img/campus-group.jpg' );
		?>
		<article class="prize">
			<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
			<div class="pb">
				<h3><?php the_title(); ?></h3>
				<?php if ( $note ) : ?><small><?php echo esc_html( $note ); ?></small><?php endif; ?>
				<?php if ( $pts ) : ?><div class="pts"><b><?php echo esc_html( $pts ); ?></b></div><?php endif; ?>
			</div>
		</article>
		<?php
	}
	echo '</div>';
	wp_reset_postdata();
}
