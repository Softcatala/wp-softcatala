<?php
/**
 * What the block editor migration still has to handle in a piece of content:
 * classic or block format, shortcodes, sc-footnotes markup, legacy classes,
 * inline styles, and blocks outside the allowed list.
 *
 * Pure analysis, no output: `wp sc blocks audit` (wp-cli/blocks_command.php)
 * runs it over the site and prints the results.
 *
 * @package Softcatala
 */

namespace Softcatala\Blocks;

/**
 * Class ContentAudit
 */
class ContentAudit {

	/**
	 * The sc-footnotes plugin's own pattern for "[1. text]" notes.
	 */
	const FOOTNOTE_PATTERN = '/\[(\d+\..*?)\]/s';

	/**
	 * Legacy class tokens that each have (or will have) a block equivalent,
	 * reported one by one. Key: report name; value: regex for the token.
	 */
	const LEGACY_CLASSES = array(
		'prettytable'      => '/^prettytable\d*$/',
		'comparacio'       => '/^comparacio\d*$/',
		'tres_columnes'    => '/^tres_columnes\d*$/',
		'taula-2col'       => '/^taula-2col$/',
		'wikitable'        => '/^wikitable$/',
		'petit'            => '/^petit$/',
		'fons-gris'        => '/^fons-gris$/',
		'center'           => '/^center$/',
		'messagebox'       => '/^messagebox$/',
		'download_section' => '/^download_section$/',
		'alerta'           => '/^alerta$/',
		'req-banner'       => '/^req-(banner|flex)$/',
	);

	/**
	 * Leftovers with no block equivalent, reported as one bucket each: they go
	 * away when the content is converted, not by building anything.
	 */
	const DEBRIS_CLASSES = array(
		'mediawiki' => '/^(mw-.+|thumb|thumbinner|thumbcaption|magnify|tnone|tright|tleft|floatnone|floatright|floatleft|external|extiw|gallerybox|gallerytext|toc)$/',
		'pasted'    => '/^(Mso.+|css-[a-z0-9]+|r-[a-z0-9]+|gmail-.+|hiddenSpellError|hiddenGrammarError)$/',
	);

	/**
	 * Shortcode tags to look for.
	 *
	 * @var string[]
	 */
	private $shortcodes;

	/**
	 * Block names allowed in the editor.
	 *
	 * @var string[]
	 */
	private $allowed_blocks;

	/**
	 * @param string[] $shortcodes     Shortcode tags to look for.
	 * @param string[] $allowed_blocks Block names allowed in the editor.
	 */
	public function __construct( array $shortcodes, array $allowed_blocks ) {
		$this->shortcodes     = $shortcodes;
		$this->allowed_blocks = $allowed_blocks;
	}

	/**
	 * An audit for this site: every registered shortcode (WordPress's and the
	 * theme's) and the block editor's allowed blocks, Custom HTML included.
	 *
	 * @return self
	 */
	public static function for_site() {
		global $shortcode_tags, $sc_shortcode_handler;

		$shortcodes = array_keys( (array) $shortcode_tags );
		if ( $sc_shortcode_handler instanceof \SC_Shortcodes ) {
			$shortcodes = array_merge( $shortcodes, $sc_shortcode_handler->names() );
		}

		return new self(
			array_values( array_unique( $shortcodes ) ),
			array_merge( Editor::config( 'blocks' ), Editor::config( 'html_blocks' ) )
		);
	}

	/**
	 * Audits one piece of content.
	 *
	 * @param string $content Raw post_content.
	 * @return array {
	 *     @type string $format         'block', 'classic' or 'empty'.
	 *     @type array  $shortcodes     tag => uses.
	 *     @type int    $footnotes      sc-footnotes notes.
	 *     @type array  $classes        legacy class (or debris bucket) => elements.
	 *     @type int    $inline_styles  style="" attributes.
	 *     @type array  $blocks_not_allowed block name => uses.
	 *     @type int    $classic_blocks Classic (core/freeform) blocks in block content.
	 *     @type int    $html_blocks    Custom HTML blocks in block content.
	 * }
	 */
	public function audit( $content ) {
		$content = (string) $content;

		$result = array(
			'format'             => $this->format( $content ),
			'shortcodes'         => $this->shortcodes( $content ),
			'footnotes'          => preg_match_all( self::FOOTNOTE_PATTERN, $content ),
			'classes'            => $this->classes( $content ),
			'inline_styles'      => preg_match_all( '/\sstyle\s*=\s*["\']/i', $content ),
			'blocks_not_allowed' => array(),
			'classic_blocks'     => 0,
			'html_blocks'        => 0,
		);

		if ( 'block' === $result['format'] ) {
			$this->walk_blocks( parse_blocks( $content ), $result );
		}

		return $result;
	}

	/**
	 * Whether an audit found anything still to migrate.
	 *
	 * @param array $result An audit() result.
	 * @return bool
	 */
	public static function has_findings( array $result ) {
		return 'classic' === $result['format']
			|| $result['shortcodes']
			|| $result['footnotes']
			|| $result['classes']
			|| $result['inline_styles']
			|| $result['blocks_not_allowed']
			|| $result['classic_blocks']
			|| $result['html_blocks'];
	}

	/**
	 * Adds one post's audit to running totals.
	 *
	 * @param array  $summary   Totals so far (start with array()).
	 * @param string $post_type The post's type.
	 * @param array  $result    Its audit() result.
	 * @return array The updated totals.
	 */
	public static function add_to_summary( array $summary, $post_type, array $result ) {
		$summary += array(
			'formats'            => array(),
			'shortcodes'         => array(),
			'classes'            => array(),
			'blocks_not_allowed' => array(),
			'other'              => array(),
		);

		$summary['formats'][ $post_type ] = ( $summary['formats'][ $post_type ] ?? array(
			'posts'   => 0,
			'block'   => 0,
			'classic' => 0,
			'empty'   => 0,
		) );
		++$summary['formats'][ $post_type ]['posts'];
		++$summary['formats'][ $post_type ][ $result['format'] ];

		foreach ( array( 'shortcodes', 'classes', 'blocks_not_allowed' ) as $group ) {
			foreach ( $result[ $group ] as $name => $uses ) {
				$entry = $summary[ $group ][ $name ] ?? array(
					'posts' => 0,
					'uses'  => 0,
				);
				++$entry['posts'];
				$entry['uses']                  += $uses;
				$summary[ $group ][ $name ] = $entry;
			}
		}

		foreach ( array( 'footnotes', 'inline_styles', 'classic_blocks', 'html_blocks' ) as $count ) {
			if ( $result[ $count ] > 0 ) {
				$entry = $summary['other'][ $count ] ?? array(
					'posts' => 0,
					'uses'  => 0,
				);
				++$entry['posts'];
				$entry['uses']               += $result[ $count ];
				$summary['other'][ $count ] = $entry;
			}
		}

		return $summary;
	}

	/**
	 * @param string $content Raw content.
	 * @return string
	 */
	private function format( $content ) {
		if ( '' === trim( $content ) ) {
			return 'empty';
		}

		return has_blocks( $content ) ? 'block' : 'classic';
	}

	/**
	 * Uses per shortcode tag, most used first.
	 *
	 * @param string $content Raw content.
	 * @return array
	 */
	private function shortcodes( $content ) {
		if ( empty( $this->shortcodes ) || false === strpos( $content, '[' ) ) {
			return array();
		}

		preg_match_all( '/' . get_shortcode_regex( $this->shortcodes ) . '/', $content, $matches );

		$uses = array();
		foreach ( $matches[2] as $index => $tag ) {
			// [[tag]] is an escaped, literal shortcode.
			if ( '[' === $matches[1][ $index ] && ']' === $matches[6][ $index ] ) {
				continue;
			}
			$uses[ $tag ] = ( $uses[ $tag ] ?? 0 ) + 1;
		}

		arsort( $uses );
		return $uses;
	}

	/**
	 * Elements per legacy class and per debris bucket.
	 *
	 * @param string $content Raw content.
	 * @return array
	 */
	private function classes( $content ) {
		preg_match_all( '/\sclass\s*=\s*(["\'])(.*?)\1/is', $content, $matches );

		$counts = array();
		foreach ( $matches[2] as $attribute ) {
			$seen = array();
			foreach ( preg_split( '/\s+/', trim( $attribute ), -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
				foreach ( self::LEGACY_CLASSES + self::DEBRIS_CLASSES as $name => $pattern ) {
					if ( ! isset( $seen[ $name ] ) && preg_match( $pattern, $token ) ) {
						$seen[ $name ]   = true;
						$counts[ $name ] = ( $counts[ $name ] ?? 0 ) + 1;
					}
				}
			}
		}

		arsort( $counts );
		return $counts;
	}

	/**
	 * Counts disallowed, Classic and Custom HTML blocks, nested ones included.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $result Audit result, updated in place.
	 */
	private function walk_blocks( array $blocks, array &$result ) {
		foreach ( $blocks as $block ) {
			$name = $block['blockName'];

			if ( null === $name ) {
				// Whitespace between blocks, or classic HTML outside any block,
				// which the editor opens as a Classic block.
				if ( '' !== trim( $block['innerHTML'] ) ) {
					++$result['classic_blocks'];
				}
				continue;
			}

			if ( 'core/freeform' === $name ) {
				++$result['classic_blocks'];
			} elseif ( 'core/html' === $name ) {
				++$result['html_blocks'];
			}

			if ( ! in_array( $name, $this->allowed_blocks, true ) ) {
				$result['blocks_not_allowed'][ $name ] = ( $result['blocks_not_allowed'][ $name ] ?? 0 ) + 1;
			}

			$this->walk_blocks( $block['innerBlocks'], $result );
		}
	}
}
