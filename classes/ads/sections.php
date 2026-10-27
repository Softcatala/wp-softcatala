<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Ads;

/**
 * Maps a post to the ad section (config/ad-slots.php) whose zones it uses.
 */
class Sections {

	const DEFAULT_SECTION = 'default';

	private $sections;

	/**
	 * @param array|null $sections Section definitions; defaults to config/ad-slots.php.
	 */
	public function __construct( $sections = null ) {
		$this->sections = null === $sections ? ZoneResolver::load_config()['sections'] : $sections;
	}

	/**
	 * Section for a page (by template) or a single post of a mapped type.
	 *
	 * @param \WP_Post|null $post
	 * @return string
	 */
	public function for_post( $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return self::DEFAULT_SECTION;
		}

		$template = 'page' === $post->post_type ? get_page_template_slug( $post ) : '';

		foreach ( $this->sections as $name => $section ) {
			if ( $template && in_array( $template, $section['templates'] ?? array(), true ) ) {
				return $name;
			}
			if ( in_array( $post->post_type, $section['post_types'] ?? array(), true ) ) {
				return $name;
			}
		}

		return self::DEFAULT_SECTION;
	}

	/**
	 * Section for the current request: singular views map through their post, the rest use default.
	 *
	 * @return string
	 */
	public function current() {
		return is_singular() ? $this->for_post( get_queried_object() ) : self::DEFAULT_SECTION;
	}
}
