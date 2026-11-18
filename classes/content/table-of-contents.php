<?php
/**
 * A page's table of contents: its second-level headings that carry an id
 * (in the block editor, a heading's "HTML anchor"). Used by the resums'
 * sidebar and the "Plantilla text amb índex" page template.
 *
 * @package Softcatala
 */

namespace Softcatala\Content;

/**
 * Class TableOfContents
 */
class TableOfContents {

	/**
	 * Entries for each <h2 id="…">, in order. A trailing count such as
	 * " (20)" is dropped: the sidebar is narrow.
	 *
	 * @param string $content Raw post content (block markup or classic HTML).
	 * @return array[] Each with id and title.
	 */
	public static function from_content( $content ) {
		if ( ! preg_match_all( '/<h2\b[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h2>/is', (string) $content, $matches, PREG_SET_ORDER ) ) {
			return array();
		}

		$toc = array();
		foreach ( $matches as $match ) {
			$toc[] = array(
				'id'    => $match[1],
				'title' => trim( preg_replace( '/\s*\(\d+\)\s*$/', '', wp_strip_all_tags( $match[2] ) ) ),
			);
		}

		return $toc;
	}
}
