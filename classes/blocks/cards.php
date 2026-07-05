<?php
/**
 * "Targetes" ACF block — native block-editor equivalent of the [cards]
 * shortcode (classes/shortcodes/class-cards.php). Lets editors build the
 * same title/image/link/description card grid through a repeater field
 * instead of bracket syntax. Shares its rendering with the shortcode via
 * SC_Shortcodes_Cards::render() so both produce identical markup.
 */

namespace Softcatala\Blocks;

class Cards {

	public function __construct() {
		add_action( 'acf/init', array( $this, 'register_field_group' ) );
		add_action( 'acf/init', array( $this, 'register_block' ) );
	}

	public function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group( array(
			'key'      => 'group_softcatala_cards_block',
			'title'    => 'Targetes',
			'fields'   => array(
				array(
					'key'           => 'field_softcatala_cards_block_cards',
					'label'         => 'Targetes',
					'name'          => 'cards',
					'type'          => 'repeater',
					'button_label'  => 'Afegeix una targeta',
					'layout'        => 'block',
					'sub_fields'    => array(
						array(
							'key'   => 'field_softcatala_cards_block_title',
							'label' => 'Títol',
							'name'  => 'title',
							'type'  => 'text',
						),
						array(
							'key'           => 'field_softcatala_cards_block_image',
							'label'         => 'Imatge',
							'name'          => 'image',
							'type'          => 'image',
							'return_format' => 'url',
							'preview_size'  => 'medium',
						),
						array(
							'key'   => 'field_softcatala_cards_block_link',
							'label' => 'Enllaç',
							'name'  => 'link',
							'type'  => 'url',
						),
						array(
							'key'   => 'field_softcatala_cards_block_description',
							'label' => 'Descripció',
							'name'  => 'description',
							'type'  => 'textarea',
						),
					),
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/cards',
					),
				),
			),
		) );
	}

	public function register_block() {
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			return;
		}

		acf_register_block_type( array(
			'name'            => 'cards',
			'title'           => 'Targetes',
			'description'     => 'Graella de targetes amb imatge, títol, descripció i enllaç.',
			'category'        => 'softcatala',
			'icon'            => 'grid-view',
			'keywords'        => array( 'targetes', 'cards', 'grid' ),
			'render_callback' => array( $this, 'render' ),
			'enqueue_style'   => get_template_directory_uri() . '/static/css/main.min.css',
		) );
	}

	public function render( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		$rows  = get_field( 'cards' ) ?: array();
		$cards = array_map( function( $row ) {
			return array(
				'title'       => $row['title'] ?? '',
				'image'       => $row['image'] ?? '',
				'link'        => $row['link'] ?? '',
				'description' => $row['description'] ?? '',
			);
		}, $rows );

		echo \SC_Shortcodes_Cards::render( $cards );
	}
}
