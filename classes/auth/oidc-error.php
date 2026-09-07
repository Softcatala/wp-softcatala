<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Auth;

/**
 * A protocol-level failure: the issuer was unreachable, refused the code
 * exchange, or returned an ID token that did not verify.
 *
 * Distinct from SsoDenied, which is a policy refusal of a perfectly valid
 * token. Both end the login, but only this one means something is broken.
 */
class OidcError extends \Exception {
}
