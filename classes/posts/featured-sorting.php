<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Posts;

/**
 * Shared comparator for post types that can be featured in a listing.
 *
 * Both programes and projectes are listed featured-first and alphabetically
 * within each group; the rule used to be copy-pasted in each provider.
 *
 * The using class must implement is_featured().
 */
trait FeaturedSorting {

	/**
	 * Whether the post is featured in listings.
	 *
	 * @return bool
	 */
	abstract public function is_featured();

	/**
	 * Sorts two posts: featured ones first, then by title.
	 *
	 * @param self $first  post to sort.
	 * @param self $second post to sort.
	 * @return int
	 */
	public static function compare_featured_then_title( $first, $second ) {
		if ( $first->is_featured() !== $second->is_featured() ) {
			return $first->is_featured() ? -1 : 1;
		}

		return strcasecmp( $first->post_title, $second->post_title );
	}
}
