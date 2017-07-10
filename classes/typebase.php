<?php
/**
 * @package SC
 */

/**
 * Shared functionality for all SC Custom Post Types
 */
class SC_TypeBase {

	/**
	 * Holds the Rewriter object
	 *
	 * @var type SC_Rewriter
	 */
	var $rewriter;

	/**
	 * Name (singular) of the post type
	 *
	 * @var type string
	 */
	var $singular;

	/**
	 * Constructs the object
	 *
	 * @param string $singular Singular CPT name.
	 * @param string $plural Plural CPT name.
	 */
	public function __construct($singular, $plural) {
		$this->singular = $singular;
		$this->rewriter = new SC_Rewriter( $singular,$plural );
		$this->rewriter->setup_rewrite();
	}

	/**
	 * Returns page containing the information of the archive page
	 *
	 * @returns TimberPost
	 */
	public function get_page() {
		$args = array(
			'name' => $this->singular . '-page',
			'post_type' => 'page',
		);

		return Timber::get_post( $args );
	}
}
