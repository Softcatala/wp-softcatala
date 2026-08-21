<?php
/**
 * Serves the curated LLM discovery documents.
 *
 * @package Softcatala
 */

namespace Softcatala\Content;

/**
 * Owns the llms.txt routes and their HTTP responses.
 */
final class LlmsEndpoint {

	private const QUERY_VAR = 'sc_llms';

	private const DOCUMENTS = array(
		'summary' => 'llms.txt',
		'full'    => 'llms-full.txt',
	);

	private const PRODUCTION_ORIGIN = 'https://www.softcatala.org';

	/**
	 * Registers routing and prevents Yoast from creating a competing file.
	 */
	public static function init() {
		add_action( 'init', array( self::class, 'register_rewrites' ) );
		add_filter( 'query_vars', array( self::class, 'register_query_var' ) );
		add_action( 'parse_request', array( self::class, 'maybe_serve' ), 0 );

		add_filter( 'option_wpseo', array( self::class, 'disable_yoast_generator' ), PHP_INT_MAX );
		add_filter( 'pre_update_option_wpseo', array( self::class, 'disable_yoast_generator' ), PHP_INT_MAX );
	}

	/**
	 * Registers exact root-level routes.
	 */
	public static function register_rewrites() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=summary', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?' . self::QUERY_VAR . '=full', 'top' );
	}

	/**
	 * @param array $query_vars Public query variables.
	 * @return array
	 */
	public static function register_query_var( $query_vars ) {
		$query_vars[] = self::QUERY_VAR;

		return $query_vars;
	}

	/**
	 * Forces Yoast's physical llms.txt generator off so that there is one owner.
	 *
	 * @param mixed $options Yoast's wpseo option value.
	 * @return mixed
	 */
	public static function disable_yoast_generator( $options ) {
		if ( is_array( $options ) ) {
			$options['enable_llms_txt'] = false;
		}

		return $options;
	}

	/**
	 * Serves a document only when both the rewrite and exact path match.
	 *
	 * @param \WP $wp Current WordPress environment.
	 */
	public static function maybe_serve( $wp ) {
		$document = $wp->query_vars[ self::QUERY_VAR ] ?? '';

		if ( ! self::matches_request( $wp->request, $document ) ) {
			return;
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'get';
		$method = strtoupper( $method );
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			status_header( 405 );
			header( 'Allow: GET, HEAD' );
			header( 'Cache-Control: no-store' );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			header( 'X-Content-Type-Options: nosniff' );
			exit;
		}

		$path = self::source_path( $document );
		$body = self::document( $document );
		if ( '' === $body || ! is_readable( $path ) ) {
			status_header( 404 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo 'Not found' . "\n";
			exit;
		}

		$modified = (int) filemtime( $path );
		$etag     = '"' . hash( 'sha256', $body ) . '"';

		self::send_headers( $etag, $modified );

		if ( self::is_not_modified( $etag, $modified ) ) {
			status_header( 304 );
			exit;
		}

		status_header( 200 );
		if ( 'HEAD' !== $method ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Curated UTF-8 plain text, not HTML.
			echo $body;
		}

		exit;
	}

	/**
	 * @param string $request WordPress request path.
	 * @param string $document Requested document key.
	 * @return bool
	 */
	public static function matches_request( $request, $document ) {
		if ( ! isset( self::DOCUMENTS[ $document ] ) ) {
			return false;
		}

		return trim( (string) $request, '/' ) === self::DOCUMENTS[ $document ];
	}

	/**
	 * Returns the editorial source with the active site's origin.
	 *
	 * @param string $document Document key.
	 * @return string
	 */
	public static function document( $document ) {
		$path = self::source_path( $document );
		if ( ! is_readable( $path ) ) {
			return '';
		}

		$body = (string) file_get_contents( $path );
		$body = str_replace( self::PRODUCTION_ORIGIN, untrailingslashit( home_url() ), $body );

		return rtrim( str_replace( "\r\n", "\n", $body ) ) . "\n";
	}

	/**
	 * @param string $document Document key.
	 * @return string
	 */
	private static function source_path( $document ) {
		if ( ! isset( self::DOCUMENTS[ $document ] ) ) {
			return '';
		}

		return get_template_directory() . '/resources/' . self::DOCUMENTS[ $document ];
	}

	/**
	 * @param string $etag Current entity tag.
	 * @param int    $modified Source modification timestamp.
	 */
	private static function send_headers( $etag, $modified ) {
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'Content-Language: ca' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: public, max-age=300, s-maxage=900, must-revalidate' );
		header( 'ETag: ' . $etag );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $modified ) . ' GMT' );
		header( 'Link: <' . esc_url_raw( home_url( '/llms.txt' ) ) . '>; rel="describedby"; type="text/plain"' );
	}

	/**
	 * @param string $etag Current entity tag.
	 * @param int    $modified Source modification timestamp.
	 * @return bool
	 */
	private static function is_not_modified( $etag, $modified ) {
		$if_none_match = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) : '';
		if ( '' !== $if_none_match ) {
			$accepted_etags = array_map( 'trim', explode( ',', $if_none_match ) );
			$current_etag   = trim( $etag, '"' );

			foreach ( $accepted_etags as $accepted_etag ) {
				if ( '*' === $accepted_etag ) {
					return true;
				}

				$candidate = preg_replace( '/^W\//i', '', $accepted_etag );
				$candidate = trim( $candidate, '"' );
				if ( hash_equals( $current_etag, $candidate ) ) {
					return true;
				}
			}

			return false;
		}

		$if_modified_since = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) : '';
		if ( '' === $if_modified_since ) {
			return false;
		}

		$client_timestamp = strtotime( $if_modified_since );

		return false !== $client_timestamp && $client_timestamp >= $modified;
	}
}
