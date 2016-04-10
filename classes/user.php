<?php
/**
 * @package SC
 */

/**
 * Handles User Fields
 */
class SC_User {

	/**
	 * @var array DescriptionAvailable custom fields for users.
	 */
	protected $custom_user_fields = array(
		array(
			'post_type' => 'projecte',
			'type' => 'checkbox', // Not used at this moment, the template should be adapted.
			'name' => 'projectes',
		),
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'extra_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'extra_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_extra_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_extra_user_profile_fields' ) );
	}

	/**
	 * This functions enables the new user fields on backend
	 *
	 * @param WP_User $user User to get information from.
	 */
	function extra_user_profile_fields( $user ) {
		$admin_template = get_stylesheet_directory() . '/templates/admin/sc-user.twig';

		foreach ( $this->custom_user_fields as $user_field ) {
			$options['name'] = $user_field['name'];
			$options['available_values'] = $this->get_info_for_select( $user_field['post_type'] );
			$options['stored_values'] = get_user_meta( $user->ID, $user_field['name'], true );
		}

		$section_html_content = Timber::fetch( $admin_template, array( 'options' => $options ) );
		echo $section_html_content;
	}


	/**
	 * This function saves the different user params
	 *
	 * @param WP_User $user_id User_id to set information to.
	 * @return bool
	 */
	function save_extra_user_profile_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		foreach ( $this->custom_user_fields as $user_field ) {

			$new_meta = $_POST[ $user_field['name'] ] ; // Input var okay.
			
			if ( $new_meta == null ) {
				$custom = array();
			} else {
				$custom = sanitize_text_field_recursively( $new_meta );
			}

			update_user_meta( $user_id, $user_field['name'], $custom );
		}

	}

	/**
	 * Returns an array of pairs ID, title of entries of CPT
	 *
	 * @param string $type Custom Post Type to filter.
	 * @return array
	 */
	function get_info_for_select($type) {
		$query = new WP_Query();

		$args = array(
			'post_type'        => $type,
			'post_status'      => 'publish',
			'no_found_rows'    => true,
			'posts_per_page'      => -1,
		);

		$results = $query->query( $args );

		return array_map( function ($entry) {
			return array(
				'value' => $entry->ID,
				'title' => $entry->post_title,
			);
		}, $results );
	}
}
