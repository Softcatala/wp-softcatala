<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Images;

/**
 * Class Avatar
 *
 * @package Softcatala\Images
 */
class Avatar {

	/**
	 * @param mixed      $args Filter arguments.
	 * @param string|int $id_or_email ID or email to find the image for.
	 *
	 * @return mixed
	 */
	static function filter( $args, $id_or_email ) {

		$user_id = self::get_user_id( $id_or_email );

		if ( ! empty( $user_id ) && is_numeric( $user_id ) ) {

			$image_url = self::get_image_url( $args, $user_id );

			if ( $image_url !== false ) {
				$args['url'] = $image_url;
			}
		}

		return $args;
	}

	private static function get_user_id( $id_or_email ) {

		return self::try_get_user_id_from_post( $id_or_email ) ?:
			    self::try_get_user_id_from_comment( $id_or_email ) ?:
				self::try_get_user_id_from_number( $id_or_email ) ?:
				self::try_get_user_id_from_email( $id_or_email ) ?:
				self::try_get_user_id_from_user( $id_or_email );
	}

	private static function try_get_user_id_from_post( $id_or_email ) {

		if ( $id_or_email instanceof WP_Post ) {
			return $id_or_email->post_author;
		}

		return false;
	}

	private static function try_get_user_id_from_comment( &$id_or_email ) {

		if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$id_or_email = get_comment( $id_or_email );
		}

		if ( $id_or_email instanceof WP_Comment ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				return $id_or_email->user_id;
			} else if ( ! empty( $id_or_email->comment_author_email ) ) {

				// If user_id not available, set as email address to handle below.
				$id_or_email = $id_or_email->comment_author_email;
			}
		}

		return false;
	}

	private static function try_get_user_id_from_number( $id_or_email ) {

		if ( is_numeric( $id_or_email ) ) {
			return $id_or_email;
		}

		return false;
	}

	private static function try_get_user_id_from_email( &$id_or_email ) {

		if ( is_string( $id_or_email ) && strpos( $id_or_email, '@' ) ) {
			$id_or_email = get_user_by( 'email', $id_or_email );
		}

		return false;
	}

	private static function try_get_user_id_from_user( $id_or_email ) {
		if ( is_a( $id_or_email, 'WP_User' ) ) {
			return $id_or_email->ID;
		}

		return false;
	}

	private static function get_image_url( $args, $user_id ) {

		$image_id = get_user_meta( $user_id, 'avatar', true );

		if ( $image_id ) {

			$image_url = wp_get_attachment_image_src( $image_id, 'full' );

			$avatar_url = $image_url[0];

			if ( isset( $args['size'] ) ) {
				$avatar_url = \Timber\ImageHelper::resize( $avatar_url, $args['size'], $args['size'], 'default' );
			}

			if ( filter_var( $avatar_url, FILTER_VALIDATE_URL ) ) {
				return $avatar_url;
			}
		}

		return false;
	}
}
