<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Ads;

/**
 * Resolves the Revive zone each ad slot shows on a request.
 *
 * Layers, first match wins: ?ads= querystring, the page's own override, the page's
 * section, the default section. In the page and section layers an empty value
 * inherits from the next layer and 0 switches the slot off.
 */
class ZoneResolver {

	const QUERY_PARAM = 'ads';

	/** ACF options page post_id; section values are stored as options named "<this>_<section>_<slot>". */
	const OPTIONS_ID = 'sc_publicitat';

	const META_PREFIX = 'sc_ads_';

	private static $config = null;

	private $slots;
	private $sections;

	public function __construct() {
		$config         = self::load_config();
		$this->slots    = array_keys( $config['slots'] );
		$this->sections = $config['sections'];
	}

	/**
	 * @return array config/ad-slots.php
	 */
	public static function load_config() {
		if ( null === self::$config ) {
			self::$config = require get_template_directory() . '/config/ad-slots.php';
		}

		return self::$config;
	}

	/**
	 * Option holding a section's zone for a slot, as written by the Publicitat options page.
	 */
	public static function option_name( $section, $slot ) {
		return self::OPTIONS_ID . '_' . self::field_name( $section, $slot );
	}

	/**
	 * ACF field name of a section's zone for a slot on the options page.
	 */
	public static function field_name( $section, $slot ) {
		return $section . '_' . $slot;
	}

	/**
	 * Post meta key (and ACF field name) of a page's own zone for a slot.
	 */
	public static function meta_key( $slot ) {
		return self::META_PREFIX . $slot;
	}

	/**
	 * Raw ?ads= value of the current request, or '' when absent or not a string.
	 */
	public static function read_query() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, per-request display toggle.
		return isset( $_GET[ self::QUERY_PARAM ] ) && is_string( $_GET[ self::QUERY_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_PARAM ] ) ) : '';
	}

	/**
	 * Whether the current request carries at least one valid ?ads= token.
	 */
	public static function has_query_overrides() {
		$parsed = ( new self() )->parse_query( self::read_query() );

		return $parsed['debug'] || $parsed['zones'] || $parsed['hidden'];
	}

	/**
	 * Parses "debug,slot:zone,-slot". Unknown slots and anything that is not a positive zone are dropped.
	 *
	 * @param string $raw
	 * @return array{debug: bool, zones: array<string, int>, hidden: array<string, bool>}
	 */
	public function parse_query( $raw ) {
		$parsed = array(
			'debug'  => false,
			'zones'  => array(),
			'hidden' => array(),
		);

		foreach ( explode( ',', (string) $raw ) as $token ) {
			$token = trim( $token );

			if ( 'debug' === $token ) {
				$parsed['debug'] = true;
			} elseif ( '-' === substr( $token, 0, 1 ) && in_array( substr( $token, 1 ), $this->slots, true ) ) {
				$parsed['hidden'][ substr( $token, 1 ) ] = true;
			} elseif ( preg_match( '/^([a-z0-9_]+):([1-9][0-9]{0,8})$/', $token, $m ) && in_array( $m[1], $this->slots, true ) ) {
				$parsed['zones'][ $m[1] ] = absint( $m[2] );
			}
		}

		return $parsed;
	}

	/**
	 * @param string     $section   Section key from Sections.
	 * @param int        $post_id   Current page or post, 0 when there is none.
	 * @param string     $raw_query ?ads= value.
	 * @return array{debug: bool, slots: array<string, array{zone: int|null, source: string}>}
	 */
	public function resolve( $section, $post_id = 0, $raw_query = '' ) {
		$query = $this->parse_query( $raw_query );
		$slots = array();

		foreach ( $this->slots as $slot ) {
			$slots[ $slot ] = $this->resolve_slot( $slot, $section, $post_id, $query );
		}

		return array(
			'debug' => $query['debug'],
			'slots' => $slots,
		);
	}

	private function resolve_slot( $slot, $section, $post_id, $query ) {
		if ( isset( $query['hidden'][ $slot ] ) ) {
			return self::result( null, 'query' );
		}
		if ( isset( $query['zones'][ $slot ] ) ) {
			return self::result( $query['zones'][ $slot ], 'query' );
		}

		if ( $post_id ) {
			$value = get_post_meta( $post_id, self::meta_key( $slot ), true );
			if ( '' !== (string) $value ) {
				return self::result( self::to_zone( $value ), 'page' );
			}
		}

		if ( Sections::DEFAULT_SECTION !== $section ) {
			$value = $this->section_value( $section, $slot );
			if ( '' !== $value ) {
				return self::result( self::to_zone( $value ), 'section' );
			}
		}

		return self::result( self::to_zone( $this->section_value( Sections::DEFAULT_SECTION, $slot ) ), 'default' );
	}

	/**
	 * Saved value, or the section's seed while the option has never been written.
	 */
	private function section_value( $section, $slot ) {
		$value = get_option( self::option_name( $section, $slot ), false );

		if ( false === $value ) {
			$value = $this->sections[ $section ]['seed'][ $slot ] ?? '';
		}

		return (string) $value;
	}

	private static function to_zone( $value ) {
		$zone = absint( $value );

		return $zone > 0 ? $zone : null;
	}

	private static function result( $zone, $source ) {
		return array(
			'zone'   => $zone,
			'source' => $source,
		);
	}
}
