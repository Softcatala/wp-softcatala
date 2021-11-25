<?php

namespace Softcatala\TypeRegisters;

/**
 * Class DadesObertes
 *
 * Registers the DadesObertes post type
 */
class DadesObertes extends PostType {

	public function __construct() {
		parent::__construct( 'Dades Obertes', 'Dades Obertes' );

		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ), 11 );
	}

	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'download':
				$download = get_field( 'download_url', $post_id );
				if ( $download ) {
					echo '<a href="' . $download . '">' . $download . '</a>';
				}
				break;
			case 'image':
				the_post_thumbnail( '', array( 'style' => 'max-width:100px;height:auto;' ), $post_id );
				break;
			default:
				return;
		}
	}

	public function remove_yoast_metabox() {
		remove_meta_box( 'wpseo_meta', 'dadesobertes', 'normal' );
	}

	public function add_columns_to_admin( $columns ) {

		return array_merge(
			$columns,
			array(
				'download' => 'Baixada',
			)
		);
	}

	public function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Dades Obertes' );

		$args = array(
			'label'               => __( 'Dades Obertes', 'softcatala' ),
			'description'         => __( 'Dades Obertes: Llistat de dades obertes', 'softcatala' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'revisions', 'thumbnail' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-media-document',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'rewrite'             => array(
				'slug' => 'dades-obertes',
				'with_front' => false,
			),
			'rewrite'             => false,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);
		register_post_type( 'dadesobertes', $args );
		remove_post_type_support( 'dadesobertes', 'excerpt' );
	}

	protected function get_ctp_labels( $menu ) {
		return array(
			'name'                  => $this->plural,
			'singular_name'         => $this->singular,
			'menu_name'             => $menu,
			'name_admin_bar'        => $menu,
			'archives'              => __( 'Open Data Archives', 'softcatala' ),
			'attributes'            => __( 'Open Data Attributes', 'softcatala' ),
			'parent_item_colon'     => __( 'Parent Open Data:', 'softcatala' ),
			'all_items'             => __( 'All Open Datas', 'softcatala' ),
			'add_new_item'          => __( 'Add New Open Data', 'softcatala' ),
			'add_new'               => __( 'Add New', 'softcatala' ),
			'new_item'              => __( 'New Open Data', 'softcatala' ),
			'edit_item'             => __( 'Edit Open Data', 'softcatala' ),
			'update_item'           => __( 'Update Open Data', 'softcatala' ),
			'view_item'             => __( 'View Open Data', 'softcatala' ),
			'view_items'            => __( 'View Open Data', 'softcatala' ),
			'search_items'          => __( 'Search Open Data', 'softcatala' ),
			'not_found'             => __( 'Not found', 'softcatala' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'softcatala' ),
			'featured_image'        => __( 'Featured Image', 'softcatala' ),
			'set_featured_image'    => __( 'Set featured image', 'softcatala' ),
			'remove_featured_image' => __( 'Remove featured image', 'softcatala' ),
			'use_featured_image'    => __( 'Use as featured image', 'softcatala' ),
			'insert_into_item'      => __( 'Insert into Open Data', 'softcatala' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Open Data', 'softcatala' ),
			'items_list'            => __( 'Open Data list', 'softcatala' ),
			'items_list_navigation' => __( 'Open Data list navigation', 'softcatala' ),
			'filter_items_list'     => __( 'Filter Open Data list', 'softcatala' ),
		);
	}
}
