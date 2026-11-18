<?php
/**
 * Block style "assistencia" for the core Table block. Editors build an
 * attendance table ("Qui va a què"): the first column holds names, the other
 * columns hold sessions, and each cell marks attendance with a mark (✓, Sí...)
 * optionally followed by "+N" companions ("+1", "+2 nens"). When the block
 * renders, the table footer is rewritten with the people total per session
 * and, when any companion is a child, a row with the children count.
 *
 * @package Softcatala
 */

namespace Softcatala\Blocks;

// phpcs:disable WordPress.NamingConventions.ValidVariableName -- DOM API property names (textContent, childNodes...) are camelCase.
/**
 * Computes attendance totals for the assistencia table style.
 */
class AttendanceTable {

	const STYLE_CLASS = 'is-style-assistencia';

	const TOTAL_CLASS = 'sc-assistencia-total';

	const INFANTS_CLASS = 'sc-assistencia-infants';

	/**
	 * Normalised cell texts (lowercase) that count as a single attendee.
	 */
	const MARKS = array( '✓', '✔', '✅', 'x', 'sí', 'si' );

	/**
	 * A "+N" companion token, optionally followed by a children word.
	 */
	const TOKEN_PATTERN = '/\+\s*(\d+)(?:\s*(nen(?:a|s|es)?|xiquet(?:a|s|es)?|infants?)\b)?/iu';

	public static function register() {
		add_filter( 'render_block_core/table', array( self::class, 'filter' ), 10, 2 );
	}

	/**
	 * Adds the attendance totals to tables that use the assistencia style.
	 *
	 * @param string $html  Rendered block HTML.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public static function filter( $html, $block ) {
		$class_name = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		$tokens     = preg_split( '/\s+/', trim( $class_name ), -1, PREG_SPLIT_NO_EMPTY );

		if ( ! in_array( self::STYLE_CLASS, $tokens, true ) ) {
			return $html;
		}

		return self::apply_totals( $html );
	}

	/**
	 * Counts the people represented by one cell.
	 *
	 * @param string $text Cell inner HTML or text.
	 * @return array { people: int, children: int }
	 */
	public static function count_cell( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = self::clean( $text );

		$people   = 0;
		$children = 0;

		$rest = preg_replace_callback(
			self::TOKEN_PATTERN,
			function ( $match ) use ( &$people, &$children ) {
				$n         = (int) $match[1];
				$people   += $n;
				if ( ! empty( $match[2] ) ) {
					$children += $n;
				}
				return ' ';
			},
			$text
		);

		$rest = self::clean( (string) $rest );

		if ( '' === $rest ) {
			$base = 0;
		} elseif ( in_array( mb_strtolower( $rest, 'UTF-8' ), self::MARKS, true ) ) {
			$base = 1;
		} else {
			// Anything else (e.g. "sis", "potser", "?") is not an attendance
			// cell, so its companions are not counted either.
			return array(
				'people' => 0,
				'children' => 0,
			);
		}

		return array(
			'people' => $base + $people,
			'children' => $children,
		);
	}

	/**
	 * Rewrites the table footer with the totals computed from the body rows.
	 *
	 * @param string $html Table block HTML.
	 * @return string The updated HTML, or $html unchanged when it has no table body.
	 */
	public static function apply_totals( $html ) {
		$doc      = new \DOMDocument( '1.0', 'UTF-8' );
		$previous = libxml_use_internal_errors( true );
		$doc->loadHTML(
			'<?xml encoding="utf-8" ?><div>' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$wrapper = $doc->getElementsByTagName( 'div' )->item( 0 );
		if ( null === $wrapper ) {
			return $html;
		}

		$table = $wrapper->getElementsByTagName( 'table' )->item( 0 );
		if ( null === $table ) {
			return $html;
		}

		$tbodies = self::child_elements( $table, 'tbody' );
		if ( empty( $tbodies ) ) {
			return $html;
		}

		$body_rows = array();
		foreach ( $tbodies as $tbody ) {
			foreach ( self::child_elements( $tbody, 'tr' ) as $tr ) {
				$body_rows[] = $tr;
			}
		}

		$head_rows = array();
		foreach ( self::child_elements( $table, 'thead' ) as $thead ) {
			foreach ( self::child_elements( $thead, 'tr' ) as $tr ) {
				$head_rows[] = $tr;
			}
		}

		// Column 0 is the label column and is never counted.
		$columns = 0;
		foreach ( array_merge( $head_rows, $body_rows ) as $tr ) {
			$columns = max( $columns, count( self::cells( $tr ) ) );
		}

		$people   = array();
		$children = array();
		for ( $i = 1; $i < $columns; $i++ ) {
			$people[ $i ]   = 0;
			$children[ $i ] = 0;
		}

		foreach ( $body_rows as $tr ) {
			$cells = self::cells( $tr );
			for ( $i = 1; $i < $columns && isset( $cells[ $i ] ); $i++ ) {
				$count         = self::count_cell( $cells[ $i ]->textContent );
				$people[ $i ]   += $count['people'];
				$children[ $i ] += $count['children'];
			}
		}

		// Cell templates for copying class and data-align into new cells.
		$template_cells = array();
		if ( ! empty( $body_rows ) ) {
			$template_cells = self::cells( $body_rows[0] );
		}

		$tfoot = null;
		$tfoots = self::child_elements( $table, 'tfoot' );
		if ( ! empty( $tfoots ) ) {
			$tfoot = $tfoots[0];
		}

		$total_row = null;
		if ( null !== $tfoot ) {
			$foot_rows = self::child_elements( $tfoot, 'tr' );
			if ( ! empty( $foot_rows ) ) {
				$total_row = $foot_rows[0];
			}
		}

		if ( null === $total_row ) {
			if ( null === $tfoot ) {
				$tfoot = $doc->createElement( 'tfoot' );
				$last_tbody = end( $tbodies );
				$table->insertBefore( $tfoot, $last_tbody->nextSibling );
			}
			$total_row = $doc->createElement( 'tr' );
			$tfoot->appendChild( $total_row );
		}

		self::add_class( $total_row, self::TOTAL_CLASS );

		$label = self::ensure_cell( $doc, $total_row, 0, $template_cells );
		if ( '' === trim( $label->textContent ) ) {
			$label->textContent = 'Total';
		}

		for ( $i = 1; $i < $columns; $i++ ) {
			$cell              = self::ensure_cell( $doc, $total_row, $i, $template_cells );
			$cell->textContent = (string) $people[ $i ];
		}

		// Children row.
		$infants_rows = array();
		foreach ( self::child_elements( $tfoot, 'tr' ) as $tr ) {
			if ( self::has_class( $tr, self::INFANTS_CLASS ) ) {
				$infants_rows[] = $tr;
			}
		}

		$has_children = array_sum( $children ) > 0;

		if ( $has_children ) {
			if ( empty( $infants_rows ) ) {
				$infants_row = $doc->createElement( 'tr' );
				$tfoot->insertBefore( $infants_row, $total_row->nextSibling );
			} else {
				$infants_row = array_shift( $infants_rows );
			}
			// Drop any duplicates left behind.
			foreach ( $infants_rows as $stale ) {
				$stale->parentNode->removeChild( $stale );
			}

			self::add_class( $infants_row, self::INFANTS_CLASS );
			foreach ( self::cells( $infants_row ) as $cell ) {
				$infants_row->removeChild( $cell );
			}

			$label              = self::new_cell( $doc, $infants_row, $template_cells, 0 );
			$label->textContent = 'dels quals, infants';

			for ( $i = 1; $i < $columns; $i++ ) {
				$cell              = self::new_cell( $doc, $infants_row, $template_cells, $i );
				$cell->textContent = $children[ $i ] > 0 ? (string) $children[ $i ] : '';
			}
		} else {
			foreach ( $infants_rows as $stale ) {
				$stale->parentNode->removeChild( $stale );
			}
		}

		$out = '';
		foreach ( $wrapper->childNodes as $child ) {
			$out .= $doc->saveHTML( $child );
		}

		return $out;
	}

	/**
	 * Normalises whitespace and invisible characters, without trimming tags.
	 *
	 * @param string $text Text to clean.
	 * @return string
	 */
	private static function clean( $text ) {
		$text = str_replace( array( "\u{00A0}", "\u{202F}" ), ' ', $text );
		$text = str_replace( "\u{FE0F}", '', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( (string) $text );
	}

	/**
	 * Direct child elements of a node with the given tag name.
	 *
	 * @param \DOMNode $parent Parent node.
	 * @param string   $name   Lowercase tag name.
	 * @return \DOMElement[]
	 */
	private static function child_elements( \DOMNode $parent, $name ) {
		$elements = array();
		foreach ( $parent->childNodes as $child ) {
			if ( $child instanceof \DOMElement && $name === $child->nodeName ) {
				$elements[] = $child;
			}
		}
		return $elements;
	}

	/**
	 * Direct td/th children of a row, in order.
	 *
	 * @param \DOMNode $row Table row.
	 * @return \DOMElement[]
	 */
	private static function cells( \DOMNode $row ) {
		$cells = array();
		foreach ( $row->childNodes as $child ) {
			if ( $child instanceof \DOMElement && ( 'td' === $child->nodeName || 'th' === $child->nodeName ) ) {
				$cells[] = $child;
			}
		}
		return $cells;
	}

	/**
	 * Returns the cell at $index, creating and appending it when missing.
	 *
	 * @param \DOMDocument $doc       Document.
	 * @param \DOMNode     $row       Row to take the cell from.
	 * @param int          $index     Cell index.
	 * @param array        $templates Cells of the first body row.
	 * @return \DOMElement
	 */
	private static function ensure_cell( \DOMDocument $doc, \DOMNode $row, $index, array $templates ) {
		$cells = self::cells( $row );
		if ( isset( $cells[ $index ] ) ) {
			return $cells[ $index ];
		}
		return self::new_cell( $doc, $row, $templates, $index );
	}

	/**
	 * Appends a new td to a row, copying class and data-align from the template
	 * cell at the same index.
	 *
	 * @param \DOMDocument $doc       Document.
	 * @param \DOMNode     $row       Row to append to.
	 * @param array        $templates Cells of the first body row.
	 * @param int          $index     Cell index.
	 * @return \DOMElement
	 */
	private static function new_cell( \DOMDocument $doc, \DOMNode $row, array $templates, $index ) {
		$cell = $doc->createElement( 'td' );
		if ( isset( $templates[ $index ] ) ) {
			foreach ( array( 'class', 'data-align' ) as $attribute ) {
				if ( $templates[ $index ]->hasAttribute( $attribute ) ) {
					$cell->setAttribute( $attribute, $templates[ $index ]->getAttribute( $attribute ) );
				}
			}
		}
		$row->appendChild( $cell );
		return $cell;
	}

	/**
	 * @param \DOMElement $node  Element.
	 * @param string      $class Class token to add (no duplicates).
	 */
	private static function add_class( \DOMElement $node, $class ) {
		if ( self::has_class( $node, $class ) ) {
			return;
		}
		$tokens   = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );
		$tokens[] = $class;
		$node->setAttribute( 'class', implode( ' ', $tokens ) );
	}

	/**
	 * @param \DOMElement $node  Element.
	 * @param string      $class Class token.
	 * @return bool
	 */
	private static function has_class( \DOMElement $node, $class ) {
		$tokens = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );
		return in_array( $class, $tokens, true );
	}
}
// phpcs:enable WordPress.NamingConventions.ValidVariableName
