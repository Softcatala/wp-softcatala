<?php
/**
 * Classe per a registrar els shortcodes del tema.
 * Originalment a https://github.com/Softcatala/sc-shortcodes (absorbit al tema).
 */
if ( ! class_exists( 'SC_Shortcodes' ) ) :
class SC_Shortcodes {

	/**
	 * @var array Llista de shortcodes registrats.
	 */
	private $shortcodes = [];

	/**
	 * Registra un shortcode.
	 *
	 * @param string $shortcode Nom del shortcode.
	 * @param array  $function Callback a executar per generar l'HTML.
	 * @param bool   $texturize Ignored. It was meant to exempt the shortcode's
	 *                          content from wptexturize, but stored the name's
	 *                          first letter, so it never did; honouring it now
	 *                          would turn the curly quotes on existing pages
	 *                          (ia-data, cards) straight.
	 */
	public function add( $shortcode, $function, $texturize ) {
		unset( $texturize );
		$this->shortcodes[ $shortcode ] = $function;
	}

	/**
	 * Names of the shortcodes registered here.
	 *
	 * @return string[]
	 */
	public function names() {
		return array_keys( $this->shortcodes );
	}

	/**
	 * Registers the shortcodes with WordPress, once, so they render in its own
	 * do_shortcode pass on the_content.
	 *
	 * They used to render in a separate pass and be registered afterwards as
	 * stand-ins (for strip_shortcodes). Two passes over the same content undo
	 * WordPress's [[tag]] escape: the first turned [[cards]] into [cards] and
	 * the second ran it. Registered up front, they are also known to
	 * wptexturize, which then leaves their attributes alone.
	 */
	public function setup() {
		foreach ( $this->shortcodes as $shortcode => $func ) {
			add_shortcode( $shortcode, $func );
		}
	}
}
endif;
