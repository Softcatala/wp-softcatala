<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Auth;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;

/**
 * Authorization-code flow with PKCE against one issuer — Keycloak here, but
 * nothing below is Keycloak-specific. Ported from Minairo's
 * minairo/auth/oidc.py.
 *
 * This class only *identifies* the user: it returns validated ID-token
 * claims and never touches WordPress users or sessions. UserResolver decides
 * who that is; LoginFlow sets the cookie.
 *
 * The ID token signature is verified against the issuer's JWKS, via
 * firebase/php-jwt.
 *
 * OIDC Core 3.1.3.7 would let a confidential client skip the signature and
 * lean on the TLS connection to the token endpoint instead. We do not,
 * because that safety would be a property of the deployment rather than of
 * this code: WP_Http's `https_ssl_verify` filter is global, so any plugin can
 * disable peer verification site-wide, and an issuer reached over an internal
 * http:// hop has no TLS to lean on at all. Either would fail open and
 * silently. Verifying the signature holds regardless of the transport.
 */
class OidcClient {

	/**
	 * Discovery and JWKS documents change rarely; a stale JWKS is repaired by
	 * the retry in decode() rather than by a short TTL.
	 */
	const METADATA_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Tolerance for clock drift between this host and the issuer.
	 */
	const LEEWAY = 60;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param Config $config Issuer configuration.
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * (verifier, S256 challenge).
	 *
	 * @return array{0:string,1:string}
	 */
	public static function generate_pkce() {
		$verifier  = wp_generate_password( 64, false );
		$challenge = self::b64url_encode( hash( 'sha256', $verifier, true ) );

		return array( $verifier, $challenge );
	}

	/**
	 * @param string $state         Opaque CSRF value.
	 * @param string $nonce         Replay guard, echoed in the ID token.
	 * @param string $code_challenge PKCE challenge.
	 * @return string
	 * @throws OidcError When discovery fails.
	 */
	public function authorization_url( $state, $nonce, $code_challenge ) {
		$metadata = $this->metadata();

		return add_query_arg(
			rawurlencode_deep(
				array(
					'response_type'         => 'code',
					'client_id'             => $this->config->client_id,
					'redirect_uri'          => Config::redirect_uri(),
					'scope'                 => 'openid email profile',
					'state'                 => $state,
					'nonce'                 => $nonce,
					'code_challenge'        => $code_challenge,
					'code_challenge_method' => 'S256',
				)
			),
			$metadata['authorization_endpoint']
		);
	}

	/**
	 * Code -> tokens -> validated ID-token claims.
	 *
	 * @param string $code          Authorization code.
	 * @param string $code_verifier PKCE verifier held in the state transient.
	 * @param string $nonce         Nonce held in the state transient.
	 * @return array Claims.
	 * @throws OidcError When the issuer is unreachable or the token is invalid.
	 */
	public function exchange_code( $code, $code_verifier, $nonce ) {
		$metadata = $this->metadata();

		$response = wp_remote_post(
			$metadata['token_endpoint'],
			array(
				'timeout' => 10,
				'headers' => array(
					// client_secret_basic: the secret stays out of the body.
					'Authorization' => 'Basic ' . base64_encode( $this->config->client_id . ':' . $this->config->client_secret ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => array(
					'grant_type'    => 'authorization_code',
					'code'          => $code,
					'redirect_uri'  => Config::redirect_uri(),
					'code_verifier' => $code_verifier,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new OidcError( 'token endpoint unreachable: ' . esc_html( $response->get_error_message() ) );
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status ) {
			throw new OidcError( 'token endpoint returned ' . esc_html( $status ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['id_token'] ) || ! is_string( $body['id_token'] ) ) {
			throw new OidcError( 'token response carries no id_token' );
		}

		$claims = $this->decode( $body['id_token'] );

		if ( ! isset( $claims['nonce'] ) || ! hash_equals( (string) $nonce, (string) $claims['nonce'] ) ) {
			throw new OidcError( 'ID token nonce mismatch' );
		}

		return $claims;
	}

	/**
	 * Where to send the browser so Keycloak drops its own session too.
	 *
	 * @param string $post_logout_uri Where Keycloak returns afterwards.
	 * @return string|null Null when the issuer advertises no end_session_endpoint.
	 */
	public function end_session_url( $post_logout_uri ) {
		try {
			$metadata = $this->metadata();
		} catch ( OidcError $e ) {
			// A logout must never fail because the issuer is unreachable.
			return null;
		}

		if ( empty( $metadata['end_session_endpoint'] ) ) {
			return null;
		}

		return add_query_arg(
			rawurlencode_deep(
				array(
					'client_id'                => $this->config->client_id,
					'post_logout_redirect_uri' => $post_logout_uri,
				)
			),
			$metadata['end_session_endpoint']
		);
	}

	/**
	 * The discovery document, cached across requests.
	 *
	 * @return array
	 * @throws OidcError When the issuer is unreachable or the token is invalid.
	 */
	public function metadata() {
		$key   = 'sc_oidc_meta_' . md5( $this->config->issuer );
		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$metadata = $this->get_json( $this->config->issuer . '/.well-known/openid-configuration' );

		foreach ( array( 'authorization_endpoint', 'token_endpoint', 'jwks_uri' ) as $required ) {
			if ( empty( $metadata[ $required ] ) ) {
				throw new OidcError( 'discovery document has no ' . esc_html( $required ) );
			}
		}

		// The issuer must not be able to redirect us at a different issuer.
		if ( ! isset( $metadata['issuer'] ) || rtrim( (string) $metadata['issuer'], '/' ) !== $this->config->issuer ) {
			throw new OidcError( 'discovery document issuer does not match SC_SSO_ISSUER' );
		}

		set_transient( $key, $metadata, self::METADATA_TTL );

		return $metadata;
	}

	/**
	 * @param bool $refresh Bypass the cache.
	 * @return array The JWKS `keys` array.
	 * @throws OidcError When the issuer is unreachable or the token is invalid.
	 */
	private function jwks( $refresh = false ) {
		$key = 'sc_oidc_jwks_' . md5( $this->config->issuer );

		if ( ! $refresh ) {
			$cached = get_transient( $key );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$document = $this->get_json( $this->metadata()['jwks_uri'] );

		if ( empty( $document['keys'] ) || ! is_array( $document['keys'] ) ) {
			throw new OidcError( 'issuer JWKS document has no keys' );
		}

		set_transient( $key, $document['keys'], self::METADATA_TTL );

		return $document['keys'];
	}

	/**
	 * Verify the signature, then the claims.
	 *
	 * @param string $id_token Compact JWS.
	 * @return array Claims.
	 * @throws OidcError When the token does not verify.
	 */
	private function decode( $id_token ) {
		// php-jwt already refuses a token whose header alg disagrees with the
		// key's (JWT.php:154), which is what stops `none` and HMAC confusion.
		// Pinning it here too keeps the intent visible and the error specific.
		$this->assert_alg( $id_token );

		try {
			$claims = $this->verified_claims( $id_token, false );
		} catch ( ExpiredException | BeforeValidException $e ) {
			// Both extend UnexpectedValueException, so they have to be caught
			// ahead of the retry below or a stale clock would refetch the JWKS.
			throw self::wrap( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- wrap() escapes.
		} catch ( SignatureInvalidException | \UnexpectedValueException $e ) {
			// One retry with fresh keys: Keycloak rotates signing keys, and a
			// cached JWKS would otherwise reject every login until it expired.
			// An unknown `kid` arrives here as UnexpectedValueException.
			try {
				$claims = $this->verified_claims( $id_token, true );
			} catch ( \Exception $retry ) {
				throw self::wrap( $retry ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- wrap() escapes.
			}
		} catch ( \Exception $e ) {
			throw self::wrap( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- wrap() escapes.
		}

		$this->assert_claims( $claims );

		return $claims;
	}

	/**
	 * @param string $id_token Compact JWS.
	 * @param bool   $refresh  Re-fetch the JWKS first.
	 * @return array Claims, as a plain array rather than php-jwt's stdClass.
	 */
	private function verified_claims( $id_token, $refresh ) {
		$keys = JWK::parseKeySet( array( 'keys' => $this->jwks( $refresh ) ), 'RS256' );

		JWT::$leeway = self::LEEWAY;

		return json_decode( wp_json_encode( JWT::decode( $id_token, $keys ) ), true );
	}

	/**
	 * @param string $id_token Compact JWS.
	 * @throws OidcError When the header names anything but RS256.
	 */
	private function assert_alg( $id_token ) {
		$parts = explode( '.', $id_token );

		if ( 3 !== count( $parts ) ) {
			throw new OidcError( 'ID token is not a compact JWS' );
		}

		$header = json_decode( JWT::urlsafeB64Decode( $parts[0] ), true );

		if ( ! is_array( $header ) || ! isset( $header['alg'] ) || 'RS256' !== $header['alg'] ) {
			throw new OidcError( 'unsupported ID token alg' );
		}
	}

	/**
	 * Wraps php-jwt's failures in one exception type with stable wording.
	 *
	 * @param \Exception $failure Underlying failure.
	 * @return OidcError
	 */
	private static function wrap( \Exception $failure ) {
		if ( $failure instanceof ExpiredException ) {
			return new OidcError( 'ID token has expired' );
		}

		if ( $failure instanceof BeforeValidException ) {
			return new OidcError( 'ID token is not yet valid' );
		}

		if ( $failure instanceof SignatureInvalidException ) {
			return new OidcError( 'ID token signature rejected' );
		}

		return new OidcError( 'ID token rejected: ' . esc_html( $failure->getMessage() ) );
	}

	/**
	 * @param array $claims Decoded payload.
	 * @throws OidcError When the issuer is unreachable or the token is invalid.
	 */
	private function assert_claims( array $claims ) {
		if ( empty( $claims['sub'] ) ) {
			throw new OidcError( 'ID token has no sub' );
		}

		if ( ! isset( $claims['iss'] ) || rtrim( (string) $claims['iss'], '/' ) !== $this->config->issuer ) {
			throw new OidcError( 'ID token issuer mismatch' );
		}

		// php-jwt checks exp against the clock, but only when it is present
		// (JWT.php:188), so a token carrying no exp would never expire.
		if ( ! isset( $claims['exp'] ) || ! is_numeric( $claims['exp'] ) ) {
			throw new OidcError( 'ID token has no exp' );
		}

		// aud may be a string or a list (RFC 7519).
		$aud       = isset( $claims['aud'] ) ? $claims['aud'] : null;
		$audiences = is_array( $aud ) ? $aud : array( $aud );

		if ( ! in_array( $this->config->client_id, $audiences, true ) ) {
			throw new OidcError( 'ID token audience mismatch' );
		}

		// With several audiences the token was not minted solely for us, so
		// azp has to name us explicitly (OIDC Core 3.1.3.7 clause 4).
		if ( count( $audiences ) > 1 && ( ! isset( $claims['azp'] ) || $claims['azp'] !== $this->config->client_id ) ) {
			throw new OidcError( 'ID token azp mismatch' );
		}
	}

	/**
	 * @param string $url Endpoint.
	 * @return array
	 * @throws OidcError When the issuer is unreachable or the token is invalid.
	 */
	private function get_json( $url ) {
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			throw new OidcError( 'issuer request failed: ' . esc_html( $url ) . ': ' . esc_html( $response->get_error_message() ) );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new OidcError( 'issuer request failed: ' . esc_html( $url ) . ': HTTP ' . esc_html( wp_remote_retrieve_response_code( $response ) ) );
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $payload ) ) {
			throw new OidcError( 'issuer returned a non-object body: ' . esc_html( $url ) );
		}

		return $payload;
	}

	/**
	 * @param string $data Raw bytes.
	 * @return string
	 */
	public static function b64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}
}
