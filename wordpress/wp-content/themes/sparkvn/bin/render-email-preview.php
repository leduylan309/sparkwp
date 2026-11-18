<?php
/**
 * Render branded lead email HTML previews for visual QA.
 *
 * Writes:
 *   assets/email-previews/lead-assessment.html
 *   assets/email-previews/lead-contact.html
 *   assets/email-previews/lead-resource.html
 *   assets/email-previews/lead-ask-teacher.html
 *
 * @package Spark_VN
 */

if ( php_sapi_name() !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

require dirname( __DIR__, 4 ) . '/wp-load.php';

$dir = get_template_directory() . '/assets/email-previews';
if ( ! is_dir( $dir ) ) {
	wp_mkdir_p( $dir );
}

$samples = array(
	'assessment'  => array(
		'fn'     => 'Lan',
		'ln'     => 'Le',
		'phone'  => '0901 111 222',
		'email'  => 'parent@example.com',
		'dob'    => '2016-05-01',
		'grade'  => 'Year 4',
		'lang'   => 'Vietnamese',
		'school' => 'BIS Thao Dien',
		'av'     => sparkvn_format_availability( 'wk, we' ),
		'fb'     => 'Teacher notes that blending still needs practice.',
		'more'   => 'Prefers weekend morning slots.',
	),
	'contact'     => array(
		'name'     => 'Parent Contact',
		'phone'    => '0912 345 678',
		'age'      => '8',
		'lang'     => 'English',
		'interest' => 'Phonics & reading',
		'addr'     => 'Thao Dien, Thu Duc',
		'msg'      => 'We would like to learn more about assessment availability this month.',
	),
	'resource'    => array(
		'fn'    => 'Minh',
		'ln'    => 'Nguyen',
		'email' => 'resource@example.com',
		'phone' => '0987 654 321',
		'int'   => 'Phonics, Reading',
	),
	'ask-teacher' => array(
		'name'  => 'Curious Parent',
		'email' => 'ask@example.com',
		'msg'   => 'How often should we practice phonics at home with a Year 2 child?',
	),
);

$index_links = '';
foreach ( $samples as $form => $fields ) {
	$html = sparkvn_render_lead_email( 0, $form, $fields );
	// Preview-friendly: local logo path works when opened via WP theme URL.
	$file = $dir . '/lead-' . $form . '.html';
	file_put_contents( $file, $html );
	echo "Wrote {$file}\n";
	$index_links .= '<li style="margin:0 0 10px;"><a href="lead-' . esc_attr( $form ) . '.html" style="color:#5D1C61;font-family:Nunito Sans,Arial,sans-serif;font-weight:600;">'
		. esc_html( sparkvn_form_label( $form ) )
		. '</a></li>';
}

$index = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" /><title>Spark lead email previews</title></head>
<body style="margin:0;background:#FBF7F0;padding:48px 24px;font-family:Georgia,serif;color:#2B2530;">
<div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #E9E0D1;border-radius:14px;padding:32px;">
<p style="margin:0 0 6px;font-family:Nunito Sans,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#AF863A;">SPARK Email</p>
<h1 style="margin:0 0 16px;font-family:Nunito Sans,Arial,sans-serif;font-size:24px;color:#5D1C61;">Lead notification previews</h1>
<p style="margin:0 0 20px;color:#6A6470;">Internal staff templates for each form variation.</p>
<ul style="padding-left:18px;margin:0;">' . $index_links . '</ul>
</div></body></html>';

file_put_contents( $dir . '/index.html', $index );
echo "Wrote {$dir}/index.html\n";
