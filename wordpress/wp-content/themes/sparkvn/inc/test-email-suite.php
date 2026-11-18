<?php
/**
 * Email feature test suite (run via: php wp-content/themes/sparkvn/bin/run-email-tests.php).
 *
 * @package Spark_VN
 */

/**
 * Assert helper.
 *
 * @param bool   $cond Condition.
 * @param string $msg  Message.
 * @param array  $bag  Results bag by ref.
 */
function sparkvn_test_assert( bool $cond, string $msg, array &$bag ): void {
	$bag[] = array(
		'ok'  => $cond,
		'msg' => $msg,
	);
	echo ( $cond ? '[PASS] ' : '[FAIL] ' ) . $msg . PHP_EOL;
}

/**
 * HTTP GET/DELETE helper for Mailpit API.
 *
 * @param string $method Method.
 * @param string $path   Path.
 * @param array  $body   Optional JSON body.
 * @return array{code:int,json:mixed,raw:string}
 */
function sparkvn_mailpit_request( string $method, string $path, array $body = array() ): array {
	$url  = 'http://mailpit:8025' . $path;
	$args = array(
		'method'  => $method,
		'timeout' => 15,
		'headers' => array( 'Accept' => 'application/json' ),
	);
	if ( $body ) {
		$args['headers']['Content-Type'] = 'application/json';
		$args['body']                    = wp_json_encode( $body );
	}
	$res = wp_remote_request( $url, $args );
	if ( is_wp_error( $res ) ) {
		return array(
			'code' => 0,
			'json' => null,
			'raw'  => $res->get_error_message(),
		);
	}
	$raw  = (string) wp_remote_retrieve_body( $res );
	$code = (int) wp_remote_retrieve_response_code( $res );
	$json = json_decode( $raw, true );
	return array(
		'code' => $code,
		'json' => $json,
		'raw'  => $raw,
	);
}

/**
 * Configure theme options for Mailpit SMTP.
 */
function sparkvn_test_configure_mailpit(): void {
	update_option( 'sparkvn_mail_enabled', '1' );
	update_option( 'sparkvn_smtp_host', 'mailpit' );
	update_option( 'sparkvn_smtp_port', 1025 );
	update_option( 'sparkvn_smtp_encryption', 'none' );
	update_option( 'sparkvn_smtp_user', '' );
	update_option( 'sparkvn_smtp_pass', '' );
	update_option( 'sparkvn_mail_from_name', 'SPARK English Center' );
	update_option( 'sparkvn_mail_from_email', 'noreply@spark.test' );
	update_option( 'sparkvn_mail_to', 'staff@spark.test' );
	update_option( 'sparkvn_mail_cc', 'cc1@spark.test, cc2@spark.test' );
}

/**
 * Clear Mailpit inbox.
 */
function sparkvn_test_mailpit_clear(): void {
	sparkvn_mailpit_request( 'DELETE', '/api/v1/messages' );
}

/**
 * Wait for N messages in Mailpit.
 *
 * @param int $min Minimum messages.
 * @param int $tries Attempts.
 * @return array|null Messages payload.
 */
function sparkvn_test_mailpit_wait( int $min = 1, int $tries = 20 ): ?array {
	for ( $i = 0; $i < $tries; $i++ ) {
		$res = sparkvn_mailpit_request( 'GET', '/api/v1/messages' );
		if ( 200 === $res['code'] && is_array( $res['json'] ) && (int) ( $res['json']['total'] ?? 0 ) >= $min ) {
			return $res['json'];
		}
		usleep( 250000 );
	}
	return null;
}

/**
 * Fetch newest message detail.
 *
 * @return array|null
 */
function sparkvn_test_mailpit_latest(): ?array {
	$list = sparkvn_mailpit_request( 'GET', '/api/v1/messages' );
	if ( empty( $list['json']['messages'][0]['ID'] ) ) {
		return null;
	}
	$id  = $list['json']['messages'][0]['ID'];
	$res = sparkvn_mailpit_request( 'GET', '/api/v1/message/' . rawurlencode( $id ) );
	return is_array( $res['json'] ) ? $res['json'] : null;
}

/**
 * Submit lead via handler.
 *
 * @param array $payload Fields including form.
 * @return array{ok:bool,post_id:int,status:string,error:string}
 */
function sparkvn_test_submit_lead( array $payload ): array {
	$req = new WP_REST_Request( 'POST', '/sparkvn/v1/lead' );
	$req->set_header( 'Content-Type', 'application/json' );
	$req->set_body( wp_json_encode( $payload ) );
	$res  = sparkvn_handle_lead( $req );
	$data = $res instanceof WP_REST_Response ? $res->get_data() : array();
	$ok   = ! empty( $data['ok'] );

	$q = new WP_Query(
		array(
			'post_type'      => 'spark_lead',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'private',
		)
	);
	$post_id = $q->posts ? (int) $q->posts[0]->ID : 0;

	return array(
		'ok'      => $ok,
		'post_id' => $post_id,
		'status'  => $post_id ? (string) get_post_meta( $post_id, 'spark_mail_status', true ) : '',
		'error'   => $post_id ? (string) get_post_meta( $post_id, 'spark_mail_error', true ) : '',
	);
}

/**
 * Run full suite. Returns exit code 0/1.
 *
 * @return int
 */
function sparkvn_run_email_test_suite(): int {
	$bag = array();

	echo "=== Spark Email Test Suite ===\n";

	// --- Unit: helpers ---
	sparkvn_test_assert(
		array( 'a@x.com', 'b@y.com' ) === sparkvn_parse_mail_list( "a@x.com, b@y.com\n b@y.com ; invalid" ),
		'parse_mail_list dedupes and validates',
		$bag
	);
	sparkvn_test_assert( 'Assessment' === sparkvn_form_label( 'assessment' ), 'form_label assessment', $bag );
	sparkvn_test_assert( 'Ask a Teacher' === sparkvn_form_label( 'ask-teacher' ), 'form_label ask-teacher', $bag );
	sparkvn_test_assert(
		str_contains( sparkvn_format_availability( 'wk, we' ), 'Week nights' ),
		'format_availability expands codes',
		$bag
	);

	// --- Mailpit reachability ---
	$health = sparkvn_mailpit_request( 'GET', '/api/v1/info' );
	sparkvn_test_assert( 200 === $health['code'], 'Mailpit API reachable from WordPress', $bag );
	if ( 200 !== $health['code'] ) {
		echo "Mailpit unavailable ({$health['raw']}). Start with: docker compose up -d mailpit\n";
		return 1;
	}

	sparkvn_test_configure_mailpit();
	sparkvn_test_mailpit_clear();

	// --- Disabled toggle ---
	update_option( 'sparkvn_mail_enabled', '0' );
	sparkvn_test_mailpit_clear();
	$r = sparkvn_test_submit_lead(
		array(
			'form'  => 'ask-teacher',
			'name'  => 'Disabled Case',
			'email' => 'off@example.com',
			'msg'   => 'Should not mail when disabled',
		)
	);
	sparkvn_test_assert( $r['ok'], 'Lead saved when mail disabled', $bag );
	sparkvn_test_assert( 'disabled' === $r['status'], 'Mail status disabled when toggle off', $bag );
	$empty = sparkvn_test_mailpit_wait( 1, 4 );
	sparkvn_test_assert( null === $empty, 'No SMTP message when disabled', $bag );
	update_option( 'sparkvn_mail_enabled', '1' );

	// --- Skip when To missing ---
	$saved_to = get_option( 'sparkvn_mail_to' );
	update_option( 'sparkvn_mail_to', '' );
	sparkvn_test_mailpit_clear();
	$r = sparkvn_test_submit_lead(
		array(
			'form'  => 'ask-teacher',
			'name'  => 'Skip Case',
			'email' => 'skip@example.com',
			'msg'   => 'Should skip mail',
		)
	);
	sparkvn_test_assert( $r['ok'], 'Lead saved when To empty', $bag );
	sparkvn_test_assert( 'skipped' === $r['status'], 'Mail status skipped without To', $bag );
	$empty = sparkvn_test_mailpit_wait( 1, 4 );
	sparkvn_test_assert( null === $empty, 'No SMTP message when skipped', $bag );
	update_option( 'sparkvn_mail_to', $saved_to );

	// --- Test email path (wp_mail directly like admin test) ---
	sparkvn_test_mailpit_clear();
	$headers = array( 'Content-Type: text/html; charset=UTF-8', 'Cc: cc1@spark.test' );
	$sent    = wp_mail( 'staff@spark.test', '[Spark] Test email', '<p>Test from suite</p>', $headers );
	sparkvn_test_assert( $sent, 'wp_mail test send via Mailpit SMTP', $bag );
	$msg = sparkvn_test_mailpit_wait( 1 );
	sparkvn_test_assert( null !== $msg, 'Test email landed in Mailpit', $bag );
	$detail = sparkvn_test_mailpit_latest();
	sparkvn_test_assert( null !== $detail && str_contains( (string) ( $detail['Subject'] ?? '' ), 'Test email' ), 'Test subject correct', $bag );

	// --- Assessment ---
	sparkvn_test_mailpit_clear();
	$r = sparkvn_test_submit_lead(
		array(
			'form'   => 'assessment',
			'fn'     => 'Lan',
			'ln'     => 'Le',
			'phone'  => '0901111222',
			'email'  => 'parent.assessment@example.com',
			'dob'    => '2016-05-01',
			'grade'  => 'Year 4',
			'lang'   => 'Vietnamese',
			'school' => 'BIS',
			'av'     => array( 'wk', 'we' ),
			'fb'     => 'Needs phonics support',
			'more'   => 'Prefers weekends',
		)
	);
	sparkvn_test_assert( $r['ok'], 'Assessment REST ok', $bag );
	sparkvn_test_assert( 'sent' === $r['status'], 'Assessment mail status sent', $bag );
	sparkvn_test_assert( null !== sparkvn_test_mailpit_wait( 1 ), 'Assessment email in Mailpit', $bag );
	$detail = sparkvn_test_mailpit_latest();
	$html   = (string) ( $detail['HTML'] ?? '' );
	$subj   = (string) ( $detail['Subject'] ?? '' );
	$to     = wp_list_pluck( $detail['To'] ?? array(), 'Address' );
	$cc     = wp_list_pluck( $detail['Cc'] ?? array(), 'Address' );
	$from   = (string) ( $detail['From']['Address'] ?? '' );
	sparkvn_test_assert( str_contains( $subj, 'Assessment' ) && str_contains( $subj, 'Lan' ), 'Assessment subject has form + name', $bag );
	sparkvn_test_assert( in_array( 'staff@spark.test', $to, true ), 'Assessment To = staff', $bag );
	sparkvn_test_assert( in_array( 'cc1@spark.test', $cc, true ) && in_array( 'cc2@spark.test', $cc, true ), 'Assessment CC both', $bag );
	sparkvn_test_assert( 'noreply@spark.test' === $from, 'Assessment From address', $bag );
	sparkvn_test_assert( str_contains( $html, '#5D1C61' ) && str_contains( $html, 'New lead' ), 'Assessment HTML brand tokens', $bag );
	sparkvn_test_assert( str_contains( $html, 'Week nights' ), 'Assessment availability expanded', $bag );
	sparkvn_test_assert( str_contains( $html, 'parent.assessment@example.com' ), 'Assessment email field in body', $bag );
	$reply = array();
	foreach ( $detail['ReplyTo'] ?? array() as $rt ) {
		$reply[] = $rt['Address'] ?? '';
	}
	sparkvn_test_assert( in_array( 'parent.assessment@example.com', $reply, true ), 'Assessment Reply-To = parent', $bag );

	// --- Contact (no email → no Reply-To) ---
	sparkvn_test_mailpit_clear();
	$r = sparkvn_test_submit_lead(
		array(
			'form'     => 'contact',
			'name'     => 'Parent Contact',
			'phone'    => '0912345678',
			'age'      => '8',
			'lang'     => 'English',
			'interest' => 'Phonics',
			'addr'     => 'Thao Dien',
			'msg'      => 'Please call me',
		)
	);
	sparkvn_test_assert( $r['ok'] && 'sent' === $r['status'], 'Contact lead mailed', $bag );
	$detail = sparkvn_test_mailpit_latest();
	sparkvn_test_assert( null !== $detail && str_contains( (string) ( $detail['Subject'] ?? '' ), 'Contact' ), 'Contact subject', $bag );
	sparkvn_test_assert( empty( $detail['ReplyTo'] ), 'Contact has no Reply-To (no email field)', $bag );
	sparkvn_test_assert( str_contains( (string) ( $detail['HTML'] ?? '' ), 'Please call me' ), 'Contact message in HTML', $bag );

	// --- Resource ---
	sparkvn_test_mailpit_clear();
	$r = sparkvn_test_submit_lead(
		array(
			'form'  => 'resource',
			'fn'    => 'Minh',
			'ln'    => 'Nguyen',
			'email' => 'resource@example.com',
			'phone' => '0987654321',
			'int'   => array( 'Phonics', 'Reading' ),
		)
	);
	sparkvn_test_assert( $r['ok'] && 'sent' === $r['status'], 'Resource lead mailed', $bag );
	$detail = sparkvn_test_mailpit_latest();
	sparkvn_test_assert( str_contains( (string) ( $detail['Subject'] ?? '' ), 'Resource' ), 'Resource subject', $bag );
	sparkvn_test_assert( str_contains( (string) ( $detail['HTML'] ?? '' ), 'Phonics' ), 'Resource interests in HTML', $bag );

	// --- Ask a Teacher ---
	sparkvn_test_mailpit_clear();
	$r = sparkvn_test_submit_lead(
		array(
			'form'  => 'ask-teacher',
			'name'  => 'Curious Parent',
			'email' => 'ask@example.com',
			'msg'   => 'How often should we practice phonics at home?',
		)
	);
	sparkvn_test_assert( $r['ok'] && 'sent' === $r['status'], 'Ask-teacher lead mailed', $bag );
	$detail = sparkvn_test_mailpit_latest();
	sparkvn_test_assert( str_contains( (string) ( $detail['Subject'] ?? '' ), 'Ask a Teacher' ), 'Ask-teacher subject', $bag );
	$reply = array();
	foreach ( $detail['ReplyTo'] ?? array() as $rt ) {
		$reply[] = $rt['Address'] ?? '';
	}
	sparkvn_test_assert( in_array( 'ask@example.com', $reply, true ), 'Ask-teacher Reply-To', $bag );

	// --- Soft-fail: bad SMTP host, REST still ok ---
	update_option( 'sparkvn_smtp_host', 'smtp.invalid.local' );
	update_option( 'sparkvn_smtp_port', 2525 );
	$r = sparkvn_test_submit_lead(
		array(
			'form'  => 'ask-teacher',
			'name'  => 'Fail Soft',
			'email' => 'fail@example.com',
			'msg'   => 'SMTP should fail',
		)
	);
	sparkvn_test_assert( $r['ok'], 'REST ok when SMTP fails', $bag );
	sparkvn_test_assert( 'failed' === $r['status'], 'Mail status failed on bad SMTP', $bag );

	// Restore Mailpit config for ongoing local use.
	sparkvn_test_configure_mailpit();

	$pass = count( array_filter( $bag, static fn( $x ) => $x['ok'] ) );
	$fail = count( $bag ) - $pass;
	echo "=== Results: {$pass} passed, {$fail} failed (total " . count( $bag ) . ") ===\n";
	return $fail > 0 ? 1 : 0;
}
