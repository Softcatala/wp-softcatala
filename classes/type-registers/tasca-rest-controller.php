<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class TascaRestController
 *
 * Restricts the auto-registered WP REST routes for the tasca post type so that
 * anonymous requests return HTTP 401. Authenticated requests from Gutenberg or
 * the WP admin pass through normally via the parent class.
 *
 * Defense-in-depth: sc_only_allow_logged_in_rest_access already restricts REST
 * globally, but that global filter may be relaxed for public endpoints in future.
 * This controller ensures tasca routes remain auth-gated independently.
 */
class TascaRestController extends \WP_REST_Posts_Controller {

	/**
	 * Restrict listing tasca posts to logged-in users.
	 *
	 * @param WP_REST_Request $request Full REST request.
	 * @return true|WP_Error True if allowed; WP_Error if not.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Heu d\'iniciar sessió per accedir a les tasques.', 'softcatala' ),
				array( 'status' => 401 )
			);
		}

		return parent::get_items_permissions_check( $request );
	}

	/**
	 * Restrict reading a single tasca post to logged-in users.
	 *
	 * @param WP_REST_Request $request Full REST request.
	 * @return true|WP_Error True if allowed; WP_Error if not.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Heu d\'iniciar sessió per accedir a les tasques.', 'softcatala' ),
				array( 'status' => 401 )
			);
		}

		return parent::get_item_permissions_check( $request );
	}
}
