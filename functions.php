<?php

if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
		} );
	return;
}

Timber::$dirname = array('templates', 'views');

class StarterSite extends TimberSite {

	function __construct() {
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		parent::__construct();
	}

	function register_post_types() {
		//this is where you can register custom post types
	}

	function register_taxonomies() {
		//this is where you can register custom taxonomies
	}

	function add_to_context( $context ) {
		$context['user_info'] = $this->get_user_information();
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;
		return $context;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own fuctions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );
		//$twig->addFilter( 'myfoo', new Twig_Filter_Function( 'myfoo' ) );
		return $twig;
	}

	function get_user_information() {
		$user_info = array();
		$user_id = get_current_user_id();
		$current_user = wp_get_current_user();
		if($user_id) {
			$user_info['is_connected'] = true;
			$user_info['wp_logout_url'] = wp_logout_url( '/' );
			$user_info['avatar']  = get_avatar( $user_id, 19, null, 'fotografia-usuari-sofcatala' );
			$user_info['name'] = $current_user->display_name;
			$user_info['profile_url']  = get_edit_profile_url( $user_id );
		} else {
			$user_info['avatar']  = get_avatar( $user_id, 19, null, 'fotografia-usuari-sofcatala' );
			$user_info['is_connected'] = false;
		}

		return $user_info;
	}

}

new StarterSite();

function myfoo( $text ) {
	$text .= ' bar!';
	return $text;
}

function softcatala_scripts() {
	wp_enqueue_style( 'sc-css-main', get_template_directory_uri() . '/static/css/main.min.css', array(), '1.0' );
	wp_enqueue_script( 'bootstrap', get_template_directory_uri() . '/static/js/bootstrap-toolkit.min.js', array(), '1.0.0', true );
	wp_enqueue_script( 'sc-js-main', get_template_directory_uri() . '/static/js/main.min.js', array(), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'softcatala_scripts' );
