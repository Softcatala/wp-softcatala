<?php
/**
 * The theme's own blocks, one folder each under blocks/ with its block.json.
 *
 * Native, server-rendered blocks: each renders with the same code as the
 * shortcode it replaces, so a page shows the same thing either way. Their
 * editor side (a preview and the settings fields) is
 * frontend/src/js/editor-blocks.ts, enqueued with the block_editor flag by
 * Editor::editor_scripts().
 *
 * sc/targetes and sc/llista-icones are ACF PRO blocks (their items are a
 * repeater field): their block.json carries an "acf" key, and they are only
 * registered when ACF PRO is.
 *
 * Registered whatever the flag says, like the block CSS: content that has
 * these blocks must render.
 *
 * @package Softcatala
 */

namespace Softcatala\Blocks;

use Softcatala\Content\TableOfContents;

/**
 * Class ThemeBlocks
 */
class ThemeBlocks {

	const DIR = 'blocks';

	/**
	 * ACF PRO blocks (their content is a form), by folder. Registered only
	 * with ACF PRO active; their block.json carries the render callback.
	 */
	const ACF_BLOCKS = array( 'targetes', 'llista-icones' );

	/**
	 * Hooks the registration.
	 */
	public static function init() {
		add_action( 'init', array( self::class, 'register' ) );
	}

	/**
	 * Render callbacks of the native blocks, by folder.
	 *
	 * @return array<string, callable>
	 */
	public static function renderers() {
		return array(
			'index'            => array( self::class, 'render_index' ),
			'ia-data'          => array( self::class, 'render_ia_data' ),
			'ia-local-checker' => array( \SC_Ia_Local_Checker::class, 'render' ),
			'navega-en-catala' => array( \SC_NavegaEnCatala::class, 'render' ),
		);
	}

	/**
	 * Registers every block under blocks/.
	 */
	public static function register() {
		$dir = get_template_directory() . '/' . self::DIR;

		foreach ( self::renderers() as $folder => $render ) {
			if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'sc/' . $folder ) ) {
				register_block_type( "$dir/$folder", array( 'render_callback' => $render ) );
			}
		}

		if ( function_exists( 'acf_register_block_type' ) ) {
			foreach ( self::ACF_BLOCKS as $folder ) {
				if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'sc/' . $folder ) ) {
					register_block_type( "$dir/$folder" );
				}
			}
		}
	}

	/**
	 * Links to the current post's anchored second-level headings.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Nothing when the post has no anchored h2.
	 */
	public static function render_index( $attributes ) {
		$post = get_post();
		$toc  = $post ? TableOfContents::from_content( $post->post_content ) : array();

		if ( empty( $toc ) ) {
			return '';
		}

		$title = isset( $attributes['titol'] ) ? trim( (string) $attributes['titol'] ) : '';
		$items = '';
		foreach ( $toc as $entry ) {
			// esc_html() keeps the entities already in the post's HTML.
			$items .= '<li><a href="#' . esc_attr( $entry['id'] ) . '">' . esc_html( $entry['title'] ) . '</a></li>';
		}

		$wrapper = get_block_wrapper_attributes( array( 'aria-label' => '' !== $title ? $title : 'Índex' ) );

		return '<nav ' . $wrapper . '>'
			. ( '' !== $title ? '<p class="sc-index-titol">' . esc_html( $title ) . '</p>' : '' )
			. '<ul class="sc-index-llista">' . $items . '</ul></nav>';
	}

	/**
	 * The [ia-data] shortcode's output, from the block's attributes.
	 *
	 * @param array $attributes Block attributes (the shortcode's).
	 * @return string
	 */
	public static function render_ia_data( $attributes ) {
		return ( new \SC_Shortcodes_IaData() )->shortcode( $attributes );
	}
}
