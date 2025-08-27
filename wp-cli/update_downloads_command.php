<?php

/**
 * Update download information for programs from external APIs
 */
class Update_Downloads_Command extends WP_CLI_Command {

	/**
	 * Update download information for programs
	 *
	 * ## OPTIONS
	 *
	 * [--program=<slug>]
	 * : Only update programs from specific group
	 *
	 * [--dry-run]
	 * : Preview what would be updated without making changes
	 *
	 * ## EXAMPLES
	 *
	 *     # Update all programs
	 *     wp sc update-downloads
	 *
	 *     # Update only LibreOffice group programs
	 *     wp sc update-downloads --program=libreoffice
	 *
	 *     # Preview changes without updating
	 *     wp sc update-downloads --dry-run
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function __invoke( $args, $assoc_args ) {
		$program_filter = isset( $assoc_args['program'] ) ? $assoc_args['program'] : null;
		$dry_run = isset( $assoc_args['dry-run'] ) && $assoc_args['dry-run'];

		if ( $dry_run ) {
			WP_CLI::log( 'DRY RUN MODE: No changes will be made' );
		}

		$updater = new SC_Downloads_Updater();
		
		// Get programs to show what we're about to update
		$programs = $updater->get_all_programs( $program_filter );
		
		if ( is_wp_error( $programs ) ) {
			WP_CLI::error( $programs->get_error_message() );
		}

		if ( empty( $programs ) ) {
			WP_CLI::warning( 'No programs to update' );
			return;
		}

		$program_count = count( $programs );
		$program_names = implode( ' - ', array_map( function( $p ) {
			return $p['wp'];
		}, $programs ) );

		WP_CLI::log( "About to update {$program_count} programs: {$program_names}" );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Updating programs', $program_count );

		// Track overall results
		$updated_count = 0;
		$failed_count = 0;
		$start_time = microtime( true );

		// Update programs individually with real-time progress
		foreach ( $programs as $program ) {
			$result = $updater->update_program( $program, $dry_run );
			
			// Display immediate feedback for each program
			if ( $result['success'] ) {
				WP_CLI::log( $result['message'] );
				$updated_count++;
			} else {
				WP_CLI::warning( $program['wp'] . ': ' . $result['message'] );
				$failed_count++;
			}
			
			// Update progress bar
			$progress->tick();
		}

		$progress->finish();
		
		// Display overall summary
		$execution_time = round( microtime( true ) - $start_time, 2 );
		
		if ( $dry_run ) {
			WP_CLI::success( "Dry run completed for {$program_count} programs in {$execution_time}s" );
		} else {
			$summary_message = "Updated {$updated_count} of {$program_count} programs in {$execution_time}s";
			if ( $failed_count > 0 ) {
				$summary_message .= " ({$failed_count} failed)";
				WP_CLI::warning( $summary_message );
			} else {
				WP_CLI::success( $summary_message );
			}
		}
	}

}
