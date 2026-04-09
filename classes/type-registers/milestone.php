<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Milestone
 *
 * Registers the Milestone post type.
 */
class Milestone extends PostType {

	public function __construct() {
		parent::__construct( 'Milestone', 'Milestones' );
	}

	protected function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Milestones' );

		$args = array(
			'label'               => __( 'Milestone', 'softcatala' ),
			'description'         => __( 'Milestone d\'un projecte de Softcatalà.', 'softcatala' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'revisions' ),
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'        => 27,
			'menu_icon'           => 'dashicons-flag',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
		);

		register_post_type( 'milestone', $args );
	}

	protected function register_custom_taxonomies() {
		// No taxonomies for milestone.
	}
}
