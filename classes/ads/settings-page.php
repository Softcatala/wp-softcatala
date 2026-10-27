<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Ads;

/**
 * "Publicitat" options page (zone per section and slot) and the per-page zone overrides.
 *
 * Field groups are registered in PHP rather than ACF JSON because production loads
 * field groups from the database only, so a JSON group would need a manual sync.
 */
class SettingsPage {

	const MENU_SLUG     = 'sc-publicitat';
	const OPTIONS_GROUP = 'group_sc_publicitat';
	const PAGE_GROUP    = 'group_sc_ads_page';

	const INHERIT_HELP = 'Buit: hereta el valor general. 0: sense publicitat.';

	public function __construct() {
		add_action( 'acf/init', array( $this, 'register_options_page' ) );
		add_action( 'acf/init', array( $this, 'register_field_groups' ) );
		add_action( 'acf/save_post', array( $this, 'clear_page_cache_on_save' ), 20 );
	}

	public function register_options_page() {
		// Options pages are ACF Pro only; the test suite runs the free plugin.
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'  => 'Publicitat',
				'menu_title'  => 'Publicitat',
				'menu_slug'   => self::MENU_SLUG,
				'parent_slug' => 'options-general.php',
				'capability'  => 'manage_options',
				'post_id'     => ZoneResolver::OPTIONS_ID,
				'autoload'    => true,
			)
		);
	}

	public function register_field_groups() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$config = ZoneResolver::load_config();

		acf_add_local_field_group(
			array(
				'key'      => self::OPTIONS_GROUP,
				'title'    => 'Zones de Revive per secció',
				'fields'   => $this->section_fields( $config ),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::MENU_SLUG,
						),
					),
				),
			)
		);

		acf_add_local_field_group(
			array(
				'key'      => self::PAGE_GROUP,
				'title'    => 'Publicitat (zones de Revive)',
				'fields'   => $this->page_fields( $config ),
				'position' => 'side',
				'location' => array(
					array( self::post_type_rule( 'page' ) ),
					array( self::post_type_rule( 'programa' ) ),
				),
			)
		);
	}

	/**
	 * Clears WP Super Cache after the Publicitat page is saved, so new zones are served at once.
	 *
	 * @param int|string $post_id ACF post_id being saved.
	 */
	public function clear_page_cache_on_save( $post_id ) {
		if ( ZoneResolver::OPTIONS_ID === $post_id ) {
			PageCache::clear();
		}
	}

	private function section_fields( $config ) {
		$fields = array();

		foreach ( $config['sections'] as $section => $definition ) {
			$fields[] = array(
				'key'   => 'field_sc_publicitat_tab_' . $section,
				'label' => $definition['title'],
				'name'  => '',
				'type'  => 'tab',
			);

			foreach ( $config['slots'] as $slot => $label ) {
				$field = self::zone_field( 'field_sc_publicitat_' . $section . '_' . $slot, ZoneResolver::field_name( $section, $slot ), $label );

				$field['default_value'] = $definition['seed'][ $slot ] ?? '';
				if ( Sections::DEFAULT_SECTION !== $section ) {
					$field['instructions'] = self::INHERIT_HELP;
				}

				$fields[] = $field;
			}
		}

		return $fields;
	}

	private function page_fields( $config ) {
		$fields = array();

		foreach ( $config['slots'] as $slot => $label ) {
			$field                 = self::zone_field( 'field_sc_ads_' . $slot, ZoneResolver::meta_key( $slot ), $label );
			$field['instructions'] = 'Buit: la zona de la secció. 0: sense publicitat.';
			$fields[]              = $field;
		}

		return $fields;
	}

	private static function zone_field( $key, $name, $label ) {
		return array(
			'key'           => $key,
			'label'         => $label,
			'name'          => $name,
			'type'          => 'number',
			'min'           => 0,
			'step'          => 1,
			'default_value' => '',
		);
	}

	private static function post_type_rule( $post_type ) {
		return array(
			'param'    => 'post_type',
			'operator' => '==',
			'value'    => $post_type,
		);
	}
}
