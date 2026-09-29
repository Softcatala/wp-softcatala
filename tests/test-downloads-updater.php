<?php
/**
 * The downloads updater copies what the rebost-releases API answers into the
 * programs. An API that fails used to look like one with nothing to update,
 * so a program could keep its old downloads for years without a trace.
 *
 * Every HTTP request is mocked through pre_http_request, so nothing here
 * touches the network.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class DownloadsUpdaterTest
 */
class DownloadsUpdaterTest extends SCTests {

	const API = 'https://api.softcatala.org/rebost-releases/v1';

	const VERSIONS = '[{"download_version":"15.0.23","download_os":"windows","arquitectura":"x86_64","download_url":"https://example.org/tor.exe","download_size":"108 MB"}]';

	/**
	 * Arguments of the last HTTP request
	 *
	 * @var array
	 */
	private $request = array();

	/**
	 * Answers every request to the API with what $responses has for its
	 * path, as array( status, body ).
	 */
	private function mock_api( $responses ) {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $responses ) {
				$this->request = $args;

				$path = ltrim( substr( $url, strlen( self::API ) ), '/' );

				if ( ! isset( $responses[ $path ] ) ) {
					return new WP_Error( 'http_request_failed', 'Connection timed out' );
				}

				list( $status, $body ) = $responses[ $path ];

				return array(
					'headers'  => array(),
					'body'     => $body,
					'response' => array(
						'code'    => $status,
						'message' => '',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			},
			10,
			3
		);
	}

	private function make_programa( $slug ) {
		return self::factory()->post->create(
			array(
				'post_type'   => 'programa',
				'post_status' => 'publish',
				'post_name'   => $slug,
			)
		);
	}

	private function update( $slug, $dry_run = true ) {
		$program = array(
			'wp'    => $slug,
			'api'   => $slug,
			'group' => $slug,
		);

		return ( new SC_Downloads_Updater() )->update_program( $program, $dry_run );
	}

	/*
	 * update_program()
	 */

	function test_versions_of_the_api_are_offered_for_the_program() {
		$post_id = $this->make_programa( 'tor' );
		$this->mock_api( array( 'tor' => array( 200, self::VERSIONS ) ) );

		$result = $this->update( 'tor' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( $post_id, $result['data']['post_id'] );
		$this->assertSame( 1, $result['data']['version_count'] );
		$this->assertSame( '15.0.23', $result['data']['versions'][0]['download_version'] );
	}

	function test_server_error_is_a_failure() {
		$this->make_programa( 'tor' );
		$this->mock_api( array( 'tor' => array( 500, '<title>500 Internal Server Error</title>' ) ) );

		$result = $this->update( 'tor' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'tor', $result['message'] );
	}

	function test_not_found_is_a_failure() {
		$this->make_programa( 'tor' );
		$this->mock_api( array( 'tor' => array( 404, 'NoData' ) ) );

		$this->assertFalse( $this->update( 'tor' )['success'] );
	}

	function test_unreachable_api_is_a_failure() {
		$this->make_programa( 'tor' );
		$this->mock_api( array() );

		$this->assertFalse( $this->update( 'tor' )['success'] );
	}

	function test_body_that_is_not_json_is_a_failure() {
		$this->make_programa( 'tor' );
		$this->mock_api( array( 'tor' => array( 200, 'NoData' ) ) );

		$this->assertFalse( $this->update( 'tor' )['success'] );
	}

	function test_empty_list_is_nothing_to_update() {
		$this->make_programa( 'tor' );
		$this->mock_api( array( 'tor' => array( 200, '[]' ) ) );

		$result = $this->update( 'tor' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( array(), $result['data'] );
	}

	function test_empty_list_with_a_line_break_is_nothing_to_update() {
		$this->make_programa( 'tor' );
		$this->mock_api( array( 'tor' => array( 200, "[]\n" ) ) );

		$result = $this->update( 'tor' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( array(), $result['data'] );
	}

	function test_failure_leaves_the_downloads_of_the_program_alone() {
		$post_id = $this->make_programa( 'tor' );
		update_post_meta( $post_id, 'baixada', 'untouched' );
		$this->mock_api( array( 'tor' => array( 500, 'error' ) ) );

		$this->update( 'tor', false );

		$this->assertSame( 'untouched', get_post_meta( $post_id, 'baixada', true ) );
	}

	function test_api_is_given_time_to_answer() {
		$this->make_programa( 'tor' );
		$this->mock_api( array( 'tor' => array( 200, self::VERSIONS ) ) );

		$this->update( 'tor' );

		$this->assertSame( 30, $this->request['timeout'] );
	}

	/*
	 * get_all_programs()
	 */

	function test_index_that_fails_is_an_error() {
		$this->mock_api( array( '' => array( 500, '<title>500 Internal Server Error</title>' ) ) );

		$this->assertWPError( ( new SC_Downloads_Updater() )->get_all_programs() );
	}

	function test_index_that_is_not_json_is_an_error() {
		$this->mock_api( array( '' => array( 200, '<html></html>' ) ) );

		$this->assertWPError( ( new SC_Downloads_Updater() )->get_all_programs() );
	}

	/*
	 * update_all_programs()
	 */

	function test_failed_programs_are_counted_apart() {
		$this->make_programa( 'tor' );
		$this->make_programa( 'gimp' );

		$index = '[{"wp":"tor","api":"tor","group":"tor"},{"wp":"gimp","api":"gimp","group":"gimp"}]';

		$this->mock_api(
			array(
				''     => array( 200, $index ),
				'tor'  => array( 500, 'error' ),
				'gimp' => array( 200, self::VERSIONS ),
			)
		);

		$result = ( new SC_Downloads_Updater() )->update_all_programs( null, true );

		$this->assertSame( 2, $result['programs_processed'] );
		$this->assertSame( 1, $result['programs_updated'] );
		$this->assertSame( 1, $result['programs_failed'] );
	}
}
