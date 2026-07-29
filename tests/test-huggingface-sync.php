<?php
/**
 * Class HuggingFaceSyncTest
 *
 * @package Softcatala
 */

require_once('sc_tests.php');

use Softcatala\Sync\HuggingFace;

/**
 * Tests of the Hugging Face sync helpers
 */
class HuggingFaceSyncTest extends SCTests {

	/**
	 * The Catalan description is extracted from any heading level
	 */
	function test_extracts_catalan_description() {
		$readme = "---\nlicense: mit\npretty_name: Test\n---\n# Title\n\nEnglish intro.\n\n## Descripció (ca)\n\nText en català.\n\nSegon paràgraf.\n\n## License\n\nMIT.";

		$expected = "Text en català.\n\nSegon paràgraf.";
		$this->assertEquals( $expected, HuggingFace::extract_catalan_description( $readme ) );
	}

	/**
	 * Heading level is respected: deeper subsections belong to the section
	 */
	function test_extraction_keeps_subsections() {
		$readme = "### Descripció (ca)\n\nIntro.\n\n#### Detalls\n\nMés text.\n\n### Altres\n\nFora.";

		$expected = "Intro.\n\n#### Detalls\n\nMés text.";
		$this->assertEquals( $expected, HuggingFace::extract_catalan_description( $readme ) );
	}

	/**
	 * Missing section returns null
	 */
	function test_extraction_returns_null_when_missing() {
		$this->assertNull( HuggingFace::extract_catalan_description( "# Title\n\nOnly English." ) );
		$this->assertNull( HuggingFace::extract_catalan_description( '' ) );
	}

	/**
	 * Section at the end of the file is extracted
	 */
	function test_extraction_at_end_of_file() {
		$readme = "# Title\n\nIntro.\n\n## Descripció (ca)\n\nText final.";

		$this->assertEquals( 'Text final.', HuggingFace::extract_catalan_description( $readme ) );
	}

	/**
	 * Markdown paragraphs, lists and inline styles are converted
	 */
	function test_markdown_to_html() {
		$markdown = "Un **corpus** amb [enllaç](https://example.org).\n\n- Primer\n- Segon";

		$html = HuggingFace::markdown_to_html( $markdown );

		$this->assertStringContainsString( '<p>Un <strong>corpus</strong> amb <a href="https://example.org">enllaç</a>.</p>', $html );
		$this->assertStringContainsString( '<ul><li>Primer</li><li>Segon</li></ul>', $html );
	}

	/**
	 * Known licenses map to name and URL, unknown ones fall back to the id
	 */
	function test_license_mapping() {
		$mit = HuggingFace::get_license_info( 'mit' );
		$this->assertEquals( 'MIT License', $mit['license_name'] );
		$this->assertEquals( 'https://opensource.org/licenses/MIT', $mit['license_url'] );

		$cc = HuggingFace::get_license_info( array( 'cc-by-4.0' ) );
		$this->assertEquals( 'Creative Commons BY 4.0', $cc['license_name'] );

		$other = HuggingFace::get_license_info( 'other', 'https://huggingface.co/datasets/softcatala/example' );
		$this->assertEquals( 'Altres condicions', $other['license_name'] );
		$this->assertEquals( 'https://huggingface.co/datasets/softcatala/example', $other['license_url'] );

		$unknown = HuggingFace::get_license_info( 'my-own-license' );
		$this->assertEquals( 'MY-OWN-LICENSE', $unknown['license_name'] );
		$this->assertEquals( '', $unknown['license_url'] );

		$this->assertNull( HuggingFace::get_license_info( '' ) );
	}
}
