<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Providers;

/**
 * Number of Catalanitzador sessions, fetched from its stats endpoint and cached.
 */
class CatalanitzadorStats {

	const STATS_URL = 'https://www.softcatala.org/catalanitzador/response.php';

	const TRANSIENT_NAME = 'total_catalanitzador_sessions';

	/** Cached in place of a count while the endpoint fails, so it is not asked on every page view. */
	const UNAVAILABLE = 'unavailable';

	/**
	 * Total sessions, or false when the endpoint is unavailable.
	 *
	 * @return int|false
	 */
	public static function sessions() {
		$stored = get_transient( self::TRANSIENT_NAME );

		if ( self::UNAVAILABLE === $stored ) {
			return false;
		}
		if ( false !== $stored ) {
			return (int) $stored;
		}

		$sessions = self::fetch();

		if ( false === $sessions ) {
			set_transient( self::TRANSIENT_NAME, self::UNAVAILABLE, HOUR_IN_SECONDS );
			return false;
		}

		set_transient( self::TRANSIENT_NAME, $sessions, DAY_IN_SECONDS );

		return $sessions;
	}

	/**
	 * @return int|false
	 */
	private static function fetch() {
		$result = ( new \SC_RestClient() )->get( self::STATS_URL );

		if ( 200 === (int) $result['code'] && is_numeric( $result['result'] ) ) {
			return (int) $result['result'];
		}

		return false;
	}
}
