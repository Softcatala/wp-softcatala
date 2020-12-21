<?php
/**
 * @package Softcatalà
 **/

namespace Softcatala\Providers;

/**
 * Repository to obtain Esdeveniments
 */
class Esdeveniments {

	const HIGHLIGHTS = true;

	public static function get_highlights() {
		return self::get( true );
	}

	public static function get( $featured = false ) {

		global $wp_query;

		$amount = -1;

		if ( $featured ) {
			add_filter( 'posts_orderby', 'orderbyreplace' );
			$amount = 3;
		}

		$base_args = array(
			'meta_key'       => 'data_inici',
			'post_type'      => 'esdeveniment',
			'post_status'    => 'publish',
			'orderby'        => 'data_inici',
			'order'          => 'ASC',
			'paged'          => get_is_paged(),
			'posts_per_page' => $amount,
			'meta_query'     => array(
				get_meta_query_value( 'data_fi', self::get_midnight(), '>=', 'NUMERIC' ),
			),
		);

		if ( $featured ) {
			$base_args['meta_query'][] = get_meta_query_value( 'destacat', '0', '>=', 'NUMERIC' );
		}

		$args = wp_parse_args( $base_args, $wp_query->query );

		$posts = \Timber::get_posts( $args );

		if ( $featured ){
			remove_filter( 'posts_orderby', 'orderbyreplace' );
		}

		return $posts;
	}

	private static function get_midnight() {
		return strtotime( 'today midnight' );
	}
}
