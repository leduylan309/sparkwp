<?php
/**
 * Spark mail transport + lead notifications.
 *
 * @package Spark_VN
 */

/**
 * Parse a stored CC / email list into unique valid addresses.
 *
 * @param string $raw Comma / newline list.
 * @return string[]
 */
function sparkvn_parse_mail_list( string $raw ): array {
	$parts  = preg_split( '/[\s,;]+/', $raw ) ?: array();
	$emails = array();
	foreach ( $parts as $part ) {
		$email = sanitize_email( $part );
		if ( $email && is_email( $email ) ) {
			$emails[] = $email;
		}
	}
	return array_values( array_unique( $emails ) );
}

/**
 * Human label for form variation.
 *
 * @param string $form Form key.
 * @return string
 */
function sparkvn_form_label( string $form ): string {
	$labels = array(
		'assessment'  => __( 'Assessment', 'sparkvn' ),
		'contact'     => __( 'Contact', 'sparkvn' ),
		'resource'    => __( 'Resource', 'sparkvn' ),
		'ask-teacher' => __( 'Ask a Teacher', 'sparkvn' ),
	);
	return $labels[ $form ] ?? ucfirst( $form );
}

/**
 * Field key → human label map (shared across forms).
 *
 * @return array<string, string>
 */
function sparkvn_lead_field_labels(): array {
	return array(
		'fn'       => __( "Student's / First name", 'sparkvn' ),
		'ln'       => __( "Student's / Last name", 'sparkvn' ),
		'name'     => __( 'Name', 'sparkvn' ),
		'email'    => __( 'Email', 'sparkvn' ),
		'phone'    => __( 'Phone / Zalo', 'sparkvn' ),
		'dob'      => __( 'Date of birth', 'sparkvn' ),
		'grade'    => __( 'Grade level', 'sparkvn' ),
		'lang'     => __( 'Primary language', 'sparkvn' ),
		'school'   => __( 'Current school', 'sparkvn' ),
		'av'       => __( 'Availability', 'sparkvn' ),
		'fb'       => __( 'Teacher feedback', 'sparkvn' ),
		'more'     => __( 'Additional notes', 'sparkvn' ),
		'int'      => __( 'Interests', 'sparkvn' ),
		'age'      => __( 'Age of child', 'sparkvn' ),
		'interest' => __( 'Tutoring / educational interest', 'sparkvn' ),
		'addr'     => __( 'Address', 'sparkvn' ),
		'msg'      => __( 'Message', 'sparkvn' ),
	);
}

/**
 * Expand availability checkbox codes for email body.
 *
 * @param string $raw Imploded codes.
 * @return string
 */
function sparkvn_format_availability( string $raw ): string {
	$map = array(
		'wk' => __( 'Week nights', 'sparkvn' ),
		'we' => __( 'Weekends', 'sparkvn' ),
		'fl' => __( 'Flexible', 'sparkvn' ),
		'cu' => __( 'Custom', 'sparkvn' ),
	);
	$parts = array_map( 'trim', explode( ',', $raw ) );
	$out   = array();
	foreach ( $parts as $code ) {
		$out[] = $map[ $code ] ?? $code;
	}
	return implode( ', ', array_filter( $out ) );
}

/**
 * Configure PHPMailer from theme SMTP options when host is set.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
 */
function sparkvn_phpmailer_init( $phpmailer ): void {
	$host = trim( (string) get_option( 'sparkvn_smtp_host', '' ) );
	if ( '' === $host ) {
		return;
	}

	$user = trim( (string) get_option( 'sparkvn_smtp_user', '' ) );
	$pass = (string) get_option( 'sparkvn_smtp_pass', '' );

	$phpmailer->isSMTP();
	$phpmailer->Host = $host;
	$phpmailer->Port = (int) get_option( 'sparkvn_smtp_port', 587 );
	// Auth only when credentials exist (Mailpit / open relays need Auth off).
	$phpmailer->SMTPAuth = ( '' !== $user );
	$phpmailer->Username = $user;
	$phpmailer->Password = $pass;

	$enc = (string) get_option( 'sparkvn_smtp_encryption', 'tls' );
	if ( 'ssl' === $enc ) {
		$phpmailer->SMTPSecure = 'ssl';
	} elseif ( 'tls' === $enc ) {
		$phpmailer->SMTPSecure = 'tls';
	} else {
		$phpmailer->SMTPSecure  = '';
		$phpmailer->SMTPAutoTLS = false;
	}
}
add_action( 'phpmailer_init', 'sparkvn_phpmailer_init' );

/**
 * Override From email when configured.
 *
 * @param string $email Default.
 * @return string
 */
function sparkvn_mail_from( string $email ): string {
	$configured = sanitize_email( (string) get_option( 'sparkvn_mail_from_email', '' ) );
	return ( $configured && is_email( $configured ) ) ? $configured : $email;
}
add_filter( 'wp_mail_from', 'sparkvn_mail_from' );

/**
 * Override From name when configured.
 *
 * @param string $name Default.
 * @return string
 */
function sparkvn_mail_from_name( string $name ): string {
	$configured = trim( (string) get_option( 'sparkvn_mail_from_name', '' ) );
	return '' !== $configured ? $configured : $name;
}
add_filter( 'wp_mail_from_name', 'sparkvn_mail_from_name' );

/**
 * Whether staff lead notification emails are enabled in settings.
 */
function sparkvn_mail_notifications_enabled(): bool {
	return '1' === (string) get_option( 'sparkvn_mail_enabled', '1' );
}

/**
 * Build and send internal lead notification.
 *
 * Soft-fails: never throws; records status on the lead post.
 *
 * @param int                  $post_id Lead post ID.
 * @param string               $form    Form key.
 * @param array<string, mixed> $fields  Submitted fields.
 * @return bool
 */
function sparkvn_send_lead_notification( int $post_id, string $form, array $fields ): bool {
	if ( ! sparkvn_mail_notifications_enabled() ) {
		update_post_meta( $post_id, 'spark_mail_status', 'disabled' );
		update_post_meta( $post_id, 'spark_mail_error', 'Lead email notifications are turned off.' );
		return false;
	}

	$to = sanitize_email( (string) get_option( 'sparkvn_mail_to', '' ) );
	if ( ! $to || ! is_email( $to ) ) {
		update_post_meta( $post_id, 'spark_mail_status', 'skipped' );
		update_post_meta( $post_id, 'spark_mail_error', 'No valid To address configured.' );
		return false;
	}

	$name  = '';
	if ( ! empty( $fields['name'] ) ) {
		$name = sanitize_text_field( (string) $fields['name'] );
	} elseif ( ! empty( $fields['fn'] ) || ! empty( $fields['ln'] ) ) {
		$name = trim(
			sanitize_text_field( (string) ( $fields['fn'] ?? '' ) ) . ' ' .
			sanitize_text_field( (string) ( $fields['ln'] ?? '' ) )
		);
	}

	$email = isset( $fields['email'] ) ? sanitize_email( (string) $fields['email'] ) : '';
	$phone = isset( $fields['phone'] ) ? sanitize_text_field( (string) $fields['phone'] ) : '';

	$who = $name ?: ( $email ?: ( $phone ?: __( 'New lead', 'sparkvn' ) ) );
	$label = sparkvn_form_label( $form );
	$subject = sprintf( '[Spark] %s — %s', $label, $who );

	$normalized = array();
	$skip       = array( 'form', 'nonce' );
	foreach ( $fields as $key => $value ) {
		$key = sanitize_key( (string) $key );
		if ( in_array( $key, $skip, true ) ) {
			continue;
		}
		if ( is_array( $value ) ) {
			$value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
		} else {
			$value = sanitize_textarea_field( (string) $value );
		}
		$value = trim( $value );
		if ( '' === $value ) {
			continue;
		}
		if ( 'av' === $key ) {
			$value = sparkvn_format_availability( $value );
		}
		$normalized[ $key ] = $value;
	}

	$body = sparkvn_render_lead_email( $post_id, $form, $normalized );

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	foreach ( sparkvn_parse_mail_list( (string) get_option( 'sparkvn_mail_cc', '' ) ) as $cc ) {
		if ( strtolower( $cc ) !== strtolower( $to ) ) {
			$headers[] = 'Cc: ' . $cc;
		}
	}
	if ( $email && is_email( $email ) ) {
		$headers[] = 'Reply-To: ' . $email;
	}

	$sent = wp_mail( $to, $subject, $body, $headers );

	if ( $sent ) {
		update_post_meta( $post_id, 'spark_mail_status', 'sent' );
		delete_post_meta( $post_id, 'spark_mail_error' );
	} else {
		update_post_meta( $post_id, 'spark_mail_status', 'failed' );
		update_post_meta( $post_id, 'spark_mail_error', 'wp_mail returned false.' );
		error_log( sprintf( 'SparkVN: lead mail failed for post %d (%s)', $post_id, $form ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	return (bool) $sent;
}
