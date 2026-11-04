<?php
/**
 * Theme header — markup matches html/shell.js.
 *
 * @package Spark_VN
 */

$sparkvn_solid = sparkvn_has_solid_header();
$sparkvn_logo  = get_template_directory_uri() . '/assets';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'sparkvn' ); ?></a>

<header class="site-header<?php echo $sparkvn_solid ? ' solid' : ''; ?>" id="top">
	<div class="container nav">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Spark English Center home', 'sparkvn' ); ?>">
			<img class="logo-white" src="<?php echo esc_url( $sparkvn_logo . '/logo-white.png' ); ?>" alt="<?php esc_attr_e( 'Spark English Center', 'sparkvn' ); ?>" />
			<img class="logo-purple" src="<?php echo esc_url( $sparkvn_logo . '/logo-purple.png' ); ?>" alt="<?php esc_attr_e( 'Spark English Center', 'sparkvn' ); ?>" />
		</a>
		<nav class="nav-links" aria-label="<?php esc_attr_e( 'Primary', 'sparkvn' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'fallback_cb'    => 'sparkvn_fallback_nav',
					'walker'         => new SparkVN_Walker_Nav(),
					'depth'          => 3,
				)
			);
			?>
		</nav>
		<a href="<?php echo esc_url( home_url( '/assessment/' ) ); ?>" class="btn btn-primary btn-sm nav-cta"><?php esc_html_e( 'Assessment', 'sparkvn' ); ?></a>
		<button class="nav-toggle" id="navToggle" aria-label="<?php esc_attr_e( 'Open menu', 'sparkvn' ); ?>" aria-expanded="false">
			<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
		</button>
	</div>
</header>

<div class="mnav" id="mnav">
	<div class="mnav__scrim" data-close></div>
	<div class="mnav__panel">
		<div class="mnav__top">
			<img src="<?php echo esc_url( $sparkvn_logo . '/logo-purple.png' ); ?>" alt="<?php esc_attr_e( 'Spark English Center', 'sparkvn' ); ?>" />
			<button class="mnav__close" data-close aria-label="<?php esc_attr_e( 'Close menu', 'sparkvn' ); ?>">
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
			</button>
		</div>
		<nav class="mnav__links" aria-label="<?php esc_attr_e( 'Mobile', 'sparkvn' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'fallback_cb'    => false,
					'walker'         => new SparkVN_Walker_Mobile(),
					'depth'          => 3,
				)
			);
			?>
		</nav>
		<a href="<?php echo esc_url( home_url( '/assessment/' ) ); ?>" class="btn btn-primary" data-close><?php esc_html_e( 'Book a Free Assessment', 'sparkvn' ); ?></a>
	</div>
</div>
<?php if ( $sparkvn_solid ) : ?>
	<div class="page-top"></div>
<?php endif; ?>
