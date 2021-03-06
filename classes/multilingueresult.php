<?php
/**
 * @package Softcatalà
 */

/**
 * DTO for Multilingual request
 */
class SC_MultilingueResult {

	public function __construct( $status, $result, $canonical = '', $description = '', $title = '', $content_title = '' ) {
		$this->status = $status;
		$this->html = $result;
		$this->canonical = $canonical;
		$this->description = $description;
		$this->title = $title;
		$this->content_title = $content_title;
	}

	public $status;
	public $html;
	public $canonical;
	public $description;
	public $title;
	public $content_title;
	public $result;
}
