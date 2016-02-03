<?php

class SC_Rewriter {

    protected $singular;
    protected $plural;

    public function __construct($singular, $plural) {
        $this->singular = $singular;
        $this->plural = $plural;
    }

    public function setup_rewrite() {
        $this->subpages_rewrite();
        add_filter( 'page_link', array($this, 'subpages_post_link') , 10, 2 );
    }

    private function subpages_rewrite() {
        add_rewrite_rule(
            "$this->plural/[^&/]+/([^/]+)/?",
            'index.php?post_type=page&pagename='. $this->get_partial_subpages_path().'$matches[1]',
            'top'
        );
    }

    public function subpages_post_link( $permalink, $post ) {

        if ( false === strpos( $permalink, $this->get_partial_subpages_path() ) ) {
            return $permalink;
        }

        $parent_id = get_post_meta($post, 'wpcf-'.$this->singular, true);

        $parent_entity = get_post( $parent_id );

        if ( $parent_entity !== null ) {
            $slug = $parent_entity->post_name;
        } else {
            $slug = '';
        }

        return str_replace( "%$this->singular%", $slug , $permalink );
    }

    private function get_partial_subpages_path() {
        return "subpagines-$this->plural/";
    }
}
