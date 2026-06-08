<?php
/**
 * Representa les llistes de dues columnes amb enllaços.
 * Originalment a https://github.com/Softcatala/sc-shortcodes (absorbit al tema).
 */
if ( ! class_exists( 'SC_Shortcodes_LinkList' ) ) :
class SC_Shortcodes_LinkList {

	public function __construct( $shortcodes_handler = null ) {
		if ( $shortcodes_handler != null ) {
			$shortcodes_handler->add( 'llista-links', array( $this, 'shortcode' ), true );
		}
	}

	public function shortcode( $atts, $content ) {
		$raw_items     = preg_split( '/\R/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$items         = array_values( array_filter( $raw_items, fn( $item ) => trim( $item ) !== '' ) );
		$columns_count = ceil( count( $items ) / 2 );

		$html = '<div class="row"><ul class="llista-check col-sm-6">';

		foreach ( $items as $key => $item ) {
			if ( $key == $columns_count ) {
				$html .= '</ul><ul class="llista-check col-sm-6">';
			}

			$values = explode( '|', $item );

			if ( $this->validate( $values ) ) {
				$html .= '<li><a href="' . $values[1] . '"><i class="fas fa-check"></i><span>' . $values[0] . '</span></a></li>';
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$html .= '<li><div class="bg-danger">L\'element de la llista no conté 2 parts: <pre>' . esc_html( $item ) . '</pre></div></li>';
			}
		}

		$html .= '</ul></div>';

		return $html;
	}

	private function validate( $elements ) {
		return is_array( $elements ) && count( $elements ) == 2;
	}
}
endif;
