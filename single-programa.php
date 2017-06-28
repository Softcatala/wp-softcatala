<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'jquery-browser-plugin', get_template_directory_uri() . '/static/js/jquery.browser.min.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main', 'jquery-browser-plugin'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
$post = Timber::query_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $post;
$context['arxivat'] = $post->has_term('arxivat', 'classificacio');

$context['credits'] = $post->get_field( 'credits' );
$baixades = $post->get_field( 'baixada' );

//Contact Form
$context['contact']['to_email'] = get_option('to_email_rebost');
$context['contact']['from_email'] = get_option('email_rebost');

//Add program form data
$context['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
$context['categories']['llicencies'] = Timber::get_terms('llicencia');

//Download count
$download_full = json_decode(file_get_contents(ABSPATH.'../full.json'), true);
if( $download_full ) {
    $wordpress_ids_column = array_column($download_full, 'wordpress_id');
    if( $wordpress_ids_column ) {
        $index = array_search($post->ID, $wordpress_ids_column);
        if ( $index ) {
            $context['total_downloads'] = $download_full[$index]['total'];
        }
    }
}

$logo = get_img_from_id( $post->logotip_programa );
$context['logotip'] = $logo;

$yoastlogo = get_the_post_thumbnail_url() ?: $logo;

$custom_logo_filter = function ($img) use($yoastlogo) {
	return $yoastlogo;
};

add_filter( 'wpseo_twitter_image', $custom_logo_filter);
add_filter( 'wpseo_opengraph_image', $custom_logo_filter);

$context['reverse_comments'] = true;

$context['baixades'] = generate_url_download( $baixades, $post );

$query = array ( 'post_id' => $post->ID , 'subpage_type' => 'programa' );
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );

query_posts($args);

$context['related_pages'] = Timber::get_posts($args);

$project_id = get_post_meta( $post->ID, 'projecte_relacionat', true );

if( $project_id ) {
    $context['projecte_relacionat_url'] = get_permalink($project_id);
    $context['projecte_relacionat_name'] =  get_the_title($project_id);
}

Timber::render( 'single-programa.twig', $context );
