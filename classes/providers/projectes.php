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
	 * @return array
	 */
	public static function get_sorted_projects( $args = array(), $arxivats = false ) {

		$default_args = self::get_query_args( $arxivats );

		$args = array_merge( $default_args, $args );

		query_posts( $args );
		$projects = \Timber\Timber::get_posts( $args );

		self::sort_projects_list( $projects );

		return $projects;
	}


	private static function get_query_args( $arxivats ) {
		return array(
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
	}

	private static function sort_projects_list( & $projects ) {
		usort( $projects, array( self::class, 'sort_projects' ) );
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
