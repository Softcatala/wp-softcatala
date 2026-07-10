<?php

namespace Softcatala\Sitemaps;

class Conjugador extends DictionarySitemap {

    protected function slug() {
        return 'conjugador';
    }

    protected function keys() {
        return range( 'A', 'Z' );
    }

    protected function use_api_key() {
        return true;
    }

    protected function api_url( $key ) {
        $url_api = get_option( 'api_conjugador' );

        return $url_api . 'index/' . strtolower( $key );
    }

    protected function word_url( $key, $word ) {
        return '/conjugador-de-verbs/verb/' . $word . '/';
    }

    protected function extract_words( $raw_json ) {
        $api_result = json_decode( $raw_json, true );

        if ( ! is_array( $api_result ) ) {
            return [];
        }

        return array_map(
            function ( $verb ) {
                return ! empty( $verb['infinitive'] ) ? $verb['infinitive'] : $verb['verb_form'];
            },
            $api_result
        );
    }
}
