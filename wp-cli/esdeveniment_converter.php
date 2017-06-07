<?php


class Esdeveniment_Converter extends WP_CLI_Command {

	private $post_type_name = 'esdeveniment';

	public function __invoke() {
		$this->change_post_meta();
	}

	private function change_post_meta() {

		$items = $this->get_all_items();

		foreach ( $items as $item ) {
			$this->update_standard_meta( $item );
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
			'wpcf-data_inici'   => 'data_inici',
			'wpcf-data_fi'      => 'data_fi',
			'wpcf-horari'       => 'horari',
			'wpcf-ciutat'       => 'ciutat',
			'wpcf-lloc'         => 'lloc',
			'wpcf-cost'         => 'cost',
			'wpcf-inscripcions' => 'inscripcions',
			'wpcf-mapa'         => 'mapa',
			'wpcf-destacat'     => 'destacat',
		);

		foreach ( $allMetas as $old => $new ) {
			$value = get_post_meta( $item->ID, $old, true );

			update_field( $new, $value, $item->ID );

			delete_post_meta( $item->ID, $old );
		}
	}
}
