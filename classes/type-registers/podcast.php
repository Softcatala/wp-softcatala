<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Slider
 *
 * Registers the Slider post type
 */
class Podcast extends PostType {

    public function __construct() {
        parent::__construct( 'Podcast', 'Podcasts' );
    }

    public function custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'episode':
                echo esc_url( get_post_meta( $post_id, 'episode', true ) );
                break;

            default:
                return;
        }
    }

    public function add_columns_to_admin( $columns ) {

        return array_merge(
            $columns,
            array(
                'episode'  => 'Episodi',
            )
        );
    }

    public function register_custom_post_type() {

        $labels = $this->get_ctp_labels( 'Podcasts' );

        $args = array(
            'label'                 => __( 'Podcast', 'softcatala' ),
            'description'           => __( 'Podcasts', 'softcatala' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'excerpt', 'thumbnail' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-microphone',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'podcasts',
            'exclude_from_search'   => true,
            'publicly_queryable'    => true,
            'rewrite'             => array(
                'slug' => 'podcasts/%podcastprograma%',
                'with_front' => false,
            ),
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );

        register_post_type( 'podcast', $args );
    }

    protected function register_custom_taxonomies() {
        $this->register_podcast_programa();
    }

    private function register_podcast_programa() {

        $labels  = $this->get_taxonomy_labels(
            'Programes de Podcast',
            'Programa de podcast',
            'Programa de podcast'
        );

        $args    = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'rewrite'           => array(
                'slug' => 'podcasts',
                'with_front' => false
            ),
            'show_in_rest'      => false,
        );
        register_taxonomy( 'podcast-programa', array( 'podcast' ), $args );

        add_filter( 'post_type_link', array($this, 'permalink_podcastprograma'), 1, 3 );
    }

    public function permalink_podcastprograma( $post_link, $id = 0 ){
        $post = get_post($id);
        if ( is_object( $post ) ){
            $terms = wp_get_object_terms( $post->ID, 'podcast-programa' );
            if( $terms ){
                return str_replace( '%podcastprograma%' , $terms[0]->slug , $post_link );
            }
        }
        return $post_link;
    }
}
