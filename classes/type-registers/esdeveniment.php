<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Esdeveniment
 *
 * Registers the Esdeveniment post type
 */
class Esdeveniment {

	public function __construct() {
		$this->register_custom_post_type();
		$this->register_custom_taxonomies();

		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ), 11 );
		add_filter( 'manage_esdeveniment_posts_columns', array( $this, 'add_columns_to_admin' ) );
		add_action( 'manage_esdeveniment_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
	}

	function custom_columns( $column, $post_id ) {
		echo get_field( $column, $post_id );
	}

	public function remove_yoast_metabox() {
		remove_meta_box( 'wpseo_meta', 'esdeveniment', 'normal' );
	}

	public function add_columns_to_admin( $columns ) {

		$return = array_merge( $columns,
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

		$labels = array(
			'name'                  => _x( 'Esdeveniments', 'Post Type General Name', 'softcatala' ),
			'singular_name'         => _x( 'Esdeveniment', 'Post Type Singular Name', 'softcatala' ),
			'menu_name'             => __( 'Esdeveniments', 'softcatala' ),
			'name_admin_bar'        => __( 'Esdeveniments', 'softcatala' ),
			'archives'              => __( 'Item Archives', 'softcatala' ),
			'attributes'            => __( 'Item Attributes', 'softcatala' ),
			'parent_item_colon'     => __( 'Parent Item:', 'softcatala' ),
			'all_items'             => __( 'All Items', 'softcatala' ),
			'add_new_item'          => __( 'Add New Item', 'softcatala' ),
			'add_new'               => __( 'Add New', 'softcatala' ),
			'new_item'              => __( 'New Item', 'softcatala' ),
			'edit_item'             => __( 'Edit Item', 'softcatala' ),
			'update_item'           => __( 'Update Item', 'softcatala' ),
			'view_item'             => __( 'View Item', 'softcatala' ),
			'view_items'            => __( 'View Items', 'softcatala' ),
			'search_items'          => __( 'Search Item', 'softcatala' ),
			'not_found'             => __( 'Not found', 'softcatala' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'softcatala' ),
			'featured_image'        => __( 'Featured Image', 'softcatala' ),
			'set_featured_image'    => __( 'Set featured image', 'softcatala' ),
			'remove_featured_image' => __( 'Remove featured image', 'softcatala' ),
			'use_featured_image'    => __( 'Use as featured image', 'softcatala' ),
			'insert_into_item'      => __( 'Insert into item', 'softcatala' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'softcatala' ),
			'items_list'            => __( 'Items list', 'softcatala' ),
			'items_list_navigation' => __( 'Items list navigation', 'softcatala' ),
			'filter_items_list'     => __( 'Filter items list', 'softcatala' ),
		);
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
			'rewrite'             => array( 'slug' => 'esdeveniments', 'with_front' => false ),
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);
		register_post_type( 'esdeveniment', $args );
	}

	private function register_custom_taxonomies() {

		$labels  = array(
			'name'                       => _x( 'Categoria de l\'esdeveniment', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Categoria dels esdeveniments', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Categoria', 'text_domain' ),
			'all_items'                  => __( 'All Items', 'text_domain' ),
			'parent_item'                => __( 'Parent Item', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent Item:', 'text_domain' ),
			'new_item_name'              => __( 'New Item Name', 'text_domain' ),
			'add_new_item'               => __( 'Add New Item', 'text_domain' ),
			'edit_item'                  => __( 'Edit Item', 'text_domain' ),
			'update_item'                => __( 'Update Item', 'text_domain' ),
			'view_item'                  => __( 'View Item', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular Items', 'text_domain' ),
			'search_items'               => __( 'Search Items', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No items', 'text_domain' ),
			'items_list'                 => __( 'Items list', 'text_domain' ),
			'items_list_navigation'      => __( 'Items list navigation', 'text_domain' ),
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
