<?php
/**
 * Template Name: Corrector BETA
 *
 * @package wp-softcatala
 */

/* JS scripts */
$deps = array('sc-js-main', 'sc-js-corrector-vite-client');

wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-vite-client', get_template_directory_uri() . '/static/js/corrector/client.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-vite-corrector', get_template_directory_uri() . '/static/js/corrector/corrector.js', $deps, WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-vite-paraphrase', get_template_directory_uri() . '/static/js/corrector/paraphrase.js', $deps, WP_SOFTCATALA_VERSION, true );

wp_enqueue_style( 'sc-css-corrector-vite', get_template_directory_uri() . '/static/css/corrector/client.css', array(), WP_SOFTCATALA_VERSION );
wp_enqueue_style( 'sc-css-corrector-vite', get_template_directory_uri() . '/static/css/corrector/main.css', array(), WP_SOFTCATALA_VERSION );

add_filter("script_loader_tag", "add_module_to_my_script", 10, 3);
function add_module_to_my_script($tag, $handle, $src)
{
    if (strpos( $handle, "sc-js-corrector-vite" ) !== false) {
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
    }

    return $tag;
}


wp_localize_script( 'sc-js-corrector-1', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
$context['api_languagetool'] = get_option('api_languagetool');
$settings = SC_Settings::get_instance();
$context['corrector_send_sessionid'] = $settings->get_setting(SC_Settings::SETTINGS_CORRECTOR_SEND_SESSIONID);

//Ads
$context['ads_container'] = true;
$timberPost = new TimberPost();
$context['post'] = $timberPost;
$context['credits'] = $timberPost->get_field( 'credits' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_corrector');

Timber::render( array( 'corrector-beta.twig' ), $context );
