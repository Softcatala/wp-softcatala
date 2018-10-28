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
class Slider extends PostType {

	public function __construct() {
		parent::__construct( 'Slider', 'Sliders' );

		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ) , 11 );
	}

	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'image':
				the_post_thumbnail( '', array( 'style' => 'max-width:200px;height:auto;' ), $post_id );
				break;

			case 'link':
				echo esc_url( get_post_meta( $post_id, 'slide_link', true ) );
				break;

			default:
				return;
		}
	}

	public function remove_yoast_metabox() {
		remove_meta_box( 'wpseo_meta', 'slider', 'normal' );
	}

	public function add_columns_to_admin( $columns ) {

		return array_merge(
			$columns,
			array(
				'image' => 'Imatge',
				'link'  => 'URL',
			)
		);
	}

	public function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Sliders' );

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
