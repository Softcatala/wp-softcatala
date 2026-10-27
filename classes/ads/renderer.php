<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Ads;

/**
 * Builds the `ads` template context and records which slots rendered a zone.
 *
 * Twig includes get a copy of the context, so a flag set inside ads/slot.twig never
 * reaches the footer. The slot partial records each zone here instead, and base.twig
 * loads the Revive script only when something was recorded.
 */
class Renderer {

	private static $zones = array();

	/**
	 * `ads` context for the current request. When ad slots are disabled no slot resolves.
	 *
	 * @param bool $enabled Active value of the ad_slots flag.
	 * @return array
	 */
	public static function context( $enabled ) {
		$config  = ZoneResolver::load_config();
		$context = array(
			'enabled'      => (bool) $enabled,
			'debug'        => false,
			'slots'        => array(),
			'revive_id'    => $config['revive_id'],
			'delivery_url' => $config['delivery_url'],
		);

		if ( ! $enabled ) {
			return $context;
		}

		$post_id  = is_singular() ? get_queried_object_id() : 0;
		$resolved = ( new ZoneResolver() )->resolve( ( new Sections() )->current(), $post_id, ZoneResolver::read_query() );

		return array_merge( $context, $resolved );
	}

	/**
	 * Records a rendered zone. Returns '' so templates can call it inline.
	 */
	public static function mark( $zone ) {
		self::$zones[] = (int) $zone;

		return '';
	}

	public static function script_needed() {
		return ! empty( self::$zones );
	}

	public static function reset() {
		self::$zones = array();
	}
}
