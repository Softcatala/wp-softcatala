<?php

/**
 * Core class for updating program download information
 * This class contains all the business logic without WP-CLI dependencies
 */
class SC_Downloads_Updater {

	/**
	 * Base API URL
	 *
	 * @var string
	 */
	private $base_url = 'https://api.softcatala.org/rebost-releases/v1';

	/**
	 * Seconds the API is given to answer
	 *
	 * The API checks every download it lists before answering. The updater
	 * can wait for it because it only runs from cron, WP-CLI or the REST API,
	 * never while a page is being served.
	 */
	const TIMEOUT = 30;

	/**
	 * Get all programs from the API
	 *
	 * @param string|null $program_filter Optional program group filter
	 * @return array|WP_Error Array of programs or WP_Error on failure
	 */
	public function get_all_programs( $program_filter = null ) {
		$all_programs = $this->fetch( $this->base_url );

		if ( is_wp_error( $all_programs ) ) {
			return new WP_Error(
				'api_error',
				"Failed to fetch programs configuration from API: {$this->base_url} ({$all_programs->get_error_message()})"
			);
		}

		if ( empty( $all_programs ) ) {
			return new WP_Error( 'no_programs', 'No programs found in API response' );
		}

		// Filter programs if specific program group requested
		if ( $program_filter ) {
			$all_programs = array_filter( $all_programs, function( $p ) use ( $program_filter ) {
				return isset( $p['group'] ) && $p['group'] == $program_filter;
			});
		}

		return $all_programs;
	}

	/**
	 * Update download information for a single program
	 *
	 * @param array $program Program configuration with 'wp' and 'api' keys
	 * @param bool $dry_run Whether to actually make changes
	 * @return array Result array with 'success', 'message', and optional 'data'
	 */
	public function update_program( $program, $dry_run = false ) {
		if ( ! isset( $program['wp'] ) || ! isset( $program['api'] ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid program configuration: missing wp or api key'
			);
		}

		$slug = $program['wp'];
		$api_url = $this->base_url . '/' . $program['api'];

		// Find the WordPress post by slug
		$post = get_page_by_path( $slug, OBJECT, 'programa' );
		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => "Program post not found: {$slug}"
			);
		}

		// Fetch data from API
		$versions = $this->fetch( $api_url );
		if ( is_wp_error( $versions ) ) {
			return array(
				'success' => false,
				'message' => "Failed to fetch data for {$slug} from {$api_url} ({$versions->get_error_message()})",
			);
		}

		if ( empty( $versions ) ) {
			return array(
				'success' => true,
				'message' => "No versions to update for {$slug}",
				'data' => array()
			);
		}

		$version_count = count( $versions );
		$version_names = implode( ' - ', array_map( function( $v ) {
			return ( $v['download_version'] ?? 'unknown' ) . '-' . 
				   ( $v['download_os'] ?? 'unknown' ) . '-' . 
				   ( $v['arquitectura'] ?? 'unknown' );
		}, $versions ) );

		if ( $dry_run ) {
			return array(
				'success' => true,
				'message' => "[DRY RUN] Would update {$version_count} versions for {$slug}: {$version_names}",
				'data' => array(
					'post_id' => $post->ID,
					'versions' => $versions,
					'version_count' => $version_count
				)
			);
		}

		// Update the ACF field
		$field_key = $this->acf_get_field_key( 'baixada', $post->ID );
		if ( $field_key ) {
			update_field( $field_key, $versions, $post->ID );
			return array(
                'success' => true,
                'message' => "Successfully updated {$version_count} versions for {$slug}: {$version_names}",
                'data' => array(
                    'post_id' => $post->ID,
                    'versions' => $versions,
                    'version_count' => $version_count
                )
            );
		} else {
			return array(
				'success' => false,
				'message' => "Could not find ACF field key for 'baixada' in {$slug}"
			);
		}
	}

	/**
	 * Update all programs or filtered programs
	 *
	 * @param string|null $program_filter Optional program group filter
	 * @param bool $dry_run Whether to actually make changes
	 * @return array Result array with overall statistics and individual results
	 */
	public function update_all_programs( $program_filter = null, $dry_run = false ) {
		$start_time = microtime( true );
		$programs = $this->get_all_programs( $program_filter );

		if ( is_wp_error( $programs ) ) {
			return array(
				'success' => false,
				'message' => $programs->get_error_message(),
				'programs_processed' => 0,
				'programs_updated' => 0,
				'programs_failed' => 0,
				'results' => array()
			);
		}

		if ( empty( $programs ) ) {
			return array(
				'success' => true,
				'message' => 'No programs to update',
				'programs_processed' => 0,
				'programs_updated' => 0,
				'programs_failed' => 0,
				'results' => array()
			);
		}

		$results = array();
		$updated_count = 0;
		$failed_count = 0;

		foreach ( $programs as $program ) {
			$result = $this->update_program( $program, $dry_run );
			$results[] = array(
				'program' => $program['wp'],
				'result' => $result
			);

			if ( $result['success'] ) {
				$updated_count++;
			} else {
				$failed_count++;
			}
		}

		$execution_time = round( microtime( true ) - $start_time, 2 );
		$program_count = count( $programs );

		return array(
			'success' => true,
			'message' => $dry_run 
				? "Dry run completed for {$program_count} programs in {$execution_time}s"
				: "Updated {$updated_count} of {$program_count} programs in {$execution_time}s ({$failed_count} failed)",
			'programs_processed' => $program_count,
			'programs_updated' => $updated_count,
			'programs_failed' => $failed_count,
			'execution_time' => $execution_time,
			'results' => $results
		);
	}

	/**
	 * Fetches a JSON list from the API
	 *
	 * @param string $url URL of the API to fetch.
	 * @return array|WP_Error The list, or WP_Error when the API cannot be reached,
	 *                        answers with anything but a 2xx status, or with a
	 *                        body that is not a JSON list
	 */
	private function fetch( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		// An error page has a body too, and it must not be taken for data.
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error( 'api_status', "HTTP {$status}" );
		}

		$list = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $list ) ) {
			return new WP_Error( 'api_body', 'the answer is not a JSON list' );
		}

		return $list;
	}

	/**
	 * Gets the ACF field key from a field name and post ID
	 *
	 * @param string $field_name The field name to look for
	 * @param int $post_id The post ID to check field groups for
	 * @return string|false The field key or false if not found
	 */
	private function acf_get_field_key( $field_name, $post_id ) {
		global $wpdb;

		$acf_fields = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID,post_parent,post_name FROM $wpdb->posts WHERE post_excerpt=%s AND post_type=%s",
			$field_name,
			'acf-field'
		) );

		// Get all fields with that name
		switch ( count( $acf_fields ) ) {
			case 0: // No such field
				return false;
			case 1: // Just one result
				return $acf_fields[0]->post_name;
		}

		// Result is ambiguous - need to check field groups
		// Get IDs of all field groups for this post
		$field_groups_ids = array();
		$field_groups = acf_get_field_groups( array(
			'post_id' => $post_id,
		) );

		foreach ( $field_groups as $field_group ) {
			$field_groups_ids[] = $field_group['ID'];
		}

		// Check if field is part of one of the field groups
		// Return the first one found
		foreach ( $acf_fields as $acf_field ) {
			if ( in_array( $acf_field->post_parent, $field_groups_ids ) ) {
				return $acf_field->post_name;
			}
		}

		return false;
	}
}
