<?php
/**
 * Tests for the sc/v1 tasques REST endpoints:
 * POST sc/v1/tasca and PATCH sc/v1/tasca/{id}/estat.
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

	public function set_up() {
		parent::set_up();

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

	public function tear_down() {
		wp_delete_post( $this->task_id, true );
		wp_delete_term( $this->term_id, 'estat_tasca' );
		parent::tear_down();
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
	 * Build a PATCH request that moves the test task to the test estat.
	 *
	 * @return WP_REST_Request
	 */
	private function make_patch_request() {
		$request = new WP_REST_Request( 'PATCH', '/sc/v1/tasca/' . $this->task_id . '/estat' );
		$request->set_param( 'id', $this->task_id );
		$request->set_param( 'estat', $this->term_slug );
		return $request;
	}

	/**
	 * Dispatch a request as a cookie-authenticated browser would.
	 *
	 * rest_do_request() calls WP_REST_Server::dispatch() directly and therefore
	 * skips check_authentication(), which is where core's nonce enforcement
	 * lives. These tests run the `rest_authentication_errors` chain first, the
	 * way serve_request() does, so the cookie/nonce path is exercised for real.
	 *
	 * @param WP_REST_Request $request The request to dispatch.
	 * @param string|null     $nonce   Nonce to present, or null to send none.
	 * @return WP_REST_Response
	 */
	private function dispatch_with_cookie_auth( $request, $nonce ) {
		$GLOBALS['wp_rest_auth_cookie'] = true;
		$_COOKIE[ LOGGED_IN_COOKIE ]    = 'cookie-auth-simulation';
		if ( null !== $nonce ) {
			$_REQUEST['_wpnonce'] = $nonce;
		}

		$auth = apply_filters( 'rest_authentication_errors', null );

		unset( $GLOBALS['wp_rest_auth_cookie'], $_COOKIE[ LOGGED_IN_COOKIE ], $_REQUEST['_wpnonce'] );

		if ( is_wp_error( $auth ) ) {
			return rest_convert_error_to_response( $auth );
		}

		return rest_do_request( $request );
	}

	/**
	 * A cookie-authenticated PATCH carrying a valid nonce succeeds.
	 */
	function test_cookie_auth_with_valid_nonce_returns_200() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$response = $this->dispatch_with_cookie_auth( $this->make_patch_request(), wp_create_nonce( 'wp_rest' ) );
		$this->assertEquals( 200, $response->get_status() );

		wp_delete_user( $user_id );
	}

	/**
	 * A cookie-authenticated PATCH without a nonce is logged out by core and 401s.
	 */
	function test_cookie_auth_without_nonce_returns_401() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$response = $this->dispatch_with_cookie_auth( $this->make_patch_request(), null );
		$this->assertEquals( 401, $response->get_status() );

		wp_delete_user( $user_id );
	}

	/**
	 * A cookie-authenticated PATCH with a bad nonce is rejected by core with 403.
	 */
	function test_cookie_auth_with_invalid_nonce_returns_403() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$response = $this->dispatch_with_cookie_auth( $this->make_patch_request(), 'invalid-nonce-value' );
		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'rest_cookie_invalid_nonce', $response->get_data()['code'] );

		wp_delete_user( $user_id );
	}

	/**
	 * Authentication that is not cookie-based — an Application Password — needs no
	 * nonce: core only demands one when a login cookie is in play.
	 */
	function test_patch_without_nonce_succeeds_for_non_cookie_auth() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// No cookie globals set, no nonce sent.
		$response = rest_do_request( $this->make_patch_request() );
		$this->assertEquals( 200, $response->get_status() );

		$assigned = wp_get_post_terms( $this->task_id, 'estat_tasca', array( 'fields' => 'slugs' ) );
		$this->assertContains( $this->term_slug, $assigned );

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

	// -----------------------------------------------------------------------
	// POST sc/v1/tasca
	// -----------------------------------------------------------------------

	/**
	 * Build a POST request against the create endpoint from a parameter array.
	 *
	 * @param array $params Request parameters.
	 * @return WP_REST_Request
	 */
	private function make_create_request( $params ) {
		$request = new WP_REST_Request( 'POST', '/sc/v1/tasca' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	/**
	 * Create a published projecte to attach test tasks to.
	 *
	 * @return int Projecte post ID.
	 */
	private function make_projecte( $title = 'Un Projecte' ) {
		return wp_insert_post( array(
			'post_type'   => 'projecte',
			'post_title'  => $title,
			'post_status' => 'publish',
		) );
	}

	/**
	 * A full payload creates the task, sets every ACF field and returns HTTP 201.
	 */
	function test_create_returns_201_and_sets_acf_fields() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id  = $this->make_projecte();
		$responsable  = $this->factory->user->create( array( 'role' => 'author' ) );
		$milestone_id = wp_insert_post( array(
			'post_type'   => 'milestone',
			'post_title'  => 'Fita 1',
			'post_status' => 'publish',
		) );
		update_post_meta( $milestone_id, 'projecte_milestone', $projecte_id );

		$response = rest_do_request( $this->make_create_request( array(
			'title'          => 'Tasca creada per API',
			'content'        => '<p>Descripció</p>',
			'projecte'       => $projecte_id,
			'estat'          => $this->term_slug,
			'milestone'      => $milestone_id,
			'responsables'   => array( $responsable ),
			'data_venciment' => '2026-09-30',
			'tasca_interna'  => true,
			'etiquetes'      => array( 'traduccio' ),
		) ) );

		$this->assertEquals( 201, $response->get_status() );

		$data    = $response->get_data();
		$task_id = $data['id'];

		$this->assertEquals( 'tasca', get_post_type( $task_id ) );
		$this->assertEquals( 'Tasca creada per API', get_the_title( $task_id ) );
		$this->assertEquals( $this->term_slug, $data['estat'] );

		$this->assertEquals( $projecte_id, (int) get_post_meta( $task_id, 'projecte_tasca', true ) );
		$this->assertEquals( $milestone_id, (int) get_post_meta( $task_id, 'milestone_tasca', true ) );
		$this->assertEquals( array( $responsable ), array_map( 'intval', (array) get_post_meta( $task_id, 'responsable_tasca', true ) ) );
		$this->assertEquals( '1', get_post_meta( $task_id, 'tasca_interna', true ) );

		$this->assertContains( $this->term_slug, wp_get_post_terms( $task_id, 'estat_tasca', array( 'fields' => 'slugs' ) ) );
		$this->assertContains( 'traduccio', wp_get_post_terms( $task_id, 'tag_tasca', array( 'fields' => 'slugs' ) ) );

		wp_delete_post( $task_id, true );
		wp_delete_post( $milestone_id, true );
		wp_delete_post( $projecte_id, true );
		wp_delete_user( $responsable );
		wp_delete_user( $user_id );
	}

	/**
	 * data_venciment is stored in ACF's Ymd format and reads back as Y-m-d.
	 */
	function test_create_stores_data_venciment_in_acf_format() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'          => 'Amb venciment',
			'projecte'       => $projecte_id,
			'data_venciment' => '2026-09-30',
		) ) );

		$this->assertEquals( 201, $response->get_status() );
		$task_id = $response->get_data()['id'];

		$this->assertEquals( '20260930', get_post_meta( $task_id, 'data_venciment', true ) );
		$this->assertEquals( '2026-09-30', get_field( 'data_venciment', $task_id ) );

		wp_delete_post( $task_id, true );
		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * Omitting estat lands the task in the first non-terminal column.
	 */
	function test_create_defaults_to_first_non_terminal_estat() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		// The set_up term has no order meta (board_order 99); add a lower-ordered
		// non-terminal column plus a terminal one that must not be picked.
		$first = wp_insert_term( 'Propostes', 'estat_tasca', array( 'slug' => 'propostes-test' ) );
		update_term_meta( $first['term_id'], 'order', 1 );
		$terminal = wp_insert_term( 'Feta', 'estat_tasca', array( 'slug' => 'feta-default-test' ) );
		update_term_meta( $terminal['term_id'], 'order', 0 );
		update_term_meta( $terminal['term_id'], 'is_terminal', 1 );

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Sense estat explícit',
			'projecte' => $projecte_id,
		) ) );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'propostes-test', $data['estat'] );
		$this->assertContains( 'propostes-test', wp_get_post_terms( $data['id'], 'estat_tasca', array( 'fields' => 'slugs' ) ) );

		wp_delete_post( $data['id'], true );
		wp_delete_post( $projecte_id, true );
		wp_delete_term( $first['term_id'], 'estat_tasca' );
		wp_delete_term( $terminal['term_id'], 'estat_tasca' );
		wp_delete_user( $user_id );
	}

	/**
	 * Creating straight into a terminal estat writes _terminal_date, like the PATCH does.
	 */
	function test_create_into_terminal_estat_writes_terminal_date() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();
		$terminal    = wp_insert_term( 'Feta', 'estat_tasca', array( 'slug' => 'feta-create-test' ) );
		update_term_meta( $terminal['term_id'], 'is_terminal', 1 );

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Ja feta',
			'projecte' => $projecte_id,
			'estat'    => 'feta-create-test',
		) ) );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertNotEmpty( get_post_meta( $response->get_data()['id'], '_terminal_date', true ) );

		wp_delete_post( $response->get_data()['id'], true );
		wp_delete_post( $projecte_id, true );
		wp_delete_term( $terminal['term_id'], 'estat_tasca' );
		wp_delete_user( $user_id );
	}

	/**
	 * status=draft is honoured and skips the publish capability gate for editors.
	 */
	function test_create_with_draft_status() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Esborrany',
			'projecte' => $projecte_id,
			'status'   => 'draft',
		) ) );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 'draft', get_post_status( $response->get_data()['id'] ) );

		wp_delete_post( $response->get_data()['id'], true );
		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * A missing projecte parameter is rejected by the schema with HTTP 400.
	 */
	function test_create_without_projecte_returns_400() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$response = rest_do_request( $this->make_create_request( array(
			'title' => 'Sense projecte',
		) ) );

		$this->assertEquals( 400, $response->get_status() );

		wp_delete_user( $user_id );
	}

	/**
	 * A projecte ID that is not a projecte post returns HTTP 422 and creates nothing.
	 */
	function test_create_with_invalid_projecte_returns_422() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$before = wp_count_posts( 'tasca' )->publish;

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Projecte inexistent',
			'projecte' => 999999,
		) ) );

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( $before, wp_count_posts( 'tasca' )->publish );

		wp_delete_user( $user_id );
	}

	/**
	 * An unknown estat slug returns HTTP 422.
	 */
	function test_create_with_unknown_estat_returns_422() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Estat inexistent',
			'projecte' => $projecte_id,
			'estat'    => 'estat-que-no-existeix',
		) ) );

		$this->assertEquals( 422, $response->get_status() );

		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * A milestone belonging to a different projecte returns HTTP 422.
	 */
	function test_create_with_milestone_from_other_projecte_returns_422() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte( 'Projecte A' );
		$other_id    = $this->make_projecte( 'Projecte B' );

		$milestone_id = wp_insert_post( array(
			'post_type'   => 'milestone',
			'post_title'  => 'Fita del projecte B',
			'post_status' => 'publish',
		) );
		update_post_meta( $milestone_id, 'projecte_milestone', $other_id );

		$response = rest_do_request( $this->make_create_request( array(
			'title'     => 'Milestone creuat',
			'projecte'  => $projecte_id,
			'milestone' => $milestone_id,
		) ) );

		$this->assertEquals( 422, $response->get_status() );

		wp_delete_post( $milestone_id, true );
		wp_delete_post( $other_id, true );
		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * A malformed data_venciment returns HTTP 422.
	 */
	function test_create_with_invalid_data_venciment_returns_422() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'          => 'Data dolenta',
			'projecte'       => $projecte_id,
			'data_venciment' => '30/09/2026',
		) ) );

		$this->assertEquals( 422, $response->get_status() );

		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * A non-existent responsable returns HTTP 422.
	 */
	function test_create_with_unknown_responsable_returns_422() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'        => 'Responsable inexistent',
			'projecte'     => $projecte_id,
			'responsables' => array( 999999 ),
		) ) );

		$this->assertEquals( 422, $response->get_status() );

		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * Anonymous POST returns HTTP 401.
	 */
	function test_anonymous_create_returns_401() {
		wp_set_current_user( 0 );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Anònim',
			'projecte' => $projecte_id,
		) ) );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );

		wp_delete_post( $projecte_id, true );
	}

	/**
	 * A subscriber cannot create tasks (HTTP 403).
	 */
	function test_subscriber_create_returns_403() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Subscriptor',
			'projecte' => $projecte_id,
		) ) );

		$this->assertEquals( 403, $response->get_status() );

		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * A contributor may create a draft but not publish (HTTP 403 on status=publish).
	 */
	function test_contributor_cannot_publish() {
		$user_id = $this->factory->user->create( array( 'role' => 'contributor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Col·laborador publica',
			'projecte' => $projecte_id,
			'status'   => 'publish',
		) ) );
		$this->assertEquals( 403, $response->get_status() );

		$response = rest_do_request( $this->make_create_request( array(
			'title'    => 'Col·laborador esborrany',
			'projecte' => $projecte_id,
			'status'   => 'draft',
		) ) );
		$this->assertEquals( 201, $response->get_status() );

		wp_delete_post( $response->get_data()['id'], true );
		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * Creating over cookie auth still requires a nonce, enforced by core.
	 */
	function test_cookie_auth_create_without_nonce_returns_401() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$projecte_id = $this->make_projecte();

		$response = $this->dispatch_with_cookie_auth(
			$this->make_create_request( array(
				'title'    => 'Sense nonce',
				'projecte' => $projecte_id,
			) ),
			null
		);
		$this->assertEquals( 401, $response->get_status() );

		wp_delete_post( $projecte_id, true );
		wp_delete_user( $user_id );
	}

	/**
	 * The POST sc/v1/tasca route is discoverable in the REST index.
	 */
	function test_create_route_is_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/sc/v1/tasca', $routes );
	}

}
