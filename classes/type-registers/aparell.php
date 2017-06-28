<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Aparell
 *
 * Registers the Aparell post type
 */
class Aparell {

	public function __construct() {
		$this->register_custom_post_type();
		$this->register_custom_taxonomies();

		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ), 11 );
		add_filter( 'manage_aparell_posts_columns', array( $this, 'add_columns_to_admin' ) );
		add_action( 'manage_aparell_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
	}

	function custom_columns( $column, $post_id ) {
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

		return array_merge($columns,
			array(
				'image' => 'Imatge',
			)
		);
	}

	public function register_custom_post_type() {

		$labels = array(
			'name'                  => _x( 'Aparells', 'Post Type General Name', 'softcatala' ),
			'singular_name'         => _x( 'Aparell', 'Post Type Singular Name', 'softcatala' ),
			'menu_name'             => __( 'Aparells', 'softcatala' ),
			'name_admin_bar'        => __( 'Aparells', 'softcatala' ),
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

	private function register_custom_taxonomies() {

		$this->register_so_aparell();
		$this->register_tipus_aparell();
		$this->register_fabricants();
	}

	private function register_fabricants() {
		$labels  = array(
			'name'                       => _x( 'Fabricants', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Fabricant', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Fabricant', 'text_domain' ),
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

	private function register_tipus_aparell() {
		$labels  = array(
			'name'                       => _x( 'Tipus d\'aparells', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Tipus d\'aparell', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Tipus', 'text_domain' ),
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
		$labels  = array(
			'name'                       => _x( 'Sistemes operatius (aparells)', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Sistema operatiu (aparell)', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Sistema operatiu', 'text_domain' ),
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
