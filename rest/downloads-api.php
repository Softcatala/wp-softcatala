<?php

/**
 * REST API endpoints for Downloads Updater
 */

/**
 * Register REST API endpoints for downloads updater
 */
function sc_register_downloads_api() {
	register_rest_route( 'sc/v1', '/update-downloads', array(
		'methods' => array( 'POST', 'GET' ),
		'callback' => 'sc_rest_update_downloads',
		'permission_callback' => 'sc_rest_downloads_permissions',
		'args' => array(
			'program' => array(
				'description' => 'Program group to update (optional)',
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'dry_run' => array(
				'description' => 'Preview changes without making them',
				'type' => 'boolean',
				'default' => false,
			),
		),
	) );

	register_rest_route( 'sc/v1', '/update-downloads/programs', array(
		'methods' => 'GET',
		'callback' => 'sc_rest_get_programs',
		'permission_callback' => 'sc_rest_downloads_permissions',
		'args' => array(
			'program' => array(
				'description' => 'Program group to filter (optional)',
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	) );
}

/**
 * Permission callback for downloads API endpoints
 */
function sc_rest_downloads_permissions( $request ) {
	// Check for API key authentication first (for automated systems)
	$api_key = $request->get_header( 'X-API-Key' ) ?: $request->get_param( 'api_key' );
	if ( $api_key && defined( 'SC_DOWNLOADS_API_KEY' ) && hash_equals( SC_DOWNLOADS_API_KEY, $api_key ) ) {
		return true;
	}

	// Check if user is logged in and has proper permissions
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			'You are not currently logged in.',
			array( 'status' => 401 )
		);
	}

	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error(
			'rest_forbidden',
			'You do not have permission to access this endpoint.',
			array( 'status' => 403 )
		);
	}

	// For session-based authentication, verify nonce for state-changing operations
	$method = $request->get_method();
	if ( in_array( $method, array( 'POST', 'PUT', 'DELETE', 'PATCH' ) ) ) {
		$nonce = $request->get_header( 'X-WP-Nonce' ) ?: $request->get_param( '_wpnonce' );
		
		if ( ! $nonce ) {
			return new WP_Error(
				'rest_missing_nonce',
				'Missing nonce. Please include X-WP-Nonce header or _wpnonce parameter.',
				array( 'status' => 403 )
			);
		}

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_invalid_nonce',
				'Invalid nonce.',
				array( 'status' => 403 )
			);
		}
	}

	return true;
}

/**
 * REST API callback for updating downloads
 */
function sc_rest_update_downloads( $request ) {
	$program_filter = $request->get_param( 'program' );
	$dry_run = $request->get_param( 'dry_run' );

	// Add execution start time
	$start_time = microtime( true );

	try {
		$result = sc_update_all_programs( $program_filter, $dry_run );
		
		// Add API-specific metadata
		$result['api_version'] = '1.0';
		$result['timestamp'] = current_time( 'mysql' );
		$result['request_id'] = wp_generate_uuid4();
		
		if ( $result['success'] ) {
			return new WP_REST_Response( $result, 200 );
		} else {
			return new WP_Error(
				'update_failed',
				$result['message'],
				array( 'status' => 500, 'data' => $result )
			);
		}
	} catch ( Exception $e ) {
		return new WP_Error(
			'update_exception',
			'An error occurred while updating downloads: ' . $e->getMessage(),
			array( 'status' => 500 )
		);
	}
}

/**
 * REST API callback for getting available programs
 */
function sc_rest_get_programs( $request ) {
	$program_filter = $request->get_param( 'program' );

	try {
		$updater = new SC_Downloads_Updater();
		$programs = $updater->get_all_programs( $program_filter );
		
		if ( is_wp_error( $programs ) ) {
			return $programs;
		}

		return new WP_REST_Response( array(
			'success' => true,
			'programs' => $programs,
			'count' => count( $programs ),
			'api_version' => '1.0',
			'timestamp' => current_time( 'mysql' ),
		), 200 );
	} catch ( Exception $e ) {
		return new WP_Error(
			'programs_exception',
			'An error occurred while fetching programs: ' . $e->getMessage(),
			array( 'status' => 500 )
		);
	}
}
