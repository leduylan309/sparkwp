<?php
/**
 * Default page template — full-width Spark sections.
 *
 * @package Spark_VN
 */

get_header();
?>
<main id="primary">
	<?php
	while ( have_posts() ) :
		the_post();
		if ( ! has_block( 'sparkvn/hero' ) ) {
			sparkvn_breadcrumb();
		}
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
