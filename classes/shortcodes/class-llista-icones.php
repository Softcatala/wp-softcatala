<?php
/**
 * Representa les llistes de dues columnes amb icones.
 * Originalment a https://github.com/Softcatala/sc-shortcodes (absorbit al tema).
 */
if ( ! class_exists( 'SC_Shortcodes_IconList' ) ) :
class SC_Shortcodes_IconList {

	public function __construct( $shortcodes_handler = null ) {
		if ( $shortcodes_handler != null ) {
			$shortcodes_handler->add( 'llista-icones', array( $this, 'shortcode' ), true );
		}
	}

	public function shortcode( $atts, $content ) {
		$atributs = shortcode_atts( array(
			'color' => 'blanc',
		), $atts );

		if ( empty( $content ) ) {
			return $content;
		}

		$content = strip_tags( $content, '<a><b><strong><em>' );
		$lines   = preg_split( '/\R/', $content, -1, PREG_SPLIT_NO_EMPTY );

		$items = array();
		foreach ( $lines as $line ) {
			$parts   = explode( '|', $line );
			$items[] = count( $parts ) === 3
				? array( 'icon' => $parts[0], 'title' => $parts[1], 'text' => $parts[2] )
				: array( 'invalid' => $line );
		}

		return self::render( $items, $atributs['color'] );
	}

	/**
	 * The icon list's markup, shared by the [llista-icones] shortcode and the
	 * sc/llista-icones block (Softcatala\Blocks\IconList).
	 *
	 * @param array  $items Each with icon (a Font Awesome 4 or 7 name), title
	 *                      and text; or with invalid (the raw line), shown as
	 *                      an error.
	 * @param string $color blanc, gris or blancgris.
	 * @return string
	 */
	public static function render( array $items, $color = 'blanc' ) {
		if ( empty( $items ) ) {
			return '';
		}

		$itemCss   = self::get_item_css_class( $color );
		$columnCss = self::get_column_css_class( $color );

		$column0 = '';
		$column1 = '';
		foreach ( array_values( $items ) as $index => $item ) {
			$item_html = self::get_icon_list_item( $item, $itemCss );
			( $index % 2 == 0 ) ? $column0 .= $item_html : $column1 .= $item_html;
		}

		// --files: the rows of the longer (left) column, so the stylesheet can
		// line the two columns' items up row by row (_cards.scss, Icon list).
		$rows  = (int) ceil( count( $items ) / 2 );
		$html  = '<div class="row llista-icones llista-icones-' . self::get_color( $color ) . '" style="--files:' . $rows . '">';
		$html .= '<div class="' . $columnCss . ' col-sm-6">' . $column0 . '</div>';
		$html .= '<div class="' . $columnCss . ' col-sm-6">' . $column1 . '</div>';
		$html .= '</div>';

		return $html;
	}

	private static function get_color( $color ) {
		return in_array( $color, array( 'blanc', 'gris', 'blancgris' ), true ) ? $color : 'blanc';
	}

	private static function get_column_css_class( $color, $default = '' ) {
		switch ( $color ) {
			case 'blancgris':
				return 'col-xs-12';
			default:
				return $default;
		}
	}

	private static function get_item_css_class( $color, $default = 'thumbnail-blanc' ) {
		switch ( $color ) {
			case 'gris':
				return 'thumbnail-gris';
			case 'blanc':
				return 'thumbnail-blanc';
			case 'blancgris':
				return 'thumbnail-blanc thumbnail-invers';
			default:
				return $default;
		}
	}

	private static function get_icon_list_item( $item, $css ) {
		if ( ! isset( $item['invalid'] ) ) {
			$fa4_name   = trim( self::get_icon( $item['icon'] ?? '' ) );
			$icon_class = self::fa4_to_fa7( $fa4_name );
			$heading    = self::get_heading( $item['title'] ?? '' );
			$body       = self::get_body( $item['text'] ?? '' );

			$html  = '<div class="thumbnail ' . $css . '">';
			$html .= '<i class="' . $icon_class . '"></i>';
			$html .= '<div class="caption">';

			if ( ! empty( $heading ) ) {
				$html .= '<h3>' . $heading . '</h3>';
			}
			if ( ! empty( $body ) ) {
				$html .= '<p>' . $body . '</p>';
			}

			$html .= '</div>';
			$html .= '</div>';
		} else {
			$html  = '<div class="bg-danger">';
			$html .= 'L\'element de la llista no conté 3 parts';
			$html .= '<pre>' . $item['invalid'] . '</pre>';
			$html .= '</div>';
		}

		return $html;
	}

	private static function fa4_to_fa7( $fa4_name ) {
		$map = array(
			// Icons with name changes
			'calendar'       => 'fas fa-calendar-days',
			'globe'          => 'fas fa-globe',
			'group'          => 'fas fa-users',
			'mouse'          => 'fas fa-computer-mouse',
			'sort-alpha-asc' => 'fas fa-arrow-down-a-z',
			'tablet'         => 'fas fa-tablet-screen-button',
			'world'          => 'fas fa-globe',
			// Icons with name changes
			'question-circle' => 'fas fa-circle-question',
			// Icons moved to far (outline variants)
			'bookmark-o'     => 'far fa-bookmark',
			'commenting-o'   => 'far fa-comment-dots',
			'envelope-o'     => 'far fa-envelope',
			'file-o'         => 'far fa-file',
			'keyboard-o'     => 'far fa-keyboard',
			'pencil-square-o' => 'far fa-pen-to-square',
			'picture-o'      => 'far fa-image',
		);

		if ( isset( $map[ $fa4_name ] ) ) {
			return $map[ $fa4_name ];
		}

		// Default: same name, solid prefix
		return 'fas fa-' . $fa4_name;
	}

	private static function get_icon( $icon ) {
		return ( isset( $icon ) && ! empty( $icon ) ) ? $icon : 'circle';
	}

	private static function get_heading( $heading ) {
		return ( isset( $heading ) && ! empty( $heading ) ) ? $heading : '';
	}

	private static function get_body( $body ) {
		return ( isset( $body ) && ! empty( $body ) ) ? $body : '';
	}
}
endif;
