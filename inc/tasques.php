<?php
/**
 * Task management (Kanban) — hook callbacks and helpers.
 *
 * Loaded at theme boot via include() in functions.php alongside the other
 * inc/ files. Hook registrations live in StarterSite::__construct().
 *
 * @package Softcatala
 */

// ---------------------------------------------------------------------------
// archived post status
// ---------------------------------------------------------------------------

/**
 * Register the 'archived' post status for the tasca post type.
 *
 * Tasks in this status are excluded from the public kanban board (the board
 * queries only 'publish' posts). They remain accessible in the WP admin under
 * the "Arxivades" view so they are never lost.
 */
function sc_register_archived_post_status() {
	register_post_status(
		'archived',
		array(
			'label'                     => _x( 'Arxivada', 'post status', 'softcatala' ),
			'label_count'               => _n_noop( 'Arxivada <span class="count">(%s)</span>', 'Arxivades <span class="count">(%s)</span>', 'softcatala' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
		)
	);
}

/**
 * Inject 'archived' into the post status dropdown on the tasca edit screen.
 *
 * WordPress only adds custom statuses to the dropdown via JavaScript — this
 * outputs a small inline script that appends the option and, if the current
 * post already has status 'archived', selects it and updates the display label.
 */
function sc_inject_archived_status_in_editor() {
	global $post;
	if ( ! $post || 'tasca' !== $post->post_type ) {
		return;
	}
	$selected = 'archived' === $post->post_status ? 'selected="selected"' : '';
	?>
	<script>
	jQuery( function( $ ) {
		$( '#post_status' ).append(
			'<option value="archived" <?php echo esc_attr( $selected ); ?>>Arxivada</option>'
		);
		<?php if ( 'archived' === $post->post_status ) : ?>
		$( '#save-action .save-post-status' ).text( 'Arxivada' );
		$( '#save-post' ).val( 'Actualitza' );
		<?php endif; ?>
	} );
	</script>
	<?php
}

/**
 * Handle the 'archive_tasca' bulk action on the tasca list table.
 *
 * @param string $redirect_url URL to redirect to after the action.
 * @param string $action       The bulk action being processed.
 * @param int[]  $post_ids     Array of post IDs selected.
 * @return string Redirect URL with result query arg appended.
 */
function sc_bulk_archive_tasques( $redirect_url, $action, $post_ids ) {
	if ( 'archive_tasca' !== $action ) {
		return $redirect_url;
	}

	$count = 0;
	foreach ( $post_ids as $post_id ) {
		if ( 'tasca' !== get_post_type( $post_id ) ) {
			continue;
		}
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'archived',
			)
		);
		++$count;
	}

	return add_query_arg( 'sc_archived', $count, remove_query_arg( array( 'archive_tasca', 'untrashed' ), $redirect_url ) );
}

/**
 * Add the 'Arxivar' option to the bulk actions dropdown on the tasca list table.
 *
 * @param array $actions Existing bulk actions.
 * @return array Modified bulk actions.
 */
function sc_add_archive_tasca_bulk_action( $actions ) {
	$actions['archive_tasca'] = __( 'Arxivar', 'softcatala' );
	return $actions;
}

/**
 * Show an admin notice after bulk-archiving tasks.
 */
function sc_archived_tasques_admin_notice() {
	if ( empty( $_REQUEST['sc_archived'] ) ) {
		return;
	}
	$count = (int) $_REQUEST['sc_archived'];
	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: %d: number of archived tasks */
				_n( '%d tasca arxivada.', '%d tasques arxivades.', $count, 'softcatala' ),
				$count
			)
		)
	);
}

// ---------------------------------------------------------------------------
// estat_tasca term meta & admin UI
// ---------------------------------------------------------------------------

/**
 * Register term meta for estat_tasca column ordering and behaviour flags.
 *
 * - order       (int)  : column position, lowest first (default 99).
 * - collapsible (bool) : when true the column collapses to a narrow strip if it
 *                        has no tasks. All columns are collapsible by default.
 * - is_terminal (bool) : marks final/terminal states (e.g. Feta, No es farà).
 *                        Terminal columns are grouped together in a single stacked
 *                        column on the board so they take up less horizontal space.
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
	register_term_meta(
		'estat_tasca',
		'collapsible',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => false,
		)
	);
	register_term_meta(
		'estat_tasca',
		'is_terminal',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
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
	<div class="form-field">
		<label for="estat_tasca_collapsible">
			<input type="checkbox" name="estat_tasca_collapsible" id="estat_tasca_collapsible" value="1" checked="checked">
			<?php esc_html_e( 'Replegable quan buida', 'softcatala' ); ?>
		</label>
		<p><?php esc_html_e( 'Si no té tasques, la columna es mostra com una tira estreta al tauler.', 'softcatala' ); ?></p>
	</div>
	<div class="form-field">
		<label for="estat_tasca_is_terminal">
			<input type="checkbox" name="estat_tasca_is_terminal" id="estat_tasca_is_terminal" value="1">
			<?php esc_html_e( 'Estat terminal', 'softcatala' ); ?>
		</label>
		<p><?php esc_html_e( 'Els estats terminals s\'apilen en una sola columna al costat dret del tauler.', 'softcatala' ); ?></p>
	</div>
	<?php
}

/**
 * Add the "Ordre" field to the Edit estat_tasca form.
 *
 * @param WP_Term $term The term being edited.
 */
function sc_estat_tasca_edit_order_field( $term ) {
	$order       = (int) get_term_meta( $term->term_id, 'order', true );
	$collapsible = get_term_meta( $term->term_id, 'collapsible', true );
	$is_terminal = get_term_meta( $term->term_id, 'is_terminal', true );
	// Default for collapsible: true when the meta has never been saved (empty string).
	$collapsible_checked = ( '' === $collapsible || filter_var( $collapsible, FILTER_VALIDATE_BOOLEAN ) ) ? 'checked="checked"' : '';
	$terminal_checked    = filter_var( $is_terminal, FILTER_VALIDATE_BOOLEAN ) ? 'checked="checked"' : '';
	?>
	<tr class="form-field">
		<th scope="row"><label for="estat_tasca_order"><?php esc_html_e( 'Ordre', 'softcatala' ); ?></label></th>
		<td>
			<input type="number" name="estat_tasca_order" id="estat_tasca_order" value="<?php echo esc_attr( $order ); ?>" min="0" step="1">
			<p class="description"><?php esc_html_e( 'Ordre de la columna al tauler kanban (els valors més baixos apareixen primer).', 'softcatala' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="estat_tasca_collapsible"><?php esc_html_e( 'Replegable quan buida', 'softcatala' ); ?></label></th>
		<td>
			<input type="checkbox" name="estat_tasca_collapsible" id="estat_tasca_collapsible" value="1" <?php echo $collapsible_checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<p class="description"><?php esc_html_e( 'Si no té tasques, la columna es mostra com una tira estreta al tauler.', 'softcatala' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="estat_tasca_is_terminal"><?php esc_html_e( 'Estat terminal', 'softcatala' ); ?></label></th>
		<td>
			<input type="checkbox" name="estat_tasca_is_terminal" id="estat_tasca_is_terminal" value="1" <?php echo $terminal_checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<p class="description"><?php esc_html_e( 'Els estats terminals s\'apilen en una sola columna al costat dret del tauler.', 'softcatala' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Save the "ordre", "collapsible" and "is_terminal" term meta when an
 * estat_tasca term is created or edited.
 *
 * @param int $term_id The term ID.
 */
function sc_save_estat_tasca_order_meta( $term_id ) {
	if ( isset( $_POST['estat_tasca_order'] ) ) {
		update_term_meta( $term_id, 'order', absint( $_POST['estat_tasca_order'] ) );
	}
	// Checkboxes are absent from POST when unchecked, so treat absence as false.
	update_term_meta( $term_id, 'collapsible', isset( $_POST['estat_tasca_collapsible'] ) ? 1 : 0 );
	update_term_meta( $term_id, 'is_terminal', isset( $_POST['estat_tasca_is_terminal'] ) ? 1 : 0 );
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
 * Canonical list of estat_tasca terms: slug => [ name, order, collapsible, is_terminal ].
 *
 * Used by both the theme-activation seeder and the WP-CLI upsert command so
 * that a single change here is reflected everywhere.
 *
 * @return array
 */
function sc_estat_tasca_defaults() {
	return array(
		//                    name           order  collapsible  is_terminal
		'propostes'  => array( 'Propostes',   1,     true,        false ),
		'pendent'    => array( 'Pendent',     2,     true,        false ),
		'bloquejada' => array( 'Bloquejada',  3,     true,        false ),
		'en-curs'    => array( 'En curs',     4,     true,        false ),
		'feta'       => array( 'Feta',        5,     false,       true  ),
		'no-es-fara' => array( 'No es farà',  6,     false,       true  ),
	);
}

/**
 * Seed the default estat_tasca terms on theme activation.
 * Only inserts terms if none exist yet (safe to call on first activation).
 * For already-seeded sites use `wp sc seed-estats` which upserts.
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

	foreach ( sc_estat_tasca_defaults() as $slug => $data ) {
		list( $name, $order ) = $data;
		$result = wp_insert_term( $name, 'estat_tasca', array( 'slug' => $slug ) );
		if ( ! is_wp_error( $result ) ) {
			update_term_meta( $result['term_id'], 'order', $order );
			update_term_meta( $result['term_id'], 'collapsible', isset( $data[2] ) ? (int) $data[2] : 1 );
			update_term_meta( $result['term_id'], 'is_terminal', isset( $data[3] ) ? (int) $data[3] : 0 );
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
