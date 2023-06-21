<?php

class SC_Sitemaps {

    private static $elems = [];

    static function init()  {

        self::$elems = [
            new Softcatala\Sitemaps\Sinonims()
        ];

        add_filter( 'wpseo_sitemap_index', ['SC_Sitemaps', 'add_sitemap_custom_items'] );
        add_filter( 'init', ['SC_Sitemaps', 'add_rewrite_rules'] );
        add_filter( 'query_vars', ['SC_Sitemaps', 'add_query_var' ] );
        add_action( 'pre_get_posts',   ['SC_Sitemaps', 'dispatch_path' ], 1 );
    }

    public static function dispatch_path( $query ) {

        if ( ! $query->is_main_query() ) {
            return;
        }

        foreach(self::$elems as $e) {
            $e->maybe_render();
        }
    }

    public static function add_rewrite_rules() {

        foreach(self::$elems as $e) {
            $e->add_rewrite_rules();
        }

    }

    public static function add_query_var( $qv ) {
        $q = [];

        foreach(self::$elems as $e) {
            $q = array_merge($q, $e->query_vars());
        }

        $qv[] = 'sc_sitemaps';

        return array_merge($qv, $q) ;
    }

    static function add_sitemap_custom_items( $sitemap_custom_items ) {

        foreach(self::$elems as $e) {
            $sitemap_custom_items .= $e->sitemap_index();
        }

        return $sitemap_custom_items;
    }
}