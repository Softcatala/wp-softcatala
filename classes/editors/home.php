<?php

namespace Softcatala\Editors;

/**
 * Class Home
 *
 * Registers the editor settings for Home page
 */
class Home {

	public static function register() {
		if ( is_admin() ) {
			$template = get_post_meta(get_the_ID(), '_wp_page_template', true);

			if($template == 'home-sc.php'){
				remove_post_type_support( 'page', 'editor' );
			}
		}
	}
}