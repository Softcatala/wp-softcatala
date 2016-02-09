<?php

/**
 * Functions related to post types
 *
 */

function get_page_parent_title( $post ) {
    $parent = array_reverse( get_post_ancestors( $post->ID ) );
    $parent_data['id'] = $parent[0];
    $parent_data['title'] = get_the_title( $parent[0] );
    return $parent_data;
}

function wp_list_subpages($parent_id, $sort_column = 'menu_order', $sort_order = 'ASC') {
    $pages_tree = wp_list_pages( array(
        'child_of' => $parent_id,
        'echo' => 0,
        'sort_column' => $sort_column,
        'sort_order'   => $sort_order,
        'link_before' => '<i class="fa fa-angle-right"></i>',
        'title_li' => '',
    ) );

    $pages_tree = str_replace( 'children', 'nav children', $pages_tree);

    return $pages_tree;
}

/*
 * Function that extracts the post id from a specific post (for use on array_map)
 */
function extract_post_ids( $post ) {
    return $post->ID;
}

/*
 * Function that extracts the post id from a specific post relationship (for use on array_map)
 */
function extract_post_ids_program( $post ) {
    return wpcf_pr_post_get_belongs( $post->ID, 'programa' );
}

/*
 * Function that extracts the post url and title from a specific post (for use on array_map)
 */
function generate_post_url_link( $post ) {
    $title = $post->post_title;
    $url = get_permalink($post);

    $return = '<a href="'.$url.'" title="'.$title.'">'.$title.'</a>';

    return $return;
}

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
    $limit = 5;
    $json_url = "http://softcatala.local/result.json";
    $baixades_json = json_decode( file_get_contents( $json_url ) );

    foreach ( $baixades_json as $key => $operating_system ) {
        $programari[$key] = array();
        $i = 0;
        foreach ( $operating_system as $pkey => $program ) {
            if ($i < $limit) {
                $programari[$key][$pkey]['title'] = str_replace('_', ' ', $program->Nom);
                $programari[$key][$pkey]['link'] = 'https://www.softcatala.org/wiki/Rebost:' . $program->Nom;
                $programari[$key][$pkey]['total_downloads'] = $program->total;
                $i++;
            }
        }
    }

    return $programari;
}