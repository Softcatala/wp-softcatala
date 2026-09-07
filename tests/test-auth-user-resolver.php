<?php
/**
 * Tests for Softcatala\Auth\UserResolver — the claims-to-WP_User match order.
 *
 * Each test name maps to one of the scenarios the design walked through.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Auth\UserResolver;
use Softcatala\Auth\SsoDenied;

/**
 * Class AuthUserResolverTest
 */
class AuthUserResolverTest extends SCTests {

	/**
	 * @var UserResolver
	 */
	private $resolver;

	public function set_up() {
		parent::set_up();
		$this->resolver = new UserResolver();
	}

	/**
	 * @param array $overrides Claim overrides.
	 * @return array A plausible Keycloak claim set.
	 */
	private function claims( array $overrides = array() ) {
		return array_merge(
			array(
				'sub'                => 'a1b2-c3d4',
				'email'              => 'jordi@example.org',
				'email_verified'     => true,
				'name'               => 'Jordi Exemple',
				'preferred_username' => 'jordi',
			),
			$overrides
		);
	}

	/**
	 * Scenario 1: the migration path. An editor who predates SSO is adopted
	 * on first login, keeping their ID, role and authorship.
	 */
	public function test_existing_account_is_linked_by_verified_email() {
		$id = $this->factory->user->create(
			array(
				'user_email' => 'jordi@example.org',
				'role'       => 'editor',
			)
		);

		list( $user, $event ) = $this->resolver->resolve( $this->claims() );

		$this->assertEquals( 'sso_linked', $event );
		$this->assertEquals( $id, $user->ID );
		$this->assertEquals( 'a1b2-c3d4', get_user_meta( $id, UserResolver::SUBJECT_META, true ) );
		$this->assertContains( 'editor', $user->roles, 'linking must not touch the role' );
	}

	/**
	 * Once linked, the subject is what matches — email is not consulted again.
	 */
	public function test_linked_account_matches_on_subject() {
		$id = $this->factory->user->create( array( 'user_email' => 'jordi@example.org' ) );
		update_user_meta( $id, UserResolver::SUBJECT_META, 'a1b2-c3d4' );

		list( $user, $event ) = $this->resolver->resolve( $this->claims() );

		$this->assertEquals( 'sso_login', $event );
		$this->assertEquals( $id, $user->ID );
	}

	/**
	 * Scenario 2: a new realm member. Realm membership is a sysadmin act, so
	 * the account is created — at the capability floor.
	 */
	public function test_unknown_user_is_provisioned_as_subscriber() {
		list( $user, $event ) = $this->resolver->resolve( $this->claims( array( 'email' => 'nova@example.org' ) ) );

		$this->assertEquals( 'sso_provisioned', $event );
		$this->assertContains( 'subscriber', $user->roles );
		$this->assertEquals( 'nova@example.org', $user->user_email );
		$this->assertEquals( 'a1b2-c3d4', get_user_meta( $user->ID, UserResolver::SUBJECT_META, true ) );
		$this->assertFalse( user_can( $user, 'edit_posts' ) );
	}

	public function test_provisioned_login_avoids_collisions() {
		$this->factory->user->create( array( 'user_login' => 'jordi' ) );

		list( $user ) = $this->resolver->resolve( $this->claims( array( 'email' => 'altre@example.org' ) ) );

		$this->assertEquals( 'jordi-2', $user->user_login );
	}

	/**
	 * Scenario 3: the address changed in Keycloak. The subject still matches,
	 * and WordPress follows.
	 */
	public function test_email_change_is_synced_when_free() {
		$id = $this->factory->user->create( array( 'user_email' => 'jordi@example.org' ) );
		update_user_meta( $id, UserResolver::SUBJECT_META, 'a1b2-c3d4' );

		list( $user, $event ) = $this->resolver->resolve( $this->claims( array( 'email' => 'jordi@softcatala.org' ) ) );

		$this->assertEquals( 'sso_login', $event );
		$this->assertEquals( 'jordi@softcatala.org', $user->user_email );
	}

	/**
	 * WordPress requires user_email to be unique, so a collision leaves the
	 * old address in place rather than failing the login.
	 */
	public function test_email_change_is_skipped_when_taken() {
		$id = $this->factory->user->create( array( 'user_email' => 'jordi@example.org' ) );
		update_user_meta( $id, UserResolver::SUBJECT_META, 'a1b2-c3d4' );
		$this->factory->user->create( array( 'user_email' => 'ocupat@softcatala.org' ) );

		list( $user ) = $this->resolver->resolve( $this->claims( array( 'email' => 'ocupat@softcatala.org' ) ) );

		$this->assertEquals( $id, $user->ID );
		$this->assertEquals( 'jordi@example.org', $user->user_email );
	}

	/**
	 * Scenario 4: a second Keycloak account claiming a linked address must not
	 * inherit that account's capabilities.
	 */
	public function test_second_subject_claiming_a_linked_email_is_denied() {
		$id = $this->factory->user->create(
			array(
				'user_email' => 'jordi@example.org',
				'role'       => 'editor',
			)
		);
		update_user_meta( $id, UserResolver::SUBJECT_META, 'a1b2-c3d4' );

		$this->expectException( SsoDenied::class );

		try {
			$this->resolver->resolve( $this->claims( array( 'sub' => 'e5f6-g7h8' ) ) );
		} catch ( SsoDenied $e ) {
			$this->assertEquals( 'subject_conflict', $e->reason() );
			$this->assertEquals( 'a1b2-c3d4', get_user_meta( $id, UserResolver::SUBJECT_META, true ) );
			throw $e;
		}
	}

	/**
	 * Scenario 5: an unverified address may never bridge to an existing
	 * account.
	 */
	public function test_unverified_email_cannot_reach_an_existing_account() {
		$id = $this->factory->user->create( array( 'user_email' => 'jordi@example.org' ) );

		try {
			$this->resolver->resolve( $this->claims( array( 'email_verified' => false ) ) );
			$this->fail( 'expected SsoDenied' );
		} catch ( SsoDenied $e ) {
			$this->assertEquals( 'email_conflict', $e->reason() );
			$this->assertEquals( '', get_user_meta( $id, UserResolver::SUBJECT_META, true ), 'nothing may be written on a refusal' );
		}
	}

	public function test_unverified_email_cannot_provision() {
		try {
			$this->resolver->resolve(
				$this->claims(
					array(
						'email'          => 'ningu@example.org',
						'email_verified' => false,
					)
				)
			);
			$this->fail( 'expected SsoDenied' );
		} catch ( SsoDenied $e ) {
			$this->assertEquals( 'email_conflict', $e->reason() );
			$this->assertFalse( get_user_by( 'email', 'ningu@example.org' ), 'no account may be created' );
		}
	}

	public function test_claims_without_email_are_denied() {
		try {
			$this->resolver->resolve( $this->claims( array( 'email' => '' ) ) );
			$this->fail( 'expected SsoDenied' );
		} catch ( SsoDenied $e ) {
			$this->assertEquals( 'no_email', $e->reason() );
		}
	}

	/**
	 * Scenario 9: an ex-member whose Keycloak account outlived the WP flag.
	 * The refusal must happen before the link is written.
	 */
	public function test_inactive_member_is_denied_before_linking() {
		$id = $this->factory->user->create( array( 'user_email' => 'jordi@example.org' ) );
		update_user_meta( $id, UserResolver::STATUS_META, '0' );

		try {
			$this->resolver->resolve( $this->claims() );
			$this->fail( 'expected SsoDenied' );
		} catch ( SsoDenied $e ) {
			$this->assertEquals( 'inactive', $e->reason() );
			$this->assertEquals( '', get_user_meta( $id, UserResolver::SUBJECT_META, true ) );
		}
	}

	/**
	 * The trap. author.php treats a missing status_member as a current member
	 * because the ACF field postdates most accounts; reading it as inactive
	 * would lock out everyone who predates it.
	 */
	public function test_absent_status_member_is_treated_as_active() {
		$id = $this->factory->user->create( array( 'user_email' => 'jordi@example.org' ) );
		$this->assertEquals( '', get_user_meta( $id, UserResolver::STATUS_META, true ) );

		list( $user, $event ) = $this->resolver->resolve( $this->claims() );

		$this->assertEquals( 'sso_linked', $event );
		$this->assertEquals( $id, $user->ID );
	}

	public function test_status_member_one_is_active() {
		$id = $this->factory->user->create( array( 'user_email' => 'jordi@example.org' ) );
		update_user_meta( $id, UserResolver::STATUS_META, '1' );

		list( $user ) = $this->resolver->resolve( $this->claims() );

		$this->assertEquals( $id, $user->ID );
	}

	public function test_claims_without_subject_are_denied() {
		try {
			$this->resolver->resolve( $this->claims( array( 'sub' => '' ) ) );
			$this->fail( 'expected SsoDenied' );
		} catch ( SsoDenied $e ) {
			$this->assertEquals( 'no_subject', $e->reason() );
		}
	}
}
