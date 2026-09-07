<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Auth;

/**
 * The OIDC issuer this site talks to, read from wp-config constants.
 *
 * Mirrors OIDCConfig in Minairo's minairo/auth/oidc.py: SSO is configured
 * if and only if issuer, client id and client secret are all present.
 * Incomplete configuration is not an error here — it is what makes
 * Mode::current() refuse to leave DISABLED, so a half-filled .env can never
 * put the site into ENFORCED and lock everybody out.
 */
class Config {

	/**
	 * @var string Issuer URL, no trailing slash.
	 */
	public $issuer;

	/**
	 * @var string
	 */
	public $client_id;

	/**
	 * @var string
	 */
	public $client_secret;

	/**
	 * @param string $issuer        Issuer URL.
	 * @param string $client_id     Client id.
	 * @param string $client_secret Client secret.
	 */
	public function __construct( $issuer, $client_id, $client_secret ) {
		$this->issuer        = rtrim( $issuer, '/' );
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
	}

	/**
	 * @return Config|null Null when the site is not configured for SSO.
	 */
	public static function from_constants() {
		if ( ! defined( 'SC_SSO_ISSUER' ) || ! defined( 'SC_SSO_CLIENT_ID' ) || ! defined( 'SC_SSO_CLIENT_SECRET' ) ) {
			return null;
		}

		if ( ! SC_SSO_ISSUER || ! SC_SSO_CLIENT_ID || ! SC_SSO_CLIENT_SECRET ) {
			return null;
		}

		return new Config( SC_SSO_ISSUER, SC_SSO_CLIENT_ID, SC_SSO_CLIENT_SECRET );
	}

	/**
	 * Where Keycloak sends the browser back.
	 *
	 * The callback rides wp-login.php's ?action dispatch, which fires
	 * login_form_{action} for anything core does not recognise. That needs no
	 * rewrite rule and stays correct however core is mounted — this install
	 * serves WordPress from /wp/.
	 *
	 * @return string
	 */
	public static function redirect_uri() {
		return add_query_arg( 'action', LoginFlow::ACTION_CALLBACK, wp_login_url() );
	}
}
