<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class ResumMensual
 *
 * Registers the Resum mensual (monthly project summary) post type. Summaries are
 * published over the REST API by SoftcatalaBot and are readable by logged-in
 * members only.
 */
class ResumMensual extends PostType {

	public function __construct() {
		parent::__construct( 'Resum mensual', 'Resums mensuals' );
	}

	protected function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Resums mensuals' );

		$args = array(
			'label'               => __( 'Resum mensual', 'softcatala' ),
			'description'         => __( 'Resum mensual de l\'estat dels projectes de Softcatalà.', 'softcatala' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'revisions' ),
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 27,
			'menu_icon'           => 'dashicons-calendar-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => 'resums-mensuals',
			'exclude_from_search' => true,
			'rewrite'             => array(
				'slug'       => 'resums-mensuals',
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'resums-mensuals',
		);

		register_post_type( 'resum_mensual', $args );
	}

	protected function register_custom_taxonomies() {
	}
}
