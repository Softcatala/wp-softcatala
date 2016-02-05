<?php
// hook add_rewrite_rules function into rewrite_rules_array
add_filter('rewrite_rules_array', 'add_rewrite_rules');
function add_rewrite_rules($aRules) {
    $aNewRules = array('diccionari_de_sinonims/paraula/([^/]+)/?' => 'index.php?post_type=page&pagename=diccionari_de_sinonims&paraula=$matches[1]');
    $aRules = $aNewRules + $aRules;
    return $aRules;
}