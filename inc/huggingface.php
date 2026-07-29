<?php

/**
 * Hugging Face sync cron functions.
 *
 * Schedules the daily job that mirrors the datasets of the Softcatalà
 * organization on Hugging Face into the 'dadesobertes' post type.
 * See Softcatala\Sync\HuggingFace for the sync logic.
 */

/**
 * Run the Hugging Face datasets sync — WordPress-friendly wrapper.
 * Can be called from hooks, cron, admin interfaces, etc.
 *
 * @param bool $dry_run Whether to actually make changes.
 * @return array Result array with statistics and details.
 */
function sc_sync_huggingface( $dry_run = false ) {
	$sync = new \Softcatala\Sync\HuggingFace();
	return $sync->sync( $dry_run );
}

/**
 * Hook for WordPress cron — sync datasets from Hugging Face.
 */
function sc_cron_sync_huggingface() {
	$result = sc_sync_huggingface();

	if ( $result['success'] ) {
		error_log( 'SC Hugging Face Sync: ' . $result['message'] );
	} else {
		error_log( 'SC Hugging Face Sync Error: ' . $result['message'] );
	}
}
add_action( 'sc_huggingface_sync_cron', 'sc_cron_sync_huggingface' );
add_action( 'init', 'sc_schedule_huggingface_sync' );

/**
 * Schedule the Hugging Face sync cron job (idempotent — safe to call on every init).
 */
function sc_schedule_huggingface_sync() {
	if ( ! wp_next_scheduled( 'sc_huggingface_sync_cron' ) ) {
		wp_schedule_event( time(), 'daily', 'sc_huggingface_sync_cron' );
	}
}

/**
 * Unschedule the Hugging Face sync cron job.
 */
function sc_unschedule_huggingface_sync() {
	$timestamp = wp_next_scheduled( 'sc_huggingface_sync_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'sc_huggingface_sync_cron' );
	}
}
