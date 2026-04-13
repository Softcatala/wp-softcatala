<?php
/**
 * @package Softcatalà
 **/

namespace Softcatala\Providers;

/**
 * Repository to obtain Projectes
 */
class Projectes {

	/**
	 * Gets all projectects sorted
	 *
	 * @param array   $args to filter out parameters.
	 * @param boolean $arxivats whether to return (or not) archived projects.
	 * @param boolean $is_logged_in whether the current visitor is authenticated.
	 * @return array
	 */
	public static function get_sorted_projects( $args = array(), $arxivats = false, $is_logged_in = false ) {

		$default_args = self::get_query_args( $arxivats, $is_logged_in );

		$args = array_merge( $default_args, $args );

		query_posts( $args );
		$projects = \Timber\Timber::get_posts( $args );

		self::sort_projects_list( $projects );

		return $projects;
	}


	private static function get_query_args( $arxivats, $is_logged_in = false ) {
		$args = array(
			'post_type' => 'projecte',
			'post_status'    => 'publish',
			'orderby' => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'classificacio',
					'field' => 'slug',
					'terms' => 'arxivat',
					'operator'  => ( $arxivats ) ? 'IN' : 'NOT IN',
				),
			),
		);

		if ( ! $is_logged_in ) {
			// Exclude internal projects from anonymous visitors.
			// Use a two-arm OR so that pre-existing posts with no meta row are treated as public.
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'projecte_intern',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'projecte_intern',
					'value'   => '1',
					'compare' => '!=',
				),
			);
		}

		return $args;
	}

	private static function sort_projects_list( & $projects ) {
		$projects->uasort( array( self::class, 'sort_projects' ) );
	}

	/**
	 * Sorts two projects based on if they're featured and title
	 *
	 * @param \Timber\Post $first project to sort.
	 * @param \Timber\Post $second project to sort.
	 * @return int
	 */
	public static function sort_projects( $first, $second ) {
		if ( $first->projecte_destacat != $second->projecte_destacat ) {
			return ( $first->projecte_destacat ) ? ( -1 ) : 1;
		}

		return strcasecmp( $first->post_title, $second->post_title );
	}
}
