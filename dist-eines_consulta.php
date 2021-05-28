<?php
/**
 * Template Name: Dist - eines consulta lingüística
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$context['post'] = new TimberPost();
$context['links'] = array (
  array (
    'link_title' => 'Traductor',
    'link_url' => '/traductor',
    'link_description' => 'Traductor català / valencià - castellà / espanyol, anglès, francès, portuguès',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-01.jpg'
  ),
  array (
    'link_title' => 'Corrector',
    'link_url' => '/corrector',
    'link_description' => 'Corrector ortogràfic i gramatical català | Corrector valencià',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-02.jpg'
  ),
  array (
    'link_title' => 'Diccionari multilingüe',
    'link_url' => '/diccionari-multilingue',
    'link_description' => 'Diccionari multilingüe català que proporciona definicions
    i traduccions a l\'anglès, alemany, francès i espanyol.',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-03.jpg'
  ),
  array (
    'link_title' => 'Diccionari de sinònims',
    'link_url' => '/diccionari-de-sinonims',
    'link_description' => 'Diccionari de sinònims de català en línia. Basat en el projecte OpenThesaurus-ca',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-04.jpg'
  )
);
Timber::render(array('plantilla-distribuidora-01.twig'), $context);
