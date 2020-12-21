<?php
/**
 * Template Name: Home Softcatala
 *
 * @package wp-softcatala
 */

//Template initialization
$templates = array('home-sc.twig' );
$context = Timber::get_context();
$context['ads_container'] = true;

//Sections population
$context['slides'] = Timber::get_posts( array( 'post_type' => 'slider' ) );
$context['posts'] = Timber::get_posts(array( 'post_type' => 'post', 'numberposts' => '3', 'post_status' => 'publish' ));
$context['programari'] = get_top_downloads_home();
$context['podcasts'] = Timber::get_posts( array( 'post_type' => 'podcast', 'numberposts' => 1 ) );

$context['recursos'] = array(
	array(
		"link" => "/traductor/",
		"title" => "Traductor",
		"description" => "Traductor català <> {castellà, anglès, portuguès, francès} basat en la tecnologia d'Apertium.",
		"image" => "https://www.softcatala.org/themes/wp-softcatala/static/images/content/img-thumb-home-01.jpg"
	),
	array(
		"link" => "/corrector/",
		"title" => "Corrector",
		"description" => "Corrector ortogràfic i gramatical. Podeu triar entre formes generals, valencianes i balears.",
		"image" => "https://www.softcatala.org/themes/wp-softcatala/static/images/content/img-thumb-home-02.jpg"
	),
	array(
		"link" => "/catalanitzador/",
		"title" => "Catalanitzador",
		"description" => "Un programa per catalanitzar els ordinadors Windows o Mac de manera senzilla i automàtica.",
		"image" => "https://www.softcatala.org/themes/wp-softcatala/static/images/content/img-thumb-home-03.jpg"
	),
	array(
		"link" => "/aparells/",
		"title" => "Guia d'aparells <br />en català",
		"description" => "Voleu saber quins mòbils i tauletes funcionen en català? Us n'informem en aquesta guia.",
		"image" => "https://www.softcatala.org/themes/wp-softcatala/static/images/content/img-thumb-home-04.jpg"
	)
);

Timber::render( $templates, $context );