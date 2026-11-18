<?php
/**
 * Block editor set-up: theme.json data, editor styles, allowed blocks, which
 * post types use the block editor, and the theme's block style variations.
 *
 * Everything here is behind the `block_editor` feature flag (Softcatalà
 * Settings, or ?ff=block_editor per request), so it can be switched off
 * without a deploy. With the flag off, WordPress and the Classic Editor
 * plugin behave exactly as before.
 *
 * Configuration lives in config/block-editor.php; the styles' CSS in
 * frontend/src/scss/_blocks.scss (front end) and editor.scss (editor canvas).
 *
 * @package Softcatala
 */

namespace Softcatala\Blocks;

/**
 * Class Editor
 */
class Editor {

	/**
	 * Same family and weights as templates/html-header.twig.
	 */
	const FONTS_URL = 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,300;1,400;1,600;1,700;1,800&display=swap';

	/**
	 * Path of the compiled editor stylesheet, relative to the theme.
	 */
	const EDITOR_STYLE = 'static/css/editor.min.css';

	/**
	 * Late enough to run after the Classic Editor plugin's own filters (100).
	 */
	const EDITOR_CHOICE_PRIORITY = 200;

	/**
	 * Parsed config/block-editor.php.
	 *
	 * @var array|null
	 */
	private static $config = null;

	/**
	 * Registers the hooks. Each callback checks the flag itself, so flipping it
	 * takes effect on the next request.
	 */
	public static function init() {
		add_filter( 'wp_theme_json_data_theme', array( self::class, 'theme_json' ) );
		add_action( 'after_setup_theme', array( self::class, 'editor_styles' ), 20 );
		add_action( 'enqueue_block_assets', array( self::class, 'editor_fonts' ) );
		add_action( 'init', array( self::class, 'register_styles' ), 20 );
		add_filter( 'allowed_block_types_all', array( self::class, 'allowed_blocks' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( self::class, 'use_for_post_type' ), self::EDITOR_CHOICE_PRIORITY, 2 );
		add_filter( 'use_block_editor_for_post', array( self::class, 'use_for_post' ), self::EDITOR_CHOICE_PRIORITY, 2 );
	}

	/**
	 * Whether the block editor set-up is on for this request.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return \SC_Settings::get_instance()->is_active( \SC_Settings::SETTINGS_BLOCK_EDITOR );
	}

	/**
	 * The configuration array, or one key of it.
	 *
	 * @param string|null $key Top-level key.
	 * @return array
	 */
	public static function config( $key = null ) {
		if ( null === self::$config ) {
			self::$config = require get_template_directory() . '/config/block-editor.php';
		}

		return null === $key ? self::$config : self::$config[ $key ];
	}

	/**
	 * Merges the theme's settings into the theme origin of theme.json.
	 *
	 * @param \WP_Theme_JSON_Data $theme_json Theme origin data.
	 * @return \WP_Theme_JSON_Data
	 */
	public static function theme_json( $theme_json ) {
		if ( ! self::is_active() ) {
			return $theme_json;
		}

		return $theme_json->update_with( self::config( 'theme_json' ) );
	}

	/**
	 * Loads the editor canvas stylesheet. WordPress inlines it and scopes every
	 * rule to .editor-styles-wrapper.
	 */
	public static function editor_styles() {
		if ( ! self::is_active() ) {
			return;
		}

		add_theme_support( 'editor-styles' );
		add_editor_style( self::EDITOR_STYLE );
	}

	/**
	 * Open Sans inside the editor canvas. enqueue_block_assets also runs on the
	 * front end, where html-header.twig already links the font.
	 */
	public static function editor_fonts() {
		if ( ! is_admin() || ! self::is_active() ) {
			return;
		}

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- versioned by Google.
		wp_enqueue_style( 'sc-editor-fonts', self::FONTS_URL, array(), null );
	}

	/**
	 * Registers the block style variations from the configuration.
	 */
	public static function register_styles() {
		if ( ! self::is_active() ) {
			return;
		}

		foreach ( self::config( 'styles' ) as $block => $styles ) {
			foreach ( $styles as $name => $label ) {
				register_block_style(
					$block,
					array(
						'name'  => $name,
						'label' => $label,
					)
				);
			}
		}
	}

	/**
	 * Limits the inserter to the blocks the theme styles.
	 *
	 * @param bool|string[]            $allowed Blocks allowed so far (true: all).
	 * @param \WP_Block_Editor_Context $context Editor context.
	 * @return bool|string[]
	 */
	public static function allowed_blocks( $allowed, $context = null ) {
		unset( $context );

		if ( ! self::is_active() ) {
			return $allowed;
		}

		$blocks = self::config( 'blocks' );

		if ( current_user_can( 'unfiltered_html' ) ) {
			$blocks = array_merge( $blocks, self::config( 'html_blocks' ) );
		}

		return $blocks;
	}

	/**
	 * Keeps the classic editor for post types not meant for blocks. For the
	 * others the Classic Editor plugin's choice (default editor, per-user
	 * switching) still applies.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        Post type.
	 * @return bool
	 */
	public static function use_for_post_type( $use_block_editor, $post_type ) {
		if ( ! self::is_active() || in_array( $post_type, self::config( 'post_types' ), true ) ) {
			return $use_block_editor;
		}

		return false;
	}

	/**
	 * Same as use_for_post_type(), for the per-post decision the Classic Editor
	 * plugin makes when users may switch editors.
	 *
	 * @param bool     $use_block_editor Whether to use the block editor.
	 * @param \WP_Post $post             Post being edited.
	 * @return bool
	 */
	public static function use_for_post( $use_block_editor, $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return $use_block_editor;
		}

		return self::use_for_post_type( $use_block_editor, $post->post_type );
	}
}
