<?php
/**
 * Single program.
 *
 * @package Spark_VN
 */

get_header();
?>
<main id="primary">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
