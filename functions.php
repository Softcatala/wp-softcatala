<?php

if ( ! class_exists( 'Timber' ) && is_admin() ) {
	add_action( 'admin_notices', function() {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
		} );
	return;
} else if ( ! class_exists( 'Timber' ) && ! is_admin() ) {
	header('HTTP/1.1 500 Internal Server Error');
    echo 'Aquest és un error 500, esperant que l\'Anna dissenye alguna cosa millor';
    die();
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
		$context['site'] = $this;
		$context['themepath'] = get_template_directory_uri();
		$context['current_url'] = get_current_url();
		return $context;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own fuctions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );
		$twig->addFilter('get_caption_from_media_url', new Twig_Filter_Function('get_caption_from_media_url'));
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
			$user_info['avatar_48']  = get_avatar( $user_id, 48, null, 'fotografia-usuari-sofcatala' );
			$user_info['name'] = $current_user->display_name;
			$user_info['profile_url']  = get_edit_profile_url( $user_id );
		} else {
			$user_info['avatar']  = get_avatar( $user_id, 19, null, 'fotografia-usuari-sofcatala' );
			$user_info['is_connected'] = false;
			$user_info['wp_login_url'] = wp_login_url(get_current_url());
		}

		return $user_info;
	}

}

new StarterSite();

function softcatala_scripts() {
	wp_enqueue_style( 'sc-css-main', get_template_directory_uri() . '/static/css/main.min.css', array(), '1.0' );
	wp_enqueue_script( 'sc-js-main', get_template_directory_uri() . '/static/js/main.min.js', array(), '1.0.0', true );
	wp_enqueue_script( 'sc-js-ads', get_template_directory_uri() . '/static/js/ads.js', '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'softcatala_scripts' );

/**
 * This function retrieves the media caption from
 * a given url. It is used because the «secondary image»
 * created from Types doesn't return the media caption
 * Author: https://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
 *
 * @param string $url
 * @return string $caption
*/
function get_caption_from_media_url( $attachment_url = '' ) {
 
	global $wpdb;
	$attachment_id = false;
 
	// If there is no url, return.
	if ( '' == $attachment_url )
		return;
 
	// Get the upload directory paths
	$upload_dir_paths = wp_upload_dir();
 
	// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
	if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {
 
		// If this is the URL of an auto-generated thumbnail, get the URL of the original image
		$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
 
		// Remove the upload path base directory from the attachment URL
		$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );
 
		// Finally, run a custom database query to get the attachment ID from the modified attachment URL
		$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );
 
	}

	//Not in the original function from the author
	$attachment_meta = get_post_field('post_excerpt', $attachment_id);
 
	return $attachment_meta;
}

/**
 * This function retrieves the current url, either on http or https format
 * depending on the current navigation
 *
 * @return string $url
*/
function get_current_url()
{
	$current_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	return $current_url;
}

/**
 * Function to get the category ID given a category slug
 *
 * @param $slug
 * @return $int
*/
function get_category_id( $slug ) {
	$category = get_category_by_slug($slug);
	$category_id = $category->term_id; 
	return $category_id;
}

function include_theme_conf()
{
    locate_template( array( 'inc/widgets.php' ), true, true );
}
add_action( 'after_setup_theme', 'include_theme_conf' );

function retrieve_page_data($page_slug = '')
{
	//Actions to be taken depending on the post type
	switch ($page_slug) {
		case 'esdeveniment':
			///Get the related «page» to this post type (it will contain the links, downloads, actions...)
			$args = array(
				'name' => 'esdeveniment-page',
				'post_type' => 'page'
			);
			$esdeveniments = get_posts($args);
			$post = Timber::query_post($esdeveniments[0]->ID);
			break;
		default:
			break;
	}

	return $post;
}


/*
 * Functions related to esdeveniments
 */

function get_the_event_filters()
{
    $filtres = array(
        array(
            'link' => 'setmana',
            'title' => 'Aquesta setmana'
        ),
        array(
            'link' => 'setmanavinent',
            'title' => 'La setmana vinent',
        ),
        array(
            'link' => '1mes',
            'title' => 'Aquest mes'
        )
    );
    return $filtres;
}

/*
 * Returns the arguments to apply to the mysql query
 */
function get_post_query_args( $filtre = '', $filtredate = '', $search = '' )
{
    //Retrieve posts
    global $paged;
    if (!isset($paged) || !$paged){
        $paged = 1;
    }

    if( $search != '' ) {
        $args = array(
            'meta_key'   =>  'wpcf-data_inici',
            'post_type' => 'esdeveniment',
            'post_status'    => 'publish',
            's'         => $search,
            'orderby'        => 'wpcf-data_inici',
            'order'          => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => 'wpcf-data_fi',
                    'value' => time(),
                    'compare' => '>=',
                    'type'      => 'NUMERIC'
                )
            ),
            'paged' => $paged,
            'posts_per_page' => 10
        );

    } else if( $filtre == '' ) {
        $args = array(
            'meta_key'   =>  'wpcf-data_inici',
            'post_type' => 'esdeveniment',
            'post_status'    => 'publish',
            'orderby'        => 'wpcf-data_inici',
            'order'          => 'ASC',
            'meta_query' => array(
                array(
                    'key'     => 'wpcf-data_fi',
                    'value' => time(),
                    'compare' => '>=',
                    'type'      => 'NUMERIC'
                )
            ),
            'paged' => $paged,
            'posts_per_page' => 10
        );
    } else if( $filtre != 'setmanavinent' ) {
        $args = array(
            'meta_key'   =>  'wpcf-data_inici',
            'post_type' => 'esdeveniment',
            'post_status'    => 'publish',
            'orderby'        => 'wpcf-data_inici',
            'order'          => 'ASC',
            'meta_query' => array(
                array(
                    'key'     => 'wpcf-data_fi',
                    'value' => array( $filtredate['start_time'], $filtredate['final_time'] ),
                    'compare' => 'BETWEEN',
                    'type'      => 'NUMERIC'
                )
            ),
            'paged' => $paged,
            'posts_per_page' => 10
        );
    } else if ( $filtre == 'setmanavinent' ) {
        $args = array(
            'meta_key'   =>  'wpcf-data_inici',
            'post_type' => 'esdeveniment',
            'post_status'    => 'publish',
            'orderby'        => 'wpcf-data_inici',
            'order'          => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => 'wpcf-data_fi',
                    'value' => $filtredate['start_time'],
                    'compare' => '>=',
                    'type'      => 'NUMERIC'
                ),
                array(
                    'key'     => 'wpcf-data_inici',
                    'value' => $filtredate['final_time'],
                    'compare' => '<=',
                    'type'      => 'NUMERIC'
                )
            ),
            'paged' => $paged,
            'posts_per_page' => 10
        );
    }

    return $args;
}

/*
 * Function to handle the date filter for events
 */
function add_query_vars_filter( $vars ){
    $vars[] = "filtre";
    return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );