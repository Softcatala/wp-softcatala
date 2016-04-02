<?php
/**
 * @package SC
 */

/**
 * Filters context for custom pages
 */
class SC_ContextFilterer {

	/**
	 * Returns filtered Timber Context
	 *
	 * @param array $args Elements to be filtered.
	 * @return array
	 */
	public function get_filtered_context( $args = false ) {

		$this->setup_filters( $args );

		$context = Timber::get_context();

		$this->remove_filters( $args );

		return $context;
	}

	/**
	 * Prefixes the title
	 *
	 * @param string $title Original title.
	 * @return string
	 */
	public function prefix_title ( $title ) {
		return $this->title . ': ' . $title;
	}

	/**
	 * Replaces the title
	 *
	 * @param string $title Original title.
	 * @return string
	 */
	public function change_title ( $title ) {
		return $this->title;
	}

	/**
	 * Setups all filters
	 *
	 * @param array $args Elements to be filtered.
	 * @return void
	 */
	private function setup_filters( $args ) {
		if ( ! is_array( $args ) ) {
			return;
		}

		if ( isset( $args['title'] ) ) {
			$this->title = $args['title'];

			add_filter( 'wpseo_title', array( $this, 'change_title' ) );
		}

		if ( isset( $args['prefix_title'] ) ) {
			$this->title = $args['prefix_title'];

			add_filter( 'wpseo_title', array( $this, 'prefix_title' ) );
		}
	}

	/**
	 * Removes all filters previously set up
	 *
	 * @param array $args Elements to be filtered.
	 * @return void
	 */
	private function remove_filters( $args ) {
		if ( is_array( $args ) ) {
			return;
		}

		remove_filter( 'wpseo_title', array( $this, 'prefix_title' ) );
		remove_filter( 'wpseo_title', array( $this, 'change_title' ) );
	}
}
