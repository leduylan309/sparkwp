<?php
/**
 * Spark card front-end.
 *
 * Hub cards follow html/:
 * - image  → .hub-card.has-img (About, Parent Center)
 * - text   → .hub-card (Teachers Corner, Resource Library)
 * - row    → .hub-card.hub-card--row (Exclusive)
 *
 * @var array $attributes Attributes.
 * @package Spark_VN
 */

$variation = $attributes['variation'] ?? 'hub';
$title     = $attributes['title'] ?? '';
$excerpt   = $attributes['excerpt'] ?? '';
$url       = $attributes['url'] ?? '';
$img       = sparkvn_resolve_uri( (string) ( $attributes['imageUrl'] ?? '' ) );
$go        = $attributes['goLabel'] ?: __( 'Explore', 'sparkvn' );
$role      = $attributes['role'] ?? '';
$bio       = $attributes['bio'] ?? '';
$cite      = $attributes['cite'] ?? '';
$source    = $attributes['source'] ?? '';
$date      = $attributes['date'] ?? '';

$needs_fallback = in_array( $variation, array( 'advantage', 'team', 'testimonial', 'program' ), true );
if ( ! $img && $needs_fallback ) {
	$img = sparkvn_asset_uri( 'assets/img/campus-classroom.jpg' );
}

if ( 'advantage' === $variation ) {
	?>
	<article class="adv-card sparkvn-card">
		<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php endif; ?>
		<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
		<div class="ab">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( $excerpt ) : ?><p><?php echo wp_kses_post( $excerpt ); ?></p><?php endif; ?>
		</div>
		<?php if ( $url ) : ?></a><?php endif; ?>
	</article>
	<?php
	return;
}

if ( 'team' === $variation ) {
	?>
	<article class="team-card sparkvn-card">
		<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
		<div class="tc-body">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( $role ) : ?><p class="role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
			<?php if ( $excerpt ) : ?><p><?php echo wp_kses_post( $excerpt ); ?></p><?php endif; ?>
			<?php if ( $bio ) : ?>
				<details>
					<summary><span class="more"><?php esc_html_e( 'Read full bio', 'sparkvn' ); ?></span><span class="less"><?php esc_html_e( 'Show less', 'sparkvn' ); ?></span></summary>
					<?php echo wp_kses_post( $bio ); ?>
				</details>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return;
}

if ( 'testimonial' === $variation ) {
	?>
	<figure class="testi sparkvn-card">
		<?php if ( $source ) : ?><span class="gsrc"><?php echo esc_html( $source ); ?></span><?php endif; ?>
		<blockquote><?php echo wp_kses_post( $excerpt ); ?></blockquote>
		<figcaption class="who">
			<span class="avatar"><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" /></span>
			<span><b><?php echo esc_html( $cite ?: $title ); ?></b><?php if ( $date ) : ?><span><?php echo esc_html( $date ); ?></span><?php endif; ?></span>
		</figcaption>
	</figure>
	<?php
	return;
}

if ( 'program' === $variation ) {
	?>
	<article class="pf sparkvn-card">
		<div class="pf-media">
			<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" />
		</div>
		<div class="pf-body">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( $excerpt ) : ?><?php echo wp_kses_post( $excerpt ); ?><?php endif; ?>
			<?php if ( $url ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $go ); ?></a>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return;
}

$tag  = $url ? 'a' : 'div';
$href = $url ? ' href="' . esc_url( $url ) . '"' : '';
$go_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

if ( $img ) {
	$classes = 'hub-card has-img sparkvn-card';
} elseif ( $excerpt ) {
	$classes = 'hub-card sparkvn-card';
} else {
	$classes = 'hub-card hub-card--row sparkvn-card';
}
?>
<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="<?php echo esc_attr( $classes ); ?>"<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $img ) : ?>
		<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
		<div class="hc-body">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( $excerpt ) : ?><p><?php echo wp_kses_post( $excerpt ); ?></p><?php endif; ?>
			<span class="go"><?php echo esc_html( $go ); ?><?php echo $go_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	<?php else : ?>
		<h3><?php echo esc_html( $title ); ?></h3>
		<?php if ( $excerpt ) : ?><p><?php echo wp_kses_post( $excerpt ); ?></p><?php endif; ?>
		<span class="go"><?php echo esc_html( $go ); ?><?php echo $go_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<?php endif; ?>
</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
