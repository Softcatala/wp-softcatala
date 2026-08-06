<?php

/**
 * The X-Requested-With guard the admin-ajax endpoints share.
 *
 * The guard only ever mattered once it stopped being satisfied: porting
 * programes.js from jQuery to fetch() dropped the header, and search, votes
 * and the aparell form all started answering an error the UI rendered as
 * English text. These tests pin the two halves of that — the header gets
 * through, and its absence stops the request loudly.
 *
 * Deliberately not tagged @group ajax: WordPress excludes that group from its
 * own suite for being slow, and these are neither slow nor worth skipping.
 */
class AjaxGuardTest extends WP_Ajax_UnitTestCase {

	public function tear_down() {
		unset( $_SERVER['HTTP_X_REQUESTED_WITH'] );

		parent::tear_down();
	}

	/**
	 * Runs the guard, returning the response body if it ended the request and
	 * null if it let the request continue.
	 */
	private function run_guard() {
		ob_start();

		try {
			sc_check_is_ajax_call();
		} catch ( WPAjaxDieStopException | WPAjaxDieContinueException $e ) {
			// wp_send_json_error() ends the request; the handler has already
			// captured the buffer into $this->_last_response.
			return $this->_last_response;
		}

		ob_end_clean();

		return null;
	}

	public function test_lets_an_xhr_through() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->assertNull( $this->run_guard() );
	}

	/** Header values are case-insensitive, and clients disagree on the casing. */
	public function test_lets_a_lowercased_xhr_through() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

		$this->assertNull( $this->run_guard() );
	}

	/**
	 * The 403 itself is out of reach here: PHPUnit has already written to
	 * stdout, so headers_sent() is true and wp_send_json_error() skips
	 * status_header(). What is checkable is that the request stops and the
	 * body reports a failure rather than a payload a client might act on.
	 */
	public function test_rejects_a_request_without_the_header() {
		unset( $_SERVER['HTTP_X_REQUESTED_WITH'] );

		$response = $this->run_guard();

		$this->assertNotNull( $response, 'The guard should have ended the request.' );
		$this->assertFalse( json_decode( $response, true )['success'] );
	}

	public function test_rejects_a_request_carrying_some_other_value() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'ShockwaveFlash/32.0';

		$response = $this->run_guard();

		$this->assertNotNull( $response, 'The guard should have ended the request.' );
		$this->assertFalse( json_decode( $response, true )['success'] );
	}
}
