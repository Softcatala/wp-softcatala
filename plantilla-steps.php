<?php
/**
 * Template Name: Plantilla Steps Softcatala
 *
 * @package wp-softcatala
 */

//JS and Styles related to the page
use Softcatala\Providers\Projectes;

wp_enqueue_script( 'sc-js-steps', get_template_directory_uri() . '/static/js/steps.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-steps', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$templates = array('plantilla-steps.twig' );

//Project
$project_slug = get_query_var( 'project' );
if ( ! empty ( $project_slug ) ) {
    $projecte = get_page_by_path( $project_slug , OBJECT, 'projecte' );
    $projecte = new TimberPost($projecte->ID);
    $content_title = 'Col·laboreu en el projecte '. $projecte->post_title;
    $projecte->project_requirements = apply_filters('the_content', $projecte->project_requirements);
    $projecte->lectures_recomanades = apply_filters('the_content', $projecte->lectures_recomanades);

    $context_filterer = new SC_ContextFilterer();
    $context = $context_filterer->get_filtered_context( array('title' => $content_title . '| Softcatalà' ) );

    $context['projecte'] = $projecte;
    $context['steps'] = $projecte->get_field( 'steps' );
    $templates = array('plantilla-steps-single.twig' );

    $args = array(
        'meta_query' => array(
            get_meta_query_value('projectes', $projecte->ID, 'like', '')
        )
    );

    $context['membres'] = get_users( $args );
} else {
	$post = Timber::get_post();

	$profile = $post->get_field('perfil');
	$profile_label = get_field_object('perfil', $post->ID);
	$all_acf_data = get_field_objects($post->ID);

	$content_title = 'Col·laboreu amb nosaltres: ' . $profile_label['choices'][$profile];

	if ( false === $profile || empty($profile)) {
		wp_redirect( '/col·laboreu/', 302 );
	}

	$project_args = array(
		'tax_query' => array(
			array(
				'taxonomy' => 'ajuda-projecte',
				'field'    => 'slug',
				'terms'    => $profile
			),
			array (
					'taxonomy' => 'classificacio',
					'field' => 'slug',
					'terms' => 'arxivat',
					'operator'  => 'NOT IN'
			)
		)
    );

	$projects = Projectes::get_sorted_projects( $project_args );

    $context = Timber::get_context();

	$context['projectes'] = $projects;

    $context['steps'] = $post->get_field( 'steps' );

    $context['telegram'] = get_telegram_group_for_profile( $profile );
}

$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = $content_title;
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
Timber::render( $templates, $context );
