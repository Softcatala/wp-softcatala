<?php
/**
 * SVG uploads are allowed only because they are sanitised first, and the
 * anonymous program form only takes images.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Images\SvgSanitizer;

/**
 * Class SvgUploadTest
 */
class SvgUploadTest extends SCTests {

	const HOSTILE = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert(1)"><script>alert(2)</script><a xlink:href="javascript:alert(3)"><circle r="4"/></a><image href="https://evil.example/x.png"/></svg>';

	private $tmp = array();

	public function tear_down() {
		foreach ( $this->tmp as $path ) {
			@unlink( $path );
		}

		parent::tear_down();
	}

	private function tmp_file( $contents ) {
		$path = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $path, $contents );
		$this->tmp[] = $path;

		return $path;
	}

	function test_sanitizer_is_installed_and_registered() {
		$this->assertTrue( SvgSanitizer::is_available() );
		$this->assertNotFalse( has_filter( 'upload_mimes', array( SvgSanitizer::class, 'allow_svg' ) ) );
		$this->assertNotFalse( has_filter( 'wp_handle_upload_prefilter', array( SvgSanitizer::class, 'prefilter' ) ) );
		$this->assertArrayHasKey( 'svg', get_allowed_mime_types() );
	}

	function test_scripts_handlers_and_remote_references_are_removed() {
		$clean = SvgSanitizer::sanitize_string( self::HOSTILE );

		$this->assertNotFalse( $clean );
		$this->assertStringContainsString( '<circle', $clean );
		$this->assertStringNotContainsString( '<script', $clean );
		$this->assertStringNotContainsString( 'onload', $clean );
		$this->assertStringNotContainsString( 'javascript:', $clean );
		$this->assertStringNotContainsString( 'evil.example', $clean );
	}

	function test_non_xml_is_rejected() {
		$this->assertFalse( SvgSanitizer::sanitize_string( '<?php echo 1;' ) );
		$this->assertFalse( SvgSanitizer::sanitize_string( '' ) );
	}

	function test_prefilter_rewrites_a_hostile_file_in_place() {
		$path = $this->tmp_file( self::HOSTILE );

		$file = SvgSanitizer::prefilter( array( 'name' => 'logo.SVG', 'tmp_name' => $path ) );

		$this->assertArrayNotHasKey( 'error', $file );
		$this->assertStringNotContainsString( '<script', file_get_contents( $path ) );
	}

	function test_prefilter_flags_a_file_that_is_not_svg() {
		$path = $this->tmp_file( 'GIF89a not really' );

		$file = SvgSanitizer::prefilter( array( 'name' => 'logo.svg', 'tmp_name' => $path ) );

		$this->assertNotEmpty( $file['error'] );
	}

	function test_prefilter_leaves_other_files_alone() {
		$path = $this->tmp_file( 'GIF89a' );

		$file = SvgSanitizer::prefilter( array( 'name' => 'logo.gif', 'tmp_name' => $path ) );

		$this->assertArrayNotHasKey( 'error', $file );
		$this->assertEquals( 'GIF89a', file_get_contents( $path ) );
	}

	function test_check_filetype_restores_svg_when_allowed() {
		$info = SvgSanitizer::check_filetype( array( 'ext' => false, 'type' => false, 'proper_filename' => false ), '/tmp/x', 'logo.svg', array( 'svg' => 'image/svg+xml' ) );

		$this->assertEquals( 'svg', $info['ext'] );
		$this->assertEquals( 'image/svg+xml', $info['type'] );
	}

	function test_check_filetype_does_not_override_when_svg_is_not_allowed() {
		$info = SvgSanitizer::check_filetype( array( 'ext' => false, 'type' => false, 'proper_filename' => false ), '/tmp/x', 'logo.svg', array( 'png' => 'image/png' ) );

		$this->assertFalse( $info['ext'] );
	}

	function test_wordpress_stores_the_sanitised_svg() {
		$path   = $this->tmp_file( self::HOSTILE );
		$file   = array( 'name' => 'logo.svg', 'tmp_name' => $path, 'size' => filesize( $path ), 'error' => 0, 'type' => 'image/svg+xml' );
		$upload = wp_handle_sideload( $file, array( 'test_form' => false, 'mimes' => sc_upload_image_mimes() ) );

		$this->assertArrayNotHasKey( 'error', $upload, print_r( $upload, true ) );
		$this->tmp[] = $upload['file'];

		$this->assertEquals( 'image/svg+xml', $upload['type'] );
		$this->assertStringNotContainsString( '<script', file_get_contents( $upload['file'] ) );
		$this->assertStringContainsString( '<circle', file_get_contents( $upload['file'] ) );
	}

	function test_wordpress_refuses_a_fake_svg() {
		$path   = $this->tmp_file( 'GIF89a not really' );
		$file   = array( 'name' => 'logo.svg', 'tmp_name' => $path, 'size' => filesize( $path ), 'error' => 0, 'type' => 'image/svg+xml' );
		$upload = wp_handle_sideload( $file, array( 'test_form' => false, 'mimes' => sc_upload_image_mimes() ) );

		$this->assertArrayHasKey( 'error', $upload );
	}

	/*
	 * Anonymous form allow-list
	 */

	function test_form_accepts_images_within_the_size_cap() {
		foreach ( array( 'logo.png', 'logo.JPG', 'logo.webp', 'logo.gif', 'logo.svg' ) as $name ) {
			$this->assertTrue( sc_upload_is_acceptable_image( array( 'name' => $name, 'size' => 1024 ) ), $name );
		}
	}

	function test_form_refuses_non_images_and_oversized_files() {
		foreach ( array( 'setup.exe', 'doc.pdf', 'archive.zip', 'page.html', 'shell.php', 'logo.png.php', 'noext' ) as $name ) {
			$this->assertFalse( sc_upload_is_acceptable_image( array( 'name' => $name, 'size' => 1024 ) ), $name );
		}

		$this->assertFalse( sc_upload_is_acceptable_image( array( 'name' => 'logo.png', 'size' => SC_UPLOAD_MAX_BYTES + 1 ) ) );
		$this->assertFalse( sc_upload_is_acceptable_image( array( 'name' => 'logo.png', 'size' => 0 ) ) );
	}
}
