<?php
/**
 * Spark Email settings (SMTP + From / To / CC).
 *
 * @package Spark_VN
 */

/**
 * Register settings and admin page.
 */
function sparkvn_email_settings_init(): void {
	register_setting(
		'sparkvn_email',
		'sparkvn_mail_enabled',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sparkvn_sanitize_mail_enabled',
			'default'           => '1',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_smtp_host',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_smtp_port',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 587,
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_smtp_encryption',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sparkvn_sanitize_smtp_encryption',
			'default'           => 'tls',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_smtp_user',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_smtp_pass',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sparkvn_sanitize_smtp_pass',
			'default'           => '',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_mail_from_name',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'SPARK English Center',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_mail_from_email',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => '',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_mail_to',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => '',
		)
	);
	register_setting(
		'sparkvn_email',
		'sparkvn_mail_cc',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sparkvn_sanitize_mail_list',
			'default'           => '',
		)
	);

	add_settings_section(
		'sparkvn_email_toggle',
		__( 'Lead notifications', 'sparkvn' ),
		'__return_false',
		'sparkvn_email'
	);
	add_settings_field( 'sparkvn_mail_enabled', __( 'Send emails', 'sparkvn' ), 'sparkvn_email_field_mail_enabled', 'sparkvn_email', 'sparkvn_email_toggle' );

	add_settings_section(
		'sparkvn_email_smtp',
		__( 'SMTP', 'sparkvn' ),
		'__return_false',
		'sparkvn_email'
	);

	add_settings_field( 'sparkvn_smtp_host', __( 'SMTP host', 'sparkvn' ), 'sparkvn_email_field_smtp_host', 'sparkvn_email', 'sparkvn_email_smtp' );
	add_settings_field( 'sparkvn_smtp_port', __( 'SMTP port', 'sparkvn' ), 'sparkvn_email_field_smtp_port', 'sparkvn_email', 'sparkvn_email_smtp' );
	add_settings_field( 'sparkvn_smtp_encryption', __( 'Encryption', 'sparkvn' ), 'sparkvn_email_field_smtp_encryption', 'sparkvn_email', 'sparkvn_email_smtp' );
	add_settings_field( 'sparkvn_smtp_user', __( 'SMTP username', 'sparkvn' ), 'sparkvn_email_field_smtp_user', 'sparkvn_email', 'sparkvn_email_smtp' );
	add_settings_field( 'sparkvn_smtp_pass', __( 'SMTP password', 'sparkvn' ), 'sparkvn_email_field_smtp_pass', 'sparkvn_email', 'sparkvn_email_smtp' );

	add_settings_section(
		'sparkvn_email_identity',
		__( 'Sender & recipients', 'sparkvn' ),
		'__return_false',
		'sparkvn_email'
	);

	add_settings_field( 'sparkvn_mail_from_name', __( 'From name', 'sparkvn' ), 'sparkvn_email_field_from_name', 'sparkvn_email', 'sparkvn_email_identity' );
	add_settings_field( 'sparkvn_mail_from_email', __( 'From email', 'sparkvn' ), 'sparkvn_email_field_from_email', 'sparkvn_email', 'sparkvn_email_identity' );
	add_settings_field( 'sparkvn_mail_to', __( 'To (primary)', 'sparkvn' ), 'sparkvn_email_field_to', 'sparkvn_email', 'sparkvn_email_identity' );
	add_settings_field( 'sparkvn_mail_cc', __( 'CC', 'sparkvn' ), 'sparkvn_email_field_cc', 'sparkvn_email', 'sparkvn_email_identity' );
}
add_action( 'admin_init', 'sparkvn_email_settings_init' );

/**
 * Sanitize on/off for lead emails.
 *
 * @param mixed $value Raw.
 * @return string '1' or '0'.
 */
function sparkvn_sanitize_mail_enabled( $value ): string {
	return ( '1' === (string) $value || 1 === $value || true === $value ) ? '1' : '0';
}

/**
 * Sanitize encryption choice.
 *
 * @param mixed $value Raw.
 * @return string
 */
function sparkvn_sanitize_smtp_encryption( $value ): string {
	$value = sanitize_key( (string) $value );
	return in_array( $value, array( 'tls', 'ssl', 'none' ), true ) ? $value : 'tls';
}

/**
 * Keep existing password when the field is left blank (masked).
 *
 * @param mixed $value Raw.
 * @return string
 */
function sparkvn_sanitize_smtp_pass( $value ): string {
	$value = is_string( $value ) ? $value : '';
	if ( '' === $value || '••••••••' === $value ) {
		return (string) get_option( 'sparkvn_smtp_pass', '' );
	}
	return $value;
}

/**
 * Sanitize comma / newline email list.
 *
 * @param mixed $value Raw.
 * @return string
 */
function sparkvn_sanitize_mail_list( $value ): string {
	$parts = preg_split( '/[\s,;]+/', (string) $value ) ?: array();
	$emails = array();
	foreach ( $parts as $part ) {
		$email = sanitize_email( $part );
		if ( $email && is_email( $email ) ) {
			$emails[] = $email;
		}
	}
	return implode( ', ', array_unique( $emails ) );
}

/**
 * Settings → Spark Email menu.
 */
function sparkvn_email_settings_menu(): void {
	add_options_page(
		__( 'Spark Email', 'sparkvn' ),
		__( 'Spark Email', 'sparkvn' ),
		'manage_options',
		'sparkvn-email',
		'sparkvn_email_settings_page'
	);
}
add_action( 'admin_menu', 'sparkvn_email_settings_menu' );

/**
 * Render settings page.
 */
function sparkvn_email_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = '';
	if ( isset( $_GET['sparkvn_test'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$result = sanitize_key( (string) wp_unslash( $_GET['sparkvn_test'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ok' === $result ) {
			$notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Test email sent. Check the To inbox.', 'sparkvn' ) . '</p></div>';
		} else {
			$msg    = isset( $_GET['sparkvn_msg'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['sparkvn_msg'] ) ) : __( 'Test email failed.', 'sparkvn' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$notice = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Spark Email', 'sparkvn' ); ?></h1>
		<p><?php echo esc_html__( 'SMTP and notification recipients for website form leads. Leave SMTP host empty to use the server default mailer.', 'sparkvn' ); ?></p>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'sparkvn_email' );
			do_settings_sections( 'sparkvn_email' );
			submit_button( __( 'Save settings', 'sparkvn' ) );
			?>
		</form>
		<hr />
		<h2><?php echo esc_html__( 'Send test email', 'sparkvn' ); ?></h2>
		<p><?php echo esc_html__( 'Saves nothing — uses the current saved settings to send a short test message to the To address.', 'sparkvn' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sparkvn_send_test_email" />
			<?php wp_nonce_field( 'sparkvn_send_test_email' ); ?>
			<?php submit_button( __( 'Send test email', 'sparkvn' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Field renderers.
 */
function sparkvn_email_field_mail_enabled(): void {
	$on = '1' === (string) get_option( 'sparkvn_mail_enabled', '1' );
	echo '<input type="hidden" name="sparkvn_mail_enabled" value="0" />';
	printf(
		'<label><input type="checkbox" name="sparkvn_mail_enabled" value="1"%s /> %s</label>',
		checked( $on, true, false ),
		esc_html__( 'Send staff notification emails when a form is submitted', 'sparkvn' )
	);
	echo '<p class="description">' . esc_html__( 'When off, leads are still saved in WordPress — only email sending is paused.', 'sparkvn' ) . '</p>';
}

function sparkvn_email_field_smtp_host(): void {
	printf(
		'<input type="text" class="regular-text" name="sparkvn_smtp_host" value="%s" placeholder="smtp.gmail.com" />',
		esc_attr( (string) get_option( 'sparkvn_smtp_host', '' ) )
	);
}

function sparkvn_email_field_smtp_port(): void {
	$port = (int) get_option( 'sparkvn_smtp_port', 587 );
	printf( '<input type="number" class="small-text" name="sparkvn_smtp_port" value="%d" min="1" max="65535" />', $port );
}

function sparkvn_email_field_smtp_encryption(): void {
	$current = (string) get_option( 'sparkvn_smtp_encryption', 'tls' );
	$opts    = array(
		'tls'  => 'TLS',
		'ssl'  => 'SSL',
		'none' => __( 'None', 'sparkvn' ),
	);
	echo '<select name="sparkvn_smtp_encryption">';
	foreach ( $opts as $value => $label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $value ),
			selected( $current, $value, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

function sparkvn_email_field_smtp_user(): void {
	printf(
		'<input type="text" class="regular-text" name="sparkvn_smtp_user" value="%s" autocomplete="off" />',
		esc_attr( (string) get_option( 'sparkvn_smtp_user', '' ) )
	);
}

function sparkvn_email_field_smtp_pass(): void {
	$has = (string) get_option( 'sparkvn_smtp_pass', '' ) !== '';
	printf(
		'<input type="password" class="regular-text" name="sparkvn_smtp_pass" value="%s" autocomplete="new-password" placeholder="%s" />',
		$has ? '••••••••' : '',
		esc_attr__( 'Leave blank to keep current password', 'sparkvn' )
	);
	if ( $has ) {
		echo '<p class="description">' . esc_html__( 'A password is saved. Enter a new value only to replace it.', 'sparkvn' ) . '</p>';
	}
}

function sparkvn_email_field_from_name(): void {
	$val = (string) get_option( 'sparkvn_mail_from_name', 'SPARK English Center' );
	if ( '' === $val ) {
		$val = 'SPARK English Center';
	}
	printf( '<input type="text" class="regular-text" name="sparkvn_mail_from_name" value="%s" />', esc_attr( $val ) );
}

function sparkvn_email_field_from_email(): void {
	printf(
		'<input type="email" class="regular-text" name="sparkvn_mail_from_email" value="%s" placeholder="noreply@example.com" />',
		esc_attr( (string) get_option( 'sparkvn_mail_from_email', '' ) )
	);
	echo '<p class="description">' . esc_html__( 'Should match the SMTP account identity.', 'sparkvn' ) . '</p>';
}

function sparkvn_email_field_to(): void {
	printf(
		'<input type="email" class="regular-text" name="sparkvn_mail_to" value="%s" placeholder="sparkalcvn@gmail.com" />',
		esc_attr( (string) get_option( 'sparkvn_mail_to', '' ) )
	);
	echo '<p class="description">' . esc_html__( 'Primary inbox for new form leads.', 'sparkvn' ) . '</p>';
}

function sparkvn_email_field_cc(): void {
	printf(
		'<textarea class="large-text" rows="3" name="sparkvn_mail_cc" placeholder="colleague@example.com, other@example.com">%s</textarea>',
		esc_textarea( (string) get_option( 'sparkvn_mail_cc', '' ) )
	);
	echo '<p class="description">' . esc_html__( 'Optional. Comma or newline separated.', 'sparkvn' ) . '</p>';
}

/**
 * Handle test email admin-post.
 */
function sparkvn_handle_send_test_email(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'sparkvn' ) );
	}
	check_admin_referer( 'sparkvn_send_test_email' );

	$to = (string) get_option( 'sparkvn_mail_to', '' );
	if ( ! $to || ! is_email( $to ) ) {
		$redirect = add_query_arg(
			array(
				'page'         => 'sparkvn-email',
				'sparkvn_test' => 'fail',
				'sparkvn_msg'  => __( 'Set a valid To email before sending a test.', 'sparkvn' ),
			),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	$subject = sprintf( '[Spark] %s', __( 'Test email', 'sparkvn' ) );
	$body    = '<p>' . esc_html__( 'This is a test message from SPARK English Center email settings.', 'sparkvn' ) . '</p>';
	$body   .= '<p>' . esc_html( wp_date( 'Y-m-d H:i:s' ) ) . '</p>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$cc      = sparkvn_parse_mail_list( (string) get_option( 'sparkvn_mail_cc', '' ) );
	foreach ( $cc as $addr ) {
		$headers[] = 'Cc: ' . $addr;
	}

	$sent = wp_mail( $to, $subject, $body, $headers );

	$args = array(
		'page'         => 'sparkvn-email',
		'sparkvn_test' => $sent ? 'ok' : 'fail',
	);
	if ( ! $sent ) {
		$args['sparkvn_msg'] = __( 'wp_mail returned false. Check SMTP credentials and server logs.', 'sparkvn' );
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_post_sparkvn_send_test_email', 'sparkvn_handle_send_test_email' );
