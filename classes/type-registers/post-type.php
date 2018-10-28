<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

use Softcatala\Routing\SubpageRewriter;

/**
 * Class PostType
 *
 * Provides generic functionality for registering post types
 * and taxonomies
 */
class PostType {

	private static $_instances = array();

	public static function get_instance() {

		$class = get_called_class();

		if ( ! isset( self::$_instances[ $class ] ) ) {

			self::$_instances[ $class ] = new $class();
		}

		return self::$_instances[ $class ];
	}

	/**
	 * @var string
	 */
	protected $singular;

	/**
	 * @var string
	 */
	protected $plural;

	/**
	 * @var bool
	 */
	private $subpages_enabled;

	public function __construct( $singular, $plural, $enable_subpages = false ) {
		$this->singular = $singular;
		$this->plural = $plural;
		$this->subpages_enabled = $enable_subpages;

		$this->register_custom_post_type();
		$this->register_custom_taxonomies();

		if ( $enable_subpages ) {
			$this->enable_subpages();
		}

		add_filter( 'manage_' . $this->singular . '_posts_columns', array( $this, 'add_columns_to_admin' ) );
		add_action( 'manage_' . $this->singular . '_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
	}

	public function add_columns_to_admin( $columns ) {

	}

	public function custom_columns( $column, $post_id ) {

	}

	protected function enable_subpages() {
		$rewriter = new SubpageRewriter( $this->singular,$this->plural );
		$rewriter->setup_rewrite();
	}

	public function get_page() {

		if ( $this->subpages_enabled ) {
			$args = array(
				'name' => $this->singular . '-page',
				'post_type' => 'page',
			);

			return \Timber\Timber::get_post( $args );
		}

		return false;

	}

	protected function register_custom_post_type() {

	}

	protected function register_custom_taxonomies() {

	}

	protected function get_ctp_labels( $menu ) {
		return array(
			'name'                  => $this->plural,
			'singular_name'         => $this->singular,
			'menu_name'             => $menu,
			'name_admin_bar'        => $menu,
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
	}

	protected function get_taxonomy_labels( $plural, $singular, $menu ) {
		return array(
			'name'                       => $plural,
			'singular_name'              => $singular,
			'menu_name'                  => $menu,
			'all_items'                  => __( 'All Items', 'softcatala' ),
			'parent_item'                => __( 'Parent Item', 'softcatala' ),
			'parent_item_colon'          => __( 'Parent Item:', 'softcatala' ),
			'new_item_name'              => __( 'New Item Name', 'softcatala' ),
			'add_new_item'               => __( 'Add New Item', 'softcatala' ),
			'edit_item'                  => __( 'Edit Item', 'softcatala' ),
			'update_item'                => __( 'Update Item', 'softcatala' ),
			'view_item'                  => __( 'View Item', 'softcatala' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'softcatala' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'softcatala' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'softcatala' ),
			'popular_items'              => __( 'Popular Items', 'softcatala' ),
			'search_items'               => __( 'Search Items', 'softcatala' ),
			'not_found'                  => __( 'Not Found', 'softcatala' ),
			'no_terms'                   => __( 'No items', 'softcatala' ),
			'items_list'                 => __( 'Items list', 'softcatala' ),
			'items_list_navigation'      => __( 'Items list navigation', 'softcatala' ),
		);
	}
}
