<?php
/**
 * Theme footer — markup matches html/shell.js.
 *
 * @package Spark_VN
 */

$sparkvn_logo = get_template_directory_uri() . '/assets/logo-white.png';
?>
<footer class="site-footer">
	<div class="container">
		<div class="footer-top">
			<div>
				<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php echo esc_url( $sparkvn_logo ); ?>" alt="<?php esc_attr_e( 'Spark English Center', 'sparkvn' ); ?>" />
				</a>
				<p class="footer-about"><?php echo wp_kses( __( '204B7/12 Nguyễn Văn Hưởng,<br />Phường An Khánh, TP.HCM.', 'sparkvn' ), array( 'br' => array() ) ); ?></p>
			</div>
			<div class="footer-col">
				<h4><?php esc_html_e( 'Spark', 'sparkvn' ); ?></h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'sparkvn' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/assessment/' ) ); ?>"><?php esc_html_e( 'Assessment', 'sparkvn' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/our-story/' ) ); ?>"><?php esc_html_e( 'Our Story', 'sparkvn' ); ?></a></li>
				</ul>
			</div>
			<div class="footer-col">
				<h4><?php esc_html_e( 'More', 'sparkvn' ); ?></h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/exclusive/' ) ); ?>"><?php esc_html_e( 'Exclusive', 'sparkvn' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'sparkvn' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'sparkvn' ); ?></a></li>
				</ul>
			</div>
			<div class="footer-col">
				<h4><?php esc_html_e( 'Get in touch', 'sparkvn' ); ?></h4>
				<ul>
					<li><a href="tel:0398143487"><?php esc_html_e( 'Phone: 0398143487', 'sparkvn' ); ?></a></li>
					<li><a href="mailto:hello@sparkvn.com"><?php esc_html_e( 'Email: hello@sparkvn.com', 'sparkvn' ); ?></a></li>
				</ul>
			</div>
		</div>
		<div class="footer-bottom">
			<span>© <span id="yr"><?php echo esc_html( gmdate( 'Y' ) ); ?></span> <?php esc_html_e( 'Spark English Center. All rights reserved.', 'sparkvn' ); ?></span>
			<div class="footer-social">
				<a href="https://www.facebook.com/people/Spark-English-Center-Vietnam/61582302060780/" aria-label="<?php esc_attr_e( 'Facebook', 'sparkvn' ); ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2 0-3 1.4-3 3.3V11H8v3h3v7h3v-7h2.5l.5-3H14V9.6c0-.4.3-.6.7-.6z"/></svg></a>
				<a href="https://tiktok.com/@sparkvietnam" aria-label="<?php esc_attr_e( 'TikTok', 'sparkvn' ); ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 3c.3 2.3 1.7 3.8 4 4v3c-1.5 0-2.9-.5-4-1.3V15a6 6 0 1 1-6-6c.3 0 .7 0 1 .1v3.1a3 3 0 1 0 2 2.8V3h3z"/></svg></a>
				<a href="https://youtube.com/@SparkVN" aria-label="<?php esc_attr_e( 'YouTube', 'sparkvn' ); ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12s0-3-.4-4.4a2.6 2.6 0 0 0-1.8-1.8C18.4 5.4 12 5.4 12 5.4s-6.4 0-7.8.4A2.6 2.6 0 0 0 2.4 7.6C2 9 2 12 2 12s0 3 .4 4.4a2.6 2.6 0 0 0 1.8 1.8c1.4.4 7.8.4 7.8.4s6.4 0 7.8-.4a2.6 2.6 0 0 0 1.8-1.8C22 15 22 12 22 12zm-12 2.8V9.2L15 12z"/></svg></a>
			</div>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
