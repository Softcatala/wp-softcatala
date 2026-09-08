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
        $slug   = $this->slug();

        foreach ( $this->keys() as $key ) {
			$known_words = get_transient( $this->stale_cache_key( $key ) );
			if ( is_array( $known_words ) && empty( $known_words ) ) {
				continue;
			}

            $sitemap_custom_items .= "
                <sitemap>
                <loc>$domain/sitemaps/$slug-$key.xml</loc>
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

		$words = $this->words_for_key( $key );
		if ( is_wp_error( $words ) ) {
			$this->return500();
		}

        $domain = home_url();

        header( 'Content-Type: text/xml; charset=UTF-8' );
		$xsl = esc_url( home_url( '/main-sitemap.xsl' ) );
		echo '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="' . $xsl . '"?>';
        echo "\n";
        echo '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        echo "\n";

        foreach ( $words as $word ) {
            $u   = rawurlencode( $word );
            $loc = $domain . $this->word_url( $key, $u );
            echo '<url><loc>' . esc_url( $loc ) . "</loc></url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Keeps sitemap URLs canonical and resolvable by WordPress rewrites.
     * Dataset APIs do not currently expose a trustworthy modification date,
     * so the sitemap deliberately omits lastmod instead of inventing one.
     */
    protected function normalise_words( $words ) {
        $normalised = [];

        foreach ( (array) $words as $word ) {
            $word = trim( (string) $word );

            // Encoded slashes are decoded before WordPress matches rewrites.
            if ( '' === $word || str_contains( $word, '/' ) ) {
                continue;
            }

            $canonical_key = mb_strtolower( $word, 'UTF-8' );
            if ( isset( $normalised[ $canonical_key ] ) ) {
                continue;
            }

            $normalised[ $canonical_key ] = $word;
        }

        return array_values( $normalised );
    }

	/**
	 * Returns a cached, validated word list and falls back to the last known
	 * valid copy when the upstream API is temporarily unavailable.
	 *
	 * @param string $key Sitemap key.
	 * @return array|\WP_Error
	 */
	protected function words_for_key( $key ) {
		$cached = get_transient( $this->fresh_cache_key( $key ) );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = $this->rest_client->get( $this->api_url( $key ), $this->use_api_key() );
		$valid_response =
			empty( $result['error'] ) &&
			200 === (int) ( $result['code'] ?? 0 ) &&
			isset( $result['result'] ) &&
			is_string( $result['result'] );

		if ( $valid_response ) {
			json_decode( $result['result'] );
			$valid_response = JSON_ERROR_NONE === json_last_error();
		}

		if ( $valid_response ) {
			$words = $this->normalise_words( $this->extract_words( $result['result'] ) );
			set_transient( $this->fresh_cache_key( $key ), $words, 6 * HOUR_IN_SECONDS );
			set_transient( $this->stale_cache_key( $key ), $words, 7 * DAY_IN_SECONDS );

			return $words;
		}

		$stale = get_transient( $this->stale_cache_key( $key ) );
		if ( is_array( $stale ) ) {
			return $stale;
		}

		return new \WP_Error( 'sc_sitemap_api_error', 'Error connecting to API server' );
	}

	/** @param string $key Sitemap key. */
	private function fresh_cache_key( $key ) {
		return 'sc_sitemap_' . md5( $this->slug() . ':' . $key ) . '_fresh';
	}

	/** @param string $key Sitemap key. */
	private function stale_cache_key( $key ) {
		return 'sc_sitemap_' . md5( $this->slug() . ':' . $key ) . '_stale';
	}

    protected function return500() {
        throw_error( '500', 'Error connecting to API server' );
        exit;
    }
}
