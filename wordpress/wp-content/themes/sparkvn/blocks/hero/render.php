<?php
/**
 * Hero front-end.
 *
 * @var array $attributes Block attributes.
 * @package Spark_VN
 */

$variation = $attributes['variation'] ?? 'page';
$is_home   = 'home' === $variation;
$classes   = 'hero';
if ( ! $is_home ) {
	$classes .= ' hero--page';
}
if ( ! empty( $attributes['center'] ) ) {
	$classes .= ' center';
}
if ( ! empty( $attributes['short'] ) ) {
	$classes .= ' short';
}

$title   = $attributes['title'] ?? '';
$lead    = $attributes['lead'] ?? '';
$lines   = $attributes['lines'] ?? '';
$lines_s = $attributes['linesSmall'] ?? '';
$eyebrow = $attributes['eyebrow'] ?? '';
$aria    = $attributes['ariaLabel'] ?: $title;
$img     = sparkvn_resolve_uri( (string) ( $attributes['imageUrl'] ?? '' ) ) ?: sparkvn_asset_uri( 'assets/img/campus-classroom.jpg' );
$video   = sparkvn_resolve_uri( (string) ( $attributes['videoUrl'] ?? '' ) ) ?: sparkvn_asset_uri( 'assets/video/hero.mp4' );
$poster  = sparkvn_resolve_uri( (string) ( $attributes['posterUrl'] ?? '' ) ) ?: sparkvn_asset_uri( 'assets/img/hero-class.jpg' );
$cta_l   = $attributes['ctaLabel'] ?? '';
$cta_u   = $attributes['ctaUrl'] ?? '';
$chips   = array_filter( array_map( 'trim', preg_split( '/\s*\|\s*|\r\n|\r|\n|\\\\n/', (string) ( $attributes['chips'] ?? '' ) ) ) );
?>
<section class="<?php echo esc_attr( $classes ); ?>" aria-label="<?php echo esc_attr( $aria ); ?>">
	<div class="hero-bg">
		<?php if ( $is_home ) : ?>
			<video class="hero-video" src="<?php echo esc_url( $video ); ?>" poster="<?php echo esc_url( $poster ); ?>" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
		<?php else : ?>
			<img src="<?php echo esc_url( $img ); ?>" alt="" />
		<?php endif; ?>
	</div>
	<div class="hero-overlay"></div>
	<div class="container"><div class="hero-inner"><div class="hero-copy">
		<?php if ( $eyebrow ) : ?><span class="eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( $lead ) : ?><p class="lead<?php echo $is_home ? ' bold' : ''; ?>"><?php echo wp_kses_post( $lead ); ?></p><?php endif; ?>
		<?php if ( $lines ) : ?>
			<p class="lines"><?php echo esc_html( $lines ); ?><?php if ( $lines_s ) : ?><small><?php echo esc_html( $lines_s ); ?></small><?php endif; ?></p>
		<?php endif; ?>
		<?php if ( $chips ) : ?>
			<div class="trust-chips">
				<?php foreach ( $chips as $chip ) : ?>
					<span class="chip"><span class="spark"></span><?php echo esc_html( $chip ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $cta_l && $cta_u ) : ?>
			<div class="hero-cta"><a href="<?php echo esc_url( $cta_u ); ?>" class="btn <?php echo $is_home ? 'btn-primary btn-lg' : 'btn-gold btn-lg'; ?>"><?php echo esc_html( $cta_l ); ?></a></div>
		<?php endif; ?>
	</div></div></div>
</section>
<?php
if ( ! sparkvn_is_block_canvas() && ! is_front_page() && ! is_page( 'contact' ) ) {
	sparkvn_breadcrumb();
}
