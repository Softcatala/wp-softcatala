<?php
/**
 * Template Name: Dist - recursos per a traductors
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$context['post'] = new TimberPost();
$context['links'] = array (
  array (
    'link_title' => 'Guia d\'estil',
    'link_url' => '/guia-estil-de-softcatala/',
    'link_description' => 'Estableix les normes que s\'han d\'aplicar a totes
    i cadascuna de les traduccions que es fan a Softcatalà.',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-01.jpg'
  ),
  array (
    'link_title' => 'Memòries de traducció',
    'link_url' => '/recursos/memories.html',
    'link_description' => 'Bases de dades que conten el text original d\'un programa i la seva traducció al català.',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-02.jpg'
  ),
  array (
    'link_title' => 'Terminologia',
    'link_url' => '/recursos/terminologia.html',
    'link_description' => 'Glossaris que recullen la terminologia utilitzada en diferents projectes.',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-03.jpg'
  ),
  array (
    'link_title' => 'Estàndards ISO',
    'link_url' => '/recursos/llistats_iso.html',
    'link_description' => 'Llistes de països i llengües tal i com es defineixen als estàndards ISO.',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-04.jpg'
  ),
  array (
    'link_title' => 'Adaptador a la variant valenciana',
    'link_url' => 'http://www.softvalencia.org/adaptador/',
    'link_description' => 'Adaptador de la variant general en català a la variant valenciana.',
    'link_image' => '/themes/wp-softcatala/static/images/content/img-thumb-home-04.jpg'
  )
);
Timber::render(array('plantilla-distribuidora-01.twig'), $context);
