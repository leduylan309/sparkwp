<?php
/**
 * 404 template.
 *
 * @package Spark_VN
 */

get_header();
?>
<main id="primary">
	<section class="section">
		<div class="container">
			<div class="sec-head center">
				<h1><?php esc_html_e( 'Page not found', 'sparkvn' ); ?></h1>
				<p class="lead"><?php esc_html_e( 'The page you are looking for does not exist.', 'sparkvn' ); ?></p>
				<p><a class="btn btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to Home', 'sparkvn' ); ?></a></p>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
