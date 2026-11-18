<?php
/**
 * Tests for Softcatala\Blocks\Editor: everything is off until the
 * block_editor flag is on, and with it on the editor is narrowed to the
 * theme's palette, blocks, post types and styles.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Blocks\Editor;

/**
 * Class BlockEditorTest
 */
class BlockEditorTest extends SCTests {

	function set_up() {
		parent::set_up();
		$this->set_flag( false );
	}

	function tear_down() {
		$this->set_flag( false );
		unset( $_GET[ SC_Settings::OVERRIDE_PARAM ] );
		parent::tear_down();
	}

	private function set_flag( $on ) {
		if ( $on ) {
			update_option( SC_Settings::SETTINGS_BLOCK_EDITOR, 'on' );
		} else {
			delete_option( SC_Settings::SETTINGS_BLOCK_EDITOR );
		}
		SC_Settings::get_instance()->reload();
		WP_Theme_JSON_Resolver::clean_cached_data();
	}

	private function palette_slugs() {
		$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
		$slugs   = array();
		foreach ( is_array( $palette ) ? $palette : array() as $entry ) {
			if ( is_array( $entry ) && isset( $entry['slug'] ) ) {
				$slugs[] = $entry['slug'];
			}
		}
		return $slugs;
	}

	function test_flag_is_defined_and_overridable() {
		$flags = SC_Settings::get_instance()->get_flags();
		$this->assertArrayHasKey( SC_Settings::SETTINGS_BLOCK_EDITOR, $flags );
		$this->assertTrue( $flags[ SC_Settings::SETTINGS_BLOCK_EDITOR ]['overridable'] );
	}

	function test_flag_off_leaves_theme_json_alone() {
		$this->assertNotContains( 'vermell-corporatiu', $this->palette_slugs() );
	}

	function test_flag_on_adds_palette_and_gradient() {
		$this->set_flag( true );

		$this->assertSame(
			array( 'vermell-corporatiu', 'vermell-fosc', 'vermell-llampant', 'text', 'text-secundari', 'fons-gris', 'fons-vermell', 'blanc' ),
			$this->palette_slugs()
		);

		$gradients = wp_get_global_settings( array( 'color', 'gradients', 'theme' ) );
		$this->assertSame( array( 'degradat-corporatiu' ), wp_list_pluck( $gradients, 'slug' ) );
	}

	function test_flag_on_closes_the_free_pickers() {
		$this->set_flag( true );

		$this->assertFalse( wp_get_global_settings( array( 'color', 'custom' ) ) );
		$this->assertFalse( wp_get_global_settings( array( 'color', 'customGradient' ) ) );
		$this->assertFalse( wp_get_global_settings( array( 'color', 'defaultPalette' ) ) );
		$this->assertFalse( wp_get_global_settings( array( 'typography', 'customFontSize' ) ) );
	}

	function test_orange_is_not_a_palette_colour() {
		$this->set_flag( true );

		$colours = wp_list_pluck( wp_get_global_settings( array( 'color', 'palette', 'theme' ) ), 'color' );
		$this->assertNotContains( '#ff8200', array_map( 'strtolower', $colours ) );
	}

	function test_override_param_turns_it_on_for_one_request() {
		$_GET[ SC_Settings::OVERRIDE_PARAM ] = SC_Settings::SETTINGS_BLOCK_EDITOR;
		SC_Settings::get_instance()->reload();
		WP_Theme_JSON_Resolver::clean_cached_data();

		$this->assertTrue( Editor::is_active() );
		$this->assertContains( 'vermell-corporatiu', $this->palette_slugs() );
	}

	function test_allowed_blocks_untouched_when_off() {
		$this->assertTrue( Editor::allowed_blocks( true ) );
	}

	function test_allowed_blocks_when_on() {
		$this->set_flag( true );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );

		$blocks = Editor::allowed_blocks( true );

		foreach ( array( 'core/paragraph', 'core/columns', 'core/column', 'core/table', 'core/list-item', 'core/freeform', 'core/media-text' ) as $block ) {
			$this->assertContains( $block, $blocks );
		}
		$this->assertNotContains( 'core/cover', $blocks );
		$this->assertNotContains( 'core/html', $blocks );
	}

	function test_html_block_for_users_with_unfiltered_html() {
		$this->set_flag( true );
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $admin );
		wp_set_current_user( $admin );

		$this->assertTrue( current_user_can( 'unfiltered_html' ) );
		$this->assertContains( 'core/html', Editor::allowed_blocks( true ) );
	}

	function test_post_types_untouched_when_off() {
		$this->assertTrue( Editor::use_for_post_type( true, 'programa' ) );
	}

	/**
	 * @dataProvider provider_post_types
	 */
	function test_post_types_when_on( $post_type, $expected ) {
		$this->set_flag( true );
		$this->assertSame( $expected, Editor::use_for_post_type( true, $post_type ) );
	}

	function provider_post_types() {
		return array(
			'post'          => array( 'post', true ),
			'page'          => array( 'page', true ),
			'projecte'      => array( 'projecte', true ),
			'resum_mensual' => array( 'resum_mensual', true ),
			'programa'      => array( 'programa', false ),
			'tasca'         => array( 'tasca', false ),
			'dadesobertes'  => array( 'dadesobertes', false ),
		);
	}

	function test_listed_post_types_keep_the_classic_editor_choice() {
		$this->set_flag( true );
		$this->assertFalse( Editor::use_for_post_type( false, 'page' ) );
	}

	function test_per_post_choice_follows_post_type() {
		$this->set_flag( true );
		$programa = self::factory()->post->create( array( 'post_type' => 'programa' ) );
		$page     = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertFalse( Editor::use_for_post( true, get_post( $programa ) ) );
		$this->assertTrue( Editor::use_for_post( true, get_post( $page ) ) );
	}

	function test_block_styles_registered_only_when_on() {
		$registry = WP_Block_Styles_Registry::get_instance();

		Editor::register_styles();
		$this->assertFalse( $registry->is_registered( 'core/table', 'assistencia' ) );

		$this->set_flag( true );
		Editor::register_styles();

		$this->assertTrue( $registry->is_registered( 'core/table', 'assistencia' ) );
		$this->assertTrue( $registry->is_registered( 'core/paragraph', 'avis-info' ) );
		$this->assertTrue( $registry->is_registered( 'core/group', 'avis-atencio' ) );

		foreach ( Editor::config( 'styles' ) as $block => $styles ) {
			foreach ( array_keys( $styles ) as $name ) {
				$registry->unregister( $block, $name );
			}
		}
	}

	function test_every_styled_block_is_allowed() {
		$allowed = Editor::config( 'blocks' );
		foreach ( array_keys( Editor::config( 'styles' ) ) as $block ) {
			$this->assertContains( $block, $allowed );
		}
	}

	function test_editor_stylesheet_is_built() {
		$this->assertFileExists( get_template_directory() . '/' . Editor::EDITOR_STYLE );
	}

	private function unregister_patterns() {
		$registry = WP_Block_Patterns_Registry::get_instance();
		foreach ( array( 'softcatala/trobada', 'softcatala/sessio' ) as $slug ) {
			if ( $registry->is_registered( $slug ) ) {
				$registry->unregister( $slug );
			}
		}
		if ( WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( 'softcatala' ) ) {
			unregister_block_pattern_category( 'softcatala' );
		}
	}

	function test_patterns_registered_only_when_on() {
		$this->unregister_patterns();
		$registry = WP_Block_Patterns_Registry::get_instance();

		Editor::register_patterns();
		$this->assertFalse( $registry->is_registered( 'softcatala/trobada' ) );

		$this->set_flag( true );
		Editor::register_patterns();

		$this->assertTrue( WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( 'softcatala' ) );
		$this->assertTrue( $registry->is_registered( 'softcatala/sessio' ) );

		$trobada = $registry->get_registered( 'softcatala/trobada' );
		$this->assertSame( 'Pàgina de trobada', $trobada['title'] );
		$this->assertSame( array( 'softcatala' ), $trobada['categories'] );
		$this->assertSame( array( 'core/post-content' ), $trobada['blockTypes'] );
		$this->assertSame( array( 'page' ), $trobada['postTypes'] );
		$this->assertStringStartsWith( '<!-- wp:paragraph', $trobada['content'] );
		$this->assertStringNotContainsString( '?>', $trobada['content'] );

		$this->unregister_patterns();
	}

	function test_trobada_pattern_renders_totals() {
		$pattern = Editor::read_pattern( get_template_directory() . '/block-patterns/trobada.php' );
		$html    = do_blocks( $pattern['properties']['content'] );

		// The sample row: ✓ in three sessions, ✓ +1 at dinner.
		$this->assertMatchesRegularExpression( '#<tr class="sc-assistencia-total"><td>Total</td>(<td[^>]*>1</td>){3}<td[^>]*>2</td></tr>#', $html );
	}

	function test_pattern_files_all_read() {
		foreach ( glob( get_template_directory() . '/block-patterns/*.php' ) as $file ) {
			$this->assertNotNull( Editor::read_pattern( $file ), basename( $file ) );
		}
	}
}
