<?php
/**
 * @package Softcatalà
 **/

namespace Softcatala\Providers;

/**
 * Repository to obtain Dades obertes
 */
class Dadesobertes {

	/**
	 * All published datasets: recently created ones first, then by
	 * Hugging Face downloads, with the title as tiebreak.
	 */
	public static function get( $featured = false ) {

		$args = array(
			'post_type'      => 'dadesobertes',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		$posts = iterator_to_array( \Timber::get_posts( $args ) );

		usort(
			$posts,
			function ( $a, $b ) {
				if ( $a->is_new() !== $b->is_new() ) {
					return $a->is_new() ? -1 : 1;
				}

				$a_downloads = (int) $a->meta( 'hf_downloads' );
				$b_downloads = (int) $b->meta( 'hf_downloads' );
				if ( $a_downloads !== $b_downloads ) {
					return $b_downloads - $a_downloads;
				}

				return strcasecmp( $a->post_title, $b->post_title );
			}
		);

		return $posts;
	}
}
