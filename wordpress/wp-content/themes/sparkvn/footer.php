<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Spark_VN
 */

?>

	<footer id="colophon" class="site-footer">
		<div class="site-footer-inner">
			<?php if ( is_active_sidebar( 'sidebar-2' ) ) : ?>
				<div class="footer-widgets">
					<?php dynamic_sidebar( 'sidebar-2' ); ?>
				</div>
			<?php endif; ?>

			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location'  => 'footer',
						'menu_id'         => 'footer-menu',
						'depth'           => 1,
						'container'       => 'nav',
						'container_class' => 'footer-navigation',
					)
				);
			}
			?>

			<div class="site-info">
				<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'sparkvn' ) ); ?>">
					<?php
					/* translators: %s: CMS name, i.e. WordPress. */
					printf( esc_html__( 'Proudly powered by %s', 'sparkvn' ), 'WordPress' );
					?>
				</a>
				<span class="sep"> | </span>
				<?php
				/* translators: 1: Theme name, 2: Theme author. */
				printf( esc_html__( 'Theme: %1$s by %2$s.', 'sparkvn' ), 'Spark VN', '<a href="' . esc_url( home_url( '/' ) ) . '">Spark VN</a>' );
				?>
			</div><!-- .site-info -->
		</div><!-- .site-footer-inner -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
