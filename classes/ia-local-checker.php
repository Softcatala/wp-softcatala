<?php
/**
 * @package Softcatala
 */

/**
 * Shortcode for the local AI compatibility checker widget.
 * Usage: [ia-local-checker]
 */
class SC_Ia_Local_Checker {

	public static function init() {
		new SC_Ia_Local_Checker();
	}

	public function __construct() {
		add_shortcode( 'ia-local-checker', array( $this, 'shortcode' ) );
	}

	public function shortcode() {
		wp_enqueue_style(
			'sc-css-ia-local-checker',
			get_template_directory_uri() . '/static/css/ia-local-checker.css',
			array(),
			WP_SOFTCATALA_VERSION
		);

		return Timber::compile( 'ia-local-checker.twig' );
	}
}
