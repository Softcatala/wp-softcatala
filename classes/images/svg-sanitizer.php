<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Images;

use enshrined\svgSanitize\Sanitizer;

/**
 * Cleans SVG uploads before WordPress stores them.
 *
 * @package Softcatala\Images
 */
class SvgSanitizer {

	const MIME = 'image/svg+xml';

	/**
	 * @return void
	 */
	public static function register() {
		if ( ! self::is_available() ) {
			return;
		}

		add_filter( 'upload_mimes', array( __CLASS__, 'allow_svg' ) );
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'prefilter' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( __CLASS__, 'prefilter' ) );
		add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'check_filetype' ), 10, 4 );
	}

	/**
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( Sanitizer::class );
	}

	/**
	 * @param array $mimes Allowed upload types.
	 * @return array
	 */
	public static function allow_svg( $mimes ) {
		$mimes['svg'] = self::MIME;

		return $mimes;
	}

	/**
	 * @param string $filename File name.
	 * @return bool
	 */
	public static function is_svg_filename( $filename ) {
		return 'svg' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	}

	/**
	 * @param string $svg SVG document.
	 * @return string|false Cleaned document, or false when it is not parseable XML.
	 */
	public static function sanitize_string( $svg ) {
		$sanitizer = new Sanitizer();
		$sanitizer->removeRemoteReferences( true );

		$clean = $sanitizer->sanitize( $svg );

		return is_string( $clean ) && '' !== $clean ? $clean : false;
	}

	/**
	 * @param string $path File to clean in place.
	 * @return bool
	 */
	public static function sanitize_file( $path ) {
		$svg = is_readable( $path ) ? file_get_contents( $path ) : false;

		if ( false === $svg ) {
			return false;
		}

		$clean = self::sanitize_string( $svg );

		return false !== $clean && false !== file_put_contents( $path, $clean );
	}

	/**
	 * @param array $file Upload as WordPress receives it.
	 * @return array
	 */
	public static function prefilter( $file ) {
		if ( empty( $file['name'] ) || ! self::is_svg_filename( $file['name'] ) || ! empty( $file['error'] ) ) {
			return $file;
		}

		if ( empty( $file['tmp_name'] ) || ! self::sanitize_file( $file['tmp_name'] ) ) {
			$file['error'] = __( 'El fitxer SVG no és vàlid.', 'softcatala' );
		}

		return $file;
	}

	/**
	 * Restores the SVG type core drops when finfo reports the file as text or XML.
	 *
	 * @param array      $info     Ext, type and proper_filename as determined so far.
	 * @param string     $file     Path to the file.
	 * @param string     $filename File name.
	 * @param array|null $mimes    Allowed types.
	 * @return array
	 */
	public static function check_filetype( $info, $file, $filename, $mimes ) {
		if ( ! self::is_svg_filename( $filename ) ) {
			return $info;
		}

		$allowed = null === $mimes ? get_allowed_mime_types() : $mimes;

		if ( ! in_array( self::MIME, $allowed, true ) ) {
			return $info;
		}

		$info['ext']  = 'svg';
		$info['type'] = self::MIME;

		return $info;
	}
}
