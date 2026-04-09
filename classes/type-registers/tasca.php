<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Tasca
 *
 * Registers the Tasca (task) post type with estat_tasca and tag_tasca taxonomies.
 */
class Tasca extends PostType {

	public function __construct() {
		parent::__construct( 'Tasca', 'Tasques' );
	}

	protected function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Tasques' );

		$args = array(
			'label'                => __( 'Tasca', 'softcatala' ),
			'description'          => __( 'Tasca de gestió de projectes de Softcatalà.', 'softcatala' ),
			'labels'               => $labels,
			'supports'             => array( 'title', 'editor', 'comments', 'revisions' ),
			'hierarchical'         => false,
			'public'               => false,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'menu_position'        => 26,
			'menu_icon'            => 'dashicons-clipboard',
			'show_in_admin_bar'    => true,
			'show_in_nav_menus'    => false,
			'can_export'           => true,
			'has_archive'          => 'tasques',
			'exclude_from_search'  => true,
			'rewrite'              => array(
				'slug'       => 'tasques',
				'with_front' => false,
			),
			'capability_type'      => 'post',
			'map_meta_cap'         => true,
			'show_in_rest'         => true,
			'rest_controller_class' => 'Softcatala\TypeRegisters\TascaRestController',
		);

		register_post_type( 'tasca', $args );
	}

	protected function register_custom_taxonomies() {
		$this->register_estat_tasca();
		$this->register_tag_tasca();
	}

	private function register_estat_tasca() {

		$labels = $this->get_taxonomy_labels(
			'Estats de les tasques',
			'Estat de la tasca',
			'Estats tasques'
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'rewrite'           => false,
			'show_in_rest'      => true,
		);

		register_taxonomy( 'estat_tasca', array( 'tasca' ), $args );
	}

	private function register_tag_tasca() {

		$labels = $this->get_taxonomy_labels(
			'Etiquetes de les tasques',
			'Etiqueta de la tasca',
			'Etiquetes tasques'
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
			'show_in_rest'      => true,
		);

		register_taxonomy( 'tag_tasca', array( 'tasca' ), $args );
	}
}
