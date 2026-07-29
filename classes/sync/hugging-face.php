<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Sync;

/**
 * Syncs the 'dadesobertes' post type from the Softcatalà organization
 * on Hugging Face, which acts as the source of truth.
 *
 * Datasets published under https://huggingface.co/softcatala are mirrored
 * as posts: new datasets create posts, existing ones are updated and posts
 * with no matching dataset are sent to the trash. The Catalan description
 * is read from a "Descripció (ca)" heading in the dataset card (README.md).
 */
class HuggingFace {

	const HF_ORG        = 'softcatala';
	const API_BASE      = 'https://huggingface.co';
	const META_REPO_ID  = 'hf_repo_id';
	const POST_TYPE     = 'dadesobertes';

	/**
	 * ACF field keys, defined in acf-json/group_61521a37043b3.json
	 */
	const FIELD_NAMEDTS      = 'field_61d6daabbc666';
	const FIELD_DESCRIPTION  = 'field_61521a8fdb76b';
	const FIELD_CREATOR      = 'field_61521b15f2536';
	const FIELD_DOWNLOAD_URL = 'field_61521aacdb76c';
	const FIELD_LICENSE      = 'field_61521b94f2539';

	/**
	 * Hugging Face license identifiers mapped to display name and URL.
	 *
	 * @var array
	 */
	private static $licenses = array(
		'apache-2.0'     => array( 'Apache License 2.0', 'https://www.apache.org/licenses/LICENSE-2.0' ),
		'mit'            => array( 'MIT License', 'https://opensource.org/licenses/MIT' ),
		'gpl-2.0'        => array( 'GNU General Public License v2.0', 'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html' ),
		'gpl-3.0'        => array( 'GNU General Public License v3.0', 'https://www.gnu.org/licenses/gpl-3.0.html' ),
		'agpl-3.0'       => array( 'GNU Affero General Public License v3.0', 'https://www.gnu.org/licenses/agpl-3.0.html' ),
		'lgpl-3.0'       => array( 'GNU Lesser General Public License v3.0', 'https://www.gnu.org/licenses/lgpl-3.0.html' ),
		'bsd-3-clause'   => array( 'BSD 3-Clause License', 'https://opensource.org/licenses/BSD-3-Clause' ),
		'cc0-1.0'        => array( 'CC0 1.0 (domini públic)', 'https://creativecommons.org/publicdomain/zero/1.0/' ),
		'cc-by-4.0'      => array( 'Creative Commons BY 4.0', 'https://creativecommons.org/licenses/by/4.0/' ),
		'cc-by-sa-3.0'   => array( 'Creative Commons BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/' ),
		'cc-by-sa-4.0'   => array( 'Creative Commons BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/' ),
		'cc-by-nc-4.0'   => array( 'Creative Commons BY-NC 4.0', 'https://creativecommons.org/licenses/by-nc/4.0/' ),
		'cc-by-nc-sa-4.0' => array( 'Creative Commons BY-NC-SA 4.0', 'https://creativecommons.org/licenses/by-nc-sa/4.0/' ),
		'cc-by-nd-4.0'   => array( 'Creative Commons BY-ND 4.0', 'https://creativecommons.org/licenses/by-nd/4.0/' ),
		'odc-by'         => array( 'Open Data Commons Attribution License', 'https://opendatacommons.org/licenses/by/1-0/' ),
		'odbl'           => array( 'Open Data Commons Open Database License', 'https://opendatacommons.org/licenses/odbl/1-0/' ),
		'unlicense'      => array( 'The Unlicense', 'https://unlicense.org/' ),
	);

	/**
	 * Runs a full sync: create/update posts from Hugging Face datasets and
	 * trash the posts that no longer have a matching dataset.
	 *
	 * @param bool $dry_run When true, report what would happen without writing.
	 * @return array {success: bool, message: string, items: array}
	 */
	public function sync( $dry_run = false ) {
		$datasets = $this->fetch_datasets();

		if ( is_wp_error( $datasets ) ) {
			return array(
				'success' => false,
				'message' => $datasets->get_error_message(),
				'items'   => array(),
			);
		}

		if ( empty( $datasets ) ) {
			return array(
				'success' => false,
				'message' => 'Hugging Face returned zero datasets: aborting sync to avoid trashing all posts',
				'items'   => array(),
			);
		}

		$existing = get_posts(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'numberposts' => -1,
			)
		);

		$by_repo = array();
		$by_slug = array();
		foreach ( $existing as $post ) {
			$repo_id = get_post_meta( $post->ID, self::META_REPO_ID, true );
			if ( $repo_id ) {
				$by_repo[ $repo_id ] = $post;
			}
			$by_slug[ $post->post_name ] = $post;
		}

		$items      = array();
		$synced_ids = array();
		$created    = 0;
		$updated    = 0;
		$trashed    = 0;
		$failed     = 0;

		foreach ( $datasets as $dataset ) {
			$result  = $this->sync_dataset( $dataset, $by_repo, $by_slug, $dry_run );
			$items[] = $result;

			if ( ! $result['success'] ) {
				$failed++;
				continue;
			}

			if ( ! empty( $result['post_id'] ) ) {
				$synced_ids[] = $result['post_id'];
			}

			if ( 'created' === $result['action'] ) {
				$created++;
			} else {
				$updated++;
			}
		}

		// Posts with no matching dataset on Hugging Face are trashed. Skip
		// this when any dataset failed to sync, so a partial API failure
		// cannot wipe otherwise valid posts.
		if ( 0 === $failed ) {
			foreach ( $existing as $post ) {
				if ( in_array( $post->ID, $synced_ids, true ) ) {
					continue;
				}

				if ( $dry_run ) {
					$items[] = array(
						'success' => true,
						'action'  => 'trashed',
						'post_id' => $post->ID,
						'message' => "[DRY RUN] Would trash '{$post->post_name}' (no matching dataset on Hugging Face)",
					);
				} else {
					wp_trash_post( $post->ID );
					$items[] = array(
						'success' => true,
						'action'  => 'trashed',
						'post_id' => $post->ID,
						'message' => "Trashed '{$post->post_name}' (no matching dataset on Hugging Face)",
					);
				}
				$trashed++;
			}
		}

		$prefix  = $dry_run ? '[DRY RUN] ' : '';
		$message = sprintf(
			'%sHugging Face sync: %d created, %d updated, %d trashed, %d failed',
			$prefix,
			$created,
			$updated,
			$trashed,
			$failed
		);

		return array(
			'success' => 0 === $failed,
			'message' => $message,
			'items'   => $items,
		);
	}

	/**
	 * Fetches all datasets of the organization from the Hugging Face API.
	 *
	 * @return array|\WP_Error
	 */
	public function fetch_datasets() {
		$url      = self::API_BASE . '/api/datasets?author=' . self::HF_ORG . '&full=true&limit=1000';
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new \WP_Error( 'hf_api_error', "Hugging Face API returned HTTP {$code} for {$url}" );
		}

		$datasets = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $datasets ) ) {
			return new \WP_Error( 'hf_api_error', 'Hugging Face API returned invalid JSON' );
		}

		return $datasets;
	}

	/**
	 * Fetches the dataset card (README.md) for a repository.
	 *
	 * @param string $repo_id e.g. "softcatala/catalan-youtube-speech".
	 * @return string|null
	 */
	public function fetch_readme( $repo_id ) {
		$url      = self::API_BASE . '/datasets/' . $repo_id . '/raw/main/README.md';
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Extracts the Catalan description from a dataset card.
	 *
	 * Looks for a markdown heading (any level) containing "Descripció (ca)"
	 * and returns the content up to the next heading of the same or a
	 * shallower level.
	 *
	 * @param string $markdown Full README.md content.
	 * @return string|null Markdown of the section, or null when not found.
	 */
	public static function extract_catalan_description( $markdown ) {
		$markdown = str_replace( "\r\n", "\n", (string) $markdown );

		// Strip the YAML front matter of the dataset card.
		$markdown = preg_replace( '/\A---\n.*?\n---\n/s', '', $markdown );

		if ( ! preg_match( '/^(#{1,6})[^\n#]*Descripció \(ca\)[^\n]*$/mu', $markdown, $matches, PREG_OFFSET_CAPTURE ) ) {
			return null;
		}

		$level   = strlen( $matches[1][0] );
		$offset  = $matches[0][1] + strlen( $matches[0][0] );
		$section = substr( $markdown, $offset );

		if ( preg_match( '/^#{1,' . $level . '}\s/m', $section, $next, PREG_OFFSET_CAPTURE ) ) {
			$section = substr( $section, 0, $next[0][1] );
		}

		$section = trim( $section );

		return '' === $section ? null : $section;
	}

	/**
	 * Converts the small subset of markdown used in dataset cards to HTML:
	 * headings, lists, paragraphs, links, bold, italics and inline code.
	 *
	 * @param string $markdown Markdown source.
	 * @return string HTML.
	 */
	public static function markdown_to_html( $markdown ) {
		$markdown = trim( str_replace( "\r\n", "\n", (string) $markdown ) );
		if ( '' === $markdown ) {
			return '';
		}

		$blocks = preg_split( '/\n{2,}/', $markdown );
		$html   = array();

		foreach ( $blocks as $block ) {
			$block = trim( $block );
			if ( '' === $block ) {
				continue;
			}

			if ( preg_match( '/^(#{1,6})\s+(.+)$/', $block, $matches ) ) {
				$level  = strlen( $matches[1] );
				$html[] = "<h{$level}>" . self::inline_markdown( $matches[2] ) . "</h{$level}>";
				continue;
			}

			$lines = preg_split( '/\n/', $block );

			$is_ul = ! array_filter( $lines, function ( $line ) {
				return ! preg_match( '/^\s*[-*+]\s+/', $line );
			} );
			$is_ol = ! array_filter( $lines, function ( $line ) {
				return ! preg_match( '/^\s*\d+[.)]\s+/', $line );
			} );

			if ( $is_ul || $is_ol ) {
				$tag   = $is_ul ? 'ul' : 'ol';
				$item  = $is_ul ? '/^\s*[-*+]\s+/' : '/^\s*\d+[.)]\s+/';
				$parts = array_map( function ( $line ) use ( $item ) {
					return '<li>' . self::inline_markdown( preg_replace( $item, '', $line ) ) . '</li>';
				}, $lines );

				$html[] = "<{$tag}>" . implode( '', $parts ) . "</{$tag}>";
				continue;
			}

			$html[] = '<p>' . self::inline_markdown( implode( ' ', $lines ) ) . '</p>';
		}

		return implode( "\n", $html );
	}

	/**
	 * Maps a Hugging Face license identifier to a name and URL.
	 *
	 * @param string|array $license License id from cardData (e.g. "cc-by-4.0").
	 * @param string       $dataset_url URL of the dataset page, used as the license
	 *                     URL when the dataset declares custom terms ("other").
	 * @return array|null {license_name: string, license_url: string}
	 */
	public static function get_license_info( $license, $dataset_url = '' ) {
		if ( is_array( $license ) ) {
			$license = reset( $license );
		}

		$license = strtolower( trim( (string) $license ) );
		if ( '' === $license ) {
			return null;
		}

		if ( 'other' === $license ) {
			return array(
				'license_name' => 'Altres condicions',
				'license_url'  => $dataset_url,
			);
		}

		if ( isset( self::$licenses[ $license ] ) ) {
			return array(
				'license_name' => self::$licenses[ $license ][0],
				'license_url'  => self::$licenses[ $license ][1],
			);
		}

		return array(
			'license_name' => strtoupper( $license ),
			'license_url'  => '',
		);
	}

	/**
	 * Creates or updates the post for a single dataset.
	 *
	 * @param array $dataset Dataset entry from the Hugging Face API.
	 * @param array $by_repo Existing posts indexed by hf_repo_id meta.
	 * @param array $by_slug Existing posts indexed by post slug.
	 * @param bool  $dry_run When true, report without writing.
	 * @return array {success: bool, action: string, post_id: int|null, message: string}
	 */
	private function sync_dataset( $dataset, $by_repo, $by_slug, $dry_run ) {
		$repo_id = isset( $dataset['id'] ) ? $dataset['id'] : '';

		if ( ! $repo_id ) {
			return array(
				'success' => false,
				'action'  => 'skipped',
				'post_id' => null,
				'message' => 'Dataset entry without id, skipped',
			);
		}

		$name  = substr( $repo_id, strpos( $repo_id, '/' ) + 1 );
		$slug  = sanitize_title( $name );
		$card  = isset( $dataset['cardData'] ) && is_array( $dataset['cardData'] ) ? $dataset['cardData'] : array();
		$title = ! empty( $card['pretty_name'] ) ? $card['pretty_name'] : $name;

		$readme         = $this->fetch_readme( $repo_id );
		$description_md = null === $readme ? null : self::extract_catalan_description( $readme );
		$has_catalan    = null !== $description_md;

		if ( ! $has_catalan ) {
			$description_md = isset( $dataset['description'] ) ? $dataset['description'] : '';
		}

		$content = self::markdown_to_html( $description_md );

		$post   = null;
		$action = 'created';
		if ( isset( $by_repo[ $repo_id ] ) ) {
			$post = $by_repo[ $repo_id ];
		} elseif ( isset( $by_slug[ $slug ] ) ) {
			$post = $by_slug[ $slug ];
		}
		if ( $post ) {
			$action = 'updated';
		}

		$language_note = $has_catalan ? '' : ' (no "Descripció (ca)" section found, using Hugging Face description)';

		if ( $dry_run ) {
			return array(
				'success' => true,
				'action'  => $action,
				'post_id' => $post ? $post->ID : null,
				'message' => "[DRY RUN] Would have {$action} '{$slug}' from {$repo_id}{$language_note}",
			);
		}

		$postarr = array(
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 40 ),
		);

		if ( $post ) {
			$postarr['ID'] = $post->ID;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return array(
				'success' => false,
				'action'  => $action,
				'post_id' => null,
				'message' => "Failed to save '{$slug}': " . $post_id->get_error_message(),
			);
		}

		update_post_meta( $post_id, self::META_REPO_ID, $repo_id );
		update_post_meta( $post_id, 'hf_downloads', isset( $dataset['downloads'] ) ? (int) $dataset['downloads'] : 0 );
		update_post_meta( $post_id, 'hf_likes', isset( $dataset['likes'] ) ? (int) $dataset['likes'] : 0 );
		update_post_meta( $post_id, 'hf_last_modified', isset( $dataset['lastModified'] ) ? $dataset['lastModified'] : '' );
		update_post_meta( $post_id, 'hf_created_at', isset( $dataset['createdAt'] ) ? $dataset['createdAt'] : '' );

		if ( function_exists( 'update_field' ) ) {
			update_field( self::FIELD_NAMEDTS, $title, $post_id );
			update_field( self::FIELD_DESCRIPTION, $content, $post_id );
			update_field( self::FIELD_DOWNLOAD_URL, self::API_BASE . '/datasets/' . $repo_id, $post_id );

			$license = self::get_license_info(
				isset( $card['license'] ) ? $card['license'] : '',
				self::API_BASE . '/datasets/' . $repo_id
			);
			if ( $license ) {
				update_field( self::FIELD_LICENSE, $license, $post_id );
			}

			// Authors are not available on Hugging Face: default to
			// Softcatalà but keep any manually curated list.
			if ( ! get_field( 'creator', $post_id ) ) {
				update_field(
					self::FIELD_CREATOR,
					array(
						array(
							'author_type'  => 'organization',
							'creator_name' => 'Softcatalà',
						),
					),
					$post_id
				);
			}
		}

		return array(
			'success' => true,
			'action'  => $action,
			'post_id' => $post_id,
			'message' => ucfirst( $action ) . " '{$slug}' from {$repo_id}{$language_note}",
		);
	}

	/**
	 * Converts inline markdown (links, bold, italics, code) to HTML.
	 *
	 * @param string $text Inline markdown.
	 * @return string HTML.
	 */
	private static function inline_markdown( $text ) {
		$text = esc_html( trim( $text ) );

		$text = preg_replace( '/\[([^\]]+)\]\(([^)\s]+)\)/', '<a href="$2">$1</a>', $text );
		$text = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $text );
		$text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );

		return $text;
	}
}
