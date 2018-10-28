<?php

namespace Softcatala\TypeRegisters;

/**
 * Class Esdeveniment
 *
 * Registers the Esdeveniment post type
 */
class Esdeveniment extends PostType {

	public function __construct() {
		parent::__construct( 'Esdeveniment', 'Esdeveniments' );

		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ), 11 );
	}

	public function custom_columns( $column, $post_id ) {
		echo esc_html( get_field( $column, $post_id ) );
	}

	public function remove_yoast_metabox() {
		remove_meta_box( 'wpseo_meta', 'esdeveniment', 'normal' );
	}

	public function add_columns_to_admin( $columns ) {

		$return = array_merge(
			 $columns,
			array(
				'data_inici' => 'Data de finalització',
				'data_fi'    => "Data d'inici",
				'horari'     => 'Horari',
				'ciutat'     => 'Ciutat',
			)
		);

		unset( $return['date'] );

		return $return;
	}

	public function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Esdeveniments' );

		$args   = array(
			'label'               => __( 'Esdeveniment', 'softcatala' ),
			'description'         => __( 'Esdeveniments i activitats relacionats amb el món de la tecnologia i el català.', 'softcatala' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'excerpt', 'thumbnail' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-calendar-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'rewrite'             => array(
				'slug' => 'esdeveniments',
				'with_front' => false,
			),
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);
		register_post_type( 'esdeveniment', $args );
	}

	protected function register_custom_taxonomies() {

		$labels  = $this->get_taxonomy_labels(
			"Categoria de l'esdeveniment",
			'Categoria dels esdeveniments',
			'Categoria'
			);

		$rewrite = array(
			'slug'         => 'esdeveniments/categoria',
			'with_front'   => true,
			'hierarchical' => true,
		);
		$args    = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => $rewrite,
			'show_in_rest'      => false,
		);
		register_taxonomy( 'esdeveniment_cat', array( 'esdeveniment' ), $args );
	}
}
