<?php
/**
 * @package Softcatalà
 */

/**
 * DTO for Sinonims result
 */
class SC_SinonimsResult {
	/**
	 * SC_SinonimsResult constructor.
	 *
	 * @param int $int
	 * @param string $result
	 * @param string $canonical_lemma
	 * @param string $canonical
	 * @param string $title
	 * @param string $content_title
	 * @param string $result1
	 */
	public function __construct( $status, $result, $canonical_lemma, $canonical = '', $title = '', $content_title = '' ) {
		$this->status = $status;
		$this->html = $result;
		$this->canonical = $canonical;
		$this->title = $title;
		$this->content_title = $content_title;
		$this->canonical_lemma = $canonical_lemma;
	}

	public $status;
	public $html;
	public $canonical;
	public $canonical_lemma;
	public $title;
	public $content_title;
	public $result;
}