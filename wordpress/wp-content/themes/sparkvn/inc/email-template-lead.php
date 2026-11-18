<?php
/**
 * HTML email template for internal lead notifications.
 *
 * @package Spark_VN
 */

/**
 * Render branded lead notification HTML.
 *
 * @param int                   $post_id Lead post ID.
 * @param string                $form    Form key.
 * @param array<string, string> $fields  Non-empty sanitized fields.
 * @return string
 */
function sparkvn_render_lead_email( int $post_id, string $form, array $fields ): string {
	$labels   = sparkvn_lead_field_labels();
	$form_lbl = sparkvn_form_label( $form );
	$when     = wp_date( 'j M Y · H:i' );
	$site     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$logo     = get_template_directory_uri() . '/assets/logo-white.png';
	// Absolute admin URL — REST submits are anonymous so get_edit_post_link() is empty.
	$edit = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

	$rows = '';
	foreach ( $fields as $key => $value ) {
		$label = $labels[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) );
		$rows .= '<tr>'
			. '<td style="padding:10px 14px;border-bottom:1px solid #E9E0D1;font-family:\'Nunito Sans\',Arial,Helvetica,sans-serif;font-size:13px;font-weight:600;color:#6A6470;width:36%;vertical-align:top;">'
			. esc_html( $label )
			. '</td>'
			. '<td style="padding:10px 14px;border-bottom:1px solid #E9E0D1;font-family:Georgia,\'Times New Roman\',serif;font-size:15px;color:#2B2530;vertical-align:top;">'
			. nl2br( esc_html( $value ) )
			. '</td>'
			. '</tr>';
	}

	$edit_block = '';
	if ( $edit ) {
		$edit_block = '<p style="margin:22px 0 0;text-align:center;">'
			. '<a href="' . esc_url( $edit ) . '" style="display:inline-block;padding:12px 22px;background:#5D1C61;color:#ffffff;text-decoration:none;font-family:\'Nunito Sans\',Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;border-radius:999px;">'
			. esc_html__( 'View in admin', 'sparkvn' )
			. '</a></p>';
	}

	return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>' . esc_html( sprintf( '[Spark] %s', $form_lbl ) ) . '</title>
</head>
<body style="margin:0;padding:0;background:#FBF7F0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FBF7F0;padding:28px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#FFFFFF;border:1px solid #E9E0D1;border-radius:14px;overflow:hidden;">
<tr>
<td style="background:#5D1C61;padding:22px 28px;text-align:center;">
<img src="' . esc_url( $logo ) . '" alt="SPARK English Center" width="160" style="display:inline-block;height:auto;border:0;" />
</td>
</tr>
<tr>
<td style="padding:28px 28px 8px;">
<p style="margin:0 0 6px;font-family:\'Nunito Sans\',Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#AF863A;">'
	. esc_html__( 'New lead', 'sparkvn' )
	. '</p>
<p style="margin:0 0 10px;font-family:\'Nunito Sans\',Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;color:#5D1C61;line-height:1.25;">'
	. esc_html( $form_lbl )
	. '</p>
<p style="margin:0 0 18px;font-family:Georgia,\'Times New Roman\',serif;font-size:14px;color:#6A6470;">'
	. esc_html( $when )
	. '</p>
<span style="display:inline-block;padding:5px 12px;background:#F3EAF4;color:#5D1C61;font-family:\'Nunito Sans\',Arial,Helvetica,sans-serif;font-size:12px;font-weight:600;border-radius:999px;border:1px solid #E4D2E6;">'
	. esc_html( $form_lbl )
	. '</span>
</td>
</tr>
<tr>
<td style="padding:18px 28px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E9E0D1;border-radius:10px;overflow:hidden;">
' . $rows . '
</table>
' . $edit_block . '
</td>
</tr>
<tr>
<td style="padding:20px 28px 28px;border-top:1px solid #E9E0D1;">
<p style="margin:0;font-family:\'Nunito Sans\',Arial,Helvetica,sans-serif;font-size:12px;color:#938D9B;text-align:center;">'
	. esc_html( $site )
	. ' · '
	. esc_html__( 'Internal notification — do not reply to parents from this message unless using Reply-To.', 'sparkvn' )
	. '</p>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>';
}
