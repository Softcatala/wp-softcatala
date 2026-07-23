<?php

namespace Softcatala\Sitemaps;

class Sinonims extends DictionarySitemap {

    protected function slug() {
        return 'sinonims';
    }

    protected function keys() {
        return range( 'A', 'Z' );
    }

    protected function use_api_key() {
        return true;
    }

    protected function api_url( $key ) {
        $url_api = get_option( 'api_diccionari_sinonims' );

        return $url_api . 'index/' . strtolower( $key );
    }

    protected function word_url( $key, $word ) {
        return '/diccionari-de-sinonims/paraula/' . $word . '/';
    }

    protected function extract_words( $raw_json ) {
        $api_result = json_decode( $raw_json );

        return isset( $api_result->words ) ? $api_result->words : [];
    }
}
