<?php

/**
 * Upsert default estat_tasca terms, including order meta.
 *
 * Safe to run on both fresh and already-seeded sites — inserts missing terms
 * and updates the order meta of existing ones. Uses the canonical list from
 * sc_estat_tasca_defaults() so a single change there propagates everywhere.
 *
 * Usage: wp sc seed-estats
 */
class Seed_Estat_Tasca_Command extends WP_CLI_Command {

	/**
	 * Upsert the default estat_tasca column terms and their order.
	 *
	 * Missing terms are created; existing terms have their order meta updated.
	 * Safe to run multiple times — no duplicates will be created.
	 *
	 * ## EXAMPLES
	 *
	 *     # Seed / sync default kanban columns (run after deploy)
	 *     wp sc seed-estats
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function __invoke( $args, $assoc_args ) {
		$defaults = sc_estat_tasca_defaults();

		foreach ( $defaults as $slug => $data ) {
			list( $name, $order ) = $data;

			$existing = get_term_by( 'slug', $slug, 'estat_tasca' );

			if ( $existing ) {
				update_term_meta( $existing->term_id, 'order', $order );
				WP_CLI::log( sprintf( 'Updated "%s" (order: %d)', $name, $order ) );
			} else {
				$result = wp_insert_term( $name, 'estat_tasca', array( 'slug' => $slug ) );
				if ( is_wp_error( $result ) ) {
					WP_CLI::warning( sprintf( 'Could not insert term "%s": %s', $name, $result->get_error_message() ) );
				} else {
					update_term_meta( $result['term_id'], 'order', $order );
					WP_CLI::log( sprintf( 'Created "%s" (order: %d)', $name, $order ) );
				}
			}
		}

		WP_CLI::success( 'estat_tasca terms synced.' );
	}
}
