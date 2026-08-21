<?php
/**
 * Archive page for programa custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page
use Softcatala\Providers\Programes;

wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array(), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$cpt_programa = \Softcatala\TypeRegisters\Programa::get_instance();

//Template initialization
$templates = array( 'archive-programa.twig' );

$post = $cpt_programa->get_page();
$post ? $context_holder['links'] = $post->meta( 'link' ) : '';
$context_holder['post'] = $post;
$context_holder['content_title'] = 'Programes i aplicacions';
$context_holder['post_type'] = $cpt_programa->singular;
$context_holder['conditions_text'] = $cpt_programa->condicions_afegir_programa();
$context_holder['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context_holder['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context_holder['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );

//Search and filters
$search = urldecode( get_query_var( 'cerca' ));
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$categoria_programa = get_query_var( 'categoria_programa' );
$arxivats = 'arxivat' === get_query_var( 'classificacio' );

//Generate $args query
$flag_search = false;
$title = 'Programes - Softcatalà';
$content_title = 'Programes';
$description = 'Descobreix programes i aplicacions disponibles en català. Filtra’ls per categoria, sistema operatiu o llicència.';

$query = array();

if ( $arxivats ) {
	$query['classificacio'] = 'arxivat';
	$title = 'Programes històrics de Softcatalà | Softcatalà';
	$content_title = 'Programes històrics';
	$description = 'Consulta els programes en català que Softcatalà conserva com a referència històrica i que ja no es mantenen activament.';
}

if( ! empty( $search ) || ! empty( $categoria_programa ) || ! empty( $sistema_operatiu ) ) {
    $flag_search = true;
    $query['s'] = $search;
    $query['categoria-programa'] = $categoria_programa;
    $query['sistema-operatiu-programa'] = $sistema_operatiu;

    //Selected values
    $context_holder['cerca'] = $search;
    $context_holder['selected_filter_categoria'] = ( isset ( $query['categoria-programa'] ) ? $query['categoria-programa'] : '' );
    $context_holder['selected_filter_so'] = ( isset ( $query['sistema-operatiu-programa'] ) ? $query['sistema-operatiu-programa'] : '' );

    $title = 'Programes - ';
    (!empty( $search ) ? $title .= 'cerca: ' . $search . ' - ' : '');
    (!empty( $categoria_programa ) ? $title .= 'categoria: ' . $categoria_programa . ' - ' : '');
    (!empty( $sistema_operatiu ) ? $title .= 'sistema operatiu: ' . $sistema_operatiu . ' - ' : '');
    $title .= 'Softcatalà';

	$heading_filters = array();
	if ( ! empty( $search ) ) {
		$heading_filters[] = 'cerca «' . $search . '»';
	}
	if ( ! empty( $categoria_programa ) ) {
		$category_term = get_term_by( 'slug', $categoria_programa, 'categoria-programa' );
		$heading_filters[] = 'categoria «' . ( $category_term ? $category_term->name : $categoria_programa ) . '»';
	}
	if ( ! empty( $sistema_operatiu ) ) {
		$os_term = get_term_by( 'slug', $sistema_operatiu, 'sistema-operatiu-programa' );
		$heading_filters[] = 'sistema ' . ( $os_term ? $os_term->name : $sistema_operatiu );
	}

	$content_title = 'Programes: ' . implode( ', ', $heading_filters );
	$description = 'Resultats del catàleg de programes i aplicacions en català filtrats per ' . implode( ', ', $heading_filters ) . '.';
}

$context_holder['content_title'] = $content_title;
$context_holder['arxivat'] = $arxivats;
$context_holder['posts'] = Programes::get_sorted( $query );

if (count($context_holder['posts']) == 0 && $flag_search == true ) {
    throw_error( '404', 'No programs found' );
}

$context_holder['categories'] = Programes::get_filters( $query );

//Context initialization
$context_filterer = new SC_ContextFilterer( $context_holder );
$context_overrides = array(
	'title'       => $title,
	'description' => $description,
	'canonical'   => $arxivats ? home_url( '/programes/arxivats/' ) : '',
);
$context = $context_filterer->get_filtered_context( $context_overrides, false );

Timber::render( $templates, $context );
