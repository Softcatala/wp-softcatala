<?php



class Links_Converter extends WP_CLI_Command {
	
	private $template_name = 'plantilla-distribuidora-01.php';
	
	public function __invoke() {
		$this->add_acf_links();
	}
	
	private function add_acf_links() {

		$q = array(
			'post_type'  => 'page',
			'meta_query' => array(
				array(
					'key'   => '_wp_page_template',
					'value' => $this->template_name
				)
			)
		);

		$items = get_posts( $q );

		foreach ( $items as $item ) {

			$nq = array(
				'post_type' => 'link',
				'meta_query' => array(
					array(
						'key'   => '_wpcf_belongs_page_id',
						'value' => "$item->ID"
					)
				)
			);


			$links = get_posts( $nq );

			$this->update_standard_meta( $item, $links );
		}
	}

	private function update_standard_meta( $item, $links ) {

		foreach ( $links as $link ) {

				echo $item->ID, $item->post_title, "-", $link->ID, $link->post_title, "\n";

				$values = array (
					'titol'         => get_post_meta($link->ID, 'wpcf-link_title', true ),
					'descripcio'    => get_post_meta($link->ID, 'wpcf-link_description', true ),
					'external_link' => get_post_meta($link->ID, 'wpcf-link_url' , true ),
				);

				$idForImage = $this->get_id( $link );

				if ( $idForImage ) {
					$values['imatge'] = $idForImage;
				}

				add_row( 'distribuidora', $values, $item->ID);

				wp_delete_post( $link->ID );
		}
	}
	
	public function get_id( $post ) {
		
		$old_meta_key = 'wpcf-link_image';
		
		$url = get_post_meta($post->ID, $old_meta_key, true );
				
		return attachment_url_to_postid($url);
	}
}
