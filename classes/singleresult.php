<?php
/**
 * @package Softcatalà
 */

/**
 * DTO for Single page ajax request
 */
class SC_SingleResult {

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
