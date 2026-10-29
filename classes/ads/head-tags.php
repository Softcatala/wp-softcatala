<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Ads;

/**
 * Third-party ad tags that must load in <head> on pages with ads.
 *
 * Slots render after wp_head(), so Renderer::script_needed() is not known yet when the
 * head prints. Instead this reads the template context before Twig renders: pages that
 * show the ad banner set `ads_container`.
 */
class HeadTags {

	const CLICKIO_HANDLE = 'sc-clickio-ads';
	const CLICKIO_SRC    = '//s.clickiocdn.com/t/250605/360_light.js';

	/**
	 * Enqueues the Clickio tag when the page shows ads and the clickio_ads flag is on.
	 *
	 * Hooked on timber/compile/data, which runs before the template (and its wp_head()) renders.
	 *
	 * @param array $data Template context.
	 * @return array Unchanged context.
	 */
	public static function enqueue( $data ) {
		if ( ! empty( $data['ads_container'] ) && \SC_Settings::get_instance()->is_active( \SC_Settings::SETTINGS_CLICKIO_ADS ) ) {
			wp_enqueue_script( self::CLICKIO_HANDLE, self::CLICKIO_SRC, array(), null, array( 'strategy' => 'async' ) );
		}

		return $data;
	}
}
