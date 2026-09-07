<?php
/**
 * Tests for Softcatala\Auth\LoginFlow.
 *
 * Mode::is_enforced() reads a wp-config constant, which cannot be redefined
 * between tests, so the enforcement filter is exercised directly rather than
 * through the hook it is registered on.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Auth\LoginFlow;

/**
 * Class AuthLoginFlowTest
 */
class AuthLoginFlowTest extends SCTests {

	/**
	 * @var LoginFlow
	 */
	private $flow;

	public function set_up() {
		parent::set_up();
		$this->flow = new LoginFlow();
	}

	/**
	 * The default install defines no SC_SSO_MODE, so the callback route must
	 * not merely be hidden but absent.
	 */
	public function test_disabled_registers_no_routes() {
		$this->assertFalse( has_action( 'login_form_' . LoginFlow::ACTION_CALLBACK ) );
		$this->assertFalse( has_action( 'login_form_' . LoginFlow::ACTION_START ) );
		$this->assertFalse( has_action( 'login_form' ) );
	}

	public function test_enforced_refuses_password_login() {
		$user = new WP_User( $this->factory->user->create() );

		$result = $this->flow->block_password_login( $user );

		$this->assertWPError( $result );
		$this->assertEquals( 'sc_sso_enforced', $result->get_error_code() );
	}

	/**
	 * The tasques endpoints authenticate with Application Passwords and
	 * Keycloak has no equivalent for machine clients, so ENFORCED must leave
	 * that path alone. A regression here breaks the kanban API silently.
	 */
	public function test_enforced_leaves_application_passwords_alone() {
		$user = new WP_User( $this->factory->user->create() );

		add_filter( 'application_password_is_api_request', '__return_true' );
		$result = $this->flow->block_password_login( $user );
		remove_filter( 'application_password_is_api_request', '__return_true' );

		$this->assertInstanceOf( 'WP_User', $result );
		$this->assertEquals( $user->ID, $result->ID );
	}

	/**
	 * An earlier failure in the authenticate chain must reach the user with
	 * its own message rather than being relabelled.
	 */
	public function test_existing_errors_pass_through() {
		$error = new WP_Error( 'incorrect_password', 'nope' );

		$this->assertSame( $error, $this->flow->block_password_login( $error ) );
	}

	public function test_null_user_passes_through() {
		$this->assertNull( $this->flow->block_password_login( null ) );
	}
}
