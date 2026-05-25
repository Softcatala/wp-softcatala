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

		$itemCss   = $this->get_item_css_class( $atributs['color'] );
		$columnCss = $this->get_column_css_class( $atributs['color'] );

		$content = strip_tags( $content, '<a><b><strong><em>' );
		$items   = preg_split( '/\R/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$columns = $this->get_icon_list_columns( $items, $itemCss );

		$html  = '<div class="row">';
		$html .= '<div class="' . $columnCss . ' col-sm-6">' . $columns[0] . '</div>';
		$html .= '<div class="' . $columnCss . ' col-sm-6">' . $columns[1] . '</div>';
		$html .= '</div>';

		return $html;
	}

	private function get_column_css_class( $color, $default = '' ) {
		switch ( $color ) {
			case 'blancgris':
				return 'col-xs-12';
			default:
				return $default;
		}
	}

	private function get_item_css_class( $color, $default = 'thumbnail-blanc' ) {
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

	private function get_icon_list_columns( $items, $css ) {
		$column0     = '';
		$column1     = '';
		$total_items = 0;

		foreach ( $items as $item ) {
			$item_html = $this->get_icon_list_item( $item, $css );
			( $total_items % 2 == 0 ) ? $column0 .= $item_html : $column1 .= $item_html;
			$total_items++;
		}

		return array( $column0, $column1 );
	}

	private function get_icon_list_item( $item, $css ) {
		$parts = explode( '|', $item );

		if ( $this->validate( $parts ) ) {
			$fa4_name  = trim( $this->get_icon( $parts[0] ) );
			$icon_class = $this->fa4_to_fa7( $fa4_name );
			$heading   = $this->get_heading( $parts[1] );
			$body      = $this->get_body( $parts[2] );

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
			$html .= '<pre>' . $item . '</pre>';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Converteix un nom d'icona FA4 a la classe FA7 correcta.
	 * Inclou prefix (fas/far/fab) i el nou nom quan ha canviat.
	 *
	 * @param string $fa4_name Nom de la icona sense prefix (ex: 'keyboard-o').
	 * @return string Classe FA7 completa (ex: 'far fa-keyboard').
	 */
	private function fa4_to_fa7( $fa4_name ) {
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

	private function validate( $elements ) {
		return is_array( $elements ) && count( $elements ) == 3;
	}

	private function get_icon( $icon ) {
		return ( isset( $icon ) && ! empty( $icon ) ) ? $icon : 'circle';
	}

	private function get_heading( $heading ) {
		return ( isset( $heading ) && ! empty( $heading ) ) ? $heading : '';
	}

	private function get_body( $body ) {
		return ( isset( $body ) && ! empty( $body ) ) ? $body : '';
	}
}
endif;
