<?php
/**
 * Template Name: Corrector antic Softcatala
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-1', get_template_directory_uri() . '/inc/languagetool/online-check/tiny_mce/tiny_mce.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-2', get_template_directory_uri() . '/inc/languagetool/online-check/tiny_mce/plugins/atd-tinymce/editor_plugin.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-3', get_template_directory_uri() . '/inc/languagetool/js/ZeroClipboard.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-4', get_template_directory_uri() . '/static/js/clipboard.min.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );

wp_enqueue_script( 'sc-js-corrector-5', get_template_directory_uri() . '/static/js/languagetool.js', array('sc-js-metacookie'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-corrector-6', get_template_directory_uri() . '/static/js/jquery.fancybox-2.1.5.pack.js', array('sc-js-main'), '2.1.5', true );
wp_enqueue_style( 'sc-css-corrector', get_template_directory_uri() . '/static/css/languagetool.css', array(), WP_SOFTCATALA_VERSION );
wp_enqueue_style( 'sc-css-corrector-stats', get_template_directory_uri() . '/static/css/corrector.css', array(), WP_SOFTCATALA_VERSION );
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
$context['sidebar_elements'] = array( 'static/corrector-stats.twig', 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_corrector');

Timber::render( array( 'corrector-antic.twig' ), $context );
