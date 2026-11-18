<?php

use Softcatala\Blocks\ContentAudit;
use Softcatala\Blocks\Editor;

/**
 * Block editor migration tools.
 *
 * Usage: wp sc blocks audit
 */
class Blocks_Command extends WP_CLI_Command {

	/**
	 * Posts fetched per query.
	 */
	const BATCH = 200;

	/**
	 * Audits content for what the block editor migration still has to handle.
	 *
	 * Reports classic versus block content, shortcodes in use (WordPress's and
	 * the theme's), sc-footnotes "[1. …]" notes, legacy classes with a block
	 * equivalent, leftover MediaWiki and pasted markup, inline styles, and
	 * blocks outside the editor's allowed list. Read-only.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<types>]
	 * : Comma-separated post types, or `any` for every type with an editor.
	 * Default: the post types that use the block editor.
	 *
	 * [--post_status=<statuses>]
	 * : Comma-separated statuses.
	 * ---
	 * default: publish,private,draft,pending,future
	 * ---
	 *
	 * [--per-post]
	 * : One row per post with findings, instead of the summary.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp sc blocks audit
	 *     wp sc blocks audit --post_type=any
	 *     wp sc blocks audit --post_type=page --per-post --format=csv > pages.csv
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 */
	public function audit( $args, $assoc_args ) {
		$format     = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$per_post   = WP_CLI\Utils\get_flag_value( $assoc_args, 'per-post', false );
		$post_types = $this->post_types( WP_CLI\Utils\get_flag_value( $assoc_args, 'post_type', '' ) );
		$statuses   = array_map( 'trim', explode( ',', WP_CLI\Utils\get_flag_value( $assoc_args, 'post_status', 'publish,private,draft,pending,future' ) ) );

		$audit   = ContentAudit::for_site();
		$summary = array();
		$rows    = array();

		foreach ( $this->posts( $post_types, $statuses ) as $post ) {
			$result  = $audit->audit( $post->post_content );
			$summary = ContentAudit::add_to_summary( $summary, $post->post_type, $result );

			if ( $per_post && ContentAudit::has_findings( $result ) ) {
				$rows[] = $this->row( $post, $result );
			}
		}

		if ( $per_post ) {
			WP_CLI\Utils\format_items( $format, $rows, array( 'ID', 'post_type', 'post_status', 'title', 'format', 'shortcodes', 'footnotes', 'classes', 'inline_styles', 'blocks_not_allowed', 'classic_blocks', 'html_blocks', 'url' ) );
			return;
		}

		$this->print_summary( $summary, $format );
	}

	/**
	 * @param string $option --post_type value.
	 * @return string[]
	 */
	private function post_types( $option ) {
		if ( '' === $option ) {
			return Editor::config( 'post_types' );
		}

		if ( 'any' === $option ) {
			return array_values( get_post_types_by_support( 'editor' ) );
		}

		return array_map( 'trim', explode( ',', $option ) );
	}

	/**
	 * Posts in batches, so large sites don't load everything at once.
	 *
	 * @param string[] $post_types Post types.
	 * @param string[] $statuses   Statuses.
	 * @return Generator<WP_Post>
	 */
	private function posts( array $post_types, array $statuses ) {
		$page = 1;

		do {
			$posts = get_posts(
				array(
					'post_type'        => $post_types,
					'post_status'      => $statuses,
					'posts_per_page'   => self::BATCH,
					'paged'            => $page,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			);

			foreach ( $posts as $post ) {
				yield $post;
			}

			++$page;
		} while ( count( $posts ) === self::BATCH );
	}

	/**
	 * @param WP_Post $post   Post.
	 * @param array   $result Audit result.
	 * @return array
	 */
	private function row( $post, array $result ) {
		$list = function ( array $counts ) {
			$items = array();
			foreach ( $counts as $name => $uses ) {
				$items[] = $uses > 1 ? "$name×$uses" : $name;
			}
			return implode( ' ', $items );
		};

		return array(
			'ID'                 => $post->ID,
			'post_type'          => $post->post_type,
			'post_status'        => $post->post_status,
			'title'              => $post->post_title,
			'format'             => $result['format'],
			'shortcodes'         => $list( $result['shortcodes'] ),
			'footnotes'          => $result['footnotes'],
			'classes'            => $list( $result['classes'] ),
			'inline_styles'      => $result['inline_styles'],
			'blocks_not_allowed' => $list( $result['blocks_not_allowed'] ),
			'classic_blocks'     => $result['classic_blocks'],
			'html_blocks'        => $result['html_blocks'],
			'url'                => get_permalink( $post ),
		);
	}

	/**
	 * @param array  $summary ContentAudit::add_to_summary() totals.
	 * @param string $format  Output format.
	 */
	private function print_summary( array $summary, $format ) {
		if ( empty( $summary ) ) {
			WP_CLI::warning( 'No posts matched.' );
			return;
		}

		$sections = array(
			'formats'            => array( 'Format per post type', 'post_type', array( 'posts', 'block', 'classic', 'empty' ) ),
			'shortcodes'         => array( 'Shortcodes', 'shortcode', array( 'posts', 'uses' ) ),
			'classes'            => array( 'Legacy classes and leftover markup (elements)', 'class', array( 'posts', 'uses' ) ),
			'other'              => array( 'Footnotes, inline styles, Classic and Custom HTML blocks', 'finding', array( 'posts', 'uses' ) ),
			'blocks_not_allowed' => array( 'Blocks outside the allowed list', 'block', array( 'posts', 'uses' ) ),
		);

		if ( in_array( $format, array( 'json', 'yaml' ), true ) ) {
			WP_CLI\Utils\format_items( $format, array( $summary ), array_keys( $summary ) );
			return;
		}

		foreach ( $sections as $key => list( $title, $label, $columns ) ) {
			if ( empty( $summary[ $key ] ) ) {
				continue;
			}

			$rows = array();
			foreach ( $summary[ $key ] as $name => $counts ) {
				$rows[] = array( $label => $name ) + $counts;
			}

			if ( 'table' === $format ) {
				WP_CLI::log( '' );
				WP_CLI::log( $title );
			}

			WP_CLI\Utils\format_items( $format, $rows, array_merge( array( $label ), $columns ) );
		}
	}
}
