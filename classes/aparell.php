<?php

/**
 * Represents a single Aparell
 */
class SC_Aparell extends SC_Content_Base {

	public function __construct( $nom, $tipus_aparell, $fabricant, $sistema_operatiu, $versio, $traduccio_catala, $correccio_catala, $comentari ) {

		parent::__construct( $nom );

		$slug      = sanitize_title_with_dashes( $this->nom );

		$fabricant_term = $this->get_fabricant_term( $fabricant );

		$terms = $this->get_terms( $fabricant_term, $tipus_aparell, $sistema_operatiu );

		$metadata = $this->get_metadata( $versio, $traduccio_catala, $correccio_catala );

		$this->wp_object = $this->save_as_draft( 'aparell', $nom, '', $slug, $terms, $metadata, true );
	}

	private function get_fabricant_term( $fabricant_name ) {

		$fabricant_term = get_term_by( 'slug', sanitize_title( $fabricant_name ), 'fabricant' );

		if ( ! $fabricant_term ) {
			$fabricant_term = wp_insert_term( $fabricant_name, 'fabricant' );
		}

		return $fabricant_term;
	}

	private function get_terms( $fabricant_term, $tipus_aparell, $sistema_operatiu ) {

		$terms = array(
			'tipus_aparell' => array( $tipus_aparell ),
			'so_aparell'    => array( $sistema_operatiu ),
		);

		if ( $fabricant_term ) {

			$terms['fabricant'] = array( $fabricant_term->term_id );
		}

		return $terms;
	}

	private function get_metadata( $versio, $traduccio_catala, $correccio_catala ) {

		return array(
			'versio'        => $versio,
			'conf_cat'      => $traduccio_catala,
			'correccio_cat' => $correccio_catala,
		);
	}
}
