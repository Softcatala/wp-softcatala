<?php
/**
 * Idempotent, minimal content set for local SEO and browser testing.
 */

function sc_dev_upsert_post( $post_type, $slug, $title, $content = '', $excerpt = '', $template = '' ) {
	$existing = get_page_by_path( $slug, OBJECT, $post_type );
	$data = array(
		'ID'           => $existing ? $existing->ID : 0,
		'post_type'    => $post_type,
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
	);

	$post_id = wp_insert_post( wp_slash( $data ), true );
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( $post_id->get_error_message() );
	}

	if ( $template ) {
		update_post_meta( $post_id, '_wp_page_template', $template );
	}

	return $post_id;
}

$home_id = sc_dev_upsert_post(
	'page',
	'inici',
	'Softcatalà',
	'Recursos i serveis per viure i treballar en català.',
	'',
	'home-sc.php'
);

$news_id = sc_dev_upsert_post(
	'page',
	'noticies',
	'Notícies',
	'Notícies de llengua catalana i tecnologia en català.'
);

sc_dev_upsert_post(
	'page',
	'diccionari-de-sinonims',
	'Diccionari de sinònims',
	'Consulteu sinònims, antònims i paraules relacionades en català.',
	'',
	'sinonims.php'
);

sc_dev_upsert_post(
	'page',
	'conjugador-de-verbs',
	'Conjugador de verbs',
	'Conjuga verbs en català i consulta totes les formes verbals, els temps, els modes i les persones.',
	'',
	'conjugador.php'
);

sc_dev_upsert_post(
	'page',
	'diccionari-angles-catala',
	'Diccionari anglès-català',
	'Consulta traduccions de l’anglès al català i del català a l’anglès, amb exemples d’ús en context.',
	'',
	'diccionari-engcat.php'
);

sc_dev_upsert_post(
	'page',
	'guia-local',
	'Guia local de Softcatalà',
	'Aquesta pàgina de mostra permet comprovar metadades, breadcrumbs, enllaços interns i jerarquia de títols.',
	''
);

$category_id = wp_create_category( 'Llengua catalana' );
$tag = term_exists( 'local', 'post_tag' );
if ( ! $tag ) {
	$tag = wp_insert_term( 'local', 'post_tag' );
}
$tag_id = is_array( $tag ) ? $tag['term_id'] : $tag;

for ( $i = 1; $i <= 24; $i++ ) {
	$post_id = sc_dev_upsert_post(
		'post',
		'noticia-local-' . $i,
		'Notícia local ' . $i,
		'Contingut editorial de prova per validar la paginació, els arxius i les metadades SEO de les notícies.',
		$i % 2 ? 'Resum editorial de la notícia local ' . $i . '.' : ''
	);
	wp_set_post_categories( $post_id, array( $category_id ) );
	wp_set_post_tags( $post_id, array( (int) $tag_id ) );
}

$program_id = sc_dev_upsert_post(
	'programa',
	'programa-local',
	'Programa local',
	'',
	'Aplicació de mostra disponible en català per validar la fitxa i el sitemap de programes.'
);

$project_id = sc_dev_upsert_post(
	'projecte',
	'projecte-local',
	'Projecte local',
	'',
	'Projecte de mostra de Softcatalà per comprovar metadades, headings i navegació.'
);

$dataset_id = sc_dev_upsert_post(
	'dadesobertes',
	'corpus-local',
	'Corpus local de prova',
	'Conjunt de dades lingüístiques de mostra per validar la fitxa i les dades estructurades.',
	''
);
update_post_meta( $dataset_id, 'namedts', 'Corpus local de prova' );
update_post_meta( $dataset_id, 'description', 'Conjunt de dades lingüístiques de mostra per a proves locals.' );
update_post_meta( $dataset_id, 'download_url', 'https://example.invalid/corpus-local.csv' );
update_post_meta( $dataset_id, 'format', 'text/csv' );
update_post_meta( $dataset_id, 'license_license_name', 'Creative Commons CC0' );
update_post_meta( $dataset_id, 'license_license_url', 'https://creativecommons.org/publicdomain/zero/1.0/' );
update_field(
	'creator',
	array(
		array( 'author_type' => 'organization', 'creator_name' => 'Softcatalà' ),
		array( 'author_type' => 'person', 'creator_name' => 'Autoria local' ),
	),
	$dataset_id
);

sc_dev_upsert_post(
	'slider',
	'destacat-local',
	'Destacat local',
	'',
	'Contingut auxiliar que només ha d’aparèixer a la portada.'
);

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );
update_option( 'page_for_posts', $news_id );
update_option( 'posts_per_page', 10 );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'category_base', 'categoria' );
update_option( 'tag_base', 'etiqueta' );
update_option( 'api_diccionari_sinonims', 'https://fixtures.softcatala.invalid/sinonims/' );
update_option( 'api_conjugador', 'https://fixtures.softcatala.invalid/conjugador/' );
update_option( 'api_diccionari_engcat', 'https://fixtures.softcatala.invalid/engcat/' );
update_option( 'api_cerca_corpus', 'https://fixtures.softcatala.invalid/corpus' );

flush_rewrite_rules();

WP_CLI::success( 'Contingut local SEO preparat.' );
