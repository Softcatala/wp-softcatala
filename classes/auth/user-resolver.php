<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Auth;

use WP_User;

/**
 * Validated ID-token claims -> a WP_User, or a refusal.
 *
 * Ported from login_via_oidc() in Minairo's minairo/auth/service.py, and the
 * match order is the security-relevant part:
 *
 *   1. usermeta sc_oidc_subject == sub        -> log in
 *   2. a WP user holds claims.email           -> link once, then log in
 *   3. otherwise                              -> provision
 *
 * `sub` is the identity. Email is only ever a ONE-TIME bridge from a WP
 * account that predates SSO to its Keycloak account: matching on email on
 * every login would hand an existing account to anyone who can get that
 * address onto a Keycloak user.
 */
class UserResolver {

	/**
	 * Keycloak's `sub`. Opaque and stable for the life of the Keycloak user.
	 */
	const SUBJECT_META = 'sc_oidc_subject';

	/**
	 * Pre-existing ACF true_false field (group_588a70c16eb71, «Estat de
	 * l'usuari») that author.php already uses to decide who appears in the
	 * members archive.
	 *
	 * Defence in depth rather than the access gate: offboarding deletes the
	 * Keycloak account, so an ex-member normally never reaches this code. It
	 * covers the window where the two systems disagree — the WP flag flipped
	 * before the Keycloak account was removed, or a returning member issued a
	 * fresh Keycloak account whose new `sub` would otherwise auto-link to
	 * their old projects and role.
	 */
	const STATUS_META = 'status_member';

	/**
	 * Role given to accounts created by SSO. Realm membership is already a
	 * deliberate sysadmin act, so provisioning is automatic; but it grants the
	 * capability floor only, and promotion stays a human decision in wp-admin.
	 */
	const PROVISION_ROLE = 'subscriber';

	/**
	 * @param array $claims Validated ID-token claims.
	 * @return array{0:WP_User,1:string} The user and one of sso_login,
	 *                                    sso_linked, sso_provisioned.
	 * @throws SsoDenied On any policy refusal.
	 */
	public function resolve( array $claims ) {
		$subject = isset( $claims['sub'] ) ? (string) $claims['sub'] : '';

		if ( '' === $subject ) {
			throw new SsoDenied( 'no_subject' );
		}

		$email    = isset( $claims['email'] ) ? sanitize_email( (string) $claims['email'] ) : '';
		$verified = ! empty( $claims['email_verified'] );

		$user  = $this->find_by_subject( $subject );
		$event = 'sso_login';

		if ( ! $user && $email ) {
			$user  = $this->find_linkable_by_email( $email, $verified, $subject );
			$event = $user ? 'sso_linked' : $event;
		}

		if ( ! $user ) {
			return array( $this->provision( $subject, $email, $verified, $claims ), 'sso_provisioned' );
		}

		// Before any write: a refused login must not leave a subject linked to
		// an account that cannot use it. Minairo can check this last because
		// its session rolls back; update_user_meta() commits immediately.
		$this->assert_active( $user );

		if ( 'sso_linked' === $event ) {
			update_user_meta( $user->ID, self::SUBJECT_META, $subject );
		}

		$this->sync_email( $user, $email, $verified );

		return array( $user, $event );
	}

	/**
	 * @param string $subject Keycloak `sub`.
	 * @return WP_User|null
	 * @throws SsoDenied When two accounts carry the same subject.
	 */
	private function find_by_subject( $subject ) {
		$users = get_users(
			array(
				'meta_key'   => self::SUBJECT_META,
				'meta_value' => $subject,
				'number'     => 2,
				'fields'     => 'all',
			)
		);

		// More than one account carrying the same subject means the meta was
		// edited by hand; refuse rather than pick one arbitrarily.
		if ( count( $users ) > 1 ) {
			throw new SsoDenied( 'ambiguous_subject' );
		}

		return $users ? $users[0] : null;
	}

	/**
	 * The one-time bridge from a pre-SSO account, with both takeover guards.
	 *
	 * @param string $email    Address claimed by the token.
	 * @param bool   $verified Whether the issuer vouches for it.
	 * @param string $subject  Keycloak `sub`.
	 * @return WP_User|null Null when no WP account holds that address.
	 * @throws SsoDenied On a policy refusal.
	 */
	private function find_linkable_by_email( $email, $verified, $subject ) {
		$candidate = get_user_by( 'email', $email );

		if ( ! $candidate ) {
			return null;
		}

		// An unverified address may not reach an existing account at all.
		if ( ! $verified ) {
			throw new SsoDenied( 'email_conflict' );
		}

		$linked = (string) get_user_meta( $candidate->ID, self::SUBJECT_META, true );

		// Already bound to a different Keycloak user claiming the same address.
		if ( '' !== $linked && $linked !== $subject ) {
			throw new SsoDenied( 'subject_conflict' );
		}

		return $candidate;
	}

	/**
	 * @param WP_User $user User being logged in.
	 * @throws SsoDenied On a policy refusal.
	 */
	private function assert_active( $user ) {
		$status = get_user_meta( $user->ID, self::STATUS_META, true );

		// Absent means active. author.php:56-60 treats a missing row as a
		// current member (its NOT EXISTS branch) because the field postdates
		// most accounts; reading a missing row as inactive would lock out
		// every account that predates it, with no password fallback under
		// ENFORCED. Only an explicitly stored falsy value denies.
		if ( '' === $status ) {
			return;
		}

		if ( ! $status ) {
			throw new SsoDenied( 'inactive' );
		}
	}

	/**
	 * Sync an address Keycloak has changed.
	 *
	 * Keycloak owns the address, so a change there should land here — but
	 * WordPress requires user_email to be unique, so a collision is reported
	 * rather than silently dropped, which would leave the two systems
	 * drifting apart with no trace.
	 *
	 * @param WP_User $user     User being logged in.
	 * @param string  $email    Address claimed by the token.
	 * @param bool    $verified Whether the issuer vouches for it.
	 */
	private function sync_email( $user, $email, $verified ) {
		if ( ! $email || ! $verified || $email === $user->user_email ) {
			return;
		}

		$holder = get_user_by( 'email', $email );

		if ( $holder && (int) $holder->ID !== (int) $user->ID ) {
			$this->log( sprintf( 'email sync skipped for user %d: %s is held by user %d', $user->ID, $email, $holder->ID ) );
			return;
		}

		$updated = wp_update_user(
			array(
				'ID'         => $user->ID,
				'user_email' => $email,
			)
		);

		if ( is_wp_error( $updated ) ) {
			$this->log( sprintf( 'email sync failed for user %d: %s', $user->ID, $updated->get_error_message() ) );
			return;
		}

		$user->user_email = $email;
	}

	/**
	 * @param string $subject  Keycloak `sub`.
	 * @param string $email    Address claimed by the token.
	 * @param bool   $verified Whether the issuer vouches for it.
	 * @param array  $claims   Full claim set, for the display name.
	 * @return WP_User
	 * @throws SsoDenied On a policy refusal.
	 */
	private function provision( $subject, $email, $verified, array $claims ) {
		if ( ! $email ) {
			throw new SsoDenied( 'no_email' );
		}

		// A brand-new account may not be created around an address the issuer
		// will not vouch for.
		if ( ! $verified ) {
			throw new SsoDenied( 'email_conflict' );
		}

		$preferred = isset( $claims['preferred_username'] ) ? (string) $claims['preferred_username'] : '';
		$login     = $this->unique_login( $preferred ? $preferred : strstr( $email, '@', true ) );

		$display = isset( $claims['name'] ) ? trim( (string) $claims['name'] ) : '';

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'display_name' => $display ? $display : $login,
				'first_name'   => isset( $claims['given_name'] ) ? (string) $claims['given_name'] : '',
				'last_name'    => isset( $claims['family_name'] ) ? (string) $claims['family_name'] : '',
				// Never used: ENFORCED refuses password login outright, and
				// under ENABLED nobody knows this value. It exists because
				// WordPress has no password-less account.
				'user_pass'    => wp_generate_password( 64, true, true ),
				'role'         => self::PROVISION_ROLE,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$this->log( 'provisioning failed: ' . $user_id->get_error_message() );
			throw new SsoDenied( 'provisioning_failed' );
		}

		update_user_meta( $user_id, self::SUBJECT_META, $subject );

		return new WP_User( $user_id );
	}

	/**
	 * @param string $wanted Preferred login name.
	 * @return string A login not currently taken.
	 */
	private function unique_login( $wanted ) {
		$base = sanitize_user( $wanted, true );

		if ( '' === $base ) {
			$base = 'usuari';
		}

		$login = $base;
		$suffix = 1;

		while ( username_exists( $login ) ) {
			++$suffix;
			$login = $base . '-' . $suffix;
		}

		return $login;
	}

	/**
	 * @param string $message Line for the PHP error log.
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[sc-sso] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
