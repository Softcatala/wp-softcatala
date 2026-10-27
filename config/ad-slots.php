<?php
/**
 * Revive ad slots and the sections that choose a zone for each of them.
 *
 * Zone values are edited on the "Publicitat" options page; 'seed' holds the zone a
 * section uses until that field has been saved for the first time.
 * A page belongs to the first section whose 'templates' or 'post_types' match it,
 * and to 'default' otherwise.
 *
 * @package Softcatala
 */

return array(
	'revive_id'    => 'eb4534b21d1ac483f6974b8de847dbe7',
	'delivery_url' => '//ad.softcatala.org/www/delivery/asyncjs.php',

	'slots'        => array(
		'header_1'       => 'Capçalera (bàner principal)',
		'header_2'       => 'Capçalera (bàner secundari, pantalles grans)',
		'sidebar_top'    => 'Barra lateral, a dalt',
		'sidebar_bottom' => 'Barra lateral, a baix',
	),

	'sections'     => array(
		'default'     => array(
			'title' => 'Per defecte',
			'seed'  => array(
				'header_1' => 13,
				'header_2' => 17,
			),
		),
		'home'        => array(
			'title'     => 'Portada',
			'templates' => array( 'home-sc.php' ),
		),
		'corrector'   => array(
			'title'     => 'Corrector',
			'templates' => array( 'corrector.php', 'corrector-antic.php' ),
			'seed'      => array( 'sidebar_top' => 36 ),
		),
		'traductor'   => array(
			'title'     => 'Traductor',
			'templates' => array( 'traductor.php' ),
			'seed'      => array( 'sidebar_top' => 38 ),
		),
		'diccionaris' => array(
			'title'     => 'Diccionaris',
			'templates' => array( 'sinonims.php', 'diccionari-engcat.php' ),
			'seed'      => array( 'sidebar_top' => 40 ),
		),
		'recursos'    => array(
			'title'     => 'Altres recursos',
			'templates' => array(
				'conjugador.php',
				'cercador-paraules.php',
				'dubbing.php',
				'dubbing-feedback.php',
				'dubbing-results.php',
				'hora-catala.php',
				'nombres-lletres.php',
				'resum-de-textos.php',
				'sep-sillabes.php',
				'transcribe.php',
				'transcribe-results.php',
			),
		),
		'programes'   => array(
			'title'      => 'Programes',
			'post_types' => array( 'programa' ),
		),
	),
);
