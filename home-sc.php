<?php
/**
 * Template Name: Home Softcatala
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$context['slides'] = Timber::get_posts(array('post_type' => 'slide'));
$args = array('post_type' => 'post', 'numberposts' => '3');
$context['posts'] = Timber::get_posts($args);
$args = get_post_query_args( SearchQueryType::Highlight );
query_posts($args);
$context['esdeveniments'] = Timber::get_posts($args);
if(count($context['esdeveniments']) < 1){
	$args = get_post_query_args( SearchQueryType::All );
	query_posts($args);
	$context['esdeveniments'] = Timber::get_posts($args);
}
$context['programari'] = getProgramari();
Timber::render( array( 'home-sc.twig' ), $context );

/**
 * Temporary function to retrive most downloaded software list
 * Should be removed once «Programari» section is implemented and
 * $context['programari'] retrieves the post type 'programa'
 *
 * @return array
 *
*/
function getProgramari() 
{
	$programari = array(
		'windows' => array(
			array(
				'title' => 'LibreOffice',
				'link' => '/libreoffice',
				'total_downloads' => '7443'
				),
			array(
				'title' => 'Gimp',
				'link' => '/gimp',
				'total_downloads' => '6343'
				),
			array(
				'title' => 'Firefox',
				'link' => '/firefox',
				'total_downloads' => '5988'
				),
			array(
				'title' => 'WinRAR',
				'link' => '/winrar',
				'total_downloads' => '4343'
				),
			array(
				'title' => 'Audacity',
				'link' => '/audacity',
				'total_downloads' => '2563'
				),
			),
		'linux' => array(
			array(
				'title' => 'LibreOffice Linux',
				'link' => '/libreoffice',
				'total_downloads' => '7443'
				),
			array(
				'title' => 'Gimp',
				'link' => '/gimp',
				'total_downloads' => '6343'
				),
			array(
				'title' => 'Firefox',
				'link' => '/firefox',
				'total_downloads' => '5988'
				),
			array(
				'title' => 'WinRAR',
				'link' => '/winrar',
				'total_downloads' => '4343'
				),
			array(
				'title' => 'Audacity',
				'link' => '/audacity',
				'total_downloads' => '2563'
				),
			),
		'osx' => array(
			array(
				'title' => 'LibreOffice OSX',
				'link' => '/libreoffice',
				'total_downloads' => '7443'
				),
			array(
				'title' => 'Gimp',
				'link' => '/gimp',
				'total_downloads' => '6343'
				),
			array(
				'title' => 'Firefox',
				'link' => '/firefox',
				'total_downloads' => '5988'
				),
			array(
				'title' => 'WinRAR',
				'link' => '/winrar',
				'total_downloads' => '4343'
				),
			array(
				'title' => 'Audacity',
				'link' => '/audacity',
				'total_downloads' => '2563'
				),
			),
		'android' => array(
			array(
				'title' => 'LibreOffice Android',
				'link' => '/libreoffice',
				'total_downloads' => '7443'
				),
			array(
				'title' => 'Gimp',
				'link' => '/gimp',
				'total_downloads' => '6343'
				),
			array(
				'title' => 'Firefox',
				'link' => '/firefox',
				'total_downloads' => '5988'
				),
			array(
				'title' => 'WinRAR',
				'link' => '/winrar',
				'total_downloads' => '4343'
				),
			array(
				'title' => 'Audacity',
				'link' => '/audacity',
				'total_downloads' => '2563'
				),
			),
		'ios' => array(
			array(
				'title' => 'LibreOffice iOS',
				'link' => '/libreoffice',
				'total_downloads' => '7443'
				),
			array(
				'title' => 'Gimp',
				'link' => '/gimp',
				'total_downloads' => '6343'
				),
			array(
				'title' => 'Firefox',
				'link' => '/firefox',
				'total_downloads' => '5988'
				),
			array(
				'title' => 'WinRAR',
				'link' => '/winrar',
				'total_downloads' => '4343'
				),
			array(
				'title' => 'Audacity',
				'link' => '/audacity',
				'total_downloads' => '2563'
				),
			)
		);

	return $programari;
}