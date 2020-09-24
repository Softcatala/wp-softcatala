<?php
/**
 * The template for displaying Archive pages, for native and custom post_types
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */

$templates = array( 'archive-podcast.twig' );

$title = 'Els Podcasts de Softcatalà';
$description = 'Tots els podcasts que, des de Softcatalà, gravem, editem i distribuïm per contribuir a la difusió del català a Internet.';
$post = Timber::get_post();

//Context initialization
$context_filterer = new SC_ContextFilterer();
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

$terms = Timber::get_terms('podcast-programa');
$programes = [];
foreach ($terms as $term) {
    $programes[] = new XVPodcastModel($term->slug, $term);
}
$context['programes'] =

Timber::render( $templates, $context );

