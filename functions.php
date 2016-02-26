<?php

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
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'sc_rewrite_search' ) );
        add_action( 'template_redirect', array( $this, 'fix_woosidebar_hooks'), 1);
        add_action( 'template_redirect', array( $this, 'sc_change_search_url_rewrite' ) );
        add_action( 'after_setup_theme', array( $this, 'include_theme_conf' ) );
        //SC Dashboard settings
        add_action('admin_menu', array( $this, 'include_sc_settings' ));

        spl_autoload_register( array( $this, 'autoload' ) );

        parent::__construct();
    }

    function autoload($cls) {
        $path =  __DIR__ . '/classes/' . strtolower(str_replace('SC_', '', $cls)) . '.php';

        is_readable($path) && require_once($path);
    }

    function include_theme_conf() {
        locate_template( array( 'inc/widgets.php' ), true, true );
        locate_template( array( 'inc/post_types_functions.php' ), true, true );
        locate_template( array( 'inc/shortcodes-llistes.php' ), true, true );
        locate_template( array( 'inc/ajax_operations.php' ), true, true );
        locate_template( array( 'inc/rewrites.php' ), true, true );
    }

    /**
     *
     * esta funció s'encarrega de que si arriba alguna URL tipus /?s=XXX la converteix
     */
    function sc_change_search_url_rewrite() {
        if ( is_search() && ! empty( $_GET['s'] ) ) {
            wp_redirect( home_url( "/cerca/" ) . urlencode( get_query_var( 's' ) ) );
            exit();
        }
    }

    /**
     * Change "search" by "cerca"
     */
    function sc_rewrite_search(){
        global $wp_rewrite;
        $wp_rewrite->search_base = 'cerca';
    }

    /**
     * Custom Softcatalà settings
     */
    function include_sc_settings() {
        register_setting( 'softcatala-group', 'llistes_access' );

        if ( function_exists('add_submenu_page') )
            add_submenu_page('options-general.php', 'Softcatalà Settings', 'Softcatalà Settings', 'manage_options', __FILE__, array ( $this, 'softcatala_dash_page' ));
    }

    /**
     * Renders the Softcatalà dashboard settings page
     */
    function softcatala_dash_page() {
        $admin_template = dirname(__FILE__) . '/templates/admin/sc-dash.twig';
        $section_html_content = Timber::fetch( $admin_template );
        echo $section_html_content;
    }

    function register_post_types() {
        global $sc_types;

        $sc_types['programes'] = new SC_Programes();
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
        $twig->addFilter('truncate_twig', new Twig_SimpleFilter( 'truncate', 'truncate_twig' ));
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

new StarterSite();

function softcatala_scripts() {
    wp_enqueue_style( 'sc-css-main', get_template_directory_uri() . '/static/css/main.min.css', array(), '1.0' );
    wp_enqueue_script( 'sc-js-main', get_template_directory_uri() . '/static/js/main.min.js', array('jquery'), '1.0.0', true );
    wp_enqueue_script( 'sc-js-ads', get_template_directory_uri() . '/static/js/ads.js', '1.0.0', true );
    wp_enqueue_script( 'sc-js-comments', get_template_directory_uri() . '/static/js/comments.js', array('sc-js-main'), '1.0.0', true );
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

function retrieve_page_data($page_slug = '')
{
    //Actions to be taken depending on the post type
    switch ($page_slug) {
        case 'noticies':
            $args = array(
                'name' => 'noticies',
                'post_type' => 'page'
            );
            $post = Timber::get_post($args);
            break;
        default:
            $args = array(
                'name' => $page_slug.'-page',
                'post_type' => 'page'
            );
            $post = Timber::get_post($args);
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
            'link' => 'mes',
            'title' => 'Aquest mes'
        )
    );
    return $filtres;
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
    const Baixada = 9;
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
                'order'          => 'ASC'
            );
            break;
        case 'programa':
            $base_args = array(
                'post_type' => $post_type,
                'post_status'    => 'publish',
                'order'          => 'ASC',
                'paged' => get_is_paged(),
                'posts_per_page' => 18,
                'meta_query' => array(
                    get_meta_query_value('wpcf-arxivat', 0, '=', 'NUMERIC')
                )
            );
            break;
        case 'page':
            $base_args = array(
                'post_type' => $post_type,
                'post_status'    => 'publish',
                'order'          => 'ASC',
                'meta_query' => array(
                    get_meta_query_value('wpcf-programa', $filter['post_id'], '=', 'NUMERIC')
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
        case 'baixada':
            $base_args = array(
                'post_type' => $post_type,
                'post_status'    => 'publish'
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
    } else if ( $queryType == SearchQueryType::Baixada ) {
        $filter_args = array(
            'tax_query' => array (
                array(
                    'taxonomy' => 'sistema-operatiu-programa',
                    'field' => 'slug',
                    'terms' => $filter
                )
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

        if (!empty ($filter['sistema_operatiu_aparell'])) {
            $filter_args['tax_query'][] = array(
                'taxonomy' => 'sistema_operatiu_aparell',
                'field' => 'slug',
                'terms' => $filter['sistema_operatiu_aparell']
            );
            $filter_args['filter_so'] = $filter['sistema_operatiu_aparell'];
        }

        if (!empty ($filter['tipus_aparell'])) {
            $filter_args['tax_query'][] = array(
                'taxonomy' => 'tipus_aparell',
                'field' => 'slug',
                'terms' => $filter['tipus_aparell']
            );
            $filter_args['filter_tipus'] = $filter['tipus_aparell'];
        }
    } else if ( $queryType == SearchQueryType::Programa ) {
        $filter_args = array();
        if (!empty ($filter['s'])) {
            $filter_args['s'] = $filter['s'];
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

        //If 'arxivat = 1' that means that all programas should be displayed, arxivats and no arxivats
        //It's necessary to remove the meta_query filter
        if( ! empty ($filter['arxivat']) &&  $filter['arxivat'] == 1 ) {
            unset( $base_args['meta_query'] );
            $filter_args['arxivat'] = $filter['arxivat'];
        }
    } else if ( $queryType == SearchQueryType::PagePrograma ) {
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
    $vars[] = "arxivat";
    $vars[] = "paraula";
    $vars[] = "tema";
    $vars[] = "data";
    $vars[] = "project";

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

/*
 * General 'send email' function
 */
function sendEmailForm( $to_email, $nom_from, $assumpte, $fields ) {
    //email body
    $message_body = '';
    foreach( $fields as $key => $field ) {
        $message_body .= $key . ": " . $field . "\r\n\r";
    }

    //proceed with PHP email.
    $headers = 'From: '.$nom_from.' <'.$to_email. ">\r\n" .
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