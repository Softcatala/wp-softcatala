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

		query_posts( $args );
		$programs = \Timber\Timber::get_posts( $args );

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
		}

		if ( ! empty( $filter['sistema-operatiu-programa'] ) ) {
			$filter_args['tax_query'][]             = array(
				'taxonomy' => 'sistema-operatiu-programa',
				'field'    => 'slug',
				'terms'    => array(
					$filter['sistema-operatiu-programa'],
					'multiplataforma',
				),
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
}
