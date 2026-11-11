<?php
/**
 * @package Softcatala
 */

namespace Softcatala\TypeRegisters;

/**
 * Class ResumMensual
 *
 * Registers the Resum mensual (monthly project summary) post type. Summaries are
 * published over the REST API by SoftcatalaBot and are readable by logged-in
 * members only.
 */
class ResumMensual extends PostType {

	public function __construct() {
		parent::__construct( 'Resum mensual', 'Resums mensuals' );

		// Its own init callback, not part of the one-off registration above:
		// the test suite unregisters meta keys between tests and re-fires init.
		add_action( 'init', array( $this, 'register_meta' ), 11 );
	}

	protected function register_custom_post_type() {

		$labels = $this->get_ctp_labels( 'Resums mensuals' );

		$args = array(
			'label'               => __( 'Resum mensual', 'softcatala' ),
			'description'         => __( 'Resum mensual de l\'estat dels projectes de Softcatalà.', 'softcatala' ),
			'labels'              => $labels,
			// custom-fields: the REST API only exposes registered meta on post
			// types that support it.
			'supports'            => array( 'title', 'editor', 'revisions', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 27,
			'menu_icon'           => 'dashicons-calendar-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => 'resums-mensuals',
			'exclude_from_search' => true,
			'rewrite'             => array(
				'slug'       => 'resums-mensuals',
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'resums-mensuals',
		);

		register_post_type( 'resum_mensual', $args );
	}

	/**
	 * Figures SoftcatalaBot sends alongside each summary. The rendered HTML in
	 * post_content stays the source of truth for the text; these only drive the
	 * badges, the figures strip and the month shown in the archive.
	 */
	public function register_meta() {
		register_post_meta(
			'resum_mensual',
			'periode',
			array(
				'type'              => 'string',
				'description'       => 'Mes que cobreix el resum, en format AAAA-MM.',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'string',
						'pattern' => '^[0-9]{4}-(0[1-9]|1[0-2])$',
					),
				),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_post_meta(
			'resum_mensual',
			'actualitzat',
			array(
				'type'              => 'string',
				'description'       => 'Data de la darrera actualització amb respostes tardanes, en format AAAA-MM-DD.',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'string',
						'pattern' => '^([0-9]{4}-[0-9]{2}-[0-9]{2})?$',
					),
				),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		foreach ( \Softcatala\Providers\ResumsMensuals::COUNT_FIELDS as $key => $description ) {
			register_post_meta(
				'resum_mensual',
				$key,
				array(
					'type'         => 'integer',
					'description'  => $description,
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'    => 'integer',
							'minimum' => 0,
						),
					),
				)
			);
		}
	}

	protected function register_custom_taxonomies() {
	}
}
