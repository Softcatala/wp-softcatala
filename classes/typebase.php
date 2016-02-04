<?php
/**
 * @package SC
 */

/**
 * Shared functionality for all SC Custom Post Types
 */
class SC_TypeBase {

	/**
	 * Holds the Rewriter object
	 *
	 * @var type SC_Rewriter
	 */
	var $rewriter;

	/**
	 * Holds the TypeHelper object
	 *
	 * @var type SC_TypeHelper
	 */
	var $type_helper;

	/**
	 * Name (singular) of the post type
	 *
	 * @var type string
	 */
	var $singular;

	/**
	 * Constructs the object
	 *
	 * @param string $singular Singular CPT name.
	 * @param string $plural Plural CPT name.
	 */
	public function __construct($singular, $plural) {
		$this->singular = $singular;
		$this->rewriter = new SC_Rewriter( $singular,$plural );
		$this->rewriter->setup_rewrite();

		$this->type_helper = new SC_TypeHelper( $singular );

		add_filter( 'wpt_field_options', array( $this, 'custom_select' ), 10, 3 );
	}

	/**
	 * Returns a list of ID & title of entries of the CPT
	 *
	 * @return array
	 */
	public function get_info_for_select() {
		return $this->type_helper->get_info_for_select();
	}

	/**
	 * Adds all CPT entries to a HTML select tag
	 *
	 * @param array  $options Original set of options.
	 * @param string $title	Determines the custom field to filter.
	 * @param string $type Custom field original type.
	 * @return array
	 */
	public function custom_select( $options, $title, $type ) {

		switch ( strtolower( $title ) ) {
			case $this->singular:
				$options = $this->get_info_for_select();
			break;
		}
		return $options;
	}
}
