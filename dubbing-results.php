<?php
/**
 * Template Name: Dubbing-results
 *
 * @package wp-softcatala
 */
header('Cross-Origin-Embedder-Policy: require-corp');
header('Cross-Origin-Opener-Policy: same-origin');

wp_enqueue_script( 'sc-js-dubbing-results', get_template_directory_uri() . '/static/js/dubbing-results.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-subdub-editor', get_template_directory_uri() . '/static/js/subdub-editor.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_style( 'sc-css-dubbing-results', get_template_directory_uri() . '/static/css/dubbing.css', array('sc-css-main'),WP_SOFTCATALA_VERSION );

add_filter("script_loader_tag", "add_module_to_subdub_editor", 10, 3);
function add_module_to_subdub_editor($tag, $handle, $src)
{
    if (strpos( $handle, "sc-js-subdub-editor" ) !== false) {
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
    }

    return $tag;
}

$context = Timber::context();
$context['ads_container'] = true;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
$context['post'] = Timber::get_post();
Timber::render( array( 'dubbing-results.twig' ), $context );

