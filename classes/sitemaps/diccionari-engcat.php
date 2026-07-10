<?php

namespace Softcatala\Sitemaps;

class DiccionariEngcat extends DictionarySitemap {

    const LLENGUES = [ 'eng', 'cat' ];

    protected function slug() {
        return 'diccionari-engcat';
    }

    protected function keys() {
        $keys = [];

        foreach ( self::LLENGUES as $llengua ) {
            foreach ( range( 'A', 'Z' ) as $lletra ) {
                $keys[] = $llengua . '-' . $lletra;
            }
        }

        return $keys;
    }

    protected function key_regex() {
        return '(?:' . implode( '|', self::LLENGUES ) . ')-[A-Z]';
    }

    protected function api_url( $key ) {
        list( $llengua, $lletra ) = $this->parse_key( $key );

        $url_api = get_option( 'api_diccionari_engcat' );

        return $url_api . 'index/' . $llengua . '-' . strtolower( $lletra );
    }

    protected function word_url( $key, $word ) {
        list( $llengua ) = $this->parse_key( $key );

        return '/diccionari-angles-catala/' . $llengua . '/paraula/' . $word . '/';
    }

    protected function extract_words( $raw_json ) {
        $api_result = json_decode( $raw_json );

        return isset( $api_result->words ) ? $api_result->words : [];
    }

    private function parse_key( $key ) {
        return explode( '-', $key, 2 );
    }
}
