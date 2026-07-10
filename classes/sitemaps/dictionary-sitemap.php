<?php

namespace Softcatala\Sitemaps;

/**
 * Shared logic for sitemaps that list dynamically generated dictionary/index
 * pages (one word per URL), split into per-letter (or per-letter-per-language)
 * sitemap files fetched on demand from a REST API.
 */
abstract class DictionarySitemap {

    protected $rest_client;

    public function __construct( $client = null ) {
        if ( null != $client ) {
            $this->rest_client = $client;
        } else {
            $this->rest_client = new \SC_RestClient();
        }
    }

    /**
     * Slug used both as the sitemap file prefix (sitemaps/{slug}-{key}.xml)
     * and as the sc_sitemaps dispatch value.
     */
    abstract protected function slug();

    /**
     * All index keys this sitemap is split into, e.g. ['A', ..., 'Z'] or
     * ['eng-A', ..., 'cat-Z'] when a language dimension is involved.
     */
    abstract protected function keys();

    /**
     * REST API URL returning the word list for a given key.
     */
    abstract protected function api_url( $key );

    /**
     * Front-end path (relative to home_url()) for a single word belonging
     * to a given key.
     */
    abstract protected function word_url( $key, $word );

    /**
     * Turns the raw API response body for a key into a flat list of words.
     */
    abstract protected function extract_words( $raw_json );

    protected function use_api_key() {
        return false;
    }

    /**
     * Regex fragment matching a single key in the rewrite rule.
     */
    protected function key_regex() {
        return '[A-Z]';
    }

    protected function query_var() {
        return 'sc_sitemap_' . str_replace( '-', '_', $this->slug() ) . '_key';
    }

    public function sitemap_index() {
        $sitemap_custom_items = '';

        $domain = home_url();
        $time   = $this->weekly_lastmod();
        $slug   = $this->slug();

        foreach ( $this->keys() as $key ) {

            $sitemap_custom_items .= "
                <sitemap>
                <loc>$domain/sitemaps/$slug-$key.xml</loc>
                <lastmod>$time</lastmod>
                </sitemap>";
        }

        return $sitemap_custom_items;
    }

    public function query_vars() {
        return [ $this->query_var() ];
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^sitemaps/' . $this->slug() . '-(' . $this->key_regex() . ').xml$',
            'index.php?sc_sitemaps=' . $this->slug() . '&' . $this->query_var() . '=$matches[1]',
            'top'
        );
    }

    public function maybe_render() {

        if ( get_query_var( 'sc_sitemaps' ) !== $this->slug() ) {
            return;
        }

        $key = get_query_var( $this->query_var() );

        if ( ! $key ) {
            return;
        }

        $result = $this->rest_client->get( $this->api_url( $key ), $this->use_api_key() );

        if ( $result['error'] ) {
            $this->return500();
        }

        if ( 200 != $result['code'] || ! isset( $result['result'] ) ) {
            return;
        }

        $words = $this->extract_words( $result['result'] );

        $domain = home_url();
        $time   = $this->weekly_lastmod();

        header( 'Content-Type: text/xml; charset=UTF-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="//www.softcatala.org/main-sitemap.xsl"?>';
        echo "\n";
        echo '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        echo "\n";

        foreach ( $words as $word ) {
            $u   = rawurlencode( $word );
            $loc = $domain . $this->word_url( $key, $u );
            echo "<url><loc>$loc</loc><lastmod>$time</lastmod></url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * ISO-8601 timestamp of the Monday of the current week, so <lastmod>
     * stays stable within a week but still advances week to week.
     */
    protected function weekly_lastmod() {
        return date( 'c', strtotime( 'monday this week' ) );
    }

    protected function return500() {
        throw_error( '500', 'Error connecting to API server' );
        exit;
    }
}
