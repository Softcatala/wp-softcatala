<?php
/**
 * @package SC
 */

/**
 * Helper class for Types support
 */
class SC_TypeHelper {

	/**
	 * @var string CPT
	 */
	public $type;

	/**
	 * Constructor
	 *
	 * @param string $type CPT name.
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * Returns an array of pairs ID, title of entries of CPT
	 *
	 * @return array
	 */
	function get_info_for_select() {
		$query = new WP_Query();

		$args = array(
				'post_type'        => $this->type,
				'post_status'      => 'publish',
				'no_found_rows'    => true,
				'posts_per_page'      => -1,
		);

		$all_programs = $query->query( $args );

		return array_map( function ($entry) {
			return array(
				'#value' => $entry->ID,
				'#title' => $entry->post_title,
			);
		}, $all_programs );
	}
}
