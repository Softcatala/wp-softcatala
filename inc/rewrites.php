<?php
// hook add_rewrite_rules function into rewrite_rules_array
add_filter('rewrite_rules_array', 'sc_custom__rewrite_rules');
add_filter( 'post_type_link', 'sc_catalanitzador_page_link' , 10, 2 );
add_filter( 'page_link', 'sc_aparells_page_link' , 10, 2 );
add_action('init', 'sc_add_special_pages_rewrite_rules');

function sc_catalanitzador_page_link( $permalink, $postId ) {

	$post = get_post( $postId );

	$catalanitzador = get_option('catalanitzador_post_id');

	if ( !empty( $catalanitzador ) && is_numeric( $catalanitzador )
			&& $post->post_type == 'programa' && $post->ID == (int)$catalanitzador ) {
			return '/catalanitzador/';
	}

	return $permalink;
}

function sc_aparells_page_link( $permalink, $postId ) {

	$post = get_post( $postId );

	$aparells = get_option('aparells_post_id');

	if ( !empty( $aparells ) && is_numeric( $aparells )
			&& $post->post_type == 'page' && $post->ID == (int)$aparells ) {
			return '/aparells/';
	}

    return $permalink;
}

function sc_add_special_pages_rewrite_rules() {

	$catalanitzador = get_option('catalanitzador_post_id');

	if ( !empty( $catalanitzador ) && is_numeric( $catalanitzador ) ) {
		add_rewrite_rule( 'catalanitzador/?',
							'index.php?post_type=programa&p=' . (int) $catalanitzador,
							'top');
	}

	$aparells = get_option('aparells_post_id');

	if ( !empty( $aparells ) && is_numeric( $aparells ) ) {
		add_rewrite_rule('aparells/?', 'index.php?page_id=' . (int) $aparells, 'top');
	}
}

function sc_custom__rewrite_rules($aRules) {
    //Diccionari de sinònims
    $aNewRules = array('diccionari-de-sinonims/paraula/([^/]+)/?' => 'index.php?post_type=page&pagename=diccionari-de-sinonims&paraula=$matches[1]');
    $aRules = $aNewRules + $aRules;

	//Diccionari de sinònims
	$aNewRules = array('diccionari-de-sinonims/lletra/([a-zA-Z]+)/?' => 'index.php?post_type=page&pagename=diccionari-de-sinonims&lletra=$matches[1]');
	$aRules = $aNewRules + $aRules;

    //Plantilla steps
    $aNewRules = array('col·laboreu/projecte/([^/]+)/?' => 'index.php?post_type=page&pagename=col·laboreu/projecte&project=$matches[1]');
    $aRules = $aNewRules + $aRules;

    //Plantilla steps
    $cerca_base = array('cerca/([^/]+)/?' => 'index.php?s=$matches[1]');
    $cerca_base_page = array('cerca/([^/]+)/pagina/([0-9]{1,})/?' => 'index.php?s=$matches[1]&paged=$matches[2]');
    $aRules = $cerca_base_page + $cerca_base + $aRules;

    //Plantilla steps
    $cerca_base = array('noticies/cerca/([^/]+)/?' => 'index.php?cerca=$matches[1]');
    $cerca_base_page = array('noticies/cerca/([^/]+)/pagina/([0-9]{1,})/?' => 'index.php?cerca=$matches[1]&paged=$matches[2]');
    $aRules = $cerca_base_page + $cerca_base + $aRules;

    //Programes
    $aNewRules = array(
		'programes/p/([^/]+)/so/([^/]+)/cat/([^/]+)/pagina/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]&categoria_programa=$matches[3]&paged=$matches[4]',
        'programes/p/([^/]+)/so/([^/]+)/cat/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]&categoria_programa=$matches[3]',
		'programes/p/([^/]+)/so/([^/]+)/pagina/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]&paged=$matches[3]',
        'programes/p/([^/]+)/so/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&sistema_operatiu=$matches[2]',
		'programes/p/([^/]+)/cat/([^/]+)/pagina/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&categoria_programa=$matches[2]&paged=$matches[3]',
        'programes/p/([^/]+)/cat/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&categoria_programa=$matches[2]',
		'programes/p/([^/]+)/pagina/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]&paged=$matches[2]',
        'programes/p/([^/]+)/?' => 'index.php?post_type=programa&cerca=$matches[1]',
		'programes/so/([^/]+)/cat/([^/]+)/pagina/([^/]+)/?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]&categoria_programa=$matches[2]&paged=$matches[3]',
        'programes/so/([^/]+)/cat/([^/]+)/?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]&categoria_programa=$matches[2]',
		'programes/so/([^/]+)/pagina/([^/]+)/?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]&paged=$matches[2]',
        'programes/so/([^/]+)/?' => 'index.php?post_type=programa&sistema_operatiu=$matches[1]',
		'programes/cat/([^/]+)/pagina/([^/]+)/?' => 'index.php?post_type=programa&categoria_programa=$matches[1]&paged=$matches[2]',
        'programes/cat/([^/]+)/?' => 'index.php?post_type=programa&categoria_programa=$matches[1]',
        'programes/arxivats/?' => 'index.php?post_type=programa&classificacio=arxivat',
        'projectes/arxivats/?' => 'index.php?post_type=projecte&classificacio=arxivat',
    );
    $aRules = $aNewRules + $aRules;


    // Conjugador i diccionari eng-cat
    $aNewRules = array(
        'conjugador-de-verbs/verb/([^/]+)/?' => 'index.php?post_type=page&pagename=conjugador-de-verbs&verb=$matches[1]',
        'conjugador-de-verbs/lletra/([a-zA-Z]+)/?' => 'index.php?post_type=page&pagename=conjugador-de-verbs&lletra=$matches[1]',
        'diccionari-angles-catala/paraula/([^/]+)/?' => 'index.php?post_type=page&pagename=diccionari-angles-catala&paraula=$matches[1]',
        'diccionari-angles-catala/cat/lletra/([a-zA-Z]+)/?' => 'index.php?post_type=page&pagename=diccionari-angles-catala&llengua=cat&lletra=$matches[1]',
        'diccionari-angles-catala/eng/lletra/([a-zA-Z]+)' => 'index.php?post_type=page&pagename=diccionari-angles-catala&llengua=eng&lletra=$matches[1]',
    );
    
    $aRules = $aNewRules + $aRules;


    return $aRules;
}
