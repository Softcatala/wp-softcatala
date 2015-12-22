<?php

/**
 * Lists shortcodes
 * Based on http://betterwp.net/protect-shortcodes-from-wpautop-and-the-likes/
 */

global $sc_preprocessed_shortcodes;

// Add here all shortcodes to avoid wpautop and other processing on them
$sc_preprocessed_shortcodes = array ('llista-icones' => 'sc_icon_list_shortcode');

add_filter('the_content', 'sc_pre_process_shortcode', 7);
add_filter('the_content', 'sc_add_dummy_shortcode', 12);

function sc_add_dummy_shortcode( $content = '' )
{
	global $sc_preprocessed_shortcodes;
	foreach ( $sc_preprocessed_shortcodes as $shortcode => $func ) {
		add_shortcode( $shortcode, 'sc_dummy_filter');
	}
	return $content;
}

function sc_dummy_filter($atts, $content = '')
{
	return $content;
}

function sc_pre_process_shortcode( $content ) {
    global $shortcode_tags;
	global $sc_preprocessed_shortcodes;

    // Backup current registered shortcodes and clear them all out
    $orig_shortcode_tags = $shortcode_tags;
    $shortcode_tags = array();

	foreach ( $sc_preprocessed_shortcodes as $shortcode => $func ) {
		add_shortcode( $shortcode , $func);
	}

    // Do the shortcode (only the one above is registered)
    $content = do_shortcode($content);

    // Put the original shortcodes back
    $shortcode_tags = $orig_shortcode_tags;
    return $content;
}

add_filter( 'no_texturize_shortcodes', 'sc_shortcodes_to_exempt_from_wptexturize' );
function sc_shortcodes_to_exempt_from_wptexturize( $shortcodes ) {
    $shortcodes[] = 'iconlist';
    return $shortcodes;
}

function sc_icon_list_shortcode( $atts, $content ) {
	$atributs = shortcode_atts( array(
        'color' => 'blanc',
    ), $atts );
	
	$color = ($atributs['color'] == 'gris' ) ? 'gris' : 'blanc';
		
	$html = '<div class="row">';
	
	$items = preg_split( '/\R/', $content, -1, PREG_SPLIT_NO_EMPTY );
	
	$columns = sc_get_icon_list_columns( $items, $color );
	
	$html .= '<div class="col-sm-6">' . $columns[0] . '</div>';
	$html .=  '<div class="col-sm-6">' . $columns[1] . '</div>';
	$html .=  '</div>';
	
	return $html;
}

function sc_get_icon_list_columns ( $items, $color ) {
	$column0 = '';
	$column1 = '';
	$total_items = 0;
	
	foreach ( $items as $item ) {
		$item_html = sc_get_icon_list_item( $item, $color );
		if ( $total_items % 2 == 0) {
			$column0 .= $item_html;
		} else {
			$column1 .= $item_html;
		}
		$total_items++;
	}
	
	return array( $column0, $column1 );
}

function sc_get_icon_list_item ( $item, $color ) {
	$parts = explode( '|', $item );
	
	$icon = (empty($parts[0])) ? 'circle' : $parts[0];
	$heading = (empty($parts[1])) ? '' : $parts[1];
	$body = (empty($parts[2])) ? '' : $parts[2];
	
	$html = '<div class="thumbnail thumbnail-' . $color . '">';
	
	$html .= '<i class="fa fa-' . $icon . '"></i>';
	
	$html .= '<div class="caption">';
	
	if(!empty($heading)) {
		$html .= '<h3>' . $heading . '</h3>';
	}
	
	if (!empty( $body )) {
		$html .= '<p>' . $body . '</p>';
	}
	
	$html .= '</div>';
	
	$html .= '</div>';
	
	return $html;
}
