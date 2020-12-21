<?php

namespace Softcatala\Editors;

/**
 * Class Home
 *
 * Registers the editor settings for Home page
 */
class Home {

	public static function register() {
		global $post;
		if ( is_admin() ) {
			$template = get_post_meta($post->ID, '_wp_page_template', true);

			if($template == 'home-sc.php'){
				remove_post_type_support( 'page', 'editor' );
			}
		}
	}
}