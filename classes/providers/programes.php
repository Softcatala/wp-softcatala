<?php
/**
 * @package Softcatalà
 **/

namespace Softcatala\Providers;

/**
 * Repository to obtain Programes
 */
class Programes {

	/**
	 * Gets all programes sorted
	 *
	 * @param array $filter to filter out parameters.
	 * @return array
	 */
	public static function get_sorted( $filter = array() ) {

		$args = self::get_query_args( $filter );

		$programs = Filterer::timber_posts_search_in_title( $args );

		self::sort_programs_list( $programs );

		return $programs;
	}

	private static function get_query_args( $filter ) {

		$default_args = array(
			'post_type'      => 'programa',
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'paged'          => get_is_paged(),
			'posts_per_page' => 18,
			'tax_query'      => array(
				array(
					'taxonomy' => 'classificacio',
					'field'    => 'slug',
					'terms'    => 'arxivat',
					'operator' => 'NOT IN',
				),
			),
		);

		$filter_args = self::filter_args( $filter );

		return array_merge( $default_args, $filter_args );
	}

	private static function filter_args( $filter ) {

		$filter_args = array();

		if ( ! empty( $filter['s'] ) ) {
			$filter_args['s'] = $filter['s'];
			$filter_args['orderby']        = 'relevance';
			$filter_args['order']          = 'DESC';
		}

		if ( ! empty( $filter['sistema-operatiu-programa'] ) ) {

			if ( in_array( $filter['sistema-operatiu-programa'], array( 'windows', 'linux', 'osx' ) ) ) {
				$terms = array(
					$filter['sistema-operatiu-programa'],
					'multiplataforma',
				);
			} else {
				$terms = array(
					$filter['sistema-operatiu-programa']
				);
			}

			$filter_args['tax_query'][]             = array(
				'taxonomy' => 'sistema-operatiu-programa',
				'field'    => 'slug',
				'terms'    => $terms,
			);

			$filter_args['filter_sistema_operatiu'] = $filter['sistema-operatiu-programa'];
		}

		if ( ! empty( $filter['post__in'] ) ) {
			$filter_args['post__in'] = $filter['post__in'];
		}

		if ( ! empty( $filter['categoria-programa'] ) ) {
			$filter_args['tax_query'][]      = array(
				'taxonomy' => 'categoria-programa',
				'field'    => 'slug',
				'terms'    => $filter['categoria-programa'],
			);
			$filter_args['filter_categoria'] = $filter['categoria-programa'];
		}

		return $filter_args;
	}

	private static function sort_programs_list( & $programs ) {
		usort( $programs, array( self::class, 'sort_programs' ) );
	}

	/**
	 * Sorts two programs based on if they're featured and title
	 *
	 * @param \Timber\Post $first program to sort.
	 * @param \Timber\Post $second program to sort.
	 * @return int
	 */
	public static function sort_programs( $first, $second ) {
		if ( $first->programa_destacat != $second->programa_destacat ) {
			return ( $first->programa_destacat ) ? (-1) : 1;
		}

		return strcasecmp( $first->post_title, $second->post_title );
	}

	public static function get_filters( $query ) {

		$filters = array();

		$all_so  = \Timber\Timber::get_terms( 'sistema-operatiu-programa' );
		$all_cat = \Timber\Timber::get_terms( 'categoria-programa' );
		$filters['llicencies'] = \Timber\Timber::get_terms( 'llicencia' );

		if ( ! empty( $query ) ) {

			$filters['sistemes_operatius'] = array();

			foreach ( $all_so as $so ) {

				$temp_filter = $query;
				$temp_filter['sistema-operatiu-programa'] = $so->slug;

				$query_args = self::get_query_args( $temp_filter );
				$query_args['paged'] = -1;

				$wp_query = new \WP_Query( $query_args );
				if ( ! empty( $wp_query->posts ) ) {
					array_push( $filters['sistemes_operatius'], $so );
				}
			}

			$filters['categories_programes'] = array();

			foreach ( $all_cat as $cat ) {

				$temp_filter = $query;
				$temp_filter['categoria-programa'] = $cat->slug;

				$query_args = self::get_query_args( $temp_filter );
				$query_args['paged'] = -1;

				$wp_query = Filterer::wp_query_search_in_title( $query_args );

				if ( ! empty( $wp_query->posts ) ) {
					array_push( $filters['categories_programes'], $cat );
				}
			}
		} else {

			$filters['sistemes_operatius'] = $all_so;
			$filters['categories_programes'] = $all_cat;
		}//end if

		return $filters;
	}
}
