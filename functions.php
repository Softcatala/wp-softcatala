<?php

define( 'WP_SOFTCATALA_VERSION', '2.0.19' );

if( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
} else if( file_exists( ABSPATH . '/wp-content/vendor/autoload.php')) {
	require ABSPATH . '/wp-content/vendor/autoload.php';
} else if( file_exists( ABSPATH . '/vendor/autoload.php' ) ) {
	require ABSPATH . '/vendor/autoload.php';
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

\Timber\Timber::init();

include( 'inc/perfils.php' );
include( 'inc/tasques.php' );
include( 'inc/diccionari.php' );
include( 'inc/downloads.php' );
include( 'inc/query.php' );
include( 'inc/email.php' );
include( 'rest/downloads-api.php' );
include( 'rest/projectes-csv-api.php' );
include( 'rest/tasques-api.php' );



Timber::$dirname = array( 'templates', 'views' );

class StarterSite extends \Timber\Site {

	function __construct() {
		if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'menus' );
			add_theme_support( 'title-tag' );
		}


		add_filter( 'timber_context', array( $this, 'add_user_nav_info_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
		add_filter( 'xv_planeta_feed', '__return_true' );
		
		// Register REST API endpoints for downloads updater
		add_action( 'rest_api_init', 'sc_register_downloads_api' );
		add_action( 'rest_api_init', 'sc_register_projectes_api' );
		add_action( 'rest_api_init', 'sc_register_tasques_api' );
		
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
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
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

		// Task management: redirect anonymous users away from individual task permalinks.
		add_action( 'template_redirect', array( $this, 'sc_redirect_tasca_to_login' ) );

		// Visibility: redirect anonymous users away from individual internal projecte pages.
		add_action( 'template_redirect', array( $this, 'sc_redirect_internal_projecte_to_login' ) );

		// Visibility: exclude internal projectes from WP search for anonymous visitors.
		add_action( 'pre_get_posts', array( $this, 'sc_exclude_internal_projectes_from_search' ) );

		// Visibility: exclude internal projectes from Yoast SEO sitemaps.
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'sc_exclude_internal_projectes_from_sitemap' ) );

		// Task management: seed default estat_tasca terms on theme activation.
		add_action( 'after_switch_theme', 'sc_seed_estat_tasca' );

		// Task management: prevent deletion of estat_tasca terms with assigned tasks.
		add_filter( 'pre_delete_term', 'sc_guard_estat_tasca_delete', 10, 2 );

		// Task management: register term meta and admin UI for estat_tasca order.
		add_action( 'init', 'sc_register_estat_tasca_order_meta' );
		add_action( 'estat_tasca_add_form_fields', 'sc_estat_tasca_add_order_field' );
		add_action( 'estat_tasca_edit_form_fields', 'sc_estat_tasca_edit_order_field', 10, 2 );
		add_action( 'created_estat_tasca', 'sc_save_estat_tasca_order_meta' );
		add_action( 'edited_estat_tasca', 'sc_save_estat_tasca_order_meta' );
		add_filter( 'manage_edit-estat_tasca_columns', 'sc_estat_tasca_order_column' );
		add_filter( 'manage_estat_tasca_custom_column', 'sc_estat_tasca_order_column_content', 10, 3 );

		// Task management: invalidate internal-projecte transient when tasques_internes changes.
		add_action( 'save_post_projecte', 'sc_invalidate_internal_projecte_ids_transient', 10, 2 );

		// Task management: invalidate internal-tasca transient when tasca_interna changes.
		add_action( 'save_post_tasca', 'sc_invalidate_internal_tasca_ids_transient', 10, 2 );

		// Task management: restrict milestone_tasca ACF field to milestones of the selected projecte.
		add_filter( 'acf/fields/post_object/query/name=milestone_tasca', 'sc_filter_milestone_tasca_by_projecte', 10, 3 );

		// Task management: register 'archived' post status and wire admin UI for it.
		add_action( 'init', 'sc_register_archived_post_status' );
		add_action( 'post_submitbox_misc_actions', 'sc_inject_archived_status_in_editor' );
		add_filter( 'bulk_actions-edit-tasca', 'sc_add_archive_tasca_bulk_action' );
		add_filter( 'handle_bulk_actions-edit-tasca', 'sc_bulk_archive_tasques', 10, 3 );
		add_action( 'admin_notices', 'sc_archived_tasques_admin_notice' );

		add_action(
			'wp',
			function () {
				$queried_object = get_queried_object();
				if (
					isset( $queried_object->post_status ) &&
					'private' === $queried_object->post_status &&
					! is_user_logged_in()
				) {
					wp_safe_redirect( wp_login_url( get_permalink( $queried_object->ID ) ) );
					exit;
				}
			}
		);

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
		SC_NavegaEnCatala::init();
		\Softcatala\Content\JsonToTable::init();
		SC_Sitemaps::init();
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
		load_theme_textdomain('softcatala', get_template_directory() . '/languages');

		// ACF Local JSON: only active outside production.
		// In production, field groups are loaded from the DB (cached via object cache).
		// Locally and in dev, JSON files are read and written so changes are tracked in git.
		if ( !defined( 'WP_ENV' ) || 'production' !== WP_ENV ) {
			add_filter(
				'acf/settings/load_json',
				function ( $paths ) {
					$paths[] = get_stylesheet_directory() . '/acf-json';
					return $paths;
				}
			);
			add_filter(
				'acf/settings/save_json',
				function () {
					return get_stylesheet_directory() . '/acf-json';
				}
			);
		}
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
	 * Configure SMTP settings for email delivery
	 * 
	 * @param PHPMailer $phpmailer The PHPMailer instance
	 */
	function configure_smtp( $phpmailer ) {

		if ( ! defined( 'SMTP_HOST' ) ) {
			return;
		}

		$phpmailer->IsSMTP();

		$phpmailer->Host = SMTP_HOST;

		$phpmailer->Port = defined( 'SMTP_PORT' ) ? SMTP_PORT : 465;

		if ( defined( 'SMTP_USER' ) && defined( 'SMTP_PASS' ) ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = SMTP_USER;
			$phpmailer->Password = SMTP_PASS;
		}

		if ( defined( 'SMTP_SECURE' ) ) {
			$phpmailer->SMTPSecure = SMTP_SECURE;
		} else {
			$phpmailer->SMTPSecure = 'ssl';
		}

		if ( empty( $phpmailer->From ) && defined( 'SMTP_FROM' ) ) {
			$phpmailer->From = SMTP_FROM;
		}

		if ( empty( $phpmailer->FromName ) ) {
			if ( defined( 'SMTP_FROM_NAME' ) ) {
				$phpmailer->FromName = SMTP_FROM_NAME;
			} else {
				$phpmailer->FromName = get_bloginfo( 'name' );
			}
		}

		if ( empty( $phpmailer->getReplyToAddresses() ) && defined( 'SMTP_REPLY_TO' ) ) {
			$phpmailer->addReplyTo( SMTP_REPLY_TO );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$phpmailer->SMTPDebug = 2;
			$phpmailer->Debugoutput = 'error_log';
		}
	}

	/**
	 * Custom Softcatalà settings
	 */
	function include_sc_settings() {
		register_setting( 'softcatala-group', 'llistes_access' );
		register_setting( 'softcatala-group', 'api_diccionari_sinonims' );
		register_setting( 'softcatala-group', 'api_conjugador' );
		register_setting( 'softcatala-group', 'api_memory_base' );
		register_setting( 'softcatala-group', 'api_diccionari_engcat' );
		register_setting( 'softcatala-group', 'api_cerca_corpus' );
		register_setting( 'softcatala-group', 'catalanitzador_post_id' );
		register_setting( 'softcatala-group', 'aparells_post_id' );
		register_setting( 'softcatala-group', 'sc_text_programes' );
		register_setting( 'softcatala-group', 'api_languagetool' );

		add_option('api_languagetool', 'https://api.softcatala.org/corrector/v2/check');

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
			// Required for capability_type 'post' on the tasca CPT.
			$role->add_cap( 'edit_posts' );
			$role->add_cap( 'edit_published_posts' );
		}
	}

	function get_email_sections() {
		$sections = array(
			'general'   => 'General',
			'traductor_neuronal' => 'Traductor Neuronal',
			'traductor' => 'Traductor',
			'corrector' => 'Corrector',
			'recursos'  => 'Recursos',
			'rebost'    => 'Programes',
			'sinonims'  => 'Sinonims'
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
		\Softcatala\TypeRegisters\DadesObertes::get_instance();
		\Softcatala\TypeRegisters\Tasca::get_instance();
		\Softcatala\TypeRegisters\Milestone::get_instance();
	}

	/**
	 * Redirect anonymous users away from individual tasca post permalinks to the login page.
	 * The /tasques/ archive remains publicly accessible.
	 */
	function sc_redirect_tasca_to_login() {
		if ( is_singular( 'tasca' ) && ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( get_permalink() ) );
			exit;
		}
	}

	/**
	 * Redirect anonymous users away from individual internal-projecte pages to the login page.
	 * Projects with projecte_intern = true are invisible to anonymous visitors; accessing
	 * the URL directly results in a 302 redirect to the login page.
	 */
	function sc_redirect_internal_projecte_to_login() {
		if ( is_singular( 'projecte' ) && ! is_user_logged_in() ) {
			$projecte_id = get_queried_object_id();
			if ( get_field( 'projecte_intern', $projecte_id ) ) {
				wp_safe_redirect( wp_login_url( get_permalink() ) );
				exit;
			}
		}
	}

	/**
	 * Exclude internal projects from WP search results for anonymous visitors.
	 *
	 * Fires on pre_get_posts; only modifies the main search query for non-logged-in users.
	 *
	 * @param \WP_Query $query The WP_Query object.
	 */
	function sc_exclude_internal_projectes_from_search( $query ) {
		if ( ! $query->is_main_query() || ! $query->is_search() || is_user_logged_in() ) {
			return;
		}

		// Use a two-arm OR so pre-existing posts with no meta row are treated as public.
		$existing_meta_query = $query->get( 'meta_query' );
		$internal_exclusion  = array(
			'relation' => 'OR',
			array(
				'key'     => 'projecte_intern',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'projecte_intern',
				'value'   => '1',
				'compare' => '!=',
			),
		);

		if ( ! empty( $existing_meta_query ) ) {
			$query->set(
				'meta_query',
				array(
					'relation'  => 'AND',
					$existing_meta_query,
					$internal_exclusion,
				)
			);
		} else {
			$query->set( 'meta_query', $internal_exclusion );
		}
	}

	/**
	 * Exclude internal projects from Yoast SEO XML sitemaps.
	 *
	 * @param int[] $excluded_ids Currently excluded post IDs.
	 * @return int[] Extended exclusion list.
	 */
	function sc_exclude_internal_projectes_from_sitemap( $excluded_ids ) {
		$internal_ids = \Softcatala\Providers\Tasques::get_internal_projecte_ids();
		if ( ! empty( $internal_ids ) ) {
			$excluded_ids = array_unique( array_merge( (array) $excluded_ids, $internal_ids ) );
		}
		return $excluded_ids;
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
		$twig->addExtension( new \Twig\Extension\StringLoaderExtension() );
		$twig->addFilter( new \Twig\TwigFilter( 'dump', 'sc_dump' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'get_caption_from_media_url', 'get_caption_from_media_url' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'get_img_from_id', 'get_img_from_id' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'get_full_img_from_id', 'get_full_img_from_id' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'truncate_words', 'sc_truncate_words' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'print_definition', 'print_definition' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'clean_number', 'clean_number' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'home_thumb', 'home_thumb' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'safe_batch', array( 'SC_Twig_Filters', 'safe_batch' ) ) );
		/* Diccionari eng cat functions */
		$twig->addFilter( new \Twig\TwigFilter( 'fullGrammarTag', 'fullGrammarTag' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'prepareLemmaHeading', 'prepareLemmaHeading' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'prepareSubLemma', 'prepareSubLemma' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'prepareWord', 'prepareWord' ) );
		$twig->addFilter( new \Twig\TwigFilter( 'presentFeminine', 'presentFeminine' ) );

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
	wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.min.js' ), false, null, true );

	wp_register_script( 'sc-js-metacookie', get_template_directory_uri() . '/static/js/jquery.metacookie.js', array( 'jquery' ), '20210928', true );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_style( 'sc-css-main', get_template_directory_uri() . '/static/css/main.min.css', array(), WP_SOFTCATALA_VERSION );
	wp_enqueue_script( 'sc-js-main', get_template_directory_uri() . '/static/js/main.min.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
	$sc_site->register_ui_settings();
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
 * Twig function to dump a variable for debugging
 */
function sc_dump($string) {
	echo '<pre>';
	var_dump($string);
	echo '</pre>';
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

function get_breadcrumbs( $timberPost, $force = false ) {
	$ancestors = array_map( array( 'Timber', 'get_post' ), array_reverse( get_ancestors( $timberPost->id, 'page' ) ) );

	$breadcrumbs = false;

	if ( sizeof( $ancestors ) > 0 && ( show_breadcrumbs( $ancestors[0]->slug, $force ) ) ) {
		$breadcrumbs = array_map(
			function ( $p ) {
				return array( 'link' => $p->link, 'text' => $p->title );
			},
			array_merge( $ancestors, array( $timberPost ) )
		);
	}

	return $breadcrumbs;
}

function show_breadcrumbs( $slug, $force ) {
	return in_array( $slug, array( 'trobades' ) ) || $force;
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



