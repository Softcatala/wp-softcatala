<?php
/**
 * @package Softcatala
 */

/**
 * Shortcodes for Navega en català
 */
class SC_NavegaEnCatala {

	public static function init() {
		new SC_NavegaEnCatala();
	}

	public function __construct() {
		add_shortcode( 'navega-en-catala', array( $this, 'shortcode' ) );
	}

	public function shortcode() {
		return self::render();
	}

	/**
	 * The widget's markup, shared by the shortcode and the sc/navega-en-catala
	 * block (Softcatala\Blocks\ThemeBlocks).
	 *
	 * @return string
	 */
	public static function render() {
		$out = <<<NAVEGA
		<div class="navega"><div id="navega-user-agent" class="navega-user-agent"></div></div>
		<script>
		 	document.addEventListener("DOMContentLoaded", function(event) {
				jQuery.get('https://api.softcatala.org/debug-requests', {}, function(d) {
					jQuery('#navega-user-agent').html(d.headers["accept-language"]);
					if(window.navigator.language) {
					    if(window.navigator.language.substr(0, 2) == 'ca') {
							noCatala = document.getElementById('navega-no-catala');
					        if(noCatala) {
					            noCatala.classList.add('hidden');
					        }
					    } else {
					        siCatala = document.getElementById('navega-si-catala');
					        if (siCatala) {
					            siCatala.classList.add('hidden');
					        }
					    }
					}
		 	    });
			});
		</script>
NAVEGA;

		return $out;
	}
}