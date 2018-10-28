<?php

namespace Softcatala\TypeRegisters;

/**
 * Class Aparell
 *
 * Registers the Aparell post type
 */
class Aparell extends PostType {

	public function __construct() {
		parent::__construct( 'Aparell', 'Aparells' );

		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ), 11 );
	}

	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'image':
				the_post_thumbnail( '', array( 'style' => 'max-width:100px;height:auto;' ), $post_id );
				break;

			default:
				return;
		}
	}

	public function remove_yoast_metabox() {
		remove_meta_box( 'wpseo_meta', 'aparell', 'normal' );
	}

	public function add_columns_to_admin( $columns ) {

		return array_merge(
			$columns,
			array(
				'image' => 'Imatge',
			)
		);
	}

	public function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Aparells' );

		$args   = array(
			'label'               => __( 'Aparell', 'softcatala' ),
			'description'         => __( 'Aparells: Llistat d\'aparells	(telèfons, tauletes, rellotges,...)', 'softcatala' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'comments', 'revisions', 'thumbnail' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-smartphone',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => false,
			'capability_type'     => 'page',
			'show_in_rest'        => false,
		);
		register_post_type( 'aparell', $args );
	}

	protected function register_custom_taxonomies() {

		$this->register_so_aparell();
		$this->register_tipus_aparell();
		$this->register_fabricants();
	}

	private function register_fabricants() {
		$labels  = $this->get_taxonomy_labels( 'Fabricants',  'Fabricant',  'Fabricant' );

		$args    = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => false,
			'show_in_rest'      => false,
		);
		register_taxonomy( 'fabricant', array( 'aparell' ), $args );
	}

	private function register_tipus_aparell() {

		$labels  = $this->get_taxonomy_labels(
			"Tipus d'aparells",
			"Tipus d'aparell",
			'Tipus'
			);

		$args    = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => false,
			'show_in_rest'      => false,
		);

		register_taxonomy( 'tipus_aparell', array( 'aparell' ), $args );
	}

	private function register_so_aparell() {

		$labels  = $this->get_taxonomy_labels(
			'Sistemes operatius (aparells)',
			'Sistema operatiu (aparell)',
			'Sistema operatiu'
			);

		$args    = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => false,
			'show_in_rest'      => false,
		);

		register_taxonomy( 'so_aparell', array( 'aparell' ), $args );
	}
}
