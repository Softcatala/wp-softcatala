<?php
/**
 * Tests for the PATCH sc/v1/tasca/{id}/estat REST endpoint.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class TasquesApiTest
 */
class TasquesApiTest extends SCTests {

	/**
	 * @var int Tasca post ID used across tests.
	 */
	private $task_id;

	/**
	 * @var int estat_tasca term ID used across tests.
	 */
	private $term_id;

	/**
	 * @var string estat_tasca term slug used across tests.
	 */
	private $term_slug = 'en-curs';

	public function setUp() {
		parent::setUp();

		// Create a test estat_tasca term.
		$result = wp_insert_term( 'En curs', 'estat_tasca', array( 'slug' => $this->term_slug ) );
		if ( is_wp_error( $result ) ) {
			// Term may already exist from seeder; fetch it.
			$term = get_term_by( 'slug', $this->term_slug, 'estat_tasca' );
			$this->term_id = $term ? $term->term_id : 0;
		} else {
			$this->term_id = $result['term_id'];
		}

		// Create a test tasca post.
		$this->task_id = wp_insert_post( array(
			'post_type'   => 'tasca',
			'post_title'  => 'API Test Task',
			'post_status' => 'publish',
		) );
	}

	public function tearDown() {
		wp_delete_post( $this->task_id, true );
		wp_delete_term( $this->term_id, 'estat_tasca' );
		parent::tearDown();
	}

	/**
	 * Validates a PATCH with a valid estat slug updates the term and returns HTTP 200.
	 */
	function test_valid_patch_updates_estat_and_returns_200() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', $this->term_slug );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( $this->task_id, $data['id'] );
		$this->assertEquals( $this->term_slug, $data['estat'] );

		// Integration: verify database was updated.
		$assigned = wp_get_post_terms( $this->task_id, 'estat_tasca', array( 'fields' => 'slugs' ) );
		$this->assertContains( $this->term_slug, $assigned );

		wp_delete_user( $user_id );
	}

	/**
	 * PATCH with the same estat slug as current is idempotent (HTTP 200).
	 */
	function test_same_estat_is_idempotent() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Assign term first.
		wp_set_post_terms( $this->task_id, array( $this->term_id ), 'estat_tasca' );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', $this->term_slug );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		wp_delete_user( $user_id );
	}

	/**
	 * PATCH without a nonce returns HTTP 403.
	 */
	function test_missing_nonce_returns_403() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', $this->term_slug );
		// No nonce header.

		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status() );

		wp_delete_user( $user_id );
	}

	/**
	 * PATCH with an invalid nonce returns HTTP 403.
	 */
	function test_invalid_nonce_returns_403() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', $this->term_slug );
		$request->set_header( 'X-WP-Nonce', 'invalid-nonce-value' );

		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status() );

		wp_delete_user( $user_id );
	}

	/**
	 * Anonymous PATCH returns HTTP 401.
	 */
	function test_anonymous_request_returns_401() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', $this->term_slug );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_do_request( $request );
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * PATCH with an ID that belongs to a projecte (not a tasca) returns HTTP 404.
	 */
	function test_non_tasca_post_id_returns_404() {
		$user_id    = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = wp_insert_post( array(
			'post_type'   => 'projecte',
			'post_title'  => 'A Projecte',
			'post_status' => 'publish',
		) );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $projecte_id . '/estat' );
		$request->set_param( 'id', $projecte_id );
		$request->set_param( 'estat', $this->term_slug );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_do_request( $request );
		$this->assertEquals( 404, $response->get_status() );

		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * PATCH with a non-existent estat slug returns HTTP 422.
	 */
	function test_nonexistent_estat_slug_returns_422() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', 'estat-que-no-existeix' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_do_request( $request );
		$this->assertEquals( 422, $response->get_status() );

		wp_delete_user( $user_id );
	}

	/**
	 * PATCH to a terminal estat writes _terminal_date meta.
	 */
	function test_patch_to_terminal_estat_writes_terminal_date() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$terminal_term = wp_insert_term( 'Feta', 'estat_tasca', array( 'slug' => 'feta-test' ) );
		if ( is_wp_error( $terminal_term ) ) {
			$term = get_term_by( 'slug', 'feta-test', 'estat_tasca' );
			$terminal_term_id = $term->term_id;
		} else {
			$terminal_term_id = $terminal_term['term_id'];
		}
		update_term_meta( $terminal_term_id, 'is_terminal', 1 );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', 'feta-test' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertNotEmpty( get_post_meta( $this->task_id, '_terminal_date', true ) );

		wp_delete_term( $terminal_term_id, 'estat_tasca' );
		wp_delete_user( $user_id );
	}

	/**
	 * PATCH away from a terminal estat deletes _terminal_date meta.
	 */
	function test_patch_away_from_terminal_estat_removes_terminal_date() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Pre-set a _terminal_date as if the task was previously terminal.
		update_post_meta( $this->task_id, '_terminal_date', current_time( 'mysql' ) );

		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', $this->term_slug ); // non-terminal
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( get_post_meta( $this->task_id, '_terminal_date', true ) );

		wp_delete_user( $user_id );
	}

	/**
	 * The sc/v1/tasca route is discoverable in the REST index.
	 */
	function test_route_is_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/sc/v1/tasca/(?P<id>\d+)/estat', $routes );
	}
}
