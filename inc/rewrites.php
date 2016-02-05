<?php
// hook add_rewrite_rules function into rewrite_rules_array
add_filter('rewrite_rules_array', 'add_rewrite_rules');
function add_rewrite_rules($aRules) {
    //Diccionari de sinònims
    $aNewRules = array('diccionari-de-sinonims/paraula/([^/]+)/?' => 'index.php?post_type=page&pagename=diccionari-de-sinonims&paraula=$matches[1]');
    $aRules = $aNewRules + $aRules;
    return $aRules;
}