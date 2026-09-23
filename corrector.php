<?php
/**
 * Template Name: Corrector nou
 *
 * @package wp-softcatala
 */

/* JS scripts */
// corrector.js is an ES module that imports its client-<hash>.js chunk itself;
// there is no unhashed client.js to enqueue since the corrector build stopped emitting one.
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );

// Builds come from web-softcatala at /_apps/corrector/: stable/ is baked into the image,
// versions/<slug>/ is a bind mount that corrector CI publishes branches to, picked with ?corrector=<slug>.
$corrector_apps_dir = dirname( ABSPATH ) . '/_apps/corrector';
$corrector_request  = isset( $_GET['corrector'] ) && is_string( $_GET['corrector'] ) ? $_GET['corrector'] : '';
if ( preg_match( '/^[a-z0-9-]{1,63}$/', $corrector_request ) && is_file( "$corrector_apps_dir/versions/$corrector_request/corrector.js" ) ) {
	$corrector_js_uri  = home_url( "/_apps/corrector/versions/$corrector_request" );
	$corrector_css_uri = $corrector_js_uri;
	$corrector_version = (string) filemtime( "$corrector_apps_dir/versions/$corrector_request/corrector.js" );
} elseif ( is_file( "$corrector_apps_dir/stable/corrector.js" ) ) {
	$corrector_js_uri  = home_url( '/_apps/corrector/stable' );
	$corrector_css_uri = $corrector_js_uri;
	$corrector_version = trim( (string) @file_get_contents( "$corrector_apps_dir/stable/VERSION" ) ) ?: WP_SOFTCATALA_VERSION;
} else {
	$corrector_js_uri  = get_template_directory_uri() . '/static/js/corrector';
	$corrector_css_uri = get_template_directory_uri() . '/static/css/corrector';
	$corrector_version = WP_SOFTCATALA_VERSION;
}

wp_enqueue_script( 'sc-js-corrector-vite-corrector', $corrector_js_uri . '/corrector.js', array(), $corrector_version, true );
#wp_enqueue_script( 'sc-js-corrector-vite-paraphrase', $corrector_js_uri . '/paraphrase.js', array(), $corrector_version, true );

wp_enqueue_style( 'sc-css-corrector-vite-client', $corrector_css_uri . '/client.css', array(), $corrector_version );
wp_enqueue_style( 'sc-css-corrector-vite-main', $corrector_css_uri . '/corrector.css', array(), $corrector_version );




$context = Timber::context();
$context['api_languagetool'] = get_option('api_languagetool');
$settings = SC_Settings::get_instance();
$context['corrector_send_sessionid'] = $settings->get_setting(SC_Settings::SETTINGS_CORRECTOR_SEND_SESSIONID);

//Ads
$context['ads_container'] = true;
$timberPost = Timber::get_post();
$context['post'] = $timberPost;
$context['credits'] = $timberPost->meta( 'credits' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['destinatari'] = 'corrector';

Timber::render( array( 'corrector-nou.twig' ), $context );
