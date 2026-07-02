<?php
/**
 * [cards] / [card title="..." image="..." link="..."] shortcodes for a
 * responsive grid of link cards.
 *
 * Usage:
 *   [cards]
 *     [card title="Títol" image="https://.../foto.jpg" link="https://..."]Descripció del card[/card]
 *     [card title="Altre" link="/pagina-interna/"]Una altra descripció[/card]
 *   [/cards]
 *
 * Renders the "thumbnail thumbnail-vertical thumbnail-premsa" markup from
 * templates/plantilla-distribuidora-01.twig via the reusable
 * templates/components/card-thumbnail.twig partial, so image resizing
 * (resize) and description truncation (truncate_words) keep working the
 * same way. Unlike that hardcoded 4-column loop, the wrapper here uses
 * justify-content: space-evenly so rows with fewer than 4 cards stay balanced.
 */
if ( ! class_exists( 'SC_Shortcodes_Cards' ) ) :
class SC_Shortcodes_Cards {

	private static array $buffer = array();

	public function __construct( $shortcodes_handler = null ) {
		if ( $shortcodes_handler !== null ) {
			$shortcodes_handler->add( 'cards', array( $this, 'shortcode_cards' ), false );
			$shortcodes_handler->add( 'card',  array( $this, 'shortcode_card' ),  false );
		}
	}

	public function shortcode_card( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'title' => '',
			'image' => '',
			'link'  => '',
		), $atts );

		self::$buffer[] = array(
			'title'       => $atts['title'],
			'image'       => $atts['image'],
			'link'        => $atts['link'],
			'description' => trim( do_shortcode( $content ) ),
		);

		return '';
	}

	public function shortcode_cards( $atts, $content = '' ) {
		self::$buffer = array();
		do_shortcode( $content );

		$cards        = self::$buffer;
		self::$buffer = array();

		if ( empty( $cards ) ) {
			return '';
		}

		$context = Timber::context();
		$html    = '<div class="sc-cards">';

		foreach ( $cards as $card ) {
			$context['card'] = $card;
			$html            .= '<div class="col-xxs-12 col-xs-6 col-sm-4 col-md-3">';
			$html            .= Timber::compile( 'components/card-thumbnail.twig', $context );
			$html            .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}
}
endif;
