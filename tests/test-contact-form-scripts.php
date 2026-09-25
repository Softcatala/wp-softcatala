<?php
/**
 * The help modal (contact_form.twig) only works when contact_form.js is on
 * the page and can read scajax.ajax_url. The corrector lost that global when
 * its localize call stayed bound to a handle the page no longer enqueued, so
 * the data is now attached centrally to 'sc-js-contacte' and every template
 * that renders the modal has to enqueue that handle.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class ContactFormScriptsTest
 */
class ContactFormScriptsTest extends SCTests {

	public function tear_down() {
		wp_dequeue_script( 'sc-js-contacte' );
		wp_deregister_script( 'sc-js-contacte' );

		parent::tear_down();
	}

	function test_enqueuing_the_handle_from_a_template_yields_scajax() {
		// Templates enqueue before wp_head() fires wp_enqueue_scripts.
		wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
		do_action( 'wp_enqueue_scripts' );

		$data = wp_scripts()->get_data( 'sc-js-contacte', 'data' );

		$this->assertStringContainsString( 'var scajax = ', $data );
		$this->assertStringContainsString( admin_url( 'admin-ajax.php' ), $data );
	}

	function test_pages_without_the_form_do_not_define_scajax_on_it() {
		do_action( 'wp_enqueue_scripts' );

		$this->assertFalse( wp_script_is( 'sc-js-contacte', 'registered' ) );
	}

	function test_every_template_rendering_the_modal_enqueues_the_script() {
		$theme = get_template_directory();

		$modal_twigs = array();
		foreach ( glob( "$theme/templates/*.twig" ) as $twig ) {
			if ( strpos( file_get_contents( $twig ), 'contact_form.twig' ) !== false ) {
				$modal_twigs[] = basename( $twig );
			}
		}
		$this->assertNotEmpty( $modal_twigs );

		$missing = array();
		foreach ( glob( "$theme/*.php" ) as $php ) {
			$source = file_get_contents( $php );
			preg_match_all( "/'([a-z0-9_-]+\.twig)'/", $source, $matches );
			if ( ! array_intersect( $matches[1], $modal_twigs ) ) {
				continue;
			}
			if ( strpos( $source, "'sc-js-contacte'" ) === false ) {
				$missing[] = basename( $php );
			}
		}

		$this->assertSame( array(), $missing, 'Templates rendering contact_form.twig without enqueuing sc-js-contacte' );
	}
}
