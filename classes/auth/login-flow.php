<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Auth;

use WP_Error;
use WP_User;

/**
 * Wires the OIDC flow into wp-login.php and enforces the mode.
 *
 * The two routes live on wp-login.php's ?action dispatch rather than on a
 * rewrite rule: core fires login_form_{action} for any action it does not
 * recognise, which needs no flushed rewrites and stays correct with
 * WordPress mounted under /wp/ as it is here.
 */
class LoginFlow {

	const ACTION_START    = 'sc-oidc-start';
	const ACTION_CALLBACK = 'sc-oidc-callback';

	/**
	 * How long a browser has to come back from Keycloak.
	 */
	const STATE_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * @return void
	 */
	public static function init() {
		$flow = new LoginFlow();

		add_action( 'admin_notices', array( $flow, 'render_downgrade_notice' ) );

		if ( ! Mode::is_enabled() ) {
			// DISABLED registers no routes at all, so the callback is not
			// merely hidden but absent.
			return;
		}

		add_action( 'login_form_' . self::ACTION_START, array( $flow, 'start' ) );
		add_action( 'login_form_' . self::ACTION_CALLBACK, array( $flow, 'callback' ) );
		add_action( 'login_form', array( $flow, 'render_button' ) );
		add_action( 'wp_logout', array( $flow, 'logout' ), 10, 1 );

		if ( Mode::is_enforced() ) {
			add_action( 'login_init', array( $flow, 'enforce' ) );
			add_filter( 'wp_authenticate_user', array( $flow, 'block_password_login' ), 10, 1 );
			add_filter( 'allow_password_reset', '__return_false' );
		}
	}

	/**
	 * @return OidcClient
	 * @throws OidcError When SSO is not configured.
	 */
	private function client() {
		$config = Config::from_constants();

		if ( null === $config ) {
			throw new OidcError( 'SSO is not configured' );
		}

		return new OidcClient( $config );
	}

	/**
	 * Send the browser to Keycloak.
	 *
	 * @return void
	 */
	public function start() {
		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		try {
			$state = wp_generate_password( 32, false );
			$nonce = wp_generate_password( 32, false );

			list( $verifier, $challenge ) = OidcClient::generate_pkce();

			set_transient(
				$this->state_key( $state ),
				array(
					'nonce'       => $nonce,
					'verifier'    => $verifier,
					'redirect_to' => $redirect_to,
				),
				self::STATE_TTL
			);

			$url = $this->client()->authorization_url( $state, $nonce, $challenge );
		} catch ( OidcError $e ) {
			$this->fail( 'issuer_unreachable', $e );
			return;
		}

		wp_redirect( $url );
		exit;
	}

	/**
	 * Handle Keycloak's redirect back.
	 *
	 * @return void
	 */
	public function callback() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// `state` IS the CSRF token here: it was minted in start(), stored
		// server-side, and is consumed exactly once below.
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' === $state ) {
			$this->fail( 'bad_state' );
			return;
		}

		$stored = get_transient( $this->state_key( $state ) );

		// Single use, whatever happens next: a replayed code must not find a
		// live verifier waiting for it.
		delete_transient( $this->state_key( $state ) );

		if ( ! is_array( $stored ) ) {
			$this->fail( 'bad_state' );
			return;
		}

		if ( '' !== $error ) {
			$this->fail( 'issuer_refused' );
			return;
		}

		if ( '' === $code ) {
			$this->fail( 'bad_state' );
			return;
		}

		try {
			$claims = $this->client()->exchange_code( $code, $stored['verifier'], $stored['nonce'] );
		} catch ( OidcError $e ) {
			$this->fail( 'issuer_unreachable', $e );
			return;
		}

		$resolver = new UserResolver();

		try {
			list( $user, $event ) = $resolver->resolve( $claims );
		} catch ( SsoDenied $e ) {
			$this->log( sprintf( 'denied (%s) for sub %s', $e->reason(), isset( $claims['sub'] ) ? $claims['sub'] : '?' ) );
			$this->fail( $e->reason() );
			return;
		}

		$this->log( sprintf( '%s: user %d (%s)', $event, $user->ID, $user->user_login ) );

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );
		do_action( 'wp_login', $user->user_login, $user );

		$redirect_to = ! empty( $stored['redirect_to'] ) ? $stored['redirect_to'] : admin_url();

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * ENFORCED: the password form is not merely refused but never shown.
	 *
	 * @return void
	 */
	public function enforce() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : 'login';

		$passthrough = array(
			self::ACTION_START,
			self::ACTION_CALLBACK,
			// Carries its own nonce, and logout() handles the Keycloak side.
			'logout',
			// Password-protected posts post here. Anonymous visitors have no
			// business at Keycloak, and redirecting would break the form.
			'postpass',
			// Privacy request confirmation links, which carry their own key.
			'confirmaction',
		);

		if ( in_array( $action, $passthrough, true ) ) {
			return;
		}

		// After a logout that could not reach Keycloak's end_session_endpoint,
		// bouncing straight back would hand the browser to a live Keycloak
		// session and log the user in again. Show the page instead.
		if ( isset( $_GET['loggedout'] ) ) {
			return;
		}

		// The expired-session modal is an iframe; Keycloak refuses to render
		// inside one, so a redirect here is a blank box the user cannot escape.
		// Showing the form leaves them the button.
		if ( isset( $_REQUEST['interim-login'] ) ) {
			return;
		}

		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		wp_redirect( $this->start_url( $redirect_to ) );
		exit;
	}

	/**
	 * ENFORCED: refuse password authentication for everybody.
	 *
	 * Deliberately not "everybody except a break-glass account": the recovery
	 * path is SC_SSO_MODE=ENABLED in the environment, which leaves no
	 * permanently password-reachable account behind.
	 *
	 * @param WP_User|WP_Error $user Result so far.
	 * @return WP_User|WP_Error
	 */
	public function block_password_login( $user ) {
		if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
			return $user;
		}

		// Application Passwords are how the tasques endpoints authenticate and
		// Keycloak has no equivalent for them, so that path stays open.
		if ( $this->is_application_password_request() ) {
			return $user;
		}

		return new WP_Error(
			'sc_sso_enforced',
			__( '<strong>Error</strong>: en aquest lloc cal iniciar la sessió amb el compte de Softcatalà.', 'softcatala' )
		);
	}

	/**
	 * @return bool
	 */
	private function is_application_password_request() {
		$is_api_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST );

		return (bool) apply_filters( 'application_password_is_api_request', $is_api_request );
	}

	/**
	 * End the Keycloak session too, otherwise "log out" only drops the local
	 * cookie and the next login is silently automatic.
	 *
	 * @param int $user_id User logging out.
	 * @return void
	 */
	public function logout( $user_id = 0 ) {
		if ( ! $user_id || ! get_user_meta( $user_id, UserResolver::SUBJECT_META, true ) ) {
			return;
		}

		try {
			$url = $this->client()->end_session_url( home_url() );
		} catch ( OidcError $e ) {
			return;
		}

		if ( null === $url ) {
			return;
		}

		wp_redirect( $url );
		exit;
	}

	/**
	 * @return void
	 */
	public function render_button() {
		printf(
			'<p class="sc-sso-login"><a class="button button-large" href="%s">%s</a></p>',
			esc_url( $this->start_url() ),
			esc_html__( 'Entra amb el compte de Softcatalà', 'softcatala' )
		);
	}

	/**
	 * @return void
	 */
	public function render_downgrade_notice() {
		$reason = Mode::downgrade_reason();

		if ( null === $reason || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$message = 'unknown_value' === $reason
			? sprintf(
				/* translators: %s: the configured SC_SSO_MODE value. */
				__( 'SC_SSO_MODE té un valor desconegut (%s). L\'inici de sessió amb Keycloak està desactivat.', 'softcatala' ),
				Mode::declared()
			)
			: __( 'SC_SSO_MODE està activat però falta la configuració de Keycloak (SC_SSO_ISSUER, SC_SSO_CLIENT_ID, SC_SSO_CLIENT_SECRET). L\'inici de sessió amb Keycloak està desactivat.', 'softcatala' );

		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
	}

	/**
	 * @param string $redirect_to Where to land after a successful login.
	 * @return string
	 */
	private function start_url( $redirect_to = '' ) {
		$url = add_query_arg( 'action', self::ACTION_START, wp_login_url() );

		if ( $redirect_to ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
		}

		return $url;
	}

	/**
	 * @param string $state Opaque state value.
	 * @return string
	 */
	private function state_key( $state ) {
		return 'sc_oidc_state_' . $state;
	}

	/**
	 * Stop the login with an explanation.
	 *
	 * A page rather than a redirect back to wp-login.php, which under ENFORCED
	 * would bounce straight back to Keycloak and loop.
	 *
	 * @param string          $reason  Denial or error slug.
	 * @param \Exception|null $failure Underlying failure, for the log only.
	 * @return void
	 */
	private function fail( $reason, $failure = null ) {
		if ( $failure ) {
			$this->log( $reason . ': ' . $failure->getMessage() );
		}

		$retry = sprintf(
			'<p><a href="%s">%s</a></p>',
			esc_url( $this->start_url() ),
			esc_html__( 'Torna-ho a provar', 'softcatala' )
		);

		wp_die(
			wp_kses_post( '<p>' . $this->denial_message( $reason ) . '</p>' . $retry ),
			esc_html__( 'No s\'ha pogut iniciar la sessió', 'softcatala' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * @param string $reason Slug from SsoDenied or fail().
	 * @return string Message for the visitor; details stay in the log.
	 */
	private function denial_message( $reason ) {
		switch ( $reason ) {
			case 'inactive':
				return __( 'Aquest compte ja no és actiu a Softcatalà.', 'softcatala' );

			case 'subject_conflict':
			case 'ambiguous_subject':
				return __( 'Aquesta adreça ja està vinculada a un altre compte. Poseu-vos en contacte amb l\'equip de sistemes.', 'softcatala' );

			case 'email_conflict':
				return __( 'Cal verificar l\'adreça electrònica del compte abans d\'iniciar la sessió.', 'softcatala' );

			case 'no_email':
			case 'no_subject':
				return __( 'El compte no proporciona les dades necessàries per a iniciar la sessió.', 'softcatala' );

			case 'issuer_refused':
				return __( 'S\'ha cancel·lat l\'inici de sessió.', 'softcatala' );

			case 'bad_state':
				return __( 'La sessió d\'inici ha caducat. Torneu-ho a provar.', 'softcatala' );

			default:
				return __( 'No s\'ha pogut completar l\'inici de sessió. Torneu-ho a provar més tard.', 'softcatala' );
		}
	}

	/**
	 * @param string $message Line for the PHP error log.
	 * @return void
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[sc-sso] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
