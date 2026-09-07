<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Auth;

/**
 * A login refused on policy grounds rather than a protocol error.
 *
 * The reason is a stable machine-readable slug — it goes in the log and
 * selects the message shown on wp-login.php — never a sentence to display
 * as-is. Mirrors SSODenied in Minairo's minairo/auth/service.py.
 */
class SsoDenied extends \Exception {

	/**
	 * @var string
	 */
	private $reason;

	/**
	 * @param string $reason One of the slugs handled by LoginFlow::denial_message().
	 */
	public function __construct( $reason ) {
		$this->reason = $reason;
		parent::__construct( 'SSO denied: ' . $reason );
	}

	/**
	 * @return string
	 */
	public function reason() {
		return $this->reason;
	}
}
