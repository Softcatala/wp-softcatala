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
		$languageHeader = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

		echo <<<NAVEGA
		<div class="navega"><div class="navega-user-agent">$languageHeader</div></div>
		<script>
		 	document.addEventListener("DOMContentLoaded", function(event) {
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
			}
		</script>
NAVEGA;
	}
}