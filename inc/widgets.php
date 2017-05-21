<?php

/**
 * Register our sidebars and widgetized areas.
 *
 */
function softcatala_widgets_init() {

	register_sidebar( array(
		'name'          => 'General Sidebar TOP',
		'id'            => 'sidebar_top',
		'description' => 'Ginys generals que apareixen a la barra lateral, a dalt',
		'before_widget' => '<div id="%1$s" class="%2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>'
	) );
    
    register_sidebar( array(
		'name'          => 'General Sidebar Bottom',
		'id'            => 'sidebar_bottom',
		'description' => 'Ginys generals que apareixen a la barra lateral, a baix',
		'before_widget' => '<div id="%1$s" class="%2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>'
	) );

	register_sidebar( array(
			'name'          => 'Recursos Sidebar Bottom',
			'id'            => 'sidebar_bottom_recursos',
			'description' => 'Ginys generals que apareixen a la barra lateral, a baix, a les seccions de recursos (traductor, corrector, diccionari)',
			'before_widget' => '<div id="%1$s" class="%2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>'
	) );

	register_sidebar( array(
			'name'          => 'Recursos Sidebar TOP',
			'id'            => 'sidebar_top_recursos',
			'description' => 'Ginys generals que apareixen a la barra lateral, a dalt, a les seccions de recursos (traductor, corrector, diccionari)',
			'before_widget' => '<div id="%1$s" class="%2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>'
	) );

	register_widget('SC_Catalanitzador_Stats');
}
add_action( 'widgets_init', 'softcatala_widgets_init' );
