<?php
/**
 * [tabs] / [tab title="..."] shortcodes for tabbed content sections.
 *
 * Usage:
 *   [tabs]
 *     [tab title="First tab"]Content here[/tab]
 *     [tab title="Second tab"]More content[/tab]
 *   [/tabs]
 */
if ( ! class_exists( 'SC_Shortcodes_Tabs' ) ) :
class SC_Shortcodes_Tabs {

	private static int   $instance_count = 0;
	private static array $buffer         = array();

	public function __construct( $shortcodes_handler = null ) {
		if ( $shortcodes_handler !== null ) {
			$shortcodes_handler->add( 'tabs', array( $this, 'shortcode_tabs' ), false );
			$shortcodes_handler->add( 'tab',  array( $this, 'shortcode_tab' ),  false );
		}
	}

	public function shortcode_tab( $atts, $content = '' ) {
		$atts = shortcode_atts( array( 'title' => '' ), $atts );

		self::$buffer[] = array(
			'title'   => $atts['title'],
			'content' => do_shortcode( $content ),
		);

		return '';
	}

	public function shortcode_tabs( $atts, $content = '' ) {
		self::$buffer = array();
		do_shortcode( $content );

		$tabs         = self::$buffer;
		self::$buffer = array();

		if ( empty( $tabs ) ) {
			return '';
		}

		$uid = ++self::$instance_count;

		$nav = '<ul class="nav nav-tabs">';
		foreach ( $tabs as $i => $tab ) {
			$id     = 'sc-tab-' . $uid . '-' . $i;
			$active = 0 === $i ? ' class="active"' : '';
			$nav   .= '<li' . $active . '>';
			$nav   .= '<a data-toggle="tab" href="#' . esc_attr( $id ) . '">' . esc_html( $tab['title'] ) . '</a>';
			$nav   .= '</li>';
		}
		$nav .= '</ul>';

		$panels = '<div class="tab-content">';
		foreach ( $tabs as $i => $tab ) {
			$id      = 'sc-tab-' . $uid . '-' . $i;
			$active  = 0 === $i ? ' active' : '';
			$panels .= '<div id="' . esc_attr( $id ) . '" class="tab-pane' . $active . '">';
			$panels .= $tab['content'];
			$panels .= '</div>';
		}
		$panels .= '</div>';

		return $nav . $panels;
	}
}
endif;
