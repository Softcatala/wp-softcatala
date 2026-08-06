<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Posts;

use Timber\Post;

/**
 * Timber post model for the 'programa' post type.
 *
 * Registered through the 'timber/post/classmap' filter (see
 * Softcatala\TypeRegisters\Programa), so every programa returned by Timber is
 * an instance of this class, both in PHP and in Twig.
 *
 * Note: in PHP always call these as methods ($programa->is_featured()).
 * Property syntax goes through Timber\Core::__get(), which resolves postmeta
 * before methods, so a meta row with the same name would shadow the method.
 */
class Programa extends Post {

	use FeaturedSorting;

	/**
	 * Whether the program is featured in the listings.
	 *
	 * @return bool
	 */
	public function is_featured() {
		return (bool) $this->meta( 'programa_destacat' );
	}

	/**
	 * Whether the program has been archived (it is no longer maintained or
	 * distributed, so it is hidden from the listings and its comments close).
	 *
	 * @return bool
	 */
	public function is_archived() {
		return (bool) $this->has_term( 'arxivat', 'classificacio' );
	}

	/**
	 * URL of the program logo.
	 *
	 * @return string Empty when the program has no logo.
	 */
	public function logo() {
		$logo_id = $this->meta( 'logotip_programa' );

		if ( ! $logo_id ) {
			return '';
		}

		$image = wp_get_attachment_image_src( $logo_id );

		return $image ? $image[0] : '';
	}

	/**
	 * The download rows of the program, each one with the URL of the
	 * Softcatalà download counter and the operating system label and icon.
	 *
	 * @return array
	 */
	public function downloads() {
		return self::build_download_urls( $this->meta( 'baixada' ), $this->meta( 'idrebost' ), $this->ID );
	}

	/**
	 * Adds the counter URL, OS label and OS icon to a set of download rows.
	 *
	 * The counter URL looks like:
	 * https://baixades.softcatala.org/?id=3522&wid=42&versio=44.0.1&so=linux&url=...
	 *
	 * @param array|mixed $baixades  download rows as stored in the 'baixada' meta.
	 * @param string|int  $idrebost  rebost id of the program.
	 * @param int         $post_id   WordPress id of the program.
	 * @return array
	 */
	public static function build_download_urls( $baixades, $idrebost, $post_id ) {

		if ( ! is_array( $baixades ) ) {
			return array();
		}

		foreach ( $baixades as $key => $baixada ) {
			$versio_baixada = empty( $baixada['download_version'] ) ? '1.0' : $baixada['download_version'];
			$os             = $baixada['download_os'] ?? '';

			$baixades[ $key ]['download_os_label'] = get_os_nicename( $os );

			$baixades[ $key ]['download_url_ext'] = 'https://baixades.softcatala.org/'
				. '?id=' . $idrebost
				. '&wid=' . $post_id
				. '&versio=' . $versio_baixada
				. '&so=' . get_so_from_so( $os, $baixada['arquitectura'] ?? '' )
				. '&url=' . urlencode( $baixada['download_url'] ?? '' );

			$baixades[ $key ]['so_icona'] = get_awesome_icon_so( $os );
		}

		return $baixades;
	}

	/**
	 * Resolves the permalink of the program described by an entry of the
	 * baixades.softcatala.org statistics JSON, which identifies programs
	 * either by WordPress id or by rebost id.
	 *
	 * @param object $program entry of the statistics JSON.
	 * @return string|false The permalink, or false when the program is unknown.
	 */
	public static function link_from_stats( $program ) {

		if ( isset( $program->wordpress_id ) ) {
			if ( false === get_post_status( $program->wordpress_id ) ) {
				return false;
			}

			return get_post_permalink( $program->wordpress_id );
		}

		$programes = get_posts(
			array(
				'post_type'      => 'programa',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => 'idrebost',
						'value'   => $program->idrebost,
						'compare' => '=',
					),
				),
			)
		);

		if ( empty( $programes ) ) {
			return false;
		}

		return get_post_permalink( $programes[0] );
	}
}
