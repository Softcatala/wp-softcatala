<?php
/**
 * @package Softcatalà
 **/

/**
 * Client for translation memories
 */
class SC_Memory {
    private $rest_client;

    public function __construct() {
        $this->rest_client = new SC_RestClient();
    }

    public function get_generation_data() {

        $result = wp_cache_get( 'memory_generation', 'sc' );

        if ( false === $result ) {

            $base_url    = get_option( 'api_memory_base' );
            $r           = $this->rest_client->get( $base_url . '/projects' );

            if ( $r['error'] ) {
                throw_error( '500', 'Error connecting to API server' );
            }

            if ( $r['code'] == 200 ) {
                $result = json_decode( $r['result'] );
                wp_cache_set( 'memory_generation', $result, 'sc', ( 3600 * 2 ) );
            }
        }

        return $result;
    }
}