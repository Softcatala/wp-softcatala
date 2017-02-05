<?php



class Slider_Converter extends WP_CLI_Command {
	
	private $post_type_name = 'slider';
	
	public function __invoke() {
		$this->change_post_type_name();
		$this->change_post_meta();
	}
	
	private function change_post_type_name() {
		global $wp_rewrite;

		$old_post_types = array('slide' => $this->post_type_name);

		foreach ($old_post_types as $old_type=>$type) {
			
			$q = array(
				'numberposts' => -1,
				'post_status' => 'any',
				'post_type' => $old_type
			);
			
			$items = get_posts($q);
			
			foreach ($items as $item) {
				$update['ID'] = $item->ID;
				$update['post_type'] = $type;
				wp_update_post( $update );
			}
		}
		$wp_rewrite->flush_rules();	
	}
	
	private function change_post_meta() {
		
		$items = $this->get_all_items();
		
		foreach ( $items as $item ) {
			$this->update_image($item);
			$this->update_standard_meta($item);
		}
	}
	
	private function get_all_items() {
		$q = array(
				'numberposts' => -1,
				'post_status' => 'any',
				'post_type' => $this->post_type_name
			);
			
		return get_posts($q);
	}

	private function update_standard_meta($item) {
		$allMetas = array(
			'wpcf-button_title'	 => 'button_title',
			'wpcf-slide_link'	 => 'slide_link',
			'wpcf-image_credits' => 'image_credits'
		);
		
		foreach ( $allMetas as $old => $new ) {
			$value = get_post_meta($item->ID, $old, true );
			
			update_field( $new, $value, $item->ID);
		}
	}
	
	public function update_image( $item ) {
		
		$new_meta_key = '_thumbnail_id';
		$old_meta_key = 'wpcf-slide_image';
		
		$url = get_post_meta($item->ID, $old_meta_key, true );
				
		$id = attachment_url_to_postid($url);
		
		if ( $id ) {
			update_field( $new_meta_key, $id, $item->ID);
		}
	}
}
