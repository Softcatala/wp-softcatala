<?php
/**
 * Template Name: Aparells
 *
 * @package wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-aparells', get_template_directory_uri() . '/static/js/aparells.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-aparells', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$timberPost = new TimberPost();
$context_holder['post'] = $timberPost;
$parent_data = get_page_parent_title( $timberPost->ID );
$context_holder['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context_holder['links'] = $timberPost->get_field( 'link' );
$context_holder['credits'] = $timberPost->get_field( 'credit' );
$context_holder['parent_title'] = $parent_data['title'];
$context_holder['page_hierarchy'] = wp_list_subpages($parent_data['id']);
//Stats data
$json_path = ABSPATH."../aparells.json";
$context_holder['stats_aparells'] = json_decode( file_get_contents( $json_path ) );

//Filters population
$context_holder['categories']['sistemesoperatius'] = Timber::get_terms('so_aparell');
$context_holder['categories']['tipus'] = Timber::get_terms('tipus_aparell');
$context_holder['categories']['fabricants'] = Timber::get_terms('fabricant');

//Search and filters
$search = get_query_var('cerca');
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$tipus_aparell = get_query_var( 'tipus_aparell' );
$fabricant = get_query_var( 'fabricant' );

//Generate $args query
if( ! empty( $search ) || ! empty( $sistema_operatiu ) || ! empty( $tipus_aparell ) || ! empty( $fabricant ) ) {
    $query_aparell['s'] = $search;
    $query_aparell['so_aparell'] = $sistema_operatiu;
    $query_aparell['tipus_aparell'] = $tipus_aparell;
    $query_aparell['fabricant'] = $fabricant;
    $args = get_post_query_args( 'aparell', SearchQueryType::Aparell, $query_aparell );

    $title = 'Aparells - ';
    (!empty( $search ) ? $title .= 'cerca: ' . $search . ' - ' : '');
    (!empty( $tipus_aparell ) ? $title .= 'tipus: ' . get_term_name_by_slug ($tipus_aparell , 'tipus_aparell' ) . ' - ' : '');
    (!empty( $sistema_operatiu ) ? $title .= 'sistema operatiu: ' . get_term_name_by_slug ($sistema_operatiu , 'so_aparell' ) . ' - ' : '');
    (!empty( $fabricant ) ? $title .= 'fabricant: ' . get_term_name_by_slug ($fabricant , 'fabricant' ) . ' - ' : '');
    $title .= 'Softcatalà';
} else {
    $title = 'Aparells - Softcatalà';
    $args = array( 'post_type' => 'aparell', 'posts_per_page' => -1, 'order' => 'ASC' );
}

$context_holder['cerca'] = $search;
$context_holder['selected_filter_so'] = ( isset ( $args['filter_so'] ) ? $args['filter_so'] : '' );
$context_holder['selected_filter_tipus'] = ( isset ( $args['filter_tipus'] ) ? $args['filter_tipus'] : '' );
$context_holder['selected_filter_fabricant'] = ( isset ( $args['filter_fabricant'] ) ? $args['filter_fabricant'] : '' );

//Posts and pagination
$context_holder['aparells'] = Timber::get_posts( $args );

//Context initialization
$templates = array('aparells.twig' );
$description = 'Guia col·laborativa on podeu consultar i documentar els aparells (mòbils, tauletes, lectors de llibres electrònics...) que es poden configurar en català"';
$context_filterer = new SC_ContextFilterer( $context_holder );
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

$context['reverse_comments'] = true;

Timber::render( $templates, $context );
