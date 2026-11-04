<?php
/**
 * Regression coverage for slider visibility.
 */
require_once( 'sc_tests.php' );

class SliderVisibilityTest extends SCTests {

	public function test_slider_is_editable_but_not_publicly_queryable() {
		$post_type = get_post_type_object( 'slider' );

		$this->assertNotNull( $post_type );
		$this->assertTrue( $post_type->show_ui );
		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->show_in_nav_menus );
	}

	/**
	 * @dataProvider caption_shown_values
	 */
	public function test_caption_is_shown_unless_hidden( $hide_caption ) {
		$html = $this->render_slide( array( 'hide_caption' => $hide_caption ) );

		$this->assertStringContainsString( 'carousel-caption', $html );
		$this->assertStringContainsString( '<h2>Títol del destacat</h2>', $html );
	}

	public function caption_shown_values() {
		return array(
			'never saved' => array( null ),
			'unchecked'   => array( '0' ),
		);
	}

	public function test_hidden_caption_keeps_the_title_as_alt_text_and_the_link() {
		$html = $this->render_slide( array( 'hide_caption' => '1' ) );

		$this->assertStringNotContainsString( 'carousel-caption', $html );
		$this->assertStringNotContainsString( '<h2>', $html );
		$this->assertStringContainsString( 'alt="Títol del destacat"', $html );
		$this->assertStringContainsString( 'href="https://example.org/destacat"', $html );
	}

	public function test_field_group_defines_the_hide_caption_checkbox() {
		$group  = json_decode( file_get_contents( get_template_directory() . '/acf-json/group_58970c4114f78.json' ), true );
		$fields = array_column( $group['fields'], null, 'name' );

		$this->assertArrayHasKey( 'hide_caption', $fields );
		$this->assertSame( 'true_false', $fields['hide_caption']['type'] );
		$this->assertSame( 0, $fields['hide_caption']['default_value'] );
	}

	private function render_slide( $overrides ) {
		$slide = array_merge(
			array(
				'title'        => 'Títol del destacat',
				'post_excerpt' => 'Text del destacat',
				'button_title' => 'Més informació',
				'slide_link'   => 'https://example.org/destacat',
				'thumbnail'    => array( 'src' => 'https://example.org/imatge.jpg' ),
			),
			$overrides
		);

		return \Timber\Timber::compile( 'slider.twig', array( 'slides' => array( $slide ) ) );
	}
}
