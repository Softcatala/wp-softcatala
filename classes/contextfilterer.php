<?php
/**
 * @package SC
 */

/**
 * Filters context for custom pages
 */
class SC_ContextFilterer {

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

        $context = Timber::get_context();

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
            return;
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
     * Replaces the description
     *
     * @param string $description Original description.
     * @return string
     */
    public function change_description ( $description ) {
        return $this->description;
    }

    /**
     * Prefixes the description
     *
     * @param string $description Original description.
     * @return string
     */
    public function prefix_description ( $description ) {
        return $this->description . $description;
    }

    /**
     * Replaces the canonical URL
     *
     * @param string $url Original canonical URL.
     * @return string
     */
    public function change_canonical ( $url ) {
        return $this->canonical;
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
        }

        if ( isset( $args['prefix_title'] ) ) {
            $this->title = $args['prefix_title'];

            add_filter( 'wpseo_title', array( $this, 'prefix_title' ) );
			add_filter( 'wpseo_opengraph_title', array( $this, 'prefix_title' ) );
        }

        if ( isset( $args['description'] ) ) {
            $this->description = $args['description'];

            add_filter( 'wpseo_metadesc', array( $this, 'change_description' ) );
        }

        if ( isset( $args['prefix_description'] ) ) {
            $this->description = $args['prefix_description'];

            add_filter( 'wpseo_metadesc', array( $this, 'prefix_description' ) );
        }

        if ( isset( $args['canonical'] ) ) {
            $this->canonical = $args['canonical'];

            add_filter( 'wpseo_canonical', array( $this, 'change_canonical' ) );
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
        remove_filter( 'wpseo_title', array( $this, 'prefix_title' ) );
		remove_filter( 'wpseo_opengraph_title', array( $this, 'change_title' ) );
		remove_filter( 'wpseo_opengraph_title', array( $this, 'change_title' ) );
        remove_filter( 'wpseo_metadesc', array( $this, 'change_description' ) );
        remove_filter( 'wpseo_canonical', array( $this, 'change_canonical' ) );
		
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
