<?php

define( 'WP_SOFTCATALA_VERSION', '1.0.82' );

include ('php73.php');

if( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
} else if( file_exists( ABSPATH . '/../vendor/autoload.php' ) ) {
	require ABSPATH . '/../vendor/autoload.php';
} else {

	if ( is_admin() ) {

		add_action( 'admin_notices', function () {
			echo '<div class="error">' .
			        '<p>Composer autoload is not working. Theme wp-softcatala depends on composer autoloading.</p>' .
				 '</div>';
			}
		);

		return;

	} else if ( ! is_admin() ) {

		header( 'HTTP/1.1 500 Internal Server Error' );
		echo 'Aquest és un error 500. Alguna cosa no funciona bé al servidor.';
		die();
	}
}

$timber = new \Timber\Timber();

include( 'inc/perfils.php' );



Timber::$dirname = array( 'templates', 'views' );

class StarterSite extends TimberSite {

	function __construct() {
		if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'menus' );
			add_theme_support( 'title-tag' );
		}

		add_filter( 'timber_context', array( $this, 'add_user_nav_info_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_filter( 'xv_planeta_feed', '__return_true' );
		add_filter( 'xv_podcasts_log_file', function( $v ) {
			return ABSPATH . '../podcast.log';
		} );
		add_filter( 'xv_podcasts_log_fields', function( $f ) {
			return array_merge( $f, [
				'ip' => $_SERVER['HTTP_X_REAL_IP'],
				'accept' => $_SERVER['HTTP_ACCEPT'],
				'encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'],
				'charset' => $_SERVER['HTTP_ACCEPT_CHARSET'],
				'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
				'referer' => $_SERVER['HTTP_REFERER'],
				'ua' => $_SERVER['HTTP_USER_AGENT']
			]);
		} );
		add_filter( 'wpseo_twitter_creator_account', function ( $twitter ) {
			return '@softcatala';
		} );
		add_filter( 'wpseo_opengraph_author_facebook', function ( $twitter ) {
			return 'https://facebook.com/Softcatala';
		} );
		add_action( 'init', array( $this, 'sc_rewrite_search' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'template_redirect', array( $this, 'sc_change_programs_search_url_rewrite' ) );
		add_action( 'init', array( $this, 'sc_author_rewrite_base' ) );
		add_action( 'template_redirect', array( $this, 'fix_woosidebar_hooks' ), 1 );
		add_action( 'template_redirect', array( $this, 'sc_change_search_url_rewrite' ) );
		add_action( 'after_setup_theme', array( $this, 'include_theme_conf' ) );
		//SC Dashboard settings
		add_action( 'admin_menu', array( $this, 'include_sc_settings' ) );
		add_action( 'admin_init', array( $this, 'add_caps' ) );

		spl_autoload_register( array( $this, 'autoload' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			spl_autoload_register( array( $this, 'autoload_wpcli' ) );

			require __DIR__ . '/wp-cli/loader.php';
		}

		add_post_type_support( 'programa', 'woosidebars' );

		$this->init_services();

		parent::__construct();
	}

	public function init_services() {
		SC_Multilingue::init();
		\Softcatala\Content\JsonToTable::init();
	}

	function autoload_wpcli( $cls ) {
		$path = __DIR__ . '/wp-cli/' . strtolower( $cls ) . '.php';

		is_readable( $path ) && require_once( $path );
	}

	function autoload( $cls ) {
		$this->tryLoadFromNamespace( $cls ) || $this->tryLoadFromClasses( $cls );
	}

	function tryLoadFromClasses( $cls ) {

		if ( 0 !== strpos( $cls, 'SC_' ) ) {
			return;
		}

		$name = str_replace( 'SC_', '', $cls );
		$name = str_replace( '_', '-', $name );

		$path = __DIR__ . '/classes/' . strtolower( $name ) . '.php';

		if ( is_readable( $path ) && require_once( $path ) ) {
			return;
		}
	}

	function tryLoadFromNamespace( $cls ) {

		if ( 0 !== strpos( $cls, 'Softcatala' ) && 0 !== strpos( $cls, '\Softcatala' ) ) {
			return;
		}

		$path = __DIR__ . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $cls ) . '.php';
		$path = $this->decamelize( str_replace( 'Softcatala' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $path ) );

		return is_readable( $path ) && require_once( $path );
	}

	function decamelize( $string ) {
		return strtolower(
			str_replace(
				DIRECTORY_SEPARATOR . '-', DIRECTORY_SEPARATOR,
				preg_replace( [ '/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/' ], '$1-$2', $string )
			)
		);
	}

	function include_theme_conf() {
		locate_template( array( 'inc/widgets.php' ), true, true );
		locate_template( array( 'inc/post_types_functions.php' ), true, true );
		locate_template( array( 'inc/ajax_operations.php' ), true, true );
		locate_template( array( 'inc/rewrites.php' ), true, true );
	}

	function register_ui_settings() {
		wp_localize_script( 'sc-js-main', 'sc_settings', SC_Settings::get_instance()->get_setting_values() );
	}

	/**
	 * This function implements the rewrite tags for the different sections of the website
	 */
	function sc_change_programs_search_url_rewrite() {

		$post_type = get_query_var( 'post_type' );

		$params_query         = '';

		if ( $post_type == 'programa' ) {
			if ( isset( $_GET['cerca'] ) || isset( $_GET['sistema_operatiu'] ) || isset( $_GET['categoria_programa'] ) ) {
				$available_query_vars = array(
					'cerca'              => 'p',
					'sistema_operatiu'   => 'so',
					'categoria_programa' => 'cat'
				);

				foreach ( $available_query_vars as $query_var => $key ) {
					if ( get_query_var( $query_var ) ) {
						$params_query .= $key . '/' . urlencode( get_query_var( $query_var ) ) . '/';
					}
				}

				if ( ! empty( $params_query ) ) {
					wp_redirect( home_url( "/programes/" ) . $params_query );
				}
			}
		} elseif ( empty( $post_type ) ) {
			if ( isset( $_GET['cerca'] ) && isset( $_GET['form_cerca_noticies'] ) ) {
				$available_query_vars = array( 'cerca' => 'cerca' );
				foreach ( $available_query_vars as $query_var => $key ) {
					$params_query .= $key . '/' . urlencode( get_query_var( $query_var ) ) . '/';
				}

				if ( ! empty( $params_query ) ) {
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
				$real      = get_search_query();
				$converted = $this->convert_smart_quotes( $real );
				$real      = html_entity_decode( $real, ENT_QUOTES, "UTF-8" );

				if ( $converted != $real ) {
					wp_redirect( home_url( "/cerca/" ) . urlencode( $converted ) . '/' );
					exit();
				}
			}
		}
	}

	function convert_smart_quotes( $str ) {
		$chr_map = array(
			// Windows codepage 1252
			"\xC2\x82"     => "'", // U+0082⇒U+201A single low-9 quotation mark
			"\xC2\x84"     => '"', // U+0084⇒U+201E double low-9 quotation mark
			"\xC2\x8B"     => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
			"\xC2\x91"     => "'", // U+0091⇒U+2018 left single quotation mark
			"\xC2\x92"     => "'", // U+0092⇒U+2019 right single quotation mark
			"\xC2\x93"     => '"', // U+0093⇒U+201C left double quotation mark
			"\xC2\x94"     => '"', // U+0094⇒U+201D right double quotation mark
			"\xC2\x9B"     => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

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
		$chr     = array_keys( $chr_map ); // but: for efficiency you should
		$rpl     = array_values( $chr_map ); // pre-calculate these two arrays
		return str_replace( $chr, $rpl, html_entity_decode( $str, ENT_QUOTES, "UTF-8" ) );
	}

	/**
	 * Change "search" by "cerca"
	 */
	function sc_rewrite_search() {
		global $wp_rewrite;
		$wp_rewrite->search_base     = 'cerca';
		$wp_rewrite->pagination_base = 'pagina';
	}

	function sc_author_rewrite_base() {
		global $wp_rewrite;
		$author_slug                  = 'membres';
		$wp_rewrite->author_base      = $author_slug;
		$wp_rewrite->author_structure = '/membres/%author%';
	}

	/**
	 * Custom Softcatalà settings
	 */
	function include_sc_settings() {
		register_setting( 'softcatala-group', 'llistes_access' );
		register_setting( 'softcatala-group', 'api_diccionari_multilingue' );
		register_setting( 'softcatala-group', 'api_diccionari_sinonims' );
		register_setting( 'softcatala-group', 'api_conjugador' );
		register_setting( 'softcatala-group', 'api_memory_base' );
		register_setting( 'softcatala-group', 'catalanitzador_post_id' );
		register_setting( 'softcatala-group', 'aparells_post_id' );
		register_setting( 'softcatala-group', 'sc_text_programes' );

		$ui_settings = SC_Settings::get_instance()->get_setting_names();
		foreach ( $ui_settings as $setting ) {
			register_setting( 'softcatala-group', $setting );
		}

		//Email contact parameters
		$sections = $this->get_email_sections();
		foreach ( $sections as $key => $section ) {
			register_setting( 'softcatala-group', 'email_' . $key );
		}

		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'options-general.php', 'Softcatalà Settings', 'Softcatalà Settings', 'manage_options', __FILE__, array(
				$this,
				'softcatala_dash_page'
			) );
		}
	}

	function add_caps() {
		$roles   = array();
		$roles[] = get_role( 'contributor' );
		$roles[] = get_role( 'author' );

		foreach ( $roles as $role ) {
			$role->add_cap( 'edit_pages' );
			$role->add_cap( 'edit_published_pages' );
			$role->add_cap( 'upload_files' );
		}
	}

	function get_email_sections() {
		$sections = array(
			'general'   => 'General',
			'traductor_neuronal' => 'Traductor Neuronal',
			'traductor' => 'Traductor',
			'corrector' => 'Corrector',
			'recursos'  => 'Recursos',
			'rebost'    => 'Programes'
		);

		return $sections;
	}

	/**
	 * Renders the Softcatalà dashboard settings page
	 */
	function softcatala_dash_page() {
		wp_enqueue_script( 'sc-js-dash', get_template_directory_uri() . '/static/js/sc-admin.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
		$admin_template       = dirname( __FILE__ ) . '/templates/admin/sc-dash.twig';
		$sections             = $this->get_email_sections();
		$settings             = SC_Settings::get_instance();
		$section_html_content = Timber::fetch( $admin_template, array(
			'sections' => $sections,
			'settings' => $settings
		) );
		echo $section_html_content;
	}

	function register_post_types() {

		\Softcatala\TypeRegisters\Slider::get_instance();
		\Softcatala\TypeRegisters\Esdeveniment::get_instance();
		\Softcatala\TypeRegisters\Aparell::get_instance();
		\Softcatala\TypeRegisters\Programa::get_instance();
		\Softcatala\TypeRegisters\Projecte::get_instance();
	}

	function add_user_nav_info_to_context( $context ) {
		$context['user_info']     = $this->get_user_information();
		$context['search_params'] = $this->get_search_params();
		$context['site']          = $this;
		$context['themepath']     = get_template_directory_uri();
		$context['current_url']   = get_current_url();

		return $context;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own fuctions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );
		$twig->addFilter( new Twig_Filter( 'get_caption_from_media_url', 'get_caption_from_media_url' ) );
		$twig->addFilter( new Twig_Filter( 'get_img_from_id', 'get_img_from_id' ) );
		$twig->addFilter( new Twig_Filter( 'get_full_img_from_id', 'get_full_img_from_id' ) );
		$twig->addFilter( new Twig_Filter( 'truncate_words', 'sc_truncate_words' ) );
		$twig->addFilter( new Twig_Filter( 'print_definition', 'print_definition' ) );
		$twig->addFilter( new Twig_Filter( 'clean_number', 'clean_number' ) );
		$twig->addFilter( new Twig_filter( 'home_thumb', 'home_thumb' ) );

		return $twig;
	}

	function get_search_params() {
		$search_params = array();

		$search_params['current_url']                 = get_current_url();
		$search_params['current_url_filtre']          = remove_querystring_var( $search_params['current_url'], 'filtre' );
		$search_params['current_url_filtre_addition'] = get_filter_addition( $search_params['current_url_filtre'] );
		$search_params['current_url_nocat']           = get_current_url( 'filtre' );
		$search_params['current_url_params']          = get_current_querystring();
		$search_params['current_url_noparams']        = str_replace( $search_params['current_url_params'], '', $search_params['current_url'] );

		return $search_params;
	}

	function get_user_information() {
		$user_info                = array();
		$user_id                  = get_current_user_id();
		$current_user             = wp_get_current_user();
		$user_info['current_url'] = get_current_url();

		if ( $user_id ) {
			$user_info['is_connected']  = true;
			$user_info['wp_logout_url'] = wp_logout_url( '/' );
			$user_info['avatar']        = get_avatar( $user_id, 19, null, 'fotografia-usuari-sofcatala' );
			$user_info['avatar_48']     = get_avatar( $user_id, 48, null, 'fotografia-usuari-sofcatala' );
			$user_info['name']          = $current_user->display_name;
			$user_info['profile_url']   = get_edit_profile_url( $user_id );
		} else {
			$user_info['avatar']       = get_avatar( $user_id, 19, null, 'fotografia-usuari-sofcatala' );
			$user_info['is_connected'] = false;
			$user_info['wp_login_url'] = wp_login_url( get_current_url() );
		}

		return $user_info;
	}

	public function fix_woosidebar_hooks() {
		global $wp_filter;

		if ( ! isset ( $wp_filter['get_header'] ) ) {
			return;
		}

		$priorities = $wp_filter['get_header'];

		foreach ( $priorities as $p => $filters ) {
			foreach ( $filters as $f => $v ) {
				$to_add = $v['function'];

				if ( is_array( $to_add ) && count( $to_add ) == 2 ) {
					$class = get_class( $to_add[0] );

					if ( strpos( $class, 'Woo_' ) >= 0 ) {
						remove_action( 'get_header', $to_add );
						add_action( 'template_redirect', $to_add, 10 + $p );
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
	wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.js' ), false, null, true );

	wp_register_script( 'sc-js-metacookie', get_template_directory_uri() . '/static/js/jquery.metacookie.js', array( 'jquery' ), '20130313', true );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_style( 'sc-css-main', get_template_directory_uri() . '/static/css/main.min.css', array(), WP_SOFTCATALA_VERSION );
	wp_enqueue_style( 'sc-css-cookies', '/../ssi/css/cookies/cookiecuttr.css', array(), WP_SOFTCATALA_VERSION );
	wp_enqueue_script( 'sc-js-main', get_template_directory_uri() . '/static/js/main.min.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
	$sc_site->register_ui_settings();
	wp_enqueue_script( 'sc-jquery-cookie', '/../ssi/js/cookies/jquery.cookie.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
	wp_enqueue_script( 'sc-js-cookiecuttr', '/../ssi/js/cookies/jquery.cookiecuttr.js', array( 'sc-jquery-cookie' ), WP_SOFTCATALA_VERSION, true );
	//wp_enqueue_script( 'sc-js-ads', get_template_directory_uri() . '/static/js/ads.js', array(), WP_SOFTCATALA_VERSION, true );
	wp_enqueue_script( 'sc-js-comments', get_template_directory_uri() . '/static/js/comments.js', array( 'sc-js-main' ), WP_SOFTCATALA_VERSION, true );
}

add_action( 'wp_enqueue_scripts', 'softcatala_scripts' );

/**
 * This function retrieves the media caption from
 * a given url. It is used because the «secondary image»
 * created from Types doesn't return the media caption
 * Author: https://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
 *
 * @param string $url
 *
 * @return string $caption
 */
function get_caption_from_media_url( $attachment_url = '', $return_id = false ) {

	global $wpdb;
	$attachment_id = false;

	// If there is no url, return.
	if ( '' == $attachment_url ) {
		return;
	}

	// Get the upload directory paths and clean the attachment url
	$upload_dir_paths = wp_upload_dir();
	$attachment_url   = str_replace( 'wp/../', '', $attachment_url );

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
	$attachment_meta = get_post_field( 'post_excerpt', $attachment_id );

	if ( $return_id ) {
		return $attachment_id;
	}

	return $attachment_meta;
}

/**
 * Twig function to truncate text
 *
 * @param string
 *
 * @return string
 */
function sc_truncate_words( $string, $size ) {
	$splitstring = wp_trim_words( str_replace( '_', ' ', $string ), $size );

	return $splitstring;
}

/**
 * Removes useless decimal 0
 *
 * @param string $n number to clean.
 *
 * @return string
 */
function clean_number( $n ) {
	return str_replace( ',00', '', $n );
}

/**
 * Creates home thumbnail style
 *
 * @param string $img img for background.
 *
 * @return string
 */
function home_thumb( $img ) {

	// $img: 370x150

	$style = <<<STYLE
	background: url('$img') no-repeat center left #eae8e8; height: 150px; margin-bottom: 70px;
STYLE;

	return $style;
}


/**
 * Twig function specific for Diccionari multilingüe
 *
 * @param string
 *
 * @return string
 */
function print_definition( $def ) {
	$def = trim( $def );
	$pos = strpos( $def, '#' );

	if ( $pos === false ) {
		$result = ' - ' . $def;
	} else {
		$def      = str_replace( '#', '', $def );
		$entries  = explode( "\n", $def );
		$filtered = array_filter( array_map( 'trim_entries', $entries ) );
		$result   = ' - ' . implode( '<br />- ', $filtered ) . '<br />';
	}

	return $result;
}

function trim_entries( $entry ) {
	$trimmed = trim( $entry );

	return empty( $trimmed ) ? null : $trimmed;
}

function get_full_img_from_id( $img_id ) {
	$image = wp_get_attachment_image_src( $img_id, 'full' );

	return $image[0];
}
function get_img_from_id( $img_id ) {
	$image = wp_get_attachment_image_src( $img_id );

	return $image[0];
}

function get_img_id_from_url($image_url) {
	global $wpdb;
	$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ));
	return $attachment[0];
}

/**
 * This function retrieves the current url, either on http or https format
 * depending on the current navigation
 *
 * @return string $url
 */
function get_current_url( $remove = false ) {
	$current_url = ( isset( $_SERVER['HTTPS'] ) ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	if ( $remove ) {
		$current_url = remove_query_arg( $remove, $current_url );
	}

	return $current_url;
}

abstract class SearchQueryType {
	const All = 0;
	const FilteredDate = 1;
	const Search = 2;
	const Aparell = 4;
	const Post = 6;
	const PagePrograma = 7;
	const FilteredTema = 8;
}

/*
 * Returns the arguments to apply to the mysql query
 */
function get_post_query_args( $post_type, $queryType, $filter = array() ) {
	//Retrieve posts
	switch ( $post_type ) {
		case 'aparell':
			$base_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => - 1
			);
			break;
		case 'programa':
			$base_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'paged'          => get_is_paged(),
				'posts_per_page' => 18,
				'tax_query'      => array(
					array(
						'taxonomy' => 'classificacio',
						'field'    => 'slug',
						'terms'    => 'arxivat',
						'operator' => 'NOT IN'
					)
				)
			);
			break;
		case 'projecte':
			$base_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'paged'          => get_is_paged(),
				'posts_per_page' => 36,
				'tax_query'      => array(
					array(
						'taxonomy' => 'classificacio',
						'field'    => 'slug',
						'terms'    => 'arxivat',
						'operator' => 'NOT IN'
					)
				)
			);
			break;
		case 'page':
			$base_args = array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'order'       => 'ASC',
				'meta_query'  => array(
					get_meta_query_value( $filter['subpage_type'], $filter['post_id'], '=', 'NUMERIC' )
				)
			);
			break;
		case 'post':
			$base_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'order'          => 'DESC',
				'paged'          => get_is_paged(),
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
			's'          => $filter,
			'meta_query' => array(
				get_meta_query_value( 'data_fi', time(), '>=', 'NUMERIC' )
			)
		);
	} else if ( $queryType == SearchQueryType::FilteredDate ) {
		$filter_args = array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					get_meta_query_value( 'data_fi', $filter['start_time'], '>=', 'NUMERIC' )
				),
				array(
					get_meta_query_value( 'data_inici', $filter['final_time'], '<=', 'NUMERIC' )
				)
			)
		);
	} else if ( $queryType == SearchQueryType::Aparell ) {
		$filter_args = array();
		if ( ! empty ( $filter['s'] ) ) {
			$filter_args['s'] = $filter['s'];
		}

		if ( ! empty ( $filter['so_aparell'] ) ) {
			$filter_args['tax_query'][] = array(
				'taxonomy' => 'so_aparell',
				'field'    => 'slug',
				'terms'    => $filter['so_aparell']
			);
			$filter_args['filter_so']   = $filter['so_aparell'];
		}

		if ( ! empty ( $filter['tipus_aparell'] ) ) {
			$filter_args['tax_query'][]  = array(
				'taxonomy' => 'tipus_aparell',
				'field'    => 'slug',
				'terms'    => $filter['tipus_aparell']
			);
			$filter_args['filter_tipus'] = $filter['tipus_aparell'];
		}

		if ( ! empty ( $filter['fabricant'] ) ) {
			$filter_args['tax_query'][]      = array(
				'taxonomy' => 'fabricant',
				'field'    => 'slug',
				'terms'    => $filter['fabricant']
			);
			$filter_args['filter_fabricant'] = $filter['fabricant'];
		}
	} else if ( $queryType == SearchQueryType::FilteredTema ) {
		if ( ! empty ( $filter ) ) {
			$filter_args['tax_query'][] = array(
				'taxonomy' => 'esdeveniment_cat',
				'field'    => 'slug',
				'terms'    => $filter
			);
		}
	} else if ( $queryType == SearchQueryType::PagePrograma || $queryType == SearchQueryType::Projecte ) {
			$filter_args = array();
	} else {
		$filter_args = array(
			'meta_query' => array(
				get_meta_query_value( 'data_fi', time(), '>=', 'NUMERIC' )
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
		'value'   => $value,
		'compare' => $compare,
		'type'    => $type
	);
}

/*
 * Returns global paged variable
 */
function get_is_paged() {
	global $paged;

	return ( ! isset( $paged ) || ! $paged ) ? 1 : $paged;
}

/*
 * Function to handle the date filter for events
 */
function add_query_vars_filter( $vars ) {
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
	$vars[] = "verb";

	return $vars;
}

add_filter( 'query_vars', 'add_query_vars_filter' );

/*
 * Retrieve all url active parameters
 */
function get_current_querystring() {
	$output   = '';
	$firstRun = true;
	foreach ( $_GET as $key => $val ) {
		if ( ! $firstRun ) {
			$output .= "&";
		} else {
			$output   = "?";
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
	$url = substr( $url, 0, - 1 );

	return $url;
}

function get_filter_addition( $url ) {
	$pos = strpos( $url, '?' );
	if ( $pos === false ) {
		$addition = '?';
	} else {
		$addition = '&';
	}

	return $addition;
}

/*
 * Function that modifies the orderby query only for events in home page
 */
function orderbyreplace( $orderby ) {
	global $wpdb;

	return str_replace( $wpdb->prefix . 'postmeta.meta_value DESC', 'mt1.meta_value DESC, mt2.meta_value ASC', $orderby );
}

/*
 * Function that adds a excerpt to pages to be used as a subtitle
 */
add_action( 'init', 'sc_add_excerpts_to_pages' );
function sc_add_excerpts_to_pages() {
	add_post_type_support( 'page', 'excerpt' );
}

function sendEmailForm( $to_email, $nom_from, $assumpte, $fields ) {
	return sendEmailWithFromAndTo( $to_email, $to_email, $nom_from, $assumpte, $fields );
}

/*
 * General 'send email' function
 */
function sendEmailWithFromAndTo( $to_email, $from_email, $nom_from, $assumpte, $fields ) {
	//email body
	$message_body = '';
	foreach ( $fields as $key => $field ) {
		$message_body .= $key . ": " . $field . "\r\n\r";
	}

	//proceed with PHP email.
	$headers = 'From: ' . $nom_from . ' <' . $from_email . ">\r\n" .
	           'Reply-To: web@softcatala.org' . "\r\n" .
	           'X-Mailer: PHP/' . phpversion();

	$send_mail = wp_mail( $to_email, $assumpte, $message_body, $headers );

	if ( ! $send_mail ) {
		//If mail couldn't be sent output error. Check your PHP email configuration (if it ever happens)
		$output = json_encode( array( 'type' => 'error', 'text' => 'S\'ha produït un error en enviar el missatge.' ) );
	} else {
		$output = json_encode( array( 'type' => 'message', 'text' => 'S\'ha enviat la informació.' ) );
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
function cc_mime_types( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';

	return $mimes;
}

add_filter( 'upload_mimes', 'cc_mime_types' );

/**
 * Returns the user role for a user
 *
 * @param $author
 *
 * @return mixed
 */
function get_user_role( $author ) {
	$user       = get_user_by( 'id', $author->ID );
	$user_roles = $user->roles;
	$user_role  = array_shift( $user_roles );

	return $user_role;
}

/**
 * This function sets specific error headers for 404 and 500 error pages
 *
 * @param $code
 * @param $message
 */
function throw_error( $code, $message ) {
	if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
		header( "HTTP/1.1 " . $code . " " . $message );
	}

	if ( $code == 404 ) {
		global $wp_query;

		$wp_query->set_404();
	}
}

/**
 * This function executes an API call of the type 'rest' given a url with all the parameters in it
 *
 * @param $url
 *
 * @return mixed
 */
function do_json_api_call( $url ) {
	$api_call = wp_remote_get(
		$url,
		array(
			'method'  => 'GET',
			'timeout' => 5,
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		)
	);

	if ( is_wp_error( $api_call ) ) {
		$result = 'error';
	} else {
		if ( isset( $api_call['body'] ) && $api_call['body'] != '[]' ) {
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
 *
 * @return string
 */
function aparell_comment_redirect( $location ) {
	if ( isset( $_POST['redirect_page'] ) ) // Don't use "redirect_to", internal WP var
	{
		$location = esc_url( $_POST['redirect_page'] );
	}

	return $location;
}

add_filter( 'comment_post_redirect', 'aparell_comment_redirect' );

/**
 * Sets the program so depending on the downloads so
 *
 */
function align_downloads_programs_so( $post_id ) {
	$slug = 'programa';
	$post = get_post( $post_id );

	// If this isn't a 'book' post, don't update it.
	if ( $slug != $post->post_type ) {
		return;
	}

	$downloads = get_field( 'baixada' );

	if ( $downloads ) {
		foreach ( $downloads as $download ) {
			$id      = term_exists( $download['download_os'], 'sistema-operatiu-programa' );
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
function sc_responsive_image_sizes( $sizes, $size ) {

	$width = $size[0];

	// 1200, 1025, 769, 480

	// Let's assume we'll always have sidebar
	if ( $width > 870 ) {
		return '(max-width: 768px) 92vw, (max-width: 1024px) 738px, (max-width: 1200px) 870px, 870x';
	}

	return $sizes;
}

add_filter( 'wp_calculate_image_sizes', 'sc_responsive_image_sizes', 10, 2 );


/**
 * Generic function to inform SC about sections on the website not working properly
 *
 **/
function throw_service_error( $service, $message = '', $sinonims = false ) {

	global $sc_site;

	throw_error( '500', 'Error connecting to API server' );

	if ( $sinonims && $sc_site->get_setting_value( SC_Settings::SETTINGS_SEND_EMAILS_THESAURUS_ERRORS ) ) {
		return;
	}

	$fields['Hora'] = current_time( 'mysql' );
	if ( $message ) {
		$fields['Missatge'] = $message;
	}

	sendEmailForm( 'web@softcatala.org', $service, 'El servei «' . $service . '» no està funcionant correctament', $fields );
}

add_filter( 'rest_authentication_errors', 'sc_only_allow_logged_in_rest_access' );

function sc_only_allow_logged_in_rest_access( $access ) {

	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'rest_cannot_access', __( 'Only authenticated users can access the REST API.', 'disable-json-api' ), array( 'status' => rest_authorization_required_code() ) );
	}

	return $access;
}

add_filter( 'user_contactmethods', 'modify_user_contact_methods' );
function modify_user_contact_methods( $user_contact ) {

	// Add user contact methods
	$user_contact['public_email'] = 'Email públic';
	$user_contact['twitter']      = __( 'Twitter Username' );
	$user_contact['telegram']     = 'Usuari de Telegram';

	// Remove user contact methods
	unset( $user_contact['facebook'] );
	unset( $user_contact['googleplus'] );

	return $user_contact;
}

add_filter( 'pre_get_avatar_data', array('\Softcatala\Images\Avatar', 'filter'), 10, 2 );

function get_downloads_full() {

	$result = get_transient( 'downloads_full' );

	if ( false === $result ) {
		$result = json_decode(file_get_contents(ABSPATH.'../full.json'), true);
		set_transient( 'downloads_full', $result, 2 * HOUR_IN_SECONDS );
	}

	return $result;
}

function get_program_context( $programa ) {

	$context = Timber::get_context();

	$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
	$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
	$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
	$context['post'] = $programa;

	$context['arxivat'] = $programa->has_term('arxivat', 'classificacio');
	$context['credits'] = $programa->get_field( 'credits' );
	$baixades = $programa->get_field( 'baixada' );
	$context['baixades'] = generate_url_download( $baixades, $programa );

	//Contact Form
	$context['contact']['to_email'] = get_option('to_email_rebost');
	$context['contact']['from_email'] = get_option('email_rebost');

	//Add program form data
	$context['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
	$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
	$context['categories']['llicencies'] = Timber::get_terms('llicencia');

	//Download count
	$download_full = get_downloads_full();
	if( $download_full ) {
		$wordpress_ids_column = array_column($download_full, 'wordpress_id');
		if( $wordpress_ids_column ) {
			$index = array_search( $programa->ID, $wordpress_ids_column);
			if ( $index ) {
				$context['total_downloads'] = $download_full[$index]['total'];
			}
		}
	}

	$logo = get_img_from_id( $programa->logotip_programa );
	$context['logotip'] = $logo;

	$yoastlogo = get_the_post_thumbnail_url() ?: $logo;

	$custom_logo_filter = function ($img) use($yoastlogo) {
		return $yoastlogo;
	};

	add_filter( 'wpseo_twitter_image', $custom_logo_filter);
	add_filter( 'wpseo_opengraph_image', $custom_logo_filter);

	$query = array ( 'post_id' => $programa->ID , 'subpage_type' => 'programa' );
	$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );

	query_posts($args);

	$context['related_pages'] = Timber::get_posts($args);

	$project_id = get_post_meta( $programa->ID, 'projecte_relacionat', true );

	if( $project_id ) {
		$context['projecte_relacionat_url'] = get_permalink($project_id);
		$context['projecte_relacionat_name'] =  get_the_title($project_id);
	}

	return $context;
}

function sc_add_excerpt_meta_box( $post_type ) {
	add_meta_box(
		'postexcerpt',
		__( 'Excerpt' ),
		'post_excerpt_meta_box',
		$post_type,
		'sc', // change to something other then normal, advanced or side
		'high'
	);
}
add_action( 'add_meta_boxes', 'sc_add_excerpt_meta_box' );

function sc_run_excerpt_meta_box() {
	# Get the globals:
	global $post;

	# Output the "advanced" meta boxes:
	do_meta_boxes( get_current_screen(), 'sc', $post );
}

add_action( 'edit_form_after_title', 'sc_run_excerpt_meta_box' );

function sc_remove_normal_excerpt() { /*this added on my own*/
	remove_meta_box( 'postexcerpt' , 'post' , 'normal' );
}
add_action( 'admin_menu' , 'sc_remove_normal_excerpt' );

