<?php
/**
 * @package Softcatalà
 **/

/**
 * Repository to obtain Esdeveniments
 */
class SC_Esdeveniments_Repository {

	const Highlights = true;

	public static function getHighlights () {
		return self::get( true );
	}

	public static function get( $featured = false ) {

		global $wp_query;

		$amount = $featured ? 3 : -1;

		$base_args = array(
			'meta_key'       => 'wpcf-data_inici',
			'post_type'      => 'esdeveniment',
			'post_status'    => 'publish',
			'orderby'        => 'wpcf-data_inici',
			'order'          => 'ASC',
			'paged'          => get_is_paged(),
			'posts_per_page' => $amount,
			'meta_query' => array(
				get_meta_query_value( 'wpcf-data_fi', self::get_midnight(), '>=', 'NUMERIC' )
			)
		);

		if ( $featured ) {
			$base_args['meta_query'][] = get_meta_query_value( 'wpcf-destacat', '0', '>=', 'NUMERIC' );
		}

		$args = wp_parse_args( $base_args, $wp_query->query );

		query_posts( $args );
		return Timber::get_posts( $args );
	}

	private static function get_midnight() {
		return strtotime('today midnight');
	}
}