<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Posts;

use Timber\Post;

/**
 * Timber post model for the 'dadesobertes' post type.
 *
 * Registered through the 'timber/post/classmap' filter (see
 * Softcatala\TypeRegisters\DadesObertes), so every dadesobertes post
 * returned by Timber is an instance of this class, both in PHP and Twig.
 */
class DadesObertes extends Post {

	/**
	 * Datasets created on Hugging Face within this window are considered new.
	 */
	const NEW_WINDOW = 28 * DAY_IN_SECONDS;

	/**
	 * Whether the dataset was created on Hugging Face within NEW_WINDOW.
	 *
	 * @return bool
	 */
	public function is_new() {
		$created = $this->meta( 'hf_created_at' );

		return (bool) ( $created && strtotime( $created ) >= time() - self::NEW_WINDOW );
	}
}
