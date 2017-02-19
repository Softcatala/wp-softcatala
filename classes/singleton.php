<?php
/**
 * @package Softcatala
 */

/**
 * Class SC_Singleton
 *
 * Base class that provides singleton functionality
 * Can be removed if need to extend other classes
 */
class SC_Singleton {

	private static $instance = null;

	private function __construct() { }

	public static function get_instance() {

		if ( self::$instance == null ) {
			self::$instance = new static;
		}

		return self::$instance;
	}
}
