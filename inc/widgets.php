<?php

/**
 * Register our sidebars and widgetized areas.
 *
 */
function softcatala_widgets_init() {

	register_sidebar( array(
		'name'          => 'Sidebar TOP',
		'id'            => 'sidebar_top',
		'description' => 'Ginys generals que apareixen a la barra lateral, a dalt',
		'before_widget' => '<div id="%1$s" class="%2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>'
	) );
    
    register_sidebar( array(
		'name'          => 'Sidebar Bottom',
		'id'            => 'sidebar_bottom',
		'description' => 'Ginys generals que apareixen a la barra lateral, a baix',
		'before_widget' => '<div id="%1$s" class="%2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>'
	) );

}
add_action( 'widgets_init', 'softcatala_widgets_init' );