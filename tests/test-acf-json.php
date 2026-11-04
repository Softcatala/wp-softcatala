<?php
/**
 * ACF works out its default load path before the theme directory is registered,
 * so on our containers that default points at a folder that does not exist.
 */
require_once( 'sc_tests.php' );

class AcfJsonTest extends SCTests {

	private $original_paths;

	public function set_up() {
		parent::set_up();
		$this->original_paths = acf_get_setting( 'load_json' );
	}

	public function tear_down() {
		acf_update_setting( 'load_json', $this->original_paths );
		parent::tear_down();
	}

	public function test_theme_folder_is_loaded_when_acf_resolved_a_stale_default() {
		acf_update_setting( 'load_json', array( '/stale/wp-content/themes/wp-softcatala/acf-json' ) );

		$this->assertContains( get_stylesheet_directory() . '/acf-json', acf_get_setting( 'load_json' ) );
	}

	public function test_theme_folder_is_not_listed_twice_when_the_default_is_right() {
		$theme_folder = get_stylesheet_directory() . '/acf-json';
		acf_update_setting( 'load_json', array( $theme_folder ) );

		$this->assertSame( array( $theme_folder ), acf_get_setting( 'load_json' ) );
	}

	public function test_plugin_paths_are_kept_after_the_theme_so_they_take_precedence() {
		acf_update_setting( 'load_json', array( '/plugins/xv-podcasts/conf/' ) );

		$this->assertSame(
			array( get_stylesheet_directory() . '/acf-json', '/plugins/xv-podcasts/conf/' ),
			acf_get_setting( 'load_json' )
		);
	}
}
