<?php
/**
 * Task management (Kanban) — hook callbacks and helpers.
 *
 * Loaded at theme boot via include() in functions.php alongside the other
 * inc/ files. Hook registrations live in StarterSite::__construct().
 *
 * @package Softcatala
 */

/**
 * Register term meta for estat_tasca column ordering.
 */
function sc_register_estat_tasca_order_meta() {
	register_term_meta(
		'estat_tasca',
		'order',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 99,
			'sanitize_callback' => 'absint',
			'show_in_rest'      => false,
		)
	);
}

/**
 * Add the "Ordre" field to the Add New estat_tasca form.
 */
function sc_estat_tasca_add_order_field() {
	?>
	<div class="form-field">
		<label for="estat_tasca_order"><?php esc_html_e( 'Ordre', 'softcatala' ); ?></label>
		<input type="number" name="estat_tasca_order" id="estat_tasca_order" value="99" min="0" step="1">
		<p><?php esc_html_e( 'Ordre de la columna al tauler kanban (els valors més baixos apareixen primer).', 'softcatala' ); ?></p>
	</div>
	<?php
}

/**
 * Add the "Ordre" field to the Edit estat_tasca form.
 *
 * @param WP_Term $term The term being edited.
 */
function sc_estat_tasca_edit_order_field( $term ) {
	$order = (int) get_term_meta( $term->term_id, 'order', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="estat_tasca_order"><?php esc_html_e( 'Ordre', 'softcatala' ); ?></label></th>
		<td>
			<input type="number" name="estat_tasca_order" id="estat_tasca_order" value="<?php echo esc_attr( $order ); ?>" min="0" step="1">
			<p class="description"><?php esc_html_e( 'Ordre de la columna al tauler kanban (els valors més baixos apareixen primer).', 'softcatala' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Save the "ordre" term meta when an estat_tasca term is created or edited.
 *
 * @param int $term_id The term ID.
 */
function sc_save_estat_tasca_order_meta( $term_id ) {
	if ( isset( $_POST['estat_tasca_order'] ) ) {
		update_term_meta( $term_id, 'order', absint( $_POST['estat_tasca_order'] ) );
	}
}

/**
 * Add an "Ordre" column to the estat_tasca taxonomy admin screen.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function sc_estat_tasca_order_column( $columns ) {
	$columns['estat_order'] = __( 'Ordre', 'softcatala' );
	return $columns;
}

/**
 * Render the "Ordre" column value in the estat_tasca taxonomy admin screen.
 *
 * @param string $content     Existing content.
 * @param string $column_name Column name.
 * @param int    $term_id     Term ID.
 * @return string Column content.
 */
function sc_estat_tasca_order_column_content( $content, $column_name, $term_id ) {
	if ( 'estat_order' === $column_name ) {
		$order = get_term_meta( $term_id, 'order', true );
		return ( '' !== $order ) ? esc_html( $order ) : '99';
	}
	return $content;
}

/**
 * Seed the default estat_tasca terms on theme activation.
 * Only inserts terms if none exist yet (safe to call on every activation).
 */
function sc_seed_estat_tasca() {
	$existing = get_terms(
		array(
			'taxonomy'   => 'estat_tasca',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( ! empty( $existing ) && ! is_wp_error( $existing ) ) {
		return;
	}

	$defaults = array(
		'Pendent'    => 1,
		'En curs'    => 2,
		'Bloquejada' => 3,
		'Feta'       => 4,
	);

	foreach ( $defaults as $name => $order ) {
		$result = wp_insert_term( $name, 'estat_tasca' );
		if ( ! is_wp_error( $result ) ) {
			update_term_meta( $result['term_id'], 'order', $order );
		}
	}
}

/**
 * Prevent deletion of an estat_tasca term that has tasks assigned.
 *
 * @param null|WP_Error $pre_delete Pre-delete value.
 * @param int           $term_id    Term ID.
 * @return null|WP_Error Null to allow deletion; WP_Error to prevent it.
 */
function sc_guard_estat_tasca_delete( $pre_delete, $term_id ) {
	$term = get_term( $term_id );
	if ( ! $term || is_wp_error( $term ) || 'estat_tasca' !== $term->taxonomy ) {
		return $pre_delete;
	}

	$count_query = new WP_Query(
		array(
			'post_type'      => 'tasca',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'estat_tasca',
					'terms'    => $term_id,
				),
			),
		)
	);

	if ( $count_query->found_posts > 0 ) {
		return new WP_Error(
			'sc_estat_tasca_has_tasks',
			sprintf(
				/* translators: %1$s: term name, %2$d: number of assigned tasks */
				__( 'No es pot eliminar l\'estat "%1$s" perquè té %2$d tasca(es) assignada(es). Reassigneu les tasques abans d\'eliminar l\'estat.', 'softcatala' ),
				esc_html( $term->name ),
				$count_query->found_posts
			)
		);
	}

	return $pre_delete;
}

/**
 * Restrict the milestone_tasca ACF Post Object field to milestones linked to the
 * same projecte as the task being edited.
 *
 * When no projecte is selected (new/unsaved task), all milestones are shown.
 *
 * @param array  $args    WP_Query arguments passed to ACF.
 * @param array  $field   ACF field array.
 * @param int    $post_id The post being edited.
 * @return array Modified WP_Query args.
 */
function sc_filter_milestone_tasca_by_projecte( $args, $field, $post_id ) {
	// Resolve the currently selected projecte for this task.
	$projecte = get_field( 'projecte_tasca', $post_id );
	$projecte_id = is_array( $projecte ) ? ( $projecte['ID'] ?? 0 ) : (int) $projecte;

	if ( ! $projecte_id ) {
		// No projecte selected — show all milestones.
		return $args;
	}

	// Filter milestones by the meta value of the projecte_milestone ACF field.
	$args['meta_query'] = array(
		array(
			'key'   => 'projecte_milestone',
			'value' => $projecte_id,
		),
	);

	return $args;
}

/**
 * Invalidate the sc_internal_projecte_ids transient when a projecte is saved
 * and the tasques_internes ACF field may have changed.
 *
 * NOTE: If a server-side page cache is active on the host, cached versions of
 * /tasques/ may continue to show stale content until cache expiry. Configure a
 * cache purge hook here once the hosting environment and caching plugin are known.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function sc_invalidate_internal_projecte_ids_transient( $post_id, $post ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	delete_transient( 'sc_internal_projecte_ids' );
}
