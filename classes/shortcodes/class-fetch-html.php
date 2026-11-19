<?php
/**
 * [fetch-html url="..."] shortcode: fetches a remote URL server-side and
 * injects the response into the page.
 *
 * Usage:
 *   [fetch-html url="https://example.com/fragment.html"]
 *   [fetch-html url="https://example.com/fragment.html" cache="0"]
 *   [fetch-html url="https://example.com/fragment.html" raw="1"]
 *
 * Security:
 * - Uses wp_safe_remote_get(), which rejects requests to loopback/private/
 *   link-local hosts (SSRF guard) and only allows http/https URLs.
 * - Responses are cached in a transient (default 1h, see "cache" attribute
 *   in seconds, 0 disables caching) so a slow or malicious remote URL can't
 *   be used to hammer the site on every page view.
 * - By default the fetched body is passed through wp_kses_post() before
 *   being output, stripping <script> tags, event handler attributes, etc.
 *   Pass raw="1" to skip this -- only do so for URLs you fully trust, since
 *   it is equivalent to pasting arbitrary HTML/JS into the page for every
 *   visitor who loads it.
 */
if ( ! class_exists( 'SC_Shortcodes_FetchHtml' ) ) :
class SC_Shortcodes_FetchHtml {

	public function __construct( $shortcodes_handler = null ) {
		if ( $shortcodes_handler !== null ) {
			$shortcodes_handler->add( 'fetch-html', array( $this, 'shortcode' ), false );
		}
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'   => '',
				'cache' => 3600,
				'raw'   => '0',
			),
			$atts,
			'fetch-html'
		);

		$url = esc_url_raw( trim( $atts['url'] ) );

		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return '';
		}

		$cache_ttl = max( 0, (int) $atts['cache'] );
		$cache_key = 'sc_fetch_html_' . md5( $url . '|' . $atts['raw'] );

		if ( $cache_ttl > 0 ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $content_type && false === strpos( $content_type, 'html' ) && false === strpos( $content_type, 'text' ) ) {
			return '';
		}

		$body = wp_remote_retrieve_body( $response );

		if ( '1' !== $atts['raw'] ) {
			$body = wp_kses_post( $body );
		}

		$output = '<div class="sc-fetch-html">' . $body . '</div>';

		if ( $cache_ttl > 0 ) {
			set_transient( $cache_key, $output, $cache_ttl );
		}

		return $output;
	}
}
endif;
