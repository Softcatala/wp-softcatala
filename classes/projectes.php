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

	public function get_sorted_projects() {
		$args = $this->get_query_args();

		query_posts( $args );
		$projects = Timber::get_posts( $args );

		$this->sort_list( $projects );

		return $projects;
	}

	private function get_query_args() {
		return $args = array(
			'post_type' => $this->singular,
			'post_status'    => 'publish',
			'orderby' => 'title',
			'order'          => 'ASC',
			'paged' => get_is_paged(),
			'posts_per_page' => 36,
			'tax_query' => array(
				array (
					'taxonomy' => 'classificacio',
					'field' => 'slug',
					'terms' => 'arxivat',
					'operator'  => 'NOT IN'
				)
			)
		);
	}

	private function sort_list( & $projects ) {
		usort( $projects, array($this, 'sort' ) );
	}

	public function sort( $first, $second ) {
		if ( $first->projecte_destacat != $second->projecte_destacat )
		{
			return ( $first->projecte_destacat ) ? -1 : 1;
		}

		return strcmp($first->post_title, $second->post_title);
	}
}