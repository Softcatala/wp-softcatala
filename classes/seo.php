<?php
/**
 * Site-wide SEO fallbacks that complement Yoast metadata.
 *
 * The class never replaces an editorial description. It only fills empty
 * fields from content already present on the page, with a concise contextual
 * fallback for content types whose body lives entirely in custom fields.
 */
class SC_Seo {

	/**
	 * Registers the filters once per request.
	 */
	public static function init() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}
	}

	private function __construct() {
		add_filter( 'wpseo_metadesc', array( $this, 'fallback_description' ), 20 );
		add_filter( 'wpseo_opengraph_desc', array( $this, 'fallback_description' ), 20 );
		add_filter( 'wpseo_twitter_description', array( $this, 'fallback_description' ), 20 );

		add_filter( 'wpseo_title', array( $this, 'differentiate_catalog_title' ), 15 );
		add_filter( 'wpseo_opengraph_title', array( $this, 'differentiate_catalog_title' ), 15 );
		add_filter( 'wpseo_twitter_title', array( $this, 'differentiate_catalog_title' ), 15 );
		add_filter( 'wpseo_title', array( $this, 'add_page_number' ), 20 );
		add_filter( 'wpseo_opengraph_title', array( $this, 'add_page_number' ), 20 );
		add_filter( 'wpseo_twitter_title', array( $this, 'add_page_number' ), 20 );
		add_filter( 'wpseo_opengraph_url', array( $this, 'paged_open_graph_url' ), 20 );
		add_filter( 'wpseo_robots_array', array( $this, 'protect_utility_results' ), 20 );
	}

	/**
	 * Separates download pages from collaboration projects that share a name.
	 * An explicit Yoast title always wins over this contextual default.
	 *
	 * @param string $title Current SEO title.
	 * @return string
	 */
	public function differentiate_catalog_title( $title ) {
		if ( ! is_singular( array( 'programa', 'projecte' ) ) ) {
			return $title;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || '' !== trim( (string) get_post_meta( $post->ID, '_yoast_wpseo_title', true ) ) ) {
			return $title;
		}

		$site_name = get_bloginfo( 'name' ) ?: 'Softcatalà';
		if ( 'programa' === $post->post_type ) {
			return sprintf( '%s en català: baixada i informació | %s', $post->post_title, $site_name );
		}

		return sprintf( 'Projecte «%s»: col·laboreu-hi | %s', $post->post_title, $site_name );
	}

	/**
	 * Yoast can inherit the posts-page URL for a paginated custom archive.
	 * Keep Open Graph on the same page-specific URL as the canonical.
	 *
	 * @param string $url Existing Open Graph URL.
	 * @return string
	 */
	public function paged_open_graph_url( $url ) {
		if ( ! is_paged() ) {
			return $url;
		}

		return get_pagenum_link( max( 2, (int) get_query_var( 'paged' ) ) );
	}

	/**
	 * Keeps private, UUID-based and internal search-result pages out of indexes.
	 *
	 * @param array $robots Existing robots directives.
	 * @return array
	 */
	public function protect_utility_results( $robots ) {
		$private_templates = array(
			'transcribe-results.php',
			'dubbing-results.php',
			'dubbing-feedback.php',
		);

		if ( is_page_template( $private_templates ) || '' !== (string) get_query_var( 'cerca' ) ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'follow';
		}

		return $robots;
	}

	/**
	 * Adds the page number when a custom title override would otherwise make
	 * every page in an archive share the same title.
	 *
	 * @param string $title Current SEO title.
	 * @return string
	 */
	public function add_page_number( $title ) {
		if ( ! is_paged() ) {
			return $title;
		}

		$page_number = max( 2, (int) get_query_var( 'paged' ) );
		$page_label  = 'Pàgina ' . $page_number;

		if ( false !== mb_stripos( $title, $page_label, 0, 'UTF-8' ) ) {
			return $title;
		}

		if ( preg_match( '/([\-|–|—|]\s*Softcatalà)$/u', $title, $matches ) ) {
			return preg_replace( '/([\-|–|—|]\s*Softcatalà)$/u', '- ' . $page_label . ' $1', $title );
		}

		return rtrim( $title ) . ' - ' . $page_label;
	}

	/**
	 * Supplies a description only when Yoast and the editor did not provide one.
	 *
	 * @param string $description Existing description.
	 * @return string
	 */
	public function fallback_description( $description ) {
		if ( '' !== trim( (string) $description ) ) {
			return $description;
		}

		$text = $this->description_from_current_object();

		return $this->trim_description( $text );
	}

	/**
	 * Builds a factual fallback from the current WordPress object.
	 *
	 * @return string
	 */
	private function description_from_current_object() {
		if ( is_singular() ) {
			$post = get_queried_object();

			if ( $post instanceof WP_Post ) {
				$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
				$text = $this->clean_text( $text );

				if ( '' !== $text ) {
					return $text;
				}

				return $this->singular_fallback( $post );
			}
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				$term_description = $this->clean_text( term_description( $term ) );
				if ( '' !== $term_description ) {
					return $term_description;
				}

				return self::term_description( $term );
			}
		}

		if ( is_author() ) {
			$author = get_queried_object();
			if ( $author instanceof WP_User ) {
				$biography = $this->clean_text( get_the_author_meta( 'description', $author->ID ) );
				return $biography ?: 'Articles i aportacions de ' . $author->display_name . ' a Softcatalà.';
			}
		}

		return '';
	}

	/**
	 * Fallback for singular content stored mainly in ACF fields.
	 *
	 * @param WP_Post $post Current post.
	 * @return string
	 */
	private function singular_fallback( $post ) {
		$title = get_the_title( $post );

		switch ( $post->post_type ) {
			case 'programa':
				return 'Consulta la fitxa de «' . $title . '»: descripció, sistemes compatibles, versions disponibles i opcions de descàrrega en català.';
			case 'projecte':
				return 'Coneix el projecte «' . $title . '» de Softcatalà, els seus objectius, els recursos disponibles i les maneres de col·laborar-hi.';
			case 'dadesobertes':
				return 'Informació i recursos oberts del conjunt de dades «' . $title . '».';
			case 'post':
				return $title . '. Notícia publicada per Softcatalà.';
			default:
				return $title . ': informació, recursos i serveis en català de Softcatalà.';
		}
	}

	/**
	 * Generates consistent metadata and headings for taxonomy archives.
	 *
	 * @param WP_Term $term Current term.
	 * @return array
	 */
	public static function term_metadata( $term ) {
		$name = $term instanceof WP_Term ? $term->name : '';
		$taxonomy = $term instanceof WP_Term ? $term->taxonomy : '';

		switch ( $taxonomy ) {
			case 'category':
				$heading = 'Notícies sobre «' . $name . '»';
				break;
			case 'post_tag':
				$heading = 'Notícies amb l’etiqueta «' . $name . '»';
				break;
			case 'categoria-programa':
				$heading = 'Programes de la categoria «' . $name . '»';
				break;
			case 'sistema-operatiu-programa':
				$heading = 'Programes per a ' . $name;
				break;
			case 'llicencia':
				$heading = 'Programes amb llicència ' . $name;
				break;
			case 'ajuda-projecte':
				$heading = 'Projectes on podeu col·laborar en «' . $name . '»';
				break;
			default:
				$taxonomy_object = get_taxonomy( $taxonomy );
				$label = $taxonomy_object ? $taxonomy_object->labels->singular_name : 'Arxiu';
				$heading = $label . ': ' . $name;
				break;
		}

		return array(
			'content_title' => $heading,
			'title'         => $heading . ' | Softcatalà',
			'description'   => self::term_description( $term ),
		);
	}

	/**
	 * @param WP_Term $term Current term.
	 * @return string
	 */
	private static function term_description( $term ) {
		$metadata = array(
			'category'                   => 'Notícies i articles de Softcatalà relacionats amb «%s».',
			'post_tag'                   => 'Notícies i articles de Softcatalà amb l’etiqueta «%s».',
			'categoria-programa'         => 'Programes i aplicacions en català de la categoria «%s».',
			'sistema-operatiu-programa'  => 'Programes i aplicacions en català disponibles per a %s.',
			'llicencia'                   => 'Programes i aplicacions en català publicats amb la llicència %s.',
			'ajuda-projecte'              => 'Projectes de Softcatalà on podeu col·laborar en tasques de «%s».',
		);

		$format = $metadata[ $term->taxonomy ] ?? 'Continguts de Softcatalà relacionats amb «%s».';

		return sprintf( $format, $term->name );
	}

	/**
	 * @param string $text Untrusted post or term text.
	 * @return string
	 */
	private function clean_text( $text ) {
		$text = strip_shortcodes( (string) $text );
		$text = wp_strip_all_tags( $text, true );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) ?: 'UTF-8' );

		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * @param string $text Description candidate.
	 * @return string
	 */
	private function trim_description( $text ) {
		$text = $this->clean_text( $text );

		if ( mb_strlen( $text, 'UTF-8' ) <= 155 ) {
			return $text;
		}

		$excerpt = mb_substr( $text, 0, 152, 'UTF-8' );
		$excerpt = preg_replace( '/\s+\S*$/u', '', $excerpt );

		return rtrim( $excerpt, " \t\n\r\0\x0B,;:.-" ) . '…';
	}
}
