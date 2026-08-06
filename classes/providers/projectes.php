<?php
/**
 * @package Softcatalà
 **/

namespace Softcatala\Providers;

use Softcatala\Posts\Projecte;

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
	 * @return \Timber\PostCollectionInterface Iterable and countable, but not an array:
	 *                                         convert it with iterator_to_array() before
	 *                                         passing it to array_map() and friends.
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
			$args['meta_query'] = Projecte::public_meta_query();
		}

		return $args;
	}

	private static function sort_projects_list( & $projects ) {
		$projects->uasort( array( Projecte::class, 'compare_featured_then_title' ) );
	}
}
