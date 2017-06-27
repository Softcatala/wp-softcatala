<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Slider
 *
 * Registers the Slider post type
 */
class Slider {

	public function __construct() {
		$this->register_custom_post_type();

		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ) , 11 );
		add_filter( 'manage_slider_posts_columns' , array( $this, 'add_columns_to_admin' ) );
		add_action( 'manage_slider_posts_custom_column' , array( $this, 'custom_columns' ), 10, 2 );
	}

	function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'image':
				the_post_thumbnail( '', array( 'style' => 'max-width:200px;height:auto;' ), $post_id );
			break;

			case 'link':
				echo get_post_meta( $post_id, 'slide_link', true );
			break;

			default:
			return;
		}
	}

	public function remove_yoast_metabox() {
		remove_meta_box( 'wpseo_meta', 'slider', 'normal' );
	}

	public function add_columns_to_admin( $columns ) {

		return array_merge($columns,
			array(
			'image' => 'Imatge',
					'link'  => 'URL',
			  )
		);
	}

	public function register_custom_post_type() {

		$labels = array(
			'name'                  => _x( 'Sliders', 'Post Type General Name', 'softcatala' ),
			'singular_name'         => _x( 'Slider', 'Post Type Singular Name', 'softcatala' ),
			'menu_name'             => __( 'Sliders', 'softcatala' ),
			'name_admin_bar'        => __( 'Sliders', 'softcatala' ),
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
		$args = array(
			'label'                 => __( 'Slider', 'softcatala' ),
			'description'           => __( 'Destacats de la pàgina d\'inici', 'softcatala' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'excerpt', 'thumbnail' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 25,
			'menu_icon'             => 'dashicons-slides',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'rewrite'               => false,
			'capability_type'       => 'page',
			'show_in_rest'          => true,
		);
		register_post_type( 'slider', $args );
	}
}
