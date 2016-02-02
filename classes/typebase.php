<?php

class SC_TypeBase {

    var $rewriter;
    var $type_helper;

    var $singular;

    public function __construct($singular, $plural) {
        $this->singular = $singular;
        $this->rewriter = new SC_Rewriter($singular,$plural);
        $this->rewriter->setup_rewrite();

        $this->type_helper = new SC_TypeHelper($singular);

        add_filter('wpt_field_options', array( $this, 'custom_select'), 10, 3);
    }

    public function get_info_for_select() {
        return $this->type_helper->get_info_for_select();
    }    

    public function custom_select( $options, $title, $type )
    {
        switch( strtolower( $title ) )
        {
            case $this->singular:
                $options = $this->get_info_for_select();
            break;
        }
        return $options;
    }
}
