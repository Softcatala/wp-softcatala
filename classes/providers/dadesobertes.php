<?php
/**
 * @package Softcatalà
 **/

namespace Softcatala\Providers;

/**
 * Repository to obtain Dades obertes
 */
class Dadesobertes {

	
	public static function get( $featured = false ) {

		global $wp_query;
			
		$amount = 10;
		
		$base_args = array(
			'post_type'      => 'dadesobertes',
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'paged'          => get_is_paged(),
			'posts_per_page' => $amount
		);

		
		$args = wp_parse_args( $base_args, $wp_query->query );

		$posts = \Timber::get_posts( $args );

		return $posts;
	}

	
}
