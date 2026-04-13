<?php

/**
 * Downloads and programs cron/update functions.
 *
 * Handles fetching download statistics from baixades.softcatala.org,
 * building program context for Timber templates, and scheduling
 * the daily cron job that keeps download counts fresh.
 */

/**
 * Fetches the full downloads JSON from baixades.softcatala.org,
 * cached via transient for 2 hours.
 *
 * @return array|false Decoded JSON array, or false on failure.
 */
function get_downloads_full() {
	$result = get_transient( 'downloads_full' );

	if ( false === $result ) {
		$result = json_decode( file_get_contents( 'https://baixades.softcatala.org/full.json' ), true );
		set_transient( 'downloads_full', $result, 2 * HOUR_IN_SECONDS );
	}

	return $result;
}

/**
 * Builds the Timber context array for a single programa post.
 *
 * @param \Timber\Post $programa
 * @return array
 */
function get_program_context( $programa ) {
	$context = Timber::context();

	$context['sidebar_top']      = Timber::get_widgets( 'sidebar_top' );
	$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
	$context['sidebar_bottom']   = Timber::get_widgets( 'sidebar_bottom' );
	$context['post']             = $programa;

	$context['arxivat'] = $programa->has_term( 'arxivat', 'classificacio' );
	$context['credits'] = $programa->meta( 'credits' );
	$baixades           = $programa->meta( 'baixada' );
	$context['baixades'] = generate_url_download( $baixades, $programa );

	// Contact Form
	$context['contact']['to_email']   = get_option( 'to_email_rebost' );
	$context['contact']['from_email'] = get_option( 'email_rebost' );

	// Add program form data
	$context['categories']['sistemes_operatius']   = Timber::get_terms( 'sistema-operatiu-programa' );
	$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
	$context['categories']['llicencies']           = Timber::get_terms( 'llicencia' );

	// Download count
	$download_full = get_downloads_full();
	if ( $download_full ) {
		$wordpress_ids_column = array_column( $download_full, 'wordpress_id' );
		if ( $wordpress_ids_column ) {
			$index = array_search( $programa->ID, $wordpress_ids_column );
			if ( $index ) {
				$context['total_downloads'] = $download_full[ $index ]['total'];
			}
		}
	}

	$logo             = get_img_from_id( $programa->logotip_programa );
	$context['logotip'] = $logo;

	$yoastlogo = get_the_post_thumbnail_url() ?: $logo;

	$custom_logo_filter = function ( $img ) use ( $yoastlogo ) {
		return $yoastlogo;
	};

	add_filter( 'wpseo_twitter_image', $custom_logo_filter );
	add_filter( 'wpseo_opengraph_image', $custom_logo_filter );

	$query   = array( 'post_id' => $programa->ID, 'subpage_type' => 'programa' );
	$args    = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );

	query_posts( $args );

	$context['related_pages'] = Timber::get_posts( $args );

	$project_id = get_post_meta( $programa->ID, 'projecte_relacionat', true );

	if ( $project_id ) {
		$context['projecte_relacionat_url']  = get_permalink( $project_id );
		$context['projecte_relacionat_name'] = get_the_title( $project_id );
	}

	return $context;
}

/**
 * Update all program downloads — WordPress-friendly wrapper.
 * Can be called from hooks, cron, admin interfaces, etc.
 *
 * @param string|null $program_filter Optional program group filter.
 * @param bool        $dry_run        Whether to actually make changes.
 * @return array Result array with statistics and details.
 */
function sc_update_all_programs( $program_filter = null, $dry_run = false ) {
	$updater = new SC_Downloads_Updater();
	return $updater->update_all_programs( $program_filter, $dry_run );
}

/**
 * Hook for WordPress cron — update all programs.
 */
function sc_cron_update_downloads() {
	$result = sc_update_all_programs();

	if ( $result['success'] ) {
		error_log( 'SC Downloads Update: ' . $result['message'] );
	} else {
		error_log( 'SC Downloads Update Error: ' . $result['message'] );
	}
}
add_action( 'sc_update_downloads_cron', 'sc_cron_update_downloads' );
add_action( 'init', 'sc_schedule_downloads_update' );

/**
 * Schedule the downloads update cron job (idempotent — safe to call on every init).
 */
function sc_schedule_downloads_update() {
	if ( ! wp_next_scheduled( 'sc_update_downloads_cron' ) ) {
		wp_schedule_event( time(), 'daily', 'sc_update_downloads_cron' );
	}
}

/**
 * Unschedule the downloads update cron job.
 */
function sc_unschedule_downloads_update() {
	$timestamp = wp_next_scheduled( 'sc_update_downloads_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'sc_update_downloads_cron' );
	}
}
