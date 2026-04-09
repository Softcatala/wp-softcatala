<?php

/**
 * Seed default estat_tasca terms for sites where the theme was already active
 * before the task management feature was deployed.
 *
 * Usage: wp sc seed-estats
 */
class Seed_Estat_Tasca_Command extends WP_CLI_Command {

	/**
	 * Seed the default estat_tasca column terms.
	 *
	 * Only inserts terms if none exist yet. Safe to run on already-seeded sites.
	 *
	 * ## EXAMPLES
	 *
	 *     # Seed default kanban columns
	 *     wp sc seed-estats
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function __invoke( $args, $assoc_args ) {
		$existing = get_terms(
			array(
				'taxonomy'   => 'estat_tasca',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( ! empty( $existing ) && ! is_wp_error( $existing ) ) {
			WP_CLI::success( sprintf( '%d estat_tasca term(s) already exist — skipping seed.', count( $existing ) ) );
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
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( sprintf( 'Could not insert term "%s": %s', $name, $result->get_error_message() ) );
			} else {
				update_term_meta( $result['term_id'], 'order', $order );
				WP_CLI::log( sprintf( 'Created "%s" (order: %d)', $name, $order ) );
			}
		}

		WP_CLI::success( 'Default estat_tasca terms seeded.' );
	}
}
