<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Ads;

/**
 * Production's page cache is WP Super Cache; clear all of it when ad configuration changes.
 */
class PageCache {

	public static function clear() {
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
	}
}
