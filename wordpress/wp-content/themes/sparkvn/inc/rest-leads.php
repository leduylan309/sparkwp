<?php
/**
 * Public REST endpoint for Spark forms.
 *
 * @package Spark_VN
 */

/**
 * Register lead route.
 */
function sparkvn_register_lead_route(): void {
	register_rest_route(
		'sparkvn/v1',
		'/lead',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'sparkvn_handle_lead',
			'args'                => array(
				'form'  => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
				'email' => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_email',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'sparkvn_register_lead_route' );

/**
 * Store a lead.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function sparkvn_handle_lead( WP_REST_Request $request ) {
	$form  = $request->get_param( 'form' );
	$allowed = array( 'assessment', 'contact', 'resource', 'ask-teacher' );
	if ( ! in_array( $form, $allowed, true ) ) {
		return new WP_Error( 'invalid_form', __( 'Unknown form.', 'sparkvn' ), array( 'status' => 400 ) );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$email = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';
	$name  = '';
	if ( ! empty( $params['name'] ) ) {
		$name = sanitize_text_field( (string) $params['name'] );
	} elseif ( ! empty( $params['fn'] ) || ! empty( $params['ln'] ) ) {
		$name = trim( sanitize_text_field( (string) ( $params['fn'] ?? '' ) ) . ' ' . sanitize_text_field( (string) ( $params['ln'] ?? '' ) ) );
	}

	$needs_email = in_array( $form, array( 'assessment', 'resource', 'ask-teacher' ), true );
	if ( $needs_email && ( ! $email || ! is_email( $email ) ) ) {
		return new WP_Error( 'invalid_email', __( 'A valid email is required.', 'sparkvn' ), array( 'status' => 400 ) );
	}

	$title = sprintf(
		'[%s] %s',
		$form,
		$name ? $name : ( $email ? $email : __( 'New lead', 'sparkvn' ) )
	);

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'spark_lead',
			'post_status' => 'private',
			'post_title'  => $title,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$skip = array( 'form', 'nonce' );
	foreach ( $params as $key => $value ) {
		if ( in_array( $key, $skip, true ) ) {
			continue;
		}
		if ( is_array( $value ) ) {
			$value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
		} else {
			$value = sanitize_textarea_field( (string) $value );
		}
		update_post_meta( $post_id, 'spark_' . sanitize_key( (string) $key ), $value );
	}
	update_post_meta( $post_id, 'spark_form', $form );

	return rest_ensure_response(
		array(
			'ok'      => true,
			'message' => __( 'Thank you. We will be in touch shortly.', 'sparkvn' ),
		)
	);
}
