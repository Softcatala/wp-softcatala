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
 * Function to retrive most downloaded software list for the home page
 *
 * @return array
 *
 */
function get_top_downloads_home()
{
    $limit = 5;
    $json_path = ABSPATH."../top.json";
    $baixades_json = json_decode( file_get_contents( $json_path ) );

    $programari = array();
    if ( $baixades_json ) {
        foreach ( $baixades_json as $key => $operating_system ) {
            $programari[$key] = array();
            $i = 0;
            foreach ( $operating_system as $pkey => $program ) {
                if ($i < $limit) {
                    $link = get_program_link($program);
                    if ( $link ) {
                        $programari[$key][$pkey]['title'] = wp_trim_words( str_replace('_', ' ', get_the_title( $program->wordpress_id )), 8 );
                        $programari[$key][$pkey]['link'] = $link;
                        $programari[$key][$pkey]['total_downloads'] = $program->total;
                    }
                    $i++;
                }
            }
        }
    }

    return $programari;
}

/**
 * This function generates the final download url that uses the Softcatalà counter
 *
 * @param object $baixades
 * @param object $post
 * @return object $baixades
 */
function generate_url_download( $baixades, $post ) {

    //https://baixades.softcatala.org/?url=http://download.mozilla.org/?product=firefox-44.0.1&os=linux&lang=ca&id=3522&mirall=&extern=2&versio=44.0.1&so=linux
    foreach ( $baixades as $key => $baixada ) {
        if( empty( $baixada['download_version'] )) {
            $versio_baixada = '1.0';
        } else {
            $versio_baixada = $baixada['download_version'];
        }
        $baixades[$key]['download_os_label'] = get_os_nicename( $baixades[$key]['download_os'] );

        $baixades[$key]['download_url_ext'] = 'https://baixades.softcatala.org/';
        $baixades[$key]['download_url_ext'] .= '?id='.$post->idrebost;
        $baixades[$key]['download_url_ext'] .= '&wid='.$post->ID;
        $baixades[$key]['download_url_ext'] .= '&versio='.$versio_baixada;
        $baixades[$key]['download_url_ext'] .= '&so='.get_so_from_so( $baixada['download_os'], $baixada['arquitectura'] );
        $baixades[$key]['download_url_ext'] .= '&url='.urlencode($baixada['download_url']);

        $baixades[$key]['so_icona'] = get_awesome_icon_so($baixada['download_os']);
    }

    return $baixades;
}

function get_os_nicename( $os ) {
    switch ( $os ) {
        case 'windows':
            $os_nicename = 'Windows';
            break;
        case 'linux':
            $os_nicename = 'Linux';
            break;
        case 'osx':
            $os_nicename = 'OS X';
            break;
        case 'android':
            $os_nicename = 'Android';
            break;
        case 'ios':
            $os_nicename = 'iOS';
            break;
        default:
            $os_nicename = $os;
            break;
    }

    return $os_nicename;
}

function get_so_from_so( $os, $arch ) {
    switch ( $os ) {
        case 'windows':
            if( $arch == 'x86_64') {
                $os_baixada = 'win64';
            } else {
                $os_baixada = 'win32';
            }
            break;
        case 'linux':
            if( $arch == 'x86_64') {
                $os_baixada = 'linux64';
            } else {
                $os_baixada = 'linux';
            }
            break;
        case 'osx':
            $os_baixada = 'mac';
            break;
        case 'android':
            $os_baixada = 'and';
            break;
        default:
            $os_baixada = $os;
            break;
    }

    return $os_baixada;
}

/**
 * This function returns the awesome icon corresponding to an operating system
 *
 */
function get_awesome_icon_so( $os ) {
    switch ($os) {
        case 'android':
        case 'linux':
        case 'windows':
            $os_icona = $os;
            break;
        case 'ios':
        case 'osx':
            $os_icona = 'apple';
            break;
        case 'web':
            $os_icona = 'globe';
            break;
        case 'windows-phone':
            $os_icona = 'windows';
            break;
        case 'multiplataforma':
            $os_icona = 'circle-thin';
            break;
        default:
            $os_icona = 'circle-o';
            break;
    }
    return $os_icona;
}

/**
 * This function retrieves the program link depending on the idrebost or wordpress_id
 */
function get_program_link( $program ) {
    $link = false;
    if( isset( $program->wordpress_id )) {
        if ( FALSE !== get_post_status( $program->wordpress_id ) ) {
            $link = get_post_permalink( $program->wordpress_id );
        }
    } else {
        $args = array(
            'post_type' => 'programa',
            'meta_query' => array(
                array(
                    'key' => 'wpcf-idrebost',
                    'value' => $program->idrebost,
                    'compare' => '='
                )
            )
        );
        $programes = query_posts($args);
        if ( count ( $programes) > 0 ) {
            $link = get_post_permalink( $programes[0]->ID );
        }
    }

    return $link;
}

/**
 * Returns a post value given a custom_field
 *
 * @param string $post_type
 * @param string $custom_field
 * @param string $custom_field_value
 * @param string $field
 * @return mixed
 */
function get_field_value_from_custom_field( $post_type, $custom_field, $custom_field_value, $field ) {
    $args = array(
        'post_type' => $post_type,
        'meta_query' => array(
            array(
                'key' => $custom_field,
                'value' => $custom_field_value,
                'compare' => '='
            )
        )
    );
    $posts = query_posts($args);
    $post = new TimberPost($posts[0]->ID);

    return $post->$field;
}

/**
 * Tries to subscribe an email to a mailing list
 *
 * @param $url
 * @return mixed
 */
function send_subscription_to_mailinglist( $url ) {
    $result['message'] = '';
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $data = curl_exec($ch);
    curl_close($ch);

    if(preg_match('#Subscrit satisfactòriament#i', $data)) {
        $result['status'] = true;
    } else {
        $result['status'] = false;
        if(preg_match('#Ja sou membre#i', $data)) {
            $result['message'] = 'Sembla que ja sou membre de la llista de correu.';
        } else {
            $result['message'] = 'S\'ha produït un error desconegut. Podeu informar-nos a <a href="mailto:web@softcatala.org">web@softcatala.org</a>';
        }
    }

    return $result;
}

/**
 * Gets the term name from its slug
 *
 * @param $term_slug
 * @param $taxonomy
 * @return string
 */
function get_term_name_by_slug( $slug, $taxonomy ) {
    $term = get_term_by( 'slug', $slug, $taxonomy );

    return $term->name;
}

/**
 * Function to get the category ID given a category slug
 *
 * @param $slug
 * @return $int
 */
function get_category_id( $slug ) {
    $category = get_category_by_slug($slug);
    $category_id = $category->term_id;
    return $category_id;
}

function retrieve_page_data($page_slug = '')
{
    //Actions to be taken depending on the post type
    switch ($page_slug) {
        case 'noticies':
            $args = array(
                'name' => 'noticies',
                'post_type' => 'page'
            );
            $post = Timber::get_post($args);
            break;
        default:
            $args = array(
                'name' => $page_slug.'-page',
                'post_type' => 'page'
            );
            $post = Timber::get_post($args);
            break;
    }

    return $post;
}


/**
 * Functions related to esdeveniments
 *
 * @return array
 */
function get_the_event_filters()
{
    $filtres = array(
        array(
            'link' => 'setmana',
            'title' => 'Aquesta setmana'
        ),
        array(
            'link' => 'setmanavinent',
            'title' => 'La setmana vinent',
        ),
        array(
            'link' => 'mes',
            'title' => 'Aquest mes'
        )
    );
    return $filtres;
}

/**
 * Gets the filter date name from the filter date slug
 *
 * @param $filter_date_slug
 * @return mixed
 */
function get_the_filter_date_name( $filter_date_slug )
{
    $filtres = get_the_event_filters();

    foreach($filtres as $key => $item) {
        if($item['link'] == $filter_date_slug) {
            $result = $item['title'];
            break;
        }
    }

    return $result;
}

/**
 * Returns the name and link from a batch of users
 *
 * @array $users array
 * @return $array
 **/
function get_users_metadata($users_ids ) {
    foreach( $users_ids as $key => $user_id ) {
        $user = get_userdata( $user_id );
        $users[$key]['name'] = $user->first_name . ' ' . $user->last_name;
        $users[$key]['url'] = get_author_posts_url( $user_id );
        $users[$key]['email'] = $user->user_email;
    }

    return $users;
}

function get_responsables($project){
    $project = get_page_by_path(strtolower($project), OBJECT, 'projecte');

    if ($project != null) {
        $responsables = get_field('responsable', $project);

        if(is_array($responsables) && !empty($responsables)) {
            return $responsables;
        }
    }

    return false;
}

function get_gravatar_url( $email, $size = '270' ) {
    $hash = md5( strtolower( trim ( $email ) ) );
    return 'https://gravatar.com/avatar/' . $hash . '?size=' . $size . '&d=mm&r=g';
}
