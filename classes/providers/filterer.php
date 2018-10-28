<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Providers;

/**
 * Class Filterer
 *
 * Wraps 3rd party provider classes adding filters
 */
class Filterer {

	public static function wp_query_search_in_title( $query ) {

		add_filter( 'posts_search', [ self::class, 'search_by_title_only' ], 500, 2 );

		$wp_query = new \WP_Query( $query );

		remove_filter( 'posts_search', [ self::class, 'search_by_title_only' ], 500 );

		return $wp_query;
	}

	public static function timber_posts_search_in_title( $query ) {

		add_filter( 'posts_search', [ self::class, 'search_by_title_only' ], 500, 2 );

		query_posts( $query );
		$programs = \Timber\Timber::get_posts( $query );

		remove_filter( 'posts_search', [ self::class, 'search_by_title_only' ], 500 );

		return $programs;
	}

	/**
	 * Search SQL filter for matching against post title only.
	 *
	 * @link    http://wordpress.stackexchange.com/a/11826/1685
	 *
	 * @param   string    $search provided by the user.
	 * @param   \WP_Query $wp_query built so far.
	 *
	 * @return array|string
	 */
	public static function search_by_title_only( $search, $wp_query ) {
		if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
			global $wpdb;

			$query = $wp_query->query_vars;
			$fuzzy = ! empty( $query['exact'] ) ? '' : '%';

			$search = array();

			foreach ( (array) $query['search_terms'] as $term ) {
				$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $fuzzy . $wpdb->esc_like( $term ) . $fuzzy );
			}

			if ( ! is_user_logged_in() ) {
				$search[] = "$wpdb->posts.post_password = ''";
			}

			$search = ' AND ' . implode( ' AND ', $search );
		}

		return $search;
	}
}
