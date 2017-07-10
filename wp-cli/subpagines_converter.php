<?php

class Subpagines_Converter {


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
			'post_type'   => 'page',
		);

		return get_posts( $q );
	}

	private function update_standard_meta( $item ) {
		$allMetas = array(
			'wpcf-programa' => 'programa',
			'wpcf-projecte' => 'projecte'
		);

		foreach ( $allMetas as $old => $new ) {

			$value = get_post_meta( $item->ID, $old, true );

			if ( value !== false ) {

				update_field( $new, $value, $item->ID );
				delete_post_meta( $item->ID, $old );
			}
		}
	}
}