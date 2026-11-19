<?php
/**
 * Tests for Softcatala\Blocks\ThemeBlocks: the theme's own blocks render the
 * same thing as the shortcodes they replace.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class ThemeBlocksTest
 */
class ThemeBlocksTest extends SCTests {

	function set_up() {
		parent::set_up();
		Softcatala\Blocks\ThemeBlocks::register();
	}

	function test_native_blocks_are_registered() {
		$registry = WP_Block_Type_Registry::get_instance();

		foreach ( array( 'sc/index', 'sc/ia-data', 'sc/ia-local-checker', 'sc/navega-en-catala' ) as $name ) {
			$this->assertTrue( $registry->is_registered( $name ), $name );
			$this->assertSame( 'softcatala', $registry->get_registered( $name )->category, $name );
		}
	}

	function test_targetes_needs_acf_pro() {
		// The test site runs the free ACF, which has no blocks.
		$this->assertFalse( function_exists( 'acf_register_block_type' ) );
		$this->assertFalse( WP_Block_Type_Registry::get_instance()->is_registered( 'sc/targetes' ) );
	}

	function test_every_theme_block_is_allowed() {
		$allowed = Softcatala\Blocks\Editor::config( 'blocks' );

		foreach ( glob( get_template_directory() . '/blocks/*/block.json' ) as $file ) {
			$metadata = json_decode( file_get_contents( $file ), true );
			$this->assertContains( $metadata['name'], $allowed, $metadata['name'] );
		}
	}

	private function in_post( $content, $markup ) {
		$post_id         = self::factory()->post->create( array( 'post_content' => $content ) );
		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		$html = do_blocks( $markup );
		wp_reset_postdata();

		return $html;
	}

	function test_index_links_the_posts_anchored_headings() {
		$content = '<h2 id="primer" class="wp-block-heading">Primer &amp; segon</h2><h2>Sense ancoratge</h2><h2 id="tercer">Tercer</h2>';
		$html    = $this->in_post( $content, '<!-- wp:sc/index /-->' );

		$this->assertStringContainsString( '<nav class="wp-block-sc-index" aria-label="En aquesta pàgina">', $html );
		$this->assertStringContainsString( '<p class="sc-index-titol">En aquesta pàgina</p>', $html );
		$this->assertStringContainsString( '<li><a href="#primer">Primer &amp; segon</a></li><li><a href="#tercer">Tercer</a></li>', $html );
		$this->assertStringNotContainsString( 'Sense ancoratge', $html );
	}

	function test_index_title_can_be_changed_or_left_out() {
		$content = '<h2 id="a">A</h2>';

		$this->assertStringContainsString( '<p class="sc-index-titol">Sumari</p>', $this->in_post( $content, '<!-- wp:sc/index {"titol":"Sumari"} /-->' ) );
		$this->assertStringNotContainsString( 'sc-index-titol', $this->in_post( $content, '<!-- wp:sc/index {"titol":""} /-->' ) );
	}

	function test_index_without_anchored_headings_renders_nothing() {
		$this->assertSame( '', trim( $this->in_post( '<h2>Res</h2>', '<!-- wp:sc/index /-->' ) ) );
	}

	function test_ia_data_block_renders_like_the_shortcode() {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'headers'  => array( 'content-type' => 'application/json' ),
					'body'     => wp_json_encode(
						array(
							'text' => array(
								'model' => 'Model',
								'clam'  => 'CLAM%',
							),
							'data' => array(
								array(
									'model' => 'model-a',
									'clam'  => 68.12,
								),
							),
						)
					),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		);

		$block     = do_blocks( '<!-- wp:sc/ia-data {"url":"https://example.org/data.json","cache":0} /-->' );
		$shortcode = do_shortcode( '[ia-data url="https://example.org/data.json" cache="0"]' );

		$this->assertStringContainsString( 'model-a', $block );
		$this->assertSame( trim( $shortcode ), trim( $block ) );
	}

	/**
	 * The checker's CSS is scoped to its own root class: .cc is also the
	 * footer's licence column, and #sct-ia only exists on the ia-local page.
	 */
	function test_checker_css_is_scoped_to_the_widget() {
		$css = preg_replace( '#/\*.*?\*/#s', '', file_get_contents( get_template_directory() . '/static/css/ia-local-checker.css' ) );

		$this->assertDoesNotMatchRegularExpression( '/\.cc(?=[\s:{,])/', $css );
		$this->assertStringContainsString( 'class="cc sc-ia-checker"', do_blocks( '<!-- wp:sc/ia-local-checker /-->' ) );
	}

	function test_widget_blocks_render_like_their_shortcodes() {
		$this->assertSame( trim( do_shortcode( '[ia-local-checker]' ) ), trim( do_blocks( '<!-- wp:sc/ia-local-checker /-->' ) ) );
		$this->assertSame( trim( do_shortcode( '[navega-en-catala]' ) ), trim( do_blocks( '<!-- wp:sc/navega-en-catala /-->' ) ) );
		$this->assertStringContainsString( 'navega-user-agent', do_blocks( '<!-- wp:sc/navega-en-catala /-->' ) );
	}
}
