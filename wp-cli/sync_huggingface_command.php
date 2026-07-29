<?php

/**
 * Sync Dades Obertes posts from the Softcatalà organization on Hugging Face
 */
class Sync_HuggingFace_Command extends WP_CLI_Command {

	/**
	 * Sync the dadesobertes post type from Hugging Face datasets
	 *
	 * Hugging Face is the source of truth: datasets are created or updated
	 * as posts, and posts without a matching dataset are trashed.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview what would be changed without making changes
	 *
	 * ## EXAMPLES
	 *
	 *     # Sync all datasets
	 *     wp sc sync-huggingface
	 *
	 *     # Preview changes without updating
	 *     wp sc sync-huggingface --dry-run
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function __invoke( $args, $assoc_args ) {
		$dry_run = isset( $assoc_args['dry-run'] ) && $assoc_args['dry-run'];

		if ( $dry_run ) {
			WP_CLI::log( 'DRY RUN MODE: No changes will be made' );
		}

		$start_time = microtime( true );

		$sync   = new \Softcatala\Sync\HuggingFace();
		$result = $sync->sync( $dry_run );

		foreach ( $result['items'] as $item ) {
			if ( $item['success'] ) {
				WP_CLI::log( $item['message'] );
			} else {
				WP_CLI::warning( $item['message'] );
			}
		}

		$execution_time = round( microtime( true ) - $start_time, 2 );

		if ( $result['success'] ) {
			WP_CLI::success( $result['message'] . " in {$execution_time}s" );
		} else {
			WP_CLI::error( $result['message'] );
		}
	}
}
