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

	/**
	 * Holds a reference to an instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * SC_Singleton constructor.
	 */
	private function __construct() { }

	/**
	 * Returns an instance of a given class
	 *
	 * @return null|static
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}
}
