<?php
/**
 * @package Softcatalà
 **/

/**
 * Represents the base content for SC
 */
abstract class SC_Content_Base {

	protected $wp_object;

	protected $nom;

	protected function __construct( $nom ) {
		$this->wp_object = array(
			'status' => -1,
			'post_id' => -1,
		);

		$this->nom = $nom;
	}

	public function get_nom() {
		return $this->nom;
	}

	/**
	 * Returns if object has been stored
	 *
	 * @return bool
	 */
	public function is_draft() {
		return 1 == $this->wp_object['status'];
	}

	/**
	 * Returns ID of content
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->wp_object['post_id'];
	}

	/**
	 * Returns AJAX-ready return
	 *
	 * @return string
	 */
	public function get_return() {
		return $this->wp_object;
	}

	/**
	 * Creates the post based on the basic information provided
	 *
	 * @param string $type Type of the content.
	 * @param string $nom Name of the content.
	 * @param string $descripcio string Description of the content.
	 * @param string $slug slug of the content.
	 * @param array  $all_terms Taxonomy terms for the content.
	 * @param array  $metadata post_meta.
	 *
	 * @return array|mixed|void
	 */
	public function save_as_draft( $type, $nom, $descripcio, $slug, $all_terms, $metadata ) {
		$return = array();
		if ( isset( $metadata['post_id'] ) ) {
			$parent_id = $metadata['post_id'];
			unset( $metadata['post_id'] );
			$post_status = 'publish';
		} else {
			$post_status = 'pending';
		}

		$post_data = array(
			'post_type'      => $type,
			'post_status'    => $post_status,
			'comment_status' => 'open',
			'ping_status'    => 'closed',
			'post_author'    => get_current_user_id(),
			'post_name'      => $slug,
			'post_title'     => $nom,
			'post_content'   => $descripcio,
			'post_date'      => date( 'Y-m-d H:i:s' ),
		);

		$post_id = wp_insert_post( $post_data );
		if ( $post_id ) {

			foreach ( $all_terms as $taxonomy => $terms ) {
				wp_set_post_terms( $post_id, $terms, $taxonomy );
			}

			sc_update_metadata_acf( $post_id, $metadata );

			if ( 'aparell' == $type ) {
				$attach_id = sc_upload_file( 'file', $post_id );
				if ( $attach_id ) {
					$return = sc_set_featured_image( $post_id, $attach_id );
				} else {
					$return['status'] = 1;
				}
			} else {
				$return['status'] = 1;
			}
		} else {
			$return['status'] = 0;
			$return['text']   = "S'ha produït un error en enviar les dades. Proveu de nou.";
		}//end if

		if ( 1 == $return['status'] ) {
			$return['post_id'] = $post_id;
			$return['text']    = 'Gràcies per enviar aquesta informació. La publicarem tan aviat com puguem.';
		}

		return $return;
	}
}
