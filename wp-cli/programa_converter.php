<?php


class Programa_Converter extends WP_CLI_Command {

	private $post_type_name = 'programa';

	public function __invoke() {
		$this->change_post_meta();
	}

	private function change_post_meta() {

		$items = $this->get_all_items();

		foreach ( $items as $item ) {

			echo $item->post_title;

			$this->update_standard_meta( $item );

			echo ".\n";
		}
	}

	private function get_all_items() {
		$q = array(
			'numberposts' => - 1,
			'post_status' => 'any',
			'post_type'   => $this->post_type_name
		);

		return get_posts( $q );
	}

	private function update_standard_meta( $item ) {
		$allMetas = array(
			'wpcf-subtitle_programa'    => 'subtitle_programa',
			'wpcf-idrebost'             => 'idrebost',
			'wpcf-autor_programa'       => 'autor_programa',
			'wpcf-lloc_web_programa'    => 'lloc_web_programa',
			'wpcf-external_project_url' => 'external_project_url',
			'wpcf-autor_traduccio'      => 'autor_traduccio',
			'wpcf-valoracio'            => 'valoracio',
			'wpcf-vots'                 => 'vots',
			'_wpcf_belongs_projecte_id' => 'projecte_relacionat'
		);

		$allImages = array (
			'logotip_programa'	,
			'imatge_destacada_1',
			'imatge_destacada_2',
			'imatge_destacada_3'
		);

		foreach ( $allMetas as $old => $new ) {

			$value = get_post_meta( $item->ID, $old, true );

			if (value !== false) {

				if ( $new == 'vots' ) {
					$value = str_replace( '.', '', $value );
				}

				update_field( $new, $value, $item->ID );
				delete_post_meta( $item->ID, $old );
			}
		}

		foreach ( $allImages as $key ) {
			$this->update_image( $item, $key );
		}
	}

	public function update_image( $item, $key ) {

		$new_meta_key = $key;
		$old_meta_key = 'wpcf-' . $key;

		$url = get_post_meta($item->ID, $old_meta_key, true );

		$id = attachment_url_to_postid($url);

		if ( $id ) {
			update_field( $new_meta_key, $id, $item->ID);
			delete_post_meta( $item->ID, $old_meta_key );
		}
	}
}
