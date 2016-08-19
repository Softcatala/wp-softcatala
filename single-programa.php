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
$post_links = types_child_posts('link', $post->ID);
$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );
$baixades = $post->get_field( 'baixada' );

//Contact Form
$context['contact']['to_email'] = get_option('email_rebost');


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

$context['reverse_comments'] = true;

$context['baixades'] = generate_url_download( $baixades, $post );

$query = array ( 'post_id' => $post->ID , 'subpage_type' => 'programa' );
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);
$project_id = wpcf_pr_post_get_belongs($post->ID, 'projecte');
if( $project_id ) {
    $context['projecte_relacionat_url'] = get_permalink($project_id);
    $context['projecte_relacionat_name'] =  get_the_title($project_id);
}

if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
