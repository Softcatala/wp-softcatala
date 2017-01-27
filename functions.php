<?php

define('WP_SOFTCATALA_VERSION', '0.9.15');

if ( ! class_exists( 'Timber' ) && is_admin() ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
    } );
    return;
} else if ( ! class_exists( 'Timber' ) && ! is_admin() ) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Aquest és un error 500. Alguna cosa no funciona bé al servidor.';
    die();
}

include('inc/perfils.php');

Timber::$dirname = array('templates', 'views');

global $sc_types;

$sc_types = array();

class StarterSite extends TimberSite {

    function __construct() {
        add_theme_support( 'post-formats' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );
        add_theme_support( 'title-tag' );
        add_filter( 'timber_context', array( $this, 'add_user_nav_info_to_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
        add_filter( 'xv_planeta_feed', '__return_true' );
        add_filter( 'wpseo_twitter_creator_account', function($twitter) { return '@softcatala'; } );
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'sc_rewrite_search' ) );
        add_action( 'template_redirect', array( $this, 'sc_change_programs_search_url_rewrite' ) );
        add_action( 'init', array( $this, 'sc_author_rewrite_base' ) );
        add_action( 'template_redirect', array( $this, 'fix_woosidebar_hooks'), 1);
        add_action( 'template_redirect', array( $this, 'sc_change_search_url_rewrite' ) );
        add_action( 'after_setup_theme', array( $this, 'include_theme_conf' ) );
        //SC Dashboard settings
        add_action( 'admin_menu', array( $this, 'include_sc_settings' ));
        add_action( 'admin_init', array( $this, 'add_caps' ));

        spl_autoload_register( array( $this, 'autoload' ) );

        add_post_type_support( 'programa', 'woosidebars' );

        parent::__construct();
    }

    function autoload($cls) {
        $path =  __DIR__ . '/classes/' . strtolower(str_replace('SC_', '', $cls)) . '.php';

        is_readable($path) && require_once($path);
    }

    function include_theme_conf() {
        locate_template( array( 'inc/widgets.php' ), true, true );
        locate_template( array( 'inc/post_types_functions.php' ), true, true );
        locate_template( array( 'inc/ajax_operations.php' ), true, true );
        locate_template( array( 'inc/rewrites.php' ), true, true );
    }

	function get_ui_settings() {
		return array('log_corrector_events');
	}

	function register_ui_settings() {
		$ui_settings = $this->get_ui_settings();

		$setting_values = array();

		foreach ($ui_settings as $setting) {
			$setting_values[$setting] = get_option($setting, false);
		}

		wp_localize_script('sc-js-main', 'sc_settings', $setting_values);
	}

    /**
     * This function implements the rewrite tags for the different sections of the website
     */
    function sc_change_programs_search_url_rewrite() {
        if(get_query_var( 'post_type' ) == 'programa') {
            if(isset($_GET['cerca']) || isset($_GET['sistema_operatiu']) || isset($_GET['categoria_programa']) ) {
                $available_query_vars = array( 'cerca' => 'p', 'sistema_operatiu' => 'so', 'categoria_programa' => 'cat' );
                $params_query = '';
                foreach($available_query_vars as $query_var => $key) {
                    if (get_query_var( $query_var )) {
                        $params_query .= $key . '/' . urlencode( get_query_var( $query_var )) . '/';
                    }
                }

                if( ! empty( $params_query ) ) {
                    wp_redirect( home_url( "/programes/" ) . $params_query );
                }
            }
        } elseif(empty(get_query_var( 'post_type' ))) {
            if(isset($_GET['cerca']) && isset($_GET['form_cerca_noticies'])) {
                $available_query_vars = array( 'cerca' => 'cerca');
                foreach($available_query_vars as $query_var => $key) { 
                    $params_query .= $key . '/' . urlencode( get_query_var( $query_var )) . '/';
                }

                if( ! empty( $params_query ) ) {
                    wp_redirect( home_url( "/noticies/" ) . $params_query );
                }
            }
        }
    }

    /**
     *
     * esta funció s'encarrega de que si arriba alguna URL tipus /?s=XXX la converteix
     */
    function sc_change_search_url_rewrite() {
        if ( is_search() ) {
            if ( ! empty( $_GET['s'] ) ) {
                wp_redirect( home_url( "/cerca/" ) . urlencode( get_query_var( 's' ) ) . '/' );
                exit();
            } else {
                $real = get_search_query();
                $converted = $this->convert_smart_quotes($real);
                $real = html_entity_decode($real, ENT_QUOTES, "UTF-8");

                if ($converted != $real) {
                    wp_redirect( home_url( "/cerca/" ) . urlencode( $converted ) . '/' );
                    exit();
                }
            }
        }
    }

    function convert_smart_quotes($str)
    {
        $chr_map = array(
            // Windows codepage 1252
            "\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
            "\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
            "\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
            "\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
            "\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
            "\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
            "\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
            "\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

            // Regular Unicode     // U+0022 quotation mark (")
                                   // U+0027 apostrophe     (')
            "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
            "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
            "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
            "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
            "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
            "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
            "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
            "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
            "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
            "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
            "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
            "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
         );
         $chr = array_keys  ($chr_map); // but: for efficiency you should
         $rpl = array_values($chr_map); // pre-calculate these two arrays
         return str_replace($chr, $rpl, html_entity_decode($str, ENT_QUOTES, "UTF-8"));
    }

    /**
     * Change "search" by "cerca"
     */
    function sc_rewrite_search(){
        global $wp_rewrite;
        $wp_rewrite->search_base = 'cerca';
        $wp_rewrite->pagination_base = 'pagina';
    }

    function sc_author_rewrite_base() {
        global $wp_rewrite;
        $author_slug = 'membres';
        $wp_rewrite->author_base = $author_slug;
        $wp_rewrite->author_structure = '/membres/%author%';
    }

    /**
     * Custom Softcatalà settings
     */
    function include_sc_settings() {
        register_setting( 'softcatala-group', 'llistes_access' );
        register_setting( 'softcatala-group', 'api_diccionari_multilingue' );
        register_setting( 'softcatala-group', 'api_diccionari_sinonims' );
        register_setting( 'softcatala-group', 'catalanitzador_post_id' );
        register_setting( 'softcatala-group', 'aparells_post_id' );
        register_setting( 'softcatala-group', 'sc_text_programes' );

        $ui_settings = $this->get_ui_settings();
        foreach ( $ui_settings as $setting ) {
            register_setting( 'softcatala-group', $setting );
        }

        //Email contact parameters
        $sections = $this->get_email_sections();
        foreach ( $sections as $key => $section ) {
            register_setting( 'softcatala-group', 'email_'.$key );
        }

        if ( function_exists('add_submenu_page') ) {
            add_submenu_page('options-general.php', 'Softcatalà Settings', 'Softcatalà Settings', 'manage_options', __FILE__, array ( $this, 'softcatala_dash_page' ));
        }
    }

    function add_caps() {
		$roles = array();
		$roles[] = get_role( 'contributor' );
		$roles[] = get_role( 'author' );
		
		foreach ( $roles as $role ) {
			$role->add_cap( 'edit_pages' );
			$role->add_cap( 'edit_published_pages' );
			$role->add_cap( 'upload_files' );
		}
    }

    function get_email_sections() {
        $sections = array( 'general' => 'General', 'traductor' => 'Traductor', 'corrector' => 'Corrector', 'recursos' => 'Recursos', 'rebost' => 'Programes' );
        return $sections;
    }

    /**
     * Renders the Softcatalà dashboard settings page
     */
    function softcatala_dash_page() {
        wp_enqueue_script( 'sc-js-dash', get_template_directory_uri() . '/static/js/sc-admin.js', array('jquery'), WP_SOFTCATALA_VERSION, true );
        $admin_template = dirname(__FILE__) . '/templates/admin/sc-dash.twig';
        $sections = $this->get_email_sections();
        $section_html_content = Timber::fetch( $admin_template, array ('sections' => $sections ));
        echo $section_html_content;
    }

    function register_post_types() {
        global $sc_types;

        $sc_types['programes'] = new SC_Programes();
        $sc_types['projectes'] = new SC_Projectes();
    }

    function register_taxonomies() {
        //this is where you can register custom taxonomies
    }

    function add_user_nav_info_to_context( $context ) {
        $context['user_info'] = $this->get_user_information();
        $context['search_params'] = $this->get_search_params();
        $context['site'] = $this;
        $context['themepath'] = get_template_directory_uri();
        $context['current_url'] = get_current_url();
        return $context;
    }

    function add_to_twig( $twig ) {
        /* this is where you can add your own fuctions to twig */
        $twig->addExtension( new Twig_Extension_StringLoader() );
        $twig->addFilter('get_caption_from_media_url', new Twig_SimpleFilter( 'get_caption_from_media_url', 'get_caption_from_media_url' ));
        $twig->addFilter('get_img_from_id', new Twig_SimpleFilter( 'get_img_from_id', 'get_img_from_id' ));
        $twig->addFilter('truncate_twig', new Twig_SimpleFilter( 'truncate', 'truncate_twig' ));
        $twig->addFilter('print_definition', new Twig_SimpleFilter( 'print_definition', 'print_definition' ));
        $twig->addFilter('clean_number', new Twig_SimpleFilter( 'clean_number', 'clean_number' ));
        return $twig;
    }

    function get_search_params() {
        $search_params = array();

        $search_params['current_url'] = get_current_url();
        $search_params['current_url_filtre'] = remove_querystring_var( $search_params['current_url'], 'filtre' );
        $search_params['current_url_filtre_addition'] = get_filter_addition($search_params['current_url_filtre']);
        $search_params['current_url_nocat'] = get_current_url('filtre');
        $search_params['current_url_params'] = get_current_querystring();
        $search_params['current_url_noparams'] = str_replace($search_params['current_url_params'], '', $search_params['current_url']);

        return $search_params;
    }

    function get_user_information() {
        $user_info = array();
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $user_info['current_url'] = get_current_url();

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

    public function fix_woosidebar_hooks() {
        global $wp_filter;

        if ( !isset ($wp_filter['get_header'] ) ) {
            return;
        }

        $priorities = $wp_filter['get_header'];

        foreach( $priorities as $p => $filters ) {
            foreach ( $filters as $f => $v ) {
                $to_add = $v['function'];

                if( is_array( $to_add) && count( $to_add) == 2) {
                    $class = get_class( $to_add[0]);

                    if( strpos (  $class, 'Woo_' ) >= 0 ){
                        remove_action( 'get_header', $to_add);
                        add_action( 'template_redirect', $to_add, 10+$p);
                    }
                }
            }
        }
    }

}

global $sc_site;
$sc_site = new StarterSite();

function softcatala_scripts() {

    global $sc_site;

    wp_deregister_script( 'jquery' );
    wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.js' ), false, NULL, true );

    wp_register_script( 'sc-js-metacookie', get_template_directory_uri() . '/static/js/jquery.metacookie.js', array('jquery'), '20130313', true );

    wp_enqueue_script( 'jquery' );
    wp_enqueue_style( 'sc-css-main', get_template_directory_uri() . '/static/css/main.min.css', array(), WP_SOFTCATALA_VERSION );
    wp_enqueue_style( 'sc-css-cookies', '/../ssi/css/cookies/cookiecuttr.css', array(), WP_SOFTCATALA_VERSION );
    wp_enqueue_script( 'sc-js-main', get_template_directory_uri() . '/static/js/main.min.js', array('jquery'), WP_SOFTCATALA_VERSION, true );
    $sc_site->register_ui_settings();
    wp_enqueue_script( 'sc-jquery-cookie', '/../ssi/js/cookies/jquery.cookie.js', array('jquery'), WP_SOFTCATALA_VERSION, true );
    wp_enqueue_script( 'sc-js-cookiecuttr', '/../ssi/js/cookies/jquery.cookiecuttr.js', array('sc-jquery-cookie'), WP_SOFTCATALA_VERSION, true );
    //wp_enqueue_script( 'sc-js-ads', get_template_directory_uri() . '/static/js/ads.js', array(), WP_SOFTCATALA_VERSION, true );
    wp_enqueue_script( 'sc-js-comments', get_template_directory_uri() . '/static/js/comments.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
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
function get_caption_from_media_url( $attachment_url = '', $return_id = false ) {

    global $wpdb;
    $attachment_id = false;

    // If there is no url, return.
    if ( '' == $attachment_url )
        return;

    // Get the upload directory paths and clean the attachment url
    $upload_dir_paths = wp_upload_dir();
    $attachment_url = str_replace( 'wp/../', '', $attachment_url );

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

    if( $return_id ) {
        return $attachment_id;
    }

    return $attachment_meta;
}

/**
 * Twig function to truncate text
 *
 * @param string
 * @return string
 */
function truncate_twig( $string, $size )
{
    $splitstring = wp_trim_words( str_replace('_', ' ', $string ), $size );

    return $splitstring;
}

/**
 * Twig function specific for Dictionari multilingüe
 * Gets the source URL from the given data
 */
function get_source_link($result) {
    if($result->source == 'wikidata') {
        $value = '<a href="https://www.wikidata.org/wiki/' . $result->references->wikidata . '">Wikidata</a>';
    } else if ($result->source == 'wikidictionary_ca') {
        $value = '<a href="https://ca.wiktionary.org/wiki/' . $result->references->wikidictionary_ca . '">Viccionari</a>';
    }

    return $value;
}

/**
 * Removees useless decimal 0
 * @param string $n number to clean.
 * @return string
 */
function clean_number ( $n ) {
    return str_replace( ',00', '', $n );
}

/**
 * Twig function specific for Diccionari multilingüe
 *
 * @param string
 * @return string
 */
function print_definition( $def ) {
    $def = trim($def);
    $pos = strpos($def, '#');

    if ($pos === false) {
        $result = ' - ' . $def;
    } else {
        $def = str_replace('#', '', $def);
        $entries = explode("\n", $def);
        $filtered = array_filter(array_map('trim_entries', $entries));
        $result = ' - ' . implode('<br />- ', $filtered) . '<br />';
    }

    return $result;
}

function trim_entries($entry) {
    $trimmed = trim($entry);
    return empty($trimmed) ? null : $trimmed;
}

function get_img_from_id( $img_id ) {
    $image =  wp_get_attachment_image_src( $img_id );
    return $image[0];
}

/**
 * This function retrieves the current url, either on http or https format
 * depending on the current navigation
 *
 * @return string $url
 */
function get_current_url($remove = false)
{
    $current_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    if( $remove ) {
        $current_url = remove_query_arg( $remove, $current_url );
    }
    return $current_url;
}

abstract class SearchQueryType {
    const All = 0;
    const FilteredDate = 1;
    const Search = 2;
    const Highlight = 3;
    const Aparell = 4;
    const Programa = 5;
    const Post = 6;
    const PagePrograma = 7;
    const FilteredTema = 8;
    const Projecte = 9;
}

/*
 * Returns the arguments to apply to the mysql query
 */
function get_post_query_args( $post_type, $queryType, $filter = array() )
{
    //Retrieve posts
    switch ($post_type) {
        case 'esdeveniment':
            $base_args = array(
                'meta_key'   =>  'wpcf-data_inici',
                'post_type' => $post_type,
                'post_status'    => 'publish',
                'orderby'        => 'wpcf-data_inici',
                'order'          => 'ASC',
                'paged' => get_is_paged(),
                'posts_per_page' => 10
            );
            break;
        case 'aparell':
            $base_args = array(
                'post_type' => $post_type,
                'post_status'    => 'publish',
                'orderby' => 'title',
                'order'          => 'ASC',
                'posts_per_page' => -1
            );
            break;
        case 'programa':
            $base_args = array(
                'post_type' => $post_type,
                'post_status'    => 'publish',
                'orderby' => 'title',
                'order'          => 'ASC',
                'paged' => get_is_paged(),
                'posts_per_page' => 18,
                'tax_query' => array(
                    array (
                        'taxonomy' => 'classificacio',
                        'field' => 'slug',
                        'terms' => 'arxivat',
                        'operator'  => 'NOT IN'
                    )
                )
            );
            break;
        case 'projecte':
                $base_args = array(
                    'post_type' => $post_type,
                    'post_status'    => 'publish',
                    'orderby' => 'title',
                    'order'          => 'ASC',
                    'paged' => get_is_paged(),
                    'posts_per_page' => 36,
                    'tax_query' => array(
                        array (
                            'taxonomy' => 'classificacio',
                            'field' => 'slug',
                            'terms' => 'arxivat',
                            'operator'  => 'NOT IN'
                        )
                    )
                );
                break;
        case 'page':
            $base_args = array(
                'post_type' => $post_type,
                'post_status'    => 'publish',
                'order'          => 'ASC',
                'meta_query' => array(
                    get_meta_query_value('wpcf-'.$filter['subpage_type'], $filter['post_id'], '=', 'NUMERIC')
                )
            );
            break;
        case 'post':
            $base_args = array(
                'post_type' => $post_type,
                'post_status'    => 'publish',
                'order'          => 'DESC',
                'paged' => get_is_paged(),
                'posts_per_page' => 10
            );
            break;
    }

    $filter_args = array();
    if ( $queryType == SearchQueryType::Post ) {
        if ( ! empty ( $filter['s'] ) ) {
            $filter_args['s'] = $filter['s'];
        }
        if ( ! empty ( $filter['categoria'] ) ) {
            $filter_args['category__and'] = $filter['categoria'];
        }
    } else if ( $queryType == SearchQueryType::Search ) {
        $filter_args = array(
            's'         => $filter,
            'meta_query' => array(
                get_meta_query_value( 'wpcf-data_fi', time(), '>=', 'NUMERIC' )
            )
        );
    } else if( $queryType == SearchQueryType::FilteredDate ) {
        $filter_args = array(
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    get_meta_query_value( 'wpcf-data_fi', $filter['start_time'], '>=', 'NUMERIC' )
                ),
                array(
                    get_meta_query_value( 'wpcf-data_inici', $filter['final_time'], '<=', 'NUMERIC' )
                )
            )
        );
    } else if ( $queryType == SearchQueryType::Highlight ) {
        $filter_args = array(
            'posts_per_page' => 2,
            'meta_key'   =>  'wpcf-destacat',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'meta_query' => array(
                get_meta_query_value( 'wpcf-destacat', '0', '>=', 'NUMERIC' ),
                get_meta_query_value( 'wpcf-data_inici', '0', '>=', 'NUMERIC' ),
                get_meta_query_value( 'wpcf-data_fi', time(), '>=', 'NUMERIC' )
            )
        );
    } else if ( $queryType == SearchQueryType::Aparell ) {
        $filter_args = array();
        if (!empty ($filter['s'])) {
            $filter_args['s'] = $filter['s'];
        }

        if (!empty ($filter['so_aparell'])) {
            $filter_args['tax_query'][] = array(
                'taxonomy' => 'so_aparell',
                'field' => 'slug',
                'terms' => $filter['so_aparell']
            );
            $filter_args['filter_so'] = $filter['so_aparell'];
        }

        if (!empty ($filter['tipus_aparell'])) {
            $filter_args['tax_query'][] = array(
                'taxonomy' => 'tipus_aparell',
                'field' => 'slug',
                'terms' => $filter['tipus_aparell']
            );
            $filter_args['filter_tipus'] = $filter['tipus_aparell'];
        }

        if (!empty ($filter['fabricant'])) {
            $filter_args['tax_query'][] = array(
                'taxonomy' => 'fabricant',
                'field' => 'slug',
                'terms' => $filter['fabricant']
            );
            $filter_args['filter_fabricant'] = $filter['fabricant'];
        }
    } else if ( $queryType == SearchQueryType::Programa ) {
        $filter_args = array();
        //Avoid posts arxivats
        $filter_args['tax_query'][] = array(
            'taxonomy' => 'classificacio',
            'field' => 'slug',
            'terms' => 'arxivat',
            'operator'  => 'NOT IN'
        );

        if (!empty ($filter['s'])) {
            $filter_args['s'] = $filter['s'];
        }

        if(!empty ($filter['sistema-operatiu-programa'])) {
            $filter_args['tax_query'][] = array (
                'taxonomy' => 'sistema-operatiu-programa',
                'field' => 'slug',
                'terms' => array (
                    $filter['sistema-operatiu-programa'],
                    'multiplataforma'
                )
            );
            $filter_args['filter_sistema_operatiu'] = $filter['sistema-operatiu-programa'];
        }

        if ( ! empty ( $filter['post__in'] ) ) {
            $filter_args['post__in'] = $filter['post__in'];
        }

        if (!empty ($filter['categoria-programa'])) {
            $filter_args['tax_query'][] = array(
                'taxonomy' => 'categoria-programa',
                'field' => 'slug',
                'terms' => $filter['categoria-programa']
            );
            $filter_args['filter_categoria'] = $filter['categoria-programa'];
        }
    } else if ( $queryType == SearchQueryType::PagePrograma || $queryType == SearchQueryType::Projecte ) {
        $filter_args = array();
    } else if ( $queryType == SearchQueryType::FilteredTema ) {
        if (!empty ($filter)) {
            $filter_args['tax_query'][] = array(
                'taxonomy' => 'esdeveniment_cat',
                'field' => 'slug',
                'terms' => $filter
            );
        }
    } else {
        $filter_args = array(
            'meta_query' => array(
                get_meta_query_value( 'wpcf-data_fi', time(), '>=', 'NUMERIC' )
            )
        );
    }

    return array_merge( $base_args, $filter_args );
}

/*
 * Creates a param to query using a meta field
 */
function get_meta_query_value( $key, $value, $compare, $type ) {
    return array(
        'key'     => $key,
        'value' => $value,
        'compare' => $compare,
        'type'      => $type
    );
}

/*
 * Returns global paged variable
 */
function get_is_paged() {
    global $paged;

    return (!isset($paged) || !$paged) ? 1 : $paged;
}

/*
 * Function to handle the date filter for events
 */
function add_query_vars_filter( $vars ){
    $vars[] = "cerca";
    $vars[] = "sistema_operatiu";
    $vars[] = "tipus";
    $vars[] = "categoria_programa";
    $vars[] = "paraula";
    $vars[] = "tema";
    $vars[] = "data";
    $vars[] = "project";
    $vars[] = "lletra";
    $vars[] = "llengua";

    return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

/*
 * Retrieve all url active parameters
 */
function get_current_querystring()
{
    $output = '';
    $firstRun = true;
    foreach( $_GET as $key=>$val ) {
        if( !$firstRun ) {
            $output .= "&";
        } else {
            $output = "?";
            $firstRun = false;
        }
        $output .= sanitize_text_field( $key ) . "=" . sanitize_text_field( $val );
    }

    return $output;
}

/*
 * Removes a parameter from URL
 *
 * Source: https://davidwalsh.name/php-remove-variable#comment-16120
 */
function remove_querystring_var( $url, $key ) {
    $url = preg_replace( '/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&' );
    $url = substr($url, 0, -1);

    return $url;
}

function get_filter_addition( $url ) {
    $pos = strpos($url, '?');
    if ($pos === false) {
        $addition = '?';
    } else {
        $addition = '&';
    }

    return $addition;
}

/*
 * Returns the start_time and final_time of the time range in UNIX Timestamp
 */
function get_final_time( $filter )
{
    $today_unix_time = strtotime("today");

    switch ($filter) {
        case 'setmana':
            $filterdate['start_time'] = $today_unix_time;
            $filterdate['final_time'] = strtotime("next Sunday");
            break;
        case 'mes':
            $filterdate['start_time'] = $today_unix_time;
            $filterdate['final_time'] = strtotime("first day of next month");
            break;
        case 'setmanavinent':
            $filterdate['start_time'] = strtotime("next Monday");
            $filterdate['final_time'] = strtotime("sunday next week");
            break;
        default:
            $filterdate['start_time'] = $today_unix_time;
            $filterdate['final_time'] = strtotime("+100 weeks");
            break;
    }

    return $filterdate;
}

/*
 * Function that modifies the orderby query only for events in home page
 */
function orderbyreplace( $orderby ) {
    global $wpdb;
    return str_replace($wpdb->prefix.'postmeta.meta_value DESC', 'mt1.meta_value DESC, mt2.meta_value ASC', $orderby);
}

/*
 * Function that adds a excerpt to pages to be used as a subtitle
 */
add_action( 'init', 'sc_add_excerpts_to_pages' );
function sc_add_excerpts_to_pages() {
    add_post_type_support( 'page', 'excerpt' );
}

function sendEmailForm( $to_email, $nom_from, $assumpte, $fields ) {
    return sendEmailWithFromAndTo($to_email, $to_email, $nom_from, $assumpte, $fields);
}

/*
 * General 'send email' function
 */
function sendEmailWithFromAndTo( $to_email, $from_email, $nom_from, $assumpte, $fields) {
    //email body
    $message_body = '';
    foreach( $fields as $key => $field ) {
        $message_body .= $key . ": " . $field . "\r\n\r";
    }

    //proceed with PHP email.
    $headers = 'From: '.$nom_from.' <'.$from_email. ">\r\n" .
        'Reply-To: web@softcatala.org' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    $send_mail = wp_mail($to_email, $assumpte, $message_body, $headers);

    if(!$send_mail) {
        //If mail couldn't be sent output error. Check your PHP email configuration (if it ever happens)
        $output = json_encode(array('type'=>'error', 'text' => 'S\'ha produït un error en enviar el missatge.'));
    } else {
        $output = json_encode(array('type'=>'message', 'text' => 'S\'ha enviat la informació.'));
    }
    return $output;
}

/*  Add responsive container to embeds
/* ------------------------------------ */
function sc_embed_html( $html ) {
    return '<div class="embed-responsive embed-responsive-16by9">' . $html . '</div>';
}
add_filter( 'embed_oembed_html', 'sc_embed_html', 10, 3 );

/* SVG Graphics */
function cc_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

/**
 * Returns the user role for a user
 *
 * @param $author
 * @return mixed
 */
function get_user_role( $author )
{
    $user = get_user_by('id', $author->ID);
    $user_roles = $user->roles;
    $user_role = array_shift($user_roles);

    return $user_role;
}

/**
 * This function sets specific error headers for 404 and 500 error pages
 *
 * @param $code
 * @param $message
 */
function throw_error( $code, $message ) {
    global $wp_query;
    header("HTTP/1.1 " . $code . " " . $message);
    ${"call"} = 'set_'.$code;
    $wp_query->{"call"}();
}

/**
 * This function executes an API call of the type 'rest' given a url with all the parameters in it
 *
 * @param $url
 * @return mixed
 */
function do_json_api_call( $url ) {
    $api_call = wp_remote_get(
        $url,
        array(
            'method' => 'GET',
            'timeout' => 5,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        )
    );

    if ( is_wp_error( $api_call ) ) {
        $result = 'error';
    } else {
        if( isset($api_call['body']) && $api_call['body'] != '[]' ) {
            $result = $api_call['body'];
        } else {
            //Return true to inform that the call was OK, but the result was empty
            $result = $api_call;
        }
    }

    return $result;
}

/**
 * In case the variable 'redirect_page' is set, the comment form will redirect to that value
 *
 * @param $location
 * @return string
 */
function aparell_comment_redirect( $location ) {
    if ( isset( $_POST['redirect_page'] ) ) // Don't use "redirect_to", internal WP var
        $location = esc_url($_POST['redirect_page']);

    return $location;
}
add_filter( 'comment_post_redirect', 'aparell_comment_redirect' );

add_shortcode( 'multilingue-stats', 'multilingue_stats' );

function multilingue_stats() {
    $url_api = get_option( 'api_diccionari_multilingue' );

    $api_call = do_json_api_call($url_api . '/statistics');
    $statistics = json_decode($api_call);

    $stats = '';

    if ( $statistics ) {
        $ca_labels = add_multilingue_stats($statistics, 'ca_labels');
        $ca_descs = add_multilingue_stats($statistics, 'ca_descs');
        $en_labels = add_multilingue_stats($statistics, 'en_labels');
        $en_descs = add_multilingue_stats($statistics, 'en_descs');
        $fr_labels = add_multilingue_stats($statistics, 'fr_labels');
        $fr_descs = add_multilingue_stats($statistics, 'fr_descs');
        $de_labels = add_multilingue_stats($statistics, 'de_labels');
        $de_descs = add_multilingue_stats($statistics, 'de_descs');
        $es_labels = add_multilingue_stats($statistics, 'es_labels');
        $es_descs = add_multilingue_stats($statistics, 'es_descs');
        $it_labels = add_multilingue_stats($statistics, 'it_labels');
        $it_descs = add_multilingue_stats($statistics, 'it_descs');

        ob_start();

        ?>
        <i><small>
            L'índex va ser actualitzat per últim cop el <?= $statistics->wikidata->date ?> i conté: <?=$ca_labels?>
                paraules i <?=$ca_descs?> definicions en català, <?=$en_labels?> paraules i <?=$en_descs?>
                definicions en anglès, <?=$fr_labels?> paraules i <?=$fr_descs?> definicions en francès,
                <?=$it_labels?> paraules i <?=$it_descs?> definicions en italià, <?=$de_labels?> paraules i
                <?=$de_descs?> definicions en alemany, <?=$es_labels?> paraules i <?=$es_descs?>  definicions
                en espanyol, i <?= $statistics->wikidata->images ?> imatges.
        </small></i>
        <?php

        $stats = ob_get_clean();
    }

    return $stats;
}

function add_multilingue_stats($statistics, $key) {
        $wikidata = (array) $statistics->wikidata;
        $wikidictionary = (array) $statistics->wikidictionary;
        $value = $wikidata[$key];
        if(isset($wikidictionary[$key]) && $wikidictionary[$key]) {
                $value += $wikidictionary[$key];
        }
        return $value;
}


/**
 * Sets the program so depending on the downloads so
 *
 */
function align_downloads_programs_so( $post_id )
{
    $slug = 'programa';
    $post = get_post($post_id);

    // If this isn't a 'book' post, don't update it.
    if ( $slug != $post->post_type ) {
        return;
    }

    $downloads = get_field( 'baixada' );

    if( $downloads ) {
        foreach ($downloads as $download) {
            $id = term_exists( $download['download_os'], 'sistema-operatiu-programa' );
            $terms[] = $id['term_id'];
        }

        //Set the operating system taxonomy for program
        $terms = array_map( 'intval', $terms );
        wp_set_object_terms( $post_id, $terms, 'sistema-operatiu-programa', false );
    }
}
add_action( 'save_post', 'align_downloads_programs_so' );

/**
 * Set the resize quality to 90
 *
 **/
function sc_image_full_quality( $quality ) {
    return 90;
}
add_filter( 'jpeg_quality', 'sc_image_full_quality' );
add_filter( 'wp_editor_set_quality', 'sc_image_full_quality' );

/*
 * Responsive images
 */
function sc_responsive_image_sizes($sizes, $size) {

    $width = $size[0];

    // 1200, 1025, 769, 480

    // Let's assume we'll always have sidebar
    if ($width > 870) {
        return '(max-width: 768px) 92vw, (max-width: 1024px) 738px, (max-width: 1200px) 870px, 870x';
    }

    return $sizes;
}
add_filter('wp_calculate_image_sizes', 'sc_responsive_image_sizes', 10 , 2);


/**
 * Generic function to inform SC about sections on the website not working properly
 *
 **/
function throw_service_error( $service, $message = '' ) {
    throw_error('500', 'Error connecting to API server');

    $fieds['Hora'] = current_time( 'mysql' );
    if( $message ) {
        $fieds['Missatge d\'error'] = $message;
    }

    sendEmailForm( 'web@softcatala.org', $service, 'El servei «' . $service . '» no està funcionant correctament', $fields );
}

add_filter( 'rest_authentication_errors', 'sc_only_allow_logged_in_rest_access' );

function sc_only_allow_logged_in_rest_access( $access ) {

    if( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_cannot_access', __( 'Only authenticated users can access the REST API.', 'disable-json-api' ), array( 'status' => rest_authorization_required_code() ) );
    }

    return $access;           
}


add_filter( 'user_contactmethods', 'update_contact_methods',10,1);

function update_contact_methods( $contactmethods ) {
	unset($contactmethods['twitter']);
	unset($contactmethods['facebook']);
	unset($contactmethods['googleplus']);
	unset($contactmethods['url']);

	return $contactmethods;
}

function remove_website_row_wpse_94963_css()
{
    echo '<style>tr.user-url-wrap{ display: none; }</style>';
}
add_action( 'admin_head-user-edit.php', 'remove_website_row_wpse_94963_css' );
add_action( 'admin_head-profile.php',   'remove_website_row_wpse_94963_css' );

add_filter( 'pre_get_avatar_data', 'sc_set_avatar_based_on_user_meta', 10, 2 );
function sc_set_avatar_based_on_user_meta( $args, $id_or_email ){

	// Set this to the full meta key set in Save As under Auto Populate tab (for WP Job Manager Field Editor)
	$user_avatar_meta_key = 'avatar';

	// Check for comment_ID
	if( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ){
		$id_or_email = get_comment( $id_or_email );
	}

	// Check if WP_Post
	if( $id_or_email instanceof WP_Post ){
		$user_id = $id_or_email->post_author;
	}

	// Check if WP_Comment
	if( $id_or_email instanceof WP_Comment ){
		if( ! empty( $id_or_email->user_id ) ){
			$user_id = $id_or_email->user_id;
		} elseif( ! empty( $id_or_email->comment_author_email ) ){
			// If user_id not available, set as email address to handle below
			$id_or_email = $id_or_email->comment_author_email;
		}
	}

	if( is_numeric( $id_or_email ) ){
		$user_id = $id_or_email;
	} elseif( is_string( $id_or_email ) && strpos( $id_or_email, '@' ) ){
		$id_or_email = get_user_by( 'email', $id_or_email );
	}

	// Last check, convert user object to ID
	if( $id_or_email instanceof WP_User ){
		$user_id = $id_or_email->ID;
	}

	// Now that we have a user ID, check meta for avatar file
	if( ! empty( $user_id ) && is_numeric( $user_id ) ){

		// As long as it's a valid URL, let's go ahead and set it
		$image_id = get_user_meta($user_id, 'avatar', true); // CHANGE TO YOUR FIELD NAME
		// Bail if we don't have a local avatar
		if ( $image_id ) {
			// Get the file size
			$image_url  = wp_get_attachment_image_src( $image_id, 'full' ); // Set image size by name

			// Get the file url
			$avatar_url = $image_url[0];

			if( filter_var( $avatar_url, FILTER_VALIDATE_URL ) ){
				$args['url'] = $avatar_url;
			}
		}
	}

	return $args;
}

