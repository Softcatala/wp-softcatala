<?php
/**
 * Classe per a autoregistrar els shortcodes i avaluars abans de wptexturize.
 * Originalment a https://github.com/Softcatala/sc-shortcodes (absorbit al tema).
 */
if ( ! class_exists( 'SC_Shortcodes' ) ) :
class SC_Shortcodes {

	/**
	 * @var array Llista de shortcodes registrats.
	 */
	private $shortcodes = [];

	/**
	 * @var array Llista de shortcodes exempts de wptexturize.
	 */
	private $exempt = [];

	/**
	 * Registra un shortcode.
	 *
	 * @param string $shortcode Nom del shortcode.
	 * @param array  $function Callback a executar per generar l'HTML.
	 * @param bool   $texturize Defineix si s'ha de filtrar l'HTML generat per wptexturize.
	 */
	public function add( $shortcode, $function, $texturize ) {
		$this->shortcodes[ $shortcode ] = $function;

		if ( ! $texturize ) {
			$this->exempt[] = $shortcode[0];
		}
	}

	/**
	 * Configura els shortcodes
	 */
	public function setup() {
		add_filter( 'the_content', array( $this, 'pre_process_shortcode' ), 11 );
		add_filter( 'the_content', array( $this, 'add_dummy_shortcode' ), 12 );
		add_filter( 'no_texturize_shortcodes', array( $this, 'exempt_from_wptexturize' ) );
	}

	/**
	 * Crea un shortcode fictici per a que WordPress l'elimine.
	 *
	 * @param string $content Contingut del shortcode.
	 * @return string
	 */
	public function add_dummy_shortcode( $content = '' ) {
		foreach ( $this->shortcodes as $shortcode => $func ) {
			add_shortcode( $shortcode, array(
				$this,
				function( $atts, $content ) {
					return $content;
				},
			));
		}
		return $content;
	}

	/**
	 * Pre-processa el contingut del shortcode registrat.
	 *
	 * @global array $shortcode_tags Shortcodes registrats normalment a WordPress.
	 * @param string $content Contingut del shortcode.
	 * @return string
	 */
	public function pre_process_shortcode( $content ) {
		global $shortcode_tags;

		$orig_shortcode_tags = $shortcode_tags;
		$shortcode_tags = [];

		foreach ( $this->shortcodes as $shortcode => $func ) {
			add_shortcode( $shortcode, $func );
		}

		$content = do_shortcode( $content );

		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}

	/**
	 * Genera la llista de shortcodes a excloure de wptexturize.
	 *
	 * @param array $shortcodes llista de shortcodes a excloure.
	 * @return array
	 */
	public function exempt_from_wptexturize( $shortcodes ) {
		foreach ( $this->exempt as $shortcode ) {
			$shortcodes[] = $shortcode;
		}
		return $shortcodes;
	}
}
endif;
