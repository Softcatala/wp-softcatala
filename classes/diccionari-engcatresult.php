<?php
/**
 * @package Softcatalà
 */

/**
 * DTO for Engcat dicctionary result
 */
class SC_Diccionari_EngCatResult {
	/**
	 * SC_SinonimsResult constructor.
	 *
	 * @param int $int
	 * @param string $html
	 * @param string $canonical_lemma
	 * @param string $canonical
	 * @param string $title
	 * @param string $content_title
	 * @param string $result1
	 * @param string $detected_language
	 */
	public function __construct( $status, $html, $canonical_lemma, $canonical = '', $title = '', $content_title = '', $detected_language = '' ) {
		$this->status = $status;
		$this->html = str_replace("'", '’', $html);
		$this->canonical = $canonical;
		$this->title = $title;
		$this->content_title = $content_title;
		$this->canonical_lemma = $canonical_lemma;
		$this->detected_language = $detected_language;
	}

	public $status;
	public $html;
	public $canonical;
	public $canonical_lemma;
	public $title;
	public $content_title;
	public $result;
	public $detected_language;
}