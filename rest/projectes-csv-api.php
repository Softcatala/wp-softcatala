<?php

/**
 * REST API endpoint to list Projectes as JSON
 */

function sc_register_projectes_api() {
	register_rest_route(
		'sc/v1',
		'/projectes',
		array(
			'methods'             => 'GET',
			'callback'            => 'sc_rest_projectes',
			'permission_callback' => 'is_user_logged_in',
		)
	);
}
add_action( 'rest_api_init', 'sc_register_projectes_api' );

function sc_rest_projectes() {
	$query = new WP_Query(
		array(
			'post_type'      => 'projecte',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	$projectes = array();

	foreach ( $query->posts as $post ) {
		$responsables_raw = get_field( 'responsable', $post->ID );
		$responsables     = array();
		if ( is_array( $responsables_raw ) ) {
			foreach ( $responsables_raw as $u ) {
				$responsables[] = array(
					'username'     => $u['user_nicename'],
					'display_name' => $u['display_name'],
					'telegram'     => get_user_meta( $u['ID'], 'telegram', true ) ?: null,
				);
			}
		}

		$categories = wp_get_post_terms( $post->ID, 'categoria_projecte', array( 'fields' => 'names' ) );
		$categories = is_array( $categories ) ? $categories : array();

		$arxivat = wp_get_post_terms( $post->ID, 'classificacio', array( 'fields' => 'slugs' ) );
		$estat   = is_array( $arxivat ) && in_array( 'arxivat', $arxivat, true ) ? 'arxivat' : 'actiu';

		$projectes[] = array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'url'          => get_permalink( $post->ID ),
			'responsables' => $responsables,
			'categories'   => $categories,
			'estat'        => $estat,
		);
	}

	return rest_ensure_response( $projectes );
}
