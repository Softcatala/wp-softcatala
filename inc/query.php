<?php

/**
 * Post query helper functions.
 *
 * Provides SearchQueryType constants and argument builders used by
 * archive templates and providers to construct WP_Query args.
 */

abstract class SearchQueryType {
	const All           = 0;
	const FilteredDate  = 1;
	const Search        = 2;
	const Aparell       = 4;
	const Post          = 6;
	const PagePrograma  = 7;
	const FilteredTema  = 8;
	const PageProjecte  = 9;
}

/**
 * Returns the WP_Query arguments for a given post type and query type.
 *
 * @param string $post_type
 * @param int    $queryType  One of the SearchQueryType constants.
 * @param mixed  $filter
 * @return array
 */
function get_post_query_args( $post_type, $queryType, $filter = array() ) {
	switch ( $post_type ) {
		case 'aparell':
			$base_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => -1,
			);
			break;
		case 'programa':
			$base_args = array(
				'post_type'      => $post_type,
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
			break;
		case 'projecte':
			$base_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'paged'          => get_is_paged(),
				'posts_per_page' => 36,
				'tax_query'      => array(
					array(
						'taxonomy' => 'classificacio',
						'field'    => 'slug',
						'terms'    => 'arxivat',
						'operator' => 'NOT IN',
					),
				),
			);
			break;
		case 'page':
			$base_args = array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'order'       => 'ASC',
				'meta_query'  => array(
					get_meta_query_value( $filter['subpage_type'], $filter['post_id'], '=', 'NUMERIC' ),
				),
			);
			break;
		case 'post':
			$base_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'order'          => 'DESC',
				'paged'          => get_is_paged(),
				'posts_per_page' => 10,
			);
			break;
		default:
			$base_args = array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
			);
	}

	$filter_args = array();
	if ( $queryType == SearchQueryType::Post ) {
		if ( ! empty( $filter['s'] ) ) {
			$filter_args['s'] = $filter['s'];
		}
		if ( ! empty( $filter['categoria'] ) ) {
			$filter_args['category__and'] = $filter['categoria'];
		}
	} elseif ( $queryType == SearchQueryType::Search ) {
		$filter_args = array(
			's'          => $filter,
			'meta_query' => array(
				get_meta_query_value( 'data_fi', time(), '>=', 'NUMERIC' ),
			),
		);
	} elseif ( $queryType == SearchQueryType::FilteredDate ) {
		$filter_args = array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					get_meta_query_value( 'data_fi', $filter['start_time'], '>=', 'NUMERIC' ),
				),
				array(
					get_meta_query_value( 'data_inici', $filter['final_time'], '<=', 'NUMERIC' ),
				),
			),
		);
	} elseif ( $queryType == SearchQueryType::Aparell ) {
		$filter_args = array();
		if ( ! empty( $filter['s'] ) ) {
			$filter_args['s'] = $filter['s'];
		}

		if ( ! empty( $filter['so_aparell'] ) ) {
			$filter_args['tax_query'][] = array(
				'taxonomy' => 'so_aparell',
				'field'    => 'slug',
				'terms'    => $filter['so_aparell'],
			);
			$filter_args['filter_so'] = $filter['so_aparell'];
		}

		if ( ! empty( $filter['tipus_aparell'] ) ) {
			$filter_args['tax_query'][]  = array(
				'taxonomy' => 'tipus_aparell',
				'field'    => 'slug',
				'terms'    => $filter['tipus_aparell'],
			);
			$filter_args['filter_tipus'] = $filter['tipus_aparell'];
		}

		if ( ! empty( $filter['fabricant'] ) ) {
			$filter_args['tax_query'][]      = array(
				'taxonomy' => 'fabricant',
				'field'    => 'slug',
				'terms'    => $filter['fabricant'],
			);
			$filter_args['filter_fabricant'] = $filter['fabricant'];
		}
	} elseif ( $queryType == SearchQueryType::FilteredTema ) {
		if ( ! empty( $filter ) ) {
			$filter_args['tax_query'][] = array(
				'taxonomy' => 'esdeveniment_cat',
				'field'    => 'slug',
				'terms'    => $filter,
			);
		}
	} elseif ( $queryType == SearchQueryType::PagePrograma || $queryType == SearchQueryType::PageProjecte ) {
		$filter_args = array(
			'posts_per_page' => 20,
		);
	} else {
		$filter_args = array(
			'meta_query' => array(
				get_meta_query_value( 'data_fi', time(), '>=', 'NUMERIC' ),
			),
		);
	}

	return array_merge( $base_args, $filter_args );
}

/**
 * Creates a meta_query clause array.
 *
 * @param string $key
 * @param mixed  $value
 * @param string $compare
 * @param string $type
 * @return array
 */
function get_meta_query_value( $key, $value, $compare, $type ) {
	return array(
		'key'     => $key,
		'value'   => $value,
		'compare' => $compare,
		'type'    => $type,
	);
}

/**
 * Returns the current page number from the global $paged variable.
 *
 * @return int
 */
function get_is_paged() {
	global $paged;

	return ( ! isset( $paged ) || ! $paged ) ? 1 : $paged;
}
