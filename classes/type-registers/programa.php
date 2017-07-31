<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Programa
 *
 * Registers the Programa post type
 */
class Programa extends PostType {

	public function __construct() {
		parent::__construct( 'Programa', 'Programes', true );
	}

	/**
	 * Returns text explaining conditions to add a program
	 *
	 * @return string
	 */
	public function condicions_afegir_programa() {
		return get_option( 'sc_text_programes' );
	}

	/**
	 * Default email of the section
	 *
	 * @return type
	 */
	public function email() {
		return get_option( 'email_rebost' );
	}


	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'image':
				$image = get_post_meta( $post_id, 'logotip_programa', true );
				wp_get_attachment_image( $image, 'full', false, array( 'style' => 'max-width:100px;height:auto;' ) );
			break;

			default:
			return;
		}
	}

	public function add_columns_to_admin( $columns ) {

		return array_merge($columns,
			array(
				'image' => 'Imatge',
			)
		);
	}

	protected function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Programes' );

		$args   = array(
			'label'               => __( 'Programa', 'softcatala' ),
			'description'         => __( 'Programes: Rebost d\'aplicacions.', 'softcatala' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'excerpt', 'comments', 'revisions', 'thumbnail' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-desktop',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => array( 'slug' => 'programes', 'with_front' => false ),
			'capability_type'     => 'page',
			'show_in_rest'        => false,
		);
		register_post_type( 'programa', $args );
	}


	protected function register_custom_taxonomies() {

		$this->register_categoria_programa();
		$this->register_llicencies();
		$this->register_sistema_operatiu();
		$this->register_classificacio();
	}

	private function register_categoria_programa() {

		$labels  = $this->get_taxonomy_labels(
			'Categories del programa',
			'Categoria del programa',
			'Categoria programes');

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
		register_taxonomy( 'categoria-programa', array( 'programa' ), $args );
	}

	private function register_llicencies() {

		$labels  = $this->get_taxonomy_labels( 'Llicències', 'Llicència',  'Llicència' );

		$rewrite = array(
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

		register_taxonomy( 'llicencia', array( 'programa' ), $args );
	}

	private function register_sistema_operatiu() {

		$labels  = $this->get_taxonomy_labels(
			'Sistemes operatius',
			'Sistema operatiu',
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
			'meta_box_cb'       => false,
		);

		register_taxonomy( 'sistema-operatiu-programa', array( 'programa' ), $args );
	}

	private function register_classificacio() {

		$labels  = $this->get_taxonomy_labels(
			'Classificació',
			'Classificació',
			'Classificació'
		);

		$args    = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => false,
			'show_in_rest'      => false,
		);

		register_taxonomy( 'classificacio', array( 'programa', 'projecte' ), $args );
	}
}
