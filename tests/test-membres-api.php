<?php
/**
 * Tests for GET sc/v1/membres: the active members, logged-in only.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class MembresApiTest
 */
class MembresApiTest extends SCTests {

	/**
	 * Requests the endpoint as the given user (0 = anonymous).
	 *
	 * @param int $user_id
	 * @return WP_REST_Response
	 */
	private function get_membres( $user_id ) {
		wp_set_current_user( $user_id );
		return rest_do_request( new WP_REST_Request( 'GET', '/sc/v1/membres' ) );
	}

	/**
	 * The usernames in a response, sorted.
	 *
	 * @param WP_REST_Response $response
	 * @return string[]
	 */
	private function usernames( $response ) {
		$names = wp_list_pluck( $response->get_data(), 'username' );
		sort( $names );
		return $names;
	}

	function test_anonymous_request_is_refused() {
		$response = $this->get_membres( 0 );
		$this->assertEquals( 401, $response->get_status() );
	}

	function test_lists_active_and_unset_status_but_not_former_members() {
		$active = $this->factory->user->create( array( 'user_nicename' => 'actiu' ) );
		update_user_meta( $active, 'status_member', '1' );
		$this->factory->user->create( array( 'user_nicename' => 'sense-estat' ) );
		$former = $this->factory->user->create( array( 'user_nicename' => 'antic' ) );
		update_user_meta( $former, 'status_member', '0' );

		$admin    = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$response = $this->get_membres( $admin );

		$this->assertEquals( 200, $response->get_status() );
		$names = $this->usernames( $response );
		$this->assertContains( 'actiu', $names );
		$this->assertContains( 'sense-estat', $names );
		$this->assertNotContains( 'antic', $names );
	}

	function test_item_shape_with_email_for_list_users() {
		$user = $this->factory->user->create(
			array(
				'user_nicename' => 'joana',
				'display_name'  => 'Joana Puig',
				'user_email'    => 'joana@example.org',
			)
		);
		update_user_meta( $user, 'telegram', 'joanapuig' );

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$data  = $this->get_membres( $admin )->get_data();
		$item  = current( wp_list_filter( $data, array( 'username' => 'joana' ) ) );

		$this->assertEquals( 'Joana Puig', $item['display_name'] );
		$this->assertEquals( 'joanapuig', $item['telegram'] );
		$this->assertEquals( 'joana@example.org', $item['email'] );
	}

	function test_email_is_hidden_without_list_users() {
		$this->factory->user->create( array( 'user_nicename' => 'pere' ) );

		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$data       = $this->get_membres( $subscriber )->get_data();

		$this->assertNotEmpty( $data );
		foreach ( $data as $item ) {
			$this->assertArrayNotHasKey( 'email', $item );
		}
		$item = current( wp_list_filter( $data, array( 'username' => 'pere' ) ) );
		$this->assertNull( $item['telegram'] );
	}
}
