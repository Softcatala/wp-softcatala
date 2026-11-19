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
	 * Pattern files, relative to the theme. Not `patterns/`: WordPress registers
	 * that folder by itself, and these must follow the feature flag.
	 */
	const PATTERNS_DIR = 'block-patterns';

	/**
	 * Pattern file headers, as WordPress reads them from a theme's patterns/.
	 */
	const PATTERN_HEADERS = array(
		'title'         => 'Title',
		'slug'          => 'Slug',
		'description'   => 'Description',
		'categories'    => 'Categories',
		'keywords'      => 'Keywords',
		'blockTypes'    => 'Block Types',
		'postTypes'     => 'Post Types',
		'viewportWidth' => 'Viewport Width',
	);

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
		add_action( 'enqueue_block_editor_assets', array( self::class, 'editor_scripts' ) );
		add_action( 'init', array( self::class, 'register_styles' ), 20 );
		add_action( 'init', array( self::class, 'register_patterns' ), 20 );
		add_filter( 'allowed_block_types_all', array( self::class, 'allowed_blocks' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( self::class, 'use_for_post_type' ), self::EDITOR_CHOICE_PRIORITY, 2 );
		add_filter( 'use_block_editor_for_post', array( self::class, 'use_for_post' ), self::EDITOR_CHOICE_PRIORITY, 2 );
		add_filter( 'classic_editor_plugin_settings', array( self::class, 'classic_editor_settings' ) );
		add_filter( 'wp_redirect', array( self::class, 'restore_redirect' ) );

		// Render groups as the editor saves them. For a theme without a
		// theme.json file (ours is merged from PHP, so the flag can switch it
		// off), WordPress wraps each group's content in an extra
		// .wp-block-group__inner-container for older themes that styled it.
		// This theme never did, and the wrapper breaks the group styles that
		// lay out their children (xifres). Not behind the flag: like the
		// block CSS, how block content renders must not depend on it.
		remove_filter( 'render_block_core/group', 'wp_restore_group_inner_container', 10 );
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
	 * The theme's rich-text formats (frontend/src/js/editor-formats.ts).
	 * Loaded as a module: see $module_handles in functions.php.
	 */
	public static function editor_scripts() {
		if ( ! self::is_active() ) {
			return;
		}

		wp_enqueue_script(
			'sc-js-editor-formats',
			get_template_directory_uri() . '/static/js/editor-formats.js',
			array( 'wp-rich-text', 'wp-block-editor', 'wp-element' ),
			WP_SOFTCATALA_VERSION,
			true
		);

		wp_enqueue_script(
			'sc-js-editor-blocks',
			get_template_directory_uri() . '/static/js/editor-blocks.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-server-side-render', 'wp-data', 'wp-hooks', 'wp-dom-ready', 'wp-autop', 'wp-shortcode', 'wp-editor', 'wp-notices' ),
			WP_SOFTCATALA_VERSION,
			true
		);
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
	 * Registers the Softcatalà pattern category and the patterns in
	 * block-patterns/. Each file is a pattern header followed by plain block
	 * markup (validated by frontend/tests/blocks.test.ts).
	 */
	public static function register_patterns() {
		if ( ! self::is_active() ) {
			return;
		}

		$category = self::config( 'pattern_category' );
		register_block_pattern_category( $category['slug'], array( 'label' => $category['label'] ) );

		foreach ( glob( get_template_directory() . '/' . self::PATTERNS_DIR . '/*.php' ) as $file ) {
			$pattern = self::read_pattern( $file );

			if ( $pattern && ! \WP_Block_Patterns_Registry::get_instance()->is_registered( $pattern['slug'] ) ) {
				register_block_pattern( $pattern['slug'], $pattern['properties'] );
			}
		}
	}

	/**
	 * Reads one pattern file.
	 *
	 * @param string $file Absolute path.
	 * @return array|null array( 'slug' => …, 'properties' => … ), or null without a slug, title or content.
	 */
	public static function read_pattern( $file ) {
		$headers = get_file_data( $file, self::PATTERN_HEADERS );
		$source  = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local theme file.
		$end     = strpos( $source, '?>' );
		$content = trim( false === $end ? $source : substr( $source, $end + 2 ) );

		if ( '' === $headers['slug'] || '' === $headers['title'] || '' === $content ) {
			return null;
		}

		$properties = array(
			'title'   => $headers['title'],
			'content' => $content,
		);

		if ( '' !== $headers['description'] ) {
			$properties['description'] = $headers['description'];
		}

		if ( '' !== $headers['viewportWidth'] ) {
			$properties['viewportWidth'] = (int) $headers['viewportWidth'];
		}

		foreach ( array( 'categories', 'keywords', 'blockTypes', 'postTypes' ) as $list ) {
			if ( '' !== $headers[ $list ] ) {
				$properties[ $list ] = array_map( 'trim', explode( ',', $headers[ $list ] ) );
			}
		}

		return array(
			'slug'       => $headers['slug'],
			'properties' => $properties,
		);
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
	 * With the block_editor_default flag (and block_editor) on, overrides the
	 * Classic Editor plugin's settings: the block editor by default, users may
	 * switch. Posts last edited in the classic editor keep opening in it (the
	 * plugin remembers each post's editor). Off, the plugin's own settings
	 * apply. The plugin hides its settings screen while this decides.
	 *
	 * @param array|false $settings Settings forced so far (false: none).
	 * @return array|false
	 */
	public static function classic_editor_settings( $settings ) {
		if ( ! self::is_active() || ! \SC_Settings::get_instance()->is_active( \SC_Settings::SETTINGS_BLOCK_EDITOR_DEFAULT ) ) {
			return $settings;
		}

		return array(
			'editor'      => 'block',
			'allow-users' => true,
		);
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

	/**
	 * After "Restore this revision", sends the block editor no ?revision=.
	 *
	 * The revisions screen (revision.php) redirects to the edit screen with message=5&revision=<id>,
	 * for the classic editor's "restored" notice. Since WordPress 7.0 the block
	 * editor reads ?revision= as "show this revision", and when the screen has
	 * meta boxes (visual revisions off) it sends you on to revision.php: the
	 * restore works, but you land back on the revisions screen. The classic
	 * editor keeps the parameter for its notice.
	 *
	 * Core bug https://core.trac.wordpress.org/ticket/66708; its patch
	 * (wordpress-develop PR #16080, slated for 7.2) builds the same URL.
	 * Remove this once the minimum WordPress version has it.
	 *
	 * @param string $location Redirect URL.
	 * @return string
	 */
	public static function restore_redirect( $location ) {
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- revision.php checked the nonce.
		if ( 'revision.php' !== ( $GLOBALS['pagenow'] ?? '' ) || 'restore' !== $action ) {
			return $location;
		}

		parse_str( (string) wp_parse_url( $location, PHP_URL_QUERY ), $args );
		$post = isset( $args['post'], $args['revision'] ) ? get_post( (int) $args['post'] ) : null;

		return $post && use_block_editor_for_post( $post ) ? remove_query_arg( 'revision', $location ) : $location;
	}
}
