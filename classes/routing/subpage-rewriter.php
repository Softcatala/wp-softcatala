<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Routing;

/**
 * Handles Rewrite API for CPT subpages
 */
class SubpageRewriter {
	/**
	 * Singular name of the CPT
	 *
	 * @var string
	 */
	protected $singular;

	/**
	 * Plural name of the CPT.
	 *
	 * @var string
	 */
	protected $plural;

	/**
	 * Constructor
	 *
	 * @param string $singular Singular name of the CPT.
	 * @param string $plural Singular name of the CPT.
	 */
	public function __construct( $singular, $plural ) {
		$this->singular = strtolower( $singular );
		$this->plural = strtolower( $plural );
	}

	/**
	 * Configures the rewrites.
	 */
	public function setup_rewrite() {
		$this->subpages_rewrite();
		add_filter( 'page_link', array( $this, 'subpages_post_link' ) , 10, 2 );
	}

	/**
	 * Configures the rewrite for CPT subpages
	 */
	private function subpages_rewrite() {
		add_rewrite_rule(
			"$this->plural/[^&/]+/([a-zA-Z][^/]*)/?",
			'index.php?post_type=page&pagename=' . $this->get_partial_subpages_path() . '$matches[1]',
			'top'
		);
	}

	/**
	 * Returs permalink for particular subpage.
	 *
	 * @param string $permalink default permalink for the page.
	 * @param object $page WP_Post object representing the page.
	 * @return string
	 */
	public function subpages_post_link( $permalink, $page ) {

		if ( false === strpos( $permalink, $this->get_partial_subpages_path() ) ) {
			return $permalink;
		}

		$parent_id = get_post_meta( $page, $this->singular, true );

		$parent_entity = get_post( $parent_id );

		if ( null !== $parent_entity ) {

			$slug = $parent_entity->post_name;

			return str_replace(
				$this->get_partial_subpages_path(),
				$this->plural . '/' . $slug . '/',
				$permalink
			);
		}
	}

	/**
	 * Returns parent page in the content tree of the CPT subpages.
	 *
	 * @return string
	 */
	private function get_partial_subpages_path() {
		return "subpagines-$this->plural/";
	}
}
