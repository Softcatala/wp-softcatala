<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Content;


class JsonToTable
{
    public static function init() {
        new JsonToTable();
    }

    public function __construct()
    {
        add_filter( 'the_content', array($this, 'add_table') );
    }

    function add_table( $content )
    {
        if (is_main_query()) {
            return $content . $this->get_table();
        }

        return $content;
    }

    function get_table() {
        $jsonUrl = get_field( 'json_api_url' );

        if( $jsonUrl ) {
            $data = $this->get_cached_data($jsonUrl);

            if($data) {

                $titles = $data['text'];

                $html = \Timber::fetch('components/table-bordered.twig', array(
                    'keys' => array_keys($titles),
                    'titles' => $titles,
                    'rows' => $data['data']
                ));
                return $html;
            }
        }

        return '';
    }

    function get_cached_data($jsonUrl) {
        $key = 'json-api-result-'.get_the_ID();
        $result = get_transient( $key );

        if ( false === $result ) {
            $result = json_decode(file_get_contents($jsonUrl), true);
            set_transient( $key, $result, 10 * HOUR_IN_SECONDS );
        }

        return $result;
    }
}

