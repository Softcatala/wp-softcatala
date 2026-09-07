<?php
/**
 * Tests for Softcatala\Auth\OidcClient.
 *
 * These exercise the ID-token verification end to end against a real RSA
 * key: the JWK-to-PEM DER encoding is hand-rolled to avoid a Composer
 * dependency, so it needs to be proven rather than eyeballed. The issuer is
 * mocked through pre_http_request, so nothing here touches the network.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Auth\Config;
use Softcatala\Auth\OidcClient;
use Softcatala\Auth\OidcError;

/**
 * Class AuthOidcClientTest
 */
class AuthOidcClientTest extends SCTests {

	const ISSUER    = 'https://accounts.example.org/realms/softcatala';
	const CLIENT_ID = 'wp-softcatala';

	/**
	 * @var resource|\OpenSSLAsymmetricKey
	 */
	private $key;

	/**
	 * @var OidcClient
	 */
	private $client;

	/**
	 * @var string Key id served by the mocked JWKS, changed to rotate keys.
	 */
	private $kid = 'test-key-1';

	/**
	 * @var int How many times the JWKS was fetched.
	 */
	private $jwks_fetches = 0;

	/**
	 * @var array Claims served by the mocked token endpoint.
	 */
	private $claims;

	/**
	 * @var string Header served by the mocked token endpoint.
	 */
	private $header;

	public function set_up() {
		parent::set_up();

		$this->key = openssl_pkey_new(
			array(
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			)
		);

		$this->client = new OidcClient( new Config( self::ISSUER, self::CLIENT_ID, 'secret' ) );

		// kid is merged in at signing time so rotation is picked up.
		$this->header = array( 'alg' => 'RS256' );

		$this->claims = array(
			'sub'            => 'a1b2-c3d4',
			'iss'            => self::ISSUER,
			'aud'            => self::CLIENT_ID,
			'exp'            => time() + 300,
			'iat'            => time(),
			'nonce'          => 'test-nonce',
			'email'          => 'jordi@example.org',
			'email_verified' => true,
		);

		delete_transient( 'sc_oidc_meta_' . md5( self::ISSUER ) );
		delete_transient( 'sc_oidc_jwks_' . md5( self::ISSUER ) );

		add_filter( 'pre_http_request', array( $this, 'mock_issuer' ), 10, 3 );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'mock_issuer' ), 10 );
		parent::tear_down();
	}

	/**
	 * Stands in for the whole issuer.
	 *
	 * @param mixed  $preempt Short-circuit value.
	 * @param array  $args    Request args.
	 * @param string $url     Request URL.
	 * @return array|mixed
	 */
	public function mock_issuer( $preempt, $args, $url ) {
		// pre_http_request is a filter, not a short circuit: every callback
		// runs and the last non-false value wins. A test that installs its own
		// response at an earlier priority must not have it overwritten here.
		if ( false !== $preempt ) {
			return $preempt;
		}

		if ( false !== strpos( $url, '.well-known/openid-configuration' ) ) {
			return $this->json_response(
				array(
					'issuer'                 => self::ISSUER,
					'authorization_endpoint' => self::ISSUER . '/protocol/openid-connect/auth',
					'token_endpoint'         => self::ISSUER . '/protocol/openid-connect/token',
					'jwks_uri'               => self::ISSUER . '/protocol/openid-connect/certs',
					'end_session_endpoint'   => self::ISSUER . '/protocol/openid-connect/logout',
				)
			);
		}

		if ( false !== strpos( $url, '/certs' ) ) {
			++$this->jwks_fetches;
			return $this->json_response( array( 'keys' => array( $this->jwk() ) ) );
		}

		if ( false !== strpos( $url, '/token' ) ) {
			return $this->json_response( array( 'id_token' => $this->id_token() ) );
		}

		return $preempt;
	}

	/**
	 * @param array $body Response payload.
	 * @return array
	 */
	private function json_response( array $body ) {
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * @return array The public key as a JWKS entry.
	 */
	private function jwk() {
		$details = openssl_pkey_get_details( $this->key );

		return array(
			'kty' => 'RSA',
			'use' => 'sig',
			'alg' => 'RS256',
			'kid' => $this->kid,
			'n'   => OidcClient::b64url_encode( $details['rsa']['n'] ),
			'e'   => OidcClient::b64url_encode( $details['rsa']['e'] ),
		);
	}

	/**
	 * @return string A compact JWS over the current header and claims.
	 */
	private function id_token() {
		$header = array_merge( $this->header, array( 'kid' => $this->kid ) );

		$input = OidcClient::b64url_encode( wp_json_encode( $header ) )
			. '.' . OidcClient::b64url_encode( wp_json_encode( $this->claims ) );

		$signature = '';
		openssl_sign( $input, $signature, $this->key, OPENSSL_ALGO_SHA256 );

		return $input . '.' . OidcClient::b64url_encode( $signature );
	}

	/**
	 * The happy path, and with it the DER encoding of the public key.
	 */
	public function test_valid_token_is_accepted() {
		$claims = $this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );

		$this->assertEquals( 'a1b2-c3d4', $claims['sub'] );
		$this->assertEquals( 'jordi@example.org', $claims['email'] );
		$this->assertTrue( $claims['email_verified'] );
	}

	public function test_tampered_payload_is_rejected() {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( false === strpos( $url, '/token' ) ) {
					return $preempt;
				}

				// Re-sign nothing: swap the payload under a valid signature.
				list( $header, , $signature ) = explode( '.', $this->id_token() );
				$forged = wp_json_encode( array_merge( $this->claims, array( 'sub' => 'attacker' ) ) );

				return $this->json_response(
					array( 'id_token' => $header . '.' . OidcClient::b64url_encode( $forged ) . '.' . $signature )
				);
			},
			9,
			3
		);

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'signature' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
	}

	/**
	 * Taking `alg` from the token is how `none` and HMAC confusion get in.
	 */
	public function test_alg_none_is_rejected() {
		$this->header['alg'] = 'none';

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'unsupported ID token alg' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
	}

	public function test_wrong_audience_is_rejected() {
		$this->claims['aud'] = 'un-altre-client';

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'audience' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
	}

	public function test_wrong_issuer_is_rejected() {
		$this->claims['iss'] = 'https://evil.example.org/realms/softcatala';

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'issuer mismatch' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
	}

	public function test_expired_token_is_rejected() {
		$this->claims['exp'] = time() - 3600;

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'expired' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
	}

	public function test_nonce_mismatch_is_rejected() {
		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'nonce' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'un-altre-nonce' );
	}

	/**
	 * Several audiences means the token was not minted solely for us.
	 */
	public function test_multiple_audiences_require_azp() {
		$this->claims['aud'] = array( self::CLIENT_ID, 'una-altra-app' );

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'azp' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
	}

	public function test_multiple_audiences_pass_with_matching_azp() {
		$this->claims['aud'] = array( self::CLIENT_ID, 'una-altra-app' );
		$this->claims['azp'] = self::CLIENT_ID;

		$claims = $this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );

		$this->assertEquals( 'a1b2-c3d4', $claims['sub'] );
	}

	/**
	 * A discovery document naming a different issuer must not be able to
	 * redirect us at endpoints we did not configure.
	 */
	public function test_discovery_issuer_must_match_configuration() {
		delete_transient( 'sc_oidc_meta_' . md5( self::ISSUER ) );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( false === strpos( $url, '.well-known' ) ) {
					return $preempt;
				}

				return $this->json_response(
					array(
						'issuer'                 => 'https://evil.example.org/',
						'authorization_endpoint' => 'https://evil.example.org/auth',
						'token_endpoint'         => 'https://evil.example.org/token',
						'jwks_uri'               => 'https://evil.example.org/certs',
					)
				);
			},
			9,
			3
		);

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'issuer does not match' );

		$this->client->metadata();
	}

	public function test_authorization_url_carries_pkce_and_state() {
		list( $verifier, $challenge ) = OidcClient::generate_pkce();

		$this->assertEquals( $challenge, OidcClient::b64url_encode( hash( 'sha256', $verifier, true ) ) );

		$url = $this->client->authorization_url( 'the-state', 'the-nonce', $challenge );

		$this->assertStringContainsString( 'response_type=code', $url );
		$this->assertStringContainsString( 'code_challenge_method=S256', $url );
		$this->assertStringContainsString( 'state=the-state', $url );
		$this->assertStringContainsString( 'nonce=the-nonce', $url );
		$this->assertStringContainsString( 'client_id=' . self::CLIENT_ID, $url );
	}

	/**
	 * php-jwt validates exp only when it is present (JWT.php:188), so a token
	 * carrying none would never expire. That check has to stay ours.
	 */
	public function test_token_without_exp_is_rejected() {
		unset( $this->claims['exp'] );

		$this->expectException( OidcError::class );
		$this->expectExceptionMessage( 'no exp' );

		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
	}

	/**
	 * Keycloak rotates signing keys. A cached JWKS would otherwise reject
	 * every login until the transient expired, so an unknown kid must trigger
	 * exactly one refetch.
	 */
	public function test_rotated_signing_key_is_refetched() {
		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
		$this->assertEquals( 1, $this->jwks_fetches, 'the first login fetches the JWKS once' );

		$this->key = openssl_pkey_new(
			array(
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			)
		);
		$this->kid = 'test-key-2';

		$claims = $this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );

		$this->assertEquals( 'a1b2-c3d4', $claims['sub'] );
		$this->assertEquals( 2, $this->jwks_fetches, 'the rotated key is refetched exactly once' );
	}

	/**
	 * The retry is for rotation, not for forgery: a bad signature must not
	 * turn into an unbounded refetch loop, and must still fail.
	 */
	public function test_retry_does_not_rescue_a_bad_signature() {
		$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
		$before = $this->jwks_fetches;

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( false === strpos( $url, '/token' ) ) {
					return $preempt;
				}

				list( $header, $payload ) = explode( '.', $this->id_token() );

				return $this->json_response(
					array( 'id_token' => $header . '.' . $payload . '.' . OidcClient::b64url_encode( 'not-a-signature' ) )
				);
			},
			9,
			3
		);

		try {
			$this->client->exchange_code( 'the-code', 'the-verifier', 'test-nonce' );
			$this->fail( 'expected OidcError' );
		} catch ( OidcError $e ) {
			$this->assertStringContainsString( 'signature', $e->getMessage() );
			$this->assertEquals( $before + 1, $this->jwks_fetches, 'exactly one retry' );
		}
	}
}
