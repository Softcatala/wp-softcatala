<?php
/**
 * @package SC
 */

/**
 * Handles Programes CPT
 */
class SC_Programes extends SC_TypeBase {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'programa','programes' );
	}

	/**
	 * Returns text explaining conditions to add a program
	 * @return string
	 */
	public function condicions_afegir_programa() {
		return get_option( 'sc_text_programes' );
	}

	/**
	 * Default email of the section
	 * @return type
	 */
	public function email() {
		return get_option('email_rebost');
	}
}
