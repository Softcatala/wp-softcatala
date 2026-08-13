<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Posts;

use Timber\Post;

/**
 * Timber post model for the 'projecte' post type.
 *
 * Registered through the 'timber/post/classmap' filter (see
 * Softcatala\TypeRegisters\Projecte), so every projecte returned by Timber is
 * an instance of this class, both in PHP and in Twig.
 *
 * Note: in PHP always call these as methods ($projecte->is_featured()).
 * Property syntax goes through Timber\Core::__get(), which resolves postmeta
 * before methods, so a meta row with the same name would shadow the method.
 */
class Projecte extends Post {

	use FeaturedSorting;

	/**
	 * The Telegram group used when nothing more specific is available.
	 */
	const GENERIC_TELEGRAM_GROUP = 'Softcatala_Collaboradors';

	/**
	 * Whether the project is featured in the listings.
	 *
	 * @return bool
	 */
	public function is_featured() {
		return (bool) $this->meta( 'projecte_destacat' );
	}

	/**
	 * Whether the project is internal, and therefore invisible to anonymous
	 * visitors.
	 *
	 * @return bool
	 */
	public function is_internal() {
		return self::is_internal_post( $this->ID );
	}

	/**
	 * Same rule as is_internal(), for the call sites that only have a post id.
	 *
	 * @param int $post_id id of the projecte.
	 * @return bool
	 */
	public static function is_internal_post( $post_id ) {
		return (bool) get_field( 'projecte_intern', $post_id );
	}

	/**
	 * The meta query that hides internal projects from anonymous visitors.
	 *
	 * A two-arm OR, so that projects with no meta row at all are treated as
	 * public.
	 *
	 * @return array
	 */
	public static function public_meta_query() {
		return array(
			'relation' => 'OR',
			array(
				'key'     => 'projecte_intern',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'projecte_intern',
				'value'   => '1',
				'compare' => '!=',
			),
		);
	}

	/**
	 * Finds a project by its slug.
	 *
	 * @param string $slug slug of the projecte.
	 * @return self|null Null when there is no project with that slug.
	 */
	public static function find_by_slug( $slug ) {
		$projecte = get_page_by_path( strtolower( $slug ), OBJECT, 'projecte' );

		return $projecte ? \Timber\Timber::get_post( $projecte->ID ) : null;
	}

	/**
	 * The Telegram group to send a would-be collaborator to: the project's own,
	 * the one of the first collaborator profile it asks for, or the generic one.
	 *
	 * @return string Telegram group name, without the t.me/ prefix.
	 */
	public function telegram_group() {
		$telegram = $this->meta( 'telegram' );

		if ( $telegram ) {
			return $telegram;
		}

		$perfils = $this->terms( 'ajuda-projecte' );

		if ( is_array( $perfils ) ) {
			foreach ( $perfils as $perfil ) {
				$telegram = $perfil->meta( 'telegram' );

				if ( $telegram ) {
					return $telegram;
				}
			}
		}

		return self::GENERIC_TELEGRAM_GROUP;
	}

	/**
	 * The users responsible for the project.
	 *
	 * @return array|false The 'responsable' field, or false when empty.
	 */
	public function responsables() {
		$responsables = get_field( 'responsable', $this->ID );

		if ( is_array( $responsables ) && ! empty( $responsables ) ) {
			return $responsables;
		}

		return false;
	}
}
