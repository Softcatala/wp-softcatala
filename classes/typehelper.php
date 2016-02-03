<?php

class SC_TypeHelper {

    public $type;

    public function __construct( $type ) {
        $this->type = $type;
    }

    function get_info_for_select() {
        $query = new WP_Query();
        
        $args = array(
                'post_type'        => $this->type,
                'post_status'      => 'publish',
                'no_found_rows'    => true,
                'posts_per_page'      => -1
        );
        
        $all_programs = $query->query( $args );

        return array_map( function ($entry) {
            return array(
                '#value' => $entry->ID,
                '#title' => $entry->post_title,
            );
        }, $all_programs );
    }
}
