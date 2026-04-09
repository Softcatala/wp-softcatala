<?php
/**
 * Archive page for tasca custom post type (kanban board).
 *
 * @package  wp-softcatala
 */

use Softcatala\Providers\Tasques;

// Enqueue SortableJS and kanban JS (JS loaded in footer).
wp_register_script(
	'sortablejs',
	get_template_directory_uri() . '/static/js/sortable.min.js',
	array(),
	'1.15.7',
	true
);
wp_enqueue_script(
	'sc-js-kanban',
	get_template_directory_uri() . '/static/js/kanban.js',
	array( 'jquery', 'sortablejs' ),
	WP_SOFTCATALA_VERSION,
	true
);

// Deliver REST URL, nonce, and login state to the JS layer.
// Nonce is generated server-side here — never via Twig output — per GAP-SEC-4.
wp_localize_script(
	'sc-js-kanban',
	'sc_kanban',
	array(
		'rest_url'     => rest_url( 'sc/v1/tasca/' ),
		'nonce'        => wp_create_nonce( 'wp_rest' ),
		'is_logged_in' => is_user_logged_in(),
	)
);

$is_logged_in = is_user_logged_in();

// Fetch tasks and board metadata from the provider.
$estats       = Tasques::get_ordered_estats();
$tasks        = Tasques::get_all_for_board( $is_logged_in );
$tasks_by_estat = Tasques::group_by_estat( $tasks, $estats );
$filter_options = Tasques::get_filter_options( $tasks );

$total_task_count = count( $tasks );

// Enrich each task with the data attributes the Twig template and JS need.
$tasks_data = array();
foreach ( $tasks as $task ) {
	$projecte       = get_field( 'projecte_tasca', $task->ID );
	$responsables   = get_field( 'responsable_tasca', $task->ID );
	$milestone      = get_field( 'milestone_tasca', $task->ID );
	$data_venciment = get_field( 'data_venciment', $task->ID );
	$task_tags      = wp_get_post_terms( $task->ID, 'tag_tasca' );
	$estat_terms    = wp_get_post_terms( $task->ID, 'estat_tasca' );

	$projecte_slug = '';
	$projecte_name = '';
	if ( $projecte ) {
		$p_id          = is_array( $projecte ) ? ( $projecte['ID'] ?? 0 ) : (int) $projecte;
		$projecte_slug = get_post_field( 'post_name', $p_id );
		$projecte_name = get_the_title( $p_id );
	}

	$assignee_usernames = array();
	if ( $responsables ) {
		if ( ! is_array( $responsables ) ) {
			$responsables = array( $responsables );
		}
		foreach ( $responsables as $u ) {
			$assignee_usernames[] = is_array( $u ) ? ( $u['user_login'] ?? '' ) : get_userdata( (int) $u )->user_login;
		}
	}

	$milestone_id    = 0;
	$milestone_title = '';
	if ( $milestone ) {
		$m_id            = is_array( $milestone ) ? ( $milestone['ID'] ?? 0 ) : (int) $milestone;
		$milestone_id    = $m_id;
		$milestone_title = get_the_title( $m_id );
	}

	$tag_slugs = array();
	if ( $task_tags && ! is_wp_error( $task_tags ) ) {
		foreach ( $task_tags as $tag ) {
			$tag_slugs[] = $tag->slug;
		}
	}

	$estat_slug = '';
	if ( $estat_terms && ! is_wp_error( $estat_terms ) && ! empty( $estat_terms ) ) {
		$estat_slug = $estat_terms[0]->slug;
	}

	$tasks_data[ $task->ID ] = array(
		'ID'                 => $task->ID,
		'title'              => get_the_title( $task ),
		'content'            => apply_filters( 'the_content', $task->post_content ),
		'projecte_slug'      => $projecte_slug,
		'projecte_name'      => $projecte_name,
		'assignee_usernames' => implode( ',', $assignee_usernames ),
		'milestone_id'       => $milestone_id,
		'milestone_title'    => $milestone_title,
		'data_venciment'     => $data_venciment ?: '',
		'tag_slugs'          => implode( ',', $tag_slugs ),
		'estat_slug'         => $estat_slug,
		'comments'           => get_comments( array( 'post_id' => $task->ID, 'status' => 'approve' ) ),
	);
}

$templates = array( 'archive-tasca.twig' );

$context_holder['estats']           = $estats;
$context_holder['tasks_data']       = $tasks_data;
$context_holder['tasks_by_estat']   = $tasks_by_estat;
$context_holder['filter_options']   = $filter_options;
$context_holder['total_task_count'] = $total_task_count;
$context_holder['is_logged_in']     = $is_logged_in;

$context_filterer = new SC_ContextFilterer( $context_holder );
$context_overrides = array(
	'title'       => 'Tasques - Softcatalà',
	'description' => 'Tauler kanban de tasques dels projectes de Softcatalà.',
);
$context = $context_filterer->get_filtered_context( $context_overrides, false );

Timber::render( $templates, $context );
