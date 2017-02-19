<?php
/**
 * @package SC
 */

/**
 * Handles Projectes CPT
 */
class SC_Projectes extends SC_TypeBase {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'projecte','projectes' );
	}

	/**
	 * Gets all projectects sorted
	 *
	 * @param array $args to filter out parameters.
	 * @return array
	 */
	public function get_sorted_projects( $args = array() ) {
		$default_args = $this->get_query_args();

		$args = array_merge( $default_args, $args );

		query_posts( $args );
		$projects = Timber::get_posts( $args );

		$this->sort_list( $projects );

		return $projects;
	}

	private function get_query_args() {
		return array(
			'post_type' => $this->singular,
			'post_status'    => 'publish',
			'orderby' => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'classificacio',
					'field' => 'slug',
					'terms' => 'arxivat',
					'operator'  => 'NOT IN',
				),
			),
		);
	}

	private function sort_list( & $projects ) {
		usort( $projects, array( $this, 'sort' ) );
	}

	/**
	 * Sorts two projects based on if they're featured and title
	 *
	 * @param Projecte $first project to sort.
	 * @param Projecte $second project to sort.
	 * @return int
	 */
	public function sort( $first, $second ) {
		if ( $first->projecte_destacat != $second->projecte_destacat ) {
			return ( $first->projecte_destacat ) ? (-1) : 1;
		}

		return strcmp( $first->post_title, $second->post_title );
	}
}
