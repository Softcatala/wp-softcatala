<?php

/**
 * Register our sidebars and widgetized areas.
 *
 */

add_filter( 'the_content', 'sc_add_custom_table_class' );
function sc_add_custom_table_class( $content ) {
    //General style rewrite for <table> and <ul>
    $content = str_replace( '<table>', '<table class="table table-bordered">', $content );
    $content = str_replace( '<ul>', '<ul class="cont-llista">', $content );

    //Styles to rewrite classes from old content in mediawiki
    $content = str_replace( '<table class="comparacio">', '<table class="table table-bordered">', $content );
    $content = str_replace( '<table class="prettytable">', '<table class="table table-bordered taula-2col">', $content );
    $content = str_replace( '<table class="prettytable1">', '<table class="table table-bordered taula-2col">', $content );

    return $content;
}