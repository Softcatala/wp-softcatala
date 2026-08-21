<?php
/**
 * Filters context for custom pages
 */
class SC_ContextFilterer {

	/** @var string */
	private $title = '';

	/** @var string */
	private $description = '';

	/** @var string */
	private $canonical = '';

	/** @var bool */
	private $prefix_description = false;

	/**
	 * @var array|bool Contains elements to add to the context.
	 */
	private $context_elements;

	/**
	 * Constructor
	 *
	 * @param array|bool $array initial set of elements for the context.
	 */
	public function __construct( $array = false ) {
		$this->context_elements = $array;
	}

	/**
	 * Returns filtered Timber Context
	 *
	 * @param array $args Elements to be filtered.
	 * @param bool  $override_with_empty Whether to override default when empty is provided.
	 * @return array
	 */
	public function get_filtered_context( $args = false, $override_with_empty = true ) {

		if ( ! $override_with_empty ) {
			$args = $this->remove_empty( $args );
		}

		$this->setup_filters( $args );

		$context = Timber::context();

		$context = $this->add_context_elements( $context );

		$this->remove_filters( $args );

		return $context;
	}

	/**
	 * Adds set of elements to Timber's Context
	 *
	 * @param array $context Timber Context.
	 * @return array
	 */
	private function add_context_elements( $context ) {

		if ( ! is_array( $this->context_elements ) ) {
			return $context;
		}

		foreach ( $this->context_elements as $key => $value ) {
			$context[ $key ] = $value;
		}

		return $context;
	}

	/**
	 * Prefixes the title
	 *
	 * @param string $title Original title.
	 * @return string
	 */
	public function prefix_title( $title ) {
		return $this->title . ': ' . $title;
	}

	/**
	 * Replaces the title
	 *
	 * @param string $title Original title.
	 * @return string
	 */
	public function change_title( $title ) {
		return $this->title;
	}

	/**
	 * Replaces the description
	 *
	 * @param string $description Original description.
	 * @return string
	 */
	public function change_description( $description ) {
		return $this->description;
	}

	/**
	 * Prefixes the description
	 *
	 * @param string $description Original description.
	 * @return string
	 */
	public function prefix_description( $description ) {
		return $this->description . $description;
	}

	/**
	 * Replaces the canonical URL
	 *
	 * @param string $url Original canonical URL.
	 * @return string
	 */
	public function change_canonical( $url ) {
		return $this->canonical;
	}

	/**
	 * Keeps the Yoast WebPage entity aligned with a dynamic route.
	 *
	 * @param array $data Original WebPage schema data.
	 * @return array
	 */
	public function change_webpage_schema( $data ) {
		if ( ! is_array( $data ) || empty( $this->canonical ) ) {
			return $data;
		}

		$data['@id'] = $this->canonical . '#webpage';
		$data['url'] = $this->canonical;

		if ( ! empty( $this->title ) ) {
			$data['name'] = $this->title;
		}

		$data['description'] = $this->schema_description( $data['description'] ?? '' );
		$data = $this->change_schema_actions( $data );

		return $data;
	}

	/**
	 * @param string $description Existing schema description.
	 * @return string
	 */
	private function schema_description( $description ) {
		if ( empty( $this->description ) ) {
			return $description;
		}

		return $this->prefix_description ? $this->description . $description : $this->description;
	}

	/**
	 * @param array $data WebPage schema data.
	 * @return array
	 */
	private function change_schema_actions( $data ) {
		if ( ! isset( $data['potentialAction'] ) || ! is_array( $data['potentialAction'] ) ) {
			return $data;
		}

		foreach ( $data['potentialAction'] as &$action ) {
			if ( is_array( $action ) && isset( $action['target'] ) ) {
				$action['target'] = array( $this->canonical );
			}
		}
		unset( $action );

		return $data;
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
			add_filter( 'wpseo_opengraph_title', array( $this, 'change_title' ) );
			add_filter( 'wpseo_twitter_title', array( $this, 'change_title' ) );
		}

		if ( isset( $args['prefix_title'] ) ) {
			$this->title = $args['prefix_title'];

			add_filter( 'wpseo_title', array( $this, 'prefix_title' ) );
			add_filter( 'wpseo_opengraph_title', array( $this, 'prefix_title' ) );
			add_filter( 'wpseo_twitter_title', array( $this, 'prefix_title' ) );
		}

		if ( isset( $args['description'] ) ) {
			$this->description = $args['description'];
			$this->prefix_description = false;

			add_filter( 'wpseo_metadesc', array( $this, 'change_description' ) );
			add_filter( 'wpseo_opengraph_desc', array( $this, 'change_description' ) );
			add_filter( 'wpseo_twitter_description', array( $this, 'change_description' ) );
		}

		if ( isset( $args['prefix_description'] ) ) {
			$this->description = $args['prefix_description'];
			$this->prefix_description = true;

			add_filter( 'wpseo_metadesc', array( $this, 'prefix_description' ) );
			add_filter( 'wpseo_opengraph_desc', array( $this, 'prefix_description' ) );
			add_filter( 'wpseo_twitter_description', array( $this, 'prefix_description' ) );
		}

		if ( isset( $args['canonical'] ) ) {
			$this->canonical = $args['canonical'];

			add_filter( 'wpseo_canonical', array( $this, 'change_canonical' ) );
			add_filter( 'wpseo_opengraph_url', array( $this, 'change_canonical' ) );
			add_filter( 'wpseo_schema_webpage', array( $this, 'change_webpage_schema' ) );
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

		remove_filter( 'wpseo_title', array( $this, 'change_title' ) );
		remove_filter( 'wpseo_title', array( $this, 'prefix_title' ) );
		remove_filter( 'wpseo_opengraph_title', array( $this, 'change_title' ) );
		remove_filter( 'wpseo_opengraph_title', array( $this, 'prefix_title' ) );
		remove_filter( 'wpseo_twitter_title', array( $this, 'change_title' ) );
		remove_filter( 'wpseo_twitter_title', array( $this, 'prefix_title' ) );
		remove_filter( 'wpseo_metadesc', array( $this, 'change_description' ) );
		remove_filter( 'wpseo_metadesc', array( $this, 'prefix_description' ) );
		remove_filter( 'wpseo_opengraph_desc', array( $this, 'change_description' ) );
		remove_filter( 'wpseo_opengraph_desc', array( $this, 'prefix_description' ) );
		remove_filter( 'wpseo_twitter_description', array( $this, 'change_description' ) );
		remove_filter( 'wpseo_twitter_description', array( $this, 'prefix_description' ) );
		remove_filter( 'wpseo_canonical', array( $this, 'change_canonical' ) );
		remove_filter( 'wpseo_opengraph_url', array( $this, 'change_canonical' ) );
		remove_filter( 'wpseo_schema_webpage', array( $this, 'change_webpage_schema' ) );
	}

	/**
	 * Remove all elements of the array with null value
	 *
	 * @param array $args Elements to be filtered.
	 * @return array|false
	 */
	private function remove_empty( $args ) {

		$new_args = array();

		foreach ( $args as $key => $value ) {
			if ( ! empty( trim( $value ) ) ) {
				$new_args[ $key ] = $value;
			}
		}

		return empty( $new_args ) ? false : $new_args;
	}
}
