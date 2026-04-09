<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Providers;

/**
 * Repository to obtain Tasques for the kanban board.
 */
class Tasques {

	/**
	 * Transient key for caching the list of internal-projecte IDs.
	 */
	const TRANSIENT_INTERNAL_IDS = 'sc_internal_projecte_ids';

	/**
	 * Fetch all published tasks visible to the given visitor type, for the global board.
	 *
	 * For anonymous visitors, tasks linked to projectes with `tasques_internes = true`
	 * are excluded. For logged-in users, all published tasks are returned.
	 *
	 * @param bool $is_logged_in Whether the current visitor is authenticated.
	 * @return \WP_Post[] Array of WP_Post objects.
	 */
	public static function get_all_for_board( $is_logged_in ) {
		$args = array(
			'post_type'      => 'tasca',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
		);

		$query = new \WP_Query( $args );
		$tasks = $query->posts;

		if ( $is_logged_in ) {
			return $tasks;
		}

		// Anonymous visitors: exclude tasks from internal projectes.
		$internal_ids = self::get_internal_projecte_ids();
		if ( empty( $internal_ids ) ) {
			return $tasks;
		}

		return array_values(
			array_filter(
				$tasks,
				function ( $task ) use ( $internal_ids ) {
					$projecte = get_field( 'projecte_tasca', $task->ID );
					if ( ! $projecte ) {
						return true; // No projecte linked — show to everyone.
					}
					$projecte_id = is_array( $projecte ) ? ( $projecte['ID'] ?? 0 ) : (int) $projecte;
					return ! in_array( $projecte_id, $internal_ids, true );
				}
			)
		);
	}

	/**
	 * Group tasks into an associative array keyed by estat_tasca slug, ordered by
	 * the term `order` meta. Tasks with no estat_tasca term go to an 'unassigned' bucket.
	 *
	 * @param \WP_Post[] $tasks  Array of WP_Post objects.
	 * @param \WP_Term[] $estats Ordered list of estat_tasca terms.
	 * @return array Associative array: [ estat_slug => WP_Post[], ..., 'unassigned' => WP_Post[] ]
	 */
	public static function group_by_estat( $tasks, $estats ) {
		$grouped = array();

		// Pre-initialise buckets in term order.
		foreach ( $estats as $estat ) {
			$grouped[ $estat->slug ] = array();
		}
		$grouped['unassigned'] = array();

		foreach ( $tasks as $task ) {
			$terms = wp_get_post_terms( $task->ID, 'estat_tasca' );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				$grouped['unassigned'][] = $task;
				continue;
			}
			$slug = $terms[0]->slug;
			if ( ! array_key_exists( $slug, $grouped ) ) {
				// Term exists but wasn't in our ordered list (edge case).
				$grouped[ $slug ] = array();
			}
			$grouped[ $slug ][] = $task;
		}

		return $grouped;
	}

	/**
	 * Extract unique filter options from the task set for populating the filter bar.
	 *
	 * @param \WP_Post[] $tasks Array of WP_Post objects.
	 * @return array {
	 *     @type array $projectes  [ ['slug' => string, 'name' => string], ... ]
	 *     @type array $assignees  [ ['username' => string, 'display_name' => string], ... ]
	 *     @type array $milestones [ ['id' => int, 'title' => string], ... ]
	 *     @type array $tags       [ ['slug' => string, 'name' => string], ... ]
	 * }
	 */
	public static function get_filter_options( $tasks ) {
		$projectes  = array();
		$assignees  = array();
		$milestones = array();
		$tags       = array();

		$seen_projecte_ids  = array();
		$seen_assignee_ids  = array();
		$seen_milestone_ids = array();
		$seen_tag_ids       = array();

		foreach ( $tasks as $task ) {
			// Projecte.
			$projecte = get_field( 'projecte_tasca', $task->ID );
			if ( $projecte ) {
				$p_id = is_array( $projecte ) ? ( $projecte['ID'] ?? 0 ) : (int) $projecte;
				if ( $p_id && ! in_array( $p_id, $seen_projecte_ids, true ) ) {
					$p_post = is_array( $projecte ) ? $projecte : get_post( $p_id );
					if ( $p_post ) {
						$projectes[]          = array(
							'slug' => get_post_field( 'post_name', $p_id ),
							'name' => get_the_title( $p_id ),
						);
						$seen_projecte_ids[] = $p_id;
					}
				}
			}

			// Assignees (user field, possibly multiple).
			$responsables = get_field( 'responsable_tasca', $task->ID );
			if ( $responsables ) {
				if ( ! is_array( $responsables ) ) {
					$responsables = array( $responsables );
				}
				foreach ( $responsables as $user ) {
					$uid = is_array( $user ) ? ( $user['ID'] ?? 0 ) : (int) $user;
					if ( $uid && ! in_array( $uid, $seen_assignee_ids, true ) ) {
						$user_obj = is_array( $user ) ? (object) $user : get_userdata( $uid );
						if ( $user_obj ) {
							$assignees[]          = array(
								'username'     => is_array( $user ) ? ( $user['user_login'] ?? '' ) : $user_obj->user_login,
								'display_name' => is_array( $user ) ? ( $user['display_name'] ?? '' ) : $user_obj->display_name,
							);
							$seen_assignee_ids[] = $uid;
						}
					}
				}
			}

			// Milestone.
			$milestone = get_field( 'milestone_tasca', $task->ID );
			if ( $milestone ) {
				$m_id = is_array( $milestone ) ? ( $milestone['ID'] ?? 0 ) : (int) $milestone;
				if ( $m_id && ! in_array( $m_id, $seen_milestone_ids, true ) ) {
					$milestones[]          = array(
						'id'    => $m_id,
						'title' => get_the_title( $m_id ),
					);
					$seen_milestone_ids[] = $m_id;
				}
			}

			// Tags (tag_tasca taxonomy).
			$task_tags = wp_get_post_terms( $task->ID, 'tag_tasca' );
			if ( $task_tags && ! is_wp_error( $task_tags ) ) {
				foreach ( $task_tags as $tag ) {
					if ( ! in_array( $tag->term_id, $seen_tag_ids, true ) ) {
						$tags[]          = array(
							'slug' => $tag->slug,
							'name' => $tag->name,
						);
						$seen_tag_ids[] = $tag->term_id;
					}
				}
			}
		}

		return array(
			'projectes'  => $projectes,
			'assignees'  => $assignees,
			'milestones' => $milestones,
			'tags'       => $tags,
		);
	}

	/**
	 * Return the IDs of all projectes where `tasques_internes` is truthy.
	 * Result is cached in a 5-minute transient and invalidated on projecte save
	 * via the `sc_invalidate_internal_projecte_ids_transient` hook.
	 *
	 * @return int[] Array of projecte post IDs.
	 */
	public static function get_internal_projecte_ids() {
		$cached = get_transient( self::TRANSIENT_INTERNAL_IDS );
		if ( false !== $cached ) {
			return $cached;
		}

		$projectes = get_posts(
			array(
				'post_type'      => 'projecte',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$internal = array();
		foreach ( $projectes as $projecte_id ) {
			if ( get_field( 'tasques_internes', $projecte_id ) ) {
				$internal[] = (int) $projecte_id;
			}
		}

		set_transient( self::TRANSIENT_INTERNAL_IDS, $internal, 5 * MINUTE_IN_SECONDS );

		return $internal;
	}

	/**
	 * Get the ordered list of estat_tasca terms, sorted by the `order` term meta
	 * with term name as a secondary sort (alphabetical tiebreaker).
	 *
	 * @return \WP_Term[] Array of WP_Term objects.
	 */
	public static function get_ordered_estats() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'estat_tasca',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		// Load the order meta for each term and sort: primary by order (int), secondary by name.
		foreach ( $terms as $term ) {
			$term->board_order   = (int) get_term_meta( $term->term_id, 'order', true );
			if ( 0 === $term->board_order ) {
				$term->board_order = 99;
			}
			// collapsible defaults to true (empty string = never saved = use default).
			$collapsible = get_term_meta( $term->term_id, 'collapsible', true );
			$term->collapsible = ( '' === $collapsible ) ? true : (bool) $collapsible;
			$term->is_terminal = (bool) get_term_meta( $term->term_id, 'is_terminal', true );
		}

		usort(
			$terms,
			function ( $a, $b ) {
				if ( $a->board_order !== $b->board_order ) {
					return $a->board_order <=> $b->board_order;
				}
				return strcasecmp( $a->name, $b->name );
			}
		);

		return $terms;
	}
}
