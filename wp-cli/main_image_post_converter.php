<?php


class Main_Image_Post_Converter extends WP_CLI_Command {

	private $post_type_name = 'post';

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

		$this->update_image( $item, 'main_image_post' );
	}

	public function update_image( $item, $key ) {

		$new_meta_key = $key;
		$old_meta_key = 'wpcf-' . $key;

		$url = get_post_meta($item->ID, $old_meta_key, true );

		if (!$url) {
			return;
		}

		echo $url, "\n";

		$home_url = home_url();
		if ( 0 !== strpos($url, $home_url) && 0 == strpos($url, '/uploads') ) {
			$url = $home_url . $url;
		}

		$id = attachment_url_to_postid($url);

		if (!$id) {
			$id = get_img_id_from_url( $url );
		}

		if (!$id) {
			$id = $this->attach_url( $url );
		}

		echo $url, '-', $id, "\n";

		if ( $id ) {
			$this->set_id( $new_meta_key, $old_meta_key, $id, $item );
		}
	}

	function attach_url( $url ) {

		$basepath = str_replace( home_url(), '', $url );

		$wp_filetype = wp_check_filetype( basename( $basepath ), null );

		$attachment = array(
			'guid'           => $url,
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => preg_replace( '/.[^.]+$/', '', basename( $basepath ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $basepath, 0 );

		$attach_data = wp_generate_attachment_metadata( $attach_id, $basepath );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	function set_id($new_meta_key, $old_meta_key, $id, $item) {
		update_field( $new_meta_key, $id, $item->ID);
		delete_post_meta( $item->ID, $old_meta_key );
	}

}