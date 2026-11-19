<?php
/**
 * The sc/llista-icones block (blocks/llista-icones/block.json): the
 * [llista-icones] shortcode as an ACF PRO block. Each item is a row of a
 * repeater (icon, title, text) instead of an "icon|title|text" line, and it
 * renders with the shortcode's own SC_Shortcodes_IconList::render().
 *
 * @package Softcatala
 */

namespace Softcatala\Blocks;

/**
 * Class IconList
 */
class IconList {

	const COLORS = array(
		'blanc'     => 'Blanc, en dues columnes',
		'gris'      => 'Gris, en dues columnes',
		'blancgris' => 'Blanc amb icona destacada, en una columna',
	);

	/**
	 * Hooks the field group.
	 */
	public static function init() {
		add_action( 'acf/init', array( self::class, 'register_field_group' ) );
	}

	/**
	 * The block's fields.
	 */
	public static function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_sc_llista_icones',
				'title'    => 'Llista amb icones',
				'fields'   => array(
					array(
						'key'           => 'field_sc_llista_icones_color',
						'label'         => 'Aspecte',
						'name'          => 'color',
						'type'          => 'select',
						'choices'       => self::COLORS,
						'default_value' => 'blanc',
					),
					array(
						'key'          => 'field_sc_llista_icones_items',
						'label'        => 'Elements',
						'name'         => 'items',
						'type'         => 'repeater',
						'button_label' => 'Afegeix un element',
						'layout'       => 'block',
						'sub_fields'   => array(
							array(
								'key'          => 'field_sc_llista_icones_icon',
								'label'        => 'Icona',
								'name'         => 'icon',
								'type'         => 'text',
								'instructions' => 'El nom d\'una icona de Font Awesome (fontawesome.com/icons), per exemple «users» o «language». Buit, un cercle.',
							),
							array(
								'key'   => 'field_sc_llista_icones_title',
								'label' => 'Títol',
								'name'  => 'title',
								'type'  => 'text',
							),
							array(
								'key'   => 'field_sc_llista_icones_text',
								'label' => 'Text',
								'name'  => 'text',
								'type'  => 'textarea',
								'rows'  => 3,
							),
						),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'sc/llista-icones',
						),
					),
				),
			)
		);
	}

	/**
	 * Render callback (ACF).
	 *
	 * @param array  $block      ACF block settings.
	 * @param string $content    Inner content (unused).
	 * @param bool   $is_preview Whether it renders in the editor.
	 * @param int    $post_id    Post being rendered (unused).
	 */
	public static function render( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		$rows  = get_field( 'items' );
		$rows  = is_array( $rows ) ? $rows : array();
		$items = array();
		// The same inline tags the shortcode keeps; the icon name ends up in
		// a class attribute.
		$inline = array(
			'a'      => array( 'href' => array() ),
			'b'      => array(),
			'strong' => array(),
			'em'     => array(),
		);
		foreach ( $rows as $row ) {
			$items[] = array(
				'icon'  => preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) ( $row['icon'] ?? '' ) ) ),
				'title' => wp_kses( (string) ( $row['title'] ?? '' ), $inline ),
				'text'  => wp_kses( (string) ( $row['text'] ?? '' ), $inline ),
			);
		}

		if ( empty( $items ) && $is_preview ) {
			echo '<p>Afegiu elements a la llista a la barra lateral.</p>';
			return;
		}

		$color = get_field( 'color' );
		echo \SC_Shortcodes_IconList::render( $items, $color ? $color : 'blanc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the shortcode's renderer.
	}
}
