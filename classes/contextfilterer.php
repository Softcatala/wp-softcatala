<?php
/**
 * @package SC
 */

/**
 * Filters context for custom pages
 */
class SC_ContextFilterer {

	public function get_filtered_context( $args = false ) {
		
		$this->setup_filters( $args );
		
		$context = Timber::get_context();
		
		$this->remove_filters( $args );
		
		return $context;
	}
	
	public function prefix_title ( $title ) {
		return $this->title . ': ' . $title;
	}
		
	public function change_title ( $title ) {
		return $this->title;
	}
	
	private function setup_filters( $args ) {
		if ( !is_array ( $args ) ) {
			return;
		}
		
		if( isset( $args['title'] ) ) {
			$this->title = $args['title'];
			
			add_filter('wpseo_title', array( $this, 'change_title' ) );
		}
		
		if( isset( $args['prefix_title'] ) ) {
			$this->title = $args['prefix_title'];
			
			add_filter('wpseo_title', array( $this, 'prefix_title' ) );
		}
	}
	
	private function remove_filters( $args ) {
		if ( is_array ( $args ) ) {
			return;
		}
		
		remove_filter('wpseo_title', array( $this, 'prefix_title' ) );
		remove_filter('wpseo_title', array( $this, 'change_title' ) );
	}
}
