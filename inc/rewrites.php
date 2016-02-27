<?php
// hook add_rewrite_rules function into rewrite_rules_array
add_filter('rewrite_rules_array', 'add_rewrite_rules');
function add_rewrite_rules($aRules) {
    //Diccionari de sinònims
    $aNewRules = array('diccionari-de-sinonims/paraula/([^/]+)/?' => 'index.php?post_type=page&pagename=diccionari-de-sinonims&paraula=$matches[1]');
    $aRules = $aNewRules + $aRules;

    //Plantilla steps
    $aNewRules = array('col·laboreu/projecte/([^/]+)/?' => 'index.php?post_type=page&pagename=col·laboreu&project=$matches[1]');
    $aRules = $aNewRules + $aRules;

    //Plantilla steps
    $cerca_base = array('cerca/([^/]+)/?' => 'index.php?s=$matches[1]');
    $cerca_base_page = array('cerca/([^/]+)/page/([0-9]{1,})/?' => 'index.php?s=$matches[1]&paged=$matches[2]');
    $aRules = $cerca_base_page + $cerca_base + $aRules;
    return $aRules;
}