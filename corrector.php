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

// ?corrector=alpha loads the build bind-mounted at /alpha/corrector/ by web-softcatala, when present.
$corrector_alpha_dir = dirname( ABSPATH ) . '/alpha/corrector';
if ( 'alpha' === ( $_GET['corrector'] ?? '' ) && file_exists( $corrector_alpha_dir . '/corrector.js' ) ) {
	$corrector_js_uri  = home_url( '/alpha/corrector' );
	$corrector_css_uri = $corrector_js_uri;
	$corrector_version = (string) filemtime( $corrector_alpha_dir . '/corrector.js' );
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
