<?php

namespace Softcatala\Content;

/**
 * View model for the per-letter word index (conjugador, sinònims, engcat).
 *
 * Passed to ajax/lletra.twig as $index.
 */
class LletraIndex {

	/** @var string  Single uppercase letter, e.g. "A" */
	public string $letter;

	/** @var string  URL prefix for letter links, e.g. "/conjugador-de-verbs/lletra" */
	public string $letter_url_prefix;

	/**
	 * @var array  List of words: each entry is ['text' => string, 'url' => string]
	 */
	public array $words;

	public function __construct( string $letter, string $letter_url_prefix, array $words ) {
		$this->letter            = strtoupper( $letter );
		$this->letter_url_prefix = $letter_url_prefix;
		$this->words             = $words;
	}
}
