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

    //Programes
    $aNewRules = array(
        'programes/p/([^/]+)/so/([^/]+)/cat/([^/]+)/arx/([^/]+)?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]&categoria_programa=$matches[3]&arxivat=$matches[4]',
        'programes/p/([^/]+)/so/([^/]+)/cat/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]&categoria_programa=$matches[3]',
        'programes/p/([^/]+)/so/([^/]+)/arx/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]&arxivat=$matches[3]',
        'programes/p/([^/]+)/cat/([^/]+)/arx/([^/]+)?' => 'index.php?post_type=programa&cerca=$matches[1]&categoria_programa=$matches[2]&arxivat=$matches[3]',
        'programes/p/([^/]+)/so/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]',
        'programes/p/([^/]+)/cat/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&categoria_programa=$matches[2]',
        'programes/p/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]',
        'programes/so/([^/]+)/cat/([^/]+)/arx/([^/]+)?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]&categoria_programa=$matches[2]&arxivat=$matches[3]',
        'programes/so/([^/]+)/arx/([^/]+)?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]&arxivat=$matches[2]',
        'programes/so/([^/]+)/cat/([^/]+)/?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]&categoria_programa=$matches[2]',
        'programes/so/([^/]+)/?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]',
        'programes/cat/([^/]+)/arx/([^/]+)?' => 'index.php?post_type=programa&categoria_programa=$matches[1]&arxivat=$matches[2]',
        'programes/cat/([^/]+)/?' => 'index.php?post_type=programa&categoria_programa=$matches[1]',
        'programes/arx/([^/]+)/?' => 'index.php?post_type=programa&arxivat=$matches[1]',
    );
    $aRules = $aNewRules + $aRules;

    return $aRules;
}