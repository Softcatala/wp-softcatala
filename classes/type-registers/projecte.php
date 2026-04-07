<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class Projecte
 *
 * Registers the Projecte post type
 */
class Projecte extends PostType {

	public function __construct() {
		parent::__construct( 'Projecte', 'Projectes', true );

		add_filter( 'views_edit-projecte', array( $this, 'add_active_projects_view' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_active_projects' ) );
	}

	public function add_active_projects_view( $views ) {
		$is_default = ! isset( $_GET['show_all'] );

		// Count active (published, not arxivat) projects.
		$count_query = new \WP_Query(
			array(
				'post_type'      => 'projecte',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'classificacio',
						'field'    => 'slug',
						'terms'    => 'arxivat',
						'operator' => 'NOT IN',
					),
				),
			)
		);
		$active_count = $count_query->found_posts;

		// Point the built-in "All" link to opt-out of the active filter,
		// and mark it current when show_all=1.
		if ( isset( $views['all'] ) ) {
			$all_url      = add_query_arg( 'show_all', '1', admin_url( 'edit.php?post_type=projecte' ) );
			$views['all'] = preg_replace( '/href="[^"]+"/', 'href="' . esc_url( $all_url ) . '"', $views['all'] );
			if ( $is_default ) {
				$views['all'] = str_replace( array( ' class="current"', ' aria-current="page"' ), '', $views['all'] );
			} else {
				$views['all'] = str_replace( '<a ', '<a class="current" aria-current="page" ', $views['all'] );
			}
		}

		$active_url  = admin_url( 'edit.php?post_type=projecte' );
		$current     = $is_default ? ' class="current" aria-current="page"' : '';
		$active_view = '<a href="' . esc_url( $active_url ) . '"' . $current . '>'
			. __( 'Projectes actius', 'softcatala' )
			. ' <span class="count">(' . $active_count . ')</span></a>';

		return array_merge( array( 'projectes_actius' => $active_view ), $views );
	}

	public function filter_active_projects( $query ) {
		$is_projecte_screen  = isset( $_GET['post_type'] ) && $_GET['post_type'] === 'projecte';
		$is_default_view     = ! isset( $_GET['show_all'] ) && ! isset( $_GET['post_status'] );
		if ( is_admin() && $query->is_main_query() && $is_projecte_screen && $is_default_view ) {
			$query->set( 'post_status', 'publish' );
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'classificacio',
						'field'    => 'slug',
						'terms'    => 'arxivat',
						'operator' => 'NOT IN',
					),
				)
			);
		}
	}

	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'image':
				$image = get_post_meta( $post_id, 'logotip', true );
				wp_get_attachment_image( $image, 'full', false, array( 'style' => 'max-width:100px;height:auto;' ) );
				break;

			default:
				return;
		}
	}

	public function add_columns_to_admin( $columns ) {

		return array_merge(
			$columns,
			array(
				'image' => 'Imatge',
			)
		);
	}

	protected function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Projectes' );

		$args   = array(
			'label'               => __( 'Projecte', 'softcatala' ),
			'description'         => __( 'Projecte de Softcatalà.', 'softcatala' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'excerpt', 'comments', 'revisions', 'thumbnail' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-feedback',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => array(
				'slug' => 'projectes',
				'with_front' => false,
			),
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);
		register_post_type( 'projecte', $args );
	}


	protected function register_custom_taxonomies() {

		$this->register_categoria_projecte();
		$this->register_ajuda_projecte();
	}

	private function register_categoria_projecte() {

		$labels  = $this->get_taxonomy_labels(
			'Categories dels projectes',
			'Categoria del projecte',
			'Categories projectes'
			);

		$rewrite = array(
			'slug' => 'en-que-treballem',
			'with_front' => false,
			'hierarchical'      => true,
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
		register_taxonomy( 'categoria_projecte', array( 'projecte' ), $args );
	}

	private function register_ajuda_projecte() {

		$labels  = $this->get_taxonomy_labels(
			'En què cal ajuda',
			'En què cal ajuda',
			'En què cal ajuda'
		);

		$args    = array(
			'labels'            => $labels,
			'with-front'        => false,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => false,
			'show_in_rest'      => false,
		);

		register_taxonomy( 'ajuda-projecte', array( 'projecte' ), $args );
	}
}
